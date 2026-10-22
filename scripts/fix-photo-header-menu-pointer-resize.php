<?php
/**
 * FoodFoto / header-with-photo: make the currently-visible green lower-right
 * menu resize handle ACTUALLY scale the entire white panel on mouse drag.
 *
 * PHP CLI 8.3, production SEOHOST. Patches only one already-installed Blade
 * template. No DB writes, no UI replacement, no public header modifications.
 * Makes private backup and aborts safely if expected prior fix is missing.
 */
declare(strict_types=1);

$root='/home/srv51861/domains/foodfoto.pl/laravel';
$relative='resources/views/admin/draft-header/preview.blade.php';
$file=$root.'/'.$relative;
$oldSign='FF_HERO_BUILDER_RESIZE_EXIT_20261022';
$newSign='FF_HERO_MENU_POINTER_FIX_20261022';

function failPointer(string $why):never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: ".$why."\n");
    exit(2);
}
if(!is_file($file)||!is_readable($file)||!is_writable($file))
    failPointer('Nie znaleziono rozbudowanego edytora naglowka ze zdjeciem.');
$old=file_get_contents($file);
if(!is_string($old)||strlen($old)<8000)failPointer('Szablon jest pusty lub niekompletny.');
if(str_contains($old,$newSign)){
    if(str_contains($old,"ffHandle.addEventListener('pointerdown',ffBeginResize,true)")){
        echo "GOTOWE WCZESNIEJ: niezalezny mechanizm przeciagania menu jest juz zainstalowany.\n";
        exit(0);
    }
    failPointer('Znaleziono niekompletna wersje poprawki. Brak zmian.');
}
$checks=[
    $oldSign,
    'ff-hero-menu-resize-handle',
    'id="save-exit-hero-header-builder"',
    'id="exit-hero-header-builder"',
    "const menu=document.querySelector('#draft-main-header .header-actions');",
    'const menuBaseWidth=menu.getBoundingClientRect().width;',
    "function setMenuAppearance(p)",
    "if(id==='menu-panel'){el.style.width='';setMenuAppearance(p)}",
    "const resizing=ev.target===handle",
    "const pos={}, selected=new Set(), history=[], future=[];",
    'function checkpoint(){',
    'function apply(el,p){',
    'function sync(){',
    'const clamp=(v,a,b)=>',
    'checkpoint();history.length=0;sync();',
    "document.getElementById('save-layout').onclick=async()=>",
];
foreach($checks as $str)if(substr_count($old,$str)!==1)
    failPointer('Rozpoznano inna wersje edytora; nie nadpisuje kodu: '.substr($str,0,45));

$anchor='checkpoint();history.length=0;sync();';

