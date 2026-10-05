<?php
declare(strict_types=1);

/**
 * Yönetim paneli çekirdeği: oturum, güvenlik, yönlendirme yardımcıları ve form bileşenleri.
 * Bölümler app/admin/sections/{bolum}.php içindedir ve bu dosyadaki fonksiyonları kullanır.
 */

require_once APP . '/spam.php';   // şüpheli (karantinadaki) kayıtlar: spam_rewrite(), spam_purge()

/* =========================================================================
   Oturum ve güvenlik
   ========================================================================= */

function adm_boot(): void
{
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    header('X-Frame-Options: DENY');
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    // Site adresi https ise (ör. HTTPS'i karşılayan bir vekil sunucunun arkasında) çerez yine yalnızca HTTPS ile gönderilir; yerel geliştirme hariç
    $host = strtolower((string) preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
    if (stripos((string) cfg('url'), 'https://') === 0 && !in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)) {
        $secure = true;
    }
    session_name('arsl_yonetim');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (base_path() ?: '') . '/yonetim',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function adm_url(string $p = ''): string { return url('yonetim' . ($p !== '' ? '/' . ltrim($p, '/') : '')); }
function adm_go(string $p = ''): void { header('Location: ' . adm_url($p), true, 303); exit; }
/** Geçerli şifrenin parmak izi; oturumda saklanır, şifre değişince eski oturumlar kendiliğinden kapanır. */
function adm_pw_mark(): string { return substr(hash('sha256', adm_hash()), 0, 16); }
function adm_logged(): bool
{
    return !empty($_SESSION['admin']) && ($_SESSION['until'] ?? 0) > time()
        && is_string($_SESSION['pw'] ?? null) && hash_equals(adm_pw_mark(), $_SESSION['pw']);
}
function adm_csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function adm_csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(adm_csrf()) . '">'; }
function adm_csrf_ok(): bool { return is_string($_POST['_csrf'] ?? null) && hash_equals(adm_csrf(), $_POST['_csrf']); }

/** Bir sonraki sayfada gösterilecek bildirim. */
function adm_flash(?string $msg = null, string $kind = 'ok'): ?array
{
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $kind]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function adm_hash(): string
{
    $f = ROOT . '/storage/admin.json';
    if (is_file($f)) {
        $d = json_decode((string) file_get_contents($f), true);
        if (!empty($d['hash'])) return (string) $d['hash'];
    }
    return (string) cfg('admin.password_hash');
}

