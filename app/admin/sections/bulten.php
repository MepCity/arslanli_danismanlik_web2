<?php
/**
 * Bülten: aboneler (süzgeç, kopyalama, dışa aktarma), e-posta yazma, gönderim ve geçmiş.
 * Mantık: app/bulten.php (aboneler, alıcı listesi, e-posta, gönderim motoru, saatlik sınır).
 *
 *   /yonetim/bulten                             aboneler (GET: il[], sektor[], imalat, durum, q, sayfa)
 *   /yonetim/bulten/csv                         süzülen listenin CSV dosyası
 *   /yonetim/bulten/cikar               (POST)  bir adresi abonelikten çıkarır
 *   /yonetim/bulten/yeniden-abone       (POST)  abonelikten ayrılmış bir adresi yeniden abone yapar (ayrılma kalıcıdır; yalnızca buradan geri açılır)
 *   /yonetim/bulten/engel-kaldir        (POST)  kaydı silinmiş bir kişinin "bir daha e-posta alma" kaydını (anahtarlı özet) adresi yazılarak kaldırır
 *   /yonetim/bulten/yeni                        e-posta yazma ekranı (POST: taslağı kaydet ya da gönderimi başlat)
 *   /yonetim/bulten/yeni/{say|onizle|deneme}    (POST, JSON) alıcı sayısı, önizleme, deneme e-postası
 *   /yonetim/bulten/gonderimler                 taslaklar, gönderim geçmişi, saatlik sınır
 *   /yonetim/bulten/gonderimler/{id}            taslaksa yazma ekranı, değilse gönderim sayfası (ilerleme ve ayrıntı)
 *   /yonetim/bulten/gonderimler/{id}/{gonder|duraklat|surdur|yeniden|sil}   (POST; gonder JSON döner)
 *   /yonetim/bulten/ayar                (POST)  saatlik gönderim sınırı
 */

require_once APP . '/bulten.php';
require_once APP . '/mailer.php';

$yol      = mail_transport();          // 'smtp' | 'mail' | '' (gönderim mümkün değil)
$durumlar = bulten_durumlar();
$durumAd  = bulten_durum_adlari();
$a0       = (string) ($rest[0] ?? '');
$a1       = (string) ($rest[1] ?? '');
$a2       = (string) ($rest[2] ?? '');
$num      = fn(int $x): string => number_format($x, 0, ',', '.');
$zaman    = fn($t): string => ($t = is_int($t) ? $t : (int) strtotime((string) $t)) > 0 ? date('d.m.Y H:i', $t) : '';
$json     = function (array $d): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, bulten_json_flags());
    exit;
};
$yolYok   = 'E-posta gönderilemiyor: sunucuda e-posta fonksiyonu kapalı ve SMTP ayarlı değil. İletişim ve şirket bölümünden SMTP bilgilerini girin.';
$uyari    = $yol === ''
    ? ui_alert('<strong>Şu an e-posta gönderilemiyor.</strong> Sunucuda e-posta fonksiyonu (mail) kapalı ve SMTP ayarlı değil. Gönderim ve deneme düğmeleri, <a href="'
        . adm_url('ayarlar') . '#eposta">İletişim ve şirket</a> bölümündeki E-posta kartından SMTP bilgileri girilene kadar kapalıdır. Abone listesi, dışa aktarma ve taslak hazırlama çalışır.', 'warn')
    : '';
// Görünürlük anahtarı kapalıyken form ve sayfalar sitede yoktur; abone listesi ve gönderim sürer, e-postadaki ayrılma bağlantısı da çalışır
if (!feature('bulten')) {
    $uyari .= '<div class="alert alert--off" role="status">' . ui_icon('eye-slash') . '<div><strong>Bülten kaydı şu an sitede kapalı.</strong> Ziyaretçiler bülten formunu görmüyor, yeni abone gelmiyor. Mevcut aboneler ve gönderimler çalışmaya devam eder; e-postalardaki abonelikten ayrılma bağlantısı da açıktır. <a href="'
        . adm_url('gorunurluk') . '">Görünürlük ayarından açın</a></div></div>';
}

/** Bir gönderimin tahmini süresi (saatlik sınıra göre). */
$sure = function (int $n): string {
    $sinir = bulten_ayarlar()['saatlik'];
    if ($n <= $sinir) {
        return 'Saatte en fazla ' . $sinir . ' e-posta gönderilir; bu gönderim birkaç dakika sürer.';
    }
    return 'Saatte en fazla ' . $sinir . ' e-posta gönderilir; bu gönderim yaklaşık ' . (int) floor(($n - 1) / $sinir) . ' saat sürer ve bu sürede gönderim sayfası açık kalmalıdır.';
};

/** Yazma ekranından gelen alanlar. */
$girdi = function (): array {
    $f = bulten_suzgec($_POST);
    $f['durum'] = '';
    $id = post_str('id', 12);
    return ['id' => bulten_id_gecerli($id) ? $id : '', 'konu' => bulten_satir(post_str('konu', 1000), 1000), 'govde' => post_str('govde', BULTEN_GOVDE_MAX + 1), 'f' => $f];
};

/** Önizleme ve deneme için örnek alıcı: listedeki ilk abonenin adı; adres olarak $adres (gerçek abonenin ayrılma bağlantısı panelde dolaşmaz). */
$ornek = function (array $f, string $adres): array {
    $ilk = bulten_alicilar($f)[0] ?? ['ad' => 'Ahmet', 'soyad' => 'Yılmaz'];
    return ['email' => $adres, 'ad' => (string) $ilk['ad'], 'soyad' => (string) $ilk['soyad']];
};

/* =========================================================================
   İşlemler
   ========================================================================= */

/** İşlemden sonra dönülecek liste adresi (süzgeç ve sayfa): yalnızca "?" ile başlayan sorgu kabul edilir. */
$geriAl = function (): string {
    $geri = post_str('geri', 3000);
    return $geri !== '' && $geri[0] === '?' && !preg_match('/[\x00-\x1F]/', $geri) ? $geri : '';
};

/* ---------- Abonelikten çıkar ---------- */
if ($a0 === 'cikar' && $method === 'POST') {
    $email = strtolower(trim(post_str('email', 254)));
    $geri  = $geriAl();
    if (!in_array($email, array_column(bulten_aboneler(), 'email'), true)) {
        adm_flash('Abone bulunamadı.', 'err');
    } else {
        try {
            adm_flash(bulten_ayril($email, 'yönetim panelinden çıkarıldı') ? 'Adres abonelikten çıkarıldı; artık bülten e-postası gönderilmez.' : 'Bu adres zaten abonelikten çıkmış.');
        } catch (Throwable $e) {
            adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
        }
    }
    adm_go('bulten' . $geri);
}

/* ---------- Kaydı silinmiş kişinin engel kaydını kaldır (kişi bunu açıkça istediğinde; adres günlüğe yazılmaz) ---------- */
if ($a0 === 'engel-kaldir' && $method === 'POST') {
    $email = strtolower(trim(post_str('email', 254)));
    if ($email === '' || !mail_address_ok($email)) {
        adm_flash('Geçerli bir e-posta adresi yazın.', 'err');
    } else {
        try {
            adm_flash(bulten_engel_kaldir($email) ? 'Bu adresin “bir daha e-posta alma” kaydı silindi. Adres aynı formla yeniden kayıt olursa abone sayılır ve bülten alır.' : 'Bu adres için silinmiş kişi kaydı (engel listesi) bulunamadı. Adresi hâlâ listede görünen bir abone için “Yeniden abone yap” kullanılır.');
        } catch (Throwable $e) {
            adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
        }
    }
    adm_go('bulten');
}

/* ---------- Ayrılmış adresi yeniden abone yap (ayrılma kalıcıdır; sitedeki form aboneliği kendiliğinden geri açmaz) ---------- */
if ($a0 === 'yeniden-abone' && $method === 'POST') {
    $email = strtolower(trim(post_str('email', 254)));
    $geri  = $geriAl();
    if ((array_column(bulten_aboneler(), 'durum', 'email')[$email] ?? '') !== 'ayrildi') {
        adm_flash('Abonelikten ayrılmış böyle bir adres bulunamadı.', 'err');
    } else {
        try {
            if (!bulten_yeniden_abone($email)) {
                adm_flash('Bu adres zaten abone.');
            } elseif ((array_column(bulten_aboneler(), 'durum', 'email')[$email] ?? '') === 'onayli') {
                adm_flash('Adres yeniden abone yapıldı; bülten e-postaları yeniden gönderilir.');
            } else {
                adm_flash('Adres ayrılanlar listesinden çıkarıldı. İleti onayı içeren bir kaydı olmadığı için e-posta gönderilmez.');
            }
        } catch (Throwable $e) {
            adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
        }
    }
    adm_go('bulten' . $geri);
}

