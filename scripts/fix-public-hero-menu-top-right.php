<?php
/**
 * FoodFoto: automatically anchor white menu capsule to upper-right of
 * PUBLIC photo header, matching the full-header builder across browsers.
 *
 * Safe, scoped patch: only shared public/draft render component;
 * editor itself, saved width, radius, logo geometry, db untouched.
 * Guarded one-file backup / atomic replace / result validation.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$relative='resources/views/admin/components/draft-shared-header.blade.php';
$path=$root.'/'.$relative;
function stopAnchor(string $message):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $message\n");exit(2);}
if(!is_file($path)||!is_readable($path)||!is_writable($path))stopAnchor('Nie mozna odczytac obecnego szablonu.');
$old=file_get_contents($path);
if(!is_string($old)||strlen($old)<10000)stopAnchor('Szablon niekompletny.');
$signature='FF_PUBLIC_FULL_HEADER_TOP_RIGHT_20261022';
if(str_contains($old,$signature)){echo "GOTOWE WCZESNIEJ: mocowanie menu w prawym gornym rogu jest juz zainstalowane.\n";exit(0);}
$before=<<<'JS'
        const finalX = minX <= maxX ? Math.min(maxX, Math.max(minX, x)) : maxX;
        const minY = (stageRect.top + 2 - base.top) / viewScale;
        const finalY = Math.max(minY, y);
        menu.style.transform = 'translate(' + finalX + 'px,' + finalY + 'px)';
JS;
$after=<<<'JS'
        // FF_PUBLIC_FULL_HEADER_TOP_RIGHT_20261022
        // On the PUBLIC hero (only), anchor to the right edge and to the top
        // of the photo, regardless of viewport width, browser or saved drag X.
        // Keep the saved menu scale as the preferred size; fitMenu still shrinks
        // it when needed to avoid collision with the left-hand logo.
        const publicHero = root.classList.contains('ff-public-header') && !isSimple;
        const finalX = publicHero
            ? maxX
            : (minX <= maxX ? Math.min(maxX, Math.max(minX, x)) : maxX);
        const minY = (stageRect.top + 2 - base.top) / viewScale;
        const finalY = publicHero
            ? (stageRect.top - base.top) / viewScale
            : Math.max(minY, y);
        menu.style.transform = 'translate(' + finalX + 'px,' + finalY + 'px)';
JS;
$before2=<<<'JS'
    const observer = new ResizeObserver(requestFit);
    observer.observe(stage);
JS;
$after2=<<<'JS'
    // Older Safari versions lack ResizeObserver: resize/load remain usable.
    if (typeof ResizeObserver !== 'undefined') {
        const observer = new ResizeObserver(requestFit);
        observer.observe(stage);
    }
JS;
if(substr_count($old,$before)!==1||substr_count($old,$before2)!==1)
    stopAnchor('Kod dopasowania menu jest inny niz spodziewany; bez nadpisywania.');
foreach(['#fve-header-variant-preview .ff-shared-menu{','class="ff-shared-stage', "const nativeWidth = menu.getBoundingClientRect().width / viewScale;"] as $check)
    if(!str_contains($old,$check))stopAnchor('Brakuje wymaganej funkcji: '.$check);
$new=str_replace([$before,$before2],[$after,$after2],$old);
if(substr_count($new,$signature)!==1||$new===$old)stopAnchor('Nie mozna przygotowac bezpiecznej poprawki.');
$folder=$root.'/storage/app/backups';
if(!is_dir($folder)&&!mkdir($folder,0700,true))stopAnchor('Nie mozna utworzyc katalogu kopii.');
$backup=$folder.'/public-hero-menu-top-right-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)stopAnchor('Nie zapisano kopii.');
@chmod($backup,0600);
echo "KOPIA: $backup\n";
$tmp=$path.'.right-anchor-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)stopAnchor('Nie mozna zapisac pliku tymczasowego.');
@chmod($tmp,fileperms($path)&0777);
if(!rename($tmp,$path)){@unlink($tmp);stopAnchor('Nie mozna zapisac poprawki.');}
$readback=@file_get_contents($path);
if(!is_string($readback)||hash('sha256',$readback)!==hash('sha256',$new)){
    @copy($backup,$path);
    fwrite(STDERR,"BLAD KONTROLI: przywrocono oryginalny szablon.\n");
    exit(3);
}
$views=$root.'/storage/framework/views';
if(is_dir($views))foreach(glob($views.'/*.php')?:[] as $f)if(is_file($f))@unlink($f);
echo "SUKCES: na publicznym naglowku ZE ZDJECIEM panel menu jest automatycznie mocowany do prawego gornego rogu.\n";
echo "Utrzymano proporcje menu; mechanizm fitMenu dopasowuje rozmiar tak, by unikac kolizji z logo.\n";
echo "Starsze Safari: bezpieczna obsluga braku ResizeObserver.\n";
echo "Bez zmian: baza, zapisane ustawienia buildera, menu uproszczone, fotografie i teksty.\n";
echo "Sprawdz teraz foodfoto.pl w Safari i Firefoksie oraz przy roznych szerokosciach okna.\n";
