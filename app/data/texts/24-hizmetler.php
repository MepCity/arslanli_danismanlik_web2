<?php
/** Hizmetler sayfası (app/pages/hizmetler.php + assets/js/pages/services.js): dosya dolabı. Dosyaların kendi içeriği (başlık, kısa açıklama, programlar, "kimin için") Hizmetler bölümünden gelir; burası onları çevreleyen yazılardır. */
return [
    'hizmetler' => [
        'label' => 'Hizmetler (dosya dolabı)',
        'url'   => 'hizmetler',
        'icon'  => 'stack',
        'sections' => [
            '1. Başlık' => [
                'hizmetler.hero.evrak' => ['Başlığın üstündeki satır: evrak numarası', '{evrak} [kalın]{no}[/kalın]', 'rich', '{evrak} yerine sayfa etiketindeki “Evrak” sözcüğü, {no} yerine bu sayfanın evrak numarası gelir.', ['vars' => ['evrak' => '“Evrak” sözcüğü', 'no' => 'sayfanın evrak numarası'], 'need' => ['no'], 'max' => 30]],
                'hizmetler.hero.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]Hizmet alanlarımız[/kalın]', 'rich', '', ['max' => 50]],
                'hizmetler.hero.ek' => ['Başlığın üstündeki satır: ek', 'Ek: [kalın]{hizmet_sayisi} dosya[/kalın]', 'rich', '{hizmet_sayisi} yerine dosya sayısı gelir.', ['vars' => ['hizmet_sayisi'], 'need' => ['hizmet_sayisi'], 'max' => 30]],
                'hizmetler.hero.baslik' => ['Büyük başlık', 'Dosya dolabı', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 40]],
                'hizmetler.hero.giris' => ['Başlığın altındaki açıklama', 'Her destek türü için ayrı bir dosya tutuyoruz: kimler başvurabilir, hangi programlar var, sizden hangi evrak istenir. Aşağı kaydırdıkça dosyalar tek tek öne gelir. Ne yapmak istediğinizi işaretlerseniz ilgili dosyaları öne çıkarırız.', 'text'],
            ],
            '2. Ne yapmak istiyorsunuz? süzgeci' => [
                'hizmetler.dolap.etiket' => ['Dosya dolabının erişilebilirlik adı', 'Hizmet dosyaları', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'hizmetler.eleme.baslik' => ['Süzgecin başlığı', 'Ne yapmak istiyorsunuz?', 'line', '', ['max' => 40]],
                'hizmetler.eleme.ipucu' => ['Süzgecin başlığının altındaki ipucu', 'Birini işaretleyin; ilgili dosyalar öne çıkar.', 'line', '', ['max' => 80]],
                'hizmetler.eleme.sonuc' => ['Süzgecin altındaki durum cümlesi (seçim yokken)', 'Dolapta {hizmet_sayisi} dosya var. Hepsine aşağıdan ulaşabilirsiniz.', 'line', '{hizmet_sayisi} yerine dosya sayısı gelir.', ['vars' => ['hizmet_sayisi'], 'need' => ['hizmet_sayisi'], 'max' => 120]],
                'hizmetler.eleme.sonuc_tek' => ['Süzgecin altındaki durum cümlesi (tek dosya öne çıkınca)', 'Bakmanız gereken dosya: {dosyalar}.', 'line', 'Bir seçenek işaretlenince görünür. {dosyalar} yerine dosyanın bağlantısı gelir; silmeyin.', ['vars' => ['dosyalar' => 'öne çıkan dosyanın bağlantısı'], 'need' => ['dosyalar'], 'max' => 120, 'js' => true]],
                'hizmetler.eleme.sonuc_cok' => ['Süzgecin altındaki durum cümlesi (birden çok dosya öne çıkınca)', '{n} dosya öne çıktı: {dosyalar}.', 'line', 'Bir seçenek işaretlenince görünür. {n} yerine dosya sayısı, {dosyalar} yerine dosyaların bağlantıları gelir; silmeyin.', ['vars' => ['n' => 'öne çıkan dosya sayısı', 'dosyalar' => 'öne çıkan dosyaların bağlantıları'], 'need' => ['n', 'dosyalar'], 'max' => 120, 'js' => true]],
                'hizmetler.eleme.ve' => ['Durum cümlesinde dosyaları birbirine bağlayan sözcük', 've', 'line', '“A, B ve C” biçimindeki sıralamada son iki dosyanın arasına gelir.', ['max' => 12, 'js' => true]],
            ],
            '3. Dosya kartları' => [
                'hizmetler.dosya.sekme' => ['Dosya sırtındaki etiketin okunuşu', 'Dosya {no}: {baslik} dosyasını öne getir', 'line', 'Görünmez; ekran okuyucular içindir. {no} yerine dosya numarası, {baslik} yerine dosyanın adı gelir.', ['vars' => ['no' => 'dosya numarası', 'baslik' => 'dosyanın adı'], 'need' => ['no', 'baslik'], 'max' => 90]],
                'hizmetler.dosya.no_etiket' => ['Kartta dosya numarası etiketi', 'Dosya No', 'line', '', ['max' => 24]],
                'hizmetler.dosya.icindekiler' => ['Kartta program listesinin başlığı', 'Dosyada', 'line', '', ['max' => 24]],
                'hizmetler.dosya.kimin_icin' => ['Kartta “kimin için” etiketi', 'Kimin için:', 'line', '', ['max' => 24]],
                'hizmetler.dosya.ac' => ['Kartın altındaki düğme', 'Dosyayı açın', 'line', 'Hizmet dosyasının sayfasına gider.', ['max' => 30]],
                'hizmetler.cekmece.plaka' => ['Çekmecenin ön yüzündeki plaka yazısı', '{no} · {sayfa}', 'line', '{no} yerine bu sayfanın evrak numarası, {sayfa} yerine sayfanın adı gelir.', ['vars' => ['no' => 'sayfanın evrak numarası', 'sayfa' => 'sayfanın adı'], 'need' => ['no'], 'max' => 30]],
            ],
            '4. Emin değil misiniz? bölümü' => [
                'hizmetler.dsor.baslik' => ['Büyük başlık', 'Hangi dosyanın sizin olduğundan emin değil misiniz?', 'line', '', ['max' => 80]],
                'hizmetler.dsor.giris' => ['Başlığın yanındaki açıklama', 'Çoğu işletme birden fazla dosyaya girer: bir Ar-Ge projesi, aynı yıl bir makine yatırımı, ardından bir fuar. Ne yapmak istediğinizi anlatın; hangi dosyaların hangi sırayla açılacağını birlikte çıkaralım.', 'text'],
                'hizmetler.dsor.dugme' => ['Koyu düğme: ön görüşme', 'Ön görüşme isteyin', 'line', 'İletişim sayfasına gider.', ['max' => 40]],
            ],
            '5. Sayfanın sonundaki bağlantı' => [
                'hizmetler.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine sonraki sayfanın evrak numarası gelir. Bağlantının büyük yazısı sonraki sayfanın adıdır (Sayfa adları).', ['vars' => ['no' => 'sonraki sayfanın numarası'], 'need' => ['no'], 'max' => 40]],
            ],
            '6. Arama motorları' => [
                'hizmetler.seo.description' => ['Arama motoru açıklaması', 'TÜBİTAK, KOSGEB, Sanayi ve Teknoloji Bakanlığı, Ticaret Bakanlığı, AB projeleri, sınai mülkiyet, kalite belgelendirme, yatırım danışmanlığı ve yatırım kredilerinde başvuru dosyası ve yürütme.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['max' => 320]],
            ],
        ],
    ],
];
