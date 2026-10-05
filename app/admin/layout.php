<?php
/**
 * Panel iskeleti. adm_layout() / adm_bare() tarafından çağrılır.
 * @var string $title
 * @var string $content
 * @var array  $o
 */

/**
 * BEKLEYEN BÖLÜMLER: sonraki aşamalarda v3 verisine uyarlanacak panel bölümleri. Bu listedeki bölümlerin dosyaları (app/admin/sections/)
 * kaynak siteden olduğu gibi kopyalandı ama v3'e uyarlanmadı; bu yüzden menüde ve komut paletinde görünmez, adresleri Genel bakışa
 * yönlendirir ve diğer bölümlerdeki bağlantıları gizlenir. Bir bölümü uyarlayan aşamanın ajanı yalnızca kendi satırını siler.
 * Anahtar: panel bölümü (adres parçası); değer: bölümü uyarlayacak aşama.
 */
if (!function_exists('adm_pending_sections')) {
    function adm_pending_sections(): array
    {
        return [
            'seo'         => '3A',   // SEO ve yapay zekâ
        ];
    }
}
// index.php yalnızca listeyi yüklemek için bu dosyayı çağırdığında iskelet basılmaz
if (!empty($GLOBALS['adm_defs_only'])) {
    return;
}

$bare    = !empty($o['bare']);
$section = $o['section'] ?? '';
$flash   = adm_flash();

// Panel bölümü → sitedeki görünürlük anahtarı (kapalıysa menüde "Kapalı" etiketi ve bölümde uyarı)
$sectionFeature = ['blog' => 'blog', 'duyurular' => 'duyurular', 'referanslar' => 'referanslar', 'ilanlar' => 'kariyer', 'basvurular' => 'kariyer'];
$featureNames   = ['blog' => 'Yazılar', 'duyurular' => 'Duyurular', 'referanslar' => 'Referanslar', 'kariyer' => 'Kariyer sayfası'];

$pending = adm_pending_sections();
$nav = [
    ['', [
        ['genel', '', 'Genel bakış', 'squares-four'],
        ['ziyaretciler', 'ziyaretciler', 'Ziyaretçiler', 'users-three'],
        ['gecmis', 'gecmis', 'Değişiklik geçmişi', 'clock-counter-clockwise'],
    ]],
    ['İçerik', [
        ['duyurular', 'duyurular', 'Duyurular', 'megaphone'],
        ['ilanlar', 'ilanlar', 'İş ilanları', 'file-text'],
        ['hizmetler', 'hizmetler', 'Hizmetler', 'stack'],
        ['blog', 'blog', 'Yazılar', 'newspaper'],
        ['referanslar', 'referanslar', 'Referanslar', 'images'],
        ['metinler', 'metinler', 'Sayfa metinleri', 'text-aa'],
        ['kurumsal', 'kurumsal', 'Kurumsal içerik', 'buildings'],
    ]],
    ['Gelen kutusu', [
        ['kayitlar', 'kayitlar', 'Form kayıtları', 'tray'],
        ['bulten', 'bulten', 'Bülten', 'envelope-simple'],
        ['basvurular', 'basvurular', 'İş başvuruları', 'briefcase'],
    ]],
    ['Ayarlar', [
        ['gorunurluk', 'gorunurluk', 'Görünürlük', 'eye'],
        ['ayarlar', 'ayarlar', 'İletişim ve şirket', 'gear'],
        ['seo', 'seo', 'SEO ve yapay zekâ', 'robot'],
        ['mcp', 'mcp', 'Yapay zekâ erişimi', 'plugs-connected'],
        ['guvenlik', 'guvenlik', 'Güvenlik ve yedek', 'shield-check'],
    ]],
];

// Bekleyen bölümler (bkz. adm_pending_sections) menüde ve komut paletinde yer almaz
$nav = array_values(array_filter(array_map(function ($g) use ($pending) {
    $g[1] = array_values(array_filter($g[1], fn($i) => !isset($pending[$i[0]])));
    return $g;
}, $nav), fn($g) => $g[1]));

