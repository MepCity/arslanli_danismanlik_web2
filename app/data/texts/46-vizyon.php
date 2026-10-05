<?php
/** Vizyonumuz sayfası (app/pages/vizyon.php + assets/js/pages/vision.js): mühürlü zarf ve mektup. Mektubun selamı, girişi, maddeleri ve kapanışı Kurumsal içerik > Misyon ve vizyon bölümünden gelir. */
return [
    'vizyon' => [
        'label' => 'Vizyonumuz',
        'url'   => 'kurumsal/vizyonumuz',
        'icon'  => 'eye',
        'sections' => [
            '1. Başlık' => [
                'vizyon.hero.sayi' => ['Başlığın üstündeki satır: sayı', 'Sayı: [kalın]ARS-{yil}/009[/kalın]', 'rich', 'Kâğıdın üstündeki küçük satırın ilk parçası. {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 40]],
                'vizyon.hero.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]Vizyonumuz[/kalın]', 'rich', '', ['max' => 40]],
                'vizyon.hero.acilis' => ['Başlığın üstündeki satır: açılış yılı', 'Açılış: [kalın]{acilis_yili}[/kalın]', 'rich', '{acilis_yili} yerine mektubun açılış yılı gelir (Kurumsal içerik).', ['vars' => ['acilis_yili'], 'need' => ['acilis_yili'], 'max' => 40]],
                'vizyon.hero.baslik' => ['Büyük başlık', 'Vizyonumuz', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 40]],
                'vizyon.hero.giris' => ['Başlığın altındaki açıklama', 'Vizyonumuzu, kuruluşumuzun {acilis_yil_sayisi|sıra} yılında, {acilis_yili}’de açılmak üzere bir mektuba yazdık. Beklemek istemezseniz şimdi açabilirsiniz.', 'text', '{acilis_yil_sayisi|sıra} yerine açılış yılının kuruluştan sayılan sırası yazıyla gelir (otuzuncu…), {acilis_yili} yerine mektubun açılış yılı gelir; ikisini de silmeyin.', ['vars' => ['acilis_yil_sayisi', 'acilis_yili'], 'need' => ['acilis_yil_sayisi', 'acilis_yili']]],
            ],
            '2. Zarf' => [
                'vizyon.zarf.alici' => ['Zarfın üstünde alıcı etiketi', 'Alıcı:', 'line', 'Yanında firmanın adı görünür.', ['max' => 16]],
                'vizyon.zarf.adres' => ['Zarfın üstünde adres etiketi', 'Adres:', 'line', '', ['max' => 16]],
                'vizyon.zarf.adres_deger' => ['Zarfın üstünde adres değeri', 'İstanbul, {acilis_yili}', 'line', '{acilis_yili} yerine mektubun açılış yılı gelir.', ['vars' => ['acilis_yili'], 'need' => ['acilis_yili'], 'max' => 40]],
                'vizyon.zarf.kase_ust' => ['Zarfın kaşesinde büyük satır', '{acilis_yili}’de açılacaktır', 'line', 'Kaşede büyük harfle basılır. {acilis_yili} yerine mektubun açılış yılı gelir. Kaşeye sığması için kısa tutun.', ['vars' => ['acilis_yili'], 'need' => ['acilis_yili'], 'max' => 30]],
                'vizyon.zarf.kase_alt' => ['Zarfın kaşesinde küçük satır', 'kuruluşumuzun {acilis_yil_sayisi}. yılı', 'line', 'Kaşede büyük harfle basılır. {acilis_yil_sayisi} yerine açılış yılının kuruluştan sayılan sırası (30) gelir.', ['vars' => ['acilis_yil_sayisi'], 'need' => ['acilis_yil_sayisi'], 'max' => 40]],
                'vizyon.zarf.arka' => ['Zarf açılırken kapağın iç yüzündeki yazı', '{acilis_yili}’de açılacak', 'line', 'Mektup açılırken katlanan kâğıdın arkasında görünür. {acilis_yili} yerine mektubun açılış yılı gelir.', ['vars' => ['acilis_yili'], 'need' => ['acilis_yili'], 'max' => 40, 'js' => true]],
                'vizyon.zarf.dugme' => ['Zarfın altındaki düğme', 'Mektubu şimdi açın', 'line', '', ['max' => 40]],
            ],
            '3. Mektup' => [
                'vizyon.mektup.etiket' => ['Mektubun erişilebilirlik adı', 'Vizyon mektubu', 'line', 'Görünmez; ekran okuyucular içindir.'],
                'vizyon.mektup.tarih' => ['Mektubun başındaki tarih', 'İstanbul, Ekim 2026', 'line', 'Mektubun yazıldığı yer ve zaman; mektubun girişindeki “2026 sonbaharında” ile uyumlu tutun.', ['max' => 40]],
                'vizyon.mektup.konu' => ['Mektubun konu satırı', 'Konu: [kalın]Vizyonumuz[/kalın] · Açılış tarihi: [kalın]{acilis_yili}[/kalın]', 'rich', '{acilis_yili} yerine mektubun açılış yılı gelir.', ['vars' => ['acilis_yili'], 'need' => ['acilis_yili'], 'max' => 80]],
                'vizyon.mektup.imza_tarih' => ['Mektubun sonunda imzanın yanındaki tarih', 'İstanbul, Ekim 2026', 'line', 'Firma adının yanında görünür.', ['max' => 40]],
            ],
            '4. Sayfanın sonundaki bağlantı' => [
                'vizyon.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine Mihenk Taşlarımız sayfasının evrak numarası gelir. Bağlantının büyük yazısı o sayfanın adıdır (Sayfa adları).', ['vars' => ['no' => 'Mihenk Taşlarımız sayfasının numarası'], 'need' => ['no'], 'max' => 40]],
            ],
            '5. Arama motorları' => [
                'vizyon.seo.description' => ['Arama motoru açıklaması', 'Vizyonumuzu, kuruluşumuzun {acilis_yil_sayisi|sıra} yılında, {acilis_yili}’de açılmak üzere bir mektuba yazdık: işletmelerin Ar-Ge ve yenilik kapasitesi, zamanında bilgi ve yeni nesil destek programları.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. {acilis_yil_sayisi|sıra} yerine açılış yılının sırası yazıyla (otuzuncu…), {acilis_yili} yerine açılış yılı gelir.', ['vars' => ['acilis_yil_sayisi', 'acilis_yili'], 'need' => ['acilis_yil_sayisi', 'acilis_yili'], 'max' => 320]],
            ],
        ],
    ],
];
