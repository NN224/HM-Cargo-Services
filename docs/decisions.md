# Owner-Approved Decisions

These decisions are binding. Changes require explicit owner approval and a same-change update to all affected specifications.

## D-001: Use a custom lightweight system

**Date:** 2026-07-18  
**Status:** Approved

Use Laravel with Filament administration infrastructure and implement HM Cargo Services' business logic explicitly. Do not adapt a large courier SaaS product and do not build the administration foundation from raw HTML templates.

## D-002: Free database path

**Date:** 2026-07-18  
**Status:** Approved

Use SQLite for local development/tests and self-hosted PostgreSQL in production. No paid database subscription is required.

## D-003: Remote warehouse employees

**Date:** 2026-07-18  
**Status:** Approved

Employees may work from other countries through the HTTPS web application. PostgreSQL remains private on the server. Employees are assigned to a warehouse; administrators see all warehouses.

## D-004: Shipment batches are essential

**Date:** 2026-07-18  
**Status:** Approved

Shipment batches are a core module, not optional tagging. A batch defines the transport route, groups shipments, carries one total route cost per kilogram, supports operational status changes, and reports revenue, cost, outstanding, and profit.

## D-005: Routes are configurable

**Date:** 2026-07-18  
**Status:** Approved

Initial routes are Dubai-to-Lebanon, Dubai-to-Syria-direct, and Dubai-to-Beirut-to-Syria. More routes will be added over time. Routes are records managed by administrators, not hard-coded branches.

## D-006: Customer and recipient are distinct

**Date:** 2026-07-18  
**Status:** Approved from reference-dashboard behavior

The billing customer owns rates, credit, balance, and statement. The recipient collects and normally pays. They may be the same person through a default `recipient is customer` choice.

## D-007: Pricing is weight-only and route-specific

**Date:** 2026-07-18  
**Status:** Approved

Price uses exact total package weight multiplied by the billing customer's rate for the assigned batch route. No volumetric pricing and no minimum weight. Direct and transit routes may have different customer prices.

## D-008: Custom customer rounding

**Date:** 2026-07-18  
**Status:** Approved by voice confirmation

Do not round weight. On the final customer charge only, `.01-.29` rounds down and `.30-.99` rounds up. Batch cost and profit retain cents.

## D-009: USD only

**Date:** 2026-07-18  
**Status:** Approved

All prices, costs, payments, balances, and reports use USD. No exchange-rate or multi-currency feature is allowed in version 1.

## D-010: Delivery is outside the system

**Date:** 2026-07-18  
**Status:** Approved

Some recipients collect and others coordinate delivery independently with Beirut. HM Cargo Services does not manage or pay for that delivery. The system must not include delivery fees or last-mile operations.

## D-011: Multi-package shipments and generated barcodes

**Date:** 2026-07-18  
**Status:** Approved

A shipment contains multiple packages. Every package receives a system-generated barcode, exact weight, and optional description/reference. Labels print on normal A4 paper and barcodes display and scan on phones. Price is not printed on labels.

## D-012: Recipient payment and customer credit

**Date:** 2026-07-18  
**Status:** Approved by voice confirmation

On warehouse arrival, the recipient receives a WhatsApp arrival/amount message, collects the complete shipment, and normally pays. Credit customers may collect without immediate payment and carry an outstanding account balance.

## D-013: Flexible payments

**Date:** 2026-07-18  
**Status:** Approved

Accept cash, Whish, bank transfer, and other named methods. Allow full and partial payments and multiple payments per shipment. Account credit payments allocate oldest outstanding first. Reversals preserve history.

## D-014: Public tracking without login

**Date:** 2026-07-18  
**Status:** Approved

Recipients track without logging in. The secure public view shows route/status timeline, package progress, total charge, paid, remaining, and payment state while hiding account-wide and internal information.

## D-015: Complete-package collection only

**Date:** 2026-07-18  
**Status:** Approved

Track every package separately. A shipment cannot be collected until all active packages arrive. Partial pickup is outside version 1.

