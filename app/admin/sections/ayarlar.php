<?php
/**
 * İletişim ve şirket: telefon, adres, şirket bilgileri, sosyal medya, e-posta.
 * Veri: content 'settings' (config.php üzerine yazılan katman, bkz. settings_apply()).
 */

$networks = [
    'Instagram' => ['Instagram', 'https://www.instagram.com/...'],
    'LinkedIn'  => ['LinkedIn', 'https://www.linkedin.com/company/...'],
    'Facebook'  => ['Facebook', 'https://www.facebook.com/...'],
    'X'         => ['X (Twitter)', 'https://x.com/...'],
];
$secures = ['ssl' => 'SSL (genellikle 465 numaralı port)', 'tls' => 'TLS (genellikle 587 numaralı port)', 'none' => 'Yok (genellikle 25 numaralı port)'];

function ay_url_ok(string $u): bool
{
    return settings_url_ok($u);
}

require_once APP . '/mailer.php';   // mail_address_ok(): e-posta adreslerinin sıkı denetimi; deneme e-postası

/* ---------- Deneme e-postası ---------- */
if (($rest[0] ?? '') === 'deneme' && $method === 'POST') {
    $to = (string) cfg('mail.to');
    if (!mail_address_ok($to)) {
        adm_flash('Alıcı e-posta adresi geçerli değil. Önce E-posta bölümünden düzeltip kaydedin.', 'err');
        adm_go('ayarlar#eposta');
    }
    if (($_SESSION['test_mail_at'] ?? 0) > time() - 20) {
        adm_flash('Az önce bir deneme e-postası gönderildi. Lütfen birkaç saniye bekleyin.', 'err');
        adm_go('ayarlar#eposta');
    }
    $_SESSION['test_mail_at'] = time();
    $ok = send_mail($to, 'Deneme e-postası: ' . cfg('name'),
        "Merhaba,\n\nBu ileti, web sitesi yönetim panelinden gönderilen bir deneme e-postasıdır.\n"
        . "Bu iletiyi aldıysanız sitedeki formlardan gelen bildirimler de bu adrese ulaşacaktır.\n\n"
        . 'Gönderim zamanı: ' . date('d.m.Y H:i') . "\n");
    if ($ok) {
        adm_flash('Deneme e-postası ' . $to . ' adresine gönderildi. Birkaç dakika içinde gelen kutusuna ya da spam klasörüne düşmelidir.');
    } else {
        adm_flash('E-posta gönderilemedi. Sunucu, port, güvenlik türü, kullanıcı adı ve şifreyi kontrol edin. SMTP kullanmıyorsanız barındırma firmanız sunucunun e-posta fonksiyonunu kapatmış olabilir; bu durumda SMTP bilgilerini girin.', 'err');
    }
    adm_go('ayarlar#eposta');
}

/* ---------- Mevcut (geçerli) değerler ---------- */
// SMTP üç hâlden birindedir (bkz. smtp_state): panelde seçim yok (yapılandırma dosyası geçerli), panelden kaydedilmiş, panelde kapatılmış
$smtpSt    = smtp_state();
$smtpMode  = $smtpSt['mode'];
$smtpShow  = $smtpMode === 'panel' ? $smtpSt['panel'] : ($smtpMode === 'miras' ? $smtpSt['inherited'] : null);   // alanlarda görünen bağlantı bilgisi
$smtpPanel = $smtpMode === 'panel' ? $smtpSt['panel'] : null;                                                   // panelde kayıtlı olan (şifre yeniden yazma kuralı buna göre)
$smtpNow   = smtp_tuple(cfg('mail.smtp'));                                                                       // şu an gerçekten kullanılan
$socialNow = (array) cfg('social', []);

