<?php
/**
 * Yasal metinler: KVKK Aydınlatma Metni ve Çerez Politikası maddeleri (madde ekleme, silme, sıralama, düzenleme).
 * Veri: content 'legal' (belge => madde listesi). Varsayılan: app/data/legal.php. Doğrulama ve kayıt app/legal.php içindedir (legal_save); MCP de aynı işlevi kullanır.
 * Madde numaraları, içindekiler ve bağlantı kimlikleri sıradan kendiliğinden üretilir. "Kontrol edildi" işareti Güvenlik ve yedek bölümündedir.
 */

$docs = legal_docs();
$doc  = $rest[0] ?? 'kvkk';
if (!isset($docs[$doc])) {
    adm_go('yasal');
}
$LM = legal_limits();

$errors = [];
$arts   = null;   // hata durumunda gönderilen maddeler (metin kutusu biçiminde)

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $raw = [];
    foreach ((array) ($_POST['articles'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $g = fn(string $k) => is_string($row[$k] ?? null) ? mb_substr((string) $row[$k], 0, 40000) : '';
        $raw[] = legal_article_from_text($g('title'), $g('paras'), $g('items'), $g('plain'), $g('anchor'));
    }
    $r = legal_save($doc, $raw);
    if ($r['ok']) {
        adm_flash($r['changed'] ? 'Değişiklikler kaydedildi; sayfa sitede güncellendi.' : 'Değişiklik yok; kayıt oluşturulmadı.');
        adm_go('yasal/' . $doc);
    }
    $errors = $r['errors'];
    $arts = array_map('legal_article_to_text', legal_clean($doc, $raw));
}
$arts ??= array_map('legal_article_to_text', legal_get($doc));

/* ---------- Görünüm ---------- */
$tabs = '';
foreach ($docs as $k => $d) {
    $tabs .= '<a class="tab' . ($k === $doc ? ' is-on' : '') . '" href="' . adm_url('yasal/' . $k) . '"' . ($k === $doc ? ' aria-current="page"' : '') . '>' . e($d[1]) . '</a>';
}

$help = '<div class="ysl__help"><p>Her kutu bir <b>madde</b>dir; sayfada sırayla numaralanır (Madde 1, Madde 2…) ve içindekiler listesi başlıklardan kendiliğinden oluşur. Maddeyi eklemek, silmek ya da taşımak için sağdaki düğmeleri kullanın.</p>'
    . '<ul class="ysl__rules"><li><b>Fıkralar:</b> her fıkra ayrı bir paragraftır; fıkraların arasına <b>boş bir satır</b> bırakın. Bir fıkranın içinde satır sonu gerekirse (adres bloğu gibi) boş satır bırakmadan alt satıra geçin.</li>'
    . '<li><b>Liste maddeleri:</b> her satıra bir madde (a, b, c… harfleri kendiliğinden verilir).</li>'
    . '<li><b>Sade Türkçesi:</b> maddenin altındaki açılır özet; isterseniz boş bırakın.</li>'
    . '<li>Metinlerde <code>[kalın]…[/kalın]</code>, <code>[eğik]…[/eğik]</code>, <code>[vurgu]…[/vurgu]</code> işaretleri ve <code>{firma}</code>, <code>{eposta_baglanti}</code> gibi yer tutucular yazılabilir; HTML yazılamaz.</li></ul></div>';
$vars = '';
foreach (array_merge(['firma' => 'firmanın adı', 'firma_kisa' => 'firmanın kısa adı', 'adres' => 'açık adres', 'telefon' => 'telefon', 'eposta_baglanti' => 'e-posta (tıklanabilir)', 'vergi_dairesi' => 'vergi dairesi', 'vergi_no' => 'vergi numarası'], legal_local_vars($doc)) as $v => $d) {
    $vars .= '<button type="button" class="txf__var" data-ins="{' . e($v) . '}" title="' . e($d) . '">{' . e($v) . '}<small>' . e($d) . '</small></button>';
}
$cond = '';
if ($doc === 'cerez') {
    $chips = '';
    foreach (legal_conditions() as $code => $name) {
        $chips .= '<button type="button" class="txf__var" data-ins="[koşul: ' . e($name) . '] " title="Fıkranın başına eklenir">[koşul: ' . e($name) . ']</button>';
    }
    $cond = '<div class="ysl__cond">' . ui_icon('info') . '<div><p><b>Siteyi tarayarak üretilen kısım.</b> Çerez Politikası’nın bazı fıkraları sitenin kendi kodu taranarak seçilir: sitede çerez, tarayıcı depolaması, dış sunucu ya da gömülü içerik bulunup bulunmamasına göre farklı fıkra görünür. Bir fıkranın başına <code>[koşul: …]</code> yazarsanız fıkra yalnızca o durumda görünür; koşulsuz fıkra her zaman görünür. Tarama sonucu elle değiştirilemez, değişen yalnızca fıkraların metnidir. Bir maddenin görünen hiçbir fıkrası kalmazsa madde sayfada hiç görünmez.</p><div class="ysl__chips">' . $chips . '</div></div></div>';
} else {
    $cond = '<div class="ysl__cond">' . ui_icon('info') . '<div><p><b>“İş başvurusu yapan adaylar” maddesi silinemez.</b> Kariyer sayfasındaki başvuru formu bu maddeye bağlanır (bağlantı kimliği <code>basvuru-adaylari</code> sabittir). Başlığını, metnini ve sırasını değiştirebilirsiniz.</p></div></div>';
}

ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong><ul class="errs"><li>' . implode('</li><li>', array_map('e', $errors)) . '</li></ul>');
?>
<div class="ay">
  <nav class="tabs ay__tabs" aria-label="Yasal metinler"><?= $tabs ?></nav>
  <?= ui_card('Nasıl düzenlenir?', $help . $cond, ['desc' => 'Metinler hukuki sorumluluk taşır: değişiklikten sonra hukuk danışmanınızın görmesi için Güvenlik ve yedek bölümündeki “kontrol edildi” işaretini yenileyin.']) ?>
  <form id="yasal-form" method="post" action="<?= adm_url('yasal/' . $doc) ?>" novalidate data-ysl>
    <?= adm_csrf_field() ?>
    <?= ui_card($docs[$doc][0] . ': maddeler', ui_repeater('articles', '', $arts, [
        ['key' => 'title', 'label' => 'Madde başlığı', 'maxlength' => $LM['title'][1], 'class' => 'span-2'],
        ['key' => 'paras', 'label' => 'Fıkralar (aralarına boş satır bırakın)', 'type' => 'textarea', 'rows' => 9, 'maxlength' => 30000, 'class' => 'span-2 ysl__big'],
        ['key' => 'items', 'label' => 'Harfli liste maddeleri (her satıra bir madde; yoksa boş bırakın)', 'type' => 'textarea', 'rows' => 3, 'maxlength' => 10000, 'class' => 'span-2'],
        ['key' => 'plain', 'label' => 'Sade Türkçesi (isteğe bağlı)', 'type' => 'textarea', 'rows' => 3, 'maxlength' => 8000, 'class' => 'span-2'],
        ['key' => 'anchor', 'type' => 'hidden', 'class' => 'rp__field--hidden'],
    ], ['add' => 'Madde ekle', 'min' => 1]), [
        'desc'    => $LM['articles'][0] . ' ile ' . $LM['articles'][1] . ' madde. Sayfa sitede “' . $docs[$doc][0] . '” adıyla yayınlanır; sayfanın başlığı, “Kısaca” kutusu ve sonundaki bağlantı Sayfa metinleri bölümündedir.',
        'actions' => ui_view_link(url($docs[$doc][2])) . ui_history_link('legal'),
    ]) ?>
  </form>
  <div class="ysl__vars"><span class="txf__barl">Metne eklenebilir yer tutucular</span><?= $vars ?></div>
</div>
<script>
(function () {
  var f = document.getElementById("yasal-form"); if (!f) return;
  var last = null;
  f.addEventListener("focusin", function (e) { if (e.target.matches("textarea")) last = e.target; });
  document.querySelectorAll(".ysl__vars [data-ins], .ysl__chips [data-ins]").forEach(function (b) {
    b.addEventListener("click", function () {
      if (!last) { last = f.querySelector("textarea"); }
      var t = b.getAttribute("data-ins"), a = last.selectionStart, z = last.selectionEnd;
      last.value = last.value.slice(0, a) + t + last.value.slice(z);
      last.focus(); last.setSelectionRange(a + t.length, a + t.length);
      last.dispatchEvent(new Event("input", { bubbles: true }));
    });
  });
})();
</script>
<?php
adm_layout('Yasal metinler', (string) ob_get_clean(), [
    'section'  => 'yasal',
    'subtitle' => 'KVKK Aydınlatma Metni ve Çerez Politikası maddeleri: ekleyin, çıkarın, sıralayın, düzenleyin.',
    'form'     => 'yasal-form',
]);
