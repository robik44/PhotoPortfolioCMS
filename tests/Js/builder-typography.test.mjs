import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const moduleSource = readFileSync(new URL('../../public/js/site-typography.js', import.meta.url), 'utf8');
const thumbnailSource = readFileSync(new URL('../../public/js/builder-thumbnail-gallery.js', import.meta.url), 'utf8');
const builder = readFileSync(new URL('../../resources/views/admin/pages/builder.blade.php', import.meta.url), 'utf8');
const custom = `hf_${'a'.repeat(32)}`;
const catalog = {
    families: { Arial: 'Arial, sans-serif', Georgia: 'Georgia, serif', [custom]: `${custom}, Arial, sans-serif` },
    choices: [{ value: 'Arial', label: 'Arial' }, { value: 'Georgia', label: 'Georgia' }, { value: custom, label: 'Własna (własna)' }],
    defaults: { site_body_font_family: 'Georgia', site_heading_font_family: custom },
    textTypes: ['heading', 'text', 'button', 'section', 'gallery'],
};

function node(tag = 'div') {
    const listeners = new Map();
    return {
        tag, children: [], style: { setProperty(key, value) { this[key] = value; } }, dataset: {}, classList: { add() {} }, textContent: '', value: '',
        replaceChildren() { this.children = []; },
        set innerHTML(value) { this.children = []; },
        appendChild(child) { this.children.push(child); return child; },
        setAttribute(key, value) { this[key] = value; },
        addEventListener(event, callback) { listeners.set(event, [...(listeners.get(event) ?? []), callback]); },
        emit(event) { for (const callback of listeners.get(event) ?? []) callback({}); },
    };
}
function find(root, predicate) {
    if (predicate(root)) return root;
    for (const child of root.children) {
        const result = find(child, predicate);
        if (result) return result;
    }
}
function slice(start, end, offset = 0) {
    const a = builder.indexOf(start, offset);
    const b = builder.indexOf(end, a + start.length);
    assert.ok(a >= 0 && b > a);
    return builder.slice(a, b);
}
function setup(mode, item, galleryList = [], photoList = []) {
    const document = { createElement: node, addEventListener() {} };
    const context = vm.createContext({ window: {}, document, catalog, item, properties: node(), page: node(), galleries: galleryList, photos: photoList, selectedElement: null, data: { sections: [item] }, selected: item.id,
        builderData: { version: 1, settings: {}, sections: [item] }, label: type => type, elementLabel: type => type });
    vm.runInContext(moduleSource + '\nwindow.builderTypography = window.SiteTypography.create(catalog);', context);
    vm.runInContext(thumbnailSource, context);
    vm.runInContext(readFileSync(new URL('../../public/js/builder-button.js', import.meta.url), 'utf8'), context);
    if (mode === 'full') {
        const offset = builder.indexOf('/* FULL VISUAL PAGE EDITOR */');
        const functions = slice('    function createElementContent(item)', '    function render()', offset)
            + slice('    function field(', '    function showProperties(item)', offset)
            + slice('    function showProperties(item)', '    function addElement(type)', offset);
        vm.runInContext(functions + '\nfunction render() { preview = createElementContent(item); }\nshowProperties(item); render();', context);
    } else {
        const functions = slice('            function ensureElementStyle(item)', '            function createField(')
            + slice('            function createField(', '            function showImageProperties(')
            + slice('            function showProperties(item)', '            document.querySelectorAll(".builder-add")');
        vm.runInContext(functions + '\nshowProperties(item); render();', context);
    }
    return { context, preview: () => mode === 'full' ? context.preview : context.page.children[0].children[0] };
}
function textItem(type) {
    return { id: 'element-1', type, content: 'Tekst', position_x: 12, position_y: 20,
        style: { color: '#123456', font_size: 24, font_weight: 400, text_align: 'left', line_height: 1.6, letter_spacing: 0 }, other: { keep: true } };
}

for (const mode of ['full', 'legacy']) {
    for (const type of catalog.textTypes) {
        test(`${mode}: ${type} changes system and custom font immediately in the actual renderer`, () => {
            const item = textItem(type);
            const { context, preview } = setup(mode, item);
            const before = JSON.stringify(item);
            const select = find(context.properties, element => element.tag === 'select' && element['aria-label'] === 'Rodzaj czcionki');
            assert.ok(select);
            assert.equal(preview().style.fontFamily, 'Arial, sans-serif');
            assert.equal(item.style.font_family, undefined);
            for (const id of ['Georgia', custom]) {
                select.value = id;
                select.emit('input');
                assert.equal(item.style.font_family, id);
                assert.equal(preview().style.fontFamily, catalog.families[id]);
            }
            delete item.style.font_family;
            assert.equal(JSON.stringify(item), before);
        });
    }
}

