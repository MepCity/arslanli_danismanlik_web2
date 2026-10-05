<?php
declare(strict_types=1);

/**
 * Duyurular: storage/duyurular.json dosyasında saklanır, /yonetim panelinden yönetilir.
 * Dosya yoksa (panelden hiç kaydedilmemişse) app/data/duyurular.php içindeki doğrulanmış yedi duyuru kullanılır.
 * Sitenin duyuru sayfası, açılış penceresi ve takvim dosyası yalnızca bu dosyadaki işlevleri kullanır.
 *
 * Bir duyuru:
 *  id, title, kurum, summary, link, published (bool; yazılmamışsa yayında), sample (bool: "Örnek" etiketi),
 *  featured (bool: sitenin açılışında öne çıkar), featured_until (YYYY-MM-DD; boşsa son başvuru günü),
 *  events: [ { date: YYYY-MM-DD, type: baslangic|son|sonuc|diger, note } ],
 *  created, updated
 *
 * Her kayıttan önce dosyanın önceki hali storage/content/_history/duyurular/ altına alınır (Geçmiş'ten geri alınabilir).
 * İlk kayıtta "önceki hal" varsayılan yedi duyurudur: sitenin ilk haline her zaman dönülebilir.
 */

const ANN_FILE = ROOT . '/storage/duyurular.json';

/** Tarih türleri ve adları; adlar panelde Sayfa metinleri > Duyurular > "Tarih türlerinin adları" bölümündedir. */
function ann_types(): array
{
    return [
        'baslangic' => t('duyurular.tur.baslangic'),
        'son'       => t('duyurular.tur.son'),
        'sonuc'     => t('duyurular.tur.sonuc'),
        'diger'     => t('duyurular.tur.diger'),
    ];
}

/** Bir tarih türünün adı; tür bilinmiyorsa "Bilgilendirme" adı. */
function ann_type_name(string $type): string
{
    return ann_types()[$type] ?? t('duyurular.kagit.tur_yok');
}

/** Dosya yokken kullanılan varsayılan duyurular (app/data/duyurular.php); hepsi yayındadır. */
function ann_seed(): array
{
    static $seed = null;
    if ($seed === null) {
        $seed = [];
        foreach ((array) require APP . '/data/duyurular.php' as $a) {
            if (is_array($a)) {
                $seed[] = $a + ['published' => true];
            }
        }
    }
    return $seed;
}

/** Tüm duyurular (taslaklar dahil), kayıt sırasıyla. storage/duyurular.json yoksa varsayılanlar. */
function ann_all(): array
{
    if (!is_file(ANN_FILE)) {
        return ann_seed();
    }
    $data = json_decode((string) file_get_contents(ANN_FILE), true);
    return is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
}

/** Dosyaya güvenli yazım: önce geçici dosya, sonra yer değiştirme. */
function ann_save_all(array $items): bool
{
    $items = array_values($items);
    $json  = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    // Dosya yokken önceki hal varsayılan duyurulardır (ilk kayıtta sitenin özgün hali olarak saklanır)
    $fresh = !is_file(ANN_FILE);
    $prev  = $fresh ? json_encode(ann_seed(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : (string) file_get_contents(ANN_FILE);
    if ($prev === $json) {
        return true;   // içerik aynı (ya da varsayılanla aynı): yeni sürüm ve günlük kaydı oluşmaz, dosya açılmaz
    }
    // Geçmiş: önceki hal saklanır (ilk kayıtta "-0000" ekiyle, hiç silinmeyen özgün hal)
    $hdir = CONTENT_DIR . '/_history/duyurular';
    if (!is_dir($hdir)) {
        @mkdir($hdir, 0755, true);
    }
    $hasOrig = (bool) glob($hdir . '/*-0000.json');
    $rev = $hasOrig ? content_rev_id($hdir) : date('Ymd-His') . '-0000';
    @file_put_contents($hdir . '/' . $rev . '.json', $prev, LOCK_EX);
    $old = array_filter(glob($hdir . '/*.json') ?: [], fn($f) => !str_ends_with($f, '-0000.json'));
    rsort($old);
    foreach (array_slice($old, CONTENT_HISTORY_KEEP) as $f) {
        @unlink($f);
    }
    $tmp = ANN_FILE . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, ANN_FILE)) {
        @unlink($tmp);
        return false;
    }
    if (function_exists('changelog_record')) {
        changelog_record('duyurular', $rev, json_decode($prev, true) ?: [], $items);
    }
    if (function_exists('seo_changed')) {
        seo_changed('duyurular');   // Aşama 3A: arama motoru bildirimi (app/seo.php); o zamana dek yok
    }
    return true;
}

