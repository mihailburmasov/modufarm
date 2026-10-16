<?php
// Всплывающее окно с формой заявки. Открывают его кнопки-ссылки на #zayavka (main.js); без JS кнопки
// по-прежнему ведут к блоку заявки внизу страницы.
$ld = S::$site['lead']; $p0 = phone0();
?>
<dialog class="modal" data-modal aria-labelledby="modal-title">
  <div class="modal__box">
    <button class="modal__close" type="button" aria-label="Закрыть" data-modal-close><svg class="ic" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
    <div class="modal__head">
      <h2 class="modal__title" id="modal-title"><?= esc($ld['title']) ?></h2>
      <p class="modal__lead">Оставьте телефон — перезвоним и обсудим. Или позвоните сами: <?= phone_a($p0) ?></p>
    </div>
<?= tpl('lead-form', ['formName' => 'modal-' . (S::$page === 'index' ? 'main' : S::$page)]) ?>
  </div>
</dialog>
