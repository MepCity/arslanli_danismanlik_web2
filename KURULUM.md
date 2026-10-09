# Arslanlı Yatırım & Danışmanlık · web sitesi (v3 "Evrak")

Veritabanı gerektirmeyen, saf PHP ile çalışan site. Derleme adımı yok; klasörü sunucuya yüklemeniz yeterli. Sitenin her yazısı, listesi, duyurusu, ilanı ve ayarı **yönetim panelinden** (`/yonetim`) değiştirilir; panelde yapılan işlerin neredeyse tamamı, kişiye özel bir erişim anahtarıyla bağlanan yapay zekâ asistanlarıyla da yapılabilir (MCP). Bu belge siteyi kuracak ve yönetecek kişiler içindir.

## Tasarım fikri

Site, hibe ve teşvik işinin kendi dünyasından kurulur: kanun metni, üst yazı, dilekçe, telli dosya, kaşe, takvim yaprağı, dekont.
Her sayfa bu dünyadan farklı bir nesneyle çalışır:

| Sayfa | Adres | Deneyim |
|---|---|---|
| Ana sayfa | `/` | 5746 sayılı Kanun’un gerçek metni üzerinde gezen büyüteç; altında sade Türkçesi. Koparılan takvim yapraklarıyla başvuru akışı, kitap dizini gibi dosya listesi, kırmızı kalemle düzeltilen cümleler, kaşe gibi basılan referanslar, yaklaşan çağrı tarihleri (çağrı takvimi) |
| Hakkımızda | `/hakkimizda` | Arşiv rafı: 2001–2026 arası teşvik mevzuatındaki değişiklikler klasör sırtlarında |
| Hizmetler | `/hizmetler` | Dosya dolabı: telli dosyalar ve “ne yapmak istiyorsunuz?” eşleştiricisi |
| Hizmet detayı | `/urunler/detay/...` | Açık dosya: “Ek” sekmeleri, madde madde işler, işaretlenip yazdırılabilen evrak listesi |
| Referanslar | `/referans` | Kaşe masası: tıkladığınız yere müşteri logosu basılır |
| Makaleler (kurulumda kapalı) | `/blog` | Gazete ön sayfası |
| Makale (kurulumda kapalı) | `/blog/...` | Sakin okuma sayfası, kenarda ilerleyen kurşun kalem |
| Duyurular | `/duyurular` | İlan panosu: panoya iğnelenmiş ilan kâğıtları, köşede “kaç gün kaldı” damgası, çağrı takvimi, resmî kaynak bağlantısı ve takvime ekleme (`/duyurular.ics`) |
| Açılış duyurusu | tüm sayfalar (öne çıkan duyuru varsa) | Masaya düşen acele evrak: geri sayımlı pencere; kapatılınca sol altta küçük bir etiket kalır |
| İletişim | `/iletisim` | Boşlukları doldurulan dilekçe; gönderilince “ALINDI” kaşesi |
| Haberdar Ol | `/haberdarol` | Dergi kuponu (bülten kaydı). Her sayfanın sağ kenarındaki “Haberdar ol” sekmesi de aynı formu açar |
| Kariyer | `/kariyer` | Özlük dosyası: açık pozisyonlar (“kadro talep fişi”) ve genel başvuru formu; gönderilince “DOSYAYA EKLENDİ” kaşesi |
| İş ilanı | `/kariyer/{ilan-adresi}` | Tek bir pozisyonun fişi ve kendi başvuru formu |
| Hesap Numaralarımız | `/hesap-numaralarimiz` | POS fişi gibi basılan dekontlar |
| Misyonumuz | `/kurumsal/misyonumuz` | Çizgili defterde iş listesi |
| Vizyonumuz | `/kurumsal/vizyonumuz` | Açılış yılında açılacak mühürlü mektup |
| Mihenk Taşlarımız | `/kurumsal/mihenk-taslarimiz` | Siyah taşa sürtülerek ortaya çıkan ilkeler |
| KVKK / Çerez | `/kurumsal/kvkk-aydinlatma-metni`, `/kurumsal/cerez-politikasi` | Madde madde sözleşme düzeni, her maddenin altında “Sade Türkçesi” |
| Bültenden ayrılma | `/bulten/ayril` | Bülten e-postasındaki bağlantının açtığı “sicil kartı”; menüde ve site haritasında yoktur, arama motorlarına kapalıdır |
| 404 | — | “Eksik evrak” yazısı |

Sayfalar arası geçişte yeni sayfa, masaya konan yeni bir kâğıt gibi aşağıdan gelir (tarayıcının View Transitions özelliği; desteklemeyen tarayıcıda normal geçiş olur).

**Üst gezinme.** Üstte logo, ortada sayfa dizini (açık sayfaların numarası ve adı), sağda “Ön görüşme” düğmesi (İletişim’e gider) ve **Fihrist** düğmesi bulunur. Dizinde bir sayfanın üzerine gelince, dizinin üstündeki küçük “Evrak 05 · Duyurular” etiketi o sayfayı gösterir; ayrılınca bulunulan sayfa geri gelir. Aşağı kaydırınca üst çubuk gizlenir, klavyeyle odaklanınca geri gelir. Fihrist, tüm siteyi gösteren açılır dizindir: sayfalar, hizmet dosyaları ve kurumsal sayfalar (Haberdar Ol, Misyon, Vizyon, Mihenk Taşları, Hesap Numaraları), altta telefon, e-posta ve kısa adres. Alt bilgide Duyurular, Kariyer, KVKK, Çerez, Hesap Numaraları ve Bülten bağlantıları bulunur; kapalı bölümün bağlantısı görünmez.

**Çağrı takvimi.** Duyurulardaki tüm tarihler (başvuru başlangıcı, son başvuru günü, sonuç açıklaması, bilgilendirme) tek bir sürekli form çizelgesinde tarih sırasıyla basılır: ana sayfada yaklaşan ilk altı tarih ve tümüne bağlantı, Duyurular sayfasında yaklaşan bütün tarihler. Geçmiş tarihler çizelgeye girmez (süresi dolan çağrı panoda “Süresi doldu” damgasıyla durur). Duyurular bölümü kapatılırsa çizelge, takvim dosyaları (`/duyurular.ics`) ve açılış penceresi birlikte kalkar.

**Sayfa numaraları (“Evrak NN”).** Her sayfanın numarası, açık sayfalar arasındaki sırasından kendiliğinden üretilir: Ana sayfa 01, Hakkımızda 02, Hizmetler 03, Referanslar 04, Makaleler, Duyurular, Kariyer, İletişim; ardından Haberdar Ol, Misyon, Vizyon, Mihenk Taşları, Hesap Numaraları, Çerez, KVKK. Bir bölümü Görünürlük’ten kapattığınızda numaralar boşluk bırakmadan kapanır (Makaleler kapalıyken Duyurular 05, KVKK 14’tür). Numaralar fihristte, üst dizinde, sayfa başlarında ve “Sonraki evrak” kartlarında aynı yerden gelir; elle yazılmaz. Hizmet dosyalarının numarası (“03.1”, “03.2”…) Hizmetler sayfasının numarası ile dosyanın sırasından oluşur. Sayfaların adları **Sayfa metinleri > Menü, Fihrist ve alt bilgi** bölümünde değiştirilir; ad her yerde birlikte değişir.

## Gereksinimler

- PHP 8.0 veya üzeri (8.1+ önerilir); `mbstring` ve **GD** eklentileri (referans kaşeleri ve paylaşım görselleri GD ile üretilir), yedek için **ZipArchive** (`php-zip`)
- Apache (`mod_rewrite` açık) ya da Nginx
- Form e-postaları ve bülten için `mail()` ya da bir SMTP hesabı
- HTTPS (aşağıya bakın). Yapay zekâ erişimi HTTPS olmadan çalışmaz.

## Kurulum

