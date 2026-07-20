<?php

namespace App\Enums;

/**
 * The package state machine, separate from the shipment's (AGENTS.md).
 *
 * Includes the exception states D-016 requires: a package may go missing or
 * be damaged without dragging its whole shipment into that state.
 */
enum PackageStatus: string
{
    case Received = 'received';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Collected = 'collected';
    case Missing = 'missing';
    case Damaged = 'damaged';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'تم الاستلام',
            self::InTransit => 'في الطريق',
            self::Arrived => 'وصل',
            self::Collected => 'تم التسليم',
            self::Missing => 'مفقود',
            self::Damaged => 'تالف',
            self::Cancelled => 'ملغى',
        };
    }

    /**
     * A package that still counts toward its shipment's obligations.
     *
     * Cancelled, missing and damaged packages are excluded: a shipment must
     * not be held back from collection by a package that will never arrive
     * (D-015 gates on active packages only).
     */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Cancelled, self::Missing, self::Damaged], true);
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
