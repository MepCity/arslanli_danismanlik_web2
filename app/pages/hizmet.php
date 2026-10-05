<?php
/**
 * Hizmet detayı: açık dosya.
 * @var string $slug
 * @var array  $service
 */
$s     = $service;
$n     = (int) $s['no'];
$keys  = array_keys(services());
$pos   = array_search($slug, $keys, true);
$nSlug = $keys[($pos + 1) % count($keys)];
$next  = services()[$nSlug];

page([
    'id'          => 'service',
    'title'       => $s['title'],
    'description' => $s['short'],
    'folio'       => folio_html(svc_no($n), $s['title']),
]);

// Sayfanın beş bölümü: [bağlantı kimliği, sol listedeki ad]
$ekler = [];
foreach (range(1, 5) as $k) {
    $ekler[] = ['ek-' . $k, t('hizmet.ek.liste_' . $k)];
}
?>

<article class="dosya" style="--c:var(--f-<?= e($s['color']) ?>)" aria-labelledby="dosya-title">

  <header class="kapak pagehead">
    <div class="wrap">
      <div class="kapak__board" data-kapak>
        <span class="kapak__tab" aria-hidden="true"><b><?= svc_no($n) ?></b> <?= e($s['tab']) ?></span>
        <svg class="kapak__tel" viewBox="0 0 40 300" preserveAspectRatio="none" aria-hidden="true">
          <defs>
            <linearGradient id="tel-g" x1="0" x2="1">
              <stop offset="0" stop-color="#7d7b75"/><stop offset=".45" stop-color="#e2dfd8"/><stop offset="1" stop-color="#8a8882"/>
            </linearGradient>
          </defs>
          <rect x="6" y="0" width="12" height="300" rx="3" fill="url(#tel-g)"/>
          <path d="M12 70h22a4 4 0 0 1 0 8H12zM12 222h22a4 4 0 0 1 0 8H12z" fill="url(#tel-g)"/>
        </svg>
        <div class="kapak__in">
          <p class="docmeta"><span><?= th('hizmet.kapak.no', ['no' => svc_no($n)]) ?></span><span><?= th('hizmet.kapak.konu', ['konu' => $s['nav']]) ?></span><span><?= th('hizmet.kapak.ek', ['n' => count($ekler)]) ?></span></p>
          <div class="kapak__label" data-label>
            <h1 class="display kapak__h" id="dosya-title"><?= e($s['title']) ?></h1>
          </div>
          <p class="kapak__lead"><?= e($s['lead']) ?></p>
          <p class="kapak__act">
            <a class="btn btn--ink" href="<?= url('iletisim') ?>"><?= e(t('hizmet.kapak.dugme')) ?> <?= arrow() ?></a>
            <a class="link ui" href="<?= url('hizmetler') ?>"><?= e(t('hizmet.kapak.geri')) ?></a>
          </p>
        </div>
      </div>
    </div>
  </header>

  <div class="dosya__body wrap">
    <nav class="ekler" aria-label="<?= e(t('hizmet.kapak.bolumler')) ?>">
      <ol class="ekler__list" role="list">
        <?php foreach ($ekler as $k => [$id, $label]): ?>
          <li><a href="#<?= $id ?>" data-ek-link="<?= $id ?>"><span class="ekler__no"><?= e(t('hizmet.ek.no', ['n' => $k + 1])) ?></span><span class="ekler__t"><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>

    <div class="sayfa">

      <!-- Ek-1 -->
      <section class="ek" id="ek-1" data-ek aria-labelledby="ek-1-h">
        <header class="ek__head">
          <p class="ek__no"><?= e(t('hizmet.ek.no', ['n' => 1])) ?></p>
          <h2 class="ek__h" id="ek-1-h"><?= e(t('hizmet.kimin.baslik')) ?></h2>
        </header>
        <div class="uygun">
          <div class="uygun__col uygun__col--yes" data-on>
            <p class="uygun__k">
              <svg class="uygun__mark" viewBox="0 0 40 40" aria-hidden="true"><path pathLength="1" d="M5 22c4 3 7 7 10 11 6-11 13-20 21-29"/></svg>
              <?= e(t('hizmet.kimin.uygun')) ?>
            </p>
            <p class="uygun__t"><?= e($s['fit']) ?></p>
          </div>
          <div class="uygun__col uygun__col--no" data-on>
            <p class="uygun__k">
              <svg class="uygun__mark" viewBox="0 0 40 40" aria-hidden="true"><path pathLength="1" d="M8 8c8 7 16 16 24 25M33 7c-9 8-17 17-25 26"/></svg>
              <?= e(t('hizmet.kimin.uygun_degil')) ?>
            </p>
            <p class="uygun__t"><?= e($s['unfit']) ?></p>
          </div>
        </div>

        <?php if (!empty($s['law'])): ?>
          <div class="mevzuat" data-mevzuat>
            <div class="mevzuat__card">
              <div class="mevzuat__face mevzuat__face--law">
                <p class="mevzuat__src"><?= e($s['law']['source']) ?></p>
                <blockquote class="mevzuat__law"><?= e($s['law']['text']) ?></blockquote>
              </div>
              <div class="mevzuat__face mevzuat__face--plain">
                <p class="mevzuat__src"><?= e(t('hizmet.mevzuat.sade_etiket')) ?></p>
                <p class="mevzuat__plain"><?= e($s['law']['plain']) ?></p>
              </div>
            </div>
            <button class="btn btn--sm mevzuat__btn" type="button" aria-pressed="false" data-mevzuat-btn><?= e(t('hizmet.mevzuat.dugme_sade')) ?></button>
          </div>
        <?php endif; ?>
      </section>

      <!-- Ek-2 -->
      <section class="ek" id="ek-2" data-ek aria-labelledby="ek-2-h">
        <header class="ek__head">
          <p class="ek__no"><?= e(t('hizmet.ek.no', ['n' => 2])) ?></p>
          <h2 class="ek__h" id="ek-2-h"><?= e(t('hizmet.programlar.baslik')) ?></h2>
        </header>
        <table class="ftable">
          <caption class="sr-only"><?= e(t('hizmet.programlar.tablo', ['baslik' => $s['title']])) ?></caption>
          <thead>
            <tr><th scope="col"><?= e(t('hizmet.programlar.sutun_sira')) ?></th><th scope="col"><?= e(t('hizmet.programlar.sutun_program')) ?></th><th scope="col"><?= e(t('hizmet.programlar.sutun_ne_icin')) ?></th></tr>
          </thead>
          <tbody>
            <?php foreach ($s['programs'] as $i => [$name, $desc]): ?>
              <tr data-rise style="--delay:<?= $i * 0.06 ?>s">
                <td class="ftable__no">2.<?= $i + 1 ?></td>
                <th scope="row" class="ftable__name"><?= e($name) ?></th>
                <td class="ftable__desc"><?= e($desc) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </section>

      <!-- Ek-3 -->
      <section class="ek" id="ek-3" data-ek aria-labelledby="ek-3-h">
        <header class="ek__head">
          <p class="ek__no"><?= e(t('hizmet.ek.no', ['n' => 3])) ?></p>
          <h2 class="ek__h" id="ek-3-h"><?= e(t('hizmet.adimlar.baslik')) ?></h2>
        </header>
        <ol class="maddeler" role="list">
          <?php foreach ($s['steps'] as $i => [$t, $d]): ?>
            <li class="madde" data-rise style="--delay:<?= $i * 0.05 ?>s">
              <h3 class="madde__h"><span class="madde__no"><?= e(t('hizmet.adimlar.madde', ['n' => $i + 1])) ?></span> <span class="madde__dash">—</span> <?= e($t) ?></h3>
              <p class="madde__t"><span class="madde__f">(1)</span> <?= e($d) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <!-- Ek-4 -->
      <section class="ek" id="ek-4" data-ek aria-labelledby="ek-4-h">
        <header class="ek__head">
          <p class="ek__no"><?= e(t('hizmet.ek.no', ['n' => 4])) ?></p>
          <h2 class="ek__h" id="ek-4-h"><?= e(t('hizmet.evrak.baslik')) ?></h2>
        </header>
        <div class="evrak" data-evrak data-key="<?= e($slug) ?>">
          <div class="evrak__top">
            <p class="evrak__title"><?= e(t('hizmet.evrak.liste_baslik', ['baslik' => $s['title']])) ?></p>
            <p class="evrak__count" aria-live="polite" data-evrak-count><?= th('hizmet.evrak.sayac', ['n' => ['html' => '<span>0</span>'], 'toplam' => count($s['docs'])]) ?></p>
          </div>
          <ul class="evrak__list" role="list">
            <?php foreach ($s['docs'] as $i => $doc): ?>
              <li>
                <label class="check evrak__item">
                  <input type="checkbox" data-doc="<?= $i ?>">
                  <span><?= e($doc) ?></span>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>
          <p class="evrak__note"><?= e(t('hizmet.evrak.not')) ?></p>
          <p class="evrak__print-only"><?= e(t('hizmet.evrak.yazdir_satir')) ?></p>
          <div class="evrak__act">
            <button class="btn btn--sm" type="button" data-evrak-print><?= e(t('hizmet.evrak.yazdir')) ?></button>
            <button class="link ui evrak__reset" type="button" data-evrak-reset><?= e(t('hizmet.evrak.temizle')) ?></button>
          </div>
          <div class="evrak__stamp kase kase--red" aria-hidden="true" data-evrak-stamp>
            <svg viewBox="0 0 220 84"><rect x="4" y="4" width="212" height="76" rx="10" fill="none" stroke="currentColor" stroke-width="4"/><rect x="11" y="11" width="198" height="62" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/><text x="110" y="56" text-anchor="middle" font-size="34" font-weight="800" style="font-stretch:66%;letter-spacing:.04em"><?= e(t('hizmet.evrak.kase')) ?></text></svg>
          </div>
        </div>
      </section>

      <!-- Ek-5 -->
      <section class="ek" id="ek-5" data-ek aria-labelledby="ek-5-h">
        <header class="ek__head">
          <p class="ek__no"><?= e(t('hizmet.ek.no', ['n' => 5])) ?></p>
          <h2 class="ek__h" id="ek-5-h"><?= e(t('hizmet.sss.baslik')) ?></h2>
        </header>
        <div class="notlar">
          <?php foreach ($s['faq'] as $i => [$q, $a]): ?>
            <details class="not" style="--r:<?= [-0.6, 0.5, -0.3, 0.7][$i % 4] ?>deg">
              <summary class="not__q"><span><?= e($q) ?></span><i class="not__i" aria-hidden="true"></i></summary>
              <div class="not__a"><p><?= e($a) ?></p></div>
            </details>
          <?php endforeach; ?>
        </div>
      </section>

    </div>
  </div>
</article>

<div class="wrap">
  <a class="next next--file" href="<?= service_url($nSlug) ?>" style="--c:var(--f-<?= e($next['color']) ?>)">
    <span class="next__k"><?= e(t('hizmet.sonraki.etiket', ['no' => svc_no($next)])) ?></span>
    <span class="next__t"><span><?= e($next['title']) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
