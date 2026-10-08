<?php
/**
 * Duyurular ve çağrı takvimi. Veri: storage/duyurular.json (app/announcements.php)
 */

$types = ann_types();
$id    = $rest[0] ?? null;
$today = date('Y-m-d');

/* ---------- Liste ---------- */
if ($id === null) {
    $list = ann_all();
    usort($list, fn($x, $y) => strcmp(ann_sort_key($x), ann_sort_key($y)));
    $filter = $_GET['durum'] ?? '';
    $counts = ['' => count($list), 'yayinda' => 0, 'taslak' => 0, 'ornek' => 0];
    foreach ($list as $a) {
        !empty($a['published']) ? $counts['yayinda']++ : $counts['taslak']++;
        if (!empty($a['sample'])) $counts['ornek']++;
    }
    $list = array_filter($list, fn($a) => match ($filter) {
        'yayinda' => !empty($a['published']),
        'taslak'  => empty($a['published']),
        'ornek'   => !empty($a['sample']),
        default   => true,
    });
    $chips = '';
    foreach (['' => 'Tümü', 'yayinda' => 'Yayında', 'taslak' => 'Taslak', 'ornek' => 'Örnek'] as $k => $l) {
        $chips .= '<a class="chip' . ($filter === $k ? ' is-on' : '') . '" href="' . adm_url('duyurular') . ($k ? '?durum=' . $k : '') . '">' . $l . ' <em>' . $counts[$k] . '</em></a>';
    }
    $rows = '';
    foreach ($list as $a) {
        $next = null;
        foreach ($a['events'] ?? [] as $ev) if ($ev['date'] >= $today) { $next = $ev; break; }
        $meta = [];
        if (!empty($a['kurum'])) $meta[] = '<span>' . e($a['kurum']) . '</span>';
        $meta[] = '<span>' . count($a['events'] ?? []) . ' tarih</span>';
        if ($next) {
            $left = (int) round((strtotime($next['date']) - strtotime($today)) / 86400);
            $meta[] = '<span><i class="mk mk--' . e($next['type']) . '"></i> ' . e($types[$next['type']] ?? '') . ': ' . e(tr_date($next['date'])) . ' (' . ($left === 0 ? 'bugün' : $left . ' gün') . ')</span>';
        } elseif (!empty($a['events'])) {
            $meta[] = '<span>Tüm tarihler geçti</span>';
        }
        $badges = !empty($a['published']) ? '<span class="badge badge--ok"><i class="dot"></i>Yayında</span>' : '<span class="badge">Taslak</span>';
        if (!empty($a['sample'])) $badges .= '<span class="badge badge--warn">Örnek</span>';
        if (!empty($a['featured']) && !empty($a['published']) && ann_featured_until($a) >= $today) $badges .= '<span class="badge badge--coral">' . ui_icon('sparkle') . 'Açılışta öne çıkıyor</span>';
        $rows .= '<a class="list__row" href="' . adm_url('duyurular/' . $a['id']) . '"><span class="list__main"><span class="list__title">' . e($a['title']) . '</span><span class="list__meta">' . implode('', $meta) . '</span></span>'
            . '<span class="list__side">' . $badges . '<span class="list__go">' . ui_icon('caret-right') . '</span></span></a>';
    }
    $body = '<div class="chips">' . $chips . '</div>'
        . ($rows ? '<div class="list" style="border-top:1px solid var(--line)">' . $rows . '</div>'
                 : '<div class="empty">' . ui_icon('megaphone') . '<strong>Bu filtrede duyuru yok</strong><a class="btn btn--soft btn--sm" href="' . adm_url('duyurular/yeni') . '">Yeni duyuru</a></div>');
    adm_layout('Duyurular', ui_card('', $body), [
        'section'  => 'duyurular',
        'subtitle' => 'Yayındaki duyurular ana sayfadaki takvimde ve Duyurular sayfasında görünür. Yaklaşan tarihi olanlar en üstte.',
        'actions'  => ui_view_link(url('duyurular')) . ui_history_link('duyurular') . '<a class="btn btn--sm" href="' . adm_url('duyurular/yeni') . '">' . ui_icon('plus') . 'Yeni duyuru</a>',
    ]);
}

$isNew = $id === 'yeni';
$old   = $isNew ? null : ann_find($id);
if (!$isNew && !$old) {
    adm_flash('Duyuru bulunamadı.', 'err');
    adm_go('duyurular');
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && $old) {
    ann_save_all(array_filter(ann_all(), fn($a) => $a['id'] !== $old['id'])) ? adm_flash('Duyuru silindi.') : adm_flash('Silinemedi.', 'err');
    adm_go('duyurular');
}

