import { createFileRoute } from "@tanstack/react-router";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";

export const Route = createFileRoute("/contact")({
  head: () => ({
    meta: [
      { title: "Contact — HM Cargo Services" },
      { name: "description", content: "Talk to the HM Cargo Services team about your cargo, route, or ongoing logistics needs." },
      { property: "og:title", content: "Contact — HM Cargo Services" },
      { property: "og:description", content: "Talk to the team that moves your cargo." },
      { property: "og:url", content: "/contact" },
    ],
    links: [{ rel: "canonical", href: "/contact" }],
  }),
  component: ContactPage,
});

function ContactPage() {
  return (
    <div className="bg-[#050607]">
      <SiteNav />
      <main id="main" className="pt-32 pb-28">
        <section className="mx-auto max-w-[1200px] px-6 lg:px-10 py-16">
          <p className="eyebrow">Contact</p>
          <h1 className="mt-5 font-display text-6xl lg:text-8xl">Talk to the operation.</h1>
          <p className="mt-6 max-w-xl text-lg text-muted-foreground">A named contact will respond, not a queue.</p>
        </section>
        <section className="mx-auto max-w-[1200px] px-6 lg:px-10">
          <div className="grid gap-px border border-[color:var(--border)] bg-[color:var(--border)] md:grid-cols-3">
            {[
              {
                label: "General",
                contacts: [{ label: "info@hmcargoservices.com", href: "mailto:info@hmcargoservices.com" }],
              },
              {
                label: "Quotes",
                contacts: [{ label: "quotes@hmcargoservices.com", href: "mailto:quotes@hmcargoservices.com" }],
              },
              {
                label: "Operations",
                contacts: [
                  { label: "+961 81 059 063", href: "tel:+96181059063" },
                  { label: "+971 52 153 0190", href: "tel:+971521530190" },
                ],
              },
            ].map((card) => (
              <div key={card.label} className="bg-[#0b0d10] p-10">
                <p className="eyebrow">{card.label}</p>
                <p className="mt-5 font-display text-2xl">HM Cargo Services</p>
                <div className="mt-3 flex flex-col gap-1 text-sm text-muted-foreground">
                  {card.contacts.map((contact) => (
                    <a key={contact.href} href={contact.href} className="transition-colors hover:text-foreground">
                      {contact.label}
                    </a>
                  ))}
                </div>
              </div>
            ))}
          </div>
          <div className="mt-10 hairline p-8 lg:p-10 bg-[#0b0d10]">
            <p className="eyebrow">Office</p>
            <p className="mt-3 text-muted-foreground">
              Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE
            </p>
          </div>
        </section>
      </main>
      <SiteFooter />
    </div>
  );
}
