@php
    $headerSettings = \App\Support\HeaderSettings::resolve($settings ?? []);
    $headerFonts = \App\Support\HeaderFonts::custom($settings ?? []);
    $headerFamilies = \App\Support\HeaderFonts::families($headerFonts);
@endphp

@include('components.header-font-faces', ['fonts' => $headerFonts])

<style>
    .site-header .header-inner { padding-top: var(--header-padding-top); padding-bottom: var(--header-padding-bottom); }
    .site-header .logo { display: block; max-width: 100%; white-space: normal; overflow-wrap: anywhere; }
    .site-header .logo span { display: block; }
    .site-header.header-layout-center .header-inner { flex-direction: column; align-items: center; gap: 16px; }
    .site-header.header-layout-center .logo { text-align: center; }
    .site-header.header-layout-center .main-menu { justify-content: center; flex-wrap: wrap; }
    .site-header.header-layout-right .main-menu { order: -1; flex-wrap: wrap; }
    .site-header.header-layout-right .logo { margin-left: auto; text-align: right; }
    .site-header .main-menu-item { position: relative; }
    .site-header .main-menu-link { display: inline-flex; align-items: center; gap: 6px; padding: 10px 0; }
    .site-header .main-menu-arrow { font-size: 9px; line-height: 1; }
    .site-header .main-submenu {
        position: absolute; top: calc(100% + 1px); left: -14px; min-width: 190px;
        padding: 10px 0; margin: 0; background: #fff; border: 1px solid #eee;
        box-shadow: 0 8px 24px rgba(0,0,0,.08); list-style: none;
        opacity: 0; visibility: hidden; transform: translateY(6px);
        transition: opacity .18s ease, transform .18s ease, visibility .18s ease; z-index: 200;
    }
    .site-header .main-menu-item:hover > .main-submenu,
    .site-header .main-menu-item:focus-within > .main-submenu { opacity: 1; visibility: visible; transform: translateY(0); }
    .site-header .main-submenu li { margin: 0; padding: 0; }
    .site-header .main-submenu a { display: block; padding: 9px 16px; white-space: nowrap; }
    .site-header .main-submenu a:hover { opacity: 1; background: #f7f7f7; }
    @media (max-width: 520px) {
        .site-header .header-inner {
            min-height: 0;
            padding: 8px 12px 7px;
            gap: 7px;
        }
        .site-header.header-layout-center .header-inner {
            gap: 7px;
        }
        .site-header .logo,
        .site-header.header-layout-center .logo,
        .site-header.header-layout-right .logo {
            width: 100%;
            max-width: none;
            white-space: nowrap;
            overflow-wrap: normal;
        }
        .site-header .logo > span:first-child {
            margin-top: 0;
            line-height: 1.05;
            white-space: nowrap;
            overflow-wrap: normal;
            font-size: clamp(14px, 5vw, 18px) !important;
            letter-spacing: .04em !important;
        }
        .site-header .logo > span:last-child {
            margin-top: 1px;
            line-height: 1.1;
            white-space: nowrap;
            overflow-wrap: normal;
            font-size: clamp(7.5px, 2.3vw, 9.5px) !important;
            letter-spacing: .06em !important;
        }
        .site-header .main-menu,
        .site-header.header-layout-center .main-menu,
        .site-header.header-layout-right .main-menu {
            width: 100%;
            flex-wrap: nowrap !important;
            justify-content: center;
            gap: clamp(14px, 4vw, 22px);
            font-size: clamp(7px, 2.15vw, 9px);
            letter-spacing: .01em;
        }
        .site-header .main-menu-item { flex: 0 1 auto; min-width: 0; }
        .site-header .main-menu-link {
            gap: 2px;
            padding: 5px 0;
            line-height: 1.1;
            white-space: nowrap;
        }
        .site-header .main-menu-arrow { font-size: 6px; }
    }
</style>

<header class="site-header header-layout-{{ $headerSettings['header_layout'] }}" style="--header-padding-top: {{ $headerSettings['header_padding_top'] }}px; --header-padding-bottom: {{ $headerSettings['header_padding_bottom'] }}px;">
    <div class="header-inner">

        <a href="{{ url('/') }}" class="logo">
            <span style="font-family:{{ $headerFamilies[$headerSettings['header_logo_font_family']] }}; font-size:{{ $headerSettings['header_logo_font_size'] }}px; font-weight:{{ $headerSettings['header_logo_font_weight'] }}; letter-spacing:{{ $headerSettings['header_logo_letter_spacing'] }}em; color:{{ $headerSettings['header_logo_color'] }};">
                {{ $headerSettings['logo'] }}
            </span>

            <span style="font-family:{{ $headerFamilies[$headerSettings['header_subtitle_font_family']] }}; font-size:{{ $headerSettings['header_subtitle_font_size'] }}px; font-weight:{{ $headerSettings['header_subtitle_font_weight'] }}; letter-spacing:{{ $headerSettings['header_subtitle_letter_spacing'] }}em; color:{{ $headerSettings['header_subtitle_color'] }};">
                {{ $headerSettings['logo_subtitle'] }}
            </span>
        </a>

        <nav class="main-menu">
            @forelse($menuItems ?? collect() as $menuItem)

                @php
                    if ($menuItem->type === "page" && $menuItem->page) {
                        $menuUrl = route("page.public", $menuItem->page);
                    } elseif ($menuItem->type === "gallery" && $menuItem->gallery) {
                        $menuUrl = route("portfolio.gallery", $menuItem->gallery);
                    } elseif ($menuItem->type === "url" && $menuItem->url) {
                        $menuUrl = $menuItem->url;
                    } else {
                        $menuUrl = "#";
                    }
                @endphp

                <div class="main-menu-item">
                    <a href="{{ $menuUrl }}" class="main-menu-link">
                        {{ $menuItem->title }}
                        @if($menuItem->children->count())
                            <span class="main-menu-arrow">▼</span>
                        @endif
                    </a>

                    @if($menuItem->children->count())
                        <ul class="main-submenu">
                            @foreach($menuItem->children as $child)

                                @php
                                    if ($child->type === "page" && $child->page) {
                                        $childUrl = route("page.public", $child->page);
                                    } elseif ($child->type === "gallery" && $child->gallery) {
                                        $childUrl = route("portfolio.gallery", $child->gallery);
                                    } elseif ($child->type === "url" && $child->url) {
                                        $childUrl = $child->url;
                                    } else {
                                        $childUrl = "#";
                                    }
                                @endphp

                                <li>
                                    <a href="{{ $childUrl }}">{{ $child->title }}</a>
                                </li>

                            @endforeach
                        </ul>
                    @endif
                </div>

            @empty

                <a href="{{ url('/') }}" class="main-menu-link">Start</a>
                <a href="{{ url('/#portfolio') }}" class="main-menu-link">Portfolio</a>
                <a href="{{ route('about') }}" class="main-menu-link">O mnie</a>
                <a href="{{ route('contact') }}" class="main-menu-link">Kontakt</a>

            @endforelse
        </nav>

    </div>
</header>
