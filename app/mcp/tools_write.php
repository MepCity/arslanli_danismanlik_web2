<?php
declare(strict_types=1);

/**
 * MCP yazma araçları (duyurular, icerik, ayarlar izinleri): duyurular, iş ilanları, yazı kaydetme, geri alma, görünürlük, iletişim bilgileri.
 * Hizmet, referans, kurumsal liste, e-posta ve gelen kutusu araçları tools_manage.php, bülten araçları tools_bulten.php içindedir.
 * Doğrulama kuralları yönetim panelindekiyle aynıdır; duyurularda ortak model (ann_validate, ann_upsert), iş ilanlarında ilan_validate ve ilan_upsert kullanılır.
 * Her çağrı app/mcp/tools.php içinde yazma kilidi altında çalışır ve mevcut işlevlerden geçer: geçmiş kaydı, görünürlük kuralları
 * ve IndexNow bildirimi aynen çalışır.
 */

/* =========================================================================
   Ortak yardımcılar
   ========================================================================= */

/** Denetim karakterlerini ve CRLF'i temizler; kenar boşluklarını atar. */
function mcp_str($v): string
{
    $v = str_replace(["\r\n", "\r"], "\n", (string) $v);
    return trim((string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v));
}

/** Tek satırlık alan: satır sonları boşluğa çevrilir. */
function mcp_line($v): string
{
    return trim((string) preg_replace('/\s*\n\s*/u', ' ', mcp_str($v)));
}

function mcp_events_in(array $list): array
{
    $out = [];
    foreach ($list as $e) {
        $out[] = ['date' => (string) ($e['tarih'] ?? ''), 'type' => (string) ($e['tur'] ?? 'diger'), 'note' => mcp_line($e['not'] ?? '')];
    }
    return $out;
}

function mcp_ann_fields(bool $forUpdate): array
{
    return [
        'baslik'   => sc_str('Duyuru başlığı, en fazla 200 karakter. Örnek: "KOSGEB Yeşil Sanayi Destek Programı başvuruları açıldı".', ['minLength' => 1, 'maxLength' => 200]),
        'kurum'    => sc_str('Duyuruyu yapan kurum, en fazla 80 karakter. Örnek: "KOSGEB", "TÜBİTAK".', ['maxLength' => 80]),
        'ozet'     => sc_str('Kısa özet, en fazla 1200 karakter. Yalnızca resmi kaynakta yazan bilgileri kullanın; tarih, tutar ya da koşul uydurmayın.', ['maxLength' => 1200]),
        'baglanti' => sc_str('Resmi kaynağın adresi: http:// ya da https:// ile başlamalı, en fazla 300 karakter.', ['maxLength' => 300, 'pattern' => '^(https?://\S+)?$', 'x-ipucu' => 'http:// ya da https:// ile başlayan adres']),
        'tarihler' => sc_list(sc_obj([
            'tarih' => sc_date('Gün, YYYY-AA-GG biçiminde.'),
            'tur'   => sc_enum(['baslangic', 'son', 'sonuc', 'diger'], 'baslangic: başvuru başlangıcı; son: son başvuru günü; sonuc: sonuç açıklanması; diger: bilgilendirme (toplantı, duyuru vb.). Verilmezse diger.'),
            'not'   => sc_str('İsteğe bağlı kısa not, en fazla 160 karakter. Örnek: "Saat 17.00\'ye kadar".', ['maxLength' => 160]),
        ], ['tarih']), ($forUpdate ? 'Verilirse duyurunun TÜM tarih listesini değiştirir (var olan tarihler silinir, yalnızca burada verdikleriniz kalır); eskileri korumak istiyorsanız onları da yeniden yazın. ' : '')
            . 'Duyurunun tarihleri. Yalnızca resmi kaynakta yazan tarihleri girin, hiçbirini uydurmayın. Resmi kaynakta tarih yoksa boş liste verin. Son başvuru günü (tur: son) takvimde ve öne çıkarmada kullanılır.', ['maxItems' => 12]),
        'yayinda'  => sc_bool('true: sitede yayınlanır; false: taslak olarak saklanır, ziyaretçi görmez.' . ($forUpdate ? '' : ' Varsayılan true.')),
        'one_cikar' => sc_bool('true: sitenin açılışında ekranın ortasında, her ziyaretçiye bitiş gününe kadar gösterilir ("açılışta öne çıkan duyuru"). Aynı anda yalnızca bir duyuru öne çıkabilir; yenisi öncekini kaldırır. Tarihi olmayan ya da yayında olmayan duyuru öne çıkarılamaz.' . ($forUpdate ? '' : ' Varsayılan false.')),
        'one_cikar_bitis' => sc_date('Öne çıkarmanın biten günü, YYYY-AA-GG. Verilmezse duyurunun son başvuru günü (yoksa en son tarihi) kullanılır. Bugünden önce olamaz.' . ($forUpdate ? ' Boş metin ("") verirseniz elle girilen bitiş silinir.' : ''), true),
    ];
}

/** Duyuru girdisini (Türkçe alanlar) ann_validate'e uygun biçime çevirir. */
function mcp_ann_input(array $a, ?array $old): array
{
    $in = $old ? [
        'title' => $old['title'] ?? '', 'kurum' => $old['kurum'] ?? '', 'summary' => $old['summary'] ?? '', 'link' => $old['link'] ?? '',
        'published' => !empty($old['published']), 'sample' => !empty($old['sample']), 'featured' => !empty($old['featured']),
        'featured_until' => $old['featured_until'] ?? '', 'events' => $old['events'] ?? [],
    ] : [
        'title' => '', 'kurum' => '', 'summary' => '', 'link' => '', 'published' => true, 'sample' => false, 'featured' => false, 'featured_until' => '', 'events' => [],
    ];
    if (array_key_exists('baslik', $a)) $in['title'] = mcp_line($a['baslik']);
    if (array_key_exists('kurum', $a)) $in['kurum'] = mcp_line($a['kurum']);
    if (array_key_exists('ozet', $a)) $in['summary'] = mcp_str($a['ozet']);
    if (array_key_exists('baglanti', $a)) $in['link'] = mcp_line($a['baglanti']);
    if (array_key_exists('yayinda', $a)) $in['published'] = (bool) $a['yayinda'];
    if (array_key_exists('one_cikar', $a)) $in['featured'] = (bool) $a['one_cikar'];
    if (array_key_exists('one_cikar_bitis', $a)) $in['featured_until'] = (string) $a['one_cikar_bitis'];
    if (array_key_exists('tarihler', $a)) $in['events'] = mcp_events_in((array) $a['tarihler']);
    return $in;
}

