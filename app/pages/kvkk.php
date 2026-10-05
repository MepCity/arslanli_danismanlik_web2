<?php
/**
 * KVKK Aydınlatma Metni (6698 sayılı Kanun m. 10)
 *
 * İçerik sitenin kodu (app/form.php, app/spam.php, assets/js) okunarak yazıldı: dört form (dilekçe, Haberdar Ol, bülten, iş başvurusu),
 * gönderimlerin storage/ altında saklanması ve şirket adresine e-postalanması, özgeçmiş dosyası, istenmeyen gönderim süzgeci.
 * Sitede ziyaretçi sayacı, yönetim paneli, toplu bülten gönderimi ve abonelikten ayrılma bağlantısı YOKTUR; metin bunlardan söz etmez.
 * Kariyer sayfası bu metindeki #basvuru-adaylari bağlantısına gider: o maddenin 'anchor' değerini değiştirmeyin.
 *
 * TASLAKTIR: Yayına almadan önce hukuki kontrol gerekir. Özellikle şunlar netleştirilmeli:
 *  - Hukuki sebepler (özellikle Haberdar Ol / ticari elektronik ileti için dayanılan sebep),
 *  - Saklama süreleri (özellikle iş başvurularının saklama süresi: kodda otomatik silme yoktur, süre belirtilmemiştir),
 *  - Barındırma ve e-posta hizmet sağlayıcılarının adları ve sunucularının yurt dışında olup olmadığı,
 *  - İYS kaydı yükümlülüğünün sizin için geçerli olup olmadığı.
 * Teknik not: form.php gönderimleri storage/submissions.jsonl dosyasına IP adresiyle birlikte yazar;
 * hız sınırı kayıtları (storage/rate-*.json) kendiliğinden silinmez, sunucuda dönemsel temizlik önerilir.
 */
require_once APP . '/pages/_legal.php';

page([
    'id'          => 'legal',
    'title'       => 'KVKK Aydınlatma Metni',
    'description' => 'Arslanlı Yatırım & Danışmanlık web sitesindeki dilekçe, Haberdar Ol, bülten kayıt ve iş başvurusu formlarıyla işlenen kişisel verilere ilişkin aydınlatma metni.',
    'folio'       => pg_folio('kvkk'),
]);

$mail  = '<a class="link" href="mailto:' . e(cfg('email')) . '">' . e(cfg('email')) . '</a>';
$cerez = '<a class="link" href="' . url('kurumsal/cerez-politikasi') . '">Çerez Politikası</a>';

