<?php
/** Misyonumuz sayfası (app/pages/misyon.php + assets/js/pages/mission.js): defterdeki iş listesi. Misyon cümlesi ve beş madde Kurumsal içerik > Misyon ve vizyon bölümünden gelir. */
return [
    'misyon' => [
        'label' => 'Misyonumuz',
        'url'   => 'kurumsal/misyonumuz',
        'icon'  => 'sparkle',
        'sections' => [
            '1. Başlık' => [
                'misyon.hero.sayi' => ['Başlığın üstündeki satır: sayı', 'Sayı: [kalın]ARS-{yil}/008[/kalın]', 'rich', 'Kâğıdın üstündeki küçük satırın ilk parçası. {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 40]],
                'misyon.hero.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]Misyonumuz[/kalın]', 'rich', '', ['max' => 40]],
                'misyon.hero.baslik' => ['Büyük başlık', 'Misyonumuz', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 40]],
                'misyon.hero.giris' => ['Başlığın altındaki açıklama', 'Misyonumuzu bir iş listesi olarak yazdık. Her dosyada bu {misyon_sayisi|yazı} işi yaparız; biri eksik kalırsa dosya bitmiş sayılmaz.', 'text', '{misyon_sayisi|yazı} yerine misyon maddesi sayısı yazıyla gelir (beş…); silmeyin.', ['vars' => ['misyon_sayisi'], 'need' => ['misyon_sayisi']]],
            ],
            '2. Defter sayfası' => [
                'misyon.defter.tarih' => ['Defterin üst satırı: tarih', 'Tarih: [eğik]{tarih}[/eğik]', 'rich', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
                'misyon.defter.konu' => ['Defterin üst satırı: konu', 'Konu: [eğik]Misyon[/eğik]', 'rich', '', ['max' => 40]],
                'misyon.defter.baslik' => ['Listenin başlığı', 'Yapılacaklar', 'line', '', ['max' => 40]],
                'misyon.defter.okuyucu' => ['Her maddenin ekran okuyucu notu', '(her dosyada yapılır)', 'line', 'Görünmez; ekran okuyucular içindir.', ['max' => 60]],
                'misyon.defter.imza' => ['İmzanın altındaki şehir', 'İstanbul', 'line', 'Firma adının altında görünür.', ['max' => 30]],
            ],
            '3. Sayfanın sonundaki bağlantı' => [
                'misyon.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine Vizyonumuz sayfasının evrak numarası gelir. Bağlantının büyük yazısı Vizyonumuz sayfasının adıdır (Sayfa adları).', ['vars' => ['no' => 'Vizyonumuz sayfasının numarası'], 'need' => ['no'], 'max' => 40]],
            ],
            '4. Arama motorları' => [
                'misyon.seo.description' => ['Arama motoru açıklaması', 'Misyonumuz: {misyon} Bunu her dosyada yaptığımız {misyon_sayisi|yazı} işle yerine getiriyoruz.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. {misyon} yerine misyon cümlesi, {misyon_sayisi|yazı} yerine madde sayısı yazıyla gelir.', ['vars' => ['misyon' => 'misyon cümlesi', 'misyon_sayisi'], 'need' => ['misyon'], 'max' => 320]],
            ],
        ],
    ],
];
