<?php

namespace App\Filament\Concerns;

use App\Enums\Capability;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;

/**
 * Renders a page as locked instead of showing its content.
 *
 * The trait decides nothing about *who* may see a page — the calling page
 * supplies both the condition and the reason. That is deliberate: a capability
 * gate (this piece) and a per-employee administrative lock (piece 3) share one
 * presentation but decide access completely differently.
 */
trait LocksWhenUnauthorized
{
    protected function lockNotice(string $reason): Section
    {
        return Section::make('هذه الصفحة مقفلة')
            ->icon('heroicon-o-lock-closed')
            ->schema([
                TextEntry::make('lock_reason')
                    ->hiddenLabel()
                    ->state($reason),
            ]);
    }

    protected function capabilityLockReason(Capability $capability): string
    {
        return "تحتاج صلاحية \"{$capability->label()}\" لعرض هذه الصفحة.";
    }
}
