<?php
/**
 * Duyurular: resmî kurumların açık çağrıları ve tarihleri.
 * Başlık, tarih, kurum, özet ve bağlantılar resmî kaynaklarla karşılaştırılmıştır (Ekim 2026); olduğu gibi korunur.
 *
 * Bir duyuru:
 *  id, title, kurum, summary, link, updated,
 *  featured (bool): sitenin açılışında öne çıkan duyuru; süresi son başvuru gününde (yoksa son tarihte) dolar,
 *  events: [ { date: YYYY-MM-DD, type: baslangic|son|sonuc|diger, note } ]
 *
 * Bu dosya hem veriyi (require ile dizi döner) hem de sayfa, açılış penceresi ve takvim dosyası için yardımcıları içerir.
 * Yeni duyuru: aşağıdaki listeye bir blok ekleyin; kapanan çağrılar listede kalır ve "Süresi doldu" olarak görünür.
 */

/** Makaleler bölümü açık mı? (app/config.php 'blog'; ayar yoksa açık sayılır) */
function blog_on(): bool
{
    return (bool) cfg('blog', true);
}

function ann_types(): array
{
    return [
            'baslangic' => 'Başvuru başlangıcı',
            'son'       => 'Son başvuru günü',
            'sonuc'     => 'Sonuç açıklanması',
            'diger'     => 'Bilgilendirme',
    ];
}

function ann_data(): array
{
    static $data = null;
    if ($data === null) {
        $data = ann_items();
        foreach ($data as &$a) {
            usort($a['events'], fn($x, $y) => strcmp($x['date'], $y['date']));
        }
        unset($a);
    }
    return $data;
}

function ann_items(): array
{
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
}

/* ---------- Sıralama ve durum ---------- */

/** En yakın gelecek tarihi (yoksa en son geçmiş tarihi) sıralama anahtarı olarak döndürür. */
function ann_sort_key(array $a): string
{
    $today = date('Y-m-d');
    foreach (array_column($a['events'], 'date') as $d) {
        if ($d >= $today) {
            return '0' . $d;                                   // yaklaşanlar önce, en yakını en üstte
        }
    }
    $last = $a['events'] ? end($a['events'])['date'] : '0000-00-00';
    return '1' . (string) (99999999 - (int) str_replace('-', '', $last)); // geçmişler sonra, en yenisi önce
}

/** Ziyaretçi için sıralı duyurular. */
function ann_published(): array
{
    $items = ann_data();
    usort($items, fn($x, $y) => strcmp(ann_sort_key($x), ann_sort_key($y)));
    return $items;
}

/** Hiçbir tarihi kalmamış çağrı: süresi dolmuştur. */
function ann_is_past(array $a): bool
{
    return !$a['events'] || end($a['events'])['date'] < date('Y-m-d');
}

/** Bugünden verilen güne kaç gün var (geçmişse negatif). */
function ann_days_left(string $ymd): int
{
    return (int) round((strtotime($ymd) - strtotime(date('Y-m-d'))) / 86400);
}

function ann_left_text(string $ymd): string
{
    $n = ann_days_left($ymd);
    return $n < 0 ? 'Geçti' : ($n === 0 ? 'Bugün' : ($n === 1 ? 'Yarın' : $n . ' gün kaldı'));
}

/** "12 Ekim" */
function ann_short_date(string $ymd): string
{
    $months = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
    $t = strtotime($ymd);
    return date('j', $t) . ' ' . $months[(int) date('n', $t) - 1];
}

/** Geri sayım ve damga için hedef olay: son başvuru günü; geçtiyse ya da yoksa bugünden sonraki ilk tarih. */
function ann_target(array $a): ?array
{
    $today = date('Y-m-d');
    foreach ($a['events'] as $ev) {
        if ($ev['type'] === 'son' && $ev['date'] >= $today) return $ev;
    }
    foreach ($a['events'] as $ev) {
        if ($ev['date'] >= $today) return $ev;
    }
    return null;
}

/** Öne çıkarmanın bitiş günü: son başvuru günü, o da yoksa en son tarih. */
function ann_featured_until(array $a): string
{
    $son = array_column(array_filter($a['events'], fn($e) => $e['type'] === 'son'), 'date');
    if ($son) return max($son);
    $all = array_column($a['events'], 'date');
    return $all ? max($all) : '';
}

