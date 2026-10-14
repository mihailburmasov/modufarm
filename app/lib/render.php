<?php
// Общие части страниц: данные сайта, экранирование, ссылки, картинки, телефоны и реквизиты.
// Сами страницы — шаблоны в app/templates/*.php, порядок сборки — app/lib/pages.php.
declare(strict_types=1);

// Данные сайта на время одной сборки
final class S
{
    public static string $base = '';
    public static string $siteUrl = '';
    public static string $buildDate = '';
    public static string $prefix = '';      // начало относительных ссылок на текущей странице ('' или '/база/' для 404)
    public static array $company = [];
    public static array $site = [];
    public static array $docs = [];         // свидетельства (documents.json → items)
    public static array $docByNum = [];
    public static array $photos = [];       // папка => [фото]
    public static array $photoIndex = [];   // id => фото
    public static array $structure = [];
}

// Якоря разделов главной: куда могут вести пункты меню (ключ раздела site.json => якорь)
const SECTION_ANCHORS = [
    'napravleniya' => ['programs', 'Направления (программы)'],
    'robot' => ['robot', 'Робот под культуру'],
    'teplicy' => ['greenhouse', 'Теплицы под ключ'],
    'sady' => ['gardens', 'Проектирование садов'],
    'etapy' => ['steps', 'Этапы работы'],
    'dokumenty' => ['documents', 'Документы'],
    'zayavka' => ['lead', 'Заявка и контакты'],
];

function load_site_data(): void
{
    $d = cfg('data_dir');
    S::$siteUrl = rtrim((string)cfg('site_url'), '/');
    S::$base = rtrim((string)cfg('base_path'), '/');
    S::$buildDate = date('Y-m-d');
    S::$prefix = '';
    S::$company = read_json("$d/company.json");
    S::$site = read_json("$d/site.json");
    S::$docs = read_json("$d/documents.json")['items'];
    S::$photos = read_json("$d/photos.json");
    S::$structure = read_json("$d/structure.json");
    S::$photoIndex = [];
    foreach (S::$photos as $list) foreach ($list as $p) S::$photoIndex[$p['id']] = $p;
    S::$docByNum = [];
    foreach (S::$docs as $doc) S::$docByNum[$doc['num']] = $doc;
}

// Экранирование для HTML. Неразрывный пробел выводится как &nbsp; — так он виден в исходнике страницы.
function esc($s): string
{
    return str_replace(['&', '<', '>', '"', "\u{00A0}"], ['&amp;', '&lt;', '&gt;', '&quot;', '&nbsp;'], (string)($s ?? ''));
}

// Ссылки внутри сайта — относительные: сайт работает и в корне домена, и в подпапке
function u(string $p): string { return S::$prefix . $p; }
// css/js с версией по содержимому: после обновления файла браузеры сразу берут новый
function asset(string $p): string
{
    static $cache = [];
    $f = rtrim(cfg('public_dir'), '/\\') . '/' . $p;
    $cache[$p] ??= is_file($f) ? substr(md5_file($f), 0, 10) : S::$buildDate;
    return u($p) . '?v=' . $cache[$p];
}
function abs_url(string $p): string { return S::$siteUrl . S::$base . $p; }
// Значение для JSON внутри <script>: «<» не может закрыть тег
function js($v): string { return json_encode($v, JSON_FLAGS | JSON_HEX_TAG | JSON_UNESCAPED_LINE_TERMINATORS); }

// ---------- фото ----------
function photo(string $id): array
{
    if (!isset(S::$photoIndex[$id])) throw new RuntimeException('Нет фото ' . $id);
    return S::$photoIndex[$id];
}
function img_url(string $id, string $suffix = ''): string { return u('img/' . $id . $suffix . '.webp'); }
// Картинка документа: небольшие превью — одним файлом, крупные загруженные фото — с уменьшенной копией
function doc_img(string $id, string $sizes): string
{
    $p = photo($id);
    $srcset = $p['w'] > 600 ? ' srcset="' . img_url($id, '-sm') . ' ' . $p['sw'] . 'w, ' . img_url($id) . ' ' . $p['w'] . 'w" sizes="' . $sizes . '"' : '';
    return '<img src="' . img_url($id) . '"' . $srcset . ' width="' . $p['w'] . '" height="' . $p['h'] . '" loading="lazy" alt="' . esc($p['alt']) . '">';
}
function pdf_url(string $file): string { return u('docs/' . rawurlencode($file)); }

