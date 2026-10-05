<?php
/** Genel bakış */

$now    = time();
$today  = date('Y-m-d');
$hour   = (int) date('G');
$hello  = $hour < 5 ? 'İyi geceler' : ($hour < 12 ? 'Günaydın' : ($hour < 18 ? 'İyi günler' : ($hour < 23 ? 'İyi akşamlar' : 'İyi geceler')));
$days   = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
$months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

$anns      = ann_all();
$published = array_values(array_filter($anns, fn($a) => !empty($a['published'])));
$events    = array_values(array_filter(ann_events($published), fn($e) => $e['date'] >= $today));
$weekEnd   = date('Y-m-d', strtotime('+7 days'));
$closing   = count(array_filter($events, fn($e) => $e['type'] === 'son' && $e['date'] <= $weekEnd));

$allRecs   = adm_records();
$records   = array_values(array_filter($allRecs, fn($r) => empty($r['spam'])));   // şüpheli olarak ayrılanlar sayılmaz, "Son gelenler"de görünmez
// Şüpheli olarak ayrılanların sayısı: e-posta gönderilmediği için yönetici onları yalnızca buradan ve ilgili listeden fark eder
$susForms  = count(array_filter($allRecs, fn($r) => !empty($r['spam']) && ($r['form'] ?? '') !== 'kariyer'));
$susApps   = count(array_filter($allRecs, fn($r) => !empty($r['spam']) && ($r['form'] ?? '') === 'kariyer'));
$unread    = adm_unread();
$apps      = array_values(array_filter($records, fn($r) => ($r['form'] ?? '') === 'kariyer'));
$forms     = array_values(array_filter($records, fn($r) => ($r['form'] ?? '') !== 'kariyer'));
$subs      = count(array_filter($records, fn($r) => in_array($r['form'] ?? '', ['bulten', 'haberdarol'], true)));
$recent    = array_slice(array_reverse($records), 0, 6);
$formNames = ['bulten' => ['Bülten', 'navy'], 'iletisim' => ['İletişim', 'ok'], 'haberdarol' => ['Haberdar ol', 'navy'], 'kariyer' => ['İş başvurusu', 'coral']];

function ago(int $t): string
{
    $d = time() - $t;
    if ($d < 60) return 'az önce';
    if ($d < 3600) return floor($d / 60) . ' dk önce';
    if ($d < 86400) return floor($d / 3600) . ' sa önce';
    if ($d < 86400 * 7) return floor($d / 86400) . ' gün önce';
    return date('d.m.Y', $t);
}

/* ---------- Site sağlığı ---------- */
$state  = adm_state();
$health = [];
$health[] = is_file(ROOT . '/storage/admin.json')
    ? [true, 'Yönetici şifresi değiştirildi', 'Kurulumla gelen ilk şifre artık geçersiz.', '']
    : [false, 'İlk şifre hâlâ kullanılıyor', 'Güvenliğiniz için şifrenizi değiştirin.', adm_url('guvenlik') . '#sifre'];
$health[] = (is_file(ROOT . '/storage/secret.key') || cfg('secret') !== 'degistir-bunu-uzun-rastgele-bir-anahtar-ile-7f3a9c')
    ? [true, 'Form güvenlik anahtarı özel', 'Formlar size özel bir anahtarla imzalanıyor.', '']
    : [false, 'Form güvenlik anahtarı varsayılan', 'Tek tıkla yeni bir anahtar oluşturun.', adm_url('guvenlik') . '#anahtar'];
$samples = count(array_filter($published, fn($a) => !empty($a['sample'])));
$health[] = $samples
    ? [false, $samples . ' örnek duyuru yayında', 'Gerçek bilgilerle güncelleyin ya da silin.', adm_url('duyurular')]
    : [true, 'Örnek duyuru yok', 'Yayındaki tüm duyurular gerçek.', ''];
