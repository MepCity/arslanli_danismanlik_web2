<?php
/**
 * Çağrı takvimi: bütün çağrıların tarihleri tek bir sürekli form çizelgesinde, tarih sırasıyla.
 * Nesne: kenarları delikli, yeşil çizgili, daktilo harfleriyle basılmış "sürekli form" çıktısı (eski usul muhasebe ve tahakkuk listesi).
 * Duyuru panosundaki iğnelenmiş kâğıtlardan ve ana sayfadaki koparılan takvim yapraklarından ayrı bir nesnedir.
 * Duyuruları yalnızca ann_published() ve ann_events() ile okur; Duyurular bölümü kapalıysa hiçbir şey basmaz.
 * @var string $cagriMode  'home' (ilk 6 tarih, tümüne bağlantı) | 'page' (yaklaşan bütün tarihler)
 */
if (!feature('duyurular')) {
    return;
}
$mode  = ($cagriMode ?? 'home') === 'page' ? 'page' : 'home';
$today = date('Y-m-d');

$months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$short  = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
$wdays  = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];

// Yaklaşan tarihler: geçmiş tarihler çizelgeye hiç girmez (süresi dolan çağrılar panoda "Süresi doldu" damgasıyla durur)
$upcoming = array_values(array_filter(ann_events(ann_published()), fn($e) => $e['date'] >= $today));
$total    = count($upcoming);
$shown    = $mode === 'home' ? array_slice($upcoming, 0, 6) : $upcoming;

/** Tarihin türü: [damga yazısı, sınıf]. "Bilgilendirme" tarihlerinden ön kayıt olanı ayrı basılır. */
$kind = function (array $e): array {
    switch ($e['type']) {
        case 'baslangic': return ['Başvuru açılışı', 'open'];
        case 'son':       return ['Son başvuru', 'deadline'];
        case 'sonuc':     return ['Sonuç', 'result'];
    }
    return mb_stripos((string) $e['note'], 'ön kayıt') !== false ? ['Ön kayıt', 'pre'] : ['Önemli tarih', 'info'];
};

// Aya göre grupla (tarih sırası korunur)
$groups = [];
foreach ($shown as $e) {
    $groups[substr($e['date'], 0, 7)][] = $e;
}

page(['styles' => array_values(array_unique(array_merge(page()['styles'] ?? [], ['cagri'])))]);
$i = 0;
?>
<section class="cg cg--<?= $mode ?><?= $mode === 'home' ? ' section' : '' ?>" id="cagri-takvimi" aria-labelledby="cg-title">
  <div class="wrap">
    <header class="cg__head" data-rise>
      <p class="label">Çağrı takvimi</p>
      <h2 class="display h2 cg__h" id="cg-title">Önümüzdeki tarihler.</h2>
      <p class="cg__lead"><?= $shown ? 'Açık çağrıların başlangıç, ön kayıt, son başvuru ve sonuç günleri, tarih sırasıyla. Hangisine ne kadar süre kaldığı yanında yazar; bir satıra tıklarsanız çağrının duyurusuna gidersiniz.' : 'Açık çağrıların başlangıç, ön kayıt, son başvuru ve sonuç günleri burada tarih sırasıyla toplanır.' ?></p>
    </header>

    <div class="cg__form" data-rise style="--delay:.08s">
      <div class="cg__sheet">
        <p class="cg__run" aria-hidden="true">
          <span>Çağrı çizelgesi<?= $total ? ' · ' . $total . ' tarih' : '' ?></span>
          <span>Düzenleme: <?= e(today_official()) ?></span>
        </p>

        <?php if (!$shown): ?>
          <div class="cg__empty">
            <p class="cg__stars" aria-hidden="true">*** yaklaşan tarih yok ***</p>
            <p class="cg__none">Şu anda takvimde yaklaşan bir tarih görünmüyor. Yeni bir çağrı açıldığında başlangıç, son başvuru ve sonuç günleri bu çizelgeye işlenir.</p>
            <?php if (feature('bulten')): ?><p class="cg__none"><a class="link" href="<?= url('haberdarol') ?>" data-nl-open>Yeni çağrılardan haberdar olmak için bültene kayıt olun</a></p><?php endif; ?>
          </div>
        <?php else: ?>
          <?php foreach ($groups as $ym => $rows): ?>
            <h3 class="cg__mon"><span><?= e(tr_upper($months[(int) substr($ym, 5, 2) - 1])) ?> <?= e(substr($ym, 0, 4)) ?></span></h3>
            <ol class="cg__list" role="list">
              <?php foreach ($rows as $e):
                  [$kLabel, $kClass] = $kind($e);
                  $n    = ann_days_left($e['date']);
                  $t    = strtotime($e['date']);
                  $soon = $n <= 7;
                  $left = $n === 0 ? 'Bugün' : ($n === 1 ? 'Yarın' : $n . ' gün kaldı');
                  $i++; ?>
                <li class="cg__row cg__row--<?= $kClass ?><?= $soon ? ' is-soon' : '' ?>" data-on style="--delay:<?= round(0.05 * (($i - 1) % 4), 2) ?>s">
                  <a class="cg__a" href="<?= e(ann_url($e)) ?>" aria-label="<?= e(tr_date($e['date']) . ', ' . $wdays[(int) date('w', $t)] . ': ' . $kLabel . '. ' . $e['kurum'] . ', ' . $e['title'] . '. ' . $left) ?>">
                    <time class="cg__date" datetime="<?= e($e['date']) ?>"><b><?= (int) date('j', $t) ?></b><span><?= e(tr_upper($short[(int) date('n', $t) - 1])) ?></span><i><?= e(mb_substr($wdays[(int) date('w', $t)], 0, 3)) ?></i></time>
                    <span class="cg__kind"><?= e(tr_upper($kLabel)) ?></span>
                    <span class="cg__body">
                      <span class="cg__t"><?= e($e['title']) ?></span>
                      <span class="cg__org"><?= e($e['kurum']) ?><?= $e['note'] !== '' ? ' · ' . e($e['note']) : '' ?></span>
                    </span>
                    <span class="cg__left"><?php if ($n > 1): ?><b><?= $n ?></b><span>gün kaldı</span><?php else: ?><b><?= $left ?></b><?php endif; ?></span>
                    <?= arrow('arw cg__arw') ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php endforeach; ?>
          <p class="cg__stars" aria-hidden="true">*** <?= $mode === 'home' && $total > count($shown) ? 'devamı duyurular sayfasında' : 'liste sonu' ?> ***</p>
        <?php endif; ?>

        <p class="cg__foot">
          <a class="btn btn--sm" href="<?= url('duyurular.ics') ?>" type="text/calendar"><?= icon('calendar-blank') ?>Tüm tarihleri takvime ekle</a>
          <?php if ($mode === 'home'): ?>
            <a class="link ui" href="<?= url('duyurular') ?>">Duyuruların tamamı<?= $total > count($shown) ? ' (' . $total . ' tarih)' : '' ?></a>
          <?php else: ?>
            <span>Google, Apple ya da Outlook takviminize eklenir.</span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</section>
