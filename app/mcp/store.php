<?php
declare(strict_types=1);

/**
 * Yapay zekâ erişimi: dosya tabanlı depo (veritabanı yok).
 * ---------------------------------------------------------------------------
 * storage/mcp/keys.json      erişim anahtarları (yalnızca sha256 özeti saklanır, anahtarın kendisi asla)
 * storage/mcp/oauth.json     OAuth istemcileri, tek kullanımlık kodlar, erişim ve yenileme belirteçleri (hepsi özet olarak)
 * storage/mcp/rate.json      anahtar başına dakikalık çağrı sayacı
 * storage/mcp/throttle.json  IP başına hatalı anahtar denemesi ve kayıt sınırı
 * storage/mcp/log.jsonl      denetim kaydı (son 2000 satır)
 *
 * Her yazım kilit altında yapılır ve geçici dosya + yer değiştirme ile atomiktir. storage/ klasörü web'e kapalıdır.
 */

const MCP_LOG_KEEP = 2000;

/** Verilebilecek izinler: ad => [başlık, açıklama, kişisel veri içerir mi] */
function mcp_scopes(): array
{
    return [
        'okuma'        => ['Okuma', 'Siteyi, sayfaları, duyuruları, yayınlanmış iş ilanlarını, hizmetleri, yazıları, referansları, kurumsal listeleri, yasal metinleri, sayfa metinlerini, ayarları, ziyaretçi sayılarını ve değişiklik geçmişini okur; arama motoru taraması yapar.', false],
        'duyurular'    => ['Duyurular', 'Duyuru ekler, günceller, siler ve açılışta öne çıkarır.', false],
        'icerik'       => ['Sayfa içerikleri', 'Sayfa metinlerini, hizmet dosyalarını, yazıları, referansları ve kurumsal listeleri (süreç, zaman çizelgesi, ilkeler, misyon, vizyon, form seçenekleri, hizmet hedef eşleştiricisi) ve yasal metinleri (KVKK, çerez politikası maddeleri) değiştirir; hizmet, yazı, referans ve iş ilanı ekler, günceller ya da siler (iş ilanını yayınlar, kapatır); görsel indirir; değişiklikleri geri alır. Banka hesaplarını değiştirmek için ayrıca Ayarlar izni gerekir.', false],
        'ayarlar'      => ['Ayarlar', 'Bölümleri sitede açıp kapatır; iletişim ve şirket bilgilerini, e-posta gönderim ayarlarını, bülten saatlik sınırını ve arama motoru ayarlarını değiştirir. Form bildirimlerinin gittiği adresi değiştirmek için ayrıca Gelen kutusu izni gerekir.', false],
        'gelen_kutusu' => ['Gelen kutusu', 'Form kayıtlarını, bülten abonelerini ve iş başvurularını okur; form kaydı ve iş başvurusu siler, şüpheli kayıtları ayıklar ya da gelen kutusuna alır, aboneyi abonelikten çıkarır. Sayfa içerikleri izniyle birlikte bülten e-postası taslağı hazırlar ve düzenler (e-posta gönderemez; gönderimi yönetici panelden başlatır). Kişisel veri içerir.', true],
    ];
}

/** PHP 8.1'deki array_is_list() karşılığı (site PHP 8.0 ile de çalışır). */
function mcp_is_list(array $a): bool
{
    return $a === [] || array_keys($a) === range(0, count($a) - 1);
}

function mcp_dir(): string
{
    $dir = ROOT . '/storage/mcp';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return $dir;
}

function mcp_json_flags(): int
{
    return JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
}

/** Kilitsiz okuma (yazımlar yer değiştirmeyle atomik olduğundan tutarlıdır). */
function mcp_json_read(string $name): array
{
    $file = mcp_dir() . '/' . $name . '.json';
    if (!is_file($file)) {
        return [];
    }
    $d = json_decode((string) @file_get_contents($file), true);
    return is_array($d) ? $d : [];
}

/**
 * Oku-değiştir-yaz: $fn(array &$data) kilit altında çalışır, dönüş değeri aynen verilir.
 * @return mixed
 */
