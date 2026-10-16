<?php
// Страница «Роботы и программы»: первый экран, направления (программы), робот под культуру, кому полезно.
// Тексты — data/site.json (robotsPage, programs, robot, audience), свидетельства — data/documents.json.
$s = S::$site;
$h = $s['robotsPage']; $pr = $s['programs']; $rb = $s['robot']; $au = $s['audience'];
$cards = all_program_cards();
$withDoc = count(array_filter($cards, fn($x) => ($x['doc'] ?? '') !== ''));
?>
  <section class="hero">
    <div class="hero__grid-bg" aria-hidden="true"></div>
    <div class="container hero__inner">
      <div class="hero__text">
        <p class="eyebrow eyebrow--light"><?= esc($h['eyebrow']) ?></p>
        <h1 class="hero__title"><?= esc($h['h1']) ?></h1>
        <p class="hero__lead"><?= esc($h['lead']) ?></p>
        <div class="hero__actions">
          <a class="btn btn--accent" href="#zayavka" data-topic="<?= esc($h['btnTopic']) ?>"><?= esc($h['btnLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
<?php if (section_on('programs')): ?>
          <a class="btn btn--ghost-light" href="#napravleniya">Все программы</a>
<?php endif ?>
        </div>
        <ul class="hero__facts">
          <li><b><?= count($cards) ?></b><span><?= esc($h['factAllText']) ?></span></li>
<?php if ($withDoc > 0): ?>
          <li><b><?= $withDoc ?></b><span><?= esc($h['factDocsText']) ?></span></li>
<?php endif ?>
        </ul>
      </div>

<?= hero_figure($h['photo'], $h['caption']) ?>
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
<?php if (section_on('documents')): ?>
      <p class="cards-more"><a href="<?= u('index.html') ?>#dokumenty">Свидетельства Роспатента и сертификаты →</a></p>
<?php endif ?>
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
