<?php
declare(strict_types=1);

/**
 * Bülten: aboneler, süzgeçler, toplu e-posta gönderimi ve abonelikten ayrılma.
 * ---------------------------------------------------------------------------
 * Aboneler ayrı bir yerde tutulmaz; storage/submissions.jsonl içindeki bülten kayıtlarından ("bulten", eski "haberdarol") türetilir.
 * Spam süzgecinin karantinaya aldığı kayıtlar ("spam" anahtarı taşıyanlar) hiçbir yerde abone sayılmaz.
 * Bir adresin abonelik bilgisini (ad, il, sektör, onay) o adresin onay içeren EN ESKİ kaydı belirler: aynı adresle sonradan
 * yapılan kayıtlar (başkası da yapmış olabilir) saklanır ama aboneyi değiştirmez. Abonelikten ayrılma kalıcıdır: adres ancak
 * yönetici panelden yeniden abone yaparsa e-posta alır; sitedeki formu yeniden doldurmak aboneliği geri açmaz.
 *
 *   storage/bulten/ayarlar.json           saatlik gönderim sınırı
 *   storage/bulten/ayrilanlar.json        abonelikten ayrılan adresler (adres => zaman); yönetici yeniden abone yapana kadar kalır
 *   storage/bulten/bastirilanlar.json     kaydı silinen kişilerin "bir daha e-posta alma" tercihi: adres DEĞİL, adresin anahtarlı özeti (HMAC, site anahtarıyla)
 *                                         => ayrılma zamanı. Kişinin son kaydı silinince ayrılanlar listesindeki adres yerine buraya geçer; aynı adresle
 *                                         sonradan yapılan kayıt yine "ayrıldı" görünür ve e-posta almaz. Yönetici "yeniden abone yap" derse silinir.
 *   storage/bulten/saat.json              son bir saatte gönderilen e-postaların zamanları (sınır tüm gönderimler için ortaktır)
 *   storage/bulten/imza.json              ayrılma bağlantılarının imzalandığı anahtarlar (anahtar yenilense de eski e-postalardaki bağlantı çalışır)
 *   storage/bulten/gonderimler/{id}.json  gönderimler ve taslaklar
 *
 * Gönderim (sunucuda zamanlanmış görev yoktur): gönderim sayfası açıkken tarayıcı, parti uç noktasını art arda çağırır.
 * Her çağrı gönderimin kilidi altında en fazla BULTEN_PARTI alıcıyı işler (gönderir ya da atlar); aynı anda gelen ikinci çağrı beklemeden geri döner.
 * Her alıcı gönderilmeden ÖNCE dosyada "gonderiliyor" diye işaretlenir; istek yarıda kesilirse o alıcıya yeniden gönderilmez,
 * "hata" olarak işaretlenir ve yönetici isterse yeniden dener. Böylece bir alıcıya aynı gönderimden iki e-posta gitmez.
 *
 * Panel: app/admin/sections/bulten.php · Herkese açık ayrılma sayfası: app/pages/bulten-ayril.php (assets/css/pages/unsub.css) · MCP: app/mcp/tools_bulten.php
 */

require_once APP . '/spam.php';     // spam_records(): kayıt dosyasının paylaşımlı kilitle okunması
require_once APP . '/mailer.php';   // mail_address_ok(): adreslerin sıkı denetimi

const BULTEN_DIR         = ROOT . '/storage/bulten';
const BULTEN_PARTI       = 10;      // bir istekte işlenen en fazla alıcı (gönderilen ve atlananlar birlikte)
const BULTEN_PARTI_SURE  = 12;      // bir istekte yeni e-postaya başlamak için süre sınırı (saniye)
const BULTEN_ARDARDA_HATA = 3;      // art arda bu kadar e-posta gönderilemezse gönderim kendiliğinden duraklatılır
const BULTEN_SAATLIK     = 100;     // varsayılan saatlik gönderim sınırı
const BULTEN_SAATLIK_MIN = 20;
const BULTEN_SAATLIK_MAX = 2000;
const BULTEN_KONU_MAX    = 150;
const BULTEN_GOVDE_MAX   = 60000;
const BULTEN_IMALAT      = 'İmalat';   // "İmalat (tümü)" kısayolu: adı bununla başlayan bütün sektörler
const BULTEN_KESILDI     = 'Gönderim yarıda kesildi; e-postanın ulaşıp ulaşmadığı bilinmiyor.';   // yarıda kesilen alıcının notu (yeniden denemede uyarı için sayılır)

/* =========================================================================
   Depo yardımcıları
   ========================================================================= */

function bulten_dir(string $alt = ''): string
{
    $dir = BULTEN_DIR . ($alt !== '' ? '/' . $alt : '');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function bulten_json_flags(): int
{
    return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
}

function bulten_oku(string $dosya): array
{
    $d = is_file($dosya) ? json_decode((string) @file_get_contents($dosya), true) : null;
    return is_array($d) ? $d : [];
}

/** Geçici dosya + yer değiştirme: okuyanlar hiçbir zaman yarım dosya görmez. */
function bulten_yaz(string $dosya, array $veri): bool
{
    $tmp = $dosya . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, json_encode($veri, bulten_json_flags())) === false) {
        @unlink($tmp);
        return false;
    }
    @chmod($tmp, 0600);
    if (!@rename($tmp, $dosya)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * $fn kilit altında çalışır ve sonucu döner. $bekle false ise ve kilit başkasındaysa beklemeden null döner
 * (bu yüzden $fn hiçbir zaman null döndürmemelidir).
 * @return mixed
 */
function bulten_kilit(string $ad, callable $fn, bool $bekle = true)
{
    $fh = @fopen(bulten_dir() . '/.' . $ad . '.lock', 'c');
    if (!$fh) {
        throw new RuntimeException('Bülten deposu yazılamıyor (storage/bulten klasörü yazılabilir mi?).');
    }
    try {
        if (!flock($fh, $bekle ? LOCK_EX : LOCK_EX | LOCK_NB)) {
            return null;
        }
        return $fn();
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/** Türkçe karakter ve büyük/küçük harf farkını yok sayan arama biçimi (Sayfa metinleri aramasıyla aynı kural). */
function bulten_fold(string $s): string
{
    $s = strtr($s, ['İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u']);
    $s = mb_strtolower($s, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $s));
}

/** Tek satırlık, denetim karakteri içermeyen metin. */
function bulten_satir(string $s, int $max): string
{
    $s = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s);
    return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $s)), 0, $max);
}

/* =========================================================================
   Ayarlar ve saatlik sınır
   ========================================================================= */

/** @return array{saatlik:int} */
function bulten_ayarlar(): array
{
    $d = bulten_oku(BULTEN_DIR . '/ayarlar.json');
    $n = (int) ($d['saatlik'] ?? BULTEN_SAATLIK);
    return ['saatlik' => max(BULTEN_SAATLIK_MIN, min(BULTEN_SAATLIK_MAX, $n))];
}

function bulten_ayar_kaydet(int $saatlik): bool
{
    $saatlik = max(BULTEN_SAATLIK_MIN, min(BULTEN_SAATLIK_MAX, $saatlik));
    return (bool) bulten_kilit('ayarlar', fn() => bulten_yaz(bulten_dir() . '/ayarlar.json', ['saatlik' => $saatlik]) ? 1 : 0);
}

/** Son bir saatte gönderilen e-postaların zamanları (eskiden yeniye). */
function bulten_saat_listesi(): array
{
    $now = time();
    $t = array_values(array_filter((array) (bulten_oku(BULTEN_DIR . '/saat.json')['t'] ?? []), fn($x) => is_int($x) && $x > $now - 3600 && $x <= $now + 5));
    sort($t);
    return $t;
}

/**
 * Saatlik sınırdan yer ayırır (gönderimden ÖNCE; aynı anda çalışan iki gönderim sınırı birlikte aşamaz).
 * @return array{0:int, 1:int} [ayrılan e-posta sayısı, hiç yer yoksa ilk yerin açılmasına kalan saniye]
 */
function bulten_saat_ayir(int $istek): array
{
    return bulten_kilit('saat', function () use ($istek): array {
        $t = bulten_saat_listesi();
        $bos = bulten_ayarlar()['saatlik'] - count($t);
        $n = max(0, min($istek, $bos));
        if ($n === 0) {
            // En eski gönderim bir saatini doldurunca bir yer açılır; sınır az önce düşürüldüyse fazlalığın erimesi beklenir
            $acilacak = $t[max(0, -$bos)] ?? time();
            return [0, max(1, $acilacak + 3600 - time() + 1)];
        }
        $now = time();
        for ($i = 0; $i < $n; $i++) {
            $t[] = $now;
        }
        bulten_yaz(bulten_dir() . '/saat.json', ['t' => $t]);
        return [$n, 0];
    });
}