function mcp_json_update(string $name, callable $fn)
{
    $dir = mcp_dir();
    $lock = @fopen($dir . '/.' . $name . '.lock', 'c');
    if (!$lock) {
        throw new RuntimeException('Depo kilidi açılamadı (storage/mcp yazılabilir mi?).');
    }
    try {
        flock($lock, LOCK_EX);
        $file = $dir . '/' . $name . '.json';
        $data = is_file($file) ? json_decode((string) @file_get_contents($file), true) : [];
        if (!is_array($data)) {
            $data = [];
        }
        $out = $fn($data);
        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, json_encode($data, mcp_json_flags() | JSON_PRETTY_PRINT)) === false) {
            @unlink($tmp);
            throw new RuntimeException('Depo yazılamadı (storage/mcp yazılabilir mi?).');
        }
        @chmod($tmp, 0600);
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            throw new RuntimeException('Depo yazılamadı (storage/mcp yazılabilir mi?).');
        }
        return $out;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Yazma araçları arasında karşılıklı dışlama: aynı anda iki yazım birbirinin üzerine yazmasın. */
function mcp_write_lock(callable $fn)
{
    $lock = @fopen(mcp_dir() . '/.write.lock', 'c');
    if (!$lock) {
        throw new RuntimeException('Yazma kilidi açılamadı.');
    }
    try {
        flock($lock, LOCK_EX);
        return $fn();
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Rastgele base62 metin (kriptografik). */
function mcp_random(int $len): string
{
    $alphabet = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
    $out = '';
    for ($i = 0; $i < $len; $i++) {
        $out .= $alphabet[random_int(0, 61)];
    }
    return $out;
}

function mcp_hash(string $secret): string
{
    return hash('sha256', $secret);
}

function mcp_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
}

/* =========================================================================
   Erişim anahtarları
   ========================================================================= */

/** Geçerli izin listesi: bilinmeyenler atılır, "okuma" her zaman vardır. */
function mcp_clean_scopes(array $scopes): array
{
    $known = array_keys(mcp_scopes());
    $out = ['okuma'];
    foreach ($scopes as $s) {
        if (is_string($s) && in_array($s, $known, true) && !in_array($s, $out, true)) {
            $out[] = $s;
        }
    }
    // Sıra her zaman mcp_scopes() sırasıdır
    return array_values(array_filter($known, fn($k) => in_array($k, $out, true)));
}

function mcp_keys(): array
{
    $d = mcp_json_read('keys');
    return array_values((array) ($d['keys'] ?? []));
}

/** Yeni anahtar üretir. @return array{0: string, 1: array} [anahtarın kendisi (bir kez gösterilir), kayıt] */
function mcp_key_create(string $name, array $scopes, ?int $days): array
{
    $name = trim((string) preg_replace('/\s+/u', ' ', $name));
    $name = mb_substr($name, 0, 80);
    $secret = 'arsl_' . mcp_random(40);
    $rec = [
        'id'          => bin2hex(random_bytes(6)),
        'name'        => $name,
        'prefix'      => substr($secret, 0, 9),
        'hash'        => mcp_hash($secret),
        'scopes'      => mcp_clean_scopes($scopes),
        'created'     => date('c'),
        'expires'     => $days !== null ? date('c', time() + $days * 86400) : null,
        'last_used'   => null,
        'last_client' => '',
        'revoked'     => false,
    ];
    mcp_json_update('keys', function (array &$d) use ($rec): void {
        $d['keys'] = array_values((array) ($d['keys'] ?? []));
        $d['keys'][] = $rec;
    });
    return [$secret, $rec];
}

function mcp_key_active(array $k): bool
{
    if (!empty($k['revoked'])) {
        return false;
    }
    if (!empty($k['expires']) && (int) strtotime((string) $k['expires']) <= time()) {
        return false;
    }
    return true;
}

function mcp_key_get(string $id): ?array
{
    foreach (mcp_keys() as $k) {
        if (($k['id'] ?? '') === $id) {
            return $k;
        }
    }
    return null;
}

/** Düz anahtardan kayıt (iptal edilmiş ya da süresi dolmuş da dönebilir; çağıran mcp_key_active ile bakar). */
function mcp_key_by_secret(string $secret): ?array
{
    if (!preg_match('/^arsl_[0-9A-Za-z]{40}$/', $secret)) {
        return null;
    }
    $h = mcp_hash($secret);
    foreach (mcp_keys() as $k) {
        if (isset($k['hash']) && hash_equals((string) $k['hash'], $h)) {
            return $k;
        }
    }
    return null;
}

/** Erişimi kaldırır: anahtar ve ona bağlı tüm kodlar ve belirteçler hemen geçersiz olur. */
function mcp_key_revoke(string $id): bool
{
    $found = false;
    mcp_json_update('keys', function (array &$d) use ($id, &$found): void {
        foreach ((array) ($d['keys'] ?? []) as $i => $k) {
            if (($k['id'] ?? '') === $id && empty($k['revoked'])) {
                $d['keys'][$i]['revoked'] = true;
                $d['keys'][$i]['revoked_at'] = date('c');
                $found = true;
            }
        }
    });
    if ($found) {
        mcp_json_update('oauth', function (array &$d) use ($id): void {
            foreach (['codes', 'access', 'refresh'] as $bucket) {
                foreach ((array) ($d[$bucket] ?? []) as $h => $row) {
                    if (($row['key_id'] ?? '') === $id) {
                        unset($d[$bucket][$h]);
                    }
                }
            }
        });
    }
    return $found;
}

/** Son kullanım zamanını (en çok dakikada bir) ve uygulama adını kaydeder. */
function mcp_key_touch(string $id, string $client): void
{
    $k = mcp_key_get($id);
    if (!$k) {
        return;
    }
    $last = !empty($k['last_used']) ? (int) strtotime((string) $k['last_used']) : 0;
    $client = mb_substr(trim($client), 0, 100);
    if (time() - $last < 60 && ($client === '' || $client === ($k['last_client'] ?? ''))) {
        return;
    }
    mcp_json_update('keys', function (array &$d) use ($id, $client): void {
        foreach ((array) ($d['keys'] ?? []) as $i => $x) {
            if (($x['id'] ?? '') === $id) {
                $d['keys'][$i]['last_used'] = date('c');
                if ($client !== '') {
                    $d['keys'][$i]['last_client'] = $client;
                }
            }
        }
    });
}

/* =========================================================================
   İstekten kimlik çözümü
   ========================================================================= */

/** Authorization başlığı: HTTP_AUTHORIZATION, REDIRECT_HTTP_AUTHORIZATION ya da getallheaders(). */
function mcp_auth_header(): string
{
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'] as $k) {
        if (!empty($_SERVER[$k]) && is_string($_SERVER[$k])) {
            return trim($_SERVER[$k]);
        }
    }
    foreach (['getallheaders', 'apache_request_headers'] as $fn) {
        if (function_exists($fn)) {
            foreach ((array) $fn() as $name => $value) {
                if (strcasecmp((string) $name, 'Authorization') === 0 && is_string($value)) {
                    return trim($value);
                }
            }
        }
    }
    return '';
}

