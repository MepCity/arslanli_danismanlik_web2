<?php
/**
 * İş ilanı: /kariyer/{adres}. Veri: storage/ilanlar.json (app/ilanlar.php).
 * Özlük dosyasına iliştirilmiş "kadro talep fişi": fiş, kadronun künyesini daktiloyla (alan, şehir, tür, deneyim, son gün) taşır;
 * yanındaki basılı kâğıt görevleri, aranan nitelikleri ve tercih sebeplerini form satırları gibi sıralar; altında Kariyer sayfasındaki
 * aynı özlük dosyası durur, yalnızca pozisyon kutusu ilanın adıyla önceden doldurulmuştur (app/partials/kariyer-form.php).
 * Kapalı ya da süresi dolmuş ilan: HTTP 410 (index.php), dizine kapalı, form yok; üstü kırmızı kaşeyle "İLAN KAPANDI" basılır ve
 * ziyaretçi genel başvuruya yönlendirilir.
 * Arama motoru yapılandırılmış verisi (JobPosting) bu şablonda basılmaz; Aşama 3A ekler.
 * @var array $ilan
 */

$open = ilan_active($ilan);
$ref  = 'KT-' . strtoupper(substr((string) $ilan['id'], 0, 5));   // talep numarası: kimlikten türetilir, ilanla birlikte değişmez
$date = today_official();

page([
    'id'          => 'careers',
    'title'       => $open ? $ilan['title'] : 'İlan kapandı',
    'description' => $open ? ilan_seo_description($ilan) : 'Bu iş ilanı kapandı. Özgeçmişinizi genel başvuru formuyla bırakabilirsiniz.',
    'folio'       => pg_folio('kariyer'),
    'noindex'     => !$open,
]);

/* ---------- Kapalı ya da süresi dolmuş ilan ---------- */
if (!$open):
    $expired = ilan_state($ilan) === 'doldu';
    $others  = ilan_published();
?>
<section class="kr-head kr-head--ilan pagehead wrap" aria-labelledby="kr-title">
  <p class="label" data-rise><?= e(pg_label('kariyer')) ?> · Kapanan kadro</p>
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s">Bu ilan <?= annot('kapandı', 'under', 'red') ?>.</h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <p class="lead"><?= $expired ? 'Bu pozisyon için son başvuru günü geçti, artık başvuru alınmıyor.' : 'Bu pozisyon için artık başvuru almıyoruz.' ?></p>
    <p class="kr-head__more">Özgeçmişinizi yine de bırakabilirsiniz: genel başvuru formuna gelen dosyalar, uygun bir kadro açıldığında yeniden değerlendirilir.</p>
  </div>
</section>

<section class="ilc wrap" aria-label="Kapanan ilan">
  <div class="ilc__slip" data-rise>
    <span class="ilc__stub" aria-hidden="true"><small>Kadro</small><b>—</b></span>
    <div class="ilc__body">
      <p class="ilc__ref">Talep no <b><?= e($ref) ?></b></p>
      <h2 class="ilc__title"><?= e($ilan['title']) ?></h2>
      <p class="ilc__meta"><?= e(implode(' · ', ilan_meta($ilan))) ?></p>
    </div>
    <div class="ilc__stamp" aria-hidden="true">
      <svg viewBox="0 0 300 130">
        <rect x="4" y="4" width="292" height="122" rx="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="12" y="12" width="276" height="106" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
        <text x="150" y="66" text-anchor="middle" font-size="40" font-weight="900" style="font-stretch:60%;letter-spacing:.04em">İLAN KAPANDI</text>
        <text x="150" y="97" text-anchor="middle" font-size="16" font-weight="700" style="font-stretch:78%"><?= $expired ? e(tr_upper(tr_date((string) $ilan['deadline']))) . ' · SON GÜN' : 'BAŞVURU ALINMIYOR' ?></text>
      </svg>
    </div>
  </div>
  <p class="ilc__act" data-rise>
    <a class="btn sendbtn" href="<?= url('kariyer') ?>#basvuru">Genel başvuru formuna gidin <?= arrow() ?></a>
    <?php if ($others): ?><a class="link" href="<?= url('kariyer') ?>#acik-pozisyonlar">Açık pozisyonlara bakın</a><?php endif; ?>
  </p>
</section>
<?php return; endif;

/* ---------- Açık ilan ---------- */
$facts = [['Alan', $ilan['area']], ['Şehir', $ilan['city']], ['Çalışma türü', $ilan['type']]];
if ($ilan['experience'] !== '') {
    $facts[] = ['Deneyim', $ilan['experience']];
}
$facts[] = ['Yayın tarihi', tr_date(substr(ilan_posted($ilan), 0, 10))];
$blocks = [
    ['duties',       'A', 'Görevler',                   'num'],
    ['requirements', 'B', 'Aranan nitelikler',          'tick'],
    ['extras',       'C', 'Tercih sebebi olacaklar',    'box'],
];
$durum = (string) ($_GET['durum'] ?? '');
$sent  = $durum === 'tamam';
$cities = site('sehirler');
$levels = site('deneyim');
$left   = $ilan['deadline'] !== '' ? (int) round((strtotime($ilan['deadline']) - strtotime(date('Y-m-d'))) / 86400) : null;
?>

