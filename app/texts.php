<?php
declare(strict_types=1);

/**
 * Düzenlenebilir sayfa metinleri: biçim işaretleri, yer tutucular, doğrulama ve kaydetme.
 * ---------------------------------------------------------------------------
 * Kayıt defteri app/data/texts/NN-grup.php dosyalarındadır; texts_registry(), texts_flat() ve t() app/content.php içindedir.
 * Bu dosya geri kalanını taşır. Kurallar ve örnekler: METIN-KURALLARI.md (geliştirici belgesi).
 *
 * Kayıt defterinde bir metin: 'grup.bolum.anahtar' => [etiket, varsayılan, tür, yardım?, seçenekler?]
 *   tür:
 *     line   tek satır düz metin (düğme, menü, etiket, sekme başlığı, aria-label, placeholder)
 *     text   düz metin, birkaç satır olabilir; sayfada paragraf olarak akar (satır sonları boşluk sayılır)
 *     lines  düz metin; her satır sonu sayfada elle satır sonu (<br>) olur (iki satırlı başlıklar)
 *     rich   düz metin + sınırlı biçim işaretleri (aşağıda); satır sonu <br> olur
 *   seçenekler (hepsi isteğe bağlı):
 *     'max'     en çok karakter (biçim işaretleri sayılmaz). Verilmezse varsayılan metnin üç katı (en az 60, en çok 2000).
 *     'lines'   en çok satır. Verilmezse: line 1, text 8, lines/rich varsayılanın satır sayısı + 2.
 *     'vars'    metinde kullanılabilen yer tutucular: ['firma', 'yil', 'n' => 'kalan gün sayısı']
 *               (sayısal anahtar = genel yer tutucu, adlı anahtar = şablonun verdiği yerel yer tutucu ve açıklaması)
 *     'need'    metinde mutlaka bulunması gereken yer tutucular (silinirse kayıt reddedilir)
 *     'nowidow' son iki sözcük arada bölünmesin (başlıklarda)
 *     'js'      true ise metin JavaScript'e de verilir (app.js içinde T('anahtar'); bkz. texts_js)
 *
 * Biçim işaretleri (yalnızca rich türünde; HTML yazılamaz):
 *   [kalın]…[/kalın]  [eğik]…[/eğik]  [çizgi]…[/çizgi] (el çizimi alt çizgi)  [kırmızı-çizgi]…[/kırmızı-çizgi]
 *   [halka]…[/halka] (el çizimi halka)  [kırmızı-halka]…[/kırmızı-halka]
 *   [vurgu]…[/vurgu] (fosforlu kalem gibi vurgu: <mark>; yasal metinlerin "Kısaca" kutusunda kullanılır)
 *   Çizgi ve halka birbirinin içine girmez; kalın, eğik ve vurgu her birinin içinde olabilir.
 *
 * Yer tutucular: {ad} ya da {ad|süzgeç}. Değeri sayfa basılırken doldurulur. Süzgeçler (sayı değerlerinde):
 *   |yazı / |Yazı  sayıyı yazıyla söyler: 8 → "sekiz" / "Sekiz" (20'den büyük sayı rakamla kalır)
 *   |sıra / |Sıra  sıra sözcüğü: 30 → "otuzuncu" / "Otuzuncu" (99'dan büyükte "100.")
 *   |iyelik        ek almış sözcük: 3 → "üçü", 6 → "altısı" ("ilk {n|iyelik}", "{n|yazı} ilkenin {n|iyelik}"; 99'dan büyükte "100’ü")
 *   |büyük         BÜYÜK HARF (her değerde)
 *   Genel olanlar app/data/text-vars.php içindedir; yerel olanları şablon t('anahtar', ['n' => 3]) ile verir.
 */

const TEXT_TYPES = ['line', 'text', 'lines', 'rich'];

