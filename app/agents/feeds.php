<?php
declare(strict_types=1);

/**
 * Haber akışları (RSS 2.0, JSON Feed 1.1). Takvim (iCalendar) app/announcements.php içindedir (ann_serve_ics).
 * Yazılar ve yayındaki duyurular aynı kaynaktan okunur: posts(), ann_published().
 */

/** Göreli bağlantıları mutlak yapar (akış okuyucular sayfa adresini bilmez). */
function agent_abs_html(string $html): string
{
    $bp = base_path();
    return (string) preg_replace_callback('#\b(href|src)="(/[^"]*|\#[^"]*)"#', function ($m) use ($bp) {
        $v = $m[2];
        if ($v[0] === '#') return $m[0];
        if ($bp !== '' && str_starts_with($v, $bp . '/')) $v = substr($v, strlen($bp));
        return $m[1] . '="' . htmlspecialchars(absolute_url(ltrim($v, '/')), ENT_QUOTES, 'UTF-8') . '"';
    }, $html);
}

function agent_ts(string $s): int
{
    $t = strtotime($s);
    return $t === false ? 0 : (int) $t;
}

/**
 * Akış öğeleri, yeniden eskiye.
 * @return array<int, array<string,mixed>>
 */
function agent_feed_items(): array
{
    $items = [];
    foreach (posts() as $slug => $p) {
        $url = absolute_url('blog/' . $slug);
        $pub = agent_ts((string) $p['date'] . ' 09:00');
        $upd = !empty($p['updated']) ? agent_ts((string) $p['updated'] . ' 09:00') : $pub;
        $img = post_image($p);
        $items[] = [
            'kind'    => 'post',
            'id'      => $url,
            'url'     => $url,
            'title'   => (string) $p['title'],
            'summary' => agent_line((string) $p['excerpt']),
            'html'    => agent_abs_html((string) $p['body']),
            'pub'     => $pub,
            'upd'     => max($pub, $upd),
            'cat'     => (string) ($p['category'] ?? ''),
            'image'   => $img ? seo_abs($img) : '',
        ];
    }
    $today = date('Y-m-d');
    $types = ann_types();
    foreach (ann_published() as $a) {
        $url = absolute_url('duyurular') . '#duyuru-' . $a['id'];
        $pub = agent_ts((string) ($a['created'] ?? '')) ?: agent_mtime([ANN_FILE]);
        $upd = agent_ts((string) ($a['updated'] ?? '')) ?: $pub;
        $evs = $a['events'] ?? [];
        usort($evs, fn($x, $y) => strcmp((string) $x['date'], (string) $y['date']));
        $up = array_values(array_filter($evs, fn($e) => (string) $e['date'] >= $today));

        $html = '';
        if (!empty($a['summary'])) $html .= '<p>' . nl2br(e((string) $a['summary'])) . '</p>';
        if ($up) {
            $html .= '<p><strong>' . e(t('makine.akis.yaklasan')) . '</strong></p><ul>';
            foreach ($up as $e) {
                $html .= '<li>' . e(tr_date((string) $e['date'])) . ': ' . e($types[$e['type']] ?? t('makine.akis.bilgi')) . (!empty($e['note']) ? ' (' . e((string) $e['note']) . ')' : '') . '</li>';
            }
            $html .= '</ul>';
        }
        if (!empty($a['link'])) $html .= '<p>' . e(t('makine.akis.resmi')) . ': <a href="' . e((string) $a['link']) . '">' . e((string) $a['link']) . '</a></p>';

        $text = (string) ($a['summary'] ?? '');
        if ($up) {
            $text .= "\n" . t('makine.akis.yaklasan') . ':';
            foreach ($up as $e) $text .= "\n- " . tr_date((string) $e['date']) . ': ' . ($types[$e['type']] ?? t('makine.akis.bilgi')) . (!empty($e['note']) ? ' (' . $e['note'] . ')' : '');
        }
        if (!empty($a['link'])) $text .= "\n" . t('makine.akis.resmi') . ': ' . $a['link'];

        $items[] = [
            'kind'     => 'ann',
            'id'       => $url,
            'url'      => $url,
            'title'    => (string) $a['title'],
            'summary'  => agent_line((string) ($a['summary'] ?? '')),
            'html'     => $html,
            'text'     => trim($text),
            'pub'      => $pub,
            'upd'      => max($pub, $upd),
            'cat'      => t('makine.akis.duyuru'),
            'kurum'    => (string) ($a['kurum'] ?? ''),
            'link'     => (string) ($a['link'] ?? ''),
            'image'    => '',
        ];
    }
    usort($items, fn($x, $y) => $y['pub'] <=> $x['pub'] ?: strcmp($x['id'], $y['id']));
    return $items;
}

