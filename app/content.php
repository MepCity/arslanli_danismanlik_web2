<?php
declare(strict_types=1);

/**
 * İçerik katmanı
 * ---------------------------------------------------------------------------
 * Panelden düzenlenen her şey storage/content/{anahtar}.json dosyasında saklanır.
 * Dosya yoksa app/data/*.php içindeki varsayılan içerik kullanılır.
 * Her kayıttan önce eski sürüm storage/content/_history/{anahtar}/ altına kopyalanır.
 *
 * Anahtarlar:
 *   settings  iletişim, şirket, sosyal medya, e-posta ayarları (config.php üzerine yazar)
 *   services  hizmet alanları
 *   posts     blog yazıları
 *   refs      referans logoları ve kaşe maskeleri (liste)
 *   lists     süreç adımları, ilkeler, misyon/vizyon maddeleri, banka hesapları vb.
 *   texts     sayfa metinleri (t() ve th() işlevleri; bkz. app/texts.php)
 *   features  sitede açık ve kapalı bölümler
 *   seo       arama motoru ve yapay zekâ ayarları
 *
 * Her değişiklik ayrıca değişiklik günlüğüne yazılır (app/changelog.php): kim, ne zaman, neyi değiştirdi.
 */

const CONTENT_DIR = ROOT . '/storage/content';
const CONTENT_HISTORY_KEEP = 25;

function &content_cache(): array
{
    static $cache = [];
    return $cache;
}

function content_get(string $key, $default = null)
{
    $cache = &content_cache();
    if (!array_key_exists($key, $cache)) {
        $file = CONTENT_DIR . '/' . $key . '.json';
        $cache[$key] = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
    }
    return $cache[$key] ?? $default;
}

/** Panelde hiç kaydedilmemiş bir anahtarın sitedeki varsayılan hali (ilk kayıtta geçmişe eklenir). */
function content_default(string $key)
{
    switch ($key) {
        case 'services': return require APP . '/data/services.php';
        case 'posts':    return require APP . '/data/posts.php';
        case 'refs':
            return refs_defaults();
        default:         return new stdClass(); // ayar/metin katmanları: boş = varsayılanlar
    }
}

function content_put(string $key, $value): bool
{
    if (!preg_match('/^[a-z_]+$/', $key)) {
        return false;
    }
    if (!is_dir(CONTENT_DIR) && !@mkdir(CONTENT_DIR, 0755, true)) {
        return false;
    }
    $file = CONTENT_DIR . '/' . $key . '.json';
    $hdir = CONTENT_DIR . '/_history/' . $key;
    if (!is_dir($hdir)) {
        @mkdir($hdir, 0755, true);
    }
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $prev = is_file($file) ? (string) file_get_contents($file) : null;
    if ($prev !== null && $prev === $json) {
        return true;   // içerik aynı: yeni sürüm ve günlük kaydı oluşmaz
    }
    if ($prev !== null) {
        $rev = content_rev_id($hdir);
        @copy($file, $hdir . '/' . $rev . '.json');
        $old = array_filter(glob($hdir . '/*.json') ?: [], fn($f) => !str_ends_with($f, '-0000.json'));
        rsort($old);
        foreach (array_slice($old, CONTENT_HISTORY_KEEP) as $f) {
            @unlink($f);
        }
        $before = json_decode($prev, true);
    } else {
        // İlk kayıt: sitenin özgün hali "-0000" ekiyle saklanır ve hiç silinmez.
        $rev = date('Ymd-His') . '-0000';
        $orig = json_encode(content_default($key), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        @file_put_contents($hdir . '/' . $rev . '.json', $orig, LOCK_EX);
        $before = json_decode((string) $orig, true);
    }
    $tmp  = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false || !@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    $cache = &content_cache();
    $cache[$key] = json_decode($json, true);
    if (function_exists('changelog_record')) {
        changelog_record($key, $rev, $before, $cache[$key]);   // kim, neyi değiştirdi (Değişiklik geçmişi)
    }
    if (function_exists('seo_changed')) {   // Aşama 3A: arama motoru bildirimi (app/seo.php); o zamana dek yok
        seo_changed($key);
    }
    return true;
}

/**
 * Yeni sürüm kimliği: "YYYYAAGG-SSDDSS-xxxx". Son dört hane saniyenin kesridir;
 * böylece aynı saniyede yapılan değişiklikler de (örn. yapay zekâ ile art arda) doğru sırada dizilir.
 */
function content_rev_id(string $hdir): string
{
    $t = microtime(true);
    $n = 1 + (int) (($t - floor($t)) * 0xfffd);
    do {
        $id = date('Ymd-His', (int) $t) . '-' . sprintf('%04x', $n++);
    } while (is_file($hdir . '/' . $id . '.json') && $n <= 0xffff);
    return $id;
}

/** Bir içerik anahtarının önceki sürümleri (en yeni önce): [id => tarih] */
function content_history(string $key): array
{
    $out = [];
    foreach (glob(CONTENT_DIR . '/_history/' . $key . '/*.json') ?: [] as $f) {
        $id = basename($f, '.json');
        $out[$id] = DateTime::createFromFormat('Ymd-His', substr($id, 0, 15)) ?: null;
    }
    krsort($out);
    return $out;
}

function content_restore(string $key, string $rev): bool
{
    if (!preg_match('/^\d{8}-\d{6}-[a-f0-9]{4}$/', $rev)) {
        return false;
    }
    $f = CONTENT_DIR . '/_history/' . $key . '/' . $rev . '.json';
    if (!is_file($f)) {
        return false;
    }
    $data = json_decode((string) file_get_contents($f), true);
    if ($key === 'texts' && is_array($data)) {
        $data = texts_sanitize_all($data);   // eski sürüm bugünkü kurallara uymuyorsa (kayıt defteri değişmiş olabilir) uymayan metinler alınmaz
    }
    return $data !== null && content_put($key, $data);
}

/* ---------- Görünürlük: sitedeki bölümler panelden açılıp kapatılır (content 'features') ---------- */

/** Bölümler ve varsayılan durumları. Yazılar başlangıçta kapalıdır. */
function features_defaults(): array
{
    return [
        'blog'        => false,   // Yazılar: /blog, yazı sayfaları, ana sayfadaki yazılar
        'duyurular'   => true,    // Duyurular ve çağrı takvimi: /duyurular, ana sayfadaki takvim, takvim akışı
        'referanslar' => true,    // Referanslar: /referans, ana sayfa ve Hakkımızda'daki logolar
        'kariyer'     => true,    // Kariyer: /kariyer ve iş başvuru formu
        'bulten'      => true,    // Bülten (Haberdar ol): /haberdarol, kenardaki sekme ve pencere, "Haberdar ol" düğmeleri
        'whatsapp'    => true,    // Sağ alttaki WhatsApp düğmesi
    ];
}

function feature(string $key): bool
{
    $f = content_get('features', []);
    if (is_array($f) && array_key_exists($key, $f)) {
        return (bool) $f[$key];
    }
    return (bool) (features_defaults()[$key] ?? true);
}

/** Bir sayfa yolunun bağlı olduğu bölüm (yoksa null). Uzantılı adresler (.md, .ics) de sayılır. */
function path_feature(string $path): ?string
{
    $path = (string) preg_replace('/\.(md|ics)$/', '', trim($path, '/'));
    if ($path === 'blog' || str_starts_with($path, 'blog/')) return 'blog';
    if ($path === 'duyurular' || str_starts_with($path, 'duyurular/')) return 'duyurular';
    if ($path === 'referans') return 'referanslar';
    if ($path === 'kariyer' || str_starts_with($path, 'kariyer/')) return 'kariyer';
    if ($path === 'haberdarol') return 'bulten';
    return null;
}

function path_enabled(string $path): bool
{
    $f = path_feature($path);
    return $f === null || feature($f);
}

/* ---------- Ayarlar: config.php üzerine yalnızca izin verilen alanlar yazılır ---------- */

function settings_apply(array $config, array $s): array
{
    foreach (['name', 'phone', 'whatsapp', 'email', 'address', 'address_short', 'maps_url', 'store_submissions'] as $k) {
        if (array_key_exists($k, $s) && $s[$k] !== '' && $s[$k] !== null) {
            $config[$k] = $s[$k];
        }
    }
    if (!empty($s['phone'])) {
        $config['phone_href'] = phone_href((string) $s['phone']);
    }
    foreach (['authorized', 'tax_office', 'tax_number'] as $k) {
        if (!empty($s['company'][$k])) {
            $config['company'][$k] = $s['company'][$k];
        }
    }
    if (isset($s['social']) && is_array($s['social'])) {
        $config['social'] = array_filter($s['social'], fn($u) => is_string($u) && $u !== '');
    }
    foreach (['to', 'from', 'from_name'] as $k) {
        if (!empty($s['mail'][$k])) {
            $config['mail'][$k] = $s['mail'][$k];
        }
    }
    if (array_key_exists('smtp', $s['mail'] ?? [])) {
        $config['mail']['smtp'] = !empty($s['mail']['smtp']['host']) ? $s['mail']['smtp'] : null;
    }
    return $config;
}

/** "+90 554 808 97 71" ya da "0554 808 97 71" → "+905548089771" */
function phone_href(string $phone): string
{
    $d = preg_replace('/\D+/', '', $phone);
    if (str_starts_with($d, '90')) return '+' . $d;
    if (str_starts_with($d, '0')) return '+9' . $d;
    return '+' . $d;
}

/* ---------- Doğrulama yardımcıları (hizmetler, referanslar, kurumsal listeler) ---------- */

/** Tek satırlık metin: denetim karakterleri atılır, boşluklar ve satır sonları tek boşluğa iner. */
function val_line($v, int $cap = 3000): string
{
    if (!is_scalar($v)) {
        return '';
    }
    $v = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $v);
    return mb_substr((string) preg_replace('/\s+/u', ' ', trim($v)), 0, $cap);
}

