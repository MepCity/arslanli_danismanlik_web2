<?php
/**
 * Çerez Politikası
 * Metnin bir kısmı sitenin kendi kodu taranarak kurulur: bir gün çerez, tarayıcı depolama alanı
 * ya da dış kaynaklı içerik eklenirse sayfa "kullanmıyoruz" demeyi bırakır. Yine de böyle bir
 * değişiklikten sonra metni gözden geçirin.
 */
require_once APP . '/pages/_legal.php';

page([
    'id'          => 'legal',
    'title'       => 'Çerez Politikası',
    'description' => 'Arslanlı Yatırım & Danışmanlık web sitesinin tarayıcınızda ne sakladığı ve ne saklamadığı: çerez, analiz aracı ve reklam takibi kullanılmaz.',
    'folio'       => 'Evrak 12 · <b>Çerez Politikası</b>',
]);

/* ---------- Sitenin kodunu tara ---------- */

$files = array_merge(
    glob(ROOT . '/index.php') ?: [],
    glob(APP . '/*.php') ?: [],
    glob(APP . '/partials/*.php') ?: [],
    array_filter(glob(APP . '/pages/*.php') ?: [], fn($f) => !in_array(basename($f), ['cerez.php', 'kvkk.php', '_legal.php'], true)),
    glob(ROOT . '/assets/js/*.js') ?: [],
    glob(ROOT . '/assets/js/pages/*.js') ?: []
);
$usesCookies = false;
$storagePages = [];
$embeds = false;
$external = false;
foreach ($files as $f) {
    $src = (string) @file_get_contents($f);
    if (basename($f) === 'app.js') {
        // Ziyaret sayacı (bkz. app/stats.php) yalnızca yönetici tarayıcısındaki bir işareti okur; ziyaretçinin tarayıcısına bir şey yazmaz,
        // bu yüzden "tarayıcı depolama alanı kullanılıyor" taramasına girmez
        $src = (string) preg_replace('#/\* -+ Ziyaret sayacı.*?(?=/\* -+ Sayfa betiği)#su', '', $src);
    }
    if (preg_match('/\bsetcookie\s*\(|\bsession_start\s*\(|document\.cookie/', $src)) {
        $usesCookies = true;
    }
    if (str_ends_with($f, '.js') && preg_match('/\b(local|session)Storage\s*[.\[]/', $src)) {
        $storagePages[] = basename($f, '.js');
    }
    if (str_ends_with($f, '.php') && stripos($src, '<iframe') !== false) {
        $embeds = true;
    }
    if (str_ends_with($f, '.php') && preg_match('#<(script|link)[^>]+(src|href)="https?://#i', $src)) {
        $external = true;
    }
}
$pageNames = [
    'app' => 'sitenin geneli', 'home' => 'ana sayfa', 'about' => 'Hakkımızda', 'services' => 'Hizmetler',
    'service' => 'hizmet sayfaları', 'refs' => 'Referanslar', 'blog' => 'Makaleler', 'post' => 'makale sayfaları',
    'contact' => 'İletişim', 'signup' => 'Haberdar Ol', 'bank' => 'Hesap Numaralarımız', 'mission' => 'Misyonumuz',
    'vision' => 'Vizyonumuz', 'stones' => 'Mihenk Taşlarımız',
];
$storageWhere = implode(', ', array_map(fn($p) => $pageNames[$p] ?? $p, array_unique($storagePages)));
$mail = '<a class="link" href="mailto:' . e(cfg('email')) . '">' . e(cfg('email')) . '</a>';
$kvkk = '<a class="link" href="' . url('kurumsal/kvkk-aydinlatma-metni') . '">KVKK Aydınlatma Metni</a>';

/* ---------- Kısaca ---------- */

$summary = [];
$summary[] = $usesCookies
    ? 'Bu site yalnızca çalışması için <mark>zorunlu</mark> teknik çerezler kullanır.'
    : 'Bu site tarayıcınıza <mark>çerez yerleştirmez</mark>.';
$summary[] = 'Ziyaretinizi izleyen analiz, reklam ya da sosyal medya aracı yok.';
if (!$external) {
    $summary[] = 'Yazı tipleri ve kodlar kendi sunucumuzdan gelir; ziyaretiniz <mark>üçüncü taraflara</mark> bildirilmez.';
}
if ($storagePages) {
    $summary[] = 'Bazı sayfalar yaptığınız seçimleri yalnızca kendi tarayıcınızda hatırlar; bu bilgi bize gelmez.';
}
$summary[] = 'Form gönderirseniz kötüye kullanımı önlemek için IP adresiniz işlenir. Ayrıntısı ' . $kvkk . '’nde.';

