<?php
/**
 * Değişiklik geçmişi: sitede kim, ne zaman, neyi değiştirdi; her içerik değişikliği tek tıkla geri alınır.
 * Günlük ve fark: app/changelog.php.
 * Adresler: /yonetim/gecmis · /yonetim/gecmis/{bölüm} · /yonetim/gecmis/{bölüm}/{sürüm} (GET: ayrıntı, POST: geri al)
 */

$sections = changelog_sections();
$key = (string) ($rest[0] ?? '');
$rev = (string) ($rest[1] ?? '');
if ($key !== '' && !isset($sections[$key])) {
    adm_flash('Geçmiş bulunamadı.', 'err');
    adm_go('gecmis');
}

/* ---------- Geri al ---------- */
if ($method === 'POST' && $key !== '' && $rev !== '') {
    if (changelog_restore($key, $rev)) {
        $rep = restore_report();
        adm_flash($sections[$key][0] . ' seçtiğiniz değişiklikten önceki haline döndürüldü. Geri almadan önceki hali de geçmişe eklendi.'
            . ($key === 'ilanlar' && ilan_restore_opened() ? ' DİKKAT: şu ilanlar geri almayla başvuruya açıldı: ' . implode(', ', ilan_restore_opened()) . '.' : '')
            // Eski sürüm bugünkü kurallarla yeniden doğrulanır; uymayan öğeler alınmaz ve burada söylenir
            . ($rep['dropped'] ? ' Bugünkü kurallara uymadığı için geri alınmayan öğeler: ' . restore_report_text($rep['dropped']) : '')
            . ($rep['notes'] ? ' ' . restore_report_text($rep['notes']) : ''));
        adm_go('gecmis/' . $key);
    }
    $rep = restore_report();
    adm_flash('Sürüm geri yüklenemedi.' . ($rep['error'] ? ' ' . $rep['error'] . ' Hiçbir şey değişmedi.' : '') . ($rep['dropped'] ? ' ' . restore_report_text($rep['dropped']) : ''), 'err');
    adm_go('gecmis/' . $key);
}

/** "3 Ekim 2026, 14:05" */
$when = function (string $iso): string {
    $t = $iso !== '' ? (int) strtotime($iso) : 0;
    return $t ? tr_date(date('Y-m-d', $t)) . ', ' . date('H:i', $t) : 'Tarih bilinmiyor';
};

/** Değişikliği yapan: rozet */
$who = function (array $w): string {
    $type = (string) ($w['type'] ?? '');
    if ($type === 'mcp') {
        return '<span class="badge badge--navy">' . ui_icon('robot') . e((string) $w['name']) . (($w['client'] ?? '') !== '' ? ' · ' . e((string) $w['client']) : '') . '</span>';
    }
    if ($type === 'panel') {
        return '<span class="badge">' . ui_icon('desktop') . 'Yönetim paneli</span>';
    }
    return '<span class="badge">' . ($type === 'bilinmiyor' ? 'Kimin yaptığı kayıtlı değil' : 'Sistem') . '</span>';
};

/** Geri alma düğmesi (onay metniyle) */
$undo = function (array $r, string $class = 'btn btn--ghost btn--sm') use ($sections): string {
    if (empty($r['can'])) {
        return '';
    }
    $label = $sections[$r['key']][0];
    $msg = $label . ' bu değişiklikten önceki haline dönsün mü?'
        . ((int) $r['later'] > 0 ? ' Bu bölümde sonradan yapılan ' . (int) $r['later'] . ' değişiklik de geri alınır.' : '')
        . ' Şu anki hal geçmişe kaydedilir; geri almayı da geri alabilirsiniz.';
    return '<form method="post" action="' . adm_url('gecmis/' . $r['key'] . '/' . $r['rev']) . '" data-confirm="' . e($msg) . '">' . adm_csrf_field()
        . '<button class="' . $class . '" type="submit">' . ui_icon('arrow-counter-clockwise') . 'Geri al</button></form>';
};

/* =========================================================================
   Ayrıntı: bir değişikliğin öncesi ve sonrası
   ========================================================================= */
