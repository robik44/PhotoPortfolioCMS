@php
    $gv = \App\Support\GalleryVisualLayout::class;
    $orderedPhotos = $gv::orderedPhotos($gallery->photos, $visualLayout);
    $b = $visualLayout['blocks'];
    $cols = (int) $visualLayout['columns'];
    $gap = (int) $visualLayout['gap'];
    $photoHeight = (int) $visualLayout['photo_height'];
    $photoMode = $visualLayout['photo_mode'];
    $rows = $cols > 0 ? (int) ceil($orderedPhotos->count() / $cols) : 0;
    $pageHeight = max((float) $visualLayout['canvas_height'],
        (float) $b['photos']['y'] + $rows * ($photoHeight + $gap) + 65);
    foreach (['title','description','services','back'] as $blockId) {
        $pageHeight = max($pageHeight, (float) $b[$blockId]['y'] + 160);
    }
    $positions = $visualLayout['individual_photos'] ?? [];
    if ($photoMode === 'free') {
        $pageHeight = max((float) $visualLayout['canvas_height'], 500);
        foreach ($orderedPhotos as $i => $photo) {
            $p = $positions[(string) $photo->id] ?? [
                'x' => $b['photos']['x'] + ($i % 4) * 22,
                'y' => $b['photos']['y'] + floor($i / 4) * ($photoHeight + $gap),
                'w' => 20, 'h' => $photoHeight,
            ];
            $pageHeight = max($pageHeight, (float) $p['y'] + (float) $p['h'] + 70);
        }
    }
    foreach ($visualLayout['extras'] as $extra) {
        $pageHeight = max($pageHeight, (float) $extra['y'] + (float) ($extra['h'] ?? 120) + 60);
    }

    $pos = static fn (array $item) => 'left:'.$item['x'].'%;top:'.$item['y'].'px;width:'.$item['w'].'%;';
    $typeCss = static function (array $item, string $id) use ($gv): string {
        return 'font-family:'.$gv::font($item['font']).';'
            .'font-size:'.$item['size'].'px;font-weight:'.$item['weight'].';'
            .'line-height:'.$item['leading'].';letter-spacing:'.$item['spacing'].'px;'
            .'color:'.$item['color'].';';
    };
@endphp

<style>
    .gv-public-canvas{position:relative;max-width:1200px;width:100%;margin:auto;box-sizing:border-box}
    .gv-public-canvas .gv-block{position:absolute;box-sizing:border-box;min-width:0}
    .gv-public-canvas .gv-block h1,.gv-public-canvas .gv-block p{margin:0;white-space:pre-line}
    .gv-public-canvas .gv-text{white-space:pre-line;overflow-wrap:break-word}
    .gv-public-canvas .gv-links{display:flex;flex-wrap:wrap;gap:12px 22px}
    .gv-public-canvas .gv-links a{text-decoration:underline;text-underline-offset:3px}
    .gv-public-canvas .gv-back a{text-transform:uppercase;text-decoration:none}
    .gv-public-canvas .gv-grid{display:grid;grid-template-columns:repeat({{ $cols }},minmax(0,1fr));gap:{{ $gap }}px}
    .gv-public-canvas .gv-grid .gallery-item img{width:100%;height:{{ $photoHeight }}px;object-fit:{{ $visualLayout['photo_fit'] }}}
    .gv-public-canvas .gv-free-photo .gallery-item img{width:100%;height:100%;object-fit:{{ $visualLayout['photo_fit'] }}}
    .gv-public-canvas .gv-free-photo .gallery-item{height:100%}
    .gv-public-canvas .gallery-item{min-width:0;cursor:zoom-in}
    .gv-public-canvas .gv-button{display:inline-block;border:1px solid currentColor;padding:12px 18px;text-decoration:none}
    .gv-public-canvas .gv-divider{height:2px;width:100%}
    @foreach(array_merge($b, collect($visualLayout['extras'])->keyBy('id')->all()) as $id => $item)
        @if(isset($item['font']))
            @media (max-width:900px) and (min-width:601px){
                .gv-public-canvas .gv-block[data-gv-id="{{ $id }}"] .gv-text,
                .gv-public-canvas .gv-block[data-gv-id="{{ $id }}"] .gv-type {font-size:{{ $item['tablet'] }}px!important}
            }
            @media(max-width:600px){
                .gv-public-canvas .gv-block[data-gv-id="{{ $id }}"] .gv-text,
                .gv-public-canvas .gv-block[data-gv-id="{{ $id }}"] .gv-type {font-size:{{ $item['mobile'] }}px!important}
            }
        @endif
    @endforeach
    @media(max-width:900px){
        .gv-public-canvas{height:auto!important;padding:38px 5% 60px;display:flex;flex-direction:column;gap:20px}
        .gv-public-canvas>.gv-block{position:relative!important;left:auto!important;top:auto!important;width:100%!important}
        .gv-public-canvas .gv-grid{grid-template-columns:repeat({{ (int) $visualLayout['columns_tablet'] }},minmax(0,1fr));}
        .gv-public-canvas .gv-free-photo{max-width:100%;height:auto!important}
        .gv-public-canvas .gv-free-photo .gallery-item img{height:auto;max-height:600px}
    }
    @media(max-width:600px){
        .gv-public-canvas{padding:28px 20px 50px;gap:16px}
        .gv-public-canvas .gv-grid{grid-template-columns:repeat({{ (int) $visualLayout['columns_mobile'] }},minmax(0,1fr));gap:12px}
        .gv-public-canvas .gv-grid .gallery-item img{height:auto;max-height:500px;object-fit:contain}
        .gv-public-canvas .gv-links{gap:10px}
    }