/** Biçim işareti adları: ad => [tür, ...]. */
const TEXT_MARKS = [
    'kalın'         => ['b'],
    'eğik'          => ['em'],
    'çizgi'         => ['annot', 'under', ''],
    'kırmızı-çizgi' => ['annot', 'under', 'red'],
    'halka'         => ['annot', 'circle', ''],
    'kırmızı-halka' => ['annot', 'circle', 'red'],
    'vurgu'         => ['mark'],
];

/** Yer tutucu süzgeçleri (bkz. text_var). */
const TEXT_FILTERS = ['yazı', 'Yazı', 'sıra', 'Sıra', 'iyelik', 'büyük'];

/** Yer tutucu: {ad} ya da {ad|süzgeç}. */
const TEXT_PH = '/\{([a-z][a-z0-9_]*)(?:\|([^{}|\s]{1,12}))?\}/u';

/** Biçim işareti etiketi: [ad] ya da [/ad]. */
function text_mark_re(): string
{
    return '/(\[\/?(?:' . implode('|', array_map(fn($n) => preg_quote($n, '/'), array_keys(TEXT_MARKS))) . ')\])/u';
}

/** Türkçe küçük harf. */
function text_lower(string $s): string
{
    return mb_strtolower(strtr($s, ['İ' => 'i', 'I' => 'ı']));
}

/* ---------- Yer tutucular ---------- */

/** Genel yer tutucular: ad => [açıklama, değer işlevi, html mi]. */
function text_globals(): array
{
    static $g = null;
    if ($g === null) {
        $g = (array) require APP . '/data/text-vars.php';
    }
    return $g;
}

/** Bir yer tutucunun değeri: [metin, html mi]; bilinmiyorsa null. */
function text_var(string $name, ?string $filter, array $local): ?array
{
    if (array_key_exists($name, $local)) {
        $v = $local[$name];
        $html = false;
        if (is_array($v) && isset($v['html'])) {
            $v = (string) $v['html'];
            $html = true;
        }
    } elseif (isset(text_globals()[$name])) {
        [, $fn, $html] = text_globals()[$name] + [2 => false];
        $v = $fn();
    } else {
        return null;
    }
    $v = (string) $v;
    if ($filter !== null && !$html && preg_match('/^\d+$/', $v)) {
        $n = (int) $v;
        if ($filter === 'Yazı') {
            $v = number_word($n);
        } elseif ($filter === 'yazı') {
            $v = text_lower(number_word($n));
        } elseif ($filter === 'sıra' || $filter === 'Sıra') {
            $v = text_number_ordinal($n);
            $v = $filter === 'Sıra' ? text_upper_first($v) : $v;
        } elseif ($filter === 'iyelik') {
            $v = text_number_possessive($n);
        }
    }
    if ($filter === 'büyük' && !$html) {
        $v = tr_upper($v);
    }
    return [$v, $html];
}

/** İlk harfi Türkçe büyük harfe çevirir ("otuzuncu" → "Otuzuncu", "ikinci" → "İkinci"). */
function text_upper_first(string $s): string
{
    return $s === '' ? $s : mb_strtoupper(strtr(mb_substr($s, 0, 1), ['i' => 'İ', 'ı' => 'I'])) . mb_substr($s, 1);
}

/**
 * 0-99 arası sayının sözcükleri (soldan sağa: ["yirmi", "beş"]); aralık dışında null.
 * Sıra ve iyelik ekleri son sözcüğe gelir.
 */
function text_number_parts(int $n): ?array
{
    $ones = [0 => 'sıfır', 1 => 'bir', 'iki', 'üç', 'dört', 'beş', 'altı', 'yedi', 'sekiz', 'dokuz'];
    $tens = [10 => 'on', 20 => 'yirmi', 30 => 'otuz', 40 => 'kırk', 50 => 'elli', 60 => 'altmış', 70 => 'yetmiş', 80 => 'seksen', 90 => 'doksan'];
    if ($n < 0 || $n > 99) {
        return null;
    }
    if ($n < 10) {
        return [$ones[$n]];
    }
    $t = $n - $n % 10;
    return $n % 10 === 0 ? [$tens[$t]] : [$tens[$t], $ones[$n % 10]];
}