$js = <<<'JS'
// FF_HERO_MENU_POINTER_FIX_20261022
// Independent grip handler. The old delegated menu pointerdown could only
// paint a visible grip; it did not reliably start a resize in the full header.
// Capture pointerdown on the handle itself, stop its bubbling to menu drag,
// track movement on window, and reuse original apply() and save-layout.
const ffHandle=document.querySelector('#draft-main-header .header-actions > .resize-handle.ff-hero-menu-resize-handle');
if(ffHandle){
  ffHandle.setAttribute('role','button');
  ffHandle.setAttribute('tabindex','0');
  ffHandle.setAttribute('aria-label','Skaluj biały panel menu. Przeciągnij prawy dolny róg lub użyj strzałek.');
  const ffClampWidth=w=>clamp(
    Math.round(w),
    Math.round(menuBaseWidth*.55),
    Math.round(menuBaseWidth*1.8)
  );
  const ffActualWidth=()=>Number(pos['menu-panel']?.w)||menuBaseWidth;
  const ffUpdateWidth=(base,width)=>{
    const next=ffClampWidth(width);
    apply(menu,{...base,w:next});
    const percent=Math.round(next/menuBaseWidth*100);
    status.textContent='Białe menu: '+percent+'% — kliknij Zapisz układ';
  };
  function ffBeginResize(ev){
    if(ev.button!==0 || !ev.isPrimary)return;
    ev.preventDefault();
    ev.stopPropagation();
    checkpoint();
    selected.clear();selected.add('menu-panel');sync();
    const origin={...(pos['menu-panel']||{x:0,y:0})};
    const sx=ev.clientX,sy=ev.clientY;
    const initial=ffActualWidth();
    const pointer=ev.pointerId;
    let moved=false;
    // Listening on window, rather than on the zoomed/resized panel, avoids
    // losing the gesture when its boundary moves under the cursor.
    const ffOnMove=e=>{
      if(e.pointerId!==pointer)return;
      const dx=e.clientX-sx,dy=e.clientY-sy;
      if(Math.abs(dx)<1 && Math.abs(dy)<1)return;
      moved=true;
      const delta=Math.abs(dx)>=Math.abs(dy)?dx:dy;
      ffUpdateWidth(origin,initial+delta);
    };
    const ffStop=e=>{
      if(e && e.pointerId!==undefined && e.pointerId!==pointer)return;
      window.removeEventListener('pointermove',ffOnMove,true);
      window.removeEventListener('pointerup',ffStop,true);
      window.removeEventListener('pointercancel',ffStop,true);
      window.removeEventListener('blur',ffStop,true);
      if(moved)status.textContent='Zmieniono rozmiar białego menu — kliknij Zapisz układ';
      sync();
    };
    window.addEventListener('pointermove',ffOnMove,true);
    window.addEventListener('pointerup',ffStop,true);
    window.addEventListener('pointercancel',ffStop,true);
    window.addEventListener('blur',ffStop,true);
  }
  ffHandle.addEventListener('pointerdown',ffBeginResize,true);
  ffHandle.addEventListener('keydown',ev=>{
    if(!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(ev.key))return;
    ev.preventDefault();ev.stopPropagation();
    checkpoint();selected.clear();selected.add('menu-panel');
    const base={...(pos['menu-panel']||{x:0,y:0})};
    const plus=ev.key==='ArrowRight'||ev.key==='ArrowDown';
    ffUpdateWidth(base,ffActualWidth()+(plus?15:-15));
    sync();
  });
}
JS;

if(substr_count($old,$anchor)!==1)failPointer('Nie znaleziono jednoznacznego miejsca wstawienia.');
$new=str_replace($anchor,$anchor."\n".$js,$old);
if($new===$old || substr_count($new,$newSign)!==1 || !str_contains($new,"ffHandle.addEventListener('pointerdown',ffBeginResize,true)"))
    failPointer('Kontrola nowego kodu nie powiodla sie.');

$folder=$root.'/storage/app/backups';
if(!is_dir($folder) && !mkdir($folder,0700,true))failPointer('Nie mozna utworzyc katalogu kopii.');
$backup=$folder.'/hero-menu-pointer-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$old,LOCK_EX)===false)failPointer('Nie mozna zapisac kopii szablonu.');
@chmod($backup,0600);
echo "KOPIA: $backup\n";
$tmp=$file.'.ff-menu-pointer-'.getmypid();
if(file_put_contents($tmp,$new,LOCK_EX)===false)failPointer('Nie mozna zapisac tymczasowego kodu.');
@chmod($tmp,fileperms($file)&0777);
if(!rename($tmp,$file)){
    @unlink($tmp);
    failPointer('Zapis na serwerze nie powiodl sie.');
}
$current=@file_get_contents($file);
if(!is_string($current)||hash('sha256',$current)!==hash('sha256',$new)) {
    @copy($backup,$file);
    fwrite(STDERR,"KONTROLA NIEUDANA — przywrocono kopie.\n");
    exit(3);
}
$views=$root.'/storage/framework/views';
if(is_dir($views))foreach(glob($views.'/*.php')?:[] as $compiled)if(is_file($compiled))@unlink($compiled);
echo "SUKCES: podlaczono dzialajaca obsluge przeciagniecia zielonego uchwytu na bialym menu.\n";
echo "TEST: otworz edytor naglowka ze zdjeciem, wcisnij i trzymaj zielony uchwyt, przeciagaj w prawo/dol lub lewo/gore.\n";
echo "Rezerwowy test: kliknij uchwyt i nacisnij strzalke w prawo/lewo, zeby zwiekszyc/zmniejszyc panel o 15 px.\n";
echo "Aby utrwalic, kliknij Zapisz uklad (lub Zapisz i wyjdz).\n";
echo "Nie zmieniono bazy, ustawien naglowka, fotografii, publicznej strony ani edytora uproszczonego.\n";
