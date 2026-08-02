@php
    use App\Support\Format;

    $packages = $tracking['packages'];
@endphp

<section class="card">
    <h2>
        الطرود ({{ count($packages) }})
        <span class="aside">{{ Format::weight($tracking['total_weight_kg']) }}</span>
    </h2>

    @if ($packages === [])
        <p class="colophon">لم تُسجَّل طرود على هذه الشحنة بعد.</p>
    @else
        <div class="packages">
            @foreach ($packages as $package)
                @php
                    // A collected package has no current step — it is finished.
                    // Without this it falls through to "upcoming" and a
                    // delivered box shows the same grey dot as one not yet sent.
                    $state = match (true) {
                        $package['is_finished'] => 'done',
                        $package['position'] !== null => 'current',
                        default => 'upcoming',
                    };

                    // What the row says when closed: the exception first, then
                    // the delay, then simply where the package has reached.
                    $headline = match (true) {
                        $package['is_exception'] => $package['status_label'],
                        $package['is_delayed'] => 'متأخر · '.$package['stage_label'],
                        default => $package['stage_label'],
                    };

                    $meta = collect([
                        $package['description'],
                        Format::weight($package['weight_kg']),
                    ])->filter()->implode(' · ');
                @endphp

                <details class="package"
                         data-state="{{ $state }}"
                         data-delayed="{{ $package['is_delayed'] ? 'true' : 'false' }}"
                         data-exception="{{ $package['is_exception'] ? 'true' : 'false' }}">
                    <summary>
                        <span class="pip"></span>
                        <span class="headline">
                            <span class="where">{{ $headline }}</span>
                            <span class="meta" dir="auto">{{ $meta }}</span>
                        </span>
                        <span class="code">{{ $package['barcode'] }}</span>
                        <svg class="chevron" width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4Z"/>
                        </svg>
                    </summary>

                    <div class="body">
                        <div class="trail">
                            @foreach ($package['journey'] as $leg)
                                <div class="leg" data-state="{{ $leg['state'] }}">
                                    <span class="mark" aria-hidden="true">
                                        {{ ['done' => '✓', 'current' => '●'][$leg['state']] ?? '○' }}
                                    </span>
                                    <span class="what">{{ $leg['label'] }}</span>
                                    <span class="when">{{ Format::moment($leg['occurred_at']) ?? '' }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if ($package['delay_reason'] !== null)
                            <p class="reason">{{ $package['delay_reason'] }}</p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>
    @endif
</section>
