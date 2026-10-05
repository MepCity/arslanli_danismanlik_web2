<?php
/**
 * Çerez Politikası sayfası (app/pages/cerez.php): hukuki metin. Sayfa, sitenin kendi kodunu tarayıp çerez, tarayıcı depolaması ve dış kaynak
 * kullanılıp kullanılmadığına göre hangi yazının basılacağını SEÇER; bu tarama sayfada kalır, yazıların kendisi burada durur.
 * Aynı maddenin “… kullanılıyorsa” ve “… kullanılmıyorsa” sürümleri ayrı kayıttır: o an hangisi geçerliyse o görünür, diğeri bekler.
 * Madde numaraları, içindekiler ve çerçeve yazıları Yasal metinler grubundadır. Hukuki metindir: değiştirmeden önce hukuk danışmanınıza sorun.
 */
return [
    'cerez' => [
        'label' => 'Çerez Politikası',
        'url'   => 'kurumsal/cerez-politikasi',
        'icon'  => 'file-text',
        'sections' => [
            '1. Sayfanın başı' => [
                'cerez.baslik.sayi' => ['Belge numarası', 'ARS-{yil}/012', 'line', 'Sayfanın üstündeki “Sayı:” satırında görünür; {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 30]],
                'cerez.baslik.konu' => ['Konu satırı', 'Çerezler ve tarayıcı verileri', 'line', 'Sayfanın üstündeki “Konu:” satırında görünür.', ['max' => 60]],
                'cerez.baslik.guncelleme' => ['Son güncelleme tarihi', '5 Ekim 2026', 'line', 'Sayfanın üstünde ve metnin sonunda görünür. Metni değiştirdiğinizde bu tarihi de güncelleyin; gün ay yıl biçiminde yazın.', ['max' => 30]],
                'cerez.baslik.baslik' => ['Büyük başlık', 'Çerez politikası', 'line', 'Sayfanın adı (menü ve alt bilgi) Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 60]],
                'cerez.baslik.giris' => ['Başlığın altındaki açıklama', 'Sitemizi gezerken tarayıcınızda neyin kaldığını ve neyin kalmadığını anlatan metin.', 'text'],
            ],
            '2. Kısaca kutusu (sağ üstte sade özet)' => [
                'cerez.kisaca.cerez_var' => ['Birinci cümle (sitede zorunlu çerez varsa)', 'Bu site yalnızca çalışması için [kalın]zorunlu[/kalın] teknik çerezler kullanır.', 'rich', 'Sitenin kodunda çerez kullanımı bulunursa görünür. Vurgulu kısım [kalın]…[/kalın] işaretleriyle yazılır.', ['max' => 300]],
                'cerez.kisaca.cerez_yok' => ['Birinci cümle (sitede çerez yoksa)', 'Bu site tarayıcınıza [kalın]çerez yerleştirmez[/kalın].', 'rich', 'Sitenin kodunda çerez kullanımı bulunmazsa görünür. Vurgulu kısım [kalın]…[/kalın] işaretleriyle yazılır.', ['max' => 300]],
                'cerez.kisaca.analiz' => ['İkinci cümle: analiz ve reklam aracı olmadığı', 'Ziyaretinizi izleyen analiz, reklam ya da sosyal medya aracı yok.', 'rich', 'Her zaman görünür.', ['max' => 300]],
                'cerez.kisaca.ucuncu' => ['Üçüncü cümle: dış sunucu kullanılmadığı', 'Yazı tipleri ve kodlar kendi sunucumuzdan gelir; ziyaretiniz [kalın]üçüncü taraflara[/kalın] bildirilmez.', 'rich', 'Sitede dış sunucudan yüklenen bir betik ya da stil dosyası yoksa görünür. Vurgulu kısım [kalın]…[/kalın] işaretleriyle yazılır.', ['max' => 300]],
                'cerez.kisaca.depolama' => ['Dördüncü cümle: tarayıcıda hatırlanan seçimler', 'Bazı sayfalar yaptığınız seçimleri yalnızca kendi tarayıcınızda hatırlar; bu bilgi bize gelmez.', 'rich', 'Sitenin bazı sayfaları tarayıcı depolamasını kullanıyorsa görünür.', ['max' => 300]],
                'cerez.kisaca.form' => ['Beşinci cümle: form gönderirken IP adresi', 'Form gönderirseniz kötüye kullanımı önlemek için IP adresiniz işlenir. Ayrıntısı {kvkk_baglanti}’nde.', 'rich', 'Her zaman görünür. {kvkk_baglanti} yerine KVKK Aydınlatma Metni sayfasının adı, tıklanabilir olarak gelir; silmeyin.', ['vars' => ['kvkk_baglanti' => 'KVKK sayfası bağlantısı'], 'need' => ['kvkk_baglanti'], 'max' => 300]],
            ],
            '3. Madde 1: Amaç ve kapsam' => [
                'cerez.m1.baslik' => ['Madde başlığı', 'Amaç ve kapsam', 'line', 'Hukuki metin; hukuk danışmanınıza sorun. Başlık hem maddenin üstünde hem Maddeler listesinde görünür.', ['max' => 80]],
                'cerez.m1.f1' => ['1. fıkra: politikanın konusu', 'Bu politika, {firma} tarafından yayımlanan {site_adresi} internet sitesinin, ziyaretiniz sırasında tarayıcınızda ve cihazınızda hangi bilgileri sakladığını ve hangilerini saklamadığını açıklar.', 'text', 'Hukuki metin; hukuk danışmanınıza sorun. {site_adresi} yerine sitenin adresi (https:// olmadan) gelir.', ['vars' => ['firma', 'site_adresi' => 'sitenin adresi', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m1.f2' => ['2. fıkra: formlardaki verilerin ayrı düzenlendiği', 'Formlar aracılığıyla bize ilettiğiniz kişisel verilerin işlenmesi {kvkk_baglanti}’nde ayrıca düzenlenmiştir.', 'text', '{kvkk_baglanti} yerine KVKK Aydınlatma Metni sayfasının adı, tıklanabilir olarak gelir; silmeyin.', ['vars' => ['kvkk_baglanti' => 'KVKK sayfası bağlantısı', 'firma', 'firma_kisa', 'eposta_baglanti'], 'need' => ['kvkk_baglanti'], 'max' => 2000]],
                'cerez.m1.sade' => ['Sade Türkçesi', 'Bu sayfa, sitenin cihazınızda ne bıraktığını anlatır. Formlara yazdıklarınız başka bir sayfanın konusu.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '4. Madde 2: Çerez nedir' => [
                'cerez.m2.baslik' => ['Madde başlığı', 'Çerez nedir', 'line', 'Hukuki metin; hukuk danışmanınıza sorun.', ['max' => 80]],
                'cerez.m2.f1' => ['Fıkra: çerezin tanımı', 'Çerezler, bir internet sitesi ziyaret edildiğinde tarayıcı aracılığıyla cihaza kaydedilen ve sonraki ziyaretlerde siteye geri gönderilebilen küçük metin dosyalarıdır. Oturumu açık tutmak, tercihleri hatırlamak, ziyaretçi davranışını ölçmek ya da reklam göstermek amacıyla kullanılabilirler.', 'text', 'Hukuki metin; hukuk danışmanınıza sorun.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m2.sade' => ['Sade Türkçesi', 'Sitenin tarayıcınıza bıraktığı küçük not kâğıtları. Bazıları işe yarar, bazıları sizi takip etmek içindir.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '5. Madde 3: Bu sitede kullanılan çerezler' => [
                'cerez.m3.baslik' => ['Madde başlığı', 'Bu sitede kullanılan çerezler', 'line', 'Hukuki metin; hukuk danışmanınıza sorun. Bu madde, sitenin kodu taranarak “çerez kullanılıyorsa” ya da “çerez kullanılmıyorsa” yazılarından birini gösterir.', ['max' => 80]],
                'cerez.m3.f1_var' => ['1. fıkra (sitede çerez kullanılıyorsa)', 'Sitemiz yalnızca güvenli ve doğru çalışması için zorunlu olan teknik çerezleri kullanır. Analiz, reklam, hedefleme ya da sosyal medya çerezi kullanılmaz.', 'text', 'Hukuki metin. Yalnızca sitenin kodunda çerez kullanımı bulunursa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m3.f2_var' => ['2. fıkra (sitede çerez kullanılıyorsa)', 'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti kendi teknik çerezlerini kullanırsa bu madde buna göre güncellenir.', 'text', 'Yalnızca sitenin kodunda çerez kullanımı bulunursa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m3.sade_var' => ['Sade Türkçesi (sitede çerez kullanılıyorsa)', 'Yalnızca sitenin çalışması için gerekenler var. Sizi takip eden çerez yok.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
                'cerez.m3.f1_yok' => ['1. fıkra (sitede çerez kullanılmıyorsa)', 'Sitemiz tarayıcınıza çerez yerleştirmez. Oturum çerezi, tercih çerezi, analiz çerezi, reklam ya da hedefleme çerezi ve sosyal medya çerezi kullanılmaz; bu nedenle sitede bir çerez onay penceresi de bulunmaz.', 'text', 'Hukuki metin. Sitenin kodunda çerez kullanımı bulunmazsa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m3.f2_yok' => ['2. fıkra (sitede çerez kullanılmıyorsa)', 'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti (örneğin saldırı koruması) kendi teknik çerezlerini kullanmaya başlarsa bu madde buna göre güncellenir.', 'text', 'Sitenin kodunda çerez kullanımı bulunmazsa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m3.sade_yok' => ['Sade Türkçesi (sitede çerez kullanılmıyorsa)', 'Çerez yok. O yüzden size “çerezleri kabul ediyor musunuz?” diye sormuyoruz.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '6. Madde 4: Formlar ve güvenlik kayıtları' => [
                'cerez.m4.baslik' => ['Madde başlığı', 'Formlar ve güvenlik kayıtları', 'line', 'Hukuki metin; hukuk danışmanınıza sorun.', ['max' => 80]],
                'cerez.m4.f1' => ['1. fıkra: gönderimin insandan geldiğini ayırt eden belirteç', 'İletişim, Haberdar Ol, bülten ve iş başvurusu formlarında, gönderimin bir insan tarafından yapıldığını ayırt etmek için forma imzalı, tek kullanımlık bir belirteç eklenir; forma ilk dokunduğunuzda tarayıcınız küçük bir hesaplama yapıp sonucunu da forma yazar. Bu bilgiler çerez olarak saklanmaz; formun içinde durur ve yalnızca formu gönderdiğinizde bize iletilir. Kullanılmış belirteçlerin özeti sunucumuzda en çok iki saat tutulur.', 'text', 'Hukuki metin; hukuk danışmanınıza sorun.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m4.f2' => ['2. fıkra: IP adresinin özeti', 'Kısa sürede çok sayıda gönderim yapılmasını engellemek için, form gönderildiğinde IP adresinizin özeti (hash) ve son gönderim zamanları sunucumuzda tutulur. Bu kayıt yalnızca bu amaçla kullanılır ve kısa süre sonra silinir. Gönderdiğiniz formun kendisinin (IP adresiniz dahil) nasıl saklandığı {kvkk_baglanti}’nde anlatılmıştır.', 'text', '{kvkk_baglanti} yerine KVKK Aydınlatma Metni sayfasının adı, tıklanabilir olarak gelir; silmeyin.', ['vars' => ['kvkk_baglanti' => 'KVKK sayfası bağlantısı', 'firma', 'firma_kisa', 'eposta_baglanti'], 'need' => ['kvkk_baglanti'], 'max' => 2000]],
                'cerez.m4.f3' => ['3. fıkra: formdaki bilgilerin işlenmesi', 'Formda yazdığınız bilgilerin nasıl işlendiği {kvkk_baglanti}’nde anlatılmıştır.', 'text', '{kvkk_baglanti} yerine KVKK Aydınlatma Metni sayfasının adı, tıklanabilir olarak gelir; silmeyin.', ['vars' => ['kvkk_baglanti' => 'KVKK sayfası bağlantısı', 'firma', 'firma_kisa', 'eposta_baglanti'], 'need' => ['kvkk_baglanti'], 'max' => 2000]],
                'cerez.m4.sade' => ['Sade Türkçesi', 'Formlar çerezsiz çalışır. Spam’i engellemek için form gönderdiğinizde IP adresinizin bir özetini kısa süre kullanırız.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '7. Madde 5: Tarayıcı depolama alanı' => [
                'cerez.m5.baslik' => ['Madde başlığı', 'Tarayıcı depolama alanı', 'line', 'Hukuki metin; hukuk danışmanınıza sorun. Bu madde, sitenin kodu taranarak “depolama kullanılıyorsa” ya da “kullanılmıyorsa” yazılarından birini gösterir.', ['max' => 80]],
                'cerez.m5.f1_var' => ['1. fıkra (tarayıcı depolaması kullanılıyorsa)', 'Sitenin bazı bölümleri ({sayfalar}) yaptığınız seçimleri hatırlamak için tarayıcınızın yerel depolama alanını (localStorage ya da sessionStorage) kullanır. Bu bilgi yalnızca cihazınızda tutulur, sunucumuza gönderilmez.', 'text', 'Hukuki metin. {sayfalar} yerine depolamayı kullanan sayfaların adları gelir (adlar bu grubun “Madde 5: sayfa adları” bölümünden); silmeyin.', ['vars' => ['sayfalar' => 'depolamayı kullanan sayfaların adları', 'firma', 'firma_kisa', 'eposta_baglanti'], 'need' => ['sayfalar'], 'max' => 2000]],
                'cerez.m5.f2_var' => ['2. fıkra (tarayıcı depolaması kullanılıyorsa)', 'Bu bilgileri tarayıcınızın “site verilerini temizle” seçeneğiyle dilediğiniz zaman silebilirsiniz; site bundan sonra da çalışmaya devam eder.', 'text', 'Yalnızca sitenin bazı sayfaları tarayıcı depolamasını kullanıyorsa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m5.sade_var' => ['Sade Türkçesi (tarayıcı depolaması kullanılıyorsa)', 'İşaretlediğiniz bir şeyi hatırlamak için tarayıcınıza not düşebiliriz. O not sizde kalır, bize gelmez.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
                'cerez.m5.f1_yok' => ['Fıkra (tarayıcı depolaması kullanılmıyorsa)', 'Site, tarayıcınızın yerel depolama alanına (localStorage, sessionStorage ya da IndexedDB) bilgi yazmaz.', 'text', 'Hukuki metin. Sitenin hiçbir sayfası tarayıcı depolamasını kullanmıyorsa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m5.sade_yok' => ['Sade Türkçesi (tarayıcı depolaması kullanılmıyorsa)', 'Tarayıcınıza başka türlü bir not da bırakmıyoruz.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '8. Madde 5: sayfa adları (depolama cümlesinde sayılan)' => [
                'cerez.depolama.app' => ['Sitenin geneli', 'sitenin geneli', 'line', 'Madde 5’teki cümlede, depolamayı kullanan bölümler sayılırken bu ad geçer. Yalnızca o bölüm depolamayı kullanıyorsa görünür.', ['max' => 40]],
                'cerez.depolama.home' => ['Ana sayfa', 'ana sayfa', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.about' => ['Hakkımızda sayfası', 'Hakkımızda', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.services' => ['Hizmetler sayfası', 'Hizmetler', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.service' => ['Hizmet sayfaları', 'hizmet sayfaları', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.refs' => ['Referanslar sayfası', 'Referanslar', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.blog' => ['Makaleler sayfası', 'Makaleler', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.post' => ['Makale sayfaları', 'makale sayfaları', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.contact' => ['İletişim sayfası', 'İletişim', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.signup' => ['Haberdar Ol sayfası', 'Haberdar Ol', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.bank' => ['Hesap numaraları sayfası', 'Hesap Numaralarımız', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.mission' => ['Misyon sayfası', 'Misyonumuz', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.vision' => ['Vizyon sayfası', 'Vizyonumuz', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.stones' => ['Mihenk taşları sayfası', 'Mihenk Taşlarımız', 'line', 'Madde 5’teki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
            ],
            '9. Madde 6: Dış kaynaklar ve bağlantılar' => [
                'cerez.m6.baslik' => ['Madde başlığı', 'Dış kaynaklar ve bağlantılar', 'line', 'Hukuki metin; hukuk danışmanınıza sorun.', ['max' => 80]],
                'cerez.m6.kendi' => ['Fıkra: her şeyin kendi sunucumuzdan geldiği', 'Sitedeki yazı tipleri, görseller ve kod kütüphaneleri kendi sunucumuzdan yüklenir. Sayfaları açtığınızda yazı tipi, harita ya da istatistik hizmeti sunan üçüncü taraf sunuculara istek gönderilmez.', 'text', 'Hukuki metin. Yalnızca sitede dış sunucudan yüklenen bir betik ya da stil dosyası yoksa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m6.gomulu' => ['Fıkra: gömülü üçüncü taraf içerik', 'Bazı sayfalarda üçüncü taraflara ait gömülü içerikler (örneğin harita) bulunabilir. Bu içerikler kendi çerez politikalarına göre çerez kullanabilir.', 'text', 'Yalnızca sitede gömülü çerçeve (iframe) bulunuyorsa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m6.baglanti_blog' => ['Fıkra: dış sitelere bağlantılar (Makaleler açıksa)', 'WhatsApp, Google Haritalar, makalelerdeki paylaşım düğmeleri (LinkedIn, X, WhatsApp), sosyal medya hesaplarımız ve duyurulardaki kurum sayfalarına verilen bağlantılara tıkladığınızda ilgili sitenin kendi çerez ve gizlilik politikaları geçerli olur. Bu bağlantılara tıklamadığınız sürece o sitelere hiçbir bilgi gitmez.', 'text', 'Hukuki metin. Makaleler bölümü açıksa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m6.baglanti' => ['Fıkra: dış sitelere bağlantılar (Makaleler kapalıysa)', 'WhatsApp, Google Haritalar, sosyal medya hesaplarımız ve duyurulardaki kurum sayfalarına verilen bağlantılara tıkladığınızda ilgili sitenin kendi çerez ve gizlilik politikaları geçerli olur. Bu bağlantılara tıklamadığınız sürece o sitelere hiçbir bilgi gitmez.', 'text', 'Hukuki metin. Makaleler bölümü kapalıysa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m6.sade' => ['Sade Türkçesi', 'Biz kimseye “şu kişi siteye girdi” diye haber vermiyoruz. Ama WhatsApp ya da Instagram bağlantısına tıklarsanız artık onların sitesindesiniz.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '10. Madde 7: Çerezleri yönetme' => [
                'cerez.m7.baslik' => ['Madde başlığı', 'Çerezleri yönetme', 'line', 'Hukuki metin; hukuk danışmanınıza sorun.', ['max' => 80]],
                'cerez.m7.f1_var' => ['Fıkra (sitede çerez kullanılıyorsa)', 'Kullandığınız tarayıcının ayarlarından çerezleri ve site verilerini görüntüleyebilir, silebilir ya da engelleyebilirsiniz. Zorunlu çerezlerin engellenmesi sitenin bazı işlevlerinin beklendiği gibi çalışmamasına yol açabilir.', 'text', 'Hukuki metin. Yalnızca sitenin kodunda çerez kullanımı bulunursa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m7.f1_yok' => ['Fıkra (sitede çerez kullanılmıyorsa)', 'Kullandığınız tarayıcının ayarlarından çerezleri ve site verilerini görüntüleyebilir, silebilir ya da engelleyebilirsiniz. Sitemiz çerez kullanmadığı için bu ayarları değiştirmeniz sitenin çalışmasını etkilemez.', 'text', 'Hukuki metin. Sitenin kodunda çerez kullanımı bulunmazsa görünür.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m7.sade_var' => ['Sade Türkçesi (sitede çerez kullanılıyorsa)', 'Tarayıcı ayarlarınız sizin elinizde.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
                'cerez.m7.sade_yok' => ['Sade Türkçesi (sitede çerez kullanılmıyorsa)', 'Tarayıcı ayarlarınız sizin elinizde. Neyi kapatırsanız kapatın, bu site çalışır.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '11. Madde 8: Değişiklikler ve yürürlük' => [
                'cerez.m8.baslik' => ['Madde başlığı', 'Değişiklikler ve yürürlük', 'line', 'Hukuki metin; hukuk danışmanınıza sorun.', ['max' => 80]],
                'cerez.m8.f1' => ['1. fıkra: değişiklik olursa', 'Sitede çerez ya da benzeri bir teknoloji kullanmaya başlarsak bu politikayı önceden güncelleriz ve gerekli olduğu durumlarda onayınızı isteriz.', 'text', 'Hukuki metin; hukuk danışmanınıza sorun.', ['vars' => ['firma', 'firma_kisa', 'eposta_baglanti'], 'max' => 2000]],
                'cerez.m8.f2' => ['2. fıkra: yürürlük ve iletişim', 'Bu politika yayımlandığı tarihte yürürlüğe girer. Sorularınız için {eposta_baglanti} adresine yazabilirsiniz.', 'text', '{eposta_baglanti} yerine şirketin e-posta adresi (tıklanabilir) gelir; silmeyin.', ['vars' => ['eposta_baglanti', 'firma', 'firma_kisa'], 'need' => ['eposta_baglanti'], 'max' => 2000]],
                'cerez.m8.sade' => ['Sade Türkçesi', 'Bir şey değişirse önce bu sayfayı değiştiririz.', 'text', 'Maddenin altındaki “Sade Türkçesi” açılır kutusunda görünür.', ['max' => 500]],
            ],
            '12. Sayfanın sonundaki bağlantı' => [
                'cerez.sonraki.ad' => ['Sonraki sayfa bağlantısının büyük yazısı', 'KVKK metni', 'line', 'KVKK Aydınlatma Metni sayfasına götürür; küçük yazı (Sonraki evrak) Yasal metinler grubundadır.', ['max' => 50]],
            ],
            '13. Arama motorları' => [
                'cerez.seo.description' => ['Arama motoru açıklaması', '{firma} web sitesinin tarayıcınızda ne sakladığı ve ne saklamadığı: çerez, analiz aracı ve reklam takibi kullanılmaz.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma'], 'max' => 320]],
            ],
        ],
    ],
];
