<?php
declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Istanbul');

define('APP', __DIR__);
define('ROOT', dirname(__DIR__));

$GLOBALS['config']   = require APP . '/config.php';
$GLOBALS['services'] = require APP . '/data/services.php';
$GLOBALS['posts']    = require APP . '/data/posts.php';
$GLOBALS['site']     = require APP . '/data/site.php';

function cfg(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function site(string $key)
{
    return $GLOBALS['site'][$key] ?? null;
}

function services(): array
{
    return $GLOBALS['services'];
}

function posts(): array
{
    $posts = $GLOBALS['posts'];
    uasort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
    return $posts;
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Sitenin kurulu olduğu alt klasör (kök dizinde ise boş). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $base = rtrim($dir, '/');
    }
    return $base;
}

function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return base_path() . '/' . $path;
}

function absolute_url(string $path = ''): string
{
    return rtrim((string) cfg('url'), '/') . '/' . ltrim($path, '/');
}

/** Önbellek kırıcı sürüm parametresiyle dosya adresi. */
function asset(string $path): string
{
    $file = ROOT . '/assets/' . ltrim($path, '/');
    $v    = is_file($file) ? substr(base_convert((string) filemtime($file), 10, 36), -6) : '0';
    return url('assets/' . ltrim($path, '/')) . '?v=' . $v;
}

function icon(string $name, string $class = 'ico'): string
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file = APP . '/icons/' . $name . '.svg';
        $cache[$name] = is_file($file) ? (string) file_get_contents($file) : '';
    }
    return str_replace('<svg ', '<svg class="' . e($class) . '" aria-hidden="true" focusable="false" ', $cache[$name]);
}

function service_url(string $slug): string
{
    return url('urunler/detay/' . $slug);
}

function post_url(string $slug): string
{
    return url('blog/' . $slug);
}

function years_active(): int
{
    return (int) date('Y') - (int) cfg('founded');
}

function tr_date(string $ymd): string
{
    $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $t = strtotime($ymd);
    return date('j', $t) . ' ' . $months[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function reading_time(string $html): int
{
    $words = count(preg_split('/\s+/u', trim(strip_tags($html)), -1, PREG_SPLIT_NO_EMPTY));
    return max(1, (int) ceil($words / 180));
}

/** Türkçe karakterlere uygun büyük harf. */
function tr_upper(string $s): string
{
    return mb_strtoupper(strtr($s, ['i' => 'İ', 'ı' => 'I']));
}

/* ---------- Form güvenliği: çerez gerektirmeyen imzalı belirteç ---------- */

function form_token(): string
{
    $t = (string) time();
    return $t . '.' . hash_hmac('sha256', $t, (string) cfg('secret'));
}

function verify_form_token(?string $token): bool
{
    if (!$token || !str_contains($token, '.')) {
        return false;
    }
    [$t, $sig] = explode('.', $token, 2);
    if (!ctype_digit($t) || !hash_equals(hash_hmac('sha256', $t, (string) cfg('secret')), $sig)) {
        return false;
    }
    $age = time() - (int) $t;
    // Çok hızlı (bot) ya da çok eski (2 saat) gönderimleri reddet.
    return $age >= 2 && $age <= 7200;
}

/* ---------- Sayfa durumu ---------- */

$GLOBALS['page'] = [
    'id'          => 'home',     // body[data-page] ve sayfa betiği (assets/js/pages/{id}.js)
    'title'       => '',
    'description' => '',
    'theme'       => 'paper',    // paper | dark
    'folio'       => '',         // üst bilgide görünen evrak etiketi
    'canonical'   => '',
    'image'       => '',
    'schema'      => [],
];

function page(array $values = []): array
{
    if ($values) {
        $GLOBALS['page'] = array_merge($GLOBALS['page'], $values);
    }
    return $GLOBALS['page'];
}

function render(string $view, array $vars = []): void
{
    // index.php'de çözülen geçerli yol: canonical, og:url, fihristteki işaret ve form dönüşleri bunu kullanır
    $path = (string) ($GLOBALS['path'] ?? '');
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP . '/pages/' . $view . '.php';
    $content = ob_get_clean();
    require APP . '/partials/layout.php';
}

function not_found(): void
{
    http_response_code(404);
    page(['id' => 'notfound', 'title' => 'Sayfa bulunamadı', 'folio' => 'Eksik evrak']);
    render('404');
    exit;
}

function redirect(string $to, int $code = 301): void
{
    header('Location: ' . $to, true, $code);
    exit;
}

/** Başlıklarda son iki kelimeyi birbirine bağlar (tek kelimelik satır kalmasın). */
function nowidow(string $text): string
{
    $text = e($text);
    $pos  = strrpos($text, ' ');
    return $pos === false ? $text : substr($text, 0, $pos) . '&nbsp;' . substr($text, $pos + 1);
}

/** Satır sonlarını <br> yapar; metin önce kaçırılır. */
function lines(string $text): string
{
    return implode('<br>', array_map('e', explode("\n", $text)));
}

/** Sayıyı iki haneli yazar: 3 -> 03 */
function nn(int $n): string
{
    return str_pad((string) $n, 2, '0', STR_PAD_LEFT);
}

/** Bugünün tarihi, resmi yazı biçiminde: 02/10/2026 */
function today_official(): string
{
    return date('d/m/Y');
}

/** El çizimi ok (düğmeler ve bağlantılar için). */
function arrow(string $class = 'arw'): string
{
    return '<svg class="' . e($class) . '" viewBox="0 0 48 20" aria-hidden="true" focusable="false"><path pathLength="1" d="M2 11.5c9-1.6 22-2.4 41-1.2M35 3.5c2.6 2.4 5.4 4.6 8.6 6.9-3 2.1-5.6 4.3-8 7"/></svg>';
}

/**
 * El çizimi kalem işareti: bir kelimenin etrafına halka ya da altına çizgi.
 * Kullanım: annot('Ar-Ge', 'circle'), annot('tamamı', 'under', 'red')
 */
function annot(string $text, string $shape = 'under', string $tone = ''): string
{
    $paths = [
        'circle' => ['viewBox' => '0 0 200 80', 'd' => 'M38 14C78 1 162 3 189 27c17 17-21 44-90 48C35 79 3 62 9 39 14 21 58 8 128 7'],
        'under'  => ['viewBox' => '0 0 200 16', 'd' => 'M3 10c38-5 79 3 117-3 26-3 52-2 77 1'],
    ];
    $p = $paths[$shape] ?? $paths['under'];
    return '<span class="annot-wrap">' . $text . '<svg class="annot annot--' . $shape . ($tone ? ' annot--' . $tone : '') . '" viewBox="' . $p['viewBox'] . '" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path pathLength="1" d="' . $p['d'] . '"/></svg></span>';
}
