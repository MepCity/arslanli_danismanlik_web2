<?php
/**
 * İletişim: dilekçe.
 * Form doldurulmaz, boşlukları olan bir dilekçe yazılır. Gönderilince "ALINDI" kaşesi basılır,
 * dilekçe üçe katlanıp zarfa girer (app/form.php, 'iletisim').
 */
page([
    'id'          => 'contact',
    'title'       => 'İletişim',
    'description' => 'Arslanlı Yatırım & Danışmanlık’a yazın: telefon, e-posta, WhatsApp ve adres. Hibe, teşvik ve Ar-Ge desteklerine dair sorunuzu kısa bir dilekçeyle iletin.',
    'folio'       => 'Evrak 06 · <b>İletişim</b>',
]);

$topics = array_map(fn($s) => $s['nav'], services());
$topics[] = 'Genel bilgi';
$wanted = (string) ($_GET['konu'] ?? '');
$chosen = in_array($wanted, $topics, true) ? $wanted : 'Genel bilgi';

$durum = (string) ($_GET['durum'] ?? '');
$date  = today_official();
?>

<section class="dk-head pagehead wrap" aria-labelledby="contact-title">
  <p class="label" data-rise>Evrak 06 · İletişim</p>
  <h1 class="display dk-head__h" id="contact-title" data-rise style="--delay:.05s">Bize bir dilekçe yazın.</h1>
  <p class="lead dk-head__lead" data-rise style="--delay:.12s">Form doldurmuyorsunuz; boşlukları olan kısa bir dilekçe yazıyorsunuz. Adınızı, konunuzu ve size nasıl ulaşacağımızı yazmanız yeterli. Acelesi olan işler için telefon ve WhatsApp hemen yanında.</p>
</section>

