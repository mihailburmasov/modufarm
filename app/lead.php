<?php
// Приём заявок с формы сайта: проверка, запись в журнал (storage/leads), отправка на почту и в Telegram.
// Ответ — JSON. Заявка сохраняется в журнале, даже если почта или Telegram не сработали: её видно в админке.
// Поля формы — app/templates/index.php, отправка — public/js/main.js.
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/admin/ops.php';
require_once __DIR__ . '/lead-deliver.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex');

function lead_reply(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_FLAGS);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') lead_reply(405, ['ok' => false, 'error' => 'method']);

$field = fn(string $k, int $max) => mb_substr(trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', is_string($_POST[$k] ?? null) ? $_POST[$k] : '') ?? ''), 0, $max);

// Антиспам. 1) Скрытое поле, которое люди не видят и не заполняют. 2) Отправка быстрее 2 секунд после открытия страницы.
// Боту отвечаем «всё хорошо», чтобы он не узнал, что его отсекли.
if ($field('website', 200) !== '') lead_reply(200, ['ok' => true]);
if ((int)$field('elapsed', 10) < 2) lead_reply(200, ['ok' => true]);

// Телефон: 11 цифр, начинается с 7 (8 в начале заменяем)
$digits = preg_replace('~\D~', '', $field('phone', 40));
if (strlen($digits) === 11 && $digits[0] === '8') $digits = '7' . substr($digits, 1);
if (strlen($digits) !== 11 || $digits[0] !== '7') lead_reply(422, ['ok' => false, 'error' => 'phone']);
if ($field('consent', 5) !== '1') lead_reply(422, ['ok' => false, 'error' => 'consent']);

$lead = [
    'date' => date('Y-m-d H:i:s'),
    'phone' => sprintf('+7 (%s) %s-%s-%s', substr($digits, 1, 3), substr($digits, 4, 3), substr($digits, 7, 2), substr($digits, 9, 2)),
    'name' => $field('name', 80),
    'topic' => $field('topic', 100),
    'crop' => $field('crop', 200),
    'message' => $field('message', 1500),
    'calc' => $field('calc', 1000),
    'page' => $field('page', 500),
    'referrer' => $field('referrer', 500),
    'utm' => array_filter([
        'utm_source' => $field('utm_source', 120),
        'utm_medium' => $field('utm_medium', 120),
        'utm_campaign' => $field('utm_campaign', 200),
        'utm_content' => $field('utm_content', 200),
        'utm_term' => $field('utm_term', 200),
        'yclid' => $field('yclid', 60),
    ], fn($v) => $v !== ''),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
];

// Заявка всегда пишется в журнал. Лимит (30 в час с одного адреса — офис за одним IP тоже пройдёт)
// только придерживает уведомления на почту и в Telegram, чтобы бот не завалил их спамом.
try {
    $notify = with_lock(function () use (&$lead) {
        $f = storage_path('lead-rate.json');
        $all = is_file($f) ? read_json($f) : [];
        foreach ($all as $ip => $list) { $all[$ip] = array_values(array_filter($list, fn($t) => $t > time() - 3600)); if (!$all[$ip]) unset($all[$ip]); }
        $notify = count($all[$lead['ip']] ?? []) < 30;
        $all[$lead['ip']][] = time();
        write_file_atomic($f, json_pretty($all));
        if (!$notify) $lead['muted'] = true;
        ensure_dir(storage_path('leads'));
        if (file_put_contents(storage_path('leads/' . date('Y-m') . '.jsonl'), json_encode($lead, JSON_FLAGS) . "\n", FILE_APPEND | LOCK_EX) === false) throw new RuntimeException('journal');
        return $notify;
    });
} catch (Throwable $e) {
    error_log('lead: ' . $e->getMessage());
    lead_reply(500, ['ok' => false, 'error' => 'save']);
}
if (!$notify) lead_reply(200, ['ok' => true]);

$lines = [
    'Заявка с сайта' . ($lead['topic'] !== '' ? ': ' . $lead['topic'] : ''),
    'Телефон: ' . $lead['phone'],
    'Имя: ' . ($lead['name'] ?: '—'),
    'Культура и площадь: ' . ($lead['crop'] ?: '—'),
    'Задача: ' . ($lead['message'] ?: '—'),
];
if ($lead['calc'] !== '') $lines[] = 'Расчёт на сайте: ' . $lead['calc'];
if ($lead['utm']) { $lines[] = ''; foreach ($lead['utm'] as $k => $v) $lines[] = "$k: $v"; }
$lines[] = 'Страница: ' . ($lead['page'] ?: '—');
if ($lead['referrer'] !== '') $lines[] = 'Откуда пришёл: ' . $lead['referrer'];
$lines[] = 'Время: ' . $lead['date'];
$subject = 'Заявка с сайта: ' . $lead['phone'] . ($lead['topic'] !== '' ? ' — ' . $lead['topic'] : '');

// Посетителю отвечаем сразу (заявка уже в журнале), почта и Telegram уходят после ответа
$body = json_encode(['ok' => true]);
ignore_user_abort(true);
http_response_code(200);
header('Content-Length: ' . strlen($body));
header('Connection: close');
echo $body;
if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
else { while (ob_get_level() > 0) ob_end_flush(); flush(); }
lead_deliver(implode("\n", $lines), $subject);
