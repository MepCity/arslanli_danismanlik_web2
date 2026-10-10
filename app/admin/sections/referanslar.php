<?php
/**
 * Referanslar (kurum logoları ve kaşe maskeleri): liste, sıralama, ekleme, düzenleme, silme.
 * Veri: content 'refs' (liste). Doğrulama ve kayıt app/content.php içindedir (ref_upsert, ref_delete, refs_reorder).
 * Sitede yalnızca kaşe maskesi görünür; yüklenen logodan sunucuda üretilir (ref_make_ink, GD).
 */

$all   = refs_list();
$L     = refs_limits();
$arg   = $rest[0] ?? null;
$isNew = $arg === 'yeni';
$modes = ['auto' => 'Otomatik algıla', 'dark' => 'Koyu renkli logo (açık zeminde)', 'light' => 'Açık renkli logo (koyu zeminde)'];

/* ---------- Sıralamayı kaydet ---------- */
if ($arg === 'sirala' && $method === 'POST') {
    $r = refs_reorder(post_list('order', 80));
    $r['ok'] ? adm_flash('Referans sırası kaydedildi.') : adm_flash(implode(' ', $r['errors']), 'err');
    adm_go('referanslar');
}

/* ---------- Liste ---------- */
if ($arg === null) {
    $rows = '';
    foreach ($all as $r) {
        $edit = adm_url('referanslar/' . $r['id']);
        $rows .= '<div class="rp__item ref-row" data-rp-item><button class="rp__handle" type="button" data-rp-handle aria-label="Sürükleyerek sırala">' . ui_icon('dots-six-vertical') . '</button>'
            . '<input type="hidden" name="order[0]" value="' . e($r['id']) . '">'
            . '<a class="ref-row__main" href="' . $edit . '"><span class="ref-logo"><img src="' . e(media_url($r['logo'])) . '" alt="" loading="lazy" draggable="false"></span>'
            . '<span class="ref-ink" style="--src:url(\'' . e(media_url($r['ink'])) . '\')" aria-hidden="true"></span>'
            . '<span class="list__title">' . e($r['name']) . '</span></a>'
            . '<div class="rp__tools"><button class="rp__btn" type="button" data-rp-up aria-label="Yukarı">↑</button><button class="rp__btn" type="button" data-rp-down aria-label="Aşağı">↓</button>'
            . '<a class="btn btn--ghost btn--sm" href="' . $edit . '">' . ui_icon('pencil-simple') . 'Düzenle</a></div></div>';
    }
    $body = '<form id="order-form" method="post" action="' . adm_url('referanslar/sirala') . '">' . adm_csrf_field()
        . '<div class="rp" data-rp data-rp-name="order"><div class="rp__items" data-rp-items>' . $rows . '</div></div></form>';
    $main = ui_card('Kurum logoları', $body, ['desc' => 'Sitedeki sırayı değiştirmek için satırları sürükleyin ya da oklarla taşıyın; ardından kaydedin. Mavi görünen sitede kullanılan kaşedir; geniş ekranda yanında yüklenen logo da görünür.']);
    $broken = refs_problems();
    $warn = $broken ? ui_alert('<strong>Bazı referansların dosyası eksik:</strong> ' . e(implode(', ', array_keys($broken))) . '. Bu kurumların logosunu yeniden yükleyin (yedekten geri yüklemede görseller atlanmış olabilir).') : '';
    adm_layout('Referanslar', $warn . '<div class="split"><div style="min-width:0">' . $main . '</div><aside class="split__side">' . ui_card('Sitede nerede görünür?', '<p class="muted">Ana sayfadaki kaşe şeridi, Referanslar sayfasındaki kaşe masası ve kayıt defteri bu listeden beslenir. En az ' . $L['count'][0] . ', en çok ' . $L['count'][1] . ' kurum olabilir; kaşe masasında ilk dört kurumun kaşesi hazır basılı durur.</p>') . '</aside></div>', [
        'section'  => 'referanslar',
        'subtitle' => count($all) . ' kurum. Sitede logonun kendisi değil, ondan üretilen tek renkli kaşe görünür.',
        'actions'  => ui_view_link(url('referans')) . ui_history_link('refs') . '<a class="btn btn--sm" href="' . adm_url('referanslar/yeni') . '">' . ui_icon('plus') . 'Yeni referans</a>',
        'form'     => 'order-form',
    ]);
}

$cur = null;
if (!$isNew) {
    foreach ($all as $r) {
        if ($r['id'] === $arg) $cur = $r;
    }
    if (!$cur) {
        adm_flash('Referans bulunamadı.', 'err');
        adm_go('referanslar');
    }
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && $cur) {
    $r = ref_delete($cur['id']);
    $r['ok'] ? adm_flash('Referans silindi. Geçmişten geri alabilirsiniz.') : adm_flash(implode(' ', $r['errors']), 'err');
    adm_go($r['ok'] ? 'referanslar' : 'referanslar/' . $cur['id']);
}

