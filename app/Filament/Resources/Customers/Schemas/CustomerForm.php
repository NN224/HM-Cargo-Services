<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم العميل')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->required()
                    ->maxLength(32)
                    ->unique(ignoreRecord: true),

                Toggle::make('is_credit_customer')
                    ->label('عميل آجل')
                    ->helperText('العميل الآجل يستلم شحنته دون دفع فوري ويحمل رصيداً مستحقاً.')
                    ->default(false),

                Toggle::make('is_active')
                    ->label('نشط')
                    ->helperText('العملاء يُعطَّلون ولا يُحذفون نهائياً.')
                    ->default(true),
            ]);
    }
}
