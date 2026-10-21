@php
    $sections = collect($sections ?? [])->values();
    $fontCatalog = $fontCatalog ?? app(\App\Services\SiteFontLibrary::class)->catalog();
    $settings = $settings ?? [];
    $canvasId = $canvasId ?? 'builder-public-canvas';
    $canvasSettings = is_array($canvasSettings ?? null) ? $canvasSettings : [];
    $canvasBackground = $settings['background_color'] ?? '#ffffff';
    $designHeight = 900;
    // Use the first visually placed gallery as the Portfolio scroll target.
    // Do not depend on a heading: it can be edited or deleted in the builder.
    $portfolioAnchorIndex = $canvasId === 'home-public-builder-canvas'
        ? $sections
            ->filter(fn ($element) => in_array(($element['type'] ?? ''), ['gallery_mosaic', 'gallery', 'thumbnail_gallery'], true))
            ->sortBy(fn ($element) => (float) ($element['position_y'] ?? 0))
            ->keys()
            ->first()
        : null;
    $layerMap = [
        'image' => 10,
        'color_panel' => 2,
        'separator' => 20,
        'gallery' => 30,
        'gallery_mosaic' => 30,
        'thumbnail_gallery' => 30,
        'section' => 40,
        'text' => 100,
        'heading' => 110,
        'button' => 120,
    ];
@endphp

@if($sections->contains(fn ($item) => ($item['type'] ?? '') === 'gallery_mosaic'))
    <link rel="stylesheet" href="{{ asset('css/foodfoto-gallery-mosaic.css') }}?v={{ @filemtime(public_path('css/foodfoto-gallery-mosaic.css')) ?: 1 }}">
@endif
@if($sections->contains(fn ($item) => ($item['type'] ?? '') === 'process_steps'))
    <link rel="stylesheet" href="{{ asset('css/foodfoto-process.css') }}?v={{ @filemtime(public_path('css/foodfoto-process.css')) ?: 1 }}">
@endif

