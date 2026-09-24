import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const script = readFileSync(process.env.MENU_ORDER_SCRIPT || new URL('../../public/js/menu-order.js', import.meta.url), 'utf8');
function setup({ failure = false, deferred = false, readyState = 'loading' } = {}) {
    class Element {
        constructor(attributes = {}) {
            this.attributes = { ...attributes }; this.children = []; this.parentElement = null;
            this.dataset = {}; this.listeners = new Map(); this.textContent = '';
            for (const [name, value] of Object.entries(attributes)) if (name.startsWith('data-')) {
                this.dataset[name.slice(5).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())] = value;
            }
        }
        matches(selector) {
            if (selector.startsWith('.')) return (this.attributes.class || '').split(' ').includes(selector.slice(1));
            return this.hasAttribute(selector.slice(1, -1));
        }
        closest(selector) { return this.matches(selector) ? this : this.parentElement?.closest(selector) || null; }
        hasAttribute(name) { return Object.hasOwn(this.attributes, name); }
        setAttribute(name, value) { this.attributes[name] = value; }
        removeAttribute(name) { delete this.attributes[name]; }
        contains(element) { return !!element && (element === this || this.children.some(child => child.contains(element))); }
        appendChild(row) { this.insertBefore(row, null); return row; }
        insertBefore(row, target) {
            if (row === target) return;
            if (row.parentElement) row.parentElement.children.splice(row.parentElement.children.indexOf(row), 1);
            this.children.splice(target ? this.children.indexOf(target) : this.children.length, 0, row);
            row.parentElement = this;
        }
        get nextSibling() { return this.parentElement.children[this.parentElement.children.indexOf(this) + 1] || null; }
        querySelector(selector) { return this.children.find(child => child.matches(selector.replace(':scope > ', ''))) || null; }
        getBoundingClientRect() {
            if (!this.hasAttribute('data-menu-id')) return this.parentElement.getBoundingClientRect();
            return { top: this.parentElement.children.indexOf(this) * 100, height: 100 };
        }
        addEventListener(name, handler) { this.listeners.set(name, [...(this.listeners.get(name) || []), handler]); }
        focus() {}
    }
    const root = new Element({ 'data-menu-sort': '', 'data-parent': '', 'data-url': '/menu/reorder' });
    function row(id, parent) {
        const row = parent.appendChild(new Element({ 'data-menu-id': String(id) }));
        const header = row.appendChild(new Element({ class: 'cms-menu-item' }));
        row.handle = header.appendChild(new Element({ class: 'cms-menu-drag' }));
        row.title = header.appendChild(new Element());
        return row;
    }
    const a = row(1, root), b = row(2, root);
    const sub = a.appendChild(new Element({ 'data-menu-sort': '', 'data-parent': '1' }));
    const c = row(3, sub), d = row(4, sub);
    const otherSub = b.appendChild(new Element({ 'data-menu-sort': '', 'data-parent': '2' }));
    const e = row(5, otherSub);
    const status = { textContent: '' }, requests = [], readyCallbacks = [];
    let resolveSave;
    const document = {
        readyState,
        querySelector: selector => selector === '[data-menu-sort]' ? root : { content: 'csrf' },
        getElementById: () => status,
        addEventListener: (_, callback) => readyCallbacks.push(callback),
    };
    const context = { document, fetch: async (_, options) => {
        requests.push(JSON.parse(options.body));
        if (deferred) await new Promise(resolve => { resolveSave = resolve; });
        return { ok: !failure, json: async () => ({ success: !failure }) };
    }};
    runInNewContext(script, context);
    readyCallbacks.forEach(callback => callback());
    function emit(type, target, extra = {}) {
        const event = { target, clientY: 10, relatedTarget: null, defaultPrevented: false,
            dataTransfer: { setData() {} }, preventDefault() { this.defaultPrevented = true; }, ...extra };
        for (const handler of root.listeners.get(type) || []) handler(event);
        return event;
    }
    // Model the native browser contract: re-hit-test after every dragover,
    // deliver drop only if the LAST dragover was cancelled, then ALWAYS dragend.
    function nativeDrag(source, list, y) {
        emit('dragstart', source.handle);
        let event, target;
        for (let i = 0; i < 3; i++) {
            const hit = list.children.find(row => { const box = row.getBoundingClientRect(); return y >= box.top && y < box.top + box.height; });
            target = hit?.title || list;
            event = emit('dragover', target, { clientY: y });
        }
        if (event.defaultPrevented) emit('drop', target, { clientY: y });
        emit('dragend', source.handle);
        return event.defaultPrevented;
    }
    return { root, sub, otherSub, a, b, c, d, e, status, requests, emit, nativeDrag,
        resolveSave: () => resolveSave(), initializeAgain: () => runInNewContext(script, { ...context, document: { ...document, readyState: 'complete' } }) };
}
const order = list => list.children.map(row => Number(row.dataset.menuId));
const tick = () => new Promise(resolve => setImmediate(resolve));

