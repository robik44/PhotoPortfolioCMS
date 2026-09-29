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
                    id="photo-upload-form"
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

                    <p id="upload-status" role="status" aria-live="polite"></p>
                    <ul id="upload-errors" style="color:#991b1b;"></ul>
                    <a id="upload-library" href="{{ route('photos.index') }}" hidden>Zobacz Bibliotekę</a>

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

        const form = document.getElementById('photo-upload-form');
        const status = document.getElementById('upload-status');
        const errors = document.getElementById('upload-errors');
        const library = document.getElementById('upload-library');
        let uploading = false;
        let previewUrls = [];

        imageInput.addEventListener('change', function () {
            previewUrls.forEach(url => URL.revokeObjectURL(url));
            previewUrls = [];
            preview.replaceChildren();
            Array.from(this.files).forEach(file => {
                if (!file.type.startsWith('image/')) return;
                const item = document.createElement('div');
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                previewUrls.push(img.src);
                img.alt = '';
                img.loading = 'lazy';
                img.style.cssText = 'width:100%;aspect-ratio:1;object-fit:cover;display:block';
                const name = document.createElement('div');
                name.textContent = file.name;
                name.style.cssText = 'padding:8px;font-size:11px;overflow-wrap:anywhere';
                item.append(img, name);
                preview.append(item);
            });
        });

        window.addEventListener('beforeunload', event => {
            if (uploading) { event.preventDefault(); event.returnValue = ''; }
        });

        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (uploading) return;
            const files = Array.from(imageInput.files);
            if (!files.length) return;
            uploading = true;
            imageInput.disabled = true;
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            errors.replaceChildren();
            library.hidden = true;
            let succeeded = 0;
            for (const [index, file] of files.entries()) {
                status.textContent = `Wysyłanie ${index + 1} z ${files.length}: ${file.name}. Dodano: ${succeeded}.`;
                try {
                    if (file.size > 50 * 1024 * 1024) throw new Error('Plik przekracza limit 50 MB.');
                    const data = new FormData();
                    data.append('_token', form.querySelector('[name="_token"]').value);
                    data.append('images[]', file);
                    const response = await fetch(form.action, {
                        method: 'POST', body: data, headers: { Accept: 'application/json' },
                        signal: AbortSignal.timeout(180000)
                    });
                    const result = await response.json().catch(() => ({}));
                    if (!response.ok || response.redirected) {
                        throw new Error(response.status === 413
                            ? 'Zdjęcie przekracza limit serwera. Zmniejsz plik i spróbuj ponownie.'
                            : result.message || `Błąd HTTP ${response.status}. Odśwież sesję i spróbuj ponownie.`);
                    }
                    succeeded++;
                } catch (error) {
                    const item = document.createElement('li');
                    item.textContent = `${file.name}: ${error.message}`;
                    errors.append(item);
                }
            }
            uploading = false;
            imageInput.disabled = false;
            button.disabled = false;
            imageInput.value = '';
            status.textContent = `Zakończono. Dodano: ${succeeded} z ${files.length}. Błędy: ${files.length - succeeded}.`;
            library.hidden = false;
            if (succeeded === files.length) window.location.assign(library.href);
        });
    </script>
</x-app-layout>
