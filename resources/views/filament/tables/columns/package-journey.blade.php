<style>
    .pj-wrapper {
        direction: rtl;
        background: rgba(24, 24, 27, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 0.875rem;
        padding: 0.75rem 0.875rem;
        margin-top: 0.375rem;
    }

    .pj-header-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.625rem;
    }

    .pj-title {
        font-size: 0.725rem;
        font-weight: 600;
        color: #a1a1aa;
        display: flex;
        align-items: center;
        gap: 0.375rem;
    }

    .pj-status-badge {
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        line-height: 1.2;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .pj-badge-current { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    .pj-badge-complete { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
    .pj-badge-delayed { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
    .pj-badge-pending { background: rgba(156, 163, 175, 0.12); color: #a1a1aa; border: 1px solid rgba(156, 163, 175, 0.2); }

    /* Stepper track */
    .pj-stepper-track {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0.75rem 0.25rem 0.375rem;
        padding: 0;
    }

    .pj-track-line-bg {
        position: absolute;
        top: 50%;
        left: 0.75rem;
        right: 0.75rem;
        height: 2px;
        background: #27272a;
        transform: translateY(-50%);
        z-index: 1;
    }

    .pj-track-line-fill {
        position: absolute;
        top: 50%;
        right: 0.75rem;
        height: 2px;
        background: linear-gradient(to left, #10b981, #3b82f6);
        transform: translateY(-50%);
        z-index: 1;
        transition: width 0.3s ease;
    }

    .pj-node {
        position: relative;
        z-index: 2;
        width: 1.35rem;
        height: 1.35rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
        background: #18181b;
        border: 2px solid #3f3f46;
        color: #71717a;
        transition: all 0.2s ease;
    }

    .pj-node-complete {
        background: #10b981;
        border-color: #10b981;
        color: #ffffff;
    }

    .pj-node-current {
        background: #3b82f6;
        border-color: #60a5fa;
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.25);
    }

    .pj-node-delayed {
        background: #f59e0b;
        border-color: #fbbf24;
        color: #ffffff;
        box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.25);
    }

    /* Labels below stepper */
    .pj-labels-row {
        display: flex;
        justify-content: space-between;
        gap: 0.125rem;
        margin-top: 0.375rem;
    }

    .pj-step-col {
        flex: 1;
        text-align: center;
        font-size: 0.625rem;
        color: #71717a;
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .pj-step-col-active {
        color: #60a5fa;
        font-weight: 600;
    }

    .pj-step-col-complete {
        color: #34d399;
    }
</style>

@php
    $journey = $getState();
    $steps = $journey['steps'];
    $packageCount = $journey['package_count'];
    $delayedCount = $journey['delayed_count'];

    // Determine current active step index and state summary
    $currentStepIndex = -1;
    $lastCompletedIndex = -1;
    $activeStepLabel = 'قيد التجهيز';
    $statusType = 'pending'; // pending, current, complete, delayed

    if ($delayedCount > 0) {
        $statusType = 'delayed';
        $activeStepLabel = '⚠️ يوجد طرد متأخر';
    } else {
        foreach ($steps as $idx => $step) {
            if ($step['completed_count'] >= $packageCount && $packageCount > 0) {
                $lastCompletedIndex = $idx;
            }
            if ($step['current_count'] > 0) {
                $currentStepIndex = $idx;
                $activeStepLabel = $step['label'];
                $statusType = 'current';
                break;
            }
        }

        if ($currentStepIndex === -1 && $lastCompletedIndex === 6) {
            $statusType = 'complete';
            $activeStepLabel = '✓ مكتمل وتسلم للعميل';
        } elseif ($currentStepIndex === -1 && $lastCompletedIndex >= 0) {
            $activeStepLabel = 'في الطريق إلى الخطوة التالية';
            $statusType = 'current';
        }
    }

    // Short titles for the 7 steps below the line
    $shortTitles = [
        'مستودع المبدأ',
        'مطار المبدأ',
        'مغادرة المبدأ',
        'مطار الوصول',
        'مغادرة الوصول',
        'مكتب التسليم',
        'استلام العميل'
    ];

    // Progress line width percentage (0 to 100)
    $progressIdx = $currentStepIndex >= 0 ? $currentStepIndex : ($lastCompletedIndex >= 0 ? $lastCompletedIndex : 0);
    $progressPercent = min(100, max(0, ($progressIdx / 6) * 100));
@endphp

<div class="pj-wrapper">
    <div class="pj-header-row">
        <div class="pj-title">
            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
            <span>مسار الطرود</span>
            <span class="text-xs text-gray-500 font-normal">({{ $packageCount }} طرد)</span>
        </div>

        <div class="pj-status-badge pj-badge-{{ $statusType }}">
            {{ $activeStepLabel }}
        </div>
    </div>

    <!-- 7-Step Stepper Bar -->
    <div class="pj-stepper-track">
        <div class="pj-track-line-bg"></div>
        <div class="pj-track-line-fill" style="width: {{ $progressPercent }}%;"></div>

        @foreach ($steps as $idx => $step)
            @php
                $isCurrent = ($idx === $currentStepIndex);
                $isComplete = ($idx <= $lastCompletedIndex);
                $hasDelay = ($step['delayed_count'] > 0);

                $nodeClass = $hasDelay ? 'pj-node-delayed' : ($isCurrent ? 'pj-node-current' : ($isComplete ? 'pj-node-complete' : ''));
            @endphp

            <div class="pj-node {{ $nodeClass }}" title="{{ $step['label'] }}">
                @if ($hasDelay)
                    !
                @elseif ($isComplete)
                    ✓
                @else
                    {{ $idx + 1 }}
                @endif
            </div>
        @endforeach
    </div>

    <!-- Concise Step Titles Row -->
    <div class="pj-labels-row">
        @foreach ($steps as $idx => $step)
            @php
                $isCurrent = ($idx === $currentStepIndex);
                $isComplete = ($idx <= $lastCompletedIndex);
                $textClass = $isCurrent ? 'pj-step-col-active' : ($isComplete ? 'pj-step-col-complete' : '');
            @endphp
            <div class="pj-step-col {{ $textClass }}" title="{{ $step['label'] }}">
                {{ $shortTitles[$idx] }}
            </div>
        @endforeach
    </div>
</div>
