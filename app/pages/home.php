<?php
$law     = site('hero_law');
$process = site('process');
page([
    'id'          => 'home',
    'description' => t('home.seo.description'),
    'folio'       => pg_folio('home'),
    'vendor'      => ['CustomEase'],
]);

// "Bu cümleleri bizden duymazsınız": beş iddia ve düzeltmesi (home.kalem.iddia_N / duzeltme_N)
$claims = range(1, 5);

/** Kanun duvarı: iki katman aynı yapıyı paylaşır, böylece mercek altındaki sade metin tam yerine oturur. */
$wall = function (string $mode) use ($law): void { ?>
  <div class="wall wall--<?= $mode ?>" aria-hidden="true">
    <div class="wall__in">
      <div class="wall__head">
        <p class="wall__title"><?= e($law['title']) ?></p>
        <p class="wall__meta"><?php foreach ($law['meta'] as $m): ?><span><?= e($m) ?></span><?php endforeach; ?></p>
      </div>
      <div class="wall__grid">
        <?php foreach ($law['blocks'] as $i => $b): ?>
          <div class="mb" data-block="<?= $i ?>">
            <p class="mb__ref"><?= e($b['ref']) ?></p>
            <div class="mb__body">
              <p class="mb__law"><?= e($b['law']) ?></p>
              <p class="mb__plain"><?= $b['plain'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php };
?>

<!-- 1 · Mercek: kanun metni ve sade Türkçesi -->
<section class="mercek" data-mercek aria-labelledby="home-title">
  <div class="stage" data-stage>
    <?php $wall('law'); $wall('plain'); ?>
    <div class="lens" aria-hidden="true" data-lens>
      <i class="lens__handle"></i>
      <i class="lens__ring"></i>
    </div>
  </div>

  <div class="ustyazi wrap">
    <div class="ustyazi__sheet" data-ustyazi>
      <p class="docmeta"><span><?= th('home.mercek.sayi') ?></span><span><?= th('home.mercek.konu') ?></span></p>
      <h1 class="display ustyazi__h" id="home-title"><?php foreach (explode("\n", t('home.mercek.baslik')) as $i => $ln): ?><?= $i ? ' ' : '' ?><span class="ln"><?= e($ln) ?></span><?php endforeach; ?></h1>
      <p class="ustyazi__p"><?= e(t('home.mercek.giris')) ?> <span class="only-fine"><?= e(t('home.mercek.ipucu_fare')) ?></span><span class="only-touch"><?= e(t('home.mercek.ipucu_dokunma')) ?></span></p>
      <div class="ustyazi__act">
        <a class="btn btn--ink" href="<?= url('iletisim') ?>"><?= e(t('home.mercek.dugme_iletisim')) ?> <?= arrow() ?></a>
        <button class="btn" type="button" data-plain-all aria-pressed="false"><?= e(t('home.mercek.dugme_sade')) ?></button>
      </div>
    </div>
  </div>

  <dl class="sr-only">
    <?php foreach ($law['blocks'] as $b): ?>
      <dt><?= e(t('home.mercek.okuyucu_kanun', ['madde' => $b['ref'], 'metin' => $b['law']])) ?></dt>
      <dd><?= th('home.mercek.okuyucu_sade', ['metin' => ['html' => strip_tags($b['plain'])]]) ?></dd>
    <?php endforeach; ?>
  </dl>
</section>

<!-- 2 · Takvim: bir başvurunun sekiz yaprağı -->
<section class="takvim" data-takvim aria-labelledby="takvim-title">
  <div class="takvim__in wrap" data-takvim-pin>
    <div class="takvim__text">
      <p class="label"><?= e(t('home.takvim.etiket')) ?></p>
      <h2 class="display takvim__h" id="takvim-title"><?= th('home.takvim.baslik') ?></h2>
      <p class="takvim__lead"><?= e(t('home.takvim.giris')) ?></p>
      <ol class="takvim__toc" role="list" aria-hidden="true">
        <?php foreach ($process as $i => $st): ?>
          <li data-toc="<?= $i ?>"><span><?= nn($i + 1) ?></span><?= e($st['title']) ?></li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="pad" data-pad>
      <div class="pad__bind" aria-hidden="true"><i></i><i></i></div>
      <div class="pad__stack">
        <?php foreach ($process as $i => $st): ?>
          <article class="leaf<?= $i === count($process) - 1 ? ' leaf--red' : '' ?>" style="--z:<?= count($process) - $i ?>" data-leaf>
            <header class="leaf__top">
              <span><?= e(tr_upper($st['phase'])) ?></span>
              <span><?= e(t('home.takvim.yaprak_sira', ['n' => $i + 1, 'toplam' => count($process)])) ?></span>
            </header>
            <p class="leaf__no" aria-hidden="true"><?= $i + 1 ?></p>
            <h3 class="leaf__t"><?= e($st['title']) ?></h3>
            <dl class="leaf__dl">
              <div><dt><?= e(t('home.takvim.biz')) ?></dt><dd><?= e($st['us']) ?></dd></div>
              <div class="leaf__you"><dt><?= e(t('home.takvim.siz')) ?></dt><dd><?= e($st['you']) ?></dd></div>
            </dl>
            <footer class="leaf__foot" aria-hidden="true" lang="en"><?= e(t('home.takvim.yaprak_alt')) ?></footer>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- 3 · Dosyalar: dizin -->
<section class="dosyalar section" aria-labelledby="dosya-title">
  <div class="wrap">
    <header class="dosyalar__head">
      <p class="label"><?= e(t('home.dosyalar.etiket', ['no' => pg_no('hizmetler')])) ?></p>
      <h2 class="display h2" id="dosya-title"><?= e(t('home.dosyalar.baslik')) ?></h2>
      <a class="link ui" href="<?= url('hizmetler') ?>"><?= e(t('home.dosyalar.baglanti')) ?></a>
    </header>
    <ol class="dlist" role="list">
      <?php foreach (services() as $slug => $s): ?>
        <li class="drow" style="--c:var(--f-<?= e($s['color']) ?>)">
          <a href="<?= service_url($slug) ?>">
            <span class="drow__tab"><span><?= svc_no($s) ?></span></span>
            <span class="drow__t"><?= e($s['title']) ?></span>
            <span class="drow__d"><?= e($s['short']) ?></span>
            <?= arrow('arw drow__arw') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<?php /* Çağrı takvimi: Duyurular bölümü kapalıyken hiçbir şey basılmaz */ $cagriMode = 'home'; require APP . '/partials/cagri.php'; ?>

<!-- 4 · Kırmızı kalem -->
<section class="kalem section" aria-labelledby="kalem-title">
  <div class="wrap kalem__in">
    <header class="kalem__head">
      <p class="label"><?= e(t('home.kalem.etiket')) ?></p>
      <h2 class="display h2" id="kalem-title"><?= e(t('home.kalem.baslik')) ?></h2>
    </header>
    <ul class="kalem__list" role="list">
      <?php foreach ($claims as $i => $c): ?>
        <li class="kalem__row" data-on style="--delay:<?= $i * 0.04 ?>s">
          <p class="kalem__claim"><s class="strike"><?= e(t('home.kalem.iddia_' . $c)) ?></s></p>
          <p class="kalem__fix"><?= e(t('home.kalem.duzeltme_' . $c)) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php if (feature('referanslar')): ?>
<!-- 5 · Kaşeler -->
<section class="kaseler section" aria-labelledby="kaseler-title">
  <div class="wrap">
    <header class="kaseler__head">
      <h2 class="display h2" id="kaseler-title"><?= e(t('home.kaseler.baslik')) ?></h2>
      <a class="btn" href="<?= url('referans') ?>"><?= e(t('home.kaseler.dugme')) ?> <?= arrow() ?></a>
    </header>
    <ul class="kaseler__grid" role="list">
      <?php foreach (refs_map() as $slug => $name): ?>
        <li><span class="inklogo" role="img" aria-label="<?= e($name) ?>" style="--src:url('<?= ref_ink_url($slug) ?>')"></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php /* 6 · Bülten: makale sütunları yalnızca Yazılar bölümü açıkken (panel, Görünürlük) */ if (feature('blog')): ?>
<section class="bulten section" aria-labelledby="bulten-title">
  <div class="wrap">
    <header class="bulten__mast">
      <p class="bulten__side"><?= e(t('home.bulten.sayi', ['n' => count(posts())])) ?></p>
      <h2 class="bulten__name" id="bulten-title"><?= e(t('home.bulten.ad')) ?></h2>
      <p class="bulten__side"><?= e(tr_date(date('Y-m-d'))) ?></p>
    </header>
    <div class="bulten__cols">
      <?php foreach (array_slice(posts(), 0, 3, true) as $slug => $post): ?>
        <article class="bulten__col">
          <p class="label"><?= e(t('home.bulten.kunye', ['tarih_uzun' => tr_date($post['date']), 'dk' => reading_time($post['body'])])) ?></p>
          <h3><a href="<?= post_url($slug) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e($post['excerpt']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <a class="link ui bulten__all" href="<?= url('blog') ?>"><?= e(t('home.bulten.baglanti')) ?></a>
  </div>
</section>
<?php endif; ?>

<!-- 7 · Son söz -->
<section class="masa section" aria-labelledby="masa-title">
  <div class="wrap masa__in">
    <h2 class="display masa__h" id="masa-title"><?= e(t('home.masa.baslik')) ?></h2>
    <div class="masa__side">
      <p class="lead"><?= e(t('home.masa.giris')) ?></p>
      <div class="masa__act">
        <a class="btn btn--ink" href="<?= url('iletisim') ?>"><?= e(t('home.masa.dugme')) ?> <?= arrow() ?></a>
        <a class="btn" href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a>
      </div>
    </div>
  </div>
</section>
