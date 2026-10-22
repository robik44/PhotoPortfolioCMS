<?php
/**
 * FoodFoto 2026-10-22: full public H1/H2/H3 hierarchy, keyword distribution,
 * home-section visual heading diagnostics, and builder semantic-tag inventory.
 *
 * READ ONLY: no writes to database, files, templates or CMS. PHP CLI 8.3.
 */
declare(strict_types=1);

$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
$urls=[
'/',
'/o-mnie',
'/kontakt',
'/strona/klienci',
'/polityka-prywatnosci',
'/portfolio/zywnosc',
'/portfolio/stylizacja',
'/portfolio/opakowania',
'/portfolio/czasopisma',
'/strona/fotografia-zywnosci',
'/strona/fotografia-kulinarna',
'/strona/food-styling',
'/strona/fotografia-produktowa-zywnosci',
'/strona/zdjecia-na-opakowania',
'/strona/fotografia-reklamowa-zywnosci',
];
$terms=[
 'fotografia żywności',
 'fotografia kulinarna',
 'fotografia jedzenia',
 'stylizacja żywności',
 'stylizacja potraw',
 'food styling',
 'fotografia reklamowa',
 'fotografia produktowa',
 'zdjęcia na opakowania',
];
$visualCandidates=[
 'Smak w kadrach',
 'Smak ma swoją opowieść',
 'Galerie- wybrane realizacje',
 'Galerie - wybrane realizacje',
 'Jak pracuję',
 'Wybrane realizacje',
 'Więcej niż zdjęcia.',
];
function normText(string $text):string {
    return trim(preg_replace('/\s+/u',' ',$text)??$text);
}
function oneLine(string $text,int $max=210):string {
    return mb_substr(normText($text),0,$max);
}
function occurrences(string $haystack,string $needle):int {
    $count=0;$start=0;$needle=mb_strtolower($needle);$haystack=mb_strtolower($haystack);
    while(($at=mb_strpos($haystack,$needle,$start))!==false){$count++;$start=$at+mb_strlen($needle);}
    return $count;
}
function pageFetch(string $path):?DOMDocument {
    $url='https://www.foodfoto.pl'.($path==='/'?'/':$path).'?heading_audit='.time().'-'.bin2hex(random_bytes(3));
    $cmd='curl -fsSL --connect-timeout 8 --max-time 25 -A '
        .escapeshellarg('FoodFoto-HeadingHierarchyAudit/1.0')
        .' '.escapeshellarg($url).' 2>/dev/null';
    $source=shell_exec($cmd);
    if(!is_string($source)||strlen($source)<600)return null;
    $document=new DOMDocument();
    libxml_use_internal_errors(true);
    $ok=@$document->loadHTML('<?xml encoding="UTF-8"?>'.$source);
    libxml_clear_errors();
    return $ok?$document:null;
}
function snippetPath(DOMNode $node):string {
    $parts=[];
    $p=$node;
    for($i=0;$i<6&&$p instanceof DOMElement;$i++,$p=$p->parentNode){
        $part=strtolower($p->tagName);
        if($p->hasAttribute('id'))$part.='#'.oneLine($p->getAttribute('id'),50);
        $classes=preg_split('/\s+/u',trim($p->getAttribute('class')));
        if($classes&&$classes[0]!=='')$part.='.'.implode('.',array_slice($classes,0,2));
        array_unshift($parts,$part);
    }
    return implode(' > ',$parts);
}
function envParse(string $file):array {
    $out=[];
    foreach(@file($file,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[] as $line){
        if(!preg_match('/^\s*([A-Z][A-Z0-9_]*)\s*=\s*(.*?)\s*$/',$line,$m))continue;
        $v=trim($m[2]);
        if(strlen($v)>1&&(($v[0]==='"'&&str_ends_with($v,'"'))||($v[0]==="'"&&str_ends_with($v,"'"))))
            $v=substr($v,1,-1);
        else $v=preg_replace('/\s+#.*$/','',$v)??$v;
        $out[$m[1]]=$v;
    }
    return $out;
}
function connectReadOnly(string $root):?PDO {
    $v=envParse($root.'/.env');
    $type=strtolower((string)($v['DB_CONNECTION']??'sqlite'));
    try{
        if($type==='sqlite'){
            $file=$v['DB_DATABASE']??($root.'/database/database.sqlite');
            if(!str_starts_with($file,'/'))$file=$root.'/'.$file;
            if(!is_file($file))return null;
            $db=new PDO('sqlite:'.$file,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $db->exec('PRAGMA query_only=ON');
            return $db;
        }
        if(in_array($type,['mysql','mariadb'],true)){
            $name=$v['DB_DATABASE']??'';
            if($name==='')return null;
            $dsn=($v['DB_SOCKET']??'')!==''
                ?'mysql:unix_socket='.$v['DB_SOCKET'].';dbname='.$name.';charset=utf8mb4'
                :'mysql:host='.($v['DB_HOST']??'127.0.0.1').';port='.($v['DB_PORT']??3306).';dbname='.$name.';charset=utf8mb4';
            return new PDO($dsn,$v['DB_USERNAME']??'',$v['DB_PASSWORD']??'',[
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>7]);
        }
    }catch(Throwable $e){echo "\nBAZA: nie udalo sie odczytac danych buildera; szczegoly polaczenia ukryte.\n";}
    return null;
}
if(!class_exists('DOMDocument')){fwrite(STDERR,"PHP DOM jest niedostepne.\n");exit(2);}
echo "FOODFOTO — AUDYT H1/H2/H3 I FRAZ — ".date('Y-m-d H:i:s')."\n";
echo "TRYB: tylko odczyt / brak zmian w serwisie.\n";
$counts=[];$failures=[];$homeDoc=null;
foreach($urls as $path){
    $dom=pageFetch($path);
    echo "\n=======================================================\n";
    echo "ADRES: ".$path."\n";
    if(!$dom){echo "NIE ODCZYTANO STRONY\n";$failures[]=$path;continue;}
    if($path==='/')$homeDoc=$dom;
    $xp=new DOMXPath($dom);
    $headingNodes=$xp->query('//h1|//h2|//h3');
    $seenH2=false;$h1=0;$h2=0;$h3=0;$headingsText=[];
    foreach($headingNodes as $node){
        $tag=strtoupper($node->nodeName);
        if($tag==='H1')$h1++;
        if($tag==='H2'){$h2++;$seenH2=true;}
        if($tag==='H3')$h3++;
        $label=oneLine($node->textContent,175);
        echo sprintf("  %-2s | %s",$tag,$label);
        if($tag==='H3'&&!$seenH2)echo "  [H3 PRZED PIERWSZYM H2]";
        echo "\n";
        $headingsText[]=$label;
    }
    echo "SUMA: H1=$h1 H2=$h2 H3=$h3\n";
    if($h1!==1)echo "UWAGA: oczekiwano jednego H1.\n";
    $bodyNode=$xp->query('//body')->item(0);
    $bodyText=$bodyNode?oneLine($bodyNode->textContent,100000):'';
    $headingText=implode(' ',$headingsText);
    $matches=[];
    foreach($terms as $term){
        $all=occurrences($bodyText,$term);
        $inHeadings=occurrences($headingText,$term);
        if($all)$matches[]="$term: caly tekst=$all, naglowki=$inHeadings";
    }
    echo "FRAZY: ".($matches?implode('; ',$matches):'brak sprawdzanych fraz')."\n";
    $counts[$path]=['h1'=>$h1,'h2'=>$h2,'h3'=>$h3];
}

echo "\n=======================================================\n";
echo "TYTULY WIZUALNE STRONY GLOWNEJ — RZECZYWISTY ZNACZNIK HTML\n";
if($homeDoc){
    $xpath=new DOMXPath($homeDoc);
    $found=[];
    foreach($xpath->query('//body//*') as $node){
        if(!$node instanceof DOMElement)continue;
        if(in_array(strtolower($node->tagName),['body','script','style'],true))continue;
        $content=oneLine($node->textContent,300);
        $target=null;
        foreach($visualCandidates as $candidate){
            if($content===$candidate){$target=$candidate;break;}
        }
        if($target===null)continue;
        $childAlsoMatches=false;
        foreach($node->childNodes as $child){
            if($child instanceof DOMElement && oneLine($child->textContent,300)===$target){$childAlsoMatches=true;break;}
        }
        if($childAlsoMatches)continue;
        $key=$target.'-'.spl_object_id($node);
        $found[$key]=$target;
        echo "  '".oneLine($target)."' = <".strtolower($node->tagName).">\n";
        echo "    Lokalizacja: ".snippetPath($node)."\n";
        $p=$node;
        for($i=0;$i<6&&$p instanceof DOMElement;$i++,$p=$p->parentNode){
            if($p->hasAttribute('data-builder-id')){
                echo "    ID buildera: ".oneLine($p->getAttribute('data-builder-id'),100)."\n";
                break;
            }
        }
        echo "    Styl elementu: ".oneLine($node->getAttribute('style'),180)."\n";
    }
    if(!$found)echo "  Nie znaleziono wymienionych tytulow w formie samodzielnych elementow.\n";
}
echo "\n=======================================================\n";
echo "BUILDER — AKTUALNE TAGI SEMANTYCZNE W BAZIE (ODCZYT)\n";
$db=connectReadOnly($root);
if($db){
    try {
        $query="SELECT pb.id AS builder_id, p.slug AS slug, pb.content, pb.type AS builder_type
                FROM page_builders pb
                LEFT JOIN pages p ON p.id=pb.page_id
                WHERE pb.published=1 AND (pb.type='home' OR p.slug IN (
                    'o-mnie','kontakt','klienci','fotografia-zywnosci',
                    'fotografia-kulinarna','food-styling',
                    'fotografia-produktowa-zywnosci','zdjecia-na-opakowania',
                    'fotografia-reklamowa-zywnosci'
                ))
                ORDER BY CASE WHEN pb.type='home' THEN 0 ELSE 1 END, p.slug";
        foreach($db->query($query)->fetchAll(PDO::FETCH_ASSOC) as $row){
            $content=json_decode((string)$row['content'],true);
            if(!is_array($content)||!is_array($content['sections']??null))continue;
            $sections=$content['sections'];
            echo "\n  BUILDER ID=".$row['builder_id']." SLUG=".($row['slug']??'STRONA-GLOWNA')." TYPE=".$row['builder_type']." SEKCJE=".count($sections)."\n";
            foreach($sections as $index=>$element){
                if(!is_array($element))continue;
                $type=$element['type']??'';
                $label=oneLine((string)($element['content']??''),130);
                $tag=$element['semantic_tag']??($element['heading_level']??'[DOMYSLNY]');
                $isHeadline=($type==='heading'||in_array($tag,['h1','h2','h3'],true));
                $isCandidate=false;
                foreach($visualCandidates as $c) {
                    if($label===oneLine($c,130)){$isCandidate=true;break;}
                }
                if(!$isHeadline&&!$isCandidate)continue;
                echo "    #".($index+1)." ID=".oneLine((string)($element['id']??''),50)
                   ." TYPE=".$type." TAG=".$tag
                   ." X=".($element['position_x']??'?')
                   ." Y=".($element['position_y']??'?')
                   ." TEXT=".$label."\n";
            }
        }
    }catch(Throwable $e){echo "  Brak dostepu do tabeli page_builders/pages.\n";}
}else echo "  Brak dostepu do danych; raport HTML powyzej pozostaje wazny.\n";
echo "\n=======================================================\n";
echo "PODSUMOWANIE: poprawnie odczytano ".count($counts)."/".count($urls)." stron.\n";
if($failures)echo "NIEODCZYTANE: ".implode(', ',$failures)."\n";
echo "KONIEC: ZADNYCH PLIKOW ANI REKORDOW NIE ZMIENIONO.\n";