/** Ayrılıp kullanılmayan yerleri geri verir. */
function bulten_saat_iade(int $n): void
{
    if ($n <= 0) {
        return;
    }
    bulten_kilit('saat', function () use ($n): int {
        $t = bulten_saat_listesi();
        bulten_yaz(bulten_dir() . '/saat.json', ['t' => array_slice($t, 0, max(0, count($t) - $n))]);
        return 1;
    });
}

/** @return array{kullanilan:int, sinir:int, bekle:int} bekle: sınır doluysa ilk yerin açılmasına kalan saniye, değilse 0 */
function bulten_saat_durum(): array
{
    $t = bulten_saat_listesi();
    $sinir = bulten_ayarlar()['saatlik'];
    $bos = $sinir - count($t);
    return ['kullanilan' => count($t), 'sinir' => $sinir, 'bekle' => $bos > 0 ? 0 : max(1, ($t[max(0, -$bos)] ?? time()) + 3600 - time() + 1)];
}

/* =========================================================================
   Aboneler
   ========================================================================= */

function bulten_durumlar(): array
{
    return ['onayli' => 'Onaylı', 'onaysiz' => 'Onay yok', 'ayrildi' => 'Ayrıldı'];
}

/** Spam süzgecinin (app/spam.php) şüpheli bulup ayırdığı kayıt: "spam" anahtarı taşır; "Spam değil" denince anahtar kaldırılır. */
function bulten_karantinada(array $r): bool
{
    return !empty($r['spam']);
}

/** Ticari elektronik ileti onayı var mı: form "Evet (gg.aa.yyyy ss:dd)" yazar. */
function bulten_onay_var(array $d): bool
{
    return is_string($d['etk'] ?? null) && str_starts_with(trim($d['etk']), 'Evet');
}

/** Abonelikten ayrılanlar: adres => ayrılma zamanı (unix). Adres, yönetici yeniden abone yapana kadar listede kalır. */
function bulten_ayrilanlar(): array
{
    $out = [];
    foreach (bulten_oku(BULTEN_DIR . '/ayrilanlar.json') as $email => $iso) {
        $t = is_string($iso) ? (int) strtotime($iso) : 0;
        if (is_string($email) && $t > 0) {
            $out[$email] = $t;
        }
    }
    return $out;
}

/* ---------- Engel listesi: kaydı silinen kişinin ayrılma tercihi (adres tutulmaz, yalnızca anahtarlı özeti) ---------- */

/** Anahtarın kısa kimliği (özetle birlikte saklanır; hangi anahtarla üretildiği bilinsin). Anahtarın kendisi sızmaz. */
function bulten_engel_kimlik(string $anahtar): string
{
    return substr(hash('sha256', 'engel-kimlik|' . $anahtar), 0, 8);
}

/** Adresin engel listesindeki girdisi: "anahtarkimligi:özet". Özet, imza bağlantısındaki imzadan farklıdır (ayrı alan etiketi). */
function bulten_engel_girdi(string $email, string $anahtar): string
{
    return bulten_engel_kimlik($anahtar) . ':' . hash_hmac('sha256', 'engel|' . strtolower(trim($email)), $anahtar);
}

/** Engel listesi: girdi => ayrılma zamanı (unix). Dosyadaki bozuk satırlar atılır. */
function bulten_engel_listesi(): array
{
    $out = [];
    foreach (bulten_oku(BULTEN_DIR . '/bastirilanlar.json') as $girdi => $iso) {
        $t = is_string($iso) ? (int) strtotime($iso) : 0;
        if (is_string($girdi) && preg_match('/^[a-f0-9]{8}:[a-f0-9]{64}$/D', $girdi) && $t > 0) {
            $out[$girdi] = $t;
        }
    }
    return $out;
}

/**
 * Engel listesine bakarken denenecek anahtarlar: şimdiki form güvenlik anahtarı ve saklanmış eski anahtarlar (imza.json).
 * Anahtar yenilense de önceki anahtarla yazılmış girdiler bulunur (ayrılma bağlantılarının imzaları gibi); varsayılan anahtar hiçbir zaman saklanmadığından
 * yalnızca şimdiki anahtar olarak denenir.
 */
function bulten_engel_anahtarlari(): array
{
    $k = (array) (bulten_oku(BULTEN_DIR . '/imza.json')['anahtarlar'] ?? []);
    return array_values(array_unique(array_filter(array_merge([(string) cfg('secret')], array_filter($k, 'is_string')), fn($x) => $x !== '')));
}

/**
 * Bir adresin engel listesi girdisini siler (kişi “bu özetin de silinmesini” istediğinde): her anahtarla üretilmiş girdi temizlenir. Adres günlüğe yazılmaz.
 * @return bool bir girdi silindiyse true
 */
function bulten_engel_kaldir(string $email): bool
{
    $email = strtolower(trim($email));
    if ($email === '' || !mail_address_ok($email)) {
        return false;
    }
    $oldu = (bool) bulten_kilit('ayrilanlar', function () use ($email): int {
        $file = bulten_dir() . '/bastirilanlar.json';
        $engel = bulten_oku($file);
        $degisti = false;
        foreach (bulten_engel_anahtarlari() as $a) {
            $g = bulten_engel_girdi($email, $a);
            if (isset($engel[$g])) {
                unset($engel[$g]);
                $degisti = true;
            }
        }
        return $degisti && bulten_yaz($file, $engel) ? 1 : 0;
    });
    if ($oldu) {
        bulten_aboneler(true);
        changelog_event('bulten', 'Kaydı silinmiş bir kişinin “bir daha e-posta alma” kaydı (anahtarlı özet) yönetim panelinden kaldırıldı');
    }
    return $oldu;
}

/** Yedekten gelen engel listesi girdilerini mevcut listeyle birleştirir (yalnızca biçimi geçerli girdiler). @return int eklenen girdi sayısı */
function bulten_engel_birlestir(array $in): int
{
    return (int) bulten_kilit('ayrilanlar', function () use ($in): int {
        $file = bulten_dir() . '/bastirilanlar.json';
        $cur = bulten_oku($file);
        $n = 0;
        foreach ($in as $g => $iso) {
            if (is_string($g) && preg_match('/^[a-f0-9]{8}:[a-f0-9]{64}$/D', $g) && is_string($iso) && strtotime($iso) > 0 && !isset($cur[$g])) {
                $cur[$g] = $iso;
                $n++;
            }
        }
        return $n && bulten_yaz($file, $cur) ? $n : 0;
    });
}

/** Adres engel listesinde mi? Ayrılma zamanı (unix) ya da 0. */
function bulten_engel_zamani(string $email): int
{
    $liste = bulten_engel_listesi();
    if (!$liste) {
        return 0;
    }
    foreach (bulten_engel_anahtarlari() as $a) {
        $g = bulten_engel_girdi($email, $a);
        if (isset($liste[$g])) {
            return $liste[$g];
        }
    }
    return 0;
}

/** Adresin ayrılma zamanı: ayrılanlar listesinde (adres açık) ya da engel listesinde (yalnızca özet); yoksa 0. */
function bulten_ayrilma_zamani(string $email): int
{
    $email = strtolower(trim($email));
    return bulten_ayrilanlar()[$email] ?? bulten_engel_zamani($email);
}

/**
 * Aboneler, en yeni kayıt önce. Abone = e-posta adresi (küçük harfle) başına tek satır. Bilgileri (ad, il, sektör, kayıt tarihi),
 * o adresin karantinada olmayan ve ileti onayı içeren EN ESKİ bülten kaydından gelir: aynı adresle sonradan yapılan kayıtlar
 * (adresin sahibi olmayan biri de yapmış olabilir) Form kayıtlarında görünür ama aboneyi değiştirmez. Onay içeren kaydı hiç
 * olmayan adres (eski "Haberdar ol" kayıtları) en eski kaydıyla ve "onaysiz" olarak listelenir.
 * Durum: ayrildi (abonelikten çıkmış; yönetici panelden yeniden abone yapana kadar böyle kalır, formu yeniden doldurmak yetmez),
 * onayli (ileti onayı var), onaysiz (ileti onayı yok).
 * yeniden: ayrılmış adres, ayrıldıktan sonra onay vererek formu yeniden doldurduysa o kaydın zamanı (yöneticiye gösterilir), yoksa 0.
 * @return array<int, array{email:string, ad:string, soyad:string, adsoyad:string, telefon:string, il:string, sektor:string, konular:string, zaman:int, durum:string, yeniden:int}>
 */
