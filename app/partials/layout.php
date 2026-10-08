<?php
/**
 * Ortak sayfa iskeleti.
 * @var string $content  Sayfa içeriği (render() tarafından)
 * @var string $path     Geçerli yol (index.php)
 */
$p     = page();
$seo   = seo_meta($p, $path ?? '');   // başlık, açıklama, canonical, robots, paylaşım görseli: tek yerden (app/seo.php)
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
// Etiket: "Evrak 03 · <b>Ad</b>" → kısa ekranlarda adı düşer (hdr__nm), çok dar ekranda "Evrak" sözcüğü de (hdr__w); sözcük genel.folio.word
$folio = $p['folio'] ?: pg_folio('home');
$folioHtml = preg_match('/^' . preg_quote(e(folio_word()), '/') . '\s+(\S+)\s*·\s*(.+)$/su', $folio, $fm)
    ? '<span class="hdr__ev"><span class="hdr__w">' . e(folio_word()) . ' </span>' . $fm[1] . '</span><span class="hdr__nm"> · ' . $fm[2] . '</span>'
    : $folio;
// Açılışta öne çıkan duyuru (varsa): pencere, çip ve kendi dosyaları yalnızca o zaman yüklenir
$spot = function_exists('ann_featured') ? ann_featured() : null;
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?= seo_head($p, $path ?? '', $seo) ?><meta name="theme-color" content="<?= $p['theme'] === 'dark' ? '#121214' : '#eeeae1' ?>">
<link rel="icon" type="image/svg+xml" href="<?= asset('img/favicon.svg') ?>">
<link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
<link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
<link rel="preload" href="<?= url('assets/fonts/archivo.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= url('assets/fonts/newsreader.woff2') ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
<?php if ($pageCss): ?><link rel="stylesheet" href="<?= $pageCss ?>"><?php endif; ?>
<?php if ($pageJs): ?><link rel="modulepreload" href="<?= $pageJs ?>"><?php endif; ?>
<?php foreach (($p['styles'] ?? []) as $extraCss): ?><link rel="stylesheet" href="<?= asset('css/' . $extraCss . '.css') ?>">
<?php endforeach; ?>
<?php if ($spot): ?><link rel="stylesheet" href="<?= asset('css/spotlight.css') ?>">
<?php endif; ?>
<script>
(function(){var h=document.documentElement;h.classList.add('js');
window.__revealFallback=setTimeout(function(){h.classList.add('reveal-fallback')},4000);
window.addEventListener('pagereveal',function(e){window.__vt=e.viewTransition||null});})();
</script>
<script type="application/json" id="js-metin"><?= json_encode(texts_js(), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<?= seo_jsonld($p, $path ?? '', $seo) ?></head>
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

<a class="skip" href="#main"><?= e(t('genel.header.skip')) ?></a>

<header class="hdr" data-hdr>
  <div class="hdr__in wrap">
    <a class="hdr__brand" href="<?= url() ?>" aria-label="<?= e(t('genel.header.logo_label')) ?>"><?php require APP . '/partials/logo.php'; ?></a>
    <div class="hdr__mid">
      <p class="hdr__folio" aria-hidden="true" data-folio data-word="<?= e(folio_word()) ?>"><?= $folioHtml ?></p>
      <nav class="hnav hnav--w<?= [4 => 550, 5 => 550, 6 => 640, 7 => 740][count($menu)] ?? (count($menu) < 4 ? 550 : 840) ?>" aria-label="<?= e(t('genel.header.nav_label')) ?>" data-hnav>
        <ol class="hnav__list" role="list">
          <?php foreach ($menu as $m): $cur = $hereExact && $here && $here['key'] === $m['key'] ? ' aria-current="page"' : ($here && $here['key'] === $m['key'] ? ' aria-current="true"' : ''); ?>
            <li><a href="<?= url($m['path']) ?>"<?= $cur ?> data-no="<?= e($m['nn']) ?>"><span class="hnav__no" aria-hidden="true"><?= e($m['nn']) ?></span><span class="hnav__t"><?= e($m['label']) ?></span></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>
    </div>
    <div class="hdr__act">
      <a class="btn btn--sm hdr__cta" href="<?= url('iletisim') ?>"><?= e(t('genel.header.cta')) ?> <?= arrow() ?></a>
      <button class="hdr__menu" type="button" aria-expanded="false" aria-controls="fihrist" data-menu-open>
        <span><?= e(t('genel.header.menu')) ?></span><span class="hdr__tabs" aria-hidden="true"><i></i><i></i><i></i></span>
      </button>
    </div>
  </div>
</header>

<div class="fih" id="fihrist" role="dialog" aria-modal="true" aria-label="<?= e(t('genel.fihrist.label')) ?>" data-menu>
  <div class="fih__in wrap">
    <div class="fih__top">
      <a class="hdr__brand" href="<?= url() ?>" aria-label="<?= e(t('genel.fihrist.logo_label')) ?>"><?php require APP . '/partials/logo.php'; ?></a>
      <button class="fih__close" type="button" data-menu-close><span><?= e(t('genel.fihrist.close')) ?></span><?= icon('x') ?></button>
    </div>
    <nav aria-label="<?= e(t('genel.fihrist.nav_label')) ?>">
      <p class="fih__h"><span><?= e(t('genel.fihrist.pages_head')) ?></span><em><?= e(t('genel.fihrist.unit')) ?></em></p>
      <ul class="idx" role="list">
        <?php foreach ($menu as $m): ?>
          <li><a href="<?= url($m['path']) ?>"<?= $isCur($m['path']) ?>><span class="t"><?= e($m['label']) ?></span><span class="dots" aria-hidden="true"></span><span class="pg"><?= $m['nn'] ?></span></a></li>
        <?php endforeach; ?>
      </ul>
    </nav>
    <div class="fih__side">
      <nav aria-label="<?= e(t('genel.fihrist.files_label')) ?>">
        <p class="fih__h"><span><?= e(t('genel.fihrist.files_head')) ?></span><em><?= pg_no('hizmetler') ?></em></p>
        <ul class="idx idx--sm" role="list">
          <?php foreach (services() as $slug => $s): ?>
            <li><a href="<?= service_url($slug) ?>"<?= $isCur('urunler/detay/' . $slug) ?>><span class="tab" style="--c:var(--f-<?= e($s['color']) ?>)" aria-hidden="true"></span><span class="t"><?= e($s['nav']) ?></span><span class="dots" aria-hidden="true"></span><span class="pg"><?= svc_no($s) ?></span></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <nav aria-label="<?= e(t('genel.fihrist.corp_label')) ?>">
        <p class="fih__h"><span><?= e(t('genel.fihrist.corp_head')) ?></span><em><?= e(t('genel.fihrist.unit')) ?></em></p>
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
        <p class="ftr__salute"><?= th('genel.footer.salute') ?></p>
        <p class="ftr__sign"><?= th('genel.footer.sign') ?></p>
      </div>
      <?php $kaseClass = 'ftr__kase'; require APP . '/partials/kase.php'; ?>
    </div>

    <div class="ftr__ekler">
      <h2><?= e(t('genel.footer.ekler')) ?></h2>
      <div class="ftr__ek">
        <span class="label"><?= e(t('genel.footer.ek1')) ?></span>
        <a class="big" href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a>
        <a class="small" href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank"><?= e(t('genel.footer.whatsapp_link')) ?></a>
      </div>
      <div class="ftr__ek">
        <span class="label"><?= e(t('genel.footer.ek2')) ?></span>
        <a class="big" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a>
        <span class="ftr__nav">
          <?php foreach (cfg('social') as $name => $href): ?><a href="<?= e($href) ?>" rel="noopener" target="_blank"><?= e($name) ?></a><?php endforeach; ?>
        </span>
      </div>
      <div class="ftr__ek">
        <span class="label"><?= e(t('genel.footer.ek3')) ?></span>
        <a class="small" href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(cfg('address')) ?></a>
      </div>
    </div>

    <div class="ftr__base">
      <p><?= e(t('genel.footer.copyright')) ?></p>
      <nav aria-label="<?= e(t('genel.footer.nav_label')) ?>">
        <?php if (path_enabled('duyurular')): ?>
        <a href="<?= url('duyurular') ?>"><?= e(pg_name('duyurular')) ?></a>
        <?php endif; ?>
        <?php if (path_enabled('kariyer')): ?>
        <a href="<?= url('kariyer') ?>"><?= e(pg_name('kariyer')) ?></a>
        <?php endif; ?>
        <a href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>"><?= e(pg_name('kvkk')) ?></a>
        <a href="<?= url('kurumsal/cerez-politikasi') ?>"><?= e(pg_name('cerez')) ?></a>
        <a href="<?= url('hesap-numaralarimiz') ?>"><?= e(pg_name('hesap')) ?></a>
        <?php if (feature('bulten')): ?>
        <a href="<?= url('haberdarol') ?>" data-nl-open><?= e(t('genel.footer.bulten')) ?></a>
        <?php endif; ?>
      </nav>
    </div>
  </div>
</footer>

<?php if (feature('whatsapp')): ?>
<aside aria-label="<?= e(t('genel.whatsapp.bolge')) ?>">
<a class="wa" href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank" aria-label="<?= e(t('genel.whatsapp.label')) ?>">
  <?= icon('whatsapp-logo') ?><span><?= e(t('genel.whatsapp.text')) ?></span>
</a>
</aside>
<?php endif; ?>

<?php if (feature('bulten')) { require APP . '/partials/bulten.php'; } ?>
<?php if ($spot) { require APP . '/partials/spotlight.php'; } ?>

<div class="toast" role="status" aria-live="polite" data-toast></div>

<?php /* Görünüm tetikleyicileri (yükselen yazılar, kaşe, kalem işareti): sayfa betikleri ve GSAP beklenmeden, gövde ayrıştırılır ayrıştırılmaz kurulur;
        yoksa yavaş bağlantıda içerik JavaScript inene kadar görünmez kalır (ölçüm: LCP telefonda 3,1 sn → 1,6 sn). Davranış ve ayarlar eskisiyle aynı. */ ?>
<script>
(function(){var els=document.querySelectorAll('[data-rise], [data-stamp], .annot, [data-hl], [data-on]');
var on=function(e){e.classList.add('is-on')};
if(!('IntersectionObserver' in window)||matchMedia('(prefers-reduced-motion: reduce)').matches){els.forEach(on);window.__observe=on;return}
var io=new IntersectionObserver(function(es){es.forEach(function(en){if(!en.isIntersecting)return;en.target.classList.add('is-on');en.target.dispatchEvent(new CustomEvent('on'));io.unobserve(en.target)})},{rootMargin:'0px 0px -12% 0px',threshold:0.01});
window.__observe=function(e){io.observe(e)};els.forEach(window.__observe)})();
</script>
<script src="<?= asset('vendor/gsap.min.js') ?>" defer></script>
<?php /* ScrollTrigger yalnızca kullanan sayfalarda yüklenir (page(['vendor' => ['ScrollTrigger', ...]])) */ ?>
<?php foreach ($vendor as $v): ?><script src="<?= asset('vendor/' . $v . '.min.js') ?>" defer></script>
<?php endforeach; ?>
<script src="<?= asset('vendor/lenis.min.js') ?>" defer></script>
<?php if ($spot): ?><script src="<?= asset('js/spotlight.js') ?>" defer></script>
<?php endif; ?>
<script src="<?= asset('js/hdrnav.js') ?>" defer></script>
<script type="module" src="<?= asset('js/app.js') ?>"<?= $pageJs ? ' data-page-script="' . e($pageJs) . '"' : '' ?>></script>
</body>
</html>
