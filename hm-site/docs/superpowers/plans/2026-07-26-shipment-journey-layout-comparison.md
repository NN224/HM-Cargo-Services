# Shipment Journey Layout Comparison Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build one standalone interactive HTML file that compares four RTL dark-mode layouts for managing HM Cargo journeys and their shipments.

**Architecture:** A single dependency-free HTML document contains semantic markup, scoped CSS, fixed demo data, and local JavaScript state. One shared renderer feeds all four layout variants so journey selection, shipment selection, search, and bulk-action feedback remain consistent while only composition changes.

**Tech Stack:** HTML5, CSS custom properties and responsive grid, vanilla JavaScript, Chrome visual QA.

## Global Constraints

- The preview is standalone and must not modify or connect to the Laravel/Filament application.
- The interface is Arabic RTL and visually consistent with the existing dark HM Cargo shipments page.
- All four layouts use the same demo journeys and shipments for a fair comparison.
- Only active journeys appear; journey progress represents the furthest-behind shipment.
- Bulk actions apply to selected shipments, while individual shipment actions remain available.
- The `correct` operation is visibly identified as admin-only and absent from the normal-user controls.
- At narrow widths, journey navigation appears before the shipment workspace.
- No external libraries, fonts, images, or network requests.

---

### Task 1: Standalone comparison shell and shared state

**Files:**

- Create: `design-previews/shipment-journey-layouts.html`

**Interfaces:**

- Consumes: The approved specification in `docs/superpowers/specs/2026-07-26-shipment-journey-layout-comparison-design.md`.
- Produces: `render(): void`, `setLayout(layoutId: string): void`, `selectJourney(journeyId: string): void`, and shared `state` containing `layout`, `journeyId`, `query`, and `selectedShipmentIds`.

- [ ] **Step 1: Create semantic HTML and fixed demo data**

Add a comparison header with four buttons whose `data-layout` values are
`cards`, `table`, `rail`, and `command`. Define three demo shipments split
between `test-1` and `test-2`, plus a derived `all` selection:

```js
const state = {
    layout: "cards",
    journeyId: "all",
    query: "",
    selectedShipmentIds: new Set(),
    role: "operator",
};

const journeys = [
    { id: "test-2", name: "الرحلة: test 2", route: "Dubai ← Lebanon", status: "قيد التجهيز" },
    { id: "test-1", name: "الرحلة: test 1", route: "Dubai ← Lebanon", status: "قيد التجهيز" },
];
```

- [ ] **Step 2: Add the visual system**

Use CSS variables for charcoal surfaces, muted text, blue selection, orange
actions, red payment alerts, 12px radii, and a compact spacing rhythm. Add
responsive rules at `900px` and `640px` so split layouts stack without
horizontal page overflow.

- [ ] **Step 3: Implement the shared state transitions**

```js
function setLayout(layoutId) {
    state.layout = layoutId;
    render();
}

function selectJourney(journeyId) {
    state.journeyId = journeyId;
    state.selectedShipmentIds.clear();
    render();
}
```

Add delegated event handling for layout buttons, journey cards, search,
checkboxes, select-all, and preview-only status menus. Every state change calls
`render()` and updates the comparison description and selection count.

- [ ] **Step 4: Run structural validation**

Run:

```bash
node -e "const fs=require('fs');const s=fs.readFileSync('design-previews/shipment-journey-layouts.html','utf8');for(const x of ['data-layout=\"cards\"','data-layout=\"table\"','data-layout=\"rail\"','data-layout=\"command\"','function render()'])if(!s.includes(x))throw Error(x)"
```

Expected: exit code `0`.

- [ ] **Step 5: Commit**

```bash
git add design-previews/shipment-journey-layouts.html
git commit -m "feat: add shipment journey layout comparison"
```

### Task 2: Four layout renderers and operational details

**Files:**

- Modify: `design-previews/shipment-journey-layouts.html`

**Interfaces:**

- Consumes: Shared `state`, `journeys`, `shipments`, `filteredShipments()`, and `journeySummary()`.
- Produces: `renderCardsLayout()`, `renderTableLayout()`, `renderRailLayout()`, and `renderCommandLayout()`, each returning an HTML string.

- [ ] **Step 1: Implement the recommended split-card renderer**

Render a 30/70 split with compact journey cards on the side and detailed
shipment cards in the workspace. Journey cards include route, shipment count,
weight, outstanding amount, and the stage label derived from the slowest
shipment.

- [ ] **Step 2: Implement the dense table renderer**

Render the same journey navigation as a compact list and expose each shipment
as one table row with selection, number, customer, recipient, stage, weight,
payment, and individual action controls.

- [ ] **Step 3: Implement the horizontal rail renderer**

Render equal-height journey selector cards across the top, followed by a
separate search/filter toolbar and a responsive shipment-card grid.

- [ ] **Step 4: Implement the command-center renderer**

Render a narrow journey column, a KPI summary for the selected journey,
prominent selected-count feedback, and compact shipment rows optimized for bulk
status work.

- [ ] **Step 5: Add empty and bulk-action states**

When search yields no results, render:

```html
<div class="empty-state">
    <strong>لا توجد شحنات مطابقة</strong>
    <span>جرّب تغيير البحث أو اختيار رحلة أخرى.</span>
</div>
```

Show the bulk toolbar only when at least one shipment is selected. The normal
operator status menu must not contain `correct`; an adjacent note explains
that correction is available to administrators only.

- [ ] **Step 6: Run content validation**

Run:

```bash
node -e "const fs=require('fs');const s=fs.readFileSync('design-previews/shipment-journey-layouts.html','utf8');for(const x of ['renderCardsLayout','renderTableLayout','renderRailLayout','renderCommandLayout','أبطأ شحنة','خاص بالمدير'])if(!s.includes(x))throw Error(x)"
```

Expected: exit code `0`.

- [ ] **Step 7: Commit**

```bash
git add design-previews/shipment-journey-layouts.html
git commit -m "feat: complete journey management design variants"
```

### Task 3: Browser verification and handoff

**Files:**

- Modify only if defects are found: `design-previews/shipment-journey-layouts.html`

**Interfaces:**

- Consumes: The finished standalone HTML file.
- Produces: A visually verified local preview kept open for user comparison.

- [ ] **Step 1: Serve the preview locally**

Run:

```bash
python3 -m http.server 4177 --directory design-previews
```

Open `http://127.0.0.1:4177/shipment-journey-layouts.html`.

- [ ] **Step 2: Verify desktop interactions**

Confirm all four layout buttons switch the visible composition, selecting
`test 1` shows two shipments, selecting `test 2` shows one shipment, search
filters the selected journey, and selection exposes the bulk toolbar.

- [ ] **Step 3: Verify responsive behavior**

At widths near `900px` and `640px`, confirm the journey area precedes shipment
content, controls remain readable, and the document has no unintended
horizontal page overflow.

- [ ] **Step 4: Fix and re-check any visible defects**

For each defect, edit only the standalone preview, reload the page, and repeat
the exact failed interaction or viewport check.

- [ ] **Step 5: Final validation**

Run:

```bash
git diff --check
git status --short
```

Expected: no whitespace errors; only intended preview changes remain.
