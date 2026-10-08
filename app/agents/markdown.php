<?php
declare(strict_types=1);

/**
 * Her herkese açık sayfanın Markdown sürümü.
 * Sayfa, gerçek şablonuyla üretilir (seo_render_page) ve <main> içeriği Markdown'a çevrilir: ziyaretçinin okuduğu her şey, okuduğu sırada
 * gelir; metin kaydındaki, sayfa adlarındaki ve içerik deposundaki değişiklikler kendiliğinden yansır. Çizim, süs ve etkileşim öğeleri
 * (aria-hidden, düğmeler, formlar, SVG, sayaçlar) atılır; ekran okuyucuya özel (sr-only) metinler korunur.
 * Yapı: tek H1 (sayfa adı), özet, sayfanın sırasını izleyen bölümler, İletişim bloğu ve HTML adresi.
 */

/* ---------- Küçük yardımcılar ---------- */

/** Markdown bağlantı metni içindeki köşeli parantezleri korur. */
function agent_md_link(string $text, string $url): string
{
    $text = str_replace(['[', ']'], ['\\[', '\\]'], agent_line($text));
    $url  = str_replace([' ', '(', ')'], ['%20', '%28', '%29'], $url);
    return '[' . $text . '](' . $url . ')';
}

/** Düz metindeki Markdown karakterlerini etkisizleştirir. */
function agent_md_text(string $s): string
{
    return str_replace(['\\', '*', '`', '[', ']', '<', '~', '_'], ['\\\\', '\\*', '\\`', '\\[', '\\]', '\\<', '\\~', '\\_'], $s);
}

/** Sayfadaki bir bağlantı → mutlak adres; sitenin herkese açık sayfalarına Markdown sürümü üzerinden. */
function agent_abs_href(string $href, string $pageUrl): string
{
    $href = trim($href);
    if ($href === '') return '';
    if (preg_match('#^(tel:|mailto:)#i', $href)) return $href;
    $frag = '';
    if (($i = strpos($href, '#')) !== false) {
        $frag = substr($href, $i);
        $href = substr($href, 0, $i);
    }
    if ($href === '') return seo_md_url(trim(substr($pageUrl, strlen(absolute_url())), '/')) . $frag;   // aynı sayfadaki bölüm: Markdown sürümünde
    if (preg_match('#^https?://#i', $href)) {
        $root = rtrim(absolute_url(), '/');
        if (!str_starts_with($href, $root . '/') && $href !== $root) return $href . $frag;
        $href = substr($href, strlen($root));
    }
    if ($href === '' || $href[0] === '/') {
        $bp = base_path();
        if ($bp !== '' && str_starts_with($href, $bp . '/')) $href = substr($href, strlen($bp));
        $rel = trim((string) parse_url($href, PHP_URL_PATH), '/');
        if (!path_enabled($rel)) return '';   // panelden kapatılmış bölüme bağlantı verilmez (metin olarak kalır)
        if (in_array($rel, seo_public_paths(), true)) return seo_md_url($rel) . $frag;
        return absolute_url($rel) . ($frag === '' ? '' : $frag);
    }
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $href)) return '';   // javascript: vb.
    return absolute_url($href) . $frag;
}

/* ---------- HTML → Markdown ---------- */

const AGENT_MD_SKIP_TAGS = ['script', 'style', 'noscript', 'template', 'svg', 'button', 'input', 'select', 'textarea', 'form', 'iframe', 'object', 'embed',
    'canvas', 'audio', 'video', 'img', 'picture', 'caption', 'dialog'];
/** Sayfada görünen ama makineye bilgi vermeyen (sayaç, düğme satırı, sayfa içi dizin, yazdırma notu) sınıflar. */
const AGENT_MD_SKIP_CLASSES = ['ekler', 'evrak__count', 'evrak__act', 'evrak__print-only', 'mevzuat__btn', 'skip', 'only-touch', 'only-touch-ab',
    'toast', 'kr-slip__go', 'slip__tear', 'pencil', 'yazi__share', 'yazi__copy', 'next', 'hdr', 'fih', 'ftr'];
