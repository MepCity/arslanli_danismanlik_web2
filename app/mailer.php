<?php
declare(strict_types=1);

/**
 * E-posta gönderir. Ayarlarda SMTP tanımlıysa SMTP, değilse PHP mail() kullanılır. Varsayılan ileti düz metindir.
 * mail() sunucuda kapatılmışsa (disable_functions) ve SMTP ayarlı değilse ileti gönderilmez, false döner; hiçbir yolda ölümcül hata oluşmaz.
 *
 * $opts (bülten gönderimi kullanır; verilmezse davranış eskisiyle aynıdır):
 *   'html'    => iletinin HTML sürümü; verilirse ileti multipart/alternative olur (düz metin + HTML)
 *   'headers' => ['List-Unsubscribe' => '<https://...>', ...] ek başlıklar. Ad ya da değer geçersizse (satır sonu, denetim
 *                karakteri, ASCII dışı karakter) ya da çekirdek bir başlığı (From, To, Subject, Content-Type...) ezmeye çalışıyorsa ileti gönderilmez.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null, array $attachments = [], array $opts = []): bool
{
    mail_last_error('');
    $from     = (string) cfg('mail.from');
    // Alıcı ve gönderen adresi başlıklara, SMTP komutlarına ve mail() için "-f" değişkenine girer: sıkı denetimden geçmezse gönderilmez
    foreach ([$to, $from] as $addr) {
        if (!mail_address_ok($addr)) {
            mail_last_error('Alıcı ya da gönderen adresi geçerli değil.');
            return false;
        }
    }
    // Gönderen adı: bülten e-postalarında şirket adı (opts.from_name), diğerlerinde ayarlardaki ad
    $fromName = is_string($opts['from_name'] ?? null) && trim($opts['from_name']) !== '' && strpbrk($opts['from_name'], "\r\n") === false
        ? trim($opts['from_name']) : (string) cfg('mail.from_name');
    $encSubj  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encName  = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    // Yanıtla adresi ziyaretçiden gelir ve başlığa olduğu gibi yazılır: aynı denetimden geçmezse başlık hiç eklenmez
    $replyTo  = mail_address_ok($replyTo) ? $replyTo : null;

    $headers = [
        'From: ' . $encName . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'X-Mailer: Arslanli-Web',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    // Ek başlıklar: değerler başlık satırına olduğu gibi girer; satır sonu ya da denetim karakteri taşıyan değer gönderimi durdurur
    $reserved = ['from', 'to', 'cc', 'bcc', 'subject', 'date', 'reply-to', 'mime-version', 'content-type', 'content-transfer-encoding', 'x-mailer'];
    foreach ((array) ($opts['headers'] ?? []) as $name => $value) {
        if (!is_string($name) || !is_string($value) || !preg_match('/^[A-Za-z][A-Za-z0-9\-]{0,60}$/D', $name)
            || $value === '' || strlen($value) > 900 || preg_match('/[^\x20-\x7E]/', $value) || in_array(strtolower($name), $reserved, true)) {
            error_log('E-posta gönderilmedi: geçersiz ek başlık (' . (is_string($name) ? substr($name, 0, 40) : '?') . ').');
            mail_last_error('Ek başlık geçersiz.');
            return false;
        }
        $headers[] = $name . ': ' . $value;
    }
    // Message-ID: SMTP ile gönderimde sunucu eklemeyebilir; alıcı sunucular (Gmail dahil) eksikliğini spam işareti sayar
    if (!preg_grep('/^message-id$/i', array_map('strval', array_keys((array) ($opts['headers'] ?? []))))) {
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '.' . time() . '@' . substr((string) strrchr($from, '@'), 1) . '>';
    }

    // HTML sürümü varsa gövde multipart/alternative olur: önce düz metin, sonra HTML (istemci gösterebildiği son bölümü seçer)
    $html     = is_string($opts['html'] ?? null) && $opts['html'] !== '' ? $opts['html'] : null;
    $textPart = "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($body));
    $altType  = '';
    $altBody  = '';
    if ($html !== null) {
        $alt     = '=_arsl_alt_' . bin2hex(random_bytes(12));
        $altType = 'multipart/alternative; boundary="' . $alt . '"';
        $altBody = "--$alt\r\n" . $textPart
            . "--$alt\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . "Content-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$alt--\r\n";
    }

    if (!$attachments && $html !== null) {
        $headers[]   = 'Content-Type: ' . $altType;
        $encodedBody = $altBody;
    } elseif (!$attachments) {
        $headers[]   = 'Content-Type: text/plain; charset=UTF-8';
        $headers[]   = 'Content-Transfer-Encoding: base64';
        $encodedBody = chunk_split(base64_encode($body));
    } else {
        // Ekli ileti: multipart/mixed
        $boundary  = '=_arsl_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $encodedBody  = "--$boundary\r\n"
            . ($html !== null ? 'Content-Type: ' . $altType . "\r\n\r\n" . $altBody : $textPart);
        foreach ($attachments as $att) {
            if (empty($att['path']) || !is_file($att['path'])) {
                continue;
            }
            $name = '=?UTF-8?B?' . base64_encode((string) $att['name']) . '?=';
            $encodedBody .= "--$boundary\r\n"
                . 'Content-Type: ' . ($att['type'] ?? 'application/octet-stream') . '; name="' . $name . "\"\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . 'Content-Disposition: attachment; filename="' . $name . "\"\r\n\r\n"
                . chunk_split(base64_encode((string) file_get_contents($att['path'])));
        }
        $encodedBody .= "--$boundary--\r\n";
    }

    $smtp = cfg('mail.smtp');
    if (is_array($smtp) && !empty($smtp['host'])) {
        try {
            $ok = smtp_send($smtp, $from, $to, $encSubj, $headers, $encodedBody);
            if (!$ok) {
                mail_last_error('SMTP: güvenli (TLS) bağlantı kurulamadı.');
            }
            return $ok;
        } catch (Throwable $e) {
            error_log('SMTP hatası: ' . $e->getMessage());
            mail_last_error('SMTP: ' . $e->getMessage());
            return false;
        }
    }

    // Bazı barındırma firmaları mail() fonksiyonunu kapatır (disable_functions): çağırmak ölümcül hata olurdu
    if (!function_exists('mail')) {
        error_log('E-posta gönderilemedi: sunucuda mail() fonksiyonu kapalı ve SMTP ayarlı değil. Yönetim panelinde İletişim ve şirket, E-posta bölümünden SMTP bilgilerini girin.');
        mail_last_error('Sunucuda mail() fonksiyonu kapalı ve SMTP ayarlı değil.');
        return false;
    }
    $ok = @mail($to, $encSubj, $encodedBody, implode("\r\n", $headers), '-f' . $from);
    if (!$ok) {
        mail_last_error('Sunucunun e-posta fonksiyonu iletiyi kabul etmedi.');
    }
    return $ok;
}

/**
 * E-posta adresi başlıklara, SMTP komutlarına ve mail() için "-f" değişkenine güvenle girebilir mi?
 * filter_var tek başına yetmez: tırnak içine alınmış adreslerde satır sonu, boşluk ve denetim karakterlerine izin verir
 * ("a\<satır sonu>Bcc:..."@alan.com gibi bir adres iletiye başlık satırı ekletir). Bu yüzden satır sonu, denetim karakteri,
 * boşluk, çift tırnak ve ters eğik çizgi içeren adres geçersiz sayılır. Form alanı, Yanıtla adresi, alıcı, gönderen,
 * panel ve yapay zekâ erişimindeki e-posta ayarları ile bülten aboneleri bu tek denetimi kullanır.
 * @param mixed $addr
 */
