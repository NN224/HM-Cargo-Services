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
            if (($jd['package_count'] ?? 0) === 0) continue;
            $sCurrent = -1; $sLastDone = -1;
            foreach (($jd['steps'] ?? []) as $idx => $step) {
                if ($step['completed_count'] > 0) $sLastDone = $idx;
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
        $currentStepIdx = -1; $lastCompletedIdx = -1;
        if (!empty($steps)) {
            foreach ($steps as $idx => $step) {
                if ($step['completed_count'] > 0) $lastCompletedIdx = $idx;
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
    .js-sidebar-wrap {
        padding: 1.25rem 0.875rem;
        border-right: 1px solid #292f39;
        background: #0c0f14;
    }
    .js-list {
        display: flex;
        flex-direction: column;
        gap: 0.5625rem;
    }
    .js-card {
        position: relative;
        width: 100%;
        padding: 0.875rem;
        border: 1px solid #292f39;
        border-radius: 0.8125rem;
        background: #111419;
        color: inherit;
        text-align: right;
        cursor: pointer;
        transition: border-color .16s, background .16s, transform .16s;
        box-shadow: none;
        user-select: none;
    }
    .js-card:hover {
        transform: translateY(-1px);
        border-color: #3a424f;
        background: #171b21;
    }
    .js-card-active {
        border-color: #4f8cff !important;
        background: linear-gradient(90deg, transparent, rgba(79,140,255,.09)), #171b21 !important;
        box-shadow: inset 3px 0 0 #4f8cff !important;
    }
    .js-card-row {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        align-items: start;
    }
    .js-name {
        display: block;
        font-size: 0.875rem;
        font-weight: 850;
        color: #f7f8fa;
    }
    .js-route {
        display: block;
        margin-top: 0.1875rem;
        color: #929aa8;
        font-size: 0.6875rem;
    }
    .js-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 0.375rem;
        margin-top: 0.75rem;
    }
    .js-badge {
        display: inline-flex;
        gap: 0.3125rem;
        align-items: center;
        min-width: max-content;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        font-size: 0.6875rem;
        font-weight: 750;
    }
    .js-badge-neutral {
        border: 1px solid #292f39;
        background: #1c2129;
        color: #929aa8;
    }
    .js-badge-money {
        border: 1px solid rgba(255,119,119,.25);
        background: rgba(255,119,119,.12);
        color: #ffb0b0;
    }
    .js-badge-stage {
        border: 1px solid rgba(233,200,83,.25);
        background: rgba(233,200,83,.1);
        color: #e9c853;
    }
    .js-progress {
        display: block;
        height: 4px;
        margin-top: 0.75rem;
        overflow: hidden;
        border-radius: 99px;
        background: #282d35;
    }
    .js-progress span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: #4f8cff;
    }
    .js-progress-copy {
        display: flex;
        justify-content: space-between;
        gap: 0.625rem;
        margin-top: 0.375rem;
        color: #666f7d;
        font-size: 0.625rem;
    }
    .js-sidebar-title {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 1rem;
        margin-bottom: 0.875rem;
    }
    .js-sidebar-title h3 {
        margin: 0;
        font-size: 1rem;
        color: #f7f8fa;
        line-height: 1.35;
    }
    .js-sidebar-title p {
        margin: 0.25rem 0 0;
        color: #929aa8;
        font-size: 0.7rem;
    }
    .js-sidebar-note {
        margin-top: 0.875rem;
        padding: 0.625rem;
        border-radius: 0.5625rem;
        background: rgba(255,255,255,.025);
        color: #666f7d;
        font-size: 0.625rem;
        line-height: 1.5;
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
