<?php
declare(strict_types=1);

/**
 * Arama motorları ve yapay zekâ ajanları için makine okunur adresler.
 * index.php tarafından çağrılır; kendi adresleri değilse sessizce döner (yönlendirici devam eder).
 *
 *   /robots.txt  /.well-known/security.txt  /{indexnow-anahtarı}.txt
 *   /llms.txt  /llms-full.txt
 *   /index.md  /{sayfa}.md  (her herkese açık sayfanın Markdown sürümü)
 *   /feed.xml  /feed.json   (takvim dosyaları /duyurular.ics ve /duyurular/{id}.ics index.php içinde ann_serve_ics ile sunulur)
 */

$__agentPath = isset($path) ? (string) $path : trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

if ($__agentPath !== '' && strpbrk($__agentPath, '.') !== false) {
    require_once __DIR__ . '/lib.php';

    switch ($__agentPath) {
        case 'robots.txt':
        case 'llms.txt':
        case 'llms-full.txt':
        case '.well-known/security.txt':
            require_once __DIR__ . '/markdown.php';
            require_once __DIR__ . '/text.php';
            if ($__agentPath === 'robots.txt') agent_serve_robots();
            elseif ($__agentPath === 'llms.txt') agent_serve_llms();
            elseif ($__agentPath === 'llms-full.txt') agent_serve_llms_full();
            else agent_serve_security();
            exit;

        case 'feed.xml':
        case 'feed.json':
            if (!feature('blog') && !feature('duyurular')) not_found();   // iki bölüm de kapalıysa akış yok
            require_once __DIR__ . '/feeds.php';
            if ($__agentPath === 'feed.xml') agent_serve_rss();
            else agent_serve_jsonfeed();
            exit;
    }

    if (str_ends_with($__agentPath, '.md') && !str_contains($__agentPath, '..')) {
        require_once __DIR__ . '/markdown.php';
        $__p = substr($__agentPath, 0, -3);
        $__p = $__p === 'index' ? '' : $__p;
        $__b = agent_md_build($__p);
        if ($__b === null || ($__p !== '' && !path_enabled($__p))) {
            agent_send("# 404: Sayfa bulunamadı\n\nBu adreste bir Markdown sayfası yok. Sitenin içerik özeti: " . absolute_url('llms.txt') . "\n",
                'text/markdown; charset=utf-8', 0, ['status' => 404]);
        }
        agent_send($__b['md'], 'text/markdown; charset=utf-8', $__b['mtime'], [
            'headers' => [
                'Link: <' . absolute_url($__p) . '>; rel="canonical"',
                'Content-Language: tr',
            ],
        ]);
    }

    // IndexNow anahtar dosyası: yalnızca panelde tanımlı anahtarın adıyla; başka .txt adresleri yönlendiriciye kalır
    if (preg_match('#^([a-zA-Z0-9-]{8,128})\.txt$#', $__agentPath)) {
        require_once __DIR__ . '/text.php';
        agent_serve_indexnow($__agentPath);
    }
}
unset($__agentPath, $__m, $__p, $__b);
