<?php
/**
 * Hesap Numaralarımız: dekont.
 * Her banka için bir fiş yazıcısı; sayfa açılınca fişler satır satır basılır.
 * Hareket azaltıldıysa ya da JavaScript yoksa fişler hazır basılmış durur.
 */
page([
    'id'          => 'bank',
    'title'       => 'Hesap Numaralarımız',
    'description' => 'Arslanlı Yatırım & Danışmanlık banka hesap bilgileri: Ziraat Katılım Bankası ve Halk Bankası IBAN numaraları.',
    'folio'       => pg_folio('hesap'),
]);

$banks = site('banks');
$now   = time();
?>

<section class="bank-head pagehead wrap" aria-labelledby="bank-title">
  <p class="label" data-rise><?= e(pg_label('hesap')) ?></p>
  <h1 class="display bank-head__h" id="bank-title" data-rise style="--delay:.05s">Hesap numaralarımız.</h1>
  <p class="lead bank-head__lead" data-rise style="--delay:.12s">Ödemenizi aşağıdaki <?= count($banks) === 2 ? 'iki hesaptan birine' : 'hesaplardan birine' ?> yapabilirsiniz. Açıklama satırına firmanızın adını yazmanız, ödemeyi doğru dosyayla eşleştirmemizi kolaylaştırır.</p>
</section>

<section class="pos wrap" aria-label="Banka hesapları">
  <?php foreach ($banks as $i => $b): ?>
    <?php $iban = str_replace(' ', '', $b['iban']); ?>
    <article class="pos__unit" data-pos aria-labelledby="bank-<?= $i ?>">
      <div class="pos__printer" aria-hidden="true">
        <span class="pos__led"></span>
        <span class="pos__brand">Fiş <?= $i + 1 ?> / <?= count($banks) ?></span>
        <span class="pos__slot"></span>
      </div>
      <div class="pos__mouth">
        <div class="fis" data-fis>
          <p class="fis__c fis__bank" id="bank-<?= $i ?>"><?= e(tr_upper($b['bank'])) ?></p>
          <p class="fis__c fis__small">HESAP BİLGİ FİŞİ</p>
          <hr class="fis__sep">
          <dl class="fis__dl">
            <div><dt>Hesap sahibi</dt><dd><?= e(tr_upper($b['holder'])) ?></dd></div>
            <div><dt>Hesap no</dt><dd><?= e($b['account']) ?></dd></div>
          </dl>
          <hr class="fis__sep">
          <p class="fis__k">IBAN</p>
          <p class="fis__iban"><?= e($b['iban']) ?></p>
          <hr class="fis__sep">
          <p class="fis__row"><span>Açıklama</span><span>firmanızın adı</span></p>
          <p class="fis__row"><span>Tarih</span><span><?= date('d/m/Y', $now) ?></span></p>
          <p class="fis__row"><span>Saat</span><span><?= date('H:i', $now) ?></span></p>
          <p class="fis__c fis__end">*** TEŞEKKÜR EDERİZ ***</p>
        </div>
      </div>
      <div class="pos__act">
        <button class="btn btn--sm" type="button" data-copy="<?= e($iban) ?>" data-copy-msg="IBAN kopyalandı: <?= e($b['iban']) ?>">IBAN’ı kopyala</button>
        <button class="btn btn--sm pos__ghost" type="button" data-copy="<?= e($b['account']) ?>" data-copy-msg="Hesap numarası kopyalandı">Hesap no’yu kopyala</button>
      </div>
    </article>
  <?php endforeach; ?>

  <aside class="pos__note" aria-label="Ödeme notları">
    <p class="pos__note-h">Ödeme yaparken</p>
    <ol role="list">
      <li><span>1</span><p>Alıcı adını <b><?= e(tr_upper($banks[0]['holder'])) ?></b> olarak yazın.</p></li>
      <li><span>2</span><p>Açıklama satırına firmanızın adını ekleyin.</p></li>
      <li><span>3</span><p>Dekontu <a class="link" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a> adresine iletebilirsiniz.</p></li>
    </ol>
  </aside>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k">Bir sorunuz mu var? · <?= pg_no('iletisim') ?></span>
    <span class="next__t"><span>İletişim</span></span>
    <?= arrow() ?>
  </a>
</div>
