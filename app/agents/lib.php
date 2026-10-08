<?php
declare(strict_types=1);

/**
 * Makine okunur katman: ortak yardımcılar
 * ---------------------------------------------------------------------------
 * - agent_send(): ortak yanıt başlıkları, Last-Modified / ETag ve 304 desteği
 * - agent_route(): bir yolun hangi sayfa şablonuna ait olduğunu bulur
 * - agent_snapshot(): sayfanın gerçek şablonla üretilmiş özeti (başlık, açıklama, Markdown); içerik değişmedikçe önbellekten gelir
 * - agent_lastmods(): sayfaların DÜRÜST son değişiklik zamanı: görünen içeriğin (Markdown özeti) parmak izi değişince güncellenir
 *
 * Markdown ve başlık/açıklama özeti sayfa şablonları çalıştırılarak üretilir; bu yüzden sayfa adları, metin kaydı, görünürlük anahtarları
 * ve içerik deposundaki her değişiklik kendiliğinden yansır.
 */

const AGENT_VERSION = 3;   // biçim değişince eski önbellekler geçersiz olur

function agent_host(): string
{
    return (string) (parse_url((string) cfg('url'), PHP_URL_HOST) ?: 'localhost');
}

/** Var olan dosyaların en yeni değişiklik zamanı (hiçbiri yoksa 0). */
function agent_mtime(array $files): int
{
    $m = 0;
    foreach ($files as $f) {
        $t = is_file($f) ? (int) filemtime($f) : 0;
        if ($t > $m) $m = $t;
    }
    return $m;
}

/** Bugünün başlangıcı: tarihe göre değişen çıktılar için alt sınır. */
function agent_today(): int
{
    return (int) strtotime('today');
}

function agent_content_files(array $keys): array
{
    $out = [];
    foreach ($keys as $k) $out[] = CONTENT_DIR . '/' . $k . '.json';
    return $out;
}

/** İçeriği ve çıktıyı etkileyen bütün dosyalar (veri, metin kaydı, şablonlar, bu katmanın kodu). */
function agent_source_files(): array
{
    $files = $GLOBALS['__agent_files'] ?? null;
    if ($files === null) {
        $files = $GLOBALS['__agent_files'] = array_merge(
            glob(CONTENT_DIR . '/*.json') ?: [],
            [ANN_FILE, ILAN_FILE, ROOT . '/storage/config.local.php', APP . '/config.php', APP . '/bootstrap.php', APP . '/content.php', APP . '/texts.php',
             APP . '/announcements.php', APP . '/ilanlar.php', APP . '/seo.php'],
            glob(APP . '/pages/*.php') ?: [], glob(APP . '/partials/*.php') ?: [], glob(APP . '/data/*.php') ?: [],
            glob(APP . '/data/texts/*.php') ?: [], glob(APP . '/agents/*.php') ?: []
        );
    }
    return $files;
}

/** Kaynakların parmak izi (ad, boyut, zaman) + bugünün tarihi (kalan gün gibi tarihe bağlı yazılar için). */
function agent_fingerprint(): string
{
    $fp = $GLOBALS['__agent_fp'] ?? null;
    if ($fp === null) {
        $acc = '';
        foreach (agent_source_files() as $f) {
            $st = @stat($f);
            if (!$st) continue;
            // Panelden değişen veri dosyaları içeriğiyle (aynı saniyede, aynı boyutta iki kayıt ayırt edilsin); kod ve şablonlar boyut ve zamanla
            $acc .= $f . ':' . $st['size'] . ':' . $st['mtime'] . (str_starts_with($f, ROOT . '/storage/') ? ':' . md5_file($f) : '') . ';';
        }
        $fp = $GLOBALS['__agent_fp'] = md5($acc) . '-' . date('Ymd') . '-' . AGENT_VERSION;
    }
    return $fp;
}

/** Kaynak dosyaların en yeni zamanı. */
function agent_source_mtime(): int
{
    $m = $GLOBALS['__agent_mt'] ?? null;
    if ($m === null) $m = $GLOBALS['__agent_mt'] = agent_mtime(agent_source_files());
    return $m;
}

