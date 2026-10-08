<?php
/** Form kayıtları: iletişim, bülten, Haberdar Ol (iş başvuruları ayrı bölümde) */

$names = ['bulten' => 'Bülten', 'iletisim' => 'İletişim', 'haberdarol' => 'Haberdar ol'];

/* ---------- Şüpheli kayıtlar: /yonetim/kayitlar/{id}/spam-degil (gelen kutusuna al, bekletilen e-postayı gönder), /yonetim/kayitlar/supheli/sil (hepsini sil) ---------- */
if (($rest[1] ?? '') === 'spam-degil' && $method === 'POST') {
    $rel = adm_record_release((string) ($rest[0] ?? ''));
    if (!$rel) adm_flash('Kayıt bulunamadı.', 'err');
    elseif ($rel['sent']) adm_flash('Kayıt gelen kutusuna alındı, bildirim e-postası gönderildi.');
    else adm_flash('Kayıt gelen kutusuna alındı ancak bildirim e-postası gönderilemedi.', 'err');
    adm_go('kayitlar?tur=supheli');
}
if (($rest[0] ?? '') === 'supheli' && ($rest[1] ?? '') === 'sil' && $method === 'POST') {
    $n = adm_spam_delete_all();
    adm_flash($n ? $n . ' şüpheli kayıt kalıcı olarak silindi.' : 'Silinecek şüpheli kayıt yok.');
    adm_go('kayitlar');
}

/* ---------- Sil: /yonetim/kayitlar/{id}/sil (bülten onayını geri alan ya da silinmesini isteyen kişinin kaydı) ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST') {
    $del = adm_form_record_delete((string) ($rest[0] ?? ''));
    $del ? adm_flash('Kayıt kalıcı olarak silindi.') : adm_flash('Kayıt bulunamadı.', 'err');
    adm_go(!empty($del['spam']) ? 'kayitlar?tur=supheli' : 'kayitlar');
}
spam_purge();   // 30 günü dolan ya da en yeni SPAM_MAX kaydın dışında kalan şüpheli kayıtlar sayfa açılırken temizlenir
$all   = array_reverse(array_values(array_filter(adm_records(), fn($r) => ($r['form'] ?? '') !== 'kariyer')));
// Süzgecin şüpheli bulduğu kayıtlar (bkz. app/spam.php) yalnızca "Şüpheli" süzgecinde görünür; diğer listelere ve CSV'ye girmez
$sus   = array_values(array_filter($all, fn($r) => !empty($r['spam'])));
$all   = array_values(array_filter($all, fn($r) => empty($r['spam'])));
$tur   = (string) ($_GET['tur'] ?? '');
$q     = trim((string) ($_GET['q'] ?? ''));
$isSus = $tur === 'supheli';
$rows  = $isSus ? $sus : $all;
if (isset($names[$tur])) $rows = array_values(array_filter($rows, fn($r) => ($r['form'] ?? '') === $tur || ($tur === 'bulten' && ($r['form'] ?? '') === 'haberdarol')));
if ($q !== '') {
    $needle = mb_strtolower($q);
    $rows = array_values(array_filter($rows, fn($r) => str_contains(mb_strtolower(json_encode($r['data'] ?? [], JSON_UNESCAPED_UNICODE)), $needle)));
}

$cols = [
    'Ad soyad' => fn($d) => trim(($d['ad'] ?? $d['isimsoyisim'] ?? $d['namesurname'] ?? '') . ' ' . ($d['soyad'] ?? $d['isimsoyisim2'] ?? '')),
    'E-posta'  => fn($d) => $d['email'] ?? '',
    'Telefon'  => fn($d) => $d['telefon'] ?? $d['phone'] ?? '',
    'İl'       => fn($d) => $d['il'] ?? '',
    'Sektör / unvan' => fn($d) => $d['sektor'] ?? $d['unvan'] ?? '',
    'Firma'    => fn($d) => $d['firma'] ?? '',
    'Konu'     => fn($d) => $d['konu'] ?? '',
    'Mesaj'    => fn($d) => $d['mesaj'] ?? $d['message'] ?? '',
    'KVKK'     => fn($d) => $d['kvkk'] ?? '',
    'İleti onayı' => fn($d) => $d['etk'] ?? '',
];

/* ---------- CSV ---------- */
if (($rest[0] ?? '') === 'csv' || $sub === 'kayitlar.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="form-kayitlari-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, array_map('csv_safe', array_merge(['Tarih', 'Form'], array_keys($cols))), ';', '"', '');
    foreach ($rows as $r) {
        $d = $r['data'] ?? [];
        $line = [date('d.m.Y H:i', strtotime($r['time'] ?? 'now')), $names[$r['form'] ?? ''] ?? ($r['form'] ?? '')];
        foreach ($cols as $fn) $line[] = $fn($d);
        fputcsv($out, array_map(fn($c) => csv_safe((string) $c), $line), ';', '"', '');
    }
    fclose($out);
    exit;
}

