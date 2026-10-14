<?php
// Настройки по умолчанию — для работы на своём компьютере.
// Рядом может лежать app/config.local.php и переопределять нужные ключи (пример — config.local.example.php).
// На хостинге этот файл создаёт deploy.php init из app/config.server.php.
return [
    // Адрес сайта без слеша в конце и подпапка, если сайт лежит не в корне домена.
    // DOMAIN — заглушка, пока у сайта нет домена (на превью GitHub Pages адрес задаётся переменными SITE_URL и SITE_BASE).
    'site_url'    => 'https://DOMAIN',
    'base_path'   => '',
    'studio_slug' => 'modufarm',

    // Папки. data — единственный источник правды (тексты, контакты, документы, фото).
    // public — статика (css, js, шрифты, картинки, PDF). out — куда пишутся готовые страницы (корень сайта).
    // Если public и out — одна папка (так на хостинге), статика не копируется.
    'data_dir'    => ROOT_DIR . '/data',
    'public_dir'  => ROOT_DIR . '/public',
    'out_dir'     => ROOT_DIR . '/dist',
    'storage_dir' => ROOT_DIR . '/storage',
];
