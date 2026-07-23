<?php

namespace App\Enums;

/**
 * The fixed set of capability switches an administrator may grant to a
 * warehouse employee (D-022).
 *
 * This list is deliberately closed. It exists so a junior clerk can be kept
 * away from pricing and payments without building the user-defined permission
 * matrix that AGENTS.md excludes and that the client named as a source of
 * complexity in their previous system.
 *
 * Adding a case is an owner decision, not a developer convenience.
 */
enum Capability: string
{
    case PriceShipments = 'price_shipments';
    case RecordPayments = 'record_payments';
    case EditAfterDispatch = 'edit_after_dispatch';
    case DeleteRecords = 'delete_records';
    case ManageCustomers = 'manage_customers';

    public function label(): string
    {
        return match ($this) {
            self::PriceShipments => 'تسعير الشحنات وإسنادها للرحلات',
            self::RecordPayments => 'تسجيل الدفعات',
            self::EditAfterDispatch => 'التعديل بعد إرسال الرحلة',
            self::DeleteRecords => 'حذف السجلات',
            self::ManageCustomers => 'إدارة العملاء والأسعار',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PriceShipments => 'يحدد سعر الشحنة ويربطها برحلة.',
            self::RecordPayments => 'يسجّل دفعات العملاء ويعكسها.',
            self::EditAfterDispatch => 'يعدّل شحنة بعد مغادرة رحلتها — إجراء يخص المدير عادةً.',
            self::DeleteRecords => 'يحذف السجلات التي لا يرتبط بها شيء. المرتبط يُعطَّل فقط.',
            self::ManageCustomers => 'ينشئ العملاء ويحدد أسعارهم على المسارات.',
        };
    }

    /** @return array<string, string> value => Arabic label, for Filament */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