// Komut paleti (⌘K) için arama dizini
$palette = [];
if (!$bare) {
    foreach ($nav as [$g, $items]) {
        foreach ($items as [$key, $path, $label, $ic]) {
            $palette[] = ['t' => $label, 'k' => 'Bölüm', 'u' => adm_url($path)];
        }
    }
    $palette[] = ['t' => 'Yeni duyuru ekle', 'k' => 'İşlem', 'u' => adm_url('duyurular/yeni')];
    if (!isset($pending['ilanlar'])) $palette[] = ['t' => 'Yeni ilan ekle', 'k' => 'İşlem', 'u' => adm_url('ilanlar/yeni')];
    $palette[] = ['t' => 'Yeni yazı ekle', 'k' => 'İşlem', 'u' => adm_url('blog/yeni')];
    if (!isset($pending['bulten'])) $palette[] = ['t' => 'Bülten e-postası yaz', 'k' => 'İşlem', 'u' => adm_url('bulten/yeni')];
    $palette[] = ['t' => 'Şifre değiştir', 'k' => 'İşlem', 'u' => adm_url('guvenlik')];
    if (!isset($pending['hizmetler'])) foreach (services() as $slug => $s) $palette[] = ['t' => $s['title'], 'k' => 'Hizmet', 'u' => adm_url('hizmetler/' . $slug)];
    foreach ($GLOBALS['posts'] as $slug => $p) $palette[] = ['t' => $p['title'], 'k' => empty($p['draft']) ? 'Yazı' : 'Taslak yazı', 'u' => adm_url('blog/' . $slug)];
    if (!isset($pending['metinler']) && function_exists('texts_registry')) foreach (texts_registry() as $gid => $g) $palette[] = ['t' => 'Metinler: ' . ($g['label'] ?? $gid), 'k' => 'Sayfa metinleri', 'u' => adm_url('metinler/' . $gid)];
    foreach (ann_all() as $a) $palette[] = ['t' => $a['title'], 'k' => 'Duyuru', 'u' => adm_url('duyurular/' . $a['id'])];
    if (!isset($pending['ilanlar'])) foreach (ilan_all() as $x) $palette[] = ['t' => $x['title'], 'k' => 'İş ilanı', 'u' => adm_url('ilanlar/' . $x['id'])];
}
$unread = $bare ? [] : adm_unread();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Yönetim · <?= e(cfg('short_name') ?: cfg('name')) ?></title>
<link rel="icon" href="<?= url('assets/img/favicon.png') ?>">
<link rel="stylesheet" href="<?= asset('admin/fonts.css') ?>">
<link rel="stylesheet" href="<?= asset('admin/admin.css') ?>">
<?php foreach (glob(ROOT . '/assets/admin/parts/*.css') ?: [] as $_css): ?><link rel="stylesheet" href="<?= asset('admin/parts/' . basename($_css)) ?>">
<?php endforeach; ?>
</head>
<body class="<?= $bare ? 'is-bare' : 'is-app' ?>">
<?php if ($bare): ?>
  <main class="bare"><?= $content ?></main>
