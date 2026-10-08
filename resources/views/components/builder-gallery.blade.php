@php
    $mode = $element['gallery_mode'] ?? 'all';
    $single = $mode === 'single';
    $collectionId = is_numeric($element['gallery_collection_id'] ?? null) ? (int) $element['gallery_collection_id'] : null;
    $selectedIds = collect($element['gallery_ids'] ?? [])->filter(fn ($id) => is_numeric($id) && $id > 0)->map(fn ($id) => (int) $id)->unique()->values();

    $galleryQuery = \App\Models\Gallery::with('photos')
        ->where('published', true)
        ->when($collectionId, fn ($query) => $query->where('gallery_collection_id', $collectionId));

    if ($single) {
        $galleries = (clone $galleryQuery)->whereKey($element['gallery_id'] ?? 0)->get();
    } elseif ($mode === 'selected') {
        $galleryMap = (clone $galleryQuery)->whereIn('id', $selectedIds)->get()->keyBy('id');
        $galleries = $selectedIds->map(fn ($id) => $galleryMap->get($id))->filter()->values();
    } else {
        $galleries = $galleryQuery->orderBy('sort_order')->orderBy('title')->get();
    }
    $columns = max(1, min(12, (int) ($element['gallery_columns'] ?? 4)));
    $gap = max(0, min(100, (int) ($element['gallery_gap'] ?? 14)));
    $align = in_array(($element['gallery_align'] ?? 'left'), ['left','center','right'], true) ? ($element['gallery_align'] ?? 'left') : 'left';
    $cardSettings = is_array($element['gallery_card_settings'] ?? null) ? $element['gallery_card_settings'] : [];
    $justify = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'][$align];
    $cardRatio = in_array(($element['gallery_ratio'] ?? '1 / .7'), ['1 / .7', '1 / 1', '4 / 3', '3 / 2', '16 / 9'], true)
        ? ($element['gallery_ratio'] ?? '1 / .7') : '1 / .7';
    $galleryTitleTag = in_array($galleryTitleTag ?? 'div', ['div', 'h3'], true) ? ($galleryTitleTag ?? 'div') : 'div';
@endphp
<div class="builder-gallery-grid {{ $single ? 'is-single' : 'is-covers' }}" style="--bg-columns:{{ $columns }};--bg-gap:{{ $gap }}px;--bg-ratio:{{ $cardRatio }};--bg-justify:{{ $justify }};">
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
                <img src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?? '' }}" loading="lazy" style="display:block;width:100%;aspect-ratio:var(--bg-ratio,1 / .7);object-fit:cover;">
            </article>
        @endforeach
    @else
        @foreach($galleries as $gallery)
            @php
                $cover = $gallery->photos->first(fn ($photo) => (bool) $photo->pivot->is_cover) ?? $gallery->photos->first();
                $local = is_array($cardSettings[$gallery->id] ?? null) ? $cardSettings[$gallery->id] : [];
                $rawWidth = isset($local['width']) && is_numeric($local['width']) ? (float) $local['width'] : 0;
                $cardWidth = $rawWidth > 0 ? max(5, min(100, $rawWidth)) : 0;
                $cardOffset = max(-2000, min(2000, (float) ($local['x_offset'] ?? 0)));
                $defaultBasis = "calc((100% - " . ($columns - 1) . " * var(--bg-gap,14px)) / " . $columns . ")";
            @endphp
            <a href="{{ route('portfolio.gallery', $gallery) }}" class="builder-gallery-card"
               style="flex-basis:{{ $cardWidth > 0 ? $cardWidth.'%' : $defaultBasis }};transform:translateX({{ $cardOffset }}px);">
                @if($cover)
                    <img src="{{ $cover->imageUrl() }}" alt="{{ $cover->alt ?: $gallery->title }}" loading="lazy" style="display:block;width:100%;aspect-ratio:var(--bg-ratio,1 / .7);object-fit:cover;">
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
        gap: var(--bg-gap, 14px);
        width: 100%;
    }

    .builder-gallery-grid.is-single {
        display: grid;
        grid-template-columns: repeat(var(--bg-columns, 4), minmax(0, 1fr));
    }

    .builder-gallery-grid.is-covers {
        display: flex;
        flex-wrap: wrap;
        justify-content: var(--bg-justify, flex-start);
        align-items: flex-start;
    }

    .builder-gallery-card {
        display: block;
        min-width: 0;
        box-sizing: border-box;
        flex: 0 0 auto;
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

</style>
