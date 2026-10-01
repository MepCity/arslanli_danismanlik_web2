<?php
/**
 * Sayfalar arasında paylaşılan içerikler.
 * Mevzuat alıntıları mevzuat.gov.tr'deki güncel metinden birebir alınmıştır; (…) kısaltmayı gösterir.
 */
return [
    // Referans logoları: assets/img/refs/{slug}.webp
    'refs' => [
        'pilot-seating'  => 'Pilot Seating',
        'tork'           => 'Tork Fixing Systems',
        'cnk-havacilik'  => 'CNK Havacılık',
        'acar-kaporta'   => 'Acar Kaporta',
        'sacform'        => 'Sacform',
        'pilotcar'       => 'Pilotcar',
        'yonca-shipyard' => 'Yonca Shipyard',
        'mirmaksan'      => 'Mirmaksan',
        'premier'        => 'Premier',
        'schafer'        => 'Schafer',
        'saha-istanbul'  => 'Saha İstanbul',
        'hayat-holding'  => 'Hayat Holding',
        'mado'           => 'Mado',
        'nsk'            => 'NSK Europe',
    ],

    // Ana sayfa: kanun metni ve sade Türkçesi (mercek altında görünen)
    'hero_law' => [
        'title'  => 'Araştırma, Geliştirme ve Tasarım Faaliyetlerinin Desteklenmesi Hakkında Kanun',
        'meta'   => ['Kanun No. 5746', 'Kabul 28/2/2008', 'R.G. 12/3/2008 · Sayı 26814'],
        'blocks' => [
            [
                'ref'   => 'Madde 3/1',
                'law'   => 'Ar-Ge ve tasarım indirimi: Teknoloji merkezi işletmelerinde, Ar-Ge merkezlerinde, kamu kurum ve kuruluşları ile kanunla kurulan veya teknoloji geliştirme projesi anlaşmaları kapsamında uluslararası kurumlardan ya da kamu kurum ve kuruluşlarından Ar-Ge projelerini desteklemek amacıyla fon veya kredi kullanan vakıflar tarafından veya uluslararası fonlarca desteklenen Ar-Ge ve yenilik projelerinde (…) gerçekleştirilen Ar-Ge ve yenilik harcamalarının tamamı (…) kurum kazancının ve (…) ticari kazancın tespitinde indirim konusu yapılır.',
                'plain' => 'Ar-Ge merkezinizde ya da desteklenen bir projede harcadığınız paranın <mark>tamamı</mark>, vergi hesaplanırken kazancınızdan düşülür.',
            ],
            [
                'ref'   => 'Madde 3/1',
                'law'   => 'Kazancın yetersiz olması nedeniyle ilgili hesap döneminde indirim konusu yapılamayan tutar, sonraki hesap dönemlerine devredilir. Devredilen tutarlar, takip eden yıllarda 213 sayılı Kanuna göre her yıl belirlenen yeniden değerleme oranında artırılarak dikkate alınır.',
                'plain' => 'O yıl kârınız yetmezse düşemediğiniz tutar kaybolmaz; <mark>değerlenerek</mark> sonraki yıllara aktarılır.',
            ],
            [
                'ref'   => 'Madde 3/2',
                'law'   => '(…) hesaplanan gelir vergisinden (…) kalan vergi tutarının; doktoralı olanlar ile desteklenecek program alanlarından birinde en az yüksek lisans derecesine sahip olanlar için yüzde doksan beşi, yüksek lisanslı olanlar ile desteklenecek program alanlarından birinde lisans derecesine sahip olanlar için yüzde doksanı ve diğerleri için yüzde sekseni, verilecek muhtasar beyanname üzerinden tahakkuk eden vergiden indirilmek suretiyle terkin edilir.',
                'plain' => 'Ar-Ge çalışanınızın maaşından kesilen gelir vergisinin <mark>%80’ini</mark> devlete ödemezsiniz. Yüksek lisans ya da doktora varsa oran %90–95’e çıkar.',
            ],
            [
                'ref'   => 'Madde 3/3',
                'law'   => 'Sigorta primi desteği: (…) çalışan Ar-Ge ve destek personeli (…) ile (…) ücreti gelir vergisinden istisna olan personelin; bu çalışmaları karşılığında elde ettikleri ücretleri üzerinden hesaplanan sigorta primi işveren hissesinin yarısı, (…) Maliye Bakanlığı bütçesine konulacak ödenekten karşılanır.',
                'plain' => 'Aynı çalışanların SGK işveren payının <mark>yarısını</mark> devlet öder.',
            ],
            [
                'ref'   => 'Madde 1/1',
                'law'   => 'Bu Kanunun amacı; Ar-Ge, yenilik ve tasarım yoluyla ülke ekonomisinin uluslararası düzeyde rekabet edebilir bir yapıya kavuşturulması için teknolojik bilgi üretilmesini, üründe ve üretim süreçlerinde yenilik yapılmasını, ürün kalitesi ve standardının yükseltilmesini, verimliliğin artırılmasını, üretim maliyetlerinin düşürülmesini, teknolojik bilginin ticarileştirilmesini, rekabet öncesi işbirliklerinin geliştirilmesini (…) Ar-Ge ve tasarım personeli ve nitelikli işgücü istihdamının artırılmasını desteklemek ve teşvik etmektir.',
                'plain' => 'Kısacası: yeni bir ürün ya da süreç geliştiren işletmenin <mark>yükünü paylaşmak</mark> için yazılmış bir kanun.',
            ],
            [
                'ref'   => 'Madde 3/4',
                'law'   => 'Damga vergisi istisnası: Bu Kanun kapsamındaki her türlü Ar-Ge ve yenilik faaliyetleri ile tasarım faaliyetlerine ilişkin olarak düzenlenen kağıtlardan damga vergisi alınmaz.',
                'plain' => 'Ar-Ge ve tasarım işleri için düzenlediğiniz evraktan <mark>damga vergisi</mark> alınmaz.',
            ],
            [
                'ref'   => 'Madde 2/c',
                'law'   => 'Ar-Ge merkezi: Ar-Ge ve yenilik projelerini veya sözleşme çerçevesinde siparişe dayalı olarak yürütülen Ar-Ge ve yenilik faaliyetlerini gerçekleştirmek üzere kurulan (…) sermaye şirketlerinin; organizasyon yapısı içinde ayrı bir birim şeklinde örgütlenmiş, münhasıran yurtiçinde araştırma ve geliştirme faaliyetlerinde bulunan ve en az elli tam zaman eşdeğer Ar-Ge personeli istihdam eden, yeterli Ar-Ge birikimi ve yeteneği olan birimleri,',
                'plain' => 'Kanunda <mark>elli</mark> kişi yazar; 2016’daki kararla eşik on beşe indi. Metni okumak yetmez, güncelini bilmek gerekir.',
            ],
            [
                'ref'   => 'Madde 2/a',
                'law'   => 'Araştırma ve geliştirme faaliyeti (Ar-Ge): (…) sistematik bir temelde yürütülen yaratıcı çalışmaları, çevre uyumlu ürün tasarımı veya yazılım faaliyetleri ile alanında bilimsel ve teknolojik gelişme sağlayan, bilimsel ve teknolojik bir belirsizliğe odaklanan, çıktıları özgün, deneysel, bilimsel ve teknik içerik taşıyan faaliyetleri,',
                'plain' => 'Ama önce şart: Ar-Ge sayılması için sonucun <mark>baştan bilinmemesi</mark> gerekir. Çözmeye çalıştığınız gerçek bir teknik belirsizlik.',
            ],
        ],
    ],

    // Ana sayfa takvim yaprakları: bir başvurunun baştan sona akışı
    'process' => [
        ['phase' => 'Hazırlık',  'title' => 'Tanışma',     'us' => 'İşinizi, yatırım planınızı ve Ar-Ge gündeminizi dinleriz. Hangi kapının açık olduğunu o gün söyleriz.', 'you' => 'Bir saat ayırırsınız.'],
        ['phase' => 'Hazırlık',  'title' => 'Eleme',       'us' => 'Size uymayan programları çizer, kalanları bütçe, takvim ve kazanma şansına göre sıralarız.', 'you' => 'Son iki yılın mali tablolarını gönderirsiniz.'],
        ['phase' => 'Hazırlık',  'title' => 'Takvim',      'us' => 'Çağrının kapanış tarihinden geriye doğru sayarak bir iş takvimi çıkarırız. Son hafta boş kalır.', 'you' => 'Takvimi onaylarsınız.'],
        ['phase' => 'Başvuru',   'title' => 'Yazım',       'us' => 'Proje önerisini, bütçeyi ve iş planını yazarız. Her taslağı size okuturuz.', 'you' => 'Teknik sorularımızı cevaplarsınız; çoğu e-postayla.'],
        ['phase' => 'Başvuru',   'title' => 'Başvuru',     'us' => 'Dosyayı sisteme yükler, son kontrolü yapar, başvuru numarasını size iletiriz.', 'you' => 'Onay ekranında imzanızı atarsınız.'],
        ['phase' => 'Başvuru',   'title' => 'Değerlendirme', 'us' => 'Hakem ya da kurul sorularını birlikte cevaplarız. Sunum varsa önceden prova ederiz.', 'you' => 'Sunuma katılırsınız.'],
        ['phase' => 'Yürütme',   'title' => 'Sözleşme',    'us' => 'Sözleşmeyi okur, yükümlülüklerinizi tek sayfaya indiririz. Dönem raporlarını biz hazırlarız.', 'you' => 'Faturaları ve kayıtları düzenli tutarsınız.'],
        ['phase' => 'Yürütme',   'title' => 'Ödeme',       'us' => 'Ödeme taleplerini ve kapanış raporunu hazırlarız. Sonra bir sonraki çağrıya bakarız.', 'you' => 'Desteği hesabınızda görürsünüz.'],
    ],

    // Hakkımızda: arşiv rafı. kind: law (mevzuat) | us (biz)
    'timeline' => [
        ['year' => 2001, 'kind' => 'law', 'title' => 'Teknoparklar kanunu', 'text' => '4691 sayılı Teknoloji Geliştirme Bölgeleri Kanunu yayımlanır. Üniversitelerin yanında Ar-Ge yapan şirketler için ayrı bir düzen kurulur.', 'src' => 'R.G. 6/7/2001'],
        ['year' => 2004, 'kind' => 'law', 'title' => 'Turquality', 'text' => 'Türk markalarını yurt dışına taşımak için kurulan program, tekstil ve hazır giyimle başlar.', 'src' => '23/11/2004'],
        ['year' => 2006, 'kind' => 'law', 'title' => 'Kalkınma ajansları', 'text' => '5449 sayılı Kanun çıkar. Bugün 26 bölgede ajans, kendi bölgesine yönelik destek programları açıyor.', 'src' => 'R.G. 8/2/2006'],
        ['year' => 2007, 'kind' => 'us',  'title' => 'Arslanlı kuruldu', 'text' => 'İstanbul’da, işletmelerin devlet desteklerine ulaşmasına yardım etmek için ilk dosyamızı açtık.', 'src' => 'İstanbul'],
        ['year' => 2008, 'kind' => 'law', 'title' => 'Ar-Ge Kanunu', 'text' => '5746 sayılı Kanun yürürlüğe girer: Ar-Ge harcamaları için vergi indirimi, personel için stopaj ve SGK desteği.', 'src' => 'R.G. 12/3/2008'],
        ['year' => 2012, 'kind' => 'law', 'title' => 'Yeni teşvik sistemi', 'text' => '2012/3305 sayılı Karar ile bölgesel, büyük ölçekli ve stratejik yatırımlar için teşvik sistemi kurulur.', 'src' => 'R.G. 19/6/2012'],
        ['year' => 2016, 'kind' => 'law', 'title' => 'Tasarım merkezleri', 'text' => '6676 sayılı Kanun tasarım merkezlerini sisteme ekler. Aynı yıl Ar-Ge merkezi için personel eşiği 50’den 15’e iner.', 'src' => 'R.G. 26/2/2016'],
        ['year' => 2017, 'kind' => 'law', 'title' => 'Sınai Mülkiyet Kanunu', 'text' => '6769 sayılı Kanun marka, patent, faydalı model ve tasarım korumasını tek kanunda toplar.', 'src' => 'R.G. 10/1/2017'],
        ['year' => 2019, 'kind' => 'law', 'title' => 'Teknoloji Odaklı Sanayi Hamlesi', 'text' => 'Bakanlığın teknoloji odaklı yatırım programı ilk çağrısını makine sektörü için açar.', 'src' => '2019'],
        ['year' => 2021, 'kind' => 'law', 'title' => 'Horizon Europe', 'text' => 'Türkiye, AB’nin 2021–2027 araştırma ve yenilik programına katılım anlaşmasını imzalar. Aynı yıl 5746’nın süresi 2028 sonuna uzatılır.', 'src' => '27/10/2021'],
        ['year' => 2022, 'kind' => 'law', 'title' => 'İhracat destekleri tek kararda', 'text' => '5973 sayılı Karar ile mal ihracatına yönelik destekler tek metinde toplanır.', 'src' => 'R.G. 18/8/2022'],
        ['year' => 2025, 'kind' => 'law', 'title' => 'Teşvik sistemi yenilendi', 'text' => '9903 sayılı Karar, 2012’den beri uygulanan sistemi kaldırır. Yerine Türkiye Yüzyılı Kalkınma Hamlesi, sektörel ve bölgesel teşvikler gelir.', 'src' => 'R.G. 30/5/2025'],
        ['year' => 2026, 'kind' => 'law', 'title' => 'Hizmet ihracatı', 'text' => '10962 sayılı Karar ile hizmet ihracatına yönelik destekler yeniden düzenlenir.', 'src' => 'R.G. 27/2/2026'],
    ],

    // Mihenk taşlarımız
    'principles' => [
        ['Uymuyorsa söyleriz.', 'Bir programa uygun değilseniz bunu ilk görüşmede duyarsınız, üçüncü ayda değil.'],
        ['Son güne kalmayız.', 'Dosya, çağrı kapanmadan en az bir hafta önce hazırdır. Son gün sistemler yavaşlar, sorular cevapsız kalır.'],
        ['Taşıyabileceğiniz kadar.', 'Sizi en büyük bütçeye değil, yürütebileceğiniz projeye yönlendiririz. Kazanılıp yürütülemeyen proje başınıza iş açar.'],
        ['Dosyanız sizindir.', 'Paylaştığınız teknik ve mali bilgiler yalnızca sizin dosyanız için kullanılır, başka kimseyle paylaşılmaz.'],
        ['Kabulden sonra da buradayız.', 'Raporlama, denetim, ödeme talebi. Bir projenin en uzun kısmı kabul yazısından sonra başlar.'],
        ['Mevzuatı biz izleriz.', 'Karar ve tebliğleri takip ederiz. Sizi ilgilendiren bir değişiklik olduğunda haberi bizden alırsınız.'],
    ],

    // Misyonumuz: iş listesi
    'mission' => [
        'statement' => 'İşletmelerin hak ettiği kamu desteğine, kâğıt işine boğulmadan ulaşmasını sağlamak.',
        'items' => [
            'İşletmeye uyan programı bulmak; uymayanı baştan elemek.',
            'Başvuru dosyasını, programın değerlendirme formunu bilerek yazmak.',
            'Kabulden sonra raporları, denetimleri ve ödeme taleplerini zamanında yürütmek.',
            'Ar-Ge ve tasarım merkezlerinin kurulmasına ve belgelerini korumasına yardım etmek.',
            'Kâğıt işini üstlenip ekibinize üretmeye vakit bırakmak.',
        ],
    ],

    // Vizyonumuz: 2037'de açılacak mektup
    'vision' => [
        'open_year' => 2037,
        'greeting'  => 'Sevgili 2037,',
        'intro'     => 'Bu mektubu 2026 sonbaharında, İstanbul’da yazıyoruz. Açıldığında kuruluşumuzun otuzuncu yılı olacak. Umarız şunlar gerçek olmuştur:',
        'items' => [
            'Müşterilerimizin Ar-Ge ekipleri Avrupa projelerinde ortak değil, koordinatör olarak yer alıyordur.',
            'Bir destek programı değiştiğinde, ilgili her müşterimiz bunu ilk bizden duyuyordur.',
            'Yeşil dönüşüm, dijitalleşme ve ileri teknoloji programlarında ilk aranan ekip biziz.',
            '“Dosyayı Arslanlı hazırladıysa eksik evrak çıkmaz” cümlesi hâlâ doğrudur.',
        ],
        'closing'   => 'Olmadıysa: mektubu katlayın, dosyayı açın, işe devam edin.',
    ],

    'banks' => [
        [
            'bank'    => 'Ziraat Katılım Bankası',
            'holder'  => 'Hatice ARSLAN',
            'account' => '94-945444-1',
            'iban'    => 'TR27 0020 9000 0094 5444 0000 01',
        ],
        [
            'bank'    => 'Halk Bankası',
            'holder'  => 'Hatice ARSLAN',
            'account' => '01046042',
            'iban'    => 'TR77 0001 2009 8900 0001 0460 42',
        ],
    ],
];