if ($key !== '' && $rev !== '') {
    $detail = changelog_detail($key, $rev);
    $entry = null;
    foreach (changelog_entries($key) as $r) {
        if (($r['rev'] ?? null) === $rev) { $entry = $r; break; }
    }
    if ($detail === null || $entry === null) {
        adm_flash('Bu değişikliğin sürümü artık saklanmıyor.', 'err');
        adm_go('gecmis/' . $key);
    }
    $clip = fn(string $s): string => mb_strlen($s) > 2500 ? mb_substr($s, 0, 2500) . '…' : $s;
    $cell = fn(string $s, string $cls): string => '<td class="' . $cls . '">' . ($s === '' ? '<span class="hx-none">(boş)</span>' : e($clip($s))) . '</td>';

    $cards = '';
    foreach ($detail['items'] as $it) {
        $rows = '';
        foreach ($it['rows'] as [$field, $old, $new]) {
            $rows .= '<tr><th scope="row">' . e($field) . '</th>' . $cell((string) $old, 'is-old') . $cell((string) $new, 'is-new') . '</tr>';
        }
        $cards .= ui_card((string) $it['title'], $rows !== ''
            ? '<div class="tbl-wrap hx-wrap"><table class="hx-diff"><thead><tr><th scope="col">Alan</th><th scope="col">Önce</th><th scope="col">Sonra</th></tr></thead><tbody>' . $rows . '</tbody></table></div>'
            : '<p class="muted">Bu değişikliğin alan ayrıntısı yok.</p>');
    }
    if ($detail['partial']) {
        $cards = ui_alert('Bu değişiklik ile sonraki arasındaki sürümler artık saklanmadığı için ayrıntı gösterilemiyor. Yine de bu değişiklikten önceki hale dönebilirsiniz.', 'info');
    } elseif ($cards === '') {
        $cards = ui_alert('Bu kayıtta görünür bir fark yok; içerik aynı haliyle yeniden kaydedilmiş.', 'info');
    }

    $sum = '';
    foreach ((array) $entry['sum'] as $line) {
        $sum .= '<li>' . e((string) $line) . '</li>';
    }
    $side = '<div class="hx-facts"><p><span>Ne zaman</span>' . e($when((string) $entry['t'])) . '</p><p><span>Kim</span>' . $who((array) $entry['who']) . '</p>'
        . '<p><span>Bölüm</span><a href="' . adm_url($sections[$key][1]) . '">' . e($sections[$key][0]) . '</a></p>'
        . (($entry['note'] ?? '') !== '' ? '<p><span>Not</span>' . e((string) $entry['note']) . '</p>' : '') . '</div>';
    $undoCard = !empty($entry['can'])
        ? ui_card('Bu değişikliği geri al', '<p class="muted">' . e($sections[$key][0]) . ' bölümü bu değişiklikten önceki haline döner.'
            . ((int) $entry['later'] > 0 ? ' <strong>Bu bölümde sonradan yapılan ' . (int) $entry['later'] . ' değişiklik de geri alınır.</strong>' : '')
            . ' Şu anki hal geçmişe kaydedilir; geri almayı da geri alabilirsiniz.</p>' . $undo($entry, 'btn btn--block'))
        : '';

    adm_layout('Değişiklik ayrıntısı', '<div class="split"><div style="display:grid;gap:20px;min-width:0">'
        . ui_card('Ne değişti', '<ul class="hx-sum">' . $sum . '</ul>') . $cards
        . '</div><aside class="split__side">' . ui_card('Kayıt', $side) . $undoCard . '</aside></div>', [
        'section' => 'gecmis',
        'crumbs'  => [['Değişiklik geçmişi', adm_url('gecmis')], [$sections[$key][0], adm_url('gecmis/' . $key)]],
    ]);
}

/* =========================================================================
   Liste
   ========================================================================= */
$all  = changelog_entries();
$kim  = in_array($_GET['kim'] ?? '', ['panel', 'mcp'], true) ? (string) $_GET['kim'] : '';
$rows = array_values(array_filter($all, fn($r) => ($key === '' || $r['key'] === $key) && ($kim === '' || ($r['who']['type'] ?? '') === $kim)));

$per   = 50;
$pages = max(1, (int) ceil(count($rows) / $per));
$page  = max(1, min($pages, (int) ($_GET['sayfa'] ?? 1)));
$slice = array_slice($rows, ($page - 1) * $per, $per);

$link = function (string $k, string $who, int $p = 1): string {
    $q = array_filter(['kim' => $who, 'sayfa' => $p > 1 ? $p : null]);
    return adm_url('gecmis' . ($k !== '' ? '/' . $k : '')) . ($q ? '?' . http_build_query($q) : '');
};

