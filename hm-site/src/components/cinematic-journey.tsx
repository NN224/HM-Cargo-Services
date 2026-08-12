import { useEffect, useRef } from "react";
import symbolAsset from "@/assets/hm-logo-symbol.png";
import sceneContainer from "@/assets/scenes/scene-container.jpg";
import sceneWarehouse from "@/assets/scenes/scene-warehouse.jpg";
import sceneTruck from "@/assets/scenes/scene-truck.jpg";
import scenePort from "@/assets/scenes/scene-port.jpg";
import sceneMap from "@/assets/scenes/scene-map.jpg";
import { Link } from "@tanstack/react-router";

type SceneCopy = {
    eyebrow: string;
    title: string;
    body: string;
};

const SCENES: SceneCopy[] = [
    {
        eyebrow: "Cargo without uncertainty",
        title: "Moving your cargo forward.",
        body: "Reliable logistics, clear visibility, and careful handling from origin to destination.",
    },
    {
        eyebrow: "Origin",
        title: "Ready at the source.",
        body: "Organized handling from the very first step of the journey.",
    },
    {
        eyebrow: "Warehouse",
        title: "Controlled at every handoff.",
        body: "Careful warehouse processing and shipment coordination across every stage.",
    },
    {
        eyebrow: "Transport",
        title: "Built to keep moving.",
        body: "Reliable ground transportation through every leg of the route.",
    },
    {
        eyebrow: "Port",
        title: "Connected beyond borders.",
        body: "Cargo solutions designed for regional and international movement.",
    },
    {
        eyebrow: "Visibility",
        title: "Visibility from origin to arrival.",
        body: "Clear updates and confidence throughout the shipment lifecycle.",
    },
    {
        eyebrow: "Arrival",
        title: "Delivered with intent.",
        body: "Every shipment ends where it began — with care.",
    },
];

