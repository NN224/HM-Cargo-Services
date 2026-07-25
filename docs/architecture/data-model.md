# Conceptual Data Model

This is a technology-neutral domain model. Exact Laravel migrations and class names will be fixed in the approved implementation plan.

## 1. Warehouse

Represents Dubai, Beirut, Syria, and future operating locations.

Core data:

- Unique code and Arabic/English display names.
- Country and optional city.
- Active status.
- Contact information used in customer messages.

Invariants:

- A warehouse referenced by historical records is deactivated, never deleted.
- Warehouse employees belong to exactly one active warehouse in version 1.

## 2. User

Core data:

- Name, email/login, phone, password credential, active status.
- Role: administrator or warehouse employee.
- Warehouse assignment for employees; null for administrators.

Invariants:

- Authorization is enforced through backend policies.
- Every operational or financial mutation records the user and warehouse context.

## 3. Customer

The billing account owner.

Core data:

- Name, normalized phone, optional company name, notes, active status.
- Credit-eligible boolean.

Invariants:

- Duplicate normalized phone numbers require an explicit override.
- Customers with history are deactivated, never deleted.
- Customer is separate from recipient, although they may represent the same person.

## 4. Route

Core data:

- Unique code and display name.
- Origin warehouse.
- Destination warehouse.
- Optional transit warehouse.
- Active status.

Invariants:

- Origin, destination, and transit must be distinct where present.
- New routes can be created without code changes.
- Historical routes are deactivated, never deleted.

## 5. CustomerRouteRate

Core data:

- Customer and route.
- USD-per-kilogram rate stored exactly.
- Effective start date/time and active status.

Invariants:

- A customer cannot have overlapping active rates for the same route and effective period.
- Historical rates are preserved.
- Shipment pricing stores a rate snapshot and does not depend on later lookup.

## 6. ShipmentBatch

Core data:

- Human-readable batch number/name.
- Route.
- Planned departure and actual event times.
- Cost-per-kilogram snapshot.
- Status and notes.

Derived values:

- Shipment count, package count, exact weight, customer revenue, paid, outstanding, cost, and profit.

Invariants:

- Cost-per-kilogram covers the entire route.
- Dispatched batches require privileged audited corrections.
- Batch status is independent of shipment and package statuses.

## 7. Shipment

Core data:

- Internal sequential reference for administration.
- Public high-entropy tracking token.
- Billing customer.
- Recipient snapshot: name and phone; optional non-delivery location note.
- Optional sender snapshot: name and phone.
- Final destination warehouse.
- Optional active batch.
- Route and rate snapshots after batch assignment.
- Exact total weight, computed charge, manually set final charge (D-020), pricing timestamp.
- Operational and payment summary status.
- Notes and timestamps.

Invariants:

- At least one active package is required before operational acceptance.
- One active batch maximum.
- Final destination must match the batch route destination.
- Public tracking never uses the internal sequential reference alone.
- Recipient data is a shipment snapshot and must not change when a customer profile changes.

## 8. Package

Core data:

- Shipment.
- Unique system-generated barcode.
- Sequence number within shipment, such as `2 of 3`.
- Exact decimal weight.
- Optional description and external reference.
- Current operational/exception status.

Invariants:

- Barcode is globally unique and immutable after label issue.
- Weight is positive and is never rounded.
- A collected package cannot be silently moved back to transit.
- Packages with history are cancelled or exception-resolved, never deleted.

## 9. PackageStatusEvent

Append-only event containing:

- Package, status, warehouse, batch where relevant.
- Public-safe note and private operational note kept separately.
- User, timestamp, scan/manual source, and correction linkage.

Invariants:

- Physical arrivals require a package-level event.
- Batch actions cannot invent an arrival scan.
- Corrections append events; they do not overwrite history.

## 10. ShipmentStatusEvent and BatchStatusEvent

Append-only aggregate events with user, timestamp, warehouse, previous status, new status, reason, and source action.

Invariants:

- Aggregate transitions follow `domain/shipment-lifecycle.md`.
- System-derived transitions identify the package event that caused them.

## 11. Charge and Adjustment

Financial ledger records:

- Shipment charge posting.
- Post-dispatch repricing adjustment.
- Cancellation or exception adjustment.
- Amount in USD cents, reason, effective time, user, and linked shipment.

Invariants:

- Posted charges are immutable.
- Corrections use compensating adjustments.

## 12. Payment

Core data:

- Customer, USD amount, method, optional custom method name.
- Collected at, collecting user, warehouse, reference, notes.
- Unique receipt number.
- Optional reversal relationship.

Invariants:

- Amount is positive for an original payment.
- Reversal is a linked compensating record.
- Payment is never hard-deleted.

## 13. PaymentAllocation

Links a payment to one shipment charge/adjustment balance.

Core data:

- Payment, target charge, allocated amount, allocation time and ordering reason.

Invariants:

- Allocations cannot exceed available payment or target outstanding amount without explicit overpayment handling.
- Account-level allocation is oldest-outstanding-first.
- Payment and allocation changes are transactional.

## 14. AuditEvent

Append-only security and privileged-change record containing actor, action, entity type/id, before/after safe snapshots, reason, request context, and timestamp.

Required for:

- Post-dispatch route/weight changes.
- Customer/rate changes.
- Payment reversal.
- Financial adjustment.
- Exception resolution.
- Privileged status correction.

## 15. Key relationships

```text
Customer 1 ── * CustomerRouteRate * ── 1 Route
Customer 1 ── * Shipment
Shipment 1 ── * Package
ShipmentBatch 1 ── * Shipment
Route 1 ── * ShipmentBatch
Package 1 ── * PackageStatusEvent
Shipment 1 ── * ShipmentStatusEvent
ShipmentBatch 1 ── * BatchStatusEvent
Shipment 1 ── * Charge/Adjustment
Customer 1 ── * Payment
Payment 1 ── * PaymentAllocation * ── 1 Charge/Adjustment
User 1 ── * AuditEvent
```

## 16. Transaction boundaries

The following operations must be atomic:

- Assign shipment to batch, snapshot route/rate, and post initial charge.
- Scan package arrival and update aggregate shipment state.
- Collect all arrived packages, derive complete or partial shipment status, and write all package/shipment events.
- Create payment and allocations.
- Reverse payment and release/reapply allocations.
- Reprice dispatched shipment and post adjustment.
