<?php
/**
 * Açılışta öne çıkan duyuru: masaya gelen acele evrak.
 * Bir duyuru öne çıkarıldığında (app/data/duyurular.php, 'featured'), süresi dolana kadar ziyaretçiye oturum başına bir kez
 * ekranın ortasında açılır; kapatılınca sol altta küçük bir çip kalır ve pencereyi yeniden açar.
 * İşaretleme <template> içindedir: arama motorları sayfa metni olarak görmez, assets/js/spotlight.js açar.
 * @var array $spot  ann_featured() sonucu
 */
$types  = ann_types();
$today  = date('Y-m-d');
$events = $spot['events'];
$target = ann_target($spot);
$untilLbl = $target ? ['son' => 'Son başvuruya kalan', 'baslangic' => 'Başvuruların açılmasına', 'sonuc' => 'Sonuçların açıklanmasına', 'diger' => 'Bir sonraki tarihe'][$target['type']] : '';

// Tarih cetveli: ilk ve son tarih arasında orantılı konum (bayraklar). Etiketler cetvelin altında eşit sütunlarda durur,
// bu yüzden tarihler ne kadar yakın olursa olsun üst üste binmez; spotlight.js bayraklardan etiketlere ince iplik çeker.
$dates = array_column($events, 'date');
$first = $dates ? strtotime(min($dates)) : 0;
$last  = $dates ? strtotime(max($dates)) : 0;
$pos   = fn(string $d) => $last > $first ? round((strtotime($d) - $first) / ($last - $first) * 100, 3) : 50;
$todayPos = $last > $first && $today > min($dates) && $today < max($dates) ? $pos($today) : null;

$key = substr(md5($spot['id'] . '|' . $spot['updated']), 0, 10);
?>
<template id="spot-tpl"
  data-key="<?= e($key) ?>"
  data-target="<?= $target ? e($target['date'] . 'T23:59:59' . date('P', strtotime($target['date']))) : '' ?>"
  data-page="<?= e(page()['id']) ?>">
  <dialog class="spot" aria-labelledby="spot-t" aria-describedby="spot-s">
    <div class="spot__sheet">
      <span class="spot__live"><i aria-hidden="true"></i>Canlı çağrı</span>
      <button class="spot__close" type="button" data-spot-close aria-label="Kapat"><?= icon('x') ?></button>

      <div class="spot__scroll" data-spot-scroll data-lenis-prevent>
        <p class="docmeta spot__meta"><span>Kurum: <b><?= e($spot['kurum']) ?></b></span><span>Tarih: <b><?= today_official() ?></b></span></p>

        <div class="spot__body">
          <div class="spot__text">
            <h2 class="spot__title<?= mb_strlen($spot['title']) > 64 ? ' spot__title--long' : '' ?>" id="spot-t"><?= e($spot['title']) ?></h2>
            <p class="spot__sum" id="spot-s"><?= e($spot['summary']) ?></p>
          </div>
          <?php if ($target): ?>
          <div class="spot__count" data-spot-count>
            <p class="spot__count-l"><?= e($untilLbl) ?></p>
            <div class="spot__digits" role="timer" aria-label="<?= e($untilLbl) ?>">
              <span><b data-u="d">00</b><small>gün</small></span>
              <span><b data-u="h">00</b><small>saat</small></span>
              <span><b data-u="m">00</b><small>dakika</small></span>
              <span><b data-u="s">00</b><small>saniye</small></span>
            </div>
            <p class="spot__count-date"><?= e(tr_date($target['date'])) ?><?= $target['note'] !== '' ? ' · ' . e($target['note']) : '' ?></p>
            <p class="spot__lastday" data-spot-lastday hidden>Bugün son gün</p>
          </div>
          <?php endif; ?>
        </div>

        <div class="spot__time<?= count($events) > 1 ? ' has-ruler' : '' ?>" data-spot-time>
          <p class="label spot__time-h">Önemli tarihler</p>
          <?php if (count($events) > 1): ?>
          <div class="spot__ruler" aria-hidden="true">
            <?php if ($todayPos !== null): ?><i class="spot__today" style="--x:<?= $todayPos ?>%"><span>bugün</span></i><?php endif; ?>
            <?php foreach ($events as $ev): ?><i class="spot__flag spot__flag--<?= e($ev['type']) ?><?= $ev['date'] < $today ? ' is-past' : '' ?>" style="--x:<?= $pos($ev['date']) ?>%" data-flag></i><?php endforeach; ?>
          </div>
          <svg class="spot__ties" aria-hidden="true" focusable="false"></svg>
          <?php endif; ?>
          <ol class="spot__evs" style="--n:<?= count($events) ?>">
            <?php foreach ($events as $ev): ?>
            <li class="spot__ev spot__ev--<?= e($ev['type']) ?><?= $ev['date'] < $today ? ' is-past' : '' ?>" data-ev>
              <span class="spot__ev-t"><?= e($types[$ev['type']] ?? 'Bilgilendirme') ?></span>
              <time class="spot__ev-d" datetime="<?= e($ev['date']) ?>"><?= e(tr_date($ev['date'])) ?></time>
              <?php if ($ev['note'] !== ''): ?><span class="spot__ev-n"><?= e($ev['note']) ?></span><?php endif; ?>
            </li>
            <?php endforeach; ?>
          </ol>
        </div>

        <div class="spot__acts">
          <a class="btn btn--ink" href="<?= e(ann_url($spot)) ?>" data-spot-go>Duyuruyu aç <?= arrow() ?></a>
          <a class="btn" href="<?= e($spot['link']) ?>" target="_blank" rel="noopener">Resmî kaynak <?= icon('arrow-up-right') ?></a>
          <a class="link spot__ics" href="<?= url('duyurular/' . $spot['id'] . '.ics') ?>" type="text/calendar"><?= icon('calendar-blank') ?>Takvime ekle</a>
        </div>
      </div>
    </div>
  </dialog>
  <button class="spot-chip" type="button" data-spot-chip aria-haspopup="dialog">
    <i aria-hidden="true"></i><span class="spot-chip__l">Canlı çağrı</span><span class="spot-chip__t"><?= e($spot['title']) ?></span>
  </button>
</template>
