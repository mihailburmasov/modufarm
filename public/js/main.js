(function () {
  'use strict';

  // Яндекс Метрика: ЗАПОЛНИТЬ номер счётчика. Пока 0 — счётчик не подключается.
  // Когда включите — обновите текст cookie-баннера и политику (раздел 6).
  var METRIKA_ID = 0;
  // Куда отправлять заявки: обработчик на своём хостинге (заявки видны в админке, уходят на почту и в Telegram)
  var FORM_ENDPOINT = 'lead.php';

  document.documentElement.classList.add('js');

  function goal(name) {
    if (METRIKA_ID && window.ym) window.ym(METRIKA_ID, 'reachGoal', name);
  }

  if (METRIKA_ID) {
    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
      m[i].l = 1 * new Date();
      k = e.createElement(t); a = e.getElementsByTagName(t)[0];
      k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');
    window.ym(METRIKA_ID, 'init', { clickmap: true, trackLinks: true, accurateTrackBounce: true, webvisor: true });
  }

  var startTime = Date.now();

  // Год в подвале
  document.querySelectorAll('.js-year').forEach(function (el) { el.textContent = new Date().getFullYear(); });

  // Шапка: фон при прокрутке, мобильное меню
  var header = document.querySelector('[data-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 20); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });

    var burger = header.querySelector('[data-burger]');
    var nav = header.querySelector('[data-nav]');
    var setOpen = function (open) {
      header.classList.toggle('is-open', open);
      burger.setAttribute('aria-expanded', String(open));
      burger.setAttribute('aria-label', open ? 'Закрыть меню' : 'Открыть меню');
    };
    if (burger && nav) {
      burger.addEventListener('click', function () { setOpen(!header.classList.contains('is-open')); });
      nav.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
    }
  }

  // Отметить тему заявки во всех формах страницы (в блоке внизу и во всплывающем окне)
  function setTopic(topic) {
    if (!topic) return;
    document.querySelectorAll('input[name="topic"]').forEach(function (input) {
      if (input.value === topic) input.checked = true;
    });
  }

  // Всплывающее окно с формой: кнопки «Обсудить теплицу», «Заказать проект сада» и т. п. открывают форму
  // на том же экране. Пункты меню («Контакты») по-прежнему ведут к блоку заявки внизу страницы.
  // Если браузер не умеет <dialog>, кнопка работает как ссылка на блок заявки.
  var modal = document.querySelector('[data-modal]');
  var canModal = modal && typeof modal.showModal === 'function';
  if (canModal) {
    var opener = null;
    var closeModal = function () { modal.close(); };
    modal.addEventListener('close', function () {
      document.documentElement.classList.remove('modal-open');
      if (opener) { opener.focus({ preventScroll: true }); opener = null; }
    });
    // Клик по затемнённому фону закрывает окно
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    modal.querySelector('[data-modal-close]').addEventListener('click', closeModal);
  }
  // Расчёт из калькулятора уходит с заявкой (скрытое поле calc) и виден над полями формы.
  // Кнопки, кроме кнопки калькулятора, расчёт убирают — чтобы он не прилип к заявке на другую тему.
  var calcSummary = null; // задаёт калькулятор ниже
  function setCalc(text) {
    document.querySelectorAll('[data-calc-field]').forEach(function (input) { input.value = text; });
    document.querySelectorAll('[data-calc-view]').forEach(function (box) {
      box.hidden = !text;
      box.querySelector('[data-calc-text]').textContent = text;
    });
  }

  document.querySelectorAll('a.btn[href="#zayavka"]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      setTopic(btn.getAttribute('data-topic'));
      setCalc(btn.hasAttribute('data-calc-send') && calcSummary ? calcSummary() : '');
      if (!canModal) return;
      e.preventDefault();
      opener = btn;
      document.documentElement.classList.add('modal-open');
      modal.showModal();
      goal('form_open');
    });
  });

  document.querySelectorAll('[data-goal]').forEach(function (el) {
    el.addEventListener('click', function () { goal(el.getAttribute('data-goal')); });
  });

  // Появление блоков при прокрутке
  var revealEls = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); }
      });
    }, { rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(function (el, i) {
      el.style.transitionDelay = (i % 3) * 70 + 'ms';
      io.observe(el);
    });
  } else {
    revealEls.forEach(function (el) { el.classList.add('is-in'); });
  }

  // Калькулятор теплицы: единица учёта — полублок (пролёт × длина полублока). Цена позиции —
  // data-price за полублок + data-fixed разово на комплекс; оба пустые — позиция «по запросу».
  var calc = document.querySelector('[data-calc]');
  if (calc) {
    var SPAN = Number(calc.getAttribute('data-span')), LEN = Number(calc.getAttribute('data-len'));
    var inW = calc.querySelector('[data-calc-w]'), inL = calc.querySelector('[data-calc-l]');
    var plan = calc.querySelector('[data-calc-plan]');
    var money = function (n) { return new Intl.NumberFormat('ru-RU').format(n) + ' ₽'; };
    var plural = function (n, forms) {
      var a = n % 100, b = n % 10;
      return forms[a > 10 && a < 20 ? 2 : b === 1 ? 0 : b >= 2 && b <= 4 ? 1 : 2];
    };
    var num = function (el, attr) { var v = el.getAttribute(attr); return v === '' || v === null ? null : Number(v); };
    // Стоимость позиции на units полублоков; null — цены нет
    var cost = function (el, units) {
      var p = num(el, 'data-price'), f = num(el, 'data-fixed');
      return p === null && f === null ? null : (p || 0) * units + (f || 0);
    };
    // Значение поля — кратное шагу и в пределах min..max
    var snap = function (input) {
      var step = Number(input.step), min = Number(input.min), max = Number(input.max);
      var v = Math.round((Number(input.value) || min) / step) * step;
      v = Math.min(max, Math.max(min, v));
      input.value = v;
      return v;
    };
    var state = {};

    var drawPlan = function (spans, lens) {
      var w = lens * LEN, h = spans * SPAN;
      var parts = [];
      for (var r = 0; r < spans; r++) for (var c = 0; c < lens; c++) {
        parts.push('<rect x="' + (c * LEN) + '" y="' + (r * SPAN) + '" width="' + LEN + '" height="' + SPAN + '"/>');
      }
      plan.setAttribute('viewBox', '-1 -1 ' + (w + 2) + ' ' + (h + 2));
      plan.innerHTML = '<g class="calc__cells">' + parts.join('') + '</g><rect class="calc__outline" x="0" y="0" width="' + w + '" height="' + h + '"/>';
    };

    var update = function () {
      var w = snap(inW), l = snap(inL);
      var spans = w / SPAN, lens = l / LEN, units = spans * lens, area = w * l;
      calc.querySelector('[data-calc-w-note]').textContent = spans + ' ' + plural(spans, ['пролёт', 'пролёта', 'пролётов']) + ' по ' + SPAN + ' м';
      calc.querySelector('[data-calc-l-note]').textContent = lens + ' × ' + LEN + ' м';
      drawPlan(spans, lens);

      var frame = calc.querySelector('[data-calc-frame]');
      var cover = calc.querySelector('input[name="calc-cover"]:checked');
      var opts = Array.prototype.slice.call(calc.querySelectorAll('input[name="calc-opt"]:checked'));
      var items = [frame].concat(cover ? [cover] : [], opts);
      var total = 0, known = 0, pending = [];
      var lines = calc.querySelector('[data-calc-lines]');
      lines.innerHTML = '';
      items.forEach(function (el) {
        var c = cost(el, units), title = el.getAttribute('data-title');
        var li = document.createElement('li');
        var t = document.createElement('span'); t.textContent = title;
        var v = document.createElement('b'); v.textContent = c === null ? 'по запросу' : money(c);
        if (c === null) { v.className = 'is-pending'; pending.push(title.toLowerCase()); } else { total += c; known++; }
        li.appendChild(t); li.appendChild(v); lines.appendChild(li);
      });

      var sizeText = w + ' × ' + l + ' м · ' + new Intl.NumberFormat('ru-RU').format(area) + ' м² · ' + units + ' ' + plural(units, ['полублок', 'полублока', 'полублоков']);
      calc.querySelector('[data-calc-size]').textContent = sizeText;
      calc.querySelector('[data-calc-total]').textContent = calc.querySelector('[data-calc-bar-total]').textContent = known ? money(total) : 'по запросу';
      var pend = calc.querySelector('[data-calc-pending]');
      pend.hidden = !(known && pending.length);
      pend.textContent = 'Без учёта позиций по запросу: ' + pending.join(', ');

      state = {
        size: w + ' × ' + l + ' м (' + spans + ' ' + plural(spans, ['пролёт', 'пролёта', 'пролётов']) + ', ' + units + ' ' + plural(units, ['полублок', 'полублока', 'полублоков']) + '), ' + new Intl.NumberFormat('ru-RU').format(area) + ' м²',
        cover: cover ? cover.getAttribute('data-title').toLowerCase() : '',
        opts: opts.map(function (el) { return el.getAttribute('data-title').toLowerCase(); }),
        price: known ? money(total) + (pending.length ? ' (без учёта позиций по запросу: ' + pending.join(', ') + ')' : '') : 'по запросу'
      };
      // Расчёт уже передан в форму (окно закрыли, конфигурацию поменяли) — обновить, чтобы ушёл актуальный
      var field = document.querySelector('[data-calc-field]');
      if (field && field.value) setCalc(calcSummary());
    };

    calcSummary = function () {
      return 'Теплица ' + state.size
        + (state.cover ? '. Покрытие: ' + state.cover : '')
        + '. Системы: ' + (state.opts.length ? state.opts.join(', ') : 'не выбраны')
        + '. Ориентировочно: ' + state.price.replace(/ /g, ' ');
    };

    calc.querySelectorAll('.stepper').forEach(function (box) {
      var input = box.querySelector('input');
      box.querySelectorAll('[data-step]').forEach(function (b) {
        b.addEventListener('click', function () {
          input.value = Number(input.value) + Number(b.getAttribute('data-step')) * Number(input.step);
          update();
        });
      });
      input.addEventListener('change', update);
    });
    calc.addEventListener('change', function (e) { if (e.target.name === 'calc-cover' || e.target.name === 'calc-opt') update(); });
    calc.classList.add('is-live');
    update();
  }

  // Cookie-баннер
  var cookie = document.querySelector('[data-cookie]');
  var cookieKey = 'modufarm-cookie-ok';
  var cookieSeen = false;
  try { cookieSeen = localStorage.getItem(cookieKey) === '1'; } catch (e) { /* приватный режим */ }
  if (cookie && !cookieSeen) {
    // Показываем после начала прокрутки, чтобы не закрывать первый экран
    var showCookie = function () {
      if (window.scrollY < 200) return;
      cookie.hidden = false;
      window.removeEventListener('scroll', showCookie);
    };
    window.addEventListener('scroll', showCookie, { passive: true });
    showCookie();
    cookie.querySelector('[data-cookie-ok]').addEventListener('click', function () {
      try { localStorage.setItem(cookieKey, '1'); } catch (e) { /* ничего */ }
      cookie.hidden = true;
    });
  }

  // Метки рекламы: запоминаем на первом заходе, отправляем с заявкой
  var trackKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'yclid'];
  var params = new URLSearchParams(location.search);
  var tracking = {};
  try { tracking = JSON.parse(sessionStorage.getItem('modufarm-utm') || '{}'); } catch (e) { tracking = {}; }
  if (trackKeys.some(function (k) { return params.get(k); })) {
    tracking = {};
    trackKeys.forEach(function (k) { if (params.get(k)) tracking[k] = params.get(k); });
    tracking.page = location.href;
    tracking.referrer = document.referrer;
    try { sessionStorage.setItem('modufarm-utm', JSON.stringify(tracking)); } catch (e) { /* ничего */ }
  }

  // Маска телефона +7 (XXX) XXX-XX-XX
  function formatPhone(value) {
    var d = value.replace(/\D/g, '');
    if (!d) return '';
    if (d[0] === '8') d = '7' + d.slice(1);
    if (d[0] !== '7') d = '7' + d;
    d = d.slice(0, 11);
    var out = '+7';
    if (d.length > 1) out += ' (' + d.slice(1, 4);
    if (d.length >= 4) out += ')';
    if (d.length > 4) out += ' ' + d.slice(4, 7);
    if (d.length > 7) out += '-' + d.slice(7, 9);
    if (d.length > 9) out += '-' + d.slice(9, 11);
    return out;
  }
  document.querySelectorAll('[data-phone]').forEach(function (input) {
    input.addEventListener('input', function (e) {
      if (e.inputType && e.inputType.indexOf('delete') === 0) return;
      input.value = formatPhone(input.value);
    });
    input.addEventListener('blur', function () {
      if (input.value.replace(/\D/g, '').length <= 1) input.value = '';
    });
  });

  // Отправка формы
  document.querySelectorAll('[data-form]').forEach(function (form) {
    var status = form.querySelector('[data-status]');
    var phone = form.querySelector('[name="phone"]');
    var consent = form.querySelector('[name="consent"]');
    var started = false;

    form.addEventListener('input', function () {
      if (!started) { started = true; goal('form_start'); }
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      status.textContent = '';
      status.className = 'form__status';

      var digits = phone.value.replace(/\D/g, '');
      var phoneOk = digits.length === 11;
      phone.closest('.field').classList.toggle('is-error', !phoneOk);
      consent.closest('.consent').classList.toggle('is-error', !consent.checked);
      if (!phoneOk) { status.textContent = 'Проверьте номер телефона'; status.classList.add('is-error'); phone.focus(); return; }
      if (!consent.checked) { status.textContent = 'Нужно согласие на обработку данных'; status.classList.add('is-error'); return; }

      var data = new FormData(form);
      data.append('elapsed', String(Math.round((Date.now() - startTime) / 1000)));
      Object.keys(tracking).forEach(function (k) { data.append(k, tracking[k]); });
      if (!tracking.page) data.append('page', location.href);
      if (!tracking.referrer) data.append('referrer', document.referrer);

      var btn = form.querySelector('[type="submit"]');
      var btnText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Отправляем…';

      fetch(FORM_ENDPOINT, { method: 'POST', body: data })
        .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
        .then(function (res) {
          if (!res.ok) throw new Error(res.error || 'fail');
          goal('lead');
          location.href = 'spasibo.html';
        })
        .catch(function () {
          status.textContent = 'Не удалось отправить заявку. Попробуйте ещё раз чуть позже.';
          status.classList.add('is-error');
          btn.disabled = false;
          btn.textContent = btnText;
        });
    });
  });

  // Кнопка «Редактировать» для того, кто вошёл в админку (метка sg_adm ставится при входе, сама доступа не даёт)
  var editTo = document.body.getAttribute('data-edit');
  if (editTo && /(^|;\s*)sg_adm=1/.test(document.cookie)) {
    var eb = document.createElement('a');
    eb.href = editTo;
    eb.textContent = '✎ Редактировать';
    eb.setAttribute('style', 'position:fixed;left:16px;bottom:16px;z-index:90;padding:10px 16px;border-radius:10px;background:#0e3b2c;color:#fff;font:600 14px/1.2 system-ui,sans-serif;text-decoration:none;box-shadow:0 6px 20px rgba(0,0,0,.3);border:1px solid #9fdc6a');
    document.body.appendChild(eb);
  }
})();
