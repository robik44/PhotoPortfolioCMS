@php
    $sections = collect($sections ?? [])->values();
    $fontCatalog = $fontCatalog ?? app(\App\Services\SiteFontLibrary::class)->catalog();
    $settings = $settings ?? [];
    $canvasId = $canvasId ?? 'builder-public-canvas';
    $canvasSettings = is_array($canvasSettings ?? null) ? $canvasSettings : [];
    $canvasBackground = $settings['background_color'] ?? '#ffffff';
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
            $isTypographyTarget = in_array($type, \App\Services\SiteFontLibrary::TEXT_BLOCKS, true);
            $typographyCss = $isTypographyTarget
                ? \App\Support\BuilderTypography::inlineCss($element, $fontCatalog)
                : '';

            // Visual typography and semantic HTML are intentionally independent.
            // Existing headings keep their legacy heading_level. Text blocks default to <p>.
            $allowedSemanticTags = ['div', 'p', 'h1', 'h2', 'h3', 'small'];
            $homepageSemanticDefault = $canvasId === 'home-public-builder-canvas'
                ? match ($element['id'] ?? null) {
                    'hero-heading' => 'h1',
                    'hero-text' => 'p',
                    'portfolio-heading' => 'h2',
                    default => null,
                }
                : null;
            $requestedSemanticTag = $element['semantic_tag']
                ?? ($type === 'heading' ? ($element['heading_level'] ?? $homepageSemanticDefault ?? 'div')
                    : ($type === 'text' ? ($homepageSemanticDefault ?? 'p') : 'div'));
            $semanticTag = in_array($requestedSemanticTag, $allowedSemanticTags, true)
                ? $requestedSemanticTag
                : 'div';
        @endphp

        <div
            @if(($element['id'] ?? null) === 'portfolio-heading') id="portfolio" @endif
            class="page-element page-element-{{ $type }}"
            style="
                --mobile-order:{{ (int) round($y * 1000) }};
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
            data-builder-index="{{ $loop->index }}"
        >
            @if($type === 'image')
                @php
                    $photoRecord = \App\Models\Photo::find($element['photo_id'] ?? null);
                    $photoUrl = $photoRecord?->imageUrl() ?: ($element['photo_url'] ?? null);
                    $photoAlt = $photoRecord?->alt ?: ($element['photo_title'] ?? '');
                    $imageRadius = max(0, (int) ($element['image_radius'] ?? 0));
                    $imageHeight = max(0, (int) ($element['image_height'] ?? $element['element_height'] ?? 0));
                    $isPriorityImage = $canvasId === 'home-public-builder-canvas'
                        && ($element['id'] ?? null) === 'hero-image';
                @endphp
                @if($photoUrl)
                    @php($imageHref = \App\Support\BuilderButton::href($element['image_link'] ?? null))
                    @if($imageHref)<a href="{{ $imageHref }}" class="builder-public-image-link" style="display:block;">@endif
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $photoAlt }}"
                        @if($isPriorityImage)
                            loading="eager"
                            fetchpriority="high"
                        @else
                            loading="lazy"
                        @endif
                        decoding="async"
                        style="
                            display:block;
                            width:100%;
                            @if($imageHeight > 0) height:{{ $imageHeight }}px;object-fit:cover; @else height:auto; @endif
                            border-radius:{{ $imageRadius }}px;
                        "
                    >
                    @if($imageHref)</a>@endif
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
                <{{ $semanticTag }} class="builder-public-text" data-builder-typography-target
                    style="margin:0;{{ $typographyCss }}"
                >{{ $content }}</{{ $semanticTag }}>

            @elseif($type === 'text')
                <{{ $semanticTag }} class="builder-public-text" data-builder-typography-target style="margin:0;{{ $typographyCss }}">
{!! nl2br(e($content)) !!}</{{ $semanticTag }}>

            @elseif($type === 'button')
                @php($buttonHref = \App\Support\BuilderButton::href($element['button_link'] ?? null))
                <a
                    class="builder-public-button" data-builder-typography-target
                    @if($buttonHref) href="{{ $buttonHref }}" @endif
                    @if($buttonHref && ($element['button_new_tab'] ?? false)) target="_blank" rel="noopener noreferrer" @endif
                    style="{{ $typographyCss }}{{ \App\Support\BuilderButton::css($element) }}"
                >{{ $content }}</a>

            @elseif($type === 'separator')
                <div style="height:1px;width:100%;background:{{ $color }};"></div>

            @elseif($type === 'section')
                <div class="builder-public-text" data-builder-typography-target style="{{ $typographyCss }}">{{ $content }}</div>

            @elseif($type === 'thumbnail_gallery')
                @include('components.builder-thumbnail-gallery', ['element' => $element])

            @elseif($type === 'gallery')
                <div data-builder-typography-target style="{{ $typographyCss }}">
                    @include('components.builder-gallery', [
                        'element' => $element,
                        'group' => 'builder-'.$loop->index,
                        'galleryTitleTag' => $canvasId === 'home-public-builder-canvas' ? 'h3' : 'div',
                    ])
                </div>
            @endif
        </div>
    @endforeach
