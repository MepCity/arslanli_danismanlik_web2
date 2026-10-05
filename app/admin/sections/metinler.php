<?php
/**
 * Sayfa metinleri: sitedeki başlık, paragraf ve düğme yazılarının düzenlenmesi.
 * Kayıt: content 'texts' (yalnızca değiştirilen metinler: anahtar => metin).
 * Kayıt listesi: app/data/texts/*.php (texts_registry / texts_flat).
 */

$registry = texts_registry();
$flat     = texts_flat();

/** Aramada Türkçe karakter ve büyük/küçük harf farkını yok sayar. */
function txt_fold(string $s): string
{
    $s = html_entity_decode(strip_tags($s), ENT_QUOTES, 'UTF-8');
    $s = strtr($s, ['İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ç' => 'c', 'ç' => 'c', 'Ğ' => 'g', 'ğ' => 'g', 'Ö' => 'o', 'ö' => 'o', 'Ş' => 's', 'ş' => 's', 'Ü' => 'u', 'ü' => 'u']);
    $s = mb_strtolower($s, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $s));
}

/** Panelden değiştirilmiş (özgün metinden farklı) metin sayısı, grup bazında. */
function txt_changed_map(array $registry, array $over): array
{
    $out = [];
    foreach ($registry as $gid => $g) {
        $n = 0;
        foreach ($g['sections'] ?? [] as $items) {
            foreach ($items as $k => $it) {
                if (isset($over[$k]) && trim((string) $over[$k]) !== '' && (string) $over[$k] !== (string) $it[1]) $n++;
            }
        }
        $out[$gid] = $n;
    }
    return $out;
}

function txt_field(string $key, array $item, string $current, string $context = ''): string
{
    [$label, $default, $type] = [$item[0], (string) $item[1], $item[2] ?? 'line'];
    $help    = $item[3] ?? '';
    $name    = 't[' . $key . ']';
    $id      = ui_id($name);
    $changed = $current !== $default;
    $len     = mb_strlen($current);

    if ($type === 'html') {
        $ctl = ui_rich($name, '', $current);
    } elseif ($type === 'text') {
        $rows = max(2, min(9, (int) ceil($len / 68) + 1));
        $ctl  = ui_textarea($name, '', $current, ['rows' => $rows]);
    } else {
        $ctl = ui_text($name, '', $current);
    }

    $orig = $type === 'html' ? '<div class="prose-admin">' . $default . '</div>' : '<p>' . nl2br(e($default)) . '</p>';

    return '<div class="txf' . ($changed ? ' is-changed' : '') . '" data-txf data-type="' . e($type) . '" data-default="' . e($default) . '">'
        . '<input type="hidden" name="keys[]" value="' . e($key) . '">'
        . '<div class="txf__head">'
        .   '<label class="txf__label" for="' . e($id) . '">' . e($label) . '</label>'
        .   '<span class="txf__tools"><span class="badge badge--coral txf__badge">Değiştirildi</span>'
        .   '<button class="txf__reset" type="button" data-txf-reset>' . ui_icon('arrow-counter-clockwise') . 'Özgün metne dön</button></span>'
        . '</div>'
        . ($context !== '' ? '<p class="txf__ctx">' . e($context) . '</p>' : '')
        . $ctl
        . ($help !== '' ? '<p class="txf__help">' . e($help) . '</p>' : '')
        . '<details class="txf__orig"><summary>Özgün metin</summary><div class="txf__origtext">' . $orig . '</div></details>'
        . '</div>';
}

$over   = (array) content_get('texts', []);
$query  = trim((string) ($_GET['ara'] ?? ''));
$query  = mb_substr($query, 0, 120);
$gid    = (string) ($rest[0] ?? '');
$search = $query !== '';
$changedBy = txt_changed_map($registry, $over);

if (!$registry) {
    adm_layout('Sayfa metinleri', ui_card('', '<div class="empty">' . ui_icon('text-aa') . '<strong>Düzenlenebilir metin bulunamadı</strong><span>Metin listesi henüz hazırlanmamış.</span></div>'), ['section' => 'metinler']);
}

