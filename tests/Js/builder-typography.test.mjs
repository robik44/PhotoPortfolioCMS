import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const moduleSource = readFileSync(new URL('../../public/js/site-typography.js', import.meta.url), 'utf8');
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
        tag, children: [], style: {}, dataset: {}, classList: { add() {} }, textContent: '', value: '',
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
function setup(mode, item, galleryList = []) {
    const document = { createElement: node, addEventListener() {} };
    const context = vm.createContext({ window: {}, document, catalog, item, properties: node(), page: node(), galleries: galleryList, photos: [], selectedElement: null, data: { sections: [item] }, selected: item.id,
        builderData: { version: 1, settings: {}, sections: [item] }, label: type => type, elementLabel: type => type });
    vm.runInContext(moduleSource + '\nwindow.builderTypography = window.SiteTypography.create(catalog);', context);
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
    for (const type of ['image', 'separator']) {
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
