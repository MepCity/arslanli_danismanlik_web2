<?php
declare(strict_types=1);

/**
 * Düz metin uç noktaları: robots.txt, security.txt, IndexNow anahtar dosyası, llms.txt, llms-full.txt
 */

/** Yapay zekâ arama ve yanıt tarayıcıları (kullanıcı sorusuna yanıt verirken siteyi okuyup alıntılar): ai_search ayarı */
function agent_ai_search_bots(): array
{
    return ['OAI-SearchBot', 'ChatGPT-User', 'PerplexityBot', 'Perplexity-User', 'Claude-SearchBot', 'Claude-User', 'DuckAssistBot', 'MistralAI-User', 'Applebot'];
}

/** Model eğitimi için içerik toplayan tarayıcılar: ai_training ayarı */
function agent_ai_training_bots(): array
{
    return ['GPTBot', 'ClaudeBot', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'meta-externalagent', 'Bytespider', 'Amazonbot', 'cohere-training-data-crawler'];
}

function agent_robots_disallows(): array
{
    return ['Disallow: /yonetim', 'Disallow: /storage/', 'Disallow: /app/', 'Disallow: /form', 'Disallow: /olc', 'Disallow: /mcp', 'Disallow: /oauth/'];
}

function agent_robots_group(array $bots, bool $allow): string
{
    $g = array_map(fn($b) => 'User-agent: ' . $b, $bots);
    if ($allow) {
        $g[] = 'Allow: /';
        foreach (agent_robots_disallows() as $d) $g[] = $d;
    } else {
        $g[] = 'Disallow: /';
    }
    return implode("\n", $g);
}

function agent_robots_body(): string
{
    $s = seo_settings();
    $out = [];
    $out[] = '# robots.txt: bu dosya yönetim panelindeki "SEO ve yapay zekâ" ayarlarından otomatik üretilir; elle düzenlenmez.';
    $out[] = '# Herkese açık tüm sayfalar taranabilir. Yönetim paneli, form uç noktaları ve sunucu klasörleri kapalıdır.';
    $out[] = '# Yapay zekâ arama ve yanıt botları ile model eğitimi botları ayrı gruplardır; panelde ayrı ayrı açılıp kapatılır.';
    $out[] = '# Yapay zekâ asistanları için site özeti: ' . absolute_url('llms.txt') . ' (her sayfanın Markdown sürümü: ' . absolute_url('index.md') . ')';
    $out[] = '';
    $out[] = '# Tüm tarayıcılar';
    $out[] = agent_robots_group(['*'], true);
    $out[] = '';
    $out[] = '# Yapay zekâ arama ve yanıt botları (ayar: ' . (!empty($s['ai_search']) ? 'açık' : 'kapalı') . ')';
    $out[] = agent_robots_group(agent_ai_search_bots(), !empty($s['ai_search']));
    $out[] = '';
    $out[] = '# Yapay zekâ model eğitimi botları (ayar: ' . (!empty($s['ai_training']) ? 'açık' : 'kapalı') . ')';
    $out[] = agent_robots_group(agent_ai_training_bots(), !empty($s['ai_training']));
    $out[] = '';
    $out[] = 'Sitemap: ' . absolute_url('sitemap.xml');
    return implode("\n", $out) . "\n";
}

function agent_serve_robots(): void
{
    $m = max(agent_source_mtime(), agent_mtime(agent_content_files(['seo'])), agent_mtime(agent_content_files(['settings'])));
    agent_send(agent_robots_body(), 'text/plain; charset=utf-8', $m);
}

/** IndexNow anahtarı geçerliyse dosya adı (anahtar.txt) için gövde; değilse null. */
function agent_indexnow_key(): ?string
{
    $k = seo_settings()['indexnow_key'] ?? '';
    return is_string($k) && preg_match('/^[a-zA-Z0-9-]{8,128}$/', $k) ? $k : null;
}

