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
                ['t' => '[kalın]İletişim sayfasındaki dilekçe formu:[/kalın] ad soyad, e-posta adresi ve mesajınızın içeriği (zorunlu); firma adı, konu ve telefon numarası (isteğe bağlı).', 'when' => ''],
                ['t' => '[kalın]Bülten kayıt formu (Haberdar Ol sayfasındaki kupon ve sayfa kenarındaki “Bültene kayıt ol” sekmesi):[/kalın] ad, soyad, e-posta adresi, telefon numarası, il ve sektör (zorunlu); isteğe bağlı olarak ilgilendiğiniz konular. Bunlara ek olarak, aydınlatma metnini okuduğunuza ve ticari elektronik ileti almaya ilişkin iki ayrı onay kaydınız, her biri onayı verdiğiniz tarih ve saatle birlikte saklanır.', 'when' => ''],
                ['t' => '[kalın]İş başvurusu formu (Kariyer sayfası ve iş ilanı sayfaları):[/kalın] ad, soyad, e-posta adresi, telefon numarası, bulunduğunuz şehir ve özgeçmiş dosyanız (PDF ya da DOCX, en çok 5 MB) (zorunlu); LinkedIn bağlantınız, ilgilendiğiniz alan ya da pozisyon, deneyim süreniz ve ön yazınız (isteğe bağlı). Aydınlatma metnine ilişkin onay kaydınız onayın tarih ve saatiyle, isteğe bağlı saklama izniniz ise işaretlediyseniz tarih ve saatiyle, işaretlemediyseniz “Hayır” olarak saklanır. Bir iş ilanının sayfasından başvuruyorsanız başvurunuza o ilanın kimliği ve başlığı da yazılır. Ayrıntısı “İş başvurusu yapan adaylar” maddesindedir.', 'when' => ''],
                ['t' => '[kalın]Tüm formlarda:[/kalın] gönderim tarihi ve saati ile IP adresiniz (işlem güvenliği ve kötüye kullanımın önlenmesi için). Gönderimin gerçek bir kişiden gelip gelmediğini ayırt etmek için formun ne kadar sürede doldurulduğu, tarayıcınızın bildirdiği tarayıcı türü ve dil bilgisi ile metnin kendisi, gönderim anında otomatik olarak değerlendirilir. Bu değerlendirmede bakılan bilgilerin kendisi saklanmaz; yalnızca şüpheli bulunan kayda değerlendirmenin puanı ve kısa nedenleri yazılır. Ayrıntısı “İstenmeyen ileti (spam) süzgeci” maddesindedir.', 'when' => ''],
                ['t' => '[kalın]Siteyi gezerken:[/kalın] hangi sayfaya baktığınız ve sitemize hangi siteden geldiğiniz, kimliğiniz belirlenmeden günlük toplamlara eklenir; IP adresiniz ve tarayıcı bilginiz kaydedilmez. Ayrıntısı “Siteyi gezerken işlenen veriler” maddesindedir.', 'when' => ''],
                ['t' => '[kalın]Bize doğrudan ulaştığınızda:[/kalın] telefon, e-posta ya da WhatsApp üzerinden bizimle paylaştığınız bilgiler.', 'when' => ''],
                ['t' => 'Formlarda özel nitelikli kişisel veri (sağlık, din, mezhep, siyasi düşünce, biyometrik veri gibi) istemiyoruz; mesajlarınızda ve özgeçmişinizde bu tür bilgilere yer vermemenizi rica ederiz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Formlara yazdıklarınızı (iş başvurusunda özgeçmişinizi de), ne zaman gönderdiğinizi ve hangi IP adresinden gönderdiğinizi biliyoruz. Siteyi gezerken ise sizi tanıyabileceğimiz bir şey kaydetmiyoruz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'İşleme amaçları',
            'paras' => [
                ['t' => 'Kişisel verilerinizi şu amaçlarla işleriz: talebinizi değerlendirmek ve size dönüş yapmak; işletmenize uygun olabilecek destek programları hakkında ön değerlendirme yapmak ve bir hizmet ilişkisi kurulacaksa bunun için gereken görüşmeleri yürütmek; onay vermeniz hâlinde yeni çağrılar, program değişiklikleri ve başvuru takvimleri hakkında sizi bilgilendirmek ve ticari elektronik ileti göndermek; iş başvurunuzu değerlendirmek ve sizinle bu konuda iletişim kurmak; formların kötüye kullanılmasını önlemek ve bilgi güvenliğini sağlamak; sitemizin hangi sayfalarının kaç kişi tarafından gezildiğini, kimliğinizi belirlemeden ölçmek; mevzuattan doğan yükümlülüklerimizi yerine getirmek.', 'when' => ''],
                ['t' => 'Verilerinizi bu amaçlar dışında kullanmayız, satmayız ve reklam amacıyla üçüncü kişilerle paylaşmayız.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Size dönmek, izin verdiyseniz çağrıları haber vermek, başvurunuzu değerlendirmek, formları spam’den korumak ve sitenin kaç kişi tarafından gezildiğini saymak için.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Toplama yöntemi ve hukuki sebepler',
            'paras' => [
                ['t' => 'Kişisel verileriniz, internet sitemizdeki formlar ve ziyaret sayacı aracılığıyla elektronik ortamda, ayrıca bize doğrudan ulaştığınız telefon, e-posta ve mesajlaşma kanalları üzerinden toplanır.', 'when' => ''],
                ['t' => 'Dilekçe formuyla ve doğrudan iletişimle toplanan veriler; KVKK m. 5/2-c (bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması), m. 5/2-ç (hukuki yükümlülüğümüzü yerine getirebilmemiz için zorunlu olması) ve m. 5/2-f (temel hak ve özgürlüklerinize zarar vermemek kaydıyla meşru menfaatimiz için zorunlu olması) hukuki sebeplerine dayanılarak işlenir. Dilekçe formunda ayrı bir onay kutusu bulunmaz.', 'when' => ''],
                ['t' => 'Bülten kayıt formuyla toplanan iletişim bilgileriniz, 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun uyarınca verdiğiniz, e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine ilişkin onaya dayanılarak bilgilendirme gönderimi için işlenir. Bu onayı dilediğiniz zaman, gerekçe göstermeden ve ücretsiz olarak geri alabilirsiniz; geri aldığınızda size yeni bilgilendirme gönderilmez. Onayınızı geri almak için size gönderdiğimiz her e-postanın altındaki “Abonelikten ayrıl” bağlantısını kullanabilir ya da {eposta_baglanti} adresine yazabilirsiniz. Onayınızın ve geri alma işleminizin kaydı, ispat yükümlülüğümüz ve meşru menfaatimiz (m. 5/2-ç ve m. 5/2-f) kapsamında tutulur.', 'when' => ''],
                ['t' => 'Gönderimin tarihi, saati ve IP adresiniz, istenmeyen ileti süzgeci, form güvenliği ve ziyaret sayacı için işlenen bilgiler; bilgi güvenliğine ve sitenin işleyişini ölçmeye ilişkin meşru menfaatimiz (m. 5/2-f) kapsamında işlenir. İş başvurularının hukuki sebepleri “İş başvurusu yapan adaylar” maddesinde, yönetimde yapay zekâ asistanı kullanılmasının hukuki sebebi “Yönetimde yapay zekâ asistanları” maddesindedir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Size dönebilmek için bu bilgilere ihtiyacımız var. Bülten ise yalnızca izin verdiyseniz gelir ve izni tek tıkla ya da tek e-postayla geri alırsınız.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Gönderimlerin saklanması ve e-postayla iletilmesi',
            'paras' => [
                ['t' => 'Formu gönderdiğinizde, yazdığınız bilgiler gönderim tarihi, saati ve IP adresinizle birlikte şirketin bildirim e-posta adresine e-posta olarak iletilir; iş başvurusunda özgeçmiş dosyanız e-postaya eklenir. Size ayrıca otomatik bir onay e-postası gönderilmez. Bildirim e-postası, istenmeyen ileti süzgecinin şüpheli bulduğu gönderimler için gönderilmez (bkz. “İstenmeyen ileti (spam) süzgeci”).', 'when' => ''],
                ['t' => 'Bildirim e-postasının yanında gönderiminiz sunucumuzdaki bir kayıt dosyasında da saklanır; böylece e-posta iletilemese bile kayıt kaybolmaz. Bülten kayıtları (abone listesinin kaynağı), iş başvuruları ve şüpheli bulunan gönderimler her koşulda bu dosyaya yazılır; dilekçe formu gönderimlerinin bu dosyaya yazılması ise bir ayardır ve kapatılabilir. Kayıt dosyası internetten erişime kapalı bir klasördedir; kayıtları yalnızca yönetim paneline şifreyle giren yetkili çalışanlarımız görür.', 'when' => ''],
                ['t' => 'İş başvurusundaki özgeçmiş dosyanız, kayıt dosyasından ayrı bir klasörde rastgele bir adla saklanır; dosyanın türü adına değil içeriğine bakılarak doğrulanır. Dosyanın özgün adı başvuru kaydına yazılır.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Formu gönderince bilgileriniz şirketin e-posta kutusuna düşer ve sunucumuzda bir kayıt olarak durur. Size ayrıca onay e-postası göndermiyoruz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'İstenmeyen ileti (spam) süzgeci',
            'paras' => [
                ['t' => 'Formlar programlarla doldurulup kötüye kullanılabildiğinden, her gönderim bildirim e-postası gönderilmeden önce otomatik olarak puanlanır; bu puanlamayı bir çalışanımız tek tek yapmaz. Süzgeç aşağıdakilere bakar. Tek bir işaret gönderimi şüpheli saymaya yetmez, birkaçı bir araya gelince puan sınırı aşılır; gizli alanın doldurulması ise tek başına yeter:', 'when' => ''],
            ],
            'items' => [
                ['t' => 'Formun açılışından gönderilişine kadar geçen süre; gönderimin gerçek bir tarayıcıdan yapılıp yapılmadığı (tarayıcının forma yazdığı küçük hesaplama, tarayıcı türü, dil bilgisi, isteğin başka bir siteden gelip gelmediği),', 'when' => ''],
                ['t' => 'İnsanların görmediği gizli bir alanın doldurulup doldurulmadığı,', 'when' => ''],
                ['t' => 'Mesajda bağlantı (web adresi) bulunup bulunmadığı ve arama motoru optimizasyonu, veri ya da yazılım satışı, kumar ve kredi gibi reklam kalıpları,', 'when' => ''],
                ['t' => 'Metnin İngilizce ya da yabancı alfabeyle yazılmış olması; sitemizin kendi alan adının mesajda geçmesi,', 'when' => ''],
                ['t' => 'Adın ya da firma adının rastgele harflerden ya da deneme adlarından oluşması,', 'when' => ''],
                ['t' => 'Telefon numarasının Türkiye numarasına benzemesi,', 'when' => ''],
                ['t' => 'Tek kullanımlık (geçici) e-posta adresi kullanılıp kullanılmadığı,', 'when' => ''],
                ['t' => 'Aynı metnin son 30 günde, aynı e-posta adresinin son 24 saatte birden çok kez gönderilip gönderilmediği (önceki kayıtlarla karşılaştırılır).', 'when' => ''],
            ],
            'plain' => [
                ['t' => 'Formları programlardan korumak için gönderilen her formu bir program puanlıyor. Neye baktığını yukarıda sıraladık.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Şüpheli bulunan gönderimler ve otomatik değerlendirme',
            'paras' => [
                ['t' => 'Süzgeç bir gönderimi şüpheli bulursa gönderim silinmez ve size bir uyarı gösterilmez: ekranda olağan “ulaştı” iletisini görürsünüz. Ancak gönderiminiz şirketin e-posta kutusuna iletilmez; yalnızca yönetim panelindeki “Şüpheli” listesine, neden şüpheli bulunduğu yazılarak alınır. Yetkili çalışanlarımız bu listeye bakar; gerçek bir kişiye ait olduğunu görürlerse kaydı olağan kayıtlar arasına alır ve bildirim e-postası o anda gönderilir.', 'when' => ''],
                ['t' => 'Şüpheli kayıtlar en çok 30 gün saklanır ve en yeni 300 kayıtla sınırlıdır; mesaj ve ön yazı gibi uzun metinler ilk 1000 karakteriyle tutulur. Süresi dolan kayıtlar, iş başvurusuysa özgeçmiş dosyasıyla birlikte, kendiliğinden silinir; silme işlemi forma gönderim geldikçe ara sıra ve yönetici Form kayıtları ya da İş başvuruları listesini açtığında yapılır, hiç gönderim ya da panel ziyareti olmazsa süresi dolan kayıtlar bir sonrakine kadar kalabilir.', 'when' => ''],
                ['t' => 'Bu değerlendirme tamamen otomatiktir. Formu gönderdiğiniz hâlde bir dönüş almadıysanız süzgecimiz gönderiminizi yanlışlıkla şüpheli saymış olabilir; telefonla ya da e-postayla bize ulaşmanız yeterlidir. KVKK m. 11 uyarınca, otomatik değerlendirme sonucunda aleyhinize bir sonuç doğduğunu düşünüyorsanız buna itiraz edebilirsiniz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Gerçek biri yanlışlıkla ayrılırsa mesajı panelde otuz gün bekler; bize yazarsanız çıkarırız.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Siteyi gezerken işlenen veriler',
            'paras' => [
                ['t' => 'Sitemizdeki bir sayfayı açtığınızda tarayıcınız sunucumuza küçük bir bildirim gönderir: bildirimde açtığınız sayfanın adresi ve sitemize hangi siteden geldiğiniz bulunur. Her internet isteğinde olduğu gibi IP adresiniz ve tarayıcı bilginiz de sunucuya ulaşır.', 'when' => ''],
                ['t' => 'Aynı ziyaretçiyi gün içinde bir kez sayabilmek için IP adresiniz ve tarayıcı bilginiz, her gün yenilenen rastgele bir değerle birlikte kısa bir özete çevrilir; IP adresiniz ve tarayıcı bilginiz kaydedilmez. Tek bir kaynaktan günde çok sayıda ziyaretçi yazılmasını önlemek için IP adresinizin de aynı şekilde alınmış ayrı bir kısa özeti tutulur. Bu özetler ve rastgele değer yalnızca o gün için tutulur, ertesi günün ilk sayımında silinir; bu yüzden günler arasında eşleştirilemez. Sayaç çerez ya da başka bir kalıcı tanımlayıcı kullanmaz.', 'when' => ''],
                ['t' => 'Kalıcı olarak yalnızca günlük toplamlar saklanır: günlük ziyaretçi ve sayfa görüntüleme sayısı, sayfa başına görüntüleme, ziyaretçilerin geldiği site adları (örneğin google.com; adresin tamamı değil) ve telefondan gelenlerin sayısı. Toplamlar bir kişiyle ilişkilendirilemez; bu yüzden süre sınırı olmadan tutulur. Arama motoru ve diğer yazılım robotları ile yönetim paneline giriş yapılmış tarayıcıdan yapılan ziyaretler sayılmaz; JavaScript kapalıysa ziyaret sayılmaz.', 'when' => ''],
                ['t' => 'Formlarda kullanılan imzalı ve tek kullanımlık güvenlik belirteci kişisel veri içermez; kullanılmış belirteçlerin özeti sunucuda en çok iki saat tutulur. Kısa sürede çok sayıda gönderimi sınırlamak için formu gönderdiğinizde IP adresinizin özeti ve gönderim zamanları sunucuda tutulur; bu kayıtlar gönderim sınırı dışında hiçbir amaçla kullanılmaz ve bir günden eskileri sunucu temizlik yaptığında silinir. Temizlik gönderimlerle birlikte ara sıra çalıştığı için bu kayıtlar bir günden biraz daha uzun kalabilir.', 'when' => ''],
                ['t' => 'Sitemizi barındıran sunucu, her internet sitesinde olduğu gibi bağlantı bilgilerinizi (IP adresi, istenen adres, tarayıcı bilgisi, zaman) kendi sunucu kayıtlarında tutabilir; bu kayıtlar barındırma sağlayıcısının sorumluluğundadır ve sitenin yönetim panelinde görünmez. İçerik değiştiğinde sayfa adreslerinin listesi arama motorlarına (IndexNow) bildirilebilir; bu bildirim yalnızca herkese açık sayfa adreslerini taşır, ziyaretçilere ilişkin hiçbir bilgi içermez. Sayfa paylaşıldığında görünen önizleme görselleri sitemizin herkese açık içeriğinden kendi sunucumuzda üretilir. Ziyaretçiler siteye dosya yükleyemez; yükleme yalnızca iş başvuru formundaki özgeçmiş dosyası içindir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Hangi sayfaya baktığınızı ve nereden geldiğinizi sayıyoruz ama kim olduğunuzu kaydetmiyoruz. Çerez de kullanmıyoruz. Ayrıntısı Çerez Politikası’nda.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Bülten aboneleri ve toplu e-posta',
            'paras' => [
                ['t' => 'Bülten kayıt formunu doldurup ticari elektronik ileti onayı veren kişilere, yeni çağrılar, program değişiklikleri ve başvuru takvimleri hakkında e-posta göndeririz. E-posta yalnızca bu onayı vermiş ve abonelikten ayrılmamış kişilere gider; onay kaydı bulunmayan eski form kayıtlarına gönderilmez. Bülten e-postalarının gönderimi yönetim panelinden, yetkili bir çalışanımızın başlatmasıyla yapılır. Bülten formundaki onay metninde e-posta, SMS ve telefon sayılmıştır; sitemizin toplu gönderim aracı yalnızca e-posta gönderir.', 'when' => ''],
                ['t' => 'Her bülten e-postasının altında şirket adı, adres, telefon, e-posta adresi, e-postayı neden aldığınız (“bülten formunu doldururken verdiğiniz onay”) ve “Abonelikten ayrıl” bağlantısı bulunur. Bağlantı adresinize özeldir ve tek tıkla çalışır; e-posta uygulamanızdaki (Gmail gibi) “abonelikten çık” düğmesi de aynı işi görür. Bize yazarak ya da arayarak da ayrılabilirsiniz. E-postalar, açıp açmadığınızı ya da bağlantılara tıklayıp tıklamadığınızı izlemez.', 'when' => ''],
                ['t' => 'Abonelikten ayrılmak kalıcıdır: ayrıldıktan sonra bülten formunu aynı adresle yeniden doldursanız bile e-posta almaya başlamazsınız (formu başkası da sizin adresinizle doldurmuş olabilir). Yeniden almak isterseniz bize yazmanız yeterlidir; aboneliği yetkili çalışanımız sizin isteğiniz üzerine yeniden açar.', 'when' => ''],
                ['t' => 'Ayrıldığınızda, adresinize bir daha e-posta gönderilmemesini sağlamak ve onayınızın ile geri almanızın kaydını tutmak için adresiniz ve ayrılma tarihi ayrı bir “ayrılanlar” listesine yazılır; bülten formundaki kaydınız (ad, soyad, telefon, il, sektör, ilgilendiğiniz konular, onay kayıtları) da kayıt dosyasında durmaya devam eder.', 'when' => ''],
                ['t' => 'Yapılan her toplu gönderim için bir kayıt tutulur: e-postanın konusu ve metni, alıcıların hangi süzgeçle seçildiği, gönderimi başlatan kişi ya da erişim, gönderim zamanı ve her alıcı için adı, soyadı, e-posta adresi, gönderimin sonucu (gönderildi, hata, atlandı), zamanı ve varsa hata notu.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Bülten yalnızca izin verdiyseniz gelir ve her e-postanın altındaki bağlantıyla tek tıkla ayrılırsınız. Ayrılınca adresiniz “ayrıldı” diye bizde kalır ki size bir daha yazmayalım.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'İş başvurusu yapan adaylar',
            'paras' => [
                ['t' => 'Kariyer sayfamızdaki genel başvuru formuyla ya da bir iş ilanının kendi sayfasındaki formla başvuru yaptığınızda, “İşlenen kişisel veriler” maddesinde sayılan bilgileriniz ve özgeçmiş dosyanız başvurunuzu değerlendirmek ve sizinle iletişim kurmak amacıyla işlenir. İlan sayfasından yaptığınız başvuru o ilana bağlanır; ilan sayfası dışından yapılan başvurular, belirli bir ilana bağlı olmayan genel başvurudur. İlan sonradan kapansa ya da silinse de başvurunuza yazılan ilan başlığı kayıtta durur. Özgeçmişinizde ve ön yazınızda sağlık durumu, din, siyasi görüş, kimlik belgesi numarası gibi bilgilere yer vermemenizi, fotoğraf eklemenizin zorunlu olmadığını hatırlatırız.', 'when' => ''],
                ['t' => 'Bu veriler, bir iş ilişkisi kurulması yolunda talebiniz üzerine işlem yapılması (KVKK m. 5/2-c) ve meşru menfaatimiz (m. 5/2-f) hukuki sebeplerine dayanılarak işlenir. Başvurunuz özgeçmişinizle birlikte şirketin e-posta adresine iletilir ve sunucumuzdaki kayıt dosyasında saklanır; özgeçmiş dosyanız ayrı bir klasörde tutulur. Başvurunuzu yalnızca işe alım sürecinde görevli yetkili çalışanlarımız görür; başka firmalarla paylaşılmaz.', 'when' => ''],
                ['t' => 'Başvuru formundaki ikinci onay kutusu isteğe bağlıdır. İşaretlerseniz, başvurunuzun ileride açılabilecek uygun pozisyonlar için de saklanmasına açık rızanızı vermiş olursunuz; işaretlemezseniz başvurunuz yalnızca açık olan değerlendirme süreci için kullanılır. Bu izni dilediğiniz zaman, gerekçe göstermeden {eposta_baglanti} adresine yazarak geri alabilirsiniz; geri aldığınızda başvurunuz ve özgeçmişiniz silinir.', 'when' => ''],
                ['t' => 'Saklama: Başvurular sunucuda kendiliğinden silinmez; silme, yetkili çalışanlarımızın yönetim panelinden yaptığı bir işlemdir ve özgeçmiş dosyası da bu işlemle birlikte silinir. Saklama izni vermediyseniz başvurunuz ve özgeçmişiniz, işe alım süreci sonuçlandığında silinir. İzin verdiyseniz izniniz süresince, siz silinmesini isteyene kadar saklanır; bunun için ayrıca bir üst süre belirlenmemiştir. Şirket e-posta kutusundaki bildirim e-postası (özgeçmiş eki dahil) sunucudaki kayıttan ayrı bir kopyadır; o da aynı şekilde silinir.', 'when' => ''],
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
                ['t' => 'Kişisel verileriniz, hizmetin yürütülmesi için destek aldığımız barındırma (sunucu) ve e-posta hizmeti sağlayıcılarına, kanunen yetkili kamu kurum ve kuruluşlarına talep edilmesi hâlinde ve yönetim işlerinde kullanılan yapay zekâ hizmetlerine (bkz. “Yönetimde yapay zekâ asistanları”), KVKK m. 8 ve m. 9’da belirtilen şartlar çerçevesinde aktarılabilir. Bülten e-postaları, adresinizi barındıran e-posta hizmetine ulaşır.', 'when' => ''],
                ['t' => 'Ticari elektronik ileti onaylarının İleti Yönetim Sistemi’ne (İYS) kaydı gerekiyorsa, bu kayıt şirket tarafından ayrıca yapılır; sitemiz İYS ile otomatik olarak bağlantılı değildir.', 'when' => ''],
                ['t' => 'Kullandığımız barındırma ya da e-posta hizmetinin sunucuları yurt dışında bulunuyorsa, bu aktarım KVKK m. 9’da öngörülen şartlara uygun olarak yapılır. Hangi hizmet sağlayıcılarla çalıştığımızı öğrenmek için bize yazabilirsiniz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Verileriniz yalnızca sitenin ve e-postamızın çalıştığı altyapıya, yönetimde kullandığımız yapay zekâ asistanlarına ve kanunen isteyen resmi kurumlara gider; reklam için kimseye verilmez.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Yönetimde yapay zekâ asistanları',
            'paras' => [
                ['t' => 'Sitenin yönetim işlerinde yetkili çalışanlarımız, kendi yapay zekâ asistanlarını (örneğin Claude ya da ChatGPT) sitenin yönetim arayüzüne bağlayabilir. Bağlantı her çalışan için ayrı bir erişim anahtarıyla kurulur; anahtar varsayılan olarak yalnızca “okuma” iziniyle oluşturulur, diğer izinler ayrıca işaretlenir; anahtar istenirse süreli verilir ve istenen an kapatılabilir. Asistanın yaptığı işlemler, hangi çalışanın hangi uygulamayla yaptığı belirtilerek kayıt altına alınır.', 'when' => ''],
                ['t' => '“Gelen kutusu” izni verilen bir asistan, formlardan gelen kayıtları, bülten abonelerini ve iş başvurularını okuyabilir; çalışanın isteği üzerine kayıt silebilir ya da aboneyi abonelikten çıkarabilir. Bu izin verilmedikçe asistan hiçbir form kaydını, abone bilgisini ya da başvuruyu göremez. Asistan bülten e-postası gönderemez (en fazla taslak hazırlar); panel şifresini ve erişim anahtarlarını göremez; özgeçmiş dosyalarının içeriği ve IP adresiniz asistana aktarılmaz.', 'when' => ''],
                ['t' => 'Asistanın okuduğu kişisel veriler, çalışanın kullandığı yapay zekâ hizmetinin sunucularında o hizmetin kendi koşullarına göre işlenir ve bu sunucular yurt dışında olabilir. Bu nedenle “Gelen kutusu” izni yalnızca gerektiğinde ve yetkili çalışanlarımıza verilir. Bu işleme, yönetim işlerinin yürütülmesine ilişkin meşru menfaatimize (KVKK m. 5/2-f) dayanılır; yurt dışına aktarım KVKK m. 9’da öngörülen şartlara uygun olarak yapılır. Hangi yapay zekâ hizmetlerinin kullanıldığını öğrenmek için bize yazabilirsiniz.', 'when' => ''],
                ['t' => '“Gelen kutusu” izniyle asistana aktarılabilen bilgiler şunlardır:', 'when' => ''],
            ],
            'items' => [
                ['t' => 'Ad, soyad, e-posta adresi ve telefon numarası,', 'when' => ''],
                ['t' => 'İl ya da şehir, sektör ve ilgilendiğiniz konular,', 'when' => ''],
                ['t' => 'Yazdığınız mesaj ya da ön yazı (en çok ilk 2000 karakteri),', 'when' => ''],
                ['t' => 'Başvurularda pozisyon, deneyim süresi, LinkedIn bağlantısı ve özgeçmiş dosyasının adı,', 'when' => ''],
                ['t' => 'Aydınlatma metni, ticari elektronik ileti ve saklama onaylarınız ile tarihleri,', 'when' => ''],
                ['t' => 'Bülten aboneliğinizin durumu.', 'when' => ''],
            ],
            'plain' => [
                ['t' => 'Çalışanlarımız, işlerini kolaylaştıran yapay zekâ asistanlarını sitenin yönetimine bağlayabilir. İzin verilirse bu asistan form kayıtlarınızı, abone bilgilerinizi ve başvurunuzu okuyabilir; bu bilgiler asistanın hizmet sağlayıcısına, belki yurt dışına gider. Bu izin yalnızca gerektiğinde verilir.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Saklama süresi',
            'paras' => [
                ['t' => 'Kişisel verileriniz, işleme amacının gerektirdiği süre boyunca saklanır. Sunucumuzda kendiliğinden silme yapan bir düzenek yalnızca aşağıda belirtilen kayıtlar için vardır; diğer kayıtlar yetkili çalışanlarımız tarafından silinir ve bunların çoğu için henüz sabit bir saklama süresi belirlenmemiştir.', 'when' => ''],
                ['t' => 'Dilekçe formuyla ilettiğiniz bilgiler kendiliğinden silinmez; yetkili çalışanlarımız tarafından silinir. Bunun için sabit bir süre belirlenmemiştir; silinmesini dilediğiniz zaman isteyebilirsiniz.', 'when' => ''],
                ['t' => 'Bülten kaydınız, onayınızı geri alana kadar e-posta gönderimi için kullanılır. Onayınızı geri aldığınızda size bülten gönderilmez; adresiniz ve ayrılma tarihiniz “abonelikten ayrıldı” olarak, bülten formundaki kaydınız da onay kayıtlarınızla birlikte yetkili çalışanlarımız silene kadar saklanır. Bunun için sabit bir süre belirlenmemiştir. Silinmesini isterseniz adresinizle ilişkili bütün bülten kayıtlarınız silinir; ayrılma bilginiz ve toplu gönderim kayıtlarındaki adınız, soyadınız ve e-posta adresiniz de silinir. Bu durumda adresinize bir daha e-posta gönderilmesini engelleyen bir kayıt da kalmayacağı için, bülten gitmesini istemiyorsanız bunu bize yazarken belirtin. Toplu gönderim kayıtlarının kendisi için de sabit bir saklama süresi belirlenmemiştir.', 'when' => ''],
                ['t' => 'İş başvurularının saklanması “İş başvurusu yapan adaylar” maddesinde anlatılmıştır.', 'when' => ''],
                ['t' => 'İstenmeyen ileti süzgecinin şüpheli bulduğu gönderimler en çok 30 gün, en yeni 300 kayıt kadar saklanır ve süre dolunca kendiliğinden silinir (ayrıntısı “Şüpheli bulunan gönderimler ve otomatik değerlendirme” maddesindedir).', 'when' => ''],
                ['t' => 'Kullanılmış form belirteçlerinin özeti en çok iki saat, form gönderim sınırı için tutulan IP özetleri bir günden eskilerinin temizlenmesine kadar saklanır. Ziyaret sayacının günlük özetleri ertesi günün ilk sayımında silinir; günlük toplamlar kişi bilgisi içermediğinden süre sınırı olmadan tutulur.', 'when' => ''],
                ['t' => 'Yönetim panelinden alınan yedek dosyaları ve e-posta kutularındaki bildirim e-postaları, sunucudaki kayıtlardan ayrı kopyalardır; sunucudaki kaydın silinmesiyle kendiliğinden silinmezler. Silme taleplerinde bu kopyalar da ayrıca silinir. Saklama süresi dolan veriler silinir, yok edilir ya da anonim hâle getirilir.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Süresi belli olanları (şüpheli ayrılan gönderimler, teknik kayıtlar) kendiliğinden siliyoruz. Diğerlerini isteğiniz üzerine ya da işimiz bitince elle sileriz; sabit bir süre henüz belirlemedik.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Veri güvenliği',
            'paras' => [
                ['t' => 'Kişisel verilerinizin güvenliği için şu önlemleri uygularız:', 'when' => ''],
            ],
            'items' => [
                ['t' => 'Form kayıtları ve özgeçmiş dosyaları internetten doğrudan erişime kapalı bir klasörde tutulur; özgeçmiş dosyaları rastgele adlarla saklanır,', 'when' => ''],
                ['t' => 'Site, HTTP isteklerini HTTPS’e yönlendirecek biçimde yapılandırılmıştır,', 'when' => ''],
                ['t' => 'Yönetim paneline yalnızca şifreyle girilir; 15 dakikada 5 hatalı denemeden sonra giriş geçici olarak kapanır ve işlem yapılmazsa oturum 8 saat sonra sona erer,', 'when' => ''],
                ['t' => 'Yapay zekâ erişim anahtarları sunucuda yalnızca özet olarak saklanır, kişiye özeldir ve istenen an kapatılabilir,', 'when' => ''],
                ['t' => 'Formlar imzalı ve tek kullanımlık belirteçlerle korunur,', 'when' => ''],
                ['t' => 'Yönetim panelindeki ve yapay zekâ erişimindeki değişiklikler kimin yaptığıyla birlikte kaydedilir; kayıt silme, başvuru silme ya da abonelikten ayrılma gibi işlemlerin kaydına ilgili kişinin adı ya da e-posta adresi yazılmaz.', 'when' => ''],
            ],
            'plain' => [
                ['t' => 'Verilerinizi kilit altında tutmak için aldığımız önlemler bunlar. Hiçbir önlem kusursuz değildir; bir sorun görürseniz bize yazın.', 'when' => ''],
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
                ['t' => 'Başvurunuz, niteliğine göre en kısa sürede ve en geç otuz gün içinde sonuçlandırılır; işlem kural olarak ücretsizdir. Başvurunuzun reddedilmesi, verilen cevabı yetersiz bulmanız ya da süresinde cevap verilmemesi hâlinde Kişisel Verileri Koruma Kurulu’na şikâyette bulunabilirsiniz.', 'when' => ''],
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
                ['t' => 'Formlar aracılığıyla bize ilettiğiniz kişisel verilerin ve ziyaret sayacının işlenmesi {kvkk_baglanti}’nde ayrıca düzenlenmiştir.', 'when' => ''],
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
                ['t' => 'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti kendi teknik çerezlerini kullanırsa bu madde buna göre güncellenir. Yönetim paneli ve yapay zekâ erişimi sayfalarının çerezleri “Yönetim paneli ve yapay zekâ erişimi” maddesinde ayrıca anlatılmıştır.', 'when' => 'cerez_var'],
                ['t' => 'Sitemizin ziyaretçiye açık sayfaları tarayıcınıza çerez yerleştirmez. Oturum çerezi, tercih çerezi, analiz çerezi, reklam ya da hedefleme çerezi ve sosyal medya çerezi kullanılmaz; bu nedenle bu sayfalarda bir çerez onay penceresi de bulunmaz. Yalnızca yetkili çalışanlarımızın kullandığı yönetim paneli ile yapay zekâ erişimi onay sayfası, “Yönetim paneli ve yapay zekâ erişimi” maddesinde anlatılan oturum çerezlerini kullanır.', 'when' => 'cerez_yok'],
                ['t' => 'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti (örneğin saldırı koruması) kendi teknik çerezlerini kullanmaya başlarsa bu madde buna göre güncellenir.', 'when' => 'cerez_yok'],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Yalnızca sitenin çalışması için gerekenler var. Sizi takip eden çerez yok.', 'when' => 'cerez_var'],
                ['t' => 'Sitede gezerken çerez yok. O yüzden size “çerezleri kabul ediyor musunuz?” diye sormuyoruz. Yalnızca çalışanlarımızın yönetim sayfası kendine ait bir oturum çerezi kullanır.', 'when' => 'cerez_yok'],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Ziyaret sayacı',
            'paras' => [
                ['t' => 'Sitemize kaç kişinin geldiğini görmek için kendi sunucumuzda çalışan, çerez kullanmayan bir sayaç kullanırız; Google Analytics gibi dış bir analiz hizmeti kullanılmaz. Bir sayfayı açtığınızda tarayıcınız sunucumuza o sayfanın adresini ve sitemize hangi siteden geldiğinizi bildirir.', 'when' => ''],
                ['t' => 'Aynı ziyaretçiyi gün içinde bir kez sayabilmek için IP adresiniz ve tarayıcı bilginiz, her gün yenilenen rastgele bir değerle birlikte kısa bir özete çevrilir. IP adresiniz ve tarayıcı bilginiz kaydedilmez; özet yalnızca o gün için tutulur, ertesi günün ilk sayımında silinir ve günler arasında eşleştirilemez. Kalıcı olarak yalnızca günlük toplam sayılar saklanır. Sayaç çerez ya da başka bir kalıcı tanımlayıcı bırakmaz; ayrıntısı {kvkk_baglanti}’ndedir.', 'when' => ''],
                ['t' => 'Sayaç, tarayıcınızın yerel depolama alanında yalnızca yönetim panelinin bıraktığı bir işaret olup olmadığına bakar; varsa o tarayıcının ziyaretleri sayılmaz. Sayaç bu alana bir şey yazmaz.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Kaç kişinin geldiğini çerezsiz, kendi sunucumuzda sayıyoruz. Sizi tanıyabileceğimiz bir bilgiyi tutmuyoruz.', 'when' => ''],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Formlar ve güvenlik kayıtları',
            'paras' => [
                ['t' => 'İletişim, bülten ve iş başvurusu formlarında, gönderimin bir insan tarafından yapıldığını ayırt etmek için forma imzalı, tek kullanımlık bir belirteç eklenir; forma ilk dokunduğunuzda tarayıcınız küçük bir hesaplama yapıp sonucunu da forma yazar. Formda ayrıca insanların görmediği gizli bir alan bulunur. Bu bilgiler çerez olarak saklanmaz; formun içinde durur ve yalnızca formu gönderdiğinizde bize iletilir. Kullanılmış belirteçlerin özeti sunucumuzda en çok iki saat tutulur.', 'when' => ''],
                ['t' => 'Kısa sürede çok sayıda gönderim yapılmasını engellemek için, form gönderildiğinde IP adresinizin özeti (hash) ve gönderim zamanları sunucumuzda tutulur. Bu kayıt yalnızca bu amaçla kullanılır; bir günden eskileri sunucu temizlik yaptığında silinir. Gönderdiğiniz formun kendisinin (IP adresiniz dahil) nasıl saklandığı ve gönderimin istenmeyen ileti süzgecinden nasıl geçirildiği {kvkk_baglanti}’nde anlatılmıştır.', 'when' => ''],
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
                ['t' => 'Oturum belleğine (sessionStorage) yazılanlar tarayıcı sekmesini kapattığınızda kendiliğinden silinir; yerel depolamaya (localStorage) yazılanlar siz silene kadar cihazınızda kalır. Bu bilgileri tarayıcınızın “site verilerini temizle” seçeneğiyle dilediğiniz zaman silebilirsiniz; site bundan sonra da çalışmaya devam eder.', 'when' => 'depolama_var'],
                ['t' => 'Site, tarayıcınızın yerel depolama alanına (localStorage, sessionStorage ya da IndexedDB) bilgi yazmaz.', 'when' => 'depolama_yok'],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'İşaretlediğiniz bir şeyi ya da kapattığınız bir pencereyi hatırlamak için tarayıcınıza not düşebiliriz. O not sizde kalır, bize gelmez.', 'when' => 'depolama_var'],
                ['t' => 'Tarayıcınıza başka türlü bir not da bırakmıyoruz.', 'when' => 'depolama_yok'],
            ],
            'anchor' => '',
        ],
        [
            'title' => 'Yönetim paneli ve yapay zekâ erişimi',
            'paras' => [
                ['t' => 'Sitenin yönetim paneli (/yonetim adresi) yalnızca yetkili çalışanlarımız içindir ve ziyaretçi sayfalarının parçası değildir. Bu adresi açan her tarayıcıya, giriş sayfası açıldığı anda “arsl_yonetim” adlı bir oturum çerezi verilir. Çerez zorunludur: oturumu sürdürmek ve işlemlerin sahte isteklere karşı korunması için kullanılır; yalnızca yönetim paneli adreslerinde gönderilir, tarayıcıyı kapattığınızda silinir, tarayıcıdaki betikler tarafından okunamaz ve başka sitelerden gelen isteklerle gönderilmez. Panelde işlem yapılmazsa oturum 8 saat sonra sunucuda da geçersiz olur.', 'when' => ''],
                ['t' => 'Çalışanlarımızın yapay zekâ asistanlarını siteye bağlarken kullandığı onay sayfası (/oauth/authorize) da formu sahte isteklere karşı korumak için “arsl_oauth” adlı bir oturum çerezi bırakır; bu çerez yalnızca o sayfada gönderilir ve tarayıcıyı kapattığınızda silinir.', 'when' => ''],
                ['t' => 'Paneli kullanan tarayıcının yerel depolama alanında ayrıca iki küçük işaret tutulur: bu tarayıcının kendi ziyaretlerinin ziyaretçi sayacına yazılmaması için bir işaret ve arama motoru ekranındaki önizleme tercihi. Bu işaretler yalnızca paneli kullanan çalışanın tarayıcısında oluşur; siteyi gezmenizle oluşmaz ve sunucuya gönderilmez.', 'when' => ''],
            ],
            'items' => [],
            'plain' => [
                ['t' => 'Bu madde yalnızca çalışanlarımızı ilgilendirir. Siteyi gezerken yönetim sayfasını açmadıkça size bu çerezler verilmez.', 'when' => ''],
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
                ['t' => 'Kullandığınız tarayıcının ayarlarından çerezleri ve site verilerini görüntüleyebilir, silebilir ya da engelleyebilirsiniz. Ziyaretçi sayfalarımız çerez kullanmadığı için bu ayarları değiştirmeniz sitenin çalışmasını etkilemez; yalnızca yönetim paneline giriş yapamazsınız.', 'when' => 'cerez_yok'],
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
