<x-app-layout>
    <x-slot name="header">Biblioteka czcionek</x-slot>
    <div class="cms-card" style="padding:24px;max-width:960px;">
        <h1 class="cms-dashboard-title">Biblioteka czcionek</h1>
        <p>Jedna biblioteka dla nagłówka, stron, galerii, zdjęć i obu builderów. Czcionkę wystarczy wgrać raz.</p>
        @if ($errors->any())
            <div class="cms-alert cms-alert-error" role="alert">
                @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        @include('components.header-font-faces', ['fonts' => $siteFonts['fonts']])
        <form method="POST" action="{{ route('fonts.store') }}" enctype="multipart/form-data" style="margin:24px 0;">
            @csrf
            <label for="font_file">Wgraj własną czcionkę — zalecany WOFF2; również WOFF, TTF lub OTF, maks. 5 MB</label>
            <input id="font_file" type="file" name="font_file" accept=".woff2,.woff,.ttf,.otf" required>
            <button class="cms-button cms-button-primary" type="submit">Dodaj czcionkę</button>
        </form>
        <h2>Czcionki systemowe i własne</h2>
        <ul style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin:20px 0;">
            @foreach ($siteFonts['choices'] as $font)
                <li style="font-family:{{ $font['css'] }};padding:12px;border:1px solid #ddd;">{{ $font['label'] }}</li>
            @endforeach
        </ul>
        <form method="POST" action="{{ route('fonts.update') }}">
            @csrf
            @method('PUT')
            <h2>Domyślne czcionki treści witryny</h2>
            <p>Stosowane w statycznych widokach i jako domyślne czcionki nowych bloków. Indywidualne ustawienia nagłówka, galerii i zapisanych bloków mają pierwszeństwo.</p>
            @foreach (['site_body_font_family' => 'Tekst', 'site_heading_font_family' => 'Nagłówki'] as $key => $label)
                <label for="{{ $key }}">{{ $label }} — Rodzaj czcionki</label>
                <select id="{{ $key }}" name="{{ $key }}" style="display:block;margin:8px 0 20px;">
                    @foreach ($siteFonts['choices'] as $font)
                        <option value="{{ $font['value'] }}" style="font-family:{{ $font['css'] }};" @selected(old($key, $siteFonts['defaults'][$key]) === $font['value'])>{{ $font['label'] }}</option>
                    @endforeach
                </select>
            @endforeach
            <button class="cms-button cms-button-primary" type="submit">Zapisz domyślne czcionki</button>
        </form>
    </div>
</x-app-layout>