function agent_serve_indexnow(string $file): void
{
    $key = agent_indexnow_key();
    if ($key === null || !hash_equals($key . '.txt', $file)) return;   // bizim değil: yönlendirici devam eder (404)
    agent_send($key, 'text/plain; charset=utf-8', max(agent_source_mtime(), agent_mtime(agent_content_files(['seo']))));
}

function agent_serve_security(): void
{
    $expires = gmdate('Y-m-d\T00:00:00\Z', strtotime('today +364 days'));
    $body = "# Güvenlik açığı bildirimi (RFC 9116)\n"
        . 'Contact: mailto:' . cfg('email') . "\n"
        . 'Expires: ' . $expires . "\n"
        . "Preferred-Languages: tr, en\n"
        . 'Canonical: ' . absolute_url('.well-known/security.txt') . "\n";
    agent_send($body, 'text/plain; charset=utf-8', max(agent_source_mtime(), agent_today()));
}

/* ---------- llms.txt ---------- */

function agent_llms_head(): string
{
    $name = (string) cfg('name');
    $svc  = implode(', ', array_map(fn($s) => (string) $s['title'], array_values(services())));
    $quote = agent_line(t('genel.seo.description'));
    $facts = agent_line(t('makine.llms.tanitim', ['hizmetler' => $svc])) . ' Site: ' . absolute_url() . '.';
    return '# ' . $name . "\n\n> " . $quote . "\n\n" . $facts . "\n";
}

function agent_llms_link(string $path, ?string $title = null, ?string $desc = null): string
{
    if (!path_enabled($path)) return '';   // panelde kapatılan bölüm
    $m = agent_page_meta($path);
    if (!$m) return '';
    $title = $title ?? $m['title'];
    $desc  = $desc ?? $m['desc'];
    return '- ' . agent_md_link($title, seo_md_url($path)) . ($desc !== '' ? ': ' . agent_line($desc) : '');
}

function agent_llms_body(): string
{
    require_once __DIR__ . '/markdown.php';
    $o = [agent_llms_head()];

    $o[] = '## ' . pg_name('hizmetler') . "\n\n" . implode("\n", array_filter(array_map(
        fn($slug) => agent_llms_link('urunler/detay/' . $slug),
        array_keys(services())
    )));

    $corp = [];
    foreach (['hakkimizda', 'kurumsal/misyonumuz', 'kurumsal/vizyonumuz', 'kurumsal/mihenk-taslarimiz', 'hesap-numaralarimiz', 'referans'] as $p) $corp[] = agent_llms_link($p);
    $o[] = '## ' . t('makine.llms.kurumsal') . "\n\n" . implode("\n", array_filter($corp));

    // Duyurular ve akışlar: yalnızca açık bölümler
    $ann = [];
    if (feature('duyurular')) {
        $ann[] = agent_llms_link('duyurular');
        $ann[] = '- ' . agent_md_link(t('makine.llms.takvim'), absolute_url('duyurular.ics')) . ': ' . agent_line(t('makine.llms.takvim_not'));
    }
    if (feature('duyurular') || feature('blog')) {
        $ann[] = '- ' . agent_md_link(t('makine.llms.rss'), absolute_url('feed.xml')) . ': ' . agent_line(t('makine.llms.akis_not', ['bicim' => 'RSS 2.0']));
        $ann[] = '- ' . agent_md_link(t('makine.llms.json'), absolute_url('feed.json')) . ': ' . agent_line(t('makine.llms.akis_not', ['bicim' => 'JSON Feed 1.1']));
    }
    if ($ann) $o[] = '## ' . t('makine.llms.akis') . "\n\n" . implode("\n", array_filter($ann));

    if (feature('blog')) {
        $posts = [];
        foreach (array_keys(posts()) as $slug) $posts[] = agent_llms_link('blog/' . $slug);
        $o[] = '## ' . pg_name('blog') . "\n\n" . ($posts ? implode("\n", array_filter($posts)) : agent_llms_link('blog'));
    }

    // Açık pozisyonlar: yalnızca açık ilanlar (taslak, kapalı ve süresi dolmuş ilan yok); Kariyer bölümü kapalıysa hiçbiri yok
    $jobs = [];
    foreach (ilan_published() as $x) $jobs[] = agent_llms_link(ilan_path($x), (string) $x['title'], implode(', ', ilan_meta($x)) . (!empty($x['deadline']) ? '. ' . agent_line(t('ilan.fis.son_basvuru')) . ': ' . tr_date($x['deadline']) : ''));
    if (array_filter($jobs)) $o[] = '## ' . agent_line(t('kariyer.pozisyon.baslik')) . "\n\n" . implode("\n", array_filter($jobs));

    $o[] = '## ' . agent_line(pg_name('iletisim')) . "\n\n" . implode("\n", array_merge(array_filter([agent_llms_link('iletisim')]), agent_md_contact_lines()));

    $opt = [];
    foreach (['blog', 'kariyer', 'haberdarol', 'kurumsal/kvkk-aydinlatma-metni', 'kurumsal/cerez-politikasi'] as $p) $opt[] = agent_llms_link($p);
    $opt = array_values(array_filter($opt));
    $opt[] = '- ' . agent_md_link(t('makine.llms.sitemap'), absolute_url('sitemap.xml')) . ': ' . agent_line(t('makine.llms.sitemap_not'));
    $opt[] = '- ' . agent_md_link(t('makine.llms.tam'), absolute_url('llms-full.txt')) . ': ' . agent_line(t('makine.llms.tam_not'));
    $o[] = "## Optional\n\n" . implode("\n", $opt);

    return implode("\n", array_map(fn($b) => rtrim($b) . "\n", $o));
}

