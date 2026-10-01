<?php
$all = services();
page([
    'id'          => 'services',
    'title'       => 'Hizmetler',
    'description' => 'TÜBİTAK, KOSGEB, Sanayi ve Teknoloji Bakanlığı, Ticaret Bakanlığı, AB projeleri, sınai mülkiyet, kalite belgelendirme, yatırım danışmanlığı ve yatırım kredilerinde başvuru dosyası ve yürütme.',
    'folio'       => 'Evrak 03 · <b>Hizmetler</b>',
]);

// "Ne yapmak istiyorsunuz?" → ilgili dosyalar
$goals = [
    'arge'    => ['Ar-Ge projesi yapmak', ['tubitak-1989', 'kosgeb-1659', 'sanayi-ve-teknoloji-bakanligi-1329', 'avrupa-birligi-projeleri-2319']],
    'merkez'  => ['Ar-Ge ya da tasarım merkezi kurmak', ['sanayi-ve-teknoloji-bakanligi-1329']],
    'yatirim' => ['Makine ya da tesis yatırımı', ['sanayi-ve-teknoloji-bakanligi-1329', 'kosgeb-1659', 'yatirim-danismanligi-2649', 'yatirima-yonelik-krediler-2979']],
    'ihracat' => ['İhracat, fuar ve tanıtım', ['ticaret-bakanligi-destekleri-999']],
    'marka'   => ['Marka ya da patent', ['sinai-mulkiyet-haklari-tescilleri-669', 'tubitak-1989']],
    'kalite'  => ['ISO belgesi almak', ['kalite-belgelendirme-339']],
    'kredi'   => ['Uygun maliyetli kredi', ['yatirima-yonelik-krediler-2979', 'yatirim-danismanligi-2649']],
    'ab'      => ['Avrupa’dan ortak bulmak', ['avrupa-birligi-projeleri-2319']],
];
// Dosya sırtındaki etiketlerin yatay konumları (gerçek askılı dosyalardaki gibi kademeli)
$tabs = [4, 22, 40, 58, 74, 13, 31, 49, 66];
?>

<section class="dhead pagehead" aria-labelledby="dhead-title">
  <div class="wrap dhead__in">
    <p class="docmeta"><span>Evrak <b>03</b></span><span>Konu: <b>Hizmet alanlarımız</b></span><span>Ek: <b><?= count($all) ?> dosya</b></span></p>
    <h1 class="display dhead__h" id="dhead-title">Dosya dolabı</h1>
    <p class="lead dhead__lead">Her destek türü için ayrı bir dosya tutuyoruz: kimler başvurabilir, hangi programlar var, sizden hangi evrak istenir. Aşağı kaydırdıkça dosyalar tek tek öne gelir. Ne yapmak istediğinizi işaretlerseniz ilgili dosyaları öne çıkarırız.</p>
  </div>
</section>

<section class="dolap" data-dolap aria-label="Hizmet dosyaları">
  <div class="dolap__in wrap" data-dolap-pin>

    <aside class="eleme" aria-labelledby="eleme-h">
      <h2 class="eleme__h" id="eleme-h">Ne yapmak istiyorsunuz?</h2>
      <p class="eleme__hint">Birini işaretleyin; ilgili dosyalar öne çıkar.</p>
      <ul class="eleme__list" role="list">
        <?php foreach ($goals as $key => [$label, $slugs]): ?>
          <li>
            <button class="eleme__opt" type="button" aria-pressed="false" data-goal="<?= e($key) ?>" data-slugs="<?= e(implode(',', $slugs)) ?>">
              <span class="eleme__box" aria-hidden="true"></span><span><?= e($label) ?></span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="eleme__out" aria-live="polite" data-eleme-out>Dolapta <?= count($all) ?> dosya var. Hepsine aşağıdan ulaşabilirsiniz.</p>
    </aside>

    <div class="drawer" data-drawer>
      <div class="drawer__scene" data-scene>
        <span class="drawer__rail drawer__rail--l" aria-hidden="true"></span>
        <span class="drawer__rail drawer__rail--r" aria-hidden="true"></span>
        <ol class="files" role="list">
          <?php $i = 0; foreach ($all as $slug => $s): ?>
            <li class="file" id="dosya-<?= e($s['no']) ?>" style="--c:var(--f-<?= e($s['color']) ?>);--tab:<?= $tabs[$i % count($tabs)] ?>%" data-file="<?= $i ?>" data-slug="<?= e($slug) ?>">
              <article class="file__card" aria-labelledby="ft-<?= e($s['no']) ?>">
                <span class="file__hook file__hook--l" aria-hidden="true"></span>
                <span class="file__hook file__hook--r" aria-hidden="true"></span>
                <a class="file__tab" href="#dosya-<?= e($s['no']) ?>" data-tab="<?= $i ?>" aria-label="Dosya 03.<?= (int) $s['no'] ?>: <?= e($s['title']) ?> dosyasını öne getir">
                  <span class="file__tabno">03.<?= (int) $s['no'] ?></span><span class="file__tabt"><?= e($s['tab']) ?></span>
                </a>
                <div class="file__face">
                  <div class="file__label">
                    <p class="file__no"><span>Dosya No</span> <b>03.<?= (int) $s['no'] ?></b></p>
                    <h3 class="file__t" id="ft-<?= e($s['no']) ?>"><a href="<?= service_url($slug) ?>"><?= e($s['title']) ?></a></h3>
                  </div>
                  <p class="file__short"><?= e($s['short']) ?></p>
                  <div class="file__inside">
                    <p class="file__ih">Dosyada</p>
                    <ul class="file__progs" role="list">
                      <?php foreach ($s['programs'] as [$name]): ?><li><?= e($name) ?></li><?php endforeach; ?>
                    </ul>
                  </div>
                  <p class="file__fit"><span>Kimin için:</span> <?= e($s['fit']) ?></p>
                  <a class="btn btn--ink btn--sm file__open" href="<?= service_url($slug) ?>" tabindex="-1" aria-hidden="true">Dosyayı açın <?= arrow() ?></a>
                </div>
              </article>
            </li>
          <?php $i++; endforeach; ?>
        </ol>
      </div>
      <div class="drawer__front" aria-hidden="true">
        <span class="drawer__plate">03 · Hizmetler</span>
        <span class="drawer__handle"></span>
        <span class="drawer__count" data-count>1 / <?= count($all) ?></span>
      </div>
    </div>
  </div>
</section>

<section class="dsor section" aria-labelledby="dsor-title">
  <div class="wrap dsor__in">
    <h2 class="display dsor__h" id="dsor-title">Hangi dosyanın sizin olduğundan emin değil misiniz?</h2>
    <div>
      <p class="lead">Çoğu işletme birden fazla dosyaya girer: bir Ar-Ge projesi, aynı yıl bir makine yatırımı, ardından bir fuar. Ne yapmak istediğinizi anlatın; hangi dosyaların hangi sırayla açılacağını birlikte çıkaralım.</p>
      <p class="dsor__act"><a class="btn btn--ink" href="<?= url('iletisim') ?>">Ön görüşme isteyin <?= arrow() ?></a></p>
    </div>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('referans') ?>">
    <span class="next__k">Sonraki evrak · 04</span>
    <span class="next__t"><span>Referanslar</span></span>
    <?= arrow() ?>
  </a>
</div>
