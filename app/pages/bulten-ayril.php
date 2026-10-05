<?php
/**
 * Bültenden ayrılma: sicil kartı ve terkin şerhi.
 * /bulten/ayril?e=...&k=... (her bülten e-postasının altındaki bağlantı; bkz. bulten_ayril_sayfasi, app/bulten.php)
 * Bir adresin bültene kayıtlı olması, sicilde bir satır olması gibidir: onaylayınca satırın üstü kırmızı kalemle çizilir,
 * kenarına "terkin edildi" yazılır. Arama motorlarına kapalıdır; site haritasında ve fihristte yer almaz.
 *
 * @var string $durum   'onay' (bağlantı geçerli, onay bekleniyor) | 'tamam' (az önce ayrıldı) | 'zaten' (daha önce ayrılmış) | 'gecersiz'
 * @var string $adres   abonelikten çıkacak e-posta adresi ('gecersiz' durumunda boş)
 * @var string $aksiyon onay formunun gönderileceği adres
 * @var int    $tarih   (isteğe bağlı) adresin abonelikten ayrıldığı an; yoksa bugünün tarihi yazılır
 *
 * Bu sayfanın metinleri Aşama 2B için ayrıca kayıt defterine taşınabilir; şimdilik burada, aşağıdaki tek dizide düz Türkçe olarak durur.
 */
$metin = [
    'onay' => [
        'etiket' => 'Evrak · Terkin şerhi',
        'baslik' => 'Bültenden ayrılmak üzeresiniz.',
        'giris'  => 'Onayladığınızda aşağıdaki adrese artık bülten ve bilgilendirme e-postası göndermeyiz.',
        'durum'  => 'Kayıtlı',
        'dugme'  => 'Abonelikten ayrıl',
        'vazgec' => 'Vazgeçtim, ana sayfaya dön',
    ],
    'tamam' => [
        'etiket' => 'Evrak · Terkin şerhi',
        'baslik' => 'Abonelikten ayrıldınız.',
        'giris'  => 'Bu adrese artık bülten e-postası göndermeyeceğiz. Fikriniz değişirse bize e-posta ya da telefonla haber verin; aboneliğinizi yeniden açarız.',
        'durum'  => 'Terkin edildi',
        'dugme'  => 'Ana sayfaya dön',
    ],
    'zaten' => [
        'etiket' => 'Evrak · Terkin şerhi',
        'baslik' => 'Bu adres zaten ayrılmış.',
        'giris'  => 'Kaydınız daha önce terkin edilmiş; yapılacak bir işlem yok ve bu adrese bülten e-postası gönderilmiyor. Yeniden almak isterseniz bize e-posta ya da telefonla haber verin.',
        'durum'  => 'Terkin edilmiş',
        'dugme'  => 'Ana sayfaya dön',
    ],
    'gecersiz' => [
        'etiket' => 'Evrak · Terkin şerhi',
        'baslik' => 'Bu bağlantı geçerli değil.',
        'giris'  => 'Bağlantı eksik kopyalanmış ya da bozulmuş olabilir. Aldığınız iletideki bağlantıya yeniden tıklayın ya da abonelikten ayrılmak istediğinizi bize şu adresten yazın:',
        'durum'  => 'Bulunamadı',
        'dugme'  => 'Ana sayfaya dön',
    ],
];
$durum = isset($metin[$durum ?? '']) ? $durum : 'gecersiz';
$m     = $metin[$durum];
$kapali = in_array($durum, ['tamam', 'zaten'], true);
$gun    = $kapali && !empty($tarih) ? date('d/m/Y', (int) $tarih) : today_official();

page([
    'id'          => 'unsub',
    'title'       => 'Bültenden ayrıl',
    'description' => 'Bülten aboneliğinden ayrılma.',
    'folio'       => 'Evrak — · <b>Terkin şerhi</b>',
    'noindex'     => true,
]);
?>

<section class="us pagehead wrap" aria-labelledby="us-title">
  <p class="label" data-rise><?= e($m['etiket']) ?></p>
  <h1 class="display us__h" id="us-title" data-rise style="--delay:.05s"><?= e($m['baslik']) ?></h1>
  <p class="lead us__lead" data-rise style="--delay:.12s"><?= e($m['giris']) ?></p>

  <article class="sicil us__card is-<?= e($durum) ?>" data-rise style="--delay:.2s" aria-label="Sicil kartı">
    <header class="sicil__top">
      <p class="sicil__title">Bülten kayıt defteri</p>
      <p class="sicil__form">Form: <b>ARS-B/02</b></p>
    </header>

    <dl class="sicil__rows">
      <div class="sicil__row sicil__row--mail">
        <dt>E-posta</dt>
        <dd>
          <?php if ($durum === 'gecersiz'): ?>
            <span class="sicil__blank" aria-hidden="true"></span>
            <span class="sr-only">Kayıt bulunamadı</span>
          <?php else: ?>
            <span class="sicil__mail"><?= e($adres) ?></span>
            <?php if ($kapali): ?>
              <svg class="sicil__strike" viewBox="0 0 400 24" preserveAspectRatio="none" aria-hidden="true" focusable="false"><path pathLength="1" d="M2 14C60 8 130 17 200 11s140 4 198-3"/></svg>
            <?php endif; ?>
          <?php endif; ?>
        </dd>
      </div>
      <div class="sicil__row">
        <dt>Durum</dt>
        <dd class="sicil__state"><?= e($m['durum']) ?></dd>
      </div>
      <div class="sicil__row">
        <dt>Tarih</dt>
        <dd class="sicil__date"><?= e($gun) ?></dd>
      </div>
    </dl>

    <?php if ($kapali): ?>
      <p class="sicil__note" aria-hidden="true">terkin edildi · <?= e($gun) ?></p>
    <?php endif; ?>

    <?php if ($durum === 'gecersiz'): ?>
      <p class="sicil__contact"><a class="link" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></p>
    <?php endif; ?>

    <?php if ($durum === 'onay'): ?>
      <form class="sicil__act" method="post" action="<?= e($aksiyon) ?>">
        <button class="btn btn--ink" type="submit"><?= e($m['dugme']) ?> <?= arrow() ?></button>
        <a class="link sicil__cancel" href="<?= url() ?>"><?= e($m['vazgec']) ?></a>
      </form>
    <?php else: ?>
      <div class="sicil__act">
        <a class="btn" href="<?= url() ?>"><?= e($m['dugme']) ?> <?= arrow() ?></a>
      </div>
    <?php endif; ?>
  </article>
</section>
