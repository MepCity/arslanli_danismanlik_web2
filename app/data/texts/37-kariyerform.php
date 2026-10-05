<?php
/**
 * Kariyer başvuru formu (app/partials/kariyer-form.php, assets/js/pages/careers.js): "özlük dosyası". Hem Kariyer sayfasında (genel başvuru)
 * hem her ilanın sayfasında (ilana özel başvuru) aynı form basılır; buradaki yazılar ikisinde de görünür. Şehir ve deneyim listeleri
 * Kurumsal içerik bölümünden gelir. Sunucunun verdiği hata ve gönderim iletileri Form iletileri grubundadır.
 */
return [
    'kariyerform' => [
        'label' => 'Kariyer başvuru formu (Kariyer ve ilan sayfaları)',
        'url'   => 'kariyer',
        'icon'  => 'file-text',
        'sections' => [
            '1. Dosyanın sekmesi ve başlığı' => [
                'kariyerform.dosya.sekme' => ['Dosya sekmesinin yazısı', 'Özlük dosyası', 'line', 'Form ve gönderildikten sonraki ekran için ortaktır.', ['max' => 30]],
                'kariyerform.dosya.aday' => ['Sekmede ad yazılmamışken görünen yazı', 'aday adı', 'line', 'Ziyaretçi adını yazınca bu yazının yerine adı görünür.', ['max' => 24]],
                'kariyerform.dosya.hata' => ['Gönderim başarısız bildirimi', 'Başvurunuz gönderilemedi. Lütfen alanları kontrol edip yeniden deneyin ya da bizi telefonla arayın.', 'text', 'Sayfa yenilenerek dönüldüğünde (JavaScript kapalıysa) formun üstünde görünür.'],
                'kariyerform.dosya.baslik' => ['Formun başlığı', 'Aday başvuru formu', 'line', '', ['max' => 40]],
                'kariyerform.dosya.tarih' => ['Tarih etiketi', 'Tarih', 'line', 'Yanında bugünün tarihi görünür.', ['max' => 16]],
                'kariyerform.dosya.zorunlu' => ['Zorunlu alan açıklaması', 'Yıldızlı ({yildiz}) alanların doldurulması zorunludur.', 'text', '{yildiz} yerine kırmızı yıldız işareti gelir; silmeyin.', ['vars' => ['yildiz' => 'kırmızı yıldız işareti'], 'need' => ['yildiz']]],
                'kariyerform.dosya.istege_bagli' => ['Alan etiketlerinin yanındaki “isteğe bağlı” notu', '(isteğe bağlı)', 'line', 'LinkedIn, pozisyon, deneyim ve ön yazı alanlarının etiketinin yanında silik görünür.', ['max' => 24]],
            ],
            '2. A bölümü: kimlik ve iletişim' => [
                'kariyerform.kimlik.bant' => ['Bölüm şeridinin yazısı', 'Kimlik ve iletişim', 'line', 'Bölümün harfi (A) sayfada otomatik gelir.', ['max' => 40]],
                'kariyerform.kimlik.ad' => ['Ad alanının etiketi', 'Ad', 'line', 'Alanın numarası ve zorunlu işareti sayfada otomatik gelir.', ['max' => 30]],
                'kariyerform.kimlik.ad_ornek' => ['Ad alanındaki örnek yazı', 'Örn: Ahmet', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'kariyerform.kimlik.soyad' => ['Soyad alanının etiketi', 'Soyad', 'line', '', ['max' => 30]],
                'kariyerform.kimlik.soyad_ornek' => ['Soyad alanındaki örnek yazı', 'Örn: Yılmaz', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'kariyerform.kimlik.eposta' => ['E-posta alanının etiketi', 'E-posta adresi', 'line', '', ['max' => 30]],
                'kariyerform.kimlik.eposta_ornek' => ['E-posta alanındaki örnek yazı', 'ornek@alanadi.com', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'kariyerform.kimlik.telefon' => ['Telefon alanının etiketi', 'Telefon numarası', 'line', '', ['max' => 30]],
                'kariyerform.kimlik.telefon_ornek' => ['Telefon alanındaki örnek yazı', '0532 000 00 00', 'line', 'Kutu boşken soluk görünür.', ['max' => 30]],
                'kariyerform.kimlik.sehir' => ['Şehir alanının etiketi', 'Şehir', 'line', '', ['max' => 30]],
                'kariyerform.kimlik.sehir_sec' => ['Şehir listesinin ilk satırı', 'Şehir seçin', 'line', 'Şehirler Kurumsal içerik bölümündeki “Form seçenekleri” listesinden gelir.', ['max' => 30]],
                'kariyerform.kimlik.linkedin' => ['LinkedIn alanının etiketi', 'LinkedIn profili', 'line', '', ['max' => 30]],
                'kariyerform.kimlik.linkedin_ornek' => ['LinkedIn alanındaki örnek yazı', 'https://www.linkedin.com/in/profil', 'line', 'Kutu boşken soluk görünür.', ['max' => 60]],
            ],
            '3. B bölümü: mesleki bilgiler' => [
                'kariyerform.meslek.bant' => ['Bölüm şeridinin yazısı', 'Mesleki bilgiler', 'line', 'Bölümün harfi (B) sayfada otomatik gelir.', ['max' => 40]],
                'kariyerform.meslek.pozisyon' => ['İlgilenilen alan alanının etiketi (genel başvuru)', 'İlgilenilen alan / pozisyon', 'line', 'Yalnızca genel başvuruda görünür.', ['max' => 40]],
                'kariyerform.meslek.pozisyon_ornek' => ['İlgilenilen alan alanındaki örnek yazı', 'Örn: Teşvik danışmanlığı', 'line', 'Kutu boşken soluk görünür.', ['max' => 40]],
                'kariyerform.meslek.staj' => ['İlgilenilen alan önerilerinde “Staj” seçeneği', 'Staj', 'line', 'Ziyaretçi kutuya yazarken hizmet adlarının yanında önerilir.', ['max' => 30]],
                'kariyerform.meslek.pozisyon_ilan' => ['Başvurulan pozisyon etiketi (ilana özel başvuru)', 'Başvurulan pozisyon', 'line', 'Yalnızca bir ilanın sayfasında, ilanın adının üstünde görünür.', ['max' => 40]],
                'kariyerform.meslek.deneyim' => ['Deneyim alanının etiketi', 'Deneyim süresi', 'line', '', ['max' => 30]],
                'kariyerform.meslek.deneyim_sec' => ['Deneyim listesinin ilk satırı', 'Deneyim seçin', 'line', 'Seçenekler Kurumsal içerik bölümündeki “Form seçenekleri” listesinden gelir.', ['max' => 30]],
            ],
            '4. C bölümü: ekler' => [
                'kariyerform.ek.bant' => ['Bölüm şeridinin yazısı', 'Ekler', 'line', 'Bölümün harfi (C) sayfada otomatik gelir.', ['max' => 40]],
                'kariyerform.ek.cv' => ['Özgeçmiş alanının etiketi', 'Özgeçmiş (CV)', 'line', 'Alanın numarası (Ek-1) ve zorunlu işareti sayfada otomatik gelir.', ['max' => 30]],
                'kariyerform.ek.sec' => ['Dosya seçme kutusundaki yazı', 'Özgeçmişinizi seçmek için tıklayın ya da buraya sürükleyin', 'line', '', ['max' => 80]],
                'kariyerform.ek.kural' => ['Dosya seçme kutusundaki kural', 'Yalnızca PDF ve DOCX, en fazla 5 MB.', 'line', 'Kuralın kendisini (PDF ve DOCX, 5 MB) bu yazı değiştirmez; yalnızca söylenişini değiştirir.', ['max' => 60]],
                'kariyerform.ek.kaldir' => ['Seçilen dosyayı kaldıran düğme', 'Kaldır', 'line', '', ['max' => 20]],
                'kariyerform.ek.kaldir_etiket' => ['Kaldır düğmesinin erişilebilirlik adı', 'Seçilen dosyayı kaldır', 'line', 'Görünmez; ekran okuyucular içindir.'],
            ],
            '5. D bölümü: ön yazı' => [
                'kariyerform.yazi.bant' => ['Bölüm şeridinin yazısı', 'Ön yazı', 'line', 'Bölümün harfi (D) sayfada otomatik gelir.', ['max' => 40]],
                'kariyerform.yazi.etiket' => ['Ön yazı alanının etiketi', 'Ön yazı / mesaj', 'line', '', ['max' => 40]],
                'kariyerform.yazi.ornek' => ['Ön yazı alanındaki örnek yazı', 'Başvurunuzla ilgili iletmek istediğiniz ön yazıyı veya açıklamayı buraya yazabilirsiniz', 'text', 'Kutu boşken soluk görünür.'],
            ],
            '6. E bölümü: onaylar (hukuki metin)' => [
                'kariyerform.onay.bant' => ['Bölüm şeridinin yazısı', 'Onaylar', 'line', 'Bölümün harfi (E) sayfada otomatik gelir.', ['max' => 40]],
                'kariyerform.onay.kvkk' => ['Aydınlatma metni onayı', 'Kişisel verilerimin {baglanti} kapsamında işlenmesini ve iş başvurusu değerlendirme sürecinde saklanmasını onaylıyorum.', 'text', 'Hukuki bir onay metnidir; değiştirmeden önce hukuk danışmanınıza sorun. {baglanti} yerine bir sonraki kutudaki bağlantı yazısı gelir; silmeyin.', ['vars' => ['baglanti' => 'Aydınlatma Metni bağlantısı'], 'need' => ['baglanti']]],
                'kariyerform.onay.kvkk_baglanti' => ['Aydınlatma metni bağlantısının yazısı', 'Aydınlatma Metni', 'line', 'Yukarıdaki cümlenin içinde tıklanabilir görünür.', ['max' => 40]],
                'kariyerform.onay.saklama' => ['Başvurunun saklanmasına izin kutusu', 'Başvurumun, ileride açılabilecek uygun pozisyonlar için de saklanmasına izin veriyorum.', 'text', 'Hukuki bir onay metnidir; değiştirmeden önce hukuk danışmanınıza sorun.'],
                'kariyerform.onay.istege_bagli' => ['Saklama izninin yanındaki not', '(İsteğe bağlı)', 'line', '', ['max' => 24]],
            ],
            '7. Gönderme' => [
                'kariyerform.gonder.dugme' => ['Gönder düğmesi', 'Başvurumu gönder', 'line', '', ['max' => 40]],
            ],
            '8. Alan hatalarının tarayıcıdaki iletileri' => [
                'kariyerform.dogrulama.ad' => ['Ad boş bırakılırsa', 'Adınızı yazın.', 'line', 'Alanın altında kırmızı görünür.', ['js' => true]],
                'kariyerform.dogrulama.soyad' => ['Soyad boş bırakılırsa', 'Soyadınızı yazın.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.eposta_bos' => ['E-posta boş bırakılırsa', 'E-posta adresinizi yazın.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.eposta_hatali' => ['E-posta geçersizse', 'Geçerli bir e-posta adresi yazın.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.telefon_bos' => ['Telefon boş bırakılırsa', 'Telefon numaranızı yazın.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.telefon_hatali' => ['Telefon geçersizse', 'Telefonu 0532 000 00 00 gibi yazın.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.sehir' => ['Şehir seçilmezse', 'Şehrinizi seçin.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.linkedin' => ['LinkedIn adresi geçersizse', 'Adres https:// ile başlamalı.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.kvkk' => ['Aydınlatma metni onayı işaretlenmezse', 'Devam etmek için Aydınlatma Metni onayı gerekli.', 'line', '', ['js' => true]],
                'kariyerform.dogrulama.cv' => ['Özgeçmiş eklenmezse', 'Özgeçmişinizi ekleyin.', 'line', '', ['js' => true]],
            ],
            '9. Özgeçmiş dosyası iletileri (tarayıcıda)' => [
                'kariyerform.dosya_ileti.tur' => ['Dosya PDF ya da DOCX değilse', 'Yalnızca PDF ya da DOCX dosyası yükleyebilirsiniz.', 'line', 'Dosya kutusunun altında kırmızı görünür.', ['js' => true]],
                'kariyerform.dosya_ileti.boyut' => ['Dosya 5 MB’tan büyükse', 'Dosya en fazla 5 MB olabilir. Seçtiğiniz dosya {boyut}.', 'line', '{boyut} yerine seçilen dosyanın boyutu gelir (örn. 7,3 MB); silmeyin.', ['js' => true, 'vars' => ['boyut' => 'seçilen dosyanın boyutu'], 'need' => ['boyut']]],
                'kariyerform.dosya_ileti.surukle' => ['Dosya sürüklenemezse', 'Dosya sürüklenemedi. Lütfen tıklayarak seçin.', 'line', '', ['js' => true]],
            ],
            '10. Gönderildikten sonra' => [
                'kariyerform.tamam.kayitli' => ['Sekmede ad yoksa görünen yazı', 'kayıtlı', 'line', 'Başvuru gönderildikten sonra dosyanın sekmesinde adayın adı görünür; ad bulunamazsa bu yazı kalır.', ['max' => 24]],
                'kariyerform.tamam.etiket' => ['Küçük üst yazı', 'Başvuru · {tarih}', 'line', '{tarih} yerine bugünün tarihi gelir.', ['vars' => ['tarih'], 'need' => ['tarih'], 'max' => 40]],
                'kariyerform.tamam.baslik' => ['Büyük başlık', 'Başvurunuz alındı.', 'line', '', ['max' => 40]],
                'kariyerform.tamam.mesaj' => ['Başlığın altındaki ileti (genel başvuru)', 'Teşekkür ederiz. Başvurunuzu inceledikten sonra uygun bir pozisyon olduğunda sizinle iletişime geçeceğiz.', 'text', 'Bu yazı sayfa yenilenerek dönüldüğünde görünür; normalde sunucunun yanıtı (Form iletileri grubu) görünür.'],
                'kariyerform.tamam.mesaj_ilan' => ['Başlığın altındaki ileti (ilana özel başvuru)', 'Teşekkür ederiz. “{ilan}” başvurunuz özgeçmişinizle birlikte ekibimize ulaştı; inceledikten sonra sizinle iletişime geçeceğiz.', 'text', '{ilan} yerine ilanın adı gelir; silmeyin. Bu yazı sayfa yenilenerek dönüldüğünde görünür; normalde sunucunun yanıtı (Form iletileri grubu) görünür.', ['vars' => ['ilan' => 'ilanın adı'], 'need' => ['ilan']]],
                'kariyerform.tamam.ana_sayfa' => ['Ana sayfaya dönüş düğmesi', 'Ana sayfaya dönün', 'line', '', ['max' => 40]],
                'kariyerform.tamam.kase_baslik' => ['Kaşenin büyük yazısı', 'Dosyaya eklendi', 'line', 'Kaşede büyük harfle basılır; yer dar, kısa tutun.', ['max' => 18]],
                'kariyerform.tamam.kase_alt' => ['Kaşenin alt satırı', '{tarih} · {firma_kisa|büyük}', 'line', '{tarih} yerine bugünün tarihi, {firma_kisa} yerine firmanın kısa adı gelir.', ['vars' => ['tarih', 'firma_kisa'], 'max' => 40]],
            ],
        ],
    ],
];
