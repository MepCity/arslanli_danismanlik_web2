<?php
$timeline = site('timeline');
page([
    'id'          => 'about',
    'title'       => 'Hakkımızda',
    'description' => 'Arslanlı Yatırım & Danışmanlık 2007’de İstanbul’da kuruldu. TÜBİTAK, KOSGEB, Bakanlık, ihracat ve AB desteklerinde başvuru dosyasını hazırlıyor, kabulden sonra raporlama ve ödeme taleplerini yürütüyoruz.',
    'folio'       => 'Evrak 02 · <b>Hakkımızda</b>',
]);

// Klasör renkleri (ofis klasörü sırtları) ve kalınlıkları
$colors = ['#2b2d32', '#34497a', '#5e625e', '#7a302a', '#2f5a4e', '#3b3e46', '#46587f', '#6b6860', '#2c2e34', '#56405c', '#37544a', '#30364a', '#4b4f58'];
$widths = [1, 0.86, 1.14, 1.28, 1, 0.92, 1.1, 0.95, 1.2, 1, 0.86, 1.06, 1.16];
$initial = 0;
foreach ($timeline as $i => $t) {
    if ($t['kind'] === 'us') { $initial = $i; break; }
}

$cards = [
    ['kurumsal/misyonumuz', 'KUR 08', 'Misyonumuz', 'Beş maddelik iş listemiz. Her dosyada bu işleri yaparız.'],
    ['kurumsal/vizyonumuz', 'KUR 09', 'Vizyonumuz', 'Kuruluşumuzun otuzuncu yılında, ' . site('vision')['open_year'] . '’de açılacak bir mektup.'],
    ['kurumsal/mihenk-taslarimiz', 'KUR 10', 'Mihenk Taşlarımız', 'Her dosyada uyduğumuz altı ilke.'],
];
?>

<!-- Başlık -->
<section class="ab-head pagehead" aria-labelledby="ab-title">
  <div class="wrap">
    <p class="docmeta"><span>Sayı: <b>ARS-<?= date('Y') ?>/002</b></span><span>Konu: <b>Hakkımızda</b></span><span>Tarih: <b><?= today_official() ?></b></span></p>
    <h1 class="display ab-head__h" id="ab-title"><span class="ln"><?= cfg('founded') ?>’den beri</span> <span class="ln">dosya</span> <span class="ln">hazırlıyoruz.</span></h1>
    <div class="ab-head__cols">
      <p class="lead" data-rise><?= e(cfg('name')) ?>, <?= cfg('founded') ?>’de İstanbul’da kuruldu. İşletmelerin TÜBİTAK, KOSGEB, Sanayi ve Teknoloji Bakanlığı, Ticaret Bakanlığı ve Avrupa Birliği desteklerine yaptığı başvuruların dosyasını hazırlıyoruz.</p>
      <p class="ab-head__p" data-rise style="--delay:.08s">İşimiz başvuruyla bitmiyor. Kabulden sonra dönem raporlarını, harcama belgelerini, denetim hazırlığını ve ödeme taleplerini proje kapanana kadar biz yürütüyoruz. <?= years_active() ?> yıldır aynı işi yapıyoruz: mevzuatı okuyup işletmenin anlayacağı dile çeviriyoruz.</p>
    </div>
  </div>
</section>

