@php
    $mosaicCards = array_slice(is_array($element['mosaic_cards'] ?? null) ? $element['mosaic_cards'] : [], 0, 6);
    while (count($mosaicCards) < 6) $mosaicCards[] = ['gallery_id' => null];
    $mosaicIds = collect($mosaicCards)->pluck('gallery_id')->filter(fn($id) => is_numeric($id) && $id > 0)->unique()->all();
    $mosaicGalleryMap = \App\Models\Gallery::with('photos')->where('published', true)->whereIn('id', $mosaicIds)->get()->keyBy('id');
    // A card may link to a category page while displaying ANY photo from the
    // photo library, even when the photo is not assigned to a gallery.
    $mosaicPhotoIds = collect($mosaicCards)->pluck('photo_id')->filter(fn($id) => is_numeric($id) && $id > 0)->unique()->all();
    $mosaicPhotoMap = \App\Models\Photo::whereIn('id', $mosaicPhotoIds)->get()->keyBy('id');
    $mosaicLayout = ($element['mosaic_layout'] ?? 'editorial') === 'even' ? 'even' : 'editorial';
    $mosaicGap = max(0, min(60, (int)($element['mosaic_gap'] ?? 12)));
    $mosaicRowHeight = max(150, min(550, (int)($element['mosaic_row_height'] ?? 260)));
    $mosaicSide = max(17, min(34, (float)($element['mosaic_side_width'] ?? 26)));
    $mosaicTitleSize = max(22, min(76, (float)($element['mosaic_title_size'] ?? 44)));
    $mosaicMoreLink = \App\Support\BuilderButton::href($element['mosaic_more_link'] ?? '/#portfolio') ?: url('/#portfolio');
    $mosaicHeaderTitle = (string)($element['mosaic_title'] ?? "Piękne obrazy\ndla smacznych historii");
    $mosaicShowDescriptions = (bool)($element['mosaic_descriptions'] ?? true);
    $mosaicTypography = is_array($element['mosaic_typography'] ?? null) ? $element['mosaic_typography'] : [];
    $mosaicCatalog = app(\App\Services\SiteFontLibrary::class)->catalog();
    $mosaicTextCss = static fn(string $role, array $card = []) =>
        \App\Support\MosaicTypography::css(
            array_replace(
                is_array($mosaicTypography[$role] ?? null) ? $mosaicTypography[$role] : [],
                is_array($card['typography'][$role] ?? null) ? $card['typography'][$role] : []
            ),
            $mosaicCatalog
        );
    $mosaicMoreButton = (bool)($element['mosaic_more_button'] ?? false);
    $mosaicMoreBg = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($element['mosaic_more_bg'] ?? ''))
        ? $element['mosaic_more_bg'] : '#58754a';
    $mosaicMoreBorder = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($element['mosaic_more_border'] ?? ''))
        ? $element['mosaic_more_border'] : '#58754a';
    $mosaicMoreRadius = max(0, min(100, (float)($element['mosaic_more_radius'] ?? 40)));
    $mosaicMorePadX = max(0, min(80, (float)($element['mosaic_more_padding_x'] ?? 22)));
    $mosaicMorePadY = max(0, min(50, (float)($element['mosaic_more_padding_y'] ?? 12)));
