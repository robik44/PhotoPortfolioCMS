<x-app-layout>
    @include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
    <script src="{{ asset('js/site-typography.js') }}"></script>
    <script src="{{ asset('js/builder-button.js') }}"></script>
    @php
        $buttonTargets = collect([
            ['url' => url('/'), 'label' => 'START'],
            ['url' => url('/').'#portfolio', 'label' => 'Portfolio'],
        ])->concat(\App\Models\Page::where('published', true)->orderBy('title')->get()->map(fn ($target) => [
            'url' => isset(\App\Support\ContentPages::PAGES[$target->slug]) ? url('/'.$target->slug) : route('page.public', $target),
            'label' => 'Strona: '.$target->title,
        ]))->concat(\App\Models\Gallery::where('published', true)->orderBy('title')->get()->map(fn ($target) => [
            'url' => route('portfolio.gallery', $target), 'label' => 'Galeria: '.$target->title,
        ]));
    @endphp
    <script>window.builderButtonTargets = @json($buttonTargets->values());</script>
    <script>
        window.builderTypography = window.SiteTypography.create({
            families: @json($siteFonts['families']),
            choices: @json($siteFonts['choices']),
            defaults: @json($siteFonts['defaults']),
            textTypes: @json(\App\Services\SiteFontLibrary::TEXT_BLOCKS)
        });
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

    .fve-content {
        position: relative;
        min-height: 900px;
        background: #fff;
        padding-bottom: 80px;
    }

    .fve-footer {
        min-height: 110px;
        border-top: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px;
        box-sizing: border-box;
        color: #777;
        font-size: 12px;
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

    .fve-element.selected .fve-resize-handle {
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

    .fve-element.selected .fve-delete {
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

        </aside>

        <main class="fve-stage" id="fve-stage">

            <div class="fve-page-wrap" id="fve-page-wrap">

                <div class="fve-page" id="fve-page">

                    <header class="fve-header">

                        <div>
                            <div
                                class="fve-logo"
                                id="fve-logo"
                            ></div>

                            <div
                                class="fve-logo-subtitle"
                                id="fve-logo-subtitle"
                            ></div>
                        </div>

                        <nav
                            class="fve-menu"
                            id="fve-menu"
                        ></nav>

                    </header>

                    <main
                        class="fve-content"
                        id="fve-content"
                    ></main>

                    <footer
                        class="fve-footer"
                        id="fve-footer"
                    ></footer>

                </div>

            </div>

        </main>

        <aside class="fve-sidebar right">

            <div class="fve-sidebar-title">
                Właściwości
            </div>

            <div id="fve-properties">

                <p style="color:#888;line-height:1.6;font-size:13px;">
                    Kliknij element na stronie, aby go edytować.
                </p>

            </div>

        </aside>

    </div>

</div>

<script>
/* FULL VISUAL PAGE EDITOR */

document.addEventListener("DOMContentLoaded", function () {

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
        $galleriesForEditor = \App\Models\Gallery::with("photos")
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
                    "description" => $gallery->description,
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
            thumbnail_gallery: "Galeria miniaturek",
            button: "Przycisk",
            separator: "Separator"
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

        document.getElementById("fve-logo").textContent =
            settings.logo || "MAGDA GUGAŁA";

        document.getElementById("fve-logo-subtitle").textContent =
            settings.logo_subtitle || "FOTOGRAFIA";

        const menu =
            document.getElementById("fve-menu");

        menu.innerHTML = "";

        menuItems.forEach(function (item) {

            const a =
                document.createElement("span");

            a.textContent = item.title;

            a.style.cursor = "default";

            menu.appendChild(a);
        });
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
            box.appendChild(window.ThumbnailGallery.preview(item, photos));
        } else if (item.type === "image") {

            if (item.photo_url) {

                const image =
                    document.createElement("img");

                image.src = item.photo_url;

                image.alt =
                    item.photo_title || "Zdjęcie";

                image.style.width = "100%";

                if (item.image_height && Number(item.image_height) > 0) {
                    image.style.height =
                        Number(item.image_height) + "px";
                    image.style.objectFit = "cover";
                } else {
                    image.style.height = "auto";
                    image.style.objectFit = "contain";
                }

                image.style.borderRadius =
                    (item.image_radius || 0) + "px";

                if (item.image_link) {
                    image.style.cursor = "pointer";
                    image.title = "Zdjęcie ma ustawiony link";
                }

                box.appendChild(image);

            } else {

                box.innerHTML =
                    "<div style='padding:100px 20px;text-align:center;background:#f3f3f3;color:#999;'>Wybierz zdjęcie</div>";
            }

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
            button.style.color = item.style.color;
            button.style.whiteSpace = "normal";
            button.style.overflowWrap = "anywhere";
            window.BuilderButton.apply(button, item);

            box.appendChild(button);

        } else if (item.type === "gallery") {

            const selectedGallery = galleries.find(gallery => Number(gallery.id) === Number(item.gallery_id));
            const galleryList = item.gallery_mode === "single"
                ? (selectedGallery?.photos || []) : galleries;

            const grid = document.createElement("div");

            grid.style.display = "grid";
            grid.style.gridTemplateColumns =
                "repeat(4, minmax(0, 1fr))";
            grid.style.gap = "14px";
            grid.style.width = "100%";

            if (!galleryList.length) {

                const empty = document.createElement("div");

                empty.textContent = item.gallery_mode === 'single'
                    ? (selectedGallery ? 'Ta galeria nie zawiera jeszcze zdjęć.' : 'Wybierz galerię we właściwościach elementu.')
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

                    card.style.background = "#fff";
                    card.style.border = "1px solid #e5e5e5";
                    card.style.overflow = "hidden";

                    if (gallery.cover_url) {

                        const image =
                            document.createElement("img");

                        image.src = gallery.cover_url;
                        image.alt = gallery.alt || gallery.title || "";

                        image.style.display = "block";
                        image.style.width = "100%";
                        image.style.aspectRatio = "1 / 0.7";
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

                    title.style.padding = "12px";

                    card.appendChild(title);

                    grid.appendChild(card);
                });

                box.appendChild(grid);
            }

        } else {

            box.textContent =
                item.content || label(item.type);
        }

        window.builderTypography.imageCaption(box, item);
        return box;
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
                thumbnail_gallery: 30,
                text: 100,
                heading: 110,
                button: 120
            };

            element.style.zIndex =
                layerMap[item.type] || 100;

            if (selected === item.id) {
                element.classList.add("selected");
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

                selected = null;

                render();
                showProperties(null);
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
                selected = item.id;
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
            let startX = 0;
            let startY = 0;
            let startLeft = 0;
            let startTop = 0;

            element.addEventListener("mousedown", function (event) {

                if (event.target.closest(".fve-resize-handle")) {
                    return;
                }

                if (event.button !== 0) {
                    return;
                }

                dragging = true;

                selected = item.id;

                startX = event.clientX;
                startY = event.clientY;

                startLeft = item.position_x;
                startTop = item.position_y;

                element.style.cursor = "grabbing";

                event.preventDefault();
                event.stopPropagation();

                showProperties(item);
            });

            document.addEventListener("mousemove", function (event) {

                if (!dragging) {
                    return;
                }

                const rect =
                    content.getBoundingClientRect();

                const dx =
                    ((event.clientX - startX) / rect.width) * 100;

                const dyPx = (event.clientY - startY) / zoom;
                const dy = (dyPx / 900) * 100;

                item.position_x =
                    Math.max(
                        0,
                        Math.min(
                            100 - item.element_width,
                            startLeft + dx
                        )
                    );

                item.position_y = Math.max(0, startTop + dy);

                element.style.left =
                    item.position_x + "%";

                element.style.top =
                    ((Number(item.position_y) || 0) / 100 * 900) + "px";
            });

            document.addEventListener("mouseup", function () {

                if (!dragging) {
                    return;
                }

                dragging = false;

                element.style.cursor = "move";

                if (item.type === 'thumbnail_gallery') window.ThumbnailGallery.fitCanvas(content);
                showProperties(item);
            });

            element.addEventListener("click", function () {

                selected = item.id;

                showProperties(item);

                render();
            });

            content.appendChild(element);
        });

        requestAnimationFrame(function () {
            let maxBottom = 900;
            content.querySelectorAll(".fve-element").forEach(function (element) {
                maxBottom = Math.max(maxBottom, element.offsetTop + element.offsetHeight + 80);
            });
            content.style.height = maxBottom + "px";
            content.style.minHeight = maxBottom + "px";
            page.style.minHeight = (maxBottom + 206) + "px";
            updateZoom();
        });
        window.ThumbnailGallery.fitCanvas(content);
    }

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
                "<p style='color:#888;line-height:1.6;font-size:13px;'>Kliknij element na stronie, aby go edytować.</p>";

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

        window.builderTypography.panel(properties, item, render, 'fve-field');
        window.builderTypography.captionFields(properties, item, render, 'fve-field');
        window.BuilderButton.fields(properties, item, render, 'fve-field');
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
            selectField('Tryb galerii', item.gallery_mode || 'all', [['all', 'Wszystkie galerie'], ['single', 'Wybrana galeria']], value => { item.gallery_mode = value; });
            if (item.gallery_mode === 'single') {
                selectField('Wybierz galerię', item.gallery_id || '', [['', 'Wybierz galerię'], ...galleries.map(gallery => [gallery.id, gallery.title])], value => { item.gallery_id = value ? Number(value) : null; });
            }
        }


        if (item.type === 'thumbnail_gallery') {
            window.ThumbnailGallery.properties(properties, item, photos, render, 'fve-field');
        }

        if (item.type !== "image" && item.type !== "thumbnail_gallery") {

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
                item.position_x =
                    Number(value) || 0;
            }
        );

        field(
            "Pozycja Y (%)",
            "number",
            item.position_y,
            function (value) {
                item.position_y =
                    Number(value) || 0;
            }
        );

        field(
            "Szerokość boksu (%)",
            "number",
            item.element_width,
            function (value) {
                item.element_width =
                    Math.max(
                        5,
                        Math.min(
                            100 - (Number(item.position_x) || 0),
                            Number(value) || 5
                        )
                    );
                item.position_x = Math.max(0, Math.min(item.position_x || 0, 100 - item.element_width));
            }
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
                item.image_link || "",
                [
                    ["", "Brak linku"],
                    ...(window.builderButtonTargets || []).map(target => [target.url, target.label])
                ],
                function (value) {
                    item.image_link = value || null;
                }
            );

            field(
                "Wysokość zdjęcia (px)",
                "number",
                item.image_height || 0,
                function (value) {
                    item.image_height =
                        Math.max(
                            0,
                            Number(value) || 0
                        );
                    item.element_height = item.image_height;
                }
            );

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

    function addElement(type) {

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
            image_width: 100,
            image_radius: 0
        };

        window.builderTypography.initialize(item);
        if (type === 'heading') item.heading_level = 'h2';
        if (type === 'thumbnail_gallery') {
            window.ThumbnailGallery.initialize(item);
            item.element_width = 90;
        }
        data.sections.push(item);

        selected = item.id;

        render();
        showProperties(item);
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

                button.textContent =
                    "✓ Zapisano";

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

<link rel="stylesheet" href="{{ asset('css/thumbnail-gallery.css') }}">
<script src="{{ asset('js/builder-thumbnail-gallery.js') }}" defer></script>
@include('components.photo-library-dialog')
</x-app-layout>