$a = $old ?? ['title' => '', 'kurum' => '', 'summary' => '', 'link' => '', 'published' => true, 'sample' => false, 'featured' => false, 'featured_until' => '', 'events' => [['date' => '', 'type' => 'baslangic', 'note' => ''], ['date' => '', 'type' => 'son', 'note' => '']]];
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    [$a, $errors] = ann_validate([
        'title'          => $_POST['title'] ?? '',
        'kurum'          => $_POST['kurum'] ?? '',
        'summary'        => $_POST['summary'] ?? '',
        'link'           => $_POST['link'] ?? '',
        'published'      => post_bool('published'),
        'sample'         => post_bool('sample'),
        'featured'       => post_bool('featured'),
        'featured_until' => $_POST['featured_until'] ?? '',
        'events'         => post_rows('events', ['date', 'type', 'note'], 160),
    ], $old);
    if (!$errors) {
        if (ann_upsert($a)) {
            adm_flash(($old ? 'Duyuru kaydedildi.' : 'Duyuru eklendi.') . (!empty($a['featured']) && !empty($a['published']) ? ' Sitenin açılışında öne çıkarılıyor.' : ''));
            adm_go('duyurular/' . $a['id']);
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

ob_start();
if ($errors) echo ui_alert(e(implode(' ', $errors)));
?>
<form id="ann-form" method="post" action="<?= adm_url('duyurular/' . ($isNew ? 'yeni' : $a['id'])) ?>" class="split">
  <?= adm_csrf_field() ?>
  <div style="display:grid;gap:20px">
    <?= ui_card('Duyuru', implode('', [
        ui_text('title', 'Başlık', $a['title'], ['required' => true, 'maxlength' => 200, 'counter' => true, 'placeholder' => 'Örn: Yeşil Sanayi Destek Programı başvuruları açıldı']),
        '<div class="grid2">',
        ui_text('kurum', 'Kurum / program', $a['kurum'], ['maxlength' => 80, 'placeholder' => 'Örn: KOSGEB']),
        ui_text('link', 'Resmî duyuru bağlantısı', $a['link'], ['type' => 'url', 'placeholder' => 'https://...', 'help' => 'Ziyaretçi "Resmî duyuru" bağlantısıyla kurumun sayfasına gider.']),
        '</div>',
        ui_textarea('summary', 'Kısa açıklama', $a['summary'], ['rows' => 4, 'maxlength' => 1200, 'counter' => true, 'placeholder' => 'Kimler başvurabilir, destek kapsamı ne, nelere dikkat edilmeli?']),
    ])) ?>
    <?= ui_card('Takvim tarihleri', ui_repeater('events', '', $a['events'], [
        ['key' => 'date', 'label' => 'Tarih', 'type' => 'date'],
        ['key' => 'type', 'label' => 'Tür', 'type' => 'select', 'options' => $types],
        ['key' => 'note', 'label' => 'Not (isteğe bağlı)', 'maxlength' => 160, 'placeholder' => 'Örn: Saat 23.59\'a kadar', 'class' => 'span-2'],
    ], ['add' => 'Tarih ekle']), ['desc' => 'Her tarih sitedeki takvimde işaretlenir ve geri sayımla gösterilir. Tarihi boş bırakılan satırlar kaydedilmez.']) ?>
  </div>
  <aside class="split__side">
    <?= ui_card('Yayın', implode('', [
        ui_toggle('published', 'Sitede yayınla', !empty($a['published']), ['help' => 'Kapalıyken taslak olarak saklanır.']),
        ui_toggle('sample', 'Örnek duyuru', !empty($a['sample']), ['help' => 'Sitede "Örnek duyuru" etiketiyle görünür.']),
        '<button class="btn btn--block" type="submit">' . ui_icon('floppy-disk') . ($isNew ? 'Duyuruyu ekle' : 'Kaydet') . '</button>',
        !$isNew ? ui_view_link(url('duyurular') . '#duyuru-' . $a['id'], 'Sitede gör') : '',
    ])) ?>
    <?php $fu = ann_featured_until($a); $live = !empty($a['featured']) && !empty($a['published']) && $fu >= $today; ?>
    <?= ui_card('Açılışta öne çıkar', implode('', [
        ($live ? ui_alert('Şu an sitenin açılışında gösteriliyor; ' . e(tr_date($fu)) . ' sonunda kendiliğinden kalkar.', 'ok') : ''),
        ui_toggle('featured', 'Siteye girenlere ortada göster', !empty($a['featured']), ['help' => 'Masaüstünde de telefonda da ekranın ortasında açılan bir pencereyle gösterilir. Aynı anda tek duyuru öne çıkar; bunu açarsanız diğerinin öne çıkarması kalkar.']),
        ui_text('featured_until', 'Ne zamana kadar?', (string) ($a['featured_until'] ?? ''), ['type' => 'date', 'help' => 'Boş bırakırsanız son başvuru gününe kadar gösterilir.' . ($fu !== '' && empty($a['featured_until']) ? ' Şu an: ' . e(tr_date($fu)) . '.' : '')]),
    ]), ['desc' => 'Önemli bir çağrıyı ziyaretçilerin kaçırmaması için.', 'class' => 'ann-feat']) ?>
  </aside>
</form>
<?php if (!$isNew): ?>
  <?= ui_card('Duyuruyu sil', '<form method="post" action="' . adm_url('duyurular/' . $a['id'] . '/sil') . '" data-confirm="Bu duyuru kalıcı olarak silinsin mi?" data-confirm-detail="Duyuru sitedeki takvimden ve listelerden kalkar. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz.">' . adm_csrf_field() . '<p class="muted" style="margin-bottom:12px">Duyuru sitedeki takvimden ve listelerden kalkar. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz.</p><button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Duyuruyu sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif; ?>
<?php
adm_layout($isNew ? 'Yeni duyuru' : 'Duyuruyu düzenle', (string) ob_get_clean(), [
    'section' => 'duyurular',
    'crumbs'  => [['Duyurular', adm_url('duyurular')]],
    'form'    => 'ann-form',
]);
