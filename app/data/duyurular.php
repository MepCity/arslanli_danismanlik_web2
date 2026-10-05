<?php
/**
 * Duyurular: resmî kurumların açık çağrıları ve tarihleri (varsayılan içerik).
 * Başlık, tarih, kurum, özet ve bağlantılar resmî kaynaklarla karşılaştırılmıştır (Ekim 2026); olduğu gibi korunur.
 *
 * Bu dosya yalnızca veridir: sitenin okuduğu yer app/announcements.php'dir. Panelden ilk değişiklik yapılana kadar
 * (storage/duyurular.json yokken) duyurular buradan okunur; ilk kayıtta aşağıdaki liste dosyaya taşınır ve
 * "sitenin ilk hali" olarak değişiklik geçmişinde saklanır. Panelden değişen duyuru bu dosyaya yazılmaz.
 *
 * Bir duyuru:
 *  id, title, kurum, summary, link, updated,
 *  featured (bool): sitenin açılışında öne çıkan duyuru; süresi son başvuru gününde (yoksa son tarihte) dolar,
 *  events: [ { date: YYYY-MM-DD, type: baslangic|son|sonuc|diger, note } ]
 *  (published yazılmamışsa yayında sayılır; panelde kaydedilen duyurularda 'published', 'sample', 'featured_until', 'created' da bulunur.)
 *
 * Yeni duyuru: aşağıdaki listeye bir blok ekleyin; kapanan çağrılar listede kalır ve "Süresi doldu" olarak görünür.
 */

