<?php
/**
 * Hizmetler: liste, sıralama, ekleme, düzenleme, silme.
 * Veri: content 'services' (slug => hizmet). Varsayılan: app/data/services.php
 */

$all    = services();
$glyphs = service_glyphs();
$slug  = $rest[0] ?? null;
$isNew = $slug === 'yeni';

/* ---------- Sıralamayı kaydet ---------- */
if ($slug === 'sirala' && $method === 'POST') {
    $order = array_values(array_filter(post_list('order'), fn($s) => isset($all[$s])));
    $new = [];
    foreach ($order as $s) $new[$s] = $all[$s];
    foreach ($all as $s => $v) if (!isset($new[$s])) $new[$s] = $v;
    content_put('services', $new) ? adm_flash('Hizmet sırası kaydedildi.') : adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
    adm_go('hizmetler');
}

/* ---------- Liste ---------- */
if ($slug === null) {
    $rows = '';
    foreach ($all as $s => $v) {
        $rows .= '<div class="rp__item" data-rp-item><button class="rp__handle" type="button" data-rp-handle aria-label="Sürükleyerek sırala">' . ui_icon('dots-six-vertical') . '</button>'
            . '<input type="hidden" name="order[0]" value="' . e($s) . '">'
            . '<a class="list__main" href="' . adm_url('hizmetler/' . $s) . '" style="text-decoration:none;color:inherit">'
            . '<span class="list__title">' . e($v['title']) . '</span>'
            . '<span class="list__meta"><span>' . e(mb_strimwidth($v['short'] ?? '', 0, 110, '…')) . '</span></span>'
            . '<span class="list__meta"><span>' . count($v['programs'] ?? []) . ' program</span><span>' . count($v['scope'] ?? []) . ' kapsam maddesi</span><span>' . count($v['faq'] ?? []) . ' soru</span></span></a>'
            . '<div class="rp__tools"><button class="rp__btn" type="button" data-rp-up aria-label="Yukarı">↑</button><button class="rp__btn" type="button" data-rp-down aria-label="Aşağı">↓</button>'
            . '<a class="btn btn--ghost btn--sm" href="' . adm_url('hizmetler/' . $s) . '">' . ui_icon('pencil-simple') . 'Düzenle</a></div></div>';
    }
    $body = '<form id="order-form" method="post" action="' . adm_url('hizmetler/sirala') . '">' . adm_csrf_field()
        . '<div class="rp" data-rp data-rp-name="order"><div class="rp__items" data-rp-items>' . $rows . '</div></div></form>';
    adm_layout('Hizmetler', ui_card('Hizmet alanları', $body, [
        'desc' => 'Sitedeki sırayı değiştirmek için satırları sürükleyin ya da oklarla taşıyın; ardından kaydedin. Bir hizmeti düzenlemek için üzerine tıklayın.',
    ]), [
        'section'  => 'hizmetler',
        'subtitle' => count($all) . ' hizmet alanı. Menü, ana sayfa, Hizmetler sayfası ve altbilgi bu listeden beslenir.',
        'actions'  => ui_view_link(url('hizmetler')) . ui_history_link('services') . '<a class="btn btn--sm" href="' . adm_url('hizmetler/yeni') . '">' . ui_icon('plus') . 'Yeni hizmet</a>',
        'form'     => 'order-form',
    ]);
}

if (!$isNew && !isset($all[$slug])) {
    adm_flash('Hizmet bulunamadı.', 'err');
    adm_go('hizmetler');
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && !$isNew) {
    if (count($all) <= 1) {
        adm_flash('Son hizmet silinemez.', 'err');
        adm_go('hizmetler/' . $slug);
    }
    unset($all[$slug]);
    content_put('services', $all) ? adm_flash('Hizmet silindi. Geçmişten geri alabilirsiniz.') : adm_flash('Silinemedi.', 'err');
    adm_go('hizmetler');
}

