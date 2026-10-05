<?php
declare(strict_types=1);

/**
 * IndexNow: içerik değişince Bing, Yandex, Seznam ve Naver'e anında haber verir.
 * (Google IndexNow kullanmaz; Google için Search Console + site haritası yeterlidir.)
 *
 * - indexnow_on_change($key): panelde bir içerik kaydedilince seo_changed() üzerinden çağrılır.
 * - indexnow_submit($urls): adresleri bildirir; yerel ortamda hiçbir şey göndermez.
 * Hiçbir hata panelde kaydı bozmaz ya da yavaşlatmaz: yanıt gönderildikten sonra çalışır, her şey yakalanır.
 */

const INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const INDEXNOW_WAIT     = 60;      // iki bildirim arası en az saniye
const INDEXNOW_MAX_URLS = 10000;
const INDEXNOW_LOG_KEEP = 50;

function indexnow_file(string $name): string
{
    return ROOT . '/storage/' . $name;
}

function indexnow_read(string $name): array
{
    $f = indexnow_file($name);
    $d = is_file($f) ? json_decode((string) @file_get_contents($f), true) : null;
    return is_array($d) ? $d : [];
}

function indexnow_write(string $name, array $data): void
{
    @file_put_contents(indexnow_file($name), json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/** Anahtar biçimi: 8-128 karakter, harf, rakam ve tire. */
function indexnow_key_ok(string $key): bool
{
    return (bool) preg_match('/^[A-Za-z0-9\-]{8,128}$/', $key);
}

function indexnow_new_key(): string
{
    return bin2hex(random_bytes(16));
}

/** Alan adı: küçük harf, port ve baştaki "www." olmadan. */
function indexnow_host(string $host): string
{
    $host = strtolower(trim($host));
    $host = (string) preg_replace('/:\d+$/', '', $host);
    return (string) preg_replace('/^www\./', '', $host);
}

function indexnow_site_host(): string
{
    return indexnow_host((string) parse_url((string) cfg('url'), PHP_URL_HOST));
}

/** Test ortamı: yalnızca komut satırında, ortam değişkeniyle sahte bir uç nokta verilebilir. */
function indexnow_test_endpoint(): string
{
    return PHP_SAPI === 'cli' ? (string) getenv('INDEXNOW_TEST_ENDPOINT') : '';
}

/** Yerel geliştirme mi? Öyleyse hiçbir şey gönderilmez. */
function indexnow_is_local(): bool
{
    if (indexnow_test_endpoint() !== '') return false;
    $site = indexnow_site_host();
    if ($site === '' || $site === 'localhost' || preg_match('/^127\./', $site) || preg_match('/\.(local|test|localhost)$/', $site) || $site === '::1') {
        return true;
    }
    $req = indexnow_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return $req === '' || $req !== $site;   // istek başka bir adresten geliyorsa (yerel geliştirme)
}

/** Sitedeki tüm herkese açık adresler (mutlak). */
function indexnow_all_urls(): array
{
    return array_map(fn($p) => absolute_url($p), seo_public_paths());
}

/** İçerik anahtarı → değişen sayfaların mutlak adresleri. */
function indexnow_urls_for(string $key): array
{
    $paths = [];
    switch ($key) {
        case 'services':
            $paths = ['hizmetler'];
            foreach (array_keys(services()) as $s) $paths[] = 'urunler/detay/' . $s;
            break;
        case 'posts':
            foreach (seo_public_paths() as $p) if ($p === 'blog' || str_starts_with($p, 'blog/')) $paths[] = $p;
            break;
        case 'duyurular':
            $paths = ['', 'duyurular'];
            break;
        case 'ilanlar':
            // Kariyer sayfası ve en az bir kez yayınlanmış ilanların adresleri (kapanan ilanın adresi yeniden taranıp 410 görülsün).
            // Taslak adresleri bildirilmez. Silinen ilan listede artık yoktur; adresi bildirilmez, 404'ü arama motoru kendi taramasında görür.
            $paths = ['kariyer'];
            foreach (ilan_all() as $x) if ((string) ($x['published'] ?? '') !== '') $paths[] = ilan_path($x);
            break;
        case 'refs':
            $paths = ['', 'referans'];
            break;
        case 'texts':
        case 'settings':
        case 'lists':
            return indexnow_all_urls();
        case 'features':
            // Bölüm açıldı ya da kapandı: kapanan sayfaların da yeniden taranması (404 görülmesi) için hepsi bildirilir
            $all = indexnow_all_urls();
            foreach (array_keys(site_static_routes_all()) as $p) $all[] = absolute_url((string) $p);
            return array_values(array_unique($all));
        default:
            return [];   // 'seo' ve bilinmeyen anahtarlar: bildirilecek sayfa yok
    }
    return array_map(fn($p) => absolute_url((string) $p), array_values(array_unique($paths)));
}

/** Panelde içerik kaydedildi. Bildirim, yanıt tarayıcıya gittikten sonra yapılır. */
function indexnow_on_change(string $key): void
{
    static $pending = [];
    static $registered = false;
    try {
        if (empty(seo_settings()['indexnow'])) return;
        $urls = indexnow_urls_for($key);
        if (!$urls) return;
        foreach ($urls as $u) $pending[$u] = true;
        if ($registered) return;
        $registered = true;
        register_shutdown_function(function () use (&$pending): void {
            try {
                if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
                // Yalnızca görünen içeriği gerçekten değişen (ya da artık bulunmayan) sayfalar bildirilir: taslak kaydı, kimsenin göremeyeceği değişiklik bildirim üretmez
                $list = indexnow_changed_only(array_keys($pending));
                if ($list) indexnow_submit($list);
            } catch (Throwable $e) {
                // panel işini etkilemesin
            }
        });
    } catch (Throwable $e) {
        // sessizce geç
    }
}

/**
 * Adreslerden görünen içeriği son bildirimden beri değişenleri ayırır (Markdown özetinin parmak izi, app/agents/lib.php).
 * Sayfa artık yoksa (kapatılmış bölüm, silinmiş ilan) adres listede kalır: arama motoru 404'ü görsün.
 * @param string[] $urls
 * @return string[]
 */
function indexnow_changed_only(array $urls): array
{
    if (!is_file(APP . '/agents/lib.php')) return $urls;
    require_once APP . '/agents/lib.php';
    require_once APP . '/agents/markdown.php';
    agent_flush();
    agent_refresh_globals();   // bu istekte kaydedilen içerik şablonlarda görünsün
    $seen = indexnow_read('indexnow-seen.json');
    $next = $seen;
    $out  = [];
    $root = absolute_url();
    foreach ($urls as $u) {
        $p = trim(substr($u, strlen($root)), '/');
        $snap = agent_snapshot($p);
        if (!$snap) {
            // Sayfa artık yok: daha önce bildirilmişse bir kez haber verilir (404/410 görülsün); hiç bildirilmemiş adres (taslak, hiç yayınlanmamış) gönderilmez
            if (isset($seen[$p])) $out[] = $u;
            unset($next[$p]);
            continue;
        }
        if (($seen[$p] ?? '') !== $snap['hash']) $out[] = $u;
        $next[$p] = $snap['hash'];
    }
    if ($next !== $seen) indexnow_write('indexnow-seen.json', $next);
    return $out;
}

/** Son bildirimler (en yeni önce). */
function indexnow_log(): array
{
    return array_slice(array_reverse(indexnow_read('indexnow.log.json')), 0, 20);
}

function indexnow_log_add(int $n, string $kind, string $msg): void
{
    try {
        $log = indexnow_read('indexnow.log.json');
        $log[] = ['t' => time(), 'n' => $n, 'kind' => $kind, 'msg' => $msg];
        indexnow_write('indexnow.log.json', array_slice($log, -INDEXNOW_LOG_KEEP));
    } catch (Throwable $e) {
    }
}

/** Gönderilecek gövde: yalnızca bu sitenin alan adındaki mutlak adresler, en çok 10.000. */
function indexnow_payload(array $urls, string $key): array
{
    $host = indexnow_site_host();
    $keep = [];
    foreach ($urls as $u) {
        if (!is_string($u) || !preg_match('#^https?://#i', $u)) continue;
        if (indexnow_host((string) parse_url($u, PHP_URL_HOST)) !== $host) continue;
        $keep[$u] = true;
    }
    return [
        'host'        => (string) parse_url((string) cfg('url'), PHP_URL_HOST),
        'key'         => $key,
        'keyLocation' => absolute_url($key . '.txt'),
        'urlList'     => array_slice(array_keys($keep), 0, INDEXNOW_MAX_URLS),
    ];
}

/** @return array{0:int,1:string} [HTTP kodu (0 = bağlanılamadı), hata metni] */
function indexnow_post(string $endpoint, array $payload): array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json; charset=utf-8'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err  = curl_error($ch);
        return [$code, $err];
    }
    $ctx = stream_context_create(['http' => [
        'method' => 'POST', 'timeout' => 3, 'ignore_errors' => true,
        'header' => "Content-Type: application/json; charset=utf-8\r\n", 'content' => $body,
    ]]);
    $r = @file_get_contents($endpoint, false, $ctx);
    $code = 0;
    foreach ($http_response_header ?? [] as $h) if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $code = (int) $m[1];
    return [$code, $r === false ? 'bağlantı kurulamadı' : ''];
}

