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
    'title'       => 'Kariyer',
    'description' => 'Arslanlı Yatırım & Danışmanlık ekibine katılmak için iş başvurusu formu. Hibe, teşvik ve yatırım danışmanlığında birlikte çalışalım.',
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
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s">Dosyanızı <?= annot('açalım', 'under', 'red') ?>.</h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <p class="lead">Hibe, teşvik ve yatırım danışmanlığında bizimle çalışmak istiyorsanız aşağıdaki formu doldurun. Başvurunuz özgeçmişinizle birlikte doğrudan ekibimize ulaşır.</p>
    <p class="kr-head__more">Burada iş, çağrı metnini ve mevzuatı okumak, bir başvuruyu eksiksiz hazırlamak ve son tarihe yetiştirmektir. Kâğıda, tarihe ve ayrıntıya özen gösteriyorsanız bize yazın.</p>
  </div>
</section>

<?php
// Açık pozisyonlar: yayında ve süresi dolmamış ilan yoksa hiçbir şey basılmaz (sayfa eskisi gibi kalır)
$jobs = ilan_published();
if ($jobs): ?>
<section class="kj wrap" id="acik-pozisyonlar" aria-labelledby="kj-title">
  <div class="kj__top" data-rise>
    <h2 class="kj__h" id="kj-title">Açık pozisyonlar</h2>
    <p class="kj__lead">Aşağıdaki kadrolar için başvuru alıyoruz. Birini seçerseniz başvurunuz doğrudan o ilana bağlanır. Size uygun bir ilan yoksa genel başvuru formu <a class="link" href="#basvuru">aşağıda</a>.</p>
  </div>
  <ol class="kj__list" role="list">
    <?php foreach ($jobs as $i => $job):
        $left = $job['deadline'] !== '' ? (int) round((strtotime($job['deadline']) - strtotime(date('Y-m-d'))) / 86400) : null; ?>
      <li class="kj-slip" data-rise style="--rot:<?= [-0.35, 0.3, -0.2, 0.4][$i % 4] ?>deg;--delay:<?= round(0.05 * min($i, 4), 2) ?>s">
        <a class="kj-slip__a" href="<?= e(ilan_url($job)) ?>">
          <span class="kj-slip__stub" aria-hidden="true"><small>Kadro</small><b><?= nn($i + 1) ?></b></span>
          <span class="kj-slip__body">
            <span class="kj-slip__title"><?= e($job['title']) ?></span>
            <?php if ($job['summary'] !== ''): ?><span class="kj-slip__sum"><?= e($job['summary']) ?></span><?php endif; ?>
            <span class="kj-slip__f">
              <?php foreach ([['Alan', $job['area']], ['Şehir', $job['city']], ['Çalışma türü', $job['type']], ['Deneyim', $job['experience']]] as [$k, $v]): if ($v === '') continue; ?>
                <span><em><?= e($k) ?></em> <?= e($v) ?></span>
              <?php endforeach; ?>
            </span>
          </span>
          <span class="kj-slip__end">
            <?php if ($job['deadline'] !== ''): ?>
              <span class="kj-slip__dl<?= $left <= 7 ? ' is-soon' : '' ?>"><em>Son başvuru</em><b><?= e(tr_date($job['deadline'])) ?></b><?php if ($left <= 7): ?><small><?= $left <= 0 ? 'Bugün son gün' : $left . ' gün kaldı' ?></small><?php endif; ?></span>
            <?php else: ?>
              <span class="kj-slip__dl is-none"><em>Son başvuru</em><b>Süresiz</b></span>
            <?php endif; ?>
            <span class="kj-slip__go">İlanı açın <?= arrow() ?></span>
          </span>
        </a>
        <span class="kj-slip__clip" aria-hidden="true"><?= icon('paperclip', 'ico') ?></span>
      </li>
    <?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<section class="kr wrap" aria-label="İş başvurusu">

  <aside class="kr__side" aria-label="Çalıştığımız alanlar ve değerlendirme süreci">
    <div class="kr__block" data-rise>
      <h2 class="kr__h">Çalıştığımız alanlar</h2>
      <ol class="kr__areas" role="list">
        <?php $i = 0; foreach (services() as $slug => $s): $i++; ?>
          <li><span class="kr__no" aria-hidden="true"><?= sprintf('%02d', $i) ?></span><a href="<?= service_url($slug) ?>"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="kr__block" data-rise>
      <h2 class="kr__h">Başvurunuz nasıl değerlendirilir?</h2>
      <ol class="kr__steps" role="list">
        <li>
          <span class="kr__step" aria-hidden="true">1</span>
          <p><b>Başvurunuz bize ulaşır.</b> Form ve özgeçmişiniz doğrudan ekibimize iletilir.</p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">2</span>
          <p><b>İnceleriz.</b> Deneyiminizi ve ilgilendiğiniz alanı ekibimizin ihtiyaçlarıyla birlikte değerlendiririz.</p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">3</span>
          <p><b>Size ulaşırız.</b> Uygun bir pozisyon olduğunda görüşme için sizinle iletişime geçeriz.</p>
        </li>
      </ol>
    </div>

    <p class="kr__note" data-rise>Başvurunuzda paylaştığınız bilgiler yalnızca işe alım süreci için kullanılır. Ayrıntılar için <a class="link" href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>#basvuru-adaylari">Aydınlatma Metni</a>’ne bakabilirsiniz.</p>
  </aside>

  <div class="kr__desk" id="basvuru" data-desk>

    <?php $krIlan = null; require APP . '/partials/kariyer-form.php'; ?>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k">Aklınızdaki soru için</span>
    <span class="next__t"><span>İletişim</span></span>
    <?= arrow() ?>
  </a>
</div>