/* ---------- Saatlik gönderim sınırı ---------- */
if ($a0 === 'ayar' && $method === 'POST') {
    $v = post_str('saatlik', 8);
    $n = ctype_digit($v) ? (int) $v : 0;
    if ($n < BULTEN_SAATLIK_MIN || $n > BULTEN_SAATLIK_MAX) {
        adm_flash('Saatlik sınır ' . BULTEN_SAATLIK_MIN . ' ile ' . BULTEN_SAATLIK_MAX . ' arasında bir sayı olmalı.', 'err');
    } else {
        $eski = bulten_ayarlar()['saatlik'];
        try {
            $ok = bulten_ayar_kaydet($n);
        } catch (Throwable $e) {
            $ok = false;
        }
        if ($ok && $eski !== $n) {
            changelog_event('bulten', 'Bülten saatlik gönderim sınırı ' . $eski . ' yerine ' . $n . ' oldu');
        }
        $ok ? adm_flash('Saatlik gönderim sınırı kaydedildi: saatte en fazla ' . $n . ' e-posta.') : adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
    }
    adm_go('bulten/gonderimler#sinir');
}

/* ---------- Yazma ekranının yardımcı uçları (JSON): alıcı sayısı, önizleme, deneme e-postası ---------- */
if ($a0 === 'yeni' && $a1 !== '') {
    if ($method !== 'POST' || !in_array($a1, ['say', 'onizle', 'deneme'], true)) {
        adm_go('bulten/yeni');
    }
    $in = $girdi();
    if ($a1 === 'say') {
        $n = count(bulten_alicilar($in['f']));
        $json(['ok' => true, 'n' => $n, 'ozet' => bulten_suzgec_metni($in['f']), 'sure' => $sure($n), 'liste' => adm_url('bulten') . bulten_suzgec_sorgu(['durum' => 'onayli'] + $in['f'])]);
    }
    [$konu, $html, $hata] = bulten_icerik($in['konu'], $in['govde']);
    if ($hata) {
        $json(['ok' => false, 'hata' => implode(' ', $hata)]);
    }
    $ileti = ['subject' => $konu, 'html' => $html, 'text' => bulten_duz_metin($html)];
    if ($a1 === 'onizle') {
        $kime = $ornek($in['f'], 'ornek@firma.com');
        $m = bulten_eposta($ileti, $kime);
        $json(['ok' => true, 'konu' => $m['konu'], 'html' => $m['html'], 'metin' => $m['metin'], 'kime' => trim($kime['ad'] . ' ' . $kime['soyad'])]);
    }
    // Deneme e-postası: yöneticinin yazdığı adrese tek kopya; saatlik sınıra sayılır
    $adres = strtolower(trim(post_str('deneme_adres', 254)));
    if ($yol === '') {
        $json(['ok' => false, 'hata' => $yolYok]);
    }
    if (!mail_address_ok($adres)) {
        $json(['ok' => false, 'hata' => 'Deneme e-postasının gideceği adres geçerli değil.']);
    }
    if (!bulten_anahtar_hazir()) {
        $json(['ok' => false, 'hata' => 'Siteye özel form güvenlik anahtarı yok; e-postadaki abonelikten ayrılma bağlantısı çalışmaz. Güvenlik ve yedek bölümünden yeni anahtar oluşturun.']);
    }
    if (($_SESSION['bl_deneme_at'] ?? 0) > time() - 10) {
        $json(['ok' => false, 'hata' => 'Az önce bir deneme e-postası gönderildi. Birkaç saniye bekleyip yeniden deneyin.']);
    }
    try {
        [$yer, $bekle] = bulten_saat_ayir(1);
        if ($yer === 0) {
            $json(['ok' => false, 'hata' => 'Saatlik gönderim sınırı dolu. Yaklaşık ' . (int) ceil($bekle / 60) . ' dakika sonra yeniden deneyin.']);
        }
        $_SESSION['bl_deneme_at'] = time();
        bulten_imza_arsivle();
        $ok = bulten_gonder_tek($ileti, $ornek($in['f'], $adres), true);
        if (!$ok) {
            bulten_saat_iade(1);
        }
    } catch (Throwable $e) {
        $json(['ok' => false, 'hata' => 'Gönderilemedi: storage klasörü yazılabilir mi?']);
    }
    $json($ok
        ? ['ok' => true, 'mesaj' => 'Deneme e-postası ' . $adres . ' adresine gönderildi. Birkaç dakika içinde gelen kutusuna ya da spam klasörüne düşmelidir.']
        : ['ok' => false, 'hata' => 'Deneme e-postası gönderilemedi' . (mail_last_error() !== '' ? ' (' . mail_last_error() . ')' : '') . '. E-posta ayarlarını İletişim ve şirket bölümünden kontrol edin.']);
}

/* ---------- Gönderim işlemleri: parti gönder, duraklat, sürdür, hataları yeniden dene, sil ---------- */
if ($a0 === 'gonderimler' && $a2 !== '') {
    if ($method !== 'POST' || !bulten_id_gecerli($a1)) {
        adm_go('bulten/gonderimler' . (bulten_id_gecerli($a1) ? '/' . $a1 : ''));
    }
    if ($a2 === 'gonder') {
        // Oturum kilidi bırakılır: parti sürerken panelin diğer sayfaları (ve Duraklat düğmesi) beklemesin.
        // Aynı alıcıya iki kez gönderilmesini oturum değil, gönderimin kendi kilidi önler (bkz. bulten_parti).
        session_write_close();
        try {
            $json(bulten_parti($a1));
        } catch (Throwable $e) {
            error_log('[bulten] parti: ' . $e->getMessage());
            $json(['ok' => false, 'hata' => 'Gönderim sürdürülemedi: storage klasörü yazılabilir mi?']);
        }
    }
    if ($a2 === 'sil') {
        try {
            $s = bulten_sil($a1);
        } catch (Throwable $e) {
            $s = null;
        }
        $s ? adm_flash($s['state'] === 'taslak' ? 'Taslak silindi.' : 'Gönderim kaydı silindi.') : adm_flash('Silinemedi: gönderim sürüyor olabilir. Önce duraklatın.', 'err');
        adm_go('bulten/gonderimler' . ($s ? '' : '/' . $a1));
    }
    if (in_array($a2, ['duraklat', 'surdur', 'yeniden'], true)) {
        if ($a2 !== 'duraklat' && $yol === '') {
            adm_flash($yolYok, 'err');
            adm_go('bulten/gonderimler/' . $a1);
        }
        try {
            $r = bulten_durum_degistir($a1, $a2);
        } catch (Throwable $e) {
            $r = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
        }
        if (is_array($r)) {
            adm_flash(['duraklat' => 'Gönderim duraklatıldı. Sürdür düğmesiyle kaldığı yerden devam eder.', 'surdur' => 'Gönderim sürdürülüyor.', 'yeniden' => 'Hata alan alıcılar yeniden sıraya alındı.'][$a2]);
        } else {
            adm_flash($r, 'err');
        }
    }
    adm_go('bulten/gonderimler/' . $a1);
}

/* ---------- Taslağı kaydet ya da gönderimi başlat ---------- */
$yaz = null;   // yazma ekranı gösterilecekse: ['id', 'konu', 'govde', 'f', 'hatalar', 'taslak']
if ($a0 === 'yeni' && $method === 'POST') {
    $in = $girdi();
    if ($in['id'] === '') {
        adm_flash('Form eksik gönderildi. Sayfayı yenileyip yeniden deneyin.', 'err');
        adm_go('bulten/yeni');
    }
    $baslat = post_str('islem', 20) === 'baslat';
    [$konu, $html, $hatalar] = bulten_icerik($in['konu'], $in['govde'], !$baslat);
    if ($baslat && $yol === '') {
        $hatalar[] = $yolYok;
    }
    $beklenen = post_str('beklenen', 8);
    if ($baslat && !ctype_digit($beklenen)) {
        $hatalar[] = 'Alıcı sayısı doğrulanamadı. Sayıyı kontrol edip gönderimi yeniden başlatın.';
    }
    if (!$hatalar) {
        try {
            $r = $baslat ? bulten_baslat($in['id'], $konu, $html, $in['f'], (int) $beklenen) : bulten_taslak_kaydet($in['id'], $konu, $html, $in['f']);
        } catch (Throwable $e) {
            $r = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
        }
        if (is_array($r)) {
            adm_flash($baslat ? 'Gönderim başlatıldı: ' . count($r['recipients']) . ' alıcı. Bu sayfa açık kaldıkça gönderim sürer.' : 'Taslak kaydedildi.');
            adm_go('bulten/gonderimler/' . $in['id']);
        }
        $var = bulten_gonderim($in['id']);
        if ($var !== null && $var['state'] !== 'taslak') {
            adm_flash($r, 'err');     // çift tıklama ya da ikinci sekme: gönderim zaten var
            adm_go('bulten/gonderimler/' . $in['id']);
        }
        $hatalar[] = $r;
    }
    $eski = bulten_gonderim($in['id']);
    $yaz = ['id' => $in['id'], 'konu' => $in['konu'], 'govde' => bulten_govde_temizle($in['govde']), 'f' => $in['f'], 'hatalar' => $hatalar, 'taslak' => $eski !== null && $eski['state'] === 'taslak' ? $eski : null];
} elseif ($a0 === 'yeni') {
    $f = bulten_suzgec($_GET);
    $f['durum'] = '';
    $yaz = ['id' => bulten_yeni_id(), 'konu' => '', 'govde' => '', 'f' => $f, 'hatalar' => [], 'taslak' => null];
}

