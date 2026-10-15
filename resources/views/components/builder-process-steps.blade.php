@php
    $process = is_array($element ?? null) ? $element : [];
    $design = is_array($process['process_design'] ?? null) ? $process['process_design'] : [];
    $defaultSteps = [
        ['icon' => 'message', 'title' => '1. Rozmowa i pomysł', 'description' => 'Poznaję Twoje potrzeby, cel i charakter marki.'],
        ['icon' => 'bowl', 'title' => '2. Przygotowanie i stylizacja', 'description' => 'Tworzę koncepcję, przygotowuję produkty i aranżuję plan zdjęciowy.'],
        ['icon' => 'image', 'title' => '3. Gotowe kadry', 'description' => 'Otrzymujesz dopracowane zdjęcia gotowe do publikacji i użycia w materiałach.'],
    ];
    $steps = $process['process_steps'] ?? $defaultSteps;
    $steps = is_array($steps) && count($steps) === 3 ? array_values($steps) : $defaultSteps;
    $icons = [
        'message' => '<path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5c-1.1 0-2.2-.25-3.15-.7L4 20l1.7-5.35A7.5 7.5 0 1 1 20 11.5Z"/>',
        'bowl' => '<path d="M3 13h18c-.5 5-3.5 8-9 8s-8.5-3-9-8Z"/><path d="M6 9c1 0 2-1 2-3m4 3c1 0 2-1 2-3m4 3c1 0 2-1 2-3"/><path d="m9 13 8-10 2 1-6 9"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="1.5"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 18 5-5 3 3 3-4 5 5"/>',
        'camera' => '<path d="M4 7h3l2-2h6l2 2h3v12H4Z"/><circle cx="12" cy="13" r="4"/>',
        'lightbulb' => '<path d="M9 18h6M10 21h4M8 14a7 7 0 1 1 8 0c-.7.7-1 1.5-1 2H9c0-.5-.3-1.3-1-2Z"/>',
        'check' => '<circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/>',
    ];
    $color = static fn ($key, $default) => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($design[$key] ?? '')) ? $design[$key] : $default;
    $num = static fn ($key, $default, $min, $max) => max($min, min($max, is_numeric($design[$key] ?? null) ? (float) $design[$key] : $default));
    $catalog = $fontCatalog ?? app(\App\Services\SiteFontLibrary::class)->catalog();
    $headFont = \App\Services\SiteFontLibrary::css($design['heading_font'] ?? 'Georgia', $catalog, 'Georgia');
    $bodyFont = \App\Services\SiteFontLibrary::css($design['body_font'] ?? 'Arial', $catalog, 'Arial');
    $processTextParts = \App\Support\ProcessTypography::parts($process);
@endphp
<section class="ff-process {{ ($design['show_arrows'] ?? true) === false ? 'ff-process-hide-arrows' : '' }}"
    style="
        --ff-process-background:{{ $color('background', '#ffffff') }};
        --ff-process-text:{{ $color('text', '#24241f') }};
        --ff-process-muted:{{ $color('muted', '#64675e') }};
        --ff-process-accent:{{ $color('accent', '#a7b593') }};
        --ff-process-icon-background:{{ $color('icon_background', '#f2f6eb') }};
        --ff-process-icon-color:{{ $color('icon_color', '#858d82') }};
        --ff-process-pad-x:{{ $num('pad_x', 54, 0, 160) }}px;
        --ff-process-pad-y:{{ $num('pad_y', 70, 0, 160) }}px;
        --ff-process-head-gap:{{ $num('head_gap', 56, 0, 140) }}px;
        --ff-process-section-gap:{{ $num('section_gap', 46, 0, 150) }}px;
        --ff-process-steps-gap:{{ $num('steps_gap', 24, 0, 120) }}px;
        --ff-process-icon-size:{{ $num('icon_size', 82, 36, 150) }}px;
        --ff-process-title-size:{{ $num('title_size', 58, 20, 100) }}px;
        --ff-process-title-weight:{{ $num('title_weight', 400, 100, 900) }};
        --ff-process-lead-size:{{ $num('lead_size', 18, 10, 36) }}px;
        --ff-process-step-title-size:{{ $num('step_title_size', 23, 12, 40) }}px;
        --ff-process-step-description-size:{{ $num('step_description_size', 16, 10, 30) }}px;
        --ff-process-heading-font:{{ $headFont }};
        --ff-process-body-font:{{ $bodyFont }};
    ">
    <div class="ff-process-intro">
        <div>
            <p class="ff-process-eyebrow" data-process-part="kicker" style="{{ \App\Support\BuilderTypography::inlineCss($processTextParts['kicker'], $catalog) }}">{{ $process['process_kicker'] ?? 'JAK PRACUJĘ' }}</p>
            <h2 class="ff-process-title" data-process-part="title" style="{{ \App\Support\BuilderTypography::inlineCss($processTextParts['title'], $catalog) }}">{{ $process['process_title'] ?? 'Od pomysłu do gotowych kadrów' }}</h2>
        </div>
        <p class="ff-process-lead" data-process-part="intro" style="{{ \App\Support\BuilderTypography::inlineCss($processTextParts['intro'], $catalog) }}">{{ $process['process_intro'] ?? 'Dbam o każdy detal — od koncepcji, przez stylizację, po finalne zdjęcia. Pracuję elastycznie, dopasowując się do potrzeb Twojej marki.' }}</p>
    </div>
    <div class="ff-process-steps">
        @foreach($steps as $i => $step)
            @php
                $step = is_array($step) ? $step : $defaultSteps[$i];
                $icon = in_array($step['icon'] ?? '', array_keys($icons), true) ? $step['icon'] : $defaultSteps[$i]['icon'];
            @endphp
            <article class="ff-process-step">
                <div class="ff-process-bubble">
                    <svg class="ff-process-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none"
                         stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
                        {!! $icons[$icon] !!}
                    </svg>
                </div>
                <div>
                    <h3 class="ff-process-step-title" data-process-part="step{{ $i+1 }}_title" style="{{ \App\Support\BuilderTypography::inlineCss($processTextParts['step'.($i+1).'_title'], $catalog) }}">{{ $step['title'] ?? '' }}</h3>
                    <p class="ff-process-step-description" data-process-part="step{{ $i+1 }}_description" style="{{ \App\Support\BuilderTypography::inlineCss($processTextParts['step'.($i+1).'_description'], $catalog) }}">{{ $step['description'] ?? '' }}</p>
                </div>
            </article>
        @endforeach
    </div>
</section>
