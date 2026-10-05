<?php
/**
 * Hesap Numaralarımız: dekont.
 * Her banka için bir fiş yazıcısı; sayfa açılınca fişler satır satır basılır.
 * Hareket azaltıldıysa ya da JavaScript yoksa fişler hazır basılmış durur.
 */
page([
    'id'          => 'bank',
    'title'       => pg_name('hesap'),
    'description' => t('hesap.seo.description'),
    'folio'       => pg_folio('hesap'),
]);

$banks = site('banks');
$now   = time();
?>

<section class="bank-head pagehead wrap" aria-labelledby="bank-title">
  <p class="label" data-rise><?= e(pg_label('hesap')) ?></p>
  <h1 class="display bank-head__h" id="bank-title" data-rise style="--delay:.05s"><?= e(t('hesap.hero.baslik')) ?></h1>
  <p class="lead bank-head__lead" data-rise style="--delay:.12s"><?= th(count($banks) === 1 ? 'hesap.hero.giris_tek' : 'hesap.hero.giris') ?></p>
</section>

<section class="pos wrap" aria-label="<?= e(t('hesap.fis.liste_etiket')) ?>">
  <?php foreach ($banks as $i => $b): ?>
    <?php $iban = str_replace(' ', '', $b['iban']); ?>
    <article class="pos__unit" data-pos aria-labelledby="bank-<?= $i ?>">
      <div class="pos__printer" aria-hidden="true">
        <span class="pos__led"></span>
        <span class="pos__brand"><?= e(t('hesap.fis.yazici', ['sira' => $i + 1, 'toplam' => count($banks)])) ?></span>
        <span class="pos__slot"></span>
      </div>
      <div class="pos__mouth">
        <div class="fis" data-fis>
          <p class="fis__c fis__bank" id="bank-<?= $i ?>"><?= e(tr_upper($b['bank'])) ?></p>
          <p class="fis__c fis__small"><?= e(t('hesap.fis.baslik')) ?></p>
          <hr class="fis__sep">
          <dl class="fis__dl">
            <div><dt><?= e(t('hesap.fis.sahip')) ?></dt><dd><?= e(tr_upper($b['holder'])) ?></dd></div>
            <div><dt><?= e(t('hesap.fis.no')) ?></dt><dd><?= e($b['account']) ?></dd></div>
          </dl>
          <hr class="fis__sep">
          <p class="fis__k"><?= e(t('hesap.fis.iban')) ?></p>
          <p class="fis__iban"><?= e($b['iban']) ?></p>
          <hr class="fis__sep">
          <p class="fis__row"><span><?= e(t('hesap.fis.aciklama')) ?></span><span><?= e(t('hesap.fis.aciklama_deger')) ?></span></p>
          <p class="fis__row"><span><?= e(t('hesap.fis.tarih')) ?></span><span><?= date('d/m/Y', $now) ?></span></p>
          <p class="fis__row"><span><?= e(t('hesap.fis.saat')) ?></span><span><?= date('H:i', $now) ?></span></p>
          <p class="fis__c fis__end"><?= e(t('hesap.fis.son')) ?></p>
        </div>
      </div>
      <div class="pos__act">
        <button class="btn btn--sm" type="button" data-copy="<?= e($iban) ?>" data-copy-msg="<?= e(t('hesap.kopya.iban_tamam', ['iban' => $b['iban']])) ?>"><?= e(t('hesap.kopya.iban')) ?></button>
        <button class="btn btn--sm pos__ghost" type="button" data-copy="<?= e($b['account']) ?>" data-copy-msg="<?= e(t('hesap.kopya.no_tamam')) ?>"><?= e(t('hesap.kopya.no')) ?></button>
      </div>
    </article>
  <?php endforeach; ?>

  <aside class="pos__note" aria-label="<?= e(t('hesap.not.etiket')) ?>">
    <p class="pos__note-h"><?= e(t('hesap.not.baslik')) ?></p>
    <ol role="list">
      <li><span>1</span><p><?= th('hesap.not.bir', ['hesap_sahibi' => tr_upper($banks[0]['holder'])]) ?></p></li>
      <li><span>2</span><p><?= th('hesap.not.iki') ?></p></li>
      <li><span>3</span><p><?= th('hesap.not.uc') ?></p></li>
    </ol>
  </aside>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k"><?= e(t('hesap.sonraki.etiket', ['no' => pg_no('iletisim')])) ?></span>
    <span class="next__t"><span><?= e(pg_name('iletisim')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