$gonderim = null;
if ($a0 === 'gonderimler' && $a1 !== '') {
    $gonderim = bulten_gonderim($a1);
    if ($gonderim === null) {
        adm_flash('Gönderim bulunamadı.', 'err');
        adm_go('bulten/gonderimler');
    }
    if ($gonderim['state'] === 'taslak') {
        $yaz = ['id' => $gonderim['id'], 'konu' => (string) $gonderim['subject'], 'govde' => (string) $gonderim['html'], 'f' => $gonderim['filter'], 'hatalar' => [], 'taslak' => $gonderim];
    }
}
if (!in_array($a0, ['', 'csv', 'yeni', 'gonderimler'], true)) {
    adm_go('bulten');
}

/* =========================================================================
   Ortak parçalar
   ========================================================================= */

$imalatVar = (bool) array_filter((array) site('sektorler'), fn($s) => bulten_imalat_mi((string) $s));

/**
 * Çoklu seçim kutusu: açılır bir liste içinde onay kutuları (JavaScript olmadan da çalışır).
 * $ek: listenin başındaki özel seçenekler [[alan adı, değer, etiket, seçili mi], ...]
 */
$coklu = function (string $ad, string $etiket, string $hepsi, array $secenekler, array $secili, array $sayilar = [], array $ek = []) use ($num): string {
    $ozet = [];
    $opts = '';
    foreach ($ek as [$n, $v, $l, $on]) {
        if ($on) $ozet[] = $l;
        $opts .= '<label class="bl-ms__opt bl-ms__opt--ek"><input type="checkbox" name="' . e($n) . '" value="' . e($v) . '" data-label="' . e($l) . '"' . ($on ? ' checked' : '') . '><span>' . e($l) . '</span></label>';
    }
    // Listeden sonradan kaldırılmış ama süzgeçte kalan değerler de görünür ki kaldırılabilsin
    $hepsiSecenek = array_merge(array_values($secenekler), array_values(array_diff($secili, $secenekler)));
    foreach ($hepsiSecenek as $s) {
        $on = in_array($s, $secili, true);
        if ($on) $ozet[] = (string) $s;
        $opts .= '<label class="bl-ms__opt"><input type="checkbox" name="' . e($ad) . '[]" value="' . e((string) $s) . '"' . ($on ? ' checked' : '') . '><span>' . e((string) $s) . '</span>'
            . (!empty($sayilar[$s]) ? '<em>' . $num((int) $sayilar[$s]) . '</em>' : '') . '</label>';
    }
    return '<details class="bl-ms' . ($ozet ? ' is-set' : '') . '" data-bl-ms data-bl-all="' . e($hepsi) . '">'
        . '<summary class="inp bl-ms__sum"><span class="bl-ms__label">' . e($etiket) . '</span><span class="bl-ms__val" data-bl-ms-val>' . e($ozet ? implode(', ', $ozet) : $hepsi) . '</span></summary>'
        . '<div class="bl-ms__pop">'
        . (count($hepsiSecenek) > 12 ? '<input class="inp bl-ms__find" type="search" placeholder="' . e($etiket) . ' ara" aria-label="' . e($etiket) . ' ara" data-bl-ms-find autocomplete="off">' : '')
        . '<div class="bl-ms__list">' . $opts . '</div>'
        . '<div class="bl-ms__foot"><button class="btn btn--ghost btn--sm" type="button" data-bl-ms-clear>Seçimi temizle</button><button class="btn btn--soft btn--sm" type="button" data-bl-ms-ok>Tamam</button></div>'
        . '</div></details>';
};

/** İl ve sektör kutuları. $kume: seçeneklerin yanındaki sayıların hesaplanacağı aboneler. */
$suzgecKutulari = function (array $f, array $kume) use ($coklu, $imalatVar): string {
    $sayIl = array_count_values(array_filter(array_column($kume, 'il'), fn($x) => $x !== ''));
    $saySek = array_count_values(array_filter(array_column($kume, 'sektor'), fn($x) => $x !== ''));
    $ek = $imalatVar || $f['imalat'] ? [['imalat', '1', BULTEN_IMALAT . ' (tümü)', $f['imalat']]] : [];
    return $coklu('il', 'İl', 'Tüm iller', (array) site('iller'), $f['il'], $sayIl)
        . $coklu('sektor', 'Sektör', 'Tüm sektörler', (array) site('sektorler'), $f['sektor'], $saySek, $ek);
};

$sekmeler = function (string $acik): string {
    $taslak = bulten_taslak_sayisi();
    $out = '';
    foreach (['' => 'Aboneler', 'gonderimler' => 'Gönderimler'] as $k => $ad) {
        $out .= '<a class="tab' . ($acik === $k ? ' is-on' : '') . '" href="' . adm_url('bulten' . ($k !== '' ? '/' . $k : '')) . '"' . ($acik === $k ? ' aria-current="page"' : '') . '>' . $ad
            . ($k === 'gonderimler' && $taslak ? ' <em class="bl-tab-n" title="' . $taslak . ' taslak">' . $taslak . '</em>' : '') . '</a>';
    }
    return '<nav class="tabs bl-tabs" aria-label="Bülten bölümleri">' . $out . '</nav>';
};

$kimRozeti = function ($w): string {
    $w = is_array($w) ? $w : [];
    if (($w['type'] ?? '') === 'mcp') {
        return '<span class="badge badge--navy">' . ui_icon('robot') . 'Yapay zekâ: ' . e((string) ($w['name'] ?? '')) . (($w['client'] ?? '') !== '' ? ' · ' . e((string) $w['client']) : '') . '</span>';
    }
    return '<span class="badge">' . ui_icon('desktop') . 'Yönetim paneli</span>';
};

$durumRozeti = function (string $state) use ($durumAd): string {
    $cls = ['taslak' => '', 'gonderiliyor' => ' badge--navy', 'duraklatildi' => ' badge--warn', 'tamamlandi' => ' badge--ok'][$state] ?? '';
    return '<span class="badge' . $cls . '">' . e($durumAd[$state] ?? $state) . '</span>';
};

/** Sayfalama satırı: $adres(sayfa) bağlantıyı üretir. */
$sayfalama = function (int $sayfa, int $toplam, callable $adres): string {
    if ($toplam <= 1) {
        return '';
    }
    return '<div class="bl-pager">'
        . ($sayfa > 1 ? '<a class="btn btn--ghost btn--sm" href="' . e($adres($sayfa - 1)) . '">Önceki</a>' : '<span></span>')
        . '<span class="muted">Sayfa ' . $sayfa . ' / ' . $toplam . '</span>'
        . ($sayfa < $toplam ? '<a class="btn btn--ghost btn--sm" href="' . e($adres($sayfa + 1)) . '">Sonraki</a>' : '<span></span>') . '</div>';
};

