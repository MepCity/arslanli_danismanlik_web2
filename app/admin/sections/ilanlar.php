<?php
/**
 * İş ilanları: liste, ekleme, düzenleme, yayınlama, kapatma, yeniden açma ve silme.
 * Veri: storage/ilanlar.json (app/ilanlar.php). Genel başvuru formu ve aday havuzu bundan bağımsız çalışır; ilana gelen başvurular
 * "İş başvuruları" bölümünde ayrı süzülür.
 */

$id    = $rest[0] ?? null;
$today = date('Y-m-d');

/** Durum rozeti */
$stateBadge = function (array $x): string {
    return match (ilan_state($x)) {
        'yayinda' => '<span class="badge badge--ok"><i class="dot"></i>Yayında</span>',
        'doldu'   => '<span class="badge badge--warn">Süresi doldu</span>',
        'kapali'  => '<span class="badge badge--navy">Kapalı</span>',
        default   => '<span class="badge">Taslak</span>',
    };
};

/* ---------- Liste ---------- */
if ($id === null) {
    $list   = ilan_all();
    $counts = ilan_application_counts();
    // Açıklar önce, ardından taslaklar, sonra kapananlar; her grupta en son güncellenen üstte
    $rank = ['yayinda' => 0, 'taslak' => 1, 'doldu' => 2, 'kapali' => 3];
    usort($list, fn($a, $b) => [$rank[ilan_state($a)], (string) ($b['updated'] ?? '')] <=> [$rank[ilan_state($b)], (string) ($a['updated'] ?? '')]);
    $filter = (string) ($_GET['durum'] ?? '');
    $bucket = fn(array $x): string => match (ilan_state($x)) { 'yayinda' => 'yayinda', 'taslak' => 'taslak', default => 'kapali' };
    $n = ['' => count($list), 'yayinda' => 0, 'taslak' => 0, 'kapali' => 0];
    foreach ($list as $x) $n[$bucket($x)]++;
    $list = array_filter($list, fn($x) => $filter === '' || $bucket($x) === $filter);
    $chips = '';
    foreach (['' => 'Tümü', 'yayinda' => 'Yayında', 'taslak' => 'Taslak', 'kapali' => 'Kapalı'] as $k => $l) {
        $chips .= '<a class="chip' . ($filter === $k ? ' is-on' : '') . '" href="' . adm_url('ilanlar') . ($k ? '?durum=' . $k : '') . '">' . $l . ' <em>' . $n[$k] . '</em></a>';
    }
    $rows = '';
    foreach ($list as $x) {
        $meta = [];
        if ($x['area'] !== '') $meta[] = '<span>' . e($x['area']) . '</span>';
        $meta[] = '<span>' . e($x['city']) . ' · ' . e($x['type']) . '</span>';
        if ($x['deadline'] !== '') {
            $left = (int) round((strtotime($x['deadline']) - strtotime($today)) / 86400);
            $meta[] = '<span>Son başvuru: ' . e(tr_date($x['deadline'])) . (ilan_state($x) === 'yayinda' ? ' (' . ($left === 0 ? 'bugün' : $left . ' gün') . ')' : '') . '</span>';
        } else {
            $meta[] = '<span>Son başvuru günü yok</span>';
        }
        $apps = (int) ($counts['ilan'][$x['id']]['n'] ?? 0);
        $rows .= '<a class="list__row" href="' . adm_url('ilanlar/' . $x['id']) . '"><span class="list__main"><span class="list__title">' . e($x['title']) . '</span><span class="list__meta">' . implode('', $meta) . '</span></span>'
            . '<span class="list__side">' . $stateBadge($x) . '<span class="badge' . ($apps ? ' badge--coral' : '') . '">' . $apps . ' başvuru</span><span class="list__go">' . ui_icon('caret-right') . '</span></span></a>';
    }
    $body = '<div class="chips">' . $chips . '</div>'
        . ($rows ? '<div class="list" style="border-top:1px solid var(--line)">' . $rows . '</div>'
                 : '<div class="empty">' . ui_icon('file-text') . '<strong>' . ($n[''] ? 'Bu filtrede ilan yok' : 'Henüz iş ilanı yok') . '</strong><span>' . ($n[''] ? '' : 'İlan açtığınızda Kariyer sayfasında "Açık pozisyonlar" olarak görünür. Genel başvuru formu ilan olmasa da çalışmaya devam eder.') . '</span><a class="btn btn--soft btn--sm" href="' . adm_url('ilanlar/yeni') . '">Yeni ilan ekle</a></div>');
    adm_layout('İş ilanları', ui_card('', $body), [
        'section'  => 'ilanlar',
        'subtitle' => 'Yayındaki ilanlar Kariyer sayfasında "Açık pozisyonlar" olarak listelenir ve kendi sayfasında başvuru alır. İlan olmasa da genel başvuru formu (aday havuzu) çalışır.',
        'actions'  => ui_view_link(url('kariyer')) . ui_history_link('ilanlar') . '<a class="btn btn--sm" href="' . adm_url('ilanlar/yeni') . '">' . ui_icon('plus') . 'Yeni ilan ekle</a>',
    ]);
}

