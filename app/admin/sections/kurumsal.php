<?php
/**
 * Kurumsal içerik: süreç, ilkeler, misyon/vizyon, ana sayfa hareketleri, banka hesapları, form seçenekleri.
 * Veri: site('anahtar') listeleri (app/data/site.php); panelden kaydedilenler content 'lists' içinde.
 * Kayıt, mevcut 'lists' içeriğine BİRLEŞTİRİLİR (başka alanlar, örn. ref_count, korunur).
 */

$tabs = [
    'surec'    => 'Süreç ve ilkeler',
    'misyon'   => 'Misyon ve vizyon',
    'anasayfa' => 'Ana sayfa hareketleri',
    'banka'    => 'Banka hesapları',
    'form'     => 'Form seçenekleri',
];
$tab = $rest[0] ?? 'surec';
if (!isset($tabs[$tab])) {
    adm_go('kurumsal');
}

/** IBAN: boşluksuz büyük harfe çevirir, TR + 24 rakam ve mod-97 sağlamasını denetler. Geçerliyse 4'lü gruplu metni döndürür. */
function kr_iban(string $raw): ?string
{
    $iban = strtoupper(preg_replace('/\s+/', '', $raw));
    if (!preg_match('/^TR\d{24}$/', $iban)) return null;
    $moved = substr($iban, 4) . substr($iban, 0, 4);
    $num = '';
    foreach (str_split($moved) as $c) $num .= ctype_alpha($c) ? (string) (ord($c) - 55) : $c;
    $rem = 0;
    foreach (str_split($num, 7) as $chunk) $rem = (int) ($rem . $chunk) % 97;
    return $rem === 1 ? trim(chunk_split($iban, 4, ' ')) : null;
}

$errors = [];
$data   = [];   // sekmedeki alanların gösterilecek değerleri (hata durumunda gönderilenler)

/** [[h, t], ...] listesini onarıcıya (h/t) çevirir */
$pairs = fn($list) => array_map(fn($x) => ['h' => (string) ($x[0] ?? ''), 't' => (string) ($x[1] ?? '')], array_values((array) $list));

