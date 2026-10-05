<?php
declare(strict_types=1);

/**
 * Yasal metinlerin maddeleri: KVKK Aydınlatma Metni ve Çerez Politikası.
 * ---------------------------------------------------------------------------
 * Her belge sıralı bir madde listesidir (panel: Yasal metinler; MCP: yasal_metin_getir / yasal_metin_guncelle). Madde: başlık, fıkralar,
 * harfli liste maddeleri, sade Türkçesi. Madde numaraları, içindekiler ve bağlantı kimlikleri (madde-1, madde-2…) sıradan kendiliğinden üretilir.
 * Varsayılanlar: app/data/legal.php. Kayıt: content 'legal' (belge => madde listesi); her kayıt değişiklik geçmişine düşer ve geri alınır.
 *
 * Çerez Politikası'nın bir kısmı sitenin kendi kodu taranarak üretilir (cerez_scan): çerez, tarayıcı depolaması, dış kaynak, gömülü içerik.
 * Bir fıkra ya da sade Türkçesi 'when' koşuluyla bu taramanın sonucuna bağlanabilir; koşul tutmuyorsa fıkra görünmez. Tarama her zaman kodu okur;
 * metnin kendisi düzenlenir, tarama sonucu düzenlenemez.
 *
 * KVKK madde 5 (iş başvurusu yapan adaylar) 'basvuru-adaylari' bağlantı kimliğini taşır: Kariyer sayfasındaki form bu kimliğe bağlanır.
 * Bu madde silinemez, kimliği değiştirilemez (sırası ve metni değiştirilebilir).
 */

/** Belgeler: anahtar => [ad, panel sekmesi etiketi, sitedeki adres]. */
function legal_docs(): array
{
    return [
        'kvkk'  => ['KVKK Aydınlatma Metni', 'KVKK Aydınlatma Metni', 'kurumsal/kvkk-aydinlatma-metni'],
        'cerez' => ['Çerez Politikası', 'Çerez Politikası', 'kurumsal/cerez-politikasi'],
    ];
}

/** Dışarıdan bağlantı verilen madde kimliği (belge => kimlik): bu maddeler silinemez. */
function legal_anchors(): array
{
    return ['kvkk' => 'basvuru-adaylari'];
}

/** Fıkra koşulları (yalnızca Çerez Politikası): kod => panelde görünen ad. */
function legal_conditions(): array
{
    return [
        'cerez_var'    => 'site çerez kullanıyorsa',
        'cerez_yok'    => 'site çerez kullanmıyorsa',
        'depolama_var' => 'sayfalar tarayıcı depolamasını kullanıyorsa',
        'depolama_yok' => 'sayfalar tarayıcı depolamasını kullanmıyorsa',
        'dis_yok'      => 'dış sunucudan betik ya da stil yüklenmiyorsa',
        'dis_var'      => 'dış sunucudan betik ya da stil yükleniyorsa',
        'gomulu_var'   => 'sayfalarda gömülü (iframe) içerik varsa',
        'blog_acik'    => 'Yazılar bölümü açıksa',
        'blog_kapali'  => 'Yazılar bölümü kapalıysa',
    ];
}

/** Metinde kullanılabilen yerel yer tutucular (belgeye özgü; genel yer tutucular her belgede geçerlidir): ad => açıklama. */
function legal_local_vars(string $doc): array
{
    return $doc === 'kvkk'
        ? ['yetkili' => 'şirket yetkilisinin adı', 'cerez_baglanti' => 'Çerez Politikası sayfasına bağlantı']
        : ['kvkk_baglanti' => 'KVKK Aydınlatma Metni sayfasına bağlantı', 'site_adresi' => 'sitenin adresi', 'sayfalar' => 'tarayıcıda seçim hatırlayan sayfaların adları'];
}

function legal_limits(): array
{
    return [
        'articles' => [3, 20],     // madde sayısı
        'title'    => [3, 90],     // başlık (içindekiler listesine sığar)
        'paras'    => [1, 10],     // maddedeki fıkra sayısı
        'items'    => [0, 14],     // harfli liste maddeleri (a-k… en çok 14 harf)
        'plain'    => [0, 3],      // sade Türkçesi (koşula göre seçilenler dahil)
        'text'     => [5, 2500],   // fıkra, liste maddesi ve sade Türkçesi uzunluğu
    ];
}

