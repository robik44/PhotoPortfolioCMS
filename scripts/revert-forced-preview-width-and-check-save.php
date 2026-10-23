<?php
/** Restore correct persisted builder geometry. Remove ONLY the temporary preview-width override.
 * Read current saved description geometry after edit. Public website untouched.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$view=$root.'/resources/views/admin/draft-header/preview.blade.php';
function failX(string $s):never{fwrite(STDERR,"STOP: $s\n");exit(2);}
$src=@file_get_contents($view);
if(!is_string($src)||!is_writable($view))failX('Brak dostepu do podgladu.');
$tag='// FF_PREVIEW_DESCRIPTION_PARITY_20261023';
$end='cornerPanel.querySelectorAll(\'[data-corner]\')';
if(substr_count($src,$tag)===1){
 $a=strpos($src,$tag);$b=strpos($src,$end,$a);
 if($b===false||$b-$a>2500||!str_contains(substr($src,$a,$b-$a),'ffDesc.dataset.publicPreviewWidth'))
   failX('Nie rozpoznano fragmentu poprzedniej poprawki. Nic nie zmieniono.');
 $fixed=substr($src,0,$a).substr($src,$b);
 $dir=$root.'/storage/app/backups';
 if(!is_dir($dir)&&!mkdir($dir,0700,true))failX('Brak katalogu kopii.');
 $backup=$dir.'/preview-forced-width-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
 if(file_put_contents($backup,$src,LOCK_EX)===false)failX('Nie mozna wykonac kopii.');
 $tmp=$view.'.undo-'.getmypid();
 if(file_put_contents($tmp,$fixed,LOCK_EX)===false)failX('Brak zapisu tymczasowego.');
 @chmod($tmp,fileperms($view)&0777);
 if(!rename($tmp,$view))failX('Nie mozna zmienic pliku.');
 $lines=[];$code=0;
 exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$lines,$code);
 if($code!==0){
   @copy($backup,$view);
   exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');
   failX('Blade compile failed, restored backup: '.implode(' ',$lines));
 }
 echo "COFNIETO wymuszenie 850px w podgladzie. Backup: $backup\n";
}elseif(!str_contains($src,'FF_PREVIEW_DESCRIPTION_PARITY_20261023')){
 echo "Wymuszenia 850px nie ma w szablonie — nic nie zmieniono.\n";
}else failX('Nieznany wariant poprawki — nie modyfikuje pliku.');
chdir($root);
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$raw=\App\Models\SiteSetting::where('key','draft_header_builder_positions')->value('value');
$p=json_decode((string)$raw,true);
echo "\nAKTUALNIE ZAPISANA GEOMETRIA OPISU (draft_header_builder_positions):\n";
echo json_encode(is_array($p)?($p['description']??null):null,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
echo "INNE ZAPISANE BLOKI: ".implode(', ',array_keys(is_array($p)?$p:[]))."\n";
echo "Publiczna strona i dane pozostaly BEZ ZMIAN.\n";
