@php
    $sections = collect($sections ?? [])->values();
    $fontCatalog = $fontCatalog ?? app(\App\Services\SiteFontLibrary::class)->catalog();
    $settings = $settings ?? [];
    $canvasId = $canvasId ?? 'builder-public-canvas';
    $canvasSettings = is_array($canvasSettings ?? null) ? $canvasSettings : [];
    $canvasBackground = $canvasSettings['background_color'] ?? $settings['background_color'] ?? '#ffffff';
    $designHeight = 900;
    $layerMap = [
        'image' => 10,
        'separator' => 20,
        'gallery' => 30,
        'thumbnail_gallery' => 30,
        'section' => 40,
        'text' => 100,
        'heading' => 110,
        'button' => 120,
    ];
@endphp

<div id="{{ $canvasId }}" class="builder-public-canvas" style="background:{{ $canvasBackground }};">
    @foreach($sections as $element)
        @php
            $type = $element['type'] ?? 'text';
            $content = $element['content'] ?? '';
            $style = is_array($element['style'] ?? null) ? $element['style'] : [];

            $x = max(0, min(100, (float) ($element['position_x'] ?? 5)));
            $y = max(0, (float) ($element['position_y'] ?? 5));
            $width = max(5, min(100 - $x, (float) ($element['element_width'] ?? 38)));
            $height = max(0, (float) ($element['element_height'] ?? 0));
            $top = ($y / 100) * $designHeight;
            $zIndex = $layerMap[$type] ?? 100;

            $fontFamily = \App\Services\SiteFontLibrary::css($style['font_family'] ?? null, $fontCatalog);
            $fontSize = (float) ($style['font_size'] ?? ($type === 'heading' ? 42 : 18));
            $fontWeight = (int) ($style['font_weight'] ?? 400);
            $color = $style['color'] ?? '#222222';
            $requestedTextAlign = $style['text_align'] ?? 'left';
            $textAlign = in_array($requestedTextAlign, ['left', 'center', 'right'], true)
                ? $requestedTextAlign
                : 'left';
            $lineHeight = max(0.45, min(4, (float) ($style['line_height'] ?? 1.4)));
            $letterSpacing = (float) ($style['letter_spacing'] ?? 0);
            $wordSpacing = (float) ($style['word_spacing'] ?? 0);
        @endphp

        <div
            @if(($element['id'] ?? null) === 'portfolio-heading') id="portfolio" @endif
            class="page-element page-element-{{ $type }}"
            style="
                left:{{ $x }}%;
                top:{{ $top }}px;
                width:{{ $width }}%;
                @if($height > 0) min-height:{{ $height }}px; @endif
                z-index:{{ $zIndex }};
                @if(in_array($type, ['heading','text','button','section','gallery'], true))
                    font-family:{{ $fontFamily }};
                    font-size:{{ $fontSize }}px;
                    font-weight:{{ $fontWeight }};
                    color:{{ $color }};
                    text-align:{{ $textAlign }};
                    line-height:{{ $lineHeight }};
                    letter-spacing:{{ $letterSpacing }}px;
                    word-spacing:{{ $wordSpacing }}px;
                @endif
            "
            data-builder-id="{{ $element['id'] ?? '' }}"
        >
            @if($type === 'image')
                @php
                    $photoRecord = \App\Models\Photo::find($element['photo_id'] ?? null);
                    $photoUrl = $photoRecord?->imageUrl() ?: ($element['photo_url'] ?? null);
                    $photoAlt = $photoRecord?->alt ?: ($element['photo_title'] ?? '');
                    $imageRadius = max(0, (int) ($element['image_radius'] ?? 0));
                    $imageHeight = max(0, (int) ($element['image_height'] ?? $element['element_height'] ?? 0));
                @endphp
                @if($photoUrl)
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $photoAlt }}"
                        loading="lazy"
                        decoding="async"
                        style="
                            display:block;
                            width:100%;
                            @if($imageHeight > 0) height:{{ $imageHeight }}px;object-fit:cover; @else height:auto; @endif
                            border-radius:{{ $imageRadius }}px;
                        "
                    >
                    @if(trim($element['caption'] ?? '') !== '')
                        <div
                            class="page-image-caption"
                            style="
                                white-space:pre-line;
                                color:#222;
                                text-align:left;
                                @if(!empty($element['caption_font_family'])) font-family:{{ \App\Services\SiteFontLibrary::css($element['caption_font_family'], $fontCatalog) }}; @endif
                                @if(!empty($element['caption_font_size'])) font-size:{{ (float) $element['caption_font_size'] }}px; @endif
                            "
                        >{{ $element['caption'] }}</div>
                    @endif
                @endif

            @elseif($type === 'heading')
                @php($headingTag = in_array($element['heading_level'] ?? '', ['h1', 'h2', 'h3'], true) ? $element['heading_level'] : 'div')
                <{{ $headingTag }} class="builder-public-text"
                    style="
                        margin:0;
                        font-family:{{ $fontFamily }};
                        font-size:{{ $fontSize }}px;
                        font-weight:{{ $fontWeight }};
                        color:{{ $color }};
                        text-align:{{ $textAlign }};
                        line-height:{{ $lineHeight }};
                        letter-spacing:{{ $letterSpacing }}px;
                        word-spacing:{{ $wordSpacing }}px;
                    "
                >{{ $content }}</{{ $headingTag }}>

            @elseif($type === 'text')
                <div class="builder-public-text"
                    style="
                        font-family:{{ $fontFamily }};
                        font-size:{{ $fontSize }}px;
                        font-weight:{{ $fontWeight }};
                        color:{{ $color }};
                        text-align:{{ $textAlign }};
                        line-height:{{ $lineHeight }};
                        letter-spacing:{{ $letterSpacing }}px;
                        word-spacing:{{ $wordSpacing }}px;
                    "
                >{!! nl2br(e($content)) !!}</div>

            @elseif($type === 'button')
                @php($buttonHref = \App\Support\BuilderButton::href($element['button_link'] ?? null))
                <a
                    class="builder-public-button"
                    @if($buttonHref) href="{{ $buttonHref }}" @endif
                    @if($buttonHref && ($element['button_new_tab'] ?? false)) target="_blank" rel="noopener noreferrer" @endif
                    style="
                        font-family:{{ $fontFamily }};
                        font-size:{{ $fontSize }}px;
                        font-weight:{{ $fontWeight }};
                        color:{{ $color }};
                        text-align:{{ $textAlign }};
                        line-height:{{ $lineHeight }};
                        letter-spacing:{{ $letterSpacing }}px;
                        word-spacing:{{ $wordSpacing }}px;
                        {{ \App\Support\BuilderButton::css($element) }}
                    "
                >{{ $content }}</a>

            @elseif($type === 'separator')
                <div style="height:1px;width:100%;background:{{ $color }};"></div>

            @elseif($type === 'section')
                <div class="builder-public-text"
                    style="
                        font-family:{{ $fontFamily }};
                        font-size:{{ $fontSize }}px;
                        font-weight:{{ $fontWeight }};
                        color:{{ $color }};
                        text-align:{{ $textAlign }};
                        line-height:{{ $lineHeight }};
                        letter-spacing:{{ $letterSpacing }}px;
                        word-spacing:{{ $wordSpacing }}px;
                    "
                >{{ $content }}</div>

            @elseif($type === 'thumbnail_gallery')
                @include('components.builder-thumbnail-gallery', ['element' => $element])

            @elseif($type === 'gallery')
                @include('components.builder-gallery', ['element' => $element, 'group' => 'builder-'.$loop->index])
            @endif
        </div>
    @endforeach
