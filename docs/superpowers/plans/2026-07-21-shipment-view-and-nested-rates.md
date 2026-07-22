# Shipment View and Nested Rates Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an operator open a shipment and see its packages, tell the two creation screens apart, and find a customer's rates on the customer instead of in a separate tab.

**Architecture:** Four independent UI changes, no domain logic and no schema change. A `ViewShipment` page follows the existing `ViewRecord` pattern used by `ViewBatch` and `StatementCustomer`. The rates table attaches to the customer as a read-only infolist section, and `CustomerRateResource` keeps all its pages while dropping out of the navigation.

**Tech Stack:** Laravel 13.20, Filament 5.7, Pest 4.7, SQLite (`:memory:` for tests), Arabic RTL.

## Global Constraints

- Money is integer cents in storage; dollars in display. Conversion at the boundary only, never a stored float.
- Weight is `decimal(12,4)`, summed in SQL, never in PHP.
- All user-facing strings are Arabic. Comments explain *why*, not *what*.
- Authorization is enforced in backend guards, never by hiding UI alone.
- No changes to `app/Models/**`, no migrations, no changes to `app/Services/**`.
- Filament pages and tables render through Livewire — assert with `Livewire::test(...)`, never `get()`.
- Do NOT `git commit` until `php artisan test` passes in full.
- Do NOT touch `app/Filament/Resources/CustomerRates/Pages/EditCustomerRate.php`. It carries a confirmation guard that took several attempts to make fire at all, and its failure mode is silent.

---

### Task 1: A shipment can be opened, and its packages are on the page

**Files:**
- Create: `app/Filament/Resources/Shipments/Pages/ViewShipment.php`
- Modify: `app/Filament/Resources/Shipments/ShipmentResource.php` (add the `view` page to `getPages()`)
- Test: `tests/Feature/ShipmentViewTest.php`

**Interfaces:**
- Consumes: `Shipment::packages()` (HasMany), `Package` fields `barcode`, `weight_kg`, `description`, `source_barcode`, `status` (cast to `App\Enums\PackageStatus`, which has `label(): string`)
- Produces: route name `filament.admin.resources.shipments.view`, class `App\Filament\Resources\Shipments\Pages\ViewShipment`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ShipmentViewTest.php`:

```php
<?php

use App\Enums\PackageStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Opening a shipment.
 *
 * Packages existed in the product only as a count on the list, so no screen
 * would tell you a barcode, a single package's weight, or which box had not
 * arrived. An operator asked "which of these three hasn't turned up?" had
 * nowhere to look.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $this->damascus->id,
    ]);
});

test('every package appears with its barcode and weight', function () {
    $this->shipment->packages()->create(['weight_kg' => 2.5, 'description' => 'ملابس']);
    $this->shipment->packages()->create(['weight_kg' => 1.25, 'description' => 'أدوات']);

    $barcodes = $this->shipment->packages()->pluck('barcode');

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee($barcodes[0])
        ->assertSee($barcodes[1])
        ->assertSee('2.5')
        ->assertSee('1.25');
});

test('each package shows its own status, not the shipment stage', function () {
    // The whole reason to open a shipment sitting at partial_at_destination is
    // to learn which box is missing. A page that renders one status for every
    // row cannot answer that, so the statuses here are deliberately different.
    $arrived = $this->shipment->packages()->create(['weight_kg' => 1.0]);
    $missing = $this->shipment->packages()->create(['weight_kg' => 1.0]);

    $arrived->forceFill(['status' => PackageStatus::ArrivedDestination])->save();
    $missing->forceFill(['status' => PackageStatus::Missing])->save();

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee(PackageStatus::ArrivedDestination->label())
        ->assertSee(PackageStatus::Missing->label());
});

