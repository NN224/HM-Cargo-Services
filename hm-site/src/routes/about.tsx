import { createFileRoute } from "@tanstack/react-router";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import { QuoteCTASection } from "@/components/home-sections";

export const Route = createFileRoute("/about")({
    head: () => ({
        meta: [
            { title: "About — HM Cargo Services" },
            {
                name: "description",
                content:
                    "HM Cargo Services is a logistics operator focused on moving cargo forward with care, clarity, and reliability at every stage.",
            },
            { name: "publisher", content: "Nabel Al Chaar — Founder & Digital Growth Strategist" },
            { name: "robots", content: "index, follow" },
            {
                name: "keywords",
                content:
                    "HM Cargo Services, about HM Cargo, cargo logistics, Dubai freight forwarder, international shipping team, Nabel Al Chaar",
            },
            { property: "og:title", content: "About — HM Cargo Services" },
            {
                property: "og:description",
                content: "A logistics operator built around the cargo, not the paperwork.",
            },
            { property: "og:url", content: "https://hmcargoservices.com/about" },
            { property: "og:image", content: "https://hmcargoservices.com/og-image.jpg" },
            { name: "twitter:image", content: "https://hmcargoservices.com/og-image.jpg" },
        ],
        links: [{ rel: "canonical", href: "https://hmcargoservices.com/about" }],
    }),
    component: AboutPage,
});

function AboutPage() {
    return (
        <div className="bg-[#050607]">
            <SiteNav />
            <main id="main" className="pt-32">
                <section className="mx-auto max-w-[1200px] px-6 lg:px-10 py-16 lg:py-24">
                    <p className="eyebrow">About</p>
                    <h1 className="mt-5 font-display text-6xl lg:text-8xl max-w-4xl">
                        Built around the cargo. Not around the paperwork.
                    </h1>
                </section>
                <section className="mx-auto max-w-[1000px] px-6 lg:px-10 pb-28 space-y-8 text-lg leading-relaxed text-foreground/90">
                    <p>
                        HM Cargo Services is a logistics operator focused on one thing: moving your
                        cargo forward — with care, with clarity, and without surprises along the
                        way.
                    </p>
                    <p className="text-muted-foreground">
                        Every shipment we handle passes through a coordinated network of ground
                        transport, warehousing, and international routes. What our clients notice
                        first is the visibility. What keeps them with us is the reliability
                        underneath it.
                    </p>
                    <p className="text-muted-foreground">
                        The team behind HM Cargo Services has spent years in the operational side of
                        logistics — the yards, the docks, the paperwork, the late-night calls. That
                        experience shapes how the operation runs today, and it's why cargo entrusted
                        to HM tends to move without drama.
                    </p>
                    <p className="text-muted-foreground">
                        From regular regional movements to destination-specific international
                        requests, we coordinate each shipment around its route, cargo requirements,
                        and handoffs. Our role is to keep the people, documentation, and movement
                        aligned from origin to arrival.
                    </p>
                </section>
                <QuoteCTASection />
            </main>
            <SiteFooter />
        </div>
    );
}