1. **Yüklenecek paket:** bu klasörün şu içeriği sitenin kök dizinine (`public_html` vb.) yüklenir: `app/`, `assets/`, `index.php`, `.htaccess`, `.user.ini`, `storage/.htaccess`, `uploads/.htaccess`. Gizli (nokta ile başlayan) dosyaları da yükleyin. `.git` klasörünü ve bu `KURULUM.md` dosyasını yüklemeyin; yüklense bile `.htaccess` bunların dışarıdan okunmasını engeller. `storage/` ve `uploads/` klasörleri depoda yalnızca kendi `.htaccess` dosyalarıyla gelir; içleri sunucuda oluşur.
2. Yüklemeden sonra tarayıcıda `/.git/HEAD`, `/app/config.php` ve `/storage/secret.key` adreslerinin “erişim yok” (403) döndüğünü kontrol edin; dönmüyorsa sunucunuz `.htaccess` dosyasını dikkate almıyordur, barındırma firmanıza danışın (Nginx kullanıyorsanız aşağıdaki bloğu ekleyin).
3. `app/config.php` dosyasında yalnızca `url` değerinin canlı alan adınız olduğunu kontrol edin (`https://arslanlidanismanlik.com`). Telefon, adres, vergi bilgileri, sosyal ağlar ve e-posta ayarlarını panelden **İletişim ve şirket** sayfasında yapabilirsiniz.
4. **İzinler:** `storage/` ve `uploads/` klasörleri PHP tarafından **yazılabilir** olmalı (genelde 755 yeterli; gerekirse 775). Form güvenlik anahtarı siteye ilk girişte kendiliğinden oluşur (`storage/secret.key`); oluşamazsa (klasör yazılamıyorsa) bülten gönderimi başlamaz.
5. `/yonetim` adresinden giriş yapın; **Güvenlik ve yedek** sayfasında şifrenizi değiştirin. Genel bakış’taki “Site sağlığı” listesi eksik kalan adımları gösterir.
6. **HTTPS:** `.htaccess` siteyi kendiliğinden HTTPS’e ve `www`’siz adrese yönlendirir (yerel adreslerde uygulanmaz). Sunucuda SSL kurulu **değilse** dosyadaki yönlendirme satırlarının başına `#` koyun; yoksa site açılmaz. Üretimde SSL’i açık tutun.
7. **Yükleme sınırı:** özgeçmiş dosyaları en çok 5 MB’tır; çoğu sunucuda PHP’nin sınırı 2 MB olduğundan `.user.ini` (PHP-FPM) ve `.htaccess` (mod_php) sınırı 8 MB’a çıkarır. Barındırma firmanız bu dosyaları dikkate almıyorsa panelinden `upload_max_filesize` en az 6M, `post_max_size` en az 8M olacak şekilde ayarlayın.

Site bir alt klasörde de çalışır (ör. `alanadi.com/yeni/`); adresler otomatik ayarlanır (yapay zekâ erişimi için alan adının kökünde olması gerekir).

### Gizli ayarlar: `storage/config.local.php`

Ayarların önceliği: `app/config.php` < `storage/config.local.php` < panelden kaydedilenler. `config.local.php`, sunucuda elle oluşturduğunuz, depoya girmeyen, `app/config.php` ile aynı biçimde bir dizi döndüren PHP dosyasıdır; yalnızca değiştirmek istediklerinizi yazarsınız:

```php
<?php
return [
  'mail' => ['smtp' => ['host' => 'mail.alanadiniz.com', 'port' => 465, 'secure' => 'ssl', 'user' => 'noreply@alanadiniz.com', 'pass' => 'şifre']],
  'admin' => ['password_hash' => '$2y$...'],   // isteğe bağlı: ilk panel şifresinin özeti
];
```

**SMTP üç durumdan birindedir** (panelde **İletişim ve şirket** > E-posta, yapay zekâ erişiminde `smtp_kaynak`): *yapılandırma dosyasından* (panelde SMTP için seçim kaydedilmemiştir; `config.local.php` ne diyorsa o geçerlidir ve oradaki şifre panele, `settings.json`’a, geçmişe ya da yedeğe hiçbir zaman kopyalanmaz), *panelde ayarlı* ve *panelde kapalı* (yerel dosyadaki SMTP de yok sayılır). SMTP alanlarına dokunmadan kaydetmek durumu değiştirmez. Panelden yazdığınız şifre `storage/content/settings.json` içinde düz metin durur (panelin işi budur) ama yedek dosyasına ve değişiklik geçmişi sürümlerine girmez; eski sürümlerde kalmış şifreler günlük bakımla silinir. Daha güvenlisi şifreyi `config.local.php`’ye yazmaktır.

### E-posta: `mail()` ya da SMTP

- Varsayılan olarak sunucunun `mail()` işlevi kullanılır. Barındırma firması bunu kapatmışsa (ya da e-postalar spam’e düşüyorsa) SMTP kullanın: panelde **İletişim ve şirket > E-posta** kartında “SMTP ile gönder”i açıp bilgileri girin ya da yukarıdaki `config.local.php` örneğini kullanın. SMTP sunucusu olarak sertifikasındaki adla eşleşen adı yazın (ör. `mail.alanadiniz.com`); `localhost` yazılırsa güvenli bağlantı doğrulanamaz. Bilmiyorsanız SSL ve 465 genellikle çalışır.
- “Gönderen adres” sitenin kendi alan adından olmalıdır; alan adınızda SPF ve DKIM kayıtlarının tanımlı olduğunu barındırma firmanıza doğrulatın.
- **Deneme e-postası gönder** düğmesi, bildirim adresine kısa bir ileti yollar; ayarları kaydettikten sonra ilk iş bunu deneyin.
- Formlardan gelen bildirimler “Bildirimlerin gideceği adres”e gider. E-posta iletilemezse `storage/mail-failures.log` dosyasına not düşülür ve Genel bakış’ta uyarı çıkar.
- Sunucuda `mail()` kapalı ve SMTP ayarsızsa e-posta gönderilmez; bülten gönderim düğmeleri de kapalı kalır.

### Nginx kullanıyorsanız

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ ^/(app|storage)/ { deny all; }
# Nokta ile başlayan dosya ve klasörler (.git vb.) sunulmaz; /.well-known/ hariç
location ~ /\.(?!well-known) { deny all; }
location ~ ^/uploads/.*\.(php|phtml|phar|cgi|pl|py)$ { deny all; }
location = /KURULUM.md { deny all; }
location ~ (\.user\.ini|\.(log|jsonl|sh|lock))$ { deny all; }
gzip on;
gzip_types text/css application/javascript text/markdown text/plain application/json application/feed+json application/rss+xml application/xml text/calendar image/svg+xml;
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

PHP sınırları için `upload_max_filesize 8M` ve `post_max_size 10M` değerlerini PHP ayarına yazın (Nginx’te `.htaccess` çalışmaz).

## Yerelde çalıştırma

```bash
PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8090 index.php
```

(`PHP_CLI_SERVER_WORKERS`, panelin kendi kendine yaptığı yoklamaların tıkanmaması içindir.) Yerelde IndexNow bildirimi gönderilmez.

## İçerik nerede?

Sitedeki her şey panelden değiştirilir; dosya düzenlemeniz gerekmez. Teknik olarak: kurulumla gelen içerik `app/data/*.php` ve `app/config.php` dosyalarındadır (depoya girer). Panelden yaptığınız her değişiklik `storage/content/` klasörüne JSON olarak yazılır ve kurulum içeriğinin yerine geçer; böylece kod güncellemeleri panelde yaptığınız değişiklikleri silmez.

