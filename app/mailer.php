<?php
declare(strict_types=1);

/**
 * Düz metin e-posta gönderir. config.php içinde 'smtp' tanımlıysa SMTP, değilse PHP mail() kullanılır.
 */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $from     = (string) cfg('mail.from');
    $fromName = (string) cfg('mail.from_name');
    $encSubj  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encName  = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
    $replyTo  = ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) ? $replyTo : null;

    $headers = [
        'From: ' . $encName . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'X-Mailer: Arslanli-Web',
    ];
    if ($replyTo) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    $encodedBody = chunk_split(base64_encode($body));

    $smtp = cfg('mail.smtp');
    if (is_array($smtp) && !empty($smtp['host'])) {
        try {
            return smtp_send($smtp, $from, $to, $encSubj, $headers, $encodedBody);
        } catch (Throwable $e) {
            error_log('SMTP hatası: ' . $e->getMessage());
            return false;
        }
    }

    return @mail($to, $encSubj, $encodedBody, implode("\r\n", $headers), '-f' . $from);
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
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
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
    $cmd('QUIT', [221]);
    fclose($fp);
    return true;
}