## D-016: Privileged corrections and exception states

**Date:** 2026-07-18  
**Status:** Approved

After dispatch, route or weight edits require an administrator and audit history. Include cancelled, missing, and damaged exception states. Financial corrections use adjustments, not deletion.

## D-017: Reaffirm Laravel and Filament; retire the Node.js prototype

**Date:** 2026-07-20  
**Status:** Approved

D-001 and D-002 stand unchanged. The product is built with Laravel and Filament, SQLite for local development and tests, and self-hosted PostgreSQL in production.

**Background.** `docs/implementation-plan-nodejs.md` proposed replacing the stack with Express, React, and Drizzle because "the AI Studio environment strictly runs Node.js containers and does not support Laravel/PHP." That plan carried `Status: Proposed` and was never approved. Product code was scaffolded against it anyway, which contradicted D-001, D-002, and the AGENTS.md rule forbidding scaffolding before an approved task-level plan exists.

**Why the deviation is void.** The environment constraint no longer applies. Development now runs on a local machine with PHP 8.5 available, so nothing prevents Laravel.

**Impact.**

- `docs/implementation-plan-nodejs.md` is marked Rejected and superseded. It is retained as a record, not deleted.
- The Node.js prototype under `src/`, plus `server.ts`, `vite.config.ts`, `drizzle.config.ts`, and the committed `sqlite.db`, is treated as a throwaway prototype. It carries no authentication, tests, foreign keys, migrations, or business rules, and is removed at Phase 6 of the Laravel plan rather than migrated.
- All canonical business documents remain valid without modification. They are the asset this project keeps.

## D-018: Right-size version 1 engineering ceremony

**Date:** 2026-07-20  
**Status:** Approved

Version 1 targets a small operations team, not a regulated financial institution. Engineering obligations are reduced so delivery stays fast, while every real business rule is preserved.

**Retained in full — these are business correctness, not ceremony.**

- USD only.
- Custom customer rounding: `.01`-`.29` down, `.30`-`.99` up; exact dollars unchanged.
- Exact decimal weight with no rounding and no minimum billable weight.
- Route-specific pricing and the price-per-kilogram snapshot taken at batch assignment.
- Complete-package collection gate before a shipment may be collected.
- Customer credit accounts with oldest-outstanding-first allocation.
- Money stored as integer cents.
- Database constraints and migrations enforcing invariants.
- Server-side authorization through backend policies.
- No hard deletion of customers, shipments, packages, batches, or payments.
- Public tracking tokens are high-entropy and never expose sequential internal IDs.

**Deferred past version 1.**

- Full append-only event sourcing for every state change. Version 1 keeps a single `audit_logs` table covering only financial mutations, status transitions, and privileged edits.
- Guaranteed idempotency on all retryable mutations. Version 1 relies on database transactions plus unique constraints; explicit idempotency keys are added only if duplicate submissions are observed in practice.
- The 80 percent global coverage mandate. Version 1 requires tests for the domain rules listed as retained above, and for authorization boundaries. Coverage of Filament UI scaffolding is not required.
- Effective-dated customer rates. Version 1 stores one current rate per customer and route; the snapshot on the shipment already preserves history.
- Rate limiting on public tracking. Deferred until the surface is publicly reachable.

Any deferred item may be reinstated by owner decision without changing a business rule.

## D-019: Delivery stays outside the invoice, and the recipient is name and phone only

**Date:** 2026-07-20  
**Status:** Approved by voice confirmation. Reconfirms D-010 after reviewing the client's existing dashboard.

The client's current system shows a `رسوم التوصيل` field on the pricing screen and a `عنوان المستلم` field on the recipient section. Both were reviewed and both are **excluded** from this system.

**Delivery fees.** HM Cargo Services does not charge the customer a delivery fee and does not place one on the invoice. Delivery is settled directly between the recipient and the Beirut delivery company and never passes through this system. There is no delivery-fee field, and delivery never affects a shipment total, a customer balance, or batch profit.

