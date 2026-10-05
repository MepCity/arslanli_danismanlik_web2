<?php
$timeline = site('timeline');
page([
    'id'          => 'about',
    'title'       => pg_name('hakkimizda'),
    'description' => t('hakkimizda.seo.description'),
    'folio'       => pg_folio('hakkimizda'),
]);

// Klasör renkleri (ofis klasörü sırtları) ve kalınlıkları
$colors = ['#2b2d32', '#34497a', '#5e625e', '#7a302a', '#2f5a4e', '#3b3e46', '#46587f', '#6b6860', '#2c2e34', '#56405c', '#37544a', '#30364a', '#4b4f58'];
$widths = [1, 0.86, 1.14, 1.28, 1, 0.92, 1.1, 0.95, 1.2, 1, 0.86, 1.06, 1.16];
$initial = 0;
foreach ($timeline as $i => $t) {
    if ($t['kind'] === 'us') { $initial = $i; break; }
}

// Kuruluştan önce gelen klasör sayısı ve "ilk üçü" gibi ek almış biçimi (sayfa metninde {onceki_sayi})
$onceki = count(array_filter($timeline, fn($t) => (int) $t['year'] < (int) cfg('founded')));
$iyelik = [1 => 'biri', 'ikisi', 'üçü', 'dördü', 'beşi', 'altısı', 'yedisi', 'sekizi', 'dokuzu', 'onu'];
$onceki_sayi = $iyelik[$onceki] ?? $onceki . '’i';

// Kuruluştan açılış yılına kaç yıl geçtiği, sıra sayısı olarak yazıyla ("otuzuncu")
$sira_yil = (int) site('vision')['open_year'] - (int) cfg('founded');
$ones = [1 => 'birinci', 'ikinci', 'üçüncü', 'dördüncü', 'beşinci', 'altıncı', 'yedinci', 'sekizinci', 'dokuzuncu'];
$tens = [10 => 'onuncu', 20 => 'yirminci', 30 => 'otuzuncu', 40 => 'kırkıncı', 50 => 'ellinci', 60 => 'altmışıncı', 70 => 'yetmişinci', 80 => 'sekseninci', 90 => 'doksanıncı'];
$tenw = [10 => 'on', 20 => 'yirmi', 30 => 'otuz', 40 => 'kırk', 50 => 'elli', 60 => 'altmış', 70 => 'yetmiş', 80 => 'seksen', 90 => 'doksan'];
$sira_yazi = $sira_yil % 10 === 0 && isset($tens[$sira_yil]) ? $tens[$sira_yil]
    : (($sira_yil > 10 && $sira_yil < 100 && isset($ones[$sira_yil % 10])) ? $tenw[$sira_yil - $sira_yil % 10] . ' ' . $ones[$sira_yil % 10]
    : ($ones[$sira_yil] ?? $sira_yil . '.'));

$cards = [
    ['kurumsal/misyonumuz', t('hakkimizda.kartlar.misyon_no'), pg_name('misyon'), t('hakkimizda.kartlar.misyon')],
    ['kurumsal/vizyonumuz', t('hakkimizda.kartlar.vizyon_no'), pg_name('vizyon'), t('hakkimizda.kartlar.vizyon', ['mektup_yili_sirasi' => $sira_yazi, 'acilis_yili' => site('vision')['open_year']])],
    ['kurumsal/mihenk-taslarimiz', t('hakkimizda.kartlar.mihenk_no'), pg_name('mihenk'), t('hakkimizda.kartlar.mihenk')],
];
?>

<!-- Başlık -->
<section class="ab-head pagehead" aria-labelledby="ab-title">
  <div class="wrap">
    <p class="docmeta"><span><?= th('hakkimizda.hero.sayi') ?></span><span><?= th('hakkimizda.hero.konu') ?></span><span><?= th('hakkimizda.hero.tarih') ?></span></p>
    <h1 class="display ab-head__h" id="ab-title"><?php foreach (explode("\n", t('hakkimizda.hero.baslik')) as $i => $ln): ?><?= $i ? ' ' : '' ?><span class="ln"><?= e($ln) ?></span><?php endforeach; ?></h1>
    <div class="ab-head__cols">
      <p class="lead" data-rise><?= e(t('hakkimizda.hero.giris')) ?></p>
      <p class="ab-head__p" data-rise style="--delay:.08s"><?= e(t('hakkimizda.hero.ikinci')) ?></p>
    </div>
  </div>
