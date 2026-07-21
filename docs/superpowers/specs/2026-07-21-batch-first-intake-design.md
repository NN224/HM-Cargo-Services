# Batch-First Intake

**Date:** 2026-07-21
**Status:** Approved by owner

## The problem

Cargo does not arrive all at once. The operator knows on Sunday that a load
leaves for Syria on Thursday, and customers drop goods off across the days
between. The natural way to work is to open Thursday's load and add to it as
things arrive.

The system currently requires the opposite order. To record a customer's
boxes you create a shipment — choosing customer, recipient, destination — and
then go to another screen to attach it to a batch. Nothing is wrong with the
result, but the operator is made to assemble records when all they did was
receive four boxes from a regular customer.

The client left their previous system because it made them feel like that.

## What already works

Nothing in the data model needs to change.

A batch is the travelling load. It already carries a route and a departure
date and can be created empty, days ahead. It already holds shipments
belonging to many different customers, each priced at its own agreed rate.
Twenty packages for fifteen customers is one batch holding fifteen shipments.

What is missing is a way in. This design adds one screen and changes no
rules.

## The flow

From an open batch, an **استلام بضاعة** action opens a form:

- **Customer** — searchable select. A user holding `ManageCustomers` may
  create one inline without leaving the form.
- **المستلم هو العميل** — a checkbox, ticked by default. Unticking reveals
  recipient name and phone. Both cases occur in practice and neither should
  slow the other down.
- **Packages** — a repeater, one row per box: weight, optional description.

On save, in one transaction, the system:

1. Creates the shipment for that customer.
2. Sets its destination from the batch's route, so the operator never types
   a destination that must then match the route.
3. Assigns it to the batch.
4. Snapshots the customer's agreed rate for that route and computes the
   charge, through the existing `BatchAssignmentService`.
5. Generates a barcode per package.

The operator entered a customer and some weights. Everything else was already
known.

## Who sees money

The employee receiving goods sees the customer, the recipient, the weights
and the barcodes. They do not see the rate per kilogram, the shipment total,
or anything derived from them. Those fields render only for a user holding
`PriceShipments`.

This refines what that capability means. Applying a rate that was agreed
before the goods moved is not a pricing decision — the number already exists
and the system only multiplies it by a weight. The capability gates *setting
and changing* a customer's rate, not the arithmetic of applying it. Reading
it the other way would mean an employee without it could not receive cargo
at all, which is the whole purpose of the screen.

Changing a rate remains where it is: the customer rates screen, an
administrator's job.

## When there is no agreed rate

No package moves before its price is agreed with its owner, so a customer
without a rate for this route is a customer nobody has settled terms with
yet. The system must not invent one — `pricing-payments.md` §2 forbids
falling back to zero or a default, because that bills a real person an amount
nobody agreed to.

Two outcomes, decided by the receiving user's own capabilities:

- **Holding `ManageCustomers`** — a rate field appears in the same form. They
  record what was agreed and carry on without leaving the screen.
- **Not holding it** — the form refuses in Arabic, naming what is missing and
  who can supply it. Nothing is saved.

There is no third state. A shipment is never parked unpriced waiting for
someone to notice it.

## Guarding a mistyped rate

A rate is a standing agreement, so a typo does not spoil one load — it
becomes the customer's price until somebody catches it.

The inline field above only ever records a *first* rate for a customer who
had none, so there is nothing there to overwrite. The risk lives on the rates
screen, where an existing figure is replaced. Changing one there raises a
confirmation naming both:

> سعر أحمد على سوريا كان ٣٫٠٠ — بدك تخليه ٣٠٫٠٠؟

One click. Enough to catch a slipped decimal, not enough to be ceremony.

## Not in scope

- No change to the data model, to pricing rules, or to how batches, shipments
  and packages relate.
- No per-shipment manual rates. The rate belongs to the customer and the
  route, exactly as before.
- The existing shipment-first creation screen stays. This adds a second door,
  it does not replace the first.

## Testing

- A batch-first intake creates a shipment, prices it from the customer's
  rate, assigns it, and generates one barcode per package — in one
  transaction, with a failure leaving nothing behind.
- Destination is taken from the batch route and matches it, so the existing
  destination invariant cannot be violated through this door.
- Recipient defaults to the customer when the box is ticked, and takes the
  typed values when it is not.
- A user without `PriceShipments` is not shown the rate or the total; the
  assertion is on their absence from the response, not on the template.
- A customer with no rate: accepted with an inline rate by a user holding
  `ManageCustomers`, refused with a clear message otherwise, and in the
  refusal nothing is written.
- Changing an existing rate on the rates screen requires the confirmation,
  and the previous rate is preserved with its effective date.
