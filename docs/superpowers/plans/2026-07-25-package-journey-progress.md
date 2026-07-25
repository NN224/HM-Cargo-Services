# Package Journey Progress Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a real seven-step, route-aware package journey that staff can advance for all or selected packages, with delays, administrator corrections, and a privacy-safe public progress bar.

**Architecture:** Keep package rows and append-only package events as the physical source of truth. A focused `PackageJourneyService` owns authorization, row locking, forward transitions, delays, corrections, audit logging, and shipment aggregation; Filament actions and the public controller consume projections instead of duplicating rules.

**Tech Stack:** PHP 8.5, Laravel 13, Filament 5, Livewire 4, Pest 4, SQLite tests, self-hosted PostgreSQL production.

## Global Constraints

- Arabic-first RTL UI and Arabic validation/errors.
- Seven fixed journey steps with dynamic origin airport, destination airport, and delivery office labels.
- No user-editable workflow engine and no paid external dependency.
- Package facts drive shipment progress; batch state remains independent.
- Ordinary employees advance one step forward only and may act only in an authorized route/warehouse context.
- Administrators may move forward or backward only with a required reason.
- Delay and correction reasons are private unless the actor explicitly publishes them.
- Multi-package operations are transactional, lock rows, and fail atomically.
- Missing, damaged, cancelled, and collected packages cannot be silently advanced.
- Public tracking receives an explicit safe projection and no full Eloquent model.
- Money remains integer cents and weight remains exact decimal.
- Every behavior change starts with a failing Pest test and every PHP change is formatted with Pint.

---

### Task 1: Canonical Decision, Route Labels, and Journey Event Schema

**Files:**
- Create: `database/migrations/2026_07_25_100000_add_journey_labels_to_routes_table.php`
- Create: `database/migrations/2026_07_25_100001_add_journey_metadata_to_packages_and_events.php`
- Create: `database/migrations/2026_07_25_100002_create_audit_logs_table.php`
- Create: `app/Models/AuditLog.php`
- Create: `tests/Feature/PackageJourneySchemaTest.php`
- Modify: `app/Models/Route.php`
- Modify: `app/Models/Package.php`
- Modify: `app/Enums/PackageStatus.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Modify: `docs/decisions.md`
- Modify: `docs/product-spec.md`
- Modify: `docs/domain/shipment-lifecycle.md`
- Modify: `docs/architecture/data-model.md`
- Modify: `docs/superpowers/specs/2026-07-18-hm-cargo-system-design.md`
- Modify: `AGENTS.md`

**Interfaces:**
- Produces: `PackageStatus::ArrivedOriginAirport`.
- Produces: `PackageStatus::journeySteps(): array<int, PackageStatus>`.
- Produces: `PackageStatus::journeyPosition(): ?int`.
- Produces route attributes `origin_airport_name`, `destination_airport_name`, and `delivery_office_name`.
- Produces package delay attributes `is_delayed`, `delay_reason`, `delay_reason_is_public`, and `delayed_at`.
- Produces event columns `previous_status`, `event_kind`, `private_reason`, and `public_reason`.
- Produces append-only `AuditLog` rows for privileged journey corrections.

- [ ] **Step 1: Write the failing schema and enum tests**

```php
test('the package journey has seven ordered normal steps', function () {
    expect(PackageStatus::journeySteps())->toBe([
        PackageStatus::ReceivedOrigin,
        PackageStatus::ArrivedOriginAirport,
        PackageStatus::InTransit,
        PackageStatus::ArrivedTransit,
        PackageStatus::DepartedTransit,
        PackageStatus::ArrivedDestination,
        PackageStatus::Collected,
    ]);
});

