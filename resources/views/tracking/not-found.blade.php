<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#06090f">
    <title>تعذر العثور على الشحنة | HM Cargo Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    {{--
        Standalone rather than sharing the tracking page's partial: this
        response must not depend on a projection it never has, and it is the
        one page that renders when something has already gone wrong.
    --}}
    <style>
        * { box-sizing: border-box; }

        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            place-items: center;
            padding: 1.5rem;
            background: #06090f;
            color: #f4f6f8;
            font-family: 'Cairo', 'Noto Kufi Arabic', system-ui, -apple-system, sans-serif;
            line-height: 1.7;
        }

        main {
            width: min(100%, 26rem);
            display: grid;
            gap: 1.25rem;
            text-align: center;
        }

        .card {
            padding: 2rem 1.5rem;
            border-radius: 1.1rem;
            background: #0e131c;
            border: 1px solid #212c3c;
        }

        .glyph {
            width: 46px;
            height: 46px;
            margin: 0 auto 1rem;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fbbf24;
        }

        h1 { margin: 0 0 0.5rem; font-size: 1.25rem; font-weight: 800; }
        p { margin: 0; color: #93a0b4; font-size: 0.92rem; }

        a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 1.25rem;
            border-radius: 0.8rem;
            background: #064e3b;
            border: 1px solid rgba(16, 185, 129, 0.35);
            color: #a7f3d0;
            font-weight: 800;
            font-size: 0.92rem;
            text-decoration: none;
        }
    </style>
</head>
<body>
<main>
    <div class="card">
        <div class="glyph">
            <svg width="22" height="22" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M10 1.5 19 18H1L10 1.5Zm0 6a.9.9 0 0 0-.9.9v3.6a.9.9 0 0 0 1.8 0V8.4a.9.9 0 0 0-.9-.9Zm0 7.2a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z"/>
            </svg>
        </div>
        <h1>تعذر العثور على الشحنة</h1>
        <p>تأكد من رابط التتبع أو باركود الطرد وحاول مرة أخرى. إذا وصلك الرابط عبر واتساب، افتحه من الرسالة مباشرة.</p>
    </div>

    <a href="https://wa.me/{{ config('company.support_whatsapp') }}" target="_blank" rel="noreferrer noopener">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm5.5 14.2c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.7-1.2-4.4-3.9-4.6-4.1-.1-.2-1-1.4-1-2.6 0-1.2.6-1.8.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2 0 .4-.1.5l-.3.4c-.1.1-.3.3-.1.6.1.2.6 1 1.3 1.6.9.8 1.6 1 1.9 1.2.2.1.4 0 .5-.1l.7-.8c.2-.2.3-.2.5-.1l1.8.9c.2.1.4.2.4.3.1.2.1.7 0 1.2Z"/>
        </svg>
        تواصل مع خدمة العملاء
    </a>
</main>
</body>
</html>