/** Sıra sözcüğü: 1 → "birinci", 30 → "otuzuncu", 25 → "yirmi beşinci"; 99'dan büyükte "100.". */
function text_number_ordinal(int $n): string
{
    $parts = text_number_parts($n);
    if ($parts === null) {
        return $n . '.';
    }
    $ord = ['sıfır' => 'sıfırıncı', 'bir' => 'birinci', 'iki' => 'ikinci', 'üç' => 'üçüncü', 'dört' => 'dördüncü', 'beş' => 'beşinci', 'altı' => 'altıncı', 'yedi' => 'yedinci',
        'sekiz' => 'sekizinci', 'dokuz' => 'dokuzuncu', 'on' => 'onuncu', 'yirmi' => 'yirminci', 'otuz' => 'otuzuncu', 'kırk' => 'kırkıncı', 'elli' => 'ellinci',
        'altmış' => 'altmışıncı', 'yetmiş' => 'yetmişinci', 'seksen' => 'sekseninci', 'doksan' => 'doksanıncı'];
    $last = array_pop($parts);
    $parts[] = $ord[$last];
    return implode(' ', $parts);
}

/** Ek almış sayı sözcüğü (üçüncü tekil iyelik): 3 → "üçü", 6 → "altısı", 12 → "on ikisi"; 99'dan büyükte "100’ü". */
function text_number_possessive(int $n): string
{
    $parts = text_number_parts($n);
    if ($parts === null) {
        return $n . '’ü';
    }
    $pos = ['sıfır' => 'sıfırı', 'bir' => 'biri', 'iki' => 'ikisi', 'üç' => 'üçü', 'dört' => 'dördü', 'beş' => 'beşi', 'altı' => 'altısı', 'yedi' => 'yedisi', 'sekiz' => 'sekizi',
        'dokuz' => 'dokuzu', 'on' => 'onu', 'yirmi' => 'yirmisi', 'otuz' => 'otuzu', 'kırk' => 'kırkı', 'elli' => 'ellisi', 'altmış' => 'altmışı', 'yetmiş' => 'yetmişi',
        'seksen' => 'sekseni', 'doksan' => 'doksanı'];
    $last = array_pop($parts);
    $parts[] = $pos[$last];
    return implode(' ', $parts);
}

/** Adları Türkçe sıralar: "A", "A ve B", "A, B ve C". */
function text_join_list(array $names): string
{
    $names = array_values(array_filter(array_map('strval', $names), fn($n) => $n !== ''));
    if (count($names) < 2) {
        return $names[0] ?? '';
    }
    $last = array_pop($names);
    return implode(', ', $names) . ' ve ' . $last;
}

/** Metindeki yer tutucuları ve biçim işaretlerini ayıklar. */
function text_placeholders(string $s): array
{
    preg_match_all(TEXT_PH, $s, $m, PREG_SET_ORDER);
    return $m;
}

/* ---------- Biçim işaretleri ---------- */

/** İşaretleri soyar. */
function text_strip_marks(string $s): string
{
    return (string) preg_replace('/\[\/?(?:' . implode('|', array_map(fn($n) => preg_quote($n, '/'), array_keys(TEXT_MARKS))) . ')\]/u', '', $s);
}

