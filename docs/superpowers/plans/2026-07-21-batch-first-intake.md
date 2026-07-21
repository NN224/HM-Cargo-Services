# Batch-First Intake Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an operator open a batch and receive a customer's packages into it directly, instead of building a shipment record and then attaching it.

**Architecture:** One new service (`BatchIntakeService`) wraps the existing `BatchAssignmentService` in a single transaction: it creates the shipment with the destination taken from the batch route, creates the packages, then assigns and prices through the existing path. One new Filament page hosts the form. No model, migration, or pricing rule changes.

**Tech Stack:** Laravel 13.20, Filament 5.7, Pest 4.7, SQLite (`:memory:` for tests), Arabic RTL throughout.

## Global Constraints

- Money is integer cents everywhere. Multiplication uses `bcmul`. No floats in any money path.
- Weight is `decimal(12,4)`, summed in SQL, never in PHP.
- Derived columns (`total_weight_kg`) are absent from `$fillable` — assign with `forceFill`, never `update()`, which discards them silently.
- All user-facing strings are Arabic.
- Authorization is enforced in backend policies and action guards, never by hiding UI alone (AGENTS.md:82).
- Filament tables and forms render through Livewire — assert with `Livewire::test(...)`, never `get()`.
- Comments explain *why*, matching `app/Services/BatchAssignmentService.php`.
- Never `git commit` without the full suite passing: `php artisan test`.
- No new migrations. No changes to `app/Models/**` except where a task says so explicitly.

---

### Task 1: Intake service creates, assigns and prices in one transaction

**Files:**
- Create: `app/Services/BatchIntakeService.php`
- Test: `tests/Feature/BatchIntakeTest.php`

**Interfaces:**
- Consumes: `BatchAssignmentService::assign(Shipment $shipment, Batch $batch): Shipment`
- Produces: `BatchIntakeService::receive(Batch $batch, array $data): Shipment`

  `$data` keys:
  - `customer_id` (int, required)
  - `recipient_is_customer` (bool, default true)
  - `recipient_name` (string, required when `recipient_is_customer` is false)
  - `recipient_phone` (string, required when `recipient_is_customer` is false)
  - `packages` (array of `['weight_kg' => string|float, 'description' => ?string]`, at least one)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BatchIntakeTest.php`:

```php
<?php

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->service = app(BatchIntakeService::class);
});

test('intake creates a priced shipment attached to the batch', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [
            ['weight_kg' => 2.5, 'description' => 'ملابس'],
            ['weight_kg' => 1.5, 'description' => null],
        ],
    ]);

    expect($shipment->batch_id)->toBe($this->batch->id)
        ->and($shipment->packages)->toHaveCount(2)
        ->and((string) $shipment->total_weight_kg)->toBe('4.0000')
        ->and($shipment->rate_per_kg_cents)->toBe(300)
        // 4.0 kg × 300 cents
        ->and($shipment->final_charge_cents)->toBe(1200);
});

test('the destination comes from the batch route, not from the caller', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->destination_warehouse_id)->toBe($this->damascus->id);
});

test('the recipient defaults to the customer', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->recipient_name)->toBe('أحمد')
        ->and($shipment->recipient_phone)->toBe('+971500000001');
});

test('a different recipient is kept when given', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => false,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->recipient_name)->toBe('سامي')
        ->and($shipment->recipient_phone)->toBe('+9613000001');
});

test('every package gets its own barcode', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [
            ['weight_kg' => 1.0, 'description' => null],
            ['weight_kg' => 2.0, 'description' => null],
        ],
    ]);

    $barcodes = $shipment->packages->pluck('barcode');

    expect($barcodes)->toHaveCount(2)
        ->and($barcodes->unique())->toHaveCount(2)
        ->and($barcodes->filter())->toHaveCount(2);
});

test('at least one package is required', function () {
    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $this->customer->id,
        'recipient_is_customer' => true,
        'packages' => [],
    ]))->toThrow(DomainException::class);
});