/** Hız sınırı anahtarı: IPv4 adresi olduğu gibi, IPv6 adresi /64 önekiyle (tek bir abone çok sayıda adres kullanabilir). */
function adm_ip_key(): string
{
    $ip  = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $bin = @inet_pton($ip);
    if ($bin === false || strlen($bin) !== 16) return $ip;
    if (str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")) return (string) inet_ntop(substr($bin, 12));   // IPv4 eşlemeli adres (::ffff:1.2.3.4)
    return bin2hex(substr($bin, 0, 8));
}

/**
 * Hatalı giriş sınırı: 15 dakikada 5 deneme.
 * Denetim kilit altında yapılır ve kilit, hatalı deneme kaydedilene (ya da istek bitene) kadar bırakılmaz;
 * böylece aynı anda gelen istekler sınırı birlikte aşamaz.
 */
function adm_throttle(bool $record = false): bool
{
    static $lock = null;
    $dir  = ROOT . '/storage';
    $file = $dir . '/login-' . md5(adm_ip_key()) . '.json';
    $now  = time();
    $free = function () use (&$lock): void {
        if ($lock) { flock($lock, LOCK_UN); fclose($lock); $lock = null; }
    };
    if (!$lock && ($lock = @fopen($dir . '/login.lock', 'c') ?: null)) {
        flock($lock, LOCK_EX);
        register_shutdown_function($free);   // başarılı girişte kilit, yanıttan sonraki işler beklenmeden bırakılır
        // Eski deneme dosyaları ara sıra temizlenir (yaklaşık 50 istekte bir)
        if (mt_rand(1, 50) === 1) {
            foreach (glob($dir . '/login-*.json') ?: [] as $f) {
                $m = @filemtime($f);
                if ($m !== false && $m < $now - 86400) @unlink($f);
            }
        }
    }
    $hits = is_file($file) ? json_decode((string) @file_get_contents($file), true) : [];
    $hits = array_values(array_filter(is_array($hits) ? $hits : [], fn($t) => $t > $now - 900));
    if ($record) { $hits[] = $now; @file_put_contents($file, json_encode($hits)); }
    $full = count($hits) >= 5;
    if ($record || $full) $free();
    return $full;
}

/* =========================================================================
   Panel durumu (son görülme zamanları, yedek tarihi)
   ========================================================================= */

function adm_state(?array $set = null): array
{
    $f = ROOT . '/storage/admin-state.json';
    $s = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
    if ($set !== null) {
        $s = array_replace($s, $set);
        @file_put_contents($f, json_encode($s, JSON_PRETTY_PRINT), LOCK_EX);
    }
    return $s;
}

/** Form gönderimleri (storage/submissions.jsonl), en yeni sonda. Dosya paylaşımlı kilitle okunur (bkz. spam_records). */
function adm_records(): array
{
    return spam_records();
}

/**
 * Kimliği ve türü tutan ilk kaydı kayıt dosyasından çıkarır. Okuma, silme ve yazma dosyanın kilidi altında tek adımda yapılır
 * (spam_rewrite): o sırada gelen yeni gönderim kaybolmaz, başka bir yeniden yazmayla çakışmaz.
 * @param callable $match kaydın silinecek kayıt olup olmadığını söyler
 * @return array|null çıkarılan kayıt; bulunamazsa ya da dosya yazılamazsa null
 */
function adm_record_remove(string $id, callable $match): ?array
{
    if (!preg_match('/^[a-f0-9]{12}$/', $id)) {
        return null;
    }
    $is = fn(array $r): bool => ($r['id'] ?? '') === $id && $match($r);
    if (!array_filter(adm_records(), $is)) {
        return null;   // kayıt yoksa dosya boşuna yeniden yazılmaz
    }
    $removed = null;
    $ok = spam_rewrite(function (array $r) use ($is, &$removed): ?array {
        if ($removed === null && $is($r)) {
            $removed = $r;
            return null;
        }
        return $r;
    });
    return $ok ? $removed : null;
}

/**
 * İş başvurusunu ve özgeçmiş dosyasını kalıcı olarak siler (panel ve yapay zekâ erişimi ortak kullanır).
 * @return array|null silinen kayıt; bulunamazsa null
 */
function adm_application_delete(string $id): ?array
{
    $removed = adm_record_remove($id, fn(array $r): bool => ($r['form'] ?? '') === 'kariyer');
    if ($removed === null) {
        return null;
    }
    spam_cv_delete($removed);
    // Günlüğe kişi adı yazılmaz
    changelog_event('basvurular', 'İş başvurusu ve özgeçmiş dosyası kalıcı olarak silindi' . (!empty($removed['data']['pozisyon']) ? ' (' . mb_substr((string) $removed['data']['pozisyon'], 0, 60) . ')' : ''));
    return $removed;
}

/**
 * İletişim ya da bülten formundan gelen bir kaydı kalıcı olarak siler (panel ve yapay zekâ erişimi ortak kullanır).
 * Bülten onayını geri alan ya da verisinin silinmesini isteyen kişinin kaydı böyle kaldırılır; iş başvuruları için adm_application_delete() kullanılır.
 * @return array|null silinen kayıt; bulunamazsa null
 */
function adm_form_record_delete(string $id): ?array
{
    $removed = adm_record_remove($id, fn(array $r): bool => ($r['form'] ?? '') !== 'kariyer');
    if ($removed === null) {
        return null;
    }
    $names = ['bulten' => 'bülten', 'haberdarol' => 'bülten', 'iletisim' => 'iletişim'];
    changelog_event('kayitlar', 'Bir ' . ($names[$removed['form'] ?? ''] ?? 'form') . ' kaydı kalıcı olarak silindi');   // günlüğe kişi adı yazılmaz
    if (in_array($removed['form'] ?? '', ['bulten', 'haberdarol'], true)) {
        require_once APP . '/bulten.php';
        // Adresin başka hiçbir bülten kaydı kalmadıysa ayrılanlar listesindeki ve gönderim kayıtlarındaki izleri de silinir
        bulten_adres_unut((string) ($removed['data']['email'] ?? ''));
    }
    return $removed;
}

/**
 * Şüpheli olarak ayrılmış bir kaydı gelen kutusuna alır ("Spam değil"): "spam" anahtarı kaldırılır ve
 * bekletilen bildirim e-postası gönderilir (iş başvurusunda özgeçmiş ekiyle).
 * @return array|null alınan kayıt ve 'sent' (e-posta gönderildi mi); bulunamazsa null
 */
function adm_record_release(string $id): ?array
{
    static $forms = null;
    if (!preg_match('/^[a-f0-9]{12}$/', $id)) {
        return null;
    }
    $rec = null;
    spam_rewrite(function (array $r) use ($id, &$rec): array {
        if ($rec === null && ($r['id'] ?? '') === $id && !empty($r['spam'])) {
            unset($r['spam']);
            $rec = $r;
        }
        return $r;
    });
    if ($rec === null) {
        return null;
    }
    if ($forms === null) {
        define('FORM_DEFS_ONLY', true);   // form.php gönderim işlemez; yalnızca tanımları döndürür ve form_notify() işlevini tanımlar
        $forms = require APP . '/form.php';
    }
    $type = (string) ($rec['form'] ?? '');
    $sent = isset($forms[$type]) && form_notify($forms[$type], $type, (array) ($rec['data'] ?? []), (string) ($rec['ip'] ?? ''), strtotime((string) ($rec['time'] ?? '')) ?: time());
    if (!$sent) {
        @file_put_contents(ROOT . '/storage/mail-failures.log', date('c') . ' ' . $type . " e-postası gönderilemedi\n", FILE_APPEND | LOCK_EX);
    }
    // Günlüğe kişi adı yazılmaz
    changelog_event($type === 'kariyer' ? 'basvurular' : 'kayitlar', 'Şüpheli olarak ayrılan bir ' . ($type === 'kariyer' ? 'iş başvurusu' : 'form kaydı') . ' gelen kutusuna alındı (spam değil)');
    return $rec + ['sent' => $sent];
}

/** Şüpheli olarak ayrılmış iletişim ve bülten kayıtlarının hepsini kalıcı olarak siler (iş başvurularına dokunmaz). Silinen kayıt sayısı döner. */
function adm_spam_delete_all(): int
{
    $n = 0;
    spam_rewrite(function (array $r) use (&$n): ?array {
        if (empty($r['spam']) || ($r['form'] ?? '') === 'kariyer') {
            return $r;
        }
        $n++;
        return null;
    });
    if ($n) {
        changelog_event('kayitlar', 'Şüpheli olarak ayrılan ' . $n . ' form kaydı kalıcı olarak silindi');
    }
    return $n;
}

/** Kenar menüdeki "yeni" sayıları: son ziyaretten sonra gelen kayıtlar. Şüpheli olarak ayrılanlar sayılmaz. */
function adm_unread(): array
{
    $st = adm_state();
    $out = ['kayitlar' => 0, 'basvurular' => 0];
    foreach (adm_records() as $r) {
        if (!empty($r['spam'])) continue;
        $t = strtotime($r['time'] ?? '') ?: 0;
        if (($r['form'] ?? '') === 'kariyer') {
            if ($t > ($st['seen_basvurular'] ?? 0)) $out['basvurular']++;
        } elseif ($t > ($st['seen_kayitlar'] ?? 0)) {
            $out['kayitlar']++;
        }
    }
    return $out;
}

/** CSV hücresi: =, +, -, @, sekme ya da satır başı ile başlayan değerin önüne ' konur; Excel onu formül olarak çalıştırmaz. */
function csv_safe(string $v): string
{
    // Yalnızca rakam ve ayraçlardan oluşan değerler (ör. "+90 554 808 97 71") formül olamaz; olduğu gibi kalır
    if ($v === '' || strpbrk($v[0], "=+-@\t\r") === false || preg_match('/^[+\-]?[0-9 ().\-]+$/D', $v)) {
        return $v;
    }
    return "'" . $v;
}

/* =========================================================================
   İkonlar
   ========================================================================= */

function ui_icon(string $name, string $class = 'i'): string
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $f = APP . '/icons/ui/' . $name . '.svg';
        $cache[$name] = is_file($f) ? (string) file_get_contents($f) : '';
    }
    return str_replace('<svg ', '<svg class="' . e($class) . '" aria-hidden="true" focusable="false" ', $cache[$name]);
}

