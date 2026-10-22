<?php
/** FoodFoto: white public hero capsule FLUSH with the right and top edges.
 * Supports either: previous stable 12px patch applied or not applied.
 * Modifies public shared-header component only; no builder/database writes.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/admin/components/draft-shared-header.blade.php';
function stopFlush(string $s):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
$old=@file_get_contents($file);
if(!is_string($old)||strlen($old)<10000||!is_writable($file))stopFlush('Plik szablonu nieczytelny.');
$done='FF_MENU_FLUSH_EDGES_20261022';
if(str_contains($old,$done)){echo "JUZ GOTOWE: panel ma top:0/right:0.\n";exit(0);}
foreach(['FF_FIXED_1400_COMPOSITION_20261022','FF_MENU_ABSOLUTE_TOP_RIGHT_20261022'] as $mark)
 if(!str_contains($old,$mark))stopFlush('Brak wymaganej wersji: '.$mark);
$stable='FF_MENU_STABLE_SINGLE_AUTHORITY_20261022';
$sel='#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-menu';
if(str_contains($old,$stable)){
    // Update only the last precise menu declaration from the previous patch.
    $start=strpos($old,'/* '.$stable);
    $end=strpos($old,"\n</style>",$start);
    if($start===false||$end===false)stopFlush('Nie rozpoznano bloku poprzedniej poprawki.');
    $part=substr($old,$start,$end-$start);
    $rx='~('.preg_quote($sel,'~').'\s*\{)([^}]*)(\})~s';
    if(preg_match_all($rx,$part,$m)!==1)stopFlush('Nie znaleziono jednoznacznego bloku menu.');
    $decl=$m[2][0];
    if(substr_count($decl,'top:12px!important;')!==1||substr_count($decl,'right:12px!important;')!==1)
       stopFlush('Istniejace odstepy sa inne niz 12px. Bez nadpisywania.');
    $adjust=str_replace(['top:12px!important;','right:12px!important;'],
                        ['top:0!important;','right:0!important;'],$decl);
    $part=preg_replace($rx,'$1'.$adjust.'$3',$part,1);
    $part=str_replace($stable,$stable." / ".$done,$part);
    $new=substr_replace($old,$part,$start,$end-$start);
}else{
    // Introduce the stable top-right geometry in a single scoped CSS block,
    // plus a single early-return in fitMenu, bypassing conflicting prior JS.
    $styleAnchor="\n</style>\n<script>\ndocument.addEventListener('DOMContentLoaded', function () {";
    $jsAnchor='        const targetWidth = Number.parseFloat(stage.dataset.menuWidth) || 0;';
    if(substr_count($old,$styleAnchor)!==1||substr_count($old,$jsAnchor)!==1)
      stopFlush('Inna wersja szablonu; nie podejmuje ryzyka.');
    $css=<<<'CSS'

/* FF_MENU_FLUSH_EDGES_20261022 — public FULL photo header only. */
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-bar{
    position:relative!important;width:1400px!important;min-width:1400px!important;
    max-width:none!important;padding:0!important;margin:0!important;
    box-sizing:border-box!important;display:block!important;
}
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-brand{
    position:absolute!important;left:56px!important;top:12px!important;display:flex!important;
}
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full .ff-shared-menu{
    position:absolute!important;top:0!important;right:0!important;
    left:auto!important;bottom:auto!important;margin:0!important;
    transform:none!important;transform-origin:top right!important;
    max-width:none!important;white-space:nowrap!important;
}
CSS;
    $js=<<<'JS'
        // Fixed public FULL hero: single preferred menu width, no JS moves.
        if (root.classList.contains('ff-public-header') && !isSimple) {
            const wanted = Number.parseFloat(stage.dataset.menuWidth) || 0;
            menu.style.zoom = '1';
            menu.style.transform = 'none';
            const natural = menu.offsetWidth;
            menu.style.zoom = String(natural > 100 && wanted > 0
                ? Math.max(.55,Math.min(1.8,wanted/natural)) : 1);
            return;
        }
JS;
    $new=str_replace($styleAnchor,"\n".$css.$styleAnchor,$old);
    $new=str_replace($jsAnchor,$js."\n".$jsAnchor,$new);
}
if(!isset($new)||$new===$old||substr_count($new,$done)!==1)stopFlush('Nie udalo sie przygotowac jednoznacznej poprawki.');
$dir=$root.'/storage/app/backups';
if(!is_dir($dir)&&!mkdir($dir,0700,true))stopFlush('Nie mozna przygotowac backup.');
$backup=$dir.'/menu-flush-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)stopFlush('Backup nieudany.');
$temp=$file.'.flush-'.getmypid();
if(file_put_contents($temp,$new,LOCK_EX)===false)stopFlush('Nie mozna zapisac pliku tymczasowego.');
@chmod($temp,fileperms($file)&0777);
if(!rename($temp,$file))stopFlush('Podmiana nieudana.');
if(@file_get_contents($file)!==$new){@copy($backup,$file);stopFlush('Weryfikacja nieudana: przywrocono kopie.');}
$cache=$root.'/storage/framework/views';
if(is_dir($cache))foreach(glob($cache.'/*.php')?:[] as $p)if(is_file($p))@unlink($p);
echo "KOPIA: $backup\n";
echo "SUKCES: biale menu publicznego pelnego naglowka: top=0, right=0.\n";
echo "Pozostale pozycje, rozmiary buildera, baza i obrazy bez zmian.\n";
echo "Przy oknie wezszym od 1400px prawa czesc projektu moze wymagac przewijania.\n";
