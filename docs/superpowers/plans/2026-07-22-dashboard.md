# Adaptive Dashboard — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace Filament's default dashboard with widgets that show operational counts to everyone and money only to a money-holder.

**Architecture:** Four small auto-discovered Filament widgets, each self-gating via a static `canView()`, plus removal of the two default library widgets. No new service, no migration. Money widgets reuse the exact `canView()` + integer-cents pattern of `BatchProfitabilityWidget`.

**Tech Stack:** Laravel 13.20, Filament 5.7, Pest 4.7, SQLite (`:memory:` for tests), Arabic RTL.

## Global Constraints

- Authorization is enforced server-side: a widget hidden by `canView()` is not rendered and its query does not run. Never rely on hiding a rendered value.
- An administrator implicitly holds every capability (`User::hasCapability` returns true for administrators). Never gate a money widget so an administrator loses it.
- Money is integer cents in storage; dollars on screen; conversion at the display boundary only. Reuse the `formatUsd(int $cents)` idiom from `BatchProfitabilityWidget`.
- Weight, where it appears, stays in SQL.
- All user-facing strings are Arabic. Comments explain *why*, not *what*.
- No comparison percentages, no charts (declined in the spec).
- No changes to `app/Models/**`, no migrations, no changes to `app/Services/**`.
- Filament widgets render through Livewire — assert with `Livewire::test(...)`, never `get()`.
- `ShipmentStatus::AwaitingBatch` is defined but never *set* as a status. "Awaiting a batch" is therefore queried as `batch_id IS NULL` with packages, not by that status.
- Do NOT `git commit` until `php artisan test` passes in full.

---

### Task 1: Operational counts, and remove the default widgets

**Files:**
- Create: `app/Filament/Widgets/OperationalStatsWidget.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/DashboardWidgetsTest.php`

**Interfaces:**
- Produces: `App\Filament\Widgets\OperationalStatsWidget` — a `StatsOverviewWidget` with two stats (awaiting a batch, ready for collection), `canView()` true for any authenticated user.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DashboardWidgetsTest.php`:

```php
<?php

use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\OperationalStatsWidget;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The dashboard's operational counts.
 *
 * These are the two numbers that tell an operator to act — cargo to price
 * onto a batch, and cargo to release — and they show for everyone, money-
 * holder or not.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $this->damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);
});

function shipmentWith(array $attributes, bool $withPackage = true): Shipment
{
    $shipment = Shipment::create(array_merge([
        'customer_id' => test()->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => test()->damascus->id,
    ], $attributes));

    if ($withPackage) {
        $shipment->packages()->create(['weight_kg' => 1.0]);
    }

    return $shipment->fresh();
}

test('the operational counts are visible to a capability-less employee', function () {
    expect(OperationalStatsWidget::canView())->toBeTrue();
});

test('awaiting-a-batch counts shipments with packages and no batch', function () {
    // Counted: has packages, no batch.
    shipmentWith(['batch_id' => null]);
    shipmentWith(['batch_id' => null]);
    // Not counted: no packages yet (an empty draft).
    shipmentWith(['batch_id' => null], withPackage: false);

    Livewire::actingAs($this->clerk)
        ->test(OperationalStatsWidget::class)
        ->assertSee('بانتظار رحلة')
        ->assertSee('2');
});

