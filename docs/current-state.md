# Current State — Handoff

**Last updated:** 2026-07-31
**Tests:** 367 passing, 1221 assertions

Read this first if you are picking the project up. It says what exists, what
does not, and what to do next. The binding rules live in [`../AGENTS.md`](../AGENTS.md)
and [`decisions.md`](decisions.md); this file only reports status.

---

## What the system can do today

The operational spine now runs end to end. Log in and manage warehouses,
customers, routes and per-customer rates. Create a multi-package shipment with
an exact total weight. Assign it to a batch, which prices it. Dispatch the
batch and see revenue, cost and profit. Scan packages in at transit and
destination warehouses, with shipment status derived from the package rows
rather than declared. Collect a shipment only once every active package has
arrived. Record full, partial and account-level payments with oldest-first
allocation and reversals. Follow a shipment from the public tracking page
    without logging in. Package progress now uses the fixed five-step journey
    with route-specific labels, selected-package advancement, delay overlays,
    administrator corrections, and a privacy-safe public journey bar.

It also prints A4 labels, hands off to WhatsApp, and produces a customer
statement and a batch report.

Cargo can be received the way it actually arrives: open the load that leaves
on Thursday and add each customer's boxes as they come in, with destination,
rate and barcodes filled in from what the batch already knows.

The dashboard opens on the work rather than a vendor advertisement: two
operational counts for everyone (cargo awaiting a batch, cargo ready to
release) and a recent-shipments list, plus — for a money-holder — the
outstanding total and the customers-in-debt list. Money screens are gated by
capability: an employee without the pricing capability meets the batch report
locked, without the payments capability meets the payments screen locked, and
the standalone customer-rates screen is forbidden without the customers
capability.

## What it cannot do yet

The feature set specified for version 1 is complete. That is not the
same as ready: none of it has been used against real cargo, and no operator
has tried the scan flow on a real phone in a real warehouse.

---

## Built and tested

| Area | State |
|---|---|
| Laravel 13.20 + Filament 5.7 + Pest 4.7, Arabic RTL | done |
| Authentication, two roles, warehouse scoping | done |
| Five capability switches per employee (D-022) | done, with screen |
| Safe deletion — blocked when depended upon (D-021) | done, with buttons |
| Customers, routes, per-route rates | done, with screens |
| Shipments and packages, exact weight, safe identifiers | done, with screen |
| Batch pricing on assignment, dispatch, profitability widget | done, with screen |
| Package scanning and `package_status_events` | done, with mobile screen |
| Complete and partial collection — release arrived packages and retain the rest in transit (D-029) | done |
| Payments, oldest-first allocation, reversals | done, with screen |
| Public tracking on `/track/{token}`, rate limited | done |
| Five-step package journey, selected progress, delays, corrections, safe public bar (D-031) | done, needs real warehouse/airport operational trial |
| A4 labels with a scannable tracking QR + link, no price printed | done, wired into the shipment UI and after intake |
| Two WhatsApp handoffs — tracking at intake, amount at arrival (D-028) | done |
| Customer statement, reconciled against the ledger | done |
| Batch report — counts, weight, revenue, cost, profit | done |
| Shipment destination, enforced against the batch route | done |
| Batch-first intake — receive cargo into an open batch | done |
| Shipment view with its packages, per-package status | done |
| Money screens gated by capability; shared locked-page notice | done |
| Dashboard — operational counts for all, money for a money-holder | done |
| Per-employee page locking, enforced centrally (D-026) | done |
| Operational reports by warehouse, route and date range | done, with screen |
| PostgreSQL in production (Railway), Postgres-PITR backups | done |
| Staging environment on Railway (separate DB + app) | done |
| Production environment on Railway — `system.hmcargoservices.com` + `hmcargoservices.com` | done |

---

## Suggested next step

The system is fully built and deployed. The only remaining step is an
operational trial against real cargo:

- Create a real shipment in Dubai and assign it to a batch.
- Scan packages in at transit and destination warehouses from a real phone.
- Collect and record payment.
- Verify the public tracking page from the recipient's phone.

This will expose gaps that 367 automated tests cannot: scan UX on a real
phone camera, label print quality on a real A4 printer, and WhatsApp
message delivery in the real flow.

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
| D-023 | A missing or damaged package stays in the billable weight. Only cancellation removes it |
| D-024 | Receiving cargo applies an agreed rate — it is not a pricing decision |
| D-025 | Employees see every shipment; batches stay warehouse-scoped |
| D-026 | Per-employee page locking — a page deny-list, not a permission matrix |
| D-027 | The package label carries a QR of its public tracking URL |
| D-028 | Two WhatsApp messages — tracking link at intake, amount at arrival |

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

