# HM Cargo Services Marketing Website Design

## Objective

Create a premium public marketing website for HM Cargo Services. The homepage is the primary experience: a dark, cinematic, scroll-controlled story following one shipment from preparation to final handoff. The journey communicates reliable air and road cargo services. A separate blog section supports long-term SEO.

This website is not an operational dashboard. It does not expose shipment, warehouse, payment, or administrative workflows.

## Audience and Conversion

The website addresses businesses and individuals looking for dependable cargo transportation. Its primary conversion is a quote request. Secondary actions are exploring services, contacting HM Cargo, and reading practical shipping content.

The website must not invent service destinations, prices, certifications, delivery times, statistics, customer logos, addresses, or contact information. Missing business details remain clearly identified content fields until supplied.

## Brand Direction

- Deep near-black foundation: `#050607`, with graphite layers rather than a flat pure-black page.
- Primary accent: HM orange near `#FF7900`.
- Supporting materials: brushed steel, dark concrete, cold white highlights, restrained atmospheric haze, and controlled shadows.
- Typography: strong editorial display type paired with a highly readable sans-serif body.
- Tone: confident, precise, industrial, premium, and modern.
- The provided HM logo remains recognizable and is never redrawn or distorted.
- The full logo is used in the opening and footer. The symbol-only mark is used in navigation and compact brand moments.

Brand source files:

- `/Users/nabel/Desktop/Generated image 1.png`
- `/Users/nabel/Desktop/Generated image 1-1.png`

## Homepage Story

The desktop homepage follows one identifiable parcel from the origin warehouse to the recipient. The visitor travels with the shipment rather than watching disconnected service demonstrations.

The experience uses one continuous forward camera journey. Scroll controls time while the scene remains pinned. Scene boundaries preserve forward motion and must not introduce pull-backs, rewinds, or visible cuts.

### Scene 1: The Shipment Begins

The experience opens inside a dark, modern warehouse. Controlled light reveals one parcel being prepared and scanned. A restrained orange light travels over its route label and introduces the HM mark.

- Eyebrow: Cargo without uncertainty.
- Headline: Every journey starts with trust.
- Body: Reliable air and road logistics, clear visibility, and careful handling from origin to destination.
- Actions: Request a Quote; Follow the Journey.

### Scene 2: Road to the Airport

The camera stays with the parcel as it moves through the loading area and into an HM cargo vehicle. The warehouse doors open and the vehicle travels toward the airport at night.

- Message: Moving from the first mile.
- Support: Coordinated road handling that keeps every shipment progressing.

### Scene 3: Ready for Flight

The vehicle arrives at the airport cargo terminal. The parcel moves through controlled handling and approaches the aircraft cargo hold. The camera follows it inside before the cargo door closes.

- Message: Prepared for what comes next.
- Support: Careful coordination at every handoff.

### Scene 4: Takeoff

The perspective flows from the cargo hold into an exterior runway view without an abrupt cut. Aircraft lights intensify, the runway begins moving, and the plane takes off as the visitor scrolls.

- Message: Built to cross borders.
- Support: Air cargo solutions designed to keep business moving.

### Scene 5: In Flight

The aircraft rises above the clouds. HM orange becomes a restrained route line that connects the real flight to simple shipment-status moments. The aircraft remains the visual anchor; the scene must not become a generic technology dashboard.

- Message: Visibility throughout the journey.
- Support: Clear progress from origin to arrival.

### Scene 6: Descent and Landing

The route line resolves back into the aircraft as it descends. Runway lights emerge below and the plane lands at the destination airport. Scroll pacing makes the descent and touchdown feel deliberate and satisfying.

- Message: Arriving with confidence.
- Support: The same coordinated care continues at the destination.

### Scene 7: The Final Mile

The parcel leaves the aircraft, passes through the destination cargo terminal, and moves into an HM road vehicle. The camera continues alongside the final road journey.

- Message: From runway to doorstep.
- Support: Reliable road movement completes the journey.

### Scene 8: Customer Handoff

