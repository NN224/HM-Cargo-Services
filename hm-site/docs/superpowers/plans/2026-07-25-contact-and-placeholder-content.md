# HM Cargo Contact and Placeholder Content Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace visible placeholder content with confirmed contact details and accurate company and coverage copy.

**Architecture:** Keep the existing TanStack Start components and page structure. Make focused content-only edits to the shared footer, Contact, About, Coverage, and home-section components without introducing new dependencies or changing routing, animation, or the quote flow.

**Tech Stack:** React, TypeScript, TanStack Start, Tailwind CSS, Vite

## Global Constraints

- General enquiries use `info@hmcargoservices.com`.
- Quote requests use `quotes@hmcargoservices.com`.
- The address is `Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE`.
- Existing Lebanon and UAE telephone numbers remain unchanged.
- UAE, Lebanon, and Syria are the confirmed primary markets.
- International destinations, including the United States, are available on request.
- Do not claim certifications, company figures, client names, permanent foreign offices, or permanent global infrastructure.
- Keep existing layout, animation, navigation, blog states, and quote flow unchanged.
- The project directory is not a Git repository, so commit steps are not applicable.

---

### Task 1: Contact details

**Files:**
- Modify: `src/components/site-footer.tsx`
- Modify: `src/routes/contact.tsx`

**Interfaces:**
- Consumes: Existing `SiteFooter` and `ContactPage` React components.
- Produces: Working `mailto:` and `tel:` links and a consistent postal address.

- [ ] **Step 1: Record the pre-change placeholder matches**

Run:

```bash
rg -n -i 'address line|full address|email — placeholder' src/components/site-footer.tsx src/routes/contact.tsx
```

Expected: footer address and email placeholders plus Contact page general email, quote email, and full-address placeholders.

- [ ] **Step 2: Update the shared footer**

Replace the footer address placeholder with:

```tsx
<li>Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE</li>
```

Replace the footer email placeholder with:

```tsx
<li>
  <a href="mailto:info@hmcargoservices.com" className="transition-colors hover:text-foreground">
    info@hmcargoservices.com
  </a>
</li>
```

- [ ] **Step 3: Update the Contact cards**

Use a single `contacts` array on every card:

```tsx
{[
  {
    label: "General",
    contacts: [{ label: "info@hmcargoservices.com", href: "mailto:info@hmcargoservices.com" }],
  },
  {
    label: "Quotes",
    contacts: [{ label: "quotes@hmcargoservices.com", href: "mailto:quotes@hmcargoservices.com" }],
  },
  {
    label: "Operations",
    contacts: [
      { label: "+961 81 059 063", href: "tel:+96181059063" },
      { label: "+971 52 153 0190", href: "tel:+971521530190" },
    ],
  },
].map((card) => (
  <div key={card.label} className="bg-[#0b0d10] p-10">
    <p className="eyebrow">{card.label}</p>
    <p className="mt-5 font-display text-2xl">HM Cargo Services</p>
    <div className="mt-3 flex flex-col gap-1 text-sm text-muted-foreground">
      {card.contacts.map((contact) => (
        <a key={contact.href} href={contact.href} className="transition-colors hover:text-foreground">
          {contact.label}
        </a>
      ))}
    </div>
  </div>
))}
```

Replace the office placeholder with:

```tsx
<p className="mt-3 text-muted-foreground">
  Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE
</p>
```

- [ ] **Step 4: Verify the contact content**

Run:

```bash
rg -n 'info@hmcargoservices\.com|quotes@hmcargoservices\.com|Office 404' src/components/site-footer.tsx src/routes/contact.tsx
```

Expected: the general email appears in the footer and Contact page; the quote email appears on the Contact page; the address appears in both files.

### Task 2: Company and coverage copy

**Files:**
- Modify: `src/routes/about.tsx`
- Modify: `src/routes/coverage.tsx`
- Modify: `src/components/home-sections.tsx`

**Interfaces:**
- Consumes: Existing `AboutPage`, `CoveragePage`, `IntroSection`, `AdvantagesSection`, and `CoverageSection` components.
- Produces: Credible company copy and confirmed market coverage without unsupported claims.

- [ ] **Step 1: Record the pre-change content placeholders**