<section class="dk wrap" aria-label="Dilekçe ve iletişim bilgileri">
  <div class="dk__desk" data-desk>

    <form class="letter<?= $durum === 'tamam' ? ' is-received' : '' ?>" data-form data-letter action="<?= url('form') ?>" method="post" novalidate aria-labelledby="dk-subject">
      <input type="hidden" name="_form" value="iletisim">
      <input type="hidden" name="_token" value="<?= e(form_token()) ?>">
      <input type="hidden" name="_back" value="<?= e(url('iletisim')) ?>">
      <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <?php if ($durum === 'tamam'): ?>
        <p class="letter__notice is-ok" role="status">Dilekçeniz bize ulaştı. Teşekkür ederiz.</p>
      <?php elseif ($durum === 'hata'): ?>
        <p class="letter__notice is-error" role="alert">Dilekçeniz gönderilemedi. Lütfen alanları kontrol edip yeniden deneyin ya da bizi telefonla arayın.</p>
      <?php endif; ?>

      <p class="letter__date"><time datetime="<?= date('Y-m-d') ?>"><?= e($date) ?></time></p>

      <p class="letter__to">
        <span><?= e(tr_upper(cfg('name'))) ?>’A</span>
        <span><?= e(tr_upper('İstanbul')) ?></span>
      </p>

      <p class="letter__subject" id="dk-subject">
        <span class="letter__k">Konu:</span>
        <span data-mirror="konu"><?= e($chosen) ?></span> hakkında görüşme talebi.
      </p>

      <div class="letter__body">
        <p>
          Ben
          <span class="field blank" style="--w:15ch">
            <label class="sr-only" for="dk-name">Adınız ve soyadınız (zorunlu)</label>
            <input class="in" id="dk-name" name="namesurname" type="text" required maxlength="120" autocomplete="name" placeholder="adınız soyadınız" data-mirror-src="name">
            <span class="field__err" aria-live="polite"></span>
          </span>,
          <span class="field blank" style="--w:13ch">
            <label class="sr-only" for="dk-firm">Firmanızın adı (isteğe bağlı)</label>
            <input class="in" id="dk-firm" name="firma" type="text" maxlength="160" autocomplete="organization" placeholder="firmanızın adı">
            <span class="field__err" aria-live="polite"></span>
          </span>
          adına yazıyorum.
          <span class="field blank blank--select">
            <label class="sr-only" for="dk-topic">Konu</label>
            <select class="in" id="dk-topic" name="konu" data-mirror-src="konu">
              <?php foreach ($topics as $t): ?>
                <option<?= $t === $chosen ? ' selected' : '' ?>><?= e($t) ?></option>
              <?php endforeach; ?>
            </select>
            <span class="field__err" aria-live="polite"></span>
          </span>
          hakkında sizinle görüşmek istiyorum.
        </p>

        <div class="field letter__msg">
          <label for="dk-msg">Kısaca durumumuz şöyle:</label>
          <textarea class="in" id="dk-msg" name="message" required maxlength="5000" rows="5" placeholder="Ne yapmak istiyorsunuz, hangi aşamadasınız, elinizde bir çağrı metni var mı?"></textarea>
          <span class="field__err" aria-live="polite"></span>
        </div>

        <p>
          Bana
          <span class="field blank" style="--w:14ch">
            <label class="sr-only" for="dk-phone">Telefon numaranız</label>
            <input class="in" id="dk-phone" name="phone" type="tel" maxlength="20" pattern="[0-9 +\(\)\-]{7,20}" autocomplete="tel" inputmode="tel" placeholder="05xx xxx xx xx">
            <span class="field__err" aria-live="polite"></span>
          </span>
          numarasından ya da
          <span class="field blank" style="--w:17ch">
            <label class="sr-only" for="dk-mail">E-posta adresiniz (zorunlu)</label>
            <input class="in" id="dk-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email" placeholder="ornek@firma.com">
            <span class="field__err" aria-live="polite"></span>
          </span>
          adresinden ulaşabilirsiniz.
        </p>

        <p class="letter__arz">Gereğini arz ederim.</p>
      </div>

      <div class="letter__sign" aria-hidden="true">
        <span class="letter__sdate"><?= e($date) ?></span>
        <span class="letter__sname" data-mirror="name" data-empty="Ad Soyad">Ad Soyad</span>
        <span class="letter__sline">Ad Soyad · İmza</span>
      </div>

      <div class="letter__foot">
        <p class="form-status" role="status" aria-live="polite"></p>
        <button class="btn sendbtn" type="submit">Dilekçeyi gönder <?= arrow() ?></button>
      </div>

      <div class="alindi" aria-hidden="true" data-alindi>
        <svg viewBox="0 0 260 130">
          <rect x="4" y="4" width="252" height="122" rx="10" fill="none" stroke="currentColor" stroke-width="4"/>
          <rect x="12" y="12" width="236" height="106" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
          <text x="130" y="68" text-anchor="middle" font-size="50" font-weight="900" style="font-stretch:64%;letter-spacing:.06em">ALINDI</text>
          <text x="130" y="96" text-anchor="middle" font-size="16" font-weight="700" style="font-stretch:78%"><?= e($date) ?> · ARSLANLI</text>
        </svg>
      </div>
    </form>

    <div class="dk__post" data-post hidden>
      <div class="dk__done" tabindex="-1" data-done>
        <p class="label">Dilekçe <?= e($date) ?></p>
        <p class="dk__done-h serif-display">Dilekçeniz yola çıktı.</p>
        <p class="dk__done-msg" data-done-msg>Dilekçeniz bize ulaştı.</p>
        <a class="btn" href="<?= url('iletisim') ?>">Yeni bir dilekçe yazın</a>
      </div>
    </div>
  </div>

  <aside class="dk__side" aria-label="Doğrudan iletişim">
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
            <span class="kv__k">Adres</span>
            <a class="kv__addr" href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(cfg('address')) ?></a>
          </span>
          <span class="kv__rows">
            <a href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank">Haritada aç</a>
            <a href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank">WhatsApp’tan yazın</a>
          </span>
        </div>
      </div>
      <button class="kv__flip" type="button" data-kv-flip aria-pressed="false">Kartı çevirin</button>
    </div>

    <dl class="dk__direct">
      <div>
        <dt>Telefon</dt>
        <dd><a href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a></dd>
      </div>
      <div>
        <dt>E-posta</dt>
        <dd><a href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a></dd>
      </div>
      <div>
        <dt>WhatsApp</dt>
        <dd><a href="https://wa.me/<?= e(cfg('whatsapp')) ?>" rel="noopener" target="_blank">Mesaj yazın</a></dd>
      </div>
      <div>
        <dt>Adres</dt>
        <dd><a href="<?= e(cfg('maps_url')) ?>" rel="noopener" target="_blank"><?= e(cfg('address')) ?></a></dd>
      </div>
    </dl>
  </aside>
</section>

<div class="wrap">
  <a class="next" href="<?= url('haberdarol') ?>">
    <span class="next__k">Sonraki evrak · 07</span>
    <span class="next__t"><span>Haberdar Ol</span></span>
    <?= arrow() ?>
  </a>
</div>