$failLog = ROOT . '/storage/mail-failures.log';
$recentFail = is_file($failLog) && filemtime($failLog) > $now - 7 * 86400;
$health[] = $recentFail
    ? [false, 'Son 7 günde e-posta iletilemedi', 'Kayıtlar panelde duruyor; e-posta ayarlarını kontrol edin.', adm_url('ayarlar') . '#eposta']
    : [true, 'E-posta gönderiminde sorun kaydı yok', cfg('mail.smtp') ? 'SMTP ile gönderiliyor.' : 'Sunucunun e-posta fonksiyonu kullanılıyor.', ''];
$lastBackup = (int) ($state['last_backup'] ?? 0);
$health[] = $lastBackup > $now - 30 * 86400
    ? [true, 'Yedek güncel', 'Son yedek: ' . date('d.m.Y', $lastBackup), '']
    : [false, $lastBackup ? 'Son yedek 30 günden eski' : 'Henüz yedek alınmadı', 'İçeriklerinizin yedeğini indirin.', adm_url('guvenlik') . '#yedek'];
$health[] = !empty($state['kvkk_reviewed'])
    ? [true, 'KVKK metni gözden geçirildi', 'İşaretlenme: ' . date('d.m.Y', (int) $state['kvkk_reviewed']), '']
    : [false, 'KVKK metni hukukçu onayı bekliyor', 'Metin yayına hazır; hukuk danışmanınız okuduktan sonra işaretleyin.', adm_url('guvenlik') . '#kvkk'];
$okCount = count(array_filter($health, fn($h) => $h[0]));

ob_start(); ?>
<section class="hello">
  <div>
    <h2><?= $hello ?></h2>
    <p><?= date('j') . ' ' . $months[(int) date('n') - 1] . ' ' . date('Y') . ', ' . $days[(int) date('w')] ?>. <?= $closing ? $closing . ' çağrının son başvuru günü bu hafta.' : 'Bu hafta kapanan bir çağrı görünmüyor.' ?></p>
    <?php if ($feat = ann_featured()): ?><p class="hello__feat"><?= ui_icon('sparkle') ?><span>Açılışta öne çıkan: <a href="<?= adm_url('duyurular/' . $feat['id']) ?>"><?= e($feat['title']) ?></a> · <?= e(tr_date(ann_featured_until($feat))) ?> sonunda kendiliğinden kalkar.</span></p><?php endif; ?>
  </div>
  <div class="hello__actions">
    <a class="btn" href="<?= adm_url('duyurular/yeni') ?>"><?= ui_icon('plus') ?>Yeni duyuru</a>
    <a class="btn btn--ghost" href="<?= adm_url('blog/yeni') ?>"><?= ui_icon('newspaper') ?>Yeni yazı</a>
    <?php if (!isset(adm_pending_sections()['metinler'])): ?><a class="btn btn--ghost" href="<?= adm_url('metinler') ?>"><?= ui_icon('text-aa') ?>Metinleri düzenle</a><?php endif; ?>
  </div>
</section>

