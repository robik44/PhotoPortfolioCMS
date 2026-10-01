<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow">

    <title>{{ config("app.name", "Photo Portfolio CMS") }}</title>

    @vite(["resources/css/app.css", "resources/js/app.js"])
</head>

<body class="cms-body">
@php
    $viewSiteUrl = request()->routeIs('galleries.edit')
        ? route('portfolio.gallery', request()->route('gallery'))
        : url('/');
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

            <a href="{{ route("galleries.index") }}"
               class="cms-nav-item {{ request()->routeIs("galleries.*") ? "active" : "" }}">
                <span>▦</span>
                <span>Galerie</span>
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
                <span>Nagłówek</span>
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

</body>
</html>