/* Çoklu seçim kutuları ve e-posta önizleme çerçevesi (bütün ekranlarda ortak) */
$jsOrtak = <<<'JS'
(function () {
  var d = document;
  var fold = function (s) { return String(s).toLocaleLowerCase('tr').replace(/[çğıöşü]/g, function (c) { return { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u' }[c]; }); };
  var all = [].slice.call(d.querySelectorAll('[data-bl-ms]'));
  all.forEach(function (ms) {
    var val = ms.querySelector('[data-bl-ms-val]');
    var boxes = function () { return [].slice.call(ms.querySelectorAll('input[type=checkbox]')); };
    var upd = function () {
      var on = boxes().filter(function (b) { return b.checked; }).map(function (b) { return b.getAttribute('data-label') || b.value; });
      val.textContent = on.length ? on.join(', ') : ms.getAttribute('data-bl-all');
      ms.classList.toggle('is-set', on.length > 0);
    };
    ms.addEventListener('change', upd);
    var find = ms.querySelector('[data-bl-ms-find]');
    var ara = function () {
      var q = fold(find.value.trim());
      [].forEach.call(ms.querySelectorAll('.bl-ms__opt'), function (o) { o.hidden = q !== '' && fold(o.textContent).indexOf(q) < 0; });
    };
    if (find) {
      find.addEventListener('input', ara);
      find.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
    }
    ms.querySelector('[data-bl-ms-clear]').addEventListener('click', function () {
      boxes().forEach(function (b) { b.checked = false; });
      upd();
      ms.dispatchEvent(new Event('change', { bubbles: true }));
    });
    ms.querySelector('[data-bl-ms-ok]').addEventListener('click', function () { ms.open = false; ms.querySelector('summary').focus(); });
    // Açılınca arama kutusuna odaklanır; kapanınca arama temizlenir (bir sonraki açılışta bütün seçenekler görünür)
    ms.addEventListener('toggle', function () {
      if (!find) return;
      if (ms.open) find.focus(); else if (find.value !== '') { find.value = ''; ara(); }
    });
  });
  d.addEventListener('click', function (e) { all.forEach(function (ms) { if (ms.open && !ms.contains(e.target)) ms.open = false; }); });
  d.addEventListener('keydown', function (e) { if (e.key === 'Escape') all.forEach(function (ms) { if (ms.open) { ms.open = false; ms.querySelector('summary').focus(); } }); });

  // Önizleme çerçevesi içeriğinin boyuna göre uzar (içindeki bağlantılar tıklanmaz)
  window.blFrame = function (f) {
    var fit = function () { try { f.style.height = Math.max(160, f.contentDocument.documentElement.scrollHeight + 4) + 'px'; } catch (e) {} };
    f.addEventListener('load', fit);
    fit();
  };
  [].forEach.call(d.querySelectorAll('[data-bl-frame][srcdoc]'), window.blFrame);
  [].forEach.call(d.querySelectorAll('details[data-bl-frame-wrap]'), function (w) {
    w.addEventListener('toggle', function () { var f = w.querySelector('[data-bl-frame]'); if (w.open && f) window.blFrame(f); });
  });
})();
JS;

/* =========================================================================
   E-posta yazma ekranı (yeni ya da taslak)
   ========================================================================= */
if ($yaz !== null) {
    $f        = $yaz['f'];
    $taslak   = $yaz['taslak'];
    $onayli   = array_values(array_filter(bulten_aboneler(), fn($a) => $a['durum'] === 'onayli'));
    $n        = count(bulten_alicilar($f));
    $deneme   = is_string($_POST['deneme_adres'] ?? null) ? post_str('deneme_adres', 254) : (string) cfg('mail.to');
    $kapali   = $yol === '' ? ' disabled title="SMTP ayarlanmadan e-posta gönderilemez"' : '';

    ob_start();
    echo $uyari;
    if ($yaz['hatalar']) {
        echo ui_alert('<strong>İşlem yapılamadı.</strong> ' . implode(' ', array_map('e', $yaz['hatalar'])));
    }
    if ($taslak && ($taslak['who']['type'] ?? '') === 'mcp') {
        echo ui_alert('Bu taslağı <strong>' . e((string) $taslak['who']['name']) . '</strong> yapay zekâ erişimiyle hazırladı (' . e($zaman($taslak['created'] ?? '')) . '). Metni ve alıcıları kontrol edin; gönderim yalnızca buradan, sizin onayınızla başlar.', 'info');
    }
    ?>
    <form id="bl-form" class="split bl-yaz" method="post" action="<?= adm_url('bulten/yeni') ?>" data-bl-yaz
          data-say="<?= adm_url('bulten/yeni/say') ?>" data-onizle="<?= adm_url('bulten/yeni/onizle') ?>" data-deneme="<?= adm_url('bulten/yeni/deneme') ?>">
      <?= adm_csrf_field() ?>
      <input type="hidden" name="id" value="<?= e($yaz['id']) ?>">
      <input type="hidden" name="beklenen" value="<?= $n ?>">
      <div style="display:grid;gap:20px;min-width:0">
        <?= ui_card('Alıcılar',
            '<div class="bl-filters" data-bl-filters>' . $suzgecKutulari($f, $onayli)
            . '<input class="inp bl-filters__q" type="search" name="q" value="' . e($f['q']) . '" placeholder="Ad, e-posta, sektör ya da konu ara" aria-label="Abonelerde ara" maxlength="120" autocomplete="off"></div>'
            . '<p class="bl-seg"><strong data-bl-n>' . $num($n) . '</strong> aboneye gönderilecek <span class="bl-seg__f">(<span data-bl-ozet>' . e(bulten_suzgec_metni($f)) . '</span>)</span> '
            . '<a href="' . e(adm_url('bulten') . bulten_suzgec_sorgu(['durum' => 'onayli'] + $f)) . '" target="_blank" rel="noopener" data-bl-liste>Listeyi gör</a></p>',
            ['desc' => 'Yalnızca durumu "Onaylı" olan aboneler alıcıdır: ileti onayı vermiş ve abonelikten ayrılmamış olanlar. Her adrese tek e-posta gider. Alıcı listesi, gönderimi başlattığınız anda kesinleşir.']) ?>

        <?= ui_card('E-posta',
            ui_text('konu', 'Konu', $yaz['konu'], ['maxlength' => BULTEN_KONU_MAX, 'counter' => true, 'placeholder' => 'Örn: KOSGEB Yeşil Sanayi çağrısı başvuruya açıldı'])
            . ui_rich('govde', 'Metin', $yaz['govde'], ['help' => 'Metinde <code>{ad}</code> ve <code>{soyad}</code> yazdığınız yere her alıcının kendi adı ve soyadı gelir; örneğin "Merhaba {ad} Bey/Hanım". '
                . 'Şirket adı, adres, telefon, e-posta, bu e-postanın neden gönderildiği ve abonelikten ayrılma bağlantısı her e-postanın altına kendiliğinden eklenir. Görsel eklenemez.'])) ?>

        <?= ui_card('Önizleme',
            '<div class="bl-prev__bar"><button class="btn btn--soft btn--sm" type="button" data-bl-preview>' . ui_icon('eye') . 'Önizlemeyi göster</button>'
            . '<span class="muted" data-bl-prev-kime hidden></span></div>'
            . '<div class="bl-prev" data-bl-prev hidden><p class="bl-prev__konu"><span>Konu</span><strong data-bl-prev-konu></strong></p>'
            . '<iframe class="bl-frame" sandbox="allow-same-origin" title="E-posta önizlemesi" data-bl-frame></iframe>'
            . '<details class="bl-prev__txt"><summary>Düz metin sürümü (HTML göstermeyen uygulamalar için)</summary><pre data-bl-prev-metin></pre></details></div>',
            ['id' => 'onizleme', 'desc' => 'Alıcının göreceği e-posta, altbilgisiyle birlikte. Metni değiştirdikten sonra önizlemeyi yenileyin.']) ?>
      </div>

      <aside class="split__side">
        <?= ui_card('Gönderim',
            '<p class="bl-send__n"><strong data-bl-n>' . $num($n) . '</strong><span>alıcı</span></p>'
            . '<p class="muted" data-bl-sure>' . e($sure($n)) . '</p>'
            . '<button class="btn btn--block" type="button" data-bl-start' . ($n === 0 || $yol === '' ? ' disabled' : '') . ($yol === '' ? ' data-kapali title="SMTP ayarlanmadan e-posta gönderilemez"' : '') . '>' . ui_icon('paper-plane-tilt') . 'Gönderimi başlat</button>'
            . '<button class="btn btn--ghost btn--block" type="submit">' . ui_icon('floppy-disk') . 'Taslağı kaydet</button>'
            . ($taslak ? '<button class="btn btn--danger btn--block" type="submit" form="bl-sil">' . ui_icon('trash') . 'Taslağı sil</button>' : '')
            . '<p class="bl-msg" data-bl-msg role="status" hidden></p>') ?>
        <?= ui_card('Deneme e-postası',
            ui_text('deneme_adres', 'Gideceği adres', $deneme, ['type' => 'email', 'maxlength' => 254, 'autocomplete' => 'email'])
            . '<button class="btn btn--ghost btn--block" type="button" data-bl-test' . $kapali . '>' . ui_icon('envelope-simple') . 'Kendime deneme gönder</button>'
            . '<p class="bl-msg" data-bl-test-msg role="status" hidden></p>',
            ['desc' => 'E-postanın bir kopyası yalnızca bu adrese gider; konunun başına [Deneme] yazılır.']) ?>
      </aside>
    </form>
    <?php if ($taslak): ?>
      <form id="bl-sil" method="post" action="<?= adm_url('bulten/gonderimler/' . $taslak['id'] . '/sil') ?>" data-confirm="Bu taslak silinsin mi? Geri alınamaz."><?= adm_csrf_field() ?></form>
    <?php endif; ?>
    <script><?= $jsOrtak ?></script>
    <script>
    (function () {
      var d = document, form = d.querySelector('[data-bl-yaz]');
      if (!form) return;
      var q = function (s) { return form.querySelector(s); };
      var nf = new Intl.NumberFormat('tr-TR');
      var bek = q('[name=beklenen]'), start = q('[data-bl-start]'), msg = q('[data-bl-msg]'), tmsg = q('[data-bl-test-msg]');
      var show = function (el, text, err) { el.textContent = text; el.hidden = !text; el.classList.toggle('is-err', !!err); };
      var sync = function () { var a = q('[data-rt-area]'), o = q('[data-rt-out]'); if (a && o) o.value = a.innerHTML; };
      var post = function (url) {
        sync();
        return fetch(url, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.text(); })
          .then(function (t) {
            try { return JSON.parse(t); } catch (e) { return { ok: false, hata: 'Oturumunuz kapanmış olabilir. Yazdıklarınızı kopyalayıp sayfayı yenileyin ve yeniden deneyin.' }; }
          }, function () { return { ok: false, hata: 'Sunucuya ulaşılamadı. Bağlantınızı kontrol edip yeniden deneyin.' }; });
      };

      // Alıcı sayısı: süzgeç değiştikçe sunucudan yeniden alınır
      var sira = 0;
      var say = function () {
        var no = ++sira;
        start.disabled = true;
        post(form.getAttribute('data-say')).then(function (j) {
          if (no !== sira) return;
          if (!j.ok) { show(msg, j.hata, true); return; }
          bek.value = j.n;
          [].forEach.call(form.querySelectorAll('[data-bl-n]'), function (el) { el.textContent = nf.format(j.n); });
          q('[data-bl-ozet]').textContent = j.ozet;
          q('[data-bl-sure]').textContent = j.sure;
          q('[data-bl-liste]').href = j.liste;
          start.disabled = j.n === 0 || start.hasAttribute('data-kapali');
        });
      };
      var box = q('[data-bl-filters]'), t = null;
      box.addEventListener('change', say);
      box.querySelector('[name=q]').addEventListener('input', function () { clearTimeout(t); t = setTimeout(say, 350); });
      box.querySelector('[name=q]').addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(t); say(); } });

      // Önizleme: sunucuda hazırlanan e-posta, altbilgisiyle
      var pv = q('[data-bl-preview]'), frame = q('[data-bl-frame]');
      pv.addEventListener('click', function () {
        pv.disabled = true;
        post(form.getAttribute('data-onizle')).then(function (j) {
          pv.disabled = false;
          var kime = q('[data-bl-prev-kime]');
          if (!j.ok) { q('[data-bl-prev]').hidden = true; kime.hidden = false; kime.textContent = j.hata; kime.classList.add('bl-err'); return; }
          kime.classList.remove('bl-err');
          kime.hidden = false;
          kime.textContent = 'Örnek alıcı: ' + j.kime;
          q('[data-bl-prev-konu]').textContent = j.konu;
          q('[data-bl-prev-metin]').textContent = j.metin;
          q('[data-bl-prev]').hidden = false;
          frame.srcdoc = j.html;
          window.blFrame(frame);
          pv.lastChild.textContent = 'Önizlemeyi yenile';
        });
      });

      // Deneme e-postası
      var test = q('[data-bl-test]'), adr = q('[name=deneme_adres]');
      var dene = function () {
        if (test.disabled) return;
        test.disabled = true;
        show(tmsg, 'Gönderiliyor.', false);
        post(form.getAttribute('data-deneme')).then(function (j) { test.disabled = false; show(tmsg, j.ok ? j.mesaj : j.hata, !j.ok); });
      };
      test.addEventListener('click', dene);
      adr.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); dene(); } });

      // Gönderimi başlat: alıcı sayısıyla onay alınır; "islem" alanı yalnızca bu gönderim için eklenir
      start.addEventListener('click', function () {
        var n = +bek.value;
        if (!n) return;
        var soru = nf.format(n) + ' aboneye e-posta gönderilecek. Gönderim başladıktan sonra metin ve alıcılar değiştirilemez. Başlatılsın mı?';
        var git = function () {
          var h = d.createElement('input');
          h.type = 'hidden'; h.name = 'islem'; h.value = 'baslat';
          form.appendChild(h);
          sync();
          if (form.requestSubmit) form.requestSubmit(); else { form.dispatchEvent(new Event('submit')); form.submit(); }
          h.remove();
        };
        // Biçimli onay penceresi (admin.js); yoksa ya da açılamazsa yerel onay sorulur
        if (window.admAsk) window.admAsk(soru, { opener: start, title: nf.format(n) + ' aboneye e-posta gönderilsin mi?', detail: 'Gönderim başladıktan sonra metin ve alıcılar değiştirilemez.', yes: 'Gönderimi başlat', danger: true }).then(function (ok) { if (ok) git(); });
        else if (window.confirm(soru)) git();
      });
    })();
    </script>
    <?php
    adm_layout($taslak ? 'Taslak e-posta' : 'E-posta yaz', (string) ob_get_clean(), [
        'section'  => 'bulten',
        'crumbs'   => [['Bülten', adm_url('bulten')], ['Gönderimler', adm_url('bulten/gonderimler')]],
        'subtitle' => 'Bülten abonelerine gönderilecek e-postayı yazın, önizleyin, kendinize deneme gönderin ve gönderimi başlatın.',
        'form'     => 'bl-form',
    ]);
}

