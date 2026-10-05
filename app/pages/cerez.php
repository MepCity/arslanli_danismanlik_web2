<?php
/**
 * Çerez Politikası
 * Metnin bir kısmı sitenin kendi kodu taranarak kurulur: bir gün çerez, tarayıcı depolama alanı
 * ya da dış kaynaklı içerik eklenirse sayfa "kullanmıyoruz" demeyi bırakır. Yine de böyle bir
 * değişiklikten sonra metni gözden geçirin.
 * Taramanın sonucuna göre hangi fıkranın basılacağı app/legal.php içinde seçilir (fıkraların 'when' koşulu); maddelerin metni panelde Yasal metinler
 * bölümündedir. Kısaca kutusu ve sayfa başı yazıları kayıt defterindedir (app/data/texts/54-cerez.php).
 */
require_once APP . '/pages/_legal.php';

page([
    'id'          => 'legal',
    'title'       => pg_name('cerez'),
    'description' => t('cerez.seo.description'),
    'folio'       => pg_folio('cerez'),
]);

/* ---------- Sitenin kodunu tara (app/legal.php: cerez_scan) ---------- */

$scan = cerez_scan();
$usesCookies  = $scan['cookies'];
$storagePages = $scan['storage_pages'];
$embeds       = $scan['embeds'];
$external     = $scan['external'];
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
$summary[] = th($usesCookies ? 'cerez.kisaca.cerez_var' : 'cerez.kisaca.cerez_yok');
$summary[] = th('cerez.kisaca.analiz');
if (!$external) {
    $summary[] = th('cerez.kisaca.ucuncu');
}
if ($storagePages) {
    $summary[] = th('cerez.kisaca.depolama');
}
$summary[] = th('cerez.kisaca.form', $vars);

/* ---------- Maddeler: metinler Yasal metinler bölümünden, hangi fıkranın görüneceği taramadan (app/legal.php) ---------- */

$maddeler = legal_maddeler('cerez', $vars);

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
