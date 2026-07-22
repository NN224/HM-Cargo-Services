# A Dashboard That Adapts to Who Is Looking

**Date:** 2026-07-22
**Status:** Approved by owner

## Where this sits

The second of three pieces answering *who sees what*. It rests on the
capability gating built in the first piece: the dashboard shows money only to
someone entitled to see it, using the same `canView()`-per-widget mechanism
that already guards the profitability widget.

## The problem

The dashboard today is Filament's default: two library widgets — an account
greeting and a Filament advertisement (its documentation link and version).
The first screen an operator opens every morning shows a software vendor's
ad and nothing about the work. The reference system the client showed us
fills this screen with figures, but also with meaningless comparison
percentages («1000% ▲») the client's own spec rejected, and with six-month
charts that need six months of data to mean anything.

## The principle

Show what needs acting on today, and nothing that only misleads. Every number
on the dashboard should point at something to do — money to collect, cargo to
release, shipments to price. No comparison percentages. No charts.

And the dashboard adapts to who is looking: money appears only for someone
who may see it, exactly as the money screens are gated.

## What gets built

Four small widgets, each self-gating by `canView()`, plus the removal of the
two default library widgets.

### For everyone (administrator and employee)

**Awaiting a batch.** A count: shipments that have packages but no active
batch — cargo received and waiting to be priced onto a load. Points at the
intake-to-batch step.

**Ready for collection.** A count: shipments where every active package has
arrived at destination and the shipment is `ready_for_collection`. Points at
the recipient to notify and collect from.

**Recent shipments.** A short table of the latest shipments — reference,
customer, recipient, current status — so the operator sees today's activity
without opening the full list. No money columns.

### For a money-holder only (administrator, or an employee with `RecordPayments`)

**Outstanding.** A single figure: the total still owed across all customers —
what there is to collect. Gated by `canView()` on `RecordPayments`; an
administrator holds it implicitly.

**Customers in debt.** A short table of customers carrying an outstanding
balance, largest first, with their phone — the follow-up-for-payment list.
Same gate.

## Why these five and not the reference set

The reference dashboard's revenue and collected totals are history, readable
in the batch report and the customer statement. Its comparison percentages
are the misleading kind the spec forbids. Its charts need a data history the
system does not yet have and add configurability without adding a decision.
What survives is the two numbers that tell an operator to *do* something —
collect, and release — and the two lists behind them.

## The mechanism

Each widget is a Filament widget with a `canView(): bool` static method:

- The two operational widgets (awaiting-batch, ready-for-collection) and
  recent-shipments return `true` for any authenticated user.
- The outstanding figure and customers-in-debt table return
  `true` only for a user holding `RecordPayments` (an administrator holds
  every capability, so this includes them).

This is the pattern `BatchProfitabilityWidget::canView()` already uses. A
widget hidden by `canView()` is not rendered and its query does not run, so
the gate is enforced server-side, not by hiding a rendered value.

The counts and totals are read through the existing services and models — no
new service, no new query layer, no schema change. Weight stays in SQL, money
stays integer cents shown as dollars at the display boundary.

## Not in scope

- Comparison percentages and trend charts. Declined.
- Revenue and collected-total cards. History, available elsewhere.
- Warehouse-scoping the operational counts. Per D-025 an employee sees every
  shipment, so the counts are company-wide for everyone; there is no
  per-warehouse dashboard in this piece.
- Per-employee page locking (piece 3).

## Testing

- The outstanding figure and the customers-in-debt table are hidden from an
  employee without `RecordPayments` — asserted on `canView()` returning false
  and on the figure being absent from the rendered dashboard, not on a hidden
  element.
- Both are shown to an administrator and to an employee holding
  `RecordPayments`.
- The operational counts (awaiting-batch, ready-for-collection) show for a
  capability-less employee, and each counts the right shipments: a test
  creates shipments in and out of the counted state and asserts the number.
- The recent-shipments table carries no money column — asserted on the
  absence of any charge/total value for a shipment that has one.
- The outstanding figure equals the sum the customer statements reconcile to,
  so the dashboard and the statements cannot disagree.
- The two default Filament widgets (account, info) no longer render.
