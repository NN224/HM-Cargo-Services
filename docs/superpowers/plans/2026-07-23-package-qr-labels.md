# Package QR Labels Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a scannable QR (encoding each package's public tracking URL) and the tracking link to the *existing* A4 label, wire the already-built label pages into the UI, show the QR on the shipment view and public tracking page, and make the warehouse scanner accept a scanned tracking URL.

## What already exists (do NOT rebuild)

A complete, tested labels feature is already in the codebase (commit `86aab3a`):

- **Routes** (`routes/web.php`, inside `middleware(['auth'])`):
  - `labels.package` → `GET /labels/packages/{package}`
  - `labels.shipment` → `GET /labels/shipments/{shipment}`
- **`app/Http/Controllers/LabelController.php`** — `printPackage()` and
  `printShipment()`; both call `Gate::authorize('view', $shipment)` and pass
  `['shipment' => ..., 'labels' => [{package, sequence}], 'destination' => ...]`.
- **`resources/views/labels/print.blade.php`** — A4 cards, one per package,
  rendering a **1D Code128 barcode via JsBarcode (CDN)**, the barcode text, the
  package sequence ("1 من 2"), reference, recipient, and destination. No pricing.
- **`tests/Feature/BarcodeLabelTest.php`** — asserts barcode/sequence/reference/
  recipient/destination present, pricing absent, auth required, policy enforced.

Two facts about the current state that shape this plan:

1. The label carries a **1D barcode**, which a phone camera cannot act on to
   open a link — this is the gap the customer needs closed.
2. The label pages are **not linked from anywhere in the UI** — no button
   reaches them; an operator would have to type the URL.

**Approach: extend, don't replace.** Keep the existing 1D barcode and all label
fields (they are tested and useful for handheld scanners). *Add* a
server-rendered QR of the tracking URL and the tracking link text. Wire the
pages into the UI.

**Tech Stack:** Laravel 13.20, Filament 5.7, Pest 4.7, `bacon/bacon-qr-code` ^3.1 (already installed), SQLite (test) / PostgreSQL (prod).

## Global Constraints

- All UI copy is Arabic (RTL). No float arithmetic; weight/money paths are untouched.
- QR is rendered **server-side as inline SVG** (no new CDN dependency); the `<?xml ...?>` prolog is stripped before embedding.
- QR payload is always `route('tracking.show', $package->barcode)` — a URL, never the bare barcode.
- **Never** add pricing to the label — `BarcodeLabelTest` asserts its absence; keep those assertions passing.
- Do not create a new labels controller, route, or view — extend the existing ones named above.
- `bacon/bacon-qr-code ^3.1` is already installed. Do not re-require it.

---

### Task 1: `QrCode` service + `Package::trackingUrl()`

**Files:**
- Create: `app/Services/QrCode.php`
- Modify: `app/Models/Package.php`
- Test: `tests/Unit/QrCodeTest.php`, `tests/Feature/PackageTrackingUrlTest.php`

**Interfaces:**
- Produces: `App\Services\QrCode::svg(string $data, int $size = 180): string` — inline SVG, no XML prolog.
- Produces: `Package::trackingUrl(): string` — `route('tracking.show', $this->barcode)`.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/QrCodeTest.php`:

```php
<?php

use App\Services\QrCode;

test('svg returns inline markup without an xml prolog', function () {
    $svg = app(QrCode::class)->svg('https://example.test/track/PKG-ABCDE12345');

    expect($svg)->toStartWith('<svg')
        ->and($svg)->toContain('</svg>')
        ->and($svg)->not->toContain('<?xml');
});

test('svg honours the requested pixel size', function () {
    $svg = app(QrCode::class)->svg('https://example.test/track/PKG-ABCDE12345', 240);

    expect($svg)->toContain('width="240"')->and($svg)->toContain('height="240"');
});
```

`tests/Feature/PackageTrackingUrlTest.php`:

```php
<?php

use App\Models\Customer;
use App\Models\Shipment;

