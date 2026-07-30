@php
    use App\Enums\BatchStatus;
    use App\Models\Batch;
    use App\Models\Shipment;
    use App\Services\PackageJourneyProjection;

    $batches = Batch::with(['route.originWarehouse', 'route.destinationWarehouse', 'shipments.packages'])
        ->whereNotIn('status', [BatchStatus::Completed->value, BatchStatus::Cancelled->value])
        ->orderBy('created_at', 'desc')
        ->get();

    $allShipmentsCount = Shipment::count();
    $allWeightSum = rtrim(rtrim(number_format((float) Shipment::sum('total_weight_kg'), 2), '0'), '.');
    $allUnpaidCents = Shipment::whereNotNull('final_charge_cents')
        ->get()
        ->sum(fn ($s) => max(0, (int) ($s->final_charge_cents ?? 0) - (int) ($s->paid_amount_cents ?? 0)));

    $allJourneyOptions = collect([
        (object) ['id' => null, 'summary' => (object) [
            'count' => $allShipmentsCount,
            'weight' => $allWeightSum,
            'unpaid' => $allUnpaidCents > 0 ? '$'.number_format($allUnpaidCents / 100, 2) : null,
            'stageName' => null,
            'progress' => null,
        ]],
    ])->merge($batches->map(function ($b) {
        $bWeight = rtrim(rtrim(number_format((float) $b->shipments->sum('total_weight_kg'), 2), '0'), '.');
        $bUnpaid = 0;
        foreach ($b->shipments as $s) {
            $bUnpaid += max(0, (int) ($s->final_charge_cents ?? 0) - (int) ($s->paid_amount_cents ?? 0));
        }

        $minProgressIdx = null;
        $minJourneyData = null;
        foreach ($b->shipments as $shipment) {
            $jd = app(PackageJourneyProjection::class)->forShipment($shipment);
            $jdPkgCount = $jd['package_count'] ?? 0;
            if ($jdPkgCount === 0) continue;
            $sCurrent = -1; $sLastDone = -1;
            foreach (($jd['steps'] ?? []) as $idx => $step) {
                if ($step['completed_count'] >= $jdPkgCount && $jdPkgCount > 0) $sLastDone = $idx;
                if ($step['current_count'] > 0) { $sCurrent = $idx; break; }
            }
            $sIdx = $sCurrent >= 0 ? $sCurrent : ($sLastDone >= 0 ? $sLastDone : 0);
            if ($minProgressIdx === null || $sIdx < $minProgressIdx) {
                $minProgressIdx = $sIdx;
                $minJourneyData = $jd;
            }
        }
        if ($minJourneyData === null && $b->shipments->first()) {
            $minJourneyData = app(PackageJourneyProjection::class)->forShipment($b->shipments->first());
        }

        $jd = $minJourneyData;
        $steps = $jd['steps'] ?? [];
        $jdPackageCount = $jd['package_count'] ?? 0;
        $currentStepIdx = -1; $lastCompletedIdx = -1;
        if (!empty($steps)) {
            foreach ($steps as $idx => $step) {
                if ($step['completed_count'] >= $jdPackageCount && $jdPackageCount > 0) $lastCompletedIdx = $idx;
                if ($step['current_count'] > 0) { $currentStepIdx = $idx; break; }
            }
        }
        $progressIdx = $currentStepIdx >= 0 ? $currentStepIdx : ($lastCompletedIdx >= 0 ? $lastCompletedIdx : 0);
        $progressPercent = empty($steps) ? null : min(100, max(0, round(($progressIdx / 6) * 100)));
        $stageLabel = $steps[$currentStepIdx]['label'] ?? ($steps[$lastCompletedIdx]['label'] ?? null);

        return (object) [
            'id' => $b->id,
            'model' => $b,
            'summary' => (object) [
                'count' => $b->shipments->count(),
                'weight' => $bWeight,
                'unpaid' => $bUnpaid > 0 ? '$'.number_format($bUnpaid / 100, 2) : null,
                'stageName' => $stageLabel,
                'progress' => $progressPercent,
            ],
        ];
    }));
@endphp

