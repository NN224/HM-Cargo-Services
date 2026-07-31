export type BlogCategory =
  | "Shipping Guides"
  | "Cargo Updates"
  | "Customs"
  | "Logistics"
  | "Business Tips";

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
  body: Array<
    | { type: "p"; text: string }
    | { type: "h2"; text: string; id: string }
    | { type: "ul"; items: string[] }
    | { type: "quote"; text: string }
  >;
  faq: { q: string; a: string }[];
};

import container from "@/assets/scenes/scene-container.jpg";

export const posts: BlogPost[] = [
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
    featured: true,
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
