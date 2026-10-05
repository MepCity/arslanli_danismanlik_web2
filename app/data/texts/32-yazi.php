<?php
/** Makale sayfası (app/pages/yazi.php + assets/js/pages/post.js): tek bir yazının şablonu. Yazının başlığı, özeti, gövdesi, kategorisi ve tarihi Yazılar bölümünden gelir. Yalnızca Yazılar bölümü açıkken görünür. */
return [
    'yazi' => [
        'label' => 'Makale sayfası (şablon)',
        'url'   => 'blog',
        'icon'  => 'newspaper',
        'sections' => [
            '1. Yazının başı' => [
                'yazi.folio.ad' => ['Üst çubuktaki sayfa etiketinin adı', 'Makale', 'line', 'Üst çubukta “Evrak 05 · Makale” biçiminde görünür.', ['max' => 30]],
                'yazi.bas.geri' => ['Başlığın üstündeki bağlantı', 'Arslanlı Bülteni', 'line', 'Makaleler sayfasına gider. Yanında yazının kategorisi görünür.', ['max' => 40]],
                'yazi.bas.tarih' => ['Başlığın üstündeki satır: tarih', 'Tarih: [kalın]{yazi_tarihi}[/kalın]', 'rich', '{yazi_tarihi} yerine yazının tarihi gelir.', ['vars' => ['yazi_tarihi' => 'yazının tarihi'], 'need' => ['yazi_tarihi'], 'max' => 40]],
                'yazi.bas.okuma' => ['Başlığın üstündeki satır: okuma süresi', 'Okuma: [kalın]{dk} dakika[/kalın]', 'rich', '{dk} yerine okuma süresi gelir.', ['vars' => ['dk' => 'okuma süresi (dakika)'], 'need' => ['dk'], 'max' => 40]],
                'yazi.bas.bolum' => ['Başlığın üstündeki satır: bölüm', 'Bölüm: [kalın]{bolum}[/kalın]', 'rich', '{bolum} yerine yazının kategorisi gelir.', ['vars' => ['bolum' => 'yazının kategorisi'], 'need' => ['bolum'], 'max' => 50]],
                'yazi.bas.altyazi' => ['Fotoğrafın altyazısı', 'Temsilî fotoğraf.', 'line', '', ['max' => 40]],
            ],
            '2. Yazının sonu' => [
                'yazi.son.paylas' => ['Paylaşım bağlantılarının üstündeki yazı', 'Bu yazıyı paylaşın', 'line', '', ['max' => 40]],
                'yazi.son.linkedin' => ['Paylaşım bağlantısı: LinkedIn', 'LinkedIn', 'line', '', ['max' => 24]],
                'yazi.son.x' => ['Paylaşım bağlantısı: X', 'X', 'line', '', ['max' => 24]],
                'yazi.son.whatsapp' => ['Paylaşım bağlantısı: WhatsApp', 'WhatsApp', 'line', '', ['max' => 24]],
                'yazi.son.kopyala' => ['Paylaşım düğmesi: bağlantıyı kopyala', 'Bağlantıyı kopyala', 'line', '', ['max' => 30]],
                'yazi.son.kopyalandi' => ['Bağlantı kopyalanınca görünen bildirim', 'Bağlantı kopyalandı', 'line', '', ['max' => 50]],
            ],
            '3. Aynı sayıdan' => [
                'yazi.diger.baslik' => ['Diğer yazıların başlığı', 'Aynı sayıdan', 'line', '', ['max' => 40]],
                'yazi.diger.kunye' => ['Diğer yazıların üstündeki satır', '{tarih_uzun} · {dk} dk', 'line', '{tarih_uzun} yerine yazının tarihi, {dk} yerine okuma süresi gelir.', ['vars' => ['tarih_uzun' => 'yazının tarihi', 'dk' => 'okuma süresi (dakika)'], 'need' => ['tarih_uzun', 'dk'], 'max' => 40]],
            ],
            '4. Sayfanın sonundaki bağlantı' => [
                'yazi.sonraki.etiket' => ['Sonraki makale bağlantısının küçük yazısı', 'Sonraki makale', 'line', 'Bağlantının büyük yazısı sonraki makalenin başlığıdır.', ['max' => 40]],
            ],
        ],
    ],
];
