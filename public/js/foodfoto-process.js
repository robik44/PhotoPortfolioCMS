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
    background: '#ffffff', text: '#24241f', muted: '#64675e',
    accent: '#a7b593', icon_background: '#f2f6eb', icon_color: '#858d82',
    pad_x: 54, pad_y: 70, head_gap: 56, section_gap: 46,
    steps_gap: 24, icon_size: 82,
    title_size: 58, title_weight: 400, lead_size: 18,
    step_title_size: 23, step_description_size: 16,
    heading_font: 'Georgia', body_font: 'Arial', show_arrows: true
  };
  let fontChoices = [];
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
  function render(item) {
    initialize(item);
    const d = item.process_design;
    const section = tag('section', 'ff-process');
    if (d.show_arrows === false) section.classList.add('ff-process-hide-arrows');
    const cssVars = {
      '--ff-process-background': safeColor(d.background,'#fff'),
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
    const intro = tag('div','ff-process-intro');
    const leadLeft = tag('div');
    leadLeft.appendChild(tag('p','ff-process-eyebrow',item.process_kicker));
    leadLeft.appendChild(tag('h2','ff-process-title',item.process_title));
    intro.appendChild(leadLeft);
    intro.appendChild(tag('p','ff-process-lead',item.process_intro));
    section.appendChild(intro);
    const steps = tag('div','ff-process-steps');
    item.process_steps.slice(0,3).forEach((step,index) => {
      const col = tag('article','ff-process-step');
      const bubble = tag('div','ff-process-bubble');
      bubble.appendChild(createIcon(iconLabels.some(i=>i[0]===step.icon) ? step.icon : defaultSteps[index].icon));
      col.appendChild(bubble);
      const text = tag('div');
      text.appendChild(tag('h3','ff-process-step-title',step.title));
      text.appendChild(tag('p','ff-process-step-description',step.description));
      col.appendChild(text);
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
    const d=item.process_design;
    sectionTitle('Typografia');
    const choices = fontChoices.map(c=>[c.value,c.label]);
    const familyChoices=choices.length?choices:[['Georgia','Georgia'],['Arial','Arial']];
    field(container,'Czcionka nagłówków',d.heading_font,'select',v=>d.heading_font=v,{choices:familyChoices});
    field(container,'Czcionka opisów',d.body_font,'select',v=>d.body_font=v,{choices:familyChoices});
    for(const [key,label,min,max,step] of [
      ['title_size','Wielkość głównego nagłówka (px)',20,100,.1],
      ['title_weight','Grubość głównego nagłówka',100,900,100],
      ['lead_size','Wielkość opisu (px)',10,36,.1],
      ['step_title_size','Wielkość tytułów etapów (px)',12,40,.1],
      ['step_description_size','Wielkość opisów etapów (px)',10,30,.1]
    ])field(container,label,d[key],'number',v=>d[key]=v,{min,max,step});
    sectionTitle('Kolory');
    for(const [key,label] of [
      ['background','Tło'],['text','Nagłówki'],['muted','Teksty opisów'],
      ['accent','Etykieta, linia i strzałki'],['icon_background','Tło ikon'],
      ['icon_color','Kolor ikon']
    ])field(container,label,d[key],'color',v=>d[key]=v);
    sectionTitle('Odstępy i ikony');
    for(const [key,label,min,max] of [
      ['pad_x','Marginesy boczne (px)',0,160],
      ['pad_y','Marginesy góra/dół (px)',0,160],
      ['head_gap','Odstęp między tytułem i opisem (px)',0,140],
      ['section_gap','Odstęp nagłówka od etapów (px)',0,150],
      ['steps_gap','Odstęp między etapami (px)',0,120],
      ['icon_size','Wielkość ikon (px)',36,150]
    ])field(container,label,d[key],'number',v=>d[key]=v,{min,max,step:1});
    field(container,'Strzałki między etapami',d.show_arrows===false?'no':'yes','select',v=>d.show_arrows=v==='yes',{choices:[['yes','Pokaż'],['no','Ukryj']]});
  }
  window.FoodfotoProcess={initialize,render,panel,configure};
})();