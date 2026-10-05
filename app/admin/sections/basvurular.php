<?php
/**
 * İş başvuruları (kariyer formu) ve özgeçmiş dosyaları.
 * Süzgeç (?ilan=): boş = tümü, "genel" = genel başvurular (aday havuzu), ilan kimliği = o ilana gelenler.
 */

// Süzgeç; silme, "Spam değil" ve CSV işlemleri de aynı süzgeçle geri döner
$fil  = (string) ($_GET['ilan'] ?? '');
$fil  = $fil === 'genel' || preg_match('/^[a-f0-9]{10}$/D', $fil) ? $fil : '';
$back = 'basvurular' . ($fil !== '' ? '?ilan=' . $fil : '');
$qs   = $fil !== '' ? '?ilan=' . $fil : '';

/* ---------- Özgeçmiş indir: /yonetim/basvurular/cv/{dosya} (eski: /yonetim/cv/{dosya}) ---------- */
$cvName = $section === 'cv' ? ($rest[0] ?? '') : (($rest[0] ?? '') === 'cv' ? ($rest[1] ?? '') : '');
if ($cvName !== '') {
    if (!preg_match('#^(\d{8}-\d{6}-[a-f0-9]{12})\.(pdf|docx)$#', $cvName, $m) || !is_file(ROOT . '/storage/cv/' . $cvName)) {
        adm_flash('Dosya bulunamadı.', 'err');
        adm_go($back);
    }
    $orig = $cvName;
    foreach (adm_records() as $r) if (($r['data']['cv'] ?? '') === $cvName) { $orig = ($r['data']['cv_name'] ?? '') ?: $cvName; break; }
    $file = ROOT . '/storage/cv/' . $cvName;
    header('Content-Type: ' . ($m[2] === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
    header('X-Content-Type-Options: nosniff');
    header('Content-Length: ' . filesize($file));
    header("Content-Disposition: attachment; filename=\"cv." . $m[2] . "\"; filename*=UTF-8''" . rawurlencode($orig));
    readfile($file);
    exit;
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && preg_match('/^[a-f0-9]{12}$/', $rest[0] ?? '')) {
    if (adm_application_delete($rest[0])) {
        adm_flash('Başvuru ve özgeçmiş dosyası silindi.');
    }
    adm_go($back);
}

/* ---------- Şüpheli başvuru: /yonetim/basvurular/{id}/spam-degil (gelen kutusuna al, bekletilen e-postayı özgeçmişle gönder) ---------- */
if (($rest[1] ?? '') === 'spam-degil' && $method === 'POST') {
    $rel = adm_record_release((string) ($rest[0] ?? ''));
    if (!$rel) adm_flash('Başvuru bulunamadı.', 'err');
    elseif ($rel['sent']) adm_flash('Başvuru gelen kutusuna alındı, bildirim e-postası gönderildi.');
    else adm_flash('Başvuru gelen kutusuna alındı ancak bildirim e-postası gönderilemedi.', 'err');
    adm_go($back);
}

spam_purge();   // 30 günü dolan ya da en yeni SPAM_MAX kaydın dışında kalan şüpheli başvurular (özgeçmiş dosyalarıyla) sayfa açılırken temizlenir
// Süzgecin şüpheli bulduğu başvurular (bkz. app/spam.php) listede "Şüpheli" etiketiyle görünür; yeni sayılmaz, CSV'ye girmez
$allRows = array_reverse(array_values(array_filter(adm_records(), fn($r) => ($r['form'] ?? '') === 'kariyer')));
$ilanOf  = fn(array $r): string => (string) ($r['data']['ilan'] ?? '');
$ilanNow = array_column(ilan_all(), null, 'id');   // var olan ilanlar
/** İlanın görünen adı: var olan ilanın şu anki adı, silinmişse başvuruya yazılan başlık kopyası (silinmiş ilan olarak işaretlenir). */
$ilanName = function (string $id, string $snap) use ($ilanNow): array {
    return isset($ilanNow[$id]) ? [(string) $ilanNow[$id]['title'], false] : [($snap !== '' ? $snap : 'İlan') , true];
};
// Süzgeç düğmeleri: her ilanın başvuru sayısı (şüpheli ayrılanlar sayılmaz); yalnızca başvurusu olan ilanlar listelenir
$byIlan = [];
$nGenel = 0;
$nAll   = 0;
foreach ($allRows as $r) {
    $iid = $ilanOf($r);
    if ($iid !== '') {
        $byIlan[$iid] ??= ['n' => 0, 'snap' => (string) ($r['data']['ilan_baslik'] ?? '')];
        $byIlan[$iid]['snap'] = $byIlan[$iid]['snap'] !== '' ? $byIlan[$iid]['snap'] : (string) ($r['data']['ilan_baslik'] ?? '');
    }
    if (!empty($r['spam'])) continue;
    $nAll++;
    if ($iid === '') $nGenel++; else $byIlan[$iid]['n']++;
}
uasort($byIlan, fn($a, $b) => $b['n'] <=> $a['n']);
$rows = array_values(array_filter($allRows, fn($r) => $fil === '' || ($fil === 'genel' ? $ilanOf($r) === '' : $ilanOf($r) === $fil)));
$nSus = count(array_filter($rows, fn($r) => !empty($r['spam'])));
$cols = [
    'Ad soyad' => fn($d) => trim(($d['ad'] ?? '') . ' ' . ($d['soyad'] ?? '')),
    'E-posta'  => fn($d) => $d['email'] ?? '',
    'Telefon'  => fn($d) => $d['telefon'] ?? '',
    'Şehir'    => fn($d) => $d['sehir'] ?? '',
    'Pozisyon' => fn($d) => $d['pozisyon'] ?? '',
    'İlan'     => function ($d) use ($ilanNow) {
        $iid = (string) ($d['ilan'] ?? '');
        if ($iid === '') return 'Genel başvuru';
        return isset($ilanNow[$iid]) ? (string) $ilanNow[$iid]['title'] : (string) ($d['ilan_baslik'] ?? 'İlan') . ' (silinmiş ilan)';
    },
    'Deneyim'  => fn($d) => $d['deneyim'] ?? '',
    'LinkedIn' => fn($d) => $d['linkedin'] ?? '',
    'Ön yazı'  => fn($d) => $d['mesaj'] ?? '',
    'KVKK'     => fn($d) => $d['kvkk'] ?? '',
    'Saklama izni' => fn($d) => $d['saklama'] ?? '',
];

/* ---------- CSV ---------- */
if (($rest[0] ?? '') === 'csv' || $sub === 'basvurular.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    $csvTag = $fil === '' ? '' : ($fil === 'genel' ? 'genel-' : (isset($ilanNow[$fil]) ? $ilanNow[$fil]['slug'] : 'ilan-' . $fil) . '-');
    header('Content-Disposition: attachment; filename="is-basvurulari-' . $csvTag . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_map('csv_safe', array_merge(['Tarih'], array_keys($cols), ['Özgeçmiş'])), ';', '"', '');
    foreach ($rows as $r) {
        if (!empty($r['spam'])) continue;
        $d = $r['data'] ?? [];
        $line = [date('d.m.Y H:i', strtotime($r['time'] ?? 'now'))];
        foreach ($cols as $fn) $line[] = $fn($d);
        $line[] = $d['cv_name'] ?? '';
        fputcsv($out, array_map(fn($c) => csv_safe((string) $c), $line), ';', '"', '');
    }
    fclose($out);
    exit;
}

$seen = (int) (adm_state()['seen_basvurular'] ?? 0);
if ($fil === '') adm_state(['seen_basvurular' => time()]);   // süzülmüş listeyi açmak, görülmemiş başvuruları görülmüş saymaz

$items = '';
foreach ($rows as $r) {
    $d = $r['data'] ?? [];
    $t = strtotime($r['time'] ?? 'now') ?: 0;
    $sus = !empty($r['spam']);
    $badges = (!$sus && $t > $seen ? '<span class="badge badge--coral">Yeni</span>' : '')
        . ($sus ? '<span class="badge badge--warn">Şüpheli</span>' : '')
        . (str_starts_with((string) ($d['saklama'] ?? ''), 'Evet') ? '<span class="badge badge--ok">Saklama izni var</span>' : '');
    $why = '';
    foreach ($sus ? (array) ($r['spam']['reasons'] ?? []) : [] as $x) $why .= '<li>' . e((string) $x) . '</li>';
    $rel = $sus && !empty($r['id'])
        ? '<form method="post" action="' . adm_url('basvurular/' . $r['id'] . '/spam-degil' . $qs) . '">' . adm_csrf_field() . '<button class="btn btn--soft btn--sm" type="submit" title="Gelen kutusuna al ve bildirim e-postasını gönder">' . ui_icon('check-circle') . 'Spam değil</button></form>'
        : '';
    $iid  = $ilanOf($r);
    [$iname, $gone] = $iid !== '' ? $ilanName($iid, (string) ($d['ilan_baslik'] ?? '')) : ['', false];
    $where = $iid === '' ? '<span class="badge">Genel başvuru</span>'
        : '<a class="badge ' . ($gone ? 'badge--warn' : 'badge--navy') . '" style="text-decoration:none" href="' . adm_url('basvurular?ilan=' . $iid) . '" title="Bu ilana gelen başvurular">' . ui_icon('file-text') . ($gone ? 'Silinmiş ilan: ' : 'İlan: ') . e($iname) . '</a>';
    $meta = array_filter([e($d['sehir'] ?? ''), e($d['deneyim'] ?? ''), !empty($d['linkedin']) ? '<a href="' . e($d['linkedin']) . '" target="_blank" rel="noopener noreferrer">LinkedIn</a>' : '']);
    $cv = !empty($d['cv']) && is_file(ROOT . '/storage/cv/' . $d['cv'])
        ? '<a class="btn btn--soft btn--sm" href="' . adm_url('basvurular/cv/' . $d['cv']) . '">' . ui_icon('download-simple') . 'Özgeçmiş</a>'
        : '<span class="badge">Dosya yok</span>';
    $del = !empty($r['id'])
        ? '<form method="post" action="' . adm_url('basvurular/' . $r['id'] . '/sil' . $qs) . '" data-confirm="Başvuru ve özgeçmiş dosyası kalıcı olarak silinsin mi?">' . adm_csrf_field() . '<button class="btn btn--danger btn--sm" type="submit" aria-label="Başvuruyu sil">' . ui_icon('trash') . '</button></form>'
        : '';
    $items .= '<div class="list__row" style="align-items:start"><div class="list__main">'
        . '<span class="list__meta"><span>' . e(date('d.m.Y H:i', $t)) . '</span>' . ($iid === '' && !empty($d['pozisyon']) ? '<span>' . e($d['pozisyon']) . '</span>' : '') . '</span>'
        . '<span class="list__title" style="font-size:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">' . e(trim(($d['ad'] ?? '') . ' ' . ($d['soyad'] ?? ''))) . $badges . $where . '</span>'
        . '<span class="list__meta"><a href="mailto:' . e($d['email'] ?? '') . '">' . e($d['email'] ?? '') . '</a><a href="tel:' . e(preg_replace('/\s+/', '', (string) ($d['telefon'] ?? ''))) . '">' . e($d['telefon'] ?? '') . '</a>' . ($meta ? '<span>' . implode(' · ', $meta) . '</span>' : '') . '</span>'
        . (!empty($d['mesaj']) ? '<details style="margin-top:6px"><summary style="cursor:pointer;color:var(--navy);font-size:13.5px">Ön yazıyı göster</summary><p style="margin-top:6px;max-width:70ch;color:var(--text-2)">' . nl2br(e($d['mesaj'])) . '</p></details>' : '')
        . ($why ? '<ul class="spam-why" aria-label="Neden şüpheli">' . $why . '</ul>' : '')
        . '</div><div class="list__side">' . $cv . $rel . $del . '</div></div>';
}

$chip = fn(string $v, string $label, int $n) => '<a class="chip' . ($fil === $v ? ' is-on' : '') . '" href="' . adm_url('basvurular') . ($v !== '' ? '?ilan=' . $v : '') . '">' . e($label) . ' <em>' . $n . '</em></a>';
$chips = $chip('', 'Tümü', $nAll) . $chip('genel', 'Genel başvurular (havuz)', $nGenel);
foreach ($byIlan as $iid => $b) {
    [$iname, $gone] = $ilanName((string) $iid, $b['snap']);
    $chips .= $chip((string) $iid, $iname . ($gone ? ' (silinmiş ilan)' : ''), $b['n']);
}
$filterBar = $byIlan || ilan_all() ? '<div class="chips" role="group" aria-label="Başvuruları süz" style="margin-bottom:' . ($items ? '16px' : '0') . '">' . $chips . '</div>' : '';

if ($items) {
    $listHtml = '<div class="list">' . $items . '</div>';
} elseif ($fil !== '' || $allRows) {
    $listHtml = '<div class="empty">' . ui_icon('briefcase') . '<strong>Bu süzgeçte başvuru yok</strong><a class="btn btn--soft btn--sm" href="' . adm_url('basvurular') . '">Tüm başvuruları göster</a></div>';
} else {
    $listHtml = '<div class="empty">' . ui_icon('briefcase') . '<strong>Henüz iş başvurusu yok</strong><span>Kariyer sayfasındaki formdan gelen başvurular burada listelenir.</span>' . ui_view_link(url('kariyer'), 'Kariyer sayfasını aç') . '</div>';
}
$body = $filterBar . $listHtml;
$fname = $fil === '' ? '' : ($fil === 'genel' ? 'Genel başvurular (aday havuzu)' : (function () use ($fil, $byIlan, $ilanName) { [$n, $g] = $ilanName($fil, (string) ($byIlan[$fil]['snap'] ?? '')); return 'İlan: ' . $n . ($g ? ' (silinmiş ilan)' : ''); })());

adm_layout('İş başvuruları', ui_card('', $body), [
    'section'  => 'basvurular',
    'subtitle' => ($fname !== '' ? e($fname) . ': ' : '') . (count($rows) - $nSus) . ' başvuru. Silme işlemi özgeçmiş dosyasını da kalıcı olarak siler; işe alım süreci biten başvuruları düzenli olarak temizleyin.'
        . ($nSus ? ' Ayrıca ' . $nSus . ' şüpheli başvuru var: bunlar için e-posta gönderilmedi; özgeçmiş dosyalarıyla birlikte ' . SPAM_DAYS . ' gün sonra ya da şüpheli kayıtlar ' . SPAM_MAX . ' sayısını aşınca en eskiden başlayarak kendiliğinden silinir. Gerçek bir başvuruysa "Spam değil" düğmesiyle gelen kutusuna alın.' : ''),
    'actions'  => (count($rows) > $nSus ? '<a class="btn btn--ghost btn--sm" href="' . adm_url('basvurular/csv' . $qs) . '">' . ui_icon('download-simple') . 'Excel için indir' . ($fil !== '' ? ' (süzülmüş)' : '') . '</a>' : '') . ui_view_link(url('kariyer'), 'Kariyer sayfası'),
]);
