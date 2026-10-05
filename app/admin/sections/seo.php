<?php
/**
 * SEO ve yapay zekâ: site taraması, arama motoru bağlantıları, yapay zekâ görünürlüğü.
 * Ayarlar: content 'seo' (bkz. seo_defaults()). Bildirim: app/indexnow.php.
 * Adresler: /yonetim/seo/{genel-bakis|ayarlar|yapay-zeka}; yardımcı: onizleme, kontrol (JSON).
 */

require_once APP . '/indexnow.php';

/* =========================================================================
   Site taraması
   ========================================================================= */

require_once APP . '/seo_scan.php';  // tarama işlevleri (panel ve yapay zekâ erişimi ortak kullanır)

/* =========================================================================
   Sunucunun kendi adreslerine bakış (robots.txt, llms.txt, Markdown sürümleri…)
   ========================================================================= */

function seoa_self_origin(): string
{
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    $h    = indexnow_host($host);
    $loop = (bool) preg_match('/^(127\.|localhost$|\[?::1)/', $h);
    if ($host !== '' && preg_match('/^[A-Za-z0-9.\-:\[\]]+$/', $host) && ($loop || $h === indexnow_site_host())) {
        $https = !$loop && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return ($https ? 'https://' : 'http://') . $host;
    }
    return rtrim((string) cfg('url'), '/');
}

/** @return array{code:int, type:string, body:string, bytes:int} */
function seoa_fetch(string $path, int $max = 200000): array
{
    $u = seoa_self_origin() . url($path);
    $out = ['code' => 0, 'type' => '', 'body' => '', 'bytes' => 0];
    try {
        if (function_exists('curl_init')) {
            $ch = curl_init($u);
            $body = '';
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2, CURLOPT_TIMEOUT => 3, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_WRITEFUNCTION  => function ($c, $chunk) use (&$body, $max) { $body .= $chunk; return strlen($body) > $max ? 0 : strlen($chunk); },
            ]);
            curl_exec($ch);
            $out['code'] = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $out['type'] = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $out['body'] = $body;
        } else {
            $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true, 'follow_location' => 0]]);
            $r = @file_get_contents($u, false, $ctx, 0, $max);
            $out['body'] = $r === false ? '' : $r;
            foreach ($http_response_header ?? [] as $h) {
                if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) $out['code'] = (int) $m[1];
                if (stripos($h, 'content-type:') === 0) $out['type'] = trim(substr($h, 13));
            }
        }
    } catch (Throwable $e) {
    }
    $out['bytes'] = strlen($out['body']);
    return $out;
}