</div>

<style>
    .builder-public-canvas {
        position: relative;
        width: 100%;
        max-width: 1400px;
        min-height: 900px;
        margin: 0 auto;
        overflow: hidden;
        box-sizing: border-box;
    }

    .page-element {
        position: absolute;
        box-sizing: border-box;
        min-width: 1px;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .builder-public-text {
        width: 100%;
        min-height: inherit;
        box-sizing: border-box;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .page-element-image {
        overflow: hidden;
    }

    .builder-public-button {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        min-height: inherit;
        box-sizing: border-box;
        white-space: normal;
        overflow-wrap: anywhere;
        text-decoration: none;
    }

    .page-element-gallery {
        overflow: visible;
    }

    @media (max-width: 900px) {
        .builder-public-canvas {
            width: 100%;
            overflow-x: hidden;
        }
    }
</style>

<script>
(() => {
    const canvas = document.getElementById(@json($canvasId));
    if (!canvas) return;

    const fit = () => {
        let maxBottom = 900;
        canvas.querySelectorAll('.page-element').forEach((element) => {
            maxBottom = Math.max(maxBottom, element.offsetTop + element.offsetHeight + 40);
        });
        canvas.style.minHeight = Math.ceil(maxBottom) + 'px';
    };

    const observer = new ResizeObserver(fit);
    observer.observe(canvas);
    canvas.querySelectorAll('.page-element').forEach((element) => observer.observe(element));
    window.addEventListener('load', fit);
    fit();
})();
</script>
