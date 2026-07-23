<?php

use App\Enums\LockablePage;

/**
 * The single registry of lockable destinations.
 *
 * Everything — the form toggles, the stored locks, the middleware — reads keys
 * from here, so they cannot drift. The earlier per-screen gating missed screens
 * precisely because there was no such single list.
 */
test('the dashboard is never lockable', function () {
    $keys = array_map(fn (LockablePage $p): string => $p->value, LockablePage::cases());

    expect($keys)->not->toContain('dashboard');
});

test('every case has an Arabic label', function () {
    foreach (LockablePage::cases() as $page) {
        expect($page->label())->toBeString()->not->toBe('');
    }
});

test('a panel route name resolves to its page key', function () {
    expect(LockablePage::fromRouteName('filament.admin.resources.shipments.index'))
        ->toBe(LockablePage::Shipments)
        ->and(LockablePage::fromRouteName('filament.admin.resources.payments.create'))
        ->toBe(LockablePage::Payments)
        ->and(LockablePage::fromRouteName('filament.admin.pages.scan-packages'))
        ->toBe(LockablePage::ScanPackages);
});

test('the dashboard, login, and unknown routes resolve to null', function () {
    expect(LockablePage::fromRouteName('filament.admin.pages.dashboard'))->toBeNull()
        ->and(LockablePage::fromRouteName('filament.admin.auth.login'))->toBeNull()
        ->and(LockablePage::fromRouteName(null))->toBeNull()
        ->and(LockablePage::fromRouteName('some.other.route'))->toBeNull();
});
