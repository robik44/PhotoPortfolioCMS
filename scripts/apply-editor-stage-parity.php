<?php
/**
 * FoodFoto — stage-coordinate parity for the HEADER EDITOR ONLY.
 *
 * - copies the PUBLIC full hero's absolute layout and typography baselines
 *   into the editor while preserving saved drag/resize transforms
 * - leaves the white menu's existing geometry and grip handlers untouched
 * - does NOT touch public templates, 1400px viewport or SiteSetting records
 * - backs up original file and automatically rolls back on Blade compile failure
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/admin/draft-header/preview.blade.php';
function stopFF(string $reason):never{fwrite(STDERR,"STOP BEZ ZMIAN: ".$reason."\n");exit(2);}
$old=@file_get_contents($file);
if(!is_string($old)||!is_writable($file)||strlen($old)<20000)stopFF('Nie odczytano poprawnie aktualnego edytora.');
$marker='FF_EDITOR_PUBLIC_HERO_STAGE_PARITY_20261023';
if(str_contains($old,$marker)){echo "JUZ ZAINSTALOWANE — bez zmian.\n";exit(0);}
$checks=[
    '// FF_HERO_MENU_POINTER_FIX_20261022',
    'function apply(el,p)',
    'const stored=@json($draftPositions)',
    'document.getElementById(\'save-layout\').onclick=async()=>',
    '<div class="hero-copy">',
    '</style></head>',
];
foreach($checks as $s){if(substr_count($old,$s)!==1)stopFF('Nieoczekiwana wersja edytora: '.substr($s,0,55));}
if(str_contains($old,'FF_PREVIEW_DESCRIPTION_PARITY_20261023'))stopFF('Nadal aktywna poprzednia poprawka 850 px.');
$css=<<<'CSS'
/* FF_EDITOR_PUBLIC_HERO_STAGE_PARITY_20261023
   Match the public banner's scene-coordinate baseline. No public CSS
   changes; do not alter .header-actions / menu alignment or its grips.
   The element's existing inline drag geometry is applied on top. */
#viewport-stage:not(.inner-page) .scene > .hero-copy{
  position:absolute;
  left:4%;
  top:177px;
  margin:0;
  max-width:820px;
  z-index:2;
}
#viewport-stage:not(.inner-page) .hero-copy h1{
  font-family:Georgia,serif;
  font-size:83px;
  line-height:1.04;
  letter-spacing:-.04em;
  margin:0 0 18px;
  white-space:pre-line;
}
#viewport-stage:not(.inner-page) .hero-copy p{
  font-family:Arial,sans-serif;
  font-size:16px;
  line-height:1.65;
  max-width:560px;
  margin:0 0 8px;
}
#viewport-stage:not(.inner-page) .hero-copy .eyebrow{
  font:11px/1.4 Arial,sans-serif;
  letter-spacing:.38em;
  margin:0 0 20px;
}
CSS;
$updated=str_replace('</style></head>',$css."\n</style></head>",$old);
if($updated===$old)stopFF('Zmiana CSS nie powiodla sie.');
// Verify before saving that the existing special menu pointer handler has
// survived byte-for-byte and that no public file needs touching.
$menuStart=strpos($old,'// FF_HERO_MENU_POINTER_FIX_20261022');
$menuOld=substr($old,$menuStart);
$menuNew=substr($updated,strpos($updated,'// FF_HERO_MENU_POINTER_FIX_20261022'));
if($menuOld!==$menuNew)stopFF('Zmiana objela obsluge menu; przerwano.');
$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))stopFF('Nie mozna zalozyc katalogu kopii.');
$backup=$backupDir.'/header-editor-before-stage-parity-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)stopFF('Nie mozna zapisac kopii.');
$tmp=$file.'.parity-'.getmypid();
if(file_put_contents($tmp,$updated,LOCK_EX)===false)stopFF('Nie mozna zapisac pliku tymczasowego.');
@chmod($tmp,fileperms($file)&0777);
if(!rename($tmp,$file))stopFF('Nie mozna podmienic edytora.');
$lines=[];$status=1;
exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$lines,$status);
if($status!==0){
 @copy($backup,$file);
 exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');
 stopFF('Kompilacja nieudana; przywrocono oryginal. '.implode(' | ',$lines));
}
echo "OK: ujednolicono punkt odniesienia i pozycjonowanie bloku tekstowego w EDYTORZE.\n";
echo "OK: zachowano obsluge bialej kapsuly menu (FF_HERO_MENU_POINTER_FIX_20261022).\n";
echo "OK: zaden publiczny szablon ani dane nie zostaly zmienione.\n";
echo "KOPIA: ".$backup."\n";
echo "UWAGA: Jest to pierwszy etap wizualnej zgodnosci. Po porownaniu trzeba zweryfikowac zachowanie zapisu oraz nastepne roznice.\n";