<div id="{{ $canvasId }}" class="builder-public-canvas" style="background:{{ $canvasBackground }};">
    @foreach($sections as $element)
        @php
            $type = $element['type'] ?? 'text';
            $content = $element['content'] ?? '';
            $style = is_array($element['style'] ?? null) ? $element['style'] : [];

            $x = max(0, min(100, (float) ($element['position_x'] ?? 5)));
            $y = max(0, (float) ($element['position_y'] ?? 5));
            // Keep the public geometry identical to the visual editor.
            // The editor allows a box to extend past the right edge and clips it at the canvas.
            // Do not shrink the box based on X here, because that changes all inner percentage sizing.
            $width = max(1, min(100, (float) ($element['element_width'] ?? 38)));
            $height = max(0, (float) ($element['element_height'] ?? 0));
            $top = ($y / 100) * $designHeight;
            $zIndex = $layerMap[$type] ?? 100;
            if ($type === 'color_panel') {
                $zIndex = max(0, min(50, (int) ($element['panel_layer'] ?? 2)));
            } elseif ($type === 'image' && isset($element['image_layer'])) {
                $zIndex = max(0, min(50, (int) $element['image_layer']));
            }

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
            @if($portfolioAnchorIndex !== null && $loop->index === $portfolioAnchorIndex) id="portfolio" @endif
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
            data-flow-mode="{{ ($element['flow_mode'] ?? 'free') === 'auto' ? 'auto' : 'free' }}"
            data-flow-group="{{ $element['flow_group'] ?? '' }}"
            data-insertion-targets="{{ isset($element['flow_insertion_targets']) ? json_encode($element['flow_insertion_targets']) : '' }}"
            data-insertion-existing="{{ isset($element['flow_insertion_existing']) ? json_encode($element['flow_insertion_existing']) : '' }}"
            data-insertion-target-top="{{ isset($element['flow_insertion_target_top']) ? $element['flow_insertion_target_top'] : '' }}"
            data-flow-baseline="{{ max(0, (float) ($element['flow_base_height'] ?? 0)) }}"
            data-flow-anchor="{{ (float) ($element['flow_anchor'] ?? 0) }}"
            data-flow-contributions="{{ isset($element['flow_anchor_contributions']) ? json_encode((object) $element['flow_anchor_contributions']) : '' }}"
            data-base-top="{{ $top }}"
        >
            @if($type === 'icon')
                @php
                    $phoneSize = max(16, min(120, (float) ($element['icon_size'] ?? 38)));
                    $phoneColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($element['icon_color'] ?? ''))
                        ? $element['icon_color'] : '#222222';
                @endphp
                <svg xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    width="{{ $phoneSize }}" height="{{ $phoneSize }}"
                    role="img" aria-label="Telefon"
                    fill="none" stroke="{{ $phoneColor }}" stroke-width="1.65"
                    stroke-linecap="round" stroke-linejoin="round"
                    style="display:block;max-width:none">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.8 19.8 0 0 1 3.09 5.18 2 2 0 0 1 5.08 3h3a2 2 0 0 1 2 1.72c.12.96.34 1.9.66 2.8a2 2 0 0 1-.45 2.11L8.5 11.41a16 16 0 0 0 4.09 4.09l1.78-1.79a2 2 0 0 1 2.11-.45c.9.32 1.84.54 2.8.66A2 2 0 0 1 22 16.92z"/>
                </svg>

            @elseif($type === 'color_panel')
                @php
                    $panelHex = preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($element['panel_color'] ?? ''))
                        ? $element['panel_color'] : '#ffffff';
                    $panelOpacity = max(0, min(100, (float) ($element['panel_opacity'] ?? 100))) / 100;
                    $panelRgb = implode(',', [
                        hexdec(substr($panelHex, 1, 2)),
                        hexdec(substr($panelHex, 3, 2)),
                        hexdec(substr($panelHex, 5, 2))
                    ]);
                    $panelHeight = max(1, min(1500, (float) ($element['element_height'] ?? 200)));
                @endphp
                <div aria-hidden="true" style="width:100%;height:{{ $panelHeight }}px;background:rgba({{ $panelRgb }},{{ $panelOpacity }});"></div>

            @elseif($type === 'image')
                @php
                    $photoRecord = \App\Models\Photo::find($element['photo_id'] ?? null);
                    $photoUrl = $photoRecord?->imageUrl() ?: ($element['photo_url'] ?? null);
                    $photoAlt = $photoRecord?->alt ?: ($photoRecord?->title ?: ($element['photo_title'] ?? ''));
                    $photoFile = $photoRecord ? public_path('storage/'.\App\Models\Photo::storagePath($photoRecord->webp ?: $photoRecord->filename)) : null;
                    $photoSize = $photoFile && is_file($photoFile) ? @getimagesize($photoFile) : false;
                    $imageRadius = max(0, (int) ($element['image_radius'] ?? 0));
                    $imageHeight = max(0, (int) ($element['image_height'] ?? $element['element_height'] ?? 0));
                    $imageFit = ($element['image_fit'] ?? 'cover') === 'contain' ? 'contain' : 'cover';
                    $imageRatio = in_array(($element['image_ratio'] ?? 'auto'), ['auto', '1 / 1', '4 / 3', '3 / 2', '16 / 9'], true)
                        ? ($element['image_ratio'] ?? 'auto') : 'auto';
                    $isPriorityImage = $canvasId === 'home-public-builder-canvas'
                        && ($element['id'] ?? null) === 'hero-image';
                @endphp
                @if($photoUrl)
                    @php
                        $imageZoom = (bool) ($element['image_lightbox'] ?? false);
                        $imageHref = $imageZoom ? null : \App\Support\BuilderButton::href($element['image_link'] ?? null);
                    @endphp
                    @if($imageZoom)
                        <button
                            type="button"
                            class="builder-zoom-trigger"
                            data-builder-image-zoom
                            data-zoom-url="{{ $photoUrl }}"
                            data-zoom-alt="{{ $photoAlt }}"
                            aria-label="{{ $photoAlt ? 'Powiększ zdjęcie: '.$photoAlt : 'Powiększ zdjęcie' }}"
                        >
                    @elseif($imageHref)
                        <a href="{{ $imageHref }}" class="builder-public-image-link" style="display:block;">
                    @endif
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $photoAlt }}"
                        @if($photoSize) width="{{ $photoSize[0] }}" height="{{ $photoSize[1] }}" @endif
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
                            @if($imageHeight > 0)
                                height:{{ $imageHeight }}px;
                                object-fit:{{ $imageFit }};
                            @elseif($imageRatio !== 'auto')
                                aspect-ratio:{{ $imageRatio }};
                                height:auto;
                                object-fit:{{ $imageFit }};
                            @else
                                height:auto;
                                object-fit:contain;
                            @endif
                            border-radius:{{ $imageRadius }}px;
                        "
                    >
                    @if($imageZoom)
                        </button>
                    @elseif($imageHref)
                        </a>
                    @endif
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
                @php
                    $textLink = \App\Support\BuilderButton::href($element['text_link'] ?? null);
                    $exactEmail = filter_var(trim((string) $content), FILTER_VALIDATE_EMAIL) ? trim((string) $content) : null;
                    $textHref = $textLink ?: ($exactEmail ? 'mailto:'.$exactEmail : null);
                    $mailTarget = $textHref && preg_match('/^mailto:([^?]+)/i', $textHref, $mailMatches)
                        ? rawurldecode($mailMatches[1]) : null;
                    $protectedTextEmail = $mailTarget && filter_var($mailTarget, FILTER_VALIDATE_EMAIL)
                        ? $mailTarget : null;
                @endphp
                <{{ $semanticTag }} class="builder-public-text" data-builder-typography-target style="margin:0;{{ $typographyCss }}">
