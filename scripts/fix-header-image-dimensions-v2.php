<?php
/** FoodFoto: targeted width/height only on site-header hero and logo.
 * Safe against divergence: backups, Blade compilation, rollback. Leaves ALT unchanged.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$view=$root.'/resources/views/admin/components/draft-shared-header.blade.php';
$logo=$root.'/storage/app/public/branding/foodfoto-transparent-v2.png';
$hero=$root.'/storage/app/public/draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn.png';
function bail(string $s):never{fwrite(STDERR,"STOP BEZ ZMIAN: $s\n");exit(2);}
foreach([$view,$logo,$hero] as $p)if(!is_file($p))bail('Brak wymaganego pliku '.basename($p));
$raw=file_get_contents($view);if(!is_string($raw)||!is_writable($view))bail('Brak dostepu do szablonu');
$mark='FF_HEADER_DIMENSIONS_20261023';
if(str_contains($raw,$mark)){echo "JUZ ZAINSTALOWANE — wymiary logo i naglowka.\n";exit(0);}
$logoSz=getimagesize($logo);$heroSz=getimagesize($hero);
if(!$logoSz||!$heroSz||$heroSz[0]!==1916||$heroSz[1]!==821)bail('Nieoczekiwany obraz naglowka.');
if($logoSz[0]<10||$logoSz[1]<10||$logoSz[0]>4000||$logoSz[1]>4000)bail('Nieprawidlowe wymiary logo.');
$logoNeedle='<img src="{{ asset(\'storage/branding/foodfoto-transparent-v2.png\') }}" alt="FoodFoto">';
if(substr_count($raw,$logoNeedle)!==1)bail('Nie rozpoznano znacznika logo; nie dokonano zmian.');
$raw2=str_replace($logoNeedle,
 '<img src="{{ asset(\'storage/branding/foodfoto-transparent-v2.png\') }}" alt="FoodFoto" width="'.$logoSz[0].'" height="'.$logoSz[1].'">',$raw);
$heroNeedle='<img class="ff-shared-photo"';
if(substr_count($raw2,$heroNeedle)!==2) {
 // Previous WebP installation has one hero img in picture and one fallback in @else, expected two.
 bail('Nie rozpoznano dwoch znacznikow hero w zoptymalizowanym naglowku.');
}
$heroAttrs='@if($photo === \'draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn.png\') width="1916" height="821" @endif';
$raw3=str_replace($heroNeedle,$heroNeedle.' '.$heroAttrs,$raw2);
if(substr_count($raw3,$heroAttrs)!==2)bail('Blad przygotowania szablonu.');
$raw3=str_replace('{{-- FF_HERO_WEBP_OPTIMIZED_20261023:', '{{-- '.$mark.' | FF_HERO_WEBP_OPTIMIZED_20261023:', $raw3);
if(!str_contains($raw3,$mark))bail('Brak znacznika istniejacej optymalizacji WebP.');
$backupDir=$root.'/storage/app/backups';if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))bail('Nie mozna wykonac katalogu kopii.');
$backup=$backupDir.'/header-dimensions-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$raw,LOCK_EX)===false)bail('Nie mozna zrobic kopii.');
$tmp=$view.'.dimensions-'.getmypid();
if(file_put_contents($tmp,$raw3,LOCK_EX)===false)bail('Blad zapisu');
@chmod($tmp,fileperms($view)&0777);
if(!rename($tmp,$view))bail('Nie udalo sie zaktualizowac.');
$out=[];$exit=0;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$out,$exit);
if($exit!==0){@copy($backup,$view);exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');bail('Kompilacja nieudana; przywrocono kopie: '.implode(' / ',$out));}
echo "SUKCES — wymiary naglowka 1916 x 821 i logo ".$logoSz[0]." x ".$logoSz[1].".\n";
echo "KOPIA: $backup\n";
echo "Blade cache: OK. ALT, CSS, builder, obrazy i mobilny uklad BEZ ZMIAN.\n";
echo "Sprawdz strone publiczna oraz widok mobilny.\n";
