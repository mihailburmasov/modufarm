// Паритет с прежним ручным сайтом (коммит d3898d3): собранные страницы должны совпадать построчно,
// кроме намеренных отличий (метки версий css/js, пути фото, lead.php, data-edit, убранные служебные комментарии).
// node tests/parity.mjs — печатает оставшиеся отличия; пусто = совпадает.
import { execFileSync } from 'node:child_process';
import { readFileSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
const OLD = 'd3898d3';
const out = mkdtempSync(join(tmpdir(), 'mf-parity-'));
execFileSync('php', ['app/bin/build.php'], { env: { ...process.env, SITE_URL: 'https://DOMAIN', SITE_BASE: '', OUT_DIR: out, STORAGE_DIR: join(out, '.st') } });
const norm = s => s
  .replace(/\?v=[0-9a-z]+/g, '?v=X')
  .replace(/img\/docs\//g, 'img/').replace(/hero-sad-1120/g, 'hero-sad').replace(/hero-sad-700/g, 'hero-sad-sm')
  .replace(/send\.php/g, 'lead.php').replace(/ data-edit="[^"]*"/g, '').replace(/id="i-arrow"viewBox/, 'id="i-arrow" viewBox')
  .replace(/^[ \t]*<!--(?! Схема сада)[\s\S]*?-->\n/gm, '')
  .replace(/<lastmod>[\d-]+<\/lastmod>/g, '');
let bad = 0;
for (const f of ['index.html', 'politika.html', 'soglasie.html', 'spasibo.html', 'sitemap.xml']) {
  const a = norm(execFileSync('git', ['show', `${OLD}:${f}`], { encoding: 'utf8' })).split('\n');
  const b = norm(readFileSync(join(out, f), 'utf8')).split('\n');
  const diffs = [];
  for (let i = 0; i < Math.max(a.length, b.length); i++) if (a[i] !== b[i]) diffs.push(`  ${i + 1}:\n    было:  ${a[i]}\n    стало: ${b[i]}`);
  console.log((diffs.length ? 'DIFF ' : 'OK   ') + f + (diffs.length ? '\n' + diffs.slice(0, 5).join('\n') : ''));
  if (diffs.length) bad++;
}
process.exitCode = bad ? 1 : 0;
