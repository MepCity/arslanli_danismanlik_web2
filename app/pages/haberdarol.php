<?php
/**
 * Haberdar Ol: dergi kuponu.
 * Kesik çizgili kupon; makas kenarında bekler. Gönderilince kuponu çevresinden keser,
 * kupon düşer ve sayfada kalan boşlukta alındı fişi görünür (app/form.php, 'bulten').
 */
page([
    'id'          => 'signup',
    'title'       => pg_name('haberdarol'),
    'description' => t('haberdarol.seo.description'),
    'folio'       => pg_folio('haberdarol'),
]);

$durum = (string) ($_GET['durum'] ?? '');
$sent  = $durum === 'tamam';
?>

<section class="kp-head pagehead wrap" aria-labelledby="signup-title">
  <p class="label" data-rise><?= e(pg_label('haberdarol')) ?></p>
  <h1 class="display kp-head__h" id="signup-title" data-rise style="--delay:.05s"><?= e(t('haberdarol.hero.baslik')) ?></h1>
  <p class="lead kp-head__lead" data-rise style="--delay:.12s"><?= th('haberdarol.hero.giris') ?></p>
</section>

<section class="kp wrap" aria-label="<?= e(t('haberdarol.kupon.alan_etiket')) ?>">
  <div class="kp__page">
    <p class="kp__run" aria-hidden="true"><span><?= e(t('haberdarol.kupon.sol')) ?></span><span><?= e(t('haberdarol.kupon.sag', ['no' => pg_no('haberdarol')])) ?></span></p>

    <div class="kp__stage<?= $sent ? ' is-sent' : '' ?>" data-kp-stage>
      <div class="kp__hole" data-kp-hole<?= $sent ? '' : ' hidden' ?>>
        <div class="kp__slip" tabindex="-1" data-kp-slip>
          <p class="label"><?= e(t('haberdarol.alindi.etiket')) ?></p>
          <p class="kp__slip-h serif-display"><?= e(t('haberdarol.alindi.baslik')) ?></p>
          <p class="kp__slip-msg" data-kp-msg><?= e(t('haberdarol.alindi.mesaj')) ?></p>
          <?php $yaz = ['html' => '<a class="link" href="' . url('iletisim') . '">' . e(t('haberdarol.alindi.yaz')) . '</a>']; ?>
          <?php if (feature('blog') || feature('duyurular')): ?>
          <p class="kp__slip-more"><?= th('haberdarol.alindi.devam', ['gozat' => ['html' => '<a class="link" href="' . url(feature('blog') ? 'blog' : 'duyurular') . '">' . e(t(feature('blog') ? 'haberdarol.alindi.makaleler' : 'haberdarol.alindi.cagrilar')) . '</a>'], 'yaz' => $yaz]) ?></p>
          <?php else: ?>
          <p class="kp__slip-more"><?= th('haberdarol.alindi.devam_kisa', ['yaz' => $yaz]) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <div class="kupon" data-kupon<?= $sent ? ' hidden' : '' ?>>
        <svg class="kupon__cut" aria-hidden="true" focusable="false" data-kp-cut><path class="kupon__dash"/><path class="kupon__line"/></svg>
        <span class="kupon__scissors" aria-hidden="true" data-kp-scissors>
          <svg viewBox="0 0 64 32">
            <g class="bl bl--a"><circle cx="9" cy="8" r="6.5"/><path d="M14.5 10.5 30 16 63 12.5"/></g>
            <g class="bl bl--b"><circle cx="9" cy="24" r="6.5"/><path d="M14.5 21.5 30 16 63 19.5"/></g>
            <circle class="pv" cx="30" cy="16" r="1.8"/>
          </svg>
        </span>

        <form class="kupon__form" data-form data-kp-form action="<?= url('form') ?>" method="post" novalidate aria-labelledby="kp-title">
          <input type="hidden" name="_form" value="bulten">
          <input type="hidden" name="_token" value="<?= e(form_token()) ?>">
          <input type="hidden" name="_back" value="<?= e(url('haberdarol')) ?>">
          <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

          <?php if ($durum === 'hata'): ?>
            <p class="kupon__notice" role="alert"><?= e(t('haberdarol.kupon.hata')) ?></p>
          <?php endif; ?>

          <header class="kupon__head">
            <p class="kupon__kicker" id="kp-title"><?= th('haberdarol.baslik.kupon') ?></p>
            <ol class="kupon__steps" aria-label="<?= e(t('haberdarol.baslik.adimlar_etiket')) ?>">
              <li><b>1</b> <?= e(t('haberdarol.baslik.adim1')) ?></li>
              <li><b>2</b> <?= e(t('haberdarol.baslik.adim2')) ?></li>
              <li><b>3</b> <?= e(t('haberdarol.baslik.adim3')) ?></li>
            </ol>
          </header>

          <div class="kupon__grid">
            <div class="field">
              <label for="kp-ad"><?= e(t('haberdarol.form.ad')) ?></label>
              <input id="kp-ad" name="ad" type="text" required maxlength="80" autocomplete="given-name">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-soyad"><?= e(t('haberdarol.form.soyad')) ?></label>
              <input id="kp-soyad" name="soyad" type="text" required maxlength="80" autocomplete="family-name">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-mail"><?= e(t('haberdarol.form.eposta')) ?></label>
              <input id="kp-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-tel"><?= e(t('haberdarol.form.telefon')) ?></label>
              <input id="kp-tel" name="telefon" type="tel" required maxlength="20" pattern="[0-9 +\(\)\-]{7,20}" autocomplete="tel" inputmode="tel">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-il"><?= e(t('haberdarol.form.il')) ?></label>
              <select id="kp-il" name="il" required>
                <option value=""><?= e(t('haberdarol.form.il_sec')) ?></option>
                <?php foreach (site('iller') as $il): ?><option><?= e($il) ?></option><?php endforeach; ?>
              </select>
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-sektor"><?= e(t('haberdarol.form.sektor')) ?></label>
              <select id="kp-sektor" name="sektor" required>
                <option value=""><?= e(t('haberdarol.form.sektor_sec')) ?></option>
                <?php foreach ((array) site('sektorler') as $sk): ?><option><?= e($sk) ?></option><?php endforeach; ?>
              </select>
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field kupon__wide">
              <label for="kp-mesaj"><?= e(t('haberdarol.form.konular')) ?> <span class="opt"><?= e(t('haberdarol.form.istege_bagli')) ?></span></label>
              <textarea id="kp-mesaj" name="mesaj" rows="3" maxlength="5000" placeholder="<?= e(t('haberdarol.form.konular_ornek')) ?>"></textarea>
              <span class="field__err" aria-live="polite"></span>
            </div>
          </div>

          <div class="kupon__consent">
            <div class="field">
              <label class="check"><input type="checkbox" name="kvkk" value="1" required><span><?= th('haberdarol.onay.kvkk', ['baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '" target="_blank" rel="noopener">' . e(t('haberdarol.onay.kvkk_baglanti')) . '</a>']]) ?></span></label>
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label class="check"><input type="checkbox" name="etk" value="1" required><span><?= th('haberdarol.onay.etk') ?></span></label>
              <span class="field__err" aria-live="polite"></span>
            </div>
          </div>

          <div class="kupon__foot">
            <p class="form-status" role="status" aria-live="polite"></p>
            <button class="btn btn--ink" type="submit"><?= e(t('haberdarol.gonder.dugme')) ?> <?= arrow() ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <aside class="kp__side" aria-labelledby="kp-side-title">
    <h2 class="kp__side-h" id="kp-side-title"><?= e(t('haberdarol.yan.baslik')) ?></h2>
    <ol class="kp__list" role="list">
      <li data-rise>
        <span class="kp__n">1</span>
        <p><?= th('haberdarol.yan.bir') ?></p>
      </li>
      <li data-rise style="--delay:.06s">
        <span class="kp__n">2</span>
        <p><?= th('haberdarol.yan.iki') ?></p>
      </li>
      <li data-rise style="--delay:.12s">
        <span class="kp__n">3</span>
        <p><?= th('haberdarol.yan.uc') ?></p>
      </li>
    </ol>
    <p class="kp__note"><?= th('haberdarol.yan.not', ['baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '">' . e(pg_name('kvkk')) . '</a>']]) ?></p>
  </aside>
</section>

<div class="wrap">
  <a class="next" href="<?= url('kurumsal/misyonumuz') ?>">
    <span class="next__k"><?= e(t('haberdarol.sonraki.etiket', ['no' => pg_no('misyon')])) ?></span>
    <span class="next__t"><span><?= e(pg_name('misyon')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
