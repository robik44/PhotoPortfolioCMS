const test = require('node:test');
const assert = require('node:assert/strict');
const { align, normalizePositions } = require('../../public/js/gallery-visual-guides.js');

const other = [{x:100,y:170,w:80,h:80}];

test('matches left edges within seven pixels, without moving vertical coordinate', () => {
    assert.deepEqual(align({x:96,y:80,w:60,h:60},other),{
        x:100,y:80,vertical:100,horizontal:null,
    });
});

test('reports guide but does not snap when snapping is disabled', () => {
    assert.deepEqual(align({x:96,y:80,w:60,h:60},other,{snap:false}),{
        x:96,y:80,vertical:100,horizontal:null,
    });
});

test('does not jump to distant items', () => {
    assert.deepEqual(align({x:60,y:80,w:60,h:60},[{x:100,y:190,w:80,h:80}]),{
        x:60,y:80,vertical:null,horizontal:null,
    });
});

test('recognizes right edge aligned to another left edge', () => {
    assert.deepEqual(align({x:95,y:100,w:50,h:40},[{x:148,y:200,w:100,h:25}]),{
        x:98,y:100,vertical:148,horizontal:null,
    });
});

test('snaps independently to the closest X and Y alignment guides', () => {
    assert.deepEqual(align({x:96,y:206,w:50,h:40},[{x:100,y:210,w:80,h:25}]),{
        x:100,y:202.5,vertical:100,horizontal:222.5,
    });
});

test('alignment guides can be disabled independently of snapping', () => {
    assert.deepEqual(align({x:96,y:80,w:60,h:60},other,{guides:false}),{
        x:100,y:80,vertical:null,horizontal:null,
    });
});

test('one-pixel precision is preserved without a matching guide', () => {
    assert.deepEqual(align({x:101.125,y:55.25,w:32,h:29},[],{snap:true}),{
        x:101.125,y:55.25,vertical:null,horizontal:null,
    });
});

test('Laravel empty array becomes safe ID-keyed photo position object', () => {
    const empty=normalizePositions([]);
    assert.equal(Array.isArray(empty),false);
    empty['90']={x:15.25,y:400.5,w:20,h:250};
    const encoded=JSON.stringify(empty);
    assert.equal(encoded,'{"90":{"x":15.25,"y":400.5,"w":20,"h":250}}');
    assert.equal(encoded.includes('null'),false);
});

test('legacy sparse array keeps valid photo IDs and drops JSON null slots', () => {
    const legacy=[];
    legacy[90]={x:15,y:400,w:20,h:250};
    const normalized=normalizePositions(legacy);
    assert.deepEqual(Object.keys(normalized),['90']);
    assert.equal(Object.keys(JSON.parse(JSON.stringify(normalized))).length,1);
});

test('rejects null and invalid free-photo entries while preserving valid ones', () => {
    assert.deepEqual(normalizePositions({
        '90':{x:6,y:360,w:20,h:250},
        '91':null,'garbage':{x:0,y:0},
        '0':{x:0,y:0},
    }),{'90':{x:6,y:360,w:20,h:250}});
});