$isNew = $id === 'yeni';
$old   = $isNew ? null : ilan_find($id);
if (!$isNew && !$old) {
    adm_flash('İlan bulunamadı.', 'err');
    adm_go('ilanlar');
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && $old) {
    ilan_delete($old['id']) ? adm_flash('İlan silindi. Bu ilana gelen başvurular silinmedi; İş başvuruları listesinde "silinmiş ilan" olarak durur.') : adm_flash('Silinemedi.', 'err');
    adm_go('ilanlar');
}

$x = $old ?? ['title' => '', 'area' => '', 'city' => 'İstanbul', 'type' => 'Tam zamanlı', 'experience' => '', 'summary' => '', 'duties' => [], 'requirements' => [], 'extras' => [], 'deadline' => '', 'status' => 'taslak'];
$cur    = (string) ($old['status'] ?? 'taslak');   // kayıtlı durum (formdaki denemeden bağımsız)
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $islem  = (string) ($_POST['islem'] ?? 'kaydet');
    $status = match ($islem) { 'yayinla', 'ac' => 'yayinda', 'kapat' => 'kapali', 'taslak' => 'taslak', default => $cur };
    [$x, $errors] = ilan_validate([
        'title'        => $_POST['title'] ?? '',
        'area'         => $_POST['area'] ?? '',
        'city'         => $_POST['city'] ?? '',
        'type'         => $_POST['type'] ?? '',
        'experience'   => $_POST['experience'] ?? '',
        'summary'      => $_POST['summary'] ?? '',
        'duties'       => $_POST['duties'] ?? '',
        'requirements' => $_POST['requirements'] ?? '',
        'extras'       => $_POST['extras'] ?? '',
        'deadline'     => $_POST['deadline'] ?? '',
        'status'       => $status,
    ], $old);
    if (!$errors) {
        if (ilan_upsert($x)) {
            $msg = match (true) {
                $islem === 'yayinla' => 'İlan yayınlandı; Kariyer sayfasında ve ' . ilan_path($x) . ' adresinde görünüyor.',
                $islem === 'ac'      => 'İlan yeniden açıldı; başvuru alıyor.',
                $islem === 'kapat'   => 'İlan kapatıldı. Adresi artık "ilan kapandı" iletisi gösterir; başvuru alınmaz. Eski başvurular durur.',
                $islem === 'taslak'  => 'İlan taslağa alındı; sitede görünmez.',
                default              => $old ? 'İlan kaydedildi.' : 'İlan eklendi (taslak).',
            };
            adm_flash($msg);
            adm_go('ilanlar/' . $x['id']);
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

$apps  = $old ? (int) (ilan_application_counts()['ilan'][$old['id']]['n'] ?? 0) : 0;
$state = $old ? ilan_state($old) : 'taslak';

ob_start();
if ($errors) echo ui_alert(e(implode(' ', $errors)));
if ($old && $state === 'doldu') echo ui_alert('Son başvuru günü (' . e(tr_date($old['deadline'])) . ') geçtiği için bu ilan sitede açık görünmüyor; adresi "ilan kapandı" iletisi gösteriyor. Yeniden başvuru almak için ileri bir tarih girip kaydedin ya da tarihi boş bırakın.', 'info');

$areaOpts = implode('', array_map(fn($a) => '<option value="' . e($a) . '"></option>', ilan_area_suggestions()));
$areaCtl  = '<input class="inp" type="text"' . ui_attrs(['maxlength' => 80, 'required' => true, 'autocomplete' => 'off', 'placeholder' => 'Örn: Teşvik danışmanlığı', 'help' => true], 'area') . ' list="il-areas" value="' . e($x['area']) . '" data-counter><datalist id="il-areas">' . $areaOpts . '</datalist>';
$listHelp = 'Her satıra bir madde yazın (en fazla 20 madde, her biri en çok 300 karakter).';

$actions = '';
if ($cur === 'yayinda') {
    $actions .= '<button class="btn btn--block" type="submit" name="islem" value="kaydet">' . ui_icon('floppy-disk') . 'Kaydet</button>'
        . '<button class="btn btn--soft btn--block" type="submit" name="islem" value="kapat">Kapat (başvuru almayı durdur)</button>'
        . '<button class="btn btn--ghost btn--block" type="submit" name="islem" value="taslak">Taslağa al</button>';
} elseif ($cur === 'kapali') {
    $actions .= '<button class="btn btn--block" type="submit" name="islem" value="ac">' . ui_icon('arrow-counter-clockwise') . 'Yeniden aç</button>'
        . '<button class="btn btn--soft btn--block" type="submit" name="islem" value="kaydet">' . ui_icon('floppy-disk') . 'Kaydet (kapalı kalsın)</button>'
        . '<button class="btn btn--ghost btn--block" type="submit" name="islem" value="taslak">Taslağa al</button>';
} else {
    $actions .= '<button class="btn btn--block" type="submit" name="islem" value="yayinla">' . ui_icon('paper-plane-tilt') . 'Yayınla</button>'
        . '<button class="btn btn--soft btn--block" type="submit" name="islem" value="kaydet">' . ui_icon('floppy-disk') . ($isNew ? 'Taslak olarak kaydet' : 'Taslağı kaydet') . '</button>';
}
?>
<form id="ilan-form" method="post" action="<?= adm_url('ilanlar/' . ($isNew ? 'yeni' : $old['id'])) ?>" class="split">
  <?= adm_csrf_field() ?>
  <div style="display:grid;gap:20px">
    <?= ui_card('İlan', implode('', [
        ui_text('title', 'Pozisyon adı', $x['title'], ['required' => true, 'maxlength' => 120, 'counter' => true, 'placeholder' => 'Örn: Hibe ve Teşvik Uzmanı']),
        '<div class="grid2">',
        ui_wrap('area', 'Alan / bölüm', $areaCtl, ['required' => true, 'help' => 'Kariyer sayfasındaki "Çalıştığımız alanlar" listesinden seçebilir ya da kendiniz yazabilirsiniz.']),
        ui_select('city', 'Şehir', ilan_cities(), $x['city'], ['values_are_labels' => true, 'empty' => 'Şehir seçin', 'required' => true]),
        ui_select('type', 'Çalışma türü', ilan_types(), $x['type'], ['values_are_labels' => true, 'required' => true]),
        ui_select('experience', 'Deneyim (isteğe bağlı)', (array) site('deneyim'), $x['experience'], ['values_are_labels' => true, 'empty' => 'Belirtilmedi']),
        '</div>',
        ui_textarea('summary', 'Kısa tanıtım', $x['summary'], ['rows' => 4, 'maxlength' => 600, 'counter' => true, 'placeholder' => 'Pozisyon nedir, ekipte nasıl bir rol üstlenecek? İki üç cümle yeterli.']),
    ])) ?>
    <?= ui_card('Görevler ve nitelikler', implode('', [
        ui_textarea('duties', 'Görevler', implode("\n", $x['duties']), ['rows' => 6, 'help' => 'Bu pozisyondaki kişi ne yapacak? ' . $listHelp]),
        ui_textarea('requirements', 'Aranan nitelikler', implode("\n", $x['requirements']), ['rows' => 6, 'help' => 'Adayda mutlaka aranan şartlar. ' . $listHelp . ' Yayınlamak için en az bir madde gerekir.']),
        ui_textarea('extras', 'Tercih sebebi olacaklar (isteğe bağlı)', implode("\n", $x['extras']), ['rows' => 4, 'help' => 'Zorunlu olmayan ama başvuruyu güçlendiren özellikler. ' . $listHelp]),
    ])) ?>
  </div>
  <aside class="split__side">
    <?= ui_card('Yayın', implode('', [
        $old ? '<p style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><span class="muted">Durum</span>' . $stateBadge($old) . '</p>' : '',
        $old ? '<p class="muted" style="overflow-wrap:anywhere">Adres: <code>' . e(ilan_path($old)) . '</code><br><small>' . ((string) ($old['published'] ?? '') === '' ? 'İlan ilk kez yayınlanana kadar adres, her kayıtta pozisyon adına göre yenilenir; yayınlandıktan sonra değişmez.' : 'Yayınlandığı için adres değişmez.') . '</small></p>' : '',
        ui_text('deadline', 'Son başvuru günü (isteğe bağlı)', $x['deadline'], ['type' => 'date', 'help' => 'Gün sonuna kadar başvuru alınır; ertesi gün ilan kendiliğinden kapanır. Boşsa ilan siz kapatana kadar açık kalır.']),
        $actions,
        $old && $state === 'yayinda' ? ui_view_link(ilan_url($old), 'Sitede gör') : ($old && $cur !== 'taslak' ? ui_view_link(ilan_url($old), 'Sayfayı gör') : ''),
    ])) ?>
    <?php if ($old): ?>
      <?= ui_card('Başvurular', '<p class="muted" style="margin-bottom:12px">' . ($apps ? $apps . ' kişi bu ilana başvurdu.' : 'Bu ilana henüz başvuru gelmedi.') . '</p><a class="btn btn--soft btn--sm" href="' . adm_url('basvurular?ilan=' . $old['id']) . '">' . ui_icon('briefcase') . 'Başvuranları gör</a>') ?>
    <?php endif; ?>
  </aside>
</form>
<?php if ($old): ?>
  <?= ui_card('İlanı sil', '<form method="post" action="' . adm_url('ilanlar/' . $old['id'] . '/sil') . '" data-confirm="Bu ilan kalıcı olarak silinsin mi? Başvurular silinmez.">' . adm_csrf_field() . '<p class="muted" style="margin-bottom:12px">İlan sitedeki listeden kalkar ve adresi "bulunamadı" döner. Bu ilana gelen başvurular silinmez; İş başvuruları bölümünde "silinmiş ilan" olarak durur. İlan geçmişten geri alınabilir. Başvuru almayı durdurmak istiyorsanız silmek yerine kapatın.</p><button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'İlanı sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif; ?>
<?php
adm_layout($isNew ? 'Yeni ilan' : 'İlanı düzenle', (string) ob_get_clean(), [
    'section' => 'ilanlar',
    'crumbs'  => [['İş ilanları', adm_url('ilanlar')]],
    'form'    => 'ilan-form',
]);
