<?php
/**
 * FOODFOTO — SEO: two visible text titles become H2; four portfolio card H3 become H2.
 * Uses exactly two current production Blade files and one home page_builders JSON row.
 * Only semantics change. Typography, copy, coordinates, images, order remain untouched.
 * PHP CLI 8.3 + PDO; no Laravel bootstrap, Composer or Artisan.
 *
 * Default: dry run; --apply: guarded production update with backup and rollback.
 */
declare(strict_types=1);

$root='/home/srv51861/domains/foodfoto.pl/laravel';
if(!is_dir($root)) {fwrite(STDERR,"PRZERWANO: Brak folderu Laravel.\n");exit(2);}
chdir($root);
$apply=in_array('--apply',$argv,true);
$names=[
 'fve-1792056317142-i69pxzrvj6'=>'Smak w kadrach',
 'fve-1792667803515-svolfay6iyd'=>'Smak ma swoją opowieść',
];
$galleryTitles=['FOOD STYLING','FOTOGRAFIA ŻYWNOŚCI','OPAKOWANIA','CZASOPISMA'];
$paths=[
 'resources/views/components/public-builder-canvas.blade.php',
 'resources/views/components/builder-gallery.blade.php',
];
$needleTag="'galleryTitleTag' => \$canvasId === 'home-public-builder-canvas' ? 'h3' : 'div',";
$replacementTag="'galleryTitleTag' => \$canvasId === 'home-public-builder-canvas' ? 'h2' : 'div',";
$needleAllowed="\$galleryTitleTag = in_array(\$galleryTitleTag ?? 'div', ['div', 'h3'], true) ? (\$galleryTitleTag ?? 'div') : 'div';";
$replacementAllowed="\$galleryTitleTag = in_array(\$galleryTitleTag ?? 'div', ['div', 'h2', 'h3'], true) ? (\$galleryTitleTag ?? 'div') : 'div';";

