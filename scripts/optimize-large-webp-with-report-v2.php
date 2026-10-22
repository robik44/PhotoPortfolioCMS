<?php
/** FoodFoto: safe WebP optimization + read-only ALT and dimensions report.
 * The source and all builder data remain unchanged. On failed checks, no view changes.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$stem='D1LwaysS6MgehZRTYFQaCBRouAtYt1IcVkpZ8xK0-web';
$source=$root.'/storage/app/public/photos/'.$stem.'.webp';
$optimized=$root.'/storage/app/public/photos/'.$stem.'-optimized.webp';
$view=$root.'/resources/views/components/public-builder-canvas.blade.php';
$php='/opt/alt/php84/usr/bin/php';
function failFF(string $why):never {fwrite(STDERR,"PRZERWANO: $why\n");exit(2);}
if(!is_file($source)||!is_readable($source))failFF('Nie odnaleziono obrazu; szablon nietkniety.');
if(!is_file($view)||!is_writable($view))failFF('Nie mozna odczytac szablonu.');
$before=file_get_contents($view);
$marker='FF_LARGE_WEBP_SAFE_20261023';
if(str_contains($before,$marker)){echo "JUZ ZAINSTALOWANE: optymalizacja wskazanego WebP.\n";exit(0);}
$anchor='                    $photoUrl = $photoRecord?->imageUrl() ?: ($element[\'photo_url\'] ?? null);';
if(substr_count($before,$anchor)!==1)failFF('Szablon strony jest inny niz oczekiwano; nie modyfikuje.');
if(!function_exists('imagecreatefromwebp')||!function_exists('imagewebp'))failFF('Brak obslugi WebP przez PHP GD; nie modyfikuje.');
$info=getimagesize($source);
if(!$info||$info[2]!==IMAGETYPE_WEBP||$info[0]<200||$info[1]<200||$info[0]*$info[1]>32000000)failFF('Nietypowe lub uszkodzone zrodlo.');
$origBytes=filesize($source);
$im=@imagecreatefromwebp($source);
if(!$im)failFF('Nie mozna zdekodowac oryginalnego WebP.');
imagepalettetotruecolor($im);
imagealphablending($im,false);imagesavealpha($im,true);
$tmp=$optimized.'.tmp'.getmypid().'.webp';
$ok=@imagewebp($im,$tmp,88);imagedestroy($im);
if(!$ok||!is_file($tmp))failFF('Kodowanie WebP nie powiodlo sie.');
$inf2=@getimagesize($tmp);$size2=filesize($tmp);
if(!$inf2||$inf2[2]!==IMAGETYPE_WEBP||$inf2[0]!==$info[0]||$inf2[1]!==$info[1]||$size2<10000||$size2>=$origBytes*0.8){
 @unlink($tmp);
 echo "BRAK ZMIANY: ponowne kodowanie nie zmniejszylo pliku o co najmniej 20%.\n";
 echo "ORYGINAL: ".round($origBytes/1024)." KB | Proba: ".round($size2/1024)." KB\n";
 exit(0);
}
$dir=$root.'/storage/app/backups';
if(!is_dir($dir)&&!mkdir($dir,0700,true)){@unlink($tmp);failFF('Nie udalo sie utworzyc katalogu kopii.');}
$backup=$dir.'/large-webp-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$before,LOCK_EX)===false){@unlink($tmp);failFF('Nie wykonano kopii.');}
if(!rename($tmp,$optimized))failFF('Nie zapisano WebP.');
@chmod($optimized,0644);
$addition=<<<'PHPBLADE'
                    // FF_LARGE_WEBP_SAFE_20261023: source filename match only; original retained.
                    if ($photoUrl && str_ends_with(parse_url((string) $photoUrl, PHP_URL_PATH) ?: '', '/photos/D1LwaysS6MgehZRTYFQaCBRouAtYt1IcVkpZ8xK0-web.webp')
                        && is_file(storage_path('app/public/photos/D1LwaysS6MgehZRTYFQaCBRouAtYt1IcVkpZ8xK0-web-optimized.webp'))) {
                        $photoUrl = asset('storage/photos/D1LwaysS6MgehZRTYFQaCBRouAtYt1IcVkpZ8xK0-web-optimized.webp');
                    }
PHPBLADE;
$new=str_replace($anchor,$anchor."\n".$addition,$before);
$tem=$view.'.webp-'.getmypid();
if(file_put_contents($tem,$new,LOCK_EX)===false)failFF('Nie zapisano szablonu.');
@chmod($tem,fileperms($view)&0777);
if(!rename($tem,$view))failFF('Nie podmieniono szablonu.');
$lines=[];$ret=1;
exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:cache 2>&1',$lines,$ret);
if($ret!==0){
 @copy($backup,$view);
 exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:clear 2>&1');
 failFF('Kompilacja nieudana. Przywrocono kopie. '.implode(' | ',$lines));
}
echo "SUKCES: nowy WebP ".round($size2/1024)." KB zamiast ".round($origBytes/1024)." KB, oszczednosc ".round(100*(1-$size2/$origBytes),1)."%\n";
echo "Wymiary: ".$info[0]."x".$info[1]." px; oryginal zachowany.\n";
echo "KOPIA SZABLONU: $backup\n";
echo "Kompilacja Blade: OK. Nie zmieniono bazy, pozycji zdjec ani wygladu buildera.\n";
echo "\n=== DIAGNOSTYKA ALT I WYMIAROW (odczyt strony glownej) ===\n";
$ch=curl_init('https://www.foodfoto.pl/?img_audit='.time());
curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>20,CURLOPT_USERAGENT=>'FoodFotoDiagnostic/1.0']);
$html=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
if($status!==200||!is_string($html)){echo "HTTP $status. Nie mozna zdiagnozowac HTML.\n";exit(0);}
$dom=new DOMDocument();$prev=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);libxml_clear_errors();libxml_use_internal_errors($prev);
$missing=0;$empty=0;
foreach($dom->getElementsByTagName('img') as $img){
 $src=$img->getAttribute('src');if(!$src)continue;
 $alt=trim($img->getAttribute('alt'));
 if($alt===''&&str_contains($src,'/storage/photos/')){
  $empty++;echo "ALT do oceny: $src | rodzic: ".$img->parentNode?->nodeName." | klasa: ".$img->getAttribute('class')."\n";
 }
 if(!$img->hasAttribute('width')||!$img->hasAttribute('height')){
  $missing++;echo "BRAK wymiarow: $src | klasa: ".$img->getAttribute('class')."\n";
 }
}
echo "LACZNIE: obrazy biblioteki z pustym ALT: $empty; img bez dwoch atrybutow rozmiaru: $missing.\n";
echo "Uwaga: brak width/height sam nie przesadza o CLS — builder moze rezerwowac miejsce CSS.\n";
