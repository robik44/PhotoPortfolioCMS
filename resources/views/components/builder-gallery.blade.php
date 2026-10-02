@php
    $single = ($element['gallery_mode'] ?? 'all') === 'single';
    $galleries = $single
        ? \App\Models\Gallery::with('photos')->whereKey($element['gallery_id'] ?? 0)->get()
        : \App\Models\Gallery::with('photos')->orderBy('sort_order')->orderBy('title')->get();
    $galleryTitleTag = in_array($galleryTitleTag ?? 'div', ['div', 'h3'], true) ? ($galleryTitleTag ?? 'div') : 'div';
@endphp
<div class="builder-gallery-grid">
    @if($single)
        @foreach($galleries->first()?->photos ?? [] as $photo)
            @php
                $photoTypography = \App\Support\TypographySettings::read($settings, 'photo_'.$photo->id.'_typography');
                // A local Builder override takes precedence over the photo defaults.
                foreach (['family', 'size'] as $property) {
                    if (!empty($element['caption_font_'.$property])) $photoTypography['description_font_'.$property] = $element['caption_font_'.$property];
                }
            @endphp
            <article class="gallery-item builder-gallery-card" role="button" tabindex="0" style="cursor:zoom-in;"
                aria-label="{{ 'Otwórz zdjęcie: '.($photo->title ?: $photo->alt) }}"
                data-gallery-group="{{ $group }}"
                @include('components.photo-typography-attributes', ['typography' => $photoTypography])
                data-photo-url="{{ $photo->imageUrl() }}" data-photo-alt="{{ $photo->alt ?? '' }}"
                data-photo-title="{{ $photo->title ?? '' }}" data-photo-description="{{ $photo->description ?? '' }}">
                <img src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?? '' }}" loading="lazy" style="display:block;width:100%;aspect-ratio:1 / .7;object-fit:cover;">
            </article>
        @endforeach
    @else
        @foreach($galleries as $gallery)
            @php($cover = $gallery->photos->first(fn ($photo) => (bool) $photo->pivot->is_cover) ?? $gallery->photos->first())
            <a href="{{ route('portfolio.gallery', $gallery) }}" class="builder-gallery-card">
                @if($cover)
                    <img src="{{ $cover->imageUrl() }}" alt="{{ $cover->alt ?: $gallery->title }}" loading="lazy" style="display:block;width:100%;aspect-ratio:1 / .7;object-fit:cover;">
                @else
                    <div style="height:120px;display:flex;align-items:center;justify-content:center;background:#f3f3f3;color:#999;">Brak zdjęcia</div>
                @endif
                <{{ $galleryTitleTag }} class="builder-gallery-card-title">{{ $gallery->title }}</{{ $galleryTitleTag }}>
            </a>
        @endforeach
    @endif
</div>


<style>
    .builder-gallery-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        width: 100%;
    }

    .builder-gallery-card {
        display: block;
        min-width: 0;
        background: #fff;
        border: 1px solid #e5e5e5;
        overflow: hidden;
        color: inherit;
        text-decoration: none;
    }

    .builder-gallery-card-title {
        margin: 0;
        padding: 12px;
        font: inherit;
        color: inherit;
        text-align: inherit;
        line-height: 1.2;
        overflow-wrap: normal;
        word-break: normal;
        hyphens: none;
    }

    @media (max-width: 900px) {
        .builder-gallery-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }
    }

    @media (max-width: 560px) {
        .builder-gallery-grid {
            grid-template-columns: 1fr;
            gap: 18px;
        }

        .builder-gallery-card-title {
            padding: 14px 16px 16px;
        }
    }
</style>
