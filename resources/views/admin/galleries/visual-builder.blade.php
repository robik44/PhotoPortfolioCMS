<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">Wizualny builder galerii — {{ $gallery->title }}</h2>
    </x-slot>

    @php
        $photoPayload = $gallery->photos->map(fn ($photo) => [
            'id' => $photo->id,
            'title' => $photo->title ?: ('Fotografia '.$photo->id),
            'alt' => $photo->alt ?: '',
            'url' => $photo->thumbnailUrl(),
        ])->values();
        $payload = [
            'layout' => $layout,
            'gallery' => [
                'id' => $gallery->id,
                'title' => $gallery->title,
                'description' => $gallery->description ?: '',
                'services' => $relatedServices,
            ],
            'photos' => $photoPayload,
            'scope' => $scope,
            'editUrl' => route('galleries.edit', $gallery),
        ];
    @endphp

    <div id="gallery-visual-editor" class="gv-shell">
        <script id="gv-initial" type="application/json">{!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) !!}</script>

        <div class="gv-toolbar">
            <a class="gv-tool" href="{{ route('galleries.index', ['collection' => $gallery->gallery_collection_id]) }}">← Galerie</a>
            <a class="gv-tool" href="{{ route('galleries.edit', $gallery) }}">Edytuj treść i SEO</a>
            <a class="gv-tool" href="{{ route('portfolio.gallery', $gallery) }}" target="_blank" rel="noopener noreferrer">Strona publiczna ↗</a>
            <span style="flex:1"></span>
            <a class="gv-tool {{ $scope === 'global' ? 'active' : '' }}" href="{{ route('galleries.builder', ['gallery' => $gallery->id, 'scope' => 'global']) }}">Wspólny szablon</a>
            <a class="gv-tool {{ $scope === 'gallery' ? 'active' : '' }}" href="{{ route('galleries.builder', ['gallery' => $gallery->id, 'scope' => 'gallery']) }}">Ta galeria</a>
        </div>

        @if(session('success'))
            <div class="gv-notice">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="gv-error">{{ $errors->first() }}</div>
        @endif
        <p class="gv-info">
            @if($scope === 'global')
                Edytujesz <strong>wspólny szablon wszystkich galerii</strong>. Opublikowany szablon działa we wszystkich galeriach bez indywidualnych nadpisań.
                Zawartość tytułów, opisów i fotografii jest pobierana z każdej galerii oddzielnie.
            @else
                Edytujesz <strong>wyłącznie {{ $gallery->title }}</strong>. Zmienione właściwości nadpisują wspólny szablon; pozostałe nadal dziedziczą jego ustawienia.
            @endif
            Zapis szkicu nie zmienia publicznej strony. @if($hasPublished) <strong>Ten zakres ma już opublikowany układ.</strong> @endif
        </p>

        <div class="gv-main">
            <aside class="gv-side">
                <h3>Elementy</h3>
                <div id="gv-layers" class="gv-layers"></div>
                <h3>Dodaj element</h3>
                <button type="button" class="gv-add" data-gv-add="text">+ Pole tekstowe</button>
                <button type="button" class="gv-add" data-gv-add="button">+ Przycisk</button>
                <button type="button" class="gv-add" data-gv-add="divider">+ Separator</button>
                @if($scope === 'gallery')
                    <button type="button" class="gv-add" data-gv-add="image">+ Zdjęcie z galerii</button>
                @endif
                <a class="gv-tool gv-add" href="{{ route('galleries.library', $gallery) }}">+ Biblioteka zdjęć ↗</a>
                <p class="gv-help">Złap element na podglądzie i przesuń. Niebieskim uchwytem w narożniku zmienisz jego szerokość.</p>
            </aside>

            <section class="gv-preview">
                <div class="gv-preview-top">
                    <strong>Podgląd {{ $gallery->title }}</strong>
                    <button type="button" class="gv-tool active" data-gv-device="desktop">Komputer</button>
                    <button type="button" class="gv-tool" data-gv-device="tablet">Tablet</button>
                    <button type="button" class="gv-tool" data-gv-device="mobile">Telefon</button>
                    <label><input type="checkbox" id="gv-snap" checked> Przyciąganie do siatki</label>
                </div>
                <div class="gv-preview-top">
                    <label for="gv-mode">Układ fotografii</label>
                    <select id="gv-mode">
                        <option value="grid" @selected($layout['photo_mode'] === 'grid')>Siatka</option>
                        @if($scope === 'gallery')
                            <option value="free" @selected($layout['photo_mode'] === 'free')>Swobodny — osobno każde zdjęcie</option>
                        @endif
                    </select>
                    <span class="gv-muted">Zdjęcia i ALT pozostają w bibliotece CMS.</span>
                </div>
                <div class="gv-scroll">
                    <div id="gv-canvas" class="gv-canvas"></div>
                </div>
            </section>

            <aside class="gv-side gv-properties">
                <h3>Właściwości elementu</h3>
                <div id="gv-inspector"></div>
            </aside>
        </div>

        <form class="gv-save" method="POST" action="{{ route('galleries.builder.save', $gallery) }}">
            @csrf
            <input type="hidden" name="scope" value="{{ $scope }}">
            <input type="hidden" id="gv-payload" name="layout">
            <span id="gv-status" class="gv-status">Projekt gotowy do edycji</span>
            <button type="button" id="gv-reset-layout" class="gv-tool">Odrzuć niezapisane zmiany</button>
            <button type="submit" name="action" value="save" class="gv-tool">Zapisz szkic</button>
            <button type="submit" name="action" value="publish" class="gv-publish">Opublikuj układ</button>
        </form>
        <p class="gv-help gv-note">Bezpieczny start: dopóki nie klikniesz „Opublikuj układ”, publiczne galerie korzystają z dotychczasowego wyglądu. Builder nie zmienia SEO, adresów, zdjęć ani ich ALT.</p>
    </div>

    <style>
        .gv-shell{margin:24px auto 70px;max-width:1900px;padding:0 16px;color:#222;font-family:Arial,sans-serif}
        .gv-toolbar,.gv-preview-top,.gv-save{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .gv-toolbar{margin:0 0 14px}
        .gv-tool,.gv-add,.gv-publish{display:inline-block;border:1px solid #d1d5db;border-radius:5px;background:#fff;
            color:#222;padding:10px 13px;text-decoration:none;font-size:13px;cursor:pointer}
        .gv-tool.active{border-color:#111;background:#222;color:#fff}
        .gv-publish{background:#222;color:#fff;border-color:#222;font-weight:600}
        .gv-add{display:block;width:100%;margin-bottom:8px;text-align:left}
        .gv-info,.gv-help,.gv-muted{font-size:13px;line-height:1.6;color:#656565}
        .gv-info{background:#f7f7f4;border:1px solid #e4e4df;border-radius:5px;padding:12px 16px;margin-bottom:16px}
        .gv-help{margin-top:20px}
        .gv-notice{padding:12px;background:#edf7ee;border:1px solid #cce9cc;margin-bottom:15px}
        .gv-error{padding:12px;background:#fee2e2;border:1px solid #fca5a5;margin-bottom:15px}
        .gv-main{display:grid;grid-template-columns:minmax(195px,225px) minmax(0,1fr) minmax(235px,285px);gap:12px;align-items:start}
        .gv-side,.gv-preview{border:1px solid #e1e1df;background:white;border-radius:7px}
        .gv-side{padding:16px;max-height:80vh;overflow-y:auto;position:sticky;top:20px}
        .gv-side h3{font-size:15px;margin:8px 0 14px;font-weight:700}
        .gv-layers{padding-bottom:16px;border-bottom:1px solid #eee;margin-bottom:16px}
        .gv-layer{display:block;background:#f9f9f8;border:1px solid #ddd;text-align:left;padding:9px 10px;font-size:12px;
            cursor:pointer;width:100%;margin:4px 0;border-radius:3px}
        .gv-layer.active{border-color:#2563eb;background:#eff6ff}
        .gv-preview{min-width:0}
        .gv-preview-top{padding:11px 13px;border-bottom:1px solid #ececec;font-size:12px}
        .gv-preview-top strong{margin-right:auto}
        .gv-preview-top select{padding:8px;border:1px solid #ccc}
        .gv-scroll{overflow:auto;background:#e9eae9;padding:22px;max-height:78vh}
        .gv-canvas{width:1200px;height:900px;position:relative;background:#f9f9f7;border:1px solid #dadada;
            background-image:linear-gradient(to right,rgba(0,0,0,.025) 1px,transparent 1px),
                linear-gradient(to bottom,rgba(0,0,0,.025) 1px,transparent 1px);
            background-size:50px 50px}
        .gv-element{position:absolute;cursor:move;touch-action:none;min-height:26px;border:1px solid transparent}
        .gv-element:hover{border-color:#b6c6e7}
        .gv-selected{outline:2px solid #2563eb;outline-offset:1px;background:rgba(37,99,235,.025);z-index:2}
        .gv-grip{display:none;position:absolute;bottom:-8px;right:-8px;background:#2563eb;color:#fff;font-size:12px;
            width:24px;height:24px;justify-content:center;align-items:center;cursor:ew-resize;touch-action:none}
        .gv-selected>.gv-grip{display:flex}
        .gv-text{white-space:pre-line;margin:0;word-break:normal}
        .gv-related{display:flex;gap:24px;flex-wrap:wrap;text-decoration:underline}
        .gv-photos-grid{display:grid;align-items:start}
        .gv-thumb{background:#eee;cursor:grab;min-width:0}
        .gv-thumb img{display:block;width:100%;max-width:100%}
        .gv-free-img img{display:block;width:100%;object-fit:contain}
        .gv-divider{height:2px;width:100%;margin-top:12px}
        .gv-button{display:inline-block;padding:12px 20px;border:1px solid currentColor;cursor:move}
        .gv-control{margin:13px 0}
        .gv-field-label{display:block;font-size:12px;margin-bottom:5px;color:#545454}
        .gv-control input,.gv-control select{width:100%;padding:9px;border:1px solid #d1d5db;border-radius:3px;font-size:13px;background:#fff;color:#222}
        .gv-danger{border:1px solid #c33;color:#c33;background:#fff;padding:9px;margin-top:14px;cursor:pointer}
        .gv-edit-content{display:block;margin-top:16px;text-decoration:underline;color:#444;font-size:12px}
        .gv-save{margin-top:15px;background:#fff;padding:15px;border:1px solid #ddd;border-radius:6px;position:sticky;bottom:0;z-index:10}
        .gv-save .gv-publish{margin-left:0}
        .gv-status{margin-right:auto;color:#777;font-size:13px}
        .gv-status-dirty{margin-right:auto;color:#b45309;font-size:13px;font-weight:600}
        .gv-note{margin-bottom:30px}
        .gv-canvas.gv-tablet{width:760px}
        .gv-canvas.gv-tablet .gv-element{width:92%!important;left:4%!important;position:relative!important;top:auto!important;
            margin:20px 0;cursor:pointer}
        .gv-canvas.gv-tablet .gv-element .gv-photos-grid{grid-template-columns:repeat(2, minmax(0,1fr))!important}
        .gv-canvas.gv-mobile{width:375px}
        .gv-canvas.gv-mobile .gv-element{width:92%!important;left:4%!important;position:relative!important;top:auto!important;
            margin:14px 0;cursor:pointer}
        .gv-canvas.gv-mobile .gv-element .gv-photos-grid{grid-template-columns:repeat(1,minmax(0,1fr))!important}
        .gv-canvas.gv-mobile .gv-element .gv-free-img{max-width:100%}
        .gv-canvas.gv-mobile .gv-element .gv-free-img img{height:auto!important}
        @media(max-width:1200px){.gv-main{grid-template-columns:170px minmax(0,1fr) 220px}}
        @media(max-width:850px){.gv-main{grid-template-columns:1fr}.gv-side{position:static;max-height:none}}
    </style>
    <script defer src="{{ asset('js/gallery-visual-builder.js') }}?v=1"></script>
</x-app-layout>