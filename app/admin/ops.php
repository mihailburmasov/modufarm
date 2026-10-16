<?php
// Операции админки: медиатека, PDF документов, корзина, история правок, заявки, настройки.
declare(strict_types=1);

function translit_slug(string $s, string $fallback = 'file'): string
{
    static $map = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'];
    $s = strtr(mb_strtolower($s), $map);
    $s = trim(preg_replace('~[^a-z0-9]+~', '-', $s), '-');
    return substr($s, 0, 60) ?: $fallback;
}

// =====================================================================
// МЕДИАТЕКА
// =====================================================================
function img_dir(): string { return rtrim(cfg('public_dir'), '/\\') . '/img'; }

function photo_cat_labels(): array
{
    $labels = doc_get('structure.json')['photoCats'] ?? [];
    foreach (doc_get('photos.json') as $key => $_) $labels[$key] ??= $key;
    return $labels;
}

// Где используется фото
function photo_usage(string $id): array
{
    $uses = [];
    $site = doc_get('site.json');
    if (($site['hero']['photo'] ?? '') === $id) $uses[] = 'фото первого экрана';
    if (($site['iso']['preview'] ?? '') === $id) $uses[] = 'превью сертификата ИСО';
    if (($site['sro']['preview'] ?? '') === $id) $uses[] = 'превью выписки СРО';
    foreach (doc_get('documents.json')['items'] as $d) if (($d['preview'] ?? '') === $id) $uses[] = 'превью свидетельства № ' . $d['num'];
    return $uses;
}

function photos_payload(): array
{
    $cats = [];
    $labels = photo_cat_labels();
    foreach (doc_get('photos.json') as $key => $list) $cats[] = ['key' => $key, 'label' => $labels[$key], 'photos' => $list];
    return ['cats' => $cats, 'base' => S::$base];
}

function next_photo_id(string $cat): string
{
    $max = 0;
    foreach (doc_get('photos.json')[$cat] ?? [] as $p) if (preg_match('~^' . preg_quote($cat, '~') . '-(\d+)$~', $p['id'], $m)) $max = max($max, (int)$m[1]);
    do { $id = $cat . '-' . str_pad((string)++$max, 2, '0', STR_PAD_LEFT); } while (is_file(img_dir() . "/$id.webp"));
    return $id;
}

// Загрузка: уменьшение и перевод в WebP (крупное 1280 px и превью 640 px — как у остальных фото сайта)
function photo_upload(string $cat, array $file, string $alt): array
{
    if (!isset(doc_get('photos.json')[$cat])) throw new InvalidArgumentException('Папка не найдена');
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException(($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE ? 'Файл слишком большой' : 'Файл не загрузился');
    if ($file['size'] > 30 * 1024 * 1024) throw new InvalidArgumentException('Файл больше 30 МБ');
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpeg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) throw new InvalidArgumentException('Нужна картинка JPG, PNG или WebP');
    if ($info[0] * $info[1] > 60_000_000) throw new InvalidArgumentException('Слишком большое разрешение');
    @ini_set('memory_limit', '512M');
    $src = match ($types[$info[2]]) { 'jpeg' => @imagecreatefromjpeg($file['tmp_name']), 'png' => @imagecreatefrompng($file['tmp_name']), 'webp' => @imagecreatefromwebp($file['tmp_name']) };
    if (!$src) throw new InvalidArgumentException('Не удалось прочитать картинку');
    if ($types[$info[2]] === 'jpeg' && function_exists('exif_read_data')) {
        $o = (int)(@exif_read_data($file['tmp_name'])['Orientation'] ?? 1);
        $rot = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($rot) { $r = imagerotate($src, $rot, 0); if ($r) { imagedestroy($src); $src = $r; } }
    }
    $id = next_photo_id($cat);
    ensure_dir(img_dir());
    [$w, $h] = webp_fit($src, 1280, 70, img_dir() . "/$id.webp");
    [$sw, $sh] = webp_fit($src, 640, 60, img_dir() . "/$id-sm.webp");
    imagedestroy($src);
    $alt = trim($alt) ?: 'Фото';
    $entry = ['id' => $id, 'w' => $w, 'h' => $h, 'sw' => $sw, 'sh' => $sh, 'alt' => mb_substr($alt, 0, 300)];
    $photos = doc_get('photos.json');
    $photos[$cat][] = $entry;
    try { commit_changes(['photos.json' => $photos]); }
    catch (Throwable $e) { @unlink(img_dir() . "/$id.webp"); @unlink(img_dir() . "/$id-sm.webp"); throw $e; }
    return $entry;
}

