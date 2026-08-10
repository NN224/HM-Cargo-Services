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
        slug: "shipping-dubai-to-lebanon",
        title: "Dubai to Lebanon Cargo Shipping, Start to Finish",
        excerpt:
            "A practical guide to shipping Dubai to Lebanon: sea or air, booking lead times, documents, costs and what to ask before you hand over cargo.",
        category: "Shipping Guides",
        author: "HM Cargo Editorial",
        publishedAt: "2026-08-16",
        readingMinutes: 12,
        cover: "/media/blog/shipping-dubai-to-lebanon-featured.webp",
        featured: true,
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
