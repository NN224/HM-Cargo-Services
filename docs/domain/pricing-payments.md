# Pricing, Payments, Credit, and Profit Rules

This document is the canonical source for all financial calculations.

## 1. Currency and numeric storage

- USD is the only billing and reporting currency.
- Store money as integer cents.
- Store weight as an exact decimal; never use binary floating point.
- There is no minimum billable weight.
- Do not round package or shipment weight.

## 2. Customer route rates

- The billing customer owns rate agreements and credit balance.
- A rate is specific to a configured route.
- Dubai-to-Syria-direct and Dubai-to-Beirut-to-Syria are different rates.
- Rates have an effective start date and active status.
- Assigning a shipment to a batch snapshots the selected USD-per-kilogram rate onto the shipment.
- Later rate changes do not alter existing shipment snapshots.
- If no active customer rate exists for the batch route, assignment must fail with a clear validation error; agents must not silently use zero or a global fallback.

## 3. Customer charge calculation

Let:

- `W` = sum of exact weights for active packages. Active means everything except cancelled: a missing or damaged package still counts toward `W` and does not reduce the charge by itself (D-023). Its financial resolution is an explicit administrator adjustment.
- `R` = shipment rate snapshot in USD per kilogram.
- `raw_charge` = `W × R`.

> **SUPERSEDED by D-020 (2026-07-20).** The automatic rule described in this section is no longer implemented. The operator types the final amount the customer pays; the computed charge is preserved and the adjustment is derived. Weight is still never rounded, and batch cost and profit still keep their cents. The text below is retained as a record of the original rule.

Apply custom rounding only to the final customer charge:

- Exact whole-dollar values stay unchanged.
- Fraction `.01` through `.29` rounds down to the current whole dollar.
- Fraction `.30` through `.99` rounds up to the next whole dollar.

Examples:

| Raw charge | Final customer charge |
|---:|---:|
| $92.00 | $92.00 |
| $92.10 | $92.00 |
| $92.20 | $92.00 |
| $92.29 | $92.00 |
| $92.30 | $93.00 |
| $92.40 | $93.00 |
| $92.50 | $93.00 |
| $92.99 | $93.00 |

Implementation must use decimal/integer arithmetic and explicit tests at `.00`, `.01`, `.29`, `.30`, and `.99` boundaries.

## 4. Repricing and historical integrity

- The shipment stores exact weight, route, rate snapshot, raw charge, rounded final charge, and pricing timestamp.
- Before dispatch, authorized edits may recalculate the charge and replace the unposted draft price.
- After dispatch, only an administrator may change weight or route.
- Post-dispatch repricing creates an adjustment entry for the difference. It does not rewrite posted ledger history.
- If the new amount exceeds payments, the difference becomes outstanding.
- If payments exceed the new amount, the excess becomes customer credit available for allocation or refund resolution.

## 5. Batch cost and profit

Let:

- `BW` = sum of exact active package weights included in the batch.
- `CR` = batch cost-per-kilogram snapshot for the full route.
- `batch_cost` = `BW × CR`.
- `batch_revenue` = sum of rounded final customer charges for active, non-cancelled shipments.
- `batch_profit` = `batch_revenue - batch_cost`.

Rules:

- The batch has one cost-per-kilogram for the full route, including routes with transit.
- Preserve cents for batch cost and profit.
- Do not apply the custom customer rounding rule to batch cost or profit.
- A missing batch cost rate is not zero. Profit displays as unavailable until a cost rate is entered.
- Cancelled or financially reversed shipments are included or excluded according to their posted adjustments, not by deleting history.

## 6. Payment states

- `unpaid`: paid amount is zero and outstanding is positive.
- `partially_paid`: paid amount is greater than zero and less than the charge.
- `paid`: allocated payment equals the effective charge.
- `credit`: shipment was collected while some or all charge remains on the customer account.
- `overpaid`: allocations exceed the effective charge due to a correction or excess payment; excess becomes account credit.

Public tracking may show safe Arabic labels for these states.

## 7. Payment recording

Each payment records:

- Billing customer.
- Amount in USD cents.
- Method: cash, Whish, bank transfer, or `other` with a required custom name.
- Date/time.
- Collecting user and warehouse.
- Optional external reference and notes.
- Unique receipt number.
- Audit timestamps.

Rules:

- Full and partial payments are allowed.
- Multiple payments may be allocated to one shipment.
- A payment may be entered from a specific shipment or from the customer's account.
- A shipment-specific payment allocates to that shipment first; any deliberate excess requires administrator confirmation before becoming account credit.
- An account-level payment allocates to the oldest outstanding shipment charges first, ordered by charge posting time and stable ID.
- Payment creation and allocation occur in one database transaction.

## 8. Credit collection

- A customer may be marked credit-eligible.
- A credit-eligible recipient may collect all packages without immediate payment.
- Collection does not mark the financial balance paid.
- Outstanding charge remains on the billing customer's statement.
- Later account payments allocate oldest outstanding first.
- Credit eligibility is not a currency, payment method, or delivery option.
- Version 1 does not enforce a numeric credit limit.

## 9. Reversals and corrections

- Never hard-delete a payment, allocation, charge, or adjustment.
- Reversing a payment creates a compensating reversal linked to the original payment.
- Reversal requires administrator permission and a reason.
- The receipt and statement show the reversal without pretending the original action never occurred.
- Reallocation after reversal is transactional and deterministic.

## 10. Customer statement

The statement is ordered chronologically and includes:

- Shipment charge postings.
- Repricing adjustments.
- Payments.
- Payment reversals.
- Allocations and released allocations.
- Running customer balance.

The statement must reconcile exactly with shipment outstanding totals and unapplied customer credit.

## 11. Public payment summary

Public tracking may show only:

- Final charge.
- Total allocated paid amount.
- Remaining amount.
- Payment status.

It must not show payment references, collector names, internal notes, other shipment balances, full customer statements, batch cost, or profit.

## 12. No delivery finance

Do not create delivery fees, delivery costs, driver payments, or delivery profit. Customers may arrange local delivery with Beirut or another party outside HM Cargo Services; it has no effect on this ledger.

