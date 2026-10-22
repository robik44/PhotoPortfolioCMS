<?php
/**
 * FoodFoto — SEO: remove ONLY the test H2 "Nowy nagłówek" from /kontakt.
 * Production PHP 8.3 / PDO, no Laravel bootstrap, no Artisan, no git pull.
 * Guarded preflight, a private full-JSON backup, transactional single-row update,
 * public HTML postflight, safe compare-and-swap rollback.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
if(!is_dir($root)) {fwrite(STDERR,"PRZERWANO: Nie ma katalogu Laravel.\n");exit(2);}
chdir($root);
$wanted='Nowy nagłówek';
function fail(string $why):never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $why\n");
    exit(2);
}
function loadEnv(string $path):array {
    $vars=[];
    foreach(@file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line){
        if(!preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/',$line,$m))continue;
        $value=trim($m[2]);
        if(strlen($value)>=2 && (($value[0]==='"'&&str_ends_with($value,'"'))||($value[0]==="'"&&str_ends_with($value,"'"))))
            $value=substr($value,1,-1);
        else $value=preg_replace('/\s+#.*$/','',$value)??$value;
        $vars[$m[1]]=$value;
    }
    return $vars;
}
function connect(array $v,string $root):PDO {
    $type=strtolower((string)($v['DB_CONNECTION']??'sqlite'));
    try {
        if($type==='sqlite'){
            $path=$v['DB_DATABASE']??"$root/database/database.sqlite";
            if(!str_starts_with($path,'/'))$path=$root.'/'.$path;
            if(!is_file($path))fail('Brak pliku produkcyjnej bazy SQLite.');
            return new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>7]);
        }
        if($type==='mysql'||$type==='mariadb'){
            if(($v['DB_DATABASE']??'')==='')fail('Brak nazwy bazy.');
            $dsn=($v['DB_SOCKET']??'')!==''
                ? 'mysql:unix_socket='.$v['DB_SOCKET'].';dbname='.$v['DB_DATABASE'].';charset=utf8mb4'
                : 'mysql:host='.($v['DB_HOST']??'127.0.0.1').';port='.($v['DB_PORT']??'3306').';dbname='.$v['DB_DATABASE'].';charset=utf8mb4';
            return new PDO($dsn,$v['DB_USERNAME']??'',$v['DB_PASSWORD']??'',[
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>7
            ]);
        }
    }catch(Throwable $ex){fail('Nie mozna odczytac bazy danych (szczegoly polaczenia ukryte).');}
    fail('Nieobslugiwany rodzaj bazy danych.');
}
function headings():?array {
    $url='https://www.foodfoto.pl/kontakt?seo_test_heading_check='.time();
    $cmd='curl -fsSL --connect-timeout 8 --max-time 25'
       .' -A '.escapeshellarg('FoodFoto-ContactH2Fix/1.0')
       .' '.escapeshellarg($url).' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<600)return null;
    libxml_use_internal_errors(true);
    $dom=new DOMDocument();
    $ok=@$dom->loadHTML('<?xml encoding="UTF-8"?>'.$html);
    libxml_clear_errors();
    if(!$ok)return null;
    $xpath=new DOMXPath($dom);$result=[];
    foreach(['h1','h2','h3'] as $tag){
        $result[$tag]=[];
        foreach($xpath->query('//'.$tag) as $node) {
            $result[$tag][]=trim(preg_replace('/\s+/u',' ',$node->textContent)??'');
        }
    }
    return $result;
}
function exactCount(array $headings,string $phrase):int {
    return count(array_filter($headings,static fn($s)=>$s===$phrase));
}
if(!class_exists('DOMDocument'))fail('PHP DOM niedostepne.');
$before=headings();
if(!$before)fail('Nie mozna pobrac aktualnej strony Kontakt.');
$h1=$before['h1'];
echo "PRZED: H1: ".implode(' | ',$h1)."\n";
echo "PRZED: H2: ".implode(' | ',$before['h2'])."\n";
if(count($h1)!==1||trim($h1[0])!=='KONTAKT')fail('Zmianie ulegla struktura H1 na stronie Kontakt.');
if(exactCount($before['h2'],$wanted)!==1) {
    if(exactCount($before['h2'],$wanted)===0){
        echo "Testowy H2 juz nie wystepuje w publicznym HTML. Nie zmieniono bazy.\n";
        exit(0);
    }
    fail('W publicznym HTML wystepuje wiecej niz jeden testowy H2.');
}
$pdo=connect(loadEnv($root.'/.env'),$root);
$sql="SELECT pb.id AS builder_id, pb.content, pb.published
      FROM page_builders pb INNER JOIN pages p ON p.id=pb.page_id
      WHERE p.slug='kontakt' AND pb.type='page'";
try {
    $rows=$pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){fail('Brak wymaganego rekordu buildera Kontakt.');}
if(count($rows)!==1)fail('W bazie znaleziono '.count($rows).' builderow Kontakt; oczekiwano dokladnie jednego.');
$row=$rows[0];
if((int)$row['published']!==1)fail('Builder Kontakt nie jest opublikowany.');
$raw=(string)$row['content'];
try{$document=json_decode($raw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable $e){fail('Niepoprawny format JSON w builderze Kontakt.');}
if(!is_array($document)||!isset($document['sections'])||!is_array($document['sections']))
    fail('Builder nie zawiera tablicy sections.');
if(!array_is_list($document['sections']))fail('Lista sections ma niestandardowa strukture.');
$match=[];
foreach($document['sections'] as $index=>$element){
    if(!is_array($element))continue;
    $content=trim(preg_replace('/\s+/u',' ',(string)($element['content']??''))??'');
    if($content!==$wanted)continue;
    $type=$element['type']??'';
    $semantic=$element['semantic_tag']??($element['heading_level']??'');
    if(!in_array($type,['heading','text'],true)||!in_array($semantic,['h2','H2'],true))
        fail('Element o tresci testowej nie jest zwyklym H2: nr '.($index+1));
    $match[]=$index;
}
if(count($match)!==1)
    fail('W builderze znaleziono '.count($match).' testowych elementow H2 zamiast jednego.');
$i=$match[0];$item=$document['sections'][$i];
echo "ELEMENT: #".($i+1).", id=".((string)($item['id']??'[brak]'))
   .", typ=".($item['type']??'').", pozycja=".($item['position_x']??'?')
   ."/".($item['position_y']??'?')."\n";
echo "INNE ELEMENTY: ".(count($document['sections'])-1)."; pozostaja bez zmian.\n";
unset($document['sections'][$i]);
$document['sections']=array_values($document['sections']);
$newRaw=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))fail('Nie mozna utworzyc folderu backupu.');
$backup=$backupDir.'/contact-test-heading-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$data=json_encode([
    'generated_at'=>date(DATE_ATOM),'slug'=>'kontakt',
    'builder_id'=>(int)$row['builder_id'],'removed_element'=>$item,
    'previous_content'=>$raw
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR);
if(file_put_contents($backup,$data,LOCK_EX)===false)fail('Nie mozna utworzyc kopii JSON.');
@chmod($backup,0600);
echo "KOPIA: $backup\n";
try{
    $pdo->beginTransaction();
    $lock=$pdo->prepare($sql.(strtolower((string)(loadEnv($root.'/.env')['DB_CONNECTION']??'sqlite'))==='sqlite'?'':' FOR UPDATE'));
    $lock->execute();
    $locked=$lock->fetchAll(PDO::FETCH_ASSOC);
    if(count($locked)!==1||(string)$locked[0]['content']!==$raw)
        throw new RuntimeException('Builder zmienil sie od kontroli — transakcja anulowana.');
    $u=$pdo->prepare('UPDATE page_builders SET content=? WHERE id=?');
    $u->execute([$newRaw,(int)$row['builder_id']]);
    if($u->rowCount()!==1)throw new RuntimeException('Nie zapisano dokladnie jednego buildera.');
    $pdo->commit();
}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    fail('Blad zapisu, transakcja wycofana: '.$e->getMessage());
}
echo "ZAPISANO: usunieto tylko testowy naglowek z buildera Kontakt.\n";
$after=headings();
$wantedH2=array_values(array_filter($before['h2'],static fn($s)=>$s!==$wanted));
$success=$after
    && $after['h1']===$before['h1']
    && $after['h2']===$wantedH2
    && $after['h3']===$before['h3'];
if(!$success){
    try {
        $pdo->beginTransaction();
        $st=$pdo->prepare('SELECT content FROM page_builders WHERE id=?');
        $st->execute([(int)$row['builder_id']]);
        if((string)$st->fetchColumn()!==$newRaw)throw new RuntimeException('Rekord zmieniony przez inny proces.');
        $restore=$pdo->prepare('UPDATE page_builders SET content=? WHERE id=?');
        $restore->execute([$raw,(int)$row['builder_id']]);
        $pdo->commit();
        fwrite(STDERR,"KONTROLA NIEUDANA — automatycznie przywrocono kopie buildera.\n");
    }catch(Throwable $e){
        if($pdo->inTransaction())$pdo->rollBack();
        fwrite(STDERR,"UWAGA: nie udalo sie wycofac zmiany. Kopia: $backup\n");
    }
    exit(3);
}
echo "PO: H1: ".implode(' | ',$after['h1'])."\n";
echo "PO: H2: ".implode(' | ',$after['h2'])."\n";
echo "SUKCES: testowy H2 'Nowy nagłówek' usuniety, pozostale H1/H2/H3 zachowane.\n";
echo "Nie zmieniono zadnych innych elementow, pozycji, zdjec, styli ani ustawien buildera.\n";
