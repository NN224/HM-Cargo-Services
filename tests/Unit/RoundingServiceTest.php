<?php

use App\Services\RoundingService;

/**
 * D-008 — the custom customer rounding rule, approved by voice confirmation.
 *
 *   .01 through .29  rounds DOWN
 *   .30 through .99  rounds UP
 *   exact dollars    unchanged
 *
 * It applies to the final customer charge ONLY. Weight is never rounded, and
 * batch cost and profit retain their cents.
 *
 * Every value here is in integer cents. This is the single most dangerous
 * calculation in the system: an error bills real customers the wrong amount,
 * so the boundaries are tested exhaustively rather than by sampling.
 */
test('the exact boundaries of the rounding rule', function (int $cents, int $expected) {
    expect(RoundingService::roundCustomerCharge($cents))->toBe($expected);
})->with([
    // Exact dollars are never touched.
    'zero'                => [0, 0],
    'exactly $1.00'       => [100, 100],
    'exactly $7.00'       => [700, 700],

    // .01 - .29 rounds down.
    'lower edge $1.01'    => [101, 100],
    'mid $1.15'           => [115, 100],
    'upper edge $1.29'    => [129, 100],

    // .30 - .99 rounds up.
    'lower edge $1.30'    => [130, 200],
    'mid $1.65'           => [165, 200],
    'upper edge $1.99'    => [199, 200],

    // The .29/.30 hinge on a larger amount, where an off-by-one is costly.
    '$149.29 rounds down' => [14929, 14900],
    '$149.30 rounds up'   => [14930, 15000],

    // Sub-dollar amounts follow the same rule.
    '$0.29 becomes zero'  => [29, 0],
    '$0.30 becomes $1.00' => [30, 100],
]);

test('rounding is idempotent — a rounded charge never shifts again', function (int $cents) {
    $once = RoundingService::roundCustomerCharge($cents);
    $twice = RoundingService::roundCustomerCharge($once);

    expect($twice)->toBe($once);
})->with([0, 29, 30, 100, 129, 130, 14929, 14930]);

test('every rounded charge lands on a whole dollar', function (int $cents) {
    expect(RoundingService::roundCustomerCharge($cents) % 100)->toBe(0);
})->with([0, 1, 29, 30, 99, 100, 101, 555, 14929, 14930, 999999]);

test('a negative charge is rejected rather than silently rounded', function () {
    RoundingService::roundCustomerCharge(-100);
})->throws(InvalidArgumentException::class);

test('the rule is exhaustively correct across a full dollar', function () {
    // Walk every cent from $0.00 to $9.99 and assert the rule directly,
    // so no boundary can hide between the sampled cases above.
    for ($dollars = 0; $dollars <= 9; $dollars++) {
        for ($fraction = 0; $fraction <= 99; $fraction++) {
            $cents = $dollars * 100 + $fraction;

            $expected = match (true) {
                $fraction === 0 => $dollars * 100,
                $fraction <= 29 => $dollars * 100,
                default => ($dollars + 1) * 100,
            };

            expect(RoundingService::roundCustomerCharge($cents))
                ->toBe($expected, "failed at {$dollars}.{$fraction}");
        }
    }
});