const AGENT_MD_BLOCK_TAGS = ['p', 'div', 'section', 'article', 'header', 'footer', 'aside', 'main', 'figure', 'figcaption', 'address', 'nav', 'fieldset', 'summary'];

function agent_md_has_class(DOMElement $n, array $classes): bool
{
    $c = ' ' . preg_replace('/\s+/', ' ', $n->getAttribute('class')) . ' ';
    foreach ($classes as $k) if (str_contains($c, ' ' . $k . ' ')) return true;
    return false;
}

/** İlk alt öğenin (sınıf adıyla) düz metni; yoksa boş. */
function agent_md_cls(DOMElement $n, string $class, string $pageUrl): string
{
    $x = (new DOMXPath($n->ownerDocument))->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]', $n)->item(0);
    return $x instanceof DOMElement ? agent_line(strip_tags(agent_md_inline($x, $pageUrl))) : '';
}

function agent_md_skip(DOMElement $n): bool
{
    $tag = strtolower($n->nodeName);
    if (in_array($tag, AGENT_MD_SKIP_TAGS, true)) return true;
    if ($n->getAttribute('aria-hidden') === 'true' || $n->hasAttribute('hidden')) return true;
    if (preg_match('/display\s*:\s*none/i', $n->getAttribute('style'))) return true;
    if ($n->hasAttribute('data-md-skip')) return true;
    return agent_md_has_class($n, AGENT_MD_SKIP_CLASSES);
}

/** Sayfanın <main> öğesi: [h1 metni, Markdown gövde (h1 hariç)]. */
function agent_main_to_md(string $html, string $pageUrl): array
{
    $doc = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    $main = $doc->getElementsByTagName('main')->item(0);
    if (!$main) return ['h1' => '', 'md' => ''];
    $h1 = '';
    $first = $main->getElementsByTagName('h1')->item(0);
    if ($first instanceof DOMElement) {
        $h1 = agent_line(strip_tags(agent_md_inline($first, $pageUrl)));
        $first->parentNode->removeChild($first);   // sayfa adı H1 olarak ayrıca basılır
    }
    $md = agent_md_blocks($main, $pageUrl, "\n\n");
    $md = (string) preg_replace("/\n{3,}/", "\n\n", $md);    return ['h1' => $h1, 'md' => trim($md)];
}

