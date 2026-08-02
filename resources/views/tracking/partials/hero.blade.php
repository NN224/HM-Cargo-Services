@php
    use App\Support\Format;

    $arrived = $tracking['arrived_count'];
    $total = $tracking['package_count'];
    $isDelayed = $tracking['delayed_count'] > 0;

    // The headline colour carries the answer before the words are read:
    // green when there is nothing to wait for, amber when something is wrong.
    $isSettled = in_array($tracking['status'], [
        'ready_for_collection', 'arrived', 'collected', 'partially_collected',
    ], true);

    $tone = match (true) {
        $isDelayed, $tracking['status'] === 'exception' => ['#fbbf24', 'rgba(245, 158, 11, 0.16)'],
        $isSettled => ['#34d399', 'rgba(16, 185, 129, 0.2)'],
        default => ['#f4f6f8', 'rgba(96, 165, 250, 0.14)'],
    };
@endphp

<section class="hero" style="--accent: {{ $tone[0] }}; --glow: {{ $tone[1] }};">
    <p class="eyebrow">حالة الشحنة</p>
    <h1>{{ $tracking['status_label'] }}</h1>

    @if ($total > 0)
        <p class="sub">
            وصل {{ $arrived }} من {{ $total }} {{ $total === 1 ? 'طرد' : 'طرود' }}
        </p>
        {{-- Filled by distance travelled, not by how many have landed: the
             bar must move when the cargo moves. --}}
        <div class="meter" role="presentation">
            <span style="width: {{ max(2, round($tracking['progress_ratio'] * 100)) }}%;"></span>
        </div>
    @else
        <p class="sub">لم تُسجَّل طرود على هذه الشحنة بعد.</p>
    @endif
</section>

@foreach ($tracking['notices'] as $notice)
    <div class="notice">
        <svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 1.5 19 18H1L10 1.5Zm0 6a.9.9 0 0 0-.9.9v3.6a.9.9 0 0 0 1.8 0V8.4a.9.9 0 0 0-.9-.9Zm0 7.2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/>
        </svg>
        <span>{{ $notice }}</span>
    </div>
@endforeach
