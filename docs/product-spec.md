# HM Cargo Services Product Specification

**Status:** Owner-approved design baseline  
**Date:** 2026-07-18  
**Primary language:** Arabic, right-to-left  
**Currency:** USD only

## 1. Purpose

HM Cargo Services needs a simple operational system for receiving cargo in Dubai, grouping shipments into route-specific batches, tracking multi-package movement through destination or transit warehouses, notifying recipients, collecting payments, managing customer credit, and measuring batch profitability.

The product replaces spreadsheet and messaging fragmentation without becoming a generic ERP or a full courier/fleet platform.

## 2. Actors

### Administrator

- Manages all warehouses, users, customers, routes, customer rates, shipments, packages, batches, payments, credit, reports, and settings.
- May perform audited route or weight corrections after dispatch.
- May reverse an incorrect payment without deleting the original record.

### Warehouse employee

- Is assigned to one warehouse.
- Sees batches, shipments, and packages relevant to that warehouse.
- Scans packages, records arrival, sends the prepared WhatsApp message, records collection, and records payments.
- Cannot view another warehouse's operational data or change protected financial configuration.

### Public tracking visitor

- Does not log in.
- Opens an unguessable tracking URL or enters a valid tracking code.
- Sees only the matched shipment's safe tracking and payment summary.

## 3. Domain roles

### Billing customer

The account owner used for route-specific rates, credit balance, payments, and statements. The billing customer may also be the recipient.

### Recipient

The person who collects the shipment at the destination warehouse and normally pays. Recipient contact information is stored as a snapshot on the shipment so historical records remain correct if a customer's profile changes.

### Sender

Optional shipment contact information for the party delivering cargo in Dubai. Sender information does not control rates, balances, or credit in version 1.

## 4. Functional modules

### 4.1 Dashboard

Show operational facts without misleading percentage comparisons:

- Shipments awaiting batch assignment.
- Active batches and their current stage.
- Packages expected at each warehouse.
- Shipments ready for collection.
- Amount collected, amount outstanding, and credit balance totals.
- Recent shipment and payment activity.

### 4.2 Customers and recipients

- Create and manage billing customers.
- Store name, phone, optional company name, notes, and active status.
- Mark whether credit collection is allowed.
- View shipments, payments, allocations, and account statement.
- Warn before creating a duplicate customer with the same normalized phone number.
- On a shipment, default the recipient to the billing customer and allow a different recipient.

### 4.3 Routes and customer rates

- Administrators can create and deactivate routes without code changes.
- A route has an origin warehouse, destination warehouse, and optional transit warehouse.
- Initial routes are:
  - Dubai to Lebanon.
  - Dubai to Syria direct.
  - Dubai to Beirut to Syria.
- A customer may have a different USD-per-kilogram rate for each route.
- Rate changes affect future pricing only.

### 4.4 Shipments and packages

- Create a shipment with billing customer, recipient, optional sender, final destination, and notes.
- Add one or more packages.
- Generate a unique barcode for every package.
- Record exact decimal weight per package; do not round weight.
- Calculate shipment total weight from active packages.
- Print one large label or multiple labels on A4 paper.
- Display the barcode on a phone.
- Scan a barcode with the phone camera to open the package and parent shipment.
- Do not print price on the physical package label.

### 4.5 Shipment batches

- Create a batch with route, name, departure date, total cost per kilogram, and notes.
- Add eligible shipments to the batch.
- Enforce route and destination compatibility.
- Assigning a shipment to a batch selects the billable route and finalizes the initial charge.
- Show package count, shipment count, exact total weight, customer revenue, batch cost, collected amount, outstanding amount, and profit.
- Perform allowed batch-level status transitions while preserving package-level scan accuracy.

### 4.6 Warehouse operations

- Show expected incoming batches and packages.
- Scan each package on arrival.
- Mark missing or damaged exceptions without falsely marking the entire shipment complete.
- Mark a shipment ready for collection only after all active packages arrive at the destination warehouse.
- Version 1 does not permit partial pickup.

### 4.7 WhatsApp notification

- Prepare a one-click WhatsApp message; no paid WhatsApp API is required.
- The arrival message includes recipient name, arrival warehouse, shipment number, total due, remaining amount, and secure tracking link.
- The employee chooses to open/send the message; the system records that the message link was prepared or opened, not guaranteed delivery.