</style>

<main class="gv-public-canvas" style="height:{{ $pageHeight }}px">
    <div class="gv-block" data-gv-id="title" style="{{ $pos($b['title']) }}">
        <h1 class="gv-text" style="{{ $typeCss($b['title'],'title') }}">{{ $gallery->title }}</h1>
    </div>

    @if($gallery->description)
        <div class="gv-block" data-gv-id="description" style="{{ $pos($b['description']) }}">
            <p class="gv-text" style="{{ $typeCss($b['description'],'description') }}">{{ $gallery->description }}</p>
        </div>
    @endif

    @if(count($relatedServices))
        <nav class="gv-block gv-links" data-gv-id="services" style="{{ $pos($b['services']) }}{{ $typeCss($b['services'],'services') }}" aria-label="Powiązane usługi">
            @foreach($relatedServices as $service)
                <a href="{{ url('/strona/'.$service['slug']) }}" class="gv-type">{{ $service['label'] }}</a>
            @endforeach
        </nav>
    @endif

    <div class="gv-block gv-back" data-gv-id="back" style="{{ $pos($b['back']) }}{{ $typeCss($b['back'],'back') }}">
        <a class="gv-type" href="{{ url('/') }}#portfolio">← {{ $backLink['back_text'] ?? 'Powrót do galerii' }}</a>
    </div>

    @if($orderedPhotos->count())
        @if($photoMode === 'grid')
            <section class="gv-block gv-grid" data-gv-id="photos" style="{{ $pos($b['photos']) }}">
                @foreach($orderedPhotos as $photo)
                    @include('components.gallery-visual-photo', ['photo' => $photo, 'idx' => $loop->index])
                @endforeach
            </section>
        @else
            @foreach($orderedPhotos as $photo)
                @php
                    $i = $loop->index;
                    $p = $positions[(string) $photo->id] ?? [
                        'x' => $b['photos']['x'] + ($i % 4) * 22,
                        'y' => $b['photos']['y'] + floor($i / 4) * ($photoHeight + $gap),
                        'w' => 20, 'h' => $photoHeight,
                    ];
                @endphp
                <section class="gv-block gv-free-photo" data-gv-id="photo-{{ $photo->id }}" style="{{ $pos($p) }}height:{{ $p['h'] }}px">
                    @include('components.gallery-visual-photo', ['photo' => $photo, 'idx' => $loop->index])
                </section>
            @endforeach
        @endif
    @else
        <p class="gv-block" data-gv-id="photos" style="{{ $pos($b['photos']) }}">Ta galeria nie zawiera jeszcze zdjęć.</p>
    @endif

    @foreach($visualLayout['extras'] as $element)
        @php
            $kind = $element['type'];
            $style = $pos($element);
        @endphp
        @if($kind === 'text')
            <div class="gv-block" data-gv-id="{{ $element['id'] }}" style="{{ $style }}">
                <p class="gv-text" style="{{ $typeCss($element, $element['id']) }}">{{ $element['text'] }}</p>
            </div>
        @elseif($kind === 'button')
            @php($link = \App\Support\BuilderButton::href($element['url'] ?? ''))
            <div class="gv-block" data-gv-id="{{ $element['id'] }}" style="{{ $style }}">
                <a class="gv-button gv-type" @if($link) href="{{ $link }}" @endif style="{{ $typeCss($element,$element['id']) }}">{{ $element['text'] }}</a>
            </div>
        @elseif($kind === 'divider')
            <div class="gv-block" data-gv-id="{{ $element['id'] }}" style="{{ $style }}">
                <div class="gv-divider" style="background:{{ $element['color'] }}"></div>
            </div>
        @elseif($kind === 'image')
            @php($selected = $gallery->photos->firstWhere('id', (int) ($element['photo_id'] ?? 0)))
            @if($selected)
                <div class="gv-block" data-gv-id="{{ $element['id'] }}" style="{{ $style }}">
                    <img src="{{ $selected->thumbnailUrl() }}" alt="{{ $selected->alt ?: $selected->title ?: $gallery->title }}"
                         loading="lazy" style="width:100%;height:{{ (int) ($element['h'] ?? 240) }}px;object-fit:{{ $visualLayout['photo_fit'] }};display:block">
                </div>
            @endif
        @endif
    @endforeach
</main>