<!-- Arşiv rafı -->
<section class="raf section" aria-labelledby="raf-title" data-raf>
  <div class="wrap">
    <header class="raf__head">
      <p class="label">Arşiv rafı · <?= $timeline[0]['year'] ?>–<?= end($timeline)['year'] ?></p>
      <h2 class="display h2" id="raf-title">Kurulduğumuzdan beri izlediğimiz mevzuat</h2>
      <p class="raf__note">Raftaki her klasör, kuruluşumuzdan bu yana takip ettiğimiz bir mevzuat değişikliği; ilk üçü bizden önce gelen temel düzenlemeler. <span class="only-fine-ab">Bir klasörü çekip içini okuyun.</span><span class="only-touch-ab">Bir klasöre dokunup içini okuyun.</span></p>
    </header>

    <div class="shelf" data-shelf>
      <div class="shelf__scroll" data-shelf-scroll>
        <div class="shelf__row" data-tabs aria-label="Yıllara göre mevzuat klasörleri">
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
          <p class="pn__k">Klasör <?= $t['year'] ?> · <?= $t['kind'] === 'us' ? 'Bizim yılımız' : ($t['year'] < cfg('founded') ? 'Kuruluşumuzdan önce' : 'Mevzuat değişikliği') ?></p>
          <h3 class="pn__t"><?= e($t['title']) ?></h3>
          <p class="pn__x"><?= e($t['text']) ?></p>
          <p class="pn__src">Künye: <?= e($t['src']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Çalışma usulü -->
<section class="usul section" aria-labelledby="usul-title">
  <div class="wrap usul__in">
    <header class="usul__head">
      <p class="label">Çalışma usulü</p>
      <h2 class="display h2" id="usul-title">Nasıl çalışırız</h2>
    </header>
    <ol class="usul__list" role="list">
      <li class="usul__m" data-rise>
        <p class="usul__no">Madde 1 –</p>
        <h3 class="usul__t">Önce okuruz.</h3>
        <p>Çağrı metnini, uygulama esaslarını ve değerlendirme formunu; sonra sizin işinizi. İkisi örtüşmüyorsa <?= annot('başvuru önermeyiz', 'under', 'red') ?>.</p>
      </li>
      <li class="usul__m" data-rise>
        <p class="usul__no">Madde 2 –</p>
        <h3 class="usul__t">Sonra yazarız.</h3>
        <p>Proje önerisini, bütçeyi ve iş planını programın istediği sırayla ve diliyle yazarız. Hiçbir taslak sizin <?= annot('onayınız', 'circle') ?> olmadan sisteme girmez.</p>
      </li>
      <li class="usul__m" data-rise>
        <p class="usul__no">Madde 3 –</p>
        <h3 class="usul__t">Kabulden sonra da yürütürüz.</h3>
        <p>Dönem raporları, harcama belgeleri, denetim hazırlığı ve ödeme talepleri proje kapanana kadar bir takvime bağlıdır. <?= annot('O takvimi biz tutarız', 'under') ?>.</p>
      </li>
    </ol>
  </div>
</section>

<!-- Sicil kaydı -->
<section class="sicil section" aria-labelledby="sicil-title">
  <div class="wrap sicil__in">
    <div class="sicil__text">
      <p class="label">Sicil kaydı</p>
      <h2 class="display h2" id="sicil-title">Resmî bilgilerimiz</h2>
      <p class="sicil__p">Teklif, sözleşme ya da fatura için ihtiyaç duyacağınız bilgiler bu formda. Banka hesaplarımız ayrı bir evrakta.</p>
      <a class="link ui" href="<?= url('hesap-numaralarimiz') ?>">Hesap numaralarımız</a>
    </div>

    <div class="fcard" data-typed>
      <div class="fcard__head">
        <span class="fcard__title">Sicil kaydı</span>
        <span class="fcard__no">Form ARS-02 · <?= date('Y') ?></span>
      </div>
      <dl class="fcard__grid">
        <div class="fc fc--wide"><dt>Ticari unvan</dt><dd data-type><?= e(cfg('name')) ?></dd></div>
        <div class="fc"><dt>Kuruluş</dt><dd data-type><?= cfg('founded') ?>, İstanbul</dd></div>
        <div class="fc"><dt>Yetkili</dt><dd data-type><?= e(cfg('company.authorized')) ?></dd></div>
        <div class="fc"><dt>Vergi dairesi</dt><dd data-type><?= e(cfg('company.tax_office')) ?></dd></div>
        <div class="fc"><dt>Vergi numarası</dt><dd data-type><?= e(cfg('company.tax_number')) ?></dd></div>
        <div class="fc fc--wide"><dt>Adres</dt><dd data-type><?= e(cfg('address')) ?></dd></div>
        <div class="fc"><dt>Telefon</dt><dd><a data-type href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a></dd></div>
        <div class="fc"><dt>E-posta</dt><dd><a data-type href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></dd></div>
        <div class="fc fc--wide"><dt>Faaliyet konusu</dt><dd data-type>Hibe, teşvik ve yatırım danışmanlığı: proje yazımı, başvuru, raporlama ve yürütme</dd></div>
      </dl>
    </div>
  </div>
</section>

<!-- Katalog kartları -->
<section class="katalog section" aria-labelledby="katalog-title">
  <div class="wrap">
    <header class="katalog__head">
      <p class="label">Kurumsal</p>
      <h2 class="display h2" id="katalog-title">Diğer kurumsal evraklar</h2>
    </header>
    <ul class="kartlar" role="list">
      <?php foreach ($cards as $i => [$to, $call, $title, $desc]): ?>
        <li style="--r:<?= [-2.2, 1.4, -0.8][$i] ?>deg">
          <a class="kart" href="<?= url($to) ?>">
            <span class="kart__call"><?= e($call) ?></span>
            <span class="kart__t"><?= e($title) ?></span>
            <span class="kart__d"><?= e($desc) ?></span>
            <span class="kart__go">Evrakı açın <?= arrow() ?></span>
            <span class="kart__hole" aria-hidden="true"></span>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('hizmetler') ?>">
    <span class="next__k">Sonraki evrak · 03</span>
    <span class="next__t"><span>Hizmetler</span></span>
    <?= arrow() ?>
  </a>
</div>