/** Bir duyuru için olayları tarihe göre sıralı verir (ziyaretçi tarafı bunu varsayar). */
function ann_sorted(array $a): array
{
    $a['events'] = array_values((array) ($a['events'] ?? []));
    usort($a['events'], fn($x, $y) => strcmp((string) $x['date'], (string) $y['date']));
    $a += ['published' => true, 'featured' => false, 'updated' => ''];
    return $a;
}

function ann_find(string $id): ?array
{
    foreach (ann_all() as $a) {
        if (($a['id'] ?? '') === $id) {
            return $a;
        }
    }
    return null;
}

/** En yakın gelecek tarihi (yoksa en son geçmiş tarihi) sıralama anahtarı olarak döndürür. */
function ann_sort_key(array $a): string
{
    $today = date('Y-m-d');
    $dates = array_map(fn($e) => $e['date'], $a['events'] ?? []);
    sort($dates);
    foreach ($dates as $d) {
        if ($d >= $today) {
            return '0' . $d;                       // yaklaşanlar önce, en yakını en üstte
        }
    }
    $last = end($dates) ?: '0000-00-00';
    return '1' . (string) (99999999 - (int) str_replace('-', '', $last)); // geçmişler sonra, en yenisi önce
}

/** Yayındaki duyurular, ziyaretçi için sıralı (olayları tarih sıralı). Duyurular bölümü kapalıysa boş. */
function ann_published(): array
{
    if (function_exists('feature') && !feature('duyurular')) {
        return [];
    }
    $items = array_map('ann_sorted', array_filter(ann_all(), fn($a) => !empty($a['published'])));
    usort($items, fn($x, $y) => strcmp(ann_sort_key($x), ann_sort_key($y)));
    return array_values($items);
}

/** Takvim için düz olay listesi. */
function ann_events(array $items): array
{
    $types = ann_types();
    $out = [];
    foreach ($items as $a) {
        foreach ($a['events'] ?? [] as $ev) {
            $out[] = [
                'id'    => $a['id'],
                'date'  => $ev['date'],
                'type'  => $ev['type'],
                'label' => $types[$ev['type']] ?? ann_type_name('diger'),
                'note'  => $ev['note'] ?? '',
                'title' => $a['title'],
                'kurum' => $a['kurum'] ?? '',
            ];
        }
    }
    usort($out, fn($x, $y) => strcmp($x['date'], $y['date']));
    return $out;
}

function ann_new_id(): string
{
    return bin2hex(random_bytes(5));
}

