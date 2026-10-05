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
    'title'       => $open ? $ilan['title'] : t('ilan.seo.kapali_baslik'),
    'description' => $open ? ilan_seo_description($ilan) : t('ilan.seo.kapali_aciklama'),
    'folio'       => pg_folio('kariyer'),
    'noindex'     => !$open,
]);

/* ---------- Kapalı ya da süresi dolmuş ilan ---------- */
if (!$open):
    $expired = ilan_state($ilan) === 'doldu';
    $others  = ilan_published();
?>
<section class="kr-head kr-head--ilan pagehead wrap" aria-labelledby="kr-title">
  <p class="label" data-rise><?= e(pg_label('kariyer', t('ilan.kapali.etiket', ['sayfa' => pg_name('kariyer')]))) ?></p>
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s"><?= th('ilan.kapali.baslik') ?></h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <p class="lead"><?= th($expired ? 'ilan.kapali.giris_doldu' : 'ilan.kapali.giris_kapali') ?></p>
    <p class="kr-head__more"><?= th('ilan.kapali.devam') ?></p>
  </div>
</section>

<section class="ilc wrap" aria-label="<?= e(t('ilan.kapali.alan_etiket')) ?>">
  <div class="ilc__slip" data-rise>
    <span class="ilc__stub" aria-hidden="true"><small><?= e(t('ilan.kapali.kadro')) ?></small><b>—</b></span>
    <div class="ilc__body">
      <p class="ilc__ref"><?= th('ilan.kapali.talep', ['no' => $ref]) ?></p>
      <h2 class="ilc__title"><?= e($ilan['title']) ?></h2>
      <p class="ilc__meta"><?= e(implode(' · ', ilan_meta($ilan))) ?></p>
    </div>
    <div class="ilc__stamp" aria-hidden="true">
      <svg viewBox="0 0 300 130">
        <rect x="4" y="4" width="292" height="122" rx="10" fill="none" stroke="currentColor" stroke-width="4"/>
        <rect x="12" y="12" width="276" height="106" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
        <text x="150" y="66" text-anchor="middle" font-size="40" font-weight="900" style="font-stretch:60%;letter-spacing:.04em"><?= e(tr_upper(t('ilan.kapali.damga'))) ?></text>
        <text x="150" y="97" text-anchor="middle" font-size="16" font-weight="700" style="font-stretch:78%"><?= e(tr_upper($expired ? t('ilan.kapali.damga_doldu', ['gun' => tr_date((string) $ilan['deadline'])]) : t('ilan.kapali.damga_kapali'))) ?></text>
      </svg>
    </div>
  </div>
  <p class="ilc__act" data-rise>
    <a class="btn sendbtn" href="<?= url('kariyer') ?>#basvuru"><?= e(t('ilan.kapali.forma_git')) ?> <?= arrow() ?></a>
    <?php if ($others): ?><a class="link" href="<?= url('kariyer') ?>#acik-pozisyonlar"><?= e(t('ilan.kapali.diger')) ?></a><?php endif; ?>
  </p>
</section>
<?php return; endif;

/* ---------- Açık ilan ---------- */
$facts = [[t('ilan.fis.alan'), $ilan['area']], [t('ilan.fis.sehir'), $ilan['city']], [t('ilan.fis.tur'), $ilan['type']]];
if ($ilan['experience'] !== '') {
    $facts[] = [t('ilan.fis.deneyim'), $ilan['experience']];
}
$facts[] = [t('ilan.fis.yayin'), tr_date(substr(ilan_posted($ilan), 0, 10))];
$blocks = [
    ['duties',       'A', t('ilan.tanim.gorevler'),    'num'],
    ['requirements', 'B', t('ilan.tanim.nitelikler'),  'tick'],
    ['extras',       'C', t('ilan.tanim.tercih'),      'box'],
];
$durum = (string) ($_GET['durum'] ?? '');
$sent  = $durum === 'tamam';
$cities = site('sehirler');
$levels = site('deneyim');
$left   = $ilan['deadline'] !== '' ? (int) round((strtotime($ilan['deadline']) - strtotime(date('Y-m-d'))) / 86400) : null;
?>