function bulten_aboneler(bool $yenile = false): array
{
    static $cache = null;
    if ($cache !== null && !$yenile) {
        return $cache;
    }
    $ilk = [];       // adres => aboneyi belirleyen kayıt
    $sonOnay = [];   // adres => onay içeren en yeni kaydın zamanı
    $sira = 0;
    foreach (spam_records() as $r) {
        if (!in_array($r['form'] ?? '', ['bulten', 'haberdarol'], true) || bulten_karantinada($r)) {
            continue;
        }
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        $email = is_scalar($d['email'] ?? null) ? strtolower(trim((string) $d['email'])) : '';
        if (!mail_address_ok($email)) {
            continue;
        }
        $t = (int) strtotime((string) ($r['time'] ?? ''));
        $onay = bulten_onay_var($d);
        $sira++;
        if ($onay) {
            $sonOnay[$email] = max($sonOnay[$email] ?? 0, $t);
        }
        if (isset($ilk[$email])) {
            // Onay içeren kayıt onaysız kaydın önüne geçer; aynı türdekilerden en eskisi kalır
            if (($ilk[$email]['onay'] && !$onay) || ($ilk[$email]['onay'] === $onay && $ilk[$email]['zaman'] <= $t)) {
                continue;
            }
        }
        $str = fn(string ...$keys): string => (string) (array_values(array_filter(array_map(fn($k) => is_scalar($d[$k] ?? null) ? trim((string) $d[$k]) : '', $keys), fn($v) => $v !== ''))[0] ?? '');
        $ad = bulten_satir($str('ad', 'isimsoyisim'), 80);
        $soyad = bulten_satir($str('soyad', 'isimsoyisim2'), 80);
        $ilk[$email] = [
            'email'   => $email,
            'ad'      => $ad,
            'soyad'   => $soyad,
            'adsoyad' => trim($ad . ' ' . $soyad),
            'telefon' => bulten_satir($str('telefon'), 40),
            'il'      => bulten_satir($str('il'), 60),
            'sektor'  => bulten_satir($str('sektor', 'unvan'), 160),   // eski "Haberdar ol" formunda sektör yerine ticari unvan vardı
            'konular' => mb_substr($str('mesaj'), 0, 5000),
            'zaman'   => $t,
            'onay'    => $onay,
            'sira'    => $sira,
        ];
    }
    $ayrilan = bulten_ayrilanlar();
    $out = [];
    foreach ($ilk as $email => $a) {
        // Ayrılma kalıcıdır: adres listede durdukça, sonradan yeni kayıt yapılmış olsa da "ayrildi" kalır. Kaydı bir kez silinmiş kişinin
        // tercihi engel listesinde (adres yerine anahtarlı özet) durur: aynı adresle yeniden doldurulan form da aboneliği geri açmaz.
        $ayrildi = $ayrilan[$email] ?? bulten_engel_zamani($email);
        $a['durum'] = $ayrildi ? 'ayrildi' : ($a['onay'] ? 'onayli' : 'onaysiz');
        $a['yeniden'] = $ayrildi && ($sonOnay[$email] ?? 0) > $ayrildi ? $sonOnay[$email] : 0;
        $out[] = $a;
    }
    usort($out, fn($x, $y) => [$y['zaman'], $y['sira']] <=> [$x['zaman'], $x['sira']]);
    foreach ($out as &$a) {
        unset($a['onay'], $a['sira']);
    }
    unset($a);
    return $cache = $out;
}

/** "İmalat (tümü)" kısayolunun kapsadığı sektör mü: adı "İmalat" ile başlayanlar. */
function bulten_imalat_mi(string $sektor): bool
{
    return str_starts_with($sektor, BULTEN_IMALAT);
}

/**
 * İstekten (GET, POST ya da kayıtlı taslak) süzgeç: il[] ve sektor[] çoklu seçim, imalat = "İmalat (tümü)", durum, q = arama.
 * Listede bulunmayan değerler atılmaz (atılsaydı süzgeç sessizce genişlerdi); hiçbir kayıtla eşleşmezler.
 * @return array{il:string[], sektor:string[], imalat:bool, durum:string, q:string}
 */
function bulten_suzgec(array $in): array
{
    $liste = function ($v): array {
        $out = [];
        foreach (is_array($v) ? $v : [] as $x) {
            $x = is_string($x) ? bulten_satir($x, 160) : '';
            if ($x !== '' && !in_array($x, $out, true)) {
                $out[] = $x;
            }
        }
        return array_slice($out, 0, 100);
    };
    $durum = is_string($in['durum'] ?? null) && isset(bulten_durumlar()[$in['durum']]) ? $in['durum'] : '';
    $imalat = $in['imalat'] ?? false;
    return [
        'il'     => $liste($in['il'] ?? []),
        'sektor' => $liste($in['sektor'] ?? []),
        'imalat' => $imalat === true || $imalat === 1 || $imalat === '1',
        'durum'  => $durum,
        'q'      => is_string($in['q'] ?? null) ? bulten_satir($in['q'], 120) : '',
    ];
}

/** Süzgecin adres satırı parametreleri (boş olanlar yazılmaz). */
function bulten_suzgec_sorgu(array $f, array $ek = []): string
{
    $q = array_filter(['il' => $f['il'], 'sektor' => $f['sektor'], 'imalat' => $f['imalat'] ? 1 : null, 'durum' => $f['durum'], 'q' => $f['q']] + $ek, fn($v) => $v !== null && $v !== '' && $v !== []);
    return $q ? '?' . http_build_query($q) : '';
}

/** Süzgecin okunur hali. $aramayiGizle: arama metni kişi adı ya da adres olabileceğinden günlüğe yazılmaz. */
function bulten_suzgec_metni(array $f, bool $aramayiGizle = false): string
{
    $p = [];
    if ($f['il']) {
        $p[] = 'İl: ' . implode(', ', $f['il']);
    }
    if ($f['sektor'] || $f['imalat']) {
        $p[] = 'Sektör: ' . implode(', ', array_merge($f['imalat'] ? [BULTEN_IMALAT . ' (tümü)'] : [], $f['sektor']));
    }
    if ($f['durum'] !== '') {
        $p[] = 'Durum: ' . bulten_durumlar()[$f['durum']];
    }
    if ($f['q'] !== '') {
        $p[] = $aramayiGizle ? 'arama süzgeci' : 'Arama: "' . $f['q'] . '"';
    }
    return $p ? implode(' · ', $p) : 'Tüm aboneler';
}

/** Süzgece uyan aboneler. Sektör süzgeci listedeki değerle birebir eşleşir; serbest metin sektörler arama kutusuyla bulunur. */
function bulten_suz(array $aboneler, array $f): array
{
    $il = array_flip($f['il']);
    $sek = array_flip($f['sektor']);
    $kelimeler = array_values(array_filter(explode(' ', bulten_fold($f['q'])), fn($k) => $k !== ''));
    $out = [];
    foreach ($aboneler as $a) {
        if ($il && !isset($il[$a['il']])) {
            continue;
        }
        if (($sek || $f['imalat']) && !isset($sek[$a['sektor']]) && !($f['imalat'] && bulten_imalat_mi($a['sektor']))) {
            continue;
        }
        if ($f['durum'] !== '' && $a['durum'] !== $f['durum']) {
            continue;
        }
        if ($kelimeler) {
            $hay = bulten_fold($a['adsoyad'] . ' ' . $a['email'] . ' ' . $a['sektor'] . ' ' . $a['konular']);
            foreach ($kelimeler as $k) {
                if (strpos($hay, $k) === false) {
                    continue 2;
                }
            }
        }
        $out[] = $a;
    }
    return $out;
}

/**
 * Süzgece uyan ve e-posta alabilen (durumu "Onaylı") aboneler. Adres başına tek kayıt vardır; liste yinelenmez.
 * @return array<int, array{email:string, ad:string, soyad:string}>
 */
function bulten_alicilar(array $f): array
{
    $f['durum'] = 'onayli';
    return array_map(fn($a) => ['email' => $a['email'], 'ad' => $a['ad'], 'soyad' => $a['soyad']], bulten_suz(bulten_aboneler(), $f));
}

/* =========================================================================
   Abonelikten ayrılma
   ========================================================================= */

/**
 * Ayrılma bağlantılarının imza anahtarı: form güvenlik anahtarından "bulten-ayril" etiketiyle türetilir. Formların belirteçleri ve hız sınırı
 * dosyaları aynı ham anahtarı kullandığından, ayrılma imzası onlarla aynı anahtara bağlı kalmasın diye amaca özel anahtar kullanılır.
 */
function bulten_ayril_anahtari(string $anahtar): string
{
    return hash_hmac('sha256', 'bulten-ayril', $anahtar);
}

/**
 * Adresin ayrılma imzası. Yeni bağlantılar her zaman şimdiki anahtardan türetilen anahtarla imzalanır; eski (türetmesiz) imzalar yalnızca
 * doğrulanır (bkz. bulten_imza_gecerli).
 */
function bulten_imza(string $email, ?string $anahtar = null): string
{
    return hash_hmac('sha256', $email, bulten_ayril_anahtari($anahtar ?? (string) cfg('secret')));
}

/** config.php ile gelen varsayılan form güvenlik anahtarı: herkesçe bilindiği için ayrılma bağlantılarında hiçbir zaman geçerli sayılmaz. */
function bulten_varsayilan_anahtar(): string
{
    static $v = null;
    return $v ??= (string) (((array) require APP . '/config.php')['secret'] ?? '');
}