for (const nested of [false, true]) {
    test(`native dragover → drop → dragend keeps ${nested ? 'submenu' : 'root'} row in place before and after save`, async () => {
        const ui = setup({ deferred: true });
        const first = nested ? ui.c : ui.a, last = nested ? ui.d : ui.b, list = nested ? ui.sub : ui.root;
        assert.equal(ui.nativeDrag(last, list, 10), true);
        const expected = [Number(last.dataset.menuId), Number(first.dataset.menuId)];
        assert.deepEqual(order(list), expected);
        assert.equal(ui.requests.length, 1);
        assert.deepEqual(ui.requests[0], { parent_id: nested ? 1 : null, items: expected.map(id => ({ id })) });
        assert.equal(ui.status.textContent, 'Zapisywanie…');
        ui.resolveSave(); await tick();
        assert.deepEqual(order(list), expected);
        assert.equal(ui.status.textContent, 'Kolejność została zapisana.');
    });
}

test('insertion marker is visible without moving DOM until drop; dragging down works', async () => {
    const ui = setup();
    ui.emit('dragstart', ui.a.handle);
    const event = ui.emit('dragover', ui.b.title, { clientY: 190 });
    assert.equal(event.defaultPrevented, true);
    assert.equal(ui.b.attributes['data-menu-drop'], 'after');
    assert.deepEqual(order(ui.root), [1, 2]);
    ui.emit('drop', ui.b.title, { clientY: 190 });
    ui.emit('dragend', ui.a.handle);
    await tick();
    assert.deepEqual(order(ui.root), [2, 1]);
    assert.equal(ui.b.hasAttribute('data-menu-drop'), false);
    assert.equal(ui.a.hasAttribute('data-menu-dragging'), false);
});

test('own row remains a valid native drop target and unchanged order is not saved', () => {
    const ui = setup();
    assert.equal(ui.nativeDrag(ui.a, ui.root, 10), true);
    assert.deepEqual(order(ui.root), [1, 2]);
    assert.equal(ui.requests.length, 0);
});

test('list padding is a valid native drop target', async () => {
    const ui = setup();
    assert.equal(ui.nativeDrag(ui.a, ui.root, 220), true);
    await tick();
    assert.deepEqual(order(ui.root), [2, 1]);
});

test('cross-parent and cross-level drops are rejected, including nested DOM targets', () => {
    const ui = setup();
    for (const [source, target] of [[ui.c, ui.b], [ui.c, ui.e], [ui.b, ui.c]]) {
        ui.emit('dragstart', source.handle);
        assert.equal(ui.emit('dragover', target.title).defaultPrevented, false);
        ui.emit('drop', target.title);
        ui.emit('dragend', source.handle);
    }
    assert.deepEqual(order(ui.root), [1, 2]);
    assert.deepEqual(order(ui.sub), [3, 4]);
    assert.deepEqual(order(ui.otherSub), [5]);
    assert.equal(ui.requests.length, 0);
});

test('cancelled drag clears the marker without changing order or saving', () => {
    const ui = setup();
    ui.emit('dragstart', ui.b.handle);
    ui.emit('dragover', ui.a.title);
    ui.emit('dragend', ui.b.handle);
    assert.deepEqual(order(ui.root), [1, 2]);
    assert.equal(ui.a.hasAttribute('data-menu-drop'), false);
    assert.equal(ui.requests.length, 0);
});

test('failed save restores previous order and reports an error', async () => {
    const ui = setup({ failure: true });
    ui.nativeDrag(ui.b, ui.root, 10);
    await tick();
    assert.deepEqual(order(ui.root), [1, 2]);
    assert.match(ui.status.textContent, /Nie udało się/);
    assert.doesNotMatch(ui.status.textContent, /została zapisana/);
});

test('late initialization runs once and keyboard still sorts within one parent', async () => {
    const ui = setup({ readyState: 'complete' });
    ui.initializeAgain();
    ui.emit('keydown', ui.c.handle, { key: 'ArrowDown' });
    await tick();
    assert.equal(ui.requests.length, 1);
    assert.deepEqual(ui.requests[0], { parent_id: 1, items: [{ id: 4 }, { id: 3 }] });
});