function mail_address_ok($addr): bool
{
    return is_string($addr) && $addr !== '' && strlen($addr) <= 254
        && !preg_match('/[\x00-\x20\x7F"\\\\]/', $addr)
        && filter_var($addr, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Kullanılacak gönderim yolu: 'smtp' (ayarlıysa), 'mail' (PHP mail() kullanılabiliyorsa) ya da '' (gönderim mümkün değil:
 * SMTP ayarlı değil ve mail() sunucuda kapalı).
 */
function mail_transport(): string
{
    $smtp = cfg('mail.smtp');
    if (is_array($smtp) && !empty($smtp['host'])) {
        return 'smtp';
    }
    return function_exists('mail') ? 'mail' : '';
}

/** Son send_mail() çağrısının hata nedeni (başarılıysa ''). Toplu gönderimde alıcı başına kaydedilir. */
function mail_last_error(?string $set = null): string
{
    static $err = '';
    if ($set !== null) {
        $err = mb_substr(trim((string) preg_replace('/\s+/u', ' ', $set)), 0, 200);
    }
    return $err;
}

function smtp_send(array $s, string $from, string $to, string $subject, array $headers, string $body): bool
{
    $secure = $s['secure'] ?? 'ssl';
    $host   = ($secure === 'ssl' ? 'ssl://' : '') . $s['host'];
    $fp     = @stream_socket_client($host . ':' . ($s['port'] ?? 465), $errno, $errstr, 15);
    if (!$fp) {
        throw new RuntimeException("Bağlantı kurulamadı: $errstr");
    }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $c, array $ok) use ($fp, $read): string {
        fwrite($fp, $c . "\r\n");
        $r = $read();
        if (!in_array((int) substr($r, 0, 3), $ok, true)) {
            throw new RuntimeException(trim($r));
        }
        return $r;
    };

    $read();
    $domain = preg_replace('/^.*@/', '', $from);
    $cmd('EHLO ' . $domain, [250]);
    if ($secure === 'tls') {
        $cmd('STARTTLS', [220]);
        // TLS kurulamazsa bağlantı kapatılır; kullanıcı adı ve şifre hiçbir zaman açık metin olarak gönderilmez
        if (@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
            fclose($fp);
            error_log('SMTP hatası: TLS bağlantısı kurulamadı. ' . (error_get_last()['message'] ?? ''));
            return false;
        }
        $cmd('EHLO ' . $domain, [250]);
    }
    if (!empty($s['user'])) {
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode((string) $s['user']), [334]);
        $cmd(base64_encode((string) $s['pass']), [235]);
    }
    $cmd('MAIL FROM:<' . $from . '>', [250]);
    $cmd('RCPT TO:<' . $to . '>', [250, 251]);
    $cmd('DATA', [354]);

    $msg  = 'To: <' . $to . ">\r\n";
    $msg .= 'Subject: ' . $subject . "\r\n";
    $msg .= 'Date: ' . date('r') . "\r\n";
    $msg .= implode("\r\n", $headers) . "\r\n\r\n";
    $msg .= str_replace("\n.", "\n..", $body);
    $cmd($msg . "\r\n.", [250]);
    // Sunucu iletiyi kabul etti (250): bundan sonrası gönderimin sonucunu değiştirmez. QUIT yanıtı gelmezse ya da bağlantı
    // koparsa hata sayılmaz; sayılsaydı ileti "gönderilemedi" görünür ve yeniden denenince alıcıya ikinci kez giderdi.
    try {
        stream_set_timeout($fp, 3);   // yanıt vermeyen sunucu toplu gönderimi alıcı başına 15 saniye bekletmesin
        @fwrite($fp, "QUIT\r\n");
        $read();
    } catch (Throwable $e) {
    }
    @fclose($fp);
    return true;
}