/** "Authorization: Bearer xxx" içindeki belirteç ('' = yok). */
function mcp_bearer(): string
{
    if (preg_match('/^Bearer\s+(\S+)$/i', mcp_auth_header(), $m)) {
        return $m[1];
    }
    return '';
}

/**
 * Anahtar ya da OAuth erişim belirtecinden kimlik.
 * @return array{key_id:string, name:string, scopes:array, client:string, via:string}|null
 */
function mcp_authenticate(string $token): ?array
{
    if ($token === '' || strlen($token) > 200) {
        return null;
    }
    $key = null;
    $client = '';
    $via = 'key';
    if (str_starts_with($token, 'arat_')) {
        $via = 'oauth';
        $h = mcp_hash($token);
        $d = mcp_json_read('oauth');
        $row = $d['access'][$h] ?? null;
        if (!is_array($row) || (int) ($row['expires'] ?? 0) <= time()) {
            return null;
        }
        $key = mcp_key_get((string) ($row['key_id'] ?? ''));
        $client = (string) ($d['clients'][$row['client_id'] ?? '']['name'] ?? '');
        if ($client === '') {
            $client = 'OAuth uygulaması';
        }
    } else {
        $key = mcp_key_by_secret($token);
    }
    if (!$key || !mcp_key_active($key)) {
        return null;
    }
    return [
        'key_id' => (string) $key['id'],
        'name'   => (string) $key['name'],
        'scopes' => mcp_clean_scopes((array) ($key['scopes'] ?? [])),
        'client' => $client,
        'via'    => $via,
    ];
}

/* =========================================================================
   Sınırlar
   ========================================================================= */

/**
 * Anahtar başına dakikalık sınır. $write true ise yazma sayacına da bakılır ve yazılır.
 * @return array{ok:bool, retry:int}
 */
function mcp_rate(string $keyId, bool $write = false, bool $record = true): array
{
    return mcp_json_update('rate', function (array &$d) use ($keyId, $write, $record): array {
        $now = time();
        foreach ($d as $id => $row) {      // bir dakikadan eski kayıtları temizle
            $c = array_values(array_filter((array) ($row['c'] ?? []), fn($t) => $t > $now - 60));
            $w = array_values(array_filter((array) ($row['w'] ?? []), fn($t) => $t > $now - 60));
            if (!$c && !$w) {
                unset($d[$id]);
            } else {
                $d[$id] = ['c' => $c, 'w' => $w];
            }
        }
        $row = $d[$keyId] ?? ['c' => [], 'w' => []];
        if ($write) {
            if (count($row['w']) >= 30) {
                return ['ok' => false, 'retry' => max(1, (int) min($row['w']) + 60 - $now)];
            }
            if ($record) {
                $row['w'][] = $now;
            }
        } else {
            if (count($row['c']) >= 120) {
                return ['ok' => false, 'retry' => max(1, (int) min($row['c']) + 60 - $now)];
            }
            if ($record) {
                $row['c'][] = $now;
            }
        }
        $d[$keyId] = $row;
        return ['ok' => true, 'retry' => 0];
    });
}

