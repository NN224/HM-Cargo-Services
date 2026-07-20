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

