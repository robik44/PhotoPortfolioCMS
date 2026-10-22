<?php
/**
 * FoodFoto: one fixed 1400px desktop composition; on touch phones fit entire
 * 1400px layout to viewport using existing scaleDesktop() wrapper.
 * Desktop zoom leads to normal browser scrollbars rather than responsive
 * reflow. Fix full photo header/menu to design geometry and top-right anchor.
 * Only 3 public-view templates; NEVER alters builder, settings or photos.
 */
declare(strict_types=1);
$root='/home/srv51861/domains/foodfoto.pl/laravel';
$files=[
  'resources/views/welcome.blade.php',
  'resources/views/pages/show.blade.php',
  'resources/views/admin/components/draft-shared-header.blade.php',
];
function stopRigid(string $msg):never{fwrite(STDERR,"PRZERWANO BEZ ZMIAN: $msg\n");exit(2);}
$raw=[];
foreach($files as $name){
  $v=@file_get_contents($root.'/'.$name);
  if(!is_string($v)||strlen($v)<10000||!is_writable($root.'/'.$name))stopRigid("Nieoczekiwany/brakujacy plik: $name");
  if(str_contains($v,'FF_FIXED_1400_COMPOSITION_20261022'))stopRigid('Fragment poprawki juz jest w plikach; nie powtarzam zapisu.');
  $raw[$name]=$v;
}
$css=<<<'CSS'

<style id="ff-fixed-composition-20261022">
/* FF_FIXED_1400_COMPOSITION_20261022 — desktop layout is a stable canvas;
   browser may scroll at zoom above 100%, but must not rearrange the design. */
@media screen and (hover: hover) and (pointer: fine) {
  html,body {min-width:1400px!important;overflow-x:auto!important}
  body {width:1400px!important;margin:0!important}
  main.site-typography {width:1400px!important;min-width:1400px!important;max-width:none!important}
  #home-public-builder-canvas,#page-public-builder-canvas {
    width:1400px!important;min-width:1400px!important;max-width:none!important;
  }
}
</style>
CSS;
foreach(array_slice($files,0,2) as $path){
  $source=$raw[$path];
  $anchor='{{-- On touch screens preserve the 1400px desktop composition, fit it into';
  if(substr_count($source,$anchor)!==1)stopRigid('Nie rozpoznano mobilnego systemu skalowania w '.$path);
  if(!str_contains($source,'var factor = Math.min(1, viewportWidth / 1400);')
   || !str_contains($source,"root.style.zoom = String(factor);")
   || !str_contains($source,"root.className = 'ff-desktop-scale-root';")
   || !str_contains($source,'user-scalable=yes'))
      stopRigid('Mobilne dopasowanie strony jest inne niz oczekiwane w '.$path);
  $raw[$path]=str_replace($anchor,$css."\n    ".$anchor,$source);
}
$component=$raw[$files[2]];
$styleAnchor="\n</style>\n<script>\ndocument.addEventListener('DOMContentLoaded', function () {";
$fixedHeaderCss=<<<'CSS'

/* FF_FIXED_1400_COMPOSITION_20261022
   Keep public PHOTO header at its original 1400px design size in every
   desktop browser, even when viewport media queries would otherwise reflow.
   Phone layout uses the same 1400px composition scaled as one whole. */
