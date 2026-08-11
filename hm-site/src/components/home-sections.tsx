import { Link } from "@tanstack/react-router";
import type { LucideIcon } from "lucide-react";
import {
    Boxes,
    Truck,
    Warehouse,
    ClipboardList,
    Radar,
    Building2,
    ArrowUpRight,
    Check,
} from "lucide-react";
import { posts } from "@/lib/blog-data";

export const SERVICES: { title: string; body: string; Icon: LucideIcon }[] = [
    {
        title: "International Cargo",
        body: "Sea and air freight coordinated across regional and international lanes.",
        Icon: Boxes,
    },
    {
        title: "Ground Transportation",
        body: "Reliable road transport with consistent handling and route visibility.",
        Icon: Truck,
    },
    {
        title: "Warehousing & Handling",
        body: "Organized storage, staging, and controlled handling between legs.",
        Icon: Warehouse,
    },
    {
        title: "Shipment Coordination",
        body: "End-to-end orchestration across carriers, customs, and destinations.",
        Icon: ClipboardList,
    },
    {
        title: "Cargo Tracking",
        body: "Clear status updates from origin to arrival — no black boxes.",
        Icon: Radar,
    },
    {
        title: "Business Logistics",
        body: "Tailored supply-chain programs for repeat and enterprise shipments.",
        Icon: Building2,
    },
];

