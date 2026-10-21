<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Nagłówek uproszczony — edytor FoodFoto</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f1f2ef;color:#252923;font:14px Arial,sans-serif}
.toolbar{position:sticky;top:0;z-index:100;background:white;border-bottom:1px solid #d4dbd0;padding:14px 2.5%;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
.toolbar h1{font:400 23px Georgia,serif;margin:0 18px 0 0}
.toolbar a,.toolbar button{padding:10px 15px;border:1px solid #ced5ce;border-radius:7px;text-decoration:none;color:#222;background:white;font:13px Arial;cursor:pointer}
.toolbar #save-compact{background:#244c35;color:white;border-color:#244c35;font-weight:bold}
.toolbar output{font-size:13px;color:#376343}
.description{margin:20px 2.5%;line-height:1.55;max-width:1100px}
.workspace{overflow:auto;padding:22px 25px 40px;background:repeating-conic-gradient(#f1f1ef 0% 25%,#e7e7e4 0% 50%) 0 0/20px 20px}
.designer{position:relative;width:1400px;min-width:1400px;min-height:125px;background:#fff;box-shadow:0 10px 30px #0002}
.designer #fve-header-variant-preview .ff-shared-stage.ff-simple{min-height:110px;overflow:visible}
.designer .ff-shared-photo{display:none!important}
.designer .ff-shared-brand,.designer .ff-shared-menu{user-select:none}
.designer .ff-menu-grip{display:none!important}
.designer .ff-shared-brand img,.designer .ff-shared-rule,
.designer .ff-shared-name strong,.designer .ff-shared-name small,
.designer .ff-shared-menu{cursor:move;touch-action:none}
.designer .selected-compact{outline:2px dashed #55894b!important;outline-offset:4px}
#size-handle{position:fixed;z-index:110;display:none;width:17px;height:17px;border:2px solid #fff;background:#3c764a;box-shadow:0 0 0 1px #2d5b35;border-radius:3px;cursor:nwse-resize;touch-action:none}
#size-handle.on{display:block}
.properties{margin:22px 2.5% 40px;background:white;border:1px solid #d8ddd8;border-radius:10px;padding:20px;max-width:1100px}
.properties h2{font:400 22px Georgia,serif;margin:0 0 10px}
.properties .help{color:#5a675d}
.property-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px}
.property-grid label{display:flex;flex-direction:column;gap:5px;font-size:12px;font-weight:600}
.property-grid input,.property-grid select{width:100%;padding:9px 10px;border:1px solid #cdd5cc;border-radius:6px;font:14px Arial;background:#fff}
.property-grid [hidden]{display:none!important}
.bottom-hint{margin:10px 2.5% 30px;font-size:12px;color:#666}
</style>
</head>
<body>
@php
    $saveCompactUrl = route('draft-header.compact-layout');
@endphp
<div class="toolbar">
    <h1>Wspólny nagłówek uproszczony</h1>
    <a href="{{ route('draft-header.preview') }}">← Nagłówek ze zdjęciem</a>
    <a href="{{ route('draft-header.review') }}">Przegląd publikacji</a>
    <button type="button" id="save-compact">Zapisz nagłówek uproszczony</button>
    <button type="button" id="restore-compact">Cofnij niezapisane zmiany</button>
    <output id="compact-status" role="status">Gotowy</output>
</div>
<p class="description">
    Ten układ jest wspólny dla <strong>wszystkich podstron z nagłówkiem bez zdjęcia</strong>
    i <strong>nagłówka po przewinięciu strony głównej</strong>. Nie zmienia banera ze zdjęciem.
    Kliknij logo, pionową kreskę, nazwisko, podpis lub menu. Przeciągaj, aby przesunąć;
    zielonym kwadracikiem zmienisz rozmiar zaznaczonego elementu.
</p>
<div class="workspace">
    <div class="designer" id="compact-designer">
        @include('admin.components.draft-shared-header')
    </div>
</div>
<button id="size-handle" type="button" aria-label="Zmień rozmiar zaznaczonego elementu" title="Przeciągnij, aby zmienić rozmiar"></button>
<section class="properties" aria-label="Właściwości nagłówka uproszczonego">
    <h2 id="element-name">Wybierz element w nagłówku</h2>
    <p class="help" id="element-hint">Dostępna jest precyzja 0,1 px w typografii. Zmiany zobaczysz od razu, a zapiszesz przyciskiem na górze.</p>
    <div id="fields" class="property-grid"></div>
</section>
<p class="bottom-hint">Po zapisaniu sprawdź podstronę Kontakt i nagłówek po przewinięciu strony głównej. Ustawienia dotyczą wszystkich nagłówków uproszczonych.</p>
<script>
(() => {
const saved = @json($compactLayout);
const saveUrl = @json($saveCompactUrl);
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const names = {logo:'Logo FoodFoto',divider:'Pionowa kreska',title:'Magda Gugała',subtitle:'FOTOGRAFIA I STYLIZACJA ŻYWNOŚCI',menu:'Białe menu'};
const initial = {
 logo:{x:0,y:0,size:58},
 divider:{x:0,y:0,size:58,width:1},
 title:{x:0,y:0,font:28,spacing:0,word_spacing:0,color:'#222222',weight:400},
 subtitle:{x:0,y:0,font:9,spacing:1.4,word_spacing:0,color:'#222222'},
 menu:{x:0,y:0,scale:1}
};
const labels = {
 x:'Przesunięcie w poziomie (px)',y:'Przesunięcie w pionie (px)',size:'Wielkość / wysokość (px)',
 width:'Grubość kreski (px)',font:'Rozmiar czcionki (px)',spacing:'Odstęp liter (px)',
 word_spacing:'Odstęp słów (px)',color:'Kolor tekstu',weight:'Grubość czcionki',scale:'Skala menu'
};
const specs = {
 x:{type:'number',min:-350,max:350,step:1},y:{type:'number',min:-150,max:150,step:1},
 size:{type:'number',min:20,max:180,step:1},width:{type:'number',min:1,max:8,step:1},
 font:{type:'number',min:6,max:80,step:0.1},
 spacing:{type:'number',min:-2,max:20,step:0.1},
 word_spacing:{type:'number',min:-2,max:30,step:0.1},
 color:{type:'color'},weight:{type:'select'},scale:{type:'number',min:0.7,max:1.3,step:0.01}
};
const clamp=(v,a,b)=>Math.min(b,Math.max(a,v));
function fresh(){
 const data=JSON.parse(JSON.stringify(initial));
 for(const key of Object.keys(initial)){
  const source=(saved && typeof saved[key]==='object' && saved[key]!==null)?saved[key]:{};
  for(const name of Object.keys(initial[key])){
   if(source[name]!==undefined) data[key][name]=source[name];
  }
 }
 return data;
}
let layout=fresh(),selected=null,dirty=false;
const root=document.getElementById('compact-designer').querySelector('#fve-header-variant-preview');
const stage=root.querySelector('.ff-shared-stage');
const bar=root.querySelector('.ff-shared-bar');
const elements={
 logo:root.querySelector('.ff-shared-brand img'),
 divider:root.querySelector('.ff-shared-rule'),
 title:root.querySelector('.ff-shared-name strong'),
 subtitle:root.querySelector('.ff-shared-name small'),
 menu:root.querySelector('.ff-shared-menu')
};
const handle=document.getElementById('size-handle'),fields=document.getElementById('fields');
const status=document.getElementById('compact-status');
function setVars(){
 const vars={
 '--ffc-logo-size':layout.logo.size+'px',
 '--ffc-logo-x':layout.logo.x+'px','--ffc-logo-y':layout.logo.y+'px',
 '--ffc-divider-size':layout.divider.size+'px','--ffc-divider-width':layout.divider.width+'px',
 '--ffc-divider-x':layout.divider.x+'px','--ffc-divider-y':layout.divider.y+'px',
 '--ffc-title-font':layout.title.font+'px','--ffc-title-spacing':layout.title.spacing+'px',
 '--ffc-title-word-spacing':layout.title.word_spacing+'px','--ffc-title-color':layout.title.color,
 '--ffc-title-weight':layout.title.weight,
 '--ffc-title-x':layout.title.x+'px','--ffc-title-y':layout.title.y+'px',
 '--ffc-subtitle-font':layout.subtitle.font+'px','--ffc-subtitle-spacing':layout.subtitle.spacing+'px',
 '--ffc-subtitle-word-spacing':layout.subtitle.word_spacing+'px',
 '--ffc-subtitle-color':layout.subtitle.color,
 '--ffc-subtitle-x':layout.subtitle.x+'px','--ffc-subtitle-y':layout.subtitle.y+'px',
 '--ffc-menu-x':layout.menu.x+'px','--ffc-menu-y':layout.menu.y+'px',
 '--ffc-menu-scale':layout.menu.scale
 };
 for(const [name,value] of Object.entries(vars)){
  root.style.setProperty(name,value);
  stage.style.setProperty(name,value);
  bar.style.setProperty(name,value);
 }
 // Menu fitting on simple headers is collision-aware. Request a refit
 // after changing its saved variables, not during a drag for performance.
 if(selected==='menu'){
   const menu=elements.menu;
   menu.style.zoom=String(layout.menu.scale);
   menu.style.transform='translate('+layout.menu.x+'px,'+layout.menu.y+'px)';
 }
 requestAnimationFrame(updateHandle);
}
function updateHandle(){
 if(!selected || !elements[selected]){handle.classList.remove('on');return}
 const rect=elements[selected].getBoundingClientRect();
 handle.style.left=Math.round(rect.right-8)+'px';
 handle.style.top=Math.round(rect.bottom-8)+'px';
 handle.classList.add('on');
}
function markDirty(){dirty=true;status.textContent='Niezapisane zmiany';status.style.color='#945c2e'}
function selectItem(name){
 if(selected && elements[selected]) elements[selected].classList.remove('selected-compact');
 selected=name;
 elements[name].classList.add('selected-compact');
 document.getElementById('element-name').textContent=names[name];
 drawFields();updateHandle();
}
function drawFields(){
 fields.innerHTML='';
 if(!selected)return;
 const section=layout[selected];
 for(const [key,value] of Object.entries(section)){
  const prop=specs[key];
  const label=document.createElement('label');
  label.textContent=labels[key]||key;
  const input=prop.type==='select'?document.createElement('select'):document.createElement('input');
  if(prop.type==='select'){
   for(const n of [300,400,500,600,700,800]){
    const opt=document.createElement('option');opt.value=n;opt.textContent=n;input.appendChild(opt);
   }
  }else {
   input.type=prop.type;
   if(prop.min!==undefined)input.min=prop.min;
   if(prop.max!==undefined)input.max=prop.max;
   if(prop.step!==undefined)input.step=prop.step;
  }
  input.value=value;
  input.addEventListener('input',()=>{
   if(input.type==='color')section[key]=input.value;
   else if(input.value!=='') {
    const minimum=(selected==='logo'&&key==='size')?25:(selected==='title'&&key==='font')?12:(selected==='subtitle'&&key==='font')?6:prop.min??0;
    const maximum=(selected==='divider'&&key==='size')?150:prop.max??1000;
    section[key]=Number(clamp(Number(input.value)||0,minimum,maximum).toFixed(prop.step===0.1||prop.step===0.01?2:0));
   }
   setVars();markDirty();
  });
  label.appendChild(input);fields.appendChild(label);
 }
}
let gesture=null;
Object.entries(elements).forEach(([name,el])=>{
 if(!el)return;
 el.addEventListener('pointerdown',event=>{
  if(event.button!==0)return;
  event.preventDefault();event.stopPropagation();
  selectItem(name);
  gesture={mode:'move',name,startX:event.clientX,startY:event.clientY,baseX:layout[name].x,baseY:layout[name].y};
 });
});
handle.addEventListener('pointerdown',event=>{
 if(event.button!==0||!selected)return;
 event.preventDefault();event.stopPropagation();
 gesture={mode:'resize',name:selected,startX:event.clientX,startY:event.clientY,
  startValue:layout[selected].size??layout[selected].font??layout[selected].scale??1};
});
window.addEventListener('pointermove',event=>{
 if(!gesture)return;
 const dx=event.clientX-gesture.startX,dy=event.clientY-gesture.startY;
 const p=layout[gesture.name];
 if(gesture.mode==='move'){
  p.x=Math.round(clamp(gesture.baseX+dx,-350,350));
  p.y=Math.round(clamp(gesture.baseY+dy,-150,150));
 }else{
  const distance=Math.abs(dy)>Math.abs(dx)?dy:dx;
  if(gesture.name==='menu')p.scale=Number(clamp(gesture.startValue+distance*.003,0.7,1.3).toFixed(2));
  else if(gesture.name==='divider')p.size=Math.round(clamp(gesture.startValue+distance,20,150));
  else if(gesture.name==='logo')p.size=Math.round(clamp(gesture.startValue+distance,25,180));
  else if(gesture.name==='title')p.font=Number(clamp(gesture.startValue+distance*.32,12,80).toFixed(1));
  else if(gesture.name==='subtitle')p.font=Number(clamp(gesture.startValue+distance*.2,6,32).toFixed(1));
 }
 setVars();markDirty();
});
window.addEventListener('pointerup',()=>{if(!gesture)return;gesture=null;drawFields();window.dispatchEvent(new Event('resize'));});
window.addEventListener('pointercancel',()=>{gesture=null;});
window.addEventListener('scroll',updateHandle,{passive:true});
window.addEventListener('resize',updateHandle);
document.getElementById('restore-compact').addEventListener('click',()=>{
 layout=fresh();setVars();drawFields();dirty=false;status.textContent='Przywrócono ostatnio zapisane ustawienia';status.style.color='#376343';
});
document.getElementById('save-compact').addEventListener('click',async()=>{
 const btn=document.getElementById('save-compact');
 btn.disabled=true;status.textContent='Zapisywanie…';
 try{
  const response=await fetch(saveUrl,{method:'PUT',credentials:'same-origin',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
    body:JSON.stringify({layout})});
  if(!response.ok){const error=await response.json().catch(()=>({}));throw Error(Object.values(error.errors||{}).flat()[0]||error.message||'HTTP '+response.status);}
  const result=await response.json();
  if(!result.ok)throw Error('Nie otrzymano potwierdzenia zapisu');
  dirty=false;status.textContent='Zapisano ✓ — wspólne ustawienia są aktywne';status.style.color='#376343';
  // Refresh the last saved baseline for the undo button.
  for(const key of Object.keys(initial)) saved[key]=JSON.parse(JSON.stringify(layout[key]));
 }catch(error){status.textContent='Błąd zapisu: '+error.message;status.style.color='#9b3232'}
 finally{btn.disabled=false}
});
window.addEventListener('beforeunload',event=>{if(dirty){event.preventDefault();event.returnValue='';}});
setVars();
})();
</script>
</body>
</html>