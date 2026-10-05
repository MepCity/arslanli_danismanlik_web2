<?php
declare(strict_types=1);

/**
 * Yapay zekâ erişimiyle görsel ekleme (yazı görseli, referans logosu).
 * Görsel ya herkese açık bir https adresinden indirilir (gorsel_url) ya da base64 olarak verilir (gorsel_base64);
 * ardından panelden yüklenenlerle aynı işlemden geçer: doğrulama, küçültme, WebP'ye çevirme (image_store).
 *
 * Adresten indirme sunucunun iç ağına kapalıdır: yalnızca https ve 443 numaralı kapı, yalnızca herkese açık IP adresleri,
 * bağlantı çözümlenen adrese sabitlenir (yeniden çözümleme oyununa karşı) ve yönlendirmeler tek tek denetlenir.
 */

const MCP_IMG_MAX = 8388608;   // 8 MB

/** Görsel alan araçların ortak girdi alanları. */
function mcp_image_fields(string $what): array
{
    return [
        'gorsel_url'    => sc_str($what . ': görselin https:// ile başlayan, herkese açık adresi (JPG, PNG ya da WebP; en fazla 8 MB). Sunucu görseli indirir, küçültür ve kendi kopyasını saklar. Yalnızca kullanıcının verdiği ya da doğruladığınız bir adresi kullanın.',
            ['maxLength' => 1000, 'pattern' => '^(https://\S+)?$', 'x-ipucu' => 'https:// ile başlayan görsel adresi']),
        'gorsel_base64' => sc_str('Adres yerine görselin kendisi, base64 olarak (başında "data:image/png;base64," olabilir). Küçük görseller içindir; büyük görsellerde gorsel_url kullanın.',
            ['maxLength' => 11000000]),
    ];
}

/** Herkese açık bir IPv4 adresi mi (yerel, özel ağ ve ayrılmış aralıklar değil). */
function mcp_ip_public(string $ip): bool
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }
    $n = ip2long($ip);
    foreach ([['100.64.0.0', 10], ['192.0.0.0', 24], ['198.18.0.0', 15], ['224.0.0.0', 4]] as [$net, $bits]) {     // taşıyıcı içi, deneme ve çok noktaya yayın (multicast) aralıkları
        if (($n & (-1 << (32 - $bits))) === (ip2long($net) & (-1 << (32 - $bits)))) {
            return false;
        }
    }
    return true;
}

