<?php
/**
 * FoodFoto | H1 globalnego banera | instalator produkcyjny v3
 * Tylko konkretny plik: resources/views/admin/components/draft-shared-header.blade.php
 * PHP 8.3, bez Laravel/Composer/Artisan/git pull/zmian w bazie danych.
 */
declare(strict_types=1);

$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
$relative='resources/views/admin/components/draft-shared-header.blade.php';
$target="$root/$relative";
$marker='FF_SEO_SHARED_HERO_H1_V3';
$paths=[
    '/', '/o-mnie', '/kontakt', '/strona/klienci', '/polityka-prywatnosci',
    '/portfolio/zywnosc', '/portfolio/stylizacja',
    '/portfolio/opakowania', '/portfolio/czasopisma',
    '/strona/fotografia-zywnosci', '/strona/fotografia-kulinarna',
    '/strona/food-styling', '/strona/fotografia-produktowa-zywnosci',
    '/strona/zdjecia-na-opakowania', '/strona/fotografia-reklamowa-zywnosci'
];

function stopH1(string $why): never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $why\n");
    exit(2);
}
function inspectPublic(string $path): ?array {
    $url='https://www.foodfoto.pl'.($path==='/'?'/':$path);
    $url.=(str_contains($url,'?')?'&':'?').'h1_audit='.time();
    $cmd='curl -fsSL --retry 1 --retry-delay 1 --connect-timeout 8 --max-time 25'
       .' -A '.escapeshellarg('FoodFoto-H1-TargetedAudit/1.0')
       .' '.escapeshellarg($url).' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<600)return null;
    libxml_use_internal_errors(true);
    $doc=new DOMDocument();
    $ok=@$doc->loadHTML('<?xml encoding="UTF-8"?>'.$html);
    libxml_clear_errors();
    if(!$ok)return null;
    $xp=new DOMXPath($doc);
    $all=[];$hero=[];
    foreach($xp->query('//h1') as $node)
        $all[]=trim(preg_replace('/\s+/u',' ',$node->textContent)??'');
    foreach($xp->query('//h1[ancestor::*[contains(concat(" ",normalize-space(@class)," ")," ff-shared-hero ")]]') as $node)
        $hero[]=trim(preg_replace('/\s+/u',' ',$node->textContent)??'');
    return ['all'=>$all,'hero'=>$hero];
}
function resultOK(string $path, ?array $result): bool {
    if($result===null||count($result['all'])!==1)return false;
    return count($result['hero'])===($path==='/'?1:0);
}
function flushCompiledViews(string $root):void {
    $dir="$root/storage/framework/views";
    if(!is_dir($dir))return;
    foreach(glob("$dir/*.php")?:[] as $file) {
        if(is_file($file))@unlink($file);
    }
}
if(!class_exists('DOMDocument'))stopH1('PHP DOM jest niedostepny.');
if(!is_file($target)||!is_readable($target)||!is_writable($target))
    stopH1("Brakuje pliku lub dostepu do $relative.");
$source=file_get_contents($target);
if(!is_string($source)||$source==='')stopH1('Nie mozna odczytac szablonu.');
if(str_contains($source,$marker)){
    echo "POPRAWKA JUZ ISTNIEJE. Kontrola H1 na 15 stronach:\n";
    $passed=0;
    foreach($paths as $path){
        $r=inspectPublic($path);$ok=resultOK($path,$r);
        if($ok)$passed++;
        echo ($ok?'OK':'UWAGA')." $path: ".($r?implode(' | ',$r['all']):'brak odpowiedzi')."\n";
    }
    echo "WYNIK: $passed/15.\n";
    exit($passed===15?0:2);
}

// Check exact, observed production source. Admin builder is a separate file:
// its H1 must remain untouched so the JavaScript preview continues to work.
if(!str_contains($source,'class="ff-shared-hero"')
    || !str_contains($source,'$isPublicDraftHeader')
    || !str_contains($source,'$styleFor($blocks[\'headline\']')
    || !str_contains($source,'$pageBannerDraft[\'title\']')) {
    stopH1('Kod naglowka odbiega od otrzymanej diagnostyki.');
}
$found=preg_match_all('~<h1\b([^>]*)>(.*?)</h1\s*>~is',$source,$matches,PREG_SET_ORDER);
if($found!==1){
    stopH1("Oczekiwano jednego H1 w $relative; znaleziono ".(int)$found.'.');
}
$original=$matches[0][0];
$attrs=$matches[0][1];
$inner=$matches[0][2];
if(!str_contains($original,'$styleFor($blocks[\'headline\']')
    || !str_contains($original,'$pageBannerDraft[\'title\']')
    || preg_match('/\bclass\s*=/i',$attrs)) {
    stopH1('Nie rozpoznano pojedynczego H1 lub ma inne atrybuty.');
}
if(substr_count($source,$original)!==1)stopH1('H1 nie jest jednoznaczny.');

