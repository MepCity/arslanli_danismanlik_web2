<?php
declare(strict_types=1);

/**
 * Arama motorları ve yapay zekâ ajanları için ortak katman
 * ---------------------------------------------------------------------------
 * - seo_settings(): panelin "SEO ve yapay zekâ" bölümündeki ayarlar (content 'seo')
 * - seo_meta(): sayfanın başlığı, açıklaması, asıl adresi, robots değeri ve paylaşım görseli (tek yerden)
 * - seo_head(): <head> içindeki başlık, açıklama, canonical, robots, doğrulama, paylaşım (Open Graph, Twitter) ve alternatif sürüm etiketleri
 * - seo_graph(): sayfanın tek parça JSON-LD grafiği (kurum, web sitesi, sayfa, gezinme izi + sayfaya özel düğümler)
 * - seo_md_url(): sayfanın Markdown sürümünün adresi (llms.txt kuralı: adres + ".md")
 * - seo_changed(): panelde içerik kaydedildiğinde çağrılır (IndexNow bildirimi vb.)
 *
 * Sayfa şablonları <head> için hiçbir şey basmaz; yalnızca page([...]) ile başlık, açıklama, noindex, canonical, image verir.
 * Sayfaya özel yapısal veri düğümleri burada, sayfanın kimliği ve adresinden üretilir (seo_nodes); page(['schema' => [...]]) ile
 * şablonlar ek düğüm de verebilir. Yalnızca ziyaretçinin sayfada gördüğü bilgi işaretlenir.
 * Herkese açık sayfa yolları site_public_paths() işlevindedir (app/bootstrap.php); seo_public_paths() onun takma adıdır.
 */

/** Herkese açık, dizine eklenecek sayfa yolları (site haritası, llms.txt, panel taraması): tek kaynak site_public_paths(). */
function seo_public_paths(): array
{
    return site_public_paths();
}

/**
 * Bir sayfayı ayrı bir çıktı olarak üretir (panel taraması, Markdown sürümü vb.); bulunamazsa null.
 * Gerçek istekteki sayfa durumu ve adres bozulmaz. Dönen dizi: html ve sayfanın page() değerleri.
 * @return array{html:string, page:array}|null
 */
function seo_render_page(string $path): ?array
{
    $path = trim($path, '/');
    $view = null;
    $vars = [];
    $routes = site_static_routes();
    if (isset($routes[$path])) {
        $view = $routes[$path];
    } elseif (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $path, $m) && isset(services()[$m[1]])) {
        [$view, $vars] = ['hizmet', ['slug' => $m[1], 'service' => services()[$m[1]]]];
    } elseif (preg_match('#^blog/category/([a-z0-9\-]+)$#', $path, $m) && in_array($path, seo_public_paths(), true)) {
        [$view, $vars] = ['blog', ['category' => $m[1]]];
    } elseif (preg_match('#^blog/([a-z0-9\-]+)$#', $path, $m) && isset(posts()[$m[1]])) {
        [$view, $vars] = ['yazi', ['slug' => $m[1], 'post' => posts()[$m[1]]]];
    } elseif (preg_match('#^kariyer/([a-z0-9\-]+)$#', $path, $m) && in_array($path, seo_public_paths(), true)) {
        $x = ilan_find_slug($m[1]);
        if ($x === null) {
            return null;
        }
        [$view, $vars] = ['ilan', ['ilan' => $x]];
    }
    if ($view === null || !is_file(APP . '/pages/' . $view . '.php')) {
        return null;
    }
    $save = [$_SERVER['REQUEST_URI'] ?? '/', $GLOBALS['page'], $GLOBALS['path'] ?? '', $_GET];
    $_SERVER['REQUEST_URI'] = base_path() . '/' . $path;
    $GLOBALS['page'] = $GLOBALS['page_defaults'];
    $GLOBALS['path'] = $path;
    $_GET = [];
    ob_start();
    try {
        render($view, $vars);
    } finally {
        $html = (string) ob_get_clean();
        $rendered = $GLOBALS['page'];
        [$_SERVER['REQUEST_URI'], $GLOBALS['page'], $GLOBALS['path'], $_GET] = $save;
    }
    return ['html' => $html, 'page' => $rendered];
}

