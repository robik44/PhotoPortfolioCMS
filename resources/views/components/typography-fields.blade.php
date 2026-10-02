@php
    $fontCatalog = $siteFonts ?? app(\App\Services\SiteFontLibrary::class)->catalog();
    $breakpoints = ['desktop' => 'Desktop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'];
    $keyFor = static fn (string $property, string $breakpoint) => \App\Support\TypographySettings::key($field, $property, $breakpoint);
    $inputStyle = 'display:block;width:100%;min-height:42px;margin:7px 0 12px;padding:9px 10px;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#111827;font-size:14px;';
@endphp

<div class="shared-typography-fields" style="margin:14px 0 20px;padding:14px;border:1px solid #e5e7eb;border-radius:8px;background:#fafafa;">
    @foreach($breakpoints as $breakpoint => $breakpointLabel)
        @php($familyKey = $keyFor('font_family', $breakpoint))
        @php($sizeKey = $keyFor('font_size', $breakpoint))
        <details @if($breakpoint === 'desktop') open @endif style="margin:0 0 10px;">
            <summary style="cursor:pointer;font-weight:700;padding:6px 0;">{{ $label }} — {{ $breakpointLabel }}</summary>
            <div style="padding-top:8px;">
                <label for="{{ $familyKey }}">{{ $label }} — krój czcionki @if($breakpoint !== 'desktop')({{ $breakpointLabel }})@endif</label>
                <select name="{{ $familyKey }}" id="{{ $familyKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'Domyślny (bez zmiany)' : 'Dziedzicz' }}</option>
                    @foreach($fontCatalog['choices'] as $font)
                        <option value="{{ $font['value'] }}" style="font-family:{{ $font['css'] }};" @selected(old($familyKey, $typography[$familyKey] ?? '') === $font['value'])>{{ $font['label'] }}</option>
                    @endforeach
                </select>
                @error($familyKey)<p role="alert">{{ $message }}</p>@enderror

                @if($withSize ?? true)
                    <label for="{{ $sizeKey }}">{{ $label }} — rozmiar czcionki (px)@if($breakpoint !== 'desktop') — {{ $breakpointLabel }}@endif</label>
                    <input type="number" inputmode="decimal" step="0.1" min="1" max="200" name="{{ $sizeKey }}" id="{{ $sizeKey }}"
                           value="{{ old($sizeKey, $typography[$sizeKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? 'Domyślny (bez zmiany)' : 'Dziedzicz' }}" style="{{ $inputStyle }}">
                    @error($sizeKey)<p role="alert">{{ $message }}</p>@enderror
                @endif

                @php($weightKey = $keyFor('font_weight', $breakpoint))
                <label for="{{ $weightKey }}">Grubość</label>
                <select name="{{ $weightKey }}" id="{{ $weightKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'Domyślna' : 'Dziedzicz' }}</option>
                    @foreach([100,200,300,400,500,600,700,800,900] as $weight)
                        <option value="{{ $weight }}" @selected((string) old($weightKey, $typography[$weightKey] ?? '') === (string) $weight)>{{ $weight }}</option>
                    @endforeach
                </select>

                @php($styleKey = $keyFor('font_style', $breakpoint))
                <label for="{{ $styleKey }}">Styl</label>
                <select name="{{ $styleKey }}" id="{{ $styleKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'Domyślny' : 'Dziedzicz' }}</option>
                    <option value="normal" @selected(old($styleKey, $typography[$styleKey] ?? '') === 'normal')>Normal</option>
                    <option value="italic" @selected(old($styleKey, $typography[$styleKey] ?? '') === 'italic')>Italic</option>
                </select>

                @php($transformKey = $keyFor('text_transform', $breakpoint))
                <label for="{{ $transformKey }}">Wielkość liter</label>
                <select name="{{ $transformKey }}" id="{{ $transformKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'Bez zmiany' : 'Dziedzicz' }}</option>
                    @foreach(['none' => 'Bez zmian', 'uppercase' => 'UPPERCASE', 'lowercase' => 'lowercase', 'capitalize' => 'Capitalize'] as $value => $title)
                        <option value="{{ $value }}" @selected(old($transformKey, $typography[$transformKey] ?? '') === $value)>{{ $title }}</option>
                    @endforeach
                </select>

                @php($alignKey = $keyFor('text_align', $breakpoint))
                <label for="{{ $alignKey }}">Wyrównanie</label>
                <select name="{{ $alignKey }}" id="{{ $alignKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'Domyślne' : 'Dziedzicz' }}</option>
                    @foreach(['left' => 'Do lewej', 'center' => 'Do środka', 'right' => 'Do prawej', 'justify' => 'Justuj'] as $value => $title)
                        <option value="{{ $value }}" @selected(old($alignKey, $typography[$alignKey] ?? '') === $value)>{{ $title }}</option>
                    @endforeach
                </select>

                @php($lineKey = $keyFor('line_height', $breakpoint))
                <label for="{{ $lineKey }}">Interlinia</label>
                <input type="number" inputmode="decimal" step="0.05" min="0.1" max="10" name="{{ $lineKey }}" id="{{ $lineKey }}"
                       value="{{ old($lineKey, $typography[$lineKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? 'Domyślna' : 'Dziedzicz' }}" style="{{ $inputStyle }}">

                @php($lineUnitKey = $keyFor('line_height_unit', $breakpoint))
                <label for="{{ $lineUnitKey }}">Jednostka interlinii</label>
                <select name="{{ $lineUnitKey }}" id="{{ $lineUnitKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'Bez jednostki' : 'Dziedzicz' }}</option>
                    <option value="unitless" @selected(old($lineUnitKey, $typography[$lineUnitKey] ?? '') === 'unitless')>Bez jednostki</option>
                    <option value="px" @selected(old($lineUnitKey, $typography[$lineUnitKey] ?? '') === 'px')>px</option>
                </select>

                @php($letterKey = $keyFor('letter_spacing', $breakpoint))
                <label for="{{ $letterKey }}">Odstęp między literami / tracking</label>
                <input type="number" inputmode="decimal" step="0.01" min="-10" max="20" name="{{ $letterKey }}" id="{{ $letterKey }}"
                       value="{{ old($letterKey, $typography[$letterKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? '0' : 'Dziedzicz' }}" style="{{ $inputStyle }}">

                @php($letterUnitKey = $keyFor('letter_spacing_unit', $breakpoint))
                <label for="{{ $letterUnitKey }}">Jednostka trackingu</label>
                <select name="{{ $letterUnitKey }}" id="{{ $letterUnitKey }}" style="{{ $inputStyle }}">
                    <option value="">{{ $breakpoint === 'desktop' ? 'px' : 'Dziedzicz' }}</option>
                    <option value="px" @selected(old($letterUnitKey, $typography[$letterUnitKey] ?? '') === 'px')>px</option>
                    <option value="em" @selected(old($letterUnitKey, $typography[$letterUnitKey] ?? '') === 'em')>em</option>
                </select>

                @php($wordKey = $keyFor('word_spacing', $breakpoint))
                <label for="{{ $wordKey }}">Odstęp między słowami (px)</label>
                <input type="number" inputmode="decimal" step="0.1" min="-50" max="100" name="{{ $wordKey }}" id="{{ $wordKey }}"
                       value="{{ old($wordKey, $typography[$wordKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? '0' : 'Dziedzicz' }}" style="{{ $inputStyle }}">

                <details style="margin:10px 0 0;">
                    <summary style="cursor:pointer;font-weight:600;">Zaawansowane</summary>
                    <div style="padding-top:10px;">
                        @php($colorKey = $keyFor('color', $breakpoint))
                        <label for="{{ $colorKey }}">Kolor tekstu</label>
                        <input type="color" name="{{ $colorKey }}" id="{{ $colorKey }}" value="{{ old($colorKey, $typography[$colorKey] ?? '#222222') }}" style="{{ $inputStyle }}">

                        @php($alphaKey = $keyFor('color_alpha', $breakpoint))
                        <label for="{{ $alphaKey }}">Alpha koloru (%)</label>
                        <input type="number" inputmode="decimal" step="1" min="0" max="100" name="{{ $alphaKey }}" id="{{ $alphaKey }}"
                               value="{{ old($alphaKey, $typography[$alphaKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? '100' : 'Dziedzicz' }}" style="{{ $inputStyle }}">

                        @php($opacityKey = $keyFor('opacity', $breakpoint))
                        <label for="{{ $opacityKey }}">Opacity tekstu (%)</label>
                        <input type="number" inputmode="decimal" step="1" min="0" max="100" name="{{ $opacityKey }}" id="{{ $opacityKey }}"
                               value="{{ old($opacityKey, $typography[$opacityKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? '100' : 'Dziedzicz' }}" style="{{ $inputStyle }}">

                        @foreach([
                            'paragraph_spacing' => ['Odstęp między akapitami (px)', 1, 0, 500],
                            'margin_top' => ['Margines nad (px)', 1, -500, 500],
                            'margin_bottom' => ['Margines pod (px)', 1, -500, 500],
                            'max_width' => ['Maksymalna szerokość (px)', 1, 0, 3000],
                            'max_line_length' => ['Maks. długość wiersza (ch)', 1, 10, 120],
                            'offset_x' => ['Offset X (px)', 1, -1000, 1000],
                            'offset_y' => ['Offset Y (px)', 1, -1000, 1000],
                        ] as $property => [$propertyLabel, $step, $min, $max])
                            @php($propertyKey = $keyFor($property, $breakpoint))
                            <label for="{{ $propertyKey }}">{{ $propertyLabel }}</label>
                            <input type="number" inputmode="decimal" step="{{ $step }}" min="{{ $min }}" max="{{ $max }}" name="{{ $propertyKey }}" id="{{ $propertyKey }}"
                                   value="{{ old($propertyKey, $typography[$propertyKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? 'Domyślne' : 'Dziedzicz' }}" style="{{ $inputStyle }}">
                        @endforeach

                        @php($widthKey = $keyFor('text_width', $breakpoint))
                        <label for="{{ $widthKey }}">Szerokość tekstu</label>
                        <input type="number" inputmode="decimal" step="0.1" min="0" max="2000" name="{{ $widthKey }}" id="{{ $widthKey }}"
                               value="{{ old($widthKey, $typography[$widthKey] ?? '') }}" placeholder="{{ $breakpoint === 'desktop' ? 'Auto' : 'Dziedzicz' }}" style="{{ $inputStyle }}">

                        @php($widthUnitKey = $keyFor('text_width_unit', $breakpoint))
                        <label for="{{ $widthUnitKey }}">Jednostka szerokości</label>
                        <select name="{{ $widthUnitKey }}" id="{{ $widthUnitKey }}" style="{{ $inputStyle }}">
                            <option value="">{{ $breakpoint === 'desktop' ? '%' : 'Dziedzicz' }}</option>
                            <option value="%" @selected(old($widthUnitKey, $typography[$widthUnitKey] ?? '') === '%')>%</option>
                            <option value="px" @selected(old($widthUnitKey, $typography[$widthUnitKey] ?? '') === 'px')>px</option>
                        </select>
                    </div>
                </details>
            </div>
        </details>
    @endforeach
</div>