function haltSeo(string $message):never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: ".$message."\n");
    exit(2);
}
function readEnvSeo(string $file):array {
    $out=[];
    foreach(@file($file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line) {
        if(!preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/',$line,$m))continue;
        $v=trim($m[2]);
        if(strlen($v)>=2 && (($v[0]==='"'&&str_ends_with($v,'"'))||($v[0]==="'"&&str_ends_with($v,"'"))))$v=substr($v,1,-1);
        else $v=preg_replace('/\s+#.*$/','',$v)??$v;
        $out[$m[1]]=$v;
    }
    return $out;
}
function dbSeo(array $env,string $root):PDO {
    $type=strtolower((string)($env['DB_CONNECTION']??'sqlite'));
    try {
        if($type==='sqlite'){
            $path=$env['DB_DATABASE']??"$root/database/database.sqlite";
            if(!str_starts_with($path,'/'))$path=$root.'/'.$path;
            if(!is_file($path))haltSeo('Nie ma pliku SQLite.');
            return new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        }
        if(in_array($type,['mysql','mariadb'],true)){
            if(($env['DB_DATABASE']??'')==='')haltSeo('Brak nazwy bazy danych.');
            $dsn=($env['DB_SOCKET']??'')!==''
                ?'mysql:unix_socket='.$env['DB_SOCKET'].';dbname='.$env['DB_DATABASE'].';charset=utf8mb4'
                :'mysql:host='.($env['DB_HOST']??'127.0.0.1').';port='.($env['DB_PORT']??3306).';dbname='.$env['DB_DATABASE'].';charset=utf8mb4';
            return new PDO($dsn,$env['DB_USERNAME']??'',$env['DB_PASSWORD']??'',[
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>7
            ]);
        }
    }catch(Throwable $e){haltSeo('Nie mozna polaczyc z baza (dane polaczenia ukryte).');}
    haltSeo('Nieobslugiwany rodzaj bazy danych.');
}
function collapseSeo(string $v):string {return trim(preg_replace('/\s+/u',' ',$v)??$v);}
function liveSeo(string $path):?array {
    $url='https://www.foodfoto.pl'.($path==='/'?'/':$path).'?seo_hier='.time().'-'.bin2hex(random_bytes(3));
    $cmd='curl -fsSL --connect-timeout 8 --max-time 25 -A '
        .escapeshellarg('FoodFoto-SEOHeadingsFix/1.0').' '.escapeshellarg($url).' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<600)return null;
    $dom=new DOMDocument();
    libxml_use_internal_errors(true);
    $ok=@$dom->loadHTML('<?xml encoding="UTF-8"?>'.$html);
    libxml_clear_errors();
    if(!$ok)return null;
    $xp=new DOMXPath($dom);
    $result=['h1'=>[],'h2'=>[],'h3'=>[],'galleries'=>[],'slogans'=>[],'first_h2_before_h3'=>true];
    foreach(['h1','h2','h3'] as $tag) {
        foreach($xp->query('//'.$tag) as $n)$result[$tag][]=collapseSeo($n->textContent);
    }
    foreach($xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," builder-gallery-card-title ")]') as $n){
        $result['galleries'][]=['tag'=>strtolower($n->nodeName),'text'=>collapseSeo($n->textContent)];
    }
    $seenH2=false;
    foreach($xp->query('//h1|//h2|//h3') as $n) {
        if(strtolower($n->nodeName)==='h2')$seenH2=true;
        if(strtolower($n->nodeName)==='h3'&&!$seenH2)$result['first_h2_before_h3']=false;
    }
    foreach(['fve-1792056317142-i69pxzrvj6','fve-1792667803515-svolfay6iyd'] as $id) {
        $nodes=$xp->query('//*[@data-builder-id="'.$id.'"]/*[self::p or self::h2 or self::h3]');
        if($nodes->length===1) {
            $n=$nodes->item(0);
            $result['slogans'][$id]=['tag'=>strtolower($n->nodeName),'text'=>collapseSeo($n->textContent),'style'=>$n instanceof DOMElement?$n->getAttribute('style'):''];
        }
    }
    return $result;
}
function homeGood(array $check,array $names,array $galleryTitles,bool $after):bool {
    if(count($check['h1'])!==1||$check['h1'][0]!=='Fotografia i stylizacja żywności')return false;
    if(count($check['h2'])!==($after?11:5) || count($check['h3'])!==($after?9:13))return false;
    if(count($check['galleries'])!==4)return false;
    foreach($check['galleries'] as $i=>$entry) {
        if($entry['tag']!==($after?'h2':'h3')||$entry['text']!==$galleryTitles[$i])return false;
    }
    foreach($names as $id=>$text) {
        if(!isset($check['slogans'][$id])||$check['slogans'][$id]['tag']!==($after?'h2':'p')||$check['slogans'][$id]['text']!==$text)
            return false;
    }
    if($after&&!$check['first_h2_before_h3'])return false;
    return true;
}
function clearViewsSeo(string $root):void {
    $dir=$root.'/storage/framework/views';
    if(is_dir($dir))foreach(glob($dir.'/*.php')?:[] as $f)if(is_file($f))@unlink($f);
}
if(!class_exists('DOMDocument'))haltSeo('Brak DOMDocument w PHP.');
$before=liveSeo('/');
if(!$before)haltSeo('Strona glowna nie odpowiada.');
echo "PRZED: H1=".count($before['h1'])." H2=".count($before['h2'])." H3=".count($before['h3'])."\n";
foreach($before['slogans'] as $id=>$v)echo "  ".$v['tag'].": ".$v['text']." (id $id)\n";
foreach($before['galleries'] as $g)echo "  ".$g['tag'].": ".$g['text']."\n";
if(!homeGood($before,$names,$galleryTitles,false))haltSeo('Obecna strona glowna odbiega od otrzymanego audytu; brak zmian.');