/** Yalnızca HTML gerekiyorsa (eski çağrılar): bkz. seo_render_page. */
function seo_render(string $path): ?string
{
    $r = seo_render_page($path);
    return $r ? $r['html'] : null;
}

function seo_defaults(): array
{
    return [
        'verify'       => ['google' => '', 'bing' => '', 'yandex' => ''],
        'indexnow'     => false,     // içerik değişince Bing/Yandex'e anında bildir
        'indexnow_key' => '',
        'ai_search'    => true,      // ChatGPT, Claude, Perplexity gibi yapay zekâ aramalarında görünür ol
        'ai_training'  => true,      // içeriğin yapay zekâ modellerinin eğitiminde kullanılmasına izin ver
    ];
}

function seo_settings(): array
{
    $s = content_get('seo', []);
    $d = seo_defaults();
    $out = array_replace($d, is_array($s) ? array_intersect_key($s, $d) : []);
    $out['verify'] = array_replace($d['verify'], is_array($out['verify']) ? array_intersect_key($out['verify'], $d['verify']) : []);
    return $out;
}

/** Sitenin kanonik kök adresi + parça */
function seo_id(string $fragment, string $path = ''): string
{
    return absolute_url($path) . '#' . $fragment;
}

/** Sayfanın Markdown sürümü: "/" → "/index.md", "/hizmetler" → "/hizmetler.md" */
function seo_md_url(string $path, bool $absolute = true): string
{
    $path = trim($path, '/');
    $md   = ($path === '' ? 'index' : $path) . '.md';
    return $absolute ? absolute_url($md) : url($md);
}

/** Tam adres (yerel görsel yolu ya da /assets/... adresi) → mutlak adres, sorgu dizesi olmadan */
function seo_abs(string $urlOrPath): string
{
    if (preg_match('#^https?://#', $urlOrPath)) return $urlOrPath;
    $p = (string) parse_url($urlOrPath, PHP_URL_PATH);
    if (base_path() !== '' && str_starts_with($p, base_path())) $p = substr($p, strlen(base_path()));
    return absolute_url(ltrim($p, '/'));
}

/* =========================================================================
   Başlık, açıklama, asıl adres, robots, paylaşım görseli
   ========================================================================= */

/** Sayfa dizine kapalı mı? (404, bültenden ayrılma, kapalı ilan: şablon page(['noindex' => true]) der) */
function seo_noindex(array $p): bool
{
    return $p['id'] === 'notfound' || !empty($p['noindex']);
}

/**
 * Sekme başlığı. Google ~60 karakterden sonrasını keser: uzun kalırsa firma adı kısa haliyle ({firma_kisa}), o da yetmezse
 * yalnızca sayfa başlığı kullanılır (marka og:site_name ve yapısal veride zaten var). Kalıplar panelde "Sayfa metinleri" altındadır.
 */
function seo_title(array $p): string
{
    $fit = fn(string $s): bool => mb_strlen($s) <= 60;
    if ($p['title'] === '') {
        $t = t('genel.seo.title_home');
        return $fit($t) ? $t : t('genel.seo.title_home', ['firma' => (string) cfg('short_name')]);
    }
    $t = t('genel.seo.title_pattern', ['sayfa' => $p['title']]);
    if ($fit($t)) return $t;
    $short = t('genel.seo.title_pattern', ['sayfa' => $p['title'], 'firma' => (string) cfg('short_name')]);
    return $fit($short) ? $short : (string) $p['title'];
}

/** Sayfa adresinden hangi paylaşım kartının üretileceği: [tür, ad] ya da null (genel görsel kullanılır). */
function seo_og_key(array $p, string $path): ?array
{
    $path = trim($path, '/');
    if (seo_noindex($p)) return null;
    if (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $path, $m)) return ['hizmet', $m[1]];
    if (preg_match('#^blog/([a-z0-9\-]+)$#', $path, $m) && $m[1] !== 'category') return ['yazi', $m[1]];
    if (preg_match('#^kariyer/([a-z0-9\-]+)$#', $path, $m)) return ['ilan', $m[1]];
    $routes = site_static_routes();
    $base = isset($routes[$path]) ? $path : (str_starts_with($path, 'blog/') ? 'blog' : '');
    if ($base === '' && $path !== '') return null;
    return ['sayfa', $base === '' ? 'ana-sayfa' : slugify($base)];
}

