<?php
/**
 * Duyurular: ilan panosu.
 * Resmî kurumların açık çağrıları, panoya iğnelenmiş ilan kâğıtları olarak durur: en yakın son tarih en üstte,
 * her kâğıdın köşesinde "kaç gün kaldı" yazan tarih damgası, altında fihrist gibi noktalı tarih satırları.
 * Kapanan çağrılar panonun altında "Süresi doldu" damgasıyla soluk kalır. Tarihler /duyurular.ics ile takvime eklenir.
 */
page([
    'id'          => 'announcements',
    'title'       => t('duyurular.seo.baslik'),
    'description' => t('duyurular.seo.description'),
    'folio'       => pg_folio('duyurular'),
]);

$types    = ann_types();
$all      = ann_published();
$open     = array_values(array_filter($all, fn($a) => !ann_is_past($a)));
$closed   = array_values(array_filter($all, fn($a) => ann_is_past($a)));
$featured = ann_featured();
$stampLbl = ['son' => t('duyurular.damga.son'), 'baslangic' => t('duyurular.damga.baslangic'), 'sonuc' => t('duyurular.damga.sonuc'), 'diger' => t('duyurular.damga.siradaki')];
/** Tarihe kalan süre ("Bugün", "Yarın", "5 gün kaldı", geçmişse "Geçti"). */
$leftText = fn(string $ymd): string => ($n = ann_days_left($ymd)) < 0 ? t('duyurular.kalan.gecti') : ($n === 0 ? t('duyurular.kalan.bugun') : ($n === 1 ? t('duyurular.kalan.yarin') : t('duyurular.kalan.gun', ['n' => $n])));
$rots     = [-0.7, 0.5, -0.3, 0.8, -0.5, 0.4, -0.2, 0.6];
$no       = 0;

