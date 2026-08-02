<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds CORS headers for the /api/track/* endpoints only.
 *
 * Allows the public marketing site (hmcargoservices.com) to call the
 * tracking API from the browser. No credentials, no mutation — only
 * GET requests to the public tracking routes are permitted.
 */
class TrackingCors
{
    /** @var list<string> */
    private array $allowed = [
        'https://hmcargoservices.com',
        'https://www.hmcargoservices.com',
        // Allow localhost during development
        'http://localhost:8080',
        'http://localhost:5173',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin', '');

        $response = $next($request);

        if (in_array($origin, $this->allowed, strict: true)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');
            $response->headers->set('Access-Control-Max-Age', '3600');
        }

        // Handle preflight
        if ($request->isMethod('OPTIONS')) {
            $response->setStatusCode(204);
        }

        return $response;
    }
}
