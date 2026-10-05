<?php
/**
 * Ziyaretçiler: siteye kaç kişi geldi, hangi sayfalara bakıldı, nereden gelindi.
 * Sayaç: app/stats.php (çerezsiz; IP adresi ve kişisel veri saklanmaz).
 * Adres: /yonetim/ziyaretciler?gun=7|30|90
 */

require_once APP . '/stats.php';

$ranges = [7 => 'Son 7 gün', 30 => 'Son 30 gün', 90 => 'Son 90 gün'];
$n = (int) ($_GET['gun'] ?? 30);
if (!isset($ranges[$n])) {
    $n = 30;
}
$all   = stats_days(90);
$days  = array_slice($all, -$n, null, true);
$tot   = stats_totals($days);
$dates = array_keys($all);
$today = $all[$dates[89]];
$yest  = $all[$dates[88]];
$t7    = stats_totals(array_slice($all, -7, null, true));
$t30   = stats_totals(array_slice($all, -30, null, true));
$since = stats_since();

$num    = fn(int $x): string => number_format($x, 0, ',', '.');
$months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
$short  = fn(string $d): string => (int) substr($d, 8, 2) . ' ' . $months[(int) substr($d, 5, 2) - 1];

/* ---------- Özet kutuları (sabit dönemler) ---------- */
$tile = fn(string $label, string $icon, int $u, int $v): string => '<div class="stat"><span class="stat__top">' . e($label) . ' ' . ui_icon($icon) . '</span>'
    . '<span class="stat__n">' . $num($u) . '</span><span class="stat__note">ziyaretçi · ' . $num($v) . ' sayfa görüntüleme</span></div>';
$tiles = '<section class="stats vz-tiles" aria-label="Ziyaretçi özeti">'
    . $tile('Bugün', 'users-three', $today['u'], $today['v'])
    . $tile('Dün', 'users-three', $yest['u'], $yest['v'])
    . $tile('Son 7 gün', 'calendar-blank', $t7['u'], $t7['v'])
    . $tile('Son 30 gün', 'calendar-blank', $t30['u'], $t30['v'])
    . '</section>';