/** Uzunluk denetimi: kurala uymayan için okunur bir ileti, uyan için null. */
function val_len(string $where, string $label, string $v, int $min, int $max): ?string
{
    $n = mb_strlen($v);
    $w = $where !== '' ? $where . ': ' : '';
    if ($v === '') {
        return $min > 0 ? $w . $label . ' boş bırakılamaz.' : null;
    }
    if ($n < $min) {
        return $w . $label . ' en az ' . $min . ' karakter olmalı (şu an ' . $n . ').';
    }
    if ($n > $max) {
        return $w . $label . ' en fazla ' . $max . ' karakter olabilir (şu an ' . $n . ').';
    }
    return null;
}

/** Liste boyutu denetimi: tam sayı ya da aralık. */
function val_count(string $label, int $n, int $min, int $max): ?string
{
    if ($min === $max && $n !== $min) {
        return $label . ': tam ' . $min . ' madde olmalı (şu an ' . $n . '). Sitedeki tasarım ve metinler bu sayıya göre kurulmuştur.';
    }
    if ($n < $min) {
        return $label . ': en az ' . $min . ' madde olmalı (şu an ' . $n . ').';
    }
    if ($n > $max) {
        return $label . ': en fazla ' . $max . ' madde olabilir (şu an ' . $n . ').';
    }
    return null;
}

/**
 * IBAN: boşluksuz büyük harfe çevirir; "TR" + 24 rakam ve mod-97 sağlamasını denetler.
 * Geçerliyse 4'lü gruplara ayrılmış metni, değilse null döndürür.
 */
function iban_format(string $raw): ?string
{
    $iban = strtoupper((string) preg_replace('/\s+/', '', $raw));
    if (!preg_match('/^TR\d{24}$/', $iban)) {
        return null;
    }
    $moved = substr($iban, 4) . substr($iban, 0, 4);
    $num = '';
    foreach (str_split($moved) as $c) {
        $num .= ctype_alpha($c) ? (string) (ord($c) - 55) : $c;
    }
    $rem = 0;
    foreach (str_split($num, 7) as $chunk) {
        $rem = (int) ($rem . $chunk) % 97;
    }
    return $rem === 1 ? trim(chunk_split($iban, 4, ' ')) : null;
}

/** Küçük sayıyı Türkçe yazıyla söyler, baş harfi büyük ("Dokuz", "On iki"); 20'den büyükte rakam. Başlıklarda hizmet sayısı için. */
function number_word(int $n): string
{
    $w = [1 => 'Bir', 'İki', 'Üç', 'Dört', 'Beş', 'Altı', 'Yedi', 'Sekiz', 'Dokuz', 'On', 'On bir', 'On iki', 'On üç', 'On dört', 'On beş', 'On altı', 'On yedi', 'On sekiz', 'On dokuz', 'Yirmi'];
    return $w[$n] ?? (string) $n;
}

/* ---------- Hizmetler ---------- */

/**
 * Hizmet dosyasının renkleri: anahtar => ad. Anahtarlar assets/css/app.css içindeki --f-* değişkenleridir;
 * sitenin kullandığı palet budur (menü sekmesi, dosya sırtı, sayfa kenarlığı).
 */
function service_colors(): array
{
    return ['blue' => 'Mavi', 'green' => 'Yeşil', 'red' => 'Kırmızı', 'orange' => 'Turuncu', 'lilac' => 'Eflatun',
        'yellow' => 'Sarı', 'grey' => 'Gri', 'manila' => 'Krem (manila)', 'pink' => 'Pembe'];
}

/** Aynı renklerin onaltılık değerleri (panelde örnek göstermek için; kaynak: assets/css/app.css --f-*). */
function service_color_hex(): array
{
    return ['blue' => '#8ea8d4', 'green' => '#9fbb98', 'red' => '#dc7c6d', 'orange' => '#e39b62', 'lilac' => '#b2a5d0',
        'yellow' => '#e8cd68', 'grey' => '#b8b5ad', 'manila' => '#d9c69c', 'pink' => '#e2aab0'];
}

/**
 * Hizmet alanlarının sınırları: [en az, en çok] (metinlerde karakter, listelerde madde sayısı).
 * Sınırlar sitedeki mevcut en uzun metne en az bir buçuk kat pay bırakır ve sayfa düzeninin (dosya sırtı, menü,
 * dosya kartı, sıralı madde listesi) taşmadan taşıyabildiği ölçüdedir.
 */
function service_limits(): array
{
    return [
        'services'     => [3, 12],     // Dosya dolabı ve menü bu aralıkta düzenlidir
        'slug'         => [2, 60],
        'title'        => [2, 60],     // Dosya başlığı, sayfa başlığı
        'nav'          => [2, 28],     // Menü ve iletişim formu konu listesi
        'tab'          => [2, 12],     // Dosya sırtındaki etiket (büyük harfle basılır)
        'short'        => [20, 180],   // Listelerde ve ana sayfada tek cümle
        'lead'         => [40, 420],
        'programs'     => [2, 6],      // Dosya kartında madde işareti ve ayrıntı tablosu
        'program_name' => [2, 60],
        'program_desc' => [5, 140],
        'steps'        => [3, 8],
        'step_title'   => [2, 40],
        'step_text'    => [10, 240],
        'docs'         => [3, 10],
        'doc'          => [3, 120],
        'fit'          => [20, 220],
        'unfit'        => [20, 300],
        'law_source'   => [3, 60],
        'law_text'     => [10, 600],
        'law_plain'    => [10, 240],
        'faq'          => [1, 8],
        'faq_q'        => [5, 120],
        'faq_a'        => [10, 600],
    ];
}

