<x-app-layout>
    @include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
    <script src="{{ asset('js/site-typography.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/foodfoto-gallery-mosaic.css') }}?v={{ @filemtime(public_path('css/foodfoto-gallery-mosaic.css')) ?: 1 }}">
    <script src="{{ asset('js/builder-button.js') }}"></script>
    <script src="{{ asset('js/builder-auto-flow.js') }}?v={{ @filemtime(public_path('js/builder-auto-flow.js')) ?: 1 }}"></script>
    <link rel="stylesheet" href="{{ asset('css/foodfoto-process.css') }}?v={{ @filemtime(public_path('css/foodfoto-process.css')) ?: 1 }}">
    <script src="{{ asset('js/foodfoto-process.js') }}?v={{ @filemtime(public_path('js/foodfoto-process.js')) ?: 1 }}"></script>
    @php
        $builderHeaderSettings = \App\Models\SiteSetting::pluck('value', 'key')->toArray();
        $builderHeaderMenuItems = \App\Models\MenuItem::query()
            ->whereNull('parent_id')
            ->where('published', true)
            ->with([
                'page',
                'gallery',
                'children' => function ($query) {
                    $query->where('published', true)
                        ->with(['page', 'gallery'])
                        ->orderBy('sort_order');
                },
            ])
            ->orderBy('sort_order')
            ->get();

        $buttonTargets = collect([
            ['url' => url('/'), 'label' => 'START'],
            ['url' => url('/').'#portfolio', 'label' => 'Galeria na stronie głównej — przewiń'],
        ])->concat(\App\Models\Page::where('published', true)->orderBy('title')->get()->map(fn ($target) => [
            'url' => isset(\App\Support\ContentPages::PAGES[$target->slug]) ? url('/'.$target->slug) : route('page.public', $target),
            'label' => 'Strona: '.$target->title,
        ]))->concat(\App\Models\Gallery::where('published', true)->orderBy('title')->get()->map(fn ($target) => [
            'url' => route('portfolio.gallery', $target), 'label' => 'Galeria: '.$target->title,
        ]));
    @endphp
    <script>window.builderButtonTargets = @json($buttonTargets->values());</script>
    <script>
        window.FoodfotoProcess.configure(@json($siteFonts['choices']));
        window.builderTypography = window.SiteTypography.create({
            families: @json($siteFonts['families']),
            choices: @json($siteFonts['choices']),
            defaults: @json($siteFonts['defaults']),
            textTypes: @json(\App\Services\SiteFontLibrary::TEXT_BLOCKS)
        });
        window.FoodfotoProcess.attachTypography(window.builderTypography);
    </script>
    @if (!empty($preparedStaticContent))
        <p class="cms-alert">To propozycja układu na podstawie dotychczasowej treści. Publiczna strona zmieni się dopiero po zapisaniu buildera.</p>
    @endif
    <p style="margin:12px 24px;"><a href="{{ route('fonts.index') }}" target="_blank" rel="noopener">Biblioteka czcionek</a> — po dodaniu fontu zapisz pracę i odśwież edytor.</p>
    <x-slot name="header">Builder strony</x-slot>

    <div class="cms-card" style="padding:24px;" id="builder-overview">
        <h1 class="cms-dashboard-title">{{ $page->title }}</h1>
        <p id="builder-launch-status" role="status" style="margin:16px 0;">
            Edytor nie jest jeszcze gotowy. Jeśli się nie otworzy, odśwież stronę
            lub skorzystaj z poniższych linków.
        </p>
        <noscript>
            <p>Edytor wizualny wymaga włączonej obsługi JavaScript.
                Lista stron i formularz edycji pozostają dostępne.</p>
        </noscript>
        <div style="display:flex; flex-wrap:wrap; gap:12px;">
            <a href="{{ route('pages.index') }}" class="cms-button">Wróć do listy stron</a>
            <a href="{{ $editPageUrl }}" class="cms-button">Ustawienia strony</a>
            <a href="{{ $publicPageUrl }}" class="cms-button" target="_blank" rel="noopener">Podgląd strony ↗</a>
            <button id="full-visual-editor-button" type="button" class="cms-button cms-button-primary" disabled>
                Otwórz edytor wizualny
            </button>
        </div>
    </div>

@php
    $photosForBuilder = \App\Models\Photo::orderBy("sort_order")
        ->orderBy("id")
        ->get()
        ->map(function ($photo) {
            $filename = ltrim($photo->filename, "/");
            $storagePath = str_starts_with($filename, "photos/") ? $filename : "photos/" . $filename;

            return [
                "id" => $photo->id,
                "title" => $photo->title,
                "alt" => $photo->alt,
                "thumbnail_url" => $photo->thumbnailUrl(),
                "filename" => $filename,
                "url" => asset("storage/" . $storagePath),
            ];
        })
        ->values()
        ->all();
@endphp

<style>

    .fve-banner-draft-settings { position:relative; flex-shrink:0; }
    #fve-banner-settings-panel[hidden] { display:none!important; }
    #fve-banner-settings-panel { position:absolute;top:calc(100% + 12px);left:0;z-index:120;width:min(390px,88vw);max-height:calc(100vh - 120px);overflow:auto;background:#fff;box-shadow:0 12px 35px #0003;border:1px solid #d6ded2;border-radius:12px;padding:20px; }
    #fve-page-banner-form { display:flex;flex-direction:column;gap:12px;font:13px/1.4 Arial,sans-serif; }
    #fve-page-banner-form strong { font-size:16px; }
    #fve-page-banner-form p { margin:0;color:#536054;font-size:12px; }
    #fve-page-banner-form label { display:flex;flex-direction:column;gap:5px;font-weight:600; }
    #fve-page-banner-form input:not([type=checkbox]),#fve-page-banner-form textarea { width:100%;padding:9px;border:1px solid #cbd3ca;border-radius:5px;font:14px Arial,sans-serif;box-sizing:border-box; }
    #fve-page-banner-form .fve-banner-reset { flex-direction:row;align-items:center;font-weight:400; }
    .fve-banner-form-actions { display:flex;gap:8px;flex-wrap:wrap; }
    #fve-page-banner-save-status { min-height:16px;color:#31573e; }
    /* FULL VISUAL PAGE EDITOR */

    #full-visual-editor {
        position: fixed;
        inset: 0;
        z-index: 99999;
        display: none;
        flex-direction: column;
        background: #e9e9e9;
    }

    #full-visual-editor.open {
        display: flex;
    }

    .fve-topbar {
        height: 64px;
        flex: 0 0 64px;
        background: #fff;
        border-bottom: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 18px;
        box-sizing: border-box;
    }

    .fve-topbar-left,
    .fve-topbar-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .fve-title {
        font-size: 15px;
        font-weight: 600;
    }

    .fve-button {
        border: 1px solid #d4d4d4;
        background: #fff;
        color: #222;
        border-radius: 6px;
        padding: 9px 13px;
        cursor: pointer;
        font-size: 13px;
    }

    .fve-button.dark {
        background: #171717;
        color: #fff;
        border-color: #171717;
    }

    .fve-button:hover {
        opacity: .85;
    }

    .fve-workspace {
        flex: 1;
        min-height: 0;
        display: grid;
        grid-template-columns: 220px minmax(500px, 1fr) 300px;
    }

    .fve-sidebar {
        background: #fff;
        border-right: 1px solid #ddd;
        overflow-y: auto;
        padding: 18px;
        box-sizing: border-box;
    }

    .fve-sidebar.right {
        border-right: 0;
        border-left: 1px solid #ddd;
    }

    .fve-sidebar-title {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
        color: #777;
        text-transform: uppercase;
        margin-bottom: 15px;
    }

    .fve-tool {
        width: 100%;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 6px;
        padding: 12px;
        text-align: left;
        margin-bottom: 8px;
        cursor: pointer;
        font-size: 13px;
    }

    .fve-tool:hover {
        background: #f7f7f7;
    }

    .fve-stage {
        position: relative;
        overflow: auto;
        padding: 50px;
        box-sizing: border-box;
        background:
            linear-gradient(45deg, #eeeeee 25%, transparent 25%),
            linear-gradient(-45deg, #eeeeee 25%, transparent 25%),
            linear-gradient(45deg, transparent 75%, #eeeeee 75%),
            linear-gradient(-45deg, transparent 75%, #eeeeee 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0;
    }

    .fve-page-wrap {
        width: 1400px;
        margin: 0 auto;
        transform-origin: top center;
    }

    .fve-page {
        width: 1400px;
        min-height: 900px;
        background: #fff;
        box-shadow: 0 12px 40px rgba(0,0,0,.18);
        position: relative;
        overflow: hidden;
    }

    .fve-header {
        height: 96px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 55px;
        box-sizing: border-box;
        border-bottom: 1px solid #eee;
        background: #fff;
    }

    .fve-logo {
        font-size: 22px;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .fve-logo-subtitle {
        margin-top: 5px;
        font-size: 10px;
        letter-spacing: .18em;
        color: #777;
    }

    .fve-menu {
        display: flex;
        align-items: center;
        gap: 28px;
        font-size: 12px;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .fve-public-header-preview {
        position: relative;
        z-index: 5000;
    }

    .fve-public-header-preview a,
    .fve-public-footer-preview a {
        pointer-events: none;
        cursor: default;
    }

    .fve-content {
        position: relative;
        min-height: 900px;
        background: #fff;
        padding-bottom: 0;
    }

    .fve-element {
        position: absolute;
        box-sizing: border-box;
        cursor: move;
        min-width: 40px;
        user-select: none;
        margin: 0;
    }

    .fve-element.selected {
        outline: 2px solid #111;
        outline-offset: 3px;
    }

    .fve-element.group-selected:not(.primary-selected) {
        outline: 2px solid #6b7280;
        outline-offset: 3px;
    }

    .fve-ruler-top {
        position: relative;
        height: 28px;
        margin-left: 28px;
        width: 1400px;
        background: #f7f7f7;
        border: 1px solid #d8d8d8;
        border-bottom: 0;
        overflow: hidden;
        box-sizing: border-box;
        font: 10px/1 Arial, sans-serif;
        color: #666;
    }

    .fve-ruler-left {
        position: absolute;
        top: 28px;
        left: 0;
        width: 28px;
        height: calc(100% - 28px);
        background: #f7f7f7;
        border: 1px solid #d8d8d8;
        border-right: 0;
        overflow: hidden;
        box-sizing: border-box;
        font: 10px/1 Arial, sans-serif;
        color: #666;
        z-index: 5;
    }

    .fve-ruler-canvas {
        position: relative;
        margin-left: 28px;
    }

    .fve-ruler-tick {
        position: absolute;
        box-sizing: border-box;
        pointer-events: none;
    }

    .fve-ruler-top .fve-ruler-tick {
        bottom: 0;
        border-left: 1px solid #aaa;
        height: 7px;
    }

    .fve-ruler-top .fve-ruler-tick.major {
        height: 13px;
    }

    .fve-ruler-left .fve-ruler-tick {
        right: 0;
        border-top: 1px solid #aaa;
        width: 7px;
    }

    .fve-ruler-left .fve-ruler-tick.major {
        width: 13px;
    }

    .fve-ruler-label {
        position: absolute;
        font-size: 9px;
        color: #777;
        pointer-events: none;
    }

    .fve-ruler-top .fve-ruler-label {
        top: 3px;
        transform: translateX(3px);
    }

    .fve-ruler-left .fve-ruler-label {
        right: 14px;
        transform: translateY(3px) rotate(-90deg);
        transform-origin: right top;
    }

    .fve-snap-guide {
        position: absolute;
        z-index: 999999;
        pointer-events: none;
        display: none;
        background: #d12c77;
    }

    .fve-snap-guide.vertical {
        top: 0;
        bottom: 0;
        width: 1px;
    }

    .fve-snap-guide.horizontal {
        left: 0;
        right: 0;
        height: 1px;
    }

    .fve-resize-handle {
        position: absolute;
        right: -8px;
        bottom: -8px;
        width: 16px;
        height: 16px;
        border: 2px solid #fff;
        background: #171717;
        box-shadow: 0 0 0 1px rgba(0,0,0,.35);
        border-radius: 3px;
        cursor: nwse-resize;
        display: none;
        z-index: 1000;
    }

    .fve-element.primary-selected .fve-resize-handle {
        display: block;
    }

    .fve-element > div,
    .fve-element > h1,
    .fve-element > h2,
    .fve-element > h3 {
        max-width: 100%;
        overflow-wrap: anywhere;
        white-space: normal;
    }

    .fve-element:hover {
        outline: 1px dashed #777;
        outline-offset: 2px;
    }

    .fve-element img {
        display: block;
        width: 100%;
        height: auto;
    }

    .fve-delete {
        position: absolute;
        right: -12px;
        top: -12px;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        border: 0;
        background: #171717;
        color: #fff;
        cursor: pointer;
        display: none;
        z-index: 999;
    }

    .fve-element.primary-selected .fve-delete {
        display: block;
    }

    .fve-field {
        margin-bottom: 16px;
    }

    .fve-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #555;
    }

    .fve-field input,
    .fve-field textarea,
    .fve-field select {
        width: 100%;
        box-sizing: border-box;
        padding: 9px 10px;
        border: 1px solid #d4d4d4;
        border-radius: 5px;
        background: #fff;
        font-size: 13px;
    }

    .fve-field textarea {
        min-height: 120px;
        resize: vertical;
    }

    /* Keep the automatic layout control readable in the narrow right sidebar.
       Ordinary typography inputs are width:100%; checkboxes must not be. */
    #full-visual-editor .fve-auto-flow-control {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 12px 10px;
        margin: 4px 0 0;
        border: 1px solid #d4d4d4;
        border-radius: 6px;
        background: #fafafa;
        cursor: pointer;
        color: #202020;
        font-size: 13px;
        font-weight: 650;
        line-height: 1.35;
        white-space: normal;
        overflow-wrap: break-word;
    }
    #full-visual-editor .fve-auto-flow-control input[type="checkbox"] {
        appearance: auto;
        -webkit-appearance: checkbox;
        display: block;
        flex: 0 0 19px;
        width: 19px !important;
        min-width: 19px;
        max-width: 19px;
        height: 19px;
        padding: 0;
        margin: 0;
        border-radius: 3px;
        accent-color: #202020;
        cursor: pointer;
    }
    #full-visual-editor .fve-auto-flow-control span {
        display: block;
        flex: 1 1 auto;
        min-width: 0;
    }
    #full-visual-editor .fve-auto-flow-control:focus-within {
        outline: 2px solid #838383;
        outline-offset: 2px;
    }

    .fve-zoom {
        min-width: 55px;
        text-align: center;
        font-size: 12px;
    }

    @media (max-width: 1100px) {
        .fve-workspace {
            grid-template-columns: 180px minmax(400px, 1fr) 260px;
        }

        .fve-page-wrap {
            transform-origin: top left;
        }
    }
</style>

<div id="full-visual-editor">

    <div class="fve-topbar">

        <div class="fve-topbar-left">
@php
  $headerVariantPageId = ($isHomeBuilder ?? false) ? null : ($page->id ?? null);
  $headerVariantKey = $headerVariantPageId ? 'draft_page_header_variant_'.$headerVariantPageId : 'draft_page_header_variant_home';
  $headerVariantValue = \App\Models\SiteSetting::where('key', $headerVariantKey)->value('value') ?: (($isHomeBuilder ?? false) ? 'full' : 'simple');
  $pageBannerRaw = $headerVariantPageId
    ? \App\Models\SiteSetting::where('key', 'draft_page_banner_'.$headerVariantPageId)->value('value')
    : null;
  $pageBannerDecoded = is_string($pageBannerRaw) ? json_decode($pageBannerRaw, true) : null;
  $pageBannerDraft = is_array($pageBannerDecoded) ? $pageBannerDecoded : [];
  $draftPreviewImage = ($pageBannerDraft['photo'] ?? null)
    ?: \App\Models\SiteSetting::where('key', 'draft_header_preview_image')->value('value');
@endphp
<div class="fve-header-choice" style="display:flex;align-items:center;gap:8px;padding:6px 12px;background:#edf1eb;border-radius:7px">
<label for="fve-header-variant" style="font-size:12px;font-weight:600;color:#233a2b">Nagłówek:</label>
<select id="fve-header-variant" style="padding:7px;border:1px solid #c7d1c4;border-radius:5px;background:#fff;font-size:12px">
<option value="simple" @selected($headerVariantValue === 'simple')>Tylko logo i menu</option>
<option value="full" @selected($headerVariantValue === 'full')>Pełny — z banerem</option>
</select>
<span id="fve-header-variant-status" style="font-size:11px;color:#426049">Wersja testowa</span>
</div>


@if($isHomeBuilder ?? false)
<a href="{{ route('draft-header.preview') }}" class="fve-button" style="text-decoration:none;white-space:nowrap" target="_blank" rel="noopener">Edytuj logo i menu ↗</a>
<a href="{{ route('draft-header.compact-editor') }}" class="fve-button" style="text-decoration:none;white-space:nowrap" target="_blank" rel="noopener">Edytuj wspólny nagłówek bez zdjęcia ↗</a>
<a href="{{ route('home-hero-draft.edit') }}" class="fve-button" style="text-decoration:none;white-space:nowrap" target="_blank" rel="noopener">Edytuj roboczy baner ↗</a>
<a href="{{ route('draft-header.review') }}" class="fve-button" style="text-decoration:none;white-space:nowrap" target="_blank" rel="noopener">Przegląd publikacji ↗</a>
@endif
@if(!($isHomeBuilder ?? false))
<div class="fve-banner-draft-settings">
<button type="button" class="fve-button" id="fve-banner-settings-toggle" aria-expanded="false" aria-controls="fve-banner-settings-panel">Edytuj baner</button>
<div id="fve-banner-settings-panel" hidden>
<form id="fve-page-banner-form" action="{{ route('draft-page-banner.save', $page) }}" method="post" enctype="multipart/form-data">
@csrf
<strong>Baner tej podstrony — wersja testowa</strong>
<p>Zmiany dotyczą tylko strony „{{ $page->title }}”. Logo i menu pozostają wspólne.</p>
<label>Tekst nad tytułem
<input type="text" name="eyebrow" maxlength="120" value="{{ $pageBannerDraft['eyebrow'] ?? ($builderHeaderSettings['draft_header_eyebrow'] ?? 'APETYT NA PIĘKNE HISTORIE') }}">
</label>
<label>Tytuł
<textarea name="title" rows="2" maxlength="180">{{ $pageBannerDraft['title'] ?? ($builderHeaderSettings['draft_header_hero_title'] ?? "Fotografia\ni stylizacja żywności") }}</textarea>
</label>
<label>Opis
<textarea name="description" rows="3" maxlength="500">{{ $pageBannerDraft['description'] ?? ($builderHeaderSettings['draft_header_hero_description'] ?? 'Tworzę apetyczne obrazy, które budują marki, opowiadają historie i inspirują do dobrego jedzenia.') }}</textarea>
</label>
<label>Tekst zielonego przycisku
<input type="text" name="button" maxlength="80" value="{{ $pageBannerDraft['button'] ?? ($builderHeaderSettings['draft_header_cta_text'] ?? 'ZOBACZ PORTFOLIO') }}">
</label>
<label>Zdjęcie banera (JPG, PNG lub WebP, do 8 MB)
<input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
</label>
<label class="fve-banner-reset"><input type="checkbox" name="reset_photo" value="1"> Użyj ponownie wspólnego zdjęcia</label>
<div class="fve-banner-form-actions">
<button type="submit" class="fve-button dark">Zapisz roboczy baner</button>
<button type="button" class="fve-button" id="fve-banner-panel-close">Zamknij</button>
</div>
<output id="fve-page-banner-save-status" role="status"></output>
</form></div></div>
@endif

            <button
                type="button"
                class="fve-button"
                id="fve-close"
            >
                ← Zamknij
            </button>

            <div class="fve-title">
                Edycja wizualna — {{ $page->title }}
            </div>

        </div>

        <div class="fve-topbar-right">

            <button
                type="button"
                class="fve-button"
                id="fve-zoom-out"
            >
                −
            </button>

            <div
                class="fve-zoom"
                id="fve-zoom-label"
            >
                70%
            </div>

            <button
                type="button"
                class="fve-button"
                id="fve-zoom-in"
            >
                +
            </button>

            <button
                type="button"
                class="fve-button"
                id="fve-fit"
            >
                Dopasuj
            </button>

            <button
                type="button"
                class="fve-button dark"
                id="fve-save"
            >
                Zapisz
            </button>

        </div>

    </div>

    <div class="fve-workspace">

        <aside class="fve-sidebar">

            <div class="fve-sidebar-title">
                Elementy
            </div>

            <button class="fve-tool" data-fve-add="text">
                + Tekst
            </button>

            <button class="fve-tool" data-fve-add="heading">
                + Nagłówek
            </button>

            <button class="fve-tool" data-fve-add="image">
                + Zdjęcie
            </button>

            <button class="fve-tool" data-fve-add="thumbnail_gallery">+ Galeria miniaturek</button>

            <button class="fve-tool" data-fve-add="gallery">
                + Galeria
            </button>

            <button class="fve-tool" data-fve-add="button">
                + Przycisk
            </button>

            <button class="fve-tool" data-fve-add="separator">
                + Separator
            </button>

            <div class="fve-sidebar-title" style="margin-top:22px;">
                Gotowe układy
            </div>
            <button type="button" class="fve-tool" data-fve-preset="image-text">
                + Zdjęcie i tekst
            </button>
            <button type="button" class="fve-tool" data-fve-preset="text-image">
                + Tekst i zdjęcie
            </button>
            <button type="button" class="fve-tool" data-fve-preset="three-steps">
                + Trzy etapy
            </button>
            <button type="button" class="fve-tool" data-fve-preset="process-collaboration">
                + Proces współpracy — 3 etapy
            </button>
            <button type="button" class="fve-tool" data-fve-preset="contact-banner">
                + Baner kontaktowy
            </button>
            <button type="button" class="fve-tool" data-fve-preset="contact-finale">
                + Finał strony — zdjęcie i kontakt
            </button>
            @if($isHomeBuilder ?? false)
                <button type="button" class="fve-tool" data-fve-add="gallery_mosaic">
                    + Mozaika galerii FoodFoto (6 kart)
                </button>
                <button type="button" class="fve-tool" data-fve-preset="foodfoto-home">
                    + Kompozycja FoodFoto
                </button>
                <small style="display:block;padding:8px 10px;color:#666;line-height:1.5">
                    Dodaje nowe sekcje na końcu strony bez zmiany obecnych galerii i treści.
                    Przed publikacją wybierz zdjęcia i sprawdź układ.
                </small>
            @endif

        </aside>

        <main class="fve-stage" id="fve-stage">

            <div class="fve-page-wrap" id="fve-page-wrap">
                <div class="fve-ruler-top" id="fve-ruler-top"></div>
                <div class="fve-ruler-left" id="fve-ruler-left"></div>
                <div class="fve-ruler-canvas">
                <div class="fve-page" id="fve-page">

                    @if($isHomeBuilder ?? false)
                    @php
                        // Preview-only. The published homepage elements remain stored
                        // untouched in the existing PageBuilder and can be restored.
                        $homeDraftContent = \App\Models\PageBuilder::query()
                            ->whereNull('page_id')
                            ->where('type', 'home_hero_draft')
                            ->first()?->content ?? [];
                        $homeDraftSections = collect($homeDraftContent['sections'] ?? [])->keyBy('id');
                        $pageBannerDraft = [
                            'eyebrow' => $homeDraftSections->get('hero-eyebrow')['content'] ?? ($builderHeaderSettings['draft_header_eyebrow'] ?? 'APETYT NA PIĘKNE HISTORIE'),
                            'title' => $homeDraftSections->get('hero-heading')['content'] ?? ($builderHeaderSettings['draft_header_hero_title'] ?? "Fotografia\ni stylizacja żywności"),
                            'description' => $homeDraftSections->get('hero-text')['content'] ?? ($builderHeaderSettings['draft_header_hero_description'] ?? ''),
                            'button' => $homeDraftSections->get('hero-portfolio-cta')['content'] ?? ($builderHeaderSettings['draft_header_cta_text'] ?? 'ZOBACZ PORTFOLIO'),
                        ];
                        $draftPreviewImage = ($homeDraftContent['preview_image'] ?? null)
                            ?: ($builderHeaderSettings['draft_header_preview_image'] ?? null);
                    @endphp
                    <div class="fve-home-draft-hero" id="fve-home-draft-hero" aria-label="Roboczy nagłówek i baner strony głównej">
                        @include('admin.components.draft-shared-header')
                    </div>
                    <style>
                        /* Replace only the builder's visual presentation.
                           Legacy hero records still exist and retain their
                           geometry in the editor data and public database. */
                        #fve-page .fve-home-draft-hero {
                            position:absolute;top:0;left:0;width:100%;z-index:4900;
                            pointer-events:none;overflow:hidden;
                        }
                        #fve-page {
                            --ff-builder-hero-height:{{ max(480,min(900,(int)($builderHeaderSettings['draft_header_hero_height'] ?? 650))) }}px;
                        }
                        #fve-page .fve-home-draft-hero .ff-shared-stage.ff-full {
                            height:var(--ff-builder-hero-height);
                        }
                        /* Existing sections retain their original positions inside
                           a canvas designed for a 540px legacy hero. Only move the
                           canvas as a whole so this preview follows the full hero
                           height without editing or re-saving section geometry. */
                        #fve-page .fve-content {
                            margin-top:calc(var(--ff-builder-hero-height) - 540px);
                        }
                        #fve-page .fve-home-draft-hero .ff-shared-brand,
                        #fve-page .fve-home-draft-hero .ff-shared-menu,
                        #fve-page .fve-home-draft-hero .ff-shared-hero {
                            pointer-events:auto;
                            cursor:pointer;
                        }
                        #fve-page .fve-home-draft-hero .ff-shared-brand:hover,
                        #fve-page .fve-home-draft-hero .ff-shared-menu:hover,
                        #fve-page .fve-home-draft-hero .ff-shared-hero:hover {
                            outline:2px dashed #5a7b51;
                            outline-offset:3px;
                        }
                        #fve-page .fve-content .fve-element[data-id="hero-image"],
                        #fve-page .fve-content .fve-element[data-id="hero-heading"],
                        #fve-page .fve-content .fve-element[data-id="hero-text"],
                        #fve-page .fve-content .fve-element[data-id="hero-eyebrow"],
                        #fve-page .fve-content .fve-element[data-id="hero-portfolio-cta"] {
                            visibility:hidden!important;pointer-events:none!important;
                        }
                    </style>
                    <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const overlay = document.getElementById('fve-home-draft-hero');
                        if (!overlay) return;
                        const links = [
                            ['.ff-shared-brand', '{{ route('draft-header.preview') }}', 'Otwórz centralny edytor logo i nagłówka'],
                            ['.ff-shared-menu', '{{ route('draft-header.preview') }}', 'Otwórz centralny edytor menu'],
                            ['.ff-shared-hero', '{{ route('home-hero-draft.edit') }}', 'Otwórz edytor banera strony głównej']
                        ];
                        links.forEach(([selector,url,label]) => {
                            const el=overlay.querySelector(selector);
                            if (!el) return;
                            el.title=label;
                            el.setAttribute('role','button');
                            el.setAttribute('tabindex','0');
                            const open=()=>window.open(url,'_blank','noopener');
                            el.addEventListener('click',open);
                            el.addEventListener('keydown',event=>{
                                if(event.key==='Enter'||event.key===' '){event.preventDefault();open();}
                            });
                        });
                    });
                    </script>
                    @else
                        @include('admin.components.draft-shared-header')
                    @endif

                    <main
                        class="fve-content"
                        id="fve-content"
                    ></main>

                    <div class="fve-public-footer-preview">
                        @include('components.site-footer', ['settings' => $builderHeaderSettings])
                    </div>

                </div>
                </div>

            </div>

        </main>

        <aside class="fve-sidebar right">

            <div class="fve-sidebar-title">
                Właściwości
            </div>

            <div id="fve-properties">

                <p style="color:#888;line-height:1.6;font-size:13px;">
                    Kliknij element na stronie, aby go edytować. Shift/Cmd/Ctrl + klik zaznacza kilka elementów.
                </p>

            </div>

        </aside>

    </div>