/** Belgenin varsayılan maddeleri. */
function legal_default(string $doc): array
{
    static $d = null;
    $d ??= (array) require APP . '/data/legal.php';
    return (array) ($d[$doc] ?? []);
}

/** Belgenin güncel maddeleri (panelden kaydedilmişse o, değilse varsayılan). */
function legal_get(string $doc): array
{
    $stored = content_get('legal', []);
    return is_array($stored) && isset($stored[$doc]) && is_array($stored[$doc]) ? array_values($stored[$doc]) : legal_default($doc);
}

/* ---------- Metin çözümleme (panel ve MCP ortak) ---------- */

/** "[koşul: …]" ön ekini ayırır: [koşul kodu, kalan metin]; ön ek yoksa ['', metin]; bilinmeyen koşulda [null, metin]. */
function legal_split_cond(string $s): array
{
    if (preg_match('/^\[koşul:\s*([^\]]+)\]\s*(.*)$/su', $s, $m)) {
        $code = array_search(trim($m[1]), legal_conditions(), true);
        return [$code === false ? null : (string) $code, $m[2]];
    }
    return ['', $s];
}

/** Bir blok metni (boş satırla ayrılmış fıkralar) satır listesine çevirir. */
function legal_blocks(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $out = [];
    foreach (preg_split('/\n\s*\n/u', trim($text)) as $b) {
        $b = trim((string) preg_replace('/[ \t]+\n|\n[ \t]+/', "\n", $b));
        if ($b !== '') {
            $out[] = $b;
        }
    }
    return $out;
}

/** Panelin metin kutularından madde kurar: fıkralar ve sade Türkçesi boş satırla, liste maddeleri satır satır ayrılır; "[koşul: …]" ön eki koşulu verir. */
function legal_article_from_text(string $title, string $paras, string $items, string $plain, string $anchor = ''): array
{
    $mk = function (string $b): array {
        [$when, $t] = legal_split_cond($b);
        return ['t' => $t, 'when' => $when ?? '?' . $b];
    };
    $list = [];
    foreach (preg_split('/\n/u', str_replace(["\r\n", "\r"], "\n", $items)) as $l) {
        $l = trim($l);
        if ($l !== '') {
            $list[] = ['t' => $l, 'when' => ''];
        }
    }
    return ['title' => trim($title), 'paras' => array_map($mk, legal_blocks($paras)), 'items' => $list, 'plain' => array_map($mk, legal_blocks($plain)), 'anchor' => trim($anchor)];
}

/** Maddeyi metin kutularının içeriğine çevirir (legal_article_from_text'in tersi). */
function legal_article_to_text(array $a): array
{
    $one = fn(array $x) => (($x['when'] ?? '') !== '' && isset(legal_conditions()[$x['when']]) ? '[koşul: ' . legal_conditions()[$x['when']] . '] ' : '') . (string) ($x['t'] ?? '');
    return [
        'title' => (string) ($a['title'] ?? ''),
        'paras' => implode("\n\n", array_map($one, (array) ($a['paras'] ?? []))),
        'items' => implode("\n", array_map(fn($x) => (string) ($x['t'] ?? ''), (array) ($a['items'] ?? []))),
        'plain' => implode("\n\n", array_map($one, (array) ($a['plain'] ?? []))),
        'anchor' => (string) ($a['anchor'] ?? ''),
    ];
}

