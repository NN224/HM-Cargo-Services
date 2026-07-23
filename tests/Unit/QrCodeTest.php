<?php

use App\Services\QrCode;

test('svg returns inline markup without an xml prolog', function () {
    $svg = app(QrCode::class)->svg('https://example.test/track/PKG-ABCDE12345');

    expect($svg)->toStartWith('<svg')
        ->and($svg)->toContain('</svg>')
        ->and($svg)->not->toContain('<?xml');
});

test('svg honours the requested pixel size', function () {
    $svg = app(QrCode::class)->svg('https://example.test/track/PKG-ABCDE12345', 240);

    expect($svg)->toContain('width="240"')->and($svg)->toContain('height="240"');
});
