<?php
/** FoodFoto: make the header builder honor saved paragraph width (768px currently).
 * Editor ONLY; preserve all other geometry, menu handlers and public files.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/admin/draft-header/preview.blade.php';
function haltFF(string $s):never{fwrite(STDERR,"STOP BEZ ZMIAN: $s\n");exit(2);}
$before=@file_get_contents($file);
if(!is_string($before)||!is_writable($file))haltFF('Nie mozna odczytac pliku edytora.');
$tag='FF_EDITOR_DESCRIPTION_RESPECT_SAVED_WIDTH_20261023';
if(str_contains($before,$tag)){echo "JUZ ZAINSTALOWANE\n";exit(0);}
foreach(['FF_EDITOR_PUBLIC_HERO_STAGE_PARITY_20261023','FF_HERO_MENU_POINTER_FIX_20261022','function apply(el,p)'] as $s){
 if(!str_contains($before,$s))haltFF('Brak oczekiwanej poprawki: '.$s);
}
$needle='</style></head>';
if(substr_count($before,$needle)!==1)haltFF('Nieznana struktura CSS podgladu.');
$css=<<<'CSS'
/* FF_EDITOR_DESCRIPTION_RESPECT_SAVED_WIDTH_20261023
   The public p has max-width:var(--lw), which equals saved description.w.
   Editor sets element.style.width to this value but an old max-width:560px
   capped it. Uncap ONLY this draggable paragraph, never the public site. */
#viewport-stage:not(.inner-page) .hero-copy p[data-drag="description"]{
  max-width:none;
}
CSS;
$after=str_replace($needle,$css."\n".$needle,$before);
$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))haltFF('Brak katalogu kopii.');
$backup=$backupDir.'/header-editor-width-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$before,LOCK_EX)===false)haltFF('Nie utworzono kopii.');
$tmp=$file.'.ffwidth-'.getmypid();
if(file_put_contents($tmp,$after,LOCK_EX)===false)haltFF('Nie zapisano pliku tymczasowego.');
@chmod($tmp,fileperms($file)&0777);
if(!rename($tmp,$file))haltFF('Nie zmieniono pliku.');
$out=[];$code=1;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$out,$code);
if($code!==0){@copy($backup,$file);exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');haltFF('Kompilacja nieudana. Przywrocono kopie: '.implode(' | ',$out));}
echo "SUKCES: builder respektuje zapisana szerokosc pola opisu, bez limitu 560px.\n";
echo "Biala kapsula menu oraz publiczna strona NIEZMIENIONE.\n";
echo "KOPIA: $backup\n";
echo "Teraz odswiez builder Cmd+Shift+R i porownaj opis z publiczna strona.\n";