/** ann_validate sonrası MCP'ye özgü kurallar: hata ve uyarı listeleri */
function mcp_ann_checks(array $item, bool $checkFeatured = true): array
{
    $errors = [];
    $warn = [];
    $today = date('Y-m-d');
    if ($checkFeatured && !empty($item['featured'])) {
        if (empty($item['published'])) {
            $errors[] = 'Yayında olmayan (taslak) bir duyuru öne çıkarılamaz. Önce yayinda: true yapın.';
        }
        $until = ann_featured_until($item);
        if ($until !== '' && $until < $today) {
            $errors[] = 'Öne çıkarmanın bitiş günü (' . $until . ') geçmişte kaldı; bugün ' . $today . '. Gelecekte bir bitiş günü verin (one_cikar_bitis) ya da öne çıkarmayın.';
        }
    }
    $types = array_column((array) $item['events'], 'type');
    if (!in_array('son', $types, true)) {
        $warn[] = 'Son başvuru günü (tur: son) girilmedi; takvimde ve öne çıkarmada son gün görünmez.';
    }
    if (($item['link'] ?? '') === '') {
        $warn[] = 'Resmi kaynak bağlantısı girilmedi.';
    }
    if (!feature('duyurular')) {
        $warn[] = 'Duyurular bölümü sitede şu an kapalı; duyuru kaydedildi ama ziyaretçilere görünmez (gorunurluk_ayarla ile açılabilir).';
    }
    return [$errors, $warn];
}

function mcp_ann_save(array $item): void
{
    if (!ann_upsert($item)) {
        mcp_fail('Kaydedilemedi: sunucuda storage klasörü yazılabilir olmalı. Site yöneticisine bildirin.');
    }
}

/** İş ilanı araçlarının (is_ilani_ekle, is_ilani_guncelle) ortak alanları. */
function mcp_ilan_fields(bool $forUpdate): array
{
    $list = fn(string $what) => sc_list(sc_str('Bir madde, en fazla 300 karakter.', ['minLength' => 1, 'maxLength' => 300]), ($forUpdate ? 'Verilirse TÜM listeyi değiştirir (eski maddeler silinir; korumak istediklerinizi yeniden yazın). ' : '') . $what . ' Her öğe bir madde; en fazla 20 madde.', ['maxItems' => 20]);
    return [
        'baslik'       => sc_str('Pozisyon adı, en fazla 120 karakter. Örnek: "Hibe ve Teşvik Uzmanı". Sayfa adresi bundan üretilir ve ilan eklendikten sonra değişmez.', ['minLength' => 1, 'maxLength' => 120]),
        'alan'         => sc_str('Alan / bölüm, en fazla 80 karakter. Örnek: "Teşvik danışmanlığı". Kariyer sayfasındaki "Çalıştığımız alanlar" (hizmet adları) ile uyumlu yazın.', ['minLength' => 1, 'maxLength' => 80]),
        'sehir'        => sc_enum(ilan_cities(), 'İlanın şehri: sitedeki şehir listesinden biri ya da "Uzaktan".'),
        'calisma_turu' => sc_enum(ilan_types(), 'Çalışma türü.'),
        'deneyim'      => sc_str('İsteğe bağlı aranan deneyim; şunlardan biri olmalı: ' . implode(', ', (array) site('deneyim')) . '.' . ($forUpdate ? ' Boş metin ("") deneyim şartını kaldırır.' : ''), ['maxLength' => 40]),
        'ozet'         => sc_str('Pozisyonun kısa tanıtımı, en fazla 600 karakter. Yalnızca kullanıcının verdiği bilgiyi yazın; maaş, yan hak ya da koşul uydurmayın.', ['maxLength' => 600]),
        'gorevler'     => $list('Pozisyondaki kişinin görevleri.'),
        'nitelikler'   => $list('Aranan nitelikler. Yayınlamak (durum: yayinda) için en az bir madde gerekir.'),
        'tercih_sebepleri' => $list('Zorunlu olmayan ama başvuruyu güçlendiren özellikler.'),
        'son_basvuru'  => sc_date('Son başvuru günü, YYYY-AA-GG; o günün sonuna kadar başvuru alınır, ertesi gün ilan kendiliğinden kapanır. Boşsa ilan siz kapatana kadar açık kalır. Yayınlanan ilan için geçmiş bir gün olamaz.' . ($forUpdate ? ' Boş metin ("") son günü kaldırır.' : ''), true),
        'durum'        => sc_enum(['taslak', 'yayinda', 'kapali'], 'taslak: sitede görünmez; yayinda: sitede açık, başvuru alır; kapali: başvuru almayı durdurur (adres "ilan kapandı" iletisi gösterir).' . ($forUpdate ? ' Kapalı ilanı yeniden açmak için yayinda verin.' : ' Varsayılan taslak.')),
    ];
}

/** İlan girdisini (Türkçe alanlar) ilan_validate'e uygun biçime çevirir. */
function mcp_ilan_input(array $a, ?array $old): array
{
    $in = $old ? [
        'title' => $old['title'] ?? '', 'area' => $old['area'] ?? '', 'city' => $old['city'] ?? '', 'type' => $old['type'] ?? '', 'experience' => $old['experience'] ?? '',
        'summary' => $old['summary'] ?? '', 'duties' => $old['duties'] ?? [], 'requirements' => $old['requirements'] ?? [], 'extras' => $old['extras'] ?? [],
        'deadline' => $old['deadline'] ?? '', 'status' => $old['status'] ?? 'taslak',
    ] : [
        'title' => '', 'area' => '', 'city' => '', 'type' => '', 'experience' => '', 'summary' => '', 'duties' => [], 'requirements' => [], 'extras' => [], 'deadline' => '', 'status' => 'taslak',
    ];
    foreach (['baslik' => 'title', 'alan' => 'area', 'sehir' => 'city', 'calisma_turu' => 'type', 'deneyim' => 'experience', 'son_basvuru' => 'deadline', 'durum' => 'status'] as $k => $f) {
        if (array_key_exists($k, $a)) $in[$f] = mcp_line($a[$k]);
    }
    if (array_key_exists('ozet', $a)) $in['summary'] = mcp_str($a['ozet']);
    foreach (['gorevler' => 'duties', 'nitelikler' => 'requirements', 'tercih_sebepleri' => 'extras'] as $k => $f) {
        if (array_key_exists($k, $a)) $in[$f] = array_map('mcp_line', (array) $a[$k]);
    }
    return $in;
}

