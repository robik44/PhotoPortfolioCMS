@php($fontCatalog = $siteFonts ?? app(\App\Services\SiteFontLibrary::class)->catalog())
@php($familyKey = $field.'_font_family')
@php($sizeKey = $field.'_font_size')
<div style="margin:12px 0;">
    <label for="{{ $familyKey }}">{{ $label }} — krój czcionki</label>
    <select name="{{ $familyKey }}" id="{{ $familyKey }}" style="display:block;width:100%;margin:8px 0;">
        <option value="">Domyślny (bez zmiany)</option>
        @foreach($fontCatalog['choices'] as $font)
            <option value="{{ $font['value'] }}" style="font-family:{{ $font['css'] }};" @selected(old($familyKey, $typography[$familyKey] ?? '') === $font['value'])>{{ $font['label'] }}</option>
        @endforeach
    </select>
    @error($familyKey)<p role="alert">{{ $message }}</p>@enderror
    @if($withSize ?? true)
        <label for="{{ $sizeKey }}">{{ $label }} — rozmiar czcionki (px)</label>
        <input type="number" inputmode="decimal" step="any" min="1" max="200" name="{{ $sizeKey }}" id="{{ $sizeKey }}"
               value="{{ old($sizeKey, $typography[$sizeKey] ?? '') }}" placeholder="Domyślny (bez zmiany)" style="display:block;width:100%;min-height:44px;margin:8px 0;padding:10px 12px;border:1px solid #9ca3af;border-radius:6px;background:#fff;color:#111827;font-size:16px;">
        @error($sizeKey)<p role="alert">{{ $message }}</p>@enderror
    @endif
</div>
