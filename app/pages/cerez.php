<?php
/**
 * Çerez Politikası
 * Metnin bir kısmı sitenin kendi kodu taranarak kurulur: bir gün çerez, tarayıcı depolama alanı
 * ya da dış kaynaklı içerik eklenirse sayfa "kullanmıyoruz" demeyi bırakır. Yine de böyle bir
 * değişiklikten sonra metni gözden geçirin.
 * Taramanın sonucuna göre hangi yazının basılacağı burada seçilir; yazıların kendisi kayıt defterindedir (app/data/texts/54-cerez.php; panelde
 * Sayfa metinleri > Çerez Politikası): aynı maddenin "_var" (kullanılıyor) ve "_yok" (kullanılmıyor) sürümleri ayrı kayıttır.
 */
require_once APP . '/pages/_legal.php';

page([
    'id'          => 'legal',
    'title'       => pg_name('cerez'),
    'description' => t('cerez.seo.description'),
    'folio'       => pg_folio('cerez'),
]);

/* ---------- Sitenin kodunu tara ---------- */

$files = array_merge(
    glob(ROOT . '/index.php') ?: [],
    glob(APP . '/*.php') ?: [],
    glob(APP . '/partials/*.php') ?: [],
    array_filter(glob(APP . '/pages/*.php') ?: [], fn($f) => !in_array(basename($f), ['cerez.php', 'kvkk.php', '_legal.php'], true)),
    glob(ROOT . '/assets/js/*.js') ?: [],
    glob(ROOT . '/assets/js/pages/*.js') ?: []
);
$usesCookies = false;
$storagePages = [];
$embeds = false;
$external = false;
foreach ($files as $f) {
    $src = (string) @file_get_contents($f);
    if (basename($f) === 'app.js') {
        // Ziyaret sayacı (bkz. app/stats.php) yalnızca yönetici tarayıcısındaki bir işareti okur; ziyaretçinin tarayıcısına bir şey yazmaz,
        // bu yüzden "tarayıcı depolama alanı kullanılıyor" taramasına girmez
        $src = (string) preg_replace('#/\* -+ Ziyaret sayacı.*?(?=/\* -+ Sayfa betiği)#su', '', $src);
    }
    if (preg_match('/\bsetcookie\s*\(|\bsession_start\s*\(|document\.cookie/', $src)) {
        $usesCookies = true;
    }
    if (str_ends_with($f, '.js') && preg_match('/\b(local|session)Storage\s*[.\[]/', $src)) {
        $storagePages[] = basename($f, '.js');
    }
    if (str_ends_with($f, '.php') && stripos($src, '<iframe') !== false) {
        $embeds = true;
    }
    if (str_ends_with($f, '.php') && preg_match('#<(script|link)[^>]+(src|href)="https?://#i', $src)) {
        $external = true;
    }
}
$pageNames = [];
foreach (['app', 'home', 'about', 'services', 'service', 'refs', 'blog', 'post', 'contact', 'signup', 'bank', 'mission', 'vision', 'stones'] as $id) {
    $pageNames[$id] = t('cerez.depolama.' . $id);
}
$storageWhere = implode(', ', array_map(fn($p) => $pageNames[$p] ?? $p, array_unique($storagePages)));
$vars = [
    'kvkk_baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '">' . e(pg_name('kvkk')) . '</a>'],
    'site_adresi'   => preg_replace('#^https?://#', '', (string) cfg('url')),
    'sayfalar'      => $storageWhere,
];

/* ---------- Kısaca ---------- */

$summary = [];
$summary[] = str_replace(['<b>', '</b>'], ['<mark>', '</mark>'], th($usesCookies ? 'cerez.kisaca.cerez_var' : 'cerez.kisaca.cerez_yok'));
$summary[] = str_replace(['<b>', '</b>'], ['<mark>', '</mark>'], th('cerez.kisaca.analiz'));
if (!$external) {
    $summary[] = str_replace(['<b>', '</b>'], ['<mark>', '</mark>'], th('cerez.kisaca.ucuncu'));
}
if ($storagePages) {
    $summary[] = str_replace(['<b>', '</b>'], ['<mark>', '</mark>'], th('cerez.kisaca.depolama'));
}
$summary[] = str_replace(['<b>', '</b>'], ['<mark>', '</mark>'], th('cerez.kisaca.form', $vars));

/* ---------- Maddeler ---------- */

$maddeler = [];

$maddeler[] = [
    'title' => t('cerez.m1.baslik'),
    'paras' => [th('cerez.m1.f1', $vars), th('cerez.m1.f2', $vars)],
    'sade'  => th('cerez.m1.sade'),
];

$maddeler[] = [
    'title' => t('cerez.m2.baslik'),
    'paras' => [th('cerez.m2.f1')],
    'sade'  => th('cerez.m2.sade'),
];

$maddeler[] = [
    'title' => t('cerez.m3.baslik'),
    'paras' => $usesCookies ? [th('cerez.m3.f1_var'), th('cerez.m3.f2_var')] : [th('cerez.m3.f1_yok'), th('cerez.m3.f2_yok')],
    'sade'  => th($usesCookies ? 'cerez.m3.sade_var' : 'cerez.m3.sade_yok'),
];

$maddeler[] = [
    'title' => t('cerez.m4.baslik'),
    'paras' => [th('cerez.m4.f1'), th('cerez.m4.f2', $vars), th('cerez.m4.f3', $vars)],
    'sade'  => th('cerez.m4.sade'),
];

$maddeler[] = [
    'title' => t('cerez.m5.baslik'),
    'paras' => $storagePages ? [th('cerez.m5.f1_var', $vars), th('cerez.m5.f2_var')] : [th('cerez.m5.f1_yok')],
    'sade'  => th($storagePages ? 'cerez.m5.sade_var' : 'cerez.m5.sade_yok'),
];

$maddeler[] = [
    'title' => t('cerez.m6.baslik'),
    'paras' => array_values(array_filter([
        $external ? null : th('cerez.m6.kendi'),
        $embeds ? th('cerez.m6.gomulu') : null,
        th(feature('blog') ? 'cerez.m6.baglanti_blog' : 'cerez.m6.baglanti'),
    ])),
    'sade'  => th('cerez.m6.sade'),
];

$maddeler[] = [
    'title' => t('cerez.m7.baslik'),
    'paras' => [th($usesCookies ? 'cerez.m7.f1_var' : 'cerez.m7.f1_yok')],
    'sade'  => th($usesCookies ? 'cerez.m7.sade_var' : 'cerez.m7.sade_yok'),
];

$maddeler[] = [
    'title' => t('cerez.m8.baslik'),
    'paras' => [th('cerez.m8.f1'), th('cerez.m8.f2')],
    'sade'  => th('cerez.m8.sade'),
];

legal_doc([
    'no'       => t('cerez.baslik.sayi'),
    'subject'  => t('cerez.baslik.konu'),
    'h1'       => t('cerez.baslik.baslik'),
    'updated'  => t('cerez.baslik.guncelleme'),
    'lead'     => t('cerez.baslik.giris'),
    'summary'  => $summary,
    'maddeler' => $maddeler,
    'next'     => ['label' => t('cerez.sonraki.ad'), 'href' => url('kurumsal/kvkk-aydinlatma-metni'), 'no' => pg_no('kvkk')],
]);
