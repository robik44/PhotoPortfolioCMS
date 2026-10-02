@php
    $defaults = \App\Support\ContentPages::PAGES['polityka-prywatnosci'];
    $privacyText = trim((string) ($page?->content ?? '')) !== '' ? $page->content : $defaults['text'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('components.seo-meta', ['seo' => \App\Support\Seo::meta($page ?? null, 'Polityka prywatności', url('/polityka-prywatnosci'))])
    @include('components.site-typography')
    <style>
        body{margin:0;background:#fff;color:#222}.privacy-page{max-width:900px;margin:0 auto;padding:70px 28px 90px}
        .privacy-page h1{margin:0 0 32px;font-size:clamp(34px,5vw,52px);font-weight:400}.privacy-copy{max-width:760px;white-space:pre-line;line-height:1.75;color:#555}
        .site-footer{padding:28px;text-align:center;border-top:1px solid #eee;color:#777;font-size:13px}
    </style>
</head>
<body class="site-typography">
@include('components.site-header', ['settings' => $globalHeaderSettings, 'menuItems' => $globalHeaderMenuItems])
<main class="privacy-page"><h1>Polityka prywatności</h1><div class="privacy-copy">{{ $privacyText }}</div></main>
<footer class="site-footer"><a href="{{ route('privacy') }}">Polityka prywatności</a></footer>
</body>
</html>
