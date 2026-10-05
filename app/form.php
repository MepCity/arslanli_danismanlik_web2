<?php
declare(strict_types=1);

/**
 * İletişim, bülten (Haberdar Ol kuponu dahil) ve iş başvurusu (kariyer) formlarının ortak işleyicisi.
 * JSON döndürür; JavaScript kapalıysa kullanıcıyı sayfaya geri yönlendirir.
 * İstenmeyen gönderimler app/spam.php ile puanlanır; şüpheli olanlar reddedilmez, e-posta gönderilmeden saklanır.
 */

require_once APP . '/spam.php';
require_once APP . '/mailer.php';   // mail_address_ok(): e-posta alanının sıkı denetimi; send_mail(): bildirim e-postası

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

/** $exit false ise yanıt yazılır ama istek sürer (yanıttan sonra bildirim e-postası gönderilirken kullanılır). */
function respond(bool $ok, string $message, array $errors = [], int $status = 200, bool $exit = true): void
{
    global $wantsJson;
    if ($wantsJson) {
        http_response_code($ok ? 200 : $status);
        header('Content-Type: application/json; charset=utf-8');
        // Belirteç tek kullanımlıktır: başarılı gönderimin yanıtında yenisi verilir, ziyaretçi sayfayı yenilemeden bir kez daha gönderebilir.
        // Belirteci eskimiş ya da kullanılmış gönderimin (419) yanıtında da yenisi verilir: tarayıcı formdaki belirteci değiştirir,
        // ziyaretçi yazdıklarını kaybetmeden yeniden gönderir (bkz. assets/js/app.js).
        echo json_encode(['ok' => $ok, 'message' => $message, 'errors' => (object) $errors] + ($ok || $status === 419 ? ['token' => form_token()] : []), JSON_UNESCAPED_UNICODE);
        if ($exit) {
            exit;
        }
        return;
    }
    // Yalnızca site içi yol kabul edilir: tek bir / ile başlar, ardından / ya da \ gelmez, denetim karakteri içermez
    $back = $_POST['_back'] ?? '';
    if (!is_string($back) || !str_starts_with($back, base_path() . '/') || preg_match('#^/[/\\\\]|[\x00-\x1F\x7F]#', $back)) {
        $back = url('iletisim');
    }
    header('Location: ' . $back . '?durum=' . ($ok ? 'tamam' : 'hata'), true, 303);
    if ($exit) {
        exit;
    }
}

/** Hız sınırı anahtarı: IPv4 adresi olduğu gibi, IPv6 adresi /64 önekiyle (tek bir abone çok sayıda adres kullanabilir). */
function form_rate_key(string $ip): string
{
    $bin = @inet_pton($ip);
    if ($bin === false || strlen($bin) !== 16) {
        return $ip;
    }
    if (str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")) {
        return (string) inet_ntop(substr($bin, 12));   // IPv4 eşlemeli adres (::ffff:1.2.3.4)
    }
    return bin2hex(substr($bin, 0, 8));
}

/**
 * Hız sınırı: aynı IP'den 10 dakikada en fazla 5 gönderim; tüm sitede saatte en fazla 120 gönderim ve 30 özgeçmiş.
 * Şüpheli bulunan gönderimler site geneli 120 sınırına sayılmaz (bkz. form_rate_site_undo); IP ve özgeçmiş sınırına sayılır.
 * Denetim ve kayıt tek bir kilit altında yapılır; aynı anda gelen istekler sınırı birlikte aşamaz.
 * Sınır dolmuşsa hangisinin dolduğu döner ('ip', 'site', 'cv'); dolmamışsa null döner ve $record verilmişse gönderim kaydedilir.
 */
