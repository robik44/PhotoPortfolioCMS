<?php
/**
 * FoodFoto: Safari photo fallback + responsive gallery mosaic,
 * and safer public header menu wrapping on narrow desktop browsers.
 *
 * Production-safe, guarded. No database, settings or visual builder writes.
 * Exactly three narrowly-scoped files. Backups and rollback.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
chdir($root);
function stopFix(string $text):never {fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $text\n");exit(2);}
$paths=[
'resources/views/components/builder-gallery-mosaic.blade.php',
'public/css/foodfoto-gallery-mosaic.css',
'resources/views/components/foodfoto-published-header.blade.php',
];
$old=[];
foreach($paths as $p){$v=@file_get_contents($root.'/'.$p);if(!is_string($v)||strlen($v)<1000||!is_writable($root.'/'.$p))stopFix("Plik niezgodny lub niedostepny: $p");$old[$p]=$v;}
$marker='FF_SAFARI_RESPONSIVE_20261022';
foreach($old as $s)if(str_contains($s,$marker))stopFix('Poprawka juz obecna lub fragmentaryczna; bez nadpisywania.');

$imgAnchor='<img class="ff-mosaic-image" src="{{ $photo->imageUrl() }}" alt="{{ $photo->alt ?: ($photo->title ?: $cardTitle) }}" loading="lazy" decoding="async" style="object-position:{{ $imageX }}% {{ $imageY }}%;">';
$imgNew='<img class="ff-mosaic-image" src="{{ $photo->imageUrl() }}" data-fallback-src="{{ asset(\'storage/\'.\App\Models\Photo::storagePath($photo->filename)) }}" onerror="if(this.dataset.fallbackSrc &amp;&amp; this.src !== this.dataset.fallbackSrc){this.onerror=null;this.src=this.dataset.fallbackSrc;}" alt="{{ $photo->alt ?: ($photo->title ?: $cardTitle) }}" loading="lazy" decoding="async" style="object-position:{{ $imageX }}% {{ $imageY }}%;">';
$gallery=$old[$paths[0]];
if(substr_count($gallery,$imgAnchor)!==1)stopFix('Szablon mozaiki galerii ma inna wersje tagu obrazka.');
$gallery=str_replace($imgAnchor,$imgNew,$gallery);
if(!str_contains($gallery,'data-fallback-src=')||!str_contains($gallery,'onerror='))stopFix('Nie przygotowano awaryjnego formatu fotografii.');

$css=$old[$paths[1]];
foreach(['.ff-mosaic-desktop-fixed','.ff-mosaic-grid','.ff-mosaic-header','@media(max-width:750px)'] as $v)
    if(!str_contains($css,$v))stopFix('Nieoczekiwana wersja stylow galerii: '.$v);
$css .= <<<'CSS'


/* FF_SAFARI_RESPONSIVE_20261022 — non-destructive gallery layout fallback.
   Preserve the editorial six-card composition on full-size desktops.
   Override only when actual CSS viewport is too narrow. */