/** Sayfanın paylaşım kartı (app/og.php üretir); kart içeriği değişince adres de değişir. */
function seo_og_url(array $p, string $path): string
{
    $key = seo_og_key($p, $path);
    if ($key === null) {
        return $p['image'] !== '' && !seo_noindex($p) ? seo_abs((string) $p['image']) : absolute_url('assets/img/og.jpg');
    }
    return absolute_url('og/' . $key[0] . '/' . $key[1] . '.png') . '?v=' . substr(md5($p['title'] . '|' . $p['description'] . '|' . cfg('phone') . '|' . cfg('name')), 0, 8);
}

/**
 * Sayfanın başlık bilgileri (tek kaynak).
 * canon: noindex sayfalarda boş (asıl adres etiketi basılmaz); url: paylaşım adresi (og:url).
 * @return array{title:string, desc:string, canon:string, url:string, robots:string, noindex:bool, image:string, name:string}
 */
function seo_meta(array $p, string $path): array
{
    $path    = trim($path, '/');
    $noindex = seo_noindex($p);
    $url     = $p['canonical'] ?: ($p['id'] === 'notfound' ? absolute_url() : absolute_url($path));
    return [
        'title'   => seo_title($p),
        'desc'    => $p['description'] ?: t('genel.seo.description'),
        'canon'   => $noindex ? '' : $url,
        'url'     => $url,
        'robots'  => $noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
        'noindex' => $noindex,
        'image'   => seo_og_url($p, $path),
        'name'    => $p['title'] ?: (string) cfg('name'),
    ];
}

/* =========================================================================
   Yapısal veri (JSON-LD)
   ========================================================================= */

/**
 * Kurum düğümü: tüm sayfalarda aynı @id ile tek kez tanımlanır. Her sayfada alt bilgide görünen bilgiler (ad, telefon, e-posta, adres,
 * sosyal bağlantılar) hep yer alır; kuruluş yılı, vergi numarası ve hizmet listesi yalnızca görüldükleri sayfalarda eklenir ($extra).
 * @param array<string,mixed> $extra
 */
function seo_org(array $extra = []): array
{
    $postal = ['@type' => 'PostalAddress', 'streetAddress' => (string) cfg('address'), 'addressCountry' => 'TR'];
    if (preg_match('#^(.+?),?\s*(\d{5})\s+([^/,]+?)\s*/\s*([^/,]+)$#u', trim((string) cfg('address')), $am)) {
        $postal = array_merge($postal, ['streetAddress' => trim($am[1], ' ,'), 'postalCode' => $am[2], 'addressLocality' => trim($am[3]), 'addressRegion' => trim($am[4])]);
    }
    $org = [
        '@type'        => 'ProfessionalService',
        '@id'          => seo_id('org'),
        'name'         => (string) cfg('name'),
        'url'          => absolute_url(),
        'logo'         => ['@type' => 'ImageObject', '@id' => seo_id('logo'), 'url' => absolute_url('assets/img/logo.png'), 'caption' => (string) cfg('name')],
        'image'        => ['@id' => seo_id('logo')],
        'telephone'    => (string) cfg('phone_href'),
        'email'        => (string) cfg('email'),
        'address'      => $postal,
        'hasMap'       => (string) cfg('maps_url'),
        'contactPoint' => [['@type' => 'ContactPoint', 'contactType' => 'customer service', 'telephone' => (string) cfg('phone_href'), 'email' => (string) cfg('email')]],
        'sameAs'       => array_values(array_filter((array) cfg('social'))),
    ];
    return array_filter(array_merge($org, $extra), fn($v) => $v !== '' && $v !== null && $v !== []);
}

