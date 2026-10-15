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