/** İşaretlerin eşleşmesini denetler; sorun varsa Türkçe açıklama, yoksa null. */
function text_marks_check(string $s): ?string
{
    $stack = [];
    foreach (preg_split(text_mark_re(), $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $piece) {
        if (!preg_match('/^\[(\/?)([^\]]+)\]$/u', $piece, $m) || !isset(TEXT_MARKS[$m[2]])) {
            continue;
        }
        $name = $m[2];
        if ($m[1] === '') {
            if (TEXT_MARKS[$name][0] === 'annot') {
                foreach ($stack as $open) {
                    if (TEXT_MARKS[$open][0] === 'annot') {
                        return '[' . $name . '] işareti [' . $open . '] işaretinin içine yazılamaz; çizgi ve halka iç içe olmaz.';
                    }
                }
            }
            $stack[] = $name;
        } else {
            if (!$stack) {
                return '[/' . $name . '] işaretinin açılışı yok.';
            }
            $top = array_pop($stack);
            if ($top !== $name) {
                return '[' . $top . '] işareti [/' . $top . '] ile kapatılmalı; [/' . $name . '] burada yanlış yerde.';
            }
        }
    }
    return $stack ? '[' . end($stack) . '] işareti kapatılmamış; sonuna [/' . end($stack) . '] ekleyin.' : null;
}

/** Bir işaretin HTML karşılığı (içerik zaten güvenli HTML'dir). */
function text_mark_html(string $name, string $inner): string
{
    $d = TEXT_MARKS[$name];
    if ($d[0] === 'b') {
        return '<b>' . $inner . '</b>';
    }
    if ($d[0] === 'em') {
        return '<em>' . $inner . '</em>';
    }
    if ($d[0] === 'mark') {
        return '<mark>' . $inner . '</mark>';
    }
    return annot($inner, $d[1], $d[2]);
}

/* ---------- Gösterim ---------- */

/** Kayıttaki güncel ham metin: panelden değiştirilmişse o, değilse varsayılan. */
function text_source(string $key): ?array
{
    $item = texts_flat()[$key] ?? null;
    if ($item === null) {
        return null;
    }
    $over = content_get('texts', []);
    $raw  = is_array($over) && isset($over[$key]) && is_string($over[$key]) && trim($over[$key]) !== '' ? $over[$key] : (string) $item[1];
    return [$raw, (string) ($item[2] ?? 'line')];
}

/** Düz parça: kaçırır, yer tutucuları doldurur, satır sonlarını $nl ile değiştirir. */
function text_part_html(string $s, array $vars, string $nl): string
{
    $out = [];
    foreach (explode("\n", $s) as $line) {
        $out[] = preg_replace_callback(TEXT_PH, function (array $m) use ($vars): string {
            $v = text_var($m[1], $m[2] ?? null, $vars);
            if ($v === null) {
                return '';
            }
            return $v[1] ? $v[0] : e($v[0]);
        }, e($line));
    }
    return implode($nl, $out);
}

/** Güvenli HTML'in son iki sözcüğünü bağlar (etiketlerin içine dokunmaz). */
function text_nowidow(string $html): string
{
    $parts = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    for ($i = count($parts) - 1; $i >= 0; $i--) {
        if ($i % 2 === 1) {
            continue;   // etiket
        }
        $pos = strrpos($parts[$i], ' ');
        if ($pos !== false) {
            $parts[$i] = substr($parts[$i], 0, $pos) . '&nbsp;' . substr($parts[$i], $pos + 1);
            break;
        }
    }
    return implode('', $parts);
}

/** Biçim işaretli metni güvenli HTML'e çevirir (kapanmamış işaretler sonda kapatılır, artık kapanışlar yok sayılır). */
function text_rich_html(string $raw, array $vars, string $nl = '<br>'): string
{
    $stack = [['', '']];
    foreach (preg_split(text_mark_re(), $raw, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $piece) {
        if (preg_match('/^\[(\/?)([^\]]+)\]$/u', $piece, $m) && isset(TEXT_MARKS[$m[2]])) {
            if ($m[1] === '') {
                $stack[] = [$m[2], ''];
            } elseif (count($stack) > 1 && end($stack)[0] === $m[2]) {
                [$name, $buf] = array_pop($stack);
                $stack[count($stack) - 1][1] .= text_mark_html($name, $buf);
            }
            continue;
        }
        $stack[count($stack) - 1][1] .= text_part_html($piece, $vars, $nl);
    }
    while (count($stack) > 1) {
        [$name, $buf] = array_pop($stack);
        $stack[count($stack) - 1][1] .= text_mark_html($name, $buf);
    }
    return $stack[0][1];
}

/**
 * Sayfaya basılacak güvenli HTML. Şablonda doğrudan yazılır: <?= th('grup.anahtar') ?>
 * $vars: yerel yer tutucular; düz değer kaçırılır, ['html' => '…'] verilen değer olduğu gibi basılır (yalnızca şablon verebilir).
 */
function th(string $key, array $vars = []): string
{
    $src = text_source($key);
    if ($src === null) {
        return e($key);
    }
    [$raw, $type] = $src;
    switch ($type) {
        case 'rich':
            $html = text_rich_html($raw, $vars);
            break;
        case 'lines':
            $html = text_part_html(text_strip_marks($raw), $vars, '<br>');
            break;
        case 'text':
            $html = text_part_html(text_strip_marks($raw), $vars, "\n");
            break;
        default:
            $html = text_part_html((string) preg_replace('/\s*\n\s*/', ' ', text_strip_marks($raw)), $vars, ' ');
    }
    return !empty(texts_opts($key)['nowidow']) ? text_nowidow($html) : $html;
}

/** Düz metin sürümü (ham değil, yer tutucular doldurulmuş, işaretler soyulmuş); şablonda e(t('…')) ile kaçırılarak yazılır. */
function text_plain(string $key, array $vars = [], bool $keepUnknown = false): string
{
    $src = text_source($key);
    if ($src === null) {
        return $key;
    }
    [$raw, $type] = $src;
    $raw = text_strip_marks($raw);
    $raw = $type === 'text' || $type === 'lines' ? $raw : (string) preg_replace('/\s*\n\s*/', ' ', $raw);
    return (string) preg_replace_callback(TEXT_PH, function (array $m) use ($vars, $keepUnknown): string {
        $v = text_var($m[1], $m[2] ?? null, $vars);
        if ($v === null) {
            return $keepUnknown ? $m[0] : '';
        }
        return $v[1] ? trim(html_entity_decode(strip_tags($v[0]), ENT_QUOTES, 'UTF-8')) : $v[0];
    }, $raw);
}

/**
 * JavaScript'in okuyacağı metinler: kayıt defterinde 'js' => true işaretli olanlar (anahtar => düz metin). Genel yer tutucular doldurulur;
 * şablona özgü ({n} gibi) olanlar JavaScript'te app.js içindeki T('anahtar', {n: 3}) ile doldurulur.
 */
function texts_js(): array
{
    $out = [];
    foreach (texts_flat() as $k => $item) {
        if (!empty($item[4]['js'])) {
            $out[$k] = text_plain($k, [], true);
        }
    }
    return $out;
}

/* ---------- Seçenekler ve doğrulama ---------- */

/** Bir metnin seçenekleri, varsayılanlarla tamamlanmış: max, lines, vars (ad => açıklama), need, nowidow. */
function texts_opts(string $key): array
{
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $item = texts_flat()[$key] ?? null;
    if ($item === null) {
        return [];
    }
    $type = (string) ($item[2] ?? 'line');
    $def  = (string) $item[1];
    $o    = (array) ($item[4] ?? []);
    $vars = [];
    foreach ((array) ($o['vars'] ?? []) as $k => $v) {
        if (is_int($k)) {
            $vars[(string) $v] = (string) (text_globals()[$v][0] ?? '');
        } else {
            $vars[(string) $k] = (string) $v;
        }
    }
    $defLines = substr_count($def, "\n") + 1;
    return $cache[$key] = [
        'max'     => (int) ($o['max'] ?? min(2000, max(60, mb_strlen(text_strip_marks($def)) * 3))),
        'lines'   => (int) ($o['lines'] ?? ($type === 'line' ? 1 : ($type === 'text' ? 8 : $defLines + 2))),
        'vars'    => $vars,
        'need'    => array_values((array) ($o['need'] ?? [])),
        'nowidow' => !empty($o['nowidow']),
    ];
}

/**
 * Bir metni kayıt için denetler ve temizler.
 * @return array{value:string, error:?string}  value: kaydedilecek metin; '' = özgün metne dön. error doluysa kayıt reddedilir.
 */
function texts_clean(string $key, $raw): array
{
    $flat = texts_flat();
    if (!isset($flat[$key])) {
        return ['value' => '', 'error' => 'Bu anahtarda bir metin yok: ' . mb_substr($key, 0, 60)];
    }
    if (!is_string($raw)) {
        return ['value' => '', 'error' => 'Metin yazı olmalı.'];
    }
    $item = $flat[$key];
    $type = (string) ($item[2] ?? 'line');
    $opts = texts_opts($key);
    $v    = str_replace(["\r\n", "\r"], "\n", $raw);
    $v    = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v);   // denetim karakterleri
    $v    = trim((string) preg_replace('/[ \t]+\n|\n[ \t]+/', "\n", $v));
    if (mb_strlen($v) > 20000) {
        return ['value' => '', 'error' => 'Metin çok uzun.'];
    }
    if ($type === 'line') {
        $v = trim((string) preg_replace('/\s*\n\s*/', ' ', $v));
    } else {
        $v = trim((string) preg_replace("/\n{3,}/", "\n\n", $v));
    }
    if ($v === '' || $v === (string) $item[1]) {
        return ['value' => '', 'error' => null];
    }
    // HTML ve betik yazılamaz: biçim için köşeli işaretler vardır
    if (preg_match('/<\s*\/?\s*[a-zA-Z!?]/', $v)) {
        return ['value' => '', 'error' => 'HTML etiketi yazılamaz (“<” işaretinden sonra harf gelmemeli).' . ($type === 'rich' ? ' Kalın, eğik, çizgi ya da halka için köşeli işaretleri kullanın; altındaki “Biçim” düğmeleri yazar.' : '')];
    }
    if ($type !== 'rich' && $v !== text_strip_marks($v)) {
        return ['value' => '', 'error' => 'Bu metin düz metindir; [kalın] gibi biçim işaretleri burada kullanılamaz.'];
    }
    if ($type === 'rich' && ($err = text_marks_check($v)) !== null) {
        return ['value' => '', 'error' => $err];
    }
    // Yer tutucular: yalnızca bu metne tanımlı olanlar; kullanılmayan süslü parantez yok
    $allowed = array_keys($opts['vars']);
    $used    = [];
    foreach (text_placeholders($v) as $m) {
        if (!in_array($m[1], $allowed, true)) {
            return ['value' => '', 'error' => 'Bilinmeyen yer tutucu {' . $m[1] . '}. ' . ($allowed ? 'Bu metinde yazılabilenler: ' . implode(', ', array_map(fn($a) => '{' . $a . '}', $allowed)) . '.' : 'Bu metinde yer tutucu kullanılamaz.')];
        }
        if (isset($m[2]) && !in_array($m[2], TEXT_FILTERS, true)) {
            return ['value' => '', 'error' => 'Bilinmeyen biçim {' . $m[1] . '|' . $m[2] . '}; yalnızca ' . implode(', ', array_map(fn($f) => '|' . $f, TEXT_FILTERS)) . ' vardır.'];
        }
        $used[$m[1]] = true;
    }
    if (preg_match('/[{}]/', (string) preg_replace(TEXT_PH, '', $v))) {
        return ['value' => '', 'error' => 'Süslü parantez yalnızca yer tutucu için kullanılır, ör. ' . ($allowed ? '{' . $allowed[0] . '}' : '{firma}') . '. Eksik ya da fazla parantez var.'];
    }
    foreach ($opts['need'] as $n) {
        if (!isset($used[$n])) {
            return ['value' => '', 'error' => '{' . $n . '} yer tutucusu silinmemeli; sayfada o yere ' . ($opts['vars'][$n] !== '' ? $opts['vars'][$n] : 'gerekli değer') . ' yazılır.'];
        }
    }
    $len = mb_strlen(text_strip_marks($v));
    if ($len > $opts['max']) {
        return ['value' => '', 'error' => 'Çok uzun: ' . $len . ' karakter yazdınız, bu metin en çok ' . $opts['max'] . ' karakter olabilir (tasarım bu uzunluğa göre kurulu).'];
    }
    if (substr_count($v, "\n") + 1 > $opts['lines']) {
        return ['value' => '', 'error' => $opts['lines'] === 1 ? 'Bu metin tek satır olmalı.' : 'Çok fazla satır: en çok ' . $opts['lines'] . ' satır olabilir.'];
    }
    return ['value' => $v, 'error' => null];
}

/**
 * Metinleri toplu kaydeder (hepsi ya da hiçbiri). $posted: anahtar => yeni metin ('' ya da özgün metin = özgün hale döner).
 * $only: verilirse yalnızca bu anahtarlar kabul edilir (panelde o ekranda görünenler).
 * @return array{ok:bool, errors:array<string,string>, changed:string[]}  errors: anahtar => açıklama; ok=false ise hiçbir şey kaydedilmez.
 */
function texts_save(array $posted, ?array $only = null): array
{
    $flat   = texts_flat();
    $old    = (array) content_get('texts', []);
    $new    = $old;
    $errors = [];
    foreach ($posted as $k => $raw) {
        $k = (string) $k;
        if (!isset($flat[$k]) || ($only !== null && !in_array($k, $only, true))) {
            $errors[$k] = 'Bilinmeyen metin anahtarı: ' . mb_substr($k, 0, 60) . '.';
            continue;
        }
        $r = texts_clean($k, $raw);
        if ($r['error'] !== null) {
            $errors[$k] = $r['error'];
        } elseif ($r['value'] === '') {
            unset($new[$k]);
        } else {
            $new[$k] = $r['value'];
        }
    }
    if ($errors) {
        return ['ok' => false, 'errors' => $errors, 'changed' => []];
    }
    ksort($new);
    ksort($old);
    if ($new === $old) {
        return ['ok' => true, 'errors' => [], 'changed' => []];
    }
    if (!content_put('texts', $new)) {
        return ['ok' => false, 'errors' => ['' => 'Kaydedilemedi: storage klasörü yazılabilir mi?'], 'changed' => []];
    }
    $changed = [];
    foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $k) {
        if (($old[$k] ?? null) !== ($new[$k] ?? null)) {
            $changed[] = (string) $k;
        }
    }
    return ['ok' => true, 'errors' => [], 'changed' => $changed];
}

