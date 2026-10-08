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
                'cerez.baslik.guncelleme' => ['Son güncelleme tarihi', '6 Ekim 2026', 'line', 'Sayfanın üstünde ve metnin sonunda görünür. Metni değiştirdiğinizde bu tarihi de güncelleyin; gün ay yıl biçiminde yazın.', ['max' => 30]],
                'cerez.baslik.baslik' => ['Büyük başlık', 'Çerez politikası', 'line', 'Sayfanın adı (menü ve alt bilgi) Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 60]],
                'cerez.baslik.giris' => ['Başlığın altındaki açıklama', 'Sitemizi gezerken tarayıcınızda neyin kaldığını ve neyin kalmadığını anlatan metin.', 'text'],
            ],
            '2. Kısaca kutusu (sağ üstte sade özet)' => [
                'cerez.kisaca.cerez_var' => ['Birinci cümle (sitede zorunlu çerez varsa)', 'Bu site yalnızca çalışması için [vurgu]zorunlu[/vurgu] teknik çerezler kullanır.', 'rich', 'Sitenin kodunda çerez kullanımı bulunursa görünür. Vurgulu kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'cerez.kisaca.cerez_yok' => ['Birinci cümle (sitede çerez yoksa)', 'Bu sitenin ziyaretçi sayfaları tarayıcınıza [vurgu]çerez yerleştirmez[/vurgu].', 'rich', 'Sitenin kodunda çerez kullanımı bulunmazsa görünür. Vurgulu kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'cerez.kisaca.analiz' => ['İkinci cümle: analiz ve reklam aracı olmadığı', 'Ziyaretinizi başka sitelere ileten analiz, reklam ya da sosyal medya aracı yok; ziyaretçi sayısını [vurgu]çerezsiz, kendi sayacımızla[/vurgu] tutarız.', 'rich', 'Her zaman görünür.', ['max' => 300]],
                'cerez.kisaca.ucuncu' => ['Üçüncü cümle: dış sunucu kullanılmadığı', 'Yazı tipleri ve kodlar kendi sunucumuzdan gelir; ziyaretiniz [vurgu]üçüncü taraflara[/vurgu] bildirilmez.', 'rich', 'Sitede dış sunucudan yüklenen bir betik ya da stil dosyası yoksa görünür. Vurgulu kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'cerez.kisaca.depolama' => ['Dördüncü cümle: tarayıcıda hatırlanan seçimler', 'Bazı sayfalar yaptığınız seçimleri yalnızca kendi tarayıcınızda hatırlar; bu bilgi bize gelmez.', 'rich', 'Sitenin bazı sayfaları tarayıcı depolamasını kullanıyorsa görünür.', ['max' => 300]],
                'cerez.kisaca.form' => ['Beşinci cümle: form gönderirken IP adresi', 'Form gönderirseniz kötüye kullanımı önlemek için IP adresiniz işlenir. Ayrıntısı {kvkk_baglanti}’nde.', 'rich', 'Her zaman görünür. {kvkk_baglanti} yerine KVKK Aydınlatma Metni sayfasının adı, tıklanabilir olarak gelir; silmeyin.', ['vars' => ['kvkk_baglanti' => 'KVKK sayfası bağlantısı'], 'need' => ['kvkk_baglanti'], 'max' => 300]],
            ],
            '3. Tarayıcı depolaması maddesinde sayılan sayfa adları' => [
                'cerez.depolama.spotlight' => ['Açılışta öne çıkan duyuru penceresi', 'açılışta öne çıkan duyuru penceresi', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer. Yalnızca bu pencere depolamayı kullanıyorsa görünür.', ['max' => 50]],
                'cerez.depolama.app' => ['Sitenin geneli', 'sitenin geneli', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede, depolamayı kullanan bölümler sayılırken bu ad geçer. Yalnızca o bölüm depolamayı kullanıyorsa görünür.', ['max' => 40]],
                'cerez.depolama.home' => ['Ana sayfa', 'ana sayfa', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.about' => ['Hakkımızda sayfası', 'Hakkımızda', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.services' => ['Hizmetler sayfası', 'Hizmetler', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.service' => ['Hizmet sayfaları', 'hizmet sayfaları', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.refs' => ['Referanslar sayfası', 'Referanslar', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.blog' => ['Makaleler sayfası', 'Makaleler', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.post' => ['Makale sayfaları', 'makale sayfaları', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.contact' => ['İletişim sayfası', 'İletişim', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.signup' => ['Haberdar Ol sayfası', 'Haberdar Ol', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.bank' => ['Hesap numaraları sayfası', 'Hesap Numaralarımız', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.mission' => ['Misyon sayfası', 'Misyonumuz', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.vision' => ['Vizyon sayfası', 'Vizyonumuz', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
                'cerez.depolama.stones' => ['Mihenk taşları sayfası', 'Mihenk Taşlarımız', 'line', 'Tarayıcı depolama alanı maddesindeki cümlede bu bölümün adı olarak geçer.', ['max' => 40]],
            ],
            '4. Sayfanın sonundaki bağlantı' => [
                'cerez.sonraki.ad' => ['Sonraki sayfa bağlantısının büyük yazısı', 'KVKK metni', 'line', 'KVKK Aydınlatma Metni sayfasına götürür; küçük yazı (Sonraki evrak) Yasal metinler grubundadır.', ['max' => 50]],
            ],
            '5. Arama motorları' => [
                'cerez.seo.description' => ['Arama motoru açıklaması', '{firma} sitesinin tarayıcınızda ne sakladığı ve ne saklamadığı: çerez, analiz aracı ve reklam takibi yok; ziyaret sayacı çerezsizdir.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma'], 'max' => 320]],
            ],
        ],
    ],
];
