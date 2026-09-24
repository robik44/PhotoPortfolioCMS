import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const script = readFileSync(new URL('../../public/js/builder-gallery-layout.js', import.meta.url), 'utf8');
test('large selected gallery expands the canvas enough to show final rows and adapts on resize', () => {
    const canvas = { offsetHeight: 900, style: {} };
    const gallery = { offsetHeight: 2400, style: { top: '20%' }, querySelectorAll: () => [] };
    let resize;
    runInNewContext(script, {
        document: { querySelector: () => canvas, querySelectorAll: () => [gallery], addEventListener: (_, callback) => callback() },
        window: { addEventListener: (_, callback) => { resize = callback; } },
    });
    assert.equal(canvas.style.minHeight, '3025px');
    assert.ok(3025 * .2 + gallery.offsetHeight < 3025);
    gallery.offsetHeight = 300;
    resize();
    assert.equal(canvas.style.minHeight, '900px');
});

test('pages without a selected gallery keep their existing canvas dimensions', () => {
    const canvas = { offsetHeight: 900, style: {} };
    runInNewContext(script, {
        document: { querySelector: () => canvas, querySelectorAll: () => [], addEventListener: (_, callback) => callback() },
    });
    assert.deepEqual(canvas.style, {});
});
