<?php /** Нижняя строка подвала: копирайт, юр. страницы, ссылка студии. $cls — доп. класс. */ ?>
    <div class="footer__bottom<?= $cls ?>">
      <span>© <span class="js-year"><?= date('Y') ?></span> <?= esc(S::$company['brand']) ?></span>
      <div class="footer-bottom-links">
        <a href="<?= u('politika.html') ?>">Политика конфиденциальности</a>
        <a href="<?= u('soglasie.html') ?>">Согласие на обработку данных</a>
        <a class="footer-credit" href="https://sitomika.ru/?utm_source=<?= esc(cfg('studio_slug')) ?>&amp;utm_medium=footer&amp;utm_campaign=client-sites" target="_blank" rel="noopener">Разработано в sitomika.ru</a>
      </div>
    </div>
