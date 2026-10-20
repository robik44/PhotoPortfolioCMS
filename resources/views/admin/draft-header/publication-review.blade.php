<x-app-layout>
    <x-slot name="header">Przegląd publikacji — nagłówki FoodFoto</x-slot>

    <div class="cms-card" style="max-width:1100px;margin:24px auto;padding:30px">
        <h1 style="font:400 40px Georgia,serif;margin:0 0 12px">Przygotowanie publikacji</h1>
        <p style="font:16px/1.6 Arial,sans-serif;max-width:820px">
            To jest <strong>lista kontrolna wersji testowej</strong>. Ten panel nie publikuje nagłówków
            ani nie zmienia aktualnej strony FoodFoto. Publikację i wycofanie zmian podłączymy
            dopiero po zatwierdzeniu wyglądu wszystkich stron.
        </p>
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
            Do uruchomienia produkcyjnego pozostają: ostateczna kontrola wersji komputerowej
            i mobilnej, podłączenie wspólnego nagłówka w szablonach publicznych,
            mechanizm świadomej publikacji oraz bezpieczne wycofanie zmian.
        </p>
        <a href="{{ route('draft-header.preview') }}" style="display:inline-block;margin-top:10px">← Centralny builder nagłówka</a>
    </div>
</x-app-layout>
