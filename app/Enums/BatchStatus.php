<?php

namespace App\Enums;

/**
 * The batch state machine — separate from shipment and package status.
 *
 * A batch action never substitutes for a package-level arrival scan: marking a
 * batch arrived does not mark its packages arrived, because packages go
 * missing and get damaged individually.
 */
enum BatchStatus: string
{
    case Open = 'open';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'مفتوحة',
            self::Dispatched => 'أُرسلت',
            self::InTransit => 'في الطريق',
            self::Arrived => 'وصلت',
            self::Completed => 'مكتملة',
            self::Cancelled => 'ملغاة',
        };
    }

    /**
     * A batch that still holds its shipments.
     *
     * Used to enforce "one active batch per shipment": a shipment released
     * from a cancelled batch must be assignable again.
     */
    public function isActive(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }

    /** Ordinary route and weight edits are locked once a batch has left. */
    public function isDispatched(): bool
    {
        return ! in_array($this, [self::Open, self::Cancelled], true);
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