| Ne | Kurulum içeriği | Panelde |
|---|---|---|
| İletişim, şirket, sosyal ağlar, e-posta ayarları | `app/config.php` | İletişim ve şirket |
| Hizmet dosyaları | `app/data/services.php` | Hizmetler |
| Makaleler | `app/data/posts.php` | Yazılar |
| Referans logoları | `app/data/site.php`, `assets/img/refs/` | Referanslar |
| Süreç, ilkeler, zaman çizelgesi, misyon, vizyon, banka hesapları, form seçenekleri, hedef eşleştirici | `app/data/site.php` | Kurumsal içerik, Hizmetler > Hedef eşleştirici |
| Ana sayfadaki kanun metinleri (`hero_law`) | `app/data/site.php` | Yalnızca okunur (aşağıya bakın) |
| Sayfalardaki tüm yazılar | `app/data/texts/*.php` (kayıt defteri) | Sayfa metinleri |
| KVKK ve çerez maddeleri | `app/data/legal.php` | Yasal metinler |
| Duyurular | `app/data/duyurular.php` (dosya yoksa) | Duyurular (`storage/duyurular.json`) |
| İş ilanları | — | İş ilanları (`storage/ilanlar.json`) |
| Görünürlük varsayılanları | `app/content.php` (`features_defaults`) | Görünürlük |
| Panelden yüklenen görseller | — | `uploads/` |
| Sayfa şablonları, ortak üst/alt bilgi, kaşe | `app/pages/*.php`, `app/partials/*.php` | (tasarımdır, panelden değişmez) |

**Mevzuat alıntıları:** ana sayfadaki kanun metinleri, hizmetlerdeki “mevzuat alıntısı” ve zaman çizelgesindeki mevzuat kaynakları resmî metinle kelimesi kelimesine aynı olmalıdır. Ana sayfa kanun metni panelde yalnızca okunur gösterilir (güncellemek kodla yapılır: `app/data/site.php`). Hizmet alıntısı panelden değiştirilebilir ama değişince onay ister.

## Yönetim paneli: `/yonetim`

- Adres: `https://arslanlidanismanlik.com/yonetim` (arama motorlarına kapalıdır). İlk (geçici) şifre size ayrıca iletilir; **ilk girişte Güvenlik ve yedek sayfasından değiştirin** (en az 10 karakter). Şifre değişince açık olan eski oturumlar kapanır.
- 15 dakikada 5 hatalı denemeden sonra giriş geçici olarak kilitlenir; işlem yapılmazsa oturum 8 saat sonra kapanır. Ortak bilgisayarda işiniz bitince **Çıkış yap**.
- Bir alanı değiştirdiğinizde altta “Kaydedilmemiş değişiklikler var” çubuğu çıkar; kaydetmek için çubuktaki düğmeyi ya da ⌘S / Ctrl+S’yi kullanın. **⌘K / Ctrl+K** ile bölüm, hizmet, yazı, duyuru, ilan ve metin gruplarında hızlıca arama yapılır.
- Menüdeki rozetler: sayı = yeni (okunmamış) kayıt; “Kapalı” = o bölüm sitede kapalı (içeriği hazırlamaya devam edebilirsiniz).

Aşağıdaki bölümler menüdeki sırayla anlatılır.

### Genel bakış

Günün özeti: bu hafta kapanan çağrılar, öne çıkan duyuru, yayındaki duyuru sayısı, yeni form kaydı / iş başvurusu / bülten abonesi sayıları (bekleyen şüpheli kayıtlar ayrıca gösterilir), yaklaşan tarihler, son gelenler, ziyaretçi özeti ve **Site sağlığı** kontrolleri (şifre, form anahtarı, örnek duyuru, e-posta sorunu, yedek, KVKK onayı, referans dosyaları, arama motoru doğrulaması, IndexNow, tarama). Eksik her madde bir “Düzelt” bağlantısı taşır.

### Ziyaretçiler

Bugün, dün, son 7, 30 ve 90 gün için ziyaretçi ve sayfa görüntüleme sayıları, günlük grafik, en çok bakılan on sayfa, ziyaretçilerin geldiği siteler ve telefon / bilgisayar dağılımı. Nasıl sayıldığı için aşağıdaki “Ziyaretçi sayacı” bölümüne bakın. Panele giriş yaptığınız tarayıcının kendi ziyaretleri sayılmaz.

### Değişiklik geçmişi

Sitede kimin, ne zaman, neyi değiştirdiği tek listede görünür; panelden yapılanlar “Yönetim paneli”, yapay zekâ erişimiyle yapılanlar kişinin ve uygulamanın adıyla yazılır. Bölüme ve kişiye göre süzülür. Her kaydın **Ayrıntı** sayfası alan alan öncesini ve sonrasını gösterir; **Geri al** o bölümü o değişiklikten önceki haline döndürür. Geri alma şu anki hali silmez; o da geçmişe eklenir ve geri alınabilir. Her içerik bölümü (duyurular, iş ilanları, hizmetler, yazılar, referanslar, sayfa metinleri, kurumsal listeler, yasal metinler, görünürlük, ayarlar, SEO) için son 25 değişiklik ve sitenin ilk hali saklanır. Şifre değişikliği, erişim anahtarı oluşturma, başvuru ve kayıt silme, bülten gönderimleri gibi içerik dışı olaylar listede görünür ama geri alınamaz; bu kayıtlara kişi adı ya da e-posta adresi yazılmaz. Genel günlük en çok 1000 satırdır.

### İçerik

**Duyurular.** Başlık, kurum, kısa açıklama, resmî duyuru bağlantısı ve istediğiniz kadar tarih. Tarih türleri: Başvuru başlangıcı, Son başvuru günü, Sonuç açıklanması, Bilgilendirme. Tarihler çağrı takviminde ve Duyurular sayfasında işaretlenir, takvim uygulamalarına `.ics` olarak eklenir. “Sitede yayınla” kapalıysa taslaktır. “Örnek duyuru” işaretli kayıtlarda sitede “Örnek duyuru” etiketi görünür; kurulumla gelen deneme duyurularını yayından önce gerçekleriyle değiştirin ya da silin.
*Açılışta öne çıkan duyuru:* düzenleme ekranındaki “Siteye girenlere ortada göster” anahtarıyla bir duyuru, siteye giren herkese oturum başına bir kez, ilk harekette, ekranın ortasında canlı geri sayımlı bir pencereyle gösterilir (Duyurular ve Haberdar Ol sayfalarında kendiliğinden açılmaz). Kapatılınca sol altta küçük bir etiket kalır. Aynı anda tek duyuru öne çıkar (yenisini seçince eskisi kalkar); “Ne zamana kadar?” boşsa son başvuru gününe kadar gösterilir, sonra kendiliğinden kalkar.

**İş ilanları.** Pozisyon adı, alan, şehir (ya da Yurt dışı / Uzaktan), çalışma türü, isteğe bağlı deneyim, kısa tanıtım ve üç liste: görevler, aranan nitelikler, tercih sebepleri (her listede en çok 20 madde, madde başına en çok 300 karakter). Durumlar: **Taslak** (sitede yok), **Yayında**, **Kapalı**. Yayınlamak için en az bir aranan nitelik gerekir ve son başvuru günü geçmişte olamaz. Son başvuru günü doluysa gün sonuna kadar başvuru alınır, ertesi gün ilan kendiliğinden kapanır; boşsa siz kapatana kadar açık kalır. Yayındaki ilan `/kariyer` sayfasında “Açık pozisyonlar” olarak listelenir ve `/kariyer/ilan-adresi` adresinde kendi formuyla açılır; o formla gelen başvuru ilana bağlanır. İlanın adresi başlıktan üretilir ve ilk yayından sonra değişmez. Kapalı ya da süresi dolmuş ilan sayfası “ilan kapandı” iletisiyle HTTP 410 döner, taslağın adresi “bulunamadı” döner; açık ilan sayfaları arama motorları için yapısal veri (JobPosting) taşır. Kariyer bölümü Görünürlük’ten kapatılırsa ilan adresleri de “bulunamadı” döner.

**Hizmetler.** Hizmet dosyaları (en az 3, en çok 12): başlık, menü adı, sırt etiketi (en çok 12 karakter), kısa açıklama, giriş, programlar (2–6), adımlar (3–8), evrak listesi (3–10), kimler için uygun / uygun değil, mevzuat alıntısı (kaynak, metin, sade Türkçesi), sık sorulanlar ve dokuz palet renginden biri. Satırları sürükleyerek ya da oklarla sıralarsınız; dosya numaraları sıraya göre yeniden verilir. Adres başlıktan üretilir ve oluşturulduktan sonra sabittir. **Hedef eşleştirici** düğmesi, Hizmetler sayfasındaki “Ne yapmak istiyorsunuz?” seçeneklerini yönetir: her hedef bir yazı ve öne çıkacak 1–12 hizmet dosyasıdır (2–12 hedef).

