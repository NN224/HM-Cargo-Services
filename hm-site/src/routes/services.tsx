import { createFileRoute, Link } from "@tanstack/react-router";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import { SERVICES } from "@/components/home-sections";
import { QuoteCTASection } from "@/components/home-sections";

export const Route = createFileRoute("/services")({
  head: () => ({
    meta: [
      { title: "Services — HM Cargo Services" },
      { name: "description", content: "International cargo, ground transportation, warehousing, shipment coordination, tracking, and business logistics — coordinated as one operation." },
      { property: "og:title", content: "Services — HM Cargo Services" },
      { property: "og:description", content: "A complete cargo operation, coordinated as one." },
      { property: "og:url", content: "/services" },
    ],
    links: [{ rel: "canonical", href: "/services" }],
  }),
  component: ServicesPage,
});

function ServicesPage() {
  return (
    <div className="bg-[#050607]">
      <SiteNav />
      <main id="main" className="pt-32">
        <section className="mx-auto max-w-[1440px] px-6 lg:px-10 py-16 lg:py-24">
          <p className="eyebrow">Services</p>
          <h1 className="mt-5 font-display text-6xl lg:text-8xl max-w-4xl">Every stage of the shipment. Under one operation.</h1>
          <p className="mt-8 max-w-2xl text-lg text-muted-foreground">
            HM Cargo Services covers the full journey — from the first pickup to the final delivery — with the same discipline at every stage.
          </p>
        </section>
        <section className="mx-auto max-w-[1440px] px-6 lg:px-10 pb-28">
          <div className="grid gap-px border border-[color:var(--border)] bg-[color:var(--border)] md:grid-cols-2">
            {SERVICES.map((s) => (
              <article key={s.title} className="bg-[#0b0d10] p-10 lg:p-14">
                <div className="flex h-12 w-12 items-center justify-center border border-[color:var(--border-strong)] bg-[color:var(--surface-2)] text-[color:var(--accent)]">
                  <s.Icon className="h-5 w-5" strokeWidth={1.5} />
                </div>
                <h2 className="mt-8 font-display text-3xl lg:text-4xl">{s.title}</h2>
                <p className="mt-5 max-w-md text-base leading-relaxed text-muted-foreground">{s.body}</p>
                <Link to="/quote" className="mt-8 inline-flex text-xs font-medium tracking-[0.18em] uppercase text-foreground hover:text-[color:var(--accent)]">
                  Request for this service →
                </Link>
              </article>
            ))}
          </div>
        </section>
        <QuoteCTASection />
      </main>
      <SiteFooter />
    </div>
  );
}
