import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const root = new URL('../../', import.meta.url);
const read = path => readFileSync(new URL(path, root), 'utf8');

test('public button keeps obfuscated email and exact CMS caption', () => {
  const blade = read('resources/views/components/public-builder-canvas.blade.php');
  assert.match(blade, /data-ff-email-label/);
  assert.match(blade, /email-protect\.js/);
  assert.match(blade, /@filemtime\(public_path\('js\/email-protect\.js'\)\)/);
});

test('public email script restores mailto and exact saved label', () => {
  const attrs = new Map([
    ['data-ff-email', Buffer.from('magda@foodfoto.pl').toString('base64')],
    ['data-ff-email-label', Buffer.from('magda@foodfoto.pl →').toString('base64')],
  ]);
  const link = {
    textContent: 'Napisz e-mail →',
    href: '/kontakt',
    hasAttribute: name => attrs.has(name),
    getAttribute: name => attrs.get(name),
    removeAttribute: name => attrs.delete(name),
  };
  const listeners = new Map();
  const context = {
    Uint8Array,
    TextDecoder,
    atob: encoded => Buffer.from(encoded, 'base64').toString('binary'),
    document: {
      readyState: 'complete',
      querySelectorAll: selector => selector === 'a[data-ff-email]' ? [link] : [],
      addEventListener: (name, handler) => listeners.set(name, handler),
    },
    window: { addEventListener() {} },
  };
  vm.runInNewContext(read('public/js/email-protect.js'), context);
  assert.equal(link.href, 'mailto:magda@foodfoto.pl');
  assert.equal(link.textContent, 'magda@foodfoto.pl →');
  assert.equal(attrs.size, 0);
  assert.ok(listeners.has('click'));
});

test('all button editors put text and action controls first', () => {
  const blade = read('resources/views/admin/pages/builder.blade.php');
  const js = read('public/js/builder-contact-button-editor.js');
  assert.match(blade, /builder-contact-button-editor\.js/);
  assert.match(js, /tekst przycisku/);
  assert.match(js, /akcja \/ link/);
  assert.match(js, /insertAdjacentElement\('afterend', node\)/);
  assert.doesNotMatch(js, /item\.content\s*=|item\.button_link\s*=/);
});
