<?php
$principles = site('principles');
page([
    'id'          => 'stones',
    'theme'       => 'dark',
    'title'       => pg_name('mihenk'),
    'description' => t('mihenk.seo.description'),
    'folio'       => pg_folio('mihenk'),
]);

$roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
// Kuyumcunun karşılaştırma iğneleri: ayarı bilinen alaşımların taşta bıraktığı iz rengi
$needles = [8 => '#b48a68', 14 => '#c39f5e', 18 => '#d4b052', 22 => '#e0bb42', 24 => '#e6b52a'];
?>

<section class="tas-head pagehead" aria-labelledby="tas-title">
  <div class="wrap tas-head__in">
    <p class="docmeta" data-rise><span><?= th('mihenk.hero.sayi') ?></span><span><?= th('mihenk.hero.konu') ?></span></p>
    <h1 class="display tas-head__h" id="tas-title" data-rise style="--delay:.06s"><?= e(t('mihenk.hero.baslik')) ?></h1>
    <div class="tas-head__txt" data-rise style="--delay:.12s">
      <p class="lead"><?= e(t('mihenk.hero.giris')) ?></p>
      <p class="tas-head__note"><?= e(t('mihenk.hero.not')) ?></p>
    </div>
  </div>
</section>

<section class="tas" data-tas aria-label="<?= e(t('mihenk.tas.etiket')) ?>">
  <div class="wrap">
    <div class="tas__stone" data-stone>
      <canvas class="tas__cv" data-cv aria-hidden="true"></canvas>
      <ol class="tas__list" role="list">
        <?php foreach ($principles as $i => [$title, $text]): ?>
          <li class="tas__p" data-p>
            <span class="tas__no" aria-hidden="true"><?= $roman[$i] ?? ($i + 1) ?></span>
            <h2 class="tas__t"><?= e($title) ?></h2>
            <p class="tas__x"><?= e($text) ?></p>
          </li>
        <?php endforeach; ?>
      </ol>
      <div class="tas__needles" aria-hidden="true">
        <?php foreach ($needles as $k => $c): ?>
          <span class="tas__needle" style="--c:<?= $c ?>"><i></i><b><?= $k ?></b></span>
        <?php endforeach; ?>
        <em><?= e(t('mihenk.tas.ayar')) ?></em>
      </div>
    </div>

    <div class="tas__bar">
      <p class="tas__hint">
        <span class="only-fine"><?= e(t('mihenk.cubuk.ipucu_fare')) ?></span>
        <span class="only-touch"><?= e(t('mihenk.cubuk.ipucu_dokunma')) ?></span>
      </p>
      <p class="tas__count" aria-live="polite" data-count><?= th('mihenk.cubuk.sayac', ['n' => ['html' => '<b>0</b>'], 'toplam' => count($principles)]) ?></p>
      <button class="btn tas__all" type="button" data-rub-all><?= e(t('mihenk.cubuk.hepsi')) ?></button>
    </div>
  </div>
</section>

<section class="section tas-next">
  <div class="wrap">
    <a class="next" href="<?= url('hakkimizda') ?>">
      <span class="next__k"><?= e(t('mihenk.sonraki.etiket', ['no' => pg_no('hakkimizda')])) ?></span>
      <span class="next__t"><span><?= e(pg_name('hakkimizda')) ?></span></span>
      <?= arrow() ?>
    </a>
  </div>
</section>
