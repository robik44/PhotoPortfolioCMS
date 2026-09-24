import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { runInNewContext } from 'node:vm';

const template = readFileSync(new URL('../../resources/views/components/gallery-lightbox.blade.php', import.meta.url), 'utf8');
const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];

// Exercise the view's actual event handlers without introducing a browser dependency.
function setup(count = 3) {
    const document = { body: { style: { overflow: 'auto' } }, activeElement: null };
    function element() {
        const listeners = new Map();
        const classes = new Set();
        return {
            dataset: {}, attributes: {}, src: '', alt: '', textContent: '',
            classList: {
                add: name => classes.add(name), remove: name => classes.delete(name),
                contains: name => classes.has(name),
            },
            setAttribute(name, value) { this.attributes[name] = value; },
            addEventListener(name, handler) {
                listeners.set(name, [...(listeners.get(name) ?? []), handler]);
            },
            dispatch(name, properties = {}) {
                const event = {
                    target: this, defaultPrevented: false,
                    preventDefault() { this.defaultPrevented = true; }, stopPropagation() {},
                    ...properties,
                };
                for (const handler of listeners.get(name) ?? []) handler(event);
                return event;
            },
            contains(target) { return target === this; },
            focus() { document.activeElement = this; },
        };
    }
    Object.assign(document, element());
    const items = Array.from({ length: count }, (_, index) => Object.assign(element(), {
        dataset: {
            photoUrl: `/storage/photos/${index}.jpg`, photoTitle: `Tytuł "${index}" & B`,
            photoDescription: `Opis <${index}>`, photoAlt: `Alternatywny opis ${index}`,
        },
    }));
    const elements = Object.fromEntries([
        'lightbox', 'lightboxImage', 'lightboxTitle', 'lightboxDescription',
        'lightboxClose', 'lightboxPrev', 'lightboxNext',
    ].map(id => [id, element()]));
    document.getElementById = id => elements[id];
    document.querySelectorAll = selector => {
        assert.equal(selector, '.gallery-item');
        return items;
    };
    runInNewContext(script, { document });
    document.dispatch('DOMContentLoaded');
    return { document, items, ...elements };
}

test('thumbnail opens original photo; previous and next cycle through all photos', () => {
    const ui = setup(14);
    ui.items[0].dispatch('click');
    assert.equal(ui.lightbox.attributes['aria-hidden'], 'false');
    assert.equal(ui.lightboxImage.src, '/storage/photos/0.jpg');
    assert.equal(ui.lightboxImage.alt, 'Alternatywny opis 0');
    assert.equal(ui.lightboxTitle.textContent, 'Tytuł "0" & B');
    assert.equal(ui.lightboxDescription.textContent, 'Opis <0>');
    assert.equal(ui.document.body.style.overflow, 'hidden');
    ui.lightboxPrev.dispatch('click');
    assert.equal(ui.lightboxImage.src, '/storage/photos/13.jpg');
    ui.lightboxNext.dispatch('click');
    assert.equal(ui.lightboxImage.src, '/storage/photos/0.jpg');
    ui.lightboxNext.dispatch('click');
    assert.equal(ui.lightboxImage.src, '/storage/photos/1.jpg');
});

test('X, Escape and backdrop close the viewer and return focus to the opening thumbnail', () => {
    for (const close of [
        ui => ui.document.dispatch('click', { target: ui.lightboxClose }),
        ui => ui.document.dispatch('keydown', { key: 'Escape' }),
        ui => ui.lightbox.dispatch('click'),
    ]) {
        const ui = setup();
        ui.items[1].dispatch('click');
        ui.lightboxNext.dispatch('click');
        close(ui);
        assert.equal(ui.lightbox.classList.contains('is-open'), false);
        assert.equal(ui.lightbox.attributes['aria-hidden'], 'true');
        assert.equal(ui.lightboxImage.src, '');
        assert.equal(ui.document.body.style.overflow, 'auto');
        assert.equal(ui.document.activeElement, ui.items[1]);
        assert.equal(ui.items.length, 3);
    }
});

test('keyboard opens thumbnails, navigates photos, traps focus and ignores arrows after closing', () => {
    const ui = setup();
    assert.equal(ui.items[1].dispatch('keydown', { key: 'Enter' }).defaultPrevented, true);
    assert.equal(ui.document.activeElement, ui.lightboxClose);
    ui.document.dispatch('keydown', { key: 'ArrowRight' });
    assert.equal(ui.lightboxImage.src, '/storage/photos/2.jpg');
    assert.equal(ui.document.dispatch('keydown', { key: 'ArrowLeft' }).defaultPrevented, true);
    assert.equal(ui.lightboxImage.src, '/storage/photos/1.jpg');
    ui.document.dispatch('keydown', { key: 'Tab', shiftKey: true });
    assert.equal(ui.document.activeElement, ui.lightboxNext);
    ui.document.dispatch('keydown', { key: 'Tab' });
    assert.equal(ui.document.activeElement, ui.lightboxClose);
    ui.document.dispatch('keydown', { key: 'Escape' });
    ui.document.dispatch('keydown', { key: 'ArrowRight' });
    assert.equal(ui.lightboxImage.src, '');
    ui.items[0].dispatch('keydown', { key: ' ' });
    assert.equal(ui.lightboxImage.src, '/storage/photos/0.jpg');
});

test('empty gallery does not open a viewer', () => {
    const ui = setup(0);
    ui.lightboxNext.dispatch('click');
    assert.equal(ui.lightbox.classList.contains('is-open'), false);
    assert.equal(ui.document.body.style.overflow, 'auto');
});


test('multiple PageBuilder galleries navigate only within the opened element', () => {
    const ui = setup(4);
    ui.items.forEach((item, index) => { item.dataset.galleryGroup = index < 2 ? 'first' : 'second'; });
    ui.items[0].dispatch('click');
    ui.lightboxPrev.dispatch('click');
    assert.equal(ui.lightboxImage.src, '/storage/photos/1.jpg');
    ui.lightboxNext.dispatch('click');
    assert.equal(ui.lightboxImage.src, '/storage/photos/0.jpg');
    ui.document.dispatch('keydown', { key: 'Escape' });
    ui.items[3].dispatch('click');
    ui.lightboxNext.dispatch('click');
    assert.equal(ui.lightboxImage.src, '/storage/photos/2.jpg');
});
