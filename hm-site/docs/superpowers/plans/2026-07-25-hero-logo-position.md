# Hero Logo Position Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Raise the opening-scene HM Cargo logo slightly without changing any other behavior.

**Architecture:** Keep GSAP's animated scene layer unchanged. Apply a responsive Tailwind translation only to the inner wrapper shared by the logo image and SVG overlay.

**Tech Stack:** React, TypeScript, Tailwind CSS, GSAP, Vite

## Global Constraints

- Do not change logo size.
- Do not change scene animation.
- Do not change copy or other layout elements.

---

### Task 1: Adjust the opening logo position

**Files:**
- Modify: `src/components/cinematic-journey.tsx:242`

**Interfaces:**
- Consumes: Existing `SceneOpening` wrapper and Tailwind utility classes.
- Produces: A responsive upward visual offset isolated from the GSAP scene layer.

- [ ] **Step 1: Capture the current rendered logo bounds**

Read the opening logo's rendered bounding rectangle at `http://localhost:8080/` and record its `top` coordinate.

- [ ] **Step 2: Add the minimal position adjustment**

Change the wrapper class from:

```tsx
className="relative"
```

to:

```tsx
className="relative -translate-y-[5vh] lg:-translate-y-[7vh]"
```

- [ ] **Step 3: Verify the rendered position**

Reload `http://localhost:8080/`, confirm the logo's `top` coordinate decreased, and confirm the image has a non-zero natural width.

- [ ] **Step 4: Verify the production build**

Run:

```bash
npm run build
```

Expected: exit code `0`.
