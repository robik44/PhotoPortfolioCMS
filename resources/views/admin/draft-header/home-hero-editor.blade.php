<!DOCTYPE html>
<html lang="pl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Roboczy builder banera — FoodFoto</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f3f0;color:#262722;font:15px Arial,sans-serif}
.top{position:sticky;top:0;z-index:50;background:#fff;border-bottom:1px solid #ddd;padding:13px 25px;display:flex;gap:14px;align-items:center;flex-wrap:wrap}
.top a{color:#244a38}.top button{background:#213d2e;color:white;border:0;border-radius:7px;padding:13px 20px;cursor:pointer}
.top input{width:75px;padding:7px}.notice{background:#e8f0e6;padding:12px 25px}
.stage{position:relative;min-height:690px;overflow:hidden;background:#e9e6dd}
.stage .photo{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
.stage:after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(249,247,244,.9),rgba(249,247,244,.5) 44%,transparent 80%);pointer-events:none}
.item{position:absolute;z-index:2;left:6%;cursor:move;touch-action:none;padding:3px;max-width:90%}
.item.active{outline:1px dashed #3f754f}
.item .handle{display:none;position:absolute;right:-7px;bottom:-7px;background:#4d7850;border:2px solid white;width:17px;height:17px;cursor:nwse-resize}
.item.active .handle{display:block}
[data-id="hero-eyebrow"]{top:21%;font-size:13px;letter-spacing:4px;color:#636e61}
[data-id="hero-heading"]{top:30%;font:400 80px/1.02 Georgia,serif;white-space:pre-line;letter-spacing:-.035em;width:850px}
[data-id="hero-text"]{top:61%;font:19px/1.45 Arial,sans-serif;width:700px}
[data-id="hero-portfolio-cta"]{top:78%;background:#597649;color:white;padding:19px 35px;border-radius:60px;letter-spacing:2px}
.controls{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.controls label{display:flex;gap:7px;align-items:center}
#editor-text{width:370px;max-width:75vw;padding:8px}#status{font-weight:bold}.below{margin:30px auto;max-width:1400px;padding:20px}.below iframe{display:block;width:100%;height:750px;border:1px solid #d2d2cc;background:#fff}.photo-form{display:flex;align-items:center;gap:8px}.photo-form input{max-width:180px}

/* Shared draft header visual, isolated to the private homepage staging view */
.hero-header{position:absolute;z-index:12;top:0;left:0;right:0;display:flex;align-items:flex-start;justify-content:space-between;padding:22px 4%;gap:24px;pointer-events:none}
.hero-header>*{pointer-events:auto}
.draft-brand{display:flex;align-items:center;gap:15px;transform-origin:top left}
.draft-brand img{width:85px;height:auto;display:block}.draft-brand .rule{height:85px;width:2px;background:#a9aaa6;flex-shrink:0}
.draft-brand .name strong{display:block;font:400 35px Georgia,serif;white-space:nowrap}
.draft-brand .name small{display:block;font:11px Arial,sans-serif;letter-spacing:.16em;margin-top:5px;white-space:nowrap}
.draft-menu{display:flex;align-items:center;gap:16px;background:#fff;padding:13px 17px;border-radius:12px 12px 34px 40px;transform-origin:top right;white-space:nowrap}
.draft-menu nav{display:flex;gap:20px;align-items:center}.draft-menu nav a{font:11px Arial,sans-serif;letter-spacing:.08em;color:#252525;text-decoration:none}
.draft-menu .contact{border:1px solid #293b30;border-radius:40px;padding:14px 20px;font:11px Arial,sans-serif;letter-spacing:.08em;text-decoration:none;color:#252525}
.sticky-demo{position:fixed;top:0;left:0;right:0;z-index:40;display:flex;align-items:center;justify-content:space-between;gap:20px;background:#f8f7f3;padding:10px 4%;box-shadow:0 2px 10px #0001;border-bottom:1px solid #ddd;visibility:hidden;transform:translateY(-100%);transition:transform .2s,visibility .2s}
.sticky-demo.shown{visibility:visible;transform:translateY(0)}.sticky-demo .draft-brand img{width:55px}.sticky-demo .draft-brand .rule{height:55px}.sticky-demo .draft-brand .name strong{font-size:25px}.sticky-demo .draft-brand .name small{font-size:9px}
.sticky-demo .draft-menu{border:1px solid #dedbd5;padding:6px 10px}.sticky-demo .draft-menu nav{gap:14px}.sticky-demo .draft-menu .contact{padding:10px 14px}
@media(max-width:1000px){.hero-header,.sticky-demo{gap:10px}.draft-brand img{width:60px}.draft-brand .rule{height:60px}.draft-brand .name strong{font-size:25px}.draft-menu{gap:8px}.draft-menu nav{gap:9px}.draft-menu nav a{font-size:9px}.draft-menu .contact{font-size:9px;padding:10px}}
</style></head><body>
<div class="notice">WERSJA ROBOCZA — tylko dla administratora. Zapis nie zmienia publicznej strony ani istniejącego buildera.</div>
<div class="top"><strong>BUILDER BANERA STRONY GŁÓWNEJ</strong>
<a href="{{ route('draft-header.edit') }}">← Nagłówek testowy</a>
<div class="controls"><label>Treść <input id="editor-text" aria-label="Treść zaznaczonego elementu"></label><label>Pismo <input id="font-size" type="number" min="8" max="120"></label></div>
<form id="photo-form" class="photo-form"><label>Zdjęcie banera <input id="hero-photo-input" name="hero_photo" type="file" accept="image/jpeg,image/png,image/webp"></label><button type="submit">Zmień zdjęcie</button></form>
<button id="save" type="button">Zapisz roboczy baner</button><span id="status">Wybierz element</span>
</div>

@php($draftLogo = 'branding/foodfoto-transparent-v2.png')
@php($brandSrc = is_file(storage_path('app/public/'.$draftLogo)) ? asset('storage/'.$draftLogo) : (!empty($header['header_brand_image']) ? asset('storage/'.$header['header_brand_image']) : null))
<template id="draft-header-template">
<div class="draft-brand">
@if($brandSrc)<img src="{{ $brandSrc }}" alt="FoodFoto">@else<strong>food<br>foto</strong>@endif
<span class="rule"></span><span class="name"><strong>{{ $header['logo'] }}</strong><small>{{ $header['logo_subtitle'] }}</small></span>
</div>
<div class="draft-menu">
<nav>@foreach($menuItems as $item)<a href="#" onclick="return false">{{ $item->label ?? $item->title ?? 'Menu' }}</a>@endforeach</nav>
<a class="contact" href="#" onclick="return false">{{ $settings['draft_header_contact_text'] }}</a>
</div>
</template>
<div id="sticky-demo" class="sticky-demo" aria-label="Testowy nagłówek po przewinięciu"></div>
<section class="stage" id="stage">
<div id="hero-header" class="hero-header" aria-label="Podgląd testowego nagłówka"></div>
@php($photo = $draft['preview_image'] ?? '')
@if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="Zdjęcie tła roboczego banera">@endif
@foreach($draft['sections'] ?? [] as $block)
<div class="item" data-id="{{ $block['id'] }}"><span class="value">{{ $block['content'] }}</span><span class="handle" title="Zmień rozmiar"></span></div>
@endforeach
</section>
<div class="below">
<h2>Pozostałe sekcje strony głównej — podgląd pomocniczy</h2>
<p>Podgląd istniejącej strony jest tylko do porównania. Ustawienia powyższego roboczego banera nie są jeszcze automatycznie przenoszone do tego widoku ani na stronę publiczną.</p>
<iframe src="{{ route('foodfoto.home.preview') }}" title="Podgląd dotychczasowej strony głównej" loading="lazy"></iframe>
</div>
<script>
(()=>{
const raw=@json($draft['sections'] ?? []);
const hp=@json($headerPositions);
for(const id of ['hero-header','sticky-demo']){
 const target=document.getElementById(id);
 target.append(document.getElementById('draft-header-template').content.cloneNode(true));
}
const heroHeader=document.getElementById('hero-header');
const brand=heroHeader.querySelector('.draft-brand'),menu=heroHeader.querySelector('.draft-menu');
const brandPart=hp['brand-image']||{},textPart=hp['brand-text']||{},linePart=hp['brand-divider']||{},menuPart=hp['menu-panel']||{};
if(brandPart.w)brand.querySelector('img').style.width=brandPart.w+'px';
if(textPart.font)brand.querySelector('.name strong').style.fontSize=textPart.font+'px';
if(linePart.h)brand.querySelector('.rule').style.height=linePart.h+'px';
if(menuPart.w)menu.style.zoom=Math.max(.55,Math.min(1.8,menuPart.w/1050));
if(menuPart.rtl!==undefined)menu.style.borderRadius=[menuPart.rtl,menuPart.rtr,menuPart.rbr,menuPart.rbl].map(v=>(v||0)+'px').join(' ');
const sticky=document.getElementById('sticky-demo');
const updateSticky=()=>sticky.classList.toggle('shown',window.scrollY>160);
window.addEventListener('scroll',updateSticky,{passive:true});updateSticky();

const items=new Map([...document.querySelectorAll('.item')].map(el=>[el.dataset.id,el]));
const data=new Map(raw.map(s=>[s.id,{...s,draft_geometry:{x:0,y:0,...(s.draft_geometry||{})}}]));
const input=document.getElementById('editor-text'),font=document.getElementById('font-size'),status=document.getElementById('status');let active=null;
const natural=new Map([...items].map(([id,el])=>[id,el.getBoundingClientRect().width]));
function paint(id){const el=items.get(id),p=data.get(id).draft_geometry;el.style.transform='translate('+(p.x||0)+'px,'+(p.y||0)+'px)';if(p.w)el.style.width=p.w+'px';if(p.font)el.style.fontSize=p.font+'px';el.querySelector('.value').textContent=data.get(id).content}
function choose(id){active=id;items.forEach((el,k)=>el.classList.toggle('active',k===id));input.value=data.get(id).content;font.value=data.get(id).draft_geometry.font||parseFloat(getComputedStyle(items.get(id)).fontSize);status.textContent='Edytujesz: '+id}
items.forEach((el,id)=>{paint(id);el.addEventListener('pointerdown',ev=>{
if(ev.button!==0)return;ev.preventDefault();choose(id);const p=data.get(id).draft_geometry;const orig={...p},sx=ev.clientX,sy=ev.clientY,resizing=ev.target.classList.contains('handle');el.setPointerCapture(ev.pointerId);
const move=e=>{const dx=e.clientX-sx,dy=e.clientY-sy;if(resizing){const base=orig.w||natural.get(id);p.w=Math.max(40,Math.min(1400,Math.round(base+dx)));if(id==='hero-heading'||id==='hero-eyebrow'||id==='hero-text'){p.font=Math.max(8,Math.min(120,Math.round((orig.font||parseFloat(getComputedStyle(el).fontSize))+dy*.3)))}}else{p.x=Math.max(-400,Math.min(400,Math.round(orig.x+dx)));p.y=Math.max(-300,Math.min(300,Math.round(orig.y+dy)))}paint(id);status.textContent='Zmodyfikowano — zapisz'};
const end=()=>{el.removeEventListener('pointermove',move);el.removeEventListener('pointerup',end)};
el.addEventListener('pointermove',move);el.addEventListener('pointerup',end,{once:true});
})});
input.oninput=()=>{if(!active)return;data.get(active).content=input.value;paint(active)};
font.onchange=()=>{if(!active)return;data.get(active).draft_geometry.font=Math.max(8,Math.min(120,Number(font.value)||16));paint(active)};
document.getElementById('photo-form').onsubmit=async e=>{
e.preventDefault();const file=document.getElementById('hero-photo-input').files[0];if(!file){status.textContent='Najpierw wybierz zdjęcie';return}
status.textContent='Wysyłanie zdjęcia…';
try{const body=new FormData();body.append('hero_photo',file);const res=await fetch(@json(route('home-hero-draft.photo')),{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body});
if(!res.ok)throw Error('HTTP '+res.status);const json=await res.json();let img=document.querySelector('.stage .photo');if(!img){img=document.createElement('img');img.className='photo';img.alt='Zdjęcie tła roboczego banera';document.getElementById('stage').prepend(img)}img.src=json.image_url;status.textContent='Zapisano nowe zdjęcie wersji roboczej ✓'}catch(error){status.textContent='Błąd zdjęcia: '+error.message}
};
document.getElementById('save').onclick=async()=>{status.textContent='Zapisywanie…';try{const res=await fetch(@json(route('home-hero-draft.save')),{method:'PUT',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({sections:[...data.values()].map(s=>({id:s.id,content:s.content,draft_geometry:s.draft_geometry}))})});if(!res.ok)throw Error('HTTP '+res.status);status.textContent='Zapisano wersję roboczą ✓'}catch(e){status.textContent='Błąd zapisu: '+e.message}};
})();
</script></body></html>