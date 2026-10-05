<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

// Adresler tek yerden gelir (bkz. site_public_paths): panelden kapatılan bölümlerin sayfaları, taslak yazılar ve kapalı ilanlar listede yer almaz.
// Son değişiklik zamanı dürüsttür: sayfanın görünen içeriği (başlık, açıklama, gövde) değişince güncellenir (app/agents/lib.php, agent_lastmods).
require_once APP . '/agents/lib.php';
$paths = site_public_paths();
$mods  = agent_lastmods($paths);
$latest = $mods ? max($mods) : 0;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' || ($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') {
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $latest ?: time()) . ' GMT');
    header('Cache-Control: public, max-age=3600');
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $p) {
    $t = $mods[trim($p, '/')] ?? 0;
    echo '  <url><loc>' . e(absolute_url($p)) . '</loc>' . ($t ? '<lastmod>' . date('c', $t) . '</lastmod>' : '') . '</url>' . "\n";
}
echo '</urlset>' . "\n";
