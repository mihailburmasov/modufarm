// Сценарии правок через API админки и приём заявок. В конце data/ возвращается как было.
// Запуск из корня проекта: node tests/edits.mjs (сервер и вход — см. tests/lib.mjs)
import { readFileSync, writeFileSync, readdirSync, existsSync, rmSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { SITE, req, login, api, check } from './lib.mjs';

const snap = () => Object.fromEntries(readdirSync('data').map(f => [f, readFileSync('data/' + f, 'utf8')]));
const page = (f = 'index.html') => readFileSync('dist/' + f, 'utf8');
const original = snap();
const made = {}; // загруженные тестом файлы — удаляются в конце
const S = id => api('section?id=' + id);
const save = (id, values) => api('section', { id, values });

// без входа — 401 у API
check((await (await fetch(SITE + '/admin/api/bootstrap')).status) === 401, 'API без входа отвечает 401');
await login();

try {
  // 1. Правка с HTML-символами и неразрывным пробелом
  let s = await S('hero');
  const h1 = 'hero.json';
  s.values['site.json#hero.h1'] = 'Роботы <script>alert(1)</script> & «сады» тест';
  let r = await save('hero', s.values);
  check(r.ok && r.message.startsWith('Сохранено'), 'правка заголовка сохранена', r.message || r.error);
  check(page().includes('<h1 class="hero__title">Роботы &lt;script&gt;alert(1)&lt;/script&gt; &amp; «сады»&nbsp;тест</h1>'), 'заголовок на сайте экранирован');

  // 2. Откат по истории
  const rev = (await api('revisions')).items.find(x => x.file === 'site.json');
  r = await api('revision/restore', { id: rev.id });
  check(r.ok && snap()['site.json'] === original['site.json'], 'откат по истории вернул site.json');
  check(page().includes('Сельхозроботы под задачи вашей культуры и&nbsp;проекты фруктовых садов'), 'на сайте прежний заголовок');

  // 3. Неверные значения: несуществующее свидетельство, пустая иконка, плохая дата
  s = await S('programs');
  const groups = s.values['site.json#programs.groups'];
  groups[0].cards[0].doc = '999';
  r = await save('programs', s.values);
  check(!r.ok && /999/.test(r.error), 'ссылка на несуществующее свидетельство отклонена', r.error);
  s = await S('company');
  s.values['company.json#legalDate'] = '13.10.2026';
  r = await save('company', s.values);
  check(!r.ok && /формат/.test(r.error), 'дата в неверном формате отклонена', r.error);
  check(JSON.stringify(snap()) === JSON.stringify(original), 'после отказов data/ не изменилась');

  // 4. Удаление свидетельства, на которое ссылается программа, — сборка не проходит, файлы на месте
  s = await S('documents');
  s.values['documents.json#items'] = s.values['documents.json#items'].filter(d => d.num !== '2026689317');
  r = await save('documents', s.values);
  check(!r.ok && /2026689317/.test(r.error), 'удаление используемого свидетельства остановлено', r.error);
  check(snap()['documents.json'] === original['documents.json'], 'documents.json вернулся как был');

  // 5. Новое свидетельство: PDF + превью, привязка к программе — цифра на первом экране растёт
  const fd = new FormData();
  fd.append('file', new Blob(['%PDF-1.4\n%тест\n'], { type: 'application/pdf' }), 'Свидетельство 2026700001.pdf');
  const up = await api('pdf/upload', null, fd);
  made.pdf = up.name;
  check(up.ok && /^svidetelstvo-2026700001(-\d+)?\.pdf$/.test(up.name), 'PDF загружен с латинским именем', up.name || up.error);
  check(existsSync('dist/docs/' + up.name), 'PDF попал в собранный сайт');
  const bad = new FormData();
  bad.append('file', new Blob(['<html>'], { type: 'application/pdf' }), 'fake.pdf');
  const upBad = await api('pdf/upload', null, bad);
  check(!upBad.ok, 'не-PDF отклонён', upBad.error);
  const jpg = execFileSync('php', ['-r', '$i=imagecreatetruecolor(800,1100);imagefill($i,0,0,imagecolorallocate($i,250,250,240));ob_start();imagejpeg($i);echo ob_get_clean();'], { maxBuffer: 1 << 24 });
  const pf = new FormData(); pf.append('cat', 'docs'); pf.append('file', new Blob([jpg], { type: 'image/jpeg' }), 'p.jpg'); pf.append('alt', 'Свидетельство № 2026700001');
  const ph = await api('photo/upload', null, pf);
  made.photo = ph.photo?.id;
  check(ph.ok && ph.photo.w === 800, 'превью загружено и пережато в WebP', ph.photo?.id || ph.error);
  s = await S('documents');
  s.values['documents.json#items'].push({ num: '2026700001', date: '14.10.2026', name: 'Сбор урожая', pdf: up.name, preview: ph.photo.id });
  r = await save('documents', s.values);
  check(r.ok, 'свидетельство добавлено', r.message || r.error);
  check(page().includes('№ 2026700001 · 14.10.2026'), 'карточка свидетельства на сайте');
  check(page().includes('srcset="img/' + ph.photo.id + '-sm.webp'), 'крупное превью с уменьшенной копией');
  s = await S('programs');
  s.values['site.json#programs.groups'][0].cards[0].doc = '2026700001';
  r = await save('programs', s.values);
  check(r.ok && page().includes('<li><b>7</b><span>из них уже зарегистрированы'), 'программа получила свидетельство, на первом экране 7', r.message || r.error);
  const del = await api('pdf/delete', { name: up.name });
  check(!del.ok && /используется/.test(del.error), 'используемый PDF удалить нельзя', del.error);

  // 6. Новая программа без иконки — понятная ошибка; пустая строка карточки — пропускается
  s = await S('programs');
  s.values['site.json#programs.groups'][2].cards.push({ title: 'Новая', icon: '', text: 'x', doc: '' });
  r = await save('programs', s.values);
  check(!r.ok && /иконк/.test(r.error), 'программа без иконки отклонена', r.error);
  s.values['site.json#programs.groups'][2].cards[2] = { title: '', icon: '', text: '', doc: '' };
  r = await save('programs', s.values);
  check(r.ok && r.message === 'Изменений нет', 'пустая новая карточка пропущена', r.message || r.error);
  s.values['site.json#programs.groups'][2].cards[2] = { title: 'Новая', icon: 'bug', text: 'Текст', doc: '' };
  r = await save('programs', s.values);
  check(r.ok && page().includes('class="cards cards--3"') && page().includes('<li><b>11</b>'), 'три карточки в группе — сетка на 3, всего 11');

  // 7. Скрыть раздел — пропадают и раздел, и пункты меню
  s = await S('robot');
  s.values['site.json#robot.hidden'] = true;
  r = await save('robot', s.values);
  check(r.ok && !page().includes('id="robot"') && !page().includes('href="#robot"'), 'скрытый раздел и его пункты меню пропали');

  // 8. Телефоны: пометка сохраняется, ссылка tel: считается сама
  s = await S('company');
  s.values['company.json#phones'][1].display = '8 978 111-22-33';
  r = await save('company', s.values);
  const c = JSON.parse(readFileSync('data/company.json', 'utf8'));
  check(r.ok && c.phones[1].tel === '+79781112233' && c.phones[0].note === 'есть в MAX', 'телефон пересчитан, пометка цела');
  check(page('politika.html').includes('href="tel:+79781112233"'), 'новый телефон в политике');

  // 9. Заявки: без имени — принимается (имя необязательно), UTM сохраняются; без согласия — отказ; бот — молча
  const lead = (o) => fetch(SITE + '/lead.php', { method: 'POST', body: new URLSearchParams({ phone: '8 (900) 123-45-67', consent: '1', elapsed: '9', topic: 'Теплица под ключ', crop: 'лимон, 1 га', utm_source: 'yandex', utm_campaign: 'test', page: SITE + '/', ...o }) }).then(x => x.json().then(j => [x.status, j]));
  let [st, j] = await lead({});
  check(st === 200 && j.ok, 'заявка без имени принята');
  const last = (await api('leads')).items[0];
  check(last.phone === '+7 (900) 123-45-67' && last.utm.utm_source === 'yandex' && last.topic === 'Теплица под ключ', 'заявка в журнале с UTM и темой');
  [st, j] = await lead({ consent: '' });
  check(st === 422 && j.error === 'consent', 'без согласия — отказ');
  const n = (await api('leads')).items.length;
  [st, j] = await lead({ website: 'spam' });
  check(j.ok && (await api('leads')).items.length === n, 'бот получил «ок», заявка не записана');
} finally {
  // вернуть данные как были, убрать загруженные тестом файлы и пересобрать
  for (const [f, t] of Object.entries(original)) writeFileSync('data/' + f, t);
  if (made.pdf) rmSync('public/docs/' + made.pdf, { force: true });
  if (made.photo) for (const sfx of ['', '-sm']) rmSync('public/img/' + made.photo + sfx + '.webp', { force: true });
  const r = await api('rebuild', {});
  check(r.ok && JSON.stringify(snap()) === JSON.stringify(original), 'data/ возвращена к исходному состоянию, тестовые файлы убраны', r.message || r.error);
}
