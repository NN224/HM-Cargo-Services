<?php

namespace App\Enums;

/**
 * The only two roles in version 1 (D-003).
 *
 * AGENTS.md scope discipline explicitly excludes "fine-grained permission
 * matrices beyond administrator and warehouse employee", so this enum must
 * not grow without an owner decision.
 */
enum UserRole: string
{
    case Administrator = 'administrator';
    case WarehouseEmployee = 'warehouse_employee';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'مدير',
            self::WarehouseEmployee => 'موظف مستودع',
        };
    }

    /** Administrators see and act across every warehouse. */
    public function isAdministrator(): bool
    {
        return $this === self::Administrator;
    }

    /** @return array<string, string> value => Arabic label, for Filament selects */
    public static function options(): array
    {
        return array_column(
            array_map(
                fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
                self::cases(),
            ),
            'label',
            'value',
        );
    }
}
