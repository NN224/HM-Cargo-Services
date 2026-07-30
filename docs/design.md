# HM Cargo Services - Design & UI/UX Specification

**Status:** Draft  
**Primary language:** Arabic (RTL)  
**Target Devices:** Mobile-first (phones for warehouse ops, desktop for admin)

## 1. Design Philosophy & Aesthetics
The HM Cargo Services administration panel and public tracking page must deliver a **premium, rich, and dynamic user experience** while remaining focused on operational efficiency. It should feel state-of-the-art and distance itself from generic, utilitarian ERP systems.

### 1.1 Core Principles
- **Clarity Over Clutter:** The UI opens directly on actionable work (e.g., pending shipments, pending batches).
- **Glassmorphism & Depth:** Use subtle translucent backgrounds, soft shadows, and layered elevation to give depth to the interface, especially for modal dialogs, status overlays, and tracking cards.
- **Dynamic Interactions:** Hover states, micro-animations on buttons, smooth transitions on row expansion, and skeleton loading screens.
- **RTL Native:** The entire layout, typography, and spacing must be designed right-to-left first.

## 2. Color Palette
The color scheme should convey trust, speed, and premium service. Avoid generic default browser or framework colors. Use harmonious HSL-tailored colors.

- **Primary Brand Color (Cargo Blue):** `#1D4ED8` (Deep, vibrant blue for primary actions and brand identity).
- **Secondary/Accent (Express Amber):** `#F59E0B` (For highlights, alerts, and pending states).
- **Background (Light Mode):** `#F8FAFC` (Sleek slate-tinted off-white to reduce eye strain).
- **Background (Dark Mode):** `#0F172A` (Deep slate for a modern, high-contrast dark mode).
- **Success (Delivered/Paid):** `#10B981` (Vibrant emerald).
- **Danger (Missing/Damaged/Unpaid):** `#EF4444` (Vibrant rose/red).
- **Surface/Card:** `#FFFFFF` (Light) / `#1E293B` (Dark) with subtle 1px borders (`#E2E8F0` / `#334155`).

## 3. Typography
Modern, highly legible typography that supports Arabic and Latin scripts beautifully.

- **Primary Font:** `Inter` (for English/Latin characters, numbers, and tracking codes).
- **Arabic Font:** `Tajawal` or `Cairo` (Google Fonts) for native, modern Arabic rendering.
- **Hierarchy:**
  - **H1 (Page Titles):** 24px/32px, Semi-Bold, tight tracking.
  - **H2 (Card Headers):** 18px, Medium.
  - **Body:** 14px, Regular (optimised for data density on mobile).
  - **Monospace (Tracking/Barcodes):** 13px, `JetBrains Mono` or `Fira Code`.

## 4. Key Screens & Components

### 4.1 Dashboard (The Operations Center)
- **Top Metrics Cards:** Glassmorphic cards displaying "Cargo Awaiting Batch", "Ready to Release", and financial totals (for money-holders).
- **Micro-animation:** Numbers counting up on load.
- **Recent Shipments List:** A clean, borderless table or list view with status pills.

### 4.2 Shipment & Package Scanning (Mobile View)
- **Camera Viewport:** Full-width or large prominent scanning area at the top.
- **Success Feedback:** Haptic feedback (if possible via web API) and a bright green flash/toast when a barcode is successfully registered.
- **Bottom Sheet:** Swipe-up bottom sheet for package details and exception marking (missing/damaged).

### 4.3 Public Tracking Page (`/track/{token}`)
- **Vibe:** Consumer-facing, ultra-premium, no login required.
- **Hero Section:** Large, centered shipment status (e.g., "In Transit to Syria") with a dynamic, glowing background gradient reflecting the status.
- **Journey Progress Bar:** A vertically (mobile) or horizontally (desktop) oriented stepper with 7 distinct milestones.
  - Completed steps: Solid Primary color with a checkmark icon.
  - Current step: Pulsing animation or glowing ring.
  - Future steps: Muted/greyed out.
- **Data Display:** Exact weight, package count ("2 of 3 arrived"), and payment summary in visually distinct, rounded cards.

### 4.4 Filament Theme Integration
- Compile a custom Filament theme (`index.css` via Vite) implementing the color tokens and typography.
- Override default Filament generic components to add soft rounding (`rounded-xl` or `rounded-2xl` for cards/modals).
- Remove heavy borders in favor of subtle drop shadows (`shadow-sm` on tables, `shadow-lg` on modals).

## 5. SEO & Accessibility (Public Tracking)
- **Title Tags:** `[Shipment Reference] - HM Cargo Tracking`
- **Meta Descriptions:** "Track your HM Cargo shipment status and journey."
- **Contrast:** Ensure all text-to-background contrast ratios meet WCAG AA standards (especially for the amber and grey text).
- **Semantics:** Proper use of `<header>`, `<main>`, `<section>`, and `<article>`.

## 6. CSS Framework
- **TailwindCSS:** Used natively within the Laravel Filament ecosystem.
- Custom Tailwind config to include `Tajawal`/`Inter` fonts, specific brand hex codes, and custom animation utilities (e.g., `animate-pulse-slow`, `animate-slide-up`).