/* ---------- Günlük ziyaretçi grafiği (sütunlar, tek seri) ---------- */
$chart = function (array $days) use ($num, $short): string {
    $W = 760; $H = 240; $L = 40; $R = 10; $T = 14; $B = 28;
    $pw = $W - $L - $R; $ph = $H - $T - $B;
    $count = count($days);
    $max = max(array_merge([0], array_column($days, 'u')));
    // Eksen: temiz sayılara yuvarlanmış en çok beş aralık
    $step = 1;
    foreach ([1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000] as $s) {
        $step = $s;
        if ($max / $s <= 5) break;
    }
    $top = max($step * 2, (int) ceil($max / $step) * $step);
    $y = fn(float $v): float => round($T + $ph - ($v / $top) * $ph, 1);

    $grid = '';
    for ($v = 0; $v <= $top; $v += $step) {
        $grid .= '<line class="vz-grid' . ($v === 0 ? ' vz-base' : '') . '" x1="' . $L . '" x2="' . ($W - $R) . '" y1="' . $y($v) . '" y2="' . $y($v) . '"/>'
            . '<text class="vz-tick" x="' . ($L - 8) . '" y="' . ($y($v) + 4) . '" text-anchor="end">' . $num($v) . '</text>';
    }
    $slot = $pw / $count;
    $bw = max(2, min(16, $slot * 0.68));   // ince sütun: aralığın kalanı boşluk
    $every = $count <= 10 ? 1 : ($count <= 31 ? 5 : 15);
    $bars = ''; $hits = ''; $labels = '';
    $i = 0;
    foreach ($days as $d => $row) {
        $x = $L + $i * $slot + ($slot - $bw) / 2;
        $h = $row['u'] > 0 ? max(2, ($row['u'] / $top) * $ph) : 0;
        if ($h > 0) {
            $r = min(4, $bw / 2, $h);
            $x0 = round($x, 1); $x1 = round($x + $bw, 1); $yt = round($T + $ph - $h, 1); $yb = $T + $ph;
            $bars .= '<path class="vz-bar" data-i="' . $i . '" d="M' . $x0 . ' ' . $yb . 'V' . round($yt + $r, 1) . 'Q' . $x0 . ' ' . $yt . ' ' . round($x0 + $r, 1) . ' ' . $yt
                . 'H' . round($x1 - $r, 1) . 'Q' . $x1 . ' ' . $yt . ' ' . $x1 . ' ' . round($yt + $r, 1) . 'V' . $yb . 'Z"/>';
        }
        $hits .= '<rect class="vz-hit" data-i="' . $i . '" data-d="' . e(tr_date($d)) . '" data-u="' . (int) $row['u'] . '" data-v="' . (int) $row['v'] . '" data-y="' . round($T + $ph - $h, 1)
            . '" x="' . round($L + $i * $slot, 1) . '" y="' . $T . '" width="' . round($slot, 1) . '" height="' . $ph . '"/>';
        $fromEnd = $count - 1 - $i;
        if ($fromEnd % $every === 0) {
            $labels .= '<text class="vz-tick" x="' . round($L + $i * $slot + $slot / 2, 1) . '" y="' . ($H - 8) . '" text-anchor="' . ($fromEnd === 0 ? 'end' : 'middle') . '"'
                . ($fromEnd === 0 ? ' dx="' . round($slot / 2, 1) . '"' : '') . '>' . e($fromEnd === 0 ? 'Bugün' : $short($d)) . '</text>';
        }
        $i++;
    }
    return '<div class="vz-chart" data-vz tabindex="0" role="group" aria-label="Günlük ziyaretçi grafiği. Günler arasında sol ve sağ ok tuşlarıyla gezinebilirsiniz; aynı sayılar aşağıdaki tabloda da vardır.">'
        . '<svg viewBox="0 0 ' . $W . ' ' . $H . '" role="img" aria-hidden="true" focusable="false">' . $grid . $bars . $labels . $hits . '</svg>'
        . '<div class="vz-tip" data-vz-tip hidden><span class="vz-tip__d"></span><strong></strong><span class="vz-tip__v"></span></div></div>';
};

$tableRows = '';
foreach (array_reverse($days, true) as $d => $row) {
    $tableRows .= '<tr><td>' . e(tr_date($d)) . '</td><td class="vz-num">' . $num($row['u']) . '</td><td class="vz-num">' . $num($row['v']) . '</td></tr>';
}
$table = '<details class="vz-details"><summary>Tablo olarak göster</summary><div class="tbl-wrap vz-wrap"><table class="tbl"><thead><tr><th>Gün</th><th class="vz-num">Ziyaretçi</th><th class="vz-num">Sayfa görüntüleme</th></tr></thead><tbody>'
    . $tableRows . '</tbody></table></div></details>';

/* ---------- Sıralı listeler: sayfalar, kaynaklar, cihaz ---------- */
$rank = function (array $rows, string $empty) use ($num): string {
    if (!$rows) {
        return '<p class="muted">' . e($empty) . '</p>';
    }
    $max = max(array_column($rows, 1)) ?: 1;
    $out = '';
    foreach ($rows as [$label, $val, $sub]) {
        $out .= '<li><span class="vz-rank__l">' . e($label) . ($sub !== '' ? '<small>' . e($sub) . '</small>' : '') . '</span><span class="vz-rank__n">' . $num((int) $val) . '</span>'
            . '<span class="vz-rank__bar" aria-hidden="true"><i style="width:' . max(1, round($val / $max * 100, 1)) . '%"></i></span></li>';
    }
    return '<ol class="vz-rank">' . $out . '</ol>';
};

