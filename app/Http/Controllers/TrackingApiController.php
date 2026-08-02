<?php

namespace App\Http\Controllers;

use App\Services\PublicTrackingProjection;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public JSON API for shipment tracking.
 *
 * Serves the same projection as the tracking page, so the two can never
 * describe the same scan differently.
 *
 * Lookup is by unguessable token only. A by-reference endpoint used to exist
 * for a search box on the marketing site; references are sequential
 * (`HM-2026-000001`), so it let anyone walk the range and read recipient
 * names, routes and outstanding balances. The search box and the endpoint
 * were both removed rather than rate-limited — the token is the lookup key.
 */
class TrackingApiController extends Controller
{
    public function __construct(
        private readonly PublicTrackingProjection $projection,
    ) {}

    public function show(string $token): JsonResponse
    {
        $tracking = $this->projection->forToken($token);

        if ($tracking === null) {
            $parentToken = $this->projection->tokenForBarcode($token);

            $tracking = $parentToken === null
                ? null
                : $this->projection->forToken($parentToken);
        }

        if ($tracking === null) {
            return response()->json(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($tracking);
    }
}
