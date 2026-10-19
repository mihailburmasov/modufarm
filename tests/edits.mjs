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
  check(page().includes('<h1 class="hero__title">Промышленные теплицы для круглогодичного урожая</h1>'), 'на сайте прежний заголовок');

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

  // 5. Новое свидетельство: PDF + превью, привязка к программе — цифра на первом экране «Роботов» растёт
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
  check(r.ok && page('roboty.html').includes('<li><b>7</b><span>из них уже зарегистрированы'), 'программа получила свидетельство, на первом экране «Роботов» 7', r.message || r.error);
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
  check(r.ok && page('roboty.html').includes('class="cards cards--3"') && page('roboty.html').includes('<li><b>11</b>'), 'три карточки в группе — сетка на 3, всего 11');

  // 7. Скрыть раздел — пропадают и раздел, и пункты меню (на всех страницах)
  s = await S('robot');
  s.values['site.json#robot.hidden'] = true;
  r = await save('robot', s.values);
  check(r.ok && !page('roboty.html').includes('id="robot"') && !page('roboty.html').includes('href="#robot"') && !page().includes('roboty.html#robot'), 'скрытый раздел и его пункты меню пропали');
  s = await S('principle');
  s.values['site.json#principle.hidden'] = true;
  r = await save('principle', s.values);
  check(r.ok && !page().includes('id="princip"') && !page().includes('href="#princip"') && !page('roboty.html').includes('index.html#princip'), 'скрытый раздел главной пропал вместе с меню и второй кнопкой первого экрана');

  // 7а. Скрыть страницу «Сады» — файла нет, ссылок и карточки на главной нет
  s = await S('gardens');
  s.values['site.json#gardensPage.hidden'] = true;
  r = await save('gardens', s.values);
  check(r.ok && !existsSync('dist/sady.html') && !page().includes('sady.html') && !page('roboty.html').includes('sady.html'), 'скрытая страница «Сады» убрана вместе со ссылками', r.message || r.error);
  s.values['site.json#gardensPage.hidden'] = false;
  r = await save('gardens', s.values);
  check(r.ok && existsSync('dist/sady.html') && page().includes('href="sady.html"'), 'страница «Сады» вернулась');

  // 7б. Тема заявки по умолчанию — своя на каждой странице
  check(/value="Теплица под ключ" checked/.test(page()) && /value="Сельхозробот под задачу" checked/.test(page('roboty.html')) && /value="Проект фруктового сада" checked/.test(page('sady.html')), 'в форме отмечена тема своей страницы');

  // 8. Телефоны: пометка сохраняется, ссылка tel: считается сама
  s = await S('company');
  s.values['company.json#phones'][1].display = '8 978 111-22-33';
  r = await save('company', s.values);
  const c = JSON.parse(readFileSync('data/company.json', 'utf8'));
  check(r.ok && c.phones[1].tel === '+79781112233' && c.phones[0].note === 'написать в MAX' && c.phones[0].noteUrl.startsWith('https://max.ru/') && /<a class="lead__note" href="https:\/\/max\.ru\//.test(page()), 'телефон пересчитан, пометка и ссылка на MAX целы');
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

  // 10. Калькулятор: цены из админки — на сайте с пробелами; отрицательная и дробная цена отклоняются;
  // пустые цены — «по запросу»; расчёт приходит с заявкой
  check(page().includes('id="kalkulyator"') && page().includes('<i>по запросу</i>'), 'калькулятор на главной, без цен — «по запросу»');
  s = await S('calc');
  s.values['site.json#calc.frame.price'] = 1250000;
  s.values['site.json#calc.options'][0].fixed = 300000;
  r = await save('calc', s.values);
  check(r.ok && page().includes('data-price="1250000"') && page().includes('<i>1&nbsp;250&nbsp;000&nbsp;₽ за полублок</i>') && page().includes('<i>300&nbsp;000&nbsp;₽ на комплекс</i>'), 'цены калькулятора на сайте', r.message || r.error);
  s.values['site.json#calc.covers'][0].price = -5;
  r = await save('calc', s.values);
  check(!r.ok && /не меньше 0/.test(r.error), 'отрицательная цена отклонена', r.error);
  s.values['site.json#calc.covers'][0].price = 10.5;
  r = await save('calc', s.values);
  check(!r.ok && /целое/.test(r.error), 'дробная цена отклонена', r.error);
  s.values['site.json#calc.covers'][0].price = null;
  s.values['site.json#calc.defSpans'] = 50;
  r = await save('calc', s.values);
  check(!r.ok && /по умолчанию/.test(r.error), 'размер по умолчанию больше наибольшего — отказ', r.error);
  s.values['site.json#calc.defSpans'] = 1;
  s.values['site.json#calc.covers'] = [];
  r = await save('calc', s.values);
  check(!r.ok && /покрыти/.test(r.error), 'калькулятор без покрытия — отказ', r.error);
  [st, j] = await lead({ calc: 'Теплица 20 × 100 м (2 пролёта, 4 полублока), 2 000 м². Покрытие: двойная плёнка с наддувом' });
  check(st === 200 && j.ok && (await api('leads')).items[0].calc.startsWith('Теплица 20 × 100 м'), 'расчёт калькулятора сохранён в заявке');
} finally {
  // вернуть данные как были, убрать загруженные тестом файлы и пересобрать
  for (const [f, t] of Object.entries(original)) writeFileSync('data/' + f, t);
  if (made.pdf) rmSync('public/docs/' + made.pdf, { force: true });
  if (made.photo) for (const sfx of ['', '-sm']) rmSync('public/img/' + made.photo + sfx + '.webp', { force: true });
  const r = await api('rebuild', {});
  check(r.ok && JSON.stringify(snap()) === JSON.stringify(original), 'data/ возвращена к исходному состоянию, тестовые файлы убраны', r.message || r.error);
}