@endphp
<section class="ff-mosaic ff-mosaic-desktop-fixed" style="--fm-gap:{{ $mosaicGap }}px;--fm-row:{{ $mosaicRowHeight }}px;--fm-side:{{ $mosaicSide }}%;--fm-title:{{ $mosaicTitleSize }}px;" aria-label="Wybrane realizacje FoodFoto">
    <div class="ff-mosaic-header">
        <div>
            <span class="ff-mosaic-kicker" style="{{ $mosaicTextCss('kicker') }}">{{ $element['mosaic_kicker'] ?? 'WYBRANE REALIZACJE' }}</span>
            <h2 class="ff-mosaic-heading" style="{{ $mosaicTextCss('heading') }}">{{ $mosaicHeaderTitle }}</h2>
        </div>
        <p class="ff-mosaic-intro" style="{{ $mosaicTextCss('intro') }}">{{ $element['mosaic_intro'] ?? 'Fotografia żywności i stylizacja, które podkreślają charakter produktu i marki.' }}</p>
        <a class="ff-mosaic-more {{ $mosaicMoreButton ? 'is-button' : '' }}" href="{{ $mosaicMoreLink }}" style="{{ $mosaicTextCss('more') }};--fm-more-bg:{{ $mosaicMoreBg }};--fm-more-border:{{ $mosaicMoreBorder }};--fm-more-radius:{{ $mosaicMoreRadius }}px;--fm-more-pad-x:{{ $mosaicMorePadX }}px;--fm-more-pad-y:{{ $mosaicMorePadY }}px">{{ $element['mosaic_more_label'] ?? 'ZOBACZ WSZYSTKIE REALIZACJE' }} {{ $element['mosaic_more_action_text'] ?? '→' }}</a>
    </div>
    <div class="ff-mosaic-grid {{ $mosaicLayout === 'even' ? 'is-even' : '' }}">
        @foreach($mosaicCards as $i => $mosaicCard)
            @php
                $mosaicCard = is_array($mosaicCard) ? $mosaicCard : [];
                $gallery = $mosaicGalleryMap->get((int)($mosaicCard['gallery_id'] ?? 0));
                $photo = $mosaicPhotoMap->get((int)($mosaicCard['photo_id'] ?? 0))
                    ?? $gallery?->photos?->first(fn($p) => (int)$p->id === (int)($mosaicCard['photo_id'] ?? 0));
                if (!$photo && $gallery) $photo = $gallery->photos->first(fn($p) => (bool)$p->pivot?->is_cover) ?? $gallery->photos->first();
                $cardLink = \App\Support\BuilderButton::href($mosaicCard['link'] ?? null) ?: ($gallery ? route('portfolio.gallery', ['gallery' => $gallery->slug]) : null);
                $cardTitle = trim((string)($mosaicCard['title'] ?? '')) ?: ($gallery?->title ?? 'Wybierz galerię');
                $cardCategory = trim((string)($mosaicCard['category'] ?? '')) ?: $cardTitle;
                $cardDescription = trim((string)($mosaicCard['description'] ?? ''));
                $imageY = max(0, min(100, (int)($mosaicCard['crop_y'] ?? 50)));
                $imageX = max(0, min(100, (int)($mosaicCard['crop_x'] ?? 50)));
            @endphp
            <a class="ff-mosaic-card" @if($cardLink) href="{{ $cardLink }}" @else aria-disabled="true" tabindex="-1" @endif aria-label="{{ $cardTitle }}">
                <div class="ff-mosaic-photo">
                    @if($photo)
                        <img class="ff-mosaic-image" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: ($photo->title ?: $cardTitle) }}" loading="lazy" decoding="async" style="object-position:{{ $imageX }}% {{ $imageY }}%;">
                    @else
                        <div class="ff-mosaic-placeholder">Wybierz zdjęcie</div>
                    @endif
                </div>
                <div class="ff-mosaic-card-meta">
                    <div class="ff-mosaic-text">
                        <h3 class="ff-mosaic-card-category" style="{{ $mosaicTextCss('category', $mosaicCard) }}">{{ $cardCategory }}</h3>
                        @if($mosaicShowDescriptions && $cardDescription !== '')
                            <p class="ff-mosaic-card-description" style="{{ $mosaicTextCss('description', $mosaicCard) }}">{{ $cardDescription }}</p>
                        @endif
                    </div>
                    <span class="ff-mosaic-arrow" aria-hidden="true" style="{{ $mosaicTextCss('arrow', $mosaicCard) }}">{{ $mosaicCard['action_text'] ?? '→' }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>