test('trackingUrl points at the public tracking route for this barcode', function () {
    $customer = Customer::create(['name' => 'عميل', 'phone' => '+971500000001']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم',
        'recipient_phone' => '+963900000001',
    ]);
    $package = $shipment->packages()->create(['weight_kg' => '1.0000']);

    expect($package->trackingUrl())->toBe(route('tracking.show', $package->barcode));
});
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Unit/QrCodeTest.php tests/Feature/PackageTrackingUrlTest.php`
Expected: FAIL — class/method missing.

- [ ] **Step 3: Write the service**

`app/Services/QrCode.php`:

```php
<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Renders a QR code as inline SVG.
 *
 * SVG via bacon's pure-PHP backend needs no GD or imagick, so it is safe on
 * the FrankenPHP production image. Bacon prefixes an XML prolog; inline SVG in
 * HTML must not carry it, so everything before the first <svg is dropped.
 */
class QrCode
{
    public function svg(string $data, int $size = 180): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($data);
        $start = strpos($svg, '<svg');

        return $start === false ? $svg : substr($svg, $start);
    }
}
```

- [ ] **Step 4: Add `trackingUrl()` to `Package`**

After `hasArrivedAtDestination()` in `app/Models/Package.php`:

```php
    /** The public tracking URL a customer reaches by scanning this package. */
    public function trackingUrl(): string
    {
        return route('tracking.show', $this->barcode);
    }
```

- [ ] **Step 5: Run to verify they pass**

Run: `php artisan test tests/Unit/QrCodeTest.php tests/Feature/PackageTrackingUrlTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Services/QrCode.php app/Models/Package.php tests/Unit/QrCodeTest.php tests/Feature/PackageTrackingUrlTest.php
git commit -m "feat: add QrCode service and Package::trackingUrl"
```

---

### Task 2: Warehouse scanner tolerates a tracking URL

**Files:**
- Modify: `app/Services/PackageScanService.php`
- Test: `tests/Feature/PackageScanTest.php` (append one test)

**Interfaces:**
- Produces: `scan()` resolves the package when handed either `PKG-XXXX` or `.../track/PKG-XXXX`.

**Context:** The camera scanner (`ScanPackages`, `new BarcodeDetector()` with
default formats) decodes QR and writes its raw value — the tracking URL — into
the barcode field. `scan()` currently looks up `->where('barcode', trim($barcode))`,
which never matches a URL. Normalize first.

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/PackageScanTest.php`. Reuse the file's existing
`beforeEach` and whatever setup its passing "arrival at destination" test uses
to obtain a receivable package on a dispatched batch. Then:

```php
test('a scanned tracking URL resolves to the same package', function () {
    // Build a receivable package on a dispatched batch exactly as the
    // successful-scan test above does. Name it $package with its $warehouse
    // and acting $user in scope.
    $scanned = app(App\Services\PackageScanService::class)
        ->scan($package->trackingUrl(), $warehouse, $user, 'camera_or_scanner');

    expect($scanned->id)->toBe($package->id);
});
```

> Copy the exact receivable-package setup from the neighbouring successful-scan
> test in this file. Do not invent domain states.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test tests/Feature/PackageScanTest.php`
Expected: FAIL — "لم يُعثر على طرد بهذا الباركود."

- [ ] **Step 3: Normalize in the service**

In `scan()`, change the lookup to normalize first:

```php
            $identity = Package::query()
                ->where('barcode', $this->normalizeScannedValue($barcode))
                ->first(['id', 'shipment_id']);
```

Add the private method:

```php
    /**
     * A camera reading a package QR yields the tracking URL, not the bare
     * code. Reduce a `.../track/PKG-XXXX` value to its trailing segment so the
     * same code a customer scans also drives an arrival scan. A plain barcode
     * passes through unchanged.
     */
    private function normalizeScannedValue(string $raw): string
    {
        $value = trim($raw);

        if (str_contains($value, '/track/')) {
            $value = trim(substr($value, strrpos($value, '/') + 1));
        }

        return $value;
    }
