import { Link } from "@tanstack/react-router";
import { Facebook, Instagram } from "lucide-react";
import fullLogo from "@/assets/hm-logo-symbol.png";

export function SiteFooter() {
    return (
        <footer className="relative border-t border-[color:var(--border)] bg-[#050607] pt-20 pb-10">
            <div className="mx-auto max-w-[1440px] px-6 lg:px-10">
                <div className="grid gap-14 lg:grid-cols-[1.4fr_1fr_1fr_1fr]">
                    <div>
                        <img
                            src={fullLogo}
                            alt="HM Cargo Services"
                            width={220}
                            height={220}
                            className="h-28 w-auto object-contain"
                        />
                        <p className="mt-6 max-w-sm text-sm leading-relaxed text-muted-foreground">
                            Moving cargo forward. Reliable logistics, clear visibility, and careful
                            handling from origin to destination.
                        </p>
                        <p className="eyebrow mt-6">Contact</p>
                        <ul className="mt-3 space-y-1 text-sm text-muted-foreground">
                            <li>
                                Office 404, Awqaf Building, Street 8, Al Murar, Deira, Dubai, UAE
                            </li>
                            <li>
                                <a
                                    href="tel:+96181059063"
                                    className="transition-colors hover:text-foreground"
                                >
                                    +961 81 059 063
                                </a>
                            </li>
                            <li>
                                <a
                                    href="tel:+971521530190"
                                    className="transition-colors hover:text-foreground"
                                >
                                    +971 52 153 0190
                                </a>
                            </li>
                            <li>
                                <a
                                    href="mailto:info@hmcargoservices.com"
                                    className="transition-colors hover:text-foreground"
                                >
                                    info@hmcargoservices.com
                                </a>
                            </li>
                        </ul>
                        <div className="mt-6 flex items-center gap-3">
                            <a
                                href="https://www.facebook.com/profile.php?id=61577769857365"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="HM Cargo Services Facebook"
                                className="flex h-9 w-9 items-center justify-center rounded-md border border-[color:var(--border)] bg-[#0b0d10] text-muted-foreground transition-colors hover:border-[color:var(--accent)] hover:text-foreground"
                            >
                                <Facebook className="h-4 w-4" />
                            </a>
                            <a
                                href="https://www.instagram.com/hm_cargo2026"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="HM Cargo Services Instagram"
                                className="flex h-9 w-9 items-center justify-center rounded-md border border-[color:var(--border)] bg-[#0b0d10] text-muted-foreground transition-colors hover:border-[color:var(--accent)] hover:text-foreground"
                            >
                                <Instagram className="h-4 w-4" />
                            </a>
                        </div>
                    </div>

                    <FooterCol
                        title="Services"
                        items={[
                            { label: "International Cargo", to: "/services" },
                            { label: "Ground Transportation", to: "/services" },
                            { label: "Warehousing & Handling", to: "/services" },
                            { label: "Shipment Coordination", to: "/services" },
                            { label: "Cargo Tracking", to: "/services" },
                            { label: "Business Logistics", to: "/services" },
                        ]}
                    />
                    <FooterCol
                        title="Company"
                        items={[
                            { label: "About", to: "/about" },
                            { label: "Coverage", to: "/coverage" },
                            { label: "Blog", to: "/blog" },
                            { label: "Contact", to: "/contact" },
                            { label: "Request a Quote", to: "/quote" },
                        ]}
                    />
                    <FooterCol
                        title="Legal"
                        items={[
                            { label: "Privacy Policy", to: "/" },
                            { label: "Terms of Service", to: "/" },
                            { label: "Cookie Policy", to: "/" },
                        ]}
                    />
                </div>

                <div className="mt-16 flex flex-col gap-4 border-t border-[color:var(--border)] pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-xs tracking-[0.14em] uppercase text-[color:var(--text-dim)]">
                        © {new Date().getFullYear()} HM Cargo Services. All rights reserved.
                    </p>
                    <p className="text-xs tracking-[0.14em] uppercase text-[color:var(--text-dim)]">
                        Cargo without uncertainty.
                    </p>
                </div>
            </div>
        </footer>
    );
}

function FooterCol({ title, items }: { title: string; items: { label: string; to: string }[] }) {
    return (
        <div>
            <h4 className="eyebrow text-foreground/70">{title}</h4>
            <ul className="mt-5 space-y-3">
                {items.map((i) => (
                    <li key={i.label}>
                        <Link
                            to={i.to}
                            className="text-sm text-muted-foreground hover:text-foreground transition-colors"
                        >
                            {i.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
