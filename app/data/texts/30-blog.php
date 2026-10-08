<?php
/** Makaleler sayfası (app/pages/blog.php): gazete ön sayfası. Yalnızca Yazılar bölümü açıkken görünür. Yazıların başlığı, özeti, kategorisi ve tarihi Yazılar bölümünden gelir. */
return [
    'blog' => [
        'label' => 'Makaleler (gazete sayfası)',
        'url'   => 'blog',
        'icon'  => 'newspaper',
        'sections' => [
            '1. Gazete başlığı' => [
                'blog.mast.sehir' => ['Gazete şeridinde şehir ve tarih', 'İstanbul · {tarih_uzun}', 'line', '{tarih_uzun} yerine bugünün tarihi gelir.', ['vars' => ['tarih_uzun' => 'bugünün tarihi (5 Ekim 2026 biçiminde)'], 'need' => ['tarih_uzun'], 'max' => 50]],
                'blog.mast.slogan' => ['Gazete şeridinin ortasındaki slogan', 'Hibe, teşvik ve Ar-Ge mevzuatı üzerine notlar', 'line', '', ['max' => 80]],
                'blog.mast.sayi' => ['Gazete şeridinde sayı numarası', 'Sayı {n}', 'line', '{n} yerine yazı sayısı gelir.', ['vars' => ['n' => 'yazı sayısı'], 'need' => ['n'], 'max' => 20]],
                'blog.mast.ad' => ['Gazetenin adı (büyük başlık)', 'Arslanlı Bülteni', 'line', '', ['max' => 40]],
                'blog.mast.bolumler_etiket' => ['Bölüm bağlantılarının erişilebilirlik adı', 'Bölümler', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'blog.mast.bolumler' => ['Bölüm bağlantılarının solundaki yazı', 'Bölümler', 'line', '', ['max' => 24]],
                'blog.mast.tumu' => ['Bölüm bağlantıları: tümü', 'Tümü', 'line', 'Bölüm adları (Genel, Kalite…) Yazılar bölümündeki kategorilerden gelir.', ['max' => 24]],
                'blog.mast.genel' => ['Bölüm bağlantıları: varsayılan “Genel” bölümü', 'Genel', 'line', 'Yazısı olmasa bile her zaman listelenen bölümün adı. Diğer bölüm adları Yazılar bölümündeki kategorilerden gelir.', ['max' => 24]],
                'blog.mast.bolum_notu' => ['Bir bölüm seçilince çıkan not', 'Bölüm: [kalın]{bolum}[/kalın] · {n} yazı · {baglanti}', 'rich', '{bolum} yerine seçilen bölümün adı, {n} yerine yazı sayısı, {baglanti} yerine aşağıdaki bağlantı gelir; silmeyin.', ['vars' => ['bolum' => 'seçilen bölümün adı', 'n' => 'bölümdeki yazı sayısı', 'baglanti' => '“Sayının tamamı” bağlantısı'], 'need' => ['bolum', 'baglanti'], 'max' => 80]],
                'blog.mast.bolum_notu_baglanti' => ['Bölüm notundaki bağlantının yazısı', 'Sayının tamamı', 'line', 'Makaleler sayfasının ilk haline döner.', ['max' => 30]],
            ],
            '2. Manşet yazısı' => [
                'blog.manset.kunye' => ['Manşetin üstündeki satır', 'Manşet · {bolum} · {tarih_uzun} · {dk} dk okuma', 'line', '{bolum} yerine yazının kategorisi, {tarih_uzun} yerine tarihi, {dk} yerine okuma süresi gelir.', ['vars' => ['bolum' => 'yazının kategorisi', 'tarih_uzun' => 'yazının tarihi', 'dk' => 'okuma süresi (dakika)'], 'need' => ['bolum', 'tarih_uzun', 'dk'], 'max' => 70]],
                'blog.manset.devam' => ['Manşetin altındaki bağlantı', 'Yazının devamı', 'line', 'Yazının sayfasına gider.', ['max' => 30]],
                'blog.manset.altyazi' => ['Manşet fotoğrafının altyazısı', 'Temsilî fotoğraf.', 'line', '', ['max' => 40]],
                'blog.bos.baslik' => ['Bölümde yazı yokken başlık', 'Bu bölümde henüz yazı yok.', 'line', 'Yazısı olmayan bir bölüm seçilince görünür.', ['max' => 60]],
                'blog.bos.not' => ['Bölümde yazı yokken açıklama', 'Yeni yazılar bu sayfada yayımlanır. {baglanti}.', 'text', '{baglanti} yerine aşağıdaki bağlantı gelir; silmeyin.', ['vars' => ['baglanti' => '“Sayının tamamına dönün” bağlantısı'], 'need' => ['baglanti']]],
                'blog.bos.baglanti' => ['Açıklamadaki bağlantının yazısı', 'Sayının tamamına dönün', 'line', '', ['max' => 40]],
            ],
            '3. Diğer yazılar ve yan sütun' => [
                'blog.sutun.kunye' => ['Yazı sütununun üstündeki satır', '{bolum} · {tarih_uzun} · {dk} dk', 'line', '{bolum} yerine yazının kategorisi, {tarih_uzun} yerine tarihi, {dk} yerine okuma süresi gelir.', ['vars' => ['bolum' => 'yazının kategorisi', 'tarih_uzun' => 'yazının tarihi', 'dk' => 'okuma süresi (dakika)'], 'need' => ['bolum', 'tarih_uzun', 'dk'], 'max' => 60]],
                'blog.sutun.devam' => ['Yazı sütununun altındaki bağlantı', 'Devamı', 'line', 'Yazının sayfasına gider.', ['max' => 24]],
                'blog.yan.etiket' => ['Yan sütunun erişilebilirlik adı', 'Bu sayıda', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'blog.yan.baslik' => ['Yan sütundaki içindekiler başlığı', 'Bu sayıda', 'line', '', ['max' => 30]],
                'blog.yan.dk' => ['İçindekiler listesinde okuma süresi', '{dk} dk', 'line', '{dk} yerine okuma süresi (dakika) gelir.', ['vars' => ['dk' => 'okuma süresi (dakika)'], 'need' => ['dk'], 'max' => 16]],
                'blog.ilan.baslik' => ['Küçük ilan kutusunun başlığı', 'İlan', 'line', 'Yalnızca Bülten bölümü açıkken görünür.', ['max' => 16]],
                'blog.ilan.metin' => ['Küçük ilan kutusunun metni', 'Yeni bir hibe ya da teşvik çağrısı açıldığında haber almak isteyen işletmelere duyurulur.', 'text', 'Yalnızca Bülten bölümü açıkken görünür.'],
                'blog.ilan.dugme' => ['Küçük ilan kutusunun alt yazısı', 'Kuponu doldurun', 'line', 'Haberdar Ol sayfasına gider.', ['max' => 30]],
                'blog.alt.yayin' => ['Gazete alt çizgisinde yayıncı yazısı', '{firma} yayınıdır.', 'line', '{firma} yerine firmanın adı gelir.', ['vars' => ['firma'], 'max' => 60]],
                'blog.alt.sayfa' => ['Gazete alt çizgisinde sayfa yazısı', 'Sayfa 1', 'line', '', ['max' => 16]],
            ],
            '4. Sayfanın sonundaki bağlantı' => [
                'blog.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine İletişim sayfasının evrak numarası gelir. Bağlantının büyük yazısı İletişim sayfasının adıdır (Sayfa adları).', ['vars' => ['no' => 'İletişim sayfasının numarası'], 'need' => ['no'], 'max' => 40]],
            ],
            '5. Arama motorları' => [
                'blog.seo.baslik_bolum' => ['Bölüm sayfasının sekme başlığı', '{bolum} · {sayfa}', 'line', 'Bir bölüm seçilince tarayıcı sekmesinde görünür. {bolum} yerine bölümün adı, {sayfa} yerine sayfanın adı (Sayfa adları) gelir.', ['vars' => ['bolum' => 'bölümün adı', 'sayfa' => 'sayfanın adı'], 'need' => ['bolum'], 'max' => 60]],
                'blog.seo.description_bolum' => ['Bölüm sayfasının arama motoru açıklaması', 'Arslanlı Bülteni, {bolum} bölümü: {n} yazı. Hibe, teşvik ve Ar-Ge konularında sade ve somut yazılar.', 'text', 'Bir bölüm seçilince Google sonuçlarında görünür; sayfada görünmez. {bolum} yerine bölümün adı, {n} yerine bölümdeki yazı sayısı gelir.', ['vars' => ['bolum' => 'bölümün adı', 'n' => 'bölümdeki yazı sayısı'], 'need' => ['bolum'], 'max' => 320]],
                'blog.seo.description' => ['Arama motoru açıklaması', 'Arslanlı Bülteni: Ar-Ge yapılanması, kalite belgelendirme, yatırım teşvikleri ve hibe programları üzerine sade ve somut yazılar.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['max' => 320]],
            ],
        ],
    ],
];
