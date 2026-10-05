<?php
/**
 * Site taraması (SEO denetimi): her herkese açık sayfa dahili olarak üretilir, başlık, açıklama, ana başlık,
 * asıl adres, paylaşım etiketleri, yapılandırılmış veri ve yazı miktarı denetlenir.
 * Hem yönetim paneli (app/admin/sections/seo.php) hem de yapay zekâ erişimi (app/mcp/tools.php) bunu kullanır.
 */

// Sayfa yolu eşlemesi panel çekirdeğindeki adm_url() işlevini kullanır (yalnızca işlev tanımları; yan etkisi yoktur).
require_once APP . '/admin/core.php';

const SEOA_SCAN_FILE = '/storage/seo-scan.json';
const SEOA_SCAN_TTL  = 86400;

/** Sabit sayfa yolu → [görünen ad, sayfa kimliği (Sayfa metinleri grubu)]. Adlar sayfa kaydından (panel > Sayfa metinleri > "Sayfa adları") gelir. */
function seoa_static_names(): array
{
    $out = [];
    foreach (site_pages_all() as $key => $pg) {
        $out[$pg['path']] = [$pg['label'], $key];
    }
    return $out;
}

/**
 * Sayfa yolu → [görünen ad, açıklamanın düzenleneceği adres, yerin açıklaması, başlığın düzenleneceği adres, başlık yerinin açıklaması]
 * Statik sayfalarda başlık "Sayfa adları" listesinden (Menü, Fihrist ve alt bilgi), açıklama sayfanın kendi grubundan gelir.
 */
function seoa_page_info(string $path): array
{
    $names = seoa_static_names();
    $reg = texts_registry();
    $genel = isset($reg['genel']) ? adm_url('metinler/genel') : '';
    $genelWhere = 'Sayfa metinleri, "' . ($reg['genel']['label'] ?? 'Menü, Fihrist ve alt bilgi') . '" > Sayfa adları';
    if (isset($names[$path])) {
        [$name, $group] = $names[$path];
        $own = isset($reg[$group]) ? adm_url('metinler/' . $group) : '';
        $ownWhere = 'Sayfa metinleri, "' . ($reg[$group]['label'] ?? $name) . '" > Arama motorları';
        // Duyurular ve Yazılar sayfalarının sekme başlığı kendi grubundadır; diğerlerinde sayfa adı Sayfa adları listesindedir
        $own_title = in_array($group, ['duyurular'], true);
        return [$name, $own, $ownWhere, $own_title ? $own : $genel, $own_title ? $ownWhere : $genelWhere];
    }
    if (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $path, $m)) {
        $s = services()[$m[1]] ?? null;
        $u = adm_url('hizmetler/' . $m[1]);
        $w = 'Hizmetler, "' . ($s['title'] ?? $m[1]) . '"';
        return [$s['title'] ?? $m[1], $u, $w, $u, $w];
    }
    if (preg_match('#^blog/category/([a-z0-9\-]+)$#', $path, $m)) {
        $label = $m[1];
        foreach (posts() as $p) if (slugify((string) ($p['category'] ?? '')) === $m[1]) $label = (string) $p['category'];
        $u = isset($reg['blog']) ? adm_url('metinler/blog') : '';
        $w = 'Sayfa metinleri, "' . ($reg['blog']['label'] ?? 'Makaleler') . '" > Arama motorları';
        return [pg_name('blog') . ': ' . $label . ' kategorisi', $u, $w, $u, $w];
    }
    if (preg_match('#^blog/([a-z0-9\-]+)$#', $path, $m)) {
        $p = posts()[$m[1]] ?? null;
        $u = adm_url('blog/' . $m[1]);
        $w = 'Yazılar, "' . ($p['title'] ?? $m[1]) . '"';
        return [$p['title'] ?? $m[1], $u, $w, $u, $w];
    }
    if (preg_match('#^kariyer/([a-z0-9\-]+)$#', $path, $m)) {
        $x = ilan_find_slug($m[1]);
        $u = $x ? adm_url('ilanlar/' . $x['id']) : '';
        $w = 'İş ilanları, "' . ($x['title'] ?? $m[1]) . '"';
        return [$x['title'] ?? $m[1], $u, $w, $u, $w];
    }
    return [$path, '', '', '', ''];
}

