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
  document.querySelectorAll('a.btn[href="#zayavka"]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      setTopic(btn.getAttribute('data-topic'));
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
