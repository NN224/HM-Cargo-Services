# Per-Employee Page Locking — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an administrator lock a specific page for a specific employee, enforced centrally, with the locked page reusing the shared locked-page notice.

**Architecture:** A `LockablePage` enum is the single registry of lockable destinations and their route keys. A `locked_pages` JSON column on the user stores locks, mirroring the existing `capabilities` column. The employee form gains lock toggles. One middleware on the panel resolves the current route to a key and redirects a locked employee to a dedicated locked page. No change to any capability.

**Tech Stack:** Laravel 13.20, Filament 5.7, Pest 4.7, SQLite (`:memory:` for tests), Arabic RTL.

## Global Constraints

- Enforcement is central and server-side: one middleware, driven by the `LockablePage` registry. No per-page lock check — that is the failure mode this replaces.
- An administrator is never locked. `User::isAdministrator()` short-circuits every lock check.
- A lock removes page access only. It never removes a capability and never blocks an action reachable from an unlocked page.
- The dashboard is not lockable and must never be in the registry.
- All user-facing strings are Arabic. Comments explain *why*, not *what*.
- Stored locks mirror `capabilities`: a JSON array of strings, unrecognised values ignored, so a hand-edited row cannot invent a lock.
- No changes to `app/Services/**`. Model, migration, and Filament changes only where a task says so.
- Filament pages render through Livewire — assert with `Livewire::test(...)`; enforcement is tested with `get(...)` on the real route, since the middleware acts on the HTTP request.
- Route names in this panel are `filament.admin.resources.{key}.{action}` and `filament.admin.pages.{key}` — verified. The key is the segment after `resources.` or `pages.`.
- Do NOT `git commit` until `php artisan test` passes in full.

---

### Task 1: The page registry

**Files:**
- Create: `app/Enums/LockablePage.php`
- Test: `tests/Feature/LockablePageTest.php`

**Interfaces:**
- Produces: enum `App\Enums\LockablePage` (backed by string keys), with:
  - cases for every lockable destination
  - `label(): string` — the Arabic screen name
  - `static options(): array<string,string>` — value => label, for the form
  - `static fromRouteName(?string $routeName): ?self` — the key a panel route belongs to, or null (dashboard, login, the locked page, anything unlisted)

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/LockablePageTest.php`:

```php
<?php

use App\Enums\LockablePage;

/**
 * The single registry of lockable destinations.
 *
 * Everything — the form toggles, the stored locks, the middleware — reads keys
 * from here, so they cannot drift. The earlier per-screen gating missed screens
 * precisely because there was no such single list.
 */
test('the dashboard is never lockable', function () {
    $keys = array_map(fn (LockablePage $p): string => $p->value, LockablePage::cases());

    expect($keys)->not->toContain('dashboard');
});

test('every case has an Arabic label', function () {
    foreach (LockablePage::cases() as $page) {
        expect($page->label())->toBeString()->not->toBe('');
    }
});

test('a panel route name resolves to its page key', function () {
    expect(LockablePage::fromRouteName('filament.admin.resources.shipments.index'))
        ->toBe(LockablePage::Shipments)
        ->and(LockablePage::fromRouteName('filament.admin.resources.payments.create'))
        ->toBe(LockablePage::Payments)
        ->and(LockablePage::fromRouteName('filament.admin.pages.scan-packages'))
        ->toBe(LockablePage::ScanPackages);
});