$svc = $isNew ? ['title' => '', 'nav' => '', 'glyph' => 'lattice', 'short' => '', 'lead' => '', 'programs' => [], 'scope' => [], 'for' => '', 'faq' => []] : $all[$slug];
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $svc = [
        'title'    => post_str('title', 120),
        'nav'      => post_str('nav', 60),
        'glyph'    => isset($glyphs[$_POST['glyph'] ?? '']) ? $_POST['glyph'] : 'lattice',
        'short'    => post_str('short', 240),
        'lead'     => post_str('lead', 1500),
        'programs' => post_list('programs', 120),
        'scope'    => array_map(fn($r) => [$r['h'], $r['t']], post_rows('scope', ['h', 't'])),
        'for'      => post_str('for', 600),
        'faq'      => array_map(fn($r) => [$r['q'], $r['a']], post_rows('faq', ['q', 'a'])),
    ];
    if ($svc['title'] === '') $errors[] = 'Başlık zorunludur.';
    if ($svc['short'] === '') $errors[] = 'Kısa açıklama zorunludur.';
    if ($svc['nav'] === '') $svc['nav'] = $svc['title'];

    $target = $slug;
    if ($isNew) {
        $target = adm_slug(post_str('slug', 80) ?: $svc['title']);
        if (isset($all[$target])) $errors[] = 'Bu adres başka bir hizmette kullanılıyor; farklı bir adres yazın.';
    }
    if (!$errors) {
        $all[$target] = $svc;
        if (content_put('services', $all)) {
            adm_flash($isNew ? 'Hizmet eklendi.' : 'Değişiklikler kaydedildi.');
            adm_go('hizmetler/' . $target);
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

/* ---------- Düzenleme formu ---------- */
ob_start();
if ($errors) echo ui_alert(e(implode(' ', $errors)));
?>
<form id="svc-form" method="post" action="<?= adm_url('hizmetler/' . ($isNew ? 'yeni' : $slug)) ?>" class="split">
  <?= adm_csrf_field() ?>
  <div style="display:grid;gap:20px">
    <?= ui_card('Temel bilgiler', implode('', [
        ui_text('title', 'Başlık', $svc['title'], ['required' => true, 'maxlength' => 120, 'placeholder' => 'Örn: TÜBİTAK Destekleri', 'id' => 'f-title']),
        '<div class="grid2">',
        ui_text('nav', 'Menüdeki kısa ad', $svc['nav'], ['maxlength' => 60, 'help' => 'Menü ve altbilgide görünür. Boş bırakırsanız başlık kullanılır.']),
        $isNew
            ? ui_text('slug', 'Sayfa adresi', '', ['maxlength' => 80, 'slug_from' => '#f-title', 'help' => 'Başlıktan otomatik oluşur: /urunler/detay/<b>adres</b>'])
            : ui_text('slug_ro', 'Sayfa adresi', '/urunler/detay/' . $slug, ['readonly' => true, 'help' => 'Arama motorlarındaki sıralamayı korumak için mevcut adresler değiştirilmez.']),
        '</div>',
        ui_textarea('short', 'Kısa açıklama', $svc['short'], ['required' => true, 'rows' => 2, 'maxlength' => 240, 'counter' => true, 'help' => 'Listelerde ve arama sonuçlarında görünen bir iki cümlelik özet.']),
        ui_textarea('lead', 'Giriş paragrafı', $svc['lead'], ['rows' => 4, 'maxlength' => 1500, 'counter' => true, 'help' => 'Hizmet sayfasında başlığın altındaki büyük paragraf.']),
        ui_textarea('for', 'Kimler için?', $svc['for'], ['rows' => 2, 'maxlength' => 600]),
    ])) ?>

    <?= ui_card('Öne çıkan programlar ve başlıklar', ui_repeater('programs', '', $svc['programs'], [['key' => '', 'placeholder' => 'Örn: 1501 Sanayi Ar-Ge', 'maxlength' => 120]], ['add' => 'Program ekle']), [
        'desc' => 'Hizmet sayfasında etiket olarak, ana sayfada satır sonunda görünür.',
    ]) ?>

    <?= ui_card('Neler yapıyoruz?', ui_repeater('scope', '', array_map(fn($x) => ['h' => $x[0] ?? '', 't' => $x[1] ?? ''], $svc['scope']), [
        ['key' => 'h', 'label' => 'Başlık', 'placeholder' => 'Örn: Ön değerlendirme', 'maxlength' => 120, 'class' => 'span-2'],
        ['key' => 't', 'label' => 'Açıklama', 'type' => 'textarea', 'rows' => 2, 'class' => 'span-2'],
    ], ['add' => 'Madde ekle']), ['desc' => 'Hizmet sayfasında üst üste binen kartlar olarak gösterilir. En iyi görünüm için 3-5 madde önerilir.']) ?>

    <?= ui_card('Sık sorulanlar', ui_repeater('faq', '', array_map(fn($x) => ['q' => $x[0] ?? '', 'a' => $x[1] ?? ''], $svc['faq']), [
        ['key' => 'q', 'label' => 'Soru', 'maxlength' => 200, 'class' => 'span-2'],
        ['key' => 'a', 'label' => 'Cevap', 'type' => 'textarea', 'rows' => 3, 'class' => 'span-2'],
    ], ['add' => 'Soru ekle']), ['desc' => 'Sayfada açılır liste olarak görünür ve arama motorlarına da sık sorulan sorular olarak bildirilir.']) ?>
  </div>

  <aside class="split__side">
    <?= ui_card('Yayın', implode('', [
        '<button class="btn btn--block" type="submit">' . ui_icon('floppy-disk') . ($isNew ? 'Hizmeti ekle' : 'Kaydet') . '</button>',
        !$isNew ? ui_view_link(service_url($slug), 'Sayfayı sitede aç') : '',
        !$isNew ? ui_history_link('services') : '',
    ])) ?>
    <?= ui_card('Sayfa görseli', ui_select('glyph', 'Hareketli çizim türü', $glyphs, $svc['glyph'], ['help' => 'Hizmet sayfasının üst kısmındaki, imleçle etkileşen çizim.'])) ?>
  </aside>
</form>
<?php if (!$isNew): ?>
  <?= ui_card('Hizmeti sil', '<form method="post" action="' . adm_url('hizmetler/' . $slug . '/sil') . '" data-confirm="&quot;' . e($svc['title']) . '&quot; silinsin mi? Sayfası yayından kalkar.">' . adm_csrf_field()
      . '<p class="muted" style="margin-bottom:12px">Hizmet sayfası yayından kalkar ve menülerden çıkar. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz.</p>'
      . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Hizmeti sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif;

adm_layout($isNew ? 'Yeni hizmet' : $svc['title'], (string) ob_get_clean(), [
    'section' => 'hizmetler',
    'crumbs'  => [['Hizmetler', adm_url('hizmetler')]],
    'form'    => 'svc-form',
]);
