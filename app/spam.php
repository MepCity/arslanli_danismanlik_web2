<?php
declare(strict_types=1);

/**
 * İstenmeyen gönderim (spam) süzgeci: puanlama, JavaScript kanıtı, tek kullanımlık belirteç ve karantina.
 * Hiçbir gönderim sessizce silinmez: puanı SPAM_LIMIT ve üzerinde olan kayıt "spam" anahtarıyla saklanır,
 * bildirim e-postası gönderilmez ve panelde "Şüpheli" altında SPAM_DAYS gün bekler (bkz. app/form.php).
 * Şüpheli kayıtlar sınırlıdır (toplu gönderimle dosya şişirilemesin diye): serbest metinleri SPAM_TEXT_MAX karaktere
 * kısaltılarak saklanır ve yalnızca en yeni SPAM_MAX tanesi tutulur.
 *
 * Kayıt dosyası (storage/submissions.jsonl) tek bir kilit düzeniyle kullanılır: okuyanlar paylaşımlı kilitle (spam_records),
 * yeniden yazanlar ayrıcalıklı kilitle (spam_rewrite), yeni kayıt ekleyenler aynı dosyanın kilidiyle (form.php) çalışır.
 * Böylece yeniden yazma sırasında kimse yarım dosya görmez ve o arada gelen kayıt kaybolmaz.
 */

const SPAM_LIMIT    = 6;      // bu puan ve üzeri şüpheli sayılır
const SPAM_DAYS     = 30;     // şüpheli kayıt bu kadar gün sonra kendiliğinden silinir
const SPAM_MAX      = 300;    // en fazla bu kadar şüpheli kayıt tutulur; fazlası (en eskiler) silinir
const SPAM_ROWS_MAX = 10000;  // kayıt dosyasında (her türden) en fazla bu kadar kayıt; aşılınca en eskiler düşer
const SPAM_FILE_MAX = 6291456; // kayıt dosyasının en fazla bayt boyutu (6 MB); aşılınca en eskiler düşer. Dosya her okumada belleğe alındığı için bellek sınırını aşmasın diye
const SPAM_TEXT_MAX = 1000;   // şüpheli kaydın serbest metin alanları bu kadar karakterle saklanır

/** Formun alanları: ad parçaları, firma (ya da sektör), telefon ve serbest metin alanları. */
function spam_fields(string $type): array
{
    return [
        'iletisim'   => ['name' => ['namesurname'], 'org' => null, 'phone' => 'phone', 'text' => ['konu', 'message']],
        'bulten'     => ['name' => ['ad', 'soyad'], 'org' => 'sektor', 'phone' => 'telefon', 'text' => ['sektor', 'mesaj']],
        'kariyer'    => ['name' => ['ad', 'soyad'], 'org' => null, 'phone' => 'telefon', 'text' => ['pozisyon', 'mesaj']],
        'haberdarol' => ['name' => ['isimsoyisim', 'isimsoyisim2'], 'org' => 'unvan', 'phone' => 'telefon', 'text' => ['unvan', 'mesaj']],
    ][$type] ?? ['name' => [], 'org' => null, 'phone' => null, 'text' => []];
}

/** Kaydın serbest metni (alanlar boşlukla birleştirilir). */
function spam_text(string $type, array $data): string
{
    $out = [];
    foreach (spam_fields($type)['text'] as $k) {
        // İlana başvuruda pozisyon ilanın başlığıdır (sunucu yazar, aday değil): aynı ilana başvuranların metni birbirine benzemesin
        if ($k === 'pozisyon' && !empty($data['ilan'])) {
            continue;
        }
        $out[] = (string) ($data[$k] ?? '');
    }
    return trim(implode(' ', $out));
}

/**
 * Şüpheli kaydın saklanacak hali: serbest metin alanları SPAM_TEXT_MAX karaktere kısaltılır, kısaltılan metnin sonuna "…" konur.
 * Zaten kısaltılmış kayda yeniden uygulanınca sonuç değişmez (yinelenen metin karşılaştırması buna dayanır).
 */
