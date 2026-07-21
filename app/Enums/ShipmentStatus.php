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
    case Draft = 'draft';
    case AwaitingBatch = 'awaiting_batch';
    case Assigned = 'assigned';
    case Pending = 'pending';
    case InTransit = 'in_transit';
    case PartialAtTransit = 'partial_at_transit';
    case AtTransit = 'at_transit';
    case PartialAtDestination = 'partial_at_destination';
    case ReadyForCollection = 'ready_for_collection';
    case Arrived = 'arrived';
    case Collected = 'collected';
    case Cancelled = 'cancelled';
    case Exception = 'exception';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::AwaitingBatch => 'بانتظار رحلة',
            self::Assigned => 'مسندة إلى رحلة',
            self::Pending => 'قيد الانتظار',
            self::InTransit => 'في الطريق',
            self::PartialAtTransit => 'وصل بعضها إلى مستودع العبور',
            self::AtTransit => 'في مستودع العبور',
            self::PartialAtDestination => 'وصل بعضها إلى الوجهة',
            self::ReadyForCollection => 'جاهزة للتسليم',
            self::Arrived => 'وصلت',
            self::Collected => 'تم التسليم',
            self::Cancelled => 'ملغاة',
            self::Exception => 'استثناء يحتاج معالجة',
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
