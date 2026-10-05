<?php
/**
 * İletişim: dilekçe.
 * Form doldurulmaz, boşlukları olan bir dilekçe yazılır. Gönderilince "ALINDI" kaşesi basılır,
 * dilekçe üçe katlanıp zarfa girer (app/form.php, 'iletisim').
 */
page([
    'id'          => 'contact',
    'title'       => pg_name('iletisim'),
    'description' => t('iletisim.seo.description'),
    'folio'       => pg_folio('iletisim'),
]);

$topics = array_map(fn($s) => $s['nav'], services());
$general = t('iletisim.dilekce.konu_genel');
$topics[] = $general;
$wanted = (string) ($_GET['konu'] ?? '');
$chosen = in_array($wanted, $topics, true) ? $wanted : $general;

$durum = (string) ($_GET['durum'] ?? '');
$date  = today_official();
?>

<section class="dk-head pagehead wrap" aria-labelledby="contact-title">
  <p class="label" data-rise><?= e(pg_label('iletisim')) ?></p>
  <h1 class="display dk-head__h" id="contact-title" data-rise style="--delay:.05s"><?= e(t('iletisim.hero.baslik')) ?></h1>
  <p class="lead dk-head__lead" data-rise style="--delay:.12s"><?= th('iletisim.hero.giris') ?></p>
</section>