/** llms.txt ve llms-full.txt için ortak son değişiklik zamanı: tüm herkese açık sayfaların en yenisi */
function agent_llms_mtime(): int
{
    $m = agent_lastmods(seo_public_paths());
    return $m ? max($m) : agent_source_mtime();
}

function agent_serve_llms(): void
{
    $b = agent_llms_body();
    agent_send($b, 'text/plain; charset=utf-8', agent_llms_mtime());
}

/** llms-full.txt'deki sayfa sırası: ana sayfa, kurumsal, hizmetler, referanslar, duyurular, yazılar, iletişim, hesap, kariyer, yasal. */
function agent_full_order(): array
{
    $order = ['', 'hakkimizda', 'kurumsal/misyonumuz', 'kurumsal/vizyonumuz', 'kurumsal/mihenk-taslarimiz', 'hizmetler'];
    foreach (array_keys(services()) as $slug) $order[] = 'urunler/detay/' . $slug;
    array_push($order, 'referans', 'duyurular', 'blog');
    foreach (seo_public_paths() as $p) if (str_starts_with($p, 'blog/')) $order[] = $p;
    array_push($order, 'iletisim', 'hesap-numaralarimiz', 'kariyer');
    foreach (ilan_published() as $x) $order[] = ilan_path($x);
    array_push($order, 'haberdarol', 'kurumsal/kvkk-aydinlatma-metni', 'kurumsal/cerez-politikasi');
    return array_values(array_unique(array_filter($order, fn($p) => $p === '' || in_array($p, seo_public_paths(), true))));
}

function agent_serve_llms_full(): void
{
    require_once __DIR__ . '/markdown.php';
    $docs = [];
    $m = agent_llms_mtime();
    foreach (agent_full_order() as $p) {
        $b = agent_md_build($p, true);
        if ($b) $docs[] = rtrim($b['md']);
    }
    $head = '# ' . cfg('name') . ": tüm site içeriği\n\n> " . agent_line(t('genel.seo.description')) . "\n\n" . agent_line(t('makine.llms.tam_giris'))
        . ' (' . absolute_url('llms.txt') . ')';
    $body = $head . "\n\n---\n\n" . implode("\n\n---\n\n", $docs) . "\n\n---\n\n" . agent_md_contact_block() . "\n";
    agent_send($body, 'text/plain; charset=utf-8', $m);
}
