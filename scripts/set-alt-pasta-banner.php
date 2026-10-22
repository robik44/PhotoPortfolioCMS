<?php
/** FoodFoto: change ALT only for one verified library photo, preserving all other fields. */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$filename='rqD8sSPwoPnqNNHQIbpAcUKgW0bMw5oZueY8JPO8-web.webp';
$alt='Makaron w kremowym sosie z ziołami i tartym serem, podany na ceramicznym talerzu';
$photos=\App\Models\Photo::query()->where('webp',$filename)->orWhere('webp','photos/'.$filename)->orWhere('filename',$filename)->orWhere('filename','photos/'.$filename)->get();
function failAlt(string $s):never{fwrite(STDERR,"STOP BEZ ZMIAN: $s\n");exit(2);}
if($photos->count()!==1)failAlt('Liczba pasujacych rekordow: '.$photos->count().'. Wymagana dokladnie jedna fotografia.');
$p=$photos->first();
if(!is_file($root.'/storage/app/public/photos/'.$filename))failAlt('Nie znaleziono wskazanego pliku zdjecia.');
$old=(string)($p->alt??'');
if($old===$alt){echo "JUZ USTAWIONY: ALT fotografii #{$p->id}.\n";exit(0);}
if(trim($old)!=='')failAlt('Fotografia ma juz niepusty ALT; nie nadpisuje: '.$old);
$dir=$root.'/storage/app/backups';if(!is_dir($dir)&&!mkdir($dir,0700,true))failAlt('Nie mozna utworzyc katalogu kopii.');
$back=$dir.'/photo-alt-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$data=['photo_id'=>$p->id,'filename'=>$p->filename,'webp'=>$p->webp,'alt_before'=>$p->alt,'alt_after'=>$alt,'saved_at'=>date(DATE_ATOM)];
if(file_put_contents($back,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),LOCK_EX)===false)failAlt('Nie udalo sie utworzyc kopii.');
$p->alt=$alt;
if(!$p->save())failAlt('Blad zapisu ALT.');
$p->refresh();
if($p->alt!==$alt)failAlt('Zapis nie zostal potwierdzony.');
echo "SUKCES: zapisano ALT fotografii ID {$p->id}.\nALT: {$p->alt}\n";
echo "KOPIA POPRZEDNIEJ WARTOSCI: $back\n";
echo "Nie zmieniono tytulu, pliku, opisu, obrazow, CSS ani buildera.\n";
