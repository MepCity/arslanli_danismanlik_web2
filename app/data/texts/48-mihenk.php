<?php
/** Mihenk Taşlarımız sayfası (app/pages/mihenk.php + assets/js/pages/stones.js): siyah taş ve altı ilke. İlkelerin başlıkları ve açıklamaları Kurumsal içerik > Süreç ve ilkeler bölümünden gelir. */
return [
    'mihenk' => [
        'label' => 'Mihenk Taşlarımız',
        'url'   => 'kurumsal/mihenk-taslarimiz',
        'icon'  => 'shield-check',
        'sections' => [
            '1. Başlık' => [
                'mihenk.hero.sayi' => ['Başlığın üstündeki satır: sayı', 'Sayı: [kalın]ARS-{yil}/010[/kalın]', 'rich', 'Kâğıdın üstündeki küçük satırın ilk parçası. {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 40]],
                'mihenk.hero.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]Çalışma ilkelerimiz[/kalın]', 'rich', '', ['max' => 50]],
                'mihenk.hero.baslik' => ['Büyük başlık', 'Mihenk taşlarımız', 'line', 'Sayfanın adı Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 40]],
                'mihenk.hero.giris' => ['Başlığın altındaki açıklama (büyük yazı)', 'Kuyumcular altının ayarını anlamak için onu siyah bir taşa sürter; taşta kalan izin rengi ayarı gösterir. Bizim işimizin ayarını da bu {ilke_sayisi|yazı} ilke gösterir.', 'text', '{ilke_sayisi|yazı} yerine ilke sayısı yazıyla gelir (altı…); silmeyin.', ['vars' => ['ilke_sayisi'], 'need' => ['ilke_sayisi']]],
                'mihenk.hero.not' => ['Açıklamanın altındaki küçük not', 'Kuyumcu taştaki izi, ayarı bilinen iğnelerin bıraktığı izlerle yan yana koyarak okur. Taşın sağ alt köşesindeki çizgiler o iğnelerin izleri.', 'text'],
            ],
            '2. Taş ve ilkeler' => [
                'mihenk.tas.etiket' => ['Taşın erişilebilirlik adı', '{ilke_sayisi|Yazı} ilke', 'line', 'Görünmez; ekran okuyucular içindir. {ilke_sayisi|Yazı} yerine ilke sayısı yazıyla gelir.', ['vars' => ['ilke_sayisi'], 'max' => 40]],
                'mihenk.tas.ayar' => ['Taşın köşesinde iğne çizgilerinin yanındaki yazı', 'ayar', 'line', 'Taşın sağ alt köşesindeki 8, 14, 18, 22, 24 sayılarının yanında küçük harfle görünür.', ['max' => 16]],
            ],
            '3. Alt çubuk' => [
                'mihenk.cubuk.ipucu_fare' => ['Taşın altındaki ipucu: fareli bilgisayar', 'Taşın üzerinde fareyle basılı tutup sürükleyin; izin altından ilkeler çıkar.', 'line', 'Yalnızca fare kullanan ziyaretçilere görünür.', ['max' => 100]],
                'mihenk.cubuk.ipucu_dokunma' => ['Taşın altındaki ipucu: dokunmatik ekran', 'Parmağınızı taşın üzerinde sağa sola sürün. Yukarı aşağı hareket sayfayı kaydırır.', 'line', 'Yalnızca telefon ve tablet kullanan ziyaretçilere görünür.', ['max' => 140]],
                'mihenk.cubuk.sayac' => ['İlke sayacı', '{n} / {toplam} ilke ortaya çıktı', 'line', 'Ziyaretçi taşı sürttükçe kendiliğinden artar. {n} yerine ortaya çıkan ilke sayısı, {toplam} yerine toplam ilke sayısı gelir.', ['vars' => ['n' => 'ortaya çıkan ilke sayısı', 'toplam' => 'toplam ilke sayısı'], 'need' => ['n', 'toplam'], 'max' => 50, 'js' => true]],
                'mihenk.cubuk.tamam' => ['İlke sayacı: hepsi ortaya çıkınca', '{ilke_sayisi|Yazı} ilkenin {ilke_sayisi|iyelik} da ortada.', 'line', 'Bütün ilkeler ortaya çıkınca sayacın yerine görünür. {ilke_sayisi|iyelik} sayıyı ek almış yazar (altısı…); ilke sayısı değişince kendiliğinden uyar.', ['vars' => ['ilke_sayisi'], 'need' => ['ilke_sayisi'], 'max' => 60, 'js' => true]],
                'mihenk.cubuk.hepsi' => ['Altındaki düğme: hepsini sür', 'Hepsini sür', 'line', '', ['max' => 30]],
            ],
            '4. Sayfanın sonundaki bağlantı' => [
                'mihenk.sonraki.etiket' => ['Sonraki sayfa bağlantısının küçük yazısı', 'Sonraki evrak · {no}', 'line', '{no} yerine Hakkımızda sayfasının evrak numarası gelir. Bağlantının büyük yazısı Hakkımızda sayfasının adıdır (Sayfa adları).', ['vars' => ['no' => 'Hakkımızda sayfasının numarası'], 'need' => ['no'], 'max' => 40]],
            ],
            '5. Arama motorları' => [
                'mihenk.seo.description' => ['Arama motoru açıklaması', '{firma}’ın çalışırken uyduğu {ilke_sayisi|yazı} ilke: uymuyorsa söyleriz, son güne kalmayız, dosyanız sizindir.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. İlkeleri değiştirdiyseniz buradaki örnekleri de güncelleyin.', ['vars' => ['firma', 'ilke_sayisi'], 'max' => 320]],
            ],
        ],
    ],
];
