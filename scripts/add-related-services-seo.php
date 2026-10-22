<?php
/** FoodFoto: SEO related services below PageBuilder, outside its geometry.
 * Exactly six slugs. Guards, backup, PHP 8.4 Blade compilation and rollback.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$p='resources/views/pages/show.blade.php';
$f=$root.'/'.$p;
function stopRelated(string $s):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
$old=@file_get_contents($f);
if(!is_string($old)||strlen($old)<10000||!is_writable($f))stopRelated('Nie mozna odczytac szablonu podstron.');
$marker='FF_RELATED_SERVICES_SEO_20261023';
if(str_contains($old,$marker)){echo "JUZ ZAINSTALOWANE: Zobacz rowniez.\n";exit(0);}
$anchor="\n</main>\n\n\n@include('components.site-footer'";
if(substr_count($old,$anchor)!==1||!str_contains($old,"'canvasId' => 'page-public-builder-canvas'"))
 stopRelated('Nieznany uklad szablonu; bez zmian.');
$code=<<<'BLADE'

{{-- FF_RELATED_SERVICES_SEO_20261023 — static semantic links only, outside the builder. --}}
@php
    $ffRelatedMap = [
        'fotografia-kulinarna' => [
            ['food-styling', 'Stylizacja żywności'],
            ['fotografia-reklamowa-zywnosci', 'Fotografia reklamowa'],
        ],
        'fotografia-produktowa-zywnosci' => [
            ['zdjecia-na-opakowania', 'Zdjęcia na opakowania'],
            ['fotografia-reklamowa-zywnosci', 'Fotografia reklamowa'],
        ],
        'fotografia-reklamowa-zywnosci' => [
            ['fotografia-kulinarna', 'Fotografia kulinarna'],
            ['fotografia-produktowa-zywnosci', 'Fotografia produktowa'],
        ],
        'food-styling' => [
            ['fotografia-kulinarna', 'Fotografia kulinarna'],
            ['fotografia-zywnosci', 'Fotografia żywności'],
        ],
        'fotografia-zywnosci' => [
            ['fotografia-produktowa-zywnosci', 'Fotografia produktowa'],
            ['fotografia-kulinarna', 'Fotografia kulinarna'],
        ],
        'zdjecia-na-opakowania' => [
            ['fotografia-produktowa-zywnosci', 'Fotografia produktowa'],
            ['food-styling', 'Stylizacja żywności'],
        ],
    ];
    $ffRelated = $ffRelatedMap[$page->slug] ?? [];
@endphp
@if(count($ffRelated) > 0)
    <nav class="ff-related-services" aria-label="Powiązane usługi">
        <div class="ff-related-services__inner">
            <h2>Zobacz również</h2>
            <div class="ff-related-services__links">
                @foreach($ffRelated as [$ffSlug, $ffLabel])
                    <a href="{{ url('/strona/'.$ffSlug) }}">{{ $ffLabel }} <span aria-hidden="true">→</span></a>
                @endforeach
            </div>
        </div>
    </nav>
    <style>
        /* Only in this six-page component; stable 1400px design canvas. */
        .ff-related-services {width:1400px;max-width:none;margin:0 auto;background:#f9f9f6;box-sizing:border-box;border-top:1px solid #ecece7}
        .ff-related-services__inner {padding:38px 56px 46px}
        .ff-related-services h2 {font:400 29px/1.2 Georgia,serif;color:#282b25;margin:0 0 18px}
        .ff-related-services__links {display:flex;flex-wrap:wrap;gap:14px 24px}
        .ff-related-services__links a {font:400 14px/1.4 Arial,sans-serif;color:#384a37;text-decoration:none;border-bottom:1px solid #889384;padding:5px 0}
        .ff-related-services__links a:hover,.ff-related-services__links a:focus-visible {color:#1e3d24;border-color:#1e3d24}
        .ff-related-services__links span {margin-left:7px}
    </style>
@endif
BLADE;
$new=str_replace($anchor,"\n</main>\n".$code."\n@include('components.site-footer'",$old);
if($new===$old||substr_count($new,$marker)!==1)stopRelated('Nie przygotowano poprawki.');
$php='/opt/alt/php84/usr/bin/php';
if(!is_executable($php))stopRelated('Nie znaleziono PHP 8.4.');
$dir=$root.'/storage/app/backups';
if(!is_dir($dir)&&!mkdir($dir,0700,true))stopRelated('Brak katalogu kopii.');
$backup=$dir.'/seo-related-services-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)stopRelated('Kopia nieudana.');
$tmp=$f.'.related-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)stopRelated('Blad zapisu tymczasowego.');
@chmod($tmp,fileperms($f)&0777);
if(!rename($tmp,$f)){@unlink($tmp);stopRelated('Nie mozna podmienic szablonu.');}
if(@file_get_contents($f)!==$new){@copy($backup,$f);stopRelated('Blad kontroli zapisu; kopia przywrocona.');}
$out=[];$status=0;
exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:cache 2>&1',$out,$status);
if($status!==0){
 @copy($backup,$f);
 exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:clear 2>&1');
 fwrite(STDERR,"KOMPILACJA NIEUDANA, PRZYWROCONO KOPIE:\n".implode("\n",$out)."\n");exit(3);
}
echo "KOPIA: $backup\n";
echo "SUKCES: dodano sekcje Zobacz rowniez na 6 podstronach uslugowych.\n";
echo "Kazda usluga ma dwa powiazane linki. Inne strony pozostaja bez zmian.\n";
echo "PHP 8.4 Blade compile: OK.\n";
echo "Nie zmieniono PageBuildera, pozycji elementow, zdjec, bazy ani stylow naglowka.\n";
echo "Sprawdz wyglad uslug na MacBooku i telefonie, a nastepnie linki na publicznych stronach.\n";
