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
    $visibleUnpaidCents = $visibleShipments->sum(fn ($s) => max(0, (int) ($s->final_charge_cents ?? 0) - (int) ($s->paid_amount_cents ?? 0)));
    $currentTitle = $selectedBatch?->reference ?? 'تجميع الرحلات';
    $currentRoute = $selectedBatch
        ? (($selectedBatch->route?->originWarehouse?->name ?? 'Dubai').' ← '.($selectedBatch->route?->destinationWarehouse?->name ?? 'الوجهة'))
        : 'عرض كل الشحنات النشطة · المرحلة الحالية محسوبة حسب أبطأ شحنة';
    $bulkLabelShipment = $visibleShipments->first(fn ($shipment) => in_array($shipment->id, $this->selectedShipmentIds, true));
    $bulkLabelRoute = $bulkLabelShipment?->batch?->route ?? $selectedBatch?->route;
    $journeyProjection = app(PackageJourneyProjection::class);

    $stepIcons = ['▣', '✈', '↗', '✈', '↗', '⌂', '✓'];
@endphp

<x-filament-panels::page>
    <div class="hm-app-layout" dir="rtl">

        <main>
            <!-- Page Header -->
            <section class="page-head">
                <div>
                    <h1>{{ $currentTitle }}</h1>
                    <p class="sub">{{ $currentRoute }}</p>
                </div>
                <div class="controls">
                    <div class="hm-search-wrap">
                        <input
                            class="hm-search"
                            type="search"
                            placeholder="بحث عن شحنة أو عميل..."
                            wire:model.live.debounce.300ms="shipmentSearch"
                        >
                    </div>
                    <details class="hm-filter-menu">
                        <summary class="hm-ghost-btn">الفلاتر ☷</summary>
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
            </section>

            <!-- Summary Stats Cards -->
            <section class="summary">
                <div>
                    <span>الشحنات النشطة</span>
                    <b>{{ $visibleShipments->count() }} شحنات</b>
                </div>
                <div>
                    <span>إجمالي الوزن</span>
                    <b>{{ $visibleWeight }} كغ</b>
                </div>
                <div>
                    <span>غير مدفوع</span>
                    <b>${{ number_format($visibleUnpaidCents / 100, 2) }}</b>
                </div>
            </section>

            <!-- Bulk Selection Toolbar -->
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

            <!-- Main Content Grid: Shipments + Active Trips Sidebar -->
            <section class="content-grid">
                <div class="shipments">
                    @if ($visibleShipments->isEmpty())
                        <div class="hm-empty">
                            <strong>لا توجد شحنات مطابقة</strong>
                            <span>جرّب تغيير البحث أو اختيار رحلة أخرى.</span>
                        </div>
                    @else
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
                                $stageLabel = app(PackageJourneyProjection::class)->labelForShipment($shipment->batch?->route, $shipment->status);
                                $unpaidCents = max(0, (int) ($shipment->final_charge_cents ?? 0) - (int) ($shipment->paid_amount_cents ?? 0));
                                $isSelected = in_array($shipment->id, $this->selectedShipmentIds, true);
                            @endphp

                            <article class="shipment {{ $isSelected ? 'is-selected' : '' }}">
                                <!-- Card Top -->
                                <div class="card-top">
                                    <div style="display: flex; gap: 10px; align-items: start;">
                                        <button
                                            type="button"
                                            class="hm-card-check"
                                            wire:click="toggleShipmentSelection({{ $shipment->id }})"
                                            aria-label="تحديد الشحنة {{ $shipment->reference }}"
                                            aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                        >
                                            <span class="hm-check-box {{ $isSelected ? 'is-checked' : '' }}"></span>
                                        </button>
                                        <div>
                                            <div class="id">{{ $shipment->reference }}</div>
                                            <div class="name">{{ $shipment->customer?->name ?? $shipment->recipient_name }}</div>
                                        </div>
                                    </div>
                                    <span class="status {{ $shipment->status === \App\Enums\ShipmentStatus::Collected ? 'status-done' : 'status-in-progress' }}">
                                        {{ $stageLabel }}
                                    </span>
                                </div>

                                <!-- Stepper Journey Track -->
                                <div class="tracking">
                                    <div class="track-label">
                                        <span>مسار الشحنة</span>
                                        <span>{{ $shipment->status === \App\Enums\ShipmentStatus::Collected ? 'مكتملة' : 'المرحلة الحالية' }}</span>
                                    </div>
                                    <ol class="steps">
                                        @foreach ($steps as $idx => $step)
                                            @php
                                                $isDone = ($idx < $activeStep || ($shipment->status === \App\Enums\ShipmentStatus::Collected));
                                                $isCurrent = ($idx === $activeStep && $shipment->status !== \App\Enums\ShipmentStatus::Collected);
                                                $icon = $stepIcons[$idx] ?? '•';
                                            @endphp
                                            <li class="{{ $isDone ? 'done' : ($isCurrent ? 'current' : '') }}">
                                                <i>{{ $icon }}</i>
                                                {{ $step['label'] }}
                                            </li>
                                        @endforeach
                                    </ol>
                                </div>

                                <!-- Meta Grid (Route, Packages, Weight) -->
                                <div class="meta">
                                    <div>
                                        <small>الرحلة</small>
                                        <strong>{{ $shipment->batch?->reference ?? 'غير مسندة' }}</strong>
                                    </div>
                                    <div>
                                        <small>الطرود</small>
                                        <strong>{{ $shipment->packages_count }}</strong>
                                    </div>
                                    <div>
                                        <small>الوزن</small>
                                        <strong>{{ rtrim(rtrim(number_format((float) $shipment->total_weight_kg, 2), '0'), '.') }} كغ</strong>
                                    </div>
                                </div>

                                <!-- WhatsApp Notifications Status -->
                                <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; font-size: 11px;">
                                    @if (is_null($shipment->intake_notified_at))
                                        <button type="button" wire:click="mountTableAction('whatsappIntake', '{{ $shipment->id }}')" class="status-btn-action">إرسال إشعار استلام 💬</button>
                                    @else
                                        <span class="status-badge-sent">إشعار الاستلام: مرسل ✓</span>
                                    @endif

                                    @if (in_array($shipment->status, [App\Enums\ShipmentStatus::PartialAtDestination, App\Enums\ShipmentStatus::ReadyForCollection, App\Enums\ShipmentStatus::PartiallyCollected, App\Enums\ShipmentStatus::Collected]) && is_null($shipment->arrival_notified_at))
                                        <button type="button" wire:click="mountTableAction('whatsappArrival', '{{ $shipment->id }}')" class="status-btn-action">إرسال إشعار الوصول 💬</button>
                                    @elseif (!is_null($shipment->arrival_notified_at))
                                        <span class="status-badge-sent">إشعار الوصول: مرسل ✓</span>
                                    @endif
                                </div>

                                <!-- Card Bottom Footer & Actions -->
                                <div class="card-bottom">
                                    <div>
                                        <div class="amount">${{ number_format((float) ($shipment->final_charge_cents / 100), 2) }}</div>
                                        @if ($unpaidCents > 0)
                                            <div class="unpaid">غير مدفوع (${{ number_format($unpaidCents / 100, 2) }})</div>
                                        @else
                                            <div class="paid">مدفوع بالكامل ✓</div>
                                        @endif
                                    </div>

                                    <div class="action-wrap" x-data="{ cardOpen: false }">
                                        <button type="button" class="action-toggle" x-on:click="cardOpen = !cardOpen" aria-label="إجراءات الشحنة" :aria-expanded="cardOpen">•••</button>
                                        <div class="action-menu" x-show="cardOpen" x-transition x-on:click.away="cardOpen = false" x-cloak>
                                            <a href="{{ ShipmentResource::getUrl('view', ['record' => $shipment]) }}" x-on:click="cardOpen = false">👁️ عرض التفاصيل</a>
                                            <a href="{{ ShipmentResource::getUrl('edit', ['record' => $shipment]) }}" x-on:click="cardOpen = false">✏️ تعديل الشحنة</a>
                                            <button type="button" class="hm-action-link" x-on:click="cardOpen = false" wire:click="mountTableAction('manageJourney', '{{ $shipment->id }}')">📦 إدارة الطرود</button>
                                            <button type="button" class="hm-action-link" x-on:click="cardOpen = false" wire:click="mountTableAction('copyTrackingLink', '{{ $shipment->id }}')">🔗 نسخ رابط التتبع</button>
                                            <a href="{{ route('labels.shipment', $shipment) }}" target="_blank" x-on:click="cardOpen = false">🖨️ طباعة الملصقات</a>
                                            @if (in_array($shipment->status, [\App\Enums\ShipmentStatus::ReadyForCollection, \App\Enums\ShipmentStatus::PartialAtDestination, \App\Enums\ShipmentStatus::PartiallyCollected, \App\Enums\ShipmentStatus::InTransit, \App\Enums\ShipmentStatus::AtTransit]))
                                                <button type="button" class="hm-action-link" x-on:click="cardOpen = false" wire:click="mountTableAction('partialCollect', '{{ $shipment->id }}')">
                                                    {{ $shipment->status === \App\Enums\ShipmentStatus::ReadyForCollection ? '✅ تسليم الشحنة للعميل' : '🚚 تسليم الطرود الواصلة' }}
                                                </button>
                                            @endif
                                            <button type="button" class="hm-action-link" x-on:click="cardOpen = false" wire:click="mountTableAction('whatsappIntake', '{{ $shipment->id }}')">💬 واتساب: رابط التتبع</button>
                                            @if ($shipment->status === \App\Enums\ShipmentStatus::ReadyForCollection)
                                                <button type="button" class="hm-action-link" x-on:click="cardOpen = false" wire:click="mountTableAction('whatsappArrival', '{{ $shipment->id }}')">💬 واتساب: إشعار الوصول</button>
                                            @endif
                                            @if (auth()->user()?->hasCapability(\App\Enums\Capability::DeleteRecords))
                                                <hr style="border: none; border-top: 1px solid var(--line); margin: 4px 0;">
                                                <button type="button" class="hm-action-link" style="color: var(--danger);" x-on:click="cardOpen = false" wire:click="mountTableAction('delete', '{{ $shipment->id }}')">🗑️ حذف الشحنة</button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    @endif
                </div>

                <!-- Active Trips Sidebar Panel -->
                <aside class="trips-panel">
                    @include('filament.tables.components.journey-sidebar')
                </aside>
            </section>
        </main>
    </div>

    <!-- Layout Styles -->
    <style>
        /* Scope layout override only to list-shipments page */
        .fi-resource-list-records-page .fi-sidebar,
        .fi-resource-list-records-page .fi-topbar,
        .fi-resource-list-records-page .fi-header {
            display: none !important;
        }

        .fi-resource-list-records-page .fi-main {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        :root {
            --bg: #ffffff;
            --surface: #f8fafc;
            --surface-2: #f1f5f9;
            --text: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --accent: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;

            --hm-border: #e2e8f0;
            --hm-border-hover: #cbd5e1;
            --hm-bg-page: #ffffff;
            --hm-bg-hover: #f1f5f9;
            --hm-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --hm-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --hm-blue-bg: rgba(59, 130, 246, 0.08);
            --hm-blue-border: rgba(59, 130, 246, 0.25);
            --hm-text-main: #0f172a;
            --hm-text-sub: #334155;
            --hm-text-muted: #64748b;
            --hm-badge-red-border: rgba(239, 68, 68, 0.25);
            --hm-badge-red-bg: rgba(239, 68, 68, 0.1);
            --hm-badge-red-text: #dc2626;
            --hm-badge-orange-border: rgba(245, 158, 11, 0.25);
            --hm-badge-orange-bg: rgba(245, 158, 11, 0.1);
            --hm-badge-orange-text: #d97706;
            --hm-step-line: #e2e8f0;
        }

        .dark {
            --bg: #090909;
            --surface: #121212;
            --surface-2: #1a1a1a;
            --text: #f4f4f5;
            --muted: #a1a1aa;
            --line: #292929;
            --accent: #67d5a8;
            --warning: #f3c969;
            --danger: #f28b91;

            --hm-border: #292929;
            --hm-border-hover: #3f3f46;
            --hm-bg-page: #121212;
            --hm-bg-hover: #1a1a1a;
            --hm-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
            --hm-shadow-md: 0 8px 20px rgba(0, 0, 0, 0.4);
            --hm-blue-bg: rgba(59, 130, 246, 0.15);
            --hm-blue-border: rgba(59, 130, 246, 0.3);
            --hm-text-main: #f4f4f5;
            --hm-text-sub: #e4e4e7;
            --hm-text-muted: #a1a1aa;
            --hm-badge-red-border: rgba(239, 68, 68, 0.3);
            --hm-badge-red-bg: rgba(239, 68, 68, 0.15);
            --hm-badge-red-text: #f87171;
            --hm-badge-orange-border: rgba(245, 158, 11, 0.3);
            --hm-badge-orange-bg: rgba(245, 158, 11, 0.15);
            --hm-badge-orange-text: #fbbf24;
            --hm-step-line: #27272a;
        }

        .hm-app-layout {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: inherit;
            min-height: 100vh;
        }

        .topbar {
            min-height: 64px;
            padding: 0 clamp(18px, 4vw, 56px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px solid var(--line);
            background: var(--surface);
            position: relative;
        }

        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .mobile-nav-toggle {
            display: none;
            background: transparent;
            border: 1px solid var(--line);
            color: var(--text);
            font-size: 18px;
            padding: 4px 10px;
            border-radius: 8px;
            cursor: pointer;
        }

        .brand {
            font-weight: 900;
            letter-spacing: 0.5px;
            font-size: 20px;
            color: #f59e0b;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            flex: 1;
            justify-content: space-around;
            min-width: max-content;
        }

        .nav-links a {
            padding: 20px 14px 17px;
            color: var(--muted);
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-links a:hover {
            color: var(--text);
        }

        .nav-links a.active {
            color: var(--text);
            border-bottom-color: var(--accent);
            font-weight: 700;
        }

        .settings {
            position: relative;
            display: flex;
            align-items: center;
            gap: 9px;
            white-space: nowrap;
        }

        .settings button {
            border: 1px solid var(--line);
            background: var(--surface-2);
            color: var(--text);
            font-size: 14px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .settings button:hover {
            background: var(--surface);
        }

        .user-chip {
            font-size: 12px;
            font-weight: 600;
            color: var(--muted);
        }

        .settings-menu {
            display: none;
            position: absolute;
            top: 48px;
            left: 0;
            width: 220px;
            padding: 8px;
            background: var(--surface-2);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(0,0,0,.4);
            z-index: 40;
        }

        .settings-menu.open {
            display: grid;
            gap: 2px;
        }

        .user-info-header {
            padding: 8px 12px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .user-info-header strong {
            font-size: 14px;
            color: var(--text);
        }

        .user-info-header small {
            font-size: 11px;
            color: var(--muted);
        }

        .menu-divider {
            border: none;
            border-top: 1px solid var(--line);
            margin: 4px 0;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--danger) !important;
            padding: 10px 12px;
            text-align: right;
            width: 100%;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: background 0.15s;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        .settings-menu a {
            padding: 10px 12px;
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.15s;
        }

        .settings-menu a:hover {
            background: var(--surface);
        }

        main {
            width: min(1540px, calc(100% - 48px));
            margin: 34px auto 56px;
        }

        .page-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            margin-bottom: 24px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
        }

        .sub {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .controls {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .hm-search-wrap {
            position: relative;
            min-width: 260px;
        }

        .hm-search {
            width: 100%;
            height: 42px;
            padding: 0 16px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--text);
            font-size: 13px;
            outline: none;
            transition: border 0.2s;
        }

        .hm-search:focus {
            border-color: #3b82f6;
        }

        .hm-ghost-btn {
            height: 42px;
            padding: 0 18px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: var(--surface);
            color: var(--muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .hm-ghost-btn:hover {
            background: var(--surface-2);
            color: var(--text);
        }

        .hm-filter-menu {
            position: relative;
        }

        .hm-filter-menu summary {
            list-style: none;
        }

        .hm-filter-menu summary::-webkit-details-marker {
            display: none;
        }

        .hm-filter-panel {
            position: absolute;
            z-index: 30;
            top: calc(100% + 8px);
            left: 0;
            min-width: 220px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--surface-2);
            box-shadow: 0 12px 28px rgba(0,0,0,.4);
        }

        .hm-filter-panel label {
            display: grid;
            gap: 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .hm-filter-panel select {
            height: 40px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            padding: 0 10px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 24px;
        }

        .summary div {
            padding: 18px 20px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .summary span {
            color: var(--muted);
            font-size: 13px;
            display: block;
        }

        .summary b {
            display: block;
            font-size: 22px;
            margin-top: 6px;
            font-weight: 800;
            color: var(--text);
        }

        .hm-bulk-bar {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
            padding: 14px 20px;
            border: 1px solid rgba(59,130,246,.3);
            border-radius: 14px;
            background: rgba(59,130,246,.08);
            flex-wrap: wrap;
        }

        .hm-bulk-copy {
            display: flex;
            gap: 12px;
            align-items: center;
            font-size: 13px;
        }

        .hm-bulk-count {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3b82f6;
            color: #fff;
            font-weight: 800;
        }

        .hm-bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .hm-bulk-field select, .hm-bulk-field input {
            height: 38px;
            padding: 0 12px;
            border: 1px solid var(--line);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
        }

        .hm-primary-btn {
            height: 38px;
            padding: 0 20px;
            border: none;
            border-radius: 999px;
            background: #f59e0b;
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }

        .hm-primary-btn:hover {
            background: #d97706;
        }

        .hm-select-all {
            display: inline-flex;
            gap: 10px;
            align-items: center;
            color: var(--muted);
            font-size: 13px;
            margin: 0 4px 18px;
            background: transparent;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }

        .hm-select-all:hover {
            color: var(--text);
        }

        .hm-check-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border: 1px solid var(--line);
            border-radius: 5px;
            background: var(--surface);
            transition: 0.2s;
        }

        .hm-check-box.is-checked {
            border-color: #f59e0b;
            background: #f59e0b;
        }

        .hm-check-box.is-checked::after {
            content: "";
            width: 4px;
            height: 8px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg) translateY(-1px);
        }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            align-items: start;
        }

        .shipments {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .shipment {
            padding: 20px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            transition: all 0.2s;
        }

        .shipment:hover {
            border-color: rgba(255,255,255,0.15);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }

        .shipment.is-selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--line);
        }

        .id {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: 500;
        }

        .name {
            font-size: 19px;
            font-weight: 800;
            color: var(--text);
            line-height: 1.3;
        }

        .status {
            white-space: nowrap;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .status-done {
            background: color-mix(in srgb, var(--accent) 18%, transparent);
            color: var(--accent);
        }

        .status-in-progress {
            background: color-mix(in srgb, var(--warning) 18%, transparent);
            color: var(--warning);
        }

        .tracking {
            padding: 16px 0;
            border-bottom: 1px solid var(--line);
        }

        .track-label {
            display: flex;
            justify-content: space-between;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 14px;
            font-weight: 500;
        }

        .steps {
            list-style: none;
            display: flex;
            justify-content: space-between;
            gap: 4px;
            margin: 0;
            padding: 0;
        }

        .steps li {
            flex: 1;
            min-width: 0;
            color: var(--muted);
            text-align: center;
            font-size: 11px;
            line-height: 1.3;
        }

        .steps i {
            width: 30px;
            height: 30px;
            margin: 0 auto 6px;
            display: grid;
            place-items: center;
            border: 1px solid var(--line);
            border-radius: 50%;
            background: var(--surface-2);
            font-style: normal;
            font-size: 13px;
            transition: all 0.2s;
        }

        .steps li.done i, .steps li.current i {
            border-color: var(--accent);
        }

        .steps li.done {
            color: var(--text);
        }

        .steps li.current {
            color: var(--accent);
            font-weight: 700;
        }

        .steps li.current i {
            background: color-mix(in srgb, var(--accent) 20%, var(--surface-2));
            box-shadow: 0 0 12px color-mix(in srgb, var(--accent) 40%, transparent);
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding-top: 16px;
        }

        .meta small {
            display: block;
            color: var(--muted);
            margin-bottom: 4px;
            font-size: 11px;
        }

        .meta strong {
            font-weight: 600;
            font-size: 13px;
            color: var(--text);
        }

        .status-btn-action {
            background: color-mix(in srgb, #3b82f6 15%, transparent);
            border: 1px solid rgba(59,130,246,0.3);
            color: #60a5fa;
            border-radius: 6px;
            padding: 4px 10px;
            cursor: pointer;
            font-family: inherit;
        }

        .status-badge-sent {
            background: color-mix(in srgb, var(--accent) 15%, transparent);
            border: 1px solid color-mix(in srgb, var(--accent) 30%, transparent);
            color: var(--accent);
            border-radius: 6px;
            padding: 4px 10px;
            font-weight: 600;
        }

        .card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 18px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
        }

        .amount {
            font-weight: 800;
            font-size: 16px;
            color: var(--text);
        }

        .unpaid {
            color: var(--danger);
            font-size: 12px;
            font-weight: 600;
        }

        .paid {
            color: var(--accent);
            font-size: 12px;
            font-weight: 600;
        }

        .action-wrap {
            position: relative;
        }

        .action-toggle {
            border: 1px solid var(--line);
            background: var(--surface-2);
            color: var(--muted);
            font-size: 18px;
            letter-spacing: 2px;
            cursor: pointer;
            padding: 4px 14px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .action-toggle:hover {
            color: var(--text);
            background: var(--surface);
        }

        .action-menu {
            position: absolute;
            left: 0;
            bottom: 42px;
            width: 220px;
            padding: 8px;
            background: var(--surface-2);
            border: 1px solid var(--line);
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(0,0,0,.6);
            z-index: 50;
            display: grid;
            gap: 2px;
        }

        .action-menu a, .action-menu button {
            padding: 9px 12px;
            color: var(--text);
            text-decoration: none;
            border-radius: 8px;
            font-size: 13px;
            text-align: right;
            background: none;
            border: none;
            cursor: pointer;
            width: 100%;
            font-family: inherit;
            transition: background 0.15s;
        }

        .action-menu a:hover, .action-menu button:hover {
            background: var(--surface);
        }

        .trips-panel {
            padding: 20px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .hm-empty {
            grid-column: 1 / -1;
            display: grid;
            place-items: center;
            min-height: 280px;
            padding: 40px;
            border: 2px dashed var(--line);
            border-radius: 16px;
            color: var(--muted);
            text-align: center;
        }

        .hm-empty strong {
            color: var(--text);
            font-size: 18px;
            margin-bottom: 8px;
        }

        @media (max-width: 1100px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            .shipments {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .mobile-nav-toggle {
                display: block;
            }
            .nav-links {
                display: none;
                position: absolute;
                top: 64px;
                right: 0;
                left: 0;
                background: var(--surface);
                border-bottom: 1px solid var(--line);
                flex-direction: column;
                padding: 12px 20px;
                gap: 8px;
                z-index: 50;
            }
            .nav-links.mobile-open {
                display: flex;
            }
            .nav-links a {
                padding: 10px 14px;
                width: 100%;
                border-bottom: none;
                border-radius: 8px;
            }
            .nav-links a.active {
                background: var(--surface-2);
            }
        }

        @media (max-width: 600px) {
            main {
                width: min(100% - 24px, 1540px);
                margin-top: 20px;
            }
            .page-head {
                align-items: flex-start;
                flex-direction: column;
            }
            .summary {
                grid-template-columns: 1fr;
            }
            .meta {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>

    <x-filament-actions::modals />
</x-filament-panels::page>
