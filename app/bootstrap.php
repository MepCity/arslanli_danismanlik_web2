<?php
declare(strict_types=1);

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Istanbul');

define('APP', __DIR__);
define('ROOT', dirname(__DIR__));

require APP . '/content.php';
require APP . '/changelog.php';

// Ayarların önceliği: app/config.php < storage/config.local.php < panelden kaydedilen ayarlar (storage/content/settings.json).
$GLOBALS['config']   = require APP . '/config.php';

// Sunucuya özel ayarlar (SMTP şifresi gibi): storage/config.local.php, app/config.php ile aynı biçimde bir dizi döndüren PHP dosyasıdır
// ve git'e girmez (storage/ depoda yoktur). İçindeki değerler config.php'nin üzerine yazılır; dosya yoksa ya da bozuksa sessizce atlanır.
$_local = ROOT . '/storage/config.local.php';
if (is_file($_local)) {
    try {
        $_over = (static function (string $__file) {
            return require $__file;
        })($_local);
        if (is_array($_over)) {
            $GLOBALS['config'] = array_replace_recursive($GLOBALS['config'], $_over);
        }
    } catch (Throwable $_e) {
        error_log('storage/config.local.php okunamadı: ' . $_e->getMessage());
    }
}
unset($_local, $_over, $_e);
// Panelden kaydedilen ayarlar en üsttedir (yalnızca izin verilen alanlar: bkz. settings_apply)
$GLOBALS['config'] = settings_apply($GLOBALS['config'], (array) content_get('settings', []));

// Form güvenlik anahtarı: config.php'deki varsayılan değer herkesçe bilindiği için, siteye özel anahtar ilk açılışta
// kendiliğinden oluşturulur ("x": dosya yalnızca yoksa açılır, aynı anda gelen iki istekten biri yazar).
// Anahtar dosyası config.local.php'deki 'secret' değerinin de önüne geçer.
$_key = ROOT . '/storage/secret.key';
if (!is_file($_key) && ($_fh = @fopen($_key, 'x')) !== false) {
    fwrite($_fh, bin2hex(random_bytes(32)));
    fclose($_fh);
    @chmod($_key, 0600);
}
if (is_file($_key) && ($_k = trim((string) file_get_contents($_key))) !== '') {
    $GLOBALS['config']['secret'] = $_k;
}
unset($_key, $_fh, $_k);
// Varsayılanlar app/data/*.php dosyalarından gelir; panelden yapılan değişiklikler (storage/content/*.json) üzerine yazılır.
$GLOBALS['services'] = content_get('services') ?? require APP . '/data/services.php';
$GLOBALS['posts']    = content_get('posts') ?? require APP . '/data/posts.php';
$GLOBALS['site']     = array_merge(require APP . '/data/site.php', (array) content_get('lists', []));
require APP . '/announcements.php';   // duyurular: storage/duyurular.json, yoksa app/data/duyurular.php
require APP . '/legal.php';           // yasal metinlerin maddeleri (KVKK, çerez politikası; panel: Yasal metinler)
require APP . '/ilanlar.php';         // iş ilanları (veri katmanı; ilan sayfası ve panel ekranı Aşama 2D)
require APP . '/seo.php';             // arama motoru ve yapay zekâ katmanı: sayfa başlığı, yapısal veri, paylaşım etiketleri, seo_changed() (Aşama 3A)

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
    // Yazılar bölümü kapalıysa sitede hiçbir yazı görünmez (panel $GLOBALS['posts'] ile tümünü görür);
    // taslaklar ('draft' => true) sitede hiçbir yerde görünmez
    if (!feature('blog')) {
        return [];
    }
    $posts = array_filter($GLOBALS['posts'], fn($p) => empty($p['draft']));
    uasort($posts, fn($a, $b) => strcmp($b['date'], $a['date']));
    return $posts;
}

