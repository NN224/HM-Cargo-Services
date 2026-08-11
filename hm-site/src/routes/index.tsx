import { createFileRoute } from "@tanstack/react-router";
import { CinematicJourney } from "@/components/cinematic-journey";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import {
    IntroSection,
    ServicesSection,
    AdvantagesSection,
    CoverageSection,
    ProcessSection,
    BlogPreviewSection,
    QuoteCTASection,
} from "@/components/home-sections";

export const Route = createFileRoute("/")({
    head: () => ({
        meta: [
            { title: "HM Cargo Services — Moving your cargo forward" },
            {
                name: "description",
                content:
                    "Reliable logistics, clear visibility, and careful handling from origin to destination. HM Cargo Services operates international, ground, and warehousing solutions.",
            },
            { name: "publisher", content: "Nabel Al Chaar — Founder & Digital Growth Strategist" },
            { name: "robots", content: "index, follow" },
            {
                name: "keywords",
                content:
                    "HM Cargo Services, cargo shipping, international freight forwarding, logistics UAE, Dubai cargo, Syria cargo, Lebanon freight forwarding, warehousing, ground transport, supply chain",
            },
            { property: "og:title", content: "HM Cargo Services — Moving your cargo forward" },
            {
                property: "og:description",
                content:
                    "Reliable logistics, clear visibility, and careful handling from origin to destination.",
            },
            { property: "og:url", content: "https://hmcargoservices.com/" },
            { property: "og:image", content: "https://hmcargoservices.com/og-image.jpg" },
            { name: "twitter:image", content: "https://hmcargoservices.com/og-image.jpg" },
        ],
        links: [{ rel: "canonical", href: "https://hmcargoservices.com/" }],
    }),
    component: HomePage,
});

function HomePage() {
    return (
        <div className="bg-[#050607]">
            <SiteNav />
            <main id="main">
                <CinematicJourney />
                <IntroSection />
                <ServicesSection />
                <AdvantagesSection />
                <CoverageSection />
                <ProcessSection />
                <BlogPreviewSection />
                <QuoteCTASection />
            </main>
            <SiteFooter />
        </div>
    );
}
