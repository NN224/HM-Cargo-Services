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
    case Created = 'created';
    case ReceivedOrigin = 'received_origin';
    case ArrivedOriginAirport = 'arrived_origin_airport';
    case InTransit = 'in_transit';
    case ArrivedTransit = 'arrived_transit';
    case DepartedTransit = 'departed_transit';
    case ArrivedDestination = 'arrived_destination';
    case Collected = 'collected';
    case Missing = 'missing';
    case Damaged = 'damaged';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'تم الإنشاء',
            self::ReceivedOrigin => 'مستلم في مستودع المنشأ',
            self::ArrivedOriginAirport => 'وصل إلى مطار الانطلاق',
            self::InTransit => 'في الطريق',
            self::ArrivedTransit => 'وصل إلى مستودع العبور',
            self::DepartedTransit => 'غادر مستودع العبور',
            self::ArrivedDestination => 'وصل إلى مستودع الوجهة',
            self::Collected => 'تم التسليم',
            self::Missing => 'مفقود',
            self::Damaged => 'تالف',
            self::Cancelled => 'ملغى',
        };
    }

    /**
     * A package that still counts toward its shipment's obligations.
     *
     * Missing and damaged packages remain active because they must block
     * collection until an administrator resolves the exception. Only an
     * explicitly cancelled package leaves the shipment obligation.
     */
    public function isActive(): bool
    {
        return $this !== self::Cancelled;
    }

    public function isException(): bool
    {
        return in_array($this, [self::Missing, self::Damaged], true);
    }

    /** @return array<int, self> */
    public static function journeySteps(): array
    {
        return [
            self::ReceivedOrigin,
            self::ArrivedOriginAirport,
            self::ArrivedTransit,
            self::ArrivedDestination,
            self::Collected,
        ];
    }

    public function isRetiredJourneyStatus(): bool
    {
        return in_array($this, [self::InTransit, self::DepartedTransit], true);
    }

    public function journeyPosition(): ?int
    {
        $position = array_search($this, self::journeySteps(), true);

        return $position === false ? null : $position;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            if ($case->isRetiredJourneyStatus()) {
                continue;
            }

            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
