<?php
$content = file_get_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php');

$useStatements = "use Filament\Tables\Columns\Layout\Split;\nuse Filament\Tables\Columns\Layout\Stack;";
$content = preg_replace('/use Filament\\\\Tables\\\\Columns\\\\TextColumn;/', "use Filament\\Tables\\Columns\\TextColumn;\n" . $useStatements, $content);

$columnsReplacement = <<<EOT
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('reference')
                            ->label('رقم الشحنة')
                            ->weight('bold')
                            ->searchable()
                            ->sortable()
                            ->copyable(),
                            
                        TextColumn::make('created_at')
                            ->label('التاريخ')
                            ->date('Y-m-d')
                            ->alignEnd()
                            ->sortable(),
                    ]),
                    
                    Stack::make([
                        TextColumn::make('customer.name')
                            ->label('العميل')
                            ->icon('heroicon-m-user')
                            ->searchable()
                            ->sortable(),
                            
                        TextColumn::make('recipient_name')
                            ->label('المستلم')
                            ->icon('heroicon-m-truck')
                            ->searchable(),
                    ])->space(1),
                    
                    Split::make([
                        TextColumn::make('packages_count')
                            ->label('الطرود')
                            ->icon('heroicon-m-cube')
                            ->counts('packages'),
                            
                        TextColumn::make('total_weight_kg')
                            ->label('الوزن')
                            ->icon('heroicon-m-scale')
                            ->formatStateUsing(fn (\$state): string => rtrim(rtrim(number_format((float) \$state, 4), '0'), '.').' كغ')
                            ->sortable(),
                    ]),

                    Stack::make([
                        TextColumn::make('status')
                            ->label('الحالة')
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
                            ->label('حالة الدفع')
                            ->state(fn (Shipment \$record): string => \$record->paymentStatusLabel())
                            ->badge()
                            ->color(fn (string \$state): string => match (true) {
                                str_contains(\$state, 'بالكامل') => 'success',
                                str_contains(\$state, 'جزئياً') => 'warning',
                                str_contains(\$state, 'غير مدفوع') => 'danger',
                                default => 'gray',
                            }),

                        TextColumn::make('notification_status')
                            ->label('الإشعار')
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
                    ])->space(1),
                ])->space(3),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
EOT;

$content = preg_replace('/->columns\(\[.*?\]\)/s', $columnsReplacement, $content, 1);
$content = str_replace("->contentGrid([\n                'md' => 2,\n                'xl' => 3,\n            ])\n            ->contentGrid([\n                'md' => 2,\n                'xl' => 3,\n            ])", "->contentGrid([\n                'md' => 2,\n                'xl' => 3,\n            ])", $content);

file_put_contents('app/Filament/Resources/Shipments/Tables/ShipmentsTable.php', $content);
echo "Done\n";