$maddeler = [
    [
        'title' => 'Veri sorumlusu',
        'paras' => [
            '6698 sayılı Kişisel Verilerin Korunması Kanunu (“KVKK”) uyarınca kişisel verileriniz, veri sorumlusu sıfatıyla ' . e(cfg('name')) . ' (“Arslanlı”) tarafından bu metinde açıklanan kapsamda işlenir.',
            'Yetkili: ' . e(cfg('company.authorized')) . '<br>Vergi dairesi ve numarası: ' . e(cfg('company.tax_office')) . ', ' . e(cfg('company.tax_number')) . '<br>Adres: ' . e(cfg('address')) . '<br>E-posta: ' . $mail . '<br>Telefon: ' . e(cfg('phone')),
        ],
        'sade' => 'Verilerinizden sorumlu olan biziz. Bize bu adreslerden ulaşırsınız.',
    ],
    [
        'title' => 'İşlenen kişisel veriler',
        'paras' => [
            '<b>İletişim sayfasındaki dilekçe formu:</b> ad soyad, firma adı, konu, mesajınızın içeriği, telefon numarası ve e-posta adresi.',
            '<b>Bülten kayıt formu (Haberdar Ol sayfasındaki kupon ve sayfa kenarındaki “Bültene kayıt ol” sekmesi):</b> ad, soyad, e-posta adresi, telefon numarası, bulunduğunuz il, sektörünüz, isteğe bağlı olarak ilgilendiğiniz konular ile aydınlatma metnini okuduğunuza ve ticari elektronik ileti almaya ilişkin onay kayıtlarınız (onayın tarihi ve saati dahil).',
            '<b>İş başvurusu formu (Kariyer sayfası):</b> ad, soyad, e-posta adresi, telefon numarası, bulunduğunuz şehir, isteğe bağlı LinkedIn bağlantınız, ilgilendiğiniz alan ya da pozisyon, deneyim süreniz, ön yazınız, yüklediğiniz özgeçmiş dosyası (PDF ya da DOCX) ile aydınlatma metnine ve isteğe bağlı saklama iznine ilişkin onay kayıtlarınız. Ayrıntısı “İş başvurusu yapan adaylar” maddesindedir.',
            '<b>Tüm formlarda:</b> gönderim tarihi ve saati ile IP adresiniz (işlem güvenliği). Gönderimin gerçek bir kişiden gelip gelmediğini ayırt etmek için formun ne kadar sürede doldurulduğu, tarayıcınızın bildirdiği tarayıcı türü ve dil bilgisi ile metnin kendisi, gönderim anında otomatik olarak değerlendirilir.',
            '<b>Bize doğrudan ulaştığınızda:</b> telefon, e-posta ya da WhatsApp üzerinden bizimle paylaştığınız bilgiler.',
            'Formlarda özel nitelikli kişisel veri (sağlık, din, mezhep, siyasi düşünce, biyometrik veri gibi) istemiyoruz; mesajlarınızda bu tür bilgilere yer vermemenizi rica ederiz.',
        ],
        'sade' => 'Formlara yazdıklarınızı (iş başvurusunda özgeçmişinizi de), ne zaman gönderdiğinizi ve hangi IP adresinden gönderdiğinizi biliyoruz.',
    ],
    [
        'title' => 'İşleme amaçları',
        'paras' => [
            'Kişisel verilerinizi şu amaçlarla işleriz: talebinizi değerlendirmek ve size dönüş yapmak; işletmenize uygun olabilecek destek programları hakkında ön değerlendirme yapmak ve bir hizmet ilişkisi kurulacaksa bunun için gereken görüşmeleri yürütmek; onay vermeniz hâlinde yeni çağrılar, program değişiklikleri ve başvuru takvimleri hakkında sizi bilgilendirmek; iş başvurunuzu değerlendirmek ve sizinle bu konuda iletişim kurmak; formların kötüye kullanılmasını önlemek ve bilgi güvenliğini sağlamak; mevzuattan doğan yükümlülüklerimizi yerine getirmek.',
            'Verilerinizi bu amaçlar dışında kullanmayız, satmayız ve reklam amacıyla üçüncü kişilerle paylaşmayız.',
        ],
        'sade' => 'Size dönmek, izin verdiyseniz çağrıları haber vermek ve formları spam’den korumak için.',
    ],
    [
        'title' => 'Toplama yöntemi ve hukuki sebepler',
        'paras' => [
            'Kişisel verileriniz, internet sitemizdeki formlar aracılığıyla elektronik ortamda ve bize doğrudan ulaştığınız telefon, e-posta ve mesajlaşma kanalları üzerinden toplanır.',
            'Dilekçe formuyla ve doğrudan iletişimle toplanan veriler; KVKK m. 5/2-c (bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması), m. 5/2-ç (hukuki yükümlülüğümüzü yerine getirebilmemiz için zorunlu olması) ve m. 5/2-f (temel hak ve özgürlüklerinize zarar vermemek kaydıyla meşru menfaatimiz için zorunlu olması) hukuki sebeplerine dayanılarak işlenir. IP adresi ve gönderim zamanı, bilgi güvenliğine ilişkin meşru menfaatimiz kapsamında işlenir.',
            'Haberdar Ol ve bülten kayıt formlarıyla toplanan iletişim bilgileriniz, 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun uyarınca verdiğiniz, e-posta, SMS ve telefon yoluyla bilgilendirme ve ticari elektronik ileti gönderilmesine ilişkin onaya dayanılarak bilgilendirme gönderimi için işlenir. Bu onayı dilediğiniz zaman, gerekçe göstermeden ve ücretsiz olarak geri alabilirsiniz; geri aldığınızda size yeni bilgilendirme gönderilmez. Onayınızı geri almak için ' . $mail . ' adresine yazmanız yeterlidir.',
        ],
        'sade' => 'Size dönebilmek için bu bilgilere ihtiyacımız var. Bülten ise yalnızca izin verdiyseniz gelir ve izni tek e-postayla geri alırsınız.',
    ],
    [
        'title'  => 'İş başvurusu yapan adaylar',
        'anchor' => 'basvuru-adaylari',
        'paras'  => [
            'Kariyer sayfamızdaki formla iş başvurusu yaptığınızda, “İşlenen kişisel veriler” maddesinde sayılan bilgileriniz ve özgeçmiş dosyanız başvurunuzu değerlendirmek ve sizinle iletişim kurmak amacıyla işlenir. Özgeçmişinizde ve ön yazınızda sağlık durumu, din, siyasi görüş, kimlik belgesi numarası gibi bilgilere yer vermemenizi, fotoğraf eklemenizin zorunlu olmadığını hatırlatırız.',
            'Bu veriler, bir iş ilişkisi kurulması yolunda talebiniz üzerine işlem yapılması (KVKK m. 5/2-c) ve meşru menfaatimiz (m. 5/2-f) hukuki sebeplerine dayanılarak işlenir. Başvurunuz özgeçmişinizle birlikte şirketin e-posta adresine iletilir ve sunucumuzdaki kayıt dosyasında saklanır; özgeçmiş dosyanız ayrı bir klasörde tutulur. Başvurunuzu yalnızca işe alım sürecinde görevli yetkili çalışanlarımız görür; başka firmalarla paylaşılmaz.',
            'Başvuru formundaki ikinci onay kutusu isteğe bağlıdır. İşaretlerseniz, başvurunuzun ileride açılabilecek uygun pozisyonlar için de saklanmasına açık rızanızı vermiş olursunuz; işaretlemezseniz başvurunuz yalnızca açık olan değerlendirme süreci için kullanılır. Bu izni dilediğiniz zaman, gerekçe göstermeden ' . $mail . ' adresine yazarak geri alabilirsiniz; geri aldığınızda başvurunuz ve özgeçmişiniz silinir.',
            'Saklama: Saklama izni vermediyseniz başvurunuz ve özgeçmişiniz, işe alım süreci sonuçlandığında silinir. İzin verdiyseniz izniniz süresince, siz silinmesini isteyene kadar saklanır. Şirket e-posta kutusundaki kopyalar da aynı şekilde silinir.',
        ],
        'sade'   => 'Özgeçmişiniz yalnızca işe alım için kullanılır. “Gelecekteki pozisyonlar için sakla” kutusunu işaretlemezseniz süreç bitince sileriz; işaretlerseniz siz isteyene kadar saklarız ve tek e-postayla sildirebilirsiniz.',
    ],
    [
        'title' => 'Kişisel verilerin aktarılması',
        'paras' => [
            'Kişisel verileriniz, hizmetin yürütülmesi için destek aldığımız barındırma (sunucu) ve e-posta hizmeti sağlayıcılarına, ticari elektronik ileti onaylarının kaydı için mevzuatın gerektirdiği ölçüde İleti Yönetim Sistemi’ne (İYS) ve talep edilmesi hâlinde kanunen yetkili kamu kurum ve kuruluşlarına, KVKK m. 8 ve m. 9’da belirtilen şartlar çerçevesinde aktarılabilir.',
            'Kullandığımız barındırma ya da e-posta hizmetinin sunucuları yurt dışında bulunuyorsa, bu aktarım KVKK m. 9’da öngörülen şartlara uygun olarak yapılır. Hangi hizmet sağlayıcılarla çalıştığımızı öğrenmek için bize yazabilirsiniz.',
        ],
        'sade' => 'Verileriniz yalnızca sitenin ve e-postamızın çalıştığı altyapıya ve kanunen isteyen resmi kurumlara gider.',
    ],
    [
        'title' => 'Saklama süresi',
        'paras' => [
            'Dilekçe formuyla ilettiğiniz bilgiler, talebiniz sonuçlanana kadar ve sonrasında olası uyuşmazlıklara karşı ilgili mevzuatta öngörülen süreler boyunca saklanır.',
            'Haberdar Ol ve bülten kaydınız, onayınızı geri alana kadar saklanır. Onay kayıtları, mevzuatın öngördüğü süre boyunca ayrıca tutulur.',
            'İş başvurularının saklanması “İş başvurusu yapan adaylar” maddesinde anlatılmıştır.',
            'Form gönderimlerinde kullanılan IP adresi özetleri yalnızca kısa süreli gönderim sınırlaması için kullanılır. Gönderim istenmeyen ileti (spam) olarak değerlendirilirse kayıt, ilgili e-posta bildirimi gönderilmeden en çok otuz gün saklanıp silinir. Saklama süresi dolan veriler silinir, yok edilir ya da anonim hâle getirilir.',
        ],
        'sade' => 'İşimiz bittiğinde ve kanunun saymamızı istediği süre dolduğunda verilerinizi sileriz.',
    ],
    [
        'title' => 'Haklarınız',
        'paras' => [
            'KVKK m. 11 uyarınca veri sorumlusuna başvurarak şu haklarınızı kullanabilirsiniz:',
        ],
        'list' => [
            ['a', 'Kişisel verilerinizin işlenip işlenmediğini öğrenme,'],
            ['b', 'Kişisel verileriniz işlenmişse buna ilişkin bilgi talep etme,'],
            ['c', 'Kişisel verilerinizin işlenme amacını ve bunların amacına uygun kullanılıp kullanılmadığını öğrenme,'],
            ['ç', 'Yurt içinde veya yurt dışında kişisel verilerinizin aktarıldığı üçüncü kişileri bilme,'],
            ['d', 'Kişisel verilerinizin eksik veya yanlış işlenmiş olması hâlinde bunların düzeltilmesini isteme,'],
            ['e', 'KVKK m. 7’de öngörülen şartlar çerçevesinde kişisel verilerinizin silinmesini veya yok edilmesini isteme,'],
            ['f', '(d) ve (e) bentleri uyarınca yapılan işlemlerin, kişisel verilerinizin aktarıldığı üçüncü kişilere bildirilmesini isteme,'],
            ['g', 'İşlenen verilerin münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme,'],
            ['ğ', 'Kişisel verilerinizin kanuna aykırı olarak işlenmesi sebebiyle zarara uğramanız hâlinde zararın giderilmesini talep etme.'],
        ],
        'sade' => 'Hakkınızda ne bildiğimizi sorabilir, yanlışsa düzeltmemizi, gereksizse silmemizi isteyebilirsiniz.',
    ],
    [
        'title' => 'Başvuru yöntemi',
        'paras' => [
            'Haklarınıza ilişkin taleplerinizi, kimliğinizi tespit etmemize imkân verecek bilgilerle birlikte yukarıdaki adresimize yazılı olarak ya da daha önce bize bildirdiğiniz ve kayıtlarımızda bulunan e-posta adresinizden ' . $mail . ' adresine iletebilirsiniz.',
            'Başvurunuz, niteliğine göre en kısa sürede ve en geç otuz gün içinde sonuçlandırılır; işlem kural olarak ücretsizdir. Formu gönderdiğiniz hâlde mesajınıza dönüş almadıysanız, otomatik süzgecimiz mesajınızı yanlışlıkla istenmeyen ileti saymış olabilir; telefonla ya da e-postayla bize ulaşmanız yeterlidir. Başvurunuzun reddedilmesi, verilen cevabı yetersiz bulmanız ya da süresinde cevap verilmemesi hâlinde Kişisel Verileri Koruma Kurulu’na şikâyette bulunabilirsiniz.',
        ],
        'sade' => 'Bize yazın; en geç otuz gün içinde, ücretsiz cevap veririz.',
    ],
    [
        'title' => 'Yürürlük',
        'paras' => [
            'Bu aydınlatma metni yayımlandığı tarihte yürürlüğe girer. Metinde değişiklik yapılması hâlinde güncel sürüm bu sayfada yayımlanır. Tarayıcınızda saklanan bilgiler için ' . $cerez . '’na bakabilirsiniz.',
        ],
    ],
];

