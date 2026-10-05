<?php
/**
 * Bülten kaydı: sayfa kenarındaki sarı ayraç ve açtığı resmi form.
 * JavaScript yoksa ayraç /haberdarol sayfasına gider.
 * @var string $path  Geçerli sayfa yolu (layout.php)
 */
$kvkkUrl = url('kurumsal/kvkk-aydinlatma-metni');
?>
<?php if (page()['id'] !== 'signup'): ?>
<a class="nl-tab" href="<?= url('haberdarol') ?>" data-nl-open aria-haspopup="dialog">
  <?= icon('envelope-simple') ?><span>Bültene kayıt ol</span>
</a>
<?php endif; ?>

<dialog class="nl" id="bulten" aria-labelledby="nl-title" data-lenis-prevent>
  <div class="nl__sheet" data-nl-sheet>
    <header class="nl__top">
      <p class="docmeta"><span>Form: <b>ARS-B/01</b></span><span>Tarih: <b><?= today_official() ?></b></span></p>
      <button class="nl__close" type="button" data-nl-close><span>Kapat</span><?= icon('x') ?></button>
    </header>

    <div class="nl__body" data-nl-body>
      <p class="label">Bülten kayıt formu</p>
      <h2 class="display nl__title" id="nl-title">Haberdar olun.</h2>
      <p class="nl__lead">Yeni bir hibe ya da teşvik çağrısı açıldığında, bir programın kuralları değiştiğinde ve takip ettiğiniz bir başvurunun son tarihi yaklaştığında size yazalım. Formu bir kez doldurmanız yeterli.</p>

      <form class="nlf" data-form data-nl-form action="<?= url('form') ?>" method="post" novalidate>
        <input type="hidden" name="_form" value="bulten">
        <input type="hidden" name="_token" value="<?= e(form_token()) ?>">
        <input type="hidden" name="_back" value="<?= e(url($path ?? '')) ?>">
        <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div class="nlf__grid">
          <div class="field nlf__cell">
            <label for="nl-ad"><b>1</b> Ad <span class="req" aria-hidden="true">*</span></label>
            <input id="nl-ad" name="ad" type="text" required maxlength="80" autocomplete="given-name" placeholder="Ahmet">
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field nlf__cell">
            <label for="nl-soyad"><b>2</b> Soyad <span class="req" aria-hidden="true">*</span></label>
            <input id="nl-soyad" name="soyad" type="text" required maxlength="80" autocomplete="family-name" placeholder="Yılmaz">
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field nlf__cell">
            <label for="nl-mail"><b>3</b> E-posta <span class="req" aria-hidden="true">*</span></label>
            <input id="nl-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email" placeholder="ahmet@sirket.com">
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field nlf__cell">
            <label for="nl-tel"><b>4</b> Telefon <span class="req" aria-hidden="true">*</span></label>
            <input id="nl-tel" name="telefon" type="tel" required maxlength="20" pattern="[0-9 +\(\)\-]{7,20}" autocomplete="tel" inputmode="tel" placeholder="05xx xxx xx xx">
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field nlf__cell">
            <label for="nl-il"><b>5</b> İl <span class="req" aria-hidden="true">*</span></label>
            <select id="nl-il" name="il" required>
              <option value="">İl seçin</option>
              <?php foreach (site('iller') as $il): ?><option><?= e($il) ?></option><?php endforeach; ?>
            </select>
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field nlf__cell">
            <label for="nl-sektor"><b>6</b> Sektör <span class="req" aria-hidden="true">*</span></label>
            <select id="nl-sektor" name="sektor" required>
              <option value="">Sektör seçin</option>
              <?php foreach ((array) site('sektorler') as $sk): ?><option><?= e($sk) ?></option><?php endforeach; ?>
            </select>
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field nlf__cell nlf__cell--wide">
            <label for="nl-mesaj"><b>7</b> İlgilendiğiniz konular</label>
            <textarea id="nl-mesaj" name="mesaj" rows="3" maxlength="5000" placeholder="İlgilendiğiniz teşvik, hibe ya da danışmanlık konularını yazabilirsiniz"></textarea>
            <p class="field__err" aria-live="polite"></p>
          </div>
        </div>

        <div class="nlf__checks">
          <div class="field">
            <label class="check"><input type="checkbox" name="kvkk" value="1" required><span>Kişisel verilerimin işlenmesine ilişkin <a class="link" href="<?= $kvkkUrl ?>" target="_blank" rel="noopener">Aydınlatma Metni</a>’ni okudum. <span class="req" aria-hidden="true">*</span></span></label>
            <p class="field__err" aria-live="polite"></p>
          </div>
          <div class="field">
            <label class="check"><input type="checkbox" name="etk" value="1" required><span><?= e(cfg('name')) ?> tarafından e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine onay veriyorum. Onayımı dilediğim zaman geri alabilirim. <span class="req" aria-hidden="true">*</span></span></label>
            <p class="field__err" aria-live="polite"></p>
          </div>
        </div>

        <div class="nlf__foot">
          <button class="btn btn--ink" type="submit">Bültene kayıt ol <?= arrow() ?></button>
          <p class="form-status" role="status"></p>
        </div>
      </form>
    </div>

    <div class="nl__done" data-nl-done hidden tabindex="-1">
      <p class="label">Bülten kayıt formu</p>
      <h2 class="display nl__title">Kaydınız alındı.</h2>
      <p class="nl__lead">Sizi ilgilendiren bir çağrı açıldığında ya da bir programın kuralı değiştiğinde haberi bizden alacaksınız. Onayınızı istediğiniz zaman <a class="link" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a> adresine yazarak geri alabilirsiniz.</p>
      <button class="btn" type="button" data-nl-close>Kapat</button>
    </div>

    <div class="nl__stamp" aria-hidden="true" data-nl-stamp>
      <svg viewBox="0 0 300 110"><rect x="4" y="4" width="292" height="102" rx="10" fill="none" stroke="currentColor" stroke-width="5"/><rect x="13" y="13" width="274" height="84" rx="6" fill="none" stroke="currentColor" stroke-width="1.6"/><text x="150" y="66" text-anchor="middle" font-size="46" font-weight="850" style="font-stretch:66%;letter-spacing:.04em">KAYDEDİLDİ</text><text x="150" y="88" text-anchor="middle" font-size="15" font-weight="700" style="font-stretch:80%"><?= today_official() ?></text></svg>
    </div>
  </div>
</dialog>
