@once
    <link rel="stylesheet" href="{{ asset('css/photo-picker.css') }}">
    <script src="{{ asset('js/photo-picker.js') }}" defer></script>
    <dialog id="photo-library-dialog" class="photo-library-dialog" aria-labelledby="photo-library-title">
        <div class="photo-library-header">
            <h2 id="photo-library-title">Wybierz z Biblioteki</h2>
            <button type="button" class="cms-button" data-library-cancel aria-label="Zamknij Bibliotekę">Zamknij</button>
        </div>
        <label for="photo-library-search">Szukaj po tytule, ALT lub opisie</label>
        <input id="photo-library-search" type="search" autocomplete="off" data-library-search>
        <div class="photo-library-grid">
            @foreach(\App\Models\Photo::orderByDesc('id')->get() as $libraryPhoto)
                <button type="button" class="photo-library-card" data-library-photo="{{ $libraryPhoto->id }}"
                    data-search="{{ $libraryPhoto->title }} {{ $libraryPhoto->alt }} {{ $libraryPhoto->description }}"
                    aria-label="{{ $libraryPhoto->title ?: $libraryPhoto->alt ?: 'Fotografia '.$loop->iteration }}" aria-pressed="false">
                    <img src="{{ $libraryPhoto->thumbnailUrl() }}" alt="{{ $libraryPhoto->alt ?: $libraryPhoto->title }}" loading="lazy" width="240" height="180">
                    <span data-library-caption>{{ $libraryPhoto->title ?: $libraryPhoto->alt }}</span>
                    <span class="photo-library-mark" aria-hidden="true">✓ Wybrane</span>
                </button>
            @endforeach
        </div>
        <p data-library-empty hidden>Brak zdjęć pasujących do wyszukiwania.</p>
        <div class="photo-library-footer">
            <button type="button" class="cms-button" data-library-none aria-pressed="false">Brak / użyj domyślnego</button>
            <span data-library-status role="status" aria-live="polite"></span>
            <button type="button" class="cms-button" data-library-cancel>Anuluj</button>
            <button type="button" class="cms-button cms-button-primary" data-library-confirm>Zatwierdź wybór</button>
        </div>
    </dialog>
@endonce
