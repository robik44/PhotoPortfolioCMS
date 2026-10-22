#!/usr/bin/env bash
# SEOHOST targeted repair: editable button fields + restored mailto public links.
# Production may be newer than GitHub main. Never git pull/replace full Blade.
set -euo pipefail
cd /home/srv51861/domains/foodfoto.pl/laravel
EDITOR='resources/views/admin/pages/builder.blade.php'
PUBLIC='resources/views/components/public-builder-canvas.blade.php'
EMAIL_JS='public/js/email-protect.js'
EDITOR_JS='public/js/builder-contact-button-editor.js'
BASE='https://raw.githubusercontent.com/robik44/PhotoPortfolioCMS/7c1716b68f6fccae64723f1855779ecaeeb71fee'
# email-protect.js may be absent on SEOHOST; the installer creates it.
for f in "$EDITOR" "$PUBLIC"; do
    [[ -s "$f" ]] || { echo "PRZERWANO: brakuje $f" >&2; exit 1; }
done
grep -Fq 'BuilderButton.fields(properties, item' "$EDITOR" || { echo 'PRZERWANO: nieznana wersja edytora przycisku.' >&2; exit 1; }
grep -Fq 'data-ff-email' "$PUBLIC" || { echo 'PRZERWANO: brak mechanizmu e-mail w widoku publicznym.' >&2; exit 1; }

TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
curl -fsSL --retry 2 --connect-timeout 7 --max-time 30 "$BASE/$EMAIL_JS" -o "$TMP/email-protect.js"
curl -fsSL --retry 2 --connect-timeout 7 --max-time 30 "$BASE/$EDITOR_JS" -o "$TMP/builder-contact-button-editor.js"
grep -Fq 'window.__foodfotoEmailProtectionReady' "$TMP/email-protect.js"
grep -Fq 'tekst przycisku' "$TMP/builder-contact-button-editor.js"
if command -v node >/dev/null 2>&1; then
    node --check "$TMP/email-protect.js"
    node --check "$TMP/builder-contact-button-editor.js"
fi

BACKUP="storage/app/contact-finale-button-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP" public/js
cp -p "$EDITOR" "$BACKUP/builder.blade.php"
cp -p "$PUBLIC" "$BACKUP/public-builder-canvas.blade.php"
if [[ -f "$EMAIL_JS" ]]; then
    cp -p "$EMAIL_JS" "$BACKUP/email-protect.js"
else
    echo 'Brakujacy email-protect.js zostanie teraz zainstalowany.'
fi
if [[ -f "$EDITOR_JS" ]]; then cp -p "$EDITOR_JS" "$BACKUP/builder-contact-button-editor.js"; fi
echo "Kopia bezpieczenstwa: $BACKUP"

# Atomic replacement of just the small JavaScript files.
cp "$TMP/email-protect.js" "$EMAIL_JS.new"
cp "$TMP/builder-contact-button-editor.js" "$EDITOR_JS.new"
mv -f "$EMAIL_JS.new" "$EMAIL_JS"
mv -f "$EDITOR_JS.new" "$EDITOR_JS"
chmod 644 "$EMAIL_JS" "$EDITOR_JS"

php <<'PHP'
<?php
$editorPath = 'resources/views/admin/pages/builder.blade.php';
$publicPath = 'resources/views/components/public-builder-canvas.blade.php';
$e = file_get_contents($editorPath);
$p = file_get_contents($publicPath);
function abortInstall(string $message): never {
    fwrite(STDERR, "PRZERWANO: {$message}. Kopie zapasowe zapisano; wczesniejszych plikow Blade nie nadpisano.\n");
    exit(1);
}
if (!is_string($e) || !is_string($p)) abortInstall('nie udalo sie odczytac widokow');
if (!str_contains($e, '<x-app-layout>')) abortInstall('nie ma punktu wstawienia w edytorze');
$needle = '<div id="{{ $canvasId }}" class="builder-public-canvas"';
if (!str_contains($p, $needle)) abortInstall('nie ma punktu wstawienia w publicznym szablonie');

if (!str_contains($e, "asset('js/builder-contact-button-editor.js')")) {
    $script = <<<'BLADE'
    <script src="{{ asset('js/builder-contact-button-editor.js') }}?v={{ @filemtime(public_path('js/builder-contact-button-editor.js')) ?: 1 }}" defer></script>
BLADE;
    $e = str_replace('<x-app-layout>', "<x-app-layout>\n".$script, $e);
}
if (!str_contains($p, "asset('js/email-protect.js')")) {
    $script = <<<'BLADE'
<script src="{{ asset('js/email-protect.js') }}?v={{ @filemtime(public_path('js/email-protect.js')) ?: 1 }}" defer></script>
BLADE;
    $p = str_replace($needle, $script."\n".$needle, $p);
}
// Verify both edited variants before writing. No database content is changed.
if (substr_count($e, "asset('js/builder-contact-button-editor.js')") !== 1 ||
    substr_count($p, "asset('js/email-protect.js')") !== 1) {
    abortInstall('kontrola duplikatow skryptow nie powiodla sie');
}
if (file_put_contents($editorPath, $e, LOCK_EX) === false) abortInstall('blad zapisu buildera');
if (file_put_contents($publicPath, $p, LOCK_EX) === false) abortInstall('blad zapisu szablonu publicznego');
echo "OK: edytor i wszystkie publiczne strony korzystajace z buildera poprawione.\n";
PHP

# Artisan cannot run under server CLI PHP 8.3 when Composer expects >=8.4.1.
if [[ -d storage/framework/views ]]; then
    find storage/framework/views -maxdepth 1 -type f -name '*.php' -delete
fi
echo '--- WERYFIKACJA ---'
grep -n 'builder-contact-button-editor.js' "$EDITOR" | head -1
grep -n 'email-protect.js' "$PUBLIC" | head -1
echo 'Kontrola publicznego HTML:'
HTML=$(curl -fsSL --connect-timeout 7 --max-time 18 \
    "https://www.foodfoto.pl/?button-fix-check=$(date +%s)" || true)
if [[ -n "$HTML" ]]; then
    if grep -Fq 'data-ff-email=' <<<"$HTML"; then echo 'OK: kod e-mail jest obecny w publicznym HTML.'; else echo 'UWAGA: brak chronionego przycisku w pobranym HTML.'; fi
    if grep -Fq 'email-protect.js' <<<"$HTML"; then echo 'OK: strona zalacza skrypt odtwarzajacy link i napis.'; else echo 'UWAGA: publiczna strona nie zawiera skryptu, sprawdz cache i widok.'; fi
else
    echo 'UWAGA: nie udalo sie pobrac strony publicznej.'
fi
echo 'GOTOWE. Odswiez builder i strone publiczna (Cmd+Shift+R), przetestuj klik w przycisk.'
echo "Przywracanie starych plikow: $BACKUP"