@media screen and (max-width: 1100px) {
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-header {
    grid-template-columns: minmax(0,1fr) minmax(0,1fr);
    gap: clamp(12px,2vw,24px);
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-more {
    justify-self: start;
    white-space: normal;
    grid-column: 1 / -1;
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-grid {
    grid-template-columns: repeat(3,minmax(0,1fr));
    grid-template-rows: repeat(2,minmax(180px, var(--fm-row)));
    grid-template-areas: 'a b c' 'd e f';
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-heading {
    font-size: min(var(--fm-title), clamp(25px,3.7vw,46px));
  }
}
@media screen and (max-width: 700px) {
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-header {
    grid-template-columns: minmax(0,1fr);
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-more {
    grid-column: 1;
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-grid {
    grid-template-columns: repeat(2,minmax(0,1fr));
    grid-template-rows: repeat(3,minmax(160px,auto));
    grid-template-areas: 'a b' 'c d' 'e f';
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-card-meta {
    padding: 9px;
  }
  .ff-mosaic.ff-mosaic-desktop-fixed .ff-mosaic-intro {
    max-width: 60ch;
  }
}
CSS;
$header=$old[$paths[2]];
$headAnchor='@media(max-width:900px) and (hover:hover){.ff-public-sticky .ff-shared-bar{flex-wrap:wrap;gap:5px}.ff-public-sticky .ff-menu-links{flex-wrap:wrap;gap:8px}.ff-public-sticky .ff-shared-menu{max-width:100%;margin:0;flex-wrap:wrap}.ff-public-sticky .ff-shared-name strong{font-size:var(--ffc-title-font,28px)!important}}';
if(substr_count($header,$headAnchor)!==1)stopFix('Nie rozpoznano aktualnego bloku responsywnosci naglowka.');
$headerReplacement=$headAnchor."\n".<<<'CSS'
        /* FF_SAFARI_RESPONSIVE_20261022: responsive sticky bar on mouse,
           trackpad AND touch devices. Do not change saved design coordinates. */
        @media screen and (max-width: 1200px) {
          .ff-public-sticky .ff-shared-bar {gap:clamp(8px,1.4vw,22px);padding-left:2.5vw;padding-right:2.5vw}
          .ff-public-sticky .ff-shared-menu {max-width:100%;min-width:0;padding:8px 12px}
          .ff-public-sticky .ff-menu-links {gap:clamp(7px,1vw,17px);min-width:0}
        }
        @media screen and (max-width: 900px) {
          .ff-public-sticky .ff-shared-bar {flex-wrap:wrap;gap:10px}
          .ff-public-sticky .ff-shared-menu {margin-left:0;flex:1 1 100%;justify-content:space-between;flex-wrap:wrap}
          .ff-public-sticky .ff-menu-links {flex-wrap:wrap;gap:10px}
        }
CSS;
$header=str_replace($headAnchor,$headerReplacement,$header);
$updated=[$paths[0]=>$gallery,$paths[1]=>$css,$paths[2]=>$header];
foreach($updated as $p=>$v)if($v===$old[$p])stopFix('Brak zmian w '.$p);
$backup=$root.'/storage/app/backups/safari-responsive-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($backup,0700,true))stopFix('Nie mozna utworzyc kopii.');
foreach($old as $p=>$v){$target=$backup.'/'.$p;if(!is_dir(dirname($target))&&!mkdir(dirname($target),0700,true))stopFix('Problem z kopia katalogu.');if(file_put_contents($target,$v,LOCK_EX)===false)stopFix('Nie zapisano kopii: '.$p);}
echo "KOPIA: $backup\n";
$written=[];
try{
    foreach($updated as $p=>$v){
        $target=$root.'/'.$p;$tmp=$target.'.safari-fix-'.getmypid();
        if(file_put_contents($tmp,$v,LOCK_EX)===false)throw new RuntimeException('Nie mozna zapisac '.$p);
        @chmod($tmp,fileperms($target)&0777);
        if(!rename($tmp,$target))throw new RuntimeException('Nie mozna podmienic '.$p);
        $written[]=$p;
        if(hash('sha256',file_get_contents($target))!==hash('sha256',$v))
            throw new RuntimeException('Kontrola po zapisie nie przeszla dla '.$p);
    }
}catch(Throwable $e){
    foreach($written as $p)@copy($backup.'/'.$p,$root.'/'.$p);
    fwrite(STDERR,"PRZERWANO: ".$e->getMessage()."; przywrocono kopie plikow.\n");
    exit(3);
}
$views=$root.'/storage/framework/views';
if(is_dir($views))foreach(glob($views.'/*.php')?:[] as $v)if(is_file($v))@unlink($v);
echo "SUKCES: galeria korzysta z oryginalnego pliku, gdy preferowany obraz WebP nie zaladuje sie w starszej przegladarce.\n";
echo "SUKCES: mozaika ma elastyczny uklad do 1100px i 700px; sticky menu ma poprawione odstepy na mniejszych ekranach.\n";
echo "BEZ ZMIAN: baza, pozycje, wyglad na szerokim ekranie, naglowek w builderze, tresci i pozostale szablony.\n";
echo "UWAGA: to pierwszy etap zgodnosci. Sprawdz wizualnie Safari, Firefox, laptop i telefon.\n";
