<?php
/**
 * Kurumsal içerik: süreç, ilkeler, mevzuat zaman çizelgesi, misyon/vizyon, banka hesapları, form seçenekleri.
 * Veri: site('anahtar') listeleri (app/data/site.php); panelden kaydedilenler content 'lists' içinde.
 * Doğrulama ve kayıt app/content.php içindedir (lists_save, list_clean, list_errors); sekme başına yalnızca o sekmenin listeleri kaydedilir.
 */

$tabs = [
    'surec'    => 'Süreç ve ilkeler',
    'tarihce'  => 'Zaman çizelgesi',
    'misyon'   => 'Misyon ve vizyon',
    'banka'    => 'Banka hesapları',
    'form'     => 'Form seçenekleri',
    'kanun'    => 'Ana sayfa kanun metni',
];
$tab = $rest[0] ?? 'surec';
if (!isset($tabs[$tab])) {
    adm_go('kurumsal');
}
$LM = lists_limits();

$errors = [];
$data   = [];   // sekmedeki alanların gösterilecek değerleri (hata durumunda gönderilenler)

/** [[başlık, metin], ...] listesini onarıcıya (h/t) çevirir */
$pairs = fn($list) => array_map(fn($x) => ['h' => (string) ($x[0] ?? ''), 't' => (string) ($x[1] ?? '')], array_values((array) $list));

/** Sekmenin listeleri: anahtar => ham değer okuyucu (POST'tan) */
$read = [
    'surec'   => ['process' => fn() => post_rows('process', ['phase', 'title', 'us', 'you'], 1500), 'principles' => fn() => post_rows('principles', ['h', 't'], 1500)],
    'tarihce' => ['timeline' => fn() => post_rows('timeline', ['year', 'kind', 'title', 'text', 'src'], 1500)],
    'misyon'  => [
        'mission' => fn() => ['statement' => post_str('mission_statement', 1500), 'items' => post_list('mission_items', 1500)],
        'vision'  => fn() => ['open_year' => post_str('vision_open_year', 10), 'greeting' => post_str('vision_greeting', 1500), 'intro' => post_str('vision_intro', 3000), 'items' => post_list('vision_items', 1500), 'closing' => post_str('vision_closing', 1500)],
    ],
    'banka'   => ['banks' => fn() => post_rows('banks', ['bank', 'holder', 'account', 'iban'], 200)],
    'form'    => ['sektorler' => fn() => post_list('sektorler', 400), 'deneyim' => fn() => post_list('deneyim', 400)],
];

/* ---------- Kaydet ---------- */
$posted = null;
if ($method === 'POST' && isset($read[$tab])) {
    $posted = [];
    foreach ($read[$tab] as $k => $fn) {
        $posted[$k] = $fn();
    }
    $r = lists_save($posted);
    if ($r['ok']) {
        adm_flash($r['changed'] ? 'Değişiklikler kaydedildi.' : 'Değişiklik yok; kayıt oluşturulmadı.');
        adm_go('kurumsal/' . $tab);
    }
    $errors = $r['errors'];
}

/** Gösterilecek değer: hata varsa gönderilen (temizlenmiş), yoksa sitedeki güncel liste */
$val = fn(string $k) => $posted !== null && isset($posted[$k]) ? list_clean($k, $posted[$k]) : site($k);

/* ---------- Görünüm ---------- */
ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong><ul class="errs"><li>' . implode('</li><li>', array_map('e', $errors)) . '</li></ul>');
?>
<div class="ay">
  <nav class="tabs ay__tabs" aria-label="Kurumsal içerik bölümleri">
    <?php foreach ($tabs as $k => $label): ?><a class="tab<?= $k === $tab ? ' is-on' : '' ?>" href="<?= adm_url('kurumsal/' . $k) ?>"<?= $k === $tab ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
  </nav>