/** Görseli adresten geçici bir dosyaya indirir; yolu döner. */
function mcp_image_download(string $url): string
{
    if (!function_exists('curl_init')) {
        mcp_fail('Bu sunucuda adresten görsel indirilemiyor. Görseli yönetim panelinden yükleyin ya da gorsel_base64 ile verin.');
    }
    $tmp = (string) tempnam(sys_get_temp_dir(), 'arsl');
    for ($hop = 0; $hop < 3; $hop++) {
        $p = parse_url($url);
        if (!is_array($p) || strtolower((string) ($p['scheme'] ?? '')) !== 'https' || empty($p['host']) || isset($p['user']) || isset($p['pass']) || (int) ($p['port'] ?? 443) !== 443) {
            @unlink($tmp);
            mcp_fail('Görsel adresi https:// ile başlayan, kullanıcı adı ve özel kapı numarası içermeyen bir adres olmalı.');
        }
        // Yalnızca komut satırından (php-cli) çalışan sınama düzeneği için: ağa çıkmadan yerel bir sunucudan görsel alır.
        // Web isteklerinde (cli-server dahil) PHP_SAPI farklıdır; bu kanca orada hiçbir zaman çalışmaz.
        if (PHP_SAPI === 'cli' && isset($GLOBALS['mcp_test_fetch']) && is_callable($GLOBALS['mcp_test_fetch'])) {
            @unlink($tmp);
            $tmp = (string) tempnam(sys_get_temp_dir(), 'arsl');
            file_put_contents($tmp, (string) ($GLOBALS['mcp_test_fetch'])($url));
            return $tmp;
        }
        $host = strtolower((string) $p['host']);
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (@gethostbynamel($host) ?: []);
        $ips = array_values(array_filter($ips, 'mcp_ip_public'));
        if (!$ips) {
            @unlink($tmp);
            mcp_fail('Görsel adresi bulunamadı ya da herkese açık bir sunucuya ait değil: ' . mcp_clip($host, 80));
        }
        $fh = fopen($tmp, 'w');
        $size = 0;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RESOLVE        => [$host . ':443:' . $ips[0]],
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_USERAGENT      => 'ArslanliSite/1.2 (+' . absolute_url() . ')',
            CURLOPT_HTTPHEADER     => ['Accept: image/webp,image/png,image/jpeg'],
            CURLOPT_WRITEFUNCTION  => function ($c, $chunk) use ($fh, &$size) {
                $size += strlen($chunk);
                return $size > MCP_IMG_MAX ? 0 : fwrite($fh, $chunk);
            },
        ]);
        $ok = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $next = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        if ((string) curl_getinfo($ch, CURLINFO_PRIMARY_IP) !== $ips[0]) {     // bağlantı sabitlenen adrese gitmediyse indirme başarısız sayılır
            $ok = false;
            $code = 0;
            $next = '';
        }
        unset($ch);
        fclose($fh);
        if ($code >= 300 && $code < 400 && $next !== '') {
            $url = $next;        // yönlendirme: yeni adres de aynı denetimden geçer
            continue;
        }
        if ($size > MCP_IMG_MAX) {
            @unlink($tmp);
            mcp_fail('Görsel en fazla 8 MB olabilir.');
        }
        if ($ok === false || $code !== 200 || $size === 0) {
            @unlink($tmp);
            mcp_fail('Görsel indirilemedi' . ($code ? ' (sunucu ' . $code . ' yanıtı verdi)' : '') . '. Adresin doğrudan bir görsel dosyasına gittiğinden ve herkese açık olduğundan emin olun.');
        }
        return $tmp;
    }
    @unlink($tmp);
    mcp_fail('Görsel adresi çok fazla yönlendirme yapıyor.');
}

/**
 * Araç girdisindeki görseli (gorsel_url ya da gorsel_base64) geçici bir dosyaya alır; çağıran işi bitince dosyayı siler.
 * @return string|null geçici dosyanın yolu; girdide görsel yoksa null
 */
function mcp_image_tmp(array $a): ?string
{
    $url = trim((string) ($a['gorsel_url'] ?? ''));
    $b64 = trim((string) ($a['gorsel_base64'] ?? ''));
    if ($url === '' && $b64 === '') {
        return null;
    }
    if ($url !== '' && $b64 !== '') {
        mcp_fail('gorsel_url ve gorsel_base64 birlikte verilemez; birini seçin.');
    }
    if ($url !== '') {
        return mcp_image_download($url);
    }
    $bin = base64_decode((string) preg_replace('/\s+/', '', (string) preg_replace('#^data:image/[a-z0-9.+\-]+;base64,#i', '', $b64)), true);
    if ($bin === false || $bin === '') {
        mcp_fail('gorsel_base64 geçerli bir base64 verisi değil.');
    }
    if (strlen($bin) > MCP_IMG_MAX) {
        mcp_fail('Görsel en fazla 8 MB olabilir.');
    }
    $tmp = (string) tempnam(sys_get_temp_dir(), 'arsl');
    file_put_contents($tmp, $bin);
    return $tmp;
}

/**
 * Araç girdisindeki görseli (gorsel_url ya da gorsel_base64) siteye alır (yazı görseli).
 * @return string|null "uploads/{klasör}/{ad}.webp"; girdide görsel yoksa null
 */
function mcp_image_input(array $a, string $dir, int $maxW): ?string
{
    $tmp = mcp_image_tmp($a);
    if ($tmp === null) {
        return null;
    }
    try {
        return image_store($tmp, $dir, $maxW);
    } catch (RuntimeException $e) {
        mcp_fail($e->getMessage() . ' (Verilen adres ya da veri bir JPG, PNG ya da WebP görseli olmalı; SVG kabul edilmez.)');
    } finally {
        if (is_file($tmp)) {
            @unlink($tmp);
        }
    }
}
