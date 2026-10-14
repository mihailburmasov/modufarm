<?php
// Главная (одностраничник). Тексты — data/site.json, контакты и реквизиты — data/company.json, свидетельства — data/documents.json.
// Строки, где PHP-тег стоит в конце, теряют перевод строки — поэтому управляющие теги стоят с начала строки.
$s = S::$site; $c = S::$company; $p0 = phone0();
$h = $s['hero']; $pr = $s['programs']; $rb = $s['robot']; $gh = $s['greenhouse']; $gd = $s['gardens'];
$au = $s['audience']; $st = $s['steps']; $dc = $s['documents']; $iso = $s['iso']; $ld = $s['lead'];
$cards = all_program_cards();
$withDoc = count(array_filter($cards, fn($x) => ($x['doc'] ?? '') !== ''));
$hp = photo($h['photo']);
$q = fn($v) => json_encode((string)$v, JSON_FLAGS | JSON_HEX_TAG);
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= esc($s['meta']['title']) ?></title>
  <meta name="description" content="<?= esc($s['meta']['description']) ?>">
  <link rel="canonical" href="<?= esc(abs_url('/')) ?>">
  <meta name="theme-color" content="#0e3b2c">

  <meta property="og:type" content="website">
  <meta property="og:locale" content="ru_RU">
  <meta property="og:site_name" content="<?= esc($c['brand']) ?>">
  <meta property="og:title" content="<?= esc($s['meta']['ogTitle']) ?>">
  <meta property="og:description" content="<?= esc($s['meta']['ogDescription']) ?>">
  <meta property="og:url" content="<?= esc(abs_url('/')) ?>">
  <meta property="og:image" content="<?= esc(abs_url('/img/og.jpg')) ?>">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="icon" href="<?= u('favicon.svg') ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= u('img/apple-touch-icon.png') ?>">
  <link rel="preload" href="<?= u('fonts/onest-cyr.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": <?= $q($c['brand']) ?>,
    "legalName": <?= $q($c['legalName']) ?>,
    "url": <?= $q(abs_url('/')) ?>,
    "logo": <?= $q(abs_url('/img/apple-touch-icon.png')) ?>,
    "taxID": <?= $q($c['inn']) ?>,
    "telephone": [<?= implode(', ', array_map(fn($p) => $q(tel_ld($p['tel'])), phones())) ?>],
    "email": <?= $q($c['email']) ?>,
    "address": {
      "@type": "PostalAddress",
      "postalCode": <?= $q($c['postalCode']) ?>,
      "addressCountry": "RU",
      "addressRegion": <?= $q($c['region']) ?>,
      "addressLocality": <?= $q($c['locality']) ?>,
      "streetAddress": <?= $q($c['street']) ?>

    },
    "description": <?= $q($s['meta']['orgDescription']) ?>

  }
  </script>
</head>
<body data-edit="admin/#/">

<?= site_sprite() ?>


<header class="header" data-header>
  <div class="container header__inner">
    <a class="logo" href="#top" aria-label="<?= esc($c['brand']) ?> — на главную">
      <svg class="logo__mark" width="40" height="40"><use href="#i-logo"/></svg>
      <span class="logo__text"><span class="logo__name"><?= esc($c['brand']) ?></span><span class="logo__sub"><?= esc($c['brandSub']) ?></span></span>
    </a>
    <nav class="nav" data-nav aria-label="Разделы">
<?php foreach (nav_items('header') as $n): ?>
      <a href="#<?= $n['target'] ?>"><?= esc($n['label']) ?></a>
<?php endforeach ?>
      <a class="nav__phone" href="tel:<?= esc($p0['tel']) ?>" data-goal="phone_click"><?= esc($p0['display']) ?></a>
    </nav>
    <a class="header__phone" href="tel:<?= esc($p0['tel']) ?>" data-goal="phone_click"><svg class="ic"><use href="#i-phone"/></svg><span><?= esc($p0['display']) ?></span></a>
    <a class="btn btn--accent btn--sm header__cta" href="#zayavka"><?= esc($s['nav']['ctaLabel']) ?></a>
    <button class="burger" type="button" aria-label="Открыть меню" aria-expanded="false" data-burger><span></span><span></span><span></span></button>
  </div>