test('thumbnail canvas sizing follows moved blocks and resets after their removal', () => {
    const context = vm.createContext({ window: {}, document: {} });
    vm.runInContext(thumbnailSource, context);
    vm.runInContext(readFileSync(new URL('../../public/js/builder-button.js', import.meta.url), 'utf8'), context);
    const block = { offsetHeight: 1200, style: { top: '5%' }, dataset: {} };
    let blocks = [block];
    const canvas = { offsetHeight: 900, style: {}, dataset: {}, querySelectorAll: () => blocks };
    context.window.ThumbnailGallery.fitCanvas(canvas);
    assert.equal(canvas.style.minHeight, '1285px');
    block.style.top = '50%';
    context.window.ThumbnailGallery.fitCanvas(canvas);
    assert.equal(canvas.style.minHeight, '2440px');
    block.style.top = '120%';
    context.window.ThumbnailGallery.fitCanvas(canvas);
    assert.equal(block.style.top, '1080px');
    context.window.ThumbnailGallery.fitCanvas(canvas);
    assert.equal(canvas.style.minHeight, '2300px');
    blocks = [];
    context.window.ThumbnailGallery.fitCanvas(canvas);
    assert.equal(canvas.style.minHeight, '900px');
});

test('full builder existing text controls update content, size, weight, color and spacing without save', () => {
    const item = textItem('text');
    const { context, preview } = setup('full', item);
    for (const [label, value, styleKey, expected] of [
        ['Treść', 'Nowa treść', null, 'Nowa treść'],
        ['Rozmiar czcionki', '36', 'fontSize', '36px'],
        ['Grubość czcionki', '700', 'fontWeight', 700],
        ['Kolor tekstu', '#abcdef', 'color', '#abcdef'],
        ['Odstęp między literami (px)', '3', 'letterSpacing', '3px'],
    ]) {
        const wrapper = context.properties.children.find(element => element.children[0]?.textContent === label);
        const input = wrapper.children[1];
        input.value = value;
        input.emit('input');
        assert.equal(styleKey ? preview().style[styleKey] : preview().textContent, expected);
    }
});

test('graphic blocks do not get font fields or new font properties', () => {
    const context = vm.createContext({ window: {}, document: { createElement: node }, catalog });
    vm.runInContext(moduleSource + '\napi = window.SiteTypography.create(catalog);', context);
    for (const type of ['image', 'separator', 'thumbnail_gallery']) {
        const item = { type };
        const properties = node();
        const preview = node();
        context.api.initialize(item);
        context.api.field(properties, item, () => assert.fail('Unexpected render'), 'field');
        context.api.apply(preview, item);
        assert.equal(item.style, undefined);
        assert.equal(properties.children.length, 0);
        assert.equal(preview.style.fontFamily, undefined);
    }
});

for (const mode of ['full', 'legacy']) {
    test(`${mode}: thumbnail block renders ordered live Photo references and its own settings without text fields`, () => {
        const item = { ...textItem('thumbnail_gallery'), photo_ids: [2, 999, 1], image_fit: 'cover', thumbnail_height: 180 };
        const photos = [{ id: 1, url: '/one.png', alt: 'Logo one' }, { id: 2, thumbnail_url: '/two-small.png', title: 'Firma two' }];
        const { context, preview } = setup(mode, item, [], photos);
        const grid = find(preview(), element => element.className === 'thumbnail-gallery-grid');
        assert.deepEqual(grid.children.map(image => image.src), ['/two-small.png', '/one.png']);
        assert.deepEqual(grid.children.map(image => image.alt), ['Firma two', 'Logo one']);
        assert.equal(grid.style['--tg-desktop'], '5');
        assert.equal(grid.style['--tg-fit'], undefined);
        assert.equal(grid.style['--tg-height'], undefined);
        assert.equal(find(context.properties, element => element['aria-label'] === 'Rodzaj czcionki'), undefined);
        assert.equal(find(context.properties, element => element['data-thumbnail-setting'] === 'image_fit'), undefined);
        assert.equal(find(context.properties, element => element['data-thumbnail-setting'] === 'thumbnail_height'), undefined);
        for (const [key, value, variable, expected] of [
            ['columns_desktop', '6', '--tg-desktop', '6'], ['columns_tablet', '4', '--tg-tablet', '4'],
            ['columns_mobile', '1', '--tg-mobile', '1'], ['gap', '12', '--tg-gap', '12px'],
        ]) {
            const input = find(context.properties, element => element['data-thumbnail-setting'] === key);
            input.value = value; input.emit('change');
            assert.equal(item[key], Number(value));
            assert.equal(find(preview(), element => element.className === 'thumbnail-gallery-grid').style[variable], expected);
        }
        assert.equal(item.image_fit, 'cover');
        assert.equal(item.thumbnail_height, 180);
        assert.deepEqual(item.photo_ids, [2, 999, 1]);
    });
}

