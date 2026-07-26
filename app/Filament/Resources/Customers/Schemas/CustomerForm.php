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
                    ->maxLength(255)
                    // dir="auto" lets the browser choose direction from the
                    // first strong character, so an Arabic name reads
                    // right-to-left and a Latin one left-to-right. A fixed
                    // direction would mis-align one of the two.
                    ->extraInputAttributes(['dir' => 'auto']),

                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->required()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->maxLength(32)
                    ->unique(ignoreRecord: true)
                    // A phone number is always left-to-right, even in an RTL
                    // form: "+971 50 123" must never be reordered on screen.
                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'text-align:left'])
                    ->rule('regex:/^\+?[0-9\s\-()]{7,}$/')
                    ->validationMessages([
                        'regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط، ويمكن أن يبدأ بـ + ويحوي مسافات أو شرطات.',
                        'unique' => 'رقم الهاتف مسجَّل لعميل آخر.',
                    ])
                    ->helperText('مثال: ‎+971501234567'),

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
