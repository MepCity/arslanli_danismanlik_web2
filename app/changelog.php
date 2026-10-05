<?php
declare(strict_types=1);

/**
 * Değişiklik günlüğü: sitede kim, ne zaman, neyi değiştirdi.
 * ---------------------------------------------------------------------------
 * İçerik katmanı (content_put, ann_save_all) her kayıtta buraya bir satır yazar. Satır, değişiklikten ÖNCEKİ halin
 * sürüm kimliğini taşır; böylece her değişiklik tek tıkla geri alınabilir (Yönetim > Değişiklik geçmişi, MCP: geri_al).
 *
 *   storage/content/_history/log.jsonl            günlük (son CHANGELOG_KEEP satır)
 *   storage/content/_history/{bölüm}/{sürüm}.json  o değişiklikten önceki hal (app/content.php)
 *
 *   actor()             işlemi yapan: panel, yapay zekâ erişimi (kişi ve uygulama) ya da sistem
 *   changelog_record()  içerik değişikliği (geri alınabilir)
 *   changelog_event()   içerik dışı olay (şifre, erişim anahtarı, başvuru silme…; geri alınamaz)
 *   changelog_entries() günlük, en yeni önce
 *   changelog_detail()  bir değişikliğin alan alan öncesi ve sonrası
 */

const CHANGELOG_KEEP = 1000;

/** Bölümler: anahtar => [ad, paneldeki yeri, geri alınabilir mi] */
function changelog_sections(): array
{
    return [
        'duyurular'  => ['Duyurular', 'duyurular', true],
        'ilanlar'    => ['İş ilanları', 'ilanlar', true],
        'services'   => ['Hizmetler', 'hizmetler', true],
        'posts'      => ['Yazılar', 'blog', true],
        'refs'       => ['Referanslar', 'referanslar', true],
        'texts'      => ['Sayfa metinleri', 'metinler', true],
        'lists'      => ['Kurumsal içerik', 'kurumsal', true],
        'features'   => ['Görünürlük', 'gorunurluk', true],
        'settings'   => ['İletişim ve şirket', 'ayarlar', true],
        'seo'        => ['SEO ve yapay zekâ', 'seo', true],
        'kayitlar'   => ['Form kayıtları', 'kayitlar', false],
        'basvurular' => ['İş başvuruları', 'basvurular', false],
        'bulten'     => ['Bülten', 'bulten', false],
        'erisim'     => ['Yapay zekâ erişimi', 'mcp', false],
        'guvenlik'   => ['Güvenlik ve yedek', 'guvenlik', false],
    ];
}

/**
 * İşlemi yapan. Panel oturumu ve yapay zekâ erişimi kendi adını bildirir; bildirilmezse "Sistem".
 * @return array{type:string, name:string, client:string}
 */
function actor(?array $set = null): array
{
    static $a = ['type' => 'sistem', 'name' => 'Sistem', 'client' => ''];
    if ($set !== null) {
        $a = ['type' => (string) ($set['type'] ?? 'sistem'), 'name' => mb_substr((string) ($set['name'] ?? ''), 0, 80), 'client' => mb_substr((string) ($set['client'] ?? ''), 0, 100)];
    }
    return $a;
}

/** Bu istekteki kayıtlara eklenecek not (örn. "Geri alma"). */
function changelog_note(?string $set = null): string
{
    static $note = '';
    if ($set !== null) {
        $note = mb_substr($set, 0, 200);
    }
    return $note;
}

function changelog_file(): string
{
    return CONTENT_DIR . '/_history/log.jsonl';
}

