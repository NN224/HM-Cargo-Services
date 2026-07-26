# Package Journey Progress Design

**Date:** 2026-07-25
**Status:** Owner-approved design; not yet implemented

## 1. Goal

Replace the repeated shipment-group heading on the shipments screen with a
clear, real operational progress bar inside every shipment card. The bar is
not decorative: every completed step represents a persisted package event.
Staff can advance all eligible packages in a shipment or select particular
packages when they travel separately.

The same safe progress appears on public tracking without exposing employees,
internal notes, audit details, or other shipments.

## 2. Fixed journey template with dynamic locations

Version 1 uses one fixed seven-step journey template:

1. Arrived at the origin warehouse.
2. Arrived at the origin airport.
3. Departed the origin airport.
4. Arrived at the destination airport.
5. Departed the destination airport.
6. Arrived at the delivery office.
7. Collected by the recipient.

The workflow order is fixed in code. Administrators do not create, remove, or
reorder steps. This preserves the approved exclusion of a user-editable
workflow engine.

Labels are dynamic:

- The origin warehouse comes from the route's existing origin warehouse.
- The destination warehouse continues to represent the final HM Cargo
  destination.
- Each route stores an origin airport name, destination airport name, and
  delivery office name.

These three route fields are required for active routes. Existing routes must
be backfilled before the new progress actions are enabled.

## 3. Package state is the source of truth

Every package holds its current operational state. Normal states follow the
seven steps in order. The existing exception states remain:

- `cancelled`
- `missing`
- `damaged`

Missing, damaged, or cancelled packages are never silently advanced by a bulk
operation. Existing collection rules remain:

- All arrived packages may be collected together.
- Some arrived packages may be collected while others remain in transit
  (D-029).
- The shipment becomes fully collected only when all active packages are
  collected.

Every progress change appends a package status event containing:

- package and shipment context;
- previous and new state;
- actor and warehouse context;
- event timestamp;
- event kind: progress, delay, or administrator correction;
- private reason where supplied;
- public reason only when the actor explicitly chooses to publish it.

Events are append-only. Corrections never rewrite or delete earlier events.

## 4. Shipment aggregate state

Shipment state is derived from its active package rows after every package
operation.

- When all active packages share one journey step, the shipment reflects that
  step.
- When packages are split between steps, the shipment shows partial progress
  with completed and remaining counts.
- When at least one package is collected and another active package is not,
  the shipment is `partially_collected`.
- When every active package is collected, the shipment is `collected`.
- A missing or damaged active package keeps the shipment in `exception` until
  the exception is resolved.

Batch state remains an independent state machine. Advancing packages does not
silently advance or close a batch.

## 5. Staff progress actions

The progress bar is rendered inside each shipment card. Shipment cards remain
visually separated, but the repeated batch/group label is removed.

An ordinary warehouse employee may:

- advance eligible packages forward by exactly one step;
- choose all eligible packages or select specific packages;
- mark all or selected packages as delayed at their current step;
- write a delay reason;
- choose whether the delay reason is public or internal.

An ordinary employee cannot skip a step, move a package backwards, advance a
package in an exception state, or operate outside the employee's authorized
warehouse context.

The server enforces these rules. Hiding or disabling a UI control is not an
authorization boundary.

## 6. Delay behavior

Delay is an overlay on the current journey step, not a separate journey step.
A delayed package retains its operational state and receives:

- a current delayed flag;
- the delay event timestamp;
- a private reason;
- an optional public reason.

The next successful forward transition clears the current delayed flag and
current delay details. The append-only delay event remains in history.

When only some packages are delayed, the shipment card and public tracking
show the affected package count. Public tracking shows a reason only when it
was explicitly marked public.

## 7. Administrator corrections

An administrator may move selected packages forward or backward to any valid
journey step. Every correction requires a non-empty reason.

For each correction, the administrator chooses whether its reason:

- appears to the customer on public tracking; or
- remains an internal audit detail.

The correction executes through the same transactional domain service as
ordinary progress, with an explicit privileged-correction mode. The service
records the previous and corrected states and an audit log entry.

Corrections cannot erase financial facts, payment history, collection events,
or exception history.

## 8. Transaction and validation rules

A multi-package operation is atomic:

1. Authorize the actor against the shipment and relevant route/warehouse.
2. Lock the shipment and selected package rows.
3. Re-read current package states.
4. Validate every selected package.
5. Reject the complete operation if any selected package is ineligible.
6. Update all selected packages.
7. Append package events and required audit records.
8. Recalculate the shipment aggregate state.
9. Commit.

No selected package changes when validation fails. The Arabic error identifies
the ineligible package and reason without exposing private data.

## 9. Public tracking

Public tracking renders a read-only version of the seven-step bar with dynamic
route labels.

It may show:

- completed, current, delayed, and upcoming steps;
- per-package progress;
- completed and remaining package counts;
- a currently published delay reason;
- published administrator correction reasons.

It never shows:

- employee identity;
- internal reasons or notes;
- audit metadata;
- batch cost or profit;
- other shipments or customer-account history.

The public controller continues to pass a narrow, explicit safe projection to
the view. Full Eloquent models are not passed to public Blade templates.

## 10. UI behavior

### Administration shipment card

- Keep a distinct visual boundary around every shipment.
- Remove duplicated headings such as `الرحلة: الرحلة: test 1`.
- Show the batch reference once as secondary context.
- Render the seven steps horizontally on desktop and as a compact scrollable
  or wrapped sequence on phones.
- Completed steps use a success treatment.
- The current step is prominent.
- Delayed steps use a warning treatment.
- Upcoming steps are muted.
- A split shipment shows package counts at the relevant steps.

Clicking an allowed step opens one modal that contains package selection,
delay/correction reason fields when applicable, and the public-visibility
choice.

### Public tracking

The same semantic progress is rendered read-only with customer-safe wording
and no operational controls.

## 11. Migration and compatibility

The existing operational states map to the new journey as follows:

| Existing state | Journey meaning |
| --- | --- |
| `received_origin` | Arrived at origin warehouse |
| new state | Arrived at origin airport |
| `in_transit` | Departed origin airport |
| `arrived_transit` | Arrived at destination airport |
| `departed_transit` | Departed destination airport |
| `arrived_destination` | Arrived at delivery office |
| `collected` | Collected by recipient |

Existing events remain valid. The implementation adds the missing origin
airport state and enriches event metadata without deleting history.

Routes without the three new labels remain readable but cannot use the new
progress actions until an administrator completes their configuration.

## 12. Testing requirements

Tests are written before implementation and cover:

- the seven forward transitions;
- dynamic route labels;
- advancing all eligible packages;
- advancing selected packages only;
- atomic refusal when one selected package is ineligible;
- employee prevention from skipping or moving backward;
- warehouse authorization boundaries;
- administrator forward and backward correction with a mandatory reason;
- public and private correction reasons;
- delay creation, selective delay, and automatic clearing on progress;
- public and private delay reasons;
- split-package shipment aggregation;
- partial and complete collection compatibility;
- missing, damaged, and cancelled package protection;
- safe public tracking projection and explicit absence of every forbidden
  field;
- Livewire action visibility and Arabic validation;
- responsive rendered progress on desktop and phone.

## 13. Explicit non-goals

- No user-editable workflow/status engine.
- No automatic GPS, airport, carrier, or flight integration.
- No driver, vehicle, map, or last-mile delivery management.
- No automatic package progress inferred from batch status.
- No paid messaging or tracking dependency.
