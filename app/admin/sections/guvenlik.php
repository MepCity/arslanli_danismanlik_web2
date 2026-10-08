<?php
/** Güvenlik ve yedek: panel şifresi, yedek indirme/geri yükleme, form güvenlik anahtarı, KVKK işareti. */

$errors = [];
$hasZip = class_exists('ZipArchive');

/* ---------- Şifre değiştir ---------- */
if (($rest[0] ?? '') === 'sifre' && $method === 'POST') {
    $cur = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['new'] ?? '');
    $rep = (string) ($_POST['repeat'] ?? '');
    if (!password_verify($cur, adm_hash()))  $errors[] = 'Mevcut şifre hatalı.';
    elseif (mb_strlen($new) < 10)            $errors[] = 'Yeni şifre en az 10 karakter olmalı.';
    elseif ($new !== $rep)                   $errors[] = 'Yeni şifreler birbiriyle aynı değil.';
    elseif (password_verify($new, adm_hash())) $errors[] = 'Yeni şifre eskisiyle aynı olamaz.';
    if (!$errors) {
        $ok = @file_put_contents(ROOT . '/storage/admin.json', json_encode(['hash' => password_hash($new, PASSWORD_DEFAULT), 'changed' => date('c')]), LOCK_EX);
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['pw'] = adm_pw_mark();   // şifreyi değiştiren yönetici oturumda kalır; diğer oturumlar kapanır
            changelog_event('guvenlik', 'Yönetim paneli şifresi değiştirildi');
            adm_flash('Şifre değiştirildi. Bir sonraki girişte yeni şifreyi kullanın.');
            adm_go('guvenlik');
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

/* ---------- Yedeği indir ---------- */
if (($rest[0] ?? '') === 'yedek' && $method === 'POST') {
    if (!$hasZip) { adm_flash('Bu sunucuda ZIP desteği kapalı; yedek oluşturulamıyor.', 'err'); adm_go('guvenlik#yedek'); }
    $withRecords = post_bool('kayitlar');
    $tmp = tempnam(sys_get_temp_dir(), 'arsl');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE) !== true) { adm_flash('Yedek dosyası hazırlanamadı.', 'err'); adm_go('guvenlik#yedek'); }
    $n = ['content' => 0, 'img' => 0];
    $addTree = function (string $dir, string $prefix, ?callable $accept) use ($zip, &$addTree, &$n): void {
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..' || $f === '.htaccess') continue;
            $full = $dir . '/' . $f;
            if (is_link($full)) continue;
            if (is_dir($full)) { $addTree($full, $prefix . $f . '/', $accept); continue; }
            if ($accept && !$accept($f)) continue;
            if (($prefix === 'content/' && $f === 'settings.json') || $prefix === 'content/_history/settings/') {
                // Ayarlar ve ayarların geçmiş sürümleri: panelden yazılmış SMTP şifresi yedeğe girmez
                [$txt] = settings_strip_secrets((string) @file_get_contents($full));
                $zip->addFromString($prefix . $f, $txt);
            } else {
                $zip->addFile($full, $prefix . $f);
            }
            $n[$accept ? 'img' : 'content']++;
        }
    };
    if (is_dir(CONTENT_DIR)) $addTree(CONTENT_DIR, 'content/', fn($f) => str_ends_with($f, '.json'));
    if (is_file(ROOT . '/storage/duyurular.json')) $zip->addFile(ROOT . '/storage/duyurular.json', 'duyurular.json');
    if (is_file(ROOT . '/storage/ilanlar.json')) $zip->addFile(ROOT . '/storage/ilanlar.json', 'ilanlar.json');
    if (is_dir(ROOT . '/uploads')) $addTree(ROOT . '/uploads', 'uploads/', fn($f) => (bool) preg_match('/\.(webp|jpe?g|png)$/i', $f));
    $readme = "Arslanlı web sitesi yedeği\r\nOluşturulma: " . date('d.m.Y H:i') . "\r\n\r\n"
        . "content/        Sayfa metinleri, hizmetler, yazılar, ayarlar ve kurumsal listeler; değişiklik geçmişi (_history) dahil.\r\n"
        . "                 E-posta (SMTP) şifresi yedeğe ve geçmiş sürümlerine GİRMEZ; geri yüklemeden sonra panelde yeniden yazmanız gerekebilir.\r\n"
        . "duyurular.json  Duyurular ve çağrı takvimi.\r\n"
        . "ilanlar.json     İş ilanları (başvurular burada değildir; bunlar kayitlar/ altındadır).\r\n"
        . "uploads/        Panelden yüklenen görseller.\r\n"
        . "bulten/bastirilanlar.json  Kaydı silinen kişilerin \"bir daha e-posta alma\" tercihi (adres değil, anahtarlı özet). Yalnızca bu sitenin form güvenlik anahtarıyla (storage/secret.key) anlamlıdır; siteyi taşırken storage/ klasörünün tamamını kopyalayın.\r\n"
        . ($withRecords ? "kayitlar/       Form kayıtları, iş başvurusu özgeçmişleri, bülten gönderimleri ve abonelikten ayrılanlar listesi. KİŞİSEL VERİ İÇERİR; güvenli saklayın, paylaşmayın (açık adresli abonelikten ayrılanlar listesi dahil).\r\n" : '')
        . "\r\nYedeğe GİRMEYENLER: panel şifresi, form güvenlik anahtarı (secret.key), bülten imza anahtarları (bulten/imza.json), erişim anahtarları, config.local.php, SMTP şifresi.\r\n"
        . "\r\nGeri yüklemek için: Yönetim > Güvenlik ve yedek > Yedekten geri yükle. Her içerik kayıttaki kurallardan yeniden geçer; bildirim ve gönderen e-posta adresleri ancak geri yüklerken kutu işaretlenirse alınır.\r\n"
        . "Form kayıtları gizlilik nedeniyle panelden geri yüklenmez.\r\n";
    $zip->addFromString('BENIOKU.txt', $readme);
    require_once APP . '/bulten.php';
    if (is_file(ROOT . '/storage/bulten/bastirilanlar.json')) $zip->addFile(ROOT . '/storage/bulten/bastirilanlar.json', 'bulten/bastirilanlar.json');   // yalnızca anahtarlı özetler: kişisel veri değil
    if ($withRecords) {
        if (is_file(ROOT . '/storage/submissions.jsonl')) $zip->addFile(ROOT . '/storage/submissions.jsonl', 'kayitlar/submissions.jsonl');
        if (is_dir(ROOT . '/storage/cv')) foreach (scandir(ROOT . '/storage/cv') ?: [] as $f) {
            $full = ROOT . '/storage/cv/' . $f;
            if ($f[0] !== '.' && is_file($full) && !is_link($full)) $zip->addFile($full, 'kayitlar/cv/' . $f);
        }
        // Bülten: abonelikten ayrılanlar, gönderimler ve taslaklar. İmza anahtarları (imza.json) ve sayaçlar yedeğe girmez.
        $bulten = ROOT . '/storage/bulten';
        if (is_file($bulten . '/ayrilanlar.json')) $zip->addFile($bulten . '/ayrilanlar.json', 'kayitlar/bulten/ayrilanlar.json');
        foreach (glob($bulten . '/gonderimler/*.json') ?: [] as $full) {
            if (is_file($full) && !is_link($full)) $zip->addFile($full, 'kayitlar/bulten/gonderimler/' . basename($full));
        }
    }
    if (!$zip->close() || !is_file($tmp)) { @unlink($tmp); adm_flash('Yedek dosyası oluşturulamadı.', 'err'); adm_go('guvenlik#yedek'); }
    adm_state(['last_backup' => time()]);
    $name = 'arslanli-yedek-' . date('Y-m-d') . '.zip';
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}

