<?php
declare(strict_types=1);

/**
 * MCP yönetim araçları: panelde yapılan işlerin v3 (Evrak) verisine uyarlanmış karşılıkları.
 *   icerik        hizmet dosyası ekle / güncelle / sil / sırala; yazı sil, yazı görseli; referans ekle / güncelle / sil / sırala;
 *                 kurumsal listeler (süreç, zaman çizelgesi, ilkeler, misyon, vizyon, banka hesapları, form seçenekleri)
 *   ayarlar       e-posta gönderim ayarları, deneme e-postası (arama motoru araçları tools_seo.php içindedir)
 *   gelen_kutusu  form kaydı ve iş başvurusu silme, şüpheli kayıtları ayıklama
 * Kurallar yönetim panelindekiyle aynıdır: doğrulama ve yazma işlevleri ortaktır (service_upsert, ref_upsert, lists_save, content_put);
 * geçmiş ve değişiklik günlüğü aynen işler.
 *
 * Bilerek dışarıda bırakılanlar (yalnızca panelden): panel şifresi, erişim anahtarları, form güvenlik anahtarı,
 * yedek alma ve geri yükleme, özgeçmiş dosyalarının indirilmesi, KVKK "kontrol edildi" işareti, abonelikten ayrılmış kişiyi yeniden abone yapmak.
 */

require_once __DIR__ . '/media.php';

/* =========================================================================
   Hizmet dosyası
   ========================================================================= */

/** Hizmet araçlarının (hizmet_ekle, hizmet_guncelle) ortak içerik alanları; sınırlar service_limits() ile aynıdır. */
function mcp_service_fields(): array
{
    $L = service_limits();
    $r = fn(string $k): string => $L[$k][0] . ' ile ' . $L[$k][1] . ' karakter';
    $c = fn(string $k): string => $L[$k][0] . ' ile ' . $L[$k][1] . ' madde';
    return [
        'baslik'        => sc_str('Dosya ve sayfa başlığı, ' . $r('title') . '. Örnek: "TÜBİTAK Destekleri".', ['minLength' => 1, 'maxLength' => 200]),
        'menu_adi'      => sc_str('Menüde ve iletişim formu konu listesinde görünen kısa ad, ' . $r('nav') . '. Örnek: "TÜBİTAK".', ['maxLength' => 100]),
        'sirt_etiketi'  => sc_str('Dosya sırtındaki etiket (büyük harfle basılır), ' . $r('tab') . '. Örnek: "TÜBİTAK".', ['maxLength' => 60]),
        'renk'          => sc_enum(array_keys(service_colors()), 'Dosya rengi (menü sekmesi, dosya sırtı, sayfa kenarlığı): ' . implode(', ', array_map(fn($k, $v) => $k . ' = ' . $v, array_keys(service_colors()), service_colors())) . '.'),
        'kisa_aciklama' => sc_str('Listelerde ve ana sayfada görünen tek cümlelik özet, ' . $r('short') . '.', ['minLength' => 1, 'maxLength' => 600]),
        'giris'         => sc_str('Sayfada başlığın altındaki giriş paragrafı, ' . $r('lead') . '.', ['maxLength' => 2000]),
        'programlar'    => sc_list(sc_obj([
            'ad'       => sc_str('Program adı, ' . $r('program_name') . '. Örnek: "1501 Sanayi Ar-Ge".', ['minLength' => 1, 'maxLength' => 200]),
            'aciklama' => sc_str('Programın bir cümlelik açıklaması, ' . $r('program_desc') . '.', ['minLength' => 1, 'maxLength' => 600]),
        ], ['ad', 'aciklama']), 'Dosya kartındaki ve ayrıntı tablosundaki programlar (TÜM liste; ' . $c('programs') . '). Her öğe bir ad ve bir açıklamadır.', ['maxItems' => 12]),
        'adimlar'       => sc_list(sc_obj([
            'baslik' => sc_str('Adımın başlığı, ' . $r('step_title') . '. Örnek: "Ön okuma".', ['minLength' => 1, 'maxLength' => 200]),
            'metin'  => sc_str('Adımın açıklaması, ' . $r('step_text') . '.', ['minLength' => 1, 'maxLength' => 800]),
        ], ['baslik', 'metin']), '"Ne yapıyoruz" sıralı adımları (TÜM liste; ' . $c('steps') . ').', ['maxItems' => 16]),
        'evrak_listesi' => sc_list(sc_str('Bir evrak, ' . $r('doc') . '. Örnek: "Vergi levhası ve imza sirküleri".', ['minLength' => 1, 'maxLength' => 400]), 'Başvuruda istenen evrakların kontrol listesi (TÜM liste; ' . $c('docs') . ').', ['maxItems' => 20]),
        'uygun'         => sc_str('"Kimler için uygun" metni, ' . $r('fit') . '.', ['maxLength' => 800]),
        'uygun_degil'   => sc_str('"Kimler için uygun değil" metni, ' . $r('unfit') . '.', ['maxLength' => 800]),
        'kanun_alintisi' => sc_obj([
            'kaynak'      => sc_str('Kanun ve madde, ' . $r('law_source') . '. Örnek: "5746 sayılı Kanun, Madde 2/a".', ['maxLength' => 200]),
            'metin'       => sc_str('Kanundan KELİMESİ KELİMESİNE alıntı, ' . $r('law_text') . '. Uydurmayın, özetlemeyin; resmî metinden alın (atlanan yerler "…" ile gösterilir).', ['maxLength' => 3000]),
            'sade_turkce' => sc_str('Alıntının sade Türkçe karşılığı, ' . $r('law_plain') . '.', ['maxLength' => 800]),
        ], [], 'Hizmet sayfasındaki mevzuat alıntısı (isteğe bağlı; üç alanı birlikte verin). Boş nesne ({}) alıntıyı kaldırır. Metin resmî mevzuattan alıntı olduğu için mevcut bir alıntının METNİ değişiyorsa kanun_alintisi_onayi: true da verilmelidir.'),
        'kanun_alintisi_onayi' => sc_bool('Yalnızca var olan bir hizmetin kanun alıntısının metnini değiştirirken: true = yeni metni kanunun güncel resmî metniyle karşılaştırdığımı ve kullanıcıdan onay aldığımı doğruluyorum. Panel de aynı onayı ister.'),
        'sss'           => sc_list(sc_obj([
            'soru'  => sc_str('Soru, ' . $r('faq_q') . '.', ['minLength' => 1, 'maxLength' => 300]),
            'cevap' => sc_str('Yanıt, ' . $r('faq_a') . '.', ['minLength' => 1, 'maxLength' => 1500]),
        ], ['soru', 'cevap']), 'Sık sorulan sorular (TÜM liste; ' . $c('faq') . ').', ['maxItems' => 16]),
    ];
}

/** Hizmetin kayıt şeklini (dizi) verilen Türkçe alanlarla günceller; yalnızca verilen alanlar değişir. Hata varsa McpToolError. */
function mcp_service_raw(array $base, array $a): array
{
    $raw = $base;
    foreach (['baslik' => 'title', 'menu_adi' => 'nav', 'sirt_etiketi' => 'tab', 'renk' => 'color', 'kisa_aciklama' => 'short', 'giris' => 'lead', 'uygun' => 'fit', 'uygun_degil' => 'unfit'] as $k => $f) {
        if (array_key_exists($k, $a)) {
            $raw[$f] = mcp_line($a[$k]);
        }
    }
    if (array_key_exists('programlar', $a)) {
        $raw['programs'] = array_map(fn($r) => [mcp_line($r['ad'] ?? ''), mcp_line($r['aciklama'] ?? '')], $a['programlar']);
    }
    if (array_key_exists('adimlar', $a)) {
        $raw['steps'] = array_map(fn($r) => [mcp_line($r['baslik'] ?? ''), mcp_line($r['metin'] ?? '')], $a['adimlar']);
    }
    if (array_key_exists('evrak_listesi', $a)) {
        $raw['docs'] = array_map('mcp_line', $a['evrak_listesi']);
    }
    if (array_key_exists('sss', $a)) {
        $raw['faq'] = array_map(fn($r) => [mcp_line($r['soru'] ?? ''), mcp_line($r['cevap'] ?? '')], $a['sss']);
    }
    if (array_key_exists('kanun_alintisi', $a)) {
        $l = (array) $a['kanun_alintisi'];
        $unknown = array_diff(array_keys($l), ['kaynak', 'metin', 'sade_turkce']);
        if ($unknown) {
            mcp_fail('kanun_alintisi içinde bilinmeyen alan: ' . implode(', ', $unknown) . ' (yalnızca kaynak, metin, sade_turkce).');
        }
        $raw['law'] = $l ? ['source' => mcp_line($l['kaynak'] ?? ''), 'text' => mcp_str($l['metin'] ?? ''), 'plain' => mcp_line($l['sade_turkce'] ?? '')] : [];
        if ($l && in_array('', [$raw['law']['source'], $raw['law']['text'], $raw['law']['plain']], true)) {
            mcp_fail('kanun_alintisi için kaynak, metin ve sade_turkce alanlarının üçü de dolu olmalı (alıntıyı kaldırmak için boş nesne {} verin).');
        }
    }
    return $raw;
}

