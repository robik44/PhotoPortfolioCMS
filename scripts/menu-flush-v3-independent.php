<?php
/** FoodFoto: public FULL header menu anchored precisely top-right.
 * This installer does not require any former menu-hotfix marker.
 * Safe preflight, backup, atomic file replacement, no database/builder edits.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/admin/components/draft-shared-header.blade.php';
function stopFF(string $s):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
$old=@file_get_contents($file);
if(!is_string($old)||strlen($old)<10000||!is_writable($file))stopFF('Nie mozna odczytac szablonu.');
$flag='FF_MENU_TRUE_FLUSH_V3_20261022';
if(str_contains($old,$flag)){echo "JUZ ZAINSTALOWANE: panel przy krawedziach.\n";exit(0);}
$start="\n</style>\n<script>\ndocument.addEventListener('DOMContentLoaded', function () {";
$needle='        const targetWidth = Number.parseFloat(stage.dataset.menuWidth) || 0;';
if(substr_count($old,$start)!==1||substr_count($old,$needle)!==1
 || !str_contains($old,"root.classList.contains('ff-public-header')")
 || !str_contains($old,'data-menu-width='))
 stopFF('Nie rozpoznano aktualnej funkcji menu; nie ryzykuje zmian.');
$css=<<<'CSS'

/* FF_MENU_TRUE_FLUSH_V3_20261022 — only public full photo header.
   The 1400px stage is the single fixed reference frame. */
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-bar{
    position:relative!important;
    box-sizing:border-box!important;
    width:1400px!important;
    min-width:1400px!important;
    max-width:1400px!important;
    margin:0!important;
    padding:0!important;
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
    top:0!important;
    right:0!important;
    left:auto!important;
    bottom:auto!important;
    margin:0!important;
    transform:none!important;
    transform-origin:top right!important;
    max-width:none!important;
    flex:0 0 auto!important;
    flex-wrap:nowrap!important;
    white-space:nowrap!important;
}
CSS;
$js=<<<'JS'
        // FF full public photo header: use builder width consistently, do not
        // move menu via collision/viewport fit code. CSS pins it to top/right 0.
        if (root.classList.contains('ff-public-header') && !isSimple) {
            const desired = Number.parseFloat(stage.dataset.menuWidth) || 0;
            menu.style.zoom = '1';
            menu.style.transform = 'none';
            const unscaled = menu.offsetWidth;
            const factor = desired > 0 && unscaled > 100
                ? Math.max(.55, Math.min(1.8, desired / unscaled)) : 1;
            menu.style.zoom = String(factor);
            return;
        }
JS;
$new=str_replace($start,"\n".$css.$start,$old);
$new=str_replace($needle,$js."\n".$needle,$new);
if(substr_count($new,$flag)!==1||$new===$old)stopFF('Poprawka nie zostala poprawnie przygotowana.');
$folder=$root.'/storage/app/backups';
if(!is_dir($folder)&&!mkdir($folder,0700,true))stopFF('Brak katalogu na kopie.');
$backup=$folder.'/menu-flush-v3-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)stopFF('Nie zapisano kopii.');
$tmp=$file.'.flush-v3-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)stopFF('Nie zapisano pliku tymczasowego.');
@chmod($tmp,fileperms($file)&0777);
if(!rename($tmp,$file)){@unlink($tmp);stopFF('Nie udalo sie podmienic szablonu.');}
if(@file_get_contents($file)!==$new){@copy($backup,$file);fwrite(STDERR,"KONTROLA NIEUDANA; przywrocono kopie.\n");exit(3);}
$cache=$root.'/storage/framework/views';
if(is_dir($cache))foreach(glob($cache.'/*.php')?:[] as $path)if(is_file($path))@unlink($path);
echo "KOPIA: $backup\n";
echo "SUKCES: menu przypiete dokladnie do prawego i gornego rogu sceny (0 px).\n";
echo "SUKCES: skala menu zgodna z zapisem buildera, bez przeliczania dla szerokosci okna.\n";
echo "Bez zmian: builder, baza danych, zdjecia, naglowek uproszczony.\n";
echo "Uwaga: przy wezszych oknach prawa krawedz projektu moze wymagac przewiniecia poziomego.\n";