$st = adm_state();
$seen = (int) ($st['seen_kayitlar'] ?? 0);
adm_state(['seen_kayitlar' => time()]);

$chips = '';
$counts = ['' => count($all)];
foreach ($names as $k => $n) $counts[$k] = count(array_filter($all, fn($r) => ($r['form'] ?? '') === $k || ($k === 'bulten' && ($r['form'] ?? '') === 'haberdarol')));
unset($counts['haberdarol']);
$counts['supheli'] = count($sus);
foreach (['' => 'Tümü', 'iletisim' => 'İletişim', 'bulten' => 'Bülten ve Haberdar Ol', 'supheli' => 'Şüpheli'] as $k => $n) {
    $chips .= '<a class="chip' . ($tur === $k ? ' is-on' : '') . '" href="' . adm_url('kayitlar') . ($k ? '?tur=' . $k : '') . '">' . e($n) . ' <em>' . ($counts[$k] ?? 0) . '</em></a>';
}

// Seçili listede hiç dolu olmayan sütunlar gizlenir (ör. yalnız bülten kayıtlarında "Konu").
$shown = array_filter($cols, function ($fn) use ($rows) {
    foreach ($rows as $r) if (trim((string) $fn($r['data'] ?? [])) !== '') return true;
    return false;
});
$nowrapCols = ['Ad soyad', 'E-posta', 'Telefon', 'İl', 'KVKK', 'İleti onayı'];

$tbl = '';
foreach ($rows as $r) {
    $d = $r['data'] ?? [];
    $t = strtotime($r['time'] ?? 'now') ?: 0;
    $new = !$isSus && $t > $seen ? ' <span class="badge badge--coral">Yeni</span>' : '';
    $tbl .= '<tr><td class="nowrap">' . e(date('d.m.Y H:i', $t)) . $new . '</td><td><span class="badge">' . e($names[$r['form'] ?? ''] ?? '') . '</span></td>';
    if ($isSus) {
        $why = '';
        foreach ((array) ($r['spam']['reasons'] ?? []) as $x) $why .= '<li>' . e((string) $x) . '</li>';
        $tbl .= '<td class="wrap"><span class="badge badge--warn">' . (int) ($r['spam']['score'] ?? 0) . ' puan</span>' . ($why ? '<ul class="spam-why">' . $why . '</ul>' : '') . '</td>';
    }
    foreach ($shown as $label => $fn) {
        $v = (string) $fn($d);
        if ($label === 'E-posta' && $v) $v = '<a href="mailto:' . e($v) . '">' . e($v) . '</a>';
        elseif ($label === 'Telefon' && $v) $v = '<a href="tel:' . e(preg_replace('/\s+/', '', $v)) . '">' . e($v) . '</a>';
        else $v = nl2br(e($v));
        $tbl .= '<td' . ($label === 'Mesaj' ? ' class="wrap"' : (in_array($label, $nowrapCols, true) ? ' class="nowrap"' : '')) . '>' . $v . '</td>';
    }
    $acts = !empty($r['id'])
        ? '<form method="post" action="' . adm_url('kayitlar/' . $r['id'] . '/sil') . '" data-confirm="Bu kayıt kalıcı olarak silinsin mi? Geri alınamaz.">' . adm_csrf_field()
            . '<button class="btn btn--danger btn--sm" type="submit" aria-label="Kaydı sil" title="Kaydı sil">' . ui_icon('trash') . '</button></form>'
        : '';
    if ($isSus && $acts) {
        $acts = '<div class="spam-acts"><form method="post" action="' . adm_url('kayitlar/' . $r['id'] . '/spam-degil') . '">' . adm_csrf_field()
            . '<button class="btn btn--soft btn--sm" type="submit" title="Gelen kutusuna al ve bildirim e-postasını gönder">' . ui_icon('check-circle') . 'Spam değil</button></form>' . $acts . '</div>';
    }
    $tbl .= '<td>' . $acts . '</td>';
    $tbl .= '</tr>';
}