/** Siteye özel bir form güvenlik anahtarı var mı? Yoksa (storage yazılamıyorsa) ayrılma bağlantıları imzalanamaz; gönderim başlatılmaz. */
function bulten_anahtar_hazir(): bool
{
    $k = (string) cfg('secret');
    return $k !== '' && $k !== bulten_varsayilan_anahtar();
}

/**
 * İmzalamada kullanılan anahtarı saklar: form güvenlik anahtarı yenilense de gönderilmiş e-postalardaki ayrılma bağlantıları çalışmaya devam eder.
 * Varsayılan anahtar hiçbir zaman saklanmaz.
 */
function bulten_imza_arsivle(): void
{
    $k = (string) cfg('secret');
    $file = BULTEN_DIR . '/imza.json';
    if (!bulten_anahtar_hazir() || in_array($k, (array) (bulten_oku($file)['anahtarlar'] ?? []), true)) {
        return;
    }
    bulten_kilit('imza', function () use ($k): int {
        $file = bulten_dir() . '/imza.json';
        $list = array_values(array_filter((array) (bulten_oku($file)['anahtarlar'] ?? []), fn($x) => is_string($x) && $x !== '' && $x !== $k));
        $list[] = $k;
        // Son 12 anahtar saklanır; ayrıca engel listesindeki bir girdinin üretildiği anahtar (kaydı silinen kişinin ayrılma tercihi) hiç atılmaz
        $gerekli = array_unique(array_map(fn($g) => explode(':', $g)[0], array_keys(bulten_engel_listesi())));
        $tut = array_slice($list, -12);
        foreach (array_slice($list, 0, -12) as $x) {
            if (in_array(bulten_engel_kimlik($x), $gerekli, true)) {
                $tut[] = $x;
            }
        }
        return bulten_yaz($file, ['anahtarlar' => array_values(array_unique($tut))]) ? 1 : 0;
    });
}

function bulten_imza_gecerli(string $email, string $k): bool
{
    if (!preg_match('/^[a-f0-9]{64}$/D', $k)) {
        return false;
    }
    $anahtarlar = array_merge([(string) cfg('secret')], array_filter((array) (bulten_oku(BULTEN_DIR . '/imza.json')['anahtarlar'] ?? []), 'is_string'));
    $varsayilan = bulten_varsayilan_anahtar();
    foreach (array_unique($anahtarlar) as $a) {
        // Varsayılan anahtar herkesçe bilinir: onunla üretilmiş imza (dosyada eskiden kalmış olsa bile) kabul edilmez
        if ($a === '' || $a === $varsayilan) {
            continue;
        }
        // Türetilmiş anahtarla üretilen imza (yeni bağlantılar) ya da bu sürümden önce gönderilmiş e-postalardaki ham anahtarla üretilen imza:
        // ayrılma hakkı yasal zorunluluk olduğundan eski e-postalardaki bağlantılar çalışmaya devam eder. Yeni bağlantılar eski biçimde üretilmez.
        if (hash_equals(bulten_imza($email, $a), $k) || hash_equals(hash_hmac('sha256', $email, $a), $k)) {
            return true;
        }
    }
    return false;
}

/** Her alıcıya özel ayrılma bağlantısı: e = adresin adrese uygun base64 hali, k = adresin imzası. */
function bulten_ayril_adresi(string $email): string
{
    $email = strtolower(trim($email));
    return absolute_url('bulten/ayril') . '?e=' . rtrim(strtr(base64_encode($email), '+/', '-_'), '=') . '&k=' . bulten_imza($email);
}

/** Bağlantıdaki e ve k değerlerinden adres; imza geçersizse null. */
function bulten_ayril_coz(string $e, string $k): ?string
{
    if ($e === '' || strlen($e) > 400 || !preg_match('/^[A-Za-z0-9_\-]+$/D', $e)) {
        return null;
    }
    $email = base64_decode(strtr($e, '-_', '+/'), true);
    if (!is_string($email) || $email !== strtolower($email) || !mail_address_ok($email)) {
        return null;
    }
    return bulten_imza_gecerli($email, $k) ? $email : null;
}

/**
 * Adresi abonelikten çıkarır. Ayrılma kalıcıdır: adres, yönetici panelden yeniden abone yapana kadar listede kalır
 * (bkz. bulten_yeniden_abone); zaten ayrılmış adres için bir şey değişmez.
 * Günlüğe adres yazılmaz. @return bool yeni bir ayrılma kaydedildiyse true
 */
function bulten_ayril(string $email, string $nasil): bool
{
    $email = strtolower(trim($email));
    if (!mail_address_ok($email)) {
        return false;
    }
    $yeni = (bool) bulten_kilit('ayrilanlar', function () use ($email): int {
        $file = bulten_dir() . '/ayrilanlar.json';
        $list = bulten_oku($file);
        if ((isset($list[$email]) && (int) strtotime((string) $list[$email]) > 0) || bulten_engel_zamani($email) > 0) {
            return 0;   // zaten ayrılmış (adres açık ya da engel listesinde)
        }
        $list[$email] = date('c');
        return bulten_yaz($file, $list) ? 1 : 0;
    });
    if ($yeni) {
        bulten_aboneler(true);
        changelog_event('bulten', 'Bir abone bültenden ayrıldı (' . $nasil . ')');
    }
    return $yeni;
}

/**
 * Abonelikten ayrılmış adresi yeniden abone yapar: adres ayrılanlar listesinden çıkarılır ve yeniden e-posta alır.
 * Yalnızca yönetim panelinden, kişinin isteği üzerine yapılır; sitedeki form bunu kendiliğinden yapmaz.
 * Günlüğe adres yazılmaz. @return bool adres listedeydi ve çıkarıldıysa true
 */
function bulten_yeniden_abone(string $email): bool
{
    $email = strtolower(trim($email));
    if ($email === '') {
        return false;
    }
    $oldu = (bool) bulten_kilit('ayrilanlar', function () use ($email): int {
        $file = bulten_dir() . '/ayrilanlar.json';
        $list = bulten_oku($file);
        $n = 0;
        if (isset($list[$email])) {
            unset($list[$email]);
            $n += bulten_yaz($file, $list) ? 1 : 0;
        }
        // Engel listesindeki girdi de (hangi anahtarla yazılmış olursa olsun) temizlenir: yeniden abone yapmak tercihi tümüyle geri alır
        $engel = bulten_oku(bulten_dir() . '/bastirilanlar.json');
        $degisti = false;
        foreach (bulten_engel_anahtarlari() as $a) {
            $g = bulten_engel_girdi($email, $a);
            if (isset($engel[$g])) {
                unset($engel[$g]);
                $degisti = true;
            }
        }
        if ($degisti && bulten_yaz(bulten_dir() . '/bastirilanlar.json', $engel)) {
            $n++;
        }
        return $n;
    });
    if ($oldu) {
        bulten_aboneler(true);
        changelog_event('bulten', 'Abonelikten ayrılmış bir adres yönetim panelinden yeniden abone yapıldı');
    }
    return $oldu;
}

/**
 * Bir kişinin bülten kaydı Form kayıtlarından silindikten sonra (verisinin silinmesini isteyen kişi) bülten deposunda kalan izlerini temizler.
 * Adresin başka bir bülten kaydı duruyorsa (şüpheli olarak ayrılmış olanlar dahil) hiçbir şey yapılmaz: ayrılma kaydı silinseydi,
 * duran kayıt sonradan gelen kutusuna alındığında adres yeniden e-posta almaya başlardı. Hiç kaydı kalmadıysa adres ayrılanlar
 * listesinden çıkarılır ve yerine adresin anahtarlı özeti (HMAC) engel listesine yazılır: kişi bir daha e-posta almaz, ama adresin kendisi
 * saklanmaz; aynı adresle sonradan yapılan kayıt "ayrıldı" görünür. Gönderim kayıtlarında adı ve adresi silinir (sayılar ve durumlar kalır), sırada bekliyorsa atlanır.
 * Kaydı silen işlev (adm_form_record_delete) çağırır.
 */
