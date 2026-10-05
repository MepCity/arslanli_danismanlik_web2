<?php
/**
 * Açılışta öne çıkan duyuru penceresi ve sol alttaki çipi (app/partials/spotlight.php). Duyurunun kendisi (başlık, özet, kurum,
 * tarihler, bağlantı) Duyurular bölümünden gelir; burada yalnızca pencerenin çerçevesindeki yazılar durur.
 */
return [
    'acilis' => [
        'label' => 'Öne çıkan duyuru penceresi',
        'url'   => '',
        'icon'  => 'bell',
        'sections' => [
            '1. Pencere ve çip' => [
                'acilis.canli' => ['“Canlı çağrı” rozeti', 'Canlı çağrı', 'line', 'Pencerenin köşesindeki rozette ve pencere kapanınca sol altta kalan çipte görünür.', ['max' => 24]],
                'acilis.kapat' => ['Kapat düğmesinin erişilebilirlik adı', 'Kapat', 'line', 'Görünmez (yalnızca çarpı simgesi görünür); ekran okuyucular içindir.', ['max' => 20]],
                'acilis.kurum' => ['Üst şeritte kurum satırı', 'Kurum: [kalın]{kurum}[/kalın]', 'rich', '{kurum} yerine duyurunun kurumu gelir; silmeyin.', ['vars' => ['kurum' => 'duyurudaki kurum adı'], 'need' => ['kurum'], 'max' => 40]],
                'acilis.tarih' => ['Üst şeritte tarih satırı', 'Tarih: [kalın]{tarih}[/kalın]', 'rich', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
            ],
            '2. Geri sayım' => [
                'acilis.sayac.son' => ['Geri sayım başlığı: son başvuru', 'Son başvuruya kalan', 'line', '', ['max' => 40]],
                'acilis.sayac.baslangic' => ['Geri sayım başlığı: başvuru açılışı', 'Başvuruların açılmasına', 'line', '', ['max' => 40]],
                'acilis.sayac.sonuc' => ['Geri sayım başlığı: sonuç', 'Sonuçların açıklanmasına', 'line', '', ['max' => 40]],
                'acilis.sayac.diger' => ['Geri sayım başlığı: diğer tarih', 'Bir sonraki tarihe', 'line', '', ['max' => 40]],
                'acilis.sayac.gun' => ['Sayaç birimi: gün', 'gün', 'line', '', ['max' => 12]],
                'acilis.sayac.saat' => ['Sayaç birimi: saat', 'saat', 'line', '', ['max' => 12]],
                'acilis.sayac.dakika' => ['Sayaç birimi: dakika', 'dakika', 'line', '', ['max' => 12]],
                'acilis.sayac.saniye' => ['Sayaç birimi: saniye', 'saniye', 'line', '', ['max' => 12]],
                'acilis.sayac.bugun' => ['Son gün uyarısı', 'Bugün son gün', 'line', 'Hedef tarihin son gününde sayacın yerine görünür.', ['max' => 30]],
            ],
            '3. Önemli tarihler' => [
                'acilis.tarihler.baslik' => ['Tarih listesinin başlığı', 'Önemli tarihler', 'line', '', ['max' => 40]],
                'acilis.tarihler.bugun' => ['Tarih cetvelindeki “bugün” işareti', 'bugün', 'line', 'Tarihler arasında bugünü gösteren küçük işaret.', ['max' => 12]],
                'acilis.tarihler.bilgi' => ['Türü belirsiz tarihin etiketi', 'Bilgilendirme', 'line', 'Duyurudaki tarihin türü tanınmazsa görünür.', ['max' => 24]],
            ],
            '4. Düğmeler' => [
                'acilis.dugme.ac' => ['Duyuruyu açan düğme', 'Duyuruyu aç', 'line', '', ['max' => 30]],
                'acilis.dugme.kaynak' => ['Resmî kaynağa giden düğme', 'Resmî kaynak', 'line', '', ['max' => 30]],
                'acilis.dugme.takvim' => ['Takvime ekleme bağlantısı', 'Takvime ekle', 'line', '', ['max' => 30]],
            ],
        ],
    ],
];
