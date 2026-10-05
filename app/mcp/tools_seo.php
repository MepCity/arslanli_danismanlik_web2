<?php
declare(strict_types=1);

/**
 * MCP arama motoru (SEO) araçları: seo_tara, seo_ayarlarini_getir, seo_ayarlarini_guncelle, arama_motorlarina_bildir.
 * KOŞULLU: Aşama 3A dosyaları (app/seo.php, app/seo_scan.php, app/indexnow.php) yüklü değilse ilgili araç kayda girmez;
 * sahte sonuç üretilmez. Tarama aracı seoa_scan(), ayar ve bildirim araçları seo_settings(), indexnow_submit() ve seoa_verify_code() ister.
 */

function mcp_seo_public(): array
{
    $s = seo_settings();
    return [
        'google_dogrulama'    => (string) $s['verify']['google'],
        'bing_dogrulama'      => (string) $s['verify']['bing'],
        'yandex_dogrulama'    => (string) $s['verify']['yandex'],
        'indexnow'            => !empty($s['indexnow']),
        'indexnow_anahtari_var' => (string) $s['indexnow_key'] !== '',
        'yapay_zeka_aramasi'  => !empty($s['ai_search']),
        'yapay_zeka_egitimi'  => !empty($s['ai_training']),
        'site_haritasi'       => absolute_url('sitemap.xml'),
        'robots'              => absolute_url('robots.txt'),
    ];
}