function bulten_adres_unut(string $email): void
{
    $email = strtolower(trim($email));
    if ($email === '') {
        return;
    }
    foreach (spam_records() as $r) {
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        if (in_array($r['form'] ?? '', ['bulten', 'haberdarol'], true) && is_scalar($d['email'] ?? null) && strtolower(trim((string) $d['email'])) === $email) {
            return;
        }
    }
    bulten_kilit('ayrilanlar', function () use ($email): int {
        $file = bulten_dir() . '/ayrilanlar.json';
        $list = bulten_oku($file);
        if (!isset($list[$email])) {
            return 0;
        }
        // Silme isteğine uyulur (adres saklanmaz) ama ayrılma tercihi etkisini korur: adres yerine anahtarlı özeti engel listesine geçer.
        // Önce engel listesi yazılır, sonra adres çıkarılır: yarıda kesilirse tercih kaybolmaz.
        $engelDosya = bulten_dir() . '/bastirilanlar.json';
        $engel = bulten_oku($engelDosya);
        $engel[bulten_engel_girdi($email, (string) cfg('secret'))] = (string) $list[$email];
        if (!bulten_yaz($engelDosya, $engel)) {
            return 0;
        }
        unset($list[$email]);
        return bulten_yaz($file, $list) ? 1 : 0;
    });
    bulten_imza_arsivle();   // engel listesindeki özet bu anahtarla yazıldı: anahtar sonradan yenilense de bulunabilsin diye saklanır
    $iz = '"email":' . json_encode($email, bulten_json_flags());
    foreach (glob(bulten_dir('gonderimler') . '/*.json') ?: [] as $f) {
        $id = basename($f, '.json');
        if (!bulten_id_gecerli($id) || !str_contains((string) @file_get_contents($f), $iz)) {
            continue;
        }
        bulten_kilit('g-' . $id, function () use ($id, $email): int {
            $c = bulten_gonderim($id);
            if ($c === null) {
                return 0;
            }
            foreach ($c['recipients'] as $i => $r) {
                if (($r['email'] ?? '') !== $email) {
                    continue;
                }
                $bekliyordu = ($r['status'] ?? '') === 'bekliyor';
                $c['recipients'][$i] = array_merge($r, ['email' => '(silindi)', 'ad' => '', 'soyad' => ''],
                    $bekliyordu ? ['status' => 'atlandi', 'time' => date('c'), 'error' => 'Kaydı silindi.'] : []);
            }
            return bulten_yaz(bulten_gonderim_dosya($id), $c) ? 1 : 0;
        });
    }
}

/** /bulten/ayril: GET onay sayfasını gösterir, POST abonelikten çıkarır; "List-Unsubscribe=One-Click" taşıyan POST sayfasız çalışır (RFC 8058). */
function bulten_ayril_sayfasi(): void
{
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    $al = fn(string $k): string => is_string($_GET[$k] ?? null) ? $_GET[$k] : (is_string($_POST[$k] ?? null) ? $_POST[$k] : '');
    $e = $al('e');
    $k = $al('k');
    $email = bulten_ayril_coz($e, $k);
    $post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

    // E-posta hizmetlerinin (Gmail, Yahoo...) "abonelikten çık" düğmesi: gövdesinde yalnızca bu alan bulunan bir POST gönderir
    if ($post && ($_POST['List-Unsubscribe'] ?? '') === 'One-Click') {
        header('Content-Type: text/plain; charset=utf-8');
        if ($email === null) {
            http_response_code(400);
            echo "Bağlantı geçerli değil.\n";
            return;
        }
        bulten_ayril($email, 'e-posta uygulamasındaki düğmeyle');
        echo "Abonelikten çıkarıldınız.\n";
        return;
    }

    $vars = ['durum' => 'gecersiz', 'adres' => '', 'aksiyon' => ''];
    if ($email !== null) {
        if ($post) {
            // Yeni bir ayrılma kaydedildiyse "tamam"; adres zaten ayrılmışsa (çift tıklama, sayfanın yenilenmesi) "zaten"
            $vars = ['durum' => bulten_ayril($email, 'e-postadaki bağlantıyla') ? 'tamam' : 'zaten', 'adres' => $email, 'aksiyon' => '', 'tarih' => bulten_ayrilma_zamani($email) ?: time()];
        } elseif (bulten_ayrilma_zamani($email) > 0) {
            $vars = ['durum' => 'zaten', 'adres' => $email, 'aksiyon' => '', 'tarih' => bulten_ayrilma_zamani($email)];   // GET hiçbir şeyi değiştirmez
        } else {
            $vars = ['durum' => 'onay', 'adres' => $email, 'aksiyon' => url('bulten/ayril') . '?e=' . rawurlencode($e) . '&k=' . rawurlencode($k)];
        }
    } elseif (!$post && $e === '' && $k === '' && isset($_GET['onizleme'])) {
        // Paneldeki "Sitede gör" bağlantısı: sayfanın görünüşü, örnek bir adresle (düğme bir şey yapmaz)
        $vars = ['durum' => 'onay', 'adres' => 'ornek@firma.com', 'aksiyon' => url('bulten/ayril') . '?onizleme=1'];
    } else {
        http_response_code(400);
    }
    render('bulten-ayril', $vars);
}

/* =========================================================================
   E-posta: gövde, düz metin, kişiselleştirme, şablon
   ========================================================================= */

/** Editörden ya da yapay zekâdan gelen gövdeyi temizler: izinli etiketler, boş paragraflar atılır, site içi bağlantılar tam adrese çevrilir. */
function bulten_govde_temizle(string $html): string
{
    $h = sanitize_html(mb_substr($html, 0, BULTEN_GOVDE_MAX));
    $h = (string) preg_replace('#<p>(?:\s|&nbsp;|\x{00A0}|<br\s*/?>)*</p>#u', '', $h);
    $h = (string) preg_replace_callback('#<a href="([^"]*)"#', function (array $m): string {
        $u = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
        if ($u !== '' && $u[0] === '/') {
            return '<a href="' . e(absolute_url(ltrim($u, '/'))) . '"';
        }
        return $u !== '' && $u[0] === '#' ? '<a' : $m[0];   // sayfa içi bağlantı e-postada çalışmaz
    }, $h);
    return trim($h);
}

