<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تتبع الشحنة {{ $tracking['reference'] }} | HM Cargo Services</title>
    <!-- Google Font: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-deep-black: #0b0f19;
            --card-dark-bg: #111827;
            --card-border: #1f2937;
            --primary-gradient: linear-gradient(135deg, #064e3b 0%, #042f2e 100%);
            --text-white: #f9fafb;
            --text-muted: #9ca3af;
            --accent-emerald: #10b981;
            --accent-amber: #f59e0b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg-deep-black);
            color: var(--text-white);
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        main {
            width: min(100% - 1.5rem, 36rem);
            margin: 0 auto;
            padding: 1rem 0 3.5rem;
        }

        /* Hero Header - Deep Black Glass with Logo */
        header {
            position: relative;
            padding: 2rem 1.5rem 2.25rem;
            border-radius: 1.5rem;
            background: var(--primary-gradient);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: white;
            text-align: center;
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
            overflow: hidden;
        }

        header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 65%);
            pointer-events: none;
        }

        .logo-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            padding: 0.45rem 1.25rem;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(16, 185, 129, 0.4);
            backdrop-filter: blur(12px);
            margin-bottom: 0.85rem;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
        }

        .logo-icon {
            width: 28px;
            height: 28px;
            background: #10b981;
            color: #042f2e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 0.9rem;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }

        .logo-text {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #f9fafb;
        }

        h1 {
            margin: 0 0 0.25rem;
            font-size: 1.75rem;
            font-weight: 800;
        }

        .ref-number {
            font-size: 1.15rem;
            font-weight: 700;
            color: #34d399;
            direction: ltr;
            letter-spacing: 1px;
            margin: 0 0 1rem;
        }

        .status-pill {
            display: inline-block;
            padding: 0.45rem 1.25rem;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            font-weight: 800;
            font-size: 0.95rem;
        }

        /* Step Progress Tracker */
        .stepper-container {
            margin: 1.25rem 0;
            padding: 1.25rem 1rem;
            background: var(--card-dark-bg);
            border-radius: 1.25rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            border: 1px solid var(--card-border);
        }

        .stepper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
        }

        .stepper::before {
            content: "";
            position: absolute;
            top: 18px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #1f2937;
            z-index: 1;
        }

        .step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            flex: 1;
            text-align: center;
        }

        .step-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #111827;
            border: 3px solid #374151;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .step.active .step-icon {
            background: #10b981;
            border-color: #064e3b;
            color: white;
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.4);
        }

        .step.completed .step-icon {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .step-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-muted);
        }

        .step.active .step-title {
            color: #6ee7b7;
        }

        /* Content Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.85rem;
        }

        .card {
            background: var(--card-dark-bg);
            border-radius: 1.15rem;
            padding: 1.1rem;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25);
        }

        .card.full-width {
            grid-column: 1 / -1;
        }

        .card-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .card-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-white);
            margin: 0;
        }

        /* Financial Highlight Banner */
        .finance-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #111827 0%, #1f2937 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: white;
            border-radius: 1.25rem;
            padding: 1.25rem;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .finance-item {
            text-align: center;
        }

        .finance-label {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-bottom: 0.25rem;
        }

        .finance-value {
            font-size: 1.15rem;
            font-weight: 800;
        }

        .finance-value.total { color: #34d399; }
        .finance-value.paid { color: #60a5fa; }
        .finance-value.balance { color: #fbbf24; }

        /* Timeline Section */
        .timeline-section {
            grid-column: 1 / -1;
            margin-top: 0.5rem;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 800;
            margin: 0 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-white);
        }

        .timeline {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .timeline-item {
            position: relative;
            padding-right: 1.75rem;
            padding-bottom: 1.35rem;
            border-right: 2px solid #1f2937;
        }

        .timeline-item:last-child {
            border-right-color: transparent;
            padding-bottom: 0;
        }

        .timeline-node {
            position: absolute;
            right: -7px;
            top: 2px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent-emerald);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.5);
        }

        .timeline-content {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-white);
        }

        .timeline-time {
            font-size: 0.78rem;
            color: var(--text-muted);
            direction: ltr;
            text-align: right;
            margin-top: 0.25rem;
            font-weight: 600;
        }

        .qr-box {
            text-align: center;
            padding: 1rem;
        }

        .qr-box svg {
            max-width: 150px;
            height: auto;
            border-radius: 0.75rem;
            background: white;
            padding: 0.5rem;
        }

        /* Call To Action (CTA) Section */
        .cta-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #064e3b 0%, #059669 100%);
            border-radius: 1.25rem;
            padding: 1.25rem 1.5rem;
            text-align: center;
            box-shadow: 0 8px 24px rgba(6, 78, 59, 0.3);
            margin-top: 0.5rem;
        }

        .cta-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.35rem;
        }

        .cta-subtitle {
            font-size: 0.85rem;
            color: #a7f3d0;
            margin-bottom: 1rem;
        }

        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.85rem 1.25rem;
            background: #ffffff;
            color: #064e3b;
            font-weight: 800;
            font-size: 0.95rem;
            border-radius: 0.85rem;
            text-decoration: none;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            transition: transform 0.2s ease;
        }

        .cta-button:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