test('new blocks use global defaults, old blocks remain untouched and unknown CSS is never used', () => {
    const context = vm.createContext({ window: {}, document: { createElement: node }, catalog });
    vm.runInContext(moduleSource + '\napi = window.SiteTypography.create(catalog);', context);
    const heading = { type: 'heading' };
    context.api.initialize(heading);
    assert.equal(heading.style.font_family, custom);
    const text = { type: 'text' };
    context.api.initialize(text);
    assert.equal(text.style.font_family, 'Georgia');
    assert.equal(context.api.css('Arial; background:url(bad)'), 'Arial, sans-serif');
    assert.equal(context.api.css('__proto__'), 'Arial, sans-serif');
    assert.equal(context.api.css(['Georgia']), 'Arial, sans-serif');
});


test('gallery properties switch between all cards and one live gallery photo preview', () => {
    const item = textItem('gallery');
    const galleries = [
        { id: 1, title: 'Food', cover_url: 'cover.jpg', photos: [{ title: 'Pierwsze', cover_url: 'first.jpg' }, { title: 'Drugie', cover_url: 'second.jpg' }] },
        { id: 2, title: 'Other', cover_url: 'other.jpg', photos: [] },
    ];
    const { context, preview } = setup('full', item, galleries);
    assert.equal(preview().children[0].children.length, 2);
    const mode = find(context.properties, element => element.textContent === 'Tryb galerii').children[0];
    mode.value = 'single'; mode.emit('change');
    assert.equal(item.gallery_mode, 'single');
    const choice = find(context.properties, element => element.textContent === 'Wybierz galerię').children[0];
    choice.value = '1'; choice.emit('change');
    assert.equal(item.gallery_id, 1);
    assert.deepEqual(Array.from(preview().children[0].children, card => card.children[0].src), ['first.jpg', 'second.jpg']);
    assert.equal(item.photos, undefined);
});

test('heading properties change H1/H2/H3 tags while preserving visual styles and legacy div', () => {
    const item = textItem('heading');
    const { context, preview } = setup('full', item);
    assert.equal(preview().tag, 'div');
    const before = JSON.stringify(item.style);
    for (const tag of ['h1', 'h2', 'h3']) {
        const select = find(context.properties, element => element.textContent === 'Poziom nagłówka').children[0];
        select.value = tag; select.emit('change');
        assert.equal(item.heading_level, tag);
        assert.equal(preview().tag, tag);
        assert.equal(preview().style.margin, '0');
        assert.equal(JSON.stringify(item.style), before);
    }
});

for (const type of ['image', 'gallery']) {
    test(`${type}: caption controls preserve defaults and reopen saved font settings`, () => {
        const item = { ...textItem(type), photo_url: '/photo.jpg' };
        const before = JSON.stringify(item);
        const { context, preview } = setup('full', item);
        assert.equal(JSON.stringify(item), before);
        const prefix = type === 'gallery' ? 'Opis zdjęcia w podglądzie' : 'Podpis zdjęcia';
        const font = find(context.properties, el => el['aria-label'] === `${prefix} — rodzaj czcionki`);
        const size = find(context.properties, el => el['aria-label'] === `${prefix} — rozmiar czcionki (px)`);
        assert.equal(font.value, '');
        assert.equal(size.value, '');
        const imageBefore = type === 'image' ? JSON.stringify(preview().children[0]) : null;
        if (type === 'image') {
            assert.equal(preview().children.length, 1);
            const caption = find(context.properties, el => el['aria-label'] === 'Podpis / opis zdjęcia');
            caption.value = 'Podpis\nDrugi akapit'; caption.emit('input');
        }
        font.value = custom; font.emit('change');
        size.value = '27'; size.emit('input');
        assert.equal(item.caption_font_family, custom);
        assert.equal(item.caption_font_size, 27);
        const reopened = setup('full', JSON.parse(JSON.stringify(item)));
        assert.equal(find(reopened.context.properties, el => el['aria-label'] === `${prefix} — rodzaj czcionki`).value, custom);
        assert.equal(find(reopened.context.properties, el => el['aria-label'] === `${prefix} — rozmiar czcionki (px)`).value, 27);
        if (type === 'image') {
            assert.equal(JSON.stringify(preview().children[0]), imageBefore);
            assert.equal(preview().children[1].textContent, 'Podpis\nDrugi akapit');
            assert.equal(preview().children[1].style.fontFamily, catalog.families[custom]);
            assert.equal(preview().children[1].style.fontSize, '27px');
            const caption = find(context.properties, el => el['aria-label'] === 'Podpis / opis zdjęcia');
            caption.value = '  '; caption.emit('input');
            assert.equal(preview().children.length, 1);
        }
        font.value = ''; font.emit('change');
        size.value = ''; size.emit('input');
        assert.equal(Object.hasOwn(item, 'caption_font_family'), false);
        assert.equal(Object.hasOwn(item, 'caption_font_size'), false);
    });
}