/** Dışarıdan gelen bir metin kaydını (yedek geri yükleme) süzer: bilinmeyen anahtarlar ve geçersiz metinler düşer. */
function texts_sanitize_all(array $over): array
{
    $out = [];
    foreach ($over as $k => $raw) {
        $r = is_string($k) ? texts_clean($k, $raw) : ['value' => '', 'error' => 'x'];
        if ($r['error'] === null && $r['value'] !== '') {
            $out[$k] = $r['value'];
        }
    }
    ksort($out);
    return $out;
}

/** Kayıt defterinin kendi tutarlılığı: sorun listesi (boşsa kayıt defteri temiz). Yeni metin dosyası eklenince çalıştırılır. */
function texts_registry_check(): array
{
    $problems = [];
    $seen = [];
    foreach (texts_registry() as $gid => $g) {
        if (!preg_match('/^[a-z][a-z0-9]*$/', (string) $gid)) {
            $problems[] = "Grup adı geçersiz: $gid (küçük harf ve rakam)";
        }
        foreach (['label', 'icon'] as $f) {
            if (trim((string) ($g[$f] ?? '')) === '') {
                $problems[] = "$gid: '$f' boş";
            }
        }
        if (!isset($g['url'])) {
            $problems[] = "$gid: 'url' yok (anasayfa için boş metin)";
        }
        if (empty($g['sections'])) {
            $problems[] = "$gid: bölüm yok";
        }
        foreach ((array) ($g['sections'] ?? []) as $sec => $items) {
            foreach ((array) $items as $k => $it) {
                $where = "$gid › $sec › $k";
                if (isset($seen[$k])) {
                    $problems[] = "$where: anahtar ikinci kez tanımlı (ilki: {$seen[$k]})";
                }
                $seen[$k] = $where;
                if (!str_starts_with($k, $gid . '.') || !preg_match('/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*){1,3}$/', $k)) {
                    $problems[] = "$where: anahtar '$gid.' ile başlamalı, küçük harf/rakam/alt çizgi, 2-4 parça";
                }
                if (!is_array($it) || count($it) < 3) {
                    $problems[] = "$where: [etiket, varsayılan, tür, yardım?, seçenekler?] biçiminde değil";
                    continue;
                }
                [$label, $def, $type] = $it;
                if (!is_string($label) || trim($label) === '' || mb_strlen($label) > 90) {
                    $problems[] = "$where: etiket boş ya da 90 karakterden uzun";
                } elseif ($label === $k || preg_match('/^[a-z0-9_.]+$/', $label)) {
                    $problems[] = "$where: etiket anahtar gibi; sahibine anlamlı bir ad yazın";
                }
                if (!in_array($type, TEXT_TYPES, true)) {
                    $problems[] = "$where: bilinmeyen tür '$type'";
                    continue;
                }
                if (!is_string($def) || trim($def) === '') {
                    $problems[] = "$where: varsayılan metin boş";
                    continue;
                }
                if (isset($it[3]) && !is_string($it[3])) {
                    $problems[] = "$where: yardım metni dize olmalı (4. eleman); seçenekler 5. elemandadır";
                }
                if (isset($it[4]) && !is_array($it[4])) {
                    $problems[] = "$where: seçenekler (5. eleman) dizi olmalı";
                }
                // Varsayılan, kendi kurallarına uymalı (yer tutucu tanımlı, işaretler eşleşiyor, sınırlar yeterli)
                $probe = $def . "\u{200B}";   // varsayılanla aynı sayılıp atlanmasın
                $r = texts_clean($k, $probe);
                if ($r['error'] !== null) {
                    $problems[] = "$where: varsayılan metin kendi kurallarına uymuyor: " . $r['error'];
                }
                foreach ((array) (($it[4]['vars'] ?? [])) as $vk => $vv) {
                    if (is_int($vk) && !isset(text_globals()[$vv])) {
                        $problems[] = "$where: genel yer tutucu '$vv' app/data/text-vars.php içinde yok";
                    }
                    if (!is_int($vk) && isset(text_globals()[$vk])) {
                        $problems[] = "$where: yerel yer tutucu '$vk' genel bir yer tutucuyla aynı adı taşıyor; başka bir ad seçin";
                    }
                }
                foreach ((array) ($it[4]['need'] ?? []) as $nd) {
                    if (!array_key_exists($nd, texts_opts($k)['vars'])) {
                        $problems[] = "$where: 'need' içindeki '$nd' 'vars' içinde tanıtılmamış";
                    }
                }
                foreach ((array) ($it[4] ?? []) as $ok => $_) {
                    if (!in_array($ok, ['max', 'lines', 'vars', 'need', 'nowidow', 'js'], true)) {
                        $problems[] = "$where: bilinmeyen seçenek '$ok'";
                    }
                }
            }
        }
    }
    return $problems;
}
