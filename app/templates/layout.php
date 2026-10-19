<?php
// Оболочка страниц с разделами (главная, «Роботы», «Сады»): шапка с меню, подвал, cookie-баннер.
// $title, $description, $ogTitle, $ogDescription, $loc (адрес страницы от корня), $edit (раздел админки), $ld (микроразметка организации), $body
$c = S::$company; $p0 = phone0();
$q = fn($v) => json_encode((string)$v, JSON_FLAGS | JSON_HEX_TAG);
?>
<!doctype html>
<html lang="ru"<?= metrika_attr() ?>>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= esc($title) ?></title>
  <meta name="description" content="<?= esc($description) ?>">
  <link rel="canonical" href="<?= esc(abs_url($loc)) ?>">
  <meta name="theme-color" content="#0e3b2c">

  <meta property="og:type" content="website">
  <meta property="og:locale" content="ru_RU">
  <meta property="og:site_name" content="<?= esc($c['brand']) ?>">
  <meta property="og:title" content="<?= esc($ogTitle) ?>">
  <meta property="og:description" content="<?= esc($ogDescription) ?>">
  <meta property="og:url" content="<?= esc(abs_url($loc)) ?>">
  <meta property="og:image" content="<?= esc(abs_url('/img/og-mittrobot.jpg')) ?>">
  <meta name="twitter:card" content="summary_large_image">

  <link rel="icon" href="<?= u('favicon.ico') ?>" sizes="any">
  <link rel="icon" href="<?= u('img/icon-192.png') ?>" type="image/png" sizes="192x192">
  <link rel="apple-touch-icon" href="<?= u('img/apple-touch-icon.png') ?>">
  <link rel="preload" href="<?= u('fonts/onest-cyr.woff2') ?>" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<?php if ($ld): ?>

  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": <?= $q($c['brand']) ?>,
    "legalName": <?= $q($c['legalName']) ?>,
    "url": <?= $q(abs_url('/')) ?>,
    "logo": <?= $q(abs_url('/img/logo.png')) ?>,
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
    "description": <?= $q(S::$site['meta']['orgDescription']) ?>

  }
  </script>
<?php endif ?>
</head>
<body data-edit="<?= u('admin/#/' . $edit) ?>">
<?= metrika_noscript() ?>
<?= site_sprite() ?>


<header class="header" data-header>
  <div class="container header__inner">
    <a class="logo" href="<?= S::$page === 'index' ? '#top' : u('index.html') ?>" aria-label="<?= esc($c['brand']) ?> — на главную">
      <img class="logo__mark" src="<?= u('img/logo-mark.png') ?>" width="40" height="40" alt="">
      <span class="logo__text"><span class="logo__name"><?= esc($c['brand']) ?></span><span class="logo__sub"><?= esc($c['brandSub']) ?></span></span>
    </a>
    <nav class="nav" data-nav aria-label="Разделы">
<?php foreach (nav_items('header') as $n): ?>
      <a href="<?= nav_href($n['target']) ?>"<?= SECTION_ANCHORS[$n['target']][2] === S::$page && SECTION_ANCHORS[$n['target']][3] ? ' aria-current="page"' : '' ?>><?= esc($n['label']) ?></a>
<?php endforeach ?>
      <a class="nav__phone" href="tel:<?= esc($p0['tel']) ?>" data-goal="phone_click"><?= esc($p0['display']) ?></a>
    </nav>
    <a class="header__phone" href="tel:<?= esc($p0['tel']) ?>" data-goal="phone_click"><svg class="ic"><use href="#i-phone"/></svg><span><?= esc($p0['display']) ?></span></a>
    <a class="btn btn--accent btn--sm header__cta" href="#zayavka"><?= esc(S::$site['nav']['ctaLabel']) ?></a>
    <button class="burger" type="button" aria-label="Открыть меню" aria-expanded="false" data-burger><span></span><span></span><span></span></button>
  </div>
</header>

<main id="top">
<?= $body ?>
<?= tpl('lead-block') ?>
</main>

<footer class="footer">
  <div class="container">
    <div class="footer__top">
      <a class="logo logo--light" href="<?= S::$page === 'index' ? '#top' : u('index.html') ?>" aria-label="<?= esc($c['brand']) ?> — <?= S::$page === 'index' ? 'наверх' : 'на главную' ?>">
        <img class="logo__mark" src="<?= u('img/logo-mark.png') ?>" width="40" height="40" alt="">
        <span class="logo__text"><span class="logo__name"><?= esc($c['brand']) ?></span><span class="logo__sub"><?= esc($c['brandSub']) ?></span></span>
      </a>
      <nav class="footer__nav" aria-label="Разделы">
<?php foreach (nav_items('footer') as $n): ?>
        <a href="<?= nav_href($n['target']) ?>"><?= esc($n['label']) ?></a>
<?php endforeach ?>
      </nav>
    </div>
    <p class="footer__contact"><?= footer_contacts(true) ?></p>
    <p class="footer__legal"><?= legal_footer_html() ?></p>
<?= tpl('footer-bottom', ['cls' => '']) ?>
  </div>
</footer>

<div class="cookie" data-cookie hidden>
  <p>Сайт использует файлы cookie<?= metrika_id() ? ' и сервис Яндекс Метрика' : '' ?>, чтобы корректно работать<?= metrika_id() ? ' и становиться удобнее' : '' ?>. Подробнее — в <a href="<?= u('politika.html') ?>">политике конфиденциальности</a>.</p>
  <button class="btn btn--sm btn--primary" type="button" data-cookie-ok>Понятно</button>
</div>

<?= tpl('lead-modal') ?>

<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
