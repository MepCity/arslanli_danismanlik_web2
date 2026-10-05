<?php
/**
 * Sayfa metinlerinde kullanılabilen GENEL yer tutucular: {ad} ya da {ad|yazı} (sayıyı yazıyla).
 * ad => [açıklama (panelde sahibine gösterilir), değer işlevi, html mi (true ise değer olduğu gibi basılır)]
 *
 * Yalnızca birden çok sayfada kullanılan değerler buraya girer. Tek şablona özgü değerler (kalan gün sayısı, ilan adı...)
 * şablondan t('anahtar', ['n' => 3]) ile verilir ve kayıt defterinde 'vars' => ['n' => 'açıklama'] ile tanıtılır.
 * Bu dosyaya yeni satır ekleyen kişi satırı listenin SONUNA ekler.
 */
return [
    'firma'          => ['firmanın adı', fn() => cfg('name'), false],
    'firma_kisa'     => ['firmanın kısa adı', fn() => cfg('short_name'), false],
    'kurulus'        => ['kuruluş yılı', fn() => cfg('founded'), false],
    'yil'            => ['içinde bulunulan yıl', fn() => date('Y'), false],
    'yil_sayisi'     => ['kuruluştan bu yana geçen yıl sayısı', fn() => years_active(), false],
    'tarih'          => ['bugünün tarihi (gg/aa/yyyy)', fn() => today_official(), false],
    'tarih_nokta'    => ['bugünün tarihi (gg.aa.yyyy)', fn() => date('d.m.Y'), false],
    'telefon'        => ['telefon numarası', fn() => cfg('phone'), false],
    'eposta'         => ['e-posta adresi', fn() => cfg('email'), false],
    'adres'          => ['açık adres', fn() => cfg('address'), false],
    'adres_kisa'     => ['kısa adres (ilçe, il)', fn() => cfg('address_short'), false],
    'vergi_dairesi'  => ['vergi dairesi', fn() => cfg('company.tax_office'), false],
    'vergi_no'       => ['vergi numarası', fn() => cfg('company.tax_number'), false],
    'hizmet_sayisi'  => ['Hizmetler bölümündeki dosya sayısı', fn() => count(services()), false],
    'surec_sayisi'   => ['çalışma sürecindeki adım sayısı', fn() => count((array) site('process')), false],
    'ilke_sayisi'    => ['mihenk taşlarındaki ilke sayısı', fn() => count((array) site('principles')), false],
    'misyon_sayisi'  => ['misyon maddelerinin sayısı', fn() => count((array) (site('mission')['items'] ?? [])), false],
    'hesap_sayisi'   => ['banka hesabı sayısı', fn() => count((array) site('banks')), false],
    'eposta_baglanti' => ['e-posta adresi, tıklanabilir bağlantı olarak', fn() => '<a class="link" href="mailto:' . e(cfg('email')) . '">' . e(cfg('email')) . '</a>', true],
];
