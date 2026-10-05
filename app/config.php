<?php
/**
 * Site ayarları. Sunucuya yüklerken yalnızca bu dosyayı düzenlemeniz yeterli.
 */
return [
    'name'        => 'Arslanlı Yatırım & Danışmanlık',
    'short_name'  => 'Arslanlı',
    'founded'     => 2007,

    // Canlı alan adı (sitemap, canonical ve Open Graph etiketleri için).
    'url'         => 'https://arslanlidanismanlik.com',

    'phone'       => '+90 554 808 97 71',
    'phone_href'  => '+905548089771',
    'whatsapp'    => '905548089771',
    'email'       => 'info@arslanlidanismanlik.com',
    'address'     => 'Starport Residence, Yenişehir Mah. Millet Cad. Sümbül Sk. No:10 D:28, 34912 Pendik / İstanbul',
    'address_short' => 'Pendik, İstanbul',
    'maps_url'    => 'https://www.google.com/maps/search/?api=1&query=Starport+Residence+Yeni%C5%9Fehir+Mahallesi+Millet+Caddesi+S%C3%BCmb%C3%BCl+Sokak+No%3A10+34912+Pendik+%C4%B0stanbul',

    'company' => [
        'authorized' => 'Hatice Arslan',
        'tax_office' => 'Pendik',
        'tax_number' => '303 063 8183',
    ],

    'social' => [
        'Instagram' => 'https://www.instagram.com/arslanlidanismanlik/',
        'LinkedIn'  => 'https://www.linkedin.com/company/arslanli-danismanlik/',
        'Facebook'  => 'https://www.facebook.com/arslanlidanismanlik',
        'X'         => 'https://twitter.com/ArslanlDan',
    ],

    // Form gönderimleri
    'mail' => [
        'to'        => 'info@arslanlidanismanlik.com',
        'from'      => 'noreply@arslanlidanismanlik.com',
        'from_name' => 'Arslanlı Web Sitesi',
        // SMTP kullanmak isterseniz doldurun. Boş bırakılırsa PHP mail() kullanılır.
        // Şifreyi bu dosyaya (ve git'e) yazmayın: aynı biçimde bir dizi döndüren storage/config.local.php dosyası
        // sunucuda oluşturulursa içindeki değerler bu ayarların üzerine yazılır, ör.
        //   <?php return ['mail' => ['smtp' => ['host' => '...', 'port' => 465, 'secure' => 'ssl', 'user' => '...', 'pass' => '...']]];
        // 'smtp' => ['host' => 'mail.arslanlidanismanlik.com', 'port' => 465, 'secure' => 'ssl', 'user' => 'noreply@arslanlidanismanlik.com', 'pass' => '••••'],
        'smtp'      => null,
    ],

    // Gönderimler ayrıca storage/ klasörüne kaydedilsin mi? (e-posta iletilemezse kayıp olmaz)
    'store_submissions' => true,

    // Yönetim paneli (/yonetim). İlk şifrenin karşılığıdır; panelden "Güvenlik ve yedek" sayfasıyla değiştirin.
    // Değiştirilen şifre storage/admin.json dosyasına yazılır ve bunun yerine geçer.
    'admin' => [
        'password_hash' => '$2y$12$MM9u4i5p170LROlZqFH2aO3tRyjX87Wl/cpxSeSKaKM1um2UJ42E.',
    ],

    // Form güvenlik anahtarı (yedek değer). Gerçek anahtar storage/secret.key dosyasında tutulur ve ilk istekte kendiliğinden
    // oluşturulur; dosya varken bu değer kullanılmaz. Bu değeri değiştirmenize gerek yok.
    'secret' => 'degistir-bunu-uzun-rastgele-bir-anahtar-ile-7f3a9c',
];
