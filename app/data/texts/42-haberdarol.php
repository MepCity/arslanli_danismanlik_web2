<?php
/**
 * Haberdar Ol sayfası (app/pages/haberdarol.php): kesik çizgili bülten kuponu. Sayfa kenarındaki bülten penceresinin yazıları
 * "Bülten kayıt penceresi" grubundadır; il, sektör listeleri Kurumsal içerik bölümünden gelir. Hata ve gönderim iletileri Form iletileri grubundadır.
 */
return [
    'haberdarol' => [
        'label' => 'Haberdar Ol (bülten kuponu)',
        'url'   => 'haberdarol',
        'icon'  => 'envelope-simple',
        'sections' => [
            '1. Başlık' => [
                'haberdarol.hero.baslik' => ['Büyük başlık', 'Kesip gönderin.', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 50]],
                'haberdarol.hero.giris' => ['Başlığın altındaki açıklama', 'Sizi ilgilendiren bir destek çağrısı açıldığında, bir programın şartları değiştiğinde ya da takip ettiğiniz bir başvurunun son tarihi yaklaştığında e-postayla haber verelim. Kuponu bir kez doldurmanız yeterli.', 'text'],
            ],
            '2. Kuponun çevresi' => [
                'haberdarol.kupon.alan_etiket' => ['Kuponun erişilebilirlik adı', 'Haberdar Ol kuponu', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'haberdarol.kupon.sol' => ['Kupon sayfasının üst satırı, solda', '{firma_kisa} Bülteni', 'line', 'Kupon sayfasının kenarında silik görünen bir süs satırıdır.', ['vars' => ['firma_kisa'], 'max' => 40]],
                'haberdarol.kupon.sag' => ['Kupon sayfasının üst satırı, sağda', 'Kupon sayfası · {no}', 'line', '{no} yerine bu sayfanın evrak numarası gelir.', ['vars' => ['no' => 'bu sayfanın numarası'], 'need' => ['no'], 'max' => 40]],
                'haberdarol.kupon.hata' => ['Gönderim başarısız bildirimi', 'Kuponunuz gönderilemedi. Lütfen alanları kontrol edip yeniden deneyin.', 'text', 'Sayfa yenilenerek dönüldüğünde (JavaScript kapalıysa) kuponun üstünde görünür.'],
            ],
            '3. Kuponun başlığı' => [
                'haberdarol.baslik.kupon' => ['Kuponun adı', 'Kupon [eğik]Haberdar Ol[/eğik]', 'rich', 'Kuponun sol üstünde görünür.', ['max' => 40]],
                'haberdarol.baslik.adimlar_etiket' => ['Üç adım listesinin erişilebilirlik adı', 'Üç adım', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'haberdarol.baslik.adim1' => ['Birinci adım', 'Doldurun', 'line', 'Adımın numarası sayfada otomatik gelir.', ['max' => 20]],
                'haberdarol.baslik.adim2' => ['İkinci adım', 'Kesin', 'line', '', ['max' => 20]],
                'haberdarol.baslik.adim3' => ['Üçüncü adım', 'Gönderin', 'line', '', ['max' => 20]],
            ],
            '4. Kupon alanları' => [
                'haberdarol.form.ad' => ['Ad alanının etiketi', 'Adınız', 'line', '', ['max' => 30]],
                'haberdarol.form.soyad' => ['Soyad alanının etiketi', 'Soyadınız', 'line', '', ['max' => 30]],
                'haberdarol.form.eposta' => ['E-posta alanının etiketi', 'E-posta adresiniz', 'line', '', ['max' => 30]],
                'haberdarol.form.telefon' => ['Telefon alanının etiketi', 'Telefonunuz', 'line', '', ['max' => 30]],
                'haberdarol.form.il' => ['İl alanının etiketi', 'İliniz', 'line', '', ['max' => 30]],
                'haberdarol.form.il_sec' => ['İl listesinin ilk satırı', 'İl seçin', 'line', 'İller Kurumsal içerik bölümündeki listeden gelir.', ['max' => 30]],
                'haberdarol.form.sektor' => ['Sektör alanının etiketi', 'Sektörünüz', 'line', '', ['max' => 30]],
                'haberdarol.form.sektor_sec' => ['Sektör listesinin ilk satırı', 'Sektör seçin', 'line', 'Sektörler Kurumsal içerik bölümündeki “Form seçenekleri” listesinden gelir.', ['max' => 30]],
                'haberdarol.form.konular' => ['İlgilenilen konular alanının etiketi', 'İlgilendiğiniz konular', 'line', '', ['max' => 40]],
                'haberdarol.form.istege_bagli' => ['İlgilenilen konular etiketinin yanındaki not', '(isteğe bağlı)', 'line', 'Etiketin hemen yanında silik görünür.', ['max' => 24]],
                'haberdarol.form.konular_ornek' => ['İlgilenilen konular alanındaki örnek yazı', 'İlgilendiğiniz teşvik, hibe ya da danışmanlık konularını yazabilirsiniz', 'text', 'Kutu boşken soluk görünür.'],
            ],
            '5. Onay kutuları (hukuki metin)' => [
                'haberdarol.onay.kvkk' => ['Aydınlatma metni onayı', 'Kişisel verilerimin işlenmesine ilişkin {baglanti}’ni okudum.', 'text', 'Hukuki bir onay metnidir; değiştirmeden önce hukuk danışmanınıza sorun. {baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'Aydınlatma Metni bağlantısı'], 'need' => ['baglanti']]],
                'haberdarol.onay.kvkk_baglanti' => ['Aydınlatma metni bağlantısının yazısı', 'Aydınlatma Metni', 'line', 'Yukarıdaki cümlenin içinde tıklanabilir görünür.', ['max' => 40]],
                'haberdarol.onay.etk' => ['Ticari elektronik ileti onayı', '{firma} tarafından e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine onay veriyorum. Onayımı dilediğim zaman geri alabilirim.', 'text', 'Hukuki bir onay metnidir; değiştirmeden önce hukuk danışmanınıza sorun.', ['vars' => ['firma'], 'need' => ['firma']]],
            ],
            '6. Gönderme' => [
                'haberdarol.gonder.dugme' => ['Gönder düğmesi', 'Kuponu gönder', 'line', '', ['max' => 40]],
            ],
            '7. Kupon gönderildikten sonra' => [
                'haberdarol.alindi.etiket' => ['Küçük üst yazı', 'Alındı · {tarih}', 'line', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
                'haberdarol.alindi.baslik' => ['Büyük başlık', 'Kuponunuz bize ulaştı.', 'line', '', ['max' => 50]],
                'haberdarol.alindi.mesaj' => ['Başlığın altındaki ileti (sunucu ileti vermezse)', 'Sizi ilgilendiren bir çağrı açıldığında haber vereceğiz.', 'text', 'Normalde sunucunun yanıtı (Form iletileri grubundaki “Bülten kaydı alındı”) görünür; bu yazı sayfa yenilenerek dönüldüğünde görünür.'],
                'haberdarol.alindi.devam' => ['Alt not (makaleler ya da duyurular açıksa)', 'O zamana kadar {gozat} göz atabilir ya da bir sorunuz varsa {yaz}.', 'text', '{gozat} ve {yaz} yerine sırayla aşağıdaki iki bağlantı yazısı gelir; silmeyin.', ['vars' => ['gozat' => 'makaleler ya da açık çağrılar bağlantısı', 'yaz' => 'bize yazın bağlantısı'], 'need' => ['gozat', 'yaz']]],
                'haberdarol.alindi.devam_kisa' => ['Alt not (makaleler ve duyurular kapalıysa)', 'Bir sorunuz varsa {yaz}.', 'text', '{yaz} yerine “bize yazın” bağlantısı gelir; silmeyin.', ['vars' => ['yaz' => 'bize yazın bağlantısı'], 'need' => ['yaz']]],
                'haberdarol.alindi.makaleler' => ['Alt notta makaleler bağlantısının yazısı', 'makalelere', 'line', 'Yalnızca Makaleler bölümü açıksa görünür; “göz atabilir” sözüne bağlanır.', ['max' => 40]],
                'haberdarol.alindi.cagrilar' => ['Alt notta açık çağrılar bağlantısının yazısı', 'açık çağrılara', 'line', 'Makaleler kapalı, Duyurular açıksa görünür; “göz atabilir” sözüne bağlanır.', ['max' => 40]],
                'haberdarol.alindi.yaz' => ['Alt notta “bize yazın” bağlantısının yazısı', 'bize yazabilirsiniz', 'line', 'İletişim sayfasına gider.', ['max' => 40]],
            ],
            '8. Yan not: size neler gelir' => [
                'haberdarol.yan.baslik' => ['Yan notun başlığı', 'Size neler gelir?', 'line', '', ['max' => 40]],
                'haberdarol.yan.bir' => ['Birinci madde', '[kalın]Yeni açılan çağrılar.[/kalın] İlgilendiğiniz konularda bir destek çağrısı açıldığında, kimlerin başvurabileceğiyle birlikte.', 'rich'],
                'haberdarol.yan.iki' => ['İkinci madde', '[kalın]Değişen kurallar.[/kalın] Bir programın destek oranı, bütçe sınırı ya da başvuru şartı değiştiğinde.', 'rich'],
                'haberdarol.yan.uc' => ['Üçüncü madde', '[kalın]Yaklaşan kapanışlar.[/kalın] Takip ettiğiniz bir çağrının son başvuru tarihi yaklaştığında.', 'rich'],
                'haberdarol.yan.not' => ['Yan notun son cümlesi', 'Kişisel verilerinizin nasıl işlendiğini {baglanti}’nde bulabilirsiniz.', 'text', '{baglanti} yerine KVKK Aydınlatma Metni sayfasının adı, tıklanabilir olarak gelir; silmeyin.', ['vars' => ['baglanti' => 'KVKK sayfası bağlantısı'], 'need' => ['baglanti']]],
            ],
            '9. Sayfanın sonundaki bağlantı' => [
                'haberdarol.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine Misyonumuz sayfasının evrak numarası gelir. Bağlantının büyük yazısı o sayfanın adıdır (Sayfa adları).', ['vars' => ['no' => 'Misyonumuz sayfasının numarası'], 'need' => ['no'], 'max' => 50]],
            ],
            '10. Arama motorları' => [
                'haberdarol.seo.description' => ['Arama motoru açıklaması', 'Hibe ve teşvik çağrıları açıldığında, program şartları değiştiğinde ya da kapanış tarihi yaklaştığında e-postayla haber verelim. Kuponu doldurun.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['max' => 320]],
            ],
        ],
    ],
];
