<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تتبع الشحنة {{ $tracking['reference'] }}</title>
    <style>
        :root { color-scheme: light; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f7f6; color: #17352f; }
        main { width: min(100% - 2rem, 48rem); margin: 0 auto; padding: 1.25rem 0 3rem; }
        header { padding: 1.5rem; border-radius: 1.25rem; background: #0d5c4f; color: white; }
        h1, h2, p { margin-top: 0; }
        h1 { margin-bottom: .4rem; font-size: clamp(1.5rem, 6vw, 2.25rem); }
        .muted { color: #cce8e1; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .75rem; margin-top: 1rem; }
        .card { padding: 1rem; border: 1px solid #dbe7e4; border-radius: 1rem; background: white; box-shadow: 0 8px 24px rgb(18 63 54 / 7%); }
        .wide { grid-column: 1 / -1; }
        .label { margin-bottom: .3rem; color: #55736d; font-size: .82rem; }
        .value { margin: 0; font-size: 1.05rem; font-weight: 700; }
        .timeline { margin: 0; padding: 0; list-style: none; }
        .timeline li { position: relative; padding: 0 1.35rem 1.15rem 0; border-right: 2px solid #b9d7d0; }
        .timeline li::before { position: absolute; right: -.43rem; top: .15rem; width: .7rem; height: .7rem; border-radius: 999px; background: #0d806d; content: ""; }
        .timeline time { display: block; margin-top: .25rem; color: #667a76; font-size: .78rem; direction: ltr; text-align: right; }
        @media (max-width: 34rem) { .grid { grid-template-columns: 1fr; } .wide { grid-column: auto; } }
    </style>
</head>
<body>
<main>
    <header>
        <p class="muted">HM Cargo Services</p>
        <h1>تتبع الشحنة</h1>
        <p class="value">{{ $tracking['reference'] }}</p>
    </header>

    <section class="grid" aria-label="ملخص الشحنة">
        <article class="card">
            <p class="label">المستلم</p>
            <p class="value">{{ $tracking['recipient'] }}</p>
        </article>
        <article class="card">
            <p class="label">المرحلة الحالية</p>
            <p class="value">{{ $tracking['stage'] }}</p>
        </article>
        <article class="card wide">
            <p class="label">المسار</p>
            <p class="value">{{ $tracking['route'] }}</p>
        </article>
        <article class="card">
            <p class="label">تقدم الطرود</p>
            <p class="value">{{ $tracking['progress'] }}</p>
        </article>
        <article class="card">
            <p class="label">الوزن الإجمالي</p>
            <p class="value">{{ $tracking['total_weight'] }}</p>
        </article>
        <article class="card">
            <p class="label">الرسم النهائي</p>
            <p class="value">{{ $tracking['final_charge'] }}</p>
        </article>
        <article class="card">
            <p class="label">المدفوع</p>
            <p class="value">{{ $tracking['paid_amount'] }}</p>
        </article>
        <article class="card">
            <p class="label">المتبقي</p>
            <p class="value">{{ $tracking['remaining_amount'] }}</p>
        </article>
        <article class="card">
            <p class="label">حالة الدفع</p>
            <p class="value">{{ $tracking['payment_status'] }}</p>
        </article>
        <article class="card wide" aria-label="رمز التتبّع">
            <p class="label">امسح لمتابعة الشحنة</p>
            <div style="width:160px;height:160px;margin-inline:auto;">{!! $tracking['qr'] !!}</div>
        </article>
        <article class="card wide">
            <h2>الخط الزمني</h2>
            @if ($tracking['timeline'] === [])
                <p class="label">لا توجد أحداث عامة بعد.</p>
            @else
                <ol class="timeline">
                    @foreach ($tracking['timeline'] as $event)
                        <li>
                            <strong>{{ $event['label'] }}</strong>
                            <time>{{ $event['occurred_at'] }}</time>
                        </li>
                    @endforeach
                </ol>
            @endif
        </article>
    </section>
</main>
</body>
</html>
