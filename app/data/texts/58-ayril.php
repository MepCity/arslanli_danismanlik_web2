<?php
/**
 * Bültenden ayrılma sayfası (app/pages/bulten-ayril.php): her bülten e-postasının altındaki bağlantının açtığı "sicil kartı".
 * Sayfa dört durumda görünür: onay bekleniyor, ayrıldınız, zaten ayrılmış, bağlantı geçersiz. E-postanın alt bilgisi
 * (neden gönderildiği, ayrılma bağlantısı) "Bülten kayıt penceresi" grubundadır (“E-posta alt bilgisi” bölümü).
 * Bu sayfa menüde ve site haritasında yoktur; arama motorlarına kapalıdır.
 */
return [
    'ayril' => [
        'label' => 'Bültenden ayrılma',
        'url'   => 'bulten/ayril',
        'icon'  => 'envelope-simple',
        'sections' => [
            '1. Tüm durumlarda ortak yazılar' => [
                'ayril.sayfa.ad' => ['Sayfanın adı (üst çubukta ve küçük üst yazıda)', 'Terkin şerhi', 'line', 'Sayfanın başında “Evrak · Terkin şerhi” biçiminde, üst çubukta “Evrak — · Terkin şerhi” biçiminde görünür.', ['max' => 30]],
                'ayril.kart.etiket' => ['Kartın erişilebilirlik adı', 'Sicil kartı', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ayril.kart.baslik' => ['Kartın üst yazısı', 'Bülten kayıt defteri', 'line', '', ['max' => 40]],
                'ayril.kart.form' => ['Kartın form numarası', 'Form: [kalın]ARS-B/02[/kalın]', 'rich', 'Resmî form görünümü için. Kalın yazılan kısım form kodudur.', ['max' => 40]],
                'ayril.kart.eposta' => ['Kartta e-posta satırının etiketi', 'E-posta', 'line', '', ['max' => 24]],
                'ayril.kart.durum' => ['Kartta durum satırının etiketi', 'Durum', 'line', '', ['max' => 24]],
                'ayril.kart.tarih' => ['Kartta tarih satırının etiketi', 'Tarih', 'line', '', ['max' => 24]],
                'ayril.kart.terkin' => ['Kartın kenarındaki “terkin” notu', 'terkin edildi · {kayit_tarihi}', 'line', '{kayit_tarihi} yerine ayrılma tarihi gelir. Aboneliği sona ermiş kartlarda el yazısıyla görünür.', ['vars' => ['kayit_tarihi' => 'ayrılma tarihi'], 'need' => ['kayit_tarihi'], 'max' => 40]],
            ],
            '2. Onay bekleniyor (e-postadaki bağlantıya tıklayınca)' => [
                'ayril.onay.baslik' => ['Büyük başlık', 'Bültenden ayrılmak üzeresiniz.', 'line', '', ['max' => 60]],
                'ayril.onay.giris' => ['Başlığın altındaki açıklama', 'Onayladığınızda aşağıdaki adrese artık bülten ve bilgilendirme e-postası göndermeyiz.', 'text'],
                'ayril.onay.durum' => ['Kartta durum', 'Kayıtlı', 'line', '', ['max' => 24]],
                'ayril.onay.dugme' => ['Onay düğmesi', 'Abonelikten ayrıl', 'line', '', ['max' => 40]],
                'ayril.onay.vazgec' => ['Vazgeçme bağlantısı', 'Vazgeçtim, ana sayfaya dön', 'line', '', ['max' => 50]],
            ],
            '3. Ayrılma tamamlandı' => [
                'ayril.tamam.baslik' => ['Büyük başlık', 'Abonelikten ayrıldınız.', 'line', '', ['max' => 60]],
                'ayril.tamam.giris' => ['Başlığın altındaki açıklama', 'Bu adrese artık bülten e-postası göndermeyeceğiz. Fikriniz değişirse bize e-posta ya da telefonla haber verin; aboneliğinizi yeniden açarız.', 'text'],
                'ayril.tamam.durum' => ['Kartta durum', 'Terkin edildi', 'line', '', ['max' => 24]],
                'ayril.tamam.dugme' => ['Ana sayfa düğmesi', 'Ana sayfaya dön', 'line', '', ['max' => 40]],
            ],
            '4. Adres zaten ayrılmış' => [
                'ayril.zaten.baslik' => ['Büyük başlık', 'Bu adres zaten ayrılmış.', 'line', '', ['max' => 60]],
                'ayril.zaten.giris' => ['Başlığın altındaki açıklama', 'Kaydınız daha önce terkin edilmiş; yapılacak bir işlem yok ve bu adrese bülten e-postası gönderilmiyor. Yeniden almak isterseniz bize e-posta ya da telefonla haber verin.', 'text'],
                'ayril.zaten.durum' => ['Kartta durum', 'Terkin edilmiş', 'line', '', ['max' => 24]],
                'ayril.zaten.dugme' => ['Ana sayfa düğmesi', 'Ana sayfaya dön', 'line', '', ['max' => 40]],
            ],
            '5. Bağlantı geçersiz' => [
                'ayril.gecersiz.baslik' => ['Büyük başlık', 'Bu bağlantı geçerli değil.', 'line', '', ['max' => 60]],
                'ayril.gecersiz.giris' => ['Başlığın altındaki açıklama', 'Bağlantı eksik kopyalanmış ya da bozulmuş olabilir. Aldığınız iletideki bağlantıya yeniden tıklayın ya da abonelikten ayrılmak istediğinizi bize şu adresten yazın:', 'text', 'Cümlenin sonunda şirketin e-posta adresi (İletişim ve şirket ayarları) bağlantı olarak görünür.'],
                'ayril.gecersiz.durum' => ['Kartta durum', 'Bulunamadı', 'line', '', ['max' => 24]],
                'ayril.gecersiz.bos' => ['Kartta e-posta satırı boşken okunan yazı', 'Kayıt bulunamadı', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'ayril.gecersiz.dugme' => ['Ana sayfa düğmesi', 'Ana sayfaya dön', 'line', '', ['max' => 40]],
            ],
            '6. Arama motorları ve sekme başlığı' => [
                'ayril.seo.baslik' => ['Sekme başlığı', 'Bültenden ayrıl', 'line', 'Tarayıcı sekmesinde görünür; sayfa arama motorlarına kapalıdır.', ['max' => 60]],
                'ayril.seo.description' => ['Sayfa açıklaması', 'Bülten aboneliğinden ayrılma.', 'line', 'Sayfada görünmez.', ['max' => 160]],
            ],
        ],
    ],
];
