<?php
/**
 * İletişim sayfası (app/pages/iletisim.php): form değil, boşlukları olan bir dilekçe. Dilekçenin cümleleri boşlukların etrafında
 * parçalar hâlinde durur ("Ben [ad], [firma] adına yazıyorum."): boşluklar kutudur, sayfada sabit kalır.
 * Telefon, e-posta ve adres İletişim ve şirket ayarlarından gelir. Hata ve gönderim iletileri Form iletileri grubundadır.
 */
return [
    'iletisim' => [
        'label' => 'İletişim',
        'url'   => 'iletisim',
        'icon'  => 'envelope-simple',
        'sections' => [
            '1. Başlık' => [
                'iletisim.hero.baslik' => ['Büyük başlık', 'Bize bir dilekçe yazın.', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 60]],
                'iletisim.hero.giris' => ['Başlığın altındaki açıklama', 'Form doldurmuyorsunuz; boşlukları olan kısa bir dilekçe yazıyorsunuz. Adınızı, konunuzu ve size nasıl ulaşacağımızı yazmanız yeterli. Acelesi olan işler için telefon ve WhatsApp hemen yanında.', 'text'],
            ],
            '2. Dilekçenin üst kısmı' => [
                'iletisim.dilekce.alan_etiket' => ['Dilekçe kâğıdının erişilebilirlik adı', 'Dilekçe ve iletişim bilgileri', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.dilekce.hitap' => ['Dilekçenin hitap satırı (firma adı)', '{firma|büyük}’A', 'line', 'Dilekçenin sağ üstünde büyük harfle basılır; {firma} yerine firmanın adı gelir.', ['vars' => ['firma'], 'need' => ['firma'], 'max' => 60]],
                'iletisim.dilekce.sehir' => ['Hitap satırının altındaki şehir', 'İstanbul', 'line', 'Büyük harfle basılır.', ['max' => 30]],
                'iletisim.dilekce.konu_etiket' => ['“Konu:” satırının başı', 'Konu:', 'line', '', ['max' => 16]],
                'iletisim.dilekce.konu_son' => ['Konu satırının devamı', 'hakkında görüşme talebi.', 'line', 'Seçilen konunun hemen arkasından gelir: “Genel bilgi hakkında görüşme talebi.”', ['max' => 50]],
                'iletisim.dilekce.konu_genel' => ['Konu listesinin son seçeneği', 'Genel bilgi', 'line', 'Hizmet adlarının ardından listeye eklenir; konu seçilmemişse bu yazılır.', ['max' => 30]],
            ],
            '3. Dilekçenin birinci paragrafı' => [
                'iletisim.paragraf1.ben' => ['Ad kutusundan önceki söz', 'Ben', 'line', '', ['max' => 16]],
                'iletisim.paragraf1.ad_etiket' => ['Ad kutusunun erişilebilirlik adı', 'Adınız ve soyadınız (zorunlu)', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.paragraf1.ad_ornek' => ['Ad kutusundaki örnek yazı', 'adınız soyadınız', 'line', 'Kutu boşken soluk görünür; kutu bu yazıya göre genişliktedir, kısa tutun.', ['max' => 24]],
                'iletisim.paragraf1.firma_etiket' => ['Firma kutusunun erişilebilirlik adı', 'Firmanızın adı (isteğe bağlı)', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.paragraf1.firma_ornek' => ['Firma kutusundaki örnek yazı', 'firmanızın adı', 'line', 'Kutu boşken soluk görünür; kutu bu yazıya göre genişliktedir, kısa tutun.', ['max' => 24]],
                'iletisim.paragraf1.adina' => ['Firma kutusundan sonraki söz', 'adına yazıyorum.', 'line', '', ['max' => 40]],
                'iletisim.paragraf1.konu_etiket' => ['Konu listesinin erişilebilirlik adı', 'Konu', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.paragraf1.konu_son' => ['Konu listesinden sonraki söz', 'hakkında sizinle görüşmek istiyorum.', 'line', '', ['max' => 60]],
            ],
            '4. Mesaj kutusu' => [
                'iletisim.mesaj.etiket' => ['Mesaj kutusunun başlığı', 'Kısaca durumumuz şöyle:', 'line', '', ['max' => 50]],
                'iletisim.mesaj.ornek' => ['Mesaj kutusundaki örnek yazı', 'Ne yapmak istiyorsunuz, hangi aşamadasınız, elinizde bir çağrı metni var mı?', 'text', 'Kutu boşken soluk görünür.'],
            ],
            '5. Dilekçenin üçüncü paragrafı' => [
                'iletisim.paragraf3.bana' => ['Telefon kutusundan önceki söz', 'Bana', 'line', '', ['max' => 16]],
                'iletisim.paragraf3.telefon_etiket' => ['Telefon kutusunun erişilebilirlik adı', 'Telefon numaranız', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.paragraf3.telefon_ornek' => ['Telefon kutusundaki örnek yazı', '05xx xxx xx xx', 'line', 'Kutu boşken soluk görünür; kutu bu yazıya göre genişliktedir, kısa tutun.', ['max' => 20]],
                'iletisim.paragraf3.numara' => ['Telefon kutusundan sonraki söz', 'numarasından ya da', 'line', '', ['max' => 40]],
                'iletisim.paragraf3.eposta_etiket' => ['E-posta kutusunun erişilebilirlik adı', 'E-posta adresiniz (zorunlu)', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.paragraf3.eposta_ornek' => ['E-posta kutusundaki örnek yazı', 'ornek@firma.com', 'line', 'Kutu boşken soluk görünür; kutu bu yazıya göre genişliktedir, kısa tutun.', ['max' => 24]],
                'iletisim.paragraf3.adres' => ['E-posta kutusundan sonraki söz', 'adresinden ulaşabilirsiniz.', 'line', '', ['max' => 40]],
                'iletisim.paragraf3.arz' => ['Dilekçenin kapanış cümlesi', 'Gereğini arz ederim.', 'line', '', ['max' => 40]],
            ],
            '6. İmza satırı' => [
                'iletisim.imza.ad' => ['İmza satırında ad (yazılmamışsa)', 'Ad Soyad', 'line', 'Ziyaretçi adını yazınca bu yazının yerine adı görünür.', ['max' => 30]],
                'iletisim.imza.cizgi' => ['İmza çizgisinin altındaki yazı', 'Ad Soyad · İmza', 'line', '', ['max' => 30]],
            ],
            '7. Gönderme ve “Alındı” kaşesi' => [
                'iletisim.gonder.dugme' => ['Gönder düğmesi', 'Dilekçeyi gönder', 'line', '', ['max' => 40]],
                'iletisim.kase.baslik' => ['Kaşenin büyük yazısı', 'Alındı', 'line', 'Kaşede büyük harfle basılır; yer dar, kısa tutun.', ['max' => 14]],
                'iletisim.kase.alt' => ['Kaşenin alt satırı', '{tarih} · {firma_kisa|büyük}', 'line', '{tarih} yerine bugünün tarihi, {firma_kisa} yerine firmanın kısa adı gelir.', ['vars' => ['tarih', 'firma_kisa'], 'max' => 40]],
            ],
            '8. Sayfa yenilenerek dönüldüğünde (JavaScript kapalıysa)' => [
                'iletisim.durum.tamam' => ['Gönderim başarılı bildirimi', 'Dilekçeniz bize ulaştı. Teşekkür ederiz.', 'line', 'Dilekçenin üstünde görünür.'],
                'iletisim.durum.hata' => ['Gönderim başarısız bildirimi', 'Dilekçeniz gönderilemedi. Lütfen alanları kontrol edip yeniden deneyin ya da bizi telefonla arayın.', 'text', 'Dilekçenin üstünde görünür.'],
            ],
            '9. Gönderildikten sonra' => [
                'iletisim.tamam.etiket' => ['Küçük üst yazı', 'Dilekçe {tarih}', 'line', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
                'iletisim.tamam.baslik' => ['Büyük başlık', 'Dilekçeniz yola çıktı.', 'line', '', ['max' => 50]],
                'iletisim.tamam.mesaj' => ['Başlığın altındaki ileti (sunucu ileti vermezse)', 'Dilekçeniz bize ulaştı.', 'line', 'Normalde sunucunun yanıtı (Form iletileri grubundaki “İletişim dilekçesi alındı”) görünür; bu yazı yedektir.', ['js' => true]],
                'iletisim.tamam.yeni' => ['Yeni dilekçe düğmesi', 'Yeni bir dilekçe yazın', 'line', '', ['max' => 40]],
            ],
            '10. Yandaki iletişim kartı ve liste' => [
                'iletisim.yan.etiket' => ['Yan bölümün erişilebilirlik adı', 'Doğrudan iletişim', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'iletisim.kart.adres' => ['Kartvizitin arka yüzünde adres etiketi', 'Adres', 'line', '', ['max' => 20]],
                'iletisim.kart.harita' => ['Kartvizitin arka yüzünde harita bağlantısı', 'Haritada aç', 'line', '', ['max' => 30]],
                'iletisim.kart.whatsapp' => ['Kartvizitin arka yüzünde WhatsApp bağlantısı', 'WhatsApp’tan yazın', 'line', '', ['max' => 30]],
                'iletisim.kart.cevir' => ['Kartviziti çeviren düğme', 'Kartı çevirin', 'line', '', ['max' => 30]],
                'iletisim.liste.telefon' => ['Listede telefon etiketi', 'Telefon', 'line', '', ['max' => 20]],
                'iletisim.liste.eposta' => ['Listede e-posta etiketi', 'E-posta', 'line', '', ['max' => 20]],
                'iletisim.liste.whatsapp' => ['Listede WhatsApp etiketi', 'WhatsApp', 'line', '', ['max' => 20]],
                'iletisim.liste.whatsapp_yaz' => ['Listede WhatsApp bağlantısının yazısı', 'Mesaj yazın', 'line', '', ['max' => 30]],
                'iletisim.liste.adres' => ['Listede adres etiketi', 'Adres', 'line', '', ['max' => 20]],
            ],
            '11. Sayfanın sonundaki bağlantı' => [
                'iletisim.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine sıradaki sayfanın evrak numarası gelir. Bağlantının büyük yazısı o sayfanın adıdır (Sayfa adları).', ['vars' => ['no' => 'sıradaki sayfanın numarası'], 'need' => ['no'], 'max' => 50]],
            ],
            '12. Arama motorları' => [
                'iletisim.seo.description' => ['Arama motoru açıklaması', '{firma}’a yazın: telefon, e-posta, WhatsApp ve adres. Hibe, teşvik ve Ar-Ge desteklerine dair sorunuzu kısa bir dilekçeyle iletin.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma'], 'max' => 320]],
            ],
        ],
    ],
];