function seoa_json(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* =========================================================================
   Ayar yardımcıları
   ========================================================================= */

function seoa_save(array $s): bool
{
    return content_put('seo', $s);
}

function seoa_copy_row(string $label, string $note, string $abs, string $local, string $probe = ''): string
{
    return '<div class="sx-link">'
        . '<div class="sx-link__txt"><strong>' . e($label) . ($probe !== '' ? ' <i class="sx-dot" data-probe="' . e($probe) . '" title="Kontrol ediliyor"></i>' : '') . '</strong><span>' . e($note) . '</span></div>'
        . '<div class="sx-link__url"><code>' . e($abs) . '</code>'
        . '<button class="btn btn--ghost btn--sm" type="button" data-copy="' . e($abs) . '">' . ui_icon('copy') . '<span>Kopyala</span></button>'
        . '<a class="btn btn--ghost btn--sm" href="' . e($local) . '" target="_blank" rel="noopener">' . ui_icon('arrow-square-out') . 'Aç</a></div></div>';
}

/* =========================================================================
   Yönlendirme
   ========================================================================= */

$tabs = ['genel-bakis' => 'Genel bakış', 'ayarlar' => 'Arama motorları', 'yapay-zeka' => 'Yapay zekâ'];
$tab  = $rest[0] ?? 'genel-bakis';

/* ---------- JSON yardımcıları ---------- */
if ($tab === 'kontrol' && $method === 'GET') {
    $files = ['sitemap' => 'sitemap.xml', 'robots' => 'robots.txt', 'llms' => 'llms.txt', 'md' => 'index.md', 'feed-xml' => 'feed.xml', 'feed-json' => 'feed.json', 'ics' => 'duyurular.ics'];
    $n = (string) ($_GET['ad'] ?? '');
    $set = seo_settings();
    if ($n === 'indexnow') {
        $k = (string) $set['indexnow_key'];
        if (!indexnow_key_ok($k)) seoa_json(['ok' => false, 'code' => 0, 'note' => 'Anahtar yok']);
        $r = seoa_fetch($k . '.txt');
        seoa_json(['ok' => $r['code'] === 200 && trim($r['body']) === $k, 'code' => $r['code'], 'note' => $r['code'] === 200 ? (trim($r['body']) === $k ? 'Anahtar dosyası açılıyor' : 'Dosya var ama içeriği anahtarla aynı değil') : 'Anahtar dosyası açılmıyor']);
    }
    if (!isset($files[$n])) seoa_json(['ok' => false, 'code' => 0, 'note' => 'Bilinmeyen']);
    $r = seoa_fetch($files[$n]);
    $html = stripos($r['type'], 'text/html') !== false;
    seoa_json(['ok' => $r['code'] === 200 && !$html, 'code' => $r['code'], 'bytes' => $r['bytes'], 'note' => $r['code'] === 200 && !$html ? 'Çalışıyor' : 'Henüz yok']);
}

if ($tab === 'onizleme' && $method === 'GET') {
    $kind = (string) ($_GET['tur'] ?? '');
    if ($kind === 'robots') { $path = 'robots.txt'; $lines = 80; }
    elseif ($kind === 'llms') { $path = 'llms.txt'; $lines = 40; }
    elseif ($kind === 'md') {
        $yol = trim((string) ($_GET['yol'] ?? ''), '/');
        if (!in_array($yol, seo_public_paths(), true)) seoa_json(['ok' => false, 'note' => 'Sayfa bulunamadı.']);
        $path = seo_md_url($yol, false);
        $path = ltrim(substr($path, strlen(base_path())), '/');
        $lines = 60;
    } else seoa_json(['ok' => false, 'note' => 'Bilinmeyen']);
    $r = seoa_fetch($path);
    $html = stripos($r['type'], 'text/html') !== false;
    if ($r['code'] !== 200 || $html) {
        seoa_json(['ok' => false, 'note' => $r['code'] === 0 ? 'Sunucu şu an yanıt vermedi (3 saniye bekledik).' : 'Bu adres henüz yayında değil.']);
    }
    $all = preg_split('/\R/u', rtrim($r['body']));
    seoa_json(['ok' => true, 'text' => implode("\n", array_slice($all, 0, $lines)), 'total' => count($all), 'shown' => min($lines, count($all)), 'bytes' => $r['bytes']]);
}

if (!isset($tabs[$tab])) adm_go('seo/genel-bakis');

$set    = seo_settings();
$errors = [];
$notes  = [];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $do = post_str('do', 30);

    if ($tab === 'genel-bakis') {
        $scan = seoa_scan(true);
        adm_flash('Tarama tamamlandı: ' . count($scan['pages']) . ' sayfa, ' . $scan['ms'] . ' ms.');
        adm_go('seo/genel-bakis');
    }

    if ($tab === 'ayarlar') {
        $new = $set;
        foreach (['google' => ['google-site-verification', 'Google Search Console'], 'bing' => ['msvalidate.01', 'Bing Webmaster'], 'yandex' => ['yandex-verification', 'Yandex Webmaster']] as $k => [$meta, $label]) {
            [$code, $err] = seoa_verify_code(post_str('verify_' . $k, 600), $meta, $label);
            if ($err !== '') $errors[] = $err;
            $new['verify'][$k] = $code;
        }
        $new['indexnow'] = post_bool('indexnow');
        if ($do === 'yeni-anahtar') {
            $new['indexnow_key'] = indexnow_new_key();
            $new['indexnow'] = true;
        } elseif ($new['indexnow'] && !indexnow_key_ok((string) $new['indexnow_key'])) {
            $new['indexnow_key'] = indexnow_new_key();
        }
        if (!$errors) {
            if (seoa_save($new)) {
                if ($do === 'bildir') {
                    indexnow_changed_only(indexnow_all_urls());   // bildirilen sayfaların şu anki hali "görüldü" sayılır; sonraki bildirimler yalnızca değişenleri taşır
                    $r = indexnow_submit(indexnow_all_urls(), true);
                    $msg = ['gonderildi' => 'Tüm sayfalar (' . $r['sent'] . ') Bing, Yandex ve diğer arama motorlarına bildirildi.', 'yerel' => 'Bu bir yerel ortam olduğu için hiçbir şey gönderilmedi. Canlı sitede çalışır.', 'sirada' => 'Az önce bir bildirim yapıldı; adresler sıraya alındı. Bir dakika sonra panelde yaptığınız ilk işlemde otomatik gönderilir.', 'kapali' => 'Önce IndexNow anahtarını açıp kaydedin.', 'hata' => 'Bildirim gönderilemedi: ' . ($r['message'] ?: 'bağlantı kurulamadı') . '. Günlükte ayrıntı var.'];
                    adm_flash($msg[$r['status']] ?? 'Tamamlandı.', in_array($r['status'], ['hata', 'kapali'], true) ? 'err' : 'ok');
                } elseif ($do === 'yeni-anahtar') {
                    adm_flash('Yeni IndexNow anahtarı oluşturuldu. Anahtar dosyası otomatik olarak yeni adreste yayınlanır.');
                } else {
                    adm_flash('Değişiklikler kaydedildi.');
                }
                adm_go('seo/ayarlar');
            }
            $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
        }
        $set = $new;
    }

    if ($tab === 'yapay-zeka') {
        $new = $set;
        $new['ai_search']   = post_bool('ai_search');
        $new['ai_training'] = post_bool('ai_training');
        if (seoa_save($new)) {
            adm_flash('Yapay zekâ ayarları kaydedildi. robots.txt güncellendi.');
            adm_go('seo/yapay-zeka');
        }
        $errors[] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
        $set = $new;
    }
}

/* =========================================================================
   Görünüm: ortak parçalar
   ========================================================================= */

$tabNav = '<nav class="tabs sx-tabs" aria-label="SEO bölümleri">';
foreach ($tabs as $k => $label) {
    $tabNav .= '<a class="tab' . ($k === $tab ? ' is-on' : '') . '" href="' . adm_url('seo/' . $k) . '"' . ($k === $tab ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
}
$tabNav .= '</nav>';

$probeJs = <<<'JS'
(function () {
  var dots = document.querySelectorAll('[data-probe]');
  var base = document.querySelector('[data-probe-url]');
  if (!dots.length || !base) return;
  var root = base.getAttribute('data-probe-url');
  var run = function (i) {
    if (i >= dots.length) return;
    var el = dots[i];
    var next = function () { run(i + 1); };
    fetch(root + '?ad=' + encodeURIComponent(el.getAttribute('data-probe')), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        el.classList.add(j.ok ? 'is-ok' : 'is-off');
        el.title = j.note || (j.ok ? 'Çalışıyor' : 'Bulunamadı');
        var lab = el.closest('[data-probe-row]');
        if (lab) { lab.classList.add(j.ok ? 'is-ok-live' : 'is-off-live'); var t = lab.querySelector('[data-probe-text]'); if (t) t.textContent = j.note || ''; }
        next();
      })
      .catch(function () { el.classList.add('is-off'); el.title = 'Kontrol edilemedi'; next(); });
  };
  run(0);
})();
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-copy]');
  if (!b) return;
  var text = b.getAttribute('data-copy'), lab = b.querySelector('span');
  var done = function () { if (!lab) return; var o = lab.textContent; lab.textContent = 'Kopyalandı'; b.classList.add('is-done'); setTimeout(function () { lab.textContent = o; b.classList.remove('is-done'); }, 1600); };
  if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(done, function () { fallback(); });
  else fallback();
  function fallback() { var t = document.createElement('textarea'); t.value = text; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); done(); } catch (x) {} t.remove(); }
});
JS;