test('a route stores the three dynamic journey labels', function () {
    $route = Route::factory()->create([
        'origin_airport_name' => 'مطار دبي',
        'destination_airport_name' => 'مطار بيروت',
        'delivery_office_name' => 'مكتب دمشق',
    ]);

    expect($route->origin_airport_name)->toBe('مطار دبي')
        ->and($route->destination_airport_name)->toBe('مطار بيروت')
        ->and($route->delivery_office_name)->toBe('مكتب دمشق');
});
```

- [ ] **Step 2: Run the tests and verify RED**

Run: `php artisan test --compact tests/Feature/PackageJourneySchemaTest.php`

Expected: FAIL because the new enum case, methods, and route columns do not exist.

- [ ] **Step 3: Generate and implement the migrations**

Run:

```bash
php artisan make:migration add_journey_labels_to_routes_table --table=routes --no-interaction
php artisan make:migration add_journey_metadata_to_packages_and_events --no-interaction
```

Use nullable route columns during migration so existing rows remain readable:

```php
Schema::table('routes', function (Blueprint $table): void {
    $table->string('origin_airport_name')->nullable();
    $table->string('destination_airport_name')->nullable();
    $table->string('delivery_office_name')->nullable();
});

Schema::table('packages', function (Blueprint $table): void {
    $table->boolean('is_delayed')->default(false);
    $table->text('delay_reason')->nullable();
    $table->boolean('delay_reason_is_public')->default(false);
    $table->timestamp('delayed_at')->nullable();
});

Schema::table('package_status_events', function (Blueprint $table): void {
    $table->string('previous_status')->nullable();
    $table->string('event_kind')->default('progress');
    $table->text('private_reason')->nullable();
    $table->text('public_reason')->nullable();
});

Schema::create('audit_logs', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->string('action');
    $table->string('auditable_type');
    $table->unsignedBigInteger('auditable_id');
    $table->json('before');
    $table->json('after');
    $table->text('reason');
    $table->timestamp('created_at');
    $table->index(['auditable_type', 'auditable_id', 'created_at']);
});
```

- [ ] **Step 4: Implement the append-only audit model**

```php
final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('سجل التدقيق غير قابل للتعديل.'));
        static::deleting(fn () => throw new LogicException('سجل التدقيق غير قابل للحذف.'));
    }
}
```

- [ ] **Step 5: Implement the fixed journey enum API**

```php
case ArrivedOriginAirport = 'arrived_origin_airport';

/** @return array<int, self> */
public static function journeySteps(): array
{
    return [
        self::ReceivedOrigin,
        self::ArrivedOriginAirport,
        self::InTransit,
        self::ArrivedTransit,
        self::DepartedTransit,
        self::ArrivedDestination,
        self::Collected,
    ];
}

public function journeyPosition(): ?int
{
    $position = array_search($this, self::journeySteps(), true);

    return $position === false ? null : $position;
}
```

- [ ] **Step 6: Backfill seeded routes and document D-030**

Add `D-030: Fixed package journey with dynamic route locations` to `docs/decisions.md`. State that D-030 replaces direct-route skipping of transit-shaped states, keeps batch/package state machines separate, and requires append-only progress, delay, and correction events.

Update every listed canonical document in the same change. Seed all three route labels for each initial route without adding airport APIs or delivery logistics.

- [ ] **Step 7: Run migration and domain tests**

Run:

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan test --compact tests/Feature/PackageJourneySchemaTest.php tests/Feature/RoutesAndRatesTest.php
```

Expected: PASS.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --format agent app/Enums/PackageStatus.php app/Models/Route.php app/Models/Package.php database/migrations tests/Feature/PackageJourneySchemaTest.php
git add AGENTS.md app/Enums/PackageStatus.php app/Models/Route.php app/Models/Package.php app/Models/AuditLog.php database/migrations database/seeders/DatabaseSeeder.php docs tests/Feature/PackageJourneySchemaTest.php
git commit -m "feat: define package journey schema"
```

---

### Task 2: Transactional Forward Progress for All or Selected Packages

**Files:**
- Create: `app/Services/PackageJourneyService.php`
- Create: `tests/Feature/PackageJourneyProgressTest.php`
- Modify: `app/Models/Shipment.php`

**Interfaces:**
- Consumes: `PackageStatus::journeySteps()` and `journeyPosition()`.
- Produces: `PackageJourneyService::advance(Shipment $shipment, array $packageIds, User $actor): Shipment`.
- Produces: `Shipment::recalculateOperationalStatus()` support for the new origin-airport step.

- [ ] **Step 1: Write failing forward-progress tests**

Cover all selected packages, a selected subset, one-step-only enforcement, exception protection, warehouse authorization, and atomic rollback:

```php
test('an employee advances selected packages by exactly one step', function () {
    $shipment = journeyShipment([
        PackageStatus::ReceivedOrigin,
        PackageStatus::ReceivedOrigin,
    ]);
    $selected = $shipment->packages->first();

    app(PackageJourneyService::class)->advance(
        $shipment,
        [$selected->id],
        $this->originEmployee,
    );

    expect($selected->fresh()->status)->toBe(PackageStatus::ArrivedOriginAirport)
        ->and($shipment->packages->last()->fresh()->status)->toBe(PackageStatus::ReceivedOrigin);
});

