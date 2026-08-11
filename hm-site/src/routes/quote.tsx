import { createFileRoute } from "@tanstack/react-router";
import { useState } from "react";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import { Check } from "lucide-react";

export const Route = createFileRoute("/quote")({
    head: () => ({
        meta: [
            { title: "Request a Quote — HM Cargo Services" },
            {
                name: "description",
                content:
                    "Tell us about your cargo, origin, and destination. HM Cargo Services will respond with a route, timeline, and quote — usually the same day.",
            },
            { name: "publisher", content: "Nabel Al Chaar — Founder & Digital Growth Strategist" },
            { name: "robots", content: "index, follow" },
            {
                name: "keywords",
                content:
                    "cargo quote, freight shipping quote, international cargo rates, shipping estimation Dubai",
            },
            { property: "og:title", content: "Request a Quote — HM Cargo Services" },
            { property: "og:description", content: "Tell us where your cargo needs to go." },
            { property: "og:url", content: "https://hmcargoservices.com/quote" },
            { property: "og:image", content: "https://hmcargoservices.com/og-image.jpg" },
            { name: "twitter:image", content: "https://hmcargoservices.com/og-image.jpg" },
        ],
        links: [{ rel: "canonical", href: "https://hmcargoservices.com/quote" }],
    }),
    component: QuotePage,
});

function Field({
    label,
    ...props
}: { label: string } & React.InputHTMLAttributes<HTMLInputElement>) {
    return (
        <label className="block">
            <span className="block text-[0.7rem] font-medium tracking-[0.18em] uppercase text-muted-foreground">
                {label}
            </span>
            <input
                {...props}
                className="mt-2 block w-full border border-[color:var(--border-strong)] bg-[color:var(--surface-2)] px-4 py-3 text-sm text-foreground focus:border-[color:var(--accent)] focus:outline-none"
            />
        </label>
    );
}

function QuotePage() {
    const [sent, setSent] = useState(false);
    return (
        <div className="bg-[#050607]">
            <SiteNav />
            <main id="main" className="pt-32 pb-28">
                <section className="mx-auto max-w-[1200px] px-6 lg:px-10 py-16">
                    <p className="eyebrow">Request a Quote</p>
                    <h1 className="mt-5 font-display text-6xl lg:text-8xl max-w-3xl">
                        Tell us where your cargo needs to go.
                    </h1>
                    <p className="mt-8 max-w-xl text-lg text-muted-foreground">
                        Share a few details about the shipment. We'll respond with a route,
                        timeline, and quote — usually the same day.
                    </p>
                </section>
                <section className="mx-auto max-w-[900px] px-6 lg:px-10">
                    {sent ? (
                        <div className="hairline bg-[#0b0d10] p-12 text-center">
                            <Check className="mx-auto h-8 w-8 text-[color:var(--accent)]" />
                            <h2 className="mt-6 font-display text-3xl">Request received.</h2>
                            <p className="mt-3 text-muted-foreground">
                                A named operations contact will be in touch shortly.
                            </p>
                        </div>
                    ) : (
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                setSent(true);
                            }}
                            className="hairline bg-[#0b0d10] p-8 lg:p-12 space-y-6"
                        >
                            <div className="grid gap-6 sm:grid-cols-2">
                                <Field label="Full name" name="name" required autoComplete="name" />
                                <Field label="Company" name="company" autoComplete="organization" />
                                <Field
                                    label="Email"
                                    type="email"
                                    name="email"
                                    required
                                    autoComplete="email"
                                />
                                <Field label="Phone" type="tel" name="phone" autoComplete="tel" />
                                <Field
                                    label="Origin"
                                    name="origin"
                                    required
                                    placeholder="City, country"
                                />
                                <Field
                                    label="Destination"
                                    name="destination"
                                    required
                                    placeholder="City, country"
                                />
                                <Field
                                    label="Cargo type"
                                    name="cargo"
                                    placeholder="Pallets, container, general"
                                />
                                <Field label="Estimated weight / volume" name="weight" />
                            </div>
                            <label className="block">
                                <span className="block text-[0.7rem] font-medium tracking-[0.18em] uppercase text-muted-foreground">
                                    Additional details
                                </span>
                                <textarea
                                    name="notes"
                                    rows={4}
                                    className="mt-2 block w-full border border-[color:var(--border-strong)] bg-[color:var(--surface-2)] px-4 py-3 text-sm text-foreground focus:border-[color:var(--accent)] focus:outline-none"
                                />
                            </label>
                            <div className="flex flex-wrap items-center justify-between gap-4 pt-2">
                                <p className="text-xs text-[color:var(--text-dim)]">
                                    We reply to every request from a named operations contact.
                                </p>
                                <button type="submit" className="btn-primary">
                                    Send request
                                </button>
                            </div>
                        </form>
                    )}
                </section>
            </main>
            <SiteFooter />
        </div>
    );
}