<?php if ($tab === 'kanun'):
    $law = (array) site('hero_law');
    $blocks = '';
    foreach ((array) ($law['blocks'] ?? []) as $b) {
        $blocks .= '<div class="law-block"><p class="law-block__ref">' . e((string) ($b['ref'] ?? '')) . '</p><blockquote>' . e((string) ($b['law'] ?? '')) . '</blockquote><p class="law-block__plain"><b>Sade Türkçesi:</b> ' . e(strip_tags((string) ($b['plain'] ?? ''))) . '</p></div>';
    }
    echo ui_card('Ana sayfadaki kanun metni', '<div class="svc-quote">' . ui_icon('lock-key') . '<p><b>Bu metin panelden düzenlenemez.</b> Ana sayfadaki mercek altında görünen metin, ' . e((string) ($law['title'] ?? '')) . ' kanunundan <b>resmî mevzuattan birebir alınmış</b> alıntılardır (<code>app/data/site.php</code> içindeki <code>hero_law</code>). Yanlış bir değişiklik yasal metni çarpıtabileceği için yalnızca mevzuat resmen değiştiğinde, kanunun güncel metniyle karşılaştırılarak sitenin kaynak dosyasında güncellenir; geliştiricinize iletin. Hizmet sayfalarındaki mevzuat alıntıları ise Hizmetler bölümünden, uyarıyla birlikte düzenlenebilir.</p></div>'
        . '<p class="muted">' . e(implode(' · ', array_map('strval', (array) ($law['meta'] ?? [])))) . '</p>' . $blocks, [
        'actions' => ui_view_link(url()),
    ]);
