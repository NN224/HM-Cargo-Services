export type BlogCategory =
    "Shipping Guides" | "Cargo Updates" | "Customs" | "Logistics" | "Business Tips";

export type BlogPost = {
    slug: string;
    title: string;
    excerpt: string;
    category: BlogCategory;
    author: string;
    publishedAt: string; // ISO date
    readingMinutes: number;
    cover: string; // asset url
    featured?: boolean;
    metaTitle?: string;
    metaDescription?: string;
    ogTitle?: string;
    ogDescription?: string;
    pillarTopic?: string;
    contentType?: string;
    body: Array<
        | { type: "p"; text: string }
        | { type: "h2"; text: string; id: string }
        | { type: "h3"; text: string; id?: string }
        | { type: "ul"; items: string[] }
        | { type: "ol"; items: string[] }
        | { type: "quote"; text: string }
        | { type: "table"; headers: string[]; rows: string[][] }
        | { type: "image"; src: string; alt: string }
    >;
    faq: { q: string; a: string }[];
    jsonLd?: Record<string, unknown>;
};

import container from "@/assets/scenes/scene-container.jpg";

export const posts: BlogPost[] = [
    {
        slug: "cargo-dubai-to-syria",
        title: "Everything That Goes Into Shipping Cargo From Dubai to Syria",
        excerpt:
            "A practical guide to cargo Dubai to Syria: routes, documents, lead times, indicative market costs, and the questions to ask before you book.",
        category: "Shipping Guides",
        author: "HM Cargo Editorial",
        publishedAt: "2026-08-17",
        readingMinutes: 11,
        cover: "/media/blog/cargo-dubai-to-syria-featured.webp",
        featured: true,
        metaTitle: "Cargo Dubai to Syria: Complete Shipping Guide",
        metaDescription:
            "A practical guide to cargo Dubai to Syria: routes, documents, lead times, indicative market costs, and the questions to ask before you book.",
        ogTitle: "Shipping Cargo From Dubai to Syria: Full Guide",
        ogDescription:
            "Routes, paperwork, lead times and indicative market costs for cargo from Dubai to Syria, plus the questions to ask any operator.",
        pillarTopic: "International freight from the UAE",
        contentType: "article",
        body: [
            {
                type: "p",
                text: "Most people who need to move cargo from Dubai to Syria do not fail on price. They fail on handoffs. A shipment leaves a warehouse in Al Quoz, clears an exporter formality, sits at a port, changes hands to a trucking leg, and then goes quiet. Nobody lied to you. The chain simply had four owners and no single person who could tell you where the box was on a Tuesday afternoon.",
            },
            {
                type: "p",
                text: "The second failure is paperwork. An invoice value that does not match the packing list, a description written in shorthand, a consignee name spelled two ways across two documents — each of these can hold cargo at a border for days. Delays on this lane are rarely dramatic. They are administrative, and they compound, because one missed cutoff pushes you to the next sailing or the next convoy window.",
            },
            {
                type: "quote",
                text: "A Dubai to Syria shipment is won or lost on documentation and handoff control, not on the freight rate you negotiated.",
            },
            {
                type: "p",
                text: "This guide covers what the route actually is, how sea and air compare, what documents travel with the cargo, how long to allow, what the market typically charges, and the questions worth asking before you commit. Read it top to bottom if this is your first shipment. Skim to the section you need if it is not.",
            },
            {
                type: "h2",
                id: "what-does-cargo-dubai-to-syria-actually-mean",
                text: 'What does "cargo Dubai to Syria" actually mean?',
            },
            {
                type: "p",
                text: "Cargo Dubai to Syria is the movement of commercial or personal goods from the UAE to a Syrian destination, using sea freight, air freight, or road transport, with customs formalities handled at both the exit and entry points. It is a multi-leg movement rather than a single journey, which is why coordination matters more than any one leg.",
            },
            {
                type: "p",
                text: "The Dubai side is straightforward in structure. Goods are collected, documented, and exported through a UAE gateway — Jebel Ali for containerised sea freight, Dubai International or Al Maktoum for air, or a land border for road movements. Dubai Customs publishes UAE export procedures and declaration requirements at [dubaicustoms.gov.ae](https://www.dubaicustoms.gov.ae), so the exporter-side rules are public and checkable.",
            },
            {
                type: "p",
                text: "The Syrian side is where variability lives. Entry point, inspection practice, and clearance timing all shift with the cargo type and the moment. Because of that, an operator who works the lane regularly is worth more than one who quotes it occasionally. HM Cargo Services lists the UAE, Lebanon and Syria as its primary operating markets, which is a different thing from arranging an occasional shipment there.",
            },
            {
                type: "quote",
                text: "Key takeaway: Dubai to Syria is not one journey. It is a chain of legs and clearances, so the person controlling the handoffs matters more than the person quoting the rate.",
            },
            {
                type: "h2",
                id: "should-you-send-cargo-to-syria-by-sea-or-by-air",
                text: "Should you send cargo to Syria by sea or by air?",
            },
            {
                type: "p",
                text: "Sea freight suits heavy, bulky, or non-urgent cargo to Syria and costs far less per kilo. Air freight suits light, high-value, time-critical, or temperature-sensitive goods and moves in days rather than weeks. Most shippers on this lane default to sea and use air only for the portion that genuinely cannot wait.",
            },
            {
                type: "p",
                text: "The economics are simple. Sea freight prices on volume and weight together, so a dense pallet travels cheaply. Air freight prices on chargeable weight, which punishes anything bulky and light. A shipment of machine parts often makes sense by air, although the same volume in packaging materials almost never does.",
            },
            {
                type: "p",
                text: "Timing is the other axis. Sea moves on fixed sailing schedules, which means missing a cutoff costs you a full cycle rather than a day. Air departs far more often, so a missed flight is a smaller loss. HM Cargo Services offers both sea and air freight under its International Cargo service, alongside Ground Transportation for the road legs on either end.",
            },
            {
                type: "table",
                headers: ["Factor", "Sea freight", "Air freight"],
                rows: [
                    [
                        "Best for",
                        "Heavy, bulky, non-urgent goods",
                        "Light, high-value, urgent goods",
                    ],
                    ["Pricing basis", "Volume and weight combined", "Chargeable weight"],
                    ["Schedule", "Fixed sailings, hard cutoffs", "Frequent departures"],
                    ["Missed-cutoff cost", "A full sailing cycle", "Usually the next flight"],
                    ["Packing demand", "Higher — longer handling chain", "Lower — fewer touches"],
                ],
            },
            {
                type: "p",
                text: "The International Maritime Organization sets the global safety and packaging rules that govern sea movements, including the dangerous goods code, and publishes them at [imo.org](https://www.imo.org). That matters if any part of your consignment is classed as hazardous, because the classification changes your packing, your paperwork, and your booking window.",
            },
            {
                type: "h2",
                id: "what-documents-does-a-dubai-to-syria-shipment-need",
                text: "What documents does a Dubai to Syria shipment need?",
            },
            {
                type: "p",
                text: "Every Dubai to Syria shipment travels with a commercial invoice, a packing list, and a transport document — a bill of lading for sea or an air waybill for air. Certificates of origin and product-specific permits are frequently required on top. The exact set depends on the commodity, so confirm it before you pack rather than after.",
            },
            {
                type: "p",
                text: "Consistency across those documents matters more than the documents themselves. The consignee name, the goods description, the piece count and the declared value should read identically everywhere. HM Cargo Services names incomplete or inconsistent documentation as the most common cause of shipment delays, in its guide to preparing cargo for international shipping at [Preparing Cargo for International Shipping](https://hmcargoservices.com/blog/preparing-cargo-for-international-shipping).",
            },
            {
                type: "p",
                text: "A licensed customs broker is not legally required in most destinations, although it usually pays for itself. That is the position HM Cargo Services states, and it holds up practically. A broker who handles a lane weekly recognises a description that will trigger an inspection, which is knowledge you cannot buy on a single shipment.",
            },
            {
                type: "p",
                text: "Keep one more habit. Photograph the cargo packed and labelled before it leaves your premises. It costs nothing, and it settles condition disputes quickly if anything arrives damaged.",
            },
            {
                type: "image",
                src: "/media/blog/cargo-dubai-to-syria-1.webp",
                alt: "A freight worker checking a clipboard beside a strapped and labelled pallet inside a warehouse",
            },
            {
                type: "h2",
                id: "how-much-lead-time-should-you-allow",
                text: "How much lead time should you allow?",
            },
            {
                type: "p",
                text: "Book standard freight 2–3 weeks before the target sailing date, and allow 4–6 weeks for hazardous, oversized, or seasonally constrained cargo. Those are the windows HM Cargo Services publishes. They exist because documentation, permits and space booking all run in sequence, so a late start pushes everything behind it.",
            },
            {
                type: "p",
                text: "The 2–3 week window is not padding. It covers gathering documents, confirming the commodity classification, booking space, arranging collection, and reaching the port before the cutoff. Any one of those can stall for a day, which is fine when you have slack and expensive when you do not.",
            },
            {
                type: "p",
                text: "The 4–6 week window applies where a third party has to approve something. Hazardous goods need classification and compliant packing. Oversized cargo needs equipment planned in advance. Seasonal cargo competes for space with everyone else who wants the same weeks, so booking early is the whole strategy.",
            },
            {
                type: "ol",
                items: [
                    "**Cargo classification.** Hazardous or restricted goods need a longer approval path, so classify honestly at the start.",
                    "**Documentation readiness.** Missing or inconsistent paperwork is the single most common delay cause, which is why document prep should begin before packing.",
                    "**Dimensions and weight.** Oversized or unusually heavy pieces need specific equipment, and that equipment gets booked in advance.",
                    "**Seasonality.** Peak periods tighten space on both sea and air, so the same shipment needs more lead time in a busy month.",
                    "**Destination requirements.** Entry-point rules and inspection practice vary, so confirm the destination-side needs before you commit to a date.",
                    "**Collection distance.** A pickup far from the gateway adds a road leg, and that leg has its own timing risk.",
                ],
            },
            {
                type: "quote",
                text: "Key takeaway: Treat the booking window as a sequence, not a buffer. Standard freight wants 2–3 weeks; anything hazardous, oversized or seasonal wants 4–6.",
            },
            {
                type: "h2",
                id: "what-does-cargo-from-dubai-to-syria-cost",
                text: "What does cargo from Dubai to Syria cost?",
            },
            {
                type: "p",
                text: "There is no published price for this lane, because freight is quoted per shipment. Cost is driven by mode, volume, weight, commodity type, and the road legs at either end. Across the market, the ranges below are the shape most quotes take, although your actual number depends entirely on your cargo.",
            },
            {
                type: "table",
                headers: ["Cost component", "What drives it", "Typical market pattern"],
                rows: [
                    [
                        "Sea freight (LCL)",
                        "Cubic metres and weight",
                        "Priced per CBM, with a minimum charge",
                    ],
                    [
                        "Sea freight (FCL)",
                        "Container size and route",
                        "Flat rate per 20ft or 40ft box",
                    ],
                    ["Air freight", "Chargeable weight", "Priced per kg, multiples of sea rates"],
                    [
                        "Customs clearance",
                        "Declaration complexity",
                        "Flat fee per declaration, plus duties",
                    ],
                    ["Road legs", "Distance and vehicle type", "Priced per trip at each end"],
                    ["Warehousing", "Days stored and handling", "Priced per pallet or per day"],
                ],
            },
            {
                type: "p",
                text: "*Note: These are indicative ranges based on general market practice, not quoted prices, and they vary by cargo, season, and route — confirm current costs directly with the venue before you budget.*",
            },
            {
                type: "p",
                text: "HM Cargo Services publishes no prices anywhere on its site. Instead, it quotes per shipment and states it responds with a route, a timeline, and a quote — usually the same day. You get that by submitting the [Request a Quote form](https://hmcargoservices.com/quote), or by messaging on [WhatsApp](https://wa.me/971521530190).",
            },
            {
                type: "p",
                text: 'Vague messages get vague answers. If you send only "how much to Syria", any operator has to guess at mode, volume and commodity, so the number that comes back is worthless. Give the origin, the destination, the cargo type and the estimated weight or volume, because those four fields turn a guess into a quote.',
            },
            {
                type: "h2",
                id: "how-is-the-shipment-actually-coordinated-end-to-end",
                text: "How is the shipment actually coordinated end to end?",
            },
            {
                type: "p",
                text: "Coordination on this lane means one party owning the pickup, the handling, the documentation, and every handoff between carriers and customs. HM Cargo Services describes a three-step process: 01 Request, where you share cargo details and destinations; 02 Coordinate, covering pickup, handling, documentation and every handoff with one point of contact; and 03 Deliver.",
            },
            {
                type: "p",
                text: 'The value sits in step two. A shipment to Syria changes hands several times, and each change is a chance for information to drop. When one operator holds the whole chain, the answer to "where is it" comes from one place. HM Cargo Services lists Shipment Coordination across carriers, customs and destinations as a named service at [services page](https://hmcargoservices.com/services).',
            },
            {
                type: "p",
                text: "Warehousing belongs in the same conversation. Cargo often needs to sit somewhere between collection and sailing, especially when you are consolidating from several suppliers. HM Cargo Services lists Warehousing & Handling as one of its six services, which keeps that stage inside the same operation rather than subcontracted out.",
            },
            {
                type: "p",
                text: "Tracking closes the loop. HM Cargo Services lists Cargo Tracking as a service and describes clear visibility with reliable status updates as one of its stated advantages. That is a different promise from a public tracking portal, so ask what form the updates take before you assume.",
            },
            {
                type: "h2",
                id: "what-should-you-ask-before-committing-to-an-operator",
                text: "What should you ask before committing to an operator?",
            },
            {
                type: "p",
                text: "Before booking cargo from Dubai to Syria, confirm that the operator actually works the lane, that you get one named contact, and that the quote covers every leg rather than the freight alone. These questions separate an operator who runs the route from one who resells it.",
            },
            {
                type: "ul",
                items: [
                    "**Is Syria a regular lane for you, or an occasional arrangement?** Regular lanes come with regular handling knowledge, which is what prevents surprises at the destination.",
                    '**Who is my named contact, and do they stay with the shipment?** HM Cargo Services states that every quote request is answered by a named operations contact, "not a queue".',
                    "**Does the quote include the road legs at both ends?** A freight-only number looks cheaper until the trucking invoice arrives separately.",
                    "**What happens if my documents are wrong?** You want someone who checks paperwork before departure rather than after a hold.",
                    "**How and how often will I get status updates?** Agree the rhythm upfront, because silence feels like a problem even when nothing is wrong.",
                    '**Who handles customs on each side?** If the answer is "a partner", ask who talks to that partner when something stalls.',
                    "**Can you handle warehousing if my cargo is ready early?** Consolidating from several suppliers usually means something waits somewhere.",
                ],
            },
            {
                type: "h2",
                id: "why-hm-cargo-services-suits-this-route",
                text: "Why HM CARGO SERVICES suits this route",
            },
            {
                type: "p",
                text: "HM Cargo Services is a Dubai-based operator whose primary markets are the UAE, Lebanon and Syria, which makes this lane core business rather than an exception. It runs six services — International Cargo, Ground Transportation, Warehousing & Handling, Shipment Coordination, Cargo Tracking and Business Logistics — from one operation, so the handoffs between sea, air, road and storage stay inside a single chain.",
            },
            {
                type: "p",
                text: 'That structure answers the coordination problem directly. HM Cargo Services describes its network as covering ground, air, sea and warehousing under one operation, with a handling profile matched to each cargo\'s route. Its office is at Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai. Coverage is summarised as "UAE, Lebanon, Syria, Worldwide on request", with the United States named among the destinations arranged on request.',
            },
            {
                type: "p",
                text: "Who this is a strong fit for:",
            },
            {
                type: "ul",
                items: [
                    "Businesses shipping regularly between the UAE and Syria, because the lane is a primary market rather than a one-off arrangement",
                    "Shippers who need sea, air, road and storage handled by one operator instead of several",
                    "Anyone who wants a named contact for every active shipment rather than a ticket queue",
                    "Repeat and enterprise shippers, who are served by the Business Logistics programme for tailored supply-chain arrangements",
                    "Shippers who want a same-day answer, since the stated response is a route, a timeline and a quote — usually the same day",
                ],
            },
            {
                type: "p",
                text: "You can read more about the operation at [about page](https://hmcargoservices.com/about), or check the lanes at [coverage page](https://hmcargoservices.com/coverage).",
            },
            {
                type: "h2",
                id: "frequently-asked-questions-about-dubai-to-syria-cargo",
                text: "Frequently Asked Questions About Dubai to Syria Cargo",
            },
            {
                type: "h2",
                id: "the-verdict-on-shipping-dubai-to-syria",
                text: "The verdict on shipping Dubai to Syria",
            },
            {
                type: "p",
                text: "Shipping cargo from Dubai to Syria is manageable when you plan the documents early and put one operator in charge of the handoffs. Book standard freight 2–3 weeks out and hazardous or oversized cargo 4–6 weeks out. Choose sea for bulk and air for urgency, then judge operators on lane experience rather than headline rate.",
            },
            {
                type: "p",
                text: "The lane punishes vagueness at both ends. A precise brief gets a precise quote, and consistent paperwork gets a shipment that keeps moving. That is most of the job.",
            },
            {
                type: "ul",
                items: [
                    "Send your cargo details through the [Request a Quote form](https://hmcargoservices.com/quote) for a route, a timeline and a quote — usually the same day",
                    "Message the team on [WhatsApp](https://wa.me/971521530190) if you want to talk through the route first",
                    "Reach the office via [contact page](https://hmcargoservices.com/contact), or email info@hmcargoservices.com for general enquiries and quotes@hmcargoservices.com for quotes",
                    "Review the full service list at [services page](https://hmcargoservices.com/services) before you brief the shipment",
                ],
            },
        ],
        faq: [
            {
                q: "Is Syria one of the countries you actually operate in?",
                a: 'Yes. HM Cargo Services lists the UAE, Lebanon and Syria as its primary operating markets, and summarises coverage as "UAE, Lebanon, Syria, Worldwide on request". Syria is a core lane rather than an occasional arrangement. Destinations outside the region are arranged on request, based on cargo, route and service required.',
            },
            {
                q: "How is a shipment to Syria coordinated across the different handoffs?",
                a: "HM Cargo Services runs a three-step process: 01 Request, 02 Coordinate, 03 Deliver. The Coordinate step covers pickup, handling, documentation and every handoff, with one point of contact throughout. Shipment Coordination across carriers, customs and destinations is one of its six named services, so the legs stay inside one operation.",
            },
            {
                q: "Will I have one point of contact for the whole route, or several?",
                a: 'One. HM Cargo Services states that direct communication with a named contact for every active shipment is one of its four stated advantages. Every quote request is answered by a named operations contact, described on the site as "not a queue". That contact is where your status questions go.',
            },
            {
                q: "How much lead time should I allow for a shipment heading to Syria?",
                a: "Allow 2–3 weeks before the target sailing date for standard freight, and 4–6 weeks for hazardous, oversized, or seasonally constrained cargo. Those are the windows HM Cargo Services publishes in its guide to preparing cargo for international shipping. Booking earlier gives you slack if a document or permit takes longer than expected.",
            },
            {
                q: "What information about the destination do you need on the quote form?",
                a: "The Request a Quote form asks for full name, company, email, phone, origin, destination, cargo type, estimated weight or volume, and additional details. The destination field is where the Syrian delivery point goes, and the additional details box is where you note anything unusual about access or timing. HM Cargo Services responds with a route, a timeline, and a quote — usually the same day.",
            },
            {
                q: "How will I know where my cargo is once it has left Dubai?",
                a: "Cargo Tracking is one of the six services HM Cargo Services names, and clear visibility with reliable status updates is one of its stated advantages. Updates come through the named contact assigned to your shipment. The site does not describe a specific tracking portal or update frequency, so ask your contact what form the updates take when you book.",
            },
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@id": "https://hmcargoservices.com/#website",
                    "@type": "WebSite",
                    inLanguage: "en",
                    name: "HM CARGO SERVICES",
                    publisher: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    url: "https://hmcargoservices.com/",
                },
                {
                    "@id": "https://hmcargoservices.com/#business",
                    "@type": "LocalBusiness",
                    address: {
                        "@type": "PostalAddress",
                        addressLocality: "Dubai, UAE",
                        addressRegion: "Al Murar, Deira",
                        streetAddress: "Office 404, Awqaf Building, Street 8",
                    },
                    description:
                        "HM Cargo Services is a Dubai-based logistics operator offering international sea and air freight, ground transportation, warehousing and handling, shipment coordination, cargo tracking, and business logistics programs. Its core trade lanes are the UAE, Lebanon, and Syria, with worldwide shipments arranged on request.",
                    name: "HM CARGO SERVICES",
                    url: "https://hmcargoservices.com/",
                },
                {
                    "@id": "https://hmcargoservices.com/blog/cargo-dubai-to-syria#article",
                    "@type": "Article",
                    about: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    articleSection: "International freight from the UAE",
                    author: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    dateModified: "2026-08-17",
                    datePublished: "2026-08-17",
                    description:
                        "A practical guide to cargo Dubai to Syria: routes, documents, lead times, indicative market costs, and the questions to ask before you book.",
                    headline: "Cargo Dubai to Syria: Complete Shipping Guide",
                    inLanguage: "en",
                    isPartOf: {
                        "@id": "https://hmcargoservices.com/#website",
                    },
                    mainEntityOfPage: {
                        "@id": "https://hmcargoservices.com/blog/cargo-dubai-to-syria",
                        "@type": "WebPage",
                    },
                    publisher: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    wordCount: 2813,
                },
                {
                    "@id": "https://hmcargoservices.com/blog/cargo-dubai-to-syria#breadcrumb",
                    "@type": "BreadcrumbList",
                    itemListElement: [
                        {
                            "@type": "ListItem",
                            item: "https://hmcargoservices.com/",
                            name: "HM CARGO SERVICES",
                            position: 1,
                        },
                        {
                            "@type": "ListItem",
                            item: "https://hmcargoservices.com/blog/",
                            name: "Blog",
                            position: 2,
                        },
                        {
                            "@type": "ListItem",
                            item: "https://hmcargoservices.com/blog/cargo-dubai-to-syria",
                            name: "Everything That Goes Into Shipping Cargo From Dubai to Syria",
                            position: 3,
                        },
                    ],
                },
                {
                    "@id": "https://hmcargoservices.com/blog/cargo-dubai-to-syria#faq",
                    "@type": "FAQPage",
                    mainEntity: [
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: 'Yes. HM Cargo Services lists the UAE, Lebanon and Syria as its primary operating markets, and summarises coverage as "UAE, Lebanon, Syria, Worldwide on request". Syria is a core lane rather than an occasional arrangement. Destinations outside the region are arranged on request, based on cargo, route and service required.',
                            },
                            name: "Is Syria one of the countries you actually operate in?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "HM Cargo Services runs a three-step process: 01 Request, 02 Coordinate, 03 Deliver. The Coordinate step covers pickup, handling, documentation and every handoff, with one point of contact throughout. Shipment Coordination across carriers, customs and destinations is one of its six named services, so the legs stay inside one operation.",
                            },
                            name: "How is a shipment to Syria coordinated across the different handoffs?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: 'One. HM Cargo Services states that direct communication with a named contact for every active shipment is one of its four stated advantages. Every quote request is answered by a named operations contact, described on the site as "not a queue". That contact is where your status questions go.',
                            },
                            name: "Will I have one point of contact for the whole route, or several?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "Allow 2–3 weeks before the target sailing date for standard freight, and 4–6 weeks for hazardous, oversized, or seasonally constrained cargo. Those are the windows HM Cargo Services publishes. Booking earlier gives you slack if a document or permit takes longer than expected.",
                            },
                            name: "How much lead time should I allow for a shipment heading to Syria?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "The Request a Quote form asks for full name, company, email, phone, origin, destination, cargo type, estimated weight or volume, and additional details. The destination field is where the Syrian delivery point goes, and additional details is where you note anything unusual about access or timing. HM Cargo Services responds with a route, a timeline, and a quote — usually the same day.",
                            },
                            name: "What information about the destination do you need on the quote form?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "Cargo Tracking is one of the six services HM Cargo Services names, and clear visibility with reliable status updates is one of its stated advantages. Updates come through the named contact assigned to your shipment. The site does not describe a specific tracking portal or update frequency, so ask your contact what form the updates take.",
                            },
                            name: "How will I know where my cargo is once it has left Dubai?",
                        },
                    ],
                },
            ],
        },
    },
    {
        slug: "shipping-dubai-to-lebanon",
        title: "Dubai to Lebanon Cargo Shipping, Start to Finish",
        excerpt:
            "A practical guide to shipping Dubai to Lebanon: sea or air, booking lead times, documents, costs and what to ask before you hand over cargo.",
        category: "Shipping Guides",
        author: "HM Cargo Editorial",
        publishedAt: "2026-08-16",
        readingMinutes: 12,
        cover: "/media/blog/shipping-dubai-to-lebanon-featured.webp",
        featured: false,
        metaTitle: "Shipping Dubai to Lebanon: Complete Cargo Guide",
        metaDescription:
            "A practical guide to shipping Dubai to Lebanon: sea or air, booking lead times, documents, costs and what to ask before you hand over cargo.",
        ogTitle: "Dubai to Lebanon Cargo Shipping Guide",
        ogDescription:
            "Sea or air, how early to book, which documents matter, and the questions to ask before shipping cargo from Dubai to Lebanon.",
        pillarTopic: "International freight from the UAE",
        contentType: "article",
        body: [
            {
                type: "p",
                text: "Most problems on this route start long before anything is loaded. A shipper packs a pallet and sends a rough description by message. A rate comes back that sounds fine. Then the real questions arrive, because the paperwork needs answers nobody prepared.",
            },
            {
                type: "p",
                text: "Shipping from Dubai to Lebanon is the movement of commercial or personal cargo from the UAE to a Lebanese destination by sea or air under a booked freight service. That sounds simple on paper. In practice the cargo passes through a pickup, an export clearance, a carrier, an import clearance and a final road leg. Each handoff is a place where a missing detail stops everything.",
            },
            {
                type: "quote",
                text: "A Dubai to Lebanon shipment is won or lost on the details you supply in the first hour, not on the rate you negotiate in the first week.",
            },
            {
                type: "p",
                text: "This guide covers the whole run in order. You get the sea versus air decision, realistic booking lead times, the documents that travel with the goods and the reasons shipments actually stall. It also gives you a comparison framework for cargo companies, a checklist of questions to ask before you commit, and answers to the questions shippers on this lane raise most often.",
            },
            {
                type: "h2",
                id: "what-does-shipping-cargo-from-dubai-to-lebanon-actually-involve",
                text: "What does shipping cargo from Dubai to Lebanon actually involve?",
            },
            {
                type: "p",
                text: "Shipping cargo from Dubai to Lebanon involves five linked stages: collection in the UAE, export documentation and clearance, the sea or air leg, import clearance in Lebanon, and inland delivery to the consignee. One operator usually coordinates all five. Where that coordination breaks, the cargo waits rather than moves.",
            },
            {
                type: "p",
                text: "Collection is the first stage. A truck picks the cargo up from a warehouse, a shop or a residence in the UAE. Because that truck is booked against a sailing or a flight, a late pickup can cost you the slot entirely.",
            },
            {
                type: "p",
                text: "Export handling comes next. The goods are described, weighed and declared before they leave the country. Dubai Customs publishes the UAE's declaration procedures and channels on its site at [dubaicustoms.gov.ae](https://www.dubaicustoms.gov.ae), so the requirements are not a mystery. They are simply unforgiving about accuracy.",
            },
            {
                type: "p",
                text: "The main leg follows. Sea cargo moves in a container or as loose cargo consolidated with other shipments. Air cargo moves on a passenger or freighter flight, which is why it costs more per kilogram.",
            },
            {
                type: "p",
                text: "Import clearance in Lebanon is the stage most first-time shippers underestimate. Duty, taxes and destination charges are assessed there against your documents. After release, a final road leg carries the goods to the consignee's address.",
            },
            {
                type: "quote",
                text: "Key takeaway: A Dubai to Lebanon shipment is five stages, not one journey. Ask any provider who owns each stage, since the gaps between stages are where cargo goes quiet.",
            },
            {
                type: "h2",
                id: "should-you-send-your-cargo-by-sea-or-by-air",
                text: "Should you send your cargo by sea or by air?",
            },
            {
                type: "p",
                text: "Send it by sea when volume and cost matter more than speed, and by air when the value or urgency of the goods justifies a much higher rate per kilogram. Sea suits furniture, machinery, building materials and stock replenishment. Air suits spare parts, samples, documents and anything perishable or time-critical.",
            },
            {
                type: "p",
                text: 'The honest test is not "how fast do I want this". It is "what does a week of delay cost me". A container of retail stock arriving two weeks later is an inconvenience. A production line stopped for a missing part is a different number entirely.',
            },
            {
                type: "p",
                text: "Volume is the other half of the decision. Air freight is priced on chargeable weight, which uses the greater of actual weight and volumetric weight. Light bulky cargo therefore prices badly by air, even when it is easy to lift.",
            },
            {
                type: "table",
                headers: ["Factor", "Sea freight", "Air freight"],
                rows: [
                    [
                        "Best suited to",
                        "Bulky, heavy or low-urgency cargo",
                        "Urgent, high-value or light cargo",
                    ],
                    [
                        "Cost driver",
                        "Container space or cubic metres",
                        "Chargeable weight, actual or volumetric",
                    ],
                    ["Typical transit", "Longer, measured in weeks", "Shorter, measured in days"],
                    [
                        "Booking pressure",
                        "Tied to a fixed sailing schedule",
                        "More frequent departure options",
                    ],
                    [
                        "Documentation",
                        "Bill of lading and supporting papers",
                        "Air waybill and supporting papers",
                    ],
                ],
            },
            {
                type: "p",
                text: "HM Cargo Services offers both, since International Cargo covers sea and air freight across regional and international lanes. The choice between them is still yours, so bring the deadline and the dimensions to the conversation. You can see the full service range on the [services page](https://hmcargoservices.com/services).",
            },
            {
                type: "h2",
                id: "how-far-ahead-should-you-book-a-shipment-to-lebanon",
                text: "How far ahead should you book a shipment to Lebanon?",
            },
            {
                type: "p",
                text: "Book standard freight two to three weeks before the target sailing date. Allow four to six weeks for hazardous, oversized or seasonally constrained cargo. That guidance comes from the HM Cargo Services Journal article on preparing cargo for international shipping, and it exists because slots and paperwork both take time to secure.",
            },
            {
                type: "p",
                text: "Two to three weeks sounds generous until you break it down. Documents need drafting and checking. Special permits, where the cargo needs them, are not issued on demand. As a result, the buffer disappears faster than most shippers expect.",
            },
            {
                type: "p",
                text: "The four to six week window applies to cargo with extra conditions attached. Hazardous goods need declarations and carrier approval. Oversized cargo needs equipment that is not always sitting idle. Seasonal cargo competes with everyone else shipping the same thing at the same time.",
            },
            {
                type: "p",
                text: "Late booking is still possible on this lane. It just narrows your options to whatever space remains, which is rarely the cheapest space. The full write-up sits in [Preparing Cargo for International Shipping](https://hmcargoservices.com/blog/preparing-cargo-for-international-shipping) if you want the longer version.",
            },
            {
                type: "quote",
                text: "Key takeaway: Two to three weeks is the working minimum for standard freight to Lebanon, while anything hazardous, oversized or seasonal needs four to six.",
            },
            {
                type: "h2",
                id: "which-documents-does-a-dubai-to-lebanon-shipment-need",
                text: "Which documents does a Dubai to Lebanon shipment need?",
            },
            {
                type: "p",
                text: "A Dubai to Lebanon shipment needs a commercial invoice, a packing list, a transport document and full consignee details as its core paperwork. Additional certificates apply depending on the goods. The exact set varies by cargo type and destination requirements, which is why you confirm it with your operator before packing.",
            },
            {
                type: "ol",
                items: [
                    "**Commercial invoice.** This states what the goods are, what they are worth and who is buying them. Duty in Lebanon is assessed against it, so an understated value creates a problem rather than a saving.",
                    "**Packing list.** This breaks the shipment into pieces, weights and dimensions. It has to match the invoice exactly, because a mismatch between the two is a classic clearance query.",
                    "**Transport document.** Sea shipments travel on a bill of lading and air shipments on an air waybill. Both name the shipper and the consignee, so a spelling error here is expensive to correct later.",
                    "**Consignee identification details.** Lebanese customs clears goods against a named importer. The consignee therefore needs to be a real, reachable party with valid identification, not a rough name written on a form.",
                    "**Certificates specific to the cargo.** Food, chemicals, electronics and regulated goods can require certificates of origin, conformity or health. Ask early, since these are issued by third parties and cannot be rushed.",
                ],
            },
            {
                type: "p",
                text: "The International Maritime Organization sets the global rules for declaring dangerous goods at sea, published at [imo.org](https://www.imo.org). If your cargo touches that category at all, declare it. An undeclared hazardous item is a safety issue before it is a paperwork issue.",
            },
            {
                type: "image",
                src: "/media/blog/shipping-dubai-to-lebanon-1.webp",
                alt: "A worker checking a paper packing list against palletised cargo in a warehouse",
            },
            {
                type: "h2",
                id: "what-actually-causes-delays-on-this-route",
                text: "What actually causes delays on this route?",
            },
            {
                type: "p",
                text: "Incomplete or inconsistent documentation is the most common cause of shipment delays, according to the HM Cargo Services Journal. Not weather, not congestion, not customs being difficult. The invoice says one thing and the packing list says another, so the shipment stops until somebody reconciles them.",
            },
            {
                type: "p",
                text: "The second cause is a consignee who cannot be reached. Cargo arrives, a query is raised, and nobody answers the phone number on the file. Because storage charges start accruing at that point, a quiet week becomes a real cost.",
            },
            {
                type: "p",
                text: 'The third is vague cargo description. "General goods" is not a description that clears anything. Customs authorities classify goods to assess duty, which is why a precise product description saves you days.',
            },
            {
                type: "p",
                text: "The fourth is a shipper who assumes someone else is handling the destination end. Ask explicitly whether your quote covers import clearance and delivery in Lebanon, or stops at the port. Both models exist, and the difference only becomes obvious when the cargo has already landed.",
            },
            {
                type: "h2",
                id: "what-changes-the-cost-of-shipping-from-dubai-to-lebanon",
                text: "What changes the cost of shipping from Dubai to Lebanon?",
            },
            {
                type: "p",
                text: "Cost on this lane is driven by the shipping mode, the volume and weight of the cargo, the nature of the goods and how much of the journey the quote covers. Rates are quoted per shipment rather than published, so two similar-looking consignments can price very differently once the details are on the table.",
            },
            {
                type: "ol",
                items: [
                    "**Mode.** Air freight costs substantially more per kilogram than sea freight. That gap is the single biggest lever on your total, which is why the urgency question comes first.",
                    "**Volume and weight.** Sea cargo is priced by container or by cubic measurement, while air cargo is priced on chargeable weight. Bulky light cargo is therefore the worst case for air.",
                    "**Cargo type.** Hazardous, temperature-sensitive and fragile goods need extra handling. Extra handling means extra cost, and it also narrows the pool of carriers willing to take the booking.",
                    "**Scope of the quote.** A port-to-port rate and a door-to-door rate are not comparable numbers. Always check whether pickup, import clearance and final delivery sit inside the figure.",
                    "**Timing.** Booking against a sailing three weeks out gives you options. Booking against one leaving in three days gives you whatever is left.",
                ],
            },
            {
                type: "p",
                text: "HM Cargo Services does not publish prices anywhere on its site. Pricing is quoted per shipment, and the site states it responds with a route, a timeline and a quote, usually the same day. You submit the details through the [Request a Quote form](https://hmcargoservices.com/quote).",
            },
            {
                type: "h2",
                id: "how-do-you-compare-cargo-companies-in-dubai-for-this-lane",
                text: "How do you compare cargo companies in Dubai for this lane?",
            },
            {
                type: "p",
                text: "Compare providers on lane familiarity, coverage of the whole journey, communication structure and how they handle documentation. Price is easy to compare and tells you the least. A cheap quote that stops at the port and a complete quote that reaches the consignee's door are answering two different questions.",
            },
            {
                type: "table",
                headers: ["What to check", "What to look for", "Why it matters"],
                rows: [
                    [
                        "Lane familiarity",
                        "Lebanon named as a core market, not an occasional favour",
                        "Regular lanes come with established handling routines",
                    ],
                    [
                        "Journey coverage",
                        "Ground, air, sea and warehousing under one operation",
                        "Fewer handoffs between separate companies means fewer gaps",
                    ],
                    [
                        "Point of contact",
                        "A named person on your shipment",
                        "You skip the queue when something needs deciding",
                    ],
                    [
                        "Documentation help",
                        "Guidance on what the goods need before packing",
                        "Paperwork errors are the leading cause of delay",
                    ],
                    [
                        "Quote turnaround",
                        "A route, a timeline and a price together",
                        "A bare number without a route is not a plan",
                    ],
                    [
                        "Tracking",
                        "Reliable status updates through the journey",
                        "Silence is what makes shippers anxious, not distance",
                    ],
                ],
            },
            {
                type: "p",
                text: "Use this as a scorecard rather than a wish list. A provider that scores well on four of six is a reasonable choice. One that cannot answer the coverage question at all is telling you something useful.",
            },
            {
                type: "h2",
                id: "what-should-you-ask-before-you-book",
                text: "What should you ask before you book?",
            },
            {
                type: "p",
                text: "Ask about scope, contacts, documents and timing before you commit to any provider. These four areas produce nearly every unpleasant surprise on the Dubai to Lebanon route. Vague messages get vague answers, so put the questions in writing and keep the replies.",
            },
            {
                type: "ul",
                items: [
                    "**Does this quote cover door-to-door, or does it stop at the port?** The answer changes your total cost and who you chase when the cargo lands.",
                    "**Who is my named contact once the booking is confirmed?** A named operations contact is very different from a shared inbox.",
                    "**Exactly which documents do you need from me, and by when?** Get the list before you pack, because repacking to match paperwork is miserable work.",
                    "**What happens if my consignee is unreachable on arrival?** Storage charges accrue quietly, so agree a fallback contact upfront.",
                    "**How will I receive status updates, and how often?** Set the expectation early rather than asking for news later.",
                    "**Can you handle the road leg inside Lebanon?** Ground transportation at the destination end is a separate capability from the sea or air leg.",
                    "**What is the realistic booking deadline for my target departure?** Two to three weeks is the standard freight guidance, and special cargo needs longer.",
                ],
            },
            {
                type: "h2",
                id: "why-hm-cargo-services-fits-the-dubai-to-lebanon-lane",
                text: "Why HM CARGO SERVICES fits the Dubai to Lebanon lane",
            },
            {
                type: "p",
                text: "HM Cargo Services scores well against that comparison framework because Lebanon is one of its primary operating markets, not an occasional route. The company operates from Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai. Its stated coverage is the UAE, Lebanon, Syria and worldwide on request.",
            },
            {
                type: "p",
                text: "Six services cover the stages of this journey: International Cargo for sea and air freight, Ground Transportation, Warehousing and Handling, Shipment Coordination across carriers and customs and destinations, Cargo Tracking, and Business Logistics for repeat and enterprise shipments. Because those sit under one operation, you are not stitching together separate suppliers for the pickup, the main leg and the final delivery.",
            },
            {
                type: "p",
                text: "The process HM Cargo Services publishes has three steps. Step 01 is Request, where you share cargo details and destinations. Step 02 is Coordinate, covering pickup, handling, documentation and every handoff with one point of contact. Step 03 is Deliver.",
            },
            {
                type: "p",
                text: "Communication is structured around a named contact rather than a queue. The site states that every quote request is answered by a named operations contact, and that each active shipment has a direct contact. Two numbers are used for operations, one Lebanese and one Emirati, which suits a lane with parties at both ends.",
            },
            {
                type: "p",
                text: "Who this is a strong fit for:",
            },
            {
                type: "ul",
                items: [
                    "Businesses moving stock or equipment between the UAE and Lebanon on a repeat basis",
                    "Shippers who want the pickup, main leg and destination road leg coordinated by one operation",
                    "Anyone who wants a named person on the shipment instead of a ticket number",
                    "Shippers weighing sea against air and wanting both options quoted properly",
                    "Individuals sending household or personal cargo who need documentation guidance first",
                ],
            },
            {
                type: "p",
                text: "You can read the company background on the [about page](https://hmcargoservices.com/about) and the full lane list on the [coverage page](https://hmcargoservices.com/coverage).",
            },
            {
                type: "h2",
                id: "frequently-asked-questions-about-shipping-dubai-to-lebanon",
                text: "Frequently Asked Questions About Shipping Dubai to Lebanon",
            },
            {
                type: "p",
                text: "Shippers on the Dubai to Lebanon route ask the same handful of questions before booking, and most concern coverage, contact and responsibility at the destination end. The answers here come from what HM Cargo Services publishes. Where the site does not settle a point, the answer says how to confirm it directly.",
            },
            {
                type: "h2",
                id: "the-verdict-how-to-start-your-dubai-to-lebanon-shipment",
                text: "The verdict: how to start your Dubai to Lebanon shipment",
            },
            {
                type: "p",
                text: "Get the cargo details right first, choose sea or air against a real deadline, and pick a provider who covers the whole journey rather than one leg. HM Cargo Services fits shippers who want the UAE and Lebanon lane handled as a single coordinated run with a named contact. Prices are quoted per shipment.",
            },
            {
                type: "p",
                text: "Start here:",
            },
            {
                type: "ul",
                items: [
                    "Send your cargo details through the [Request a Quote form](https://hmcargoservices.com/quote) for a route, a timeline and a quote, usually the same day",
                    "Message the team on [WhatsApp](https://wa.me/971521530190) if you would rather ask a question before filling anything in",
                    "Use the [contact page](https://hmcargoservices.com/contact) or email info@hmcargoservices.com for general enquiries",
                    "Email quotes@hmcargoservices.com when you already have the weights, dimensions and destination ready",
                ],
            },
        ],
        faq: [
            {
                q: "Do you regularly ship cargo from Dubai to Lebanon?",
                a: "HM Cargo Services names the UAE, Lebanon and Syria as its primary operating markets, and its International Cargo service covers sea and air freight on regional and international lanes. Schedule frequency and shipment volumes are not published on the site. Ask the operations team for current departure options when you send your cargo details.",
            },
            {
                q: "Can I reach your team on a Lebanese number if I am calling from Beirut?",
                a: "Two numbers are listed and HM Cargo Services states that both are used for operations. The Lebanese-code number is +961 81 059 063 and the UAE-code number is +971 52 153 0190. A caller in Lebanon can therefore use the local one.",
            },
            {
                q: "Can I send cargo by sea to Lebanon and also by air if it is urgent?",
                a: "International Cargo at HM Cargo Services includes both sea and air freight. Heavy or bulky consignments usually go by sea, whereas urgent and high-value goods are the normal case for air. Share your deadline and dimensions so both options can be priced against your actual cargo.",
            },
            {
                q: "Who handles the pickup in Dubai and the delivery at the Lebanese end?",
                a: "Ground Transportation and Shipment Coordination are two of the six services HM Cargo Services offers. Coordination covers pickup, handling, documentation and every handoff, run through a single point of contact across carriers, customs and destinations. Agree the precise scope for your shipment before you book, since coverage depends on what you arrange.",
            },
            {
                q: "What details do I need to give you about the Lebanese consignee?",
                a: "The published quote form collects full name, company, email, phone, origin, destination, cargo type, estimated weight or volume and additional details. Requirements specific to a Lebanese consignee are not listed on the site. Submit a request or write to quotes@hmcargoservices.com, and the named operations contact will confirm what is needed.",
            },
            {
                q: "Can you arrange the road leg once the cargo lands in Lebanon?",
                a: "Ground Transportation is a named HM Cargo Services offering, and the company describes ground, air, sea and warehousing as one coordinated network. No specific Lebanese routes or cities are published. Include the final delivery address with your enquiry, and the reply comes back with a route and a timeline.",
            },
        ],
        jsonLd: {
            "@context": "https://schema.org",
            "@graph": [
                {
                    "@id": "https://hmcargoservices.com/#website",
                    "@type": "WebSite",
                    inLanguage: "en",
                    name: "HM CARGO SERVICES",
                    publisher: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    url: "https://hmcargoservices.com/",
                },
                {
                    "@id": "https://hmcargoservices.com/#business",
                    "@type": "LocalBusiness",
                    address: {
                        "@type": "PostalAddress",
                        addressLocality: "Dubai, UAE",
                        addressRegion: "Al Murar, Deira",
                        streetAddress: "Office 404, Awqaf Building, Street 8",
                    },
                    description:
                        "HM Cargo Services is a Dubai-based logistics operator offering international sea and air freight, ground transportation, warehousing and handling, shipment coordination, cargo tracking, and business logistics programs. Its core trade lanes are the UAE, Lebanon, and Syria, with worldwide shipments arranged on request.",
                    name: "HM CARGO SERVICES",
                    url: "https://hmcargoservices.com/",
                },
                {
                    "@id": "https://hmcargoservices.com/blog/shipping-dubai-to-lebanon#article",
                    "@type": "Article",
                    about: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    articleSection: "International freight from the UAE",
                    author: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    dateModified: "2026-08-16",
                    datePublished: "2026-08-16",
                    description:
                        "A practical guide to shipping Dubai to Lebanon: sea or air, booking lead times, documents, costs and what to ask before you hand over cargo.",
                    headline: "Shipping Dubai to Lebanon: Complete Cargo Guide",
                    inLanguage: "en",
                    isPartOf: {
                        "@id": "https://hmcargoservices.com/#website",
                    },
                    mainEntityOfPage: {
                        "@id": "https://hmcargoservices.com/blog/shipping-dubai-to-lebanon",
                        "@type": "WebPage",
                    },
                    publisher: {
                        "@id": "https://hmcargoservices.com/#business",
                    },
                    wordCount: 2977,
                },
                {
                    "@id": "https://hmcargoservices.com/blog/shipping-dubai-to-lebanon#breadcrumb",
                    "@type": "BreadcrumbList",
                    itemListElement: [
                        {
                            "@type": "ListItem",
                            item: "https://hmcargoservices.com/",
                            name: "HM CARGO SERVICES",
                            position: 1,
                        },
                        {
                            "@type": "ListItem",
                            item: "https://hmcargoservices.com/blog/",
                            name: "Blog",
                            position: 2,
                        },
                        {
                            "@type": "ListItem",
                            item: "https://hmcargoservices.com/blog/shipping-dubai-to-lebanon",
                            name: "Dubai to Lebanon Cargo Shipping, Start to Finish",
                            position: 3,
                        },
                    ],
                },
                {
                    "@id": "https://hmcargoservices.com/blog/shipping-dubai-to-lebanon#faq",
                    "@type": "FAQPage",
                    mainEntity: [
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "HM Cargo Services names the UAE, Lebanon and Syria as its primary operating markets, and its International Cargo service covers sea and air freight on regional and international lanes. Schedule frequency and shipment volumes are not published on the site. Ask the operations team for current departure options when you send your cargo details.",
                            },
                            name: "Do you regularly ship cargo from Dubai to Lebanon?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "Two numbers are listed and HM Cargo Services states that both are used for operations. The Lebanese-code number is +961 81 059 063 and the UAE-code number is +971 52 153 0190. A caller in Lebanon can therefore use the local one.",
                            },
                            name: "Can I reach your team on a Lebanese number if I am calling from Beirut?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "International Cargo at HM Cargo Services includes both sea and air freight. Heavy or bulky consignments usually go by sea, whereas urgent and high-value goods are the normal case for air. Share your deadline and dimensions so both options can be priced against your actual cargo.",
                            },
                            name: "Can I send cargo by sea to Lebanon and also by air if it is urgent?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "Ground Transportation and Shipment Coordination are two of the six services HM Cargo Services offers. Coordination covers pickup, handling, documentation and every handoff, run through a single point of contact across carriers, customs and destinations. Agree the precise scope for your shipment before you book, since coverage depends on what you arrange.",
                            },
                            name: "Who handles the pickup in Dubai and the delivery at the Lebanese end?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "The published quote form collects full name, company, email, phone, origin, destination, cargo type, estimated weight or volume and additional details. Requirements specific to a Lebanese consignee are not listed on the site. Submit a request or write to quotes@hmcargoservices.com, and the named operations contact will confirm what is needed.",
                            },
                            name: "What details do I need to give you about the Lebanese consignee?",
                        },
                        {
                            "@type": "Question",
                            acceptedAnswer: {
                                "@type": "Answer",
                                text: "Ground Transportation is a named HM Cargo Services offering, and the company describes ground, air, sea and warehousing as one coordinated network. No specific Lebanese routes or cities are published. Include the final delivery address with your enquiry, and the reply comes back with a route and a timeline.",
                            },
                            name: "Can you arrange the road leg once the cargo lands in Lebanon?",
                        },
                    ],
                },
            ],
        },
    },
    {
        slug: "preparing-cargo-for-international-shipping",
        title: "Preparing Your Cargo for International Shipping",
        excerpt:
            "The single biggest cause of delayed shipments isn't distance — it's how the cargo left the origin. Here's how to make sure yours travels ready.",
        category: "Shipping Guides",
        author: "HM Cargo Editorial",
        publishedAt: "2026-04-12",
        readingMinutes: 7,
        cover: container,
        featured: false,
        body: [
            {
                type: "p",
                text: "Every international shipment carries a story from the moment it leaves your facility. That story is written in packaging, paperwork, and the small operational decisions you make before the container doors close. Get those decisions right and the rest of the journey tends to take care of itself.",
            },
            {
                type: "h2",
                id: "packaging",
                text: "Packaging that survives the route, not just the depot",
            },
            {
                type: "p",
                text: "Cargo that only has to reach a nearby warehouse can tolerate lighter packaging. Cargo that will be handled by four carriers, two ports, and a customs inspection cannot. Design your packaging for the worst leg of the journey, not the best one.",
            },
            {
                type: "ul",
                items: [
                    "Match pallet dimensions to your destination market's standard footprint.",
                    "Use edge protectors and corner boards for anything stacked above shoulder height.",
                    "Shrink-wrap in the direction of expected motion — vertical for road, cross-braced for sea.",
                    "Photograph every finished pallet before it leaves your dock.",
                ],
            },
            {
                type: "h2",
                id: "documentation",
                text: "Documentation is a schedule, not a folder",
            },
            {
                type: "p",
                text: "Commercial invoices, packing lists, certificates of origin, and any product-specific certifications should be ready before pickup, not before departure. Every hour of paperwork that happens after the cargo is on the move is an hour the shipment is standing still.",
            },
            {
                type: "quote",
                text: "The cheapest lane in the world is worthless if your paperwork misses the vessel.",
            },
            {
                type: "h2",
                id: "handoffs",
                text: "Own the handoffs, not just the endpoints",
            },
            {
                type: "p",
                text: "Most cargo problems happen between organizations, not inside them. Know exactly who signs for your cargo at each transfer, what condition it should be in, and how you'll be notified if anything is off. A five-minute call at each handoff prevents five-day disputes at delivery.",
            },
        ],
        faq: [
            {
                q: "How far in advance should I book an international shipment?",
                a: "For standard freight, plan on booking 2–3 weeks before your target sailing date. For hazardous, oversized, or seasonally constrained cargo, allow 4–6 weeks.",
            },
            {
                q: "Do I need a customs broker for every international shipment?",
                a: "Most destinations legally allow self-clearance, but a licensed broker familiar with your product category almost always pays for themselves in avoided delays, misclassifications, and duty overpayments.",
            },
            {
                q: "What's the most common cause of shipment delays?",
                a: "Incomplete or inconsistent documentation. The physical cargo is rarely the problem — the paperwork attached to it usually is.",
            },
        ],
    },
];

export function getPost(slug: string) {
    return posts.find((p) => p.slug === slug);
}

export function relatedPosts(slug: string, n = 3) {
    return posts.filter((p) => p.slug !== slug).slice(0, n);
}
