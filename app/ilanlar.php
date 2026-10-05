<?php
declare(strict_types=1);

/**
 * İş ilanları: storage/ilanlar.json dosyasında saklanır, /yonetim panelinden yönetilir.
 * Genel başvuru formu (aday havuzu) olduğu gibi durur; ilan, belirli bir pozisyon için ayrıca açılır ve /kariyer/{adres} sayfasında yayınlanır.
 *
 * Bir ilan:
 *  id, slug (başlıktan, benzersiz; sonradan değişmez), title (pozisyon adı), area (alan / bölüm), city (şehir ya da "Uzaktan"),
 *  type (çalışma türü), experience (isteğe bağlı, deneyim listesinden), summary,
 *  duties, requirements, extras (her biri madde listesi), deadline (YYYY-AA-GG, isteğe bağlı),
 *  status (taslak | yayinda | kapali), published (ilk yayına çıkış anı; hiç yayınlanmadıysa boş), created, updated
 *
 * AÇIK ilan: durumu "yayinda" olan ve son başvuru günü boş ya da bugün/sonrası olan ilan (son gün dahildir).
 * Durumu "yayinda" olup son günü geçmiş ilan "süresi doldu" sayılır ve kapalı gibi davranır.
 *
 * Her kayıttan önce dosyanın önceki hali storage/content/_history/ilanlar/ altına alınır (Geçmiş'ten geri alınabilir).
 */

const ILAN_FILE = ROOT . '/storage/ilanlar.json';

function ilan_types(): array
{
    return ['Tam zamanlı', 'Yarı zamanlı', 'Staj', 'Proje bazlı'];
}

function ilan_statuses(): array
{
    return [
        'taslak'  => 'Taslak',
        'yayinda' => 'Yayında',
        'kapali'  => 'Kapalı',
    ];
}

/** İlanın şehir seçenekleri: sitedeki şehir listesi ve "Uzaktan". */
function ilan_cities(): array
{
    return array_merge(array_values((array) site('sehirler')), ['Uzaktan']);
}

/** İstek içi önbellek (site haritası gibi sayfalar dosyayı onlarca kez sorar); her yazımdan ve geri yüklemeden sonra boşaltılır. */
function ilan_cache(?array $set = null, bool $clear = false): ?array
{
    static $cache = null;
    if ($clear) {
        $cache = null;
    } elseif ($set !== null) {
        $cache = $set;
    }
    return $cache;
}

/** Tüm ilanlar. Bozuk (dizi olmayan) öğeler atılır. */
function ilan_all(): array
{
    $c = ilan_cache();
    if ($c !== null) {
        return $c;
    }
    $out = [];
    if (is_file(ILAN_FILE)) {
        $data = json_decode((string) file_get_contents(ILAN_FILE), true);
        $out = is_array($data) ? array_values(array_filter($data, 'is_array')) : [];
    }
    ilan_cache($out);
    return $out;
}

/**
 * Okuma-değiştirme-yazma işlemini kilit altında çalıştırır (aynı anda gelen kayıtlar birbirini ezmesin).
 * Aynı istek içinde iç içe çağrılırsa kilit yeniden alınmaz.
 */
