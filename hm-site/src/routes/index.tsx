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
      { name: "description", content: "Reliable logistics, clear visibility, and careful handling from origin to destination. HM Cargo Services operates international, ground, and warehousing solutions." },
      { property: "og:title", content: "HM Cargo Services — Moving your cargo forward" },
      { property: "og:description", content: "Reliable logistics, clear visibility, and careful handling from origin to destination." },
      { property: "og:url", content: "/" },
    ],
    links: [{ rel: "canonical", href: "/" }],
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