test('one ineligible selected package rolls back the complete operation', function () {
    $shipment = journeyShipment([
        PackageStatus::ReceivedOrigin,
        PackageStatus::Damaged,
    ]);

    expect(fn () => app(PackageJourneyService::class)->advance(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
    ))->toThrow(DomainException::class);

    expect($shipment->packages->first()->fresh()->status)
        ->toBe(PackageStatus::ReceivedOrigin);
});
```

- [ ] **Step 2: Run tests and verify RED**

Run: `php artisan test --compact tests/Feature/PackageJourneyProgressTest.php`

Expected: FAIL because `PackageJourneyService` does not exist.

- [ ] **Step 3: Implement the service with one transaction and stable lock order**

```php
public function advance(Shipment $shipment, array $packageIds, User $actor): Shipment
{
    return DB::transaction(function () use ($shipment, $packageIds, $actor): Shipment {
        $lockedShipment = Shipment::query()->lockForUpdate()->findOrFail($shipment->id);
        $packages = $lockedShipment->packages()
            ->whereKey($packageIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $this->authorizeSelection($lockedShipment, $packages, $packageIds, $actor);

        foreach ($packages as $package) {
            $next = $this->nextStatus($package->status);
            $this->recordProgress($package, $next, $actor);
        }

        $lockedShipment->recalculateOperationalStatus();

        return $lockedShipment->fresh();
    });
}
```

Validate the full selection before the first update. Require exact ID equality, reject exception/terminal states, and use `User::canUseRoute()` for the shipment batch route.

Authorize the actor's assigned warehouse against the target step:

- origin warehouse for origin-warehouse arrival, origin-airport arrival, and origin-airport departure;
- transit warehouse for destination-airport arrival/departure when the route has transit;
- destination warehouse for destination-airport arrival/departure on a direct route, delivery-office arrival, and collection.

Administrators may operate every step.

- [ ] **Step 4: Extend shipment aggregation**

Map the new origin-airport stage to `ShipmentStatus::InTransit` while retaining detailed per-package counts in the journey projection. Do not add seven duplicate shipment enum states; package facts remain the detailed source.

- [ ] **Step 5: Run focused and regression tests**

Run:

```bash
php artisan test --compact tests/Feature/PackageJourneyProgressTest.php
php artisan test --compact tests/Feature/PackageScanTest.php tests/Feature/ShipmentCollectionTest.php tests/Feature/ExceptionPackageChargeTest.php
```

Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --format agent app/Services/PackageJourneyService.php app/Models/Shipment.php tests/Feature/PackageJourneyProgressTest.php
git add app/Services/PackageJourneyService.php app/Models/Shipment.php tests/Feature/PackageJourneyProgressTest.php
git commit -m "feat: advance selected packages through journey"
```

---

### Task 3: Delay Overlay and Administrator Corrections

**Files:**
- Modify: `app/Services/PackageJourneyService.php`
- Create: `tests/Feature/PackageJourneyDelayTest.php`
- Create: `tests/Feature/PackageJourneyCorrectionTest.php`

**Interfaces:**
- Produces: `PackageJourneyService::delay(Shipment $shipment, array $packageIds, User $actor, string $reason, bool $publishReason): Shipment`.
- Produces: `PackageJourneyService::correct(Shipment $shipment, array $packageIds, PackageStatus $target, User $administrator, string $reason, bool $publishReason): Shipment`.

- [ ] **Step 1: Write failing delay tests**

```php
test('an employee delays selected packages with a private reason', function () {
    $shipment = journeyShipment([PackageStatus::ArrivedOriginAirport]);

    app(PackageJourneyService::class)->delay(
        $shipment,
        $shipment->packages->modelKeys(),
        $this->originEmployee,
        'تأخر التحميل',
        false,
    );

    $package = $shipment->packages->first()->fresh();

    expect($package->is_delayed)->toBeTrue()
        ->and($package->delay_reason)->toBe('تأخر التحميل')
        ->and($package->delay_reason_is_public)->toBeFalse();
});

test('forward progress clears the current delay but retains its event', function () {
    $shipment = journeyShipment([PackageStatus::ArrivedOriginAirport]);
    $package = $shipment->packages->first();
    $service = app(PackageJourneyService::class);

    $service->delay(
        $shipment,
        [$package->id],
        $this->originEmployee,
        'تأخر التحميل',
        true,
    );
    $service->advance($shipment, [$package->id], $this->originEmployee);

    expect($package->fresh()->is_delayed)->toBeFalse()
        ->and(DB::table('package_status_events')
            ->where('package_id', $package->id)
            ->where('event_kind', 'delay')
            ->exists())->toBeTrue();
});
```

Replace the explanatory comment in the second test with the complete arrangement and assertions before running it.

- [ ] **Step 2: Write failing administrator-correction tests**

Test that an employee cannot move backward, an administrator can move selected packages backward with a reason, an empty reason is rejected, and public/private reasons populate only the appropriate event column.

- [ ] **Step 3: Run both files and verify RED**

Run:

```bash
php artisan test --compact tests/Feature/PackageJourneyDelayTest.php tests/Feature/PackageJourneyCorrectionTest.php
```

Expected: FAIL because `delay()` and `correct()` do not exist.

- [ ] **Step 4: Implement delay and correction through the same locked selection**

```php
public function delay(
    Shipment $shipment,
    array $packageIds,
    User $actor,
    string $reason,
    bool $publishReason,
): Shipment

public function correct(
    Shipment $shipment,
    array $packageIds,
    PackageStatus $target,
    User $administrator,
    string $reason,
    bool $publishReason,
): Shipment
```

Use a private `withLockedSelection()` boundary so authorization, exact selection checks, lock order, and atomic behavior cannot drift between operations. `correct()` must call `isAdministrator()` and reject non-journey targets. Both methods write `package_status_events`; corrections additionally create:

```php
AuditLog::create([
    'user_id' => $administrator->id,
    'action' => 'package_journey_corrected',
    'auditable_type' => Package::class,
    'auditable_id' => $package->id,
    'before' => ['status' => $previous->value],
    'after' => ['status' => $target->value],
    'reason' => $reason,
    'created_at' => now(),
]);
```

- [ ] **Step 5: Verify delay, correction, and progress together**

Run:

```bash
php artisan test --compact tests/Feature/PackageJourneyDelayTest.php tests/Feature/PackageJourneyCorrectionTest.php tests/Feature/PackageJourneyProgressTest.php
```

Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --format agent app/Services/PackageJourneyService.php tests/Feature/PackageJourneyDelayTest.php tests/Feature/PackageJourneyCorrectionTest.php
git add app/Services/PackageJourneyService.php tests/Feature/PackageJourneyDelayTest.php tests/Feature/PackageJourneyCorrectionTest.php
git commit -m "feat: record journey delays and corrections"
```

---

### Task 4: Route Configuration and Journey Projection

**Files:**
- Create: `app/Services/PackageJourneyProjection.php`
- Create: `tests/Feature/PackageJourneyProjectionTest.php`
- Modify: `app/Filament/Resources/Routes/Schemas/RouteForm.php`
- Modify: `app/Filament/Resources/Routes/Tables/RoutesTable.php`
- Modify: `tests/Feature/RoutesAndRatesTest.php`

**Interfaces:**
- Produces: `PackageJourneyProjection::forShipment(Shipment $shipment): array`.
- Projection shape:

```php
array{
    steps: array<int, array{
        status: string,
        label: string,
        completed_count: int,
        current_count: int,
        delayed_count: int
    }>,
    package_count: int,
    delayed_count: int
}
```

- [ ] **Step 1: Write failing dynamic-label projection tests**

Assert these exact Arabic labels for a Dubai-to-Beirut route:

```php
[
    'وصل مستودع دبي',
    'وصل مطار دبي',
    'غادر مطار دبي',
    'وصل مطار بيروت',
    'غادر مطار بيروت',
    'وصل مكتب دمشق',
    'استلمه العميل',
]
```

Also assert split counts when two packages occupy different steps and a third is delayed.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --compact tests/Feature/PackageJourneyProjectionTest.php`

Expected: FAIL because the projection class does not exist.

- [ ] **Step 3: Implement the projection**

Use one eager-loaded package collection and no queries inside the seven-step loop. Derive labels from route fields and warehouse names. Throw a clear `DomainException` from staff actions when labels are incomplete; read-only projections may return a safe “غير مضبوط” label.

- [ ] **Step 4: Add required route fields to Filament**

```php
TextInput::make('origin_airport_name')
    ->label('مطار الانطلاق')
    ->required(fn (Get $get): bool => (bool) $get('is_active'))
    ->maxLength(255),

TextInput::make('destination_airport_name')
    ->label('مطار الوصول')
    ->required(fn (Get $get): bool => (bool) $get('is_active'))
    ->maxLength(255),

TextInput::make('delivery_office_name')
    ->label('مكتب التسليم')
    ->required(fn (Get $get): bool => (bool) $get('is_active'))
    ->maxLength(255),
```

Display the three values on the routes table without introducing a separate workflow configuration screen.

- [ ] **Step 5: Run projection and route tests**

Run:

```bash
php artisan test --compact tests/Feature/PackageJourneyProjectionTest.php tests/Feature/RoutesAndRatesTest.php
```

Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --format agent app/Services/PackageJourneyProjection.php app/Filament/Resources/Routes tests/Feature/PackageJourneyProjectionTest.php tests/Feature/RoutesAndRatesTest.php
git add app/Services/PackageJourneyProjection.php app/Filament/Resources/Routes tests/Feature/PackageJourneyProjectionTest.php tests/Feature/RoutesAndRatesTest.php
git commit -m "feat: configure dynamic journey locations"
```

---

### Task 5: Interactive Shipment-Card Progress Bar

**Files:**
- Create: `app/Filament/Resources/Shipments/Actions/ManageJourneyAction.php`
- Create: `resources/views/filament/tables/columns/package-journey.blade.php`
- Create: `tests/Feature/ShipmentJourneyUiTest.php`
- Modify: `app/Filament/Resources/Shipments/Tables/ShipmentsTable.php`
- Modify: `app/Filament/Resources/Shipments/Pages/ListShipments.php`

**Interfaces:**
- Consumes: `PackageJourneyService` and `PackageJourneyProjection`.
- Produces one table action named `manageJourney`.
- Produces one custom column view receiving only the journey projection array.

- [ ] **Step 1: Write failing Livewire tests**

Test that each shipment card has one visual boundary, does not render duplicated `الرحلة: الرحلة:`, shows all seven dynamic labels, allows an employee to mount forward/delay actions, and hides administrator correction controls from employees.

Use `TestAction::make('manageJourney')->table($shipment)` for the row action and assert Arabic validation when no packages are selected.

- [ ] **Step 2: Verify RED**

Run: `php artisan test --compact tests/Feature/ShipmentJourneyUiTest.php`

Expected: FAIL because the action and column view do not exist.

- [ ] **Step 3: Implement the action as a focused class**

The modal fields are:

```php
Select::make('operation')
    ->options([
        'advance' => 'تقديم للمرحلة التالية',
        'delay' => 'تسجيل تأخير',
        'correct' => 'تصحيح إداري',
    ])
    ->required()
    ->live(),

CheckboxList::make('package_ids')
    ->label('الطرود')
    ->options(fn (Shipment $record): array => $record->packages()
        ->orderBy('id')
        ->pluck('barcode', 'id')
        ->all())
    ->required(),

Textarea::make('reason')
    ->label('السبب')
    ->required(fn (Get $get): bool => in_array($get('operation'), ['delay', 'correct'], true)),

Toggle::make('publish_reason')
    ->label('إظهار السبب للعميل في رابط التتبع')
    ->default(false),
```

Show the correction target only for administrators. Dispatch exclusively to `PackageJourneyService`; do not update models in the action.

- [ ] **Step 4: Implement the responsive progress column**

Render a semantic ordered list with completed/current/delayed/upcoming states and package counts. Use a horizontally scrollable seven-step sequence on phones. Do not add inline CSS to `ListShipments.php`; place styles in the existing panel stylesheet or Tailwind classes supported by the project.

Remove default batch grouping and show the batch reference once inside the card. Preserve the user's current card layout changes outside this scope.

- [ ] **Step 5: Run UI and existing shipment tests**

Run:

```bash
php artisan test --compact tests/Feature/ShipmentJourneyUiTest.php tests/Feature/ShipmentUiTest.php tests/Feature/ShipmentViewTest.php
```

Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --format agent app/Filament/Resources/Shipments/Actions/ManageJourneyAction.php app/Filament/Resources/Shipments/Tables/ShipmentsTable.php tests/Feature/ShipmentJourneyUiTest.php
git add app/Filament/Resources/Shipments resources/views/filament/tables/columns/package-journey.blade.php tests/Feature/ShipmentJourneyUiTest.php
git commit -m "feat: manage journey from shipment cards"
```

---

### Task 6: Privacy-Safe Public Journey Tracking

**Files:**
- Modify: `app/Http/Controllers/TrackingController.php`
- Modify: `resources/views/tracking/show.blade.php`
- Modify: `tests/Feature/PublicTrackingTest.php`
- Create: `tests/Feature/PublicJourneyTrackingTest.php`

**Interfaces:**
- Consumes the same seven-step semantics as `PackageJourneyProjection`.
- Produces `tracking['journey']` with labels, counts, delayed flags, and published reasons only.

- [ ] **Step 1: Write failing privacy and rendering tests**

Create public and private delay/correction events. Assert the public response includes dynamic labels and published reasons, but excludes:

```php
[
    'user_id',
    'employee',
    'private_reason',
    'delay_reason',
    'internal',
    'batch cost',
    'profit',
    'collector',
]
```

Assert package counts at split steps and customer-safe Arabic wording.

- [ ] **Step 2: Verify RED**

Run:

```bash
php artisan test --compact tests/Feature/PublicJourneyTrackingTest.php tests/Feature/PublicTrackingTest.php
```

Expected: FAIL because the public projection has no seven-step journey.

- [ ] **Step 3: Extend only the safe SQL projection**

Select route label fields, package IDs/barcodes only when needed for safe counts, statuses, delay flags, and `package_status_events.public_reason`. Never select `private_reason`, `user_id`, internal notes, costs, rates, or employee relations.

Build:

```php
'journey' => [
    'steps' => $steps,
    'published_events' => $publishedEvents,
],
```

Pass the existing `$tracking` array to Blade; do not pass models.

- [ ] **Step 4: Render the read-only responsive bar**

Reuse the visual meanings from the administration bar without controls. Show a warning summary for delayed packages and only published reasons.

- [ ] **Step 5: Run public privacy regressions**

Run:

```bash
php artisan test --compact tests/Feature/PublicJourneyTrackingTest.php tests/Feature/PublicTrackingTest.php tests/Feature/TrackingQrTest.php tests/Feature/PackageTrackingUrlTest.php
```

Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --format agent app/Http/Controllers/TrackingController.php tests/Feature/PublicJourneyTrackingTest.php tests/Feature/PublicTrackingTest.php
git add app/Http/Controllers/TrackingController.php resources/views/tracking/show.blade.php tests/Feature/PublicJourneyTrackingTest.php tests/Feature/PublicTrackingTest.php
git commit -m "feat: show safe public journey progress"
```

---

### Task 7: Scan and Collection Compatibility

**Files:**
- Modify: `app/Services/PackageScanService.php`
- Modify: `app/Services/ShipmentCollectionService.php`
- Modify: `app/Filament/Pages/ScanPackages.php`
- Modify: `tests/Feature/PackageScanTest.php`
- Modify: `tests/Feature/ShipmentCollectionTest.php`

**Interfaces:**
- Consumes: `PackageJourneyService::advance()`.
- Keeps barcode scanning as an authenticated shortcut into the same transition rules.
- Keeps D-029 partial collection and complete collection transactional.

- [ ] **Step 1: Write failing compatibility tests**

Assert that a scan at a valid checkpoint advances only one ordered step, cannot skip origin-airport or destination-airport stages, and collection still changes only `ArrivedDestination` packages to `Collected`.

- [ ] **Step 2: Verify RED**

Run:

```bash
php artisan test --compact tests/Feature/PackageScanTest.php tests/Feature/ShipmentCollectionTest.php
```

Expected: at least the old direct-route skip test fails against the new required order.

- [ ] **Step 3: Route scanning through the journey service**

Keep barcode normalization and warehouse authorization in `PackageScanService`, but delegate the locked status transition and event creation to `PackageJourneyService`. Do not retain a second transition table in `arrivalStatus()`.

Update `ScanPackages` Arabic guidance to name the expected next step and reject a scan performed at an unrelated route location.

- [ ] **Step 4: Preserve collection semantics**

Collection continues to release only `ArrivedDestination` packages. Ensure the collection event uses `previous_status`, clears delay metadata, uses `event_kind = progress`, and recalculates the shipment.

- [ ] **Step 5: Run operational regressions**

Run:

```bash
php artisan test --compact tests/Feature/PackageScanTest.php tests/Feature/ShipmentCollectionTest.php tests/Feature/E2EVerificationTest.php
```

Expected: PASS.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --format agent app/Services/PackageScanService.php app/Services/ShipmentCollectionService.php app/Filament/Pages/ScanPackages.php tests/Feature/PackageScanTest.php tests/Feature/ShipmentCollectionTest.php
git add app/Services/PackageScanService.php app/Services/ShipmentCollectionService.php app/Filament/Pages/ScanPackages.php tests/Feature/PackageScanTest.php tests/Feature/ShipmentCollectionTest.php
git commit -m "refactor: align scanning with package journey"
```

---

### Task 8: Full Verification and Rendered QA

**Files:**
- Modify: `docs/current-state.md`
- Modify only when a verified defect requires it: files from Tasks 1-7.

**Interfaces:**
- Produces verified desktop, phone, authorization, privacy, and regression evidence.

- [ ] **Step 1: Run formatting and static diff checks**

```bash
vendor/bin/pint --dirty --format agent
git diff --check
```

Expected: both commands exit 0. Do not repair unrelated pre-existing files; scope any formatting command when the shared working tree contains unrelated changes.

- [ ] **Step 2: Run the complete suite**

Run: `php artisan test --compact`

Expected: all tests pass with zero failures.

- [ ] **Step 3: Run database rebuild proof**

Run:

```bash
php artisan migrate:fresh --seed --no-interaction
php artisan test --compact tests/Feature/PackageJourneySchemaTest.php tests/Feature/PackageJourneyProgressTest.php
```

Expected: migrations, seeders, and focused tests pass on a clean SQLite database.

- [ ] **Step 4: Perform rendered administration QA**

Start the local server using the project's normal command. Verify in a real browser at desktop and phone widths:

- shipment cards remain clearly separated;
- duplicated group headings are gone;
- all seven labels fit or scroll without overlap;
- selected-package modal works;
- employee controls exclude correction;
- administrator correction requires a reason;
- delayed and split-package states are visually distinct.

Capture screenshots for desktop and phone evidence.

- [ ] **Step 5: Perform rendered public-tracking QA**

Open one valid public tracking token containing split progress, one public delay, and one private reason. Verify:

- seven dynamic labels render;
- only the public reason appears;
- employee and internal fields are absent;
- phone layout has no horizontal page overflow;
- QR and payment summary still work.

- [ ] **Step 6: Update current state and commit verification documentation**

Update `docs/current-state.md` with the fresh test and assertion counts and mark real package journey progress as built but still requiring real warehouse/airport operational trial.

```bash
git add docs/current-state.md
git commit -m "docs: record package journey verification"
```
