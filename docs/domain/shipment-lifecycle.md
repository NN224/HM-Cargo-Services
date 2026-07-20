# Shipment Lifecycle

This document defines the canonical operational lifecycle for packages, shipments, and shipment batches.

## 1. Separate state machines

The system must not use one status field for everything.

- **Package status** describes one physical parcel.
- **Shipment status** summarizes the group of packages belonging to one customer transaction.
- **Batch status** describes the transport movement that carries multiple shipments.

A batch-level action may create events for its shipments, but it must not fabricate package scans.

## 2. Shipment creation in Dubai

1. Select or create the billing customer.
2. Default the recipient to the customer; allow a different recipient.
3. Record optional sender information.
4. Select the final destination, not the transport path.
5. Add at least one package.
6. For each package:
   - Generate a unique system barcode.
   - Record exact decimal weight.
   - Record an optional description or external reference.
7. Calculate exact shipment weight as the sum of active package weights.
8. Print A4 labels or display a barcode on a phone.

The shipment is not finally priced until it is assigned to a batch because the batch determines whether a Syria shipment travels directly or through Beirut.

## 3. Batch creation and dispatch

1. An administrator creates a batch and chooses one configured route.
2. The batch records one USD cost per kilogram for the entire route.
3. Eligible shipments are added.
4. Assignment snapshots the customer's rate for the batch route and calculates the shipment charge.
5. Before dispatch, an administrator confirms shipment/package counts, exact weight, route, cost rate, customer revenue, and expected profit.
6. Dispatch locks ordinary route and weight edits.

Initial route templates:

- Dubai to Lebanon.
- Dubai to Syria direct.
- Dubai to Beirut to Syria.

Routes are data, not hard-coded branches. Future routes can be added by an administrator.

## 4. Normal statuses

### Package statuses

- `created` - barcode generated; package record exists.
- `received_origin` - physically accepted at the Dubai warehouse.
- `in_transit` - travelling in a dispatched batch.
- `arrived_transit` - scanned at a configured transit warehouse.
- `departed_transit` - left the transit warehouse for the final destination.
- `arrived_destination` - scanned at the final warehouse.
- `collected` - released to the recipient after the shipment is eligible for collection.
- `cancelled` - removed before completion with an audited reason.
- `missing` - expected but not present at a checkpoint.
- `damaged` - present but physically damaged.

### Shipment statuses

- `draft` - shipment being entered.
- `awaiting_batch` - packages accepted but no active batch assigned.
- `assigned` - assigned and priced, batch not dispatched.
- `in_transit` - batch dispatched.
- `partial_at_transit` - only some packages scanned at transit.
- `at_transit` - all active packages scanned at transit.
- `partial_at_destination` - only some packages scanned at destination.
- `ready_for_collection` - all active packages arrived at destination.
- `collected` - all active packages released to the recipient.
- `cancelled` - shipment cancelled under an approved exception flow.
- `exception` - at least one active package is missing or damaged and requires resolution.

### Batch statuses

- `draft` - editable batch under preparation.
- `ready` - validated and ready to dispatch.
- `dispatched` - departed the origin warehouse.
- `at_transit` - reached the configured transit warehouse.
- `departed_transit` - left transit for final destination.
- `arrived_destination` - reached the final warehouse.
- `closed` - all included shipments are collected, cancelled, or resolved.
- `cancelled` - transport movement cancelled with an audited reason.

## 5. Aggregate rules

- Package scans are the source of truth for physical arrival.
- A shipment becomes `partial_at_destination` when at least one but not all active packages have arrived.
- A shipment becomes `ready_for_collection` only when every active, non-cancelled package is `arrived_destination`.
- Version 1 does not allow releasing only some packages. The collect action is disabled until the shipment is ready.
- A shipment becomes `collected` only when all active packages are marked collected in one controlled transaction.
- A batch is not closed merely because one shipment is collected.
- A batch becomes eligible for closure only when all shipments are collected, cancelled, or have an explicitly resolved exception.

## 6. Transit behavior

For Dubai to Beirut to Syria:

1. Packages are received in Dubai.
2. The batch is dispatched.
3. Packages are scanned at Beirut as transit arrivals.
4. Missing/damaged differences are recorded.
5. The batch departs Beirut.
6. Packages are scanned at the Syria destination warehouse.
7. Complete shipments become ready for collection.

For direct routes, transit statuses are skipped; they are not created with fake timestamps.

## 7. Destination warehouse workflow

1. The assigned warehouse employee opens the incoming batch.
2. The employee scans each package barcode using a phone camera or compatible scanner.
3. The system shows package, shipment, recipient, package count, expected weight, and payment summary.
4. The system updates package arrival and recalculates aggregate shipment status.
5. When all packages arrive, the system prepares an Arabic WhatsApp arrival message with the secure tracking link and amount due.
6. When the recipient arrives, the employee opens the shipment by barcode.
7. Payment is recorded as full, partial, or credit according to `pricing-payments.md`.
8. The employee confirms collection; the system records user, warehouse, date/time, and package collection events.

Local delivery coordination is outside the system. Reaching `ready_for_collection` means HM Cargo Services has made the shipment available at the warehouse; it does not represent last-mile delivery.

## 8. Public tracking

- Public tracking requires no login.
- A high-entropy token identifies one shipment.
- Scanning any package barcode resolves to the parent shipment through a safe tracking flow.
- The timeline shows only public events and safe labels.
- It may show route, current stage, package progress, exact total weight, charge, paid, remaining, and payment status.
- It must not show internal notes, other shipments, employee identity, customer statement details, batch cost, or profit.
- Cancelled, missing, and damaged events use customer-safe wording approved by the administrator; internal diagnostics remain private.

## 9. Privileged changes

After batch dispatch:

- Warehouse employees cannot change route, package weight, billing customer, or rate snapshot.
- Administrators may correct them only with a reason.
- A correction writes an audit event and, when financial amounts change, a financial adjustment rather than rewriting history.
- Moving a shipment to another route triggers explicit repricing confirmation.
- If payments already exist, the system preserves allocations and records the resulting credit or outstanding difference.

## 10. Exception handling

### Cancelled before dispatch

- Remove the shipment from the draft batch.
- Cancel active packages with a reason.
- Reverse the charge if it was already posted.

### Cancelled after dispatch

- Administrator approval and reason are required.
- Do not automatically erase charges or payments.
- Record the operational and financial resolution explicitly.

### Missing package

- Mark only the missing package.
- Set the parent shipment to `exception`.
- Prevent collection until the package arrives, is cancelled, or the exception is resolved by an administrator.

### Damaged package

- Mark the package damaged with notes.
- Set the shipment to `exception` until an administrator records the resolution.
- Do not automatically change the charge; financial handling is an explicit adjustment.

