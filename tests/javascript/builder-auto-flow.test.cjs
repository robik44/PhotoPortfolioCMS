const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');

const ctx = {window: {}};
vm.runInNewContext(
    fs.readFileSync(path.join(__dirname, '../../public/js/builder-auto-flow.js'), 'utf8'),
    ctx
);
const layout = ctx.window.BuilderAutoFlow.calculate;
const top = (rows, id) => layout(rows).placements.find(item => item.id === id).top;

test('new automatic text makes room for all cards in the same photo row', () => {
    const rows = [
        {id:'text', mode:'auto', x:50, y:400, width:750, height:230},
        {id:'card1', mode:'free', x:0, y:480, width:280, height:300},
        {id:'card2', mode:'free', x:300, y:480, width:280, height:300},
        {id:'card3', mode:'free', x:600, y:480, width:280, height:300}
    ];
    for (const id of ['card1','card2','card3']) assert.equal(top(rows,id),644);
    assert.equal(top(rows,'text'),400);
});

test('collision with an image beginning slightly before text still moves the image', () => {
    const rows = [
        {id:'card', mode:'free', x:0, y:370, width:500, height:220},
        {id:'text', mode:'auto', x:0, y:400, width:500, height:90}
    ];
    assert.equal(top(rows,'card'),504);
});

test('manual anchor cannot cancel a new flow event', () => {
    for(const extra of [{anchor:150},{anchorContributions:{older:150}}]) {
        const rows = [
            {id:'photo', mode:'free', x:0, y:480, width:500, height:220, ...extra},
            {id:'text', mode:'auto', x:0, y:400, width:500, height:230}
        ];
        assert.equal(top(rows,'photo'),644);
    }
});

test('a later new block displaces previously anchored content without double-applying older shifts', () => {
    const rows = [
        {id:'old', mode:'auto', x:0,y:100,width:300,height:110,baseline:60},
        {id:'new', mode:'auto', x:0,y:400,width:300,height:200,anchorContributions:{old:50}},
        {id:'photo', mode:'free',x:0,y:490,width:300,height:200,anchorContributions:{old:50}}
    ];
    assert.equal(top(rows,'new'),400);
    assert.equal(top(rows,'photo'),614);
});

test('free overlays and unrelated side-by-side columns keep manual positions', () => {
    const overlay = [
        {id:'photo', mode:'free', x:0,y:370,width:500,height:220},
        {id:'overlay', mode:'free',x:0,y:400,width:500,height:90}
    ];
    assert.equal(top(overlay,'photo'),370);
    const columns = [
        {id:'left',mode:'auto',x:0,y:100,width:200,height:80},
        {id:'right',mode:'free',x:300,y:100,width:200,height:80}
    ];
    assert.equal(top(columns,'right'),100);
});

test('height change and photo-row grouping use stable shared displacement', () => {
    const growth = [
        {id:'text',mode:'auto',x:0,y:100,width:250,height:150,baseline:70},
        {id:'below',mode:'free',x:0,y:280,width:250,height:70}
    ];
    assert.equal(top(growth,'below'),360);
    const cards = [
        {id:'text1',mode:'auto',x:0,y:100,width:140,height:150},
        {id:'text2',mode:'auto',x:180,y:100,width:140,height:150},
        {id:'card1',mode:'free',x:0,y:200,width:140,height:50},
        {id:'card2',mode:'free',x:180,y:200,width:140,height:50}
    ];
    assert.equal(top(cards,'card1'),264);
    assert.equal(top(cards,'card2'),264);
});

test('production-like older process and large photo: one-pixel drag does not swap text/photo order', () => {
    const rows = [
        {id:'olderIntro',mode:'auto',x:36,y:1329,width:532,height:29,baseline:29,anchor:26.7},
        {id:'intro2',mode:'auto',x:34,y:1358,width:532,height:29,baseline:29,anchor:41.38},
        {id:'gallery',mode:'free',x:0,y:1368,width:1398,height:287.5},
        {id:'process',mode:'auto',x:0,y:1891,width:1393,height:487,baseline:487,anchor:191.68},
        {id:'widePhoto',mode:'free',x:0,y:1990,width:1400,height:257.37,anchor:196.43},
        {id:'text',mode:'auto',x:61,y:2094,width:1170,height:131,baseline:131},
        {id:'photo1',mode:'free',x:86,y:2357,width:374,height:524},
        {id:'photo2',mode:'free',x:491,y:2357,width:374,height:524},
        {id:'photo3',mode:'free',x:901,y:2357,width:374,height:524},
    ];
    let previous = null;
    for (let delta = -20; delta <= 20; delta += 1) {
        rows.find(row => row.id === 'text').y = 2094 + delta;
        const positions = Object.fromEntries(layout(rows).placements.map(item => [item.id, item.top]));
        assert.ok(positions.widePhoto >= positions.text + 131 + 13.5,
          'the automatic text must not overlap the older wide image');
        assert.equal(positions.photo1, positions.photo2);
        assert.equal(positions.photo2, positions.photo3);
        if (previous) {
          assert.ok(Math.abs(positions.widePhoto - previous.widePhoto) <= 2,
            'small drag cannot make the image jump hundreds of pixels');
        }
        previous = positions;
    }
});