/* =========================================================================
   Gönderim sayfası: ilerleme, duraklat / sürdür, hatalar, alıcı ayrıntısı
   ========================================================================= */
if ($gonderim !== null) {
    $c       = $gonderim;
    $s       = bulten_sayilar($c);
    $biten   = $s['gonderildi'] + $s['hata'] + $s['atlandi'];
    $pct     = $s['toplam'] ? (int) round($biten / $s['toplam'] * 100) : 0;
    $suruyor = $c['state'] === 'gonderiliyor';
    $saat    = bulten_saat_durum();
    $taban   = 'bulten/gonderimler/' . $c['id'];

    $durumMetni = [
        'gonderiliyor' => $yol === '' ? $yolYok : 'Gönderim sürüyor. Bu sayfa açık kaldıkça e-postalar gönderilir; sekmeyi kapatırsanız gönderim durur ve sayfayı yeniden açtığınızda kaldığı yerden devam eder.',
        'duraklatildi' => (string) ($c['pause_note'] ?? '') !== '' ? (string) $c['pause_note'] : 'Gönderim duraklatıldı. Sürdür düğmesiyle kaldığı yerden devam eder; gönderilmiş olanlara yeniden gönderilmez.',
        'tamamlandi'   => 'Gönderim ' . $zaman($c['finished'] ?? '') . ' tarihinde tamamlandı.',
    ][$c['state']];

    // Hata alanlar içinde gönderimi yarıda kesilenler (e-posta ulaşmış olabilir): yeniden gönderme onayında ayrıca belirtilir
    $kesilen = count(array_filter($c['recipients'], fn($r) => ($r['status'] ?? '') === 'hata' && ($r['error'] ?? '') === BULTEN_KESILDI));
    $dugme = fn(string $islem, string $etiket, string $ikon, string $cls, string $onay = '', bool $kapat = false): string =>
        '<form method="post" action="' . adm_url($taban . '/' . $islem) . '"' . ($onay !== '' ? ' data-confirm="' . e($onay) . '"' : '') . '>' . adm_csrf_field()
        . '<button class="btn ' . $cls . '" type="submit"' . ($kapat ? ' disabled title="SMTP ayarlanmadan e-posta gönderilemez"' : '') . '>' . ($ikon !== '' ? ui_icon($ikon) : '') . e($etiket) . '</button></form>';
    $dugmeler = ($suruyor ? $dugme('duraklat', 'Duraklat', '', 'btn--ghost') : '')
        . ($c['state'] === 'duraklatildi' ? $dugme('surdur', 'Sürdür', 'paper-plane-tilt', '', '', $yol === '') : '')
        . ($s['hata'] > 0 ? $dugme('yeniden', 'Hata alanlara yeniden gönder (' . $s['hata'] . ')', 'arrows-clockwise', 'btn--ghost',
            $s['hata'] . ' alıcıya e-posta yeniden gönderilecek. '
            . ($kesilen > 0
                ? 'DİKKAT: bunlardan ' . $kesilen . ' alıcının gönderimi yarıda kesilmişti; e-posta bu alıcılara ulaşmış olabilir, yeniden gönderilirse aynı e-postayı ikinci kez alırlar. '
                : 'Sunucunun yanıt vermediği gönderimlerde e-posta alıcıya ulaşmış olabilir; öyleyse aynı e-postayı ikinci kez alır. ')
            . 'Devam edilsin mi?', $yol === '') : '')
        . (!$suruyor ? $dugme('sil', 'Kaydı sil', 'trash', 'btn--danger', 'Bu gönderimin kaydı ve alıcı listesi silinsin mi? Geri alınamaz.') : '');

    $kutu = fn(string $k, string $ad, int $v): string => '<div class="bl-num bl-num--' . $k . '"><dt>' . $ad . '</dt><dd data-bl-c="' . $k . '">' . $num($v) . '</dd></div>';
    $ilerleme = '<section class="card bl-run is-' . e($c['state']) . ((string) ($c['pause_note'] ?? '') !== '' && $c['state'] === 'duraklatildi' ? ' is-err' : '') . '" data-bl-run data-url="' . adm_url($taban . '/gonder') . '" data-csrf="' . e(adm_csrf()) . '" data-auto="' . ($suruyor && $yol !== '' ? '1' : '0') . '">'
        . '<div class="card__body">'
        . '<div class="bl-run__head">' . $durumRozeti($c['state']) . '<strong data-bl-pct>%' . $pct . '</strong></div>'
        . '<div class="bl-bar" role="progressbar" aria-label="Gönderim ilerlemesi" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . $pct . '"><i data-bl-bar style="width:' . $pct . '%"></i></div>'
        . '<dl class="bl-nums">' . $kutu('gonderildi', 'Gönderildi', $s['gonderildi']) . $kutu('bekliyor', 'Bekliyor', $s['bekliyor'] + $s['gonderiliyor'])
        . $kutu('hata', 'Hata', $s['hata']) . $kutu('atlandi', 'Atlandı', $s['atlandi']) . $kutu('toplam', 'Toplam alıcı', $s['toplam']) . '</dl>'
        . '<p class="bl-run__status" data-bl-status role="status">' . e($durumMetni) . '</p>'
        . ($suruyor ? '<noscript><p class="bl-run__status">Gönderimin ilerlemesi için tarayıcınızda JavaScript açık olmalıdır.</p></noscript>' : '')
        . '<p class="muted bl-run__cap">Saatlik sınır: ' . $saat['sinir'] . ' e-posta. Son bir saatte gönderilen: <span data-bl-saat>' . $saat['kullanilan'] . '</span>.</p>'
        . ($dugmeler !== '' ? '<div class="bl-run__actions">' . $dugmeler . '</div>' : '')
        . '</div></section>';

    // Alıcı listesi: duruma göre süzülür, 100'er satır
    $adlar  = ['gonderildi' => 'Gönderildi', 'bekliyor' => 'Bekliyor', 'gonderiliyor' => 'Gönderiliyor', 'hata' => 'Hata', 'atlandi' => 'Atlandı'];
    $renk   = ['gonderildi' => ' badge--ok', 'hata' => ' badge--err', 'atlandi' => ' badge--warn', 'gonderiliyor' => ' badge--navy'];
    $hangi  = is_string($_GET['durum'] ?? null) && isset($adlar[$_GET['durum']]) ? $_GET['durum'] : '';
    $alici  = $hangi === '' ? $c['recipients'] : array_values(array_filter($c['recipients'], fn($r) => ($r['status'] ?? '') === $hangi));
    $per    = 100;
    $sayfalar = max(1, (int) ceil(count($alici) / $per));
    $sayfa  = max(1, min($sayfalar, (int) ($_GET['sayfa'] ?? 1)));
    $cips   = '<a class="chip' . ($hangi === '' ? ' is-on' : '') . '" href="' . adm_url($taban) . '#alicilar">Tümü <em>' . $s['toplam'] . '</em></a>';
    foreach (['gonderildi', 'hata', 'atlandi', 'bekliyor'] as $k) {
        if ($s[$k] > 0 || $hangi === $k) {
            $cips .= '<a class="chip' . ($hangi === $k ? ' is-on' : '') . '" href="' . adm_url($taban) . '?durum=' . $k . '#alicilar">' . $adlar[$k] . ' <em>' . $s[$k] . '</em></a>';
        }
    }
    $tr = '';
    foreach (array_slice($alici, ($sayfa - 1) * $per, $per) as $r) {
        $st = (string) ($r['status'] ?? '');
        $tr .= '<tr><td class="nowrap">' . e((string) ($r['email'] ?? '')) . '</td><td class="nowrap">' . e(trim(($r['ad'] ?? '') . ' ' . ($r['soyad'] ?? ''))) . '</td>'
            . '<td><span class="badge' . ($renk[$st] ?? '') . '">' . e($adlar[$st] ?? $st) . '</span></td><td class="nowrap">' . e($zaman($r['time'] ?? '')) . '</td>'
            . '<td class="wrap">' . e((string) ($r['error'] ?? '')) . '</td></tr>';
    }
    $liste = '<div class="chips bl-chips">' . $cips . '</div>'
        . ($tr !== ''
            ? '<div class="tbl-wrap bl-tbl"><table class="tbl"><thead><tr><th>E-posta</th><th>Ad soyad</th><th>Durum</th><th>Zaman</th><th>Not</th></tr></thead><tbody>' . $tr . '</tbody></table></div>'
            : '<div class="empty">' . ui_icon('envelope-simple') . '<strong>Bu durumda alıcı yok</strong></div>')
        . $sayfalama($sayfa, $sayfalar, fn(int $p) => adm_url($taban) . '?' . http_build_query(array_filter(['durum' => $hangi, 'sayfa' => $p > 1 ? $p : null])) . '#alicilar');

    $ilk = $c['recipients'][0] ?? ['ad' => 'Ahmet', 'soyad' => 'Yılmaz'];
    $m = bulten_eposta($c, ['email' => 'ornek@firma.com', 'ad' => (string) ($ilk['ad'] ?? ''), 'soyad' => (string) ($ilk['soyad'] ?? '')]);
    $kayit = '<div class="hx-facts">'
        . '<p><span>Konu</span>' . e((string) $c['subject']) . '</p>'
        . '<p><span>Alıcılar</span>' . e((string) ($c['filter_desc'] ?? '')) . '</p>'
        . '<p><span>Başlatan</span>' . $kimRozeti($c['who'] ?? []) . '</p>'
        . (is_array($c['draft_by'] ?? null) && ($c['draft_by']['type'] ?? '') === 'mcp' ? '<p><span>Taslağı hazırlayan</span>' . $kimRozeti($c['draft_by']) . '</p>' : '')
        . '<p><span>Başlangıç</span>' . e($zaman($c['created'] ?? '')) . '</p>'
        . (($c['finished'] ?? '') !== '' ? '<p><span>Bitiş</span>' . e($zaman($c['finished'])) . '</p>' : '')
        . '</div>';
    $eposta = '<details class="bl-prev__txt" data-bl-frame-wrap><summary>Gönderilen e-postayı göster</summary>'
        . '<iframe class="bl-frame" sandbox="allow-same-origin" title="Gönderilen e-posta" data-bl-frame srcdoc="' . e($m['html']) . '"></iframe></details>';

    ob_start();
    echo $uyari;
    ?>
    <div class="split">
      <div style="display:grid;gap:20px;min-width:0">
        <?= $ilerleme ?>
        <?= ui_card('Alıcılar', $liste, ['id' => 'alicilar', 'desc' => 'Alıcı listesi gönderim başlarken kesinleşti. O günden sonra abonelikten ayrılan ya da kaydı silinen adresler "Atlandı" olarak görünür; onlara e-posta gönderilmez.']) ?>
      </div>
      <aside class="split__side">
        <?= ui_card('Kayıt', $kayit . $eposta) ?>
      </aside>
    </div>
    <script><?= $jsOrtak ?></script>
    <script>
    (function () {
      var box = document.querySelector('[data-bl-run]');
      if (!box || box.getAttribute('data-auto') !== '1') return;
      var url = box.getAttribute('data-url'), csrf = box.getAttribute('data-csrf');
      var bar = box.querySelector('[data-bl-bar]'), msg = box.querySelector('[data-bl-status]');
      var nf = new Intl.NumberFormat('tr-TR'), timer = null, fails = 0;
      var say = function (text) { msg.textContent = text; };
      var paint = function (j) {
        var s = j.sayilar, done = s.gonderildi + s.hata + s.atlandi, pct = s.toplam ? Math.round(done / s.toplam * 100) : 0;
        var v = { gonderildi: s.gonderildi, bekliyor: s.bekliyor + s.gonderiliyor, hata: s.hata, atlandi: s.atlandi, toplam: s.toplam };
        Object.keys(v).forEach(function (k) { var el = box.querySelector('[data-bl-c="' + k + '"]'); if (el) el.textContent = nf.format(v[k]); });
        bar.style.width = pct + '%';
        bar.parentNode.setAttribute('aria-valuenow', pct);
        box.querySelector('[data-bl-pct]').textContent = '%' + pct;
        if (j.saat) box.querySelector('[data-bl-saat]').textContent = nf.format(j.saat.kullanilan);
      };
      // Saatlik sınır doldu: kalan süre gösterilir; dakikada bir yeniden sorulur (sınır o arada yükseltilmiş olabilir), yer açılınca gönderim kendiliğinden sürer
      var wait = function (sec, dk) {
        say('Saatlik gönderim sınırına ulaşıldı. Yaklaşık ' + nf.format(dk) + ' dakika sonra kendiliğinden devam edecek; bu sayfayı açık bırakın.');
        timer = setTimeout(step, Math.max(2, Math.min(sec + 1, 60)) * 1000);
      };
      var step = function () {
        var fd = new FormData();
        fd.append('_csrf', csrf);
        fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
          .then(function (r) { return r.text(); })
          .then(function (t) {
            var j;
            try { j = JSON.parse(t); } catch (e) {
              box.classList.add('is-err');
              say('Oturumunuz kapanmış olabilir. Sayfayı yenileyip yeniden giriş yapın; gönderim kaldığı yerden sürer.');
              return;
            }
            fails = 0;
            if (!j.ok) { box.classList.add('is-err'); say(j.hata || 'Gönderim sürdürülemedi.'); return; }
            paint(j);
            if (j.state !== 'gonderiliyor') { location.reload(); return; }   // tamamlandı ya da başka sekmeden duraklatıldı
            if (j.engel) { box.classList.add('is-err'); say(j.engel); return; }
            if (j.mesgul) { say('Gönderim şu an başka bir sekmede sürüyor; ilerleme buradan da izlenir.'); timer = setTimeout(step, 4000); return; }
            if (j.bekle) { wait(j.bekle, j.bekle_dk); return; }
            say('Gönderiliyor. Bu sayfa açık kaldıkça gönderim sürer.');
            timer = setTimeout(step, 700);
          }, function () {
            fails++;
            say('Sunucuya ulaşılamadı; kısa süre sonra yeniden denenecek.');
            timer = setTimeout(step, Math.min(60000, 5000 * fails));
          });
      };
      step();
    })();
    </script>
    <?php
    adm_layout('Gönderim', (string) ob_get_clean(), [
        'section'  => 'bulten',
        'crumbs'   => [['Bülten', adm_url('bulten')], ['Gönderimler', adm_url('bulten/gonderimler')]],
        'subtitle' => e((string) $c['subject']),
    ]);
}