else: ?>
  <form id="list-form" method="post" action="<?= adm_url('kurumsal/' . $tab) ?>" class="ay__list-form" novalidate>
    <?= adm_csrf_field() ?>
    <?php
    $pairFields = fn(string $hl, string $tl, int $hm, int $tm) => [
        ['key' => 'h', 'label' => $hl, 'maxlength' => $hm, 'class' => 'span-2'],
        ['key' => 't', 'label' => $tl, 'type' => 'textarea', 'rows' => 3, 'maxlength' => $tm, 'class' => 'span-2'],
    ];

    if ($tab === 'surec') {
        $P = $LM['process'];
        echo ui_card('Çalışma süreci', ui_repeater('process', '', $val('process'), [
            ['key' => 'phase', 'label' => 'Aşama', 'maxlength' => $P['phase'][1], 'placeholder' => 'Örn: Hazırlık'],
            ['key' => 'title', 'label' => 'Başlık', 'maxlength' => $P['title'][1], 'placeholder' => 'Örn: Tanışma'],
            ['key' => 'us', 'label' => 'Biz ne yaparız?', 'type' => 'textarea', 'rows' => 2, 'maxlength' => $P['us'][1], 'class' => 'span-2'],
            ['key' => 'you', 'label' => 'Siz ne yaparsınız? (kısa)', 'maxlength' => $P['you'][1], 'class' => 'span-2'],
        ], ['add' => 'Adım ekle']), [
            'desc'    => 'Ana sayfadaki "Sekiz yaprak" takviminde her adım bir yaprak olur; sağdaki kırmızı yaprak sonuncudur. Takvim tam 8 yaprağa göre tasarlandığı için adım sayısı 8 olmalıdır (başlıktaki "sekiz" sayıdan kendiliğinden yazılır); sırayı ve metinleri değiştirebilirsiniz. Aşama adı yaprağın üstünde büyük harfle basılır; ayrı aşama adları kullanmak zorunda değilsiniz.',
            'actions' => ui_view_link(url('#takvim-title')),
        ]);
        echo ui_card('İlkeler (Mihenk taşlarımız)', ui_repeater('principles', '', $pairs($val('principles')), $pairFields('Başlık', 'Açıklama', $LM['principles']['title'][1], $LM['principles']['text'][1]), ['add' => 'İlke ekle']), [
            'desc'    => 'Mihenk taşlarımız sayfasında taş üzerinde silinen altı ilke. Taşlar tam 6 ilkeye göre tasarlandığı için sayı 6 olmalıdır (sayfadaki "altı" sayıdan kendiliğinden yazılır).',
            'actions' => ui_view_link(url('kurumsal/mihenk-taslarimiz')),
        ]);
    } elseif ($tab === 'tarihce') {
        $T = $LM['timeline'];
        echo ui_card('Mevzuat zaman çizelgesi', ui_repeater('timeline', '', $val('timeline'), [
            ['key' => 'year', 'label' => 'Yıl', 'maxlength' => 4, 'placeholder' => '2008', 'type' => 'text'],
            ['key' => 'kind', 'label' => 'Tür', 'type' => 'select', 'options' => ['law' => 'Mevzuat', 'us' => 'Biz (kuruluş)']],
            ['key' => 'title', 'label' => 'Başlık (klasör sırtında)', 'maxlength' => $T['title'][1], 'class' => 'span-2'],
            ['key' => 'src', 'label' => 'Kaynak (örn. R.G. 12/3/2008)', 'maxlength' => $T['src'][1], 'class' => 'span-2'],
            ['key' => 'text', 'label' => 'Metin', 'type' => 'textarea', 'rows' => 3, 'maxlength' => $T['text'][1], 'class' => 'span-2'],
        ], ['add' => 'Kayıt ekle']), [
            'desc'    => 'Hakkımızda sayfasındaki arşiv rafı: her kayıt raftan çekilen bir klasördür. ' . $T['count'][0] . ' ile ' . $T['count'][1] . ' kayıt; her yıl en çok bir kez yer alır ve kayıtlar kaydederken yıla göre sıralanır. "Biz" türündeki ilk kayıt sayfa açılınca açık klasördür. Raf notundaki "ilk üçü bizden önce gelen düzenlemeler" cümlesi, kuruluş yılından önceki kayıtların sayısına göre kendiliğinden uyar. Mevzuat kaynaklarını resmî metinden kontrol edin.',
            'actions' => ui_view_link(url('hakkimizda#raf-title')),
        ]);
    } elseif ($tab === 'misyon') {
        $M = $LM['mission'];
        $V = $LM['vision'];
        $mi = (array) $val('mission');
        $vi = (array) $val('vision');
        $founded = (int) cfg('founded');
        $stmtBar = '<div class="txf__bar" role="group" aria-label="Biçim"><span class="txf__barl">Biçim</span><button type="button" class="txf__fmt txf__fmt--red" data-stmt-fmt title="Seçili ifadeyi sayfada kırmızı kalemle çizdirir">Kırmızı çizgi</button></div>';
        echo ui_card('Misyon', $stmtBar . ui_textarea('mission_statement', 'Misyon cümlesi', (string) ($mi['statement'] ?? ''), ['required' => true, 'rows' => 2, 'maxlength' => $M['statement'][1] + 40, 'help' => 'Defterin üstündeki ana cümle (' . $M['statement'][0] . ' ile ' . $M['statement'][1] . ' karakter). Sayfada kırmızı kalemle altı çizilecek ifadeyi seçip “Kırmızı çizgi” düğmesine basın; cümle değişse bile işaret ifadeyle birlikte kalır. Yalnızca bir ifade çizilebilir; işaret [kırmızı-çizgi]…[/kırmızı-çizgi] biçiminde görünür.'])
            . ui_repeater('mission_items', 'Yapılacaklar (iş listesi)', (array) ($mi['items'] ?? []), [['key' => '', 'type' => 'textarea', 'rows' => 2, 'maxlength' => $M['item'][1]]], ['add' => 'Madde ekle']), [
            'desc'    => 'Misyonumuz sayfasında kalemle işaretlenen iş listesi. Sayfa ve Hakkımızda kartı madde sayısını yazıyla söyler ("beş iş"); sayı ' . $M['count'][0] . ($M['count'][0] === $M['count'][1] ? ' ile sabittir (tasarım bu kadar maddeye göre kurulu).' : ' ile ' . $M['count'][1] . ' arasındadır.'),
            'actions' => ui_view_link(url('kurumsal/misyonumuz')),
        ]);
        echo ui_card('Vizyon mektubu', ui_text('vision_open_year', 'Açılış yılı', (string) ($vi['open_year'] ?? ''), ['required' => true, 'maxlength' => 4, 'inputmode' => 'numeric', 'help' => 'Mektubun açılacağı yıl (' . ((int) date('Y') + 1) . ' ile 2100 arası). Sitedeki "kuruluşumuzun otuzuncu yılı" ifadesi bu yıldan kendiliğinden hesaplanır (kuruluş ' . $founded . '; ' . ($founded + 30) . ' yazarsanız "otuzuncu", başka bir yıl yazarsanız o sayının sırası yazılır).'])
            . ui_text('vision_greeting', 'Selamlama', (string) ($vi['greeting'] ?? ''), ['required' => true, 'maxlength' => $V['greeting'][1], 'help' => 'Örn: "Sevgili 2037,"'])
            . ui_textarea('vision_intro', 'Giriş paragrafı', (string) ($vi['intro'] ?? ''), ['required' => true, 'rows' => 3, 'maxlength' => $V['intro'][1], 'counter' => true])
            . ui_repeater('vision_items', 'Maddeler', (array) ($vi['items'] ?? []), [['key' => '', 'type' => 'textarea', 'rows' => 2, 'maxlength' => $V['item'][1]]], ['add' => 'Madde ekle'])
            . ui_text('vision_closing', 'Kapanış cümlesi', (string) ($vi['closing'] ?? ''), ['required' => true, 'maxlength' => $V['closing'][1]]), [
            'desc'    => 'Vizyonumuz sayfasında zarftan çıkan mektup. ' . $V['count'][0] . ' ile ' . $V['count'][1] . ' madde.',
            'actions' => ui_view_link(url('kurumsal/vizyonumuz')),
        ]);
    } elseif ($tab === 'banka') {
        $B = $LM['banks'];
        echo ui_card('Banka hesapları', ui_repeater('banks', '', $val('banks'), [
            ['key' => 'bank', 'label' => 'Banka', 'maxlength' => $B['bank'][1], 'placeholder' => 'Örn: Halk Bankası'],
            ['key' => 'holder', 'label' => 'Hesap sahibi', 'maxlength' => $B['holder'][1]],
            ['key' => 'account', 'label' => 'Hesap numarası', 'maxlength' => $B['account'][1]],
            ['key' => 'iban', 'label' => 'IBAN', 'maxlength' => 40, 'placeholder' => 'TR00 0000 0000 0000 0000 0000 00'],
        ], ['add' => 'Hesap ekle']), [
            'desc'    => 'Hesap Numaralarımız sayfasında her hesap için bir fiş basılır; ziyaretçi IBAN\'a dokunarak kopyalar. ' . $B['count'][0] . ' ile ' . $B['count'][1] . ' hesap. IBAN yazılırken boşluklar serbesttir, kaydedince 4\'lü gruplara ayrılır; TR kontrol rakamları denetlenir ve yanlış bir IBAN kaydedilmez.',
            'actions' => ui_view_link(url('hesap-numaralarimiz')),
        ]);
    } else {
        $S = $LM['sektorler'];
        $D = $LM['deneyim'];
        echo ui_card('Sektör seçenekleri', ui_repeater('sektorler', '', (array) $val('sektorler'), [['key' => '', 'placeholder' => 'Örn: İmalat: makine ve metal', 'maxlength' => $S['item'][1]]], ['add' => 'Sektör ekle']), [
            'desc'    => 'Bülten kayıt formundaki "Sektör" açılır listesinin seçenekleri, buradaki sırayla (' . $S['count'][0] . ' ile ' . $S['count'][1] . ' arası, yinelenmez). Aboneleri Bülten bölümünde bu sektörlere göre süzersiniz; adı "İmalat" ile başlayan seçenekler orada "İmalat (tümü)" kısayoluyla birlikte seçilir. Bir seçeneğin adını değiştirirseniz eski adla kaydolmuş aboneler yeni adın süzgecine girmez; arama kutusuyla bulunur.',
            'actions' => ui_view_link(url('haberdarol')),
        ]);
        echo ui_card('Deneyim seçenekleri', ui_repeater('deneyim', '', (array) $val('deneyim'), [['key' => '', 'placeholder' => 'Örn: 3-5 yıl', 'maxlength' => $D['item'][1]]], ['add' => 'Seçenek ekle']), [
            'desc'    => 'Kariyer sayfasındaki başvuru formunda ve iş ilanlarında "Deneyim" açılır listesinin seçenekleri, buradaki sırayla (' . $D['count'][0] . ' ile ' . $D['count'][1] . ' arası, yinelenmez). Bir ilanda seçili olan deneyim adını değiştirirseniz o ilanı yeniden kaydederken listeden seçmeniz gerekir.',
            'actions' => ui_view_link(url('kariyer')),
        ]);
    }
    ?>
  </form>
<?php endif; ?>
</div>
<script>
(function () {
  var b = document.querySelector("[data-stmt-fmt]"), t = document.querySelector('[name="mission_statement"]');
  if (!b || !t) return;
  b.addEventListener("click", function () {
    var a = t.selectionStart, z = t.selectionEnd, v = t.value;
    if (a === z) { t.focus(); return; }
    var open = "[kırmızı-çizgi]", close = "[/kırmızı-çizgi]";
    v = v.split(open).join("").split(close).join("");   // yalnızca bir ifade çizilir: öncekini kaldır
    var cut = t.value.slice(0, a).split(open).join("").split(close).join("").length;
    t.value = v.slice(0, cut) + open + v.slice(cut, cut + (z - a)) + close + v.slice(cut + (z - a));
    t.dispatchEvent(new Event("input", { bubbles: true }));
  });
})();
</script>
<?php
adm_layout('Kurumsal içerik', (string) ob_get_clean(), [
    'section'  => 'kurumsal',
    'subtitle' => 'Sitenin kurumsal sayfalarındaki ve ana sayfadaki ortak listeler.',
    'actions'  => ui_history_link('lists'),
    'form'     => $tab === 'kanun' ? null : 'list-form',
]);