function seoa_clean(string $s): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $s));
}

/** Tek bir sayfanın HTML'ini inceler. */
function seoa_parse(string $html, string $path): array
{
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $x = new DOMXPath($doc);
    $attr = function (string $q, string $a) use ($x): string {
        $n = $x->query($q)->item(0);
        return $n instanceof DOMElement ? seoa_clean($n->getAttribute($a)) : '';
    };

    $titleNode = $x->query('//head/title')->item(0);
    $d = [
        'title'    => $titleNode ? seoa_clean($titleNode->textContent) : '',
        'desc'     => $attr('//meta[@name="description"]', 'content'),
        'h1'       => $x->query('//h1')->length,
        'canon'    => $attr('//link[@rel="canonical"]', 'href'),
        'robots'   => $attr('//meta[@name="robots"]', 'content'),
        'og_title' => $attr('//meta[@property="og:title"]', 'content'),
        'og_desc'  => $attr('//meta[@property="og:description"]', 'content'),
        'og_image' => $attr('//meta[@property="og:image"]', 'content'),
        'noalt'    => $x->query('//img[not(@alt)]')->length,
    ];

    // Yapılandırılmış veri
    $d['ld_found'] = 0;
    $d['ld_bad']   = 0;
    $d['ld_page']  = false;
    foreach ($x->query('//script[@type="application/ld+json"]') as $s) {
        $d['ld_found']++;
        $j = json_decode((string) $s->textContent, true);
        if (!is_array($j)) { $d['ld_bad']++; continue; }
        $nodes = isset($j['@graph']) && is_array($j['@graph']) ? $j['@graph'] : [$j];
        foreach ($nodes as $n) {
            foreach ((array) ($n['@type'] ?? []) as $t) {
                if (is_string($t) && preg_match('/^(WebPage|[A-Za-z]+Page)$/', $t)) $d['ld_page'] = true;
            }
        }
    }

    // <main> içindeki görünür yazı
    $main = $x->query('//main')->item(0);
    $words = 0;
    if ($main) {
        $clone = $main->cloneNode(true);
        $cx = new DOMXPath($doc);
        foreach ($cx->query('.//script|.//style|.//noscript|.//template|.//svg', $clone) as $rm) {
            if ($rm->parentNode) $rm->parentNode->removeChild($rm);
        }
        $words = preg_match_all('/[\p{L}\p{N}]+/u', seoa_clean($clone->textContent));
    }
    $d['words'] = (int) $words;
    return $d;
}

function seoa_trunc(string $s, int $n): string
{
    return mb_strlen($s) > $n ? rtrim(mb_substr($s, 0, $n - 1)) . '…' : $s;
}

