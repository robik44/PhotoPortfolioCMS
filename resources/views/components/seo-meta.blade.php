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