/** Bir ilan kâğıdı */
$sheet = function (array $a) use (&$no, $types, $featured, $stampLbl, $rots, $leftText): void {
    $no++;
    $past   = ann_is_past($a);
    $target = $past ? null : ann_target($a);
    $left   = $target ? ann_days_left($target['date']) : null;
    $soon   = $left !== null && $left <= 7;
    $isFeat = $featured && $featured['id'] === $a['id'];
    $lastEv = $a['events'] ? end($a['events']) : null;
    ?>
  <article class="ilan<?= $past ? ' is-past' : '' ?><?= $isFeat ? ' is-feat' : '' ?>" id="duyuru-<?= e($a['id']) ?>" data-rise style="--rot:<?= $rots[($no - 1) % count($rots)] ?>deg;--delay:<?= round(0.04 * (($no - 1) % 2), 2) ?>s">
    <i class="ilan__pin" aria-hidden="true"></i>
    <div class="ilan__sheet">
      <header class="ilan__top">
        <div>
          <p class="ilan__kurum"><?= e($a['kurum']) ?></p>
          <p class="docmeta"><span><?= th('duyurular.kagit.no', ['no' => nn($no)]) ?></span><?php if ($isFeat): ?><span class="ilan__live"><i aria-hidden="true"></i><?= e(t('duyurular.kagit.canli')) ?></span><?php endif; ?></p>
        </div>
        <?php if ($past && $lastEv): ?>
          <div class="ilan__stamp ilan__stamp--past" data-on aria-label="<?= e(t('duyurular.damga.doldu_aria', ['tarih_uzun' => tr_date($lastEv['date'])])) ?>"><span class="ilan__stamp-k"><?= e(t('duyurular.damga.doldu')) ?></span><b><?= e(tr_upper(ann_short_date($lastEv['date']))) ?></b><span class="ilan__stamp-l"><?= e(date('Y', strtotime($lastEv['date']))) ?></span></div>
        <?php elseif ($target): ?>
          <div class="ilan__stamp<?= $soon ? ' is-soon' : '' ?>" data-on aria-label="<?= e(t('duyurular.damga.aria', ['tur' => $stampLbl[$target['type']], 'tarih_uzun' => tr_date($target['date']), 'kalan' => $leftText($target['date'])])) ?>"><span class="ilan__stamp-k"><?= e(tr_upper($stampLbl[$target['type']])) ?></span><b><?= e(tr_upper(ann_short_date($target['date']))) ?></b><span class="ilan__stamp-l"><?= e(tr_upper($leftText($target['date']))) ?></span></div>
        <?php endif; ?>
      </header>

      <h2 class="ilan__title"><?= e($a['title']) ?></h2>
      <p class="ilan__sum"><?= e($a['summary']) ?></p>

      <?php if ($a['events']): ?>
      <ol class="ilan__ev" aria-label="<?= e(t('duyurular.kagit.tarihler')) ?>">
        <?php foreach ($a['events'] as $ev):
            $evPast = $ev['date'] < date('Y-m-d');
            $n = ann_days_left($ev['date']); ?>
          <li class="ev ev--<?= e($ev['type']) ?><?= $evPast ? ' is-past' : '' ?>">
            <i class="ev__mk" aria-hidden="true"></i>
            <span class="ev__type"><?= e($types[$ev['type']] ?? t('duyurular.kagit.tur_yok')) ?></span>
            <span class="ev__dots" aria-hidden="true"></span>
            <time class="ev__date" datetime="<?= e($ev['date']) ?>"><?= e(tr_date($ev['date'])) ?></time>
            <?php if ($ev['note'] !== ''): ?><span class="ev__note"><?= e($ev['note']) ?></span><?php endif; ?>
            <span class="ev__left<?= $n >= 0 && $n <= 7 ? ' is-soon' : '' ?>"><?= e($leftText($ev['date'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>

      <p class="ilan__links">
        <a class="btn btn--sm" href="<?= e($a['link']) ?>" target="_blank" rel="noopener"><?= e(t('duyurular.kagit.resmi')) ?> <?= icon('arrow-up-right') ?></a>
        <a class="link ilan__cal" href="<?= url('duyurular/' . $a['id'] . '.ics') ?>" type="text/calendar"><?= icon('calendar-blank') ?><?= e(t('duyurular.kagit.takvime')) ?></a>
        <a class="link" href="<?= url('iletisim') ?>"><?= e(t('duyurular.kagit.birlikte')) ?></a>
      </p>
    </div>
  </article>
<?php };
?>

<section class="ann-head pagehead wrap" aria-labelledby="ann-title">
  <p class="label" data-rise><?= e(pg_label('duyurular')) ?></p>
  <h1 class="display ann-head__h" id="ann-title" data-rise style="--delay:.05s"><?= e(t('duyurular.hero.baslik')) ?></h1>
  <div class="ann-head__side" data-rise style="--delay:.12s">
    <p class="lead"><?= th('duyurular.hero.giris') ?></p>
    <p class="ann-head__ics">
      <a class="btn btn--sm" href="<?= url('duyurular.ics') ?>" type="text/calendar"><?= icon('calendar-blank') ?><?= e(t('duyurular.hero.takvim')) ?></a>
      <span><?= e(t('duyurular.hero.takvim_not')) ?></span>
    </p>
  </div>
</section>

<?php $cagriMode = 'page'; require APP . '/partials/cagri.php'; ?>

<section class="board" aria-label="<?= e(t('duyurular.pano.etiket')) ?>">
  <div class="board__in wrap">
    <?php if (!$all): ?>
      <p class="board__empty"><?= th('duyurular.pano.bos') ?></p>
    <?php endif; ?>
    <?php if ($open): ?>
    <div class="board__grid">
      <?php foreach ($open as $a) { $sheet($a); } ?>
    </div>
    <?php endif; ?>
    <?php if ($closed): ?>
    <p class="board__sub"><span><?= e(t('duyurular.pano.arsiv')) ?></span><em><?= e(t('duyurular.pano.arsiv_not')) ?></em></p>
    <div class="board__grid board__grid--past">
      <?php foreach ($closed as $a) { $sheet($a); } ?>
    </div>
    <?php endif; ?>
  </div>
</section>