**Yazılar.** Makaleler: başlık, kategori, tarih, özet, gövde, kapak görseli, taslak durumu. Kapak görseli en az 800×450 piksel, genişlik/yükseklik oranı 1,2–2,4, en çok 8 MB olmalı; WebP’ye çevrilip `uploads/blog/` altına konur. **Yazılar kurulumda kapalıdır**; Görünürlük’ten açılır.

**Referanslar.** Kurum logoları (en az 4, en çok 30; ad 2–40 karakter). Yeni logo en az 120 piksel genişliğinde olmalı. Sitedeki mavi **kaşe** sürümü, yüklenen logodan sunucuda (GD ile) kendiliğinden üretilir; ikinci bir dosya yüklemeniz gerekmez. Logolar `uploads/refs/` ve `uploads/refs-ink/` altına gider; sıra sürükleyerek değişir. Kaşe masasında ilk dört kurumun kaşesi hazır basılı durur.

**Sayfa metinleri.** Ziyaretçinin gördüğü her yazı (başlık, paragraf, düğme, form iletisi, e-posta alt bilgisi, erişilebilirlik adı…) burada, sayfa sayfa gruplanmış olarak düzenlenir (28 grup, 860’tan fazla metin). Arama kutusu Türkçe karakter ve büyük/küçük harf farkını yok sayar. Her metnin yanında türü (tek satır, paragraf, satır sonlu, biçimli), uzunluk sınırı ve “özgün metne dön” bağlantısı vardır; değiştirilmiş metinler işaretlenir.
*Biçim işaretleri* (yalnızca “biçimli” metinlerde; HTML yazılamaz): `[kalın]…[/kalın]`, `[eğik]…[/eğik]`, `[çizgi]…[/çizgi]` (el çizimi alt çizgi), `[kırmızı-çizgi]…[/kırmızı-çizgi]`, `[halka]…[/halka]`, `[kırmızı-halka]…[/kırmızı-halka]`, `[vurgu]…[/vurgu]` (fosforlu kalem). Düzenleme kutusunun yanındaki “Biçim” düğmeleri bunları ekler.
*Yer tutucular:* `{firma}`, `{telefon}`, `{eposta}`, `{adres}`, `{yil}`, `{hizmet_sayisi}`, `{banka_adlari}` gibi değerler sayfa basılırken doldurulur; bir metinde hangilerinin kullanılabildiği düzenleme kutusunda düğme olarak listelenir. Bazı yer tutucular zorunludur (silinirse kayıt reddedilir). Sayıyı yazıya çeviren süzgeçler: `{sayi|yazı}` (8 → sekiz), `{sayi|sıra}` (30 → otuzuncu), `{sayi|iyelik}` (3 → üçü), `{ad|büyük}` (BÜYÜK HARF).
*Sayıya bağlı metinler:* süreç, ilke ve misyon sayıları sabit olduğundan (aşağıda) metinlerdeki “sekiz”, “altı”, “beş” sözcükleri sayıdan kendiliğinden yazılır; elle yazılmaz.

**Kurumsal içerik.** Altı sekme:
- *Süreç ve ilkeler:* çalışma süreci **tam 8** adım, mihenk taşı ilkeleri **tam 6** madde (ana sayfadaki “sekiz yaprak” takvimi ve mihenk taşları sayfası bu sayılara göre tasarlıdır).
- *Zaman çizelgesi:* 6–20 kayıt (yıl, tür, başlık, metin, kaynak).
- *Misyon ve vizyon:* misyon cümlesi ve **tam 5** madde; cümlenin içinde `[kırmızı-çizgi]…[/kırmızı-çizgi]` ile kırmızı kalemle çizilecek ifade işaretlenir. Vizyon mektubu: açılış yılı, selamlama, giriş, 3–8 madde, kapanış.
- *Banka hesapları:* 1–6 hesap (banka, hesap sahibi, IBAN). Banka hesabını değiştirmek yapay zekâ erişiminde ayrıca Ayarlar izni ister.
- *Form seçenekleri:* sektör listesi (3–60) ve deneyim listesi (2–20). Bir seçeneğin adını değiştirirseniz eski adla kaydolmuş bülten aboneleri yeni adın süzgecine girmez. İl listesi (81 il + Yurt dışı) sabittir.
- *Ana sayfa kanun metni:* yalnızca okunur.

**Yasal metinler.** KVKK Aydınlatma Metni ve Çerez Politikası maddeleri. Her kutu bir **maddedir**: başlık, fıkralar (aralarına boş satır), harfli liste maddeleri (her satıra bir madde; a, b, c… kendiliğinden verilir), isteğe bağlı “Sade Türkçesi”. Belge başına 3–20 madde, maddede en çok 10 fıkra, 14 liste maddesi, 3 sade not; her fıkra 5–2500 karakter. Madde numaraları, içindekiler ve bağlantı kimlikleri sıradan kendiliğinden üretilir. KVKK’daki **“İş başvurusu yapan adaylar” maddesi silinemez** (Kariyer sayfasındaki form bu maddeye bağlanır); başlığı, metni ve sırası değişebilir. `[kalın]`, `[eğik]`, `[vurgu]` işaretleri ve `{firma}`, `{eposta_baglanti}` gibi yer tutucular yazılabilir. Liste maddeleri her zaman maddenin fıkralarından **sonra** basılır; listeyi tanıtan cümleyi son fıkra yapın.
*Çerez Politikası’nın bir kısmı sitenin kendi kodu taranarak üretilir:* site çerez, tarayıcı depolaması, dış kaynak ya da gömülü içerik kullanıyor mu diye bakılır ve fıkralar buna göre görünür ya da gizlenir (fıkranın başına `[koşul: …]` yazılır; koşulların listesi düzenleme ekranındadır). Tarama sonucu düzenlenemez; yalnızca hangi fıkranın hangi duruma bağlı olduğu düzenlenir. Tarama yalnızca ziyaretçiye açık sayfaları okur; yönetim paneli ve yapay zekâ erişiminin çerezleri metinde elle yazılı bir maddedir. Sitede çerez ya da yeni bir tarayıcı depolaması eklenirse (ör. yeni bir betik) metinleri gözden geçirin. Sayfanın başlığı, “Kısaca” kutusu ve sonundaki tarih **Sayfa metinleri > KVKK / Çerez** gruplarındadır; metni değiştirince “Son güncelleme” tarihini de güncelleyin. Hukuki metindir: değişiklikten sonra hukuk danışmanınıza gösterip **Güvenlik ve yedek > KVKK ve çerez metinleri** bölümündeki “kontrol edildi” işaretini yenileyin (bu işaret yalnızca panelden verilir).

### Gelen kutusu

**Form kayıtları.** İletişim ve bülten formlarından gelenler; tür (İletişim / Bülten), tarih ve **Şüpheli** süzgeçleriyle filtrelenir, aranır, “Excel için indir” ile CSV alınır. E-posta iletilemese bile kayıtlar burada durur (iletişim kayıtlarının saklanması İletişim ve şirket’teki “Form kayıtlarını panelde de sakla” ayarına bağlıdır; bülten kayıtları ve şüpheliler her zaman saklanır). Bir kişinin kaydı satırdaki düğmeyle kalıcı silinir (bildirim e-postasının posta kutusundaki kopyasını ayrıca silin). Bir bülten kaydını sildiğinizde, o adresin başka bülten kaydı kalmadıysa adres “ayrılanlar” listesinden ve gönderim kayıtlarından (ad, soyad, adres) de silinir; başka kaydı duruyorsa ayrılma kaydı korunur. Bu yüzden bir kişinin verisinin silinmesi istendiğinde adresin **tüm** bülten kayıtlarını silin. **Şüpheliler** için aşağıdaki “Formlar ve istenmeyen gönderim süzgeci” bölümüne bakın.

