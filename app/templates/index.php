<?php
// Главная — тепличные комплексы. Тексты — data/site.json, свидетельства — data/documents.json.
// Строки, где PHP-тег стоит в конце, теряют перевод строки — поэтому управляющие теги стоят с начала строки.
$s = S::$site;
$h = $s['hero']; $md = $s['modules']; $pc = $s['principle']; $gh = $s['greenhouse']; $rt = $s['robotics'];
$fr = $s['frame']; $sp = $s['specs']; $st = $s['steps']; $dc = $s['documents']; $iso = $s['iso'];
?>
  <section class="hero">
    <div class="hero__grid-bg" aria-hidden="true"></div>
    <div class="container hero__inner">
      <div class="hero__text">
        <p class="eyebrow eyebrow--light"><?= esc($h['eyebrow']) ?></p>
        <h1 class="hero__title"><?= esc($h['h1']) ?></h1>
        <p class="hero__lead"><?= esc($h['lead']) ?></p>
        <div class="hero__actions">
          <a class="btn btn--accent" href="#zayavka" data-topic="<?= esc($h['btnMainTopic']) ?>"><?= esc($h['btnMainLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
<?php if ($h['btnSecondLabel'] !== '' && section_on(SECTION_ANCHORS[$h['btnSecondTarget']][0])): ?>
          <a class="btn btn--ghost-light" href="<?= nav_href($h['btnSecondTarget']) ?>"><?= esc($h['btnSecondLabel']) ?></a>
<?php endif ?>
        </div>
<?php if ($h['facts']): ?>
        <ul class="hero__facts hero__facts--<?= min(3, count($h['facts'])) ?>">
<?php foreach ($h['facts'] as $f): ?>
          <li><b><?= esc($f['num']) ?></b><span><?= esc($f['text']) ?></span></li>
<?php endforeach ?>
        </ul>
<?php endif ?>
      </div>

<?= hero_figure($h['photo'], $h['caption']) ?>
    </div>
  </section>

<?php if (section_on('modules')): $mp = photo($md['photo']); ?>
  <section class="section section--dark modules" id="moduli">
    <div class="container">
      <div class="modules__head">
        <div class="section__head">
          <p class="eyebrow eyebrow--light"><?= esc($md['eyebrow']) ?></p>
          <h2 class="section__title"><?= esc($md['title']) ?></h2>
          <p class="section__lead section__lead--light"><?= esc($md['lead']) ?></p>
        </div>
        <ul class="scale">
<?php foreach ($md['scale'] as $x): ?>
          <li class="reveal"><b><?= esc($x['num']) ?></b><span><?= esc($x['text']) ?></span></li>
<?php endforeach ?>
        </ul>
      </div>
      <figure class="modules__art reveal">
        <img src="<?= img_url($mp['id']) ?>" srcset="<?= img_url($mp['id'], '-sm') ?> <?= $mp['sw'] ?>w, <?= img_url($mp['id']) ?> <?= $mp['w'] ?>w" sizes="(max-width: 1240px) 100vw, 1190px" width="<?= $mp['w'] ?>" height="<?= $mp['h'] ?>" loading="lazy" alt="<?= esc($mp['alt']) ?>">
<?php if ($md['caption'] !== ''): ?>
        <figcaption><?= esc($md['caption']) ?></figcaption>
<?php endif ?>
      </figure>
<?php if ($md['points']): ?>
      <ul class="modules__points">
<?php foreach ($md['points'] as $pt): ?>
        <li><svg class="ic"><use href="#i-check"/></svg><?= esc($pt) ?></li>
<?php endforeach ?>
      </ul>
<?php endif ?>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('principle')): ?>
  <section class="section" id="princip">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($pc['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($pc['title']) ?></h2>
        <p class="section__lead"><?= esc($pc['lead']) ?></p>
      </div>
      <figure class="scheme reveal">
        <!-- Разрез двух пролётов: геометрия по разрезу эскизного проекта (отметки +3,500 / +4,032 / +5,451 / +6,512) -->
        <svg viewBox="-40 0 840 380" class="scheme__svg" role="img" aria-label="Разрез теплицы: крутой скат на север, пологий на юг, тёплый воздух уходит через фрамуги в коньке, прохладный заходит через боковые фрамуги">
          <defs>
            <marker id="ar-cold" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 10 5 0 10z" fill="#2f7fc1"/></marker>
            <marker id="ar-warm" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 0 10 5 0 10z" fill="#e8553a"/></marker>
          </defs>
          <path d="M20 300 H780" stroke="#c9c4b2" stroke-width="2"/>
          <g fill="none" stroke="#0e3b2c" stroke-linejoin="round">
            <path d="M70 300 V171 M390 300 V188 M710 300 V171" stroke-width="4"/>
            <path d="M70 188 H710" stroke-width="2.5"/>
            <path d="M70 171 L108.4 188 L143.6 135.1 L185.2 188 L232.7 91.6 L281.2 188 L319.6 150.5 L354.8 188 L390 170.7 M232.7 188 V91.6 M390 171 L428.4 188 L463.6 135.1 L505.2 188 L552.7 91.6 L601.2 188 L639.6 150.5 L674.8 188 L710 170.7 M552.7 188 V91.6" stroke-width="1.4" opacity=".55"/>
            <path d="M70 171 L232.7 91.6 M232.7 125.6 L390 171 L552.7 91.6 M552.7 125.6 L710 171" stroke-width="4"/>
          </g>
          <g stroke="#3fae55" stroke-width="5" stroke-linecap="round">
            <path d="M232.7 91.6 L258 116"/><path d="M552.7 91.6 L578 116"/>
            <path d="M710 174 L732 194"/><path d="M710 240 L736 266"/>
          </g>
          <g fill="none" stroke-width="3" stroke-linecap="round">
            <path d="M780 280 C 750 280, 735 262, 690 262" stroke="#2f7fc1" marker-end="url(#ar-cold)"/>
            <path d="M780 205 C 755 205, 735 190, 690 190" stroke="#2f7fc1" marker-end="url(#ar-cold)"/>
            <path d="M680 262 C 600 262, 540 250, 490 246 M370 250 C 300 250, 230 248, 170 246" stroke="#2f7fc1" stroke-dasharray="2 8" opacity=".8"/>
            <path d="M480 240 C 505 195, 532 128, 558 109 S 595 72, 620 56" stroke="#e8553a" marker-end="url(#ar-warm)"/>
            <path d="M160 240 C 185 195, 212 128, 238 109 S 275 72, 300 56" stroke="#e8553a" marker-end="url(#ar-warm)"/>
          </g>
          <g font-family="Onest, sans-serif" font-size="19" fill="#13261e">
            <text x="98" y="150" font-weight="600">26°</text>
            <text x="318" y="140" font-weight="600">16°</text>
            <text x="-30" y="30" fill="#5c6f66">← север</text>
            <text x="780" y="30" text-anchor="end" fill="#5c6f66">юг →</text>
            <text x="628" y="50" fill="#e8553a" font-size="17">тёплый воздух</text>
            <text x="796" y="160" fill="#2f7fc1" font-size="17" text-anchor="end">прохлада</text>
            <text x="230" y="345" text-anchor="middle" fill="#5c6f66" font-size="17">пролёт 10 м</text>
            <text x="550" y="345" text-anchor="middle" fill="#5c6f66" font-size="17">пролёт 10 м</text>
          </g>
          <g stroke="#9aa59f" stroke-width="1.5"><path d="M70 325 H390 M390 325 H710 M70 318 v14 M390 318 v14 M710 318 v14"/></g>
          <g font-family="Onest, sans-serif" font-size="16" fill="#5c6f66">
            <path d="M48 300 V188 M42 188 h12 M42 300 h12" stroke="#9aa59f" stroke-width="1.5"/>
            <text x="40" y="250" text-anchor="end">3,5 м</text>
            <path d="M-34 91.6 H62 M-34 300 V91.6" stroke="#9aa59f" stroke-width="1.5" stroke-dasharray="3 4"/>
            <text x="-28" y="200">6,5 м</text>
          </g>
        </svg>
        <figcaption class="scheme__legend">
          <span class="scheme__hint">Схему можно прокрутить вбок →</span>
          <span><i class="lg lg--vent"></i>фрамуги</span><span><i class="lg lg--warm"></i>тёплый воздух уходит через конёк</span><span><i class="lg lg--cold"></i>прохладный заходит сбоку</span>
        </figcaption>
      </figure>

      <div class="principle">
        <div class="principle__facts">
          <ul class="facts">
<?php foreach ($pc['facts'] as $f): ?>
            <li class="reveal"><b><?= esc($f['num']) ?></b><span><?= esc($f['text']) ?></span></li>
<?php endforeach ?>
          </ul>
<?php if ($pc['factsNote'] !== ''): ?>
          <p class="note"><?= esc($pc['factsNote']) ?></p>
<?php endif ?>
          <ul class="ticks">
<?php foreach ($pc['points'] as $pt): ?>
            <li><svg class="ic"><use href="#i-check"/></svg><?= esc($pt) ?></li>
<?php endforeach ?>
          </ul>
        </div>
<?php if ($pc['photo'] !== ''): $pp = photo($pc['photo']); ?>
        <figure class="principle__art reveal">
          <img src="<?= img_url($pp['id']) ?>" srcset="<?= img_url($pp['id'], '-sm') ?> <?= $pp['sw'] ?>w, <?= img_url($pp['id']) ?> <?= $pp['w'] ?>w" sizes="(max-width: 1040px) 92vw, 560px" width="<?= $pp['w'] ?>" height="<?= $pp['h'] ?>" loading="lazy" alt="<?= esc($pp['alt']) ?>">
<?php if ($pc['caption'] !== ''): ?>
          <figcaption><?= esc($pc['caption']) ?></figcaption>
<?php endif ?>
        </figure>
<?php endif ?>
      </div>
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
<?php if (section_on('robotics')): ?>
  <section class="section section--dark" id="robotizaciya">
    <div class="container split split--photo">
<?php if ($rt['photo'] !== ''): ?>
      <figure class="split__photo reveal">
        <?= doc_img($rt['photo'], '(max-width: 1040px) 92vw, 520px') ?>

<?php if ($rt['caption'] !== ''): ?>
        <figcaption><?= esc($rt['caption']) ?></figcaption>
<?php endif ?>
      </figure>
<?php endif ?>
      <div>
        <p class="eyebrow eyebrow--light"><?= esc($rt['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($rt['title']) ?></h2>
        <p class="section__lead section__lead--light"><?= esc($rt['lead']) ?></p>
        <ol class="layers layers--tight">
<?php foreach ($rt['items'] as $i => $l): ?>
          <li class="layers__item reveal"><span class="layers__num"><?= sprintf('%02d', $i + 1) ?></span><div><h3><?= esc($l['title']) ?></h3><p><?= esc($l['text']) ?></p></div></li>
<?php endforeach ?>
        </ol>
<?php if ($rt['linkLabel'] !== '' && page_on('roboty')): ?>
        <a class="link-more" href="<?= page_url('roboty') ?>"><?= esc($rt['linkLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
<?php endif ?>
      </div>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('frame')): ?>
  <section class="section" id="konstrukciya">
    <div class="container split split--frame">
      <div class="split__text">
        <p class="eyebrow"><?= esc($fr['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($fr['title']) ?></h2>
        <p class="section__lead"><?= esc($fr['lead']) ?></p>
        <ul class="ticks">
<?php foreach ($fr['points'] as $pt): ?>
          <li><svg class="ic"><use href="#i-check"/></svg><?= esc($pt) ?></li>
<?php endforeach ?>
        </ul>
      </div>
<?php if ($fr['photo'] !== ''): ?>
      <figure class="drawing reveal">
        <?= doc_img($fr['photo'], '(max-width: 1040px) 92vw, 584px') ?>

<?php if ($fr['caption'] !== ''): ?>
        <figcaption><?= esc($fr['caption']) ?></figcaption>
<?php endif ?>
      </figure>
<?php endif ?>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('specs')): ?>
  <section class="section section--soft" id="proekt">
    <div class="container">
      <div class="section__head">
        <p class="eyebrow"><?= esc($sp['eyebrow']) ?></p>
        <h2 class="section__title"><?= esc($sp['title']) ?></h2>
        <p class="section__lead"><?= esc($sp['lead']) ?></p>
      </div>
      <div class="specs">
        <dl class="specs__table reveal">
<?php foreach ($sp['rows'] as $r): ?>
          <div><dt><?= esc($r['label']) ?></dt><dd><?= esc($r['value']) ?></dd></div>
<?php endforeach ?>
        </dl>
        <div class="specs__side">
          <div class="specs__calc reveal">
            <h3><?= esc($sp['calcTitle']) ?></h3>
            <ul>
<?php foreach ($sp['calc'] as $x): ?>
              <li><?= esc($x) ?></li>
<?php endforeach ?>
            </ul>
          </div>
<?php if ($sp['note'] !== ''): ?>
          <p class="note"><?= esc($sp['note']) ?></p>
<?php endif ?>
          <a class="btn btn--primary" href="#zayavka" data-topic="<?= esc($sp['btnTopic']) ?>"><?= esc($sp['btnLabel']) ?> <svg class="ic"><use href="#i-arrow"/></svg></a>
        </div>
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
<?php if (page_on('roboty') || page_on('sady')): ?>
  <section class="section section--soft section--tight">
    <div class="container more">
<?php if (page_on('roboty')): $rp = S::$site['robotsPage']; ?>
      <a class="more__card reveal" href="<?= page_url('roboty') ?>">
        <span class="eyebrow"><?= esc($rp['eyebrow']) ?></span>
        <h2><?= esc($rp['h1']) ?></h2>
        <p><?= esc($rp['lead']) ?></p>
        <span class="more__go">Роботы и программы <svg class="ic"><use href="#i-arrow"/></svg></span>
      </a>
<?php endif ?>
<?php if (page_on('sady')): $gp = S::$site['gardensPage']; ?>
      <a class="more__card reveal" href="<?= page_url('sady') ?>">
        <span class="eyebrow"><?= esc($gp['eyebrow']) ?></span>
        <h2><?= esc($gp['h1']) ?></h2>
        <p><?= esc($gp['lead']) ?></p>
        <span class="more__go">Проектирование садов <svg class="ic"><use href="#i-arrow"/></svg></span>
      </a>
<?php endif ?>
    </div>
  </section>

<?php endif ?>
<?php if (section_on('documents')): ?>
  <section class="section" id="dokumenty">
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