</div>

<style>
    @foreach($sections as $element)
        @php($responsiveType = $element['type'] ?? 'text')
        @if(in_array($responsiveType, \App\Services\SiteFontLibrary::TEXT_BLOCKS, true))
            {!! \App\Support\BuilderTypography::responsiveCss(
                $element,
                $fontCatalog,
                '#' . $canvasId . ' .page-element[data-builder-index="' . $loop->index . '"] [data-builder-typography-target]'
            ) !!}
        @endif
    @endforeach

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
        display: block;
        width: 100%;
        min-height: inherit;
        box-sizing: border-box;
        white-space: normal;
        overflow-wrap: anywhere;
    }

    .builder-public-text p + p,
    [data-builder-typography-target] p + p {
        margin-top: var(--typography-paragraph-spacing, 0px);
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
            min-height: 0;
            overflow: visible;
            display: flex;
            flex-direction: column;
            gap: 28px;
            padding: 0 18px 36px;
        }

        .builder-public-canvas .page-element {
            position: relative !important;
            left: 0 !important;
            top: auto !important;
            width: 100% !important;
            min-height: 0 !important;
            order: var(--mobile-order, 0);
        }

        .builder-public-canvas .page-element-image img {
            height: auto !important;
            object-fit: contain !important;
        }

        .builder-public-canvas .builder-public-text,
        .builder-public-canvas [data-builder-typography-target] {
            overflow-wrap: normal;
            word-break: normal;
        }

        #home-public-builder-canvas {
            padding: 0 0 40px;
            gap: 28px;
        }

        #home-public-builder-canvas .page-element:not(.page-element-image) {
            padding-left: 18px;
            padding-right: 18px;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-image"] {
            position: relative !important;
            left: 0 !important;
            top: auto !important;
            width: 100% !important;
            padding: 0 !important;
            overflow: hidden;
            order: 0;
            z-index: 10 !important;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-image"] img {
            width: 100% !important;
            height: clamp(360px, 110vw, 500px) !important;
            object-fit: cover !important;
            border-radius: 0 !important;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-heading"] {
            position: absolute !important;
            left: 5% !important;
            top: 34px !important;
            bottom: auto !important;
            width: 88% !important;
            padding: 0 !important;
            order: 0;
            z-index: 120 !important;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-heading"] [data-builder-typography-target] {
            font-size: clamp(26px, 7vw, 34px) !important;
            line-height: 1.02 !important;
            letter-spacing: .01em !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-text"] {
            position: absolute !important;
            left: 5% !important;
            top: auto !important;
            bottom: 28px !important;
            width: 82% !important;
            padding: 0 !important;
            order: 0;
            z-index: 120 !important;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-text"] [data-builder-typography-target] {
            font-size: clamp(14px, 4vw, 18px) !important;
            line-height: 1.2 !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        #home-public-builder-canvas .page-element[data-builder-id="portfolio-heading"] {
            margin-top: 8px;
        }

        #home-public-builder-canvas .page-element-text:not([data-builder-id="hero-text"]) [data-builder-typography-target] {
            font-size: clamp(15px, 4.2vw, 18px) !important;
            line-height: 1.5 !important;
            letter-spacing: 0 !important;
            word-spacing: normal !important;
            overflow-wrap: normal !important;
            word-break: normal !important;
        }

        #home-public-builder-canvas .page-element-heading:not([data-builder-id="hero-heading"]) [data-builder-typography-target] {
            overflow-wrap: normal !important;
            word-break: normal !important;
        }
    }

    @media (max-width: 560px) {
        .builder-public-canvas {
            gap: 24px;
            padding-left: 14px;
            padding-right: 14px;
        }

        #home-public-builder-canvas {
            padding-left: 0;
            padding-right: 0;
        }

        #home-public-builder-canvas .page-element:not(.page-element-image) {
            padding-left: 16px;
            padding-right: 16px;
        }

        #home-public-builder-canvas .page-element[data-builder-id="hero-heading"],
        #home-public-builder-canvas .page-element[data-builder-id="hero-text"] {
            padding: 0 !important;
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
