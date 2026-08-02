<?php

namespace App\Support;

/**
 * Display formatting for the public tracking page.
 *
 * Kept apart from the projection so that what is safe to publish and how it is
 * written stay separate concerns.
 *
 * Numbers are written in Western digits throughout, including inside Arabic
 * sentences: the reference, the barcode and the amounts a customer reads back
 * over the phone are all Latin, and mixing the two forms on one screen was
 * harder to read than either alone.
 */
final class Format
{
    /**
     * The stored weight is an exact decimal(12,4) string. Trailing zeros are
     * dropped for reading — "8.2500" is written 8.25 — without ever passing
     * the value through a float.
     */
    public static function weight(string $kilograms): string
    {
        [$whole, $fraction] = array_pad(explode('.', $kilograms, 2), 2, '');

        $fraction = rtrim($fraction, '0');

        return ($fraction === '' ? $whole : $whole.'.'.$fraction).' كغ';
    }

    /** A short day-and-time stamp; the year is noise on a tracking page. */
    public static function moment(?string $timestamp): ?string
    {
        if ($timestamp === null) {
            return null;
        }

        return date('d/m · H:i', strtotime($timestamp));
    }
}
