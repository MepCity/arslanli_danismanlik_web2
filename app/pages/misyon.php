<?php
$m = site('mission');
page([
    'id'          => 'mission',
    'title'       => pg_name('misyon'),
    'description' => t('misyon.seo.description', ['misyon' => $m['statement']]),
    'folio'       => pg_folio('misyon'),
]);

/** Kalemle çizilmiş kutu: her satırda biraz farklı olsun diye dört çeşit. */
$boxes = [
    'M3 4.5c6.5-1 13.6-1.2 20.4-.4.6 6.4.5 13.2-.2 19.6-6.9.6-13.4.5-19.8-.3-.7-6.3-.8-12.6-.4-18.9',
    'M4 3.6c6.8-.3 13.3-.6 19.7.5.3 6.6.8 12.9-.3 19.4-6.4.9-13.2.4-19.5.2-.6-6.6-.3-13.3.1-19.1',
    'M3.4 4.2c6.4-.8 13.5-.4 20.2-.1.4 6.5.2 13-.6 19.7-6.6.5-13 .7-19.5-.1-.3-6.4-.6-12.9-.1-19.5',
    'M3.8 3.9c6.9-.6 13.1-.9 19.9.2.7 6.2.4 12.9-.1 19.5-6.8.4-13.3.6-19.6-.4-.5-6.3-.4-12.7-.2-19.3',
];
$ticks = [
    'M6 14.5c2.4 2.2 4.4 4.9 6.2 8.2C16 14.6 21.9 6.9 30.5-1.5',
    'M5.5 15.2c2.6 2 4.6 4.6 6.6 7.8 3.4-7.6 9.1-15.6 17.9-23.6',
    'M6.4 13.8c2.3 2.6 4.1 5.5 5.6 8.9C15.8 14 21.6 6 31-2',
    'M5.8 14.8c2.7 2.1 4.7 4.8 6.4 7.9 3.6-7.9 9.6-15.3 18.2-23.2',
];
?>

<section class="ms pagehead" aria-labelledby="ms-title">
  <div class="wrap ms__in">
    <header class="ms__head">
      <p class="docmeta"><span><?= th('misyon.hero.sayi') ?></span><span><?= th('misyon.hero.konu') ?></span></p>
      <h1 class="display ms__h" id="ms-title"><?= e(t('misyon.hero.baslik')) ?></h1>
      <p class="lead ms__lead"><?= e(t('misyon.hero.giris')) ?></p>
    </header>

    <div class="defter" data-defter>
      <div class="defter__spiral" aria-hidden="true"><?php for ($i = 0; $i < 22; $i++): ?><i></i><?php endfor; ?></div>
      <div class="defter__page">
        <p class="defter__meta"><span><?= th('misyon.defter.tarih') ?></span><span><?= th('misyon.defter.konu') ?></span></p>

        <?php $vurgu = e(t('misyon.defter.vurgu')); ?>
        <p class="defter__statement"><?= $vurgu !== '' ? str_replace($vurgu, annot($vurgu, 'under', 'red'), e($m['statement'])) : e($m['statement']) ?></p>

        <h2 class="defter__h"><?= e(t('misyon.defter.baslik')) ?></h2>
        <ol class="todo" role="list">
          <?php foreach ($m['items'] as $i => $item): ?>
            <li class="todo__i" data-on style="--delay:<?= 0.15 + ($i % 2) * 0.05 ?>s">
              <span class="todo__no" aria-hidden="true"><?= $i + 1 ?>.</span>
              <span class="todo__box" aria-hidden="true">
                <svg viewBox="0 0 27 27"><path class="todo__frame" d="<?= $boxes[$i % 4] ?>"/></svg>
                <svg class="todo__tick" viewBox="0 -4 34 30"><path pathLength="1" d="<?= $ticks[$i % 4] ?>"/></svg>
              </span>
              <span class="todo__t"><?= e($item) ?></span>
              <span class="sr-only"><?= e(t('misyon.defter.okuyucu')) ?></span>
            </li>
          <?php endforeach; ?>
        </ol>

        <p class="defter__sign"><em><?= e(cfg('name')) ?></em><span><?= e(t('misyon.defter.imza')) ?></span></p>
      </div>
    </div>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('kurumsal/vizyonumuz') ?>">
    <span class="next__k"><?= e(t('misyon.sonraki.etiket', ['no' => pg_no('vizyon')])) ?></span>
    <span class="next__t"><span><?= e(pg_name('vizyon')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