**Bülten.** Sitedeki bülten formunu dolduranların listesi ve toplu e-posta.
- *Aboneler:* her e-posta adresi bir kez listelenir. Ad, il, sektör ve kayıt tarihi, o adresin ileti onayı verdiği **ilk** kayıttan gelir; aynı adresle sonradan yapılan kayıtlar (adresin sahibi olmayan biri de yapmış olabilir) Form kayıtları’nda görünür ama aboneyi değiştirmez. Durumlar: **Onaylı** (ticari ileti onayı var ve ayrılmamış; e-posta yalnızca bunlara gider), **Onay yok** (ileti onayı bulunmayan eski kayıt), **Ayrıldı**. Şüpheli ayrılan kayıtlar listeye girmez.
- *Süzmek:* **İl** ve **Sektör** kutularından bir ya da birkaç seçenek, **Durum** ve arama kutusu (ad, e-posta, sektör, konu). Sektör kutusundaki **İmalat (tümü)** adı “İmalat” ile başlayan bütün sektörleri birlikte seçer. **E-posta adreslerini kopyala** süzülen listedeki e-posta alabilen adresleri panoya alır; **Excel için indir** listeyi CSV verir. Süzülmüş sayfanın adresini saklayabilirsiniz.
- *Yazmak:* **Bu listeye e-posta gönder** yazma ekranını aynı süzgeçle açar. Konu (en çok 150 karakter) ve metin (en çok 60.000 karakter; başlık, paragraf, liste, alıntı, bağlantı; görsel eklenemez). Metinde `{ad}` ve `{soyad}` yazdığınız yere her alıcının adı gelir. **Önizlemeyi göster** alıcının göreceği e-postayı, **Kendime deneme gönder** kendi adresinize bir kopyayı verir. **Gönderimi başlat** alıcı sayısını gösterip onay ister; alıcı listesi o anda kesinleşir. Yarım işi **Taslağı kaydet** ile saklarsınız.
- *Nasıl ilerler:* sunucuda zamanlanmış görev olmadığından e-postaları açık duran gönderim sayfası on’ar on’ar gönderir. **Sayfayı açık bırakın**; sekme kapanırsa gönderim durur, sayfayı yeniden açınca kaldığı yerden sürer. **Duraklat** ve **Sürdür** vardır; bir alıcıya aynı gönderimden iki e-posta gitmez. Hata alan alıcılar nedenleriyle listelenir ve **Hata alanlara yeniden gönder** ile tekrar denenir. Gönderimi yarıda kesilen alıcılar da “Hata” görünür; e-posta onlara ulaşmış olabilir, yeniden gönderirseniz aynı e-postayı ikinci kez alabilirler. Art arda üç e-posta gönderilemezse gönderim kendiliğinden duraklar; e-posta ayarlarını kontrol edin.
- *Saatlik sınır:* barındırma firmaları saatte gönderilebilecek e-posta sayısını sınırlar. Varsayılan saatte 100’dür; **Gönderimler** sekmesinin altından 20–2000 arasında değiştirilir (sınırı barındırma firmanızdan öğrenin). Sınır dolunca sayfa kaç dakika bekleyeceğini yazar ve süre dolunca devam eder (250 alıcı, saatte 100 sınırıyla yaklaşık 2,5 saat sürer). Sınır tüm gönderimler için ortaktır.
- *Her e-postanın altında* şirket adı, adres, telefon, e-posta, e-postanın neden geldiği ve **Abonelikten ayrıl** bağlantısı kendiliğinden bulunur (**Sayfa metinleri > Bülten kayıt penceresi > Abonelere giden e-postaların alt bilgisi**; yasal bilgilendirmedir, kaldırmayın). Başlıklarda “List-Unsubscribe” de bulunduğundan e-posta uygulamalarındaki “abonelikten çık” düğmesi de çalışır. E-postalarda açılma ya da tıklama izleme yoktur.
- *Ayrılma kalıcıdır:* kişi formu yeniden doldursa bile abonelik kendiliğinden açılmaz (formu onun adresiyle başkası da doldurabilir). Böyle bir kayıt gelirse satırda “formu bu adresle yeniden dolduruldu” notu görünür; kişi gerçekten yeniden abone olmak istiyorsa **Yeniden abone yap** düğmesine basın. E-postayla ya da telefonla çıkmak istediğini bildirenleri satırdaki **Abonelikten çıkar** ile aynı gün çıkarın.
- *Ayrılma bağlantısının güvenliği:* bağlantılar sitenin form güvenlik anahtarıyla imzalanır; kurulumla gelen varsayılan anahtar hiçbir zaman kabul edilmez. Anahtarı yenilemeniz eski e-postalardaki bağlantıları bozmaz (eski anahtarlar saklanır).
- *Kayıt tutma:* her gönderim için bir kayıt kalır (konu, metin, süzgeç, kimin başlattığı, her alıcının adı, adresi ve sonucu); taslağı ya da biten gönderim kaydını **Gönderimler** sekmesinden silebilirsiniz.
- *İYS:* ticari elektronik ileti gönderen işletmelerin onayları İleti Yönetim Sistemi’ne (İYS) kaydetmesi ve ret bildirimlerini oraya işlemesi gerekebilir. Bu panel İYS ile bağlantılı değildir; ilk gönderimden önce İYS kaydınızı ve e-posta alt bilgisinin yeterliliğini hukuk danışmanınızla netleştirin.
- Yapay zekâ asistanı abone listesini süzüp sayabilir ve **taslak** hazırlayabilir; **gönderemez**. Gönderimi her zaman siz bu ekrandan başlatırsınız.

**İş başvuruları.** Kariyer sayfasındaki ve ilan sayfalarındaki formdan gelen başvurular. **Tümü / Genel başvurular (havuz) / ilan adı** süzgeci vardır; CSV süzgeci izler ve “İlan” sütunu taşır. Başvuru kaydı ilanın kimliğini ve başlığının bir kopyasını tutar: ilan silinse bile başvurular durur ve “silinmiş ilan” diye işaretlenir. Satırda “Saklama izni var” etiketi, adayın ileride açılacak pozisyonlar için saklamayı onayladığını gösterir. Buradan **özgeçmiş (PDF ya da DOCX) indirilir** ve başvuru silinir; silme özgeçmiş dosyasını da kalıcı olarak siler. **Başvurular kendiliğinden silinmez:** işe alım süreci bitince (saklama izni verenlerde adayın isteğiyle) siz silmelisiniz; bu KVKK açısından önemlidir ve şirketin e-posta kutusundaki kopyalar için de geçerlidir.

### Ayarlar

**Görünürlük.** Altı anahtar: Yazılar, Duyurular ve çağrı takvimi, Referanslar, Kariyer, Bülten (Haberdar ol), WhatsApp düğmesi. Kapalı bölüm menüden, alt bilgiden, ana sayfadan, site haritasından, `llms.txt`’den ve akışlardan kalkar, adresi “bulunamadı” döner (Kariyer kapalıyken yeni başvuru da alınmaz); içeriği panelde saklanır. Kurulumda **yalnızca Yazılar kapalıdır**. Ekranın üstündeki fihrist önizlemesi, anahtarları değiştirdikçe menünün nasıl göründüğünü gösterir. Bülten kapalıyken bile e-postalardaki **ayrılma sayfası** ve yönetim erişimi çalışır.

**İletişim ve şirket.** Dört bölüm: *İletişim* (telefon, WhatsApp numarası ülke koduyla, e-posta, kısa adres, açık adres, Google Haritalar bağlantısı), *Şirket* (ad, yetkili, vergi dairesi ve numarası; Hakkımızda’daki künyede, kaşede ve KVKK metninde görünür), *Sosyal medya* (boş bırakılan ağ görünmez; bağlantılar `https://` ile başlar), *E-posta* (bildirimlerin gideceği adres, gönderen adres ve adı, “Form kayıtlarını panelde de sakla”, SMTP, deneme e-postası). İş başvuruları “kayıtları sakla” seçiminden bağımsız olarak her zaman saklanır.