test('ready-for-collection counts shipments in that status', function () {
    $ready = shipmentWith(['batch_id' => null]);
    $ready->forceFill(['status' => ShipmentStatus::ReadyForCollection])->save();

    // Not ready.
    shipmentWith(['batch_id' => null]);

    Livewire::actingAs($this->clerk)
        ->test(OperationalStatsWidget::class)
        ->assertSee('جاهزة للتسليم')
        ->assertSee('1');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter DashboardWidgetsTest`
Expected: FAIL — `Class "App\Filament\Widgets\OperationalStatsWidget" not found.`

- [ ] **Step 3: Write the widget**

Read `app/Filament/Widgets/BatchProfitabilityWidget.php` first for the `StatsOverviewWidget` + `Stat` + `canView` idiom.

Create `app/Filament/Widgets/OperationalStatsWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * The two counts that ask an operator to do something: cargo received but not
 * yet on a batch (to price and load), and cargo that has all arrived (to
 * release). Shown to everyone — neither is money.
 */
class OperationalStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    protected function getStats(): array
    {
        // "Awaiting a batch" is has-packages-and-no-batch. The AwaitingBatch
        // enum case is defined but never assigned, so the batch_id is the
        // reliable signal, not the status.
        $awaitingBatch = Shipment::query()
            ->whereNull('batch_id')
            ->whereHas('packages')
            ->count();

        $readyForCollection = Shipment::query()
            ->where('status', ShipmentStatus::ReadyForCollection->value)
            ->count();

        return [
            Stat::make('بانتظار رحلة', (string) $awaitingBatch)
                ->description('شحنات وصلت طرودها وتنتظر الإسناد إلى رحلة'),
            Stat::make('جاهزة للتسليم', (string) $readyForCollection)
                ->description('شحنات وصلت كل طرودها وجاهزة لتسليم المستلم'),
        ];
    }
}
```

- [ ] **Step 4: Remove the two default widgets**

In `app/Providers/Filament/AdminPanelProvider.php`, the panel currently registers:

```php
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
```

Replace with an empty registration — the dashboard's own widgets come from `discoverWidgets`, and the account greeting and Filament advertisement are not wanted:

```php
            ->widgets([])
```

Remove the now-unused `use Filament\Widgets\AccountWidget;` and `use Filament\Widgets\FilamentInfoWidget;` imports.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter DashboardWidgetsTest`
Expected: PASS, 3 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Widgets/OperationalStatsWidget.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/DashboardWidgetsTest.php
git commit -m "feat: dashboard operational counts, and drop the default widgets

The first screen an operator opened each morning showed a Filament ad and an
account greeting. It now shows the two counts that ask for action: cargo
waiting to be priced onto a batch, and cargo ready to release. Both are shown
to everyone; neither is money."
```

---

### Task 2: The outstanding total, for a money-holder only

**Files:**
- Create: `app/Filament/Widgets/OutstandingWidget.php`
- Test: `tests/Feature/OutstandingWidgetTest.php`

**Interfaces:**
- Produces: `App\Filament\Widgets\OutstandingWidget` — a `StatsOverviewWidget` with one stat, `canView()` true only for a user holding `RecordPayments`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/OutstandingWidgetTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Widgets\OutstandingWidget;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CustomerStatementService;
use App\Services\PaymentService;
use Livewire\Livewire;

/**
 * The outstanding total is money, so it is gated on the payments capability
 * and must equal what the customer statements reconcile to — the dashboard
 * and the statements cannot be allowed to disagree.
 */
beforeEach(function () {
    $this->warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->warehouse->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    // A shipment charged $100, of which $30 has been paid → $70 outstanding.
    $shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $shipment->forceFill(['final_charge_cents' => 10000])->save();

    app(PaymentService::class)->recordPayment([
        'customer_id' => $this->customer->id,
        'amount_cents' => 3000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ], $shipment);
});

test('the outstanding widget is hidden from an employee without the payments capability', function () {
    $this->actingAs($this->clerk);
    expect(OutstandingWidget::canView())->toBeFalse();
});

test('it is shown to an administrator and to an employee holding the capability', function () {
    $this->actingAs($this->admin);
    expect(OutstandingWidget::canView())->toBeTrue();

    $this->clerk->grantCapability(Capability::RecordPayments);
    $this->actingAs($this->clerk->fresh());
    expect(OutstandingWidget::canView())->toBeTrue();
});

test('the figure is the outstanding total and reconciles with the statement', function () {
    Livewire::actingAs($this->admin)
        ->test(OutstandingWidget::class)
        ->assertSee('المستحق')
        ->assertSee('$70.00');

    // The dashboard total must equal the sum the statements reconcile to.
    $statementOutstanding = app(CustomerStatementService::class)
        ->getSummary($this->customer)['outstanding_cents'];

    expect($statementOutstanding)->toBe(7000);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter OutstandingWidgetTest`
Expected: FAIL — `Class "App\Filament\Widgets\OutstandingWidget" not found.`

- [ ] **Step 3: Write the widget**

Create `app/Filament/Widgets/OutstandingWidget.php`. The outstanding total is total charged minus total allocated — the same formula `CustomerStatementService::getSummary()` uses per customer, summed across all customers, so the dashboard and the statements agree by construction.

```php
<?php

namespace App\Filament\Widgets;

use App\Enums\Capability;
use App\Models\PaymentAllocation;
use App\Models\Shipment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What there is to collect, company-wide.
 *
 * Outstanding is total charged minus total allocated — every allocation
 * belongs to some customer's payment, so summing across all customers is the
 * same figure the per-customer statement reconciles to. Money, so gated on the
 * payments capability; an administrator holds it implicitly.
 */
class OutstandingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::RecordPayments);
    }

    protected function getStats(): array
    {
        $charged = (int) Shipment::query()->sum('final_charge_cents');
        $allocated = (int) PaymentAllocation::query()->sum('amount_cents');

        $outstanding = $charged - $allocated;

        return [
            Stat::make('المستحق', $this->formatUsd($outstanding))
                ->description('إجمالي المبالغ المتبقية على العملاء'),
        ];
    }

    private function formatUsd(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $absolute = abs($cents);

        return sprintf('%s$%d.%02d', $sign, intdiv($absolute, 100), $absolute % 100);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter OutstandingWidgetTest`
Expected: PASS, 3 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Widgets/OutstandingWidget.php tests/Feature/OutstandingWidgetTest.php
git commit -m "feat: the outstanding total, for a money-holder only

What there is to collect, company-wide — total charged minus total allocated,
the same figure the statements reconcile to. Gated on the payments capability;
an administrator holds it implicitly."
```

---

### Task 3: Recent shipments, with no money

**Files:**
- Create: `app/Filament/Widgets/RecentShipmentsWidget.php`
- Test: `tests/Feature/RecentShipmentsWidgetTest.php`

**Interfaces:**
- Produces: `App\Filament\Widgets\RecentShipmentsWidget` — a `TableWidget` listing the latest shipments with no money column, `canView()` true for any authenticated user.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/RecentShipmentsWidgetTest.php`:

```php
<?php

use App\Enums\UserRole;
use App\Filament\Widgets\RecentShipmentsWidget;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Recent shipments, shown to everyone and carrying no money — an at-a-glance
 * of today's activity without opening the full list.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    $this->shipment = Shipment::create([
        'customer_id' => $this->customer->id,
        'recipient_name' => 'سامي',
        'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $damascus->id,
    ]);
    // Give it a charge, so a money column would have something to leak.
    $this->shipment->forceFill(['final_charge_cents' => 9999])->save();
});

test('recent shipments are visible to a capability-less employee', function () {
    expect(RecentShipmentsWidget::canView())->toBeTrue();
});

test('the table shows the shipment and its customer, but no money', function () {
    Livewire::actingAs($this->clerk)
        ->test(RecentShipmentsWidget::class)
        ->assertCanSeeTableRecords([$this->shipment])
        ->assertSee($this->shipment->reference)
        ->assertSee('أحمد')
        // The $99.99 charge must not appear — no money column.
        ->assertDontSee('99.99');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter RecentShipmentsWidgetTest`
Expected: FAIL — `Class "App\Filament\Widgets\RecentShipmentsWidget" not found.`

- [ ] **Step 3: Write the widget**

Read `vendor/filament/widgets/src/TableWidget.php` and `app/Filament/Resources/Shipments/Tables/ShipmentsTable.php` for the `TableWidget` + column idiom. Create `app/Filament/Widgets/RecentShipmentsWidget.php`:

```php
<?php

namespace App\Filament\Widgets;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Today's activity at a glance, without opening the full list. It carries no
 * money column — the recent-shipments view is operational, and money is gated
 * elsewhere; a charge does not belong on a screen everyone sees.
 */
class RecentShipmentsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'أحدث الشحنات';

    public static function canView(): bool
    {
        return auth()->user() instanceof User;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Shipment::query()->latest()->limit(8)
            )
            ->columns([
                TextColumn::make('reference')->label('رقم الشحنة'),
                TextColumn::make('customer.name')->label('العميل'),
                TextColumn::make('recipient_name')->label('المستلم'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (ShipmentStatus $state): string => $state->label()),
            ])
            ->paginated(false);
    }
}
```

If `TableWidget::table()` or `->query(...)` differs in this Filament version, read `vendor/filament/widgets/src/TableWidget.php` for the real signature (it may require `getTableQuery()` instead of `->query()`), use it, and note the substitution in your report. The table must show the columns above and must NOT show any charge/total — the test asserts the `99.99` charge is absent.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter RecentShipmentsWidgetTest`
Expected: PASS, 2 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Widgets/RecentShipmentsWidget.php tests/Feature/RecentShipmentsWidgetTest.php
git commit -m "feat: a recent-shipments table on the dashboard, with no money

Today's activity at a glance without opening the full list. No charge column —
it is shown to everyone, and money is gated elsewhere."
```

---

### Task 4: Customers in debt, for a money-holder only

**Files:**
- Create: `app/Filament/Widgets/CustomersInDebtWidget.php`
- Test: `tests/Feature/CustomersInDebtWidgetTest.php`

**Interfaces:**
- Produces: `App\Filament\Widgets\CustomersInDebtWidget` — a `TableWidget` listing customers with a positive outstanding balance, largest first, `canView()` true only for a user holding `RecordPayments`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CustomersInDebtWidgetTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Widgets\CustomersInDebtWidget;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PaymentService;
use Livewire\Livewire;

/**
 * The follow-up-for-payment list, gated on the payments capability. A customer
 * appears only while they owe something, largest first.
 */
beforeEach(function () {
    $this->warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $this->warehouse->id,
    ]);

    // In debt: charged $50, paid nothing.
    $this->inDebt = Customer::create(['name' => 'مدين', 'phone' => '+971500000001']);
    $s = Shipment::create([
        'customer_id' => $this->inDebt->id,
        'recipient_name' => 'س', 'recipient_phone' => '+9613000001',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $s->forceFill(['final_charge_cents' => 5000])->save();

    // Settled: charged $20, paid $20.
    $this->settled = Customer::create(['name' => 'مسدّد', 'phone' => '+971500000002']);
    $s2 = Shipment::create([
        'customer_id' => $this->settled->id,
        'recipient_name' => 'ص', 'recipient_phone' => '+9613000002',
        'destination_warehouse_id' => $damascus->id,
    ]);
    $s2->forceFill(['final_charge_cents' => 2000])->save();
    app(PaymentService::class)->recordPayment([
        'customer_id' => $this->settled->id,
        'amount_cents' => 2000,
        'method' => Payment::METHOD_CASH,
        'collected_at' => now(),
        'collected_by' => $this->admin->id,
        'warehouse_id' => $this->warehouse->id,
    ], $s2);
});

test('the list is hidden from an employee without the payments capability', function () {
    $this->actingAs($this->clerk);
    expect(CustomersInDebtWidget::canView())->toBeFalse();
});

test('it shows a customer who owes, and its figure, and hides one who is settled', function () {
    Livewire::actingAs($this->admin)
        ->test(CustomersInDebtWidget::class)
        ->assertSee('مدين')
        ->assertSee('$50.00')
        ->assertDontSee('مسدّد');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter CustomersInDebtWidgetTest`
Expected: FAIL — `Class "App\Filament\Widgets\CustomersInDebtWidget" not found.`

- [ ] **Step 3: Write the widget**

Create `app/Filament/Widgets/CustomersInDebtWidget.php`. Outstanding per customer is charged minus allocated, computed as a select expression so the table can order and filter on it. The table and column names are verified: `shipments.customer_id`, `shipments.final_charge_cents`, `payment_allocations.payment_id`, `payment_allocations.amount_cents`, `payments.id`, `payments.customer_id`.

```php
<?php

namespace App\Filament\Widgets;

use App\Enums\Capability;
use App\Models\Customer;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * The follow-up-for-payment list: customers still owing, largest first.
 *
 * Outstanding is charged minus allocated, computed here as a select expression
 * so the table can order and filter by it — the same formula the statement
 * reconciles to. Money, so gated on the payments capability.
 */
class CustomersInDebtWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'عملاء عليهم رصيد';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCapability(Capability::RecordPayments);
    }

    public function table(Table $table): Table
    {
        $charged = '(SELECT COALESCE(SUM(final_charge_cents), 0) FROM shipments WHERE shipments.customer_id = customers.id)';
        $allocated = '(SELECT COALESCE(SUM(pa.amount_cents), 0) FROM payment_allocations pa '
            .'INNER JOIN payments p ON p.id = pa.payment_id WHERE p.customer_id = customers.id)';

        return $table
            ->query(
                Customer::query()
                    ->select('customers.*')
                    ->selectRaw("($charged - $allocated) as outstanding_cents")
                    // whereRaw, not havingRaw: SQLite rejects HAVING without a
                    // GROUP BY, and the outstanding expression is a correlated
                    // subquery that belongs in WHERE. Verified against SQLite.
                    ->whereRaw("($charged - $allocated) > 0")
                    ->orderByRaw("($charged - $allocated) DESC")
            )
            ->columns([
                TextColumn::make('name')->label('العميل'),
                TextColumn::make('phone')->label('رقم الهاتف'),
                TextColumn::make('outstanding_cents')
                    ->label('المتبقي')
                    ->formatStateUsing(fn ($state): string => sprintf('$%d.%02d', intdiv((int) $state, 100), (int) $state % 100)),
            ])
            ->paginated(false);
    }
}
```

`TableWidget` uses the `InteractsWithTable` trait, whose configuration hook is `table(Table $table): Table` (confirmed) — the same one Task 3 uses. The `->query(...)` and `whereRaw`/`orderByRaw` form above was verified to build and execute on SQLite. The raw expression is repeated in the `select`, `where` and `order` rather than referencing the `outstanding_cents` alias, because SQLite does not allow an alias in `WHERE`.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter CustomersInDebtWidgetTest`
Expected: PASS, 2 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Widgets/CustomersInDebtWidget.php tests/Feature/CustomersInDebtWidgetTest.php
git commit -m "feat: customers-in-debt list, for a money-holder only

The follow-up-for-payment list — customers still owing, largest first,
computed as charged minus allocated, the figure the statement reconciles to.
Gated on the payments capability."
```

---

### Task 5: Update the handoff state

**Files:**
- Modify: `docs/current-state.md`

- [ ] **Step 1: Update the state file**

In the "Built and tested" table, add:

```markdown
| Dashboard — operational counts for all, money for a money-holder | done |
```

Update the header's test and assertion counts to the figures `php artisan test` prints. Remove the earlier note (if present) describing the dashboard as showing a Filament advertisement.

- [ ] **Step 2: Verify the counts are real**

Run: `php artisan test`
Copy the reported `tests` and `assertions` figures into the header. Do not estimate them.

- [ ] **Step 3: Commit**

```bash
git add docs/current-state.md
git commit -m "docs: record the adaptive dashboard in the handoff state"
```
