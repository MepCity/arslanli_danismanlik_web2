<?php
$principles = site('principles');
page([
    'id'          => 'stones',
    'theme'       => 'dark',
    'title'       => 'Mihenk Taşlarımız',
    'description' => 'Arslanlı Yatırım & Danışmanlık’ın çalışırken uyduğu altı ilke: uymuyorsa söyleriz, son güne kalmayız, dosyanız sizindir.',
    'folio'       => pg_folio('mihenk'),
]);

$roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'];
// Kuyumcunun karşılaştırma iğneleri: ayarı bilinen alaşımların taşta bıraktığı iz rengi
$needles = [8 => '#b48a68', 14 => '#c39f5e', 18 => '#d4b052', 22 => '#e0bb42', 24 => '#e6b52a'];
?>

<section class="tas-head pagehead" aria-labelledby="tas-title">
  <div class="wrap tas-head__in">
    <p class="docmeta" data-rise><span>Sayı: <b>ARS-<?= date('Y') ?>/010</b></span><span>Konu: <b>Çalışma ilkelerimiz</b></span></p>
    <h1 class="display tas-head__h" id="tas-title" data-rise style="--delay:.06s">Mihenk taşlarımız</h1>
    <div class="tas-head__txt" data-rise style="--delay:.12s">
      <p class="lead">Kuyumcular altının ayarını anlamak için onu siyah bir taşa sürter; taşta kalan izin rengi ayarı gösterir. Bizim işimizin ayarını da bu altı ilke gösterir.</p>
      <p class="tas-head__note">Kuyumcu taştaki izi, ayarı bilinen iğnelerin bıraktığı izlerle yan yana koyarak okur. Taşın sağ alt köşesindeki çizgiler o iğnelerin izleri.</p>
    </div>
  </div>
</section>

<section class="tas" data-tas aria-label="Altı ilke">
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
        <em>ayar</em>
      </div>
    </div>

    <div class="tas__bar">
      <p class="tas__hint">
        <span class="only-fine">Taşın üzerinde fareyle basılı tutup sürükleyin; izin altından ilkeler çıkar.</span>
        <span class="only-touch">Parmağınızı taşın üzerinde sağa sola sürün. Yukarı aşağı hareket sayfayı kaydırır.</span>
      </p>
      <p class="tas__count" aria-live="polite" data-count><b>0</b> / <?= count($principles) ?> ilke ortaya çıktı</p>
      <button class="btn tas__all" type="button" data-rub-all>Hepsini sür</button>
    </div>
  </div>
</section>

<section class="section tas-next">
  <div class="wrap">
    <a class="next" href="<?= url('hakkimizda') ?>">
      <span class="next__k">Sonraki evrak · <?= pg_no('hakkimizda') ?></span>
      <span class="next__t"><span>Hakkımızda</span></span>
      <?= arrow() ?>
    </a>
  </div>
</section>