/** Öne çıkarmanın bitiş günü: elle verilen tarih, yoksa son başvuru günü, o da yoksa en son tarih. */
function ann_featured_until(array $a): string
{
    $u = (string) ($a['featured_until'] ?? '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $u)) return $u;
    $son = array_column(array_filter($a['events'] ?? [], fn($e) => ($e['type'] ?? '') === 'son'), 'date');
    if ($son) return max($son);
    $all = array_column($a['events'] ?? [], 'date');
    return $all ? max($all) : '';
}

/** Sitenin açılışında öne çıkan duyuru (yayında, öne çıkarılmış, süresi dolmamış); birden fazlaysa en son güncellenen. */
function ann_featured(): ?array
{
    if (function_exists('feature') && !feature('duyurular')) {
        return null;
    }
    $today = date('Y-m-d');
    $list = array_map('ann_sorted', array_filter(ann_all(), fn($a) => !empty($a['published']) && !empty($a['featured']) && ann_featured_until($a) >= $today));
    usort($list, fn($x, $y) => strcmp((string) ($y['updated'] ?? ''), (string) ($x['updated'] ?? '')));
    return $list[0] ?? null;
}

/**
 * Gelen veriyi doğrular ve kayda hazır duyuruyu üretir (panel ve MCP ortak kullanır).
 * $in: title, kurum, summary, link, published, sample, featured, featured_until, events [[date,type,note]]
 * @return array{0: array, 1: string[]}  [duyuru, hatalar]
 */
function ann_validate(array $in, ?array $old = null): array
{
    $types  = ann_types();
    $errors = [];
    $str = function ($k, int $max) use ($in): string {
        $v = $in[$k] ?? '';
        return is_scalar($v) ? mb_substr(trim(str_replace(["\r\n", "\r"], "\n", (string) $v)), 0, $max) : '';
    };
    $events = [];
    foreach ((array) ($in['events'] ?? []) as $ev) {
        if (!is_array($ev)) continue;
        $date = trim((string) ($ev['date'] ?? ''));
        if ($date === '') continue;
        $dt = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dt || $dt->format('Y-m-d') !== $date) { $errors[] = 'Geçersiz tarih: ' . $date . ' (YYYY-AA-GG biçiminde olmalı).'; continue; }
        $type = (string) ($ev['type'] ?? 'diger');
        $events[] = ['date' => $date, 'type' => isset($types[$type]) ? $type : 'diger', 'note' => mb_substr(trim((string) ($ev['note'] ?? '')), 0, 160)];
    }
    usort($events, fn($x, $y) => strcmp($x['date'], $y['date']));
    $until = $str('featured_until', 10);
    if ($until !== '' && (!($d = DateTime::createFromFormat('Y-m-d', $until)) || $d->format('Y-m-d') !== $until)) {
        $errors[] = 'Öne çıkarma bitiş tarihi geçersiz.';
        $until = '';
    }
    $a = [
        'id'             => $old['id'] ?? ann_new_id(),
        'title'          => $str('title', 200),
        'kurum'          => $str('kurum', 80),
        'summary'        => $str('summary', 1200),
        'link'           => $str('link', 300),
        'published'      => !empty($in['published']),
        'sample'         => !empty($in['sample']),
        'featured'       => !empty($in['featured']),
        'featured_until' => $until,
        'events'         => $events,
        'created'        => $old['created'] ?? date('c'),
        'updated'        => date('c'),
    ];
    if ($a['title'] === '') $errors[] = 'Başlık zorunludur.';
    if ($a['link'] !== '' && (!filter_var($a['link'], FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $a['link']))) $errors[] = 'Bağlantı http:// ya da https:// ile başlamalı.';
    if ($a['featured'] && ann_featured_until($a) === '') $errors[] = 'Öne çıkarmak için en az bir tarih ya da öne çıkarma bitiş tarihi girin.';
    return [$a, $errors];
}

/** Duyuruyu ekler ya da günceller; öne çıkarılırsa diğerlerinin öne çıkarması kaldırılır. */
function ann_upsert(array $a): bool
{
    $items = ann_all();
    $found = false;
    foreach ($items as $i => $x) {
        if (($x['id'] ?? '') === $a['id']) { $items[$i] = $a; $found = true; }
        elseif (!empty($a['featured']) && !empty($x['featured'])) { $items[$i]['featured'] = false; }
    }
    if (!$found) $items[] = $a;
    return ann_save_all($items);
}

/* ---------- Sayfa ve açılış penceresi için durum yardımcıları (ziyaretçi tarafı; olaylar tarih sıralı) ---------- */

/** Hiçbir tarihi kalmamış çağrı: süresi dolmuştur. */
function ann_is_past(array $a): bool
{
    return !$a['events'] || end($a['events'])['date'] < date('Y-m-d');
}

/** Bugünden verilen güne kaç gün var (geçmişse negatif). */
function ann_days_left(string $ymd): int
{
    return (int) round((strtotime($ymd) - strtotime(date('Y-m-d'))) / 86400);
}

/** Tarihe kalan süre ("Bugün", "Yarın", "5 gün kaldı", geçmişse "Geçti"); sözcükler Sayfa metinleri > Duyurular > "Kalan süre" bölümündedir. */
function ann_left_text(string $ymd): string
{
    $n = ann_days_left($ymd);
    return $n < 0 ? t('duyurular.kalan.gecti') : ($n === 0 ? t('duyurular.kalan.bugun') : ($n === 1 ? t('duyurular.kalan.yarin') : t('duyurular.kalan.gun', ['n' => $n])));
}