/**
 * IP başına sınır: $bucket için $max olay / $window saniye.
 * $record true ise olay eklenir. @return bool true: sınır aşılmadı
 */
function mcp_ip_limit(string $bucket, int $max, int $window, bool $record): bool
{
    $ip = $bucket . ':' . substr(mcp_hash(mcp_client_ip()), 0, 24);
    return mcp_json_update('throttle', function (array &$d) use ($ip, $max, $window, $record): bool {
        $now = time();
        foreach ($d as $k => $times) {
            $keep = array_values(array_filter((array) $times, fn($t) => $t > $now - 86400));
            if (!$keep) {
                unset($d[$k]);
            } else {
                $d[$k] = $keep;
            }
        }
        $times = array_values(array_filter((array) ($d[$ip] ?? []), fn($t) => $t > $now - $window));
        $over = count($times) >= $max;
        if ($record && !$over) {
            $times[] = $now;
        }
        if ($times) {
            $d[$ip] = $times;
        } else {
            unset($d[$ip]);
        }
        return !$over;
    });
}

/** Sınır aşıldı mı (kayıt eklemeden)? */
function mcp_ip_blocked(string $bucket, int $max, int $window): bool
{
    $ip = $bucket . ':' . substr(mcp_hash(mcp_client_ip()), 0, 24);
    $d = mcp_json_read('throttle');
    $now = time();
    $n = count(array_filter((array) ($d[$ip] ?? []), fn($t) => $t > $now - $window));
    return $n >= $max;
}

/* =========================================================================
   Denetim kaydı
   ========================================================================= */

/** Tek satırlık kayıt ekler; son MCP_LOG_KEEP satır tutulur. */
function mcp_log(array $entry): void
{
    $file = mcp_dir() . '/log.jsonl';
    $entry = ['time' => date('c')] + $entry;
    $line = json_encode($entry, mcp_json_flags()) . "\n";
    $fh = @fopen($file, 'c+');
    if (!$fh) {
        return;
    }
    try {
        flock($fh, LOCK_EX);
        fseek($fh, 0, SEEK_END);
        fwrite($fh, $line);
        // Her ~25 kayıtta bir ya da dosya büyüdüğünde kırp
        clearstatcache(true, $file);
        if (filesize($file) > 400000 || mt_rand(1, 25) === 1) {
            rewind($fh);
            $lines = [];
            while (($l = fgets($fh)) !== false) {
                $lines[] = $l;
            }
            if (count($lines) > MCP_LOG_KEEP) {
                $lines = array_slice($lines, -MCP_LOG_KEEP);
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, implode('', $lines));
            }
        }
        fflush($fh);
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
    @chmod($file, 0600);
}

/** Son $n kayıt, en yeni önce. */
function mcp_log_tail(int $n = 100, string $keyId = ''): array
{
    $file = mcp_dir() . '/log.jsonl';
    if (!is_file($file)) {
        return [];
    }
    $out = [];
    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    for ($i = count($lines) - 1; $i >= 0 && count($out) < $n; $i--) {
        $r = json_decode($lines[$i], true);
        if (!is_array($r)) {
            continue;
        }
        if ($keyId !== '' && ($r['key'] ?? '') !== $keyId) {
            continue;
        }
        $out[] = $r;
    }
    return $out;
}

/* =========================================================================
   Adresler
   ========================================================================= */

/**
 * Sitenin mutlak kök adresi (alt klasör dahil, sonda eğik çizgi yok).
 * Yerel geliştirmede (127.0.0.1, localhost) istekteki adres kullanılır; canlıda her zaman ayarlardaki adres
 * (Host başlığına güvenilmez, böylece sahte Host ile yanlış adres üretilemez).
 */
function mcp_origin(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host !== '' && preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d{1,5})?$/', $host)) {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return ($https ? 'https://' : 'http://') . $host . base_path();
    }
    return rtrim((string) cfg('url'), '/') . base_path();
}

function mcp_url(string $path = ''): string
{
    return mcp_origin() . '/' . ltrim($path, '/');
}
