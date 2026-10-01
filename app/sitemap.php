<?php
declare(strict_types=1);

header('Content-Type: application/xml; charset=utf-8');

$paths = ['', 'hakkimizda', 'hizmetler', 'referans', 'blog', 'iletisim', 'haberdarol', 'hesap-numaralarimiz',
    'kurumsal/misyonumuz', 'kurumsal/vizyonumuz', 'kurumsal/mihenk-taslarimiz', 'kurumsal/cerez-politikasi', 'kurumsal/kvkk-aydinlatma-metni'];
foreach (array_keys(services()) as $slug) {
    $paths[] = 'urunler/detay/' . $slug;
}
foreach (array_keys(posts()) as $slug) {
    $paths[] = 'blog/' . $slug;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($paths as $p) {
    echo '  <url><loc>' . e(absolute_url($p)) . '</loc></url>' . "\n";
}
echo '</urlset>' . "\n";