function ilan_locked(callable $fn)
{
    static $held = false;
    if ($held) {
        return $fn();
    }
    $lock = @fopen(ROOT . '/storage/ilanlar.lock', 'c');
    if (!$lock) {
        return $fn();
    }
    try {
        flock($lock, LOCK_EX);
        $held = true;
        ilan_cache(null, true);   // kilidi aldıktan sonra güncel dosya okunur
        return $fn();
    } finally {
        $held = false;
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/** Dosyaya güvenli yazım: önce geçici dosya, sonra yer değiştirme. */
function ilan_save_all(array $items): bool
{
    $items = array_values($items);
    $json  = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false) {
        return false;   // kodlanamayan içerik boş dosya olarak yazılmaz
    }
    $prev  = is_file(ILAN_FILE) ? (string) file_get_contents(ILAN_FILE) : null;
    if ($prev !== null && $prev === $json) {
        return true;   // içerik aynı: yeni sürüm ve günlük kaydı oluşmaz
    }
    // Geçmiş: önceki hal saklanır (ilk kayıtta "-0000" ekiyle, hiç silinmeyen özgün hal)
    $rev = null;
    if (defined('CONTENT_DIR')) {
        $hdir = CONTENT_DIR . '/_history/ilanlar';
        if (!is_dir($hdir)) @mkdir($hdir, 0755, true);
        $hasOrig = (bool) glob($hdir . '/*-0000.json');
        $rev = $hasOrig ? content_rev_id($hdir) : date('Ymd-His') . '-0000';
        if ($prev !== null) {
            @copy(ILAN_FILE, $hdir . '/' . $rev . '.json');
            $old = array_filter(glob($hdir . '/*.json') ?: [], fn($f) => !str_ends_with($f, '-0000.json'));
            rsort($old);
            foreach (array_slice($old, CONTENT_HISTORY_KEEP) as $f) @unlink($f);
        } else {
            @file_put_contents($hdir . '/' . $rev . '.json', '[]', LOCK_EX);   // hiç ilan yokken: boş liste de bir "önceki hal"dir
        }
    }
    $tmp = ILAN_FILE . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    if (!@rename($tmp, ILAN_FILE)) {
        @unlink($tmp);
        return false;
    }
    ilan_cache(null, true);
    if ($rev !== null && function_exists('changelog_record')) {
        changelog_record('ilanlar', $rev, $prev !== null ? (json_decode($prev, true) ?: []) : [], $items);
    }
    if (function_exists('seo_changed')) {
        seo_changed('ilanlar');
    }
    return true;
}

function ilan_find(string $id): ?array
{
    foreach (ilan_all() as $x) {
        if (($x['id'] ?? '') === $id) {
            return $x;
        }
    }
    return null;
}

function ilan_find_slug(string $slug): ?array
{
    foreach (ilan_all() as $x) {
        if (($x['slug'] ?? '') === $slug) {
            return $x;
        }
    }
    return null;
}

function ilan_new_id(): string
{
    return bin2hex(random_bytes(5));
}

/** İlanın sayfa adresi (yol). */
function ilan_path(array $x): string
{
    return 'kariyer/' . $x['slug'];
}

function ilan_url(array $x): string
{
    return url(ilan_path($x));
}

/** Başlıktan benzersiz adres: "Teşvik Danışmanı" → "tesvik-danismani" (varsa "-2", "-3"...). $exceptId: kendi kaydı çakışma sayılmaz. */
function ilan_slug(string $title, ?string $exceptId = null): string
{
    $base = rtrim(mb_substr(slugify($title), 0, 60), '-');
    $base = $base !== '' ? $base : 'ilan';
    $taken = [];
    foreach (ilan_all() as $x) {
        if (($x['id'] ?? '') !== $exceptId) {
            $taken[(string) ($x['slug'] ?? '')] = true;
        }
    }
    $slug = $base;
    for ($i = 2; isset($taken[$slug]); $i++) {
        $slug = $base . '-' . $i;
    }
    return $slug;
}

/** Son başvuru günü geçmiş mi? (son günün kendisinde ilan hâlâ açıktır) */
function ilan_deadline_passed(array $x): bool
{
    $d = (string) ($x['deadline'] ?? '');
    return $d !== '' && $d < date('Y-m-d');
}

/** AÇIK ilan: yayında ve süresi dolmamış. */
function ilan_active(array $x): bool
{
    return ($x['status'] ?? '') === 'yayinda' && !ilan_deadline_passed($x);
}

/** İlanın şu anki durumu: taslak | yayinda (açık) | doldu (yayında ama son gün geçti) | kapali */
function ilan_state(array $x): string
{
    $st = (string) ($x['status'] ?? 'taslak');
    if ($st === 'yayinda' && ilan_deadline_passed($x)) {
        return 'doldu';
    }
    return isset(ilan_statuses()[$st]) ? $st : 'taslak';
}

/**
 * Sitede görünen (açık) ilanlar, en yeni yayınlanan önce. Kariyer bölümü kapalıysa hiçbiri görünmez.
 * Taslak, kapalı ve süresi dolmuş ilanlar sitede listelenmez, site haritasına ve akışlara girmez.
 */
function ilan_published(): array
{
    if (function_exists('feature') && !feature('kariyer')) {
        return [];
    }
    $items = array_values(array_filter(ilan_all(), 'ilan_active'));
    usort($items, fn($x, $y) => strcmp(ilan_posted($y), ilan_posted($x)));
    return $items;
}

/** İlanın yayına çıktığı an (hiç yayınlanmadıysa oluşturulduğu an). */
function ilan_posted(array $x): string
{
    return (string) (($x['published'] ?? '') ?: ($x['created'] ?? ''));
}

/** Sayfa adresine (slug) göre ilan; taslak, hiç yayınlanmamış ya da bölüm kapalıysa null (404). Kapalı ve süresi dolmuş ilan döner (410 gösterilir). */
function ilan_public_find(string $slug): ?array
{
    if (function_exists('feature') && !feature('kariyer')) {
        return null;
    }
    $x = ilan_find_slug($slug);
    // Hiç yayınlanmamış ilan (taslak ya da doğrudan kapalı kaydedilmiş) ziyaretçiye hiçbir biçimde gösterilmez
    return $x && ($x['status'] ?? '') !== 'taslak' && (string) ($x['published'] ?? '') !== '' ? $x : null;
}

/** "Alan · Şehir · Tür · Deneyim" meta satırının parçaları. */
function ilan_meta(array $x): array
{
    return array_values(array_filter([(string) ($x['area'] ?? ''), (string) ($x['city'] ?? ''), (string) ($x['type'] ?? ''), (string) ($x['experience'] ?? '')], fn($v) => $v !== ''));
}

/** Kariyer sayfasında listelenen "alanlar": Çalıştığımız alanlar listesindeki hizmet adları (ilan alanı önerileri). */
function ilan_area_suggestions(): array
{
    $out = [];
    foreach (services() as $s) {
        $out[] = (string) $s['title'];
    }
    return $out;
}

/**
 * İş başvurusu sayıları (şüpheli ayrılanlar sayılmaz): genel havuz ve ilan başına.
 * @return array{genel:int, toplam:int, ilan: array<string, array{n:int, baslik:string}>}
 */
function ilan_application_counts(): array
{
    require_once APP . '/spam.php';
    $out = ['genel' => 0, 'toplam' => 0, 'ilan' => []];
    foreach (spam_records() as $r) {
        if (($r['form'] ?? '') !== 'kariyer' || !empty($r['spam'])) {
            continue;
        }
        $out['toplam']++;
        $id = (string) ($r['data']['ilan'] ?? '');
        if ($id === '') {
            $out['genel']++;
            continue;
        }
        $out['ilan'][$id] ??= ['n' => 0, 'baslik' => (string) ($r['data']['ilan_baslik'] ?? '')];
        $out['ilan'][$id]['n']++;
    }
    return $out;
}

/** Liste girdisini (satır satır metin ya da dizi) temiz bir maddelere çevirir; sınırı aşan madde ve fazlalık hata olur. */
function ilan_list_in($v, string $label, array &$errors): array
{
    $lines = is_array($v) ? $v : preg_split('/\R/u', (string) $v);
    $out = [];
    foreach ((array) $lines as $l) {
        if (!is_scalar($l)) continue;
        $l = trim((string) preg_replace('/\s+/u', ' ', (string) $l));
        $l = ltrim($l, "-•*· \t");   // satır başındaki madde imi atılır
        if ($l === '') continue;
        if (mb_strlen($l) > 300) {
            $errors[] = $label . ': bir madde en fazla 300 karakter olabilir ("' . mb_substr($l, 0, 40) . '…").';
            $l = mb_substr($l, 0, 300);
        }
        $out[] = $l;
    }
    if (count($out) > 20) {
        $errors[] = $label . ' en fazla 20 madde olabilir (' . count($out) . ' madde yazıldı).';
        $out = array_slice($out, 0, 20);
    }
    return $out;
}

/**
 * Gelen veriyi doğrular ve kayda hazır ilanı üretir (panel ve MCP ortak kullanır).
 * $in: title, area, city, type, experience, summary, duties, requirements, extras, deadline, status
 * @return array{0: array, 1: string[]}  [ilan, hatalar]
 */
function ilan_validate(array $in, ?array $old = null): array
{
    $errors = [];
    $str = function ($k, int $max, bool $oneLine = true) use ($in): string {
        $v = $in[$k] ?? '';
        if (!is_scalar($v)) return '';
        $v = trim(str_replace(["\r\n", "\r"], "\n", (string) $v));
        if ($oneLine) $v = trim((string) preg_replace('/\s*\n\s*/u', ' ', $v));
        return mb_substr($v, 0, $max);
    };
    $status = $str('status', 10);
    if (!isset(ilan_statuses()[$status])) {
        $status = (string) ($old['status'] ?? 'taslak');
    }
    $deadline = $str('deadline', 10);
    if ($deadline !== '' && (!($d = DateTime::createFromFormat('Y-m-d', $deadline)) || $d->format('Y-m-d') !== $deadline)) {
        $errors[] = 'Son başvuru tarihi geçersiz (YYYY-AA-GG biçiminde olmalı).';
        $deadline = '';
    }
    $now = date('c');
    $x = [
        'id'           => $old['id'] ?? ilan_new_id(),
        'slug'         => '',
        'title'        => $str('title', 120),
        'area'         => $str('area', 80),
        'city'         => $str('city', 40),
        'type'         => $str('type', 20),
        'experience'   => $str('experience', 40),
        'summary'      => $str('summary', 600, false),
        'duties'       => ilan_list_in($in['duties'] ?? [], 'Görevler', $errors),
        'requirements' => ilan_list_in($in['requirements'] ?? [], 'Aranan nitelikler', $errors),
        'extras'       => ilan_list_in($in['extras'] ?? [], 'Tercih sebebi olacaklar', $errors),
        'deadline'     => $deadline,
        'status'       => $status,
        'published'    => (string) ($old['published'] ?? ''),
        'created'      => $old['created'] ?? $now,
        'updated'      => $now,
    ];
    // Adres, ilan ilk kez yayınlanana kadar her kayıtta güncel başlıktan yeniden üretilir (çalışma başlığı adrese yapışmasın);
    // yayınlandıktan sonra değişmez (paylaşılmış bağlantılar bozulmasın)
    $x['slug'] = (string) ($old['published'] ?? '') !== '' && (string) ($old['slug'] ?? '') !== '' ? (string) $old['slug'] : ilan_slug($x['title'], $x['id']);
    if ($x['title'] === '') $errors[] = 'Pozisyon adı zorunludur.';
    if ($x['area'] === '') $errors[] = 'Alan / bölüm zorunludur.';
    if (!in_array($x['city'], ilan_cities(), true)) $errors[] = 'Şehir listeden seçilmeli ("Uzaktan" da seçilebilir).';
    if (!in_array($x['type'], ilan_types(), true)) $errors[] = 'Çalışma türü listeden seçilmeli.';
    // Deneyim listesi sonradan düzenlenmiş olabilir: kayıtlı eski değer değişmediyse kabul edilir (ilan yine de kapatılabilsin)
    if ($x['experience'] !== '' && !in_array($x['experience'], (array) site('deneyim'), true) && $x['experience'] !== (string) ($old['experience'] ?? '')) $errors[] = 'Deneyim listeden seçilmeli.';
    if ($x['status'] === 'yayinda') {
        if (!$x['requirements']) $errors[] = 'Yayınlamak için en az bir aranan nitelik yazın.';
        // Süresi çoktan geçmiş bir ilan yayınlanamaz (az önce yazılan tarih ya da kapalı ilanın yeniden açılması); zaten yayındaki ilanın başka alanları serbestçe düzeltilir
        $was = ($old['status'] ?? '') === 'yayinda' && ($old['deadline'] ?? '') === $x['deadline'];
        if (ilan_deadline_passed($x) && !$was) {
            $errors[] = 'Son başvuru tarihi geçmişte kaldı (' . $x['deadline'] . '); yayınlamak için ileri bir tarih girin ya da tarihi boş bırakın.';
        }
    }
    if ($x['status'] === 'yayinda' && $x['published'] === '') {
        $x['published'] = $now;
    }
    return [$x, $errors];
}

/** İlanı ekler ya da günceller (kilit altında; adres benzersizliği kilit içinde yeniden denetlenir). */
function ilan_upsert(array $x): bool
{
    return (bool) ilan_locked(function () use ($x): bool {
        $items = ilan_all();
        foreach ($items as $y) {
            if (($y['id'] ?? '') !== $x['id'] && ($y['slug'] ?? '') === $x['slug']) {
                $x['slug'] = ilan_slug((string) $x['title'], (string) $x['id']);   // aynı anda başka bir kayıt aynı adresi almış
                break;
            }
        }
        $found = false;
        foreach ($items as $i => $y) {
            if (($y['id'] ?? '') === $x['id']) { $items[$i] = $x; $found = true; }
        }
        if (!$found) $items[] = $x;
        return ilan_save_all($items);
    });
}

/** İlanı siler (kilit altında); başvurular silinmez (kayıtta ilan kimliği ve başlık kopyası kalır). */
function ilan_delete(string $id): bool
{
    return (bool) ilan_locked(fn(): bool => ilan_save_all(array_values(array_filter(ilan_all(), fn($x) => ($x['id'] ?? '') !== $id))));
}

/**
 * Geri yüklenecek (yedekten ya da geçmişten gelen) ilan verisini temizler: yalnızca dizi öğeler, geçerli kimlik ve adres,
 * her öğe ilan_validate'ten geçer; geçmeyenler atılır. Kimlik ve adres tekrarı atılır / benzersizleştirilir.
 */
function ilan_clean_restore(array $data): array
{
    $out = [];
    $ids = [];
    $slugs = [];
    foreach ($data as $x) {
        if (!is_array($x) || !is_string($x['id'] ?? null) || !preg_match('/^[a-f0-9]{10}$/D', $x['id']) || isset($ids[$x['id']])) continue;
        if (!is_string($x['slug'] ?? null) || !preg_match('/^[a-z0-9-]+$/D', $x['slug'])) continue;
        [$v, $errs] = ilan_validate($x, $x);
        if ($errs) continue;
        if (is_string($x['updated'] ?? null) && $x['updated'] !== '') $v['updated'] = $x['updated'];
        $v['slug'] = $x['slug'];   // geri yüklenen adres korunur (yayınlanmışsa bağlantılar çalışmaya devam eder)
        $base = $v['slug'];
        for ($i = 2; isset($slugs[$v['slug']]); $i++) $v['slug'] = $base . '-' . $i;
        $ids[$v['id']] = true;
        $slugs[$v['slug']] = true;
        $out[] = $v;
    }
    return $out;
}

/** Son geri yüklemede AÇILAN (daha önce açık olmayan, şimdi açık) ilanların başlıkları; mesajlarda gösterilir. */
function ilan_restore_opened(?array $set = null): array
{
    static $opened = [];
    if ($set !== null) $opened = $set;
    return $opened;
}

/**
 * Yedekten ya da geçmişten gelen ilan verisini temizleyip kaydeder; geri yükleme sonucunda başvuruya yeniden açılan ilanları
 * ilan_restore_opened() ile bildirir. Dolu veri tamamen geçersizse hiçbir şey yazılmaz (false).
 */
function ilan_restore_all(array $data): bool
{
    $clean = ilan_clean_restore($data);
    if ($data && !$clean) return false;
    return (bool) ilan_locked(function () use ($clean): bool {
        $before = array_column(array_filter(ilan_all(), 'ilan_active'), 'title', 'id');
        if (!ilan_save_all($clean)) return false;
        $after = array_filter($clean, 'ilan_active');
        ilan_restore_opened(array_values(array_map(fn($x) => (string) $x['title'], array_filter($after, fn($x) => !isset($before[$x['id']])))));
        return true;
    });
}

/** schema.org employmentType karşılığı. */
function ilan_employment_type(string $type): string
{
    return ['Tam zamanlı' => 'FULL_TIME', 'Yarı zamanlı' => 'PART_TIME', 'Staj' => 'INTERN', 'Proje bazlı' => 'CONTRACTOR'][$type] ?? 'OTHER';
}

/** İlan metninin tek parça HTML hali (JobPosting "description" için): özet ve üç liste. */
function ilan_description_html(array $x): string
{
    $h = '';
    if (($x['summary'] ?? '') !== '') {
        $h .= '<p>' . nl2br(e((string) $x['summary']), false) . '</p>';
    }
    foreach (['duties' => 'Görevler', 'requirements' => 'Aranan nitelikler', 'extras' => 'Tercih sebebi olacaklar'] as $k => $label) {
        if (!empty($x[$k])) {
            $h .= '<h3>' . e($label) . '</h3><ul>' . implode('', array_map(fn($i) => '<li>' . e((string) $i) . '</li>', (array) $x[$k])) . '</ul>';
        }
    }
    return $h;
}

/** Sayfa açıklaması (arama sonucu): özet, yoksa ilk görevler; en çok 160 karakter. */
function ilan_seo_description(array $x): string
{
    $s = trim((string) preg_replace('/\s+/u', ' ', (string) ($x['summary'] ?? '')));
    if ($s === '') {
        $s = implode('. ', array_slice((array) ($x['requirements'] ?? []), 0, 2));
    }
    $s = (string) $x['title'] . ($s !== '' ? ': ' . $s : '') . ' (' . implode(', ', array_filter([(string) $x['city'], (string) $x['type']])) . ')';
    return mb_strlen($s) > 160 ? rtrim(mb_substr($s, 0, 159)) . '…' : $s;
}

/** schema.org JobPosting düğümü (yalnızca açık ilan için). */
function ilan_job_posting(array $x): array
{
    $canon  = absolute_url(ilan_path($x));
    $remote = ($x['city'] ?? '') === 'Uzaktan';
    $loc    = ['@type' => 'Place', 'address' => array_filter(['@type' => 'PostalAddress', 'addressLocality' => $remote ? '' : (string) $x['city'], 'addressCountry' => 'TR'])];
    $node = [
        '@type'              => 'JobPosting',
        '@id'                => $canon . '#jobposting',
        'url'                => $canon,
        'title'              => (string) $x['title'],
        'description'        => ilan_description_html($x),
        'datePosted'         => substr(ilan_posted($x), 0, 10),
        'validThrough'       => !empty($x['deadline']) ? date('c', (int) strtotime($x['deadline'] . ' 23:59:59')) : null,
        'employmentType'     => ilan_employment_type((string) $x['type']),
        'occupationalCategory' => (string) $x['area'],
        'responsibilities'   => !empty($x['duties']) ? implode("\n", (array) $x['duties']) : null,
        'qualifications'     => !empty($x['requirements']) ? implode("\n", (array) $x['requirements']) : null,
        'hiringOrganization' => ['@type' => 'Organization', 'name' => (string) cfg('name'), 'sameAs' => absolute_url(), 'logo' => absolute_url('assets/img/logo.png')],
        'jobLocation'        => $loc,
        'jobLocationType'    => $remote ? 'TELECOMMUTE' : null,
        'applicantLocationRequirements' => $remote ? ['@type' => 'Country', 'name' => 'TR'] : null,
        'identifier'         => ['@type' => 'PropertyValue', 'name' => (string) cfg('name'), 'value' => (string) $x['id']],
        'directApply'        => true,
        'inLanguage'         => 'tr-TR',
    ];
    return array_filter($node, fn($v) => $v !== null && $v !== '');
}