```

- [ ] **Step 4: Run to verify pass**

Run: `php artisan test tests/Feature/PackageScanTest.php`
Expected: PASS (existing + new).

- [ ] **Step 5: Commit**

```bash
git add app/Services/PackageScanService.php tests/Feature/PackageScanTest.php
git commit -m "feat: accept a tracking URL at package scan"
```

---

### Task 3: Add QR + tracking link to the existing label

**Files:**
- Modify: `app/Http/Controllers/LabelController.php`
- Modify: `resources/views/labels/print.blade.php`
- Test: `tests/Feature/BarcodeLabelTest.php` (extend, keep all existing assertions)

**Interfaces:**
- Consumes: `QrCode::svg()`, `Package::trackingUrl()`.

**Context:** `printPackage()`/`printShipment()` build `$labels` as
`[{package, sequence}]`. Add `qr` (SVG) and `tracking_url` to each entry. Keep
the 1D barcode and every existing field.

- [ ] **Step 1: Extend the test**

Add assertions to the two existing passing tests in `BarcodeLabelTest.php`
(the single-package and full-shipment prints), asserting the QR and link now
appear — without removing any existing assertion:

```php
    // Appended inside the existing "prints a package label ..." test, after
    // the current assertions:
    $response->assertSee($this->package1->trackingUrl());
    $response->assertSee('<svg', escape: false); // QR + JsBarcode both use <svg>; QR presence is covered by the URL assertion above
```

Add one focused new test proving the QR encodes the URL server-side (not the
CDN 1D barcode):

```php
it('renders a server-side QR of the tracking URL on the label', function () {
    $user = User::factory()->create(['role' => UserRole::Administrator]);
    Gate::define('view', fn (User $u, Shipment $s) => true);

    $html = actingAs($user)->get("/labels/packages/{$this->package1->id}")->getContent();

    // The QR SVG is emitted inline by the server (no network needed), and the
    // tracking URL is printed as readable text beside it.
    expect($html)->toContain('<svg')
        ->and($html)->toContain($this->package1->trackingUrl());
});
```

- [ ] **Step 2: Run to verify the new assertions fail**

Run: `php artisan test tests/Feature/BarcodeLabelTest.php`
Expected: FAIL — tracking URL not on the page yet.

- [ ] **Step 3: Pass QR + URL from the controller**

In `LabelController`, inject `QrCode` into both methods (type-hint the
parameter, e.g. `printPackage(Request $request, Package $package, QrCode $qr)`)
and enrich each `$labels` entry. For `printPackage`:

```php
        $labels = [
            [
                'package' => $package,
                'sequence' => $packageSequence,
                'tracking_url' => $package->trackingUrl(),
                'qr' => $qr->svg($package->trackingUrl(), 200),
            ],
        ];
```

For `printShipment`, inside the `->map(...)`:

```php
            return [
                'package' => $package,
                'sequence' => ($index + 1).' من '.$totalCount,
                'tracking_url' => $package->trackingUrl(),
                'qr' => $qr->svg($package->trackingUrl(), 200),
            ];
```

Add `use App\Services\QrCode;` at the top.

- [ ] **Step 4: Render QR + link in the label**

In `resources/views/labels/print.blade.php`, inside each `.label-card`, add a
QR block and the tracking link near the barcode (keep the existing 1D barcode
and text). For example, after the `.barcode-text` div:

```blade
            <div class="text-center" style="margin: 12px 0;">
                <div style="width:150px;height:150px;margin:0 auto;">{!! $label['qr'] !!}</div>
                <div style="font-size:12px;direction:ltr;word-break:break-all;margin-top:6px;">
                    {{ $label['tracking_url'] }}
                </div>
            </div>
