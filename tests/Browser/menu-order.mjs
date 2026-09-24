// Run with Playwright available (or MENU_PLAYWRIGHT_MODULE pointing to its index.mjs).
// Uses the actual Blade/CSS/JS and native mouse dragging; PHP MenuOrderTest covers DB persistence.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
const playwright = await import(process.env.MENU_PLAYWRIGHT_MODULE || 'playwright');
const html = execFileSync('php', [fileURLToPath(new URL('./menu-order-fixture.php', import.meta.url))], { encoding: 'utf8' });
const script = readFileSync(process.env.MENU_ORDER_SCRIPT || new URL('../../public/js/menu-order.js', import.meta.url), 'utf8');
let passed = 0;
for (const engine of (process.env.MENU_BROWSER_ENGINES || 'chromium,firefox,webkit').split(',')) {
    const browser = await playwright[engine].launch({ headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1200 } });
        let requests = [], failSave = false;
        await page.route('http://menu.test/**', async route => {
            const path = new URL(route.request().url()).pathname;
            if (path === '/menu/reorder') {
                requests.push(route.request().postDataJSON());
                await new Promise(resolve => setTimeout(resolve, 150));
                await route.fulfill({ status: failSave ? 500 : 200, contentType: 'application/json', body: JSON.stringify({ success: !failSave }) });
            } else if (path === '/js/menu-order.js') {
                await route.fulfill({ contentType: 'application/javascript', body: script });
            } else await route.fulfill({ contentType: 'text/html', body: html });
        });
        const root = '.cms-menu-list';
        const sub = '[data-menu-sort][data-parent="1"]';
        const ids = selector => page.locator(`${selector} > [data-menu-id]`).evaluateAll(rows => rows.map(row => Number(row.dataset.menuId)));
        async function reset() { requests = []; failSave = false; await page.goto('http://menu.test/menu'); }
        async function drag(source, target, edge = 'before') {
            const start = await source.boundingBox(), end = await target.boundingBox();
            await page.mouse.move(start.x + start.width / 2, start.y + start.height / 2);
            await page.mouse.down();
            await page.mouse.move(start.x + start.width / 2 + 12, start.y + start.height / 2, { steps: 4 });
            const x = end.x + Math.min(120, end.width / 2), y = edge === 'before' ? end.y + 10 : end.y + end.height - 10;
            await page.mouse.move(x, y, { steps: 12 });
            await page.mouse.move(x, y + 1);
            await page.waitForTimeout(100);
            const marker = await page.locator('[data-menu-drop]').count();
            await page.mouse.up(); // Browser delivers drop followed by dragend.
            return marker;
        }
        for (const [list, first, last] of [[root, 1, 2], [sub, 3, 4]]) {
            await reset();
            const handle = id => page.locator(`${list} > [data-menu-id="${id}"] .cms-menu-drag`).first();
            const row = id => page.locator(`${list} > [data-menu-id="${id}"]`).locator(list === root ? ':scope > .cms-menu-item' : ':scope');
            const marker = await drag(handle(last), row(first));
            assert.deepEqual(await ids(list), [last, first], `${engine}: drop/dragend must keep new order`);
            assert.equal(marker, 1, `${engine}: visible insertion boundary`);
            await page.waitForFunction(() => document.getElementById('menu-order-status').textContent === 'Kolejność została zapisana.');
            assert.deepEqual(await ids(list), [last, first]);
            assert.deepEqual(requests, [{ parent_id: list === root ? null : 1, items: [{ id: last }, { id: first }] }]);
            await drag(handle(last), row(first), 'after');
            await page.waitForFunction(() => document.getElementById('menu-order-status').textContent === 'Kolejność została zapisana.');
            assert.deepEqual(await ids(list), [first, last], `${engine}: dragging down`);
            passed++;
        }
        await reset();
        await drag(page.locator(`${sub} > [data-menu-id="3"] .cms-menu-drag`), page.locator('[data-menu-id="5"]'));
        assert.deepEqual(await ids(sub), [3, 4]);
        assert.equal(requests.length, 0);
        passed++;
        await reset(); failSave = true;
        await drag(page.locator(`${root} > [data-menu-id="2"] .cms-menu-drag`).first(), page.locator(`${root} > [data-menu-id="1"] > .cms-menu-item`));
        await page.waitForFunction(() => document.getElementById('menu-order-status').textContent.includes('Nie udało się'));
        assert.deepEqual(await ids(root), [1, 2]);
        passed++;
        console.log(`${engine}: 4 native browser scenarios passed`);
    } finally { await browser.close(); }
}
console.log(`Passed: ${passed} browser scenarios`);
