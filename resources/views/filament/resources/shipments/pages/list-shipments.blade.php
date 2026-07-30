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
                                            @if (in_array($shipment->status, [\App\Enums\ShipmentStatus::ReadyForCollection, \App\Enums\ShipmentStatus::PartialAtDestination, \App\Enums\ShipmentStatus::PartiallyCollected, \App\Enums\ShipmentStatus::InTransit, \App\Enums\ShipmentStatus::AtTransit]))
                                                <button type="button" wire:click="mountTableAction('partialCollect', '{{ $shipment->id }}')">{{ $shipment->status === \App\Enums\ShipmentStatus::ReadyForCollection ? 'تسليم الشحنة للعميل' : 'تسليم الطرود الواصلة' }}</button>
                                            @endif
                                        </div>
                                    </details>
                                    <div class="hm-shipment-id">
                                        <div style="display: flex; align-items: center; gap: 8px;">
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
                                        </div>
                                        <div style="direction: rtl; text-align: left;">
                                            <span class="hm-customer-name">{{ $shipment->customer?->name }}</span>
                                        </div>
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

                                            @if (in_array($shipment->status, [App\Enums\ShipmentStatus::PartialAtDestination, App\Enums\ShipmentStatus::ReadyForCollection, App\Enums\ShipmentStatus::ReadyForCollection, App\Enums\ShipmentStatus::PartiallyCollected, App\Enums\ShipmentStatus::Collected]) && is_null($shipment->arrival_notified_at))
                                                <button type="button" wire:click="mountTableAction('whatsappArrival', '{{ $shipment->id }}')" class="hm-money-badge" style="width: max-content; font-size: 10px; cursor: pointer;">رسالة الوصول: إرسال الآن 💬</button>
                                            @elseif (!is_null($shipment->arrival_notified_at))
                                                <strong class="hm-success-badge" style="width: max-content; font-size: 10px;">رسالة الوصول: مرسلة ✓</strong>
                                            @endif
                                        </div>
                                    </div>
                                    <div><span>المستلم</span><strong style="font-size: 15px; color: var(--hm-text-main);">{{ $shipment->recipient_name }}</strong></div>
                                    <div><span>الطرود</span><strong>▣ {{ $shipment->packages_count }}</strong></div>
                                    <div><span>الوزن</span><strong>⚖ {{ rtrim(rtrim(number_format((float) $shipment->total_weight_kg, 2), '0'), '.') }} كغ</strong></div>
                                </div>

                                <div class="hm-card-footer">
                                    <span class="hm-money-badge">${{ number_format($unpaidCents / 100, 2) }} غير مدفوع</span>
                                    <div class="hm-card-actions">
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
        :root {
            --hm-bg-page: #ffffff;
            --hm-bg-sub: #f8fafc;
            --hm-bg-hover: #f1f5f9;
            --hm-border: #e2e8f0;
            --hm-border-hover: #cbd5e1;
            --hm-text-main: #0f172a;
            --hm-text-sub: #475569;
            --hm-text-muted: #64748b;
            --hm-shadow-sm: 0 2px 8px rgba(0,0,0,0.03);
            --hm-shadow-md: 0 10px 30px rgba(0,0,0,.05);
            --hm-shadow-lg: 0 10px 40px rgba(0,0,0,.1);
            --hm-blue-bg: rgba(59,130,246,.05);
            --hm-blue-border: rgba(59,130,246,.2);
            --hm-step-line: #e2e8f0;
            --hm-step-border: #cbd5e1;
            --hm-badge-red-bg: rgba(239,68,68,.1);
            --hm-badge-red-border: rgba(239,68,68,.2);
            --hm-badge-red-text: #dc2626;
            --hm-badge-green-bg: rgba(16,185,129,.1);
            --hm-badge-green-border: rgba(16,185,129,.3);
            --hm-badge-green-text: #059669;
            --hm-badge-orange-bg: rgba(245,158,11,.1);
            --hm-badge-orange-border: rgba(245,158,11,.2);
            --hm-badge-orange-text: #d97706;
        }
        
        .dark {
            --hm-bg-page: #09090b;
            --hm-bg-sub: #18181b;
            --hm-bg-hover: #27272a;
            --hm-border: #27272a;
            --hm-border-hover: #3f3f46;
            --hm-text-main: #f4f4f5;
            --hm-text-sub: #d4d4d8;
            --hm-text-muted: #a1a1aa;
            --hm-shadow-sm: 0 4px 15px rgba(0,0,0,0.2);
            --hm-shadow-md: 0 10px 30px rgba(0,0,0,.4);
            --hm-shadow-lg: 0 10px 40px rgba(0,0,0,.5);
            --hm-blue-bg: rgba(59,130,246,.15);
            --hm-blue-border: rgba(59,130,246,.3);
            --hm-step-line: #3f3f46;
            --hm-step-border: #52525b;
            --hm-badge-red-bg: rgba(239,68,68,.15);
            --hm-badge-red-border: rgba(239,68,68,.3);
            --hm-badge-red-text: #fca5a5;
            --hm-badge-green-bg: rgba(16,185,129,.15);
            --hm-badge-green-border: rgba(16,185,129,.3);
            --hm-badge-green-text: #6ee7b7;
            --hm-badge-orange-bg: rgba(245,158,11,.15);
            --hm-badge-orange-border: rgba(245,158,11,.3);
            --hm-badge-orange-text: #fcd34d;
        }

        .hm-frame{overflow:hidden;border:1px solid var(--hm-border);border-radius:24px;background:var(--hm-bg-page);box-shadow:var(--hm-shadow-md);color:var(--hm-text-main)}
        .hm-select-all,.hm-card-check{font:inherit;text-align:inherit;cursor:pointer}.hm-card-check{display:inline-flex;padding:0;border:0;background:transparent;color:inherit}.hm-select-all input[type=checkbox],.hm-card-check input[type=checkbox]{display:none}
        .hm-check-box{display:inline-flex;align-items:center;justify-content:center;width:18px;height:18px;border:1px solid var(--hm-border-hover);border-radius:5px;background:var(--hm-bg-sub);transition: 0.2s;}
        .hm-check-box.is-checked{border-color:#f59e0b;background:#f59e0b}
        .hm-check-box.is-checked::after{content:"";width:5px;height:10px;border:solid #fff;border-width:0 2px 2px 0;transform:rotate(45deg) translateY(-1px)}
        .hm-bulk-field{display:flex;flex:1 1 210px;min-width:0;gap:6px;align-items:center;color:var(--hm-text-muted);font-size:12px;font-weight:600}.hm-bulk-reason{flex-basis:240px}
        .hm-bulk-field select,.hm-bulk-field input{width:100%;height:40px;min-width:0;max-width:100%;box-sizing:border-box;padding:0 12px;border:1px solid var(--hm-border);border-radius:10px;background:var(--hm-bg-page);color:var(--hm-text-main);outline:0;transition:0.2s}
        .hm-bulk-field select:focus,.hm-bulk-field input:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
        .hm-screen-bar{display:flex;justify-content:space-between;gap:20px;align-items:center;min-height:68px;padding:12px 24px;border-bottom:1px solid var(--hm-border);background:var(--hm-bg-sub)}
        .hm-brand{display:flex;gap:10px;align-items:center;font-size:14px;font-weight:800;letter-spacing:0.5px;color:var(--hm-text-main);}
        .hm-brand span{color:#f59e0b;font-size:20px;font-weight:900}
        .hm-context{display:flex;gap:18px;align-items:center;color:var(--hm-text-muted);font-size:13px}
        .hm-online{display:flex;gap:8px;align-items:center;font-weight:500;padding:4px 12px;background:rgba(16,185,129,0.1);border-radius:99px;color:#10b981}
        .hm-online i{width:8px;height:8px;border-radius:99px;background:#10b981;box-shadow:0 0 8px rgba(16,185,129,0.4)}
        .hm-split{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,.37fr);min-height:625px}
        .hm-workspace{min-width:0;padding:24px;background:var(--hm-bg-page);}
        .hm-sidebar{border-right:1px solid var(--hm-border);background:var(--hm-bg-sub);}
        .hm-section-title{display:flex;justify-content:space-between;gap:18px;align-items:start;margin-bottom:20px}
        .hm-section-title h2{margin:0;font-size:22px;font-weight:700;line-height:1.35;color:var(--hm-text-main)}
        .hm-section-title p{margin:6px 0 0;color:var(--hm-text-muted);font-size:13px}
        .hm-count-badge,.hm-neutral-badge{display:inline-flex;gap:6px;align-items:center;min-width:max-content;padding:6px 14px;border:1px solid var(--hm-border);border-radius:999px;background:var(--hm-bg-hover);color:var(--hm-text-sub);font-size:12px;font-weight:600;}
        .hm-toolbar{display:flex;gap:12px;align-items:center;margin-bottom:24px;padding:14px;border:1px solid var(--hm-border);border-radius:16px;background:var(--hm-bg-sub);}
        .hm-search-wrap{position:relative;flex:1}
        .hm-search{width:100%;height:44px;padding:0 42px 0 16px;border:1px solid var(--hm-border);outline:0;border-radius:12px;background:var(--hm-bg-page);color:var(--hm-text-main);font-size:14px;transition:0.2s}
        .hm-search:focus{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.15)}
        .hm-ghost-btn,.hm-mini-btn,.hm-icon-btn{border:1px solid var(--hm-border);border-radius:12px;background:var(--hm-bg-page);color:var(--hm-text-sub);cursor:pointer;transition:.2s;text-decoration:none;font-weight:500}
        .hm-ghost-btn:hover,.hm-mini-btn:hover,.hm-icon-btn:hover{background:var(--hm-bg-hover);color:var(--hm-text-main)}
        .hm-ghost-btn{height:44px;padding:0 18px;display:inline-flex;align-items:center}
        .hm-primary-btn{height:44px;padding:0 24px;border:none;border-radius:999px;background:linear-gradient(135deg, #f59e0b, #d97706);color:#fff;font-weight:700;display:inline-flex;align-items:center;cursor:pointer;box-shadow:0 4px 12px rgba(245, 158, 11, 0.2);transition:0.2s}
        .hm-primary-btn:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(245, 158, 11, 0.3)}
        .hm-filter-menu,.hm-actions-menu{position:relative}
        .hm-filter-menu summary,.hm-actions-menu summary{list-style:none}
        .hm-filter-menu summary::-webkit-details-marker,.hm-actions-menu summary::-webkit-details-marker{display:none}
        .hm-filter-panel,.hm-actions-panel{position:absolute;z-index:30;top:calc(100% + 10px);left:0;min-width:200px;padding:12px;border:1px solid var(--hm-border);border-radius:16px;background:var(--hm-bg-page);box-shadow:var(--hm-shadow-lg)}.hm-actions-panel{right:0;left:auto;display:grid;gap:4px}
        .hm-actions-panel a, .hm-actions-panel button{padding:10px 14px;border-radius:10px;color:var(--hm-text-sub);text-decoration:none;font-size:13px; background:none; border:none; text-align:right; font-family:inherit; cursor:pointer; width:100%;transition:0.15s;font-weight:500}
        .hm-actions-panel a:hover, .hm-actions-panel button:hover{background:var(--hm-bg-hover);color:var(--hm-text-main)}
        .hm-filter-panel label{display:grid;gap:8px;color:var(--hm-text-muted);font-size:12px;font-weight:500}
        .hm-filter-panel select{height:40px;border:1px solid var(--hm-border);border-radius:10px;background:var(--hm-bg-page);color:var(--hm-text-main)}
        .hm-select-all{display:flex;gap:10px;align-items:center;color:var(--hm-text-muted);font-size:13px;margin:4px 4px 16px;width:max-content;cursor:pointer;font-weight:500;transition:0.2s}
        .hm-select-all:hover{color:var(--hm-text-main)}
        .hm-bulk-bar{display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:20px;padding:14px 20px;border:1px solid var(--hm-blue-border);border-radius:16px;background:var(--hm-blue-bg);}
        .hm-bulk-copy{display:flex;gap:12px;align-items:center;font-size:13px;font-weight:500;color:var(--hm-text-main);}
        .hm-bulk-copy small{color:var(--hm-text-muted);font-weight:400}
        .hm-bulk-count{display:grid;place-items:center;width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg, #3b82f6, #2563eb);color:white;font-weight:800;box-shadow:0 4px 12px rgba(59,130,246,0.3)}
        .hm-bulk-actions{display:flex;gap:10px;align-items:center}
        .hm-shipment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}
        .hm-shipment-card{position:relative;padding:24px;border:1px solid var(--hm-border);border-radius:24px;background:var(--hm-bg-page);transition:all .25s cubic-bezier(0.4, 0, 0.2, 1);box-shadow:var(--hm-shadow-sm)}
        .hm-shipment-card:hover{border-color:var(--hm-border-hover);box-shadow:var(--hm-shadow-md);transform:translateY(-2px)}
        .hm-shipment-card.is-selected{border-color:#3b82f6;background:var(--hm-blue-bg);box-shadow:0 0 0 1px #3b82f6, var(--hm-shadow-md)}
        .hm-shipment-top{display:flex;justify-content:space-between;gap:16px;align-items:start}
        .hm-shipment-id{display:grid;gap:6px;text-align:left;direction:ltr}
        .hm-shipment-id strong{font-weight:800;font-size:20px;color:var(--hm-text-main);letter-spacing:0.5px}
        .hm-shipment-id span{color:var(--hm-text-muted);font-size:13px;font-weight:500}
        .hm-customer-name{font-size:18px;color:var(--hm-text-main);font-weight:800;}
        .hm-stepper{margin:24px 0;padding:20px;border:1px solid var(--hm-border);border-radius:16px;background:var(--hm-bg-sub)}
        .hm-stepper-head{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:20px;color:var(--hm-text-sub);font-size:13px;font-weight:600}
        .hm-stage-badge{display:inline-flex;padding:6px 14px;border:1px solid var(--hm-badge-orange-border);border-radius:999px;background:var(--hm-badge-orange-bg);color:var(--hm-badge-orange-text);font-size:12px;font-weight:700}
        .hm-steps{position:relative;display:grid;grid-template-columns:repeat(7,1fr);direction:rtl}
        .hm-steps::before{content:"";position:absolute;top:12px;left:7%;right:7%;height:2px;background:var(--hm-step-line);z-index:0}
        .hm-step{position:relative;text-align:center;color:var(--hm-text-muted);font-size:11px;line-height:1.4;font-weight:500}
        .hm-step span{position:relative;z-index:1;display:grid;place-items:center;width:26px;height:26px;margin:0 auto 10px;border:2px solid var(--hm-step-border);border-radius:50%;background:var(--hm-bg-page);font-size:11px;font-weight:700;transition:0.3s;color:var(--hm-text-sub);}
        .hm-step.is-current{color:#3b82f6}
        .hm-step.is-current span{border-color:#3b82f6;background:#3b82f6;color:white;box-shadow:0 0 14px rgba(59,130,246,.4)}
        .hm-detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 24px;margin-top:20px}
        .hm-detail-grid span{display:block;color:var(--hm-text-muted);font-size:12px;font-weight:500;margin-bottom:6px}
        .hm-detail-grid strong{display:block;overflow:hidden;font-size:14px;font-weight:700;color:var(--hm-text-main);text-overflow:ellipsis;white-space:nowrap}
        .hm-card-footer{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-top:24px;padding-top:20px;border-top:1px solid var(--hm-border)}
        .hm-money-badge{display:inline-flex;padding:6px 14px;border:1px solid var(--hm-badge-red-border);border-radius:999px;background:var(--hm-badge-red-bg);color:var(--hm-badge-red-text);font-size:12px;font-weight:700}
        .hm-success-badge{display:inline-flex;padding:6px 14px;border:1px solid var(--hm-badge-green-border);border-radius:999px;background:var(--hm-badge-green-bg);color:var(--hm-badge-green-text);font-size:12px;font-weight:700}
        .hm-card-actions{display:flex;gap:10px;align-items:center}
        .hm-mini-btn{min-height:38px;padding:8px 16px;font-size:13px;border-radius:12px}
        .hm-icon-btn{display:grid;place-items:center;width:40px;height:40px;border-radius:50%}
        .hm-empty{display:grid;place-items:center;min-height:300px;padding:40px;border:2px dashed var(--hm-border-hover);border-radius:24px;color:var(--hm-text-muted);text-align:center;background:var(--hm-bg-sub)}
        .hm-empty strong{color:var(--hm-text-main);font-size:18px;margin-bottom:10px}
        .hm-empty span{display:block;font-size:14px}
        .hm-bulk-bar{flex-wrap:wrap;overflow:hidden;box-sizing:border-box}.hm-bulk-copy{flex:0 1 auto;min-width:0}.hm-bulk-actions{flex:1 1 520px;min-width:0;flex-wrap:wrap;justify-content:flex-end}.hm-bulk-actions>.hm-primary-btn,.hm-bulk-actions>.hm-ghost-btn{flex:0 0 auto;white-space:nowrap}
        .hm-action-link{background:none;border:none;color:var(--hm-text-sub);cursor:pointer;text-align:right;width:100%;font:inherit;padding:10px 14px;border-radius:10px;font-size:13px;display:block;transition:0.15s;font-weight:500}
        .hm-action-link:hover{background:var(--hm-bg-hover);color:var(--hm-text-main)}
        @media(max-width:900px){.hm-split{display:flex;flex-direction:column-reverse}.hm-sidebar{border-right:0;border-bottom:1px solid var(--hm-border)}.hm-shipment-grid{grid-template-columns:1fr}.hm-toolbar,.hm-bulk-bar{align-items:stretch;flex-direction:column}.hm-bulk-actions{justify-content:stretch}.hm-bulk-field,.hm-bulk-actions>.hm-primary-btn,.hm-bulk-actions>.hm-ghost-btn{width:100%}}
    </style>

    <x-filament-actions::modals />
</x-filament-panels::page>