/* =========================================================================
   Gönderimler: taslaklar, geçmiş, saatlik sınır
   ========================================================================= */
if ($a0 === 'gonderimler') {
    $hepsi = bulten_gonderimler();
    $taslaklar = array_values(array_filter($hepsi, fn($c) => $c['state'] === 'taslak'));
    $gecmis = array_values(array_filter($hepsi, fn($c) => $c['state'] !== 'taslak'));
    usort($gecmis, fn($a, $b) => strcmp((string) ($b['created'] ?? ''), (string) ($a['created'] ?? '')));
    $saat = bulten_saat_durum();

    $tl = '';
    foreach ($taslaklar as $c) {
        $n = count(bulten_alicilar($c['filter']));
        $tl .= '<div class="list__row"><div class="list__main">'
            . '<span class="list__title">' . e((string) $c['subject'] !== '' ? (string) $c['subject'] : '(konu yazılmamış)') . '</span>'
            . '<span class="list__meta"><span>' . e($zaman($c['updated'] ?? $c['created'] ?? '')) . '</span><span>' . e((string) ($c['filter_desc'] ?? '')) . '</span><span>şu an ' . $num($n) . ' alıcı</span>' . $kimRozeti($c['who'] ?? []) . '</span></div>'
            . '<div class="list__side"><a class="btn btn--soft btn--sm" href="' . adm_url('bulten/gonderimler/' . $c['id']) . '">' . ui_icon('pencil-simple') . 'Aç</a>'
            . '<form method="post" action="' . adm_url('bulten/gonderimler/' . $c['id'] . '/sil') . '" data-confirm="Bu taslak silinsin mi? Geri alınamaz.">' . adm_csrf_field()
            . '<button class="btn btn--danger btn--sm" type="submit" aria-label="Taslağı sil" title="Taslağı sil">' . ui_icon('trash') . '</button></form></div></div>';
    }
    $gl = '';
    foreach ($gecmis as $c) {
        $s = bulten_sayilar($c);
        $gl .= '<a class="list__row" href="' . adm_url('bulten/gonderimler/' . $c['id']) . '"><div class="list__main">'
            . '<span class="list__title">' . e((string) $c['subject']) . '</span>'
            . '<span class="list__meta"><span>' . e($zaman($c['created'] ?? '')) . '</span><span>' . e((string) ($c['filter_desc'] ?? '')) . '</span>'
            . '<span>' . $num($s['gonderildi']) . ' / ' . $num($s['toplam']) . ' gönderildi</span>'
            . ($s['hata'] ? '<span class="bl-warn">' . $num($s['hata']) . ' hata</span>' : '') . ($s['atlandi'] ? '<span>' . $num($s['atlandi']) . ' atlandı</span>' : '') . '</span></div>'
            . '<div class="list__side">' . $durumRozeti($c['state']) . ui_icon('caret-right', 'i list__go') . '</div></a>';
    }
    $yolAdi = ['smtp' => 'SMTP hesabıyla (' . e((string) (cfg('mail.smtp.host') ?? '')) . ')', 'mail' => 'sunucunun e-posta fonksiyonuyla (toplu gönderimde SMTP ayarlamanız önerilir)', '' => 'gönderim şu an mümkün değil'][$yol];
    $sinir = '<form method="post" action="' . adm_url('bulten/ayar') . '" class="bl-cap">' . adm_csrf_field()
        . ui_text('saatlik', 'Saatte en fazla kaç e-posta gönderilsin?', (string) $saat['sinir'], ['type' => 'number', 'min' => BULTEN_SAATLIK_MIN, 'max' => BULTEN_SAATLIK_MAX, 'step' => 1, 'required' => true, 'inputmode' => 'numeric',
            'help' => BULTEN_SAATLIK_MIN . ' ile ' . BULTEN_SAATLIK_MAX . ' arası. Barındırma firmaları saatlik e-posta sayısını sınırlar (çoğunlukla 100 ile 500 arası); sınırınızı firmanızdan öğrenin. Sınır dolunca gönderim bekler ve kendiliğinden sürer.'])
        . '<div><button class="btn btn--ghost" type="submit">' . ui_icon('floppy-disk') . 'Kaydet</button></div></form>'
        . '<p class="muted">Son bir saatte gönderilen: <strong>' . $num($saat['kullanilan']) . '</strong> / ' . $num($saat['sinir']) . ' (tüm gönderimler ve deneme e-postaları birlikte sayılır). E-postalar ' . $yolAdi . ' gönderilir.</p>';

    ob_start();
    echo $uyari . $sekmeler('gonderimler');
    if ($taslaklar) {
        echo ui_card('Taslaklar', '<div class="list">' . $tl . '</div>', ['desc' => 'Henüz gönderilmemiş e-postalar. Yapay zekâ erişimiyle hazırlanan taslaklar da burada görünür; hiçbir taslak siz açıp başlatmadan gönderilmez.']);
    }
    echo ui_card('Gönderim geçmişi', $gl !== ''
        ? '<div class="list">' . $gl . '</div>'
        : '<div class="empty">' . ui_icon('paper-plane-tilt') . '<strong>Henüz gönderim yok</strong><span>Aboneler sekmesinde listeyi süzüp "Bu listeye e-posta gönder" düğmesiyle ilk e-postanızı hazırlayabilirsiniz.</span></div>');
    echo ui_card('Saatlik gönderim sınırı', $sinir, ['id' => 'sinir']);
    adm_layout('Bülten', (string) ob_get_clean(), [
        'section'  => 'bulten',
        'subtitle' => 'Bülten abonelerine gönderilen e-postalar: taslaklar, süren ve tamamlanan gönderimler.',
        'actions'  => '<a class="btn btn--sm" href="' . adm_url('bulten/yeni') . '">' . ui_icon('pencil-simple') . 'E-posta yaz</a>',
    ]);
}

