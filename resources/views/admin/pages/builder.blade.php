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

    <div class="builder-old-editor" style="padding:32px;">

        @php
            $photosForBuilder = \App\Models\Photo::orderBy("sort_order")
                ->orderBy("id")
                ->get()
                ->map(function ($photo) {
                    $filename = ltrim($photo->filename, "/");

                    if (str_starts_with($filename, "photos/")) {
                        $storagePath = $filename;
                    } else {
                        $storagePath = "photos/" . $filename;
                    }

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

        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:28px;">
            <div>
                <div style="font-size:12px; font-weight:700; letter-spacing:2px; color:#888; margin-bottom:8px;">
                    VISUAL EDITOR
                </div>

                <h1 style="font-family:Georgia,serif; font-size:44px; font-weight:400; margin:0;">
                    {{ $page->title }}
                </h1>

                <p style="margin-top:10px; color:#777;">
                    Edytuj wizualnie układ tej strony.
                </p>
            </div>

            <div style="display:flex; gap:12px; align-items:center;">
                <a
                    href="{{ $publicPageUrl }}"
                    target="_blank"
                    style="text-decoration:none; color:#555;"
                >
                    Podgląd ↗
                </a>

                <a
                    href="{{ $editPageUrl }}"
                    style="text-decoration:none; color:#555;"
                >
                    Ustawienia strony
                </a>

                <button
                    type="button"
                    id="builder-save"
                    style="
                        border:0;
                        background:#222;
                        color:#fff;
                        padding:12px 20px;
                        border-radius:6px;
                        cursor:pointer;
                        font-weight:600;
                    "
                >
                    Zapisz
                </button>
            </div>
        </div>

        <div
            id="builder-status"
            style="
                display:none;
                margin-bottom:18px;
                padding:12px 16px;
                border-radius:6px;
                background:#f1f1f1;
                color:#333;
            "
        ></div>

        <div
            style="
                display:grid;
                grid-template-columns:240px minmax(400px, 1fr) 300px;
                gap:20px;
                align-items:start;
            "
        >

            {{-- ELEMENTY --}}
            <div
                style="
                    background:#fff;
                    border:1px solid #ddd;
                    border-radius:8px;
                    padding:22px;
                "
            >
                <div
                    style="
                        font-size:12px;
                        font-weight:700;
                        letter-spacing:1.5px;
                        color:#777;
                        margin-bottom:18px;
                    "
                >
                    ELEMENTY
                </div>

                <div style="display:flex; flex-direction:column; gap:10px;">

                    <button type="button" class="builder-add" data-type="text">
                        + Tekst
                    </button>

                    <button type="button" class="builder-add" data-type="heading">
                        + Nagłówek
                    </button>

                    <button type="button" class="builder-add" data-type="image">
                        + Zdjęcie
                    </button>

                    <button type="button" class="builder-add" data-type="thumbnail_gallery">Galeria miniaturek</button>
                    <button type="button" class="builder-add" data-type="gallery">
                        + Galeria
                    </button>

                    <button type="button" class="builder-add" data-type="button">
                        + Przycisk
                    </button>

                    <button type="button" class="builder-add" data-type="section">
                        + Sekcja
                    </button>

                    <button type="button" class="builder-add" data-type="separator">
                        + Separator
                    </button>

                </div>
            </div>


            {{-- PODGLĄD --}}
            <div
                style="
                    background:#fff;
                    border:1px solid #ddd;
                    border-radius:8px;
                    overflow:hidden;
                "
            >
                <div
                    style="
                        height:48px;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        background:#fafafa;
                        border-bottom:1px solid #ddd;
                        color:#777;
                        font-size:13px;
                    "
                >
                    Podgląd strony
                </div>

                <div
                    id="builder-canvas"
                    style="
                        min-height:620px;
                        padding:24px;
                        background:#f3f3f3;
                    "
                >
                    <div
                        id="builder-page"
                        style="
                            min-height:560px;
                            background:#fff;
                            padding:40px;
                            box-sizing:border-box;
                        "
                    ></div>
                </div>
            </div>


            {{-- WŁAŚCIWOŚCI --}}
            <div
                style="
                    background:#fff;
                    border:1px solid #ddd;
                    border-radius:8px;
                    padding:22px;
                    min-height:260px;
                "
            >
                <div
                    style="
                        font-size:12px;
                        font-weight:700;
                        letter-spacing:1.5px;
                        color:#777;
                        margin-bottom:18px;
                    "
                >
                    WŁAŚCIWOŚCI
                </div>

                <div id="builder-properties">
                    <p style="color:#888; line-height:1.6;">
                        Wybierz element na podglądzie, aby edytować jego właściwości.
                    </p>
                </div>
            </div>

        </div>
    </div>


    <style>
        .builder-add {
            width:100%;
            text-align:left;
            background:#fff;
            border:1px solid #ddd;
            border-radius:6px;
            padding:14px 16px;
            font-size:14px;
            cursor:pointer;
            transition:.15s;
        }

        .builder-add:hover {
            background:#f7f7f7;
            border-color:#bbb;
        }

        .builder-element {
            position:relative;
            border:1px dashed #ccc;
            padding:28px;
            margin-bottom:20px;
            cursor:pointer;
            background:#fff;
            transition:.15s;
        }

        .builder-element:hover {
            border-color:#888;
        }

        .builder-element.selected {
            border:2px solid #222;
        }

        .builder-delete {
            position:absolute;
            top:8px;
            right:8px;
            border:0;
            background:#222;
            color:#fff;
            width:26px;
            height:26px;
            border-radius:50%;
            cursor:pointer;
            display:none;
            z-index:5;
        }

        .builder-element:hover .builder-delete,
        .builder-element.selected .builder-delete {
            display:block;
        }

        .builder-field {
            margin-bottom:16px;
        }

        .builder-field label {
            display:block;
            font-size:12px;
            color:#777;
            margin-bottom:6px;
        }

        .builder-field input,
        .builder-field textarea,
        .builder-field select {
            width:100%;
            box-sizing:border-box;
            border:1px solid #ddd;
            border-radius:5px;
            padding:10px;
            font-family:inherit;
            background:#fff;
        }

        .builder-field input[type="color"] {
            height:42px;
            padding:4px;
            cursor:pointer;
        }

        .builder-photo-preview {
            width:100%;
            max-height:180px;
            object-fit:cover;
            display:block;
            border-radius:5px;
            margin-bottom:12px;
        }

        .builder-no-photo {
            padding:35px 15px;
            text-align:center;
            background:#f5f5f5;
            color:#999;
            margin-bottom:12px;
            border-radius:5px;
        }
    </style>


    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const page = document.getElementById("builder-page");
            const properties = document.getElementById("builder-properties");
            const saveButton = document.getElementById("builder-save");
            const status = document.getElementById("builder-status");

            let selectedElement = null;

            const initialContent = @json($builder->content);

            let builderData = initialContent || {};

            const photos = @json($photosForBuilder);


            if (!builderData.version) {
                builderData.version = 1;
            }

            if (!builderData.settings) {
                builderData.settings = {
                    background_color: "#ffffff"
                };
            }

            if (!builderData.settings.background_color) {
                builderData.settings.background_color = "#ffffff";
            }

            if (!Array.isArray(builderData.sections)) {
                builderData.sections = [];
            }


            function createId() {
                return "element-" + Date.now() + "-" + Math.floor(Math.random() * 100000);
            }


            function elementLabel(type) {

                const labels = {
                    text: "Tekst",
                    heading: "Nagłówek",
                    image: "Zdjęcie",
                    gallery: "Galeria",
            thumbnail_gallery: "Galeria miniaturek",
                    button: "Przycisk",
                    section: "Sekcja",
                    separator: "Separator"
                };

                return labels[type] || "Element";
            }


            function defaultElement(type) {

                const base = {
                    id: createId(),
                    type: type,
                    style: {
                        color: type === "button" ? "#ffffff" : "#222222",
                        font_size: type === "heading" ? 42 : 18,
                        font_weight: type === "heading" ? 400 : 400,
                        text_align: "left",
                        line_height: 1.6,
                        letter_spacing: 0
                    }
                };


                window.builderTypography.initialize(base);

                if (type === "text") {
                    return {
                        ...base,
                        content: "Tutaj wpisz tekst."
                    };
                }


                if (type === "heading") {
                    return {
                        ...base,
                        content: "Nowy nagłówek"
                    };
                }


                if (type === "image") {
                    return {
                        ...base,
                        photo_id: null,
                        photo_url: null,
                        photo_title: "",
                        image_width: 100,
                        image_radius: 0
                    };
                }


                if (type === "thumbnail_gallery") {
                    window.ThumbnailGallery.initialize(base);
                    base.element_width = 90;
                    return base;
                }

                if (type === "gallery") {
                    return {
                        ...base,
                        content: "Galeria",
                        gallery_id: null,
                        gallery_mode: "all"
                    };
                }


                if (type === "button") {
                    return {
                        ...base,
                        content: "Przycisk"
                    };
                }


                if (type === "section") {
                    return {
                        ...base,
                        content: "Sekcja"
                    };
                }


                if (type === "separator") {
                    return {
                        ...base,
                        content: ""
                    };
                }


                return base;
            }


            function ensureElementStyle(item) {

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

                if (item.position_x === undefined) {
                    item.position_x = 20;
                }

                if (item.position_y === undefined) {
                    item.position_y = 20;
                }

                if (item.z_index === undefined) {
                    item.z_index = 1;
                }
            }


            function render() {

                page.innerHTML = "";

                page.style.position = "relative";
                page.style.minHeight = "650px";
                page.style.width = "100%";
                page.style.overflow = "hidden";
                page.style.backgroundColor =
                    builderData.settings.background_color || "#ffffff";


                if (builderData.sections.length === 0) {

                    const empty = document.createElement("div");

                    empty.style.padding = "80px 30px";
                    empty.style.textAlign = "center";
                    empty.style.color = "#999";

                    empty.innerHTML = `
                        <div style="font-size:18px; margin-bottom:8px;">
                            Pusta strona
                        </div>

                        <div style="font-size:13px;">
                            Dodaj pierwszy element z lewej strony.
                        </div>
                    `;

                    page.appendChild(empty);

                    return;
                }


                builderData.sections.forEach(function (item) {

                    ensureElementStyle(item);

                    const element = document.createElement("div");

                    element.className = "builder-element";
                    element.dataset.id = item.id;

                    ensureElementStyle(item);

                    element.style.position = "absolute";
                    element.style.left = item.position_x + "%";
                    element.style.top = item.position_y + "%";
                    element.style.zIndex = item.z_index;

                    element.style.maxWidth = "85%";
                    element.style.boxSizing = "border-box";
                    element.style.cursor = "move";

                    element.style.padding = "8px";

                    if (item.type === "text" || item.type === "heading") {
                        element.style.width = "auto";
                        element.style.minWidth = "80px";
                    }

                    if (item.type === "thumbnail_gallery") {
                        element.style.width = (item.element_width || 90) + "%";
                        element.dataset.thumbnailBlock = '';
                    }
                    if (item.type === "image") {
                        element.style.width =
                            Math.min(item.image_width || 100, 85) + "%";
                    }

                    if (selectedElement === item.id) {
                        element.classList.add("selected");

                        element.style.outline = "2px solid #222";
                        element.style.outlineOffset = "2px";
                        element.style.background = "rgba(255,255,255,.35)";
                    } else {
                        element.style.outline = "1px dashed rgba(0,0,0,.18)";
                        element.style.outlineOffset = "1px";
                        element.style.background = "rgba(255,255,255,.08)";
                    }


                    const content = document.createElement("div");

                    window.builderTypography.apply(content, item);
                    content.style.color = item.style.color;
                    content.style.fontSize =
                        item.style.font_size + "px";
                    content.style.fontWeight =
                        item.style.font_weight;
                    content.style.textAlign =
                        item.style.text_align;
                    content.style.lineHeight =
                        item.style.line_height;
                    content.style.letterSpacing =
                        item.style.letter_spacing + "px";


                    if (item.type === "thumbnail_gallery") {
                        content.appendChild(window.ThumbnailGallery.preview(item, photos));
                    } else if (item.type === "separator") {

                        content.innerHTML = `
                            <div style="
                                height:1px;
                                background:#bbb;
                                width:100%;
                            "></div>
                        `;

                    } else if (
                        item.type === "image" &&
                        item.photo_url
                    ) {

                        const image = document.createElement("img");

                        image.src = item.photo_url;
                        image.alt = item.photo_title || "Zdjęcie";

                        image.style.display = "block";
                        image.style.width =
                            (item.image_width || 100) + "%";
                        image.style.height =
                            item.image_height
                                ? item.image_height + "px"
                                : "auto";
                        image.style.objectFit = "cover";
                        image.style.borderRadius =
                            (item.image_radius || 0) + "px";
                        image.style.maxWidth = "100%";

                        content.appendChild(image);

                    } else if (item.type === "image") {

                        content.innerHTML = `
                            <div style="
                                padding:60px 20px;
                                text-align:center;
                                background:#f5f5f5;
                                color:#999;
                            ">
                                Wybierz zdjęcie
                            </div>
                        `;

                    } else {

                        content.textContent =
                            item.content || elementLabel(item.type);
                    }


                    window.builderTypography.imageCaption(content, item);
                    element.appendChild(content);


                    const deleteButton = document.createElement("button");

                    deleteButton.type = "button";
                    deleteButton.className = "builder-delete";
                    deleteButton.textContent = "×";

                    deleteButton.addEventListener("click", function (event) {

                        event.stopPropagation();

                        builderData.sections =
                            builderData.sections.filter(function (section) {
                                return section.id !== item.id;
                            });

                        selectedElement = null;

                        properties.innerHTML = `
                            <p style="color:#888; line-height:1.6;">
                                Wybierz element na podglądzie, aby edytować jego właściwości.
                            </p>
                        `;

                        render();
                    });


                    element.appendChild(deleteButton);


                    element.addEventListener("click", function () {

                        selectedElement = item.id;

                        showProperties(item);

                        render();
                    });

                    let dragging = false;
                    let startX = 0;
                    let startY = 0;
                    let startLeft = 0;
                    let startTop = 0;

                    element.addEventListener("mousedown", function (event) {

                        if (event.button !== 0) {
                            return;
                        }

                        dragging = true;

                        selectedElement = item.id;

                        startX = event.clientX;
                        startY = event.clientY;

                        startLeft = item.position_x;
                        startTop = item.position_y;

                        element.style.cursor = "grabbing";

                        event.preventDefault();
                        event.stopPropagation();
                    });

                    document.addEventListener("mousemove", function (event) {

                        if (!dragging) {
                            return;
                        }

                        const rect = page.getBoundingClientRect();

                        const deltaX =
                            ((event.clientX - startX) / rect.width) * 100;

                        const deltaY =
                            ((event.clientY - startY) / rect.height) * 100;

                        item.position_x =
                            Math.max(
                                0,
                                Math.min(100, startLeft + deltaX)
                            );

                        item.position_y =
                            Math.max(
                                0,
                                Math.min(100, startTop + deltaY)
                            );

                        element.style.left = item.position_x + "%";
                        element.style.top = item.position_y + "%";
                    });

                    document.addEventListener("mouseup", function () {

                        if (!dragging) {
                            return;
                        }

                        dragging = false;

                        element.style.cursor = "move";

                        showProperties(item);
                    });


                    page.appendChild(element);
                });
                if (builderData.sections.some(item => item.type === 'thumbnail_gallery')) window.ThumbnailGallery.fitCanvas(page);
            }


            function createField(
                labelText,
                type,
                value,
                onChange,
                options = {}
            ) {

                const wrapper = document.createElement("div");

                wrapper.className = "builder-field";


                const label = document.createElement("label");

                label.textContent = labelText;

                wrapper.appendChild(label);


                let input;


                if (type === "textarea") {

                    input = document.createElement("textarea");

                    input.rows = 6;

                } else if (type === "select") {

                    input = document.createElement("select");

                    options.choices.forEach(function (choice) {

                        const option = document.createElement("option");

                        option.value = choice.value;
                        option.textContent = choice.label;

                        input.appendChild(option);
                    });

                } else {

                    input = document.createElement("input");

                    input.type = type;
                }


                input.value = value;


                input.addEventListener("input", function () {

                    onChange(input.value);

                    render();

                    const current = builderData.sections.find(
                        function (element) {
                            return element.id === selectedElement;
                        }
                    );

                    if (current) {
                        showProperties(current);
                    }
                });


                wrapper.appendChild(input);

                properties.appendChild(wrapper);

                return input;
            }


            function showImageProperties(item) {

                if (item.photo_url) {

                    const image = document.createElement("img");

                    image.src = item.photo_url;

                    image.className = "builder-photo-preview";

                    properties.appendChild(image);

                } else {

                    const empty = document.createElement("div");

                    empty.className = "builder-no-photo";

                    empty.textContent = "Nie wybrano zdjęcia";

                    properties.appendChild(empty);
                }


                const libraryButton = document.createElement("button");

                libraryButton.type = "button";
                libraryButton.textContent = "Wybierz z biblioteki";

                libraryButton.style.width = "100%";
                libraryButton.style.padding = "12px";
                libraryButton.style.border = "1px solid #222";
                libraryButton.style.background = "#222";
                libraryButton.style.color = "#fff";
                libraryButton.style.borderRadius = "5px";
                libraryButton.style.cursor = "pointer";
                libraryButton.style.fontWeight = "600";

                properties.appendChild(libraryButton);


                libraryButton.addEventListener("click", function () {

                    const overlay = document.createElement("div");

                    overlay.style.position = "fixed";
                    overlay.style.inset = "0";
                    overlay.style.background = "rgba(0,0,0,.45)";
                    overlay.style.zIndex = "9999";
                    overlay.style.display = "flex";
                    overlay.style.alignItems = "center";
                    overlay.style.justifyContent = "center";


                    const modal = document.createElement("div");

                    modal.style.width = "20vw";
                    modal.style.minWidth = "360px";
                    modal.style.maxWidth = "520px";
                    modal.style.maxHeight = "70vh";
                    modal.style.background = "#fff";
                    modal.style.borderRadius = "10px";
                    modal.style.boxShadow = "0 20px 60px rgba(0,0,0,.25)";
                    modal.style.overflow = "hidden";


                    const modalHeader = document.createElement("div");

                    modalHeader.style.display = "flex";
                    modalHeader.style.alignItems = "center";
                    modalHeader.style.justifyContent = "space-between";
                    modalHeader.style.padding = "16px 18px";
                    modalHeader.style.borderBottom = "1px solid #eee";


                    const modalTitle = document.createElement("strong");

                    modalTitle.textContent = "Biblioteka zdjęć";

                    modalHeader.appendChild(modalTitle);


                    const closeButton = document.createElement("button");

                    closeButton.type = "button";
                    closeButton.textContent = "×";

                    closeButton.style.border = "0";
                    closeButton.style.background = "transparent";
                    closeButton.style.fontSize = "28px";
                    closeButton.style.cursor = "pointer";
                    closeButton.style.lineHeight = "1";


                    modalHeader.appendChild(closeButton);

                    modal.appendChild(modalHeader);


                    const modalBody = document.createElement("div");

                    modalBody.style.padding = "14px";
                    modalBody.style.overflowY = "auto";
                    modalBody.style.maxHeight = "calc(70vh - 65px)";


                    const photoGrid = document.createElement("div");

                    photoGrid.style.display = "grid";
                    photoGrid.style.gridTemplateColumns =
                        "repeat(2, minmax(0, 1fr))";
                    photoGrid.style.gap = "10px";


                    if (photos.length === 0) {

                        const empty = document.createElement("div");

                        empty.textContent =
                            "Brak zdjęć w bibliotece.";

                        empty.style.padding = "40px 10px";
                        empty.style.textAlign = "center";
                        empty.style.color = "#999";

                        photoGrid.appendChild(empty);

                    } else {

                        photos.forEach(function (photo) {

                            const photoItem =
                                document.createElement("button");

                            photoItem.type = "button";

                            photoItem.style.padding = "0";
                            photoItem.style.border =
                                Number(item.photo_id) === Number(photo.id)
                                    ? "3px solid #222"
                                    : "1px solid #ddd";

                            photoItem.style.background = "#fff";
                            photoItem.style.borderRadius = "5px";
                            photoItem.style.overflow = "hidden";
                            photoItem.style.cursor = "pointer";


                            const thumbnail =
                                document.createElement("img");

                            thumbnail.src = photo.url;

                            thumbnail.alt =
                                photo.title ||
                                photo.filename;

                            thumbnail.style.display = "block";
                            thumbnail.style.width = "100%";
                            thumbnail.style.height = "120px";
                            thumbnail.style.objectFit = "cover";


                            thumbnail.onerror = function () {

                                thumbnail.style.display = "none";

                                const error =
                                    document.createElement("div");

                                error.textContent =
                                    "Nie można wczytać zdjęcia";

                                error.style.height = "120px";
                                error.style.display = "flex";
                                error.style.alignItems = "center";
                                error.style.justifyContent = "center";
                                error.style.fontSize = "12px";
                                error.style.color = "#999";
                                error.style.background = "#f5f5f5";

                                photoItem.prepend(error);
                            };


                            photoItem.appendChild(thumbnail);


                            const name =
                                document.createElement("div");

                            name.textContent =
                                photo.title ||
                                photo.filename;

                            name.style.padding = "7px";
                            name.style.fontSize = "11px";
                            name.style.textAlign = "left";
                            name.style.whiteSpace = "nowrap";
                            name.style.overflow = "hidden";
                            name.style.textOverflow = "ellipsis";


                            photoItem.appendChild(name);


                            photoItem.addEventListener(
                                "click",
                                function () {

                                    item.photo_id =
                                        Number(photo.id);

                                    item.photo_url =
                                        photo.url;

                                    item.photo_title =
                                        photo.title || "";

                                    overlay.remove();

                                    render();

                                    showProperties(item);
                                }
                            );


                            photoGrid.appendChild(photoItem);
                        });
                    }


                    modalBody.appendChild(photoGrid);

                    modal.appendChild(modalBody);

                    overlay.appendChild(modal);

                    document.body.appendChild(overlay);


                    closeButton.addEventListener(
                        "click",
                        function () {
                            overlay.remove();
                        }
                    );


                    overlay.addEventListener(
                        "click",
                        function (event) {

                            if (event.target === overlay) {
                                overlay.remove();
                            }
                        }
                    );

                });


                if (item.photo_url) {

                    createField(
                        "Szerokość zdjęcia (%)",
                        "number",
                        item.image_width || 100,
                        function (value) {
                            item.image_width =
                                Math.max(
                                    1,
                                    Math.min(
                                        100,
                                        Number(value) || 100
                                    )
                                );
                        }
                    );


                    createField(
                        "Wysokość zdjęcia (px)",
                        "number",
                        item.image_height || 0,
                        function (value) {
                            item.image_height =
                                Math.max(
                                    0,
                                    Number(value) || 0
                                );
                        }
                    );


                    createField(
                        "Zaokrąglenie rogów (px)",
                        "number",
                        item.image_radius || 0,
                        function (value) {
                            item.image_radius =
                                Math.max(
                                    0,
                                    Number(value) || 0
                                );
                        }
                    );
                }
            }


            function showProperties(item) {

                if (!item) {
                    return;
                }

                ensureElementStyle(item);

                properties.innerHTML = "";
                if (item) window.builderTypography.field(properties, item, render, 'builder-field');
                if (item) window.builderTypography.captionFields(properties, item, render, 'builder-field');
                if (item) window.BuilderButton.fields(properties, item, render, 'builder-field');


                const title = document.createElement("div");

                title.style.fontWeight = item.style.font_weight;
                title.style.fontSize = "18px";
                title.style.marginBottom = "20px";
                title.textContent = elementLabel(item.type);

                properties.appendChild(title);


                if (item.type === "thumbnail_gallery") {
                    window.ThumbnailGallery.properties(properties, item, photos, render, 'builder-field');
                    return;
                }

                if (item.type === "image") {

                    showImageProperties(item);

                    return;
                }


                if (item.type !== "separator") {

                    createField(
                        "Treść",
                        item.type === "text"
                            ? "textarea"
                            : "text",
                        item.content || "",
                        function (value) {
                            item.content = value;
                        }
                    );


                    createField(
                        "Rozmiar czcionki (px)",
                        "number",
                        item.style.font_size,
                        function (value) {
                            item.style.font_size =
                                Number(value) || 1;
                        }
                    );


                    createField(
                        "Grubość",
                        "select",
                        item.style.font_weight,
                        function (value) {
                            item.style.font_weight =
                                Number(value);
                        },
                        {
                            choices: [
                                {
                                    value: "300",
                                    label: "Lekka"
                                },
                                {
                                    value: "400",
                                    label: "Normalna"
                                },
                                {
                                    value: "500",
                                    label: "Średnia"
                                },
                                {
                                    value: "600",
                                    label: "Półgruba"
                                },
                                {
                                    value: "700",
                                    label: "Pogrubiona"
                                }
                            ]
                        }
                    );


                    createField(
                        "Kolor tekstu",
                        "color",
                        item.style.color,
                        function (value) {
                            item.style.color = value;
                        }
                    );


                    createField(
                        "Wyrównanie",
                        "select",
                        item.style.text_align,
                        function (value) {
                            item.style.text_align = value;
                        },
                        {
                            choices: [
                                {
                                    value: "left",
                                    label: "Do lewej"
                                },
                                {
                                    value: "center",
                                    label: "Wyśrodkuj"
                                },
                                {
                                    value: "right",
                                    label: "Do prawej"
                                },
                                {
                                    value: "justify",
                                    label: "Wyjustuj"
                                }
                            ]
                        }
                    );


                    createField(
                        "Wysokość linii",
                        "number",
                        item.style.line_height,
                        function (value) {
                            item.style.line_height =
                                Number(value) || 1;
                        }
                    );


                    createField(
                        "Odstęp między literami (px)",
                        "number",
                        item.style.letter_spacing,
                        function (value) {
                            item.style.letter_spacing =
                                Number(value) || 0;
                        }
                    );
                }
            }


            document.querySelectorAll(".builder-add").forEach(
                function (button) {

                    button.addEventListener(
                        "click",
                        function () {

                            const type =
                                button.dataset.type;

                            const element =
                                defaultElement(type);

                            builderData.sections.push(
                                element
                            );

                            selectedElement =
                                element.id;

                            render();

                            showProperties(element);
                        }
                    );
                }
            );


            saveButton.addEventListener(
                "click",
                async function () {

                    saveButton.disabled = true;
                    saveButton.textContent =
                        "Zapisywanie...";

                    status.style.display =
                        "none";


                    try {

                        const response =
                            await fetch(
                                "{{ $builderSaveUrl }}",
                                {
                                    method:"POST",

                                    headers:{
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
                                            content:
                                                builderData
                                        })
                                }
                            );


                        const result =
                            await response.json();


                        if (!response.ok) {

                            throw new Error(
                                result.message ||
                                "Nie udało się zapisać."
                            );
                        }


                        status.textContent =
                            "✓ Układ strony został zapisany.";

                        status.style.display =
                            "block";


                    } catch (error) {

                        status.textContent =
                            "Błąd zapisu: " +
                            error.message;

                        status.style.display =
                            "block";


                    } finally {

                        saveButton.disabled =
                            false;

                        saveButton.textContent =
                            "Zapisz";
                    }
                }
            );


            render();


            if (builderData.sections.length > 0) {

                const first =
                    builderData.sections[0];

                selectedElement =
                    first.id;

                showProperties(first);

                render();
            }

        });
    