/** Sayfa yolundan okunur ad (Aşama 3A'da app/seo_scan.php'deki seoa_page_info ile birleştirilebilir). */
$pageName = function (string $p): string {
    $static = ['' => 'Ana sayfa', 'hakkimizda' => 'Hakkımızda', 'hizmetler' => 'Hizmetler', 'referans' => 'Referanslar', 'blog' => 'Makaleler', 'iletisim' => 'İletişim',
        'haberdarol' => 'Haberdar Ol', 'hesap-numaralarimiz' => 'Hesap Numaralarımız', 'duyurular' => 'Duyurular', 'kariyer' => 'Kariyer',
        'kurumsal/misyonumuz' => 'Misyonumuz', 'kurumsal/vizyonumuz' => 'Vizyonumuz', 'kurumsal/mihenk-taslarimiz' => 'Mihenk Taşlarımız',
        'kurumsal/cerez-politikasi' => 'Çerez Politikası', 'kurumsal/kvkk-aydinlatma-metni' => 'KVKK Aydınlatma Metni'];
    if (isset($static[$p])) return $static[$p];
    if (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $p, $m)) return (string) (services()[$m[1]]['title'] ?? $p);
    if (preg_match('#^blog/category/([a-z0-9\-]+)$#', $p, $m)) return 'Makaleler: ' . $m[1] . ' bölümü';
    if (preg_match('#^blog/([a-z0-9\-]+)$#', $p, $m)) return (string) ($GLOBALS['posts'][$m[1]]['title'] ?? $p);
    if (preg_match('#^kariyer/([a-z0-9\-]+)$#', $p, $m)) return (string) ((ilan_find_slug($m[1]) ?? [])['title'] ?? $p);
    return $p;
};
$pages = [];
foreach (array_slice($tot['p'], 0, 10, true) as $path => $views) {
    $name = $pageName(trim((string) $path, '/'));
    $pages[] = [$name !== '' && $name !== trim((string) $path, '/') ? $name : (string) $path, (int) $views, $path === '/' ? '/' : (string) $path];
}
$sources = [];
foreach (array_slice($tot['r'], 0, 8, true) as $src => $c) {
    $sources[] = [$src === '(doğrudan)' ? 'Doğrudan' : ($src === '(diğer)' ? 'Diğer siteler' : (string) $src), (int) $c,
        $src === '(doğrudan)' ? 'Adresi yazarak, yer iminden ya da bir uygulamadan' : ''];
}
$desktop = max(0, $tot['u'] - $tot['um']);
$pct = fn(int $x): string => $tot['u'] > 0 ? ' · %' . round($x / $tot['u'] * 100) : '';
$devices = $tot['u'] > 0 ? [['Telefon ve tablet', $tot['um'], ''], ['Bilgisayar', $desktop, '']] : [];
usort($devices, fn($a, $b) => $b[1] <=> $a[1]);

/* ---------- Sayfa ---------- */
$tabs = '';
foreach ($ranges as $k => $label) {
    $tabs .= '<a class="tab' . ($n === $k ? ' is-on' : '') . '" href="' . adm_url('ziyaretciler') . '?gun=' . $k . '"' . ($n === $k ? ' aria-current="true"' : '') . '>' . $label . '</a>';
}

ob_start();
echo $tiles;
if ($since === null): ?>
  <?= ui_card('', '<div class="empty">' . ui_icon('users-three') . '<strong>Henüz ziyaret kaydı yok</strong><span>Sayaç çalışıyor. Sitenin bir sayfası açıldığında ilk ziyaret burada görünür. Bu tarayıcıdan yaptığınız ziyaretler sayılmaz.</span>' . ui_view_link(url(), 'Siteyi aç') . '</div>') ?>