</header>

<main id="top">

  <section class="hero">
    <div class="hero__grid-bg" aria-hidden="true"></div>
    <div class="container hero__inner">
      <div class="hero__text">
        <p class="eyebrow eyebrow--light"><?= esc($h['eyebrow']) ?></p>
        <h1 class="hero__title"><?= esc($h['h1']) ?></h1>
        <p class="hero__lead"><?= esc($h['lead']) ?></p>
        <div class="hero__actions">
          <a class="btn btn--accent" href="#zayavka" data-topic="<?= esc($h['btnMainTopic']) ?>"><?= esc($h['btnMainLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
<?php if ($h['btnSecondLabel'] !== ''): ?>
          <a class="btn btn--ghost-light" href="#zayavka" data-topic="<?= esc($h['btnSecondTopic']) ?>"><?= esc($h['btnSecondLabel']) ?></a>
<?php endif ?>
        </div>
        <ul class="hero__facts">
          <li><b><?= count($cards) ?></b><span><?= esc($h['factAllText']) ?></span></li>
<?php if ($withDoc > 0): ?>
          <li><b><?= $withDoc ?></b><span><?= esc($h['factDocsText']) ?></span></li>
<?php endif ?>
        </ul>
      </div>

      <figure class="hero__art">
        <img class="hero__photo" src="<?= img_url($hp['id']) ?>" srcset="<?= img_url($hp['id'], '-sm') ?> <?= $hp['sw'] ?>w, <?= img_url($hp['id']) ?> <?= $hp['w'] ?>w" sizes="(max-width: 1040px) 92vw, 540px" width="<?= $hp['w'] ?>" height="<?= $hp['h'] ?>" alt="<?= esc($hp['alt']) ?>" fetchpriority="high" style="height:auto">
<?php if ($h['caption'] !== ''): ?>
        <figcaption class="hero__caption"><?= esc($h['caption']) ?></figcaption>
<?php endif ?>
      </figure>
    </div>
  </section>

<?php if (section_on('programs')): ?>
  <section class="section" id="napravleniya">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($pr['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($pr['title']) ?></h2>
<?php if ($pr['lead'] !== ''): ?>
        <p class="section__lead"><?= esc($pr['lead']) ?></p>
<?php endif ?>
      </div>
<?php foreach ($pr['groups'] as $g): ?>

      <div class="cards-group">
        <h3 class="cards-group__title"><?= esc($g['title']) ?></h3>
        <div class="cards <?= cards_cols(count($g['cards'])) ?>">
<?php foreach ($g['cards'] as $card): $doc = ($card['doc'] ?? '') !== '' ? S::$docByNum[$card['doc']] : null; ?>
          <article class="card reveal">
            <div class="card__icon"><svg class="ic"><use href="#i-<?= $card['icon'] ?>"/></svg></div>
            <h4 class="card__title"><?= esc($card['title']) ?></h4>
            <p><?= esc($card['text']) ?></p>
<?php if ($doc): ?>
            <a class="card__doc" href="<?= pdf_url($doc['pdf']) ?>" target="_blank" rel="noopener">Свидетельство № <?= esc($doc['num']) ?></a>
<?php else: ?>
            <span class="card__doc card__doc--plain"><?= esc($pr['noDocLabel']) ?></span>
<?php endif ?>
          </article>
<?php endforeach ?>
        </div>
      </div>
<?php endforeach ?>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('robot')): ?>
  <section class="section section--dark" id="robot">
    <div class="container split">
      <div class="split__text">
        <p class="eyebrow eyebrow--light"><?= esc($rb['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($rb['title']) ?></h2>
        <p class="section__lead section__lead--light"><?= esc($rb['lead']) ?></p>
        <a class="btn btn--accent" href="#zayavka" data-topic="<?= esc($rb['btnTopic']) ?>"><?= esc($rb['btnLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
      </div>
      <ol class="layers">
<?php foreach ($rb['layers'] as $i => $l): ?>
        <li class="layers__item reveal"><span class="layers__num"><?= sprintf('%02d', $i + 1) ?></span><div><h3><?= esc($l['title']) ?></h3><p><?= esc($l['text']) ?></p></div></li>
<?php endforeach ?>
      </ol>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('greenhouse')): ?>
  <section class="section section--soft" id="teplicy">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($gh['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($gh['title']) ?></h2>
        <p class="section__lead"><?= esc($gh['lead']) ?></p>
      </div>
      <div class="features">
<?php foreach ($gh['features'] as $f): ?>
        <div class="feature reveal">
          <h3><?= esc($f['title']) ?></h3>
          <ul>
<?php foreach ($f['points'] as $pt): ?>
            <li><?= esc($pt) ?></li>
<?php endforeach ?>
          </ul>
        </div>
<?php endforeach ?>
      </div>
      <div class="features__foot">
        <p><?= esc($gh['footText']) ?></p>
        <a class="btn btn--primary" href="#zayavka" data-topic="<?= esc($gh['btnTopic']) ?>"><?= esc($gh['btnLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
      </div>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('gardens')): ?>
  <section class="section" id="sady">
    <div class="container split split--reverse">
      <div class="split__text">
        <p class="eyebrow"><?= esc($gd['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($gd['title']) ?></h2>
        <p class="section__lead"><?= esc($gd['lead']) ?></p>
        <ul class="ticks">
<?php foreach ($gd['ticks'] as $t): ?>
          <li><svg class="ic"><use href="#i-check"/></svg><?= esc($t) ?></li>
<?php endforeach ?>
        </ul>
<?php if ($gd['sro'] !== ''): ?>
        <p class="sro"><?= esc($gd['sro']) ?></p>
<?php endif ?>
        <a class="btn btn--primary" href="#zayavka" data-topic="<?= esc($gd['btnTopic']) ?>"><?= esc($gd['btnLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
      </div>
      <div class="plan reveal" aria-hidden="true">
        <!-- Схема сада: условная иллюстрация -->
        <svg viewBox="0 0 440 400" class="plan__svg">
          <rect x="1" y="1" width="438" height="398" rx="24" fill="#f7f5ec" stroke="#d9d4c2"/>
          <g stroke="#e3dfd0" stroke-width="1">
            <path d="M40 0v400M80 0v400M120 0v400M160 0v400M200 0v400M240 0v400M280 0v400M320 0v400M360 0v400M400 0v400"/>
            <path d="M0 40h440M0 80h440M0 120h440M0 160h440M0 200h440M0 240h440M0 280h440M0 320h440M0 360h440"/>
          </g>
          <path d="M50 340 Q60 60 380 60" stroke="#c3d9e6" stroke-width="10" fill="none" stroke-linecap="round" opacity=".7"/>
          <g fill="#3e9450">
            <defs><g id="row"><circle cx="0" cy="0" r="9"/><circle cx="30" cy="0" r="9"/><circle cx="60" cy="0" r="9"/><circle cx="90" cy="0" r="9"/><circle cx="120" cy="0" r="9"/><circle cx="150" cy="0" r="9"/><circle cx="180" cy="0" r="9"/><circle cx="210" cy="0" r="9"/></g></defs>
            <use href="#row" x="130" y="120"/><use href="#row" x="130" y="170"/><use href="#row" x="130" y="220"/><use href="#row" x="130" y="270"/><use href="#row" x="130" y="320"/>
          </g>
          <g fill="#e8553a"><circle cx="190" cy="170" r="3"/><circle cx="280" cy="220" r="3"/><circle cx="220" cy="270" r="3"/><circle cx="310" cy="120" r="3"/></g>
          <path d="M108 95 H362 V145 H108 V195 H362 V245 H108 V295 H362" stroke="#e8553a" stroke-width="3" stroke-dasharray="8 7" fill="none" stroke-linecap="round"/>
          <g transform="translate(96 84)"><rect width="24" height="20" rx="5" fill="#0e3b2c"/><circle cx="6" cy="20" r="4" fill="#1b2a24"/><circle cx="18" cy="20" r="4" fill="#1b2a24"/></g>
          <g font-family="Onest, sans-serif" font-size="12" fill="#5c6f66">
            <text x="372" y="149">4,5 м</text>
            <path d="M366 155 v40" stroke="#5c6f66"/><path d="M362 155h8M362 195h8" stroke="#5c6f66"/>
            <text x="28" y="384">Схема условная</text>
          </g>
        </svg>
      </div>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('audience')): ?>
  <section class="section section--soft">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($au['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($au['title']) ?></h2>
      </div>
      <div class="audience">
<?php foreach ($au['items'] as $a): ?>
        <div class="audience__item reveal"><h3><?= esc($a['title']) ?></h3><p><?= esc($a['text']) ?></p></div>
<?php endforeach ?>
      </div>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('steps')): ?>
  <section class="section" id="etapy">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($st['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($st['title']) ?></h2>
      </div>
      <ol class="steps">
<?php foreach ($st['items'] as $i => $x): ?>
        <li class="steps__item reveal"><span class="steps__num"><?= $i + 1 ?></span><h3><?= esc($x['title']) ?></h3><p><?= esc($x['text']) ?></p></li>
<?php endforeach ?>
      </ol>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('documents')): ?>
  <section class="section section--soft" id="dokumenty">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($dc['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($dc['title']) ?></h2>
        <p class="section__lead"><?= esc($dc['lead']) ?></p>
      </div>
<?php if (S::$docs): ?>

      <div class="docs">
<?php foreach (S::$docs as $d): ?>
        <a class="doc reveal" href="<?= pdf_url($d['pdf']) ?>" target="_blank" rel="noopener">
          <?= doc_img($d['preview'], '(max-width: 700px) 50vw, 200px') ?>

          <span class="doc__num">№ <?= esc($d['num']) ?> · <?= esc($d['date']) ?></span>
          <span class="doc__name"><?= esc($d['name']) ?></span>
        </a>
<?php endforeach ?>
      </div>
<?php endif ?>
<?php if (empty($iso['hidden'])): ?>

      <div class="iso reveal">
        <a class="iso__img" href="<?= pdf_url($iso['pdf']) ?>" target="_blank" rel="noopener">
          <?= doc_img($iso['preview'], '260px') ?>

        </a>
        <div class="iso__text">
          <p class="eyebrow"><?= esc($iso['eyebrow']) ?></p>
          <h3><?= esc($iso['title']) ?></h3>
          <dl class="iso__list">
            <div><dt>Номер</dt><dd><?= esc($iso['num']) ?></dd></div>
            <div><dt>Действует</dt><dd><?= esc($iso['valid']) ?></dd></div>
            <div><dt>Область</dt><dd><?= esc($iso['scope']) ?></dd></div>
            <div><dt>Выдан</dt><dd><?= esc($iso['issuer']) ?></dd></div>
          </dl>
<?php if ($iso['noteText'] !== ''): ?>
          <p class="iso__note"><?= esc($iso['noteText']) ?><?php if ($iso['noteLinkUrl'] !== ''): ?> <a href="<?= esc($iso['noteLinkUrl']) ?>" target="_blank" rel="noopener"><?= esc($iso['noteLinkText'] ?: $iso['noteLinkUrl']) ?></a><?php endif ?>.</p>
<?php endif ?>
          <a class="btn btn--ghost" href="<?= pdf_url($iso['pdf']) ?>" target="_blank" rel="noopener"><svg class="ic"><use href="#i-doc"/></svg><?= esc($iso['btnLabel']) ?></a>
        </div>
      </div>
<?php endif ?>
    </div>
  </section>

<?php endif ?>
  <section class="section section--dark" id="zayavka">
    <div class="container lead">
      <div class="lead__text">
        <p class="eyebrow eyebrow--light"><?= esc($ld['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($ld['title']) ?></h2>
        <p class="section__lead section__lead--light"><?= esc($ld['lead']) ?></p>
        <ul class="lead__contacts">
<?php foreach (phones() as $p): ?>
          <li><?= phone_a($p, true, '<svg class="ic"><use href="#i-phone"/></svg>') ?><?php if (($p['note'] ?? '') !== ''): ?> <span class="lead__note"><?= esc($p['note']) ?></span><?php endif ?></li>
<?php endforeach ?>
          <li class="lead__mail"><?= mail_a(true, '', '<svg class="ic"><use href="#i-mail"/></svg>') ?></li>
        </ul>
        <p class="lead__addr"><?= esc($c['legalName']) ?> · <?= esc($c['shortAddress']) ?></p>
      </div>

      <form class="form" action="<?= u('lead.php') ?>" method="post" novalidate data-form>
        <input type="hidden" name="form_name" value="main">
        <div class="form__trap" aria-hidden="true"><label>Сайт <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <fieldset class="form__topics">
          <legend><?= esc($ld['topicsLabel']) ?></legend>
<?php foreach ($ld['topics'] as $i => $t): ?>
          <label class="chip"><input type="radio" name="topic" value="<?= esc($t['value']) ?>"<?= $i === 0 ? ' checked' : '' ?>><span><?= esc($t['label'] ?: $t['value']) ?></span></label>
<?php endforeach ?>
        </fieldset>

        <div class="form__row">
          <label class="field"><span>Имя</span><input type="text" name="name" autocomplete="name" maxlength="80" placeholder="Как к вам обращаться"></label>
          <label class="field"><span>Телефон *</span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required data-phone></label>
        </div>
        <label class="field"><span>Культура и площадь</span><input type="text" name="crop" maxlength="200" placeholder="Например: яблоня, 40 га"></label>
        <label class="field"><span>Задача</span><textarea name="message" rows="3" maxlength="1500" placeholder="Что нужно автоматизировать или спроектировать"></textarea></label>

        <label class="consent">
          <input type="checkbox" name="consent" value="1" checked required>
          <span>Даю <a href="<?= u('soglasie.html') ?>" target="_blank">согласие на обработку персональных данных</a> и принимаю <a href="<?= u('politika.html') ?>" target="_blank">политику конфиденциальности</a></span>
        </label>

        <button class="btn btn--accent btn--block" type="submit"><?= esc($ld['submitLabel']) ?></button>
        <p class="form__status" role="status" aria-live="polite" data-status></p>
      </form>
    </div>
  </section>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <a class="logo logo--light" href="#top" aria-label="<?= esc($c['brand']) ?> — наверх">
        <svg class="logo__mark" width="40" height="40"><use href="#i-logo"/></svg>
        <span class="logo__text"><span class="logo__name"><?= esc($c['brand']) ?></span><span class="logo__sub"><?= esc($c['brandSub']) ?></span></span>
      </a>
      <nav class="footer__nav" aria-label="Разделы">
<?php foreach (nav_items('footer') as $n): ?>
        <a href="#<?= $n['target'] ?>"><?= esc($n['label']) ?></a>
<?php endforeach ?>
      </nav>
    </div>
    <p class="footer__contact"><?= footer_contacts(true) ?></p>
    <p class="footer__legal"><?= legal_footer_html() ?></p>
<?= tpl('footer-bottom', ['cls' => '']) ?>
  </div>
</footer>

<div class="cookie" data-cookie hidden>
  <p>Сайт использует файлы cookie, чтобы корректно работать. Подробнее — в <a href="<?= u('politika.html') ?>">политике конфиденциальности</a>.</p>
  <button class="btn btn--sm btn--primary" type="button" data-cookie-ok>Понятно</button>
</div>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
