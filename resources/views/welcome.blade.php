@php
    $defaultSettings = [
        'site_title' => 'Fotografia',
        'site_subtitle' => 'Fotografia kulinarna i artystyczna',
        'hero_title' => 'Fotografia to sposób patrzenia na świat',
        'hero_subtitle' => 'Obrazy, smaki i historie',
        'hero_button' => 'Zobacz portfolio',
        'about_title' => 'O mnie',
        'about_text' => 'Tworzę fotografie, które opowiadają historie poprzez światło, formę i detal.',
        'contact_title' => 'Kontakt',
        'contact_text' => 'Zapraszam do współpracy.',
        'contact_email' => '',
        'footer_text' => '© ' . date('Y') . ' Fotografia',
    ];

    $settings = array_merge($defaultSettings, $settings ?? []);

    $heroPhoto = $heroPhoto ?? null;

    $heroImageUrl = $heroImageUrl ?? null;

    if (!$heroImageUrl && $heroPhoto) {
        $heroImageUrl = asset('storage/photos/' . basename($heroPhoto->filename));
    }

    $heroImageUrl = $heroImageUrl ?: null;

    $homeElements = $homeElements ?? [];
    $fontCatalog = app(\App\Services\SiteFontLibrary::class)->catalog();

    $builderTextCss = static function (?array $element, array $fontCatalog, array $defaults = []): string {
        $style = $element['style'] ?? [];

        $fontFamily = \App\Services\SiteFontLibrary::css(
            $style['font_family'] ?? null,
            $fontCatalog,
            $defaults['font_family'] ?? 'Arial'
        );

        $fontSize = (float) ($style['font_size'] ?? ($defaults['font_size'] ?? 18));
        $fontWeight = (int) ($style['font_weight'] ?? ($defaults['font_weight'] ?? 400));
        $color = $style['color'] ?? ($defaults['color'] ?? '#222222');
        $textAlign = $style['text_align'] ?? ($defaults['text_align'] ?? 'left');
        $lineHeight = (float) ($style['line_height'] ?? ($defaults['line_height'] ?? 1.4));
        $letterSpacing = (float) ($style['letter_spacing'] ?? ($defaults['letter_spacing'] ?? 0));

        return "font-family:{$fontFamily};font-size:{$fontSize}px;font-weight:{$fontWeight};color:{$color};"
            . "text-align:{$textAlign};line-height:{$lineHeight};letter-spacing:{$letterSpacing}px;";
    };

    $builderBoxCss = static function (?array $element): string {
        if (!$element) {
            return '';
        }

        $width = max(5, min(100, (float) ($element['element_width'] ?? 100)));
        $x = max(0, min(100 - $width, (float) ($element['position_x'] ?? 0)));
        $height = max(0, (float) ($element['element_height'] ?? 0));

        return "width:{$width}%;max-width:none;margin-left:{$x}%;box-sizing:border-box;"
            . ($height > 0 ? "min-height:{$height}px;" : '')
            . "white-space:normal;overflow-wrap:anywhere;";
    };

    $heroTitleElement = $homeElements['hero_title'] ?? null;
    $heroSubtitleElement = $homeElements['hero_subtitle'] ?? null;
    $portfolioTitleElement = $homeElements['portfolio_title'] ?? null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=1400">

    @include('components.seo-meta', ['seo' => \App\Support\Seo::meta(null)])

    @if(collect($homeSections ?? [])->contains('type', 'thumbnail_gallery'))
        <link rel="stylesheet" href="{{ asset('css/thumbnail-gallery.css') }}">
    @endif

    <style>
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            color: #222;
            background: {{ $settings['background_color'] ?? '#ffffff' }};
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255,255,255,.96);
            border-bottom: 1px solid #eee;
        }

        .header-inner {
            max-width: 1400px;
            min-height: 76px;
            margin: auto;
            padding: 0 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 30px;
        }

        .logo {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .logo span {
            display: block;
            margin-top: 4px;
            font-size: 10px;
            font-weight: 400;
            letter-spacing: .14em;
            color: #777;
        }

        .main-menu {
            display: flex;
            align-items: center;
            gap: 28px;
            font-size: 13px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .main-menu-item {
            position: relative;
        }

        .main-menu-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 0;
            transition: opacity .2s;
        }

        .main-menu-link:hover {
            opacity: .55;
        }

        .main-menu-arrow {
            font-size: 9px;
            line-height: 1;
        }

        .main-submenu {
            position: absolute;
            top: calc(100% + 1px);
            left: -14px;
            min-width: 190px;
            padding: 10px 0;
            margin: 0;
            background: #fff;
            border: 1px solid #eee;
            box-shadow: 0 8px 24px rgba(0,0,0,.08);
            list-style: none;
            opacity: 0;
            visibility: hidden;
            transform: translateY(6px);
            transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
            z-index: 200;
        }

        .main-menu-item:hover > .main-submenu,
        .main-menu-item:focus-within > .main-submenu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .main-submenu li {
            margin: 0;
            padding: 0;
        }

        .main-submenu a {
            display: block;
            padding: 9px 16px;
            white-space: nowrap;
            transition: background .18s, opacity .18s;
        }

        .main-submenu a:hover {
            opacity: 1;
            background: #f7f7f7;
        }

        .hero {
            position: relative;
            min-height: 430px;
            display: flex;
            align-items: center;
            background:
                linear-gradient(rgba(0,0,0,.32), rgba(0,0,0,.32)),
                url('{{ $heroImageUrl ?: asset('images/hero.jpg') }}')
                center / cover no-repeat;
        }

        .hero-content {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            padding: 80px 28px;
            color: #fff;
        }

        .hero-content h1 {
            max-width: 720px;
            margin: 0 0 24px;
            font-size: clamp(36px, 5vw, 70px);
            line-height: 1.05;
            font-weight: 400;
        }

        .hero-content p {
            max-width: 520px;
            margin: 0 0 34px;
            font-size: 18px;
            line-height: 1.6;
        }

        .button {
            display: inline-block;
            padding: 15px 25px;
            border: 1px solid currentColor;
            font-size: 12px;
            letter-spacing: .12em;
            text-transform: uppercase;
            transition: background .2s, color .2s;
        }

        .button:hover {
            color: #222;
        }

        .button:not([style*="background-color"]) {
            background: transparent;
        }

        .button:not([style*="background-color"]):hover {
            background: #fff;
        }

        .section {
            max-width: 1200px;
            margin: auto;
            padding: 55px 28px;
        }

        .section-heading {
            margin-bottom: 45px;
        }

        .section-heading h2 {
            margin: 0 0 14px;
            font-size: 34px;
            font-weight: 400;
        }

        .section-heading p {
            max-width: 650px;
            margin: 0;
            color: #777;
            line-height: 1.7;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .gallery-card {
            display: block;
            overflow: hidden;
            background: #f4f4f4;
        }

        .gallery-card-image {
            aspect-ratio: 4 / 5;
            overflow: hidden;
            background: #f7f7f7;
        }

        .gallery-card-image img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            transition: transform .45s ease;
        }

        .gallery-card:hover img {
            transform: scale(1.04);
        }

        .gallery-card-content {
            padding: 20px 2px 4px;
        }

        .gallery-card-content h3 {
            margin: 0 0 8px;
            font-size: 17px;
            font-weight: 400;
        }

        .gallery-card-content p {
            margin: 0;
            color: #777;
            font-size: 14px;
            line-height: 1.6;
        }

        .empty-gallery {
            padding: 50px;
            text-align: center;
            color: #777;
            background: #f7f7f7;
        }

        .about-section {
            background: transparent;
        }

        .about-inner {
            max-width: 1200px;
            margin: auto;
            padding: 55px 28px;
        }

        .about-inner h2,
        .contact-inner h2 {
            margin: 0 0 20px;
            font-size: 34px;
            font-weight: 400;
        }

        .about-inner p,
        .contact-inner p {
            max-width: 700px;
            color: #666;
            line-height: 1.8;
        }

        .contact-inner {
            max-width: 1200px;
            margin: auto;
            padding: 55px 28px;
        }

        .contact-email {
            display: inline-block;
            margin-top: 15px;
            font-size: 18px;
            border-bottom: 1px solid #222;
        }

        .site-footer {
            padding: 30px 28px;
            border-top: 1px solid #eee;
            color: #888;
            font-size: 12px;
            text-align: center;
        }

        /* Responsive layout: desktop 4, tablet 2, phone 1. */
        @media (max-width: 900px) {
            .header-inner {
                min-height: 65px;
                padding: 14px 18px;
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .main-menu {
                width: 100%;
                flex-wrap: wrap;
                gap: 8px 18px;
                font-size: 11px;
            }

            .main-submenu {
                left: 0;
            }

            .hero {
                min-height: 460px;
            }

            .hero-content {
                padding: 60px 20px;
            }

            .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 14px;
            }

            .section,
            .about-inner,
            .contact-inner {
                padding: 50px 20px;
            }
        }

        @media (max-width: 520px) {
            .site-header .logo {
                width: 100%;
                white-space: nowrap;
                overflow-wrap: normal;
            }

            .site-header .logo > span:first-child {
                white-space: nowrap;
                font-size: clamp(14px, 5.2vw, 18px) !important;
                letter-spacing: .055em !important;
            }

            .site-header .logo > span:last-child {
                white-space: nowrap;
                font-size: clamp(7px, 2.35vw, 10px) !important;
                letter-spacing: .08em !important;
            }

            .header-inner {
                padding-top: 14px;
                padding-bottom: 14px;
            }

            .main-menu {
                flex-wrap: nowrap;
                justify-content: space-between;
                gap: 0;
                font-size: clamp(7px, 2.15vw, 9px);
                letter-spacing: .02em;
            }

            .main-menu-item {
                flex: 0 1 auto;
                min-width: 0;
            }

            .main-menu-link {
                gap: 3px;
                white-space: nowrap;
            }

            .main-menu-arrow {
                font-size: 7px;
            }

            .hero {
                min-height: 420px;
            }

            .hero-content h1 {
                font-size: clamp(34px, 11vw, 44px);
            }

            .hero-content p {
                font-size: 16px;
            }

            .gallery-grid {
                grid-template-columns: 1fr;
            }

            .gallery-card-content {
                padding-top: 14px;
            }
        }

    </style>
</head>

<body style="background: {{ $settings['background_color'] ?? '#ffffff' }};">

@include("components.site-header", ["settings" => $settings, "menuItems" => $menuItems])

@include('components.site-typography')
<main class="site-typography">
    @include('components.public-builder-canvas', [
        'sections' => $homeSections ?? collect(),
        'settings' => $settings,
        'canvasSettings' => $homeBuilderSettings ?? [],
        'fontCatalog' => $fontCatalog,
        'canvasId' => 'home-public-builder-canvas',
    ])
</main>

<footer class="site-footer site-typography">
    {{ $settings['footer_text'] }}
    <span aria-hidden="true"> · </span><a href="{{ route('privacy') }}" style="text-decoration:underline;text-underline-offset:3px;">Polityka prywatności</a>
</footer>

@if(collect($homeSections ?? [])->contains('type', 'gallery'))
    @include('components.gallery-lightbox')
@endif
<script src="{{ asset('js/builder-gallery-layout.js') }}" defer></script>

</body>
</html>
