<?php
/**
 * Kariyer başvuru formu (özlük dosyası) ve gönderim sonrası "DOSYAYA EKLENDİ" kaşesi.
 * Genel başvuru (/kariyer) ile ilana başvuru (/kariyer/{adres}) aynı formu kullanır; gönderim, doğrulama iletileri,
 * özgeçmiş alanı ve JavaScript'siz yol ikisinde de aynıdır (app/form.php, assets/js/pages/careers.js).
 *
 * @var array|null $krIlan  null: genel başvuru (aday havuzu). İlan: forma ilanın kimliği gizli alan olarak eklenir ve
 *                          "ilgilenilen pozisyon" kutusu, ilanın başlığı daktiloyla yazılmış gibi sabit görünür
 *                          (sunucu zaten pozisyonu ilandan alır, gönderilen değer yok sayılır).
 * @var bool   $sent        Gönderim sonrası durum (?durum=tamam)
 * @var string $durum       ?durum değeri
 * @var string $date        Resmî tarih
 * @var array  $cities      Şehir listesi
 * @var array  $levels      Deneyim listesi
 */
$krIlan = $krIlan ?? null;
?>
    <form class="kd" data-form data-kd action="<?= url('form') ?>" method="post" enctype="multipart/form-data" aria-labelledby="kd-title"<?= $sent ? ' hidden' : '' ?>>
      <input type="hidden" name="_form" value="kariyer">
      <input type="hidden" name="_token" value="<?= e(form_token()) ?>">
      <input type="hidden" name="_back" value="<?= e($krIlan ? ilan_url($krIlan) : url('kariyer')) ?>">
      <?php if ($krIlan): ?><input type="hidden" name="ilan" value="<?= e($krIlan['id']) ?>"><?php endif; ?>
      <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
      <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <div class="kd__tab" aria-hidden="true">
        <span class="kd__tabk"><?= e(t('kariyerform.dosya.sekme')) ?></span>
        <span class="kd__who is-empty" data-who data-empty="<?= e(t('kariyerform.dosya.aday')) ?>"><?= e(t('kariyerform.dosya.aday')) ?></span>
      </div>

      <div class="kd__body">
        <div class="kd__sheet">
          <span class="kd__clip" aria-hidden="true"><?= icon('paperclip', 'ico') ?></span>

          <?php if ($durum === 'hata'): ?>
            <p class="kd__notice" role="alert"><?= e(t('kariyerform.dosya.hata')) ?></p>
          <?php endif; ?>

          <header class="kd__head">
            <h2 class="kd__title" id="kd-title"><?= e(t('kariyerform.dosya.baslik')) ?></h2>
            <p class="kd__date"><span><?= e(t('kariyerform.dosya.tarih')) ?></span> <time datetime="<?= date('Y-m-d') ?>"><?= e($date) ?></time></p>
          </header>
          <p class="kd__req"><?= th('kariyerform.dosya.zorunlu', ['yildiz' => ['html' => '<span class="req">*</span>']]) ?></p>

          <div class="kd__sec" role="group" aria-labelledby="kd-s1">
            <p class="kd__band" id="kd-s1"><b>A</b> <?= e(t('kariyerform.kimlik.bant')) ?></p>
            <div class="cells">
              <div class="field cell">
                <label for="kr-ad"><i>1</i> <?= e(t('kariyerform.kimlik.ad')) ?> <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-ad" name="ad" type="text" required maxlength="80" autocomplete="given-name" placeholder="<?= e(t('kariyerform.kimlik.ad_ornek')) ?>" data-who-src="ad">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-soyad"><i>2</i> <?= e(t('kariyerform.kimlik.soyad')) ?> <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-soyad" name="soyad" type="text" required maxlength="80" autocomplete="family-name" placeholder="<?= e(t('kariyerform.kimlik.soyad_ornek')) ?>" data-who-src="soyad">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-mail"><i>3</i> <?= e(t('kariyerform.kimlik.eposta')) ?> <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email" placeholder="<?= e(t('kariyerform.kimlik.eposta_ornek')) ?>">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-tel"><i>4</i> <?= e(t('kariyerform.kimlik.telefon')) ?> <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-tel" name="telefon" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" pattern="[0-9 +\(\)\-]{7,20}" placeholder="<?= e(t('kariyerform.kimlik.telefon_ornek')) ?>">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-sehir"><i>5</i> <?= e(t('kariyerform.kimlik.sehir')) ?> <span class="req" aria-hidden="true">*</span></label>
                <select id="kr-sehir" name="sehir" required>
                  <option value=""><?= e(t('kariyerform.kimlik.sehir_sec')) ?></option>
                  <?php foreach ($cities as $il): ?><option<?= $il === 'İstanbul' ? ' selected' : '' ?>><?= e($il) ?></option><?php endforeach; ?>
                </select>
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-li"><i>6</i> <?= e(t('kariyerform.kimlik.linkedin')) ?> <em><?= e(t('kariyerform.dosya.istege_bagli')) ?></em></label>
                <input id="kr-li" name="linkedin" type="url" maxlength="200" autocomplete="url" inputmode="url" placeholder="<?= e(t('kariyerform.kimlik.linkedin_ornek')) ?>">
                <p class="field__err" aria-live="polite"></p>
              </div>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s2">
            <p class="kd__band" id="kd-s2"><b>B</b> <?= e(t('kariyerform.meslek.bant')) ?></p>
            <div class="cells">