$v = [
    'name'          => (string) cfg('name'),
    'phone'         => (string) cfg('phone'),
    'whatsapp'      => (string) cfg('whatsapp'),
    'email'         => (string) cfg('email'),
    'address'       => (string) cfg('address'),
    'address_short' => (string) cfg('address_short'),
    'maps_url'      => (string) cfg('maps_url'),
    'authorized'    => (string) cfg('company.authorized'),
    'tax_office'    => (string) cfg('company.tax_office'),
    'tax_number'    => (string) cfg('company.tax_number'),
    'mail_to'       => (string) cfg('mail.to'),
    'mail_from'     => (string) cfg('mail.from'),
    'mail_from_name' => (string) cfg('mail.from_name'),
    'store'         => (bool) cfg('store_submissions'),
    'smtp_mode'     => $smtpMode,
    'smtp_host'     => (string) ($smtpShow['host'] ?? ''),
    'smtp_port'     => (string) ($smtpShow['port'] ?? '465'),
    'smtp_secure'   => (string) ($smtpShow['secure'] ?? 'ssl'),
    'smtp_user'     => (string) ($smtpShow['user'] ?? ''),
];
foreach ($networks as $k => $_) $v['social_' . $k] = (string) ($socialNow[$k] ?? '');
$storedPass = $smtpMode === 'panel' ? $smtpSt['panel_pass'] : '';   // yalnızca panelden yazılmış şifre; yapılandırma dosyasındaki şifre buraya hiç gelmez
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST' && ($rest[0] ?? '') === '') {
    foreach (['name', 'phone', 'whatsapp', 'email', 'address', 'address_short', 'maps_url', 'authorized', 'tax_office', 'tax_number', 'mail_to', 'mail_from', 'mail_from_name', 'smtp_host', 'smtp_port', 'smtp_user'] as $k) {
        $v[$k] = post_str($k, $k === 'address' ? 400 : 200);
    }
    foreach ($networks as $k => $_) $v['social_' . $k] = post_str('social_' . $k, 500);
    $v['store']       = post_bool('store');
    // SMTP hâli: alan gönderilmediyse (eski form) mevcut hâl korunur; sayfayı SMTP alanlarına dokunmadan kaydetmek hâli değiştirmez
    $v['smtp_mode']   = in_array($_POST['smtp_mode'] ?? '', ['miras', 'panel', 'kapali'], true) ? (string) $_POST['smtp_mode'] : $smtpMode;
    $v['smtp_secure'] = isset($secures[$_POST['smtp_secure'] ?? '']) ? (string) $_POST['smtp_secure'] : 'ssl';
    $newPass          = is_string($_POST['smtp_pass'] ?? null) ? mb_substr((string) $_POST['smtp_pass'], 0, 200) : '';

    // Telefon: yazıldığı gibi gösterilir; yalnızca anlamlı olup olmadığına bakılır
    $digits = preg_replace('/\D+/', '', $v['phone']);
    if ($v['phone'] === '') $errors[] = 'Telefon numarasını yazın.';
    elseif (!preg_match('/^[0-9+()\s.\-]+$/', $v['phone']) || strlen($digits) < 10 || strlen($digits) > 15) $errors[] = 'Telefon numarası geçerli görünmüyor. Örnek: +90 554 808 97 71';

    // WhatsApp: boşluk ve artı işareti sessizce temizlenir
    $v['whatsapp'] = preg_replace('/[\s+\-()]+/', '', $v['whatsapp']);
    if ($v['whatsapp'] === '') $errors[] = 'WhatsApp numarasını yazın.';
    elseif (!preg_match('/^[1-9][0-9]{9,14}$/', $v['whatsapp'])) $errors[] = 'WhatsApp numarası yalnızca rakamlardan oluşmalı ve ülke koduyla başlamalıdır. Örnek: 905548089771 (başında 0 ya da + olmadan).';

    // E-posta adresleri başlıklara ve gönderim komutlarına girer: tırnak, boşluk, ters eğik çizgi ya da satır sonu içeren adres kabul edilmez
    if (!mail_address_ok($v['email'])) $errors[] = 'İletişim e-posta adresi geçerli değil.';
    if ($v['address'] === '') $errors[] = 'Açık adresi yazın.';
    if ($v['address_short'] === '') $errors[] = 'Kısa adresi yazın. Örnek: Pendik, İstanbul';
    if (!ay_url_ok($v['maps_url'])) $errors[] = 'Google Haritalar bağlantısı https:// ile başlayan geçerli bir adres olmalı.';
    if ($v['name'] === '') $errors[] = 'Şirket adını yazın.';
    if ($v['authorized'] === '') $errors[] = 'Yetkili kişiyi yazın.';
    if ($v['tax_office'] === '') $errors[] = 'Vergi dairesini yazın.';
    if ($v['tax_number'] === '') $errors[] = 'Vergi numarasını yazın.';
    foreach ($networks as $k => [$label]) {
        $u = $v['social_' . $k];
        if ($u !== '' && !ay_url_ok($u)) $errors[] = $label . ' bağlantısı https:// ile başlayan geçerli bir adres olmalı. Göstermek istemiyorsanız alanı boş bırakın.';
    }
    if (!mail_address_ok($v['mail_to'])) $errors[] = 'Bildirimlerin gideceği e-posta adresi geçerli değil.';
    if (!mail_address_ok($v['mail_from'])) $errors[] = 'Gönderen e-posta adresi geçerli değil.';
    if ($v['mail_from_name'] === '') $errors[] = 'Gönderen adını yazın. Örnek: Arslanlı Web Sitesi';
    $port = (int) $v['smtp_port'];
    foreach (['name' => 'Şirket adı', 'address' => 'Açık adres', 'address_short' => 'Kısa adres', 'authorized' => 'Yetkili kişi', 'tax_office' => 'Vergi dairesi',
        'tax_number' => 'Vergi numarası', 'mail_from_name' => 'Gönderen adı', 'smtp_user' => 'SMTP kullanıcı adı'] as $k => $label) {
        if (val_markup($v[$k])) $errors[] = val_markup_error('', $label);   // düz metin alanları: HTML işareti yazılamaz
    }
    if ($v['smtp_mode'] === 'panel') {
        if ($v['smtp_host'] === '' || !preg_match('/^[A-Za-z0-9.\-]+$/', $v['smtp_host'])) $errors[] = 'SMTP sunucu adını yazın. Örnek: mail.siteniz.com';
        if (!ctype_digit($v['smtp_port']) || $port < 1 || $port > 65535) $errors[] = 'SMTP port numarası 1 ile 65535 arasında bir sayı olmalı.';
        // Kayıtlı şifre başka bir sunucuya ya da hesaba kendiliğinden gönderilmez: bağlantı bilgileri değiştiyse (ya da SMTP yeni açılıyorsa) şifre yeniden yazılır
        // Karşılaştırma panelde kayıtlı bilgiyle yapılır: yapılandırma dosyasından gelen bilgi ve şifre panele taşınmaz, şifre yeniden yazılır
        $smtpChanged = $smtpPanel === null || $v['smtp_host'] !== $smtpPanel['host'] || $port !== $smtpPanel['port']
            || $v['smtp_secure'] !== $smtpPanel['secure'] || $v['smtp_user'] !== $smtpPanel['user'];
        if ($smtpChanged && $newPass === '' && $v['smtp_user'] !== '') $errors[] = 'SMTP sunucusu, portu, güvenlik türü ya da kullanıcı adı değiştiğinde şifreyi yeniden yazın.';
    }

    if (!$errors) {
        $s = content_get('settings', []);
        if (!is_array($s)) $s = [];
        foreach (['name', 'phone', 'whatsapp', 'email', 'address', 'address_short', 'maps_url'] as $k) $s[$k] = $v[$k];
        $s['store_submissions'] = $v['store'];
        $s['company'] = ['authorized' => $v['authorized'], 'tax_office' => $v['tax_office'], 'tax_number' => $v['tax_number']];
        $s['social'] = [];
        foreach ($networks as $k => $_) $s['social'][$k] = $v['social_' . $k];
        $s['mail'] = is_array($s['mail'] ?? null) ? $s['mail'] : [];
        $s['mail']['to'] = $v['mail_to'];
        $s['mail']['from'] = $v['mail_from'];
        $s['mail']['from_name'] = $v['mail_from_name'];
        if ($v['smtp_mode'] === 'panel') {
            $s['mail']['smtp'] = [
                'host'   => $v['smtp_host'],
                'port'   => $port,
                'secure' => $v['smtp_secure'],
                'user'   => $v['smtp_user'],
                'pass'   => $newPass !== '' ? $newPass : $storedPass,
            ];
        } elseif ($v['smtp_mode'] === 'kapali') {
            $s['mail']['smtp'] = null;       // açıkça kapalı: yerel dosyadaki SMTP de yok sayılır
        } else {
            unset($s['mail']['smtp']);       // seçim yok: yapılandırma dosyası ne diyorsa o; içerik deposuna SMTP bilgisi ve şifre yazılmaz
        }
        if (content_put('settings', $s)) {
            adm_flash('Ayarlar kaydedildi.');
            adm_go('ayarlar');
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

/* ---------- Sayfa ---------- */
$t = fn(string $k, string $label, array $o = []) => ui_text($k, $label, $v[$k], $o);

$waHref = $v['whatsapp'] !== '' ? 'https://wa.me/' . rawurlencode($v['whatsapp']) : '#';
$statusNow = $smtpNow ? 'SMTP ile (' . $smtpNow['host'] . ')' : 'sunucunun e-posta fonksiyonu';
$smtpWhere = ['miras' => 'Kaynak: yapılandırma dosyası (storage/config.local.php); panelde SMTP için bir seçim kaydedilmemiş.', 'panel' => 'Kaynak: panelde kayıtlı SMTP ayarları.',
    'kapali' => 'Kaynak: panelde SMTP kapatılmış; yapılandırma dosyasında SMTP ayarı olsa bile kullanılmaz.'][$smtpMode];
$smtpModes = ['miras' => 'Yapılandırma dosyasından (panelde ayar yok)', 'panel' => 'Panelde ayarla', 'kapali' => 'Kapalı: sunucunun kendi e-posta fonksiyonu'];
$smtpInh   = $smtpSt['inherited'];

ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong> ' . implode(' ', array_map('e', $errors)));
?>
<div class="ay">
  <nav class="chips ay__nav" aria-label="Bölümler">
    <a class="chip" href="#iletisim">İletişim</a>
    <a class="chip" href="#sirket">Şirket</a>
    <a class="chip" href="#sosyal">Sosyal medya</a>
    <a class="chip" href="#eposta">E-posta</a>
  </nav>

  <form id="settings-form" method="post" action="<?= adm_url('ayarlar') ?>" class="ay__form" novalidate>
    <?= adm_csrf_field() ?>

    <?= ui_card('İletişim', implode('', [
        '<div class="grid2">',
        $t('phone', 'Telefon', ['required' => true, 'maxlength' => 40, 'autocomplete' => 'off', 'placeholder' => '+90 554 808 97 71', 'help' => 'Sitede yazdığınız gibi görünür. Arama bağlantısı otomatik oluşturulur.']),
        ui_text('whatsapp', 'WhatsApp numarası', $v['whatsapp'], ['required' => true, 'maxlength' => 20, 'inputmode' => 'numeric', 'placeholder' => '905548089771', 'help' => 'Ülke koduyla ve yalnızca rakamlarla yazın, örneğin 905xxxxxxxxx. Sitedeki WhatsApp düğmesi bu numaraya yönlenir. <a class="ay__try" href="' . e($waHref) . '" target="_blank" rel="noopener" data-wa-try>WhatsApp\'ta dene</a>']),
        '</div>',
        '<div class="grid2">',
        $t('email', 'E-posta', ['type' => 'email', 'required' => true, 'maxlength' => 120, 'help' => 'Ziyaretçilerin görüp yazacağı adres: altbilgide ve iletişim sayfasında görünür.']),
        $t('address_short', 'Kısa adres', ['required' => true, 'maxlength' => 80, 'placeholder' => 'Pendik, İstanbul', 'help' => 'Fihristin altında ve "bulunamadı" sayfasında görünen kısa konum.']),
        '</div>',
        ui_textarea('address', 'Açık adres', $v['address'], ['required' => true, 'rows' => 2, 'maxlength' => 400, 'help' => 'Altbilgide ve iletişim sayfasında görünür.']),
        $t('maps_url', 'Google Haritalar bağlantısı', ['type' => 'url', 'required' => true, 'maxlength' => 500, 'placeholder' => 'https://www.google.com/maps/...', 'help' => 'Adrese tıklayanların açacağı harita. https:// ile başlamalıdır. Haritada konumu bulup "Paylaş" bölümünden bağlantıyı kopyalayabilirsiniz.']),
    ]), ['id' => 'iletisim', 'desc' => 'Altbilgi, iletişim sayfası ve WhatsApp düğmesi bu bilgilerden beslenir.']) ?>

    <?= ui_card('Şirket', implode('', [
        $t('name', 'Şirket adı', ['required' => true, 'maxlength' => 120, 'help' => 'Sayfa başlıklarında, altbilgide ve arama motoru bilgilerinde kullanılır.']),
        '<div class="grid3">',
        $t('authorized', 'Yetkili kişi', ['required' => true, 'maxlength' => 120]),
        $t('tax_office', 'Vergi dairesi', ['required' => true, 'maxlength' => 80]),
        $t('tax_number', 'Vergi numarası', ['required' => true, 'maxlength' => 30, 'inputmode' => 'numeric']),
        '</div>',
    ]), ['id' => 'sirket', 'desc' => 'Hakkımızda sayfasındaki şirket bilgileri bölümünde, kaşede ve KVKK metninde görünür.']) ?>

    <?php
    $soc = '';
    foreach ($networks as $k => [$label, $ph]) {
        $soc .= ui_text('social_' . $k, $label, $v['social_' . $k], ['type' => 'url', 'maxlength' => 500, 'placeholder' => $ph]);
    }
    echo ui_card('Sosyal medya', '<div class="grid2">' . $soc . '</div><p class="fld__help">Boş bıraktığınız ağ sitede görünmez. Bağlantılar https:// ile başlamalıdır.</p>', [
        'id' => 'sosyal', 'desc' => 'Altbilgideki bağlantılar ve arama motorlarına verilen kurum bilgisi. Sitenin simgesi olan ağlar listelenir.',
    ]);
    ?>

    <?= ui_card('E-posta', implode('', [
        '<p class="ay__status">' . ui_icon($smtpNow ? 'check-circle' : 'envelope-simple') . '<span>Şu an: <strong>' . e($statusNow) . '</strong> gönderiliyor. ' . e($smtpWhere) . '</span></p>',
        '<div class="grid2">',
        $t('mail_to', 'Bildirimlerin gideceği adres', ['type' => 'email', 'required' => true, 'maxlength' => 120, 'help' => 'Sitedeki formlar doldurulunca haber veren e-posta buraya gelir.']),
        $t('mail_from', 'Gönderen adres', ['type' => 'email', 'required' => true, 'maxlength' => 120, 'help' => 'Sitenin kendi alan adından bir adres olmalı (örneğin noreply@siteniz.com); aksi halde iletiler spam sayılabilir.']),
        '</div>',
        $t('mail_from_name', 'Gönderen adı', ['required' => true, 'maxlength' => 80, 'help' => 'Bildirimlerde gönderen olarak görünür.']),
        ui_toggle('store', 'Form kayıtlarını panelde de sakla', $v['store'], ['help' => 'Açıkken her form gönderimi "Form kayıtları" bölümünde de durur; e-posta ulaşmasa bile kayıp olmaz. İş başvuruları bu seçimden bağımsız olarak her zaman saklanır.']),
        '<div class="ay__smtp">',
        ui_select('smtp_mode', 'SMTP (e-postayı hesabınızla gönderme)', $smtpModes, $v['smtp_mode'], ['help' => 'SMTP ile e-postalar daha güvenilir ulaşır; bilgileri e-posta hizmetinizden (barındırma firmanız) alabilirsiniz. “Yapılandırma dosyasından” seçiliyken sunucudaki storage/config.local.php ne diyorsa o geçerlidir ve şifre hiçbir zaman panele ya da içerik deposuna kopyalanmaz.']),
        '<p class="fld__help" data-smtp-inherit' . ($v['smtp_mode'] === 'miras' ? '' : ' hidden') . '>' . ($smtpInh ? 'Yapılandırma dosyasındaki ayar: <strong>' . e($smtpInh['host'] . ':' . $smtpInh['port']) . '</strong>, ' . e($smtpInh['secure']) . ($smtpInh['user'] !== '' ? ', kullanıcı ' . e($smtpInh['user']) : '') . '. Şifre dosyada kalır; burada gösterilmez.' : 'Yapılandırma dosyasında SMTP ayarı yok; sunucunun kendi e-posta fonksiyonu kullanılır.') . '</p>',
        '<div class="ay__smtp-fields" data-smtp-fields' . ($v['smtp_mode'] === 'panel' ? '' : ' hidden') . '>',
        '<div class="grid2">',
        $t('smtp_host', 'Sunucu', ['maxlength' => 120, 'placeholder' => 'mail.siteniz.com', 'autocomplete' => 'off']),
        $t('smtp_port', 'Port', ['maxlength' => 5, 'inputmode' => 'numeric', 'autocomplete' => 'off']),
        '</div>',
        ui_select('smtp_secure', 'Güvenlik', $secures, $v['smtp_secure'], ['help' => 'Bilmiyorsanız SSL ve 465 genellikle çalışır.']),
        '<div class="grid2">',
        $t('smtp_user', 'Kullanıcı adı', ['maxlength' => 160, 'autocomplete' => 'off', 'help' => 'Çoğunlukla tam e-posta adresidir.']),
        ui_text('smtp_pass', 'Şifre', '', ['type' => 'password', 'maxlength' => 200, 'autocomplete' => 'new-password', 'placeholder' => $storedPass !== '' ? 'Kayıtlı şifre korunuyor' : '', 'help' => ($storedPass !== '' ? 'Değiştirmek istemiyorsanız boş bırakın. ' : '') . 'Panelden yazdığınız şifre sitenin içerik deposunda (storage/content/settings.json) düz metin olarak durur; yedek dosyasına ve değişiklik geçmişi sürümlerine girmez (geçmiş sürümlerinden silinir). Önceki bir sürüme ya da yedeğe dönerseniz, bağlantı bilgisi aynıysa şu anki şifre korunur; değilse SMTP ayarı geri yüklenmez ve şifreyi yeniden yazmanız gerekir. Daha güvenlisi, şifreyi sunucudaki storage/config.local.php dosyasına yazıp yukarıda “Yapılandırma dosyasından”ı seçmektir.']),
        '</div>',
        '</div></div>',
    ]), ['id' => 'eposta', 'desc' => 'Sitedeki formlardan gelen iletilerin nereye ve nasıl gideceği.']) ?>
  </form>

  <?= ui_card('Deneme e-postası gönder', '<form method="post" action="' . adm_url('ayarlar/deneme') . '" class="ay__test">' . adm_csrf_field()
      . '<p class="muted">Kayıtlı ayarlarla <strong>' . e($v['mail_to']) . '</strong> adresine kısa bir deneme iletisi gider. Az önce değiştirdiğiniz bilgiler varsa önce kaydedin.</p>'
      . '<div><button class="btn btn--ghost" type="submit">' . ui_icon('envelope-simple') . 'Deneme e-postası gönder</button></div></form>', [
      'id' => 'deneme', 'desc' => 'Formlardan gelen iletilerin gerçekten ulaşıp ulaşmadığını görmenin en hızlı yolu.',
  ]) ?>
</div>
<script>
(function () {
  var t = document.getElementById('f-smtp-mode'), box = document.querySelector('[data-smtp-fields]'), inh = document.querySelector('[data-smtp-inherit]');
  if (t && box) t.addEventListener('change', function () { box.hidden = t.value !== 'panel'; if (inh) inh.hidden = t.value !== 'miras'; });
  var wa = document.getElementById('f-whatsapp'), a = document.querySelector('[data-wa-try]');
  if (wa && a) wa.addEventListener('input', function () {
    var n = wa.value.replace(/[\s+\-()]+/g, '');
    a.href = n ? 'https://wa.me/' + encodeURIComponent(n) : '#';
  });
})();
</script>
<?php
adm_layout('İletişim ve şirket', (string) ob_get_clean(), [
    'section'  => 'ayarlar',
    'subtitle' => 'Sitenin altbilgisinde, iletişim sayfasında ve şema verilerinde görünen bilgiler.',
    'actions'  => ui_view_link(url('iletisim')) . ui_history_link('settings'),
    'form'     => 'settings-form',
]);