/* =========================================================================
   Aboneler
   ========================================================================= */
$tum      = bulten_aboneler();
$f        = bulten_suzgec($_GET);
$rows     = bulten_suz($tum, $f);
$alabilir = array_values(array_filter($rows, fn($a) => $a['durum'] === 'onayli'));

/* ---------- CSV: süzülen listenin tamamı ---------- */
if ($a0 === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="bulten-aboneleri-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_map('csv_safe', ['Ad', 'Soyad', 'E-posta', 'Telefon', 'İl', 'Sektör', 'İlgilendiği konular', 'Kayıt tarihi', 'Durum']), ';', '"', '');
    foreach ($rows as $a) {
        $line = [$a['ad'], $a['soyad'], $a['email'], $a['telefon'], $a['il'], $a['sektor'], $a['konular'], $zaman($a['zaman']), $durumlar[$a['durum']]];
        fputcsv($out, array_map(fn($c) => csv_safe((string) $c), $line), ';', '"', '');
    }
    fclose($out);
    exit;
}

$per      = 100;
$sayfalar = max(1, (int) ceil(count($rows) / $per));
$sayfa    = max(1, min($sayfalar, (int) ($_GET['sayfa'] ?? 1)));
$sorgu    = bulten_suzgec_sorgu($f);
$suzuldu  = $sorgu !== '';
$say      = array_count_values(array_column($tum, 'durum'));
$rozet    = ['onayli' => ' badge--ok', 'onaysiz' => ' badge--warn', 'ayrildi' => ''];