test('multi-element image/text preset fits at clicked row and moves all cards in unison', () => {
    const group = 'inserted-layout';
    const rows = [
        {id:'existing',mode:'free',x:0,y:100,width:1250,height:310},
        {id:'photo',mode:'auto',flowGroup:group,x:70,y:500,width:574,height:390},
        {id:'heading',mode:'auto',flowGroup:group,x:770,y:545,width:450,height:62},
        {id:'paragraph',mode:'auto',flowGroup:group,x:770,y:653,width:450,height:80},
        {id:'button',mode:'auto',flowGroup:group,x:770,y:788,width:280,height:46},
        {id:'card1',mode:'free',x:50,y:570,width:320,height:420},
        {id:'card2',mode:'free',x:400,y:570,width:320,height:420},
        {id:'card3',mode:'free',x:750,y:570,width:320,height:420}
    ];
    assert.equal(top(rows,'existing'),100);
    assert.equal(top(rows,'photo'),500);
    assert.equal(top(rows,'heading'),545);
    assert.equal(top(rows,'paragraph'),653);
    assert.equal(top(rows,'button'),788);
    for(const id of ['card1','card2','card3']) assert.equal(top(rows,id),904);
});

test('growing a paragraph expands its whole preset slot', () => {
    const rows = [
        {id:'photo',flowGroup:'p',mode:'auto',x:0,y:300,width:600,height:390},
        {id:'paragraph',flowGroup:'p',mode:'auto',x:650,y:400,width:500,height:400},
        {id:'below',mode:'free',x:0,y:500,width:1200,height:300}
    ];
    assert.equal(top(rows,'photo'),300);
    assert.equal(top(rows,'paragraph'),400);
    assert.equal(top(rows,'below'),814);
    rows[1].height = 500;
    assert.equal(top(rows,'below'),914);
});

test('two inserted preset groups stay ordered, aligned and independently editable', () => {
    const rows = [
        {id:'first',flowGroup:'a',mode:'auto',x:0,y:300,width:600,height:390},
        {id:'second',flowGroup:'b',mode:'auto',x:0,y:500,width:600,height:390},
        {id:'old',mode:'free',x:0,y:700,width:600,height:300}
    ];
    assert.equal(top(rows,'first'),300);
    assert.equal(top(rows,'second'),704);
    assert.equal(top(rows,'old'),1108);
});

test('a preset appended at the bottom leaves its saved top unchanged', () => {
    const rows = [{id:'last',flowGroup:'p',mode:'auto',x:0,y:300,width:800,height:500}];
    assert.equal(top(rows,'last'),300);
});

test('legacy saved gallery positions above visual insertion still move as a complete row', () => {
    // 3 cards are visibly below the click, but their stored Y precedes it
    // because prior content already shifted them down in the editor.
    const group = 'insert-preset';
    const rows = [
        {id:'earlier',mode:'free',x:0,y:1930,width:1400,height:260},
        {id:'card1',mode:'free',x:50,y:2350,width:350,height:500},
        {id:'card2',mode:'free',x:450,y:2350,width:350,height:500},
        {id:'card3',mode:'free',x:850,y:2350,width:350,height:500},
        {id:'newImage',mode:'auto',flowGroup:group,
          insertionTargets:['card1','card2','card3'],
          insertionExisting:['earlier','card1','card2','card3'],
          insertionTargetTop:2580,
          x:50,y:2430,width:575,height:390},
        {id:'heading',mode:'auto',flowGroup:group,x:730,y:2475,width:500,height:70},
        {id:'paragraph',mode:'auto',flowGroup:group,x:730,y:2600,width:500,height:85},
        {id:'button',mode:'auto',flowGroup:group,x:730,y:2700,width:250,height:44},
    ];
    assert.equal(top(rows,'earlier'),1930);
    for(const id of ['card1','card2','card3']) assert.equal(top(rows,id),2834);
    assert.equal(top(rows,'newImage'),2430);
    // The longest member (390px image) defines the bottom of the section.
    rows.find(row => row.id==='newImage').height=490;
    for(const id of ['card1','card2','card3']) assert.equal(top(rows,id),2934);
});

test('visual insertion does not shift an older section whose saved position is later', () => {
    const group='inserted';
    const rows=[
        {id:'earlier',x:0,y:2000,width:1200,height:260,mode:'free'},
        {id:'newImage',x:50,y:2270,width:575,height:390,flowGroup:group,mode:'auto',
            insertionTargets:['card1','card2'],insertionExisting:['earlier','card1','card2'],
            insertionTargetTop:2310},
        {id:'newText',x:700,y:2400,width:500,height:200,flowGroup:group,mode:'auto'},
        {id:'card1',x:0,y:2310,width:400,height:500,mode:'free'},
        {id:'card2',x:500,y:2310,width:400,height:500,mode:'free'},
    ];
    assert.equal(top(rows,'earlier'),2000);
    assert.equal(top(rows,'card1'),2674);
    assert.equal(top(rows,'card2'),2674);
});