function agent_feed_mtime(array $items): int
{
    $m = max(agent_source_mtime(), agent_today());
    // Yaklaşan tarihler günlük değiştiği için günün başlangıcı alt sınırdır; yazı/duyuru tarihleri gelecekte olamaz.
    foreach ($items as $it) $m = max($m, min((int) $it['upd'], time()));
    return $m;
}

function agent_cdata(string $s): string
{
    return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $s) . ']]>';
}

function agent_xml(string $s): string
{
    // XML 1.0'da geçersiz denetim karakterlerini at
    $s = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s);
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/* ---------- RSS 2.0 ---------- */

function agent_serve_rss(): void
{
    $items = agent_feed_items();
    $mtime = agent_feed_mtime($items);
    $self  = absolute_url('feed.xml');
    $x = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $x .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n<channel>\n";
    $x .= '  <title>' . agent_xml(t('makine.akis.baslik')) . "</title>\n";
    $x .= '  <link>' . agent_xml(absolute_url()) . "</link>\n";
    $x .= '  <description>' . agent_xml(agent_line(t('genel.seo.description'))) . "</description>\n";
    $x .= "  <language>tr-TR</language>\n";
    $x .= '  <lastBuildDate>' . date(DATE_RSS, $mtime) . "</lastBuildDate>\n";
    $x .= '  <atom:link href="' . agent_xml($self) . '" rel="self" type="application/rss+xml"/>' . "\n";
    $x .= "  <image>\n    <url>" . agent_xml(absolute_url('assets/img/logo.png')) . "</url>\n    <title>" . agent_xml((string) cfg('name')) . "</title>\n    <link>" . agent_xml(absolute_url()) . "</link>\n  </image>\n";
    foreach ($items as $it) {
        $x .= "  <item>\n";
        $x .= '    <title>' . agent_xml($it['title']) . "</title>\n";
        $x .= '    <link>' . agent_xml($it['url']) . "</link>\n";
        $x .= '    <guid isPermaLink="' . ($it['kind'] === 'post' ? 'true' : 'false') . '">' . agent_xml($it['id']) . "</guid>\n";
        $x .= '    <pubDate>' . date(DATE_RSS, (int) $it['pub']) . "</pubDate>\n";
        if ($it['cat'] !== '') $x .= '    <category>' . agent_xml($it['cat']) . "</category>\n";
        if ($it['kind'] === 'post') {
            $x .= '    <description>' . agent_xml($it['summary']) . "</description>\n";
            $x .= '    <content:encoded>' . agent_cdata($it['html']) . "</content:encoded>\n";
        } else {
            $x .= '    <description>' . agent_cdata($it['html']) . "</description>\n";
        }
        $x .= '    <dc:creator>' . agent_xml((string) cfg('name')) . "</dc:creator>\n";
        $x .= "  </item>\n";
    }
    $x .= "</channel>\n</rss>\n";
    agent_send($x, 'application/rss+xml; charset=utf-8', $mtime);
}

/* ---------- JSON Feed 1.1 ---------- */

function agent_serve_jsonfeed(): void
{
    $items = agent_feed_items();
    $mtime = agent_feed_mtime($items);
    $out = [];
    foreach ($items as $it) {
        $o = [
            'id'             => $it['id'],
            'url'            => $it['url'],
            'title'          => $it['title'],
            'summary'        => $it['summary'],
            'content_html'   => $it['html'],
            'date_published' => date(DATE_ATOM, (int) $it['pub']),
            'date_modified'  => date(DATE_ATOM, (int) $it['upd']),
            'tags'           => array_values(array_filter([$it['cat'], $it['kind'] === 'ann' ? ($it['kurum'] ?? '') : ''])),
            'language'       => 'tr-TR',
        ];
        if ($it['kind'] === 'ann') {
            $o['content_text'] = $it['text'];
            if ($it['link'] !== '') $o['external_url'] = $it['link'];
        }
        if ($it['image'] !== '') $o['image'] = $it['image'];
        $o['tags'] = $o['tags'] ?: [];
        $out[] = $o;
    }
    $feed = [
        'version'       => 'https://jsonfeed.org/version/1.1',
        'title'         => t('makine.akis.baslik'),
        'home_page_url' => absolute_url(),
        'feed_url'      => absolute_url('feed.json'),
        'description'   => agent_line(t('genel.seo.description')),
        'icon'          => absolute_url('assets/img/apple-touch-icon.png'),
        'favicon'       => absolute_url('assets/img/favicon.png'),
        'language'      => 'tr-TR',
        'authors'       => [['name' => (string) cfg('name'), 'url' => absolute_url()]],
        'items'         => $out,
    ];
    agent_send(json_encode($feed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE) . "\n", 'application/feed+json; charset=utf-8', $mtime);
}
