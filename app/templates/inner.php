<?php
// Оболочка внутренних страниц (политика, согласие, «спасибо», 404).
// $title, $description (может быть пустым), $noindex, $edit (раздел админки), $headerContacts, $footerFull, $body
$c = S::$company; $p0 = phone0();
?>
<!doctype html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <title><?= esc($title) ?></title>
<?php if ($noindex): ?>
  <meta name="robots" content="noindex, nofollow">
<?php else: ?>
  <meta name="description" content="<?= esc($description) ?>">
<?php endif ?>
  <link rel="icon" href="<?= u('favicon.svg') ?>" type="image/svg+xml">
  <link rel="apple-touch-icon" href="<?= u('img/apple-touch-icon.png') ?>">
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
</head>
<body<?= $edit !== '' ? ' data-edit="' . u('admin/#/' . $edit) . '"' : '' ?>>
<header class="header header--solid">
  <div class="container header__inner">
    <a class="logo" href="<?= u('index.html') ?>" aria-label="<?= esc($c['brand']) ?> — на главную">
      <img class="logo__mark" src="<?= u('img/logo-light.svg') ?>" width="40" height="40" alt="">
      <span class="logo__text"><span class="logo__name"><?= esc($c['brand']) ?></span><span class="logo__sub"><?= esc($c['brandSub']) ?></span></span>
    </a>
<?php if ($headerContacts): ?>
    <a class="header__phone header__phone--inner" href="tel:<?= esc($p0['tel']) ?>"><span><?= esc($p0['display']) ?></span></a>
    <a class="btn btn--accent btn--sm header__cta header__cta--keep" href="<?= u('index.html#zayavka') ?>"><?= esc(S::$site['nav']['ctaLabel']) ?></a>
<?php endif ?>
  </div>
</header>

<main class="page">
<?= $body ?>
</main>

<footer class="footer">
  <div class="container">
<?php if ($footerFull): ?>
    <p class="footer__contact footer__legal--first"><?= footer_contacts(false) ?></p>
    <p class="footer__legal"><?= legal_footer_html() ?></p>
<?= tpl('footer-bottom', ['cls' => '']) ?>
<?php else: ?>
<?= tpl('footer-bottom', ['cls' => ' footer__bottom--first']) ?>
<?php endif ?>
  </div>
</footer>
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