/** Ham girişi (form ya da yapay zekâ) hizmetin kayıt biçimine getirir. Doğrulama yapmaz; boş satırlar atılır, anahtar sırası sabittir. */
function service_clean(array $in): array
{
    $pairs = function ($rows): array {
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $r) {
            if (!is_array($r)) {
                continue;
            }
            $r = array_values($r);
            $a = val_line($r[0] ?? '');
            $b = val_line($r[1] ?? '');
            if ($a !== '' || $b !== '') {
                $out[] = [$a, $b];
            }
        }
        return $out;
    };
    $docs = [];
    foreach (is_array($in['docs'] ?? null) ? $in['docs'] : [] as $d) {
        $d = val_line($d);
        if ($d !== '') {
            $docs[] = $d;
        }
    }
    $svc = [
        'no'       => val_line($in['no'] ?? ''),
        'title'    => val_line($in['title'] ?? ''),
        'nav'      => val_line($in['nav'] ?? ''),
        'tab'      => val_line($in['tab'] ?? ''),
        'color'    => val_line($in['color'] ?? ''),
        'short'    => val_line($in['short'] ?? ''),
        'lead'     => val_line($in['lead'] ?? ''),
        'programs' => $pairs($in['programs'] ?? []),
        'steps'    => $pairs($in['steps'] ?? []),
        'docs'     => $docs,
        'fit'      => val_line($in['fit'] ?? ''),
        'unfit'    => val_line($in['unfit'] ?? ''),
    ];
    $law = is_array($in['law'] ?? null) ? $in['law'] : [];
    // Alıntı metni resmî metinden birebir alınır: boşlukları olduğu gibi kalır, yalnızca uçlar kırpılır
    $quote = is_scalar($law['text'] ?? null) ? trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', str_replace(["\r\n", "\r"], "\n", (string) $law['text']))) : '';
    $law = ['source' => val_line($law['source'] ?? ''), 'text' => mb_substr($quote, 0, 3000), 'plain' => val_line($law['plain'] ?? '')];
    if ($law['source'] !== '' || $law['text'] !== '' || $law['plain'] !== '') {
        $svc['law'] = $law;   // mevzuat alıntısı isteğe bağlıdır; hiç yazılmadıysa anahtar hiç yer almaz
    }
    $svc['faq'] = $pairs($in['faq'] ?? []);
    return $svc;
}

/** Hizmetin doğrulama hataları (okunur Türkçe iletiler); boş dizi = geçerli. */
function service_errors(array $s): array
{
    $L = service_limits();
    $e = [];
    $add = function (?string $m) use (&$e): void {
        if ($m !== null) {
            $e[] = $m;
        }
    };
    $add(val_len('', 'Başlık', $s['title'], ...$L['title']));
    $add(val_len('', 'Menüdeki ad', $s['nav'], ...$L['nav']));
    $add(val_len('', 'Dosya sırtı etiketi', $s['tab'], ...$L['tab']));
    if (!isset(service_colors()[$s['color']])) {
        $e[] = 'Renk, sitenin dosya renklerinden biri olmalı (' . implode(', ', array_keys(service_colors())) . ').';
    }
    $add(val_len('', 'Kısa açıklama', $s['short'], ...$L['short']));
    $add(val_len('', 'Giriş paragrafı', $s['lead'], ...$L['lead']));
    $add(val_len('', '"Uygun" metni', $s['fit'], ...$L['fit']));
    $add(val_len('', '"Uygun değil" metni', $s['unfit'], ...$L['unfit']));

    $add(val_count('Programlar', count($s['programs']), ...$L['programs']));
    foreach ($s['programs'] as $i => [$n, $d]) {
        $add(val_len('Program ' . ($i + 1), 'adı', $n, ...$L['program_name']));
        $add(val_len('Program ' . ($i + 1), 'açıklaması', $d, ...$L['program_desc']));
    }
    $add(val_count('Ne yapıyoruz (adımlar)', count($s['steps']), ...$L['steps']));
    foreach ($s['steps'] as $i => [$t, $d]) {
        $add(val_len('Adım ' . ($i + 1), 'başlığı', $t, ...$L['step_title']));
        $add(val_len('Adım ' . ($i + 1), 'metni', $d, ...$L['step_text']));
    }
    $add(val_count('Evrak listesi', count($s['docs']), ...$L['docs']));
    foreach ($s['docs'] as $i => $d) {
        $add(val_len('Evrak ' . ($i + 1), 'metni', $d, ...$L['doc']));
    }
    $add(val_count('Sık sorulanlar', count($s['faq']), ...$L['faq']));
    foreach ($s['faq'] as $i => [$q, $a]) {
        $add(val_len('Soru ' . ($i + 1), 'metni', $q, ...$L['faq_q']));
        $add(val_len('Soru ' . ($i + 1), 'yanıtı', $a, ...$L['faq_a']));
    }
    if (isset($s['law'])) {
        $add(val_len('Mevzuat alıntısı', 'kaynak', $s['law']['source'], ...$L['law_source']));
        $add(val_len('Mevzuat alıntısı', 'alıntı metni', $s['law']['text'], ...$L['law_text']));
        $add(val_len('Mevzuat alıntısı', 'sade Türkçesi', $s['law']['plain'], ...$L['law_plain']));
    }
    return $e;
}

/** Mevzuat alıntısının metni (resmî metinden birebir) eskisinden farklı mı? Yeni eklenen alıntı değişiklik sayılmaz. */
function service_law_changed($old, $new): bool
{
    $o = is_array($old) ? trim((string) ($old['text'] ?? '')) : '';
    $n = is_array($new) ? trim((string) ($new['text'] ?? '')) : '';
    return $o !== '' && $o !== $n;
}

/** Dosya numaraları ("01", "02"…) sıraya göre verilir: sırayı değiştirmek numaraları da düzenli tutar. */
function services_renumber(array $all): array
{
    $i = 0;
    foreach ($all as $slug => $s) {
        $all[$slug]['no'] = sprintf('%02d', ++$i);
    }
    return $all;
}

/** Hizmet listesini (slug => hizmet) numaralayıp içerik deposuna yazar. */
function services_save(array $all): bool
{
    $ok = content_put('services', services_renumber($all));
    if ($ok) {
        $GLOBALS['services'] = content_get('services');
    }
    return $ok;
}