function webp_fit($src, int $max, int $q, string $dest): array
{
    $w = imagesx($src); $h = imagesy($src);
    $k = min(1, $max / max($w, $h));
    $nw = max(1, (int)round($w * $k)); $nh = max(1, (int)round($h * $k));
    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false); imagesavealpha($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $tmp = $dest . '.tmp';
    if (!imagewebp($dst, $tmp, $q)) throw new RuntimeException('Не удалось сохранить WebP');
    imagedestroy($dst);
    rename($tmp, $dest);
    return [$nw, $nh];
}

// Подпись и папка
function photo_update(string $id, array $v): void
{
    $photos = doc_get('photos.json');
    foreach ($photos as $cat => $list) foreach ($list as $i => $p) {
        if ($p['id'] !== $id) continue;
        $p['alt'] = mb_substr(trim((string)($v['alt'] ?? $p['alt'])), 0, 300) ?: $p['alt'];
        $to = (string)($v['cat'] ?? $cat);
        if (!isset($photos[$to])) throw new InvalidArgumentException('Папка не найдена');
        if ($to === $cat) $photos[$cat][$i] = $p;
        else { array_splice($photos[$cat], $i, 1); $photos[$to][] = $p; }
        commit_changes(['photos.json' => $photos]);
        return;
    }
    throw new InvalidArgumentException('Фото не найдено');
}

function photo_move(string $id, int $dir): void
{
    $photos = doc_get('photos.json');
    foreach ($photos as $cat => $list) foreach ($list as $i => $p) {
        if ($p['id'] !== $id) continue;
        $j = $i + $dir;
        if ($j < 0 || $j >= count($list)) return;
        [$photos[$cat][$i], $photos[$cat][$j]] = [$photos[$cat][$j], $photos[$cat][$i]];
        commit_changes(['photos.json' => $photos]);
        return;
    }
}

function photo_delete(string $id): void
{
    if ($uses = photo_usage($id)) throw new InvalidArgumentException('Фото используется: ' . implode(', ', array_unique($uses)) . '. Сначала замените его там.');
    $photos = doc_get('photos.json');
    foreach ($photos as $cat => $list) foreach ($list as $i => $p) {
        if ($p['id'] !== $id) continue;
        array_splice($photos[$cat], $i, 1);
        $files = [];
        foreach (["$id.webp", "$id-sm.webp"] as $fn) if (is_file(img_dir() . "/$fn")) $files[] = $fn;
        $tid = trash_put('photo', $id, $p['alt'], ['entry' => $p, 'cat' => $cat, 'files' => $files]);
        foreach ($files as $fn) rename(img_dir() . "/$fn", storage_path("trash/$tid/$fn"));
        try { commit_changes(['photos.json' => $photos]); }
        catch (Throwable $e) { foreach ($files as $fn) rename(storage_path("trash/$tid/$fn"), img_dir() . "/$fn"); trash_forget($tid); throw $e; }
        return;
    }
    throw new InvalidArgumentException('Фото не найдено');
}

function photo_cat_add(string $label): string
{
    $label = trim($label);
    if (mb_strlen($label) < 2) throw new InvalidArgumentException('Введите название папки');
    $key = translit_slug($label, 'papka');
    $photos = doc_get('photos.json');
    $n = 2; $base = $key;
    while (isset($photos[$key])) $key = $base . '-' . $n++;
    $photos[$key] = [];
    $st = doc_get('structure.json');
    $st['photoCats'][$key] = $label;
    commit_changes(['photos.json' => $photos, 'structure.json' => $st]);
    return $key;
}

// =====================================================================
// PDF ДОКУМЕНТОВ (public/docs). Это файлы, а не данные: история правок их не касается, удаление — в корзину.
// =====================================================================
const PDF_NAME_RE = '~^[a-z0-9][a-z0-9._-]{0,90}\.pdf$~';
function pdf_dir(): string { return rtrim(cfg('public_dir'), '/\\') . '/docs'; }

function pdf_usage(string $name): array
{
    $uses = [];
    $site = doc_get('site.json');
    if (($site['iso']['pdf'] ?? '') === $name) $uses[] = 'сертификат ИСО';
    if (($site['sro']['pdf'] ?? '') === $name) $uses[] = 'выписка СРО';
    foreach (doc_get('documents.json')['items'] as $d) if (($d['pdf'] ?? '') === $name) $uses[] = 'свидетельство № ' . $d['num'];
    return $uses;
}

