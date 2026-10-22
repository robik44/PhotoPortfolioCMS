<?php
/** FoodFoto read-only diagnosis: locate duplicate public H1 before editing. PHP 8.3. */
declare(strict_types=1);
chdir('/home/srv51861/domains/foodfoto.pl/laravel');
if (!class_exists('DOMDocument')) {fwrite(STDERR,"BRAK PHP DOM\n");exit(2);}
$urls=[
 '/' => 'https://www.foodfoto.pl/',
 '/o-mnie' => 'https://www.foodfoto.pl/o-mnie',
 '/kontakt' => 'https://www.foodfoto.pl/kontakt',
 '/strona/klienci' => 'https://www.foodfoto.pl/strona/klienci',
 '/portfolio/zywnosc' => 'https://www.foodfoto.pl/portfolio/zywnosc',
 '/polityka-prywatnosci' => 'https://www.foodfoto.pl/polityka-prywatnosci'
];
function shortText(string $str,int $max=220):string{
    return mb_substr(trim(preg_replace('/\s+/u',' ',$str)??$str),0,$max);
}
foreach($urls as $name=>$url){
    echo "\n=== $name ===\n";
    $cmd='curl -fsSL --connect-timeout 8 --max-time 20 -A '.
         escapeshellarg('FoodFoto-H1-ReadOnly-Diagnostic').' '.escapeshellarg($url).
         ' 2>/dev/null';
    $html=shell_exec($cmd);
    if(!is_string($html)||strlen($html)<200){echo "BRAK ODCZYTU\n";continue;}
    libxml_use_internal_errors(true);
    $dom=new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8"?>'.$html);
    libxml_clear_errors();
    $xp=new DOMXPath($dom);
    foreach($xp->query('//h1') as $index=>$node){
        echo "H1 #".($index+1).": ".shortText($node->textContent)."\n";
        echo "  HTML: ".shortText($dom->saveHTML($node),400)."\n";
        $p=$node;$path=[];
        for($i=0;$i<7&&$p instanceof DOMElement;$i++,$p=$p->parentNode){
            $seg=strtolower($p->tagName);
            $class=$p->getAttribute('class');$id=$p->getAttribute('id');
            if($id!=='')$seg.='#'.shortText($id,60);
            if($class!=='')$seg.='.'.str_replace(' ','.',shortText($class,90));
            array_unshift($path,$seg);
        }
        echo "  Rodzice: ".implode(' > ',$path)."\n";
    }
}
echo "\n=== SZABLONY Z H1 (tylko odczyt) ===\n";
$paths=[
'resources/views/components/site-header.blade.php',
'resources/views/components/public-builder-canvas.blade.php',
'resources/views/pages/show.blade.php',
'resources/views/portfolio/gallery.blade.php',
'resources/views/about.blade.php',
'resources/views/contact.blade.php',
'resources/views/privacy.blade.php',
'resources/views/welcome.blade.php'
];
foreach($paths as $path){
    if(!is_file($path)){echo "BRAK $path\n";continue;}
    $source=file($path);
    foreach($source as $i=>$line){
        if(preg_match('~<\s*h1\b|</\s*h1\s*>|site-header|site_brand_h1~i',$line)){
            if(!preg_match('~<\s*h1\b|</\s*h1\s*>~i',$line))continue;
            echo "$path:".($i+1).": ".shortText($line,200)."\n";
        }
    }
}
echo "\nKONIEC: diagnostyka nie zmienila ani jednego pliku.\n";
