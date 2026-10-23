<?php
/** FoodFoto: narrowly scoped copy correction on /o-mnie only. */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
function stopFF(string $s):never {fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
$page=\App\Models\Page::query()->where('slug','o-mnie')->first();
if(!$page)stopFF('Nie znaleziono podstrony o-mnie.');
$builder=$page->builder;
$replacements=[
 'Jako fotograf i foodstylista'=>'Jako fotografka i food stylistka',
 'jako fotograf i foodstylista'=>'jako fotografka i food stylistka',
 'Pracowałem'=>'Pracowałam',
 'pracowałem'=>'pracowałam',
];
$allChanges=[];
function patchFF(mixed $value,array $pairs,string $path,array &$changes):mixed {
 if(is_string($value)){
  foreach($pairs as $from=>$to){
   $cnt=substr_count($value,$from);
   if($cnt){$value=str_replace($from,$to,$value);$changes[]="$path: $from -> $to ($cnt)";}
  }
  return $value;
 }
 if(is_array($value)){foreach($value as $k=>$v)$value[$k]=patchFF($v,$pairs,$path.'.'.$k,$changes);}
 return $value;
}
$beforePage=(string)($page->content??'');
$afterPage=patchFF($beforePage,$replacements,'pages.content',$allChanges);
$beforeBuilder=$builder?->content;
$afterBuilder=is_array($beforeBuilder)?patchFF($beforeBuilder,$replacements,'page_builders.content',$allChanges):$beforeBuilder;
echo "PODSTRONA: /o-mnie, page ID ".$page->id."\n";
if(!$allChanges){
 echo "NIE WPROWADZONO ZMIAN: wskazane zwroty nie wystepuja w danych podstrony.\n";
 echo "Moze to oznaczac, ze tekst zostal juz poprawiony albo jest zapisany w innej formie.\n";
 exit(0);
}
foreach($allChanges as $line)echo "POPRAWKA: $line\n";
$dir=$root.'/storage/app/backups';
if(!is_dir($dir)&&!mkdir($dir,0700,true))stopFF('Nie ma katalogu kopii.');
$backup=$dir.'/about-text-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$save=['page_id'=>$page->id,'page_content_before'=>$page->content,'builder_id'=>$builder?->id,'builder_content_before'=>$beforeBuilder,'changes'=>$allChanges];
if(file_put_contents($backup,json_encode($save,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_INVALID_UTF8_SUBSTITUTE),LOCK_EX)===false)stopFF('Nie udalo sie zapisac kopii.');
\Illuminate\Support\Facades\DB::transaction(function()use($page,$builder,$beforePage,$afterPage,$beforeBuilder,$afterBuilder){
 if($beforePage!==$afterPage){$page->content=$afterPage;$page->save();}
 if($builder && $beforeBuilder!==$afterBuilder){$builder->content=$afterBuilder;$builder->save();}
});
echo "SUKCES: poprawiono tylko wymienione formy w tekstach strony O mnie.\n";
echo "KOPIA: $backup\n";
echo "Nie zmieniono CSS, rozmiarow, pozycji, zdjec ani innych stron.\n";
