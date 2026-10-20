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
#editor-text{width:370px;max-width:75vw;padding:8px}#status{font-weight:bold}
</style></head><body>
<div class="notice">WERSJA ROBOCZA — tylko dla administratora. Zapis nie zmienia publicznej strony ani istniejącego buildera.</div>
<div class="top"><strong>BUILDER BANERA STRONY GŁÓWNEJ</strong>
<a href="{{ route('draft-header.edit') }}">← Nagłówek testowy</a>
<div class="controls"><label>Treść <input id="editor-text" aria-label="Treść zaznaczonego elementu"></label><label>Pismo <input id="font-size" type="number" min="8" max="120"></label></div>
<button id="save" type="button">Zapisz roboczy baner</button><span id="status">Wybierz element</span>
</div>
<section class="stage" id="stage">
@php($photo = $draft['preview_image'] ?? '')
@if($photo)<img class="photo" src="{{ asset('storage/'.$photo) }}" alt="Zdjęcie tła roboczego banera">@endif
@foreach($draft['sections'] ?? [] as $block)
<div class="item" data-id="{{ $block['id'] }}"><span class="value">{{ $block['content'] }}</span><span class="handle" title="Zmień rozmiar"></span></div>
@endforeach
</section>
<script>
(()=>{
const raw=@json($draft['sections'] ?? []);
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
document.getElementById('save').onclick=async()=>{status.textContent='Zapisywanie…';try{const res=await fetch(@json(route('home-hero-draft.save')),{method:'PUT',credentials:'same-origin',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({sections:[...data.values()].map(s=>({id:s.id,content:s.content,draft_geometry:s.draft_geometry}))})});if(!res.ok)throw Error('HTTP '+res.status);status.textContent='Zapisano wersję roboczą ✓'}catch(e){status.textContent='Błąd zapisu: '+e.message}};
})();
</script></body></html>