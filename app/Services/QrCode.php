<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Renders a QR code as inline SVG.
 *
 * SVG via bacon's pure-PHP backend needs no GD or imagick, so it is safe on the
 * FrankenPHP production image and needs no CDN. Bacon prefixes an XML prolog;
 * inline SVG in an HTML document must not carry it, so everything before the
 * first <svg is dropped.
 */
class QrCode
{
    public function svg(string $data, int $size = 180): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());
        $svg = (new Writer($renderer))->writeString($data);
        $start = strpos($svg, '<svg');

        return $start === false ? $svg : substr($svg, $start);
    }
}