$ref = $cur ?? ['id' => '', 'name' => '', 'logo' => '', 'ink' => ''];
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $ref['name'] = post_str('name', 200);
    $up  = $_FILES['logo'] ?? null;
    $has = is_array($up) && ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $logo = null;
    if ($has) {
        if ($up['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $up['tmp_name'])) {
            $errors[] = $up['error'] === UPLOAD_ERR_INI_SIZE || $up['error'] === UPLOAD_ERR_FORM_SIZE ? 'Logo dosyası sunucunun yükleme sınırını aşıyor.' : 'Logo yüklenemedi; tekrar deneyin.';
        } else {
            $logo = ['path' => (string) $up['tmp_name'], 'uploaded' => true];
        }
    }
    $mode = isset($modes[$_POST['ink_mode'] ?? '']) ? $_POST['ink_mode'] : 'auto';
    if (!$errors) {
        if ($cur && !$logo && $ref['name'] === $cur['name']) {
            adm_flash('Değişiklik yok; kayıt oluşturulmadı.');
            adm_go('referanslar/' . $cur['id']);
        }
        content_guard_require(['refs']);   // sayfa açıldıktan sonra referanslar değiştiyse logo işlenmeden reddedilir (iyimser kilit)
        $r = ref_upsert($cur['id'] ?? null, $ref['name'], $logo, $mode);
        if ($r['ok']) {
            adm_flash($isNew ? 'Referans eklendi; kaşe görünümü logodan üretildi.' : 'Referans kaydedildi.');
            adm_go('referanslar/' . $r['id']);
        }
        $errors = $r['errors'];
    }
}

/* ---------- Düzenleme formu ---------- */
$logoUrl = $ref['logo'] !== '' ? media_url($ref['logo']) : '';
$inkUrl  = $ref['ink'] !== '' ? media_url($ref['ink']) : '';
$prev = $inkUrl === ''
    ? '<p class="muted">Logoyu seçip kaydettiğinizde kaşe görünümü sunucuda otomatik üretilir ve burada görünür.</p>'
    : '<div class="ref-stamps">'
        . '<span class="ref-stamps__t" style="--ink:#23409c"><i class="ref-ink ref-ink--big" style="--src:url(\'' . e($inkUrl) . '\')"></i></span>'
        . '<span class="ref-stamps__t" style="--ink:#cf412f"><i class="ref-ink ref-ink--big" style="--src:url(\'' . e($inkUrl) . '\')"></i></span>'
        . '<span class="ref-stamps__t" style="--ink:#5b44b0"><i class="ref-ink ref-ink--big" style="--src:url(\'' . e($inkUrl) . '\')"></i></span></div>'
        . '<p class="fld__help" style="margin-top:10px">Referanslar sayfasındaki kaşe masasında kaşe bu üç mürekkepten birinde basılır; ana sayfada ve kayıt defterinde tek renkli görünür. Kaşe, logonun koyu kısımları opak, açık kısımları şeffaf olacak biçimde logodan üretilmiştir.</p>';

ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong> ' . implode(' ', array_map('e', $errors)));
?>
<form id="ref-form" method="post" enctype="multipart/form-data" action="<?= adm_url('referanslar/' . ($isNew ? 'yeni' : $ref['id'])) ?>" class="split" novalidate>
  <?= adm_csrf_field() ?>
  <div style="display:grid;gap:20px;min-width:0">
    <?= ui_card('Kurum', ui_text('name', 'Kurum adı', $ref['name'], ['required' => true, 'maxlength' => $L['name'][1], 'counter' => true, 'placeholder' => 'Örn: Örnek Havacılık A.Ş.', 'help' => 'Kaşe masasındaki "Sıradaki kaşe" satırında ve kayıt defterinde görünür.'])
        . ui_image('logo', 'Logo', $logoUrl !== '' ? $ref['logo'] : null, ['contain' => true, 'required' => true, 'help' => 'Şeffaf arka planlı PNG ya da WebP en iyisidir; beyaz zeminli JPG de olur (zemin otomatik atılır). En az 120 piksel genişlik. Yüklenen logo uploads klasörüne alınır.'])
        . ui_select('ink_mode', 'Logonun rengi (kaşe için)', $modes, 'auto', ['help' => 'Kaşe, logonun koyu kısımlarından üretilir. Logo açık renkliyse (beyaz yazı gibi) ve sonuç boş çıkıyorsa "Açık renkli logo" seçin. Yalnızca yeni logo yüklerken kullanılır.'])) ?>
    <?= ui_card('Sitede nasıl görünür', $prev) ?>
  </div>
  <aside class="split__side">
    <?= ui_card('Kaydet', '<button class="btn btn--block" type="submit">' . ui_icon('floppy-disk') . ($isNew ? 'Referansı ekle' : 'Kaydet') . '</button>'
        . ($isNew ? '<p class="fld__help">Yeni referans listenin sonuna eklenir. Sırayı Referanslar listesinden değiştirebilirsiniz.</p>' : '')
        . ui_view_link(url('referans'), 'Sitede gör') . ui_history_link('refs')) ?>
  </aside>
</form>
<?php if (!$isNew): ?>
  <?= ui_card('Referansı sil', '<form method="post" action="' . adm_url('referanslar/' . $ref['id'] . '/sil') . '" data-confirm="&quot;' . e($ref['name']) . '&quot; silinsin mi? Kaşesi sitedeki listelerden kalkar.">' . adm_csrf_field()
      . '<p class="muted" style="margin-bottom:12px">Kaşe sitedeki tüm listelerden kalkar. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz. En az ' . $L['count'][0] . ' referans kalmalıdır.</p>'
      . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Referansı sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif;
adm_layout($isNew ? 'Yeni referans' : (string) $ref['name'], (string) ob_get_clean(), [
    'section' => 'referanslar',
    'crumbs'  => [['Referanslar', adm_url('referanslar')]],
    'form'    => 'ref-form',
]);
