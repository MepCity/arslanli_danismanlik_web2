<?php
/**
 * Ortak sayfa iskeleti.
 * @var string $content  Sayfa içeriği (render() tarafından)
 * @var string $path     Geçerli yol (index.php)
 */
$p     = page();
$title = $p['title'] ? $p['title'] . ' | ' . cfg('name') : cfg('name') . ' | Hibe, teşvik ve Ar-Ge danışmanlığı';
$desc  = $p['description'] ?: 'Arslanlı Yatırım & Danışmanlık: 2007’den beri TÜBİTAK, KOSGEB, Bakanlık, ihracat ve AB desteklerinde başvuru dosyası, proje yazımı ve yürütme. İstanbul.';
$canon = $p['canonical'] ?: absolute_url($path ?? '');
$image = $p['image'] ?: absolute_url('assets/img/og.jpg');
$id    = $p['id'];

$pageCss = is_file(ROOT . '/assets/css/pages/' . $id . '.css') ? asset('css/pages/' . $id . '.css') : null;
$pageJs  = is_file(ROOT . '/assets/js/pages/' . $id . '.js') ? asset('js/pages/' . $id . '.js') : null;
$vendor  = $p['vendor'] ?? [];

$current = '/' . trim($path ?? '', '/');
$isCur = fn(string $to) => ($current === '/' . trim($to, '/')) ? ' aria-current="page"' : '';

// Fihrist ve üst bilgideki dizin tek sayfa kaydından gelir (app/bootstrap.php, site_pages()): numaralar boşluksuz, kapalı bölümler yok
$menu = site_pages_in('menu');
$corp = site_pages_in('corp');
[$here, $hereExact] = pg_for_path($path ?? '');
// Etiket: "Evrak 03 · <b>Ad</b>" → kısa ekranlarda adı düşer (hdr__nm), çok dar ekranda "Evrak" sözcüğü de (hdr__w)
$folio = $p['folio'] ?: pg_folio('home');
$folioHtml = preg_match('/^Evrak\s+(\S+)\s*·\s*(.+)$/su', $folio, $fm)
    ? '<span class="hdr__ev"><span class="hdr__w">Evrak </span>' . $fm[1] . '</span><span class="hdr__nm"> · ' . $fm[2] . '</span>'
    : $folio;
