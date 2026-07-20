<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * The custom customer rounding rule (D-008).
 *
 * Approved by the owner:
 *   .01 - .29  rounds DOWN
 *   .30 - .99  rounds UP
 *   exact dollars are unchanged
 *
 * Scope — this applies to the FINAL CUSTOMER CHARGE ONLY:
 *   - Weight is never rounded (D-007). There is no minimum billable weight.
 *   - Batch cost and profit keep their cents (D-008), so they must not be
 *     passed through this service.
 *
 * All values are integer cents. Money never touches binary floating point
 * anywhere in this system, so this class deals only in integers.
 */
final class RoundingService
{
    private const CENTS_PER_DOLLAR = 100;

    /**
     * The highest fractional value that still rounds down. At .30 the charge
     * rounds up instead, which is what makes this rule custom rather than the
     * usual half-up rounding at .50.
     */
    private const ROUND_DOWN_CEILING = 29;

    /**
     * Round a customer charge to whole dollars using the approved rule.
     *
     * @param  int  $cents  the unrounded charge, in cents
     * @return int the rounded charge, in cents, always a whole dollar
     *
     * @throws InvalidArgumentException when given a negative charge
     */
    public static function roundCustomerCharge(int $cents): int
    {
        if ($cents < 0) {
            throw new InvalidArgumentException(
                "A customer charge cannot be negative; received {$cents} cents. "
                .'Corrections are recorded as adjustments, never as negative charges (D-016).'
            );
        }

        $wholeDollars = intdiv($cents, self::CENTS_PER_DOLLAR);
        $fraction = $cents % self::CENTS_PER_DOLLAR;

        if ($fraction === 0 || $fraction <= self::ROUND_DOWN_CEILING) {
            return $wholeDollars * self::CENTS_PER_DOLLAR;
        }

        return ($wholeDollars + 1) * self::CENTS_PER_DOLLAR;
    }
}