test('lightbox description typography changes per photo and resets for legacy galleries', () => {
    const source = readFileSync(new URL('../../resources/views/components/gallery-lightbox.blade.php', import.meta.url), 'utf8');
    const show = source.slice(source.indexOf('    function showPhoto(index)'), source.indexOf('    function closeLightbox()'));
    const context = vm.createContext({
        items: [{dataset: {photoUrl: '/one.jpg', photoDescription: 'Opis', descriptionFontFamily: 'Georgia, serif', descriptionFontSize: '28px', titleFontFamily: 'Verdana, sans-serif', titleFontSize: '21px'}}, {dataset: {photoUrl: '/two.jpg', photoDescription: 'Drugi opis'}}],
        currentIndex: 0, image: {}, title: {style: {}}, description: {style: {}},
        lightbox: {classList: {contains: () => true, add() {}}, setAttribute() {}},
        document: {body: {style: {}}},
    });
    vm.runInContext(show + '\nshowPhoto(0);', context);
    assert.equal(context.description.style.fontFamily, 'Georgia, serif');
    assert.equal(context.description.style.fontSize, '28px');
    assert.equal(context.description.textContent, 'Opis');
    assert.equal(context.title.style.fontFamily, 'Verdana, sans-serif');
    assert.equal(context.title.style.fontSize, '21px');
    vm.runInContext('showPhoto(1);', context);
    assert.equal(context.description.style.fontFamily, '');
    assert.equal(context.description.style.fontSize, '');
    assert.equal(context.description.textContent, 'Drugi opis');
    assert.equal(context.title.style.fontFamily, '');
    assert.equal(context.title.style.fontSize, '');
    assert.equal(context.image.src, '/two.jpg');
});


test('button fields update appearance without changing legacy defaults or geometry', () => {
    const item = textItem('button');
    const before = JSON.stringify(item);
    const { context, preview } = setup('full', item);
    assert.equal(JSON.stringify(item), before);
    for (const [label, value, property, expected] of [
        ['Kolor tła przycisku', '#abcdef', 'backgroundColor', '#abcdef'],
        ['Kolor obramowania', '#123456', 'borderColor', '#123456'],
        ['Grubość obramowania (px)', '3', 'borderWidth', '3px'],
        ['Zaokrąglenie narożników (px)', '9', 'borderRadius', '9px'],
        ['Odstęp wewnętrzny pionowy (px)', '15', 'paddingTop', '15px'],
        ['Odstęp wewnętrzny poziomy (px)', '31', 'paddingLeft', '31px'],
    ]) {
        const input = find(context.properties, el => el['aria-label'] === label);
        input.value = value; input.emit('input');
        assert.equal(preview().children[0].style[property], expected);
    }
    const link = find(context.properties, el => el['aria-label'] === 'Akcja / link');
    link.value = 'mailto:test@example.com'; link.emit('input');
    assert.equal(item.button_link, link.value);
    const wrapper = context.properties.children.find(el => el.textContent === 'Otwórz w nowej karcie');
    wrapper.children[0].value = '1'; wrapper.children[0].emit('change');
    assert.equal(item.button_new_tab, true);
    const reopened = setup('full', JSON.parse(JSON.stringify(item)));
    assert.equal(find(reopened.context.properties, el => el['aria-label'] === 'Akcja / link').value, link.value);
    assert.equal(item.position_x, 12);
    assert.equal(item.position_y, 20);
});


test('button existing typography and target picker update independently', () => {
    const item = textItem('button');
    const { context, preview } = setup('full', item);
    for (const [label, value, property, expected] of [
        ['Tekst przycisku', 'Nowy tekst', null, 'Nowy tekst'],
        ['Rozmiar czcionki', '32', 'fontSize', '32px'],
        ['Kolor tekstu', '#abcdef', 'color', '#abcdef'],
        ['Grubość czcionki', '700', 'fontWeight', 700],
    ]) {
        const wrapper = context.properties.children.find(el => el.children[0]?.textContent === label);
        wrapper.children[1].value = value; wrapper.children[1].emit('input');
        assert.equal(property ? preview().style[property] : preview().children[0].textContent, expected);
    }
    context.window.builderButtonTargets = [{url: '/strona/test', label: 'Strona: Test'}, {url: '/portfolio/test', label: 'Galeria: Test'}];
    vm.runInContext('showProperties(item);', context);
    const wrapper = context.properties.children.find(el => el.textContent === 'Wybierz stronę lub galerię');
    for (const url of ['/strona/test', '/portfolio/test']) {
        wrapper.children[0].value = url; wrapper.children[0].emit('change');
        assert.equal(item.button_link, url);
    }
});
