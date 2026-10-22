#!/usr/bin/env bash
set -euo pipefail

cd /home/srv51861/domains/foodfoto.pl/laravel
VIEW='resources/views/admin/pages/builder.blade.php'
JS='public/js/process-decorative-photo-library.js'
CSS='public/css/process-decorative-photo-library.css'
BASE='https://raw.githubusercontent.com/robik44/PhotoPortfolioCMS/cf867bf235898a0a351053cf03eb6109526f68c1'

if [[ ! -f "$VIEW" || ! -f public/js/photo-picker.js ]] || ! grep -Fq 'components.photo-library-dialog' "$VIEW"; then
  echo 'PRZERWANO: brakuje widoku buildera lub wspolnej biblioteki zdjec.' >&2
  exit 1
fi
if ! grep -Rq 'Własny obrazek dekoracyjny pod tekstami' public/js resources/views 2>/dev/null; then
  echo 'UWAGA: nie znaleziono etykiety dekoracji; na tej wersji serwera poprawka moze sie nie aktywowac.' >&2
fi

BACKUP="storage/app/process-photo-library-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP" public/js public/css
cp -p "$VIEW" "$BACKUP/builder.blade.php"
[[ ! -f "$JS" ]] || cp -p "$JS" "$BACKUP/process-decorative-photo-library.js"
[[ ! -f "$CSS" ]] || cp -p "$CSS" "$BACKUP/process-decorative-photo-library.css"
echo "Kopia bezpieczenstwa: $BACKUP"

curl -fsSL --retry 2 --max-time 30 "$BASE/$JS" -o "$JS.tmp"
curl -fsSL --retry 2 --max-time 30 "$BASE/$CSS" -o "$CSS.tmp"
test -s "$JS.tmp"
test -s "$CSS.tmp"
if command -v node >/dev/null 2>&1; then
  node --check < "$JS.tmp"
fi
mv -f "$JS.tmp" "$JS"
mv -f "$CSS.tmp" "$CSS"
chmod 644 "$JS" "$CSS"

php <<'PHP'
<?php
$path = 'resources/views/admin/pages/builder.blade.php';
$source = file_get_contents($path);
if ($source === false || !str_contains($source, '<x-app-layout>')) {
    fwrite(STDERR, "PRZERWANO: brak punktu wstawienia w builderze.\n");
    exit(1);
}
$inserts = [];
if (!str_contains($source, "asset('css/process-decorative-photo-library.css')")) {
    $inserts[] = <<<'BLADE'
    <link rel="stylesheet" href="{{ asset('css/process-decorative-photo-library.css') }}?v={{ @filemtime(public_path('css/process-decorative-photo-library.css')) ?: 1 }}">
BLADE;
}
if (!str_contains($source, "asset('js/process-decorative-photo-library.js')")) {
    $inserts[] = <<<'BLADE'
    <script src="{{ asset('js/process-decorative-photo-library.js') }}?v={{ @filemtime(public_path('js/process-decorative-photo-library.js')) ?: 1 }}" defer></script>
BLADE;
}
if ($inserts) {
    $source = preg_replace('/<x-app-layout>/', '<x-app-layout>' . "\n" . implode("\n", $inserts), $source, 1);
    if (file_put_contents($path, $source) === false) {
        fwrite(STDERR, "PRZERWANO: nie udalo sie zapisac widoku buildera.\n");
        exit(1);
    }
}
echo "Widok buildera zaktualizowany.\n";
PHP

php artisan view:clear
echo '--- Weryfikacja ---'
ls -lh "$JS" "$CSS"
grep -n 'process-decorative-photo-library' "$VIEW"
echo "GOTOWE. Kopia oryginalnych plikow: $BACKUP"
echo 'Odswiez edytor, sprawdz wybor, anulowanie, usuwanie i ponowny zapis zdjecia.'