```

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test tests/Feature/BarcodeLabelTest.php`
Expected: PASS (all existing + new).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/LabelController.php resources/views/labels/print.blade.php tests/Feature/BarcodeLabelTest.php
git commit -m "feat: add a tracking QR and link to the package label"
```

---

### Task 4: Wire labels into the shipment UI + inline QR on the view

**Files:**
- Modify: `app/Filament/Resources/Shipments/Tables/ShipmentsTable.php`
- Modify: `app/Filament/Resources/Shipments/Pages/ViewShipment.php`
- Create: `resources/views/filament/entries/qr.blade.php`
- Test: `tests/Feature/ShipmentLabelUiTest.php`

**Interfaces:**
- Consumes: route `labels.shipment`, `QrCode::svg()`, `Package::trackingUrl()`.

**Context:** The labels pages exist but no UI reaches them. Add a
"طباعة الملصقات" action to the shipments table row actions and the
`ViewShipment` header, both linking to `route('labels.shipment', $record)` in a
new tab. Also render the QR per package on `ViewShipment`.

> **Filament HTML note:** Filament sanitizes strings in several `TextEntry`
> HTML paths and may strip raw `<svg>`. Render the inline QR through a
> `ViewEntry` pointing at a Blade partial that echoes the pre-rendered SVG with
> `{!! !!}` — do NOT rely on `TextEntry::make(...)->html()` for the SVG. The
> `assertSee('<svg')` test guards this either way.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Enums\UserRole;
use App\Filament\Resources\Shipments\Pages\ViewShipment;
use App\Models\Customer;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;

beforeEach(function () {
    $warehouse = Warehouse::create(['name' => 'دبي', 'location' => 'الإمارات']);
    $this->admin = User::create([
        'name' => 'مدير', 'email' => 'ship-ui@hmcargo.test', 'password' => 'secret',
        'role' => UserRole::Administrator, 'warehouse_id' => $warehouse->id,
    ]);
    $customer = Customer::create(['name' => 'عميل', 'phone' => '+971500000001']);
    $this->shipment = Shipment::create([
        'customer_id' => $customer->id, 'recipient_name' => 'مستلم', 'recipient_phone' => '+963900000001',
    ]);
    $this->shipment->packages()->create(['weight_kg' => '1.0000']);
    $this->actingAs($this->admin);
});

test('the shipment view offers a print-labels action and shows a QR', function () {
    Livewire::test(ViewShipment::class, ['record' => $this->shipment->getRouteKey()])
        ->assertSee('طباعة الملصقات', escape: false)
        ->assertSee('<svg', escape: false)
        ->assertSee(route('labels.shipment', $this->shipment));
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test tests/Feature/ShipmentLabelUiTest.php`
Expected: FAIL — action/QR absent.

- [ ] **Step 3: Add the Blade QR partial**

`resources/views/filament/entries/qr.blade.php`:

```blade
<div style="width:110px;height:110px;">{!! $getState() !!}</div>
```

- [ ] **Step 4: Add the table action**

In `ShipmentsTable.php` `recordActions([...])`, add (before `whatsapp` or after
`copyTrackingLink`):

```php
                Action::make('printLabels')
                    ->label('طباعة الملصقات')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Shipment $record): string => route('labels.shipment', $record))
                    ->openUrlInNewTab(),
```

Ensure `use App\Filament\Resources\Shipments\...` / `Filament\Actions\Action`
and `App\Models\Shipment` are imported (Action is already used in this file).

- [ ] **Step 5: Add the header action + inline QR on `ViewShipment`**

Imports:

```php
use App\Services\QrCode;
use Filament\Actions\Action;
use Filament\Infolists\Components\ViewEntry;
```

Header action in `getHeaderActions()` (before `EditAction::make()`):

```php
            Action::make('printLabels')
                ->label('طباعة الملصقات')
                ->icon('heroicon-o-printer')
                ->url(fn (): string => route('labels.shipment', $this->record))
                ->openUrlInNewTab(),
```

In `infolist()`, resolve the service and add `qr` to each package row of the
`->state([... 'packages' => ...])` map:

```php
        $qr = app(QrCode::class);
        // ... in the packages map, add to each row:
                    'qr' => $qr->svg($package->trackingUrl(), 110),
```

In the `RepeatableEntry` schema add the QR entry and bump the column count:

```php
                                ViewEntry::make('qr')
                                    ->label('رمز التتبّع')
                                    ->view('filament.entries.qr'),
```

(Adjust `->columns(5)` to `->columns(6)`.)

- [ ] **Step 6: Run to verify pass**

Run: `php artisan test tests/Feature/ShipmentLabelUiTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Shipments/Tables/ShipmentsTable.php app/Filament/Resources/Shipments/Pages/ViewShipment.php resources/views/filament/entries/qr.blade.php tests/Feature/ShipmentLabelUiTest.php
git commit -m "feat: reach the labels page from the shipment UI and show a QR"
```

---

### Task 5: Redirect after receiving + QR on the public tracking page + docs

**Files:**
- Modify: `app/Filament/Pages/ReceiveIntoBatch.php`
- Modify: `app/Http/Controllers/TrackingController.php`
- Modify: `resources/views/tracking/show.blade.php`
- Modify: `docs/decisions.md`, `docs/current-state.md`
- Test: `tests/Feature/BatchIntakeTest.php` (append), `tests/Feature/TrackingQrTest.php`