/** Ham maddeleri kayıt biçimine getirir (doğrulama yapmaz). */
function legal_clean(string $doc, $raw): array
{
    $leaf = function ($rows) use ($doc): array {
        $out = [];
        foreach (is_array($rows) ? $rows : [] as $x) {
            $x = is_array($x) ? $x : ['t' => $x];
            $t = trim(str_replace(["\r\n", "\r"], "\n", (string) ($x['t'] ?? '')));
            $t = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $t);
            $t = trim((string) preg_replace('/[ \t]+\n|\n[ \t]+|\n{2,}/', "\n", $t));
            if ($t !== '') {
                $out[] = ['t' => $t, 'when' => $doc === 'cerez' ? (string) ($x['when'] ?? '') : ((string) ($x['when'] ?? '') === '' ? '' : '?' . $x['when'])];
            }
        }
        return $out;
    };
    $out = [];
    foreach (is_array($raw) ? $raw : [] as $a) {
        if (!is_array($a)) {
            continue;
        }
        $art = ['title' => trim((string) preg_replace('/\s+/u', ' ', (string) ($a['title'] ?? ''))), 'paras' => $leaf($a['paras'] ?? []), 'items' => $leaf($a['items'] ?? []),
            'plain' => $leaf($a['plain'] ?? []), 'anchor' => trim((string) ($a['anchor'] ?? ''))];
        foreach ($art['items'] as &$it) {
            $it['when'] = '';
        }
        unset($it);
        if ($art['title'] !== '' || $art['paras'] || $art['items'] || $art['plain']) {
            $out[] = $art;
        }
    }
    return $out;
}

/** Bir fıkranın metnini denetler (HTML yok, işaretler eşleşiyor, yer tutucular tanıtılmış, uzunluk sınırda); sorun varsa açıklama, yoksa null. */
function legal_text_error(string $doc, string $where, string $t): ?string
{
    $L = legal_limits()['text'];
    if (preg_match('/<\s*\/?\s*[a-zA-Z!?]/', $t)) {
        return $where . ': HTML yazılamaz (“<” işaretinden sonra harf gelmemeli). Kalın, eğik ve vurgu için köşeli işaretleri kullanın.';
    }
    if (($err = text_marks_check($t)) !== null) {
        return $where . ': ' . $err;
    }
    $allowed = array_merge(array_keys(text_globals()), array_keys(legal_local_vars($doc)));
    foreach (text_placeholders($t) as $m) {
        if (!in_array($m[1], $allowed, true)) {
            return $where . ': bilinmeyen yer tutucu {' . $m[1] . '}. Bu belgede yazılabilenler: ' . implode(', ', array_map(fn($a) => '{' . $a . '}', $allowed)) . '.';
        }
        if (isset($m[2]) && !in_array($m[2], TEXT_FILTERS, true)) {
            return $where . ': bilinmeyen biçim {' . $m[1] . '|' . $m[2] . '}.';
        }
    }
    if (preg_match('/[{}]/', (string) preg_replace(TEXT_PH, '', $t))) {
        return $where . ': süslü parantez yalnızca yer tutucu için kullanılır (ör. {firma}); eksik ya da fazla parantez var.';
    }
    $n = mb_strlen(text_strip_marks($t));
    if ($n < $L[0]) {
        return $where . ': en az ' . $L[0] . ' karakter olmalı (şu an ' . $n . ').';
    }
    if ($n > $L[1]) {
        return $where . ': en fazla ' . $L[1] . ' karakter olabilir (şu an ' . $n . ').';
    }
    return null;
}

/** Belgenin doğrulama hataları (okunur Türkçe iletiler); boş dizi = geçerli. $c: legal_clean() çıktısı. */
function legal_errors(string $doc, array $c): array
{
    $L = legal_limits();
    $e = [];
    $add = function (?string $m) use (&$e): void {
        if ($m !== null) {
            $e[] = $m;
        }
    };
    $add(val_count(legal_docs()[$doc][0] . ' maddeleri', count($c), ...$L['articles']));
    $conds = legal_conditions();
    $anchors = [];
    foreach ($c as $i => $a) {
        $w = ($i + 1) . '. madde';
        $add(val_len($w, 'başlığı', $a['title'], ...$L['title']));
        if (preg_match('/<\s*\/?\s*[a-zA-Z!?]|[{}\[\]]/', $a['title'])) {
            $e[] = $w . ': başlıkta HTML, köşeli ya da süslü parantez yazılamaz.';
        }
        $add(val_count($w . ' fıkraları', count($a['paras']), ...$L['paras']));
        $add(val_count($w . ' liste maddeleri', count($a['items']), ...$L['items']));
        $add(val_count($w . ' sade Türkçesi', count($a['plain']), ...$L['plain']));
        foreach (['paras' => 'fıkra', 'items' => 'liste maddesi', 'plain' => 'sade Türkçesi'] as $f => $name) {
            foreach ($a[$f] as $j => $x) {
                $ww = $w . ' ' . ($j + 1) . '. ' . $name;
                $when = (string) $x['when'];
                if ($when !== '' && $doc !== 'cerez') {
                    $e[] = $ww . ': koşul yalnızca Çerez Politikası’nda kullanılabilir.';
                } elseif ($when !== '' && !isset($conds[$when])) {
                    $e[] = $ww . ': bilinmeyen koşul. Geçerli koşullar: ' . implode('; ', array_map(fn($x) => '[koşul: ' . $x . ']', $conds)) . '.';
                }
                $add(legal_text_error($doc, $ww, (string) $x['t']));
            }
        }
        if ($a['anchor'] !== '') {
            $anchors[] = $a['anchor'];
        }
    }
    $need = legal_anchors()[$doc] ?? null;
    if ($need !== null && !in_array($need, $anchors, true)) {
        $e[] = 'Kariyer sayfasındaki başvuru formu bu belgenin “iş başvurusu yapan adaylar” maddesine (' . $need . ') bağlanır; bu madde silinemez ve bağlantı kimliği değiştirilemez. Maddenin başlığını ve metnini değiştirebilir, yerini taşıyabilirsiniz.';
    }
    foreach ($anchors as $an) {
        if ($an !== $need) {
            $e[] = 'Bağlantı kimliği yalnızca sistemce verilir; “' . $an . '” kimliği yazılamaz.';
        }
    }
    return $e;
}

