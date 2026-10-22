<?php
/** FoodFoto 2026-10-22: center fixed 1400px desktop canvas, no inner changes. */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$files=['resources/views/welcome.blade.php','resources/views/pages/show.blade.php'];
function halt(string $s):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: ".$s."\n");exit(2);}
$old=[];$new=[];$marker='FF_DESKTOP_CANVAS_CENTER_20261022';
foreach($files as $p){
  $content=@file_get_contents($root.'/'.$p);
  if(!is_string($content)||strlen($content)<10000||!is_writable($root.'/'.$p))halt('Brak szablonu '.$p);
  if(str_contains($content,$marker))halt('Poprawka juz obecna. Niczego nie nadpisano.');
  if(!str_contains($content,'FF_FIXED_1400_COMPOSITION_20261022'))halt('Brak zainstalowanego stalego ukladu w '.$p);
  if(!str_contains($content,'width:1400px!important;margin:0!important'))halt('Nie rozpoznano reguly wyjsciowej w '.$p);
  $old[$p]=$content;
  $needle='body {width:1400px!important;margin:0!important}';
  $replacement='body {width:1400px!important;margin:0 auto!important} /* '.$marker.' */';
  if(substr_count($content,$needle)!==1)halt('Niejednoznaczna regula body w '.$p);
  $new[$p]=str_replace($needle,$replacement,$content);
}
$folder=$root.'/storage/app/backups/center-desktop-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($folder,0700,true))halt('Nie mozna utworzyc kopii.');
foreach($old as $p=>$value){
 $dest=$folder.'/'.$p;
 if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))halt('Nie mozna utworzyc katalogu kopii.');
 if(file_put_contents($dest,$value,LOCK_EX)===false)halt('Blad kopii '.$p);
}
echo "KOPIA: ".$folder."\n";
$written=[];
try{
 foreach($new as $p=>$value){
  $target=$root.'/'.$p;$tmp=$target.'.center-'.getmypid();
  if(file_put_contents($tmp,$value,LOCK_EX)===false)throw new RuntimeException('Zapis '.$p);
  @chmod($tmp,fileperms($target)&0777);
  if(!rename($tmp,$target))throw new RuntimeException('Podmiana '.$p);
  $written[]=$p;
  if(@file_get_contents($target)!==$value)throw new RuntimeException('Kontrola '.$p);
 }
}catch(Throwable $e){
 foreach($written as $p)@copy($folder.'/'.$p,$root.'/'.$p);
 fwrite(STDERR,"BLAD. Przywrocono kopie: ".$e->getMessage()."\n");exit(3);
}
$cache=$root.'/storage/framework/views';
if(is_dir($cache))foreach(glob($cache.'/*.php')?:[] as $p)if(is_file($p))@unlink($p);
echo "SUKCES: stala kompozycja 1400px jest wycentrowana w szerszym oknie.\n";
echo "W wezszych oknach uklad pozostaje staly, z przewijaniem poziomym.\n";
echo "Telefon: dotychczasowe pomniejszanie calej kompozycji bez zmian.\n";
echo "Bez zmian: naglowek, menu, builder, baza, zdjecia.\n";
