<?php
/**
 * Yazılar (blog): liste, ekleme, düzenleme, silme, taslak.
 * Veri: content 'posts' (adres => yazı). Varsayılan: app/data/posts.php. Taslaklar 'draft' => true ile işaretlenir.
 */

$all  = $GLOBALS['posts'];
$slug = $rest[0] ?? null;
$isNew = $slug === 'yeni';

/** Kayıtlı görsel değerinden (kısa kimlik ya da uploads/...) önizleme yolu. */
$imgPath = function (string $img): string {
    if ($img === '') return '';
    return str_contains($img, '/') ? $img : 'img/blog/' . $img . '.webp';
};
/** Kategori adının sitedeki adres karşılığı (app/pages/blog.php ile aynı kural). */
$catSlug = fn(string $c): string => slugify($c);

/* ---------- Liste ---------- */
if ($slug === null) {
    $sorted = $all;
    uasort($sorted, fn($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')));
    $q   = mb_substr(trim((string) ($_GET['ara'] ?? '')), 0, 80);
    $cat = (string) ($_GET['kategori'] ?? '');
    $cats = [];
    foreach ($sorted as $p) { $c = (string) ($p['category'] ?? 'Genel'); $cats[$c] = ($cats[$c] ?? 0) + 1; }
    ksort($cats);
    $shown = array_filter($sorted, function ($p) use ($q, $cat) {
        if ($cat !== '' && (string) ($p['category'] ?? 'Genel') !== $cat) return false;
        if ($q !== '') {
            $hay = ($p['title'] ?? '') . ' ' . ($p['excerpt'] ?? '') . ' ' . ($p['category'] ?? '');
            if (mb_stripos($hay, $q) === false) return false;
        }
        return true;
    });
    $qs = fn(array $x) => ($x ? '?' . http_build_query($x) : '');
    $chips = '<a class="chip' . ($cat === '' ? ' is-on' : '') . '" href="' . adm_url('blog') . $qs($q !== '' ? ['ara' => $q] : []) . '">Tümü <em>' . count($sorted) . '</em></a>';
    foreach ($cats as $c => $n) {
        $chips .= '<a class="chip' . ($cat === (string) $c ? ' is-on' : '') . '" href="' . adm_url('blog') . $qs(array_filter(['kategori' => $c, 'ara' => $q])) . '">' . e($c) . ' <em>' . $n . '</em></a>';
    }
    $rows = '';
    foreach ($shown as $s => $p) {
        $ip = $imgPath((string) ($p['image'] ?? ''));
        $thumb = $ip ? '<span class="blg-thumb"><img src="' . e(media_url($ip)) . '" alt="" loading="lazy"></span>' : '<span class="blg-thumb blg-thumb--none">' . ui_icon('images') . '</span>';
        $draft = !empty($p['draft']);
        $rows .= '<a class="list__row blg-row" href="' . adm_url('blog/' . $s) . '">' . $thumb
            . '<span class="list__main"><span class="list__title">' . e($p['title'] ?? $s) . '</span><span class="list__meta">'
            . '<span>' . e(tr_date((string) ($p['date'] ?? date('Y-m-d')))) . '</span><span>' . e($p['category'] ?? 'Genel') . '</span><span>' . reading_time((string) ($p['body'] ?? '')) . ' dk okuma</span></span></span>'
            . '<span class="list__side">' . ($draft ? '<span class="badge">Taslak</span>' : '') . '<span class="list__go">' . ui_icon('caret-right') . '</span></span></a>';
    }
    $new = '<a class="btn btn--sm" href="' . adm_url('blog/yeni') . '">' . ui_icon('plus') . 'Yeni yazı</a>';
    if (!$all) {
        $body = '<div class="empty">' . ui_icon('newspaper') . '<strong>Henüz yazı yok</strong><span>İlk yazınızı ekleyin; sitedeki Yazılar sayfasında görünür.</span><a class="btn btn--sm" href="' . adm_url('blog/yeni') . '">' . ui_icon('plus') . 'Yeni yazı</a></div>';
    } else {
        $tool = '<form class="blg-tools" method="get" action="' . adm_url('blog') . '" role="search">'
            . ($cat !== '' ? '<input type="hidden" name="kategori" value="' . e($cat) . '">' : '')
            . '<div class="blg-search">' . ui_icon('magnifying-glass') . '<input class="inp" type="search" name="ara" value="' . e($q) . '" placeholder="Başlıkta ara" aria-label="Yazılarda ara" maxlength="80"></div>'
            . '<div class="chips">' . $chips . '</div></form>';
        $list = $rows
            ? '<div class="list" style="margin:18px -22px -22px;border-top:1px solid var(--line)">' . $rows . '</div>'
            : '<div class="empty" style="margin:18px -22px -22px;border-top:1px solid var(--line)">' . ui_icon('magnifying-glass') . '<strong>Aramanızla eşleşen yazı yok</strong><a class="btn btn--soft btn--sm" href="' . adm_url('blog') . '">Filtreleri temizle</a></div>';
        $body = $tool . $list;
    }
    $nDraft = count(array_filter($all, fn($p) => !empty($p['draft'])));
    adm_layout('Yazılar', ui_card('', $body), [
        'section'  => 'blog',
        'subtitle' => count($all) . ' yazı' . ($nDraft ? ', ' . $nDraft . ' taslak' : '') . '. Yayındaki yazılar sitedeki Yazılar sayfasında, en yenisi en üstte görünür.',
        'actions'  => ui_view_link(url('blog')) . ui_history_link('posts') . $new,
    ]);
}

if (!$isNew && !isset($all[$slug])) {
    adm_flash('Yazı bulunamadı.', 'err');
    adm_go('blog');
}

/* ---------- Sil ---------- */
if (($rest[1] ?? '') === 'sil' && $method === 'POST' && !$isNew) {
    unset($all[$slug]);
    content_put('posts', $all) ? adm_flash('Yazı silindi. Geçmişten geri alabilirsiniz.') : adm_flash('Silinemedi: storage klasörü yazılabilir mi?', 'err');
    adm_go('blog');
}

$post = $isNew
    ? ['title' => '', 'category' => 'Genel', 'date' => date('Y-m-d'), 'image' => '', 'excerpt' => '', 'body' => '']
    : $all[$slug];
$post += ['title' => '', 'category' => 'Genel', 'date' => date('Y-m-d'), 'image' => '', 'excerpt' => '', 'body' => ''];
$errors  = [];
$postedSlug = '';

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $stored = (string) ($post['image'] ?? ''); // yalnızca sunucudaki değer
    $post = [
        'title'    => post_str('title', 160),
        'category' => post_str('category', 40) ?: 'Genel',
        'date'     => post_str('date', 10),
        'image'    => $stored,
        'excerpt'  => post_str('excerpt', 300),
        'body'     => sanitize_html((string) ($_POST['body'] ?? '')),
    ];
    if (post_bool('draft')) $post['draft'] = true;
    $postedSlug = post_str('slug', 80);

    if ($post['title'] === '') $errors[] = 'Başlık zorunludur.';
    if ($post['excerpt'] === '') $errors[] = 'Kısa özet zorunludur.';
    if (trim(strip_tags($post['body'])) === '') $errors[] = 'Yazının metni boş olamaz.';
    $dt = DateTime::createFromFormat('Y-m-d', $post['date']);
    if (!$dt || $dt->format('Y-m-d') !== $post['date']) $errors[] = 'Geçerli bir yayın tarihi seçin.';
    if (!preg_match('/^[\p{L}\p{N} \-]+$/u', $post['category']) || $catSlug($post['category']) === '') {
        $errors[] = 'Kategori adı yalnızca harf, rakam, boşluk ve tire içerebilir.';
    }

    $target = (string) $slug;
    if ($isNew) {
        $target = mb_substr(adm_slug($postedSlug ?: $post['title']), 0, 80);
        if (isset($all[$target])) $errors[] = 'Bu sayfa adresi başka bir yazıda kullanılıyor; farklı bir adres yazın.';
    }
    if (!$errors) {
        if (post_bool('image_remove')) $post['image'] = '';
        $up = $_FILES['image'] ?? null;
        if (is_array($up) && ($up['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $post['image'] = upload_image($up, 'blog', 1600);
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
                $post['image'] = $stored;
            }
        }
    }
    if (!$errors) {
        $all[$target] = $post;
        if (content_put('posts', $all)) {
            adm_flash($isNew ? 'Yazı eklendi.' : 'Yazı kaydedildi.');
            adm_go('blog/' . $target);
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
    }
}

/* ---------- Düzenleme formu ---------- */
$known = [];
foreach ($GLOBALS['posts'] as $p) $known[(string) ($p['category'] ?? 'Genel')] = 1;
$known['Genel'] = 1;
ksort($known);
$dl = '<datalist id="cat-list">';
foreach (array_keys($known) as $c) $dl .= '<option value="' . e((string) $c) . '">';
$dl .= '</datalist>';
$catField = ui_wrap('category', 'Kategori', '<input class="inp" type="text" id="f-category" name="category" list="cat-list" maxlength="40" value="' . e($post['category']) . '" autocomplete="off" aria-describedby="f-category-h">' . $dl, [
    'help' => 'Listeden seçin ya da yeni bir ad yazın. Sitede kategori sayfası olarak görünür.',
]);
$isDraft = !empty($post['draft']);
$imgCur  = $imgPath((string) $post['image']);

ob_start();
if ($errors) echo ui_alert(e(implode(' ', $errors)));
?>
<form id="post-form" method="post" enctype="multipart/form-data" action="<?= adm_url('blog/' . ($isNew ? 'yeni' : $slug)) ?>" class="split">
  <?= adm_csrf_field() ?>
  <div style="display:grid;gap:20px;min-width:0">
    <?= ui_card('Yazı', implode('', [
        ui_text('title', 'Başlık', $post['title'], ['required' => true, 'maxlength' => 160, 'counter' => true, 'id' => 'f-title', 'placeholder' => 'Örn: Ar-Ge merkezi kurmadan önce bilinmesi gerekenler']),
        $isNew
            ? ui_text('slug', 'Sayfa adresi', $postedSlug, ['maxlength' => 80, 'slug_from' => '#f-title', 'help' => 'Başlıktan otomatik oluşur: ' . e(url('blog/')) . '<b>adres</b>'])
            : ui_text('slug_ro', 'Sayfa adresi', url('blog/' . $slug), ['readonly' => true, 'help' => 'Arama motorlarındaki sıralamayı korumak için mevcut adresler değiştirilmez.']),
        ui_textarea('excerpt', 'Kısa özet', $post['excerpt'], ['required' => true, 'rows' => 3, 'maxlength' => 300, 'counter' => true, 'help' => 'Yazı listesinde ve Google sonuçlarında görünen bir iki cümlelik özet.']),
    ])) ?>
    <?= ui_card('Metin', ui_rich('body', 'Yazının metni', (string) $post['body'], ['help' => 'Ara başlıklar ve listeler okunabilirliği artırır. Görsel eklemek için sağdaki kapak görselini kullanın.'])
        . '<p class="muted blg-count" data-blg-count aria-live="polite"></p>') ?>
  </div>

  <aside class="split__side">
    <?= ui_card('Yayın', implode('', [
        ui_toggle('draft', 'Taslak olarak sakla', $isDraft, ['help' => 'Taslaklar sitede görünmez.']),
        ui_text('date', 'Yayın tarihi', $post['date'], ['type' => 'date', 'required' => true, 'help' => 'Listede bu tarihe göre sıralanır.']),
        $catField,
        '<button class="btn btn--block" type="submit">' . ui_icon('floppy-disk') . ($isNew ? 'Yazıyı ekle' : 'Kaydet') . '</button>',
        (!$isNew && !$isDraft) ? ui_view_link(post_url($slug), 'Sitede gör') : '',
        ui_history_link('posts'),
    ]), ['class' => 'blg-pub']) ?>
    <?= ui_card('Kapak görseli', ui_image('image', '', $imgCur, ['help' => 'Yazı listesinde ve yazının başında görünür. Yatay görseller en iyi sonucu verir: en az 800×450 piksel, genişlik/yükseklik oranı 1,2 ile 2,4 arasında (liste 3:2, yazı başı 21:9 kutuya kırpılır); en çok 8 MB.'])) ?>
  </aside>
</form>
<?php if (!$isNew): ?>
  <?= ui_card('Yazıyı sil', '<form method="post" action="' . adm_url('blog/' . $slug . '/sil') . '" data-confirm="&quot;' . e($post['title']) . '&quot; silinsin mi? Yazı sitede yayından kalkar.">' . adm_csrf_field()
      . '<p class="muted" style="margin-bottom:12px">Yazı ve sayfası sitede yayından kalkar. Yanlışlıkla silerseniz Geçmiş bölümünden geri alabilirsiniz.</p>'
      . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Yazıyı sil</button></form>', ['class' => 'card--danger']) ?>
<?php endif; ?>
<script>
(function () {
  var area = document.querySelector('[data-rt-area]'), out = document.querySelector('[data-blg-count]');
  if (!area || !out) return;
  var upd = function () {
    var n = (area.innerText || '').trim().split(/\s+/).filter(Boolean).length;
    out.textContent = n ? n + ' kelime, yaklaşık ' + Math.max(1, Math.ceil(n / 180)) + ' dakikalık okuma' : '';
  };
  area.addEventListener('input', upd); upd();
})();
</script>
<?php
adm_layout($isNew ? 'Yeni yazı' : (string) $post['title'], (string) ob_get_clean(), [
    'section' => 'blog',
    'crumbs'  => [['Yazılar', adm_url('blog')]],
    'form'    => 'post-form',
]);