/**
 * Bir belgenin maddelerini kaydeder (panel ve MCP). $raw: madde listesi (ham).
 * @return array{ok:bool, errors:string[], changed:bool}
 */
function legal_save(string $doc, $raw): array
{
    if (!isset(legal_docs()[$doc])) {
        return ['ok' => false, 'errors' => ['Bilinmeyen belge: ' . $doc . '. Geçerli belgeler: ' . implode(', ', array_keys(legal_docs())) . '.'], 'changed' => false];
    }
    $c = legal_clean($doc, $raw);
    // Sistemin verdiği bağlantı kimliği yazarın girdisinden değil, kayıtlı maddeden gelir: aynı başlıkla eşleşen eski maddenin kimliği korunur
    $need = legal_anchors()[$doc] ?? null;
    if ($need !== null) {
        foreach ($c as &$a) {
            $a['anchor'] = $a['anchor'] === $need ? $need : '';
        }
        unset($a);
    }
    $errors = legal_errors($doc, $c);
    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'changed' => false];
    }
    if (json_encode($c) === json_encode(legal_get($doc))) {
        return ['ok' => true, 'errors' => [], 'changed' => false];
    }
    $stored = content_get('legal', []);
    $stored = is_array($stored) ? $stored : [];
    $stored[$doc] = $c;
    if (json_encode($c) === json_encode(legal_default($doc))) {
        unset($stored[$doc]);   // varsayılana dönüldü
    }
    if (!content_put('legal', $stored)) {
        return ['ok' => false, 'errors' => ['Kaydedilemedi: storage klasörü yazılabilir mi?'], 'changed' => false];
    }
    return ['ok' => true, 'errors' => [], 'changed' => true];
}

/** Dışarıdan gelen kaydı (yedek geri yükleme, geçmiş) süzer: geçersiz belge ve maddeler düşer. */
function legal_sanitize_all($stored): array
{
    $out = [];
    foreach (is_array($stored) ? $stored : [] as $doc => $arts) {
        if (!isset(legal_docs()[(string) $doc])) {
            continue;
        }
        $c = legal_clean((string) $doc, $arts);
        if (!legal_errors((string) $doc, $c)) {
            $out[$doc] = $c;
        }
    }
    return $out;
}

/* ---------- Sayfa: tarama ve gösterim ---------- */

/**
 * Sitenin kendi kodunu tarar: çerez, tarayıcı depolaması, dış kaynak, gömülü içerik. Sonuç Çerez Politikası'nın hangi fıkralarının görüneceğini belirler.
 * @return array{cookies:bool, storage_pages:string[], embeds:bool, external:bool}
 */