/** Hizmet dosyalarının listesi (kurum düğümüne, hizmetlerin göründüğü sayfalarda). */
function seo_org_services(): array
{
    $offers = [];
    foreach (services() as $slug => $s) {
        $offers[] = ['@type' => 'Service', '@id' => seo_id('service', 'urunler/detay/' . $slug), 'name' => (string) $s['title'], 'url' => absolute_url('urunler/detay/' . $slug)];
    }
    return ['hasOfferCatalog' => ['@type' => 'OfferCatalog', 'name' => pg_name('hizmetler'), 'url' => absolute_url('hizmetler'), 'itemListElement' => $offers]];
}

/** Sayfa türü → schema.org WebPage alt türü */
function seo_page_type(string $id): string
{
    return [
        'about' => 'AboutPage', 'mission' => 'AboutPage', 'vision' => 'AboutPage', 'stones' => 'AboutPage',
        'contact' => 'ContactPage',
        'services' => 'CollectionPage', 'refs' => 'CollectionPage', 'blog' => 'CollectionPage', 'announcements' => 'CollectionPage', 'careers' => 'CollectionPage',
        'post' => 'WebPage',
    ][$id] ?? 'WebPage';
}

/**
 * Gezinme izi: page('crumbs') yoksa sayfa türüne göre üst sayfa eklenir. Adlar sitedeki sayfa adlarından gelir.
 * @return array<int, array{0:string,1:string}> [ad, yol]
 */
function seo_crumbs(array $p, string $path): array
{
    $path = trim($path, '/');
    if ($p['id'] === 'home') return [];
    if (!empty($p['crumbs'])) {
        $c = $p['crumbs'];
    } else {
        $parents = [
            'mission' => [pg_name('hakkimizda'), 'hakkimizda'], 'vision' => [pg_name('hakkimizda'), 'hakkimizda'], 'stones' => [pg_name('hakkimizda'), 'hakkimizda'],
            'service' => [pg_name('hizmetler'), 'hizmetler'],
            'post'    => [pg_name('blog'), 'blog'],
        ];
        $c = isset($parents[$p['id']]) ? [$parents[$p['id']]] : [];
        if ($p['id'] === 'blog' && str_starts_with($path, 'blog/category/')) $c[] = [pg_name('blog'), 'blog'];
        if ($p['id'] === 'careers' && str_starts_with($path, 'kariyer/')) $c[] = [pg_name('kariyer'), 'kariyer'];
        $last = $p['title'] ?: (string) cfg('name');
        [$reg, $exact] = pg_for_path($path);
        if ($exact && $reg) $last = $reg['label'];                       // sayfa adı menüde yazdığı gibi
        elseif ($p['id'] === 'blog' && preg_match('#^blog/category/([a-z0-9\-]+)$#', $path, $cm)) $last = seo_category_name($cm[1]);
        $c[] = [$last, $path];
    }
    return array_merge([[pg_name('home'), '']], $c);
}

/** Yazı bölümünün adı (adres parçasından): yazılardaki kategori adı, yoksa "Genel". */
function seo_category_name(string $slug): string
{
    foreach ($GLOBALS['posts'] as $x) if (slugify((string) ($x['category'] ?? 'Genel')) === $slug) return (string) $x['category'];
    return t('blog.mast.genel');
}

/** Liste sayfaları için ItemList düğümü. @param array<int, array{name:string, url:string, item?:array}> $items */
function seo_item_list(string $path, string $name, array $items): array
{
    $els = [];
    foreach (array_values($items) as $i => $it) {
        $el = ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $it['url'], 'name' => $it['name']];
        if (!empty($it['item'])) $el['item'] = $it['item'];
        $els[] = $el;
    }
    return ['@type' => 'ItemList', '@id' => absolute_url($path) . '#list', 'name' => $name, 'numberOfItems' => count($els), 'itemListElement' => $els];
}

