<?php
/**
 * Hizmet dosyası sayfası (app/pages/hizmet.php + assets/js/pages/service.js): dokuz hizmetin hepsi bu şablonla açılır.
 * Burası şablonun kendi yazılarıdır; dosyanın başlığı, girişi, programları, adımları, evrak listesi, soruları ve kanun alıntısı
 * Hizmetler bölümünden gelir ve orada düzenlenir.
 */
return [
    'hizmet' => [
        'label' => 'Hizmet dosyası sayfası (şablon)',
        'url'   => 'hizmetler',
        'icon'  => 'file-text',
        'sections' => [
            '1. Dosyanın kapağı' => [
                'hizmet.kapak.no' => ['Başlığın üstündeki satır: dosya numarası', 'Dosya No [kalın]{no}[/kalın]', 'rich', '{no} yerine dosya numarası gelir.', ['vars' => ['no' => 'dosya numarası'], 'need' => ['no'], 'max' => 30]],
                'hizmet.kapak.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]{konu}[/kalın]', 'rich', '{konu} yerine dosyanın menüdeki adı gelir.', ['vars' => ['konu' => 'dosyanın kısa adı'], 'need' => ['konu'], 'max' => 40]],
                'hizmet.kapak.ek' => ['Başlığın üstündeki satır: ek', 'Ek: [kalın]{n} bölüm[/kalın]', 'rich', '{n} yerine sayfadaki bölüm sayısı gelir.', ['vars' => ['n' => 'bölüm sayısı'], 'need' => ['n'], 'max' => 30]],
                'hizmet.kapak.dugme' => ['Koyu düğme: görüşme', 'Bu dosya için görüşelim', 'line', 'İletişim sayfasına gider.', ['max' => 40]],
                'hizmet.kapak.geri' => ['Düğmenin yanındaki bağlantı', 'Dosya dolabına dönün', 'line', 'Hizmetler sayfasına gider.', ['max' => 40]],
                'hizmet.kapak.bolumler' => ['Bölüm listesinin erişilebilirlik adı', 'Dosyanın bölümleri', 'line', 'Görünmez; ekran okuyucular içindir. Sayfanın solundaki Ek-1 … Ek-5 listesini anlatır.'],
            ],
            '2. Ek numarası ve bölüm listesi' => [
                'hizmet.ek.no' => ['Bölüm numarası (sol listede ve her bölümün başında)', 'Ek-{n}', 'line', '{n} yerine bölümün sırası gelir.', ['vars' => ['n' => 'bölümün sırası'], 'need' => ['n'], 'max' => 12]],
                'hizmet.ek.liste_1' => ['Sol listede birinci bölümün adı', 'Kimin için', 'line', '', ['max' => 30]],
                'hizmet.ek.liste_2' => ['Sol listede ikinci bölümün adı', 'Programlar', 'line', '', ['max' => 30]],
                'hizmet.ek.liste_3' => ['Sol listede üçüncü bölümün adı', 'Ne yapıyoruz', 'line', '', ['max' => 30]],
                'hizmet.ek.liste_4' => ['Sol listede dördüncü bölümün adı', 'Evrak listesi', 'line', '', ['max' => 30]],
                'hizmet.ek.liste_5' => ['Sol listede beşinci bölümün adı', 'Sorular', 'line', '', ['max' => 30]],
            ],
            '3. Birinci bölüm: kimin için' => [
                'hizmet.kimin.baslik' => ['Bölüm başlığı', 'Kimin için?', 'line', '', ['max' => 40]],
                'hizmet.kimin.uygun' => ['Yeşil sütunun başlığı', 'Uygun', 'line', 'Yanında dosyanın “kimin için” yazısı görünür.', ['max' => 24]],
                'hizmet.kimin.uygun_degil' => ['Kırmızı sütunun başlığı', 'Uygun değil', 'line', 'Yanında dosyanın “uygun değil” yazısı görünür.', ['max' => 24]],
                'hizmet.mevzuat.sade_etiket' => ['Kanun kartının arka yüzündeki başlık', 'Sade Türkçesi', 'line', 'Kartın arkasında, kanun maddesinin sade anlatımının üstünde görünür.', ['max' => 30]],
                'hizmet.mevzuat.dugme_sade' => ['Kanun kartının altındaki düğme', 'Sade Türkçesi', 'line', 'Tıklanınca kart döner; düğmenin yazısı bir sonraki metne döner.', ['js' => true, 'max' => 30]],
                'hizmet.mevzuat.dugme_kanun' => ['Kanun kartının altındaki düğme (kart dönmüşken)', 'Kanun metni', 'line', 'Kart sade anlatımı gösterirken düğmenin üzerinde görünür.', ['js' => true, 'max' => 30]],
            ],
            '4. İkinci bölüm: programlar' => [
                'hizmet.programlar.baslik' => ['Bölüm başlığı', 'Dosyadaki programlar', 'line', '', ['max' => 50]],
                'hizmet.programlar.tablo' => ['Tablonun erişilebilirlik başlığı', '{baslik} kapsamındaki programlar', 'line', 'Görünmez; ekran okuyucular içindir. {baslik} yerine dosyanın adı gelir.', ['vars' => ['baslik' => 'dosyanın adı'], 'need' => ['baslik'], 'max' => 80]],
                'hizmet.programlar.sutun_sira' => ['Tabloda birinci sütunun başlığı', 'Sıra', 'line', '', ['max' => 16]],
                'hizmet.programlar.sutun_program' => ['Tabloda ikinci sütunun başlığı', 'Program', 'line', '', ['max' => 24]],
                'hizmet.programlar.sutun_ne_icin' => ['Tabloda üçüncü sütunun başlığı', 'Ne için', 'line', '', ['max' => 24]],
            ],
            '5. Üçüncü bölüm: ne yapıyoruz' => [
                'hizmet.adimlar.baslik' => ['Bölüm başlığı', 'Ne yapıyoruz?', 'line', '', ['max' => 40]],
                'hizmet.adimlar.madde' => ['Her adımın numara yazısı', 'Madde {n}', 'line', '{n} yerine adımın sırası gelir. Yanındaki çizgi ve adımın başlığı sabittir.', ['vars' => ['n' => 'adımın sırası'], 'need' => ['n'], 'max' => 20]],
            ],
            '6. Dördüncü bölüm: evrak listesi' => [
                'hizmet.evrak.baslik' => ['Bölüm başlığı', 'Sizden isteyeceğimiz evrak', 'line', '', ['max' => 50]],
                'hizmet.evrak.liste_baslik' => ['Listenin üst çubuğundaki başlık', '{baslik} · Evrak listesi', 'line', '{baslik} yerine dosyanın adı gelir.', ['vars' => ['baslik' => 'dosyanın adı'], 'need' => ['baslik'], 'max' => 70]],
                'hizmet.evrak.sayac' => ['Listenin üst çubuğundaki sayaç', '{n} / {toplam} hazır', 'line', '{n} yerine işaretlenen evrak sayısı, {toplam} yerine listedeki evrak sayısı gelir; sayı ziyaretçi işaretledikçe kendiliğinden artar.', ['vars' => ['n' => 'işaretlenen evrak sayısı', 'toplam' => 'listedeki evrak sayısı'], 'need' => ['n', 'toplam'], 'max' => 30]],
                'hizmet.evrak.not' => ['Listenin altındaki not', 'Liste programa ve açık çağrıya göre değişebilir; kesin listeyi ön görüşmede birlikte çıkarırız. İşaretleriniz yalnızca bu tarayıcıda saklanır.', 'text'],
                'hizmet.evrak.yazdir_satir' => ['Yazdırılan listenin altındaki iletişim satırı', '{firma} · {telefon} · {eposta}', 'line', 'Yalnızca listeyi yazdırınca çıkar. Firma adı, telefon ve e-posta İletişim ve şirket ayarlarından gelir.', ['vars' => ['firma', 'telefon', 'eposta'], 'max' => 80]],
                'hizmet.evrak.yazdir' => ['Listenin altındaki düğme: yazdır', 'Listeyi yazdırın', 'line', '', ['max' => 30]],
                'hizmet.evrak.temizle' => ['Listenin altındaki bağlantı: işaretleri temizle', 'İşaretleri temizleyin', 'line', '', ['max' => 30]],
                'hizmet.evrak.kase' => ['Tüm evrak işaretlenince basılan kaşe', 'EVRAK TAM', 'line', 'Kaşeye sığması için kısa tutun; büyük harfle yazın.', ['max' => 14]],
                'hizmet.evrak.tamam' => ['Tüm evrak işaretlenince çıkan bildirim', 'Evrak listeniz tamam.', 'line', '', ['js' => true, 'max' => 50]],
            ],
            '7. Beşinci bölüm: sık sorulanlar' => [
                'hizmet.sss.baslik' => ['Bölüm başlığı', 'Sık sorulanlar', 'line', 'Soruların kendisi Hizmetler bölümünden gelir.', ['max' => 40]],
            ],
            '8. Sayfanın sonundaki bağlantı' => [
                'hizmet.sonraki.etiket' => ['Sonraki dosya bağlantısının küçük yazısı', 'Sonraki dosya · {no}', 'line', '{no} yerine sonraki dosyanın numarası gelir. Bağlantının büyük yazısı sonraki dosyanın adıdır.', ['vars' => ['no' => 'sonraki dosyanın numarası'], 'need' => ['no'], 'max' => 40]],
            ],
        ],
    ],
];
