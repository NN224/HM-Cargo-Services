import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import {
    Outlet,
    Link,
    createRootRouteWithContext,
    useRouter,
    HeadContent,
    Scripts,
} from "@tanstack/react-router";
import type { ReactNode } from "react";

import appCss from "../styles.css?url";

function NotFoundComponent() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-background px-4">
            <div className="max-w-md text-center">
                <p className="eyebrow">Error 404</p>
                <h1 className="mt-4 text-5xl text-foreground">Route not found</h1>
                <p className="mt-3 text-sm text-muted-foreground">
                    The page you're looking for isn't part of the HM Cargo network.
                </p>
                <div className="mt-8">
                    <Link to="/" className="btn-primary">
                        Return home
                    </Link>
                </div>
            </div>
        </div>
    );
}

function ErrorComponent({ error, reset }: { error: Error; reset: () => void }) {
    console.error(error);
    const router = useRouter();

    return (
        <div className="flex min-h-screen items-center justify-center bg-background px-4">
            <div className="max-w-md text-center">
                <p className="eyebrow">Signal lost</p>
                <h1 className="mt-4 text-4xl text-foreground">This page didn't load</h1>
                <p className="mt-3 text-sm text-muted-foreground">
                    Something went wrong in transit. Try again or head back to the origin.
                </p>
                <div className="mt-8 flex flex-wrap justify-center gap-3">
                    <button
                        onClick={() => {
                            router.invalidate();
                            reset();
                        }}
                        className="btn-primary"
                    >
                        Try again
                    </button>
                    <a href="/" className="btn-secondary">
                        Go home
                    </a>
                </div>
            </div>
        </div>
    );
}

export const Route = createRootRouteWithContext<{ queryClient: QueryClient }>()({
    head: () => ({
        meta: [
            { charSet: "utf-8" },
            { name: "viewport", content: "width=device-width, initial-scale=1" },
            { name: "theme-color", content: "#050607" },
            { name: "author", content: "HM Cargo Services" },
            { name: "publisher", content: "Nabel Al Chaar — Founder & Digital Growth Strategist" },
            { name: "robots", content: "index, follow" },
            {
                name: "keywords",
                content:
                    "HM Cargo Services, cargo shipping, international freight forwarding, logistics UAE, Dubai cargo, Syria cargo, Lebanon freight forwarding, warehousing, ground transport, supply chain",
            },
            { property: "og:type", content: "website" },
            { property: "og:site_name", content: "HM Cargo Services" },
            { property: "og:url", content: "https://hmcargoservices.com/" },
            { property: "og:image", content: "https://hmcargoservices.com/og-image.jpg" },
            { name: "twitter:card", content: "summary_large_image" },
            { name: "twitter:image", content: "https://hmcargoservices.com/og-image.jpg" },
        ],
        links: [
            { rel: "stylesheet", href: appCss },
            { rel: "icon", type: "image/png", href: "/favicon.png" },
            { rel: "shortcut icon", type: "image/png", href: "/favicon.png" },
            { rel: "apple-touch-icon", href: "/icons/icon-192.png" },
            { rel: "manifest", href: "/manifest.json" },
            { rel: "preconnect", href: "https://fonts.googleapis.com" },
            { rel: "preconnect", href: "https://fonts.gstatic.com", crossOrigin: "anonymous" },
            {
                rel: "stylesheet",
                href: "https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300;9..144,400;9..144,500&family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap",
            },
            {
                rel: "stylesheet",
                href: "https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap",
            },
        ],
        scripts: [
            {
                children: `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-WW96QPML');`,
            },
            {
                type: "application/ld+json",
                children: JSON.stringify({
                    "@context": "https://schema.org",
                    "@type": "LogisticsService",
                    name: "HM Cargo Services",
                    url: "https://hmcargoservices.com",
                    logo: "https://hmcargoservices.com/favicon.png",
                    image: "https://hmcargoservices.com/og-image.jpg",
                    description:
                        "Reliable logistics, clear visibility, and careful handling from origin to destination. HM Cargo Services operates international, ground, and warehousing solutions.",
                    publisher: {
                        "@type": "Person",
                        name: "Nabel Al Chaar",
                        jobTitle: "Founder & Digital Growth Strategist",
                    },
                    address: {
                        "@type": "PostalAddress",
                        streetAddress: "Office 404, Awqaf Building, Street 8, Al Murar, Deira",
                        addressLocality: "Dubai",
                        addressCountry: "AE",
                    },
                    telephone: "+971521530190",
                    sameAs: [
                        "https://www.facebook.com/profile.php?id=61577769857365",
                        "https://www.instagram.com/hm_cargo2026",
                    ],
                }),
            },
            {
                type: "module",
                children: `
          if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
              navigator.serviceWorker.register('/sw.js').catch(err => {
                console.error('ServiceWorker registration failed: ', err);
              });
            });
          }
        `,
            },
        ],
    }),
    shellComponent: RootShell,
    component: RootComponent,
    notFoundComponent: NotFoundComponent,
    errorComponent: ErrorComponent,
});

function RootShell({ children }: { children: ReactNode }) {
    return (
        <html lang="en" className="dark">
            <head>
                <HeadContent />
            </head>
            <body>
                <noscript>
                    <iframe
                        src="https://www.googletagmanager.com/ns.html?id=GTM-WW96QPML"
                        height="0"
                        width="0"
                        style={{ display: "none", visibility: "hidden" }}
                    ></iframe>
                </noscript>
                {children}
                <Scripts />
            </body>
        </html>
    );
}

function RootComponent() {
    const { queryClient } = Route.useRouteContext();

    return (
        <QueryClientProvider client={queryClient}>
            <Outlet />
        </QueryClientProvider>
    );
}