function indexnow_code_text(int $code, string $err): string
{
    if ($code === 0) return 'Bağlanılamadı' . ($err !== '' ? ' (' . $err . ')' : '');
    if ($code === 200) return 'Gönderildi';
    if ($code === 202) return 'Kabul edildi, anahtar doğrulanacak';
    if ($code === 400) return 'Geçersiz istek (400)';
    if ($code === 403) return 'Anahtar doğrulanamadı (403): anahtar dosyası açılıyor mu?';
    if ($code === 422) return 'Adresler bu siteye ait görünmüyor (422)';
    if ($code === 429) return 'Çok sık istek (429), sonra tekrar denenecek';
    return 'Beklenmeyen yanıt (' . $code . ')';
}

/**
 * Adresleri bildirir. Hiçbir koşulda istisna fırlatmaz.
 * @return array{status:string, sent:int, message:string}  status: kapali | yerel | sirada | gonderildi | hata
 */
/** Sırada bekleyen adresler varsa ve bekleme süresi dolduysa, yanıt gönderildikten sonra iletir (her panel isteğinde çağrılır). */
function indexnow_flush_later(): void
{
    try {
        $q = indexnow_read('indexnow-queue.json');
        if (empty($q['urls']) || time() - (int) ($q['last'] ?? 0) < INDEXNOW_WAIT || empty(seo_settings()['indexnow'])) return;
        register_shutdown_function(function (): void {
            try {
                if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
                indexnow_submit([]);
            } catch (Throwable $e) {
            }
        });
    } catch (Throwable $e) {
    }
}