<section class="dk wrap" aria-label="<?= e(t('iletisim.dilekce.alan_etiket')) ?>">
  <div class="dk__desk" data-desk>

    <form class="letter<?= $durum === 'tamam' ? ' is-received' : '' ?>" data-form data-letter action="<?= url('form') ?>" method="post" novalidate aria-labelledby="dk-subject">
      <input type="hidden" name="_form" value="iletisim">
      <input type="hidden" name="_token" value="<?= e(form_token()) ?>">
      <input type="hidden" name="_back" value="<?= e(url('iletisim')) ?>">
      <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <?php if ($durum === 'tamam'): ?>
        <p class="letter__notice is-ok" role="status"><?= e(t('iletisim.durum.tamam')) ?></p>
      <?php elseif ($durum === 'hata'): ?>
        <p class="letter__notice is-error" role="alert"><?= e(t('iletisim.durum.hata')) ?></p>
      <?php endif; ?>

      <p class="letter__date"><time datetime="<?= date('Y-m-d') ?>"><?= e($date) ?></time></p>

      <p class="letter__to">
        <span><?= e(t('iletisim.dilekce.hitap')) ?></span>
        <span><?= e(tr_upper(t('iletisim.dilekce.sehir'))) ?></span>
      </p>

      <p class="letter__subject" id="dk-subject">
        <span class="letter__k"><?= e(t('iletisim.dilekce.konu_etiket')) ?></span>
        <span data-mirror="konu"><?= e($chosen) ?></span>
        <?= e(t('iletisim.dilekce.konu_son')) ?>
      </p>

      <div class="letter__body">
        <p>
          <?= e(t('iletisim.paragraf1.ben')) ?>
          <span class="field blank" style="--w:15ch">
            <label class="sr-only" for="dk-name"><?= e(t('iletisim.paragraf1.ad_etiket')) ?></label>
            <input class="in" id="dk-name" name="namesurname" type="text" required maxlength="120" autocomplete="name" placeholder="<?= e(t('iletisim.paragraf1.ad_ornek')) ?>" data-mirror-src="name">
            <span class="field__err" aria-live="polite"></span>
          </span>,
          <span class="field blank" style="--w:13ch">
            <label class="sr-only" for="dk-firm"><?= e(t('iletisim.paragraf1.firma_etiket')) ?></label>
            <input class="in" id="dk-firm" name="firma" type="text" maxlength="160" autocomplete="organization" placeholder="<?= e(t('iletisim.paragraf1.firma_ornek')) ?>">
            <span class="field__err" aria-live="polite"></span>
          </span>
          <?= e(t('iletisim.paragraf1.adina')) ?>
          <span class="field blank blank--select">
            <label class="sr-only" for="dk-topic"><?= e(t('iletisim.paragraf1.konu_etiket')) ?></label>
            <select class="in" id="dk-topic" name="konu" data-mirror-src="konu">
              <?php foreach ($topics as $t): ?>
                <option<?= $t === $chosen ? ' selected' : '' ?>><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="field__err" aria-live="polite"></span>
          </span>
          <?= e(t('iletisim.paragraf1.konu_son')) ?>
        </p>

        <div class="field letter__msg">
          <label for="dk-msg"><?= e(t('iletisim.mesaj.etiket')) ?></label>
          <textarea class="in" id="dk-msg" name="message" required maxlength="5000" rows="5" placeholder="<?= e(t('iletisim.mesaj.ornek')) ?>"></textarea>
          <span class="field__err" aria-live="polite"></span>
        </div>

        <p>
          <?= e(t('iletisim.paragraf3.bana')) ?>
          <span class="field blank" style="--w:14ch">
            <label class="sr-only" for="dk-phone"><?= e(t('iletisim.paragraf3.telefon_etiket')) ?></label>
            <input class="in" id="dk-phone" name="phone" type="tel" maxlength="20" pattern="[0-9 +\(\)\-]{7,20}" autocomplete="tel" inputmode="tel" placeholder="<?= e(t('iletisim.paragraf3.telefon_ornek')) ?>">
            <span class="field__err" aria-live="polite"></span>
          </span>
          <?= e(t('iletisim.paragraf3.numara')) ?>
          <span class="field blank" style="--w:17ch">
            <label class="sr-only" for="dk-mail"><?= e(t('iletisim.paragraf3.eposta_etiket')) ?></label>
            <input class="in" id="dk-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email" placeholder="<?= e(t('iletisim.paragraf3.eposta_ornek')) ?>">
            <span class="field__err" aria-live="polite"></span>
          </span>
          <?= e(t('iletisim.paragraf3.adres')) ?>
        </p>

        <p class="letter__arz"><?= e(t('iletisim.paragraf3.arz')) ?></p>
      </div>

      <div class="letter__sign" aria-hidden="true">
        <span class="letter__sdate"><?= e($date) ?></span>
        <span class="letter__sname" data-mirror="name" data-empty="<?= e(t('iletisim.imza.ad')) ?>"><?= e(t('iletisim.imza.ad')) ?></span>
        <span class="letter__sline"><?= e(t('iletisim.imza.cizgi')) ?></span>
      </div>

      <div class="letter__foot">
        <p class="form-status" role="status" aria-live="polite"></p>
        <button class="btn sendbtn" type="submit"><?= e(t('iletisim.gonder.dugme')) ?> <?= arrow() ?></button>
      </div>

      <div class="alindi" aria-hidden="true" data-alindi>
        <svg viewBox="0 0 260 130">
          <rect x="4" y="4" width="252" height="122" rx="10" fill="none" stroke="currentColor" stroke-width="4"/>
          <rect x="12" y="12" width="236" height="106" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
          <text x="130" y="68" text-anchor="middle" font-size="50" font-weight="900" style="font-stretch:64%;letter-spacing:.06em"><?= e(tr_upper(t('iletisim.kase.baslik'))) ?></text>
          <text x="130" y="96" text-anchor="middle" font-size="16" font-weight="700" style="font-stretch:78%"><?= e(t('iletisim.kase.alt')) ?></text>
        </svg>
      </div>
    </form>

    <div class="dk__post" data-post hidden>
      <div class="dk__done" tabindex="-1" data-done>
        <p class="label"><?= e(t('iletisim.tamam.etiket')) ?></p>
        <p class="dk__done-h serif-display"><?= e(t('iletisim.tamam.baslik')) ?></p>
        <p class="dk__done-msg" data-done-msg><?= e(t('iletisim.tamam.mesaj')) ?></p>
        <a class="btn" href="<?= url('iletisim') ?>"><?= e(t('iletisim.tamam.yeni')) ?></a>
      </div>
    </div>
  </div>

  <aside class="dk__side" aria-label="<?= e(t('iletisim.yan.etiket')) ?>">
    <div class="kv" data-kv>
      <div class="kv__in">
        <div class="kv__face kv__front">
          <span class="kv__logo"><?php require APP . '/partials/logo.php'; ?></span>
          <span class="kv__id">
            <span class="kv__name"><?= e(cfg('name')) ?></span>
            <span class="kv__rows">
              <a href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a>
              <a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a>
            </span>
          </span>
        </div>
        <div class="kv__face kv__back">
          <span class="kv__id">
            <span class="kv__k"><?= e(t('iletisim.kart.adres')) ?></span>
            <a class="kv__addr" href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(cfg('address')) ?></a>
          </span>
          <span class="kv__rows">
            <a href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(t('iletisim.kart.harita')) ?></a>
            <a href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank"><?= e(t('iletisim.kart.whatsapp')) ?></a>
          </span>
        </div>
      </div>
      <button class="kv__flip" type="button" data-kv-flip aria-pressed="false"><?= e(t('iletisim.kart.cevir')) ?></button>
    </div>

    <dl class="dk__direct">
      <div>
        <dt><?= e(t('iletisim.liste.telefon')) ?></dt>
        <dd><a href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a></dd>
      </div>
      <div>
        <dt><?= e(t('iletisim.liste.eposta')) ?></dt>
        <dd><a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></dd>
      </div>
      <div>
        <dt><?= e(t('iletisim.liste.whatsapp')) ?></dt>
        <dd><a href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank"><?= e(t('iletisim.liste.whatsapp_yaz')) ?></a></dd>
      </div>
      <div>
        <dt><?= e(t('iletisim.liste.adres')) ?></dt>
        <dd><a href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(cfg('address')) ?></a></dd>
      </div>
    </dl>
  </aside>
</section>

<?php if ($nx = pg_next('iletisim')): ?>
<div class="wrap">
  <a class="next" href="<?= url($nx['path']) ?>">
    <span class="next__k"><?= e(t('iletisim.sonraki.etiket', ['no' => $nx['nn']])) ?></span>
    <span class="next__t"><span><?= e($nx['label']) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
<?php endif; ?>
