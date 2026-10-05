<?php
/** Referanslar sayfası (app/pages/referans.php + assets/js/pages/refs.js): kaşe masası ve kayıt defteri. Kurum adları ve kaşeler Referanslar bölümünden gelir. */
return [
    'referans' => [
        'label' => 'Referanslar',
        'url'   => 'referans',
        'icon'  => 'images',
        'sections' => [
            '1. Başlık' => [
                'referans.hero.sayi' => ['Başlığın üstündeki satır: sayı', 'Sayı: [kalın]ARS-{yil}/004[/kalın]', 'rich', 'Kâğıdın üstündeki küçük satırın ilk parçası. {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 40]],
                'referans.hero.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]Referanslar[/kalın]', 'rich', '', ['max' => 40]],
                'referans.hero.baslik' => ['Büyük başlık', 'Dosyasını hazırladığımız kurumlardan bazıları.', 'line', '', ['max' => 70]],
            ],
            '2. Kaşe masası' => [
                'referans.masa.giris' => ['Başlığın yanındaki açıklama', 'Aşağıdaki sümen sizin. Masanın üzerinde bir yere tıkladığınızda sıradaki kurumun kaşesi oraya basılır. Kurumların tam listesi masanın altındaki kayıt defterinde.', 'text'],
                'referans.masa.hepsi' => ['Birinci düğme: hepsini bas', 'Hepsini bas', 'line', '', ['max' => 30]],
                'referans.masa.bir' => ['İkinci düğme: bir kaşe bas', 'Bir kaşe bas', 'line', '', ['max' => 30]],
                'referans.masa.temizle' => ['Üçüncü düğme: masayı temizle', 'Masayı temizle', 'line', '', ['max' => 30]],
                'referans.masa.siradaki' => ['Düğmelerin altındaki sıradaki kaşe yazısı', 'Sıradaki kaşe: {ad}', 'line', '{ad} yerine basılacak kurumun adı gelir; kaşe basıldıkça kendiliğinden değişir.', ['vars' => ['ad' => 'sıradaki kurumun adı'], 'need' => ['ad'], 'max' => 40]],
                'referans.masa.ipucu_fare' => ['Sümen üzerindeki ipucu: fareli bilgisayar', 'Tıklayın, kaşe basılsın.', 'line', 'Yalnızca fare kullanan ziyaretçilere görünür.', ['max' => 50]],
                'referans.masa.ipucu_dokunma' => ['Sümen üzerindeki ipucu: dokunmatik ekran', 'Dokunun, kaşe basılsın.', 'line', 'Yalnızca telefon ve tablet kullanan ziyaretçilere görünür.', ['max' => 50]],
            ],
            '3. Kayıt defteri' => [
                'referans.defter.etiket' => ['Bölüm üstündeki küçük etiket', 'Kayıt defteri', 'line', '', ['max' => 30]],
                'referans.defter.baslik' => ['Büyük başlık', 'Kurumlar, sırasıyla.', 'line', '', ['max' => 50]],
                'referans.defter.sutun_sira' => ['Defter sayfasında birinci sütunun başlığı', 'Sıra', 'line', '', ['max' => 16]],
                'referans.defter.sutun_kurum' => ['Defter sayfasında ikinci sütunun başlığı', 'Kurum', 'line', '', ['max' => 24]],
                'referans.defter.sutun_kase' => ['Defter sayfasında üçüncü sütunun başlığı', 'Kaşe', 'line', '', ['max' => 24]],
            ],
            '4. Sayfanın sonundaki bağlantı' => [
                'referans.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine sonraki sayfanın evrak numarası gelir. Bağlantının büyük yazısı sonraki sayfanın adıdır (Sayfa adları).', ['vars' => ['no' => 'sonraki sayfanın numarası'], 'need' => ['no'], 'max' => 40]],
            ],
            '5. Arama motorları' => [
                'referans.seo.description' => ['Arama motoru açıklaması', 'Hibe, teşvik ve Ar-Ge başvurularında dosyasını hazırladığımız kurumlardan bazıları: Pilot Seating, Tork, CNK Havacılık, Acar Kaporta, Sacform, Mado, NSK ve diğerleri.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. Kurum adları Referanslar bölümü değişirse güncelleyin. 150-160 karakter önerilir.', ['max' => 320]],
            ],
        ],
    ],
];