<section class="kr-head kr-head--ilan pagehead wrap" aria-labelledby="kr-title">
  <p class="label" data-rise><?= e(pg_label('kariyer')) ?> · Açık kadro</p>
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s"><?= e($ilan['title']) ?></h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <?php if ($ilan['summary'] !== ''): ?><p class="lead"><?= nl2br(e($ilan['summary']), false) ?></p><?php endif; ?>
    <p class="kr-head__more"><a class="link" href="<?= url('kariyer') ?>#acik-pozisyonlar">Tüm açık pozisyonlar</a></p>
  </div>
</section>

<section class="il wrap" aria-label="Kadro bilgileri">
  <aside class="il__side" data-rise>
    <div class="slip">
      <p class="slip__k">Kadro talep fişi</p>
      <p class="slip__no">Talep no <b><?= e($ref) ?></b></p>
      <dl class="slip__dl">
        <?php foreach ($facts as [$k, $v]): ?>
          <div><dt><?= e($k) ?></dt><span class="slip__dots" aria-hidden="true"></span><dd><?= e($v) ?></dd></div>
        <?php endforeach; ?>
      </dl>
      <p class="slip__due<?= $left !== null && $left <= 7 ? ' is-soon' : '' ?>">
        <span class="slip__due-k">Son başvuru</span>
        <?php if ($left !== null): ?>
          <b class="slip__due-d"><?= e(tr_date($ilan['deadline'])) ?></b>
          <?php if ($left <= 7): ?><span class="slip__due-n"><?= $left <= 0 ? 'Bugün son gün' : $left . ' gün kaldı' ?></span><?php endif; ?>
        <?php else: ?>
          <b class="slip__due-d is-none">Belirtilmedi</b>
          <span class="slip__due-n">İlan açık olduğu sürece başvuru alınır.</span>
        <?php endif; ?>
      </p>
      <div class="slip__tear"><a class="btn sendbtn slip__go" href="#basvuru">Başvuru formuna geçin <?= arrow() ?></a></div>
    </div>
    <p class="kr__note">Bu pozisyon size uymuyorsa özgeçmişinizi <a class="link" href="<?= url('kariyer') ?>#basvuru">genel başvuru formuyla</a> da bırakabilirsiniz.</p>
  </aside>

  <article class="il__sheet" data-rise aria-label="Kadro tanımı">
    <header class="il__head">
      <h2 class="il__title">Kadro tanımı</h2>
      <p class="il__date"><span>Tarih</span> <time datetime="<?= date('Y-m-d') ?>"><?= e($date) ?></time></p>
    </header>
    <?php foreach ($blocks as [$key, $letter, $label, $mark]): if (empty($ilan[$key])) continue; ?>
      <div class="kd__sec" role="group" aria-labelledby="il-<?= e($key) ?>">
        <p class="kd__band" id="il-<?= e($key) ?>"><b><?= $letter ?></b> <?= e($label) ?></p>
        <ol class="il__list il__list--<?= $mark ?>" role="list">
          <?php foreach ($ilan[$key] as $n => $item): ?>
            <li><span class="il__mk" aria-hidden="true"><?= $mark === 'num' ? nn($n + 1) : '' ?></span><span><?= e($item) ?></span></li>
          <?php endforeach; ?>
        </ol>
      </div>
    <?php endforeach; ?>
  </article>
</section>

<section class="kr kr--ilan wrap" aria-label="İlana başvuru">
  <aside class="kr__side" aria-label="Değerlendirme süreci">
    <div class="kr__block" data-rise>
      <h2 class="kr__h">Başvurunuz nasıl değerlendirilir?</h2>
      <ol class="kr__steps" role="list">
        <li>
          <span class="kr__step" aria-hidden="true">1</span>
          <p><b>Başvurunuz bu ilana bağlanır.</b> Form ve özgeçmişiniz, ilanın adıyla birlikte ekibimize ulaşır.</p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">2</span>
          <p><b>İnceleriz.</b> Deneyiminizi, aranan niteliklerle ve ekibimizin ihtiyacıyla birlikte değerlendiririz.</p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">3</span>
          <p><b>Size ulaşırız.</b> Uygun bulursak görüşme için sizinle iletişime geçeriz.</p>
        </li>
      </ol>
    </div>
    <p class="kr__note" data-rise>Başvurunuzda paylaştığınız bilgiler yalnızca işe alım süreci için kullanılır. Ayrıntılar için <a class="link" href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>#basvuru-adaylari">Aydınlatma Metni</a>’ne bakabilirsiniz.</p>
  </aside>

  <div class="kr__desk" id="basvuru" data-desk>
    <?php $krIlan = $ilan; require APP . '/partials/kariyer-form.php'; ?>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('kariyer') ?>">
    <span class="next__k">Diğer kadrolar için</span>
    <span class="next__t"><span>Kariyer</span></span>
    <?= arrow() ?>
  </a>
</div>