<section class="stats" aria-label="Özet">
  <a class="stat" href="<?= adm_url('duyurular') ?>">
    <span class="stat__top">Yayındaki duyurular <?= ui_icon('megaphone') ?></span>
    <span class="stat__n"><?= count($published) ?></span>
    <span class="stat__note"><?= $closing ? '<b>' . $closing . '</b> tanesi bu hafta kapanıyor' : count($events) . ' yaklaşan tarih' ?></span>
  </a>
  <a class="stat" href="<?= adm_url('kayitlar') ?>">
    <span class="stat__top">Form kayıtları <?= ui_icon('tray') ?></span>
    <span class="stat__n"><?= count($forms) ?></span>
    <span class="stat__note"><?= ($unread['kayitlar'] ? '<b>' . $unread['kayitlar'] . ' yeni</b> kayıt var' : 'Okunmamış kayıt yok') . ($susForms ? ' · <b>' . $susForms . '</b> şüpheli bekliyor' : '') ?></span>
  </a>
  <a class="stat" href="<?= adm_url('basvurular') ?>">
    <span class="stat__top">İş başvuruları <?= ui_icon('briefcase') ?></span>
    <span class="stat__n"><?= count($apps) ?></span>
    <span class="stat__note"><?= ($unread['basvurular'] ? '<b>' . $unread['basvurular'] . ' yeni</b> başvuru var' : 'Yeni başvuru yok') . ($susApps ? ' · <b>' . $susApps . '</b> şüpheli bekliyor' : '') ?></span>
  </a>
  <a class="stat" href="<?= adm_url('kayitlar') ?>?tur=bulten">
    <span class="stat__top">Bülten kayıtları <?= ui_icon('envelope-simple') ?></span>
    <span class="stat__n"><?= $subs ?></span>
    <span class="stat__note">Bülten ve Haberdar Ol formları</span>
  </a>
</section>

