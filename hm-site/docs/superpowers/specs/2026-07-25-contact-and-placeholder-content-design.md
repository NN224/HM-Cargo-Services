# HM Cargo Contact and Placeholder Content Design

## Goal

Replace visible placeholder content with accurate contact details and concise,
credible company copy. Remove sections that would otherwise require invented or
unverified claims.

## Contact details

- General enquiries: `info@hmcargoservices.com`
- Quote requests: `quotes@hmcargoservices.com`
- Address: `Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE`
- Keep the existing Lebanon and UAE telephone numbers unchanged.
- Show the general email and address in the site footer.
- Show both emails, the address, and the existing telephone numbers on the
  contact page.

## Company content

- Replace the About page placeholder with a concise company description focused
  on coordinated cargo handling, clear communication, and reliable delivery.
- Do not claim a founding year, team size, shipment volume, certifications, or
  other figures that have not been supplied.

## Coverage content

- Present the UAE, Lebanon, and Syria as the company's primary operating markets.
- Explain that wider international destinations, including the United States,
  can be arranged based on each request.
- Avoid claiming permanent offices, routes, or infrastructure outside the
  confirmed primary markets.

## Removed content

- Remove the certifications and figures placeholder from the home page.
- Remove the client logos and case studies placeholder until real assets and
  approved client references are available.
- Remove the detailed coverage-map placeholder and replace it with useful
  coverage copy; do not fabricate a map.

## Unchanged content

- Keep the existing blog “coming soon” states.
- Keep the existing visual design, layout, animation, navigation, telephone
  numbers, and quote flow unchanged.

## Verification

- Search the rendered source for visible placeholder text.
- Confirm both email links use the correct `mailto:` destinations.
- Confirm the address appears on the contact page and in the footer.
- Confirm the UAE, Lebanon, Syria, and international-on-request copy appears.
- Run TypeScript checks and the production build.
- Open the home, About, Coverage, and Contact pages and confirm they render
  without a Vite error overlay.
