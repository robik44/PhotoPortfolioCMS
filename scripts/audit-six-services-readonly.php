<?php
/** FoodFoto SEO: READ-ONLY live audit, 6 public service pages and sitemap.
 * No Laravel boot, no DB writes, no files modified inside website.
 */
declare(strict_types=1);
$base='https://www.foodfoto.pl';
$services=[
'fotografia-kulinarna'=>'Fotografia kulinarna',
'fotografia-produktowa-zywnosci'=>'Fotografia produktowa',
'fotografia-reklamowa-zywnosci'=>'Fotografia reklamowa',
'food-styling'=>'Stylizacja żywności',
'fotografia-zywnosci'=>'Fotografia żywności',
'zdjecia-na-opakowania'=>'Zdjęcia na opakowania',
];
function retrieve(string $url):array {
  $tmp=tempnam(sys_get_temp_dir(),'ffseo');
  if(!$tmp) return ['',0,'brak temp'];
  $cmd='curl -sSL --max-time 18 --connect-timeout 7 -A '.escapeshellarg('Mozilla/5.0 (compatible; FoodFotoSEOCheck/1.0)').' -o '.escapeshellarg($tmp).' -w '.escapeshellarg('%{http_code}|%{url_effective}').' '.escapeshellarg($url).' 2>&1';
  $result=trim((string)shell_exec($cmd));$raw=(string)@file_get_contents($tmp);@unlink($tmp);
  if(preg_match('/(\d{3})\|(https?:\/\/[^\s]+)/',$result,$m))return [$raw,(int)$m[1],$m[2]];
  return [$raw,0,$result];
}
function prop(string $html,string $attr,string $name):string {
  $pattern='~<meta\b[^>]*>~is';
  preg_match_all($pattern,$html,$tags);
  foreach($tags[0] as $tag) {
    if(!preg_match('~\b'.preg_quote($attr,'~').'\s*=\s*(["\'])'.preg_quote($name,'~').'\1~i',$tag))continue;
    if(preg_match('~\bcontent\s*=\s*(["\'])(.*?)\1~is',$tag,$m)) return html_entity_decode(trim($m[2]),ENT_QUOTES|ENT_HTML5,'UTF-8');
  }
  return '';
}
function canonical(string $html):string {
  preg_match_all('~<link\b[^>]*>~is',$html,$tags);
  foreach($tags[0] as $tag)if(preg_match('~\brel\s*=\s*(["\'])canonical\1~i',$tag)
   &&preg_match('~\bhref\s*=\s*(["\'])(.*?)\1~is',$tag,$m))return html_entity_decode(trim($m[2]),ENT_QUOTES|ENT_HTML5,'UTF-8');
  return '';
}
function headings(string $html,string $tag):array {
  preg_match_all('~<'.$tag.'\b[^>]*>(.*?)</'.$tag.'>~is',$html,$matches);
  return array_map(static fn($s)=>trim((string)preg_replace('/\s+/u',' ',strip_tags(str_ireplace(['<br>','<br/>','<br />'],' ',$s)))), $matches[1]);
}
echo "FOODFOTO — audyt publicznych podstron (tylko odczyt)\n";
echo "DATA: ".date('c')."\n\n";
$codes=[];$details=[];
foreach($services as $slug=>$label){
  $url=$base.'/strona/'.$slug;
  [$html,$status,$final]=retrieve($url);
  $codes[$slug]=$status;
  echo "=============================================\n".$label."\n";
  echo "URL: ".$url."\nSTATUS: ".$status." | ".$final."\n";
  if($status!==200||trim($html)===''){echo "UWAGA: brak wiarygodnego HTML do oceny\n\n";continue;}
  preg_match('~<title\b[^>]*>(.*?)</title>~is',$html,$title);
  $ttl=html_entity_decode(trim(strip_tags($title[1]??'')),ENT_QUOTES|ENT_HTML5,'UTF-8');
  echo "TITLE (".mb_strlen($ttl)." zn.): ".$ttl."\n";
  $description=prop($html,'name','description');
  echo "DESCRIPTION (".mb_strlen($description)." zn.): ".$description."\n";
  echo "ROBOTS META: ".(prop($html,'name','robots')?:'[brak]')."\n";
  echo "CANONICAL: ".(canonical($html)?:'[brak]')."\n";
  foreach(['h1','h2','h3'] as $h){$list=headings($html,$h);echo strtoupper($h)." (".count($list)."):\n";foreach(array_slice($list,0,12) as $val)echo "  - ".$val."\n";}
  preg_match_all('~<a\b[^>]*href\s*=\s*(["\'])(.*?)\1~is',$html,$links);
  $same=[];
  foreach($links[2]??[] as $href){
    $href=html_entity_decode($href,ENT_QUOTES|ENT_HTML5,'UTF-8');
    if(str_starts_with($href,'/strona/')||str_starts_with($href,$base.'/strona/'))$same[]=$href;
  }
  echo "LINKI DO INNYCH USLUG: ".implode(', ',array_unique($same))."\n\n";
}
echo "=============================================\nSITEMAP\n";
[$sitemap,$status,$final]=retrieve($base.'/sitemap.xml');
echo "STATUS: $status | $final\n";
if($status===200){foreach($services as $slug=>$label)echo $label.': '.(str_contains($sitemap,'/strona/'.$slug)?'JEST':'BRAK W SITEMAP')."\n";}
else echo "Nie zweryfikowano sitemap.\n";
echo "\nROBOTS.TXT\n";
[$robots,$rs,$rf]=retrieve($base.'/robots.txt');
echo "STATUS: $rs | $rf\n";
if($rs===200)echo substr($robots,0,2000)."\n";
echo "\nKONIEC DIAGNOSTYKI (nie zmieniono zadnych plikow ani danych).\n";