<style>
    :root {
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

    .js-sidebar-wrap {
        padding: 24px;
    }
    .js-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .js-card {
        position: relative;
        width: 100%;
        padding: 16px;
        border: 1px solid var(--hm-border);
        border-radius: 16px;
        background: var(--hm-bg-page);
        color: inherit;
        text-align: right;
        cursor: pointer;
        transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: var(--hm-shadow-sm);
        user-select: none;
    }
    .js-card:hover {
        transform: translateY(-2px);
        border-color: var(--hm-border-hover);
        box-shadow: var(--hm-shadow-md);
    }
    .js-card-active {
        border-color: #3b82f6 !important;
        background: var(--hm-blue-bg) !important;
        box-shadow: 0 0 0 1px #3b82f6, var(--hm-shadow-md) !important;
    }
    .js-card-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: start;
    }
    .js-name {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: var(--hm-text-main);
        letter-spacing: 0.5px;
    }
    .js-route {
        display: block;
        margin-top: 4px;
        color: var(--hm-text-muted);
        font-size: 11px;
        font-weight: 500;
    }
    .js-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }
    .js-badge {
        display: inline-flex;
        gap: 6px;
        align-items: center;
        min-width: max-content;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
    }
    .js-badge-neutral {
        border: 1px solid var(--hm-border);
        background: var(--hm-bg-hover);
        color: var(--hm-text-sub);
    }
    .js-badge-money {
        border: 1px solid var(--hm-badge-red-border);
        background: var(--hm-badge-red-bg);
        color: var(--hm-badge-red-text);
    }
    .js-badge-stage {
        border: 1px solid var(--hm-badge-orange-border);
        background: var(--hm-badge-orange-bg);
        color: var(--hm-badge-orange-text);
    }
    .js-progress {
        display: block;
        height: 6px;
        margin-top: 12px;
        overflow: hidden;
        border-radius: 99px;
        background: var(--hm-step-line);
    }
    .js-progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
    }
    .js-progress-copy {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 8px;
        color: var(--hm-text-muted);
        font-size: 11px;
        font-weight: 500;
    }
    .js-sidebar-title {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 16px;
        margin-bottom: 24px;
    }
    .js-sidebar-title h3 {
        margin: 0;
        font-size: 18px;
        color: var(--hm-text-main);
        line-height: 1.35;
        font-weight: 700;
    }
    .js-sidebar-title p {
        margin: 4px 0 0;
        color: var(--hm-text-muted);
        font-size: 12px;
    }
    .js-sidebar-note {
        margin-top: 24px;
        padding: 16px;
        border-radius: 12px;
        background: var(--hm-blue-bg);
        border: 1px solid var(--hm-blue-border);
        color: var(--hm-text-sub);
        font-size: 11px;
        line-height: 1.6;
        font-weight: 500;
    }
</style>

<div class="js-sidebar-wrap">
    <div class="js-sidebar-title">
        <div><h3>الرحلات النشطة</h3><p>المكتملة والملغاة غير ظاهرة</p></div>
        <span class="js-badge js-badge-neutral">{{ $batches->count() }}</span>
    </div>
    <div class="js-list">
        @foreach ($allJourneyOptions as $opt)
            @php
                $isActive = ($this->selectedBatchId === $opt->id);
                $s = $opt->summary;
                $isAll = ($opt->id === null);
            @endphp
            <div
                class="js-card {{ $isActive ? 'js-card-active' : '' }}"
                wire:click="selectBatch({{ $isAll ? 'null' : $opt->id }})"
            >
                <span class="js-card-row">
                    <span>
                        <span class="js-name">{{ $isAll ? 'جميع الرحلات' : $opt->model->reference }}</span>
                        <span class="js-route">
                            {{ $isAll
                                ? 'عرض كل الشحنات النشطة'
                                : ($opt->model->route?->originWarehouse?->name ?? 'دبي').' ← '.($opt->model->route?->destinationWarehouse?->name ?? 'الوجهة')
                            }}
                        </span>
                    </span>
                    @if ($s->stageName)
                        <span class="js-badge js-badge-stage">{{ $s->stageName }}</span>
                    @elseif ($isAll)
                        <span class="js-badge js-badge-neutral">نظرة شاملة</span>
                    @endif
                </span>
                <span class="js-stats">
                    <span class="js-badge js-badge-neutral">▣ {{ $s->count }} شحنات</span>
                    <span class="js-badge js-badge-neutral">⚖ {{ $s->weight }} كغ</span>
                    @if ($s->unpaid)
                        <span class="js-badge js-badge-money">{{ $s->unpaid }} غير مدفوع</span>
                    @endif
                </span>
                @if ($s->progress !== null)
                    <span class="js-progress"><span style="width:{{ $s->progress }}%"></span></span>
                    <span class="js-progress-copy">
                        <span>المرحلة حسب أبطأ شحنة</span>
                        <span>{{ $s->progress }}%</span>
                    </span>
                @endif
            </div>
        @endforeach
    </div>
    <div class="js-sidebar-note">بطاقة الرحلة تعرض مرحلتها وفق أبطأ شحنة حتى لا يبدو أن الدفعة تقدمت قبل اكتمال جميع شحناتها.</div>
</div>