/** /x.md gibi bir yolu (başta/sonda eğik çizgisiz) sayfa şablonuna çözer; herkese açık değilse null. */
function agent_route(string $path): ?array
{
    $path = trim($path, '/');
    if (!in_array($path, seo_public_paths(), true)) return null;
    $routes = site_static_routes();
    if (isset($routes[$path])) return ['view' => $routes[$path], 'slug' => null, 'path' => $path];
    if (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $path, $m)) return ['view' => 'hizmet', 'slug' => $m[1], 'path' => $path];
    if (preg_match('#^blog/category/([a-z0-9\-]+)$#', $path, $m)) return ['view' => 'blog', 'slug' => $m[1], 'path' => $path];
    if (preg_match('#^blog/([a-z0-9\-]+)$#', $path, $m)) return ['view' => 'yazi', 'slug' => $m[1], 'path' => $path];
    if (preg_match('#^kariyer/([a-z0-9\-]+)$#', $path, $m)) return ['view' => 'ilan', 'slug' => $m[1], 'path' => $path];
    return null;
}

/** Tek satır, boşlukları sadeleştirilmiş metin. */
function agent_line(string $s): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $s));
}

/** Bu istekteki önbellekleri (parmak izi, sayfa özetleri) siler: içerik az önce yazıldıysa taze okunsun. */
function agent_flush(): void
{
    unset($GLOBALS['__agent_fp'], $GLOBALS['__agent_files'], $GLOBALS['__agent_mt'], $GLOBALS['__agent_mem']);
    clearstatcache();
}

/**
 * Genel değişkenleri (hizmetler, yazılar, ortak listeler, ayarlar) dosyalardan yeniler: bu istekte yazılmış içerik (panel kaydı, yapay zekâ erişimi)
 * şablonlarda görünsün. Açılıştaki (app/bootstrap.php) öncelik sırası korunur: config.php < config.local.php < panel ayarları.
 */
function agent_refresh_globals(): void
{
    $GLOBALS['services'] = services_effective();
    $GLOBALS['posts']    = content_get('posts') ?? require APP . '/data/posts.php';
    $GLOBALS['site']     = lists_effective();
    $cfg   = require APP . '/config.php';
    $local = ROOT . '/storage/config.local.php';
    if (is_file($local)) {
        try {
            $over = (static function (string $__file) { return require $__file; })($local);
            if (is_array($over)) $cfg = array_replace_recursive($cfg, $over);
        } catch (Throwable $e) {
        }
    }
    $cfg = settings_apply($cfg, (array) content_get('settings', []));
    $cfg['secret'] = $GLOBALS['config']['secret'] ?? $cfg['secret'];
    $GLOBALS['config'] = $cfg;
}

/**
 * Sayfanın özeti: ['title', 'desc', 'h1', 'view', 'md' (gövde Markdown), 'hash'].
 * Şablon çalıştırılır ve <main> Markdown'a çevrilir; kaynaklar değişmedikçe storage/seo-cache/ içinden okunur.
 */
