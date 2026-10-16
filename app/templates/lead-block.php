<?php
// Блок заявки — на каждой странице с разделами, чтобы кнопки с темой отмечали её в форме без перехода.
$c = S::$company; $ld = S::$site['lead'];
// По умолчанию отмечена тема своей страницы (если она есть среди тем), иначе первая
$pageTopic = ['roboty' => S::$site['robotsPage']['btnTopic'], 'sady' => S::$site['gardens']['btnTopic']][S::$page] ?? '';
$checked = in_array($pageTopic, array_column($ld['topics'], 'value'), true) ? $pageTopic : $ld['topics'][0]['value'];
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

      <form class="form" action="<?= u('lead.php') ?>" method="post" novalidate data-form>
        <input type="hidden" name="form_name" value="<?= S::$page === 'index' ? 'main' : S::$page ?>">
        <div class="form__trap" aria-hidden="true"><label>Сайт <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <fieldset class="form__topics">
          <legend><?= esc($ld['topicsLabel']) ?></legend>
<?php foreach ($ld['topics'] as $t): ?>
          <label class="chip"><input type="radio" name="topic" value="<?= esc($t['value']) ?>"<?= $t['value'] === $checked ? ' checked' : '' ?>><span><?= esc($t['label'] ?: $t['value']) ?></span></label>
<?php endforeach ?>
        </fieldset>

        <div class="form__row">
          <label class="field"><span>Имя</span><input type="text" name="name" autocomplete="name" maxlength="80" placeholder="Как к вам обращаться"></label>
          <label class="field"><span>Телефон *</span><input type="tel" name="phone" autocomplete="tel" inputmode="tel" placeholder="+7 (___) ___-__-__" required data-phone></label>
        </div>
        <label class="field"><span>Культура и площадь</span><input type="text" name="crop" maxlength="200" placeholder="Например: лимон, 1 га"></label>
        <label class="field"><span>Задача</span><textarea name="message" rows="3" maxlength="1500" placeholder="Что нужно построить, автоматизировать или спроектировать"></textarea></label>

        <label class="consent">
          <input type="checkbox" name="consent" value="1" checked required>
          <span>Даю <a href="<?= u('soglasie.html') ?>" target="_blank">согласие на обработку персональных данных</a> и принимаю <a href="<?= u('politika.html') ?>" target="_blank">политику конфиденциальности</a></span>
        </label>

        <button class="btn btn--accent btn--block" type="submit"><?= esc($ld['submitLabel']) ?></button>
        <p class="form__status" role="status" aria-live="polite" data-status></p>
      </form>
    </div>
  </section>
