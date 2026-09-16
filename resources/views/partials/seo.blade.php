@php
    $seoTitle = $seo->title();
    $seoDesc = $seo->description();
    $seoUrl = $seo->url();
    $seoImage = $seo->image();
    $nameParts = preg_split('/\s+/', trim($profile->name)) ?: [];
@endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDesc }}">
    <meta name="author" content="{{ $profile->name }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ $seoUrl }}">
    <link rel="sitemap" type="application/xml" href="{{ route('sitemap') }}">
    @foreach (array_filter([$profile->github_url, $profile->linkedin_url]) as $me)
        <link rel="me" href="{{ $me }}">
    @endforeach

    {{-- Open Graph: Facebook, LinkedIn, WhatsApp, Telegram, Slack, Discord --}}
    <meta property="og:type" content="profile">
    <meta property="og:site_name" content="{{ $profile->name }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:url" content="{{ $seoUrl }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDesc }}">
    @if (count($nameParts) > 1)
        <meta property="profile:first_name" content="{{ $nameParts[0] }}">
        <meta property="profile:last_name" content="{{ end($nameParts) }}">
    @endif
    @if ($seoImage)
        <meta property="og:image" content="{{ $seoImage['url'] }}">
        <meta property="og:image:secure_url" content="{{ $seoImage['url'] }}">
        <meta property="og:image:type" content="{{ $seoImage['type'] }}">
        <meta property="og:image:width" content="{{ $seoImage['width'] }}">
        <meta property="og:image:height" content="{{ $seoImage['height'] }}">
        <meta property="og:image:alt" content="{{ $seoImage['alt'] }}">
    @endif

    {{-- X / Twitter --}}
    <meta name="twitter:card" content="{{ $seoImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDesc }}">
    @if ($seoImage)
        <meta name="twitter:image" content="{{ $seoImage['url'] }}">
        <meta name="twitter:image:alt" content="{{ $seoImage['alt'] }}">
    @endif
    @if ($seo->twitter())
        <meta name="twitter:site" content="{{ $seo->twitter() }}">
        <meta name="twitter:creator" content="{{ $seo->twitter() }}">
    @endif

    {{-- Icons --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/favicon-32.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ route('manifest') }}">
    <meta name="theme-color" content="#e6e4df">

    {{-- Structured data (Google rich results) --}}
    <script type="application/ld+json">{!! json_encode($seo->schema(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
