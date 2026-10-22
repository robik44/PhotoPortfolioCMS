<?php
/** Read-only HTML audit of ALT + image dimensions on FoodFoto. Never modifies site files. */
declare(strict_types=1);
$pages=['/','/strona/fotografia-kulinarna','/strona/fotografia-produktowa-zywnosci','/strona/fotografia-reklamowa-zywnosci','/strona/food-styling','/strona/fotografia-zywnosci','/strona/zdjecia-na-opakowania'];
$site='https://www.foodfoto.pl';
echo "AUDYT ALT / WYMIARY — TYLKO ODCZYT\n";
foreach($pages as $path) {
 $ch=curl_init($site.$path.'?ff_audit_alt=20261023');
 curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_USERAGENT=>'FoodFotoOwnerAudit/1.0']);
 $html=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);
 echo "\n=== $path (HTTP $http) ===\n";
 if($http!==200||!is_string($html)){echo "Brak HTML: $err\n";continue;}
 $dom=new DOMDocument();$prior=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html);libxml_clear_errors();libxml_use_internal_errors($prior);
 $total=0;$missingAlt=0;$missingDim=0;
 foreach($dom->getElementsByTagName('img') as $img) {
  $src=$img->getAttribute('src');if(!$src)continue;
  $total++;
  $alt=trim($img->getAttribute('alt'));
  $dimensions=$img->hasAttribute('width')&&$img->hasAttribute('height')&&(int)$img->getAttribute('width')>0&&(int)$img->getAttribute('height')>0;
  $isEmpty=$alt==='';
  if($isEmpty)$missingAlt++;if(!$dimensions)$missingDim++;
  if(!$isEmpty&&$dimensions)continue;
  $parent=$img->parentNode;
  $ancestors=[];
  for($i=0;$i<3&&$parent instanceof DOMElement;$i++,$parent=$parent->parentNode){
   $ancestors[]=$parent->tagName.($parent->hasAttribute('class')?'.'.str_replace(' ','|',$parent->getAttribute('class')):'');
  }
  $decorative=$img->getAttribute('aria-hidden')==='true'||str_contains(strtolower($src),'botanical');
  echo '  SRC: '.$src."\n";
  echo '    ALT: '.($isEmpty?'[PUSTY]':mb_substr($alt,0,110)).($decorative?' [MOZLIWA DEKORACJA]':'')."\n";
  echo '    Wymiary HTML: '.($dimensions?$img->getAttribute('width').'x'.$img->getAttribute('height'):'BRAK')."\n";
  echo '    CSS: '.mb_substr(preg_replace('/\s+/',' ',$img->getAttribute('style')),0,160)."\n";
  echo '    Klasa: '.$img->getAttribute('class')." | Rodzice: ".implode(' > ',$ancestors)."\n";
 }
 echo "PODSUMOWANIE: obrazow=$total; pusty ALT=$missingAlt; brak poprawnej pary width/height=$missingDim\n";
}
echo "\nUWAGA: pusty ALT dekoracji jest prawidlowy, a CSS z zarezerwowana przestrzenia moze ograniczac CLS nawet bez width/height.\n";
echo "KONIEC. Nie zmieniono zadnych danych ani plikow witryny.\n";
