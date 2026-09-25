import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const script = readFileSync(new URL('../../public/js/photo-picker.js', import.meta.url), 'utf8');

// Run the production handlers against a small DOM adapter, without dependencies.
function setup({ legacy = false, empty = false } = {}) {
    const document = { activeElement: null };
    function element(properties = {}) {
        const listeners = new Map();
        return Object.assign({
            dataset: {}, attributes: {}, children: {}, hidden: false, value: '', textContent: '', disabled: false,
            addEventListener(name, handler) { listeners.set(name, [...(listeners.get(name) || []), handler]); },
            dispatchEvent(event) { for (const handler of listeners.get(event.type) || []) handler(event); },
            dispatch(type, properties = {}) {
                const event = { type, currentTarget: this, defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, ...properties };
                this.dispatchEvent(event);
                return event;
            },
            setAttribute(name, value) { this.attributes[name] = value; },
            removeAttribute(name) { delete this[name]; },
            querySelector(selector) { return this.children[selector] || null; },
            focus() { document.activeElement = this; },
        }, properties);
    }
    const cards = empty ? [] : ['1', '2'].map((id, index) => element({
        dataset: { libraryPhoto: id, search: index ? 'Kawa Espresso Palarnia' : 'Żywność Jabłko Sad' },
        children: {
            img: element({ src: `/storage/photos/${id}-thumb.jpg`, alt: `ALT ${id}` }),
            '[data-library-caption]': element({ textContent: `Tytuł ${id}` }),
        },
    }));
    function picker(withLegacy) {
        const children = Object.fromEntries(['value', 'clear', 'preview', 'image', 'caption', 'remove', 'empty', 'open']
            .map(name => [`[data-photo-${name}]`, element()]));
        children['[data-photo-preview]'].hidden = !withLegacy;
        if (withLegacy) children['[data-photo-image]'].src = '/storage/pages/legacy.jpg';
        children['[data-photo-clear]'].value = '0';
        return element({ dataset: { fallback: withLegacy ? '0' : '1' }, children });
    }
    const pickers = [picker(legacy), picker(false)];
    const children = Object.fromEntries(['search', 'none', 'confirm', 'status', 'empty'].map(name => [`[data-library-${name}]`, element()]));
    const cancel = element();
    const dialog = element({ children, open: false,
        querySelectorAll(selector) { return selector === '[data-library-photo]' ? cards : [cancel]; },
        showModal() { this.open = true; },
        close() { this.open = false; this.dispatch('close'); },
    });
    document.getElementById = () => dialog;
    document.querySelectorAll = () => pickers;
    const window = {};
    runInNewContext(script, { document, window, Event: class { constructor(type) { this.type = type; } } });
    const field = (name, index = 0) => pickers[index].querySelector(`[data-photo-${name}]`);
    const control = name => dialog.querySelector(`[data-library-${name}]`);
    const open = (index = 0) => field('open', index).dispatch('click');
    return { document, dialog, cards, cancel, field, control, open, library: window.PhotoLibrary };
}

test('select and change commit Photo IDs and thumbnails only after confirmation', () => {
    const ui = setup();
    ui.open();
    assert.equal(ui.dialog.open, true);
    assert.equal(ui.document.activeElement, ui.control('search'));
    ui.cards[0].dispatch('click');
    assert.equal(ui.cards[0].attributes['aria-pressed'], 'true');
    assert.equal(ui.field('value').value, '');
    ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '1');
    assert.equal(ui.field('image').src, '/storage/photos/1-thumb.jpg');
    assert.equal(ui.field('preview').hidden, false);
    assert.equal(ui.document.activeElement, ui.field('open'));
    ui.open();
    assert.equal(ui.cards[0].attributes['aria-pressed'], 'true');
    ui.cards[1].dispatch('click');
    ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '2');
});

test('cancel and native Escape close discard draft selection including draft clear', () => {
    const ui = setup();
    ui.field('value').value = '1';
    ui.open(); ui.cards[1].dispatch('click'); ui.cancel.dispatch('click');
    assert.equal(ui.field('value').value, '1');
    ui.open(); ui.control('none').dispatch('click'); ui.dialog.close();
    assert.equal(ui.field('value').value, '1');
    ui.open();
    assert.equal(ui.cards[0].attributes['aria-pressed'], 'true');
});

test('clear detaches assignment, and fallback option needs confirmation', () => {
    const ui = setup();
    ui.open(); ui.cards[0].dispatch('click'); ui.control('confirm').dispatch('click');
    ui.open();
    assert.equal(ui.control('none').textContent, 'Brak / użyj domyślnego');
    ui.control('none').dispatch('click');
    assert.equal(ui.field('value').value, '1');
    ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '');
    assert.equal(ui.field('clear').value, '1');
    assert.equal(ui.field('preview').hidden, true);
    ui.open(); ui.cards[1].dispatch('click'); ui.control('confirm').dispatch('click');
    assert.equal(ui.field('clear').value, '0');
    ui.field('remove').dispatch('click');
    assert.equal(ui.field('value').value, '');
    assert.equal(ui.field('image').src, undefined);
});