export function CinematicJourney() {
    const rootRef = useRef<HTMLDivElement>(null);
    const progressRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        // Reduced motion: skip GSAP entirely; the static fallback markup below renders as a scrollable stack.
        if (typeof window === "undefined") return;
        const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (reduce) return;

        let ctx: { revert: () => void } | undefined;
        let cleanup: (() => void) | undefined;

        (async () => {
            const gsapMod = await import("gsap");
            const stMod = await import("gsap/ScrollTrigger");
            const Lenis = (await import("lenis")).default;

            const gsap = gsapMod.default;
            const ScrollTrigger = stMod.ScrollTrigger;
            gsap.registerPlugin(ScrollTrigger);

            const lenis = new Lenis({
                duration: 1.15,
                easing: (t: number) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                smoothWheel: true,
            });
            lenis.on("scroll", ScrollTrigger.update);
            gsap.ticker.add((time) => lenis.raf(time * 1000));
            gsap.ticker.lagSmoothing(0);

            ctx = gsap.context(() => {
                const root = rootRef.current!;
                const sceneCount = 7;
                // Pause holds on scenes 2 (warehouse index=2) and 5 (visibility index=5)
                // Total pin distance in viewport heights
                const totalVH = sceneCount * 120; // extra room per scene

                const layers = Array.from(root.querySelectorAll<HTMLElement>("[data-scene]"));
                const copyEls = Array.from(root.querySelectorAll<HTMLElement>("[data-copy]"));
                const dots = Array.from(root.querySelectorAll<HTMLElement>("[data-dot]"));

                // Initial states
                layers.forEach((el, i) => {
                    gsap.set(el, { opacity: i === 0 ? 1 : 0, scale: i === 0 ? 1 : 1.06 });
                });
                copyEls.forEach((el, i) => {
                    gsap.set(el, { opacity: i === 0 ? 1 : 0, y: i === 0 ? 0 : 24 });
                });

                const pinTl = gsap.timeline({
                    scrollTrigger: {
                        trigger: root,
                        start: "top top",
                        end: `+=${totalVH}vh`,
                        pin: true,
                        scrub: 0.8,
                        anticipatePin: 1,
                        onUpdate: (self) => {
                            const active = Math.min(
                                sceneCount - 1,
                                Math.floor(self.progress * sceneCount),
                            );
                            dots.forEach((d, i) => {
                                d.dataset.active = i <= active ? "true" : "false";
                            });
                            if (progressRef.current) {
                                progressRef.current.style.height = `${self.progress * 100}%`;
                            }
                        },
                    },
                });

                // Each scene occupies 1/sceneCount of the timeline. Transitions overlap by 0.15.
                const step = 1 / sceneCount;
                for (let i = 0; i < sceneCount; i++) {
                    const start = i * step;
                    const end = (i + 1) * step;
                    const mid = start + step * 0.25;
                    const outStart = end - step * 0.25;

                    // Copy in
                    pinTl.to(
                        copyEls[i],
                        { opacity: 1, y: 0, duration: step * 0.25, ease: "power2.out" },
                        start,
                    );
                    // Copy hold, then out — never overlap into next scene
                    pinTl.to(
                        copyEls[i],
                        { opacity: 0, y: -20, duration: step * 0.2, ease: "power2.in" },
                        outStart,
                    );

                    // Scene layer in (skip 0 already visible)
                    if (i > 0) {
                        pinTl.to(
                            layers[i],
                            { opacity: 1, scale: 1, duration: step * 0.35, ease: "power2.inOut" },
                            start - step * 0.05,
                        );
                    }
                    // Scene layer subtle forward push during its window
                    pinTl.to(layers[i], { scale: 1.08, duration: step, ease: "none" }, start);
                    // Previous scene out
                    if (i > 0) {
                        pinTl.to(
                            layers[i - 1],
                            { opacity: 0, duration: step * 0.35, ease: "power2.inOut" },
                            start - step * 0.02,
                        );
                    }

                    // Scene 1: orange line traces the logo
                    if (i === 0) {
                        const line = root.querySelector<SVGPathElement>("[data-logo-line]");
                        if (line) {
                            const len = line.getTotalLength?.() ?? 400;
                            gsap.set(line, { strokeDasharray: len, strokeDashoffset: len });
                            pinTl.to(
                                line,
                                { strokeDashoffset: 0, duration: step * 0.6, ease: "power2.out" },
                                mid - step * 0.3,
                            );
                        }
                    }

                    // Scene 5 (map): draw the orange route
                    if (i === 5) {
                        const route = root.querySelector<SVGPathElement>("[data-route-line]");
                        if (route) {
                            const len = route.getTotalLength?.() ?? 800;
                            gsap.set(route, { strokeDasharray: len, strokeDashoffset: len });
                            pinTl.to(
                                route,
                                { strokeDashoffset: 0, duration: step * 0.9, ease: "power2.inOut" },
                                start,
                            );
                        }
                    }
                }
            }, rootRef);

            cleanup = () => {
                lenis.destroy();
            };
        })();

        return () => {
            ctx?.revert();
            cleanup?.();
        };
    }, []);

    return (
        <section
            ref={rootRef}
            aria-label="HM Cargo journey"
            className="relative h-screen w-full overflow-hidden bg-[#050607]"
        >
            {/* Scene layers */}
            <SceneOpening />
            <SceneImage
                src={sceneContainer}
                alt="Sealed cargo container in a dark industrial space"
                title="HM Cargo Sealed Container Logistics"
            />
            <SceneImage
                src={sceneWarehouse}
                alt="Dark industrial warehouse with orange guide lights"
                title="HM Cargo Warehouse & Storage Facilities"
            />
            <SceneImage
                src={sceneTruck}
                alt="Modern cargo truck moving through the night"
                title="HM Cargo Ground Transportation Fleet"
            />
            <SceneImage
                src={scenePort}
                alt="Night port with cranes and a moored cargo vessel"
                title="HM Cargo Port Operations & Sea Freight"
            />
            <SceneMap />
            <SceneArrival />

            {/* Vignette overlay */}
            <div className="pointer-events-none absolute inset-0 z-10 bg-[radial-gradient(ellipse_at_center,transparent_45%,rgba(5,6,7,0.75)_100%)]" />

            {/* Timed copy overlays */}
            <div className="pointer-events-none absolute inset-0 z-20 flex items-center">
                <div className="mx-auto w-full max-w-[1440px] px-6 lg:px-16">
                    {SCENES.map((s, i) => (
                        <div
                            key={i}
                            data-copy
                            className="absolute inset-x-0 lg:inset-x-auto lg:left-24 xl:left-32 max-w-2xl px-6 lg:px-0"
                        >
                            {i === 0 ? (
                                <h1 className="mt-5 font-display text-5xl sm:text-6xl lg:text-8xl text-foreground">
                                    {s.title}
                                </h1>
                            ) : (
                                <h2 className="mt-5 font-display text-4xl sm:text-5xl lg:text-7xl text-foreground">
                                    {s.title}
                                </h2>
                            )}
                            <p className="mt-6 max-w-lg text-base sm:text-lg leading-relaxed text-muted-foreground">
                                {s.body}
                            </p>
                            {i === 0 && (
                                <div className="pointer-events-auto mt-10 flex flex-wrap gap-4">
                                    <Link to="/quote" className="btn-primary">
                                        Request a Quote
                                    </Link>
                                    <Link to="/services" className="btn-secondary">
                                        Explore Our Services
                                    </Link>
                                </div>
                            )}
                            {i === 6 && (
                                <div className="pointer-events-auto mt-10 flex flex-wrap gap-4">
                                    <Link to="/contact" className="btn-primary">
                                        Talk to the Operation
                                    </Link>
                                    <Link to="/coverage" className="btn-secondary">
                                        View Coverage
                                    </Link>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            </div>

            {/* Scroll indicator */}
            <div className="pointer-events-none absolute bottom-8 left-6 lg:left-16 z-20 flex items-center gap-3">
                <div className="h-2 w-2 rounded-full bg-[color:var(--accent)] animate-pulse" />
                <span className="font-mono text-[0.65rem] uppercase tracking-[0.2em] text-muted-foreground">
                    Scroll to navigate journey
                </span>
            </div>

            {/* Step indicator */}
            <div
                ref={progressRef}
                className="pointer-events-none absolute right-6 lg:right-16 top-1/2 z-20 hidden -translate-y-1/2 flex-col gap-3 lg:flex"
            >
                {SCENES.map((_, i) => (
                    <div key={i} data-step-dot className="relative flex items-center justify-end">
                        <span
                            className="h-1.5 w-1.5 rounded-full bg-[color:var(--border-strong)] transition-all duration-300"
                            style={{ top: `${(i / (SCENES.length - 1)) * 100}%` }}
                        />
                    </div>
                ))}
            </div>
        </section>
    );
}

function SceneOpening() {
    return (
        <div data-scene className="scene-layer flex items-center justify-center bg-[#050607]">
            <div className="relative -translate-y-[5vh] lg:-translate-y-[7vh]">
                <img
                    src={symbolAsset}
                    alt="HM Cargo symbol emerging from darkness"
                    title="HM Cargo Services Symbol Logo"
                    width={520}
                    height={520}
                    className="h-[36vh] w-auto max-w-[80vw] object-contain opacity-90"
                    style={{ filter: "drop-shadow(0 0 60px rgba(255,121,0,0.15))" }}
                />
                {/* orange energy line overlay */}
                <svg
                    className="pointer-events-none absolute inset-0 h-full w-full"
                    viewBox="0 0 520 520"
                    fill="none"
                    aria-hidden
                >
                    <path
                        data-logo-line
                        d="M 110 260 L 410 260"
                        stroke="#FF7900"
                        strokeWidth="3"
                        strokeLinecap="round"
                        style={{ filter: "drop-shadow(0 0 8px #FF7900)" }}
                    />
                </svg>
            </div>
        </div>
    );
}

function SceneImage({ src, alt, title }: { src: string; alt: string; title?: string }) {
    return (
        <div data-scene className="scene-layer">
            <img
                src={src}
                alt={alt}
                title={title || alt}
                width={1600}
                height={912}
                className="h-full w-full object-cover"
                loading="lazy"
                decoding="async"
            />
            <div className="absolute inset-0 bg-gradient-to-r from-[#050607]/70 via-[#050607]/20 to-[#050607]/40" />
        </div>
    );
}

function SceneMap() {
    return (
        <div data-scene className="scene-layer">
            <img
                src={sceneMap}
                alt="Dark world map showing HM Cargo global visibility"
                title="HM Cargo Global Operations Map"
                width={1600}
                height={912}
                className="h-full w-full object-cover"
                loading="lazy"
                decoding="async"
            />
            <div className="absolute inset-0 bg-gradient-to-r from-[#050607]/60 via-transparent to-[#050607]/40" />
            <svg
                className="absolute inset-0 h-full w-full"
                viewBox="0 0 1600 900"
                preserveAspectRatio="none"
                aria-hidden
            >
                <defs>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="4" result="blur" />
                        <feMerge>
                            <feMergeNode in="blur" />
                            <feMergeNode in="SourceGraphic" />
                        </feMerge>
                    </filter>
                </defs>
                <path
                    data-route-line
                    d="M 220 480 Q 480 300 720 420 T 1180 340 T 1420 500"
                    stroke="#FF7900"
                    strokeWidth="2.5"
                    fill="none"
                    strokeLinecap="round"
                    filter="url(#glow)"
                />
                {[
                    [220, 480],
                    [720, 420],
                    [1180, 340],
                    [1420, 500],
                ].map(([x, y], i) => (
                    <g key={i}>
                        <circle cx={x} cy={y} r="4" fill="#FF7900" filter="url(#glow)" />
                        <circle
                            cx={x}
                            cy={y}
                            r="10"
                            fill="none"
                            stroke="#FF7900"
                            strokeOpacity="0.35"
                        />
                    </g>
                ))}
            </svg>
        </div>
    );
}

function SceneArrival() {
    return (
        <div data-scene className="scene-layer flex items-center justify-center bg-[#050607]">
            <img
                src={symbolAsset}
                alt="HM Cargo Services background symbol logo"
                title="HM Cargo Services — Arrival"
                aria-hidden
                className="absolute inset-0 m-auto h-[70vh] w-auto object-contain opacity-[0.06]"
            />
            <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(255,121,0,0.08)_0%,transparent_60%)]" />
        </div>
    );
}
