<x-filament-panels::page>
    <style>
        .op-grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; }
        .op-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
        .op-card {
            background-color: var(--color-white, #ffffff);
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid var(--color-gray-200, #e5e7eb);
            text-align: center;
        }
        .dark .op-card {
            background-color: var(--color-gray-900, #111827);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .op-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            border-bottom: 1px solid var(--color-gray-200, #e5e7eb);
            margin-bottom: 1.5rem;
        }
        .dark .op-tabs {
            border-color: rgba(255, 255, 255, 0.1);
        }
        .op-tab-btn {
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--color-gray-500, #6b7280);
        }
        .dark .op-tab-btn {
            color: var(--color-gray-400, #9ca3af);
        }
        .op-tab-active {
            border-bottom-color: var(--color-primary-600, #f59e0b);
            color: var(--color-primary-600, #f59e0b);
        }
        .dark .op-tab-active {
            border-bottom-color: var(--color-primary-500, #f59e0b);
            color: var(--color-primary-500, #f59e0b);
        }
        .op-tab-inactive:hover {
            color: var(--color-gray-700, #374151);
            background-color: var(--color-gray-50, #f9fafb);
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .dark .op-tab-inactive:hover {
            color: var(--color-gray-200, #e5e7eb);
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .op-table-wrapper {
            overflow-x: auto;
            border: 1px solid var(--color-gray-200, #e5e7eb);
            border-radius: 0.75rem;
            background-color: var(--color-white, #ffffff);
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .op-table-wrapper {
            border-color: rgba(255, 255, 255, 0.1);
            background-color: var(--color-gray-900, #111827);
        }
        .op-table { width: 100%; text-align: right; border-collapse: collapse; }
        .op-table th {
            padding: 0.75rem 1rem;
            background-color: var(--color-gray-50, #f9fafb);
            font-weight: 600;
            color: var(--color-gray-500, #6b7280);
            border-bottom: 1px solid var(--color-gray-200, #e5e7eb);
        }
        .dark .op-table th {
            background-color: rgba(255, 255, 255, 0.03);
            color: var(--color-gray-400, #9ca3af);
            border-bottom-color: rgba(255, 255, 255, 0.1);
        }
        .op-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--color-gray-200, #e5e7eb);
        }
        .dark .op-table td {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        .op-table tbody tr:last-child td { border-bottom: none; }
        .op-table tbody tr:hover { background-color: var(--color-gray-50, #f9fafb); }
        .dark .op-table tbody tr:hover { background-color: rgba(255, 255, 255, 0.02); }

        .op-text-primary { color: var(--color-primary-600, #f59e0b); }
        .dark .op-text-primary { color: var(--color-primary-500, #f59e0b); }
        .op-text-emerald { color: var(--color-success-600, #10b981); }
        .dark .op-text-emerald { color: var(--color-success-500, #10b981); }
        .op-text-blue { color: var(--color-info-600, #3b82f6); }
        .dark .op-text-blue { color: var(--color-info-500, #3b82f6); }
        .op-text-red { color: var(--color-danger-600, #ef4444); }
        .dark .op-text-red { color: var(--color-danger-500, #ef4444); }
        .op-text-amber { color: var(--color-warning-600, #f59e0b); }
        .dark .op-text-amber { color: var(--color-warning-500, #f59e0b); }
        
        .op-flex-center { display: flex; align-items: center; justify-content: space-between; }
        .op-flex-gap { display: flex; align-items: center; gap: 1rem; }
    </style>

    <!-- Official Company Header -->
    <div class="op-flex-center op-card mb-4" style="text-align: right; padding: 1rem;">
        <div class="op-flex-gap">
            <img src="{{ asset('images/logo.png') }}" alt="HM Cargo Services" style="height: 48px; max-width: 100%; object-fit: contain;">
            <div>
                <h2 style="font-size: 1.125rem; font-weight: 700;">HM Cargo Services</h2>
                <p style="font-size: 0.75rem; color: var(--color-gray-500, #6b7280);">التقارير التشغيلية والمالية الرسمية — هاتف: +971521616814</p>
            </div>
        </div>
        <div style="text-align: left; font-size: 0.75rem; color: var(--color-gray-500, #6b7280);">
            {{ now()->format('Y-m-d H:i') }}
        </div>
    </div>

    <!-- Filter Section -->
    <div class="op-card" style="margin-bottom: 1.5rem; text-align: right;">
        <div class="op-grid-4">
            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">من تاريخ</label>
                <input type="date" wire:model.live="date_from" style="width: 100%; border-radius: 0.5rem; border: 1px solid var(--color-gray-300, #d1d5db); padding: 0.5rem; background: transparent;">
            </div>

            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">إلى تاريخ</label>
                <input type="date" wire:model.live="date_to" style="width: 100%; border-radius: 0.5rem; border: 1px solid var(--color-gray-300, #d1d5db); padding: 0.5rem; background: transparent;">
            </div>

            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">المستودع</label>
                <select wire:model.live="warehouse_id" style="width: 100%; border-radius: 0.5rem; border: 1px solid var(--color-gray-300, #d1d5db); padding: 0.5rem; background: transparent;">
                    <option value="">جميع المستودعات</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label style="display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem;">خط الشحن</label>
                <select wire:model.live="route_id" style="width: 100%; border-radius: 0.5rem; border: 1px solid var(--color-gray-300, #d1d5db); padding: 0.5rem; background: transparent;">
                    <option value="">جميع الخطوط</option>
                    @foreach($routes as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Reports Navigation Tabs -->
    <div class="op-tabs">
        <button wire:click="setTab('cargo')" class="op-tab-btn {{ $activeTab === 'cargo' ? 'op-tab-active' : 'op-tab-inactive' }}">
            📦 حركة المستودعات والطرود
        </button>

        <button wire:click="setTab('routes')" class="op-tab-btn {{ $activeTab === 'routes' ? 'op-tab-active' : 'op-tab-inactive' }}">
            🚛 أداء خطوط الشحن
        </button>

        @if($this->canViewMoney())
            <button wire:click="setTab('financial')" class="op-tab-btn {{ $activeTab === 'financial' ? 'op-tab-active' : 'op-tab-inactive' }}">
                💵 المقبوضات والمالية
            </button>
        @endif

        <button wire:click="setTab('exceptions')" class="op-tab-btn {{ $activeTab === 'exceptions' ? 'op-tab-active' : 'op-tab-inactive' }}">
            ⚠️ الاستثناءات والمشاكل
        </button>
    </div>

    <!-- TAB 1: Cargo & Warehouse Report -->
    @if($activeTab === 'cargo' && $cargo)
        <div class="op-grid-4" style="margin-bottom: 1.5rem;">
            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">إجمالي الشحنات المستلمة</div>
                <div class="op-text-primary" style="font-size: 1.875rem; font-weight: 700;">{{ $cargo['total_shipments'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">إجمالي عدد الطرود</div>
                <div class="op-text-primary" style="font-size: 1.875rem; font-weight: 700;">{{ $cargo['total_packages'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">إجمالي الوزن المشحون (كغ)</div>
                <div class="op-text-emerald" style="font-size: 1.875rem; font-weight: 700;">{{ $cargo['total_weight_kg'] }} كغ</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">طرود بانتظار الرحلة</div>
                <div class="op-text-blue" style="font-size: 1.875rem; font-weight: 700;">{{ $cargo['awaiting_packages'] }}</div>
            </div>
        </div>
    @endif

    <!-- TAB 2: Route & Freight Report -->
    @if($activeTab === 'routes' && $routeData)
        <div class="op-table-wrapper" style="margin-bottom: 1.5rem;">
            <table class="op-table text-sm">
                <thead class="text-xs uppercase">
                    <tr>
                        <th>خط الشحن</th>
                        <th>عدد الرحلات</th>
                        <th>إجمالي الوزن (كغ)</th>
                        @if($this->canViewMoney())
                            <th>إجمالي الإيرادات ($)</th>
                            <th>إجمالي التكلفة ($)</th>
                            <th>صافي الربح ($)</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($routeData as $r)
                        <tr>
                            <td style="font-weight: 700;">{{ $r['route_name'] }}</td>
                            <td>{{ $r['batches_count'] }} رحلة</td>
                            <td class="op-text-emerald" style="font-weight: 600;">{{ $r['total_weight_kg'] }} كغ</td>
                            @if($this->canViewMoney())
                                <td class="op-text-primary" style="font-weight: 700;">${{ $r['total_revenue_usd'] }}</td>
                                <td style="font-weight: 500; color: var(--color-gray-500, #6b7280);">${{ $r['total_cost_usd'] }}</td>
                                <td style="font-weight: 700;" class="{{ $r['is_profitable'] ? 'op-text-emerald' : 'op-text-red' }}">
                                    ${{ $r['net_profit_usd'] }}
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-gray-500, #6b7280);">لا توجد بيانات متاحة لهذ الفلاتر.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- TAB 3: Financial & Collections Report -->
    @if($activeTab === 'financial' && $this->canViewMoney() && $fin)
        <div class="op-grid-4" style="margin-bottom: 1.5rem;">
            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">إجمالي المقبوضات ($)</div>
                <div class="op-text-emerald" style="font-size: 1.875rem; font-weight: 700;">${{ $fin['total_collected_usd'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">مقبوضات كاش (نقدي)</div>
                <div style="font-size: 1.875rem; font-weight: 700;">${{ $fin['cash_usd'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">مقبوضات ويش (Whish)</div>
                <div class="op-text-primary" style="font-size: 1.875rem; font-weight: 700;">${{ $fin['whish_usd'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">تحويل بنكي وأخرى</div>
                <div class="op-text-blue" style="font-size: 1.875rem; font-weight: 700;">${{ $fin['bank_usd'] }}</div>
            </div>
        </div>

        <div class="op-table-wrapper" style="margin-bottom: 1.5rem;">
            <div style="padding: 1rem; border-bottom: 1px solid var(--color-gray-200, #e5e7eb); font-weight: 700;">سجل المقبوضات الأخيرة</div>
            <table class="op-table text-sm">
                <thead class="text-xs uppercase">
                    <tr>
                        <th>رقم الإيصال</th>
                        <th>العميل</th>
                        <th>المبلغ</th>
                        <th>طريقة الدفع</th>
                        <th>المستودع</th>
                        <th>التاريخ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fin['recent_payments'] as $p)
                        <tr>
                            <td class="op-text-primary" style="font-family: monospace; font-weight: 700;">{{ $p->receipt_number }}</td>
                            <td style="font-weight: 700;">{{ $p->customer->name ?? '—' }}</td>
                            <td class="op-text-emerald" style="font-weight: 700;">${{ number_format($p->amount_cents / 100, 2) }}</td>
                            <td>{{ $p->method === 'other' ? $p->custom_method_name : $p->method }}</td>
                            <td>{{ $p->warehouse->name ?? '—' }}</td>
                            <td style="font-size: 0.75rem; color: var(--color-gray-500, #6b7280);">{{ $p->collected_at->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--color-gray-500, #6b7280);">لا توجد مقبوضات مسجلة بهذه الفترة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- TAB 4: Exceptions Report -->
    @if($activeTab === 'exceptions' && $exc)
        <div class="op-grid-3" style="margin-bottom: 1.5rem;">
            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">طرود مفقودة</div>
                <div class="op-text-red" style="font-size: 1.875rem; font-weight: 700;">{{ $exc['missing_count'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">طرود متضررة</div>
                <div class="op-text-amber" style="font-size: 1.875rem; font-weight: 700;">{{ $exc['damaged_count'] }}</div>
            </div>

            <div class="op-card">
                <div style="font-size: 0.875rem; font-weight: 500; margin-bottom: 0.25rem; color: var(--color-gray-500, #6b7280);">شحنات ملغاة</div>
                <div style="font-size: 1.875rem; font-weight: 700; color: var(--color-gray-500, #6b7280);">{{ $exc['cancelled_count'] }}</div>
            </div>
        </div>

        <div class="op-table-wrapper" style="margin-bottom: 1.5rem;">
            <div style="padding: 1rem; border-bottom: 1px solid var(--color-gray-200, #e5e7eb); font-weight: 700;">تفاصيل طرود الاستثناءات الحالية</div>
            <table class="op-table text-sm">
                <thead class="text-xs uppercase">
                    <tr>
                        <th>الباركود</th>
                        <th>مرجع الشحنة</th>
                        <th>العميل</th>
                        <th>الحالة الاستثنائية</th>
                        <th>الوزن (كغ)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($exc['missing_packages'] as $pkg)
                        <tr>
                            <td class="op-text-red" style="font-family: monospace; font-weight: 700;">{{ $pkg->barcode }}</td>
                            <td style="font-weight: 700;">{{ $pkg->shipment->reference ?? '—' }}</td>
                            <td>{{ $pkg->shipment->customer->name ?? '—' }}</td>
                            <td><span style="padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px; background-color: var(--color-danger-50, #fef2f2); color: var(--color-danger-600, #ef4444);">مفقود</span></td>
                            <td>{{ number_format((float) $pkg->weight_kg, 2) }} كغ</td>
                        </tr>
                    @endforeach

                    @foreach($exc['damaged_packages'] as $pkg)
                        <tr>
                            <td class="op-text-amber" style="font-family: monospace; font-weight: 700;">{{ $pkg->barcode }}</td>
                            <td style="font-weight: 700;">{{ $pkg->shipment->reference ?? '—' }}</td>
                            <td>{{ $pkg->shipment->customer->name ?? '—' }}</td>
                            <td><span style="padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700; border-radius: 9999px; background-color: var(--color-warning-50, #fffbeb); color: var(--color-warning-600, #f59e0b);">متضرر</span></td>
                            <td>{{ number_format((float) $pkg->weight_kg, 2) }} كغ</td>
                        </tr>
                    @endforeach

                    @if($exc['missing_count'] === 0 && $exc['damaged_count'] === 0)
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem; color: var(--color-gray-500, #6b7280);">لا توجد طرود مفقودة أو متضررة مسجلة في هذه الفترة.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>


    @endif
</x-filament-panels::page>
