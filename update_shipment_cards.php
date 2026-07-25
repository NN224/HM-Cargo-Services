<?php
$content = file_get_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php');

$useStatements = <<<EOT
use Filament\Tables\Columns\Layout\Grid;
EOT;

if (!str_contains($content, 'use Filament\Tables\Columns\Layout\Grid;')) {
    $content = preg_replace('/use Filament\\\\Tables\\\\Columns\\\\Layout\\\\Split;/', "use Filament\\Tables\\Columns\\Layout\\Split;\nuse Filament\\Tables\\Columns\\Layout\\Grid;", $content);
}

$columnsReplacement = <<<EOT
            ->columns([
                Stack::make([
                    Grid::make(2)->schema([
                        TextColumn::make('reference')
                            ->description('رقم الشحنة', 'above')
                            ->weight('bold')
                            ->searchable()
                            ->sortable()
                            ->copyable(),
                            
                        TextColumn::make('created_at')
                            ->description('التاريخ', 'above')
                            ->date('Y-m-d')
                            ->sortable(),
                    ]),
                    
                    Grid::make(2)->schema([
                        TextColumn::make('customer.name')
                            ->description('العميل', 'above')
                            ->icon('heroicon-m-user')
                            ->searchable()
                            ->sortable(),
                            
                        TextColumn::make('recipient_name')
                            ->description('المستلم', 'above')
                            ->icon('heroicon-m-truck')
                            ->searchable(),
                    ]),
                    
                    Grid::make(2)->schema([
                        TextColumn::make('packages_count')
                            ->description('الطرود', 'above')
                            ->icon('heroicon-m-cube')
                            ->counts('packages'),
                            
                        TextColumn::make('total_weight_kg')
                            ->description('الوزن', 'above')
                            ->icon('heroicon-m-scale')
                            ->formatStateUsing(fn (\$state): string => rtrim(rtrim(number_format((float) \$state, 4), '0'), '.').' كغ')
                            ->sortable(),
                    ]),

                    Grid::make(2)->schema([
                        TextColumn::make('status')
                            ->description('الحالة', 'above')
                            ->badge()
                            ->formatStateUsing(fn (ShipmentStatus \$state): string => \$state->label())
                            ->color(fn (ShipmentStatus \$state): string => match (\$state) {
                                ShipmentStatus::Draft, ShipmentStatus::AwaitingBatch, ShipmentStatus::Assigned => 'gray',
                                ShipmentStatus::Pending => 'warning',
                                ShipmentStatus::InTransit, ShipmentStatus::PartialAtTransit, ShipmentStatus::AtTransit => 'info',
                                ShipmentStatus::PartialAtDestination => 'info',
                                ShipmentStatus::ReadyForCollection, ShipmentStatus::Arrived => 'primary',
                                ShipmentStatus::Collected, ShipmentStatus::PartiallyCollected => 'success',
                                ShipmentStatus::Cancelled => 'danger',
                                ShipmentStatus::Exception => 'danger',
                            }),

                        TextColumn::make('payment_status')
                            ->description('حالة الدفع', 'above')
                            ->state(fn (Shipment \$record): string => \$record->paymentStatusLabel())
                            ->badge()
                            ->color(fn (string \$state): string => match (true) {
                                str_contains(\$state, 'بالكامل') => 'success',
                                str_contains(\$state, 'جزئياً') => 'warning',
                                str_contains(\$state, 'غير مدفوع') => 'danger',
                                default => 'gray',
                            }),
                    ]),
                    
                    Grid::make(1)->schema([
                        TextColumn::make('notification_status')
                            ->description('الإشعار', 'above')
                            ->state(function (Shipment \$record): string {
                                if (\$record->arrival_notified_at !== null) {
                                    return 'تم إشعار الوصول';
                                }
                                if (\$record->status === ShipmentStatus::ReadyForCollection) {
                                    return '⚠️ يحتاج إشعار وصول!';
                                }
                                if (\$record->intake_notified_at !== null) {
                                    return 'تم إرسال التتبع';
                                }
                                return '—';
                            })
                            ->badge()
                            ->color(fn (string \$state): string => match (true) {
                                str_contains(\$state, 'تم') => 'success',
                                str_contains(\$state, '⚠️') => 'warning',
                                default => 'gray',
                            }),
                    ]),
                ])->space(3),
            ])
EOT;

$content = preg_replace('/->columns\(\[\s+Stack::make\(.*?\]\)->space\(3\),\s+\]\)/s', $columnsReplacement, $content, 1);

file_put_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php', $content);
echo "Done\n";
