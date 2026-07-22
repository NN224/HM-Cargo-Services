# Visibility Gating and a Shared Locked-Page Mechanism

**Date:** 2026-07-22
**Status:** Approved by owner

## Where this sits

This is the first of three pieces that answer one question — *who sees what*:

1. **This piece.** Every money screen is gated by the capability that governs
   its content, and a shared locked-page mechanism is built. It closes real
   leaks that exist today.
2. A dashboard that adapts to who is looking (admin sees money, employee does
   not). Sits on this piece's gating.
3. Per-employee page locking — an administrator locking a specific page for a
   specific employee. Reuses this piece's locked-page mechanism.

They are built in this order because each rests on the one before.

## The leaks that exist today

- **The batch report** (`app/Filament/Resources/Batches/Pages/BatchReport.php`)
  has no guard of any kind. It shows revenue, cost and profit to every
  employee. The identical figures are correctly gated on the profitability
  widget — the report was simply missed.
- **A customer's rates** now render on the customer page (a section added last
  round) and are visible to anyone who can open a customer, including an
  employee with no pricing role. This contradicts D-024, which hides the rate
  per kilogram from anyone without the pricing capability.
- **Payments** are gated, but by *hiding* (`canViewAny()` returns false, so the
  resource vanishes from the navigation and a direct URL 403s). The owner wants
  a money screen the employee is not entitled to to appear locked, not to
  disappear — so they know it exists and is not theirs, and do not ask why it
  is missing.

## What gets built

### A shared locked-page mechanism

A single presentation an employee meets when they reach a page they may not
see: the page renders a notice — «هذه الصفحة مقفلة» — with a reason, instead
of its content. It is enforced in the backend: a hand-typed URL reaches the
same lock, not the real page.

The reason adapts to why the page is locked:

- **This piece:** «تحتاج صلاحية … لعرض هذه الصفحة.»
- **Piece 3:** «هذه الصفحة مقفلة من الإدارة.»

Same presentation, different reason. Piece 3 needs this mechanism and has no
alternative — its whole purpose is a page the employee sees as locked — so it
is built here, where the first user of it already needs it.

### The money screens, locked not hidden

| Screen | Locked when the user lacks | Shape |
|---|---|---|
| Payments (`PaymentResource`) | `RecordPayments` | full destination → locked page |
| Batch report (`BatchReport`) | `PriceShipments` | full destination → locked page |

Both keep their navigation entry / button. Opening either without the
capability shows the locked notice. The administrator, who implicitly holds
every capability, sees the real page.

### A customer's rates, hidden not locked

The rates section on the customer edit page is **hidden** when the user lacks
`ManageCustomers`, rather than shown locked. A lock box inside an otherwise
usable page is visual clutter, and the employee still needs the rest of the
customer page to receive cargo. The section simply is not rendered; the page
opens normally without it.

## An owner decision this records

The owner has decided that a warehouse employee sees **every** shipment, not
only those on routes touching their warehouse. This contradicts AGENTS.md
("warehouse employees see and operate only their assigned warehouse"), so it
is recorded as a decision (see below) rather than treated as a leak.
`ShipmentResource` is therefore left unscoped deliberately, and the shipment
"leak" identified earlier is withdrawn.

Note an asymmetry the owner has accepted: batches *are* scoped by warehouse
(an employee only works batches whose route touches their warehouse, enforced
in `BatchResource` and `BatchIntakeService`), while shipments are not. Viewing
a shipment and operating a batch are different acts; the owner wants the first
open and the second scoped.

## Not in scope

- Warehouse scoping on shipments. Explicitly declined by the owner.
- The dashboard (piece 2) and per-employee locking (piece 3).
- Any change to what a capability grants. The five capabilities and their
  meanings are unchanged; this piece only enforces them where they were not
  enforced.

## Testing

- The batch report is locked for an employee without `PriceShipments`: the
  page renders the locked notice and none of the figures (revenue, cost,
  profit) appear in the response — asserted on their absence, not on a hidden
  button.
- An administrator sees the real batch report, figures and all.
- Payments appear locked, not absent, for an employee without `RecordPayments`:
  the navigation entry is present and opening it shows the locked notice.
- An employee who holds `RecordPayments` reaches the real payments screen.
- The rates section does not render on the customer page for an employee
  without `ManageCustomers` — asserted on the rate figures being absent from
  the response — while the rest of the customer page still renders.
- An administrator, and an employee holding `ManageCustomers`, see the rates.
- The locked notice's reason names the capability required, and a
  reason-carrying parameter exists so piece 3 can supply its own wording.