return [
    [
        'id'       => '5283b275ae',
        'title'    => 'TÜBİTAK 1832 Sanayide Yeşil Dönüşüm 2026-2 çağrısı 12 Ekim\'de kapanıyor',
        'kurum'    => 'TÜBİTAK',
        'summary'  => 'Yeşil dönüşüme yönelik Ar-Ge ve yenilik projeleri için KOBİ\'ler ve büyük işletmeler başvurabilir. Proje konusu iklim değişikliği, döngüsel ekonomi, temiz enerji, sürdürülebilir tarım ya da akıllı ulaşım alanlarından birine girmelidir. Destek oranı büyük işletmelerde %70, KOBİ\'lerde %80, deprem bölgesindeki KOBİ\'lerde %90\'dır; sermaye şirketlerine en fazla %50\'si geri ödenmek üzere faizsiz geri ödemeli destek sağlanır. Proje başvurusundan önce kuruluş bazlı ön kaydın 8 Ekim\'e kadar tamamlanması gerekir.',
        'link'     => 'https://tubitak.gov.tr/tr/duyuru/1832-sanayide-yesil-donusum-2026-2-cagrisi-basvuruya-acildi',
        'featured' => true,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-08-03', 'type' => 'baslangic', 'note' => 'Çağrı başvuruya açıldı'],
            ['date' => '2026-10-08', 'type' => 'diger', 'note' => 'Kuruluş bazlı ön kayıt için son gün'],
            ['date' => '2026-10-12', 'type' => 'son', 'note' => 'Saat 23.59\'a kadar, PRODİS üzerinden'],
        ],
    ],
    [
        'id'       => '277514c23f',
        'title'    => 'TÜBİTAK 1501 Sanayi Ar-Ge 2026-2 çağrısı: ön kayıt 22 Ekim, son başvuru 26 Ekim',
        'kurum'    => 'TÜBİTAK',
        'summary'  => 'KOBİ ölçeğindeki sermaye şirketlerinin Ar-Ge projeleri için PRODİS üzerinden başvuru alınır. Destek oranı kuruluşun desteklenen ilk 5 projesinde %75, 6. ve sonraki projelerinde %60\'tır; proje başına azami destek 20 milyon TL\'dir. Proje başvurusundan önce kuruluş bazlı ön kaydın tamamlanması gerekir.',
        'link'     => 'https://tubitak.gov.tr/tr/duyuru/1501-sanayi-ar-ge-destek-programi-ve-1507-kobi-ar-ge-baslangic-destek-programi-2026-yili-2-cagrilari-acildi',
        'featured' => false,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-07-20', 'type' => 'baslangic', 'note' => ''],
            ['date' => '2026-10-22', 'type' => 'diger', 'note' => 'Kuruluş bazlı ön kayıt için son gün, saat 23.59'],
            ['date' => '2026-10-26', 'type' => 'son', 'note' => 'Saat 23.59\'a kadar, PRODİS üzerinden'],
        ],
    ],
    [
        'id'       => '7b4f8adef4',
        'title'    => 'TÜBİTAK 1507 KOBİ Ar-Ge Başlangıç 2026-2 çağrısı: son başvuru 11 Kasım',
        'kurum'    => 'TÜBİTAK',
        'summary'  => 'KOBİ\'lerin ilk Ar-Ge projeleri için PRODİS üzerinden başvuru alınır. Kuruluş bazlı ön kayıt 9 Kasım\'a kadar tamamlanmalıdır. Destek oranı ve üst limitler için program mevzuatına bakılmalıdır.',
        'link'     => 'https://tubitak.gov.tr/tr/duyuru/1501-sanayi-ar-ge-destek-programi-ve-1507-kobi-ar-ge-baslangic-destek-programi-2026-yili-2-cagrilari-acildi',
        'featured' => false,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-07-20', 'type' => 'baslangic', 'note' => ''],
            ['date' => '2026-11-09', 'type' => 'diger', 'note' => 'Kuruluş bazlı ön kayıt için son gün, saat 23.59'],
            ['date' => '2026-11-11', 'type' => 'son', 'note' => 'Saat 23.59\'a kadar, PRODİS üzerinden'],
        ],
    ],
    [
        'id'       => '97e101cbd8',
        'title'    => 'TÜBİTAK 1707 Sipariş Ar-Ge 2026-3 çağrısı 13 Kasım\'a kadar açık',
        'kurum'    => 'TÜBİTAK',
        'summary'  => 'Bir müşteri kuruluş ile en az bir tedarikçi KOBİ ortak başvuru yapar; proje, müşteri kuruluşun ihtiyacını Ar-Ge yoluyla ticari bir ürüne dönüştürmelidir. Proje bütçesinin %40\'ını TÜBİTAK, en az %40\'ını müşteri kuruluş, en fazla %20\'sini tedarikçi kuruluş karşılar. Başvuruyu PRODİS üzerinden müşteri kuruluş yapar; müşteri ve tedarikçi kuruluşların ön kaydı 11 Kasım\'a kadar tamamlanmalıdır.',
        'link'     => 'https://tubitak.gov.tr/tr/duyuru/1707-siparis-ar-ge-2026-yili-3-cagrisi-acildi',
        'featured' => false,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-09-01', 'type' => 'baslangic', 'note' => ''],
            ['date' => '2026-11-11', 'type' => 'diger', 'note' => 'Kuruluş bazlı ön kayıt için son gün (müşteri ve tedarikçi kuruluşlar)'],
            ['date' => '2026-11-13', 'type' => 'son', 'note' => 'Saat 23.59\'a kadar, PRODİS üzerinden'],
        ],
    ],
    [
        'id'       => 'e11d1f3304',
        'title'    => 'KOSGEB İstihdamı Koruma Destek Programı başvuruları 31 Ekim\'e kadar',
        'kurum'    => 'KOSGEB',
        'summary'  => 'KOSGEB\'in desteklediği sektörlerde, ana ya da yan faaliyet NACE kodu imalat (Kısım C) olan KOBİ\'ler ve büyük işletmeler başvurabilir. Finansman desteği 10 puandan 12 puana çıkarıldı; kredi üst limiti KOBİ\'lerde 50 milyon TL, büyük işletmelerde 150 milyon TL\'dir. Azami 6 ay anapara ödemesiz, azami 36 ay vadeli kredilerde kullanılır.',
        'link'     => 'https://www.kosgeb.gov.tr/site/tr/genel/detay/9471/istihdami-koruma-destek-programinin-kapsami-genisletildi',
        'featured' => false,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-09-01', 'type' => 'baslangic', 'note' => ''],
            ['date' => '2026-10-31', 'type' => 'son', 'note' => ''],
        ],
    ],
    [
        'id'       => 'f32b63433f',
        'title'    => 'İSTKA 2026 Yaratıcı Ekonominin Güçlendirilmesi Mali Destek Programı: son başvuru 4 Aralık',
        'kurum'    => 'İSTKA',
        'summary'  => 'İstanbul Kalkınma Ajansı\'nın programında toplam bütçe 300 milyon TL\'dir; proje başına destek 5 ile 20 milyon TL arasında, azami destek oranı %75\'tir. Yalnızca İstanbul\'daki kâr amacı gütmeyen kurumlar (kamu kurumları, belediyeler, odalar, sivil toplum kuruluşları, üniversiteler, OSB\'ler ve benzerleri) başvurabilir; uygun başvuru sahipleri ve proje konuları program rehberinde yer alır. Taahhütname, başvurudan sonra 11 Aralık saat 17.00\'ye kadar teslim edilmelidir.',
        'link'     => 'https://www.istka.org.tr/duyuru/2026-yili-yaratici-ekonominin-guclendirilmesi-mali-destek-programi-1zsq',
        'featured' => false,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-09-08', 'type' => 'diger', 'note' => 'Program ilan edildi'],
            ['date' => '2026-09-22', 'type' => 'baslangic', 'note' => 'Başvurular KAYS üzerinden alınmaya başladı'],
            ['date' => '2026-12-04', 'type' => 'son', 'note' => 'Saat 23.59\'a kadar'],
            ['date' => '2026-12-11', 'type' => 'diger', 'note' => 'Taahhütname teslimi için son gün, saat 17.00'],
        ],
    ],
    [
        'id'       => '306b90ab24',
        'title'    => 'AB EIC Accelerator: tam başvurular için bir sonraki kesim tarihi 4 Kasım',
        'kurum'    => 'Avrupa Komisyonu',
        'summary'  => 'Avrupa Yenilik Konseyi\'nin EIC Accelerator programına AB üyesi ülkelerdeki ve Türkiye\'nin de aralarında olduğu Ufuk Avrupa\'ya ortak ülkelerdeki yenilikçi KOBİ\'ler ve girişimler başvurabilir. Tam başvurular belirlenen kesim tarihlerinde toplu olarak değerlendirmeye alınır; tam başvuru için önce kısa başvurunun olumlu (GO) sonuçlanması gerekir ve kısa başvurular her ayın ilk salı günü değerlendirmeye alınır. Hibe ve yatırım bileşenlerinin koşulları program sayfasında yer alır.',
        'link'     => 'https://eic.ec.europa.eu/eic-funding-opportunities/eic-accelerator_en',
        'featured' => false,
        'updated'  => '2026-10-03T18:02:41+03:00',
        'events'   => [
            ['date' => '2026-11-04', 'type' => 'diger', 'note' => 'Tam başvuru (2. adım) kesim tarihi, Brüksel saatiyle 17.00'],
        ],
    ],
];
