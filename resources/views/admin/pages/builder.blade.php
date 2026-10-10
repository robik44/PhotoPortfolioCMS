<x-app-layout>
    @include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
    <script src="{{ asset('js/site-typography.js') }}"></script>
    <script src="{{ asset('js/builder-button.js') }}"></script>
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
                <div class="fve-ruler-top" id="fve-ruler-top"></div>
                <div class="fve-ruler-left" id="fve-ruler-left"></div>
                <div class="fve-ruler-canvas">
                <div class="fve-page" id="fve-page">

                    <div class="fve-public-header-preview">
                        @include('components.site-header', [
                            'settings' => $builderHeaderSettings,
                            'menuItems' => $builderHeaderMenuItems,
                        ])
                    </div>

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
        const y = (Number(item.position_y) || 0) / 100 * 900;
        const width = (Number(item.element_width) || 0) / 100 * 1400;
        const height = node ? node.getBoundingClientRect().height / zoom : Math.max(24, Number(item.element_height) || 24);
        return { x, y, width, height, right: x + width, bottom: y + height, cx: x + width / 2, cy: y + height / 2 };
    }

    function snapGroup(groupIds, proposedDxPx, proposedDyPx, starts) {
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

        const xTargets = [0, 700, 1400];
        const yTargets = [0];
        data.sections.forEach(other => {
            if (groupIds.includes(other.id)) return;
            const g = elementGeometry(other);
            xTargets.push(g.x, g.cx, g.right);
            yTargets.push(g.y, g.cy, g.bottom);
        });

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
        // Header is rendered by the same shared Blade component as the public site.
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

                const snapped = snapGroup(dragIds, rawDxPx, rawDyPx, dragStarts);

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
            });

            document.addEventListener("mouseup", function () {
                if (!dragging) return;

                dragging = false;
                hideSnapGuides();

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
            let maxBottom = 900;
            content.querySelectorAll(".fve-element").forEach(function (element) {
                maxBottom = Math.max(maxBottom, element.offsetTop + element.offsetHeight + 40);
            });
            content.style.height = maxBottom + "px";
            content.style.minHeight = maxBottom + "px";
            page.style.minHeight = "0";
            updateZoom();
            renderRulers();
        });
        window.ThumbnailGallery.fitCanvas(content);
    }

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