/* =========================================================================
   Sayfa iskeleti
   ========================================================================= */

/**
 * Bölüm sayfasını panel iskeletiyle basar ve çıkar.
 * $o: section (aktif menü), subtitle, crumbs [[ad, adres], ...], actions (HTML), form (kaydet çubuğu için form id), wide (bool)
 */
function adm_layout(string $title, string $content, array $o = []): void
{
    require APP . '/admin/layout.php';
    exit;
}

/** Giriş sayfası gibi kenar menüsüz sayfalar */
function adm_bare(string $title, string $content): void
{
    $o = ['bare' => true];
    require APP . '/admin/layout.php';
    exit;
}

/* =========================================================================
   Form bileşenleri
   Hepsi HTML döndürür. Ad (name) değerleri PHP dizisi biçiminde olabilir: "faq[0][q]".
   ========================================================================= */

function ui_id(string $name): string { return 'f-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'); }

function ui_wrap(string $name, string $label, string $control, array $o = []): string
{
    $id   = $o['id'] ?? ui_id($name);
    $req  = !empty($o['required']) ? ' <span class="req" aria-hidden="true">*</span>' : '';
    $help = !empty($o['help']) ? '<p class="fld__help" id="' . $id . '-h">' . $o['help'] . '</p>' : '';
    $cls  = 'fld' . (!empty($o['class']) ? ' ' . $o['class'] : '');
    $lab  = $label !== '' ? '<label class="fld__label" for="' . $id . '">' . e($label) . $req . '</label>' : '';
    return '<div class="' . $cls . '">' . $lab . $control . $help . '</div>';
}