/* ---------- Maddeler ---------- */

$maddeler = [];

$maddeler[] = [
    'title' => 'Amaç ve kapsam',
    'paras' => [
        'Bu politika, ' . e(cfg('name')) . ' tarafından yayımlanan ' . e(preg_replace('#^https?://#', '', (string) cfg('url'))) . ' internet sitesinin, ziyaretiniz sırasında tarayıcınızda ve cihazınızda hangi bilgileri sakladığını ve hangilerini saklamadığını açıklar.',
        'Formlar aracılığıyla bize ilettiğiniz kişisel verilerin işlenmesi ' . $kvkk . '’nde ayrıca düzenlenmiştir.',
    ],
    'sade' => 'Bu sayfa, sitenin cihazınızda ne bıraktığını anlatır. Formlara yazdıklarınız başka bir sayfanın konusu.',
];

$maddeler[] = [
    'title' => 'Çerez nedir',
    'paras' => [
        'Çerezler, bir internet sitesi ziyaret edildiğinde tarayıcı aracılığıyla cihaza kaydedilen ve sonraki ziyaretlerde siteye geri gönderilebilen küçük metin dosyalarıdır. Oturumu açık tutmak, tercihleri hatırlamak, ziyaretçi davranışını ölçmek ya da reklam göstermek amacıyla kullanılabilirler.',
    ],
    'sade' => 'Sitenin tarayıcınıza bıraktığı küçük not kâğıtları. Bazıları işe yarar, bazıları sizi takip etmek içindir.',
];

$maddeler[] = [
    'title' => 'Bu sitede kullanılan çerezler',
    'paras' => $usesCookies
        ? [
            'Sitemiz yalnızca güvenli ve doğru çalışması için zorunlu olan teknik çerezleri kullanır. Analiz, reklam, hedefleme ya da sosyal medya çerezi kullanılmaz.',
            'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti kendi teknik çerezlerini kullanırsa bu madde buna göre güncellenir.',
        ]
        : [
            'Sitemiz tarayıcınıza çerez yerleştirmez. Oturum çerezi, tercih çerezi, analiz çerezi, reklam ya da hedefleme çerezi ve sosyal medya çerezi kullanılmaz; bu nedenle sitede bir çerez onay penceresi de bulunmaz.',
            'Sitenin barındırıldığı sunucu ya da güvenlik hizmeti (örneğin saldırı koruması) kendi teknik çerezlerini kullanmaya başlarsa bu madde buna göre güncellenir.',
        ],
    'sade' => $usesCookies
        ? 'Yalnızca sitenin çalışması için gerekenler var. Sizi takip eden çerez yok.'
        : 'Çerez yok. O yüzden size “çerezleri kabul ediyor musunuz?” diye sormuyoruz.',
];

$maddeler[] = [
    'title' => 'Formlar ve güvenlik kayıtları',
    'paras' => [
        'İletişim, Haberdar Ol, bülten ve iş başvurusu formlarında, gönderimin bir insan tarafından yapıldığını ayırt etmek için forma imzalı, tek kullanımlık bir belirteç eklenir; forma ilk dokunduğunuzda tarayıcınız küçük bir hesaplama yapıp sonucunu da forma yazar. Bu bilgiler çerez olarak saklanmaz; formun içinde durur ve yalnızca formu gönderdiğinizde bize iletilir. Kullanılmış belirteçlerin özeti sunucumuzda en çok iki saat tutulur.',
        'Kısa sürede çok sayıda gönderim yapılmasını engellemek için, form gönderildiğinde IP adresinizin özeti (hash) ve son gönderim zamanları sunucumuzda tutulur. Bu kayıt yalnızca bu amaçla kullanılır ve kısa süre sonra silinir. Gönderdiğiniz formun kendisinin (IP adresiniz dahil) nasıl saklandığı ' . $kvkk . '’nde anlatılmıştır.',
        'Formda yazdığınız bilgilerin nasıl işlendiği ' . $kvkk . '’nde anlatılmıştır.',
    ],
    'sade' => 'Formlar çerezsiz çalışır. Spam’i engellemek için form gönderdiğinizde IP adresinizin bir özetini kısa süre kullanırız.',
];

