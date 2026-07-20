# Current State — Handoff

**Last updated:** 2026-07-20, end of session
**Commit:** `95ed2aa`
**Tests:** 86 passing, 198 assertions, working tree clean

Read this first if you are picking the project up. It says what exists, what
does not, and what to do next. The binding rules live in [`../AGENTS.md`](../AGENTS.md)
and [`decisions.md`](decisions.md); this file only reports status.

---

## What the system can do today

Log in, and manage the reference data a shipment needs: warehouses, customers,
routes, per-customer rates. Create a shipment with multiple packages and an
exact total weight. Price a shipment by assigning it to a batch.

## What it cannot do yet

No batch screen, no scanning, no collection, no payments, no customer
statements, no public tracking page, no WhatsApp handoff, no profitability
report, no barcode labels. **Roughly half the product remains, and it is the
operational half.**

Do not describe this system as nearly ready.

---

## Built and tested

| Area | State |
|---|---|
| Laravel 13.20 + Filament 5.7 + Pest 4.7, Arabic RTL | done |
| Authentication, two roles, warehouse scoping | done |
| Five capability switches per employee (D-022) | done, no UI yet |
| Safe deletion — blocked when depended upon (D-021) | done, no UI yet |
| Customers, routes, per-route rates | done, with screens |
| Shipments and packages, exact weight, safe identifiers | done, with screen |
| Batch model and pricing on assignment | done, **no screen** |

## Not built

- Filament screen for batches, the dispatch action, profitability widget
- Employee management screen with the capability toggles
- Delete buttons wired to `deleteSafely()`
- Package scanning, collection gate, payments, credit allocation
- Public tracking page on `/track/{token}`
- A4 labels, WhatsApp link, statements, reports
- Removal of `legacy-node-prototype/` (Phase 6)
- PostgreSQL (Phase 7)

---

## Suggested next step

Finish Phase 4: the batch screen, a dispatch action that snapshots
`cost_per_kg_cents` and locks ordinary edits, and the profitability widget.
This is the first point where the owner can see pricing working end to end.

Then Phase 5, in this order: scanning → collection gate → payments → public
tracking.

---

## Decisions taken during the rebuild

Full text in [`decisions.md`](decisions.md). These reversed or narrowed earlier
rules, so do not act on the originals without reading these.

| | |
|---|---|
| D-017 | Laravel and Filament reaffirmed; the Node prototype is throwaway |
| D-018 | Version 1 ceremony reduced — no event sourcing, no idempotency keys, no 80% coverage mandate |
| D-019 | No delivery fees and no recipient address. Name and phone only |
| D-020 | **The automatic rounding rule in D-008 is gone.** The operator types the final amount |
| D-021 | Deletion allowed only when nothing depends on the record |
| D-022 | Two roles plus five fixed capability switches — not a permission matrix |

---

## Traps worth knowing

**Money.** Integer cents everywhere, `bcmul` for multiplication. Floats lose
cents: `0.01 kg × $4.50` truncates to 4 cents through a float and is 5 in
decimal. Six such cases appear in the first 20 kg at the rates in use.

**Weight.** `decimal(12,4)`, summed in SQL, never in PHP. `0.1 + 0.2` is
`0.30000000000000004` as a float.

**Mass assignment.** `total_weight_kg` is deliberately absent from `$fillable`.
`update()` discards non-fillable keys **silently** — this already caused the
total to sit at zero with no error. Derived columns are assigned directly.

**Filament tables render through Livewire.** Column headers are not in the
initial page HTML, so assert them with `Livewire::test(...)`, not `get()`.

**Verification.** Two false "✓" results this session came from shell fallbacks
(`grep ... || echo ok`) firing on a shell error rather than a real match. Check
findings with a real assertion, and confirm file changes with `git status` in
the project root rather than a broad `find`.

---

## Reference dashboard

The client runs an existing system at `shipping.level-you.net/admin`. It is a
source of ideas, not a template — the client explicitly asked for something
simpler and does not want a copy.

Taken from it: separate internal reference and tracking number; the
"recipient is the customer" checkbox; the live weight total; an optional
supplier barcode per package; payment and outstanding columns.

Rejected: the editable status screen (AGENTS.md excludes a workflow engine);
manually typed per-shipment rates (ours come from the customer's route rate);
free-text country and city (ours uses a configured route); delivery fees and
recipient address (D-019).

Its tracking numbers are four digits and guessable, which is why ours separates
a readable staff reference from a 48-character random public token.

---

## Running it

```bash
php artisan serve          # http://127.0.0.1:8000 redirects to /admin
php artisan test           # 86 passing
php artisan migrate:fresh --seed   # needs ADMIN_PASSWORD in .env
```

The seeder refuses to invent a credential: set `ADMIN_PASSWORD` in `.env` or no
administrator is created.