test('the dashboard, login, and unknown routes resolve to null', function () {
    expect(LockablePage::fromRouteName('filament.admin.pages.dashboard'))->toBeNull()
        ->and(LockablePage::fromRouteName('filament.admin.auth.login'))->toBeNull()
        ->and(LockablePage::fromRouteName(null))->toBeNull()
        ->and(LockablePage::fromRouteName('some.other.route'))->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter LockablePageTest`
Expected: FAIL — `Enum "App\Enums\LockablePage" not found.`

- [ ] **Step 3: Write the enum**

Read `app/Enums/Capability.php` for this project's enum idiom (`label()`, `options()`). The case values are the route keys verified from `php artisan route:list`: `shipments`, `batches`, `customers`, `customer-rates`, `payments`, `routes`, `warehouses`, `users`, `receive-into-batch`, `scan-packages`. The dashboard is deliberately absent.

Create `app/Enums/LockablePage.php`:

```php
<?php

namespace App\Enums;

/**
 * Every page an administrator may lock for an employee, keyed by the segment
 * that identifies it in a panel route name.
 *
 * This is the single source the lock toggles, the stored locks, and the
 * enforcement middleware all read — one list so the three cannot disagree. The
 * dashboard is intentionally not here: a home screen is not something to lock
 * someone out of, and it already shows an employee no money.
 */
enum LockablePage: string
{
    case Shipments = 'shipments';
    case Batches = 'batches';
    case Customers = 'customers';
    case CustomerRates = 'customer-rates';
    case Payments = 'payments';
    case Routes = 'routes';
    case Warehouses = 'warehouses';
    case Users = 'users';
    case ReceiveIntoBatch = 'receive-into-batch';
    case ScanPackages = 'scan-packages';

    public function label(): string
    {
        return match ($this) {
            self::Shipments => 'الشحنات',
            self::Batches => 'الرحلات',
            self::Customers => 'العملاء',
            self::CustomerRates => 'أسعار العملاء',
            self::Payments => 'المدفوعات',
            self::Routes => 'المسارات',
            self::Warehouses => 'المستودعات',
            self::Users => 'الموظفون',
            self::ReceiveIntoBatch => 'استلام بضاعة',
            self::ScanPackages => 'مسح الطرود',
        };
    }

    /** @return array<string, string> value => Arabic label, for the form */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /**
     * The lockable page a panel route belongs to, or null.
     *
     * Null for the dashboard, login, the locked page, and anything not in the
     * registry — the middleware treats null as "not lockable, let it through".
     */
    public static function fromRouteName(?string $routeName): ?self
    {
        if ($routeName === null) {
            return null;
        }

        // filament.admin.resources.{key}.{action} or filament.admin.pages.{key}
        if (! preg_match('/^filament\.admin\.(?:resources|pages)\.([a-z0-9-]+)/', $routeName, $matches)) {
            return null;
        }

        return self::tryFrom($matches[1]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter LockablePageTest`
Expected: PASS, 4 tests.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Enums/LockablePage.php tests/Feature/LockablePageTest.php
git commit -m "feat: a registry of lockable pages

One list of every page an administrator may lock, keyed by the route segment
that identifies it — the single source the toggles, the stored locks, and the
enforcement will all read, so they cannot drift. The dashboard is not in it."
```

---

### Task 2: Storing locks on the user

**Files:**
- Create: `database/migrations/2026_07_23_000001_add_locked_pages_to_users_table.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/UserLockedPagesTest.php`

**Interfaces:**
- Consumes: `App\Enums\LockablePage`
- Produces on `User`:
  - `lockedPageList(): array<int,string>` — stored lock keys, unrecognised ignored
  - `lockPage(LockablePage $page): void`
  - `unlockPage(LockablePage $page): void`
  - `isPageLocked(LockablePage $page): bool` — false for an administrator, always

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/UserLockedPagesTest.php`:

```php
<?php

use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Locks stored on the user, mirroring how capabilities are stored: a list of
 * string keys, unrecognised values ignored, and never applying to an admin.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

test('a page can be locked and unlocked for an employee', function () {
    expect($this->clerk->isPageLocked(LockablePage::Payments))->toBeFalse();

    $this->clerk->lockPage(LockablePage::Payments);
    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeTrue();

    $this->clerk->unlockPage(LockablePage::Payments);
    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeFalse();
});

test('an administrator is never locked, whatever the column holds', function () {
    $this->admin->forceFill(['locked_pages' => [LockablePage::Payments->value]])->save();

    expect($this->admin->fresh()->isPageLocked(LockablePage::Payments))->toBeFalse();
});

test('an unrecognised stored key is ignored', function () {
    $this->clerk->forceFill(['locked_pages' => ['payments', 'not-a-real-page']])->save();

    expect($this->clerk->fresh()->lockedPageList())->toBe(['payments']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter UserLockedPagesTest`
Expected: FAIL — no `locked_pages` column / no `isPageLocked` method.

- [ ] **Step 3: Write the migration**

Create `database/migrations/2026_07_23_000001_add_locked_pages_to_users_table.php`, mirroring the `capabilities` migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-employee page locks (D-026). A closed, per-user deny-list of
            // page keys, stored as JSON for the same reason capabilities are:
            // the list is fixed in code, never user-defined, so a pivot table
            // would invite the permission matrix this deliberately is not.
            $table->json('locked_pages')->nullable()->after('capabilities');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locked_pages');
        });
    }
};
```

- [ ] **Step 4: Add the column to fillable and casts, and the methods**

In `app/Models/User.php`:

1. Add `'locked_pages'` to the `#[Fillable([...])]` attribute list.
2. In `casts()`, add `'locked_pages' => 'array',` beside `'capabilities' => 'array'`.
3. Add `use App\Enums\LockablePage;` to the imports.
4. Add these methods next to the capability methods, mirroring them:

```php
    /**
     * The pages currently locked for this employee, unrecognised keys dropped.
     *
     * Mirrors capabilityList(): a hand-edited row cannot lock a page that does
     * not exist in the registry.
     *
     * @return array<int, string>
     */
    public function lockedPageList(): array
    {
        $valid = array_column(LockablePage::cases(), 'value');

        return array_values(array_intersect((array) ($this->locked_pages ?? []), $valid));
    }

    /**
     * Whether this page is locked for this user.
     *
     * An administrator is never locked — the whole feature is about narrowing
     * an employee, and an administrator holds everything (D-026).
     */
    public function isPageLocked(LockablePage $page): bool
    {
        if ($this->isAdministrator()) {
            return false;
        }

        return in_array($page->value, $this->lockedPageList(), true);
    }

    public function lockPage(LockablePage $page): void
    {
        $this->forceFill([
            'locked_pages' => array_values(array_unique(
                [...$this->lockedPageList(), $page->value]
            )),
        ])->save();
    }

    public function unlockPage(LockablePage $page): void
    {
        $this->forceFill([
            'locked_pages' => array_values(
                array_diff($this->lockedPageList(), [$page->value])
            ),
        ])->save();
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter UserLockedPagesTest`
Expected: PASS, 3 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_07_23_000001_add_locked_pages_to_users_table.php app/Models/User.php tests/Feature/UserLockedPagesTest.php
git commit -m "feat: store per-employee page locks on the user

A locked_pages JSON column and lock/unlock/isPageLocked methods, mirroring the
capabilities column exactly — unrecognised keys ignored, and an administrator
never locked."
```

---

### Task 3: Central enforcement and the locked page

**Files:**
- Create: `app/Http/Middleware/EnforcePageLocks.php`
- Create: `app/Filament/Pages/PageLocked.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Test: `tests/Feature/PageLockEnforcementTest.php`

**Interfaces:**
- Consumes: `LockablePage::fromRouteName()`, `User::isPageLocked()`, `LocksWhenUnauthorized`
- Produces: middleware `App\Http\Middleware\EnforcePageLocks`; a Filament page `App\Filament\Pages\PageLocked` at slug `page-locked` that renders the locked notice.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PageLockEnforcementTest.php`:

```php
<?php

use App\Enums\Capability;
use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Shipments\ShipmentResource;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Central enforcement: one middleware, driven by the registry, redirects a
 * locked employee to the locked page — for every lockable route, with no
 * per-page code.
 */
beforeEach(function () {
    $dubai = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

    $this->clerk = User::create([
        'name' => 'موظف', 'email' => 'clerk@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
        'warehouse_id' => $dubai->id,
    ]);

    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'admin@hmcargo.test',
        'password' => 'secret', 'role' => UserRole::Administrator,
        'warehouse_id' => null,
    ]);
});

test('an employee with a page locked is redirected to the locked page', function () {
    $this->clerk->lockPage(LockablePage::Shipments);

    $this->actingAs($this->clerk)
        ->get(ShipmentResource::getUrl('index'))
        ->assertRedirect(\App\Filament\Pages\PageLocked::getUrl());
});

test('the locked page tells the employee an administrator locked it', function () {
    $this->actingAs($this->clerk)
        ->get(\App\Filament\Pages\PageLocked::getUrl())
        ->assertSee('من الإدارة');
});

test('the same employee reaches a page that is not locked', function () {
    $this->clerk->lockPage(LockablePage::Shipments);

    // Shipments is locked; customers is not.
    $this->actingAs($this->clerk)
        ->get(\App\Filament\Resources\Customers\CustomerResource::getUrl('index'))
        ->assertSuccessful();
});

test('a lock overrides a held capability but does not remove it', function () {
    $this->clerk->grantCapability(Capability::RecordPayments);
    $this->clerk->lockPage(LockablePage::Payments);

    // The payments page is blocked despite the capability...
    $this->actingAs($this->clerk->fresh())
        ->get(PaymentResource::getUrl('index'))
        ->assertRedirect(\App\Filament\Pages\PageLocked::getUrl());

    // ...but the capability itself is intact.
    expect($this->clerk->fresh()->hasCapability(Capability::RecordPayments))->toBeTrue();
});

test('an administrator is never redirected, even with a key toggled on', function () {
    $this->admin->forceFill(['locked_pages' => [LockablePage::Shipments->value]])->save();

    $this->actingAs($this->admin->fresh())
        ->get(ShipmentResource::getUrl('index'))
        ->assertSuccessful();
});

test('every lockable page is enforced by the middleware', function () {
    // Walk the whole registry: each key, when locked, must redirect. This is
    // what stops a destination being lockable in the UI yet unenforced.
    foreach (LockablePage::cases() as $page) {
        $user = User::create([
            'name' => 'x', 'email' => 'x'.$page->value.'@t.test',
            'password' => 'secret', 'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => Warehouse::first()->id,
            'locked_pages' => [$page->value],
        ]);

        // The two custom Pages vs. everything else (resources). Matched by
        // exact key, not a substring — 'batches' contains 'batch' but is a
        // resource, not a page.
        $isPage = in_array($page->value, ['receive-into-batch', 'scan-packages'], true);
        $routeName = $isPage
            ? 'filament.admin.pages.'.$page->value
            : 'filament.admin.resources.'.$page->value.'.index';

        $this->actingAs($user)
            ->get(route($routeName))
            ->assertRedirect(\App\Filament\Pages\PageLocked::getUrl());
    }
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter PageLockEnforcementTest`
Expected: FAIL — `Class "App\Http\Middleware\EnforcePageLocks"` / `PageLocked` not found.

- [ ] **Step 3: Write the locked page**

Read `app/Filament/Pages/ScanPackages.php` for the custom `Page` shape and `app/Filament/Concerns/LocksWhenUnauthorized.php` for the notice. Create `app/Filament/Pages/PageLocked.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LocksWhenUnauthorized;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/**
 * Where the middleware sends an employee who opened a locked page.
 *
 * It is not itself lockable and carries no navigation entry — it is only ever
 * reached by redirect. It renders the shared locked notice so a page locked by
 * an administrator looks the same as one gated by a missing capability.
 */
class PageLocked extends Page
{
    use LocksWhenUnauthorized;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'page-locked';

    protected static ?string $title = 'صفحة مقفلة';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->lockNotice('هذه الصفحة مقفلة من الإدارة.'),
        ]);
    }
}
```

If a `Page` in this version has no `content(Schema $schema)` hook, use the one `ScanPackages` uses to render its schema, and note the substitution in your report.

- [ ] **Step 4: Write the middleware**

Create `app/Http/Middleware/EnforcePageLocks.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Enums\LockablePage;
use App\Filament\Pages\PageLocked;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The single enforcement point for per-employee page locks.
 *
 * Central by design: one check over every panel request, driven by the
 * LockablePage registry, so no destination can be lockable in the UI yet
 * unenforced. A page added later is covered the moment its key is in the
 * registry — no new enforcement code.
 */
class EnforcePageLocks
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $page = LockablePage::fromRouteName($request->route()?->getName());

        if ($user instanceof User && $page !== null && $user->isPageLocked($page)) {
            return redirect(PageLocked::getUrl());
        }

        return $next($request);
    }
}
```

- [ ] **Step 5: Register the middleware on the panel**

In `app/Providers/Filament/AdminPanelProvider.php`, add `EnforcePageLocks` to the panel's `->authMiddleware([...])` array, AFTER `Authenticate::class`:

```php
            ->authMiddleware([
                Authenticate::class,
                EnforcePageLocks::class,
            ]);
```

This placement is deliberate: `authMiddleware` runs only on authenticated panel routes and only after `Authenticate` has resolved the user, so `$request->user()` is guaranteed populated. Add the import `use App\Http\Middleware\EnforcePageLocks;`.

Confirm in your test run that an authenticated employee is actually redirected (the enforcement tests prove `$request->user()` is seen); if for any reason the user is null here, the general `->middleware([...])` array is the fallback, and note the move in your report.

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter PageLockEnforcementTest`
Expected: PASS, 6 tests.

- [ ] **Step 7: Run the full suite**

Run: `php artisan test`
Expected: PASS. No existing test should break — the middleware is inert unless a user has a lock, and no existing user does.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/EnforcePageLocks.php app/Filament/Pages/PageLocked.php app/Providers/Filament/AdminPanelProvider.php tests/Feature/PageLockEnforcementTest.php
git commit -m "feat: enforce page locks centrally, with a shared locked page

One middleware over every panel request, driven by the page registry, redirects
a locked employee to a page that renders the shared locked notice. Central so no
destination can be lockable yet unenforced — a page added later is covered the
moment its key is in the registry. An administrator is never redirected, and a
lock overrides a capability for that page without removing it."
```

---

### Task 4: The lock toggles on the employee screen

**Files:**
- Modify: `app/Filament/Resources/Users/Schemas/UserForm.php`
- Modify: `app/Filament/Resources/Users/Pages/CreateUser.php`
- Modify: `app/Filament/Resources/Users/Pages/EditUser.php`
- Test: `tests/Feature/EmployeeLockTogglesTest.php`

**Interfaces:**
- Consumes: `LockablePage`, `User::lockedPageList()`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/EmployeeLockTogglesTest.php`:

```php
<?php

use App\Enums\LockablePage;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

/**
 * The administrator's controls for locking pages per employee, below the
 * existing capability toggles.
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

    $this->actingAs($this->admin);
});

test('the form shows a lock toggle for every registry page and none for the dashboard', function () {
    $component = Livewire::test(EditUser::class, ['record' => $this->clerk->getRouteKey()]);

    foreach (LockablePage::cases() as $page) {
        $component->assertFormFieldExists("lock_{$page->value}");
    }

    $component->assertFormFieldDoesNotExist('lock_dashboard');
});

test('toggling a lock on stores it, and off removes it', function () {
    Livewire::test(EditUser::class, ['record' => $this->clerk->getRouteKey()])
        ->fillForm(['lock_payments' => true])
        ->call('save');

    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeTrue();

    Livewire::test(EditUser::class, ['record' => $this->clerk->getRouteKey()])
        ->fillForm(['lock_payments' => false])
        ->call('save');

    expect($this->clerk->fresh()->isPageLocked(LockablePage::Payments))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter EmployeeLockTogglesTest`
Expected: FAIL — no `lock_*` fields.

- [ ] **Step 3: Add the lock section to the form**

In `app/Filament/Resources/Users/Schemas/UserForm.php`, build lock toggles the same way capability toggles are built, and add a section below «الصلاحيات الإضافية». After the `$capabilityToggles` loop, add:

```php
        $lockToggles = [];

        // One toggle per lockable page (D-026). Hydrated from the stored lock
        // list, saved back in the page mutators, exactly like the capability
        // toggles above.
        foreach (LockablePage::cases() as $page) {
            $lockToggles[] = Toggle::make("lock_{$page->value}")
                ->label($page->label())
                ->formatStateUsing(fn (?User $record): bool => $record ? $record->isPageLocked($page) : false);
        }
```

And add this section to the schema `->components([...])`, after the «الصلاحيات الإضافية» section:

```php
                Section::make('الصفحات المقفولة')
                    ->description('امنع الموظف من فتح صفحات محددة. القفل يمنع الصفحة فقط، لا يغيّر صلاحياته.')
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::WarehouseEmployee->value)
                    ->schema($lockToggles)
                    ->columns(2),
```

Add `use App\Enums\LockablePage;` to the imports.

- [ ] **Step 4: Save the toggles in both page mutators**

`CreateUser` and `EditUser` each extract capability toggles in a mutator. Add the same extraction for lock toggles. In `app/Filament/Resources/Users/Pages/EditUser.php`, inside `mutateFormDataBeforeSave`, before `return $data;`, add:

```php
        $locked = [];
        foreach (LockablePage::cases() as $page) {
            $key = "lock_{$page->value}";
            if (! empty($data[$key])) {
                $locked[] = $page->value;
            }
            unset($data[$key]);
        }
        // An administrator is never locked; keep the column clean for them.
        $data['locked_pages'] = ($data['role'] ?? null) === UserRole::Administrator->value ? [] : $locked;
```

Add `use App\Enums\LockablePage;` to `EditUser`. Make the identical change in `app/Filament/Resources/Users/Pages/CreateUser.php` (it has a `mutateFormDataBeforeCreate` with the same capability-extraction shape — mirror it), adding the import there too.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter EmployeeLockTogglesTest`
Expected: PASS, 2 tests.

- [ ] **Step 6: Run the full suite**

Run: `php artisan test`
Expected: PASS. `EmployeeManagementTest` and any test that saves a user through these pages must stay green — the new mutator code adds `locked_pages` but leaves the capability handling untouched.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Users/ tests/Feature/EmployeeLockTogglesTest.php
git commit -m "feat: lock toggles on the employee screen

Below the capability toggles, a section to lock specific pages per employee —
one toggle per registry page, hydrated and saved the same way the capability
toggles are. Shown only for employees; the copy states a lock blocks the page,
not a capability."
```

---

### Task 5: Show the lock in the navigation, and update the handoff state

**Files:**
- Modify: `docs/current-state.md`

The spec asks that a locked page still show in the navigation, marked, rather than vanish — so the employee knows it exists. The middleware already leaves navigation untouched (it acts on access, not the menu), so a locked page's nav entry already appears and clicking it reaches the locked page. No code is needed for the entry to show; this task records the feature and the state.

- [ ] **Step 1: Update the state file**

In the "Built and tested" table, add:

```markdown
| Per-employee page locking, enforced centrally (D-026) | done |
```

In the "Decisions taken during the rebuild" table, add:

```markdown
| D-026 | Per-employee page locking — a page deny-list, not a permission matrix |
```

Update the header's test and assertion counts to the figures `php artisan test` prints.

- [ ] **Step 2: Verify the counts are real**

Run: `php artisan test`
Copy the reported `tests` and `assertions` figures into the header. Do not estimate them.

- [ ] **Step 3: Commit**

```bash
git add docs/current-state.md
git commit -m "docs: record per-employee page locking in the handoff state"
```