#fve-header-variant-preview.ff-public-header .ff-shared-stage.ff-full{
  width:1400px!important;min-width:1400px!important;max-width:none!important;
  height:var(--ff-hero-height,650px)!important;min-height:0!important;
  overflow:hidden!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-bar{
  width:1400px!important;min-width:1400px!important;max-width:none!important;
  display:flex!important;flex-wrap:nowrap!important;
  align-items:center!important;justify-content:space-between!important;
  gap:30px!important;padding:12px 4vw!important;min-height:84px!important;
  box-sizing:border-box!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-brand{
  flex-shrink:0!important;max-width:none!important;transform:translate(var(--ff-brand-x),var(--ff-brand-y))!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-menu{
  position:relative!important;flex:0 0 auto!important;
  flex-wrap:nowrap!important;margin-left:auto!important;
  max-width:none!important;white-space:nowrap!important;
  padding:8px 10px 8px 26px!important;
  justify-content:initial!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-menu-links{
  flex-wrap:nowrap!important;gap:22px!important;
  padding:12px 6px!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-name strong{
  font-size:var(--ff-name,36px)!important;
  max-width:none!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-hero{
  position:absolute!important;left:4%!important;top:177px!important;
  margin:0!important;max-width:820px!important;
}
#fve-header-variant-preview.ff-public-header .ff-full .ff-shared-hero h1{
  font-size:var(--fs,83px)!important;
  max-width:var(--lw,850px)!important;
}
CSS;
if(substr_count($component,$styleAnchor)!==1)stopRigid('Inny blok CSS w publicznym naglowku.');
$component=str_replace($styleAnchor,"\n".$fixedHeaderCss.$styleAnchor,$component);

$jsAnchor="        const barRect = bar.getBoundingClientRect();";
$fixedMenuJS=<<<'JS'
        // One fixed public photo-header composition across browsers. Preserve
        // the preferred builder menu width, then anchor its edge to the right
        // of the 1400px design stage. No collision/reflow on window resize.
        if (root.classList.contains('ff-public-header') && !isSimple) {
            menu.style.zoom = String(zoom);
            menu.style.transform = 'none';
            const capsule = menu.getBoundingClientRect();
            const right = (stageRect.right - capsule.right) / viewScale;
            const top = (stageRect.top - capsule.top) / viewScale;
            menu.style.transform = 'translate(' + right + 'px,' + top + 'px)';
            return;
        }
JS;
if(substr_count($component,$jsAnchor)!==1)stopRigid('Nie rozpoznano obliczen pozycji panelu menu.');
$component=str_replace($jsAnchor,$fixedMenuJS."\n".$jsAnchor,$component);
$observerAnchor="    const observer = new ResizeObserver(requestFit);\n    observer.observe(stage);";
$observerNew="    if (typeof ResizeObserver !== 'undefined') {\n        const observer = new ResizeObserver(requestFit);\n        observer.observe(stage);\n    }";
// Both variants are safe: older component with unconditional observer and
// newer component where the Safari compatibility guard is already present.
if(substr_count($component,$observerAnchor)===1){
    $component=str_replace($observerAnchor,$observerNew,$component);
}elseif(str_contains($component,"if (typeof ResizeObserver !== 'undefined') {")
    && str_contains($component,"const observer = new ResizeObserver(requestFit);")
    && str_contains($component,"observer.observe(stage);")){
    echo "SAFARI: istniejace zabezpieczenie ResizeObserver zostaje bez zmian.\\n";
}else{
    stopRigid('Nie rozpoznano obecnej obslugi Safari.');
}
$raw[$files[2]]=$component;
$backup=$root.'/storage/app/backups/fixed-canvas-20261022-'.date('Ymd-His').'-'.bin2hex(random_bytes(3));
if(!mkdir($backup,0700,true))stopRigid('Nie mozna utworzyc kopii.');
foreach($files as $path){
  $before=@file_get_contents($root.'/'.$path);
  $dest=$backup.'/'.$path;
  if(!is_dir(dirname($dest))&&!mkdir(dirname($dest),0700,true))stopRigid('Nie mozna zrobic podkatalogu kopii.');
  if(file_put_contents($dest,$before,LOCK_EX)===false)stopRigid('Nie zapisano kopii '.$path);
}
echo "KOPIA: $backup\n";
$written=[];
try{
 foreach($files as $path){
   $target=$root.'/'.$path;$tmp=$target.'.ff-fixed-'.getmypid();
   if(file_put_contents($tmp,$raw[$path],LOCK_EX)===false)throw new RuntimeException('Zapis tymczasowy '.$path);
   @chmod($tmp,fileperms($target)&0777);
   if(!rename($tmp,$target))throw new RuntimeException('Podmiana '.$path);
   $written[]=$path;
   if(hash('sha256',file_get_contents($target))!==hash('sha256',$raw[$path]))
     throw new RuntimeException('Kontrola '.$path);
 }
}catch(Throwable $e){
 foreach($written as $path)@copy($backup.'/'.$path,$root.'/'.$path);
 fwrite(STDERR,"BŁĄD; przywrocono kopie: ".$e->getMessage()."\n");exit(3);
}
$views=$root.'/storage/framework/views';
if(is_dir($views))foreach(glob($views.'/*.php')?:[] as $v)if(is_file($v))@unlink($v);
echo "SUKCES: kompozycja komputerowa 1400 px, przegladarka moze przewijac przy zoomie.\n";
echo "TELEFON: pozostawiono skalowanie CALOSCI strony do szerokosci telefonu, tak jak na komputerze.\n";
echo "BIALE MENU: nie przelicza juz skali w zaleznosci od wolnego miejsca; trzyma prawy gorny rog naglowka.\n";
echo "NIE ZMIENIONO: buildera, zapisanych pozycji, fotografii, bazy, naglowka uproszczonego.\n";
echo "SPRAWDZ: Safari i Firefox 100%, 125%, 150% oraz telefon pion/poziom.\n";
