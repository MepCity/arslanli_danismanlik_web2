<?php
/**
 * Duyurular sayfası (app/pages/duyurular.php): ilan panosu. Çağrıların kendisi (kurum, başlık, özet, tarihler, bağlantı, tür adları) Duyurular
 * bölümünden gelir; burada yalnızca panonun çerçevesindeki ve her ilan kâğıdındaki sabit yazılar durur. Sayfanın üstündeki çağrı takvimi
 * "Çağrı takvimi" grubundadır.
 */
return [
    'duyurular' => [
        'label' => 'Duyurular',
        'url'   => 'duyurular',
        'icon'  => 'calendar-blank',
        'sections' => [
            '1. Başlık' => [
                'duyurular.hero.baslik' => ['Büyük başlık', 'Duyurular.', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 50]],
                'duyurular.hero.giris' => ['Başlığın altındaki açıklama', 'Açılan çağrılar ve önemli tarihler. Her ilanın altında resmî kaynağına bağlantı vardır; tarihleri tek dokunuşla takviminize ekleyebilirsiniz.', 'text'],
                'duyurular.hero.takvim' => ['Başlığın altındaki takvim düğmesi', 'Tüm tarihleri takvime ekle', 'line', 'Bütün çağrıların tarihlerini tek dosyada takvim uygulamasına aktarır.', ['max' => 50]],
                'duyurular.hero.takvim_not' => ['Takvim düğmesinin yanındaki not', 'Google, Apple ya da Outlook takviminize eklenir.', 'line', '', ['max' => 80]],
            ],
            '2. Pano' => [
                'duyurular.pano.etiket' => ['Panonun erişilebilirlik adı', 'Açık çağrılar', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'duyurular.pano.bos' => ['Yayında hiç duyuru yokken görünen yazı', 'Şu anda yayında bir duyuru yok. Yeni çağrılar açıldığında burada görünür.', 'text'],
                'duyurular.pano.arsiv' => ['Süresi dolan çağrıların bölüm başlığı', 'Süresi dolanlar', 'line', 'Panonun altındaki soluk çağrıların üstünde görünür.', ['max' => 40]],
                'duyurular.pano.arsiv_not' => ['Arşiv başlığının yanındaki küçük not', 'arşiv', 'line', '', ['max' => 24]],
            ],
            '3. Her ilan kâğıdında' => [
                'duyurular.kagit.no' => ['Kâğıdın üstündeki sıra numarası', 'Duyuru: [kalın]{no}[/kalın]', 'rich', '{no} yerine kâğıdın sırası (01, 02…) gelir; silmeyin.', ['vars' => ['no' => 'kâğıdın sırası'], 'need' => ['no'], 'max' => 30]],
                'duyurular.kagit.canli' => ['Öne çıkan çağrıdaki “canlı” işareti', 'Canlı çağrı', 'line', 'Yalnızca öne çıkarılan duyuruda görünür.', ['max' => 24]],
                'duyurular.kagit.tarihler' => ['Tarih listesinin erişilebilirlik adı', 'Önemli tarihler', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'duyurular.kagit.tur_yok' => ['Türü bilinmeyen tarihin adı', 'Bilgilendirme', 'line', 'Duyuruda tarihin türü seçilmemişse ya da silinmişse görünür.', ['max' => 30]],
                'duyurular.kagit.resmi' => ['Resmî duyuru düğmesi', 'Resmî duyuru', 'line', 'Kurumun kendi duyuru sayfasına götürür.', ['max' => 30]],
                'duyurular.kagit.takvime' => ['Takvime ekle bağlantısı', 'Takvime ekle', 'line', 'Yalnızca bu çağrının tarihlerini takvime aktarır.', ['max' => 30]],
                'duyurular.kagit.birlikte' => ['İletişime götüren bağlantı', 'Bu çağrıyı birlikte değerlendirelim', 'line', 'İletişim sayfasına götürür.', ['max' => 50]],
            ],
            '4. Kâğıdın köşesindeki damga' => [
                'duyurular.damga.son' => ['Damga türü: son başvuru', 'Son başvuru', 'line', 'Damgada büyük harfle basılır; yer dar, kısa tutun.', ['max' => 18]],
                'duyurular.damga.baslangic' => ['Damga türü: başvuru başlangıcı', 'Başvuru başlangıcı', 'line', 'Damgada büyük harfle basılır; yer dar, kısa tutun.', ['max' => 22]],
                'duyurular.damga.sonuc' => ['Damga türü: sonuç', 'Sonuç', 'line', 'Damgada büyük harfle basılır; yer dar, kısa tutun.', ['max' => 18]],
                'duyurular.damga.siradaki' => ['Damga türü: diğer önemli tarih', 'Sıradaki tarih', 'line', 'Damgada büyük harfle basılır; yer dar, kısa tutun.', ['max' => 18]],
                'duyurular.damga.doldu' => ['Süresi dolmuş çağrının damgası', 'Süresi doldu', 'line', 'Damgada ve erişilebilirlik açıklamasında görünür; yer dar, kısa tutun.', ['max' => 18]],
                'duyurular.damga.aria' => ['Damganın erişilebilirlik açıklaması', '{tur}: {tarih_uzun}, {kalan}', 'line', 'Görünmez; ekran okuyucular damgaya gelince bunu okur.', ['vars' => ['tur' => 'damganın türü', 'tarih_uzun' => 'tarih', 'kalan' => 'kalan süre'], 'need' => ['tur', 'tarih_uzun', 'kalan']]],
                'duyurular.damga.doldu_aria' => ['Süresi dolmuş damganın erişilebilirlik açıklaması', 'Süresi doldu: {tarih_uzun}', 'line', 'Görünmez; ekran okuyucular içindir.', ['vars' => ['tarih_uzun' => 'son tarih'], 'need' => ['tarih_uzun']]],
            ],
            '5. Kalan süre (duyuru sayfasında, çağrı takviminde ve ana sayfada aynı kalıplar)' => [
                'duyurular.kalan.gecti' => ['Tarihi geçmiş satırda', 'Geçti', 'line', 'Önemli tarihler listesinde, günü geçmiş tarihin sağında görünür.', ['max' => 16]],
                'duyurular.kalan.bugun' => ['Tarih bugünse', 'Bugün', 'line', '', ['max' => 16]],
                'duyurular.kalan.yarin' => ['Tarih yarınsa', 'Yarın', 'line', '', ['max' => 16]],
                'duyurular.kalan.gun' => ['Tarihe kalan gün', '{n} gün kaldı', 'line', '{n} yerine kalan gün sayısı gelir; silmeyin. Damgada büyük harfle basılır.', ['vars' => ['n' => 'kalan gün sayısı'], 'need' => ['n'], 'max' => 24]],
            ],
            '6. Tarih türlerinin adları (duyuru düzenlerken seçilen ve sitede, takvim dosyasında görünen adlar)' => [
                'duyurular.tur.baslangic' => ['Tarih türü: başvuru başlangıcı', 'Başvuru başlangıcı', 'line', 'Duyurudaki tarihin yanında, çağrı takviminde, açılış penceresinde, takvim (.ics) dosyasında ve duyuru düzenlerken tür listesinde görünür.', ['max' => 30]],
                'duyurular.tur.son' => ['Tarih türü: son başvuru günü', 'Son başvuru günü', 'line', 'Aynı yerlerde görünür. Geri sayım ve damga bu türdeki tarihi esas alır.', ['max' => 30]],
                'duyurular.tur.sonuc' => ['Tarih türü: sonuç açıklanması', 'Sonuç açıklanması', 'line', 'Aynı yerlerde görünür.', ['max' => 30]],
                'duyurular.tur.diger' => ['Tarih türü: bilgilendirme', 'Bilgilendirme', 'line', 'Aynı yerlerde görünür. Başka bir türe girmeyen tarihler için.', ['max' => 30]],
            ],
            '7. Takvim dosyası (.ics): ziyaretçinin takvimine eklenen kayıtlar' => [
                'duyurular.takvim.ad' => ['Takvimin adı', '{firma}: Duyurular', 'line', 'Ziyaretçi “Takvime ekle” ile tüm duyuruları aldığında takvim uygulamasında bu adla görünür. {firma} yerine firmanın adı gelir.', ['vars' => ['firma'], 'max' => 60]],
                'duyurular.takvim.resmi' => ['Etkinlik açıklamasında resmî bağlantının etiketi', 'Resmi bağlantı', 'line', 'Takvim etkinliğinin açıklamasında, kurumun duyuru adresinin önünde yazar.', ['max' => 30]],
                'duyurular.takvim.duyuru' => ['Etkinlik açıklamasında sitedeki duyurunun etiketi', 'Duyuru', 'line', 'Takvim etkinliğinin açıklamasında, sitemizdeki duyuru adresinin önünde yazar.', ['max' => 30]],
                'duyurular.takvim.yok' => ['Bulunmayan duyurunun takvimi istenirse', 'Duyuru bulunamadı.', 'line', 'Silinmiş ya da yayından kalkmış bir duyurunun takvim bağlantısı açılırsa gösterilir.', ['max' => 60]],
            ],
            '8. Arama motorları' => [
                'duyurular.seo.baslik' => ['Sekme başlığı', 'Duyurular ve Çağrı Takvimi', 'line', 'Tarayıcı sekmesinde ve Google sonuçlarında görünür; sayfada görünmez. Menüdeki sayfa adı Sayfa adları listesinden gelir.', ['max' => 60]],
                'duyurular.seo.description' => ['Arama motoru açıklaması', 'Hibe ve teşvik programlarının başvuru başlangıç, son başvuru ve sonuç tarihleri. {firma} çağrı takvimi.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma'], 'max' => 320]],
            ],
        ],
    ],
];