@if($protectedTextEmail)<a href="{{ url('/kontakt') }}"
    data-ff-email="{{ base64_encode($protectedTextEmail) }}"
    data-ff-email-label="{{ base64_encode((string) $content) }}"
    style="color:inherit;text-decoration:underline;text-underline-offset:3px;">Napisz e-mail</a>@elseif($textHref)<a href="{{ $textHref }}" style="color:inherit;text-decoration:underline;text-underline-offset:3px;">{!! nl2br(e($content)) !!}</a>@else{!! nl2br(e($content)) !!}@endif</{{ $semanticTag }}>

            @elseif($type === 'button')
                @php
                    $buttonHref = \App\Support\BuilderButton::href($element['button_link'] ?? null);
                    $buttonMailTarget = $buttonHref && preg_match('/^mailto:([^?]+)/i', $buttonHref, $buttonMailMatches)
                        ? rawurldecode($buttonMailMatches[1]) : null;
                    $protectedButtonEmail = $buttonMailTarget && filter_var($buttonMailTarget, FILTER_VALIDATE_EMAIL)
                        ? $buttonMailTarget : null;
                @endphp
                <a
                    class="builder-public-button" data-builder-typography-target
                    @if($protectedButtonEmail)
                        href="{{ url('/kontakt') }}"
                        data-ff-email="{{ base64_encode($protectedButtonEmail) }}"
                        data-ff-email-label="{{ base64_encode((string) $content) }}"
                    @elseif($buttonHref) href="{{ $buttonHref }}" @endif
                    @if($buttonHref && !$protectedButtonEmail && ($element['button_new_tab'] ?? false)) target="_blank" rel="noopener noreferrer" @endif
                    style="{{ $typographyCss }}{{ \App\Support\BuilderButton::css($element) }}"
                >{{ $protectedButtonEmail ? 'Napisz e-mail →' : $content }}</a>

            @elseif($type === 'process_steps')
                @include('components.builder-process-steps', ['element' => $element, 'fontCatalog' => $fontCatalog])

            @elseif($type === 'separator')
                <div style="height:1px;width:100%;background:{{ $color }};"></div>

            @elseif($type === 'section')
                <div class="builder-public-text" data-builder-typography-target style="{{ $typographyCss }}">{{ $content }}</div>

            @elseif($type === 'thumbnail_gallery')
                @include('components.builder-thumbnail-gallery', ['element' => $element])

            @elseif($type === 'gallery_mosaic')
                @include('components.builder-gallery-mosaic', ['element' => $element])

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
        @elseif($responsiveType === 'process_steps')
            @php($processSectionIndex = $loop->index)
            @foreach(\App\Support\ProcessTypography::parts($element) as $partKey => $textPart)
                {!! \App\Support\BuilderTypography::responsiveCss(
                    $textPart,
                    $fontCatalog,
                    '#' . $canvasId . ' .page-element[data-builder-index="' . $processSectionIndex . '"] [data-process-part="' . $partKey . '"]'
                ) !!}
            @endforeach
        @endif
    @endforeach

    .builder-public-canvas {
        position: relative;
        width: 1400px;
        max-width: none;
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

</style>

<script src="{{ asset('js/builder-auto-flow.js') }}?v={{ @filemtime(public_path('js/builder-auto-flow.js')) ?: 1 }}"></script>
<script>
(() => {
    const canvas = document.getElementById(@json($canvasId));
    if (!canvas) return;

    const fit = () => {
        const nodes = Array.from(canvas.querySelectorAll('.page-element'));
        const model = nodes.map(node => ({
            id: node.dataset.builderId,
            type: node.className.match(/page-element-(\w+)/)?.[1] || '',
            flowGroup: node.dataset.flowGroup || null,
            insertionTargets: node.dataset.insertionTargets
                ? JSON.parse(node.dataset.insertionTargets) : null,
            insertionExisting: node.dataset.insertionExisting
                ? JSON.parse(node.dataset.insertionExisting) : null,
            insertionTargetTop: node.dataset.insertionTargetTop
                ? Number(node.dataset.insertionTargetTop) : null,
            anchorContributions: node.dataset.flowContributions
                ? JSON.parse(node.dataset.flowContributions) : null,
            x: node.offsetLeft,
            y: Number(node.dataset.baseTop) || 0,
            width: node.offsetWidth,
            height: node.offsetHeight,
            baseline: Number(node.dataset.flowBaseline) || 0,
            anchor: Number(node.dataset.flowAnchor) || 0,
            mode: node.dataset.flowMode || 'free'
        }));
        const plan = window.BuilderAutoFlow.calculate(model);
        const positions = new Map(plan.placements.map(entry => [entry.id, entry.top]));
        let maxBottom = 900;
        nodes.forEach(node => {
            const top = positions.get(node.dataset.builderId);
            if (top !== undefined) node.style.top = top + 'px';
            maxBottom = Math.max(maxBottom, node.offsetTop + node.offsetHeight + 40);
        });
        const next = Math.ceil(maxBottom) + 'px';
        if (canvas.style.minHeight !== next) canvas.style.minHeight = next;
    };

    // Same-page Portfolio links scroll to the gallery rather than to a
    // removable heading. Account for the sticky global header.
    const portfolio = canvas.querySelector('#portfolio');
    if (portfolio) {
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (!link || link.target === '_blank') return;
            const destination = new URL(link.href, window.location.href);
            const normalizedPath = path => path === '/' ? path : path.replace(/\/+$/, '') || '/';
            if (destination.origin !== window.location.origin
                || normalizedPath(destination.pathname) !== normalizedPath(window.location.pathname)
                || destination.hash !== '#portfolio') return;

            event.preventDefault();
            const headerHeight = document.querySelector('.site-header')?.getBoundingClientRect().height || 0;
            const top = Math.max(0, portfolio.getBoundingClientRect().top + window.scrollY - headerHeight - 12);
            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({top, behavior: reduceMotion ? 'auto' : 'smooth'});
            window.history.replaceState(null, '', destination.pathname + destination.search + destination.hash);
        });
    }

    const observer = new ResizeObserver(fit);
    observer.observe(canvas);
    canvas.querySelectorAll('.page-element').forEach((element) => observer.observe(element));
    window.addEventListener('load', fit);
    fit();
})();
</script>