function cerez_scan(): array
{
    static $r = null;
    if ($r !== null) {
        return $r;
    }
    $files = array_merge(
        glob(ROOT . '/index.php') ?: [],
        array_filter(glob(APP . '/*.php') ?: [], fn($f) => basename($f) !== 'legal.php'),   // bu dosya tarama kalıplarını kendi içinde taşır
        glob(APP . '/partials/*.php') ?: [],
        array_filter(glob(APP . '/pages/*.php') ?: [], fn($f) => !in_array(basename($f), ['cerez.php', 'kvkk.php', '_legal.php'], true)),
        glob(ROOT . '/assets/js/*.js') ?: [],
        glob(ROOT . '/assets/js/pages/*.js') ?: []
    );
    $r = ['cookies' => false, 'storage_pages' => [], 'embeds' => false, 'external' => false];
    foreach ($files as $f) {
        $src = (string) @file_get_contents($f);
        if (basename($f) === 'app.js') {
            // Ziyaret sayacı (bkz. app/stats.php) yalnızca yönetici tarayıcısındaki bir işareti okur; ziyaretçinin tarayıcısına bir şey yazmaz,
            // bu yüzden "tarayıcı depolama alanı kullanılıyor" taramasına girmez
            $src = (string) preg_replace('#/\* -+ Ziyaret sayacı.*?(?=/\* -+ Sayfa betiği)#su', '', $src);
        }
        if (preg_match('/\bsetcookie\s*\(|\bsession_start\s*\(|document\.cookie/', $src)) {
            $r['cookies'] = true;
        }
        if (str_ends_with($f, '.js') && preg_match('/\b(local|session)Storage\s*[.\[]/', $src)) {
            $r['storage_pages'][] = basename($f, '.js');
        }
        if (str_ends_with($f, '.php') && stripos($src, '<iframe') !== false) {
            $r['embeds'] = true;
        }
        if (str_ends_with($f, '.php') && preg_match('#<(script|link)[^>]+(src|href)="https?://#i', $src)) {
            $r['external'] = true;
        }
    }
    return $r;
}

/** Çerez taramasının ve sitenin durumuna göre sağlanan koşullar: kod => bool. */
function legal_states(): array
{
    $s = cerez_scan();
    return [
        'cerez_var' => $s['cookies'], 'cerez_yok' => !$s['cookies'],
        'depolama_var' => (bool) $s['storage_pages'], 'depolama_yok' => !$s['storage_pages'],
        'dis_yok' => !$s['external'], 'dis_var' => $s['external'],
        'gomulu_var' => $s['embeds'],
        'blog_acik' => feature('blog'), 'blog_kapali' => !feature('blog'),
    ];
}

/** Maddelerdeki bir metnin güvenli HTML'i (satır sonları <br> olur; işaretler ve yer tutucular çevrilir). */
function legal_html(string $raw, array $vars): string
{
    return text_rich_html($raw, $vars);
}

/**
 * Belgenin sayfada gösterilecek maddeleri ($doc['maddeler'] biçiminde): koşulu tutmayan fıkralar atılır, hiç fıkrası kalmayan madde görünmez.
 * @param array $vars th() için yerel yer tutucular
 */
function legal_maddeler(string $doc, array $vars = []): array
{
    $states = $doc === 'cerez' ? legal_states() : [];
    $ok = fn(array $x) => ($x['when'] ?? '') === '' || !empty($states[$x['when']]);
    $letters = ['a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ', 'h', 'ı', 'i', 'j', 'k'];
    $out = [];
    foreach (legal_get($doc) as $a) {
        $paras = array_values(array_filter((array) ($a['paras'] ?? []), $ok));
        if (!$paras) {
            continue;
        }
        $m = ['title' => (string) $a['title'], 'paras' => array_map(fn($x) => legal_html((string) $x['t'], $vars), $paras)];
        foreach (array_values((array) ($a['items'] ?? [])) as $n => $x) {
            $m['list'][] = [$letters[$n] ?? (string) ($n + 1), legal_html((string) $x['t'], $vars)];
        }
        $plain = array_values(array_filter((array) ($a['plain'] ?? []), $ok));
        if ($plain) {
            $m['sade'] = legal_html((string) $plain[0]['t'], $vars);
        }
        if (!empty($a['anchor'])) {
            $m['anchor'] = (string) $a['anchor'];
        }
        $out[] = $m;
    }
    return $out;
}
