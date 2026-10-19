<?php
// Страницы сайта и проверка данных перед сборкой.
// Если данные не проходят проверку, сборка падает — админка возвращает файлы как были и показывает сообщение.
declare(strict_types=1);

function validate_data(): void
{
    $s = S::$site;
    $err = [];
    $pdfDir = rtrim(cfg('public_dir'), '/\\') . '/docs/';
    $photoOk = fn($id) => is_string($id) && isset(S::$photoIndex[$id]);

    if (!phones()) $err[] = 'нужен хотя бы один телефон';
    foreach (['hero' => 'первый экран главной', 'robotsPage' => 'первый экран страницы «Роботы»', 'modules' => 'модульный комплекс от 10 × 50 м', 'principle' => 'устройство теплицы', 'robotics' => 'роботизированный уход', 'frame' => 'каркас'] as $key => $name) {
        $id = $s[$key]['photo'] ?? null;
        if (in_array($key, ['principle', 'robotics', 'frame'], true) && $id === '') continue; // без картинки можно
        if (!$photoOk($id)) $err[] = "фото в разделе «{$name}» не найдено в медиатеке";
    }
    if (!isset(SECTION_ANCHORS[$s['hero']['btnSecondTarget'] ?? ''])) $err[] = 'вторая кнопка первого экрана ведёт в неизвестный раздел';

    $nums = [];
    foreach (S::$docs as $i => $d) {
        $label = 'свидетельство ' . ($d['num'] !== '' ? '№ ' . $d['num'] : ($i + 1));
        if ($d['num'] === '') $err[] = "у документа " . ($i + 1) . ' не указан номер';
        if (isset($nums[$d['num']])) $err[] = "номер {$d['num']} указан у двух документов";
        $nums[$d['num']] = true;
        if (($d['pdf'] ?? '') === '' || !is_file($pdfDir . $d['pdf'])) $err[] = "$label: не выбран файл PDF";
        if (!$photoOk($d['preview'] ?? null)) $err[] = "$label: не выбрана картинка-превью";
    }
    foreach ($s['programs']['groups'] as $g) foreach ($g['cards'] as $card) {
        if (!isset(ICONS[$card['icon'] ?? ''])) $err[] = "программа «{$card['title']}»: не выбрана иконка";
        $num = $card['doc'] ?? '';
        if ($num !== '' && !isset(S::$docByNum[$num])) $err[] = "программа «{$card['title']}» ссылается на свидетельство № $num, которого нет в разделе «Документы»";
    }
    if (!all_program_cards()) $err[] = 'в разделе «Направления» нет ни одной программы';
    foreach (['iso' => 'сертификат ИСО', 'sro' => 'выписка СРО'] as $key => $label) {
        $c = $s[$key];
        if (!empty($c['hidden'])) continue;
        if (($c['pdf'] ?? '') === '' || !is_file($pdfDir . $c['pdf'])) $err[] = "$label: не выбран файл PDF";
        if (!$photoOk($c['preview'] ?? null)) $err[] = "$label: не выбрана картинка-превью";
    }
    $cc = $s['calc'];
    if (section_on('calc')) {
        if (!$cc['covers']) $err[] = 'в калькуляторе нужен хотя бы один вариант покрытия';
        foreach (['Spans' => 'ширина', 'Lengths' => 'длина'] as $k => $label) {
            $max = (int)($cc['max' . $k] ?? 0); $def = (int)($cc['def' . $k] ?? 0);
            if ($max < 1) $err[] = "калькулятор: $label — наибольшее значение должно быть не меньше 1";
            elseif ($def < 1 || $def > $max) $err[] = "калькулятор: $label по умолчанию должна быть от 1 до $max";
        }
    }
    foreach (['header', 'footer'] as $w) foreach ($s['nav'][$w] as $n) if (!isset(SECTION_ANCHORS[$n['target'] ?? ''])) $err[] = "пункт меню «{$n['label']}» ведёт в неизвестный раздел";
    if (!$s['lead']['topics']) $err[] = 'в форме заявки нужна хотя бы одна тема';
    if ($err) throw new RuntimeException('Проверьте данные: ' . implode('; ', array_unique($err)) . '.');
}

// Страница с разделами в общей оболочке (шапка с меню, блок заявки, подвал)
function section_page(string $page, string $tplName, array $meta, string $edit, string $loc): void
{
    S::$page = $page;
    $html = tpl('layout', [
        'title' => $meta['title'], 'description' => $meta['description'],
        'ogTitle' => $meta['ogTitle'] ?? $meta['title'], 'ogDescription' => $meta['ogDescription'] ?? $meta['description'],
        'loc' => $loc, 'edit' => $edit, 'ld' => $page === 'index', 'body' => tpl($tplName),
    ]);
    S::$page = 'index';
    emit_page('/' . PAGES[$page][0], $html, ['loc' => $loc]);
}

function page_home(): void
{
    section_page('index', 'index', S::$site['meta'], '', '/');
}

function page_robots(): void
{
    if (page_on('roboty')) section_page('roboty', 'roboty', S::$site['robotsPage']['meta'], 'section/robotsPage', '/roboty.html');
}

function page_gardens(): void
{
    if (page_on('sady')) section_page('sady', 'sady', S::$site['gardensPage']['meta'], 'section/gardens', '/sady.html');
}

function inner_page(string $file, array $o): void
{
    $o += ['description' => '', 'noindex' => false, 'edit' => '', 'headerContacts' => true, 'footerFull' => true];
    emit_page('/' . $file, tpl('inner', $o + ['body' => tpl(basename($file, '.html'))]), ['sitemap' => !$o['noindex']]);
}

function page_privacy(): void
{
    $b = S::$company['brand'];
    inner_page('politika.html', ['title' => 'Политика конфиденциальности — ' . $b, 'description' => 'Политика обработки персональных данных сайта ' . $b . ' (' . S::$company['legalName'] . ').', 'edit' => 'section/company']);
}

function page_consent(): void
{
    $b = S::$company['brand'];
    inner_page('soglasie.html', ['title' => 'Согласие на обработку персональных данных — ' . $b, 'description' => 'Согласие на обработку персональных данных при отправке заявки на сайте ' . $b . '.', 'edit' => 'section/company']);
}

function page_thanks(): void
{
    inner_page('spasibo.html', ['title' => 'Заявка отправлена — ' . S::$company['brand'], 'noindex' => true, 'edit' => 'section/thanks', 'headerContacts' => false, 'footerFull' => false]);
}

// 404 открывается по любому адресу, поэтому ссылки в ней — от корня сайта
function page_not_found(): void
{
    S::$prefix = S::$base . '/';
    inner_page('404.html', ['title' => 'Страница не найдена — ' . S::$company['brand'], 'noindex' => true, 'headerContacts' => false, 'footerFull' => false]);
    S::$prefix = '';
}