**Interfaces:**
- Consumes: route `labels.shipment`, `QrCode::svg()`.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/BatchIntakeTest.php`, reusing the file's existing
setup and the exact valid payload of its passing "successful intake" test:

```php
test('a successful intake redirects to the printable labels page', function () {
    // Arrange + fill exactly as the passing successful-intake test in this
    // file; then:
    Livewire::test(App\Filament\Pages\ReceiveIntoBatch::class)
        ->fillForm([/* the same valid payload the passing intake test uses */])
        ->call('receive')
        ->assertRedirect(route('labels.shipment', App\Models\Shipment::latest('id')->firstOrFail()));
});
```

> Copy the payload and setup verbatim from the passing intake test — do not
> invent field names.

`tests/Feature/TrackingQrTest.php`:

```php
<?php

use App\Models\Customer;
use App\Models\Shipment;

test('the public tracking page shows a QR of the tracking URL', function () {
    $customer = Customer::create(['name' => 'عميل', 'phone' => '+971500000001']);
    $shipment = Shipment::create([
        'customer_id' => $customer->id,
        'recipient_name' => 'مستلم كامل',
        'recipient_phone' => '+963900000001',
    ]);
    $shipment->packages()->create(['weight_kg' => '1.0000']);

    $this->get(route('tracking.show', $shipment->public_token))
        ->assertOk()
        ->assertSee('<svg', escape: false);
});
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Feature/BatchIntakeTest.php tests/Feature/TrackingQrTest.php`
Expected: FAIL — no redirect; no `<svg` on tracking page.

- [ ] **Step 3: Redirect after receiving**

In `ReceiveIntoBatch::receive()`, on the success branch, replace the
`$this->form->fill([...])` line with a redirect (keep the success notification):

```php
            $this->redirect(route('labels.shipment', $shipment));

            return;
```

- [ ] **Step 4: QR on the tracking controller + view**

In `TrackingController::show()`, add to the `$tracking` array:

```php
            'qr' => app(\App\Services\QrCode::class)->svg(route('tracking.show', $token), 160),
```

In `resources/views/tracking/show.blade.php`, render it (e.g. a `.card.wide`
below the summary grid):

```blade
    <article class="card wide" aria-label="رمز التتبّع">
        <p class="label">امسح لمتابعة الشحنة</p>
        <div style="width:160px;height:160px;margin-inline:auto;">{!! $tracking['qr'] !!}</div>
    </article>
```

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test tests/Feature/BatchIntakeTest.php tests/Feature/TrackingQrTest.php`
Expected: PASS.

- [ ] **Step 6: Record D-027 and update state**

Append to `docs/decisions.md`:

```markdown
## D-027: The package label carries a QR of its public tracking URL

The label already had a 1D barcode (for handheld scanners) and every package
had a working public tracking page. The label now also renders a QR encoding
`/track/{barcode}` — a URL a phone camera acts on — so a customer scans it and
lands on the tracking page, and the tracking link is printed as text beside it.
The same QR drives the warehouse arrival scan: `PackageScanService` reduces a
scanned tracking URL to its trailing barcode. QR is inline SVG
(bacon/bacon-qr-code, pure PHP — no GD/imagick, no CDN). The already-built
labels pages are now reachable from the shipments table, the shipment view, and
automatically after a successful intake.
```

Update the test/assertion totals in `docs/current-state.md` (run
`php artisan test` for the numbers) and add one line noting package QR labels
are live and wired into the UI.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Pages/ReceiveIntoBatch.php app/Http/Controllers/TrackingController.php resources/views/tracking/show.blade.php docs/decisions.md docs/current-state.md tests/Feature/BatchIntakeTest.php tests/Feature/TrackingQrTest.php
git commit -m "feat: labels after intake, QR on tracking page, record D-027"
```

---

## Final step

Run the full suite (`php artisan test`) — all green — then push to trigger the
Railway redeploy.

## Self-Review Notes

- Extends the existing labels feature; creates no duplicate controller/route/view.
- Keeps every existing `BarcodeLabelTest` assertion (barcode, fields, no pricing).
- QR payload is a URL consistently across label, view, tracking, and scan-normalize.
- Tasks 2 and 5 reuse existing test setup rather than inventing domain states.
- Filament SVG rendering uses a `ViewEntry` + Blade partial to dodge the HTML sanitizer.
