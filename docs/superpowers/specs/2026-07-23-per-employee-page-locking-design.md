# Per-Employee Page Locking

**Date:** 2026-07-23
**Status:** Approved by owner

## Where this sits

The third and last of the *who sees what* pieces. The first gated money by
capability and built a shared locked-page notice; the second built the
dashboard. This lets an administrator lock a specific page for a specific
employee — a page the employee would otherwise be allowed to open now shows as
locked.

It reuses the first piece's `LocksWhenUnauthorized` notice, so a locked page
looks the same whether the reason is a missing capability or an administrative
lock.

## The distinction from capabilities

A capability grants an *ability* — record a payment, price a shipment. A lock
removes *access to a page*, and only ever for one named employee. They are
different axes and must not be conflated:

- A lock blocks the page, not the ability. If an employee holds `RecordPayments`
  but the payments *page* is locked for them, they cannot open that page — but
  a "record payment" action reachable from elsewhere still works. To remove an
  ability, remove the capability; locking a page is not the tool for that.
- A lock overrides a capability *for that page*. Holding the capability does not
  reopen a page an administrator has locked.
- An administrator is never locked out of anything.

## What gets built

### A page registry — the single source of keys

One place defines every lockable destination and its stable key: `shipments`,
`batches`, `customers`, `customer-rates`, `payments`, `routes`, `warehouses`,
`users`, `receive-into-batch`, `scan-packages`. The dashboard is deliberately
absent — a home screen is not something to lock someone out of, and it already
adapts to show no money to an employee.

Every part of the feature reads keys from this registry — the lock toggles on
the employee screen, the stored locks, and the enforcement — so the three can
never drift out of step. This is the same failure the earlier per-screen gating
hit (a rule applied by hand missed screens); a single registry plus a test that
enumerates it is the guard.

### Storage

A new column on the user holds the list of locked page keys for that employee,
in the shape the existing `capabilities` column already uses (a JSON array of
strings, with unrecognised values ignored so a hand-edited row cannot invent a
lock).

### The employee screen

Below the existing capability toggles, a section «الصفحات المقفولة» with one
toggle per registry key. The administrator locks whatever they choose, per
employee. Shown only for employees — an administrator holds everything and is
never locked, so the section is irrelevant to them.

### Central enforcement

One middleware on the admin panel, running on every panel request. It resolves
the current route to a page key via the registry, and if that key is locked for
the current user (and the user is not an administrator), it sends them to a
single locked page that renders the `LocksWhenUnauthorized` notice with the
reason «هذه الصفحة مقفلة من الإدارة».

Central, not per-page, on purpose: the lesson from the earlier gating work is
that a rule wired screen by screen misses screens. One middleware covers every
current and future destination; a page added later is lockable the moment its
key is in the registry, with no new enforcement code.

The navigation entry for a locked page still shows — the employee sees it exists
and is marked locked, rather than finding it silently gone. Enforcement is on
access, not on the menu.

The trade-off accepted: opening a locked page redirects to the locked page
(the URL changes), rather than the destination's own URL rendering the notice
in place. This is the cost of central enforcement, and it is the right cost —
a consistent, unmissable lock over a per-page notice that some screen would
eventually be added without.

## Not in scope

- Locking the dashboard. Excluded by design.
- Removing a capability by locking a page. A lock is page access only; use the
  capability toggle to remove an ability.
- Locking for a whole role rather than a named employee. Locks are per-user,
  matching the capability model.
- Any change to what the five capabilities grant.

## Testing

- The registry lists exactly the lockable destinations, and the dashboard key
  is not among them — asserted by enumerating the registry.
- An employee with a page locked is redirected to the locked page when they
  open it, and the locked notice names the administrative reason — asserted on
  the real route, not a hidden nav item.
- The same employee reaches every page *not* locked for them normally.
- A lock overrides a held capability: an employee holding `RecordPayments`
  with the payments page locked cannot open the payments page, but the lock
  does not remove the capability — a payment action reachable elsewhere still
  authorises.
- An administrator is never redirected, for any key, even one toggled on
  (the toggle is inert for an administrator).
- The employee screen shows a toggle for every registry key and none for the
  dashboard; toggling one on stores that key, off removes it, and an
  unrecognised stored key is ignored.
- Every lockable route is actually covered by the middleware — a test walks
  the registry and confirms each key's route is enforced, so a destination
  cannot be lockable in the UI yet unenforced in the backend.
