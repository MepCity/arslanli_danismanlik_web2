# Arslanlı Yatırım & Danışmanlık · web sitesi (v3 "Evrak")

Veritabanı gerektirmeyen, saf PHP ile çalışan site. Derleme adımı yok; klasörü sunucuya yüklemeniz yeterli.

## Tasarım fikri

Site, hibe ve teşvik işinin kendi dünyasından kurulur: kanun metni, üst yazı, dilekçe, telli dosya, kaşe, takvim yaprağı, dekont.
Her sayfa bu dünyadan farklı bir nesneyle çalışır:

| Sayfa | Adres | Deneyim |
|---|---|---|
| Ana sayfa | `/` | 5746 sayılı Kanun’un gerçek metni üzerinde gezen büyüteç; altında sade Türkçesi. Koparılan takvim yapraklarıyla başvuru akışı, kitap dizini gibi dosya listesi, kırmızı kalemle düzeltilen cümleler, kaşe gibi basılan referanslar |
| Hakkımızda | `/hakkimizda` | Arşiv rafı: 2001–2026 arası teşvik mevzuatındaki değişiklikler klasör sırtlarında |
| Hizmetler | `/hizmetler` | Dosya dolabı: dokuz telli dosya ve “ne yapmak istiyorsunuz?” eşleştiricisi |
| Hizmet detayı | `/urunler/detay/...` | Açık dosya: “Ek” sekmeleri, madde madde işler, işaretlenip yazdırılabilen evrak listesi |
| Referanslar | `/referans` | Kaşe masası: tıkladığınız yere müşteri logosu basılır |
| Makaleler | `/blog` | Gazete ön sayfası |
| Makale | `/blog/...` | Sakin okuma sayfası, kenarda ilerleyen kurşun kalem |
| İletişim | `/iletisim` | Boşlukları doldurulan dilekçe; gönderilince “ALINDI” kaşesi |
| Haberdar Ol | `/haberdarol` | Dergi kuponu |
| Hesap Numaralarımız | `/hesap-numaralarimiz` | POS fişi gibi basılan dekontlar |
| Misyonumuz | `/kurumsal/misyonumuz` | Çizgili defterde iş listesi |
| Vizyonumuz | `/kurumsal/vizyonumuz` | 2037’de açılacak mühürlü mektup |
| Mihenk Taşlarımız | `/kurumsal/mihenk-taslarimiz` | Siyah taşa sürtülerek ortaya çıkan ilkeler |
| Çerez / KVKK | `/kurumsal/...` | Madde madde sözleşme düzeni ve “Kısaca” özetleri |
| 404 | — | “Eksik evrak” yazısı |

Sayfalar arası geçişte yeni sayfa, masaya konan yeni bir kâğıt gibi aşağıdan gelir (tarayıcının View Transitions özelliği; desteklemeyen tarayıcıda normal geçiş olur).

## Gereksinimler

- PHP 8.0 veya üzeri (8.1+ önerilir)
- Apache (`mod_rewrite` açık) ya da Nginx
- Form e-postaları için `mail()` ya da bir SMTP hesabı

## Kurulum

1. Bu klasörün **tüm içeriğini** (gizli `.htaccess` dosyaları dahil) sitenin kök dizinine (`public_html` vb.) yükleyin. `.git` klasörünü yüklemeyin.
2. `app/config.php` dosyasını açın:
   - `secret` değerini uzun, rastgele bir metinle değiştirin.
   - `url` canlı alan adınız olmalı (`https://arslanlidanismanlik.com`).
   - Telefon, e-posta, adres, vergi bilgileri ve sosyal medya adreslerini kontrol edin (alt bilgideki kaşe bu bilgilerden oluşur).
   - Hosting firmanız `mail()` fonksiyonuna izin vermiyorsa `mail.smtp` bölümünü doldurun.
3. `storage/` klasörünün PHP tarafından yazılabilir olduğundan emin olun (genelde 755 yeterli; gerekirse 775).
4. SSL kuruluysa `.htaccess` içindeki HTTPS yönlendirme satırlarının başındaki `#` işaretlerini kaldırın.

