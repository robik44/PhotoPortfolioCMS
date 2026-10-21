(function () {
  'use strict';
  const icons = {
    message: '<path d="M20 11.5a7.5 7.5 0 0 1-7.5 7.5c-1.1 0-2.2-.25-3.15-.7L4 20l1.7-5.35A7.5 7.5 0 1 1 20 11.5Z"/>',
    bowl: '<path d="M3 13h18c-.5 5-3.5 8-9 8s-8.5-3-9-8Z"/><path d="M6 9c1 0 2-1 2-3m4 3c1 0 2-1 2-3m4 3c1 0 2-1 2-3"/><path d="m9 13 8-10 2 1-6 9"/>',
    image: '<rect x="3" y="4" width="18" height="16" rx="1.5"/><circle cx="8.5" cy="9" r="1.5"/><path d="m4 18 5-5 3 3 3-4 5 5"/>',
    camera: '<path d="M4 7h3l2-2h6l2 2h3v12H4Z"/><circle cx="12" cy="13" r="4"/>',
    lightbulb: '<path d="M9 18h6M10 21h4M8 14a7 7 0 1 1 8 0c-.7.7-1 1.5-1 2H9c0-.5-.3-1.3-1-2Z"/>',
    check: '<circle cx="12" cy="12" r="9"/><path d="m7 12 3 3 7-7"/>'
  };
  const iconLabels = [
    ['message', 'Rozmowa'], ['bowl', 'Stylizacja / miska'],
    ['image', 'Zdjęcie'], ['camera', 'Aparat'],
    ['lightbulb', 'Pomysł'], ['check', 'Gotowe / zatwierdzone']
  ];
  const defaultSteps = [
    {icon: 'message', title: '1. Rozmowa i pomysł', description: 'Poznaję Twoje potrzeby, cel i charakter marki.'},
    {icon: 'bowl', title: '2. Przygotowanie i stylizacja', description: 'Tworzę koncepcję, przygotowuję produkty i aranżuję plan zdjęciowy.'},
    {icon: 'image', title: '3. Gotowe kadry', description: 'Otrzymujesz dopracowane zdjęcia gotowe do publikacji i użycia w materiałach.'}
  ];
  const defaultDesign = {
    background: '#faf9f4', wash_color: '#f1e7c9', wash_opacity: 53,
    branch_visible: true, branch_opacity: 38, branch_width: 250, branch_x: -65, branch_y: -16,
    decorative_photo_id: null, decorative_photo_opacity: 35, decorative_photo_width: 340,
    decorative_photo_x: 0, decorative_photo_y: 0, decorative_photo_rotate: 0,
    show_numbers: true, number_size: 38, icon_border_width: 1,
    connector_style: 'line', connector_length: 26,
    text: '#24241f', muted: '#64675e',
    accent: '#a7b593', icon_background: '#f2f6eb', icon_color: '#858d82',
    pad_x: 54, pad_y: 70, head_gap: 56, section_gap: 46,
    steps_gap: 24, icon_size: 82,
    title_size: 58, title_weight: 400, heading_italic: true, lead_size: 18,
    step_title_size: 23, step_description_size: 16,
    heading_font: 'Georgia', body_font: 'Arial', show_arrows: true
  };
  let fontChoices = [];
  let mediaPhotos = [];
  function configurePhotos(values) { mediaPhotos = Array.isArray(values) ? values : []; }
  let typographyEditor = null;
  const activeParts = new WeakMap();
  const moveModes = new WeakMap();
  const selectedMoves = new WeakMap();
  const moveKeys = [
    ['kicker','Mała etykieta „JAK PRACUJĘ”'],
    ['title','Duży nagłówek'],
    ['intro','Opis z prawej'],
    ['branch','Gałązka'],
    ['decorative_photo','Dodatkowy obrazek'],
    ...[1,2,3].flatMap(i=>[
      ['step'+i+'_number','Etap '+i+' — numer'],
      ['step'+i+'_icon','Etap '+i+' — ikona'],
      ['step'+i+'_title','Etap '+i+' — tytuł'],
      ['step'+i+'_description','Etap '+i+' — opis'],
      ...(i<3 ? [['step'+i+'_connector','Łącznik za etapem '+i]] : [])
    ])
  ];
  const moveName = key => moveKeys.find(p=>p[0]===key)?.[1] || key;
  const movePosition = (item,key)=>{
    item.process_positions ??= {};
    const val=item.process_positions[key];
    if(!val || typeof val!=='object' || Array.isArray(val)) return {x:0,y:0};
    return {x:safeNumber(val.x,0,-1800,1800),y:safeNumber(val.y,0,-1000,1000)};
  };
  function setMove(node,item,key){
    if(!node) return;
    node.dataset.processMove=key;
    const coords=movePosition(item,key);
    node.style.setProperty('--ff-part-x',coords.x+'px');
    node.style.setProperty('--ff-part-y',coords.y+'px');
  }

  function attachTypography(editor) { typographyEditor = editor; }
  const typographyParts = [
    ['kicker','Etykieta „JAK PRACUJĘ”','text'],
    ['title','Nagłówek główny','heading'],
    ['intro','Opis wprowadzający','text'],
    ...[1,2,3].flatMap(i => [
      ['step'+i+'_number','Etap '+i+' — numer','text'],
      ['step'+i+'_title','Etap '+i+' — tytuł','heading'],
      ['step'+i+'_description','Etap '+i+' — opis','text']
    ])
  ];
  function defaultTextStyle(item,key) {
    const d = item.process_design;
    const heading = key==='title' || key.endsWith('_title');
    const kicker = key==='kicker';
    const numeral = key.endsWith('_number');
    const title = key==='title';
    const intro = key==='intro';
    const size = numeral ? d.number_size : kicker ? 11 : title ? d.title_size : intro ? d.lead_size : heading ? d.step_title_size : d.step_description_size;
    const color = kicker || numeral ? d.accent : (heading ? d.text : d.muted);
    return {
      font_family: numeral ? 'Georgia' : (kicker || intro || (!heading) ? d.body_font : d.heading_font),
      font_size: size,
      font_weight: kicker ? 600 : title ? d.title_weight : heading ? 400 : 400,
      font_style: numeral || (title && d.heading_italic !== false) ? 'italic' : 'normal',
      text_transform: kicker ? 'uppercase' : 'none',
      text_align: 'left',
      line_height: numeral ? 1 : kicker ? 1.5 : title ? 1.08 : intro ? 1.62 : heading ? 1.18 : 1.55,
      letter_spacing: kicker ? .19 : title ? -.025 : 0,
      letter_spacing_unit: kicker || title ? 'em' : 'px',
      color,
      color_alpha: 100,
      opacity: 100,
      margin_top: 0,
      margin_bottom: kicker ? 18 : heading && !title ? 9 : 0
    };
  }
  function typographyItem(item,key) {
    initialize(item);
    const type = typographyParts.find(row => row[0]===key)?.[2] || 'text';
    item.process_typography ??= {};
    const proxy = item.process_typography[key] ??= {type,style:{}};
    proxy.type = type;
    proxy.style = defaultTextStyle(item,key);
    return proxy;
  }
  function activate(item,key) { if (typographyParts.some(part=>part[0]===key)) activeParts.set(item,key); }
  function currentPart(item) { return activeParts.get(item) || 'title'; }
  function configure(choices) { fontChoices = Array.isArray(choices) ? choices : []; }
  function initialize(item) {
    if (!item.process_kicker && item.process_kicker !== '') item.process_kicker = 'JAK PRACUJĘ';
    if (!item.process_title && item.process_title !== '') item.process_title = 'Od pomysłu do gotowych kadrów';
    if (!item.process_intro && item.process_intro !== '') item.process_intro = 'Dbam o każdy detal — od koncepcji, przez stylizację, po finalne zdjęcia. Pracuję elastycznie, dopasowując się do potrzeb Twojej marki.';
    if (!Array.isArray(item.process_steps) || item.process_steps.length !== 3) {
      item.process_steps = defaultSteps.map(step => ({...step}));
    }
    if (!item.process_design || typeof item.process_design !== 'object' || Array.isArray(item.process_design)) {
      item.process_design = {};
    }
    for (const [key, value] of Object.entries(defaultDesign)) {
      if (item.process_design[key] === undefined || item.process_design[key] === null)
        item.process_design[key] = value;
    }
    if(!item.process_positions || typeof item.process_positions !== 'object'
        || Array.isArray(item.process_positions)) item.process_positions={};
    return item;
  }
  const tag = (name, className, text) => {
    const node = document.createElement(name);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
  };
  function createIcon(name) {
    const ns = 'http://www.w3.org/2000/svg';
    const svg = document.createElementNS(ns, 'svg');
    svg.setAttribute('viewBox','0 0 24 24');
    svg.setAttribute('aria-hidden','true');
    svg.setAttribute('class','ff-process-icon');
    svg.innerHTML = icons[name] || icons.message;
    return svg;
  }
  function safeColor(value, fallback) {
    return /^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback;
  }
  function safeNumber(value, fallback, low, high) {
    const number = Number(value);
    return Number.isFinite(number) ? Math.max(low, Math.min(high, number)) : fallback;
  }
  function fontCSS(id, fallback) {
    const found = fontChoices.find(c => c.value === id);
    return found ? found.css : fallback;
  }
  function render(item, onTextClick = null) {
    initialize(item);
    const d = item.process_design;
    const section = tag('section', 'ff-process');
    if (d.show_numbers === false) section.classList.add('ff-process-no-numbers');
    if (d.connector_style === 'none' || d.show_arrows === false) section.classList.add('ff-process-hide-arrows');
    if (d.connector_style === 'arrow') section.classList.add('ff-process-arrow-connectors');
    if (d.show_arrows === false) section.classList.add('ff-process-hide-arrows');
    const cssVars = {
      '--ff-process-background': safeColor(d.background,'#faf9f4'),
      '--ff-process-wash-color': safeColor(d.wash_color,'#f1e7c9'),
      '--ff-process-wash-opacity': safeNumber(d.wash_opacity,53,0,100)/100,
      '--ff-process-branch-opacity': safeNumber(d.branch_opacity,38,0,100)/100,
      '--ff-process-branch-width': safeNumber(d.branch_width,250,70,650)+'px',
      '--ff-process-branch-x': safeNumber(d.branch_x,-65,-350,350)+'px',
      '--ff-process-branch-y': safeNumber(d.branch_y,-16,-350,350)+'px',
      '--ff-process-number-size': safeNumber(d.number_size,38,12,100)+'px',
      '--ff-process-icon-border': safeNumber(d.icon_border_width,1,0,8)+'px',
      '--ff-process-connector-length': safeNumber(d.connector_length,26,0,110)+'px',
      '--ff-process-text': safeColor(d.text,'#24241f'),
      '--ff-process-muted': safeColor(d.muted,'#64675e'),
      '--ff-process-accent': safeColor(d.accent,'#a7b593'),
      '--ff-process-icon-background': safeColor(d.icon_background,'#f2f6eb'),
      '--ff-process-icon-color': safeColor(d.icon_color,'#858d82'),
      '--ff-process-pad-x': safeNumber(d.pad_x,54,0,160) + 'px',
      '--ff-process-pad-y': safeNumber(d.pad_y,70,0,160) + 'px',
      '--ff-process-head-gap': safeNumber(d.head_gap,56,0,140) + 'px',
      '--ff-process-section-gap': safeNumber(d.section_gap,46,0,150) + 'px',
      '--ff-process-steps-gap': safeNumber(d.steps_gap,24,0,120) + 'px',
      '--ff-process-icon-size': safeNumber(d.icon_size,82,36,150) + 'px',
      '--ff-process-title-size': safeNumber(d.title_size,58,20,100) + 'px',
      '--ff-process-title-weight': safeNumber(d.title_weight,400,100,900),
      '--ff-process-lead-size': safeNumber(d.lead_size,18,10,36) + 'px',
      '--ff-process-step-title-size': safeNumber(d.step_title_size,23,12,40) + 'px',
      '--ff-process-step-description-size': safeNumber(d.step_description_size,16,10,30) + 'px',
      '--ff-process-heading-font': fontCSS(d.heading_font,'Georgia, serif'),
      '--ff-process-body-font': fontCSS(d.body_font,'Arial, sans-serif')
    };
    for (const [key,value] of Object.entries(cssVars)) section.style.setProperty(key,String(value));
    if(onTextClick){
      section.classList.add('is-editing');
      if(moveModes.get(item)) section.classList.add('is-moving');
    }
    const selectPart = key => {
      selectedMoves.set(item,key);
      if(typographyParts.some(part=>part[0]===key)) activate(item,key);
      if(onTextClick) onTextClick(key);
    };
    const movable = (node,key) => {
      setMove(node,item,key);
      if(!onTextClick) return node;
      node.setAttribute('title',moveName(key)+' — kliknij, aby wybrać. Tryb przesuwania: przeciągnij.');
      node.addEventListener('pointerdown',event=>{
        if(!moveModes.get(item) || event.button!==0) return;
        event.stopPropagation();
        event.preventDefault();
        const orig=movePosition(item,key);
        const startX=event.clientX,startY=event.clientY;
        const unit=section.getBoundingClientRect().width / Math.max(1,section.offsetWidth);
        const scale=Number.isFinite(unit)&&unit>0?unit:1;
        const pointerId=event.pointerId;
        node.setPointerCapture(pointerId);
        let moved=false;
        const onMove=e=>{
          if(e.pointerId!==pointerId)return;
          const dx=(e.clientX-startX)/scale,dy=(e.clientY-startY)/scale;
          if(Math.abs(dx)>1||Math.abs(dy)>1)moved=true;
          const x=Math.round(Math.max(-1800,Math.min(1800,orig.x+dx)));
          const y=Math.round(Math.max(-1000,Math.min(1000,orig.y+dy)));
          item.process_positions[key]={x,y};
          setMove(node,item,key);
        };
        const finish=e=>{
          if(e.pointerId!==pointerId)return;
          node.removeEventListener('pointermove',onMove);
          node.removeEventListener('pointerup',finish);
          node.removeEventListener('pointercancel',finish);
          if(node.hasPointerCapture(pointerId))node.releasePointerCapture(pointerId);
          selectPart(key);
        };
        node.addEventListener('pointermove',onMove);
        node.addEventListener('pointerup',finish);
        node.addEventListener('pointercancel',finish);
      });
      node.addEventListener('click',event=>{
        if(moveModes.get(item)){
          event.stopPropagation();
          event.preventDefault();
          return;
        }
        if(!node.dataset.processPart){
          event.stopPropagation();
          selectPart(key);
        }
      });
      return node;
    };
    const assignText = (node,key) => {
      node.dataset.processPart = key;
      if (typographyEditor) typographyEditor.apply(node, typographyItem(item,key));
      movable(node,key);
      if (onTextClick) {
        node.style.cursor = 'text';
        node.addEventListener('mousedown', event => event.stopPropagation());
        node.addEventListener('click', event => {
          event.stopPropagation();
          if(moveModes.get(item))return;
          selectPart(key);
        });
      }
      return node;
    };
    if (d.branch_visible !== false) {
      const branch = tag('div','ff-process-branch');
      branch.setAttribute('aria-hidden','true');
      const branchImage = tag('img','ff-process-branch-art');
      branchImage.src='/images/foodfoto-botanical-branch.svg';
      branchImage.alt='';
      branchImage.draggable=false;
      branch.appendChild(branchImage);
      section.appendChild(movable(branch,'branch'));
    }
    const decoration = mediaPhotos.find(photo=>Number(photo.id)===Number(d.decorative_photo_id));
    if (decoration) {
      const photo = tag('img','ff-process-decorative-photo');
      photo.src = decoration.url || decoration.thumbnail_url;
      photo.alt = '';
      photo.setAttribute('aria-hidden','true');
      photo.loading='lazy';
      photo.style.width = safeNumber(d.decorative_photo_width,340,40,900)+'px';
      photo.style.left = safeNumber(d.decorative_photo_x,0,-300,100)+'%';
      photo.style.top = safeNumber(d.decorative_photo_y,0,-500,500)+'px';
      photo.style.opacity = safeNumber(d.decorative_photo_opacity,35,0,100)/100;
      photo.style.transform='rotate('+safeNumber(d.decorative_photo_rotate,0,-180,180)+'deg)';
      section.appendChild(movable(photo,'decorative_photo'));
    }
    const intro = tag('div','ff-process-intro');
    const leadLeft = tag('div');
    leadLeft.appendChild(assignText(tag('p','ff-process-eyebrow',item.process_kicker),'kicker'));
    leadLeft.appendChild(assignText(tag('h2','ff-process-title',item.process_title),'title'));
    intro.appendChild(leadLeft);
    intro.appendChild(assignText(tag('p','ff-process-lead',item.process_intro),'intro'));
    section.appendChild(intro);
    const steps = tag('div','ff-process-steps');
    item.process_steps.slice(0,3).forEach((step,index) => {
      const col = tag('article','ff-process-step');
      const number = tag('span','ff-process-number',String(index+1).padStart(2,'0'));
      number.setAttribute('aria-hidden','true');
      col.appendChild(assignText(number,'step'+(index+1)+'_number'));
      const bubble = tag('div','ff-process-bubble');
      bubble.appendChild(createIcon(iconLabels.some(i=>i[0]===step.icon) ? step.icon : defaultSteps[index].icon));
      col.appendChild(movable(bubble,'step'+(index+1)+'_icon'));
      const text = tag('div');
      text.appendChild(assignText(tag('h3','ff-process-step-title',String(step.title||'').replace(/^\s*[1-3][.)]\s*/,'')),'step'+(index+1)+'_title'));
      text.appendChild(assignText(tag('p','ff-process-step-description',step.description),'step'+(index+1)+'_description'));
      col.appendChild(text);
      if(index<2){
        const connector=tag('span','ff-process-connector');
        connector.setAttribute('aria-hidden','true');
        col.appendChild(movable(connector,'step'+(index+1)+'_connector'));
      }
      steps.appendChild(col);
    });
    section.appendChild(steps);
    return section;
  }
  function panel(container,item,rerender) {
    if (item.type !== 'process_steps') return;
    initialize(item);
    const header = tag('p','', 'Każdy tekst i każda ikona są edytowalne. Wysokość sekcji dopasowuje się automatycznie.');
    header.style.cssText = 'color:#666;font-size:12px;line-height:1.5;';
    container.appendChild(header);
    const field = (root,label,value,kind,change,opts={}) => {
      const wrap = tag('label','fve-field');
      wrap.style.display='block';
      const caption=tag('span','',label);
      caption.style.display='block';
      caption.style.marginBottom='5px';
      wrap.appendChild(caption);
      let input;
      if (kind==='select') {
        input=tag('select');
        (opts.choices||[]).forEach(choice=>{
          const option=tag('option','',choice[1]);
          option.value=String(choice[0]);input.appendChild(option);
        });
      } else {
        input=tag(kind==='textarea'?'textarea':'input');
        if(kind!=='textarea') input.type=kind;
      }
      if(kind==='number'){input.min=String(opts.min??0);input.max=String(opts.max??200);input.step=String(opts.step??1);}
      input.value=value??'';
      input.style.width='100%';
      input.addEventListener(kind==='select'?'change':'input',()=>{
        const v=kind==='number'?safeNumber(input.value,Number(value)||0,opts.min??0,opts.max??200):input.value;
        change(v);rerender();
      });
      wrap.appendChild(input);root.appendChild(wrap);
      return input;
    };
    const sectionTitle=text=>{const h=tag('h4','',text);h.style.cssText='margin:24px 0 6px;font-weight:700;border-top:1px solid #ddd;padding-top:12px;';container.appendChild(h);};
    const d=item.process_design;
    sectionTitle('Położenie każdego elementu — przesuń myszą');
    const dragIsOn=moveModes.get(item)===true;
    field(container,'Tryb swobodnego przesuwania',dragIsOn?'yes':'no','select',value=>{
      moveModes.set(item,value==='yes');
      container.innerHTML='';
      panel(container,item,rerender);
      rerender();
    },{choices:[['no','Wyłączony — kliknięcie otwiera typografię'],['yes','WŁĄCZONY — złap każdy element i przeciągnij']]});
    const info=tag('p','', 'Włącz przesuwanie i przeciągaj pojedyncze teksty, numery, ikony, gałązkę oraz linie między etapami. Możesz również wprowadzić dokładne X i Y poniżej.');
    info.style.cssText='font:12px/1.5 Arial;color:#657368';
    container.appendChild(info);
    const selectedKey=selectedMoves.get(item)||currentPart(item);
    field(container,'Wybrany element',selectedKey,'select',value=>{
      selectedMoves.set(item,value);
      if(typographyParts.some(part=>part[0]===value))activate(item,value);
      container.innerHTML='';
      panel(container,item,rerender);
    },{choices:moveKeys.map(([key,name])=>[key,name])});
    const coord=movePosition(item,selectedKey);
    field(container,'Przesunięcie poziome X (px)',coord.x,'number',value=>{
      item.process_positions[selectedKey]={...movePosition(item,selectedKey),x:Math.round(value)};
    },{min:-1800,max:1800,step:1});
    field(container,'Przesunięcie pionowe Y (px)',coord.y,'number',value=>{
      item.process_positions[selectedKey]={...movePosition(item,selectedKey),y:Math.round(value)};
    },{min:-1000,max:1000,step:1});
    const reset=tag('button','fve-button','Wyzeruj położenie wybranego elementu');
    reset.type='button';reset.style.cssText='width:100%;margin:8px 0 12px';
    reset.onclick=()=>{
      delete item.process_positions[selectedKey];
      container.innerHTML='';
      panel(container,item,rerender);
      rerender();
    };
    container.appendChild(reset);
    sectionTitle('Pełna typografia — wszystkie 12 napisów');
    const picker = field(container,'Wybierz napis do edycji',currentPart(item),'select',key=>{
      activate(item,key);
      // Rebuild only this module panel; do not discard other elements or settings.
      container.innerHTML = '';
      panel(container,item,rerender);
    },{choices:typographyParts.map(([key,label])=>[key,label])});
    picker.title = 'Możesz też kliknąć napis bezpośrednio w podglądzie.';
    const helper=tag('p','', 'Kliknij dowolny napis w sekcji lub wybierz go powyżej. Poniżej masz te same funkcje co w zwykłym polu tekstowym.');
    helper.style.cssText='font-size:12px;color:#666;line-height:1.5;';
    container.appendChild(helper);
    if(typographyEditor){
      const part=typographyItem(item,currentPart(item));
      typographyEditor.panel(container,part,rerender,'fve-field');
    }
    sectionTitle('Nagłówek sekcji');
    field(container,'Etykieta',item.process_kicker,'text',v=>item.process_kicker=v);
    field(container,'Duży nagłówek',item.process_title,'textarea',v=>item.process_title=v);
    field(container,'Opis po prawej',item.process_intro,'textarea',v=>item.process_intro=v);
    item.process_steps.forEach((step,index)=>{
      sectionTitle('Etap '+(index+1));
      field(container,'Ikona',step.icon,'select',v=>step.icon=v,{choices:iconLabels});
      field(container,'Tytuł',step.title,'textarea',v=>step.title=v);
      field(container,'Opis',step.description,'textarea',v=>step.description=v);
    });
    field(container,'Nagłówek główny kursywą',d.heading_italic===false?'no':'yes','select',v=>d.heading_italic=v==='yes',{choices:[['yes','Tak, jak na projekcie'],['no','Nie, zwykły krój']]});
    sectionTitle('Kolory');
    for(const [key,label] of [
      ['background','Kolor bazowy tła'],['wash_color','Kolor żółtej poświaty'],['text','Nagłówki'],['muted','Teksty opisów'],
      ['accent','Etykieta, linia i strzałki'],['icon_background','Tło ikon'],
      ['icon_color','Kolor ikon']
    ])field(container,label,d[key],'color',v=>d[key]=v);
    sectionTitle('Poświata i gałązka za napisami');
    field(container,'Intensywność poświaty (%)',d.wash_opacity,'number',v=>d.wash_opacity=v,{min:0,max:100,step:1});
    field(container,'Pokaż rysunek gałązki',d.branch_visible===false?'no':'yes','select',v=>d.branch_visible=v==='yes',{choices:[['yes','Tak'],['no','Nie']]});
    for(const [key,label,min,max] of [
      ['branch_opacity','Krycie gałązki (%)',0,100],
      ['branch_width','Szerokość gałązki (px)',70,650],
      ['branch_x','Pozycja gałązki w poziomie (px)',-350,350],
      ['branch_y','Pozycja gałązki w pionie (px)',-350,350]
    ])field(container,label,d[key],'number',v=>d[key]=v,{min,max,step:1});
    sectionTitle('Własny obrazek dekoracyjny pod tekstami');
    field(container,'Obraz z biblioteki zdjęć',d.decorative_photo_id||'','select',v=>d.decorative_photo_id=v?Number(v):null,{choices:[['','Bez dodatkowego obrazka'],...mediaPhotos.map(photo=>[photo.id,photo.title||photo.alt||('Zdjęcie '+photo.id)])]});
    for(const [key,label,min,max] of [
      ['decorative_photo_opacity','Krycie dekoracji (%)',0,100],
      ['decorative_photo_width','Szerokość obrazka (px)',40,900],
      ['decorative_photo_x','Pozycja pozioma (%)',-300,100],
      ['decorative_photo_y','Pozycja pionowa (px)',-500,500],
      ['decorative_photo_rotate','Obrót (stopnie)',-180,180]
    ])field(container,label,d[key],'number',v=>d[key]=v,{min,max,step:1});
    sectionTitle('Numeracja i łączniki');
    field(container,'Wyświetl numery 01 / 02 / 03',d.show_numbers===false?'no':'yes','select',v=>d.show_numbers=v==='yes',{choices:[['yes','Tak'],['no','Nie']]});
    field(container,'Wielkość numerów (px)',d.number_size,'number',v=>d.number_size=v,{min:12,max:100,step:1});
    field(container,'Grubość obramowania ikon (px)',d.icon_border_width,'number',v=>d.icon_border_width=v,{min:0,max:8,step:0.1});
    field(container,'Łączniki etapów',d.connector_style||'line','select',v=>{d.connector_style=v;d.show_arrows=v!=='none';},{choices:[['line','Delikatne linie'],['arrow','Strzałki'],['none','Bez łączników']]});
    field(container,'Długość łączników (px)',d.connector_length,'number',v=>d.connector_length=v,{min:0,max:110,step:1});
    sectionTitle('Odstępy i ikony');
    for(const [key,label,min,max] of [
      ['pad_x','Marginesy boczne (px)',0,160],
      ['pad_y','Marginesy góra/dół (px)',0,160],
      ['head_gap','Odstęp między tytułem i opisem (px)',0,140],
      ['section_gap','Odstęp nagłówka od etapów (px)',0,150],
      ['steps_gap','Odstęp między etapami (px)',0,120],
      ['icon_size','Wielkość ikon (px)',36,150]
    ])field(container,label,d[key],'number',v=>d[key]=v,{min,max,step:1});

  }
  window.FoodfotoProcess={initialize,render,panel,configure,configurePhotos,attachTypography,activate,typographyItem,typographyParts};
})();