/* ---------- Yedekten geri yükle ---------- */
if (($rest[0] ?? '') === 'geri-yukle' && $method === 'POST') {
    changelog_note('Yedekten geri yükleme');
    $back = function (string $msg, string $kind = 'err') { adm_flash($msg, $kind); adm_go('guvenlik#yedek'); };
    if (!$hasZip) $back('Bu sunucuda ZIP desteği kapalı.');
    $up = $_FILES['yedek'] ?? null;
    if (!is_array($up) || ($up['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) $back('Önce bir yedek dosyası seçin.');
    if ($up['error'] === UPLOAD_ERR_INI_SIZE || $up['error'] === UPLOAD_ERR_FORM_SIZE) $back('Dosya sunucunun yükleme sınırını aşıyor (' . ini_get('upload_max_filesize') . ').');
    if ($up['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($up['tmp_name'])) $back('Dosya yüklenemedi. Tekrar deneyin.');
    if ($up['size'] > 50 * 1024 * 1024) $back('Yedek dosyası en fazla 50 MB olabilir.');
    if (strtolower(pathinfo((string) $up['name'], PATHINFO_EXTENSION)) !== 'zip') $back('Yalnızca bu panelden indirilen .zip yedek dosyası yüklenebilir.');
    $zip = new ZipArchive();
    if ($zip->open($up['tmp_name']) !== true) $back('Dosya açılamadı; geçerli bir ZIP yedeği değil.');

    $maxEach = 20 * 1024 * 1024; $maxAll = 200 * 1024 * 1024;
    $total = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) $total += (int) ($zip->statIndex($i)['size'] ?? 0);
    if ($total > $maxAll) { $zip->close(); $back('Yedek açıldığında çok büyük (200 MB üstü); güvenlik için işlem durduruldu.'); }

    $read = function (int $i, int $size) use ($zip, $maxEach): ?string {
        if ($size > $maxEach) return null;
        $fp = $zip->getStream($zip->getNameIndex($i));
        if (!$fp) return null;
        $data = stream_get_contents($fp, $maxEach + 1);
        fclose($fp);
        return ($data === false || strlen($data) > $maxEach) ? null : $data;
    };
    $nContent = 0; $nImg = 0; $skipped = 0; $recordsSeen = false;
    $stores = [];       // anahtar => çözülmüş içerik: önce hepsi okunur, sonra bağımlılık sırasıyla doğrulanıp yazılır
    $report = [];       // geri yüklenmeyen öğeler ve bilgi notları
    $engelNote = '';
    $infoNotes = [];     // bilgi notları: neyin değiştiği / değişmediği (reddedilen öğe değil)
    $mailRoutes = post_bool('posta_adresleri');   // varsayılan kapalı: yedek, bildirimlerin gideceği adresi değiştiremez
    $histKeys = array_keys(array_filter(changelog_sections(), fn($x) => !empty($x[2])));
    $secretsNote = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $st = $zip->statIndex($i);
        $nm = (string) ($st['name'] ?? '');
        if ($nm === '' || str_ends_with($nm, '/') || $nm === 'BENIOKU.txt') continue;
        if (str_starts_with($nm, 'kayitlar/')) { $recordsSeen = true; $skipped++; continue; }
        // Yol güvenliği: üst klasöre çıkma, mutlak yol, ters eğik çizgi, boş bayt
        if (str_contains($nm, '..') || $nm[0] === '/' || str_contains($nm, '\\') || str_contains($nm, "\0") || preg_match('/^[A-Za-z]:/', $nm)) { $skipped++; continue; }
        $size = (int) ($st['size'] ?? 0);

        if (preg_match('#^content/([a-z_]+)\.json$#', $nm, $m)) {
            // Yalnızca bilinen içerik depoları; her biri aşağıda normal kayıtla aynı doğrulamadan geçer
            if (!in_array($m[1], restore_stores(), true)) { $skipped++; continue; }
            $raw = $read($i, $size);
            $val = $raw === null ? null : json_decode($raw, true);
            if (!is_array($val)) { $skipped++; continue; }
            $stores[$m[1]] = $val;
        } elseif (preg_match('#^content/_history/([a-z_]+)/(\d{8}-\d{6}-[a-f0-9]{4})\.json$#', $nm, $m)) {
            // Geçmiş sürümleri alınır (değişiklik geçmişi yedekle birlikte taşınsın) ama HAM İÇERİK OLARAK UYGULANMAZ: bir sürüm geri alınırken
            // changelog_restore() onu normal kayıtla aynı kurallardan geçirir (app/restore.php). Burada yalnızca biçim denetlenir; SMTP şifresi çıkarılır.
            if (!in_array($m[1], $histKeys, true)) { $skipped++; continue; }
            $raw = $read($i, $size);
            $val = $raw === null ? null : json_decode($raw, true);
            if ($val === null && trim((string) $raw) !== 'null') { $skipped++; continue; }
            if ($m[1] === 'settings') [$raw] = settings_strip_secrets((string) $raw);
            $dir = CONTENT_DIR . '/_history/' . $m[1];
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { $skipped++; continue; }
            if (!is_file($dir . '/' . $m[2] . '.json')) @file_put_contents($dir . '/' . $m[2] . '.json', $raw, LOCK_EX);
            $nContent++;
        } elseif ($nm === 'bulten/bastirilanlar.json') {
            $raw = $read($i, $size);
            $val = $raw === null ? null : json_decode($raw, true);
            if (!is_array($val)) { $skipped++; continue; }
            require_once APP . '/bulten.php';
            $nContent++;
            $engelNote = ' Bülten engel listesi: ' . bulten_engel_birlestir($val) . ' yeni kayıt birleştirildi (özetler yalnızca bu sitenin form güvenlik anahtarıyla eşleşir).';
        } elseif ($nm === 'duyurular.json' || $nm === 'ilanlar.json') {
            $raw = $read($i, $size);
            $val = $raw === null ? null : json_decode($raw, true);
            if (!is_array($val)) { $skipped++; continue; }
            $stores[$nm === 'duyurular.json' ? 'duyurular' : 'ilanlar'] = $val;
        } elseif (preg_match('#^uploads/([a-z0-9_-]+)/([A-Za-z0-9_-]+\.(?:webp|jpe?g|png))$#D', $nm, $m)) {
            // Görsel adında tek nokta olabilir: "x.php.png" gibi adlar eşleşmez ve aşağıda atlanır
            $raw = $read($i, $size);
            $info = $raw === null ? false : @getimagesizefromstring($raw);
            if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $m[2][0] === '.') { $skipped++; continue; }
            $dir = ROOT . '/uploads/' . $m[1];
            if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { $skipped++; continue; }
            $tmpf = $dir . '/.' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($tmpf, $raw, LOCK_EX) === false || !@rename($tmpf, $dir . '/' . $m[2])) { @unlink($tmpf); $skipped++; continue; }
            $nImg++;
        } else {
            $skipped++;
        }
    }
    // Bağımlılık sırası: hizmetler hedef eşleştiriciden (kurumsal listeler) önce
    $order = ['services', 'refs', 'posts', 'lists', 'texts', 'legal', 'features', 'settings', 'seo', 'duyurular', 'ilanlar'];
    uksort($stores, fn($a, $b) => array_search($a, $order, true) <=> array_search($b, $order, true));
    $secName = changelog_sections();
    foreach ($stores as $key => $val) {
        $label = $secName[$key][0] ?? $key;
        if (restore_store($key, $val, false, ['mail_routes' => $mailRoutes])) {
            $nContent++;
            if ($key === 'settings' && is_array($val['mail']['smtp'] ?? null) && !empty($val['mail']['smtp']['host'])) $secretsNote = true;   // yalnızca yedekte SMTP ayarı varsa
        } else {
            $skipped++;
        }
        $rep = restore_report();
        if ($rep['error'] !== null) $report[] = $label . ': ' . $rep['error'];
        foreach ($rep['dropped'] as $line) $report[] = $label . ': ' . $line;
        foreach ($rep['notes'] as $line) $infoNotes[] = $label . ': ' . $line;
    }
    $zip->close();
    if (!$nContent && !$nImg) $back("Bu dosyada geri yüklenecek içerik bulunamadı (" . $skipped . " dosya atlandı)." . ($report ? ' Nedenleri: ' . restore_report_text($report, 8) : ' Panelden indirdiğiniz bir yedek seçtiğinizden emin olun.'));
    $msg = $nContent . ' içerik dosyası, ' . $nImg . ' görsel geri yüklendi; ' . $skipped . ' dosya atlandı.';
    if ($report) $msg .= ' Normal kayıttaki kurallara uymayan öğeler alınmadı: ' . restore_report_text($report, 8);
    if ($infoNotes) $msg .= ' Bilgi: ' . restore_report_text($infoNotes, 8);
    $msg .= $engelNote;
    if ($secretsNote) $msg .= ' E-posta (SMTP) şifresi yedeğe girmediği için geri yüklenmedi; panelde SMTP kayıtlıysa ve bağlantı bilgisi aynıysa şu anki şifre korundu, değilse İletişim ve şirket sayfasında şifreyi yeniden yazın.';
    if ($recordsSeen) $msg .= ' Form kayıtları gizlilik nedeniyle geri yüklenmez.';
    if (ilan_restore_opened()) $msg .= ' DİKKAT: şu iş ilanları geri yüklemeyle başvuruya açıldı: ' . implode(', ', ilan_restore_opened()) . '.';
    $back($msg, 'ok');
}

/* ---------- Form güvenlik anahtarı ---------- */
if (($rest[0] ?? '') === 'anahtar' && $method === 'POST') {
    // Eski anahtar saklanır: gönderilmiş e-postalardaki ayrılma bağlantıları ve kaydı silinen kişilerin "bir daha e-posta alma" kayıtları
    // (adres yerine anahtarlı özet olarak durur) onunla bulunur; saklanmazsa yeni anahtar bu kayıtları sessizce geçersiz kılardı.
    require_once APP . '/bulten.php';
    bulten_imza_arsivle();
    $ok = @file_put_contents(ROOT . '/storage/secret.key', bin2hex(random_bytes(32)), LOCK_EX);
    if ($ok) {
        @chmod(ROOT . '/storage/secret.key', 0600);
        changelog_event('guvenlik', 'Yeni form güvenlik anahtarı oluşturuldu');
        adm_flash('Yeni form güvenlik anahtarı oluşturuldu.');
    } else {
        adm_flash('Anahtar kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
    }
    adm_go('guvenlik#anahtar');
}

/* ---------- KVKK işareti ---------- */
if (($rest[0] ?? '') === 'kvkk' && $method === 'POST') {
    $on = ($_POST['do'] ?? '') === 'isaretle';
    adm_state(['kvkk_reviewed' => $on ? time() : 0]);
    adm_flash($on ? 'KVKK ve çerez metinleri kontrol edildi olarak işaretlendi.' : 'İşaret kaldırıldı.');
    adm_go('guvenlik#kvkk');
}

$changed = null;
if (is_file(ROOT . '/storage/admin.json')) $changed = json_decode((string) file_get_contents(ROOT . '/storage/admin.json'), true)['changed'] ?? null;

$state      = adm_state();
$now        = time();
$lastBackup = (int) ($state['last_backup'] ?? 0);
$kvkk       = (int) ($state['kvkk_reviewed'] ?? 0);
$defSecret  = (string) ((require APP . '/config.php')['secret'] ?? '');
$keyFile    = is_file(ROOT . '/storage/secret.key');
$customKey  = $keyFile || cfg('secret') !== $defSecret;
$pwChanged  = is_file(ROOT . '/storage/admin.json');
$backupOk   = $lastBackup > $now - 30 * 86400;
$limit      = ini_get('upload_max_filesize');

$summary = [
    [$pwChanged, $pwChanged ? 'Panel şifresi değiştirildi' : 'İlk şifre kullanılıyor', $pwChanged ? 'İlk şifre artık geçersiz.' : 'Kurulumdaki ilk şifre hâlâ kullanılıyor.', '#sifre'],
    [$customKey, $customKey ? 'Form anahtarı özel' : 'Form anahtarı varsayılan', $customKey ? 'Formlar size özel anahtarla imzalanıyor.' : 'Varsayılan anahtar kullanılıyor.', '#anahtar'],
    [$backupOk, $backupOk ? 'Yedek güncel' : 'Yedek gerekli', $backupOk ? 'Son yedek: ' . date('d.m.Y', $lastBackup) : ($lastBackup ? 'Son yedek 30 günden eski.' : 'Henüz yedek alınmadı.'), '#yedek'],
    [$kvkk > 0, $kvkk > 0 ? 'KVKK metinleri kontrol edildi' : 'KVKK hukukçu onayı bekliyor', $kvkk > 0 ? 'İşaretlenme: ' . date('d.m.Y', $kvkk) : 'Hukuk danışmanı onayı işaretlenmedi.', '#kvkk'],
];
$okCount = count(array_filter($summary, fn($r) => $r[0]));
$health = '';
foreach ($summary as [$ok, $t, $d, $link]) {
    $health .= '<div class="health__row ' . ($ok ? 'is-ok' : 'is-warn') . '">' . ui_icon($ok ? 'check-circle' : 'warning-circle')
        . '<div><p class="health__t">' . e($t) . '</p><p class="health__d">' . e($d) . '</p></div>'
        . ($ok ? '<span></span>' : '<a class="btn btn--soft btn--sm" href="' . e($link) . '">Düzelt</a>') . '</div>';
}

ob_start();
if ($errors) echo ui_alert(e(implode(' ', $errors)));

/* Yedek kartı */
if ($hasZip) {
    $backup = '<div class="ay__block"><h3>Yedeği indir</h3>'
        . '<p class="muted">Sayfa metinleri, hizmetler, yazılar, ayarlar, duyurular, iş ilanları, değişiklik geçmişi ve yüklenen görseller tek bir ZIP dosyasında iner. Panelden yazdığınız e-posta (SMTP) şifresi yedeğe ve geçmiş sürümlerine girmez.</p>'
        . '<form method="post" action="' . adm_url('guvenlik/yedek') . '" class="ay__block" style="gap:16px">' . adm_csrf_field()
        . ui_toggle('kayitlar', 'Form kayıtlarını ve özgeçmişleri de ekle (kişisel veri içerir)', false, ['help' => 'Yedeği güvenli bir yerde saklayın ve başkalarıyla paylaşmayın.'])
        . '<div><button class="btn" type="submit">' . ui_icon('download-simple') . 'Yedeği indir</button></div></form>'
        . '<p class="ay__meta">' . ($lastBackup ? 'Son yedek: ' . e(tr_date(date('Y-m-d', $lastBackup))) . ', ' . date('H:i', $lastBackup) . '.' : 'Henüz yedek alınmadı.') . '</p></div>'
        . '<div class="ay__block"><h3>Yedekten geri yükle</h3>'
        . '<p class="muted">Bu panelden indirdiğiniz bir yedeği seçin; içindeki metinler, ayarlar, duyurular, iş ilanları ve görseller sitedeki güncel halin üzerine yazılır; her içerik, panelden kaydederken uygulanan kurallardan yeniden geçer ve kurallara uymayan öğeler alınmaz (hangileri olduğu sonuçta yazılır). İşlemden önce kendi yedeğinizi almanız önerilir. Form kayıtları ve özgeçmişler gizlilik nedeniyle geri yüklenmez.</p>'
        . '<form method="post" action="' . adm_url('guvenlik/geri-yukle') . '" enctype="multipart/form-data" class="ay__block" style="gap:16px" data-confirm="Yedekteki içerik sitedeki mevcut içeriğin üzerine yazılacak. Devam edilsin mi?">' . adm_csrf_field()
        . '<div class="fld"><label class="fld__label" for="f-yedek">Yedek dosyası (.zip)</label><input class="inp ay__file" type="file" id="f-yedek" name="yedek" accept=".zip,application/zip" required aria-describedby="f-yedek-h">'
        . '<p class="fld__help" id="f-yedek-h">En fazla 50 MB. Sunucunuzun yükleme sınırı ' . e((string) $limit) . '.</p></div>'
        . ui_toggle('posta_adresleri', 'Bildirim ve gönderen e-posta adreslerini de geri yükle', false, ['help' => 'Kapalıyken yedekteki ayarlardan “bildirimlerin gideceği adres” ve “gönderen adres” alınmaz; şu anki adresler kalır. Yalnızca kendi aldığınız bir yedeği geri yüklüyorsanız işaretleyin: formlardan gelen kişisel veriler bu adrese e-postalanır.'])
        . '<div><button class="btn btn--ghost" type="submit">' . ui_icon('upload-simple') . 'Geri yükle</button></div></form></div>';
} else {
    $backup = ui_alert('Bu sunucuda ZIP desteği (ZipArchive) kapalı olduğu için yedek alınamıyor. Barındırma firmanızdan "php-zip" eklentisini açmasını isteyin.', 'warn');
}
?>
<div class="split">
  <div style="display:grid;gap:20px">
    <?= ui_card('Panel şifresi', '<form method="post" action="' . adm_url('guvenlik/sifre') . '" style="display:grid;gap:16px;max-width:420px">' . adm_csrf_field()
        . ui_text('current', 'Mevcut şifre', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password'])
        . ui_text('new', 'Yeni şifre', '', ['type' => 'password', 'required' => true, 'minlength' => 10, 'autocomplete' => 'new-password', 'help' => 'En az 10 karakter. Kelime yerine kısa bir cümle seçmek hem güvenli hem akılda kalıcıdır.'])
        . ui_text('repeat', 'Yeni şifre (tekrar)', '', ['type' => 'password', 'required' => true, 'minlength' => 10, 'autocomplete' => 'new-password'])
        . '<div><button class="btn" type="submit">' . ui_icon('lock-key') . 'Şifreyi değiştir</button></div></form>', [
        'id'   => 'sifre',
        'desc' => $changed ? 'Son değişiklik: ' . e(tr_date(substr($changed, 0, 10))) . '.' : 'Kurulumdaki varsayılan şifre kullanılıyor. İlk girişten sonra değiştirmeniz önerilir.',
    ]) ?>

    <?= ui_card('Yedek', $backup, ['id' => 'yedek', 'desc' => 'İçeriğinizin bir kopyasını bilgisayarınıza indirin; gerekirse buradan geri yükleyin.']) ?>

    <?= ui_card('Form güvenlik anahtarı',
        '<p class="ay__status">' . ui_icon($customKey ? 'check-circle' : 'warning-circle') . '<span>' . ($customKey ? '<strong>Özel anahtar kullanılıyor.</strong> Formlar size özel bir anahtarla imzalanıyor.' : '<strong>Varsayılan anahtar kullanılıyor.</strong> Canlıya almadan önce yeni bir anahtar oluşturun.') . '</span></p>'
        . '<p class="muted">Sitedeki formlar (iletişim, bülten, iş başvurusu) bu anahtarla imzalanır; böylece otomatik gönderim yapan programlar engellenir. Yeni anahtar oluşturduğunuzda o anda formu açık olan ziyaretçilerin sayfayı bir kez yenilemesi gerekebilir. Önceki anahtar sunucuda (storage/bulten/imza.json) saklanır; böylece gönderilmiş bültenlerdeki ayrılma bağlantıları ve kaydı silinen kişilerin “bir daha e-posta alma” tercihi geçerli kalır. O dosyayı silmeyin: silinirse bu tercihler tanınmaz ve bu kişilere yeniden e-posta gidebilir.</p>'
        . '<form method="post" action="' . adm_url('guvenlik/anahtar') . '" data-confirm="Yeni bir form güvenlik anahtarı oluşturulsun mu?">' . adm_csrf_field()
        . '<button class="btn btn--ghost" type="submit">' . ui_icon('lock-key') . 'Yeni anahtar oluştur</button></form>',
        ['id' => 'anahtar']) ?>

    <?= ui_card('KVKK ve çerez metinleri',
        '<p class="ay__status">' . ui_icon($kvkk > 0 ? 'check-circle' : 'warning-circle') . '<span>' . ($kvkk > 0 ? '<strong>Kontrol edildi.</strong> İşaretlenme tarihi: ' . e(tr_date(date('Y-m-d', $kvkk))) . '.' : '<strong>Henüz işaretlenmedi.</strong> Metinler sitenin gerçek işleyişine göre hazırlandı; hukukçu onayı bekliyor.') . '</span></p>'
        . '<p class="muted">KVKK Aydınlatma Metni ve Çerez Politikası, sitenin topladığı veriler ve kullandığı teknolojilere göre hazırlandı. Hukuk danışmanınız okuduktan sonra aşağıdan işaretleyin.' . (isset(adm_pending_sections()['metinler']) ? '' : ' Metinleri Sayfa metinleri bölümünden düzenleyebilirsiniz.') . '</p>'
        . '<div class="ay__row">'
        . '<form method="post" action="' . adm_url('guvenlik/kvkk') . '">' . adm_csrf_field()
        . ($kvkk > 0
            ? '<input type="hidden" name="do" value="kaldir"><button class="btn btn--ghost" type="submit">İşareti kaldır</button>'
            : '<input type="hidden" name="do" value="isaretle"><button class="btn" type="submit">' . ui_icon('check-circle') . 'Hukuk danışmanı kontrol etti olarak işaretle</button>')
        . '</form>'
        . ui_view_link(url('kurumsal/kvkk-aydinlatma-metni'), 'KVKK metnini aç')
        . ui_view_link(url('kurumsal/cerez-politikasi'), 'Çerez politikasını aç')
        . (isset(adm_pending_sections()['metinler']) ? '' : '<a class="btn btn--ghost btn--sm" href="' . adm_url('metinler') . '">' . ui_icon('text-aa') . 'Sayfa metinleri</a>')
        . '</div>',
        ['id' => 'kvkk']) ?>
  </div>
  <aside class="split__side">
    <?= ui_card('Güvenlik durumu', '<div class="health">' . $health . '</div>', ['desc' => $okCount . ' / ' . count($summary) . ' kontrol tamam.']) ?>
    <?= ui_card('Oturum', '<p class="muted" style="margin-bottom:12px">Paneli 8 saat kullanmazsanız oturum kendiliğinden kapanır. Ortak bir bilgisayardaysanız işiniz bitince çıkış yapın.</p>'
        . '<form method="post" action="' . adm_url('cikis') . '">' . adm_csrf_field() . '<button class="btn btn--ghost btn--sm" type="submit">' . ui_icon('sign-out') . 'Çıkış yap</button></form>') ?>
  </aside>
</div>
<?php
adm_layout('Güvenlik ve yedek', (string) ob_get_clean(), [
    'section'  => 'guvenlik',
    'subtitle' => 'Panel şifresi, yedekleme, form anahtarı ve KVKK onayı.',
]);