if ($tab === 'surec') {
    $data = ['process' => $pairs(site('process')), 'principles' => $pairs(site('principles'))];
} elseif ($tab === 'misyon') {
    $data = ['mission_goals' => array_values((array) site('mission_goals')), 'vision_items' => $pairs(site('vision_items'))];
} elseif ($tab === 'anasayfa') {
    $noise = [];
    foreach ((array) site('noise') as $inst => $items) $noise[] = ['inst' => (string) $inst, 'items' => implode("\n", (array) $items)];
    $data = ['stations' => array_values((array) site('stations')), 'noise' => $noise];
} elseif ($tab === 'banka') {
    $data = ['banks' => array_values((array) site('banks'))];
} else {
    $data = ['sektorler' => array_values((array) site('sektorler')), 'deneyim' => array_values((array) site('deneyim'))];
}

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $save = [];
    /** Başlık + metin çiftleri: ikisi de dolu olmalı */
    $readPairs = function (string $key, string $what, ?int $exact, int $min) use (&$errors, &$data, &$save): void {
        $rows = post_rows($key, ['h', 't'], 1500);
        $data[$key] = $rows;
        $bad = array_filter($rows, fn($r) => $r['h'] === '' || $r['t'] === '');
        if ($bad) { $errors[] = $what . ': her satırın hem başlığı hem metni dolu olmalı.'; return; }
        if ($exact !== null && count($rows) !== $exact) { $errors[] = $what . ': tam ' . $exact . ' madde olmalı (şu an ' . count($rows) . '). Sitedeki tasarım bu sayıya göre kurulmuştur.'; return; }
        if (count($rows) < $min) { $errors[] = $what . ': en az ' . $min . ' madde olmalı.'; return; }
        $save[$key] = array_map(fn($r) => [$r['h'], $r['t']], $rows);
    };
    $readList = function (string $key, string $what, ?int $exact, int $min, int $max = 40) use (&$errors, &$data, &$save): void {
        $list = post_list($key, 400);
        $data[$key] = $list;
        if ($exact !== null && count($list) !== $exact) { $errors[] = $what . ': tam ' . $exact . ' madde olmalı (şu an ' . count($list) . '). Sitedeki başlık ve tasarım bu sayıya göre kurulmuştur.'; return; }
        if (count($list) < $min) { $errors[] = $what . ': en az ' . $min . ' madde olmalı.'; return; }
        if (count($list) > $max) { $errors[] = $what . ': en fazla ' . $max . ' madde olabilir.'; return; }
        $save[$key] = $list;
    };

    if ($tab === 'surec') {
        $readPairs('process', 'Çalışma süreci', 4, 4);
        $readPairs('principles', 'İlkeler', null, 1);
    } elseif ($tab === 'misyon') {
        $readList('mission_goals', 'Misyon hedefleri', null, 3, 8);
        $readPairs('vision_items', 'Vizyon maddeleri', null, 1);
    } elseif ($tab === 'anasayfa') {
        $readList('stations', 'Dalga programları', null, 3, 30);
        $rows = post_rows('noise', ['inst', 'items'], 4000);
        $data['noise'] = $rows;
        $noise = [];
        $noiseErr = false;
        foreach ($rows as $r) {
            $items = array_values(array_filter(array_map(fn($l) => mb_substr(trim($l), 0, 120), explode("\n", $r['items'])), fn($l) => $l !== ''));
            if ($r['inst'] === '' || !$items) { $noiseErr = true; break; }
            if (isset($noise[$r['inst']])) { $errors[] = '"' . $r['inst'] . '" kurumu iki kez yazılmış. Her kurum bir kez yer almalı.'; $noiseErr = true; break; }
            $noise[$r['inst']] = $items;
        }
        if ($noiseErr && !$errors) $errors[] = 'Kurum grupları: her grupta kurum adı ve en az bir program (her satıra bir tane) olmalı.';
        elseif (!$noiseErr && !$noise) $errors[] = 'Kurum grupları: en az bir kurum ekleyin.';
        elseif (!$noiseErr) $save['noise'] = $noise;
    } elseif ($tab === 'banka') {
        $rows = post_rows('banks', ['bank', 'holder', 'account', 'iban'], 120);
        $data['banks'] = $rows;
        $banks = [];
        foreach ($rows as $i => $r) {
            $n = $i + 1;
            if ($r['bank'] === '' || $r['holder'] === '' || $r['account'] === '' || $r['iban'] === '') { $errors[] = $n . '. hesap: banka, hesap sahibi, hesap numarası ve IBAN alanlarının hepsi dolu olmalı.'; continue; }
            $fmt = kr_iban($r['iban']);
            if ($fmt === null) { $errors[] = $n . '. hesabın IBAN\'ı geçerli değil. TR ile başlayan 26 karakter olmalı ve rakamlarında yazım hatası bulunmamalı.'; continue; }
            $data['banks'][$i]['iban'] = $fmt;
            $banks[] = ['bank' => $r['bank'], 'holder' => $r['holder'], 'account' => $r['account'], 'iban' => $fmt];
        }
        if (!$rows) $errors[] = 'En az bir banka hesabı ekleyin.';
        if (!$errors) $save['banks'] = $banks;
    } else {
        // Sektör adları form kayıtlarında ve bülten süzgecinde olduğu gibi kullanılır: en fazla 80 karakter, yinelenmez
        $sek = post_list('sektorler', 400);
        if (array_filter($sek, fn($x) => mb_strlen($x) > 80)) $errors[] = 'Sektör seçenekleri: bir sektör adı en fazla 80 karakter olabilir.';
        elseif (count($sek) !== count(array_unique($sek))) $errors[] = 'Sektör seçenekleri: aynı sektör iki kez yazılmış.';
        $readList('sektorler', 'Sektör seçenekleri', null, 3, 60);
        $readList('deneyim', 'Deneyim seçenekleri', null, 2, 20);
    }

    if (!$errors && $save) {
        $lists = content_get('lists', []);
        if (!is_array($lists)) $lists = [];
        if (content_put('lists', array_merge($lists, $save))) {
            adm_flash('Değişiklikler kaydedildi.');
            adm_go('kurumsal/' . $tab);
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

/* ---------- Görünüm ---------- */
ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong> ' . implode(' ', array_map('e', $errors)));
?>
<div class="ay">
  <nav class="tabs ay__tabs" aria-label="Kurumsal içerik bölümleri">
    <?php foreach ($tabs as $k => $label): ?><a class="tab<?= $k === $tab ? ' is-on' : '' ?>" href="<?= adm_url('kurumsal/' . $k) ?>"<?= $k === $tab ? ' aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
  </nav>

  <form id="list-form" method="post" action="<?= adm_url('kurumsal/' . $tab) ?>" class="ay__list-form" novalidate>
    <?= adm_csrf_field() ?>
    <?php
    $pairFields = fn(string $hl, string $tl) => [
        ['key' => 'h', 'label' => $hl, 'maxlength' => 120, 'class' => 'span-2'],
        ['key' => 't', 'label' => $tl, 'type' => 'textarea', 'rows' => 3, 'maxlength' => 1500, 'class' => 'span-2'],
    ];

    if ($tab === 'surec') {
        echo ui_card('Çalışma süreci', ui_repeater('process', '', $data['process'], $pairFields('Fiil', 'Açıklama'), ['min' => 1, 'add' => 'Adım ekle']), [
            'desc'    => 'Ana sayfada "Nasıl çalışırız?" bölümünde kartlar olarak, yatay kayan bir şerit halinde görünür. Başlık tek bir fiil olmalıdır (örneğin "Dinleriz"). Bölüm tam 4 adımlık tasarlandığı için sayı sabittir; adımların sırasını ve metinlerini değiştirebilirsiniz.',
            'actions' => ui_view_link(url('#proc-t')),
        ]);
        echo ui_card('İlkeler', ui_repeater('principles', '', $data['principles'], $pairFields('Başlık', 'Açıklama'), ['min' => 1, 'add' => 'İlke ekle']), [
            'desc'    => '"Mihenk taşlarımız" sayfasında, üzerinde parmakla silinen taş kartlar olarak görünür. 6 ilke en dengeli görünümü verir.',
            'actions' => ui_view_link(url('kurumsal/mihenk-taslarimiz')),
        ]);
    } elseif ($tab === 'misyon') {
        echo ui_card('Misyon hedefleri', ui_repeater('mission_goals', '', $data['mission_goals'], [['key' => '', 'type' => 'textarea', 'rows' => 2, 'maxlength' => 400]], ['min' => 1, 'add' => 'Hedef ekle']), [
            'desc'    => 'Misyonumuz sayfasındaki hedef listesi (3 ile 8 madde). Bölüm başlığında madde sayısı geçiyor ("beş şeye"); sayıyı değiştirirseniz başlığı da <a href="' . adm_url('metinler/mission') . '">Sayfa metinleri</a> bölümünden güncelleyin.',
            'actions' => ui_view_link(url('kurumsal/misyonumuz')),
        ]);
        echo ui_card('Vizyon maddeleri', ui_repeater('vision_items', '', $data['vision_items'], $pairFields('Başlık', 'Açıklama'), ['min' => 1, 'add' => 'Madde ekle']), [
            'desc'    => 'Vizyonumuz sayfasında, ufuk çizgisi üzerinde yan yana kayan başlıklar. 4 madde önerilir.',
            'actions' => ui_view_link(url('kurumsal/vizyonumuz')),
        ]);
    } elseif ($tab === 'anasayfa') {
        echo ui_card('Dalga programları', ui_repeater('stations', '', $data['stations'], [['key' => '', 'placeholder' => 'Örn: TÜBİTAK 1501', 'maxlength' => 80]], ['min' => 1, 'add' => 'Program ekle']), [
            'desc'    => 'Ana sayfanın en üstündeki ses dalgasında, imleç hangi frekansa gelirse o programın adı belirir. 10 ile 20 arası kısa ad en iyi sonucu verir; en az 3 gerekir.',
            'actions' => ui_view_link(url()),
        ]);
        echo ui_card('Kurumlar ve programları', ui_repeater('noise', '', $data['noise'], [
            ['key' => 'inst', 'label' => 'Kurum', 'maxlength' => 80, 'placeholder' => 'Örn: TÜBİTAK', 'class' => 'span-2'],
            ['key' => 'items', 'label' => 'Programlar (her satıra bir tane)', 'type' => 'textarea', 'rows' => 4, 'class' => 'span-2'],
        ], ['min' => 1, 'add' => 'Kurum ekle']), [
            'desc'    => 'Ana sayfada "Onlarca kurum, onlarca program" bölümünde dağınık başlayıp düzene giren program adları, kurumlara göre gruplanmış halde.',
            'actions' => ui_view_link(url()),
        ]);
    } elseif ($tab === 'banka') {
        echo ui_card('Banka hesapları', ui_repeater('banks', '', $data['banks'], [
            ['key' => 'bank', 'label' => 'Banka', 'maxlength' => 80, 'placeholder' => 'Örn: Halk Bankası'],
            ['key' => 'holder', 'label' => 'Hesap sahibi', 'maxlength' => 80],
            ['key' => 'account', 'label' => 'Hesap numarası', 'maxlength' => 40],
            ['key' => 'iban', 'label' => 'IBAN', 'maxlength' => 40, 'placeholder' => 'TR00 0000 0000 0000 0000 0000 00'],
        ], ['min' => 1, 'add' => 'Hesap ekle']), [
            'desc'    => 'Hesap Numaralarımız sayfasında kart olarak görünür; ziyaretçi IBAN\'a dokunarak kopyalar. IBAN yazılırken boşluklar serbesttir, kaydedince 4\'lü gruplara ayrılır ve rakamları denetlenir.',
            'actions' => ui_view_link(url('hesap-numaralarimiz')),
        ]);
    } else {
        echo ui_card('Sektör seçenekleri', ui_repeater('sektorler', '', $data['sektorler'], [['key' => '', 'placeholder' => 'Örn: İmalat: makine ve metal', 'maxlength' => 80]], ['min' => 1, 'add' => 'Sektör ekle']), [
            'desc'    => 'Bülten kayıt formundaki "Sektör" açılır listesinin seçenekleri, buradaki sırayla (3 ile 60 arası). Aboneleri <a href="' . adm_url('bulten') . '">Bülten</a> bölümünde bu sektörlere göre süzersiniz; adı "İmalat" ile başlayan seçenekler orada "İmalat (tümü)" kısayoluyla birlikte seçilir. Bir seçeneğin adını değiştirirseniz eski adla kaydolmuş aboneler yeni adın süzgecine girmez; arama kutusuyla bulunur.',
            'actions' => ui_view_link(url('haberdarol')),
        ]);
        echo ui_card('Deneyim seçenekleri', ui_repeater('deneyim', '', $data['deneyim'], [['key' => '', 'placeholder' => 'Örn: 3-5 yıl', 'maxlength' => 60]], ['min' => 1, 'add' => 'Seçenek ekle']), [
            'desc'    => 'Kariyer sayfasındaki başvuru formunda "Deneyim" açılır listesinin seçenekleri, buradaki sırayla.',
            'actions' => ui_view_link(url('kariyer')),
        ]);
    }
    ?>
  </form>
</div>
<?php
adm_layout('Kurumsal içerik', (string) ob_get_clean(), [
    'section'  => 'kurumsal',
    'subtitle' => 'Sitenin kurumsal sayfalarındaki ve ana sayfadaki listeler.',
    'actions'  => ui_history_link('lists'),
    'form'     => 'list-form',
]);