/** Başlıktan benzersiz adres: "TÜBİTAK Destekleri" → "tubitak-destekleri"; doluysa "-2", "-3"… eklenir. */
function service_unique_slug(string $title, array $all): string
{
    $base = mb_substr(slugify($title) ?: 'hizmet', 0, 56);
    $slug = $base;
    for ($i = 2; isset($all[$slug]); $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

/**
 * Hizmeti ekler ya da günceller (panel ve yapay zekâ erişimi ortak kullanır).
 * $slug null ise yeni hizmet eklenir; adres $opt['slug'] ile verilir, verilmezse başlıktan üretilir.
 * Mevzuat alıntısının metni değişiyorsa $opt['confirm_law'] doğru olmalıdır (resmî metinden birebir alıntıdır).
 * @return array{ok:bool, errors:string[], slug:?string, law_changed:bool}
 */
function service_upsert(?string $slug, array $raw, array $opt = []): array
{
    $all = services();
    $fail = fn(array $errors, ?string $s = null, bool $law = false): array => ['ok' => false, 'errors' => $errors, 'slug' => $s, 'law_changed' => $law];
    $isNew = $slug === null;
    if (!$isNew && !isset($all[$slug])) {
        return $fail(['Hizmet bulunamadı.']);
    }
    $svc = service_clean($raw);
    $errors = service_errors($svc);
    $target = $slug;
    if ($isNew) {
        $L = service_limits();
        if (count($all) >= $L['services'][1]) {
            $errors[] = 'En fazla ' . $L['services'][1] . ' hizmet olabilir; önce birini silin.';
        }
        $want = val_line($opt['slug'] ?? '', 120);
        if ($want !== '') {
            if (!preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $want)) {
                $errors[] = 'Sayfa adresi yalnızca küçük harf, rakam ve tire içerebilir (örn. ihracat-destekleri).';
            } elseif (mb_strlen($want) < $L['slug'][0] || mb_strlen($want) > $L['slug'][1]) {
                $errors[] = 'Sayfa adresi ' . $L['slug'][0] . ' ile ' . $L['slug'][1] . ' karakter arasında olmalı.';
            } elseif (isset($all[$want])) {
                $errors[] = 'Bu adres başka bir hizmette kullanılıyor; farklı bir adres yazın.';
            }
            $target = $want;
        } else {
            $target = service_unique_slug($svc['title'], $all);
        }
    }
    $lawChanged = !$isNew && service_law_changed($all[$slug]['law'] ?? null, $svc['law'] ?? null);
    if ($lawChanged && empty($opt['confirm_law'])) {
        $errors[] = 'Mevzuat alıntısı değişiyor. Bu metin resmî mevzuattan birebir alıntıdır; değişikliği kanunun güncel metniyle karşılaştırdığınızı onaylamanız gerekir.';
    }
    if ($errors) {
        return $fail($errors, $target, $lawChanged);
    }
    $all[$target] = $svc;
    if (!services_save($all)) {
        return $fail(['Kaydedilemedi: storage klasörü yazılabilir mi?'], $target, $lawChanged);
    }
    return ['ok' => true, 'errors' => [], 'slug' => $target, 'law_changed' => $lawChanged];
}

/** Hizmeti siler (en az 3 hizmet kalmalı). @return array{ok:bool, errors:string[]} */
function service_delete(string $slug): array
{
    $all = services();
    if (!isset($all[$slug])) {
        return ['ok' => false, 'errors' => ['Hizmet bulunamadı.']];
    }
    $min = service_limits()['services'][0];
    if (count($all) <= $min) {
        return ['ok' => false, 'errors' => ['En az ' . $min . ' hizmet kalmalı; Dosya dolabı ve menü bu sayının altında düzenli görünmez.']];
    }
    unset($all[$slug]);
    return services_save($all) ? ['ok' => true, 'errors' => []] : ['ok' => false, 'errors' => ['Silinemedi: storage klasörü yazılabilir mi?']];
}

/** Hizmetleri verilen adres sırasına dizer; listede olmayanlar sona eklenir. @return array{ok:bool, errors:string[]} */
function services_reorder(array $slugs): array
{
    $all = services();
    $new = [];
    foreach ($slugs as $s) {
        if (is_string($s) && isset($all[$s]) && !isset($new[$s])) {
            $new[$s] = $all[$s];
        }
    }
    if (!$new) {
        return ['ok' => false, 'errors' => ['Sıra boş ya da geçersiz.']];
    }
    foreach ($all as $s => $v) {
        if (!isset($new[$s])) {
            $new[$s] = $v;
        }
    }
    return services_save($new) ? ['ok' => true, 'errors' => []] : ['ok' => false, 'errors' => ['Kaydedilemedi: storage klasörü yazılabilir mi?']];
}

/* ---------- Medya ---------- */

/** Görsel yolunu adrese çevirir: "uploads/..." (panelden yüklenen) ya da "img/..." (temanın) */
function media_url(string $path): string
{
    $path = ltrim($path, '/');
    if (str_starts_with($path, 'uploads/')) {
        $file = ROOT . '/' . $path;
        return url($path) . (is_file($file) ? '?v=' . substr(base_convert((string) filemtime($file), 10, 36), -6) : '');
    }
    if (str_starts_with($path, 'assets/')) {
        $path = substr($path, 7);
    }
    return asset($path);
}

/* ---------- Referanslar ---------- */

/** Referans sınırları: [en az, en çok]. En az 4: Referanslar sayfasındaki kaşe masasında dört kaşe hazır basılı durur. */
function refs_limits(): array
{
    return ['count' => [4, 30], 'name' => [2, 40]];
}

/** Sitenin özgün referansları (app/data/site.php): logo assets/img/refs/{kod}.webp, kaşe maskesi assets/img/refs/ink/{kod}.webp */
function refs_defaults(): array
{
    $out = [];
    foreach ((array) (require APP . '/data/site.php')['refs'] as $id => $name) {
        $out[] = ['id' => (string) $id, 'name' => (string) $name, 'logo' => 'img/refs/' . $id . '.webp', 'ink' => 'img/refs/ink/' . $id . '.webp'];
    }
    return $out;
}

/**
 * Referanslar: [['id' => kod, 'name' => ad, 'logo' => yol, 'ink' => kaşe maskesi yolu], ...]
 * Yollar "img/refs/…" (temanın, assets altında) ya da "uploads/refs/…", "uploads/refs-ink/…" (panelden yüklenen) olabilir.
 */
function refs_list(): array
{
    $list = content_get('refs');
    if (!is_array($list)) {
        return refs_defaults();
    }
    $out = [];
    $seen = [];
    foreach ($list as $r) {
        if (!is_array($r) || empty($r['name']) || empty($r['logo'])) {
            continue;
        }
        $base = pathinfo((string) $r['logo'], PATHINFO_FILENAME);
        $id = (string) ($r['id'] ?? '') !== '' ? (string) $r['id'] : (slugify($base) ?: 'kurum');
        if (isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $ink = (string) ($r['ink'] ?? '');
        if ($ink === '') {
            $ink = str_starts_with((string) $r['logo'], 'img/refs/') ? 'img/refs/ink/' . basename((string) $r['logo']) : 'uploads/refs-ink/' . basename((string) $r['logo']);
        }
        $out[] = ['id' => $id, 'name' => (string) $r['name'], 'logo' => (string) $r['logo'], 'ink' => $ink];
    }
    return $out;
}

/** Kod => kurum adı (Referanslar sayfası ve ana sayfa bu listeyle çizilir). */
function refs_map(): array
{
    $out = [];
    foreach (refs_list() as $r) {
        $out[$r['id']] = $r['name'];
    }
    return $out;
}

/** Bir referansın kaşe maskesinin adresi (sayfada CSS maskesi olarak kullanılır). */
function ref_ink_url(string $id): string
{
    static $inks = null;
    if ($inks === null) {
        $inks = [];
        foreach (refs_list() as $r) {
            $inks[$r['id']] = $r['ink'];
        }
    }
    return media_url($inks[$id] ?? ('img/refs/ink/' . $id . '.webp'));
}

/** Panelde kaydedilmiş referans listesini yazar. */
function refs_save(array $list): bool
{
    return content_put('refs', array_values($list));
}

/** Kurum adından benzersiz kod: "Örnek Havacılık A.Ş." → "ornek-havacilik-a-s" */
function ref_unique_id(string $name, array $list): string
{
    $taken = array_merge(array_column($list, 'id'), ['yeni', 'sirala']);   // panel adresleriyle çakışmasın
    $base = mb_substr(slugify($name) ?: 'kurum', 0, 40);
    $id = $base;
    for ($i = 2; in_array($id, $taken, true); $i++) {
        $id = $base . '-' . $i;
    }
    return $id;
}

/**
 * Logodan kaşe maskesi üretir (GD): koyu kısımlar opak, açık kısımlar şeffaf, tek renk (siyah) bir WebP.
 * Sitedeki kaşe etkisi bu maskeyi CSS "mask" ile istenen mürekkep renginde boyar.
 *   - şeffaf arka planlı logoda şeffaflık korunur; opak (JPG) logoda arka plan kenar renginden tanınır ve atılır
 *   - koyu → opak, açık → şeffaf (eşik 90/255'ten sonra yumuşak düşüş; sitedeki özgün kaşelerin ölçülen eğrisi)
 *   - doygun renkler (açık sarı, turuncu…) açık olsalar da mürekkep sayılır; böylece renkli logo silikleşmez
 *   - logo açık renkliyse ($mode 'light', ya da otomatik algılanırsa) ters çevrilir
 *   - boş kenarlar kırpılır, en çok 480x240 piksel
 * @param string $mode auto | dark (koyu logo) | light (açık logo)
 * @throws RuntimeException okunamayan ya da boş çıkan logoda
 */
function ref_make_ink(string $src, string $dst, string $mode = 'auto'): void
{
    if (!function_exists('imagecreatefromwebp') || !function_exists('imagewebp')) {
        throw new RuntimeException('Sunucuda GD (WebP) desteği kapalı; kaşe görünümü üretilemiyor.');
    }
    $info = @getimagesize($src);
    $im = !$info ? false : match ($info[2]) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => @imagecreatefromwebp($src),
        default        => false,
    };
    if (!$im) {
        throw new RuntimeException('Logo açılamadı; kaşe görünümü üretilemedi.');
    }
    $w0 = imagesx($im);
    $h0 = imagesy($im);
    $k = min(1.0, 480 / $w0, 240 / $h0);
    $w = max(1, (int) round($w0 * $k));
    $h = max(1, (int) round($h0 * $k));
    $s = imagecreatetruecolor($w, $h);
    imagealphablending($s, false);
    imagesavealpha($s, true);
    imagefill($s, 0, 0, imagecolorallocatealpha($s, 255, 255, 255, 127));
    imagecopyresampled($s, $im, 0, 0, 0, 0, $w, $h, $w0, $h0);

    // Piksel verisi: açıklık (0-255), doygunluk (0-1), görünürlük (0-1)
    $lum = $sat = $vis = [];
    $transparent = 0;
    $wsum = $lsum = 0.0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($s, $x, $y);
            $a = 1 - (($c >> 24) & 127) / 127;
            $r = ($c >> 16) & 255;
            $g = ($c >> 8) & 255;
            $b = $c & 255;
            $l = 0.299 * $r + 0.587 * $g + 0.114 * $b;
            $i = $y * $w + $x;
            $lum[$i] = $l;
            $sat[$i] = (max($r, $g, $b) - min($r, $g, $b)) / 255;
            $vis[$i] = $a;
            if ($a < 0.97) {
                $transparent++;
            }
            $wsum += $a;
            $lsum += $a * $l;
        }
    }
    $hasAlpha = $transparent > 0.02 * $w * $h;
    $light = $mode === 'light';
    if ($mode === 'auto') {
        if ($hasAlpha) {
            $light = $wsum > 0 && $lsum / $wsum > 185;   // şeffaf zeminde açık renkli logo
        } else {
            // Opak resim: kenar piksellerinin ortalaması arka planı verir; koyu zemindeki açık logo ters çevrilir
            $edge = $n = 0;
            for ($x = 0; $x < $w; $x++) { $edge += $lum[$x] + $lum[($h - 1) * $w + $x]; $n += 2; }
            for ($y = 1; $y < $h - 1; $y++) { $edge += $lum[$y * $w] + $lum[$y * $w + $w - 1]; $n += 2; }
            $light = $n > 0 && $edge / $n < 110;
        }
    }
    $out = imagecreatetruecolor($w, $h);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    $clear = imagecolorallocatealpha($out, 0, 0, 0, 127);
    imagefill($out, 0, 0, $clear);
    $minX = $w; $minY = $h; $maxX = -1; $maxY = -1; $strong = 0;
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $i = $y * $w + $x;
            $l = $light ? 255 - $lum[$i] : $lum[$i];
            $d = $l <= 90 ? 1.0 : max(0.0, 1 - ($l - 90) / 165) ** 1.2;   // koyuluk → opaklık
            $boost = min(1.0, max(0.0, ($sat[$i] - 0.25) / 0.5)) * 0.9;     // doygun renk açık olsa da silikleşmez
            $v = $vis[$i] * max($d, $boost);
            if ($v < 0.06) {
                continue;
            }
            imagesetpixel($out, $x, $y, imagecolorallocatealpha($out, 0, 0, 0, 127 - (int) round(127 * min(1.0, $v))));
            if ($v > 0.1) {
                $minX = min($minX, $x); $maxX = max($maxX, $x); $minY = min($minY, $y); $maxY = max($maxY, $y);
            }
            if ($v > 0.5) {
                $strong++;
            }
        }
    }
    if ($maxX < 0 || $strong < 0.004 * $w * $h) {
        throw new RuntimeException('Logodan kaşe çıkarılamadı: logo boş ya da çok açık renkli görünüyor. Koyu renkli ya da şeffaf arka planlı bir logo deneyin.');
    }
    $cw = $maxX - $minX + 1;
    $ch = $maxY - $minY + 1;
    $crop = imagecreatetruecolor($cw, $ch);
    imagealphablending($crop, false);
    imagesavealpha($crop, true);
    imagefill($crop, 0, 0, imagecolorallocatealpha($crop, 0, 0, 0, 127));
    imagecopy($crop, $out, 0, 0, $minX, $minY, $cw, $ch);
    $ok = @imagewebp($crop, $dst, 82);
    if (!$ok) {
        @unlink($dst);
        throw new RuntimeException('Kaşe görünümü kaydedilemedi.');
    }
}

