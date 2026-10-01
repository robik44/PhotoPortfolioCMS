<x-app-layout>
    <x-slot name="header">Nagłówek</x-slot>

    <div class="cms-card header-settings-form" style="max-width:900px;padding:24px;">
        <h1 class="cms-dashboard-title">Globalny nagłówek</h1>
        <p>Te ustawienia dotyczą strony głównej i wszystkich podstron. Pozycje menu zmienisz w sekcji
            <a href="{{ route('menu.index') }}" style="text-decoration:underline;">Menu strony</a>.
        </p>

        @if ($errors->any())
            <div class="cms-alert cms-alert-error" role="alert">
                <p>Nie zapisano zmian. Sprawdź poprawność pól:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @include('components.header-font-faces', ['fonts' => $customFonts])

        <section class="header-preview-section" aria-labelledby="header-preview-title">
            <h2 id="header-preview-title">PODGLĄD NAGŁÓWKA</h2>
            <p>Zmiany są widoczne od razu. Zostaną opublikowane po kliknięciu „Zapisz nagłówek”.</p>
            <div id="header-live-preview" class="header-live-preview">
                <div class="header-preview-brand">
                    <span id="header-preview-logo"></span>
                    <span id="header-preview-subtitle"></span>
                </div>
                <div class="header-preview-menu" aria-label="Przykładowe położenie menu">
                    <span>Start</span><span>Portfolio ▾</span><span>O mnie</span><span>Kontakt</span>
                </div>
            </div>
            <p id="header-font-status" role="status" aria-live="polite"></p>
            <noscript>Włącz JavaScript, aby korzystać z podglądu na żywo. Zapis formularza działa bez JavaScript.</noscript>
        </section>

        <form id="header-settings-form" method="POST" enctype="multipart/form-data"
              data-font-families="{{ json_encode($fontFamilies) }}" action="{{ route('header-settings.update') }}">
            @csrf
            @method('PUT')

            @foreach (['logo' => 'Nazwa / logo tekstowe', 'subtitle' => 'Podtytuł'] as $part => $label)
                @php
                    $textKey = $part === 'logo' ? 'logo' : 'logo_subtitle';
                    $prefix = 'header_' . $part . '_';
                @endphp
                <fieldset style="margin:24px 0;padding:20px;border:1px solid #ddd;border-radius:8px;">
                    <legend>{{ $label }}</legend>
                    <label for="{{ $textKey }}">{{ $label }}</label>
                    <input id="{{ $textKey }}" name="{{ $textKey }}" type="text" maxlength="255"
                           value="{{ old($textKey, $settings[$textKey]) }}" @required($part === 'logo')>

                    <div class="header-settings-grid">
                        <div>
                            <label for="{{ $prefix }}font_family">Rodzaj czcionki</label>
                            <select id="{{ $prefix }}font_family" name="{{ $prefix }}font_family" required>
                                @foreach ($fontFamilies as $family => $cssFamily)
                                    <option value="{{ $family }}" style="font-family:{{ $cssFamily }};"
                                            @selected(old($prefix . 'font_family', $settings[$prefix . 'font_family']) === $family)>
                                        {{ isset($customFonts[$family]) ? $customFonts[$family]['label'] . ' (własna)' : $family }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="{{ $prefix }}font_size">Rozmiar czcionki (px)</label>
                            <input id="{{ $prefix }}font_size" name="{{ $prefix }}font_size" type="number"
                                   min="8" max="{{ $part === 'logo' ? 96 : 48 }}" step="1" required
                                   value="{{ old($prefix . 'font_size', $settings[$prefix . 'font_size']) }}">
                        </div>
                        <div>
                            <label for="{{ $prefix }}font_weight">Grubość czcionki</label>
                            <select id="{{ $prefix }}font_weight" name="{{ $prefix }}font_weight" required>
                                @foreach ([100 => '100 — bardzo cienka', 200 => '200 — cienka', 300 => '300 — lekka', 400 => '400 — normalna', 500 => '500 — średnia', 600 => '600 — półgruba', 700 => '700 — pogrubiona', 800 => '800 — bardzo gruba', 900 => '900 — najgrubsza'] as $weight => $weightLabel)
                                    <option value="{{ $weight }}" @selected((string) old($prefix . 'font_weight', $settings[$prefix . 'font_weight']) === (string) $weight)>{{ $weightLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="{{ $prefix }}color">Kolor</label>
                            <input id="{{ $prefix }}color" name="{{ $prefix }}color" type="color" required
                                   value="{{ old($prefix . 'color', $settings[$prefix . 'color']) }}">
                        </div>
                        <div>
                            <label for="{{ $prefix }}letter_spacing">Odstęp między literami (em)</label>
                            <input id="{{ $prefix }}letter_spacing" name="{{ $prefix }}letter_spacing" type="number"
                                   min="0" max="1" step="0.01" required
                                   value="{{ old($prefix . 'letter_spacing', $settings[$prefix . 'letter_spacing']) }}">
                        </div>
                    </div>
                </fieldset>
            @endforeach

            <fieldset style="margin:24px 0;padding:20px;border:1px solid #ddd;border-radius:8px;">
                <legend>Własne czcionki</legend>
                <p>Wybierz WOFF2, WOFF, TTF lub OTF (maks. 5 MB). Plik zostanie przesłany dopiero po zapisaniu nagłówka.
                    Zapisane czcionki są dostępne na obu listach „Rodzaj czcionki”.</p>
                <label for="font_file">Plik czcionki z komputera</label>
                <input id="font_file" name="font_file" type="file" accept=".woff2,.woff,.ttf,.otf">
                <label for="font_target">Zastosuj nową czcionkę do</label>
                <select id="font_target" name="font_target">
                    <option value="logo" @selected(old('font_target', 'logo') === 'logo')>Nazwa / logo tekstowe</option>
                    <option value="subtitle" @selected(old('font_target') === 'subtitle')>Podtytuł</option>
                    <option value="both" @selected(old('font_target') === 'both')>Nazwa i podtytuł</option>
                </select>
                <button id="header-clear-font" type="button" class="cms-button" style="margin-top:12px;" hidden>Anuluj wybór pliku</button>
                @error('font_file')
                    <p role="alert">{{ $message }} Wybierz plik ponownie.</p>
                @enderror
            </fieldset>

            <div style="margin:24px 0;">
                <label for="header_logo_subtitle_gap">Odstęp między logo a podtytułem (px)</label>
                <input id="header_logo_subtitle_gap" name="header_logo_subtitle_gap" type="number"
                       min="0" max="80" step="1" required
                       value="{{ old('header_logo_subtitle_gap', $settings['header_logo_subtitle_gap']) }}">
                <p>Zmienia pionowy odstęp pomiędzy nazwą/logo tekstowym a podtytułem na wszystkich publicznych stronach.</p>
            </div>

            <label for="header_layout">Układ nagłówka</label>
            <select id="header_layout" name="header_layout" required>
                <option value="left" @selected(old('header_layout', $settings['header_layout']) === 'left')>Logo po lewej / menu po prawej</option>
                <option value="center" @selected(old('header_layout', $settings['header_layout']) === 'center')>Logo wyśrodkowane / menu poniżej</option>
                <option value="right" @selected(old('header_layout', $settings['header_layout']) === 'right')>Menu po lewej / logo po prawej</option>
            </select>
            <p>Na małych ekranach elementy mogą układać się jeden pod drugim.</p>

            <div class="header-settings-grid">
                @foreach (['header_padding_top' => 'Odstęp nad logo i menu (px)', 'header_padding_bottom' => 'Odstęp pod logo i menu (px)'] as $key => $label)
                    <div>
                        <label for="{{ $key }}">{{ $label }}</label>
                        <input id="{{ $key }}" name="{{ $key }}" type="number" min="0" max="160" step="1" required
                               value="{{ old($key, $settings[$key]) }}" aria-describedby="header-padding-help">
                        @error($key)
                            <p role="alert">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>
            <p id="header-padding-help">Odstępy u góry i u dołu są niezależne i dotyczą wszystkich publicznych stron.
                Rozmiary logo i tekstu pozostają bez zmian.</p>

            <button type="submit" class="cms-button cms-button-primary" style="margin-top:20px;">Zapisz nagłówek</button>
        </form>
    </div>

    <style>
        .header-settings-form label { display: block; margin: 14px 0 6px; font-weight: 600; }
        .header-settings-form input, .header-settings-form select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }
        .header-settings-form input[type="color"] { height: 44px; padding: 3px; }
        .header-settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
        .header-preview-section { margin: 24px 0; padding: 16px; border: 1px solid #ddd; border-radius: 8px; background: #fafafa; }
        .header-preview-section h2 { font-weight: 700; }
        .header-live-preview { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px; padding: 24px; margin-top: 16px; background: #fff; border: 1px solid #eee; }
        .header-preview-brand { max-width: 100%; overflow-wrap: anywhere; }
        .header-preview-brand span { display: block; }
        .header-preview-brand span + span { margin-top: var(--header-logo-subtitle-gap, 4px); }
        .header-preview-menu { display: flex; flex-wrap: wrap; gap: 16px; font: 13px Arial, sans-serif; }
        .header-live-preview[data-layout="center"] { flex-direction: column; }
        .header-live-preview[data-layout="center"] .header-preview-brand { text-align: center; }
        .header-live-preview[data-layout="center"] .header-preview-menu { justify-content: center; }
        .header-live-preview[data-layout="right"] .header-preview-menu { order: -1; }
        .header-live-preview[data-layout="right"] .header-preview-brand { margin-left: auto; text-align: right; }
    </style>
    <script src="{{ asset('js/header-settings.js') }}?v={{ filemtime(public_path('js/header-settings.js')) }}" defer></script>
</x-app-layout>