// Three CSS declarations were observed in the exact same production Blade:
// two base selectors and one @media selector. All must be adapted together.
$cssSelector='.ff-shared-hero h1{';
$cssCount=substr_count($source,$cssSelector);
if($cssCount!==3)
    stopH1("Nieoczekiwana liczba reguł CSS '$cssSelector': $cssCount (oczekiwano 3).");

// Do not change the editor preview's H1; only public subpages become DIV.
// Preserve all text and inline styling exactly as in the original tag.
$opening='<h1'.$attrs.'>';
$divOpening='<div class="ff-seo-hero-title"'.$attrs.'>';
$replacement="@if(\$isPublicDraftHeader && request()->getPathInfo() !== '/')\n"
    .$divOpening.$inner."</div>\n"
    ."@else\n".$opening.$inner."</h1>\n@endif";
$next=str_replace($original,$replacement,$source);
$next=str_replace($cssSelector,'.ff-shared-hero :is(h1,.ff-seo-hero-title){',$next,$modifiedCss);
$next="{{-- ".$marker." --}}\n".$next;
if($modifiedCss!==3
    || substr_count($next,'class="ff-seo-hero-title"')!==1
    || substr_count($next,'@if($isPublicDraftHeader && request()->getPathInfo()')!==1){
    stopH1('Nie udalo sie bezpiecznie przygotowac zmiany.');
}

// Preflight: full homepage H1, duplicate on two builder pages and gallery.
$before=['/','/o-mnie','/kontakt','/portfolio/zywnosc','/polityka-prywatnosci'];
foreach($before as $path){
    $r=inspectPublic($path);
    if($r===null)stopH1("Nie moge odczytac $path, zadnych zmian.");
    $expected=$path==='/'?1:2;
    echo "PRZED $path: H1=".count($r['all'])."; baner=".count($r['hero'])
        ."; ".implode(' | ',$r['all'])."\n";
    if(count($r['all'])!==$expected||count($r['hero'])!==1)
        stopH1("Nieoczekiwany H1 na $path.");
}
$backupDir="$root/storage/app/backups";
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))
    stopH1('Nie mozna utworzyc folderu kopii.');
$backup=$backupDir.'/shared-hero-h1-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(!copy($target,$backup))stopH1('Nie udalo sie wykonac backupu.');
$tmp=$target.'.new-'.getmypid();
if(file_put_contents($tmp,$next,LOCK_EX)===false){@unlink($tmp);stopH1('Nie zapisano pliku tymczasowego.');}
chmod($tmp,fileperms($target)&0777);
if(!rename($tmp,$target)){@unlink($tmp);stopH1('Nie udalo sie zainstalowac pliku.');}
flushCompiledViews($root);
echo "ZAINSTALOWANO; KOPIA: $backup\n";

// Every failure rolls the single file back. Full page names are compared too:
// the proper subpage H1 (last H1 before update) must not be replaced by hero.
$okTotal=0;$failures=[];
foreach($paths as $path){
    $r=inspectPublic($path);
    $ok=resultOK($path,$r);
    if($ok)$okTotal++;else $failures[]=$path;
    echo ($ok?'OK':'UWAGA')." $path: H1=".($r?count($r['all']):'?')
        ."; baner=".($r?count($r['hero']):'?')
        ."; ".($r?implode(' | ',$r['all']):'BRAK ODCZYTU')."\n";
}
if($failures){
    if(copy($backup,$target)){
        flushCompiledViews($root);
        fwrite(STDERR,"WERYFIKACJA NIEUDANA ($okTotal/15). Przywrocono oryginalny szablon.\n");
    } else fwrite(STDERR,"UWAGA KRYTYCZNA: rollback nieudany. Przywroc z $backup.\n");
    exit(3);
}
echo "SUKCES: 15/15 stron ma po jednym H1. H1 strony glownej zachowany.\n";
echo "Bez zmian w bazie, zdjeciach, tekstach, linkach i podgladzie buildera.\n";
echo "Porownaj teraz wyglad gornego banera w desktopie i na telefonie.\n";
