<?php
/**
 * Kariyer: özlük dosyası.
 * Başvuru formu, manila bir dosyanın içindeki basılı bir form gibi kurulur: numaralı kutular, bölüm şeritleri,
 * özgeçmiş ataç ile forma tutturulur. Gönderilince form dosyanın içine kayar, kapak kapanır ve
 * "DOSYAYA EKLENDİ" kaşesi basılır (app/form.php, 'kariyer').
 */
page([
    'id'          => 'careers',
    'title'       => 'Kariyer',
    'description' => 'Arslanlı Yatırım & Danışmanlık ekibine katılmak için iş başvurusu formu. Hibe, teşvik ve yatırım danışmanlığında birlikte çalışalım.',
    'folio'       => 'Evrak 14 · <b>Kariyer</b>',
]);

$durum = (string) ($_GET['durum'] ?? '');
$sent  = $durum === 'tamam';
$date  = today_official();
$cities = site('sehirler');
$levels = site('deneyim');
?>

<section class="kr-head pagehead wrap" aria-labelledby="kr-title">
  <p class="label" data-rise>Evrak 14 · Kariyer</p>
  <h1 class="display kr-head__h" id="kr-title" data-rise style="--delay:.05s">Dosyanızı <?= annot('açalım', 'under', 'red') ?>.</h1>
  <div class="kr-head__txt" data-rise style="--delay:.12s">
    <p class="lead">Hibe, teşvik ve yatırım danışmanlığında bizimle çalışmak istiyorsanız aşağıdaki formu doldurun. Başvurunuz özgeçmişinizle birlikte doğrudan ekibimize ulaşır.</p>
    <p class="kr-head__more">Burada iş, çağrı metnini ve mevzuatı okumak, bir başvuruyu eksiksiz hazırlamak ve son tarihe yetiştirmektir. Kâğıda, tarihe ve ayrıntıya özen gösteriyorsanız bize yazın.</p>
  </div>
</section>

