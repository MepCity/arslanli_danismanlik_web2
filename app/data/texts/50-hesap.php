<?php
/** Hesap Numaralarımız sayfası (app/pages/hesap.php): her banka için bir fiş. Hesaplar Kurumsal içerik > Banka hesapları bölümünden gelir. */
return [
    'hesap' => [
        'label' => 'Hesap numaralarımız',
        'url'   => 'hesap-numaralarimiz',
        'icon'  => 'file-text',
        'sections' => [
            '1. Başlık' => [
                'hesap.hero.baslik' => ['Büyük başlık', 'Hesap numaralarımız.', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 50]],
                'hesap.hero.giris' => ['Başlığın altındaki açıklama', 'Ödemenizi aşağıdaki {hesap_sayisi|yazı} hesaptan birine yapabilirsiniz. Açıklama satırına firmanızın adını yazmanız, ödemeyi doğru dosyayla eşleştirmemizi kolaylaştırır.', 'text', 'İki ya da daha çok hesap varsa görünür. {hesap_sayisi|yazı} yerine hesap sayısı yazıyla gelir (iki, üç…); silmeyin.', ['vars' => ['hesap_sayisi'], 'need' => ['hesap_sayisi']]],
                'hesap.hero.giris_tek' => ['Başlığın altındaki açıklama (tek hesap varsa)', 'Ödemenizi aşağıdaki hesaba yapabilirsiniz. Açıklama satırına firmanızın adını yazmanız, ödemeyi doğru dosyayla eşleştirmemizi kolaylaştırır.', 'text', 'Yalnızca Kurumsal içerikte tek banka hesabı kalırsa görünür.'],
            ],
            '2. Fişler' => [
                'hesap.fis.liste_etiket' => ['Fişlerin erişilebilirlik adı', 'Banka hesapları', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'hesap.fis.yazici' => ['Yazıcının üstündeki fiş sırası', 'Fiş {sira} / {toplam}', 'line', '{sira} ve {toplam} yerine fişin sırası ve toplam fiş sayısı gelir.', ['vars' => ['sira' => 'fişin sırası', 'toplam' => 'toplam fiş sayısı'], 'need' => ['sira', 'toplam'], 'max' => 30]],
                'hesap.fis.baslik' => ['Fişin alt başlığı', 'HESAP BİLGİ FİŞİ', 'line', 'Fişte büyük harfle basılır.', ['max' => 30]],
                'hesap.fis.sahip' => ['Fişte hesap sahibi etiketi', 'Hesap sahibi', 'line', '', ['max' => 24]],
                'hesap.fis.no' => ['Fişte hesap numarası etiketi', 'Hesap no', 'line', '', ['max' => 24]],
                'hesap.fis.iban' => ['Fişte IBAN etiketi', 'IBAN', 'line', '', ['max' => 12]],
                'hesap.fis.aciklama' => ['Fişte açıklama satırının etiketi', 'Açıklama', 'line', '', ['max' => 24]],
                'hesap.fis.aciklama_deger' => ['Fişte açıklama satırının değeri', 'firmanızın adı', 'line', 'Ödeme açıklamasına ne yazılacağını söyler.', ['max' => 30]],
                'hesap.fis.tarih' => ['Fişte tarih etiketi', 'Tarih', 'line', '', ['max' => 16]],
                'hesap.fis.saat' => ['Fişte saat etiketi', 'Saat', 'line', '', ['max' => 16]],
                'hesap.fis.son' => ['Fişin son satırı', '*** TEŞEKKÜR EDERİZ ***', 'line', 'Fişte büyük harfle basılır.', ['max' => 40]],
            ],
            '3. Kopyalama düğmeleri' => [
                'hesap.kopya.iban' => ['IBAN kopyalama düğmesi', 'IBAN’ı kopyala', 'line', '', ['max' => 30]],
                'hesap.kopya.iban_tamam' => ['IBAN kopyalanınca görünen bildirim', 'IBAN kopyalandı: {iban}', 'line', '{iban} yerine kopyalanan IBAN gelir.', ['vars' => ['iban' => 'kopyalanan IBAN'], 'need' => ['iban'], 'max' => 50]],
                'hesap.kopya.no' => ['Hesap numarası kopyalama düğmesi', 'Hesap no’yu kopyala', 'line', '', ['max' => 30]],
                'hesap.kopya.no_tamam' => ['Hesap numarası kopyalanınca görünen bildirim', 'Hesap numarası kopyalandı', 'line', '', ['max' => 50]],
            ],
            '4. Ödeme notları' => [
                'hesap.not.etiket' => ['Notların erişilebilirlik adı', 'Ödeme notları', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'hesap.not.baslik' => ['Notların başlığı', 'Ödeme yaparken', 'line', '', ['max' => 40]],
                'hesap.not.bir' => ['Birinci not', 'Alıcı adını [kalın]{hesap_sahibi}[/kalın] olarak yazın.', 'rich', '{hesap_sahibi} yerine ilk hesabın sahibi gelir; silmeyin.', ['vars' => ['hesap_sahibi' => 'ilk hesabın sahibi'], 'need' => ['hesap_sahibi']]],
                'hesap.not.iki' => ['İkinci not', 'Açıklama satırına firmanızın adını ekleyin.', 'text'],
                'hesap.not.uc' => ['Üçüncü not', 'Dekontu {eposta_baglanti} adresine iletebilirsiniz.', 'text', '{eposta_baglanti} yerine şirketin e-posta adresi (tıklanabilir) gelir; silmeyin.', ['vars' => ['eposta_baglanti'], 'need' => ['eposta_baglanti']]],
            ],
            '5. Sayfanın sonundaki bağlantı' => [
                'hesap.sonraki.etiket' => ['İletişim bağlantısının küçük yazısı', 'Bir sorunuz mu var? · {no}', 'line', '{no} yerine İletişim sayfasının evrak numarası gelir. Bağlantının büyük yazısı İletişim sayfasının adıdır (Sayfa adları).', ['vars' => ['no' => 'İletişim sayfasının numarası'], 'need' => ['no'], 'max' => 50]],
            ],
            '6. Arama motorları' => [
                'hesap.seo.description' => ['Arama motoru açıklaması', '{firma} banka hesap bilgileri: {banka_adlari} IBAN numaraları.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. {banka_adlari} yerine Kurumsal içerikteki banka adları gelir. 150-160 karakter önerilir.', ['vars' => ['firma', 'banka_adlari'], 'max' => 320]],
            ],
        ],
    ],
];