<?php else: ?>
<a class="skip" href="#main">İçeriğe geç</a>
<div class="app">
  <aside class="side" id="side" aria-label="Yönetim menüsü">
    <a class="side__brand" href="<?= adm_url() ?>">
      <img src="<?= asset('admin/logo-light.webp') ?>" alt="<?= e(cfg('name')) ?>" width="132" height="48">
      <span>Yönetim</span>
    </a>
    <nav class="side__nav">
      <?php foreach ($nav as [$group, $items]): ?>
        <div class="side__group">
          <?php if ($group !== ''): ?><p class="side__label"><?= e($group) ?></p><?php endif; ?>
          <?php foreach ($items as [$key, $path, $label, $ic]):
              $badge = $unread[$key] ?? 0;
              $off   = isset($sectionFeature[$key]) && !feature($sectionFeature[$key]); ?>
            <a class="side__link<?= $section === $key ? ' is-on' : '' ?>" href="<?= adm_url($path) ?>"<?= $section === $key ? ' aria-current="page"' : '' ?>>
              <?= ui_icon($ic) ?><span><?= e($label) ?></span>
              <?php if ($badge): ?><em class="side__badge" title="<?= $badge ?> yeni kayıt"><?= $badge > 99 ? '99+' : $badge ?></em><?php elseif ($off): ?><em class="side__off" title="Sitede kapalı">Kapalı</em><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="side__foot">
      <a class="side__site" href="<?= url() ?>" target="_blank" rel="noopener"><?= ui_icon('arrow-square-out') ?><span>Siteyi aç</span></a>
      <form method="post" action="<?= adm_url('cikis') ?>"><?= adm_csrf_field() ?><button class="side__out" type="submit"><?= ui_icon('sign-out') ?><span>Çıkış yap</span></button></form>
    </div>
  </aside>
  <div class="side__scrim" data-side-close></div>

  <div class="main">
    <header class="top">
      <button class="top__menu" type="button" data-side-open aria-controls="side" aria-label="Menüyü aç"><?= ui_icon('list') ?></button>
      <div class="top__titles">
        <?php if (!empty($o['crumbs'])): ?>
          <nav class="crumbs" aria-label="Konum">
            <?php foreach ($o['crumbs'] as [$cl, $cu]): ?><a href="<?= e($cu) ?>"><?= e($cl) ?></a><?= ui_icon('caret-right') ?><?php endforeach; ?>
          </nav>
        <?php endif; ?>
        <h1 class="top__title"><?= e($title) ?></h1>
        <?php if (!empty($o['subtitle'])): ?><p class="top__sub"><?= $o['subtitle'] ?></p><?php endif; ?>
      </div>
      <div class="top__actions">
        <?= $o['actions'] ?? '' ?>
        <button class="top__search" type="button" data-palette-open aria-label="Panelde ara">
          <?= ui_icon('magnifying-glass') ?><span>Ara</span><kbd>⌘K</kbd>
        </button>
      </div>
    </header>

    <main class="content<?= !empty($o['wide']) ? ' content--wide' : '' ?>" id="main" tabindex="-1">
      <?php if (isset($sectionFeature[$section]) && !feature($sectionFeature[$section])): ?>
        <div class="alert alert--off" role="status"><?= ui_icon('eye-slash') ?><div><strong><?= e($featureNames[$sectionFeature[$section]]) ?> şu an sitede kapalı.</strong> Ziyaretçiler bu bölümü görmüyor; buradaki içeriği hazırlamaya devam edebilirsiniz. <a href="<?= adm_url('gorunurluk') ?>">Görünürlük ayarından açın</a></div></div>
      <?php endif; ?>
      <?= $content ?>
    </main>
  </div>
</div>

<?php if (!empty($o['form'])): ?>
<div class="savebar" data-savebar data-form="<?= e($o['form']) ?>" hidden>
  <p><?= ui_icon('warning-circle') ?><span>Kaydedilmemiş değişiklikler var</span></p>
  <div>
    <button class="btn btn--ghost btn--sm" type="button" data-savebar-reset>Vazgeç</button>
    <button class="btn btn--sm" type="submit" form="<?= e($o['form']) ?>"><?= ui_icon('floppy-disk') ?>Kaydet <kbd>⌘S</kbd></button>
  </div>
</div>
<?php endif; ?>

<div class="pal" data-palette hidden>
  <div class="pal__box" role="dialog" aria-modal="true" aria-label="Panelde ara">
    <div class="pal__input"><?= ui_icon('magnifying-glass') ?><input type="search" placeholder="Bölüm, hizmet, yazı, duyuru ya da ilan arayın" data-palette-input aria-label="Ara" autocomplete="off"><kbd>Esc</kbd></div>
    <ul class="pal__list" data-palette-list role="listbox"></ul>
  </div>
</div>
<script type="application/json" data-palette-data><?= json_encode($palette, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>

<?php if ($flash): ?>
  <div class="toast toast--<?= e($flash[1]) ?>" role="status" data-toast><?= ui_icon($flash[1] === 'err' ? 'warning-circle' : 'check-circle') ?><span><?= e($flash[0]) ?></span></div>
<?php endif; ?>
<script src="<?= asset('admin/admin.js') ?>" defer></script>
</body>
</html>
