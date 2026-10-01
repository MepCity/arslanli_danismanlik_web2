<?php
/**
 * Hizmet alanları. Adresler (slug) eski siteyle birebir aynıdır; arama motoru sıralaması korunur.
 *
 * no      : dosya numarası (hizmet dolabında ve sayfa başlığında görünür)
 * tab     : dosya sırtındaki kısa ad
 * color   : telli dosyanın rengi (assets/css/app.css içindeki --f-* değişkenleri)
 * short   : listelerde görünen tek cümle
 * lead    : sayfa girişindeki açıklama
 * programs: [ad, tek cümle açıklama]
 * steps   : bizim yaptığımız işler (sayfada "madde" olarak dizilir)
 * docs    : sizden isteyeceğimiz belgeler
 * fit     : uygun olduğu durumlar / unfit: uygun olmadığı durumlar
 * law     : (isteğe bağlı) mevzuattan bir alıntı ve sade Türkçesi
 */
return [
    'tubitak-1989' => [
        'no'       => '01',
        'title'    => 'TÜBİTAK Destekleri',
        'nav'      => 'TÜBİTAK',
        'tab'      => 'TÜBİTAK',
        'color'    => 'blue',
        'short'    => 'Ar-Ge projeniz için TEYDEB hibesi: proje önerisi, hakem ziyareti ve dönem raporları.',
        'lead'     => 'TÜBİTAK projenizin parlak olup olmadığına değil, içinde gerçek bir teknik belirsizlik olup olmadığına bakar. Biz de dosyayı bunun üzerine kurarız: neyi bilmiyorsunuz, nasıl öğreneceksiniz, ne zaman bitecek.',
        'programs' => [
            ['1501 Sanayi Ar-Ge', 'Sanayi Ar-Ge projeleri için temel hibe programı.'],
            ['1507 KOBİ Ar-Ge Başlangıç', 'Ar-Ge\'ye yeni başlayan KOBİ\'lerin ilk projeleri için.'],
            ['1505 Üniversite-Sanayi İşbirliği', 'Sorunu siz getirirsiniz, bir üniversite ekibi çözer.'],
            ['1602 Patent Destek', 'Patent başvurusunun masraflarına katkı.'],
        ],
        'steps'    => [
            ['Ön okuma', 'Fikrinizi bir saatlik görüşmede dinler, Ar-Ge sayılıp sayılmayacağını o gün söyleriz.'],
            ['Proje önerisi', 'İş paketlerini, takvimi, bütçeyi ve riskleri program formuna göre yazarız.'],
            ['Hakem ziyareti', 'Değerlendirme ziyaretinden önce sunumu ve gelebilecek soruları birlikte prova ederiz.'],
            ['Dönem raporları', 'Teknik raporları ve harcama listelerini her dönem sonunda hazırlar, sisteme yükleriz.'],
        ],
        'docs'     => ['Projenin teknik özeti (bir iki sayfa yeter)', 'Proje ekibinin özgeçmişleri', 'Son iki yılın bilanço ve gelir tablosu', 'Vergi levhası ve imza sirküleri'],
        'fit'      => 'Yeni bir ürün ya da süreç geliştiren, sonucu baştan belli olmayan bir teknik problemle uğraşan işletmeler.',
        'unfit'    => 'Hazır bir makineyi almak, mevcut ürünü başka renkte üretmek ya da yalnızca yazılım lisansı satın almak Ar-Ge projesi sayılmaz.',
        'law'      => [
            'source' => '5746 sayılı Kanun, Madde 2/a',
            'text'   => '… bilimsel ve teknolojik bir belirsizliğe odaklanan, çıktıları özgün, deneysel, bilimsel ve teknik içerik taşıyan faaliyetleri …',
            'plain'  => 'Sonucunu baştan bilmediğiniz, deneyerek çözeceğiniz bir iş. Ar-Ge budur.',
        ],
        'faq'      => [
            ['Her yeni ürün Ar-Ge projesi olur mu?', 'Hayır. TÜBİTAK çözümü bilinmeyen bir teknik soru ve onu çözmek için planlanmış bir çalışma arar. Projeniz bu tanıma uymuyorsa bunu ilk görüşmede söyleriz.'],
            ['Destek oranı ne kadar?', 'Çağrıya göre değişir. Örneğin 2026 yılının ikinci 1501 çağrısında oran, firmanın ilk beş projesi için %75, sonraki projeler için %60\'tır. 2026 çağrılarında 1501 ve 1507\'ye yalnızca KOBİ ölçeğindeki sermaye şirketleri başvurabiliyor. Başvurudan önce açık çağrının şartlarını birlikte okuruz.'],
            ['Proje reddedilirse ne olur?', 'Hakem raporunu satır satır okuruz. Eleştiriler giderilebiliyorsa dosyayı düzeltip yeniden başvurmayı planlarız.'],
        ],
    ],

    'kosgeb-1659' => [
        'no'       => '02',
        'title'    => 'KOSGEB Destekleri',
        'nav'      => 'KOSGEB',
        'tab'      => 'KOSGEB',
        'color'    => 'green',
        'short'    => 'KOBİ\'ler için Ar-Ge, makine yatırımı ve büyüme desteklerinde başvurudan ödemeye takip.',
        'lead'     => 'KOSGEB, KOBİ\'lerin en kolay ulaştığı destek kapısıdır. Zor olan program seçmek, belgeyi eksiksiz tamamlamak ve ödeme talebini zamanında yapmaktır. O üç işi biz yaparız.',
        'programs' => [
            ['Girişimci Destek Programı', 'Yeni kurulan işletmelerin ilk yılları için.'],
            ['Kapasite Geliştirme', 'Üretim kapasitesini ve verimliliği artıran projeler için.'],
            ['KOBİ Dijital Dönüşüm', 'Üretimi ve yönetimi dijitale taşıyan yatırımlar için.'],
            ['Yeşil Sanayi', 'Enerji verimliliği ve yeşil dönüşüm yatırımları için.'],
        ],
        'steps'    => [
            ['Kayıt ve beyanname', 'KOSGEB kaydınızı ve KOBİ beyannamenizi kontrol eder, eksikse tamamlarız.'],
            ['Program seçimi', 'Ölçeğinize uyan programı, birlikte kullanılabilecek diğer desteklerle beraber seçeriz.'],
            ['Proje ve iş planı', 'Başvuru formunu, iş planını ve bütçeyi kurul değerlendirmesine hazırlarız.'],
            ['Ödeme talepleri', 'Faturaları, ödeme belgelerini ve izleme raporlarını takvime göre sunarız.'],
        ],
        'docs'     => ['Güncel KOBİ beyannamesi', 'Vergi levhası ve faaliyet belgesi', 'SGK personel listesi', 'Alınacak makine ya da hizmet için proforma faturalar'],
        'fit'      => 'Büyümek, yeni ürün geliştirmek ya da üretim altyapısını yenilemek isteyen küçük ve orta ölçekli işletmeler.',
        'unfit'    => 'KOBİ ölçeğini aşmış işletmeler KOSGEB programlarına başvuramaz; onlar için Bakanlık ve TÜBİTAK seçeneklerine bakarız.',
        'faq'      => [
            ['KOBİ beyannamesi şart mı?', 'Evet. Beyanname güncel değilse başvuru yapılamaz. Bu adımı başvurudan önce birlikte tamamlarız.'],
            ['Aynı anda iki programdan yararlanabilir miyim?', 'Bazı programlar birlikte kullanılabilir, bazıları aynı gider için ikinci desteğe izin vermez. Planı bu kurala göre kurarız.'],
        ],
    ],

    'sanayi-ve-teknoloji-bakanligi-1329' => [
        'no'       => '03',
        'title'    => 'Sanayi ve Teknoloji Bakanlığı',
        'nav'      => 'Sanayi ve Teknoloji',
        'tab'      => 'BAKANLIK',
        'color'    => 'red',
        'short'    => 'Ar-Ge ve Tasarım Merkezi, yatırım teşvik belgesi ve bakanlık çağrılarında uçtan uca dosya.',
        'lead'     => 'Ar-Ge merkezi belgesi, yatırım teşvik belgesi ve proje çağrıları ayrı ayrı başvurulardır ama aynı büyüme planının parçalarıdır. Hangisinin önce, hangisinin sonra geleceğini birlikte planlarız.',
        'programs' => [
            ['Ar-Ge Merkezi', '5746 sayılı Kanun kapsamında sürekli Ar-Ge yapan birimler için.'],
            ['Tasarım Merkezi', 'Aynı kanun kapsamında tasarım faaliyeti yürüten birimler için.'],
            ['Yatırım Teşvik Belgesi', '2025\'te yürürlüğe giren 9903 sayılı Karar\'a göre vergi, gümrük ve SGK avantajları.'],
            ['Teknoparklar', '4691 sayılı Kanun kapsamında teknoloji geliştirme bölgelerinde faaliyet için.'],
        ],
        'steps'    => [
            ['Merkez başvurusu', 'Ar-Ge ya da Tasarım Merkezi dosyasını, personel ve proje portföyüyle birlikte hazırlarız.'],
            ['Merkezin yürütülmesi', 'Yıllık faaliyet raporlarını, personel kayıtlarını ve denetim hazırlığını yürütürüz.'],
            ['Teşvik belgesi', 'Belgenin alınması, revizyonu ve tamamlama vizesini takip ederiz.'],
            ['Çağrı projeleri', 'Bakanlığın tematik çağrılarında proje kurgusunu ve başvuruyu hazırlarız.'],
        ],
        'docs'     => ['Organizasyon şeması ve Ar-Ge personel listesi', 'Yürüyen ve planlanan projelerin listesi', 'Yatırım için makine-teçhizat listesi ve proformalar', 'Ticaret sicil gazetesi ve imza sirküleri'],
        'fit'      => 'Ar-Ge ve tasarım işini kurumsallaştırmak ya da yeni bir üretim yatırımına başlamak isteyen sanayi kuruluşları.',
        'unfit'    => 'Teşvik belgesi gerektiren harcamalar çoğunlukla belge tarihinden sonra yapılmalıdır. Makineyi aldıktan sonra gelirseniz bazı avantajlar kaçmış olabilir.',
        'law'      => [
            'source' => '5746 sayılı Kanun, Madde 3/3',
            'text'   => '… ücretleri üzerinden hesaplanan sigorta primi işveren hissesinin yarısı, (…) Maliye Bakanlığı bütçesine konulacak ödenekten karşılanır.',
            'plain'  => 'Ar-Ge personelinizin SGK işveren payının yarısını devlet öder.',
        ],
        'faq'      => [
            ['Ar-Ge merkezi için kaç kişi gerekir?', 'Bugün en az 15 tam zaman eşdeğer Ar-Ge personeli aranıyor; otomotiv, hava ve uzay araçları gibi bazı sektörlerde eşik 30. Tasarım merkezi için eşik 10. Kanundaki sayı 50 olsa da eşik 2016\'da kararla indirildi.'],
            ['Teşvik belgesi neler sağlar?', '30 Mayıs 2025\'te yayımlanan 9903 sayılı Karar\'la sistem yenilendi: Türkiye Yüzyılı Kalkınma Hamlesi, sektörel teşvikler ve bölgesel teşvikler. Yatırımın konusuna ve yerine göre KDV istisnası, gümrük vergisi muafiyeti, vergi indirimi gibi araçlar devreye girer. Hangilerinin size düştüğünü yatırım planınıza bakarak çıkarırız.'],
        ],
    ],

    'ticaret-bakanligi-destekleri-999' => [
        'no'       => '04',
        'title'    => 'Ticaret Bakanlığı Destekleri',
        'nav'      => 'Ticaret Bakanlığı',
        'tab'      => 'İHRACAT',
        'color'    => 'orange',
        'short'    => 'Fuar, pazar araştırması, tanıtım ve markalaşma harcamalarının bir kısmını geri almak için.',
        'lead'     => 'İhracat desteklerinde para, harcamadan sonra gelir. Faturanın, ödeme belgesinin ve ön onayın ilk günden doğru olması gerekir. Harcamayı planladığınız gün yanınızda oluruz, hak ediş hesabınıza geçene kadar da kalırız.',
        'programs' => [
            ['Turquality', 'Markasını dünyaya taşımak isteyen firmalar için uzun soluklu program.'],
            ['Yurt Dışı Fuar Katılımı', 'Fuar katılım bedeli, stant ve ulaşım giderleri için.'],
            ['Pazara Giriş ve Pazar Araştırması', 'Yeni pazar raporları, belgelendirme ve tanıtım giderleri için.'],
            ['E-İhracat', 'Pazaryeri ve e-ticaret sitelerinde yurt dışı satış giderleri için.'],
        ],
        'steps'    => [
            ['Yıllık plan', 'İhracat hedefinize göre hangi harcamanın hangi destekle karşılanacağını takvime dökeriz.'],
            ['Ön onaylar', 'Harcamadan önce onay isteyen başvuruları zamanında yaparız.'],
            ['Belge düzeni', 'Fatura, dekont ve sözleşmelerin destek şartlarına uygun düzenlenmesini sağlarız.'],
            ['Hak ediş', 'Ödeme başvurusunu hazırlar, eksik evrak yazısı gelirse aynı hafta cevaplarız.'],
        ],
        'docs'     => ['Gümrük beyannameleri ya da ihracat kayıtları', 'Fuar ve tanıtım harcamalarının faturaları', 'Banka dekontları', 'İhracatçı birliği üyelik belgesi'],
        'fit'      => 'Yurt dışına satış yapan ya da yapmaya hazırlanan üreticiler, markalar ve hizmet ihracatçıları.',
        'unfit'    => 'Ön onay gerektiren bir harcamayı onaysız yaptıysanız o kalem için destek alınamayabilir. Bu yüzden harcamadan önce konuşalım.',
        'faq'      => [
            ['Harcamayı yaptıktan sonra başvurabilir miyim?', 'Kısmen. Bazı destekler harcamadan önce ön onay ister, bazıları harcamadan sonra belirli bir süre içinde başvuru bekler. Takvimi baştan kurarsak hiçbir süre kaçmaz.'],
            ['Hangi harcamalar destekleniyor?', 'Mal ihracatında 2022\'de yayımlanan 5973 sayılı İhracat Destekleri Hakkında Karar; fuar, pazar araştırması, tanıtım, marka ve benzeri kalemleri kapsar. Hizmet ihracatı için Şubat 2026\'da yeni bir karar (10962) yürürlüğe girdi. Kapsam ve oranlar değiştiği için planı her yıl yeniden gözden geçiririz.'],
        ],
    ],

    'avrupa-birligi-projeleri-2319' => [
        'no'       => '05',
        'title'    => 'Avrupa Birliği Projeleri',
        'nav'      => 'AB Projeleri',
        'tab'      => 'AB',
        'color'    => 'lilac',
        'short'    => 'Horizon Europe ve diğer AB programlarında çağrı taraması, ortak bulma ve teklif yazımı.',
        'lead'     => 'AB programlarında bütçeler büyüktür, rekabet de öyle. Kazanan teklif doğru çağrıyı, doğru ortakları ve değerlendirme formunu ezbere bilen bir yazımı bir araya getirir. Biz o üçünü kurarız.',
        'programs' => [
            ['Horizon Europe', 'AB\'nin araştırma ve yenilik çerçeve programı; Türkiye katılımcı ülkedir.'],
            ['Eurostars', 'Ar-Ge yapan KOBİ\'lerin uluslararası ortak projeleri için.'],
            ['EIC Accelerator', 'Pazara çıkmaya yakın, yüksek riskli yenilikler için hibe ve yatırım.'],
            ['Erasmus+', 'Eğitim, mesleki gelişim ve hareketlilik projeleri için.'],
        ],
        'steps'    => [
            ['Çağrı taraması', 'Açık çağrıları teknoloji alanınıza göre süzer, kazanma şansı olanları ayırırız.'],
            ['Konsorsiyum', 'Ortak arar, rol dağılımını ve bütçe paylaşımını birlikte kurarız.'],
            ['Teklif', 'Teklifi değerlendirme kriterlerinin sırasıyla, İngilizce olarak yazarız.'],
            ['Proje yönetimi', 'Kabulden sonra raporlama, ara dönem ve kapanış işlerini yürütürüz.'],
        ],
        'docs'     => ['Kuruluşunuzun İngilizce tanıtımı', 'Ekibin İngilizce özgeçmişleri', 'Daha önce yürüttüğünüz projelerin listesi', 'Son mali tablolar'],
        'fit'      => 'Uluslararası işbirliğine açık, Ar-Ge kapasitesi olan sanayi kuruluşları, KOBİ\'ler ve araştırma ekipleri.',
        'unfit'    => 'Çağrının kapanmasına üç haftadan az kalmışsa güçlü bir teklif yetiştirmek çoğu zaman mümkün olmaz; bunu baştan söyleriz.',
        'faq'      => [
            ['Türk kuruluşları AB fonlarına başvurabilir mi?', 'Evet. Türkiye Horizon Europe gibi programlara katılan ülkeler arasındadır; Türk kuruluşları ortak ya da koordinatör olabilir. Uygunluk her çağrıda ayrıca yazılır, ilk işimiz çağrı metnini birlikte okumaktır.'],
            ['Konsorsiyum şart mı?', 'Çağrıya bağlı. Bazı çağrılar tek başvurana açıktır, çoğu farklı ülkelerden ortaklar ister.'],
            ['Hazırlığa ne zaman başlamalıyım?', 'Kapanıştan en az iki, tercihen üç ay önce.'],
        ],
    ],

    'sinai-mulkiyet-haklari-tescilleri-669' => [
        'no'       => '06',
        'title'    => 'Sınai Mülkiyet Hakları',
        'nav'      => 'Sınai Mülkiyet',
        'tab'      => 'TESCİL',
        'color'    => 'yellow',
        'short'    => 'Marka, patent, faydalı model ve endüstriyel tasarım tescilinde araştırma, başvuru ve itiraz takibi.',
        'lead'     => 'Bir markayı ya da buluşu koruma altına almadan önce, aynısının başkasında olup olmadığına bakmak gerekir. Araştırmayı başvurudan önce yapar, korumanın sınırlarını doğru çizer, TÜRKPATENT yazışmalarını takip ederiz.',
        'programs' => [
            ['Marka', 'İsim, logo ve slogan tescili.'],
            ['Patent', 'Buluşlar için 20 yıla kadar koruma.'],
            ['Faydalı Model', 'Daha hızlı, 10 yıla kadar buluş koruması.'],
            ['Endüstriyel Tasarım', 'Ürünün görünüşünün korunması.'],
        ],
        'steps'    => [
            ['Ön araştırma', 'Marka benzerliği ya da buluşun yeniliği için başvurudan önce arama yaparız.'],
            ['Başvuru', 'Sınıf seçimini yapar, başvuru dosyasını hazırlar ve TÜRKPATENT\'e sunarız.'],
            ['Yayın ve itiraz', 'Yayın dönemindeki itirazları ve resmi yazışmaları süresi içinde cevaplarız.'],
            ['Maliyet desteği', 'Uygunsa patent masraflarınız için TÜBİTAK 1602 başvurusunu da yaparız.'],
        ],
        'docs'     => ['Marka için logo ve kullanılacağı ürün ya da hizmet listesi', 'Buluş için teknik çizimler ve kısa açıklama', 'Tasarım için ürün fotoğrafları', 'Vekaletname'],
        'fit'      => 'Markasını, buluşunu ya da ürün tasarımını rakiplerine karşı korumak isteyen her ölçekten işletme.',
        'unfit'    => 'Buluşunuzu fuarda sergilediyseniz ya da internette yayımladıysanız yenilik şartı zedelenmiş olabilir. Önce bize sorun, sonra gösterin.',
        'faq'      => [
            ['Patent ile faydalı model arasındaki fark nedir?', 'İkisi de buluşu korur. Patent 20 yıla kadar koruma sağlar ve daha kapsamlı incelenir; faydalı model 10 yıla kadar, daha hızlı bir korumadır.'],
            ['Marka tescili ne kadar sürer?', 'İtiraz gelmezse genellikle birkaç ay. Yayın döneminde itiraz gelirse süre uzar.'],
        ],
    ],

    'kalite-belgelendirme-339' => [
        'no'       => '07',
        'title'    => 'Kalite Belgelendirme',
        'nav'      => 'Kalite Belgelendirme',
        'tab'      => 'ISO',
        'color'    => 'grey',
        'short'    => 'ISO yönetim sistemlerinde kurulum, iç tetkik ve belgelendirme denetimine hazırlık.',
        'lead'     => 'Belge duvarda asılı durduğu için değil, işi düzene soktuğu için işe yarar. Sistemi sizin bugünkü çalışma şeklinize göre kurar, denetçi gelmeden eksikleri birlikte kapatırız.',
        'programs' => [
            ['ISO 9001', 'Kalite yönetimi.'],
            ['ISO 14001', 'Çevre yönetimi.'],
            ['ISO 45001', 'İş sağlığı ve güvenliği.'],
            ['ISO 27001', 'Bilgi güvenliği.'],
        ],
        'steps'    => [
            ['Boşluk analizi', 'Bugünkü süreçlerinizi standardın maddeleriyle karşılaştırır, eksikleri listeleriz.'],
            ['Sistem kurulumu', 'Prosedür ve kayıtları gerektiği kadar, fazlasını değil, yazarız.'],
            ['İç tetkik', 'Ekibinizi bilgilendirir, denetimden önce iç tetkiki birlikte yaparız.'],
            ['Denetim', 'Akredite kuruluşla süreci yürütür, denetim günü yanınızda oluruz.'],
        ],
        'docs'     => ['Organizasyon şeması', 'Mevcut talimat, form ve kayıtlarınız (ne varsa)', 'Müşteri şikâyet ve iade kayıtları', 'Tedarikçi listesi'],
        'fit'      => 'Müşteri, ihale ya da ihracat şartı olarak belge isteyen ve süreçlerini düzene sokmak isteyen işletmeler.',
        'unfit'    => 'Belgeyi biz vermeyiz; akredite belgelendirme kuruluşları verir. Size "belge satan" danışmandan uzak durun.',
        'faq'      => [
            ['Belgeyi siz mi veriyorsunuz?', 'Hayır. Belgeyi akredite kuruluş verir. Biz sistemi kurar, sizi bağımsız denetime hazırlarız. Belgenin değeri de bu ayrımdan gelir.'],
            ['Kurulum ne kadar sürer?', 'Şirketin büyüklüğüne ve bugünkü düzenine göre değişir. Boşluk analizinden sonra size yazılı bir takvim veririz.'],
        ],
    ],

    'yatirim-danismanligi-2649' => [
        'no'       => '08',
        'title'    => 'Yatırım Danışmanlığı',
        'nav'      => 'Yatırım Danışmanlığı',
        'tab'      => 'YATIRIM',
        'color'    => 'manila',
        'short'    => 'Yeni tesis, kapasite artışı ve modernizasyon yatırımlarında fizibilite, teşvik ve yer seçimi.',
        'lead'     => 'Yatırımın maliyeti büyük ölçüde karar anında belirlenir: nerede, ne zaman, hangi belgeyle. Fizibiliteyi, teşvik avantajını ve finansmanı o karardan önce aynı masaya koyarız.',
        'programs' => [
            ['Fizibilite', 'Teknik, pazar ve finansal açıdan karar verilebilir rapor.'],
            ['Bölgesel Teşvikler', 'Yatırım yerine göre değişen avantajların karşılaştırması.'],
            ['Kalkınma Ajansları', 'Bölgesel mali destek programları.'],
            ['Yatırım Planı', 'Takvim, bütçe ve teşvik yükümlülüklerinin tek planda toplanması.'],
        ],
        'steps'    => [
            ['Fizibilite', 'Farklı satış, maliyet ve kur varsayımlarıyla yatırımın geri dönüşünü hesaplarız.'],
            ['Yer ve teşvik', 'Aday yerleri bölgesel teşvik farklarıyla birlikte karşılaştırırız.'],
            ['Maliyet kurgusu', 'Teşvik belgesi ve destek programlarıyla toplam maliyeti düşürecek sırayı çıkarırız.'],
            ['Uygulama takibi', 'Yatırım sürerken takvimi, bütçeyi ve teşvik şartlarını birlikte izleriz.'],
        ],
        'docs'     => ['Yatırımın konusu ve kapasitesi', 'Makine-teçhizat listesi ve teklifler', 'Aday arsa ya da bina bilgileri', 'Son üç yılın mali tabloları'],
        'fit'      => 'Yeni tesis kuran, kapasite artıran ya da üretim hattını yenileyen sanayi ve hizmet işletmeleri.',
        'unfit'    => 'Hisse senedi, fon ya da döviz gibi sermaye piyasası araçlarında yatırım tavsiyesi vermiyoruz. Bu hizmet gerçek yatırımlar içindir: bina, makine, kapasite.',
        'faq'      => [
            ['Bu hizmet borsa danışmanlığı mı?', 'Hayır. İşletmelerin tesis, makine ve kapasite yatırımlarına yöneliktir. Sermaye piyasası araçlarında tavsiye vermiyoruz.'],
            ['Yatırıma başladıktan sonra teşvik alınabilir mi?', 'Pek çok teşvik unsuru, harcamanın belge tarihinden sonra yapılmasını şart koşar. Teşvik planını yatırım kararıyla aynı anda yapmanızı öneririz.'],
        ],
    ],

    'yatirima-yonelik-krediler-2979' => [
        'no'       => '09',
        'title'    => 'Yatırıma Yönelik Krediler',
        'nav'      => 'Yatırım Kredileri',
        'tab'      => 'KREDİ',
        'color'    => 'pink',
        'short'    => 'Kamu destekli ve uygun maliyetli yatırım kredilerinde seçenek karşılaştırması ve kredi dosyası.',
        'lead'     => 'Doğru kredi, yatırımın kendini geri ödeme süresini kısaltır. Kamu destekli seçenekleri maliyet ve şartlarıyla yan yana koyar, bankanın istediği dosyayı ilk seferde eksiksiz hazırlarız.',
        'programs' => [
            ['KGF Kefaletli Krediler', 'Teminatı yetmeyen işletmeler için kefalet desteği.'],
            ['Kalkınma Ajansı Kredileri', 'Bölgesel faiz destekli programlar.'],
            ['Türk Eximbank', 'İhracatçılar için finansman.'],
            ['Kalkınma ve Yatırım Bankaları', 'Uzun vadeli yatırım kredileri.'],
        ],
        'steps'    => [
            ['İhtiyaç hesabı', 'Ne kadar krediye, hangi vadede ihtiyacınız olduğunu nakit akışınızla birlikte hesaplarız.'],
            ['Karşılaştırma', 'Kefalet, faiz desteği ve kalkınma kredisi seçeneklerini aynı tabloda gösteririz.'],
            ['Kredi dosyası', 'İş planını, nakit akış tablosunu ve istenen belgeleri hazırlarız.'],
            ['Banka görüşmeleri', 'Görüşmelere hazırlık yapar, süreç sonuçlanana kadar takip ederiz.'],
        ],
        'docs'     => ['Son üç yılın mali tabloları', 'Güncel mizan', 'Yatırım bütçesi ve teklifler', 'Mevcut kredi ve teminat listesi'],
        'fit'      => 'Yatırımını öz kaynakla tek başına karşılamak istemeyen, uygun maliyetli finansman arayan işletmeler.',
        'unfit'    => 'Kredi kararını banka ya da ilgili kurum verir; biz onay garantisi vermeyiz. Verdiğimiz söz, dosyanın eksiksiz ve zamanında olmasıdır.',
        'faq'      => [
            ['Krediyi siz mi veriyorsunuz?', 'Hayır. Kararı banka ya da kurum verir. Biz uygun seçeneği bulur ve başvurunuzu kurumun beklediği şekilde hazırlarız.'],
            ['Teşvik ve kredi birlikte kullanılabilir mi?', 'Çoğu durumda evet. Faiz desteği gibi bazı teşvik unsurları zaten kredi kullanımına bağlıdır. Planı ikisini birlikte düşünerek kurarız.'],
        ],
    ],
];
