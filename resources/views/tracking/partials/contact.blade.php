@php
    $support = config('company.support_whatsapp');
    $message = 'مرحباً، أستفسر عن شحنتي رقم '.$tracking['reference'];
@endphp

<section class="card">
    <details class="disclose qr-disclose">
        <summary>
            رمز الاستلام
            <svg width="13" height="13" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4Z"/>
            </svg>
        </summary>
        <div class="qr">
            {!! $qr !!}
            <p>اعرض هذا الرمز عند الاستلام ليفتح الموظف الشحنة مباشرة.</p>
        </div>
    </details>
</section>

<section class="cta">
    <p>
        <strong>عندك استفسار؟</strong>
        فريق خدمة العملاء جاهز لمساعدتك
    </p>
    {{--
        rel="noreferrer" matters here: the public token sits in this page's
        URL, and without it the browser hands the full address to WhatsApp in
        the Referer header.
    --}}
    <a href="https://wa.me/{{ $support }}?text={{ urlencode($message) }}"
       target="_blank"
       rel="noreferrer noopener">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm5.5 14.2c-.2.6-1.2 1.2-1.7 1.2-.5.1-1 .1-1.6-.1-.4-.1-.9-.3-1.5-.6-2.7-1.2-4.4-3.9-4.6-4.1-.1-.2-1-1.4-1-2.6 0-1.2.6-1.8.9-2.1.2-.2.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 1.9c.1.2 0 .4-.1.5l-.3.4c-.1.1-.3.3-.1.6.1.2.6 1 1.3 1.6.9.8 1.6 1 1.9 1.2.2.1.4 0 .5-.1l.7-.8c.2-.2.3-.2.5-.1l1.8.9c.2.1.4.2.4.3.1.2.1.7 0 1.2Z"/>
        </svg>
        تواصل عبر واتساب
    </a>
</section>

<p class="colophon">HM Cargo Services</p>
