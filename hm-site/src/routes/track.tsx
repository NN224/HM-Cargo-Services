import { createFileRoute, redirect } from "@tanstack/react-router";

/**
 * /track no longer exists.
 *
 * It used to take a shipment reference and look it up. References are
 * sequential (HM-2026-000001), so the search box let anyone walk the range and
 * read recipient names and routes. Tracking is now reachable only through the
 * unguessable token in the link a customer receives by WhatsApp, or the QR on
 * the package label — both of which point at the admin app, not here.
 *
 * Kept as a redirect rather than deleted outright because the path was public
 * and may still be bookmarked or linked. 301 rather than the default 307: the
 * page is gone permanently, and search engines should drop it.
 */
export const Route = createFileRoute("/track")({
    beforeLoad: () => {
        throw redirect({ to: "/", statusCode: 301 });
    },
    component: () => null,
});
