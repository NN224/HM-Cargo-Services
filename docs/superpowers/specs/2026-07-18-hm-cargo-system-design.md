# HM Cargo Services System Design

**Date:** 2026-07-18  
**Status:** Approved design baseline; awaiting owner review of written specification

## 1. Design goal

Create a lightweight, Arabic-first cargo operations platform that captures HM Cargo Services' real workflow without reproducing the reference dashboard's excessive configurability. The system must handle route-specific pricing, multi-package shipments, shipment batches, warehouse scans, secure public tracking, recipient collection, payments, credit accounts, statements, and profitability.

## 2. Chosen approach

Use Laravel and Filament rather than a static Bootstrap template or an off-the-shelf courier SaaS.

Why:

- Filament supplies authentication-compatible administration primitives, responsive forms, searchable tables, filters, bulk actions, widgets, and notifications.
- Business logic remains explicit and testable instead of being hidden inside a purchased product.
- The project avoids building generic administration UI from scratch.
- The project avoids deleting or maintaining unrelated fleet, driver, COD, SaaS tenancy, and carrier-integration modules.

## 3. Architecture

### Administration application

A Laravel application with a Filament panel serves administrators and warehouse employees. Backend policies enforce warehouse scoping and privileged actions. The panel is Arabic-first and RTL, with phone-friendly scan and collection flows.

### Public tracking application

A small public web surface in the same Laravel application resolves an unguessable shipment token or package barcode to a safe shipment view. It does not expose administration resources or sequential internal IDs.

### Domain services

Keep domain operations isolated behind focused application services:

- Shipment creation and package/barcode issuance.
- Batch assignment, route/rate snapshotting, and initial charge posting.
- Package scanning and aggregate status transitions.
- Shipment collection.
- Payment recording, FIFO allocation, and reversal.
- Post-dispatch repricing and adjustment.
- Customer statement and batch profitability projection.

These services own transactions. Filament actions and public controllers call them rather than duplicating rules.

### Persistence

- SQLite for local development and tests.
- Self-hosted PostgreSQL for production.
- Exact decimal weight and integer-cent money storage.
- Database constraints for uniqueness, required relationships, positive values, and one active batch per shipment.
- Append-only status, financial, and audit records.

## 4. Major components

### Identity and warehouse access

Two roles only: administrator and warehouse employee. Employees are assigned to a warehouse and backend policies scope queries/actions. Administrators may act across warehouses and perform audited corrections.

### Customer accounts and recipients

The billing customer owns route rates, credit, payments, and statements. Shipment recipient data is a historical snapshot and may differ from the customer. Sender data is optional and has no financial ownership in version 1.

### Routes and rates

Routes are configurable records with origin, destination, and optional transit warehouse. Customer rates are route-specific and effective-dated. Batch assignment snapshots the selected route and rate.

### Shipments and packages

A shipment groups one or more physical packages. Each package receives a unique generated barcode and exact weight. Labels support normal A4 printing and phone display. Phone-camera scanning is a primary warehouse workflow.

### Shipment batches

A batch selects one route, groups compatible shipments, stores one full-route cost per kilogram, and has its own lifecycle. Batch actions cannot replace package-level arrival scans.

### Tracking and notification

Public tracking shows safe operational and payment summaries without login. WhatsApp uses a prefilled one-click message and secure tracking link, avoiding a required paid API.

### Financial ledger

Shipment charges, adjustments, payments, reversals, and allocations form an auditable ledger. Customer total rounding is explicit and tested. Batch cost retains cents. Statements and reports derive from ledger facts rather than editable summary fields.

## 5. End-to-end data flow

1. Create/select a billing customer and recipient.
2. Create shipment and packages in Dubai; generate barcodes and record exact weights.
3. Create/select a shipment batch with a configured route and cost rate.
4. Assign shipment to batch; snapshot customer route rate and post rounded charge.
5. Dispatch batch; lock ordinary route/weight edits.
6. Scan packages at transit and/or destination warehouses.
7. Derive partial/complete shipment status from package events.
8. When all packages arrive, prepare WhatsApp arrival/amount/tracking message.
9. Recipient opens tracking or arrives at warehouse.
10. Record full, partial, or credit collection and generate receipt.
11. Collect all packages in one controlled transaction.
12. Customer statement and batch report update from ledger and events.
13. Close the batch only after every shipment is collected, cancelled, or resolved.

## 6. Error handling and safety

- Reject batch assignment when customer route rate is missing.
- Reject route/destination mismatches.
- Reject duplicate package barcodes and non-positive weights.
- Reject collection while any active package is not at destination.
- Reject employee actions outside the assigned warehouse.
- Reject post-dispatch protected edits by employees.
- Use transactions for scan/aggregate updates, pricing/posting, payment/allocation, reversal, collection, and repricing.
- Make retryable mutations idempotent to prevent duplicate payments or scans.
- Use compensating events/adjustments instead of destructive correction.
- Rate-limit public tracking attempts and avoid revealing whether arbitrary sequential IDs exist.

## 7. Security and privacy

- HTTPS is required for remote access.
- PostgreSQL is not publicly accessible.
- Passwords use Laravel's supported password hashing.
- Authorization is server-side.
- Public tokens are high-entropy, revocable, and separate from internal references.
- Public views mask private contact details and never expose profit, cost, employee identity, internal notes, or other account data.
- Audit privileged changes, financial reversals, and exception resolution.

## 8. Testing strategy

### Unit/domain tests

- All customer rounding boundaries.
- Exact weight aggregation.
- Route-rate snapshot behavior.
- Batch cost/profit cents preservation.
- FIFO credit allocation.
- Payment reversal reconciliation.
- Package-to-shipment aggregate status rules.

### Feature tests

- Administrator and warehouse authorization boundaries.
- Shipment creation and multi-package barcode issuance.
- Batch assignment and validation.
- Direct and transit scan workflows.
- Complete-package collection gate.
- Full, partial, and credit payment flows.
- Public tracking privacy and payment summary.

### Integration tests

- SQLite development/test parity for supported queries.
- PostgreSQL constraint and transaction behavior in CI or staging.
- A4 label rendering and phone-camera scanning on representative devices.
- Statement and batch report reconciliation against ledger totals.

## 9. Operational design

- Core operation must not depend on a paid external service.
- Use server-managed PostgreSQL backups and test restore procedures.
- WhatsApp is a one-click handoff, not an API delivery guarantee.
- Application updates and database maintenance remain server responsibilities rather than dashboard buttons.

## 10. Scope controls

Version 1 deliberately excludes delivery logistics, delivery fees, drivers/fleet, multiple currencies, online payments, fine-grained roles, SMTP, carrier APIs, configurable workflow engines, multi-tenant SaaS, and partial package pickup.

Any agent proposing these features must stop and obtain owner approval before changing canonical documents or code.

## 11. Success definition

The product succeeds when Dubai can create and dispatch multi-package cargo, destination warehouses can scan and collect it, customers can track and understand their amount due without login, payments and credit reconcile, and management can see accurate batch profitability—without requiring paid database services or unrelated courier complexity.

