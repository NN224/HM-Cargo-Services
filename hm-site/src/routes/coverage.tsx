import { createFileRoute } from "@tanstack/react-router";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import { CoverageSection, QuoteCTASection } from "@/components/home-sections";

export const Route = createFileRoute("/coverage")({
  head: () => ({
    meta: [
      { title: "Coverage — HM Cargo Services" },
      { name: "description", content: "HM Cargo Services operates across regional and international trade corridors, with active handling capacity in the origins and destinations our clients ship between." },
      { property: "og:title", content: "Coverage — HM Cargo Services" },
      { property: "og:description", content: "A route network built around real trade lanes." },
      { property: "og:url", content: "/coverage" },
      { property: "og:image", content: "/og-image.jpg" },
      { name: "twitter:image", content: "/og-image.jpg" },
    ],
    links: [{ rel: "canonical", href: "/coverage" }],
  }),
  component: CoveragePage,
});

function CoveragePage() {
  return (
    <div className="bg-[#050607]">
      <SiteNav />
      <main id="main" className="pt-32">
        <section className="mx-auto max-w-[1200px] px-6 lg:px-10 py-16 lg:py-24">
          <p className="eyebrow">Coverage</p>
          <h1 className="mt-5 font-display text-6xl lg:text-8xl max-w-4xl">A network built around real trade lanes.</h1>
          <p className="mt-8 max-w-2xl text-lg text-muted-foreground">
            Our core routes serve the UAE, Lebanon, and Syria, supported by international
            shipping arrangements for destinations beyond the region when requested.
          </p>
        </section>
        <CoverageSection />
        <QuoteCTASection />
      </main>
      <SiteFooter />
    </div>
  );
}
