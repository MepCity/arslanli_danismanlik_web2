<?php
/**
 * Yasal metinlerin varsayılan maddeleri (KVKK Aydınlatma Metni ve Çerez Politikası).
 * Panelde Yasal metinler bölümünden eklenir, silinir, sıralanır ve düzenlenir; kaydedilen hali storage/content/legal.json içindedir.
 * Madde: title (başlık), paras (fıkralar), items (harfli liste maddeleri), plain (sade Türkçesi), anchor (dışarıdan bağlantı verilen kısa kimlik; yalnızca KVKK madde 5: 'basvuru-adaylari').
 * Fıkra/madde/sade: t (metin; [kalın] gibi işaretler ve {firma} gibi yer tutucular yazılabilir), when (yalnızca çerez politikasında: sitenin kendi kodu taranarak
 * bulunan duruma göre görünür; boşsa her zaman). Madde numaraları, içindekiler ve bağlantı kimlikleri sıradan kendiliğinden üretilir.
 */
return [
    'kvkk' => [
        [
            'title' => 'Veri sorumlusu',
            'paras' => [
                ['t' => '6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca kişisel verileriniz, veri sorumlusu sıfatıyla {firma} (“{firma_kisa}”) tarafından bu metinde açıklanan kapsamda işlenir.', 'when' => ''],
                ['t' => 'Yetkili: {yetkili}
Vergi dairesi ve numarası: {vergi_dairesi}, {vergi_no}
Adres: {adres}
E-posta: {eposta_baglanti}
Telefon: {telefon}', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Verilerinizden sorumlu olan biziz. Bize bu adreslerden ulaşırsınız.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'İşlenen kişisel veriler',
            'paras' => [
                ['t' => '[kalın]İletişim sayfasındaki dilekçe formu:[/kalın] ad soyad, firma adı, konu, mesajınızın içeriği, telefon numarası ve e-posta adresi.', 'when' => ''],
                ['t' => '[kalın]Bülten kayıt formu (Haberdar Ol sayfasındaki kupon ve sayfa kenarındaki “Bültene kayıt ol” sekmesi):[/kalın] ad, soyad, e-posta adresi, telefon numarası, bulunduğunuz il, sektörünüz, isteğe bağlı olarak ilgilendiğiniz konular ile aydınlatma metnini okuduğunuza ve ticari elektronik ileti almaya ilişkin onay kayıtlarınız (onayın tarihi ve saati dahil).', 'when' => ''],
                ['t' => '[kalın]İş başvurusu formu (Kariyer sayfası):[/kalın] ad, soyad, e-posta adresi, telefon numarası, bulunduğunuz şehir, isteğe bağlı LinkedIn bağlantınız, ilgilendiğiniz alan ya da pozisyon, deneyim süreniz, ön yazınız, yüklediğiniz özgeçmiş dosyası (PDF ya da DOCX) ile aydınlatma metnine ve isteğe bağlı saklama iznine ilişkin onay kayıtlarınız. Ayrıntısı “İş başvurusu yapan adaylar” maddesindedir.', 'when' => ''],
                ['t' => '[kalın]Tüm formlarda:[/kalın] gönderim tarihi ve saati ile IP adresiniz (işlem güvenliği). Gönderimin gerçek bir kişiden gelip gelmediğini ayırt etmek için formun ne kadar sürede doldurulduğu, tarayıcınızın bildirdiği tarayıcı türü ve dil bilgisi ile metnin kendisi, gönderim anında otomatik olarak değerlendirilir.', 'when' => ''],
                ['t' => '[kalın]Bize doğrudan ulaştığınızda:[/kalın] telefon, e-posta ya da WhatsApp üzerinden bizimle paylaştığınız bilgiler.', 'when' => ''],
                ['t' => 'Formlarda özel nitelikli kişisel veri (sağlık, din, mezhep, siyasi düşünce, biyometrik veri gibi) istemiyoruz; mesajlarınızda bu tür bilgilere yer vermemenizi rica ederiz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Formlara yazdıklarınızı (iş başvurusunda özgeçmişinizi de), ne zaman gönderdiğinizi ve hangi IP adresinden gönderdiğinizi biliyoruz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'İşleme amaçları',
            'paras' => [
                ['t' => 'Kişisel verilerinizi şu amaçlarla işleriz: talebinizi değerlendirmek ve size dönüş yapmak; işletmenize uygun olabilecek destek programları hakkında ön değerlendirme yapmak ve bir hizmet ilişkisi kurulacaksa bunun için gereken görüşmeleri yürütmek; onay vermeniz hâlinde yeni çağrılar, program değişiklikleri ve başvuru takvimleri hakkında sizi bilgilendirmek; iş başvurunuzu değerlendirmek ve sizinle bu konuda iletişim kurmak; formların kötüye kullanılmasını önlemek ve bilgi güvenliğini sağlamak; mevzuattan doğan yükümlülüklerimizi yerine getirmek.', 'when' => ''],
                ['t' => 'Verilerinizi bu amaçlar dışında kullanmayız, satmayız ve reklam amacıyla üçüncü kişilerle paylaşmayız.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Size dönmek, izin verdiyseniz çağrıları haber vermek ve formları spam’den korumak için.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Toplama yöntemi ve hukuki sebepler',
            'paras' => [
                ['t' => 'Kişisel verileriniz, internet sitemizdeki formlar aracılığıyla elektronik ortamda ve bize doğrudan ulaştığınız telefon, e-posta ve mesajlaşma kanalları üzerinden toplanır.', 'when' => ''],
                ['t' => 'Dilekçe formuyla ve doğrudan iletişimle toplanan veriler; KVKK m. 5/2-c (bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması), m. 5/2-ç (hukuki yükümlülüğümüzü yerine getirebilmemiz için zorunlu olması) ve m. 5/2-f (temel hak ve özgürlüklerinize zarar vermemek kaydıyla meşru menfaatimiz için zorunlu olması) hukuki sebeplerine dayanılarak işlenir. IP adresi ve gönderim zamanı, bilgi güvenliğine ilişkin meşru menfaatimiz kapsamında işlenir.', 'when' => ''],
                ['t' => 'Haberdar Ol ve bülten kayıt formlarıyla toplanan iletişim bilgileriniz, 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun uyarınca verdiğiniz, e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine ilişkin onaya dayanılarak bilgilendirme gönderimi için işlenir. Bu onayı dilediğiniz zaman, gerekçe göstermeden ve ücretsiz olarak geri alabilirsiniz; geri aldığınızda size yeni bilgilendirme gönderilmez. Onayınızı geri almak için {eposta_baglanti} adresine yazmanız yeterlidir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Size dönebilmek için bu bilgilere ihtiyacımız var. Bülten ise yalnızca izin verdiyseniz gelir ve izni tek e-postayla geri alırsınız.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'İş başvurusu yapan adaylar',
            'paras' => [
                ['t' => 'Kariyer sayfamızdaki formla iş başvurusu yaptığınızda, “İşlenen kişisel veriler” maddesinde sayılan bilgileriniz ve özgeçmiş dosyanız başvurunuzu değerlendirmek ve sizinle iletişim kurmak amacıyla işlenir. Özgeçmişinizde ve ön yazınızda sağlık durumu, din, siyasi görüş, kimlik belgesi numarası gibi bilgilere yer vermemenizi, fotoğraf eklemenizin zorunlu olmadığını hatırlatırız.', 'when' => ''],
                ['t' => 'Bu veriler, bir iş ilişkisi kurulması yolunda talebiniz üzerine işlem yapılması (KVKK m. 5/2-c) ve meşru menfaatimiz (m. 5/2-f) hukuki sebeplerine dayanılarak işlenir. Başvurunuz özgeçmişinizle birlikte şirketin e-posta adresine iletilir ve sunucumuzdaki kayıt dosyasında saklanır; özgeçmiş dosyanız ayrı bir klasörde tutulur. Başvurunuzu yalnızca işe alım sürecinde görevli yetkili çalışanlarımız görür; başka firmalarla paylaşılmaz.', 'when' => ''],
                ['t' => 'Başvuru formundaki ikinci onay kutusu isteğe bağlıdır. İşaretlerseniz, başvurunuzun ileride açılabilecek uygun pozisyonlar için de saklanmasına açık rızanızı vermiş olursunuz; işaretlemezseniz başvurunuz yalnızca açık olan değerlendirme süreci için kullanılır. Bu izni dilediğiniz zaman, gerekçe göstermeden {eposta_baglanti} adresine yazarak geri alabilirsiniz; geri aldığınızda başvurunuz ve özgeçmişiniz silinir.', 'when' => ''],
                ['t' => 'Saklama: Saklama izni vermediyseniz başvurunuz ve özgeçmişiniz, işe alım süreci sonuçlandığında silinir. İzin verdiyseniz izniniz süresince, siz silinmesini isteyene kadar saklanır. Şirket e-posta kutusundaki kopyalar da aynı şekilde silinir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Özgeçmişiniz yalnızca işe alım için kullanılır. “Gelecekteki pozisyonlar için sakla” kutusunu işaretlemezseniz süreç bitince sileriz; işaretlerseniz siz isteyene kadar saklarız ve tek e-postayla sildirebilirsiniz.', 'when' => ''],
            ],
            'anchor' => 'basvuru-adaylari',
        ],
        [
            'title' => 'Kişisel verilerin aktarılması',
            'paras' => [
                ['t' => 'Kişisel verileriniz, hizmetin yürütülmesi için destek aldığımız barındırma (sunucu) ve e-posta hizmeti sağlayıcılarına, ticari elektronik ileti onaylarının kaydı için mevzuatın gerektirdiği ölçüde İleti Yönetim Sistemi’ne (İYS) ve talep edilmesi hâlinde kanunen yetkili kamu kurum ve kuruluşlarına, KVKK m. 8 ve m. 9’da belirtilen şartlar çerçevesinde aktarılabilir.', 'when' => ''],
                ['t' => 'Kullandığımız barındırma ya da e-posta hizmetinin sunucuları yurt dışında bulunuyorsa, bu aktarım KVKK m. 9’da öngörülen şartlara uygun olarak yapılır. Hangi hizmet sağlayıcılarla çalıştığımızı öğrenmek için bize yazabilirsiniz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Verileriniz yalnızca sitenin ve e-postamızın çalıştığı altyapıya ve kanunen isteyen resmi kurumlara gider.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Saklama süresi',
            'paras' => [
                ['t' => 'Dilekçe formuyla ilettiğiniz bilgiler, talebiniz sonuçlanana kadar ve sonrasında olası uyuşmazlıklara karşı ilgili mevzuatta öngörülen süreler boyunca saklanır.', 'when' => ''],
                ['t' => 'Haberdar Ol ve bülten kaydınız, onayınızı geri alana kadar saklanır. Onay kayıtları, mevzuatın öngördüğü süre boyunca ayrıca tutulur.', 'when' => ''],
                ['t' => 'İş başvurularının saklanması “İş başvurusu yapan adaylar” maddesinde anlatılmıştır.', 'when' => ''],
                ['t' => 'Form gönderimlerinde kullanılan IP adresi özetleri yalnızca kısa süreli gönderim sınırlaması için kullanılır. Gönderim istenmeyen ileti (spam) olarak değerlendirilirse kayıt, ilgili e-posta bildirimi gönderilmeden en çok otuz gün saklanıp silinir. Saklama süresi dolan veriler silinir, yok edilir ya da anonim hâle getirilir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'İşimiz bittiğinde ve kanunun saymamızı istediği süre dolduğunda verilerinizi sileriz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Haklarınız',
            'paras' => [
                ['t' => 'KVKK m. 11 uyarınca veri sorumlusuna başvurarak şu haklarınızı kullanabilirsiniz:', 'when' => ''],
            ],
            'items' => [
                ['t' => 'Kişisel verilerinizin işlenip işlenmediğini öğrenme,', 'when' => ''],
                ['t' => 'Kişisel verileriniz işlenmişse buna ilişkin bilgi talep etme,', 'when' => ''],
                ['t' => 'Kişisel verilerinizin işlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme,', 'when' => ''],
                ['t' => 'Yurt içinde veya yurt dışında kişisel verilerinizin aktarıldığı üçüncü kişileri bilme,', 'when' => ''],
                ['t' => 'Kişisel verilerinizin eksik veya yanlış işlenmiş olması hâlinde bunların düzeltilmesini isteme,', 'when' => ''],
                ['t' => 'KVKK m. 7’de öngörülen şartlar çerçevesinde kişisel verilerinizin silinmesini veya yok edilmesini isteme,', 'when' => ''],
                ['t' => '(d) ve (e) bentleri uyarınca yapılan işlemlerin, kişisel verilerinizin aktarıldığı üçüncü kişilere bildirilmesini isteme,', 'when' => ''],
                ['t' => 'İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme,', 'when' => ''],
                ['t' => 'Kişisel verilerinizin kanuna aykırı olarak işlenmesi sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme.', 'when' => ''],
            ],
            'plain' => [
                ['t' => 'Hakkınızda ne bildiğimizi sorabilir, yanlışsa düzeltmemizi, gereksizse silmemizi isteyebilirsiniz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Başvuru yöntemi',
            'paras' => [
                ['t' => 'Haklarınıza ilişkin taleplerinizi, kimliğinizi tespit etmemize imkân verecek bilgilerle birlikte yukarıdaki adresimize yazılı olarak ya da daha önce bize bildirdiğiniz ve kayıtlarımızda bulunan e-posta adresinizden {eposta_baglanti} adresine iletebilirsiniz.', 'when' => ''],
                ['t' => 'Başvurunuz, niteliğine göre en kısa sürede ve en geç otuz gün içinde sonuçlandırılır; işlem kural olarak ücretsizdir. Formu gönderdiğiniz hâlde mesajınıza dönüş almadıysanız, otomatik süzgecimiz mesajınızı yanlışlıkla istenmeyen ileti saymış olabilir; telefonla ya da e-postayla bize ulaşmanız yeterlidir. Başvurunuzun reddedilmesi, verilen cevabı yetersiz bulmanız ya da süresinde cevap verilmemesi hâlinde Kişisel Verileri Koruma Kurulu’na şikâyette bulunabilirsiniz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Bize yazın; en geç otuz gün içinde, ücretsiz cevap veririz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Yürürlük',
            'paras' => [
                ['t' => 'Bu aydınlatma metni yayımlandığı tarihte yürürlüğe girer. Metinde değişiklik yapılması hâlinde güncel sürüm bu sayfada yayımlanır. Tarayıcınızda saklanan bilgiler için {cerez_baglanti}’na bakabilirsiniz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [],
            'anchor' => '',
        ],
    ],
    'cerez' => [
        [
            'title' => 'Amaç ve kapsam',
            'paras' => [
                ['t' => 'Bu politika, {firma} tarafından yayımlanan {site_adresi} internet sitesinin, ziyaretiniz sırasında tarayıcınızda ve cihazınızda hangi bilgileri sakladığını ve hangilerini saklamadığını açıklar.', 'when' => ''],
                ['t' => 'Formlar aracılığıyla bize ilettiğiniz kişisel verilerin işlenmesi {kvkk_baglanti}’nde ayrıca düzenlenmiştir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Bu sayfa, sitenin cihazınızda ne bıraktığını anlatır. Formlara yazdıklarınız başka bir sayfanın konusu.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Çerez nedir',
            'paras' => [
                ['t' => 'Çerezler, bir internet sitesi ziyaret edildiğinde tarayıcı aracılığıyla cihaza kaydedilen ve sonraki ziyaretlerde siteye geri gönderilebilen küçük metin dosyalarıdır. Oturumu açık tutmak, tercihleri hatırlamak, ziyaretçi davranışını ölçmek ya da reklam göstermek amacıyla kullanılabilirler.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Sitenin tarayıcınıza bıraktığı küçük not kâğıtları. Bazıları işe yarar, bazıları sizi takip etmek içindir.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Bu sitede kullanılan çerezler',
            'paras' => [
                ['t' => 'Sitemiz yalnızca güvenli ve doğru çalışması için zorunlu olan teknik çerezleri kullanır. Analiz, reklam, hedefleme ya da sosyal medya çerezi kullanılmaz.', 'when' => 'cerez_var'],
                ['t' => 'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti kendi teknik çerezlerini kullanırsa bu madde buna göre güncellenir.', 'when' => 'cerez_var'],
                ['t' => 'Sitemiz tarayıcınıza çerez yerleştirmez. Oturum çerezi, tercih çerezi, analiz çerezi, reklam ya da hedefleme çerezi ve sosyal medya çerezi kullanılmaz; bu nedenle sitede bir çerez onay penceresi de bulunmaz.', 'when' => 'cerez_yok'],
                ['t' => 'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti (örneğin saldırı koruması) kendi teknik çerezlerini kullanmaya başlarsa bu madde buna göre güncellenir.', 'when' => 'cerez_yok'],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Yalnızca sitenin çalışması için gerekenler var. Sizi takip eden çerez yok.', 'when' => 'cerez_var'],
                ['t' => 'Çerez yok. O yüzden size “çerezleri kabul ediyor musunuz?” diye sormuyoruz.', 'when' => 'cerez_yok'],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Formlar ve güvenlik kayıtları',
            'paras' => [
                ['t' => 'İletişim, Haberdar Ol, bülten ve iş başvurusu formlarında, gönderimin bir insan tarafından yapıldığını ayırt etmek için forma imzalı, tek kullanımlık bir belirteç eklenir; forma ilk dokunduğunuzda tarayıcınız küçük bir hesaplama yapıp sonucunu da forma yazar. Bu bilgiler çerez olarak saklanmaz; formun içinde durur ve yalnızca formu gönderdiğinizde bize iletilir. Kullanılmış belirteçlerin özeti sunucumuzda en çok iki saat tutulur.', 'when' => ''],
                ['t' => 'Kısa sürede çok sayıda gönderim yapılmasını engellemek için, form gönderildiğinde IP adresinizin özeti (hash) ve son gönderim zamanları sunucumuzda tutulur. Bu kayıt yalnızca bu amaçla kullanılır ve kısa süre sonra silinir. Gönderdiğiniz formun kendisinin (IP adresiniz dahil) nasıl saklandığı {kvkk_baglanti}’nde anlatılmıştır.', 'when' => ''],
                ['t' => 'Formda yazdığınız bilgilerin nasıl işlendiği {kvkk_baglanti}’nde anlatılmıştır.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Formlar çerezsiz çalışır. Spam’i engellemek için form gönderdiğinizde IP adresinizin bir özetini kısa süre kullanırız.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Tarayıcı depolama alanı',
            'paras' => [
                ['t' => 'Sitenin bazı bölümleri ({sayfalar}) yaptığınız seçimleri hatırlamak için tarayıcınızın yerel depolama alanını (localStorage ya da sessionStorage) kullanır. Bu bilgi yalnızca cihazınızda tutulur, sunucumuza gönderilmez.', 'when' => 'depolama_var'],
                ['t' => 'Bu bilgileri tarayıcınızın “site verilerini temizle” seçeneğiyle dilediğiniz zaman silebilirsiniz; site bundan sonra da çalışmaya devam eder.', 'when' => 'depolama_var'],
                ['t' => 'Site, tarayıcınızın yerel depolama alanına (localStorage, sessionStorage ya da IndexedDB) bilgi yazmaz.', 'when' => 'depolama_yok'],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'İşaretlediğiniz bir şeyi hatırlamak için tarayıcınıza not düşebiliriz. O not sizde kalır, bize gelmez.', 'when' => 'depolama_var'],
                ['t' => 'Tarayıcınıza başka türlü bir not da bırakmıyoruz.', 'when' => 'depolama_yok'],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Dış kaynaklar ve bağlantılar',
            'paras' => [
                ['t' => 'Sitedeki yazı tipleri, görseller ve kod kütüphaneleri kendi sunucumuzdan yüklenir. Sayfaları açtığınızda yazı tipi, harita ya da istatistik hizmeti sunan üçüncü taraf sunuculara istek gönderilmez.', 'when' => 'dis_yok'],
                ['t' => 'Bazı sayfalarda üçüncü taraflara ait gömülü içerikler (örneğin harita) bulunabilir. Bu içerikler kendi çerez politikalarına göre çerez kullanabilir.', 'when' => 'gomulu_var'],
                ['t' => 'WhatsApp, Google Haritalar, makalelerdeki paylaşım düğmeleri (LinkedIn, X, WhatsApp), sosyal medya hesaplarımız ve duyurulardaki kurum sayfalarına verilen bağlantılara tıkladığınızda ilgili sitenin kendi çerez ve gizlilik politikaları geçerli olur. Bu bağlantılara tıklamadığınız sürece o sitelere hiçbir bilgi gitmez.', 'when' => 'blog_acik'],
                ['t' => 'WhatsApp, Google Haritalar, sosyal medya hesaplarımız ve duyurulardaki kurum sayfalarına verilen bağlantılara tıkladığınızda ilgili sitenin kendi çerez ve gizlilik politikaları geçerli olur. Bu bağlantılara tıklamadığınız sürece o sitelere hiçbir bilgi gitmez.', 'when' => 'blog_kapali'],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Biz kimseye “şu kişi siteye girdi” diye haber vermiyoruz. Ama WhatsApp ya da Instagram bağlantısına tıklarsanız artık onların sitesindesiniz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Çerezleri yönetme',
            'paras' => [
                ['t' => 'Kullandığınız tarayıcının ayarlarından çerezleri ve site verilerini görüntüleyebilir, silebilir ya da engelleyebilirsiniz. Zorunlu çerezlerin engellenmesi sitenin bazı işlevlerinin beklendiği gibi çalışmamasına yol açabilir.', 'when' => 'cerez_var'],
                ['t' => 'Kullandığınız tarayıcının ayarlarından çerezleri ve site verilerini görüntüleyebilir, silebilir ya da engelleyebilirsiniz. Sitemiz çerez kullanmadığı için bu ayarları değiştirmeniz sitenin çalışmasını etkilemez.', 'when' => 'cerez_yok'],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Tarayıcı ayarlarınız sizin elinizde.', 'when' => 'cerez_var'],
                ['t' => 'Tarayıcı ayarlarınız sizin elinizde. Neyi kapatırsanız kapatın, bu site çalışır.', 'when' => 'cerez_yok'],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Değişiklikler ve yürürlük',
            'paras' => [
                ['t' => 'Sitede çerez ya da benzeri bir teknoloji kullanmaya başlarsak bu politikayı önceden güncelleriz ve gerekli olduğu durumlarda onayınızı isteriz.', 'when' => ''],
                ['t' => 'Bu politika yayımlandığı tarihte yürürlüğe girer. Sorularınız için {eposta_baglanti} adresine yazabilirsiniz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Bir şey değişirse önce bu sayfayı değiştiririz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
    ],
];
