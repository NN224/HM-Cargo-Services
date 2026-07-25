<style>
    .pj-container {
        border-radius: 1rem;
        border: 1px solid rgba(229, 231, 235, 1);
        background-color: rgba(255, 255, 255, 0.8);
        padding: 0.75rem;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        direction: rtl;
    }
    .dark .pj-container {
        border-color: rgba(55, 65, 81, 1);
        background-color: rgba(17, 24, 39, 0.6);
    }
    .pj-header {
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        font-size: 0.75rem;
        color: rgba(75, 85, 99, 1);
    }
    .dark .pj-header {
        color: rgba(209, 213, 219, 1);
    }
    .pj-header-title {
        font-weight: 600;
    }
    .pj-list {
        display: flex;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
        margin: 0;
        list-style: none;
    }
    .pj-step {
        min-width: 8rem;
        flex: 1;
        border-radius: 0.75rem;
        border-width: 1px;
        border-style: solid;
        padding: 0.5rem 0.75rem;
        text-align: center;
    }
    .pj-step-title {
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.25rem;
    }
    .pj-step-desc {
        margin-top: 0.25rem;
        font-size: 11px;
        line-height: 1rem;
    }
    .pj-step-delayed {
        display: block;
        font-weight: 600;
    }
    
    /* Colors */
    .pj-step-amber { border-color: #fcd34d; background-color: #fffbeb; color: #78350f; }
    .dark .pj-step-amber { border-color: #f59e0b; background-color: #451a03; color: #fef3c7; }
    
    .pj-step-blue { border-color: #93c5fd; background-color: #eff6ff; color: #1e3a8a; }
    .dark .pj-step-blue { border-color: #3b82f6; background-color: #172554; color: #dbeafe; }
    
    .pj-step-emerald { border-color: #6ee7b7; background-color: #ecfdf5; color: #064e3b; }
    .dark .pj-step-emerald { border-color: #10b981; background-color: #022c22; color: #d1fae5; }
    
    .pj-step-gray { border-color: #e5e7eb; background-color: #f9fafb; color: #6b7280; }
    .dark .pj-step-gray { border-color: #374151; background-color: #1f2937; color: #9ca3af; }
</style>

@php
    $journey = $getState();
@endphp

<div class="pj-container">
    <div class="pj-header">
        <span class="pj-header-title">رحلة الطرود</span>
        <span>{{ $journey['package_count'] }} طرود</span>
    </div>

    <ol class="pj-list" aria-label="رحلة الطرود">
        @foreach ($journey['steps'] as $step)
            @php
                $isCurrent = $step['current_count'] > 0;
                $isComplete = $step['completed_count'] >= $journey['package_count'] && $journey['package_count'] > 0;
                $hasDelay = $step['delayed_count'] > 0;
                
                $colorClass = $hasDelay ? 'pj-step-amber' 
                            : ($isCurrent ? 'pj-step-blue' 
                            : ($isComplete ? 'pj-step-emerald' 
                            : 'pj-step-gray'));
            @endphp

            <li class="pj-step {{ $colorClass }}">
                <div class="pj-step-title">{{ $step['label'] }}</div>
                <div class="pj-step-desc">
                    @if ($step['current_count'] > 0)
                        <span>{{ $step['current_count'] }} حالياً</span>
                    @elseif ($step['completed_count'] > 0)
                        <span>{{ $step['completed_count'] }} أنجزت</span>
                    @else
                        <span>قادم</span>
                    @endif

                    @if ($step['delayed_count'] > 0)
                        <span class="pj-step-delayed">{{ $step['delayed_count'] }} متأخر</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