// The two components must have exactly the expected source shape.
$sources=[];$replacement=[];
foreach($paths as $path) {
    $file=$root.'/'.$path;
    if(!is_file($file)||!is_readable($file)||!is_writable($file))
        haltSeo('Brak prawa do szablonu '.$path);
    $sources[$path]=file_get_contents($file);
}
$canvas=$sources[$paths[0]];
$gallery=$sources[$paths[1]];
if(substr_count($canvas,$needleTag)!==1
    || !str_contains($canvas,'<{{ $semanticTag }} class="builder-public-text"')
    || !str_contains($canvas,'style="margin:0;{{ $typographyCss }}"'))
    haltSeo('Szablon publicznego buildera rozni sie od znanej wersji.');
if(substr_count($gallery,$needleAllowed)!==1
    || !str_contains($gallery,'.builder-gallery-card-title {')
    || !str_contains($gallery,'font: inherit;')
    || !str_contains($gallery,'margin: 0;'))
    haltSeo('Szablon kafelkow galerii ma nieznana strukture/CSS.');
$replacement[$paths[0]]=str_replace($needleTag,$replacementTag,$canvas);
$replacement[$paths[1]]=str_replace($needleAllowed,$replacementAllowed,$gallery);
if($replacement[$paths[0]]===$canvas||$replacement[$paths[1]]===$gallery)
    haltSeo('Nie udalo sie przygotowac zmian w szablonach.');
$pdo=dbSeo(readEnvSeo($root.'/.env'),$root);
try{
    $query="SELECT id, content FROM page_builders
            WHERE page_id IS NULL AND type='home' AND published=1";
    $rows=$pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){haltSeo('Brak dostepu do buildera strony glownej.');}