/** Bir hizmet dosyasının yapısal verisi: hizmet, sık sorulanlar, adımlar ve evrak listesi (hepsi sayfada görünen bölümlerdir). */
function seo_service_nodes(string $slug, string $canon): array
{
    $s = services()[$slug] ?? null;
    if (!$s) return [];
    $id = seo_id('service', 'urunler/detay/' . $slug);
    $nodes = [];
    $programs = [];
    foreach ((array) $s['programs'] as [$name, $desc]) {
        $programs[] = ['@type' => 'Service', 'name' => (string) $name, 'description' => (string) $desc];
    }
    $svc = [
        '@type'       => 'Service',
        '@id'         => $id,
        'name'        => (string) $s['title'],
        'description' => (string) $s['lead'],
        'url'         => $canon,
        'provider'    => ['@id' => seo_id('org')],
        // "Kimin için": uygun işletme tarifi, sayfada "Uygun" başlığı altında görünür
        'audience'    => !empty($s['fit']) ? ['@type' => 'BusinessAudience', 'audienceType' => (string) $s['fit']] : null,
        // "Dosyadaki programlar" tablosu
        'hasOfferCatalog' => $programs ? ['@type' => 'OfferCatalog', 'name' => t('hizmet.programlar.baslik'), 'itemListElement' => $programs] : null,
        'subjectOf'   => !empty($s['faq']) ? ['@id' => $canon . '#faq'] : null,
    ];
    $nodes[] = array_filter($svc, fn($v) => $v !== null && $v !== []);

    // "Sorular": sayfadaki sık sorulanlar
    if (!empty($s['faq'])) {
        $nodes[] = [
            '@type'      => 'FAQPage',
            '@id'        => $canon . '#faq',
            'url'        => $canon,
            'inLanguage' => 'tr-TR',
            'isPartOf'   => ['@id' => $canon . '#webpage'],
            'mainEntity' => array_map(fn($f) => ['@type' => 'Question', 'name' => (string) $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => (string) $f[1]]], array_values((array) $s['faq'])),
        ];
    }

    // "Ne yapıyoruz?" (adımlar) ve "Sizden isteyeceğimiz evrak" (liste): dosyanın nasıl yürüdüğünü anlatan HowTo, gereken evrak "supply"
    if (!empty($s['steps'])) {
        $how = [
            '@type'  => 'HowTo',
            '@id'    => $canon . '#adimlar',
            'name'   => (string) $s['title'],   // dosyanın adı; adımlar "Ne yapıyoruz?" bölümünde, evrak "Sizden isteyeceğimiz evrak" bölümünde görünür
            'inLanguage' => 'tr-TR',
            'step'   => [],
            'supply' => [],
        ];
        foreach (array_values((array) $s['steps']) as $i => [$name, $text]) {
            $how['step'][] = ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => (string) $name, 'text' => (string) $text];
        }
        foreach ((array) ($s['docs'] ?? []) as $doc) {
            $how['supply'][] = ['@type' => 'HowToSupply', 'name' => (string) $doc];
        }
        $nodes[] = array_filter($how, fn($v) => $v !== []);
    }
    return $nodes;
}

/** Duyurular sayfası: her yayındaki duyuru, başvuru penceresi tarihleriyle bir çevrim içi etkinlik (Event). */
function seo_announcement_nodes(string $canon): array
{
    $items = [];
    $i = 0;
    foreach (ann_published() as $a) {
        if (!empty($a['sample']) || empty($a['events'])) continue;   // "örnek" etiketli kayıtlar gerçek çağrı değildir
        $evs = (array) $a['events'];
        usort($evs, fn($x, $y) => strcmp((string) $x['date'], (string) $y['date']));
        $start = null;
        $end = null;
        foreach ($evs as $ev) {
            if ($ev['type'] === 'baslangic' && $start === null) $start = (string) $ev['date'];
            if ($ev['type'] === 'son') $end = (string) $ev['date'];
        }
        $start = $start ?? (string) $evs[0]['date'];
        $end   = $end ?? (string) end($evs)['date'];
        $url   = $canon . '#duyuru-' . $a['id'];
        $ev = [
            '@type'       => 'Event',
            '@id'         => $url,
            'name'        => (string) $a['title'],
            'description' => (string) $a['summary'],
            'url'         => $url,
            'startDate'   => $start,
            'endDate'     => $end,
            'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
            'eventStatus' => 'https://schema.org/EventScheduled',
            'location'    => ['@type' => 'VirtualLocation', 'url' => (string) $a['link']],
            'organizer'   => !empty($a['kurum']) ? ['@type' => 'Organization', 'name' => (string) $a['kurum']] : null,
            'inLanguage'  => 'tr-TR',
        ];
        $items[] = ['@type' => 'ListItem', 'position' => ++$i, 'item' => array_filter($ev, fn($v) => $v !== null && $v !== '')];
    }
    if (!$items) return [];
    return [['@type' => 'ItemList', '@id' => $canon . '#list', 'name' => t('duyurular.hero.baslik'), 'numberOfItems' => count($items), 'itemListElement' => $items]];
}

