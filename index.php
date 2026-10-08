<?php
declare(strict_types=1);

// Yerel geliştirme sunucusu (php -S) için: var olan dosyaları doğrudan sun.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $real = is_file($file) ? (string) realpath($file) : '';
    // Apache'deki .htaccess kurallarının yerel karşılığı: app/, storage/ ve kurulum/ayar dosyaları sunulmaz.
    if ($real !== '' && !str_starts_with($real, __DIR__ . '/app') && !str_starts_with($real, __DIR__ . '/storage')
        && !preg_match('#(^|/)\.(?!well-known(/|$))#', substr($real, strlen(__DIR__)))
        && !preg_match('#(^|/)(KURULUM\.md|\.user\.ini|\.htaccess)$|\.(log|jsonl|sh|lock)$#', $real)) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';   // ayarlar, içerik deposu, duyurular, görünürlük anahtarları (feature)
housekeeping_tick();   // günlük bakım: günde en çok bir kez, yanıt gönderildikten sonra; diğer isteklerde tek stat

header_remove('X-Powered-By');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
// HTTPS ile gelen isteklerde tarayıcı siteyi 180 gün boyunca yalnızca HTTPS ile açar (HSTS).
if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
    header('Strict-Transport-Security: max-age=15552000');
}

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
// Yalnızca sade yollar yönlendirilir; ters eğik çizgi ya da çift eğik çizgi içeren adresler (ör. /%5Cevil.com/)
// başka bir siteye yönlendirme üretmesin diye olağan "bulunamadı" sayfasına düşer.
if ($uri !== '/' && str_ends_with($uri, '/') && preg_match('#^[A-Za-z0-9/_.\-]+$#D', $path) && !str_contains($path, '//')) {
    redirect(url($path));
}

/* ---------- Statik sayfalar ---------- */

// Kapalı bölümlerin sayfaları listede yoktur (bkz. site_static_routes ve feature)
$static = site_static_routes();

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

if ($path === 'yonetim' || str_starts_with($path, 'yonetim/')) {
    require APP . '/admin/index.php';
    exit;
}

if ($path === 'form' && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require APP . '/form.php';
    exit;
}

// Ziyaretçi sayacı: sayfa açılınca tarayıcının gönderdiği bildirim (çerezsiz, kişisel veri saklamaz)
if ($path === 'olc') {
    require APP . '/stats.php';
    stats_track();
    exit;
}

// Bültenden ayrılma (/bulten/ayril): e-postadaki bağlantı, bülten bölümü sitede kapalı olsa da çalışır; bu yüzden görünürlük denetiminden önce gelir
if ($path === 'bulten/ayril') {
    require APP . '/bulten.php';
    bulten_ayril_sayfasi();
    exit;
}
// Yapay zekâ erişimi: /mcp (MCP sunucusu), /oauth/* ve /.well-known/oauth-* adresleri. Kendi adresi değilse sessizce döner;
// görünürlük denetiminden önce çalışır, böylece bölümler kapalıyken de yönetim erişimi sürer.
require APP . '/mcp/routes.php';

// Panelden kapatılan bölümler (Yazılar, Duyurular, Referanslar, Kariyer, Bülten) bulunamadı döner
if (!path_enabled($path)) {
    not_found();
}

if ($path === 'sitemap.xml') {
    require APP . '/sitemap.php';
    exit;
}

// Takvim dosyası: tüm duyurular ya da tek duyuru (iCalendar)
if ($path === 'duyurular.ics') {
    ann_serve_ics(null);
}
if (preg_match('#^duyurular/([a-z0-9]+)\.ics$#', $path, $m)) {
    ann_serve_ics($m[1]);
}

// Arama motorları ve yapay zekâ ajanları için makine okunur adresler (robots.txt, llms.txt, llms-full.txt, .md sürümleri, akışlar, IndexNow anahtarı)
require APP . '/agents/routes.php';

// Sayfa paylaşım kartları: /og/{tür}/{ad}.png
if (preg_match('#^og/([a-z]+)/([a-z0-9\-]+)\.png$#', $path, $m)) {
    require APP . '/og.php';
    exit;
}

if (isset($static[$path])) {
    if (!is_file(APP . '/pages/' . $static[$path] . '.php')) {
        not_found();
    }
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

// İş ilanı: /kariyer/{adres}. Taslak, hiç yayınlanmamış ilan (ya da bölüm kapalıyken her ilan) bulunamadı döner;
// kapalı ve süresi dolmuş ilan 410 ile "ilan kapandı" sayfasını gösterir (form yok, dizine kapalı).
if (preg_match('#^kariyer/([a-z0-9\-]+)$#', $path, $m)) {
    $ilan = ilan_public_find($m[1]);
    if ($ilan === null) {
        not_found();
    }
    if (!ilan_active($ilan)) {
        http_response_code(410);
        header('X-Robots-Tag: noindex, follow');
    }
    render('ilan', ['ilan' => $ilan]);
    exit;
}

not_found();