/** service_upsert sonucunu araç hatasına çevirir; kanun alıntısı onayı gerekiyorsa eski metni göstererek ister. */
function mcp_service_save(?string $slug, array $raw, array $a, ?array $old): array
{
    $clean = service_clean($raw);
    $lawChanged = $old !== null && service_law_changed($old['law'] ?? null, $clean['law'] ?? null);
    if ($lawChanged && empty($a['kanun_alintisi_onayi'])) {
        mcp_fail("Hizmetin mevzuat alıntısı değişiyor. Bu metin resmî mevzuattan kelimesi kelimesine alıntıdır; kaydetmeden önce yeni metni kanunun güncel resmî metniyle karşılaştırın ve kullanıcıdan onay alın.\n"
            . '- Şu anki alıntı (' . (string) ($old['law']['source'] ?? '') . '): ' . mcp_clip((string) ($old['law']['text'] ?? ''), 400) . "\n"
            . '- Yeni alıntı (' . (string) ($clean['law']['source'] ?? '') . '): ' . mcp_clip((string) ($clean['law']['text'] ?? '(kaldırılıyor)'), 400) . "\n"
            . 'Onaylandıysa aynı çağrıyı kanun_alintisi_onayi: true ile yeniden gönderin; alıntıya dokunmak istemiyorsanız kanun_alintisi alanını hiç göndermeyin.');
    }
    $opt = ['confirm_law' => !empty($a['kanun_alintisi_onayi'])];
    if ($slug === null && !empty($a['adres'])) {
        $opt['slug'] = mcp_line($a['adres']);
    }
    $r = service_upsert($slug, $raw, $opt);
    if (!$r['ok']) {
        mcp_fail("Hizmet kaydedilmedi:\n- " . implode("\n- ", $r['errors']));
    }
    return $r;
}

/* =========================================================================
   Referans
   ========================================================================= */

/** Referansı koduyla (kod) ya da adıyla (ad) bulur; dizin döner. */
function mcp_ref_find(array $all, string $ref): int
{
    $want = trim($ref);
    foreach ($all as $i => $r) {
        if ((string) $r['id'] === $want) {
            return $i;
        }
    }
    $fold = mcp_fold($want);
    foreach ($all as $i => $r) {
        if (mcp_fold((string) $r['name']) === $fold) {
            return $i;
        }
    }
    $codes = implode(', ', array_map(fn($r) => (string) $r['id'], $all));
    mcp_fail('Bu kod ya da adda bir referans yok: ' . mcp_clip($ref, 80) . '. Kodları referanslari_listele ile görebilirsiniz (şu an: ' . $codes . ').');
    return -1;
}

function mcp_refs_public(array $all): array
{
    $out = [];
    foreach (array_values($all) as $i => $r) {
        $out[] = ['sira' => $i + 1, 'kod' => (string) $r['id'], 'ad' => (string) $r['name'], 'logo_url' => mcp_abs(media_url((string) $r['logo'])), 'kase_url' => mcp_abs(media_url((string) $r['ink']))];
    }
    return $out;
}

/** Referans araçlarının ortak alanı: logo (adresten ya da base64) ve kaşe rengi. */
function mcp_ref_logo_fields(string $what): array
{
    return mcp_image_fields($what) + [
        'logo_rengi' => sc_enum(['auto', 'dark', 'light'], 'Kaşe görünümü üretilirken logonun rengi: auto (kendiliğinden algılanır, varsayılan), dark (koyu logo), light (açık renkli logo; kaşe ters çevrilir). Yalnızca logo verildiğinde kullanılır.'),
    ];
}

/** Araç girdisinde bir logo (gorsel_url ya da gorsel_base64) var mı? */
function mcp_image_given(array $a): bool
{
    return trim((string) ($a['gorsel_url'] ?? '')) !== '' || trim((string) ($a['gorsel_base64'] ?? '')) !== '';
}

/** Referans logosunu geçici dosyaya alıp ref_upsert'e verir; geçici dosya her durumda silinir. */
function mcp_ref_save(?string $id, string $name, array $a): array
{
    $tmp = mcp_image_tmp($a);
    try {
        $r = ref_upsert($id, $name, $tmp !== null ? ['path' => $tmp, 'uploaded' => false] : null, (string) ($a['logo_rengi'] ?? 'auto'));
    } finally {
        if ($tmp !== null && is_file($tmp)) {
            @unlink($tmp);
        }
    }
    if (!$r['ok']) {
        mcp_fail("Referans kaydedilmedi:\n- " . implode("\n- ", $r['errors']));
    }
    return $r;
}

/* =========================================================================
   Kurumsal listeler
   ========================================================================= */

/**
 * Yapay zekâ girdisini (Türkçe alan adları) kurumsal listenin ham kayıt biçimine çevirir; şekil hatalarını bildirir.
 * Sayı ve uzunluk sınırlarını lists_save() denetler (panelle aynı kurallar).
 * @return array{0:mixed, 1:string[]}
 */
function mcp_list_input(string $liste, $ic): array
{
    $err = [];
    $isObj = fn($x): bool => is_array($x) && !mcp_is_list($x);
    $rows = function (string $what, array $map) use ($ic, &$err, $isObj): array {
        $out = [];
        if (!is_array($ic) || !mcp_is_list($ic)) {
            $err[] = $what . ': icerik bir liste (dizi) olmalı.';
            return $out;
        }
        foreach ($ic as $i => $r) {
            if (!$isObj($r)) {
                $err[] = $what . ': ' . ($i + 1) . '. öğe bir nesne olmalı ({' . implode(', ', array_keys($map)) . '}).';
                continue;
            }
            $unk = array_diff(array_keys($r), array_keys($map));
            if ($unk) {
                $err[] = $what . ': ' . ($i + 1) . '. öğede bilinmeyen alan: ' . implode(', ', $unk) . ' (izin verilenler: ' . implode(', ', array_keys($map)) . ').';
                continue;
            }
            $x = [];
            foreach ($map as $tr => $en) {
                if (!array_key_exists($tr, $r) || !is_scalar($r[$tr])) {
                    $err[] = $what . ': ' . ($i + 1) . '. öğede "' . $tr . '" alanı eksik ya da metin değil.';
                    continue 2;
                }
                $x[$en] = mcp_line((string) $r[$tr]);
            }
            $out[] = $x;
        }
        return $out;
    };
    $strs = function (string $what, $v) use (&$err): array {
        $out = [];
        if (!is_array($v) || !mcp_is_list($v)) {
            $err[] = $what . ': bir metin listesi (dizi) olmalı.';
            return $out;
        }
        foreach ($v as $i => $t) {
            if (!is_string($t)) {
                $err[] = $what . ': ' . ($i + 1) . '. öğe metin olmalı.';
                continue;
            }
            $out[] = mcp_line($t);
        }
        return $out;
    };
    $val = null;
    switch ($liste) {
        case 'process':
            $val = $rows('Çalışma süreci', ['asama' => 'phase', 'baslik' => 'title', 'biz' => 'us', 'siz' => 'you']);
            break;
        case 'timeline':
            $val = $rows('Zaman çizelgesi', ['yil' => 'year', 'tur' => 'kind', 'baslik' => 'title', 'metin' => 'text', 'kaynak' => 'src']);
            foreach ($val as $i => $x) {
                $k = ['mevzuat' => 'law', 'biz' => 'us'][mb_strtolower($x['kind'])] ?? '';
                if ($k === '') {
                    $err[] = 'Zaman çizelgesi: ' . ($i + 1) . '. kaydın türü "mevzuat" ya da "biz" olmalı.';
                }
                $val[$i]['kind'] = $k;
            }
            break;
        case 'principles':
            $val = array_map(fn($x) => [$x['t'], $x['a']], $rows('İlkeler', ['baslik' => 't', 'aciklama' => 'a']));
            break;
        case 'banks':
            $val = $rows('Banka hesapları', ['banka' => 'bank', 'hesap_sahibi' => 'holder', 'hesap_no' => 'account', 'iban' => 'iban']);
            break;
        case 'mission':
            // cumle_duz yalnızca okuma içindir (işaretsiz sürüm); kurumsal_listeleri_getir çıktısı olduğu gibi geri yazılabilsin diye kabul edilir ve yok sayılır
            if (!$isObj($ic) || array_diff(array_keys($ic), ['cumle', 'cumle_duz', 'maddeler'])) {
                $err[] = 'Misyon: icerik {cumle, maddeler} nesnesi olmalı (cumle_duz yalnızca okumadır, yazmak gerekmez). Örnek: {"cumle": "İşletmelere, [kırmızı-çizgi]kâğıt işine boğulmadan[/kırmızı-çizgi] destek bulmak.", "maddeler": ["...", "...", "...", "...", "..."]}. Kırmızı çizgi işareti isteğe bağlıdır; işaretsiz düz cümle de yazılabilir.';
                break;
            }
            if (isset($ic['cumle']) && !is_string($ic['cumle'])) {
                $err[] = 'Misyon: cumle metin olmalı.';
                break;
            }
            $val = ['statement' => mcp_line((string) ($ic['cumle'] ?? '')), 'items' => $strs('Misyon maddeleri', $ic['maddeler'] ?? null)];
            break;
        case 'vision':
            if (!$isObj($ic) || array_diff(array_keys($ic), ['acilis_yili', 'selamlama', 'giris', 'maddeler', 'kapanis'])) {
                $err[] = 'Vizyon: icerik {acilis_yili, selamlama, giris, maddeler, kapanis} nesnesi olmalı.';
                break;
            }
            $val = ['open_year' => (string) ($ic['acilis_yili'] ?? ''), 'greeting' => mcp_line((string) ($ic['selamlama'] ?? '')), 'intro' => mcp_line((string) ($ic['giris'] ?? '')),
                'items' => $strs('Vizyon maddeleri', $ic['maddeler'] ?? null), 'closing' => mcp_line((string) ($ic['kapanis'] ?? ''))];
            break;
        case 'goals':
            $val = [];
            if (!is_array($ic) || !mcp_is_list($ic)) {
                $err[] = 'Hedef eşleştirici: icerik bir liste (dizi) olmalı: [{secenek, hizmetler: [adres]}].';
                break;
            }
            foreach ($ic as $i => $g) {
                if (!$isObj($g) || array_diff(array_keys($g), ['secenek', 'hizmetler']) || !isset($g['secenek']) || !is_scalar($g['secenek'])) {
                    $err[] = 'Hedef eşleştirici: ' . ($i + 1) . '. öğe {secenek: metin, hizmetler: [hizmet adresi, ...]} nesnesi olmalı.';
                    continue;
                }
                $val[] = ['label' => mcp_line((string) $g['secenek']), 'services' => $strs('Hedef eşleştirici ' . ($i + 1) . '. öğenin hizmetleri', $g['hizmetler'] ?? null)];
            }
            break;
        case 'sektorler':
        case 'deneyim':
            $val = $strs(lists_keys()[$liste], $ic);
            break;
        default:
            $err[] = 'Bilinmeyen liste: ' . $liste . '. Düzenlenebilir listeler: ' . implode(', ', array_keys(lists_keys())) . '.';
    }
    return [$val, $err];
}

