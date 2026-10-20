<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

    <title>{{ config("app.name", "Photo Portfolio CMS") }}</title>
    <link rel="icon" href="{{ asset(\App\Models\SiteSetting::where("key", "favicon")->value("value") ? "storage/".\App\Models\SiteSetting::where("key", "favicon")->value("value") : "favicon-mg.svg") }}">

    @vite(["resources/css/app.css", "resources/js/app.js"])
</head>

<body class="cms-body">
@php
    $viewSiteBaseUrl = request()->routeIs('galleries.edit')
        ? route('portfolio.gallery', request()->route('gallery'))
        : url('/');

    $viewSiteUrl = $viewSiteBaseUrl
        . (str_contains($viewSiteBaseUrl, '?') ? '&' : '?')
        . 'cms_preview=1';
@endphp

<div class="cms-shell">

    <aside class="cms-sidebar">

        <div class="cms-brand">
            <div class="cms-brand-name">MAGDA GUGAŁA</div>
            <div class="cms-brand-subtitle">FOTOGRAFIA</div>
        </div>

        <nav class="cms-nav">

            <div class="cms-nav-label">KOKPIT</div>

            <a href="{{ route("dashboard") }}"
               class="cms-nav-item {{ request()->routeIs("dashboard") ? "active" : "" }}">
                <span>⌂</span>
                <span>Kokpit</span>
            </a>


            <div class="cms-nav-label">TREŚĆ</div>

            <a href="{{ route("home-builder.edit") }}"
               class="cms-nav-item {{ request()->routeIs("home-builder.*") ? "active" : "" }}">
                <span>⌂</span>
                <span>Strona główna</span>
            </a>

            <a href="{{ route("pages.index") }}"
               class="cms-nav-item {{ request()->routeIs("pages.*") ? "active" : "" }}">
                <span>▤</span>
                <span>Strony</span>
            </a>

            @php
                $galleryCollectionsForNav = \Illuminate\Support\Facades\Schema::hasTable('gallery_collections')
                    ? \App\Models\GalleryCollection::orderBy('sort_order')->orderBy('name')->get()
                    : collect();
                $activeCollectionId = (int) request()->query('collection', 0);
                if (!$activeCollectionId && request()->route('gallery')) {
                    $activeCollectionId = (int) optional(request()->route('gallery')->collection)->id;
                }
            @endphp

            @forelse($galleryCollectionsForNav as $galleryCollectionNav)
                <a href="{{ route('galleries.index', ['collection' => $galleryCollectionNav->id]) }}"
                   class="cms-nav-item {{ request()->routeIs('galleries.*') && $activeCollectionId === (int) $galleryCollectionNav->id ? 'active' : '' }}">
                    <span>▦</span>
                    <span>{{ $galleryCollectionNav->name }}</span>
                </a>
            @empty
                <a href="{{ route('galleries.index') }}"
                   class="cms-nav-item {{ request()->routeIs('galleries.*') ? 'active' : '' }}">
                    <span>▦</span><span>Galerie</span>
                </a>
            @endforelse

            <a href="{{ route('gallery-collections.create') }}"
               class="cms-nav-item {{ request()->routeIs('gallery-collections.create') ? 'active' : '' }}">
                <span>＋</span>
                <span>Dodaj galerię</span>
            </a>

            <a href="{{ route("photos.index") }}"
               class="cms-nav-item {{ request()->routeIs("photos.*") ? "active" : "" }}">
                <span>▧</span>
                <span>Zdjęcia</span>
            </a>


            <a href="{{ route('content-pages.edit', 'o-mnie') }}" class="cms-nav-item">
                <span>○</span><span>O mnie</span>
            </a>
            <a href="{{ route('content-pages.edit', 'kontakt') }}" class="cms-nav-item">
                <span>✉</span><span>Kontakt</span>
            </a>

            <div class="cms-nav-label">MENU</div>

            <a href="{{ route("menu.index") }}"
               class="cms-nav-item {{ request()->routeIs("menu.*") ? "active" : "" }}">
                <span>☰</span>
                <span>Menu strony</span>
            </a>


            <a href="{{ route('seo.edit') }}" class="cms-nav-item {{ request()->routeIs('seo.*') ? 'active' : '' }}"><span>SEO</span></a>

            <div class="cms-nav-label">WYGLĄD</div>

            <a href="{{ route('fonts.index') }}" class="cms-nav-item {{ request()->routeIs('fonts.*') ? 'active' : '' }}">
                <span>Aa</span><span>Biblioteka czcionek</span>
            </a>

            <a href="{{ route('header-settings.edit') }}"
               class="cms-nav-item {{ request()->routeIs('header-settings.*') ? 'active' : '' }}">
                <span>▤</span>
                <span>Nagłówek — obecny</span>
            </a>

            <a href="{{ route('draft-header.edit') }}"
               class="cms-nav-item {{ request()->routeIs('draft-header.*') ? 'active' : '' }}">
                <span>◫</span><span>Nagłówek — NOWY (testowy)</span>
            </a>

            <a href="{{ route("site-settings.edit") }}"
               class="cms-nav-item {{ request()->routeIs("site-settings.*") ? "active" : "" }}">
                <span>⚙</span>
                <span>Ustawienia</span>
            </a>

            <div class="cms-nav-label">KONTO</div>

            <a href="{{ route('profile.edit') }}"
               class="cms-nav-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <span>●</span>
                <span>Moje konto</span>
            </a>

        </nav>


        <div class="cms-sidebar-bottom">

            <a href="{{ $viewSiteUrl }}"
               target="_blank"
               class="cms-nav-item">
                <span>↗</span>
                <span>Zobacz stronę</span>
            </a>

            <form method="POST" action="{{ route("logout") }}">
                @csrf

                <button type="submit" class="cms-nav-item cms-logout">
                    <span>↪</span>
                    <span>Wyloguj</span>
                </button>
            </form>

        </div>

    </aside>


    <div class="cms-main">

        <header class="cms-topbar">

            <div class="cms-topbar-left">

                <div class="cms-page-title">
                    @isset($header)
                        {{ $header }}
                    @else
                        Kokpit
                    @endisset
                </div>

            </div>


            <div class="cms-topbar-right">

                <a href="{{ $viewSiteUrl }}"
                   target="_blank"
                   class="cms-view-site">
                    Zobacz stronę ↗
                </a>

                <div class="cms-user">

                    <div class="cms-user-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? "U", 0, 1)) }}
                    </div>

                    <div class="cms-user-name">
                        {{ auth()->user()->name ?? "Użytkownik" }}
                    </div>

                </div>

            </div>

        </header>


        <main class="cms-content">

            @if(session("success"))
                <div class="cms-alert cms-alert-success">
                    {{ session("success") }}
                </div>
            @endif

            @if(session("error"))
                <div class="cms-alert cms-alert-error">
                    {{ session("error") }}
                </div>
            @endif

            @isset($slot)
                {{ $slot }}
            @else
                @yield("content")
            @endisset

        </main>

    </div>