/** Gövdenin düz metin sürümü: başlıklar ve paragraflar boş satırla ayrılır, listeler "- " ile, bağlantılar "yazı (adres)" olarak yazılır. */
function bulten_duz_metin(string $html): string
{
    $doc = new DOMDocument('1.0', 'UTF-8');
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $root = $doc->getElementById('__root');
    if (!$root) {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8'));
    }
    $walk = function (DOMNode $n) use (&$walk): string {
        $out = '';
        foreach ($n->childNodes as $c) {
            if ($c instanceof DOMText) {
                $out .= (string) preg_replace('/[\s\x{00A0}]+/u', ' ', (string) $c->nodeValue);
                continue;
            }
            if (!$c instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($c->tagName);
            if ($tag === 'br') {
                $out .= "\n";
            } elseif ($tag === 'a') {
                $t = trim($walk($c));
                $h = trim($c->getAttribute('href'));
                $yalin = (string) preg_replace('#^(mailto:|tel:)#i', '', $h);
                $out .= $h === '' || $t === $h || $t === $yalin ? ($t !== '' ? $t : $h) : ($t === '' ? $h : $t . ' (' . $h . ')');
            } elseif ($tag === 'ul' || $tag === 'ol') {
                $i = 0;
                $out .= "\n\n";
                foreach ($c->childNodes as $li) {
                    if ($li instanceof DOMElement && strtolower($li->tagName) === 'li') {
                        $out .= ($tag === 'ol' ? ++$i . '. ' : '- ') . trim((string) preg_replace('/\n{2,}/', "\n", $walk($li))) . "\n";
                    }
                }
                $out .= "\n";
            } elseif ($tag === 'blockquote') {
                $out .= "\n\n" . implode("\n", array_map(fn($l) => '> ' . $l, explode("\n", trim((string) preg_replace('/\n{2,}/', "\n", $walk($c)))))) . "\n\n";
            } elseif (in_array($tag, ['p', 'h2', 'h3', 'li'], true)) {
                $out .= "\n\n" . trim($walk($c)) . "\n\n";
            } else {
                $out .= $walk($c);
            }
        }
        return $out;
    };
    $t = implode("\n", array_map('trim', explode("\n", $walk($root))));
    return trim((string) preg_replace('/\n{3,}/', "\n\n", $t));
}

/** {ad} ve {soyad} yer tutucularını alıcının bilgisiyle değiştirir; HTML içinde değerler kaçışlanır. */
function bulten_kisisellestir(string $s, array $alici, bool $html): string
{
    $ad = bulten_satir((string) ($alici['ad'] ?? ''), 80);
    $soyad = bulten_satir((string) ($alici['soyad'] ?? ''), 80);
    return str_replace(['{ad}', '{soyad}'], $html ? [e($ad), e($soyad)] : [$ad, $soyad], $s);
}

/**
 * Tam HTML e-posta. Yalnızca satır içi stiller; dış görsel ya da yazı tipi yok; en fazla 600 piksel genişlik.
 * Yazı ve zemin rengi verilmez: açık ve koyu temalı e-posta uygulamalarında uygulamanın kendi renkleriyle okunur.
 */
function bulten_html_sablon(string $konu, string $govde, string $ayril): string
{
    $govde = strtr($govde, [
        '<p>'          => '<p style="margin:0 0 16px">',
        '<h2>'         => '<h2 style="margin:28px 0 10px;font-size:22px;line-height:1.25;font-weight:bold">',
        '<h3>'         => '<h3 style="margin:22px 0 8px;font-size:17px;line-height:1.35">',
        '<ul>'         => '<ul style="margin:0 0 16px;padding:0 0 0 22px">',
        '<ol>'         => '<ol style="margin:0 0 16px;padding:0 0 0 22px">',
        '<li>'         => '<li style="margin:0 0 6px">',
        '<blockquote>' => '<blockquote style="margin:0 0 16px;padding:2px 0 2px 14px;border-left:3px solid #cf412f">',
        '<a '          => '<a style="text-decoration:underline" ',
    ]);
    $a = 'style="text-decoration:underline"';
    $eposta = (string) cfg('email');
    $iletisim = array_filter([
        cfg('phone') ? e(t('bulten.posta.telefon')) . ' <a ' . $a . ' href="tel:' . e((string) cfg('phone_href')) . '">' . e((string) cfg('phone')) . '</a>' : '',
        $eposta !== '' ? e(t('bulten.posta.eposta')) . ' <a ' . $a . ' href="mailto:' . e($eposta) . '">' . e($eposta) . '</a>' : '',
    ]);
    $serif = "Georgia,'Times New Roman',Times,serif";
    $sans = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
    $ad = e((string) cfg('name'));
    // Yazı ve zemin rengi verilmez (uygulama açık ya da koyu temasına göre kendisi seçer); çizgiler ve vurgu iki zeminde de görünen orta tonlardır
    return '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="color-scheme" content="light dark"><meta name="supported-color-schemes" content="light dark"><title>' . e($konu) . '</title></head>'
        . '<body style="margin:0;padding:0">'
        . '<div style="max-width:600px;margin:0 auto;padding:24px 20px;font-family:' . $serif . ';font-size:17px;line-height:1.6">'
        // Künye satırı: gazete başlığı gibi, altında çift çizgi (üstü kalın kırmızı, altı ince)
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;border-top:3px solid #cf412f;border-bottom:1px solid #9aa3b2"><tr>'
        . '<td style="padding:10px 12px 9px 0;font-family:' . $sans . ';font-size:13px;font-weight:bold;letter-spacing:.04em;text-transform:uppercase">' . $ad . '</td>'
        . '<td align="right" style="padding:10px 0 9px;font-family:' . $serif . ';font-size:13px;font-style:italic;white-space:nowrap">' . e(today_official()) . '</td>'
        . '</tr></table>'
        . '<div style="padding:26px 0 6px">' . $govde . '</div>'
        . '<div style="border-top:1px solid #9aa3b2;margin:18px 0 0;padding:16px 0 0;font-family:' . $sans . ';font-size:13px;line-height:1.55">'
        . '<p style="margin:0 0 12px"><strong>' . $ad . '</strong><br>' . e((string) cfg('address')) . ($iletisim ? '<br>' . implode(' · ', $iletisim) : '') . '</p>'
        . '<p style="margin:0 0 10px">' . e(bulten_posta_metinleri()['neden']) . '</p>'
        . '<p style="margin:0">' . e(bulten_posta_metinleri()['ayril']) . ' <a ' . $a . ' href="' . e($ayril) . '">' . e(bulten_posta_metinleri()['baglanti']) . '</a></p>'
        . '</div></div></body></html>';
}

/**
 * E-postanın altbilgisindeki cümleler: Sayfa metinleri kayıt defterinden (bulten.posta.*). Yasal bilgilendirmedir; cümleler kaldırılmamalıdır.
 * @return array{neden:string, ayril:string, baglanti:string}
 */
function bulten_posta_metinleri(): array
{
    return [
        'neden'    => t('bulten.posta.neden'),     // e-postanın neden geldiğini açıklayan cümle
        'ayril'    => t('bulten.posta.ayril'),     // ayrılma bağlantısından önceki cümle (bağlantı hemen ardına eklenir)
        'baglanti' => t('bulten.posta.baglanti'),  // ayrılma bağlantısının yazısı
    ];
}

/** Düz metin sürümünün altbilgisi (HTML sürümündekiyle aynı bilgiler). */
function bulten_metin_altbilgi(string $ayril): string
{
    $iletisim = array_filter([cfg('phone') ? t('bulten.posta.telefon') . ' ' . cfg('phone') : '', cfg('email') ? t('bulten.posta.eposta') . ' ' . cfg('email') : '']);
    return "\n\n" . str_repeat('-', 40) . "\n" . cfg('name') . "\n" . cfg('address') . ($iletisim ? "\n" . implode(' | ', $iletisim) : '')
        . "\n\n" . bulten_posta_metinleri()['neden'] . "\n" . bulten_posta_metinleri()['ayril'] . ' ' . $ayril . "\n";
}

/**
 * Bir alıcıya gidecek ileti: konu, düz metin, HTML ve ek başlıklar.
 * @param array $c gönderim (subject, html, text) @param array $alici (email, ad, soyad)
 * @return array{konu:string, metin:string, html:string, basliklar:array<string,string>}
 */
function bulten_eposta(array $c, array $alici): array
{
    $ayril = bulten_ayril_adresi((string) $alici['email']);
    $konu = bulten_satir(bulten_kisisellestir((string) ($c['subject'] ?? ''), $alici, false), BULTEN_KONU_MAX + 160);
    $govde = (string) ($c['html'] ?? '');
    $metin = (string) ($c['text'] ?? '') !== '' ? (string) $c['text'] : bulten_duz_metin($govde);
    // Ticari iletide ücretsiz ve kolay ret imkânı: bağlantı (tek tıkla çalışır) ve şirket adresine e-posta
    $liste = '<' . $ayril . '>';
    $sirket = (string) cfg('email');
    if (mail_address_ok($sirket)) {
        $liste .= ', <mailto:' . str_replace('%40', '@', rawurlencode($sirket)) . '?subject=' . rawurlencode('Bülten aboneliğinden ayrıl') . '>';
    }
    $alan = (string) preg_replace('/^.*@/', '', (string) cfg('mail.from'));
    return [
        'konu'  => $konu,
        'metin' => bulten_kisisellestir($metin, $alici, false) . bulten_metin_altbilgi($ayril),
        'html'  => bulten_html_sablon($konu, bulten_kisisellestir($govde, $alici, true), $ayril),
        'basliklar' => [
            'List-Unsubscribe'      => $liste,
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            'Message-ID'            => '<' . bin2hex(random_bytes(12)) . '.' . time() . '@' . (preg_match('/^[A-Za-z0-9.\-]+$/D', $alan) ? $alan : 'localhost') . '>',
        ],
    ];
}

/** Tek bir alıcıya gönderir: multipart/alternative, Yanıtla adresi şirketin e-postası. Hata nedeni mail_last_error() ile okunur. */
function bulten_gonder_tek(array $c, array $alici, bool $deneme = false): bool
{
    require_once APP . '/mailer.php';
    $m = bulten_eposta($c, $alici);
    $yanit = mail_address_ok((string) cfg('email')) ? (string) cfg('email') : null;
    return send_mail((string) $alici['email'], ($deneme ? '[Deneme] ' : '') . $m['konu'], $m['metin'], $yanit, [], ['html' => $m['html'], 'headers' => $m['basliklar'], 'from_name' => (string) cfg('name')]);
}

/**
 * Konu ve gövde denetimi. $taslak: yarım iş de kaydedilebilsin diye boş konu ya da boş metin hata sayılmaz.
 * @return array{0:string, 1:string, 2:string[]} [konu, temizlenmiş gövde, hatalar]
 */
function bulten_icerik(string $konu, string $govde, bool $taslak = false): array
{
    $hata = [];
    $konu = bulten_satir($konu, 1000);
    if ($konu === '' && !$taslak) {
        $hata[] = 'Konu satırını yazın.';
    } elseif (mb_strlen($konu) > BULTEN_KONU_MAX) {
        $hata[] = 'Konu en fazla ' . BULTEN_KONU_MAX . ' karakter olabilir (şu an ' . mb_strlen($konu) . ').';
    }
    if (mb_strlen($govde) > BULTEN_GOVDE_MAX) {
        $hata[] = 'E-posta metni çok uzun; en fazla ' . BULTEN_GOVDE_MAX . ' karakter olabilir.';
    }
    $html = bulten_govde_temizle($govde);
    if (!$taslak && preg_replace('/[\s\x{00A0}]+/u', '', html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')) === '') {
        $hata[] = 'E-posta metnini yazın.';
    }
    return [$konu, $html, $hata];
}

/* =========================================================================
   Gönderimler ve taslaklar
   ========================================================================= */

function bulten_durum_adlari(): array
{
    return ['taslak' => 'Taslak', 'gonderiliyor' => 'Gönderiliyor', 'duraklatildi' => 'Duraklatıldı', 'tamamlandi' => 'Tamamlandı'];
}

function bulten_id_gecerli(string $id): bool
{
    return (bool) preg_match('/^[a-f0-9]{12}$/D', $id);
}

function bulten_yeni_id(): string
{
    do {
        $id = bin2hex(random_bytes(6));
    } while (is_file(bulten_gonderim_dosya($id)));
    return $id;
}

function bulten_gonderim_dosya(string $id): string
{
    return bulten_dir('gonderimler') . '/' . $id . '.json';
}

function bulten_gonderim(string $id): ?array
{
    if (!bulten_id_gecerli($id)) {
        return null;
    }
    $c = bulten_oku(bulten_gonderim_dosya($id));
    if (($c['id'] ?? '') !== $id || !isset(bulten_durum_adlari()[$c['state'] ?? ''])) {
        return null;
    }
    $c['recipients'] = array_values(array_filter((array) ($c['recipients'] ?? []), 'is_array'));
    $c['filter'] = bulten_suzgec(is_array($c['filter'] ?? null) ? $c['filter'] : []);
    return $c;
}

/** Taslak sayısı. Dosyaların yalnızca başı okunur (kayıtlar "id" ve "state" ile başlar); alıcı listeleri belleğe alınmaz. */
function bulten_taslak_sayisi(): int
{
    $n = 0;
    foreach (glob(bulten_dir('gonderimler') . '/*.json') ?: [] as $f) {
        if (str_contains((string) @file_get_contents($f, false, null, 0, 96), '"state":"taslak"')) {
            $n++;
        }
    }
    return $n;
}

/** Tüm gönderimler ve taslaklar, en yeni önce. */
function bulten_gonderimler(): array
{
    $out = [];
    foreach (glob(bulten_dir('gonderimler') . '/*.json') ?: [] as $f) {
        $c = bulten_gonderim(basename($f, '.json'));
        if ($c !== null) {
            $out[] = $c;
        }
    }
    usort($out, fn($a, $b) => strcmp((string) ($b['updated'] ?? $b['created'] ?? ''), (string) ($a['updated'] ?? $a['created'] ?? '')));
    return $out;
}

/** @return array{toplam:int, bekliyor:int, gonderiliyor:int, gonderildi:int, hata:int, atlandi:int} */
function bulten_sayilar(array $c): array
{
    $n = ['toplam' => 0, 'bekliyor' => 0, 'gonderiliyor' => 0, 'gonderildi' => 0, 'hata' => 0, 'atlandi' => 0];
    foreach ((array) ($c['recipients'] ?? []) as $r) {
        $n['toplam']++;
        $s = (string) ($r['status'] ?? '');
        if (isset($n[$s]) && $s !== 'toplam') {
            $n[$s]++;
        }
    }
    return $n;
}

/**
 * Taslağı kaydeder (yeni ya da var olan taslak). Gönderime alınmış bir kaydın üzerine yazmaz.
 * Taslağı ilk hazırlayan (actor(): panel ya da yapay zekâ erişimi) kayıtta saklanır ve panelde görünür.
 * @return array|string kayıt ya da hata metni
 */
function bulten_taslak_kaydet(string $id, string $konu, string $html, array $f)
{
    if (!bulten_id_gecerli($id)) {
        return 'Geçersiz taslak kimliği.';
    }
    $f = bulten_suzgec($f);
    $f['durum'] = '';   // alıcılar her zaman yalnızca "Onaylı" abonelerdir
    return bulten_kilit('g-' . $id, function () use ($id, $konu, $html, $f) {
        $eski = bulten_gonderim($id);
        if ($eski !== null && $eski['state'] !== 'taslak') {
            return 'Bu e-posta gönderime alınmış; artık taslak olarak değiştirilemez.';
        }
        $c = [
            'id'          => $id,
            'state'       => 'taslak',
            'subject'     => $konu,
            'html'        => $html,
            'text'        => bulten_duz_metin($html),
            'filter'      => $f,
            'filter_desc' => bulten_suzgec_metni($f),
            'created'     => (string) ($eski['created'] ?? date('c')),
            'updated'     => date('c'),
            'who'         => $eski['who'] ?? actor(),   // ilk hazırlayan
            'edited_by'   => actor(),                   // son yazan: panel ya da hangi yapay zekâ erişimi (yeniden yazan değişirse güncellenir)
            'recipients'  => [],
        ];
        return bulten_yaz(bulten_gonderim_dosya($id), $c) ? $c : 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    });
}

/**
 * Gönderimi başlatır: alıcı listesi bu anda dondurulur (süzgece uyan, durumu "Onaylı" aboneler).
 * Aynı kimlikle ikinci kez çağrılırsa (çift tıklama, iki sekme) yeni gönderim oluşmaz.
 * @param int|null $beklenen ekranda gösterilen alıcı sayısı; liste o arada değiştiyse gönderim başlamaz
 * @return array|string gönderim ya da hata metni
 */
function bulten_baslat(string $id, string $konu, string $html, array $f, ?int $beklenen = null)
{
    if (!bulten_id_gecerli($id)) {
        return 'Geçersiz gönderim kimliği.';
    }
    if (!bulten_anahtar_hazir()) {
        // Varsayılan anahtarla imzalanan ayrılma bağlantısı kabul edilmez: bağlantısı çalışmayan e-posta gönderilmez
        return 'Gönderim başlatılamıyor: siteye özel form güvenlik anahtarı yok, bu yüzden e-postalardaki abonelikten ayrılma bağlantıları çalışmaz. Güvenlik ve yedek bölümünden yeni anahtar oluşturun (storage klasörü yazılabilir olmalı).';
    }
    $f = bulten_suzgec($f);
    $f['durum'] = '';
    $sonuc = bulten_kilit('g-' . $id, function () use ($id, $konu, $html, $f, $beklenen) {
        $eski = bulten_gonderim($id);
        if ($eski !== null && $eski['state'] !== 'taslak') {
            return 'Bu gönderim zaten başlatılmış.';
        }
        bulten_aboneler(true);
        $alicilar = bulten_alicilar($f);
        if (!$alicilar) {
            return 'Bu süzgece uyan ve e-posta alabilen abone yok.';
        }
        if ($beklenen !== null && $beklenen !== count($alicilar)) {
            return 'Alıcı listesi az önce değişti: şu an ' . count($alicilar) . ' alıcı var. Sayıyı kontrol edip gönderimi yeniden başlatın.';
        }
        $c = [
            'id'          => $id,
            'state'       => 'gonderiliyor',
            'subject'     => $konu,
            'html'        => $html,
            'text'        => bulten_duz_metin($html),
            'filter'      => $f,
            'filter_desc' => bulten_suzgec_metni($f),
            'created'     => date('c'),
            'updated'     => date('c'),
            'finished'    => '',
            'who'         => actor(),
            'draft_by'    => $eski['edited_by'] ?? $eski['who'] ?? null,   // taslağı son yazan: gönderimi onaylayan kişi metnin kimden geldiğini görür
            'recipients'  => array_map(fn($a) => $a + ['status' => 'bekliyor', 'time' => '', 'error' => ''], $alicilar),
        ];
        return bulten_yaz(bulten_gonderim_dosya($id), $c) ? $c : 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    });
    if (is_array($sonuc)) {
        bulten_imza_arsivle();
        changelog_event('bulten', 'Bülten e-postası gönderimi başlatıldı: "' . mb_substr($konu, 0, 90) . '" (' . count($sonuc['recipients']) . ' alıcı; ' . mb_substr(bulten_suzgec_metni($f, true), 0, 90) . ')');
    }
    return $sonuc;
}

/**
 * Gönderimin durumunu değiştirir: duraklat, surdur, yeniden (hata alan alıcılar yeniden sıraya girer).
 * O sırada çalışan bir parti varsa bitmesi beklenir. @return array|string gönderim ya da hata metni
 */
function bulten_durum_degistir(string $id, string $islem)
{
    if (!bulten_id_gecerli($id)) {
        return 'Gönderim bulunamadı.';
    }
    return bulten_kilit('g-' . $id, function () use ($id, $islem) {
        $c = bulten_gonderim($id);
        if ($c === null || $c['state'] === 'taslak') {
            return 'Gönderim bulunamadı.';
        }
        if ($islem === 'duraklat' && $c['state'] === 'gonderiliyor') {
            $c['state'] = 'duraklatildi';
        } elseif ($islem === 'surdur' && $c['state'] === 'duraklatildi') {
            $c['state'] = 'gonderiliyor';
        } elseif ($islem === 'yeniden' && bulten_sayilar($c)['hata'] > 0) {
            foreach ($c['recipients'] as $i => $r) {
                if (($r['status'] ?? '') === 'hata') {
                    $c['recipients'][$i] = array_merge($r, ['status' => 'bekliyor', 'time' => '', 'error' => '']);
                }
            }
            $c['state'] = 'gonderiliyor';
            $c['finished'] = '';
        } else {
            return $c;   // yapılacak bir şey yok (ör. zaten duraklatılmış)
        }
        unset($c['pause_note']);
        $c['updated'] = date('c');
        return bulten_yaz(bulten_gonderim_dosya($id), $c) ? $c : 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    });
}

/** Taslağı ya da (gönderimi sürmeyen) bir gönderim kaydını siler. @return array|null silinen kayıt */
function bulten_sil(string $id): ?array
{
    if (!bulten_id_gecerli($id)) {
        return null;
    }
    $silinen = bulten_kilit('g-' . $id, function () use ($id) {
        $c = bulten_gonderim($id);
        if ($c === null || $c['state'] === 'gonderiliyor') {
            return false;
        }
        return @unlink(bulten_gonderim_dosya($id)) ? $c : false;
    });
    if (!is_array($silinen)) {
        return null;
    }
    // Kilit dosyası (.g-{id}.lock) bilerek silinmez: o anda aynı kimlikle çalışan başka bir istek eski dosyayı, sonraki istek
    // yeni dosyayı kilitleyip aynı gönderimi birlikte yürütebilirdi. Dosya boştur, yer tutmaz.
    if ($silinen['state'] !== 'taslak') {
        changelog_event('bulten', 'Bülten gönderimi kaydı silindi: "' . mb_substr((string) $silinen['subject'], 0, 120) . '"');
    }
    return $silinen;
}

/** Parti uç noktasının yanıtı: durum ve sayılar. */
function bulten_ilerleme(array $c, array $ek = []): array
{
    if (isset($ek['bekle'])) {
        $ek['bekle_dk'] = (int) ceil($ek['bekle'] / 60);   // sayfa bu kadar dakika bekler ve kendiliğinden devam eder
    }
    return ['ok' => true, 'state' => (string) $c['state'], 'sayilar' => bulten_sayilar($c), 'saat' => bulten_saat_durum()] + $ek;
}

/**
 * Bir parti gönderir (en fazla BULTEN_PARTI e-posta) ve ilerlemeyi döndürür.
 * Yanıttaki ek alanlar: mesgul (başka bir istek şu an gönderiyor), bekle (saatlik sınır dolu: saniye), engel (gönderim yapılamıyor: neden).
 */
function bulten_parti(string $id): array
{
    if (!bulten_id_gecerli($id)) {
        return ['ok' => false, 'hata' => 'Gönderim bulunamadı.'];
    }
    require_once APP . '/mailer.php';
    @set_time_limit(120);
    ignore_user_abort(true);   // sekme kapansa da başlanan parti tamamlanır ve sonuçlar dosyaya yazılır

    $sonuc = bulten_kilit('g-' . $id, function () use ($id): array {
        $c = bulten_gonderim($id);
        if ($c === null || $c['state'] === 'taslak') {
            return ['ok' => false, 'hata' => 'Gönderim bulunamadı.'];
        }
        $dosya = bulten_gonderim_dosya($id);
        $degisti = false;
        // Önceki istek yarıda kesilmişse: o alıcıya e-postanın gidip gitmediği bilinmez; kendiliğinden yeniden gönderilmez
        foreach ($c['recipients'] as $i => $r) {
            if (($r['status'] ?? '') === 'gonderiliyor') {
                $c['recipients'][$i] = array_merge($r, ['status' => 'hata', 'time' => date('c'), 'error' => BULTEN_KESILDI]);
                $degisti = true;
            }
        }
        $bitir = function () use (&$c, $dosya): array {
            $c['state'] = 'tamamlandi';
            $c['finished'] = $c['updated'] = date('c');
            bulten_yaz($dosya, $c);
            $n = bulten_sayilar($c);
            changelog_event('bulten', 'Bülten e-postası gönderimi tamamlandı: "' . mb_substr((string) $c['subject'], 0, 90) . '" (' . $n['gonderildi'] . ' gönderildi'
                . ($n['hata'] ? ', ' . $n['hata'] . ' hata' : '') . ($n['atlandi'] ? ', ' . $n['atlandi'] . ' atlandı' : '') . ')');
            return bulten_ilerleme($c);
        };
        if ($c['state'] !== 'gonderiliyor') {
            if ($degisti) {
                bulten_yaz($dosya, $c);
            }
            return bulten_ilerleme($c);
        }
        $bekleyen = array_keys(array_filter($c['recipients'], fn($r) => ($r['status'] ?? '') === 'bekliyor'));
        if (!$bekleyen) {
            return $bitir();
        }
        if ($degisti) {
            bulten_yaz($dosya, $c);
        }
        if (mail_transport() === '') {
            return bulten_ilerleme($c, ['engel' => 'E-posta gönderilemiyor: sunucuda e-posta fonksiyonu kapalı ve SMTP ayarlı değil. İletişim ve şirket bölümünden SMTP bilgilerini girin.']);
        }
        [$ayrilan, $bekle] = bulten_saat_ayir(min(BULTEN_PARTI, count($bekleyen)));
        if ($ayrilan === 0) {
            return bulten_ilerleme($c, ['bekle' => $bekle]);
        }
        // Alıcı listesi gönderim başlarken dondurulur; ama o günden beri ayrılan, kaydı silinen ya da karantinaya alınan adrese gönderilmez
        $durum = array_column(bulten_aboneler(true), 'durum', 'email');
        $basla = microtime(true);
        $gonderilen = 0;
        $islenen = 0;         // bu istekte işlenen alıcı: gönderilenler ve atlananlar birlikte, en fazla BULTEN_PARTI
        $ardArda = 0;         // art arda başarısız gönderim (e-posta ayarı bozuksa bütün liste hataya dönmesin)
        $yazilmadi = false;   // dosyaya henüz yazılmamış "atlandı" işareti var mı
        foreach ($bekleyen as $i) {
            // Atlananlar da istek başına sınıra sayılır: abone listesi bir an için yanlış okunsa bile tek istekte
            // bütün bekleyenler "atlandı" diye işaretlenemez
            if ($islenen >= BULTEN_PARTI) {
                break;
            }
            $r = $c['recipients'][$i];
            $email = (string) ($r['email'] ?? '');
            if (($durum[$email] ?? '') !== 'onayli') {
                $c['recipients'][$i] = array_merge($r, ['status' => 'atlandi', 'time' => date('c'),
                    'error' => ($durum[$email] ?? '') === 'ayrildi' ? 'Gönderimden önce abonelikten ayrıldı.' : 'Kaydı artık yok ya da ileti onayı bulunmuyor.']);
                $yazilmadi = true;
                $islenen++;
                continue;
            }
            if ($gonderilen >= $ayrilan || microtime(true) - $basla > BULTEN_PARTI_SURE) {
                break;
            }
            // Önce işaretle, sonra gönder: istek tam bu arada kesilse bile aynı alıcıya ikinci kez gönderilmez
            $c['recipients'][$i]['status'] = 'gonderiliyor';
            if (!bulten_yaz($dosya, $c)) {
                $c['recipients'][$i]['status'] = 'bekliyor';
                bulten_saat_iade($ayrilan - $gonderilen);
                return bulten_ilerleme($c, ['engel' => 'Gönderim dosyası yazılamadı: storage klasörü yazılabilir mi?']);
            }
            $gonderilen++;
            $islenen++;
            $ok = bulten_gonder_tek($c, $r);
            $neden = $ok ? '' : (mail_last_error() ?: 'E-posta gönderilemedi.');
            $c['recipients'][$i] = array_merge($r, ['status' => $ok ? 'gonderildi' : 'hata', 'time' => date('c'), 'error' => $neden]);
            $c['updated'] = date('c');
            bulten_yaz($dosya, $c);
            $yazilmadi = false;
            $ardArda = $ok ? 0 : $ardArda + 1;
            if ($ardArda >= BULTEN_ARDARDA_HATA) {
                break;
            }
        }
        bulten_saat_iade($ayrilan - $gonderilen);
        if ($yazilmadi) {
            bulten_yaz($dosya, $c);
        }
        if (!array_filter($c['recipients'], fn($r) => ($r['status'] ?? '') === 'bekliyor')) {
            return $bitir();
        }
        if ($ardArda >= BULTEN_ARDARDA_HATA) {
            // E-posta ayarı bozuk ya da sunucu reddediyor olabilir: kalan alıcılar denenmez, gönderim duraklatılır
            $c['state'] = 'duraklatildi';
            $c['pause_note'] = 'Art arda ' . $ardArda . ' e-posta gönderilemedi (' . $neden . '). Gönderim kendiliğinden duraklatıldı. E-posta ayarlarını kontrol ettikten sonra hata alanlara yeniden gönderin ya da gönderimi sürdürün.';
            $c['updated'] = date('c');
            bulten_yaz($dosya, $c);
        }
        return bulten_ilerleme($c);
    }, false);

    if ($sonuc === null) {
        // Başka bir sekme ya da istek şu an bu gönderimin partisini gönderiyor
        $c = bulten_gonderim($id);
        return $c === null ? ['ok' => false, 'hata' => 'Gönderim bulunamadı.'] : bulten_ilerleme($c, ['mesgul' => true]);
    }
    return $sonuc;
}
