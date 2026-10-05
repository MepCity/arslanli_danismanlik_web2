<?php
/** Sayfa bulunamadı (404) ekranı (app/pages/404.php): "eksik evrak" yazısı. Üstteki sayfa etiketi için bootstrap.php not_found(). */
return [
    'notfound' => [
        'label' => 'Sayfa bulunamadı (404)',
        'url'   => 'olmayan-sayfa',
        'icon'  => 'warning-circle',
        'sections' => [
            '1. Sekme ve sayfa etiketi' => [
                'notfound.seo.title' => ['Sekme başlığı', 'Sayfa bulunamadı', 'line', 'Tarayıcı sekmesinde görünür; sayfa arama motorlarına kapalıdır.', ['max' => 60]],
                'notfound.seo.description' => ['Sayfa açıklaması', 'Aradığınız sayfa bulunamadı.', 'line', 'Arama motorlarına kapalı sayfada görünmez; yine de kayıtlıdır.', ['max' => 120]],
                'notfound.folio' => ['Üst çubuktaki sayfa etiketi', 'Eksik evrak', 'line', 'Üst çubukta “Evrak — · Eksik evrak” biçiminde görünür.', ['max' => 30]],
            ],
            '2. Yazı başlığı' => [
                'notfound.mektup.tarih' => ['Tarih etiketi', 'Tarih', 'line', '', ['max' => 16]],
                'notfound.mektup.sayi' => ['Sayı etiketi', 'Sayı', 'line', '', ['max' => 16]],
                'notfound.mektup.sayi_deger' => ['Sayı değeri', 'ARS-{yil}/404', 'line', '{yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 30]],
                'notfound.mektup.konu_etiket' => ['Konu etiketi', 'Konu:', 'line', '', ['max' => 16]],
                'notfound.mektup.konu' => ['Konu', 'Eksik evrak hk.', 'line', '', ['max' => 50]],
                'notfound.hero.baslik' => ['Büyük başlık', 'Evrak bulunamadı.', 'line', '', ['max' => 50]],
            ],
            '3. Yazının gövdesi' => [
                'notfound.govde.hitap' => ['Hitap', 'Sayın ziyaretçi,', 'line', '', ['max' => 40]],
                'notfound.govde.bir' => ['Birinci paragraf', 'Tarafımıza iletilen talebiniz incelenmiş olup talep ettiğiniz evrak dosyada bulunamamıştır.', 'text'],
                'notfound.govde.istenen' => ['“İstenen evrak” etiketi', 'İstenen evrak:', 'line', 'Yanında ziyaretçinin yazdığı adres görünür.', ['max' => 30]],
                'notfound.govde.iki' => ['İkinci paragraf', 'Adres yanlış yazılmış, sayfa kaldırılmış ya da başka bir dosyaya taşınmış olabilir. Aradığınız bilgi büyük ihtimalle aşağıdaki eklerden birindedir.', 'text'],
                'notfound.govde.kapanis' => ['Kapanış cümlesi', 'Bilgilerinize sunarız.', 'line', '', ['max' => 50]],
            ],
            '4. Ekler (öneri bağlantıları)' => [
                'notfound.ekler.etiket' => ['Ekler listesinin erişilebilirlik adı', 'Ekler', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'notfound.ekler.baslik' => ['“Ekler:” başlığı', 'Ekler:', 'line', '', ['max' => 20]],
                'notfound.ekler.ana' => ['Birinci ek: ana sayfa açıklaması', 'Kanun metni, takvim ve dosyalarımız', 'line', 'Bağlantının adı Sayfa adları listesinden gelir.', ['max' => 80]],
                'notfound.ekler.hizmetler' => ['İkinci ek: hizmetler açıklaması', '{hizmet_sayisi|Yazı} alanda hazırladığımız dosyalar', 'line', 'Bağlantının adı Sayfa adları listesinden gelir. {hizmet_sayisi|Yazı} yerine hizmet sayısı yazıyla gelir (Dokuz…).', ['vars' => ['hizmet_sayisi'], 'max' => 80]],
                'notfound.ekler.iletisim' => ['Üçüncü ek: iletişim açıklaması', 'Aradığınızı bize sorun', 'line', 'Bağlantının adı Sayfa adları listesinden gelir.', ['max' => 80]],
            ],
            '5. Kaşe' => [
                'notfound.kase.etiket' => ['Kaşenin erişilebilirlik açıklaması', 'Kaşe: Eksik evrak', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'notfound.kase.yazi' => ['Kaşenin büyük yazısı', 'EKSİK EVRAK', 'line', 'Kaşeye sığması için kısa tutun.', ['max' => 16]],
                'notfound.kase.alt' => ['Kaşenin alt satırı', 'İADE · {tarih_nokta}', 'line', '{tarih_nokta} yerine bugünün tarihi (gg.aa.yyyy) gelir.', ['vars' => ['tarih_nokta'], 'max' => 30]],
            ],
        ],
    ],
];
