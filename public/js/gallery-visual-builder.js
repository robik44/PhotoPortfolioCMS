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
    const sharedTemplate = initial.sharedTemplate || null;
    let layout = initial.layout;
    // The Laravel default is an empty PHP array, serialized as [].
    // Keeping it as a JS array causes positions assigned under photo IDs
    // (e.g. 90) to serialize as [null, null, ...] and fail PHP validation.
    // Always use an ID-keyed object for free-position photo coordinates.
    const normalizePositions = positions =>
        window.FoodFotoGalleryGuides?.normalizePositions(positions) ?? {};
    layout.individual_photos = normalizePositions(layout.individual_photos);
    let selected = 'title';
    let device = 'desktop';
    let snap = true;
    let guides = true;
    let drag = null;
    let dirty = false;

    const $ = sel => root.querySelector(sel);
    const canvas = $('#gv-canvas');
    const stage = $('#gv-stage');
    const scrollArea = $('#gv-scroll');
    const zoomSelect = $('#gv-zoom');
    const zoomReadout = $('#gv-zoom-current');
    const focusButton = $('#gv-fullscreen');
    let zoomMode = 'fit';
    let currentScale = 1;
    let zoomFrame = 0;
    const previousBodyOverflow = document.body.style.overflow;
    const inspector = $('#gv-inspector');
    const listing = $('#gv-layers');
    const status = $('#gv-status');
    const photoById = new Map(photos.map(p => [Number(p.id), p]));
    const fontMap = {serif:'Georgia,serif', bodoni:'"Bodoni Moda",Georgia,serif',
        sans:'Arial,sans-serif', inter:'"Inter",Arial,sans-serif'};
    const fontChoices = [['serif','Georgia'],['bodoni','Bodoni Moda'],
        ['sans','Arial'],['inter','Inter']];
    (initial.fonts || []).forEach(font => {
        if (!font || typeof font.id !== 'string') return;
        fontMap[font.id] = font.css;
        if (!fontChoices.some(choice=>choice[0]===font.id)) fontChoices.push([font.id,font.label]);
    });
    const html = (tag, className, text) => {
        const el = document.createElement(tag);
        if (className) el.className = className;
        if (text !== undefined) el.textContent = text;
        return el;
    };
    const idFor = item => item.id || item;
    const fmt = n => Math.round(Number(n) * 10) / 10;
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
    // The 1200px design canvas stays at its native coordinates. Only the
    // preview is scaled, so saving never changes element positions or fonts.
    function fitCanvas() {
        const naturalWidth = canvas.offsetWidth || 1200;
        const naturalHeight = canvas.offsetHeight || 900;
        const available = Math.max(100, scrollArea.clientWidth - 32);
        const requested = zoomMode === 'fit' ? Math.min(1, available / naturalWidth) : Number(zoomMode);
        const scale = clamp(Number.isFinite(requested) ? requested : 1, .15, 1.5);
        currentScale = scale;
        canvas.style.transform = 'scale(' + scale + ')';
        stage.style.width = Math.ceil(naturalWidth * scale) + 'px';
        stage.style.height = Math.ceil(naturalHeight * scale) + 'px';
        zoomReadout.textContent = Math.round(scale * 100) + '%';
    }
    function scheduleFit() {
        if (zoomFrame) cancelAnimationFrame(zoomFrame);
        zoomFrame = requestAnimationFrame(() => { zoomFrame = 0; fitCanvas(); });
    }
    function setFocus(activate) {
        root.classList.toggle('gv-focus', activate);
        document.body.style.overflow = activate ? 'hidden' : previousBodyOverflow;
        focusButton.textContent = activate ? '↙ Zamknij pełny ekran' : '⛶ Pełny ekran';
        focusButton.setAttribute('aria-pressed', activate ? 'true' : 'false');
        scrollArea.scrollLeft = 0;
        scheduleFit();
    }
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
        if (!layout.individual_photos || Array.isArray(layout.individual_photos)) {
            layout.individual_photos = normalizePositions(layout.individual_photos);
        }
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
            return {obj:photoPosition(id,Math.max(0,idx)),type:'freephoto',id:selected,photoId:id};
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
            lineHeight:obj.leading||1.5,letterSpacing:(obj.spacing||0)+'px',
            textAlign:obj.align||'left',fontStyle:obj.italic?'italic':'normal',
            textTransform:obj.uppercase?'uppercase':'none',opacity:(obj.opacity??100)/100};
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
        el.style.order=Math.round(Number(obj.y)||0);
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
        $('#gv-mode-help').textContent=layout.photo_mode==='free'
            ? 'Swobodny: zaznacz zdjęcie, ustaw X i Y lub przeciągnij. Niebieskie linie wskazują pozycję, zielone wyrównanie do innych elementów.'
            : 'Siatka: zdjęcia ustawiają się automatycznie. Do przesuwania pojedynczych fotografii wybierz „Swobodny”.';
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
        renderSelectionGuides();
        scheduleFit();
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
        inspector.appendChild(html('h3','',type==='freephoto' ? 'Zdjęcie '+(ref.photoId || id.slice(6)) : blockName(id)));
        if (scope==='gallery' && sharedTemplate && layout.blocks[id] && sharedTemplate.blocks?.[id]) {
            const inherited=sharedTemplate.blocks[id];
            const current=layout.blocks[id];
            const overridden=Object.keys(inherited).some(key =>
                JSON.stringify(current[key])!==JSON.stringify(inherited[key])
                && !(typeof current[key]==='number' && Number(current[key])===Number(inherited[key])));
            if(overridden){
                inspector.appendChild(html('p','gv-override-label','Ten element ma indywidualne ustawienia. Niektóre zmiany ze wspólnego szablonu mogą być tu niewidoczne.'));
                const reset=html('button','gv-reset-inheritance','↶ Przywróć styl wspólny tego elementu');
                reset.type='button';
                reset.addEventListener('click',()=>{
                    if(!confirm('Przywrócić wspólne ustawienia tego elementu w szkicu?'))return;
                    layout.blocks[id] = JSON.parse(JSON.stringify(inherited));
                    progress();render();
                });
                inspector.appendChild(reset);
            }else{
                inspector.appendChild(html('p','gv-inheritance-status','Ten element dziedziczy styl ze wspólnego szablonu.'));
            }
        }
        if(type==='freephoto'){
            const selectedPhoto=photoById.get(Number(ref.photoId));
            if(selectedPhoto){
                const preview=html('div','gv-photo-inspector-preview');
                preview.appendChild(makeImage(selectedPhoto));
                preview.appendChild(html('p','gv-hint','Przesuwaj płynnie myszką lub strzałkami po 1 px; Shift + strzałka: 10 px. Linie pokazują wyrównanie do innych zdjęć i tekstów.'));
                inspector.appendChild(preview);
            }
        }
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
        input(inspector,'Pozycja X (%)',obj.x,v=>obj.x=clamp(Number(v),0,95),{min:0,max:95,step:.01});
        input(inspector,'Pozycja Y (px)',obj.y,v=>obj.y=clamp(Number(v),0,30000),{min:0,max:30000,step:1});
        input(inspector,'Szerokość (%)',obj.w,v=>obj.w=clamp(Number(v),4,100),{min:4,max:100});
        if(type==='freephoto'){
            input(inspector,'Wysokość zdjęcia (px)',obj.h,v=>obj.h=clamp(Number(v),60,1200),{min:60,max:1200,step:1});
        }
        if(type==='photos'||type==='freephoto'){
            if(type==='photos'){
                input(inspector,'Kolumny',layout.columns,v=>layout.columns=Number(v),{values:[[1,'1'],[2,'2'],[3,'3'],[4,'4'],[5,'5'],[6,'6'],[7,'7'],[8,'8']]});
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
                input(inspector,'Czcionka',obj.font,v=>obj.font=v,{values:fontChoices});
                input(inspector,'Rozmiar tekstu (px)',obj.size,v=>obj.size=clamp(Number(v),8,160),{min:8,max:160});
                input(inspector,'Grubość',obj.weight,v=>obj.weight=Number(v),{values:
                    [100,200,300,400,500,600,700,800,900].map(v=>[v,String(v)])});
                input(inspector,'Kolor',obj.color,v=>obj.color=v,{type:'color'});
                input(inspector,'Interlinia',obj.leading,v=>obj.leading=clamp(Number(v),.7,4),{min:.7,max:4,step:.05});
                input(inspector,'Odstęp liter (px)',obj.spacing,v=>obj.spacing=clamp(Number(v),-10,25),{min:-10,max:25,step:.1});
                input(inspector,'Wyrównanie',obj.align||'left',v=>obj.align=v,{values:[
                    ['left','Do lewej'],['center','Do środka'],['right','Do prawej'],['justify','Justuj']]});
                input(inspector,'Kursywa',String(Boolean(obj.italic)),v=>obj.italic=v==='true',{values:[
                    ['false','Wyłączona'],['true','Włączona']]});
                input(inspector,'Wielkie litery',String(Boolean(obj.uppercase)),v=>obj.uppercase=v==='true',{values:[
                    ['false','Bez zmiany'],['true','WERSALIKI']]});
                input(inspector,'Przezroczystość (%)',obj.opacity??100,v=>obj.opacity=clamp(Number(v),0,100),{min:0,max:100});
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
    // Compare all visible items using native (not zoom-scaled) pixel positions.
    // The active item itself is excluded, so its own edges never trigger snapping.
    function otherRects(excludeId) {
        return [...canvas.querySelectorAll('.gv-element')]
            .filter(el => el.dataset.id !== excludeId && el.offsetWidth > 0 && el.offsetHeight > 0)
            .map(el => ({
                x:el.offsetLeft,y:el.offsetTop,w:el.offsetWidth,h:el.offsetHeight,
            }));
    }
    function guideLines(match, rect) {
        canvas.querySelectorAll('.gv-align-guide').forEach(node => node.remove());
        if (!guides || device!=='desktop') return;
        // Two reference rulers remain visible while an item is selected.
        // Green guides appear when an edge or center matches another item.
        const lines = [];
        if (rect) {
            lines.push(['vertical',rect.x,'gv-reference','X '+Math.round(rect.x)+' px']);
            lines.push(['horizontal',rect.y,'gv-reference','Y '+Math.round(rect.y)+' px']);
        }
        if (match) {
            if (Number.isFinite(match.vertical)) lines.push(['vertical',match.vertical,'gv-match','Wyrównanie']);
            if (Number.isFinite(match.horizontal)) lines.push(['horizontal',match.horizontal,'gv-match','Wyrównanie']);
        }
        for (const [direction,value,style,label] of lines) {
            if (!Number.isFinite(value)) continue;
            const line = html('div','gv-align-guide gv-align-'+direction+' '+style);
            line.style[direction==='vertical'?'left':'top']=value+'px';
            if (style==='gv-reference') {
                const chip=html('span','gv-guide-label',label);
                line.appendChild(chip);
            }
            canvas.appendChild(line);
        }
    }
    function renderSelectionGuides() {
        if (!guides || device!=='desktop') {guideLines(null);return;}
        const element=[...canvas.querySelectorAll('.gv-element')].find(el=>el.dataset.id===selected);
        if (!element) {guideLines(null);return;}
        const rect={x:element.offsetLeft,y:element.offsetTop,w:element.offsetWidth,h:element.offsetHeight};
        const engine=window.FoodFotoGalleryGuides;
        const match=engine?.align(rect,otherRects(selected),{threshold:9,snap:false,guides:true}) ?? null;
        guideLines(match,rect);
    }
    function precise(value) { return Math.round(value * 10000) / 10000; }
    function alignRect(id, rect) {
        const engine = window.FoodFotoGalleryGuides;
        // Do not block editing if an old browser has a stale cached helper file.
        if (!engine) return {x:rect.x,y:rect.y,vertical:null,horizontal:null};
        return engine.align(rect,otherRects(id),{
            threshold:7, snap, guides,
        });
    }
    function pointFromModel(obj) {
        return {x:Number(obj.x)*canvas.offsetWidth/100,y:Number(obj.y),
            w:Number(obj.w)*canvas.offsetWidth/100,h:Number(obj.h)||80};
    }
    function setPrecisePosition(obj,x,y) {
        obj.x=precise(clamp(x/canvas.offsetWidth*100,0,95));
        obj.y=precise(clamp(y,0,30000));
    }

    function beginDrag(event,id){
        if(event.button!==0)return;
        // Tablet/mobile previews are stacked responsively, not pixel-positioned.
        // Move geometry on desktop; mobile typography remains independently editable.
        if(device!=='desktop'){select(id);return;}
        if(event.target.closest('.gv-thumb'))return;
        const selectedInfo=id.startsWith('photo-')?
            {obj:photoPosition(Number(id.slice(6)),orderedPhotos().findIndex(p=>p.id===Number(id.slice(6))))}
            : {obj:layout.blocks[id]||(layout.extras||[]).find(e=>e.id===id)};
        if(!selectedInfo.obj)return;
        const obj=selectedInfo.obj,rect=canvas.getBoundingClientRect();
        const resize=Boolean(event.target.closest('[data-resize]'));
        const target=event.currentTarget;
        drag={id,obj,x0:event.clientX,y0:event.clientY,initialX:Number(obj.x),
            initialY:Number(obj.y),initialW:Number(obj.w),resize,rectWidth:rect.width,
            element:target, width:target.offsetWidth,height:target.offsetHeight};
        event.preventDefault();event.currentTarget.setPointerCapture(event.pointerId);
        selected=id;
        root.querySelectorAll('.gv-element').forEach(el=>el.classList.toggle('gv-selected',el.dataset.id===id));
        // Selection updates the right panel immediately, including for photos,
        // regardless of whether the browser fires a click after dragging.
        updateInspector();
    }
    canvas.addEventListener('pointermove',event=>{
        if(!drag)return;
        const dx=(event.clientX-drag.x0)/currentScale;
        const dy=(event.clientY-drag.y0)/currentScale;
        if(drag.resize){
            const desiredWidth=drag.initialW+dx/canvas.offsetWidth*100;
            drag.obj.w=precise(clamp(desiredWidth,4,100));
            guideLines(null);
        }else{
            const rect={
                x:drag.initialX*canvas.offsetWidth/100+dx,
                y:drag.initialY+dy,
                w:drag.width,h:drag.height,
            };
            const match=alignRect(drag.id,rect);
            setPrecisePosition(drag.obj,match.x,match.y);
            guideLines(match,{
                x:Number(drag.obj.x)*canvas.offsetWidth/100,
                y:Number(drag.obj.y),w:drag.width,h:drag.height,
            });
        }
        drag.element.style.left=drag.obj.x+'%';
        drag.element.style.top=drag.obj.y+'px';
        drag.element.style.width=drag.obj.w+'%';
        progress();
    });
    const finish=()=>{
        if(!drag)return;
        drag=null;
        render();
    };
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
    $('#gv-guides').addEventListener('change',e=>{
        guides=e.target.checked;
        renderSelectionGuides();
    });
    zoomSelect.addEventListener('change',e=>{
        zoomMode=e.target.value;
        scrollArea.scrollLeft=0;
        scheduleFit();
    });
    const gallerySwitch=$('#gv-gallery-switch');
    gallerySwitch.addEventListener('change', event => {
        const target=event.target.value;
        if (dirty && !confirm('Masz niezapisane zmiany. Przejść do innej galerii bez ich zapisywania?')) {
            gallerySwitch.value=window.location.href.split('#')[0];
            // Restore selected option even when browser normalizes query params.
            [...gallerySwitch.options].forEach(opt=>{
                opt.selected=opt.value.includes('/galleries/'+gallery.id+'/builder');
            });
            return;
        }
        dirty=false;
        window.location.assign(target);
    });
    focusButton.addEventListener('click',()=>setFocus(!root.classList.contains('gv-focus')));
    document.addEventListener('keydown',event=>{
        if(event.key==='Escape'&&root.classList.contains('gv-focus'))setFocus(false);
        if(device !== 'desktop' || !['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key))return;
        const active=document.activeElement;
        if(active && (active.matches('input,select,textarea,[contenteditable="true"]') ||
            active.closest('[contenteditable="true"]')))return;
        const ref=selectedObject();
        if(!ref)return;
        event.preventDefault();
        const amount=event.shiftKey ? 10 : 1;
        let deltaX=0,deltaY=0;
        if(event.key==='ArrowLeft')deltaX=-amount;
        if(event.key==='ArrowRight')deltaX=amount;
        if(event.key==='ArrowUp')deltaY=-amount;
        if(event.key==='ArrowDown')deltaY=amount;
        const position=pointFromModel(ref.obj);
        // Arrow keys are exact. Snapping applies only to mouse dragging.
        setPrecisePosition(ref.obj,position.x+deltaX,position.y+deltaY);
        progress();
        render();
        // Show alignment lines (without moving the element) while fine tuning.
        const after=pointFromModel(ref.obj);
        const engine=window.FoodFotoGalleryGuides;
        if(engine && guides)renderSelectionGuides();
    });
    if(typeof ResizeObserver!=='undefined'){
        const observer = new ResizeObserver(scheduleFit);
        observer.observe(scrollArea);
        observer.observe(canvas);
    } else window.addEventListener('resize',scheduleFit);
    $('#gv-canvas-height').addEventListener('change',e=>{
        layout.canvas_height=clamp(Number(e.target.value)||900,450,30000);
        e.target.value=String(layout.canvas_height);
        progress();render();
    });
    $('#gv-mode').addEventListener('change',e=>{
        layout.photo_mode=e.target.value;
        if(layout.photo_mode==='grid' && selected.startsWith('photo-')) selected='photos';
        if(layout.photo_mode==='free' && selected==='photos') {
            const first=orderedPhotos()[0];
            if(first)selected='photo-'+first.id;
        }
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
        layout.individual_photos = normalizePositions(layout.individual_photos);
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
    // Open wide by default on desktop. Close with Esc or the toolbar button.
    setFocus(window.innerWidth >= 1200);
})();