function mcp_tools_seo(): array
{
    $RO = [true, false, true];
    $T = [];

    if (mcp_has_seo('tarama')) {
        $T[] = mcp_def('seo_tara', 'okuma', 'Arama motoru taraması',
            'Sitenin her sayfasını arama motoru (SEO) açısından denetler: başlık ve açıklama uzunluğu, ana başlık, asıl adres, paylaşım etiketleri, yapılandırılmış veri, görsel açıklamaları, yazı miktarı, tekrarlanan başlıklar. Skor, sorun ve uyarı sayısı ile sorunlu sayfaların bulguları ve nasıl düzeltileceği gelir. Tarama içerik değişmediği sürece 24 saat önbellekten gelir; yeniden=true ile zorla yenilenir (birkaç saniye sürebilir).',
            sc_obj(['yeniden' => sc_bool('true ise önbellek yok sayılır ve tarama yeniden yapılır. Varsayılan false.')]),
            $RO, function (array $a): array {
                return mcp_ok(mcp_scan_summary(seoa_scan(!empty($a['yeniden'])), true));
            });

    }

    if (mcp_has_seo('ayar')) {
        $T[] = mcp_def('seo_ayarlarini_getir', 'okuma', 'Arama motoru ayarlarını getir',
            'Arama motoru ve yapay zekâ ayarlarını getirir: Google, Bing ve Yandex doğrulama kodları, IndexNow bildiriminin açık olup olmadığı, yapay zekâ aramalarında görünme ve model eğitimine izin ayarları, site haritası ve robots.txt adresleri. Değiştirmek için seo_ayarlarini_guncelle.',
            $noArgs, $RO, function (array $a): array {
                return mcp_ok(mcp_seo_public());
            });


        $T[] = mcp_def('seo_ayarlarini_guncelle', 'ayarlar', 'Arama motoru ayarlarını güncelle',
            'Arama motoru ve yapay zekâ ayarlarını değiştirir. Yalnızca verdiğiniz alanlar değişir. Doğrulama kodları Google Search Console, Bing Webmaster ve Yandex Webmaster hesaplarından alınır (kullanıcı verir; uydurmayın). indexnow: içerik değişince Bing ve Yandex\'e anında bildirim. yapay_zeka_aramasi: ChatGPT, Claude, Perplexity gibi asistanların aramalarında görünme. yapay_zeka_egitimi: içeriğin model eğitiminde kullanılmasına izin. Son ikisi robots.txt dosyasını değiştirir ve sitenin görünürlüğünü etkiler: kullanıcıdan onay alın.',
            sc_obj([
                'google_dogrulama'   => $VERIFY('Google Search Console'),
                'bing_dogrulama'     => $VERIFY('Bing Webmaster'),
                'yandex_dogrulama'   => $VERIFY('Yandex Webmaster'),
                'indexnow'           => sc_bool('true: içerik değişince arama motorlarına anında bildir (IndexNow); false: kapalı.'),
                'indexnow_yeni_anahtar' => sc_bool('true: yeni bir IndexNow anahtarı üretir ve bildirimi açar (anahtar dosyası kendiliğinden yayınlanır).'),
                'yapay_zeka_aramasi' => sc_bool('true: yapay zekâ asistanlarının aramalarında görün; false: bu botlara robots.txt ile kapat.'),
                'yapay_zeka_egitimi' => sc_bool('true: içeriğin yapay zekâ modellerinin eğitiminde kullanılmasına izin ver; false: eğitim botlarına kapat.'),
            ]), [false, true, true], function (array $a): array {
                if (!$a) {
                    mcp_fail('Değiştirilecek bir alan verilmedi.');
                }
                require_once APP . '/indexnow.php';
                $before = mcp_seo_public();
                $new = seo_settings();
                $errors = [];
                foreach (['google' => ['google-site-verification', 'Google Search Console'], 'bing' => ['msvalidate.01', 'Bing Webmaster'], 'yandex' => ['yandex-verification', 'Yandex Webmaster']] as $k => [$meta, $label]) {
                    if (array_key_exists($k . '_dogrulama', $a)) {
                        [$code, $err] = seoa_verify_code((string) $a[$k . '_dogrulama'], $meta, $label);
                        if ($err !== '') $errors[] = $err;
                        $new['verify'][$k] = $code;
                    }
                }
                if ($errors) {
                    mcp_fail("Ayarlar kaydedilmedi:\n- " . implode("\n- ", $errors));
                }
                if (array_key_exists('indexnow', $a)) $new['indexnow'] = (bool) $a['indexnow'];
                if (array_key_exists('yapay_zeka_aramasi', $a)) $new['ai_search'] = (bool) $a['yapay_zeka_aramasi'];
                if (array_key_exists('yapay_zeka_egitimi', $a)) $new['ai_training'] = (bool) $a['yapay_zeka_egitimi'];
                if (!empty($a['indexnow_yeni_anahtar'])) {
                    $new['indexnow_key'] = indexnow_new_key();
                    $new['indexnow'] = true;
                } elseif ($new['indexnow'] && !indexnow_key_ok((string) $new['indexnow_key'])) {
                    $new['indexnow_key'] = indexnow_new_key();
                }
                if (!content_put('seo', $new)) {
                    mcp_save_failed();
                }
                $now = mcp_seo_public();
                $changed = array_keys(array_filter($now, fn($x, $k) => $x !== $before[$k], ARRAY_FILTER_USE_BOTH));
                if (!empty($a['indexnow_yeni_anahtar'])) $changed[] = 'indexnow_anahtari';
                $msg = $changed ? 'Arama motoru ayarları güncellendi: ' . implode(', ', array_unique($changed)) . '.' : 'Değişiklik yok: ayarlar zaten bu halde.';
                return mcp_ok(['mesaj' => $msg, 'degisenler' => array_values(array_unique($changed)), 'ayarlar' => $now], $msg);
            });


        $T[] = mcp_def('arama_motorlarina_bildir', 'ayarlar', 'Arama motorlarına bildir',
            'Sitenin tüm sayfalarını IndexNow ile Bing, Yandex ve diğer arama motorlarına bildirir (içerik değişince bu zaten kendiliğinden yapılır; bu araç toplu bildirimi elle tetikler). IndexNow açık olmalıdır (seo_ayarlarini_guncelle indexnow: true). Yerel geliştirme ortamında hiçbir şey gönderilmez.',
            sc_obj(), [false, false, true], function (array $a): array {
                require_once APP . '/indexnow.php';
                $r = indexnow_submit(indexnow_all_urls(), true);
                $text = ['gonderildi' => 'Tüm sayfalar (' . (int) $r['sent'] . ') arama motorlarına bildirildi.', 'yerel' => 'Bu bir yerel ortam olduğu için hiçbir şey gönderilmedi; canlı sitede çalışır.',
                    'kapali' => 'IndexNow kapalı. Önce seo_ayarlarini_guncelle ile indexnow: true yapın.', 'sirada' => 'Bildirim sıraya alındı; kısa süre sonra gönderilecek.'][$r['status']] ?? ('Bildirim gönderilemedi: ' . (string) $r['message']);
                if (in_array($r['status'], ['hata', 'kapali'], true)) {
                    mcp_fail($text);
                }
                return mcp_ok(['mesaj' => $text, 'durum' => (string) $r['status'], 'bildirilen_adres' => (int) $r['sent']], $text);
            });

    }

    return $T;
}