/** ilan_validate sonrası MCP'ye özgü uyarılar */
function mcp_ilan_warnings(array $item): array
{
    $warn = [];
    if (($item['deadline'] ?? '') === '') {
        $warn[] = 'Son başvuru günü girilmedi; ilan siz kapatana kadar açık kalır.';
    }
    if (!$item['duties']) {
        $warn[] = 'Görevler boş; ilan sayfasında görev listesi görünmez.';
    }
    if (ilan_state($item) === 'doldu') {
        $warn[] = 'Son başvuru günü (' . $item['deadline'] . ') geçtiği için ilan yayında görünse de başvuruya KAPALI; yeniden açmak için son_basvuru alanına ileri bir gün verin ya da boş bırakın.';
    }
    if (($item['status'] ?? '') === 'yayinda' && !feature('kariyer')) {
        $warn[] = 'Kariyer bölümü sitede şu an kapalı; ilan kaydedildi ama ziyaretçilere görünmez (gorunurluk_ayarla ile açılabilir).';
    }
    return $warn;
}

function mcp_ilan_save(array $item): void
{
    if (!ilan_upsert($item)) {
        mcp_save_failed();
    }
}

/** Metin alanı değişikliği: 'sınırlar' dizisi alan => [en çok karakter, zorunlu mu] */
function mcp_require_nonempty(string $v, string $label): string
{
    if ($v === '') {
        mcp_fail($label . ' boş olamaz.');
    }
    return $v;
}

/** https:// ile başlayan geçerli adres (ayarlar.php ile aynı kural) */
function mcp_url_ok(string $u): bool
{
    return mb_strlen($u) <= 500 && (bool) preg_match('#^https://[^\s/]+\.[^\s/]+#i', $u) && filter_var($u, FILTER_VALIDATE_URL) !== false;
}

function mcp_save_failed(): void
{
    mcp_fail('Kaydedilemedi: sunucuda storage klasörü yazılabilir olmalı. Site yöneticisine bildirin.');
}

/* =========================================================================
   Araçlar
   ========================================================================= */

