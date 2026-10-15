@php
    $photoTypography = \App\Support\TypographySettings::read($settings, 'photo_'.$photo->id.'_typography');
@endphp
<article class="gallery-item"
    role="button" tabindex="0"
    aria-label="{{ 'Otwórz zdjęcie: ' . ($photo->title ?: $photo->alt ?: $gallery->title) }}"
    @include('components.photo-typography-attributes', ['typography' => $photoTypography])
    data-photo-index="{{ $idx }}"
    data-photo-url="{{ $photo->imageUrl() }}"
    data-photo-alt="{{ $photo->alt ?: $photo->title ?: $gallery->title }}"
    data-photo-title="{{ $photo->title ?? '' }}"
    data-photo-description="{{ $photo->description ?? '' }}"
>
    <img src="{{ $photo->thumbnailUrl() }}"
         alt="{{ $photo->alt ?: $photo->title ?: $gallery->title }}"
         loading="lazy" decoding="async">
    @if($photo->title || $photo->description)
        <div class="gallery-caption" style="{{ $galleryCaptionCss }}">
            @if($photo->title)
                <strong @if($css = \App\Support\TypographySettings::css($photoTypography, 'title', $siteFonts)) style="{{ $css }}" @endif>{{ $photo->title }}</strong>
            @endif
            @if($photo->description)
                <span @if($css = \App\Support\TypographySettings::css($photoTypography, 'description', $siteFonts)) style="{{ $css }}" @endif>{{ $photo->description }}</span>
            @endif
        </div>
    @endif
</article>