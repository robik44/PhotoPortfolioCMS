<x-app-layout>
    <x-slot name="header">SEO</x-slot>
    <h1>SEO — Ustawienia globalne</h1>
    @if(session('success'))<p role="status" class="cms-alert">{{ session('success') }}</p>@endif
    @if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('seo.update') }}" class="cms-card" style="padding:24px;margin:20px 0;">
        @csrf @method('PUT')
        @foreach(['seo_site_name' => 'Nazwa witryny', 'seo_default_title' => 'Domyślny tytuł SEO', 'seo_default_description' => 'Domyślny opis SEO'] as $key => $label)
            <label style="display:block;margin:12px 0;">{{ $label }}
                <input name="{{ $key }}" value="{{ old($key, $settings[$key] ?? '') }}" maxlength="{{ $key === 'seo_default_description' ? 2000 : 255 }}" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">
            </label>
        @endforeach
        <x-photo-picker name="seo_social_photo_id" label="Domyślne zdjęcie social" :selected="$settings['seo_social_photo_id'] ?? null" fallback />
        <label>Globalne indeksowanie witryny
            <select name="seo_indexable">
                <option value="1" @selected((string) old('seo_indexable', $settings['seo_indexable'] ?? '1') === '1')>TAK</option>
                <option value="0" @selected((string) old('seo_indexable', $settings['seo_indexable'] ?? '1') === '0')>NIE</option>
            </select>
        </label>
        <p>Puste pola korzystają z dotychczasowych danych witryny. Wyłączenie indeksowania obowiązuje wszystkie strony i galerie.</p>

        <hr style="margin:28px 0;">
        <h2>SEO — Strona główna</h2>
        <label style="display:block;margin:12px 0;">Tytuł SEO strony głównej
            <input name="home_seo_title" value="{{ old('home_seo_title', $settings['home_seo_title'] ?? '') }}" maxlength="255" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">
        </label>
        <label style="display:block;margin:12px 0;">Opis SEO strony głównej
            <textarea name="home_seo_description" maxlength="2000" rows="4" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">{{ old('home_seo_description', $settings['home_seo_description'] ?? '') }}</textarea>
        </label>
        <x-photo-picker name="home_seo_social_photo_id" label="Zdjęcie social strony głównej" :selected="$settings['home_seo_social_photo_id'] ?? null" fallback />
        <label>Indeksowanie strony głównej
            <select name="home_seo_indexable">
                <option value="1" @selected((string) old('home_seo_indexable', $settings['home_seo_indexable'] ?? '1') === '1')>TAK</option>
                <option value="0" @selected((string) old('home_seo_indexable', $settings['home_seo_indexable'] ?? '1') === '0')>NIE</option>
            </select>
        </label>
        <p>Jeśli pola strony głównej pozostaną puste, użyte zostaną globalne wartości SEO.</p>
        <button class="cms-button cms-button-primary" type="submit">Zapisz SEO</button>
    </form>

    <h2>SEO — KONTROLA WITRYNY</h2>
    <p>Braki tytułu i opisu dotyczą własnych pól SEO. Publiczne meta tagi mogą korzystać z wartości domyślnych. Audyt sprawdza też strukturę nagłówków stron budowanych w edytorze: obecność H1, pusty H1 i pomijanie poziomów (np. H1 → H3). Ostrzega też o wyjątkowo długich tytułach i opisach SEO. To wskazówki redakcyjne, nie twarde limity wyszukiwarki.</p>
    @php($homeIssues = \App\Support\Seo::homeIssues())
    <section class="cms-card" style="padding:20px;margin:20px 0;">
        <h3>Strona główna</h3>
        <p>
            <strong>Home</strong> — {{ $homeIssues ? implode('; ', $homeIssues) : 'Kompletne' }}
            <a class="cms-button cms-button-small" href="{{ route('home-builder.edit') }}">Sprawdź H1</a>
        </p>
    </section>
    @unless(\App\Support\Seo::indexing($settings))<p>Globalne indeksowanie jest wyłączone — cała witryna ma noindex.</p>@endunless
    @foreach(['Strony' => $pages, 'Galerie' => $galleries] as $label => $entities)
        <section class="cms-card" style="padding:20px;margin:20px 0;">
            <h3>{{ $label }}</h3>
            @forelse($entities as $entity)
                @php($issues = \App\Support\Seo::issues($entity))
                <p>
                    <strong>{{ $entity->title }}</strong> — {{ $issues ? implode('; ', $issues) : 'Kompletne' }}
                    @unless($entity->published) (Nieopublikowana) @endunless
                    <a class="cms-button cms-button-small" href="{{ route($entity instanceof \App\Models\Page ? 'pages.edit' : 'galleries.edit', $entity) }}">Sprawdź</a>
                </p>
            @empty<p>Brak rekordów.</p>@endforelse
            @if($label === 'Strony')
                @foreach(\App\Support\ContentPages::PAGES as $slug => $defaults)
                    @unless($pages->contains('slug', $slug))
                        <p>{{ $defaults['title'] }} — korzysta z ustawień globalnych. <a href="{{ route('content-pages.edit', $slug) }}">Sprawdź</a></p>
                    @endunless
                @endforeach
            @endif
        </section>
    @endforeach

    <section class="cms-card" style="padding:20px;margin:20px 0;">
        <h3>Duplikaty meta</h3>
        @if(empty($duplicateTitles) && empty($duplicateDescriptions))
            <p>Brak duplikatów tytułów i opisów w indeksowanych treściach.</p>
        @else
            @foreach($duplicateTitles as $labels)
                <p><strong>Powtarzający się tytuł SEO:</strong> {{ implode(' · ', $labels) }}</p>
            @endforeach
            @foreach($duplicateDescriptions as $labels)
                <p><strong>Powtarzający się opis SEO:</strong> {{ implode(' · ', $labels) }}</p>
            @endforeach
        @endif
    </section>

    <section class="cms-card" style="padding:20px;margin:20px 0;">
        <h3>Linkowanie wewnętrzne</h3>
        @forelse($menuIssues as $issue)
            <p>{{ $issue }}</p>
        @empty
            <p>Menu nie zawiera odnośników do brakujących lub nieopublikowanych stron i galerii.</p>
        @endforelse
    </section>

    <section class="cms-card" style="padding:20px;">
        <h3>Biblioteka</h3>
        <p>Wszystkie Photo: {{ $photoCount }} · Posiadające ALT: {{ $photoCount - $missingAlt }}</p>
        <p><a href="{{ route('photos.index', ['seo_filter' => 'missing_alt']) }}">Brak ALT: {{ $missingAlt }}</a></p>
        <p><a href="{{ route('photos.index', ['seo_filter' => 'missing_title']) }}">Brak tytułu: {{ $missingTitle }}</a></p>
        <p><a href="{{ route('photos.index', ['seo_filter' => 'missing_description']) }}">Brak opisu: {{ $missingDescription }}</a></p>
    </section>
</x-app-layout>
