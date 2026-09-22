<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj fotografie
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

                <form
                    method="POST"
                    action="{{ route('photos.store') }}"
                    enctype="multipart/form-data"
                >

                    @csrf


                    <div style="margin-bottom:24px;">

                        <label
                            for="gallery_id"
                            style="
                                display:block;
                                font-size:14px;
                                font-weight:600;
                                margin-bottom:8px;
                            "
                        >
                            Galeria
                        </label>

                        <select
                            id="gallery_id"
                            name="gallery_id"
                            required
                            style="
                                width:100%;
                                padding:11px 12px;
                                border:1px solid #d1d5db;
                                border-radius:6px;
                                background:#fff;
                            "
                        >

                            <option value="">
                                Wybierz galerię
                            </option>

                            @foreach ($galleries as $gallery)

                                <option
                                    value="{{ $gallery->id }}"
                                    @selected(old('gallery_id') == $gallery->id)
                                >
                                    {{ $gallery->title }}
                                </option>

                            @endforeach

                        </select>

                    </div>


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
                            Fotografie
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
                            Możesz zaznaczyć jednocześnie dowolną liczbę fotografii.
                            Każda fotografia zostanie dodana do wybranej galerii.
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
                            Dodaj fotografie
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

                    item.innerHTML = `
                        <img
                            src="${event.target.result}"
                            style="
                                width:100%;
                                aspect-ratio:1/1;
                                object-fit:cover;
                                display:block;
                            "
                        >
                        <div style="
                            padding:8px;
                            font-size:11px;
                            color:#555;
                            white-space:nowrap;
                            overflow:hidden;
                            text-overflow:ellipsis;
                        ">
                            ${file.name}
                        </div>
                    `;

                    preview.appendChild(item);
                };

                reader.readAsDataURL(file);
            });
        });
    </script>

</x-app-layout>