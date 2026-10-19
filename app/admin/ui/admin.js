/* Админка ModuFarm: разделы, формы по описанию полей с сервера, медиатека, документы PDF, заявки, история, корзина. */
(function () {
  'use strict';
  var A = window.ADMIN;
  var BOOT = null, PHOTOS = null, dirty = false, saving = false, skipHash = false, formCtl = null;

  // ---------- утилиты ----------
  function $(s, r) { return (r || document).querySelector(s); }
  function h(tag, attrs) {
    var el = document.createElement(tag);
    if (attrs) for (var k in attrs) {
      var v = attrs[k];
      if (v == null || v === false) continue;
      if (k === 'class') el.className = v;
      else if (k === 'text') el.textContent = v;
      else if (k === 'html') el.innerHTML = v;
      else if (k.slice(0, 2) === 'on') el.addEventListener(k.slice(2), v);
      else el.setAttribute(k, v === true ? '' : v);
    }
    for (var i = 2; i < arguments.length; i++) add(el, arguments[i]);
    return el;
  }
  function add(el, c) {
    if (c == null || c === false) return;
    if (Array.isArray(c)) c.forEach(function (x) { add(el, x); });
    else el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c);
  }
  function icon(name) {
    var s = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    s.setAttribute('class', 'ic'); s.setAttribute('aria-hidden', 'true');
    var u = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    u.setAttribute('href', '#i-' + name); s.appendChild(u);
    return s;
  }
  function photoUrl(id, sm) { return A.base + '/img/' + id + (sm === false ? '' : '-sm') + '.webp'; }
  function pdfUrl(n) { return A.base + '/docs/' + encodeURIComponent(n); }
  function siteUrl(p) { return A.base + p; }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function esc(s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

  function api(path, opts) {
    opts = opts || {};
    var init = { method: opts.method || (opts.body || opts.form ? 'POST' : 'GET'), headers: { 'X-CSRF': A.csrf }, credentials: 'same-origin' };
    if (opts.form) init.body = opts.form;
    else if (opts.body) { init.body = JSON.stringify(opts.body); init.headers['Content-Type'] = 'application/json'; }
    return fetch(A.api + path, init).then(function (r) {
      return r.json().catch(function () { throw new Error('Сервер ответил ошибкой (' + r.status + ')'); });
    }).then(function (j) {
      if (j.relogin) { location.href = A.base + '/admin/login'; throw new Error(j.error); }
      if (!j.ok) throw new Error(j.error || 'Ошибка');
      return j;
    });
  }

  function toast(msg, type, link) {
    var t = h('div', { class: 'toast' + (type === 'err' ? ' toast--err' : '') }, msg, link ? [' ', h('a', { href: link.href, target: '_blank', rel: 'noopener', text: link.text })] : null);
    $('#toasts').appendChild(t);
    setTimeout(function () { t.remove(); }, type === 'err' ? 9000 : 4500);
  }

  function setDirty(v) {
    dirty = v;
    var bar = $('.savebar');
    if (bar) {
      bar.classList.toggle('is-dirty', v);
      $('.savebar__msg', bar).textContent = v ? 'Есть несохранённые изменения' : 'Все изменения сохранены';
    }
  }
  window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

  function setTitle(t, actions) {
    $('#pageTitle').textContent = t;
    document.title = t + ' — управление сайтом';
    var a = $('#topActions'); a.innerHTML = '';
    add(a, actions || []);
  }
  function view() { return $('#view'); }
  function loading() { view().innerHTML = ''; view().appendChild(h('p', { class: 'loading', text: 'Загрузка…' })); }
  function openSiteBtn(path, label) { return path ? h('a', { class: 'btn btn--ghost btn--sm', href: siteUrl(path), target: '_blank', rel: 'noopener' }, (label || 'Открыть на сайте') + ' ↗') : null; }

  // ---------- модальные окна ----------
  function modal(title, body, foot, small) {
    var box = h('div', { class: 'modal__box' + (small ? ' modal__box--sm' : '') },
      h('div', { class: 'modal__head' }, h('h3', { text: title }), h('button', { class: 'ibtn', type: 'button', 'aria-label': 'Закрыть', onclick: function () { close(); } }, '✕')),
      h('div', { class: 'modal__body' }, body),
      foot ? h('div', { class: 'modal__foot' }, foot) : null);
    var wrap = h('div', { class: 'modal', onclick: function (e) { if (e.target === wrap) close(); } }, box);
    function onKey(e) { if (e.key === 'Escape') close(); }
    function close() { wrap.remove(); document.removeEventListener('keydown', onKey); }
    document.addEventListener('keydown', onKey);
    document.body.appendChild(wrap);
    return { close: close, box: box };
  }
  function confirmBox(title, text, okLabel, danger) {
    return new Promise(function (resolve) {
      var m;
      var ok = h('button', { class: 'btn ' + (danger ? 'btn--danger' : 'btn--primary'), type: 'button', text: okLabel || 'Да', onclick: function () { m.close(); resolve(true); } });
      m = modal(title, h('p', { text: text }), [h('button', { class: 'btn', type: 'button', text: 'Отмена', onclick: function () { m.close(); resolve(false); } }), ok], true);
      ok.focus();
    });
  }
  function promptBox(title, label, hint, okLabel) {
    return new Promise(function (resolve) {
      var m;
      var inp = h('input', { type: 'text', class: 'inp' });
      var go = function () { var v = inp.value.trim(); if (!v) return inp.focus(); m.close(); resolve(v); };
      inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') go(); });
      m = modal(title, h('label', { class: 'fld' }, h('span', { class: 'fld__label', text: label }), inp, hint ? h('span', { class: 'fld__hint', text: hint }) : null),
        [h('button', { class: 'btn', type: 'button', text: 'Отмена', onclick: function () { m.close(); resolve(null); } }), h('button', { class: 'btn btn--primary', type: 'button', text: okLabel || 'Создать', onclick: go })], true);
      inp.focus();
    });
  }

  // ---------- медиатека: загрузка данных, выбор фото ----------
  function loadPhotos() { return api('photos').then(function (j) { PHOTOS = j; return j; }); }
  function photoById(id) {
    for (var i = 0; i < PHOTOS.cats.length; i++) for (var j = 0; j < PHOTOS.cats[i].photos.length; j++) if (PHOTOS.cats[i].photos[j].id === id) return PHOTOS.cats[i].photos[j];
    return null;
  }
  function catByKey(k) { return PHOTOS.cats.filter(function (c) { return c.key === k; })[0]; }

  function uploadFiles(cat, files, onDone) {
    var list = Array.prototype.filter.call(files, function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type); });
    if (!list.length) { toast('Выберите фото в формате JPG, PNG или WebP', 'err'); return Promise.resolve([]); }
    var label = (catByKey(cat) || {}).label || '';
    var done = [], n = 0;
    toast('Загружаем фото: ' + list.length + ' шт. Не закрывайте страницу…');
    return list.reduce(function (p, f) {
      return p.then(function () { return shrink(f); }).then(function (f2) {
        var fd = new FormData(); fd.append('cat', cat); fd.append('file', f2, f.name); fd.append('alt', label);
        return api('photo/upload', { form: fd }).then(function (j) { done.push(j.photo.id); n++; }).catch(function (e) { toast(f.name + ': ' + e.message, 'err'); });
      });
    }, Promise.resolve()).then(function () {
      if (n) toast('Загружено фото: ' + n + '. Не забудьте поправить подписи — их читают поисковики.');
      return loadPhotos().then(function () { if (onDone) onDone(done); return done; });
    });
  }

  // Крупное фото с телефона уменьшаем в браузере до 2560 px (поворот по EXIF браузер учитывает сам):
  // так загрузка быстрее и не упирается в лимит размера файла на хостинге.
  function shrink(file) {
    if (file.size < 1.5 * 1024 * 1024 || !window.createImageBitmap) return Promise.resolve(file);
    var steps = [[2560, 0.9], [2048, 0.85], [1600, 0.8]];
    return createImageBitmap(file, { imageOrientation: 'from-image' }).then(function (bmp) {
      function attempt(i) {
        var k = Math.min(1, steps[i][0] / Math.max(bmp.width, bmp.height));
        var c = document.createElement('canvas');
        c.width = Math.round(bmp.width * k); c.height = Math.round(bmp.height * k);
        c.getContext('2d').drawImage(bmp, 0, 0, c.width, c.height);
        return new Promise(function (res) { c.toBlob(res, 'image/jpeg', steps[i][1]); }).then(function (b) {
          if (b && b.size > 1.8 * 1024 * 1024 && i < steps.length - 1) return attempt(i + 1);
          return b && b.size < file.size ? b : file;
        });
      }
      return attempt(0);
    }).catch(function () { return file; });
  }

  function fileInput(multiple, onFiles) {
    var inp = h('input', { type: 'file', accept: 'image/jpeg,image/png,image/webp', multiple: multiple, hidden: true });
    inp.addEventListener('change', function () { if (inp.files.length) onFiles(inp.files); inp.value = ''; });
    return inp;
  }

  // Выбор фото: resolve(массив id) или null
  function pickPhotos(opts) {
    return new Promise(function (resolve) {
      var sel = (opts.selected || []).slice();
      var cat = opts.cat || (sel.length && findCat(sel[0])) || PHOTOS.cats[0].key;
      var body = h('div');
      var m;
      function findCat(id) { for (var i = 0; i < PHOTOS.cats.length; i++) if (PHOTOS.cats[i].photos.some(function (p) { return p.id === id; })) return PHOTOS.cats[i].key; return null; }
      function draw() {
        body.innerHTML = '';
        var tabs = h('div', { class: 'tabs' }, PHOTOS.cats.map(function (c) {
          return h('button', { type: 'button', class: 'tab' + (c.key === cat ? ' is-on' : ''), onclick: function () { cat = c.key; draw(); } }, c.label + ' (' + c.photos.length + ')');
        }));
        var c = catByKey(cat);
        var up = fileInput(true, function (files) { uploadFiles(cat, files, function (ids) { if (opts.multi) sel = sel.concat(ids); else if (ids.length) sel = [ids[0]]; draw(); }); });
        var grid = h('div', { class: 'picker' }, c.photos.map(function (p) {
          return h('button', { type: 'button', class: sel.indexOf(p.id) >= 0 ? 'is-on' : '', title: p.alt, onclick: function () {
            if (opts.multi) { var i = sel.indexOf(p.id); if (i >= 0) sel.splice(i, 1); else sel.push(p.id); draw(); }
            else { m.close(); resolve([p.id]); }
          } }, h('img', { src: photoUrl(p.id), alt: '', loading: 'lazy' }), h('span', { text: p.alt }));
        }));
        add(body, [tabs, h('p', {}, h('button', { class: 'btn btn--sm', type: 'button', onclick: function () { up.click(); } }, '+ Загрузить в эту папку'), up),
          c.photos.length ? grid : h('div', { class: 'empty', text: 'В этой папке пока нет фото' })]);
        if (opts.multi) foot.firstChild.textContent = 'Выбрано: ' + sel.length;
      }
      var foot = opts.multi ? [h('span', { class: 'savebar__msg' }), h('button', { class: 'btn', type: 'button', text: 'Отмена', onclick: function () { m.close(); resolve(null); } }), h('button', { class: 'btn btn--primary', type: 'button', text: 'Готово', onclick: function () { m.close(); resolve(sel); } })] : null;
      m = modal(opts.title || 'Выберите фото', body, foot);
      if (foot) foot = m.box.querySelector('.modal__foot');
      draw();
    });
  }

  // =====================================================================
  // ПОЛЯ ФОРМЫ. Каждое поле: { el, get() }
  // =====================================================================
  function wrapFld(fd, control, extra) {
    return h('div', { class: 'fld' },
      fd.label ? h('span', { class: 'fld__label' }, fd.label, extra || null) : null,
      control,
      fd.hint ? h('span', { class: 'fld__hint', text: fd.hint }) : null);
  }

  function autosize(t) { t.style.height = 'auto'; t.style.height = (t.scrollHeight + 2) + 'px'; }

  function textControl(fd, value, onChange) {
    var multi = fd.type === 'textarea';
    var el = multi ? h('textarea', { class: 'inp', rows: fd.rows || 3 }) : h('input', { type: 'text', class: 'inp' });
    el.value = value == null ? '' : value;
    var cnt = fd.counter ? h('span', { class: 'fld__count' }) : null;
    function upd() { if (cnt) { cnt.textContent = el.value.length + ' / ' + fd.counter; cnt.classList.toggle('is-over', el.value.length > fd.counter); } if (multi) autosize(el); }
    el.addEventListener('input', function () { upd(); onChange(); });
    setTimeout(upd, 0);
    return { el: el, cnt: cnt, get: function () { return el.value; } };
  }

  var F = {};
  F.head = function (fd) { return { el: h('div', { class: 'form-head' }, h('h2', { text: fd.label }), fd.hint ? h('p', { text: fd.hint }) : null) }; };
  F.text = F.textarea = F.url = F.email = function (fd, v, ch) { var c = textControl(fd, v, ch); return { el: wrapFld(fd, c.el, c.cnt), get: c.get }; };
  F.number = function (fd, v, ch) {
    var el = h('input', { type: 'number', class: 'inp', style: 'max-width:180px' }); el.value = v == null ? '' : v;
    el.addEventListener('input', ch);
    return { el: wrapFld(fd, el), get: function () { return el.value === '' ? null : Number(el.value); } };
  };
  F.bool = function (fd, v, ch) {
    var cb = h('input', { type: 'checkbox' }); cb.checked = !!v; cb.addEventListener('change', ch);
    return { el: h('div', { class: 'fld' }, h('label', { class: 'check' }, cb, h('span', {}, h('b', { text: fd.label }), fd.hint ? h('span', { class: 'fld__hint', text: fd.hint }) : null))), get: function () { return cb.checked; } };
  };

  // Список строк с кнопками «вверх/вниз/удалить»
  F.list = function (fd, v, ch) {
    var box = h('div', { class: 'lst' });
    var rows = [];
    function row(val) {
      var c = textControl({ type: fd.multiline ? 'textarea' : 'text', rows: 2 }, val, ch);
      var r = { el: null, c: c };
      r.el = h('div', { class: 'lst__row' }, c.el, h('div', { class: 'lst__tools' },
        h('button', { class: 'ibtn', type: 'button', title: 'Выше', onclick: function () { move(r, -1); } }, '↑'),
        h('button', { class: 'ibtn', type: 'button', title: 'Ниже', onclick: function () { move(r, 1); } }, '↓'),
        h('button', { class: 'ibtn ibtn--del', type: 'button', title: 'Удалить', onclick: function () { rows.splice(rows.indexOf(r), 1); draw(); ch(); } }, '✕')));
      return r;
    }
    function move(r, d) { var i = rows.indexOf(r), j = i + d; if (j < 0 || j >= rows.length) return; rows.splice(i, 1); rows.splice(j, 0, r); draw(); ch(); }
    function draw() { box.innerHTML = ''; rows.forEach(function (r) { box.appendChild(r.el); }); box.appendChild(addBtn); }
    var addBtn = h('button', { class: 'add', type: 'button', text: '+ Добавить пункт', onclick: function () { var r = row(''); rows.push(r); draw(); r.c.el.focus(); ch(); } });
    (v || []).forEach(function (x) { rows.push(row(x)); });
    draw();
    return { el: wrapFld(fd, box), get: function () { return rows.map(function (r) { return r.c.get(); }); } };
  };

  // Список карточек с вложенными полями (шаги, вопросы, отзывы…)
  F.items = function (fd, v, ch) {
    var box = h('div', { class: 'items' });
    var list = [];
    function summary(val) {
      for (var i = 0; i < fd.item.length; i++) { var s = fd.item[i]; if ((s.type === 'text' || s.type === 'textarea') && val[s.key]) return String(val[s.key]); }
      return '';
    }
    function card(val, open) {
      var subs = fd.item.map(function (s) { return { key: s.key, f: field(s, val[s.key], function () { titleUpd(); ch(); }) }; });
      var title = h('div', { class: 'item__title item__toggle' });
      var it = { subs: subs };
      function titleUpd() { var cur = it.get(); var i = list.indexOf(it) + 1; title.textContent = ''; add(title, [(fd.itemName || 'Элемент') + ' ' + i, h('small', { text: summary(cur).slice(0, 80) })]); }
      it.get = function () { var o = clone(val); subs.forEach(function (x) { o[x.key] = x.f.get(); }); return o; }; // поля вне формы сохраняются
      it.el = h('div', { class: 'item' + (fd.collapsed && !open ? ' is-collapsed' : '') },
        h('div', { class: 'item__head' }, title,
          h('button', { class: 'ibtn', type: 'button', title: 'Выше', onclick: function () { move(it, -1); } }, '↑'),
          h('button', { class: 'ibtn', type: 'button', title: 'Ниже', onclick: function () { move(it, 1); } }, '↓'),
          h('button', { class: 'ibtn ibtn--del', type: 'button', title: 'Удалить', onclick: function () {
            confirmBox('Удалить?', 'Удалить «' + ((fd.itemName || 'элемент') + ' ' + (list.indexOf(it) + 1)) + '»? Изменение вступит в силу после сохранения.', 'Удалить', true).then(function (ok) { if (ok) { list.splice(list.indexOf(it), 1); draw(); ch(); } });
          } }, '✕')),
        h('div', { class: 'item__body' }, subs.map(function (x) { return x.f.el; })));
      title.addEventListener('click', function () { it.el.classList.toggle('is-collapsed'); it.el.querySelectorAll('textarea').forEach(autosize); }); // высота полей считается, только когда карточка видна
      it.titleUpd = titleUpd;
      return it;
    }
    function move(it, d) { var i = list.indexOf(it), j = i + d; if (j < 0 || j >= list.length) return; list.splice(i, 1); list.splice(j, 0, it); draw(); ch(); }
    function draw() { box.innerHTML = ''; list.forEach(function (it) { box.appendChild(it.el); it.titleUpd(); }); box.appendChild(addBtn); }
    var addBtn = h('button', { class: 'add', type: 'button', text: '+ Добавить: ' + (fd.itemName || 'элемент').toLowerCase(), onclick: function () {
      var blank = {}; fd.item.forEach(function (s) { blank[s.key] = (s.type === 'items' || s.type === 'list') ? [] : s.type === 'bool' ? false : ''; });
      var it = card(blank, true); list.push(it); draw(); ch();
      var f = it.el.querySelector('input,textarea'); if (f) f.focus();
    } });
    (v || []).forEach(function (x) { list.push(card(x, false)); });
    draw();
    return { el: wrapFld(fd, box), get: function () { return list.map(function (it) { return it.get(); }); } };
  };

  F.icon = function (fd, v, ch) {
    var cur = v || '';
    var box = h('div', { class: 'icons' });
    Object.keys(BOOT.icons).forEach(function (k) {
      var b = h('button', { type: 'button', title: BOOT.icons[k], class: k === cur ? 'is-on' : '', onclick: function () {
        cur = k; Array.prototype.forEach.call(box.children, function (x) { x.classList.remove('is-on'); }); b.classList.add('is-on'); ch();
      } }, icon(k));
      box.appendChild(b);
    });
    return { el: wrapFld(fd, box), get: function () { return cur; } };
  };

  F.photo = function (fd, v, ch) {
    var cur = v || '';
    var img = h('img', { class: 'thumb', alt: '' });
    function upd() { img.src = cur ? photoUrl(cur) : ''; img.style.visibility = cur ? '' : 'hidden'; }
    upd();
    var box = h('div', { class: 'pick' }, img, h('button', { class: 'btn btn--sm', type: 'button', text: 'Выбрать фото', onclick: function () {
      pickPhotos({ selected: cur ? [cur] : [] }).then(function (ids) { if (ids && ids[0]) { cur = ids[0]; upd(); ch(); } });
    } }));
    return { el: wrapFld(fd, box), get: function () { return cur; } };
  };

  F.photos = function (fd, v, ch) {
    var ids = (v || []).slice();
    var box = h('div');
    function draw() {
      box.innerHTML = '';
      var gal = h('div', { class: 'gal' }, ids.map(function (id, i) {
        var p = photoById(id);
        var cat = !p && catByKey(id);
        return h('div', { class: 'gal__it' },
          p ? h('img', { class: 'thumb', src: photoUrl(id), alt: p.alt, title: p.alt, loading: 'lazy' }) : h('div', { class: 'thumb', style: 'display:grid;place-items:center;font-size:13px;color:#6b7078;text-align:center;padding:6px' }, cat ? 'Вся папка «' + cat.label + '»' : 'нет фото ' + id),
          h('div', { class: 'gal__tools' },
            h('button', { class: 'ibtn', type: 'button', title: 'Раньше', disabled: i === 0, onclick: function () { ids.splice(i - 1, 0, ids.splice(i, 1)[0]); draw(); ch(); } }, '←'),
            h('button', { class: 'ibtn', type: 'button', title: 'Позже', disabled: i === ids.length - 1, onclick: function () { ids.splice(i + 1, 0, ids.splice(i, 1)[0]); draw(); ch(); } }, '→'),
            h('button', { class: 'ibtn ibtn--del', type: 'button', title: 'Убрать', onclick: function () { ids.splice(i, 1); draw(); ch(); } }, '✕')));
      }));
      add(box, [ids.length ? gal : null, h('button', { class: 'add', type: 'button', text: '+ Добавить фото', onclick: function () {
        pickPhotos({ multi: true, selected: ids.filter(photoById), title: 'Отметьте фото' }).then(function (sel) {
          if (!sel) return;
          var keepCats = ids.filter(function (x) { return !photoById(x); });
          ids = keepCats.concat(sel); draw(); ch();
        });
      } })]);
    }
    draw();
    return { el: wrapFld(fd, box), get: function () { return ids.slice(); } };
  };

  // Выбор из фиксированного списка (options: {значение: подпись})
  F.choice = function (fd, v, ch) {
    var s = h('select', { class: 'sel' }, !v ? h('option', { value: '', text: '— выберите —' }) : null,
      Object.keys(fd.options).map(function (k) { return h('option', { value: k, text: fd.options[k] }); }));
    s.value = v || ''; s.addEventListener('change', ch);
    return { el: wrapFld(fd, s), get: function () { return s.value; } };
  };

  // Свидетельство из раздела «Документы» (по номеру)
  F.doc = function (fd, v, ch) {
    var s = h('select', { class: 'sel' }, h('option', { value: '', text: '— без свидетельства —' }),
      BOOT.docs.map(function (d) { return h('option', { value: d.num, text: '№ ' + d.num + ' — ' + d.name }); }));
    if (v && !BOOT.docs.some(function (d) { return d.num === v; })) s.appendChild(h('option', { value: v, text: '№ ' + v + ' (нет в «Документах»)' }));
    s.value = v || ''; s.addEventListener('change', ch);
    return { el: wrapFld(fd, s), get: function () { return s.value; } };
  };

  // Файл PDF из папки документов: выбор, загрузка нового
  F.pdf = function (fd, v, ch) {
    var cur = v || '';
    var name = h('span', { class: 'inp', style: 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap' });
    var open = h('a', { class: 'btn btn--sm btn--ghost', target: '_blank', rel: 'noopener', text: 'Открыть ↗' });
    function upd() { name.textContent = cur || 'файл не выбран'; open.href = cur ? pdfUrl(cur) : '#'; open.hidden = !cur; }
    upd();
    var box = h('div', { class: 'pick', style: 'flex-wrap:wrap' }, name, open, h('button', { class: 'btn btn--sm', type: 'button', text: 'Выбрать или загрузить', onclick: function () {
      pickPdf(cur).then(function (n) { if (n) { cur = n; upd(); ch(); } });
    } }));
    return { el: wrapFld(fd, box), get: function () { return cur; } };
  };

  function pdfInput(onFile) {
    var inp = h('input', { type: 'file', accept: 'application/pdf,.pdf', hidden: true });
    inp.addEventListener('change', function () { if (inp.files.length) onFile(inp.files[0]); inp.value = ''; });
    return inp;
  }
  function uploadPdf(file) {
    var fd = new FormData(); fd.append('file', file, file.name);
    toast('Загружаем ' + file.name + '…');
    return api('pdf/upload', { form: fd }).then(function (j) { toast(j.message + ': ' + j.name); return j.name; });
  }
  function kb(n) { return n > 1048576 ? (n / 1048576).toFixed(1) + ' МБ' : Math.max(1, Math.round(n / 1024)) + ' КБ'; }

  // Окно выбора PDF: resolve(имя файла) или null
  function pickPdf(selected) {
    return new Promise(function (resolve) {
      var body = h('div'), m;
      function draw() {
        api('pdfs').then(function (d) {
          body.innerHTML = '';
          var up = pdfInput(function (file) { uploadPdf(file).then(function (n) { m.close(); resolve(n); }).catch(function (e) { toast(e.message, 'err'); }); });
          add(body, [h('p', {}, h('button', { class: 'btn btn--sm btn--primary', type: 'button', onclick: function () { up.click(); } }, '+ Загрузить новый PDF'), up),
            h('div', { class: 'rows' }, d.items.map(function (p) {
              return h('div', { class: 'row' + (p.name === selected ? ' is-on' : '') },
                h('span', { class: 'row__ic' }, icon('doc')),
                h('div', { class: 'row__main' }, h('b', { text: p.name }), h('p', { text: kb(p.size) + (p.uses.length ? ' · используется: ' + p.uses.join(', ') : ' · нигде не используется') })),
                h('div', { class: 'row__tools' },
                  h('a', { class: 'btn btn--sm btn--ghost', href: pdfUrl(p.name), target: '_blank', rel: 'noopener', text: 'Открыть ↗' }),
                  h('button', { class: 'btn btn--sm', type: 'button', text: 'Выбрать', onclick: function () { m.close(); resolve(p.name); } })));
            }))]);
        }).catch(function (e) { toast(e.message, 'err'); });
      }
      m = modal('Файл PDF', body);
      draw();
    });
  }

  function field(fd, value, onChange) { return (F[fd.type] || F.text)(fd, value, onChange); }

  // Форма раздела: поля + нижняя панель «Сохранить»
  function buildForm(fields, values, onSave) {
    var ctl = [];
    var form = h('div', { class: 'form' });
    fields.forEach(function (fd) {
      var f = field(fd, fd.bind ? values[fd.bind] : null, function () { setDirty(true); });
      if (fd.bind) ctl.push({ bind: fd.bind, f: f });
      form.appendChild(f.el);
    });
    var btn = h('button', { class: 'btn btn--primary', type: 'button', text: 'Сохранить' });
    var bar = h('div', { class: 'savebar' }, h('span', { class: 'savebar__msg', text: 'Все изменения сохранены' }), btn);
    function collect() { var o = {}; ctl.forEach(function (c) { o[c.bind] = c.f.get(); }); return o; }
    function save() {
      if (saving) return;
      saving = true; btn.disabled = true; btn.textContent = 'Сохраняем…';
      onSave(collect()).then(function () { setDirty(false); }).catch(function (e) { toast(e.message, 'err'); })
        .then(function () { saving = false; btn.disabled = false; btn.textContent = 'Сохранить'; });
    }
    btn.addEventListener('click', save);
    formCtl = { save: save };
    return h('div', {}, form, bar);
  }
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'ы')) { if (formCtl && $('.savebar')) { e.preventDefault(); formCtl.save(); } }
  });

  // =====================================================================
  // ЭКРАНЫ
  // =====================================================================
  var routes = {};

  routes[''] = function () {
    setTitle('Управление сайтом');
    var tile = function (href, ic, title, text) { return h('a', { class: 'tile', href: href }, icon(ic), h('b', { text: title }), h('span', { text: text })); };
    var sec = function (id) { return BOOT.nav.filter(function (n) { return n.id === id; })[0] || { title: id }; };
    view().appendChild(h('div', {},
      h('p', { class: 'lead-p', text: 'Выберите, что хотите изменить. После нажатия «Сохранить» сайт обновляется сразу.' }),
      h('div', { class: 'tiles' },
        tile('#/section/hero', 'home', 'Первый экран', 'Заголовок, кнопки, фото, подписи к цифрам'),
        tile('#/section/programs', 'layers', 'Направления', 'Программы: описания, иконки, свидетельства'),
        tile('#/section/documents', 'doc', 'Документы', 'Свидетельства, сертификат ИСО, выписка СРО'),
        tile('#/section/calc', 'layers', 'Калькулятор', 'Цены каркаса, покрытия и систем за полублок'),
        tile('#/leads', 'mail', 'Заявки', BOOT.leads ? 'Всего заявок: ' + BOOT.leads : 'Заявки с формы сайта'),
        tile('#/section/company', 'phone', 'Компания и контакты', 'Телефоны, почта, реквизиты — меняются на всём сайте'),
        tile('#/photos', 'zoom', 'Фото', 'Загрузить фото, подписи, папки')),
      h('h2', { class: 'sec', text: 'Другие разделы главной' }),
      h('div', { class: 'tiles' }, ['robot', 'greenhouse', 'gardens', 'audience', 'steps', 'lead', 'menu', 'seo'].map(function (id) { return tile('#/section/' + id, 'check', sec(id).title, ''); })),
      h('h2', { class: 'sec', text: 'Если что-то пошло не так' }),
      h('div', { class: 'tiles' },
        tile('#/history', 'clock', 'История правок', 'Вернуть любую прежнюю версию'),
        tile('#/trash', 'trash', 'Корзина', 'Удалённые фото и PDF можно вернуть'))));
  };
  routes.section = function (id) {
    return Promise.all([api('section?id=' + encodeURIComponent(id)), PHOTOS || loadPhotos()]).then(function (r) {
      var d = r[0];
      setTitle(d.title, [openSiteBtn(d.url)]);
      view().innerHTML = '';
      view().appendChild(buildForm(d.fields, d.values, function (values) {
        return api('section', { body: { id: id, values: values } }).then(function (j) {
          toast(j.message, null, { href: siteUrl(d.url), text: 'Посмотреть ↗' });
          if (id === 'company' || id === 'documents') return refreshBoot().then(function () { rerender(); });
        });
      }));
    });
  };

  routes.pdfs = function () {
    return api('pdfs').then(function (d) {
      var up = pdfInput(function (file) { uploadPdf(file).then(function () { routes.pdfs(); }).catch(function (e) { toast(e.message, 'err'); }); });
      setTitle('Файлы PDF', [h('button', { class: 'btn btn--primary btn--sm', type: 'button', text: '+ Загрузить PDF', onclick: function () { up.click(); } }), up]);
      view().innerHTML = '';
      add(view(), [h('p', { class: 'lead-p', text: 'PDF свидетельств и сертификатов, которые открываются с сайта. Какой файл показать у документа — выбирается в разделе «Документы». Файл, который где-то используется, удалить нельзя.' }),
        d.items.length ? h('div', { class: 'rows' }, d.items.map(function (p) {
          return h('div', { class: 'row' },
            h('span', { class: 'row__ic' }, icon('doc')),
            h('div', { class: 'row__main' }, h('a', { href: pdfUrl(p.name), target: '_blank', rel: 'noopener', text: p.name }), h('p', { text: kb(p.size) + ' · ' + p.date + (p.uses.length ? ' · используется: ' + p.uses.join(', ') : ' · нигде не используется') })),
            h('div', { class: 'row__tools' }, p.uses.length ? null : h('button', { class: 'ibtn ibtn--del', type: 'button', title: 'Удалить', onclick: function () {
              confirmBox('Удалить файл?', p.name + ' переместится в корзину — его можно будет вернуть.', 'Удалить', true).then(function (ok) {
                if (ok) api('pdf/delete', { body: { name: p.name } }).then(function (j) { toast(j.message); refreshBoot().then(function () { routes.pdfs(); }); }).catch(function (e) { toast(e.message, 'err'); });
              });
            } }, '✕')));
        })) : h('div', { class: 'empty', text: 'Файлов пока нет.' })]);
    });
  };

  routes.photos = function (catKey) {
    return loadPhotos().then(function () {
      var cat = catByKey(catKey) ? catKey : PHOTOS.cats[0].key;
      setTitle('Фото', [h('button', { class: 'btn btn--sm', type: 'button', text: '+ Новая папка', onclick: function () {
        promptBox('Новая папка для фото', 'Название папки', 'Например: Демонтаж', 'Создать').then(function (label) {
          if (!label) return;
          api('photo/category', { body: { label: label } }).then(function (j) { toast(j.message); skipHash = true; location.hash = '#/photos/' + j.key; routes.photos(j.key); }).catch(function (e) { toast(e.message, 'err'); });
        });
      } })]);
      var c = catByKey(cat);
      var tabs = h('div', { class: 'tabs' }, PHOTOS.cats.map(function (x) { return h('a', { class: 'tab' + (x.key === cat ? ' is-on' : ''), href: '#/photos/' + x.key, style: 'text-decoration:none' }, x.label + ' (' + x.photos.length + ')'); }));
      var inp = fileInput(true, function (files) { uploadFiles(cat, files, function () { routes.photos(cat); }); });
      var drop = h('div', { class: 'drop', onclick: function () { inp.click(); } }, h('b', { text: 'Перетащите фото сюда' }), ' или нажмите, чтобы выбрать. JPG, PNG или WebP — сайт сам уменьшит и сожмёт их.', inp);
      ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-over'); }); });
      ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-over'); }); });
      drop.addEventListener('drop', function (e) { if (e.dataTransfer.files.length) uploadFiles(cat, e.dataTransfer.files, function () { routes.photos(cat); }); });

      var grid = h('div', { class: 'ph' }, c.photos.map(function (p, i) {
        var alt = h('textarea', { rows: 3, title: 'Подпись к фото' }); alt.value = p.alt;
        var sel = h('select', {}, PHOTOS.cats.map(function (x) { return h('option', { value: x.key, text: x.label }); })); sel.value = cat;
        var saveBtn = h('button', { class: 'btn btn--sm btn--primary', type: 'button', text: 'Сохранить', hidden: true });
        function changed() { saveBtn.hidden = false; }
        alt.addEventListener('input', changed); sel.addEventListener('change', changed);
        saveBtn.addEventListener('click', function () {
          api('photo/update', { body: { id: p.id, alt: alt.value, cat: sel.value } }).then(function (j) { toast(j.message); routes.photos(cat); }).catch(function (e) { toast(e.message, 'err'); });
        });
        function move(d) { api('photo/move', { body: { id: p.id, dir: d } }).then(function () { routes.photos(cat); }).catch(function (e) { toast(e.message, 'err'); }); }
        return h('div', { class: 'ph__it' },
          h('a', { href: photoUrl(p.id, false), target: '_blank', rel: 'noopener', title: 'Открыть в полном размере' }, h('img', { src: photoUrl(p.id), alt: p.alt, loading: 'lazy' })),
          h('div', { class: 'ph__b' }, alt,
            h('label', {}, 'Папка: ', sel),
            h('div', { class: 'ph__tools' },
              h('button', { class: 'ibtn', type: 'button', title: 'Раньше', disabled: i === 0, onclick: function () { move(-1); } }, '←'),
              h('button', { class: 'ibtn', type: 'button', title: 'Позже', disabled: i === c.photos.length - 1, onclick: function () { move(1); } }, '→'),
              h('span', { class: 'sp' }), saveBtn,
              h('button', { class: 'ibtn ibtn--del', type: 'button', title: 'Удалить', onclick: function () {
                confirmBox('Удалить фото?', 'Фото уберётся с сайта и переместится в корзину — его можно будет вернуть.', 'Удалить', true).then(function (ok) {
                  if (ok) api('photo/delete', { body: { id: p.id } }).then(function (j) { toast(j.message); routes.photos(cat); }).catch(function (e) { toast(e.message, 'err'); });
                });
              } }, '✕'))));
      }));
      view().innerHTML = '';
      add(view(), [h('p', { class: 'lead-p', text: 'Фото разложены по папкам. Где показать фото, выбирается в разделах сайта («Первый экран», «Документы»). Подпись к фото видят поисковики и незрячие посетители — пишите, что на фото.' }), tabs, drop,
        c.photos.length ? grid : h('div', { class: 'empty', text: 'В этой папке пока нет фото' })]);
    });
  };

  routes.leads = function () {
    return api('leads').then(function (d) {
      setTitle('Заявки');
      view().innerHTML = '';
      if (!d.items.length) return view().appendChild(h('div', { class: 'empty', text: 'Заявок пока нет. Они появятся здесь, как только кто-то отправит форму на сайте.' }));
      var gray = 'color:#6b7078;font-size:13px';
      view().appendChild(h('div', {}, h('p', { class: 'lead-p', text: 'Все заявки с формы сайта, новые сверху. Куда ещё отправлять заявки — в разделе «Настройки».' }),
        h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl' },
          h('thead', {}, h('tr', {}, ['Когда', 'Телефон и имя', 'Что нужно', 'Откуда'].map(function (t) { return h('th', { text: t }); }))),
          h('tbody', {}, d.items.map(function (l) {
            var utm = l.utm || {};
            return h('tr', {},
              h('td', { text: l.date, style: 'white-space:nowrap' }),
              h('td', {}, h('a', { href: 'tel:' + String(l.phone || '').replace(/[^\d+]/g, ''), text: l.phone, style: 'font-weight:700;white-space:nowrap' }), l.name ? [h('br'), l.name] : null),
              h('td', {}, l.topic ? h('div', { text: l.topic, style: 'font-weight:600' }) : null, l.crop ? h('div', { text: 'Культура и площадь: ' + l.crop }) : null,
                l.message ? h('div', { text: l.message, style: 'color:#6b7078;white-space:pre-wrap' }) : null,
                l.calc ? h('div', { text: 'Расчёт на сайте: ' + l.calc, style: 'margin-top:4px;padding:6px 8px;border-radius:6px;background:#eef6e6;font-size:13px' }) : null, l.muted ? h('span', { class: 'tag tag--warn', text: 'уведомление не отправлялось: много заявок с одного адреса' }) : null),
              h('td', { style: gray }, Object.keys(utm).map(function (k) { return h('div', { text: k + ': ' + utm[k] }); }),
                l.referrer ? h('div', { text: 'пришёл с: ' + l.referrer, style: 'word-break:break-all' }) : null,
                l.page ? h('a', { href: l.page, target: '_blank', rel: 'noopener', text: 'страница ↗' }) : null));
          }))))));
    });
  };

  routes.history = function () {
    return api('revisions').then(function (d) {
      setTitle('История правок');
      view().innerHTML = '';
      if (!d.items.length) return view().appendChild(h('div', { class: 'empty', text: 'Правок пока не было.' }));
      view().appendChild(h('div', {}, h('p', { class: 'lead-p', text: 'При каждом сохранении прежняя версия откладывается сюда. «Вернуть» восстанавливает раздел таким, каким он был перед этой правкой. Текущая версия при этом тоже сохранится в истории.' }),
        h('div', { class: 'tbl-wrap' }, h('table', { class: 'tbl' },
          h('thead', {}, h('tr', {}, h('th', { text: 'Когда сохранили' }), h('th', { text: 'Что' }), h('th'))),
          h('tbody', {}, d.items.map(function (it) {
            return h('tr', {}, h('td', { text: it.date, style: 'white-space:nowrap' }), h('td', { text: it.label }), h('td', { style: 'text-align:right' }, h('button', { class: 'btn btn--sm', type: 'button', text: 'Вернуть как было до этого', onclick: function () {
              confirmBox('Вернуть прежнюю версию?', '«' + it.label + '» станет таким, каким было перед сохранением ' + it.date + '. Сайт обновится сразу.', 'Вернуть').then(function (ok) {
                if (ok) api('revision/restore', { body: { id: it.id } }).then(function (j) { toast(j.message); refreshBoot().then(rerender); }).catch(function (e) { toast(e.message, 'err'); });
              });
            } })));
          }))))));
    });
  };

  routes.trash = function () {
    return api('trash').then(function (d) {
      setTitle('Корзина');
      view().innerHTML = '';
      if (!d.items.length) return view().appendChild(h('div', { class: 'empty', text: 'Корзина пуста.' }));
      view().appendChild(h('div', {}, h('p', { class: 'lead-p', text: 'Удалённые фото и файлы PDF хранятся здесь без срока — их можно вернуть в любой момент.' }),
        h('div', { class: 'rows' }, d.items.map(function (it) {
          return h('div', { class: 'row' },
            h('span', { class: 'row__ic' }, icon(it.type === 'photo' ? 'zoom' : 'doc')),
            h('div', { class: 'row__main' }, h('b', { text: (it.type === 'photo' ? 'Фото: ' : 'PDF: ') + it.title }), h('p', { text: 'Удалено ' + it.date.replace('T', ' ').slice(0, 16) })),
            h('div', { class: 'row__tools' }, h('button', { class: 'btn btn--sm', type: 'button', text: 'Вернуть', onclick: function () {
              api('trash/restore', { body: { id: it.id } }).then(function (j) { toast(j.message); refreshBoot().then(rerender); }).catch(function (e) { toast(e.message, 'err'); });
            } })));
        }))));
    });
  };

  routes.settings = function () {
    return api('settings').then(function (d) {
      setTitle('Настройки');
      var s = d.settings;
      var emails = F.list({ label: 'Почта для заявок', hint: 'Пусто — заявки уходят на почту из раздела «Компания и контакты»: ' + d.companyEmail }, s.leadEmails, function () {});
      var token = textControl({ type: 'text' }, s.tgToken, function () {});
      var chats = F.list({ label: 'Кому в Telegram (Chat ID)', hint: 'Число: личный чат или группа. Узнать свой ID можно у бота @userinfobot. Боту нужно сначала написать /start.' }, s.tgChats, function () {});
      var from = textControl({ type: 'text' }, s.mailFrom, function () {});
      var cur = h('input', { type: 'password', class: 'inp', autocomplete: 'current-password' });
      var nw = h('input', { type: 'password', class: 'inp', autocomplete: 'new-password' });
      view().innerHTML = '';
      add(view(), [
        h('div', { class: 'form' },
          h('div', { class: 'form-head' }, h('h2', { text: 'Куда приходят заявки' }), h('p', { text: 'Каждая заявка с сайта сохраняется в разделе «Заявки» и дополнительно отправляется сюда.' })),
          emails.el,
          wrapFld({ label: 'Токен Telegram-бота', hint: 'Создайте бота у @BotFather и вставьте токен. Пусто — в Telegram не отправлять.' }, token.el),
          chats.el,
          wrapFld({ label: 'Адрес отправителя писем', hint: 'Необязательно. Лучше указать ящик на домене сайта, иначе письма могут попадать в спам.' }, from.el),
          h('div', { style: 'display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px' },
            h('button', { class: 'btn btn--primary', type: 'button', text: 'Сохранить', onclick: function () {
              api('settings', { body: { leadEmails: emails.get(), tgToken: token.get(), tgChats: chats.get(), mailFrom: from.get() } }).then(function (j) { toast(j.message); }).catch(function (e) { toast(e.message, 'err'); });
            } }),
            h('button', { class: 'btn', type: 'button', text: 'Отправить проверочную заявку', onclick: function () {
              api('settings/test-lead', { body: {} }).then(function (j) {
                var r = j.result || {}, parts = [];
                if ('mail' in r) parts.push('почта: ' + (r.mail ? 'отправлено' : 'не отправилось'));
                if (r.tg) Object.keys(r.tg).forEach(function (k) { parts.push('Telegram ' + k + ': ' + (r.tg[k] ? 'доставлено' : 'ошибка')); });
                toast(parts.length ? parts.join(', ') : 'Некуда отправлять: не указаны почта и Telegram', parts.join().indexOf('ошибка') >= 0 || parts.join().indexOf('не отправ') >= 0 ? 'err' : null);
              }).catch(function (e) { toast(e.message, 'err'); });
            } }))),
        h('div', { class: 'form', style: 'margin-top:18px' },
          h('div', { class: 'form-head' }, h('h2', { text: 'Вход в админку' }), h('p', {}, 'Адрес входа: ', h('code', { text: d.secretUrl }), '. Сохраните его в закладки — без него админка не открывается.')),
          wrapFld({ label: 'Текущий пароль' }, cur),
          wrapFld({ label: 'Новый пароль', hint: 'Не короче 8 символов' }, nw),
          h('div', { style: 'margin-bottom:18px' }, h('button', { class: 'btn', type: 'button', text: 'Сменить пароль', onclick: function () {
            api('settings/password', { body: { current: cur.value, password: nw.value } }).then(function (j) { toast(j.message); cur.value = nw.value = ''; }).catch(function (e) { toast(e.message, 'err'); });
          } })))]);
    });
  };

  // ---------- навигация ----------
  function drawNav(active) {
    var nav = $('#nav'); nav.innerHTML = '';
    var groups = {};
    var link = function (href, title, badge) { return h('a', { href: href, class: href === active ? 'is-active' : '' }, h('span', { text: title }), badge ? h('span', { class: 'badge', text: String(badge) }) : null); };
    nav.appendChild(link('#/', 'Обзор'));
    BOOT.nav.forEach(function (n) { (groups[n.group] = groups[n.group] || []).push(n); });
    Object.keys(groups).forEach(function (g) {
      nav.appendChild(h('div', { class: 'side__group', text: g }));
      groups[g].forEach(function (n) {
        nav.appendChild(link('#/section/' + n.id, n.title));
      });
      if (g === 'Общее') { nav.appendChild(link('#/photos', 'Фото')); nav.appendChild(link('#/pdfs', 'Файлы PDF')); }
    });
    nav.appendChild(h('div', { class: 'side__group', text: 'Служебное' }));
    nav.appendChild(link('#/leads', 'Заявки', BOOT.leads));
    nav.appendChild(link('#/history', 'История правок'));
    nav.appendChild(link('#/trash', 'Корзина', BOOT.trash));
    nav.appendChild(link('#/settings', 'Настройки'));
  }

  function refreshBoot() { return api('bootstrap').then(function (b) { BOOT = b; drawNav(currentNavHref()); }); }
  function currentNavHref() {
    var p = location.hash.replace(/^#\/?/, '').split('/');
    if (p[0] === 'photos') return '#/photos';
    return '#/' + p.filter(Boolean).join('/');
  }

  var lastHash = location.hash;
  function rerender() {
    var y = window.scrollY;
    render().then(function () { window.scrollTo(0, y); });
  }
  function render() {
    formCtl = null;
    var p = location.hash.replace(/^#\/?/, '').split('/').map(decodeURIComponent);
    var fn = routes[p[0]] || routes[''];
    drawNav(currentNavHref());
    $('#side').classList.remove('is-open');
    loading();
    setDirty(false);
    return Promise.resolve().then(function () { view().innerHTML = ''; return fn(p[1]); }).catch(function (e) {
      view().innerHTML = ''; view().appendChild(h('p', { class: 'alert alert--err', text: e.message }));
    });
  }
  window.addEventListener('hashchange', function () {
    if (skipHash) { skipHash = false; lastHash = location.hash; return; }
    if (dirty && !window.confirm('Есть несохранённые изменения. Уйти без сохранения?')) { skipHash = true; location.hash = lastHash; return; }
    lastHash = location.hash;
    window.scrollTo(0, 0);
    render();
  });
  $('#menuBtn').addEventListener('click', function () { $('#side').classList.toggle('is-open'); });

  refreshBoot().then(render).catch(function (e) { view().appendChild(h('p', { class: 'alert alert--err', text: e.message })); });
})();