/** İş ilanı düğümü: ilan_job_posting() + ilanda görünen talep numarası (KT-…) kimlik olarak. */
function seo_job_posting(array $x): array
{
    $n = ilan_job_posting($x);
    $n['identifier'] = ['@type' => 'PropertyValue', 'name' => (string) cfg('name'), 'value' => 'KT-' . strtoupper(substr((string) $x['id'], 0, 5))];
    return $n;
}

/**
 * Sayfaya özel düğümler (kurum, web sitesi, sayfa ve gezinme izi dışındakiler); sayfa kimliği ve adresinden üretilir.
 * @return array{0: array<int,array>, 1: array<string,mixed>, 2: array<string,mixed>} [düğümler, kurum ekleri, sayfa düğümüne eklenecekler]
 */
function seo_nodes(array $p, string $path, string $canon): array
{
    $path  = trim($path, '/');
    $nodes = [];
    $org   = [];
    $page  = [];
    switch ($p['id']) {
        case 'home':
            $org = seo_org_services();
            break;
        case 'about':
            // Sicil kartı: kuruluş yılı, vergi numarası ve ünvan sayfada görünür
            $org = ['foundingDate' => (string) cfg('founded'), 'taxID' => (string) cfg('company.tax_number'), 'legalName' => (string) cfg('name')];
            break;
        case 'services':
            $org  = seo_org_services();
            $els  = [];
            foreach (services() as $slug => $s) $els[] = ['name' => (string) $s['title'], 'url' => absolute_url('urunler/detay/' . $slug)];
            $nodes[] = seo_item_list('hizmetler', pg_name('hizmetler'), $els);
            break;
        case 'service':
            if (preg_match('#^urunler/detay/([a-z0-9\-]+)$#', $path, $m)) {
                $nodes = seo_service_nodes($m[1], $canon);
                $s = services()[$m[1]] ?? null;
                if ($s) {
                    $page['mainEntity'] = ['@id' => seo_id('service', $path)];
                    if (!empty($s['law']['source'])) $page['citation'] = ['@type' => 'Legislation', 'name' => (string) $s['law']['source']];
                }
            }
            break;
        case 'refs':
            $els = [];
            foreach (refs_map() as $slug => $name) $els[] = ['name' => (string) $name, 'url' => $canon . '#' . $slug, 'item' => ['@type' => 'Organization', 'name' => (string) $name]];
            if ($els) $nodes[] = seo_item_list('referans', pg_name('referans'), $els);
            break;
        case 'blog':
            $list = posts();
            if (preg_match('#^blog/category/([a-z0-9\-]+)$#', $path, $m)) {
                $list = array_filter($list, fn($x) => slugify((string) ($x['category'] ?? 'Genel')) === $m[1]);
            }
            $els = [];
            foreach ($list as $slug => $x) $els[] = ['name' => (string) $x['title'], 'url' => absolute_url('blog/' . $slug)];
            if ($els) $nodes[] = seo_item_list($path, isset($m[1]) ? seo_category_name($m[1]) : pg_name('blog'), $els);
            break;
        case 'post':
            if (preg_match('#^blog/([a-z0-9\-]+)$#', $path, $m) && isset(posts()[$m[1]])) {
                $x   = posts()[$m[1]];
                $img = post_image($x);
                $art = [
                    '@type'            => 'Article',
                    '@id'              => $canon . '#article',
                    'headline'         => (string) $x['title'],
                    'description'      => (string) $x['excerpt'],
                    'image'            => $img ? [seo_abs($img)] : null,
                    'datePublished'    => (string) $x['date'],
                    'articleSection'   => (string) ($x['category'] ?? ''),
                    'inLanguage'       => 'tr-TR',
                    'author'           => ['@id' => seo_id('org')],
                    'publisher'        => ['@id' => seo_id('org')],
                    'mainEntityOfPage' => ['@id' => $canon . '#webpage'],
                ];
                $nodes[] = array_filter($art, fn($v) => $v !== null && $v !== '');
                $page['mainEntity'] = ['@id' => $canon . '#article'];
            }
            break;
        case 'announcements':
            $nodes = seo_announcement_nodes($canon);
            break;
        case 'careers':
            if (preg_match('#^kariyer/([a-z0-9\-]+)$#', $path, $m)) {
                $x = ilan_public_find($m[1]);
                if ($x !== null && ilan_active($x)) {
                    $nodes[] = seo_job_posting($x);
                    $page['mainEntity'] = ['@id' => $canon . '#jobposting'];
                }
            } else {
                $els = [];
                foreach (ilan_published() as $x) $els[] = ['name' => (string) $x['title'], 'url' => absolute_url(ilan_path($x))];
                if ($els) $nodes[] = seo_item_list('kariyer', t('kariyer.pozisyon.baslik'), $els);
            }
            break;
    }
    foreach ((array) $p['schema'] as $node) {
        if (is_array($node) && $node) {
            unset($node['@context']);
            $nodes[] = $node;
        }
    }
    return [$nodes, $org, $page];
}

