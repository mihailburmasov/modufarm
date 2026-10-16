<?php
// SVG-иконки. На сайте — спрайт <symbol id="i-имя">, вызов через <use href="#i-имя">.
declare(strict_types=1);

// Иконки сайта 24×24 (внутренняя разметка символа). Порядок — как в спрайте.
const ICONS = [
    'route' => '<path d="M5 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM19 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M7 17h7a3 3 0 0 0 0-6H10a3 3 0 0 1 0-6h7"/>',
    'flower' => '<circle cx="12" cy="10" r="2.2"/><path d="M12 7.8C12 5 10.5 3 9 3S7 5 9 7.5M14.2 10c2.8 0 4.8-1.5 4.8-3s-2-2-4.5 0M12 12.2c0 2.8 1.5 4.8 3 4.8s2-2 0-4.5M9.8 10C7 10 5 11.5 5 13s2 2 4.5 0"/><path d="M12 17v4"/>',
    'leafscan' => '<path d="M3 7V4a1 1 0 0 1 1-1h3M17 3h3a1 1 0 0 1 1 1v3M21 17v3a1 1 0 0 1-1 1h-3M7 21H4a1 1 0 0 1-1-1v-3"/><path d="M8 16c0-4.5 3-8 8-8.5-.3 5.4-3.6 8.5-8 8.5zM8 16l4.5-4.5"/>',
    'pulse' => '<path d="M3 12h4l2-5 4 10 2-5h6"/>',
    'cut' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M8.6 7.6 20 16M8.6 16.4 20 8"/>',
    'climate' => '<path d="M3 11 12 4l9 7v9H3z"/><path d="M10 13v3.5a2 2 0 1 0 2 0V13"/><path d="M15 14h3M15 17h3"/>',
    'fruit' => '<path d="M12 8c-1.5-1.6-4-2-5.7-.6C4.4 9 4.2 12.4 5.6 15.6 7 18.8 9.4 21 12 20c2.6 1 5-1.2 6.4-4.4 1.4-3.2 1.2-6.6-.7-8.2C16 6 13.5 6.4 12 8z"/><path d="M12 8c0-2 .6-3.6 2-5M12 6c-1.6-1.6-3.6-1.8-4.8-1"/>',
    'sprout' => '<path d="M12 21V9"/><path d="M12 13c-3.6 0-6-2.4-6-6 3.6 0 6 2.4 6 6zM12 9c0-3.3 2.2-5.5 5.5-5.5 0 3.3-2.2 5.5-5.5 5.5z"/><path d="m16 15 4 4M20 15l-4 4"/>',
    'bug' => '<rect x="8" y="7" width="8" height="13" rx="4"/><path d="M12 7v13M9.5 7a2.5 2.5 0 0 1 5 0M10 4 8.5 2.5M14 4l1.5-1.5M8 11H4M8 15.5H4.5M16 11h4M16 15.5h3.5"/>',
    'soil' => '<path d="M3 13h18M3 17h18M3 21h18"/><path d="M12 13V8"/><path d="M12 9c-2.5 0-4-1.5-4-4 2.5 0 4 1.5 4 4zM12 9c0-2.5 1.5-4 4-4 0 2.5-1.5 4-4 4z"/>',
    'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 6.5 8.5 6.5 8.5-6.5"/>',
    'arrow' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'doc' => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
    'phone' => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
    'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
];

// Иконки, которые можно выбрать для карточки программы в админке (подписи — для подсказки)
const ICON_LABELS = [
    'fruit' => 'Плод', 'sprout' => 'Побег', 'cut' => 'Срез', 'flower' => 'Цветок', 'leafscan' => 'Лист под сканером',
    'bug' => 'Вредитель', 'pulse' => 'Пульс', 'soil' => 'Почва', 'route' => 'Маршрут', 'climate' => 'Теплица и климат',
    'doc' => 'Документ', 'check' => 'Галочка', 'mail' => 'Почта', 'phone' => 'Телефон',
];

// Иконки интерфейса админки (контур 24×24)
const UI_ICONS = [
    'menu' => 'M4 7h16M4 12h16M4 17h16',
    'home' => 'M3 11 12 4l9 7M5.5 9.5V20h13V9.5',
    'layers' => 'M12 3l9 5-9 5-9-5zM3 13l9 5 9-5',
    'zoom' => 'M11 18a7 7 0 100-14 7 7 0 000 14zM20 20l-4-4M11 8v6M8 11h6',
    'clock' => 'M12 21a9 9 0 100-18 9 9 0 000 18zM12 7v5l3 2',
    'trash' => 'M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14',
    'gear' => 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-2.9-1.2l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 003 15H3a2 2 0 110-4h.1a1.7 1.7 0 001.2-2.9l-.1-.1a2 2 0 112.8-2.8l.1.1A1.7 1.7 0 0010 4.1V4a2 2 0 114 0v.1a1.7 1.7 0 002.9 1.2l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 001.2 2.9h.1a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z',
];

// Спрайт сайта — в начале каждой страницы
function site_sprite(): string
{
    $out = '<svg width="0" height="0" style="position:absolute" aria-hidden="true">' . "\n";
    foreach (ICONS as $k => $inner) $out .= '  <symbol id="i-' . $k . '" viewBox="0 0 24 24">' . $inner . "</symbol>\n";
    return $out . '</svg>';
}

// Спрайт админки: иконки сайта (для выбора) и иконки интерфейса
function sprite_svg(): string
{
    $symbols = '';
    foreach (ICONS as $k => $inner) $symbols .= '<symbol id="i-' . $k . '" viewBox="0 0 24 24">' . $inner . '</symbol>';
    foreach (UI_ICONS as $k => $d) $symbols .= '<symbol id="i-' . $k . '" viewBox="0 0 24 24"><path d="' . $d . '"/></symbol>';
    return '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $symbols . '</svg>';
}

function icon(string $name, string $cls = ''): string
{
    return '<svg class="ic' . ($cls !== '' ? ' ' . $cls : '') . '"><use href="#i-' . $name . '"/></svg>';
}

// Знак (эмблема «Ключ «Гремячий») в шапке админки — картинка сайта public/img/logo-mark.png (PNG, а не WebP: img/*.webp при заливке считаются фото клиента)
function logo_mark(): string
{
    return '<img class="logo__mark" src="' . esc(S::$base . '/img/logo-mark.png') . '" width="40" height="40" alt="">';
}
