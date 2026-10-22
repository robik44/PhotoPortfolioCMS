<?php
/**
 * FoodFoto / SEOHOST: ALT + title for 20 verified client logos and 1 basil banner.
 * Exact image filenames and order verified against public page on 2026-10-22.
 * PHP 8.3 and PDO only; no Composer, Laravel or Artisan.
 * Dry-run by default. To write: --apply (creates a backup and uses a transaction).
 */
declare(strict_types=1);

$root='/home/srv51861/domains/foodfoto.pl/laravel';
if(!is_dir($root)) {fwrite(STDERR,"Brak katalogu FoodFoto.\n");exit(2);}
chdir($root);
$apply=in_array('--apply',$argv,true);
$logos=[
 [147,'PlslCT6CwfWkfvXYFv3b2Qu1DL76QADjDH1GsClC-thumb.webp','Pudliszki'],
 [146,'5QRRqNmTtTyWTuR2HLCLduRrdh3D0HfVu73vv4q5-thumb.webp','Proste Historie Bistro'],
 [145,'7Qd3sajpcjGHK8c1xKoCDByI7G8z7Z0mCgoqYdkp-thumb.webp','Piątnica'],
 [144,'takuEu07P57nqyopg1uoTpLyE9GUX32AweKTbVxQ-thumb.webp','Lisner'],
 [143,'Jy9vrqspRbjxshQLLTdm3p1fc2hqvalcaMBYML2G-thumb.webp','InStyle'],
 [138,'wfxrXO4WZxFA4zmJivBlL1Yi7PUvzh53qAlaD0XL-thumb.webp','Bakalland'],
 [139,'8lBpjYt1qEKlmIrI7Vmrnmql2plOpfv3DA67W4BU-thumb.webp','Burger King'],
 [140,'Oof4cg9wVq9ebs2bdGUko1vZRsGrIhn3QuYjmTUA-thumb.webp','Cedrob'],
 [141,'3CwzwiNr6eK6osUMt4EiMSy3JeTPdGJP43UbCBji-thumb.webp','dr Gerard'],
 [142,'B8hoDMGhfa9V4y62Jd9AGEUbJBeEFUT5biMXVoj3-thumb.webp','Galeo'],
 [137,'u58e3GxgNn4r2B76KBmCf5eBUeEZu3QGf6zaWtOr-thumb.webp','Żabka'],
 [136,'R8OcvAzear8mMIUKeo8QT9h3xd8zMZcSA54p4qRq-thumb.webp','Winiary'],
 [135,'MlH8A6U52hDijPmmpJLEgvB7CvHIpWjIVn8fSp8I-thumb.webp','Prymat'],
 [134,'PJHYB46cPykPEfpcNDJT4dgsUrZGqBnkRVLbdIjB-thumb.webp','Philadelphia'],
 [133,'zX0taQ9RkDEffbChmN0yfvpcUa50V7cjGxdJdHoL-thumb.webp','La Drogheria 1880'],
 [128,'Bj21ZgDWa3JTdE24uhnWkeuJk9w4s1Lqpf9LY3Jp-thumb.webp','Dajar'],
 [129,'3VQYSudR99n3NnZPLYMVV0fchjxKt027bXAQRubM-thumb.webp','Iglotex'],
 [130,'rEMBuAngiHuGkfpUXE7EObn8JFwac5pneXSrsJB4-thumb.webp','Kamis'],
 [131,'cJiwiAuEo8tx3Oopux8WhZ8p7xKVzApCm4tKA98l-thumb.webp','Knorr'],
 [132,'D1kiL1d3d0bcuB5jzMiyQ2nv2rvz8hs2gXqFeVLx-thumb.webp','magazyn Kuchnia'],
];
$banner=[
 'id'=>151,
 'image'=>'21PDDxUZLcVQS3cGBec8PJQc4NmQtm1odK0wsRM9-web.webp',
 'title'=>'Bazylia na jasnym tle',
 'alt'=>'Świeże liście bazylii i rozsypane przyprawy na jasnym tle – fotografia żywności'
];
function fail(string $text): never {
    fwrite(STDERR,"PRZERWANO, BEZ ZMIAN: ".$text."\n");
    exit(2);
}
function readEnv(string $path):array {
    $values=[];
    foreach(@file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line) {
        if(!preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/',$line,$m))continue;
        $v=trim($m[2]);
        if(strlen($v)>=2 && (($v[0]==='"'&&str_ends_with($v,'"'))||($v[0]==="'"&&str_ends_with($v,"'"))))
            $v=substr($v,1,-1);
        else $v=preg_replace('/\s+#.*$/','',$v)??$v;
        $values[$m[1]]=$v;
    }
    return $values;
}
function db(array $env,string $root):PDO {
    $type=strtolower((string)($env['DB_CONNECTION']??'sqlite'));
    try {
        if($type==='sqlite'){
            $path=$env['DB_DATABASE']??$root.'/database/database.sqlite';
            if(!str_starts_with($path,'/'))$path=$root.'/'.$path;
            if(!is_file($path))fail('Nie znaleziono aktywnej bazy SQLite.');
            return new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        }
        if(in_array($type,['mysql','mariadb'],true)) {
            $name=$env['DB_DATABASE']??'';
            if($name==='')fail('Brak nazwy bazy.');
            $dsn=($env['DB_SOCKET']??'')!==''
                ? 'mysql:unix_socket='.$env['DB_SOCKET'].';dbname='.$name.';charset=utf8mb4'
                : 'mysql:host='.($env['DB_HOST']??'127.0.0.1').';port='.($env['DB_PORT']??'3306').';dbname='.$name.';charset=utf8mb4';
            return new PDO($dsn,$env['DB_USERNAME']??'',$env['DB_PASSWORD']??'',
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>7]);
        }
    }catch(Throwable $e){fail('Nie udalo sie polaczyc z baza danych.');}
    fail('Nieobslugiwany rodzaj bazy danych.');
}
function fetchPublicImages():array {
    $url='https://www.foodfoto.pl/strona/klienci?logo_alt_preflight='.time();
    $cmd='curl -fsSL --connect-timeout 8 --max-time 25 -A '.
        escapeshellarg('FoodFoto-LogoALT-Installer/1.0').' '.escapeshellarg($url).' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<700)fail('Nie moge pobrac publicznej strony Klienci.');
    libxml_use_internal_errors(true);
    $doc=new DOMDocument();
    $parsed=@$doc->loadHTML('<?xml encoding="UTF-8"?>'.$html);
    libxml_clear_errors();
    if(!$parsed)fail('Nie mozna analizowac strony Klienci.');
    $xp=new DOMXPath($doc);
    $srcs=[];$all=[];
    foreach($xp->query('//div[contains(concat(" ",normalize-space(@class)," ")," thumbnail-gallery-grid ")]//img') as $img) {
        if($img instanceof DOMElement)$srcs[]=basename((string)parse_url($img->getAttribute('src'),PHP_URL_PATH));
    }
    foreach($xp->query('//img') as $img){
        if($img instanceof DOMElement)$all[]=basename((string)parse_url($img->getAttribute('src'),PHP_URL_PATH));
    }
    return [$srcs,$all];
}
if(!class_exists('DOMDocument'))fail('Brak rozszerzenia PHP DOM.');
$expectedLogoFiles=array_column($logos,1);
[$publicLogoFiles,$publicImageFiles]=fetchPublicImages();
if($publicLogoFiles!==$expectedLogoFiles) {
    fail('Kolejnosc lub zawartosc 20 logotypow na stronie zmienila sie od zrzutu ekranu; niczego nie zapisuje.');
}
if(!in_array($banner['image'],$publicImageFiles,true))fail('Nie znaleziono zdjecia ID 151 na stronie Klienci.');
if(count($logos)!==20)fail('Nieprawidlowa liczba logotypow.');