/** Sayfanın JSON-LD grafiği. Dizine kapalı sayfalarda (404, bültenden ayrılma, kapalı ilan) yapısal veri basılmaz: boş dizi. */
function seo_graph(array $p, string $path, string $canon, string $title, string $desc, string $image): array
{
    if (seo_noindex($p)) return [];
    $path = trim($path, '/');
    [$nodes, $orgExtra, $pageExtra] = seo_nodes($p, $path, $canon);
    $graph = [seo_org($orgExtra), [
        '@type'      => 'WebSite',
        '@id'        => seo_id('website'),
        'url'        => absolute_url(),
        'name'       => (string) cfg('name'),
        'inLanguage' => 'tr-TR',
        'publisher'  => ['@id' => seo_id('org')],
    ]];

    $crumbs = seo_crumbs($p, $path);
    $page = [
        '@type'              => seo_page_type($p['id']),
        '@id'                => $canon . '#webpage',
        'url'                => $canon,
        'name'               => $title,
        'description'        => $desc,
        'inLanguage'         => 'tr-TR',
        'isPartOf'           => ['@id' => seo_id('website')],
        'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => $image],
        'publisher'          => ['@id' => seo_id('org')],
    ];
    if (in_array($p['id'], ['home', 'about', 'mission', 'vision', 'stones', 'contact'], true)) $page['about'] = ['@id' => seo_id('org')];
    if ($crumbs) $page['breadcrumb'] = ['@id' => $canon . '#breadcrumb'];
    if ($p['id'] === 'careers' && str_starts_with($path, 'kariyer/')) $page['@type'] = 'WebPage';   // ilan sayfası bir liste değildir
    $graph[] = array_merge($page, $pageExtra);

    if ($crumbs) {
        $items = [];
        foreach ($crumbs as $i => [$name, $cpath]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => absolute_url($cpath)];
        }
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $canon . '#breadcrumb', 'itemListElement' => $items];
    }
    foreach ($nodes as $node) $graph[] = $node;
    return $graph;
}

/* =========================================================================
   <head> etiketleri
   ========================================================================= */

/**
 * <head> içindeki başlık, açıklama, asıl adres, robots, doğrulama kodları, alternatif sürümler ve paylaşım etiketleri.
 * Dizine kapalı sayfada canonical yoktur. Yapısal veri ayrı basılır (seo_jsonld).
 */
