<?php
/** SEOHOST: bezpieczna poprawka dodatkowego H1 w globalnym naglowku.
 *  PHP 8.3, bez Composer i Artisan. Przed zmiana: kontrola 4 stron.
 *  Po zmianie: kontrola 15 stron, backup i zachowane style.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$view=$root.'/resources/views/components/site-header.blade.php';
$mark='FOODFOTO_SHARED_HEADER_H1_V1';
function abortFix(string $message, string $fragment=''): never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: ".$message."\n");
    if($fragment!=='') fwrite(STDERR,"Fragment: ".substr(preg_replace('/\s+/u',' ',$fragment)??$fragment,0,1000)."\n");
    exit(2);
}
function auditH1(string $url): ?array {
    $cmd='curl -fsSL --connect-timeout 8 --max-time 20 -A '.escapeshellarg('FoodFoto-H1-Audit/1.0').' '.escapeshellarg($url).' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<600) return null;
    libxml_use_internal_errors(true);
    $doc=new DOMDocument();
    $ok=@$doc->loadHTML('<?xml encoding="utf-8"?>'.$html);
    libxml_clear_errors();
    if(!$ok)return null;
    $xp=new DOMXPath($doc);
    $list=[];
    foreach($xp->query('//h1') as $h){
        $list[]=trim(preg_replace('/\s+/u',' ',$h->textContent)??'');
    }
    $header=[];
    foreach($xp->query('//header[contains(concat(" ",normalize-space(@class)," ")," site-header ")]//h1') as $h) {
        $header[]=trim(preg_replace('/\s+/u',' ',$h->textContent)??'');
    }
    return ['all'=>$list,'header'=>$header];
}
if(!class_exists('DOMDocument')) abortFix('Brak rozszerzenia PHP DOM.');
if(!is_file($view)) abortFix('Nie znaleziono site-header.blade.php.');
$s=file_get_contents($view);
if(!is_string($s)||$s==='')abortFix('Brak dostepu do szablonu naglowka.');
if(str_contains($s,$mark)){echo "Poprawka juz zainstalowana. Bez zmian.\n";exit(0);}
$probe=[
 'glowna'=>'https://www.foodfoto.pl/',
 'o-mnie'=>'https://www.foodfoto.pl/o-mnie',
 'kontakt'=>'https://www.foodfoto.pl/kontakt',
 'galeria'=>'https://www.foodfoto.pl/portfolio/zywnosc'
];
foreach($probe as $name=>$url){
    $a=auditH1($url);
    if($a===null)abortFix('Nie moge odczytac strony '.$name.' — wymagana weryfikacja.');
    echo "PRZED $name — H1: ".count($a['all'])."; w naglowku: ".count($a['header'])."; ".implode(' | ',$a['all'])."\n";
    if($name==='glowna'){
        if(count($a['all'])!==1||count($a['header'])!==1)abortFix('H1 strony glownej jest inny niz w audycie.');
    }elseif(count($a['all'])!==2||count($a['header'])!==1){
        abortFix('Dodatkowy H1 nie znajduje sie tam, gdzie oczekiwano: '.$name);
    }
}
$opens=[];$closes=[];
$n=preg_match_all('~<h1(?=[\s>])[^>]*>~i',$s,$opens);
$m=preg_match_all('~</h1\s*>~i',$s,$closes);
if($n!==1||$m!==1){
    $lines=preg_split('/\R/',$s);
    $notes=[];
    foreach($lines as $i=>$line)if(preg_match('~h1|logo_subtitle|logo-text~i',$line))$notes[]=($i+1).': '.trim($line);
    abortFix('W produkcyjnym naglowku nie ma dokladnie jednego zwyklego H1. Niczego nie zmieniam.',implode("\n",array_slice($notes,-12)));
}
$open=$opens[0][0];$close=$closes[0][0];
// Zakaz zgadywania wygladu: znacznik musi juz miec jawna typografie.
if(!preg_match('~\bstyle\s*=\s*["\x27][^"\x27]*font-size~i',$open)||
   !str_contains($open,'font-weight')){
    abortFix('H1 nie ma jawnego font-size/font-weight — wymagana reczna kontrola wygladu.',$open);
}
if(preg_match('~\bh1\s*\{|(?:\.|#|[\w-]+\s+)h1\b~i',$s)){
    abortFix('Szablon zawiera style CSS zalezne od znacznika h1 — wymagana osobna poprawka CSS.',$open);
}
$openDiv=preg_replace('~^<h1\b~i','<div',$open,1);
if(!is_string($openDiv)||$openDiv===$open)abortFix('Nie udalo sie utworzyc wersji DIV.');
// Zachowaj oryginalny tag i styl na home, a na podstronach zmien tylko semantyke.
$prefix="{{-- ".$mark." --}}\n";
$begin="@if(request()->getPathInfo() === '/')\n".$open."\n@else\n".$openDiv."\n@endif";
$finish="@if(request()->getPathInfo() === '/')\n".$close."\n@else\n</div>\n@endif";
$changed=str_replace($open,$prefix.$begin,$s);
$changed=str_replace($close,$finish,$changed);
if(substr_count($changed,$mark)!==1||substr_count($changed,$finish)!==1){
    abortFix('Nie udalo sie jednoznacznie przygotowac podmiany.');
}
$dir=$root.'/storage/app/backups/h1-header-'.date('Ymd-His');
if(!is_dir($dir)&&!mkdir($dir,0700,true))abortFix('Nie mozna przygotowac katalogu kopii.');
$backup=$dir.'/site-header.blade.php';
if(!copy($view,$backup))abortFix('Nie mozna utworzyc kopii zapasowej.');
$tmp=$view.'.new-'.getmypid();
if(file_put_contents($tmp,$changed,LOCK_EX)===false||!rename($tmp,$view)){
    @unlink($tmp);abortFix('Blad zapisu szablonu.');
}
$compiled=$root.'/storage/framework/views';
if(is_dir($compiled))foreach(glob($compiled.'/*.php')?:[] as $f)@unlink($f);
echo "ZMIENIONO: h1 na stronie glownej, div na podstronach. Kopia: $backup\n";
$paths=[
 '/','/o-mnie','/kontakt','/strona/klienci','/polityka-prywatnosci',
 '/portfolio/zywnosc','/portfolio/stylizacja','/portfolio/opakowania','/portfolio/czasopisma',
 '/strona/fotografia-zywnosci','/strona/fotografia-kulinarna','/strona/food-styling',
 '/strona/fotografia-produktowa-zywnosci','/strona/zdjecia-na-opakowania',
 '/strona/fotografia-reklamowa-zywnosci'
];
$passed=0;
foreach($paths as $p){
    $a=auditH1('https://www.foodfoto.pl'.$p.'?audit_h1='.time());
    if($a===null){echo "BRAK ODCZYTU $p\n";continue;}
    $good=count($a['all'])===1&&count($a['header'])===($p==='/'?1:0);
    if($good)$passed++;
    echo ($good?'OK':'UWAGA')." $p H1=".count($a['all'])." w-naglowku=".count($a['header'])." ".implode(' | ',$a['all'])."\n";
}
echo "WERYFIKACJA: $passed/".count($paths)." stron. Jesli mniej niz 15, sprawdz cache lub przyslij wynik.\n";
echo "GOTOWE. Sprawdz wizualnie naglowek na komputerze i telefonie.\n";