$targets=[];
foreach($logos as [$id,$image,$brand]) {
    $name=$brand==='magazyn Kuchnia'?'Logo magazynu Kuchnia':'Logo '.$brand;
    $targets[$id]=['image'=>$image,'title'=>$name,'alt'=>$name,'type'=>'logo'];
}
$targets[$banner['id']]=[
 'image'=>$banner['image'],'title'=>$banner['title'],'alt'=>$banner['alt'],'type'=>'banner'
];
$pdo=db(readEnv($root.'/.env'),$root);
$ids=array_keys($targets);$marks=implode(',',array_fill(0,count($ids),'?'));
$stmt=$pdo->prepare("SELECT id, filename, thumbnail, webp, title, alt, description FROM photos WHERE id IN ($marks)");
$stmt->execute($ids);
$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);$found=[];
foreach($rows as $row)$found[(int)$row['id']]=$row;
if(count($found)!==count($targets))fail('W bazie nie ma wszystkich 21 fotografii.');
$backupRows=[];
foreach($targets as $id=>$desired){
    $row=$found[$id];
    $dbImage=$desired['type']==='logo'?($row['thumbnail']??''):($row['webp']??'');
    if(basename((string)$dbImage)!==$desired['image']) {
        fail("ID $id ma inny plik niz rozpoznany na stronie; niczego nie zmieniam.");
    }
    $beforeTitle=trim((string)($row['title']??''));
    $beforeAlt=trim((string)($row['alt']??''));
    // Never silently overwrite labels added by a human since the diagnostic.
    if($beforeTitle!==''||$beforeAlt!=='') {
        if($beforeTitle===$desired['title']&&$beforeAlt===$desired['alt']) {
            echo "GOTOWE WCZESNIEJ $id: ".$desired['alt']."\n";
        }else fail("ID $id ma juz wlasny tytul lub ALT. Nie nadpisuje go.");
    }
    $backupRows[]=['id'=>$id,'filename'=>$row['filename'],'thumbnail'=>$row['thumbnail'],
        'webp'=>$row['webp'],'title_before'=>$row['title'],'alt_before'=>$row['alt'],
        'description_before'=>$row['description'],'title_after'=>$desired['title'],'alt_after'=>$desired['alt']];
    echo "ID $id => ".$desired['alt']."\n";
}
if(!$apply) {
    echo "\nTRYB PODGLADU — nic nie zapisano. Po sprawdzeniu mozna uzyc --apply.\n";
    exit(0);
}

