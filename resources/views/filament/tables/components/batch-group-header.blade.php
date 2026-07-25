@php
    use App\Services\PackageJourneyProjection;
    use App\Models\Shipment;

    $isUnassigned = ($batch === null);
    $batchRef = $isUnassigned ? 'شحنات غير مسندة لرحلة' : 'الرحلة: ' . $batch->reference;
    $routeName = $batch?->route?->name ?? 'مسار افتراضي';
    $statusLabel = $batch?->status?->label() ?? 'مواقف غير مسندة';
    
    // Aggregate journey calculations for all shipments in this batch
    $batchShipmentsCount = $isUnassigned 
        ? Shipment::whereNull('batch_id')->count() 
        : $batch->shipments()->count();
        
    $batchWeightSum = $isUnassigned 
        ? Shipment::whereNull('batch_id')->sum('total_weight_kg') 
        : $batch->shipments()->sum('total_weight_kg');

    $formattedWeight = rtrim(rtrim(number_format((float) $batchWeightSum, 2), '0'), '.');

    // Aggregate progress projection using representative shipment or batch status
    $sampleShipment = $record ?? ($isUnassigned ? Shipment::whereNull('batch_id')->first() : $batch->shipments()->first());
    $journeyData = $sampleShipment ? app(PackageJourneyProjection::class)->forShipment($sampleShipment) : null;
    $steps = $journeyData['steps'] ?? [];

    $currentStepIdx = -1;
    $lastCompletedIdx = -1;
    $activeStepLabel = $statusLabel;

    if ($journeyData && !empty($steps)) {
        foreach ($steps as $idx => $step) {
            if ($step['completed_count'] > 0) {
                $lastCompletedIdx = $idx;
            }
            if ($step['current_count'] > 0) {
                $currentStepIdx = $idx;
                $activeStepLabel = $step['label'];
                break;
            }
        }
    }

    $progressIdx = $currentStepIdx >= 0 ? $currentStepIdx : ($lastCompletedIdx >= 0 ? $lastCompletedIdx : 0);
    $progressPercent = min(100, max(0, ($progressIdx / 6) * 100));

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
    .bgh-wrapper {
        direction: rtl;
        background: #18181b;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.875rem;
        padding: 0.875rem 1.125rem;
        margin-top: 0.5rem;
        margin-bottom: 0.75rem;
        width: 100%;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .bgh-top-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 0.75rem;
    }

    .bgh-title-group {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .bgh-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        background: rgba(59, 130, 246, 0.15);
        color: #60a5fa;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .bgh-title {
        font-size: 1rem;
        font-weight: 700;
        color: #f4f4f5;
        line-height: 1.2;
    }

    .bgh-sub {
        font-size: 0.75rem;
        color: #a1a1aa;
        margin-top: 0.125rem;
    }

    .bgh-metrics-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .bgh-pill {
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.25rem 0.65rem;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #d4d4d8;
    }

    .bgh-status-badge {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        background: rgba(59, 130, 246, 0.2);
        color: #60a5fa;
        border: 1px solid rgba(59, 130, 246, 0.4);
    }

    /* Stepper track inside batch header */
    .bgh-stepper-track {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0.875rem 0.5rem 0.375rem;
        padding: 0;
    }

    .bgh-track-line-bg {
        position: absolute;
        top: 50%;
        left: 0.75rem;
        right: 0.75rem;
        height: 3px;
        background: #27272a;
        transform: translateY(-50%);
        z-index: 1;
    }

    .bgh-track-line-fill {
        position: absolute;
        top: 50%;
        right: 0.75rem;
        height: 3px;
        background: linear-gradient(to left, #10b981, #3b82f6);
        transform: translateY(-50%);
        z-index: 1;
        transition: width 0.3s ease;
    }

    .bgh-node {
        position: relative;
        z-index: 2;
        width: 1.4rem;
        height: 1.4rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
        background: #18181b;
        border: 2px solid #3f3f46;
        color: #71717a;
    }

    .bgh-node-complete {
        background: #10b981;
        border-color: #10b981;
        color: #ffffff;
    }

    .bgh-node-current {
        background: #3b82f6;
        border-color: #60a5fa;
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
    }

    .bgh-labels-row {
        display: flex;
        justify-content: space-between;
        gap: 0.125rem;
        margin-top: 0.375rem;
    }

    .bgh-step-col {
        flex: 1;
        text-align: center;
        font-size: 0.65rem;
        color: #71717a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .bgh-step-col-active { color: #60a5fa; font-weight: 600; }
    .bgh-step-col-complete { color: #34d399; }
</style>

<div class="bgh-wrapper">
    <!-- Header Top Row -->
    <div class="bgh-top-bar">
        <div class="bgh-title-group">
            <div class="bgh-icon">
                @if ($isUnassigned)
                    📦
                @else
                    🚚
                @endif
            </div>
            <div>
                <div class="bgh-title">{{ $batchRef }}</div>
                <div class="bgh-sub">المسار: {{ $routeName }}</div>
            </div>
        </div>

        <div class="bgh-metrics-group">
            <span class="bgh-pill">📦 {{ $batchShipmentsCount }} شحنات</span>
            <span class="bgh-pill">⚖️ {{ $formattedWeight }} كغ</span>
            <span class="bgh-status-badge">{{ $activeStepLabel }}</span>
        </div>
    </div>

    <!-- Batch Stepper Bar -->
    @if (!empty($steps))
        <div class="bgh-stepper-track">
            <div class="bgh-track-line-bg"></div>
            <div class="bgh-track-line-fill" style="width: {{ $progressPercent }}%;"></div>

            @foreach ($steps as $idx => $step)
                @php
                    $isCurrent = ($idx === $currentStepIdx);
                    $isComplete = ($idx <= $lastCompletedIdx);
                    $nodeClass = $isCurrent ? 'bgh-node-current' : ($isComplete ? 'bgh-node-complete' : '');
                @endphp

                <div class="bgh-node {{ $nodeClass }}" title="{{ $step['label'] }}">
                    @if ($isComplete)
                        ✓
                    @else
                        {{ $idx + 1 }}
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Step Titles -->
        <div class="bgh-labels-row">
            @foreach ($steps as $idx => $step)
                @php
                    $isCurrent = ($idx === $currentStepIdx);
                    $isComplete = ($idx <= $lastCompletedIdx);
                    $textClass = $isCurrent ? 'bgh-step-col-active' : ($isComplete ? 'bgh-step-col-complete' : '');
                @endphp
                <div class="bgh-step-col {{ $textClass }}" title="{{ $step['label'] }}">
                    {{ $shortTitles[$idx] }}
                </div>
            @endforeach
        </div>
    @endif
</div>
