<?php
// not_found() bu sayfayı açmadan önce id, başlık ve 404 durum kodunu ayarlar.
page([
    'description' => t('notfound.seo.description'),
    'folio'       => folio_html('—', t('notfound.folio')),
]);

$requested = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
$base = base_path();
if ($base !== '' && str_starts_with($requested, $base)) {
    $requested = substr($requested, strlen($base)) ?: '/';
}
$requested = mb_strimwidth($requested, 0, 80, '…');

$ekler = [
    ['', pg_name('home'), t('notfound.ekler.ana')],
    ['hizmetler', pg_name('hizmetler'), t('notfound.ekler.hizmetler')],
    ['iletisim', pg_name('iletisim'), t('notfound.ekler.iletisim')],
];
?>

<section class="nf pagehead" aria-labelledby="nf-title">
  <div class="wrap">
    <article class="nf__letter" data-letter>
      <header class="nf__top">
        <div class="nf__from">
          <p class="nf__firm"><?= e(tr_upper(cfg('name'))) ?></p>
          <p class="nf__city"><?= e(cfg('address_short')) ?></p>
        </div>
        <dl class="nf__meta">
          <div><dt><?= e(t('notfound.mektup.tarih')) ?></dt><dd><?= e(today_official()) ?></dd></div>
          <div><dt><?= e(t('notfound.mektup.sayi')) ?></dt><dd><?= e(t('notfound.mektup.sayi_deger')) ?></dd></div>
        </dl>
      </header>

      <p class="nf__konu"><span><?= e(t('notfound.mektup.konu_etiket')) ?></span> <?= e(t('notfound.mektup.konu')) ?></p>
      <h1 class="display nf__h" id="nf-title"><?= e(t('notfound.hero.baslik')) ?></h1>

      <div class="nf__body">
        <p><?= e(t('notfound.govde.hitap')) ?></p>
        <p><?= th('notfound.govde.bir') ?></p>
        <p class="nf__field"><span class="nf__flabel"><?= e(t('notfound.govde.istenen')) ?></span> <span class="nf__path"><?= e($requested) ?></span></p>
        <p><?= th('notfound.govde.iki') ?></p>
        <p><?= e(t('notfound.govde.kapanis')) ?></p>
      </div>

      <nav class="nf__ekler" aria-label="<?= e(t('notfound.ekler.etiket')) ?>">
        <p class="nf__ek-h"><?= e(t('notfound.ekler.baslik')) ?></p>
        <ol class="nf__list" role="list">
          <?php foreach ($ekler as $i => [$to, $label, $note]): ?>
            <li><a href="<?= url($to) ?>"><span class="nf__no"><?= $i + 1 ?> –</span><span class="nf__t"><?= e($label) ?></span><span class="nf__note"><?= e($note) ?></span><?= arrow() ?></a></li>
          <?php endforeach; ?>
        </ol>
      </nav>

      <div class="kase kase--red nf__stamp" data-stamp role="img" aria-label="<?= e(t('notfound.kase.etiket')) ?>">
        <svg viewBox="0 0 420 170" aria-hidden="true">
          <rect x="5" y="5" width="410" height="160" rx="10" fill="none" stroke="currentColor" stroke-width="7"/>
          <rect x="18" y="18" width="384" height="134" rx="5" fill="none" stroke="currentColor" stroke-width="2"/>
          <text x="210" y="102" text-anchor="middle" font-size="76" font-weight="900" style="font-stretch:62%;letter-spacing:.02em"><?= e(t('notfound.kase.yazi')) ?></text>
          <text x="210" y="136" text-anchor="middle" font-size="17" font-weight="700" style="font-stretch:80%;letter-spacing:.18em"><?= e(t('notfound.kase.alt')) ?></text>
        </svg>
      </div>
    </article>
  </div>
</section>
