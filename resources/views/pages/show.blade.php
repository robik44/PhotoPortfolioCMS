@php
    $settings = \App\Models\SiteSetting::pluck("value", "key")->toArray();

    $fontCatalog = $siteFonts;
    $backgroundColor = $settings["background_color"] ?? "#ffffff";

    $menuItems = \App\Models\MenuItem::query()
        ->whereNull("parent_id")
        ->where("published", true)
        ->with([
            "page",
            "gallery",
            "children" => function ($query) {
                $query
                    ->where("published", true)
                    ->with(["page", "gallery"])
                    ->orderBy("sort_order");
            },
        ])
        ->orderBy("sort_order")
        ->get();

    $builderContent = optional($page->builder)->content ?? [];
    $sections = $builderContent["sections"] ?? [];
    $aboutFullWidthImageId = $page->slug === 'o-mnie'
        ? (collect($sections)->firstWhere('type', 'image')['id'] ?? null)
        : null;

    $layerMap = [
        "image" => 10,
        "separator" => 20,
        "gallery" => 30,
        "thumbnail_gallery" => 30,
        "section" => 40,
        "text" => 100,
        "heading" => 110,
        "button" => 120,
    ];

    // position_y uses a stable 900px design-height unit in both the CMS and public page.
    // The canvas grows with content instead of rescaling existing positions.
    $designHeight = 900;
    $pageHeight = $designHeight;
    foreach ($sections as $element) {
        $topPx = ((float) ($element["position_y"] ?? 0) / 100) * $designHeight;
        $estimatedHeight = match ($element["type"] ?? "text") {
            "image", "gallery", "thumbnail_gallery" => 420,
            "text" => 260,
            "heading" => 100,
            default => 90,
        };
        $pageHeight = max($pageHeight, $topPx + $estimatedHeight + 100);
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    @if(collect($sections)->contains('type', 'thumbnail_gallery'))
        <link rel="stylesheet" href="{{ asset('css/thumbnail-gallery.css') }}">
    @endif

    @include('components.seo-meta', ['seo' => \App\Support\Seo::meta($page)])

    @vite(["resources/css/app.css", "resources/js/app.js"])

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
            background: {{ $backgroundColor }};
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.96);
            border-bottom: 1px solid #eee;
            backdrop-filter: blur(6px);
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
            display: block;
            white-space: nowrap;
        }

        .logo-main {
            display: block;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: .08em;
            color: #222;
        }

        .logo-subtitle {
            display: block;
            margin-top: 2px;
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
            transition:
                opacity .18s ease,
                transform .18s ease,
                visibility .18s ease;
            z-index: 1100;
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

        .page-editor-view {
            width: 100%;
            min-height: calc(100vh - 76px);
            background: {{ $backgroundColor }};
        }

        .page-canvas {
            position: relative;
            width: 100%;
            max-width: 1400px;
            min-height: {{ $pageHeight }}px;
            margin: 0 auto;
            overflow: hidden;
            background: {{ $backgroundColor }};
        }

        .page-element {
            position: relative;
            box-sizing: border-box;
            margin-bottom: 36px;
        }

        @if($page->slug === 'o-mnie')
            .page-canvas { overflow: visible; }
            .page-element-image.about-full-width-image {
                margin-left: calc(50% - 50vw) !important;
                width: 100vw !important;
                border-radius: 0 !important;
            }
            .page-element-image.about-full-width-image img { width:100%; max-width:none; border-radius:0 !important; }
        @endif

        .page-element-image {
            overflow: hidden;
        }

        .page-element-image img {
            display: block;
            width: 100%;
            height: auto;
            max-width: 100%;
        }

        .page-element-text,
        .page-element-heading,
        .page-element-button,
        .page-element-section,
        .page-element-separator,
        .page-element-gallery,
        .page-element-thumbnail-gallery {
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .page-element-button a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: inherit;
            box-sizing: border-box;
            color: #fff;
            text-decoration: none;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .page-element-gallery {
            padding: 20px;
            background: rgba(247,247,247,.85);
        }

        .page-element-section {
            padding: 20px;
            border: 1px solid rgba(238,238,238,.9);
        }

        .empty-page {
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 15px;
        }

        .site-footer {
            border-top: 1px solid rgba(238,238,238,.9);
            padding: 30px 40px;
            text-align: center;
            color: #888;
            font-size: 13px;
            background: {{ $backgroundColor }};
        }

        @media (max-width: 900px) {
            .header-inner {
                flex-direction: column;
                align-items: flex-start;
                padding: 14px 18px;
                gap: 10px;
            }

            .main-menu {
                width: 100%;
                flex-wrap: wrap;
                justify-content: flex-start;
                gap: 8px 18px;
                font-size: 11px;
            }

            .page-canvas {
                width: 100%;
                min-height: 0;
                padding-left: 18px;
                padding-right: 18px;
            }

            .page-element {
                max-width: 100%;
                margin-left: 0 !important;
            }

            .page-element img {
                max-width: 100%;
                height: auto;
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

            .page-canvas {
                padding-left: 14px;
                padding-right: 14px;
            }
        }
    </style>
</head>

<body>

@include("components.site-header", ["settings" => $settings, "menuItems" => $menuItems])


@include('components.site-typography')
<main class="page-editor-view site-typography">
    @include('components.public-builder-canvas', [
        'sections' => $sections,
        'settings' => $settings,
        'canvasSettings' => $builderContent['settings'] ?? [],
        'fontCatalog' => $fontCatalog,
        'canvasId' => 'page-public-builder-canvas',
    ])
</main>


<footer class="site-footer site-typography">
    {{ $settings["footer_text"] ?? "Fotografia" }}
</footer>

@if(collect($sections)->contains('type', 'gallery'))
    @include('components.gallery-lightbox')
@endif
<script src="{{ asset('js/builder-gallery-layout.js') }}" defer></script>

</body>
</html>