</section>

<!-- Arşiv rafı -->
<section class="raf section" aria-labelledby="raf-title" data-raf>
  <div class="wrap">
    <header class="raf__head">
      <p class="label"><?= e(t('hakkimizda.raf.etiket', ['ilk_yil' => $timeline[0]['year'], 'son_yil' => end($timeline)['year']])) ?></p>
      <h2 class="display h2" id="raf-title"><?= e(t('hakkimizda.raf.baslik')) ?></h2>
      <p class="raf__note"><?= e(t('hakkimizda.raf.not', ['onceki_sayi' => $onceki_sayi])) ?> <span class="only-fine-ab"><?= e(t('hakkimizda.raf.ipucu_fare')) ?></span><span class="only-touch-ab"><?= e(t('hakkimizda.raf.ipucu_dokunma')) ?></span></p>
    </header>

    <div class="shelf" data-shelf>
      <div class="shelf__scroll" data-shelf-scroll>
        <div class="shelf__row" data-tabs aria-label="<?= e(t('hakkimizda.raf.sekmeler')) ?>">
          <?php foreach ($timeline as $i => $t): ?>
            <button class="spine<?= $t['kind'] === 'us' ? ' spine--us' : '' ?><?= $i === $initial ? ' is-out' : '' ?>" type="button"
              id="sp-<?= $t['year'] ?>" aria-controls="pn-<?= $t['year'] ?>"
              style="--c:<?= $colors[$i % count($colors)] ?>;--w:<?= $widths[$i % count($widths)] ?>" data-spine>
              <span class="spine__label">
                <span class="spine__year"><?= $t['year'] ?></span>
                <span class="spine__t"><?= e($t['title']) ?></span>
              </span>
              <span class="spine__hole" aria-hidden="true"></span>
            </button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="shelf__board" aria-hidden="true"></div>
    </div>

    <div class="raf__panels" data-panels>
      <?php foreach ($timeline as $i => $t): ?>
        <article class="pn<?= $t['kind'] === 'us' ? ' pn--us' : '' ?>" id="pn-<?= $t['year'] ?>" aria-labelledby="sp-<?= $t['year'] ?>" data-panel>
          <p class="pn__k"><?= e(t($t['kind'] === 'us' ? 'hakkimizda.raf.klasor_biz' : ($t['year'] < cfg('founded') ? 'hakkimizda.raf.klasor_once' : 'hakkimizda.raf.klasor_degisiklik'), ['klasor_yili' => $t['year']])) ?></p>
          <h3 class="pn__t"><?= e($t['title']) ?></h3>
          <p class="pn__x"><?= e($t['text']) ?></p>
          <p class="pn__src"><?= e(t('hakkimizda.raf.kunye', ['kunye' => $t['src']])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Çalışma usulü -->
<section class="usul section" aria-labelledby="usul-title">
  <div class="wrap usul__in">
    <header class="usul__head">
      <p class="label"><?= e(t('hakkimizda.usul.etiket')) ?></p>
      <h2 class="display h2" id="usul-title"><?= e(t('hakkimizda.usul.baslik')) ?></h2>
    </header>
    <ol class="usul__list" role="list">
      <li class="usul__m" data-rise>
        <p class="usul__no"><?= e(t('hakkimizda.usul.madde_1_no')) ?></p>
        <h3 class="usul__t"><?= e(t('hakkimizda.usul.madde_1_baslik')) ?></h3>
        <p><?= th('hakkimizda.usul.madde_1') ?></p>
      </li>
      <li class="usul__m" data-rise>
        <p class="usul__no"><?= e(t('hakkimizda.usul.madde_2_no')) ?></p>
        <h3 class="usul__t"><?= e(t('hakkimizda.usul.madde_2_baslik')) ?></h3>
        <p><?= th('hakkimizda.usul.madde_2') ?></p>
      </li>
      <li class="usul__m" data-rise>
        <p class="usul__no"><?= e(t('hakkimizda.usul.madde_3_no')) ?></p>
        <h3 class="usul__t"><?= e(t('hakkimizda.usul.madde_3_baslik')) ?></h3>
        <p><?= th('hakkimizda.usul.madde_3') ?></p>
      </li>
    </ol>
  </div>
</section>

<!-- Sicil kaydı -->
<section class="sicil section" aria-labelledby="sicil-title">
  <div class="wrap sicil__in">
    <div class="sicil__text">
      <p class="label"><?= e(t('hakkimizda.sicil.etiket')) ?></p>
      <h2 class="display h2" id="sicil-title"><?= e(t('hakkimizda.sicil.baslik')) ?></h2>
      <p class="sicil__p"><?= e(t('hakkimizda.sicil.giris')) ?></p>
      <a class="link ui" href="<?= url('hesap-numaralarimiz') ?>"><?= e(t('hakkimizda.sicil.baglanti')) ?></a>
    </div>

    <div class="fcard" data-typed>
      <div class="fcard__head">
        <span class="fcard__title"><?= e(t('hakkimizda.sicil.form_baslik')) ?></span>
        <span class="fcard__no"><?= e(t('hakkimizda.sicil.form_no')) ?></span>
      </div>
      <dl class="fcard__grid">
        <div class="fc fc--wide"><dt><?= e(t('hakkimizda.sicil.unvan')) ?></dt><dd data-type><?= e(cfg('name')) ?></dd></div>
        <div class="fc"><dt><?= e(t('hakkimizda.sicil.kurulus')) ?></dt><dd data-type><?= e(t('hakkimizda.sicil.kurulus_deger')) ?></dd></div>
        <div class="fc"><dt><?= e(t('hakkimizda.sicil.yetkili')) ?></dt><dd data-type><?= e(cfg('company.authorized')) ?></dd></div>
        <div class="fc"><dt><?= e(t('hakkimizda.sicil.vergi_dairesi')) ?></dt><dd data-type><?= e(cfg('company.tax_office')) ?></dd></div>
        <div class="fc"><dt><?= e(t('hakkimizda.sicil.vergi_no')) ?></dt><dd data-type><?= e(cfg('company.tax_number')) ?></dd></div>
        <div class="fc fc--wide"><dt><?= e(t('hakkimizda.sicil.adres')) ?></dt><dd data-type><?= e(cfg('address')) ?></dd></div>
        <div class="fc"><dt><?= e(t('hakkimizda.sicil.telefon')) ?></dt><dd><a data-type href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a></dd></div>
        <div class="fc"><dt><?= e(t('hakkimizda.sicil.eposta')) ?></dt><dd><a data-type href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></dd></div>
        <div class="fc fc--wide"><dt><?= e(t('hakkimizda.sicil.faaliyet')) ?></dt><dd data-type><?= e(t('hakkimizda.sicil.faaliyet_deger')) ?></dd></div>
      </dl>
    </div>
  </div>
</section>

<!-- Katalog kartları -->
<section class="katalog section" aria-labelledby="katalog-title">
  <div class="wrap">
    <header class="katalog__head">
      <p class="label"><?= e(t('hakkimizda.kartlar.etiket')) ?></p>
      <h2 class="display h2" id="katalog-title"><?= e(t('hakkimizda.kartlar.baslik')) ?></h2>
    </header>
    <ul class="kartlar" role="list">
      <?php foreach ($cards as $i => [$to, $call, $title, $desc]): ?>
        <li style="--r:<?= [-2.2, 1.4, -0.8][$i] ?>deg">
          <a class="kart" href="<?= url($to) ?>">
            <span class="kart__call"><?= e($call) ?></span>
            <span class="kart__t"><?= e($title) ?></span>
            <span class="kart__d"><?= e($desc) ?></span>
            <span class="kart__go"><?= e(t('hakkimizda.kartlar.dugme')) ?> <?= arrow() ?></span>
            <span class="kart__hole" aria-hidden="true"></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('hizmetler') ?>">
    <span class="next__k"><?= e(t('hakkimizda.sonraki.etiket', ['no' => pg_no('hizmetler')])) ?></span>
    <span class="next__t"><span><?= e(pg_name('hizmetler')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