// Süzgeçler: bölüm ve değişikliği yapan
$counts = [];
foreach ($all as $r) {
    $counts[$r['key']] = ($counts[$r['key']] ?? 0) + 1;
}
$chips = '<a class="chip' . ($key === '' ? ' is-on' : '') . '" href="' . $link('', $kim) . '">Tüm bölümler <em>' . count($all) . '</em></a>';
foreach ($sections as $k => [$label]) {
    if (!empty($counts[$k]) || $k === $key) {
        $chips .= '<a class="chip' . ($key === $k ? ' is-on' : '') . '" href="' . $link($k, $kim) . '">' . e($label) . ' <em>' . (int) ($counts[$k] ?? 0) . '</em></a>';
    }
}
$whoChips = '';
foreach (['' => 'Herkes', 'panel' => 'Yönetim paneli', 'mcp' => 'Yapay zekâ'] as $v => $label) {
    $whoChips .= '<a class="tab' . ($kim === $v ? ' is-on' : '') . '" href="' . $link($key, $v) . '">' . $label . '</a>';
}

$list = '';
foreach ($slice as $r) {
    $sum = array_values((array) $r['sum']);
    $more = '';
    foreach (array_slice($sum, 1) as $line) {
        $more .= '<span class="hx-more">' . e((string) $line) . '</span>';
    }
    $hasDetail = !empty($r['rev']) && changelog_rev_file($r['key'], $r['rev']) !== null;
    $list .= '<div class="list__row hx-row"><div class="list__main">'
        . '<span class="list__meta hx-meta"><span>' . e($when((string) $r['t'])) . '</span><span class="badge badge--ok">' . e($sections[$r['key']][0]) . '</span>' . $who((array) $r['who'])
        . (($r['note'] ?? '') !== '' ? '<span class="badge badge--warn">' . e((string) $r['note']) . '</span>' : '') . '</span>'
        . '<span class="list__title">' . e((string) ($sum[0] ?? '')) . '</span>' . $more . '</div>'
        . '<div class="list__side">' . ($hasDetail ? '<a class="btn btn--ghost btn--sm" href="' . adm_url('gecmis/' . $r['key'] . '/' . $r['rev']) . '">Ayrıntı</a>' : '')
        . $undo($r) . (empty($r['rev']) ? '<span class="badge" title="Bu işlem içerik değişikliği değildir">Geri alınamaz</span>' : '') . '</div></div>';
}

$pager = '';
if ($pages > 1) {
    $pager = '<div class="hx-pager">'
        . ($page > 1 ? '<a class="btn btn--ghost btn--sm" href="' . $link($key, $kim, $page - 1) . '">Daha yeni</a>' : '<span></span>')
        . '<span class="muted">Sayfa ' . $page . ' / ' . $pages . '</span>'
        . ($page < $pages ? '<a class="btn btn--ghost btn--sm" href="' . $link($key, $kim, $page + 1) . '">Daha eski</a>' : '<span></span>') . '</div>';
}

$body = $list !== ''
    ? '<div class="list">' . $list . '</div>' . $pager
    : '<div class="empty">' . ui_icon('clock-counter-clockwise') . '<strong>Henüz kayıtlı bir değişiklik yok</strong><span>Panelden ya da yapay zekâ erişimiyle yapılan her değişiklik, kimin yaptığıyla birlikte burada listelenir.</span></div>';

adm_layout('Değişiklik geçmişi', '<div class="hx-filters"><div class="chips">' . $chips . '</div><div class="tabs hx-who">' . $whoChips . '</div></div>'
    . ui_card($key !== '' ? $sections[$key][0] : 'Tüm değişiklikler', $body, [
        'desc' => 'Her içerik bölümü için son ' . CONTENT_HISTORY_KEEP . ' değişiklik ve sitenin ilk hali saklanır. Geri almak şu anki hali silmez; o da geçmişe eklenir.',
    ]), [
    'section'  => 'gecmis',
    'subtitle' => 'Sitede kim, ne zaman, neyi değiştirdi. Panelden ve yapay zekâ erişimiyle yapılan değişiklikler birlikte listelenir.',
    'crumbs'   => $key !== '' ? [['Değişiklik geçmişi', adm_url('gecmis')]] : [],
]);
