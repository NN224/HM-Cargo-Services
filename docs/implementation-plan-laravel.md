# HM Cargo Services - Laravel Implementation Plan

**Date:** 2026-07-20
**Status:** APPROVED by the owner on 2026-07-20. Execution started at Phase 0.
**Authority:** [`decisions.md`](decisions.md) D-001, D-002, D-017, D-018 and [`superpowers/specs/2026-07-18-hm-cargo-system-design.md`](superpowers/specs/2026-07-18-hm-cargo-system-design.md)

Supersedes [`implementation-plan-nodejs.md`](implementation-plan-nodejs.md).

## Guiding principle

Filament supplies the administration surface: authentication, policies, tables, filters, forms, bulk actions, widgets, RTL, and mobile layout. We write **domain services and business rules only**. Any hand-built generic CRUD screen is a defect in this plan.

Every phase ends in a working, demonstrable state. No phase depends on a later phase to be useful.

---

## Phase 0 - Environment and clean slate — DONE 2026-07-20

Delivered: Composer 2.10.2, Laravel 13.20.0, Filament 5.7, Pest 4.7. SQLite for
development and `:memory:` for tests. Locale `ar`, timezone `Asia/Dubai`;
`/admin/login` renders `dir="rtl"` and returns 200, `/admin` redirects when
unauthenticated. The Node prototype was moved to `legacy-node-prototype/`
rather than deleted, because Laravel needed `package.json`, `vite.config`, and
`index.html` at the root; it is deleted for real at Phase 6. The committed
`sqlite.db` is untracked.


1. Install Composer (`brew install composer`).
2. Create the application on the current stable Laravel release in place, preserving `docs/`, `AGENTS.md`, `README.md`, and `.git`. Confirm the exact version at this step rather than assuming one.
3. Install the current stable Filament release and generate the admin panel. Verify Filament's compatibility with the chosen Laravel version before proceeding.
4. Configure SQLite for local development and tests; document the PostgreSQL production connection without provisioning it yet.
5. Add `.gitignore` entries for `*.sqlite`, `.env`, `vendor/`, and `node_modules/`. **Untrack the committed `sqlite.db`** — a database belongs in no repository.
6. Set locale to Arabic, direction RTL, timezone Asia/Dubai.

**Done when:** `php artisan serve` renders an empty Arabic RTL Filament panel and `php artisan test` runs green.

**Not removed yet:** the Node prototype stays on disk until Phase 6, so nothing is lost while the replacement is unproven.

## Phase 1 - Identity and warehouse scoping — DONE 2026-07-20

Delivered: `warehouses` table; `role`, `warehouse_id`, `is_active` on `users`
with a real foreign key; `UserRole` enum; `WarehousePolicy`; a
`Warehouse::visibleTo()` query scope applied in `WarehouseResource`; an Arabic
Filament resource with delete and bulk-delete removed; a seeder that reads
`ADMIN_PASSWORD` from the environment and refuses to invent a credential.
7 scoping tests pass, covering both the policy and the query scope, including
the unassigned-employee case and the no-hard-delete rule.


1. Migrations: `users` (with `role`, `warehouse_id`), `warehouses`.
2. `UserRole` enum: `administrator`, `warehouse_employee`.
3. Filament authentication with hashed passwords. **This is the single largest gap in the prototype and Filament closes it in configuration, not code.**
4. `WarehousePolicy` and a global query scope: an employee sees and acts on their assigned warehouse only; an administrator sees all.
5. Filament resource for warehouses, administrator-only.
6. Seeder creating one administrator.

**Tests:** an employee cannot read or mutate another warehouse's records; an administrator can.

**Done when:** login works, and warehouse scoping is enforced in policies rather than by hiding UI.

## Phase 2 - Customers, routes, and rates — DONE 2026-07-20

Delivered: `customers`, `routes`, `customer_rates` with foreign keys on every
relationship and a unique rate per customer+route. Money is `rate_per_kg_cents`,
integer cents only. Arabic Filament resources; the rate form takes dollars and
converts to cents on save, so no operator ever types a cent value. Delete and
bulk-delete removed from customers, warehouses and routes; rates stay deletable
as pricing configuration, since charge history is preserved by the shipment
snapshot. 9 domain tests pass, covering direct-vs-transit route distinctness,
per-route pricing, rate uniqueness, positive-money enforcement, and the rule
that a missing rate reports null rather than defaulting.

**Deviation to revisit in Phase 7:** the "origin must differ from destination"
invariant is enforced in `Route::booted()`, not as a database CHECK. Laravel has
no portable `check()` helper and SQLite cannot add one post-creation; emitting
SQLite-only trigger SQL would have broken the PostgreSQL parity D-002 requires.
Add the real constraint when PostgreSQL is provisioned.


1. Migrations: `customers` (`is_credit_customer`, credit terms), `routes` (origin, destination, nullable transit warehouse, `base_cost_per_kg` in cents), `customer_rates` (customer, route, `rate_per_kg` in cents, unique on customer+route per D-018).
2. Foreign keys and check constraints on every relationship and on positive money values. **The prototype declared none.**
3. Filament resources for all three, with the rate matrix reachable from the customer page.
4. `Money` cast: integer cents in, formatted USD out. Nobody types cents into a form.

**Tests:** rate uniqueness per customer and route; positive-value constraints reject bad input at the database level.

**Done when:** an administrator can configure the three initial routes from D-005 and per-customer rates for each.

## Phase 3 - Shipments, packages, and barcodes — DONE 2026-07-20

