<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only audit row for privileged operational and financial mutations.
 *
 * @property int $id
 * @property int $user_id
 * @property string $action
 * @property string $auditable_type
 * @property int $auditable_id
 * @property array<string, mixed> $before
 * @property array<string, mixed> $after
 * @property string $reason
 * @property Carbon $created_at
 *
 * @mixin \Eloquent
 */
final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'before',
        'after',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'before' => 'array',
            'after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('سجل التدقيق غير قابل للتعديل.'));
        self::deleting(fn () => throw new LogicException('سجل التدقيق غير قابل للحذف.'));
    }
}
