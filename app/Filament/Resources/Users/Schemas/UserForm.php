<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Capability;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $capabilityToggles = [];
        
        // Capabilities are defined as an enum list, avoiding the trap of a
        // complex permission matrix. We map them explicitly to visual toggles.
        foreach (Capability::cases() as $capability) {
            $capabilityToggles[] = Toggle::make("capability_{$capability->value}")
                ->label($capability->label())
                ->helperText($capability->description())
                // The underlying capabilities column is a list of strings.
                // We hydrate the toggle by checking if the user holds that capability.
                ->formatStateUsing(fn (?\App\Models\User $record): bool => $record ? $record->hasCapability($capability) : false);
        }

        return $schema
            ->components([
                Section::make('المعلومات الأساسية')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('الاسم')
                            ->required()
                            ->maxLength(255)
                            ->extraInputAttributes(['dir' => 'auto']),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            // Emails are always LTR.
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-align:left']),

                        TextInput::make('password')
                            ->label('كلمة المرور')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-align:left']),

                        Select::make('role')
                            ->label('الدور')
                            ->options(UserRole::options())
                            ->required()
                            ->live()
                            ->default(UserRole::WarehouseEmployee->value),

                        Select::make('warehouse_id')
                            ->label('المستودع')
                            ->relationship('warehouse', 'name')
                            // An administrator operates globally, so a warehouse assignment is hidden.
                            ->hidden(fn (Get $get): bool => $get('role') === UserRole::Administrator->value)
                            // A warehouse employee must be scoped to a location to operate.
                            ->required(fn (Get $get): bool => $get('role') === UserRole::WarehouseEmployee->value),
                    ]),

                Section::make('الصلاحيات الإضافية')
                    ->description('تُمنح هذه الصلاحيات إضافياً فوق دور الموظف. المدير يملكها جميعاً بشكل ضمني.')
                    // Administrators hold all capabilities implicitly, so toggles are only relevant for employees.
                    ->visible(fn (Get $get): bool => $get('role') === UserRole::WarehouseEmployee->value)
                    ->schema($capabilityToggles)
                    ->columns(1),
            ]);
    }
}