function pdfs_list(): array
{
    $out = [];
    foreach (glob(pdf_dir() . '/*.pdf') ?: [] as $f) {
        $n = basename($f);
        if (!preg_match(PDF_NAME_RE, $n)) continue;
        $out[] = ['name' => $n, 'size' => filesize($f), 'date' => date('Y-m-d H:i', filemtime($f)), 'uses' => pdf_usage($n)];
    }
    usort($out, fn($a, $b) => strcmp($a['name'], $b['name']));
    return $out;
}

function pdf_upload(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new InvalidArgumentException(($file['error'] ?? 0) === UPLOAD_ERR_INI_SIZE ? 'Файл слишком большой' : 'Файл не загрузился');
    if ($file['size'] > 25 * 1024 * 1024) throw new InvalidArgumentException('Файл больше 25 МБ');
    $head = (string)file_get_contents($file['tmp_name'], false, null, 0, 5);
    if ($head !== '%PDF-') throw new InvalidArgumentException('Это не PDF-файл');
    $base = translit_slug(preg_replace('~\.pdf$~i', '', (string)($file['name'] ?? '')), 'dokument');
    $name = $base . '.pdf'; $n = 2;
    while (is_file(pdf_dir() . '/' . $name) || trash_find('pdf', $name)) $name = $base . '-' . $n++ . '.pdf';
    ensure_dir(pdf_dir());
    $tmp = pdf_dir() . '/' . $name . '.tmp';
    if (!move_uploaded_file($file['tmp_name'], $tmp)) throw new RuntimeException('Не удалось сохранить файл');
    rename($tmp, pdf_dir() . '/' . $name);
    build_site(); // на своём компьютере файл копируется в собранный сайт
    return $name;
}

function pdf_delete(string $name): void
{
    if (!preg_match(PDF_NAME_RE, $name) || !is_file(pdf_dir() . '/' . $name)) throw new InvalidArgumentException('Файл не найден');
    if ($uses = pdf_usage($name)) throw new InvalidArgumentException('Файл используется: ' . implode(', ', $uses) . '. Сначала выберите там другой файл.');
    $tid = trash_put('pdf', $name, $name, []);
    rename(pdf_dir() . '/' . $name, storage_path("trash/$tid/$name"));
    build_site();
}

// =====================================================================
// КОРЗИНА: удалённое не стирается, а переезжает сюда. Автоочистки нет.
// =====================================================================
function trash_index(): array
{
    $f = storage_path('trash/index.json');
    return is_file($f) ? read_json($f) : [];
}
function trash_save_index(array $idx): void { write_file_atomic(storage_path('trash/index.json'), json_pretty(array_values($idx))); }

function trash_put(string $type, string $ref, string $title, array $payload): string
{
    $tid = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    ensure_dir(storage_path("trash/$tid"));
    write_file_atomic(storage_path("trash/$tid/item.json"), json_pretty(['type' => $type, 'ref' => $ref, 'title' => $title] + $payload));
    $idx = trash_index();
    array_unshift($idx, ['id' => $tid, 'type' => $type, 'ref' => $ref, 'title' => $title, 'date' => date('c')]);
    trash_save_index($idx);
    return $tid;
}
function trash_forget(string $tid): void { trash_save_index(array_filter(trash_index(), fn($t) => $t['id'] !== $tid)); }
function trash_find(string $type, string $ref): ?array
{
    foreach (trash_index() as $t) if ($t['type'] === $type && $t['ref'] === $ref) return $t;
    return null;
}

function trash_restore(string $tid): string
{
    if (!preg_match('~^[0-9a-f-]+$~', $tid) || !is_file(storage_path("trash/$tid/item.json"))) throw new InvalidArgumentException('Не найдено в корзине');
    $item = read_json(storage_path("trash/$tid/item.json"));
    if ($item['type'] === 'pdf') {
        $name = $item['ref'];
        if (is_file(pdf_dir() . '/' . $name)) throw new InvalidArgumentException('Файл с таким именем уже есть');
        ensure_dir(pdf_dir());
        rename(storage_path("trash/$tid/$name"), pdf_dir() . '/' . $name);
        build_site();
        $msg = 'Документ PDF возвращён.';
    } else {
        $photos = doc_get('photos.json');
        $cat = isset($photos[$item['cat']]) ? $item['cat'] : array_key_first($photos);
        foreach ($item['files'] as $fn) rename(storage_path("trash/$tid/$fn"), img_dir() . "/$fn");
        $photos[$cat][] = $item['entry'];
        commit_changes(['photos.json' => $photos]);
        $msg = 'Фото возвращено в медиатеку.';
    }
    trash_forget($tid);
    rrmdir(storage_path("trash/$tid"));
    return $msg;
}