$search = '<form method="get" action="' . adm_url('kayitlar') . '" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between">'
    . '<div class="chips">' . $chips . '</div>'
    . ($tur ? '<input type="hidden" name="tur" value="' . e($tur) . '">' : '')
    . '<div style="display:flex;gap:8px;flex-wrap:wrap"><input class="inp" type="search" name="q" value="' . e($q) . '" placeholder="İsim, e-posta, il ara" style="min-height:36px;width:min(220px,100%)"><button class="btn btn--ghost btn--sm" type="submit">' . ui_icon('magnifying-glass') . 'Ara</button></div></form>';

$body = $search . ($rows
    ? '<div class="tbl-wrap" style="border-top:1px solid var(--line)"><table class="tbl"><thead><tr><th>Tarih</th><th>Form</th>' . ($isSus ? '<th>Neden şüpheli</th>' : '') . '<th>' . implode('</th><th>', array_map('e', array_keys($shown))) . '</th><th><span class="sr">' . ($isSus ? 'İşlemler' : 'Sil') . '</span></th></tr></thead><tbody>' . $tbl . '</tbody></table></div>'
    : '<div class="empty">' . ui_icon('tray') . '<strong>' . ($q ? 'Aramanızla eşleşen kayıt yok' : ($isSus ? 'Şüpheli kayıt yok' : 'Henüz kayıt yok')) . '</strong></div>');

adm_layout('Form kayıtları', ui_card('', $body), [
    'section'  => 'kayitlar',
    'wide'     => true,
    'subtitle' => $isSus
        ? 'Süzgecin şüpheli bulduğu gönderimler. Bunlar için e-posta gönderilmedi. En yeni ' . SPAM_MAX . ' şüpheli kayıt tutulur, ' . SPAM_DAYS . ' gün sonra kendiliğinden silinir; uzun mesajların ilk ' . SPAM_TEXT_MAX . ' karakteri saklanır. Gerçek bir kayıt görürseniz "Spam değil" düğmesiyle gelen kutusuna alın; bekletilen e-posta o anda gönderilir.'
        : 'İletişim, bülten ve Haberdar Ol formlarından gelenler. En yenisi en üstte. E-posta iletilemese bile kayıtlar burada saklanır. Verisinin silinmesini isteyen kişinin kayıtlarını satırın sonundaki düğmeyle silebilirsiniz' . (isset(adm_pending_sections()['bulten']) ? '.' : '; bülten e-postalarını durdurmak için Bülten bölümündeki "Abonelikten çıkar" düğmesini kullanın (aynı adresle sonradan yapılan kayıtlar aboneliği değiştirmez).'),
    'actions'  => ($isSus && $sus ? '<form method="post" action="' . adm_url('kayitlar/supheli/sil') . '" data-confirm="Şüpheli kayıtların hepsi (' . count($sus) . ' kayıt) kalıcı olarak silinsin mi? Geri alınamaz.">' . adm_csrf_field()
            . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Şüphelilerin hepsini sil</button></form>' : '')
        . ($rows ? '<a class="btn btn--ghost btn--sm" href="' . adm_url('kayitlar/csv') . '?' . e(http_build_query(array_filter(['tur' => $tur, 'q' => $q]))) . '">' . ui_icon('download-simple') . 'Excel için indir</a>' : ''),
]);
