<?php
/** FoodFoto SEO: limited semantic H2 for two existing homepage text titles.
 * No changes to PageBuilder JSON, CSS, photos, menu, mobile scaling or H3 cards.
 * Safe backup, guarded code anchors, atomic replacement and Blade compile test.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$rel='resources/views/components/public-builder-canvas.blade.php';
$file=$root.'/'.$rel;
function abortSeo(string $s):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $s\n");exit(2);}
$before=@file_get_contents($file);
if(!is_string($before)||strlen($before)<12000||!is_writable($file))abortSeo('Brak pliku szablonu.');
$marker='FF_HOME_TWO_TITLES_H2_20261022';
if(str_contains($before,$marker)){echo "JUZ ZAINSTALOWANE: dwa tytuly sa oznaczane jako H2.\n";exit(0);}
$anchor=<<<'BLADE'
            $semanticTag = in_array($requestedSemanticTag, $allowedSemanticTags, true)
                ? $requestedSemanticTag
                : 'div';
BLADE;
if(substr_count($before,$anchor)!==1||!str_contains($before,'@elseif($type === \'text\')')||
   !str_contains($before,'galleryTitleTag')||!str_contains($before,'$canvasId'))
  abortSeo('Inna wersja renderer-a. Nic nie zapisano.');
$extra=<<<'BLADE'

            // FF_HOME_TWO_TITLES_H2_20261022
            // Promote only these existing standalone homepage section titles.
            // Leave all saved builder JSON and gallery H3 subheadings untouched.
            if ($canvasId === 'home-public-builder-canvas'
                && $type === 'text'
                && $semanticTag === 'p') {
                $homeHeadingText = trim((string) preg_replace('/\s+/u', ' ', (string) $content));
                if (in_array($homeHeadingText, ['Smak w kadrach', 'Smak ma swoją opowieść'], true)) {
                    $semanticTag = 'h2';
                }
            }
BLADE;
$after=str_replace($anchor,$anchor.$extra,$before);
if(substr_count($after,$marker)!==1||$after===$before)abortSeo('Nie przygotowano jednoznacznej poprawki.');
$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))abortSeo('Nie mozna utworzyc katalogu backup.');
$backup=$backupDir.'/seo-two-h2-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$before,LOCK_EX)===false)abortSeo('Nie zapisano kopii.');
$temp=$file.'.seo-h2-'.getmypid();
if(file_put_contents($temp,$after,LOCK_EX)===false)abortSeo('Nie mozna przygotowac pliku.');
@chmod($temp,fileperms($file)&0777);
if(!rename($temp,$file)){@unlink($temp);abortSeo('Nie mozna zapisac szablonu.');}
if(@file_get_contents($file)!==$after){@copy($backup,$file);abortSeo('Kontrola nieudana; przywrocono poprzedni plik.');}
$command='cd '.escapeshellarg($root).' && php artisan view:cache 2>&1';
$logs=[];$code=0;
exec($command,$logs,$code);
if($code!==0){
    @copy($backup,$file);
    exec('cd '.escapeshellarg($root).' && php artisan view:clear 2>&1');
    fwrite(STDERR,"KONTROLA BLADE NIEUDANA. Przywrocono kopie.\n".implode("\n",$logs)."\n");
    exit(3);
}
echo "KOPIA: $backup\n";
echo "SUKCES: dwa tytuly tekstowe na stronie glownej beda renderowane jako H2 (jesli nadal maja dotychczasowe brzmienie).\n";
echo "H1, H3 galerii, opisy, style, geometria, naglowek, telefon, baza i ustawienia buildera pozostaja bez zmian.\n";
echo "Skladnia widokow Blade przeszla test kompilacji.\n";
echo "UWAGA: test kompilacji nie jest testem wizualnym ani potwierdzeniem wystapienia obu tytulow w biezacej bazie.\n";
