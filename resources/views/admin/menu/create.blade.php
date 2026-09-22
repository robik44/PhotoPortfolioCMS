<x-app-layout>

    <x-slot name="header">
        Dodaj pozycję menu
    </x-slot>

    <div class="cms-page">

        <div class="cms-page-header">

            <div>
                <h1>Dodaj pozycję menu</h1>
                <p>Dodaj nowy element do nawigacji strony.</p>
            </div>

            <a href="{{ route('menu.index') }}"
               class="cms-button">
                ← Wróć do menu
            </a>

        </div>

        <div class="cms-card">

            <form method="POST" action="{{ route('menu.store') }}">

                @csrf

                <div class="cms-form-grid">

                    <div class="cms-form-group">

                        <label for="title">
                            Nazwa pozycji
                        </label>

                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="{{ old('title') }}"
                            required
                            class="cms-input"
                            placeholder="np. O mnie"
                        >

                        @error('title')
                            <div class="cms-form-error">{{ $message }}</div>
                        @enderror

                    </div>

                    <div class="cms-form-group">

                        <label for="type">
                            Typ pozycji
                        </label>

                        <select
                            id="type"
                            name="type"
                            class="cms-input"
                            required
                        >
                            <option value="page"
                                {{ old('type', 'page') === 'page' ? 'selected' : '' }}>
                                Strona
                            </option>

                            <option value="gallery"
                                {{ old('type') === 'gallery' ? 'selected' : '' }}>
                                Galeria
                            </option>

                            <option value="url"
                                {{ old('type') === 'url' ? 'selected' : '' }}>
                                Własny link
                            </option>
                        </select>

                    </div>

                </div>

                <div class="cms-form-group" id="page-field">

                    <label for="page_id">
                        Strona
                    </label>

                    <select
                        id="page_id"
                        name="page_id"
                        class="cms-input"
                    >

                        <option value="">
                            — wybierz stronę —
                        </option>

                        @foreach($pages as $page)

                            <option
                                value="{{ $page->id }}"
                                {{ old('page_id') == $page->id ? 'selected' : '' }}
                            >
                                {{ $page->title }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="cms-form-group" id="gallery-field">

                    <label for="gallery_id">
                        Galeria
                    </label>

                    <select
                        id="gallery_id"
                        name="gallery_id"
                        class="cms-input"
                    >

                        <option value="">
                            — wybierz galerię —
                        </option>

                        @foreach($galleries as $gallery)

                            <option
                                value="{{ $gallery->id }}"
                                {{ old('gallery_id') == $gallery->id ? 'selected' : '' }}
                            >
                                {{ $gallery->title }}
                            </option>

                        @endforeach

                    </select>

                </div>

                <div class="cms-form-group" id="url-field">

                    <label for="url">
                        Adres URL
                    </label>

                    <input
                        type="text"
                        id="url"
                        name="url"
                        value="{{ old('url') }}"
                        class="cms-input"
                        placeholder="https://..."
                    >

                </div>

                <div class="cms-form-group">

                    <label for="parent_id">
                        Pozycja nadrzędna
                    </label>

                    <select
                        id="parent_id"
                        name="parent_id"
                        class="cms-input"
                    >

                        <option value="">
                            — główna pozycja menu —
                        </option>

                        @foreach($parentItems as $parent)

                            <option
                                value="{{ $parent->id }}"
                                {{ old('parent_id') == $parent->id ? 'selected' : '' }}
                            >
                                {{ $parent->title }}
                            </option>

                        @endforeach

                    </select>

                    <div class="cms-form-help">
                        Wybór pozycji nadrzędnej utworzy podmenu.
                    </div>

                </div>

                <div class="cms-form-group">

                    <label class="cms-checkbox-label">

                        <input
                            type="checkbox"
                            name="published"
                            value="1"
                            {{ old('published', true) ? 'checked' : '' }}
                        >

                        <span>
                            Pozycja widoczna na stronie
                        </span>

                    </label>

                </div>

                <div class="cms-form-actions">

                    <a href="{{ route('menu.index') }}"
                       class="cms-button">
                        Anuluj
                    </a>

                    <button type="submit"
                            class="cms-button cms-button-primary">
                        Zapisz pozycję
                    </button>

                </div>

            </form>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const type = document.getElementById('type');
            const pageField = document.getElementById('page-field');
            const galleryField = document.getElementById('gallery-field');
            const urlField = document.getElementById('url-field');

            function updateFields() {

                pageField.style.display = 'none';
                galleryField.style.display = 'none';
                urlField.style.display = 'none';

                if (type.value === 'page') {
                    pageField.style.display = 'block';
                }

                if (type.value === 'gallery') {
                    galleryField.style.display = 'block';
                }

                if (type.value === 'url') {
                    urlField.style.display = 'block';
                }
            }

            type.addEventListener('change', updateFields);

            updateFields();
        });
    </script>

</x-app-layout>