</div>

<script>
/* FULL VISUAL PAGE EDITOR */

document.addEventListener("DOMContentLoaded", function () {

    @php
        $variantIsHome = (bool) ($isHomeBuilder ?? false);
        $variantBackground = $draftPreviewImage
            ? "linear-gradient(90deg,rgba(250,249,246,.87),rgba(250,249,246,.3),transparent),url('".asset('storage/'.$draftPreviewImage)."')"
            : 'none';
    @endphp
    const variantIsHome = @json($variantIsHome);
    const variantBackground = @json($variantBackground);
    const variantPageId = @json($headerVariantPageId);
    const variantSaveUrl = @json(route('draft-page-header-variant.save'));
    const headerChoice = document.getElementById('fve-header-variant');
    if (headerChoice) headerChoice.addEventListener('change', async () => {
        const status = document.getElementById('fve-header-variant-status');
        const selection = headerChoice.value;
        const headerPreview = document.getElementById('fve-header-variant-preview');
        const stage = headerPreview?.querySelector('.ff-shared-stage');
        if (stage) {
            stage.classList.toggle('ff-full', selection === 'full');
            stage.classList.toggle('ff-simple', selection !== 'full');
        }
        status.textContent = 'Zapisywanie…';
        try {
            const response = await fetch(variantSaveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({page_id: variantPageId, variant: selection})
            });
            if (!response.ok) throw Error('HTTP ' + response.status);
            status.textContent = 'Wybór zapisany ✓';
        } catch (error) {
            status.textContent = 'Błąd zapisu: ' + error.message;
        }
    });

    const bannerToggle = document.getElementById('fve-banner-settings-toggle');
    const bannerPanel = document.getElementById('fve-banner-settings-panel');
    const bannerForm = document.getElementById('fve-page-banner-form');
    if (bannerToggle && bannerPanel && bannerForm) {
        const setOpen = (open) => {
            bannerPanel.hidden = !open;
            bannerToggle.setAttribute('aria-expanded', String(open));
        };
        bannerToggle.addEventListener('click', () => setOpen(bannerPanel.hidden));
        document.getElementById('fve-banner-panel-close')?.addEventListener('click', () => setOpen(false));
        bannerForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const resultStatus = document.getElementById('fve-page-banner-save-status');
            const submit = bannerForm.querySelector('button[type="submit"]');
            resultStatus.textContent = 'Zapisywanie wersji testowej…';
            submit.disabled = true;
            try {
                const response = await fetch(bannerForm.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Accept': 'application/json'},
                    body: new FormData(bannerForm)
                });
                if (!response.ok) {
                    const error = await response.json().catch(() => ({}));
                    const message = Object.values(error.errors || {}).flat()[0] || error.message || ('HTTP ' + response.status);
                    throw Error(message);
                }
                const result = await response.json();
                const preview = document.getElementById('fve-header-variant-preview');
                if (preview) {
                    const map = [['eyebrow','.ff-eyebrow'],['title','.ff-shared-hero h1'],['description','.ff-shared-hero p']];
                    for (const [field,selector] of map) {
                        const node = preview.querySelector(selector);
                        if (node) node.textContent = result.draft[field] ?? '';
                    }
                    const cta = preview.querySelector('.ff-cta');
                    if (cta) cta.textContent = (result.draft.button ?? '') + '　→';
                    const stage = preview.querySelector('.ff-shared-stage');
                    let photo = stage?.querySelector('.ff-shared-photo');
                    if (result.photo_url && stage) {
                        if (!photo) {
                            photo = document.createElement('img');
                            photo.className = 'ff-shared-photo';
                            photo.alt = '';
                            stage.prepend(photo);
                        }
                        photo.src = result.photo_url;
                    } else if (photo) photo.remove();
                }
                bannerForm.querySelector('input[name="photo"]').value = '';
                bannerForm.querySelector('input[name="reset_photo"]').checked = false;
                resultStatus.textContent = 'Zapisano wersję roboczą ✓ — strona publiczna bez zmian';
            } catch (error) {
                resultStatus.textContent = 'Nie zapisano: ' + error.message;
            } finally {
                submit.disabled = false;
            }
        });
    }

    const openButton =
        document.getElementById("full-visual-editor-button");

    const launchStatus =
        document.getElementById("builder-launch-status");

    const editor =
        document.getElementById("full-visual-editor");

    const closeButton =
        document.getElementById("fve-close");

    const page =
        document.getElementById("fve-page");

    const content =
        document.getElementById("fve-content");

    const properties =
        document.getElementById("fve-properties");

    const zoomLabel =
        document.getElementById("fve-zoom-label");

    const pageWrap =
        document.getElementById("fve-page-wrap");

    let zoom = 0.70;
    let selected = null;
    const selectedIds = new Set();
    const rulerTop = document.getElementById("fve-ruler-top");
    const rulerLeft = document.getElementById("fve-ruler-left");
    const isHomeBuilder = @json($isHomeBuilder ?? false);

    let data = @json($builder->content ?? [
        "version" => 1,
        "settings" => [
            "background_color" => "#ffffff"
        ],
        "sections" => []
    ]);

    const photos =
        @json($photosForBuilder ?? []);

    @php
        $galleriesForEditor = \App\Models\Gallery::with(["photos", "collection"])
            ->orderBy("gallery_collection_id")
            ->orderBy("sort_order")
            ->orderBy("title")
            ->get()
            ->map(function ($gallery) {
                $cover = $gallery->photos
                    ->first(fn ($photo) => (bool) $photo->pivot->is_cover)
                    ?? $gallery->photos->first();

                return [
                    "id" => $gallery->id,
                    "title" => $gallery->title,
                    "published" => (bool) $gallery->published,
                    "description" => $gallery->description,
                    "gallery_collection_id" => $gallery->gallery_collection_id,
                    "gallery_collection_name" => optional($gallery->collection)->name,
                    "cover_url" => $cover
                        ? asset("storage/photos/" . basename($cover->filename))
                        : null,
                    "url" => route("portfolio.gallery", $gallery),
                    "photos" => $gallery->photos->map(fn ($photo) => ["id" => $photo->id, "title" => $photo->title, "alt" => $photo->alt, "cover_url" => $photo->imageUrl()])->values()->all(),
                ];
            })
            ->values()
            ->all();
    @endphp

    const galleries =
        @json($galleriesForEditor);

    const galleryCollections =
        @json(\App\Models\GalleryCollection::orderBy('sort_order')->orderBy('name')->get(['id','name'])->values());
    @php
        $mosaicPageTargets = \App\Models\Page::where('published', true)->orderBy('title')->get()
            ->map(fn ($target) => [
                'title' => $target->title,
                'url' => isset(\App\Support\ContentPages::PAGES[$target->slug])
                    ? url('/'.$target->slug)
                    : route('page.public', $target),
                'photo_id' => $target->featured_photo_id,
            ])->filter(fn ($target) => preg_match('/fotograf|styliz|opakowan|food\s*styling/i', $target['title']))
            ->values()->all();
    @endphp
    const mosaicPages = @json($mosaicPageTargets);


    @php
        $menuItemsForEditor = \App\Models\MenuItem::query()
            ->whereNull("parent_id")
            ->where("published", true)
            ->with(["page", "gallery"])
            ->orderBy("sort_order")
            ->get()
            ->map(function ($item) {
                return [
                    "title" => $item->title,
                    "type" => $item->type,
                    "url" => $item->url,
                    "page_slug" => optional($item->page)->slug,
                ];
            })
            ->values()
            ->all();
    @endphp

    const settings =
        @json(\App\Models\SiteSetting::pluck("value", "key")->toArray());

    const menuItems =
        @json($menuItemsForEditor);

    if (!data.settings) {
        data.settings = {};
    }

    if (!Array.isArray(data.sections)) {
        data.sections = [];
    }

    function syncSelection(primaryId = null) {
        if (primaryId !== null) {
            selected = primaryId;
            selectedIds.add(primaryId);
        }

        if (selected && !selectedIds.has(selected)) {
            selected = selectedIds.size ? Array.from(selectedIds)[0] : null;
        }
    }

    function selectOnly(id) {
        selectedIds.clear();
        selectedIds.add(id);
        selected = id;
    }

    function toggleSelection(id) {
        if (selectedIds.has(id)) {
            selectedIds.delete(id);
            if (selected === id) selected = selectedIds.size ? Array.from(selectedIds)[0] : null;
        } else {
            selectedIds.add(id);
            selected = id;
        }
    }

    function selectedItems() {
        return data.sections.filter(item => selectedIds.has(item.id));
    }

    function renderRulers() {
        if (!rulerTop || !rulerLeft) return;
        rulerTop.innerHTML = "";
        rulerLeft.innerHTML = "";

        for (let x = 0; x <= 1400; x += 20) {
            const tick = document.createElement("span");
            tick.className = "fve-ruler-tick" + (x % 100 === 0 ? " major" : "");
            tick.style.left = x + "px";
            rulerTop.appendChild(tick);

            if (x % 100 === 0 && x < 1400) {
                const label = document.createElement("span");
                label.className = "fve-ruler-label";
                label.style.left = x + "px";
                label.textContent = x;
                rulerTop.appendChild(label);
            }
        }

        const height = Math.max(900, content.offsetHeight || 900);
        rulerLeft.style.height = height + "px";
        for (let y = 0; y <= height; y += 20) {
            const tick = document.createElement("span");
            tick.className = "fve-ruler-tick" + (y % 100 === 0 ? " major" : "");
            tick.style.top = y + "px";
            rulerLeft.appendChild(tick);

            if (y % 100 === 0) {
                const label = document.createElement("span");
                label.className = "fve-ruler-label";
                label.style.top = y + "px";
                label.textContent = y;
                rulerLeft.appendChild(label);
            }
        }
    }

    function ensureSnapGuides() {
        let vertical = content.querySelector(".fve-snap-guide.vertical");
        let horizontal = content.querySelector(".fve-snap-guide.horizontal");

        if (!vertical) {
            vertical = document.createElement("div");
            vertical.className = "fve-snap-guide vertical";
            content.appendChild(vertical);
        }
        if (!horizontal) {
            horizontal = document.createElement("div");
            horizontal.className = "fve-snap-guide horizontal";
            content.appendChild(horizontal);
        }

        return { vertical, horizontal };
    }

    function hideSnapGuides() {
        const guides = ensureSnapGuides();
        guides.vertical.style.display = "none";
        guides.horizontal.style.display = "none";
    }

    function elementGeometry(item) {
        const node = content.querySelector('.fve-element[data-id="' + CSS.escape(item.id) + '"]');
        const x = (Number(item.position_x) || 0) / 100 * 1400;
        const y = node ? node.offsetTop : (Number(item.position_y) || 0) / 100 * 900;
        const width = (Number(item.element_width) || 0) / 100 * 1400;
        const height = node ? node.getBoundingClientRect().height / zoom : Math.max(24, Number(item.element_height) || 24);
        return { x, y, width, height, right: x + width, bottom: y + height, cx: x + width / 2, cy: y + height / 2 };
    }

    function snapGroup(groupIds, proposedDxPx, proposedDyPx, starts, fixedTargets = null) {
        const threshold = 7;
        const moving = groupIds.map(id => {
            const start = starts.get(id);
            return {
                id,
                x: start.x + proposedDxPx,
                y: start.y + proposedDyPx,
                width: start.width,
                height: start.height,
            };
        });

        const bounds = {
            x: Math.min(...moving.map(g => g.x)),
            y: Math.min(...moving.map(g => g.y)),
            right: Math.max(...moving.map(g => g.x + g.width)),
            bottom: Math.max(...moving.map(g => g.y + g.height)),
        };
        bounds.cx = (bounds.x + bounds.right) / 2;
        bounds.cy = (bounds.y + bounds.bottom) / 2;

        const xTargets = fixedTargets?.x || [0, 700, 1400];
        const yTargets = fixedTargets?.y || [0];
        if (!fixedTargets) {
            data.sections.forEach(other => {
                if (groupIds.includes(other.id)) return;
                const g = elementGeometry(other);
                xTargets.push(g.x, g.cx, g.right);
                yTargets.push(g.y, g.cy, g.bottom);
            });
        }

        let bestX = null;
        let bestY = null;
        [bounds.x, bounds.cx, bounds.right].forEach(edge => {
            xTargets.forEach(target => {
                const delta = target - edge;
                if (Math.abs(delta) <= threshold && (!bestX || Math.abs(delta) < Math.abs(bestX.delta))) {
                    bestX = { delta, target };
                }
            });
        });
        [bounds.y, bounds.cy, bounds.bottom].forEach(edge => {
            yTargets.forEach(target => {
                const delta = target - edge;
                if (Math.abs(delta) <= threshold && (!bestY || Math.abs(delta) < Math.abs(bestY.delta))) {
                    bestY = { delta, target };
                }
            });
        });

        const guides = ensureSnapGuides();
        if (bestX) {
            guides.vertical.style.left = bestX.target + "px";
            guides.vertical.style.display = "block";
        } else {
            guides.vertical.style.display = "none";
        }
        if (bestY) {
            guides.horizontal.style.top = bestY.target + "px";
            guides.horizontal.style.display = "block";
        } else {
            guides.horizontal.style.display = "none";
        }

        return {
            dx: proposedDxPx + (bestX ? bestX.delta : 0),
            dy: proposedDyPx + (bestY ? bestY.delta : 0),
        };
    }

    function ensureItem(item, index) {

        if (!item.style) {
            item.style = {};
        }

        if (!item.style.color) {
            item.style.color = "#222222";
        }

        if (!item.style.font_size) {
            item.style.font_size =
                item.type === "heading" ? 42 : 18;
        }

        if (!item.style.font_weight) {
            item.style.font_weight = 400;
        }

        if (!item.style.text_align) {
            item.style.text_align = "left";
        }

        if (!item.style.line_height) {
            item.style.line_height = 1.6;
        }

        if (item.style.letter_spacing === undefined) {
            item.style.letter_spacing = 0;
        }

        if (item.style.word_spacing === undefined) {
            item.style.word_spacing = 0;
        }

        if (item.position_x === undefined) {
            item.position_x = 5;
        }

        if (item.position_y === undefined) {
            item.position_y = 5 + index * 12;
        }

        if (item.element_width === undefined) {

            if (item.type === "image") {
                item.element_width = 55;
            } else if (item.type === "heading") {
                item.element_width = 50;
            } else {
                item.element_width = 38;
            }

        }

        if (item.z_index === undefined) {
            item.z_index = index + 1;
        }

        if (item.element_height === undefined || item.element_height === null) {
            item.element_height = 0;
        }
    }

    function label(type) {

        const labels = {
            text: "Tekst",
            heading: "Nagłówek",
            image: "Zdjęcie",
            gallery: "Galeria",
            gallery_mosaic: "Mozaika galerii FoodFoto",
            thumbnail_gallery: "Galeria miniaturek",
            button: "Przycisk",
            separator: "Separator",
            process_steps: "Proces współpracy — 3 etapy",
            color_panel: "Tło / panel kolorowy",
            icon: "Ikona"
        };

        return labels[type] || "Element";
    }

    function resolveUrl(item) {

        if (item.type === "page" && item.page_slug) {
            return "/strona/" + item.page_slug;
        }

        if (item.type === "gallery") {
            return item.gallery_id
                ? "/portfolio/" + item.gallery_id
                : "#";
        }

        if (item.url) {
            return item.url;
        }

        return "#";
    }

    function renderHeader() {
        // Header is rendered by the same shared Blade component as the public site.
    }


    // Existing site-font catalog: selected font names resolve identically in
    // the visual canvas and the published Blade renderer.
    const mosaicFontFamilies = @json($siteFonts['families']);
    const mosaicFontChoices = @json($siteFonts['choices']);
    const mosaicRoleDefaults = {
        kicker:{font_size:11,font_weight:600,line_height:1.6,letter_spacing:2.53,color:'#647362'},
        heading:{font_size:44,font_weight:400,line_height:1.05,letter_spacing:-1.54,color:'#252622'},
        intro:{font_size:15,font_weight:400,line_height:1.55,letter_spacing:0,color:'#6c7069'},
        more:{font_size:11,font_weight:600,line_height:1.5,letter_spacing:1.21,color:'#252622'},
        category:{font_size:10,font_weight:700,line_height:1.25,letter_spacing:1.2,color:'#424741'},
        description:{font_size:11,font_weight:400,line_height:1.4,letter_spacing:0,color:'#7a7e77'},
        arrow:{font_size:17,font_weight:400,line_height:1,letter_spacing:0,color:'#455747'}
    };
    const mosaicRoleLabels = {
        kicker:'Mały nagłówek — WYBRANE REALIZACJE',
        heading:'Główny nagłówek — Piękne obrazy',
        intro:'Opis sekcji',
        more:'Przycisk / link — ZOBACZ WSZYSTKIE REALIZACJE',
        category:'Podpis pod zdjęciem',
        description:'Opis pod zdjęciem',
        arrow:'Strzałka / tekst na przycisku karty'
    };
    function mosaicEffectiveTypography(item,role,card=null){
        const shared=item.mosaic_typography?.[role]||{};
        const specific=card?.typography?.[role]||{};
        return {...shared,...specific};
    }
    function mosaicApplyTypography(node,item,role,card=null){
        node.classList.add('ff-mosaic-typography-target');
        const style=mosaicEffectiveTypography(item,role,card);
        if(style.font_family && mosaicFontFamilies[style.font_family])
            node.style.fontFamily=mosaicFontFamilies[style.font_family];
        if(style.font_size!==undefined)node.style.fontSize=style.font_size+'px';
        if(style.font_weight!==undefined)node.style.fontWeight=String(style.font_weight);
        if(style.font_style)node.style.fontStyle=style.font_style;
        if(style.line_height!==undefined)node.style.lineHeight=String(style.line_height);
        if(style.letter_spacing!==undefined)node.style.letterSpacing=style.letter_spacing+'px';
        if(style.word_spacing!==undefined)node.style.wordSpacing=style.word_spacing+'px';
        if(style.color)node.style.color=style.color;
        if(style.text_transform)node.style.textTransform=style.text_transform;
        if(style.text_align)node.style.textAlign=style.text_align;
        if(style.text_decoration)node.style.textDecoration=style.text_decoration;
        return node;
    }
    function renderMosaicTypographyFields(item,role,scope){
        const isCard=scope==='card';
        const card=item.mosaic_cards[item.mosaic_selected_index];
        const group=isCard
            ? (card.typography??={})
            : (item.mosaic_typography??={});
        const values=group[role]??={};
        const defaults={...(mosaicRoleDefaults[role]||{}),...item.mosaic_typography?.[role],...values};
        const change=(key,value)=>{
            if(value===null||value==='') delete values[key]; else values[key]=value;
            if(Object.keys(values).length===0)delete group[role];
            render();
        };
        const heading=document.createElement('p');
        heading.style.cssText='font:600 12px Arial;color:#47594a;margin:8px 0 10px';
        heading.textContent=isCard?'Formatowanie tylko tej karty':'Formatowanie wspólne dla wszystkich kart';
        properties.appendChild(heading);
        selectField('Krój pisma',values.font_family||'',[
            ['','Domyślny — według projektu'],
            ...mosaicFontChoices.map(font=>[font.value,font.label])
        ],v=>change('font_family',v));
        field('Rozmiar czcionki (px, co 0,1)','number',values.font_size??defaults.font_size,
            v=>change('font_size',Math.max(7,Math.min(120,Number(v)||7))),{min:7,max:120,step:.1});
        selectField('Grubość czcionki',String(values.font_weight??defaults.font_weight),[
            ...[100,200,300,400,500,600,700,800,900].map(w=>[String(w),String(w)])
        ],v=>change('font_weight',Number(v)));
        selectField('Pochylenie',values.font_style||'normal',[
            ['normal','Prosta'],['italic','Kursywa']
        ],v=>change('font_style',v));
        field('Interlinia (np. 1,25)','number',values.line_height??defaults.line_height,
            v=>change('line_height',Math.max(.7,Math.min(3.2,Number(v)||1))),{min:.7,max:3.2,step:.01});
        field('Odstęp między literami (px)','number',values.letter_spacing??defaults.letter_spacing??0,
            v=>change('letter_spacing',Math.max(-10,Math.min(30,Number(v)||0))),{min:-10,max:30,step:.1});
        field('Odstęp między słowami (px)','number',values.word_spacing??0,
            v=>change('word_spacing',Math.max(-20,Math.min(60,Number(v)||0))),{min:-20,max:60,step:.1});
        field('Kolor tekstu','color',values.color||defaults.color||'#252622',
            v=>change('color',v));
        selectField('Wyrównanie',values.text_align||'left',[
            ['left','Do lewej'],['center','Do środka'],['right','Do prawej'],['justify','Justuj']
        ],v=>change('text_align',v));
        selectField('Wielkość liter',values.text_transform||'none',[
            ['none','Bez zmian'],['uppercase','WERSALIKI'],['lowercase','małe litery'],
            ['capitalize','Początkowe Wielkie']
        ],v=>change('text_transform',v));
        selectField('Dekoracja tekstu',values.text_decoration||'none',[
            ['none','Brak'],['underline','Podkreślenie'],['line-through','Przekreślenie']
        ],v=>change('text_decoration',v));
        const reset=mosaicNode('button','fve-button','Przywróć domyślną typografię tego tekstu');
        reset.type='button';reset.style.cssText='width:100%;margin:12px 0';
        reset.addEventListener('click',()=>{
            delete group[role];
            render();showProperties(item);
        });
        properties.appendChild(reset);
    }

    function ensureMosaicCards(item) {
        if (!Array.isArray(item.mosaic_cards)) item.mosaic_cards=[];
        item.mosaic_cards=item.mosaic_cards.slice(0,6);
        while(item.mosaic_cards.length<6) item.mosaic_cards.push({gallery_id:null,photo_id:null,title:'',category:'',description:'',link:'',crop_x:50,crop_y:50});
        item.mosaic_cards=item.mosaic_cards.map(card=>({
            gallery_id:null,photo_id:null,title:'',category:'',description:'',link:'',crop_x:50,crop_y:50,action_text:'→',
            ...(card && typeof card==='object' && !Array.isArray(card) ? card : {})
        }));
        item.mosaic_selected_index=Math.max(0,Math.min(5,Number(item.mosaic_selected_index)||0));
    }
    function mosaicNode(tag,cls,textValue) {
        const node=document.createElement(tag);
        if(cls)node.className=cls;
        if(textValue!==undefined)node.textContent=String(textValue);
        return node;
    }
    function renderGalleryMosaic(item) {
        ensureMosaicCards(item);
        const root=mosaicNode('section','ff-mosaic ff-mosaic-desktop-fixed is-editing');
        root.style.setProperty('--fm-gap',Math.max(0,Math.min(60,Number(item.mosaic_gap)||0))+'px');
        root.style.setProperty('--fm-row',Math.max(150,Math.min(550,Number(item.mosaic_row_height)||260))+'px');
        root.style.setProperty('--fm-side',Math.max(17,Math.min(34,Number(item.mosaic_side_width)||26))+'%');
        root.style.setProperty('--fm-title',Math.max(22,Math.min(76,Number(item.mosaic_title_size)||44))+'px');
        const header=mosaicNode('div','ff-mosaic-header');
        const titleArea=mosaicNode('div');
        titleArea.appendChild(mosaicApplyTypography(mosaicNode('span','ff-mosaic-kicker',item.mosaic_kicker ?? 'WYBRANE REALIZACJE'),item,'kicker'));
        titleArea.appendChild(mosaicApplyTypography(mosaicNode('h2','ff-mosaic-heading',item.mosaic_title ?? 'Piękne obrazy dla smacznych historii'),item,'heading'));
        header.appendChild(titleArea);
        header.appendChild(mosaicApplyTypography(mosaicNode('p','ff-mosaic-intro',item.mosaic_intro ?? 'Fotografia żywności dla Twojej marki'),item,'intro'));
        const more=mosaicApplyTypography(mosaicNode('span','ff-mosaic-more'+(item.mosaic_more_button?' is-button':''),(item.mosaic_more_label || 'ZOBACZ WSZYSTKIE REALIZACJE')+' →'),item,'more');
        more.style.setProperty('--fm-more-bg',item.mosaic_more_bg||'#58754a');
        more.style.setProperty('--fm-more-border',item.mosaic_more_border||'#58754a');
        more.style.setProperty('--fm-more-radius',Number(item.mosaic_more_radius??40)+'px');
        more.style.setProperty('--fm-more-pad-x',Number(item.mosaic_more_padding_x??22)+'px');
        more.style.setProperty('--fm-more-pad-y',Number(item.mosaic_more_padding_y??12)+'px');
        header.appendChild(more);
        root.appendChild(header);
        const grid=mosaicNode('div','ff-mosaic-grid'+(item.mosaic_layout==='even'?' is-even':''));
        item.mosaic_cards.forEach((entry,index)=>{
            const gallery=galleries.find(g=>Number(g.id)===Number(entry.gallery_id));
            const photo=photos.find(p=>Number(p.id)===Number(entry.photo_id))
                || gallery?.photos?.find(p=>Number(p.id)===Number(entry.photo_id));
            const imageUrl=photo?.url || photo?.cover_url || gallery?.cover_url || '';
            const card=mosaicNode('article','ff-mosaic-card'+(index===item.mosaic_selected_index?' is-active':''));
            card.dataset.mosaicIndex=index;
            const media=mosaicNode('div','ff-mosaic-photo');
            if(imageUrl){
                const img=mosaicNode('img','ff-mosaic-image');
                img.src=imageUrl;
                img.alt=photo?.alt||gallery?.title||'';
                img.loading='lazy';
                img.style.objectPosition=Math.max(0,Math.min(100,Number(entry.crop_x)||0))+'% '+Math.max(0,Math.min(100,Number(entry.crop_y)||0))+'%';
                media.appendChild(img);
            }else{
                media.appendChild(mosaicNode('div','ff-mosaic-placeholder','Wybierz zdjęcie / galerię'));
            }
            card.appendChild(media);
            const meta=mosaicNode('div','ff-mosaic-card-meta');
            const text=mosaicNode('div','ff-mosaic-text');
            text.appendChild(mosaicApplyTypography(mosaicNode('div','ff-mosaic-card-category',entry.category || gallery?.title || 'Wybierz galerię'),item,'category',entry));
            if(item.mosaic_descriptions!==false && entry.description){
                text.appendChild(mosaicApplyTypography(mosaicNode('p','ff-mosaic-card-description',entry.description),item,'description',entry));
            }
            meta.appendChild(text);
            meta.appendChild(mosaicApplyTypography(mosaicNode('span','ff-mosaic-arrow',entry.action_text??'→'),item,'arrow',entry));
            card.appendChild(meta);
            card.addEventListener('mousedown',e=>e.stopPropagation());
            card.addEventListener('click',e=>{
                e.preventDefault();e.stopPropagation();
                item.mosaic_selected_index=index;
                selectOnly(item.id);
                render();
                showProperties(item);
            });
            grid.appendChild(card);
        });
        root.appendChild(grid);
        return root;
    }
    function renderMosaicProperties(item){
        ensureMosaicCards(item);
        const intro=mosaicNode('p',null,'Cała mozaika jest jednym elementem. Kliknij kartę na stronie, aby edytować jej zdjęcie, podpis i link. Przeciągaj narożnik całego bloku, aby zmienić jego szerokość.');
        intro.style.cssText='font:12px/1.5 Arial;color:#606b62;margin:6px 0 16px';
        properties.appendChild(intro);
        function title(text){
            const h=mosaicNode('h4',null,text);
            h.style.cssText='margin:20px 0 8px;font:700 13px Arial;color:#2b4e38;border-bottom:1px solid #ddd;padding-bottom:7px';
            properties.appendChild(h);
        }
        function action(label,handler){
            const b=mosaicNode('button','fve-button',label);
            b.type='button'; b.style.cssText='width:100%;margin-bottom:8px';
            b.addEventListener('click',()=>{handler();render();showProperties(item);});
            properties.appendChild(b);
        }
        title('Teksty nad mozaiką');
        field('Mały nagłówek','text',item.mosaic_kicker ?? 'WYBRANE REALIZACJE',v=>item.mosaic_kicker=v);
        field('Nagłówek sekcji','textarea',item.mosaic_title ?? 'Piękne obrazy dla smacznych historii',v=>item.mosaic_title=v);
        field('Opis sekcji','textarea',item.mosaic_intro ?? '',v=>item.mosaic_intro=v);
        field('Tekst odnośnika','text',item.mosaic_more_label ?? 'ZOBACZ WSZYSTKIE REALIZACJE',v=>item.mosaic_more_label=v);
        selectField('Dokąd prowadzi odnośnik?',item.mosaic_more_link || '/#portfolio',
            [['/#portfolio','Do portfolio na stronie'],...(window.builderButtonTargets||[]).map(t=>[t.url,t.label])],
            v=>item.mosaic_more_link=v);
        title('Typografia — wszystkie napisy i przyciski');
        const scope = item.mosaic_typography_scope === 'card' ? 'card' : 'shared';
        selectField('Zakres zmian',scope,[
            ['shared','Wspólna — napisy sekcji i wszystkie karty'],
            ['card','Tylko wybrana karta (podpis, opis, strzałka)']
        ],v=>{
            item.mosaic_typography_scope=v;
            if(v==='card' && !['category','description','arrow'].includes(item.mosaic_typography_role)){
                item.mosaic_typography_role='category';
            }
        });
        const roles = scope==='card' ? ['category','description','arrow']
            : ['kicker','heading','intro','more','category','description','arrow'];
        const activeRole=roles.includes(item.mosaic_typography_role)
            ? item.mosaic_typography_role : (scope==='card' ? 'category' : 'heading');
        selectField('Który tekst formatujesz?',activeRole,
            roles.map(role=>[role,mosaicRoleLabels[role]]),
            v=>item.mosaic_typography_role=v);
        renderMosaicTypographyFields(item,activeRole,scope);
        title('Wygląd odnośnika nad zdjęciami');
        selectField('Styl linku „ZOBACZ WSZYSTKIE REALIZACJE”',
            item.mosaic_more_button?'button':'link',[
                ['link','Link z podkreśleniem'],
                ['button','Przycisk z tłem, obramowaniem i zaokrągleniem']
            ],v=>item.mosaic_more_button=v==='button');
        if(item.mosaic_more_button){
            field('Kolor tła przycisku','color',item.mosaic_more_bg||'#58754a',
                v=>item.mosaic_more_bg=v);
            field('Kolor ramki przycisku','color',item.mosaic_more_border||'#58754a',
                v=>item.mosaic_more_border=v);
            field('Zaokrąglenie (px)','number',item.mosaic_more_radius??40,
                v=>item.mosaic_more_radius=Math.max(0,Math.min(100,Number(v)||0)),
                {min:0,max:100,step:1});
            field('Margines wewnętrzny poziomy (px)','number',item.mosaic_more_padding_x??22,
                v=>item.mosaic_more_padding_x=Math.max(0,Math.min(80,Number(v)||0)),
                {min:0,max:80,step:1});
            field('Margines wewnętrzny pionowy (px)','number',item.mosaic_more_padding_y??12,
                v=>item.mosaic_more_padding_y=Math.max(0,Math.min(50,Number(v)||0)),
                {min:0,max:50,step:1});
        }
        title('Rozmieszczenie kart');
        selectField('Wariant kompozycji',item.mosaic_layout||'editorial',[
            ['editorial','Mozaika: dwie wysokie karty po bokach'],
            ['even','Równe karty: trzy kolumny × dwa rzędy']
        ],v=>item.mosaic_layout=v);
        field('Odstępy między kartami (px)','number',item.mosaic_gap ?? 12,
            v=>item.mosaic_gap=Math.max(0,Math.min(60,Math.round(Number(v)||0))),{min:0,max:60,step:1});
        field('Wysokość jednego rzędu (px)','number',item.mosaic_row_height ?? 260,
            v=>item.mosaic_row_height=Math.max(150,Math.min(550,Math.round(Number(v)||260))),{min:150,max:550,step:1});
        if(item.mosaic_layout !== 'even'){
            field('Szerokość skrajnych kart (%)','number',item.mosaic_side_width ?? 26,
                v=>item.mosaic_side_width=Math.max(17,Math.min(34,Number(v)||26)),{min:17,max:34,step:0.5});
        }
        field('Rozmiar nagłówka (px)','number',item.mosaic_title_size ?? 44,
            v=>item.mosaic_title_size=Math.max(22,Math.min(76,Number(v)||44)),{min:22,max:76,step:0.1});
        selectField('Podpisy opisowe kart',item.mosaic_descriptions===false?'off':'on',
            [['on','Pokaż opisy'],['off','Ukryj opisy']],v=>item.mosaic_descriptions=v==='on');
        title('Karty galerii: 6 pozycji');
        selectField('Edytowana karta',String(item.mosaic_selected_index),item.mosaic_cards.map((c,i)=>[
            String(i),String(i+1)+'. '+(c.category || galleries.find(g=>Number(g.id)===Number(c.gallery_id))?.title || 'Bez galerii')
        ]),v=>item.mosaic_selected_index=Number(v));
        const idx=item.mosaic_selected_index,card=item.mosaic_cards[idx];
        const gallery=galleries.find(g=>Number(g.id)===Number(card.gallery_id));
        const row=document.createElement('div');row.style.cssText='display:flex;gap:6px;margin:8px 0';
        const up=mosaicNode('button','fve-button','← Wcześniej');
        const down=mosaicNode('button','fve-button','Później →');
        for(const button of [up,down]){button.type='button';button.style.flex='1';row.appendChild(button);}
        up.disabled=idx===0;down.disabled=idx===5;
        const swap=delta=>{
            const other=idx+delta;if(other<0||other>5)return;
            [item.mosaic_cards[idx],item.mosaic_cards[other]]=[item.mosaic_cards[other],item.mosaic_cards[idx]];
            item.mosaic_selected_index=other;render();showProperties(item);
        };
        up.onclick=()=>swap(-1);down.onclick=()=>swap(1);properties.appendChild(row);
        selectField('Galeria (opcjonalnie — źródło okładki)',card.gallery_id||'',[
            ['','Bez galerii — link do podstrony'],
            ...galleries.filter(g=>g.published!==false).map(g=>[g.id,g.title])
        ],v=>{card.gallery_id=v?Number(v):null;});
        selectField('Zdjęcie z całej biblioteki',card.photo_id||'',[
            ['','Automatyczna okładka galerii'],
            ...photos.map(p=>[p.id,p.title||p.alt||('Zdjęcie '+p.id)])
        ],v=>card.photo_id=v?Number(v):null);
        if(photos.length){
            const photoList=mosaicNode('div');
            photoList.style.cssText='display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:6px;max-height:270px;overflow:auto;margin:6px 0 16px;padding:4px;border:1px solid #e1e7df;border-radius:6px';
            photos.forEach(photo=>{
                const pick=mosaicNode('button');
                pick.type='button';pick.title=photo.title||photo.alt||'Wybierz zdjęcie';
                pick.style.cssText='border:2px solid '+(Number(card.photo_id)===Number(photo.id)?'#37784f':'#ddd')+';border-radius:4px;padding:2px;background:white;cursor:pointer';
                const img=mosaicNode('img');img.src=photo.thumbnail_url||photo.url;img.alt=photo.alt||photo.title||'';
                img.loading='lazy';img.style.cssText='width:100%;height:65px;object-fit:cover;display:block';
                pick.appendChild(img);
                pick.addEventListener('click',()=>{card.photo_id=Number(photo.id);render();showProperties(item);});
                photoList.appendChild(pick);
            });
            properties.appendChild(photoList);
        }
        field('Etykieta pod zdjęciem','text',card.category||'',v=>card.category=v);
        field('Napis na przycisku / strzałce karty','text',card.action_text??'→',
            v=>card.action_text=String(v).slice(0,24));
        field('Krótki opis karty','textarea',card.description||'',v=>card.description=v);
        selectField('Podstrona lub galeria po kliknięciu',card.link||'',[
            ['','Otwórz wybraną galerię'],
            ...(window.builderButtonTargets||[]).map(t=>[t.url,t.label])
        ],v=>card.link=v);
        field('Kadrowanie poziome (%)','number',card.crop_x??50,
            v=>card.crop_x=Math.max(0,Math.min(100,Math.round(Number(v)||0))),{min:0,max:100,step:1});
        field('Kadrowanie pionowe (%)','number',card.crop_y??50,
            v=>card.crop_y=Math.max(0,Math.min(100,Math.round(Number(v)||0))),{min:0,max:100,step:1});
    }

    function createElementContent(item) {

        const semanticTags = ['div', 'p', 'h1', 'h2', 'h3', 'small'];
        const requestedSemanticTag = item.semantic_tag
            || (item.type === 'heading' ? (item.heading_level || 'div') : (item.type === 'text' ? 'p' : 'div'));
        const boxTag = ['heading', 'text'].includes(item.type) && semanticTags.includes(requestedSemanticTag)
            ? requestedSemanticTag
            : 'div';
        const box = document.createElement(boxTag);
        box.style.margin = '0';
        box.style.width = '100%';
        box.style.boxSizing = 'border-box';
        box.style.overflowWrap = 'anywhere';
        box.style.whiteSpace = 'normal';

        if (Number(item.element_height) > 0 && ['text', 'heading', 'section'].includes(item.type)) {
            box.style.minHeight = Number(item.element_height) + 'px';
        }

        window.builderTypography.apply(box, item);

        if (item.type === "thumbnail_gallery") {
            box.appendChild(window.ThumbnailGallery.preview(item, photos, function () {
                showProperties(item);
            }));
        } else if (item.type === "image") {

            if (item.photo_url) {

                const image =
                    document.createElement("img");

                image.src = item.photo_url;

                image.alt =
                    item.photo_title || "Zdjęcie";

                image.style.width = "100%";

                const imageFit = item.image_fit === "contain" ? "contain" : "cover";
                const imageRatio = item.image_ratio || "auto";
                if (item.image_height && Number(item.image_height) > 0) {
                    image.style.height = Number(item.image_height) + "px";
                    image.style.objectFit = imageFit;
                } else if (imageRatio !== "auto") {
                    image.style.aspectRatio = imageRatio;
                    image.style.height = "auto";
                    image.style.objectFit = imageFit;
                } else {
                    image.style.height = "auto";
                    image.style.objectFit = "contain";
                }

                image.style.borderRadius =
                    (item.image_radius || 0) + "px";

                if (item.image_lightbox) {
                    image.style.cursor = "zoom-in";
                    image.style.transition = "transform .22s ease, filter .22s ease";
                    image.title = "To zdjęcie będzie można powiększyć";
                } else if (item.image_link) {
                    image.style.cursor = "pointer";
                    image.title = "Zdjęcie ma ustawiony link";
                }

                box.appendChild(image);

            } else {

                if (item.image_placeholder_hidden) {
                    // Keep the transparent image slot selectable so photos
                    // can be chosen later without covering the backdrop.
                    box.style.height = (Number(item.image_height) || 200) + 'px';
                    box.style.border = '1px dashed rgba(128,128,128,.5)';
                    box.title = 'Kliknij i wybierz zdjęcie z biblioteki';
                } else {
                    box.innerHTML =
                        "<div style='padding:100px 20px;text-align:center;background:#f3f3f3;color:#999;'>Wybierz zdjęcie</div>";
                }
            }

        } else if (item.type === "icon") {
            // Vector icon, not a font glyph: crisp on retina and resizeable.
            const ns = 'http://www.w3.org/2000/svg';
            const svg = document.createElementNS(ns, 'svg');
            svg.setAttribute('viewBox', '0 0 24 24');
            svg.setAttribute('role', 'img');
            svg.setAttribute('aria-label', 'Telefon');
            svg.setAttribute('fill', 'none');
            svg.setAttribute('stroke', 'currentColor');
            svg.setAttribute('stroke-width', '1.65');
            svg.setAttribute('stroke-linecap', 'round');
            svg.setAttribute('stroke-linejoin', 'round');
            svg.style.display = 'block';
            svg.style.width = Math.max(16, Math.min(120, Number(item.icon_size) || 38)) + 'px';
            svg.style.height = svg.style.width;
            svg.style.color = /^#[0-9a-f]{6}$/i.test(item.icon_color || '') ? item.icon_color : '#222222';
            const path = document.createElementNS(ns, 'path');
            path.setAttribute('d', 'M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.12.96.34 1.9.66 2.8a2 2 0 0 1-.45 2.11L8.5 11.41a16 16 0 0 0 4.09 4.09l1.78-1.79a2 2 0 0 1 2.11-.45c.9.32 1.84.54 2.8.66A2 2 0 0 1 22 16.92z');
            svg.appendChild(path);
            box.appendChild(svg);

        } else if (item.type === "color_panel") {

            const hex = /^#[0-9a-f]{6}$/i.test(item.panel_color || '') ? item.panel_color : '#ffffff';
            const alpha = Math.max(0, Math.min(100, Number(item.panel_opacity) ?? 100)) / 100;
            const red = parseInt(hex.slice(1,3),16);
            const green = parseInt(hex.slice(3,5),16);
            const blue = parseInt(hex.slice(5,7),16);
            box.style.height = (Number(item.element_height) || 200) + 'px';
            box.style.background = 'rgba(' + red + ',' + green + ',' + blue + ',' + alpha + ')';
            box.style.minHeight = '0';

        } else if (item.type === "separator") {

            box.innerHTML =
                "<div style='height:1px;background:#999;width:100%;'></div>";

        } else if (item.type === "button") {

            const button =
                document.createElement("div");

            button.textContent =
                item.content || "Przycisk";

            button.style.display = "flex";
            button.style.alignItems = "center";
            button.style.justifyContent = "center";
            button.style.boxSizing = "border-box";
            button.style.width = "100%";
            button.style.minHeight = Number(item.element_height) > 0 ? Number(item.element_height) + "px" : "auto";
            button.style.padding = "12px 22px";
            button.style.background = "#171717";
            // Inherit the active responsive typography color from the parent box.
            button.style.color = "inherit";
            button.style.whiteSpace = "normal";
            button.style.overflowWrap = "anywhere";
            window.BuilderButton.apply(button, item);

            box.appendChild(button);

        } else if (item.type === "gallery_mosaic") {
            box.appendChild(renderGalleryMosaic(item));
        } else if (item.type === "gallery") {

            const collectionId = Number(item.gallery_collection_id) || 0;
            const collectionGalleries = collectionId
                ? galleries.filter(gallery => Number(gallery.gallery_collection_id) === collectionId)
                : galleries;
            const selectedGallery = collectionGalleries.find(gallery => Number(gallery.id) === Number(item.gallery_id));
            const selectedIds = Array.isArray(item.gallery_ids) ? item.gallery_ids.map(Number) : [];
            const galleryList = item.gallery_mode === "single"
                ? (selectedGallery?.photos || [])
                : item.gallery_mode === "selected"
                    ? selectedIds.map(id => collectionGalleries.find(gallery => Number(gallery.id) === id)).filter(Boolean)
                    : collectionGalleries;

            const grid = document.createElement("div");
            const galleryColumns = Math.max(1, Math.min(12, Number(item.gallery_columns) || 4));
            const galleryGap = Math.max(0, Math.min(100, Number(item.gallery_gap) || 14));

            if (item.gallery_mode === "single") {
                grid.style.display = "grid";
                grid.style.gridTemplateColumns = "repeat(" + galleryColumns + ", minmax(0, 1fr))";
            } else {
                grid.style.display = "flex";
                grid.style.flexWrap = "wrap";
                grid.style.alignItems = "flex-start";
                grid.style.justifyContent = item.gallery_align === "center"
                    ? "center"
                    : item.gallery_align === "right"
                        ? "flex-end"
                        : "flex-start";
            }
            grid.style.gap = galleryGap + "px";
            grid.style.width = "100%";

            if (!galleryList.length) {

                const empty = document.createElement("div");

                empty.textContent = item.gallery_mode === 'single'
                    ? (selectedGallery ? 'Ta galeria nie zawiera jeszcze zdjęć.' : 'Wybierz galerię we właściwościach elementu.')
                    : item.gallery_mode === 'selected'
                        ? 'Wybierz galerie do tego boksu we właściwościach elementu.'
                        : 'Brak galerii. Dodaj galerie w module Galerie.';

                empty.style.padding = "40px";
                empty.style.textAlign = "center";
                empty.style.color = "#999";
                empty.style.background = "#f5f5f5";

                box.appendChild(empty);

            } else {

                galleryList.forEach(function (gallery) {

                    const card =
                        document.createElement("div");

                    card.style.background = "transparent";
                    card.style.border = "0";
                    card.style.overflow = "visible";

                    if (item.gallery_mode !== "single") {
                        if (!item.gallery_card_settings || typeof item.gallery_card_settings !== "object" || Array.isArray(item.gallery_card_settings)) {
                            item.gallery_card_settings = {};
                        }
                        const key = String(gallery.id);
                        if (!item.gallery_card_settings[key] || typeof item.gallery_card_settings[key] !== "object") {
                            item.gallery_card_settings[key] = {};
                        }
                        const local = item.gallery_card_settings[key];
                        const explicitWidth = Number(local.width) > 0 ? Number(local.width) : 0;
                        const defaultBasis = "calc((100% - " + Math.max(0, galleryColumns - 1) + " * " + galleryGap + "px) / " + galleryColumns + ")";
                        const offset = Number(local.x_offset) || 0;

                        card.style.flex = explicitWidth > 0
                            ? "0 0 " + explicitWidth + "%"
                            : "0 0 " + defaultBasis;
                        card.style.transform = "translateX(" + offset + "px)";
                        card.style.cursor = "pointer";
                        card.dataset.galleryCardId = String(gallery.id);

                        if (Number(item.gallery_selected_id) === Number(gallery.id)) {
                            card.style.outline = "3px solid #111";
                            card.style.outlineOffset = "3px";
                        }

                        card.addEventListener("click", function (event) {
                            event.stopPropagation();
                            item.gallery_selected_id = Number(gallery.id);
                            selected = item.id;
                            render();
                            showProperties(item);
                        });
                    }

                    if (gallery.cover_url) {

                        const image =
                            document.createElement("img");

                        image.src = gallery.cover_url;
                        image.alt = gallery.alt || gallery.title || "";

                        image.style.display = "block";
                        image.style.width = "100%";
                        image.style.aspectRatio = item.gallery_ratio || "1 / .7";
                        image.style.objectFit = "cover";

                        card.appendChild(image);

                    } else {

                        const emptyImage =
                            document.createElement("div");

                        emptyImage.textContent =
                            "Brak zdjęcia";

                        emptyImage.style.height = "120px";
                        emptyImage.style.display = "flex";
                        emptyImage.style.alignItems = "center";
                        emptyImage.style.justifyContent = "center";
                        emptyImage.style.background = "#f3f3f3";
                        emptyImage.style.color = "#999";

                        card.appendChild(emptyImage);
                    }

                    const title =
                        document.createElement("div");

                    title.textContent =
                        gallery.title || "Galeria";

                    title.style.padding = "12px 0 0";

                    card.appendChild(title);

                    grid.appendChild(card);
                });

                box.appendChild(grid);
            }

        } else if (item.type === "process_steps") {

            box.appendChild(window.FoodfotoProcess.render(item, function (part) {
                selectOnly(item.id);
                window.FoodfotoProcess.activate(item,part);
                showProperties(item);
                render();
            }));

        } else if (item.type === "text") {

            const textValue = item.content || "";
            const lines = textValue.split("\n");

            lines.forEach(function (line, index) {
                box.appendChild(document.createTextNode(line));
                if (index < lines.length - 1) {
                    box.appendChild(document.createElement("br"));
                }
            });

        } else {

            box.textContent =
                item.content || label(item.type);
        }

        window.builderTypography.imageCaption(box, item);
        return box;
    }

    // Measure the same boxes that appear in the editor. Positions saved by
    // the builder remain the authoritative, unshifted coordinates.
    function flowMeasurements(overrideId = null, overrideYPx = null) {
        const nodes = new Map(Array.from(content.querySelectorAll(".fve-element"))
            .map(node => [node.dataset.id, node]));
        return data.sections.filter(item => nodes.has(item.id)).map(item => {
            const node = nodes.get(item.id);
            return {
                id: item.id,
                type: item.type,
                flowGroup: item.flow_group || null,
                insertionTargets: item.flow_insertion_targets || null,
                insertionExisting: item.flow_insertion_existing || null,
                insertionTargetTop: item.flow_insertion_target_top ?? null,
                anchorContributions: item.flow_anchor_contributions ?? null,
                x: (Number(item.position_x) || 0) * 14,
                y: item.id === overrideId ? overrideYPx : (Number(item.position_y) || 0) * 9,
                width: node.offsetWidth,
                height: node.offsetHeight,
                baseline: Number(item.flow_base_height) || 0,
                anchor: Number(item.flow_anchor) || 0,
                mode: item.flow_mode || 'free'
            };
        });
    }

    // Save per-source flow displacement at the moment an element is manually
    // positioned. A later inserted element has a new ID and can still push it.
    function inheritedFlowContributions(yPx, movingId = null) {
        const measured = flowMeasurements(movingId, yPx);
        const plan = window.BuilderAutoFlow.calculate(measured);
        if (movingId) {
            return plan.placements.find(entry => entry.id === movingId)?.contributions || {};
        }
        const sources = {};
        for (const event of plan.shifts) {
            if (yPx >= event.at - .1) {
                sources[event.sourceId] = (sources[event.sourceId] || 0) + event.delta;
            }
        }
        return sources;
    }

    function reflowElements() {
        const measured = flowMeasurements();
        let plan = window.BuilderAutoFlow.calculate(measured);
        const nodes = new Map(Array.from(content.querySelectorAll(".fve-element"))
            .map(node => [node.dataset.id, node]));

        // Legacy anchors were single numbers. Migrate while preserving their
        // visible positions, so newly added blocks no longer cancel their shifts.
        let migrated = false;
        for (const item of data.sections) {
            if (item.flow_anchor_contributions !== undefined || !Number(item.flow_anchor)) continue;
            const placement = plan.placements.find(row => row.id === item.id);
            const entries = Object.entries(placement?.contributions || {});
            const total = entries.reduce((sum, [, value]) => sum + value, 0);
            const oldAnchor = Number(item.flow_anchor);
            item.flow_anchor_contributions = total
                ? Object.fromEntries(entries.map(([id, value]) => [id, value * oldAnchor / total]))
                : {__legacy: oldAnchor};
            item.flow_anchor = 0;
            migrated = true;
        }
        if (migrated) plan = window.BuilderAutoFlow.calculate(flowMeasurements());
        for (const entry of plan.placements) {
            const node = nodes.get(entry.id);
            if (node) node.style.top = entry.top + "px";
        }
        data.sections.forEach(item => {
            if (item.flow_mode !== 'auto' || Number(item.flow_base_height) > 0) return;
            const node = nodes.get(item.id);
            if (node && node.offsetHeight > 0) item.flow_base_height = node.offsetHeight;
        });
        return Math.max(900, ...Array.from(nodes.values()).map(node =>
            node.offsetTop + node.offsetHeight + 40));
    }

    // Images and responsive gallery previews may finish loading after render.
    let autoFlowObserver = null;
    function observeAutoFlow() {
        if (!autoFlowObserver) {
            autoFlowObserver = new ResizeObserver(() => {
                if (!editor.classList.contains('open')) return;
                requestAnimationFrame(() => {
                    if (!editor.classList.contains('open')) return;
                    const height = reflowElements();
                    content.style.height = height + "px";
                    content.style.minHeight = height + "px";
                    updateZoom();
                    renderRulers();
                });
            });
        }
        autoFlowObserver.disconnect();
        content.querySelectorAll(".fve-element").forEach(node => autoFlowObserver.observe(node));
    }

    function render() {

        content.innerHTML = "";

        content.style.backgroundColor =
            settings.background_color ||
            "#ffffff";

        content.style.backgroundImage = "none";
        content.style.backgroundSize = "auto";
        content.style.backgroundPosition = "initial";
        content.style.backgroundRepeat = "initial";

        data.sections
            .slice()
            .sort((a, b) => (Number(a.position_y) || 0) - (Number(b.position_y) || 0))
            .forEach(function (item, index) {

            ensureItem(item, index);

            const element =
                document.createElement("div");

            element.className =
                "fve-element";

            if (item.type === "thumbnail_gallery") element.dataset.thumbnailBlock = '';

            element.dataset.id =
                item.id;

            element.style.left =
                item.position_x + "%";

            element.style.top =
                ((Number(item.position_y) || 0) / 100 * 900) + "px";

            element.style.width =
                item.element_width + "%";
            if (item.type === 'color_panel') element.style.minWidth = '0';

            element.style.minHeight =
                Number(item.element_height) > 0
                    ? Number(item.element_height) + "px"
                    : "0";

            // Stała hierarchia:
            // tło = 0
            // zdjęcia = 10
            // separatory = 20
            // galerie = 30
            // tekst = 100
            // nagłówki = 110
            // przyciski = 120
            const layerMap = {
                image: 10,
                separator: 20,
                gallery: 30,
                gallery_mosaic: 30,
                thumbnail_gallery: 30,
                text: 100,
                heading: 110,
                button: 120
            };

            element.style.zIndex =
                (item.type === 'color_panel')
                    ? Math.max(0, Math.min(50, Number(item.panel_layer) || 2))
                    : (item.type === 'image' && Number(item.image_layer) >= 0
                        ? Math.max(0, Math.min(50, Number(item.image_layer)))
                        : (layerMap[item.type] || 100));

            if (selectedIds.has(item.id)) {
                element.classList.add("selected", "group-selected");
            }
            if (selected === item.id) {
                element.classList.add("primary-selected");
            }

            const inner =
                createElementContent(item);

            element.appendChild(inner);

            const remove =
                document.createElement("button");

            remove.className =
                "fve-delete";

            remove.type = "button";
            remove.textContent = "×";

            remove.addEventListener("click", function (event) {

                event.stopPropagation();

                data.sections =
                    data.sections.filter(function (x) {
                        return x.id !== item.id;
                    });

                selectedIds.delete(item.id);
                selected = selectedIds.size ? Array.from(selectedIds)[0] : null;

                render();
                showProperties(selected ? itemForSelection() : null);
            });

            element.appendChild(remove);

            const resizeHandle = document.createElement("button");
            resizeHandle.type = "button";
            resizeHandle.className = "fve-resize-handle";
            resizeHandle.setAttribute("aria-label", "Zmień rozmiar elementu");
            resizeHandle.title = "Przeciągnij, aby zmienić szerokość i wysokość";
            element.appendChild(resizeHandle);

            let resizing = false;
            let resizeStartX = 0;
            let resizeStartY = 0;
            let resizeStartWidth = 0;
            let resizeStartHeight = 0;

            resizeHandle.addEventListener("mousedown", function (event) {
                if (event.button !== 0) {
                    return;
                }

                resizing = true;
                selectOnly(item.id);
                resizeStartX = event.clientX;
                resizeStartY = event.clientY;
                resizeStartWidth = Number(item.element_width) || 5;
                resizeStartHeight = Number(item.element_height) > 0
                    ? Number(item.element_height)
                    : Math.max(24, element.getBoundingClientRect().height / zoom);

                event.preventDefault();
                event.stopPropagation();
                showProperties(item);
            });

            document.addEventListener("mousemove", function (event) {
                if (!resizing) {
                    return;
                }

                const rect = content.getBoundingClientRect();
                const dxPercent = ((event.clientX - resizeStartX) / rect.width) * 100;
                const maxWidth = Math.max(5, 100 - (Number(item.position_x) || 0));

                item.element_width = Math.max(
                    5,
                    Math.min(maxWidth, resizeStartWidth + dxPercent)
                );

                item.element_height = Math.max(
                    24,
                    resizeStartHeight + ((event.clientY - resizeStartY) / zoom)
                );

                element.style.width = item.element_width + "%";
                element.style.minHeight = item.element_height + "px";

                const inner = element.firstElementChild;
                if (inner && ['text', 'heading', 'section'].includes(item.type)) {
                    inner.style.minHeight = item.element_height + "px";
                }

                if (item.type === "button" && inner) {
                    const button = inner.firstElementChild;
                    if (button) {
                        button.style.minHeight = item.element_height + "px";
                    }
                }

                if (item.type === "image") {
                    item.image_height = Math.round(item.element_height);
                    const image = element.querySelector("img");
                    if (image) {
                        image.style.height = item.image_height + "px";
                        image.style.objectFit = "cover";
                    }
                }
            });

            document.addEventListener("mouseup", function () {
                if (!resizing) {
                    return;
                }

                resizing = false;
                render();
                showProperties(item);
            });

            let dragging = false;
            let dragMoved = false;
            let startX = 0;
            let startY = 0;
            let dragIds = [];
            let dragStarts = new Map();
            let dragSnapTargets = null;

            element.addEventListener("mousedown", function (event) {
                if (
                    event.target.closest(".fve-resize-handle")
                    || event.target.closest(".fve-delete")
                    || (item.type === "thumbnail_gallery" && event.target.closest(".thumbnail-gallery-item"))
                ) {
                    return;
                }

                if (event.button !== 0) return;

                if (event.shiftKey || event.metaKey || event.ctrlKey) {
                    toggleSelection(item.id);
                    render();
                    showProperties(itemForSelection());
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                if (!selectedIds.has(item.id)) {
                    selectOnly(item.id);
                } else {
                    selected = item.id;
                }

                dragging = true;
                dragMoved = false;
                startX = event.clientX;
                startY = event.clientY;
                dragIds = Array.from(selectedIds);
                dragStarts = new Map();

                dragIds.forEach(id => {
                    const movingItem = data.sections.find(section => section.id === id);
                    if (!movingItem) return;
                    const g = elementGeometry(movingItem);
                    dragStarts.set(id, {
                        x: g.x,
                        y: g.y,
                        width: g.width,
                        height: g.height,
                        position_x: Number(movingItem.position_x) || 0,
                        position_y: Number(movingItem.position_y) || 0,
                    });
                    const node = content.querySelector('.fve-element[data-id="' + CSS.escape(id) + '"]');
                    if (node) node.style.cursor = "grabbing";
                });

                // Freeze ruler/snap references before automatic reflow moves
                // any neighboring content during a drag.
                dragSnapTargets = {x: [0, 700, 1400], y: [0]};
                data.sections.forEach(other => {
                    if (dragIds.includes(other.id)) return;
                    const g = elementGeometry(other);
                    dragSnapTargets.x.push(g.x, g.cx, g.right);
                    dragSnapTargets.y.push(g.y, g.cy, g.bottom);
                });

                event.preventDefault();
                event.stopPropagation();
                render();
                showProperties(item);
            });

            document.addEventListener("mousemove", function (event) {
                if (!dragging) return;

                const rawDxPx = (event.clientX - startX) / zoom;
                const rawDyPx = (event.clientY - startY) / zoom;
                if (Math.abs(rawDxPx) > 1 || Math.abs(rawDyPx) > 1) dragMoved = true;

                const snapped = snapGroup(dragIds, rawDxPx, rawDyPx, dragStarts, dragSnapTargets);

                // Keep the whole group inside the left/right edge and above the top edge.
                let minX = Infinity;
                let maxRight = -Infinity;
                let minY = Infinity;
                dragIds.forEach(id => {
                    const s = dragStarts.get(id);
                    if (!s) return;
                    minX = Math.min(minX, s.x + snapped.dx);
                    maxRight = Math.max(maxRight, s.x + s.width + snapped.dx);
                    minY = Math.min(minY, s.y + snapped.dy);
                });

                let dxPx = snapped.dx;
                let dyPx = snapped.dy;
                if (minX < 0) dxPx -= minX;
                if (maxRight > 1400) dxPx -= (maxRight - 1400);
                if (minY < 0) dyPx -= minY;

                dragIds.forEach(id => {
                    const movingItem = data.sections.find(section => section.id === id);
                    const s = dragStarts.get(id);
                    if (!movingItem || !s) return;

                    movingItem.position_x = ((s.x + dxPx) / 1400) * 100;
                    movingItem.position_y = ((s.y + dyPx) / 900) * 100;

                    const node = content.querySelector('.fve-element[data-id="' + CSS.escape(id) + '"]');
                    if (node) {
                        node.style.left = movingItem.position_x + "%";
                        node.style.top = ((Number(movingItem.position_y) || 0) / 100 * 900) + "px";
                    }
                });

                // Show the pushed gallery/image row WHILE dragging. Previously
                // only the text moved, then all blocks jumped after mouseup.
                if (dragIds.some(id => data.sections.some(section => section.id === id && section.flow_mode === 'auto'))) {
                    dragIds.forEach(id => {
                        const movingItem = data.sections.find(section => section.id === id);
                        if (!movingItem) return;
                        movingItem.flow_anchor_contributions =
                            inheritedFlowContributions((Number(movingItem.position_y) || 0) * 9, id);
                        movingItem.flow_anchor = 0;
                    });
                    const maxBottom = reflowElements();
                    content.style.height = maxBottom + "px";
                    content.style.minHeight = maxBottom + "px";
                }
            });

            document.addEventListener("mouseup", function () {
                if (!dragging) return;

                dragging = false;
                hideSnapGuides();

                if (dragMoved) {
                    dragIds.forEach(id => {
                        const movingItem = data.sections.find(section => section.id === id);
                        if (!movingItem) return;
                        // position_y already stores the visual drop position from mousemove.
                        // Compensate the current upstream growth; future changes still flow.
                        movingItem.flow_anchor_contributions = inheritedFlowContributions((Number(movingItem.position_y) || 0) * 9, id);
                        movingItem.flow_anchor = 0;
                    });
                }

                dragIds.forEach(id => {
                    const node = content.querySelector('.fve-element[data-id="' + CSS.escape(id) + '"]');
                    if (node) node.style.cursor = "move";
                });

                if (dragIds.some(id => {
                    const movingItem = data.sections.find(section => section.id === id);
                    return movingItem && movingItem.type === 'thumbnail_gallery';
                })) {
                    window.ThumbnailGallery.fitCanvas(content);
                }

                if (dragMoved) render();
                showProperties(itemForSelection());
                renderRulers();
            });

            element.addEventListener("click", function (event) {
                if (dragMoved) {
                    dragMoved = false;
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }

                if (event.shiftKey || event.metaKey || event.ctrlKey) {
                    toggleSelection(item.id);
                } else if (!selectedIds.has(item.id) || selectedIds.size > 1) {
                    selectOnly(item.id);
                } else {
                    selected = item.id;
                }

                showProperties(itemForSelection());
                render();
            });

            content.appendChild(element);
        });

        requestAnimationFrame(function () {
            const maxBottom = reflowElements();
            content.style.height = maxBottom + "px";
            content.style.minHeight = maxBottom + "px";
            page.style.minHeight = "0";
            updateZoom();
            renderRulers();
            observeAutoFlow();
        });
        window.ThumbnailGallery.fitCanvas(content);
    }

    // Clicking empty canvas sets the insertion point for the next new element.
    // Coordinates are normalized against the canvas so zoom does not change placement.
    let nextInsertionPoint = null;
    content.addEventListener("click", function (event) {
        if (event.target !== content) return;
        const rect = content.getBoundingClientRect();
        if (!rect.width || !rect.height) return;
        nextInsertionPoint = {
            x: Math.max(0, Math.min(95, ((event.clientX - rect.left) / rect.width) * 100)),
            y: Math.max(0, ((event.clientY - rect.top) / rect.height) * content.offsetHeight / 9),
        };
    });

    content.addEventListener("mousedown", function (event) {
        if (event.target !== content) return;
        selectedIds.clear();
        selected = null;
        hideSnapGuides();
        render();
        showProperties(null);
    });

    function field(labelText, type, value, callback, options = {}) {

        const wrapper =
            document.createElement("div");

        wrapper.className =
            "fve-field";

        const label =
            document.createElement("label");

        label.textContent =
            labelText;

        wrapper.appendChild(label);

        const input =
            document.createElement(
                type === "textarea"
                    ? "textarea"
                    : "input"
            );

        if (type !== "textarea") {
            input.type = type;
            if (type === "number") {
                input.step = options.step ?? "any";
                if (options.min !== undefined) input.min = String(options.min);
                if (options.max !== undefined) input.max = String(options.max);
            }
        }

        input.value =
            value ?? "";

        input.addEventListener("input", function () {

            callback(input.value);

            render();
        });

        wrapper.appendChild(input);

        properties.appendChild(wrapper);
    }

    function selectField(labelText, value, options, callback) {
        const wrapper = document.createElement('label');
        wrapper.className = 'fve-field';
        wrapper.textContent = labelText;
        const select = document.createElement('select');
        options.forEach(([id, title]) => {
            const option = document.createElement('option');
            option.value = id;
            option.textContent = title;
            select.appendChild(option);
        });
        select.value = value;
        select.addEventListener('change', () => { callback(select.value); render(); showProperties(itemForSelection()); });
        wrapper.appendChild(select);
        properties.appendChild(wrapper);
    }

    function itemForSelection() { return data.sections.find(item => item.id === selected); }

    function showProperties(item) {

        properties.innerHTML = "";

        if (!item) {

            properties.innerHTML =
                "<p style='color:#888;line-height:1.6;font-size:13px;'>Kliknij element, aby go edytować. Shift/Cmd/Ctrl + klik zaznacza kilka elementów. Przeciągnięcie jednego z zaznaczonych przesuwa całą grupę.</p>";

            return;
        }

        const heading =
            document.createElement("h3");

        heading.textContent =
            label(item.type);

        heading.style.margin =
            "0 0 20px";

        properties.appendChild(heading);

        const actions = document.createElement("div");
        actions.style.display = "grid";
        actions.style.gridTemplateColumns = "1fr 1fr";
        actions.style.gap = "8px";
        actions.style.marginBottom = "18px";

        function actionButton(text, callback) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "fve-button";
            button.textContent = text;
            button.addEventListener("click", callback);
            actions.appendChild(button);
        }

        actionButton("Duplikuj", function () {
            const copy = JSON.parse(JSON.stringify(item));
            copy.id = "element-" + Date.now() + "-" + Math.floor(Math.random() * 100000);
            copy.position_y = (Number(item.position_y) || 0) + 5;
            data.sections.push(copy);
            selected = copy.id;
            render();
            showProperties(copy);
        });

        actionButton("↑ Wyżej", function () {
            item.position_y = Math.max(0, (Number(item.position_y) || 0) - 5);
            render();
            showProperties(item);
        });

        actionButton("↓ Niżej", function () {
            item.position_y = (Number(item.position_y) || 0) + 5;
            render();
            showProperties(item);
        });

        properties.appendChild(actions);

        // New content may grow vertically without disturbing the existing page.
        const flowWrap = document.createElement('label');
        flowWrap.className = 'fve-auto-flow-control';
        const flowInput = document.createElement('input');
        flowInput.type = 'checkbox';
        flowInput.className = 'fve-auto-flow-input';
        const flowMembers = item.flow_group
            ? data.sections.filter(section => section.flow_group === item.flow_group)
            : [item];
        flowInput.checked = flowMembers.some(section => section.flow_mode === 'auto');
        flowInput.setAttribute('aria-label', 'Automatyczny układ — odsuwaj elementy poniżej');
        flowInput.addEventListener('change', () => {
            flowMembers.forEach(section => {
                if (flowInput.checked) {
                    const node = Array.from(content.querySelectorAll('.fve-element'))
                        .find(n => n.dataset.id === section.id);
                    section.flow_base_height = node?.offsetHeight || Number(section.element_height) || 50;
                    section.flow_mode = 'auto';
                } else {
                    section.flow_mode = 'free';
                }
            });
            render();
        });
        flowWrap.appendChild(flowInput);
        const flowLabel = document.createElement('span');
        flowLabel.textContent = item.flow_group
            ? 'Automatyczny układ całej sekcji — odsuwaj elementy poniżej'
            : 'Automatyczny układ — odsuwaj elementy poniżej';
        flowWrap.appendChild(flowLabel);
        properties.appendChild(flowWrap);
        const flowTip = document.createElement('small');
        flowTip.style.cssText = 'display:block;margin:2px 0 14px;color:#666;line-height:1.45';
        flowTip.textContent = 'Włączone: nowe lub powiększone pole robi miejsce w kolejnych rzędach. Wyłączone: swobodne nakładanie elementów.';
        properties.appendChild(flowTip);


        if (item.type === 'gallery_mosaic') {
            renderMosaicProperties(item);
            return;
        }

        if (item.type !== 'color_panel') window.builderTypography.panel(properties, item, render, 'fve-field');
        window.builderTypography.captionFields(properties, item, render, 'fve-field');
        window.BuilderButton.fields(properties, item, render, 'fve-field');
        if (item.type === 'icon') {
            field('Rozmiar ikony (px)', 'number', item.icon_size ?? 38,
                value => { item.icon_size = Math.max(16, Math.min(120, Number(value) || 38)); },
                { min: 16, max: 120, step: 1 });
            field('Kolor ikony', 'color', item.icon_color || '#222222',
                value => { item.icon_color = value; });
        }
        if (item.type === 'color_panel') {
            const backgroundField = document.createElement('label');
            backgroundField.className = 'fve-field';
            backgroundField.textContent = 'Kolor panelu';
            const colorInput = document.createElement('input');
            colorInput.type = 'color';
            colorInput.value = /^#[0-9a-f]{6}$/i.test(item.panel_color || '') ? item.panel_color : '#ffffff';
            colorInput.addEventListener('input', () => {
                item.panel_color = colorInput.value;
                const node = content.querySelector('.fve-element[data-id="' + CSS.escape(item.id) + '"]');
                if (node?.firstElementChild) {
                    const hex = colorInput.value;
                    const rgb = [1,3,5].map(i => parseInt(hex.slice(i,i+2),16));
                    node.firstElementChild.style.background = 'rgba(' + rgb.join(',') + ',' +
                        (Math.max(0, Math.min(100, Number(item.panel_opacity ?? 100))) / 100) + ')';
                }
            });
            backgroundField.appendChild(colorInput);
            properties.appendChild(backgroundField);
            field('Przezroczystość panelu (%)', 'number', item.panel_opacity ?? 100,
                value => { item.panel_opacity = Math.max(0, Math.min(100, Number(value) || 0)); render(); },
                { min:0, max:100, step:1 });
        }

        if (item.type === 'process_steps') {
            window.FoodfotoProcess.panel(properties, item, render);
        }
        if (['heading', 'text'].includes(item.type)) {
            const currentSemanticTag = item.semantic_tag
                || (item.type === 'heading' ? (item.heading_level || 'div') : 'p');
            selectField(
                'Znaczenie HTML / SEO',
                currentSemanticTag,
                [
                    ['p', 'Akapit (P)'],
                    ['h1', 'Nagłówek H1'],
                    ['h2', 'Nagłówek H2'],
                    ['h3', 'Nagłówek H3'],
                    ['small', 'Tekst pomocniczy (SMALL)'],
                    ['div', 'Neutralny kontener (DIV)']
                ],
                value => { item.semantic_tag = value; }
            );
        }
        if (item.type === 'gallery') {
            const fallbackCollectionId = galleryCollections.length ? Number(galleryCollections[0].id) : 0;
            if (!item.gallery_collection_id && fallbackCollectionId) {
                item.gallery_collection_id = fallbackCollectionId;
            }

            selectField(
                'Źródło galerii',
                item.gallery_collection_id || '',
                [['', 'Wybierz galerię'], ...galleryCollections.map(module => [module.id, module.name])],
                value => {
                    item.gallery_collection_id = value ? Number(value) : null;
                    item.gallery_id = null;
                    item.gallery_ids = [];
                }
            );

            const availableGalleries = item.gallery_collection_id
                ? galleries.filter(gallery => Number(gallery.gallery_collection_id) === Number(item.gallery_collection_id))
                : [];

            selectField('Tryb galerii', item.gallery_mode || 'all', [
                ['all', 'Wszystkie podgalerie z wybranej galerii'],
                ['selected', 'Wybrane podgalerie — osobny boks'],
                ['single', 'Jedna podgaleria — zdjęcia z powiększaniem']
            ], value => { item.gallery_mode = value; });

            if (item.gallery_mode === 'single') {
                selectField(
                    'Wybierz podgalerię',
                    item.gallery_id || '',
                    [['', 'Wybierz podgalerię'], ...availableGalleries.map(gallery => [gallery.id, gallery.title])],
                    value => { item.gallery_id = value ? Number(value) : null; }
                );
            }

            if (item.gallery_mode === 'selected') {
                if (!Array.isArray(item.gallery_ids)) item.gallery_ids = [];
                const selectedWrap = document.createElement('div');
                selectedWrap.className = 'fve-field';
                const selectedLabel = document.createElement('label');
                selectedLabel.textContent = 'Podgalerie w tym boksie';
                selectedWrap.appendChild(selectedLabel);
                availableGalleries.forEach(gallery => {
                    const row = document.createElement('label');
                    row.style.display = 'flex';
                    row.style.alignItems = 'center';
                    row.style.gap = '8px';
                    row.style.margin = '6px 0';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.checked = item.gallery_ids.map(Number).includes(Number(gallery.id));
                    checkbox.addEventListener('change', () => {
                        const ids = new Set(item.gallery_ids.map(Number));
                        checkbox.checked ? ids.add(Number(gallery.id)) : ids.delete(Number(gallery.id));
                        item.gallery_ids = [...ids];
                        render();
                    });
                    const name = document.createElement('span');
                    name.textContent = gallery.title;
                    row.appendChild(checkbox);
                    row.appendChild(name);
                    selectedWrap.appendChild(row);
                });
                properties.appendChild(selectedWrap);
            }

            if (item.gallery_mode !== 'single') {
                selectField('Wyrównanie okładek w boksie', item.gallery_align || 'left', [
                    ['left', 'Do lewej'],
                    ['center', 'Wyśrodkuj'],
                    ['right', 'Do prawej']
                ], value => { item.gallery_align = value; });

                const equalize = document.createElement('button');
                equalize.type = 'button';
                equalize.className = 'fve-button';
                equalize.style.width = '100%';
                equalize.style.marginBottom = '14px';
                equalize.textContent = 'Wyrównaj okładki — ten sam rozmiar';
                equalize.addEventListener('click', () => {
                    const ids = availableGalleries.map(gallery => Number(gallery.id));
                    if (!item.gallery_card_settings || typeof item.gallery_card_settings !== 'object' || Array.isArray(item.gallery_card_settings)) {
                        item.gallery_card_settings = {};
                    }
                    const count = Math.max(1, ids.length);
                    const gap = Math.max(0, Number(item.gallery_gap) || 0);
                    const width = Math.max(5, Math.min(100, (100 - Math.max(0, count - 1) * gap / 14) / count));
                    ids.forEach(id => {
                        item.gallery_card_settings[String(id)] = { width: width, x_offset: 0 };
                    });
                    render();
                    showProperties(item);
                });
                properties.appendChild(equalize);

                const selectedCover = availableGalleries.find(gallery => Number(gallery.id) === Number(item.gallery_selected_id));
                if (selectedCover) {
                    if (!item.gallery_card_settings || typeof item.gallery_card_settings !== 'object' || Array.isArray(item.gallery_card_settings)) {
                        item.gallery_card_settings = {};
                    }
                    const key = String(selectedCover.id);
                    if (!item.gallery_card_settings[key] || typeof item.gallery_card_settings[key] !== 'object') {
                        item.gallery_card_settings[key] = {};
                    }
                    const local = item.gallery_card_settings[key];

                    const selectedTitle = document.createElement('div');
                    selectedTitle.className = 'fve-field';
                    selectedTitle.innerHTML = '<label>Wybrana okładka</label><div style="padding:9px 10px;background:#f5f5f5;border-radius:5px;">' + selectedCover.title + '</div>';
                    properties.appendChild(selectedTitle);

                    field('Szerokość wybranej okładki (%)', 'number', local.width || '', value => {
                        local.width = Math.max(5, Math.min(100, Number(value) || 5));
                    }, { min: 5, max: 100, step: 0.1 });

                    field('Przesunięcie X wybranej okładki (px)', 'number', local.x_offset || 0, value => {
                        local.x_offset = Math.max(-2000, Math.min(2000, Number(value) || 0));
                    }, { min: -2000, max: 2000, step: 1 });
                } else {
                    const tip = document.createElement('div');
                    tip.className = 'fve-field';
                    tip.style.color = '#777';
                    tip.style.fontSize = '12px';
                    tip.textContent = 'Kliknij konkretną okładkę w podglądzie, aby zmienić tylko jej szerokość lub położenie.';
                    properties.appendChild(tip);
                }
            }

            field('Kolumny galerii', 'number', item.gallery_columns || 4, value => {
                item.gallery_columns = Math.max(1, Math.min(12, Math.round(Number(value) || 4)));
            }, { min: 1, max: 12, step: 1 });

            field('Odstęp galerii (px)', 'number', item.gallery_gap ?? 14, value => {
                item.gallery_gap = Math.max(0, Math.min(100, Math.round(Number(value) || 0)));
            }, { min: 0, max: 100, step: 1 });

            selectField('Proporcja kart galerii', item.gallery_ratio || '1 / .7', [
                ['1 / .7', 'Domyślna'],
                ['1 / 1', '1:1'],
                ['4 / 3', '4:3'],
                ['3 / 2', '3:2'],
                ['16 / 9', '16:9']
            ], value => { item.gallery_ratio = value; });
        }

        if (item.type === 'thumbnail_gallery') {
            window.ThumbnailGallery.properties(properties, item, photos, render, 'fve-field');
        }

        if (item.type !== "image" && item.type !== "thumbnail_gallery" && item.type !== "process_steps" && item.type !== "gallery_mosaic") {

            field(
                item.type === 'button' ? 'Tekst przycisku' : "Treść",
                item.type === "text"
                    ? "textarea"
                    : "text",
                item.content || "",
                function (value) {
                    item.content = value;
                }
            );

        }

        field(
            "Pozycja X (%)",
            "number",
            item.position_x,
            function (value) {
                item.position_x = Math.max(0, Math.min(100 - (Number(item.element_width) || 0), Number(value) || 0));
            },
            { min: 0, max: 100, step: 0.1 }
        );

        field(
            "Pozycja Y (%)",
            "number",
            item.position_y,
            function (value) {
                item.position_y = Math.max(0, Number(value) || 0);
                item.flow_anchor_contributions = inheritedFlowContributions(item.position_y * 9, item.id);
                item.flow_anchor = 0;
            },
            { min: 0, step: 0.1 }
        );

        field(
            "Szerokość boksu (%)",
            "number",
            item.element_width,
            function (value) {
                item.element_width =
                    Math.max(
                        1,
                        Math.min(
                            100 - (Number(item.position_x) || 0),
                            Number(value) || 1
                        )
                    );
                item.position_x = Math.max(0, Math.min(item.position_x || 0, 100 - item.element_width));
            },
            { min: 1, max: 100, step: 0.1 }
        );

        field(
            "Minimalna wysokość boksu (px)",
            "number",
            item.element_height || 0,
            function (value) {
                item.element_height = Math.max(0, Number(value) || 0);
                if (item.type === "image" && item.element_height > 0) {
                    item.image_height = item.element_height;
                }
            }
        );

        if (item.type === "image") {

            selectField(
                "Po kliknięciu zdjęcia",
                item.image_lightbox ? "__lightbox__" : (item.image_link || ""),
                [
                    ["", "Brak akcji"],
                    ["__lightbox__", "Powiększ zdjęcie"],
                    ...(window.builderButtonTargets || []).map(target => [target.url, target.label])
                ],
                function (value) {
                    if (value === "__lightbox__") {
                        item.image_lightbox = true;
                        item.image_link = null;
                    } else {
                        item.image_lightbox = false;
                        item.image_link = value || null;
                    }
                }
            );

            field(
                "Wysokość zdjęcia (px)",
                "number",
                item.image_height || 0,
                function (value) {
                    item.image_height = Math.max(0, Number(value) || 0);
                    item.element_height = item.image_height;
                },
                { min: 0, max: 2000, step: 1 }
            );

            selectField('Kadrowanie zdjęcia', item.image_fit || 'cover', [
                ['cover', 'Wypełnij / przytnij'],
                ['contain', 'Pokaż całe zdjęcie']
            ], value => { item.image_fit = value; });

            selectField('Proporcja zdjęcia (gdy wysokość = 0)', item.image_ratio || 'auto', [
                ['auto', 'Naturalna'],
                ['1 / 1', '1:1'],
                ['4 / 3', '4:3'],
                ['3 / 2', '3:2'],
                ['16 / 9', '16:9']
            ], value => { item.image_ratio = value; });

            field('Zaokrąglenie zdjęcia (px)', 'number', item.image_radius || 0, value => {
                item.image_radius = Math.max(0, Math.min(200, Number(value) || 0));
            }, { min: 0, max: 200, step: 1 });

            const imageTools = document.createElement('div');
            imageTools.className = 'fve-field';
            const imageToolsLabel = document.createElement('label');
            imageToolsLabel.textContent = 'Precyzyjne układanie zdjęć';
            imageTools.appendChild(imageToolsLabel);

            function imageTool(text, callback) {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'fve-button';
                b.style.width = '100%';
                b.style.marginBottom = '7px';
                b.textContent = text;
                b.addEventListener('click', () => {
                    callback();
                    render();
                    showProperties(item);
                });
                imageTools.appendChild(b);
            }

            imageTool('Zwykłe zdjęcia — ten sam rozmiar', () => {
                data.sections.filter(section => section.type === 'image' && section.id !== 'hero-image').forEach(section => {
                    section.element_width = item.element_width;
                    section.image_height = item.image_height || 0;
                    section.element_height = item.image_height || 0;
                    section.image_fit = item.image_fit || 'cover';
                    section.image_ratio = item.image_ratio || 'auto';
                    section.image_radius = item.image_radius || 0;
                });
            });

            imageTool('Zwykłe zdjęcia — jeden wiersz', () => {
                data.sections.filter(section => section.type === 'image' && section.id !== 'hero-image').forEach(section => {
                    section.position_y = Number(item.position_y) || 0;
                });
            });

            imageTool('Zwykłe zdjęcia — jedna kolumna', () => {
                data.sections.filter(section => section.type === 'image' && section.id !== 'hero-image').forEach(section => {
                    section.position_x = Number(item.position_x) || 0;
                });
            });

            imageTool('Rozłóż zdjęcia równo w poziomie', () => {
                const images = data.sections.filter(section => section.type === 'image' && section.id !== 'hero-image')
                    .sort((a, b) => (Number(a.position_x) || 0) - (Number(b.position_x) || 0));
                if (images.length < 2) return;
                const width = Math.min(Number(item.element_width) || 20, 100 / images.length);
                const free = Math.max(0, 100 - width * images.length);
                const gap = images.length > 1 ? free / (images.length - 1) : 0;
                images.forEach((section, index) => {
                    section.element_width = width;
                    section.position_x = index * (width + gap);
                    section.position_y = Number(item.position_y) || 0;
                });
            });

            imageTools.appendChild(document.createElement('hr'));
            properties.appendChild(imageTools);

            const button =
                document.createElement("button");

            button.type = "button";
            button.className =
                "fve-button dark";

            button.style.width = "100%";
            button.textContent =
                "Wybierz zdjęcie z biblioteki";

            button.addEventListener("click", function () {

                const overlay =
                    document.createElement("div");

                overlay.style.position = "fixed";
                overlay.style.inset = "0";
                overlay.style.zIndex = "100001";
                overlay.style.background = "rgba(0,0,0,.55)";
                overlay.style.display = "flex";
                overlay.style.alignItems = "center";
                overlay.style.justifyContent = "center";

                const modal =
                    document.createElement("div");

                modal.style.width = "760px";
                modal.style.maxWidth = "90vw";
                modal.style.maxHeight = "80vh";
                modal.style.background = "#fff";
                modal.style.padding = "20px";
                modal.style.overflow = "auto";
                modal.style.borderRadius = "8px";

                const title =
                    document.createElement("h3");

                title.textContent =
                    "Biblioteka zdjęć";

                modal.appendChild(title);

                const grid =
                    document.createElement("div");

                grid.style.display = "grid";
                grid.style.gridTemplateColumns =
                    "repeat(4,1fr)";
                grid.style.gap = "12px";

                photos.forEach(function (photo) {

                    const b =
                        document.createElement("button");

                    b.type = "button";
                    b.style.border = "1px solid #ddd";
                    b.style.background = "#fff";
                    b.style.padding = "0";
                    b.style.cursor = "pointer";

                    const img =
                        document.createElement("img");

                    img.src =
                        photo.url;

                    img.style.width = "100%";
                    img.style.height = "120px";
                    img.style.objectFit = "cover";

                    b.appendChild(img);

                    b.addEventListener("click", function () {

                        item.photo_id =
                            Number(photo.id);

                        item.photo_url =
                            photo.url;

                        item.photo_title =
                            photo.title || "";

                        overlay.remove();

                        render();
                        showProperties(item);
                    });

                    grid.appendChild(b);
                });

                modal.appendChild(grid);

                overlay.appendChild(modal);

                document.body.appendChild(overlay);

                overlay.addEventListener("click", function (event) {

                    if (event.target === overlay) {
                        overlay.remove();
                    }
                });
            });

            properties.appendChild(button);
        }
    }

    function addElement(type, options = {}) {

        const id =
            "fve-" +
            Date.now() +
            "-" +
            Math.random()
                .toString(36)
                .slice(2);

        const item = {
            id: id,
            type: type,
            content:
                type === "heading"
                    ? "Nowy nagłówek"
                    : type === "text"
                        ? "Nowy tekst"
                        : type === "button"
                            ? "Przycisk"
                            : type === "gallery"
                                ? "Galeria"
                                : "",
            style: {
                color: type === "button" ? "#ffffff" : "#222222",
                font_size:
                    type === "heading"
                        ? 42
                        : 18,
                font_weight: 400,
                text_align: "left",
                line_height: 1.6,
                letter_spacing: 0
            },
            flow_mode: options.silent ? 'free' : 'auto',
            flow_base_height: 0,
            position_x: 5,
            position_y: data.sections.length
                ? Math.max(...data.sections.map(section => Number(section.position_y) || 0)) + 10
                : 5,
            element_width:
                type === "image"
                    ? 55
                    : type === "heading"
                        ? 50
                        : 38,
            z_index:
                data.sections.length + 1,
            photo_id: null,
            photo_url: null,
            photo_title: "",
            image_link: null,
            image_lightbox: false,
            image_width: 100,
            image_radius: 0,
            image_fit: "cover",
            image_ratio: "auto",
            gallery_collection_id: galleryCollections.length ? Number(galleryCollections[0].id) : null,
            gallery_mode: "all",
            gallery_ids: [],
            gallery_align: "left",
            gallery_card_settings: {},
            gallery_columns: 4,
            gallery_gap: 14,
            gallery_ratio: "1 / .7"
        };

        // New elements start where the editor last clicked on empty canvas.
        // Layout presets and scripted additions keep their own explicit positions.
        if (!options.silent && nextInsertionPoint) {
            item.position_x = Math.min(nextInsertionPoint.x, Math.max(0, 100 - item.element_width));
            item.position_y = nextInsertionPoint.y;
            item.flow_anchor_contributions = inheritedFlowContributions(item.position_y * 9);
            item.flow_anchor = 0;
            nextInsertionPoint = null;
        }

        window.builderTypography.initialize(item);
        if (type === 'heading') item.heading_level = 'h2';
        if (type === 'process_steps') {
            window.FoodfotoProcess.initialize(item);
            item.element_width = 90;
            item.flow_mode = 'auto';
        }
        if (type === 'thumbnail_gallery') {
            window.ThumbnailGallery.initialize(item);
            item.element_width = 90;
        }
        if (type === 'gallery_mosaic') {
            item.position_x = 5;
            item.element_width = 90;
            item.element_height = 0;
            item.flow_mode = 'auto';
            item.flow_base_height = 0;
            item.mosaic_layout = 'editorial';
            item.mosaic_kicker = 'WYBRANE REALIZACJE';
            item.mosaic_title = 'Piękne obrazy\\ndla smacznych historii'.replace('\\n','\n');
            item.mosaic_intro = 'Fotografia, która podkreśla charakter Twojej marki — od opakowań po kampanie reklamowe.';
            item.mosaic_more_label = 'ZOBACZ WSZYSTKIE REALIZACJE';
            item.mosaic_more_link = '/#portfolio';
            item.mosaic_gap = 12;
            item.mosaic_row_height = 260;
            item.mosaic_side_width = 26;
            item.mosaic_title_size = 44;
            item.mosaic_descriptions = true;
            item.mosaic_selected_index = 0;
            item.mosaic_cards = Array.from({length:6}, (_,i) => {
                const page=mosaicPages[i] || null;
                const gallery=page ? null : (galleries.filter(g=>g.published !== false)[i] || null);
                return {
                  gallery_id:gallery ? Number(gallery.id) : null,
                  photo_id:page?.photo_id ? Number(page.photo_id) : null,
                  title:'',
                  category:page?.title || gallery?.title || '',
                  description:'',
                  link:page?.url || '',
                  crop_x:50,crop_y:50,action_text:'→'
                };
            });
        }
        data.sections.push(item);

        if (!options.silent) {
            selected = item.id;
            render();
            showProperties(item);
        }
        return item;
    }

    document
        .querySelectorAll("[data-fve-add]")
        .forEach(function (button) {

            button.addEventListener("click", function () {

                addElement(
                    button.dataset.fveAdd
                );
            });
        });

    // A layout preset inserts ordinary builder elements, not a locked component.
    // Every image, heading and text remains independently editable and movable.
    function addLayoutPreset(preset) {
        const lastBottom = data.sections.reduce((max, section) => {
            const top = Number(section.position_y) || 0;
            const height = (Number(section.element_height) || (
                ['image', 'gallery', 'thumbnail_gallery'].includes(section.type) ? 420 : 120
            )) / 9;
            return Math.max(max, top + height);
        }, 0);
        // A canvas click defines the vertical start of the next layout.
        // The normal preset keeps its designed horizontal alignment.
        const clickedPoint = nextInsertionPoint;
        const startY = clickedPoint ? clickedPoint.y : Math.max(5, lastBottom + 8);
        // Capture the VISUAL position of downstream content before adding the
        // preset; stored Y can be far above the rendered Y due to old anchors.
        const beforeInsertion = Array.from(content.querySelectorAll('.fve-element'))
            .map(node => ({id:node.dataset.id, top:node.offsetTop, height:node.offsetHeight}));
        const clickTop = startY * 9;
        const visibleBelow = clickedPoint
            ? beforeInsertion.filter(row => row.top >= clickTop - 50 && row.top + row.height > clickTop - 1)
            : [];
        const firstBelow = visibleBelow.length ? Math.min(...visibleBelow.map(row => row.top)) : null;
        const downstreamIds = firstBelow === null ? [] : beforeInsertion
            .filter(row => row.top >= firstBelow - 24)
            .map(row => row.id);
        const created = [];
        const flowGroup = 'layout-' + Date.now() + '-' + Math.random().toString(36).slice(2);
        const useGroupFlow = preset !== 'foodfoto-home';
        const priorContributions = useGroupFlow
            ? inheritedFlowContributions(startY * 9) : {};

        function insert(type, options) {
            const item = addElement(type, { silent: true });
            const originalStyle = { ...item.style };
            Object.assign(item, options);
            if (options.style) item.style = { ...originalStyle, ...options.style };
            if (useGroupFlow) {
                item.flow_group = flowGroup;
                item.flow_mode = 'auto';
                item.flow_base_height = 0;
                // Keep the visual insertion at the clicked point even if
                // earlier flow sources are already moving downstream rows.
                item.flow_anchor_contributions = {...priorContributions};
                item.flow_anchor = 0;
            }
            created.push(item.id);
            return item;
        }

        if (preset === 'foodfoto-home') {
            if (!isHomeBuilder) return;
            if (data.sections.some(section => section.foodfoto_preset === 'editorial-home-v1')) {
                alert('Kompozycja FoodFoto została już dodana. Możesz edytować jej elementy na stronie.');
                return;
            }
            const mark = (item) => {
                item.foodfoto_preset = 'editorial-home-v1';
                return item;
            };
            // Keep all existing SEO sections and the portfolio galleries unchanged.
            mark(insert('heading', {
                content: 'Obrazy, które pobudzają apetyt',
                position_x: 5, position_y: startY,
                element_width: 90, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 54, line_height: 1.12, text_align: 'center', font_weight: 400 }
            }));
            mark(insert('image', {
                position_x: 5, position_y: startY + 14, element_width: 43,
                image_height: 435, element_height: 435, image_fit: 'cover'
            }));
            mark(insert('heading', {
                content: 'Więcej niż zwykłe zdjęcia',
                position_x: 54, position_y: startY + 18, element_width: 40,
                heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 44, line_height: 1.12 }
            }));
            mark(insert('text', {
                content: 'W fotografii żywności liczy się każdy detal — światło, kolor, faktura i kompozycja. Tworzę obrazy, które podkreślają charakter produktu oraz opowiadają historię marki.',
                position_x: 54, position_y: startY + 32, element_width: 39,
                semantic_tag: 'p', style: { font_size: 20, line_height: 1.65 }
            }));
            mark(insert('button', {
                content: 'O MNIE', button_link: '/o-mnie',
                position_x: 54, position_y: startY + 53, element_width: 20
            }));

            const processY = startY + 78;
            mark(insert('heading', {
                content: 'Od pomysłu do kadru', position_x: 5, position_y: processY,
                element_width: 90, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 52, text_align: 'center', line_height: 1.15 }
            }));
            const titles = ['Koncepcja', 'Stylizacja', 'Fotografia'];
            const descriptions = [
                'Kompozycja dopasowana do produktu i przekazu marki.',
                'Aranżacja potraw, rekwizytów i najdrobniejszych detali.',
                'Światło i ujęcie, które nadają smak każdemu kadrowi.'
            ];
            for (let i = 0; i < 3; i++) {
                const x = 5 + i * 31;
                mark(insert('image', {
                    position_x: x, position_y: processY + 14,
                    element_width: 28, image_height: 310,
                    element_height: 310, image_fit: 'cover'
                }));
                mark(insert('heading', {
                    content: titles[i], position_x: x, position_y: processY + 53,
                    element_width: 28, heading_level: 'h3', semantic_tag: 'h3',
                    style: { font_size: 30, line_height: 1.2 }
                }));
                mark(insert('text', {
                    content: descriptions[i], position_x: x, position_y: processY + 62,
                    element_width: 28, semantic_tag: 'p',
                    style: { font_size: 18, line_height: 1.5 }
                }));
            }

            const aboutY = processY + 91;
            mark(insert('heading', {
                content: 'Doświadczenie, które widać w kadrze',
                position_x: 5, position_y: aboutY + 8,
                element_width: 43, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 44, line_height: 1.15 }
            }));
            mark(insert('text', {
                content: 'Od prawie 30 lat zajmuję się fotografią produktów spożywczych i potraw. Łączę doświadczenie fotograficzne z dbałością o stylizację żywności i indywidualny charakter każdej realizacji.',
                position_x: 5, position_y: aboutY + 23, element_width: 43,
                semantic_tag: 'p', style: { font_size: 20, line_height: 1.65 }
            }));
            mark(insert('button', {
                content: 'POZNAJ MNIE', button_link: '/o-mnie',
                position_x: 5, position_y: aboutY + 43, element_width: 22
            }));
            mark(insert('image', {
                position_x: 55, position_y: aboutY,
                element_width: 40, image_height: 430,
                element_height: 430, image_fit: 'cover'
            }));

            const contactY = aboutY + 72;
            mark(insert('image', {
                position_x: 5, position_y: contactY, element_width: 90,
                image_height: 480, element_height: 480, image_fit: 'cover'
            }));
            mark(insert('heading', {
                content: 'Porozmawiajmy o Twoim projekcie',
                position_x: 10, position_y: contactY + 13,
                element_width: 70, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 53, line_height: 1.15, color: '#ffffff' }
            }));
            mark(insert('text', {
                content: 'Fotografia i stylizacja żywności dla marek, które chcą opowiadać obrazem.',
                position_x: 10, position_y: contactY + 26,
                element_width: 68, semantic_tag: 'p',
                style: { font_size: 23, line_height: 1.5, color: '#ffffff' }
            }));
            mark(insert('button', {
                content: 'KONTAKT', button_link: '/kontakt',
                position_x: 10, position_y: contactY + 42,
                element_width: 22, style: { color: '#ffffff' }
            }));
        } else if (preset === 'image-text' || preset === 'text-image') {
            const imageFirst = preset === 'image-text';
            const imageX = imageFirst ? 5 : 54;
            const copyX = imageFirst ? 55 : 5;
            insert('image', {
                position_x: imageX, position_y: startY,
                element_width: 41, image_height: 390,
                element_height: 390, image_fit: 'cover'
            });
            insert('heading', {
                content: 'Nowy nagłówek sekcji',
                position_x: copyX, position_y: startY + 5,
                element_width: 40, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 48, line_height: 1.14 }
            });
            insert('text', {
                content: 'Wpisz tutaj własny opis. Wszystkie elementy można osobno przesuwać i edytować.',
                position_x: copyX, position_y: startY + 17,
                element_width: 38, semantic_tag: 'p',
                style: { font_size: 20, line_height: 1.55 }
            });
            insert('button', {
                content: 'ZOBACZ WIĘCEJ',
                position_x: copyX, position_y: startY + 32,
                element_width: 22
            });
        } else if (preset === 'process-collaboration') {
            // A single flexible section; all texts, icons and colors edit separately.
            // Its height is natural, allowing automatic flow without fragile overlays.
            const item = insert('process_steps', {
                position_x: 5, position_y: startY,
                element_width: 90, flow_mode: 'auto', flow_base_height: 0
            });
            window.FoodfotoProcess.initialize(item);
        } else if (preset === 'three-steps') {
            insert('heading', {
                content: 'Od pomysłu do kadru',
                position_x: 5, position_y: startY,
                element_width: 90, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 48, line_height: 1.14, text_align: 'center' }
            });
            const headings = ['Koncepcja', 'Stylizacja', 'Fotografia'];
            const labels = [
                'Pomysł i przygotowanie kompozycji.',
                'Dobór produktów, rekwizytów i detali.',
                'Światło, kadr i finalne zdjęcie.'
            ];
            for (let index = 0; index < 3; index++) {
                const x = 5 + index * 31;
                insert('image', {
                    position_x: x, position_y: startY + 12,
                    element_width: 28, image_height: 295,
                    element_height: 295, image_fit: 'cover'
                });
                insert('heading', {
                    content: headings[index],
                    position_x: x, position_y: startY + 48,
                    element_width: 28, heading_level: 'h3', semantic_tag: 'h3',
                    style: { font_size: 29, line_height: 1.2 }
                });
                insert('text', {
                    content: labels[index],
                    position_x: x, position_y: startY + 55,
                    element_width: 28, semantic_tag: 'p',
                    style: { font_size: 17, line_height: 1.5 }
                });
            }
        } else if (preset === 'contact-finale') {
            // Two stacked bands built from regular, separately editable CMS elements.
            // Photo slots intentionally start empty: the editor chooses photos from
            // their own library without uploading screenshots or hard-coding URLs.
            const add = (type, dx, dy, width, extra = {}) => insert(type, {
                position_x: dx, position_y: startY + dy, element_width: width, ...extra
            });
            const panel = (x, y, width, height, color, opacity, layer = 2) =>
                add('color_panel', x, y, width, {
                    element_height: height, panel_color: color,
                    panel_opacity: opacity, panel_layer: layer
                });
            const heading = (content, x, y, width, size, color, extra = {}) =>
                add('heading', x, y, width, {
                    content, heading_level: 'h2', semantic_tag: 'h2',
                    style: {
                        font_size: size, font_weight: 400, line_height: 1.09,
                        font_family: 'Georgia', color, ...extra
                    }
                });
            const label = (content, x, y, width, color) =>
                add('text', x, y, width, {
                    content, semantic_tag: 'small',
                    style: {
                        font_size: 13, font_weight: 600, line_height: 1.65,
                        letter_spacing: 3.5, color, font_family: 'Arial'
                    }
                });
            // Upper band — photography is placed between the dark base and overlay.
            panel(0, 0, 100, 385, '#393632', 100, 1);
            add('image', 0, 0, 100, {
                image_height: 385, element_height: 385, image_fit: 'cover',
                image_placeholder_hidden: true, image_layer: 10
            });
            panel(0, 0, 52, 385, '#1c1a19', 38, 15);
            label('DOBRA FOTOGRAFIA\nOTWIERA APETYT', 7, 6, 42, '#ffffff');
            heading('Smak zaczyna się\nod obrazu', 7, 15, 51, 72, '#ffffff');

            // Lower contact band — white background, two columns and a plant photo.
            const lower = 385 / 9;
            panel(0, lower, 100, 355, '#ffffff', 100, 2);
            label('MASZ POMYSŁ NA SESJĘ?', 8, lower + 6, 36, '#595d51');
            heading('Porozmawiajmy\no Twoim projekcie', 8, lower + 12, 39, 55, '#171717');
            add('text', 8, lower + 30, 39, {
                content: 'Chętnie odpowiem na pytania, doradzę i wspólnie ustalimy, jak najlepiej pokazać Twój produkt lub markę.',
                semantic_tag: 'p',
                style: {font_size: 18, line_height: 1.65, color: '#5f5f5f'}
            });
            panel(51, lower + 9, .2, 178, '#9c9c9c', 75, 30);
            add('text', 56, lower + 15, 6, {
                content: '✉', semantic_tag: 'div',
                style: {font_size: 49, color: '#252525', line_height: 1}
            });
            label('NAPISZ DO MNIE', 64, lower + 17, 22, '#2e2e2e');
            add('button', 64, lower + 23, 20, {
                content: 'magda@foodfoto.pl  →', button_link: 'mailto:magda@foodfoto.pl',
                button_new_tab: false, button_background: '#ffffff',
                button_border_color: '#333333', button_border_width: 1,
                button_radius: 0, button_padding_y: 14, button_padding_x: 12,
                style: {color: '#222222', font_size: 17, letter_spacing: .3}
            });
            add('image', 84, lower, 16, {
                image_height: 355, element_height: 355, image_fit: 'cover',
                image_placeholder_hidden: true, image_layer: 24
            });
        } else if (preset === 'contact-banner') {
            insert('image', {
                position_x: 5, position_y: startY, element_width: 90,
                image_height: 490, element_height: 490, image_fit: 'cover',
                image_ratio: 'auto'
            });
            insert('heading', {
                content: 'Porozmawiajmy o Twoim projekcie',
                position_x: 11, position_y: startY + 12,
                element_width: 66, heading_level: 'h2', semantic_tag: 'h2',
                style: { font_size: 52, font_weight: 500, line_height: 1.14, color: '#ffffff' }
            });
            insert('text', {
                content: 'Fotografia i stylizacja żywności dla marek, które chcą opowiadać obrazem.',
                position_x: 11, position_y: startY + 24, element_width: 58,
                semantic_tag: 'p', style: { font_size: 22, line_height: 1.45, color: '#ffffff' }
            });
            insert('button', {
                content: 'KONTAKT',
                button_link: '/kontakt',
                position_x: 11, position_y: startY + 39,
                element_width: 21,
                style: { color: '#ffffff' }
            });
        } else {
            return;
        }

        // Explicitly mark pre-existing elements below the clicked location.
        // This survives save/reload and keeps old rows together even when their
        // raw coordinates precede the click because of prior visual reflow.
        if (useGroupFlow && clickedPoint && created.length && downstreamIds.length) {
            const marker = data.sections.find(row => row.id === created[0]);
            if (marker) {
                marker.flow_insertion_targets = downstreamIds;
                marker.flow_insertion_existing = beforeInsertion.map(row => row.id);
                marker.flow_insertion_target_top = firstBelow;
            }
        }
        // A click is consumed by the complete preset, not by a single child.
        if (clickedPoint) nextInsertionPoint = null;
        selectedIds.clear();
        created.forEach(id => selectedIds.add(id));
        selected = created[0] || null;
        render();
        const primary = data.sections.find(item => item.id === selected);
        if (primary) showProperties(primary);
    }

    document.querySelectorAll('[data-fve-preset]').forEach(button => {
        button.addEventListener('click', () => addLayoutPreset(button.dataset.fvePreset));
    });

    function updateZoom() {

        pageWrap.style.transform =
            "scale(" + zoom + ")";

        pageWrap.style.marginBottom =
            Math.max(
                0,
                page.offsetHeight * zoom - page.offsetHeight
            ) + "px";

        zoomLabel.textContent =
            Math.round(zoom * 100) + "%";
    }

    document
        .getElementById("fve-zoom-in")
        .addEventListener("click", function () {

            zoom =
                Math.min(
                    1.2,
                    zoom + 0.1
                );

            updateZoom();
        });

    document
        .getElementById("fve-zoom-out")
        .addEventListener("click", function () {

            zoom =
                Math.max(
                    0.35,
                    zoom - 0.1
                );

            updateZoom();
        });

    document
        .getElementById("fve-fit")
        .addEventListener("click", function () {

            const available =
                document.getElementById("fve-stage")
                    .clientWidth - 100;

            zoom =
                Math.max(
                    0.35,
                    Math.min(
                        1,
                        available / 1200
                    )
                );

            updateZoom();
        });

    openButton.addEventListener("click", function () {

        try {
            renderHeader();
            render();
            editor.classList.add("open");
            window.ThumbnailGallery.fitCanvas(content);
            document.getElementById("fve-fit").click();
            launchStatus.textContent = "Edytor jest gotowy. Możesz otworzyć go ponownie lub wrócić do listy stron.";
        } catch (error) {
            editor.classList.remove("open");
            launchStatus.textContent = "Nie udało się otworzyć edytora. Odśwież stronę lub wróć do listy stron.";
            console.error("Nie udało się otworzyć edytora strony.", error);
        }
    });

    closeButton.addEventListener("click", function () {

        editor.classList.remove("open");
    });

    document
        .getElementById("fve-save")
        .addEventListener("click", async function () {

            const button =
                document.getElementById("fve-save");

            button.disabled = true;
            button.textContent =
                "Zapisywanie...";

            try {

                if (window.ThumbnailGallery?.syncAllFromDom) {
                    window.ThumbnailGallery.syncAllFromDom(data.sections, content);
                }
                if (window.ThumbnailGallery?.commitStoredSettings) {
                    window.ThumbnailGallery.commitStoredSettings(data.sections);
                }

                const response =
                    await fetch(
                        "{{ $builderSaveUrl }}",
                        {
                            method: "POST",
                            headers: {
                                "Content-Type":
                                    "application/json",

                                "Accept":
                                    "application/json",

                                "X-CSRF-TOKEN":
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .getAttribute(
                                            "content"
                                        )
                            },
                            body:
                                JSON.stringify({
                                    content: data
                                })
                        }
                    );

                if (!response.ok) {
                    throw new Error(
                        "Nie udało się zapisać."
                    );
                }

                const savedPayload = await response.json().catch(() => ({}));
                const savedThumbs = savedPayload.thumbnail_settings_count ?? null;
                button.textContent = savedThumbs === null
                    ? "✓ Zapisano"
                    : "✓ Zapisano (" + savedThumbs + " ustawień zdjęć)";

                setTimeout(function () {
                    button.textContent =
                        "Zapisz";
                }, 1200);

            } catch (error) {

                button.textContent =
                    "Błąd zapisu";

                setTimeout(function () {
                    button.textContent =
                        "Zapisz";
                }, 1800);
            }

            button.disabled = false;
        });

    window.addEventListener('resize', () => window.ThumbnailGallery.fitCanvas(content));
    updateZoom();

    openButton.disabled = false;
    launchStatus.textContent = "Edytor jest gotowy. Możesz otworzyć go ponownie lub wrócić do listy stron.";

    // Otwórz pełny edytor automatycznie po wejściu na stronę
    setTimeout(function () {
        openButton.click();
    }, 100);

});
</script>

<link rel="stylesheet" href="{{ asset('css/thumbnail-gallery.css') }}?v={{ @filemtime(public_path('css/thumbnail-gallery.css')) ?: time() }}">
<script src="{{ asset('js/builder-thumbnail-gallery.js') }}?v={{ @filemtime(public_path('js/builder-thumbnail-gallery.js')) ?: time() }}" defer></script>
@include('components.photo-library-dialog')
</x-app-layout>