function indexnow_submit(array $urls, bool $force = false): array
{
    $res = ['status' => 'hata', 'sent' => 0, 'message' => ''];
    try {
        $s   = seo_settings();
        $key = (string) $s['indexnow_key'];
        if (empty($s['indexnow']) || !indexnow_key_ok($key)) {
            return ['status' => 'kapali', 'sent' => 0, 'message' => 'IndexNow kapalı.'];
        }
        $payload = indexnow_payload($urls, $key);
        $n = count($payload['urlList']);
        $queued = (array) (indexnow_read('indexnow-queue.json')['urls'] ?? []);
        if ($n === 0 && !$queued) {
            return ['status' => 'kapali', 'sent' => 0, 'message' => 'Bildirilecek adres yok.'];
        }
        if (indexnow_is_local()) {
            indexnow_log_add($n, 'yerel', 'Yerel ortam, gönderilmedi');
            return ['status' => 'yerel', 'sent' => 0, 'message' => 'Yerel ortam, gönderilmedi'];
        }

        // Sıra: bekleyen adresler birleştirilir, en çok 60 saniyede bir gönderilir.
        $q = indexnow_read('indexnow-queue.json');
        $pending = array_fill_keys(array_map('strval', (array) ($q['urls'] ?? [])), true);
        foreach ($payload['urlList'] as $u) $pending[$u] = true;
        $last = (int) ($q['last'] ?? 0);
        if (!$force && time() - $last < INDEXNOW_WAIT) {
            indexnow_write('indexnow-queue.json', ['urls' => array_slice(array_keys($pending), 0, INDEXNOW_MAX_URLS), 'last' => $last]);
            indexnow_log_add($n, 'sirada', 'Sıraya alındı; bir dakika sonra panelde ilk işlemde gönderilecek');
            return ['status' => 'sirada', 'sent' => 0, 'message' => 'Sıraya alındı.'];
        }

        $send = indexnow_payload(array_keys($pending), $key);
        indexnow_write('indexnow-queue.json', ['urls' => [], 'last' => time()]);
        $endpoint = indexnow_test_endpoint() ?: INDEXNOW_ENDPOINT;
        [$code, $err] = indexnow_post($endpoint, $send);
        $ok  = $code === 200 || $code === 202;
        $msg = indexnow_code_text($code, $err);
        if (!$ok && ($code === 0 || $code >= 500 || $code === 429)) {
            indexnow_write('indexnow-queue.json', ['urls' => $send['urlList'], 'last' => time()]);   // sonra yeniden denenir
        }
        indexnow_log_add(count($send['urlList']), $ok ? 'gonderildi' : 'hata', $msg);
        return ['status' => $ok ? 'gonderildi' : 'hata', 'sent' => $ok ? count($send['urlList']) : 0, 'message' => $msg];
    } catch (Throwable $e) {
        return $res;
    }
}
