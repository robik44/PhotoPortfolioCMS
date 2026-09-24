<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Galerie
        </h2>
    </x-slot>

    <div style="padding:32px 0;">

        <div style="
            max-width:1400px;
            margin:0 auto;
            padding:0 24px;
        ">

            @if (session('success'))
                <div style="
                    margin-bottom:24px;
                    padding:14px 18px;
                    border-radius:6px;
                    background:#dcfce7;
                    color:#166534;
                ">
                    {{ session('success') }}
                </div>
            @endif

            <div style="
                display:flex;
                justify-content:space-between;
                align-items:center;
                gap:20px;
                margin-bottom:30px;
            ">

                <div>
                    <h1 style="
                        margin:0;
                        font-size:30px;
                        font-weight:400;
                    ">
                        Galerie
                    </h1>

                    <p style="
                        margin:8px 0 0;
                        color:#777;
                    ">
                        Przeciągnij galerie, aby zmienić ich kolejność.
                    </p>
                </div>

                <a
                    href="{{ route('galleries.create') }}"
                    style="
                        display:inline-block;
                        background:#171717;
                        color:#fff;
                        padding:13px 20px;
                        border-radius:6px;
                        text-decoration:none;
                        font-size:14px;
                        white-space:nowrap;
                    "
                >
                    + Dodaj galerię
                </a>

            </div>

            @if ($galleries->count())

                <div
                    id="gallery-grid"
                    style="
                        display:grid;
                        grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
                        gap:28px;
                    "
                >

                    @foreach ($galleries as $gallery)

                        @php
                            $cover = $gallery->photos
                                ->filter(function ($photo) {
                                    return (bool) $photo->pivot->is_cover
                                        && $photo->filename
                                        && file_exists(
                                            public_path('storage/photos/' . basename($photo->filename))
                                        );
                                })
                                ->first();

                            if (!$cover) {
                                $cover = $gallery->photos
                                    ->filter(function ($photo) {
                                        return $photo->filename
                                            && file_exists(
                                                public_path('storage/photos/' . basename($photo->filename))
                                            );
                                    })
                                    ->first();
                            }
                        @endphp

                        <div
                            class="gallery-item"
                            data-id="{{ $gallery->id }}"
                            draggable="true"
                            style="
                                background:#fff;
                                border-radius:10px;
                                overflow:hidden;
                                box-shadow:0 2px 12px rgba(0,0,0,0.07);
                                cursor:grab;
                            "
                        >

                            <a
                                href="{{ route('galleries.show', $gallery) }}"
                                style="
                                    display:block;
                                    text-decoration:none;
                                    color:inherit;
                                "
                            >

                                <div style="
                                    width:100%;
                                    aspect-ratio:1 / 0.7;
                                    background:#e9e7e2;
                                    overflow:hidden;
                                ">

                                    @if ($cover)

                                        <img
                                            src="{{ asset('storage/photos/' . basename($cover->filename)) }}"
                                            alt="{{ $gallery->title }}"
                                            style="
                                                width:100%;
                                                height:100%;
                                                object-fit:cover;
                                                display:block;
                                            "
                                        >

                                    @else

                                        <div style="
                                            width:100%;
                                            height:100%;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            color:#888;
                                            font-size:14px;
                                        ">
                                            Brak zdjęcia
                                        </div>

                                    @endif

                                </div>

                            </a>

                            <div style="padding:20px;">

                                <h2 style="
                                    margin:0 0 8px;
                                    font-size:21px;
                                    font-weight:500;
                                ">
                                    {{ $gallery->title }}
                                </h2>

                                @if ($gallery->description)

                                    <p style="
                                        margin:0 0 14px;
                                        color:#777;
                                        font-size:14px;
                                        line-height:1.6;
                                    ">
                                        {{ $gallery->description }}
                                    </p>

                                @endif

                                <div style="
                                    color:#777;
                                    font-size:13px;
                                    margin-bottom:18px;
                                ">
                                    {{ $gallery->photos->count() }}
                                    {{ $gallery->photos->count() === 1 ? 'fotografia' : 'fotografie' }}
                                </div>

                                <div style="
                                    display:flex;
                                    align-items:center;
                                    gap:18px;
                                    flex-wrap:wrap;
                                ">

                                    <a
                                        href="{{ route('galleries.show', $gallery) }}"
                                        style="
                                            color:#171717;
                                            font-size:13px;
                                            font-weight:600;
                                            text-decoration:none;
                                        "
                                    >
                                        Otwórz galerię →
                                    </a>

                                    <a
                                        href="{{ route('galleries.edit', $gallery) }}"
                                        style="
                                            color:#666;
                                            font-size:13px;
                                            text-decoration:none;
                                        "
                                    >
                                        Edytuj
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('galleries.destroy', $gallery) }}"
                                        style="display:inline;"
                                    >

                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            style="
                                                border:0;
                                                padding:0;
                                                background:none;
                                                color:#b91c1c;
                                                cursor:pointer;
                                                font-size:13px;
                                            "
                                            onclick="return confirm('Czy na pewno usunąć tę galerię?')"
                                        >
                                            Usuń
                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div style="
                    background:#fff;
                    padding:60px 30px;
                    text-align:center;
                    border-radius:10px;
                ">
                    <p style="
                        margin:0 0 20px;
                        color:#777;
                    ">
                        Nie ma jeszcze żadnych galerii.
                    </p>

                    <a
                        href="{{ route('galleries.create') }}"
                        style="
                            display:inline-block;
                            background:#171717;
                            color:#fff;
                            padding:13px 20px;
                            border-radius:6px;
                            text-decoration:none;
                        "
                    >
                        Dodaj pierwszą galerię
                    </a>
                </div>

            @endif

        </div>

    </div>


    <script>
        const galleryGrid = document.getElementById('gallery-grid');

        if (galleryGrid) {

            let draggedItem = null;

            galleryGrid.querySelectorAll('.gallery-item').forEach(function (item) {

                item.addEventListener('dragstart', function () {
                    draggedItem = this;

                    this.style.opacity = '0.5';
                });

                item.addEventListener('dragend', function () {
                    this.style.opacity = '1';

                    saveGalleryOrder();
                });

                item.addEventListener('dragover', function (event) {
                    event.preventDefault();

                    if (draggedItem === this) {
                        return;
                    }

                    const rect = this.getBoundingClientRect();

                    const after =
                        event.clientY > rect.top + rect.height / 2;

                    if (after) {
                        this.parentNode.insertBefore(
                            draggedItem,
                            this.nextSibling
                        );
                    } else {
                        this.parentNode.insertBefore(
                            draggedItem,
                            this
                        );
                    }
                });

            });
        }


        function saveGalleryOrder() {

            const items = document.querySelectorAll('.gallery-item');

            const galleries = Array.from(items).map(function (item) {
                return parseInt(item.dataset.id);
            });

            fetch('{{ route('galleries.reorder') }}', {

                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },

                body: JSON.stringify({
                    galleries: galleries
                })

            })
            .then(function (response) {

                if (!response.ok) {
                    throw new Error('Nie udało się zapisać kolejności galerii.');
                }

                return response.json();

            })
            .then(function () {

                showOrderMessage('Kolejność galerii została zapisana.');

            })
            .catch(function () {

                showOrderMessage(
                    'Nie udało się zapisać kolejności galerii.',
                    true
                );

            });
        }


        function showOrderMessage(message, error = false) {

            const oldMessage = document.getElementById('order-message');

            if (oldMessage) {
                oldMessage.remove();
            }

            const messageBox = document.createElement('div');

            messageBox.id = 'order-message';

            messageBox.textContent = message;

            messageBox.style.position = 'fixed';
            messageBox.style.right = '24px';
            messageBox.style.bottom = '24px';
            messageBox.style.padding = '14px 18px';
            messageBox.style.borderRadius = '6px';
            messageBox.style.zIndex = '9999';
            messageBox.style.color = '#fff';
            messageBox.style.background = error
                ? '#991b1b'
                : '#166534';

            document.body.appendChild(messageBox);

            setTimeout(function () {
                messageBox.remove();
            }, 2500);
        }
    </script>

</x-app-layout>