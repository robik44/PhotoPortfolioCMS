<?php
/**
 * FoodFoto: follow-up repair for fixed 1400px layout where the menu
 * overlaps the brand / crops at the left edge in Firefox.
 * The full public hero only: absolute top-right capsule independent of brand.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$path=$root.'/resources/views/admin/components/draft-shared-header.blade.php';
function failMenu(string $msg):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $msg\n");exit(2);}
$old=@file_get_contents($path);
if(!is_string($old)||strlen($old)<10000||!is_writable($path))failMenu('Brak dostepu do szablonu.');
$flag='FF_MENU_ABSOLUTE_TOP_RIGHT_20261022';
if(str_contains($old,$flag)){echo "Poprawka juz zainstalowana.\n";exit(0);}
foreach(['FF_FIXED_1400_COMPOSITION_20261022','const nativeWidth = menu.getBoundingClientRect().width / viewScale;', "root.classList.contains('ff-public-header') && !isSimple"] as $needed)
 if(!str_contains($old,$needed))failMenu('Inna wersja naglowka. Brak: '.$needed);
$css=<<<'CSS'

/* FF_MENU_ABSOLUTE_TOP_RIGHT_20261022: the capsule is positioned from the
   fixed scene, never from the brand's flex width. Its placement is constant
   under browser zoom and across browsers. */
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-bar {
  position:relative!important;
  padding-left:56px!important;
  padding-right:0!important;
  justify-content:flex-start!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-menu {
  position:absolute!important;
  left:auto!important;
  top:0!important;
  right:0!important;
  margin:0!important;
  transform:none!important;
  transform-origin:top right!important;
  z-index:8!important;
}
CSS;
$anchor="\n</style>\n<script>\ndocument.addEventListener('DOMContentLoaded', function () {";
if(substr_count($old,$anchor)!==1)failMenu('Nie rozpoznano granicy stylow publicznego naglowka.');
$new=str_replace($anchor,"\n".$css.$anchor,$old);
$jsAnchor="        const barRect = bar.getBoundingClientRect();";
$js=<<<'JS'
        // The photo-header capsule is already absolutely anchored via CSS.
        // Set only the width scale matching the builder; never translate it.
        if (root.classList.contains('ff-public-header') && !isSimple) {
            menu.style.zoom = String(zoom);
            menu.style.transform = 'none';
            return;
        }
JS;
if(substr_count($new,$jsAnchor)!==1)failMenu('Nie rozpoznano skryptu skali menu.');
// Install a robust early-return BEFORE any previous absolute/right alignment
// code; older blocks are left in place but never run for public full header.
$new=str_replace($jsAnchor,$js."\n".$jsAnchor,$new);
if(substr_count($new,$flag)!==1)failMenu('Blad skladania.');
$folder=$root.'/storage/app/backups';
if(!is_dir($folder)&&!mkdir($folder,0700,true))failMenu('Brak katalogu backup.');
$backup=$folder.'/menu-overlap-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)failMenu('Backup sie nie zapisal.');
$tmp=$path.'.hotfix-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)failMenu('Zapis tymczasowy nie udal sie.');
@chmod($tmp,fileperms($path)&0777);
if(!rename($tmp,$path))failMenu('Podmiana nieudana.');
if(@file_get_contents($path)!==$new){@copy($backup,$path);fwrite(STDERR,"Kontrola nieudana; odtworzono kopie.\n");exit(3);}
$views=$root.'/storage/framework/views';
if(is_dir($views))foreach(glob($views.'/*.php')?:[] as $f)if(is_file($f))@unlink($f);
echo "KOPIA: $backup\n";
echo "SUKCES: menu publicznego naglowka ze zdjeciem jest niezalezne od logo, stale przypiete do prawej gornej krawedzi.\n";
echo "Bez zmian: builder, dane, fotografie, zapisane rozmiary, nazwy i podstrony z naglowkiem uproszczonym.\n";
