<?php
/**
 * FoodFoto mobile desktop composition v1.
 *
 * The previously installed JS scales an already mobile-reflowed document.
 * On phones request a 1400px CSS layout viewport instead; Safari/Chrome
 * shrink its visual rendering to the device width natively. All 1400px
 * desktop CSS breakpoints and positions remain intact.
 *
 * This modifies exactly two PUBLIC view files, with preflight/backups.
 * It never edits builder settings, photos or the header component.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$files=['resources/views/welcome.blade.php','resources/views/pages/show.blade.php'];
function stopMobile(string $msg):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $msg\n");exit(2);}
$old=[];$new=[];
$flag='FF_MOBILE_NATIVE_DESKTOP_VIEWPORT_20261022';
$meta='<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes, maximum-scale=8">';
$replaced='<meta name="viewport" content="width=1400, user-scalable=yes, maximum-scale=8"> {{-- '.$flag.' --}}';
$jsAnchor="        if (!window.matchMedia('(hover: none) and (pointer: coarse)').matches) return;";
foreach($files as $p){
    $f=$root.'/'.$p;
    $s=@file_get_contents($f);
    if(!is_string($s)||strlen($s)<10000||!is_writable($f))stopMobile('Szablon niedostepny: '.$p);
    if(str_contains($s,$flag)){echo "JUZ ZAINSTALOWANE: ".$p."\n";continue;}
    if(substr_count($s,$meta)!==1)stopMobile('Nieoczekiwany meta viewport: '.$p);
    if(substr_count($s,$jsAnchor)!==1)stopMobile('Nie rozpoznano starszego skryptu skalowania: '.$p);
    if(!str_contains($s,"root.className = 'ff-desktop-scale-root';"))stopMobile('Nie rozpoznano oryginalnego systemu mobilnego: '.$p);
    if(!str_contains($s,'FF_FIXED_1400_COMPOSITION_20261022'))stopMobile('Brak zainstalowanej stalej kompozycji: '.$p);
    $old[$p]=$s;
    $s=str_replace($meta,$replaced,$s);
    // Keep the old implementation in place for rollback, but never run it:
    // it would shrink the already natively scaled 1400px viewport twice.
    $s=str_replace($jsAnchor,
        "        // ".$flag.": native 1400px viewport provides whole-page fit.\n".
        "        // No JS wrapper; do not rescale the document a second time.\n".
        "        return;",
        $s);
    if(substr_count($s,$flag)!==2)stopMobile('Kontrola przygotowanej poprawki: '.$p);
    $new[$p]=$s;
}
if(!$new){echo "GOTOWE: obie strony juz korzystaja z nowego ukladu mobilnego.\n";exit(0);}
$backup=$root.'/storage/app/backups/mobile-native-1400-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($backup,0700,true))stopMobile('Nie mozna utworzyc katalogu kopii.');
foreach($old as $p=>$s){
    $f=$backup.'/'.$p;
    if(!is_dir(dirname($f))&&!mkdir(dirname($f),0700,true))stopMobile('Blad katalogu kopii.');
    if(file_put_contents($f,$s,LOCK_EX)===false)stopMobile('Nie zapisano kopii: '.$p);
}
echo "KOPIA: ".$backup."\n";
$written=[];
try{
    foreach($new as $p=>$s){
        $f=$root.'/'.$p;$tmp=$f.'.mobile-'.getmypid();
        if(file_put_contents($tmp,$s,LOCK_EX)===false)throw new RuntimeException('Zapis: '.$p);
        @chmod($tmp,fileperms($f)&0777);
        if(!rename($tmp,$f))throw new RuntimeException('Podmiana: '.$p);
        $written[]=$p;
        if(@file_get_contents($f)!==$s)throw new RuntimeException('Kontrola: '.$p);
    }
}catch(Throwable $e){
    foreach($written as $p)@copy($backup.'/'.$p,$root.'/'.$p);
    fwrite(STDERR,"BLAD; przywrocono poprzednie pliki: ".$e->getMessage()."\n");exit(3);
}
$cache=$root.'/storage/framework/views';
if(is_dir($cache))foreach(glob($cache.'/*.php')?:[] as $f)if(is_file($f))@unlink($f);
echo "SUKCES: telefon uklada cala strone w 1400 CSS px i skaluje ja natywnie do ekranu.\n";
echo "Mobilne reguly zmieniajace typografie, ilosc kolumn i ulozenie sekcji przy 520/900px nie beda juz uruchamiane.\n";
echo "Wylaczono stary mobilny JS, ktory skalowal gotowa strone drugi raz.\n";
echo "Bez zmian: builder, naglowek, dane, fotografie, pliki CSS i wersja komputerowa.\n";
echo "TEST: otworz strone w nowej karcie Safari iPhone, a potem sprawdz MacBooka.\n";
