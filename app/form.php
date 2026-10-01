<?php
declare(strict_types=1);

/**
 * İletişim ve "Haberdar Ol" formlarının ortak işleyicisi.
 * JSON döndürür; JavaScript kapalıysa kullanıcıyı sayfaya geri yönlendirir.
 */

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function respond(bool $ok, string $message, array $errors = [], int $status = 200): void
{
    global $wantsJson;
    if ($wantsJson) {
        http_response_code($ok ? 200 : $status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => $ok, 'message' => $message, 'errors' => (object) $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $back = $_POST['_back'] ?? url('iletisim');
    if (!is_string($back) || !str_starts_with($back, base_path() . '/')) {
        $back = url('iletisim');
    }
    redirect($back . '?durum=' . ($ok ? 'tamam' : 'hata'), 303);
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
    // Haberdar Ol sayfasındaki kupon
    'haberdarol' => [
        'subject' => 'Web sitesi: Haberdar Ol kaydı',
        'fields'  => [
            'ad'      => ['Ad', 'required|max:80'],
            'soyad'   => ['Soyad', 'required|max:80'],
            'email'   => ['E-posta', 'required|email'],
            'telefon' => ['Telefon', 'phone'],
            'firma'   => ['Firma', 'max:160'],
            'ilgi'    => ['İlgilendiği konular', 'list|max:600'],
            'kvkk'    => ['Aydınlatma metni', 'accepted'],
            'etk'     => ['Ticari elektronik ileti onayı', 'accepted'],
        ],
    ],
];

$type = $_POST['_form'] ?? '';
if (!isset($forms[$type])) {
    respond(false, 'Geçersiz form.', [], 400);
}

// Bal küpü: insanlar bu alanı görmez, botlar doldurur.
if (!empty($_POST['website'])) {
    respond(true, 'Teşekkürler.');
}

if (!verify_form_token($_POST['_token'] ?? null)) {
    respond(false, 'Oturum süresi doldu. Lütfen sayfayı yenileyip tekrar deneyin.', [], 419);
}

// Basit hız sınırı: aynı IP'den 10 dakikada en fazla 5 gönderim.
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile  = ROOT . '/storage/rate-' . md5($ip) . '.json';
$now     = time();
$hits    = is_file($rlFile) ? (json_decode((string) file_get_contents($rlFile), true) ?: []) : [];
$hits    = array_values(array_filter($hits, fn($t) => $t > $now - 600));
if (count($hits) >= 5) {
    respond(false, 'Kısa sürede çok fazla gönderim yapıldı. Lütfen birkaç dakika sonra tekrar deneyin.', [], 429);
}

$data   = [];
$errors = [];
foreach ($forms[$type]['fields'] as $name => [$label, $rules]) {
    $rules = explode('|', $rules);
    $raw   = $_POST[$name] ?? '';
    if (in_array('list', $rules, true)) {
        // Çoklu seçim (onay kutuları): metne çevir
        $raw = is_array($raw) ? implode(', ', array_map(fn($v) => trim((string) $v), array_filter($raw, 'is_string'))) : (string) $raw;
    }
    $value = is_array($raw) ? '' : trim((string) $raw);
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    if (in_array('accepted', $rules, true)) {
        if ($value !== '1') {
            $errors[$name] = 'Devam etmek için bu onayı vermeniz gerekiyor.';
        }
        $data[$name] = $value === '1' ? 'Evet (' . date('d.m.Y H:i') . ')' : '';
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
        if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$name] = 'Geçerli bir e-posta adresi yazın.';
        }
        if ($rule === 'phone' && !preg_match('/^[0-9 +()\-]{7,20}$/', $value)) {
            $errors[$name] = 'Geçerli bir telefon numarası yazın.';
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

if ($errors) {
    respond(false, 'Lütfen işaretli alanları kontrol edin.', $errors, 422);
}

$hits[] = $now;
@file_put_contents($rlFile, json_encode($hits));

/* ---------- Kaydet ve gönder ---------- */

$labels = array_map(fn($f) => $f[0], $forms[$type]['fields']);

if (cfg('store_submissions')) {
    $line = json_encode(['form' => $type, 'time' => date('c'), 'ip' => $ip, 'data' => $data], JSON_UNESCAPED_UNICODE);
    @file_put_contents(ROOT . '/storage/submissions.jsonl', $line . "\n", FILE_APPEND | LOCK_EX);
}

$body = $forms[$type]['subject'] . "\n" . str_repeat('-', 40) . "\n";
foreach ($data as $k => $v) {
    $body .= $labels[$k] . ': ' . ($v === '' ? '-' : $v) . "\n";
}
$body .= str_repeat('-', 40) . "\nTarih: " . date('d.m.Y H:i') . "\nIP: " . $ip . "\n";

require APP . '/mailer.php';
$sent = send_mail(
    (string) cfg('mail.to'),
    $forms[$type]['subject'],
    $body,
    $data['email'] ?? null
);

if (!$sent) {
    @file_put_contents(ROOT . '/storage/mail-failures.log', date('c') . ' ' . $type . " e-postası gönderilemedi\n", FILE_APPEND | LOCK_EX);
}
if (!$sent && !cfg('store_submissions')) {
    respond(false, 'Mesajınız şu anda iletilemedi. Lütfen telefonla ya da e-posta ile ulaşın.', [], 500);
}

respond(true, $type === 'haberdarol'
    ? 'Kaydınız alındı. Sizi ilgilendiren bir çağrı açıldığında haber vereceğiz.'
    : 'Dilekçeniz bize ulaştı. En kısa sürede dönüş yapacağız.');