if(count($rows)!==1)haltSeo('Oczekiwano jednego opublikowanego buildera home.');
$row=$rows[0];
$oldRaw=(string)$row['content'];
try{$document=json_decode($oldRaw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){haltSeo('Niepoprawny JSON home buildera.');}
if(!is_array($document)||!is_array($document['sections']??null)||!array_is_list($document['sections']))
    haltSeo('Brak listy sections w home builderze.');
if(count($document['sections'])!==47)haltSeo('Nieoczekiwana liczba elementow home buildera (oczekiwano 47).');
$found=[];
foreach($document['sections'] as $index=>$section) {
    if(!is_array($section))continue;
    $id=(string)($section['id']??'');
    if(!array_key_exists($id,$names))continue;
    if(isset($found[$id]))haltSeo('Powielone ID elementu '.$id);
    if(($section['type']??'')!=='text'
        || collapseSeo((string)($section['content']??''))!==$names[$id]
        || !in_array($section['semantic_tag']??'p',['p',null],true))
        haltSeo('Nieoczekiwana tresc, typ lub znacznik elementu '.$id);
    $found[$id]=$index;
}
if(count($found)!==2)haltSeo('Nie odnaleziono dwoch tytulow sekcji home.');
foreach($found as $id=>$index)$document['sections'][$index]['semantic_tag']='h2';
$newRaw=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
echo "\nZAKRES: 2 semantyczne tagi p->h2 w builderze home i 4 tytuly galerii h3->h2.\n";
echo "Tresc, style, typografia, pozycje, zdjecia i kolejnosc pozostaja bez zmian.\n";
if(!$apply){echo "PODGLAD: Nie zapisano zadnych zmian. Uruchom z --apply, aby zatwierdzic.\n";exit(0);}

$backup=$root.'/storage/app/backups/home-seo-headings-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($backup,0700,true))haltSeo('Nie mozna utworzyc kopii zapasowej.');
foreach($sources as $path=>$value){
    $dest=$backup.'/'.$path;
    if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))haltSeo('Nie mozna przygotowac backupu pliku.');
    if(file_put_contents($dest,$value,LOCK_EX)===false)haltSeo('Nie mozna utworzyc kopii szablonu.');
}
$dbBackup=$backup.'/home-builder-before.json';
$payload=json_encode(['builder_id'=>$row['id'],'raw'=>$oldRaw,'timestamp'=>date(DATE_ATOM)],
    JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
if(file_put_contents($dbBackup,$payload,LOCK_EX)===false)haltSeo('Nie mozna zapisac kopii buildera.');
@chmod($dbBackup,0600);
echo "KOPIA: $backup\n";
$written=[];
try{
    foreach($replacement as $path=>$value){
        $file=$root.'/'.$path;
        $tmp=$file.'.seo-headings-'.getmypid();
        if(file_put_contents($tmp,$value,LOCK_EX)===false)throw new RuntimeException('Brak zapisu pliku tymczasowego');
        @chmod($tmp,fileperms($file)&0777);
        if(!rename($tmp,$file)){@unlink($tmp);throw new RuntimeException('Brak zapisu szablonu');}
        $written[]=$path;
    }
    $pdo->beginTransaction();
    $stmt=$pdo->prepare("UPDATE page_builders SET content=? WHERE id=? AND content=?");
    $stmt->execute([$newRaw,(int)$row['id'],$oldRaw]);
    if($stmt->rowCount()!==1)throw new RuntimeException('Builder zostal w miedzyczasie zmieniony.');
    $pdo->commit();
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    foreach($written as $path)@copy($backup.'/'.$path,$root.'/'.$path);
    clearViewsSeo($root);
    fwrite(STDERR,"PRZERWANO: zapis nieudany; przywrocono szablony: ".$e->getMessage()."\n");
    exit(3);
}
clearViewsSeo($root);
$after=liveSeo('/');
$homeCorrect=$after!==null&&homeGood($after,$names,$galleryTitles,true);
$styleCorrect=$homeCorrect;
if($styleCorrect)foreach($names as $id=>$name) {
    if($after['slogans'][$id]['style']!==$before['slogans'][$id]['style'])$styleCorrect=false;
}
echo "PO: H1=".($after?count($after['h1']):'?')
    ." H2=".($after?count($after['h2']):'?')
    ." H3=".($after?count($after['h3']):'?')."\n";
$other=['/o-mnie','/kontakt','/strona/klienci','/polityka-prywatnosci',
'/portfolio/zywnosc','/portfolio/stylizacja','/portfolio/opakowania','/portfolio/czasopisma',
'/strona/fotografia-zywnosci','/strona/fotografia-kulinarna','/strona/food-styling',
'/strona/fotografia-produktowa-zywnosci','/strona/zdjecia-na-opakowania','/strona/fotografia-reklamowa-zywnosci'];
$otherH1=true;
foreach($other as $path){
    $r=liveSeo($path);
    $good=$r!==null&&count($r['h1'])===1;
    if(!$good)$otherH1=false;
    echo ($good?'OK':'UWAGA')." $path: H1=".($r?count($r['h1']):'?')."\n";
}
if(!$homeCorrect||!$styleCorrect||!$otherH1){
    $rollback=true;
    foreach($sources as $path=>$value)if(!@copy($backup.'/'.$path,$root.'/'.$path))$rollback=false;
    try{
        $pdo->beginTransaction();
        $restore=$pdo->prepare('UPDATE page_builders SET content=? WHERE id=? AND content=?');
        $restore->execute([$oldRaw,(int)$row['id'],$newRaw]);
        if($restore->rowCount()!==1)$rollback=false;
        $pdo->commit();
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        $rollback=false;
    }
    clearViewsSeo($root);
    fwrite(STDERR,"KONTROLA NIEUDANA. ".($rollback?'Przywrocono oryginalne szablony i builder.':'UWAGA: Wymagana kontrola kopii!')."\n");
    fwrite(STDERR,"KOPIA: $backup\n");
    exit(4);
}
echo "SUKCES: strona glowna H1=1, H2=11, H3=9, bez H3 przed pierwszym H2.\n";
echo "Dwa tytuly sekcji maja H2, cztery tytuly galerii maja H2. Style elementow tekstowych identyczne.\n";
echo "Pozostale 14 stron: poprawny H1. Kopia: $backup\n";
echo "Skontroluj wizualnie strone glowna na komputerze i telefonie.\n";