test('a cancelled package is still listed', function () {
    // The page records what was received, not only what is billable. A box
    // that was cancelled still physically passed through the counter (D-023).
    $cancelled = $this->shipment->packages()->create(['weight_kg' => 3.0]);
    $cancelled->forceFill(['status' => PackageStatus::Cancelled])->save();

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee($cancelled->fresh()->barcode)
        ->assertSee(PackageStatus::Cancelled->label());
});

test('the supplier barcode is shown when one was recorded', function () {
    $this->shipment->packages()->create([
        'weight_kg' => 1.0,
        'source_barcode' => 'SUP-99887',
    ]);

    Livewire::actingAs($this->admin)
        ->test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee('SUP-99887');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter ShipmentViewTest`
Expected: FAIL — `Class "App\Filament\Resources\Shipments\Pages\ViewShipment" not found.`

- [ ] **Step 3: Write the page**

Create `app/Filament/Resources/Shipments/Pages/ViewShipment.php`. Follow `app/Filament/Resources/Customers/Pages/StatementCustomer.php` — same `ViewRecord` base, same `infolist(Schema $schema)` method, same `Section` / `RepeatableEntry` / `TextEntry` components.

```php
<?php

namespace App\Filament\Resources\Shipments\Pages;

use App\Enums\PackageStatus;
use App\Filament\Resources\Shipments\ShipmentResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * A shipment and the boxes it is made of.
 *
 * Package status is per row on purpose. A shipment sitting at
 * partial_at_destination is telling the operator that some box has not
 * arrived, and this is the only screen that says which one.
 */
class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $shipment = $this->record;

        return $schema
            ->state([
                'reference' => $shipment->reference,
                'customer' => $shipment->customer->name,
                'recipient' => $shipment->recipient_name,
                'recipient_phone' => $shipment->recipient_phone,
                'destination' => $shipment->destinationWarehouse?->name ?? '—',
                'status' => $shipment->status->label(),
                'total_weight' => rtrim(rtrim(number_format((float) $shipment->total_weight_kg, 4), '0'), '.').' كغ',
                'packages' => $shipment->packages->map(fn ($package): array => [
                    'barcode' => $package->barcode,
                    'weight' => rtrim(rtrim(number_format((float) $package->weight_kg, 4), '0'), '.').' كغ',
                    'description' => $package->description ?: '—',
                    'source_barcode' => $package->source_barcode ?: '—',
                    'status' => $package->status->label(),
                    'status_colour' => $package->status === PackageStatus::Cancelled
                        ? 'gray'
                        : ($package->status->isException() ? 'danger' : 'success'),
                ])->all(),
            ])
            ->components([
                Section::make('بيانات الشحنة')
                    ->schema([
                        TextEntry::make('reference')->label('رقم الشحنة'),
                        TextEntry::make('customer')->label('العميل'),
                        TextEntry::make('recipient')->label('المستلم'),
                        TextEntry::make('recipient_phone')->label('هاتف المستلم'),
                        TextEntry::make('destination')->label('مستودع الوجهة'),
                        TextEntry::make('status')->label('حالة الشحنة')->badge(),
                        TextEntry::make('total_weight')->label('الوزن الإجمالي'),
                    ])
                    ->columns(3),

                Section::make('الطرود')
                    ->schema([
                        RepeatableEntry::make('packages')
                            ->label('')
                            ->schema([
                                TextEntry::make('barcode')->label('الباركود')->copyable(),
                                TextEntry::make('weight')->label('الوزن'),
                                TextEntry::make('description')->label('الوصف'),
                                TextEntry::make('source_barcode')->label('باركود المورّد'),
                                TextEntry::make('status')
                                    ->label('حالة الطرد')
                                    ->badge()
                                    ->color(fn ($state, $record): string => $record['status_colour'] ?? 'gray'),
                            ])
                            ->columns(5),
                    ]),
            ]);
    }
}
```

If `->color(fn ($state, $record) => ...)` does not receive the row array in this Filament version, read `vendor/filament/infolists/` for how `RepeatableEntry` passes row state to a child entry's closure, use the correct signature, and note the substitution in your report. Do not drop the per-row colour to make it compile.

- [ ] **Step 4: Register the page**

In `app/Filament/Resources/Shipments/ShipmentResource.php`, add the import and the route. The `getPages()` method currently reads:

```php
    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'create' => CreateShipment::route('/create'),
            'edit' => EditShipment::route('/{record}/edit'),
        ];
    }