**SEO ve yapay zekâ.** Sitenin arama motorları ve yapay zekâ asistanları için hazırlığını izler: *Genel bakış* (her sayfanın başlık, açıklama, yapısal veri vb. 13 kontrolle taranması, puan, düzeltilecek yerlerin bağlantıları), *Arama motorları* (doğrulama kodları, IndexNow, site haritası, önemli adresler), *Yapay zekâ* (yapay zekâ aramalarında görünme ve model eğitimine izin ayrı ayrı; sonuçta üretilen `robots.txt` ekranda görünür). Ayrıntı için aşağıdaki “Arama motorları ve yapay zekâ” bölümüne bakın.

**Yapay zekâ erişimi.** Asistan bağlama ekranı; aşağıdaki “Yapay zekâ ile yönetim (MCP)” bölümünde anlatılır.

**Güvenlik ve yedek.** Panel şifresini değiştirme; **yedek indirme ve geri yükleme**; **form güvenlik anahtarı** (yeni anahtar oluşturunca o anda formu açık olan ziyaretçilerin sayfayı bir kez yenilemesi gerekebilir); KVKK / çerez metinleri için “hukuk danışmanı kontrol etti” işareti; güvenlik durumu özeti. Yedek için aşağıdaki “Yedek ve taşıma” bölümüne bakın.

## Formlar ve istenmeyen gönderim (spam) süzgeci

- Üç form vardır: İletişim (dilekçe), bülten kaydı (Haberdar Ol kuponu ve sayfa kenarındaki sekme aynı formu gönderir) ve iş başvurusu (Kariyer ve ilan sayfaları). Hepsi bildirim adresine e-posta gönderir (iş başvurusunda özgeçmiş ekte); ziyaretçiye ayrıca bir onay e-postası gitmez. E-postada gönderim tarihi ve IP adresi de yazar.
- Kayıtlar `storage/submissions.jsonl` dosyasında, özgeçmişler `storage/cv/` klasöründe rastgele adlarla durur; ikisi de dışarıdan erişime kapalıdır. Özgeçmiş yalnızca PDF ya da DOCX olabilir (en çok 5 MB); dosya türü uzantıdan değil içerikten doğrulanır.
- Bülten formunda **Aydınlatma metni onayı** ile **ticari elektronik ileti onayı iki ayrı kutudur**; her biri onay tarih ve saatiyle kayda yazılır. İş başvurusunda aydınlatma onayı ve isteğe bağlı “ileride açılacak pozisyonlar için sakla” izni vardır. İletişim formunda onay kutusu yoktur.
- Kayıt saklandıysa ziyaretçiye yanıt hemen verilir, bildirim e-postası ardından gönderilir (LiteSpeed ve PHP-FPM sunucularda); kayıt saklanamadıysa önce e-posta gönderilir; ikisi de olmazsa ziyaretçiye gönderimin alınamadığı söylenir.
- E-posta adresleri sıkı denetlenir (boşluk, çift tırnak, ters eğik çizgi ya da satır sonu içeren adres kabul edilmez).
- Botlara karşı: gizli tuzak alanı, imzalı ve tek kullanımlık zaman belirteci (çerez kullanmaz; iki saat geçerlidir, form açıldıktan en az 2 saniye sonra gönderilebilir), tarayıcının forma yazdığı küçük hesaplama, IP başına hız sınırı (10 dakikada 5 gönderim), tüm site için saatte 120 gönderim ve saatte 30 özgeçmiş sınırı ve aşağıdaki puanlama.
- Belirteç süresi dolmuşsa (sayfa saatlerce açık kalmışsa) ya da kullanılmışsa form kendini yeniler: yazılanlar durur, ziyaretçiden birkaç saniye sonra yeniden göndermesi istenir.

**Süzgeç.** Her gönderim, e-posta gönderilmeden önce puanlanır: gerçek bir tarayıcıdan ve insan hızında gelip gelmediği; metinde bağlantı olması; SEO, veri satışı, yazılım satışı, kumar gibi reklam kalıpları; İngilizce ya da yabancı alfabeyle yazılması; sitenin kendi alan adının geçmesi; adın rastgele harflerden ya da deneme adından oluşması; telefonun Türkiye numarasına benzememesi; geçici e-posta adresleri; aynı metnin son 30 günde ya da aynı e-postanın 24 saatte 3 kez gelmesi. Tek işaret yetmez, 6 puan ve üzeri şüpheli sayılır (gizli alanın dolması tek başına yeter). Şüpheli gönderim **silinmez**: sizin e-postanıza gönderilmez, panelde “Şüpheli” altında nedeniyle durur, gönderene olağan “ulaştı” yanıtı verilir.
- Şüpheli kayıtlar **30 gün** ve en yeni **300** kayıtla sınırlıdır; uzun metinler ilk 1000 karakteriyle saklanır; süresi dolanlar (iş başvurusuysa özgeçmişle) silinir. Silme, forma gönderim geldikçe ara sıra ve Form kayıtları / İş başvuruları sayfaları açıldığında yapılır; “Şüphelilerin hepsini sil” ile daha önce de temizleyebilirsiniz.
- Şüphelilerin e-postası gönderilmediğinden Şüpheli süzgecine arada bir göz atın; İngilizce yazan ya da mesajına web adresi ekleyen gerçek kişiler de oraya düşebilir. Gerçek kayıt için **Spam değil**: kayıt olağan listeye geçer ve bekletilen bildirim e-postası (özgeçmiş eki dahil) o anda gönderilir. Şüpheliler Genel bakış sayılarına ve CSV’ye girmez; Genel bakış bekleyen şüpheli sayısını ayrıca gösterir.
- Şüpheli gönderimler saatlik site sınırına sayılmaz; IP ve özgeçmiş sınırına sayılır.

## Ziyaretçi sayacı

Panelde **Ziyaretçiler** siteye kaç kişinin geldiğini gösterir. Sayaç siteyle birlikte gelir; dış bir hizmete (Google Analytics vb.) bağlı değildir.
- **Nasıl sayar:** sayfa açılınca tarayıcı `/olc` adresine küçük bir bildirim (sayfa adresi ve nereden gelindiği) gönderir. Botlar betik çalıştırmadığı için sayılmaz; bilinen bot kullanıcı adları da elenir. Panele giriş yapılmış tarayıcı (tarayıcının yerel depolamasında panelin bıraktığı işaretle) sayılmaz; bu işareti silerseniz o tarayıcı yeniden sayılır. JavaScript kapalı olanlar sayılmaz.
- **Gizlilik:** çerez ya da kalıcı tanımlayıcı kullanılmaz; IP adresi ve tarayıcı bilgisi saklanmaz. Aynı kişiyi gün içinde bir kez sayabilmek için IP ve tarayıcı bilgisi, her gün yenilenen rastgele bir değerle özetlenir; özetler ve değer yalnızca o gün tutulur, ertesi günün ilk sayımında silinir ve günler arasında eşleştirilemez. Bu yüzden birden çok günü kapsayan “ziyaretçi” toplamı, günlük ziyaretçi sayılarının toplamıdır. Tek bir IP’den günde en çok 40 yeni ziyaretçi, bir ziyaretçiden günde en çok 300 görüntüleme sayılır.
- **Ne saklanır:** gün gün ziyaretçi ve görüntüleme sayısı, sayfa başına görüntüleme, gelinen site adları (ör. `google.com`; adresin tamamı değil) ve telefon payı. Dosyalar `storage/stats/` altındadır. Bu açıklama KVKK ve Çerez metinlerinde de yazılıdır.

## Arama motorları ve yapay zekâ

Site, arama motorları ve yapay zekâ asistanları için baştan hazırdır; panelde **SEO ve yapay zekâ** bölümünden izlenir.
- **Yapısal veri:** her sayfada kurum, web sitesi, sayfa türü ve gezinme izi; hizmetlerde sık sorulanlar, yazılarda makale, açık iş ilanlarında JobPosting.
- **Paylaşım kartları:** her sayfa, hizmet, yazı ve açık ilan için marka diliyle otomatik üretilen 1200×630 görsel (`/og/...png`; `storage/og/` altında önbelleğe alınır, içerik değişince yenilenir).
- **Makine okunur adresler:** `/llms.txt`, `/llms-full.txt`, her sayfanın Markdown sürümü (`/hizmetler.md`, `/index.md`…), `/feed.xml`, `/feed.json`, `/duyurular.ics`, `/sitemap.xml`, `/robots.txt` (panelden yönetilir), `/.well-known/security.txt`. Kapalı bölümler hiçbirinde yer almaz.

