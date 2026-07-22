# Visibility Gating and a Shared Locked-Page Mechanism — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Gate every money screen by the capability that governs its content, showing whole money destinations as locked rather than hidden, and hiding a money section inside an operational page.

**Architecture:** A small reusable trait (`LocksWhenUnauthorized`) provides one method that returns either `null` (allowed — render the real content) or a locked-notice schema component (blocked). A page calls it at the top of its `content()`/`infolist()` and short-circuits to the notice. The batch report and the payments list use it; the customer rates section is simply hidden by an existing capability check. No model, service, or migration change.

**Tech Stack:** Laravel 13.20, Filament 5.7, Pest 4.7, SQLite (`:memory:` for tests), Arabic RTL.

## Global Constraints

- Authorization is enforced in the backend — the locked notice is rendered server-side, so a hand-typed URL reaches it too. Never rely on hiding a nav entry alone.
- An administrator implicitly holds every capability (`User::hasCapability` returns true for administrators). Never lock a page against an administrator.
- All user-facing strings are Arabic. Comments explain *why*, not *what*.
- Money is integer cents in storage; dollars on screen; conversion at the display boundary only.
- No changes to `app/Models/**`, no migrations, no changes to `app/Services/**`.
- Filament pages render through Livewire — assert with `Livewire::test(...)`, never `get()`.
- The locked notice must carry a *reason* parameter, because piece 3 (per-employee locking) will reuse this mechanism with a different reason ("locked by the administration") — do not hard-code the capability wording as the only possible reason.
- Do NOT `git commit` until `php artisan test` passes in full.

---

### Task 1: The shared locked-page mechanism

**Files:**
- Create: `app/Filament/Concerns/LocksWhenUnauthorized.php`
- Test: `tests/Feature/LockedPageTest.php`

**Interfaces:**
- Produces: trait `App\Filament\Concerns\LocksWhenUnauthorized` with:
  - `protected function lockNotice(string $reason): Section` — a Filament schema `Section` rendering the lock icon, a heading «هذه الصفحة مقفلة», and `$reason` as body text.
  - `protected function capabilityLockReason(\App\Enums\Capability $capability): string` — the standard reason wording for a missing capability, e.g. «تحتاج صلاحية "…" لعرض هذه الصفحة.» using `Capability::label()`.

The trait holds no capability check itself — the calling page decides *whether* to lock and supplies the reason. This keeps it reusable by piece 3, whose lock condition is per-employee, not per-capability.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LockedPageTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Filament\Concerns\LocksWhenUnauthorized;
use Filament\Schemas\Components\Section;

/**
 * The shared locked-page mechanism.
 *
 * One presentation is met by an employee who reaches a page they may not see,
 * whether the reason is a missing capability (this piece) or an administrator's
 * lock (piece 3). The reason is a parameter precisely so both can use it.
 */
test('the lock notice carries the reason it was given', function () {
    $subject = new class
    {
        use LocksWhenUnauthorized;

        public function build(string $reason): Section
        {
            return $this->lockNotice($reason);
        }
    };

    $notice = $subject->build('سبب مخصّص للاختبار');

    expect($notice)->toBeInstanceOf(Section::class);
    // The reason text is present in the built component tree.
    expect(json_encode($notice->toArray(), JSON_UNESCAPED_UNICODE))
        ->toContain('سبب مخصّص للاختبار')
        ->toContain('هذه الصفحة مقفلة');
});