<div class="dash">
  <div style="display:grid;gap:20px">
    <?php
    $tl = '';
    foreach (array_slice($events, 0, 8) as $ev) {
        $dt   = strtotime($ev['date']);
        $left = (int) round(($dt - strtotime($today)) / 86400);
        $txt  = $left === 0 ? 'Bugün' : ($left === 1 ? 'Yarın' : $left . ' gün');
        $tl  .= '<a class="tl__row' . ($left <= 7 ? ' is-soon' : '') . '" href="' . adm_url('duyurular/' . $ev['id']) . '">'
            . '<span class="tl__date"><b>' . date('j', $dt) . '</b><small>' . mb_substr($months[(int) date('n', $dt) - 1], 0, 3) . '</small></span>'
            . '<span><span class="tl__title">' . e($ev['title']) . '</span><span class="tl__type"><i class="mk mk--' . e($ev['type']) . '"></i>' . e($ev['label']) . ($ev['kurum'] ? ' · ' . e($ev['kurum']) : '') . '</span></span>'
            . '<span class="badge' . ($left <= 7 ? ' badge--coral' : '') . '">' . $txt . '</span></a>';
    }
    echo ui_card('Yaklaşan tarihler', $tl ? '<div class="tl" style="margin:-18px -22px -22px">' . $tl . '</div>' : '<div class="empty">' . ui_icon('calendar-blank') . '<strong>Yaklaşan bir tarih yok</strong><span>Duyurulara tarih eklediğinizde burada ve sitedeki takvimde görünür.</span><a class="btn btn--soft btn--sm" href="' . adm_url('duyurular/yeni') . '">Duyuru ekle</a></div>', [
        'desc'    => 'Sitedeki takvimde işaretli olan, bugünden sonraki tarihler.',
        'actions' => '<a class="btn btn--ghost btn--sm" href="' . adm_url('duyurular') . '">Tüm duyurular</a>',
    ]);

    $rc = '';
    foreach ($recent as $r) {
        $dd   = $r['data'] ?? [];
        $name = trim(($dd['ad'] ?? $dd['isimsoyisim'] ?? $dd['namesurname'] ?? '') . ' ' . ($dd['soyad'] ?? $dd['isimsoyisim2'] ?? ''));
        [$fl, $fc] = $formNames[$r['form'] ?? ''] ?? ['Form', 'navy'];
        $href = ($r['form'] ?? '') === 'kariyer' ? adm_url('basvurular') : adm_url('kayitlar');
        $rc  .= '<a class="list__row" href="' . $href . '"><span class="list__main"><span class="list__title">' . e($name ?: ($dd['email'] ?? 'İsimsiz')) . '</span>'
            . '<span class="list__meta"><span>' . e($dd['email'] ?? '') . '</span><span>' . ago(strtotime($r['time'] ?? 'now') ?: time()) . '</span></span></span>'
            . '<span class="list__side"><span class="badge badge--' . $fc . '">' . $fl . '</span></span></a>';
    }
    echo ui_card('Son gelenler', $rc ? '<div class="list">' . $rc . '</div>' : '<div class="empty">' . ui_icon('tray') . '<strong>Henüz kayıt yok</strong><span>İletişim, bülten ve iş başvurusu formlarından gelenler burada listelenir.</span></div>', [
        'actions' => '<a class="btn btn--ghost btn--sm" href="' . adm_url('kayitlar') . '">Tümü</a>',
    ]);
    ?>
  </div>

  <div style="display:grid;gap:20px">
    <?php
    // Ziyaretçiler: bugün, son 7 ve son 30 gün (ayrıntı: Ziyaretçiler bölümü)
    require_once APP . '/stats.php';
    $vz    = stats_days(30);
    $vzDay = end($vz);
    $vz7   = stats_totals(array_slice($vz, -7, null, true));
    $vz30  = stats_totals($vz);
    $vzN   = fn(int $x): string => number_format($x, 0, ',', '.');
    echo ui_card('Ziyaretçiler', '<div class="vz-mini"><div><b>' . $vzN($vzDay['u']) . '</b><span>Bugün</span></div><div><b>' . $vzN($vz7['u']) . '</b><span>Son 7 gün</span></div><div><b>' . $vzN($vz30['u']) . '</b><span>Son 30 gün</span></div></div>', [
        'desc'    => 'Siteye gelen ziyaretçi sayısı. Son 30 günde ' . $vzN($vz30['v']) . ' sayfa görüntülendi.',
        'actions' => '<a class="btn btn--ghost btn--sm" href="' . adm_url('ziyaretciler') . '">Ayrıntılar</a>',
    ]);

    $hh = '';
    foreach ($health as [$ok, $t, $desc, $link]) {
        $hh .= '<div class="health__row ' . ($ok ? 'is-ok' : 'is-warn') . '">' . ui_icon($ok ? 'check-circle' : 'warning-circle')
            . '<div><p class="health__t">' . e($t) . '</p><p class="health__d">' . e($desc) . '</p></div>'
            . ($link ? '<a class="btn btn--soft btn--sm" href="' . e($link) . '">Düzelt</a>' : '<span></span>') . '</div>';
    }
    echo ui_card('Site sağlığı', '<div class="health">' . $hh . '</div>', [
        'desc' => $okCount . ' / ' . count($health) . ' kontrol tamam.',
    ]);

    $links = [
        ['hizmetler', 'stack', 'Hizmetler', count(services()) . ' hizmet alanı'],
        ['blog', 'newspaper', 'Yazılar', count($GLOBALS['posts']) . ' yazı' . (feature('blog') ? '' : ' · sitede kapalı')],
        ['referanslar', 'images', 'Referanslar', count(refs_list()) . ' logo'],
        ['metinler', 'text-aa', 'Sayfa metinleri', 'Başlıklar ve açıklamalar'],
        ['ayarlar', 'gear', 'İletişim ve şirket', 'Telefon, adres, sosyal medya'],
    ];
    $q = '';
    foreach ($links as [$p, $ic, $t, $m]) {
        if (isset(adm_pending_sections()[$p])) continue;   // sonraki aşamada uyarlanacak bölümler bağlantı vermez
        $q .= '<a class="list__row" href="' . adm_url($p) . '"><span class="list__main"><span class="list__title" style="display:flex;gap:10px;align-items:center">' . ui_icon($ic) . e($t) . '</span><span class="list__meta">' . e($m) . '</span></span><span class="list__go">' . ui_icon('caret-right') . '</span></a>';
    }
    echo ui_card('İçerik', '<div class="list">' . $q . '</div>');
    ?>
  </div>
</div>
<?php
adm_layout('Genel bakış', (string) ob_get_clean(), ['section' => 'genel', 'actions' => ui_view_link(url(), 'Siteyi aç')]);
