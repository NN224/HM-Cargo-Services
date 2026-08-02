@php
    use App\Support\Format;

    $steps = $tracking['journey'];
    $count = $tracking['package_count'];
@endphp

<section class="card">
    <h2>
        مسار الرحلة
        <span class="aside" dir="auto">{{ $tracking['route'] ?? 'لم يُحدد المسار بعد' }}</span>
    </h2>

    <div class="stepper">
        @foreach ($steps as $step)
            @php
                // A step is done only once every package has moved past it;
                // while they are split across steps, each one that still holds
                // packages reads as current.
                $state = match (true) {
                    $count > 0 && $step['completed_count'] >= $count => 'done',
                    $step['current_count'] > 0 => 'current',
                    default => 'upcoming',
                };
            @endphp
            <div class="step" data-state="{{ $state }}" data-delayed="{{ $step['delayed_count'] > 0 ? 'true' : 'false' }}">
                <span class="dot"></span>
                <span class="caption">{{ $step['label'] }}</span>
                <span class="tally">
                    @if ($state === 'done')
                        تم
                    @elseif ($step['current_count'] > 0)
                        {{ $step['current_count'] }} حالياً
                    @elseif ($step['completed_count'] > 0)
                        {{-- Some packages are through this step and some are
                             not; saying so beats leaving the step blank. --}}
                        {{ $step['completed_count'] }} مرّت
                    @endif

                    {{-- A held package is still at this step, so the delay is
                         reported alongside the count rather than instead of it. --}}
                    @if ($step['delayed_count'] > 0)
                        <span class="held">{{ $step['delayed_count'] }} متأخر</span>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
</section>
