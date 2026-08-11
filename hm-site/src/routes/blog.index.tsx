import { createFileRoute, Link } from "@tanstack/react-router";
import { useState } from "react";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import { posts, type BlogCategory } from "@/lib/blog-data";

const CATEGORIES: (BlogCategory | "All")[] = [
    "All",
    "Shipping Guides",
    "Cargo Updates",
    "Customs",
    "Logistics",
    "Business Tips",
];

export const Route = createFileRoute("/blog/")({
    head: () => ({
        meta: [
            { title: "Blog — HM Cargo Services" },
            {
                name: "description",
                content:
                    "Insights from the HM Cargo operation — shipping guides, cargo updates, customs, logistics, and business tips.",
            },
            { name: "publisher", content: "Nabel Al Chaar — Founder & Digital Growth Strategist" },
            { name: "robots", content: "index, follow" },
            {
                name: "keywords",
                content:
                    "cargo blog, shipping guides, logistics insights, international freight updates, customs tips",
            },
            { property: "og:title", content: "Blog — HM Cargo Services" },
            { property: "og:description", content: "Insights from the HM Cargo operation." },
            { property: "og:url", content: "https://hmcargoservices.com/blog" },
            { property: "og:image", content: "https://hmcargoservices.com/og-image.jpg" },
            { name: "twitter:image", content: "https://hmcargoservices.com/og-image.jpg" },
        ],
        links: [{ rel: "canonical", href: "https://hmcargoservices.com/blog" }],
    }),
    component: BlogIndex,
});

function BlogIndex() {
    const [cat, setCat] = useState<(typeof CATEGORIES)[number]>("All");
    const featured = posts.find((p) => p.featured);
    const filtered = posts.filter(
        (p) => (cat === "All" ? true : p.category === cat) && !p.featured,
    );

    return (
        <div className="bg-[#050607]">
            <SiteNav />
            <main id="main" className="pt-32 pb-28">
                <section className="mx-auto max-w-[1440px] px-6 lg:px-10 py-16">
                    <p className="eyebrow">Journal</p>
                    <h1 className="mt-5 font-display text-6xl lg:text-8xl">
                        Insights from the operation.
                    </h1>
                    <p className="mt-8 max-w-2xl text-lg text-muted-foreground">
                        Shipping guides, cargo updates, customs, logistics, and business tips —
                        written by the people who move the cargo.
                    </p>
                </section>

                {featured && (
                    <section className="mx-auto max-w-[1440px] px-6 lg:px-10 pb-16">
                        <Link
                            to="/blog/$slug"
                            params={{ slug: featured.slug }}
                            className="group grid gap-8 lg:grid-cols-2 border border-[color:var(--border)] bg-[#0b0d10] hover:border-[color:var(--accent)] transition-colors"
                        >
                            <div className="aspect-[16/10] lg:aspect-auto overflow-hidden">
                                <img
                                    src={featured.cover}
                                    alt=""
                                    className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                                />
                            </div>
                            <div className="p-8 lg:p-12 flex flex-col justify-center">
                                <p className="text-[0.65rem] font-mono uppercase tracking-[0.2em] text-[color:var(--accent)]">
                                    Featured · {featured.category}
                                </p>
                                <h2 className="mt-4 font-display text-4xl lg:text-5xl">
                                    {featured.title}
                                </h2>
                                <p className="mt-5 text-base text-muted-foreground">
                                    {featured.excerpt}
                                </p>
                                <p className="mt-8 text-xs tracking-[0.14em] uppercase text-[color:var(--text-dim)]">
                                    {featured.author} · {featured.readingMinutes} min read
                                </p>
                            </div>
                        </Link>
                    </section>
                )}

                <section className="mx-auto max-w-[1440px] px-6 lg:px-10">
                    <div className="flex flex-wrap gap-2 border-b border-[color:var(--border)] pb-6">
                        {CATEGORIES.map((c) => (
                            <button
                                key={c}
                                onClick={() => setCat(c)}
                                className={`px-4 py-2 text-xs font-medium tracking-[0.14em] uppercase transition-colors ${
                                    cat === c
                                        ? "bg-[color:var(--accent)] text-[#0a0a0a]"
                                        : "text-muted-foreground hover:text-foreground"
                                }`}
                            >
                                {c}
                            </button>
                        ))}
                    </div>

                    <div className="mt-10 grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                        {filtered.map((p) => (
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
                        {filtered.length === 0 && (
                            <div className="col-span-full border border-dashed border-[color:var(--border-strong)] p-16 text-center text-muted-foreground">
                                More insights coming soon in this category.
                            </div>
                        )}
                    </div>
                </section>
            </main>
            <SiteFooter />
        </div>
    );
}