$dir=$root.'/storage/app/backups';
if(!is_dir($dir)&&!mkdir($dir,0700,true))fail('Nie mozna utworzyc folderu kopii zapasowej.');
$backup=$dir.'/client-logo-alts-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$data=json_encode(['generated_at'=>date(DATE_ATOM),'source'=>'client logos screenshot 2026-10-22',
    'rows'=>$backupRows],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
if(file_put_contents($backup,$data,LOCK_EX)===false)fail('Nie mozna zapisac kopii bezpieczenstwa.');
chmod($backup,0600);
echo "KOPIA: $backup\n";
$updated=0;
try {
    if(!$pdo->beginTransaction())throw new RuntimeException('Nie rozpoczeto transakcji.');
    $sql="UPDATE photos SET title=?, alt=? WHERE id=?
          AND COALESCE(TRIM(title),'')='' AND COALESCE(TRIM(alt),'')=''";
    $update=$pdo->prepare($sql);
    foreach($targets as $id=>$desired) {
        if(trim((string)($found[$id]['title']??''))!==''||trim((string)($found[$id]['alt']??''))!=='')continue;
        $update->execute([$desired['title'],$desired['alt'],$id]);
        if($update->rowCount()!==1)throw new RuntimeException("ID $id zmienilo sie w trakcie aktualizacji.");
        $updated++;
    }
    $pdo->commit();
}catch(Throwable $e) {
    if($pdo->inTransaction())$pdo->rollBack();
    fail('Transakcja zostala wycofana: '.$e->getMessage());
}
echo "ZAPISANO $updated METADANYCH; dane pozostaly w $backup\n";
$check=$pdo->prepare("SELECT id,title,alt FROM photos WHERE id IN ($marks)");
$check->execute($ids);
$pass=0;
foreach($check->fetchAll(PDO::FETCH_ASSOC) as $row){
    $id=(int)$row['id'];$expected=$targets[$id];
    if((string)$row['title']===$expected['title']&&(string)$row['alt']===$expected['alt'])$pass++;
}
echo "KONTROLA BAZY: $pass/21 poprawnych par TITLE+ALT.\n";
if($pass!==21)exit(3);
echo "GOTOWE. Bez zmian w plikach fotografii, opisach, kolejnosci, rozmiarach i builderze.\n";
