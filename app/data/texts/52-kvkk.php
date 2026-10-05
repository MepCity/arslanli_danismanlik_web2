<?php
/**
 * KVKK Aydınlatma Metni sayfası (app/pages/kvkk.php): hukuki metin. Her madde ayrı bölümdür; maddenin başlığı, her fıkrası ve sade Türkçesi ayrı ayrı düzenlenir.
 * Sayfanın çerçevesindeki sabit yazılar (Kısaca, Maddeler listesi, “Sade Türkçesi”) Yasal metinler grubundadır. Madde numaraları, içindekiler ve
 * dışarıdan gelen bağlantılar (iş başvurusu maddesine, #basvuru-adaylari) sayfada otomatik üretilir; maddenin sırasını değiştirmek kodla yapılır.
 * Hukuki metindir: değiştirmeden önce hukuk danışmanınıza sorun.
 */
return [
    'kvkk' => [
        'label' => 'KVKK Aydınlatma Metni',
        'url'   => 'kurumsal/kvkk-aydinlatma-metni',
        'icon'  => 'file-text',
        'sections' => [
            '1. Sayfanın başı' => [
                'kvkk.baslik.sayi' => ['Belge numarası', 'ARS-{yil}/013', 'line', 'Sayfanın üstündeki “Sayı:” satırında görünür; {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 30]],
                'kvkk.baslik.konu' => ['Konu satırı', 'Kişisel verilerin işlenmesi', 'line', 'Sayfanın üstündeki “Konu:” satırında görünür.', ['max' => 60]],
                'kvkk.baslik.guncelleme' => ['Son güncelleme tarihi', '6 Ekim 2026', 'line', 'Sayfanın üstünde ve metnin sonunda görünür. Metni değiştirdiğinizde bu tarihi de güncelleyin; gün ay yıl biçiminde yazın.', ['max' => 30]],
                'kvkk.baslik.baslik' => ['Büyük başlık', 'KVKK aydınlatma metni', 'line', 'Sayfanın adı (menü ve alt bilgi) Sayfa adları listesinden, bu başlık buradan değişir.', ['max' => 60]],
                'kvkk.baslik.giris' => ['Başlığın altındaki açıklama', 'Formlara yazdığınız bilgilerin bizde ne olduğunu anlatan metin. Kanun gereği hukuk diliyle yazıldı; her maddenin altında sade Türkçesi var.', 'text'],
            ],
            '2. Kısaca kutusu (sağ üstte sade özet)' => [
                'kvkk.kisaca.m1' => ['Birinci cümle: formlardaki bilgilerin ne için kullanıldığı', 'Formlara yazdıklarınızı yalnızca [vurgu]size dönmek[/vurgu], izin verdiyseniz açılan çağrıları haber vermek ve formları kötüye kullanımdan korumak için kullanırız.', 'rich', 'Kısaca kutusunda madde işaretli listede görünür. Sayfada vurgulu görünen kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'kvkk.kisaca.m2' => ['İkinci cümle: verilerin satılmaması', 'Verilerinizi [vurgu]satmayız[/vurgu]; reklam için kimseyle paylaşmayız. Siteyi gezerken kim olduğunuzu kaydetmeyiz.', 'rich', 'Kısaca kutusunda madde işaretli listede görünür. Sayfada vurgulu görünen kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'kvkk.kisaca.m3' => ['Üçüncü cümle: bülten onayının geri alınması', 'Bülten onayınızı istediğiniz an geri alabilirsiniz: her e-postadaki [vurgu]“Abonelikten ayrıl”[/vurgu] bağlantısı ya da bir e-posta yeter.', 'rich', 'Kısaca kutusunda madde işaretli listede görünür. Sayfada vurgulu görünen kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'kvkk.kisaca.m4' => ['Dördüncü cümle: iş başvurusunda özgeçmişin kullanımı', 'İş başvurusunda özgeçmişiniz [vurgu]yalnızca işe alım[/vurgu] için kullanılır; ileride saklanması sizin izninize bağlıdır.', 'rich', 'Kısaca kutusunda madde işaretli listede görünür. Sayfada vurgulu görünen kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
                'kvkk.kisaca.m5' => ['Beşinci cümle: kişinin hakları', 'Hakkınızda ne bildiğimizi sorabilir, düzeltilmesini ya da silinmesini isteyebilirsiniz. Yönetimde kullanılan yapay zekâ asistanları, izin verilirse form kayıtlarınızı okuyabilir.', 'rich', 'Kısaca kutusunda madde işaretli listede görünür. Sayfada vurgulu görünen kısım [vurgu]…[/vurgu] işaretleriyle yazılır.', ['max' => 300]],
            ],
            '3. Sayfanın sonundaki bağlantı' => [
                'kvkk.sonraki.ad' => ['Sonraki sayfa bağlantısının büyük yazısı', 'Çerez politikası', 'line', 'Çerez Politikası sayfasına götürür; küçük yazı (Sonraki evrak) Yasal metinler grubundadır.', ['max' => 50]],
            ],
            '4. Arama motorları' => [
                'kvkk.seo.description' => ['Arama motoru açıklaması', '{firma} web sitesindeki formlar, ziyaret sayacı, bülten e-postaları ve yönetimde yapay zekâ kullanımıyla işlenen kişisel verilere ilişkin aydınlatma metni.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['firma'], 'max' => 320]],
            ],
        ],
    ],
];