function ui_attrs(array $o, string $name): string
{
    $id = $o['id'] ?? ui_id($name);
    $a  = ' id="' . e($id) . '" name="' . e($name) . '"';
    if (!empty($o['help'])) $a .= ' aria-describedby="' . e($id) . '-h"';
    foreach (['placeholder', 'maxlength', 'minlength', 'pattern', 'autocomplete', 'inputmode', 'min', 'max', 'step'] as $k) {
        if (isset($o[$k]) && $o[$k] !== '') $a .= ' ' . $k . '="' . e((string) $o[$k]) . '"';
    }
    if (!empty($o['required'])) $a .= ' required';
    if (!empty($o['readonly'])) $a .= ' readonly';
    if (!empty($o['counter']) && !empty($o['maxlength'])) $a .= ' data-counter';
    if (!empty($o['slug_from'])) $a .= ' data-slug-from="' . e($o['slug_from']) . '"';
    return $a;
}

function ui_text(string $name, string $label, $value, array $o = []): string
{
    $type = $o['type'] ?? 'text';
    $ctl  = '<input class="inp" type="' . e($type) . '"' . ui_attrs($o, $name) . ' value="' . e((string) $value) . '">';
    return ui_wrap($name, $label, $ctl, $o);
}

function ui_textarea(string $name, string $label, $value, array $o = []): string
{
    $rows = (int) ($o['rows'] ?? 4);
    $ctl  = '<textarea class="inp inp--area" rows="' . $rows . '"' . ui_attrs($o, $name) . '>' . e((string) $value) . '</textarea>';
    return ui_wrap($name, $label, $ctl, $o);
}

