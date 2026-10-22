<?php
/** FoodFoto: single authoritative placement for photo-header menu.
 * No DB writes. Requires previous fixed-canvas and absolute-menu patches.
 * Stable design coordinates, top/right 12px, no collision calculations.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$path=$root.'/resources/views/admin/components/draft-shared-header.blade.php';
function abortFix(string $s):never {fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
$old=@file_get_contents($path);
if(!is_string($old)||strlen($old)<10000||!is_writable($path))abortFix('Nie ma dostepu do szablonu.');
$marker='FF_MENU_STABLE_SINGLE_AUTHORITY_20261022';
if(str_contains($old,$marker)){echo "JUZ ZAINSTALOWANE\n";exit(0);}
foreach(['FF_FIXED_1400_COMPOSITION_20261022','FF_MENU_ABSOLUTE_TOP_RIGHT_20261022'] as $v)
 if(!str_contains($old,$v))abortFix('Brak wczesniejszej poprawki: '.$v);
$anchor='        const targetWidth = Number.parseFloat(stage.dataset.menuWidth) || 0;';
if(substr_count($old,$anchor)!==1)abortFix('Zmieniona funkcja skalowania.');
$css=<<<'CSS'

/* FF_MENU_STABLE_SINGLE_AUTHORITY_20261022:
   One independent reference frame, one fixed inset, preserved capsule size.
   Override old positioning/translation rules for the public photo hero only. */
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-bar{
    position:relative!important;
    width:1400px!important;
    min-width:1400px!important;
    max-width:none!important;
    padding:0!important;
    margin:0!important;
    box-sizing:border-box!important;
    display:block!important;
}
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-brand{
    position:absolute!important;
    left:56px!important;
    top:12px!important;
    display:flex!important;
}
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-menu{
    position:absolute!important;
    top:12px!important;
    right:12px!important;
    bottom:auto!important;
    left:auto!important;
    margin:0!important;
    transform:none!important;
    transform-origin:top right!important;
    max-width:none!important;
    white-space:nowrap!important;
}
CSS;
$styleAnchor="\n</style>\n<script>\ndocument.addEventListener('DOMContentLoaded', function () {";
if(substr_count($old,$styleAnchor)!==1)abortFix('Nie rozpoznano miejsca wstawienia CSS.');
$new=str_replace($styleAnchor,"\n".$css.$styleAnchor,$old);
$js=<<<'JS'
        // FF fixed photo hero: zoom calculated once from ORIGINAL menu size;
        // no free-space shrinking, dragging, collision fit or translate.
        // Same builder width at every desktop/browser zoom.
        if (root.classList.contains('ff-public-header') && !isSimple) {
            const wanted = Number.parseFloat(stage.dataset.menuWidth) || 0;
            menu.style.zoom = '1';
            menu.style.transform = 'none';
            const intrinsic = menu.offsetWidth;
            const scale = intrinsic > 100 && wanted > 0
                ? Math.max(.55,Math.min(1.8,wanted/intrinsic)) : 1;
            menu.style.zoom = String(scale);
            return;
        }
JS;
$new=str_replace($anchor,$js."\n".$anchor,$new);
if(substr_count($new,$marker)!==1)abortFix('Niespojna poprawka.');
$dir=$root.'/storage/app/backups';
if(!is_dir($dir)&&!mkdir($dir,0700,true))abortFix('Brak kopii.');
$backup=$dir.'/stable-menu-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)abortFix('Nie mozna zapisac kopii.');
$tmp=$path.'.stable-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)abortFix('Nie mozna zapisac zmiany.');
@chmod($tmp,fileperms($path)&0777);
if(!rename($tmp,$path))abortFix('Nie mozna podmienic.');
if(file_get_contents($path)!==$new){@copy($backup,$path);abortFix('Weryfikacja zapisu; przywrocono plik.');}
$views=$root.'/storage/framework/views';
if(is_dir($views))foreach(glob($views.'/*.php')?:[] as $v)if(is_file($v))@unlink($v);
echo "KOPIA: $backup\n";
echo "ZAINSTALOWANO: stale menu 12px od prawej i gornej krawedzi sceny 1400px.\n";
echo "ZAINSTALOWANO: skala panelu z buildera, bez reagowania na szerokosc okna.\n";
echo "UWAGA: przy oknie wezszym od 1400px przewin w prawo, aby zobaczyc prawa czesc projektu.\n";
echo "NIE ZMIENIONO: bazy, zapisanych pozycji, zdjec, buildera ani naglowka uproszczonego.\n";
