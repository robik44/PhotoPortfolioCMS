/* FoodFoto opt-in gallery canvas editor. No dependencies on legacy page builder. */
(() => {
    'use strict';
    const root = document.getElementById('gallery-visual-editor');
    if (!root) return;

    const initial = JSON.parse(root.querySelector('#gv-initial').textContent);
    const photos = initial.photos;
    const gallery = initial.gallery;
    if (initial.layout?.content && initial.scope==='gallery') {
        gallery.title=initial.layout.content.title;
        gallery.description=initial.layout.content.description;
    }
    const scope = initial.scope;
    let layout = initial.layout;
    let selected = 'title';
    let device = 'desktop';
    let snap = true;
    let drag = null;
    let dirty = false;

    const $ = sel => root.querySelector(sel);
    const canvas = $('#gv-canvas');
    const inspector = $('#gv-inspector');
    const listing = $('#gv-layers');
    const status = $('#gv-status');
    const photoById = new Map(photos.map(p => [Number(p.id), p]));
    const fontMap = {serif:'Georgia,serif', bodoni:'"Bodoni Moda",Georgia,serif',
        sans:'Arial,sans-serif', inter:'"Inter",Arial,sans-serif'};
    const html = (tag, className, text) => {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    };
    const idFor = item => item.id || item;
    const fmt = n => Math.round(Number(n) * 10) / 10;
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
    const progress = () => {
        dirty = true;
        status.textContent = 'Niezapisane zmiany';
        status.className = 'gv-status-dirty';
    };
    const blockName = id => ({
        title:'Tytuł galerii',description:'Opis galerii',services:'Linki do usług',
        back:'Powrót do galerii',photos:'Siatka fotografii',
    })[id] || 'Dodatkowy element';
    const orderedPhotos = () => {
        const ids = layout.photo_order || [];
        const positions = new Map(ids.map((id,i) => [Number(id),i]));
        return photos.slice().sort((a,b) => (positions.get(a.id) ?? (100000+photos.indexOf(a)))
            - (positions.get(b.id) ?? (100000+photos.indexOf(b))));
    };
    const photoPosition = (id, index) => {
        const k=String(id);
        if (!layout.individual_photos[k]) {
            const col=index % 4, row=Math.floor(index/4);
            layout.individual_photos[k]={x:layout.blocks.photos.x+col*22,
                y:layout.blocks.photos.y+row*(layout.photo_height+layout.gap),
                w:20,h:layout.photo_height};
        }
        return layout.individual_photos[k];
    };
    const selectedObject = () => {
        if (selected.startsWith('photo-')) {
            const id=Number(selected.slice(6)),idx=orderedPhotos().findIndex(p=>p.id===id);
            return {obj:photoPosition(id,Math.max(0,idx)),type:'freephoto',id};
        }
        const core = layout.blocks[selected];
        if (core) return {obj:core,type:selected,id:selected};
        const extra=(layout.extras || []).find(item=>item.id===selected);
        return extra ? {obj:extra,type:extra.type,id:selected} : null;
    };
    const fontCss = obj => {
        let fs=device === 'mobile' ? obj.mobile : device === 'tablet' ? obj.tablet : obj.size;
        return {fontFamily:fontMap[obj.font]||fontMap.sans,fontSize:(Number(fs)||16)+'px',
            fontWeight:obj.weight||400,color:obj.color||'#222',
            lineHeight:obj.leading||1.5,letterSpacing:(obj.spacing||0)+'px'};
    };
    const makeImage = photo => {
        const image=html('img');
        image.src=photo.url;
        image.alt=photo.alt||photo.title||'Fotografia';
        image.loading='lazy';
        image.draggable=false;
        return image;
    };
    function makeElement(id,obj,inner,cssLabel) {
        const el=html('div','gv-element');
        el.dataset.id=id;
        if(id===selected)el.classList.add('gv-selected');
        el.style.left=obj.x+'%';
        el.style.top=obj.y+'px';
        el.style.width=obj.w+'%';
        if(cssLabel)el.setAttribute('aria-label',cssLabel);
        el.appendChild(inner);
        const handle=html('span','gv-grip','↘');
        handle.title='Przeciągnij, aby zmienić szerokość';
        handle.dataset.resize='1';
        el.appendChild(handle);
        el.addEventListener('pointerdown',event=>beginDrag(event,id));
        el.addEventListener('click',()=>{select(id);});
        return el;
    }
    function textElement(id,obj,content,tag='div'){
        const inner=html(tag,'gv-text',content);
        Object.assign(inner.style,fontCss(obj));
        inner.style.whiteSpace='pre-line';
        return makeElement(id,obj,inner,blockName(id));
    }
    function render() {
        canvas.replaceChildren();
        canvas.className='gv-canvas gv-'+device;
        canvas.style.setProperty('--gv-cols-tablet',String(layout.columns_tablet));
        canvas.style.setProperty('--gv-cols-mobile',String(layout.columns_mobile));
        const blocks=layout.blocks;
        let bottom=Number(layout.canvas_height)||900;
        const title=textElement('title',blocks.title,gallery.title,'h1');
        canvas.appendChild(title);
        bottom=Math.max(bottom,blocks.title.y+Math.max(75,title.scrollHeight)+20);
        if(gallery.description){
            const desc=textElement('description',blocks.description,gallery.description,'p');
            canvas.appendChild(desc);
            bottom=Math.max(bottom,blocks.description.y+Math.max(65,desc.scrollHeight)+20);
        }
        if(gallery.services.length){
            const links=html('div','gv-related');
            gallery.services.forEach(name=>links.appendChild(html('span','',name+'  ↗')));
            Object.assign(links.style,fontCss(blocks.services));
            canvas.appendChild(makeElement('services',blocks.services,links));
            bottom=Math.max(bottom,blocks.services.y+65);
        }
        const backlink=textElement('back',blocks.back,'← POWRÓT DO GALERII','div');
        canvas.appendChild(backlink);
        bottom=Math.max(bottom,blocks.back.y+60);

        const ordered=orderedPhotos();
        if(layout.photo_mode==='grid'){
            const grid=html('div','gv-photos-grid');
            grid.style.gridTemplateColumns='repeat('+layout.columns+',minmax(0,1fr))';
            grid.style.gap=layout.gap+'px';
            ordered.forEach(photo=>{
                const wrapper=html('div','gv-thumb');
                wrapper.dataset.photoId=photo.id;
                wrapper.appendChild(makeImage(photo));
                const img=wrapper.querySelector('img');
                img.style.height=layout.photo_height+'px';
                img.style.objectFit=layout.photo_fit;
                wrapper.draggable=scope==='gallery';
                wrapper.addEventListener('dragstart',e=>{if(scope!=='gallery'){e.preventDefault();return;}e.stopPropagation();e.dataTransfer.setData('text/plain',String(photo.id));});
                wrapper.addEventListener('dragover',e=>e.preventDefault());
                wrapper.addEventListener('drop',e=>{
                    e.preventDefault();e.stopPropagation();if(scope!=='gallery')return;
                    const from=Number(e.dataTransfer.getData('text/plain'));
                    const ids=orderedPhotos().map(p=>p.id);
                    const i=ids.indexOf(from),j=ids.indexOf(photo.id);
                    if(i<0||j<0||i===j)return;
                    ids.splice(i,1);ids.splice(j,0,from);
                    layout.photo_order=ids;
                    progress();render();
                });
                grid.appendChild(wrapper);
            });
            canvas.appendChild(makeElement('photos',blocks.photos,grid,'Siatka fotografii'));
            const n=Math.ceil(ordered.length/layout.columns);
            const per=layout.photo_height+layout.gap;
            bottom=Math.max(bottom,blocks.photos.y+n*per+80);
        }else{
            ordered.forEach((photo,i)=>{
                const p=photoPosition(photo.id,i);
                const wrap=html('div','gv-free-img');wrap.appendChild(makeImage(photo));
                wrap.querySelector('img').style.height=p.h+'px';
                wrap.querySelector('img').style.objectFit=layout.photo_fit;
                canvas.appendChild(makeElement('photo-'+photo.id,p,wrap,'Zdjęcie '+photo.id));
                bottom=Math.max(bottom,p.y+p.h+55);
            });
        }

        (layout.extras||[]).forEach(item=>{
            let body;
            if(item.type==='text'||item.type==='button'){
                body=html(item.type==='button'?'span':'p',item.type==='button'?'gv-button':'gv-text',item.text||'Nowy tekst');
                Object.assign(body.style,fontCss(item));
            }else if(item.type==='divider'){
                body=html('div','gv-divider');body.style.background=item.color;
            }else if(item.type==='image'){
                const photo=photoById.get(Number(item.photo_id));
                body=html('div','gv-free-img');
                if(photo){body.appendChild(makeImage(photo));body.querySelector('img').style.height=(item.h||240)+'px';}
                else body.appendChild(html('span','','Wybierz zdjęcie w ustawieniach'));
            }
            canvas.appendChild(makeElement(item.id,item,body,item.type));
            bottom=Math.max(bottom,item.y+(item.type==='image'?item.h||240:80)+50);
        });
        canvas.style.height=device==='desktop'?Math.max(550,bottom)+'px':'auto';
        listing.replaceChildren();
        const ids=['title','description','services','back',layout.photo_mode==='grid'?'photos':null,
            ...((layout.photo_mode==='free')?ordered.map(p=>'photo-'+p.id):[]),
            ...(layout.extras||[]).map(e=>e.id)].filter(Boolean);
        ids.forEach(id=>{
            const item=html('button','gv-layer'+(id===selected?' active':''),id.startsWith('photo-')?('Zdjęcie '+id.slice(6)):
                (id.startsWith('extra-')?(layout.extras.find(e=>e.id===id)?.type||'Dodatkowy'):blockName(id)));
            item.type='button';item.addEventListener('click',()=>select(id));listing.appendChild(item);
        });
        updateInspector();
    }
    function select(id) {selected=id;render();}
    const addLabel=(parent,text) => parent.appendChild(html('label','gv-field-label',text));
    const input=(parent,label,value,onChange,{type='number',step='.1',min='',max='',values=null}={})=>{
        const row=html('div','gv-control');
        addLabel(row,label);
        const el=values ? html('select') : html('input');
        if(values){values.forEach(([v,t])=>{const opt=html('option','',t);opt.value=v;el.appendChild(opt);});}
        else {el.type=type;el.step=step;if(min!=='')el.min=min;if(max!=='')el.max=max;}
        el.value=value??'';
        el.addEventListener('change',()=>{onChange(el.value);progress();render();});
        row.appendChild(el);parent.appendChild(row);
    };
    function updateInspector(){
        inspector.replaceChildren();
        const ref=selectedObject();
        if(!ref){inspector.appendChild(html('p','','Wybierz element na podglądzie.'));return;}
        const {obj,type,id}=ref;
        inspector.appendChild(html('h3','',id.startsWith('photo-')?'Zdjęcie '+id.slice(6):blockName(id)));
        if ((id==='title'||id==='description') && scope==='gallery') {
            const field=html('div','gv-control');
            addLabel(field,id==='title'?'Treść tytułu':'Treść opisu');
            const control=html(id==='description'?'textarea':'input');
            if(id==='description')control.rows=6;
            else control.type='text';
            control.value=id==='title'?gallery.title:gallery.description;
            control.style.width='100%';
            control.style.padding='9px';
            control.style.border='1px solid #ddd';
            control.addEventListener('change',()=>{
                if(id==='title'&& !control.value.trim()){
                    alert('Tytuł galerii nie może być pusty.');
                    control.value=gallery.title;
                    return;
                }
                if(!layout.content)layout.content={title:gallery.title,description:gallery.description};
                layout.content[id]=control.value;
                gallery[id]=control.value;
                progress();render();
            });
            field.appendChild(control);
            inspector.appendChild(field);
        }
        input(inspector,'Pozycja X (%)',obj.x,v=>obj.x=clamp(Number(v),0,95),{min:0,max:95});
        input(inspector,'Pozycja Y (px)',obj.y,v=>obj.y=clamp(Number(v),0,30000),{min:0,max:30000});
        input(inspector,'Szerokość (%)',obj.w,v=>obj.w=clamp(Number(v),4,100),{min:4,max:100});
        if(type==='freephoto'){
            input(inspector,'Wysokość zdjęcia (px)',obj.h,v=>obj.h=clamp(Number(v),60,1200),{min:60,max:1200});
        }
        if(type==='photos'||type==='freephoto'){
            if(type==='photos'){
                input(inspector,'Kolumny — komputer',layout.columns,v=>layout.columns=Number(v),{values:[[1,'1'],[2,'2'],[3,'3'],[4,'4'],[5,'5'],[6,'6'],[7,'7'],[8,'8']]});
                input(inspector,'Kolumny — tablet',layout.columns_tablet,v=>layout.columns_tablet=Number(v),{values:[[1,'1'],[2,'2'],[3,'3'],[4,'4']]});
                input(inspector,'Kolumny — telefon',layout.columns_mobile,v=>layout.columns_mobile=Number(v),{values:[[1,'1'],[2,'2']]});
                input(inspector,'Odstęp (px)',layout.gap,v=>layout.gap=clamp(Number(v),0,90));
                input(inspector,'Wysokość miniaturek (px)',layout.photo_height,v=>layout.photo_height=clamp(Number(v),80,900));
            }
            input(inspector,'Kadrowanie',layout.photo_fit,v=>layout.photo_fit=v,{values:[['contain','Całe zdjęcie'],['cover','Wypełnij kafelek']]});
            if(type==='photos'){
                const hint=html('p','gv-hint','Przeciągnij miniatury w podglądzie, aby zmienić ich kolejność tylko w tej galerii.');
                inspector.appendChild(hint);
            }
        }else if(type!=='photos'){
            if(type==='text'||type==='button'){
                const e=(layout.extras||[]).find(row=>row.id===id);
                input(inspector,'Tekst',e.text,v=>e.text=v,{type:'text'});
                if(type==='button')input(inspector,'Link (URL)',e.url,v=>e.url=v,{type:'text'});
            }
            if(type==='image'){
                input(inspector,'Zdjęcie',obj.photo_id||'',v=>obj.photo_id=Number(v),{
                    values:photos.map(p=>[p.id,p.title||('Zdjęcie '+p.id)])});
                input(inspector,'Wysokość (px)',obj.h||240,v=>obj.h=clamp(Number(v),60,1200));
            }
            if(type!=='image'){
                input(inspector,'Czcionka',obj.font,v=>obj.font=v,{
                    values:[['serif','Georgia'],['bodoni','Bodoni Moda'],['sans','Arial'],['inter','Inter']]});
                input(inspector,'Rozmiar — komputer (px)',obj.size,v=>obj.size=clamp(Number(v),8,160),{min:8,max:160});
                input(inspector,'Rozmiar — tablet (px)',obj.tablet,v=>obj.tablet=clamp(Number(v),8,160),{min:8,max:160});
                input(inspector,'Rozmiar — telefon (px)',obj.mobile,v=>obj.mobile=clamp(Number(v),8,160),{min:8,max:160});
                input(inspector,'Grubość',obj.weight,v=>obj.weight=Number(v),{values:
                    [100,200,300,400,500,600,700,800,900].map(v=>[v,String(v)])});
                input(inspector,'Kolor',obj.color,v=>obj.color=v,{type:'color'});
                input(inspector,'Interlinia',obj.leading,v=>obj.leading=clamp(Number(v),.7,4),{min:.7,max:4,step:.05});
                input(inspector,'Odstęp liter (px)',obj.spacing,v=>obj.spacing=clamp(Number(v),-10,25),{min:-10,max:25,step:.1});
            }
        }
        if(id.startsWith('extra-')){
            const remove=html('button','gv-danger','Usuń element');
            remove.type='button';remove.addEventListener('click',()=>{
                layout.extras=layout.extras.filter(item=>item.id!==id);
                selected='title';progress();render();
            });
            inspector.appendChild(remove);
        }
        if(id==='title'||id==='description'){
            const link=html('a','gv-edit-content','Edytuj treść oraz SEO w ustawieniach galerii ↗');
            link.href=initial.editUrl;
            inspector.appendChild(link);
        }
    }
    function beginDrag(event,id){
        if(event.button!==0)return;
        if(event.target.closest('.gv-thumb'))return;
        const selectedInfo=id.startsWith('photo-')?
            {obj:photoPosition(Number(id.slice(6)),orderedPhotos().findIndex(p=>p.id===Number(id.slice(6))))}
            : {obj:layout.blocks[id]||(layout.extras||[]).find(e=>e.id===id)};
        if(!selectedInfo.obj)return;
        const obj=selectedInfo.obj,rect=canvas.getBoundingClientRect();
        const resize=Boolean(event.target.closest('[data-resize]'));
        drag={id,obj,x0:event.clientX,y0:event.clientY,initialX:obj.x,
            initialY:obj.y,initialW:obj.w,resize,rectWidth:rect.width};
        event.preventDefault();event.currentTarget.setPointerCapture(event.pointerId);
        selected=id;
        root.querySelectorAll('.gv-element').forEach(el=>el.classList.toggle('gv-selected',el.dataset.id===id));
    }
    canvas.addEventListener('pointermove',event=>{
        if(!drag)return;
        const dx=(event.clientX-drag.x0)/drag.rectWidth*100,dy=(event.clientY-drag.y0);
        const round=v=>snap?Math.round(v/5)*5:fmt(v);
        if(drag.resize)drag.obj.w=clamp(round(drag.initialW+dx),4,100);
        else{
            drag.obj.x=clamp(round(drag.initialX+dx),0,95);
            drag.obj.y=clamp(round(drag.initialY+dy),0,30000);
        }
        const moved=canvas.querySelector('.gv-element[data-id="'+drag.id+'"]');
        if(moved){moved.style.left=drag.obj.x+'%';moved.style.top=drag.obj.y+'px';moved.style.width=drag.obj.w+'%';}
        progress();
    });
    const finish=()=>{if(!drag)return;drag=null;render();};
    canvas.addEventListener('pointerup',finish);
    canvas.addEventListener('pointercancel',finish);
    function addExtra(type){
        if(scope==='global'&&type==='image'){alert('Dodatkowe zdjęcia dodawaj w indywidualnej galerii.');return;}
        if(type==='image'&&!photos.length){alert('Najpierw dodaj zdjęcia do galerii z biblioteki.');return;}
        const id='extra-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,7);
        const item={id,type,x:8,y:layout.blocks.photos.y+80,w:type==='image'?30:65,
            font:'sans',size:22,tablet:20,mobile:18,weight:400,
            color:'#333333',leading:1.5,spacing:0,text:'Nowy tekst',url:'#',
            photo_id:photos[0]?.id||null,h:240};
        layout.extras.push(item);selected=id;progress();render();
    }
    root.querySelectorAll('[data-gv-add]').forEach(button=>button.addEventListener('click',()=>addExtra(button.dataset.gvAdd)));
    root.querySelectorAll('[data-gv-device]').forEach(button=>button.addEventListener('click',()=>{
        device=button.dataset.gvDevice;
        root.querySelectorAll('[data-gv-device]').forEach(b=>b.classList.toggle('active',b===button));render();
    }));
    $('#gv-snap').addEventListener('change',e=>{snap=e.target.checked;});
    $('#gv-mode').addEventListener('change',e=>{
        layout.photo_mode=e.target.value;
        if(layout.photo_mode==='free'&&scope==='global'){
            alert('Swobodny układ zdjęć ustawiaj indywidualnie dla galerii.');
            layout.photo_mode='grid';e.target.value='grid';return;
        }
        progress();render();
    });
    $('#gv-reset-layout').addEventListener('click',()=>{
        if(!confirm('Odrzucić niezapisane zmiany i odświeżyć edytor?'))return;
        window.location.reload();
    });
    root.querySelector('form').addEventListener('submit',e=>{
        $('#gv-payload').value=JSON.stringify(layout);
        if(e.submitter?.value==='unpublish'&&!confirm(
            'Wyłączyć opublikowany układ? Strona wróci do wcześniejszego szablonu albo do szablonu wspólnego.'
        ))e.preventDefault();
        if(e.submitter?.value==='publish'&&!confirm(
            scope==='global'?'Opublikować wspólny projekt na wszystkich galeriach bez nadpisań?':'Opublikować zmiany tylko w tej galerii?'
        ))e.preventDefault();
    });
    window.addEventListener('beforeunload',e=>{
        if(!dirty)return;
        e.preventDefault();e.returnValue='';
    });
    // Successful form navigation is allowed.
    root.querySelector('form').addEventListener('submit',e=>{
        if(!e.defaultPrevented)dirty=false;
    });
    render();
})();