function form_rate(string $ip, bool $cv, bool $record): ?string
{
    $dir  = ROOT . '/storage';
    $lock = @fopen($dir . '/rate.lock', 'c');
    if (!$lock) {
        return null;
    }
    try {
        flock($lock, LOCK_EX);
        $now  = time();
        $read = function (string $f): array {
            $d = is_file($f) ? json_decode((string) @file_get_contents($f), true) : null;
            return is_array($d) ? $d : [];
        };
        $keep = fn($list, int $age): array => array_values(array_filter((array) $list, fn($t) => is_int($t) && $t > $now - $age));
        // Eski hız sınırı dosyaları ara sıra temizlenir (yaklaşık 50 istekte bir)
        if (mt_rand(1, 50) === 1) {
            foreach (glob($dir . '/rate-*.json') ?: [] as $f) {
                $m = @filemtime($f);
                if ($m !== false && $m < $now - 86400) {
                    @unlink($f);
                }
            }
        }
        $ipFile   = $dir . '/rate-' . md5(form_rate_key($ip)) . '.json';
        $siteFile = $dir . '/rate-site.json';
        $hits = $keep($read($ipFile), 600);
        $site = $read($siteFile);
        $all  = $keep($site['all'] ?? [], 3600);
        $cvs  = $keep($site['cv'] ?? [], 3600);
        if (count($hits) >= 5) {
            return 'ip';
        }
        if (count($all) >= 120) {
            return 'site';
        }
        if ($cv && count($cvs) >= 30) {
            return 'cv';
        }
        if ($record) {
            $hits[] = $now;
            $all[]  = $now;
            if ($cv) {
                $cvs[] = $now;
            }
            @file_put_contents($ipFile, json_encode($hits));
            @file_put_contents($siteFile, json_encode(['all' => $all, 'cv' => $cvs]));
        }
        return null;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * Şüpheli bulunan gönderimi site geneli saatlik sınırdan düşer; IP sınırına ve özgeçmiş sınırına sayılmaya devam eder.
 * Gönderim önce (puanlanmadan) sayıldığı için sınır aynı anda gelen isteklerle aşılamaz; şüpheli çıkarsa sayım burada geri alınır.
 * Böylece tamamı "Şüpheli" altına düşen toplu gönderimler, gerçek ziyaretçilerin formlarını saatlik sınırla kapatamaz.
 */
function form_rate_site_undo(): void
{
    $dir  = ROOT . '/storage';
    $lock = @fopen($dir . '/rate.lock', 'c');
    if (!$lock) {
        return;
    }
    try {
        flock($lock, LOCK_EX);
        $file = $dir . '/rate-site.json';
        $site = is_file($file) ? json_decode((string) @file_get_contents($file), true) : null;
        $all  = is_array($site) ? array_values(array_filter((array) ($site['all'] ?? []), 'is_int')) : [];
        if ($all) {
            sort($all);
            array_pop($all);   // en son eklenen sayım (bu gönderimin kendisi ya da onunla aynı anda gelen bir başkası; sayı aynıdır)
            @file_put_contents($file, json_encode(['all' => $all, 'cv' => array_values((array) ($site['cv'] ?? []))]));
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

/**
 * Bildirim e-postası: şirkete gider, ziyaretçiye gönderilmez. Özgeçmiş dosyası varsa eklenir.
 * $form: aşağıdaki tanımlardan biri; $time: gönderimin yapıldığı an.
 */
function form_notify(array $form, string $type, array $data, string $ip, int $time): bool
{
    $labels = array_map(fn($f) => $f[0], $form['fields']) + ['cv' => 'Özgeçmiş dosyası', 'cv_name' => 'Özgeçmiş (özgün ad)'];
    $body = $form['subject'] . "\n" . str_repeat('-', 40) . "\n";
    foreach ($data as $k => $v) {
        $body .= ($labels[$k] ?? $k) . ': ' . ($v === '' ? '-' : $v) . "\n";
    }
    $body .= str_repeat('-', 40) . "\nTarih: " . date('d.m.Y H:i', $time) . "\nIP: " . $ip . "\n";

    $subject = $form['subject'];
    if ($type === 'kariyer') {
        $subject .= ': ' . ($data['ad'] ?? '') . ' ' . ($data['soyad'] ?? '') . (($data['pozisyon'] ?? '') !== '' ? ' (' . $data['pozisyon'] . ')' : '');
    }
    $attachments = [];
    $cv = (string) ($data['cv'] ?? '');
    if ($cv !== '' && preg_match('#^[\w.\-]+$#', $cv)) {
        $attachments[] = ['path' => ROOT . '/storage/cv/' . $cv, 'name' => (string) ($data['cv_name'] ?? $cv),
            'type' => str_ends_with($cv, '.pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    }
    return send_mail((string) cfg('mail.to'), $subject, $body, is_string($data['email'] ?? null) ? $data['email'] : null, $attachments);
}

$forms = [
    // İletişim sayfasındaki dilekçe
    'iletisim' => [
        'subject' => 'Web sitesi: dilekçe',
        'fields'  => [
            'namesurname' => ['Ad Soyad', 'required|max:120'],
            'firma'       => ['Firma', 'max:160'],
            'konu'        => ['Konu', 'max:120'],
            'message'     => ['Mesaj', 'required|max:5000'],
            'phone'       => ['Telefon', 'phone'],
            'email'       => ['E-posta', 'required|email'],
        ],
    ],
    // Bülten kaydı: her sayfadaki "Bültene kayıt ol" penceresi ve Haberdar Ol kuponu aynı formu gönderir
    'bulten' => [
        'subject' => 'Web sitesi: bülten kaydı',
        'fields'  => [
            'ad'      => ['Ad', 'required|max:80'],
            'soyad'   => ['Soyad', 'required|max:80'],
            'email'   => ['E-posta', 'required|email'],
            'telefon' => ['Telefon', 'required|phone'],
            'il'      => ['İl', 'required|in:iller'],
            'sektor'  => ['Sektör', 'required|in:sektorler'],
            'mesaj'   => ['İlgilendiği konular', 'max:5000'],
            'kvkk'    => ['Aydınlatma metni', 'accepted'],
            'etk'     => ['Ticari elektronik ileti onayı', 'accepted'],
        ],
    ],
    // İş başvurusu (sayfası ayrı; özgeçmiş dosyası $_FILES['cv'] ile gelir)
    'kariyer' => [
        'subject' => 'Web sitesi: iş başvurusu',
        'fields'  => [
            'ad'       => ['Ad', 'required|max:80'],
            'soyad'    => ['Soyad', 'required|max:80'],
            'email'    => ['E-posta', 'required|email'],
            'telefon'  => ['Telefon', 'required|phone'],
            'sehir'    => ['Şehir', 'required|in:sehirler'],
            'linkedin' => ['LinkedIn', 'url|max:200'],
            'pozisyon' => ['İlgilenilen alan / pozisyon', 'max:120'],
            'deneyim'  => ['Deneyim süresi', 'in:deneyim'],
            'mesaj'    => ['Ön yazı', 'max:5000'],
            'kvkk'     => ['Aydınlatma metni', 'accepted'],
            'saklama'  => ['Gelecek pozisyonlar için saklama izni', 'bool'],
        ],
    ],
];

if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    respond(false, 'Gönderilen dosya çok büyük. Lütfen en fazla 5 MB boyutunda bir dosya seçin.', ['cv' => 'Dosya en fazla 5 MB olabilir.'], 413);
}

// Form adı metin değilse (ör. dizi olarak gönderilmişse) geçersiz sayılır. Eski "haberdarol" formu artık yoktur: kupon da "bulten" gönderir.
$type = is_string($_POST['_form'] ?? null) ? $_POST['_form'] : '';
if (!isset($forms[$type])) {
    respond(false, 'Geçersiz form.', [], 400);
}

// Bal küpü: insanlar bu alanı görmez, botlar doldurur. Dolu gelen gönderim atılmaz ve farklı bir yanıt almaz: olağan gönderim gibi
// denetlenir, puanlamada tek başına şüpheli sayılır (bkz. spam_score), e-posta gönderilmeden storage/submissions.jsonl dosyasında saklanır.
$honeypot = !empty($_POST['website']);

// Belirteç geçersizse, süresi dolmuşsa ya da daha önce kullanılmışsa aynı yanıt verilir. Yanıt yeni bir belirteç taşır (bkz. respond):
// tarayıcı onu forma yerleştirir, ziyaretçi sayfayı yenilemeden ve yazdıklarını kaybetmeden yeniden gönderir.
$token   = is_string($_POST['_token'] ?? null) ? $_POST['_token'] : null;
$expired = 'Oturum süresi dolduğu için form yenilendi; yazdıklarınız duruyor. Lütfen birkaç saniye sonra yeniden gönderin.';
if (!verify_form_token($token) || spam_token_used($token)) {
    respond(false, $expired, [], 419);
}

// Hız sınırı (bkz. form_rate): sınır zaten dolmuşsa alanlara ve dosyaya bakılmadan geri çevrilir.
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlMsg   = [
    'ip'   => 'Kısa sürede çok fazla gönderim yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.',
    'site' => 'Şu anda çok fazla gönderim alınıyor. Lütfen bir süre sonra tekrar deneyin.',
    'cv'   => 'Şu anda çok fazla başvuru alınıyor. Lütfen bir süre sonra tekrar deneyin ya da özgeçmişinizi e-posta ile gönderin.',
];
if (($rl = form_rate($ip, $type === 'kariyer', false)) !== null) {
    respond(false, $rlMsg[$rl], [], 429);
}

$data   = [];
$errors = [];
foreach ($forms[$type]['fields'] as $name => [$label, $rules]) {
    $raw   = $_POST[$name] ?? null;
    $value = is_string($raw) ? trim($raw) : '';   // dizi olarak gönderilen alan boş sayılır
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $rules = explode('|', $rules);
    if (in_array('accepted', $rules, true)) {
        if ($value !== '1') {
            $errors[$name] = 'Devam etmek için bu onayı vermeniz gerekiyor.';
        }
        $data[$name] = $value === '1' ? 'Evet (' . date('d.m.Y H:i') . ')' : '';
        continue;
    }
    if (in_array('bool', $rules, true)) {
        $data[$name] = $value === '1' ? 'Evet (' . date('d.m.Y H:i') . ')' : 'Hayır';
        continue;
    }
    if (in_array('required', $rules, true) && $value === '') {
        $errors[$name] = $label . ' alanı zorunludur.';
        continue;
    }
    if ($value === '') {
        $data[$name] = '';
        continue;
    }
    foreach ($rules as $rule) {
        // Sıkı denetim: adres bildirim e-postasının Yanıtla başlığına girer; tırnaklı, boşluklu ya da satır sonu içeren adres kabul edilmez
        if ($rule === 'email' && !mail_address_ok($value)) {
            $errors[$name] = 'Geçerli bir e-posta adresi yazın.';
        }
        if ($rule === 'phone' && !preg_match('/^[0-9 +()\-]{7,20}$/', $value)) {
            $errors[$name] = 'Geçerli bir telefon numarası yazın.';
        }
        if ($rule === 'url' && (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $value))) {
            $errors[$name] = 'http:// ya da https:// ile başlayan geçerli bir adres yazın.';
        }
        if (str_starts_with($rule, 'in:') && !in_array($value, (array) site(substr($rule, 3)), true)) {
            $errors[$name] = 'Listeden bir seçim yapın.';
        }
        if (str_starts_with($rule, 'max:') && mb_strlen($value) > (int) substr($rule, 4)) {
            $errors[$name] = $label . ' çok uzun.';
        }
    }
    $data[$name] = $value;
}

/* ---------- Özgeçmiş dosyası (yalnızca kariyer formu) ---------- */

$cv = null;
if ($type === 'kariyer') {
    $f = $_FILES['cv'] ?? null;
    $err = is_array($f) ? (int) $f['error'] : UPLOAD_ERR_NO_FILE;
    if ($err === UPLOAD_ERR_NO_FILE) {
        $errors['cv'] = 'Özgeçmişinizi ekleyin.';
    } elseif ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE || (int) $f['size'] > 5 * 1024 * 1024) {
        $errors['cv'] = 'Dosya en fazla 5 MB olabilir.';
    } elseif ($err !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        $errors['cv'] = 'Dosya yüklenemedi. Lütfen tekrar deneyin.';
    } else {
        // Uzantıya değil dosyanın içeriğine bakılır
        $ext  = strtolower(pathinfo((string) $f['name'], PATHINFO_EXTENSION));
        $raw  = (string) file_get_contents($f['tmp_name']);
        $pdf  = $ext === 'pdf' && str_starts_with($raw, '%PDF-');
        $docx = $ext === 'docx' && str_starts_with($raw, "PK\x03\x04") && str_contains($raw, 'word/document.xml');
        if (!$pdf && !$docx) {
            $errors['cv'] = 'Yalnızca PDF ya da DOCX dosyası yükleyebilirsiniz.';
        } else {
            $cv = [
                'tmp'  => $f['tmp_name'],
                'ext'  => $ext,
                'name' => mb_substr(preg_replace('/[^\p{L}\p{N} ._()\-]/u', '_', basename((string) $f['name'])), 0, 120),
                'type' => $pdf ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
        }
        unset($raw);
    }
}

if ($errors) {
    respond(false, 'Lütfen işaretli alanları kontrol edin.', $errors, 422);
}

// Geçerli gönderim, dosya saklanmadan ve e-posta gönderilmeden önce sayılır (denetim ve kayıt aynı kilit altında).
if (($rl = form_rate($ip, $cv !== null, true)) !== null) {
    respond(false, $rlMsg[$rl], [], 429);
}

// Belirteç tek kullanımlıktır ve ancak doğrulamadan geçen gönderimde işaretlenir: alan hatasını düzelten ziyaretçi
// aynı belirteçle yeniden gönderebilir. Aynı anda gelen iki istekten yalnızca biri geçer.
if (spam_token_used($token, true)) {
    respond(false, $expired, [], 419);
}

if ($cv) {
    $dir = ROOT . '/storage/cv';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $stored = date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $cv['ext'];
    if (!@move_uploaded_file($cv['tmp'], $dir . '/' . $stored)) {
        spam_token_used($token, false);   // gönderim alınamadı: ziyaretçi aynı belirteçle yeniden deneyebilsin
        respond(false, 'Dosya kaydedilemedi. Lütfen daha sonra tekrar deneyin ya da e-posta ile gönderin.', [], 500);
    }
    $data['cv']      = $stored;
    $data['cv_name'] = $cv['name'];
}

/* ---------- İstenmeyen gönderim süzgeci (bkz. app/spam.php) ---------- */

$spam = spam_score($type, $data, [
    'age'      => form_token_age($token),
    'proof'    => spam_proof_ok($token, $_POST['_p'] ?? null),
    'ua'       => (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
    'lang'     => (string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''),
    'fetch'    => $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null,
    'records'  => spam_records(),
    'honeypot' => $honeypot,
]);
$suspect = $spam['score'] >= SPAM_LIMIT;

/* ---------- Kaydet ve gönder ---------- */

$done = match ($type) {
    'bulten'  => 'Kaydınız alındı. Yeni çağrılar ve programlar açıldığında sizi haberdar edeceğiz.',
    'kariyer' => 'Başvurunuz bize ulaştı. Değerlendirmenin ardından sizinle iletişime geçeceğiz.',
    default   => 'Dilekçeniz bize ulaştı. En kısa sürede dönüş yapacağız.',
};

// İş başvuruları ve bülten kayıtları, şüpheli gönderimler de incelenebilsin diye her zaman saklanır
// (config.php'de "store_submissions" kapalı olsa bile). Şüpheli kaydın serbest metinleri kısaltılarak saklanır.
$saved = false;
if (cfg('store_submissions') || in_array($type, ['kariyer', 'bulten'], true) || $suspect) {
    // Geçersiz UTF-8 baytları kaydı bozmasın diye yer tutucuyla değiştirilir; kayıt yine de üretilemezse "saklanmadı" sayılır
    $line  = json_encode(['id' => bin2hex(random_bytes(6)), 'form' => $type, 'time' => date('c'), 'ip' => $ip, 'data' => $suspect ? spam_trim($type, $data) : $data]
        + ($suspect ? ['spam' => $spam] : []), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    $saved = $line !== false && @file_put_contents(ROOT . '/storage/submissions.jsonl', $line . "\n", FILE_APPEND | LOCK_EX) !== false;
}

/**
 * Yanıtı hemen gönderip bağlantıyı kapatır; istek arka planda sürer (LiteSpeed ya da PHP-FPM). Sunucu bunu desteklemiyorsa
 * hiçbir şey yazmadan false döner. Böylece yanıt süresi, ardından e-posta gönderilip gönderilmediğini ele vermez
 * (şüpheli gönderim e-postasız, olağan gönderim e-postalı biter; ikisi de aynı anda yanıtlanır).
 */
$respondEarly = function (string $message): bool {
    $finish = function_exists('litespeed_finish_request') ? 'litespeed_finish_request' : (function_exists('fastcgi_finish_request') ? 'fastcgi_finish_request' : null);
    if ($finish === null) {
        return false;
    }
    ignore_user_abort(true);   // ziyaretçi sayfadan ayrılsa da e-posta gönderilir
    respond(true, $message, [], 200, false);
    $finish();
    return true;
};
$notify = function () use ($forms, $type, $data, $ip): bool {
    $sent = form_notify($forms[$type], $type, $data, $ip, time());
    if (!$sent) {
        @file_put_contents(ROOT . '/storage/mail-failures.log', date('c') . ' ' . $type . " e-postası gönderilemedi\n", FILE_APPEND | LOCK_EX);
    }
    return $sent;
};

// Şüpheli gönderim: kayıt "spam" anahtarıyla saklanır, e-posta gönderilmez. Yanıt olağan gönderimle aynıdır (bot geri bildirim almaz).
// Site geneli saatlik sınıra sayılmaz; şüpheli kayıtlar en yeni SPAM_MAX kayıtla ve SPAM_DAYS günle sınırlanır (spam_purge).
// Kayıt yazılamadıysa gönderim kaybolmasın diye aşağıda e-posta yine gönderilir.
if ($suspect && $saved) {
    form_rate_site_undo();
    $early = $respondEarly($done);
    spam_purge();
    if (!$early) {
        respond(true, $done);
    }
    exit;
}

// Kayıt saklandıysa önce yanıt verilir, bildirim e-postası ardından gönderilir (sunucu destekliyorsa): gönderim zaten dosyada durduğu
// için e-posta iletilemese de kaybolmaz; iletilemediği storage/mail-failures.log dosyasına yazılır.
if ($saved && $respondEarly($done)) {
    $notify();
    if (mt_rand(1, 50) === 1) {
        spam_purge();
    }
    exit;
}

$sent = $notify();
// Süresi dolan şüpheli kayıtlar ara sıra temizlenir (yaklaşık 50 gönderimde bir)
if (mt_rand(1, 50) === 1) {
    spam_purge();
}
// Ne kayıt saklanabildi ne e-posta gönderilebildi: gönderim alınamadı, ziyaretçiye açıkça söylenir
if (!$sent && !$saved) {
    spam_token_used($token, false);   // ziyaretçi aynı belirteçle yeniden deneyebilsin
    respond(false, 'Mesajınız şu anda iletilemedi. Lütfen telefonla ya da e-posta ile ulaşın.', [], 500);
}

respond(true, $done);