/** Bir kapsayıcının çocuklarını blok blok Markdown'a çevirir. */
function agent_md_blocks(DOMNode $parent, string $pageUrl, string $sep): string
{
    $blocks = [];
    $inline = '';
    $flush  = function () use (&$blocks, &$inline) {
        $t = trim((string) preg_replace('/[ \t\r\n]+/u', ' ', str_replace("  \n", "\x01", $inline)));
        $t = trim(str_replace(["\x01 ", " \x01", "\x01"], ["\x01", "\x01", "  \n"], $t));
        if ($t !== '') $blocks[] = $t;
        $inline = '';
    };
    foreach ($parent->childNodes as $n) {
        if ($n instanceof DOMText) {
            $inline .= agent_md_text($n->nodeValue);
            continue;
        }
        if (!($n instanceof DOMElement)) continue;
        if (agent_md_skip($n)) continue;
        $tag = strtolower($n->nodeName);

        if (agent_md_has_class($n, ['leaf'])) {              // takvimin yaprağı: sıra, aşama, başlık, "biz / siz"
            $flush();
            $phase = agent_md_cls($n, 'leaf__top', $pageUrl);
            $ttl = agent_md_cls($n, 'leaf__t', $pageUrl);
            $dl = (new DOMXPath($n->ownerDocument))->query('.//dl', $n)->item(0);
            $blocks[] = '### ' . $ttl . ($phase !== '' ? ' (' . $phase . ')' : '') . ($dl instanceof DOMElement ? "\n\n" . agent_md_dl($dl, $pageUrl) : '');
            continue;
        }
        if ($tag === 'dl' && agent_md_has_class($n, ['sr-only']) && agent_md_has_class($n->parentNode instanceof DOMElement ? $n->parentNode : $n, ['mercek'])) {
            $flush();
            $b = agent_md_hero_law();
            if ($b !== '') $blocks[] = $b;
            continue;
        }
        if (agent_md_has_class($n, ['pn'])) {                // zaman çizelgesi klasörü
            $flush();
            $blocks[] = '### ' . agent_md_cls($n, 'pn__t', $pageUrl) . "\n\n" . agent_md_cls($n, 'pn__x', $pageUrl) . "\n\n*" . agent_md_cls($n, 'pn__k', $pageUrl) . ' · ' . agent_md_cls($n, 'pn__src', $pageUrl) . '*';
            continue;
        }
        if (agent_md_has_class($n, ['ek__head'])) {          // "Ek-1" + başlık tek satır
            $flush();
            $no = agent_md_cls($n, 'ek__no', $pageUrl);
            $h = agent_md_cls($n, 'ek__h', $pageUrl);
            if ($h !== '') $blocks[] = '## ' . ($no !== '' ? $no . ' · ' : '') . $h;
            continue;
        }
        if (agent_md_has_class($n, ['uygun__col'])) {        // "Uygun / Uygun değil" + açıklama
            $flush();
            $blocks[] = '- **' . agent_md_cls($n, 'uygun__k', $pageUrl) . ':** ' . agent_md_cls($n, 'uygun__t', $pageUrl);
            continue;
        }
        if (agent_md_has_class($n, ['label']) && $tag === 'p') {
            $flush();
            $t = agent_line(strip_tags(agent_md_inline($n, $pageUrl)));
            if ($t !== '') $blocks[] = '*' . $t . '*';
            continue;
        }
        if (agent_md_has_class($n, ['docmeta'])) {          // "Sayı: … · Konu: …" künye satırı
            $flush();
            $parts = [];
            foreach ($n->childNodes as $c) if ($c instanceof DOMElement && !agent_md_skip($c)) { $x = agent_line(str_replace('**', '', strip_tags(agent_md_inline($c, $pageUrl)))); if ($x !== '') $parts[] = $x; }
            if ($parts) $blocks[] = '*' . implode(' · ', $parts) . '*';
            continue;
        }
        if (agent_md_has_class($n, ['mevzuat'])) {           // kanun alıntısı + sade Türkçesi
            $flush();
            $b = agent_md_law($n, $pageUrl);
            if ($b !== '') $blocks[] = $b;
            continue;
        }
        if ($tag === 'details') {                            // soru + yanıt
            $flush();
            $sum = '';
            $rest = [];
            foreach ($n->childNodes as $c) {
                if ($c instanceof DOMElement && strtolower($c->nodeName) === 'summary') $sum = agent_line(strip_tags(agent_md_inline($c, $pageUrl)));
                else $rest[] = $c;
            }
            $wrap = $n->ownerDocument->createElement('div');
            foreach ($rest as $c) $wrap->appendChild($c->cloneNode(true));
            $ans = agent_md_blocks($wrap, $pageUrl, "\n\n");
            $blocks[] = ($sum !== '' ? '### ' . $sum . "\n\n" : '') . $ans;
            continue;
        }
        if (in_array($tag, AGENT_MD_BLOCK_TAGS, true)) {
            $flush();
            $inner = agent_md_blocks($n, $pageUrl, $sep);
            if ($inner !== '') $blocks[] = $inner;
        } elseif (preg_match('/^h([1-6])$/', $tag, $m)) {
            $flush();
            $text = agent_line(agent_md_inline($n, $pageUrl));
            if ($text !== '') $blocks[] = str_repeat('#', max(2, (int) $m[1])) . ' ' . $text;
        } elseif ($tag === 'ul' || $tag === 'ol') {
            $flush();
            $list = agent_md_list($n, $pageUrl);
            if ($list !== '') $blocks[] = $list;
        } elseif ($tag === 'dl') {
            $flush();
            $dl = agent_md_dl($n, $pageUrl);
            if ($dl !== '') $blocks[] = $dl;
        } elseif ($tag === 'table') {
            $flush();
            $tb = agent_md_table($n, $pageUrl);
            if ($tb !== '') $blocks[] = $tb;
        } elseif ($tag === 'blockquote') {
            $flush();
            $inner = agent_md_blocks($n, $pageUrl, "\n\n");
            if ($inner !== '') $blocks[] = implode("\n", array_map(fn($l) => $l === '' ? '>' : '> ' . $l, explode("\n", $inner)));
        } elseif ($tag === 'hr') {
            $flush();
            $blocks[] = '---';
        } else {
            $inline .= agent_md_inline_node($n, $pageUrl);
        }
    }
    $flush();
    return implode($sep, $blocks);
}

