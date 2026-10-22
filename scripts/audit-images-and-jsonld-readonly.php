<?php
/** FoodFoto read-only audit: image SEO and schema on public homepage and six service pages.
 * No Laravel boot, no site files modified. Network read-only HTTP GET/HEAD.
 */
declare(strict_types=1);
$site='https://www.foodfoto.pl';
$routes=['/','/strona/fotografia-kulinarna','/strona/fotografia-produktowa-zywnosci','/strona/fotografia-reklamowa-zywnosci','/strona/food-styling','/strona/fotografia-zywnosci','/strona/zdjecia-na-opakowania'];
function requestFF(string $url,bool $head=false):array{
 $ch=curl_init($url);
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>4,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>18,CURLOPT_USERAGENT=>'FoodFotoAudit/2.0 (site owner diagnostic)',CURLOPT_NOBODY=>$head]);
 $data=curl_exec($ch);$info=curl_getinfo($ch);$err=curl_error($ch);curl_close($ch);
 return [(int)($info['http_code']??0),is_string($data)?$data:'',(int)($info['download_content_length']??-1),(int)($info['size_download']??0),$err,$info['url']??$url];
}
function tidyFF(string $s):string{return trim((string)preg_replace('/\s+/u',' ',html_entity_decode($s,ENT_QUOTES|ENT_HTML5,'UTF-8')));}
$all=[];$empty=[];$dupes=[];$issues=[];$photos=[];
foreach($routes as $route){
 [$status,$html,,, $error]=requestFF($site.$route);
 echo "\n=== ".($route==='/ '?"HOME":$route)." [HTTP $status] ===\n";
 if($status!==200){echo "Nie pobrano HTML: $error\n";continue;}
 $dom=new DOMDocument();
 $prev=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);libxml_clear_errors();libxml_use_internal_errors($prev);
 $nodes=$dom->getElementsByTagName('img');$local=[];
 foreach($nodes as $img){
  $src=$img->getAttribute('src');$alt=tidyFF($img->getAttribute('alt'));$loading=$img->getAttribute('loading');
  $w=$img->getAttribute('width');$h=$img->getAttribute('height');
  if($src==='')continue;
  $local[]=$src;
  if($alt==='')$empty[]=['route'=>$route,'src'=>$src];
  if(!isset($photos[$src]))$photos[$src]=['alt'=>$alt,'loading'=>$loading,'width'=>$w,'height'=>$h,'route'=>$route];
 }
 echo "Zdjecia: ".count($local)." | puste ALT: ".count(array_filter($local,fn($s)=>($photos[$s]['alt']??'')===''))."\n";
 $scripts=$dom->getElementsByTagName('script');$ldCount=0;
 foreach($scripts as $script){
  if(strtolower($script->getAttribute('type'))!=='application/ld+json')continue;
  $ldCount++;$json=trim($script->textContent);$result=json_decode($json);
  if(json_last_error()!==JSON_ERROR_NONE){$issues[]=$route.': niepoprawny JSON-LD: '.json_last_error_msg();continue;}
  $ctx=$result->{'@context'}??null;
  if($ctx===null)$issues[]=$route.': JSON-LD nie ma @context w obiekcie glownym';
 }
 echo "JSON-LD skrypty: $ldCount\n";
 if($ldCount===0)$issues[]=$route.': brak JSON-LD';
 $viewport=$dom->getElementsByTagName('meta');$robot='';
 foreach($viewport as $m)if(strtolower($m->getAttribute('name'))==='robots')$robot=$m->getAttribute('content');
 echo "Robots: $robot\n";
}
echo "\n=== PODSUMOWANIE OBRAZOW ===\nUnikalne obrazy: ".count($photos)."\nWystapienia pustego ALT: ".count($empty)."\n";
foreach(array_slice($empty,0,25) as $x)echo "ALT PUSTY: {$x['route']} | {$x['src']}\n";
echo "\n=== POMIAR ROZMIAROW (max 25 unikalnych obrazow z HTML) ===\n";
$checked=0;$total=0;
foreach($photos as $src=>$p){
 if($checked>=25)break;
 $url=str_starts_with($src,'https://')?$src:(str_starts_with($src,'/')?$site.$src:$site.'/'.$src);
 if(!str_starts_with($url,$site.'/'))continue;
 [$status,$body,$length,$download,$error]=requestFF($url,true);
 $bytes=max($length,0);$total+=$bytes;
 echo sprintf("%7.1f KB  HTTP %-3d  %-5s  %-8s  %s\n",$bytes/1024,$status,$p['loading']?:'-',($p['width']&&$p['height'])?'width+height':'no dims',$url);
 $checked++;
 if($status!==200)$issues[]='Obraz HTTP '.$status.': '.$url;
 if($bytes>600*1024)$issues[]='Duzy obraz (>600KB): '.$url;
}
echo "Zbadane obrazy: $checked; laczny rozmiar HEAD: ".round($total/1048576,2)." MB\n";
echo "\n=== WAZNE UWAGI ===\n";
if(!$issues)echo "Brak bledow w przeprowadzonych testach (nie oznacza pelnego audytu CWV).\n";
else foreach(array_slice($issues,0,50) as $x)echo "- $x\n";
echo "KONIEC: odczyt HTTP, bez zapisow na serwerze.\n";
