// PHOTO_PICKER_PLAYWRIGHT_MODULE may point to an external Playwright installation.
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const { chromium } = await import(process.env.PHOTO_PICKER_PLAYWRIGHT_MODULE || 'playwright');
const html = execFileSync('php', [fileURLToPath(new URL('./photo-picker-fixture.php', import.meta.url))], { encoding: 'utf8' });
assert.ok(html.includes('id="photo-library-dialog"'), 'Fixture must render the actual picker: '+html.slice(0, 1000));
const browser = await chromium.launch({ headless: true });
let passed = 0;
try {
    const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.route('http://picker.test/**', async route => {
        const path = new URL(route.request().url()).pathname;
        if (path.startsWith('/js/') || path.startsWith('/css/')) {
            await route.fulfill({ contentType: path.endsWith('.css') ? 'text/css' : 'application/javascript', body: readFileSync(new URL('../../public'+path, import.meta.url), 'utf8') });
        } else if (path.startsWith('/storage/')) {
            await route.fulfill({ contentType: 'image/png', body: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aD1sAAAAASUVORK5CYII=', 'base64') });
        } else await route.fulfill({ contentType: 'text/html', body: html });
    });
    await page.goto('http://picker.test/pages/1/edit');
    const picker = name => page.locator('[data-photo-picker]').filter({ has: page.locator(`[name="${name}"]`) });
    const social = picker('social_photo_id');
    const featured = picker('featured_photo_id');
    const modal = page.locator('#photo-library-dialog');
    const card = id => modal.locator(`[data-library-photo="${id}"]`);
    const search = modal.locator('[data-library-search]');
    const confirm = modal.locator('[data-library-confirm]');
    const value = field => field.locator('[data-photo-value]').inputValue();

    assert.equal(await modal.count(), 1);
    await social.locator('[data-photo-open]').click();
    await card(1).click();
    assert.equal(await card(1).getAttribute('aria-pressed'), 'true');
    assert.equal(await value(social), '');
    await confirm.click();
    assert.equal(await value(social), '1');
    assert.match(await social.locator('img').getAttribute('src'), /one-thumb\.jpg$/);
    assert.equal(await social.locator('[data-photo-open]').evaluate(el => el === document.activeElement), true);
    passed++;

    await social.locator('[data-photo-open]').click();
    await card(2).click();
    await modal.getByRole('button', { name: 'Anuluj', exact: true }).click();
    assert.equal(await value(social), '1');
    await social.locator('[data-photo-open]').click();
    await card(2).click();
    await page.keyboard.press('Escape');
    assert.equal(await modal.isVisible(), false);
    assert.equal(await value(social), '1');
    passed++;

    await social.locator('[data-photo-open]').click();
    for (const query of ['ŻYWNOŚĆ', 'jabłko', 'sad']) {
        await search.fill(query);
        assert.equal(await card(1).isVisible(), true);
        assert.equal(await card(2).isVisible(), false);
    }
    await search.press('Enter');
    assert.equal(await modal.isVisible(), true);
    await search.fill('espresso');
    await card(2).click(); await confirm.click();
    assert.equal(await value(social), '2');
    passed++;

    await featured.locator('[data-photo-open]').click();
    assert.equal(await search.inputValue(), '');
    await card(1).click(); await page.keyboard.press('Escape');
    assert.match(await featured.locator('img').getAttribute('src'), /pages\/legacy\.jpg$/);
    assert.equal(await featured.locator('[data-photo-clear]').inputValue(), '0');
    await featured.locator('[data-photo-open]').click();
    await card(1).click(); await confirm.click();
    assert.equal(await value(featured), '1');
    assert.equal(await value(social), '2');
    await featured.locator('[data-photo-remove]').click();
    assert.equal(await value(featured), '');
    assert.equal(await featured.locator('[data-photo-clear]').inputValue(), '1');
    assert.equal(await value(social), '2');
    passed++;

    await social.locator('[data-photo-open]').click();
    await modal.getByRole('button', { name: 'Brak / użyj domyślnego', exact: true }).click();
    await confirm.click();
    assert.equal(await value(social), '');
    assert.equal(await social.locator('[data-photo-preview]').isVisible(), false);
    passed++;

    await page.setViewportSize({ width: 390, height: 844 });
    await social.locator('[data-photo-open]').click();
    const bounds = await modal.boundingBox();
    assert.ok(bounds.x >= 0 && bounds.x + bounds.width <= 390);
    assert.ok(bounds.y >= 0 && bounds.y + bounds.height <= 844);
    await card(25).click();
    await confirm.click();
    assert.equal(await value(social), '25');
    assert.deepEqual(errors, []);
    passed++;
    console.log(`Photo picker browser: ${passed} scenarios passed (desktop/mobile).`);
} finally {
    await browser.close();
}
