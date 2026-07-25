@php
    use App\Models\Batch;
    use App\Models\Shipment;
    use App\Services\PackageJourneyProjection;

    // Fetch active batches with routes and shipments
    $batches = Batch::with(['route', 'shipments.packages'])->get();

    // Currently selected batch ID from table filter
    $selectedBatchId = $this->tableFilters['batch_id']['value'] ?? null;

    $allShipmentsCount = Shipment::count();
    $allWeightSum = rtrim(rtrim(number_format((float) Shipment::sum('total_weight_kg'), 2), '0'), '.');

    $shortTitles = [
        'مستودع المبدأ',
        'مطار المبدأ',
        'مغادرة المبدأ',
        'مطار الوصول',
        'مغادرة الوصول',
        'مكتب التسليم',
        'استلام العميل'
    ];
@endphp

<style>
    .bsb-container {
        direction: rtl;
        display: flex;
        gap: 0.875rem;
        overflow-x: auto;
        padding: 0.25rem 0.125rem 0.5rem;
        margin-bottom: 0.25rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
    }

    .bsb-card {
        min-width: 20rem;
        flex: 0 0 auto;
        background: #18181b;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 0.875rem;
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        user-select: none;
    }

    .bsb-card:hover {
        border-color: rgba(59, 130, 246, 0.4);
        transform: translateY(-2px);
    }

    .bsb-card-active {
        border-color: #3b82f6 !important;
        background: rgba(30, 58, 138, 0.25) !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3), 0 6px 16px rgba(0, 0, 0, 0.3) !important;
    }

    .bsb-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.625rem;
    }

    .bsb-title-group {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .bsb-icon {
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 0.5rem;
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .bsb-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #f4f4f5;
        line-height: 1.2;
    }

    .bsb-sub {
        font-size: 0.7rem;
        color: #a1a1aa;
        margin-top: 0.1rem;
    }

    .bsb-metrics-group {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .bsb-pill {
        font-size: 0.675rem;
        font-weight: 600;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #d4d4d8;
    }

    .bsb-status-badge {
        font-size: 0.675rem;
        font-weight: 700;
        padding: 0.15rem 0.55rem;
        border-radius: 9999px;
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.4);
    }

    /* Stepper track inside batch selector card */
    .bsb-stepper-track {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0.625rem 0.25rem 0.25rem;
        padding: 0;
    }

    .bsb-track-line-bg {
        position: absolute;
        top: 50%;
        left: 0.5rem;
        right: 0.5rem;
        height: 2px;
        background: #27272a;
        transform: translateY(-50%);
        z-index: 1;
    }

    .bsb-track-line-fill {
        position: absolute;
        top: 50%;
        right: 0.5rem;
        height: 2px;
        background: linear-gradient(to left, #10b981, #3b82f6);
        transform: translateY(-50%);
        z-index: 1;
        transition: width 0.3s ease;
    }

    .bsb-node {
        position: relative;
        z-index: 2;
        width: 1.15rem;
        height: 1.15rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.6rem;
        font-weight: 700;
        background: #18181b;
        border: 2px solid #3f3f46;
        color: #71717a;
    }

    .bsb-node-complete {
        background: #10b981;
        border-color: #10b981;
        color: #ffffff;
    }

    .bsb-node-current {
        background: #3b82f6;
        border-color: #60a5fa;
        color: #ffffff;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
    }

    .bsb-labels-row {
        display: flex;
        justify-content: space-between;
        gap: 0.1rem;
        margin-top: 0.25rem;
    }

    .bsb-step-col {
        flex: 1;
        text-align: center;
        font-size: 0.575rem;
        color: #71717a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bsb-step-col-active { color: #60a5fa; font-weight: 600; }
    .bsb-step-col-complete { color: #34d399; }
</style>

<div class="bsb-container">
    <!-- Card 1: All Batches Option -->
    <div 
        class="bsb-card {{ $selectedBatchId === null ? 'bsb-card-active' : '' }}"
        wire:click="$set('tableFilters.batch_id.value', null)"
    >
        <div class="bsb-header-row">
            <div class="bsb-title-group">
                <div class="bsb-icon">📦</div>
                <div>
                    <div class="bsb-title">جميع الرحلات</div>
                    <div class="bsb-sub">عرض كافة الشحنات</div>
                </div>
            </div>

            <div class="bsb-metrics-group">
                <span class="bsb-pill">📦 {{ $allShipmentsCount }} شحنة</span>
                <span class="bsb-pill">⚖️ {{ $allWeightSum }} كغ</span>
            </div>
        </div>
    </div>

    <!-- Active Batches Cards -->
    @foreach ($batches as $b)
        @php
            $isCardActive = ((string) $selectedBatchId === (string) $b->id);
            $bShipmentsCount = $b->shipments->count();
            $bWeightSum = rtrim(rtrim(number_format((float) $b->shipments->sum('total_weight_kg'), 2), '0'), '.');
            $routeName = $b->route?->name ?? 'مسار افتراضي';

            $sampleShipment = $b->shipments->first();
            $journeyData = $sampleShipment ? app(PackageJourneyProjection::class)->forShipment($sampleShipment) : null;
            $steps = $journeyData['steps'] ?? [];

            $currentStepIdx = -1;
            $lastCompletedIdx = -1;
            if (!empty($steps)) {
                foreach ($steps as $idx => $step) {
                    if ($step['completed_count'] > 0) {
                        $lastCompletedIdx = $idx;
                    }
                    if ($step['current_count'] > 0) {
                        $currentStepIdx = $idx;
                        break;
                    }
                }
            }

            $progressIdx = $currentStepIdx >= 0 ? $currentStepIdx : ($lastCompletedIdx >= 0 ? $lastCompletedIdx : 0);
            $progressPercent = min(100, max(0, ($progressIdx / 6) * 100));
        @endphp

        <div 
            class="bsb-card {{ $isCardActive ? 'bsb-card-active' : '' }}"
            wire:click="$set('tableFilters.batch_id.value', '{{ $b->id }}')"
        >
            <div class="bsb-header-row">
                <div class="bsb-title-group">
                    <div class="bsb-icon">🚚</div>
                    <div>
                        <div class="bsb-title">الرحلة: {{ $b->reference }}</div>
                        <div class="bsb-sub">المسار: {{ $routeName }}</div>
                    </div>
                </div>

                <div class="bsb-metrics-group">
                    <span class="bsb-pill">📦 {{ $bShipmentsCount }}</span>
                    <span class="bsb-pill">⚖️ {{ $bWeightSum }} كغ</span>
                    <span class="bsb-status-badge">{{ $b->status->label() }}</span>
                </div>
            </div>

            <!-- Stepper Track Inside Card -->
            @if (!empty($steps))
                <div class="bsb-stepper-track">
                    <div class="bsb-track-line-bg"></div>
                    <div class="bsb-track-line-fill" style="width: {{ $progressPercent }}%;"></div>

                    @foreach ($steps as $idx => $step)
                        @php
                            $isCurrent = ($idx === $currentStepIdx);
                            $isComplete = ($idx <= $lastCompletedIdx);
                            $nodeClass = $isCurrent ? 'bsb-node-current' : ($isComplete ? 'bsb-node-complete' : '');
                        @endphp

                        <div class="bsb-node {{ $nodeClass }}" title="{{ $step['label'] }}">
                            @if ($isComplete)
                                ✓
                            @else
                                {{ $idx + 1 }}
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="bsb-labels-row">
                    @foreach ($steps as $idx => $step)
                        @php
                            $isCurrent = ($idx === $currentStepIdx);
                            $isComplete = ($idx <= $lastCompletedIdx);
                            $textClass = $isCurrent ? 'bsb-step-col-active' : ($isComplete ? 'bsb-step-col-complete' : '');
                        @endphp
                        <div class="bsb-step-col {{ $textClass }}" title="{{ $step['label'] }}">
                            {{ $shortTitles[$idx] }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