Delivered: `shipments` and `packages`. Two separate identifiers — a readable
`HM-2026-000001` reference for staff, and a 48-character random `public_token`
for the tracking URL, because AGENTS.md forbids exposing a sequential id
publicly. Weight is exact decimal end to end: `decimal(12,4)` columns, decimal
casts, and summing in the database. Barcodes are random rather than
timestamp-derived. Shipment and package statuses are separate fixed enums. No
recipient address anywhere (D-019). Arabic Filament screen with a package
repeater and a live weight total. Package changes recalculate the shipment
total through model events, so any entry point stays consistent.

**Not yet built in this phase:** A4 label printing and phone-camera scanning.

1. Migrations: `shipments` (unguessable `public_token`, billing customer, recipient snapshot, status, nullable `rate_per_kg` snapshot, `total_weight` exact decimal, `total_amount` cents, `paid_amount` cents), `packages` (unique generated `barcode`, exact decimal weight, status).
2. `ShipmentService::create()` — issues the shipment, its packages, and their barcodes in one transaction.
3. Package and shipment status enums as separate state machines, per the lifecycle document.
4. Filament shipment resource with a repeatable packages section and a live total-weight display.
5. A4 barcode label view, printable and phone-displayable, with no price printed (D-011).

**Tests:** exact weight aggregation without rounding; barcode uniqueness; a shipment cannot be created with zero packages.

**Done when:** a Dubai operator can create a multi-package shipment and print its labels.

## Phase 4 - Batches, pricing, and rounding — PARTIALLY DONE 2026-07-20

**Done:** `batches` table and model with its own status enum; pricing columns
on `shipments`; `BatchAssignmentService`. Assignment resolves the customer's
rate for the batch's route, snapshots it, and computes the charge with `bcmul`
so no cent is lost to floating point. It refuses rather than guessing when no
rate exists, and leaves the shipment untouched on refusal. One transaction with
both rows locked. A later rate change never rewrites an existing charge.

Rounding follows D-020: the operator types the final amount; the computed
charge is never overwritten; the adjustment is derived.

**Still to do in this phase:** the Filament batch screen, the dispatch action
that snapshots `cost_per_kg_cents` and locks ordinary edits, and the
profitability widget (revenue, cost, outstanding, profit).

## Phase 4 (original scope, for reference)

> **Note.** Step 2 below describes the automatic `RoundingService` from D-008. That rule was **superseded by D-020** and the service was deleted. Rounding is entered manually. The rest of this section remains accurate.

This is the commercial heart. Nothing here exists in the prototype.

1. Migration: `batches` (route, status, dispatch and arrival dates, `cost_per_kg` cents snapshot).
2. `RoundingService` — the custom rule from D-008: `.01`-`.29` down, `.30`-`.99` up, exact dollars unchanged. Applies to the customer charge only; batch cost and profit retain cents.
3. `BatchAssignmentService` — validates that the customer has a rate for the batch route, snapshots `rate_per_kg` onto the shipment, computes and posts the rounded charge, all transactionally. Rejects assignment when the rate is missing and when a shipment is already in an active batch.
4. Dispatch action: snapshots `cost_per_kg`, locks ordinary route and weight edits.
5. Filament batch resource with a shipment-assignment action and a profitability widget (revenue, cost, outstanding, profit).

**Tests:** every rounding boundary (`.00`, `.01`, `.29`, `.30`, `.99`); the snapshot survives a later rate change; assignment without a rate is rejected; one active batch per shipment.

**Done when:** assigning a shipment to a batch prices it correctly and permanently.

## Phase 5 - Scanning, collection, payments, and tracking

1. `ScanService` — phone-camera package scan at transit and destination, deriving shipment status from package events transactionally.
2. `CollectionService` — enforces the D-015 gate: no collection until every active package has arrived at the destination.
3. `PaymentService` — record cash, Whish, bank, or other; full and partial; oldest-outstanding-first allocation for credit customers; reversal by compensating entry, never deletion.
4. `audit_logs` table and writes from every service above (D-018 scope: financial mutations, status transitions, privileged edits).
5. Public tracking route resolving `public_token`, showing status timeline, package progress, total, paid, and remaining — and nothing else. No login, no sequential IDs.
6. One-click WhatsApp link with prefilled Arabic arrival message and tracking link.

**Tests:** collection blocked while any package is outstanding; FIFO allocation across several shipments; reversal reconciles the balance; the public view leaks no profit, cost, employee, or other-account data.

**Done when:** the full flow from Dubai creation to destination collection and payment runs end to end.

## Phase 6 - Retire the prototype

Only after Phases 1-5 are demonstrated working:

1. Delete `src/`, `server.ts`, `vite.config.ts`, `drizzle.config.ts`, `index.html`, `dist/`, `bun.lock`, `package-lock.json`, and the Node `package.json`.
2. Remove the tracked `sqlite.db`.
3. Update `README.md` for the Laravel setup.
4. Confirm no canonical document still references the Node stack.

## Phase 7 - Production

1. Self-hosted PostgreSQL, private to the server, per D-002 and D-003.
2. Run the domain suite against PostgreSQL to confirm constraint and transaction parity.
3. HTTPS, backups, and a tested restore procedure.

---

## Sequencing note

Phases 1 through 5 are strictly ordered — each depends on the migrations and services of the one before. Phase 6 must not begin early, and Phase 7 must not begin before Phase 5 passes.

## Approval requested

Per `AGENTS.md`, no product code is scaffolded until the owner approves this plan.