/** Ana sayfadaki kanun duvarı (hero_law): başlık, künye, her madde için alıntı ve sade Türkçesi. */
function agent_md_hero_law(): string
{
    $law = (array) site('hero_law');
    if (empty($law['blocks'])) return '';
    $out = ['### ' . agent_line((string) $law['title']), '*' . implode(' · ', array_map('agent_line', (array) ($law['meta'] ?? []))) . '*'];
    foreach ($law['blocks'] as $b) {
        $plain = agent_line(html_entity_decode(strip_tags((string) $b['plain']), ENT_QUOTES, 'UTF-8'));
        $out[] = '> ' . agent_md_text(agent_line(t('home.mercek.okuyucu_kanun', ['madde' => $b['ref'], 'metin' => $b['law']]))) . "\n\n" . agent_md_text(agent_line(t('home.mercek.okuyucu_sade', ['metin' => $plain])));
    }
    return implode("\n\n", $out);
}

/** "mevzuat" kartı: kaynak, kanun metni (alıntı) ve sade Türkçesi. */
function agent_md_law(DOMElement $n, string $pageUrl): string
{
    $src = $quote = $plainLabel = $plain = '';
    foreach ((new DOMXPath($n->ownerDocument))->query('.//*[contains(@class,"mevzuat__")]', $n) as $e) {
        $cls = ' ' . $e->getAttribute('class') . ' ';
        $txt = agent_line(strip_tags(agent_md_inline($e, $pageUrl)));
        if ($txt === '') continue;
        if (str_contains($cls, ' mevzuat__law ')) $quote = $txt;
        elseif (str_contains($cls, ' mevzuat__plain ')) $plain = $txt;
        elseif (str_contains($cls, ' mevzuat__src ')) { if ($quote === '' && $src === '') $src = $txt; else $plainLabel = $txt; }
    }
    $out = [];
    if ($quote !== '') $out[] = '> ' . $quote . "\n>\n> " . ($src !== '' ? '— ' . $src : '');
    elseif ($src !== '') $out[] = $src;
    if ($plain !== '') $out[] = ($plainLabel !== '' ? '**' . agent_md_text($plainLabel) . ':** ' : '') . $plain;
    return trim(implode("\n\n", $out), "> \n") === '' ? '' : rtrim(implode("\n\n", $out), "> \n");
}

function agent_md_list(DOMElement $list, string $pageUrl): string
{
    $ordered = strtolower($list->nodeName) === 'ol';
    $i = 0;
    $items = [];
    foreach ($list->childNodes as $li) {
        if (!($li instanceof DOMElement) || strtolower($li->nodeName) !== 'li' || agent_md_skip($li)) continue;
        $content = agent_md_blocks($li, $pageUrl, "\n");
        if (agent_md_has_class($li, ['usul__m'])) {   // çalışma usulü maddesi: "Madde 1" + başlık + metin
            $content = '**' . agent_md_cls($li, 'usul__t', $pageUrl) . '** (' . rtrim(agent_md_cls($li, 'usul__no', $pageUrl), ' –-') . ') ' . agent_line(strip_tags(agent_md_inline((new DOMXPath($li->ownerDocument))->query('./p[not(@class)]', $li)->item(0) ?? $li, $pageUrl)));
        }
        if ($content === '') continue;
        $i++;
        $marker = $ordered ? $i . '. ' : '- ';
        $pad = str_repeat(' ', strlen($marker));
        $lines = explode("\n", $content);
        $first = array_shift($lines);
        $first = (string) preg_replace('/^#+ (.*)$/u', '**$1**', $first);   // madde başlıkları liste içinde kalın yazı olur
        $items[] = $marker . $first . ($lines ? "\n" . implode("\n", array_map(fn($l) => $l === '' ? '' : $pad . $l, $lines)) : '');
    }
    return implode("\n", $items);
}

