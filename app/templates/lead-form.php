<?php
// Форма заявки: одна разметка для блока внизу страницы и для всплывающего окна.
// $formName — откуда пришла заявка (main, roboty, sady; у окна — с приставкой modal-)
$ld = S::$site['lead'];
// По умолчанию отмечена тема своей страницы (если она есть среди тем), иначе первая
$pageTopic = ['roboty' => S::$site['robotsPage']['btnTopic'], 'sady' => S::$site['gardens']['btnTopic']][S::$page] ?? '';
$checked = in_array($pageTopic, array_column($ld['topics'], 'value'), true) ? $pageTopic : $ld['topics'][0]['value'];
?>
      <form class="form" action="<?= u('lead.php') ?>" method="post" novalidate data-form>
        <input type="hidden" name="form_name" value="<?= esc($formName) ?>">
        <input type="hidden" name="calc" value="" data-calc-field>
        <div class="form__trap" aria-hidden="true"><label>Сайт <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <fieldset class="form__topics">
          <legend><?= esc($ld['topicsLabel']) ?></legend>
<?php foreach ($ld['topics'] as $t): ?>
          <label class="chip"><input type="radio" name="topic" value="<?= esc($t['value']) ?>"<?= $t['value'] === $checked ? ' checked' : '' ?>><span><?= esc($t['label'] ?: $t['value']) ?></span></label>
<?php endforeach ?>
        </fieldset>

        <div class="form__calc" data-calc-view hidden><b>Ваш расчёт</b><span data-calc-text></span></div>

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
