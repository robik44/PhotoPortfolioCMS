<?php
/**
 * FoodFoto: synchronize one saved hero description from header settings
 * to the separate public home_hero_draft hero-text block.
 * Does not alter geometry, CSS, image, other content, or header flags.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);require $root.'/vendor/autoload.php';
$app=require $root.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
function ffStop(string $why):never { fwrite(STDERR,"STOP BEZ ZMIAN: $why\n"); exit(2);}
$expected='Łączę wieloletnie doświadczenie w fotografii kulinarnej z profesjonalną stylizacją żywności, tworząc apetyczne obrazy dla marek i producentów żywności.';
$oldDefault='Tworzę apetyczne obrazy, które budują marki, opowiadają historie i inspirują do dobrego jedzenia.';
$settings=(string)(\App\Models\SiteSetting::where('key','draft_header_hero_description')->value('value')??'');
$draft=\App\Models\PageBuilder::query()->whereNull('page_id')->where('type','home_hero_draft')->first();
if(!$draft)ffStop('Brak osobnego projektu home_hero_draft.');
$content=$draft->content??[];
$sections=$content['sections']??null;
if(!is_array($sections))ffStop('Nieprawidlowa struktura sekcji draftu.');
$indexes=[];
foreach($sections as $i=>$section)if(is_array($section)&&($section['id']??null)==='hero-text')$indexes[]=$i;
if(count($indexes)!==1)ffStop('Nie znaleziono dokladnie jednego bloku hero-text.');
$i=$indexes[0];$current=$sections[$i]['content']??null;
echo "TEKST W EDYTORZE USTAWIEN NAGLOWKA: ".($settings?:'[PUSTY]')."\n";
echo "TEKST W PUBLICZNYM BLOKU HERO: ".(is_string($current)?$current:'[INNY TYP]')."\n";
if($current===$expected){echo "JUZ POPRAWNE: publiczny blok zawiera wlasciwy tekst.\n";exit(0);}
if($settings!==$expected)ffStop('Tekst w ustawieniach nie zgadza sie dokladnie z uzgodniona wersja; nie zgaduje i nie nadpisuje.');
if(!is_string($current)||($current!==$oldDefault&&trim($current)!==''))
 ffStop('W publicznym hero jest niestandardowy tekst; wymaga swiadomej decyzji, nic nie zmieniono.');
$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))ffStop('Brak katalogu kopii.');
$backup=$backupDir.'/hero-description-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$snapshot=['page_builder_id'=>$draft->id,'type'=>$draft->type,'original_content'=>$content,'saved_setting'=>$settings,'at'=>date(DATE_ATOM)];
if(file_put_contents($backup,json_encode($snapshot,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_INVALID_UTF8_SUBSTITUTE),LOCK_EX)===false)
 ffStop('Nie utworzono kopii.');
$content['sections'][$i]['content']=$expected;
\Illuminate\Support\Facades\DB::transaction(function()use($draft,$content){
 $fresh=\App\Models\PageBuilder::query()->lockForUpdate()->findOrFail($draft->id);
 $freshContent=$fresh->content??[];
 $blocks=$freshContent['sections']??[];
 $matching=array_values(array_filter($blocks,fn($b)=>is_array($b)&&($b['id']??null)==='hero-text'));
 if(count($matching)!==1||($matching[0]['content']??null)!=='Tworzę apetyczne obrazy, które budują marki, opowiadają historie i inspirują do dobrego jedzenia.')
  throw new \RuntimeException('W międzyczasie zmieniono zapis hero; transakcja przerwana.');
 $fresh->content=$content;$fresh->save();
});
$check=\App\Models\PageBuilder::find($draft->id)?->content['sections'][$i]['content']??null;
if($check!==$expected)ffStop('Nie potwierdzono zapisu; zachowaj kopie do przywrocenia.');
echo "SUKCES: zsynchronizowano tylko tresc hero-text na stronie glownej.\n";
echo "KOPIA: $backup\n";
echo "Pozycje, CSS, fonty, zdjecia, skalowanie i pozostale elementy bez zmian.\n";