```

Replace it with:

```php
    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
            'create' => CreateShipment::route('/create'),
            'view' => ViewShipment::route('/{record}'),
            'edit' => EditShipment::route('/{record}/edit'),
        ];
    }
```

Add `use App\Filament\Resources\Shipments\Pages\ViewShipment;` alongside the existing page imports.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter ShipmentViewTest`
Expected: PASS, 4 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Shipments/ tests/Feature/ShipmentViewTest.php
git commit -m "feat: open a shipment and see its packages

Packages existed only as a count on the list, so nothing in the product would
tell you a barcode, one box's weight, or which of three had not arrived. A
barcode was visible only on a printed label.

Status is per row, because that is the question the page exists to answer: a
shipment at partial_at_destination is saying some box is missing, and this
says which."
```

---

### Task 2: Add a view button to the shipments list

**Files:**
- Modify: `app/Filament/Resources/Shipments/Tables/ShipmentsTable.php`
- Test: `tests/Feature/ShipmentViewTest.php` (append)

**Interfaces:**
- Consumes: `ViewShipment` route registered in Task 1

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/ShipmentViewTest.php`:

```php
test('the list offers a way into a shipment', function () {
    // A view page nobody can reach is not a feature.
    $this->shipment->packages()->create(['weight_kg' => 1.0]);

    Livewire::actingAs($this->admin)
        ->test(\App\Filament\Resources\Shipments\Pages\ListShipments::class)
        ->assertTableActionVisible('view', $this->shipment->id);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter "the list offers a way into a shipment"`
Expected: FAIL — `Action [view] not found on table.`

- [ ] **Step 3: Add the action**

In `app/Filament/Resources/Shipments/Tables/ShipmentsTable.php`, inside `->recordActions([...])`, add `ViewAction::make(),` as the **first** entry, before `EditAction::make()`.

Add `use Filament\Actions\ViewAction;` to the imports.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter ShipmentViewTest`
Expected: PASS, 5 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/Shipments/Tables/ShipmentsTable.php tests/Feature/ShipmentViewTest.php
git commit -m "feat: reach a shipment from the list"
```

---

### Task 3: Each creation screen says which situation it is for

**Files:**
- Modify: `app/Filament/Resources/Shipments/Schemas/ShipmentForm.php`
- Modify: `app/Filament/Pages/ReceiveIntoBatch.php`
- Test: `tests/Feature/CreationDoorsTest.php`

**Interfaces:** none produced.

Both screens create a shipment with packages and ask nearly the same questions. They are not redundant — cargo sometimes arrives with a batch already open and sometimes before one exists — but nothing on either screen says which situation it serves.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CreationDoorsTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Filament\Resources\Shipments\Pages\CreateShipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Two doors that both create a shipment.
 *
 * Neither is redundant: cargo arrives sometimes with a batch already open and
 * sometimes before one exists. They were only silent about which is which,
 * and an operator opening the menu could not tell them apart.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => $dubai->id,
    ]);
});

test('the shipment form says the shipment is not yet on a batch', function () {
    Livewire::actingAs($this->admin)
        ->test(CreateShipment::class)
        ->assertSee('غير مرتبطة برحلة');
});

