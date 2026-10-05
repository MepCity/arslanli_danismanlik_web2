<?php
$all = services();
page([
    'id'          => 'services',
    'title'       => pg_name('hizmetler'),
    'description' => t('hizmetler.seo.description'),
    'folio'       => pg_folio('hizmetler'),
]);

// "Ne yapmak istiyorsunuz?" → ilgili dosyalar (seçenek yazısı: hizmetler.eleme.hedef_{anahtar})
$goals = [
    'arge'    => ['tubitak-1989', 'kosgeb-1659', 'sanayi-ve-teknoloji-bakanligi-1329', 'avrupa-birligi-projeleri-2319'],
    'merkez'  => ['sanayi-ve-teknoloji-bakanligi-1329'],
    'yatirim' => ['sanayi-ve-teknoloji-bakanligi-1329', 'kosgeb-1659', 'yatirim-danismanligi-2649', 'yatirima-yonelik-krediler-2979'],
    'ihracat' => ['ticaret-bakanligi-destekleri-999'],
    'marka'   => ['sinai-mulkiyet-haklari-tescilleri-669', 'tubitak-1989'],
    'kalite'  => ['kalite-belgelendirme-339'],
    'kredi'   => ['yatirima-yonelik-krediler-2979', 'yatirim-danismanligi-2649'],
    'ab'      => ['avrupa-birligi-projeleri-2319'],
];
// Dosya sırtındaki etiketlerin yatay konumları (gerçek askılı dosyalardaki gibi kademeli)
$tabs = [4, 22, 40, 58, 74, 13, 31, 49, 66];
?>

<section class="dhead pagehead" aria-labelledby="dhead-title">
  <div class="wrap dhead__in">
    <p class="docmeta"><span><?= th('hizmetler.hero.evrak', ['evrak' => folio_word(), 'no' => pg_no('hizmetler')]) ?></span><span><?= th('hizmetler.hero.konu') ?></span><span><?= th('hizmetler.hero.ek') ?></span></p>
    <h1 class="display dhead__h" id="dhead-title"><?= e(t('hizmetler.hero.baslik')) ?></h1>
    <p class="lead dhead__lead"><?= e(t('hizmetler.hero.giris')) ?></p>
  </div>
</section>

<section class="dolap" data-dolap aria-label="<?= e(t('hizmetler.dolap.etiket')) ?>">
  <div class="dolap__in wrap" data-dolap-pin>

    <aside class="eleme" aria-labelledby="eleme-h">
      <h2 class="eleme__h" id="eleme-h"><?= e(t('hizmetler.eleme.baslik')) ?></h2>
      <p class="eleme__hint"><?= e(t('hizmetler.eleme.ipucu')) ?></p>
      <ul class="eleme__list" role="list">
        <?php foreach ($goals as $key => $slugs): ?>
          <li>
            <button class="eleme__opt" type="button" aria-pressed="false" data-goal="<?= e($key) ?>" data-slugs="<?= e(implode(',', $slugs)) ?>">
              <span class="eleme__box" aria-hidden="true"></span><span><?= e(t('hizmetler.eleme.hedef_' . $key)) ?></span>
            </button>
          </li>
        <?php endforeach; ?>
      </ul>
      <p class="eleme__out" aria-live="polite" data-eleme-out><?= e(t('hizmetler.eleme.sonuc')) ?></p>
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
                <a class="file__tab" href="#dosya-<?= e($s['no']) ?>" data-tab="<?= $i ?>" aria-label="<?= e(t('hizmetler.dosya.sekme', ['no' => svc_no($s), 'baslik' => $s['title']])) ?>">
                  <span class="file__tabno"><?= svc_no($s) ?></span><span class="file__tabt"><?= e($s['tab']) ?></span>
                </a>
                <div class="file__face">
                  <div class="file__label">
                    <p class="file__no"><span><?= e(t('hizmetler.dosya.no_etiket')) ?></span> <b><?= svc_no($s) ?></b></p>
                    <h3 class="file__t" id="ft-<?= e($s['no']) ?>"><a href="<?= service_url($slug) ?>"><?= e($s['title']) ?></a></h3>
                  </div>
                  <p class="file__short"><?= e($s['short']) ?></p>
                  <div class="file__inside">
                    <p class="file__ih"><?= e(t('hizmetler.dosya.icindekiler')) ?></p>
                    <ul class="file__progs" role="list">
                      <?php foreach ($s['programs'] as [$name]): ?><li><?= e($name) ?></li><?php endforeach; ?>
                    </ul>
                  </div>
                  <p class="file__fit"><span><?= e(t('hizmetler.dosya.kimin_icin')) ?></span> <?= e($s['fit']) ?></p>
                  <a class="btn btn--ink btn--sm file__open" href="<?= service_url($slug) ?>" tabindex="-1" aria-hidden="true"><?= e(t('hizmetler.dosya.ac')) ?> <?= arrow() ?></a>
                </div>
              </article>
            </li>
          <?php $i++; endforeach; ?>
        </ol>
      </div>
      <div class="drawer__front" aria-hidden="true">
        <span class="drawer__plate"><?= e(t('hizmetler.cekmece.plaka', ['no' => pg_no('hizmetler'), 'sayfa' => pg_name('hizmetler')])) ?></span>
        <span class="drawer__handle"></span>
        <span class="drawer__count" data-count>1 / <?= count($all) ?></span>
      </div>
    </div>
  </div>
</section>

<section class="dsor section" aria-labelledby="dsor-title">
  <div class="wrap dsor__in">
    <h2 class="display dsor__h" id="dsor-title"><?= e(t('hizmetler.dsor.baslik')) ?></h2>
    <div>
      <p class="lead"><?= e(t('hizmetler.dsor.giris')) ?></p>
      <p class="dsor__act"><a class="btn btn--ink" href="<?= url('iletisim') ?>"><?= e(t('hizmetler.dsor.dugme')) ?> <?= arrow() ?></a></p>
    </div>
  </div>
</section>

<?php if ($nx = pg_next('hizmetler')): ?>
<div class="wrap">
  <a class="next" href="<?= url($nx['path']) ?>">
    <span class="next__k"><?= e(t('hizmetler.sonraki.etiket', ['no' => $nx['nn']])) ?></span>
    <span class="next__t"><span><?= e($nx['label']) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
<?php endif; ?>