function mcp_tools_write(): array
{
    $T = [];
    $ID = sc_str('Duyurunun kimliği (duyurulari_listele sonucundan), örneğin "51d903e57d".', ['minLength' => 1, 'maxLength' => 64]);

    /* ---------- duyurular ---------- */

    $T[] = mcp_def('duyuru_ekle', 'duyurular', 'Duyuru ekle',
        'Siteye yeni bir duyuru ekler (ziyaretçi, ana sayfadaki takvimde ve Duyurular sayfasında görür). Önce duyurulari_listele ile aynı duyurunun zaten girilmediğini kontrol edin. Tarihleri yalnızca resmi kaynaktan alın, uydurmayın; çağrının resmi sayfasını sayfa_oku gibi bir araçla okuyamıyorsanız kullanıcıdan alın. Yayına çıkmadan önce kullanıcıya taslağı gösterip onay alın. Yeni duyuru varsayılan olarak yayındadır (yayinda: false ile taslak kaydedilir). "Örnek" etiketi MCP ile eklenen duyuruya hiçbir zaman konmaz.',
        sc_obj(mcp_ann_fields(false), ['baslik', 'tarihler']),
        [false, false, false], function (array $a): array {
            $title = mcp_line($a['baslik']);
            foreach (ann_all() as $x) {
                if (mb_strtolower(trim((string) ($x['title'] ?? ''))) === mb_strtolower($title)) {
                    mcp_fail('Aynı başlıkta bir duyuru zaten var (id: ' . $x['id'] . '). Güncellemek için duyuru_guncelle kullanın; gerçekten ayrı bir duyuruysa başlığını ayırt edici yapın.');
                }
            }
            [$item, $errs] = ann_validate(mcp_ann_input($a, null), null);
            [$e2, $warn] = mcp_ann_checks($item);
            $errs = array_merge($errs, $e2);
            if ($errs) {
                mcp_fail("Duyuru kaydedilmedi:\n- " . implode("\n- ", $errs));
            }
            $prevFeat = !empty($item['featured']) ? ann_featured() : null;
            mcp_ann_save($item);
            $msg = 'Duyuru eklendi' . (!empty($item['published']) ? ' ve yayınlandı.' : ' (taslak olarak, yayında değil).');
            if ($prevFeat && ($prevFeat['id'] ?? '') !== $item['id']) {
                $msg .= ' Önceki öne çıkan duyuru ("' . $prevFeat['title'] . '") artık öne çıkmıyor.';
            }
            return mcp_ok(['mesaj' => $msg, 'duyuru' => mcp_ann_public($item), 'uyarilar' => $warn], $msg);
        });

    $T[] = mcp_def('duyuru_guncelle', 'duyurular', 'Duyuruyu güncelle',
        'Var olan bir duyuruyu günceller. Yalnızca verdiğiniz alanlar değişir, verilmeyenler olduğu gibi kalır. DİKKAT: tarihler verilirse duyurunun TÜM tarih listesi onunla değiştirilir (yeni bir tarih eklemek için eski tarihleri de yeniden yazın). Önce duyuru_getir ile mevcut halini okuyun. "Örnek" etiketli bir duyurunun başlığı ya da tarihleri değiştirilirse etiket kalkar (artık gerçek içeriktir). Yayından kaldırılan (yayinda: false) duyuru öne çıkmaktan da çıkar.',
        sc_obj(['id' => $ID] + mcp_ann_fields(true), ['id']),
        [false, true, true], function (array $a): array {
            $old = ann_find(trim($a['id']));
            if (!$old) {
                mcp_fail('Bu kimlikte duyuru bulunamadı: ' . mcp_clip($a['id'], 40) . '. Kimlikler duyurulari_listele ile görülür.');
            }
            $given = array_diff(array_keys($a), ['id']);
            if (!$given) {
                mcp_fail('Değiştirilecek bir alan verilmedi. Başlık, kurum, özet, bağlantı, tarihler, yayında, one_cikar ya da one_cikar_bitis alanlarından en az birini verin.');
            }
            $in = mcp_ann_input($a, $old);
            $notes = [];
            if (!empty($old['sample']) && (array_key_exists('baslik', $a) || array_key_exists('tarihler', $a))) {
                $in['sample'] = false;
                $notes[] = '"Örnek" etiketi kaldırıldı (içerik artık gerçek).';
            }
            if (array_key_exists('yayinda', $a) && !$a['yayinda'] && !empty($old['featured'])) {
                $in['featured'] = false;
                $in['featured_until'] = '';
                $notes[] = 'Duyuru yayından kalktığı için öne çıkarma da kapatıldı.';
            }
            [$item, $errs] = ann_validate($in, $old);
            // Öne çıkarma kuralları yalnızca öne çıkarma bu çağrıda değişiyorsa denetlenir (eski, süresi dolmuş bir işaret başka güncellemeleri engellemesin)
            [$e2, $warn] = mcp_ann_checks($item, !empty($a['one_cikar']) || array_key_exists('one_cikar_bitis', $a));
            $errs = array_merge($errs, $e2);
            if ($errs) {
                mcp_fail("Duyuru güncellenmedi:\n- " . implode("\n- ", $errs));
            }
            $prevFeat = !empty($item['featured']) && empty($old['featured']) ? ann_featured() : null;
            mcp_ann_save($item);
            $msg = 'Duyuru güncellendi. Değişen alanlar: ' . implode(', ', array_values($given)) . '.';
            if ($prevFeat && ($prevFeat['id'] ?? '') !== $item['id']) {
                $notes[] = 'Önceki öne çıkan duyuru ("' . $prevFeat['title'] . '") artık öne çıkmıyor.';
            }
            if ($notes) {
                $msg .= ' ' . implode(' ', $notes);
            }
            return mcp_ok(['mesaj' => $msg, 'duyuru' => mcp_ann_public($item), 'uyarilar' => $warn], $msg);
        });

    $T[] = mcp_def('duyuru_sil', 'duyurular', 'Duyuruyu sil',
        'Bir duyuruyu siler; duyuru sitede ve takvimde kaybolur. Yalnızca kullanıcı açıkça isterse kullanın. Silme geçmişe kaydedilir: yanlışlıkla silinirse degisiklik_gecmisi (bolum: duyurular) ve geri_al ile geri getirilebilir. Tarihi geçmiş duyuruyu silmek yerine yayından kaldırmak (duyuru_guncelle yayinda: false) çoğu zaman daha doğrudur.',
        sc_obj(['id' => $ID], ['id']), [false, true, true], function (array $a): array {
            $id = trim($a['id']);
            $old = ann_find($id);
            if (!$old) {
                mcp_fail('Bu kimlikte duyuru bulunamadı: ' . mcp_clip($id, 40) . '. (Zaten silinmiş olabilir.)');
            }
            $rest = array_values(array_filter(ann_all(), fn($x) => ($x['id'] ?? '') !== $id));
            if (!ann_save_all($rest)) {
                mcp_save_failed();
            }
            $msg = 'Duyuru silindi: "' . $old['title'] . '". Geri almak için degisiklik_gecmisi (duyurular) ve geri_al kullanılabilir.';
            return mcp_ok(['mesaj' => $msg, 'silinen' => ['id' => $id, 'baslik' => (string) $old['title']]], $msg);
        });

    $T[] = mcp_def('duyuru_one_cikar', 'duyurular', 'Duyuruyu öne çıkar',
        'Bir duyuruyu "açılışta öne çıkan duyuru" yapar ya da bu durumu kaldırır. Öne çıkan duyuru, siteye giren HER ziyaretçiye ekranın ortasında, bitiş gününe kadar gösterilir; yani çok görünür bir değişikliktir, kullanıcıdan onay alın. Aynı anda yalnızca bir duyuru öne çıkabilir (yenisi öncekini kaldırır). Duyuru yayında olmalı ve bir tarihi (ya da bitiş günü) olmalıdır.',
        sc_obj([
            'id'    => $ID,
            'acik'  => sc_bool('true: öne çıkar; false: öne çıkarmayı kaldır.'),
            'bitis' => sc_date('Yalnızca acik: true iken: öne çıkarmanın biten günü (YYYY-AA-GG, bugünden önce olamaz). Verilmezse duyurunun mevcut bitişi, o da yoksa son başvuru günü kullanılır.'),
        ], ['id', 'acik']), [false, false, true], function (array $a): array {
            $old = ann_find(trim($a['id']));
            if (!$old) {
                mcp_fail('Bu kimlikte duyuru bulunamadı: ' . mcp_clip($a['id'], 40) . '. Kimlikler duyurulari_listele ile görülür.');
            }
            $in = mcp_ann_input([], $old);
            $in['featured'] = (bool) $a['acik'];
            if ($a['acik']) {
                if (isset($a['bitis'])) {
                    $in['featured_until'] = (string) $a['bitis'];
                }
            } else {
                $in['featured_until'] = '';
            }
            [$item, $errs] = ann_validate($in, $old);
            [$e2, $warn] = mcp_ann_checks($item);
            $errs = array_merge($errs, $e2);
            if ($errs) {
                mcp_fail("Öne çıkarma değiştirilmedi:\n- " . implode("\n- ", $errs));
            }
            $prevFeat = $a['acik'] ? ann_featured() : null;
            mcp_ann_save($item);
            $msg = $a['acik']
                ? 'Duyuru açılışta öne çıkıyor; bitiş günü: ' . ann_featured_until($item) . '.'
                : 'Duyuru artık açılışta öne çıkmıyor.';
            if ($prevFeat && ($prevFeat['id'] ?? '') !== $item['id']) {
                $msg .= ' Önceki öne çıkan duyuru ("' . $prevFeat['title'] . '") artık öne çıkmıyor.';
            }
            return mcp_ok(['mesaj' => $msg, 'duyuru' => mcp_ann_public($item), 'uyarilar' => $a['acik'] ? $warn : []], $msg);
        });

    /* ---------- iş ilanları (icerik izni) ---------- */

    $IID = sc_str('İlanın kimliği (is_ilanlarini_listele sonucundan), örneğin "51d903e57d".', ['minLength' => 1, 'maxLength' => 64]);

    $T[] = mcp_def('is_ilani_ekle', 'icerik', 'İş ilanı ekle',
        'Kariyer sayfasına yeni bir iş ilanı ekler: ilan kendi sayfasında (/kariyer/{adres}) yayınlanır ve o ilana özel başvuru alır; ilana başvuranlar genel başvurulardan (aday havuzu) ayrı listelenir. Önce is_ilanlarini_listele ile aynı pozisyonun zaten açılmadığını kontrol edin. Pozisyonun görev, nitelik ve koşullarını yalnızca kullanıcının verdiği bilgiden yazın; maaş, yan hak, deneyim şartı ya da tarih uydurmayın, emin değilseniz sorun. Yeni ilan varsayılan olarak TASLAK kaydedilir (ziyaretçi görmez); kullanıcıya taslağı gösterip onay aldıktan sonra durum: yayinda verin ya da is_ilani_guncelle ile yayınlayın. Yayınlanan ilan herkese açıktır ve kişisel veri içeren başvuru alır.',
        sc_obj(mcp_ilan_fields(false), ['baslik', 'alan', 'sehir', 'calisma_turu']),
        [false, false, false], function (array $a): array {
            $title = mcp_line($a['baslik']);
            foreach (ilan_all() as $x) {
                if (($x['status'] ?? '') !== 'kapali' && mb_strtolower(trim((string) ($x['title'] ?? ''))) === mb_strtolower($title)) {
                    mcp_fail('Aynı pozisyon adında bir ilan zaten var (id: ' . $x['id'] . '). Güncellemek için is_ilani_guncelle kullanın; gerçekten ayrı bir ilansa adını ayırt edici yapın.');
                }
            }
            [$item, $errs] = ilan_validate(mcp_ilan_input($a, null), null);
            if ($errs) {
                mcp_fail("İlan kaydedilmedi:\n- " . implode("\n- ", $errs));
            }
            mcp_ilan_save($item);
            $open = ilan_active($item);
            $msg = 'İlan eklendi' . ($open ? ' ve yayınlandı: ' . absolute_url(ilan_path($item)) : ' (' . ($item['status'] === 'taslak' ? 'taslak olarak, sitede görünmez' : 'kapalı olarak') . ').');
            return mcp_ok(['mesaj' => $msg, 'ilan' => mcp_ilan_public($item), 'uyarilar' => mcp_ilan_warnings($item)], $msg);
        });

    $T[] = mcp_def('is_ilani_guncelle', 'icerik', 'İş ilanını güncelle',
        'Var olan bir iş ilanını günceller; durum değişiklikleri de buradan yapılır: durum: yayinda (taslağı yayınlar ya da kapalı ilanı yeniden açar), kapali (başvuru almayı durdurur; adres "ilan kapandı" iletisi gösterir, eski başvurular durur), taslak (sitede gizler). Yalnızca verdiğiniz alanlar değişir. DİKKAT: görevler, nitelikler ya da tercih_sebepleri verilirse o listenin TAMAMI onunla değiştirilir. Önce is_ilanlarini_listele ile mevcut halini okuyun. Sayfa adresi (başlıktan üretilir) ilan eklendikten sonra değişmez. Yayınlamak için en az bir nitelik gerekir ve son başvuru günü geçmişte olamaz. Yayına çıkan ya da kapanan ilan sitede hemen etkilenir: yayınlamadan önce kullanıcıdan onay alın.',
        sc_obj(['id' => $IID] + mcp_ilan_fields(true), ['id']),
        [false, true, true], function (array $a): array {
            $old = ilan_find(trim($a['id']));
            if (!$old) {
                mcp_fail('Bu kimlikte ilan bulunamadı: ' . mcp_clip($a['id'], 40) . '. Kimlikler is_ilanlarini_listele ile görülür (taslaklar_dahil: true).');
            }
            $given = array_diff(array_keys($a), ['id']);
            if (!$given) {
                mcp_fail('Değiştirilecek bir alan verilmedi. Başlık, alan, şehir, çalışma türü, deneyim, özet, görevler, nitelikler, tercih sebepleri, son başvuru günü ya da durum alanlarından en az birini verin.');
            }
            [$item, $errs] = ilan_validate(mcp_ilan_input($a, $old), $old);
            if ($errs) {
                mcp_fail("İlan güncellenmedi:\n- " . implode("\n- ", $errs));
            }
            mcp_ilan_save($item);
            $msg = 'İlan güncellendi. Değişen alanlar: ' . implode(', ', array_values($given)) . '.';
            if (($old['status'] ?? '') !== $item['status']) {
                $msg .= ' Durum: ' . (ilan_statuses()[$old['status']] ?? $old['status']) . ' → ' . ilan_statuses()[$item['status']] . '.';
            }
            if (ilan_active($item)) {
                $msg .= ' Adres: ' . absolute_url(ilan_path($item));
            }
            return mcp_ok(['mesaj' => $msg, 'ilan' => mcp_ilan_public($item), 'uyarilar' => mcp_ilan_warnings($item)], $msg);
        });

    $T[] = mcp_def('is_ilani_sil', 'icerik', 'İş ilanını sil',
        'Bir iş ilanını siler; ilan Kariyer sayfasından kalkar ve adresi "bulunamadı" döner. Bu ilana gelen başvurular SİLİNMEZ: başvuru listesinde ilanın başlığıyla ve "silinmiş ilan" işaretiyle kalır. Yalnızca kullanıcı açıkça isterse kullanın; başvuru almayı durdurmak için silmek yerine kapatmak (is_ilani_guncelle durum: kapali) çoğu zaman daha doğrudur. Silme geçmişe kaydedilir: yanlışlıkla silinirse degisiklik_gecmisi (bolum: ilanlar) ve geri_al ile geri getirilebilir.',
        sc_obj(['id' => $IID], ['id']), [false, true, true], function (array $a): array {
            $id = trim($a['id']);
            $old = ilan_find($id);
            if (!$old) {
                mcp_fail('Bu kimlikte ilan bulunamadı: ' . mcp_clip($id, 40) . '. (Zaten silinmiş olabilir.)');
            }
            if (!ilan_delete($id)) {
                mcp_save_failed();
            }
            $msg = 'İlan silindi: "' . $old['title'] . '". Bu ilana gelen başvurular silinmedi. Geri almak için degisiklik_gecmisi (ilanlar) ve geri_al kullanılabilir.';
            return mcp_ok(['mesaj' => $msg, 'silinen' => ['id' => $id, 'baslik' => (string) $old['title']]], $msg);
        });

    /* ---------- icerik ---------- */

    $T[] = mcp_def('yazi_kaydet', 'icerik', 'Yazı kaydet',
        'Yazı (sitede "Makaleler") oluşturur ya da günceller. adres verilmezse başlıktan yeni bir adres üretilir ve YENİ yazı oluşur; var olan bir yazıyı güncellemek için onun adresini verin (yazilari_listele). Yeni yazılar varsayılan olarak TASLAK kaydedilir ve sitede görünmez; yayınlamak için taslak: false verin (önce kullanıcıdan onay alın). Güncellemede taslak verilmezse yazının mevcut yayın durumu korunur. govde: HTML ya da düz metin. HTML yalnızca şu etiketleri kullanabilir: p, h2, h3, ul, ol, li, strong, em, a (https:// adresli), blockquote, br; diğer etiketler temizlenir. Düz metin verirseniz boş satırla ayrılan bölümler paragraf olur. Kapak görseli ayrıca yazi_gorseli_ayarla ile eklenir. Yazılar bölümü sitede kapalıysa yazı kaydedilir ama görünmez.',
        sc_obj([
            'adres'    => sc_str('Güncellenecek yazının adresi (küçük harf, rakam, tire). Verilmezse başlıktan yeni yazı oluşturulur. Var olmayan bir adres verirseniz o adresle yeni yazı oluşur.', ['maxLength' => 80]),
            'baslik'   => sc_str('Yazı başlığı, en fazla 160 karakter.', ['minLength' => 1, 'maxLength' => 160]),
            'ozet'     => sc_str('Yazı listesinde ve arama sonuçlarında görünen kısa özet, en fazla 300 karakter.', ['minLength' => 1, 'maxLength' => 300]),
            'govde'    => sc_str('Yazının metni: HTML (p, h2, h3, ul, ol, li, strong, em, a, blockquote, br) ya da düz metin (boş satırla ayrılan bölümler paragraf olur).', ['minLength' => 1, 'maxLength' => 60000]),
            'kategori' => sc_str('Kategori adı: harf, rakam, boşluk ve tire, en fazla 40 karakter. Varsayılan "Genel" (güncellemede mevcut kategori korunur).', ['maxLength' => 40]),
            'tarih'    => sc_date('Yayın tarihi, YYYY-AA-GG. Varsayılan bugün (güncellemede mevcut tarih korunur). Listede bu tarihe göre sıralanır.'),
            'taslak'   => sc_bool('true: taslak (sitede görünmez), false: yayınla. Yeni yazıda varsayılan true; güncellemede verilmezse mevcut durum korunur.'),
        ], ['baslik', 'ozet', 'govde']), [false, true, true], function (array $a): array {
            $all = mcp_posts();
            $isNew = true;
            $slug = '';
            if (isset($a['adres']) && trim($a['adres']) !== '') {
                $slug = mb_substr(slugify(mcp_line($a['adres'])), 0, 80);
                if ($slug === '') {
                    mcp_fail('Geçersiz adres: yalnızca harf, rakam ve tire kullanın.');
                }
                $isNew = !isset($all[$slug]);
            } else {
                $slug = mb_substr(adm_slug(mcp_line($a['baslik'])), 0, 80);
                if (isset($all[$slug])) {
                    mcp_fail('Bu başlıktan üretilen adres (' . $slug . ') başka bir yazıda kullanılıyor. O yazıyı güncellemek istiyorsanız adres: "' . $slug . '" verin; yeni yazıysa başlığı ayırt edici yapın.');
                }
            }
            $old = $isNew ? [] : (array) $all[$slug];
            $post = $old + ['title' => '', 'category' => 'Genel', 'date' => date('Y-m-d'), 'image' => '', 'excerpt' => '', 'body' => ''];
            $post['title'] = mcp_line($a['baslik']);
            $post['excerpt'] = mcp_line($a['ozet']);
            $post['body'] = sanitize_html(mcp_to_html((string) $a['govde']));
            if (array_key_exists('kategori', $a) && trim($a['kategori']) !== '') {
                $post['category'] = mcp_line($a['kategori']);
            }
            if (array_key_exists('tarih', $a)) {
                $post['date'] = $a['tarih'];
            }
            $errors = [];
            if ($post['title'] === '') $errors[] = 'Başlık zorunludur.';
            if ($post['excerpt'] === '') $errors[] = 'Kısa özet zorunludur.';
            if (trim(strip_tags($post['body'])) === '') $errors[] = 'Yazının metni boş olamaz (izin verilen etiketler dışındaki içerik temizlenir).';
            $dt = DateTime::createFromFormat('Y-m-d', (string) $post['date']);
            if (!$dt || $dt->format('Y-m-d') !== $post['date']) $errors[] = 'Geçerli bir yayın tarihi girin (YYYY-AA-GG).';
            if (!preg_match('/^[\p{L}\p{N} \-]+$/u', (string) $post['category']) || slugify((string) $post['category']) === '') {
                $errors[] = 'Kategori adı yalnızca harf, rakam, boşluk ve tire içerebilir.';
            }
            if ($errors) {
                mcp_fail("Yazı kaydedilmedi:\n- " . implode("\n- ", $errors));
            }
            $draft = array_key_exists('taslak', $a) ? (bool) $a['taslak'] : ($isNew ? true : !empty($old['draft']));
            unset($post['draft']);
            if ($draft) {
                $post['draft'] = true;
            }
            $all[$slug] = $post;
            if (!content_put('posts', $all)) {
                mcp_save_failed();
            }
            $warn = [];
            if (!feature('blog')) {
                $warn[] = 'Yazılar bölümü sitede şu an kapalı; yazı kaydedildi ama ziyaretçilere görünmez (gorunurluk_ayarla ile açılabilir).';
            }
            if (!$draft && feature('blog') === true) {
                $warn[] = 'Yazı yayında. Kapak görseli için yazi_gorseli_ayarla kullanın.';
            }
            $msg = ($isNew ? 'Yazı oluşturuldu' : 'Yazı güncellendi') . ($draft ? ' (taslak, sitede görünmez).' : ' ve yayınlandı.');
            return mcp_ok(['mesaj' => $msg, 'yazi' => mcp_post_public($slug, $post, false), 'yeni' => $isNew, 'uyarilar' => $warn], $msg);
        });

    $T[] = mcp_def('geri_al', 'icerik', 'Değişikliği geri al',
        'Bir içerik bölümünü önceki bir sürümüne döndürür. Sürüm kimliğini degisiklik_gecmisi ile öğrenin (her sürüm, bir değişiklikten ÖNCEKİ halin kopyasıdır; "ilk_hal" sitenin kurulumdaki özgün halidir). Geri almadan önceki şu anki hal de geçmişe eklenir, yani geri almayı da geri alabilirsiniz. Önemli: seçilen sürümdeki TÜM içerik döner (yalnızca son değişiklik değil); önce degisiklik_gecmisi özetine bakın. Sürüm kimliği son_degisiklikler sonucundaki "surum" değeri de olabilir. settings, features ve seo bölümleri için ayrıca "ayarlar", duyurular bölümü için ayrıca "duyurular" izni gerekir (iş ilanları, ilanlar bölümü, "Sayfa içerikleri" iznidir); settings geri alındığında e-posta gönderim ayarları olduğu gibi korunur.',
        sc_obj([
            'bolum' => sc_enum(array_keys(mcp_history_sections()), 'İçerik bölümü (degisiklik_gecmisi ile aynı): services, posts, refs, lists, legal, texts, settings, duyurular, ilanlar, features, seo.'),
            'surum' => sc_str('Sürüm kimliği, örneğin "20261002-153012-a1b2".', ['pattern' => '^\d{8}-\d{6}-[a-f0-9]{4}$', 'x-ipucu' => 'degisiklik_gecmisi sonucundaki "surum" değeri']),
        ], ['bolum', 'surum']), [false, true, false], function (array $a, array $ctx): array {
            $key = $a['bolum'];
            $rev = $a['surum'];
            if (in_array($key, ['settings', 'features', 'seo'], true) && !in_array('ayarlar', $ctx['principal']['scopes'], true)) {
                mcp_fail('"' . mcp_history_sections()[$key] . '" bölümünü geri almak için "Ayarlar" izni gerekir; bu erişim anahtarında yok.');
            }
            if ($key === 'ilanlar' && !in_array('icerik', $ctx['principal']['scopes'], true)) {
                mcp_fail('"' . mcp_history_sections()[$key] . '" bölümünü geri almak için "Sayfa içerikleri" izni gerekir; bu erişim anahtarında yok.');
            }
            if ($key === 'duyurular' && !in_array('duyurular', $ctx['principal']['scopes'], true)) {
                mcp_fail('"' . mcp_history_sections()[$key] . '" bölümünü geri almak için "Duyurular" izni gerekir; bu erişim anahtarında yok.');
            }
            if (changelog_rev_file($key, $rev) === null) {
                $avail = array_slice(array_keys(content_history($key)), 0, 5);
                mcp_fail('Bu sürüm bulunamadı: ' . $rev . ' (' . $key . '). ' . ($avail ? 'Son sürümler: ' . implode(', ', $avail) . '. ' : '') . 'Tam liste için degisiklik_gecmisi kullanın.');
            }
            if ($key === 'lists' && !in_array('ayarlar', $ctx['principal']['scopes'], true)) {
                // Banka hesapları yalnızca "Ayarlar" izniyle değişir; geri alma bu kuralı dolanamaz
                $def  = (array) require APP . '/data/site.php';
                $snap = (array) json_decode((string) file_get_contents((string) changelog_rev_file($key, $rev)), true);
                $cur  = (array) content_get('lists', []);
                if (($snap['banks'] ?? $def['banks'] ?? null) != ($cur['banks'] ?? $def['banks'] ?? null)) {
                    mcp_fail('Bu sürümde banka hesapları şu ankinden farklı; banka hesaplarını değiştiren bir geri alma için "Ayarlar" izni gerekir, bu erişim anahtarında yok.');
                }
            }
            changelog_note('Geri alma');
            // settings: e-posta gönderim ayarları ve kayıt saklama tercihi geri alınmaz, şu anki hali korunur
            if (!changelog_restore($key, $rev, true)) {
                if ($key === 'ilanlar') {
                    mcp_fail('Bu sürümdeki ilan verisi geçerli ilan içermediği (bozuk) ya da kaydedilemediği için geri yüklenemedi; hiçbir şey değiştirilmedi.');
                }
                mcp_save_failed();
            }
            $msg = mcp_history_sections()[$key] . ' ' . $rev . ' sürümüne döndürüldü. Geri almadan önceki hal de geçmişe eklendi.';
            $opened = $key === 'ilanlar' ? ilan_restore_opened() : [];
            if ($opened) {
                $msg .= ' DİKKAT: şu ilanlar geri almayla başvuruya AÇILDI (sitede yayında): ' . implode(', ', $opened) . '. İstemiyorsanız is_ilani_guncelle ile kapatın.';
            }
            return mcp_ok(['mesaj' => $msg, 'bolum' => $key, 'surum' => $rev] + ($opened ? ['basvuruya_acilan_ilanlar' => $opened] : []), $msg);
        });

    /* ---------- ayarlar ---------- */

    $T[] = mcp_def('gorunurluk_ayarla', 'ayarlar', 'Bölüm görünürlüğünü ayarla',
        'Sitedeki bir bölümü ziyaretçilere açar ya da kapatır. Kapalı bölümün sayfaları "bulunamadı" döner, menülerden, ana sayfadan, site haritasından ve akışlardan kalkar; içeriği panelde ve bu araçlarla saklanmaya devam eder. Bu, sitenin görünümünü doğrudan etkiler: kullanıcıdan onay alın. Bölümler: blog (yazılar), duyurular (duyurular ve çağrı takvimi), referanslar, kariyer (iş başvuru formu dahil), bulten (Haberdar ol), whatsapp (sağ alttaki düğme).',
        sc_obj([
            'bolum' => sc_enum(array_keys(mcp_feature_info()), 'Bölüm.'),
            'acik'  => sc_bool('true: sitede açık; false: kapalı.'),
        ], ['bolum', 'acik']), [false, true, true], function (array $a): array {
            $info = mcp_feature_info();
            $new = [];
            foreach (array_keys(features_defaults()) as $k) {
                $new[$k] = feature($k);
            }
            $before = $new[$a['bolum']] ?? feature($a['bolum']);
            $new[$a['bolum']] = (bool) $a['acik'];
            [$label, $offNote] = $info[$a['bolum']];
            if ($before === $new[$a['bolum']]) {
                $m = $label . ' zaten ' . ($before ? 'açık' : 'kapalı') . '; değişiklik yapılmadı.';
                return mcp_ok(['mesaj' => $m, 'bolum' => $a['bolum'], 'acik' => $before], $m);
            }
            if (!content_put('features', $new)) {
                mcp_save_failed();
            }
            $msg = $label . ($new[$a['bolum']] ? ' sitede açıldı.' : ' sitede kapatıldı. ' . $offNote);
            return mcp_ok(['mesaj' => $msg, 'bolum' => $a['bolum'], 'acik' => $new[$a['bolum']]], $msg);
        });

    $T[] = mcp_def('iletisim_bilgilerini_guncelle', 'ayarlar', 'İletişim bilgilerini güncelle',
        'Sitede herkese açık görünen iletişim ve şirket bilgilerini günceller (altbilgi, iletişim sayfası, WhatsApp düğmesi, arama motoru verileri). Yalnızca verdiğiniz alanlar değişir. Bu bilgiler sitenin her yerinde görünür: yalnızca kullanıcının verdiği doğru bilgiyle ve onayıyla değiştirin. E-posta gönderim ve form bildirim ayarları için eposta_ayarlarini_guncelle kullanılır.',
        sc_obj([
            'telefon'    => sc_str('Telefon, sitede yazıldığı gibi görünür. Örnek: "+90 554 808 97 71".', ['maxLength' => 40]),
            'whatsapp'   => sc_str('WhatsApp numarası: ülke koduyla ve yalnızca rakamlarla, başında 0 ya da + olmadan. Örnek: "905548089771".', ['maxLength' => 20]),
            'eposta'     => sc_str('Ziyaretçilere gösterilen e-posta adresi.', ['maxLength' => 120]),
            'adres'      => sc_str('Açık adres (en fazla 400 karakter).', ['maxLength' => 400]),
            'kisa_adres' => sc_str('Kısa konum, örneğin "Pendik, İstanbul".', ['maxLength' => 80]),
            'harita'     => sc_str('Google Haritalar bağlantısı, https:// ile başlamalı.', ['maxLength' => 500]),
            'sirket_adi' => sc_str('Şirketin adı; site başlığında, altbilgide ve arama motoru verilerinde görünür. En fazla 200 karakter.', ['maxLength' => 200]),
            'yetkili'    => sc_str('Şirket yetkilisi (Hesap numaraları ve yasal sayfalarda görünür).', ['maxLength' => 200]),
            'vergi_dairesi' => sc_str('Vergi dairesi.', ['maxLength' => 200]),
            'vergi_no'   => sc_str('Vergi numarası.', ['maxLength' => 200]),
            'sosyal'     => sc_obj([
                'Instagram' => sc_str('https:// ile başlayan adres; boş metin ("") bağlantıyı kaldırır.', ['maxLength' => 500]),
                'LinkedIn'  => sc_str('https:// ile başlayan adres; boş metin ("") bağlantıyı kaldırır.', ['maxLength' => 500]),
                'Facebook'  => sc_str('https:// ile başlayan adres; boş metin ("") bağlantıyı kaldırır.', ['maxLength' => 500]),
                'X'         => sc_str('https:// ile başlayan adres; boş metin ("") bağlantıyı kaldırır.', ['maxLength' => 500]),
            ], [], 'Sosyal medya bağlantıları; yalnızca verdiğiniz ağlar değişir, boş metin o ağı kaldırır.'),
        ]), [false, true, true], function (array $a): array {
            if (!$a) {
                mcp_fail('Değiştirilecek bir alan verilmedi.');
            }
            $errors = [];
            $set = [];
            if (array_key_exists('telefon', $a)) {
                $v = mcp_line($a['telefon']);
                $digits = (string) preg_replace('/\D+/', '', $v);
                if ($v === '') $errors[] = 'Telefon numarası boş olamaz.';
                elseif (!preg_match('/^[0-9+()\s.\-]+$/', $v) || strlen($digits) < 10 || strlen($digits) > 15) $errors[] = 'Telefon numarası geçerli görünmüyor. Örnek: +90 554 808 97 71';
                else $set['phone'] = $v;
            }
            if (array_key_exists('whatsapp', $a)) {
                $v = (string) preg_replace('/[\s+\-()]+/', '', mcp_line($a['whatsapp']));
                if (!preg_match('/^[1-9][0-9]{9,14}$/', $v)) $errors[] = 'WhatsApp numarası yalnızca rakamlardan oluşmalı ve ülke koduyla başlamalıdır. Örnek: 905548089771 (başında 0 ya da + olmadan).';
                else $set['whatsapp'] = $v;
            }
            if (array_key_exists('eposta', $a)) {
                $v = mcp_line($a['eposta']);
                if (!filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-posta adresi geçerli değil.';
                else $set['email'] = $v;
            }
            if (array_key_exists('adres', $a)) {
                $v = mcp_line($a['adres']);
                if ($v === '') $errors[] = 'Adres boş olamaz.'; else $set['address'] = $v;
            }
            if (array_key_exists('kisa_adres', $a)) {
                $v = mcp_line($a['kisa_adres']);
                if ($v === '') $errors[] = 'Kısa adres boş olamaz.'; else $set['address_short'] = $v;
            }
            if (array_key_exists('harita', $a)) {
                $v = mcp_line($a['harita']);
                if (!mcp_url_ok($v)) $errors[] = 'Google Haritalar bağlantısı https:// ile başlayan geçerli bir adres olmalı.';
                else $set['maps_url'] = $v;
            }
            if (array_key_exists('sirket_adi', $a)) {
                $v = mcp_line($a['sirket_adi']);
                if ($v === '') $errors[] = 'Şirket adı boş olamaz.'; else $set['name'] = $v;
            }
            $company = [];
            foreach (['yetkili' => ['authorized', 'Yetkili kişi'], 'vergi_dairesi' => ['tax_office', 'Vergi dairesi'], 'vergi_no' => ['tax_number', 'Vergi numarası']] as $f => [$ck, $cl]) {
                if (array_key_exists($f, $a)) {
                    $v = mcp_line($a[$f]);
                    if ($v === '') $errors[] = $cl . ' boş olamaz.'; else $company[$ck] = $v;
                }
            }
            $social = null;
            $curForSocial = mcp_contact_public();
            if (array_key_exists('sosyal', $a)) {
                $social = [];
                foreach ((array) $curForSocial['sosyal'] as $k => $u) {
                    $social[$k] = (string) $u;
                }
                foreach (['Instagram', 'LinkedIn', 'Facebook', 'X'] as $net) {
                    $social += [$net => ''];
                }
                foreach ((array) $a['sosyal'] as $net => $u) {
                    $u = mcp_line($u);
                    if ($u !== '' && !mcp_url_ok($u)) $errors[] = $net . ' bağlantısı https:// ile başlayan geçerli bir adres olmalı (kaldırmak için boş metin verin).';
                    else $social[$net] = $u;
                }
            }
            if ($errors) {
                mcp_fail("Bilgiler kaydedilmedi:\n- " . implode("\n- ", $errors));
            }
            $cur = mcp_contact_public();
            $map = ['phone' => 'telefon', 'whatsapp' => 'whatsapp', 'email' => 'eposta', 'address' => 'adres', 'address_short' => 'kisa_adres', 'maps_url' => 'harita', 'name' => 'sirket_adi'];
            $cmap = ['authorized' => 'yetkili', 'tax_office' => 'vergi_dairesi', 'tax_number' => 'vergi_no'];
            $changed = [];
            foreach ($set as $k => $v) {
                if ((string) $cur[$map[$k]] !== $v) $changed[$map[$k]] = ['onceki' => (string) $cur[$map[$k]], 'yeni' => $v];
            }
            foreach ($company as $k => $v) {
                if ((string) $cur[$cmap[$k]] !== $v) $changed[$cmap[$k]] = ['onceki' => (string) $cur[$cmap[$k]], 'yeni' => $v];
            }
            $curSocial = (array) $cur['sosyal'];
            if ($social !== null) {
                foreach ($social as $net => $u) {
                    if ((string) ($curSocial[$net] ?? '') !== $u) $changed['sosyal.' . $net] = ['onceki' => (string) ($curSocial[$net] ?? ''), 'yeni' => $u];
                }
            }
            if (!$changed) {
                return mcp_ok(['mesaj' => 'Değişiklik yok: bilgiler zaten bu halde.'], 'Değişiklik yok: bilgiler zaten bu halde.');
            }
            $s = content_get('settings', []);
            if (!is_array($s)) {
                $s = [];
            }
            foreach ($set as $k => $v) {
                $s[$k] = $v;
            }
            if ($company) {
                $s['company'] = array_merge(is_array($s['company'] ?? null) ? $s['company'] : [], $company);
            }
            if ($social !== null) {
                $s['social'] = $social;
            }
            if (!content_put('settings', $s)) {
                mcp_save_failed();
            }
            $msg = 'İletişim ve şirket bilgileri güncellendi: ' . implode(', ', array_keys($changed)) . '.';
            return mcp_ok(['mesaj' => $msg, 'degisenler' => $changed, 'iletisim' => mcp_contact_public()], $msg);
        });

    require_once __DIR__ . '/tools_manage.php';
    require_once __DIR__ . '/tools_bulten.php';
    return array_merge($T, mcp_tools_manage(), mcp_tools_bulten(), mcp_inbox_tools());
}