if (!$search && ($gid === '' || !isset($registry[$gid]))) {
    if ($gid !== '') adm_go('metinler');       // geçersiz sayfa adı
    adm_go('metinler/' . array_key_first($registry));
}

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $posted = $_POST['t'] ?? [];
    $keys   = $_POST['keys'] ?? [];
    $posted = is_array($posted) ? $posted : [];
    $keys   = is_array($keys) ? $keys : [];

    // Bu ekranın içerebileceği anahtarlar
    $allowed = [];
    if ($search) {
        $allowed = $flat;
    } else {
        foreach ($registry[$gid]['sections'] ?? [] as $items) foreach ($items as $k => $it) $allowed[$k] = $it;
    }

    $new = $over;
    foreach ($keys as $k) {
        if (!is_string($k) || !isset($allowed[$k])) continue;
        $item = $allowed[$k];
        $v    = $posted[$k] ?? '';
        $v    = is_string($v) ? str_replace(["\r\n", "\r"], "\n", trim($v)) : '';
        $v    = mb_substr($v, 0, 20000);
        if (($item[2] ?? 'line') === 'html') {
            $v = trim(sanitize_html($v));
            // Editör özgün metni yeniden biçimlendirse bile içerik aynıysa değişiklik sayılmaz.
            if (trim(strip_tags($v)) === '' || $v === trim(sanitize_html((string) $item[1]))) $v = '';
        } elseif (($item[2] ?? 'line') === 'line') {
            $v = trim((string) preg_replace('/\s*\n\s*/', ' ', $v));
        }
        if ($v === '' || $v === (string) $item[1]) {
            unset($new[$k]);
        } else {
            $new[$k] = $v;
        }
    }
    ksort($new);
    $oldSorted = $over;
    ksort($oldSorted);
    if ($new === $oldSorted) {
        adm_flash('Değişiklik yok.');
    } elseif (content_put('texts', $new)) {
        adm_flash('Metinler kaydedildi.');
    } else {
        adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
    }
    adm_go($search ? 'metinler?ara=' . rawurlencode($query) : 'metinler/' . $gid);
}

/* ---------- Sol liste ---------- */
$nav = '<nav class="txn" aria-label="Sayfalar" data-txn>';
foreach ($registry as $id => $g) {
    $n   = $changedBy[$id] ?? 0;
    $on  = !$search && $id === $gid;
    $nav .= '<a class="txn__item' . ($on ? ' is-on' : '') . '" href="' . adm_url('metinler/' . $id) . '"' . ($on ? ' aria-current="page"' : '') . '>'
        . ui_icon($g['icon'] ?? 'text-aa') . '<span class="txn__name">' . e($g['label']) . '</span>'
        . ($n ? '<em class="txn__n">' . $n . ' değişti</em>' : '') . '</a>';
}
$nav .= '</nav>';

/* ---------- Arama kutusu ---------- */
$searchBox = '<form class="txs" method="get" action="' . adm_url('metinler') . '" role="search">'
    . '<label class="txs__box">' . ui_icon('magnifying-glass')
    . '<input class="txs__in" type="search" name="ara" value="' . e($query) . '" placeholder="Sitede gördüğünüz bir cümleyi yazın" aria-label="Metin ara" autocomplete="off" maxlength="120"></label>'
    . '<button class="btn" type="submit">Ara</button>'
    . ($search ? '<a class="btn btn--ghost" href="' . adm_url('metinler/' . ($gid !== '' ? $gid : array_key_first($registry))) . '">Aramayı temizle</a>' : '')
    . '</form>';

$seoDesc = 'Google sonuçlarında ve tarayıcı sekmesinde görünür; sayfanın kendisinde görünmez.';
$note = '<p class="txt__note">' . ui_icon('sparkle') . '<span>Kaydettiğiniz metin sitede hemen yayınlanır. Bir kutuyu boş bırakırsanız özgün metin geri gelir. Önceki sürümleri Geçmiş bölümünden geri alabilirsiniz.</span></p>';

$actions = '';
$formHtml = '';
$subtitle = 'Sitedeki başlık, paragraf ve düğme yazılarını buradan değiştirirsiniz.';

