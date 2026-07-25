<x-filament-panels::page>
    <!-- Filter Section -->
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">من تاريخ</label>
                <input type="date" wire:model.live="date_from" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">إلى تاريخ</label>
                <input type="date" wire:model.live="date_to" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المستودع</label>
                <select wire:model.live="warehouse_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
                    <option value="">جميع المستودعات</option>
                    @foreach(\App\Models\Warehouse::all() as $w)
                        <option value="{{ $w->id }}">{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">خط الشحن</label>
                <select wire:model.live="route_id" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500 text-sm">
                    <option value="">جميع الخطوط</option>
                    @foreach(\App\Models\Route::all() as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Reports Navigation Tabs -->
    <div class="flex flex-wrap border-b border-gray-200 dark:border-gray-800 gap-2 mb-6">
        <button wire:click="setTab('cargo')" class="px-4 py-2 text-sm font-bold rounded-t-lg transition-all {{ $activeTab === 'cargo' ? 'bg-amber-500 text-white border-b-2 border-amber-600 shadow' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200' }}">
            📦 حركة المستودعات والطرود
        </button>

        <button wire:click="setTab('routes')" class="px-4 py-2 text-sm font-bold rounded-t-lg transition-all {{ $activeTab === 'routes' ? 'bg-amber-500 text-white border-b-2 border-amber-600 shadow' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200' }}">
            🚛 أداء خطوط الشحن
        </button>

        @if($this->canViewMoney())
            <button wire:click="setTab('financial')" class="px-4 py-2 text-sm font-bold rounded-t-lg transition-all {{ $activeTab === 'financial' ? 'bg-amber-500 text-white border-b-2 border-amber-600 shadow' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200' }}">
                💵 المقبوضات والمالية
            </button>
        @endif

        <button wire:click="setTab('exceptions')" class="px-4 py-2 text-sm font-bold rounded-t-lg transition-all {{ $activeTab === 'exceptions' ? 'bg-amber-500 text-white border-b-2 border-amber-600 shadow' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200' }}">
            ⚠️ الاستثناءات والمشاكل
        </button>
    </div>

    <!-- TAB 1: Cargo & Warehouse Report -->
    @if($activeTab === 'cargo')
        @php $cargo = $this->getCargoReport(); @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">إجمالي الشحنات المستلمة</div>
                <div class="text-2xl font-extrabold text-amber-500">{{ $cargo['total_shipments'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">إجمالي عدد الطرود</div>
                <div class="text-2xl font-extrabold text-amber-500">{{ $cargo['total_packages'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">إجمالي الوزن المشحون (كغ)</div>
                <div class="text-2xl font-extrabold text-emerald-500">{{ $cargo['total_weight_kg'] }} كغ</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">طرود بانتظار الرحلة</div>
                <div class="text-2xl font-extrabold text-blue-500">{{ $cargo['awaiting_packages'] }}</div>
            </div>
        </div>
    @endif

    <!-- TAB 2: Route & Freight Report -->
    @if($activeTab === 'routes')
        @php $routeData = $this->getRouteReport(); @endphp
        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="p-4">خط الشحن</th>
                            <th class="p-4">عدد الرحلات</th>
                            <th class="p-4">إجمالي الوزن (كغ)</th>
                            @if($this->canViewMoney())
                                <th class="p-4">إجمالي الإيرادات ($)</th>
                                <th class="p-4">إجمالي التكلفة ($)</th>
                                <th class="p-4">صافي الربح ($)</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($routeData as $r)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $r['route_name'] }}</td>
                                <td class="p-4">{{ $r['batches_count'] }} رحلة</td>
                                <td class="p-4 font-semibold text-emerald-500">{{ $r['total_weight_kg'] }} كغ</td>
                                @if($this->canViewMoney())
                                    <td class="p-4 font-bold text-amber-500">${{ $r['total_revenue_usd'] }}</td>
                                    <td class="p-4 font-medium text-gray-500">${{ $r['total_cost_usd'] }}</td>
                                    <td class="p-4 font-extrabold {{ $r['is_profitable'] ? 'text-emerald-500' : 'text-red-500' }}">
                                        ${{ $r['net_profit_usd'] }}
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-500">لا توجد بيانات متاحة لهذ الفلاتر.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 3: Financial & Collections Report -->
    @if($activeTab === 'financial' && $this->canViewMoney())
        @php $fin = $this->getFinancialReport(); @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">إجمالي المقبوضات ($)</div>
                <div class="text-2xl font-extrabold text-emerald-500">${{ $fin['total_collected_usd'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">مقبوضات كاش (نقدي)</div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">${{ $fin['cash_usd'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">مقبوضات ويش (Whish)</div>
                <div class="text-2xl font-bold text-amber-500">${{ $fin['whish_usd'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">تحويل بنكي وأخرى</div>
                <div class="text-2xl font-bold text-blue-500">${{ $fin['bank_usd'] }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 font-bold text-gray-900 dark:text-white">سجل المقبوضات الأخيرة</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="p-4">رقم الإيصال</th>
                            <th class="p-4">العميل</th>
                            <th class="p-4">المبلغ</th>
                            <th class="p-4">طريقة الدفع</th>
                            <th class="p-4">المستودع</th>
                            <th class="p-4">التاريخ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @forelse($fin['recent_payments'] as $p)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-4 font-mono font-bold text-amber-500">{{ $p->receipt_number }}</td>
                                <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $p->customer->name ?? '—' }}</td>
                                <td class="p-4 font-extrabold text-emerald-500">${{ number_format($p->amount_cents / 100, 2) }}</td>
                                <td class="p-4">{{ $p->method === 'other' ? $p->custom_method_name : $p->method }}</td>
                                <td class="p-4">{{ $p->warehouse->name ?? '—' }}</td>
                                <td class="p-4 text-xs text-gray-500">{{ $p->collected_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-6 text-center text-gray-500">لا توجد مقبوضات مسجلة بهذه الفترة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- TAB 4: Exceptions Report -->
    @if($activeTab === 'exceptions')
        @php $exc = $this->getExceptionsReport(); @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">طرود مفقودة</div>
                <div class="text-2xl font-extrabold text-red-500">{{ $exc['missing_count'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">طرود متضررة</div>
                <div class="text-2xl font-extrabold text-amber-500">{{ $exc['damaged_count'] }}</div>
            </div>

            <div class="p-5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">شحنات ملغاة</div>
                <div class="text-2xl font-extrabold text-gray-500">{{ $exc['cancelled_count'] }}</div>
            </div>
        </div>

        <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-gray-800 font-bold text-gray-900 dark:text-white">تفاصيل طرود الاستثناءات الحالية</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right text-gray-700 dark:text-gray-300">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">
                        <tr>
                            <th class="p-4">الباركود</th>
                            <th class="p-4">مرجع الشحنة</th>
                            <th class="p-4">العميل</th>
                            <th class="p-4">الحالة الاستثنائية</th>
                            <th class="p-4">الوزن (كغ)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach($exc['missing_packages'] as $pkg)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-4 font-mono font-bold text-red-500">{{ $pkg->barcode }}</td>
                                <td class="p-4 font-bold">{{ $pkg->shipment->reference ?? '—' }}</td>
                                <td class="p-4">{{ $pkg->shipment->customer->name ?? '—' }}</td>
                                <td class="p-4"><span class="px-2.5 py-1 text-xs font-bold rounded-full bg-red-100 text-red-700">مفقود</span></td>
                                <td class="p-4">{{ number_format($pkg->weight_grams / 1000, 2) }} كغ</td>
                            </tr>
                        @endforeach

                        @foreach($exc['damaged_packages'] as $pkg)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-4 font-mono font-bold text-amber-500">{{ $pkg->barcode }}</td>
                                <td class="p-4 font-bold">{{ $pkg->shipment->reference ?? '—' }}</td>
                                <td class="p-4">{{ $pkg->shipment->customer->name ?? '—' }}</td>
                                <td class="p-4"><span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700">متضرر</span></td>
                                <td class="p-4">{{ number_format($pkg->weight_grams / 1000, 2) }} كغ</td>
                            </tr>
                        @endforeach

                        @if($exc['missing_count'] === 0 && $exc['damaged_count'] === 0)
                            <tr>
                                <td colspan="5" class="p-6 text-center text-gray-500">لا توجد طرود مفقودة أو متضررة مسجلة في هذه الفترة.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-filament-panels::page>
