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
    'folio'       => 'Evrak 03.' . $n . ' · <b>' . e($s['title']) . '</b>',
]);

$ekler = [
    ['ek-1', 'Kimin için'],
    ['ek-2', 'Programlar'],
    ['ek-3', 'Ne yapıyoruz'],
    ['ek-4', 'Evrak listesi'],
    ['ek-5', 'Sorular'],
];
?>

<article class="dosya" style="--c:var(--f-<?= e($s['color']) ?>)" aria-labelledby="dosya-title">

  <header class="kapak pagehead">
    <div class="wrap">
      <div class="kapak__board" data-kapak>
        <span class="kapak__tab" aria-hidden="true"><b>03.<?= $n ?></b> <?= e($s['tab']) ?></span>
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
          <p class="docmeta"><span>Dosya No <b>03.<?= $n ?></b></span><span>Konu: <b><?= e($s['nav']) ?></b></span><span>Ek: <b><?= count($ekler) ?> bölüm</b></span></p>
          <div class="kapak__label" data-label>
            <h1 class="display kapak__h" id="dosya-title"><?= e($s['title']) ?></h1>
          </div>
          <p class="kapak__lead"><?= e($s['lead']) ?></p>
          <p class="kapak__act">
            <a class="btn btn--ink" href="<?= url('iletisim') ?>">Bu dosya için görüşelim <?= arrow() ?></a>
            <a class="link ui" href="<?= url('hizmetler') ?>">Dosya dolabına dönün</a>
          </p>
        </div>
      </div>
    </div>
  </header>

  <div class="dosya__body wrap">
    <nav class="ekler" aria-label="Dosyanın bölümleri">
      <ol class="ekler__list" role="list">
        <?php foreach ($ekler as $k => [$id, $label]): ?>
          <li><a href="#<?= $id ?>" data-ek-link="<?= $id ?>"><span class="ekler__no">Ek-<?= $k + 1 ?></span><span class="ekler__t"><?= e($label) ?></span></a></li>
        <?php endforeach; ?>
      </ol>
    </nav>

    <div class="sayfa">

      <!-- Ek-1 -->
      <section class="ek" id="ek-1" data-ek aria-labelledby="ek-1-h">
        <header class="ek__head">
          <p class="ek__no">Ek-1</p>
          <h2 class="ek__h" id="ek-1-h">Kimin için?</h2>
        </header>
        <div class="uygun">
          <div class="uygun__col uygun__col--yes" data-on>
            <p class="uygun__k">
              <svg class="uygun__mark" viewBox="0 0 40 40" aria-hidden="true"><path pathLength="1" d="M5 22c4 3 7 7 10 11 6-11 13-20 21-29"/></svg>
              Uygun
            </p>
            <p class="uygun__t"><?= e($s['fit']) ?></p>
          </div>
          <div class="uygun__col uygun__col--no" data-on>
            <p class="uygun__k">
              <svg class="uygun__mark" viewBox="0 0 40 40" aria-hidden="true"><path pathLength="1" d="M8 8c8 7 16 16 24 25M33 7c-9 8-17 17-25 26"/></svg>
              Uygun değil
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
                <p class="mevzuat__src">Sade Türkçesi</p>
                <p class="mevzuat__plain"><?= e($s['law']['plain']) ?></p>
              </div>
            </div>
            <button class="btn btn--sm mevzuat__btn" type="button" aria-pressed="false" data-mevzuat-btn>Sade Türkçesi</button>
          </div>
        <?php endif; ?>
      </section>

      <!-- Ek-2 -->
      <section class="ek" id="ek-2" data-ek aria-labelledby="ek-2-h">
        <header class="ek__head">
          <p class="ek__no">Ek-2</p>
          <h2 class="ek__h" id="ek-2-h">Dosyadaki programlar</h2>
        </header>
        <table class="ftable">
          <caption class="sr-only"><?= e($s['title']) ?> kapsamındaki programlar</caption>
          <thead>
            <tr><th scope="col">Sıra</th><th scope="col">Program</th><th scope="col">Ne için</th></tr>
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
          <p class="ek__no">Ek-3</p>
          <h2 class="ek__h" id="ek-3-h">Ne yapıyoruz?</h2>
        </header>
        <ol class="maddeler" role="list">
          <?php foreach ($s['steps'] as $i => [$t, $d]): ?>
            <li class="madde" data-rise style="--delay:<?= $i * 0.05 ?>s">
              <h3 class="madde__h"><span class="madde__no">Madde <?= $i + 1 ?></span> <span class="madde__dash">—</span> <?= e($t) ?></h3>
              <p class="madde__t"><span class="madde__f">(1)</span> <?= e($d) ?></p>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>

      <!-- Ek-4 -->
      <section class="ek" id="ek-4" data-ek aria-labelledby="ek-4-h">
        <header class="ek__head">
          <p class="ek__no">Ek-4</p>
          <h2 class="ek__h" id="ek-4-h">Sizden isteyeceğimiz evrak</h2>
        </header>
        <div class="evrak" data-evrak data-key="<?= e($slug) ?>">
          <div class="evrak__top">
            <p class="evrak__title"><?= e($s['title']) ?> · Evrak listesi</p>
            <p class="evrak__count" aria-live="polite" data-evrak-count><span>0</span> / <?= count($s['docs']) ?> hazır</p>
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
          <p class="evrak__note">Liste programa ve açık çağrıya göre değişebilir; kesin listeyi ön görüşmede birlikte çıkarırız. İşaretleriniz yalnızca bu tarayıcıda saklanır.</p>
          <p class="evrak__print-only"><?= e(cfg('name')) ?> · <?= e(cfg('phone')) ?> · <?= e(cfg('email')) ?></p>
          <div class="evrak__act">
            <button class="btn btn--sm" type="button" data-evrak-print>Listeyi yazdırın</button>
            <button class="link ui evrak__reset" type="button" data-evrak-reset>İşaretleri temizleyin</button>
          </div>
          <div class="evrak__stamp kase kase--red" aria-hidden="true" data-evrak-stamp>
            <svg viewBox="0 0 220 84"><rect x="4" y="4" width="212" height="76" rx="10" fill="none" stroke="currentColor" stroke-width="4"/><rect x="11" y="11" width="198" height="62" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/><text x="110" y="56" text-anchor="middle" font-size="34" font-weight="800" style="font-stretch:66%;letter-spacing:.04em">EVRAK TAM</text></svg>
          </div>
        </div>
      </section>

      <!-- Ek-5 -->
      <section class="ek" id="ek-5" data-ek aria-labelledby="ek-5-h">
        <header class="ek__head">
          <p class="ek__no">Ek-5</p>
          <h2 class="ek__h" id="ek-5-h">Sık sorulanlar</h2>
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
    <span class="next__k">Sonraki dosya · 03.<?= (int) $next['no'] ?></span>
    <span class="next__t"><span><?= e($next['title']) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
