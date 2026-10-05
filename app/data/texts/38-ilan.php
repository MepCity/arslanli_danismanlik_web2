<?php
/**
 * İş ilanı sayfası (app/pages/ilan.php): her ilanın kendi sayfası. İlanın içeriği (başlık, özet, alan, şehir, çalışma türü, deneyim, son başvuru günü,
 * görevler, nitelikler) İş ilanları bölümünden gelir; burada yalnızca onu çevreleyen yazılar durur. Başvuru formu "Kariyer başvuru formu"
 * grubundadır. Kapanmış ya da süresi dolmuş ilanın sayfası ayrı bir ekran olarak aşağıda "Kapanan ilan" bölümlerindedir.
 */
return [
    'ilan' => [
        'label' => 'İş ilanı sayfası',
        'url'   => 'kariyer',
        'icon'  => 'briefcase',
        'sections' => [
            '1. Başlığın üstü ve altı (açık ilan)' => [
                'ilan.hero.etiket' => ['Küçük üst yazı', '{sayfa} · Açık kadro', 'line', '{sayfa} yerine Kariyer sayfasının adı gelir; başına sayfanın evrak etiketi eklenir.', ['vars' => ['sayfa' => 'Kariyer sayfasının adı'], 'need' => ['sayfa'], 'max' => 50]],
                'ilan.hero.tumu' => ['Tüm ilanlara dönen bağlantı', 'Tüm açık pozisyonlar', 'line', 'Başlığın altındaki özetin yanında görünür; Kariyer sayfasındaki ilan listesine götürür.', ['max' => 40]],
            ],
            '2. Kadro talep fişi (açık ilan)' => [
                'ilan.fis.alan_etiket' => ['Fiş bölümünün erişilebilirlik adı', 'Kadro bilgileri', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ilan.fis.baslik' => ['Fişin başlığı', 'Kadro talep fişi', 'line', '', ['max' => 40]],
                'ilan.fis.talep' => ['Talep numarası satırı', 'Talep no [kalın]{no}[/kalın]', 'rich', '{no} yerine ilana özgü talep numarası gelir; silmeyin.', ['vars' => ['no' => 'talep numarası'], 'need' => ['no'], 'max' => 40]],
                'ilan.fis.alan' => ['Fişte alan etiketi', 'Alan', 'line', '', ['max' => 24]],
                'ilan.fis.sehir' => ['Fişte şehir etiketi', 'Şehir', 'line', '', ['max' => 24]],
                'ilan.fis.tur' => ['Fişte çalışma türü etiketi', 'Çalışma türü', 'line', '', ['max' => 24]],
                'ilan.fis.deneyim' => ['Fişte deneyim etiketi', 'Deneyim', 'line', '', ['max' => 24]],
                'ilan.fis.yayin' => ['Fişte yayın tarihi etiketi', 'Yayın tarihi', 'line', '', ['max' => 24]],
                'ilan.fis.son_basvuru' => ['Fişte son başvuru etiketi', 'Son başvuru', 'line', '', ['max' => 24]],
                'ilan.fis.bugun' => ['Son başvuru günü bugünse', 'Bugün son gün', 'line', 'Son başvuruya bir hafta ya da daha az kalmışsa tarihin altında görünür.', ['max' => 30]],
                'ilan.fis.kalan' => ['Son başvuruya kalan gün', '{n} gün kaldı', 'line', '{n} yerine kalan gün sayısı gelir; silmeyin. Bir hafta ya da daha az kalmışsa görünür.', ['vars' => ['n' => 'kalan gün sayısı'], 'need' => ['n'], 'max' => 30]],
                'ilan.fis.belirtilmedi' => ['Son başvuru günü olmayan ilanda tarih yerine', 'Belirtilmedi', 'line', '', ['max' => 24]],
                'ilan.fis.suresiz' => ['Son başvuru günü olmayan ilanda açıklama', 'İlan açık olduğu sürece başvuru alınır.', 'line', '', ['max' => 80]],
                'ilan.fis.forma_git' => ['Fişin altındaki düğme', 'Başvuru formuna geçin', 'line', 'Sayfadaki başvuru formuna kaydırır.', ['max' => 40]],
                'ilan.fis.not' => ['Fişin altındaki not', 'Bu pozisyon size uymuyorsa özgeçmişinizi {baglanti} da bırakabilirsiniz.', 'text', '{baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'genel başvuru bağlantısı'], 'need' => ['baglanti']]],
                'ilan.fis.not_baglanti' => ['Fişin altındaki notta bağlantının yazısı', 'genel başvuru formuyla', 'line', 'Kariyer sayfasındaki genel başvuru formuna götürür.', ['max' => 40]],
            ],
            '3. Kadro tanımı (açık ilan)' => [
                'ilan.tanim.alan_etiket' => ['Kadro tanımı kâğıdının erişilebilirlik adı', 'Kadro tanımı', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ilan.tanim.baslik' => ['Kâğıdın başlığı', 'Kadro tanımı', 'line', '', ['max' => 40]],
                'ilan.tanim.tarih' => ['Tarih etiketi', 'Tarih', 'line', 'Yanında bugünün tarihi görünür.', ['max' => 16]],
                'ilan.tanim.gorevler' => ['A bölümünün başlığı', 'Görevler', 'line', 'Bölümün harfi (A) sayfada otomatik gelir; maddeler İş ilanları bölümünden gelir.', ['max' => 40]],
                'ilan.tanim.nitelikler' => ['B bölümünün başlığı', 'Aranan nitelikler', 'line', 'Bölümün harfi (B) sayfada otomatik gelir.', ['max' => 40]],
                'ilan.tanim.tercih' => ['C bölümünün başlığı', 'Tercih sebebi olacaklar', 'line', 'Bölümün harfi (C) sayfada otomatik gelir.', ['max' => 40]],
            ],
            '4. Başvuru bölümü (açık ilan)' => [
                'ilan.basvuru.alan_etiket' => ['Başvuru bölümünün erişilebilirlik adı', 'İlana başvuru', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ilan.basvuru.yan_etiket' => ['Yan notun erişilebilirlik adı', 'Değerlendirme süreci', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ilan.basvuru.surec' => ['Değerlendirme sürecinin başlığı', 'Başvurunuz nasıl değerlendirilir?', 'line', '', ['max' => 50]],
                'ilan.basvuru.bir' => ['Sürecin birinci adımı', '[kalın]Başvurunuz bu ilana bağlanır.[/kalın] Form ve özgeçmişiniz, ilanın adıyla birlikte ekibimize ulaşır.', 'rich'],
                'ilan.basvuru.iki' => ['Sürecin ikinci adımı', '[kalın]İnceleriz.[/kalın] Deneyiminizi, aranan niteliklerle ve ekibimizin ihtiyacıyla birlikte değerlendiririz.', 'rich'],
                'ilan.basvuru.uc' => ['Sürecin üçüncü adımı', '[kalın]Size ulaşırız.[/kalın] Uygun bulursak görüşme için sizinle iletişime geçeriz.', 'rich'],
                'ilan.basvuru.not' => ['Kişisel veri notu', 'Başvurunuzda paylaştığınız bilgiler yalnızca işe alım süreci için kullanılır. Ayrıntılar için {baglanti}’ne bakabilirsiniz.', 'text', '{baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'Aydınlatma Metni bağlantısı'], 'need' => ['baglanti']]],
                'ilan.basvuru.not_baglanti' => ['Kişisel veri notundaki bağlantının yazısı', 'Aydınlatma Metni', 'line', 'KVKK metninin iş başvurusu maddesine götürür.', ['max' => 40]],
            ],
            '5. Sayfanın sonundaki bağlantı (açık ilan)' => [
                'ilan.sonraki.etiket' => ['Kariyer bağlantısının küçük yazısı', 'Diğer kadrolar için', 'line', 'Bağlantının büyük yazısı Kariyer sayfasının adıdır (Sayfa adları).', ['max' => 50]],
            ],
            '6. Kapanan ilan: başlık' => [
                'ilan.kapali.etiket' => ['Küçük üst yazı', '{sayfa} · Kapanan kadro', 'line', '{sayfa} yerine Kariyer sayfasının adı gelir; başına sayfanın evrak etiketi eklenir.', ['vars' => ['sayfa' => 'Kariyer sayfasının adı'], 'need' => ['sayfa'], 'max' => 50]],
                'ilan.kapali.baslik' => ['Büyük başlık', 'Bu ilan [kırmızı-çizgi]kapandı[/kırmızı-çizgi].', 'rich', 'Altı çizili sözcüğü [kırmızı-çizgi]…[/kırmızı-çizgi] işaretleri belirler.', ['max' => 50]],
                'ilan.kapali.giris_doldu' => ['Başlığın altındaki açıklama (süresi dolmuş ilan)', 'Bu pozisyon için son başvuru günü geçti, artık başvuru alınmıyor.', 'text', 'Son başvuru günü geçmiş ilanlarda görünür.'],
                'ilan.kapali.giris_kapali' => ['Başlığın altındaki açıklama (yayından kaldırılmış ilan)', 'Bu pozisyon için artık başvuru almıyoruz.', 'text', 'Yönetici tarafından kapatılmış ilanlarda görünür.'],
                'ilan.kapali.devam' => ['Açıklamanın ikinci paragrafı', 'Özgeçmişinizi yine de bırakabilirsiniz: genel başvuru formuna gelen dosyalar, uygun bir kadro açıldığında yeniden değerlendirilir.', 'text'],
            ],
            '7. Kapanan ilan: kadro fişi ve damga' => [
                'ilan.kapali.alan_etiket' => ['Fiş bölümünün erişilebilirlik adı', 'Kapanan ilan', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ilan.kapali.kadro' => ['Fişin solundaki “Kadro” yazısı', 'Kadro', 'line', '', ['max' => 16]],
                'ilan.kapali.talep' => ['Talep numarası satırı', 'Talep no [kalın]{no}[/kalın]', 'rich', '{no} yerine ilana özgü talep numarası gelir; silmeyin.', ['vars' => ['no' => 'talep numarası'], 'need' => ['no'], 'max' => 40]],
                'ilan.kapali.damga' => ['Damganın büyük yazısı', 'İlan kapandı', 'line', 'Damgada büyük harfle basılır; yer dar, kısa tutun.', ['max' => 16]],
                'ilan.kapali.damga_doldu' => ['Damganın alt satırı (süresi dolmuş ilan)', '{gun} · Son gün', 'line', '{gun} yerine son başvuru günü gelir. Damgada büyük harfle basılır.', ['vars' => ['gun' => 'son başvuru günü'], 'need' => ['gun'], 'max' => 30]],
                'ilan.kapali.damga_kapali' => ['Damganın alt satırı (yayından kaldırılmış ilan)', 'Başvuru alınmıyor', 'line', 'Damgada büyük harfle basılır.', ['max' => 30]],
                'ilan.kapali.forma_git' => ['Genel başvuru düğmesi', 'Genel başvuru formuna gidin', 'line', 'Kariyer sayfasındaki genel başvuru formuna götürür.', ['max' => 40]],
                'ilan.kapali.diger' => ['Açık pozisyonlara bağlantı', 'Açık pozisyonlara bakın', 'line', 'Yalnızca yayında başka ilan varsa görünür.', ['max' => 40]],
            ],
            '8. Arama motorları ve sekme başlığı' => [
                'ilan.seo.kapali_baslik' => ['Kapanan ilanın sekme başlığı', 'İlan kapandı', 'line', 'Tarayıcı sekmesinde görünür. Açık ilanın sekme başlığı ilanın kendi adıdır (İş ilanları bölümü).', ['max' => 40]],
                'ilan.seo.kapali_aciklama' => ['Kapanan ilanın arama motoru açıklaması', 'Bu iş ilanı kapandı. Özgeçmişinizi genel başvuru formuyla bırakabilirsiniz.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. Açık ilanın açıklaması ilanın özetinden üretilir.', ['max' => 320]],
            ],
        ],
    ],
];
