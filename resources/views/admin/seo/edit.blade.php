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
        <button class="cms-button cms-button-primary" type="submit">Zapisz SEO</button>
    </form>

    <h2>SEO — KONTROLA WITRYNY</h2>
    <p>Braki tytułu i opisu dotyczą własnych pól SEO. Publiczne meta tagi mogą korzystać z wartości domyślnych.</p>
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
    <section class="cms-card" style="padding:20px;">
        <h3>Biblioteka</h3>
        <p>Wszystkie Photo: {{ $photoCount }} · Posiadające ALT: {{ $photoCount - $missingAlt }}</p>
        <p><a href="{{ route('photos.index', ['seo_filter' => 'missing_alt']) }}">Brak ALT: {{ $missingAlt }}</a></p>
        <p><a href="{{ route('photos.index', ['seo_filter' => 'missing_title']) }}">Brak tytułu: {{ $missingTitle }}</a></p>
        <p><a href="{{ route('photos.index', ['seo_filter' => 'missing_description']) }}">Brak opisu: {{ $missingDescription }}</a></p>
    </section>
</x-app-layout>
