<?php
/**
 * FoodFoto: ONLY READ current client logos and the lower contact banner photo.
 * Designed for SEOHOST PHP CLI 8.3, without Laravel bootstrapping.
 * Prints ID, existing title, ALT and matching image filename; no modifications.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
function info(string $message):void {echo $message."\n";}
function val(string $x):string {return trim(preg_replace('/\s+/u',' ',$x)??$x);}
function envFile(string $file):array {
    $env=[];
    foreach(@file($file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line){
        if(!preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/',$line,$m))continue;
        $value=trim($m[2]);
        if(strlen($value)>=2 && (($value[0]==='"' && str_ends_with($value,'"')) || ($value[0]==="'" && str_ends_with($value,"'")))){
            $value=substr($value,1,-1);
        }else{$value=preg_replace('/\s+#.*$/','',$value)??$value;}
        $env[$m[1]]=$value;
    }
    return $env;
}
function openDb(array $env,string $root):?PDO {
    $conn=strtolower((string)($env['DB_CONNECTION']??'sqlite'));
    try {
        if($conn==='sqlite'){
            $db=$env['DB_DATABASE']??"$root/database/database.sqlite";
            if($db===':memory:')throw new RuntimeException('sqlite in memory');
            if(!str_starts_with($db,'/'))$db="$root/$db";
            if(!is_file($db)){
                $fallback="$root/database/database.sqlite";
                if(!is_file($fallback))throw new RuntimeException('sqlite database not found');
                $db=$fallback;
            }
            $pdo=new PDO('sqlite:'.$db,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $pdo->exec('PRAGMA query_only=ON');
            return $pdo;
        }
        if(in_array($conn,['mysql','mariadb'],true)){
            $dbname=(string)($env['DB_DATABASE']??'');
            $user=(string)($env['DB_USERNAME']??'');
            $pass=(string)($env['DB_PASSWORD']??'');
            $dsn=($env['DB_SOCKET']??'')!==''
                ? 'mysql:unix_socket='.$env['DB_SOCKET'].';dbname='.$dbname.';charset=utf8mb4'
                : 'mysql:host='.($env['DB_HOST']??'127.0.0.1').';port='.($env['DB_PORT']??'3306').';dbname='.$dbname.';charset=utf8mb4';
            return new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>6]);
        }
        info('BAZA: nieobslugiwany typ '.$conn.'; wypisze same adresy obrazow.');
    }catch(Throwable $e){
        info('BAZA: brak mozliwosci odczytu metadanych (bez wypisywania hasel ani ustawien).');
    }
    return null;
}
function findPhoto(array $photos,string $src):?array {
    $name=rawurldecode(basename((string)(parse_url($src,PHP_URL_PATH)?:$src)));
    foreach($photos as $row) {
        foreach(['thumbnail','webp','filename'] as $field) {
            if(trim((string)($row[$field]??''))!=='' && basename((string)$row[$field])===$name) return $row;
        }
    }
    return null;
}
function showPhoto(array $photos,string $src,int $i,string $kind):void {
    $match=findPhoto($photos,$src);
    info("\n".$kind." #".$i);
    info('  PLIK: '.basename((string)(parse_url($src,PHP_URL_PATH)?:$src)));
    if($match) {
        info('  ID ZDJECIA: '.(string)$match['id']);
        info('  TYTUL W BIBLIOTECE: '.(val((string)($match['title']??''))?:'[PUSTY]'));
        info('  ALT W BIBLIOTECE: '.(val((string)($match['alt']??''))?:'[PUSTY]'));
        info('  OPIS W BIBLIOTECE: '.(val((string)($match['description']??''))?:'[PUSTY]'));
    } else info('  ID / METADANE: [NIE ODNALEZIONO]');
    info('  URL: '.$src);
}
if(!class_exists('DOMDocument')){info('BRAK rozszerzenia PHP DOM.');exit(2);}
$env=envFile($root.'/.env');
$photos=[];$pdo=openDb($env,$root);
if($pdo!==null) {
    try {
        $rows=$pdo->query('SELECT id, filename, thumbnail, webp, title, alt, description FROM photos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $photos=is_array($rows)?$rows:[];
        info('Biblioteka: odczytano metadane '.count($photos).' zdjec (tylko SELECT).');
    }catch(Throwable $e){info('BAZA: brak dostepu do tabeli photos.');}
}
$url='https://www.foodfoto.pl/strona/klienci?alt_readonly='.time();
$cmd='curl -fsSL --retry 1 --connect-timeout 8 --max-time 25 -A '.escapeshellarg('FoodFoto-ALT-Audit/1.0').' '.escapeshellarg($url).' 2>/dev/null';
$html=shell_exec($cmd);
if(!is_string($html)||strlen($html)<800){info('Nie udalo sie odczytac strony Klienci.');exit(2);}
libxml_use_internal_errors(true);
$doc=new DOMDocument();
$ok=@$doc->loadHTML('<?xml encoding="UTF-8"?>'.$html);
libxml_clear_errors();
if(!$ok){info('Nie udalo sie odczytac HTML.');exit(2);}
$xp=new DOMXPath($doc);
$thumbNodes=$xp->query('//div[contains(concat(" ",normalize-space(@class)," ")," thumbnail-gallery-grid ")]//img');
$thumbs=[];
foreach($thumbNodes as $img) if($img instanceof DOMElement)$thumbs[]=$img;
info('MINIATURKI LOGO KLIENTOW: '.count($thumbs));
foreach($thumbs as $i=>$img) {
    showPhoto($photos,trim($img->getAttribute('src')),$i+1,'LOGO');
    info('  ALT PUBLICZNY: '.(val($img->getAttribute('alt'))?:'[PUSTY]'));
}
info("\nPOZOSTALE ZDJECIA BEZ OPISOWEGO ALT NA STRONIE KLIENCI:");
$seen=0;
foreach($xp->query('//img') as $img){
    if(!$img instanceof DOMElement||val($img->getAttribute('alt'))!=='')continue;
    $inGallery=false;
    for($n=$img->parentNode;$n instanceof DOMElement;$n=$n->parentNode){
        if(str_contains(' '.$n->getAttribute('class').' ',' thumbnail-gallery-grid ')){$inGallery=true;break;}
    }
    if($inGallery)continue;
    $seen++;
    showPhoto($photos,trim($img->getAttribute('src')),$seen,'INNY OBRAZ');
}
info("\nKONIEC. Bez zmian w bazie, CMS, plikach i opisach ALT.");