/**
 * Yüklenen logoyu doğrular, uploads/refs altına WebP olarak koyar ve kaşe maskesini uploads/refs-ink altında üretir.
 * @return array{0:string, 1:string} [logo yolu, kaşe yolu]
 * @throws RuntimeException
 */
function ref_store_logo(string $path, bool $uploaded, string $mode = 'auto'): array
{
    $info = @getimagesize($path);
    if ($info) {
        [$w, $h] = $info;
        if (max($w, $h) < 120) {
            throw new RuntimeException('Logo çok küçük: en az 120 piksel genişliğinde olmalı (şu an ' . $w . '×' . $h . ').');
        }
        if ($w / max(1, $h) > 12 || $h / max(1, $w) > 3) {
            throw new RuntimeException('Logonun oranı çok uç: en fazla 12:1 genişlikte ya da 1:3 yükseklikte olabilir (şu an ' . $w . '×' . $h . ').');
        }
    }
    $logo = image_store($path, 'refs', 600, $uploaded);
    $dir = ROOT . '/uploads/refs-ink';
    $ink = 'uploads/refs-ink/' . pathinfo($logo, PATHINFO_FILENAME) . '.webp';
    try {
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            throw new RuntimeException('uploads klasörü oluşturulamadı.');
        }
        ref_make_ink(ROOT . '/' . $logo, ROOT . '/' . $ink, $mode);
    } catch (Throwable $e) {
        @unlink(ROOT . '/' . $logo);
        throw $e instanceof RuntimeException ? $e : new RuntimeException('Kaşe görünümü üretilemedi.');
    }
    return [$logo, $ink];
}

/**
 * Referansı ekler ya da günceller (panel ve yapay zekâ erişimi ortak kullanır).
 * $id null ise yeni referans eklenir ve $logo zorunludur. $logo: ['path' => dosya, 'uploaded' => bool] ya da null (logo değişmez).
 * $mode: kaşe üretiminde logonun rengi (auto | dark | light).
 * @return array{ok:bool, errors:string[], id:?string}
 */