$maddeler[] = [
    'title' => 'Tarayıcı depolama alanı',
    'paras' => $storagePages
        ? [
            'Sitenin bazı bölümleri (' . e($storageWhere) . ') yaptığınız seçimleri hatırlamak için tarayıcınızın yerel depolama alanını (localStorage ya da sessionStorage) kullanır. Bu bilgi yalnızca cihazınızda tutulur, sunucumuza gönderilmez.',
            'Bu bilgileri tarayıcınızın “site verilerini temizle” seçeneğiyle dilediğiniz zaman silebilirsiniz; site bundan sonra da çalışmaya devam eder.',
        ]
        : [
            'Site, tarayıcınızın yerel depolama alanına (localStorage, sessionStorage ya da IndexedDB) bilgi yazmaz.',
        ],
    'sade' => $storagePages
        ? 'İşaretlediğiniz bir şeyi hatırlamak için tarayıcınıza not düşebiliriz. O not sizde kalır, bize gelmez.'
        : 'Tarayıcınıza başka türlü bir not da bırakmıyoruz.',
];

$maddeler[] = [
    'title' => 'Dış kaynaklar ve bağlantılar',
    'paras' => array_values(array_filter([
        $external
            ? null
            : 'Sitedeki yazı tipleri, görseller ve kod kütüphaneleri kendi sunucumuzdan yüklenir. Sayfaları açtığınızda yazı tipi, harita ya da istatistik hizmeti sunan üçüncü taraf sunuculara istek gönderilmez.',
        $embeds
            ? 'Bazı sayfalarda üçüncü taraflara ait gömülü içerikler (örneğin harita) bulunabilir. Bu içerikler kendi çerez politikalarına göre çerez kullanabilir.'
            : null,
        'WhatsApp, Google Haritalar, ' . (feature('blog') ? 'makalelerdeki paylaşım düğmeleri (LinkedIn, X, WhatsApp), ' : '') . 'sosyal medya hesaplarımız ve duyurulardaki kurum sayfalarına verilen bağlantılara tıkladığınızda ilgili sitenin kendi çerez ve gizlilik politikaları geçerli olur. Bu bağlantılara tıklamadığınız sürece o sitelere hiçbir bilgi gitmez.',
    ])),
    'sade' => 'Biz kimseye “şu kişi siteye girdi” diye haber vermiyoruz. Ama WhatsApp ya da Instagram bağlantısına tıklarsanız artık onların sitesindesiniz.',
];

$maddeler[] = [
    'title' => 'Çerezleri yönetme',
    'paras' => [
        'Kullandığınız tarayıcının ayarlarından çerezleri ve site verilerini görüntüleyebilir, silebilir ya da engelleyebilirsiniz.' . ($usesCookies
            ? ' Zorunlu çerezlerin engellenmesi sitenin bazı işlevlerinin beklendiği gibi çalışmamasına yol açabilir.'
            : ' Sitemiz çerez kullanmadığı için bu ayarları değiştirmeniz sitenin çalışmasını etkilemez.'),
    ],
    'sade' => $usesCookies ? 'Tarayıcı ayarlarınız sizin elinizde.' : 'Tarayıcı ayarlarınız sizin elinizde. Neyi kapatırsanız kapatın, bu site çalışır.',
];

$maddeler[] = [
    'title' => 'Değişiklikler ve yürürlük',
    'paras' => [
        'Sitede çerez ya da benzeri bir teknoloji kullanmaya başlarsak bu politikayı önceden güncelleriz ve gerekli olduğu durumlarda onayınızı isteriz.',
        'Bu politika yayımlandığı tarihte yürürlüğe girer. Sorularınız için ' . $mail . ' adresine yazabilirsiniz.',
    ],
    'sade' => 'Bir şey değişirse önce bu sayfayı değiştiririz.',
];

legal_doc([
    'no'       => 'ARS-' . date('Y') . '/012',
    'subject'  => 'Çerezler ve tarayıcı verileri',
    'h1'       => 'Çerez politikası',
    'updated'  => '2026-10-05',
    'lead'     => 'Sitemizi gezerken tarayıcınızda neyin kaldığını ve neyin kalmadığını anlatan metin.',
    'summary'  => $summary,
    'maddeler' => $maddeler,
    'next'     => ['label' => 'KVKK metni', 'href' => url('kurumsal/kvkk-aydinlatma-metni'), 'no' => '13'],
]);
