<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $gallery->title }}
        </h2>
    </x-slot>

    <div style="padding:32px 0;">
        <div style="
            max-width:1400px;
            margin:0 auto;
            padding:0 24px;
        ">

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:flex-start;
                gap:24px;
                margin-bottom:30px;
            ">
                <div>
                    <h1 style="
                        margin:0 0 8px;
                        font-size:30px;
                        font-weight:400;
                    ">
                        {{ $gallery->title }}
                    </h1>

                    @if ($gallery->description)
                        <p style="
                            margin:0;
                            color:#777;
                            font-size:14px;
                        ">
                            {{ $gallery->description }}
                        </p>
                    @endif
                </div>

                <div style="
                    display:flex;
                    gap:10px;
                    flex-wrap:wrap;
                ">
                    <a
                        href="{{ route('galleries.index', ['collection' => $gallery->gallery_collection_id]) }}"
                        style="
                            display:inline-block;
                            padding:11px 16px;
                            border-radius:6px;
                            background:#eee;
                            color:#171717;
                            text-decoration:none;
                            font-size:14px;
                        "
                    >
                        Powrót do galerii
                    </a>

                    <a
                        href="{{ route('galleries.library', $gallery) }}"
                        style="
                            display:inline-block;
                            padding:11px 16px;
                            border-radius:6px;
                            background:#171717;
                            color:#fff;
                            text-decoration:none;
                            font-size:14px;
                        "
                    >
                        + Wybierz z biblioteki
                    </a>
                </div>
            </div>

            @if (session('success'))
                <div style="
                    margin-bottom:22px;
                    padding:13px 16px;
                    border-radius:6px;
                    background:#dcfce7;
                    color:#166534;
                ">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div style="
                    margin-bottom:22px;
                    padding:13px 16px;
                    border-radius:6px;
                    background:#fee2e2;
                    color:#991b1b;
                ">
                    {{ session('error') }}
                </div>
            @endif

            <div
                id="save-message"
                style="
                    display:none;
                    margin-bottom:22px;
                    padding:13px 16px;
                    border-radius:6px;
                    background:#dcfce7;
                    color:#166534;
                "
            >
                Kolejność zdjęć została zapisana.
            </div>

            @if ($gallery->photos->count())

                <div style="
                    margin-bottom:18px;
                    color:#777;
                    font-size:13px;
                ">
                    Przeciągnij fotografie, aby zmienić ich kolejność w tej galerii.
                </div>

                <div
                    id="photo-grid"
                    style="
                        display:grid;
                        grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
                        gap:20px;
                    "
                >
                    @foreach ($gallery->photos as $photo)
                        @php
                            $imagePath = str_starts_with(
                                $photo->filename,
                                'photos/'
                            )
                                ? $photo->filename
                                : 'photos/' . $photo->filename;

                            $isCover = (bool) $photo->pivot->is_cover;
                        @endphp

                        <div
                            class="photo-card"
                            draggable="true"
                            data-id="{{ $photo->id }}"
                            style="
                                position:relative;
                                overflow:hidden;
                                background:#fff;
                                border:1px solid #ddd;
                                border-radius:9px;
                                transition:
                                    opacity .2s,
                                    transform .2s,
                                    box-shadow .2s;
                            "
                        >
                            <div
                                class="drag-handle"
                                style="
                                    position:absolute;
                                    z-index:3;
                                    top:9px;
                                    right:9px;
                                    width:34px;
                                    height:34px;
                                    display:flex;
                                    align-items:center;
                                    justify-content:center;
                                    border-radius:6px;
                                    background:rgba(255,255,255,.92);
                                    color:#171717;
                                    font-size:20px;
                                    cursor:grab;
                                    user-select:none;
                                "
                            >
                                ⋮⋮
                            </div>

                            <div style="
                                position:relative;
                                background:#eee;
                            ">
                                <img
                                    src="{{ asset('storage/' . $imagePath) }}"
                                    alt="{{ $photo->alt ?: $photo->title ?: 'Zdjęcie' }}"
                                    style="
                                        display:block;
                                        width:100%;
                                        height:220px;
                                        object-fit:cover;
                                    "
                                >

                                @if ($isCover)
                                    <div style="
                                        position:absolute;
                                        top:10px;
                                        left:10px;
                                        padding:7px 10px;
                                        border-radius:4px;
                                        background:#171717;
                                        color:#fff;
                                        font-size:11px;
                                        text-transform:uppercase;
                                        letter-spacing:.06em;
                                    ">
                                        Okładka
                                    </div>
                                @endif
                            </div>

                            <div style="
                                display:flex;
                                flex-direction:column;
                                gap:10px;
                                padding:14px;
                            ">
                                @if ($photo->title)
                                    <strong style="
                                        font-size:14px;
                                        font-weight:600;
                                    ">
                                        {{ $photo->title }}
                                    </strong>
                                @endif

                                <div style="
                                    color:#888;
                                    font-size:11px;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ">
                                    {{ basename($photo->filename) }}
                                </div>

                                @if (!$isCover)
                                    <form
                                        method="POST"
                                        action="{{ route('galleries.photos.cover', [$gallery, $photo]) }}"
                                    >
                                        @csrf

                                        <button
                                            type="submit"
                                            style="
                                                width:100%;
                                                padding:9px 10px;
                                                border:1px solid #171717;
                                                border-radius:5px;
                                                background:#fff;
                                                color:#171717;
                                                cursor:pointer;
                                                font-size:12px;
                                            "
                                        >
                                            Ustaw jako okładkę
                                        </button>
                                    </form>
                                @else
                                    <div style="
                                        padding:9px 10px;
                                        border-radius:5px;
                                        background:#f3f3f3;
                                        color:#555;
                                        text-align:center;
                                        font-size:12px;
                                    ">
                                        Zdjęcie okładkowe
                                    </div>
                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('galleries.photos.detach', [$gallery, $photo]) }}"
                                    onsubmit="return confirm('Usunąć zdjęcie z tej galerii? Fotografia pozostanie w Bibliotece.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        style="
                                            width:100%;
                                            padding:9px 10px;
                                            border:1px solid #b91c1c;
                                            border-radius:5px;
                                            background:#fff;
                                            color:#b91c1c;
                                            cursor:pointer;
                                            font-size:12px;
                                        "
                                    >
                                        Usuń z galerii
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

            @else

                <div style="
                    padding:60px 30px;
                    background:#fff;
                    border-radius:10px;
                    text-align:center;
                ">
                    <p style="
                        margin:0 0 20px;
                        color:#777;
                    ">
                        Ta galeria nie zawiera jeszcze żadnych zdjęć.
                    </p>

                    <a
                        href="{{ route('galleries.library', $gallery) }}"
                        style="
                            display:inline-block;
                            padding:12px 18px;
                            border-radius:6px;
                            background:#171717;
                            color:#fff;
                            text-decoration:none;
                            font-size:14px;
                        "
                    >
                        Wybierz zdjęcia z biblioteki
                    </a>
                </div>

            @endif
        </div>
    </div>

    <style>
        .photo-card.dragging {
            opacity: .35;
        }

        .photo-card.drag-over {
            transform: scale(1.025);
            box-shadow: 0 0 0 3px #171717;
        }

        @media (max-width:700px) {
            #photo-grid {
                grid-template-columns:repeat(2, minmax(0, 1fr)) !important;
                gap:12px !important;
            }

            .photo-card img {
                height:150px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const grid = document.getElementById('photo-grid');

            if (!grid) {
                return;
            }

            let dragged = null;

            grid.querySelectorAll('.photo-card').forEach(function (card) {
                card.addEventListener('dragstart', function () {
                    dragged = card;
                    card.classList.add('dragging');
                });

                card.addEventListener('dragend', function () {
                    card.classList.remove('dragging');

                    grid.querySelectorAll('.photo-card').forEach(function (item) {
                        item.classList.remove('drag-over');
                    });

                    dragged = null;
                    saveOrder();
                });

                card.addEventListener('dragover', function (event) {
                    event.preventDefault();

                    if (!dragged || dragged === card) {
                        return;
                    }

                    card.classList.add('drag-over');

                    const rect = card.getBoundingClientRect();
                    const insertAfter =
                        event.clientY > rect.top + rect.height / 2;

                    if (insertAfter) {
                        card.after(dragged);
                    } else {
                        card.before(dragged);
                    }
                });

                card.addEventListener('dragleave', function () {
                    card.classList.remove('drag-over');
                });

                card.addEventListener('drop', function (event) {
                    event.preventDefault();
                    card.classList.remove('drag-over');
                });
            });

            function saveOrder() {
                const photos = Array.from(
                    grid.querySelectorAll('.photo-card')
                ).map(function (card) {
                    return Number(card.dataset.id);
                });

                fetch(
                    '{{ route('galleries.photos.reorder', $gallery) }}',
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            photos: photos
                        })
                    }
                )
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Błąd zapisu');
                    }

                    return response.json();
                })
                .then(function () {
                    const message =
                        document.getElementById('save-message');

                    if (message) {
                        message.style.display = 'block';

                        setTimeout(function () {
                            message.style.display = 'none';
                        }, 2000);
                    }
                })
                .catch(function (error) {
                    console.error(error);
                    alert('Nie udało się zapisać kolejności zdjęć.');
                });
            }
        });
    </script>
</x-app-layout>
