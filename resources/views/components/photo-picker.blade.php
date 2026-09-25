@props(['name', 'label', 'selected' => null, 'fallback' => false, 'legacyUrl' => null, 'clearName' => null])
@php
    $value = old($name, $selected);
    $photo = $value ? \App\Models\Photo::find($value) : null;
    $cleared = $clearName && old($clearName, false);
    $previewUrl = $photo?->thumbnailUrl() ?? (!$cleared ? $legacyUrl : null);
    $pickerId = 'photo-picker-'.$name;
@endphp
<fieldset class="photo-picker" data-photo-picker data-fallback="{{ $fallback ? '1' : '0' }}" aria-labelledby="{{ $pickerId }}-label">
    <legend id="{{ $pickerId }}-label">{{ $label }}</legend>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-photo-value>
    @if($clearName)
        <input type="hidden" name="{{ $clearName }}" value="{{ $cleared ? '1' : '0' }}" data-photo-clear>
    @endif
    <div data-photo-preview @if(!$previewUrl) hidden @endif>
        <img @if($previewUrl) src="{{ $previewUrl }}" @endif alt="{{ $photo?->alt ?: $photo?->title ?: $label }}" data-photo-image>
        <p data-photo-caption>{{ $photo?->title ?: $photo?->alt }}</p>
    </div>
    <div class="photo-picker-actions">
        <button type="button" class="cms-button" data-photo-open aria-haspopup="dialog" aria-controls="photo-library-dialog">{{ $previewUrl ? 'Zmień' : 'Wybierz z Biblioteki' }}</button>
        <button type="button" class="cms-button" data-photo-remove @if(!$previewUrl) hidden @endif>Usuń wybór</button>
    </div>
    <p data-photo-empty @if($previewUrl) hidden @endif>{{ $fallback ? 'Brak / użyj domyślnego' : 'Brak wybranego zdjęcia' }}</p>
    @error($name)<p role="alert">{{ $message }}</p>@enderror
</fieldset>

@include('components.photo-library-dialog')
