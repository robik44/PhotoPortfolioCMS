<?php
/** FoodFoto hero image optimizer, guarded and reversible.
 * Uses PHP 8.4 GD WebP encoder; no changes to source PNG or builder DB.
 * Only changes exact published hero image element, after creating/validating a
 * new file with identical dimensions. Falls back to existing PNG on error.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$pngRel='draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn.png';
$src=$root.'/storage/app/public/'.$pngRel;
$target=$root.'/storage/app/public/draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn-optimized.webp';
$view=$root.'/resources/views/admin/components/draft-shared-header.blade.php';
function ffStop(string $s):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
if(!is_file($src)||!is_readable($src))ffStop('Nie znaleziono pliku PNG naglowka na dysku; nie modyfikuje szablonu.');
$before=file_get_contents($view);
if(!is_string($before)||strlen($before)<10000||!is_writable($view))ffStop('Brak dostepu do szablonu naglowka.');
$mark='FF_HERO_WEBP_OPTIMIZED_20261023';
if(str_contains($before,$mark)){echo "JUZ ZAINSTALOWANE: optymalizacja naglowka.\n";exit(0);}
$original='@if($photo)<img class="ff-shared-photo" src="{{ asset(\'storage/\'.$photo) }}" alt="">@endif';
if(substr_count($before,$original)!==1)ffStop('Nie rozpoznano dokladnie elementu zdjecia w szablonie.');
if(!extension_loaded('gd')||!function_exists('imagecreatefrompng')||!function_exists('imagewebp'))ffStop('PHP 8.4 nie ma kodowania WebP w GD. Nic nie zmieniono.');
$info=@getimagesize($src);
if(!$info||$info[2]!==IMAGETYPE_PNG)ffStop('Zrodlo nie jest poprawnym PNG.');
[$w,$h]=$info;
if($w<500||$h<250||$w*$h>32000000)ffStop('Nietypowe rozmiary obrazu; wymaga kontroli.');
$oldBytes=filesize($src);
$image=@imagecreatefrompng($src);
if(!$image)ffStop('Nie mozna zdekodowac PNG.');
imagepalettetotruecolor($image);
imagealphablending($image,false);imagesavealpha($image,true);
$tmp=$target.'.tmp-'.getmypid().'.webp';
$encoded=@imagewebp($image,$tmp,88);
imagedestroy($image);
if(!$encoded||!is_file($tmp)){@unlink($tmp);ffStop('Kodowanie WebP nie powiodlo sie.');}
$newInfo=@getimagesize($tmp);$newBytes=filesize($tmp);
if(!$newInfo||$newInfo[2]!==IMAGETYPE_WEBP||$newInfo[0]!==$w||$newInfo[1]!==$h
    ||$newBytes>=($oldBytes*.8)||$newBytes<10000){
    @unlink($tmp);
    ffStop('WebP nie jest wystarczajaco mniejszy lub zmienil wymiary. Oryginal pozostaje aktywny.');
}
$dir=$root.'/storage/app/backups';if(!is_dir($dir)&&!mkdir($dir,0700,true))ffStop('Brak katalogu na backup.');
$backup=$dir.'/hero-webp-view-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$before,LOCK_EX)===false){@unlink($tmp);ffStop('Kopia szablonu nieudana.');}
if(!rename($tmp,$target))ffStop('Nie zapisano obrazu WebP.');
@chmod($target,0644);
$replacement=<<<'BLADE'
{{-- FF_HERO_WEBP_OPTIMIZED_20261023: same 1:1 scene photo, WebP with PNG fallback. --}}
@if($photo)
    @if($photo === 'draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn.png' && is_file(storage_path('app/public/draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn-optimized.webp')))
        <picture>
            <source type="image/webp" srcset="{{ asset('storage/draft-header/te2RqXBuxO4FtCXADpXtqnm4faS5LBuPy1AXNvwn-optimized.webp') }}">
            <img class="ff-shared-photo" src="{{ asset('storage/'.$photo) }}" alt="">
        </picture>
    @else
        <img class="ff-shared-photo" src="{{ asset('storage/'.$photo) }}" alt="">
    @endif
@endif
BLADE;
$after=str_replace($original,$replacement,$before);
if(substr_count($after,$mark)!==1)ffStop('Nieprawidlowa zmiana szablonu.');
$tempView=$view.'.heroopt-'.getmypid();
if(file_put_contents($tempView,$after,LOCK_EX)===false)ffStop('Blad zapisu szablonu tymczasowego.');
@chmod($tempView,fileperms($view)&0777);
if(!rename($tempView,$view))ffStop('Nie zapisano zmiany szablonu.');
if(file_get_contents($view)!==$after){@copy($backup,$view);ffStop('Kontrola zapisu nieudana, przywrocono szablon.');}
$php='/opt/alt/php84/usr/bin/php';
$output=[];$exit=0;
exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:cache 2>&1',$output,$exit);
if($exit!==0){
 @copy($backup,$view);
 exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:clear 2>&1');
 fwrite(STDERR,"KOMPILACJA NIEUDANA: przywrocono szablon.\n".implode("\n",$output)."\n");exit(3);
}
echo "KOPIA SZABLONU: $backup\n";
echo "ORYGINAL PNG: $oldBytes B (".round($oldBytes/1024)." KB)\n";
echo "NOWY WebP: $newBytes B (".round($newBytes/1024)." KB)\n";
echo "OSZCZEDNOSC: ".round(100*(1-$newBytes/$oldBytes),1)."%\n";
echo "ROZMIAR OBRAZU: $w x $h px (bez zmiany)\n";
echo "SUKCES: przegladarka moze uzyc WebP, a starsza pozostaje przy PNG.\n";
echo "NIE ZMIENIONO: zrodla PNG, zapisu zdjecia w bazie, buildera, pozycji i CSS.\n";
echo "UWAGA: zweryfikuj wyglad fotografii na laptopie, iMacu i telefonie.\n";
