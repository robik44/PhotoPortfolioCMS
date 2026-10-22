<?php
/**
 * FoodFoto — public SEO audit; READ ONLY.
 * Runs under PHP 8.3 without Laravel, Composer or database access.
 */
declare(strict_types=1);
$base='https://www.foodfoto.pl';
$paths=['/','/o-mnie','/kontakt','/strona/klienci','/polityka-prywatnosci',
'/portfolio/zywnosc','/portfolio/stylizacja','/portfolio/opakowania','/portfolio/czasopisma',
'/strona/fotografia-zywnosci','/strona/fotografia-kulinarna','/strona/food-styling',
'/strona/fotografia-produktowa-zywnosci','/strona/zdjecia-na-opakowania',
'/strona/fotografia-reklamowa-zywnosci'];
if(!class_exists('DOMDocument')){fwrite(STDERR,"BRAK PHP DOM — potrzebny modul DOM.\n");exit(2);}
function loadPage(string $url): array {
    $cmd='curl -sSL --retry 0 --connect-timeout 8 --max-time 20'
       .' -A '.escapeshellarg('FoodFoto-SEO-ReadOnly-Audit/1.0')
       .' -w '.escapeshellarg("\n__STATUS__:%{http_code}")
       .' '.escapeshellarg($url).' 2>/dev/null';
    $out=shell_exec($cmd);
    if(!is_string($out)||!preg_match('/\n__STATUS__:(\d{3})\s*$/D',$out,$match))return [0,''];
    return [(int)$match[1],substr($out,0,-strlen($match[0]))];
}
function val(?DOMNode $node): string {return trim((string)preg_replace('/\s+/u',' ',$node?->textContent??''));}
function att(DOMElement $el,string $key): string {return trim($el->getAttribute($key));}
function lower(string $text): string {return mb_strtolower($text,'UTF-8');}
$all=[];
echo "FOODFOTO — PELNY PUBLICZNY AUDYT (ODCZYT, BEZ ZMIAN) ".date('c')."\n";
foreach($paths as $path){
    [$status,$html]=loadPage($base.$path);
    echo "\n======= $path HTTP $status =======\n";
    if($status!==200){echo "NIEZWERYFIKOWANA STRONA — status HTTP $status\n";usleep(300000);continue;}
    libxml_use_internal_errors(true);$dom=new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8"?>'.$html);libxml_clear_errors();
    $xp=new DOMXPath($dom);
    $title=val($xp->query('//title')->item(0));
    $meta=[];foreach($xp->query('//meta[@name]') as $node)$meta[lower(att($node,'name'))]=att($node,'content');
    $canonical='';foreach($xp->query('//link[@rel]') as $node){
        if(str_contains(' '.lower(att($node,'rel')).' ',' canonical ')){$canonical=att($node,'href');break;}
    }
    $h=[];foreach(['h1','h2','h3'] as $tag){
       $h[$tag]=[];foreach($xp->query('//'.$tag) as $node)$h[$tag][]=val($node);
    }
    echo 'TITLE ('.mb_strlen($title).'): '.($title?:'[PUSTY]')."\n";
    echo 'META DESCRIPTION ('.mb_strlen($meta['description']??'').'): '.($meta['description']??'[BRAK]')."\n";
    echo 'CANONICAL: '.($canonical?:'[BRAK]')."\n";
    echo 'ROBOTS: '.($meta['robots']??'[BRAK]')."\n";
    foreach($h as $tag=>$items)echo strtoupper($tag).' ('.count($items).'): '.(implode(' | ',$items)?:'[BRAK]')."\n";
    $missing=0;$empty=0;$count=0;
    foreach($xp->query('//img') as $img){
      $count++;
      if(!$img->hasAttribute('alt')){$missing++;echo '  BRAK ATRYBUTU ALT: '.substr(att($img,'src'),0,140)."\n";}
      elseif(att($img,'alt')===''){
          $empty++;
          if(att($img,'aria-hidden')!=='true' && att($img,'role')!=='presentation'){
              echo '  ALT PUSTY (oceń czy dekoracja): '.substr(att($img,'src'),0,140)."\n";
          }
      }
    }
    echo "OBRAZY: $count; BEZ ALT: $missing; ALT PUSTY: $empty\n";
    $body=val($xp->query('//body')->item(0));
    $suspect=[];foreach(['Nowy nagłówek','Nowy tekst','Galere-','lorem ipsum','worzę obrazy'] as $bad){
        if(mb_stripos($body,$bad)!==false)$suspect[]=$bad;
    }
    if($suspect)echo 'TESTOWE/LITEROWKI: '.implode(' | ',$suspect)."\n";
    $keys=['fotografia żywności','fotografia jedzenia','fotografia kulinarna','stylizacja żywności',
    'stylizacja jedzenia','stylizacja potraw','food styling','food stylistka','foodstylistka',
    'food stylista','foodstylista','food stylist','zdjęcia na opakowania','zdjęcia reklamowe'];
    $hits=[];foreach($keys as $key){
        $n=substr_count(lower($body),lower($key));if($n)$hits[$key]=$n;
    }
    echo 'FRAZY (tekst publiczny): '.json_encode($hits,JSON_UNESCAPED_UNICODE)."\n";
    $all[]=compact('path','title','meta','h','canonical','missing','empty');
    usleep(300000);
}
echo "\n======= DUPLIKATY TITLE / DESCRIPTION =======\n";
foreach(['title','description'] as $field){
  $groups=[];
  foreach($all as $row){
    $value=$field==='title'?$row['title']:($row['meta']['description']??'');
    if($value!=='')$groups[lower($value)][]=$row['path'];
  }
  foreach($groups as $urls)if(count($urls)>1)echo "POWTORZENIE $field: ".implode(' ; ',$urls)."\n";
}
echo "\n======= SITEMAP I ROBOTS =======\n";
foreach(['/robots.txt','/sitemap.xml'] as $p){
  [$status,$out]=loadPage($base.$p);echo "$p HTTP $status, rozmiar ".strlen($out)." bajtow\n";
  if($status===200)echo substr(trim(preg_replace('/\s+/',' ',$out)),0,250)."\n";
}
echo "\nKONIEC: nie wprowadzono zadnych zmian.\n";
