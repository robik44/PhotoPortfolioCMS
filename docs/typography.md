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


## Responsive typography system (2026-10)

PageBuilder text-like blocks (`heading`, `text`, `button`, `section`, `gallery`) use one shared UI from `public/js/site-typography.js` and one public renderer in `App\\Support\\BuilderTypography`. Do not add parallel font-size/weight/spacing controls in individual builder blocks.

Legacy `element.style` remains the base for compatibility. New overrides are opt-in and stored under:

```json
{
  "typography": {
    "desktop": { "font_size": 52.4, "line_height": 0.95, "letter_spacing": -0.02, "letter_spacing_unit": "em" },
    "tablet": { "font_size": 43.2 },
    "mobile": { "font_size": 31.6, "line_height": 1.02 }
  }
}
```

Tablet inherits Desktop; Mobile inherits Tablet then Desktop. Empty override fields are not written. Resetting typography removes the override and reveals the legacy/default value again. No database migration is required.

Shared controls support decimal font size (0.1 px UI step), line-height (0.05), letter spacing (0.01 px/em), word spacing (0.1 px), font weight/style/transform/alignment, color + alpha, opacity, margins, paragraph spacing, text width/max-width/max line length, X/Y offsets, presets and optional fluid `clamp()`.

Gallery and Photo typography continue to use the existing `site_settings` JSON keys, while `TypographySettings` supports the same Desktop/Tablet/Mobile properties. Existing keys such as `title_font_family` and `title_font_size` remain valid; responsive values use names such as `title_tablet_font_size` and `title_mobile_letter_spacing`.

Breakpoints are shared with the current public site: Tablet max-width 900 px, Mobile max-width 520 px.

For future text components, extend the shared helpers rather than copying controls into a new form or block.
