<fieldset style="margin:24px 0;padding:16px;border:1px solid #ddd;">
    <legend>Czcionki tekstów galerii</legend>
    @foreach (['title_font_family' => 'Tytuł', 'description_font_family' => 'Opis', 'caption_font_family' => 'Podpisy zdjęć'] as $key => $label)
        @php
            $fallback = $siteFonts['defaults'][$key === 'title_font_family' ? 'site_heading_font_family' : 'site_body_font_family'];
        @endphp
        <label for="{{ $key }}">{{ $label }} — Rodzaj czcionki</label>
        <select name="{{ $key }}" id="{{ $key }}" style="display:block;width:100%;margin:8px 0 16px;">
            @foreach ($siteFonts['choices'] as $font)
                <option value="{{ $font['value'] }}" style="font-family:{{ $font['css'] }};" @selected(old($key, $galleryFonts[$key] ?? $fallback) === $font['value'])>{{ $font['label'] }}</option>
            @endforeach
        </select>
        @error($key)<p role="alert">{{ $message }}</p>@enderror
    @endforeach
    <a href="{{ route('fonts.index') }}">Biblioteka czcionek</a>
</fieldset>
@include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