<main>
    <!-- Header Hero Banner with Logo -->
    <header>
        <div class="logo-container" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.35rem; margin-bottom: 0.75rem;">
            <img src="{{ asset('images/logo.png') }}" alt="HM Cargo Services Logo" style="height: 72px; width: auto; max-width: 180px; object-fit: contain; filter: drop-shadow(0 4px 16px rgba(0,0,0,0.6));">
            <div class="logo-text" style="font-weight: 800; font-size: 1.1rem; letter-spacing: 0.5px;">HM Cargo Services</div>
        </div>
        <h1>تتبع الشحنة</h1>
        <div class="ref-number">{{ $tracking['reference'] }}</div>
        <div class="status-pill">{{ $tracking['stage'] }}</div>
    </header>

    <!-- Visual Stepper Progress -->
    <div class="stepper-container">
        <div class="stepper">
            <div class="step completed">
                <div class="step-icon">✓</div>
                <div class="step-title">استلام</div>
            </div>
            <div class="step {{ in_array($tracking['stage'], ['طرد في الطريق', 'وصل بعضها إلى الوجهة', 'وصلت لمستودع الوصول', 'جاهزة للاستلام', 'تم التسليم']) ? 'completed' : 'active' }}">
                <div class="step-icon">2</div>
                <div class="step-title">في الطريق</div>
            </div>
            <div class="step {{ in_array($tracking['stage'], ['وصل بعضها إلى الوجهة', 'وصلت لمستودع الوصول', 'جاهزة للاستلام', 'تم التسليم']) ? 'active' : '' }}">
                <div class="step-icon">3</div>
                <div class="step-title">وصلت للمستودع</div>
            </div>
            <div class="step {{ $tracking['stage'] === 'تم التسليم' ? 'completed' : '' }}">
                <div class="step-icon">4</div>
                <div class="step-title">تسليم</div>
            </div>
        </div>
    </div>

    <!-- Details Cards Grid -->
    <div class="cards-grid">
        <div class="card">
            <div class="card-label">المستلم</div>
            <div class="card-value">{{ $tracking['recipient'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">حالة الدفع</div>
            <div class="card-value" style="color: {{ $tracking['payment_status'] === 'مدفوع بالكامل' ? '#34d399' : '#fbbf24' }};">
                {{ $tracking['payment_status'] }}
            </div>
        </div>

        <div class="card full-width">
            <div class="card-label">المسار</div>
            <div class="card-value" style="direction: ltr; text-align: right;">{{ $tracking['route'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">تقدم الطرود</div>
            <div class="card-value">{{ $tracking['progress'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">الوزن الإجمالي</div>
            <div class="card-value">{{ $tracking['total_weight'] }}</div>
        </div>

        <!-- Financial Summary Banner -->
        <div class="finance-card">
            <div class="finance-item">
                <div class="finance-label">الرسم النهائي</div>
                <div class="finance-value total">{{ $tracking['final_charge'] }}</div>
            </div>
            <div style="width: 1px; height: 35px; background: rgba(255,255,255,0.1);"></div>
            <div class="finance-item">
                <div class="finance-label">المدفوع</div>
                <div class="finance-value paid">{{ $tracking['paid_amount'] }}</div>
            </div>
            <div style="width: 1px; height: 35px; background: rgba(255,255,255,0.1);"></div>
            <div class="finance-item">
                <div class="finance-label">المبلغ المتبقي</div>
                <div class="finance-value balance">{{ $tracking['remaining_amount'] }}</div>
            </div>
        </div>

        <!-- Timeline Section -->
        <div class="card full-width timeline-section">
            <div class="section-title">📍 الخط الزمني للشحنة</div>
            @if ($tracking['timeline'] === [])
                <p class="card-label" style="margin:0;">لا توجد أحداث عامة بعد.</p>
            @else
                <ul class="timeline">
                    @foreach ($tracking['timeline'] as $event)
                        <li class="timeline-item">
                            <div class="timeline-node"></div>
                            <div class="timeline-content">{{ $event['label'] }}</div>
                            <div class="timeline-time">{{ $event['occurred_at'] }}</div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <!-- QR Code Section -->
        <div class="card full-width qr-box">
            <div class="card-label" style="margin-bottom:0.75rem;">رمز QR لمتابعة الشحنة</div>
            <div>{!! $tracking['qr'] !!}</div>
        </div>

        <!-- Call To Action (CTA) Card -->
        <div class="cta-card">
            <div class="cta-title">هل لديك أي استفسار حول الشحنة؟</div>
            <div class="cta-subtitle">فريق خدمة العملاء جاهز لمساعدتك مباشرةً</div>
            <a href="https://wa.me/?text={{ urlencode('مرحباً، أستفسر عن شحنتي رقم ' . $tracking['reference']) }}" target="_blank" class="cta-button">
                💬 التواصل المباشر عبر الواتساب
            </a>
        </div>
    </div>
</main>

</body>
</html>
