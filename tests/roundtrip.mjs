// Каждый раздел админки загрузить и сохранить без изменений: ответ «Изменений нет», файлы data/ не меняются.
import { readFileSync, readdirSync } from 'node:fs';
import { login, api, check } from './lib.mjs';
const snap = () => Object.fromEntries(readdirSync('data').map(f => [f, readFileSync('data/' + f, 'utf8')]));
await login();
const before = snap();
const boot = await api('bootstrap');
for (const n of boot.nav) {
  const s = await api('section?id=' + n.id);
  const r = await api('section', { id: n.id, values: s.values });
  check(r.ok && r.message === 'Изменений нет', 'раздел ' + n.id, r.message || r.error);
}
const after = snap();
for (const f of Object.keys(before)) check(before[f] === after[f], 'файл data/' + f + ' не изменился');
