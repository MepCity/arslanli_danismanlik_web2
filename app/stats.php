<?php
declare(strict_types=1);

/**
 * Ziyaretçi sayacı: çerezsiz, veritabanısız; IP adresi ya da kişisel veri saklanmaz.
 * ---------------------------------------------------------------------------
 * Sayfa açılınca tarayıcı /olc adresine küçük bir bildirim gönderir (assets/js/app.js). Botlar betik çalıştırmadığı
 * için sayılmaz; yönetim panelini kullanan tarayıcı da sayılmaz.
 *
 * Tekil ziyaretçi: IP ve tarayıcı bilgisi, her gün yenilenen rastgele bir tuzla özetlenir. Özet yalnızca o gün,
 * "bu kişi bugün daha önce geldi mi" sorusu için tutulur; gün bitince tuzla birlikte silinir, günler arasında eşleştirilemez.
 *
 * Tek bir IP'den günde en fazla 40 yeni ziyaretçi sayılır (tarayıcı bilgisini değiştirerek sayacı şişirmek önlenir).
 * Bunun için IP'nin kendisi değil, aynı günlük tuzla alınmış kısa özeti ve o IP'den bugün ilk kez görülen ziyaretçi
 * sayısı tutulur; bu liste de gün bitince tuzla birlikte silinir.
 *
 *   storage/stats/AAAA-AA.json  gün gün toplamlar: v görüntüleme, u tekil ziyaretçi, um telefonla gelen ziyaretçi,
 *                               p sayfa => görüntüleme, r kaynak => ziyaret
 *   storage/stats/today.json    bugünün tuzu, ziyaretçi özetleri (seen) ve IP özeti => yeni ziyaretçi sayısı (ips)
 */

const STATS_DIR = ROOT . '/storage/stats';
const STATS_MAX_PER_VISITOR = 300;     // bir ziyaretçiden günde en fazla bu kadar görüntüleme sayılır
const STATS_MAX_NEW_PER_IP  = 40;      // tek bir IP'den günde en fazla bu kadar yeni ziyaretçi sayılır
const STATS_MAX_VISITORS    = 20000;   // günlük özet listesinin üst sınırı
const STATS_MAX_SOURCES     = 100;     // günde en fazla bu kadar farklı kaynak; fazlası "diğer"

function stats_is_bot(string $ua): bool
{
    return $ua === '' || (bool) preg_match('/bot|crawl|spider|slurp|headless|lighthouse|pagespeed|preview|monitor|uptime|scan|python|curl|wget|httpclient|okhttp|java\/|go-http|node-fetch|axios|facebookexternalhit|whatsapp|telegram|gpt|claude|perplexity|anthropic|bytespider/i', $ua);
}

/** İstekteki yolu herkese açık sayfa yoluna çevirir ('' = ana sayfa); sayılmayacaksa null. */
function stats_path(string $p): ?string
{
    $p = (string) parse_url($p, PHP_URL_PATH);
    $p = rawurldecode($p);
    $base = base_path();
    if ($base !== '' && str_starts_with($p, $base . '/')) {
        $p = substr($p, strlen($base));
    }
    $p = trim($p, '/');
    return strlen($p) <= 200 && in_array($p, site_public_paths(), true) ? $p : null;
}

/** Yönlendiren adresten kaynak adı ("google.com"); kendi sitemizse ya da yoksa ''. */
function stats_source(string $ref): string
{
    $host = strtolower((string) parse_url($ref, PHP_URL_HOST));
    $host = (string) preg_replace('/^www\./', '', $host);
    if ($host === '' || strlen($host) > 80 || !preg_match('/^[a-z0-9.\-]+\.[a-z]{2,}$/', $host)) {
        return '';
    }
    $own = (string) preg_replace('/^www\./', '', strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''))));
    $site = (string) preg_replace('/^www\./', '', strtolower((string) parse_url((string) cfg('url'), PHP_URL_HOST)));
    return $host === $own || $host === $site ? '' : $host;
}

/** /olc isteği: her zaman gövdesiz yanıt verir. */
function stats_track(): void
{
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        return;
    }
    http_response_code(204);
    $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (stats_is_bot($ua)) {
        return;
    }
    // Yalnızca sitenin kendi sayfalarından gelen bildirimler sayılır
    $fetchSite = (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    if ($fetchSite !== '' && $fetchSite !== 'same-origin') {
        return;
    }
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '' && strcasecmp((string) parse_url($origin, PHP_URL_HOST), (string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''))) !== 0) {
        return;
    }
    $path = stats_path(is_string($_POST['p'] ?? null) ? $_POST['p'] : '');
    if ($path === null) {
        return;
    }
    try {
        stats_hit($path, stats_source(is_string($_POST['r'] ?? null) ? mb_substr($_POST['r'], 0, 500) : ''), (string) ($_SERVER['REMOTE_ADDR'] ?? ''), $ua);
    } catch (Throwable $e) {
        // sayaç hatası ziyaretçiyi etkilemesin
    }
}

function stats_read_file(string $file): array
{
    $d = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
    return is_array($d) ? $d : [];
}

