<?php
/**
 * Hizmetler: liste, sıralama, ekleme, düzenleme, silme.
 * Veri: content 'services' (adres => hizmet). Varsayılan: app/data/services.php
 * Doğrulama ve kayıt app/content.php içindedir (service_upsert, service_delete, services_reorder); MCP de aynı işlevleri kullanır.
 */

$all   = services();
$L     = service_limits();
$slug  = $rest[0] ?? null;
$isNew = $slug === 'yeni';
$colors = service_colors();
$hex    = service_color_hex();

/* ---------- Sıralamayı kaydet ---------- */
if ($slug === 'sirala' && $method === 'POST') {
    $r = services_reorder(post_list('order', 80));
    $r['ok'] ? adm_flash('Hizmet sırası kaydedildi. Dosya numaraları sıraya göre yenilendi.') : adm_flash(implode(' ', $r['errors']), 'err');
    adm_go('hizmetler');
}

/* ---------- Liste ---------- */
if ($slug === null) {
    $rows = '';
    foreach ($all as $s => $v) {
        $rows .= '<div class="rp__item" data-rp-item><button class="rp__handle" type="button" data-rp-handle aria-label="Sürükleyerek sırala">' . ui_icon('dots-six-vertical') . '</button>'
            . '<input type="hidden" name="order[0]" value="' . e($s) . '">'
            . '<a class="list__main" href="' . adm_url('hizmetler/' . $s) . '" style="text-decoration:none;color:inherit">'
            . '<span class="svc-chip" style="--c:' . e($hex[$v['color']] ?? '#ccc') . '" title="' . e($colors[$v['color']] ?? '') . '"><b>03.' . (int) $v['no'] . '</b></span>'
            . '<span class="svc-main"><span class="list__title">' . e($v['title']) . '</span>'
            . '<span class="list__meta"><span>' . e(mb_strimwidth($v['short'] ?? '', 0, 110, '…')) . '</span></span>'
            . '<span class="list__meta"><span>' . count($v['programs'] ?? []) . ' program</span><span>' . count($v['steps'] ?? []) . ' adım</span><span>' . count($v['docs'] ?? []) . ' evrak</span><span>' . count($v['faq'] ?? []) . ' soru</span>' . (!empty($v['law']) ? '<span>mevzuat alıntısı</span>' : '') . '</span></span></a>'
            . '<div class="rp__tools"><button class="rp__btn" type="button" data-rp-up aria-label="Yukarı">↑</button><button class="rp__btn" type="button" data-rp-down aria-label="Aşağı">↓</button>'
            . '<a class="btn btn--ghost btn--sm" href="' . adm_url('hizmetler/' . $s) . '">' . ui_icon('pencil-simple') . 'Düzenle</a></div></div>';
    }
    $body = '<form id="order-form" method="post" action="' . adm_url('hizmetler/sirala') . '">' . adm_csrf_field()
        . '<div class="rp" data-rp data-rp-name="order"><div class="rp__items" data-rp-items>' . $rows . '</div></div></form>';
    adm_layout('Hizmetler', ui_card('Hizmet dosyaları', $body, [
        'desc' => 'Sitedeki sırayı değiştirmek için satırları sürükleyin ya da oklarla taşıyın; ardından kaydedin. Dosya numaraları ("03.1", "03.2"…) sıraya göre kendiliğinden verilir. Bir hizmeti düzenlemek için üzerine tıklayın.',
    ]), [
        'section'  => 'hizmetler',
        'subtitle' => count($all) . ' hizmet dosyası (en az ' . $L['services'][0] . ', en çok ' . $L['services'][1] . '). Menü, ana sayfa, Hizmetler sayfası ve iletişim formu bu listeden beslenir.',
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
    $r = service_delete($slug);
    if (!$r['ok']) {
        adm_flash(implode(' ', $r['errors']), 'err');
        adm_go('hizmetler/' . $slug);
    }
    adm_flash('Hizmet silindi. Dosya numaraları yenilendi; Geçmiş bölümünden geri alabilirsiniz.');
    adm_go('hizmetler');
}

$svc = $isNew
    ? ['no' => '', 'title' => '', 'nav' => '', 'tab' => '', 'color' => 'blue', 'short' => '', 'lead' => '', 'programs' => [], 'steps' => [], 'docs' => [], 'fit' => '', 'unfit' => '', 'faq' => []]
    : $all[$slug];
$law    = is_array($svc['law'] ?? null) ? $svc['law'] : ['source' => '', 'text' => '', 'plain' => ''];
$origLaw = $isNew ? '' : (string) ($all[$slug]['law']['text'] ?? '');
$errors = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $pairs = fn(string $k, array $f) => array_map(fn($r) => [$r[$f[0]], $r[$f[1]]], post_rows($k, $f, 3000));
    $raw = [
        'title' => post_str('title', 3000), 'nav' => post_str('nav', 3000), 'tab' => post_str('tab', 3000), 'color' => post_str('color', 40),
        'short' => post_str('short', 3000), 'lead' => post_str('lead', 3000),
        'programs' => $pairs('programs', ['n', 'd']), 'steps' => $pairs('steps', ['t', 'd']), 'docs' => post_list('docs', 3000),
        'fit' => post_str('fit', 3000), 'unfit' => post_str('unfit', 3000),
        'law' => ['source' => post_str('law_source', 3000), 'text' => post_str('law_text', 5000), 'plain' => post_str('law_plain', 3000)],
        'faq' => $pairs('faq', ['q', 'a']),
    ];
    $svc = service_clean($raw);
    $svc['no'] = $isNew ? '' : (string) $all[$slug]['no'];
    $law = is_array($svc['law'] ?? null) ? $svc['law'] : ['source' => '', 'text' => '', 'plain' => ''];
    $unchanged = !$isNew && json_encode($svc) === json_encode($all[$slug]);
    if ($unchanged) {
        adm_flash('Değişiklik yok; kayıt oluşturulmadı.');
        adm_go('hizmetler/' . $slug);
    }
    $r = service_upsert($isNew ? null : $slug, $raw, ['slug' => post_str('slug', 120), 'confirm_law' => post_str('law_ack', 2) === '1']);
    if ($r['ok']) {
        adm_flash($isNew ? 'Hizmet eklendi.' : 'Değişiklikler kaydedildi.');
        adm_go('hizmetler/' . $r['slug']);
    }
    $errors = $r['errors'];
}

/* ---------- Düzenleme formu ---------- */
$pairVals = fn(array $rows, array $k) => array_map(fn($x) => [$k[0] => $x[0] ?? '', $k[1] => $x[1] ?? ''], $rows);
$colorOpts = '';
foreach ($colors as $k => $n) {
    $colorOpts .= '<option value="' . e($k) . '" data-hex="' . e($hex[$k]) . '"' . ($k === $svc['color'] ? ' selected' : '') . '>' . e($n) . '</option>';
}
$colorField = ui_wrap('color', 'Dosya rengi', '<div class="svc-color"><span class="svc-color__sw" data-svc-sw style="--c:' . e($hex[$svc['color']] ?? '#ccc') . '"></span><div class="sel"><select class="inp" id="f-color" name="color" aria-describedby="f-color-h">' . $colorOpts . '</select></div></div>', [
    'help' => 'Sitenin dosya renklerinden biri: dosya sırtı, menüdeki sekme ve sayfa kenarlığı bu renkte olur.',
]);

ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong><ul class="errs"><li>' . implode('</li><li>', array_map('e', $errors)) . '</li></ul>');
?>
<form id="svc-form" method="post" action="<?= adm_url('hizmetler/' . ($isNew ? 'yeni' : $slug)) ?>" class="split" novalidate>
  <?= adm_csrf_field() ?>
  <input type="hidden" name="law_ack" value="0" data-law-ack>
  <div style="display:grid;gap:20px;min-width:0">
    <?= ui_card('Temel bilgiler', implode('', [
        ui_text('title', 'Başlık', $svc['title'], ['required' => true, 'maxlength' => $L['title'][1], 'counter' => true, 'placeholder' => 'Örn: TÜBİTAK Destekleri', 'id' => 'f-title', 'help' => 'Dosyanın ve sayfanın başlığı. ' . $L['title'][0] . ' ile ' . $L['title'][1] . ' karakter.']),
        '<div class="grid2">',
        ui_text('nav', 'Menüdeki ad', $svc['nav'], ['required' => true, 'maxlength' => $L['nav'][1], 'counter' => true, 'help' => 'Menüde, iletişim formunun konu listesinde ve sayfa üstündeki "Konu" satırında görünür.']),
        ui_text('tab', 'Dosya sırtı etiketi', $svc['tab'], ['required' => true, 'maxlength' => $L['tab'][1], 'counter' => true, 'help' => 'Dosya dolabında sırtın üstündeki kısa etiket; büyük harfle basılır (örn. TÜBİTAK).']),
        $colorField,
        '</div>',
        $isNew
            ? ui_text('slug', 'Sayfa adresi', '', ['maxlength' => $L['slug'][1], 'slug_from' => '#f-title', 'help' => 'Başlıktan otomatik oluşur: /urunler/detay/<b>adres</b>. Kayıttan sonra değiştirilemez; boş bırakırsanız başlıktan üretilir.'])
            : ui_text('slug_ro', 'Sayfa adresi', '/urunler/detay/' . $slug, ['readonly' => true, 'help' => 'Arama motorlarındaki sıralamayı korumak için mevcut adresler değiştirilmez.']),
        '<p class="fld__help">Dosya numarası: <b>' . ($isNew ? 'listenin sonuna göre verilir' : '03.' . (int) $svc['no']) . '</b>. Numaralar listedeki sıraya göre kendiliğinden verilir; sırayı Hizmetler listesinden değiştirin.</p>',
    ])) ?>

    <?= ui_card('Metinler', implode('', [
        ui_textarea('short', 'Kısa açıklama', $svc['short'], ['required' => true, 'rows' => 2, 'maxlength' => $L['short'][1], 'counter' => true, 'help' => 'Dosya kartında, ana sayfadaki dizinde ve arama sonuçlarında görünen tek cümle. Sınır ' . $L['short'][0] . '-' . $L['short'][1] . ' karakter.']),
        ui_textarea('lead', 'Giriş paragrafı', $svc['lead'], ['required' => true, 'rows' => 4, 'maxlength' => $L['lead'][1], 'counter' => true, 'help' => 'Hizmet sayfasında başlığın altındaki büyük paragraf.']),
    ])) ?>

    <?= ui_card('Kimin için?', implode('', [
        ui_textarea('fit', 'Uygun olduğu durumlar', $svc['fit'], ['required' => true, 'rows' => 3, 'maxlength' => $L['fit'][1], 'counter' => true, 'help' => 'Hizmet sayfasında "Uygun" sütunu; Dosya dolabında kartta da görünür.']),
        ui_textarea('unfit', 'Uygun olmadığı durumlar', $svc['unfit'], ['required' => true, 'rows' => 3, 'maxlength' => $L['unfit'][1], 'counter' => true, 'help' => 'Hizmet sayfasında "Uygun değil" sütunu.']),
    ]), ['desc' => 'Ek-1 bölümü.']) ?>

    <?= ui_card('Mevzuat alıntısı', '<div class="svc-quote">' . ui_icon('warning-circle') . '<p><b>Alıntı metni resmî mevzuattan birebir alınmalıdır.</b> Kelime eklemeyin, çıkarmayın, özetlemeyin; kısaltma için "…" kullanın. Sade Türkçesi ayrı alandadır ve serbestçe yazılır. İsterseniz üç alanı da boş bırakıp alıntıyı hiç göstermeyebilirsiniz.</p></div>'
        . '<div class="grid2">' . ui_text('law_source', 'Kaynak', $law['source'], ['maxlength' => $L['law_source'][1], 'counter' => true, 'placeholder' => 'Örn: 5746 sayılı Kanun, Madde 2/a'])
        . '</div>'
        . ui_textarea('law_text', 'Alıntı (resmî metinden birebir)', $law['text'], ['rows' => 4, 'maxlength' => $L['law_text'][1], 'counter' => true, 'id' => 'f-law-text', 'help' => 'Değiştirirseniz kaydederken sizden onay istenir.'])
        . ui_textarea('law_plain', 'Sade Türkçesi', $law['plain'], ['rows' => 2, 'maxlength' => $L['law_plain'][1], 'counter' => true, 'help' => 'Kartın arka yüzünde, "Sade Türkçesi" düğmesine basınca görünür.']),
        ['desc' => 'Ek-1 bölümünde dönen kart. Üç alan birlikte dolu ya da birlikte boş olmalıdır.']) ?>

    <?= ui_card('Dosyadaki programlar', ui_repeater('programs', '', $pairVals($svc['programs'], ['n', 'd']), [
        ['key' => 'n', 'label' => 'Program adı', 'maxlength' => $L['program_name'][1], 'placeholder' => 'Örn: 1501 Sanayi Ar-Ge', 'class' => 'span-2'],
        ['key' => 'd', 'label' => 'Ne için (tek cümle)', 'maxlength' => $L['program_desc'][1], 'class' => 'span-2'],
    ], ['add' => 'Program ekle']), ['desc' => $L['programs'][0] . ' ile ' . $L['programs'][1] . ' program. Dosya kartında adlar madde işaretiyle, hizmet sayfasında ad ve açıklama tablo olarak görünür.']) ?>

    <?= ui_card('Ne yapıyoruz?', ui_repeater('steps', '', $pairVals($svc['steps'], ['t', 'd']), [
        ['key' => 't', 'label' => 'Başlık', 'maxlength' => $L['step_title'][1], 'placeholder' => 'Örn: Ön okuma', 'class' => 'span-2'],
        ['key' => 'd', 'label' => 'Açıklama', 'type' => 'textarea', 'rows' => 2, 'maxlength' => $L['step_text'][1], 'class' => 'span-2'],
    ], ['add' => 'Adım ekle']), ['desc' => $L['steps'][0] . ' ile ' . $L['steps'][1] . ' adım. Hizmet sayfasında "Madde 1, Madde 2…" olarak numaralanır; sıra önemlidir.']) ?>

    <?= ui_card('Sizden isteyeceğimiz evrak', ui_repeater('docs', '', $svc['docs'], [['key' => '', 'placeholder' => 'Örn: Son iki yılın bilanço ve gelir tablosu', 'maxlength' => $L['doc'][1]]], ['add' => 'Evrak ekle']),
        ['desc' => $L['docs'][0] . ' ile ' . $L['docs'][1] . ' evrak. Ziyaretçi bunları işaretleyerek hazırlık listesini yazdırabilir.']) ?>

    <?= ui_card('Sık sorulanlar', ui_repeater('faq', '', $pairVals($svc['faq'], ['q', 'a']), [
        ['key' => 'q', 'label' => 'Soru', 'maxlength' => $L['faq_q'][1], 'class' => 'span-2'],
        ['key' => 'a', 'label' => 'Yanıt', 'type' => 'textarea', 'rows' => 3, 'maxlength' => $L['faq_a'][1], 'class' => 'span-2'],
    ], ['add' => 'Soru ekle']), ['desc' => $L['faq'][0] . ' ile ' . $L['faq'][1] . ' soru. Sayfada açılır not olarak görünür.']) ?>
  </div>

  <aside class="split__side">
    <?= ui_card('Yayın', implode('', [
        '<button class="btn btn--block" type="submit">' . ui_icon('floppy-disk') . ($isNew ? 'Hizmeti ekle' : 'Kaydet') . '</button>',
        !$isNew ? ui_view_link(service_url($slug), 'Sayfayı sitede aç') : '',
        !$isNew ? ui_history_link('services') : '',
    ])) ?>
  </aside>
</form>
<?php if (!$isNew): ?>
  <?= ui_card('Hizmeti sil', '<form method="post" action="' . adm_url('hizmetler/' . $slug . '/sil') . '" data-confirm="&quot;' . e($svc['title']) . '&quot; silinsin mi? Sayfası yayından kalkar ve sonraki dosyaların numaraları kayar.">' . adm_csrf_field()
      . '<p class="muted" style="margin-bottom:12px">Hizmet sayfası yayından kalkar ve menülerden çıkar; sonraki dosyaların numaraları bir azalır. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz. En az ' . $L['services'][0] . ' hizmet kalmalıdır.</p>'
      . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Hizmeti sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif; ?>
<script>
(function () {
  var f = document.getElementById('svc-form'); if (!f) return;
  var sel = document.getElementById('f-color'), sw = document.querySelector('[data-svc-sw]');
  if (sel && sw) sel.addEventListener('change', function () { sw.style.setProperty('--c', sel.options[sel.selectedIndex].getAttribute('data-hex')); });
  var ta = document.getElementById('f-law-text'), ack = f.querySelector('[data-law-ack]');
  var orig = <?= json_encode(trim($origLaw), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
  f.addEventListener('submit', function (e) {
    if (orig !== '' && ta && ta.value.trim() !== orig) {
      if (window.confirm('Mevzuat alıntısı değişiyor.\n\nBu metin resmî mevzuattan birebir alıntıdır. Değişikliği kanunun güncel metniyle karşılaştırdınız mı? Kaydetmek için Tamam\'a basın.')) ack.value = '1'; else e.preventDefault();
    }
  });
})();
</script>
<?php
adm_layout($isNew ? 'Yeni hizmet' : $svc['title'], (string) ob_get_clean(), [
    'section' => 'hizmetler',
    'crumbs'  => [['Hizmetler', adm_url('hizmetler')]],
    'form'    => 'svc-form',
]);