function spam_trim(string $type, array $data): array
{
    foreach (spam_fields($type)['text'] as $k) {
        if (is_string($data[$k] ?? null) && mb_strlen($data[$k]) > SPAM_TEXT_MAX) {
            $data[$k] = mb_substr($data[$k], 0, SPAM_TEXT_MAX) . '…';
        }
    }
    return $data;
}

/** Yinelenen metin karşılaştırması için sadeleştirme: küçük harf, rakamlar atılır, boşluklar teke iner. */
function spam_norm(string $text): string
{
    return trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/\d+/', '', mb_strtolower($text))));
}

/**
 * Metindeki bağlantılar ve çıplak alan adları: "https://...", "www....", "ornek.tld/..." ve "ornek.com".
 * Tekilleştirilmiş alan adı listesi döner (baştaki "www." atılır). E-posta adresleri bağlantı sayılmaz.
 * Çıplak alan adında yalnızca yaygın uzantılara bakılır; nokta sonrası boşluk unutulan Türkçe cümleler
 * ("merhaba.biz bir firmayız", "istiyorum.tesekkurler") bağlantı sanılmaz.
 */
function spam_hosts(string $text): array
{
    $text = (string) preg_replace('/[\w.+\-]+@[\w\-]+(?:\.[\w\-]+)+/u', ' ', mb_strtolower($text));
    $tld  = 'com|net|org|info|io|ai|app|xyz|online|shop|store|top|club|link|click|live|tech|agency|digital|ru|cn|tr|uk|eu';
    $host = '[a-z0-9][a-z0-9\-]*(?:\.[a-z0-9\-]+)*\.[a-z]{2,}';
    preg_match_all('~https?://([^\s/?#"\'<>]+)|\bwww\.(' . $host . ')|\b(' . $host . ')/|\b((?:[a-z0-9][a-z0-9\-]*\.)+(?:' . $tld . '))\b~', $text, $m, PREG_SET_ORDER);
    $out = [];
    foreach ($m as $x) {
        $h = (string) preg_replace('/^www\./', '', rtrim((string) end($x), '.,;:!)'));
        $out[$h] = true;
    }
    return array_keys($out);
}

/** $host, $domain ile aynı mı ya da onun alt alan adı mı? */
function spam_same_host(string $host, string $domain): bool
{
    return $domain !== '' && ($host === $domain || str_ends_with($host, '.' . $domain) || str_ends_with($domain, '.' . $host));
}

/**
 * Ad parçası rastgele harf dizisine benziyor mu? (bot kayıtları: "hnwqmetrzf poykjmkdmr")
 * Yalnızca 8 ve daha çok harfli parçalara bakılır. Türkçe harfler sadeleştirilir; "sch", "str", "ch", "sh", "th", "ck", "tz"
 * gibi yaygın harf öbekleri ve çift ünsüzler tek ünsüz sayılır. Böylece Kırkpınar, Hacıbektaşoğlu, Schwarzkopf, Armstrong
 * gibi adlar takılmaz. Şunlardan biri varsa rastgele sayılır:
 *  - arka arkaya 4 ya da daha çok ünsüz,
 *  - adların başında ya da sonunda görülmeyen bir ünsüz çifti ("hn...", "...zf").
 */
