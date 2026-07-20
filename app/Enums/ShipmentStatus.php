<?php

namespace App\Enums;

/**
 * The shipment state machine.
 *
 * Fixed in code on purpose. AGENTS.md excludes a user-editable workflow engine
 * from version 1, and the client named that screen as a source of complexity
 * in their existing system.
 *
 * Package status is a separate machine (see PackageStatus): a shipment's state
 * is derived from its packages, never set in parallel with them.
 */
enum ShipmentStatus: string
{
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Collected = 'collected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::InTransit => 'في الطريق',
            self::Arrived => 'وصلت',
            self::Collected => 'تم التسليم',
            self::Cancelled => 'ملغاة',
        };
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
