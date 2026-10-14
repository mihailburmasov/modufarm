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
    if (!$photoOk($s['hero']['photo'] ?? null)) $err[] = 'фото первого экрана не найдено в медиатеке';

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
    $iso = $s['iso'];
    if (empty($iso['hidden'])) {
        if (($iso['pdf'] ?? '') === '' || !is_file($pdfDir . $iso['pdf'])) $err[] = 'сертификат ИСО: не выбран файл PDF';
        if (!$photoOk($iso['preview'] ?? null)) $err[] = 'сертификат ИСО: не выбрана картинка-превью';
    }
    foreach (['header', 'footer'] as $w) foreach ($s['nav'][$w] as $n) if (!isset(SECTION_ANCHORS[$n['target'] ?? ''])) $err[] = "пункт меню «{$n['label']}» ведёт в неизвестный раздел";
    if (!$s['lead']['topics']) $err[] = 'в форме заявки нужна хотя бы одна тема';
    if ($err) throw new RuntimeException('Проверьте данные: ' . implode('; ', array_unique($err)) . '.');
}

function page_home(): void
{
    emit_page('/index.html', tpl('index'), ['loc' => '/']);
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