test('a customer with no rate for this route is refused and nothing is written', function () {
    $stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    $before = Shipment::count();

    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $stranger->id,
        'recipient_is_customer' => true,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]))->toThrow(DomainException::class);

    // The whole intake is one transaction: a refusal leaves no orphan
    // shipment or package behind.
    expect(Shipment::count())->toBe($before);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter BatchIntakeTest`
Expected: FAIL — `Target class [App\Services\BatchIntakeService] does not exist.`

- [ ] **Step 3: Write the service**

Create `app/Services/BatchIntakeService.php`:

```php
<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Shipment;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Receives a customer's packages directly into a batch.
 *
 * Cargo arrives across the week and the load leaves on a fixed day, so the
 * operator works from the batch inwards: open Thursday's load, add what just
 * came in. Building a shipment first and attaching it afterwards describes
 * the same facts in an order nobody works in.
 *
 * This does not reprice anything or bypass a rule. It fills in what the batch
 * already knows — the destination, the route, and therefore the rate — and
 * hands the result to BatchAssignmentService, which remains the only place a
 * shipment becomes billable.
 */
class BatchIntakeService
{
    public function __construct(
        private readonly BatchAssignmentService $assignment,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws DomainException when the intake cannot be completed
     */
    public function receive(Batch $batch, array $data): Shipment
    {
        return DB::transaction(function () use ($batch, $data): Shipment {
            $packages = $data['packages'] ?? [];

            if ($packages === []) {
                throw new DomainException('أضف طرداً واحداً على الأقل قبل الحفظ.');
            }

            $customer = Customer::findOrFail($data['customer_id']);

            [$name, $phone] = $this->resolveRecipient($customer, $data);

            $shipment = Shipment::create([
                'customer_id' => $customer->id,
                'recipient_name' => $name,
                'recipient_phone' => $phone,
                // The operator is standing inside a batch bound somewhere
                // specific. Asking them to retype that destination only
                // creates a chance to contradict it.
                'destination_warehouse_id' => $batch->route->destination_warehouse_id,
            ]);

            foreach ($packages as $package) {
                $shipment->packages()->create([
                    'weight_kg' => $package['weight_kg'],
                    'description' => $package['description'] ?? null,
                ]);
            }

            $shipment->recalculateTotalWeight();

            // Pricing stays where it has always been. If the customer has no
            // agreed rate for this route, assign() refuses and this whole
            // transaction unwinds — no half-received cargo.
            return $this->assignment->assign($shipment->fresh(), $batch);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string, 1: string}
     */
    private function resolveRecipient(Customer $customer, array $data): array
    {
        if (($data['recipient_is_customer'] ?? true) === true) {
            return [$customer->name, $customer->phone];
        }

        return [$data['recipient_name'], $data['recipient_phone']];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter BatchIntakeTest`
Expected: PASS, 7 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS — previous total plus 7.

- [ ] **Step 6: Commit**

```bash
git add app/Services/BatchIntakeService.php tests/Feature/BatchIntakeTest.php
git commit -m "feat: receive packages directly into a batch

Cargo arrives across the week and the load leaves on a fixed day, so the
operator works from the batch inwards. The service fills in what the batch
already knows — destination, route, and so the rate — and hands off to
BatchAssignmentService, which stays the only place a shipment is priced.

One transaction: a customer with no agreed rate is refused and leaves no
orphan shipment behind."
```

---

### Task 2: Intake form, with money hidden from anyone who may not price

**Files:**
- Create: `app/Filament/Pages/ReceiveIntoBatch.php`
- Test: `tests/Feature/BatchIntakeUiTest.php`

**Interfaces:**
- Consumes: `BatchIntakeService::receive(Batch $batch, array $data): Shipment`
- Produces: a Filament page at `/admin/receive-into-batch`, class `App\Filament\Pages\ReceiveIntoBatch`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BatchIntakeUiTest.php`:

```php
<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $this->damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);
});

test('an employee without the pricing capability can still receive cargo', function () {
    // D-024: applying a rate agreed before the goods moved is not a pricing
    // decision. Gating it would stop an employee doing most of their job.
    expect($this->clerk->hasCapability(Capability::PriceShipments))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $this->customer->id,
            'recipient_is_customer' => true,
            'packages' => [['weight_kg' => 2.0, 'description' => null]],
        ])
        ->call('receive')
        ->assertHasNoFormErrors();

    expect(Shipment::where('batch_id', $this->batch->id)->count())->toBe(1);
});

test('the rate and total are hidden from an employee who may not price', function () {
    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldHidden('rate_per_kg');
});

test('the rate is offered to a user who may price', function () {
    Livewire::actingAs($this->admin)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldVisible('rate_per_kg');
});

test('a different recipient is accepted when the box is unticked', function () {
    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $this->customer->id,
            'recipient_is_customer' => false,
            'recipient_name' => 'سامي',
            'recipient_phone' => '+9613000001',
            'packages' => [['weight_kg' => 2.0, 'description' => null]],
        ])
        ->call('receive')
        ->assertHasNoFormErrors();

    expect(Shipment::first()->recipient_name)->toBe('سامي');
});

test('a customer with no agreed rate is refused and nothing is saved', function () {
    $stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->fillForm([
            'batch_id' => $this->batch->id,
            'customer_id' => $stranger->id,
            'recipient_is_customer' => true,
            'packages' => [['weight_kg' => 2.0, 'description' => null]],
        ])
        ->call('receive');

    expect(Shipment::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter BatchIntakeUiTest`
Expected: FAIL — `Class "App\Filament\Pages\ReceiveIntoBatch" not found.`

- [ ] **Step 3: Write the page**

Create `app/Filament/Pages/ReceiveIntoBatch.php`. Follow the structure of `app/Filament/Pages/ScanPackages.php` — same `Page` base class, same `$data` property, same `form()`/`content()` split, same `Notification` usage on success and failure.

```php
<?php

namespace App\Filament\Pages;

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\User;
use App\Services\BatchIntakeService;
use BackedEnum;
use DomainException;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Receiving cargo into a batch that is already open.
 *
 * The money fields exist only for a user who may price. An employee
 * receiving boxes records a customer and some weights and never sees a
 * figure — the rate was agreed before the cargo moved (D-024).
 */
class ReceiveIntoBatch extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'استلام بضاعة';

    protected static ?string $title = 'استلام بضاعة في رحلة';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(['recipient_is_customer' => true]);
    }

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الاستلام')
                    ->description('اختر الرحلة والعميل ثم أدخل أوزان الطرود.')
                    ->schema([
                        Select::make('batch_id')
                            ->label('الرحلة')
                            ->options(fn (): array => Batch::query()
                                ->where('status', BatchStatus::Open)
                                ->orderByDesc('created_at')
                                ->pluck('reference', 'id')
                                ->all())
                            ->searchable()
                            ->required(),

                        Select::make('customer_id')
                            ->label('العميل')
                            ->options(fn (): array => Customer::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live(),

                        Checkbox::make('recipient_is_customer')
                            ->label('المستلم هو العميل')
                            ->default(true)
                            ->live(),

                        TextInput::make('recipient_name')
                            ->label('اسم المستلم')
                            ->visible(fn (Get $get): bool => ! $get('recipient_is_customer'))
                            ->required(fn (Get $get): bool => ! $get('recipient_is_customer')),

                        TextInput::make('recipient_phone')
                            ->label('هاتف المستلم')
                            ->visible(fn (Get $get): bool => ! $get('recipient_is_customer'))
                            ->required(fn (Get $get): bool => ! $get('recipient_is_customer')),

                        // Only a user who may price sees a price. For everyone
                        // else the figure does not exist on this screen.
                        TextInput::make('rate_per_kg')
                            ->label('سعر الكيلو (دولار)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn (): bool => auth()->user()?->hasCapability(Capability::PriceShipments) ?? false)
                            ->helperText('سعر العميل المتفق عليه على مسار هذه الرحلة.'),

                        Repeater::make('packages')
                            ->label('الطرود')
                            ->schema([
                                TextInput::make('weight_kg')
                                    ->label('الوزن (كغ)')
                                    ->numeric()
                                    ->step('0.0001')
                                    ->minValue(0.0001)
                                    ->required(),

                                TextInput::make('description')
                                    ->label('وصف اختياري'),
                            ])
                            ->minItems(1)
                            ->defaultItems(1)
                            ->addActionLabel('إضافة طرد'),
                    ]),
            ])
            ->statePath('data');
    }

    public function receive(): void
    {
        $state = $this->form->getState();

        $batch = Batch::findOrFail($state['batch_id']);

        try {
            $shipment = app(BatchIntakeService::class)->receive($batch, $state);

            Notification::make()
                ->title('تم الاستلام')
                ->body("أُنشئت الشحنة {$shipment->reference} وأُسندت إلى الرحلة {$batch->reference}.")
                ->success()
                ->send();

            $this->form->fill(['recipient_is_customer' => true]);
        } catch (DomainException $e) {
            Notification::make()
                ->title('تعذّر الاستلام')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
```

There is **no Blade view**. This project's Filament pages render entirely
through schemas — `resources/views/filament/pages/` does not exist. Add a
`content()` method exactly as `ScanPackages` does, and the extra imports it
needs:

```php
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('intake-form')
                ->livewireSubmitHandler('receive')
                ->footer([
                    Actions::make([
                        Action::make('receive')
                            ->label('تسجيل الاستلام')
                            ->icon(Heroicon::OutlinedInboxArrowDown)
                            ->submit('receive'),
                    ])->fullWidth(),
                ]),
        ]);
    }
```

Additional imports for this method:

```php
use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Support\Icons\Heroicon;
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter BatchIntakeUiTest`
Expected: PASS, 5 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Pages/ReceiveIntoBatch.php resources/views/filament/pages/ tests/Feature/BatchIntakeUiTest.php
git commit -m "feat: a screen for receiving cargo into an open batch

The operator picks the batch and the customer, leaves the recipient box
ticked unless somebody else is collecting, and types weights. Destination,
rate and barcodes come from what the system already knows.

Per D-024 the rate is shown only to a user who may price. An employee
receiving boxes never sees a figure."
```

---

### Task 3: Inline rate for a customer nobody has settled terms with

**Files:**
- Modify: `app/Services/BatchIntakeService.php`
- Modify: `app/Filament/Pages/ReceiveIntoBatch.php`
- Test: `tests/Feature/BatchIntakeRateTest.php`

**Interfaces:**
- Consumes: `BatchIntakeService::receive(Batch $batch, array $data): Shipment`
- Produces: `$data` gains an optional `agreed_rate_per_kg_cents` (int). When present and the customer has no rate for the batch route, a `CustomerRate` is created before assignment.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BatchIntakeRateTest.php`:

```php
<?php

use App\Enums\BatchStatus;
use App\Models\Batch;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Services\BatchIntakeService;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $this->route->id,
        'status' => BatchStatus::Open,
    ]);

    $this->stranger = Customer::create(['name' => 'غريب', 'phone' => '+971500000099']);

    $this->service = app(BatchIntakeService::class);
});

test('an agreed rate supplied at intake becomes the customer rate and prices the shipment', function () {
    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 275,
        'packages' => [['weight_kg' => 2.0, 'description' => null]],
    ]);

    expect($shipment->rate_per_kg_cents)->toBe(275)
        ->and($shipment->final_charge_cents)->toBe(550);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(275);
});

test('an agreed rate never overwrites a rate that already exists', function () {
    CustomerRate::create([
        'customer_id' => $this->stranger->id,
        'route_id' => $this->route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $shipment = $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        // Ignored. Changing an agreed rate is the rates screen's job, behind
        // its confirmation — not a side effect of receiving boxes.
        'agreed_rate_per_kg_cents' => 999,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]);

    expect($shipment->rate_per_kg_cents)->toBe(300);

    expect(CustomerRate::where('customer_id', $this->stranger->id)
        ->where('route_id', $this->route->id)
        ->value('rate_per_kg_cents'))->toBe(300);
});

test('a zero or negative agreed rate is refused and nothing is written', function () {
    expect(fn () => $this->service->receive($this->batch, [
        'customer_id' => $this->stranger->id,
        'recipient_is_customer' => true,
        'agreed_rate_per_kg_cents' => 0,
        'packages' => [['weight_kg' => 1.0, 'description' => null]],
    ]))->toThrow(DomainException::class);

    expect(Shipment::count())->toBe(0)
        ->and(CustomerRate::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter BatchIntakeRateTest`
Expected: FAIL — the first test throws the "no agreed rate" `DomainException` from `BatchAssignmentService`.

- [ ] **Step 3: Add rate handling to the service**

In `app/Services/BatchIntakeService.php`, insert this call immediately after `$customer = Customer::findOrFail($data['customer_id']);` inside the transaction:

```php
            $this->recordAgreedRateIfMissing($customer, $batch, $data);
```

Then add the method to the class:

```php
    /**
     * Record a first agreed rate, when one was supplied and none exists.
     *
     * A customer with no rate for this route is one nobody has settled terms
     * with yet. The system still refuses to invent a figure — but a user
     * entitled to record the agreement may do it here rather than break off
     * to another screen mid-intake.
     *
     * An existing rate is never touched. Changing an agreed price belongs on
     * the rates screen behind its confirmation, not to a side effect of
     * receiving boxes.
     *
     * @param  array<string, mixed>  $data
     */
    private function recordAgreedRateIfMissing(Customer $customer, Batch $batch, array $data): void
    {
        $agreed = $data['agreed_rate_per_kg_cents'] ?? null;

        if ($agreed === null) {
            return;
        }

        if ($customer->rateForRoute($batch->route) !== null) {
            return;
        }

        if ((int) $agreed <= 0) {
            throw new DomainException('سعر الكيلو المتفق عليه يجب أن يكون أكبر من صفر.');
        }

        CustomerRate::create([
            'customer_id' => $customer->id,
            'route_id' => $batch->route_id,
            'rate_per_kg_cents' => (int) $agreed,
        ]);
    }
```

Add `use App\Models\CustomerRate;` to the file's imports.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter BatchIntakeRateTest`
Expected: PASS, 3 tests.

- [ ] **Step 5: Add the field to the form**

In `app/Filament/Pages/ReceiveIntoBatch.php`, add this component immediately after the `rate_per_kg` field:

```php
                        // Shown only when this customer has no agreed rate for
                        // this route yet, and only to someone entitled to
                        // record the agreement. Everyone else is refused with
                        // a message naming who can supply it.
                        TextInput::make('agreed_rate_per_kg_cents')
                            ->label('سعر الكيلو المتفق عليه (سنت)')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => $this->needsAgreedRate($get))
                            ->required(fn (Get $get): bool => $this->needsAgreedRate($get))
                            ->helperText('لا يوجد سعر متفق عليه لهذا العميل على مسار هذه الرحلة.'),
```

And add this method to the class:

```php
    /**
     * Whether the form must ask for a first agreed rate.
     *
     * Only for a user holding manage_customers. Without it the intake is
     * refused by the service, and offering a field they may not use would be
     * a worse experience than a clear message.
     */
    private function needsAgreedRate(Get $get): bool
    {
        if (! (auth()->user()?->hasCapability(Capability::ManageCustomers) ?? false)) {
            return false;
        }

        $batchId = $get('batch_id');
        $customerId = $get('customer_id');

        if (! $batchId || ! $customerId) {
            return false;
        }

        $batch = Batch::find($batchId);
        $customer = Customer::find($customerId);

        if (! $batch || ! $customer) {
            return false;
        }

        return $customer->rateForRoute($batch->route) === null;
    }
```

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Services/BatchIntakeService.php app/Filament/Pages/ReceiveIntoBatch.php tests/Feature/BatchIntakeRateTest.php
git commit -m "feat: record a first agreed rate during intake

A customer with no rate for the route is one nobody has settled terms with.
The system still refuses to invent a figure, but a user holding
manage_customers may record the agreement without breaking off to another
screen.

An existing rate is never touched here. Changing an agreed price stays on
the rates screen behind its confirmation."
```

---

### Task 4: Confirm before an agreed rate is replaced

**Files:**
- Modify: `app/Filament/Resources/CustomerRates/Pages/EditCustomerRate.php`
- Test: `tests/Feature/RateChangeConfirmationTest.php`

**Interfaces:**
- Consumes: `CustomerRate::$rate_per_kg_cents`
- Produces: no new API. The edit form requires a confirmation naming the old and new figures before saving a changed rate.

The resource already exists at `app/Filament/Resources/CustomerRates/` with `Pages/EditCustomerRate.php`, `Pages/CreateCustomerRate.php`, `Pages/ListCustomerRates.php`, `Schemas/CustomerRateForm.php` and `Tables/CustomerRatesTable.php`. This task touches only `Pages/EditCustomerRate.php` — creating a first rate needs no confirmation, because there is no figure being replaced.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/RateChangeConfirmationTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Filament\Resources\CustomerRates\Pages\EditCustomerRate;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->rate = CustomerRate::create([
        'customer_id' => $customer->id,
        'route_id' => $route->id,
        'rate_per_kg_cents' => 300,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

test('the save action requires confirmation', function () {
    // A typed rate is a standing agreement: a slipped decimal becomes the
    // customer's price until somebody notices. One click is enough to catch
    // it, and is not ceremony.
    Livewire::actingAs($this->admin)
        ->test(EditCustomerRate::class, ['record' => $this->rate->getRouteKey()])
        ->assertActionRequiresConfirmation('save');
});

test('a confirmed change is saved', function () {
    Livewire::actingAs($this->admin)
        ->test(EditCustomerRate::class, ['record' => $this->rate->getRouteKey()])
        ->fillForm(['rate_per_kg_cents' => 350])
        ->call('save');

    expect($this->rate->fresh()->rate_per_kg_cents)->toBe(350);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter RateChangeConfirmationTest`
Expected: FAIL — the save action has no confirmation.

- [ ] **Step 3: Add the confirmation**

In `app/Filament/Resources/CustomerRates/Pages/EditCustomerRate.php`, override the save action:

```php
    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation()
            ->modalHeading('تغيير سعر متفق عليه')
            ->modalDescription(function (): string {
                $old = number_format($this->record->rate_per_kg_cents / 100, 2);
                $new = number_format(((int) ($this->data['rate_per_kg_cents'] ?? 0)) / 100, 2);

                return "سعر {$this->record->customer->name} على {$this->record->route->name} كان {$old} — بدك تخليه {$new}؟";
            });
    }
```

Add `use Filament\Actions\Action;` to the file's imports.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter RateChangeConfirmationTest`
Expected: PASS, 2 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/CustomerRates/ tests/Feature/RateChangeConfirmationTest.php
git commit -m "feat: confirm before replacing an agreed rate

A rate is a standing agreement, so a slipped decimal does not spoil one load
— it becomes the customer's price until somebody catches it. The confirmation
names both figures."
```

---

### Task 5: Create a customer without leaving the form

**Files:**
- Modify: `app/Filament/Pages/ReceiveIntoBatch.php`
- Test: `tests/Feature/BatchIntakeNewCustomerTest.php`

**Interfaces:**
- Consumes: `Customer::create(['name' => string, 'phone' => string])`
- Produces: no new API. The `customer_id` select gains a create-option form.

Filament's `createOptionForm()` is not used anywhere else in this codebase yet, so there is no local precedent to copy — the code below is complete.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BatchIntakeNewCustomerTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Pages\ReceiveIntoBatch;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);
});

test('an employee holding manage_customers is offered customer creation', function () {
    $this->clerk->grantCapability(Capability::ManageCustomers);

    Livewire::actingAs($this->clerk->fresh())
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('customer_id', checkFieldUsing: fn ($field): bool =>
            $field->getCreateOptionActionForm() !== null);
});

test('an employee without it is not', function () {
    expect($this->clerk->hasCapability(Capability::ManageCustomers))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(ReceiveIntoBatch::class)
        ->assertFormFieldExists('customer_id', checkFieldUsing: fn ($field): bool =>
            $field->getCreateOptionActionForm() === null);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter BatchIntakeNewCustomerTest`
Expected: FAIL — the first test fails because no create-option form is attached.

- [ ] **Step 3: Attach the create-option form**

In `app/Filament/Pages/ReceiveIntoBatch.php`, add these calls to the existing `customer_id` select, after `->live()`:

```php
                            // Offered only to a user who may manage customers.
                            // A walk-in customer should not send the operator
                            // to another screen mid-intake.
                            ->createOptionForm(fn (): ?array =>
                                (auth()->user()?->hasCapability(Capability::ManageCustomers) ?? false)
                                    ? [
                                        TextInput::make('name')
                                            ->label('اسم العميل')
                                            ->required(),
                                        TextInput::make('phone')
                                            ->label('رقم الهاتف')
                                            ->required(),
                                    ]
                                    : null)
                            ->createOptionUsing(fn (array $data): int => Customer::create([
                                'name' => $data['name'],
                                'phone' => $data['phone'],
                            ])->id)
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter BatchIntakeNewCustomerTest`
Expected: PASS, 2 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Pages/ReceiveIntoBatch.php tests/Feature/BatchIntakeNewCustomerTest.php
git commit -m "feat: create a customer during intake

A walk-in customer should not send the operator to another screen in the
middle of receiving boxes. Offered only to a user who may manage customers;
for everyone else the option is absent, not merely hidden."
```

---

### Task 6: Update the handoff state

**Files:**
- Modify: `docs/current-state.md`

- [ ] **Step 1: Update the state file**

In the "Built and tested" table, add:

```markdown
| Batch-first intake — receive cargo into an open batch | done |
```

In "What the system can do today", add a sentence after the existing intake description:

```markdown
Cargo can be received the way it arrives: open the load that leaves on
Thursday and add each customer's boxes as they come in, with the destination,
rate and barcodes filled in from what the batch already knows.
```

Update the header test count to the actual number reported by `php artisan test`.

- [ ] **Step 2: Verify the counts are real**

Run: `php artisan test`
Copy the reported `tests` and `assertions` figures into the header. Do not estimate them.

- [ ] **Step 3: Commit**

```bash
git add docs/current-state.md
git commit -m "docs: record batch-first intake in the handoff state"
```
