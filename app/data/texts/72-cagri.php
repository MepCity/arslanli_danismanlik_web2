<?php
/**
 * Çağrı takvimi: bütün çağrıların tarihleri tek bir sürekli form çizelgesinde (app/partials/cagri.php).
 * Ana sayfada ilk 6 tarih, Duyurular sayfasında yaklaşan bütün tarihler. Çağrıların kendisi (başlık, kurum, tarih) Duyurular
 * bölümünden gelir; burada yalnızca çizelgenin çerçevesindeki yazılar durur. Ay ve gün adları takvim bilgisidir, değişmez.
 */
return [
    'cagri' => [
        'label' => 'Çağrı takvimi (ana sayfa ve Duyurular)',
        'url'   => 'duyurular',
        'icon'  => 'calendar-blank',
        'sections' => [
            '1. Başlık' => [
                'cagri.baslik.etiket' => ['Küçük üst yazı', 'Çağrı takvimi', 'line', '', ['max' => 40]],
                'cagri.baslik.baslik' => ['Büyük başlık', 'Önümüzdeki tarihler.', 'line', '', ['max' => 60]],
                'cagri.baslik.giris' => ['Başlığın altındaki açıklama (tarih varken)', 'Açık çağrıların başlangıç, ön kayıt, son başvuru ve sonuç günleri, tarih sırasıyla. Hangisine ne kadar süre kaldığı yanında yazar; bir satıra tıklarsanız çağrının duyurusuna gidersiniz.', 'text'],
                'cagri.baslik.giris_bos' => ['Başlığın altındaki açıklama (yaklaşan tarih yokken)', 'Açık çağrıların başlangıç, ön kayıt, son başvuru ve sonuç günleri burada tarih sırasıyla toplanır.', 'text'],
            ],
            '2. Çizelgenin üst satırı' => [
                'cagri.cizelge.baslik' => ['Çizelge adı (tarih yokken)', 'Çağrı çizelgesi', 'line', '', ['max' => 40]],
                'cagri.cizelge.baslik_sayili' => ['Çizelge adı ve tarih sayısı', 'Çağrı çizelgesi · {n} tarih', 'line', '{n} yerine yaklaşan tarihlerin sayısı gelir.', ['vars' => ['n' => 'yaklaşan tarih sayısı'], 'need' => ['n'], 'max' => 50]],
                'cagri.cizelge.duzenleme' => ['Düzenleme tarihi', 'Düzenleme: {tarih}', 'line', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
            ],
            '3. Tarih satırları' => [
                'cagri.tur.baslangic' => ['Tarih türü: başvuru açılışı', 'Başvuru açılışı', 'line', 'Satırda büyük harfle basılır.', ['max' => 24]],
                'cagri.tur.son' => ['Tarih türü: son başvuru', 'Son başvuru', 'line', 'Satırda büyük harfle basılır.', ['max' => 24]],
                'cagri.tur.sonuc' => ['Tarih türü: sonuç', 'Sonuç', 'line', 'Satırda büyük harfle basılır.', ['max' => 24]],
                'cagri.tur.on_kayit' => ['Tarih türü: ön kayıt', 'Ön kayıt', 'line', 'Notunda “ön kayıt” geçen bilgilendirme tarihleri için.', ['max' => 24]],
                'cagri.tur.onemli' => ['Tarih türü: diğer önemli tarih', 'Önemli tarih', 'line', 'Satırda büyük harfle basılır.', ['max' => 24]],
                'cagri.kalan.bugun' => ['Kalan süre: bugün', 'Bugün', 'line', 'Tarih bugünse satırın sağında görünür.', ['max' => 16]],
                'cagri.kalan.yarin' => ['Kalan süre: yarın', 'Yarın', 'line', 'Tarih yarınsa satırın sağında görünür.', ['max' => 16]],
                'cagri.kalan.gun' => ['Kalan süre: ekran okuyucu için (gün sayısıyla)', '{n} gün kaldı', 'line', 'Görünmez; satırın erişilebilirlik açıklamasında okunur.', ['vars' => ['n' => 'kalan gün sayısı'], 'need' => ['n']]],
                'cagri.kalan.birim' => ['Kalan süre: sayının yanındaki yazı', 'gün kaldı', 'line', 'Satırın sağında büyük gün sayısının altında görünür.', ['max' => 24]],
                'cagri.satir.aria' => ['Satırın erişilebilirlik açıklaması', '{tarih_uzun}, {haftagunu}: {tur}. {kurum}, {baslik}. {kalan}', 'line', 'Görünmez; ekran okuyucular bir satıra gelince bunu okur.', ['vars' => ['tarih_uzun' => 'tarih', 'haftagunu' => 'haftanın günü', 'tur' => 'tarih türü', 'kurum' => 'kurum adı', 'baslik' => 'çağrının başlığı', 'kalan' => 'kalan süre']]],
            ],
            '4. Çizelgenin sonu' => [
                'cagri.son.devam' => ['Liste sonu yazısı (ana sayfada, daha fazla tarih varken)', '*** devamı duyurular sayfasında ***', 'line', '', ['max' => 60]],
                'cagri.son.bitti' => ['Liste sonu yazısı', '*** liste sonu ***', 'line', '', ['max' => 60]],
                'cagri.son.takvim' => ['Takvime ekle düğmesi', 'Tüm tarihleri takvime ekle', 'line', 'Bütün tarihleri tek dosyada takvim uygulamasına aktarır.', ['max' => 50]],
                'cagri.son.tumu' => ['Duyuruların tamamı bağlantısı', 'Duyuruların tamamı', 'line', 'Yalnızca ana sayfada görünür.', ['max' => 40]],
                'cagri.son.tumu_sayili' => ['Duyuruların tamamı bağlantısı (gizli tarih varken)', 'Duyuruların tamamı ({n} tarih)', 'line', '{n} yerine toplam tarih sayısı gelir.', ['vars' => ['n' => 'toplam tarih sayısı'], 'need' => ['n'], 'max' => 50]],
                'cagri.son.not' => ['Duyurular sayfasındaki alt not', 'Google, Apple ya da Outlook takviminize eklenir.', 'line', '', ['max' => 80]],
            ],
            '5. Yaklaşan tarih yokken' => [
                'cagri.bos.yildiz' => ['Boş çizelge işareti', '*** yaklaşan tarih yok ***', 'line', '', ['max' => 60]],
                'cagri.bos.metin' => ['Boş çizelge açıklaması', 'Şu anda takvimde yaklaşan bir tarih görünmüyor. Yeni bir çağrı açıldığında başlangıç, son başvuru ve sonuç günleri bu çizelgeye işlenir.', 'text'],
                'cagri.bos.bulten' => ['Bülten bağlantısı', 'Yeni çağrılardan haberdar olmak için bültene kayıt olun', 'line', 'Bülten bölümü açıksa görünür.', ['max' => 80]],
            ],
        ],
    ],
];