test('the intake screen says it prices on the spot', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->assertSee('رحلة مفتوحة');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter CreationDoorsTest`
Expected: FAIL, 2 tests — neither string is on the page.

- [ ] **Step 3: Add the sentence to the shipment form**

In `app/Filament/Resources/Shipments/Schemas/ShipmentForm.php`, the first `Section::make(...)` in the schema gains a description. Add this call to that section, directly after `Section::make('...')`:

```php
                    // The operator has two ways to create a shipment. Naming
                    // the situation each one serves is cheaper than expecting
                    // them to infer it from which fields are present.
                    ->description('شحنة غير مرتبطة برحلة بعد — تُسعَّر عند إسنادها لاحقاً.')
```

- [ ] **Step 4: Add the sentence to the intake screen**

In `app/Filament/Pages/ReceiveIntoBatch.php`, the `Section::make('بيانات الاستلام')` already carries a `->description(...)`. Replace that description's string with:

```php
                    ->description('استلام مباشر في رحلة مفتوحة — يُسعَّر فوراً بسعر العميل المتفق عليه.')
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter CreationDoorsTest`
Expected: PASS, 2 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Shipments/Schemas/ShipmentForm.php app/Filament/Pages/ReceiveIntoBatch.php tests/Feature/CreationDoorsTest.php
git commit -m "feat: each creation screen names the situation it is for

Two screens create a shipment and ask nearly the same questions. Neither is
redundant — cargo arrives sometimes with a batch open and sometimes before one
exists — but the operator could not tell which was which. So they get a
sentence each rather than one of them getting deleted."
```

---

### Task 4: The supplier barcode can be recorded at intake

**Files:**
- Modify: `app/Filament/Pages/ReceiveIntoBatch.php`
- Modify: `app/Services/BatchIntakeService.php`
- Test: `tests/Feature/BatchIntakeTest.php` (append)

**Interfaces:**
- Consumes: `BatchIntakeService::receive(Batch $batch, array $data, User $actor): Shipment`
- Produces: `$data['packages'][n]` accepts an optional `source_barcode` string.

The same box captures different data depending on which door it came through: `source_barcode` is on the shipment form and missing from intake.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/BatchIntakeTest.php`:

```php
test('a supplier barcode given at intake reaches the package', function () {
    // The same box must capture the same facts through either door.
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [
            ['weight_kg' => 1.0, 'description' => null, 'source_barcode' => 'SUP-4471'],
        ],
    ], $this->actor);

    expect($shipment->packages->first()->source_barcode)->toBe('SUP-4471');
});

test('a package with no supplier barcode is still accepted', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ], $this->actor);

    expect($shipment->packages->first()->source_barcode)->toBeNull();
});
```

`$this->actor` is the acting user that file's `beforeEach` already defines (BatchIntakeTest.php:42) — use it as written.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter "a supplier barcode given at intake"`
Expected: FAIL — the value is null, because the service does not pass it through.

- [ ] **Step 3: Pass it through the service**

In `app/Services/BatchIntakeService.php`, the package creation loop currently reads:

```php
            foreach ($packages as $package) {
                $shipment->packages()->create([
                    'weight_kg' => $package['weight_kg'],
                    'description' => $package['description'] ?? null,
                ]);
            }
```

Replace it with:

```php
            foreach ($packages as $package) {
                $shipment->packages()->create([
                    'weight_kg' => $package['weight_kg'],
                    'description' => $package['description'] ?? null,
                    // The supplier's own label, when the box carries one. The
                    // shipment form has always captured this; intake did not,
                    // so the same box recorded different facts depending on
                    // which screen received it.
                    'source_barcode' => $package['source_barcode'] ?? null,
                ]);
            }
```

- [ ] **Step 4: Add the field to the intake form**

In `app/Filament/Pages/ReceiveIntoBatch.php`, inside the `Repeater::make('packages')` schema, after the `description` field, add:

```php
                                TextInput::make('source_barcode')
                                    ->label('باركود المورّد (اختياري)'),
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter BatchIntakeTest`
Expected: PASS — the file's existing tests plus 2.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Pages/ReceiveIntoBatch.php app/Services/BatchIntakeService.php tests/Feature/BatchIntakeTest.php
git commit -m "feat: record the supplier barcode at intake too

