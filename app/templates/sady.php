<?php
// Страница «Проектирование садов»: короткая шапка и раздел о проектах садов. Тексты — data/site.json (gardensPage, gardens).
$hp = S::$site['gardensPage']; $gd = S::$site['gardens'];
?>
  <section class="page-hero">
    <div class="hero__grid-bg" aria-hidden="true"></div>
    <div class="container page-hero__inner">
      <p class="eyebrow eyebrow--light"><?= esc($hp['eyebrow']) ?></p>
      <h1 class="page-hero__title"><?= esc($hp['h1']) ?></h1>
<?php if ($hp['lead'] !== ''): ?>
      <p class="hero__lead"><?= esc($hp['lead']) ?></p>
<?php endif ?>
    </div>
  </section>

  <section class="section" id="proekty-sadov">
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