**Canlıya aldıktan sonra yapılacaklar**
1. [Google Search Console](https://search.google.com/search-console)’da “URL öneki” mülkü ekleyin, “HTML etiketi” doğrulama kodunu panelde **SEO ve yapay zekâ > Arama motorları** bölümüne yapıştırın, sonra `sitemap.xml` adresini gönderin.
2. Aynısını Bing Webmaster Tools ve Yandex Webmaster için yapın (Bing ChatGPT aramasının da kaynağıdır).
3. Panelde **IndexNow**’u açıp “Tüm siteyi şimdi bildir”e bir kez basın; sonra içerik değiştikçe Bing, Yandex, Seznam ve Naver’e kendiliğinden bildirilir (iki bildirim arası en az 60 saniye). Bildirimler yalnızca herkese açık sayfa adreslerini taşır.
4. **Yapay zekâ** sekmesindeki tercihleri (yapay zekâ aramalarında görünme, model eğitimi) gözden geçirin.
5. Google İşletme Profili’ndeki adres ve telefonun siteyle birebir aynı olduğundan emin olun.
6. Tarama uyarılarını (ör. 160 karakteri aşan açıklamalar) Sayfa metinleri > ilgili sayfa > Arama motorları’ndan düzeltin.

## Yapay zekâ ile yönetim (MCP)

Sitenin MCP sunucusu (`https://arslanlidanismanlik.com/mcp`), yönetime katacağınız her kişinin kendi yapay zekâ asistanını (Claude, ChatGPT, Claude Code, Cursor, VS Code, Gemini CLI…) siteye bağlamasını sağlar. Asistan, kişiye verilen izinler ölçüsünde panelde yapılan işlerin neredeyse tamamını yapabilir; her değişiklik kişinin ve uygulamanın adıyla **Değişiklik geçmişi**’ne yazılır ve oradan geri alınabilir.

| İzin | Yapılabilenler |
|---|---|
| Okuma (her zaman açık) | Site durumu, sayfalar, duyurular, yayındaki iş ilanları (taslaklar için Sayfa içerikleri izni), hizmetler, yazılar, referanslar, kurumsal listeler, yasal metinler, sayfa metinleri, ayarlar, görünürlük, ziyaretçi sayıları, arama motoru taraması, değişiklik geçmişi ve ayrıntısı |
| Duyurular | Duyuru ekleme, güncelleme, silme, açılışta öne çıkarma |
| Sayfa içerikleri | Sayfa metinleri; yasal metin maddeleri; iş ilanı ekleme, güncelleme (yayınlama, kapatma, yeniden açma), silme; hizmet ekleme, güncelleme, silme, sıralama; yazı ve görsel; referans ekleme, güncelleme, silme, sıralama; kurumsal listeler ve hedef eşleştirici; değişiklikleri geri alma |
| Ayarlar | Bölümleri açıp kapatma; iletişim ve şirket bilgileri; e-posta gönderim ayarları ve deneme e-postası; bülten saatlik sınırı; arama motoru doğrulama kodları, IndexNow, yapay zekâ görünürlüğü |
| Gelen kutusu | Form kayıtlarını, bülten abonelerini ve iş başvurularını okuma; form kaydı ve başvuru silme; şüpheli kayıtları ayıklama ya da gelen kutusuna alma; aboneyi abonelikten çıkarma; Sayfa içerikleri izniyle birlikte bülten e-postası **taslağı** hazırlama |

İki izni birlikte isteyen işler: banka hesaplarını değiştirmek (Sayfa içerikleri + Ayarlar), form bildirimlerinin gittiği adresi ya da kayıtların saklanma ayarını değiştirmek (Ayarlar + Gelen kutusu), bülten taslağı hazırlamak ve silmek (Gelen kutusu + Sayfa içerikleri).

**Erişim vermek:** panelde **Yapay zekâ erişimi** → kişinin adı, izinler (yalnızca *Okuma* seçili gelir) ve süre (30 gün, 90 gün, 1 yıl, süresiz; varsayılan 90 gün) → **Erişim anahtarı oluştur**. Anahtar yalnızca bir kez gösterilir; aynı ekranda her uygulama için adımlar adres ve anahtarla hazır yazılıdır. Claude ve ChatGPT’de adres yapıştırılır, açılan sayfaya kişi anahtarını girer (OAuth); Claude Code, Cursor, VS Code ve Gemini CLI’da adres ve `Authorization: Bearer <anahtar>` başlığı yazılır. **Erişimi kaldırmak:** listeden **Erişimi kaldır**; anahtar ve ona bağlı tüm oturumlar anında kapanır. **Son işlemler** tablosu son 100 çağrıyı (okumalar ve reddedilen denemeler dahil) kimin hangi asistanla yaptığıyla gösterir; kişisel veri kayda yazılmaz. Erişim anahtarları ve oturumlar `storage/mcp/` altında yalnızca özet (hash) olarak saklanır.

**Yalnızca panelde kalanlar (güvenlik nedeniyle asistana kapalıdır):** panel şifresi ve çıkış; erişim anahtarı oluşturma, kaldırma ve bağlantı testi; form güvenlik anahtarı; yedek alma ve geri yükleme; özgeçmiş dosyalarını indirme (asistan yalnızca dosya adını görür); **bülten e-postasının gönderilmesi, duraklatılması, sürdürülmesi ve “kendime deneme” gönderimi**; abonelikten ayrılmış kişiyi yeniden abone yapma (rıza bir insana aittir); kaydı silinmiş kişinin “bir daha e-posta alma” kaydını (engel listesi) adresi yazarak kaldırma (Bülten sayfasının altındaki kutu); KVKK / çerez “kontrol edildi” işareti; CSV dosyası indirme (aynı veri okuma araçlarıyla alınır). SMTP şifresi asistanla yazılabilir ama hiçbir araç onu geri okumaz ve işlem kayıtlarına yazılmaz.

**Gizlilik uyarısı:** *Gelen kutusu* izni, ziyaretçilerin ve iş adaylarının adını, e-postasını, telefonunu ve yazdığı mesajları asistana verir; bu bilgiler kişinin kullandığı yapay zekâ hizmetinin sunucularında, çoğu zaman yurt dışında işlenir. KVKK gereği yalnızca gerçekten gerekiyorsa verin; verecekseniz de yazma izinleriyle (Duyurular, Sayfa içerikleri, Ayarlar) aynı anahtarda birleştirmeyin, çünkü asistan ziyaretçilerin yazdığı metinleri okuyacaktır. Özgeçmiş dosyalarının içeriği ve IP adresleri asistana hiçbir zaman verilmez. Bu durum KVKK metninde “Yönetimde yapay zekâ asistanları” maddesinde yazılıdır; uygulamayı değiştirirseniz metni de güncelleyin.

**Sunucu gereksinimleri:** HTTPS zorunludur; site alan adının kökünde çalışmalıdır (`/.well-known/` adresleri için). `.htaccess`, Apache’nin `Authorization` başlığını PHP’ye iletmesini sağlar; barındırma firmanız `CGIPassAuth` satırında 500 hatası verirse o bloğu silin (diğer iki yöntem çalışır). Nginx’te ek ayar gerekmez.

## Kariyer ve iş başvuruları

- Genel başvuru formu (`/kariyer`) ilan olsun olmasın çalışır; bu başvurular aday havuzudur. İlan sayfasındaki form başvuruyu ilana bağlar (pozisyon ilandan gelir). Bkz. **İş ilanları** ve **İş başvuruları**.
- Başvuru, özgeçmiş ekiyle bildirim adresine e-posta olarak gider ve panelde listelenir. CV’ler `storage/cv/` altında saklanır, dışarıdan erişilemez.
- İşe alım sonrası gereksiz hale gelen başvuruları panelden düzenli silmeniz KVKK açısından önemlidir; hiçbiri kendiliğinden silinmez.

## Bülten ve KVKK

- Aydınlatma metni onayı ile ticari elektronik ileti onayı iki ayrı kutudur ve onay tarihi kayda yazılır (aboneler listesi bu kayıtlardan türer).
- Onay metinleri **Sayfa metinleri > Bülten kayıt penceresi** ve **Haberdar Ol** gruplarındadır; ticari ileti onay metni e-posta, SMS ve telefonu sayar, oysa sitenin toplu gönderim aracı yalnızca e-posta gönderir. Metni sitenin gerçek davranışıyla uyumlu tutun.
- KVKK ve Çerez metinleri sitenin gerçekte yaptıklarına göre yazılmıştır ama hukuki kontrolden geçmemiştir; yayından önce hukuk danışmanınıza gösterin (bkz. “Yasal metinler”). Metinde saklama süresi **belirlenmemiş** olan kayıtlar var (iletişim kayıtları, bülten kayıtları ve gönderim kayıtları, ilan başvuruları): hangi sürede silineceğine karar verip hem uygulayın hem metne yazın.

## Yedek ve taşıma

**Yedek** (Güvenlik ve yedek > Yedeği indir; ayda bir önerilir): tek bir ZIP dosyasında sayfa metinleri, hizmetler, yazılar, ayarlar, kurumsal listeler, yasal metinler, duyurular, iş ilanları, değişiklik geçmişi ve panelden yüklenen görseller. Her yedekte ayrıca kaydı silinen kişilerin “bir daha e-posta alma” tercihi bulunur (`bulten/bastirilanlar.json`: adres değil, adresin anahtarlı özeti). “Form kayıtlarını ve özgeçmişleri de ekle” kutusu işaretlenirse form kayıtları, özgeçmişler, bülten gönderim kayıtları ve açık adresli abonelikten ayrılanlar listesi de girer; **kişisel veri içerir, güvenli saklayın**. **Yedeğe girmeyenler:** panel şifresi, form güvenlik anahtarı (`secret.key`), bülten imza anahtarları (`bulten/imza.json`), yapay zekâ erişim anahtarları, `storage/config.local.php`, ziyaretçi sayıları (`storage/stats/`) ve önbellekler (`storage/og/`, `storage/seo-cache/`). Panelden yazılmış SMTP şifresi de yedeğe ve içindeki geçmiş sürümlerine girmez.
**Geri yükleme:** aynı sayfadan yalnızca bu panelden indirilmiş `.zip` (en çok 50 MB) yüklenir; metinler, ayarlar, duyurular, iş ilanları ve görseller güncel halin üzerine yazılır, öncesinde kendi yedeğinizi alın. Her içerik, panelden kaydederken uygulanan kurallardan yeniden geçer: kurallara uymayan öğeler (ör. HTML içeren bir başlık, bozuk bir adres) alınmaz ve sonuç iletisinde sayılır; hiç geçerli öğe kalmayan ya da asgari sayının (en az 3 hizmet, en az 4 referans) altına düşen bölüm hiç yazılmaz, boş bir dosya hiçbir şeyi silmez. Hizmet kaybolursa hedef eşleştiricideki ona bağlı hedefler de temizlenir. **Bildirimlerin gideceği adres ve gönderen adres yedekten geri yüklenmez**, geri yükleme formundaki kutuyu bilerek işaretlemediyseniz. SMTP şifresi yedekte bulunmaz; panelde SMTP kayıtlıysa ve bağlantı bilgisi aynıysa şu anki şifre korunur, değilse şifreyi yeniden yazın. Geçmiş sürümleri yedekle birlikte gelir ama ham uygulanmaz: bir sürüme dönülürken aynı kurallardan geçer. Bülten engel listesi (`bastirilanlar.json`) yeni tercihler olarak birleştirilir. **Form kayıtları, özgeçmişler ve bülten kayıtları panelden geri yüklenmez**; gerekirse dosyalar sunucuda `storage/` altına elle konur (`submissions.jsonl`, `cv/`, `bulten/`).

**Siteyi başka sunucuya taşımak:** önce kodu (yukarıdaki yükleme paketi) yeni sunucuya yükleyin, sonra eski sunucudan şu klasör ve dosyaları **aynen** taşıyın:
- `storage/` klasörünün tamamı (`content/` panel içeriği ve geçmişi, `duyurular.json`, `ilanlar.json`, `submissions.jsonl`, `cv/`, `bulten/`, `stats/`, `mcp/`, `secret.key`, `admin.json`, `config.local.php`): `bulten/ayrilanlar.json` ve `bulten/bastirilanlar.json` kaybolursa abonelikten çıkmış kişilere yeniden e-posta gidebilir; **siteyi başka bir sunucuya taşırken `storage/` klasörünün tamamını kopyalayın**: engel listesi `secret.key` ile üretilmiş özetlerdir, anahtar gelmezse (ya da `bulten/imza.json` kaybolursa) bu kayıtlar tanınmaz ve yedek ZIP’i bunları taşımaz; `secret.key` değişirse e-postalardaki eski ayrılma bağlantıları ve açık formlar geçersiz kalabilir (`bulten/imza.json` eski anahtarları tutar, o yüzden `bulten/` klasörünü bütün taşıyın); `mcp/` taşınmazsa asistan bağlantıları yeniden kurulur.
- `uploads/` klasörü (yazı kapakları ve referans logoları).
- Alan adı değiştiyse `app/config.php` içindeki `url` ve (varsa) Search Console / IndexNow ayarlarını güncelleyin. İzinleri (`storage/`, `uploads/` yazılabilir) ve SSL’i yeniden kontrol edin.
Önbellek klasörleri (`storage/og/`, `storage/seo-cache/`) taşınmayabilir; yeniden üretilir.

## Geliştirici için kısa notlar

- İçerik varsayılanları: hizmetler `app/data/services.php`, yazılar `app/data/posts.php`, ortak listeler ve kanun alıntıları `app/data/site.php`, duyurular `app/data/duyurular.php`, sayfa metinleri `app/data/texts/NN-grup.php`, yasal maddeler `app/data/legal.php`, genel yer tutucular `app/data/text-vars.php`. Okuma işlevleri `app/content.php` (`services()`, `posts()`, `site()`, `t()`), `app/legal.php`, `app/announcements.php`, `app/ilanlar.php`.
- Yeni bir sayfa eklenirse hem `site_pages_all()` (`app/bootstrap.php`) listesine hem “Sayfa adları” metin bölümüne anahtar eklenmelidir. Sayfaların kendi CSS ve betikleri `assets/css/pages/{id}.css` ve `assets/js/pages/{id}.js` olarak kurala göre yüklenir.
- Markdown sürümleri gerçek şablonların `<main>` bölümünden üretilir; şablonların sınıf adları değişirse `app/agents/markdown.php` kancaları güncellenmelidir.
- Çerez Politikası taraması `app/legal.php` içindeki `cerez_scan()`’dir; `app/*.php`, `app/partials`, `app/pages` ve `assets/js` dosyalarını okur, `app/admin` ve `app/mcp` klasörlerini okumaz. Tarayıcı depolaması kullanan yeni bir betik eklenirse sayfa adını `app/pages/cerez.php` içindeki listeye ve `cerez.depolama.*` metnine de ekleyin.

## Kullanılan kütüphaneler (yerel olarak `assets/vendor/` içinde)

- GSAP 3.13 + ScrollTrigger ve eklentileri (ücretsiz lisans)
- Lenis 1.3 (yumuşak kaydırma)
- Yazı tipleri: Archivo (değişken genişlik), Newsreader, Courier Prime — SIL Open Font License, `assets/fonts/` (lisans metinleri aynı klasörde); paylaşım görselleri için `app/fonts/`
- İkonlar: Phosphor Icons (MIT)

Hiçbir dış CDN’e bağımlılık yoktur.

## Erişilebilirlik ve performans

- “Hareketi azalt” ayarı açık ziyaretçilere tüm sayfalar sade, statik ve eksiksiz halde sunulur.
- JavaScript kapalıyken bile tüm içerik okunabilir ve formlar çalışır.
- Ana sayfadaki büyüteç yalnızca görünürken ve hareket ederken çizim yapar.
