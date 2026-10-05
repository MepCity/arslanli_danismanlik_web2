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
 *   refs      referans logoları (liste)
 *   lists     süreç adımları, ilkeler, misyon/vizyon maddeleri, banka hesapları vb.
 *   texts     sayfa metinleri (Aşama 2B'de eklenecek; t() fonksiyonu)
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
            $out = [];
            foreach ((array) (require APP . '/data/site.php')['refs'] as $slug => $name) {
                $out[] = ['name' => $name, 'logo' => 'img/refs/' . $slug . '.webp'];
            }
            return $out;
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

/* ---------- Hizmetler ---------- */

/** Hizmet sayfalarındaki hareketli çizimler: anahtar => açıklama. Aşama 2A: v3 hizmetlerinde 'glyph' yok ('color' var); panel Hizmetler bölümü uyarlanırken kalkar. */
function service_glyphs(): array
{
    return [
        'lattice' => 'Moleküler kafes (Ar-Ge, bilim)',
        'rise'    => 'Yükselen çubuklar (büyüme)',
        'gear'    => 'Dönen ölçek (sanayi)',
        'routes'  => 'Rotalar (ticaret, ihracat)',
        'orbit'   => 'Yörüngeler (uluslararası)',
        'print'   => 'Parmak izi (tescil, koruma)',
        'grid'    => 'Hizalanan kareler (kalite)',
        'curve'   => 'Büyüme eğrisi (yatırım)',
        'layers'  => 'Katmanlar (finansman)',
    ];
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

/** Referanslar: [['name' => ..., 'logo' => 'img/refs/x.webp' | 'uploads/refs/...'], ...] */
function refs_list(): array
{
    $list = content_get('refs');
    if (is_array($list)) {
        return array_values(array_filter($list, fn($r) => !empty($r['name']) && !empty($r['logo'])));
    }
    $out = [];
    foreach ((array) site('refs') as $slug => $name) {
        $out[] = ['name' => $name, 'logo' => 'img/refs/' . $slug . '.webp'];
    }
    return $out;
}

/** Yazı görseli adresi (görsel yoksa null) */
function post_image(array $post): ?string
{
    $img = (string) ($post['image'] ?? '');
    if ($img === '') return null;
    return str_contains($img, '/') ? media_url($img) : asset('img/blog/' . $img . '.webp');
}

/* ---------- Sayfa metinleri ---------- */

// Düzenlenebilir sayfa metinleri (texts_registry(), texts_flat(), t()) Aşama 2B'de eklenir (app/data/texts/*.php).
// O zamana dek şablonlardaki metinler sabittir; bu dosyayı çağıran kod bu işlevleri function_exists ile korur.

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