The shipment form captured it and intake did not, so the same box recorded
different facts depending on which screen received it."
```

---

### Task 5: A customer's rates live on the customer

**Files:**
- Modify: `app/Filament/Resources/Customers/Pages/EditCustomer.php`
- Modify: `app/Filament/Resources/CustomerRates/CustomerRateResource.php`
- Modify: `tests/Feature/NavigationTest.php`
- Test: `tests/Feature/CustomerRatesOnCustomerTest.php`

**Interfaces:**
- Consumes: `Customer::rates()` (HasMany to `CustomerRate`), `CustomerRate::$rate_per_kg_cents` (integer), `CustomerRate::route` (BelongsTo)

A rate is a customer's agreed price on a route and has no meaning apart from the customer, but it lived in its own navigation tab.

**Do not touch `EditCustomerRate.php`.** Its confirmation guard fires only because the form wrapper was removed and the save action rebuilt as a closure, and when that breaks it breaks silently. Editing continues to happen there.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CustomerRatesOnCustomerTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Filament\Resources\CustomerRates\CustomerRateResource;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * A rate belongs to its customer.
 *
 * "العملاء" and "أسعار العملاء" were two navigation entries for one subject,
 * and an operator could not tell why both existed.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);
    $beirut = Warehouse::create(['name' => 'Beirut', 'location' => 'Lebanon']);

    $this->direct = Route::create([
        'name' => 'Dubai → Syria (direct)',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->viaBeirut = Route::create([
        'name' => 'Dubai → Beirut → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
        'transit_warehouse_id' => $beirut->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

test('a customer page lists their rate on every route they have one for', function () {
    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id,
        'rate_per_kg_cents' => 300,
    ]);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->viaBeirut->id,
        'rate_per_kg_cents' => 275,
    ]);

    Livewire::actingAs($this->admin)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('Dubai → Syria (direct)')
        ->assertSee('Dubai → Beirut → Syria')
        // Dollars on screen; cents in storage.
        ->assertSee('3.00')
        ->assertSee('2.75');
});

test('a customer with no agreed rates says so rather than showing an empty box', function () {
    Livewire::actingAs($this->admin)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('لا توجد أسعار متفق عليها');
});

test('customer rates no longer take a navigation entry of their own', function () {
    expect(CustomerRateResource::shouldRegisterNavigation())->toBeFalse();
});

test('the rates pages stay reachable, because the edit link depends on them', function () {
    // Hiding a resource from navigation must not unregister its routes: the
    // rate is still changed there, behind the confirmation that guards it.
    $rate = CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->direct->id,
        'rate_per_kg_cents' => 300,
    ]);

    expect(CustomerRateResource::getUrl('edit', ['record' => $rate]))
        ->toBeString()
        ->toContain('customer-rates');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter CustomerRatesOnCustomerTest`
Expected: FAIL, 3 of 4 — the rates are not on the customer page and the resource still registers navigation.

- [ ] **Step 3: Hide the resource from navigation**

In `app/Filament/Resources/CustomerRates/CustomerRateResource.php`, add this method to the class:

```php
    /**
     * A rate is read on its customer's page. Its own pages stay registered —
     * changing a rate still happens there, behind the confirmation that names
     * the old and new figures — but it is no longer a destination of its own.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
```

Leave `$navigationGroup`, `$navigationSort` and `$navigationIcon` in place: `NavigationTest` reads them by reflection and a hidden resource with no declared group would fail that check for no benefit.

- [ ] **Step 4: Show the rates on the customer**

In `app/Filament/Resources/Customers/Pages/EditCustomer.php`, override `content()` to render the form and then the rates beneath it.