function seo_head(array $p, string $path, ?array $m = null): string
{
    $m   = $m ?? seo_meta($p, $path);
    $s   = seo_settings();
    $out = [];
    $out[] = '<title>' . e($m['title']) . '</title>';
    $out[] = '<meta name="description" content="' . e($m['desc']) . '">';
    if ($m['canon'] !== '') $out[] = '<link rel="canonical" href="' . e($m['canon']) . '">';
    $out[] = '<meta name="robots" content="' . e($m['robots']) . '">';
    foreach (['google' => 'google-site-verification', 'bing' => 'msvalidate.01', 'yandex' => 'yandex-verification'] as $k => $name) {
        $v = trim((string) ($s['verify'][$k] ?? ''));
        if ($v !== '' && preg_match('/^[A-Za-z0-9_\-:.=]{4,120}$/', $v)) $out[] = '<meta name="' . $name . '" content="' . e($v) . '">';
    }
    if (!$m['noindex']) {   // dizine kapalı sayfanın (ör. bültenden ayrılma) Markdown sürümü yoktur
        $out[] = '<link rel="alternate" type="text/markdown" href="' . e(seo_md_url($path)) . '" title="' . e(cfg('name')) . ' (Markdown)">';
    }
    if (feature('blog') || feature('duyurular')) {
        $out[] = '<link rel="alternate" type="application/rss+xml" href="' . e(absolute_url('feed.xml')) . '" title="' . e(cfg('name')) . '">';
        $out[] = '<link rel="alternate" type="application/feed+json" href="' . e(absolute_url('feed.json')) . '" title="' . e(cfg('name')) . '">';
    }
    $isPost = $p['id'] === 'post';
    $out[] = '<meta property="og:type" content="' . ($isPost ? 'article' : 'website') . '">';
    $out[] = '<meta property="og:locale" content="tr_TR">';
    $out[] = '<meta property="og:site_name" content="' . e(cfg('name')) . '">';
    $out[] = '<meta property="og:title" content="' . e($m['name']) . '">';
    $out[] = '<meta property="og:description" content="' . e($m['desc']) . '">';
    $out[] = '<meta property="og:url" content="' . e($m['url']) . '">';
    $out[] = '<meta property="og:image" content="' . e($m['image']) . '">';
    $out[] = '<meta property="og:image:type" content="' . (str_contains((string) parse_url($m['image'], PHP_URL_PATH), '.png') ? 'image/png' : 'image/jpeg') . '">';
    $out[] = '<meta property="og:image:width" content="1200">';
    $out[] = '<meta property="og:image:height" content="630">';
    $out[] = '<meta property="og:image:alt" content="' . e($m['name'] . ' | ' . cfg('name')) . '">';
    if ($isPost && preg_match('#^blog/([a-z0-9\-]+)$#', trim($path, '/'), $pm) && isset(posts()[$pm[1]])) {
        $x = posts()[$pm[1]];
        $out[] = '<meta property="article:published_time" content="' . e((string) $x['date']) . '">';
        if (!empty($x['category'])) $out[] = '<meta property="article:section" content="' . e((string) $x['category']) . '">';
    }
    $out[] = '<meta name="twitter:card" content="summary_large_image">';
    $out[] = '<meta name="twitter:title" content="' . e($m['name']) . '">';
    $out[] = '<meta name="twitter:description" content="' . e($m['desc']) . '">';
    $out[] = '<meta name="twitter:image" content="' . e($m['image']) . '">';
    return implode("\n", $out) . "\n";
}

/** Sayfanın JSON-LD betiği (dizine kapalı sayfada boş). */
function seo_jsonld(array $p, string $path, array $m): string
{
    $graph = seo_graph($p, $path, $m['url'], $m['name'], $m['desc'], $m['image']);
    if (!$graph) return '';
    return '<script type="application/ld+json">' . json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . "</script>\n";
}

/** Panelde bir içerik kaydedildiğinde çağrılır (content_put, ann_save_all, ilan_save_all). */
function seo_changed(string $key): void
{
    if (is_file(APP . '/indexnow.php')) {
        require_once APP . '/indexnow.php';
        if (function_exists('indexnow_on_change')) indexnow_on_change($key);
    }
}