function ref_upsert(?string $id, string $name, ?array $logo, string $mode = 'auto'): array
{
    $L = refs_limits();
    $list = refs_list();
    $name = val_line($name, 200);
    $errors = [];
    if (($m = val_len('', 'Kurum adı', $name, ...$L['name'])) !== null) {
        $errors[] = $m;
    }
    $idx = null;
    if ($id !== null) {
        foreach ($list as $i => $r) {
            if ($r['id'] === $id) {
                $idx = $i;
            }
        }
        if ($idx === null) {
            return ['ok' => false, 'errors' => ['Referans bulunamadı.'], 'id' => null];
        }
    } else {
        if ($logo === null) {
            $errors[] = 'Bir logo seçin.';
        }
        if (count($list) >= $L['count'][1]) {
            $errors[] = 'En fazla ' . $L['count'][1] . ' referans olabilir; önce birini silin.';
        }
    }
    foreach ($list as $i => $r) {
        if ($i !== $idx && mb_strtolower($r['name']) === mb_strtolower($name) && $name !== '') {
            $errors[] = 'Bu kurum zaten listede.';
        }
    }
    $files = null;
    if (!$errors && $logo !== null) {
        try {
            $files = ref_store_logo((string) $logo['path'], !empty($logo['uploaded']), $mode);
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'id' => $id];
    }
    if ($idx === null) {
        $id = ref_unique_id($name, $list);
        $list[] = ['id' => $id, 'name' => $name, 'logo' => $files[0], 'ink' => $files[1]];
    } else {
        $list[$idx]['name'] = $name;
        if ($files) {
            $list[$idx]['logo'] = $files[0];
            $list[$idx]['ink'] = $files[1];
        }
    }
    if (!refs_save($list)) {
        return ['ok' => false, 'errors' => ['Kaydedilemedi: storage klasörü yazılabilir mi?'], 'id' => $id];
    }
    return ['ok' => true, 'errors' => [], 'id' => $id];
}

/** Referansı siler (en az 4 kalmalı). Yüklenen dosyalar geçmişten geri alınabilsin diye silinmez. @return array{ok:bool, errors:string[]} */
function ref_delete(string $id): array
{
    $list = refs_list();
    $min = refs_limits()['count'][0];
    foreach ($list as $i => $r) {
        if ($r['id'] === $id) {
            if (count($list) <= $min) {
                return ['ok' => false, 'errors' => ['En az ' . $min . ' referans kalmalı; kaşe masasında dört kaşe hazır basılı durur.']];
            }
            array_splice($list, $i, 1);
            return refs_save($list) ? ['ok' => true, 'errors' => []] : ['ok' => false, 'errors' => ['Silinemedi: storage klasörü yazılabilir mi?']];
        }
    }
    return ['ok' => false, 'errors' => ['Referans bulunamadı.']];
}

/** Referansları verilen kod sırasına dizer; listede olmayanlar sona eklenir. @return array{ok:bool, errors:string[]} */
function refs_reorder(array $ids): array
{
    $by = [];
    foreach (refs_list() as $r) {
        $by[$r['id']] = $r;
    }
    $new = [];
    foreach ($ids as $i) {
        if (is_string($i) && isset($by[$i]) && !isset($new[$i])) {
            $new[$i] = $by[$i];
        }
    }
    if (!$new) {
        return ['ok' => false, 'errors' => ['Sıra boş ya da geçersiz.']];
    }
    return refs_save(array_values($new + $by)) ? ['ok' => true, 'errors' => []] : ['ok' => false, 'errors' => ['Kaydedilemedi: storage klasörü yazılabilir mi?']];
}

/** Eksik logo ya da kaşe dosyası olan referanslar (yedekten geri yükleme ya da silinen dosya sonrası): [ad => eksik dosya yolları] */
function refs_problems(): array
{
    $out = [];
    foreach (refs_list() as $r) {
        $miss = [];
        foreach (['logo', 'ink'] as $k) {
            $p = ltrim($r[$k], '/');
            $file = ROOT . '/' . (str_starts_with($p, 'uploads/') ? $p : 'assets/' . preg_replace('#^assets/#', '', $p));
            if (!is_file($file)) {
                $miss[] = $p;
            }
        }
        if ($miss) {
            $out[$r['name']] = $miss;
        }
    }
    return $out;
}

/* ---------- Kurumsal listeler (content 'lists') ---------- */

/**
 * Panelden düzenlenen ortak listeler: anahtar => ad. Değerleri app/data/site.php'de durur; panelde kaydedilenler
 * content 'lists' içinde yalnızca değişen anahtarlarla saklanır. hero_law (ana sayfadaki kanun metni), iller ve sehirler
 * bu listede yoktur: kanun metni resmî mevzuattan birebir alıntıdır, il listeleri sabittir.
 */
function lists_keys(): array
{
    return [
        'process'    => 'Çalışma süreci (ana sayfa takvimi)',
        'timeline'   => 'Mevzuat zaman çizelgesi (Hakkımızda)',
        'principles' => 'Mihenk taşlarımız (ilkeler)',
        'mission'    => 'Misyonumuz',
        'vision'     => 'Vizyonumuz (mektup)',
        'banks'      => 'Banka hesapları',
        'sektorler'  => 'Bülten formu sektör seçenekleri',
        'deneyim'    => 'Kariyer formu deneyim seçenekleri',
    ];
}

/** Kurumsal listelerin sınırları: [en az, en çok]. Metin alanlarında karakter, listelerde madde sayısı. */
function lists_limits(): array
{
    return [
        'process'    => ['count' => [8, 8], 'phase' => [2, 16], 'title' => [2, 20], 'us' => [20, 180], 'you' => [5, 100]],
        'timeline'   => ['count' => [6, 20], 'year' => [1900, 2100], 'title' => [2, 40], 'text' => [20, 240], 'src' => [2, 30]],
        'principles' => ['count' => [6, 6], 'title' => [3, 40], 'text' => [20, 200]],
        'mission'    => ['count' => [5, 5], 'statement' => [20, 160], 'item' => [10, 160]],
        'vision'     => ['count' => [3, 8], 'greeting' => [3, 40], 'intro' => [20, 300], 'item' => [10, 180], 'closing' => [5, 160]],
        'banks'      => ['count' => [1, 6], 'bank' => [2, 40], 'holder' => [2, 50], 'account' => [3, 24]],
        'sektorler'  => ['count' => [3, 60], 'item' => [2, 80]],
        'deneyim'    => ['count' => [2, 20], 'item' => [2, 60]],
    ];
}

/** Ham girişi listenin kayıt biçimine getirir (doğrulama yapmaz). Anahtar sırası sitedeki varsayılanla aynıdır. */
function list_clean(string $key, $raw)
{
    $strs = function ($rows): array {
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $v) {
            $v = val_line($v, 400);
            if ($v !== '') {
                $out[] = $v;
            }
        }
        return $out;
    };
    $rows = function ($rows, array $fields): array {
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $r) {
            if (!is_array($r)) {
                continue;
            }
            $x = [];
            foreach ($fields as $f) {
                $x[$f] = val_line($r[$f] ?? '', 800);
            }
            if (implode('', $x) !== '') {
                $out[] = $x;
            }
        }
        return $out;
    };
    $raw = is_array($raw) ? $raw : [];
    switch ($key) {
        case 'process':
            return $rows($raw, ['phase', 'title', 'us', 'you']);
        case 'timeline':
            $t = $rows($raw, ['year', 'kind', 'title', 'text', 'src']);
            foreach ($t as &$x) {
                $x['year'] = preg_match('/^\d{1,4}$/', $x['year']) ? (int) $x['year'] : $x['year'];   // sayı değilse doğrulamada reddedilir
            }
            unset($x);
            usort($t, fn($a, $b) => (is_int($a['year']) && is_int($b['year'])) ? $a['year'] <=> $b['year'] : 0);   // yıla göre, eşitlerde yazılış sırası (usort kararlı)
            return $t;
        case 'principles':
            $out = [];
            foreach ($raw as $p) {
                if (!is_array($p)) {
                    continue;
                }
                $h = val_line($p[0] ?? ($p['h'] ?? ($p['title'] ?? '')), 800);
                $t = val_line($p[1] ?? ($p['t'] ?? ($p['text'] ?? '')), 800);
                if ($h !== '' || $t !== '') {
                    $out[] = [$h, $t];
                }
            }
            return $out;
        case 'mission':
            return ['statement' => val_line($raw['statement'] ?? ''), 'items' => $strs($raw['items'] ?? [])];
        case 'vision':
            $y = val_line($raw['open_year'] ?? '');
            return ['open_year' => preg_match('/^\d{1,4}$/', $y) ? (int) $y : $y, 'greeting' => val_line($raw['greeting'] ?? ''), 'intro' => val_line($raw['intro'] ?? ''),
                'items' => $strs($raw['items'] ?? []), 'closing' => val_line($raw['closing'] ?? '')];
        case 'banks':
            $b = $rows($raw, ['bank', 'holder', 'account', 'iban']);
            foreach ($b as &$x) {
                $x['iban'] = iban_format($x['iban']) ?? $x['iban'];   // geçerliyse 4'lü gruplu; değilse olduğu gibi kalır, doğrulamada reddedilir
            }
            unset($x);
            return $b;
        default:   // sektorler, deneyim
            return $strs($raw);
    }
}