/** Bir sayfanın kontrol listesi. Her kontrol: id, label, chip (kısa), st (ok|warn|err), val, hint */
function seoa_checks(array $d, string $path, string $expectCanon): array
{
    $c = [];
    $add = function (string $id, string $label, string $st, string $chip, string $val, string $hint) use (&$c): void {
        $c[$id] = ['id' => $id, 'label' => $label, 'st' => $st, 'chip' => $chip, 'val' => $val, 'hint' => $hint];
    };

    $tl = mb_strlen($d['title']);
    if ($d['title'] === '') $add('title', 'Sayfa başlığı', 'err', 'Başlık yok', '', 'Sekmede ve arama sonuçlarında görünen başlık eksik. Sayfaya 30-60 karakterlik, konuyu anlatan bir başlık yazın.');
    elseif ($tl < 30) $add('title', 'Sayfa başlığı', 'warn', 'Başlık kısa', $tl . ' karakter', 'Başlık kısa kalmış. Arama sonuçlarında daha etkili olması için 30-60 karakter arası, sayfanın konusunu anlatan bir başlık yazın.');
    elseif ($tl > 60) $add('title', 'Sayfa başlığı', 'warn', 'Başlık uzun', $tl . ' karakter', 'Google yaklaşık 60 karakterden sonrasını keser. Başlığı kısaltın; site adı sonuna otomatik eklenir.');
    else $add('title', 'Sayfa başlığı', 'ok', '', $tl . ' karakter', 'Uzunluk uygun.');

    $dl = mb_strlen($d['desc']);
    if ($d['desc'] === '') $add('desc', 'Arama sonucu açıklaması', 'err', 'Açıklama yok', '', 'Başlığın altında görünen kısa açıklama eksik. 70-160 karakterlik, sayfanın ne sunduğunu anlatan bir cümle yazın.');
    elseif ($dl < 70) $add('desc', 'Arama sonucu açıklaması', 'warn', 'Açıklama kısa', $dl . ' karakter', 'Açıklama kısa kalmış. 70-160 karakter arasında, ziyaretçiyi tıklamaya ikna eden bir özet yazın.');
    elseif ($dl > 160) $add('desc', 'Arama sonucu açıklaması', 'warn', 'Açıklama uzun', $dl . ' karakter', 'Google yaklaşık 160 karakterden sonrasını keser. En önemli bilgiyi başa alın ve kısaltın.');
    else $add('desc', 'Arama sonucu açıklaması', 'ok', '', $dl . ' karakter', 'Uzunluk uygun.');

    if ($d['h1'] === 0) $add('h1', 'Sayfanın ana başlığı', 'err', 'Ana başlık yok', '', 'Sayfada ana başlık (H1) bulunamadı. Her sayfada konuyu anlatan tek bir ana başlık olmalıdır.');
    elseif ($d['h1'] > 1) $add('h1', 'Sayfanın ana başlığı', 'warn', 'Birden çok ana başlık', $d['h1'] . ' adet', 'Sayfada birden fazla ana başlık var. Arama motorları tek ana başlık bekler; diğerlerini ara başlık yapın.');
    else $add('h1', 'Sayfanın ana başlığı', 'ok', '', '1 adet', 'Tek ana başlık var.');

    if ($d['canon'] === '') $add('canon', 'Asıl sayfa adresi', 'err', 'Asıl adres yok', '', 'Sayfanın asıl adresi (canonical) belirtilmemiş. Bu otomatik eklenir; görüyorsanız teknik destekle paylaşın.');
    elseif (rtrim($d['canon'], '/') !== rtrim($expectCanon, '/')) $add('canon', 'Asıl sayfa adresi', 'warn', 'Asıl adres farklı', $d['canon'], 'Sayfanın asıl adresi kendi adresinden farklı: ' . $expectCanon . ' olmalı. Bu otomatik ayarlanır; görüyorsanız teknik destekle paylaşın.');
    else $add('canon', 'Asıl sayfa adresi', 'ok', '', $d['canon'], 'Sayfanın kendi adresini gösteriyor.');

    if (preg_match('/noindex/i', $d['robots'])) $add('robots', 'Arama motorlarına açık mı', 'err', 'Dizine kapalı', $d['robots'], 'Bu sayfa arama sonuçlarından gizlenmiş (noindex). Bilerek yapılmadıysa teknik destekle paylaşın.');
    else $add('robots', 'Arama motorlarına açık mı', 'ok', '', '', 'Arama motorları bu sayfayı listeleyebilir.');

    foreach (['og_title' => ['Paylaşım başlığı', 'Paylaşım başlığı yok'], 'og_desc' => ['Paylaşım açıklaması', 'Paylaşım açıklaması yok'], 'og_image' => ['Paylaşım görseli', 'Paylaşım görseli yok']] as $k => [$lab, $chip]) {
        if ($d[$k] === '') $add($k, $lab, 'warn', $chip, '', 'WhatsApp, LinkedIn ve sosyal ağlarda bağlantı paylaşıldığında kartta görünecek bilgi eksik. Bu otomatik hazırlanır; görüyorsanız teknik destekle paylaşın.');
        else $add($k, $lab, 'ok', '', $k === 'og_image' ? basename((string) parse_url($d[$k], PHP_URL_PATH)) : '', 'Paylaşım kartı için hazır.');
    }

    if ($d['ld_bad'] > 0) $add('ld', 'Arama motorları için sayfa bilgisi', 'err', 'Yapılandırılmış veri bozuk', '', 'Sayfanın makine okunur tanımı (JSON-LD) okunamıyor. Bu otomatik üretilir; görüyorsanız teknik destekle paylaşın.');
    elseif (!$d['ld_page']) $add('ld', 'Arama motorları için sayfa bilgisi', 'warn', 'Sayfa tanımı yok', '', 'Sayfanın makine okunur tanımında sayfa türü bulunamadı. Bu otomatik üretilir; görüyorsanız teknik destekle paylaşın.');
    else $add('ld', 'Arama motorları için sayfa bilgisi', 'ok', '', '', 'Sayfa türü ve kurum bilgisi arama motorlarına tanıtılıyor.');

    if ($d['noalt'] > 0) $add('alt', 'Görsel açıklamaları', 'warn', $d['noalt'] . ' görselde açıklama yok', $d['noalt'] . ' görsel', 'Bazı görsellerin açıklaması (alt metni) yok. Görme engelliler ve arama motorları görseli bu yazıyla anlar.');
    else $add('alt', 'Görsel açıklamaları', 'ok', '', '', 'Tüm görsellerde açıklama alanı var.');

    if ($d['words'] < 100) $add('words', 'Sayfadaki yazı miktarı', 'warn', 'Yazı az', $d['words'] . ' kelime', 'Sayfada çok az yazı var. Arama motorları ve yapay zekâ asistanları konuyu anlamak için biraz daha metne ihtiyaç duyar.');
    else $add('words', 'Sayfadaki yazı miktarı', 'ok', '', $d['words'] . ' kelime', 'Yeterli yazı var.');

    return $c;
}

