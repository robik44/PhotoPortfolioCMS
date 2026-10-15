const test = require('node:test');
const assert = require('node:assert/strict');
const { align } = require('../../public/js/gallery-visual-guides.js');

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
