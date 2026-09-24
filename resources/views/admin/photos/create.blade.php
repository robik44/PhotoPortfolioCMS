<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj do biblioteki
        </h2>
    </x-slot>

    <div style="padding:32px 0;">
        <div style="
            max-width:900px;
            margin:0 auto;
            padding:0 24px;
        ">

            @if ($errors->any())
                <div style="
                    margin-bottom:24px;
                    padding:14px 18px;
                    border-radius:6px;
                    background:#fee2e2;
                    color:#991b1b;
                ">
                    <strong>Nie udało się dodać fotografii:</strong>

                    <ul style="margin:10px 0 0 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div style="
                background:#fff;
                padding:32px;
                border-radius:10px;
                box-shadow:0 2px 12px rgba(0,0,0,0.08);
            ">

                <div style="margin-bottom:28px;">
                    <h3 style="
                        margin:0 0 8px;
                        font-size:18px;
                        font-weight:600;
                        color:#171717;
                    ">
                        Biblioteka zdjęć
                    </h3>

                    <p style="
                        margin:0;
                        font-size:14px;
                        line-height:1.6;
                        color:#6b7280;
                    ">
                        Dodane tutaj fotografie nie są przypisywane do żadnej galerii.
                        Później możesz wykorzystać je w galeriach, na stronach
                        i w innych elementach serwisu.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('photos.store') }}"
                    enctype="multipart/form-data"
                >
                    @csrf

                    <div style="margin-bottom:24px;">
                        <label
                            for="images"
                            style="
                                display:block;
                                font-size:14px;
                                font-weight:600;
                                margin-bottom:8px;
                            "
                        >
                            Wybierz fotografie
                        </label>

                        <input
                            id="images"
                            type="file"
                            name="images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            required
                            style="
                                display:block;
                                width:100%;
                                padding:12px;
                                border:1px solid #d1d5db;
                                border-radius:6px;
                                background:#fff;
                            "
                        >

                        <p style="
                            margin:8px 0 0;
                            font-size:13px;
                            color:#6b7280;
                        ">
                            Możesz zaznaczyć jednocześnie wiele fotografii.
                            Wszystkie zostaną zapisane w centralnej Bibliotece zdjęć.
                        </p>
                    </div>

                    <div
                        id="file-preview"
                        style="
                            display:grid;
                            grid-template-columns:repeat(auto-fill,minmax(140px,1fr));
                            gap:12px;
                            margin-bottom:24px;
                        "
                    ></div>

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:15px;
                        padding-top:8px;
                    ">
                        <button
                            type="submit"
                            style="
                                display:block;
                                background:#171717;
                                color:#fff;
                                border:none;
                                padding:14px 24px;
                                border-radius:6px;
                                font-size:14px;
                                font-weight:600;
                                cursor:pointer;
                            "
                        >
                            Dodaj do biblioteki
                        </button>

                        <a
                            href="{{ route('photos.index') }}"
                            style="
                                color:#555;
                                text-decoration:none;
                                font-size:14px;
                            "
                        >
                            Anuluj
                        </a>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        const imageInput = document.getElementById('images');
        const preview = document.getElementById('file-preview');

        imageInput.addEventListener('change', function () {
            preview.innerHTML = '';

            Array.from(this.files).forEach(function (file) {
                if (!file.type.startsWith('image/')) {
                    return;
                }

                const reader = new FileReader();

                reader.onload = function (event) {
                    const item = document.createElement('div');

                    item.style.background = '#f1f1f1';
                    item.style.borderRadius = '6px';
                    item.style.overflow = 'hidden';

                    const img = document.createElement('img');
                    img.src = event.target.result;
                    img.alt = '';
                    img.style.width = '100%';
                    img.style.aspectRatio = '1/1';
                    img.style.objectFit = 'cover';
                    img.style.display = 'block';

                    const name = document.createElement('div');
                    name.style.padding = '8px';
                    name.style.fontSize = '11px';
                    name.style.color = '#555';
                    name.style.whiteSpace = 'nowrap';
                    name.style.overflow = 'hidden';
                    name.style.textOverflow = 'ellipsis';
                    name.textContent = file.name;

                    item.appendChild(img);
                    item.appendChild(name);
                    preview.appendChild(item);
                };

                reader.readAsDataURL(file);
            });
        });
    </script>
</x-app-layout>