Site bir alt klasörde de çalışır (ör. `alanadi.com/yeni/`); adresler otomatik ayarlanır.

### Nginx kullanıyorsanız

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ ^/(app|storage)/ { deny all; }
location ~ \.(md|log|jsonl)$ { deny all; }
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

## Yerelde çalıştırma

```bash
php -S 127.0.0.1:8093 index.php
```

## İçerik nerede?

| Ne | Dosya |
|---|---|
| İletişim, ticari bilgiler, sosyal medya, e-posta ayarları | `app/config.php` |
| 9 hizmet alanı (başlık, metinler, programlar, evrak listesi, SSS) | `app/data/services.php` |
| Makaleler | `app/data/posts.php` |
| Ana sayfadaki kanun metinleri, takvim yaprakları, arşiv rafı, ilkeler, misyon, vizyon, referanslar, banka hesapları | `app/data/site.php` |
| Sayfa şablonları | `app/pages/*.php` |
| Ortak üst bilgi, fihrist, alt bilgi | `app/partials/layout.php` |
| Firma kaşesi | `app/partials/kase.php` |

**Mevzuat alıntıları:** Ana sayfadaki kanun metinleri mevzuat.gov.tr’deki 5746 sayılı Kanun’un güncel metninden birebir alınmıştır. Arşiv rafındaki tarih ve sayılar Resmî Gazete kayıtlarıyla karşılaştırılmıştır (Ekim 2026). Mevzuat değiştikçe bu metinleri `app/data/site.php` içinden güncelleyin.

**Yeni makale:** `app/data/posts.php` dosyasına yeni bir blok ekleyin, görselini `assets/img/blog/` klasörüne `.webp` olarak koyun.

**Yeni referans:** Logoyu şeffaf arka planlı olarak `assets/img/refs/{kisa-ad}.webp` dosyasına koyun, kaşe görünümü için aynı adla `assets/img/refs/ink/{kisa-ad}.webp` dosyasını da ekleyin (logonun koyu kısımları opak, açık kısımları şeffaf tek renkli bir maske), sonra `app/data/site.php` içindeki `refs` listesine ekleyin.

## Formlar

- İletişim (dilekçe) ve “Haberdar Ol” (kupon) formları `config.php` içindeki `mail.to` adresine e-posta gönderir.
- `store_submissions` açıksa her gönderim ayrıca `storage/submissions.jsonl` dosyasına kaydedilir (e-posta iletilemese bile kayıt kaybolmaz). Bu klasör dışarıdan erişime kapalıdır.
- Botlara karşı: gizli tuzak alanı, imzalı zaman belirteci (çerez kullanmaz) ve IP başına hız sınırı.
- KVKK aydınlatma metni taslaktır; yayına almadan önce hukuki kontrolden geçirin.

## Adresler

Eski sitedeki tüm adresler korunmuştur (`/hakkimizda`, `/referans`, `/blog/...`, `/kurumsal/...`, `/urunler/detay/...`, `/iletisim`, `/haberdarol`, `/hesap-numaralarimiz`). Yeni sayfalar: `/hizmetler`, `/kurumsal/kvkk-aydinlatma-metni`. Site haritası: `/sitemap.xml`.

## Kullanılan kütüphaneler (yerel olarak `assets/vendor/` içinde)

- GSAP 3.13 + ScrollTrigger ve eklentileri (ücretsiz lisans)
- Lenis 1.3 (yumuşak kaydırma)
- Yazı tipleri: Archivo (değişken genişlik), Newsreader, Courier Prime — SIL Open Font License, `assets/fonts/` (lisans metinleri aynı klasörde)
- İkonlar: Phosphor Icons (MIT)

Hiçbir dış CDN’e bağımlılık yoktur.

## Erişilebilirlik ve performans

- “Hareketi azalt” ayarı açık ziyaretçilere tüm sayfalar sade, statik ve eksiksiz halde sunulur.
- JavaScript kapalıyken bile tüm içerik okunabilir ve formlar çalışır.
- Ana sayfadaki büyüteç yalnızca görünürken ve hareket ederken çizim yapar.
- Logo vektördür; arkasındaki ses dalgası kaydırma hızına göre hareket eder.
