<?php
/**
 * Kariyer sayfası (app/pages/kariyer.php): başlık, yayındaki ilanların "kadro fişleri", yan notlar. Başvuru formunun kendisi
 * "Kariyer başvuru formu" grubundadır (ilan sayfasıyla ortaktır). İlanların içeriği (başlık, alan, şehir, son başvuru) İş ilanları bölümünden gelir.
 */
return [
    'kariyer' => [
        'label' => 'Kariyer',
        'url'   => 'kariyer',
        'icon'  => 'briefcase',
        'sections' => [
            '1. Başlık' => [
                'kariyer.hero.baslik' => ['Büyük başlık', 'Dosyanızı [kırmızı-çizgi]açalım[/kırmızı-çizgi].', 'rich', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir. Altı çizili sözcüğü [kırmızı-çizgi]…[/kırmızı-çizgi] işaretleri belirler.', ['max' => 60]],
                'kariyer.hero.giris' => ['Başlığın altındaki açıklama (birinci paragraf)', 'Hibe, teşvik ve yatırım danışmanlığında bizimle çalışmak istiyorsanız aşağıdaki formu doldurun. Başvurunuz özgeçmişinizle birlikte doğrudan ekibimize ulaşır.', 'text'],
                'kariyer.hero.devam' => ['Başlığın altındaki ikinci paragraf', 'Burada iş, çağrı metnini ve mevzuatı okumak, bir başvuruyu eksiksiz hazırlamak ve son tarihe yetiştirmektir. Kâğıda, tarihe ve ayrıntıya özen gösteriyorsanız bize yazın.', 'text'],
            ],
            '2. Açık pozisyonlar' => [
                'kariyer.pozisyon.baslik' => ['Bölümün başlığı', 'Açık pozisyonlar', 'line', 'Bu bölüm yalnızca yayında en az bir iş ilanı varsa görünür.', ['max' => 40]],
                'kariyer.pozisyon.giris' => ['Başlığın altındaki açıklama', 'Aşağıdaki kadrolar için başvuru alıyoruz. Birini seçerseniz başvurunuz doğrudan o ilana bağlanır. Size uygun bir ilan yoksa genel başvuru formu {baglanti}.', 'text', '{baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'genel başvuru formu bağlantısı'], 'need' => ['baglanti']]],
                'kariyer.pozisyon.giris_baglanti' => ['Açıklamadaki bağlantının yazısı', 'aşağıda', 'line', 'Sayfadaki genel başvuru formuna götürür.', ['max' => 30]],
                'kariyer.fis.kadro' => ['Fişin solundaki “Kadro” yazısı', 'Kadro', 'line', 'Altında fişin sıra numarası görünür.', ['max' => 16]],
                'kariyer.fis.alan' => ['Fişte alan etiketi', 'Alan', 'line', '', ['max' => 24]],
                'kariyer.fis.sehir' => ['Fişte şehir etiketi', 'Şehir', 'line', '', ['max' => 24]],
                'kariyer.fis.tur' => ['Fişte çalışma türü etiketi', 'Çalışma türü', 'line', '', ['max' => 24]],
                'kariyer.fis.deneyim' => ['Fişte deneyim etiketi', 'Deneyim', 'line', '', ['max' => 24]],
                'kariyer.fis.son_basvuru' => ['Fişte son başvuru etiketi', 'Son başvuru', 'line', '', ['max' => 24]],
                'kariyer.fis.suresiz' => ['Son başvuru günü olmayan ilanda', 'Süresiz', 'line', '', ['max' => 24]],
                'kariyer.fis.bugun' => ['Son başvuru günü bugünse', 'Bugün son gün', 'line', 'Son başvuruya bir hafta ya da daha az kalmışsa tarihin altında görünür.', ['max' => 24]],
                'kariyer.fis.kalan' => ['Son başvuruya kalan gün', '{n} gün kaldı', 'line', '{n} yerine kalan gün sayısı gelir; silmeyin. Bir hafta ya da daha az kalmışsa görünür.', ['vars' => ['n' => 'kalan gün sayısı'], 'need' => ['n'], 'max' => 24]],
                'kariyer.fis.ac' => ['Fişin sağ altındaki bağlantı', 'İlanı açın', 'line', 'İlan sayfasına götürür.', ['max' => 24]],
            ],
            '3. Yan notlar' => [
                'kariyer.yan.etiket' => ['Yan bölümün erişilebilirlik adı', 'Çalıştığımız alanlar ve değerlendirme süreci', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'kariyer.yan.alanlar' => ['“Çalıştığımız alanlar” başlığı', 'Çalıştığımız alanlar', 'line', 'Altındaki liste Hizmetler bölümünden gelir.', ['max' => 40]],
                'kariyer.yan.surec' => ['Değerlendirme sürecinin başlığı', 'Başvurunuz nasıl değerlendirilir?', 'line', '', ['max' => 50]],
                'kariyer.yan.bir' => ['Sürecin birinci adımı', '[kalın]Başvurunuz bize ulaşır.[/kalın] Form ve özgeçmişiniz doğrudan ekibimize iletilir.', 'rich'],
                'kariyer.yan.iki' => ['Sürecin ikinci adımı', '[kalın]İnceleriz.[/kalın] Deneyiminizi ve ilgilendiğiniz alanı ekibimizin ihtiyaçlarıyla birlikte değerlendiririz.', 'rich'],
                'kariyer.yan.uc' => ['Sürecin üçüncü adımı', '[kalın]Size ulaşırız.[/kalın] Uygun bir pozisyon olduğunda görüşme için sizinle iletişime geçeriz.', 'rich'],
                'kariyer.yan.not' => ['Kişisel veri notu', 'Başvurunuzda paylaştığınız bilgiler yalnızca işe alım süreci için kullanılır. Ayrıntılar için {baglanti}’ne bakabilirsiniz.', 'text', '{baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'Aydınlatma Metni bağlantısı'], 'need' => ['baglanti']]],
                'kariyer.yan.not_baglanti' => ['Kişisel veri notundaki bağlantının yazısı', 'Aydınlatma Metni', 'line', 'KVKK metninin iş başvurusu maddesine götürür.', ['max' => 40]],
            ],
            '4. Başvuru formunun çevresi' => [
                'kariyer.form.alan_etiket' => ['Başvuru bölümünün erişilebilirlik adı', 'İş başvurusu', 'line', 'Görünmez; ekran okuyucular içindir.'],
            ],
            '5. Sayfanın sonundaki bağlantı' => [
                'kariyer.sonraki.etiket' => ['İletişim bağlantısının küçük yazısı', 'Aklınızdaki soru için', 'line', 'Bağlantının büyük yazısı İletişim sayfasının adıdır (Sayfa adları).', ['max' => 50]],
            ],
            '6. Arama motorları' => [
                'kariyer.seo.description' => ['Arama motoru açıklaması', '{firma} ekibine katılmak için iş başvurusu formu. Hibe, teşvik ve yatırım danışmanlığında birlikte çalışalım.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma'], 'max' => 320]],
            ],
        ],
    ],
];