**Recipient address.** The recipient record carries **name and phone only**. No address is stored, for a concrete operational reason: different people may collect the same shipment, so an address captured at booking time is frequently wrong. The Beirut delivery team takes the address at handover, and details are confirmed with the delivery company directly.

This closes the question rather than amending D-010: D-010 stands in full.

## D-020: Rounding is entered manually, not calculated by a rule

**Date:** 2026-07-20  
**Status:** Approved by voice confirmation. **Supersedes the automatic rule in D-008.**

D-008 specified an automatic custom rule — `.01`-`.29` down, `.30`-`.99` up. The owner has since determined that this is more complexity than the business needs.

**What replaces it.** Amounts are held and displayed with two decimal places, for example `92.50`. A separate, independent field lets the operator set the rounding for that shipment by hand. The system does not round automatically.

**What this changes.**

- `App\Services\RoundingService` and its tests, added earlier on 2026-07-20, are removed. Nothing consumed them yet, so the change costs nothing.
- Money is still stored as integer cents. Manual entry changes who decides the final figure, not how it is stored.
- The rounding figure must remain visible and auditable on the shipment, so a total can always be explained.

**Unchanged from D-008:** weight is never rounded, and batch cost and profit retain their cents.

**How the field behaves — confirmed 2026-07-20.** The operator types the **final amount the customer pays**, not an adjustment. Entering `92.00` against a computed `92.50` bills `92.00`. The operator never does mental arithmetic on a difference.

The shipment therefore holds two figures:

- the **computed charge** — exact total weight multiplied by the snapshotted route rate, kept to the cent and never overwritten;
- the **final charge** — what the operator set.

The rounding adjustment is the difference between them. It is derived rather than stored, so the two figures cannot drift out of agreement, and it stays visible on the shipment so any total can be explained during an audit.

## D-021: Deletion is allowed only where nothing depends on the record

**Date:** 2026-07-20  
**Status:** Approved. Refines the blanket no-hard-delete rule in AGENTS.md and D-018.

The owner asked for the administrator to be able to delete records. The blanket prohibition existed to protect history, not to obstruct correction, and it was too strict: a customer created by mistake with no shipments carries no history worth protecting.

**The rule.** An administrator may permanently delete a record **only when no other record depends on it**. A customer with no shipments, a shipment with no payments, a route no batch has used, a batch with no shipments — all deletable. The moment anything references the record, deletion is refused and the system explains which records are blocking it, offering deactivation instead.

**Why this and not free deletion.** Deleting a customer with forty shipments would leave every historical invoice, statement and batch report pointing at a customer that no longer exists. The figures would not merely be wrong, they would be unexplainable. This rule gives the administrator genuine control over mistakes while making it impossible to corrupt settled history.

**Never deletable regardless of dependencies:** completed payments and their reversals. Financial records are corrected by compensating entries, never removed (D-016).

## D-022: Two roles, plus explicit capability switches per employee

**Date:** 2026-07-20  
**Status:** Approved. Refines D-003 and narrows the AGENTS.md exclusion of permission matrices.

The owner asked to add employees and grant permissions as he sees fit. A full permission matrix — user-defined roles with per-screen, per-action grants — is the exact complexity the client asked to be rid of, so it stays excluded.

**What is built instead.** The two roles from D-003 remain the foundation: administrator sees everything, warehouse employee is scoped to one warehouse. On top of that, each employee carries a small fixed set of capability switches the administrator toggles:

- may price shipments and assign them to batches
- may record payments
- may edit a shipment after its batch has been dispatched
- may delete records, subject to D-021
- may manage customers and rates

Switches are additive and only ever grant beyond the base employee role. An administrator implicitly holds all of them. The list is fixed in code and does not grow without an owner decision, so it cannot drift into the matrix this decision rejects.

**Why not the full matrix.** These five switches cover the real cases — keeping a junior clerk away from pricing and payments — at a fraction of the cost, and with no screen for inventing roles that nobody maintains.

