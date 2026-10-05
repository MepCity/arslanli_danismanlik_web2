<?php
/**
 * Sayfa metinleri: sitedeki başlık, paragraf, düğme ve uyarı yazılarının düzenlenmesi.
 * Kayıt: content 'texts' (yalnızca değiştirilen metinler: anahtar => metin).
 * Kayıt listesi: app/data/texts/*.php (texts_registry / texts_flat); türler, biçim işaretleri ve yer tutucular: app/texts.php.
 * Doğrulama ve kaydetme texts_save() içindedir (MCP de aynısını kullanır); bu dosya yalnızca ekranı çizer.
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
                if (isset($over[$k]) && is_string($over[$k]) && trim($over[$k]) !== '' && $over[$k] !== (string) $it[1]) $n++;
            }
        }
        $out[$gid] = $n;
    }
    return $out;
}

/** Tür adı (alanın yanında küçük yazı). */
function txt_type_name(string $type): string
{
    return ['line' => 'Tek satır', 'text' => 'Paragraf', 'lines' => 'Satır sonlu', 'rich' => 'Biçimli'][$type] ?? $type;
}

/** Düzenleme alanı: etiket, kutu, yer tutucu düğmeleri, sınır sayacı, hata ve özgün metin. $error: son kayıtta bu metin için verilen ret nedeni. */
function txt_field(string $key, array $item, string $current, string $context = '', ?string $error = null): string
{
    [$label, $default, $type] = [$item[0], (string) $item[1], $item[2] ?? 'line'];
    $help    = $item[3] ?? '';
    $opts    = texts_opts($key);
    $name    = 't[' . $key . ']';
    $id      = ui_id($name);
    $changed = $current !== $default;
    $len     = mb_strlen(text_strip_marks($current));
    $o       = ['id' => $id, 'help' => ''];

    if ($type === 'line') {
        $ctl = ui_text($name, '', $current, $o);
    } else {
        $rows = $type === 'text' ? max(2, min(9, (int) ceil($len / 68) + 1)) : max(2, min(7, substr_count($current, "\n") + 2));
        $ctl  = ui_textarea($name, '', $current, $o + ['rows' => $rows]);
    }

    $tools = '';
    if ($type === 'rich') {
        $tools .= '<div class="txf__bar" role="group" aria-label="Biçim"><span class="txf__barl">Biçim</span>'
            . '<button type="button" class="txf__fmt" data-fmt="kalın"><span><b>K</b>alın</span></button>'
            . '<button type="button" class="txf__fmt" data-fmt="eğik"><span><em>E</em>ğik</span></button>'
            . '<button type="button" class="txf__fmt" data-fmt="çizgi">Alt çizgi</button>'
            . '<button type="button" class="txf__fmt txf__fmt--red" data-fmt="kırmızı-çizgi">Kırmızı çizgi</button>'
            . '<button type="button" class="txf__fmt" data-fmt="halka">Halka</button></div>';
    }
    $chips = '';
    foreach ($opts['vars'] as $v => $desc) {
        $need = in_array($v, $opts['need'], true);
        $chips .= '<button type="button" class="txf__var' . ($need ? ' is-need' : '') . '" data-ins="{' . e($v) . '}" title="' . e(($desc !== '' ? $desc : $v) . ($need ? ' (silinmemeli)' : '')) . '">{' . e($v) . '}'
            . ($desc !== '' ? '<small>' . e($desc) . '</small>' : '') . '</button>';
    }
    if ($chips !== '') $tools .= '<div class="txf__vars"><span class="txf__barl">Yer tutucular</span>' . $chips . '</div>';

    $meta = '<p class="txf__meta"><span class="txf__type">' . e(txt_type_name($type)) . '</span>'
        . '<span class="txf__cnt" data-cnt>' . $len . ' / ' . $opts['max'] . '</span></p>';

    return '<div class="txf' . ($changed ? ' is-changed' : '') . ($error !== null ? ' has-error' : '') . '" data-txf data-type="' . e($type) . '" data-max="' . (int) $opts['max'] . '" data-lines="' . (int) $opts['lines'] . '" data-default="' . e($default) . '">'
        . '<input type="hidden" name="keys[]" value="' . e($key) . '">'
        . '<div class="txf__head">'
        .   '<label class="txf__label" for="' . e($id) . '">' . e($label) . '</label>'
        .   '<span class="txf__tools"><span class="badge badge--coral txf__badge">Değiştirildi</span>'
        .   '<button class="txf__reset" type="button" data-txf-reset>' . ui_icon('arrow-counter-clockwise') . 'Özgün metne dön</button></span>'
        . '</div>'
        . ($context !== '' ? '<p class="txf__ctx">' . e($context) . '</p>' : '')
        . $tools
        . $ctl
        . $meta
        . '<p class="txf__err" role="alert"' . ($error === null ? ' hidden' : '') . '>' . e((string) $error) . '</p>'
        . ($help !== '' ? '<p class="txf__help">' . e($help) . '</p>' : '')
        . '<details class="txf__orig"><summary>Özgün metin</summary><div class="txf__origtext"><p>' . nl2br(e($default)) . '</p></div></details>'
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
$txErr = [];   // anahtar => ret nedeni (kayıt reddedilirse alanlar yazılanlarla yeniden çizilir)
$txVal = [];
if ($method === 'POST') {
    $posted = is_array($_POST['t'] ?? null) ? $_POST['t'] : [];
    $keys   = is_array($_POST['keys'] ?? null) ? $_POST['keys'] : [];

    // Bu ekranın içerebileceği anahtarlar
    $allowed = [];
    if ($search) {
        $allowed = array_keys($flat);
    } else {
        foreach ($registry[$gid]['sections'] ?? [] as $items) foreach ($items as $k => $it) $allowed[] = $k;
    }

    $values = [];
    foreach ($keys as $k) {
        if (!is_string($k)) continue;
        $v = $posted[$k] ?? '';
        $values[$k] = is_string($v) ? $v : null;
    }
    $res = texts_save($values, $allowed);
    if ($res['ok']) {
        adm_flash($res['changed'] ? count($res['changed']) . ' metin kaydedildi ve sitede yayınlandı.' : 'Değişiklik yok.');
        adm_go($search ? 'metinler?ara=' . rawurlencode($query) : 'metinler/' . $gid);
    }
    // Reddedildi: hiçbir şey kaydedilmedi; yazılanlar yerinde kalır, nedenler alanların altında görünür
    $txErr = $res['errors'];
    foreach ($values as $k => $v) if (is_string($v)) $txVal[$k] = $v;
    $first = reset($txErr);
    $n = count($txErr);
    adm_flash('Kaydedilemedi: ' . $n . ' metinde sorun var; hiçbir değişiklik kaydedilmedi. ' . ($n === 1 ? (string) $first : 'Sorunlu alanlar işaretlendi.'), 'err');
}

/* ---------- Sol liste ---------- */
$counts = [];
foreach ($registry as $id => $g) { $counts[$id] = 0; foreach ($g['sections'] ?? [] as $items) $counts[$id] += count($items); }
$nav = '<nav class="txn" aria-label="Sayfalar" data-txn>';
foreach ($registry as $id => $g) {
    $n   = $changedBy[$id] ?? 0;
    $on  = !$search && $id === $gid;
    $nav .= '<a class="txn__item' . ($on ? ' is-on' : '') . '" href="' . adm_url('metinler/' . $id) . '"' . ($on ? ' aria-current="page"' : '') . '>'
        . ui_icon($g['icon'] ?? 'text-aa') . '<span class="txn__name">' . e($g['label']) . '</span>'
        . ($n ? '<i class="txn__dot" role="img" title="' . $n . ' metin değiştirilmiş" aria-label="' . $n . ' metin değiştirilmiş"></i>' : '') . '<span class="txn__c" title="Bu sayfadaki metin sayısı">' . $counts[$id] . '</span></a>';
}
$nav .= '</nav>';

/* ---------- Arama kutusu ---------- */
$searchBox = '<form class="txs" method="get" action="' . adm_url('metinler') . '" role="search">'
    . '<label class="txs__box">' . ui_icon('magnifying-glass')
    . '<input class="txs__in" type="search" name="ara" value="' . e($query) . '" placeholder="Sitede gördüğünüz bir cümleyi yazın" aria-label="Metin ara" autocomplete="off" maxlength="120"></label>'
    . '<button class="btn" type="submit">Ara</button>'
    . ($search ? '<a class="btn btn--ghost" href="' . adm_url('metinler/' . ($gid !== '' ? $gid : array_key_first($registry))) . '">Aramayı temizle</a>' : '')
    . '</form>';

$note = '<p class="txt__note">' . ui_icon('sparkle') . '<span>Kaydettiğiniz metin sitede hemen yayınlanır. Bir kutuyu boş bırakırsanız özgün metin geri gelir. Önceki sürümleri Değişiklik geçmişi bölümünden geri alabilirsiniz. Köşeli işaretler ([kalın]…[/kalın]) ve {süslü} yer tutucular yalnızca altında belirtilen yerlerde yazılabilir; HTML yazılamaz.</span></p>';

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
                    $cur = isset($over[$k]) && is_string($over[$k]) && trim($over[$k]) !== '' ? $over[$k] : (string) $it[1];
                    $hay = txt_fold($k . ' ' . $it[0] . ' ' . text_strip_marks($cur) . ' ' . text_strip_marks((string) $it[1]));
                    if (strpos($hay, $q) === false) continue;
                    $keysAll++;
                    if ($shown >= $limit) continue;
                    $shown++;
                    $cnt++;
                    $body .= txt_field($k, $it, $txVal[$k] ?? $cur, preg_replace('/^\d+\.\s*/u', '', $secName), $txErr[$k] ?? null);
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
            $cur = isset($over[$k]) && is_string($over[$k]) && trim($over[$k]) !== '' ? $over[$k] : (string) $it[1];
            $body .= txt_field($k, $it, $txVal[$k] ?? $cur, '', $txErr[$k] ?? null);
            $total++;
        }
        $cards .= ui_card($secName, $body, ['class' => 'txc']);
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
  var field = function (box) { return box.querySelector("textarea, input.inp"); };
  var plainLen = function (s) { return s.replace(/\[\/?(?:kalın|eğik|çizgi|kırmızı-çizgi|halka|kırmızı-halka)\]/g, "").length; };
  d.querySelectorAll("[data-txf]").forEach(function (box) {
    var def = box.getAttribute("data-default");
    var f = field(box);
    var cnt = box.querySelector("[data-cnt]");
    var max = parseInt(box.getAttribute("data-max"), 10) || 0;
    var upd = function () {
      var v = f.value;
      box.classList.toggle("is-changed", norm(v) !== "" && norm(v) !== norm(def));
      if (cnt) { var n = plainLen(v); cnt.textContent = n + " / " + max; cnt.classList.toggle("is-over", max > 0 && n > max); }
    };
    f.addEventListener("input", function () { box.classList.remove("has-error"); var e = box.querySelector(".txf__err"); if (e) e.hidden = true; upd(); });
    box.querySelector("[data-txf-reset]").addEventListener("click", function () {
      f.value = def; f.dispatchEvent(new Event("input", { bubbles: true })); f.focus();
      box.classList.remove("is-changed"); box.classList.add("is-reset");
    });
    var put = function (before, after, replace) {
      var a = f.selectionStart, b = f.selectionEnd, v = f.value;
      var mid = replace ? "" : v.slice(a, b);
      f.value = v.slice(0, a) + before + mid + after + v.slice(b);
      var c = a + before.length;
      f.focus();
      if (replace) f.setSelectionRange(c, c); else f.setSelectionRange(c, c + mid.length);
      f.dispatchEvent(new Event("input", { bubbles: true }));
    };
    box.querySelectorAll("[data-fmt]").forEach(function (btn) {
      btn.addEventListener("click", function () { var t = btn.getAttribute("data-fmt"); put("[" + t + "]", "[/" + t + "]"); });
    });
    box.querySelectorAll("[data-ins]").forEach(function (btn) {
      btn.addEventListener("click", function () { put(btn.getAttribute("data-ins"), "", true); });
    });
    upd();
  });
  var bad = d.querySelector(".txf.has-error");
  if (bad) bad.scrollIntoView({ block: "center" });
})();
</script>';

adm_layout('Sayfa metinleri', $html, [
    'section'  => 'metinler',
    'subtitle' => $subtitle,
    'actions'  => $actions,
    'form'     => $formHtml,
    'wide'     => true,
]);
