import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const script = readFileSync(new URL('../../public/js/header-settings.js', import.meta.url), 'utf8');

function setup(FontClass) {
    function element(initial = '') {
        const listeners = new Map();
        let value = initial;
        return {
            files: [], style: {}, dataset: {}, textContent: '', hidden: false, validityMessage: '',
            get value() { return value; },
            set value(next) { value = next; if (next === '') this.files = []; },
            addEventListener(event, callback) {
                listeners.set(event, [...(listeners.get(event) ?? []), callback]);
            },
            async emit(event) { await Promise.all((listeners.get(event) ?? []).map(callback => callback())); },
            setCustomValidity(message) { this.validityMessage = message; },
        };
    }
    const fields = { logo: element('Nazwa'), logo_subtitle: element('Podtytuł'), header_layout: element('left'), header_padding_top: element('48'), header_padding_bottom: element('48'), font_file: element(), font_target: element('logo') };
    for (const part of ['logo', 'subtitle']) {
        for (const [key, value] of Object.entries({ font_family: 'Arial', font_size: part === 'logo' ? '28' : '10', font_weight: '400', color: '#222222', letter_spacing: '0.08' })) {
            fields[`header_${part}_${key}`] = element(value);
        }
    }
    const nodes = Object.fromEntries(['header-settings-form', 'header-live-preview', 'header-font-status', 'header-clear-font', 'header-preview-logo', 'header-preview-subtitle'].map(id => [id, element()]));
    const form = nodes['header-settings-form'];
    form.elements = { namedItem: name => fields[name] };
    form.dataset.fontFamilies = JSON.stringify({ Arial: 'Arial, sans-serif', Georgia: 'Georgia, serif', hf_test: 'hf_test, Arial, sans-serif' });
    const loaded = new Set();
    const FontFace = FontClass ?? class {
        constructor(family) { this.family = family; }
        async load() { return this; }
    };
    vm.runInNewContext(script, { document: { getElementById: id => nodes[id], fonts: loaded }, window: { FontFace }, FontFace });
    return { fields, nodes, form, loaded };
}

test('text and all style controls update both preview lines without submission', async () => {
    const { fields, nodes, form } = setup();
    for (const part of ['logo', 'subtitle']) {
        fields[part === 'logo' ? 'logo' : 'logo_subtitle'].value = '<img onerror=alert(1)>';
        for (const [key, value] of Object.entries({ font_family: 'Georgia', font_size: '32', font_weight: '700', color: '#abcdef', letter_spacing: '0.25' })) {
            fields[`header_${part}_${key}`].value = value;
        }
        await form.emit('input');
        const node = nodes[`header-preview-${part}`];
        assert.equal(node.textContent, '<img onerror=alert(1)>');
        assert.equal(node.style.fontFamily, 'Georgia, serif');
        assert.equal(node.style.fontSize, '32px');
        assert.equal(node.style.fontWeight, '700');
        assert.equal(node.style.color, '#abcdef');
        assert.equal(node.style.letterSpacing, '0.25em');
    }
    fields.header_layout.value = 'center';
    await form.emit('change');
    assert.equal(nodes['header-live-preview'].dataset.layout, 'center');
});

test('stored fonts can be selected independently and arbitrary CSS falls back', async () => {
    const { fields, nodes, form } = setup();
    fields.header_logo_font_family.value = 'hf_test';
    fields.header_subtitle_font_family.value = 'Arial; display:none';
    await form.emit('input');
    assert.equal(nodes['header-preview-logo'].style.fontFamily, 'hf_test, Arial, sans-serif');
    assert.equal(nodes['header-preview-subtitle'].style.fontFamily, 'Arial, sans-serif');
});

test('independent spacing and all layouts preserve typography and horizontal padding', async () => {
    const { fields, nodes, form } = setup();
    const preview = nodes['header-live-preview'];
    assert.equal(preview.style.paddingTop, '48px');
    assert.equal(preview.style.paddingBottom, '48px');
    const logoStyle = { ...nodes['header-preview-logo'].style };
    const subtitleStyle = { ...nodes['header-preview-subtitle'].style };
    for (const layout of ['left', 'center', 'right']) {
        fields.header_layout.value = layout;
        for (const [top, bottom] of [['0', '48'], ['24', '48'], ['24', '72'], ['160', '0']]) {
            fields.header_padding_top.value = top;
            fields.header_padding_bottom.value = bottom;
            await form.emit('input');
            assert.equal(preview.style.paddingTop, `${top}px`);
            assert.equal(preview.style.paddingBottom, `${bottom}px`);
            assert.equal(preview.style.paddingLeft, undefined);
            assert.equal(preview.style.paddingRight, undefined);
            assert.equal(preview.dataset.layout, layout);
            assert.deepEqual(nodes['header-preview-logo'].style, logoStyle);
            assert.deepEqual(nodes['header-preview-subtitle'].style, subtitleStyle);
        }
    }
});

test('a local file loads before save, follows its target, and is removed when cancelled', async () => {
    const { fields, nodes, loaded, form } = setup();
    fields.font_file.files = [{ name: 'new.woff2', size: 64, arrayBuffer: async () => new ArrayBuffer(64) }];
    await fields.font_file.emit('change');
    assert.equal(loaded.size, 1);
    assert.match(nodes['header-preview-logo'].style.fontFamily, /^header_preview_/);
    assert.equal(nodes['header-preview-subtitle'].style.fontFamily, 'Arial, sans-serif');
    fields.font_target.value = 'both';
    await form.emit('change');
    assert.match(nodes['header-preview-subtitle'].style.fontFamily, /^header_preview_/);
    await nodes['header-clear-font'].emit('click');
    assert.equal(loaded.size, 0);
    assert.equal(nodes['header-preview-logo'].style.fontFamily, 'Arial, sans-serif');
});

test('changing the font list overrides an unsaved upload instead of masking the selection', async () => {
    const { fields, nodes, loaded } = setup();
    fields.font_file.files = [{ name: 'new.ttf', size: 64, arrayBuffer: async () => new ArrayBuffer(64) }];
    await fields.font_file.emit('change');
    fields.header_logo_font_family.value = 'Georgia';
    await fields.header_logo_font_family.emit('change');
    assert.equal(loaded.size, 0);
    assert.equal(fields.font_file.files.length, 0);
    assert.equal(nodes['header-preview-logo'].style.fontFamily, 'Georgia, serif');
});

test('invalid, oversized and unreadable fonts report an error', async () => {
    const { fields, nodes } = setup(class { async load() { throw new Error('invalid font'); } });
    for (const file of [
        { name: 'bad.php', size: 10 },
        { name: 'large.ttf', size: 6 * 1024 * 1024 },
        { name: 'broken.ttf', size: 64, arrayBuffer: async () => new ArrayBuffer(64) },
    ]) {
        fields.font_file.files = [file];
        await fields.font_file.emit('change');
        assert.notEqual(fields.font_file.validityMessage, '');
        assert.notEqual(nodes['header-font-status'].textContent, '');
    }
});

test('a late font load cannot restore a cancelled file', async () => {
    let finish;
    const { fields, nodes, loaded } = setup();
    fields.font_file.files = [{ name: 'slow.ttf', size: 64, arrayBuffer: () => new Promise(resolve => { finish = resolve; }) }];
    const pending = fields.font_file.emit('change');
    await nodes['header-clear-font'].emit('click');
    finish(new ArrayBuffer(64));
    await pending;
    assert.equal(loaded.size, 0);
    assert.equal(nodes['header-preview-logo'].style.fontFamily, 'Arial, sans-serif');
});