function spam_gibberish(string $token): bool
{
    $t = strtr(mb_strtolower(strtr($token, ['İ' => 'i', 'I' => 'i'])), ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u', 'ä' => 'a', 'é' => 'e', 'ß' => 'ss']);
    if (!preg_match('/^[a-z]{8,}$/', $t)) {
        return false;
    }
    // "y" yanında ünlü yoksa ünlü gibi okunur (Kowalczyk, Krzysztof); varsa ünsüzdür (Yılmaz, Kaya)
    $c = (string) preg_replace('/(?<![aeiou])y(?![aeiou])/', 'i', $t);
    $c = (string) preg_replace(['/^mc|sch[lmnrw]?|str|spr|scr/', '/ch|sh|th|ph|gh|kh|zh|ck|tz|ts|cz|sz|rz|ng|pf/', '/([bcdfgklmnprstz])\1/'], ['s', 'c', '$1'], $c);
    if (preg_match('/[^aeiou]{4}/', $c)) {
        return true;
    }
    $onset = ' bh bj bl br ch cl cr cv cz dj dr dw dz fj fl fr gh gj gk gl gn gr gv gw hj hr kh kj kl kn kr ks kv kw lj ll mb mc mg mk ml mp nd ng nj nk nt pf ph pl pr ps rh rr sc sh sj sk sl sm sn sp sr st sv sw sz tc th tj tk tr ts tw tz vl vr vs wh wl wr xh zb zd zh zl zv zw ';
    $coda  = ' bs ch ck cs ct cz dr ds dt ff fs ft gg gh gs hl hm hn hr hs ht kh ks kt lc ld lf lk ll lm lp ls lt lz mb mm mp ms nc nd ng nk nn ns nt nz pf ph pp ps pt rb rc rd rf rg rk rl rm rn rp rr rs rt rv rx rz sh sk sp ss st sz th tr ts tt tz wn ws zt ';
    return (preg_match('/^[^aeiouy]{2}/', $t, $m) && !str_contains($onset, ' ' . $m[0] . ' '))
        || (preg_match('/[^aeiouy]{2}$/', $t, $m) && !str_contains($coda, ' ' . $m[0] . ' '));
}

/**
 * Gönderimi puanlar. Dönen değer: ['score' => int, 'reasons' => [kısa açıklamalar]]; puan SPAM_LIMIT ve üzerindeyse şüphelidir.
 * $ctx: 'age' (belirtecin yaşı, saniye), 'proof' (JavaScript kanıtı geçerli mi), 'ua' (User-Agent), 'lang' (Accept-Language),
 *       'fetch' (Sec-Fetch-Site; yoksa null), 'records' (yinelenenleri bulmak için önceki kayıtlar: [['form', 'time', 'data'], ...]),
 *       'honeypot' (insanların görmediği gizli alan doldurulmuş mu).
 */
function spam_score(string $type, array $data, array $ctx = []): array
{
    $ctx += ['age' => null, 'proof' => false, 'ua' => '', 'lang' => '', 'fetch' => null, 'records' => [], 'honeypot' => false];
    $f     = spam_fields($type);
    $score = 0;
    $why   = [];
    $add   = function (int $n, string $reason) use (&$score, &$why): void {
        $score += $n;
        $why[]  = $reason;
    };

    /* ---------- İstek: tarayıcıdan mı geliyor? ---------- */
    // Bal küpü: gizli alanı botlar doldurur. Tek başına şüpheli saymaya yeter; kayıt atılmaz, panelde "Şüpheli" altında bekler
    // (tarayıcının otomatik doldurması yüzünden gerçek bir ziyaretçi de buraya düşebilir).
    if ($ctx['honeypot']) {
        $add(SPAM_LIMIT, 'Gizli alan dolduruldu');
    }
    if (!$ctx['proof']) {
        $add(3, 'JavaScript kanıtı yok');
    }
    if ($ctx['age'] !== null && $ctx['age'] < 5) {
        $add(2, 'Form çok hızlı gönderildi (' . (int) $ctx['age'] . ' sn)');
    }
    $ua = trim((string) $ctx['ua']);
    if (!preg_match('~^(?:Mozilla|Opera)/~', $ua) || preg_match('~bot[/\-]|\bbot\b|spider|crawl|headless|phantom|selenium|puppeteer|playwright|python|curl|wget|scrapy|httpclient|java/~i', $ua)) {
        $add(3, $ua === '' ? 'Tarayıcı bilgisi boş' : 'Tarayıcı bilgisi bot yazılımına benziyor');
    }
    if (trim((string) $ctx['lang']) === '') {
        $add(2, 'Tarayıcı dil bilgisi göndermedi');
    }
    if (is_string($ctx['fetch']) && $ctx['fetch'] !== '' && strtolower($ctx['fetch']) !== 'same-origin') {
        $add(3, 'Form başka bir siteden gönderildi');
    }

    /* ---------- Serbest metin ---------- */
    $text  = spam_text($type, $data);
    $lower = mb_strtolower($text);
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $mailDomain = (string) substr((string) strrchr($email, '@'), 1);
    $own   = (string) preg_replace('/^www\./', '', strtolower((string) parse_url((string) cfg('url'), PHP_URL_HOST)));

    // Bağlantılar: gönderenin kendi e-posta alan adı sayılmaz; sitenin kendi alan adı aşağıda ayrıca puanlanır
    $hosts = array_filter(spam_hosts($text), fn($h) => !spam_same_host($h, $mailDomain) && !spam_same_host($h, $own));
    if ($hosts) {
        $add(min(5, 2 + count($hosts)), count($hosts) > 1 ? 'Metinde ' . count($hosts) . ' bağlantı var' : 'Metinde bağlantı var');
    }
    $nameRaw = trim(implode(' ', array_map(fn($k) => (string) ($data[$k] ?? ''), $f['name'])));
    if (spam_hosts($nameRaw) || preg_match('~https?://~i', $nameRaw)) {
        $add(4, 'Ad alanında bağlantı var');
    }

    // Reklam dili: her grup bir kez sayılır
    $vocab = [
        'SEO'               => '~\bseo\b|search engine|google rank|\brank(?:ing|ings|ed|s)?\b|first page|back ?links?\b|link (?:exchange|building)|guest post|\btraffic\b|\bvisibility\b|domain authority~u',
        'dizin kaydı'       => '~\bdirector(?:y|ies)\b|\bclassifieds?\b|business listings?~u',
        'veri satışı'       => '~\bdatabases?\b|\bleads\b|lead generation|\bb2b (?:data|leads|contacts?|lists?|e-?mails?)|e-?mail (?:list|database)|mailing list|contact list~u',
        'yazılım satışı'    => '~web ?(?:site )?(?:design|develop)|website redesign|app develop|mobile apps?\b|hire (?:\w+ ){0,3}(?:developers?|programmers?|designers?)|ai developers?|software develop|\bwordpress\b~u',
        'kumar/kredi'       => '~\bcrypto|bitcoin|\bcasino|\bbetting\b|\bloans?\b|\bforex\b|investment (?:profit|return|opportunit|plan)|guaranteed (?:profit|return)|passive income~u',
        'yetişkin/ilaç'     => '~\bviagra|\bcialis|\bporn|\bsex(?:y|ual)?\b|\badult\b|\bescort|\bpharmac|\bpills?\b~u',
        'toplu ileti kalıbı' => '~unsubscribe|no strings attached|free tool|price list|opt[ \-]out~u',
    ];
    $groups = array_keys(array_filter($vocab, fn($re) => (bool) preg_match($re, $lower)));
    if ($groups) {
        $add(min(6, 3 * count($groups)), 'Reklam dili: ' . implode(', ', $groups));
    }

    // Dil: İngilizce metin ya da yabancı alfabe (site Türkiye'deki işletmelere hitap eder)
    $letters = (int) preg_match_all('/\p{L}/u', $text);
    $foreign = (int) preg_match_all('/[\p{Cyrillic}\p{Arabic}\p{Han}\p{Hiragana}\p{Katakana}\p{Hangul}]/u', $text);
    if ($letters >= 8 && $foreign * 2 > $letters) {
        $add(5, 'Metin yabancı alfabeyle yazılmış');
    } elseif (mb_strlen($text) >= 40 && !preg_match('/[çğıöşüÇĞİÖŞÜ]/u', $text)) {
        $words = preg_split('/[^\p{L}\']+/u', (string) preg_replace('~(?:https?://|www\.)\S+~', ' ', $lower), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        // Türkçe harfsiz yazılan mesajlarda da geçen "is" (iş), "on" (ön, on), "an", "her", "no", "not", "can" bilerek listede yok
        $stop  = ' a i the and of to in for with at as by it be are was were am we you your our us me my he she they their them that this these those will would could should'
            . ' have has had do does did from or if but so what which who how when where why there here about more most some any all also just only than then into out up'
            . ' now very too own each both other such same few after before over under again because while through between during until'
            . ' please thanks thank hello hi dear regards may must get been being its ';
        $hits  = count(array_filter($words, fn($w) => str_contains($stop, ' ' . $w . ' ')));
        if ($words && $hits / count($words) >= 0.25) {
            $add(3, 'Metin İngilizce');
        }
    }
    if ($own !== '' && str_contains($lower, $own)) {
        $add(2, 'Metinde sitenin kendi alan adı geçiyor');
    }

    /* ---------- Ad, firma, telefon, e-posta ---------- */
    $tokens = fn(string $s): array => preg_split('/[^\p{L}]+/u', mb_strtolower(strtr($s, ['İ' => 'i'])), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $name   = $tokens($nameRaw);
    $random = count(array_filter($name, 'spam_gibberish'));
    if ($random >= 2) {
        $add(4, 'Ad ve soyad rastgele harflerden oluşuyor');
    } elseif ($random === 1) {
        $add(2, 'Adın bir parçası rastgele harflere benziyor');
    }
    $test = ['testuser', 'myname', 'hello', 'test', 'asdf', 'qwerty'];
    if (array_intersect($name, $test) || in_array(implode(' ', $name), ['john alice', 'alice john', 'john doe'], true) || (count($name) > 1 && count(array_unique($name)) === 1)) {
        $add(3, 'Ad bir deneme adı');
    }
    $org = $f['org'] !== null ? $tokens((string) ($data[$f['org']] ?? '')) : [];
    if ($org && ($org === $name || (count($org) === 1 && in_array($org[0], $name, true)) || array_intersect($org, $test) || array_filter($org, 'spam_gibberish'))) {
        $add(2, $f['org'] === 'sektor' ? 'Sektör alanı adla aynı ya da rastgele harfler' : 'Firma adı adla aynı ya da rastgele harfler');
    }

    $digits = (string) preg_replace('/\D+/', '', (string) ($f['phone'] !== null ? ($data[$f['phone']] ?? '') : ''));
    // Türkiye numarası: isteğe bağlı 0090 / 90 / 0 önekinden sonra 2, 3, 4, 5 ya da 8 ile başlayan on hane; ya da yedi haneli 444 hattı
    if ($digits !== '' && !preg_match('/^(?:0090|90|0)?[2-58]\d{9}$|^444\d{4}$/D', $digits)) {
        $add(in_array($type, ['bulten', 'haberdarol'], true) ? 3 : 1, 'Telefon Türkiye numarasına benzemiyor');
    }

    $disposable = ['mailinator.com', 'guerrillamail.com', 'guerrillamail.net', 'sharklasers.com', 'grr.la', '10minutemail.com', '10minutemail.net', 'tempmail.com', 'temp-mail.org', 'temp-mail.io',
        'tempmail.net', 'tempail.com', 'yopmail.com', 'trashmail.com', 'throwawaymail.com', 'getnada.com', 'maildrop.cc', 'dispostable.com', 'fakeinbox.com', 'mailnesia.com',
        'emailondeck.com', 'moakt.com', 'mohmal.com', 'mintemail.com', 'discard.email', 'spam4.me', 'mailcatch.com', 'getairmail.com', 'mytemp.email', 'burnermail.io'];
    if (array_filter($disposable, fn($d) => spam_same_host($mailDomain, $d) && strlen($mailDomain) >= strlen($d))) {
        $add(4, 'Geçici (tek kullanımlık) e-posta adresi');
    }

    /* ---------- Yinelenenler ---------- */
    // Şüpheli kayıtların metni kısaltılarak saklandığı için karşılaştırma iki tarafta da kısaltılmış metin üzerinden yapılır
    $norm = mb_strlen($text) >= 40 ? spam_norm(spam_text($type, spam_trim($type, $data))) : '';
    $now  = time();
    $same = false;
    $mails = 0;
    foreach ((array) $ctx['records'] as $r) {
        $t = strtotime((string) ($r['time'] ?? '')) ?: 0;
        $d = (array) ($r['data'] ?? []);
        $rf = (string) ($r['form'] ?? '');
        if ($email !== '' && $t > $now - 86400 && strtolower(trim((string) ($d['email'] ?? ''))) === $email) {
            $mails++;
        }
        if (!$same && $norm !== '' && $t > $now - 30 * 86400 && spam_norm(spam_text($rf, spam_trim($rf, $d))) === $norm) {
            $same = true;
        }
    }
    if ($same) {
        $add(5, 'Aynı metin son 30 günde başka bir kayıtta da var');
    }
    if ($mails >= 3) {
        $add(3, 'Aynı e-posta adresi son 24 saatte ' . $mails . ' kez kullanıldı');
    }

    return ['score' => $score, 'reasons' => $why];
}

/* ---------- JavaScript kanıtı (bkz. assets/js/app.js) ---------- */

/**
 * Kanıt: fnv1a32(belirteç . ':' . sayı) değerinin son 16 biti sıfır olmalıdır.
 * PHP'nin kendi "fnv1a32" özeti kullanılır (standart 32 bit FNV-1a; 32 bit taşması sorun olmaz). Son 4 onaltılık hane = son 16 bit.
 * Eksik ya da yanlış kanıt gönderimi engellemez; yalnızca puana eklenir (JavaScript'siz gönderim çalışmaya devam eder).
 */
function spam_proof_ok(?string $token, $proof): bool
{
    return is_string($token) && is_string($proof) && preg_match('/^\d{1,8}$/D', $proof)
        && substr(hash('fnv1a32', $token . ':' . (int) $proof), -4) === '0000';
}

/* ---------- Tek kullanımlık belirteç ---------- */

/**
 * Belirteç daha önce kullanıldı mı? Kullanılan belirteçlerin özeti storage/form-tokens.json dosyasında tutulur
 * (özet → kullanıldığı an); belirteç ömrünü (2 saat) aşan kayıtlar her yazışta atılır.
 * $mark = true: kullanılmamışsa aynı kilit altında kullanıldı diye işaretler (aynı anda gelen iki istekten yalnızca biri geçer).
 * $mark = false: işareti kaldırır (gönderim sonradan başarısız olduysa ziyaretçi aynı belirteçle yeniden deneyebilsin).
 */
function spam_token_used(string $token, ?bool $mark = null): bool
{
    $fp = @fopen(ROOT . '/storage/form-tokens.json', 'c+');
    if (!$fp) {
        return false;
    }
    try {
        flock($fp, $mark === null ? LOCK_SH : LOCK_EX);
        $all  = json_decode((string) stream_get_contents($fp), true);
        $all  = is_array($all) ? $all : [];
        $key  = substr(hash('sha256', $token), 0, 32);
        $used = isset($all[$key]);
        if ($mark === null || $mark === $used) {
            return $used;
        }
        $now = time();
        $all = array_filter($all, fn($t) => is_int($t) && $t > $now - 7200);
        if ($mark) {
            $all[$key] = $now;
        } else {
            unset($all[$key]);
        }
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) json_encode($all));
        return $used;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/* ---------- Kayıtlar ve karantina ---------- */

/**
 * Form gönderimleri (storage/submissions.jsonl), en yeni sonda. Dosya paylaşımlı kilitle okunur: o sırada yeniden yazılıyorsa
 * (kayıt silme, "Spam değil", temizlik) yazma bitene kadar beklenir, yarım dosya hiçbir zaman okunmaz.
 * Panel (adm_records) ve bülten aboneleri (bulten_aboneler) de dosyayı bununla okur.
 */
function spam_records(): array
{
    $file = ROOT . '/storage/submissions.jsonl';
    $fp   = is_file($file) ? @fopen($file, 'r') : false;
    if (!$fp) {
        return [];
    }
    $rows = [];
    try {
        flock($fp, LOCK_SH);
        while (($line = fgets($fp)) !== false) {
            $r = json_decode($line, true);
            if (is_array($r)) {
                $rows[] = $r;
            }
        }
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    return $rows;
}

/**
 * Kayıt dosyasının yalnızca son $bytes baytındaki kayıtlar (en yeni sonda). Gönderim yolu bunu kullanır: yinelenen metin ve e-posta denetimi
 * için son kayıtlar yeter, dosyanın tamamı her gönderimde belleğe alınmaz. Baştaki yarım satır atılır.
 */
function spam_records_tail(int $bytes = 1048576): array
{
    $file = ROOT . '/storage/submissions.jsonl';
    $fp   = is_file($file) ? @fopen($file, 'r') : false;
    if (!$fp) {
        return [];
    }
    $rows = [];
    try {
        flock($fp, LOCK_SH);
        $size = (int) (fstat($fp)['size'] ?? 0);
        if ($size > $bytes) {
            fseek($fp, $size - $bytes);
            fgets($fp);   // kesilmiş ilk satır
        }
        while (($line = fgets($fp)) !== false) {
            $r = json_decode($line, true);
            if (is_array($r)) {
                $rows[] = $r;
            }
        }
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
    return $rows;
}

/**
 * Kayıt dosyası sınırı aşıldıysa (SPAM_FILE_MAX bayt ya da SPAM_ROWS_MAX kayıt) en eski kayıtları, sınırın yüzde 90'ına inene kadar düşürür:
 * önce en eski şüpheli kayıtlar, yetmezse en eski kayıtlar (özgeçmiş dosyalarıyla). Boyut denetimi dosyayı okumaz; yalnızca aşıldıysa dosya yeniden yazılır.
 * Kayıt sayısı sınırı da bu yeniden yazma sırasında uygulanır: boyut sınırının altında kalan ama çok kısa kayıtlarla dolmuş dosya, boyut sınırına varana dek tutulur.
 * @return int düşürülen kayıt sayısı
 */
function spam_limit_file(): int
{
    $file = ROOT . '/storage/submissions.jsonl';
    clearstatcache(true, $file);
    $size = is_file($file) ? @filesize($file) : false;
    if ($size === false || $size <= SPAM_FILE_MAX) {
        return 0;
    }
    $drop = [];   // düşecek kayıtların sırası
    $i = 0;
    $gone = [];
    spam_rewrite(function (array $r) use (&$drop, &$i, &$gone): ?array {
        if (isset($drop[$i++])) {
            spam_cv_delete($r);
            $gone[] = $r;
            return null;
        }
        return $r;
    }, function (array $rows) use (&$drop): void {
        $len = array_map(fn($r) => strlen((string) json_encode($r, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)) + 1, $rows);
        $bytes = array_sum($len);
        $count = count($rows);
        $maxB  = (int) (SPAM_FILE_MAX * 0.9);
        $maxN  = (int) (SPAM_ROWS_MAX * 0.9);
        foreach ([true, false] as $onlySuspect) {   // önce şüpheliler, sonra (gerekirse) en eskiler
            foreach ($rows as $k => $r) {
                if ($bytes <= $maxB && $count <= $maxN) {
                    break 2;
                }
                if (isset($drop[$k]) || ($onlySuspect && empty($r['spam']))) {
                    continue;
                }
                $drop[$k] = true;
                $bytes -= $len[$k];
                $count--;
            }
        }
    });
    spam_forget_subscribers($gone);
    return count($gone);
}

/**
 * Silinen kayıtlar arasında bülten kaydı varsa, o adreslerin bülten deposundaki izlerini (ad, adres) temizler: kaydı kalmayan kişinin adı ve adresi
 * bir yerde durmasın (bkz. bulten_adres_unut: adresin başka kaydı kalmışsa dokunmaz, ayrılma tercihi anahtarlı özet olarak korunur).
 * Kayıt dosyası kilidi dışında çağrılmalıdır.
 */
function spam_forget_subscribers(array $gone): void
{
    $emails = [];
    foreach ($gone as $r) {
        $d = is_array($r['data'] ?? null) ? $r['data'] : [];
        if (in_array($r['form'] ?? '', ['bulten', 'haberdarol'], true) && is_scalar($d['email'] ?? null) && trim((string) $d['email']) !== '') {
            $emails[strtolower(trim((string) $d['email']))] = true;
        }
    }
    if (!$emails) {
        return;
    }
    require_once APP . '/bulten.php';
    foreach (array_keys($emails) as $e) {
        bulten_adres_unut($e);
    }
}

/**
 * Kayıt dosyasını ayrıcalıklı kilit altında yeniden yazar. $fn her kayıt için çağrılır: kaydı (değiştirilmiş olabilir) döndürür, silinecekse null.
 * $once verilirse $fn çağrılmadan önce, aynı kilit altında, dosyadaki tüm kayıtlarla bir kez çağrılır (ör. kaç şüpheli kayıt olduğunu saymak için).
 * Okuma, karar ve yazma tek kilit altındadır: yeni gönderimler aynı dosyaya aynı kilitle eklendiği için yeniden yazma sırasında gelen kayıt kaybolmaz.
 * $fn ve $once içinden kayıt dosyası yeniden okunmamalıdır (aynı istek kendi kilidini beklerdi).
 * @return bool dosya yeniden yazıldıysa true
 */
function spam_rewrite(callable $fn, ?callable $once = null): bool
{
    $file = ROOT . '/storage/submissions.jsonl';
    $fp   = is_file($file) ? @fopen($file, 'r+') : false;
    if (!$fp) {
        return false;
    }
    try {
        flock($fp, LOCK_EX);
        $rows = [];
        while (($line = fgets($fp)) !== false) {
            $r = json_decode($line, true);
            if (is_array($r)) {
                $rows[] = $r;
            }
        }
        if ($once !== null) {
            $once($rows);
        }
        $out = '';
        foreach ($rows as $r) {
            $r = $fn($r);
            $json = $r !== null ? json_encode($r, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : false;
            if ($json !== false) {
                $out .= $json . "\n";
            }
        }
        ftruncate($fp, 0);
        rewind($fp);
        $ok = fwrite($fp, $out) === strlen($out);
        fflush($fp);
        return $ok;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/** Kaydın özgeçmiş dosyasını siler (varsa). */
function spam_cv_delete(array $r): void
{
    $cv = (string) ($r['data']['cv'] ?? '');
    if ($cv !== '' && preg_match('#^[\w.\-]+$#', $cv)) {
        @unlink(ROOT . '/storage/cv/' . $cv);
    }
}

/**
 * Şüpheli kayıtları sınırlar: SPAM_DAYS günden eski olanlar ve en yeni SPAM_MAX kaydın dışında kalanlar (en eskiler)
 * özgeçmiş dosyalarıyla birlikte silinir. Silinecek kayıt yoksa dosyaya dokunmaz. Silinen kayıt sayısı döner.
 */
function spam_purge(): int
{
    $limited = spam_limit_file();   // toplam boyut ve kayıt sayısı sınırı (süre sınırından bağımsız)
    $min = time() - SPAM_DAYS * 86400;
    $old = function (array $r) use ($min): bool {
        $t = strtotime((string) ($r['time'] ?? ''));
        return $t !== false && $t < $min;
    };
    $sus = array_filter(spam_records(), fn($r) => !empty($r['spam']));
    if (count($sus) <= SPAM_MAX && !array_filter($sus, $old)) {
        return $limited;
    }
    $n = 0;
    $gone = [];
    $extra = 0;   // sınırı aşan şüpheli kayıt sayısı: dosyanın başındaki (en eski) bu kadar şüpheli kayıt silinir
    $seen = 0;
    spam_rewrite(function (array $r) use ($old, &$n, &$extra, &$seen, &$gone): ?array {
        if (empty($r['spam'])) {
            return $r;
        }
        $seen++;
        if ($seen > $extra && !$old($r)) {
            return $r;
        }
        spam_cv_delete($r);
        $gone[] = $r;
        $n++;
        return null;
    }, function (array $rows) use (&$extra): void {
        // Sayım yazmayla aynı kilit altında yapılır: aynı anda çalışan iki temizlik gereğinden fazla kayıt silmez
        $extra = max(0, count(array_filter($rows, fn($r) => !empty($r['spam']))) - SPAM_MAX);
    });
    spam_forget_subscribers($gone);   // kaydı kalmayan bülten adreslerinin ad ve adresi bülten deposundan da silinir
    return $n + $limited;
}