test('shared modal keeps two form fields independent', () => {
    const ui = setup();
    ui.open(); ui.cards[0].dispatch('click'); ui.control('confirm').dispatch('click');
    ui.open(1); ui.cards[1].dispatch('click'); ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '1');
    assert.equal(ui.field('value', 1).value, '2');
    ui.field('remove', 1).dispatch('click');
    assert.equal(ui.field('value').value, '1');
});

test('legacy preview survives opening and cancellation; only deliberate selection or removal changes it', () => {
    const ui = setup({ legacy: true });
    ui.open();
    assert.equal(ui.control('confirm').disabled, true);
    ui.cards[0].dispatch('click'); ui.cancel.dispatch('click');
    assert.equal(ui.field('value').value, '');
    assert.equal(ui.field('clear').value, '0');
    assert.equal(ui.field('image').src, '/storage/pages/legacy.jpg');
    ui.field('remove').dispatch('click');
    assert.equal(ui.field('clear').value, '1');
});

test('search matches title ALT and description case-insensitively without clearing selection', () => {
    const ui = setup();
    ui.open(); ui.cards[0].dispatch('click');
    for (const query of [' żywność ', 'JABŁKO', 'sad']) {
        ui.control('search').value = query; ui.control('search').dispatch('input');
        assert.equal(ui.cards[0].hidden, false);
        assert.equal(ui.cards[1].hidden, true);
    }
    ui.control('search').value = 'espresso'; ui.control('search').dispatch('input');
    assert.equal(ui.cards[0].hidden, true);
    assert.equal(ui.cards[1].hidden, false);
    ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '1');
    ui.open();
    assert.equal(ui.control('search').value, '');
    assert.equal(ui.cards[0].hidden, false);
    assert.equal(ui.control('search').dispatch('keydown', { key: 'Enter' }).defaultPrevented, true);
});

test('empty library and no search results allow cancelling or explicitly choosing no photo', () => {
    const ui = setup({ empty: true });
    ui.open();
    assert.equal(ui.control('empty').hidden, false);
    assert.equal(ui.control('confirm').disabled, true);
    ui.control('none').dispatch('click');
    assert.equal(ui.control('confirm').disabled, false);
    ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '');
});

test('multi select preserves existing order, appends photos and commits numeric IDs only on confirmation', () => {
    const ui = setup();
    const selected = [2];
    let result;
    ui.library.open({ multiple: true, selected, onConfirm: ids => { result = Array.from(ids); } });
    assert.equal(ui.cards[1].attributes['aria-pressed'], 'true');
    ui.cards[0].dispatch('click');
    assert.equal(ui.cards[0].attributes['aria-pressed'], 'true');
    assert.equal(result, undefined);
    assert.deepEqual(selected, [2]);
    ui.control('confirm').dispatch('click');
    assert.deepEqual(result, [2, 1]);
    ui.library.open({ multiple: true, selected: result, onConfirm: ids => { result = Array.from(ids); } });
    ui.cards[1].dispatch('click');
    ui.control('confirm').dispatch('click');
    assert.deepEqual(result, [1]);
});

test('multi select cancellation, search and clear do not mutate the caller or single-select fields', () => {
    const ui = setup();
    const selected = [2, 1];
    ui.field('value').value = '1';
    ui.library.open({ multiple: true, selected, onConfirm: () => assert.fail('Cancelled selection committed') });
    ui.control('search').value = 'espresso'; ui.control('search').dispatch('input');
    assert.equal(ui.cards[0].hidden, true);
    assert.equal(ui.cards[0].attributes['aria-pressed'], 'true');
    ui.control('none').dispatch('click'); ui.cancel.dispatch('click');
    assert.deepEqual(selected, [2, 1]);
    assert.equal(ui.field('value').value, '1');
    ui.open(); ui.cards[1].dispatch('click'); ui.control('confirm').dispatch('click');
    assert.equal(ui.field('value').value, '2');
    assert.deepEqual(selected, [2, 1]);
});

test('multi select safely keeps stale IDs until explicit removal and allows empty confirmation', () => {
    const ui = setup();
    let result;
    ui.library.open({ multiple: true, selected: [999, 1, 1], onConfirm: ids => { result = Array.from(ids); } });
    ui.control('confirm').dispatch('click');
    assert.deepEqual(result, [999, 1]);
    ui.library.open({ multiple: true, selected: result, onConfirm: ids => { result = Array.from(ids); } });
    ui.control('none').dispatch('click'); ui.control('confirm').dispatch('click');
    assert.deepEqual(result, []);
});