The parcel reaches the recipient. The customer receives it in a simple, credible business or residential setting. The handoff is the emotional payoff, not a staged celebration.

- Eyebrow: From send-off to handoff.
- Headline: Your cargo. Delivered with confidence.
- Body: One coordinated journey, from the first scan to the final handoff.
- Primary action: Request a Quote.
- Secondary action: Contact HM Cargo.

The sequence ends on a clean HM brand frame. The full logo emerges with a subtle metallic highlight while the orange route line completes beneath it.

No maritime imagery is allowed anywhere in the experience. Do not show ports, ships, ocean freight, containers on vessels, or marine route motifs.

## Scroll and Motion

- Desktop-first cinematic experience using scroll-scrubbed pre-rendered media.
- The camera architecture is a continuous forward take made from frame-locked legs.
- Every leg starts from the previous leg's actual final rendered frame.
- Each leg begins and ends with a slow forward drift so position and velocity remain continuous.
- Text enters at deliberate points, holds while the scene settles, and exits before the next message.
- Takeoff, in-flight, landing, and final handoff receive longer scroll distance.
- Reverse scrolling must remain coherent.
- A subtle progress rail indicates the current journey stage.
- `prefers-reduced-motion` replaces cinematic motion with polished still scenes.
- Posters remain visible until media is ready; content is never blocked by video loading.

## Mobile Behavior

The final product is desktop-first. No mobile-specific cinematic video encodes are produced in the initial version.

Phones still receive a complete, usable website:

- Static scene posters and restrained CSS motion replace the desktop scrub experience.
- Content order, calls to action, navigation, blog access, and readability remain intact.
- The layout respects safe areas and avoids horizontal cropping that hides important text or actions.
- The full cinematic composition is preserved for desktop rather than compromised for portrait screens.

## Supporting Homepage Sections

After the cinematic journey, the page includes:

1. A concise company introduction.
2. Service cards for air cargo, road transportation, warehousing and handling, shipment coordination, cargo visibility, and business logistics solutions.
3. A simple three-step process: share shipment details, receive a plan and quote, HM coordinates the movement.
4. A coverage section whose destinations remain data-driven and hidden until verified.
5. Three latest blog articles.
6. A final quote-request call to action.
7. A professional footer with services, blog, contact fields, and legal links.

The page avoids excessive rounded cards, decorative particles, glassmorphism, and generic logistics template patterns.

## Blog and SEO

The website includes:

- `/blog`: index with a featured article, category filters, and SEO-friendly article cards.
- `/blog/[slug]`: reusable article layout with title, summary, author, publish date, reading time, table of contents, structured headings, related posts, FAQ section, and quote call to action.
- Initial categories: Shipping Guides, Air Cargo, Road Cargo, Customs, Logistics, and Business Tips.
- Clean URLs, canonical metadata, Open Graph metadata, breadcrumbs, and indexable semantic HTML.
- Architecture ready for Article, Breadcrumb, Organization, Service, and FAQ structured data where the page content supports it.

The first build may use clearly identified sample editorial content, but it must not present invented operational claims as company facts.

## Accessibility and Performance

- Semantic navigation, headings, buttons, links, and landmarks.
- Keyboard-accessible controls and visible focus states.
- Strong text contrast over every scene.
- No essential information exists only inside video.
- Lazy-load non-critical cinematic media.
- Use still posters during loading and for reduced-motion/mobile modes.
- Avoid blocking the initial page on large assets.
- Preserve readable content if JavaScript or animation is unavailable.

## Acceptance Criteria

- The homepage feels distinctive to HM Cargo, not like a purchased logistics theme.
- The visual system consistently uses near-black, graphite, steel, and HM orange.
- The desktop journey follows one shipment continuously through warehouse preparation, the road to the airport, aircraft loading, takeoff, flight, landing, final-mile transport, and customer handoff.
- No maritime service or imagery appears.
- The phone layout opens cleanly and preserves all core content without requiring the cinematic scrub.
- The blog index and article structure are ready for SEO publishing.
- All claims and business data are either supplied, verified, or visibly marked for later completion.
- Reduced-motion users receive a complete static experience.