<section class="kr-head kr-head--ilan pagehead wrap" aria-labelledby="kr-title">
  <p class="label" data-rise><?= e(pg_label('kariyer', t('ilan.hero.etiket', ['sayfa' => pg_name('kariyer')]))) ?></p>
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s"><?= e($ilan['title']) ?></h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <?php if ($ilan['summary'] !== ''): ?><p class="lead"><?= nl2br(e($ilan['summary']), false) ?></p><?php endif; ?>
    <p class="kr-head__more"><a class="link" href="<?= url('kariyer') ?>#acik-pozisyonlar"><?= e(t('ilan.hero.tumu')) ?></a></p>
  </div>
</section>

<section class="il wrap" aria-label="<?= e(t('ilan.fis.alan_etiket')) ?>">
  <aside class="il__side" data-rise>
    <div class="slip">
      <p class="slip__k"><?= e(t('ilan.fis.baslik')) ?></p>
      <p class="slip__no"><?= th('ilan.fis.talep', ['no' => $ref]) ?></p>
      <dl class="slip__dl">
        <?php foreach ($facts as [$k, $v]): ?>
          <div><dt><?= e($k) ?></dt><span class="slip__dots" aria-hidden="true"></span><dd><?= e($v) ?></dd></div>
        <?php endforeach; ?>
      </dl>
      <p class="slip__due<?= $left !== null && $left <= 7 ? ' is-soon' : '' ?>">
        <span class="slip__due-k"><?= e(t('ilan.fis.son_basvuru')) ?></span>
        <?php if ($left !== null): ?>
          <b class="slip__due-d"><?= e(tr_date($ilan['deadline'])) ?></b>
          <?php if ($left <= 7): ?><span class="slip__due-n"><?= e($left <= 0 ? t('ilan.fis.bugun') : t('ilan.fis.kalan', ['n' => $left])) ?></span><?php endif; ?>
        <?php else: ?>
          <b class="slip__due-d is-none"><?= e(t('ilan.fis.belirtilmedi')) ?></b>
          <span class="slip__due-n"><?= e(t('ilan.fis.suresiz')) ?></span>
        <?php endif; ?>
      </p>
      <div class="slip__tear"><a class="btn sendbtn slip__go" href="#basvuru"><?= e(t('ilan.fis.forma_git')) ?> <?= arrow() ?></a></div>
    </div>
    <p class="kr__note"><?= th('ilan.fis.not', ['baglanti' => ['html' => '<a class="link" href="' . url('kariyer') . '#basvuru">' . e(t('ilan.fis.not_baglanti')) . '</a>']]) ?></p>
  </aside>

  <article class="il__sheet" data-rise aria-label="<?= e(t('ilan.tanim.alan_etiket')) ?>">
    <header class="il__head">
      <h2 class="il__title"><?= e(t('ilan.tanim.baslik')) ?></h2>
      <p class="il__date"><span><?= e(t('ilan.tanim.tarih')) ?></span> <time datetime="<?= date('Y-m-d') ?>"><?= e($date) ?></time></p>
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

<section class="kr kr--ilan wrap" aria-label="<?= e(t('ilan.basvuru.alan_etiket')) ?>">
  <aside class="kr__side" aria-label="<?= e(t('ilan.basvuru.yan_etiket')) ?>">
    <div class="kr__block" data-rise>
      <h2 class="kr__h"><?= e(t('ilan.basvuru.surec')) ?></h2>
      <ol class="kr__steps" role="list">
        <li>
          <span class="kr__step" aria-hidden="true">1</span>
          <p><?= th('ilan.basvuru.bir') ?></p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">2</span>
          <p><?= th('ilan.basvuru.iki') ?></p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">3</span>
          <p><?= th('ilan.basvuru.uc') ?></p>
        </li>
      </ol>
    </div>
    <p class="kr__note" data-rise><?= th('ilan.basvuru.not', ['baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '#basvuru-adaylari">' . e(t('ilan.basvuru.not_baglanti')) . '</a>']]) ?></p>
  </aside>

  <div class="kr__desk" id="basvuru" data-desk>
    <?php $krIlan = $ilan; require APP . '/partials/kariyer-form.php'; ?>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('kariyer') ?>">
    <span class="next__k"><?= e(t('ilan.sonraki.etiket')) ?></span>
    <span class="next__t"><span><?= e(pg_name('kariyer')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