/** Tanım listesi: "- **terim:** tanım". */
function agent_md_dl(DOMElement $dl, string $pageUrl): string
{
    $rows = [];
    $term = null;
    $walk = function (DOMNode $parent) use (&$walk, &$rows, &$term, $pageUrl): void {
        foreach ($parent->childNodes as $c) {
            if (!($c instanceof DOMElement) || agent_md_skip($c)) continue;
            $tag = strtolower($c->nodeName);
            if ($tag === 'dt') {
                $term = agent_line(strip_tags(agent_md_inline($c, $pageUrl)));
            } elseif ($tag === 'dd') {
                $def = agent_line(agent_md_inline($c, $pageUrl));
                if ($def === '' && $term === null) continue;
                $rows[] = '- ' . ($term !== null && $term !== '' ? '**' . agent_md_text(rtrim($term, ': ')) . ':** ' : '') . $def;
                $term = null;
            } elseif ($tag === 'div') {
                $walk($c);
            }
        }
    };
    $walk($dl);
    return implode("\n", $rows);
}

function agent_md_table(DOMElement $t, string $pageUrl): string
{
    $rows = [];
    foreach ((new DOMXPath($t->ownerDocument))->query('.//tr', $t) as $tr) {
        $cells = [];
        foreach ($tr->childNodes as $c) {
            if (!($c instanceof DOMElement) || !in_array(strtolower($c->nodeName), ['td', 'th'], true) || agent_md_skip($c)) continue;
            $cells[] = str_replace('|', '\\|', agent_line(agent_md_inline($c, $pageUrl)));
        }
        if ($cells) $rows[] = $cells;
    }
    if (!$rows) return '';
    $cols = max(array_map('count', $rows));
    $line = fn(array $r) => '| ' . implode(' | ', array_pad($r, $cols, '')) . ' |';
    $out = [$line($rows[0]), '|' . str_repeat(' --- |', $cols)];
    foreach (array_slice($rows, 1) as $r) $out[] = $line($r);
    return implode("\n", $out);
}

function agent_md_inline(DOMNode $node, string $pageUrl): string
{
    $out = '';
    foreach ($node->childNodes as $c) {
        if ($c instanceof DOMText) $out .= agent_md_text($c->nodeValue);
        elseif ($c instanceof DOMElement) $out .= agent_md_inline_node($c, $pageUrl);
    }
    return $out;
}

