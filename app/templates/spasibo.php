<?php $t = S::$site['thanks']; ?>
  <div class="container thanks">
    <div class="thanks__icon"><svg class="ic" viewBox="0 0 24 24" width="40" height="40"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></div>
    <h1><?= esc($t['title']) ?></h1>
    <p><?= esc($t['text']) ?></p>
    <a class="btn btn--primary" href="<?= u('index.html') ?>"><?= esc($t['btnLabel']) ?></a>
  </div>
