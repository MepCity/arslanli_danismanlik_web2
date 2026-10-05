<?php
/**
 * Referanslar (kurum logoları): liste, sıralama, ekleme, düzenleme, silme, gösterilen sayı.
 * Veri: content 'refs' (liste). Gösterilen toplam sayı: content 'lists' içindeki 'ref_count'.
 */

$all = refs_list();
$arg = $rest[0] ?? null;
$isNew = $arg === 'yeni';
$saveFail = 'Kaydedilemedi: storage klasörü yazılabilir mi?';

/* ---------- Sıralamayı kaydet ---------- */
if ($arg === 'sirala' && $method === 'POST') {
    $new = [];
    $seen = [];
    foreach ((array) ($_POST['order'] ?? []) as $i) {
        if (!is_string($i) || !ctype_digit($i)) continue;
        $i = (int) $i;
        if (isset($all[$i]) && !isset($seen[$i])) { $seen[$i] = 1; $new[] = $all[$i]; }
    }
    foreach ($all as $i => $r) if (!isset($seen[$i])) $new[] = $r;
    content_put('refs', $new) ? adm_flash('Referans sırası kaydedildi.') : adm_flash($saveFail, 'err');
    adm_go('referanslar');
}

/* ---------- Gösterilen sayıyı kaydet ---------- */
if ($arg === 'sayi' && $method === 'POST') {
    $n = post_str('ref_count', 6);
    if (!ctype_digit($n) || (int) $n < 1 || (int) $n > 9999) {
        adm_flash('Sayı 1 ile 9999 arasında bir rakam olmalıdır.', 'err');
    } else {
        content_put('lists', array_merge((array) content_get('lists', []), ['ref_count' => (int) $n]))
            ? adm_flash('Gösterilen sayı kaydedildi.') : adm_flash($saveFail, 'err');
    }
    adm_go('referanslar');
}

/* ---------- Liste ---------- */
if ($arg === null) {
    $rows = '';
    foreach ($all as $i => $r) {
        $edit = adm_url('referanslar/' . $i);
        $rows .= '<div class="rp__item ref-row" data-rp-item><button class="rp__handle" type="button" data-rp-handle aria-label="Sürükleyerek sırala">' . ui_icon('dots-six-vertical') . '</button>'
            . '<input type="hidden" name="order[0]" value="' . $i . '">'
            . '<a class="ref-row__main" href="' . $edit . '"><span class="ref-logo"><img src="' . e(media_url($r['logo'])) . '" alt="" loading="lazy" draggable="false"></span>'
            . '<span class="list__title">' . e($r['name']) . '</span></a>'
            . '<div class="rp__tools"><button class="rp__btn" type="button" data-rp-up aria-label="Yukarı">↑</button><button class="rp__btn" type="button" data-rp-down aria-label="Aşağı">↓</button>'
            . '<a class="btn btn--ghost btn--sm" href="' . $edit . '">' . ui_icon('pencil-simple') . 'Düzenle</a></div></div>';
    }
    if ($all) {
        $body = '<form id="order-form" method="post" action="' . adm_url('referanslar/sirala') . '">' . adm_csrf_field()
            . '<div class="rp" data-rp data-rp-name="order"><div class="rp__items" data-rp-items>' . $rows . '</div></div></form>';
        $main = ui_card('Kurum logoları', $body, ['desc' => 'Sitedeki sırayı değiştirmek için satırları sürükleyin ya da oklarla taşıyın; ardından kaydedin. Bir logoyu düzenlemek için üzerine tıklayın.']);
    } else {
        $main = ui_card('Kurum logoları', '<div class="empty">' . ui_icon('buildings') . '<strong>Henüz referans yok</strong><span>Birlikte çalıştığınız kurumların logolarını ekleyin.</span><a class="btn btn--sm" href="' . adm_url('referanslar/yeni') . '">' . ui_icon('plus') . 'Yeni referans</a></div>');
    }
    $cnt = '<form method="post" action="' . adm_url('referanslar/sayi') . '">' . adm_csrf_field()
        . ui_text('ref_count', 'Toplam kurum sayısı', (string) site('ref_count'), ['type' => 'number', 'min' => 1, 'max' => 9999, 'step' => 1, 'inputmode' => 'numeric', 'required' => true,
            'help' => 'Ana sayfada &quot;25+ kurumla&quot;, Referanslar sayfasının başlığında &quot;25+&quot; ve Hakkımızda sayfasında &quot;25\'ten fazla kurumsal referans&quot; olarak görünür. Logo sayısından fazla olabilir.'])
        . '<button class="btn btn--soft btn--sm" type="submit" style="margin-top:12px">' . ui_icon('floppy-disk') . 'Sayıyı kaydet</button></form>';
    adm_layout('Referanslar', '<div class="split"><div style="min-width:0">' . $main . '</div><aside class="split__side">' . ui_card('Sitede gösterilen sayı', $cnt) . '</aside></div>', [
        'section'  => 'referanslar',
        'subtitle' => count($all) . ' kurum logosu. Ana sayfadaki kayan şerit, Hakkımızda ve Referanslar sayfaları bu listeden beslenir.',
        'actions'  => ui_view_link(url('referans')) . ui_history_link('refs') . '<a class="btn btn--sm" href="' . adm_url('referanslar/yeni') . '">' . ui_icon('plus') . 'Yeni referans</a>',
        'form'     => $all ? 'order-form' : null,
    ]);
}