function ui_select(string $name, string $label, array $options, $value, array $o = []): string
{
    $opts = '';
    if (isset($o['empty'])) $opts .= '<option value="">' . e($o['empty']) . '</option>';
    foreach ($options as $k => $v) {
        $k = is_int($k) && !empty($o['values_are_labels']) ? $v : $k;
        $opts .= '<option value="' . e((string) $k) . '"' . ((string) $k === (string) $value ? ' selected' : '') . '>' . e((string) $v) . '</option>';
    }
    return ui_wrap($name, $label, '<div class="sel"><select class="inp"' . ui_attrs($o, $name) . '>' . $opts . '</select></div>', $o);
}

/** Aç/kapa anahtarı (checkbox). Kapalıyken de değer gönderilsin diye gizli "0" eklenir. */
function ui_toggle(string $name, string $label, bool $on, array $o = []): string
{
    $id = $o['id'] ?? ui_id($name);
    $help = !empty($o['help']) ? '<span class="tgl__help">' . $o['help'] . '</span>' : '';
    return '<label class="tgl" for="' . e($id) . '"><input type="hidden" name="' . e($name) . '" value="0">'
        . '<input type="checkbox" id="' . e($id) . '" name="' . e($name) . '" value="1"' . ($on ? ' checked' : '') . '>'
        . '<span class="tgl__track" aria-hidden="true"><span class="tgl__dot"></span></span>'
        . '<span class="tgl__text"><span class="tgl__label">' . e($label) . '</span>' . $help . '</span></label>';
}

/**
 * Tekrarlanan grup: sürükleyerek sıralanır, eklenir, silinir.
 * $fields: [['key' => 'q', 'label' => 'Soru', 'type' => 'text'|'textarea'|'select'|'date', 'options' => [...], 'rows' => 3, 'placeholder' => '', 'class' => 'span-2']]
 * Tek alanlı basit listeler için $fields = [['key' => '', ...]] kullanılır; ad "programs[]" olur.
 */
function ui_repeater(string $name, string $label, array $items, array $fields, array $o = []): string
{
    $single = count($fields) === 1 && ($fields[0]['key'] ?? '') === '';
    $add    = $o['add'] ?? 'Ekle';
    $render = function ($item, string $idx) use ($name, $fields, $single): string {
        $html = '';
        foreach ($fields as $f) {
            $key   = $f['key'] ?? '';
            $nm    = $single ? $name . '[' . $idx . ']' : $name . '[' . $idx . '][' . $key . ']';
            $val   = $single ? (is_array($item) ? '' : (string) $item) : (string) ($item[$key] ?? '');
            $type  = $f['type'] ?? 'text';
            $ph    = isset($f['placeholder']) ? ' placeholder="' . e($f['placeholder']) . '"' : '';
            $ml    = isset($f['maxlength']) ? ' maxlength="' . (int) $f['maxlength'] . '"' : '';
            $lab   = !empty($f['label']) ? '<span class="rp__label">' . e($f['label']) . '</span>' : '';
            if ($type === 'textarea') {
                $ctl = '<textarea class="inp inp--area" rows="' . (int) ($f['rows'] ?? 3) . '" name="' . e($nm) . '"' . $ph . $ml . '>' . e($val) . '</textarea>';
            } elseif ($type === 'select') {
                $opts = '';
                foreach ($f['options'] ?? [] as $k => $v) $opts .= '<option value="' . e((string) $k) . '"' . ((string) $k === $val ? ' selected' : '') . '>' . e((string) $v) . '</option>';
                $ctl = '<div class="sel"><select class="inp" name="' . e($nm) . '">' . $opts . '</select></div>';
            } else {
                $ctl = '<input class="inp" type="' . e($type) . '" name="' . e($nm) . '" value="' . e($val) . '"' . $ph . $ml . '>';
            }
            $html .= '<label class="rp__field' . (!empty($f['class']) ? ' ' . e($f['class']) : '') . '">' . $lab . $ctl . '</label>';
        }
        return '<div class="rp__item" data-rp-item>'
            . '<button class="rp__handle" type="button" data-rp-handle aria-label="Sürükleyerek sırala" title="Sürükleyerek sırala">' . ui_icon('dots-six-vertical') . '</button>'
            . '<div class="rp__fields' . ($single ? ' rp__fields--single' : '') . '">' . $html . '</div>'
            . '<div class="rp__tools"><button class="rp__btn" type="button" data-rp-up aria-label="Yukarı taşı" title="Yukarı">↑</button><button class="rp__btn" type="button" data-rp-down aria-label="Aşağı taşı" title="Aşağı">↓</button>'
            . '<button class="rp__btn rp__btn--del" type="button" data-rp-del aria-label="Kaldır" title="Kaldır">' . ui_icon('trash') . '</button></div>'
            . '</div>';
    };
    $list = '';
    foreach (array_values($items) as $i => $it) $list .= $render($it, (string) $i);
    $help = !empty($o['help']) ? '<p class="fld__help">' . $o['help'] . '</p>' : '';
    return '<div class="rp' . ($single ? ' rp--single' : '') . '" data-rp data-rp-name="' . e($name) . '"' . (!empty($o['min']) ? ' data-rp-min="' . (int) $o['min'] . '"' : '') . '>'
        . ($label !== '' ? '<p class="fld__label">' . e($label) . '</p>' : '') . $help
        . '<div class="rp__items" data-rp-items>' . $list . '</div>'
        . '<template data-rp-tpl>' . $render($single ? '' : [], '__i__') . '</template>'
        . '<button class="btn btn--soft btn--sm rp__add" type="button" data-rp-add>' . ui_icon('plus') . e($add) . '</button>'
        . '</div>';
}

