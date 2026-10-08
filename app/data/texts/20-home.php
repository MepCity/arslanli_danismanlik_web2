<?php
/** Ana sayfa (app/pages/home.php + assets/js/pages/home.js): mercek, takvim, dosyalar, kırmızı kalem, kaşeler, bülten ve son söz. Kanun metni, süreç adımları ve hizmet listesi Kurumsal içerik ve Hizmetler bölümlerinden gelir. */
return [
    'home' => [
        'label' => 'Ana sayfa',
        'url'   => '',
        'icon'  => 'squares-four',
        'sections' => [
            '1. Açılış: kanun metni ve mercek' => [
                'home.mercek.sayi' => ['Başlığın üstündeki satır: sayı', 'Sayı: [kalın]ARS-{yil}/001[/kalın]', 'rich', 'Kâğıdın üstündeki küçük satırın ilk parçası. {yil} yerine bu yıl gelir.', ['vars' => ['yil'], 'max' => 40]],
                'home.mercek.konu' => ['Başlığın üstündeki satır: konu', 'Konu: [kalın]Devlet destekleri hk.[/kalın]', 'rich', '', ['max' => 50]],
                'home.mercek.baslik' => ['Büyük başlık', "Destek var.\nDili zor.", 'lines', 'Her satır sayfada ayrı bir satır olarak durur.', ['max' => 40, 'lines' => 2]],
                'home.mercek.giris' => ['Başlığın altındaki açıklama', '{kurulus}’den beri hibe ve teşvik başvurularında dosyayı biz hazırlıyor, kabulden son ödemeye kadar biz yürütüyoruz. Arkadaki metin 5746 sayılı Kanun’dan.', 'text', '{kurulus} yerine kuruluş yılı gelir. Açıklamanın devamında fare ya da dokunmatik ipucu görünür (aşağıdaki iki metin).', ['vars' => ['kurulus'], 'need' => ['kurulus']]],
                'home.mercek.ipucu_fare' => ['Açıklamanın devamı: fareli bilgisayarda ipucu', 'Merceği üzerinde gezdirin.', 'line', 'Yalnızca fare kullanan ziyaretçilere görünür.', ['max' => 60]],
                'home.mercek.ipucu_dokunma' => ['Açıklamanın devamı: dokunmatik ekranda ipucu', 'Bir paragrafa dokunun.', 'line', 'Yalnızca telefon ve tablet kullanan ziyaretçilere görünür.', ['max' => 60]],
                'home.mercek.dugme_iletisim' => ['Koyu düğme: ön görüşme', 'Ön görüşme isteyin', 'line', 'İletişim sayfasına gider.', ['max' => 40]],
                'home.mercek.dugme_sade' => ['Açık düğme: tümünü sadeleştir', 'Tümünü sadeleştir', 'line', 'Tıklanınca mercek bütün sayfayı kaplar; düğmenin yazısı bir sonraki metne döner.', ['js' => true, 'max' => 30]],
                'home.mercek.dugme_kanun' => ['Açık düğme: sadeleştirilmişken görünen yazı', 'Kanun metnine dön', 'line', '“Tümünü sadeleştir” basıldıktan sonra düğmenin üzerinde görünür.', ['js' => true, 'max' => 30]],
                'home.mercek.okuyucu_kanun' => ['Kanun maddesi: ekran okuyucu satırı', '5746 sayılı Kanun, {madde}: {metin}', 'text', 'Görünmez; ekran okuyucular içindir. {madde} yerine madde numarası, {metin} yerine kanun metni gelir.', ['vars' => ['madde' => 'madde numarası', 'metin' => 'maddenin kanun metni'], 'need' => ['madde', 'metin']]],
                'home.mercek.okuyucu_sade' => ['Kanun maddesi: sade Türkçe ekran okuyucu satırı', 'Sade Türkçesi: {metin}', 'text', 'Görünmez; ekran okuyucular içindir. {metin} yerine maddenin sade Türkçesi gelir.', ['vars' => ['metin' => 'maddenin sade Türkçesi'], 'need' => ['metin']]],
            ],
            '2. Takvim: başvurunun yaprakları' => [
                'home.takvim.etiket' => ['Bölüm üstündeki küçük etiket', 'Bir başvurunun akışı', 'line', '', ['max' => 40]],
                'home.takvim.baslik' => ['Büyük başlık', "{surec_sayisi|Yazı} yaprak.\nÇoğu bizde.", 'lines', '{surec_sayisi|Yazı} yerine süreç adımı sayısı yazıyla gelir (Sekiz…); silmeyin.', ['vars' => ['surec_sayisi'], 'need' => ['surec_sayisi'], 'max' => 40, 'lines' => 2]],
                'home.takvim.giris' => ['Başlığın altındaki açıklama', 'Takvimden bir yaprak koptukça dosya bir adım ilerler. Her yaprakta önce bizim yaptığımız iş, altında sizden istediğimiz yazıyor. Alttaki satırın hep kısa olduğunu göreceksiniz.', 'text'],
                'home.takvim.yaprak_sira' => ['Yaprağın üstünde sıra yazısı', 'Yaprak {n} / {toplam}', 'line', '{n} yerine yaprağın sırası, {toplam} yerine toplam yaprak sayısı gelir.', ['vars' => ['n' => 'yaprağın sırası', 'toplam' => 'toplam yaprak sayısı'], 'need' => ['n', 'toplam'], 'max' => 30]],
                'home.takvim.biz' => ['Yaprakta bizim işimizin etiketi', 'Biz', 'line', '', ['max' => 16]],
                'home.takvim.siz' => ['Yaprakta sizden istenenin etiketi', 'Siz', 'line', '', ['max' => 16]],
                'home.takvim.yaprak_alt' => ['Yaprağın alt çizgisindeki adres', 'arslanlidanismanlik.com', 'line', 'Her yaprağın en altında küçük harflerle görünür.', ['max' => 40]],
            ],
            '3. Dosyalar listesi' => [
                'home.dosyalar.etiket' => ['Bölüm üstündeki küçük etiket', 'Dosyalar · {no}', 'line', '{no} yerine Hizmetler sayfasının evrak numarası gelir.', ['vars' => ['no' => 'Hizmetler sayfasının numarası'], 'need' => ['no'], 'max' => 40]],
                'home.dosyalar.baslik' => ['Büyük başlık', '{hizmet_sayisi|Yazı} alanda dosya hazırlıyoruz.', 'line', '{hizmet_sayisi|Yazı} yerine hizmet dosyası sayısı yazıyla gelir (Dokuz…); silmeyin.', ['vars' => ['hizmet_sayisi'], 'need' => ['hizmet_sayisi'], 'max' => 60]],
                'home.dosyalar.baglanti' => ['Başlığın yanındaki bağlantı', 'Dosya dolabını açın', 'line', 'Hizmetler sayfasına gider.', ['max' => 40]],
            ],
            '4. Kırmızı kalem: bizden duymayacağınız cümleler' => [
                'home.kalem.etiket' => ['Bölüm üstündeki küçük etiket', 'Düzeltme', 'line', '', ['max' => 30]],
                'home.kalem.baslik' => ['Büyük başlık', 'Bu cümleleri bizden duymazsınız.', 'line', '', ['max' => 60]],
                'home.kalem.iddia_1' => ['Birinci satır: üstü çizili cümle', '“Hibeniz garanti.”', 'line', 'Sayfada üstü çizili görünür.', ['max' => 70]],
                'home.kalem.duzeltme_1' => ['Birinci satır: düzeltme', 'Kararı kurum verir. Biz dosyanın eksiksiz ve zamanında olmasını üstleniriz.', 'text'],
                'home.kalem.iddia_2' => ['İkinci satır: üstü çizili cümle', '“Her işletmeye uygun bir destek mutlaka vardır.”', 'line', 'Sayfada üstü çizili görünür.', ['max' => 90]],
                'home.kalem.duzeltme_2' => ['İkinci satır: düzeltme', 'Yoksa ilk görüşmede söyleriz.', 'text'],
                'home.kalem.iddia_3' => ['Üçüncü satır: üstü çizili cümle', '“Belgenizi biz veririz.”', 'line', 'Sayfada üstü çizili görünür.', ['max' => 70]],
                'home.kalem.duzeltme_3' => ['Üçüncü satır: düzeltme', 'ISO belgesini akredite kuruluş, teşvik belgesini Bakanlık verir. Biz hazırlarız.', 'text'],
                'home.kalem.iddia_4' => ['Dördüncü satır: üstü çizili cümle', '“Son gün yetiştiririz.”', 'line', 'Sayfada üstü çizili görünür.', ['max' => 70]],
                'home.kalem.duzeltme_4' => ['Dördüncü satır: düzeltme', 'Dosya, kapanıştan en az bir hafta önce hazır olur.', 'text'],
                'home.kalem.iddia_5' => ['Beşinci satır: üstü çizili cümle', '“Bu desteği herkes alıyor, siz de alırsınız.”', 'line', 'Sayfada üstü çizili görünür.', ['max' => 90]],
                'home.kalem.duzeltme_5' => ['Beşinci satır: düzeltme', 'Her başvuru kendi şartlarıyla değerlendirilir. Sizinkini baştan okuruz.', 'text'],
            ],
            '5. Kaşeler' => [
                'home.kaseler.baslik' => ['Büyük başlık', 'Dosyasını hazırladığımız kurumlardan bazıları', 'line', 'Kaşelerin kendisi Referanslar bölümünden gelir.', ['max' => 80]],
                'home.kaseler.dugme' => ['Başlığın yanındaki düğme', 'Kaşe masasına geçin', 'line', 'Referanslar sayfasına gider.', ['max' => 40]],
            ],
            '6. Bülten sütunları' => [
                'home.bulten.sayi' => ['Gazete başlığında sayı numarası', 'Sayı {n}', 'line', 'Yalnızca Yazılar bölümü açıkken görünür. {n} yerine yazı sayısı gelir.', ['vars' => ['n' => 'yazı sayısı'], 'need' => ['n'], 'max' => 20]],
                'home.bulten.ad' => ['Gazete adı', 'Arslanlı Bülteni', 'line', 'Yalnızca Yazılar bölümü açıkken görünür.', ['max' => 40]],
                'home.bulten.kunye' => ['Yazı sütununun üstündeki tarih satırı', '{tarih_uzun} · {dk} dk', 'line', '{tarih_uzun} yerine yazının tarihi, {dk} yerine okuma süresi (dakika) gelir.', ['vars' => ['tarih_uzun' => 'yazının tarihi', 'dk' => 'okuma süresi (dakika)'], 'need' => ['tarih_uzun', 'dk'], 'max' => 40]],
                'home.bulten.baglanti' => ['Sütunların altındaki bağlantı', 'Tüm makaleler', 'line', 'Makaleler sayfasına gider.', ['max' => 40]],
            ],
            '7. Son söz' => [
                'home.masa.baslik' => ['Büyük başlık', 'Masanızda bir çağrı metni mi var?', 'line', '', ['max' => 70]],
                'home.masa.giris' => ['Başlığın yanındaki açıklama', 'Gönderin. Okuyup işletmenize uyup uymadığını, uyuyorsa ne kadar sürede ve hangi belgelerle hazırlanacağını söyleyelim.', 'text'],
                'home.masa.dugme' => ['Koyu düğme: dilekçe', 'Dilekçe yazın', 'line', 'İletişim sayfasına gider. Yanındaki telefon düğmesinin yazısı İletişim ayarlarından gelir.', ['max' => 40]],
            ],
            '8. Arama motorları' => [
                'home.seo.description' => ['Arama motoru açıklaması', 'Hibe ve teşvik mevzuatını sade Türkçeye çeviriyoruz: TÜBİTAK, KOSGEB, bakanlık, ihracat ve AB desteklerinde başvurudan ödemeye kadar. {kurulus}’den beri İstanbul’da.', 'text', 'Google sonuçlarında görünür; sayfada görünmez. 150-160 karakter önerilir.', ['vars' => ['kurulus'], 'max' => 320]],
            ],
        ],
    ],
];
