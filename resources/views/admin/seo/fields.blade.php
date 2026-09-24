<fieldset style="margin:24px 0;padding:20px;border:1px solid #ddd;">
    <legend>SEO</legend>
    @foreach(['seo_title' => 'Tytuł SEO', 'seo_description' => 'Opis SEO'] as $field => $label)
        <label style="display:block;margin:12px 0;">{{ $label }}
            @if($field === 'seo_description')
                <textarea name="{{ $field }}" rows="3" maxlength="2000" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">{{ old($field, $entity?->$field) }}</textarea>
            @else
                <input name="{{ $field }}" value="{{ old($field, $entity?->$field) }}" maxlength="255" style="display:block;width:100%;padding:10px;border:1px solid #ddd;">
            @endif
        </label>
    @endforeach
    <x-photo-picker name="social_photo_id" label="Zdjęcie social" :selected="$entity?->social_photo_id" fallback />
    <label>Indeksowanie
        <select name="indexable">
            <option value="1" @selected((string) old('indexable', $entity?->indexable ?? true) === '1')>TAK</option>
            <option value="0" @selected((string) old('indexable', $entity?->indexable ?? true) === '0' || old('indexable', $entity?->indexable ?? true) === false)>NIE — noindex</option>
        </select>
    </label>
    @foreach(['seo_title', 'seo_description', 'social_photo_id', 'indexable', 'slug'] as $field)
        @error($field)<p style="color:#b91c1c;">{{ $message }}</p>@enderror
    @endforeach
    @if($entity?->exists)
        @php
            $preview = \App\Support\Seo::meta($entity);
            $fallbackEntity = clone $entity;
            $fallbackEntity->seo_description = null;
            $fallback = \App\Support\Seo::meta($fallbackEntity);
        @endphp
        <div style="margin-top:20px;padding:16px;background:#f7f7f7;" data-seo-preview data-gallery-default-title="{{ \App\Support\Seo::settings()['seo_default_title'] ?? '' }}" data-site="{{ \App\Support\Seo::siteName() }}" data-kind="{{ $entity instanceof \App\Models\Page ? 'page' : 'gallery' }}" data-origin="{{ url('/') }}" data-default-description="{{ $fallback['description'] }}">
            <strong>Podgląd w wyszukiwarce</strong>
            <p data-preview-url>{{ $preview['canonical'] }}</p>
            <p data-preview-title style="color:#1a0dab;">{{ $preview['title'] }}</p>
            <p data-preview-description>{{ $preview['description'] }}</p>
            <small>Podgląd orientacyjny. Wyszukiwarka może wyświetlić inny tytuł i opis.</small>
        </div>
    @endif
</fieldset>

@once<script src="{{ asset('js/seo-preview.js') }}" defer></script>@endonce