if ($search) {
    $q       = txt_fold($query);
    $shown   = 0;
    $limit   = 60;
    $cards   = '';
    $keysAll = 0;
    if (mb_strlen($q) >= 2) {
        foreach ($registry as $id => $g) {
            $body = '';
            $cnt  = 0;
            foreach ($g['sections'] ?? [] as $secName => $items) {
                foreach ($items as $k => $it) {
                    $cur = isset($over[$k]) && trim((string) $over[$k]) !== '' ? (string) $over[$k] : (string) $it[1];
                    $hay = txt_fold($it[0] . ' ' . $cur . ' ' . $it[1]);
                    if (strpos($hay, $q) === false) continue;
                    $keysAll++;
                    if ($shown >= $limit) continue;
                    $shown++;
                    $cnt++;
                    $body .= txt_field($k, $it, $cur, preg_replace('/^\d+\.\s*/u', '', $secName));
                }
            }
            if ($body !== '') {
                $cards .= ui_card($g['label'], $body, [
                    'class'   => 'txc',
                    'actions' => '<a class="btn btn--ghost btn--sm" href="' . adm_url('metinler/' . $id) . '">' . ui_icon('pencil-simple') . 'Sayfanın tümü</a>',
                ]);
            }
        }
    }
    if ($cards === '') {
        $msg = mb_strlen($q) < 2
            ? '<strong>Aramak için en az iki harf yazın</strong><span>Sitede gördüğünüz bir kelimeyi ya da cümleyi yazabilirsiniz.</span>'
            : '<strong>"' . e($query) . '" için eşleşen metin bulunamadı</strong><span>Daha kısa yazmayı ya da sitede görünen başka bir kelimeyi deneyin. Hizmet, yazı ve duyuru içerikleri kendi bölümlerinden düzenlenir.</span>';
        $main = ui_card('', '<div class="empty">' . ui_icon('magnifying-glass') . $msg . '</div>');
        $subtitle = 'Arama sonucu yok.';
    } else {
        $more = $keysAll > $shown ? '<p class="txt__more">' . $keysAll . ' sonuçtan ilk ' . $shown . ' tanesi gösteriliyor. Aramayı daraltabilirsiniz.</p>' : '';
        $main = '<form id="txt-form" class="txt__form" method="post" action="' . adm_url('metinler') . '?ara=' . rawurlencode($query) . '">' . adm_csrf_field()
            . '<p class="txt__count"><strong>' . $keysAll . '</strong> metin bulundu</p>' . $cards . $more
            . '<div class="txt__foot"><button class="btn" type="submit">' . ui_icon('floppy-disk') . 'Kaydet</button></div></form>';
        $subtitle = '"' . e($query) . '" araması: ' . $keysAll . ' sonuç.';
        $formHtml = 'txt-form';
    }
    $actions = ui_history_link('texts');
} else {
    $g     = $registry[$gid];
    $total = 0;
    $cards = '';
    foreach ($g['sections'] ?? [] as $secName => $items) {
        $body = '';
        foreach ($items as $k => $it) {
            $cur = isset($over[$k]) && trim((string) $over[$k]) !== '' ? (string) $over[$k] : (string) $it[1];
            $body .= txt_field($k, $it, $cur);
            $total++;
        }
        $cards .= ui_card($secName, $body, ['class' => 'txc', 'desc' => $secName === 'Arama motorları' ? e($seoDesc) : '']);
    }
    $n = $changedBy[$gid] ?? 0;
    $head = '<header class="txg"><span class="txg__ic">' . ui_icon($g['icon'] ?? 'text-aa') . '</span><div><h2 class="txg__t">' . e($g['label']) . '</h2>'
        . '<p class="txg__m">' . $total . ' metin' . ($n ? ' · <b>' . $n . ' tanesi değiştirilmiş</b>' : '') . '</p></div></header>';
    $main = '<form id="txt-form" class="txt__form" method="post" action="' . adm_url('metinler/' . $gid) . '">' . adm_csrf_field()
        . $head . $cards
        . '<div class="txt__foot"><button class="btn" type="submit">' . ui_icon('floppy-disk') . 'Kaydet</button></div></form>';
    $actions  = ui_view_link(url((string) ($g['url'] ?? ''))) . ui_history_link('texts');
    $formHtml = 'txt-form';
}

$html = $searchBox . $note
    . '<div class="txt">' . $nav . '<div class="txt__main">' . $main . '</div></div>'
    . '<script>
(function () {
  var d = document;
  var nav = d.querySelector("[data-txn]");
  if (nav) { var on = nav.querySelector(".is-on"); if (on && nav.scrollWidth > nav.clientWidth) nav.scrollLeft = Math.max(0, on.offsetLeft - 16); }
  var norm = function (s) { return String(s).replace(/\r\n?/g, "\n").trim(); };
  var area = function (box) { return box.querySelector("[data-rt-area]") || box.querySelector("textarea, input.inp"); };
  var current = function (box) {
    var a = box.querySelector("[data-rt-area]");
    if (a) return a.innerHTML.replace(/<p><br><\/p>/g, "");
    var f = box.querySelector("textarea, input.inp");
    return f ? f.value : "";
  };
  var same = function (a, b) { return norm(a).replace(/>\s+</g, "><") === norm(b).replace(/>\s+</g, "><"); };
  d.querySelectorAll("[data-txf]").forEach(function (box) {
    var def = box.getAttribute("data-default");
    var upd = function () { box.classList.toggle("is-changed", !same(current(box), def) && norm(current(box)) !== ""); };
    box.addEventListener("input", upd);
    box.querySelector("[data-txf-reset]").addEventListener("click", function () {
      var a = box.querySelector("[data-rt-area]");
      if (a) { a.innerHTML = def; a.dispatchEvent(new Event("input", { bubbles: true })); }
      else { var f = box.querySelector("textarea, input.inp"); f.value = def; f.dispatchEvent(new Event("input", { bubbles: true })); f.focus(); }
      box.classList.remove("is-changed");
      box.classList.add("is-reset");
    });
  });
})();
</script>';

adm_layout('Sayfa metinleri', $html, [
    'section'  => 'metinler',
    'subtitle' => $subtitle,
    'actions'  => $actions,
    'form'     => $formHtml,
    'wide'     => true,
]);
