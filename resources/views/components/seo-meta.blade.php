<title>{{ $seo['title'] }}</title>
@if($seo['description'] !== '')
<meta name="description" content="{{ $seo['description'] }}">
@endif
<link rel="canonical" href="{{ $seo['canonical'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
@if($seo['description'] !== '')
<meta property="og:description" content="{{ $seo['description'] }}">
@endif
<meta property="og:url" content="{{ $seo['canonical'] }}">
@if($seo['image'])
<meta property="og:image" content="{{ $seo['image'] }}">
@endif
<meta property="og:type" content="{{ $seo['type'] }}">

<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
@if($seo['description'] !== '')
<meta name="twitter:description" content="{{ $seo['description'] }}">
@endif
@if($seo['image'])
<meta name="twitter:image" content="{{ $seo['image'] }}">
@endif
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $seo['title'],
    'description' => $seo['description'] ?: null,
    'url' => $seo['canonical'],
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => $seo['site_name'],
        'url' => url('/'),
    ],
    'primaryImageOfPage' => $seo['image'] ? [
        '@type' => 'ImageObject',
        'url' => $seo['image'],
    ] : null,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
