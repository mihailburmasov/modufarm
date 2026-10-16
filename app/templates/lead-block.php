<?php
// Блок заявки — внизу каждой страницы с разделами. Та же форма открывается во всплывающем окне (lead-modal.php).
$c = S::$company; $ld = S::$site['lead'];
?>
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

<?= tpl('lead-form', ['formName' => S::$page === 'index' ? 'main' : S::$page]) ?>
    </div>
  </section>
