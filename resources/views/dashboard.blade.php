<x-app-layout>

    <div class="cms-dashboard-intro">

        <div>
            <div class="cms-dashboard-eyebrow">Panel administracyjny</div>

            <h1 class="cms-dashboard-title">
                Witaj w Twoim studio
            </h1>

            <p class="cms-dashboard-subtitle">
                Zarządzaj galeriami, zdjęciami i treścią swojej strony fotograficznej
                z jednego miejsca.
            </p>
        </div>

        <div class="cms-dashboard-date">
            {{ now()->translatedFormat("d F Y") }}
        </div>

    </div>


    <div class="cms-stats">

        <div class="cms-stat-card">
            <div class="cms-stat-label">Galerie</div>
            <div class="cms-stat-number">{{ $galleryCount }}</div>
            <div class="cms-stat-description">Opublikowane galerie</div>
        </div>

        <div class="cms-stat-card">
            <div class="cms-stat-label">Zdjęcia</div>
            <div class="cms-stat-number">{{ $photoCount }}</div>
            <div class="cms-stat-description">Zdjęcia w portfolio</div>
        </div>

        <div class="cms-stat-card">
            <div class="cms-stat-label">Strony</div>
            <div class="cms-stat-number">2</div>
            <div class="cms-stat-description">O mnie i Kontakt</div>
        </div>

        <div class="cms-stat-card">
            <div class="cms-stat-label">Status</div>
            <div class="cms-stat-number">●</div>
            <div class="cms-stat-description">Strona działa poprawnie</div>
        </div>

    </div>


    <div class="cms-dashboard-grid">

        <div class="cms-card">

            <div class="cms-card-header">
                <h2 class="cms-card-title">Szybkie działania</h2>
            </div>

            <div class="cms-quick-actions">

                <a href="{{ route("galleries.create") }}" class="cms-action">
                    <div class="cms-action-title">Dodaj galerię</div>
                    <div class="cms-action-description">
                        Utwórz nową galerię w portfolio.
                    </div>
                </a>

                <a href="{{ route("photos.create") }}" class="cms-action">
                    <div class="cms-action-title">Dodaj zdjęcia</div>
                    <div class="cms-action-description">
                        Dodaj nowe fotografie do wybranej galerii.
                    </div>
                </a>

                <a href="{{ route("galleries.index") }}" class="cms-action">
                    <div class="cms-action-title">Zarządzaj galeriami</div>
                    <div class="cms-action-description">
                        Edytuj, porządkuj i zmieniaj kolejność galerii.
                    </div>
                </a>

                <a href="{{ url("/") }}" target="_blank" class="cms-action">
                    <div class="cms-action-title">Zobacz stronę</div>
                    <div class="cms-action-description">
                        Otwórz publiczną wersję portfolio.
                    </div>
                </a>

            </div>

        </div>


        <div class="cms-card">

            <div class="cms-card-header">
                <h2 class="cms-card-title">Informacje</h2>
            </div>

            <div class="cms-info-list">

                <div class="cms-info-row">
                    <span class="cms-info-label">System</span>
                    <span class="cms-info-value">Photo CMS</span>
                </div>

                <div class="cms-info-row">
                    <span class="cms-info-label">Laravel</span>
                    <span class="cms-info-value">{{ app()->version() }}</span>
                </div>

                <div class="cms-info-row">
                    <span class="cms-info-label">PHP</span>
                    <span class="cms-info-value">{{ PHP_VERSION }}</span>
                </div>

                <div class="cms-info-row">
                    <span class="cms-info-label">Baza danych</span>
                    <span class="cms-info-value">SQLite</span>
                </div>

                <div class="cms-info-row">
                    <span class="cms-info-label">Status</span>
                    <span class="cms-info-value">Aktywny</span>
                </div>

            </div>

        </div>

    </div>


    <div class="cms-card">

        <div class="cms-card-header">

            <h2 class="cms-card-title">
                Ostatnio dodane zdjęcia
            </h2>

            <a href="{{ route("photos.index") }}" class="cms-card-link">
                Wszystkie zdjęcia →
            </a>

        </div>

        @if($recentPhotos->count())

            <div class="cms-photo-grid">

                @foreach($recentPhotos as $photo)

                    <a href="{{ route("photos.edit", $photo) }}" class="cms-photo-thumb">

                        <img
                            src="{{ asset("storage/photos/" . basename($photo->filename)) }}"
                            alt="{{ $photo->alt ?: $photo->title ?: "Zdjęcie" }}"
                        >

                    </a>

                @endforeach

            </div>

        @else

            <div class="cms-photo-empty">
                Nie ma jeszcze żadnych zdjęć.
            </div>

        @endif

    </div>

</x-app-layout>
