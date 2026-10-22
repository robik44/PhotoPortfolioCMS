<?php
/**
 * FoodFoto: restore easily accessible resize handle on the WHITE right-side
 * menu panel in the full/photo header visual builder, and add an EXIT-to-CMS
 * button. Leaves the compact editor, published header, settings and DB alone.
 *
 * Target: /admin/header-draft/preview
 * Source: resources/views/admin/draft-header/preview.blade.php
 *
 * Safe production patch. PHP 8.3. Does not require Composer/Artisan/git pull.
 * Creates byte-identical source backup, applies only 3 tiny anchored edits,
 * verifies result, automatically restores backup on any postflight failure.
 */

declare(strict_types=1);
$root = '/home/srv51861/domains/foodfoto.pl/laravel';
$relative = 'resources/views/admin/draft-header/preview.blade.php';
$file = $root.'/'.$relative;
$signature = 'FF_HERO_BUILDER_RESIZE_EXIT_20261022';

function abortHero(string $message): never {
    fwrite(STDERR,"PRZERWANO BEZ ZMIAN: ".$message."\n");
    exit(2);
}
if (!is_file($file) || !is_readable($file) || !is_writable($file)) {
    abortHero('Nie znaleziono dostepnego pliku '.$relative);
}
$original = file_get_contents($file);
if (!is_string($original) || strlen($original)<8000) abortHero('Szablon nie ma oczekiwanej zawartosci.');
if (str_contains($original,$signature)) {
    if (str_contains($original,'id="exit-hero-header-builder"')
        && str_contains($original,'ff-hero-menu-resize-handle')) {
        echo "GOTOWE WCZESNIEJ: uchwyt menu i przycisk wyjscia sa juz zainstalowane.\n";
        exit(0);
    }
    abortHero('Znaleziono fragmenty poprzedniej poprawki; nie powtarzam zapisu.');
}

// The menu must already exist in the fully featured editor. Do not replace
// that editor with another template or generic header.
$required = [
    'id="draft-main-header"',
    'class="header-actions" data-drag="menu-panel"',
    'class="builder-tools"',
    '<button type="button" id="save-layout">Zapisz układ</button>',
    'const menu=document.querySelector(',
    "if(id==='menu-panel')",
    'const resizing=ev.target===handle',
    "handle.className='resize-handle'",
    "e.setPointerCapture(ev.pointerId)",
    'id="viewport-stage"',
    'id="menu-corners"',
    'route(\'draft-header.positions\')',
    '</style></head>',
];
foreach($required as $token) {
    if(!str_contains($original,$token)) abortHero('Nie rozpoznano pelnego buildera lub obslugi skalowania menu: '.substr($token,0,60));
}

$saveAnchor = '<button type="button" id="save-layout">Zapisz układ</button>';
$exitHtml = <<<'BLADE'
<button type="button" id="save-exit-hero-header-builder" class="ff-hero-builder-save-exit" title="Zapisz układ i wróć do CMS">Zapisz i wyjdź</button>
<a id="exit-hero-header-builder" class="ff-hero-builder-exit" href="{{ route('dashboard') }}" title="Wróć do panelu CMS bez zapisywania zmian">Wyjdź do CMS ↗</a>
BLADE;

$css = <<<'CSS'

/* FF_HERO_BUILDER_RESIZE_EXIT_20261022
   Restore an always-visible, usable bottom-right grip only in the
   fullscreen PHOTO header editor (never on the live site). */
#draft-main-header [data-drag="menu-panel"] {
    position: relative;
}
#draft-main-header [data-drag="menu-panel"] > .resize-handle.ff-hero-menu-resize-handle {
    display: grid !important;
    place-items: center;
    right: -12px;
    bottom: -12px;
    width: 27px;
    height: 27px;
    z-index: 300;
    border: 2px solid #fff;
    border-radius: 5px;
    background: #246a43;
    color: #fff;
    box-shadow: 0 0 0 2px #246a43, 0 3px 10px #0005;
    cursor: nwse-resize;
    pointer-events: auto;
    touch-action: none;
    user-select: none;
}
#draft-main-header [data-drag="menu-panel"] > .resize-handle.ff-hero-menu-resize-handle::after {
    content: "↘";
    font: bold 16px/1 Arial, sans-serif;
}
#draft-main-header [data-drag="menu-panel"] > .resize-handle.ff-hero-menu-resize-handle:hover,
#draft-main-header [data-drag="menu-panel"] > .resize-handle.ff-hero-menu-resize-handle:focus-visible {
    background: #145033;
    outline: 2px solid #a1d1a5;
    outline-offset: 3px;
}
.builder-tools button.ff-hero-builder-save-exit {
    background: #286144;
    border: 1px solid #286144;
    color: #fff;
    font-weight: 700;
    padding: 10px 15px;
}
.builder-tools button.ff-hero-builder-save-exit:hover,
.builder-tools button.ff-hero-builder-save-exit:focus-visible {background: #17452e;}
.builder-tools .ff-hero-builder-exit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 10px 15px;
    border: 1px solid #9eafa1;
    border-radius: 7px;
    background: #eef3ed;
    color: #244734;
    font: bold 13px/1.2 Arial, sans-serif;
    text-decoration: none;
    white-space: nowrap;
}
.builder-tools .ff-hero-builder-exit:hover,
.builder-tools .ff-hero-builder-exit:focus-visible {
    border-color: #39734d;
    background: #ddeadd;
}
CSS;

