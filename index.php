<?php
declare(strict_types=1);

// Yerel geliştirme sunucusu (php -S) için: var olan dosyaları doğrudan sun.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && !str_starts_with(realpath($file), __DIR__ . '/app') && !str_starts_with(realpath($file), __DIR__ . '/storage')) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

/* ---------- İstenen yolu çözümle ---------- */

$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri  = rawurldecode($uri);
$base = base_path();
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$path = trim($uri, '/');
if ($path === 'index.php') {
    $path = '';
}

// Sondaki eğik çizgiyi kaldır (tek bir adres = daha iyi SEO).
if ($uri !== '/' && str_ends_with($uri, '/')) {
    redirect(url($path));
}

/* ---------- Statik sayfalar ---------- */

$static = [
    ''                           => 'home',
    'hakkimizda'                 => 'hakkimizda',
    'hizmetler'                  => 'hizmetler',
    'referans'                   => 'referans',
    'blog'                       => 'blog',
    'iletisim'                   => 'iletisim',
    'haberdarol'                 => 'haberdarol',
    'hesap-numaralarimiz'        => 'hesap',
    'kurumsal/misyonumuz'        => 'misyon',
    'kurumsal/vizyonumuz'        => 'vizyon',
    'kurumsal/mihenk-taslarimiz' => 'mihenk',
    'kurumsal/cerez-politikasi'  => 'cerez',
    'kurumsal/kvkk-aydinlatma-metni' => 'kvkk',
];

// Eski sitede kullanılmış olabilecek kısa adresler.
$aliases = [
    'urunler'      => 'hizmetler',
    'referanslar'  => 'referans',
    'kurumsal'     => 'hakkimizda',
    'contact'      => 'iletisim',
];

if (isset($aliases[$path])) {
    redirect(url($aliases[$path]));
}

if ($path === 'form' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require APP . '/form.php';
    exit;
}

if ($path === 'sitemap.xml') {
    require APP . '/sitemap.php';
    exit;
}

if (isset($static[$path])) {
    render($static[$path]);
    exit;
}

/* ---------- Dinamik sayfalar ---------- */

if (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $path, $m)) {
    $slug = $m[1];
    if (!isset(services()[$slug])) {
        not_found();
    }
    render('hizmet', ['slug' => $slug, 'service' => services()[$slug]]);
    exit;
}

if (preg_match('#^blog/category/([a-z0-9\-]+)$#', $path, $m)) {
    render('blog', ['category' => $m[1]]);
    exit;
}

if (preg_match('#^blog/([a-z0-9\-]+)$#', $path, $m)) {
    $slug = $m[1];
    if (!isset(posts()[$slug])) {
        not_found();
    }
    render('yazi', ['slug' => $slug, 'post' => posts()[$slug]]);
    exit;
}

not_found();
