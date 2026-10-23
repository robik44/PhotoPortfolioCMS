<?php
/** Preview-only parity: description in editor gets the live hero's wide line wrap.
 * Public templates, SiteSetting records, and saved positions remain untouched.
 */
declare(strict_types=1);
$r='/home/srv51861/domains/foodfoto.pl/laravel';
$f=$r.'/resources/views/admin/draft-header/preview.blade.php';
function abortFF($message):never{fwrite(STDERR,"BEZ ZMIAN: $message\n");exit(2);}
$before=@file_get_contents($f);
if(!is_string($before)||strlen($before)<10000||!is_writable($f))abortFF('Nie znaleziono edytora.');
$tag='FF_PREVIEW_DESCRIPTION_PARITY_20261023';
if(str_contains($before,$tag)){echo "JUZ ZAINSTALOWANE\n";exit(0);}
$needle='checkpoint();history.length=0;sync();';
if(substr_count($before,$needle)!==1)abortFF('Inna wersja edytora — brak bezpiecznego punktu wstawienia.');
$replacement=<<<'JS'
checkpoint();history.length=0;sync();
// FF_PREVIEW_DESCRIPTION_PARITY_20261023
// The public hero has a wider paragraph flow than this editor's stored
// legacy width. Apply the public-view width in the preview only. The
// saved geometry and public CSS are NOT modified. Resizing in the editor
// still works; the drag handler clears the temporary display adjustment.
const ffDesc = byId.description;
if (ffDesc && document.getElementById('page-variant')?.value === 'home') {
  const requested = Number((stored.description || {}).w) || 560;
  if (requested < 850) {
    ffDesc.style.width = '850px';
    ffDesc.style.maxWidth = '850px';
    ffDesc.dataset.publicPreviewWidth = '850';
    ffDesc.addEventListener('pointerdown', function () {
      if (!ffDesc.dataset.publicPreviewWidth) return;
      ffDesc.style.maxWidth = '';
      delete ffDesc.dataset.publicPreviewWidth;
      // Editing begins from the actual saved geometry, never from an
      // unsaved preview-only width.
      apply(ffDesc, pos.description || {});
    }, { capture: true, once: true });
  }
}
JS;
$after=str_replace($needle,$replacement,$before);
$backdir=$r.'/storage/app/backups';
if(!is_dir($backdir)&&!mkdir($backdir,0700,true))abortFF('Brak katalogu kopii.');
$back=$backdir.'/header-preview-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($back,$before,LOCK_EX)===false)abortFF('Nie zapisano kopii.');
$tmp=$f.'.previewfix-'.getmypid();
if(file_put_contents($tmp,$after,LOCK_EX)===false)abortFF('Nie zapisano nowej wersji.');
@chmod($tmp,fileperms($f)&0777);
if(!rename($tmp,$f))abortFF('Nie podmieniono pliku.');
$out=[];$code=0;
exec('cd '.escapeshellarg($r).' && /opt/alt/php84/usr/bin/php artisan view:cache 2>&1',$out,$code);
if($code!==0){@copy($back,$f);exec('cd '.escapeshellarg($r).' && /opt/alt/php84/usr/bin/php artisan view:clear 2>&1');abortFF('Kompilacja nieudana, przywrocono kopie: '.implode(' ',$out));}
echo "SUKCES: dostosowano tylko sposob wyswietlania opisu w podgladzie edytora.\n";
echo "KOPIA: $back\n";
echo "Publiczna strona, dane i geometria pozostaly bez zmian.\n";
echo "Odswiez /admin/header-draft/preview i porownaj szerokosc opisu.\n";
