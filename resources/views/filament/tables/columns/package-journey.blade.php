@php
    $journey = $getState();
@endphp

<div class="rounded-2xl border border-gray-200 bg-white/80 p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900/60" dir="rtl">
    <div class="mb-2 flex items-center justify-between gap-3 text-xs text-gray-600 dark:text-gray-300">
        <span class="font-semibold">رحلة الطرود</span>
        <span>{{ $journey['package_count'] }} طرود</span>
    </div>

    <ol class="flex gap-2 overflow-x-auto pb-1" aria-label="رحلة الطرود">
        @foreach ($journey['steps'] as $step)
            @php
                $isCurrent = $step['current_count'] > 0;
                $isComplete = $step['completed_count'] >= $journey['package_count'] && $journey['package_count'] > 0;
                $hasDelay = $step['delayed_count'] > 0;
                $classes = $hasDelay
                    ? 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-500 dark:bg-amber-950 dark:text-amber-100'
                    : ($isCurrent
                        ? 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-500 dark:bg-blue-950 dark:text-blue-100'
                        : ($isComplete
                            ? 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-500 dark:bg-emerald-950 dark:text-emerald-100'
                            : 'border-gray-200 bg-gray-50 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400'));
            @endphp

            <li class="min-w-32 flex-1 rounded-xl border px-3 py-2 text-center {{ $classes }}">
                <div class="text-xs font-semibold leading-5">{{ $step['label'] }}</div>
                <div class="mt-1 text-[11px] leading-4">
                    @if ($step['current_count'] > 0)
                        <span>{{ $step['current_count'] }} حالياً</span>
                    @elseif ($step['completed_count'] > 0)
                        <span>{{ $step['completed_count'] }} أنجزت</span>
                    @else
                        <span>قادم</span>
                    @endif

                    @if ($step['delayed_count'] > 0)
                        <span class="block font-semibold">{{ $step['delayed_count'] }} متأخر</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</div>
