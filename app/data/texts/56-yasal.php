<?php
/**
 * Yasal metin sayfalarının ortak çerçevesi (app/pages/_legal.php, assets/js/pages/legal.js): KVKK Aydınlatma Metni ve Çerez Politikası aynı düzenle
 * basılır. Burada çerçevenin sabit yazıları durur; metinlerin kendisi (maddeler, fıkralar, sade Türkçesi) kendi gruplarındadır.
 */
return [
    'yasal' => [
        'label' => 'Yasal metinlerin ortak çerçevesi',
        'url'   => 'kurumsal/kvkk-aydinlatma-metni',
        'icon'  => 'file-text',
        'sections' => [
            '1. Sayfanın üstündeki künye satırı' => [
                'yasal.ust.sayi' => ['Belge numarası satırı', 'Sayı: [kalın]{sayi}[/kalın]', 'rich', '{sayi} yerine metnin kendi belge numarası gelir (KVKK ve Çerez sayfalarının “Sayfanın başı” bölümü); silmeyin.', ['vars' => ['sayi' => 'belge numarası'], 'need' => ['sayi'], 'max' => 40]],
                'yasal.ust.konu' => ['Konu satırı', 'Konu: [kalın]{konu}[/kalın]', 'rich', '{konu} yerine metnin kendi konu satırı gelir; silmeyin.', ['vars' => ['konu' => 'metnin konusu'], 'need' => ['konu'], 'max' => 40]],
                'yasal.ust.guncelleme' => ['Son güncelleme satırı', 'Son güncelleme: [kalın]{tarih_son}[/kalın]', 'rich', '{tarih_son} yerine metnin son güncelleme tarihi gelir; silmeyin.', ['vars' => ['tarih_son' => 'son güncelleme tarihi'], 'need' => ['tarih_son'], 'max' => 50]],
            ],
            '2. Kısaca kutusu' => [
                'yasal.kisaca.baslik' => ['Kutunun başlığı', 'Kısaca', 'line', 'Başlığın sağındaki sade özet kutusu.', ['max' => 24]],
                'yasal.kisaca.giris' => ['Kutunun giriş cümlesi', 'Aşağıdaki metin hukuki bir metin; dili de öyle. Önce sade özeti:', 'text', 'Sade özet cümlelerinin üstünde görünür. Özet cümleleri her metnin kendi grubundadır.'],
            ],
            '3. Madde listesi ve maddeler' => [
                'yasal.metin.etiket' => ['Metnin tamamının erişilebilirlik adı', 'Metnin tamamı', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'yasal.liste.etiket' => ['Madde listesinin erişilebilirlik adı', 'Maddeler', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'yasal.liste.baslik' => ['Sol yandaki madde listesinin başlığı', 'Maddeler', 'line', '', ['max' => 24]],
                'yasal.liste.madde' => ['Madde listesinde her satırın başı', 'Madde {n}', 'line', '{n} yerine maddenin sırası gelir; silmeyin.', ['vars' => ['n' => 'maddenin sırası'], 'need' => ['n'], 'max' => 24]],
                'yasal.liste.tumu' => ['“Tümünü sadeleştir” düğmesi', 'Tümünü sadeleştir', 'line', 'Bütün “Sade Türkçesi” notlarını birlikte açar.', ['js' => true, 'max' => 30]],
                'yasal.liste.tumu_kapat' => ['Notlar açıkken düğmenin yazısı', 'Sade notları kapat', 'line', 'Bütün “Sade Türkçesi” notları açıkken düğmede bu görünür.', ['js' => true, 'max' => 30]],
                'yasal.madde.basi' => ['Her maddenin ilk satırının başı', 'Madde {n} –', 'line', '{n} yerine maddenin sırası gelir; silmeyin. Kalın görünür.', ['vars' => ['n' => 'maddenin sırası'], 'need' => ['n'], 'max' => 24]],
                'yasal.madde.sade' => ['Sade Türkçesi kutusunun başlığı', 'Sade Türkçesi', 'line', 'Maddenin altındaki açılır kutunun başlığı.', ['max' => 30]],
            ],
            '4. Metnin sonu' => [
                'yasal.son.cumle' => ['Metnin son cümlesi', 'Bu metin {tarih_son} tarihinde güncellenmiştir. Sorularınız için {eposta_baglanti}.', 'text', '{tarih_son} yerine metnin son güncelleme tarihi, {eposta_baglanti} yerine şirketin e-posta adresi (tıklanabilir) gelir; silmeyin.', ['vars' => ['tarih_son' => 'son güncelleme tarihi', 'eposta_baglanti'], 'need' => ['tarih_son', 'eposta_baglanti']]],
                'yasal.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine sıradaki sayfanın evrak numarası gelir. Büyük yazı her metnin kendi grubundadır.', ['vars' => ['no' => 'sıradaki sayfanın numarası'], 'need' => ['no'], 'max' => 50]],
            ],
        ],
    ],
];