export function ServicesSection() {
    return (
        <section className="relative py-28 lg:py-40 bg-[#050607]">
            <div className="mx-auto max-w-[1440px] px-6 lg:px-10">
                <div className="grid gap-16 lg:grid-cols-[1fr_2fr] lg:items-end">
                    <div>
                        <p className="eyebrow">Services</p>
                        <h2 className="mt-4 font-display text-5xl lg:text-6xl">
                            A complete cargo operation, coordinated as one.
                        </h2>
                    </div>
                    <p className="text-base leading-relaxed text-muted-foreground lg:text-lg lg:max-w-xl lg:justify-self-end">
                        From the first pickup to the final delivery, HM Cargo Services operates
                        every stage of your shipment with the same discipline — and the same
                        visibility.
                    </p>
                </div>

                <div className="mt-16 grid gap-px border border-[color:var(--border)] bg-[color:var(--border)] md:grid-cols-2 lg:grid-cols-3">
                    {SERVICES.map((s) => (
                        <article
                            key={s.title}
                            className="group relative flex flex-col gap-6 bg-[#0b0d10] p-8 lg:p-10 min-h-[260px]"
                        >
                            <div className="flex h-11 w-11 items-center justify-center border border-[color:var(--border-strong)] bg-[color:var(--surface-2)] text-[color:var(--accent)]">
                                <s.Icon className="h-5 w-5" strokeWidth={1.5} />
                            </div>
                            <h3 className="font-display text-2xl">{s.title}</h3>
                            <p className="text-sm leading-relaxed text-muted-foreground">
                                {s.body}
                            </p>
                            <Link
                                to="/services"
                                className="mt-auto inline-flex items-center gap-2 text-xs font-medium tracking-[0.18em] uppercase text-foreground group-hover:text-[color:var(--accent)] transition-colors"
                            >
                                Learn more <ArrowUpRight className="h-3.5 w-3.5" />
                            </Link>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function IntroSection() {
    return (
        <section className="relative py-28 lg:py-40 bg-[#050607] border-t border-[color:var(--border)]">
            <div className="mx-auto max-w-[1200px] px-6 lg:px-10">
                <div className="grid gap-16 lg:grid-cols-[1fr_1.4fr] lg:items-start">
                    <div>
                        <p className="eyebrow">The Company</p>
                        <h2 className="mt-4 font-display text-5xl lg:text-6xl">
                            Built around the cargo. Not around the paperwork.
                        </h2>
                    </div>
                    <div className="space-y-6">
                        <p className="text-lg leading-relaxed text-foreground/90">
                            HM Cargo Services is a logistics operator focused on one thing: moving
                            your cargo forward — with care, with clarity, and without surprises
                            along the way.
                        </p>
                        <p className="text-base leading-relaxed text-muted-foreground">
                            Every shipment we handle passes through a coordinated network of ground
                            transport, warehousing, and international routes. What our clients
                            notice first is the visibility. What keeps them with us is the
                            reliability underneath it.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

export function AdvantagesSection() {
    const items = [
        {
            title: "Careful handling",
            body: "Every cargo type gets a handling profile matched to its route.",
        },
        { title: "Clear visibility", body: "Status updates you can rely on, not chase." },
        {
            title: "Coordinated network",
            body: "Ground, air, sea, and warehousing under one operation.",
        },
        { title: "Direct communication", body: "A named contact for every active shipment." },
    ];
    return (
        <section className="relative py-28 bg-[#0b0d10] border-y border-[color:var(--border)]">
            <div className="mx-auto max-w-[1440px] px-6 lg:px-10">
                <div className="max-w-2xl">
                    <p className="eyebrow">Why HM Cargo</p>
                    <h2 className="mt-4 font-display text-4xl lg:text-5xl">
                        Advantages that show up on every shipment.
                    </h2>
                </div>
                <div className="mt-14 grid gap-px border border-[color:var(--border)] bg-[color:var(--border)] md:grid-cols-2 lg:grid-cols-4">
                    {items.map((it) => (
                        <div key={it.title} className="bg-[#050607] p-8">
                            <Check className="h-5 w-5 text-[color:var(--accent)]" />
                            <h3 className="mt-5 font-display text-xl">{it.title}</h3>
                            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                                {it.body}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function CoverageSection() {
    return (
        <section className="relative py-28 lg:py-40 bg-[#050607]">
            <div className="mx-auto max-w-[1440px] px-6 lg:px-10">
                <div className="grid gap-14 lg:grid-cols-[1fr_1.4fr] lg:items-center">
                    <div>
                        <p className="eyebrow">Coverage</p>
                        <h2 className="mt-4 font-display text-5xl lg:text-6xl">
                            A route network built around real trade lanes.
                        </h2>
                        <p className="mt-6 text-base leading-relaxed text-muted-foreground">
                            Our primary operating markets are the UAE, Lebanon, and Syria. For
                            destinations farther afield — including the United States — we arrange
                            international shipments based on the cargo, route, and service required.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            {["UAE", "Lebanon", "Syria", "Worldwide on request"].map((region) => (
                                <span
                                    key={region}
                                    className="border border-[color:var(--border-strong)] px-4 py-2 text-xs uppercase tracking-[0.14em] text-foreground/80"
                                >
                                    {region}
                                </span>
                            ))}
                        </div>
                    </div>
                    <div className="relative aspect-[16/10] w-full overflow-hidden border border-[color:var(--border)] bg-[#0b0d10]">
                        <svg viewBox="0 0 800 500" className="absolute inset-0 h-full w-full">
                            <defs>
                                <radialGradient id="cov-glow" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" stopColor="#FF7900" stopOpacity="0.25" />
                                    <stop offset="100%" stopColor="#FF7900" stopOpacity="0" />
                                </radialGradient>
                            </defs>
                            {/* grid */}
                            {[...Array(9)].map((_, i) => (
                                <line
                                    key={`v${i}`}
                                    x1={i * 100}
                                    y1={0}
                                    x2={i * 100}
                                    y2={500}
                                    stroke="rgba(255,255,255,0.04)"
                                />
                            ))}
                            {[...Array(6)].map((_, i) => (
                                <line
                                    key={`h${i}`}
                                    x1={0}
                                    y1={i * 100}
                                    x2={800}
                                    y2={i * 100}
                                    stroke="rgba(255,255,255,0.04)"
                                />
                            ))}
                            {/* connective routes */}
                            <path
                                d="M 120 300 Q 300 180 460 260 T 720 200"
                                stroke="#FF7900"
                                strokeWidth="1.5"
                                fill="none"
                                strokeOpacity="0.7"
                            />
                            <path
                                d="M 460 260 Q 520 380 680 380"
                                stroke="#FF7900"
                                strokeWidth="1.5"
                                fill="none"
                                strokeOpacity="0.5"
                            />
                            <path
                                d="M 120 300 Q 200 400 340 420"
                                stroke="#FF7900"
                                strokeWidth="1.5"
                                fill="none"
                                strokeOpacity="0.5"
                            />
                            {[
                                [120, 300],
                                [340, 420],
                                [460, 260],
                                [680, 380],
                                [720, 200],
                            ].map(([x, y], i) => (
                                <g key={i}>
                                    <circle cx={x} cy={y} r="30" fill="url(#cov-glow)" />
                                    <circle cx={x} cy={y} r="4" fill="#FF7900" />
                                </g>
                            ))}
                        </svg>
                    </div>
                </div>
            </div>
        </section>
    );
}

export function ProcessSection() {
    const steps = [
        {
            n: "01",
            title: "Request",
            body: "Share your cargo details and destinations. We respond with a route, a timeline, and a quote — usually the same day.",
        },
        {
            n: "02",
            title: "Coordinate",
            body: "We arrange pickup, handling, documentation, and every handoff along the route. You get one contact, not a chain of emails.",
        },
        {
            n: "03",
            title: "Deliver",
            body: "Cargo arrives with the visibility that carried it — clear status, careful handling, no last-minute surprises.",
        },
    ];
    return (
        <section className="relative py-28 lg:py-40 bg-[#0b0d10] border-y border-[color:var(--border)]">
            <div className="mx-auto max-w-[1440px] px-6 lg:px-10">
                <div className="max-w-2xl">
                    <p className="eyebrow">Process</p>
                    <h2 className="mt-4 font-display text-5xl lg:text-6xl">
                        Three steps. One accountable operation.
                    </h2>
                </div>
                <div className="mt-16 grid gap-10 lg:grid-cols-3">
                    {steps.map((s) => (
                        <div
                            key={s.n}
                            className="relative border-l border-[color:var(--border-strong)] pl-8"
                        >
                            <span className="font-mono text-xs tracking-[0.2em] text-[color:var(--accent)]">
                                {s.n}
                            </span>
                            <h3 className="mt-4 font-display text-3xl">{s.title}</h3>
                            <p className="mt-4 text-base leading-relaxed text-muted-foreground">
                                {s.body}
                            </p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}

export function BlogPreviewSection() {
    const latest = posts.slice(0, 3);
    return (
        <section className="relative py-28 lg:py-40 bg-[#050607]">
            <div className="mx-auto max-w-[1440px] px-6 lg:px-10">
                <div className="flex items-end justify-between gap-8">
                    <div className="max-w-xl">
                        <p className="eyebrow">Insights</p>
                        <h2 className="mt-4 font-display text-5xl lg:text-6xl">
                            From the operation.
                        </h2>
                    </div>
                    <Link to="/blog" className="hidden sm:inline-flex btn-secondary">
                        All articles
                    </Link>
                </div>
                <div className="mt-14 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    {latest.map((p) => (
                        <Link
                            key={p.slug}
                            to="/blog/$slug"
                            params={{ slug: p.slug }}
                            className="group block border border-[color:var(--border)] bg-[#0b0d10] hover:border-[color:var(--accent)] transition-colors"
                        >
                            <div className="aspect-[16/10] overflow-hidden">
                                <img
                                    src={p.cover}
                                    alt=""
                                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                    loading="lazy"
                                />
                            </div>
                            <div className="p-6">
                                <p className="text-[0.65rem] font-mono uppercase tracking-[0.2em] text-[color:var(--accent)]">
                                    {p.category}
                                </p>
                                <h3 className="mt-3 font-display text-2xl">{p.title}</h3>
                                <p className="mt-3 text-sm text-muted-foreground line-clamp-2">
                                    {p.excerpt}
                                </p>
                                <p className="mt-6 text-xs text-[color:var(--text-dim)] tracking-[0.14em] uppercase">
                                    {p.readingMinutes} min read
                                </p>
                            </div>
                        </Link>
                    ))}
                    {latest.length < 3 &&
                        Array.from({ length: 3 - latest.length }).map((_, i) => (
                            <div
                                key={i}
                                className="border border-dashed border-[color:var(--border-strong)] p-6 flex items-center justify-center text-sm text-muted-foreground min-h-[240px]"
                            >
                                More insights coming soon
                            </div>
                        ))}
                </div>
            </div>
        </section>
    );
}

export function QuoteCTASection() {
    return (
        <section className="relative py-28 lg:py-40 bg-[#050607] border-t border-[color:var(--border)]">
            <div className="mx-auto max-w-[1200px] px-6 lg:px-10 text-center">
                <p className="eyebrow">Ready when you are</p>
                <h2 className="mt-6 font-display text-5xl lg:text-7xl">Let's move it forward.</h2>
                <p className="mx-auto mt-6 max-w-xl text-base leading-relaxed text-muted-foreground">
                    Tell us where your cargo needs to go. We'll come back with a clear route,
                    timeline, and quote — usually the same day.
                </p>
                <div className="mt-10 flex flex-wrap justify-center gap-4">
                    <Link to="/quote" className="btn-primary">
                        Request a Quote
                    </Link>
                    <Link to="/contact" className="btn-secondary">
                        Talk to the team
                    </Link>
                </div>
            </div>
        </section>
    );
}