**A green suite proves only that the written tests pass.** Two real defects
survived a full green run this session, both because nothing asserted the rule:
`isActive()` was changed and silently moved billable weight, and an
administrator — who has no `warehouse_id` — could not record a payment at all,
because the form copied their empty warehouse into a not-null column. When a
predicate is shared by pricing and by an operational gate, change it only with
a test on each side.

**A pattern applied by hand, screen by screen, misses screens.** Visibility
gating was written per-resource, and it was left off several — the standalone
customer-rates screen and the batch profit report exposed money to any
employee, while the identical figures were correctly gated on the
profitability widget. Both are now closed (the report renders locked; the
rates resource 403s a direct URL). Navigation sort and icons were set
per-screen too, and two collided. When a rule must hold across every screen, a
test that enumerates them all catches the omission the hand cannot (see
`NavigationTest`). Shipments staying unscoped is now a recorded decision
(D-025), not a gap.

**Locate a leak by which page can reach the data, not which page renders it.**
Closing the rates leak first targeted the rates section on the customer edit
page — but that page already requires the customers capability to open, so no
employee ever reached it. The real leak was the standalone `CustomerRateResource`:
hidden from the navigation but with its routes still registered and no guard,
so a direct URL served every rate. A test written against the wrong page will
pass while the leak stays open one screen over, and the fix for the wrong page
widened an unrelated policy to make its test reachable. Before gating a screen,
confirm an unauthorised user can actually reach it today — an `assertForbidden`
against the real URL, not an `assertDontSee` on a page they were already
barred from.

**Switching `actingAs()` between freshly-created users inside one test logs
the request out.** The `password` cast is `hashed`, so each new user gets a
different bcrypt salt, and Filament's `AuthenticateSession` middleware reads a
salt change mid-session as session hijacking and drops the auth before the
request reaches your code. A whole-registry enforcement test that created ten
users, one per key, failed on alternating iterations for this reason — not the
feature. Reuse one user and re-`forceFill` the row each iteration; the real
middleware stack stays in the path and the test still walks every key.

**Read before you write, including files another agent just touched.** Work
here often runs several agents against one working tree. A file that looks
original may have been rewritten minutes ago, and `git status` is the only
honest answer about what is actually modified.

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

## Future Enhancements & Post V1 Roadmap

1. **Automated Direct WhatsApp Integration (`evolution-go`):**
   - Proposal to deploy a self-hosted `evolution-go` Docker container (Go-based WhatsApp REST API engine utilizing `whatsmeow`).
   - Enables background automatic messaging for intake tracking links and arrival notifications without opening `wa.me` links in WhatsApp Web.
   - Includes a dedicated Admin-only toggle switch (`isAdministrator()`) to enable (`auto`) or disable (`manual fallback`) automatic sending at any time.
   - Low resource footprint (~25MB RAM), fully self-hosted, 0 monthly API costs.

---

## Running it

```bash
php artisan serve                      # http://127.0.0.1:8000 redirects to /admin
php artisan test                       # 367 passing
php artisan migrate:fresh --seed       # needs ADMIN_PASSWORD in .env
```

The seeder refuses to invent a credential: set `ADMIN_PASSWORD` in `.env` or no
administrator is created.

---

## Branch strategy

| Branch | Purpose | Auto-deploys to |
|---|---|---|
| `production` | Default branch — stable, production-ready code | Production environment on Railway |
| `staging` | Integration branch — tested before merging to production | Staging environment on Railway |

**Workflow:** develop locally → push to `staging` → verify on staging → merge to `production`.

---

## Deployment environments (Railway)

### Staging
- **App:** `hm-cargo-services-staging.up.railway.app` (internal testing)
- **Database:** PostgreSQL (Railway-managed)
- **Purpose:** Verify changes before promoting to production

### Production
- **App (admin):** `system.hmcargoservices.com`
- **Site (public):** `hmcargoservices.com`
- **Database:** PostgreSQL (Railway-managed) + **Postgres-PITR** (point-in-time recovery backups, 6.5 MB and growing)
- **Services on Railway:** `HM-Cargo-Services`, `HM-SITE`, `Postgres`, `Postgres-PITR`
