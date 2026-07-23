# Package QR Labels — Design

**Goal:** Every package gets a scannable QR code that encodes its public tracking URL, shown after receiving, on the shipment view, and on the public tracking page, and printable as a label.

## Background

Packages already carry a unique system barcode (`PKG-XXXXXXXXXX`, generated in
`Package::booted`) and a fully working public tracking page
(`TrackingController` at `GET /track/{token}`, where `{token}` is a shipment
`public_token` *or* a package `barcode` — a barcode token redirects to the
shipment page). What is missing is a **visible, camera-scannable code**: the
barcode is stored as text and shown copyable, but never rendered as an image
a customer can scan.

## What we build

A QR code image (SVG) that encodes each package's tracking URL
(`/track/{barcode}`). Scanning it with any phone camera opens the existing
tracking page. The label carries three things, exactly:

1. The package number as-is (`PKG-XXXXXXXXXX`)
2. The tracking link as text
3. The QR code image

## Decisions

- **QR payload = the package's tracking URL** (`route('tracking.show', $package->barcode)`),
  not the bare barcode. A URL is what a phone camera acts on; a bare string is not.
- **One code serves both audiences.** The same QR that a customer scans to
  track is also usable by the warehouse camera scanner: `PackageScanService`
  normalizes a scanned tracking URL back to its trailing barcode before lookup,
  so the existing arrival-scan flow keeps working.
- **SVG, pure PHP.** Use `bacon/bacon-qr-code` with the SVG backend — no GD or
  imagick, safe on the Railway/FrankenPHP image. QR renders as inline SVG:
  crisp at any size, printable, no file storage.
- **Printable labels** live on an auth-gated web route
  (`GET /labels/shipment/{shipment}`), one label per package, with print CSS
  and auto-print. Shipments are visible to every employee (D-025), so any
  authenticated employee may open it.

## Where the code appears

1. **After receiving:** a successful intake redirects to the labels print page
   for the new shipment, ready to print and stick.
2. **Shipment view (admin):** a QR beside each package row, plus a
   "طباعة الملصقات" header action linking to the labels page.
3. **Public tracking page:** the QR is shown so the customer can re-scan or
   share it.

## Components

- `App\Services\QrCode` — `svg(string $data, int $size = 180): string`. Wraps
  bacon. The only place that knows the QR library.
- `Package::trackingUrl(): string` — `route('tracking.show', $this->barcode)`.
- `PackageScanService::normalizeScannedValue(string $raw): string` — if `$raw`
  contains `/track/`, return the last path segment; else return `trim($raw)`.
- `App\Http\Controllers\LabelController@shipment` + `resources/views/labels/shipment.blade.php`.
- Route `labels.shipment` behind `auth`.

## Out of scope (YAGNI)

Custom label sizes; batch-wide bulk printing; storing QR image files; a
separate 1D barcode. All addable later if asked.

## Testing

- `QrCode::svg()` returns non-empty valid SVG markup.
- `Package::trackingUrl()` ends with the package barcode.
- `PackageScanService` resolves a package when handed a full tracking URL.
- Labels route: guest is refused; an authenticated employee sees one label per
  package, each showing the `PKG-` code, the tracking URL text, and an `<svg>`.
- Intake redirects to the labels page on success.
- The public tracking page contains an `<svg>` QR.
