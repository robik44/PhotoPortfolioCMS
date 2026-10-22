<?php
/**
 * FoodFoto: remove the duplicate H1 on 14 subpages without changing the hero
 * visually. Surgical, guarded deployment for production-only Blade code.
 * PHP CLI 8.3 compatible; no Laravel bootstrap / git pull / DB writes.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
const MARK='FF_SEO_H1_SHARED_HERO_V2';
function quit(string $why,array $details=[]):never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $why\n");
    foreach($details as $row) fwrite(STDERR,mb_substr($row,0,500)."\n");
    exit(2);
}
function filesIn(string $directory,array $extensions):array {
    if(!is_dir($directory))return [];
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory,FilesystemIterator::SKIP_DOTS));
    $out=[];
    foreach($it as $file) {
        if(!$file->isFile()||$file->getSize()>2000000)continue;
        $name=$file->getFilename();
        foreach($extensions as $extension) {
            if(str_ends_with($name,$extension)){$out[]=$file->getPathname();break;}
        }
    }
    sort($out);return $out;
}
function headings(string $url):?array {
    $cmd='curl -fsSL --connect-timeout 8 --max-time 20 -A '.
        escapeshellarg('FoodFoto-H1-SafeInstaller/1.0').' '.escapeshellarg($url).' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<600)return null;
    libxml_use_internal_errors(true);
    $doc=new DOMDocument();
    $ok=@$doc->loadHTML('<?xml encoding="UTF-8"?>'.$html);
    libxml_clear_errors();
    if(!$ok)return null;
    $xp=new DOMXPath($doc);
    $all=[];$shared=[];
    foreach($xp->query('//h1') as $node)$all[]=trim(preg_replace('/\s+/u',' ',$node->textContent)??'');
    foreach($xp->query('//h1[ancestor::*[contains(concat(" ",normalize-space(@class)," ")," ff-shared-hero ")]]') as $node) {
        $shared[]=trim(preg_replace('/\s+/u',' ',$node->textContent)??'');
    }
    return ['all'=>$all,'shared'=>$shared];
}
if(!class_exists('DOMDocument'))quit('Brak rozszerzenia DOM w PHP CLI.');
$probe=['/','/o-mnie','/kontakt','/portfolio/zywnosc','/polityka-prywatnosci'];
foreach($probe as $path){
    $h=headings('https://www.foodfoto.pl'.$path);
    if($h===null)quit('Brak odczytu publicznej strony '.$path);
    echo "PRZED $path: H1=".count($h['all'])."; ff-shared-hero=".count($h['shared'])."; ".implode(' | ',$h['all'])."\n";
    if(count($h['all'])!==($path==='/'?1:2)||count($h['shared'])!==1)
        quit('Nieoczekiwana struktura naglowkow — niczego nie zmieniam.',[$path]);
}
$blades=filesIn($root.'/resources/views',['.blade.php']);
$markup=[];
foreach($blades as $path){
    $s=file_get_contents($path);
    if(!is_string($s)||!str_contains($s,'ff-shared-hero'))continue;
    $num=preg_match_all('~<h1(?=[\s>])[^>]*>~i',$s);
    if($num){
        $markup[]=['path'=>$path,'content'=>$s,'opens'=>$num,
            'closes'=>preg_match_all('~</h1\s*>~i',$s)];
    }
}
if(count($markup)!==1||$markup[0]['opens']!==1||$markup[0]['closes']!==1){
    $candidates=array_map(fn($v)=>str_replace($root.'/','',$v['path']).' (h1: '.$v['opens'].', /h1: '.$v['closes'].')',$markup);
    if(!$candidates){
        $candidates=['Nie znaleziono statycznego <h1> w pliku z ff-shared-hero.'];
        foreach($blades as $path){
            $s=file_get_contents($path);
            if(is_string($s)&&str_contains($s,'ff-shared-hero'))
                $candidates[]='Znacznik ff-shared-hero: '.str_replace($root.'/','',$path);
        }
    }
    quit('Nie mozna jednoznacznie znalezc zrodla H1 gornego banera.',$candidates);
}
$path=$markup[0]['path'];$source=$markup[0]['content'];
if(str_contains($source,MARK))quit('Poprawka jest juz obecna w szablonie.');
preg_match('~<h1(?=[\s>])[^>]*>~i',$source,$openMatch,PREG_OFFSET_CAPTURE);
preg_match('~</h1\s*>~i',$source,$endMatch,PREG_OFFSET_CAPTURE);
$open=$openMatch[0][0];$openPos=$openMatch[0][1];
$close=$endMatch[0][0];$closePos=$endMatch[0][1];
$heroPos=strripos(substr($source,0,$openPos),'ff-shared-hero');
if($heroPos===false||$openPos-$heroPos>4500||$closePos<$openPos)
    quit('H1 nie jest jednoznacznie powiazany z ff-shared-hero.',[str_replace($root.'/','',$path)]);
if(stripos(substr($source,$heroPos,$openPos-$heroPos),'</div>')!==false)
    echo "UWAGA: ff-shared-hero jest wczesniej w szablonie; weryfikacja za pomoca struktury DOM byla pozytywna.\n";
if(!str_contains($open,'--fs') && !str_contains($open,'style='))
    quit('Nie rozpoznano atrybutow stylu naglowka, odmowa zmiany bez kontroli CSS.',[$open]);

// CSS selectors for the shared headline, copied to class as well as H1.
// This preserves matching rules in media queries, fonts and spacing.
$cssCandidates=array_merge($blades,filesIn($root.'/public/css',['.css']));
$cssSources=[];
$selectorPattern='~((?:\.ff-shared-hero|\.ff-shared-stage|\.ff-public-header|#fve-header-variant-preview)\s*(?:>\s*|\s+))h1\b~i';
foreach($cssCandidates as $cssPath){
    $s=file_get_contents($cssPath);
    if(!is_string($s)||!preg_match($selectorPattern,$s))continue;
    $cssSources[$cssPath]=$s;
}
if(!$cssSources)quit('Nie znaleziono styli h1 dla banera; nie ryzykuje zmiany wygladu.');
$jsReferences=[];
foreach(filesIn($root.'/public/js',['.js']) as $jsPath){
    $js=file_get_contents($jsPath);
    if(!is_string($js))continue;
    if(preg_match('~ff-shared-hero.+?(?:querySelector\s*\(\s*["\x27]h1["\x27]|getElementsByTagName\s*\(\s*["\x27]h1)~is',$js))
        $jsReferences[]=str_replace($root.'/','',$jsPath);
}
if($jsReferences)quit('Skrypt przeglądarki odwołuje się do h1 — wymagana indywidualna poprawka JS.',$jsReferences);
echo "ODNALEZIONY HTML: ".str_replace($root.'/','',$path)."\n";
foreach($cssSources as $p=>$text)echo "STYL H1: ".str_replace($root.'/','',$p)."\n";

// Do not touch photos, CMS content, global SEO titles or the main page H1.
$replacementOpen="@if(request()->getPathInfo() === '/')\n"
    .$open."\n@else\n"
    .preg_replace('~^<h1\b~i','<div class="ff-seo-shared-headline"',$open,1)
    ."\n@endif";
$replacementClose="@if(request()->getPathInfo() === '/')\n"
    .$close."\n@else\n</div>\n@endif";
if(substr_count($source,$open)!==1||substr_count($source,$close)!==1)
    quit('Naglowek wystepuje wielokrotnie w szablonie.');
$editedSource=substr_replace($source,$replacementClose,$closePos,strlen($close));
$editedSource=substr_replace($editedSource,"{{-- ".MARK." --}}\n".$replacementOpen,$openPos,strlen($open));
$changes=[$path=>$editedSource];
foreach($cssSources as $cssPath=>$text){
    // A Blade file can contain BOTH the HTML markup and its CSS rules.
    // Apply the selector change to the already edited markup, not the old file!
    $base=$changes[$cssPath]??$text;
    $changed=preg_replace($selectorPattern,'$1:is(h1, .ff-seo-shared-headline)',$base,-1,$count);
    if(!is_string($changed)||$count<1)quit('Blad przygotowania zmiany CSS.');
    $changes[$cssPath]=$changed;
}
foreach($changes as $file=>$text) {
    if(!is_writable($file))quit('Plik bez praw zapisu: '.str_replace($root.'/','',$file));
}
$backupRoot=$root.'/storage/app/backups/shared-hero-h1-'.date('Ymd-His');
if(!mkdir($backupRoot,0700,true))quit('Nie udalo sie utworzyc folderu kopii zapasowej.');
foreach($changes as $file=>$text){
    $relative=substr($file,strlen($root)+1);
    $dest=$backupRoot.'/'.$relative;
    $dir=dirname($dest);
    if(!is_dir($dir)&&!mkdir($dir,0700,true))quit('Nie udalo sie przygotowac backupu.');
    if(!copy($file,$dest))quit('Nie udalo sie skopiowac oryginalu: '.$relative);
}
foreach($changes as $file=>$text){
    $tmp=$file.'.seo-h1-new-'.getmypid();
    if(file_put_contents($tmp,$text,LOCK_EX)===false||!rename($tmp,$file)){
        @unlink($tmp);
        // Best-effort rollback for all files in the batch.
        foreach($changes as $restore=>$_) {
            $relative=substr($restore,strlen($root)+1);
            if(is_file($backupRoot.'/'.$relative))copy($backupRoot.'/'.$relative,$restore);
        }
        quit('Blad zapisu; przywrocono kopie.');
    }
}
$viewCache=$root.'/storage/framework/views';
if(is_dir($viewCache))foreach(glob($viewCache.'/*.php')?:[] as $f)@unlink($f);
echo "ZAPISANO ZMIANY. BACKUP: $backupRoot\n";
// Roll back automatically if the newly rendered home or contact H1 is wrong,
// or Laravel fails to render. Leave no broken page on a failed deployment.
$home=headings('https://www.foodfoto.pl/?h1_postflight='.time());
$contact=headings('https://www.foodfoto.pl/kontakt?h1_postflight='.time());
if($home===null||$contact===null||
   count($home['all'])!==1||count($home['shared'])!==1||
   count($contact['all'])!==1||count($contact['shared'])!==0){
    foreach($changes as $restore=>$_){
        $relative=substr($restore,strlen($root)+1);
        if(is_file($backupRoot.'/'.$relative))copy($backupRoot.'/'.$relative,$restore);
    }
    if(is_dir($viewCache))foreach(glob($viewCache.'/*.php')?:[] as $f)@unlink($f);
    quit('Kontrola po wdrozeniu nie przeszla. Przywrocono wszystkie pliki.',[
        'glowna: '.($home?implode(' | ',$home['all']):'brak odczytu'),
        'kontakt: '.($contact?implode(' | ',$contact['all']):'brak odczytu')
    ]);
}
$checkPaths=['/','/o-mnie','/kontakt','/strona/klienci','/polityka-prywatnosci',
'/portfolio/zywnosc','/portfolio/stylizacja','/portfolio/opakowania','/portfolio/czasopisma',
'/strona/fotografia-zywnosci','/strona/fotografia-kulinarna','/strona/food-styling',
'/strona/fotografia-produktowa-zywnosci','/strona/zdjecia-na-opakowania',
'/strona/fotografia-reklamowa-zywnosci'];
$passed=0;
foreach($checkPaths as $p){
    $h=headings('https://www.foodfoto.pl'.$p.'?check_h1='.time());
    $ok=$h!==null&&count($h['all'])===1&&count($h['shared'])===($p==='/'?1:0);
    if($ok)$passed++;
    echo ($ok?'OK':'UWAGA')." $p — H1: ".($h?count($h['all']):'?')
        .", baner: ".($h?count($h['shared']):'?')
        .", tresc: ".($h?implode(' | ',$h['all']):'BRAK ODCZYTU')."\n";
}
echo "WYNIK: $passed/".count($checkPaths)." prawidlowych. Sprawdz wyglad naglowka desktop/mobile.\n";
echo "W przypadku problemu kopie plikow: $backupRoot\n";