/** "12 Ekim" */
function ann_short_date(string $ymd): string
{
    $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $t = strtotime($ymd);
    return date('j', $t) . ' ' . $months[(int) date('n', $t) - 1];
}

/** Geri sayım ve damga için hedef olay: son başvuru günü; geçtiyse ya da yoksa bugünden sonraki ilk tarih. */
function ann_target(array $a): ?array
{
    $today = date('Y-m-d');
    foreach ($a['events'] as $ev) {
        if ($ev['type'] === 'son' && $ev['date'] >= $today) return $ev;
    }
    foreach ($a['events'] as $ev) {
        if ($ev['date'] >= $today) return $ev;
    }
    return null;
}

function ann_url(array $a): string
{
    return url('duyurular') . '#duyuru-' . $a['id'];
}

/* ---------- iCalendar (RFC 5545) ---------- */

/** TEXT değeri kaçışı */
function ann_ics_text(string $s): string
{
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\\,', '\\n'], $s);
    return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
}

/** 75 sekizli satır katlama; UTF-8 karakterini ortasından bölmez. */
function ann_ics_fold(string $line): string
{
    if (strlen($line) <= 75) return $line;
    $out = '';
    $cur = '';
    foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
        if (strlen($cur) + strlen($ch) > 75) {
            $out .= $cur . "\r\n";
            $cur = ' ';
        }
        $cur .= $ch;
    }
    return $out . $cur;
}

function ann_ics_calendar(array $anns, string $name): string
{
    $types = ann_types();
    $host  = (string) parse_url((string) cfg('url'), PHP_URL_HOST) ?: 'localhost';
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//' . ann_ics_text((string) cfg('name')) . '//Duyurular//TR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . ann_ics_text($name),
        'X-WR-TIMEZONE:Europe/Istanbul',
        'REFRESH-INTERVAL;VALUE=DURATION:P1D',
        'X-PUBLISHED-TTL:P1D',
    ];
    foreach ($anns as $a) {
        $stamp = gmdate('Ymd\THis\Z', (int) strtotime((string) $a['updated']));
        foreach ($a['events'] as $ev) {
            $label = $types[$ev['type']] ?? ann_type_name('diger');
            $desc  = array_filter([$a['summary'], $ev['note'], t('duyurular.takvim.resmi') . ': ' . $a['link'], t('duyurular.takvim.duyuru') . ': ' . absolute_url('duyurular') . '#duyuru-' . $a['id']]);
            array_push($lines,
                'BEGIN:VEVENT',
                'UID:' . preg_replace('/[^A-Za-z0-9._-]/', '', $a['id'] . '-' . $ev['date'] . '-' . $ev['type']) . '@' . $host,
                'DTSTAMP:' . $stamp,
                'LAST-MODIFIED:' . $stamp,
                'DTSTART;VALUE=DATE:' . str_replace('-', '', $ev['date']),
                'DTEND;VALUE=DATE:' . (new DateTimeImmutable($ev['date']))->modify('+1 day')->format('Ymd'),
                'SUMMARY:' . ann_ics_text($label . ': ' . $a['title']),
                'DESCRIPTION:' . ann_ics_text(implode("\n", $desc)),
                'URL:' . absolute_url('duyurular') . '#duyuru-' . $a['id'],
                'CATEGORIES:' . ann_ics_text((string) $a['kurum']),
                'TRANSP:TRANSPARENT',
                'STATUS:CONFIRMED',
                'END:VEVENT'
            );
        }
    }
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", array_map('ann_ics_fold', $lines)) . "\r\n";
}

/** /duyurular.ics (hepsi) ya da /duyurular/{id}.ics (tek duyuru): yalnızca yayındaki duyurular */
function ann_serve_ics(?string $id): void
{
    $pub = ann_published();
    if ($id !== null) {
        $a = null;
        foreach ($pub as $x) {
            if ($x['id'] === $id) { $a = $x; break; }
        }
        if (!$a) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo t('duyurular.takvim.yok') . "\n";
            exit;
        }
        $body = ann_ics_calendar([$a], $a['title']);
        $file = 'duyuru-' . $id . '.ics';
    } else {
        $body = ann_ics_calendar($pub, t('duyurular.takvim.ad'));
        $file = 'duyurular.ics';
    }
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $file . '"');
    echo $body;
    exit;
}