// Add a class to the dynamically generated corner grip only for menu-panel.
// This does not change current resizing or saving JS.
$handleAnchor = "handle.className='resize-handle';handle.title='Zmień szerokość';";
$handleReplacement = "handle.className='resize-handle';if(id==='menu-panel'){handle.classList.add('ff-hero-menu-resize-handle');handle.title='Chwyć i przeciągnij, aby skalować całe białe menu';}else{handle.title='Zmień szerokość';}";
if (!str_contains($original,$handleAnchor)) abortHero('Kod tworzenia uchwytow w builderze jest inny.');

$exitScript = <<<'JS'
// Leave the full/photo header editor via a visible CMS action.
document.getElementById('save-exit-hero-header-builder').addEventListener('click',async()=>{
  const save=document.getElementById('save-layout');
  await save.onclick();
  if(status.textContent==='Zapisano ✓')window.location.assign(@json(route('dashboard')));
});
document.getElementById('exit-hero-header-builder').addEventListener('click',(event)=>{
  if(!window.confirm('Wyjść do CMS? Niezapisane zmiany nagłówka nie zostaną zachowane.'))event.preventDefault();
});
JS;

$replacements=[
    $saveAnchor => $saveAnchor."\n".$exitHtml,
    '</style></head>' => $css."\n</style></head>",
    $handleAnchor => $handleReplacement,
    "\n})();\n</script></body></html>" => "\n".$exitScript."\n})();\n</script></body></html>",
];
$changed=$original;
foreach($replacements as $anchor=>$replacement) {
    if(substr_count($changed,$anchor)!==1) abortHero('Znaleziono niejednoznaczny punkt modyfikacji.');
    $changed=str_replace($anchor,$replacement,$changed);
}
if($changed===$original) abortHero('Brak zmiany do zainstalowania.');

// Verify the patch is purely limited to this view and independently preserve
// position saves, undo/redo and image-height controls.
$invariants=[
    'id="undo-layout"',
    'id="redo-layout"',
    'id="hero-height-handle"',
    'id="brand-typography-controls"',
    "menu.style.zoom=",
    "positionsToSave",
    "route('draft-header.compact-editor')",
];
foreach($invariants as $fragment)
    if(!str_contains($changed,$fragment)) abortHero('Brakuje wymaganej funkcji edytora: '.substr($fragment,0,60));

$backupDir=$root.'/storage/app/backups';
if(!is_dir($backupDir) && !mkdir($backupDir,0700,true)) abortHero('Nie mozna utworzyc folderu kopii.');
$backup=$backupDir.'/photo-header-builder-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.blade.php';
if(file_put_contents($backup,$original,LOCK_EX)===false) abortHero('Nie mozna zapisac kopii szablonu.');
@chmod($backup,0600);
echo "KOPIA: $backup\n";

$tmp=$file.'.menu-fix-'.getmypid();
if(file_put_contents($tmp,$changed,LOCK_EX)===false) abortHero('Nie mozna zapisac pliku tymczasowego.');
@chmod($tmp,fileperms($file)&0777);
if(!rename($tmp,$file)){
    @unlink($tmp);
    abortHero('Nie udalo sie zastosowac poprawki.');
}
$verification=@file_get_contents($file);
if(!is_string($verification) || hash('sha256',$verification)!==hash('sha256',$changed)
    || substr_count($verification,$signature)!==1
    || substr_count($verification,'id="exit-hero-header-builder"')!==1
    || substr_count($verification,'id="save-exit-hero-header-builder"')!==1
    || substr_count($verification,'ff-hero-menu-resize-handle')<3) {
    @copy($backup,$file);
    fwrite(STDERR,"KONTROLA NIEUDANA: przywrocono kopie oryginalnego szablonu.\n");
    exit(3);
}

// The view compile cache must be refreshed without changing other web assets.
$viewDir=$root.'/storage/framework/views';
if(is_dir($viewDir)) {
    foreach(glob($viewDir.'/*.php')?:[] as $compiled)
        if(is_file($compiled))@unlink($compiled);
}
echo "SUKCES: Edytor naglowka ZE ZDJECIEM: widoczny uchwyt skalowania menu, 'Zapisz i wyjdz' i 'Wyjdz do CMS'.\n";
echo "MENU: przeciagnij zielony uchwyt z prawego dolnego rogu; skaluje caly panel i linki jak poprzednio.\n";
echo "Bez zmian w naglowku publicznym, bazie, zdjeciach, pozycjach, ustawieniach typografii oraz builderze uproszczonym.\n";
echo "Edytor: https://www.foodfoto.pl/admin/header-draft/preview\n";
