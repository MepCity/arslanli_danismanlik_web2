<?php
/**
 * Arama motorları ve yapay zekâ asistanları için makine okunur çıktılar (app/agents/*): llms.txt, her sayfanın Markdown sürümü,
 * haber akışları. Ziyaretçi bunları sitede görmez; yapay zekâ asistanları ve akış okuyucular okur. Sayfa adları "Menü, Fihrist ve alt bilgi"
 * grubundan, sayfa içerikleri kendi gruplarından gelir; burada yalnızca bu çıktılara özgü çerçeve yazıları durur.
 */
return [
    'makine' => [
        'label' => 'Yapay zekâ ve arama çıktıları (llms.txt, Markdown, akış)',
        'url'   => 'llms.txt',
        'icon'  => 'robot',
        'sections' => [
            '1. Sayfaların Markdown sürümü' => [
                'makine.md.html_surum' => ['Sayfanın sonundaki “HTML sürümü” satırı', 'Bu sayfanın HTML sürümü:', 'line', 'Her Markdown sayfasının son satırıdır; yazının ardından sayfanın adresi gelir. Görünmez; yapay zekâ asistanları içindir.', ['max' => 60]],
            ],
            '2. llms.txt (site özeti)' => [
                'makine.llms.tanitim' => ['Başlığın altındaki tanıtım paragrafı', '{firma}, {kurulus} yılından beri {adres_kisa} merkezli çalışan bir hibe, teşvik ve yatırım danışmanlığı firmasıdır. Hizmet alanları: {hizmetler}. Türkiye genelindeki işletmelere hizmet verir. İletişim: {telefon}, {eposta}. Adres: {adres}. Dil: Türkçe.', 'text', 'Görünmez; yapay zekâ asistanları içindir. {hizmetler} yerine hizmet dosyalarının adları gelir.', ['vars' => ['firma', 'kurulus', 'adres_kisa', 'telefon', 'eposta', 'adres', 'hizmetler' => 'hizmet dosyalarının adları'], 'max' => 700]],
                'makine.llms.kurumsal' => ['“Kurumsal” bölümünün başlığı', 'Kurumsal', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 40]],
                'makine.llms.akis' => ['Haber akışı bölümünün başlığı', 'Haber akışı', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 40]],
                'makine.llms.takvim' => ['Çağrı takvimi bağlantısının adı', 'Çağrı takvimi (iCalendar)', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 60]],
                'makine.llms.takvim_not' => ['Çağrı takvimi bağlantısının açıklaması', 'Başvuru başlangıç, son başvuru ve sonuç tarihleri; takvim uygulamasına eklenebilir.', 'text', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 200]],
                'makine.llms.rss' => ['RSS akışı bağlantısının adı', 'RSS akışı', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 40]],
                'makine.llms.json' => ['JSON akışı bağlantısının adı', 'JSON Feed', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 40]],
                'makine.llms.akis_not' => ['Akış bağlantılarının açıklaması', 'Yazılar ve duyurular, {bicim}.', 'line', 'Görünmez. {bicim} yerine akışın biçimi (RSS 2.0, JSON Feed 1.1) gelir.', ['vars' => ['bicim' => 'akışın biçimi'], 'max' => 80]],
                'makine.llms.sitemap' => ['Site haritası bağlantısının adı', 'Site haritası', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 40]],
                'makine.llms.sitemap_not' => ['Site haritası bağlantısının açıklaması', 'Tüm herkese açık sayfaların listesi.', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 100]],
                'makine.llms.tam' => ['“Tüm içerik” bağlantısının adı', 'Tüm içerik tek dosyada', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 60]],
                'makine.llms.tam_not' => ['“Tüm içerik” bağlantısının açıklaması', 'Bu sitedeki bütün sayfaların Markdown sürümü, tek istekte.', 'line', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 100]],
                'makine.llms.tam_giris' => ['Tüm içerik dosyasının giriş cümlesi', 'Bu dosya, sitedeki tüm sayfaların Markdown sürümünü tek istekte verir. Sayfalar --- çizgisiyle ayrılmıştır; her sayfa kendi başlığıyla başlar ve HTML adresiyle biter.', 'text', 'Görünmez; yapay zekâ asistanları içindir.', ['max' => 300]],
            ],
            '3. Haber akışı (RSS ve JSON Feed)' => [
                'makine.akis.baslik' => ['Akışın başlığı', '{firma}: yazılar ve duyurular', 'line', 'Akış okuyucularda görünür.', ['vars' => ['firma'], 'max' => 80]],
                'makine.akis.yaklasan' => ['“Yaklaşan tarihler” başlığı', 'Yaklaşan tarihler', 'line', 'Duyuru akış öğelerinde görünür.', ['max' => 40]],
                'makine.akis.resmi' => ['“Resmi bağlantı” etiketi', 'Resmi bağlantı', 'line', 'Duyuru akış öğelerinde görünür.', ['max' => 40]],
                'makine.akis.duyuru' => ['Duyuru kategorisinin adı', 'Duyuru', 'line', 'Akışta duyuruların etiketi olarak görünür.', ['max' => 30]],
                'makine.akis.bilgi' => ['Türü belirsiz tarihin adı', 'Bilgilendirme', 'line', 'Akışta ve takvim satırlarında görünür.', ['max' => 30]],
            ],
        ],
    ],
];
