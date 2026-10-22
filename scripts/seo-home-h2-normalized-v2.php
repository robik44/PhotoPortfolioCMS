<?php
/** FoodFoto SEO homepage H2 fix v2; previously installed v1 must be present.
 * Normalize line breaks in saved headings, support heading or text blocks.
 * One file only. No database, CSS, responsive layout or builder modifications.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$file=$root.'/resources/views/components/public-builder-canvas.blade.php';
function abortFF(string $message):never {fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $message\n");exit(2);}
$old=@file_get_contents($file);
if(!is_string($old)||strlen($old)<12000||!is_writable($file))abortFF('Nie mozna odczytac szablonu.');
$marker='FF_HOME_TWO_TITLES_H2_20261022';
$v2='FF_HOME_TWO_TITLES_H2_V2_NORMALIZED';
if(str_contains($old,$v2)){echo "JUZ ZAINSTALOWANE: normalizowanie obu tytulow.\n";exit(0);}
$target=<<<'BLADE'
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
$replacement=<<<'BLADE'
            // FF_HOME_TWO_TITLES_H2_V2_NORMALIZED
            // Existing titles might be a "heading" block rather than "text";
            // titles also contain newlines / nonbreaking spaces in PageBuilder.
            // Do not modify any texts, styles, gallery cards, or saved records.
            if ($canvasId === 'home-public-builder-canvas'
                && in_array($type, ['text','heading'], true)
                && in_array($semanticTag, ['p','div','h3'], true)) {
                $titleText = strip_tags((string) $content);
                $titleText = html_entity_decode($titleText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $titleText = str_replace("\xC2\xA0", ' ', $titleText);
                $titleText = trim((string) preg_replace('/\s+/u', ' ', $titleText));
                if (in_array($titleText, ['Smak w kadrach', 'Smak ma swoją opowieść'], true)) {
                    $semanticTag = 'h2';
                }
            }
BLADE;
if(substr_count($old,$target)!==1)abortFF('Wersja poprzedniej poprawki rozni sie; niczego nie nadpisano.');
if(substr_count($old,'$semanticTag = in_array($requestedSemanticTag, $allowedSemanticTags, true)')!==1)
 abortFF('Nieoczekiwany mechanizm semantyki.');
$new=str_replace($target,$replacement,$old);
if($new===$old||substr_count($new,$v2)!==1)abortFF('Przygotowanie poprawki nie powiodlo sie.');
$php='/opt/alt/php84/usr/bin/php';
if(!is_executable($php))abortFF('Brak PHP 8.4.');
$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir)&&!mkdir($backupDir,0700,true))abortFF('Nie mozna utworzyc backup.');
$backup=$backupDir.'/seo-h2-normalized-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)abortFF('Nie zapisano kopii.');
$tmp=$file.'.seo-h2-v2-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)abortFF('Nie zapisano tymczasowego pliku.');
@chmod($tmp,fileperms($file)&0777);
if(!rename($tmp,$file))abortFF('Podmiana pliku nieudana.');
if(@file_get_contents($file)!==$new){@copy($backup,$file);abortFF('Kontrola zapisu nieudana, przywrocono kopie.');}
$out=[];$code=0;
exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:cache 2>&1',$out,$code);
if($code!==0){
    @copy($backup,$file);
    exec('cd '.escapeshellarg($root).' && '.escapeshellarg($php).' artisan view:clear 2>&1');
    fwrite(STDERR,"KOMPILACJA BLADE NIEUDANA, PRZYWROCONO KOPIE:\n".implode("\n",$out)."\n");
    exit(3);
}
echo "KOPIA: $backup\n";
echo "SUKCES: poprawiono dopasowanie obu tytulow do H2 z uwzglednieniem wielowierszowego tekstu i typu bloku.\n";
echo "Kompilacja Blade: OK (PHP 8.4).\n";
echo "Nie zmieniono danych, tekstow, styli, buildera, galerii, telefonu ani naglowka.\n";
echo "TERAZ SPRAWDZ HTML: \n";
echo "curl -fsSL 'https://www.foodfoto.pl/?seo_h2_check=".date('His')."' | grep -Eio '.{0,120}Smak w kadrach.{0,120}|.{0,120}Smak ma.{0,120}' | head -n 8\n";