/** Türkçe karakterleri sadeleştirerek adres parçası üretir: "Bizden Haberler" → "bizden-haberler" */
function slugify(string $text): string
{
    $map = ['ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'I' => 'i', 'İ' => 'i', 'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u'];
    $s = strtolower(strtr($text, $map));
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', $s), '-');
}

/** Statik sayfa adresleri → şablon adı; kapalı bölümlerin sayfaları çıkarılır (index.php yönlendirmesi bunu kullanır). */
function site_static_routes(): array
{
    return array_filter(site_static_routes_all(), fn($path) => path_enabled((string) $path), ARRAY_FILTER_USE_KEY);
}

/** Tüm statik sayfalar (kapalı bölümler dahil). Aşama 3A'da app/seo.php eklenirken bu iki işlev oradan kaldırılır. */
function site_static_routes_all(): array
{
    return [
        ''                           => 'home',
        'hakkimizda'                 => 'hakkimizda',
        'hizmetler'                  => 'hizmetler',
        'referans'                   => 'referans',
        'blog'                       => 'blog',
        'iletisim'                   => 'iletisim',
        'haberdarol'                 => 'haberdarol',
        'hesap-numaralarimiz'        => 'hesap',
        'duyurular'                  => 'duyurular',
        'kariyer'                    => 'kariyer',
        'kurumsal/misyonumuz'        => 'misyon',
        'kurumsal/vizyonumuz'        => 'vizyon',
        'kurumsal/mihenk-taslarimiz' => 'mihenk',
        'kurumsal/cerez-politikasi'  => 'cerez',
        'kurumsal/kvkk-aydinlatma-metni' => 'kvkk',
    ];
}

/** "Sonraki evrak" kartı için sayfa: adayların panelden kapatılmamış ilki. Adaylar: [[yol, ad, no], ...] */
function next_page(array $candidates): array
{
    foreach ($candidates as $c) {
        if (path_enabled((string) $c[0])) {
            return $c;
        }
    }
    return end($candidates);
}

/* ---------- Sayfa kaydı: "Evrak NN" numaralarının tek kaynağı ----------
 * Fihrist, üst bilgideki dizin, sayfa etiketleri ("Evrak 06 · İletişim") ve "sonraki evrak" kartları buradan beslenir;
 * şablonlara numara elle yazılmaz. Sıra: ana liste (menu), kurumsal (corp), yasal (legal). Kapalı bölümler (feature) sıradan
 * çıkar ve numaralar boşluksuz kapanır. Anahtarlar site_static_routes_all() şablon adlarıdır. */

/** Kayıt (kapalı bölümler dahil, sıralı): anahtar => [key, path, label, group]. */
function site_pages_all(): array
{
    $path = array_flip(site_static_routes_all());
    // Sayfa adları kayıt defterindedir (panel > Sayfa metinleri > "Sayfa adları"): bir sayfa orada yeniden adlandırılınca Fihristte,
    // üst bilgideki dizinde, alt bilgide ve sayfa etiketlerinde birlikte değişir. Şablonlar ad için pg_name() kullanır, yazmaz.
    $def = [
        ['home', 'menu'], ['hakkimizda', 'menu'], ['hizmetler', 'menu'], ['referans', 'menu'], ['blog', 'menu'], ['duyurular', 'menu'],
        ['kariyer', 'menu'], ['iletisim', 'menu'],
        ['haberdarol', 'corp'], ['misyon', 'corp'], ['vizyon', 'corp'], ['mihenk', 'corp'], ['hesap', 'corp'],
        ['cerez', 'legal'], ['kvkk', 'legal'],
    ];
    $out = [];
    foreach ($def as [$key, $group]) {
        $out[$key] = ['key' => $key, 'path' => (string) $path[$key], 'label' => t('genel.sayfa.' . $key), 'group' => $group];
    }
    return $out;
}

/** Açık sayfalar, numaralı ve sıralı: anahtar => [key, path, label, group, no (int), nn ("06")]. */
function site_pages(): array
{
    $out = [];
    $n = 0;
    foreach (site_pages_all() as $key => $pg) {
        if (!path_enabled($pg['path'])) {
            continue;
        }
        $n++;
        $out[$key] = $pg + ['no' => $n, 'nn' => nn($n)];
    }
    return $out;
}

/** Açık sayfalardan bir grubun listesi ('menu' | 'corp' | 'legal'), numaralarıyla. */
function site_pages_in(string $group): array
{
    return array_values(array_filter(site_pages(), fn($p) => $p['group'] === $group));
}

/** Bir sayfanın kaydı; bölümü kapalıysa null. */
function pg(string $key): ?array
{
    return site_pages()[$key] ?? null;
}

/** İki haneli evrak numarası ("06"); sayfa kayıtta yoksa ya da kapalıysa "—". */
function pg_no(string $key): string
{
    return pg($key)['nn'] ?? '—';
}

/** Sayfanın adı ("İletişim"); kayıtta olmayan anahtarda boş. */
function pg_name(string $key): string
{
    return site_pages_all()[$key]['label'] ?? '';
}

/** Sayfa etiketindeki "Evrak" sözcüğü (kayıt defteri: genel.folio.word). */
function folio_word(): string
{
    return t('genel.folio.word');
}

/** Üst bilgideki etiket (HTML): "Evrak 06 · <b>İletişim</b>". $name verilirse sayfa adının yerine geçer; $no verilirse numaranın ("06" ya da "—"). */
function folio_html(string $no, string $name): string
{
    return e(folio_word()) . ' ' . e($no) . ' · <b>' . e($name) . '</b>';
}

function pg_folio(string $key, ?string $name = null): string
{
    return folio_html(pg_no($key), $name ?? pg_name($key));
}

/** Sayfa başlığındaki küçük etiket (düz metin, kaçırılmamış): "Evrak 06 · İletişim". */
function pg_label(string $key, ?string $name = null): string
{
    return folio_word() . ' ' . pg_no($key) . ' · ' . ($name ?? pg_name($key));
}

/** Sıradaki açık sayfa (menu, corp, legal sırasıyla); son sayfadaysa ya da sayfa kapalıysa null. */
function pg_next(string $key): ?array
{
    $found = false;
    foreach (site_pages() as $k => $p) {
        if ($found) {
            return $p;
        }
        $found = $k === $key;
    }
    return null;
}

/** Hizmet dosyasının numarası: "03.4" (Hizmetler sayfasının numarası + dosya sırası). */
function svc_no($service): string
{
    $n = is_array($service) ? (int) ($service['no'] ?? 0) : (int) $service;
    return pg_no('hizmetler') . '.' . $n;
}

/** Geçerli yola karşılık gelen kayıt: [kayıt|null, tam eşleşme mi]. Alt sayfalar (hizmet dosyası, yazı, ilan) bağlı oldukları bölüme düşer. */
function pg_for_path(string $path): array
{
    $path = trim($path, '/');
    $parent = '';
    if (str_starts_with($path, 'urunler/detay/')) {
        $parent = 'hizmetler';
    } elseif (str_starts_with($path, 'blog/')) {
        $parent = 'blog';
    } elseif (str_starts_with($path, 'kariyer/')) {
        $parent = 'kariyer';
    } elseif (str_starts_with($path, 'duyurular/')) {
        $parent = 'duyurular';
    }
    $found = null;
    foreach (site_pages() as $k => $p) {
        if ($p['path'] === $path) {
            return [$p, true];
        }
        if ($k === $parent) {
            $found = $p;
        }
    }
    return [$found, false];
}

/** Herkese açık, dizine eklenecek sayfa yolları (site haritası ve ziyaretçi sayacı); kapalı bölümler yoktur. */
function site_public_paths(): array
{
    $paths = array_keys(site_static_routes());
    foreach (array_keys(services()) as $slug) {
        $paths[] = 'urunler/detay/' . $slug;
    }
    $cats = [];
    foreach (posts() as $slug => $p) {
        $paths[] = 'blog/' . $slug;
        $cats[slugify((string) ($p['category'] ?? 'Genel'))] = true;
    }
    foreach (array_keys($cats) as $c) {
        if ($c !== '') {
            $paths[] = 'blog/category/' . $c;
        }
    }
    // Aşama 2D: açık ilanlar da girer (ilan sayfası eklenince)
    if (is_file(APP . '/pages/ilan.php')) {
        foreach (ilan_published() as $x) {
            $paths[] = ilan_path($x);
        }
    }
    return $paths;
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
    // Zaman + rastgele parça: aynı saniyede üretilen belirteçler de birbirinden farklıdır (her belirteç yalnızca bir kez kullanılır, bkz. app/spam.php)
    $t = time() . '.' . bin2hex(random_bytes(6));
    return $t . '.' . hash_hmac('sha256', $t, (string) cfg('secret'));
}

/** Belirtecin yaşı (saniye); biçimi ya da imzası geçersizse null. Rastgele parçası olmayan eski biçim (zaman.imza) de doğrulanır. */
function form_token_age(?string $token): ?int
{
    $p = $token ? strrpos($token, '.') : false;
    if (!$p) {
        return null;
    }
    $msg = substr($token, 0, $p);
    if (!preg_match('/^(\d{1,12})(?:\.[a-f0-9]{12})?$/D', $msg, $m) || !hash_equals(hash_hmac('sha256', $msg, (string) cfg('secret')), substr($token, $p + 1))) {
        return null;
    }
    return time() - (int) $m[1];
}

function verify_form_token(?string $token): bool
{
    $age = form_token_age($token);
    // Çok hızlı (bot) ya da çok eski (2 saat) gönderimleri reddet.
    return $age !== null && $age >= 2 && $age <= 7200;
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
    'noindex'     => false,      // true: arama motorlarına kapalı sayfa (asıl adres etiketi basılmaz; bkz. app/seo.php)
];
$GLOBALS['page_defaults'] = $GLOBALS['page'];   // seo_render_page() bir sayfayı gerçek istekten bağımsız üretirken bu değerlerden başlar

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
    page(['id' => 'notfound', 'title' => t('notfound.seo.title'), 'folio' => t('notfound.folio')]);
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
