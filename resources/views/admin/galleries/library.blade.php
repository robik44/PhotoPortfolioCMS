<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Wybierz z biblioteki
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
                gap:20px;
                margin-bottom:28px;
            ">
                <div>
                    <h1 style="
                        margin:0 0 8px;
                        font-size:30px;
                        font-weight:400;
                    ">
                        Wybierz fotografie
                    </h1>

                    <p style="
                        margin:0;
                        color:#6b7280;
                        font-size:14px;
                    ">
                        Galeria: <strong>{{ $gallery->title }}</strong>
                    </p>
                </div>

                <a
                    href="{{ route('galleries.show', $gallery) }}"
                    style="
                        display:inline-block;
                        padding:11px 16px;
                        background:#eee;
                        color:#171717;
                        border-radius:6px;
                        text-decoration:none;
                        font-size:14px;
                    "
                >
                    Powrót do galerii
                </a>
            </div>

            @if ($errors->any())
                <div style="
                    margin-bottom:24px;
                    padding:14px 18px;
                    border-radius:6px;
                    background:#fee2e2;
                    color:#991b1b;
                ">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($photos->count())

                <form
                    method="POST"
                    action="{{ route('galleries.photos.attach', $gallery) }}"
                >
                    @csrf

                    <div style="
                        display:flex;
                        justify-content:space-between;
                        align-items:center;
                        gap:20px;
                        margin-bottom:20px;
                        padding:16px 18px;
                        background:#fff;
                        border-radius:8px;
                        box-shadow:0 2px 10px rgba(0,0,0,.05);
                    ">
                        <div style="
                            font-size:14px;
                            color:#555;
                        ">
                            Kliknij fotografie, które chcesz dodać do galerii.
                            Możesz zaznaczyć kilka jednocześnie.
                        </div>

                        <button
                            id="submit-selected"
                            type="submit"
                            disabled
                            style="
                                padding:12px 20px;
                                border:0;
                                border-radius:6px;
                                background:#171717;
                                color:#fff;
                                font-size:14px;
                                font-weight:600;
                                cursor:pointer;
                                opacity:.4;
                            "
                        >
                            Dodaj wybrane
                            <span id="selected-count">(0)</span>
                        </button>
                    </div>

                    <div style="
                        display:grid;
                        grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
                        gap:18px;
                    ">
                        @foreach ($photos as $photo)
                            @php
                                $alreadyAssigned = in_array(
                                    (int) $photo->id,
                                    $assignedPhotoIds,
                                    true
                                );

                                $imagePath = str_starts_with(
                                    $photo->filename,
                                    'photos/'
                                )
                                    ? $photo->filename
                                    : 'photos/' . $photo->filename;
                            @endphp

                            <label
                                class="library-photo-card"
                                style="
                                    position:relative;
                                    display:block;
                                    background:#fff;
                                    border:2px solid transparent;
                                    border-radius:9px;
                                    overflow:hidden;
                                    box-shadow:0 2px 10px rgba(0,0,0,.07);
                                    cursor:{{ $alreadyAssigned ? 'default' : 'pointer' }};
                                    opacity:{{ $alreadyAssigned ? '.45' : '1' }};
                                "
                            >
                                <input
                                    type="checkbox"
                                    name="photos[]"
                                    value="{{ $photo->id }}"
                                    class="photo-checkbox"
                                    {{ $alreadyAssigned ? 'disabled' : '' }}
                                    style="
                                        position:absolute;
                                        opacity:0;
                                        pointer-events:none;
                                    "
                                >

                                <div style="
                                    position:relative;
                                    background:#e9e7e2;
                                ">
                                    <img
                                        src="{{ asset('storage/' . $imagePath) }}"
                                        alt="{{ $photo->alt ?: $photo->title ?: '' }}"
                                        style="
                                            display:block;
                                            width:100%;
                                            aspect-ratio:1/1;
                                            object-fit:cover;
                                        "
                                    >

                                    <div
                                        class="selection-mark"
                                        style="
                                            display:none;
                                            position:absolute;
                                            top:10px;
                                            right:10px;
                                            width:30px;
                                            height:30px;
                                            border-radius:50%;
                                            background:#171717;
                                            color:#fff;
                                            align-items:center;
                                            justify-content:center;
                                            font-size:18px;
                                            font-weight:700;
                                        "
                                    >
                                        ✓
                                    </div>

                                    @if ($alreadyAssigned)
                                        <div style="
                                            position:absolute;
                                            inset:0;
                                            display:flex;
                                            align-items:center;
                                            justify-content:center;
                                            background:rgba(255,255,255,.55);
                                        ">
                                            <span style="
                                                padding:7px 10px;
                                                background:#171717;
                                                color:#fff;
                                                border-radius:4px;
                                                font-size:11px;
                                                text-transform:uppercase;
                                                letter-spacing:.05em;
                                            ">
                                                Już w galerii
                                            </span>
                                        </div>
                                    @endif
                                </div>

                                <div style="
                                    padding:10px 12px;
                                    font-size:12px;
                                    color:#555;
                                    white-space:nowrap;
                                    overflow:hidden;
                                    text-overflow:ellipsis;
                                ">
                                    {{ $photo->title ?: basename($photo->filename) }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </form>

            @else

                <div style="
                    padding:60px 30px;
                    background:#fff;
                    border-radius:10px;
                    text-align:center;
                ">
                    <p style="margin:0 0 20px;color:#777;">
                        Biblioteka zdjęć jest pusta.
                    </p>

                    <a
                        href="{{ route('photos.create') }}"
                        style="
                            display:inline-block;
                            padding:12px 18px;
                            background:#171717;
                            color:#fff;
                            border-radius:6px;
                            text-decoration:none;
                        "
                    >
                        Dodaj fotografie do biblioteki
                    </a>
                </div>

            @endif
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const checkboxes = Array.from(
                document.querySelectorAll('.photo-checkbox:not(:disabled)')
            );

            const button = document.getElementById('submit-selected');
            const count = document.getElementById('selected-count');

            function refreshSelection() {
                const selected = checkboxes.filter(function (checkbox) {
                    return checkbox.checked;
                });

                checkboxes.forEach(function (checkbox) {
                    const card = checkbox.closest('.library-photo-card');
                    const mark = card.querySelector('.selection-mark');

                    if (checkbox.checked) {
                        card.style.borderColor = '#171717';
                        mark.style.display = 'flex';
                    } else {
                        card.style.borderColor = 'transparent';
                        mark.style.display = 'none';
                    }
                });

                if (button && count) {
                    count.textContent = '(' + selected.length + ')';
                    button.disabled = selected.length === 0;
                    button.style.opacity = selected.length === 0 ? '.4' : '1';
                }
            }

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshSelection);
            });

            refreshSelection();
        });
    </script>
</x-app-layout>
