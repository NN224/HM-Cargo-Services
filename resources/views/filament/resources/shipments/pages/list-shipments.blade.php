@php
    use App\Filament\Resources\Shipments\ShipmentResource;
    use App\Enums\PackageStatus;
    use App\Enums\ShipmentStatus;
    use App\Models\Batch;
    use App\Models\Shipment;
    use App\Services\PackageJourneyProjection;

    $selectedBatch = $this->selectedBatchId
        ? Batch::with(['route.originWarehouse', 'route.destinationWarehouse', 'shipments'])->find($this->selectedBatchId)
        : null;

    $shipmentsQuery = Shipment::query()
        ->with(['batch.route.originWarehouse', 'batch.route.destinationWarehouse', 'customer', 'packages'])
        ->withCount('packages')
        ->latest('created_at');

    if ($this->selectedBatchId) {
        $shipmentsQuery->where('batch_id', $this->selectedBatchId);
    }

    if (filled($this->shipmentSearch)) {
        $search = trim($this->shipmentSearch);
        $shipmentsQuery->where(function ($query) use ($search) {
            $query->where('reference', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
        });
    }

    if (filled($this->shipmentStatusFilter)) {
        $shipmentsQuery->where('status', $this->shipmentStatusFilter);
    }

    $visibleShipments = $shipmentsQuery->get();
    $visibleShipmentIds = $visibleShipments->pluck('id')->values()->all();
    $visibleWeight = rtrim(rtrim(number_format((float) $visibleShipments->sum('total_weight_kg'), 2), '0'), '.');
    $currentTitle = $selectedBatch?->reference ?? 'تجميع الرحلات';
    $currentRoute = $selectedBatch
        ? (($selectedBatch->route?->originWarehouse?->name ?? 'Dubai').' ← '.($selectedBatch->route?->destinationWarehouse?->name ?? 'الوجهة'))
        : 'عرض كل الشحنات النشطة · المرحلة الحالية محسوبة حسب أبطأ شحنة';
    $bulkLabelShipment = $visibleShipments->first(fn ($shipment) => in_array($shipment->id, $this->selectedShipmentIds, true));
    $bulkLabelRoute = $bulkLabelShipment?->batch?->route ?? $selectedBatch?->route;
    $journeyProjection = app(PackageJourneyProjection::class);
@endphp

<x-filament-panels::page>
    <div class="hm-frame" dir="rtl">
        <header class="hm-screen-bar">
            <div class="hm-brand"><span>HM</span> HM Cargo Services</div>
            <div class="hm-context">
                <span>الشحنات</span>
                <span>{{ now()->translatedFormat('d F Y') }}</span>
                <span class="hm-online"><i></i> النظام متصل</span>
            </div>
        </header>

        <div class="hm-split">
            <section class="hm-workspace">
                <div class="hm-section-title">
                    <div>
                        <h2>{{ $currentTitle }}</h2>
                        <p>{{ $currentRoute }}</p>
                    </div>
                    <span class="hm-count-badge">{{ $visibleShipments->count() }} شحنات · {{ $visibleWeight }} كغ</span>
                </div>

                <div class="hm-toolbar">
                    <label class="hm-search-wrap">
                        <input
                            class="hm-search"
                            type="search"
                            placeholder="بحث في الشحنات..."
                            wire:model.live.debounce.300ms="shipmentSearch"
                        >
                    </label>
                    <details class="hm-filter-menu">
                        <summary class="hm-ghost-btn">☷ الفلاتر</summary>
                        <div class="hm-filter-panel">
                            <label>
                                <span>الحالة التشغيلية</span>
                                <select wire:model.live="shipmentStatusFilter">
                                    <option value="">كل الحالات</option>
                                    @foreach (ShipmentStatus::options() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </details>
                </div>

                @if (count($this->selectedShipmentIds) > 0)
                    <div class="hm-bulk-bar">
                        <div class="hm-bulk-copy">
                            <span class="hm-bulk-count">{{ count($this->selectedShipmentIds) }}</span>
                            <span><strong>شحنات محددة</strong><br><small>جاهزة لتغيير الحالة الجماعي</small></span>
                        </div>
                        <div class="hm-bulk-actions">
                            <label class="hm-bulk-field">
                                <span>الحالة الجديدة</span>
                                <select wire:model.live="bulkTargetStatus">
                                    <option value="">المرحلة التالية تلقائياً</option>
                                    @if (auth()->user()?->isAdministrator())
                                        @foreach (PackageStatus::journeySteps() as $status)
                                            @if ($status !== \App\Enums\PackageStatus::Collected)
                                                <option value="{{ $status->value }}">{{ $journeyProjection->labelFor($bulkLabelRoute, $status) }}</option>
                                            @endif
                                        @endforeach
                                    @endif
                                </select>
                            </label>
                            
                            <button class="hm-primary-btn" wire:click="bulkAdvanceSelectedShipments" wire:loading.attr="disabled">
                                تحديث {{ count($this->selectedShipmentIds) }} شحنات
                            </button>

                            <div style="flex-basis: 100%; height: 0;"></div>

                            <label class="hm-bulk-field" style="flex: 1;">
                                <span>إشعار للعميل</span>
                                <input type="text" wire:model.live="bulkMessage" placeholder="نص الرسالة للعميل">
                            </label>
                            
                            <button class="hm-primary-btn" wire:click="bulkSendMessageSelectedShipments" wire:loading.attr="disabled">
                                إرسال الرسالة
                            </button>
                            
                            <button class="hm-ghost-btn" wire:click="clearShipmentSelection">إلغاء</button>
                        </div>
                    </div>
                @endif

                <button
                    type="button"
                    class="hm-select-all"
                    wire:click="toggleVisibleShipments(@js($visibleShipmentIds))"
                    aria-pressed="{{ count($visibleShipmentIds) > 0 && empty(array_diff($visibleShipmentIds, $this->selectedShipmentIds)) ? 'true' : 'false' }}"
                >
                    <span class="hm-check-box {{ count($visibleShipmentIds) > 0 && empty(array_diff($visibleShipmentIds, $this->selectedShipmentIds)) ? 'is-checked' : '' }}"></span>
                    تحديد كل الشحنات الظاهرة
                </button>

                @if ($visibleShipments->isEmpty())
                    <div class="hm-empty"><strong>لا توجد شحنات مطابقة</strong><span>جرّب تغيير البحث أو اختيار رحلة أخرى.</span></div>
                @else
                    <div class="hm-shipment-grid">
                        @foreach ($visibleShipments as $shipment)
                            @php
                                $journey = app(PackageJourneyProjection::class)->forShipment($shipment);
                                $steps = $journey['steps'] ?? [];
                                $packageCount = $journey['package_count'] ?? 0;
                                $currentStep = -1;
                                $lastDone = -1;
                                foreach ($steps as $idx => $step) {
                                    if ($step['completed_count'] >= $packageCount && $packageCount > 0) $lastDone = $idx;
                                    if ($step['current_count'] > 0) { $currentStep = $idx; break; }
                                }
                                $activeStep = $currentStep >= 0 ? $currentStep : max($lastDone, 0);
                                $stageLabel = $steps[$activeStep]['label'] ?? 'قيد التجهيز';
                                $unpaidCents = max(0, (int) ($shipment->final_charge_cents ?? 0) - (int) ($shipment->paid_amount_cents ?? 0));
                                $isSelected = in_array($shipment->id, $this->selectedShipmentIds, true);
                            @endphp
                            <article class="hm-shipment-card {{ $isSelected ? 'is-selected' : '' }}">
                                <div class="hm-shipment-top">
                                    <details class="hm-actions-menu">
                                        <summary class="hm-primary-btn">إجراءات :</summary>
                                        <div class="hm-actions-panel">
                                            <a href="{{ ShipmentResource::getUrl('edit', ['record' => $shipment]) }}">تعديل</a>
                                            <a href="{{ ShipmentResource::getUrl('view', ['record' => $shipment]) }}">عرض التفاصيل</a>
                                            <a href="{{ route('labels.shipment', $shipment) }}" target="_blank">طباعة الملصقات</a>
                                            <button type="button" wire:click="mountTableAction('whatsappIntake', '{{ $shipment->id }}')">واتساب: رابط التتبع</button>
                                            @if ($shipment->status === \App\Enums\ShipmentStatus::ReadyForCollection)
                                                <button type="button" wire:click="mountTableAction('whatsappArrival', '{{ $shipment->id }}')">واتساب: إشعار الوصول</button>
                                            @endif
                                            @if (in_array($shipment->status, [\App\Enums\ShipmentStatus::PartialAtDestination, \App\Enums\ShipmentStatus::PartiallyCollected, \App\Enums\ShipmentStatus::InTransit, \App\Enums\ShipmentStatus::AtTransit]))
                                                <button type="button" wire:click="mountTableAction('partialCollect', '{{ $shipment->id }}')">تسليم الطرود الواصلة</button>
                                            @endif
                                        </div>
                                    </details>
                                    <div class="hm-shipment-id">
                                        <button
                                            type="button"
                                            class="hm-card-check"
                                            wire:click="toggleShipmentSelection({{ $shipment->id }})"
                                            aria-label="تحديد الشحنة {{ $shipment->reference }}"
                                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                        >
                                            <span class="hm-check-box {{ $isSelected ? 'is-checked' : '' }}"></span>
                                        </button>
                                        <strong>{{ $shipment->reference }}</strong>
                                        <span>{{ $shipment->created_at?->format('Y-m-d') }}</span>
                                    </div>
                                </div>

                                <div class="hm-stepper">
                                    <div class="hm-stepper-head"><span>مسار الشحنة</span><span class="hm-stage-badge">{{ $stageLabel }}</span></div>
                                    <div class="hm-steps">
                                        @foreach ($steps as $idx => $step)
                                            <div class="hm-step {{ $idx === $activeStep ? 'is-current' : '' }}">
                                                <span>{{ $idx + 1 }}</span>
                                                {{ $step['label'] }}
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="hm-detail-grid">
                                    <div><span>الرحلة</span><strong>🚚 {{ $shipment->batch?->reference ?? 'غير مسندة' }}</strong></div>
                                    <div>
                                        <span>الحالة والإشعارات</span>
                                        <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 2px;">
                                            <strong class="hm-neutral-badge" style="width: max-content;">{{ app(\App\Services\PackageJourneyProjection::class)->labelForShipment($shipment->batch?->route, $shipment->status) }}</strong>
                                            
                                            @if (is_null($shipment->intake_notified_at))
                                                <button type="button" wire:click="mountTableAction('whatsappIntake', '{{ $shipment->id }}')" class="hm-money-badge" style="width: max-content; font-size: 10px; cursor: pointer;">رسالة الاستلام: إرسال الآن 💬</button>
                                            @else
                                                <strong class="hm-success-badge" style="width: max-content; font-size: 10px;">رسالة الاستلام: مرسلة ✓</strong>
                                            @endif

                                            @if (in_array($shipment->status, [App\Enums\ShipmentStatus::PartialAtDestination, App\Enums\ShipmentStatus::ReadyForCollection, App\Enums\ShipmentStatus::Arrived, App\Enums\ShipmentStatus::PartiallyCollected, App\Enums\ShipmentStatus::Collected]) && is_null($shipment->arrival_notified_at))
                                                <button type="button" wire:click="mountTableAction('whatsappArrival', '{{ $shipment->id }}')" class="hm-money-badge" style="width: max-content; font-size: 10px; cursor: pointer;">رسالة الوصول: إرسال الآن 💬</button>
                                            @elseif (!is_null($shipment->arrival_notified_at))
                                                <strong class="hm-success-badge" style="width: max-content; font-size: 10px;">رسالة الوصول: مرسلة ✓</strong>
                                            @endif
                                        </div>
                                    </div>
                                    <div><span>العميل</span><strong>{{ $shipment->customer?->name }}</strong></div>
                                    <div><span>المستلم</span><strong>{{ $shipment->recipient_name }}</strong></div>
                                    <div><span>الطرود</span><strong>▣ {{ $shipment->packages_count }}</strong></div>
                                    <div><span>الوزن</span><strong>⚖ {{ rtrim(rtrim(number_format((float) $shipment->total_weight_kg, 2), '0'), '.') }} كغ</strong></div>
                                </div>

                                <div class="hm-card-footer">
                                    <span class="hm-money-badge">${{ number_format($unpaidCents / 100, 2) }} غير مدفوع</span>
                                    <div class="hm-card-actions">
                                        <a class="hm-mini-btn" href="{{ ShipmentResource::getUrl('edit', ['record' => $shipment]) }}">تعديل</a>
                                        <a class="hm-mini-btn" href="{{ ShipmentResource::getUrl('view', ['record' => $shipment]) }}">عرض</a>
                                        <a class="hm-mini-btn" href="{{ route('labels.shipment', $shipment) }}" target="_blank">طباعة</a>
                                        <a class="hm-icon-btn" href="#" wire:click.prevent="mountTableAction('manageJourney', '{{ $shipment->id }}')" title="تحديث طرود الشحنة">•••</a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>

            <aside class="hm-sidebar">
                @include('filament.tables.components.journey-sidebar')
            </aside>
        </div>
    </div>

    <style>
        .hm-frame{overflow:hidden;border:1px solid #292f39;border-radius:20px;background:rgba(13,16,21,.96);box-shadow:0 18px 48px rgba(0,0,0,.24)}
        .hm-select-all,.hm-card-check{font:inherit;text-align:inherit;cursor:pointer}.hm-card-check{display:inline-flex;padding:0;border:0;background:transparent;color:inherit}.hm-select-all input[type=checkbox],.hm-card-check input[type=checkbox]{display:none}.hm-check-box{display:inline-flex;align-items:center;justify-content:center;width:13px;height:13px;border:1px solid #4b5565;border-radius:3px;background:#0d1014;box-shadow:inset 0 0 0 2px #0d1014}.hm-check-box.is-checked{border-color:#f28a12;background:#f28a12}.hm-check-box.is-checked::after{content:"";width:5px;height:8px;border:solid #16100a;border-width:0 2px 2px 0;transform:rotate(45deg) translateY(-1px)}.hm-bulk-field{display:flex;flex:1 1 210px;min-width:0;gap:6px;align-items:center;color:#929aa8;font-size:11px;font-weight:800}.hm-bulk-reason{flex-basis:240px}.hm-bulk-field select,.hm-bulk-field input{width:100%;height:40px;min-width:0;max-width:100%;box-sizing:border-box;padding:0 10px;border:1px solid #384152;border-radius:9px;background:#0d1014;color:#f7f8fa;outline:0}.hm-bulk-field select:focus,.hm-bulk-field input:focus{border-color:#4f8cff;box-shadow:0 0 0 3px rgba(79,140,255,.12)}
        .hm-screen-bar{display:flex;justify-content:space-between;gap:20px;align-items:center;min-height:62px;padding:12px 18px;border-bottom:1px solid #292f39;background:#0d1015}.hm-brand{display:flex;gap:9px;align-items:center;font-size:13px;font-weight:800}.hm-brand span{color:#f28a12;font-size:19px;font-weight:950}.hm-context{display:flex;gap:16px;align-items:center;color:#929aa8;font-size:12px}.hm-online{display:flex;gap:8px;align-items:center}.hm-online i{width:7px;height:7px;border-radius:99px;background:#59cf91;box-shadow:0 0 0 4px rgba(89,207,145,.12)}
        .hm-split{display:grid;grid-template-columns:minmax(0,1fr) minmax(250px,.37fr);min-height:625px}.hm-workspace{min-width:0;padding:20px}.hm-sidebar{border-right:1px solid #292f39;background:#0c0f14}.hm-section-title{display:flex;justify-content:space-between;gap:18px;align-items:start;margin-bottom:14px}.hm-section-title h2{margin:0;font-size:18px;line-height:1.35}.hm-section-title p{margin:4px 0 0;color:#929aa8;font-size:12px}.hm-count-badge,.hm-neutral-badge{display:inline-flex;gap:5px;align-items:center;min-width:max-content;padding:4px 8px;border:1px solid #292f39;border-radius:999px;background:#1c2129;color:#929aa8;font-size:11px;font-weight:750}.hm-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:14px;padding:13px;border:1px solid #292f39;border-radius:12px;background:#111419}.hm-search-wrap{position:relative;flex:1}.hm-search{width:100%;height:40px;padding:0 42px 0 12px;border:1px solid #292f39;outline:0;border-radius:10px;background:#0d1014;color:#f7f8fa}.hm-search:focus{border-color:#4f8cff;box-shadow:0 0 0 3px rgba(79,140,255,.12)}.hm-ghost-btn,.hm-mini-btn,.hm-icon-btn{border:1px solid #292f39;border-radius:9px;background:#171b21;color:#929aa8;cursor:pointer;transition:.16s;text-decoration:none}.hm-ghost-btn{height:40px;padding:0 13px;display:inline-flex;align-items:center}.hm-primary-btn{height:40px;padding:0 15px;border:1px solid #f28a12;border-radius:9px;background:#f28a12;color:#16100a;font-weight:900;display:inline-flex;align-items:center;cursor:pointer}.hm-filter-menu,.hm-actions-menu{position:relative}.hm-filter-menu summary,.hm-actions-menu summary{list-style:none}.hm-filter-menu summary::-webkit-details-marker,.hm-actions-menu summary::-webkit-details-marker{display:none}.hm-filter-panel,.hm-actions-panel{position:absolute;z-index:30;top:calc(100% + 8px);left:0;min-width:190px;padding:10px;border:1px solid #292f39;border-radius:10px;background:#111419;box-shadow:0 18px 48px rgba(0,0,0,.28)}.hm-actions-panel{right:0;left:auto;display:grid;gap:6px}.hm-actions-panel a, .hm-actions-panel button{padding:8px 10px;border-radius:8px;color:#d8dde6;text-decoration:none;font-size:12px; background:none; border:none; text-align:right; font-family:inherit; cursor:pointer; width:100%;}.hm-actions-panel a:hover, .hm-actions-panel button:hover{background:#1c2129}.hm-filter-panel label{display:grid;gap:6px;color:#929aa8;font-size:11px}.hm-filter-panel select{height:36px;border:1px solid #292f39;border-radius:8px;background:#0d1014;color:#f7f8fa}.hm-select-all{display:flex;gap:8px;align-items:center;color:#929aa8;font-size:11px;margin:2px 2px 11px;width:max-content;cursor:pointer}.hm-bulk-bar{display:flex;justify-content:space-between;gap:14px;align-items:center;margin-bottom:12px;padding:11px 13px;border:1px solid rgba(79,140,255,.35);border-radius:11px;background:rgba(79,140,255,.12)}.hm-bulk-copy{display:flex;gap:9px;align-items:center;font-size:12px}.hm-bulk-copy small{color:#929aa8}.hm-bulk-count{display:grid;place-items:center;width:25px;height:25px;border-radius:50%;background:#4f8cff;color:white;font-weight:900}.hm-bulk-actions{display:flex;gap:8px;align-items:center}.hm-shipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.hm-shipment-card{position:relative;padding:15px;border:1px solid #292f39;border-radius:13px;background:#111419;transition:.16s}.hm-shipment-card.is-selected{border-color:#4f8cff;box-shadow:0 0 0 3px rgba(79,140,255,.12)}.hm-shipment-top{display:flex;justify-content:space-between;gap:12px;align-items:start}.hm-shipment-id{display:grid;gap:2px;text-align:left;direction:ltr}.hm-shipment-id strong{font-weight:900}.hm-shipment-id span{color:#929aa8;font-size:11px}.hm-stepper{margin:14px 0;padding:11px;border:1px solid #292f39;border-radius:11px;background:#0d1014}.hm-stepper-head{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:10px;color:#929aa8;font-size:11px}.hm-stage-badge{display:inline-flex;padding:4px 8px;border:1px solid rgba(233,200,83,.25);border-radius:999px;background:rgba(233,200,83,.1);color:#e9c853;font-size:11px;font-weight:750}.hm-steps{display:grid;grid-template-columns:repeat(7,1fr);direction:rtl}.hm-step{position:relative;text-align:center;color:#666f7d;font-size:9px;line-height:1.2}.hm-step span{position:relative;z-index:1;display:grid;place-items:center;width:19px;height:19px;margin:0 auto 5px;border:1px solid #3a424f;border-radius:50%;background:#15191f;font-size:9px}.hm-step.is-current{color:#9fc0ff}.hm-step.is-current span{border-color:#4f8cff;background:#4f8cff;color:white;box-shadow:0 0 0 3px rgba(79,140,255,.12)}.hm-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:11px 18px;margin-top:13px}.hm-detail-grid span{display:block;color:#929aa8;font-size:10px}.hm-detail-grid strong{display:block;margin-top:2px;overflow:hidden;font-size:12px;font-weight:730;text-overflow:ellipsis;white-space:nowrap}.hm-card-footer{display:flex;justify-content:space-between;gap:10px;align-items:center;margin-top:14px;padding-top:12px;border-top:1px solid #292f39}.hm-money-badge{display:inline-flex;padding:4px 8px;border:1px solid rgba(255,119,119,.25);border-radius:999px;background:rgba(255,119,119,.12);color:#ffb0b0;font-size:11px;font-weight:750}.hm-success-badge{display:inline-flex;padding:4px 8px;border:1px solid rgba(16,185,129,.3);border-radius:999px;background:rgba(16,185,129,.15);color:#6ee7b7;font-size:11px;font-weight:750}.hm-card-actions{display:flex;gap:7px;align-items:center}.hm-mini-btn{min-height:32px;padding:5px 10px;font-size:12px}.hm-icon-btn{display:grid;place-items:center;width:34px;height:34px}.hm-empty{display:grid;place-items:center;min-height:260px;padding:35px;border:1px dashed #3a424f;border-radius:13px;color:#929aa8;text-align:center}.hm-empty strong{color:#f7f8fa}.hm-empty span{display:block;margin-top:5px;font-size:12px}
        .hm-bulk-bar{flex-wrap:wrap;overflow:hidden;box-sizing:border-box}.hm-bulk-copy{flex:0 1 auto;min-width:0}.hm-bulk-actions{flex:1 1 520px;min-width:0;flex-wrap:wrap;justify-content:flex-end}.hm-bulk-actions>.hm-primary-btn,.hm-bulk-actions>.hm-ghost-btn{flex:0 0 auto;white-space:nowrap}
        .hm-action-link{background:none;border:none;color:#d8dde6;cursor:pointer;text-align:right;width:100%;font:inherit;padding:8px 10px;border-radius:8px;font-size:12px;display:block}
        .hm-action-link:hover{background:#1c2129}
        @media(max-width:900px){.hm-split{display:flex;flex-direction:column-reverse}.hm-sidebar{border-right:0;border-bottom:1px solid #292f39}.hm-shipment-grid{grid-template-columns:1fr}.hm-toolbar,.hm-bulk-bar{align-items:stretch;flex-direction:column}.hm-bulk-actions{justify-content:stretch}.hm-bulk-field,.hm-bulk-actions>.hm-primary-btn,.hm-bulk-actions>.hm-ghost-btn{width:100%}}
    </style>

    <x-filament-actions::modals />
</x-filament-panels::page>
