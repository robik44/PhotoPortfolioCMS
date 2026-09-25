import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const { chromium } = await import(process.env.PHOTO_PICKER_PLAYWRIGHT_MODULE || 'playwright');
const fixture = fileURLToPath(new URL('./thumbnail-gallery-fixture.php', import.meta.url));
function render(input) {
    const html = execFileSync('php', [fixture], { input: JSON.stringify(input), encoding: 'utf8' });
    assert.ok(html.includes('<!DOCTYPE html>'), html.slice(0, 1000));
    return html;
}
const browser = await chromium.launch({ headless: true });
let passed = 0;
try {
    const page = await browser.newPage({ viewport: { width: 1500, height: 1100 } });
    let saved, missing;
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('http://thumbnails.test/**', async route => {
        const path = new URL(route.request().url()).pathname;
        if (path.startsWith('/js/') || path.startsWith('/css/')) {
            await route.fulfill({ contentType: path.endsWith('.css') ? 'text/css' : 'application/javascript', body: readFileSync(new URL('../../public'+path, import.meta.url), 'utf8') });
        } else if (path.startsWith('/storage/')) {
            const id = Number(path.match(/(\d+)\.png$/)?.[1] || 1);
            const height = [120, 240, 360][id % 3];
            await route.fulfill({ contentType: 'image/svg+xml', body: `<svg xmlns="http://www.w3.org/2000/svg" width="240" height="${height}"><rect width="240" height="${height}" fill="#e2e8f0"/><text x="60" y="70" font-size="30">LOGO</text></svg>` });
        } else if (route.request().method() === 'POST') {
            saved = route.request().postDataJSON().content;
            await route.fulfill({ contentType: 'application/json', body: '{"success":true}' });
        } else await route.fulfill({ contentType: 'text/html', body: render({ content: saved, mode: path === '/public' ? 'public' : 'builder', missing }) });
    });
    const modal = page.locator('#photo-library-dialog');
    const props = page.locator('#fve-properties');
    const rows = props.locator('.thumbnail-gallery-selection li');
    const ids = () => rows.evaluateAll(items => items.map(item => Number(item.dataset.photoId)));
    async function choose(...values) {
        await props.locator('[data-thumbnail-select]').click();
        for (const id of values) await modal.locator(`[data-library-photo="${id}"]`).click();
        await modal.locator('[data-library-confirm]').click();
    }
    async function save() {
        await page.locator('#fve-save').click();
        await page.waitForFunction(() => document.querySelector('#fve-save').textContent.includes('Zapisano'));
    }
    await page.goto('http://thumbnails.test/builder');
    await page.locator('[data-fve-add="thumbnail_gallery"]').click();
    assert.equal(await props.getByRole('heading', { name: 'Galeria miniaturek' }).count(), 1);
    await choose(2, 1, 3);
    assert.deepEqual(await ids(), [2, 1, 3]);
    assert.equal(await page.locator('#fve-content .thumbnail-gallery-grid img').count(), 3);
    await save();
    assert.equal(saved.sections[0].type, 'thumbnail_gallery');
    assert.deepEqual(saved.sections[0].photo_ids, [2, 1, 3]);
    assert.equal(saved.sections[0].image_fit, undefined);
    assert.equal(saved.sections[0].thumbnail_height, undefined);
    passed++;

    await props.locator('[data-thumbnail-select]').click();
    await modal.locator('[data-library-search]').fill('opis firmy 4');
    assert.equal(await modal.locator('[data-library-photo="4"]').isVisible(), true);
    assert.equal(await modal.locator('[data-library-photo="1"]').isVisible(), false);
    await modal.locator('[data-library-photo="4"]').click();
    await page.keyboard.press('Escape');
    assert.deepEqual(await ids(), [2, 1, 3]);
    await choose(4);
    assert.deepEqual(await ids(), [2, 1, 3, 4]);
    passed++;

    await rows.nth(3).dragTo(rows.nth(0));
    assert.deepEqual(await ids(), [4, 2, 1, 3]);
    await rows.nth(1).getByRole('button', { name: 'Usuń zdjęcie z bloku' }).click();
    assert.deepEqual(await ids(), [4, 1, 3]);
    await rows.nth(0).getByRole('button', { name: 'Przesuń później' }).click();
    assert.deepEqual(await ids(), [1, 4, 3]);
    await props.locator('[data-thumbnail-select]').click();
    assert.equal(await modal.locator('[data-library-photo="2"]').count(), 1);
    await modal.getByRole('button', { name: 'Anuluj', exact: true }).click();
    await save();
    await page.reload();
    await page.locator('#fve-content [data-thumbnail-block]').click();
    assert.deepEqual(await ids(), [1, 4, 3]);
    passed++;

    assert.equal(await props.locator('[data-thumbnail-setting="image_fit"], [data-thumbnail-setting="thumbnail_height"]').count(), 0);
    for (const [key, value] of [['columns_desktop', '6'], ['columns_tablet', '4'], ['columns_mobile', '1'], ['gap', '12']]) {
        const input = props.locator(`[data-thumbnail-setting="${key}"]`);
        await input.fill(value); await input.press('Tab');
    }
    await save();
    assert.equal(saved.sections[0].columns_desktop, 6);
    passed++;

    await page.goto('http://thumbnails.test/public');
    const grid = page.locator('.thumbnail-gallery-grid');
    assert.deepEqual(await grid.locator('img').evaluateAll(items => items.map(img => img.alt)), ['Logo 1', 'Logo 4', 'Logo 3']);
    assert.equal(await grid.locator('a, button, [role="button"], [tabindex], .gallery-item').count(), 0);
    assert.equal(await page.locator('#lightbox').count(), 0);
    const url = page.url();
    await grid.locator('img').first().click();
    assert.equal(page.url(), url);
    assert.equal(await page.locator('.lightbox.is-open').count(), 0);
    for (const [width, columns] of [[1200, 6], [800, 4], [390, 1]]) {
        await page.setViewportSize({ width, height: 1000 });
        await grid.locator('img').evaluateAll(images => Promise.all(images.map(img => {
            img.loading = 'eager';
            return img.decode();
        })));
        const css = await grid.evaluate(el => ({ columns: getComputedStyle(el).gridTemplateColumns.split(' ').length, gap: getComputedStyle(el).gap }));
        assert.deepEqual(css, { columns, gap: '12px' });
        const sizes = await grid.locator('img').evaluateAll(images => images.map(img => {
            const rect = img.getBoundingClientRect();
            return { width: rect.width, height: rect.height, ratio: img.naturalWidth / img.naturalHeight, cursor: getComputedStyle(img).cursor };
        }));
        for (const size of sizes) {
            assert.ok(Math.abs(size.height - size.width / size.ratio) < 1, 'Preserve each photo’s natural proportions');
            assert.equal(size.cursor, 'default');
        }
        assert.ok(new Set(sizes.map(size => Math.round(size.height))).size > 1, 'Mixed aspect ratios must have different heights');
        assert.equal(await grid.evaluate(el => el.scrollWidth <= el.clientWidth && el.scrollHeight <= el.clientHeight), true);
    }
    passed++;

    saved.sections[0].photo_ids = Array.from({ length: 35 }, (_, index) => index + 1);
    saved.sections[0].image_fit = 'cover';
    saved.sections[0].thumbnail_height = 40;
    missing = 2;
    await page.reload();
    assert.equal(await grid.locator('img').count(), 34);
    assert.equal(await grid.evaluate(el => el.style.getPropertyValue('--tg-fit') + el.style.getPropertyValue('--tg-height')), '');
    await grid.locator('img').evaluateAll(images => Promise.all(images.map(img => {
        img.loading = 'eager';
        return img.decode();
    })));
    await page.waitForFunction(() => {
        const last = document.querySelector('.thumbnail-gallery-grid img:last-child').getBoundingClientRect();
        return last.bottom <= document.querySelector('.page-canvas').getBoundingClientRect().bottom;
    });
    const last = await grid.locator('img').last().boundingBox();
    const canvas = await page.locator('.page-canvas').boundingBox();
    assert.ok(last.y + last.height <= canvas.y + canvas.height, 'Last row must not be clipped');
    passed++;

    saved.sections.push({ type: 'gallery', gallery_mode: 'single', gallery_id: 1, position_x: 0, position_y: 0, element_width: 20 });
    await page.setViewportSize({ width: 1200, height: 1000 });
    await page.reload();
    assert.equal(await page.locator('.gallery-item').count(), 1);
    await page.locator('.gallery-item').click();
    assert.equal(await page.locator('#lightbox').isVisible(), true);
    await page.keyboard.press('Escape');
    await grid.locator('img').nth(3).click();
    assert.equal(await page.locator('#lightbox').isVisible(), false);

    // Reproduce direct and delegated image openers without changing application data.
    await page.evaluate(() => {
        window.thumbnailOpenersRun = [];
        const grid = document.querySelector('[data-builder-thumbnail-gallery]');
        for (const [name, target, capture] of [
            ['window', window, false], ['document capture', document, true], ['document bubble', document, false],
            ['container', grid, false], ...[...grid.querySelectorAll('img')].map(img => ['image', img, false]),
        ]) {
            for (const type of ['click', 'dblclick', 'auxclick']) {
                target.addEventListener(type, event => {
                    if (!event.target.closest('[data-builder-thumbnail-gallery]')) return;
                    window.thumbnailOpenersRun.push(`${name}: ${type}`);
                    document.querySelector('.gallery-item').click();
                }, capture);
            }
        }
    });
    const mixedUrl = page.url();
    await grid.locator('img').nth(3).click();
    await grid.locator('img').nth(3).dblclick();
    await grid.locator('img').nth(3).click({ button: 'middle' });
    assert.deepEqual(await page.evaluate(() => window.thumbnailOpenersRun), []);
    assert.equal(await page.locator('#lightbox').isVisible(), false);
    assert.equal(page.url(), mixedUrl);
    await page.locator('.gallery-item').click();
    assert.equal(await page.locator('#lightbox').isVisible(), true);
    await page.keyboard.press('Escape');
    assert.deepEqual(errors, []);
    passed++;
    console.log(`Thumbnail gallery: ${passed} browser scenarios passed.`);
} finally { await browser.close(); }
