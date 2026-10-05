<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

// Adresler tek yerden gelir (bkz. site_public_paths): panelden kapatılan bölümlerin sayfaları listede yer almaz.
$paths = site_public_paths();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $p) {
    echo '  <url><loc>' . e(absolute_url($p)) . '</loc></url>' . "\n";
}
echo '</urlset>' . "\n";