/** Sitenin açılışında öne çıkan duyuru (öne çıkarılmış, süresi dolmamış); birden fazlaysa en son güncellenen. */
function ann_featured(): ?array
{
    $today = date('Y-m-d');
    $list  = array_filter(ann_data(), fn($a) => !empty($a['featured']) && ann_featured_until($a) >= $today);
    usort($list, fn($x, $y) => strcmp($y['updated'], $x['updated']));
    return $list[0] ?? null;
}

function ann_find(string $id): ?array
{
    foreach (ann_data() as $a) {
        if ($a['id'] === $id) return $a;
    }
    return null;
}

function ann_url(array $a): string
{
    return url('duyurular') . '#duyuru-' . $a['id'];
}

/* ---------- iCalendar (RFC 5545) ---------- */

/** TEXT değeri kaçışı */
function ann_ics_text(string $s): string
{
    $s = str_replace(["\r\n", "\r"], "\n", $s);
    $s = str_replace(['\\', ';', ',', "\n"], ['\\\\', '\;', '\\,', '\\n'], $s);
    return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $s);
}

/** 75 sekizli satır katlama; UTF-8 karakterini ortasından bölmez. */
function ann_ics_fold(string $line): string
{
    if (strlen($line) <= 75) return $line;
    $out = '';
    $cur = '';
    foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $ch) {
        if (strlen($cur) + strlen($ch) > 75) {
            $out .= $cur . "\r\n";
            $cur = ' ';
        }
        $cur .= $ch;
    }
    return $out . $cur;
}

function ann_ics_calendar(array $anns, string $name): string
{
    $types = ann_types();
    $host  = (string) parse_url((string) cfg('url'), PHP_URL_HOST) ?: 'localhost';
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//' . ann_ics_text((string) cfg('name')) . '//Duyurular//TR',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'X-WR-CALNAME:' . ann_ics_text($name),
        'X-WR-TIMEZONE:Europe/Istanbul',
        'REFRESH-INTERVAL;VALUE=DURATION:P1D',
        'X-PUBLISHED-TTL:P1D',
    ];
    foreach ($anns as $a) {
        $stamp = gmdate('Ymd\THis\Z', (int) strtotime($a['updated']));
        foreach ($a['events'] as $ev) {
            $label = $types[$ev['type']] ?? 'Bilgilendirme';
            $desc  = array_filter([$a['summary'], $ev['note'], 'Resmi bağlantı: ' . $a['link'], 'Duyuru: ' . absolute_url('duyurular') . '#duyuru-' . $a['id']]);
            array_push($lines,
                'BEGIN:VEVENT',
                'UID:' . preg_replace('/[^A-Za-z0-9._-]/', '', $a['id'] . '-' . $ev['date'] . '-' . $ev['type']) . '@' . $host,
                'DTSTAMP:' . $stamp,
                'LAST-MODIFIED:' . $stamp,
                'DTSTART;VALUE=DATE:' . str_replace('-', '', $ev['date']),
                'DTEND;VALUE=DATE:' . (new DateTimeImmutable($ev['date']))->modify('+1 day')->format('Ymd'),
                'SUMMARY:' . ann_ics_text($label . ': ' . $a['title']),
                'DESCRIPTION:' . ann_ics_text(implode("\n", $desc)),
                'URL:' . absolute_url('duyurular') . '#duyuru-' . $a['id'],
                'CATEGORIES:' . ann_ics_text($a['kurum']),
                'TRANSP:TRANSPARENT',
                'STATUS:CONFIRMED',
                'END:VEVENT'
            );
        }
    }
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", array_map('ann_ics_fold', $lines)) . "\r\n";
}

/** /duyurular.ics (hepsi) ya da /duyurular/{id}.ics (tek duyuru) */
function ann_serve_ics(?string $id): void
{
    if ($id !== null) {
        $a = ann_find($id);
        if (!$a) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Duyuru bulunamadı.\n";
            exit;
        }
        $body = ann_ics_calendar([$a], $a['title']);
        $file = 'duyuru-' . $id . '.ics';
    } else {
        $body = ann_ics_calendar(ann_published(), cfg('name') . ': Duyurular');
        $file = 'duyurular.ics';
    }
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $file . '"');
    echo $body;
    exit;
}

return ann_data();