/** kurumsal_liste_guncelle açıklamasındaki liste tarifleri (sınırlar lists_limits() ile aynı). */
function mcp_list_help(): string
{
    $L = lists_limits();
    $n = fn(string $k): string => $L[$k]['count'][0] === $L[$k]['count'][1] ? 'TAM ' . $L[$k]['count'][0] : $L[$k]['count'][0] . ' ile ' . $L[$k]['count'][1];
    return 'process = ana sayfadaki çalışma süreci, ' . $n('process') . ' adım: [{asama, baslik, biz, siz}] (aşama etiketi, adım başlığı, "Biz yaparız" ve "Siz yaparsınız" metinleri); '
        . 'timeline = Hakkımızda raf sayfasındaki mevzuat zaman çizelgesi, ' . $n('timeline') . ' kayıt: [{yil (dört haneli, her yıl bir kez), tur ("mevzuat" ya da "biz"), baslik, metin, kaynak}]; yıla göre sıralanır; mevzuat kayıtlarında kaynak Resmî Gazete künyesidir, yalnızca doğrulanmış bilgiyle yazın; '
        . 'principles = Mihenk Taşlarımız, ' . $n('principles') . ' ilke: [{baslik, aciklama}]; '
        . 'mission = Misyonumuz, bir NESNE: {cumle (isteğe bağlı kırmızı çizgi işaretiyle), maddeler: [metin]} (' . $n('mission') . ' madde; okumada gelen cumle_duz işaretsiz sürümdür, yazarken gerekmez ama gönderilirse yok sayılır); '
        . 'vision = Vizyonumuz mektubu, bir NESNE: {acilis_yili (gelecekte bir yıl), selamlama, giris, maddeler: [metin] (' . $n('vision') . ' madde), kapanis}; '
        . 'banks = Hesap Numaralarımız, ' . $n('banks') . ' hesap: [{banka, hesap_sahibi, hesap_no, iban}], IBAN geçerli olmalı (TR + 24 rakam, mod-97 sağlaması) ve tekrarlanmaz; '
        . 'sektorler = bülten formundaki sektör seçenekleri, metin listesi, ' . $n('sektorler') . ' madde (adı "İmalat" ile başlayanlar bülten süzgecinde "İmalat (tümü)" kısayoluna girer; bir adı değiştirirseniz eski adla kaydolmuş aboneler yeni adın süzgecine girmez); '
        . 'deneyim = kariyer formu deneyim seçenekleri, metin listesi, ' . $n('deneyim') . ' madde; '
        . 'goals = Hizmetler sayfasındaki "Ne yapmak istiyorsunuz?" eşleştiricisi, ' . $n('goals') . ' hedef: [{secenek (ziyaretçinin gördüğü yazı), hizmetler: [hizmet adresi, ...] (seçilince öne çıkacak hizmet dosyaları; adresler hizmetleri_listele sonucundadır)}]. '
        . 'mission.cumle içinde [kırmızı-çizgi]…[/kırmızı-çizgi] işareti sayfada kırmızı kalemle altı çizilecek ifadeyi gösterir (isteğe bağlı, en çok bir; başka işaret yazılamaz). Önce kurumsal_listeleri_getir ile cumle alanını okuyun: işaret orada yazılıdır ve silerseniz sayfadaki kırmızı çizgi kalkar. ';
}

/* =========================================================================
   Araçlar
   ========================================================================= */

/** E-posta gönderim ayarlarının şu anki hali (şifre hiçbir zaman verilmez). */
function mcp_mail_public(): array
{
    $c = mcp_config_now();
    $smtp = is_array($c['mail']['smtp'] ?? null) && !empty($c['mail']['smtp']['host']) ? $c['mail']['smtp'] : null;
    return [
        'bildirim_adresi'  => (string) ($c['mail']['to'] ?? ''),
        'gonderen_adresi'  => (string) ($c['mail']['from'] ?? ''),
        'gonderen_adi'     => (string) ($c['mail']['from_name'] ?? ''),
        'kayitlari_sakla'  => !empty($c['store_submissions']),
        'smtp_acik'        => $smtp !== null,
        'smtp_kaynak'      => ['miras' => 'yapilandirma', 'panel' => 'panel', 'kapali' => 'kapali'][smtp_state((array) content_get('settings', []))['mode']],
        'smtp_sunucu'      => (string) ($smtp['host'] ?? ''),
        'smtp_port'        => (int) ($smtp['port'] ?? 465),
        'smtp_guvenlik'    => (string) ($smtp['secure'] ?? 'ssl'),
        'smtp_kullanici'   => (string) ($smtp['user'] ?? ''),
        'smtp_sifre_kayitli' => (string) ($smtp['pass'] ?? '') !== '',
    ];
}

