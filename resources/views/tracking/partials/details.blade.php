@php
    use App\Support\Format;
@endphp

{{--
    No amounts here on purpose. The charge, the paid figure and the balance
    used to sit in this slot; they are the operator's business and the page is
    reachable by anyone the link is forwarded to. AGENTS.md permits publishing
    them but does not require it.
--}}
<section class="card">
    <h2>التفاصيل</h2>

    <div class="figures">
        <div class="figure">
            <div class="key">عدد الطرود</div>
            <div class="val">{{ $tracking['package_count'] }}</div>
        </div>
        <div class="figure">
            <div class="key">الوزن الإجمالي</div>
            <div class="val">{{ Format::weight($tracking['total_weight_kg']) }}</div>
        </div>
    </div>

    {{-- dir="auto" on every value that may hold a Latin name: without it
         "VANESSA M***" renders as "***VANESSA M" inside an RTL page. --}}
    <dl class="rows">
        <div class="row">
            <dt>المستلم</dt>
            <dd dir="auto">{{ $tracking['recipient'] }}</dd>
        </div>
        <div class="row">
            <dt>المسار</dt>
            <dd dir="auto">{{ $tracking['route'] ?? 'لم يُحدد بعد' }}</dd>
        </div>
        <div class="row">
            <dt>رقم الشحنة</dt>
            <dd dir="ltr">{{ $tracking['reference'] }}</dd>
        </div>
    </dl>
</section>