function stats_write_file(string $file, array $data): bool
{
    $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false || !@rename($tmp, $file)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

/**
 * Dünden kalan günlük tuzu ve ziyaretçi özetlerini siler (today.json). Normalde yeni günün ilk sayımı yapar; sayım gelmezse günlük bakım
 * (app/housekeeping.php) yapar, böylece eşleştirmeye yarayabilecek özetler bir gün sonrasına dek kalmaz. @return bool dosya silindiyse true
 */
function stats_purge_stale(): bool
{
    if (!is_file(STATS_DIR . '/today.json')) {
        return false;
    }
    $lock = @fopen(STATS_DIR . '/.lock', 'c');
    if (!$lock) {
        return false;
    }
    try {
        flock($lock, LOCK_EX);
        $t = stats_read_file(STATS_DIR . '/today.json');
        if (($t['date'] ?? '') !== date('Y-m-d')) {
            return @unlink(STATS_DIR . '/today.json');
        }
        return false;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Bir sayfa görüntülemesini kaydeder. Sayıldıysa true. */
function stats_hit(string $path, string $source, string $ip, string $ua): bool
{
    if (!is_dir(STATS_DIR) && !@mkdir(STATS_DIR, 0755, true)) {
        return false;
    }
    $lock = @fopen(STATS_DIR . '/.lock', 'c');
    if (!$lock) {
        return false;
    }
    try {
        flock($lock, LOCK_EX);
        $today = date('Y-m-d');
        $tf = STATS_DIR . '/today.json';
        $t = stats_read_file($tf);
        if (($t['date'] ?? '') !== $today || empty($t['salt'])) {
            $t = ['date' => $today, 'salt' => bin2hex(random_bytes(16)), 'seen' => [], 'ips' => []];   // yeni gün: dünün tuzu ve özetleri silinir
        }
        $id = substr(hash('sha256', $t['salt'] . '|' . $ip . '|' . $ua), 0, 16);
        $n = (int) ($t['seen'][$id] ?? 0);
        $new = $n === 0;
        $bin = @inet_pton($ip);
        $net = $bin !== false && strlen($bin) === 16 ? bin2hex(substr($bin, 0, 8)) : $ip;   // IPv6: /64 öneki (tek abone çok adres kullanabilir)
        $ipId = substr(hash('sha256', $t['salt'] . '|ip|' . $net), 0, 12);   // IP'nin kendisi saklanmaz
        $fromIp = (int) ($t['ips'][$ipId] ?? 0);
        if ($n >= STATS_MAX_PER_VISITOR || ($new && (count($t['seen']) >= STATS_MAX_VISITORS || $fromIp >= STATS_MAX_NEW_PER_IP))) {
            return false;
        }
        $t['seen'][$id] = $n + 1;
        if ($new) {
            $t['ips'][$ipId] = $fromIp + 1;
        }

        $mf = STATS_DIR . '/' . substr($today, 0, 7) . '.json';
        $m = stats_read_file($mf);
        $day = (array) ($m['days'][$today] ?? []) + ['v' => 0, 'u' => 0, 'um' => 0, 'p' => [], 'r' => []];
        $day['v']++;
        $key = '/' . $path;
        $day['p'][$key] = (int) ($day['p'][$key] ?? 0) + 1;
        if ($new) {
            $day['u']++;
            if (preg_match('/Mobi|Android|iPhone|iPad|iPod/i', $ua)) {
                $day['um']++;
            }
            if ($source === '') {
                $source = '(doğrudan)';     // adresi yazarak, yer iminden ya da uygulamadan gelenler
            }
        }
        if ($source !== '') {
            if (!isset($day['r'][$source]) && count($day['r']) >= STATS_MAX_SOURCES) {
                $source = '(diğer)';
            }
            $day['r'][$source] = (int) ($day['r'][$source] ?? 0) + 1;
        }
        $m['days'][$today] = $day;
        return stats_write_file($tf, $t) && stats_write_file($mf, $m);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * Son $days günün gün gün sayıları (bugün dahil, eskiden yeniye). Kayıt olmayan günler sıfırdır.
 * @return array<string, array{v:int, u:int, um:int, p:array, r:array}>
 */
function stats_days(int $days): array
{
    $days = max(1, min(400, $days));
    $months = [];
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime('-' . $i . ' days'));
        $mk = substr($d, 0, 7);
        $months[$mk] ??= stats_read_file(STATS_DIR . '/' . $mk . '.json');
        $row = (array) ($months[$mk]['days'][$d] ?? []);
        $out[$d] = ['v' => (int) ($row['v'] ?? 0), 'u' => (int) ($row['u'] ?? 0), 'um' => (int) ($row['um'] ?? 0), 'p' => (array) ($row['p'] ?? []), 'r' => (array) ($row['r'] ?? [])];
    }
    return $out;
}

/** Bir dönemin toplamları: görüntüleme, ziyaret (günlük tekil ziyaretçilerin toplamı), telefon payı, sayfalar, kaynaklar. */
function stats_totals(array $days): array
{
    $t = ['v' => 0, 'u' => 0, 'um' => 0, 'p' => [], 'r' => []];
    foreach ($days as $row) {
        $t['v'] += $row['v'];
        $t['u'] += $row['u'];
        $t['um'] += $row['um'];
        foreach ($row['p'] as $k => $n) $t['p'][$k] = ($t['p'][$k] ?? 0) + (int) $n;
        foreach ($row['r'] as $k => $n) $t['r'][$k] = ($t['r'][$k] ?? 0) + (int) $n;
    }
    arsort($t['p']);
    arsort($t['r']);
    return $t;
}

/** Sayacın ilk kayıt günü (hiç kayıt yoksa null). */
function stats_since(): ?string
{
    $files = glob(STATS_DIR . '/[0-9][0-9][0-9][0-9]-[0-9][0-9].json') ?: [];
    sort($files);
    foreach ($files as $f) {
        $days = array_keys((array) (stats_read_file($f)['days'] ?? []));
        if ($days) {
            sort($days);
            return (string) $days[0];
        }
    }
    return null;
}
