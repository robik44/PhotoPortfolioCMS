<?php
/**
 * FoodFoto: keep the PORTFOLIO dropdown above page content in PUBLIC
 * SIMPLE headers. Does not touch full photo headers, fixed 1400px geometry,
 * capsule sizing/alignment, builder settings, or any database records.
 */
declare(strict_types=1);

$root = '/home/srv51861/domains/foodfoto.pl/laravel';
$file = $root.'/resources/views/admin/components/draft-shared-header.blade.php';

function ffStop(string $why): never {
    fwrite(STDERR, "STOP BEZ ZMIAN: ".$why."\n");
    exit(2);
}

$original = @file_get_contents($file);
if (!is_string($original) || strlen($original) < 15000 || !is_writable($file)) {
    ffStop('Nie mozna bezpiecznie odczytac szablonu naglowka.');
}

$marker = 'FF_SIMPLE_DROPDOWN_LAYER_20261026';
if (str_contains($original, $marker)) {
    echo "JUZ ZAINSTALOWANE: poprawka menu jest obecna; bez zmian.\n";
    exit(0);
}

$anchor = '#fve-header-variant-preview.ff-public-header .ff-shared-stage{overflow:visible}';
if (substr_count($original, $anchor) !== 1
    || !str_contains($original, 'class="{{ $isPublicDraftHeader ? \'ff-public-header\' : \'ff-private-header\' }}" data-variant="{{ $headerVariantValue }}"')
    || !str_contains($original, '#fve-header-variant-preview.ff-public-header .ff-full{overflow:hidden}')
    || !str_contains($original, '#fve-header-variant-preview .ff-subnav{position:absolute;')) {
    ffStop('Wersja pliku rozni sie od sprawdzonej; nic nie zmieniono.');
}

$css = <<<'CSS'
/* FF_SIMPLE_DROPDOWN_LAYER_20261026
   On simple, photo-free public headers, the whole header stacking context
   must sit above the next page-builder section when PORTFOLIO opens.
   Do not change size, position, zoom or right-edge anchoring of the menu. */
#fve-header-variant-preview.ff-public-header[data-variant="simple"] {
    position: relative;
    z-index: 2500;
    overflow: visible;
}
#fve-header-variant-preview.ff-public-header[data-variant="simple"] .ff-shared-stage.ff-simple {
    overflow: visible;
}
CSS;

$revised = str_replace($anchor, $anchor."\n".$css, $original);
if ($revised === $original || !str_contains($revised, $marker)) {
    ffStop('Nie udalo sie zbudowac precyzyjnej poprawki.');
}

$backupDir = $root.'/storage/app/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0700, true)) {
    ffStop('Nie mozna utworzyc katalogu kopii.');
}
$backup = $backupDir.'/simple-header-dropdown-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if (file_put_contents($backup, $original, LOCK_EX) === false) {
    ffStop('Nie udalo sie utworzyc kopii bezpieczenstwa.');
}

$temp = $file.'.dropdown-'.getmypid();
if (file_put_contents($temp, $revised, LOCK_EX) === false) {
    ffStop('Nie udalo sie zapisac pliku tymczasowego.');
}
@chmod($temp, fileperms($file) & 0777);
if (!rename($temp, $file)) {
    @unlink($temp);
    ffStop('Nie udalo sie podmienic pliku.');
}

$output = [];
$status = 1;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1', $output, $status);
if ($status !== 0) {
    @copy($backup, $file);
    exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');
    ffStop('Kompilacja Laravel nie powiodla sie; przywrocono kopie. '.implode(' | ', $output));
}

echo "SUKCES: rozwijane menu w publicznych naglowkach uproszczonych moze wyswietlac sie nad trescia podstrony.\n";
echo "Bez zmian: naglowek ze zdjeciem, 1400px, logo, biala kapsula, jej rozmiar i pozycja.\n";
echo "Kompilacja Blade: OK.\n";
echo "KOPIA: ".$backup."\n";
echo "Odswiez /o-mnie i sprawdz pelne podmenu PORTFOLIO.\n";
