<x-app-layout>
    <x-slot name="header">Przegląd publikacji — nagłówki FoodFoto</x-slot>

    <div class="cms-card" style="max-width:1100px;margin:24px auto;padding:30px">
        <h1 style="font:400 40px Georgia,serif;margin:0 0 12px">Przygotowanie publikacji</h1>
        <p style="font:16px/1.6 Arial,sans-serif;max-width:820px">
            To jest centrum publikacji nagłówków FoodFoto. Samo otwarcie strony niczego nie zmienia.
            <strong>Publikacja wymaga wpisania potwierdzenia i naciśnięcia przycisku.</strong>
            Możesz później przywrócić poprzedni wygląd bez usuwania roboczych ustawień.
        </p>
        @if(session('success'))
            <p role="status" style="padding:12px 16px;border:1px solid #9bab9a;border-radius:7px">
                {{ session('success') }}
            </p>
        @endif
        @if($errors->any())
            <div role="alert" style="border:1px solid #be7777;padding:12px 16px;border-radius:7px">
                <strong>Operacja nie została wykonana.</strong>
                @foreach($errors->all() as $message)<p>{{ $message }}</p>@endforeach
            </div>
        @endif
        <section style="border:2px solid #bdcbbb;border-radius:12px;padding:22px;margin:24px 0;background:#fbfcfa">
            <h2 style="font:400 28px Georgia,serif;margin:0 0 10px">Status publikacji</h2>
            <p style="font:700 16px Arial,sans-serif">
                {{ $header_live ? 'NOWY NAGŁÓWEK — AKTYWNY PUBLICZNIE' : 'STARY NAGŁÓWEK — AKTYWNY PUBLICZNIE' }}
            </p>
            @if($new_home_system_live)
                <p style="font:14px/1.5 Arial;color:#933">
                    Wykryto też inny opublikowany wariant nowej strony głównej. Nie publikuj nowych
                    nagłówków, dopóki nie rozstrzygniemy, który układ ma być aktywny.
                </p>
            @endif
            @if($header_live)
                <p>Wyłączenie przywróci poprzedni nagłówek i stary baner strony głównej.</p>
                <form method="post" action="{{ route('draft-header.rollback') }}" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                    @csrf
                    <label style="font:13px Arial">Wpisz <strong>COFNIJ</strong>:
                        <input name="confirmation" type="text" placeholder="COFNIJ" required autocomplete="off"
                               style="padding:10px;border:1px solid #bbb;border-radius:6px">
                    </label>
                    <button type="submit" style="padding:12px 18px;background:#303e34;color:white;border:0;border-radius:7px;cursor:pointer">
                        Przywróć poprzedni nagłówek
                    </button>
                </form>
            @else
                <p>
                    Uaktywnienie zastąpi publicznie nagłówki, a na stronie głównej także dawny baner.
                    Nie zmieni zawartości istniejącego buildera ani nie usunie zdjęć.
                </p>
                <form method="post" action="{{ route('draft-header.publish') }}" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
                    @csrf
                    <label style="font:13px Arial">Wpisz <strong>PUBLIKUJ</strong>:
                        <input name="confirmation" type="text" placeholder="PUBLIKUJ" required autocomplete="off"
                               style="padding:10px;border:1px solid #bbb;border-radius:6px">
                    </label>
                    <button type="submit" style="padding:12px 18px;background:#245338;color:white;border:0;border-radius:7px;cursor:pointer"
                            {{ $new_home_system_live ? 'disabled' : '' }}>
                        Opublikuj nowy nagłówek
                    </button>
                </form>
            @endif
        </section>
        <div style="padding:18px 20px;background:#eff3ed;border:1px solid #ccd8cb;border-radius:9px;margin:22px 0">
            <strong>Strona główna</strong>
            <p style="margin:8px 0;font:14px Arial,sans-serif">
                Wariant: {{ $home_variant === 'full' ? 'Pełny — ze zdjęciem' : 'Podstawowy — bez zdjęcia' }}.
                Robocza kopia banera: {{ $home_draft_ready ? 'jest dostępna' : 'nie została jeszcze przygotowana' }}.
            </p>
            <a href="{{ route('home-builder.edit') }}">Otwórz builder strony głównej →</a>
        </div>

        <h2 style="font:400 28px Georgia,serif;margin:30px 0 14px">Podstrony</h2>
        <div style="overflow:auto">
            <table style="border-collapse:collapse;width:100%;font:14px/1.5 Arial,sans-serif">
                <thead><tr style="background:#f3f3f0;text-align:left">
                    <th style="padding:11px;border-bottom:1px solid #ddd">Strona</th>
                    <th style="padding:11px;border-bottom:1px solid #ddd">Nagłówek testowy</th>
                    <th style="padding:11px;border-bottom:1px solid #ddd">Osobny baner</th>
                    <th style="padding:11px;border-bottom:1px solid #ddd">Builder</th>
                </tr></thead>
                <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td style="padding:11px;border-bottom:1px solid #eee">
                            {{ $page['title'] }}{{ $page['published'] ? '' : ' (nieopublikowana podstrona)' }}
                        </td>
                        <td style="padding:11px;border-bottom:1px solid #eee">
                            {{ $page['variant'] === 'full' ? 'Pełny — z banerem' : 'Tylko logo i menu' }}
                        </td>
                        <td style="padding:11px;border-bottom:1px solid #eee">
                            {{ $page['has_own_banner'] ? 'Zapisano indywidualny' : 'Wspólne ustawienia lub brak' }}
                        </td>
                        <td style="padding:11px;border-bottom:1px solid #eee">
                            <a href="{{ route('pages.builder', ['page' => $page['id']]) }}">Sprawdź →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="padding:16px">Brak podstron w bazie.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p style="font:14px/1.7 Arial,sans-serif;color:#45574a;margin-top:24px">
            Przed publikacją sprawdź stronę główną, podstrony, galerię i menu na komputerze
            oraz telefonie. Po publikacji sprawdź działanie linków, układ i SEO.
            W razie problemów natychmiast użyj przycisku powrotu do starej wersji.
        </p>
        <a href="{{ route('draft-header.preview') }}" style="display:inline-block;margin-top:10px">← Centralny builder nagłówka</a>
    </div>
</x-app-layout>