// =====================================================================
// ИСТОРИЯ ПРАВОК
// =====================================================================
function file_label(string $rel): string
{
    static $names = ['site.json' => 'Тексты главной страницы', 'company.json' => 'Компания и контакты', 'documents.json' => 'Свидетельства', 'photos.json' => 'Медиатека', 'structure.json' => 'Папки фото'];
    return $names[$rel] ?? $rel;
}

function revisions_list(int $limit = 200): array
{
    $out = [];
    foreach (glob(storage_path('revisions/*'), GLOB_ONLYDIR) ?: [] as $dir) {
        $rel = str_replace('__', '/', basename($dir));
        foreach (glob("$dir/*.json") ?: [] as $f) {
            $name = basename($f, '.json');
            if (!preg_match('~^(\d{4}-\d\d-\d\d)_(\d\d)-(\d\d)-(\d\d)~', $name, $m)) continue;
            $out[] = ['id' => basename($dir) . '/' . $name, 'file' => $rel, 'label' => file_label($rel), 'date' => "{$m[1]} {$m[2]}:{$m[3]}:{$m[4]}"];
        }
    }
    usort($out, fn($a, $b) => strcmp($b['date'], $a['date']));
    return array_slice($out, 0, $limit);
}

// Вернуть версию файла, какой она была ДО указанной правки
function revision_restore(string $id): void
{
    if (!preg_match('~^([a-z0-9_.-]+)/([0-9_a-z-]+)$~i', $id, $m)) throw new InvalidArgumentException('Неверная версия');
    $f = storage_path("revisions/{$m[1]}/{$m[2]}.json");
    if (!is_file($f)) throw new InvalidArgumentException('Версия не найдена');
    $rel = str_replace('__', '/', $m[1]);
    $data = read_json($f);
    commit_changes([$rel => $data], 'restore');
}

// =====================================================================
// ЗАЯВКИ (пишет app/lead.php)
// =====================================================================
function leads_list(int $limit = 300): array
{
    $out = [];
    $files = glob(storage_path('leads/*.jsonl')) ?: [];
    rsort($files);
    foreach ($files as $f) {
        $lines = array_reverse(file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);
        foreach ($lines as $l) { $j = json_decode($l, true); if ($j) $out[] = $j; if (count($out) >= $limit) break 2; }
    }
    return $out;
}

// =====================================================================
// НАСТРОЙКИ (storage/settings.json — не публикуется и не попадает в git)
// =====================================================================
function settings_get(): array
{
    $f = storage_path('settings.json');
    $s = is_file($f) ? read_json($f) : [];
    return $s + ['leadEmails' => [], 'tgToken' => '', 'tgChats' => [], 'mailFrom' => ''];
}
function settings_save(array $v): void
{
    $emails = array_values(array_filter(array_map('trim', (array)($v['leadEmails'] ?? []))));
    foreach ($emails as $e) if (!filter_var($e, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException("Неверный адрес почты: $e");
    $chats = array_values(array_filter(array_map('trim', (array)($v['tgChats'] ?? []))));
    foreach ($chats as $c) if (!preg_match('~^-?\d{3,20}$~', $c)) throw new InvalidArgumentException("Chat ID — это число, например 123456789 или -1001234567890");
    $token = trim((string)($v['tgToken'] ?? ''));
    if ($token === '••••••') $token = settings_get()['tgToken'];
    if ($token !== '' && !preg_match('~^\d{5,15}:[A-Za-z0-9_-]{30,}$~', $token)) throw new InvalidArgumentException('Токен бота выглядит неверно');
    $from = trim((string)($v['mailFrom'] ?? ''));
    if ($from !== '' && !filter_var($from, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Неверный адрес отправителя');
    write_file_atomic(storage_path('settings.json'), json_pretty(['leadEmails' => $emails, 'tgToken' => $token, 'tgChats' => $chats, 'mailFrom' => $from]));
}