<?php else: ?>
  <div class="tabs vz-range" role="group" aria-label="Dönem"><?= $tabs ?></div>
  <?= ui_card('Günlük ziyaretçi', $chart($days) . $table, [
      'desc' => e($ranges[$n]) . ': ' . $num($tot['u']) . ' ziyaretçi, ' . $num($tot['v']) . ' sayfa görüntüleme. Bir günün üzerine gelince o günün sayıları görünür.',
  ]) ?>
  <div class="grid2 vz-cols">
    <?= ui_card('En çok bakılan sayfalar', $rank($pages, 'Bu dönemde kayıt yok.'), ['desc' => 'Sayfa görüntüleme sayısına göre ilk 10 sayfa.']) ?>
    <div style="display:grid;gap:20px;align-content:start">
      <?= ui_card('Ziyaretçiler nereden geliyor', $rank($sources, 'Bu dönemde kayıt yok.'), ['desc' => 'Siteye girişlerin geldiği yerler.']) ?>
      <?= ui_card('Cihaz', $rank(array_map(fn($r) => [$r[0] . $pct((int) $r[1]), $r[1], ''], $devices), 'Bu dönemde kayıt yok.')) ?>
    </div>
  </div>
<?php endif; ?>
<p class="vz-note"><?= ui_icon('lock-key') ?><span>Sayaç çerez kullanmaz ve IP adresi saklamaz. Ziyaretçiler, her gün yenilenen anonim bir özetle gün içinde bir kez sayılır; bu yüzden birden fazla günü kapsayan toplamlar, o günlerin ziyaretçi sayılarının toplamıdır. Arama motoru ve yapay zekâ botları ile yönetim paneline giriş yapılan tarayıcılar sayılmaz.<?= $since ? ' Sayım ' . e(tr_date($since)) . ' gününde başladı.' : '' ?></span></p>
<script>
(function () {
  var box = document.querySelector('[data-vz]');
  if (!box) return;
  var tip = box.querySelector('[data-vz-tip]'), hits = [].slice.call(box.querySelectorAll('.vz-hit')), bars = {}, cur = -1;
  [].forEach.call(box.querySelectorAll('.vz-bar'), function (b) { bars[b.getAttribute('data-i')] = b; });
  var nf = new Intl.NumberFormat('tr-TR');
  function show(i) {
    if (i < 0 || i >= hits.length) return;
    if (bars[cur]) bars[cur].classList.remove('is-on');
    cur = i;
    var h = hits[i];
    if (bars[i]) bars[i].classList.add('is-on');
    tip.children[0].textContent = h.getAttribute('data-d');
    tip.children[1].textContent = nf.format(+h.getAttribute('data-u')) + ' ziyaretçi';
    tip.children[2].textContent = nf.format(+h.getAttribute('data-v')) + ' sayfa görüntüleme';
    tip.hidden = false;
    var svg = box.querySelector('svg').getBoundingClientRect(), r = h.getBoundingClientRect(), b = box.getBoundingClientRect();
    var scale = svg.height / 240;
    var x = r.left + r.width / 2 - b.left, y = svg.top - b.top + (+h.getAttribute('data-y')) * scale;
    var w = tip.offsetWidth, th = tip.offsetHeight;
    tip.style.left = Math.max(0, Math.min(b.width - w, x - w / 2)) + 'px';
    tip.style.top = Math.max(0, y - th - 10) + 'px';
  }
  function hide() { if (bars[cur]) bars[cur].classList.remove('is-on'); cur = -1; tip.hidden = true; }
  hits.forEach(function (h, i) { h.addEventListener('pointerenter', function () { show(i); }); });
  box.addEventListener('pointerleave', hide);
  box.addEventListener('focus', function () { if (cur < 0) show(hits.length - 1); });
  box.addEventListener('blur', hide);
  box.addEventListener('keydown', function (e) {
    var k = e.key, i = cur < 0 ? hits.length - 1 : cur;
    if (k === 'ArrowLeft') i--; else if (k === 'ArrowRight') i++; else if (k === 'Home') i = 0; else if (k === 'End') i = hits.length - 1; else return;
    e.preventDefault();
    show(Math.max(0, Math.min(hits.length - 1, i)));
  });
})();
</script>
<?php
adm_layout('Ziyaretçiler', (string) ob_get_clean(), [
    'section'  => 'ziyaretciler',
    'subtitle' => 'Siteye kaç kişinin geldiği, hangi sayfalara bakıldığı ve ziyaretçilerin nereden geldiği.',
    'actions'  => ui_view_link(url(), 'Siteyi aç'),
]);