test('the capability reason names the capability in Arabic', function () {
    $subject = new class
    {
        use LocksWhenUnauthorized;

        public function reason(Capability $c): string
        {
            return $this->capabilityLockReason($c);
        }
    };

    $reason = $subject->reason(Capability::RecordPayments);

    // Capability::RecordPayments->label() is 'تسجيل الدفعات'.
    expect($reason)->toContain(Capability::RecordPayments->label());
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter LockedPageTest`
Expected: FAIL — `Trait "App\Filament\Concerns\LocksWhenUnauthorized" not found.`

- [ ] **Step 3: Write the trait**

First read `app/Filament/Pages/ScanPackages.php` for this project's `Section` / `TextEntry` import paths and idiom, and `app/Enums/Capability.php` for `label()`.

Create `app/Filament/Concerns/LocksWhenUnauthorized.php`:

```php
<?php

namespace App\Filament\Concerns;

use App\Enums\Capability;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

/**
 * Renders a page as locked instead of showing its content.
 *
 * The trait decides nothing about *who* may see a page — the calling page
 * supplies both the condition and the reason. That is deliberate: a capability
 * gate (this piece) and a per-employee administrative lock (piece 3) share one
 * presentation but decide access completely differently.
 */
trait LocksWhenUnauthorized
{
    protected function lockNotice(string $reason): Section
    {
        return Section::make('هذه الصفحة مقفلة')
            ->icon('heroicon-o-lock-closed')
            ->schema([
                TextEntry::make('lock_reason')
                    ->hiddenLabel()
                    ->state($reason),
            ]);
    }

    protected function capabilityLockReason(Capability $capability): string
    {
        return "تحتاج صلاحية \"{$capability->label()}\" لعرض هذه الصفحة.";
    }
}
```

`Section::make(...)->icon(...)` (via the `HasIcon` trait) and `TextEntry::make(...)->hiddenLabel()->state(...)` are the confirmed APIs in this install — `ScanPackages` uses the same `TextEntry->state()` idiom. The notice must contain the heading text and the reason text in its rendered output; the test asserts both.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter LockedPageTest`
Expected: PASS, 2 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Concerns/LocksWhenUnauthorized.php tests/Feature/LockedPageTest.php
git commit -m "feat: a shared locked-page notice

One presentation an employee meets on a page they may not see. The trait
decides nothing about access — the page supplies the condition and the reason
— so a capability gate and a per-employee administrative lock can share the
same notice while gating access completely differently."
```

---

### Task 2: The batch report is locked without the pricing capability

**Files:**
- Modify: `app/Filament/Resources/Batches/Pages/BatchReport.php`
- Test: `tests/Feature/BatchReportLockTest.php`

**Interfaces:**
- Consumes: `LocksWhenUnauthorized::lockNotice()`, `LocksWhenUnauthorized::capabilityLockReason()`

The batch report shows revenue, cost and profit and has no guard today — the clearest open leak.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BatchReportLockTest.php`:

```php
<?php

use App\Enums\BatchStatus;
use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Batches\Pages\BatchReport;
use App\Models\Batch;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The batch report exposes profit, so it is gated on the pricing capability.
 * It had no guard at all — the figures were readable by any employee, while
 * the identical numbers were correctly hidden on the profitability widget.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->batch = Batch::create([
        'reference' => 'BCH-2026-0001',
        'route_id' => $route->id,
        'status' => BatchStatus::Dispatched,
        'cost_per_kg_cents' => 150,
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

test('an employee without the pricing capability sees the report locked, with no figures', function () {
    expect($this->clerk->hasCapability(Capability::PriceShipments))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(BatchReport::class, ['record' => $this->batch->getRouteKey()])
        ->assertSee('هذه الصفحة مقفلة')
        // None of the report's own section headings render.
        ->assertDontSee('إحصائيات تشغيلية')
        ->assertDontSee('الربح');
});

test('an administrator sees the real report', function () {
    Livewire::actingAs($this->admin)
        ->test(BatchReport::class, ['record' => $this->batch->getRouteKey()])
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('الربح');
});

test('an employee who holds the pricing capability sees the real report', function () {
    $this->clerk->grantCapability(Capability::PriceShipments);

    Livewire::actingAs($this->clerk->fresh())
        ->test(BatchReport::class, ['record' => $this->batch->getRouteKey()])
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('الربح');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter BatchReportLockTest`
Expected: FAIL — the clerk currently sees the full report, so `assertSee('هذه الصفحة مقفلة')` fails.

- [ ] **Step 3: Add the lock to the report**

In `app/Filament/Resources/Batches/Pages/BatchReport.php`:

1. Add `use App\Enums\Capability;` and `use App\Filament\Concerns\LocksWhenUnauthorized;` and `use App\Models\User;` to the imports.
2. Add `use LocksWhenUnauthorized;` inside the class body (a trait use, at the top of the class).
3. At the very start of `infolist(Schema $schema)`, before it reads the report, short-circuit to the notice when the viewer lacks the capability:

```php
    public function infolist(Schema $schema): Schema
    {
        $user = auth()->user();

        // The report carries cost and profit. An employee without the pricing
        // capability meets the lock instead — the same numbers are gated on
        // the profitability widget, and this page had simply been missed.
        if (! $user instanceof User || ! $user->hasCapability(Capability::PriceShipments)) {
            return $schema->components([
                $this->lockNotice($this->capabilityLockReason(Capability::PriceShipments)),
            ]);
        }

        $batch = $this->record;
        // ... existing body unchanged from here ...
```

Leave the rest of the method exactly as it is.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter BatchReportLockTest`
Expected: PASS, 3 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/Batches/Pages/BatchReport.php tests/Feature/BatchReportLockTest.php
git commit -m "fix: lock the batch report without the pricing capability

It showed revenue, cost and profit to every employee and had no guard at all,
while the identical figures were correctly hidden on the profitability widget.
Now it renders locked for anyone without the pricing capability."
```

---

### Task 3: Payments appear locked, not absent, without the capability

**Files:**
- Modify: `app/Filament/Resources/Payments/PaymentResource.php`
- Modify: `app/Filament/Resources/Payments/Pages/ListPayments.php`
- Test: `tests/Feature/PaymentsLockTest.php`

**Interfaces:**
- Consumes: `LocksWhenUnauthorized`

Payments are gated today by `canViewAny()` returning false — which hides the resource entirely and 403s a direct URL. The owner wants it present-but-locked, so an employee knows it exists and is not theirs.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PaymentsLockTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * Payments are a whole money destination, so an employee without the payments
 * capability meets it locked rather than finding it missing — they should know
 * it exists and is not theirs, not wonder why a screen vanished.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

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

test('payments stay in the navigation for an employee without the capability', function () {
    // Present, not absent: shouldRegisterNavigation no longer depends on the
    // capability, and canViewAny no longer 403s the page.
    expect(PaymentResource::shouldRegisterNavigation())->toBeTrue()
        ->and(PaymentResource::canViewAny())->toBeTrue();
});

test('opening payments without the capability shows the lock, not the table', function () {
    Livewire::actingAs($this->clerk)
        ->test(ListPayments::class)
        ->assertSee('هذه الصفحة مقفلة')
        ->assertDontSee('رقم الإيصال');
});

test('an employee who holds the capability sees the real payments list', function () {
    $this->clerk->grantCapability(Capability::RecordPayments);

    Livewire::actingAs($this->clerk->fresh())
        ->test(ListPayments::class)
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('رقم الإيصال');
});

test('an administrator sees the real payments list', function () {
    Livewire::actingAs($this->admin)
        ->test(ListPayments::class)
        ->assertDontSee('هذه الصفحة مقفلة')
        ->assertSee('رقم الإيصال');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter PaymentsLockTest`
Expected: FAIL — `canViewAny()` returns false for the clerk, so the first test fails and the clerk cannot reach `ListPayments` at all.

- [ ] **Step 3: Open the resource, gate the create action**

In `app/Filament/Resources/Payments/PaymentResource.php`, change `canViewAny()` so the resource is reachable by any authenticated user (the list page itself will lock its content), while `canCreate()` stays gated on the capability:

```php
    public static function canViewAny(): bool
    {
        // Reachable by anyone, so the screen appears in the navigation and can
        // render itself as locked. Whether the real list shows is decided in
        // ListPayments; recording a payment stays gated by canCreate().
        return auth()->user() instanceof User;
    }
```

Add `use App\Models\User;` if it is not already imported. Leave `canCreate()` and `canEdit()` unchanged.

- [ ] **Step 4: Lock the list content**

In `app/Filament/Resources/Payments/Pages/ListPayments.php`, override `content()` to render the lock when the viewer lacks the capability, and otherwise fall through to the default list. Read `vendor/filament/filament/src/Resources/Pages/ListRecords.php` for the default `content()` body and reproduce it in the else branch, so nothing the base list renders is lost.

```php
<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\Capability;
use App\Filament\Concerns\LocksWhenUnauthorized;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Schema;

class ListPayments extends ListRecords
{
    use LocksWhenUnauthorized;

    protected static string $resource = PaymentResource::class;

    public function content(Schema $schema): Schema
    {
        $user = auth()->user();

        if (! $user instanceof User || ! $user->hasCapability(Capability::RecordPayments)) {
            return $schema->components([
                $this->lockNotice($this->capabilityLockReason(Capability::RecordPayments)),
            ]);
        }

        return parent::content($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
```

If `ListRecords` has no `content(Schema $schema)` to call via `parent::` in this version, read the class to find the method that composes the table (it may be `getTableContentComponent()` or the page renders the table through a view). Use the supported hook to render the default list in the else branch, and note the substitution in your report. Do not drop the real table to make the lock compile.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter PaymentsLockTest`
Expected: PASS, 4 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS. `AdministratorPaymentTest`, `PaymentTest` and any test that drives `ListPayments` as an administrator or capability-holder must stay green.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Payments/ tests/Feature/PaymentsLockTest.php
git commit -m "feat: payments appear locked, not absent, without the capability

Gating by canViewAny hid the screen entirely and 403'd a direct URL. The owner
wants an employee to see that payments exist and are not theirs, so the
resource is now reachable and the list renders locked without the capability.
Recording a payment stays gated by canCreate."
```

---

### Task 4: A customer's rates are hidden without the customers capability

**Files:**
- Modify: `app/Filament/Resources/Customers/Pages/EditCustomer.php`
- Test: `tests/Feature/CustomerRatesVisibilityTest.php`

**Interfaces:** none produced.

The rates section on the customer page shows the rate per kilogram to anyone who can open a customer. It is a small section inside an operational page, so it is hidden — not locked — for an employee without `ManageCustomers`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CustomerRatesVisibilityTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Models\Customer;
use App\Models\CustomerRate;
use App\Models\Route;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * A customer's rate per kilogram is money (D-024). It renders on the customer
 * page, which an employee needs for receiving cargo — so the rate section is
 * hidden for anyone without the customers capability, while the rest of the
 * page stays usable.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
    $damascus = Warehouse::create(['name' => 'Damascus', 'location' => 'Syria']);

    $route = Route::create([
        'name' => 'Dubai → Syria',
        'origin_warehouse_id' => $dubai->id,
        'destination_warehouse_id' => $damascus->id,
    ]);

    $this->customer = Customer::create(['name' => 'أحمد', 'phone' => '+971500000001']);

    CustomerRate::create([
        'customer_id' => $this->customer->id,
        'route_id' => $route->id,
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

test('the rate figure is not shown to an employee without the customers capability', function () {
    expect($this->clerk->hasCapability(Capability::ManageCustomers))->toBeFalse();

    Livewire::actingAs($this->clerk)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        // The rates section and its heading are absent...
        ->assertDontSee('الأسعار المتفق عليها')
        ->assertDontSee('3.00')
        // ...but the customer page still renders.
        ->assertSee('أحمد');
});

test('an administrator sees the rates', function () {
    Livewire::actingAs($this->admin)
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('الأسعار المتفق عليها')
        ->assertSee('3.00');
});

test('an employee who holds the customers capability sees the rates', function () {
    $this->clerk->grantCapability(Capability::ManageCustomers);

    Livewire::actingAs($this->clerk->fresh())
        ->test(EditCustomer::class, ['record' => $this->customer->getRouteKey()])
        ->assertSee('الأسعار المتفق عليها')
        ->assertSee('3.00');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter CustomerRatesVisibilityTest`
Expected: FAIL — the clerk currently sees the rates.

- [ ] **Step 3: Guard the rates section**

In `app/Filament/Resources/Customers/Pages/EditCustomer.php`, read the current `content()` and `ratesSection()`. Make the rates section contribute nothing when the viewer lacks `ManageCustomers`. Change `content()` so the spread only includes the section for an authorized viewer:

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
```

Change `ratesSection()` to return an empty array when the viewer may not see money:

```php
    private function ratesSection(): array
    {
        $user = auth()->user();

        // The rate per kilogram is money (D-024). An employee without the
        // customers capability still needs the rest of this page to receive
        // cargo, so the section is omitted rather than the page locked.
        if (! $user instanceof User || ! $user->hasCapability(Capability::ManageCustomers)) {
            return [];
        }

        // ... existing body that builds the rates section, unchanged ...
    }
```

Add `use App\Enums\Capability;` and `use App\Models\User;` to the imports if not present.

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter CustomerRatesVisibilityTest`
Expected: PASS, 3 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS. `CustomerRatesOnCustomerTest` from the previous round drives this page as an administrator and must stay green.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/Customers/Pages/EditCustomer.php tests/Feature/CustomerRatesVisibilityTest.php
git commit -m "fix: hide a customer's rates without the customers capability

The rate per kilogram is money (D-024) and was rendering to any employee who
could open a customer. It is a small section inside an operational page the
employee still needs, so it is omitted rather than the page locked."
```

---

### Task 5: Update the handoff state

**Files:**
- Modify: `docs/current-state.md`

- [ ] **Step 1: Update the state file**

In the "Built and tested" table, add:

```markdown
| Money screens gated by capability; shared locked-page notice | done |
```

In the "Decisions taken during the rebuild" table, add:

```markdown
| D-025 | Employees see every shipment; batches stay warehouse-scoped |
```

Update the header's test and assertion counts to the figures `php artisan test` prints.

Update the "per-screen-gating trap" note: the customer-rates and batch-report leaks it named are now closed; `ShipmentResource` staying unscoped is now a recorded decision (D-025), not a gap.

- [ ] **Step 2: Verify the counts are real**

Run: `php artisan test`
Copy the reported `tests` and `assertions` figures into the header. Do not estimate them.

- [ ] **Step 3: Commit**

```bash
git add docs/current-state.md
git commit -m "docs: record capability gating and D-025 in the handoff state"
```