/** Günlüğe bir satır ekler; son CHANGELOG_KEEP satır tutulur. */
function changelog_write(array $entry): void
{
    $dir = CONTENT_DIR . '/_history';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return;
    }
    $fh = @fopen(changelog_file(), 'c+');
    if (!$fh) {
        return;
    }
    try {
        flock($fh, LOCK_EX);
        fseek($fh, 0, SEEK_END);
        fwrite($fh, json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . "\n");
        clearstatcache(true, changelog_file());
        if (filesize(changelog_file()) > 600000) {
            rewind($fh);
            $lines = [];
            while (($l = fgets($fh)) !== false) {
                $lines[] = $l;
            }
            if (count($lines) > CHANGELOG_KEEP) {
                ftruncate($fh, 0);
                rewind($fh);
                fwrite($fh, implode('', array_slice($lines, -CHANGELOG_KEEP)));
            }
        }
        fflush($fh);
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

/** İçerik değişikliği: $rev, değişiklikten önceki halin sürüm kimliğidir. Günlük hatası kaydı engellemez. */
function changelog_record(string $key, string $rev, $before, $after): void
{
    try {
        $sum = changelog_summary(changelog_diff($key, $before, $after));
        changelog_write(['t' => date('c'), 'key' => $key, 'rev' => $rev, 'who' => actor(),
            'sum' => $sum ?: ['Yeniden kaydedildi (görünür bir değişiklik yok)'], 'note' => changelog_note()]);
    } catch (Throwable $e) {
        error_log('[changelog] ' . $key . ': ' . $e->getMessage());
    }
}

/** İçerik dışı olay (geri alınamaz): şifre değişti, erişim anahtarı oluşturuldu, başvuru silindi… */
function changelog_event(string $key, string $text): void
{
    try {
        changelog_write(['t' => date('c'), 'key' => $key, 'rev' => null, 'who' => actor(), 'sum' => [mb_substr($text, 0, 240)], 'note' => '']);
    } catch (Throwable $e) {
        error_log('[changelog] ' . $key . ': ' . $e->getMessage());
    }
}

/** Günlüğün ham satırları, eskiden yeniye. */
function changelog_log(): array
{
    $f = changelog_file();
    if (!is_file($f)) {
        return [];
    }
    $out = [];
    foreach (@file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $r = json_decode($line, true);
        if (is_array($r) && isset($r['key'], $r['t'])) {
            $out[] = $r;
        }
    }
    return $out;
}

/** Sürüm dosyası (yoksa null) */
function changelog_rev_file(string $key, ?string $rev): ?string
{
    if ($rev === null || !preg_match('/^[a-z_]+$/', $key) || !preg_match('/^\d{8}-\d{6}-[a-f0-9]{4}$/', $rev)) {
        return null;
    }
    $f = CONTENT_DIR . '/_history/' . $key . '/' . $rev . '.json';
    return is_file($f) ? $f : null;
}

/**
 * Günlük, en yeni önce. Günlük tutulmaya başlanmadan önce oluşmuş sürümler de (kim değiştirdiği bilinmeden) listeye girer.
 * Her kayıt: t, key, rev, who, sum, note, can (geri alınabilir mi), later (aynı bölümde sonradan yapılan değişiklik sayısı)
 * @param string $key  '' = tüm bölümler
 */
function changelog_entries(string $key = ''): array
{
    $sections = changelog_sections();
    $log = changelog_log();
    $known = [];
    foreach ($log as $r) {
        if (!empty($r['rev'])) {
            $known[$r['key']][$r['rev']] = true;
        }
    }
    // Günlükte karşılığı olmayan eski sürümler
    foreach ($sections as $k => [$label, $path, $versioned]) {
        if (!$versioned || ($key !== '' && $k !== $key)) {
            continue;
        }
        foreach (content_history($k) as $rev => $dt) {
            if (isset($known[$k][$rev])) {
                continue;
            }
            $log[] = ['t' => $dt ? $dt->format('c') : '', 'key' => $k, 'rev' => $rev, 'who' => ['type' => 'bilinmiyor', 'name' => '', 'client' => ''],
                'sum' => [str_ends_with($rev, '-0000') ? 'Sitenin ilk hali' : 'Günlük tutulmadan önce yapılmış bir değişiklik'], 'note' => ''];
        }
    }
    usort($log, fn($a, $b) => strcmp((string) $a['t'], (string) $b['t']));   // kararlı sıralama: aynı andakiler yazılış sırasında kalır
    $later = [];
    $out = [];
    for ($i = count($log) - 1; $i >= 0; $i--) {
        $r = $log[$i];
        $k = (string) $r['key'];
        if (!isset($sections[$k])) {
            continue;
        }
        $r['later'] = $later[$k] ?? 0;
        if (!empty($r['rev'])) {
            $later[$k] = ($later[$k] ?? 0) + 1;
        }
        $r['can'] = $sections[$k][2] && changelog_rev_file($k, $r['rev'] ?? null) !== null;
        if ($key === '' || $k === $key) {
            $out[] = $r;
        }
    }
    return $out;
}

/** Bölümün şu anki içeriği (sürümlerle aynı biçimde) */
function changelog_current(string $key)
{
    if ($key === 'duyurular') {
        return ann_all();
    }
    if ($key === 'ilanlar') {
        return ilan_all();
    }
    $f = CONTENT_DIR . '/' . $key . '.json';
    $d = is_file($f) ? json_decode((string) file_get_contents($f), true) : null;
    return $d ?? json_decode((string) json_encode(content_default($key)), true);
}

/**
 * Bir değişikliğin ayrıntısı: sürüm (önceki hal) ile ondan sonraki hal arasındaki fark.
 * Sonraki hal, aynı bölümdeki bir sonraki değişikliğin sürümüdür; yoksa şu anki içeriktir.
 * @return array{items: array, entry: ?array, partial: bool}|null  sürüm dosyası yoksa null
 */
function changelog_detail(string $key, string $rev): ?array
{
    $file = changelog_rev_file($key, $rev);
    if ($file === null) {
        return null;
    }
    $before = json_decode((string) file_get_contents($file), true);
    $entries = array_reverse(changelog_entries($key));   // eskiden yeniye
    $entry = null;
    $next = null;
    foreach ($entries as $i => $r) {
        if (($r['rev'] ?? null) === $rev) {
            $entry = $r;
            for ($j = $i + 1; $j < count($entries); $j++) {
                if (!empty($entries[$j]['rev'])) {
                    $next = $entries[$j];
                    break;
                }
            }
            break;
        }
    }
    $partial = false;
    if ($next !== null) {
        $nf = changelog_rev_file($key, $next['rev']);
        if ($nf === null) {
            // Aradaki sürüm artık saklanmıyor: fark, birkaç değişikliğin toplamı olurdu
            return ['items' => [], 'entry' => $entry, 'partial' => true];
        }
        $after = json_decode((string) file_get_contents($nf), true);
    } else {
        $after = changelog_current($key);
    }
    return ['items' => changelog_diff($key, $before, $after), 'entry' => $entry, 'partial' => $partial];
}

/* =========================================================================
   Fark: iki hal arasında ne değişti
   Her öğe: ['title' => 'Duyuru güncellendi: "…"', 'rows' => [[alan, önce, sonra], …]]
   ========================================================================= */

function cl_bool($v): string
{
    return !empty($v) ? 'Evet' : 'Hayır';
}

/** Herhangi bir değeri okunur metne çevirir (liste, çift, nesne). */
function cl_render($v): string
{
    if ($v === null) {
        return '';
    }
    if (is_bool($v)) {
        return cl_bool($v);
    }
    if (is_scalar($v)) {
        return (string) $v;
    }
    if (!is_array($v)) {
        return '';
    }
    $isList = $v === [] || array_keys($v) === range(0, count($v) - 1);
    $lines = [];
    foreach ($v as $k => $x) {
        if (is_array($x)) {
            $flat = [];
            array_walk_recursive($x, function ($y) use (&$flat) { $flat[] = is_bool($y) ? cl_bool($y) : (string) $y; });
            $xl = $x === [] || array_keys($x) === range(0, count($x) - 1);
            // [başlık, açıklama] çifti "başlık: açıklama"; diğerleri " · " ile
            $txt = $isList && $xl && count($x) === 2 && !is_array($x[0]) && !is_array($x[1]) ? $flat[0] . ': ' . $flat[1] : implode($xl && !$isList ? ', ' : ' · ', $flat);
        } else {
            $txt = is_bool($x) ? cl_bool($x) : (string) $x;
        }
        $lines[] = $isList ? $txt : $k . ': ' . $txt;
    }
    return implode("\n", $lines);
}

function cl_plain(string $html): string
{
    $t = html_entity_decode(strip_tags((string) preg_replace('#</(p|h[1-6]|li|blockquote)>|<br\s*/?>#i', "\n", $html)), ENT_QUOTES, 'UTF-8');
    return trim((string) preg_replace('/[ \t]*\n\s*/', "\n", $t));
}

/** Alan tanımlarına göre iki kayıt arasındaki farklı satırlar. $defs: [etiket => fn(kayıt): metin] */
function cl_rows(array $defs, array $b, array $a): array
{
    $rows = [];
    foreach ($defs as $label => $fn) {
        $x = (string) $fn($b);
        $y = (string) $fn($a);
        if ($x !== $y) {
            $rows[] = [$label, $x, $y];
        }
    }
    return $rows;
}

/**
 * Anahtarlı iki küme arasındaki fark (duyurular, hizmetler, yazılar).
 * $words: [eklendi, silindi, güncellendi]; $name: fn(kayıt): ad
 */
function cl_map_diff(array $b, array $a, array $defs, callable $name, array $words): array
{
    $items = [];
    foreach ($a as $id => $x) {
        if (!isset($b[$id])) {
            $items[] = ['title' => $words[0] . ': "' . $name($x) . '"', 'rows' => cl_rows($defs, [], (array) $x)];
        }
    }
    foreach ($b as $id => $x) {
        if (!isset($a[$id])) {
            $items[] = ['title' => $words[1] . ': "' . $name($x) . '"', 'rows' => cl_rows($defs, (array) $x, [])];
        } else {
            $rows = cl_rows($defs, (array) $x, (array) $a[$id]);
            if ($rows) {
                $items[] = ['title' => $words[2] . ': "' . $name($a[$id]) . '"', 'rows' => $rows, 'fields' => true];
            }
        }
    }
    return $items;
}

/** Sayfa metninin bulunduğu yer: "Ana sayfa › Başlık" */
function cl_text_where(string $key): string
{
    foreach (texts_registry() as $g) {
        foreach ((array) ($g['sections'] ?? []) as $items) {
            if (isset($items[$key])) {
                return (string) ($g['label'] ?? '') . ' › ' . (string) $items[$key][0];
            }
        }
    }
    return $key;
}

/** @return array<int, array{title:string, rows:array}> */
function changelog_diff(string $key, $before, $after): array
{
    $b = is_array($before) ? $before : [];
    $a = is_array($after) ? $after : [];
    $s = fn(string $k) => fn($x) => (string) ($x[$k] ?? '');

    switch ($key) {
        case 'duyurular':
            $types = ann_types();
            $byId = fn(array $l) => array_column(array_filter($l, fn($x) => is_array($x) && isset($x['id'])), null, 'id');
            $ev = fn($x) => implode("\n", array_map(fn($e) => ($e['date'] ?? '') . ' · ' . ($types[$e['type'] ?? ''] ?? 'Bilgilendirme') . (($e['note'] ?? '') !== '' ? ' (' . $e['note'] . ')' : ''), (array) ($x['events'] ?? [])));
            $flag = fn(string $k) => fn($x) => $x ? cl_bool($x[$k] ?? false) : '';
            return cl_map_diff($byId($b), $byId($a), [
                'Başlık' => $s('title'), 'Kurum' => $s('kurum'), 'Özet' => $s('summary'), 'Resmî bağlantı' => $s('link'), 'Tarihler' => $ev,
                'Yayında' => $flag('published'), 'Açılışta öne çıkar' => $flag('featured'), 'Öne çıkarma bitişi' => $s('featured_until'), '"Örnek" etiketi' => $flag('sample'),
            ], fn($x) => (string) ($x['title'] ?? ''), ['Duyuru eklendi', 'Duyuru silindi', 'Duyuru güncellendi']);

        case 'ilanlar':
            $byId = fn(array $l) => array_column(array_filter($l, fn($x) => is_array($x) && isset($x['id'])), null, 'id');
            $st = ilan_statuses();
            $lines = fn(string $k) => fn($x) => $x ? cl_render($x[$k] ?? []) : '';
            return cl_map_diff($byId($b), $byId($a), [
                'Pozisyon adı' => $s('title'), 'Adres' => $s('slug'), 'Alan' => $s('area'), 'Şehir' => $s('city'), 'Çalışma türü' => $s('type'), 'Deneyim' => $s('experience'),
                'Özet' => $s('summary'), 'Görevler' => $lines('duties'), 'Aranan nitelikler' => $lines('requirements'), 'Tercih sebebi olacaklar' => $lines('extras'),
                'Son başvuru günü' => $s('deadline'), 'Durum' => fn($x) => $x ? ($st[$x['status'] ?? ''] ?? (string) ($x['status'] ?? '')) : '',
            ], fn($x) => (string) ($x['title'] ?? ''), ['İlan eklendi', 'İlan silindi', 'İlan güncellendi']);

        case 'services':
            $items = cl_map_diff($b, $a, [
                'Başlık' => $s('title'), 'Menü adı' => $s('nav'), 'Kısa açıklama' => $s('short'), 'Giriş' => $s('lead'), 'Kimler için' => $s('for'),
                'Programlar' => fn($x) => cl_render($x['programs'] ?? []), 'Kapsam' => fn($x) => cl_render($x['scope'] ?? []),
                'Sık sorulan sorular' => fn($x) => cl_render($x['faq'] ?? []), 'Hareketli çizim' => $s('glyph'),
            ], fn($x) => (string) ($x['title'] ?? ''), ['Hizmet eklendi', 'Hizmet silindi', 'Hizmet güncellendi']);
            $ob = array_values(array_intersect(array_keys($b), array_keys($a)));
            $oa = array_values(array_intersect(array_keys($a), array_keys($b)));
            if ($ob !== $oa) {
                $t = fn(array $keys, array $src) => implode("\n", array_map(fn($k) => (string) ($src[$k]['title'] ?? $k), $keys));
                $items[] = ['title' => 'Hizmet sırası değişti', 'rows' => [['Sıra', $t($ob, $b), $t($oa, $a)]]];
            }
            return $items;

        case 'posts':
            return cl_map_diff($b, $a, [
                'Başlık' => $s('title'), 'Kategori' => $s('category'), 'Tarih' => $s('date'), 'Özet' => $s('excerpt'),
                'Metin' => fn($x) => cl_plain((string) ($x['body'] ?? '')), 'Görsel' => $s('image'),
                'Durum' => fn($x) => $x ? (!empty($x['draft']) ? 'Taslak' : 'Yayında') : '',
            ], fn($x) => (string) ($x['title'] ?? ''), ['Yazı eklendi', 'Yazı silindi', 'Yazı güncellendi']);

        case 'refs':
            $byLogo = fn(array $l) => array_column(array_filter($l, fn($x) => is_array($x) && !empty($x['logo'])), 'name', 'logo');
            $mb = $byLogo($b);
            $ma = $byLogo($a);
            $added = array_diff_key($ma, $mb);
            $gone = array_diff_key($mb, $ma);
            $items = [];
            foreach ($added as $logo => $name) {
                $from = array_search($name, $gone, true);
                if ($from !== false) {       // aynı kurum, yeni logo
                    unset($gone[$from]);
                    $items[] = ['title' => 'Referans logosu değişti: "' . $name . '"', 'rows' => [['Logo', (string) $from, (string) $logo]]];
                } else {
                    $items[] = ['title' => 'Referans eklendi: "' . $name . '"', 'rows' => [['Kurum adı', '', (string) $name], ['Logo', '', (string) $logo]]];
                }
            }
            foreach ($gone as $logo => $name) {
                $items[] = ['title' => 'Referans silindi: "' . $name . '"', 'rows' => [['Kurum adı', (string) $name, ''], ['Logo', (string) $logo, '']]];
            }
            foreach (array_intersect_key($ma, $mb) as $logo => $name) {
                if ($mb[$logo] !== $name) {
                    $items[] = ['title' => 'Referans adı değişti: "' . $name . '"', 'rows' => [['Kurum adı', (string) $mb[$logo], (string) $name]]];
                }
            }
            $ob = array_values(array_intersect(array_keys($mb), array_keys($ma)));
            $oa = array_values(array_intersect(array_keys($ma), array_keys($mb)));
            if ($ob !== $oa) {
                $items[] = ['title' => 'Referans sırası değişti', 'rows' => [['Sıra', implode("\n", array_map(fn($k) => (string) $mb[$k], $ob)), implode("\n", array_map(fn($k) => (string) $ma[$k], $oa))]]];
            }
            return $items;

        case 'texts':
            $flat = texts_flat();
            $items = [];
            foreach (array_unique(array_merge(array_keys($b), array_keys($a))) as $k) {
                $def = (string) ($flat[$k][1] ?? '');
                $x = isset($b[$k]) && trim((string) $b[$k]) !== '' ? (string) $b[$k] : $def;
                $y = isset($a[$k]) && trim((string) $a[$k]) !== '' ? (string) $a[$k] : $def;
                if ($x !== $y) {
                    $html = ($flat[$k][2] ?? 'line') === 'html';
                    $items[] = ['title' => 'Sayfa metni değişti: ' . cl_text_where((string) $k), 'rows' => [['Metin', $html ? cl_plain($x) : $x, $html ? cl_plain($y) : $y]]];
                }
            }
            return $items;

        case 'lists':
            $labels = ['process' => 'Çalışma süreci', 'principles' => 'İlkeler', 'mission_goals' => 'Misyon hedefleri', 'vision_items' => 'Vizyon maddeleri',
                'stations' => 'Ana sayfa dalga programları', 'noise' => 'Kurumlar ve programları', 'banks' => 'Banka hesapları', 'deneyim' => 'Kariyer formu deneyim seçenekleri',
                'sektorler' => 'Bülten formu sektör seçenekleri',
                'ref_count' => 'Gösterilen referans sayısı'];
            $def = (array) require APP . '/data/site.php';
            $items = [];
            foreach ($labels as $k => $label) {
                $x = cl_render($b[$k] ?? ($def[$k] ?? null));
                $y = cl_render($a[$k] ?? ($def[$k] ?? null));
                if ($x !== $y) {
                    $items[] = ['title' => 'Liste değişti: ' . $label, 'rows' => [[$label, $x, $y]]];
                }
            }
            return $items;

        case 'features':
            $names = ['blog' => 'Yazılar', 'duyurular' => 'Duyurular ve çağrı takvimi', 'referanslar' => 'Referanslar', 'kariyer' => 'Kariyer', 'bulten' => 'Bülten (Haberdar ol)', 'whatsapp' => 'WhatsApp düğmesi'];
            $items = [];
            foreach (features_defaults() as $k => $d) {
                $x = array_key_exists($k, $b) ? (bool) $b[$k] : $d;
                $y = array_key_exists($k, $a) ? (bool) $a[$k] : $d;
                if ($x !== $y) {
                    $items[] = ['title' => ($names[$k] ?? $k) . ($y ? ' sitede açıldı' : ' sitede kapatıldı'), 'rows' => [[$names[$k] ?? $k, $x ? 'Açık' : 'Kapalı', $y ? 'Açık' : 'Kapalı']]];
                }
            }
            return $items;

        case 'settings':
            $base = (array) require APP . '/config.php';
            $cb = settings_apply($base, $b);
            $ca = settings_apply($base, $a);
            $get = fn(string $path) => function ($c) use ($path) {
                foreach (explode('.', $path) as $p) {
                    if (!is_array($c) || !array_key_exists($p, $c)) return '';
                    $c = $c[$p];
                }
                return is_bool($c) ? cl_bool($c) : (is_scalar($c) ? (string) $c : '');
            };
            $smtp = fn($c) => empty($c['mail']['smtp']['host']) ? 'Kapalı (sunucunun e-posta fonksiyonu)'
                : $c['mail']['smtp']['host'] . ':' . ($c['mail']['smtp']['port'] ?? '') . ' · ' . ($c['mail']['smtp']['secure'] ?? '') . ' · ' . ($c['mail']['smtp']['user'] ?? '');
            $rows = cl_rows([
                'Şirket adı' => $get('name'), 'Telefon' => $get('phone'), 'WhatsApp' => $get('whatsapp'), 'E-posta' => $get('email'), 'Adres' => $get('address'),
                'Kısa adres' => $get('address_short'), 'Harita bağlantısı' => $get('maps_url'), 'Yetkili' => $get('company.authorized'),
                'Vergi dairesi' => $get('company.tax_office'), 'Vergi numarası' => $get('company.tax_number'),
                'Instagram' => $get('social.Instagram'), 'LinkedIn' => $get('social.LinkedIn'), 'Facebook' => $get('social.Facebook'), 'X' => $get('social.X'),
                'Bildirimlerin gittiği adres' => $get('mail.to'), 'Gönderen adresi' => $get('mail.from'), 'Gönderen adı' => $get('mail.from_name'),
                'Form kayıtlarını sakla' => $get('store_submissions'), 'SMTP' => $smtp,
            ], $cb, $ca);
            // Şifre hiçbir zaman gösterilmez; yalnızca değiştiği söylenir
            $pb = (string) ($cb['mail']['smtp']['pass'] ?? '');
            $pa = (string) ($ca['mail']['smtp']['pass'] ?? '');
            if ($pb !== $pa) {
                $rows[] = ['SMTP şifresi', $pb === '' ? '(yok)' : '(gizli)', $pa === '' ? '(kaldırıldı)' : ($pb === '' ? '(ayarlandı)' : '(değiştirildi)')];
            }
            return $rows ? [['title' => 'İletişim ve şirket ayarları değişti', 'rows' => $rows, 'fields' => true]] : [];

        case 'seo':
            $d = seo_defaults();
            $norm = function (array $x) use ($d): array {
                $o = array_replace($d, array_intersect_key($x, $d));
                $o['verify'] = array_replace($d['verify'], is_array($o['verify'] ?? null) ? $o['verify'] : []);
                return $o;
            };
            $onoff = fn(string $k) => fn($x) => !empty($x[$k]) ? 'Açık' : 'Kapalı';
            $rows = cl_rows([
                'Google doğrulama kodu' => fn($x) => (string) $x['verify']['google'], 'Bing doğrulama kodu' => fn($x) => (string) $x['verify']['bing'],
                'Yandex doğrulama kodu' => fn($x) => (string) $x['verify']['yandex'], 'IndexNow bildirimi' => $onoff('indexnow'),
                'IndexNow anahtarı' => fn($x) => (string) $x['indexnow_key'] !== '' ? mb_substr((string) $x['indexnow_key'], 0, 6) . '…' : '',
                'Yapay zekâ aramalarında görünme' => $onoff('ai_search'), 'Model eğitimine izin' => $onoff('ai_training'),
            ], $norm($b), $norm($a));
            return $rows ? [['title' => 'SEO ve yapay zekâ ayarları değişti', 'rows' => $rows, 'fields' => true]] : [];
    }
    return $b === $a ? [] : [['title' => 'İçerik değişti', 'rows' => []]];
}

/** Farktan kısa özet satırları (günlükte saklanır). */
function changelog_summary(array $items): array
{
    $out = [];
    foreach (array_slice($items, 0, 8) as $it) {
        $t = (string) $it['title'];
        if (!empty($it['fields']) && !empty($it['rows'])) {
            $t .= ' (' . implode(', ', array_map(fn($r) => mb_strtolower((string) $r[0]), array_slice($it['rows'], 0, 6))) . (count($it['rows']) > 6 ? '…' : '') . ')';
        }
        $out[] = mb_strlen($t) > 200 ? mb_substr($t, 0, 200) . '…' : $t;
    }
    if (count($items) > 8) {
        $out[] = 've ' . (count($items) - 8) . ' değişiklik daha';
    }
    return $out;
}

/**
 * Bir bölümü, verilen sürüme (o değişiklikten önceki hale) döndürür. Panel ve yapay zekâ erişimi ortak kullanır.
 * $keepMail: iletişim ayarları geri alınırken e-posta gönderim ayarları ve form kayıtlarının saklanması tercihi şu anki haliyle korunsun mu
 */
function changelog_restore(string $key, string $rev, bool $keepMail = false): bool
{
    $file = changelog_rev_file($key, $rev);
    if ($file === null || empty(changelog_sections()[$key][2])) {
        return false;
    }
    $data = json_decode((string) file_get_contents($file), true);
    if (!is_array($data)) {
        return false;
    }
    if (changelog_note() === '') {
        changelog_note('Geri alma');
    }
    if ($key === 'duyurular') {
        return ann_save_all($data);     // duyurular kendi dosyasında durur
    }
    if ($key === 'ilanlar') {
        return ilan_restore_all($data);    // iş ilanları da kendi dosyasında durur; temizlenir ve yeniden açılan ilanlar bildirilir
    }
    if ($key === 'settings' && $keepMail) {
        $cur = content_get('settings', []);
        foreach (['mail', 'store_submissions'] as $k) {
            unset($data[$k]);
            if (is_array($cur) && array_key_exists($k, $cur)) {
                $data[$k] = $cur[$k];
            }
        }
    }
    return content_put($key, $data);
}
