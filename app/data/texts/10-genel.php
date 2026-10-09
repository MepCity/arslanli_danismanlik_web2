<?php
/**
 * Her sayfada görünen çerçeve: sekme başlığı, sayfa adları, üst çubuk, Fihrist, alt bilgi, kaşe, WhatsApp düğmesi.
 * Şablonlar: app/partials/layout.php, logo.php, kase.php; sayfa adları: app/bootstrap.php (site_pages_all).
 */
return [
    'genel' => [
        'label' => 'Menü, Fihrist ve alt bilgi',
        'url'   => '',
        'icon'  => 'list',
        'sections' => [
            '1. Sekme başlığı ve arama motorları' => [
                'genel.seo.title_pattern' => ['Sekme başlığı (iç sayfalar)', '{sayfa} | {firma}', 'line', 'Tarayıcı sekmesinde ve Google sonuçlarında görünür. {sayfa} sayfanın adıyla, {firma} firma adıyla değişir.', ['vars' => ['sayfa' => 'sayfanın başlığı', 'firma'], 'need' => ['sayfa']]],
                'genel.seo.title_home' => ['Sekme başlığı (ana sayfa)', '{firma} | Hibe, teşvik ve Ar-Ge danışmanlığı', 'line', 'Ana sayfa ve kendi başlığı olmayan sayfalarda görünür.', ['vars' => ['firma']]],
                'genel.seo.description' => ['Arama motoru açıklaması (açıklaması olmayan sayfalar)', '{firma}: {kurulus}’den beri TÜBİTAK, KOSGEB, Bakanlık, ihracat ve AB desteklerinde başvuru dosyası, proje yazımı ve yürütme. İstanbul.', 'text', 'Google sonuçlarında sayfa adının altında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma', 'kurulus'], 'max' => 320]],
            ],
            '2. Sayfa adları (menü, Fihrist, alt bilgi, sayfa etiketleri)' => [
                'genel.sayfa.home' => ['Sayfa adı: Ana sayfa', 'Ana sayfa', 'line', 'Fihristte, üst çubuktaki dizinde, alt bilgide ve “Evrak NN · ad” etiketinde görünür. Burada değiştirdiğiniz ad hepsinde birden değişir.', ['max' => 40]],
                'genel.sayfa.hakkimizda' => ['Sayfa adı: Hakkımızda', 'Hakkımızda', 'line', 'Fihristte, üst çubuktaki dizinde ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.hizmetler' => ['Sayfa adı: Hizmetler', 'Hizmetler', 'line', 'Fihristte, üst çubuktaki dizinde ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.referans' => ['Sayfa adı: Referanslar', 'Referanslar', 'line', 'Fihristte, üst çubuktaki dizinde ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.blog' => ['Sayfa adı: Makaleler (blog)', 'Makaleler', 'line', 'Yazılar bölümü açıksa Fihristte ve üst çubuktaki dizinde görünür.', ['max' => 40]],
                'genel.sayfa.duyurular' => ['Sayfa adı: Duyurular', 'Duyurular', 'line', 'Fihristte, üst çubuktaki dizinde, alt bilgide ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.kariyer' => ['Sayfa adı: Kariyer', 'Kariyer', 'line', 'Fihristte, üst çubuktaki dizinde, alt bilgide ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.iletisim' => ['Sayfa adı: İletişim', 'İletişim', 'line', 'Fihristte, üst çubuktaki dizinde ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.haberdarol' => ['Sayfa adı: Haberdar Ol (bülten kuponu)', 'Haberdar Ol', 'line', 'Fihristin “Kurumsal” sütununda ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.misyon' => ['Sayfa adı: Misyonumuz', 'Misyonumuz', 'line', 'Fihristin “Kurumsal” sütununda ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.vizyon' => ['Sayfa adı: Vizyonumuz', 'Vizyonumuz', 'line', 'Fihristin “Kurumsal” sütununda ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.mihenk' => ['Sayfa adı: Mihenk Taşlarımız', 'Mihenk Taşlarımız', 'line', 'Fihristin “Kurumsal” sütununda ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.hesap' => ['Sayfa adı: Hesap Numaralarımız', 'Hesap Numaralarımız', 'line', 'Fihristin “Kurumsal” sütununda, alt bilgide ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.cerez' => ['Sayfa adı: Çerez Politikası', 'Çerez Politikası', 'line', 'Alt bilgide ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.sayfa.kvkk' => ['Sayfa adı: KVKK Aydınlatma Metni', 'KVKK Aydınlatma Metni', 'line', 'Alt bilgide ve sayfa etiketinde görünür.', ['max' => 40]],
                'genel.folio.word' => ['Sayfa etiketindeki “Evrak” sözcüğü', 'Evrak', 'line', 'Üst çubukta ve her sayfanın başında “Evrak 03 · Ad” biçiminde görünür.', ['max' => 16]],
            ],
            '3. Üst çubuk' => [
                'genel.header.skip' => ['Klavye kullanıcıları için “içeriğe geç” bağlantısı', 'İçeriğe geç', 'line', 'Normalde görünmez; ekran okuyucular ve klavye kullanıcıları içindir.'],
                'genel.header.logo_label' => ['Üst çubuktaki logonun erişilebilirlik açıklaması', '{firma}, ana sayfa', 'line', 'Görünmez; ekran okuyucular logoya gelince bunu okur.', ['vars' => ['firma']]],
                'genel.header.nav_label' => ['Üst çubuktaki sayfa dizininin erişilebilirlik adı', 'Ana sayfalar', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.header.cta' => ['Sağ üstteki düğme', 'Ön görüşme', 'line', 'İletişim sayfasına gider. Kısa tutun.', ['max' => 30]],
                'genel.header.menu' => ['Fihristi açan düğme', 'Fihrist', 'line', 'Sağ üstte, cep telefonunda da görünür. Kısa tutun.', ['max' => 20]],
            ],
            '4. Fihrist (açılır site haritası)' => [
                'genel.fihrist.label' => ['Fihrist penceresinin erişilebilirlik adı', 'Fihrist: site haritası', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.fihrist.logo_label' => ['Fihristteki logonun erişilebilirlik açıklaması', 'Ana sayfa', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.fihrist.close' => ['Fihristi kapatan düğme', 'Kapat', 'line', '', ['max' => 20]],
                'genel.fihrist.nav_label' => ['Sayfa listesinin erişilebilirlik adı', 'Ana menü', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.fihrist.pages_head' => ['Birinci sütun başlığı (sayfalar)', 'Fihrist', 'line', '', ['max' => 24]],
                'genel.fihrist.unit' => ['Sütun başlıklarının yanındaki küçük sözcük', 'sayfa', 'line', 'Birinci ve üçüncü sütun başlığının sağında görünür.', ['max' => 16]],
                'genel.fihrist.files_head' => ['İkinci sütun başlığı (hizmet dosyaları)', 'Dosyalar', 'line', 'Başlığın yanında Hizmetler sayfasının numarası görünür.', ['max' => 24]],
                'genel.fihrist.files_label' => ['Hizmet listesinin erişilebilirlik adı', 'Hizmetler', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.fihrist.corp_head' => ['Üçüncü sütun başlığı (kurumsal sayfalar)', 'Kurumsal', 'line', '', ['max' => 24]],
                'genel.fihrist.corp_label' => ['Kurumsal listenin erişilebilirlik adı', 'Kurumsal', 'line', 'Görünmez; ekran okuyucular içindir.'],
            ],
            '5. Alt bilgi' => [
                'genel.footer.salute' => ['Kapanış selamı', 'Saygılarımızla[eğik],[/eğik]', 'rich', 'Sayfa sonunda, imzanın üstünde. Virgül eğik yazılır; kalsın.', ['max' => 40]],
                'genel.footer.sign' => ['Selamın altındaki çağrı cümlesi', 'Bir sorunuz, bir proje fikriniz ya da masanızda bekleyen bir çağrı metni varsa bize yazın; okuyup size dönelim.', 'text', 'Kaşenin solunda görünür.'],
                'genel.footer.ekler' => ['“Ekler” başlığı', 'Ekler', 'line', 'Telefon, e-posta ve adres bloklarının üstünde.', ['max' => 24]],
                'genel.footer.ek1' => ['Birinci ek: telefon etiketi', 'Ek-1 · Telefon', 'line', 'Telefon numarasının üstündeki küçük etiket.', ['max' => 40]],
                'genel.footer.whatsapp_link' => ['Telefonun altındaki WhatsApp bağlantısı', 'WhatsApp’tan yazın', 'line', '', ['max' => 40]],
                'genel.footer.ek2' => ['İkinci ek: e-posta etiketi', 'Ek-2 · E-posta', 'line', 'E-posta adresinin üstündeki küçük etiket.', ['max' => 40]],
                'genel.footer.ek3' => ['Üçüncü ek: adres etiketi', 'Ek-3 · Adres', 'line', 'Adresin üstündeki küçük etiket.', ['max' => 40]],
                'genel.footer.copyright' => ['Telif satırı', '© {kurulus}–{yil} {firma}', 'line', 'En altta, sol tarafta.', ['vars' => ['kurulus', 'yil', 'firma']]],
                'genel.footer.nav_label' => ['Alt bağlantıların erişilebilirlik adı', 'Yasal', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.footer.bulten' => ['Alt bağlantı: bültene kayıt', 'Haberdar ol', 'line', 'Bülten bölümü açıksa en altta görünür.', ['max' => 40]],
            ],
            '6. Kaşe' => [
                'genel.kase.label' => ['Kaşenin erişilebilirlik açıklaması', 'Firma kaşesi: {firma}, {adres}, {vergi_dairesi} Vergi Dairesi {vergi_no}', 'text', 'Görünmez; ekran okuyucular içindir.', ['vars' => ['firma', 'adres', 'vergi_dairesi', 'vergi_no']]],
                'genel.kase.unvan' => ['Kaşenin birinci satırı (unvan)', '{firma|büyük}', 'line', 'Kaşede büyük harfle basılır. Firma adı İletişim ve şirket bölümünden değişir.', ['vars' => ['firma'], 'max' => 60]],
                'genel.kase.adres' => ['Kaşenin adres satırları', "Yenişehir Mah. Millet Cad. Sümbül Sk. No:10 D:28\nStarport Residence · 34912 Pendik / İSTANBUL", 'lines', 'Kaşede iki satır olarak basılır: her satırı ayrı satıra yazın. Kaşeye sığması için satırları kısa tutun.', ['max' => 112, 'lines' => 2]],
                'genel.kase.vergi' => ['Kaşenin vergi satırı', '{vergi_dairesi|büyük} V.D. · {vergi_no}', 'line', 'Vergi dairesi ve numara İletişim ve şirket bölümünden gelir.', ['vars' => ['vergi_dairesi', 'vergi_no'], 'max' => 50]],
                'genel.kase.tel' => ['Kaşenin telefon satırı', 'Tel: {telefon}', 'line', '', ['vars' => ['telefon'], 'max' => 40]],
            ],
            '7. Logo ve WhatsApp düğmesi' => [
                'genel.logo.label' => ['Logonun erişilebilirlik açıklaması', '{firma}', 'line', 'Görünmez; ekran okuyucular içindir.', ['vars' => ['firma']]],
                'genel.whatsapp.label' => ['Sağ alttaki WhatsApp düğmesinin erişilebilirlik açıklaması', 'WhatsApp ile yazın', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'genel.whatsapp.bolge' => ['WhatsApp düğmesini saran bölgenin adı', 'Hızlı iletişim', 'line', 'Görünmez; ekran okuyucular içindir (sayfa bölgeleri listesinde görünür).', ['max' => 40]],
                'genel.whatsapp.text' => ['Sağ alttaki WhatsApp düğmesinin yazısı', 'WhatsApp', 'line', 'WhatsApp bölümü Görünürlük ayarından açıksa görünür. Küçük ekranlarda yalnızca simge görünür.', ['max' => 24]],
            ],
        ],
    ],
];