/** Görsel alanı: önizleme, sürükle-bırak yükleme, kaldırma. Mevcut yol gizli alanda taşınır. */
function ui_image(string $name, string $label, ?string $current, array $o = []): string
{
    $id   = ui_id($name);
    $prev = $current ? '<img src="' . e(media_url($current)) . '" alt="">' : '';
    $help = $o['help'] ?? 'JPG, PNG ya da WebP. Otomatik olarak küçültülür ve WebP biçimine çevrilir.';
    return '<div class="fld"><p class="fld__label">' . e($label) . '</p>'
        . '<div class="img' . ($current ? ' has-img' : '') . (!empty($o['contain']) ? ' img--contain' : '') . '" data-img>'
        . '<input type="hidden" name="' . e($name) . '_current" value="' . e((string) $current) . '">'
        . '<input type="hidden" name="' . e($name) . '_remove" value="0" data-img-remove>'
        . '<div class="img__preview" data-img-preview>' . $prev . '</div>'
        . '<label class="img__drop" for="' . $id . '">' . ui_icon('upload-simple') . '<span data-img-text>' . ($current ? 'Görseli değiştirmek için tıklayın ya da sürükleyin' : 'Görsel seçmek için tıklayın ya da sürükleyin') . '</span>'
        . '<input class="img__input" type="file" id="' . $id . '" name="' . e($name) . '" accept="image/jpeg,image/png,image/webp"></label>'
        . ($current && empty($o['required']) ? '<button class="btn btn--ghost btn--sm img__del" type="button" data-img-del>' . ui_icon('trash') . 'Görseli kaldır</button>' : '')
        . '</div><p class="fld__help">' . e($help) . '</p></div>';
}

/** Zengin metin editörü (blog gövdesi). Kayıtta sanitize_html() ile temizlenmelidir. */
function ui_rich(string $name, string $label, string $html, array $o = []): string
{
    $tools = [
        ['h2', 'Ara başlık', 'H2'], ['h3', 'Küçük başlık', 'H3'], ['p', 'Paragraf', '¶'],
        ['bold', 'Kalın (⌘B)', '<b>B</b>'], ['italic', 'İtalik (⌘I)', '<i>I</i>'],
        ['ul', 'Madde listesi', '•'], ['ol', 'Numaralı liste', '1.'], ['quote', 'Alıntı', '❝'],
        ['link', 'Bağlantı ekle', '↗'], ['clear', 'Biçimi temizle', '⌫'],
    ];
    $bar = '';
    foreach ($tools as [$cmd, $tip, $txt]) $bar .= '<button type="button" class="rt__btn" data-rt="' . $cmd . '" title="' . e($tip) . '" aria-label="' . e($tip) . '">' . $txt . '</button>';
    return '<div class="fld"><p class="fld__label">' . e($label) . '</p>'
        . '<div class="rt" data-rt-wrap><div class="rt__bar" role="toolbar" aria-label="Metin biçimlendirme">' . $bar . '</div>'
        . '<div class="rt__area prose-admin" contenteditable="true" role="textbox" aria-multiline="true" data-rt-area>' . $html . '</div>'
        . '<textarea name="' . e($name) . '" hidden data-rt-out>' . e($html) . '</textarea></div>'
        . (!empty($o['help']) ? '<p class="fld__help">' . $o['help'] . '</p>' : '') . '</div>';
}