/** Listenin doğrulama hataları (okunur Türkçe iletiler); boş dizi = geçerli. $c: list_clean() çıktısı. */
function list_errors(string $key, $c): array
{
    $L = lists_limits()[$key] ?? null;
    $name = lists_keys()[$key] ?? $key;
    if ($L === null) {
        return ['Bilinmeyen liste: ' . $key];
    }
    $e = [];
    $add = function (?string $m) use (&$e): void {
        if ($m !== null) {
            $e[] = $m;
        }
    };
    switch ($key) {
        case 'process':
            $add(val_count('Çalışma süreci', count($c), ...$L['count']));
            foreach ($c as $i => $x) {
                $w = ($i + 1) . '. adım';
                $add(val_len($w, 'aşama', $x['phase'], ...$L['phase']));
                $add(val_len($w, 'başlık', $x['title'], ...$L['title']));
                $add(val_len($w, '"Biz" metni', $x['us'], ...$L['us']));
                $add(val_len($w, '"Siz" metni', $x['you'], ...$L['you']));
            }
            break;
        case 'timeline':
            $add(val_count('Zaman çizelgesi', count($c), ...$L['count']));
            $years = [];
            foreach ($c as $i => $x) {
                $w = ($i + 1) . '. kayıt';
                if (!is_int($x['year']) || $x['year'] < $L['year'][0] || $x['year'] > $L['year'][1]) {
                    $e[] = $w . ': yıl ' . $L['year'][0] . ' ile ' . $L['year'][1] . ' arasında, dört haneli bir yıl olmalı.';
                } elseif (isset($years[$x['year']])) {
                    $e[] = $w . ': ' . $x['year'] . ' yılı iki kez yazılmış. Raftaki her klasör ayrı bir yıldır; aynı yıldaki iki gelişmeyi tek kayıtta birleştirin.';
                } else {
                    $years[$x['year']] = true;
                }
                if (!in_array($x['kind'], ['law', 'us'], true)) {
                    $e[] = $w . ': tür "Mevzuat" ya da "Biz" olmalı.';
                }
                $add(val_len($w, 'başlık', $x['title'], ...$L['title']));
                $add(val_len($w, 'metin', $x['text'], ...$L['text']));
                $add(val_len($w, 'kaynak', $x['src'], ...$L['src']));
            }
            break;
        case 'principles':
            $add(val_count('İlkeler', count($c), ...$L['count']));
            foreach ($c as $i => [$t, $x]) {
                $add(val_len(($i + 1) . '. ilke', 'başlık', $t, ...$L['title']));
                $add(val_len(($i + 1) . '. ilke', 'açıklama', $x, ...$L['text']));
            }
            break;
        case 'mission':
            $add(val_len('Misyon', 'cümlesi', $c['statement'], ...$L['statement']));
            $add(val_count('Misyon maddeleri', count($c['items']), ...$L['count']));
            foreach ($c['items'] as $i => $x) {
                $add(val_len(($i + 1) . '. misyon maddesi', 'metni', $x, ...$L['item']));
            }
            break;
        case 'vision':
            $y = $c['open_year'];
            $min = (int) date('Y') + 1;
            if (!is_int($y) || $y < $min || $y > 2100) {
                $e[] = 'Açılış yılı ' . $min . ' ile 2100 arasında, dört haneli bir yıl olmalı (mektup gelecekte açılır).';
            }
            $add(val_len('Vizyon', 'selamlaması', $c['greeting'], ...$L['greeting']));
            $add(val_len('Vizyon', 'giriş paragrafı', $c['intro'], ...$L['intro']));
            $add(val_count('Vizyon maddeleri', count($c['items']), ...$L['count']));
            foreach ($c['items'] as $i => $x) {
                $add(val_len(($i + 1) . '. vizyon maddesi', 'metni', $x, ...$L['item']));
            }
            $add(val_len('Vizyon', 'kapanış cümlesi', $c['closing'], ...$L['closing']));
            break;
        case 'banks':
            $add(val_count('Banka hesapları', count($c), ...$L['count']));
            $seen = [];
            foreach ($c as $i => $x) {
                $w = ($i + 1) . '. hesap';
                $add(val_len($w, 'banka adı', $x['bank'], ...$L['bank']));
                $add(val_len($w, 'hesap sahibi', $x['holder'], ...$L['holder']));
                $add(val_len($w, 'hesap numarası', $x['account'], ...$L['account']));
                if (iban_format($x['iban']) === null) {
                    $e[] = $w . ': IBAN geçerli değil. TR ile başlayan 26 karakter olmalı ve rakamlarında yazım hatası bulunmamalı.';
                } elseif (isset($seen[$x['iban']])) {
                    $e[] = $w . ': bu IBAN başka bir hesapta da yazılmış.';
                } else {
                    $seen[$x['iban']] = true;
                }
            }
            break;
        default:   // sektorler, deneyim
            $add(val_count($name, count($c), ...$L['count']));
            foreach ($c as $i => $x) {
                $add(val_len(($i + 1) . '. seçenek', 'adı', $x, ...$L['item']));
            }
            if (count($c) !== count(array_unique($c))) {
                $e[] = $name . ': aynı seçenek iki kez yazılmış.';
            }
    }
    return $e;
}

/**
 * Kurumsal listeleri doğrular ve kaydeder (panel ve yapay zekâ erişimi ortak kullanır). $changes: anahtar => ham değer.
 * Sitedeki güncel değerle aynı olan listeler yazılmaz; content 'lists' içindeki diğer anahtarlar korunur.
 * @return array{ok:bool, errors:string[], changed:string[]}
 */
function lists_save(array $changes): array
{
    $errors = [];
    $clean = [];
    foreach ($changes as $k => $raw) {
        $k = (string) $k;
        $clean[$k] = list_clean($k, $raw);
        $errors = array_merge($errors, list_errors($k, $clean[$k]));
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'changed' => []];
    }
    $cur = content_get('lists', []);
    $cur = is_array($cur) ? $cur : [];
    $changed = [];
    foreach ($clean as $k => $c) {
        if (json_encode(site($k)) === json_encode($c)) {
            continue;
        }
        $cur[$k] = $c;
        $changed[] = $k;
    }
    if (!$changed) {
        return ['ok' => true, 'errors' => [], 'changed' => []];
    }
    if (!content_put('lists', $cur)) {
        return ['ok' => false, 'errors' => ['Kaydedilemedi: storage klasörü yazılabilir mi?'], 'changed' => []];
    }
    $GLOBALS['site'] = array_merge(require APP . '/data/site.php', (array) content_get('lists', []));
    return ['ok' => true, 'errors' => [], 'changed' => $changed];
}

