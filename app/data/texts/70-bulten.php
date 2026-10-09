<?php
/**
 * Bülten: her sayfadaki kenar sekmesi ve açtığı kayıt formu (app/partials/bulten.php), abonelere giden e-postaların alt bilgisi
 * (app/bulten.php). Haberdar Ol sayfası ve Bültenden ayrılma sayfası kendi gruplarındadır.
 */
return [
    'bulten' => [
        'label' => 'Bülten kayıt penceresi',
        'url'   => 'haberdarol',
        'icon'  => 'envelope-simple',
        'sections' => [
            '1. Sayfa kenarındaki sekme' => [
                'bulten.sekme.bolge' => ['Kenardaki bülten sekmesini saran bölgenin adı', 'Bülten kaydı', 'line', 'Görünmez; ekran okuyucular içindir (sayfa bölgeleri listesinde görünür).', ['max' => 40]],
                'bulten.sekme.metin' => ['Sekmenin yazısı', 'Haberdar ol', 'line', 'Sayfanın sağ kenarında dikey durur; tıklanınca kayıt penceresi açılır. Bülten bölümü Görünürlük ayarından kapatılırsa görünmez.', ['max' => 40]],
            ],
            '2. Pencerenin üst şeridi' => [
                'bulten.pencere.form' => ['Form numarası', 'Form: [kalın]ARS-B/01[/kalın]', 'rich', 'Resmî form görünümü için. Kalın yazılan kısım form kodudur.', ['max' => 40]],
                'bulten.pencere.tarih' => ['Tarih satırı', 'Tarih: [kalın]{tarih}[/kalın]', 'rich', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
                'bulten.pencere.kapat' => ['Pencereyi kapatan düğme', 'Kapat', 'line', '', ['max' => 20]],
            ],
            '3. Başlık ve giriş' => [
                'bulten.pencere.etiket' => ['Küçük üst yazı', 'Bülten kayıt formu', 'line', 'Hem form ekranında hem “Kaydınız alındı” ekranında görünür.', ['max' => 40]],
                'bulten.pencere.baslik' => ['Büyük başlık', 'Haberdar olun.', 'line', '', ['max' => 40]],
                'bulten.pencere.giris' => ['Başlığın altındaki açıklama', 'Yeni bir hibe ya da teşvik çağrısı açıldığında, bir programın kuralları değiştiğinde ve takip ettiğiniz bir başvurunun son tarihi yaklaştığında size yazalım. Formu bir kez doldurmanız yeterli.', 'text'],
            ],
            '4. Form alanları' => [
                'bulten.form.ad' => ['Ad alanının etiketi', 'Ad', 'line', 'Alanın numarası ve zorunlu işareti sayfada otomatik gelir.', ['max' => 30]],
                'bulten.form.ad_ornek' => ['Ad alanındaki örnek yazı', 'Ahmet', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'bulten.form.soyad' => ['Soyad alanının etiketi', 'Soyad', 'line', '', ['max' => 30]],
                'bulten.form.soyad_ornek' => ['Soyad alanındaki örnek yazı', 'Yılmaz', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'bulten.form.eposta' => ['E-posta alanının etiketi', 'E-posta', 'line', '', ['max' => 30]],
                'bulten.form.eposta_ornek' => ['E-posta alanındaki örnek yazı', 'ahmet@sirket.com', 'line', 'Kutu boşken soluk görünür.', ['max' => 40]],
                'bulten.form.telefon' => ['Telefon alanının etiketi', 'Telefon', 'line', '', ['max' => 30]],
                'bulten.form.telefon_ornek' => ['Telefon alanındaki örnek yazı', '05xx xxx xx xx', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'bulten.form.il' => ['İl alanının etiketi', 'İl', 'line', '', ['max' => 30]],
                'bulten.form.il_sec' => ['İl listesinin ilk satırı', 'İl seçin', 'line', 'İller Kurumsal içerik bölümündeki listeden gelir.', ['max' => 30]],
                'bulten.form.sektor' => ['Sektör alanının etiketi', 'Sektör', 'line', '', ['max' => 30]],
                'bulten.form.sektor_sec' => ['Sektör listesinin ilk satırı', 'Sektör seçin', 'line', 'Sektörler Kurumsal içerik bölümündeki “Form seçenekleri” listesinden gelir.', ['max' => 30]],
                'bulten.form.konular' => ['İlgilenilen konular alanının etiketi', 'İlgilendiğiniz konular', 'line', '', ['max' => 40]],
                'bulten.form.konular_ornek' => ['İlgilenilen konular alanındaki örnek yazı', 'İlgilendiğiniz teşvik, hibe ya da danışmanlık konularını yazabilirsiniz', 'text', 'Kutu boşken soluk görünür.'],
            ],
            '5. Onay kutuları (hukuki metin)' => [
                'bulten.onay.kvkk' => ['Aydınlatma metni onayı', 'Kişisel verilerimin işlenmesine ilişkin {baglanti}’ni okudum.', 'text', 'Hukuki bir onay metnidir; değiştirmeden önce hukuk danışmanınıza sorun. {baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'Aydınlatma Metni bağlantısı'], 'need' => ['baglanti']]],
                'bulten.onay.kvkk_baglanti' => ['Aydınlatma metni bağlantısının yazısı', 'Aydınlatma Metni', 'line', 'Yukarıdaki cümlenin içinde tıklanabilir görünür.', ['max' => 40]],
                'bulten.onay.etk' => ['Ticari elektronik ileti onayı', '{firma} tarafından e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine onay veriyorum. Onayımı dilediğim zaman geri alabilirim.', 'text', 'Hukuki bir onay metnidir; değiştirmeden önce hukuk danışmanınıza sorun.', ['vars' => ['firma'], 'need' => ['firma']]],
            ],
            '6. Gönderme' => [
                'bulten.form.gonder' => ['Gönder düğmesi', 'Haberdar ol', 'line', '', ['max' => 40]],
            ],
            '7. Kayıt alındı ekranı' => [
                'bulten.tamam.baslik' => ['Büyük başlık', 'Kaydınız alındı.', 'line', '', ['max' => 40]],
                'bulten.tamam.metin' => ['Açıklama', 'Sizi ilgilendiren bir çağrı açıldığında ya da bir programın kuralı değiştiğinde haberi bizden alacaksınız. Onayınızı istediğiniz zaman {eposta_baglanti} adresine yazarak geri alabilirsiniz.', 'text', '{eposta_baglanti} yerine şirketin e-posta adresi (tıklanabilir) gelir; silmeyin.', ['vars' => ['eposta_baglanti'], 'need' => ['eposta_baglanti']]],
                'bulten.tamam.kapat' => ['Kapat düğmesi', 'Kapat', 'line', '', ['max' => 20]],
                'bulten.tamam.damga' => ['Damga yazısı', 'KAYDEDİLDİ', 'line', 'Kayıttan sonra kâğıda basılan damganın büyük yazısı. Damgaya sığması için kısa tutun; altına bugünün tarihi basılır.', ['max' => 14]],
            ],
            '8. Abonelere giden e-postaların alt bilgisi' => [
                'bulten.posta.neden' => ['E-postanın neden geldiğini açıklayan cümle', 'Bu e-postayı, web sitemizdeki bülten formunu doldururken verdiğiniz onay nedeniyle alıyorsunuz.', 'text', 'Her bülten e-postasının altında, firma adı, adres ve iletişim bilgisinden sonra yer alır. Yasal bir bilgilendirmedir; kaldırmayın.'],
                'bulten.posta.ayril' => ['Ayrılma bağlantısından önceki cümle', 'Bu e-postaları artık almak istemiyorsanız abonelikten ücretsiz olarak ayrılabilirsiniz:', 'text', 'Ayrılma bağlantısı bu cümlenin hemen ardına eklenir.'],
                'bulten.posta.baglanti' => ['Ayrılma bağlantısının yazısı', 'Abonelikten ayrıl', 'line', '', ['max' => 40]],
                'bulten.posta.telefon' => ['Alt bilgide telefon etiketi', 'Telefon:', 'line', 'Telefon numarasından önce yazılır.', ['max' => 20]],
                'bulten.posta.eposta' => ['Alt bilgide e-posta etiketi', 'E-posta:', 'line', 'E-posta adresinden önce yazılır.', ['max' => 20]],
            ],
        ],
    ],
];