/** Kart */
function ui_card(string $title, string $body, array $o = []): string
{
    $desc = !empty($o['desc']) ? '<p class="card__desc">' . $o['desc'] . '</p>' : '';
    $act  = $o['actions'] ?? '';
    return '<section class="card' . (!empty($o['class']) ? ' ' . $o['class'] : '') . '"' . (!empty($o['id']) ? ' id="' . e($o['id']) . '"' : '') . '>'
        . ($title !== '' ? '<header class="card__head"><div><h2 class="card__title">' . e($title) . '</h2>' . $desc . '</div>' . $act . '</header>' : '')
        . '<div class="card__body">' . $body . '</div></section>';
}

/* =========================================================================
   Gelen verinin okunması
   ========================================================================= */

function post_str(string $key, int $max = 2000): string
{
    $v = $_POST[$key] ?? '';
    if (!is_string($v)) return '';
    $v = str_replace(["\r\n", "\r"], "\n", trim($v));
    return mb_substr($v, 0, $max);
}

function post_bool(string $key): bool
{
    $v = $_POST[$key] ?? '0';
    return is_array($v) ? in_array('1', $v, true) : $v === '1';
}

/** Basit liste: boşlar atılır, sıra korunur. */
function post_list(string $key, int $max = 300): array
{
    $out = [];
    foreach ((array) ($_POST[$key] ?? []) as $v) {
        if (!is_string($v)) continue;
        $v = mb_substr(trim($v), 0, $max);
        if ($v !== '') $out[] = $v;
    }
    return $out;
}

/** Tekrarlanan grup: [['q' => ..., 'a' => ...], ...]; tüm alanları boş satırlar atılır. */
function post_rows(string $key, array $fields, int $max = 3000): array
{
    $out = [];
    foreach ((array) ($_POST[$key] ?? []) as $row) {
        if (!is_array($row)) continue;
        $clean = [];
        foreach ($fields as $f) {
            $v = $row[$f] ?? '';
            $clean[$f] = is_string($v) ? mb_substr(str_replace(["\r\n", "\r"], "\n", trim($v)), 0, $max) : '';
        }
        if (implode('', $clean) !== '') $out[] = $clean;
    }
    return $out;
}

/** Türkçe karakterleri sadeleştirerek adres parçası üretir. */
function adm_slug(string $text): string
{
    return slugify($text) ?: 'sayfa';
}

/** "Sitede gör" bağlantısı */
function ui_view_link(string $href, string $label = 'Sitede gör'): string
{
    return '<a class="btn btn--ghost btn--sm" href="' . e($href) . '" target="_blank" rel="noopener">' . ui_icon('arrow-square-out') . e($label) . '</a>';
}

/** Değişiklik geçmişi bağlantısı */
function ui_history_link(string $key): string
{
    $n = count(content_history($key));
    if (!$n) return '';
    return '<a class="btn btn--ghost btn--sm" href="' . adm_url('gecmis/' . $key) . '">' . ui_icon('clock-counter-clockwise') . 'Geçmiş (' . $n . ')</a>';
}

/** Sayfa içi uyarı kutusu */
function ui_alert(string $msg, string $kind = 'err'): string
{
    return '<div class="alert alert--' . e($kind) . '" role="' . ($kind === 'err' ? 'alert' : 'status') . '">' . ui_icon($kind === 'err' ? 'warning-circle' : 'check-circle') . '<div>' . $msg . '</div></div>';
}