`EditRecord::content(Schema $schema)` is the supported hook — the same method `ScanPackages` and `ReceiveIntoBatch` already use. Its default body (vendor/filament/filament/src/Resources/Pages/EditRecord.php:430) composes `getFormContentComponent()` and `getRelationManagersContentComponent()`; keep both and append the section, so nothing the base page renders is lost:

```php
    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
                $this->getRelationManagersContentComponent(),
                ...$this->ratesSection(),
            ]);
    }

    /**
     * A customer's agreed prices, read-only.
     *
     * Read here and changed on the rate's own page: the confirmation that
     * names the old and new figures fires only through that page's rebuilt
     * save action, and it fails silently when it breaks.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    private function ratesSection(): array
    {
        $rates = $this->record->rates()->with('route')->get();

        return [
            Section::make('الأسعار المتفق عليها')
                ->description('سعر هذا العميل على كل مسار. لتغيير سعر، افتحه من زر التعديل.')
                ->schema([
                    RepeatableEntry::make('rates')
                        ->label('')
                        ->state($rates->map(fn (CustomerRate $rate): array => [
                            'route' => $rate->route->name,
                            'rate' => number_format($rate->rate_per_kg_cents / 100, 2),
                            'url' => CustomerRateResource::getUrl('edit', ['record' => $rate]),
                        ])->all())
                        ->schema([
                            TextEntry::make('route')->label('المسار'),
                            TextEntry::make('rate')->label('السعر لكل كيلو (دولار)'),
                            TextEntry::make('url')
                                ->label('')
                                ->formatStateUsing(fn (): string => 'تعديل السعر')
                                ->url(fn ($state): string => $state),
                        ])
                        ->columns(3)
                        ->visible($rates->isNotEmpty()),

                    TextEntry::make('no_rates')
                        ->label('')
                        ->state('لا توجد أسعار متفق عليها مع هذا العميل بعد.')
                        ->visible($rates->isEmpty()),
                ]),
        ];
    }
```

Add the imports the method needs: `Filament\Schemas\Schema`, `App\Filament\Resources\CustomerRates\CustomerRateResource`, `App\Models\CustomerRate`, `Filament\Infolists\Components\RepeatableEntry`, `Filament\Infolists\Components\TextEntry`, `Filament\Schemas\Components\Section`.

- [ ] **Step 5: Drop the rates entry from the navigation test**

In `tests/Feature/NavigationTest.php`, remove this line from the `navigationItems()` array:

```php
        'أسعار العملاء' => CustomerRateResource::class,
```

Leave the `use App\Filament\Resources\CustomerRates\CustomerRateResource;` import only if something else in the file still uses it; otherwise remove it too.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter CustomerRatesOnCustomerTest`
Expected: PASS, 4 tests.

- [ ] **Step 7: Run the full suite**

Run: `php artisan test`
Expected: PASS. `AdminPanelLocalisationTest` still drives `ListCustomerRates` directly and must keep passing — the page is hidden from navigation, not removed.

- [ ] **Step 8: Commit**

```bash
git add app/Filament/Resources/ tests/Feature/
git commit -m "feat: a customer's rates live on the customer

Two navigation entries for one subject: a rate is a customer's agreed price on
a route and means nothing apart from them.

The rates table attaches to the customer read-only and the tab goes. Editing
still happens on the rate's own page, because the confirmation guarding a rate
change fires only through a mechanism that took several attempts to get right
and fails silently when it breaks."
```

---

### Task 6: Update the handoff state

**Files:**
- Modify: `docs/current-state.md`

- [ ] **Step 1: Update the state file**

In the "Built and tested" table, add:

```markdown
| Shipment view with its packages, per-package status | done |
```

Update the header's test and assertion counts to the figures `php artisan test` actually prints.

- [ ] **Step 2: Verify the counts are real**

Run: `php artisan test`
Copy the reported `tests` and `assertions` figures into the header. Do not estimate them.

- [ ] **Step 3: Commit**

```bash
git add docs/current-state.md
git commit -m "docs: record the shipment view in the handoff state"
```
