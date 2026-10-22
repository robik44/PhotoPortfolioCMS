import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const css = readFileSync(new URL('../../public/css/foodfoto-process.css', import.meta.url), 'utf8');
const editor = readFileSync(new URL('../../resources/views/admin/pages/builder.blade.php', import.meta.url), 'utf8');
const js = readFileSync(new URL('../../public/js/process-custom-photo-only.js', import.meta.url), 'utf8');
const publicView = readFileSync(new URL('../../resources/views/components/builder-process-steps.blade.php', import.meta.url), 'utf8');

test('decorative image is layered below intro and step content', () => {
  const block = css.slice(css.indexOf('FOODFOTO_CUSTOM_PROCESS_BACKGROUND_V1'));
  assert.match(block, /\.ff-process \.ff-process-decorative-photo/);
  assert.match(block, /z-index: 0 !important/);
  assert.match(block, /\.ff-process > \.ff-process-intro/);
  assert.match(block, /\.ff-process > \.ff-process-steps/);
  assert.match(block, /z-index: 2 !important/);
  assert.match(block, /\.ff-process \.ff-process-branch/);
  assert.match(block, /display: none !important/);
});

test('shared process photo UI is present and legacy controls are hidden', () => {
  assert.match(editor, /process-custom-photo-only\.js/);
  assert.match(js, /poświata i gałązka za napisami/);
  assert.match(js, /pokaż rysunek gałązki/);
  assert.doesNotMatch(js, /decorative_photo_id\s*=/);
  assert.match(publicView, /\$decorativePhoto/);
  assert.match(publicView, /ff-process-decorative-photo/);
});
