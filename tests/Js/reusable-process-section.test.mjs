import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const blade = readFileSync(new URL('../../resources/views/admin/pages/builder.blade.php', import.meta.url), 'utf8');

test('process section is available once in the Elementy palette', () => {
  assert.equal((blade.match(/data-fve-preset="process-collaboration"/g) || []).length, 1);
  const position = blade.indexOf('data-fve-preset="process-collaboration"');
  const elements = blade.indexOf('data-fve-add="separator"');
  const presets = blade.indexOf('Gotowe układy');
  assert.ok(position > elements && position < presets);
  assert.match(blade.slice(position, position + 150), /Jak pracuję — gotowa sekcja/);
});

test('new process insertion clones saved process properties, not page coordinates', () => {
  assert.match(blade, /const reusableProcessTemplate = @json\(\$reusableProcessTemplate\)/);
  assert.match(blade, /whereNull\('page_id'\)/);
  assert.match(blade, /firstWhere\('type', 'process_steps'\)/);
  const start = blade.indexOf("preset === 'process-collaboration'");
  const end = blade.indexOf("} else if (preset === 'three-steps')", start);
  assert.ok(start >= 0 && end > start);
  const code = blade.slice(start, end);
  assert.match(code, /key\.startsWith\('process_'\)/);
  assert.match(code, /JSON\.parse\(JSON\.stringify\(value\)\)/);
  assert.doesNotMatch(code, /item\.id\s*=/);
});