$tbl = '';
foreach (array_slice($rows, ($sayfa - 1) * $per, $per) as $a) {
    $konu = mb_strlen($a['konular']) > 220 ? mb_substr($a['konular'], 0, 220) . '…' : $a['konular'];
    $tbl .= '<tr><td class="nowrap">' . e($a['adsoyad']) . '</td>'
        . '<td class="nowrap"><a href="mailto:' . e($a['email']) . '">' . e($a['email']) . '</a></td>'
        . '<td class="nowrap">' . ($a['telefon'] !== '' ? '<a href="tel:' . e((string) preg_replace('/\s+/', '', $a['telefon'])) . '">' . e($a['telefon']) . '</a>' : '') . '</td>'
        . '<td class="nowrap">' . e($a['il']) . '</td><td class="bl-tbl__sek">' . e($a['sektor']) . '</td><td class="wrap">' . nl2br(e($konu)) . '</td>'
        . '<td class="nowrap">' . e($zaman($a['zaman'])) . '</td>'
        . '<td><span class="badge' . $rozet[$a['durum']] . '">' . e($durumlar[$a['durum']]) . '</span>'
        // Ayrılmış adres formu yeniden doldurduysa yönetici görsün: abonelik kendiliğinden açılmaz, karar yöneticinindir
        . ($a['yeniden'] ? '<span class="bl-tbl__not">' . e($zaman($a['yeniden'])) . ' tarihinde bülten formu bu adresle yeniden dolduruldu</span>' : '') . '</td>'
        . '<td class="bl-tbl__act">'
        . '<form method="post" action="' . adm_url($a['durum'] !== 'ayrildi' ? 'bulten/cikar' : 'bulten/yeniden-abone') . '" data-confirm="' . e($a['durum'] !== 'ayrildi'
            ? $a['email'] . ' abonelikten çıkarılsın mı? Bu adrese artık bülten e-postası gönderilmez. Ayrılma kalıcıdır: kişi siteden yeniden kaydolsa da abonelik açılmaz, yalnızca bu listedeki "Yeniden abone yap" düğmesiyle geri alınır.'
            : $a['email'] . ' yeniden abone yapılsın mı? Bu adrese yeniden bülten e-postası gönderilir. Yalnızca adresin sahibi bunu sizden istediyse yapın.') . '">' . adm_csrf_field()
        . '<input type="hidden" name="email" value="' . e($a['email']) . '"><input type="hidden" name="geri" value="' . e(bulten_suzgec_sorgu($f, ['sayfa' => $sayfa > 1 ? $sayfa : null])) . '">'
        . '<button class="btn btn--ghost btn--sm" type="submit">' . ($a['durum'] !== 'ayrildi' ? 'Abonelikten çıkar' : 'Yeniden abone yap') . '</button></form>'
        . '</td></tr>';
}

$durumSec = '<div class="sel bl-filters__durum"><select class="inp" name="durum" aria-label="Durum"><option value="">Tüm durumlar</option>';
foreach ($durumlar as $k => $ad) {
    $durumSec .= '<option value="' . $k . '"' . ($f['durum'] === $k ? ' selected' : '') . '>' . e($ad) . ' (' . (int) ($say[$k] ?? 0) . ')</option>';
}
$durumSec .= '</select></div>';

$suzgec = '<form class="bl-filters" method="get" action="' . adm_url('bulten') . '">' . $suzgecKutulari($f, $tum) . $durumSec
    . '<input class="inp bl-filters__q" type="search" name="q" value="' . e($f['q']) . '" placeholder="Ad, e-posta, sektör ya da konu ara" aria-label="Abonelerde ara" maxlength="120">'
    . '<button class="btn btn--sm" type="submit">' . ui_icon('magnifying-glass') . 'Süz</button>'
    . ($suzuldu ? '<a class="btn btn--ghost btn--sm" href="' . adm_url('bulten') . '">Süzgeci temizle</a>' : '') . '</form>';

$gonderDugme = $alabilir && $yol !== ''
    ? '<a class="btn btn--sm" href="' . e(adm_url('bulten/yeni') . bulten_suzgec_sorgu(['durum' => ''] + $f)) . '">' . ui_icon('paper-plane-tilt') . 'Bu listeye e-posta gönder</a>'
    : '<button class="btn btn--sm" type="button" disabled title="' . ($yol === '' ? 'SMTP ayarlanmadan e-posta gönderilemez' : 'Bu listede e-posta alabilen abone yok') . '">' . ui_icon('paper-plane-tilt') . 'Bu listeye e-posta gönder</button>';
$ozet = '<div class="bl-sum"><p><strong>' . $num(count($rows)) . '</strong> abone ' . ($suzuldu ? 'süzgece uyuyor' : 'var') . '; <strong>' . $num(count($alabilir)) . '</strong> tanesi e-posta alabilir.</p>'
    . '<div class="bl-sum__actions">'
    . '<button class="btn btn--ghost btn--sm" type="button" data-bl-copy data-done="' . $num(count($alabilir)) . ' adres kopyalandı"' . ($alabilir ? '' : ' disabled') . '>' . ui_icon('copy') . '<span>E-posta adreslerini kopyala</span></button>'
    . ($rows ? '<a class="btn btn--ghost btn--sm" href="' . e(adm_url('bulten/csv') . $sorgu) . '">' . ui_icon('download-simple') . 'Excel için indir</a>' : '')
    . $gonderDugme . '</div>'
    . '<textarea hidden data-bl-emails>' . e(implode("\n", array_column($alabilir, 'email'))) . '</textarea></div>';

$body = $suzgec . $ozet . ($rows
    ? '<div class="tbl-wrap bl-tbl"><table class="tbl"><thead><tr><th>Ad soyad</th><th>E-posta</th><th>Telefon</th><th>İl</th><th>Sektör</th><th>İlgilendiği konular</th><th>Kayıt tarihi</th><th>Durum</th><th class="bl-tbl__act"><span class="sr">İşlem</span></th></tr></thead><tbody>'
        . $tbl . '</tbody></table></div>'
        . $sayfalama($sayfa, $sayfalar, fn(int $p) => adm_url('bulten') . bulten_suzgec_sorgu($f, ['sayfa' => $p > 1 ? $p : null]))
    : '<div class="empty">' . ui_icon('envelope-simple') . '<strong>' . ($tum ? 'Süzgece uyan abone yok' : 'Henüz abone yok') . '</strong><span>'
        . ($tum ? 'Seçimleri azaltmayı ya da arama kutusunu temizlemeyi deneyin. Sektörünü eskiden serbest metinle yazmış aboneler sektör listesinde çıkmaz; arama kutusuyla bulunur.' : 'Sitedeki bülten formunu dolduranlar burada listelenir.')
        . '</span></div>');

adm_layout('Bülten', $uyari . $sekmeler('') . ui_card('', $body, ['class' => 'bl-card'])
    . ui_card('Silinmiş kişinin “bir daha e-posta alma” kaydı', '<p class="muted">Bir kişinin form kaydı silindiğinde, daha önce abonelikten ayrılmışsa tercihi adres yerine yalnızca anahtarlı bir özet olarak saklanır (adres tutulmaz). Kişi bu özetin de silinmesini isterse adresini buraya yazın; kayıt silinir ve adres yeniden kayıt olursa e-posta alabilir.</p>'
        . '<form method="post" action="' . adm_url('bulten/engel-kaldir') . '" class="bl-engel" data-confirm="Bu adresin “bir daha e-posta alma” kaydı silinsin mi? Adres yeniden kayıt olursa bülten alır.">' . adm_csrf_field()
        . ui_text('email', 'E-posta adresi', '', ['type' => 'email', 'required' => true, 'maxlength' => 254, 'autocomplete' => 'off'])
        . '<div><button class="btn btn--ghost btn--sm" type="submit">Kaydı sil</button></div></form>', ['id' => 'engel'])
    . '<script>' . $jsOrtak . '</script><script>
(function () {
  var d = document, cp = d.querySelector("[data-bl-copy]"), ff = d.querySelector("form.bl-filters");
  // Boş bırakılan süzgeç alanları adrese yazılmaz (paylaşılan adres kısa kalır)
  if (ff) ff.addEventListener("submit", function () {
    [].forEach.call(ff.querySelectorAll("select, input[type=search]"), function (el) { if (el.name && el.value === "") el.disabled = true; });
    setTimeout(function () { [].forEach.call(ff.querySelectorAll("[disabled]"), function (el) { el.disabled = false; }); }, 0);
  });
  if (!cp) return;
  cp.addEventListener("click", function () {
    var text = d.querySelector("[data-bl-emails]").value, s = cp.querySelector("span"), old = s.textContent;
    var done = function () {
      s.textContent = cp.getAttribute("data-done");
      cp.classList.add("is-done");
      setTimeout(function () { s.textContent = old; cp.classList.remove("is-done"); }, 2400);
    };
    var fallback = function () {
      var ta = d.createElement("textarea");
      ta.value = text; ta.setAttribute("readonly", ""); ta.style.cssText = "position:fixed;opacity:0;top:0";
      d.body.appendChild(ta); ta.select();
      try { d.execCommand("copy"); done(); } catch (e) {}
      ta.remove();
    };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(text).then(done, fallback); else fallback();
  });
})();
</script>', [
    'section'  => 'bulten',
    'wide'     => true,
    'subtitle' => 'Sitedeki bülten formunu dolduranlar; her adres, ileti onayı verdiği ilk kaydıyla bir kez listelenir. Listeyi ile ve sektöre göre süzüp adresleri kopyalayabilir, Excel için indirebilir ya da doğrudan e-posta gönderebilirsiniz. Toplam ' . $num(count($tum)) . ' abone: '
        . $num((int) ($say['onayli'] ?? 0)) . ' onaylı, ' . $num((int) ($say['onaysiz'] ?? 0)) . ' onay yok, ' . $num((int) ($say['ayrildi'] ?? 0)) . ' ayrıldı.',
    'actions'  => '<a class="btn btn--sm" href="' . adm_url('bulten/yeni') . '">' . ui_icon('pencil-simple') . 'E-posta yaz</a>',
]);
