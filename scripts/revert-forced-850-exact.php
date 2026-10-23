<?php
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/admin/draft-header/preview.blade.php';
function stop(string $s):never{fwrite(STDERR,"STOP BEZ ZMIAN: $s\n");exit(2);}
$old=@file_get_contents($file);
if(!is_string($old)||!is_writable($file))stop('Brak dostepu do pliku podgladu.');
$start='// FF_PREVIEW_DESCRIPTION_PARITY_20261023';
$next='// FF_HERO_MENU_POINTER_FIX_20261022';
if(!str_contains($old,$start)){echo "Wymuszenie szerokosci juz nie wystepuje, nie zmieniono pliku.\n";}
else {
 if(substr_count($old,$start)!==1||substr_count($old,$next)!==1)stop('Znaczniki sa niejednoznaczne.');
 $a=strpos($old,$start);$b=strpos($old,$next,$a);
 $fragment=$b!==false?substr($old,$a,$b-$a):'';
 if(!$fragment||strlen($fragment)>1800||!str_contains($fragment,"ffDesc.style.width = '850px'")||!str_contains($fragment,"ffDesc.dataset.publicPreviewWidth"))stop('Fragment nie odpowiada diagnozie.');
 $changed=substr($old,0,$a).substr($old,$b);
 $dir=$root.'/storage/app/backups';
 if(!is_dir($dir)&&!mkdir($dir,0700,true))stop('Brak katalogu kopii.');
 $backup=$dir.'/preview-850-revert-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
 if(file_put_contents($backup,$old,LOCK_EX)===false)stop('Nie zapisano kopii.');
 $tmp=$file.'.rollback-'.getmypid();
 if(file_put_contents($tmp,$changed,LOCK_EX)===false)stop('Nie zapisano pliku tymczasowego.');
 @chmod($tmp,fileperms($file)&0777);
 if(!rename($tmp,$file))stop('Nie wykonano podmiany.');
 $out=[];$status=1;
 exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$out,$status);
 if($status!==0){@copy($backup,$file);exec('cd '.escapeshellarg($root).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');stop('Kompilacja nieudana, odtworzono poprzedni plik: '.implode(' | ',$out));}
 echo "SUKCES: wycofano wylacznie wymuszenie 850 px z podgladu.\n";
 echo "Zachowano FF_HERO_MENU_POINTER_FIX_20261022 i cala pozostala logike buildera.\n";
 echo "KOPIA: $backup\n";
 echo "Kompilacja Blade: OK. Publiczne szablony i dane niezmienione.\n";
}
chdir($root);require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$setting=\App\Models\SiteSetting::where('key','draft_header_builder_positions')->value('value');
$data=json_decode((string)$setting,true);
echo "ZAPISANA GEOMETRIA OPISU:\n".json_encode(is_array($data)?($data['description']??null):null,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
echo "ZAPISANE BLOKI: ".implode(', ',array_keys(is_array($data)?$data:[]))."\n";