/** İçerik değişti mi? (panelden bir kayıt yapıldıysa taramayı yenilemek için) */
function seoa_fingerprint(): string
{
    $m = 0;
    foreach (array_merge(glob(ROOT . '/storage/content/*.json') ?: [], [ROOT . '/storage/duyurular.json', ROOT . '/storage/ilanlar.json']) as $f) {
        if (is_file($f)) $m = max($m, (int) filemtime($f));
    }
    return (string) $m;
}

function seoa_scan_run(): array
{
    $t0    = microtime(true);
    $pages = [];
    foreach (seo_public_paths() as $path) {
        $rendered = seo_render_page($path);
        if ($rendered === null) continue;
        $html = $rendered['html'];
        $d = seoa_parse($html, $path);
        [$name, $fix, $where, $fixTitle, $whereTitle] = seoa_page_info($path);
        $canon = absolute_url($path);
        $pages[$path] = [
            'path' => $path, 'name' => $name, 'url' => $canon, 'fix' => $fix, 'where' => $where, 'fix_title' => $fixTitle, 'where_title' => $whereTitle,
            'd' => $d, 'checks' => seoa_checks($d, $path, $canon),
        ];
    }

    // Aynı başlık ya da açıklamanın birden çok sayfada kullanılması
    $byT = [];
    $byD = [];
    foreach ($pages as $p => $pg) {
        if ($pg['d']['title'] !== '') $byT[mb_strtolower($pg['d']['title'])][] = $p;
        if ($pg['d']['desc'] !== '')  $byD[mb_strtolower($pg['d']['desc'])][] = $p;
    }
    foreach ($pages as $p => &$pg) {
        $tk = mb_strtolower($pg['d']['title']);
        $dk = mb_strtolower($pg['d']['desc']);
        $others = fn(array $g) => implode(', ', array_map(fn($q) => $pages[$q]['name'], array_values(array_diff($g, [$p]))));
        if ($pg['d']['title'] !== '' && count($byT[$tk]) > 1) {
            $pg['checks']['dup_title'] = ['id' => 'dup_title', 'label' => 'Başlık benzersiz mi', 'st' => 'warn', 'chip' => 'Aynı başlık', 'val' => '', 'hint' => 'Bu başlık başka sayfalarda da kullanılıyor (' . $others($byT[$tk]) . '). Her sayfanın başlığı farklı olmalı.'];
        } else {
            $pg['checks']['dup_title'] = ['id' => 'dup_title', 'label' => 'Başlık benzersiz mi', 'st' => 'ok', 'chip' => '', 'val' => '', 'hint' => 'Başka hiçbir sayfa aynı başlığı kullanmıyor.'];
        }
        if ($pg['d']['desc'] !== '' && count($byD[$dk]) > 1) {
            $pg['checks']['dup_desc'] = ['id' => 'dup_desc', 'label' => 'Açıklama benzersiz mi', 'st' => 'warn', 'chip' => 'Aynı açıklama', 'val' => '', 'hint' => 'Bu açıklama başka sayfalarda da kullanılıyor (' . $others($byD[$dk]) . '). Her sayfanın açıklaması farklı olmalı.'];
        } else {
            $pg['checks']['dup_desc'] = ['id' => 'dup_desc', 'label' => 'Açıklama benzersiz mi', 'st' => 'ok', 'chip' => '', 'val' => '', 'hint' => 'Başka hiçbir sayfa aynı açıklamayı kullanmıyor.'];
        }
        $pg['checks'] = array_values($pg['checks']);
        $pg['err']  = count(array_filter($pg['checks'], fn($c) => $c['st'] === 'err'));
        $pg['warn'] = count(array_filter($pg['checks'], fn($c) => $c['st'] === 'warn'));
    }
    unset($pg);

    $total = $pass = $err = $warn = 0;
    $ld = 0;
    foreach ($pages as $pg) {
        foreach ($pg['checks'] as $c) {
            $total++;
            if ($c['st'] === 'ok') $pass++;
            elseif ($c['st'] === 'err') $err++;
            else $warn++;
        }
        if (!empty($pg['d']['ld_page']) && empty($pg['d']['ld_bad'])) $ld++;
    }
    $scan = [
        'time'   => time(),
        'ms'     => (int) round((microtime(true) - $t0) * 1000),
        'fp'     => seoa_fingerprint(),
        'pages'  => array_values($pages),
        'total'  => $total, 'pass' => $pass, 'err' => $err, 'warn' => $warn,
        'score'  => $total ? (int) floor($pass / $total * 100) : 0,
        'ld_ok'  => $ld,
    ];
    @file_put_contents(ROOT . SEOA_SCAN_FILE, json_encode($scan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    return $scan;
}

/** Önbellekteki tarama; yoksa, 24 saatten eskiyse ya da içerik değiştiyse yeniden taranır. */
function seoa_scan(bool $force = false): array
{
    $f = ROOT . SEOA_SCAN_FILE;
    if (!$force && is_file($f)) {
        $c = json_decode((string) file_get_contents($f), true);
        if (is_array($c) && isset($c['time'], $c['pages']) && time() - (int) $c['time'] < SEOA_SCAN_TTL && ($c['fp'] ?? '') === seoa_fingerprint()) {
            return $c;
        }
    }
    return seoa_scan_run();
}

function seoa_when(int $t): string
{
    $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    return (int) date('j', $t) . ' ' . $months[(int) date('n', $t) - 1] . ' ' . date('Y, H:i', $t);
}

/** Yapıştırılan kod ya da tüm <meta> etiketinden doğrulama kodunu ayıklar. [kod, hata] */
function seoa_verify_code(string $raw, string $metaName, string $label): array
{
    $raw = trim($raw);
    if ($raw === '') return ['', ''];
    if (stripos($raw, '<meta') !== false || stripos($raw, 'content=') !== false) {
        if (preg_match('/\bname\s*=\s*["\']([^"\']+)["\']/i', $raw, $nm) && strcasecmp($nm[1], $metaName) !== 0) {
            return ['', $label . ': yapıştırdığınız etiket başka bir hizmete ait görünüyor (' . $nm[1] . '). Doğru hizmetin kutusuna yapıştırın.'];
        }
        if (!preg_match('/\bcontent\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $raw, $m)) {
            return ['', $label . ': etiketin içinde content="..." kısmı bulunamadı. Etiketin tamamını ya da yalnızca koddan oluşan değeri yapıştırın.'];
        }
        $raw = trim($m[1] !== '' ? $m[1] : ($m[2] ?? ''));
    }
    if (!preg_match('/^[A-Za-z0-9_\-:.=]{4,120}$/', $raw)) {
        return ['', $label . ': kod geçersiz görünüyor. Yalnızca harf, rakam ve - _ . : = karakterlerinden oluşan 4-120 karakterlik bir kod olmalı.'];
    }
    return [$raw, ''];
}