</div>

{{-- Unified HEX entry next to native color pickers in all CMS panels. --}}
<style>
    .cms-hex-pair {
        display: inline-flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 8px;
        max-width: 100%;
        width: fit-content;
        vertical-align: middle;
        box-sizing: border-box;
    }
    .cms-hex-pair > input[type="color"] {
        width: 46px !important;
        min-width: 46px !important;
        height: 36px !important;
        flex: 0 0 46px !important;
        padding: 2px !important;
        margin: 0 !important;
        border: 1px solid #bfc5cc;
        border-radius: 5px;
        background: #fff;
        cursor: pointer;
        box-sizing: border-box;
    }
    .cms-hex-pair > .cms-hex-value {
        width: 113px !important;
        min-width: 105px !important;
        max-width: 125px !important;
        height: 36px !important;
        flex: 0 1 113px !important;
        padding: 7px 9px !important;
        margin: 0 !important;
        border: 1px solid #c7cdd6 !important;
        border-radius: 5px !important;
        background: #fff !important;
        color: #1f2937 !important;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important;
        font-size: 13px !important;
        letter-spacing: .2px;
        box-sizing: border-box;
    }
    .cms-hex-pair > .cms-hex-value:focus {
        outline: 2px solid #2563eb;
        outline-offset: 1px;
    }
    .cms-hex-pair > .cms-hex-value.cms-hex-invalid {
        border-color: #ce3838 !important;
        background: #fff6f6 !important;
    }
</style>
<script defer src="{{ asset('js/cms-color-hex.js') }}?v=1"></script>
</body>
</html>
