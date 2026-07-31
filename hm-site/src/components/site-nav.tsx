import { Link } from "@tanstack/react-router";
import { useEffect, useState } from "react";
import { Menu, X } from "lucide-react";
import symbolAsset from "@/assets/hm-logo-symbol.png";

const links = [
  { to: "/services", label: "Services" },
  { to: "/about", label: "About" },
  { to: "/coverage", label: "Coverage" },
  { to: "/blog", label: "Blog" },
  { to: "/contact", label: "Contact" },
] as const;

export function SiteNav() {
  const [scrolled, setScrolled] = useState(false);
  const [open, setOpen] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 60);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <>
      <a href="#main" className="sr-only focus:not-sr-only fixed left-4 top-4 z-[60] btn-secondary">
        Skip to content
      </a>
      <header
        className={`fixed inset-x-0 top-0 z-50 transition-all duration-500 ${
          scrolled
            ? "bg-[#050607]/92 backdrop-blur-md border-b border-[color:var(--border)]"
            : "bg-transparent"
        }`}
      >
        <div className="mx-auto flex max-w-[1440px] items-center justify-between px-6 py-4 lg:px-10">
          <Link to="/" className="flex items-center gap-3 group" aria-label="HM Cargo Services home">
            <img
              src={symbolAsset}
              alt=""
              width={40}
              height={40}
              className="h-9 w-9 object-contain"
            />
            <span className="hidden sm:block font-sans text-[0.72rem] font-semibold tracking-[0.22em] uppercase text-foreground/90">
              HM Cargo
            </span>
          </Link>

          <nav className="hidden lg:flex items-center gap-9">
            {links.map((l) => (
              <Link
                key={l.to}
                to={l.to}
                className="text-[0.78rem] font-medium tracking-[0.14em] uppercase text-muted-foreground hover:text-foreground transition-colors"
                activeProps={{ className: "text-foreground" }}
              >
                {l.label}
              </Link>
            ))}
          </nav>

          <div className="flex items-center gap-3">
            <Link to="/quote" className="hidden md:inline-flex btn-primary !py-3 !px-5">
              Request a Quote
            </Link>
            <button
              className="lg:hidden hairline p-2 text-foreground"
              onClick={() => setOpen(true)}
              aria-label="Open menu"
            >
              <Menu className="h-5 w-5" />
            </button>
          </div>
        </div>
      </header>

      {open && (
        <div className="fixed inset-0 z-[70] bg-[#050607] flex flex-col">
          <div className="flex items-center justify-between px-6 py-4 border-b border-[color:var(--border)]">
            <img src={symbolAsset} alt="HM Cargo" width={36} height={36} className="h-9 w-9" />
            <button onClick={() => setOpen(false)} aria-label="Close menu" className="hairline p-2">
              <X className="h-5 w-5" />
            </button>
          </div>
          <nav className="flex-1 flex flex-col justify-center gap-6 px-8">
            {links.map((l) => (
              <Link
                key={l.to}
                to={l.to}
                onClick={() => setOpen(false)}
                className="font-display text-4xl text-foreground"
              >
                {l.label}
              </Link>
            ))}
            <Link to="/quote" onClick={() => setOpen(false)} className="btn-primary mt-6 self-start">
              Request a Quote
            </Link>
          </nav>
        </div>
      )}
    </>
  );
}
