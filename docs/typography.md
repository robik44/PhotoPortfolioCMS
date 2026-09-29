# Wspólna typografia CMS

`App\Services\SiteFontLibrary::catalog()` jest wspólnym API katalogu: `choices`,
`families`, `fonts`, `defaults`. Definicje fontów systemowych są wyłącznie w
`HeaderFonts::SYSTEM`. Nie należy kopiować list fontów do nowych formularzy.
Builder dostaje ten sam katalog jako JSON do `SiteTypography.create()`.

Font dodaje się raz w **Bibliotece czcionek** (`/admin/fonts`). Zalecany jest WOFF2;
obecny uploader obsługuje też WOFF, TTF i OTF. Sprawdza rozszerzenie, MIME,
sygnaturę pliku oraz limit 5 MB. Pliki trafiają na dysk `public` do `fonts/`
(standardowo `storage/app/public/fonts`). Dostęp publiczny zapewnia istniejący
link `public/storage`. Rejestr w `site_settings.header_custom_fonts` zawiera
identyfikator, etykietę i ścieżkę. Historyczna nazwa klucza została zachowana,
ale biblioteka jest wspólna dla całej witryny. Restart nie usuwa rejestru ani
plików. Otwarty wcześniej formularz trzeba odświeżyć, aby zobaczyć nowy font.

## Nowe pole tekstowe

1. Dołącz `components.typography-fields`, przekazując `field` (np. `intro`),
   `label`, `typography` (tablica zapisanych ustawień) i opcjonalnie `siteFonts`.
   Komponent sam pobierze centralny katalog, jeśli nie został przekazany.
2. Waliduj przez `TypographySettings::rules(['intro'])`.
3. Zapisz zwalidowane ustawienia przez
   `TypographySettings::save('resource_123_typography', $data, ['intro'])`.
4. Odczytaj JSON przez `TypographySettings::read($settings, $key)`.
5. W publicznym elemencie użyj `TypographySettings::css($values, 'intro', $catalog)`.
   Dołącz `components.header-font-faces` z `fonts => $catalog['fonts']`, jeśli
   widok nie zawiera już `components.site-typography`.

Komponent i helper wspierają `*_font_family` i `*_font_size` (1–200 px).
Puste pola oznaczają brak lokalnego nadpisania. Nie ustawiają globalnych wartości
ani nie dopisują domyślnych fontów do istniejących treści. Pominięte pola przy
zapisie nie usuwają wcześniejszych ustawień.

## Zapis i pierwszeństwo

Bez nowych tabel i kolumn: galerie używają istniejącego JSON-u
`gallery_{id}_typography`, zdjęcia — `photo_{id}_typography` w `site_settings`.
ALT nie ma typografii. Builder zachowuje istniejący JSON w `page_builders.content`.

W publicznej galerii indywidualne ustawienia tytułu/opisu zdjęcia nadpisują
istniejącą typografię podpisów galerii. W standardowym elemencie Galeria Buildera
lokalna typografia opisu lightboxa ma pierwszeństwo przed ustawieniami zdjęcia.
Przy braku nadpisań pozostaje dotychczasowy CSS. Nie dotyczy to `thumbnail_gallery`.
Obsługa nawigacji i zdjęć lightboxa pozostaje bez zmian — przenoszone są tylko
font i rozmiar istniejących tekstów.

## Testy bez migracji

`vendor/bin/phpunit tests/Feature/CentralTypographyTest.php --do-not-cache-result`
używa tymczasowej kopii `database/database.sqlite` i testowego dysku uploadów.
Sprawdza też sumę kontrolną bazy źródłowej. Nie uruchamia migracji.

`node tests/Js/builder-typography.test.mjs` sprawdza selektory Buildera i
stosowanie/resetowanie typografii w lightboxie.