$probeAnchor = '<i hidden data-probe-url="' . e(adm_url('seo/kontrol')) . '"></i>';
$content = '';

/* =========================================================================
   Sekme 1: Genel bakış
   ========================================================================= */
if ($tab === 'genel-bakis') {
    $scan  = seoa_scan();
    $pages = $scan['pages'];
    usort($pages, fn($a, $b) => [$b['err'], $b['warn']] <=> [$a['err'], $a['warn']]);   // dengede sıra korunur (PHP 8 kararlı sıralama)
    $nBad  = count(array_filter($pages, fn($p) => $p['err'] > 0));
    $nWarn = count(array_filter($pages, fn($p) => $p['err'] === 0 && $p['warn'] > 0));
    $nOk   = count($pages) - $nBad - $nWarn;
    $score = (int) $scan['score'];
    $tone  = $score >= 90 ? 'ok' : ($score >= 70 ? 'navy' : 'coral');
    $verdict = $score >= 90 ? 'Sitenizin arama motoru ayarları çok iyi durumda.' : ($score >= 70 ? 'Genel durum iyi; birkaç düzeltme sonuçları daha da güçlendirir.' : 'Düzeltilecek önemli noktalar var. Aşağıdaki listede en acil olanlar en üstte.');
    $circ  = 2 * M_PI * 54;

    $siteName = (string) cfg('name');
    $favicon  = url('assets/img/favicon.png');
    $local = function (string $abs): string {
        $root = absolute_url('');
        return str_starts_with($abs, $root) ? url(substr($abs, strlen($root))) : $abs;
    };
    $host = (string) parse_url((string) cfg('url'), PHP_URL_HOST);

    $rows = '';
    foreach ($pages as $i => $pg) {
        $st   = $pg['err'] ? 'err' : ($pg['warn'] ? 'warn' : 'ok');
        $d    = $pg['d'];
        $chips = '';
        $shown = 0;
        foreach ($pg['checks'] as $c) {
            if ($c['st'] === 'ok' || $c['chip'] === '') continue;
            if ($shown++ >= 3) continue;
            $chips .= '<span class="badge badge--' . ($c['st'] === 'err' ? 'err' : 'warn') . '">' . e($c['chip']) . '</span>';
        }
        $more = max(0, ($pg['err'] + $pg['warn']) - 3);
        if ($more) $chips .= '<span class="badge">+' . $more . '</span>';
        if (!$chips) $chips = '<span class="badge badge--ok">' . ui_icon('check-circle') . 'Sorun yok</span>';

        $list = '';
        $ordered = $pg['checks'];
        usort($ordered, fn($a, $b) => ['err' => 0, 'warn' => 1, 'ok' => 2][$a['st']] <=> ['err' => 0, 'warn' => 1, 'ok' => 2][$b['st']]);
        $isText = in_array($pg['d']['title'], [''], true);
        foreach ($ordered as $c) {
            // Başlık sorunları sayfa adının, açıklama ve yazı miktarı sorunları sayfanın kendi metinlerinin düzenlendiği yere bağlanır
            $isTitle = in_array($c['id'], ['title', 'dup_title'], true);
            $fixUrl   = $isTitle ? $pg['fix_title'] : $pg['fix'];
            $fixWhere = $isTitle ? $pg['where_title'] : $pg['where'];
            $fixable = $c['st'] !== 'ok' && $fixUrl !== '' && in_array($c['id'], ['title', 'desc', 'dup_title', 'dup_desc', 'words'], true);
            $list .= '<li class="ck ck--' . $c['st'] . '">' . ui_icon($c['st'] === 'ok' ? 'check-circle' : 'warning-circle')
                . '<div class="ck__main"><p class="ck__t"><strong>' . e($c['label']) . '</strong>' . ($c['val'] !== '' ? '<span class="ck__v">' . e(mb_strimwidth($c['val'], 0, 70, '…')) . '</span>' : '') . '</p>'
                . '<p class="ck__h">' . e($c['hint']) . ($fixable && $fixWhere ? ' <span class="ck__where">Düzenleneceği yer: ' . e($fixWhere) . '.</span>' : '') . '</p></div>'
                . ($fixable ? '<a class="btn btn--soft btn--sm" href="' . e($fixUrl) . '">Düzelt</a>' : '') . '</li>';
        }

        $desc = seoa_trunc($d['desc'], 160);
        $crumb = $host . ($pg['path'] !== '' ? ' › ' . str_replace('/', ' › ', $pg['path']) : '');
        $og = [
            'title' => $d['og_title'] !== '' ? $d['og_title'] : $d['title'],
            'desc'  => $d['og_desc'] !== '' ? $d['og_desc'] : $d['desc'],
            'img'   => $d['og_image'] !== '' ? $local($d['og_image']) : '',
        ];
        $rows .= '<details class="srow is-' . $st . '" data-st="' . $st . '"' . ($i === 0 && $st !== 'ok' ? ' open' : '') . '>'
            . '<summary class="sr__sum"><span class="sr__dot" aria-hidden="true"></span>'
            . '<span class="sr__id"><span class="sr__name">' . e($pg['name']) . '</span><span class="sr__url">' . e('/' . $pg['path']) . '</span></span>'
            . '<span class="sr__chips">' . $chips . '</span>' . ui_icon('caret-down', 'i sr__caret') . '</summary>'
            . '<div class="sr__body"><div class="sr__checks"><ul class="ck-list">' . $list . '</ul>'
            . '<p class="sr__open"><a href="' . e(url($pg['path'])) . '" target="_blank" rel="noopener">' . ui_icon('arrow-square-out') . 'Sayfayı sitede aç</a></p></div>'
            . '<div class="sr__prev">'
            . '<p class="sr__ph">Google sonucunda böyle görünür</p>'
            . '<div class="gp" data-gp>'
            . '<div class="gp__site"><span class="gp__fav"><img src="' . e($favicon) . '" alt="" width="18" height="18"></span><span class="gp__sn"><b>' . e($siteName) . '</b><cite>' . e($pg['url'] === absolute_url('') ? 'https://' . $host : 'https://' . $crumb) . '</cite></span></div>'
            . '<div class="gp__title">' . e($d['title'] !== '' ? $d['title'] : 'Başlık yok') . '</div>'
            . '<div class="gp__desc">' . e($desc !== '' ? $desc : 'Açıklama yok: Google sayfadan kendiliğinden bir parça seçer.') . '</div></div>'
            . '<p class="sr__ph">WhatsApp ve LinkedIn\'de paylaşıldığında</p>'
            . '<div class="sc"><div class="sc__img">' . ($og['img'] !== '' ? '<img src="' . e($og['img']) . '" alt="" loading="lazy">' : '<span>Görsel yok</span>') . '</div>'
            . '<div class="sc__txt"><small>' . e($host) . '</small><b>' . e($og['title'] !== '' ? $og['title'] : 'Başlık yok') . '</b><span>' . e(seoa_trunc((string) $og['desc'], 110)) . '</span></div></div>'
            . '</div></div></details>';
    }

    $content = $tabNav . $probeAnchor
        . '<section class="sx-hero" aria-label="Tarama özeti">'
        . '<div class="sx-ring sx-ring--' . $tone . '" style="--circ:' . round($circ, 1) . ';--val:' . round($circ * $score / 100, 1) . '" role="img" aria-label="Başarı oranı yüzde ' . $score . '">'
        . '<svg viewBox="0 0 128 128" aria-hidden="true"><circle class="sx-ring__track" cx="64" cy="64" r="54"/><circle class="sx-ring__bar" cx="64" cy="64" r="54"/></svg>'
        . '<span class="sx-ring__n"><b>%' . $score . '</b><small>başarı</small></span></div>'
        . '<div class="sx-hero__txt"><h2>' . e($verdict) . '</h2>'
        . '<p>Sitenizin ' . count($pages) . ' sayfası, her biri ' . ($scan['total'] ? (int) round($scan['total'] / max(1, count($pages))) : 0) . ' ayrı kontrolle tek tek incelendi; ' . $scan['total'] . ' kontrolün ' . $scan['pass'] . ' tanesi sorunsuz geçti.</p>'
        . '<p class="sx-hero__when">Son tarama: ' . e(seoa_when((int) $scan['time'])) . ' <span>(' . $scan['ms'] . ' ms)</span></p></div>'
        . '<form class="sx-hero__act" method="post" action="' . adm_url('seo/genel-bakis') . '">' . adm_csrf_field() . '<button class="btn" type="submit">' . ui_icon('arrows-clockwise') . 'Yeniden tara</button></form>'
        . '</section>'
        . '<section class="stats sx-stats" aria-label="Sayılar">'
        . '<div class="stat"><span class="stat__top">Taranan sayfa ' . ui_icon('magnifying-glass') . '</span><span class="stat__n">' . count($pages) . '</span><span class="stat__note">' . $nOk . ' sayfa tamamen temiz</span></div>'
        . '<div class="stat"><span class="stat__top">Sorun ' . ui_icon('warning-circle') . '</span><span class="stat__n' . ($scan['err'] ? ' is-err' : '') . '">' . $scan['err'] . '</span><span class="stat__note">' . $nBad . ' sayfada</span></div>'
        . '<div class="stat"><span class="stat__top">Uyarı ' . ui_icon('warning-circle') . '</span><span class="stat__n' . ($scan['warn'] ? ' is-warn' : '') . '">' . $scan['warn'] . '</span><span class="stat__note">' . ($nWarn + ($nBad ? count(array_filter($pages, fn($p) => $p['err'] > 0 && $p['warn'] > 0)) : 0)) . ' sayfada</span></div>'
        . '</section>'
        . ui_alert('<strong>Sorun</strong> arama motorlarının sayfayı bulmasını ya da anlamasını zorlaştırır; önce bunlara bakın. <strong>Uyarı</strong> sayfanın çalışmasına engel değildir ama düzeltirseniz arama sonuçlarında daha etkili görünür. Her satıra tıklayınca neyin eksik olduğunu, Google\'da nasıl göründüğünü ve nereden düzelteceğinizi görürsünüz.', 'info')
        . '<section class="card sx-list"><header class="card__head"><div><h2 class="card__title">Sayfalar</h2><p class="card__desc">Sorunlu olanlar en üstte. Tarama, sitenin sayfalarını arama motoru gibi okur; içerik değiştikçe kendiliğinden yenilenir.</p></div>'
        . '<div class="sx-seg" role="group" aria-label="Önizleme türü"><button type="button" class="is-on" data-gp-mode="d">' . ui_icon('desktop') . 'Masaüstü</button><button type="button" data-gp-mode="m">' . ui_icon('device-mobile') . 'Telefon</button></div></header>'
        . '<div class="sx-filter chips" role="group" aria-label="Süz">'
        . '<button type="button" class="chip is-on" data-f="all">Tümü <em>' . count($pages) . '</em></button>'
        . '<button type="button" class="chip" data-f="err">Sorunlu <em>' . $nBad . '</em></button>'
        . '<button type="button" class="chip" data-f="warn">Uyarılı <em>' . $nWarn . '</em></button>'
        . '<button type="button" class="chip" data-f="ok">Temiz <em>' . $nOk . '</em></button></div>'
        . '<div class="sr-list">' . $rows . '</div></section>'
        . <<<'HTML'
<script>
(function () {
  var list = document.querySelector('.sr-list');
  if (!list) return;
  document.querySelectorAll('[data-f]').forEach(function (b) {
    b.addEventListener('click', function () {
      var f = b.getAttribute('data-f');
      document.querySelectorAll('[data-f]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
      list.querySelectorAll('.srow').forEach(function (r) { r.hidden = !(f === 'all' || r.getAttribute('data-st') === f); });
    });
  });
  var mode = 'd';
  try { mode = localStorage.getItem('seo-gp') || 'd'; } catch (e) {}
  var apply = function () {
    list.classList.toggle('is-mobile', mode === 'm');
    document.querySelectorAll('[data-gp-mode]').forEach(function (b) { b.classList.toggle('is-on', b.getAttribute('data-gp-mode') === mode); });
  };
  document.querySelectorAll('[data-gp-mode]').forEach(function (b) {
    b.addEventListener('click', function () { mode = b.getAttribute('data-gp-mode'); try { localStorage.setItem('seo-gp', mode); } catch (e) {} apply(); });
  });
  apply();
})();
</script>
HTML;

    adm_layout('SEO ve yapay zekâ', $content, [
        'section'  => 'seo',
        'subtitle' => 'Sitenizin arama motorlarında ve yapay zekâ asistanlarında nasıl göründüğünü buradan izlersiniz.',
        'actions'  => ui_view_link(url('sitemap.xml'), 'Site haritası'),
    ]);
}

/* =========================================================================
   Sekme 2: Arama motorları (ayarlar)
   ========================================================================= */
if ($tab === 'ayarlar') {
    $keyOk  = indexnow_key_ok((string) $set['indexnow_key']);
    $site   = rtrim((string) cfg('url'), '/');
    $help = function (string $title, array $steps, string $tag): string {
        $li = '';
        foreach ($steps as $s) $li .= '<li>' . $s . '</li>';
        return '<details class="sx-help"><summary>' . ui_icon('caret-down', 'i sx-help__c') . e($title) . '</summary><ol>' . $li . '</ol><p class="sx-help__tag">Etiket şuna benzer: <code>' . e($tag) . '</code></p></details>';
    };

    $verify = ui_alert('Bu kodlar, siteyi Google, Bing ve Yandex\'in yönetim araçlarında kendi sitenizmiş gibi sahiplenmenizi sağlar. Kod sitenin görünmeyen kısmına eklenir; ziyaretçiler görmez. Etiketin tamamını ya da yalnızca kod değerini yapıştırabilirsiniz.', 'info')
        . '<div class="sx-vf">'
        . ui_text('verify_google', 'Google Search Console', $set['verify']['google'], ['placeholder' => 'Etiketi ya da kodu yapıştırın', 'maxlength' => 600, 'autocomplete' => 'off', 'spellcheck' => 'false'])
        . $help('Google kodunu nerede bulurum?', [
            '<a href="https://search.google.com/search-console" target="_blank" rel="noopener">search.google.com/search-console</a> adresine Google hesabınızla girin.',
            'Sol üstteki mülk listesinden "Mülk ekle" deyin; "URL öneki" kutusuna <b>' . e($site) . '</b> yazıp devam edin.',
            'Doğrulama yöntemleri arasında <b>HTML etiketi</b>\'ni seçin. Size bir <code>&lt;meta&gt;</code> satırı gösterilir.',
            'Bu satırın tamamını kopyalayıp yukarıdaki kutuya yapıştırın ve bu sayfada Kaydet\'e basın.',
            'Search Console\'a dönüp <b>Doğrula</b> düğmesine basın.',
        ], '<meta name="google-site-verification" content="...">')
        . ui_text('verify_bing', 'Bing Webmaster Tools', $set['verify']['bing'], ['placeholder' => 'Etiketi ya da kodu yapıştırın', 'maxlength' => 600, 'autocomplete' => 'off', 'spellcheck' => 'false'])
        . $help('Bing kodunu nerede bulurum?', [
            '<a href="https://www.bing.com/webmasters" target="_blank" rel="noopener">bing.com/webmasters</a> adresine Microsoft hesabınızla girin.',
            '"Siteyi ekle" deyin ve <b>' . e($site) . '</b> adresini yazın. İsterseniz Google Search Console\'dan içe aktararak kod gerektirmeden de ekleyebilirsiniz.',
            'Doğrulama için <b>HTML Meta Etiketi</b> seçeneğini seçin ve gösterilen satırı kopyalayın.',
            'Yukarıdaki kutuya yapıştırın, bu sayfada Kaydet\'e basın, sonra Bing\'de <b>Doğrula</b>\'ya tıklayın.',
        ], '<meta name="msvalidate.01" content="...">')
        . ui_text('verify_yandex', 'Yandex Webmaster', $set['verify']['yandex'], ['placeholder' => 'Etiketi ya da kodu yapıştırın', 'maxlength' => 600, 'autocomplete' => 'off', 'spellcheck' => 'false'])
        . $help('Yandex kodunu nerede bulurum?', [
            '<a href="https://webmaster.yandex.com" target="_blank" rel="noopener">webmaster.yandex.com</a> adresine Yandex hesabınızla girin.',
            '"Site ekle" deyip <b>' . e($site) . '</b> adresini yazın.',
            'Doğrulama yöntemi olarak <b>Meta etiketi</b>\'ni seçin ve gösterilen satırı kopyalayın.',
            'Yukarıdaki kutuya yapıştırın, Kaydet\'e basın, sonra Yandex\'te <b>Kontrol et</b>\'e tıklayın.',
        ], '<meta name="yandex-verification" content="...">')
        . '</div>';

    $keyBlock = '';
    if ($keyOk) {
        $kurl = absolute_url($set['indexnow_key'] . '.txt');
        $keyBlock = '<div class="sx-key"><div class="fld"><span class="fld__label">Anahtar</span><div class="sx-key__row"><code>' . e($set['indexnow_key']) . '</code>'
            . '<button class="btn btn--ghost btn--sm" type="button" data-copy="' . e($set['indexnow_key']) . '">' . ui_icon('copy') . '<span>Kopyala</span></button></div></div>'
            . '<div class="fld"><span class="fld__label">Anahtar dosyasının adresi</span><div class="sx-key__row"><code>' . e($kurl) . '</code>'
            . '<a class="btn btn--ghost btn--sm" href="' . e(url($set['indexnow_key'] . '.txt')) . '" target="_blank" rel="noopener">' . ui_icon('arrow-square-out') . 'Kontrol et</a></div>'
            . '<p class="fld__help sx-keystate" data-probe-row><i class="sx-dot" data-probe="indexnow"></i><span data-probe-text>Kontrol ediliyor</span></p></div></div>';
    }
    $local = indexnow_is_local();
    $inow = ui_toggle('indexnow', 'İçerik değişince Bing ve Yandex\'e hemen haber ver', !empty($set['indexnow']), ['help' => 'Bir sayfa, yazı ya da duyuruyu kaydettiğinizde ilgili adresler IndexNow ile arama motorlarına bildirilir; yeni içerik günler yerine dakikalar içinde taranabilir.'])
        . ($local ? ui_alert('Şu an yerel bir ortamdasınız. Bildirimler burada gönderilmez, günlüğe "yerel ortam, gönderilmedi" olarak yazılır. Canlı sitede otomatik çalışır.', 'warn') : '')
        . $keyBlock
        . '<div class="sx-btns">'
        . '<button class="btn btn--ghost btn--sm" type="submit" name="do" value="yeni-anahtar" formnovalidate data-confirm-btn="Yeni bir anahtar oluşturulsun mu? Eski anahtar dosyası yayından kalkar.">' . ui_icon('key') . ($keyOk ? 'Yeni anahtar oluştur' : 'Anahtar oluştur') . '</button>'
        . ($keyOk && !empty($set['indexnow']) ? '<button class="btn btn--soft btn--sm" type="submit" name="do" value="bildir" formnovalidate>' . ui_icon('paper-plane-tilt') . 'Tüm siteyi şimdi bildir</button>' : '')
        . '</div>'
        . ui_alert('<strong>Google IndexNow kullanmaz.</strong> IndexNow yalnızca Bing, Yandex, Seznam ve Naver içindir. Google için doğrulama kodu ve site haritası yeterlidir; aşağıda nasıl gönderileceği anlatılıyor.', 'info');

    $logRows = '';
    foreach (indexnow_log() as $l) {
        $kind = ['gonderildi' => ['ok', 'Gönderildi'], 'yerel' => ['', 'Gönderilmedi'], 'sirada' => ['navy', 'Sırada'], 'hata' => ['err', 'Hata']][$l['kind'] ?? ''] ?? ['', '—'];
        $logRows .= '<tr><td>' . e(seoa_when((int) $l['t'])) . '</td><td>' . (int) $l['n'] . ' adres</td><td><span class="badge' . ($kind[0] ? ' badge--' . $kind[0] : '') . '">' . e($kind[1]) . '</span> <span class="muted">' . e((string) ($l['msg'] ?? '')) . '</span></td></tr>';
    }
    $logBody = $logRows
        ? '<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Zaman</th><th>Adres sayısı</th><th>Sonuç</th></tr></thead><tbody>' . $logRows . '</tbody></table></div>'
        : '<div class="empty">' . ui_icon('paper-plane-tilt') . '<strong>Henüz bildirim yok</strong><span>İçerik kaydettiğinizde ya da "Tüm siteyi şimdi bildir"e bastığınızda burada listelenir.</span></div>';

    $links = seoa_copy_row('Site haritası', 'Arama motorlarına hangi sayfalarınız olduğunu listeler. Search Console\'a bu adresi verirsiniz.', absolute_url('sitemap.xml'), url('sitemap.xml'), 'sitemap')
        . seoa_copy_row('robots.txt', 'Tarayıcı botlara nereye girebileceklerini söyler; yapay zekâ ayarları da buraya yansır.', absolute_url('robots.txt'), url('robots.txt'), 'robots')
        . seoa_copy_row('llms.txt', 'Yapay zekâ asistanları için sitenin kısa, düzenli bir tanıtımı ve sayfa listesi.', absolute_url('llms.txt'), url('llms.txt'), 'llms')
        . seoa_copy_row('Haber akışı (feed.xml)', 'Okuyucu uygulamaları ve botlar duyuru ve yazılarınızı bu adresten takip eder.', absolute_url('feed.xml'), url('feed.xml'), 'feed-xml')
        . seoa_copy_row('Haber akışı (feed.json)', 'Aynı akışın yeni nesil biçimi; bazı uygulamalar bunu tercih eder.', absolute_url('feed.json'), url('feed.json'), 'feed-json')
        . seoa_copy_row('Takvim (duyurular.ics)', 'Çağrı tarihlerini telefon ve bilgisayar takvimine abonelik olarak ekler.', absolute_url('duyurular.ics'), url('duyurular.ics'), 'ics');

    $sm = '<ol class="sx-steps">'
        . '<li><b>Search Console\'a girin.</b> <a href="https://search.google.com/search-console" target="_blank" rel="noopener">search.google.com/search-console</a> ve sitenizin mülkünü seçin. Henüz yoksa önce yukarıdaki doğrulamayı yapın.</li>'
        . '<li><b>Sol menüden "Site haritaları"na tıklayın</b> (İndeksleme başlığının altında).</li>'
        . '<li><b>"Yeni site haritası ekle" kutusuna</b> <code>sitemap.xml</code> yazın. Tam adresi ' . e(absolute_url('sitemap.xml')) . ' şeklindedir; kutu site adresini kendisi ekler.</li>'
        . '<li><b>"Gönder"e basın.</b> Durum birkaç dakika içinde "Başarılı" olur; sayfalarınızın ne kadarının dizine alındığını birkaç gün sonra aynı ekrandan izleyebilirsiniz.</li>'
        . '</ol><p class="muted sx-note">Bunu yalnızca bir kez yapmanız yeterli. Site haritası içerik değiştikçe kendiliğinden güncel kalır.</p>';

    ob_start();
    if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong> ' . implode(' ', array_map('e', $errors)));
    echo $tabNav . $probeAnchor; ?>
<form id="seo-form" method="post" action="<?= adm_url('seo/ayarlar') ?>" class="sx-form" novalidate>
  <?= adm_csrf_field() ?>
  <?= ui_card('Doğrulama kodları', $verify, ['desc' => 'Siteyi arama motorlarının yönetim araçlarına bağlamak için.']) ?>
  <?= ui_card('IndexNow: yeni içeriği hemen duyurun', $inow, ['desc' => 'Bing, Yandex, Seznam ve Naver\'e, değişen sayfaları kendiniz haber vermeden bildirir.']) ?>
</form>
<?= ui_card('Son bildirimler', $logBody, ['desc' => 'En son 20 bildirim. Aynı dakikada yapılan değişiklikler tek bildirimde birleştirilir.']) ?>
<?= ui_card('Önemli adresler', '<div class="sx-links">' . $links . '</div>', ['desc' => 'Kopyala düğmesiyle canlı sitedeki adresi alırsınız; Aç düğmesi bu ortamdaki sürümü gösterir.']) ?>
<?= ui_card('Google\'a site haritası gönderme', $sm, ['desc' => 'Google, IndexNow yerine bu yolu kullanır.']) ?>
<script>
<?= $probeJs ?>
document.addEventListener('click', function (e) {
  var b = e.target.closest('[data-confirm-btn]');
  if (b && !window.confirm(b.getAttribute('data-confirm-btn'))) e.preventDefault();
});
</script>
<?php
    adm_layout('SEO ve yapay zekâ', (string) ob_get_clean(), [
        'section'  => 'seo',
        'subtitle' => 'Arama motorlarıyla bağlantılar, anında bildirim ve önemli adresler.',
        'actions'  => '<button class="btn btn--sm" type="submit" form="seo-form">' . ui_icon('floppy-disk') . 'Kaydet</button>',
        'form'     => 'seo-form',
    ]);
}

/* =========================================================================
   Sekme 3: Yapay zekâ
   ========================================================================= */
$scan  = seoa_scan();
$vset  = array_filter($set['verify']);
$items = [
    ['ld', 'Yapılandırılmış veri', $scan['ld_ok'] . ' / ' . count($scan['pages']) . ' sayfada kurum, sayfa türü ve gezinme izi makine okunur biçimde tanımlı.', $scan['ld_ok'] === count($scan['pages']) ? 'ok' : 'warn'],
    ['llms', 'llms.txt', 'Yapay zekâ asistanları için sitenin kısa tanıtımı ve sayfa listesi.', null],
    ['md', 'Markdown sürümleri', 'Her sayfanın sade metin kopyası (örn. /hizmetler.md); asistanlar sayfayı düzgün okur.', null],
    ['feed-xml', 'Haber akışı (RSS)', 'Duyurular ve yazılar takip edilebilir.', null],
    ['feed-json', 'Haber akışı (JSON)', 'Aynı akışın yeni nesil biçimi.', null],
    ['ics', 'Takvim aboneliği', 'Çağrı tarihleri takvim uygulamalarına eklenebilir.', null],
    ['robots', 'robots.txt', 'Yapay zekâ aramaları ' . (!empty($set['ai_search']) ? 'açık' : 'kapalı') . ', model eğitimi ' . (!empty($set['ai_training']) ? 'açık' : 'kapalı') . '.', null],
    ['sitemap', 'Site haritası', 'Tüm sayfalar arama motorlarına listelenir.', null],
    ['vf', 'Doğrulama kodları', count($vset) ? implode(', ', array_map(fn($k) => ['google' => 'Google', 'bing' => 'Bing', 'yandex' => 'Yandex'][$k], array_keys($vset))) . ' eklendi.' : 'Henüz kod eklenmedi. Arama motorlarında siteyi sahiplenmek için Arama motorları sekmesinden ekleyin.', count($vset) ? 'ok' : 'warn'],
    ['in', 'IndexNow bildirimi', !empty($set['indexnow']) ? 'Açık: içerik değişince Bing ve Yandex\'e haber verilir.' : 'Kapalı. Arama motorları siteyi kendi hızlarında tarar.', !empty($set['indexnow']) ? 'ok' : 'warn'],
];
$check = '';
foreach ($items as [$id, $t, $d, $st]) {
    $check .= '<div class="health__row ' . ($st === 'ok' ? 'is-ok' : ($st === 'warn' ? 'is-warn' : 'is-wait')) . '"' . ($st === null ? ' data-probe-row' : '') . '>'
        . ui_icon($st === 'warn' ? 'warning-circle' : 'check-circle')
        . '<div><p class="health__t">' . e($t) . '</p><p class="health__d">' . e($d) . '</p></div>'
        . ($st === null ? '<span class="sx-stat"><i class="sx-dot" data-probe="' . e($id) . '"></i><span data-probe-text>Kontrol ediliyor</span></span>' : '<span class="sx-stat sx-stat--' . $st . '">' . ($st === 'ok' ? 'Hazır' : 'Eksik') . '</span>')
        . '</div>';
}

$pageOpts = '';
foreach (seo_public_paths() as $p) $pageOpts .= '<option value="' . e($p) . '">' . e(seoa_page_info($p)[0]) . '</option>';

$tg = function (string $name, string $label, bool $on, string $badge, string $what, string $yes, string $no, string $bots): string {
    return '<div class="sx-ai"><div class="sx-ai__head">' . ui_toggle($name, $label, $on) . ($badge ? '<span class="badge badge--ok">' . e($badge) . '</span>' : '') . '</div>'
        . '<p class="sx-ai__what">' . $what . '</p>'
        . '<div class="sx-ai__cols"><div><h3>Açıkken</h3><p>' . $yes . '</p></div><div><h3>Kapalıyken</h3><p>' . $no . '</p></div></div>'
        . '<p class="sx-ai__bots"><span>Etkilenen botlar</span>' . $bots . '</p></div>';
};
$botsHtml = fn(array $b) => implode('', array_map(fn($x) => '<code>' . e($x) . '</code>', $b));

ob_start();
if ($errors) echo ui_alert('<strong>Kaydedilemedi.</strong> ' . implode(' ', array_map('e', $errors)));
echo $tabNav . $probeAnchor; ?>
<form id="seo-form" method="post" action="<?= adm_url('seo/yapay-zeka') ?>" class="sx-form">
  <?= adm_csrf_field() ?>
  <?= ui_card('Yapay zekâ aramaları', $tg(
      'ai_search', 'Yapay zekâ aramalarında görün (ChatGPT, Claude, Perplexity…)', !empty($set['ai_search']), 'Önerilen',
      'İnsanlar artık bir şirket ya da destek programı hakkında önce yapay zekâ asistanlarına soruyor. Bu asistanlar cevap verirken siteleri o anda ziyaret edip okur ve kaynak olarak gösterir.',
      'Sitenizin hizmetleri ve duyuruları, ilgili sorularda adınız ve bağlantınızla cevaplarda geçebilir. Bu, size doğrudan ziyaretçi getirir.',
      'Bu asistanlar sitenizi cevaplarında kullanmaz. Sorulara başka sitelerin bilgisiyle yanıt verirler; potansiyel müşterileriniz sizi bu yoldan bulamaz.',
      $botsHtml(['OAI-SearchBot', 'ChatGPT-User', 'PerplexityBot', 'Claude-SearchBot', 'Claude-User'])
  ), ['desc' => 'Soru sorulduğu anda siteyi okuyan arama ve asistan botları.']) ?>

  <?= ui_card('Model eğitimi', $tg(
      'ai_training', 'İçeriğin yapay zekâ modellerinin eğitiminde kullanılmasına izin ver', !empty($set['ai_training']), '',
      'Bazı şirketler, gelecekteki yapay zekâ modellerini eğitmek için internetteki yazıları toplar. Burası bir ticari karardır; doğru ya da yanlış cevabı yoktur.',
      'Modeller sizi, hizmetlerinizi ve uzmanlık alanınızı daha iyi tanıyabilir; ileride sorulara verdikleri cevaplar sizin anlattığınıza daha yakın olur. Karşılığında içeriğiniz, ödeme ya da bağlantı olmadan kullanılır.',
      'İçeriğiniz eğitim için toplanmaz. Eğitime bir kez girmiş içerik sonradan geri alınamadığı için bu seçenek ileriye dönük korumadır. Google aramadaki yeriniz değişmez; bu ayar yalnızca eğitim botlarını kapsar.',
      $botsHtml(['GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot'])
  ), ['desc' => 'İçeriğin gelecekteki modellerin eğitimine girmesi.']) ?>
  <p class="muted sx-note">Bu ayarlar robots.txt dosyasına yazılır. Saygılı şirketler buna uyar; bu bir istektir, teknik bir engel değildir.</p>
</form>

<?= ui_card('robots.txt şu an böyle', '<div class="sx-term" data-load="robots"><pre class="sx-pre" data-out>Yükleniyor…</pre><p class="sx-term__note muted" data-note></p></div>', [
    'desc' => 'Botların sitenizde nereye girebileceğini söyleyen dosya. Ayarları kaydettiğinizde otomatik güncellenir.',
    'actions' => '<a class="btn btn--ghost btn--sm" href="' . e(url('robots.txt')) . '" target="_blank" rel="noopener">' . ui_icon('arrow-square-out') . 'Aç</a>',
]) ?>

<?= ui_card('Yapay zekâ asistanları sitenizi nasıl görüyor', '<div class="sx-see">'
    . '<div class="sx-term" data-load="llms"><div class="sx-term__head"><strong>llms.txt</strong><span class="muted">Asistanların ilk okuduğu kısa tanıtım</span></div><pre class="sx-pre" data-out>Yükleniyor…</pre><p class="sx-term__note muted" data-note></p></div>'
    . '<div class="sx-term" data-load="md"><div class="sx-term__head"><strong>Sayfanın Markdown sürümü</strong><div class="sel"><select class="inp" data-md-page aria-label="Sayfa seçin">' . $pageOpts . '</select></div></div><pre class="sx-pre" data-out>Yükleniyor…</pre><p class="sx-term__note muted" data-note></p></div>'
    . '</div>', ['desc' => 'Asistanlar sayfalarınızı tasarımıyla değil, bu sade metinle okur.']) ?>

<?= ui_card('Neler hazır?', '<div class="health sx-check">' . $check . '</div>', ['desc' => 'Durumlar bu sayfa açılırken canlı olarak kontrol edilir.']) ?>

<script>
<?= $probeJs ?>
(function () {
  var base = <?= json_encode(adm_url('seo/onizleme'), JSON_UNESCAPED_SLASHES) ?>;
  var load = function (box, q) {
    var out = box.querySelector('[data-out]'), note = box.querySelector('[data-note]');
    out.textContent = 'Yükleniyor…'; note.textContent = '';
    fetch(base + '?' + q, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.ok) { out.textContent = ''; out.classList.add('is-empty'); note.textContent = j.note || 'Bu içerik şu an alınamadı.'; return; }
      out.classList.remove('is-empty');
      out.textContent = j.text;
      note.textContent = j.total > j.shown ? 'İlk ' + j.shown + ' satır gösteriliyor; toplam ' + j.total + ' satır.' : j.total + ' satırın tamamı gösteriliyor.';
    }).catch(function () { out.textContent = ''; note.textContent = 'Bu içerik şu an alınamadı.'; });
  };
  document.querySelectorAll('[data-load]').forEach(function (box) {
    var k = box.getAttribute('data-load');
    if (k === 'md') {
      var sel = box.querySelector('[data-md-page]');
      var go = function () { load(box, 'tur=md&yol=' + encodeURIComponent(sel.value)); };
      sel.addEventListener('change', go); go();
    } else load(box, 'tur=' + k);
  });
})();
</script>
<?php
adm_layout('SEO ve yapay zekâ', (string) ob_get_clean(), [
    'section'  => 'seo',
    'subtitle' => 'Yapay zekâ asistanlarının sitenizi bulması, okuması ve kaynak göstermesi için.',
    'actions'  => '<button class="btn btn--sm" type="submit" form="seo-form">' . ui_icon('floppy-disk') . 'Kaydet</button>',
    'form'     => 'seo-form',
]);
