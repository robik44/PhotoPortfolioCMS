<?php
/**
 * FoodFoto — read-only examination of production H1 templates and CSS.
 * No Laravel bootstrap, no files modified, no database access.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
$targets=[
 'resources/views/admin/components/draft-shared-header.blade.php',
 'resources/views/admin/pages/builder.blade.php'
];
function out(string $text):void {echo $text."\n";}
function snippet(string $file,array $numbers,int $radius=8,int $maxLines=100):void {
    $lines=@file($file);
    if(!is_array($lines)){out("BRAK: $file");return;}
    $keep=[];
    foreach($numbers as $n)for($i=max(0,$n-$radius);$i<=min(count($lines)-1,$n+$radius);$i++)$keep[$i]=true;
    ksort($keep);
    $shown=0;$previous=-2;
    foreach(array_keys($keep) as $i) {
        if($shown++ >= $maxLines){out("  ... ograniczono wynik ...");break;}
        if($i>$previous+1)out('  ...');
        out(sprintf('%4d  %s',$i+1,rtrim($lines[$i],"\r\n")));
        $previous=$i;
    }
}
foreach($targets as $file) {
    out("\n===== ZRODLO: $file =====");
    $lines=@file($file);
    if(!is_array($lines)){out('BRAK PLIKU');continue;}
    $matches=[];
    foreach($lines as $n=>$line) if(preg_match('~<\s*/?\s*h1\b|ff-shared-hero|ff-shared-stage~i',$line))$matches[]=$n;
    out('Liczba trafien: '.count($matches));
    // Prioritize the first H1 + nearest surrounding template and CSS.
    $h1=[];foreach($lines as $n=>$line)if(preg_match('~<\s*/?\s*h1\b~i',$line))$h1[]=$n;
    snippet($file,$h1,14,105);
    $related=[];foreach($lines as $n=>$line) {
        if(preg_match('~ff-shared-hero.*h1|h1.*ff-shared-hero|ff-shared-stage.*h1|h1.*ff-shared-stage~i',$line))$related[]=$n;
    }
    if($related){out('--- CSS wokol ff-shared-hero + h1 ---');snippet($file,array_slice($related,0,20),3,100);}
}
out("\n===== SKAD WLACZANY JEST BANER =====");
$roots=['resources/views','app'];
$count=0;
foreach($roots as $dir){
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
    foreach($it as $entry){
        if(!$entry->isFile()||$entry->getSize()>1200000)continue;
        if(!preg_match('/\.(?:blade\.php|php)$/',$entry->getFilename()))continue;
        $file=$entry->getPathname();
        $source=@file($file);if(!is_array($source))continue;
        foreach($source as $n=>$line){
            if(preg_match('~draft.shared.header|draft-shared-header|fve-header-variant-preview|ff-public-header~i',$line)
              && (preg_match('~include|view\(|render|component|<div|<section~i',$line) || str_contains($file,'draft-shared-header'))) {
                out(substr($file,strlen(getcwd())+1).':'.($n+1).' '.mb_substr(trim($line),0,230));
                if(++$count>=55)break 3;
            }
        }
    }
}
out("\n===== STYLE SELEKTOROW H1 (CSS i Blade, maks. 55 wpisow) =====");
$count=0;
foreach(['resources/views','public/css'] as $dir){
    if(!is_dir($dir))continue;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
    foreach($it as $entry){
        if(!$entry->isFile()||$entry->getSize()>1200000)continue;
        if(!preg_match('/\.(?:css|blade\.php)$/',$entry->getFilename()))continue;
        $source=@file($entry->getPathname());if(!is_array($source))continue;
        foreach($source as $n=>$line){
            if(preg_match('~(?:ff-shared-hero|ff-shared-stage|ff-public-header)[^\r\n]{0,160}\bh1\b|\bh1\b[^\r\n]{0,160}(?:ff-shared-hero|ff-shared-stage)~i',$line)){
                out(substr($entry->getPathname(),strlen(getcwd())+1).':'.($n+1).' '.mb_substr(trim($line),0,260));
                if(++$count>=55)break 3;
            }
        }
    }
}
out("\nKONIEC — tylko odczyt. Nie zapisano zadnych zmian.");