### 4.8 Public tracking

- No login is required.
- Access uses an unguessable token, not a sequential database ID.
- Searching by a valid package barcode opens its parent shipment.
- Display:
  - Shipment reference and safe recipient identification.
  - Route and current location/stage.
  - Timeline of status events.
  - Package count and arrival progress, such as `2 of 3 packages arrived`.
  - Exact total weight.
  - Total charge, paid amount, remaining amount, and payment status.
- Never display full customer account statements, other shipments, profit, batch cost, employee names, internal notes, or full private contact details.

### 4.9 Payments and credit

- Record full or partial payments.
- Supported methods: cash, Whish, bank transfer, and manually named other method.
- Store amount, date/time, collector, optional reference, and notes.
- Generate a unique receipt number.
- Allow multiple payments against one shipment.
- Allow account-level credit payments and allocate them oldest outstanding shipment first.
- Credit customers may collect without payment; the amount remains outstanding on the billing customer's account.
- Reverse mistakes with compensating records and an audit trail; never delete the original payment.

### 4.10 Statements and reports

- Customer statement: shipment charges, payments, reversals/adjustments, allocations, and running balance.
- Batch report: shipment/package counts, total weight, revenue, batch cost, paid, outstanding, and profit.
- Operational reports by warehouse, route, batch, shipment status, and date range.
- PDF/print output may be delivered after the core workflow if it does not delay version 1 operations.

### 4.11 Users and settings

- Roles are limited to administrator and warehouse employee in version 1.
- Employees are assigned to one warehouse.
- Settings include company identity, logo, warehouse records, route records, and barcode print options.
- Database backup and application updates are server operations, not ordinary dashboard actions.

## 5. Pricing summary

- USD only.
- Exact decimal weight; no minimum weight.
- Customer rate is per route.
- Charge before rounding = exact shipment weight multiplied by the shipment's rate snapshot.
- Customer final charge is set manually by the operator (D-020, supersedes D-008). No automatic rounding rule is applied.
- Batch cost retains cents and equals exact active package weight multiplied by the batch cost-per-kilogram snapshot.
- Profit equals rounded customer charges in the batch minus exact batch cost.

Detailed financial rules are canonical in `domain/pricing-payments.md`.

## 6. Status summary

Package, shipment, and batch statuses are separate. The system supports normal travel events plus cancelled, missing, and damaged exceptions. Detailed transition rules are canonical in `domain/shipment-lifecycle.md`.

## 7. Explicitly out of scope for version 1

- Local delivery coordination, address routing, delivery fees, or delivery profit.
- Driver, vehicle, map, or fleet management.
- Multiple currencies and exchange rates.
- Online card/payment-gateway collection.
- Paid database or required paid core service.
- Multi-tenant SaaS organizations.
- Carrier API integrations or carrier label purchasing.
- User-editable status/workflow engine.
- Fine-grained permission checklists.
- SMTP configuration and automated email delivery.
- Partial pickup when only some packages have arrived.

## 8. Non-functional requirements

- Arabic-first RTL interface, responsive from phone to desktop.
- Remote employee access through HTTPS.
- PostgreSQL remains private to the server.
- Backend authorization is mandatory for every protected action.
- Public tracking tokens are high-entropy and revocable.
- Money uses integer cents and weights use exact decimal storage.
- Financial and status updates are transactional and audited.
- Core workflows remain usable without paid external services.
- Server-managed backups must be restorable and periodically tested.

## 9. Version 1 acceptance criteria

- An administrator can create a customer and route-specific rates.
- A Dubai employee can create a multi-package shipment, generate barcodes, record exact weights, and print A4 labels.
- A shipment can be assigned to a compatible batch and priced with the correct customer route rate and rounding rule.
- Batch cost and profit are calculated from exact weights and preserved cents.
- A warehouse employee can scan every arriving package and the shipment becomes ready only when all active packages arrive.
- The recipient can open public tracking without login and see progress and payment summary without private account data.
- A warehouse employee can record full, partial, or credit collection using supported payment methods.
- Statements and batch reports reconcile charges, payments, allocations, reversals, outstanding amounts, cost, and profit.
- Warehouse access restrictions and privileged corrections are enforced and audited.
- No delivery fee, multi-currency, fleet, or paid-database behavior exists in version 1.