Run:

```bash
rg -n -i 'company history|certifications and specific figures|client logos|specific coverage regions|detailed coverage map' src/routes/about.tsx src/routes/coverage.tsx src/components/home-sections.tsx
```

Expected: five visible placeholder locations.

- [ ] **Step 2: Replace the About placeholder**

Replace the final placeholder block in `AboutPage` with:

```tsx
<p className="text-muted-foreground">
  From regular regional movements to destination-specific international requests,
  we coordinate each shipment around its route, cargo requirements, and handoffs.
  Our role is to keep the people, documentation, and movement aligned from origin
  to arrival.
</p>
```

- [ ] **Step 3: Remove unsupported home-page sections**

Delete only these two elements:

```tsx
<div className="mt-8"><span className="placeholder-note">Certifications and specific figures — placeholder</span></div>
```

```tsx
<p className="mt-8 text-xs text-muted-foreground"><span className="placeholder-note">Client logos and case studies — placeholder</span></p>
```

Do not alter the surrounding Intro or Advantages content.

- [ ] **Step 4: Replace the shared Coverage placeholder**

Replace the shared Coverage paragraph and placeholder with:

```tsx
<p className="mt-6 text-base leading-relaxed text-muted-foreground">
  Our primary operating markets are the UAE, Lebanon, and Syria. For destinations
  farther afield — including the United States — we arrange international shipments
  based on the cargo, route, and service required.
</p>
<div className="mt-8 flex flex-wrap gap-3">
  {["UAE", "Lebanon", "Syria", "Worldwide on request"].map((region) => (
    <span key={region} className="border border-[color:var(--border-strong)] px-4 py-2 text-xs uppercase tracking-[0.14em] text-foreground/80">
      {region}
    </span>
  ))}
</div>
```

- [ ] **Step 5: Replace the Coverage page placeholder**

Use this hero paragraph and remove the detailed-map placeholder:

```tsx
<p className="mt-8 max-w-2xl text-lg text-muted-foreground">
  Our core routes serve the UAE, Lebanon, and Syria, supported by international
  shipping arrangements for destinations beyond the region when requested.
</p>
```

- [ ] **Step 6: Verify unsupported claims and placeholders are absent**

Run:

```bash
rg -n -i 'company history|certifications and specific figures|client logos|specific coverage regions|detailed coverage map' src/routes/about.tsx src/routes/coverage.tsx src/components/home-sections.tsx
```

Expected: no matches.

Run:

```bash
rg -n 'UAE|Lebanon|Syria|Worldwide on request|United States' src/routes/coverage.tsx src/components/home-sections.tsx
```

Expected: confirmed markets and international-on-request wording are present.

### Task 3: Build and rendered-page verification

**Files:**
- Verify: `src/components/site-footer.tsx`
- Verify: `src/routes/contact.tsx`
- Verify: `src/routes/about.tsx`
- Verify: `src/routes/coverage.tsx`
- Verify: `src/components/home-sections.tsx`

**Interfaces:**
- Consumes: Completed content changes from Tasks 1 and 2.
- Produces: A type-safe production build and rendered proof on all affected pages.

- [ ] **Step 1: Scan visible application content**

Run:

```bash
rg -n -i 'placeholder' src --glob '*.{ts,tsx}'
```

Expected: no visible content placeholders; form input `placeholder` props and CSS utility names are allowed.

- [ ] **Step 2: Run TypeScript**

Run:

```bash
./node_modules/.bin/tsc --noEmit
```

Expected: exit code 0 with no diagnostics.

- [ ] **Step 3: Build production output**

Run:

```bash
/Users/nabel/.local/bin/npm run build
```

Expected: successful Vite client, SSR, and Nitro `cloudflare-module` builds.

- [ ] **Step 4: Verify rendered pages**

Open `/`, `/about`, `/coverage`, and `/contact` on `http://localhost:8080`.
For every page, confirm `main` exists and `vite-error-overlay` does not exist.
On Contact, confirm:

```text
mailto:info@hmcargoservices.com
mailto:quotes@hmcargoservices.com
Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE
```

On Home and Coverage, confirm:

```text
UAE
Lebanon
Syria
Worldwide on request
```