// Açılışta öne çıkan duyuru (varsa): pencere, çip ve kendi dosyaları yalnızca o zaman yüklenir
$spot = function_exists('ann_featured') ? ann_featured() : null;
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if ($id === 'notfound'): ?><meta name="robots" content="noindex"><?php else: ?><link rel="canonical" href="<?= e($canon) ?>"><?php endif; ?>
<meta name="theme-color" content="<?= $p['theme'] === 'dark' ? '#121214' : '#eeeae1' ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="tr_TR">
<meta property="og:site_name" content="<?= e(cfg('name')) ?>">
<meta property="og:title" content="<?= e($p['title'] ?: cfg('name')) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canon) ?>">
<meta property="og:image" content="<?= e($image) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
<link rel="preload" href="<?= url('assets/fonts/archivo.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= url('assets/fonts/newsreader.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?php if ($pageCss): ?><link rel="stylesheet" href="<?= $pageCss ?>"><?php endif; ?>
<?php foreach (($p['styles'] ?? []) as $extraCss): ?><link rel="stylesheet" href="<?= asset('css/' . $extraCss . '.css') ?>">
<?php endforeach; ?>
<?php if ($spot): ?><link rel="stylesheet" href="<?= asset('css/spotlight.css') ?>">
<?php endif; ?>
<script>
(function(){var h=document.documentElement;h.classList.add('js');
window.__revealFallback=setTimeout(function(){h.classList.add('reveal-fallback')},4000);
window.addEventListener('pagereveal',function(e){window.__vt=e.viewTransition||null});})();
</script>
<script type="application/ld+json"><?= json_encode(array_merge([
    '@context' => 'https://schema.org',
    '@type'    => 'ProfessionalService',
    'name'     => cfg('name'),
    'url'      => cfg('url'),
    'telephone' => cfg('phone'),
    'email'    => cfg('email'),
    'foundingDate' => (string) cfg('founded'),
    'address'  => ['@type' => 'PostalAddress', 'streetAddress' => cfg('address'), 'addressLocality' => 'Pendik', 'addressRegion' => 'İstanbul', 'addressCountry' => 'TR'],
    'sameAs'   => array_values(cfg('social')),
], $p['schema']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</head>
<body data-page="<?= e($id) ?>" data-theme="<?= e($p['theme']) ?>" data-base="<?= e(base_path()) ?>">

<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <!-- Kaşe mürekkebi: kenarlarda hafif dalgalanma, içte boşluklar -->
    <filter id="ink" x="-5%" y="-5%" width="110%" height="110%">
      <feTurbulence type="fractalNoise" baseFrequency="0.75" numOctaves="2" seed="7" result="grain"/>
      <feColorMatrix in="grain" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 0 0 -2.6 2.05" result="holes"/>
      <feComposite in="SourceGraphic" in2="holes" operator="in" result="speckled"/>
      <feTurbulence type="fractalNoise" baseFrequency="0.035" numOctaves="2" seed="3" result="warp"/>
      <feDisplacementMap in="speckled" in2="warp" scale="3" xChannelSelector="R" yChannelSelector="G"/>
    </filter>
  </defs>
</svg>

<a class="skip" href="#main">İçeriğe geç</a>

<header class="hdr" data-hdr>
  <div class="hdr__in wrap">
    <a class="hdr__brand" href="<?= url() ?>" aria-label="<?= e(cfg('name')) ?>, ana sayfa"><?php require APP . '/partials/logo.php'; ?></a>
    <div class="hdr__mid">
      <p class="hdr__folio" aria-hidden="true" data-folio><?= $folioHtml ?></p>
      <nav class="hnav hnav--w<?= [4 => 550, 5 => 550, 6 => 640, 7 => 740][count($menu)] ?? (count($menu) < 4 ? 550 : 840) ?>" aria-label="Ana sayfalar" data-hnav>
        <ol class="hnav__list" role="list">
          <?php foreach ($menu as $m): $cur = $hereExact && $here && $here['key'] === $m['key'] ? ' aria-current="page"' : ($here && $here['key'] === $m['key'] ? ' aria-current="true"' : ''); ?>
            <li><a href="<?= url($m['path']) ?>"<?= $cur ?> data-no="<?= e($m['nn']) ?>"><span class="hnav__no" aria-hidden="true"><?= e($m['nn']) ?></span><span class="hnav__t"><?= e($m['label']) ?></span></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>
    </div>
    <div class="hdr__act">
      <a class="btn btn--sm hdr__cta" href="<?= url('iletisim') ?>">Ön görüşme <?= arrow() ?></a>
      <button class="hdr__menu" type="button" aria-expanded="false" aria-controls="fihrist" data-menu-open>
        <span>Fihrist</span><span class="hdr__tabs" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
    </div>
  </div>
</header>

<div class="fih" id="fihrist" role="dialog" aria-modal="true" aria-label="Fihrist: site haritası" data-menu>
  <div class="fih__in wrap">
    <div class="fih__top">
      <a class="hdr__brand" href="<?= url() ?>" aria-label="Ana sayfa"><?php require APP . '/partials/logo.php'; ?></a>
      <button class="fih__close" type="button" data-menu-close><span>Kapat</span><?= icon('x') ?></button>
    </div>
    <nav aria-label="Ana menü">
      <p class="fih__h"><span>Fihrist</span><em>sayfa</em></p>
      <ul class="idx" role="list">
        <?php foreach ($menu as $m): ?>
          <li><a href="<?= url($m['path']) ?>"<?= $isCur($m['path']) ?>><span class="t"><?= e($m['label']) ?></span><span class="dots" aria-hidden="true"></span><span class="pg"><?= $m['nn'] ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="fih__side">
      <nav aria-label="Hizmetler">
        <p class="fih__h"><span>Dosyalar</span><em><?= pg_no('hizmetler') ?></em></p>
        <ul class="idx idx--sm" role="list">
          <?php foreach (services() as $slug => $s): ?>
            <li><a href="<?= service_url($slug) ?>"<?= $isCur('urunler/detay/' . $slug) ?>><span class="tab" style="--c:var(--f-<?= e($s['color']) ?>)" aria-hidden="true"></span><span class="t"><?= e($s['nav']) ?></span><span class="dots" aria-hidden="true"></span><span class="pg"><?= svc_no($s) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <nav aria-label="Kurumsal">
        <p class="fih__h"><span>Kurumsal</span><em>sayfa</em></p>
        <ul class="idx idx--sm" role="list">
          <?php foreach ($corp as $m): ?>
            <li><a href="<?= url($m['path']) ?>"<?= $isCur($m['path']) ?>><span class="t"><?= e($m['label']) ?></span><span class="dots" aria-hidden="true"></span><span class="pg"><?= $m['nn'] ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
    </div>
    <div class="fih__foot">
      <a href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a>
      <a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a>
      <span><?= e(cfg('address_short')) ?></span>
    </div>
  </div>
</div>

<main id="main" tabindex="-1">
<?= $content ?>
</main>

<footer class="ftr">
  <div class="wrap">
    <div class="ftr__close">
      <div>
        <p class="ftr__salute">Saygılarımızla<em>,</em></p>
        <p class="ftr__sign">Bir sorunuz, bir proje fikriniz ya da masanızda bekleyen bir çağrı metni varsa bize yazın; okuyup size dönelim.</p>
      </div>
      <?php $kaseClass = 'ftr__kase'; require APP . '/partials/kase.php'; ?>
    </div>

    <div class="ftr__ekler">
      <h2>Ekler</h2>
      <div class="ftr__ek">
        <span class="label">Ek-1 · Telefon</span>
        <a class="big" href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a>
        <a class="small" href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank">WhatsApp’tan yazın</a>
      </div>
      <div class="ftr__ek">
        <span class="label">Ek-2 · E-posta</span>
        <a class="big" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a>
        <span class="ftr__nav">
          <?php foreach (cfg('social') as $name => $href): ?><a href="<?= e($href) ?>" rel="noopener" target="_blank"><?= e($name) ?></a><?php endforeach; ?>
        </span>
      </div>
      <div class="ftr__ek">
        <span class="label">Ek-3 · Adres</span>
        <a class="small" href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(cfg('address')) ?></a>
      </div>
    </div>

    <div class="ftr__base">
      <p>&copy; <?= cfg('founded') ?>–<?= date('Y') ?> <?= e(cfg('name')) ?></p>
      <nav aria-label="Yasal">
        <?php if (path_enabled('duyurular')): ?>
        <a href="<?= url('duyurular') ?>">Duyurular</a>
        <?php endif; ?>
        <?php if (path_enabled('kariyer')): ?>
        <a href="<?= url('kariyer') ?>">Kariyer</a>
        <?php endif; ?>
        <a href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>">KVKK Aydınlatma Metni</a>
        <a href="<?= url('kurumsal/cerez-politikasi') ?>">Çerez Politikası</a>
        <a href="<?= url('hesap-numaralarimiz') ?>">Hesap Numaralarımız</a>
        <?php if (feature('bulten')): ?>
        <a href="<?= url('haberdarol') ?>" data-nl-open>Bültene kayıt ol</a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</footer>

<?php if (feature('whatsapp')): ?>
<a class="wa" href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank" aria-label="WhatsApp ile yazın">
  <?= icon('whatsapp-logo') ?><span>WhatsApp</span>
</a>
<?php endif; ?>

<?php if (feature('bulten')) { require APP . '/partials/bulten.php'; } ?>
<?php if ($spot) { require APP . '/partials/spotlight.php'; } ?>

<div class="toast" role="status" aria-live="polite" data-toast></div>

<script src="<?= asset('vendor/gsap.min.js') ?>" defer></script>
<script src="<?= asset('vendor/ScrollTrigger.min.js') ?>" defer></script>
<?php foreach ($vendor as $v): ?><script src="<?= asset('vendor/' . $v . '.min.js') ?>" defer></script>
<?php endforeach; ?>
<script src="<?= asset('vendor/lenis.min.js') ?>" defer></script>
<?php if ($spot): ?><script src="<?= asset('js/spotlight.js') ?>" defer></script>
<?php endif; ?>
<script src="<?= asset('js/hdrnav.js') ?>" defer></script>
<script type="module" src="<?= asset('js/app.js') ?>"<?= $pageJs ? ' data-page-script="' . e($pageJs) . '"' : '' ?>></script>
</body>
</html>