<section class="kr wrap" aria-label="İş başvurusu">

  <aside class="kr__side" aria-label="Çalıştığımız alanlar ve değerlendirme süreci">
    <div class="kr__block" data-rise>
      <h2 class="kr__h">Çalıştığımız alanlar</h2>
      <ol class="kr__areas" role="list">
        <?php $i = 0; foreach (services() as $slug => $s): $i++; ?>
          <li><span class="kr__no" aria-hidden="true"><?= sprintf('%02d', $i) ?></span><a href="<?= service_url($slug) ?>"><?= e($s['title']) ?></a></li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="kr__block" data-rise>
      <h2 class="kr__h">Başvurunuz nasıl değerlendirilir?</h2>
      <ol class="kr__steps" role="list">
        <li>
          <span class="kr__step" aria-hidden="true">1</span>
          <p><b>Başvurunuz bize ulaşır.</b> Form ve özgeçmişiniz doğrudan ekibimize iletilir.</p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">2</span>
          <p><b>İnceleriz.</b> Deneyiminizi ve ilgilendiğiniz alanı ekibimizin ihtiyaçlarıyla birlikte değerlendiririz.</p>
        </li>
        <li>
          <span class="kr__step" aria-hidden="true">3</span>
          <p><b>Size ulaşırız.</b> Uygun bir pozisyon olduğunda görüşme için sizinle iletişime geçeriz.</p>
        </li>
      </ol>
    </div>

    <p class="kr__note" data-rise>Başvurunuzda paylaştığınız bilgiler yalnızca işe alım süreci için kullanılır. Ayrıntılar için <a class="link" href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>#basvuru-adaylari">Aydınlatma Metni</a>’ne bakabilirsiniz.</p>
  </aside>

  <div class="kr__desk" id="basvuru" data-desk>

    <form class="kd" data-form data-kd action="<?= url('form') ?>" method="post" enctype="multipart/form-data" aria-labelledby="kd-title"<?= $sent ? ' hidden' : '' ?>>
      <input type="hidden" name="_form" value="kariyer">
      <input type="hidden" name="_token" value="<?= e(form_token()) ?>">
      <input type="hidden" name="_back" value="<?= e(url('kariyer')) ?>">
      <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
      <div class="hp" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <div class="kd__tab" aria-hidden="true">
        <span class="kd__tabk">Özlük dosyası</span>
        <span class="kd__who is-empty" data-who data-empty="aday adı">aday adı</span>
      </div>

      <div class="kd__body">
        <div class="kd__sheet">
          <span class="kd__clip" aria-hidden="true"><?= icon('paperclip', 'ico') ?></span>

          <?php if ($durum === 'hata'): ?>
            <p class="kd__notice" role="alert">Başvurunuz gönderilemedi. Lütfen alanları kontrol edip yeniden deneyin ya da bizi telefonla arayın.</p>
          <?php endif; ?>

          <header class="kd__head">
            <h2 class="kd__title" id="kd-title">Aday başvuru formu</h2>
            <p class="kd__date"><span>Tarih</span> <time datetime="<?= date('Y-m-d') ?>"><?= e($date) ?></time></p>
          </header>
          <p class="kd__req">Yıldızlı (<span class="req">*</span>) alanların doldurulması zorunludur.</p>

          <div class="kd__sec" role="group" aria-labelledby="kd-s1">
            <p class="kd__band" id="kd-s1"><b>A</b> Kimlik ve iletişim</p>
            <div class="cells">
              <div class="field cell">
                <label for="kr-ad"><i>1</i> Ad <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-ad" name="ad" type="text" required maxlength="80" autocomplete="given-name" placeholder="Örn: Ahmet" data-who-src="ad">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-soyad"><i>2</i> Soyad <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-soyad" name="soyad" type="text" required maxlength="80" autocomplete="family-name" placeholder="Örn: Yılmaz" data-who-src="soyad">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-mail"><i>3</i> E-posta adresi <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-mail" name="email" type="email" required maxlength="160" autocomplete="email" inputmode="email" placeholder="ornek@alanadi.com">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-tel"><i>4</i> Telefon numarası <span class="req" aria-hidden="true">*</span></label>
                <input id="kr-tel" name="telefon" type="tel" required maxlength="20" autocomplete="tel" inputmode="tel" pattern="[0-9 +\(\)\-]{7,20}" placeholder="0532 000 00 00">
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-sehir"><i>5</i> Şehir <span class="req" aria-hidden="true">*</span></label>
                <select id="kr-sehir" name="sehir" required>
                  <option value="">Şehir seçin</option>
                  <?php foreach ($cities as $il): ?><option<?= $il === 'İstanbul' ? ' selected' : '' ?>><?= e($il) ?></option><?php endforeach; ?>
                </select>
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-li"><i>6</i> LinkedIn profili <em>(isteğe bağlı)</em></label>
                <input id="kr-li" name="linkedin" type="url" maxlength="200" autocomplete="url" inputmode="url" placeholder="https://www.linkedin.com/in/profil">
                <p class="field__err" aria-live="polite"></p>
              </div>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s2">
            <p class="kd__band" id="kd-s2"><b>B</b> Mesleki bilgiler</p>
            <div class="cells">
              <div class="field cell">
                <label for="kr-poz"><i>7</i> İlgilenilen alan / pozisyon <em>(isteğe bağlı)</em></label>
                <input id="kr-poz" name="pozisyon" type="text" maxlength="120" list="kr-alanlar" autocomplete="off" placeholder="Örn: Teşvik danışmanlığı">
                <datalist id="kr-alanlar">
                  <?php foreach (services() as $s): ?><option value="<?= e($s['title']) ?>"></option><?php endforeach; ?>
                  <option value="Staj"></option>
                </datalist>
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field cell">
                <label for="kr-den"><i>8</i> Deneyim süresi <em>(isteğe bağlı)</em></label>
                <select id="kr-den" name="deneyim">
                  <option value="">Deneyim seçin</option>
                  <?php foreach ($levels as $d): ?><option><?= e($d) ?></option><?php endforeach; ?>
                </select>
                <p class="field__err" aria-live="polite"></p>
              </div>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s3">
            <p class="kd__band" id="kd-s3"><b>C</b> Ekler</p>
            <div class="field attach" data-attach>
              <span class="attach__l" id="kr-cv-l"><i>Ek-1</i> Özgeçmiş (CV) <span class="req" aria-hidden="true">*</span></span>
              <label class="drop" data-drop>
                <input class="drop__input" type="file" name="cv" required accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" aria-labelledby="kr-cv-l" aria-describedby="kr-cv-h">
                <span class="drop__ico" aria-hidden="true"><?= icon('paperclip') ?></span>
                <span class="drop__t">Özgeçmişinizi seçmek için tıklayın ya da buraya sürükleyin</span>
                <span class="drop__h" id="kr-cv-h">Yalnızca PDF ve DOCX, en fazla 5 MB.</span>
              </label>
              <div class="drop__file" data-drop-file hidden>
                <span class="drop__clip" aria-hidden="true"><?= icon('paperclip') ?></span>
                <span class="drop__name" data-drop-name></span>
                <span class="drop__size" data-drop-size></span>
                <button class="drop__clear" type="button" data-drop-clear aria-label="Seçilen dosyayı kaldır"><?= icon('x') ?> Kaldır</button>
              </div>
              <p class="field__err" aria-live="polite"></p>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s4">
            <p class="kd__band" id="kd-s4"><b>D</b> Ön yazı</p>
            <div class="cells">
            <div class="field cell cell--msg">
              <label for="kr-msg"><i>9</i> Ön yazı / mesaj <em>(isteğe bağlı)</em></label>
              <textarea id="kr-msg" name="mesaj" rows="5" maxlength="5000" placeholder="Başvurunuzla ilgili iletmek istediğiniz ön yazıyı veya açıklamayı buraya yazabilirsiniz"></textarea>
              <p class="field__err" aria-live="polite"></p>
            </div>
            </div>
          </div>

          <div class="kd__sec" role="group" aria-labelledby="kd-s5">
            <p class="kd__band" id="kd-s5"><b>E</b> Onaylar</p>
            <div class="kd__checks">
              <div class="field">
                <label class="check"><input type="checkbox" name="kvkk" value="1" required><span>Kişisel verilerimin <a class="link" href="<?= url('kurumsal/kvkk-aydinlatma-metni') ?>#basvuru-adaylari" target="_blank" rel="noopener">Aydınlatma Metni</a> kapsamında işlenmesini ve iş başvurusu değerlendirme sürecinde saklanmasını onaylıyorum. <span class="req" aria-hidden="true">*</span></span></label>
                <p class="field__err" aria-live="polite"></p>
              </div>
              <div class="field">
                <label class="check"><input type="checkbox" name="saklama" value="1"><span>Başvurumun, ileride açılabilecek uygun pozisyonlar için de saklanmasına izin veriyorum. <em class="opt">(İsteğe bağlı)</em></span></label>
              </div>
            </div>
          </div>

          <div class="kd__foot">
            <p class="form-status" role="status" aria-live="polite"></p>
            <button class="btn sendbtn" type="submit">Başvurumu gönder <?= arrow() ?></button>
          </div>
        </div>
      </div>
    </form>

    <div class="kd kd--post<?= $sent ? ' is-in' : '' ?>" data-post<?= $sent ? '' : ' hidden' ?>>
      <div class="kd__tab" aria-hidden="true">
        <span class="kd__tabk">Özlük dosyası</span>
        <span class="kd__who" data-post-who>kayıtlı</span>
      </div>
      <div class="kd__body">
        <div class="kd__done" tabindex="-1" data-done>
          <p class="label">Başvuru · <?= e($date) ?></p>
          <p class="kd__done-who" data-done-who aria-hidden="true"></p>
          <h2 class="kd__done-h serif-display">Başvurunuz alındı.</h2>
          <p class="kd__done-msg" data-done-msg>Teşekkür ederiz. Başvurunuzu inceledikten sonra uygun bir pozisyon olduğunda sizinle iletişime geçeceğiz.</p>
          <a class="btn" href="<?= url() ?>">Ana sayfaya dönün <?= arrow() ?></a>
          <div class="kd-stamp" aria-hidden="true">
            <svg viewBox="0 0 300 130">
              <rect x="4" y="4" width="292" height="122" rx="10" fill="none" stroke="currentColor" stroke-width="4"/>
              <rect x="12" y="12" width="276" height="106" rx="6" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <text x="150" y="64" text-anchor="middle" font-size="34" font-weight="900" style="font-stretch:60%;letter-spacing:.03em">DOSYAYA EKLENDİ</text>
              <text x="150" y="96" text-anchor="middle" font-size="16" font-weight="700" style="font-stretch:78%"><?= e($date) ?> · ARSLANLI</text>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k">Aklınızdaki soru için</span>
    <span class="next__t"><span>İletişim</span></span>
    <?= arrow() ?>
  </a>
</div>
