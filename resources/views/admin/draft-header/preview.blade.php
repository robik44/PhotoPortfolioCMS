<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive"><meta name="csrf-token" content="{{ csrf_token() }}"><title>Testowy nagłówek FoodFoto</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f5f3ee;color:#26241f;font-family:Georgia,serif}
.preview-alert{padding:12px 4vw;background:#233b30;color:#fff;font:14px/1.4 Arial,sans-serif;display:flex;gap:24px;justify-content:space-between;align-items:center}
.preview-alert a{color:white}.scene{position:relative;min-height:650px;background:#ebe7df;overflow:hidden}
.scene>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:var(--hero-x) center}
.scene:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(245,243,238,.84),rgba(245,243,238,.38) 44%,transparent 75%);pointer-events:none}
.site-head{position:relative;z-index:2;display:flex;align-items:center;justify-content:space-between;gap:var(--gap);padding:12px 4vw;min-height:var(--height)}
.brand{display:flex;align-items:center;gap:14px;flex-shrink:0;text-decoration:none;color:#171717;background:rgba(var(--logo-rgb),var(--logo-opacity));padding:12px 20px;border-radius:8px}
.brand img{max-height:56px;width:auto;max-width:80px;object-fit:contain;mix-blend-mode:multiply}.brand .divider{width:1px;align-self:stretch;background:#aaa;min-height:36px}.brand strong{font:400 24px Georgia,serif;white-space:nowrap}.brand small{display:block;font:9px Arial,sans-serif;letter-spacing:.16em;margin-top:5px}
nav{display:flex;gap:20px;align-items:center;flex-wrap:wrap;background:var(--menu-bg);padding:21px 28px;border-radius:var(--radius)}
nav a{font:11px Arial,sans-serif;text-transform:uppercase;letter-spacing:.1em;color:#222;text-decoration:none;white-space:nowrap}
.hero-copy{position:relative;z-index:1;margin:75px 0 0 8%;max-width:650px}
.hero-copy h1{font:400 clamp(45px,5vw,83px)/.98 Georgia,serif;letter-spacing:-.04em;margin:0 0 24px}
.hero-copy p{font:16px/1.65 Arial,sans-serif;max-width:420px}
@media(max-width:1050px){.site-head{gap:12px;flex-wrap:wrap}.brand{padding:9px}.brand strong{font-size:20px}nav{gap:12px;padding:14px}.hero-copy{margin:70px 6vw}}
@media(max-width:600px){.site-head{background:rgba(255,255,255,.94)}.brand strong{font-size:17px}nav{width:100%;justify-content:center}nav a{font-size:10px}.scene{min-height:700px}.hero-copy{margin-top:80px}.hero-copy h1{font-size:48px}}

.builder-tools{position:relative;z-index:20;background:#fff;padding:10px 4vw;display:flex;gap:12px;align-items:center;font:13px Arial,sans-serif;flex-wrap:wrap;border-bottom:1px solid #ddd}
.builder-tools button{padding:9px 14px;background:#223e2f;color:white;border:0;border-radius:7px;cursor:pointer}.builder-tools button.secondary{background:#eee;color:#222}
.builder-tools output{font-weight:600}
[data-drag]{position:relative;cursor:move;touch-action:none}
[data-drag].selected{outline:1px dashed #76985f;outline-offset:5px}
.alignment-guide{position:fixed;z-index:100;pointer-events:none;background:#72a164;display:none}.alignment-guide.vertical{width:1px;top:0;bottom:0}.alignment-guide.horizontal{height:1px;left:0;right:0}
.site-head{justify-content:space-between;gap:30px}
.brand{padding:8px 4px 8px 0}.brand img{mix-blend-mode:normal}
.brand .divider{margin:0 5px}.brand strong{font-size:25px;font-weight:400}
.header-actions{display:flex;align-items:center;gap:8px;background:var(--menu-bg);border-radius:var(--radius);padding:8px 10px 8px 26px;margin-left:auto}
.header-actions .menu-drag-handle{font:20px Arial,sans-serif;color:#7c8379;cursor:grab;touch-action:none;user-select:none;flex-shrink:0}
.header-actions nav{background:transparent;padding:12px 6px;gap:22px;border-radius:0}
.header-actions .contact-button{white-space:nowrap;display:block;border:1px solid #343b34;padding:14px 22px;border-radius:999px;font:11px Arial,sans-serif;letter-spacing:.12em;text-decoration:none;color:#333}
.hero-copy{margin:62px 0 0 4%;max-width:820px}
.hero-copy .eyebrow{font:11px/1.4 Arial,sans-serif;letter-spacing:.38em;color:#4f584b;margin:0 0 20px}
.hero-copy h1{white-space:pre-line;font-size:clamp(51px,5.2vw,88px);line-height:1.04}
.hero-copy p{max-width:560px}
.hero-cta{display:inline-block;background:#597649;color:#fff;text-decoration:none;border-radius:100px;padding:17px 32px;font:12px Arial,sans-serif;letter-spacing:.12em;margin-top:19px}
@media(max-width:1100px){.header-actions{flex-wrap:wrap}.hero-copy{max-width:80%}}
@media(max-width:650px){.header-actions{width:100%;margin:0;flex-wrap:wrap;justify-content:center}.hero-copy{max-width:90%;margin:60px 5%}.hero-copy h1{font-size:clamp(42px,9vw,65px)}}

.builder-tools input,.builder-tools select{padding:6px;border:1px solid #bbb;border-radius:4px}
#viewport-stage{margin:0 auto;max-width:100%;transition:width .2s;overflow:hidden}
.ruler-horizontal{height:20px;position:relative;background:repeating-linear-gradient(90deg,#c4c9c0 0 1px,transparent 1px 10px),#f3f5f1;background-size:auto 9px;background-repeat:repeat-x;border-bottom:1px solid #b9c2b8;font:10px Arial,sans-serif;overflow:hidden}
.ruler-vertical{position:absolute;left:0;z-index:10;width:20px;height:650px;background:repeating-linear-gradient(180deg,#c4c9c0 0 1px,transparent 1px 10px),#f3f5f1;background-size:9px auto;background-repeat:repeat-y;pointer-events:none}
.resize-handle{position:absolute;width:13px;height:13px;right:-8px;bottom:-8px;background:#507446;border:2px solid #fff;box-shadow:0 0 0 1px #507446;cursor:nwse-resize;touch-action:none;display:none;z-index:30}
[data-drag].selected>.resize-handle{display:block}

/* Sticky compact version is a separate visual clone; no edit handles or live links. */
.preview-content{min-height:1000px;padding:95px 7%;background:#f8f7f3;color:#333}
.preview-content h2{font:400 44px Georgia,serif}.preview-content p{font:16px/1.6 Arial,sans-serif}
.preview-content-spacer{height:650px}
.compact-header{position:fixed;top:0;left:0;right:0;z-index:90;min-height:64px;display:flex;align-items:center;justify-content:space-between;gap:22px;padding:6px 4vw;background:#f8f7f3;border-bottom:1px solid #dedbd5;box-shadow:0 3px 12px rgba(0,0,0,.04);opacity:0;visibility:hidden;transform:translateY(-100%);transition:transform .25s,opacity .25s,visibility .25s;pointer-events:none}
.compact-header.visible{opacity:1;visibility:visible;transform:translateY(0);pointer-events:auto}
.compact-header .brand{background:transparent!important;transform:none!important;padding:0!important;gap:10px}
.compact-header .brand img{max-height:38px}.compact-header .brand strong{font-size:18px}.compact-header .brand small{font-size:7px}
.compact-header .header-actions{transform:none!important;border:1px solid #dedbd5;background:#fff;padding:3px 12px;border-radius:8px 8px 24px 40px}
.compact-header .header-actions nav{transform:none!important;gap:14px;padding:8px 4px}
.compact-header .header-actions nav a{font-size:10px}
.compact-header .contact-button{transform:none!important;padding:10px 14px;font-size:10px}
.compact-header [data-drag]{cursor:default;touch-action:auto}.compact-header .resize-handle,.compact-header .menu-drag-handle{display:none!important}
#viewport-stage.inner-page .scene{min-height:180px;background:#f8f7f3}
#viewport-stage.inner-page .scene>img,#viewport-stage.inner-page .scene:after,#viewport-stage.inner-page .hero-copy{display:none}
#viewport-stage.inner-page .site-head{background:#f8f7f3;align-items:center;padding-top:20px;padding-bottom:20px}
#viewport-stage.inner-page .site-head .header-actions{border:1px solid #dedbd5;background:#fff;border-radius:8px 8px 24px 40px;box-shadow:0 3px 12px rgba(0,0,0,.03)}
@media(max-width:900px){.compact-header{flex-wrap:wrap}.compact-header .header-actions{flex-wrap:wrap;max-width:100%}.compact-header nav{flex-wrap:wrap}}
@media(prefers-reduced-motion:reduce){.compact-header{transition:none}}
.brand-image-piece{display:inline-flex;align-items:center;justify-content:center;flex:0 0 auto;width:58px;min-width:25px}
.brand-image-piece img{display:block;width:100%;max-width:none!important;max-height:none!important;height:auto}
.brand-name-piece{display:flex;flex-direction:column;min-width:max-content}
.brand-name-piece strong,.brand-name-piece small{display:block;white-space:nowrap}
.brand .divider{flex:0 0 1px}
.brand[data-drag="brand"]{min-width:0}
.brand[data-drag="brand"].selected{outline-offset:8px}
.brand-image-piece .resize-handle,.brand-name-piece .resize-handle{bottom:-9px}
.compact-header .brand-image-piece{width:38px!important}
.compact-header .brand-image-piece img{width:100%}
.compact-header .brand-name-piece{width:auto!important}
</style></head>
<body>
<div class="preview-alert"><span>WERSJA TESTOWA — widoczna tylko po zalogowaniu. Strona publiczna nie jest zmieniona.</span><a href="{{ route('draft-header.edit') }}">← Edytuj nagłówek</a></div>
@php
    $logoHex = ltrim($settings['draft_header_logo_background'], '#');
    $logoRgb = implode(',', array_map('hexdec', str_split($logoHex, 2)));
@endphp
<div class="builder-tools">
<strong>BUILDER NAGŁÓWKA</strong>
<span>Przeciągaj zaznaczone elementy • linie pomagają wyrównywać</span>
<label>Strona <select id="page-variant"><option value="home">Strona główna</option><option value="inner">Podstrony (jasne tło)</option></select></label>
<button type="button" id="compact-demo" class="secondary">Pokaż kompaktowy</button>
<button type="button" id="save-layout">Zapisz układ</button>
<button type="button" id="undo-layout" class="secondary">↶ Cofnij</button>
<button type="button" id="redo-layout" class="secondary">↷ Ponów</button>
<label>Widok <select id="viewport-mode"><option value="100%">Komputer</option><option value="820px">Tablet</option><option value="390px">Telefon</option></select></label>
<label>Rozmiar pisma <input id="font-size" type="number" min="8" max="120" step=".1" style="width:65px" disabled></label>
<label>Odstęp liter <input id="letter-space" type="number" min="-2" max="20" step=".1" style="width:60px" disabled></label>
<label>Interlinia <input id="line-height" type="number" min=".7" max="3" step=".05" style="width:60px" disabled></label>
<button type="button" class="secondary" id="reset-layout">Wyzeruj przesunięcia</button>
<output id="builder-status" role="status">Gotowy</output>
</div>
<div class="ruler-horizontal" id="ruler-h"></div><div class="ruler-vertical" id="ruler-v"></div>
<div class="alignment-guide vertical" id="guide-x"></div><div class="alignment-guide horizontal" id="guide-y"></div>
@php
$draftPositions = json_decode($settings['draft_header_builder_positions'] ?? '{}', true) ?: [];
$draftLogo = 'branding/foodfoto-transparent-v2.png';
@endphp
<div id="viewport-stage"><div class="scene" style="--hero-x:{{ (int)$settings['draft_header_hero_x'] }}%;--logo-rgb:{{ $logoRgb }};--logo-opacity:{{ (int)$settings['draft_header_logo_opacity'] / 100 }};--gap:{{ (int)$settings['draft_header_gap'] }}px;--radius:{{ (int)$settings['draft_header_radius'] }}px;--height:{{ (int)$settings['draft_header_height'] }}px;--logo-bg:{{ $settings['draft_header_logo_background'] }};--menu-bg:{{ $settings['draft_header_menu_background'] }}">
<img src="{{ $heroUrl }}" alt="Zdjęcie górnego banera">
<header class="site-head" id="draft-main-header">
<div class="brand" data-drag="brand">
@if(file_exists(storage_path('app/public/'.$draftLogo)))
<span class="brand-image-piece" data-drag="brand-image"><img src="{{ asset('storage/'.$draftLogo) }}" alt="FoodFoto"></span>
@elseif(!empty($header['header_brand_image']))
<span class="brand-image-piece" data-drag="brand-image"><img src="{{ asset('storage/'.$header['header_brand_image']) }}" alt="FoodFoto"></span>
@else<strong>food<br>foto</strong>@endif
<span class="divider" data-drag="brand-divider"></span><span class="brand-name-piece" data-drag="brand-text"><strong>{{ $header['logo'] }}</strong><small>{{ $header['logo_subtitle'] }}</small></span>
</div>
<div class="header-actions" data-drag="menu-panel" aria-label="Przeciągnij całe menu za tło lub uchwyt">
<span class="menu-drag-handle" aria-label="Przesuń całą kapsułę" title="Przeciągnij całą kapsułę">⠿</span>
<nav data-drag="navlinks" aria-label="Menu (podgląd)">
@forelse($menuItems as $item)
<a href="#" onclick="return false">{{ $item->label ?? $item->title ?? 'Menu' }}</a>
@empty<a href="#">Portfolio</a><a href="#">O mnie</a><a href="#">Kontakt</a>@endforelse
</nav>
<a class="contact-button" data-drag="contact" href="#" onclick="return false">{{ $settings['draft_header_contact_text'] }}</a>
</div></header>
<div class="hero-copy">
<div class="eyebrow" data-drag="eyebrow">{{ $settings['draft_header_eyebrow'] }}</div>
<h1 data-drag="headline">{{ $settings['draft_header_hero_title'] }}</h1>
<p data-drag="description">{{ $settings['draft_header_hero_description'] }}</p>
<a href="#" onclick="return false" class="hero-cta" data-drag="cta">{{ $settings['draft_header_cta_text'] }}　 →</a>
</div>
</div></div>
<div class="preview-content"><h2>Dalsza część strony — podgląd przewijania</h2><p>Przewiń w dół, aby sprawdzić kompaktowy, jasny nagłówek. Ta sekcja jest widoczna tylko w builderze.</p><div class="preview-content-spacer"></div></div>
<script>
(() => {
const stored=@json($draftPositions), els=[...document.querySelectorAll('[data-drag]')], byId=Object.fromEntries(els.map(e=>[e.dataset.drag,e]));
const pos={}, selected=new Set(), history=[], future=[];
const status=document.getElementById('builder-status'), gx=document.getElementById('guide-x'),gy=document.getElementById('guide-y');
const size=document.getElementById('font-size'),spacing=document.getElementById('letter-space'),line=document.getElementById('line-height');
const clamp=(v,a,b)=>Math.min(b,Math.max(a,v));
const stage=document.getElementById('viewport-stage');
const pageSelect=document.getElementById('page-variant');
const compact=document.createElement('div');compact.className='compact-header';compact.setAttribute('aria-label','Podgląd kompaktowego nagłówka');
const originalHeader=document.getElementById('draft-main-header');
const logoClone=originalHeader.querySelector('.brand').cloneNode(true);
const menuClone=originalHeader.querySelector('.header-actions').cloneNode(true);
compact.append(logoClone,menuClone);
compact.querySelectorAll('[data-drag],.selected,.resize-handle').forEach(el=>{el.removeAttribute('data-drag');el.classList.remove('selected');if(el.classList.contains('resize-handle'))el.remove();else{el.style.transform='';el.style.width='';el.style.fontSize='';el.style.letterSpacing='';el.style.lineHeight='';}});
document.body.appendChild(compact);
let forceCompact=false;
function updateCompact(){const shouldShow=forceCompact||pageSelect.value==='inner'||window.scrollY>120;compact.classList.toggle('visible',shouldShow);}
pageSelect.onchange=()=>{stage.classList.toggle('inner-page',pageSelect.value==='inner');forceCompact=false;updateCompact()};
document.getElementById('compact-demo').onclick=()=>{forceCompact=!forceCompact;updateCompact();};
window.addEventListener('scroll',updateCompact,{passive:true});
updateCompact();
function snapshot(){return JSON.stringify(pos)}
function checkpoint(){history.push(snapshot());if(history.length>60)history.shift();future.length=0}
function apply(el,p){const id=el.dataset.drag;pos[id]={...p};el.style.transform='translate('+(p.x||0)+'px,'+(p.y||0)+'px)';
 el.style.width=p.w?p.w+'px':'';if(id==='brand-image')el.style.width=(p.w||58)+'px';if(id==='brand-text')el.style.width=p.w?p.w+'px':'';el.style.fontSize=p.font?p.font+'px':'';el.style.letterSpacing=p.spacing!==undefined?p.spacing+'px':'';el.style.lineHeight=p.line||'';
}
function restore(raw){let values=JSON.parse(raw);els.forEach(e=>apply(e,values[e.dataset.drag]||{x:0,y:0}));sync()}
function sync(){els.forEach(e=>e.classList.toggle('selected',selected.has(e.dataset.drag)));const id=[...selected][0],p=pos[id]||{};
 for(const f of [size,spacing,line])f.disabled=selected.size!==1;
 size.value=p.font|| (id?parseFloat(getComputedStyle(byId[id]).fontSize):'');
 spacing.value=p.spacing??0;line.value=p.line||1.2;
}
els.forEach(e=>{
const id=e.dataset.drag;apply(e,stored[id]||{x:0,y:0});
const handle=document.createElement('span');handle.className='resize-handle';handle.title='Zmień szerokość';e.appendChild(handle);
e.addEventListener('pointerdown',ev=>{
 if(ev.button!==0)return;if(ev.target.closest('[data-drag]')!==e)return;
 ev.preventDefault();ev.stopPropagation();checkpoint();
 if(!ev.shiftKey&&!selected.has(id))selected.clear();
 if(ev.shiftKey&&selected.has(id))selected.delete(id);else selected.add(id);
 sync();
 const resizing=ev.target===handle, rect=e.getBoundingClientRect(),sx=ev.clientX,sy=ev.clientY;
 const initial=Object.fromEntries([...selected].map(k=>[k,{...pos[k]}]));
 const width=pos[id].w||Math.round(rect.width);
 e.setPointerCapture(ev.pointerId);
 const move=event=>{
  const dx=event.clientX-sx,dy=event.clientY-sy;
  if(resizing)apply(e,{...pos[id],w:clamp(Math.round(width+dx),40,1400)});
  else for(const k of selected){let p=initial[k];if(!p)continue;apply(byId[k],{...p,x:clamp(Math.round(p.x+dx),-400,400),y:clamp(Math.round(p.y+dy),-300,300)})}
  const b=e.getBoundingClientRect();let mx=null,my=null;
  for(const o of els){if(o===e)continue;const r=o.getBoundingClientRect();if(Math.abs(b.left-r.left)<7)mx=r.left;if(Math.abs(b.top-r.top)<7)my=r.top}
  gx.style.display=mx===null?'none':'block';gx.style.left=mx+'px';gy.style.display=my===null?'none':'block';gy.style.top=my+'px';
  status.textContent=resizing?'Szerokość: '+Math.round(e.getBoundingClientRect().width)+' px':'X: '+pos[id].x+' Y: '+pos[id].y;
 };
 const end=()=>{e.removeEventListener('pointermove',move);gx.style.display=gy.style.display='none';status.textContent='Układ zmieniony — zapisz';};
 e.addEventListener('pointermove',move);e.addEventListener('pointerup',end,{once:true});e.addEventListener('pointercancel',end,{once:true});
});
});
checkpoint();history.length=0;sync();
document.getElementById('undo-layout').onclick=()=>{if(!history.length)return;future.push(snapshot());restore(history.pop());status.textContent='Cofnięto'};
document.getElementById('redo-layout').onclick=()=>{if(!future.length)return;history.push(snapshot());restore(future.pop());status.textContent='Ponowiono'};
document.getElementById('reset-layout').onclick=()=>{checkpoint();els.forEach(e=>apply(e,{x:0,y:0}));status.textContent='Wyzerowano — zapisz'};
for(const [input,key,a,b] of [[size,'font',8,120],[spacing,'spacing',-2,20],[line,'line',.7,3]]){
 input.onfocus=()=>checkpoint();
 input.onchange=()=>{if(selected.size!==1)return;let id=[...selected][0];apply(byId[id],{...pos[id],[key]:clamp(Number(input.value),a,b)});status.textContent='Typografia zmieniona — zapisz';};
}
document.getElementById('viewport-mode').onchange=e=>{document.getElementById('viewport-stage').style.width=e.target.value;status.textContent='Podgląd '+e.target.options[e.target.selectedIndex].text};
document.getElementById('save-layout').onclick=async()=>{
status.textContent='Zapisywanie…';
try{const res=await fetch(@json(route('draft-header.positions')),{method:'PUT',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({positions:pos})});
if(!res.ok)throw Error('HTTP '+res.status);status.textContent='Zapisano ✓';}catch(e){status.textContent='Błąd zapisu: '+e.message}
};
})();
</script></body></html>