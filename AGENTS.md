# HM Cargo Services - Agent Instructions

These instructions apply to the entire repository. Every AI agent must read this file and the canonical documents below before planning, coding, reviewing, or changing behavior.

## Canonical documents

Read in this order:

0. `docs/current-state.md` - what is built, what is not, and what to do next. Read before planning.
1. `docs/decisions.md` - owner-approved decisions that must not be changed implicitly.
2. `docs/product-spec.md` - product scope, actors, features, and acceptance criteria.
3. `docs/domain/shipment-lifecycle.md` - shipment, package, batch, and tracking behavior.
4. `docs/domain/pricing-payments.md` - rates, rounding, payments, credit, and profit rules.
5. `docs/architecture/data-model.md` - conceptual entities, relationships, and invariants.
6. `docs/superpowers/specs/2026-07-18-hm-cargo-system-design.md` - approved system design.

If code and documentation disagree, stop and report the conflict. Do not silently choose one.

## Product objective

Build a simple, mobile-friendly cargo administration system for HM Cargo Services. It manages customers, recipients, multi-package shipments, configurable routes, shipment batches, warehouse scanning, public tracking, payments, customer credit, statements, and batch profitability.

The system must remain simpler than the reference dashboard. Do not add generic ERP, fleet, driver, delivery, multi-tenant SaaS, or carrier-integration features without explicit owner approval.

## Non-negotiable business rules

- The billing currency is USD only.
- Local delivery is outside HM Cargo Services' responsibility. Do not model delivery fees, driver dispatch, delivery addresses, or delivery profitability.
- A billing customer and a shipment recipient are separate concepts. They may be the same person.
- The recipient normally pays when collecting the shipment. Credit customers may collect without immediate payment.
- A shipment contains one or more packages. Every package has a unique system-generated barcode and an exact decimal weight.
- Do not round weight. There is no minimum billable weight.
- The customer price is specific to a route. Direct Dubai-to-Syria and Dubai-to-Beirut-to-Syria are different routes and may have different rates.
- A shipment receives its billable route from its batch. Final pricing occurs when the shipment is assigned to a batch.
- Preserve the price-per-kilogram snapshot on the shipment. Later rate changes must not rewrite historical charges.
- Customer rounding is manual (D-020, supersedes D-008): amounts carry two decimals, and the operator types the final amount the customer pays. The system does not round automatically. The computed charge is never overwritten, and the adjustment is derived from the two.
- Batch cost and profit retain cents; the custom customer rounding rule does not apply to batch cost.
- A batch has one total cost-per-kilogram for its entire route, even when the route includes a transit warehouse.
- A shipment may belong to only one active batch at a time.
- Package status, shipment status, and batch status are separate state machines.
- A shipment cannot be completed or collected until all active packages have arrived at the destination warehouse. Partial package pickup is out of scope for version 1.
- Customer credit payments are allocated to the oldest outstanding shipments first unless a payment is explicitly recorded against a particular shipment.
- Payments may be full or partial and may use cash, Whish, bank transfer, or a manually named other method.
- Payment reversals must preserve an audit trail. Never hard-delete financial records.
- Public tracking requires no login, uses an unguessable token, and may show shipment status, package progress, total due, paid amount, remaining amount, and payment status. It must not expose other shipments, full account statements, profit, batch cost, or employee data.
- Warehouse employees see and operate only their assigned warehouse. Administrators see all warehouses.
- Weight or route changes after batch dispatch require an administrator and an audited adjustment.
- Supported exception states include cancelled, missing package, and damaged package.

## Technical direction

- Use Laravel with Filament for the administration panel.
- Use SQLite for local development and automated tests.
- Use self-hosted PostgreSQL in production. Do not introduce a paid database dependency.
- The administration UI is Arabic-first, RTL, responsive, and usable from a phone.
- Barcode labels must support normal A4 printing and on-phone display. Camera scanning must be supported in the mobile web UI.
- Prefer a one-click WhatsApp link with a prefilled message. Do not require a paid WhatsApp API in version 1.
- Keep PostgreSQL private to the server. Employees access the HTTPS web application, never the database directly.

## Scope discipline

Version 1 excludes:

- Local last-mile delivery management and delivery fees.
- Drivers, vehicles, maps, and route optimization.
- Multiple currencies or exchange rates.
- Online payment processing.
- Paid SaaS dependencies required for core operation.
- User-defined roles and per-screen permission matrices. The two roles stand, extended only by the fixed list of capability switches in D-022.
- A user-editable workflow/status engine.
- SMTP configuration and automated email delivery.
- Carrier APIs, label purchasing, and third-party courier integrations.
- Partial pickup of a multi-package shipment.

## Engineering rules

- Use migrations and constraints to enforce invariants; do not rely only on UI validation.
- Store money as integer cents. Never use binary floating point for money.
- Store weight as an exact decimal with documented precision.
- Financial mutations must be transactional. Explicit idempotency keys are deferred past version 1 per D-018; rely on transactions plus unique constraints.
- Record financial mutations, status transitions, and privileged edits in the append-only `audit_logs` table. Full event sourcing for every state change is deferred past version 1 per D-018.
- Never expose sequential internal IDs in public tracking URLs.
- Apply authorization in backend policies, not only by hiding UI elements.
- Do not hard-delete a record that anything else depends on; deactivate it instead. An administrator may permanently delete a record only when nothing references it (D-021). Completed payments, reversals, status events and audit records are never deletable regardless of dependencies.
- Add tests for every retained domain rule in D-018 and for every authorization boundary, before implementation changes. A global 80 percent coverage target does not apply to version 1; Filament scaffolding needs no coverage.
- Keep files focused by responsibility and follow the implementation plan once approved.
- Do not scaffold or implement product code until the owner approves the written specification and a task-level implementation plan exists.

## Documentation discipline

- A business-rule change requires updating the relevant canonical document in the same change.
- An owner-approved decision must be added to `docs/decisions.md` with its date and impact.
- Do not leave `TBD`, `TODO`, or ambiguous alternatives in canonical specifications.
- If a new requirement is not covered, stop and ask the owner instead of inventing behavior.

