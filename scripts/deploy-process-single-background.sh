#!/usr/bin/env bash
# Targeted SEOHOST update: keep the newer production builder files intact.
set -euo pipefail
cd /home/srv51861/domains/foodfoto.pl/laravel
CSS='public/css/foodfoto-process.css'
JS='public/js/process-custom-photo-only.js'
VIEW='resources/views/admin/pages/builder.blade.php'
PUBLIC='resources/views/components/builder-process-steps.blade.php'
BASE='https://raw.githubusercontent.com/robik44/PhotoPortfolioCMS/afb8b83f57db5212449ab2f2a96ffed9d204be0f'
MARK='FOODFOTO_CUSTOM_PROCESS_BACKGROUND_V1'

for file in "$CSS" "$VIEW" "$PUBLIC" public/js/foodfoto-process.js; do
    [[ -s "$file" ]] || { echo "PRZERWANO: brakuje $file" >&2; exit 1; }
done
if ! grep -Fq 'decorative_photo_id' "$PUBLIC" || ! grep -Fq 'decorative_photo_id' public/js/foodfoto-process.js; then
    echo 'PRZERWANO: szablon publiczny lub edytor nie obsluguja zdjecia dekoracyjnego.' >&2
    exit 1
fi

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
curl -fsSL --retry 2 --max-time 30 "$BASE/public/css/process-custom-photo-only.patch.css" -o "$TMP/patch.css"
curl -fsSL --retry 2 --max-time 30 "$BASE/$JS" -o "$TMP/process-custom-photo-only.js"
grep -Fq "$MARK" "$TMP/patch.css"
grep -Fq "$MARK" "$TMP/process-custom-photo-only.js"
if command -v node >/dev/null 2>&1; then node --check "$TMP/process-custom-photo-only.js"; fi

BACKUP="storage/app/process-background-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -p "$CSS" "$BACKUP/foodfoto-process.css"
cp -p "$VIEW" "$BACKUP/builder.blade.php"
if [[ -f "$JS" ]]; then cp -p "$JS" "$BACKUP/process-custom-photo-only.js"; fi
echo "Kopia oryginalow: $BACKUP"

if ! grep -Fq "$MARK" "$CSS"; then
    cat "$TMP/patch.css" >> "$CSS"
    echo 'CSS: stare galazki i poswiata ukryte, zdjecie za tekstem.'
else
    echo 'CSS: poprawka byla juz wgrana.'
fi
cp "$TMP/process-custom-photo-only.js" "$JS"
chmod 644 "$JS"

php <<'PHP'
<?php
$path = 'resources/views/admin/pages/builder.blade.php';
$s = file_get_contents($path);
if (!is_string($s) || !str_contains($s, '<x-app-layout>')) {
    fwrite(STDERR, "PRZERWANO: nie znaleziono punktu wstawienia skryptu UI.\n");
    exit(1);
}
if (!str_contains($s, "asset('js/process-custom-photo-only.js')")) {
    $include = <<<'BLADE'
    <script src="{{ asset('js/process-custom-photo-only.js') }}?v={{ @filemtime(public_path('js/process-custom-photo-only.js')) ?: 1 }}" defer></script>
BLADE;
    $s = str_replace('<x-app-layout>', "<x-app-layout>\n" . $include, $s);
    if (file_put_contents($path, $s, LOCK_EX) === false) {
        fwrite(STDERR, "PRZERWANO: blad zapisu widoku.\n");
        exit(1);
    }
    echo "Builder: ukryto kontrolki starej galazki i poswiaty.\n";
} else {
    echo "Builder: skrypt czyszczenia panelu byl juz dodany.\n";
}
PHP

if [[ -d storage/framework/views ]]; then
    find storage/framework/views -maxdepth 1 -type f -name '*.php' -delete
fi
echo '--- KONTROLA PLIKOW ---'
grep -n 'FOODFOTO_CUSTOM_PROCESS_BACKGROUND_V1' "$CSS" "$JS"
grep -n 'process-custom-photo-only.js' "$VIEW" | head -2

echo '--- PUBLICZNY HTML ---'
HTML=$(curl -fsSL --retry 1 --connect-timeout 6 --max-time 18 "https://www.foodfoto.pl/?process-check=$(date +%s)" || true)
if [[ -z "$HTML" ]]; then
    echo 'UWAGA: nie udalo sie pobrac publicznej strony.'
elif grep -Eq '<img[^>]*class="[^"]*ff-process-decorative-photo' <<< "$HTML"; then
    echo 'OK: publiczna strona generuje zdjecie dekoracyjne (tag IMG).'
else
    echo 'UWAGA: publiczny HTML nie zawiera zdjecia dekoracyjnego!'
    echo 'Sprawdz zapis dekoracyjnego photo ID lub powiazanie rekordu Photo.'
    echo 'Sama zmiana CSS nie naprawi braku tagu IMG w HTML.'
fi
echo 'GOTOWE. Odswiez edytor i strone publiczna (Cmd+Shift+R).'