/* EYE DROPPER COLOR PICKER */

document.addEventListener("DOMContentLoaded", function () {

    function addEyeDropperButtons() {

        document
            .querySelectorAll('input[type="color"]')
            .forEach(function (input) {

                if (input.dataset.eyeDropperAdded === "1") {
                    return;
                }

                input.dataset.eyeDropperAdded = "1";

                const button = document.createElement("button");

                button.type = "button";
                button.textContent = "Pobierz kolor z ekranu";

                button.style.display = "block";
                button.style.marginTop = "8px";
                button.style.padding = "8px 12px";
                button.style.border = "1px solid #d1d5db";
                button.style.borderRadius = "6px";
                button.style.background = "#fff";
                button.style.color = "#222";
                button.style.cursor = "pointer";
                button.style.fontSize = "13px";

                button.addEventListener("click", async function () {

                    if (!window.EyeDropper) {
                        alert(
                            "Próbnik koloru nie jest obsługiwany przez tę przeglądarkę. Użyj Google Chrome."
                        );

                        return;
                    }

                    try {

                        const eyeDropper = new EyeDropper();

                        const result = await eyeDropper.open();

                        if (result && result.sRGBHex) {

                            input.value = result.sRGBHex;

                            input.dispatchEvent(
                                new Event("input", {
                                    bubbles: true
                                })
                            );

                            input.dispatchEvent(
                                new Event("change", {
                                    bubbles: true
                                })
                            );

                        }

                    } catch (error) {

                        // Anulowanie próbnika przez użytkownika
                        // nie wymaga żadnego komunikatu.

                    }

                });

                input.insertAdjacentElement(
                    "afterend",
                    button
                );
            });
    }

    addEyeDropperButtons();

    const observer = new MutationObserver(function () {
        addEyeDropperButtons();
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

});

</script>



<style>
    .builder-old-editor {
        display: none !important;
    }
</style>

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
        width: 1200px;
        margin: 0 auto;
        transform-origin: top center;
    }

    .fve-page {
        width: 1200px;
        min-height: 1900px;
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
        min-height: 1650px;
        background: #fff;
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
    }

    .fve-element.selected {
        outline: 2px solid #111;
        outline-offset: 3px;
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

        const box = document.createElement(item.type === 'heading' && ['h1', 'h2', 'h3'].includes(item.heading_level) ? item.heading_level : 'div');
        box.style.margin = '0';

        window.builderTypography.apply(box, item);

        box.style.color =
            item.style.color;

        box.style.fontSize =
            item.style.font_size + "px";

        box.style.fontWeight =
            item.style.font_weight;

        box.style.textAlign =
            item.style.text_align;

        box.style.lineHeight =
            item.style.line_height;

        box.style.letterSpacing =
            item.style.letter_spacing + "px";

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

            button.style.display = "inline-block";
            button.style.padding = "12px 22px";
            button.style.background = "#171717";
            button.style.color = item.style.color;
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
                    title.style.fontSize = item.style.font_size + "px";
                    title.style.fontWeight = item.style.font_weight;

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
            data.settings.background_color ||
            settings.background_color ||
            "#ffffff";

        content.style.backgroundImage = "none";
        content.style.backgroundSize = "auto";
        content.style.backgroundPosition = "initial";
        content.style.backgroundRepeat = "initial";

        data.sections.forEach(function (item, index) {

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
                item.position_y + "%";

            element.style.width =
                item.element_width + "%";

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

            let dragging = false;
            let startX = 0;
            let startY = 0;
            let startLeft = 0;
            let startTop = 0;

            element.addEventListener("mousedown", function (event) {

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

                const dy =
                    ((event.clientY - startY) / rect.height) * 100;

                item.position_x =
                    Math.max(
                        0,
                        Math.min(
                            100 - item.element_width,
                            startLeft + dx
                        )
                    );

                item.position_y =
                    Math.max(
                        0,
                        Math.min(
                            95,
                            startTop + dy
                        )
                    );

                element.style.left =
                    item.position_x + "%";

                element.style.top =
                    item.position_y + "%";
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
        window.ThumbnailGallery.fitCanvas(content);
    }

    function field(labelText, type, value, callback) {

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

        window.builderTypography.field(properties, item, render, 'fve-field');
        window.builderTypography.captionFields(properties, item, render, 'fve-field');
        window.BuilderButton.fields(properties, item, render, 'fve-field');
        if (item.type === 'heading') {
            selectField('Poziom nagłówka', item.heading_level || 'div', [['div', 'Dotychczasowy (bez zmiany)'], ['h1', 'H1'], ['h2', 'H2'], ['h3', 'H3']], value => { item.heading_level = value; });
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

            field(
                "Rozmiar czcionki",
                "number",
                item.style.font_size,
                function (value) {
                    item.style.font_size =
                        Number(value) || 1;
                }
            );

            field(
                "Kolor tekstu",
                "color",
                item.style.color,
                function (value) {
                    item.style.color = value;
                }
            );
        }

        if (window.builderTypography.isText(item)) {
            field('Grubość czcionki', 'number', item.style.font_weight, function (value) {
                item.style.font_weight = Math.max(100, Math.min(900, Number(value) || 400));
            });
            field('Odstęp między literami (px)', 'number', item.style.letter_spacing, function (value) {
                item.style.letter_spacing = Number(value) || 0;
            });
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
                            item.type === "image" ? 100 : 90,
                            Number(value) || 5
                        )
                    );
                if (item.type === "image") {
                    item.position_x = Math.max(0, Math.min(item.position_x || 0, 100 - item.element_width));
                }
            }
        );

        if (item.type === "image") {

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
            position_y: 5,
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
                1900 * zoom - 1900
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