/** Yazı görseli adresi (görsel yoksa null) */
function post_image(array $post): ?string
{
    $img = (string) ($post['image'] ?? '');
    if ($img === '') return null;
    return str_contains($img, '/') ? media_url($img) : asset('img/blog/' . $img . '.webp');
}

/* ---------- Sayfa metinleri ---------- */

/**
 * Düzenlenebilir metinlerin kaydı (app/data/texts/*.php, dosya adı sırasıyla birleştirilir):
 *   [grup => ['label' => 'Ana sayfa', 'url' => '', 'icon' => 'house', 'sections' => [bölüm adı => [anahtar => [etiket, varsayılan, tür, yardım?, seçenekler?]]]]]
 * tür: 'line' (tek satır), 'text' (düz metin), 'lines' (satır sonları korunur), 'rich' (sınırlı biçim işaretleri).
 * Türler, seçenekler, biçim işaretleri ve yer tutucular: app/texts.php başı.
 */
function texts_registry(): array
{
    static $reg = null;
    if ($reg === null) {
        $reg = [];
        $files = glob(APP . '/data/texts/*.php') ?: [];
        sort($files);
        foreach ($files as $f) {
            $reg = array_merge($reg, (array) require $f);
        }
    }
    return $reg;
}

/** [anahtar => [etiket, varsayılan, tür, yardım, seçenekler]] düz liste */
function texts_flat(): array
{
    static $flat = null;
    if ($flat === null) {
        $flat = [];
        foreach (texts_registry() as $group) {
            foreach ($group['sections'] ?? [] as $items) {
                foreach ($items as $k => $item) {
                    $flat[$k] = $item;
                }
            }
        }
    }
    return $flat;
}

/**
 * Düzenlenebilir sayfa metni, DÜZ METİN olarak (biçim işaretleri soyulmuş, yer tutucular doldurulmuş, kaçırılmamış):
 * şablonda e(t('grup.anahtar')) ile yazılır. $vars: bu metne özgü yer tutucuların değerleri.
 * Biçimli (rich) ya da satır sonlu (lines) metni güvenli HTML olarak basmak için th() kullanılır.
 * Panelden değiştirilen metin storage/content/texts.json içindedir.
 */
function t(string $key, array $vars = []): string
{
    return text_plain($key, $vars);
}

require_once APP . '/texts.php';

/* ---------- Güvenli HTML (blog gövdesi) ---------- */

/** Yalnızca izin verilen etiketleri ve bağlantı adreslerini bırakır. */
function sanitize_html(string $html): string
{
    $allowed = ['p', 'h2', 'h3', 'ul', 'ol', 'li', 'strong', 'b', 'em', 'i', 'a', 'blockquote', 'br'];
    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $doc->getElementById('__root');
    if (!$root) return '';
    $walk = function (DOMNode $node) use (&$walk, $allowed, $doc) {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if ($tag === 'b') $tag = 'strong';
                if ($tag === 'i') $tag = 'em';
                if ($tag === 'div') $tag = 'p';   // editörden gelen satırlar paragraf olur
                if ($tag === 'h1' || $tag === 'h4') $tag = $tag === 'h1' ? 'h2' : 'h3';
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'svg', 'math', 'template'], true)) {
                    $node->removeChild($child);
                    continue;
                }
                $walk($child);
                if (!in_array($tag, $allowed, true)) {
                    // İzin verilmeyen etiketi kaldır, içeriğini koru
                    while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                    $node->removeChild($child);
                    continue;
                }
                $new = $doc->createElement($tag);
                if ($tag === 'a') {
                    $href = trim((string) $child->getAttribute('href'));
                    if (preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $href)) {
                        $new->setAttribute('href', $href);
                        if (preg_match('#^https?://#i', $href)) {
                            $new->setAttribute('target', '_blank');
                            $new->setAttribute('rel', 'noopener');
                        }
                    }
                }
                while ($child->firstChild) $new->appendChild($child->firstChild);
                $node->replaceChild($new, $child);
            } elseif ($child instanceof DOMComment) {
                $node->removeChild($child);
            }
        }
    };
    $walk($root);
    $out = '';
    foreach ($root->childNodes as $c) $out .= $doc->saveHTML($c);
    return trim($out);
}

/* ---------- Görsel yükleme ---------- */

/**
 * Yüklenen görseli doğrular, en fazla $maxW genişliğe küçültür ve WebP'ye çevirir (GD varsa).
 * Başarılıysa "uploads/{klasör}/{ad}.webp" döner, değilse hata metni fırlatır.
 */
function upload_image(array $file, string $dir, int $maxW = 1600): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Görsel yüklenemedi.');
    }
    return image_store((string) $file['tmp_name'], $dir, $maxW, true);
}

/**
 * Sunucudaki bir görsel dosyasını doğrular, küçültür, WebP'ye çevirir ve uploads/{klasör} altına alır.
 * $uploaded: dosya tarayıcıdan mı yüklendi (panel) yoksa sunucuda mı hazırlandı (yapay zekâ erişimi: adresten indirilen görsel).
 */
function image_store(string $path, string $dir, int $maxW = 1600, bool $uploaded = false): string
{
    $file = ['tmp_name' => $path, 'size' => (int) @filesize($path)];
    if (!is_file($path)) {
        throw new RuntimeException('Görsel yüklenemedi.');
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Görsel en fazla 8 MB olabilir.');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('Yalnızca JPG, PNG ya da WebP görsel yükleyebilirsiniz.');
    }
    // Çok büyük boyutlu görseller açılırken belleği tüketir; açılmadan önce geri çevrilir
    if ($info[0] * $info[1] > 40000000) {
        throw new RuntimeException('Görsel çok büyük; en fazla 40 megapiksel olabilir.');
    }
    if (!preg_match('/^[a-z0-9_-]+$/', $dir)) {
        throw new RuntimeException('Geçersiz klasör.');
    }
    $target = ROOT . '/uploads/' . $dir;
    if (!is_dir($target) && !@mkdir($target, 0755, true)) {
        throw new RuntimeException('uploads klasörü oluşturulamadı.');
    }
    $base = date('Ymd') . '-' . bin2hex(random_bytes(5));

    if (function_exists('imagewebp')) {
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($file['tmp_name']),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file['tmp_name']),
        };
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            if ($w > $maxW) {
                $nh = (int) round($h * $maxW / $w);
                $dst = imagecreatetruecolor($maxW, $nh);
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $h);

                $src = $dst;
            } else {
                imagepalettetotruecolor($src);
                imagealphablending($src, false);
                imagesavealpha($src, true);
            }
            $out = $target . '/' . $base . '.webp';
            $ok = imagewebp($src, $out, 84);

            if ($ok) {
                return 'uploads/' . $dir . '/' . $base . '.webp';
            }
            @unlink($out);
        }
        // GD var ama görsel açılamadı ya da WebP yazılamadı: ham dosya siteye kopyalanmaz
        throw new RuntimeException('Görsel işlenemedi.');
    }
    // GD ya da WebP desteği hiç yoksa doğrulanmış dosyayı olduğu gibi taşı
    $ext = $types[$info[2]];
    $dest = $target . '/' . $base . '.' . $ext;
    if (!($uploaded ? move_uploaded_file($file['tmp_name'], $dest) : @copy($file['tmp_name'], $dest))) {
        throw new RuntimeException('Görsel kaydedilemedi.');
    }
    return 'uploads/' . $dir . '/' . $base . '.' . $ext;
}