// ---------- телефоны и реквизиты ----------
function phones(): array { return S::$company['phones']; }
function phone0(): array { return S::$company['phones'][0]; }
// +79296604424 -> +7-929-660-44-24 (для микроразметки)
function tel_ld(string $tel): string
{
    return preg_match('~^\+7(\d{3})(\d{3})(\d{2})(\d{2})$~', $tel, $m) ? "+7-{$m[1]}-{$m[2]}-{$m[3]}-{$m[4]}" : $tel;
}
function phone_a(array $p, bool $goal = true, string $inner = ''): string
{
    return '<a href="tel:' . esc($p['tel']) . '"' . ($goal ? ' data-goal="phone_click"' : '') . '>' . $inner . esc($p['display']) . '</a>';
}
function mail_a(bool $goal = true, string $cls = '', string $inner = ''): string
{
    $m = S::$company['email'];
    return '<a' . ($cls !== '' ? ' class="' . $cls . '"' : '') . ' href="mailto:' . esc($m) . '"' . ($goal ? ' data-goal="email_click"' : '') . '>' . $inner . esc($m) . '</a>';
}
// Строка контактов в подвале
function footer_contacts(bool $goal): string
{
    return implode(' ', array_map(fn($p) => phone_a($p, $goal), phones())) . ' ' . mail_a($goal, 'footer__mail');
}
function address_full(): string
{
    $c = S::$company;
    return implode(', ', array_filter([$c['postalCode'], $c['region'], $c['district'], $c['locality'], $c['street']], fn($x) => (string)$x !== ''));
}
function legal_footer_html(): string
{
    $c = S::$company;
    return esc($c['legalName']) . ' · ОГРН ' . esc($c['ogrn']) . ' · ИНН ' . esc($c['inn']) . '<br>' . esc(address_full());
}
// Адрес сайта в юридических текстах; пока не задан — подсвечен как незаполненный
function site_address_html(): string
{
    $a = trim((string)(S::$company['siteAddress'] ?? ''));
    return $a !== '' ? esc($a) : '<span class="todo">[адрес сайта]</span>';
}
function date_ru(string $ymd): string
{
    static $m = ['', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
    if (!preg_match('~^(\d{4})-(\d\d)-(\d\d)$~', $ymd, $x)) return esc($ymd);
    return (int)$x[3] . ' ' . $m[(int)$x[2]] . ' ' . $x[1] . ' г.';
}

// ---------- разделы главной ----------
function section_on(string $key): bool { return empty(S::$site[$key]['hidden']); }
// Пункты меню, которые ведут на видимые разделы
function nav_items(string $which): array
{
    return array_values(array_filter(S::$site['nav'][$which], fn($n) => isset(SECTION_ANCHORS[$n['target']]) && section_on(SECTION_ANCHORS[$n['target']][0])));
}
function all_program_cards(): array
{
    $out = [];
    foreach (S::$site['programs']['groups'] as $g) foreach ($g['cards'] as $c) $out[] = $c;
    return $out;
}
function cards_cols(int $n): string { return $n === 3 ? 'cards--3' : ($n >= 4 ? 'cards--4' : 'cards--2'); }

// Шаблон страницы из app/templates: переменные передаются массивом
function tpl(string $name, array $vars = []): string
{
    extract($vars);
    ob_start();
    try { require APP_DIR . '/templates/' . $name . '.php'; }
    catch (Throwable $e) { ob_end_clean(); throw $e; }
    return (string)ob_get_clean();
}
