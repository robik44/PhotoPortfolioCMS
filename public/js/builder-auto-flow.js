// Shared, deterministic placement engine for the visual editor and published page.
// Existing freely positioned content is unaffected unless a block opts into auto flow.
(function () {
    'use strict';
    function calculateAutoFlow(items,gap=14){
  const numeric=(value,fallback=0)=>Number.isFinite(Number(value))?Number(value):fallback;
  const rows=items.map((entry,index)=>({
    id:String(entry.id),index,x:numeric(entry.x),y:numeric(entry.y),
    width:Math.max(0,numeric(entry.width)),height:Math.max(0,numeric(entry.height)),
    baseline:Math.max(0,numeric(entry.baseline)),anchor:numeric(entry.anchor),
    mode:entry.mode
  })).sort((a,b)=>a.y-b.y||a.index-b.index);
  const events=[];
  for(const source of rows){
    if(source.mode!=='auto')continue;
    const baseline=source.baseline>0?source.baseline:source.height;
    const target=rows.find(other=>
      other.id!==source.id && other.y>source.y+1 &&
      other.x<source.x+source.width-1 &&
      source.x<other.x+other.width-1
    );
    if(target && target.y<source.y+Math.max(source.height,baseline)+gap-0.5){
      const delta=Math.max(0,source.y+source.height+gap-target.y);
      if(delta>0.5){
        // Keep slight row misalignments (e.g. captions and covers) together.
        const rowStart=Math.min(target.y,...rows.filter(row=>
          row.y>source.y+1 && row.y>=target.y-10 && row.y<=target.y
        ).map(row=>row.y));
        events.push({at:rowStart,delta,kind:'collision'});
      }
    } else {
      const delta=source.height-baseline;
      if(Math.abs(delta)>0.5)events.push({at:source.y+baseline,delta,kind:'growth'});
    }
  }
  const eventGroups=new Map();
  for(const event of events){
    const key=Math.round(event.at*10)/10;
    if(!eventGroups.has(key))eventGroups.set(key,[]);
    eventGroups.get(key).push(event.delta);
  }
  const shifts=Array.from(eventGroups,([at,deltas])=>({
    at,delta:deltas.some(delta=>delta>0)
      ?Math.max(...deltas.filter(delta=>delta>0))
      :Math.min(...deltas)
  })).sort((a,b)=>a.at-b.at);
  return {
    placements:rows.map(row=>{
      const shift=shifts.reduce((total,event)=>total+(row.y>=event.at-.1?event.delta:0),0);
      return {id:row.id,top:row.y+shift-row.anchor,shift};
    }),
    shifts
  };
}
    window.BuilderAutoFlow = { calculate: calculateAutoFlow };
})();