function agent_md_inline_node(DOMElement $n, string $pageUrl): string
{
    if (agent_md_skip($n)) {
        // aria-label taşıyan görsel (ör. kurum logosu): etiketi metin olarak ver
        if ($n->getAttribute('role') === 'img' && $n->getAttribute('aria-label') !== '') return agent_md_text($n->getAttribute('aria-label'));
        return '';
    }
    $tag = strtolower($n->nodeName);
    if ($n->getAttribute('role') === 'img' && $n->getAttribute('aria-label') !== '') return agent_md_text($n->getAttribute('aria-label'));
    if ($tag === 'br') return "  \n";
    if (in_array($tag, AGENT_MD_BLOCK_TAGS, true) || in_array($tag, ['ul', 'ol', 'dl', 'table', 'blockquote', 'details', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'], true)) {
        // satır içi bağlamda (ör. bağlantının içindeki blok): metni boşlukla ayır
        return ' ' . agent_md_inline($n, $pageUrl) . ' ';
    }
    $inner = agent_md_inline($n, $pageUrl);
    if (in_array($tag, ['strong', 'b', 'em', 'i', 'mark', 's', 'del', 'strike'], true)) {
        $mark = ['strong' => '**', 'b' => '**', 'mark' => '**', 'em' => '*', 'i' => '*'][$tag] ?? '~~';
        if (trim($inner) === '') return $inner;
        preg_match('/^(\s*)(.*?)(\s*)$/su', $inner, $m);
        return $m[1] . $mark . $m[2] . $mark . $m[3];
    }
    if ($tag === 'a' && agent_md_has_class($n, ['drow__a']) === false && agent_md_cls($n, 'drow__t', $pageUrl) !== '') {   // hizmet dosyası satırı
        $href = agent_abs_href((string) $n->getAttribute('href'), $pageUrl);
        return '**' . agent_md_text(agent_md_cls($n, 'drow__tab', $pageUrl)) . '** ' . agent_md_link(agent_md_cls($n, 'drow__t', $pageUrl), $href) . ': ' . agent_md_cls($n, 'drow__d', $pageUrl);
    }
    if ($tag === 'a' && agent_md_has_class($n, ['kart']) && agent_md_cls($n, 'kart__t', $pageUrl) !== '') {   // katalog kartı
        return '**' . agent_md_text(agent_md_cls($n, 'kart__call', $pageUrl)) . '** ' . agent_md_link(agent_md_cls($n, 'kart__t', $pageUrl), agent_abs_href((string) $n->getAttribute('href'), $pageUrl)) . ': ' . agent_md_cls($n, 'kart__d', $pageUrl);
    }
    if ($tag === 'a' && agent_md_has_class($n, ['cg__a']) && $n->getAttribute('data-md-label') !== '') {   // çağrı takvimi satırı: tam cümle data-md-label özniteliğinde
        return agent_md_link($n->getAttribute('data-md-label'), agent_abs_href((string) $n->getAttribute('href'), $pageUrl));
    }
    if ($tag === 'a') {
        $href = agent_abs_href((string) $n->getAttribute('href'), $pageUrl);
        $txt  = trim($inner);
        if ($txt === '') return '';
        if ($href === '' || str_starts_with($href, 'tel:') || str_starts_with($href, 'mailto:')) return $inner;
        return agent_md_link(strip_tags($txt), $href);
    }
    // iki bitişik satır-içi öğe (ör. etiket + değer) kelime yapışmasın
    return $inner . (in_array($tag, ['span', 'time', 'small', 'label', 'em'], true) && !preg_match('/\s$/u', $inner) && $n->nextSibling instanceof DOMElement ? ' ' : '');
}

/* ---------- Belge ---------- */

function agent_md_contact_lines(): array
{
    return [
        '- ' . agent_line(preg_replace('/^Ek-\d+\s*[·.\-:]\s*/u', '', t('genel.footer.ek1'))) . ': ' . cfg('phone'),
        '- ' . agent_line(preg_replace('/^Ek-\d+\s*[·.\-:]\s*/u', '', t('genel.footer.ek2'))) . ': ' . cfg('email'),
        '- ' . agent_line(preg_replace('/^Ek-\d+\s*[·.\-:]\s*/u', '', t('genel.footer.ek3'))) . ': ' . agent_line((string) cfg('address')),
    ];
}

function agent_md_contact_block(): string
{
    $lines = agent_md_contact_lines();
    if (path_enabled('iletisim')) $lines[] = '- ' . agent_md_link(pg_name('iletisim'), seo_md_url('iletisim'));
    return '## ' . agent_line(pg_name('iletisim')) . "\n\n" . implode("\n", $lines);
}

/**
 * Sayfanın Markdown sürümü.
 * @param bool $full true: llms-full.txt için (İletişim bloğu en sonda bir kez eklenir, sayfada yinelenmez)
 * @return array{md:string, title:string, mtime:int, view:string}|null
 */
function agent_md_build(string $path, bool $full = false): ?array
{
    $path = trim($path, '/');
    $snap = agent_snapshot($path);
    if (!$snap) return null;
    $canon = absolute_url($path);
    $parts = ['# ' . agent_line($snap['title'])];
    if ($snap['desc'] !== '') $parts[] = '> ' . $snap['desc'];
    if ($snap['h1'] !== '' && agent_line($snap['h1']) !== agent_line($snap['title'])) $parts[] = '## ' . $snap['h1'];
    if (trim($snap['md']) !== '') $parts[] = trim($snap['md']);
    if (!$full && $snap['view'] !== 'iletisim') $parts[] = agent_md_contact_block();
    $parts[] = t('makine.md.html_surum') . ' ' . $canon;
    return ['md' => implode("\n\n", $parts) . "\n", 'title' => $snap['title'], 'mtime' => agent_lastmod($path), 'view' => $snap['view']];
}
