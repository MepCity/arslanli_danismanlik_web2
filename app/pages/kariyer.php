<?php
/**
 * Kariyer: özlük dosyası.
 * Başvuru formu, manila bir dosyanın içindeki basılı bir form gibi kurulur: numaralı kutular, bölüm şeritleri,
 * özgeçmiş ataç ile forma tutturulur. Gönderilince form dosyanın içine kayar, kapak kapanır ve
 * "DOSYAYA EKLENDİ" kaşesi basılır (app/form.php, 'kariyer'). Form, ilan sayfasıyla ortak parçadadır (app/partials/kariyer-form.php).
 * Yayında ilan varsa formun üstünde "Açık pozisyonlar" bölümü, kadro fişleri olarak basılır; ilan yoksa bölüm hiç yer almaz.
 */
page([
    'id'          => 'careers',
    'title'       => pg_name('kariyer'),
    'description' => t('kariyer.seo.description'),
    'folio'       => pg_folio('kariyer'),
]);

$durum = (string) ($_GET['durum'] ?? '');
$sent  = $durum === 'tamam';
$date  = today_official();
$cities = site('sehirler');
$levels = site('deneyim');
?>

<section class="kr-head pagehead wrap" aria-labelledby="kr-title">
  <p class="label" data-rise><?= e(pg_label('kariyer')) ?></p>
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s"><?= th('kariyer.hero.baslik') ?></h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <p class="lead"><?= th('kariyer.hero.giris') ?></p>
    <p class="kr-head__more"><?= th('kariyer.hero.devam') ?></p>
  </div>
</section>

<?php
// Açık pozisyonlar: yayında ve süresi dolmamış ilan yoksa hiçbir şey basılmaz (sayfa eskisi gibi kalır)
$jobs = ilan_published();
if ($jobs): ?>
<section class="kj wrap" id="acik-pozisyonlar" aria-labelledby="kj-title">
  <div class="kj__top" data-rise>
    <h2 class="kj__h" id="kj-title"><?= e(t('kariyer.pozisyon.baslik')) ?></h2>
    <p class="kj__lead"><?= th('kariyer.pozisyon.giris', ['baglanti' => ['html' => '<a class="link" href="#basvuru">' . e(t('kariyer.pozisyon.giris_baglanti')) . '</a>']]) ?></p>
  </div>
  <ol class="kj__list" role="list">
    <?php foreach ($jobs as $i => $job):
        $left = $job['deadline'] !== '' ? (int) round((strtotime($job['deadline']) - strtotime(date('Y-m-d'))) / 86400) : null; ?>
      <li class="kj-slip" data-rise style="--rot:<?= [-0.35, 0.3, -0.2, 0.4][$i % 4] ?>deg;--delay:<?= round(0.05 * min($i, 4), 2) ?>s">
        <a class="kj-slip__a" href="<?= e(ilan_url($job)) ?>">
          <span class="kj-slip__stub" aria-hidden="true"><small><?= e(t('kariyer.fis.kadro')) ?></small><b><?= nn($i + 1) ?></b></span>
          <span class="kj-slip__body">
            <span class="kj-slip__title"><?= e($job['title']) ?></span>
            <?php if ($job['summary'] !== ''): ?><span class="kj-slip__sum"><?= e($job['summary']) ?></span><?php endif; ?>
            <span class="kj-slip__f">
              <?php foreach ([[t('kariyer.fis.alan'), $job['area']], [t('kariyer.fis.sehir'), $job['city']], [t('kariyer.fis.tur'), $job['type']], [t('kariyer.fis.deneyim'), $job['experience']]] as [$k, $v]): if ($v === '') continue; ?>
                <span><em><?= e($k) ?></em> <?= e($v) ?></span>
              <?php endforeach; ?>
            </span>
          </span>
          <span class="kj-slip__end">
            <?php if ($job['deadline'] !== ''): ?>
              <span class="kj-slip__dl<?= $left <= 7 ? ' is-soon' : '' ?>"><em><?= e(t('kariyer.fis.son_basvuru')) ?></em><b><?= e(tr_date($job['deadline'])) ?></b><?php if ($left <= 7): ?><small><?= e($left <= 0 ? t('kariyer.fis.bugun') : t('kariyer.fis.kalan', ['n' => $left])) ?></small><?php endif; ?></span>
            <?php else: ?>
              <span class="kj-slip__dl is-none"><em><?= e(t('kariyer.fis.son_basvuru')) ?></em><b><?= e(t('kariyer.fis.suresiz')) ?></b></span>
            <?php endif; ?>
            <span class="kj-slip__go"><?= e(t('kariyer.fis.ac')) ?> <?= arrow() ?></span>
          </span>
        </a>
        <span class="kj-slip__clip" aria-hidden="true"><?= icon('paperclip', 'ico') ?></span>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<section class="kr wrap" aria-label="<?= e(t('kariyer.form.alan_etiket')) ?>">

  <aside class="kr__side" aria-label="<?= e(t('kariyer.yan.etiket')) ?>">
    <div class="kr__block" data-rise>
      <h2 class="kr__h"><?= e(t('kariyer.yan.alanlar')) ?></h2>
      <ol class="kr__areas" role="list">
        <?php $i = 0; foreach (services() as $slug => $s): $i++; ?>
          <li><span class="kr__no" aria-hidden="true"><?= sprintf('%02d', $i) ?></span><a href="<?= service_url($slug) ?>"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="kr__block" data-rise>
      <h2 class="kr__h"><?= e(t('kariyer.yan.surec')) ?></h2>
      <ol class="kr__steps" role="list">
        <li>
          <span class="kr__step" aria-hidden="true">1</span>
          <p><?= th('kariyer.yan.bir') ?></p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">2</span>
          <p><?= th('kariyer.yan.iki') ?></p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">3</span>
          <p><?= th('kariyer.yan.uc') ?></p>
        </li>
      </ol>
    </div>

    <p class="kr__note" data-rise><?= th('kariyer.yan.not', ['baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '#basvuru-adaylari">' . e(t('kariyer.yan.not_baglanti')) . '</a>']]) ?></p>
  </aside>

  <div class="kr__desk" id="basvuru" data-desk>

    <?php $krIlan = null; require APP . '/partials/kariyer-form.php'; ?>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k"><?= e(t('kariyer.sonraki.etiket')) ?></span>
    <span class="next__t"><span><?= e(pg_name('iletisim')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