function agent_snapshot(string $path): ?array
{
    $path = trim($path, '/');
    $mem = &$GLOBALS['__agent_mem'];
    $mem = $mem ?? [];
    if (array_key_exists($path, $mem)) return $mem[$path] ?: null;
    $r = agent_route($path);
    if (!$r) { $mem[$path] = false; return null; }

    $dir  = ROOT . '/storage/seo-cache';
    $file = $dir . '/s-' . sha1($path) . '.json';
    $fp   = agent_fingerprint();
    if (is_file($file)) {
        $c = json_decode((string) @file_get_contents($file), true);
        if (is_array($c) && ($c['fp'] ?? '') === $fp) return $mem[$path] = $c['snap'];
    }
    require_once __DIR__ . '/markdown.php';
    $rendered = seo_render_page($path);
    if ($rendered === null) {
        $mem[$path] = false;
        return null;
    }
    $p    = $rendered['page'];
    $meta = seo_meta($p, $path);
    $conv = agent_main_to_md($rendered['html'], absolute_url($path));
    $snap = [
        'path'  => $path,
        'view'  => $r['view'],
        'title' => (string) ($p['title'] ?: cfg('name')),
        'desc'  => agent_line($meta['desc']),
        'h1'    => $conv['h1'],
        'md'    => $conv['md'],
        'hash'  => sha1($meta['title'] . "\n" . $meta['desc'] . "\n" . agent_hash_text($conv['md'])),
    ];
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @file_put_contents($file, json_encode(['fp' => $fp, 'snap' => $snap], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $mem[$path] = $snap;
}

/** Parmak izi için metin: her gün değişen "N gün kaldı" gibi ifadeler değişiklik sayılmaz. */
function agent_hash_text(string $md): string
{
    $rel = [];
    foreach (['duyurular.kalan.gecti', 'duyurular.kalan.bugun', 'duyurular.kalan.yarin', 'ilan.fis.bugun', 'kariyer.fis.bugun'] as $k) {
        $v = agent_line(t($k));
        if ($v !== '' && $v !== $k) $rel[] = preg_quote($v, '/');
    }
    $md = (string) preg_replace('/\b\d{1,3}\s+gün\w*/u', 'N gün', $md);
    return $rel ? (string) preg_replace('/(?:' . implode('|', $rel) . ')/u', 'N', $md) : $md;
}

/**
 * Sayfaların son değişiklik zamanları (yol => unix zamanı). Görünen içerik (başlık, açıklama, Markdown) değişince güncellenir;
 * ilk görüldüğünde şablonun ve içerik dosyalarının zamanı alınır.
 * @param string[] $paths
 * @return array<string,int>
 */
function agent_lastmods(array $paths): array
{
    $f = ROOT . '/storage/seo-lastmod.json';
    $st = is_file($f) ? json_decode((string) @file_get_contents($f), true) : [];
    $st = is_array($st) ? $st : [];
    $out = [];
    $dirty = false;
    $now = time();
    foreach ($paths as $p) {
        $p = trim($p, '/');
        $snap = agent_snapshot($p);
        if (!$snap) continue;
        $e = $st[$p] ?? null;
        if (!$e) {
            $tpl  = APP . '/pages/' . $snap['view'] . '.php';
            $seed = max(agent_mtime([$tpl]), agent_mtime(glob(CONTENT_DIR . '/*.json') ?: []), agent_mtime([ANN_FILE, ILAN_FILE]));
            $e = ['h' => $snap['hash'], 't' => min($now, $seed ?: $now)];
            $st[$p] = $e;
            $dirty = true;
        } elseif ($e['h'] !== $snap['hash']) {
            $e = ['h' => $snap['hash'], 't' => max($e['t'] + 1, min($now, agent_source_mtime() ?: $now))];
            $st[$p] = $e;
            $dirty = true;
        }
        $out[$p] = (int) $e['t'];
    }
    foreach (array_keys($st) as $k) {   // kalkan sayfaların kaydı silinir
        if (!in_array($k, array_map(fn($x) => trim($x, '/'), seo_public_paths()), true)) { unset($st[$k]); $dirty = true; }
    }
    if ($dirty) @file_put_contents($f, json_encode($st, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $out;
}

function agent_lastmod(string $path): int
{
    return agent_lastmods([$path])[trim($path, '/')] ?? agent_source_mtime();
}

/** Sayfanın kısa adı ve açıklaması (llms.txt ve site haritası için). */
function agent_page_meta(string $path): ?array
{
    $s = agent_snapshot($path);
    return $s ? ['title' => $s['title'], 'desc' => $s['desc'], 'view' => $s['view']] : null;
}

/** Tarayıcı önbelleği ve koşullu istek (ETag, Last-Modified) destekli yanıt. Çıkış yapar. */
function agent_send(string $body, string $type, int $mtime, array $opts = []): void
{
    $status = (int) ($opts['status'] ?? 200);
    $maxAge = (int) ($opts['max_age'] ?? 3600);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if (!in_array($method, ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD');
        header('Content-Type: text/plain; charset=utf-8');
        echo "Method Not Allowed\n";
        exit;
    }

    http_response_code($status);
    header('Content-Type: ' . $type);
    foreach ((array) ($opts['headers'] ?? []) as $h) header($h);
    header('Vary: Accept-Encoding');

    if ($status === 200) {
        $mtime = max(0, min($mtime, time()));
        $etag  = '"' . substr(sha1($body), 0, 24) . '"';
        header('Cache-Control: public, max-age=' . $maxAge);
        header('ETag: ' . $etag);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $mtime) . ' GMT');

        $inm = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        $fresh = false;
        if ($inm !== null && $inm !== '') {
            foreach (explode(',', $inm) as $cand) {
                $cand = (string) preg_replace('#^W/#', '', trim($cand));
                if ($cand === '*' || $cand === $etag) {
                    $fresh = true;
                    break;
                }
            }
        } elseif (!empty($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $since = strtotime((string) $_SERVER['HTTP_IF_MODIFIED_SINCE']);
            $fresh = $since !== false && $mtime <= $since;
        }
        if ($fresh) {
            http_response_code(304);
            header_remove('Content-Type');
            exit;
        }
    } else {
        header('Cache-Control: public, max-age=300');
    }

    header('Content-Length: ' . strlen($body));
    if ($method !== 'HEAD') echo $body;
    exit;
}