<?php if ($krIlan): ?>
              <div class="field cell cell--fixed">
                <span class="cell__l" id="kr-poz-l"><i>7</i> <?= e(t('kariyerform.meslek.pozisyon_ilan')) ?></span>
                <p class="cell__typed" aria-labelledby="kr-poz-l"><?= e($krIlan['title']) ?></p>
              </div>
<?php else: ?>
              <div class="field cell">
                <label for="kr-poz"><i>7</i> <?= e(t('kariyerform.meslek.pozisyon')) ?> <em><?= e(t('kariyerform.dosya.istege_bagli')) ?></em></label>
                <input id="kr-poz" name="pozisyon" type="text" maxlength="120" list="kr-alanlar" autocomplete="off" placeholder="<?= e(t('kariyerform.meslek.pozisyon_ornek')) ?>">
                <datalist id="kr-alanlar">
                  <?php foreach (services() as $s): ?><option value="<?= e($s['title']) ?>"></option><?php endforeach; ?>
                  <option value="<?= e(t('kariyerform.meslek.staj')) ?>"></option>
                </datalist>
                <p class="field__err" aria-live="polite"></p>
              </div>
<?php endif; ?>
              <div class="field cell">
                <label for="kr-den"><i>8</i> <?= e(t('kariyerform.meslek.deneyim')) ?> <em><?= e(t('kariyerform.dosya.istege_bagli')) ?></em></label>
                <select id="kr-den" name="deneyim">
                  <option value=""><?= e(t('kariyerform.meslek.deneyim_sec')) ?></option>
                  <?php foreach ($levels as $d): ?><option><?= e($d) ?></option><?php endforeach; ?>
                </select>
                <p class="field__err" aria-live="polite"></p>
              </div>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s3">
            <p class="kd__band" id="kd-s3"><b>C</b> <?= e(t('kariyerform.ek.bant')) ?></p>
            <div class="field attach" data-attach>
              <span class="attach__l" id="kr-cv-l"><i>Ek-1</i> <?= e(t('kariyerform.ek.cv')) ?> <span class="req" aria-hidden="true">*</span></span>
              <label class="drop" data-drop>
                <input class="drop__input" type="file" name="cv" required accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" aria-labelledby="kr-cv-l" aria-describedby="kr-cv-h">
                <span class="drop__ico" aria-hidden="true"><?= icon('paperclip') ?></span>
                <span class="drop__t"><?= e(t('kariyerform.ek.sec')) ?></span>
                <span class="drop__h" id="kr-cv-h"><?= e(t('kariyerform.ek.kural')) ?></span>
              </label>
              <div class="drop__file" data-drop-file hidden>
                <span class="drop__clip" aria-hidden="true"><?= icon('paperclip') ?></span>
                <span class="drop__name" data-drop-name></span>
                <span class="drop__size" data-drop-size></span>
                <button class="drop__clear" type="button" data-drop-clear aria-label="<?= e(t('kariyerform.ek.kaldir_etiket')) ?>"><?= icon('x') ?> <?= e(t('kariyerform.ek.kaldir')) ?></button>
              </div>
              <p class="field__err" aria-live="polite"></p>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s4">
            <p class="kd__band" id="kd-s4"><b>D</b> <?= e(t('kariyerform.yazi.bant')) ?></p>
            <div class="cells">
            <div class="field cell cell--msg">
              <label for="kr-msg"><i>9</i> <?= e(t('kariyerform.yazi.etiket')) ?> <em><?= e(t('kariyerform.dosya.istege_bagli')) ?></em></label>
              <textarea id="kr-msg" name="mesaj" rows="5" maxlength="5000" placeholder="<?= e(t('kariyerform.yazi.ornek')) ?>"></textarea>
              <p class="field__err" aria-live="polite"></p>
            </div>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s5">
            <p class="kd__band" id="kd-s5"><b>E</b> <?= e(t('kariyerform.onay.bant')) ?></p>
            <div class="kd__checks">
              <div class="field">
                <label class="check"><input type="checkbox" name="kvkk" value="1" required><span><?= th('kariyerform.onay.kvkk', ['baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '#basvuru-adaylari" target="_blank" rel="noopener">' . e(t('kariyerform.onay.kvkk_baglanti')) . '</a>']]) ?> <span class="req" aria-hidden="true">*</span></span></label>
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field">
                <label class="check"><input type="checkbox" name="saklama" value="1"><span><?= th('kariyerform.onay.saklama') ?> <em class="opt"><?= e(t('kariyerform.onay.istege_bagli')) ?></em></span></label>
              </div>
            </div>
          </div>

          <div class="kd__foot">
            <p class="form-status" role="status" aria-live="polite"></p>
            <button class="btn sendbtn" type="submit"><?= e(t('kariyerform.gonder.dugme')) ?> <?= arrow() ?></button>
          </div>
        </div>
      </div>
    </form>

    <div class="kd kd--post<?= $sent ? ' is-in' : '' ?>" data-post<?= $sent ? '' : ' hidden' ?>>
      <div class="kd__tab" aria-hidden="true">
        <span class="kd__tabk"><?= e(t('kariyerform.dosya.sekme')) ?></span>
        <span class="kd__who" data-post-who><?= e(t('kariyerform.tamam.kayitli')) ?></span>
      </div>
      <div class="kd__body">
        <div class="kd__done" tabindex="-1" data-done>
          <p class="label"><?= e(t('kariyerform.tamam.etiket')) ?></p>
          <p class="kd__done-who" data-done-who aria-hidden="true"></p>
          <h2 class="kd__done-h serif-display"><?= e(t('kariyerform.tamam.baslik')) ?></h2>
          <p class="kd__done-msg" data-done-msg><?= $krIlan ? e(t('kariyerform.tamam.mesaj_ilan', ['ilan' => $krIlan['title']])) : e(t('kariyerform.tamam.mesaj')) ?></p>
          <a class="btn" href="<?= url() ?>"><?= e(t('kariyerform.tamam.ana_sayfa')) ?> <?= arrow() ?></a>
          <div class="kd-stamp" aria-hidden="true">
            <svg viewBox="0 0 300 130">
              <rect x="4" y="4" width="292" height="122" rx="10" fill="none" stroke="currentColor" stroke-width="4"/>
              <rect x="12" y="12" width="276" height="106" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <text x="150" y="64" text-anchor="middle" font-size="34" font-weight="900" style="font-stretch:60%;letter-spacing:.03em"><?= e(tr_upper(t('kariyerform.tamam.kase_baslik'))) ?></text>
              <text x="150" y="96" text-anchor="middle" font-size="16" font-weight="700" style="font-stretch:78%"><?= e(t('kariyerform.tamam.kase_alt')) ?></text>
            </svg>
          </div>
        </div>
      </div>
    </div>
