<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{--
        The public token is in this URL. Keeping the page out of search
        indexes is the other half of making the token worth having.
    --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <meta name="theme-color" content="#06090f">
    <title>تتبع الشحنة {{ $tracking['reference'] }} | HM Cargo Services</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">

    @include('tracking.partials.styles')
</head>
<body>

<main>
    <header class="masthead">
        <img src="{{ asset('images/logo.png') }}" alt="HM Cargo Services">
        <span class="ref">{{ $tracking['reference'] }}</span>
    </header>

    @include('tracking.partials.hero')
    @include('tracking.partials.journey')
    @include('tracking.partials.packages')
    @include('tracking.partials.details')
    @include('tracking.partials.contact')
</main>

</body>
</html>
