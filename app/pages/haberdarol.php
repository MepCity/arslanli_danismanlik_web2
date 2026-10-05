<?php
/**
 * Haberdar Ol: dergi kuponu.
 * Kesik çizgili kupon; makas kenarında bekler. Gönderilince kuponu çevresinden keser,
 * kupon düşer ve sayfada kalan boşlukta alındı fişi görünür (app/form.php, 'bulten').
 */
page([
    'id'          => 'signup',
    'title'       => 'Haberdar Ol',
    'description' => 'Sizi ilgilendiren hibe ve teşvik çağrıları açıldığında, program şartları değiştiğinde ya da kapanış tarihi yaklaştığında e-postayla haber verelim. Kuponu doldurun.',
    'folio'       => pg_folio('haberdarol'),
]);

$durum = (string) ($_GET['durum'] ?? '');
$sent  = $durum === 'tamam';
?>

<section class="kp-head pagehead wrap" aria-labelledby="signup-title">
  <p class="label" data-rise><?= e(pg_label('haberdarol')) ?></p>
  <h1 class="display kp-head__h" id="signup-title" data-rise style="--delay:.05s">Kesip gönderin.</h1>
  <p class="lead kp-head__lead" data-rise style="--delay:.12s">Sizi ilgilendiren bir destek çağrısı açıldığında, bir programın şartları değiştiğinde ya da takip ettiğiniz bir başvurunun son tarihi yaklaştığında e-postayla haber verelim. Kuponu bir kez doldurmanız yeterli.</p>
</section>

<section class="kp wrap" aria-label="Haberdar Ol kuponu">
  <div class="kp__page">
    <p class="kp__run" aria-hidden="true"><span>Arslanlı Bülteni</span><span>Kupon sayfası · <?= pg_no('haberdarol') ?></span></p>

    <div class="kp__stage<?= $sent ? ' is-sent' : '' ?>" data-kp-stage>
      <div class="kp__hole" data-kp-hole<?= $sent ? '' : ' hidden' ?>>
        <div class="kp__slip" tabindex="-1" data-kp-slip>
          <p class="label">Alındı · <?= e(today_official()) ?></p>
          <p class="kp__slip-h serif-display">Kuponunuz bize ulaştı.</p>
          <p class="kp__slip-msg" data-kp-msg>Sizi ilgilendiren bir çağrı açıldığında haber vereceğiz.</p>
          <?php if (feature('blog') || feature('duyurular')): ?>
          <p class="kp__slip-more">O zamana kadar <a class="link" href="<?= url(feature('blog') ? 'blog' : 'duyurular') ?>"><?= feature('blog') ? 'makalelere' : 'açık çağrılara' ?></a> göz atabilir ya da bir sorunuz varsa <a class="link" href="<?= url('iletisim') ?>">bize yazabilirsiniz</a>.</p>
          <?php else: ?>
          <p class="kp__slip-more">Bir sorunuz varsa <a class="link" href="<?= url('iletisim') ?>">bize yazabilirsiniz</a>.</p>
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
            <p class="kupon__notice" role="alert">Kuponunuz gönderilemedi. Lütfen alanları kontrol edip yeniden deneyin.</p>
          <?php endif; ?>

          <header class="kupon__head">
            <p class="kupon__kicker" id="kp-title">Kupon <em>Haberdar Ol</em></p>
            <ol class="kupon__steps" aria-label="Üç adım">
              <li><b>1</b> Doldurun</li>
              <li><b>2</b> Kesin</li>
              <li><b>3</b> Gönderin</li>
            </ol>
          </header>

          <div class="kupon__grid">
            <div class="field">
              <label for="kp-ad">Adınız</label>
              <input id="kp-ad" name="ad" type="text" required maxlength="80" autocomplete="given-name">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-soyad">Soyadınız</label>
              <input id="kp-soyad" name="soyad" type="text" required maxlength="80" autocomplete="family-name">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-mail">E-posta adresiniz</label>
              <input id="kp-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-tel">Telefonunuz</label>
              <input id="kp-tel" name="telefon" type="tel" required maxlength="20" pattern="[0-9 +\(\)\-]{7,20}" autocomplete="tel" inputmode="tel">
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-il">İliniz</label>
              <select id="kp-il" name="il" required>
                <option value="">İl seçin</option>
                <?php foreach (site('iller') as $il): ?><option><?= e($il) ?></option><?php endforeach; ?>
              </select>
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label for="kp-sektor">Sektörünüz</label>
              <select id="kp-sektor" name="sektor" required>
                <option value="">Sektör seçin</option>
                <?php foreach ((array) site('sektorler') as $sk): ?><option><?= e($sk) ?></option><?php endforeach; ?>
              </select>
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field kupon__wide">
              <label for="kp-mesaj">İlgilendiğiniz konular <span class="opt">(isteğe bağlı)</span></label>
              <textarea id="kp-mesaj" name="mesaj" rows="3" maxlength="5000" placeholder="İlgilendiğiniz teşvik, hibe ya da danışmanlık konularını yazabilirsiniz"></textarea>
              <span class="field__err" aria-live="polite"></span>
            </div>
          </div>

          <div class="kupon__consent">
            <div class="field">
              <label class="check"><input type="checkbox" name="kvkk" value="1" required><span>Kişisel verilerimin işlenmesine ilişkin <a class="link" href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>" target="_blank" rel="noopener">Aydınlatma Metni</a>’ni okudum.</span></label>
              <span class="field__err" aria-live="polite"></span>
            </div>
            <div class="field">
              <label class="check"><input type="checkbox" name="etk" value="1" required><span><?= e(cfg('name')) ?> tarafından e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine onay veriyorum. Onayımı dilediğim zaman geri alabilirim.</span></label>
              <span class="field__err" aria-live="polite"></span>
            </div>
          </div>

          <div class="kupon__foot">
            <p class="form-status" role="status" aria-live="polite"></p>
            <button class="btn btn--ink" type="submit">Kuponu gönder <?= arrow() ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <aside class="kp__side" aria-labelledby="kp-side-title">
    <h2 class="kp__side-h" id="kp-side-title">Size neler gelir?</h2>
    <ol class="kp__list" role="list">
      <li data-rise>
        <span class="kp__n">1</span>
        <p><b>Yeni açılan çağrılar.</b> İlgilendiğiniz konularda bir destek çağrısı açıldığında, kimlerin başvurabileceğiyle birlikte.</p>
      </li>
      <li data-rise style="--delay:.06s">
        <span class="kp__n">2</span>
        <p><b>Değişen kurallar.</b> Bir programın destek oranı, bütçe sınırı ya da başvuru şartı değiştiğinde.</p>
      </li>
      <li data-rise style="--delay:.12s">
        <span class="kp__n">3</span>
        <p><b>Yaklaşan kapanışlar.</b> Takip ettiğiniz bir çağrının son başvuru tarihi yaklaştığında.</p>
      </li>
    </ol>
    <p class="kp__note">Kişisel verilerinizin nasıl işlendiğini <a class="link" href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>">KVKK Aydınlatma Metni</a>’nde bulabilirsiniz.</p>
  </aside>
</section>

<div class="wrap">
  <a class="next" href="<?= url('kurumsal/misyonumuz') ?>">
    <span class="next__k">Sonraki evrak · <?= pg_no('misyon') ?></span>
    <span class="next__t"><span>Misyonumuz</span></span>
    <?= arrow() ?>
  </a>
</div>
