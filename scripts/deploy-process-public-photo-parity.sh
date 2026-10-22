#!/usr/bin/env bash
# Targeted repair of builder/public photo geometry parity on SEOHOST.
# Does not replace production files with older GitHub versions.
set -euo pipefail
cd /home/srv51861/domains/foodfoto.pl/laravel
SUPPORT='app/Support/ProcessTypography.php'
PUBLIC='resources/views/components/builder-process-steps.blade.php'
CSS='public/css/foodfoto-process.css'
for path in "$SUPPORT" "$PUBLIC" "$CSS"; do
    [[ -s "$path" ]] || { echo "PRZERWANO: brakuje $path" >&2; exit 1; }
done
grep -Fq 'positionStyle(' "$SUPPORT" || { echo 'PRZERWANO: nie znaleziono kodu geometrii.' >&2; exit 1; }
grep -Fq 'ff-process-decorative-photo' "$PUBLIC" || { echo 'PRZERWANO: publiczny komponent nie ma dekoracji.' >&2; exit 1; }
grep -Fq -- '--ff-part-scale' "$CSS" || { echo 'PRZERWANO: CSS nie ma mechanizmu skalowania; niczego nie zmieniono.' >&2; exit 1; }

BACKUP="storage/app/process-public-photo-parity-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -p "$SUPPORT" "$BACKUP/ProcessTypography.php"
cp -p "$PUBLIC" "$BACKUP/builder-process-steps.blade.php"
echo "Kopia bezpieczenstwa: $BACKUP"

php <<'PHP'
<?php
$helper = 'app/Support/ProcessTypography.php';
$blade = 'resources/views/components/builder-process-steps.blade.php';
$h = file_get_contents($helper);
$b = file_get_contents($blade);
$abort = static function(string $why): never {
    fwrite(STDERR, "PRZERWANO: {$why}. Oryginalne pliki pozostaly bez zmian.\n");
    exit(1);
};
if (!is_string($h) || !is_string($b)) $abort('blad odczytu');

$needle = <<<'CODE'
return '--ff-part-x:'.$x.'px;--ff-part-y:'.$y.'px;';
CODE;
$replacement = <<<'CODE'
// Keep the same scale as the interactive builder. Previously only X/Y
// reached the public page, so a resized photo reverted to 1x.
$scale = is_numeric($position['scale'] ?? null)
    ? max(0.1, min(12, (float) $position['scale']))
    : 1;
return '--ff-part-x:'.$x.'px;--ff-part-y:'.$y.'px;--ff-part-scale:'.$scale.';';
CODE;

$helperChanged = false;
if (!str_contains($h, "--ff-part-scale:")) {
    if (substr_count($h, $needle) !== 1) $abort('nieznana wersja funkcji positionStyle');
    $h = str_replace($needle, $replacement, $h);
    $helperChanged = true;
}

$bladeChanged = false;
// The editor renders DIV > IMG, but production used a bare IMG with a
// different containing box. Match the editor's DOM without changing
// existing user data or the inline geometry settings.
if (!str_contains($b, 'class="ff-process-decorative-photo-image"')) {
    $re = '~^([ \t]*)<img class="ff-process-decorative-photo"\s+data-process-move="decorative_photo"[\s\S]*?>~m';
    if (preg_match_all($re, $b, $found) !== 1) $abort('nieznany szablon obrazka w widoku publicznym');
    $whole = $found[0][0];
    $indent = $found[1][0];
    if (!preg_match('~\bsrc="([^"]+)"~', $whole, $src)) $abort('brakuje src obrazka');
    if (!preg_match('~\bstyle="([^"]+)"~', $whole, $style)) $abort('brakuje stylu obrazka');
    if (!str_contains($style[1], 'decorative_photo_width')
        || !str_contains($style[1], "positionStyle(")) $abort('nie rozpoznano stylu geometrii obrazka');
    $new = $indent . '<div class="ff-process-decorative-photo" data-process-move="decorative_photo" aria-hidden="true" style="' . $style[1] . '">' . "\n"
         . $indent . '    <img class="ff-process-decorative-photo-image" src="' . $src[1] . '" alt="" loading="lazy">' . "\n"
         . $indent . '</div>';
    $b = str_replace($whole, $new, $b);
    $bladeChanged = true;
}

if (!$helperChanged && !$bladeChanged) {
    echo "Poprawka jest juz zainstalowana. Bez zmian.\n";
    exit(0);
}
// Validate changes before overwriting either file.
$helperTmp = $helper . '.new-' . getmypid();
$bladeTmp = $blade . '.new-' . getmypid();
if (file_put_contents($helperTmp, $h, LOCK_EX) === false ||
    file_put_contents($bladeTmp, $b, LOCK_EX) === false) $abort('nie mozna zapisac plikow tymczasowych');
$test = 'php -l ' . escapeshellarg($helperTmp) . ' 2>&1';
exec($test, $out, $status);
if ($status !== 0) {
    @unlink($helperTmp); @unlink($bladeTmp);
    $abort('niepoprawna skladnia PHP: ' . implode(' ', $out));
}
if ($helperChanged && !rename($helperTmp, $helper)) $abort('nie mozna zapisac ProcessTypography');
if (!$helperChanged) @unlink($helperTmp);
if ($bladeChanged && !rename($bladeTmp, $blade)) $abort('nie mozna zapisac szablonu Blade');
if (!$bladeChanged) @unlink($bladeTmp);
echo $helperChanged ? "Dodano odtwarzanie skali zapisanej w process_positions.\n" : "Skala byla juz obslugiwana.\n";
echo $bladeChanged ? "Publiczny obrazek uzywa teraz ramki takiej jak w builderze.\n" : "Publiczny obrazek mial juz ramke.\n";
PHP

# CLI PHP is 8.3 while vendor requires >=8.4.1. Clear only compiled Blade views.
if [[ -d storage/framework/views ]]; then
    find storage/framework/views -maxdepth 1 -type f -name '*.php' -delete
fi
echo '--- Weryfikacja plikow ---'
grep -n -- '--ff-part-scale:' "$SUPPORT" | tail -1
grep -n 'class="ff-process-decorative-photo-image"' "$PUBLIC" | head -1
echo '--- Publiczna strona, odczyt bez cache ---'
curl -fsSL --connect-timeout 6 --max-time 18 \
  "https://www.foodfoto.pl/?process-parity-check=$(date +%s)" | php -r '
    $html = stream_get_contents(STDIN);
    if (preg_match("~<div class=\"ff-process-decorative-photo\"[^>]*>~s", $html, $m)) {
      preg_match("~style=\"([^\"]*)\"~s", $m[0], $styles);
      echo "OK: ramka dekoracji. Styl: ", preg_replace("/\s+/", " ", $styles[1] ?? ""), PHP_EOL;
    } else {
      echo "UWAGA: publiczna ramka dekoracji nie zostala znaleziona.", PHP_EOL;
    }
  ' || true
echo "KONIEC. Odwrocenie zmian: kopie sa w $BACKUP"
echo 'Odswiez edytor i strone publiczna (Cmd+Shift+R) i sprawdz po Zapisz.'
