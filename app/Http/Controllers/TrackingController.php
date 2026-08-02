<?php

namespace App\Http\Controllers;

use App\Services\PublicTrackingProjection;
use App\Services\QrCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public tracking page — the only page a customer ever sees.
 *
 * It is reached from the WhatsApp link sent at intake and from the QR printed
 * on every package label. Both carry the unguessable token, never the
 * readable reference.
 */
class TrackingController extends Controller
{
    public function __construct(
        private readonly PublicTrackingProjection $projection,
        private readonly QrCode $qrCode,
    ) {}

    public function show(string $token): View|RedirectResponse|HttpResponse
    {
        $tracking = $this->projection->forToken($token);

        if ($tracking === null) {
            $parentToken = $this->projection->tokenForBarcode($token);

            if ($parentToken !== null) {
                return redirect()->route('tracking.show', $parentToken);
            }

            // Invalid shapes and well-formed-but-unknown values deliberately
            // share one body so the public endpoint reveals no lookup facts.
            return response()->view('tracking.not-found', status: Response::HTTP_NOT_FOUND);
        }

        return view('tracking.show', [
            'tracking' => $tracking,
            'qr' => $this->qrCode->svg(route('tracking.show', $token), 160),
        ]);
    }
}
