# Opening a Shipment, and Telling the Two Doors Apart

**Date:** 2026-07-21
**Status:** Approved by owner

## The problem

A shipment cannot be opened. `ShipmentResource` has an index, a create page
and an edit page, and no view page. The list shows a package *count* — «الطرود:
٢» — and that is the only place packages appear anywhere in the product.

So there is no screen in the system that will tell you a package's barcode, its
individual weight, or where it has reached. A barcode is visible only on a
printed label. An operator asked "which of these three boxes hasn't arrived
yet?" has nowhere to look.

Batches have a view page. Shipments were built to the same pattern and the view
was left out — the same way six screens were left without visibility gates and
two navigation items were left sharing a sort value. A pattern applied by hand,
screen by screen, misses some.

## The second complaint, and why it is not what it looked like

Two screens create a shipment with packages: `الشحنات ← إضافة` and
`استلام بضاعة`. They ask nearly the same questions, and the operator opening
the menu cannot tell which one they want.

The obvious fix — delete one — is wrong. They serve two workflows the owner
actually has:

- Cargo arrives and Thursday's batch is already open: receive straight into it
  and it is priced on the spot.
- Cargo arrives before any batch exists: record it now, assign and price it
  later.

Both happen. The screens are not redundant; they are silent about which
situation they are for.

One inconsistency is real, though: `source_barcode` — the supplier's own
barcode on a box — can be recorded through the shipment form and not through
intake. The same cargo captures different data depending on which door it came
through.

## What gets built

**A view page for a shipment.** Its own details, and beneath them a table of
its packages: barcode, weight, description, and each package's own status. The
status matters per row — a shipment sitting at `partial_at_destination` is
telling you that some box hasn't arrived, and this is the screen that says
which.

**A sentence on each creation screen** naming the situation it is for:

- `الشحنات ← إضافة` — «شحنة غير مرتبطة برحلة بعد — تُسعَّر عند إسنادها لاحقاً»
- `استلام بضاعة` — «استلام مباشر في رحلة مفتوحة — يُسعَّر فوراً بسعر العميل»

**`source_barcode` on the intake form**, so both doors capture the same facts.

## Not in scope

- Removing either creation screen. Both workflows are real.
- A separate packages list. Packages belong to a shipment and are read there;
  a standalone list would be a fourth way to reach the same rows.
- Editing packages from the view page. Editing stays on the edit page.

## Testing

- A shipment's view page lists every one of its packages with barcode, weight
  and status — asserted on a shipment whose packages differ in status, so a
  view that renders one status for all rows fails.
- A cancelled package still appears, since the page is a record of what was
  received, not only of what is billable (D-023).
- The view page is reachable by anyone who can already reach the list, and no
  more. It must not become a way around a restriction — but see the note
  below: today there is no restriction to get around.
- `source_barcode` entered at intake reaches the package row.
- Both creation screens render their explanatory sentence.

## A restriction that does not exist yet

While writing this it turned out that `ShipmentResource` carries no warehouse
scoping and no capability gate of any kind: every employee sees every shipment
in the company, and this view page will inherit that.

That is deliberately **not** fixed here. Restricting who may see which
shipments changes what existing staff can do, and it belongs with the
permissions work the owner has deferred — not smuggled in behind a request to
show packages on a page.

Recorded so the gap is a known, dated decision rather than something a later
reader assumes was handled. It joins the same list as the ungated customer
rates screen and the ungated batch profit report.