legal_doc([
    'no'       => 'ARS-' . date('Y') . '/013',
    'subject'  => 'Kişisel verilerin işlenmesi',
    'h1'       => 'KVKK aydınlatma metni',
    'updated'  => '2026-10-05',
    'lead'     => 'Formlara yazdığınız bilgilerin bizde ne olduğunu anlatan metin. Kanun gereği hukuk diliyle yazıldı; her maddenin altında sade Türkçesi var.',
    'summary'  => [
        'Formlara yazdıklarınızı yalnızca <mark>size dönmek</mark> ve, izin verdiyseniz, açılan çağrıları haber vermek için kullanırız.',
        'Verilerinizi <mark>satmayız</mark>; reklam için kimseyle paylaşmayız.',
        'Bülten onayınızı istediğiniz an geri alabilirsiniz. Bir e-posta yeter.',
        'İş başvurusunda özgeçmişiniz <mark>yalnızca işe alım</mark> için kullanılır; ileride saklanması sizin izninize bağlıdır.',
        'Hakkınızda ne bildiğimizi sorabilir, düzeltilmesini ya da silinmesini isteyebilirsiniz.',
    ],
    'maddeler' => $maddeler,
    'next'     => ['label' => 'Çerez politikası', 'href' => url('kurumsal/cerez-politikasi'), 'no' => pg_no('cerez')],
]);