$idx = null;
if (!$isNew) {
    if (!ctype_digit($arg) || !isset($all[(int) $arg])) {
        adm_flash('Referans bulunamadı.', 'err');
        adm_go('referanslar');
    }
    $idx = (int) $arg;
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && $idx !== null) {
    array_splice($all, $idx, 1);
    content_put('refs', $all) ? adm_flash('Referans silindi. Geçmişten geri alabilirsiniz.') : adm_flash('Silinemedi: storage klasörü yazılabilir mi?', 'err');
    adm_go('referanslar');
}

$ref = $isNew ? ['name' => '', 'logo' => ''] : $all[$idx];
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $stored = (string) $ref['logo']; // yalnızca sunucudaki değer
    $ref = ['name' => post_str('name', 80), 'logo' => $stored];
    if ($ref['name'] === '') $errors[] = 'Kurum adı zorunludur.';
    $up = $_FILES['logo'] ?? null;
    $has = is_array($up) && ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    if (!$errors && $has) {
        try {
            $ref['logo'] = upload_image($up, 'refs', 600);
        } catch (RuntimeException $ex) {
            $errors[] = $ex->getMessage();
        }
    } elseif ($isNew && !$has) {
        $errors[] = 'Bir logo seçin.';
    }
    if (!$errors) {
        if ($isNew) { $all[] = $ref; $idx = count($all) - 1; } else { $all[$idx] = $ref; }
        if (content_put('refs', $all)) {
            adm_flash($isNew ? 'Referans eklendi.' : 'Referans kaydedildi.');
            adm_go('referanslar/' . $idx);
        }
        $errors[] = $saveFail;
    }
}

/* ---------- Düzenleme formu ---------- */
$logoUrl = $ref['logo'] !== '' ? media_url($ref['logo']) : '';
$prev = '<div class="ref-prev" data-ref-prev>'
    . '<div class="ref-prev__bg ref-prev__bg--dark"><span class="ref-prev__tile ref-prev__tile--gray"><img data-ref-img src="' . e($logoUrl) . '" alt=""></span><small>Sitedeki görünüm</small></div>'
    . '<div class="ref-prev__bg ref-prev__bg--light"><span class="ref-prev__tile"><img data-ref-img src="' . e($logoUrl) . '" alt=""></span><small>Üzerine gelince</small></div>'
    . '</div><p class="fld__help" style="margin-top:10px">Sitede logolar beyaz kutuda ve gri tonlu görünür; imleç üzerine gelince renklenir.</p>';

ob_start();
if ($errors) echo ui_alert(e(implode(' ', $errors)));
?>
<form id="ref-form" method="post" enctype="multipart/form-data" action="<?= adm_url('referanslar/' . ($isNew ? 'yeni' : $idx)) ?>" class="split">
  <?= adm_csrf_field() ?>
  <div style="display:grid;gap:20px;min-width:0">
    <?= ui_card('Kurum', ui_text('name', 'Kurum adı', $ref['name'], ['required' => true, 'maxlength' => 80, 'placeholder' => 'Örn: Örnek Havacılık A.Ş.', 'help' => 'Referanslar sayfasındaki tam listede logonun yanında görünür.'])
        . ui_image('logo', 'Logo', $logoUrl !== '' ? $ref['logo'] : null, ['contain' => true, 'required' => true, 'help' => 'Şeffaf arka planlı PNG ya da WebP önerilir. Logo otomatik küçültülür.'])) ?>
    <?= ui_card('Sitede nasıl görünür', $prev) ?>
  </div>
  <aside class="split__side">
    <?= ui_card('Kaydet', '<button class="btn btn--block" type="submit">' . ui_icon('floppy-disk') . ($isNew ? 'Referansı ekle' : 'Kaydet') . '</button>'
        . ($isNew ? '<p class="fld__help">Yeni referans listenin sonuna eklenir. Sırayı Referanslar sayfasından değiştirebilirsiniz.</p>' : '')
        . ui_view_link(url('referans'), 'Sitede gör') . ui_history_link('refs')) ?>
  </aside>
</form>
<?php if (!$isNew): ?>
  <?= ui_card('Referansı sil', '<form method="post" action="' . adm_url('referanslar/' . $idx . '/sil') . '" data-confirm="&quot;' . e($ref['name']) . '&quot; silinsin mi? Logo sitedeki listelerden kalkar.">' . adm_csrf_field()
      . '<p class="muted" style="margin-bottom:12px">Logo sitedeki tüm listelerden kalkar. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz.</p>'
      . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Referansı sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif; ?>
<script>
(function () {
  var inp = document.getElementById('f-logo'), imgs = document.querySelectorAll('[data-ref-img]');
  if (!inp) return;
  var show = function (u) { imgs.forEach(function (i) { if (u) { i.src = u; i.hidden = false; } else { i.hidden = true; } }); };
  show(imgs[0] && imgs[0].getAttribute('src'));
  inp.addEventListener('change', function () { if (inp.files && inp.files[0]) show(URL.createObjectURL(inp.files[0])); });
})();
</script>
<?php
adm_layout($isNew ? 'Yeni referans' : (string) $ref['name'], (string) ob_get_clean(), [
    'section' => 'referanslar',
    'crumbs'  => [['Referanslar', adm_url('referanslar')]],
    'form'    => 'ref-form',
]);
