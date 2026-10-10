<?php
declare(strict_types=1);

/**
 * Günlük bakım
 * ---------------------------------------------------------------------------
 * Sunucuda zamanlanmış görev yoktur; bakım sıradan ziyaretçi istekleriyle tetiklenir ve günde en çoğu bir kez çalışır:
 *   1. Süresi dolan şüpheli (bekletilen) gönderimler özgeçmiş dosyalarıyla birlikte silinir (spam_purge).
 *   2. Bir günden eski hız sınırı (rate-*.json) ve giriş kısıtlama (login-*.json) dosyaları silinir.
 *   3. Ziyaret sayacının dünden kalan günlük tuzu ve ziyaretçi özetleri silinir (stats_purge_stale).
 *   4. Ayarların geçmiş sürümlerinde kalmış SMTP şifreleri silinir (settings_history_scrub).
 *
 * Her istekte maliyeti tek bir stat çağrısıdır (storage/.temizlik dosyasının değişiklik zamanı). Gün dolduysa iş, yanıt ziyaretçiye gönderildikten
 * SONRA yapılır: PHP-FPM ve LiteSpeed'de fastcgi_finish_request() / litespeed_finish_request() ile bağlantı önce kapatılır; bu işlevlerin olmadığı
 * sunucuda (mod_php, php -S) iş çıktı boşaltıldıktan sonra, kısa ve zaman sınırlı olarak yapılır.
 * Eşzamanlılık: dosya kilidi (engellemesiz) işi tek isteğe verir; kilidi alamayan istek hiçbir şey beklemez. Damga yalnızca iş bitince güncellenir:
 * iş yarıda kesilirse bir sonraki istek yeniden dener.
 */

const HOUSEKEEPING_STAMP = ROOT . '/storage/.temizlik';
const HOUSEKEEPING_EVERY = 86400;

/** Bakım zamanı geldi mi? Maliyeti tek stat. */
function housekeeping_due(): bool
{
    $s = @stat(HOUSEKEEPING_STAMP);   // tek stat: değişiklik zamanı ve boyut (boş dosya: bakım başladı ama bitmedi)
    return $s === false || $s['size'] === 0 || $s['mtime'] < time() - HOUSEKEEPING_EVERY;
}

/** İstek başında çağrılır: zaman gelmediyse (çoğu istek) hemen döner; geldiyse işi yanıttan sonraya bırakır. */
function housekeeping_tick(): void
{
    if (!housekeeping_due()) {
        return;
    }
    register_shutdown_function(function (): void {
        // Yanıt ziyaretçiye önce gider
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        if (function_exists('fastcgi_finish_request')) {
            @fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            @litespeed_finish_request();
        } else {
            @flush();
        }
        ignore_user_abort(true);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();   // oturum kilidi (panel) bakım boyunca tutulmasın
        }
        try {
            housekeeping_run();
        } catch (Throwable $e) {
            error_log('Günlük bakım: ' . $e->getMessage());
        }
    });
}

/**
 * Bakımı yapar; başka bir istek zaten yapıyorsa ya da gün dolmadıysa hiçbir şey yapmaz.
 * @return array<string,int>|null yapılan işlerin sayıları; çalışmadıysa null
 */
function housekeeping_run(bool $force = false): ?array
{
    $fh = @fopen(HOUSEKEEPING_STAMP, 'c');
    if (!$fh) {
        return null;
    }
    try {
        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            return null;   // başka bir istek bakımı yapıyor
        }
        clearstatcache(true, HOUSEKEEPING_STAMP);
        $m = @filemtime(HOUSEKEEPING_STAMP);
        if (!$force && $m !== false && $m >= time() - HOUSEKEEPING_EVERY && (int) @filesize(HOUSEKEEPING_STAMP) > 0) {
            return null;   // kilidi beklerken başka bir istek bitirmiş
        }
        @set_time_limit(30);
        $done = ['supheli' => 0, 'hiz_dosyasi' => 0, 'sayac' => 0, 'gecmis_sifre' => 0];

        // 1. Süresi dolan şüpheli gönderimler (özgeçmiş dosyalarıyla)
        require_once APP . '/spam.php';
        $done['supheli'] = spam_purge();

        // 2. Bir günden eski hız sınırı ve giriş kısıtlama dosyaları (kendi kilitleriyle çakışmamak için o kilitler engellemesiz alınır)
        $dir = ROOT . '/storage';
        $old = time() - 86400;
        foreach (['rate' => 'rate-*.json', 'login' => 'login-*.json'] as $lockName => $pattern) {
            $lock = @fopen($dir . '/' . $lockName . '.lock', 'c');
            if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
                if ($lock) fclose($lock);
                continue;
            }
            foreach (glob($dir . '/' . $pattern) ?: [] as $f) {
                $t = @filemtime($f);
                if ($t !== false && $t < $old && @unlink($f)) {
                    $done['hiz_dosyasi']++;
                }
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        // 3. Ziyaret sayacının dünkü tuzu ve özetleri
        require_once APP . '/stats.php';
        $done['sayac'] = stats_purge_stale() ? 1 : 0;

        // 4. Ayarların geçmiş sürümlerinde kalmış SMTP şifreleri
        $done['gecmis_sifre'] = settings_history_scrub();
        $done['yukleme'] = uploads_purge_orphans();   // hiçbir içeriğin ve sürümün göstermediği eski yüklenmiş görseller

        // Damga en sonda güncellenir (yazı da yapılır: boş dosya "hiç çalışmadı" sayılır)
        ftruncate($fh, 0);
        fwrite($fh, date('c'));
        fflush($fh);
        @touch(HOUSEKEEPING_STAMP);
        return $done;
    } finally {
        @flock($fh, LOCK_UN);
        fclose($fh);
    }
}