function mcp_tools_manage(): array
{
    $T = [];
    $ADRES = fn(string $what) => sc_str($what . ' sayfa adresi (küçük harf, rakam, tire), örneğin "ar-ge-yapilanmasi".', ['minLength' => 1, 'maxLength' => 120]);
    $svcFields = mcp_service_fields();

    /* ---------- hizmetler ---------- */

    $T[] = mcp_def('hizmet_ekle', 'icerik', 'Hizmet dosyası ekle',
        'Siteye yeni bir hizmet dosyası ekler: menüde, ana sayfadaki Dosya dolabında, Hizmetler sayfasında ve altbilgide görünür ve kendi sayfası (/urunler/detay/{adres}) açılır. Dosya numarası ("03.N") sıraya göre kendiliğinden verilir, elle yazılmaz; yeni dosya listenin sonuna eklenir (sıra için hizmetleri_sirala). '
        . 'Dosyanın tüm alanları (başlık, menü adı, sırt etiketi, renk, kısa açıklama, giriş, programlar, adımlar, evrak listesi, uygun / uygun değil, SSS) zorunludur ve her birinin karakter ya da madde sınırı vardır (tasarım bunlara göre kuruludur; aşılırsa araç hangi alanın nasıl olması gerektiğini söyler). Mevzuat alıntısı isteğe bağlıdır; verirseniz kanundan kelimesi kelimesine alınmalıdır. '
        . 'Önce hizmetleri_listele ile benzer bir hizmetin olmadığını kontrol edin ve kullanıcıya taslağı gösterip onay alın. Program adlarını, kurum ve tutarları yalnızca doğrulanmış bilgiyle yazın. Sınır: en çok ' . service_limits()['services'][1] . ' hizmet.',
        sc_obj(['adres' => sc_str('İsteğe bağlı sayfa adresi (küçük harf, rakam, tire; ' . service_limits()['slug'][0] . ' ile ' . service_limits()['slug'][1] . ' karakter). Verilmezse başlıktan üretilir. Oluşturulduktan sonra değiştirilemez.', ['maxLength' => 80])] + $svcFields,
            ['baslik', 'menu_adi', 'sirt_etiketi', 'renk', 'kisa_aciklama', 'giris', 'programlar', 'adimlar', 'evrak_listesi', 'uygun', 'uygun_degil', 'sss']),
        [false, false, false], function (array $a): array {
            $raw = mcp_service_raw(['no' => '', 'title' => '', 'nav' => '', 'tab' => '', 'color' => '', 'short' => '', 'lead' => '', 'programs' => [], 'steps' => [], 'docs' => [], 'fit' => '', 'unfit' => '', 'faq' => []], $a);
            $r = mcp_service_save(null, $raw, $a, null);
            $s = mcp_services()[$r['slug']];
            $msg = 'Hizmet dosyası eklendi: "' . $s['title'] . '" (dosya ' . svc_no($s) . ', ' . absolute_url('urunler/detay/' . $r['slug']) . ').';
            return mcp_ok(['mesaj' => $msg, 'hizmet' => mcp_service_public($r['slug'], $s, true)], $msg);
        });

    $T[] = mcp_def('hizmet_guncelle', 'icerik', 'Hizmet dosyasını güncelle',
        'Var olan bir hizmet dosyasının içeriğini günceller (yeni dosya için hizmet_ekle, silmek için hizmet_sil, sıra için hizmetleri_sirala). Yalnızca verdiğiniz alanlar değişir. programlar, adimlar, evrak_listesi ve sss listeleri verilirse TÜM listeyi değiştirir (eski öğeler silinir): önce hizmet_getir ile mevcut halini okuyun ve değiştirmediğiniz öğeleri de listeye yazın. Her alanın sınırı vardır (hizmet_ekle açıklamasındaki gibi); aşılırsa araç hangi alanı nasıl düzelteceğinizi söyler. '
        . 'KANUN ALINTISI: kanun_alintisi.metin resmî mevzuattan kelimesi kelimesine alıntıdır. Var olan bir alıntının metnini değiştirmek için kanun_alintisi_onayi: true da gerekir; yoksa araç eski ve yeni metni gösterip reddeder. Bunu yalnızca yeni metni kanunun güncel resmî metniyle karşılaştırdıktan ve kullanıcıdan onay aldıktan sonra verin. Dosya numarası ve sayfa adresi değiştirilemez. Program adlarını, kurum ve tutarları yalnızca doğrulanmış bilgiyle yazın.',
        sc_obj(['adres' => sc_str('Hizmetin sayfa adresi, örneğin "tubitak-1989".', ['minLength' => 1, 'maxLength' => 120])] + $svcFields, ['adres']),
        [false, true, true], function (array $a): array {
            $all = mcp_services();
            $slug = mcp_service_slug($a['adres'], $all);
            $given = array_values(array_diff(array_keys($a), ['adres', 'kanun_alintisi_onayi']));
            if (!$given) {
                mcp_fail('Değiştirilecek bir alan verilmedi. baslik, menu_adi, sirt_etiketi, renk, kisa_aciklama, giris, programlar, adimlar, evrak_listesi, uygun, uygun_degil, kanun_alintisi ya da sss alanlarından en az birini verin.');
            }
            $old = (array) $all[$slug];
            $raw = mcp_service_raw($old, $a);
            mcp_service_save($slug, $raw, $a, $old);
            $s = mcp_services()[$slug];
            $msg = 'Hizmet dosyası güncellendi: "' . $s['title'] . '". Değişen alanlar: ' . implode(', ', $given) . '.';
            return mcp_ok(['mesaj' => $msg, 'hizmet' => mcp_service_public($slug, $s, true)], $msg);
        });

    $T[] = mcp_def('hizmet_sil', 'icerik', 'Hizmet dosyasını sil',
        'Bir hizmet dosyasını siler: sayfası "bulunamadı" döner; menüden, ana sayfadan, Dosya dolabından ve listelerden kalkar; sonraki dosyaların numaraları ("03.N") kayar. Yalnızca kullanıcı açıkça isterse kullanın. En az ' . service_limits()['services'][0] . ' hizmet kalmalıdır (Dosya dolabı ve menü bu sayının altında düzenli görünmez). Yanlışlıkla silinirse son_degisiklikler ve geri_al (bolum: services) ile geri getirilebilir.',
        sc_obj(['adres' => $ADRES('Silinecek hizmetin')], ['adres']), [false, true, true], function (array $a): array {
            $all = mcp_services();
            $slug = mcp_service_slug($a['adres'], $all);
            $title = (string) ($all[$slug]['title'] ?? $slug);
            $r = service_delete($slug);
            if (!$r['ok']) {
                mcp_fail('Hizmet silinmedi: ' . implode(' ', $r['errors']));
            }
            $msg = 'Hizmet dosyası silindi: "' . $title . '". Geri almak için geri_al (bolum: services) kullanılabilir.';
            $warn = [];
            foreach ((array) site('goals') as $gl) {
                if (in_array($slug, (array) ($gl['services'] ?? []), true)) {
                    $warn[] = 'Hizmetler sayfasındaki hedef eşleştiricide "' . (string) ($gl['label'] ?? '') . '" hedefi bu dosyayı öne çıkarıyordu; sayfa silinen dosyayı göstermez ama hedefin listesinde artık yok sayılır. Gerekirse kurumsal_liste_guncelle (liste: goals) ile hedefi güncelleyin.';
                }
            }
            return mcp_ok(['mesaj' => $msg, 'silinen' => ['adres' => $slug, 'baslik' => $title], 'kalan_hizmet_sayisi' => count(mcp_services()), 'uyarilar' => $warn], $msg);
        });

    $T[] = mcp_def('hizmetleri_sirala', 'icerik', 'Hizmetleri sırala',
        'Hizmet dosyalarının sitedeki sırasını değiştirir (menü, ana sayfadaki Dosya dolabı, Hizmetler sayfası ve altbilgi bu sırayı kullanır); dosya numaraları ("03.N") yeni sıraya göre yeniden verilir. adresler: yeni sıraya göre hizmet adresleri. Listede yazmadığınız hizmetler, mevcut sıralarıyla sona eklenir.',
        sc_obj(['adresler' => sc_list(sc_str('Hizmet adresi.', ['minLength' => 1, 'maxLength' => 120]), 'Yeni sıraya göre hizmet adresleri (hizmetleri_listele sonucundaki "adres").', ['minItems' => 1, 'maxItems' => 60])], ['adresler']),
        [false, true, true], function (array $a): array {
            $all = mcp_services();
            $seen = [];
            foreach ($a['adresler'] as $slug) {
                $slug = mcp_service_slug((string) $slug, $all);
                if (isset($seen[$slug])) {
                    mcp_fail('Bu adres listede iki kez yazılmış: ' . $slug);
                }
                $seen[$slug] = true;
            }
            $r = services_reorder(array_keys($seen));
            if (!$r['ok']) {
                mcp_fail('Sıra kaydedilmedi: ' . implode(' ', $r['errors']));
            }
            $new = mcp_services();
            return mcp_ok(['mesaj' => 'Hizmet sırası kaydedildi.', 'sira' => array_map(fn($k) => ['adres' => (string) $k, 'dosya_no' => svc_no($new[$k]), 'baslik' => (string) $new[$k]['title']], array_keys($new))], 'Hizmet sırası kaydedildi.');
        });

    /* ---------- yazılar ---------- */

    $T[] = mcp_def('yazi_sil', 'icerik', 'Yazıyı sil',
        'Bir yazıyı (sitede "Makaleler") siler; adresi "bulunamadı" döner. Yalnızca kullanıcı açıkça isterse kullanın. Yazıyı yayından kaldırmak yeterliyse silmek yerine yazi_kaydet ile taslak: true verin. Yanlışlıkla silinirse geri_al (bolum: posts) ile geri getirilebilir.',
        sc_obj(['adres' => $ADRES('Silinecek yazının')], ['adres']), [false, true, true], function (array $a): array {
            $all = mcp_posts();
            $slug = trim($a['adres']);
            if (!isset($all[$slug])) {
                mcp_fail('Bu adreste yazı yok: ' . mcp_clip($slug, 60) . '. Adresler yazilari_listele ile görülür (taslaklar_dahil: true).');
            }
            $title = (string) ($all[$slug]['title'] ?? $slug);
            unset($all[$slug]);
            if (!content_put('posts', $all)) {
                mcp_save_failed();
            }
            $msg = 'Yazı silindi: "' . $title . '". Geri almak için geri_al (bolum: posts) kullanılabilir.';
            return mcp_ok(['mesaj' => $msg, 'silinen' => ['adres' => $slug, 'baslik' => $title]], $msg);
        });

    $T[] = mcp_def('yazi_gorseli_ayarla', 'icerik', 'Yazı görselini ayarla',
        'Bir yazının kapak görselini ekler, değiştirir ya da kaldırır. Görsel yazı listesinde ve yazının başında görünür; en fazla 1600 piksel genişliğe küçültülür ve WebP\'ye çevrilir. Kapak kuralları panelle aynıdır: en az 800×450 piksel ve en/boy oranı 1,2 ile 2,4 arası olmalı (liste 3:2, yazı başı 21:9 kutuya kırpar); küçük ya da çok dar/uzun görsel reddedilir. Görseli gorsel_url (herkese açık https adresi; sunucu indirir) ya da gorsel_base64 ile verin; kaldırmak için kaldir: true verin. Telif hakkı size ya da firmaya ait olan, kullanıcının verdiği görselleri kullanın.',
        sc_obj(['adres' => $ADRES('Yazının')] + mcp_image_fields('Yazı görseli') + ['kaldir' => sc_bool('true: yazının görselini kaldır.')], ['adres']),
        [false, true, true], function (array $a): array {
            $all = mcp_posts();
            $slug = trim($a['adres']);
            if (!isset($all[$slug])) {
                mcp_fail('Bu adreste yazı yok: ' . mcp_clip($slug, 60) . '. Adresler yazilari_listele ile görülür (taslaklar_dahil: true).');
            }
            $remove = !empty($a['kaldir']);
            $img = $remove ? null : mcp_image_input($a, 'blog', 1600);
            if (!$remove && $img === null) {
                mcp_fail('Bir görsel verin (gorsel_url ya da gorsel_base64) ya da görseli kaldırmak için kaldir: true yazın.');
            }
            $all[$slug]['image'] = $remove ? '' : $img;
            if (!content_put('posts', $all)) {
                mcp_save_failed();
            }
            $msg = $remove ? 'Yazının görseli kaldırıldı.' : 'Yazının görseli kaydedildi.';
            return mcp_ok(['mesaj' => $msg, 'adres' => $slug, 'gorsel_url' => $remove ? null : mcp_abs(media_url((string) $img))], $msg);
        });

    /* ---------- referanslar ---------- */

    $KOD = sc_str('Referansın kodu (referanslari_listele sonucundaki "kod", örneğin "ornek-havacilik") ya da kurum adı.', ['minLength' => 1, 'maxLength' => 80]);

    $T[] = mcp_def('referans_ekle', 'icerik', 'Referans ekle',
        'Referanslara (birlikte çalışılan kurumlar) yeni bir kurum logosu ekler; ana sayfadaki kaşe şeridinde ve Referanslar sayfasındaki kaşe masasında görünür. Logo zorunludur: gorsel_url (herkese açık https adresi; sunucu güvenli biçimde indirir) ya da gorsel_base64 ile verin. Sunucu logoyu 600 piksele küçültür, WebP yapar ve sitedeki KAŞE görünümünü (tek renk mürekkep maskesi) kendisi üretir; ikinci bir görsel vermeniz gerekmez. En az 120 piksel genişlikte, 12:1 ile 1:3 arası orantılı, şeffaf ya da düz açık zeminli PNG/WebP önerilir; açık renkli logoda logo_rengi: light verin. Yalnızca kullanıcının referans olarak gösterilmesini onayladığı kurumları ekleyin. Yeni referans sona eklenir (sıra için referanslari_sirala). Sınır: ' . refs_limits()['count'][0] . ' ile ' . refs_limits()['count'][1] . ' referans, kurum adı ' . refs_limits()['name'][0] . ' ile ' . refs_limits()['name'][1] . ' karakter.',
        sc_obj(['ad' => sc_str('Kurum adı, ' . refs_limits()['name'][0] . ' ile ' . refs_limits()['name'][1] . ' karakter. Örnek: "Örnek Havacılık A.Ş.".', ['minLength' => 1, 'maxLength' => 200])] + mcp_ref_logo_fields('Kurum logosu'), ['ad']),
        [false, false, false], function (array $a): array {
            if (!mcp_image_given($a)) {
                mcp_fail('Logo zorunludur: gorsel_url (https adresi) ya da gorsel_base64 verin.');
            }
            $r = mcp_ref_save(null, mcp_line($a['ad']), $a);
            $all = refs_list();
            $i = mcp_ref_find($all, (string) $r['id']);
            $msg = 'Referans eklendi: "' . $all[$i]['name'] . '" (' . ($i + 1) . '. sırada); logo ve kaşe görünümü üretildi.';
            return mcp_ok(['mesaj' => $msg, 'referans' => mcp_refs_public($all)[$i], 'referans_sayisi' => count($all),
                'uyarilar' => feature('referanslar') ? [] : ['Referanslar bölümü sitede şu an kapalı; logo kaydedildi ama ziyaretçilere görünmez (gorunurluk_ayarla ile açılabilir).']], $msg);
        });

    $T[] = mcp_def('referans_guncelle', 'icerik', 'Referansı güncelle',
        'Var olan bir referansın kurum adını ve/veya logosunu değiştirir. Referansı kodu ya da adıyla belirtin (referans); yeni adı yeni_ad ile, yeni logoyu gorsel_url ya da gorsel_base64 ile verin (kaşe görünümü yeni logodan yeniden üretilir; logo_rengi yalnızca logo verilirse işe yarar). Verilmeyen alanlar değişmez.',
        sc_obj(['referans' => $KOD, 'yeni_ad' => sc_str('Yeni kurum adı, ' . refs_limits()['name'][0] . ' ile ' . refs_limits()['name'][1] . ' karakter.', ['minLength' => 1, 'maxLength' => 200])] + mcp_ref_logo_fields('Yeni logo'), ['referans']),
        [false, true, true], function (array $a): array {
            $all = refs_list();
            $i = mcp_ref_find($all, $a['referans']);
            if (!array_key_exists('yeni_ad', $a) && !mcp_image_given($a)) {
                mcp_fail('Değiştirilecek bir şey verilmedi: yeni_ad ve/veya yeni logo (gorsel_url ya da gorsel_base64) verin.');
            }
            if (array_key_exists('logo_rengi', $a) && !mcp_image_given($a)) {
                mcp_fail('logo_rengi yalnızca yeni logo verilirken kullanılır (kaşe logodan üretilir).');
            }
            $name = array_key_exists('yeni_ad', $a) ? mcp_line($a['yeni_ad']) : (string) $all[$i]['name'];
            mcp_ref_save((string) $all[$i]['id'], $name, $a);
            $now = refs_list();
            $j = mcp_ref_find($now, (string) $all[$i]['id']);
            $msg = 'Referans güncellendi: "' . $now[$j]['name'] . '".';
            return mcp_ok(['mesaj' => $msg, 'referans' => mcp_refs_public($now)[$j]], $msg);
        });

    $T[] = mcp_def('referans_sil', 'icerik', 'Referansı sil',
        'Bir referansı (kurum logosunu) sitedeki tüm listelerden kaldırır. En az ' . refs_limits()['count'][0] . ' referans kalmalıdır (kaşe masasında dört kaşe hazır basılı durur). Yalnızca kullanıcı açıkça isterse kullanın. Yanlışlıkla silinirse geri_al (bolum: refs) ile geri getirilebilir.',
        sc_obj(['referans' => $KOD], ['referans']),
        [false, true, true], function (array $a): array {
            $all = refs_list();
            $i = mcp_ref_find($all, $a['referans']);
            $name = (string) $all[$i]['name'];
            $r = ref_delete((string) $all[$i]['id']);
            if (!$r['ok']) {
                mcp_fail('Referans silinmedi: ' . implode(' ', $r['errors']));
            }
            $msg = 'Referans silindi: "' . $name . '". Geri almak için geri_al (bolum: refs) kullanılabilir.';
            return mcp_ok(['mesaj' => $msg, 'silinen' => $name, 'referans_sayisi' => count(refs_list())], $msg);
        });

    $T[] = mcp_def('referanslari_sirala', 'icerik', 'Referansları sırala',
        'Referans logolarının sitedeki sırasını değiştirir (kaşe şeridi ve kaşe masası bu sırayı kullanır). referanslar: yeni sıraya göre referans kodları ya da kurum adları. Listede yazmadığınız referanslar, mevcut sıralarıyla sona eklenir.',
        sc_obj(['referanslar' => sc_list(sc_str('Referansın kodu ya da kurum adı.', ['minLength' => 1, 'maxLength' => 80]), 'Yeni sıraya göre referans kodları ya da adları (referanslari_listele sonucundaki "kod" ya da "ad").', ['minItems' => 1, 'maxItems' => 60])], ['referanslar']),
        [false, true, true], function (array $a): array {
            $all = refs_list();
            $ids = [];
            foreach ($a['referanslar'] as $ref) {
                $id = (string) $all[mcp_ref_find($all, (string) $ref)]['id'];
                if (in_array($id, $ids, true)) {
                    mcp_fail('Bu referans listede iki kez yazılmış: ' . mcp_clip((string) $ref, 80));
                }
                $ids[] = $id;
            }
            $r = refs_reorder($ids);
            if (!$r['ok']) {
                mcp_fail('Sıra kaydedilmedi: ' . implode(' ', $r['errors']));
            }
            return mcp_ok(['mesaj' => 'Referans sırası kaydedildi.', 'referanslar' => mcp_refs_public(refs_list())], 'Referans sırası kaydedildi.');
        });

    /* ---------- kurumsal listeler ---------- */

    $T[] = mcp_def('kurumsal_liste_guncelle', 'icerik', 'Kurumsal listeyi güncelle',
        'Sitenin ortak listelerinden birini TÜMÜYLE değiştirir (verdiğiniz içerik eskisinin yerine geçer; önce kurumsal_listeleri_getir ile mevcut halini okuyun ve değiştirmediğiniz öğeleri de yazın). Panelin Kurumsal içerik bölümündeki sekmelerle birebir aynı doğrulama ve sayı sınırları uygulanır; sayım sınırları sayfa metinlerine bağlıdır ("sekiz", "altı", "beş" yazar). Listeler ve biçimleri: '
        . mcp_list_help()
        . 'Ana sayfadaki kanun metni (hero_law) ve il listeleri bu araçla değiştirilemez (resmî metindir). Banka bilgilerini yalnızca kullanıcının verdiği doğru bilgiyle yazın; banks listesini değiştirmek için ayrıca "Ayarlar" izni gerekir.',
        sc_obj([
            'liste'  => sc_enum(array_keys(lists_keys()), 'Değiştirilecek liste: ' . implode('; ', array_map(fn($k, $v) => $k . ' = ' . $v, array_keys(lists_keys()), lists_keys())) . '.'),
            'icerik' => ['description' => 'Listenin yeni, tam içeriği (eskisinin yerine geçer). Biçim liste türüne göre değişir: process, timeline, principles, banks, goals için nesne listesi; mission ve vision için tek nesne; sektorler ve deneyim için metin listesi. kurumsal_listeleri_getir çıktısındaki aynı alanın değeri olduğu gibi geri yazılabilir. Ayrıntı aracın açıklamasında.'],
        ], ['liste', 'icerik']), [false, true, true], function (array $a, array $ctx): array {
            if ($a['liste'] === 'banks' && !in_array('ayarlar', $ctx['principal']['scopes'], true)) {
                mcp_fail('Banka hesaplarını (banks) değiştirmek için ayrıca "Ayarlar" izni gerekir; bu erişim anahtarında yok.');
            }
            [$raw, $shape] = mcp_list_input($a['liste'], $a['icerik']);
            if ($shape) {
                mcp_fail("Liste kaydedilmedi (biçim hatası):\n- " . implode("\n- ", $shape));
            }
            $r = lists_save([$a['liste'] => $raw]);
            if (!$r['ok']) {
                mcp_fail("Liste kaydedilmedi:\n- " . implode("\n- ", $r['errors']));
            }
            $changed = (bool) $r['changed'];
            $msg = $changed ? 'Liste güncellendi: ' . $a['liste'] . ' (' . lists_keys()[$a['liste']] . ').' : 'Değişiklik yok: liste zaten bu halde.';
            return mcp_ok(['mesaj' => $msg, 'liste' => $a['liste'], 'degisti' => $changed, 'guncel' => mcp_lists_public()[$a['liste']]], $msg);
        });

    /* ---------- e-posta gönderim ayarları ---------- */

    $T[] = mcp_def('eposta_ayarlarini_getir', 'ayarlar', 'E-posta ayarlarını getir',
        'Formlardan gelen bildirimlerin gönderim ayarlarını getirir: bildirimlerin gittiği adres, gönderen adresi ve adı, kayıtların panelde saklanıp saklanmadığı ve SMTP ayarları (sunucu, port, güvenlik, kullanıcı). SMTP şifresi hiçbir zaman verilmez; yalnızca kayıtlı olup olmadığı söylenir. Bülten e-postaları da bu ayarlarla gönderilir.',
        sc_obj(), [true, false, true], function (array $a): array {
            return mcp_ok(mcp_mail_public());
        });

    $T[] = mcp_def('eposta_ayarlarini_guncelle', 'ayarlar', 'E-posta ayarlarını güncelle',
        'Formlardan (iletişim, bülten, iş başvurusu) gelen bildirimlerin gönderim ayarlarını değiştirir. Yalnızca verdiğiniz alanlar değişir. DİKKAT: bildirim_adresi değişirse ziyaretçilerin kişisel verilerini içeren bildirimler o adrese gider; yalnızca kullanıcının açıkça verdiği adresi yazın ve onay alın. bildirim_adresi ya da kayitlari_sakla değiştirilirken ayrıca "Gelen kutusu" izni gerekir (bu iki alan ziyaretçilerin kişisel verilerinin nereye gittiğini belirler). SMTP üç durumdan birindedir (eposta_ayarlarini_getir smtp_kaynak): yapilandirma (panelde seçim yok; sunucudaki storage/config.local.php geçerli, şifresi panele ve içerik deposuna HİÇ kopyalanmaz), panel (bu araçla kaydedilmiş) ve kapali. SMTP alanlarına dokunmayan bir çağrı bu durumu değiştirmez. SMTP şifresi yalnızca yazılır, bir daha okunamaz ve işlem kaydına yazılmaz; yedek dosyalarına ve dışa aktarılan geçmişe girmez. SMTP sunucusu, portu, güvenlik türü ya da kullanıcı adı değişirken (ya da SMTP panelde yeni açılırken) smtp_sifre de aynı çağrıda verilmelidir; bunlar değişmiyorsa smtp_sifre verilmeden panelde kayıtlı şifre korunur. Değişiklikten sonra deneme_epostasi_gonder ile sınayın.',
        sc_obj([
            'bildirim_adresi' => sc_str('Form bildirimlerinin gideceği e-posta adresi.', ['maxLength' => 200]),
            'gonderen_adresi' => sc_str('Bildirimlerin "kimden" adresi (sitenin alan adına ait bir adres olmalı, örneğin noreply@...).', ['maxLength' => 200]),
            'gonderen_adi'    => sc_str('Bildirimlerde görünen gönderen adı, örneğin "Arslanlı Web Sitesi".', ['minLength' => 1, 'maxLength' => 200]),
            'kayitlari_sakla' => sc_bool('true: form gönderimleri panelde de saklanır (e-posta iletilemese bile kaybolmaz). false: yalnızca e-posta gönderilir.'),
            'smtp_acik'       => sc_bool('true: e-posta SMTP hesabıyla gönderilir (bilgiler bu araçla panele kaydedilir); false: SMTP panelde açıkça kapatılır, sunucunun kendi e-posta fonksiyonu kullanılır (storage/config.local.php içindeki SMTP de yok sayılır).'),
            'smtp_kaynak'     => sc_enum(['yapilandirma', 'panel', 'kapali'], 'SMTP\'nin hangi kaynaktan geleceği: yapilandirma = panelde seçim yok, sunucudaki storage/config.local.php ne diyorsa o (panelde kayıtlı SMTP bilgisi silinir); panel = bu araçla verilen bilgiler; kapali = SMTP kapalı. Verilmezse mevcut kaynak korunur.'),
            'smtp_sunucu'     => sc_str('SMTP sunucu adı, örneğin "mail.siteniz.com".', ['maxLength' => 200]),
            'smtp_port'       => sc_int('SMTP portu (SSL için genellikle 465, TLS için 587).', ['minimum' => 1, 'maximum' => 65535]),
            'smtp_guvenlik'   => sc_enum(['ssl', 'tls', 'none'], 'Bağlantı güvenliği: ssl (genellikle 465), tls (genellikle 587), none (genellikle 25).'),
            'smtp_kullanici'  => sc_str('SMTP kullanıcı adı (çoğu zaman e-posta adresinin kendisi).', ['maxLength' => 200]),
            'smtp_sifre'      => sc_str('SMTP şifresi. Yalnızca yazılır; hiçbir araç geri okumaz.', ['maxLength' => 200]),
        ]), [false, true, true], function (array $a, array $ctx): array {
            if (!$a) {
                mcp_fail('Değiştirilecek bir alan verilmedi.');
            }
            $cur = mcp_mail_public();
            $s = content_get('settings', []);
            if (!is_array($s)) {
                $s = [];
            }
            $st = smtp_state($s);
            $v = $cur;
            foreach (['bildirim_adresi', 'gonderen_adresi', 'gonderen_adi', 'smtp_sunucu', 'smtp_kullanici'] as $k) {
                if (array_key_exists($k, $a)) $v[$k] = mcp_line($a[$k]);
            }
            foreach (['kayitlari_sakla', 'smtp_acik'] as $k) {
                if (array_key_exists($k, $a)) $v[$k] = (bool) $a[$k];
            }
            if (array_key_exists('smtp_port', $a)) $v['smtp_port'] = (int) $a['smtp_port'];
            if (array_key_exists('smtp_guvenlik', $a)) $v['smtp_guvenlik'] = $a['smtp_guvenlik'];
            // SMTP durumu: yapılandırma dosyasından (miras) / panel / kapalı. SMTP alanlarına dokunulmadıysa durum değişmez.
            $smtpKeys = ['smtp_acik', 'smtp_kaynak', 'smtp_sunucu', 'smtp_port', 'smtp_guvenlik', 'smtp_kullanici', 'smtp_sifre'];
            $touch = (bool) array_intersect($smtpKeys, array_keys($a));
            $mode = $st['mode'];
            if (isset($a['smtp_kaynak'])) {
                $mode = ['yapilandirma' => 'miras', 'panel' => 'panel', 'kapali' => 'kapali'][$a['smtp_kaynak']];
            } elseif ($touch && array_key_exists('smtp_acik', $a) && !$a['smtp_acik']) {
                $mode = 'kapali';
            } elseif ($touch && !($mode === 'miras' && $st['inherited'] !== null && !array_diff(array_keys($a), ['smtp_acik']))) {
                $mode = 'panel';   // bilgi verildi (ya da kapalıyken açıldı): panelde kayıtlı olur; yalnızca "smtp_acik: true" yapılandırma dosyasındaki SMTP'yi zaten açık bırakır
            }
            if ($mode === 'panel') {
                $basis = $st['panel'] ?? $st['inherited'] ?? ['host' => '', 'port' => 465, 'secure' => 'ssl', 'user' => ''];
                foreach (['smtp_sunucu' => 'host', 'smtp_kullanici' => 'user'] as $ak => $bk) {
                    $v[$ak] = array_key_exists($ak, $a) ? mcp_line($a[$ak]) : $basis[$bk];
                }
                $v['smtp_port'] = array_key_exists('smtp_port', $a) ? (int) $a['smtp_port'] : $basis['port'];
                $v['smtp_guvenlik'] = array_key_exists('smtp_guvenlik', $a) ? $a['smtp_guvenlik'] : $basis['secure'];
            }
            $v['smtp_acik'] = $mode === 'panel' ? true : ($mode === 'kapali' ? false : $cur['smtp_acik']);
            // Bildirim adresi ve kayıtların saklanması kişisel verinin nereye gittiğini belirler: gelen kutusu izni de aranır
            if (($v['bildirim_adresi'] !== $cur['bildirim_adresi'] || $v['kayitlari_sakla'] !== $cur['kayitlari_sakla']) && !in_array('gelen_kutusu', $ctx['principal']['scopes'], true)) {
                mcp_fail('bildirim_adresi ya da kayitlari_sakla değiştirilirken ayrıca "Gelen kutusu" izni gerekir; bu erişim anahtarında yok. Bu iki alan ziyaretçilerin kişisel verilerinin nereye gittiğini belirler. Diğer alanları bu ikisi olmadan gönderebilirsiniz.');
            }
            $errors = [];
            // Panelle aynı sıkı denetim: adresler başlıklara ve gönderim komutlarına girer (tırnak, boşluk, ters eğik çizgi, satır sonu kabul edilmez)
            require_once APP . '/mailer.php';
            if (!mail_address_ok($v['bildirim_adresi'])) $errors[] = 'Bildirimlerin gideceği e-posta adresi geçerli değil.';
            if (!mail_address_ok($v['gonderen_adresi'])) $errors[] = 'Gönderen e-posta adresi geçerli değil.';
            if ($v['gonderen_adi'] === '') $errors[] = 'Gönderen adı boş olamaz. Örnek: Arslanlı Web Sitesi';
            if ($mode === 'panel' && ($v['smtp_sunucu'] === '' || !preg_match('/^[A-Za-z0-9.\-]+$/', $v['smtp_sunucu']))) $errors[] = 'SMTP açıkken sunucu adı gerekir (smtp_sunucu). Örnek: mail.siteniz.com';
            if (val_markup($v['gonderen_adi'])) $errors[] = val_markup_error('', 'Gönderen adı');
            if (val_markup($v['smtp_kullanici'])) $errors[] = val_markup_error('', 'SMTP kullanıcı adı');
            if ($errors) {
                mcp_fail("Ayarlar kaydedilmedi:\n- " . implode("\n- ", $errors));
            }
            $hasPass = array_key_exists('smtp_sifre', $a) && (string) $a['smtp_sifre'] !== '';
            // Panelde kayıtlı şifre yalnızca kaydedildiği sunucu, port, güvenlik türü ve kullanıcıyla kullanılır; biri değişirse şifre yeniden verilmelidir.
            // Yapılandırma dosyasındaki şifre panele hiç kopyalanmaz: oradan panele geçiş de şifrenin yeniden verilmesini gerektirir.
            $tuple = ['host' => $v['smtp_sunucu'], 'port' => $v['smtp_port'], 'secure' => $v['smtp_guvenlik'], 'user' => $v['smtp_kullanici']];
            $same = $st['panel'] !== null && $st['panel'] === $tuple;
            if ($mode === 'panel' && !$hasPass && $v['smtp_kullanici'] !== '' && !$same) {
                mcp_fail('SMTP sunucusu, portu, güvenlik türü ya da kullanıcı adı değişirken (ya da SMTP panele yeni kaydedilirken) smtp_sifre alanını da verin; kayıtlı ya da yapılandırma dosyasındaki şifre başka bir sunucuya gönderilmez ve içerik deposuna kopyalanmaz.');
            }
            $pass = $hasPass ? mb_substr((string) $a['smtp_sifre'], 0, 200) : ($same ? $st['panel_pass'] : '');
            $s['store_submissions'] = $v['kayitlari_sakla'];
            $s['mail'] = is_array($s['mail'] ?? null) ? $s['mail'] : [];
            $s['mail']['to'] = $v['bildirim_adresi'];
            $s['mail']['from'] = $v['gonderen_adresi'];
            $s['mail']['from_name'] = $v['gonderen_adi'];
            if ($mode === 'panel') {
                $s['mail']['smtp'] = $tuple + ['pass' => $pass];
            } elseif ($mode === 'kapali') {
                $s['mail']['smtp'] = null;
            } else {
                unset($s['mail']['smtp']);   // seçim yok: yapılandırma dosyası geçerli; içerik deposuna SMTP bilgisi ve şifre yazılmaz
            }
            if (!content_put('settings', $s)) {
                mcp_save_failed();
            }
            $now = mcp_mail_public();
            $changed = array_keys(array_filter($now, fn($x, $k) => $x !== $cur[$k], ARRAY_FILTER_USE_BOTH));
            if ($hasPass && $pass !== $st['panel_pass']) {
                $changed[] = 'smtp_sifre';
            }
            $msg = $changed ? 'E-posta ayarları güncellendi: ' . implode(', ', $changed) . '. deneme_epostasi_gonder ile sınayabilirsiniz.' : 'Değişiklik yok: ayarlar zaten bu halde.';
            return mcp_ok(['mesaj' => $msg, 'degisenler' => $changed, 'ayarlar' => $now], $msg);
        });

    $T[] = mcp_def('deneme_epostasi_gonder', 'ayarlar', 'Deneme e-postası gönder',
        'E-posta ayarlarının çalıştığını sınamak için bildirim adresine tek bir deneme e-postası gönderir (bülten e-postası DEĞİLDİR; bülten göndermek bu sunucudan yapılamaz). Dakikada en fazla bir kez gönderilebilir. Sonuç yalnızca sunucunun iletiyi kabul edip etmediğini söyler; iletinin gelen kutusuna (ya da spam klasörüne) düştüğünü kullanıcı kendisi kontrol etmelidir.',
        sc_obj(), [false, false, false], function (array $a): array {
            $to = mcp_mail_public()['bildirim_adresi'];
            require_once APP . '/mailer.php';
            if (!mail_address_ok($to)) {
                mcp_fail('Bildirim adresi geçerli değil. Önce eposta_ayarlarini_guncelle ile düzeltin.');
            }
            $wait = mcp_json_update('state', function (array &$d): int {
                $left = (int) ($d['test_mail_at'] ?? 0) + 60 - time();
                if ($left <= 0) {
                    $d['test_mail_at'] = time();
                }
                return max(0, $left);
            });
            if ($wait > 0) {
                mcp_fail('Az önce bir deneme e-postası gönderildi. ' . $wait . ' saniye sonra tekrar deneyin.');
            }
            $ok = send_mail($to, 'Deneme e-postası: ' . cfg('name'),
                "Merhaba,\n\nBu ileti, web sitesinin yapay zekâ erişimi üzerinden gönderilen bir deneme e-postasıdır.\n"
                . "Bu iletiyi aldıysanız sitedeki formlardan gelen bildirimler de bu adrese ulaşacaktır.\n\n"
                . 'Gönderim zamanı: ' . date('d.m.Y H:i') . "\n");
            if (!$ok) {
                mcp_fail('E-posta gönderilemedi. SMTP sunucusu, port, güvenlik türü, kullanıcı adı ve şifreyi kontrol edin (eposta_ayarlarini_getir). SMTP kapalıysa barındırma firması sunucunun e-posta fonksiyonunu kapatmış olabilir; bu durumda SMTP bilgilerini girin.');
            }
            $msg = 'Deneme e-postası ' . $to . ' adresine gönderildi. Birkaç dakika içinde gelen kutusuna ya da spam klasörüne düşmelidir; kullanıcıdan kontrol etmesini isteyin.';
            return mcp_ok(['mesaj' => $msg, 'alici' => $to], $msg);
        });

    /* ---------- gelen kutusu ---------- */

    $T[] = mcp_def('form_kaydi_sil', 'gelen_kutusu', 'Form kaydını sil',
        'İletişim ya da bülten (Haberdar ol) formundan gelen bir kaydı KALICI olarak siler; geri alınamaz. Bülten onayını geri alan ya da verisinin silinmesini isteyen kişinin kaydı için kullanılır (o adresin başka bülten kaydı kalmadıysa ayrılanlar listesindeki ve gönderim kayıtlarındaki izleri de silinir). Yalnızca kullanıcı açıkça isterse ve hangi kayıt olduğunu doğruladıktan sonra kullanın. id değeri form_kayitlari sonucundan alınır. Bildirim e-postasının posta kutusundaki kopyası bu araçla silinmez; kullanıcıya hatırlatın.',
        sc_obj(['id' => sc_str('Kaydın kimliği (form_kayitlari sonucundaki "id").', ['pattern' => '^[a-f0-9]{12}$', 'x-ipucu' => '12 karakterlik kimlik'])], ['id']),
        [false, true, true], function (array $a): array {
            $r = adm_form_record_delete($a['id']);
            if ($r === null) {
                mcp_fail('Bu kimlikte bir form kaydı yok: ' . $a['id'] . '. (Zaten silinmiş olabilir; iş başvuruları için is_basvurusu_sil kullanılır.) Kimlikler form_kayitlari ile görülür.');
            }
            $msg = 'Form kaydı kalıcı olarak silindi. Bildirim e-postasının posta kutusundaki kopyası ayrıca silinmelidir.';
            return mcp_ok(['mesaj' => $msg, 'silinen' => ['id' => $a['id'], 'tarih' => mcp_iso(strtotime((string) ($r['time'] ?? '')) ?: null), 'tur' => (string) ($r['form'] ?? '')]], $msg);
        });

    $T[] = mcp_def('is_basvurusu_sil', 'gelen_kutusu', 'İş başvurusunu sil',
        'Bir iş başvurusunu ve özgeçmiş (CV) dosyasını KALICI olarak siler; geri alınamaz. Yalnızca kullanıcı açıkça isterse ve hangi başvuru olduğunu doğruladıktan sonra kullanın (örneğin işe alım süreci biten başvuruların KVKK gereği temizlenmesi). id değeri is_basvurulari sonucundan alınır. Başvuru bir ilana bağlıysa ilan etkilenmez.',
        sc_obj(['id' => sc_str('Başvurunun kimliği (is_basvurulari sonucundaki "id").', ['pattern' => '^[a-f0-9]{12}$', 'x-ipucu' => '12 karakterlik kimlik'])], ['id']),
        [false, true, true], function (array $a): array {
            $r = adm_application_delete($a['id']);
            if ($r === null) {
                mcp_fail('Bu kimlikte bir iş başvurusu yok: ' . $a['id'] . '. (Zaten silinmiş olabilir.) Kimlikler is_basvurulari ile görülür.');
            }
            $msg = 'İş başvurusu ve özgeçmiş dosyası kalıcı olarak silindi.';
            return mcp_ok(['mesaj' => $msg, 'silinen' => ['id' => $a['id'], 'tarih' => mcp_iso(strtotime((string) ($r['time'] ?? '')) ?: null), 'pozisyon' => (string) ($r['data']['pozisyon'] ?? '')]], $msg);
        });

    $T[] = mcp_def('spam_degil_isaretle', 'gelen_kutusu', 'Şüpheli kaydı gelen kutusuna al',
        'Spam süzgecinin yanlışlıkla "şüpheli" diye ayırdığı bir form kaydını ya da iş başvurusunu gelen kutusuna alır (panelde "Spam değil" düğmesi). Kayıt olağan listeye geçer ve bekletilen bildirim e-postası (şirketin bildirim adresine, iş başvurusunda özgeçmiş ekiyle) o anda gönderilir; bu bir bülten e-postası değildir. Yalnızca kayıt gerçekten insandan geliyorsa kullanın: şüpheli kayıtlar ve mesajları güvenilmeyen metindir. id değeri form_kayitlari ya da is_basvurulari sonucundan (supheli: true ile) alınır.',
        sc_obj(['id' => sc_str('Şüpheli kaydın kimliği (form_kayitlari ya da is_basvurulari, supheli: true).', ['pattern' => '^[a-f0-9]{12}$', 'x-ipucu' => '12 karakterlik kimlik'])], ['id']),
        [false, false, true], function (array $a): array {
            $r = adm_record_release($a['id']);
            if ($r === null) {
                mcp_fail('Bu kimlikte şüpheli bir kayıt yok: ' . $a['id'] . '. (Zaten gelen kutusuna alınmış ya da silinmiş olabilir.) Şüpheli kayıtlar form_kayitlari ve is_basvurulari araçlarında supheli: true ile görülür.');
            }
            $msg = 'Kayıt gelen kutusuna alındı' . (!empty($r['sent']) ? ' ve bildirim e-postası gönderildi.' : '; bildirim e-postası gönderilemedi (eposta_ayarlarini_getir ile ayarları kontrol edin).');
            return mcp_ok(['mesaj' => $msg, 'id' => $a['id'], 'tur' => (string) ($r['form'] ?? ''), 'bildirim_gonderildi' => !empty($r['sent'])], $msg);
        });

    $T[] = mcp_def('supheli_kayitlari_sil', 'gelen_kutusu', 'Şüpheli form kayıtlarının hepsini sil',
        'Spam süzgecinin şüpheli diye ayırdığı iletişim ve bülten kayıtlarının HEPSİNİ KALICI olarak siler; geri alınamaz (panelde "Şüphelilerin hepsini sil"). İş başvurularına dokunmaz; şüpheli iş başvurusu is_basvurusu_sil ile tek tek silinir. Şüpheli kayıtlar 30 gün sonra zaten kendiliğinden silinir; yalnızca kullanıcı açıkça isterse kullanın ve önce form_kayitlari (supheli: true) ile sayısını gösterip onay alın. Gerçek bir kişinin kaydı yanlışlıkla şüpheli sayılmış olabilir: emin değilseniz önce spam_degil_isaretle ile kurtarın.',
        sc_obj(['onay' => sc_bool('true: şüpheli form kayıtlarının hepsinin kalıcı olarak silinmesini kullanıcıdan onay alarak istiyorum.')], ['onay']),
        [false, true, true], function (array $a): array {
            if (empty($a['onay'])) {
                mcp_fail('Silme için onay: true verilmelidir. Önce kaç şüpheli kayıt olduğunu form_kayitlari (supheli: true) ile görüp kullanıcıdan onay alın.');
            }
            $n = adm_spam_delete_all();
            $msg = $n ? $n . ' şüpheli form kaydı kalıcı olarak silindi.' : 'Silinecek şüpheli form kaydı yok.';
            return mcp_ok(['mesaj' => $msg, 'silinen_adet' => $n], $msg);
        });

    return $T;
}
