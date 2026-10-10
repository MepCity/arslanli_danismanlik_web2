<?php
declare(strict_types=1);

/**
 * Geri yükleme doğrulaması
 * ---------------------------------------------------------------------------
 * Yedekten geri yükleme (panel › Güvenlik ve yedek), değişiklik geçmişinden geri alma (panel › Değişiklik geçmişi) ve yapay zekâ erişiminin geri_al aracı
 * içerik dosyasını ham olarak yazmaz: her depo, normal bir kayıtla AYNI temizleme ve doğrulamadan geçer.
 *
 *   services   hizmet başına service_clean + service_errors (HTML işareti yok, uzunluk ve sayı sınırları, adres biçimi)
 *   posts      yazı başına başlık/özet/kategori/tarih/görsel yolu denetimi; gövde sanitize_html() ile yeniden süzülür
 *   refs       referans başına ad (val_len) ve görsel yolu (yalnızca temanın ve uploads/refs, refs-ink klasörleri)
 *   lists      liste başına list_clean + list_errors; bilinmeyen anahtar atılır
 *   texts      texts_sanitize_all; legal: legal_sanitize_all
 *   features   yalnızca bilinen bölümler ve doğru/yanlış değerleri
 *   settings   yalnızca izin verilen alanlar, panelle aynı biçim denetimleri; SMTP şifresi hiçbir zaman geri yüklenmez (bkz. restore_settings_clean)
 *   seo        yalnızca bilinen alanlar; doğrulama kodu ve IndexNow anahtarı biçim denetiminden geçer
 *   duyurular  duyuru başına ann_validate (ann_clean_restore); ilanlar: ilan_validate (ilan_clean_restore)
 *
 * Geçmeyen öğenin akıbeti (karar): geçerli olanlar korunur, geçmeyen öğe ya da alan atılır ve nedeni yöneticiye söylenir; hiç geçerli öğe kalmayan
 * (ya da bir sayı alt sınırının, örneğin en az 3 hizmetin altına düşen) depo hiç yazılmaz, olduğu gibi kalır.
 * Rapor restore_report() ile okunur.
 */

/** Geri yüklenebilen içerik depoları (storage/content/{anahtar}.json). */
function restore_stores(): array
{
    return ['services', 'posts', 'refs', 'lists', 'texts', 'legal', 'features', 'settings', 'seo'];
}

/**
 * Son geri yükleme işleminin raporu: ['key' => ..., 'dropped' => [atılan öğeler], 'notes' => [bilgi], 'error' => ?neden].
 * İşlemi çağıran (panel ya da MCP) iletisine ekler.
 */
function restore_report(?array $set = null): array
{
    static $r = ['key' => '', 'dropped' => [], 'notes' => [], 'error' => null];
    if ($set !== null) {
        $r = $set + ['key' => '', 'dropped' => [], 'notes' => [], 'error' => null];
    }
    return $r;
}

/** Rapor satırlarını kısa bir metne çevirir (en çok $max satır, kalanı sayı olarak). */
function restore_report_text(array $items, int $max = 6): string
{
    $shown = array_slice($items, 0, $max);
    $more  = count($items) - count($shown);
    return implode(' ', $shown) . ($more > 0 ? ' … ve ' . $more . ' öğe daha.' : '');
}

/** Güvenli adres (slug) biçimi. */
function restore_slug_ok($s, int $min = 1, int $max = 80): bool
{
    return is_string($s) && mb_strlen($s) >= $min && mb_strlen($s) <= $max && preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/D', $s) === 1;
}

/**
 * Boş (ör. {} ya da []) bir dosya: yedekten geri yüklemede "bir şey yok" demektir ve atlanır (mevcut hal korunur; aksi halde boş bir dosya görünürlüğü
 * ya da ayarları sessizce varsayılana döndürürdü). Geçmiş sürümünde ise boş hal geçerlidir ("özgün hale dön": ilk sürümler boştur).
 */
function restore_empty_ok(array &$rep, string $label): bool
{
    if (!empty($rep['history'])) {
        return true;
    }
    $rep['error'] = $label . ' dosyası boş; hiçbir şey geri yüklenmedi, mevcut hali korundu.';
    return false;
}

/* ---------- Hizmetler ---------- */

function restore_services_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'Hizmetler dosyası geçerli bir liste değil.';
        return null;
    }
    $L = service_limits();
    $out = [];
    foreach ($data as $slug => $x) {
        $slug = (string) $slug;
        $w = 'Hizmet «' . mb_substr($slug, 0, 40) . '»';
        if (!restore_slug_ok($slug, ...$L['slug']) || !is_array($x)) {
            $rep['dropped'][] = $w . ': adresi ya da biçimi geçersiz.';
            continue;
        }
        $svc  = service_clean($x);
        $errs = service_errors($svc);
        if ($errs) {
            $rep['dropped'][] = $w . ': ' . $errs[0];
            continue;
        }
        if (count($out) >= $L['services'][1]) {
            $rep['dropped'][] = $w . ': en fazla ' . $L['services'][1] . ' hizmet olabilir.';
            continue;
        }
        $out[$slug] = $svc;
    }
    if (count($out) < $L['services'][0]) {   // boş dosya da dahil: hizmetsiz site kabul edilmez (sayfalar ve menü en az bu sayıya göre kurulu)
        $rep['error'] = ($data ? 'Geçerli hizmet sayısı ' . count($out) : 'Hizmetler dosyası boş') . '; en az ' . $L['services'][0] . ' hizmet olmalı. Hizmetler geri yüklenmedi, mevcut hizmetler korundu.';
        return null;
    }
    return services_renumber($out);
}

/* ---------- Yazılar ---------- */

/** Yazı görseli yolu: boş, tema görselinin adı ("img/blog/{ad}.webp"), ya da yüklenmiş görsel ("uploads/blog/..."). */
function restore_post_image_ok($img): bool
{
    return is_string($img) && ($img === '' || preg_match('#^[A-Za-z0-9_-]{1,64}$#D', $img) === 1 || preg_match('#^uploads/blog/[A-Za-z0-9_-]{1,100}\.(webp|jpe?g|png)$#D', $img) === 1);
}

function restore_posts_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'Yazılar dosyası geçerli bir liste değil.';
        return null;
    }
    $out = [];
    foreach ($data as $slug => $p) {
        $slug = (string) $slug;
        $w = 'Yazı «' . mb_substr($slug, 0, 40) . '»';
        if (!restore_slug_ok($slug, 1, 80) || !is_array($p)) {
            $rep['dropped'][] = $w . ': adresi ya da biçimi geçersiz.';
            continue;
        }
        $post = [
            'title'    => val_line($p['title'] ?? '', 160),
            'category' => val_line($p['category'] ?? 'Genel', 40) ?: 'Genel',
            'date'     => is_string($p['date'] ?? null) ? $p['date'] : '',
            'image'    => $p['image'] ?? '',
            'excerpt'  => val_line($p['excerpt'] ?? '', 300),
            'body'     => '',
        ];
        $err = null;
        if ($post['title'] === '') $err = 'başlık boş.';
        elseif ($post['excerpt'] === '') $err = 'kısa özet boş.';
        elseif (($m = post_markup_errors($post)) !== []) $err = $m[0];
        elseif (!($dt = DateTime::createFromFormat('Y-m-d', $post['date'])) || $dt->format('Y-m-d') !== $post['date']) $err = 'yayın tarihi geçersiz.';
        elseif (!preg_match('/^[\p{L}\p{N} \-]+$/u', $post['category']) || slugify($post['category']) === '') $err = 'kategori adı geçersiz.';
        elseif (!restore_post_image_ok($post['image'])) $err = 'görsel yolu geçersiz.';
        elseif (!is_string($p['body'] ?? null) || mb_strlen($p['body']) > 200000) $err = 'gövde yok ya da çok uzun.';
        if ($err === null) {
            $post['body'] = sanitize_html($p['body']);   // zengin alan: kayıttaki süzgeçle yeniden süzülür (betik, olay işleyicisi, tehlikeli bağlantı atılır)
            if (trim(strip_tags($post['body'])) === '') $err = 'gövde boş (izin verilen etiketler dışındaki içerik süzgeçte atılır).';
        }
        if ($err !== null) {
            $rep['dropped'][] = $w . ': ' . $err;
            continue;
        }
        if (!empty($p['draft'])) {
            $post['draft'] = true;
        }
        $out[$slug] = $post;
    }
    if ($data && !$out) {
        $rep['error'] = 'Hiçbir yazı geçerli değil. Yazılar geri yüklenmedi.';
        return null;
    }
    return $out;
}

/* ---------- Referanslar ---------- */

function restore_refs_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'Referanslar dosyası geçerli bir liste değil.';
        return null;
    }
    $L = refs_limits();
    $out = [];
    $seen = [];
    foreach (array_values($data) as $n => $r) {
        $w = 'Referans ' . ($n + 1);
        $name = is_array($r) ? val_line($r['name'] ?? '', 200) : '';
        $err = null;
        if (!is_array($r)) $err = 'biçimi geçersiz.';
        elseif (($m = val_len('', 'Kurum adı', $name, ...$L['name'])) !== null) $err = $m;
        elseif (!restore_slug_ok($r['id'] ?? null, 1, 60)) $err = 'kodu geçersiz.';
        elseif (isset($seen[$r['id']])) $err = 'kodu tekrarlı.';
        elseif (!is_string($r['logo'] ?? null) || !preg_match('#^(img/refs/[a-z0-9_-]{1,80}\.webp|uploads/refs/[A-Za-z0-9_-]{1,100}\.(webp|jpe?g|png))$#D', $r['logo'])) $err = 'logo yolu geçersiz.';
        elseif (isset($r['ink']) && $r['ink'] !== '' && (!is_string($r['ink']) || !preg_match('#^(img/refs/ink/[a-z0-9_-]{1,80}\.webp|uploads/refs-ink/[A-Za-z0-9_-]{1,100}\.webp)$#D', $r['ink']))) $err = 'kaşe yolu geçersiz.';
        elseif (count($out) >= $L['count'][1]) $err = 'en fazla ' . $L['count'][1] . ' referans olabilir.';
        if ($err !== null) {
            $rep['dropped'][] = $w . ($name !== '' ? ' («' . mb_substr($name, 0, 40) . '»)' : '') . ': ' . $err;
            continue;
        }
        $seen[$r['id']] = true;
        $out[] = ['id' => $r['id'], 'name' => $name, 'logo' => $r['logo'], 'ink' => (string) ($r['ink'] ?? '')];
    }
    if (count($out) < $L['count'][0]) {   // boş dosya da dahil
        $rep['error'] = ($data ? 'Geçerli referans sayısı ' . count($out) : 'Referanslar dosyası boş') . '; en az ' . $L['count'][0] . ' referans olmalı. Referanslar geri yüklenmedi, mevcut referanslar korundu.';
        return null;
    }
    return $out;
}

/* ---------- Kurumsal listeler ---------- */

function restore_lists_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'Kurumsal listeler dosyası geçerli değil.';
        return null;
    }
    if (!$data && !restore_empty_ok($rep, 'Kurumsal listeler')) {
        return null;
    }
    $out = [];
    foreach ($data as $k => $raw) {
        $k = (string) $k;
        if (!isset(lists_keys()[$k])) {
            $rep['dropped'][] = 'Liste «' . mb_substr($k, 0, 40) . '»: bilinmeyen liste.';
            continue;
        }
        $c = list_clean($k, $raw);
        if ($k === 'goals') {
            $c = restore_goals_prune($c, $rep);   // olmayan hizmetlere işaret eden hedefler ayıklanır; geri kalan liste kaydedilebilsin
        }
        $errs = list_errors($k, $c);
        if ($errs) {
            $rep['dropped'][] = 'Liste ' . $k . ' (' . lists_keys()[$k] . '): ' . $errs[0];
            continue;
        }
        $out[$k] = $c;
    }
    if ($data && !$out) {
        $rep['error'] = 'Hiçbir liste geçerli değil. Kurumsal listeler geri yüklenmedi.';
        return null;
    }
    return $out;
}

/**
 * Hedef eşleştirici: artık var olmayan hizmet adreslerini hedeflerden çıkarır, hizmeti kalmayan hedefi atar (aksi halde liste, Kurumsal içerikte elle düzeltilene
 * kadar kaydedilemez). Yapılan ayıklama $rep['notes'] içinde söylenir.
 */
function restore_goals_prune(array $goals, array &$rep): array
{
    $known = services();
    $out = [];
    $cut = 0;
    $gone = 0;
    foreach ($goals as $g) {
        $keep = array_values(array_filter((array) ($g['services'] ?? []), fn($sl) => isset($known[$sl])));
        $cut += count((array) ($g['services'] ?? [])) - count($keep);
        if (!$keep) {
            $gone++;
            continue;
        }
        $out[] = ['label' => $g['label'], 'services' => $keep];
    }
    if ($cut || $gone) {
        $rep['notes'][] = 'Hedef eşleştirici: artık var olmayan hizmetlere bağlı ' . $cut . ' bağlantı çıkarıldı' . ($gone ? ', hizmeti kalmayan ' . $gone . ' hedef atıldı' : '') . '.';
    }
    return $out;
}

/** Hizmetler geri yüklendikten sonra kayıtlı hedef eşleştiriciyi güncel hizmetlere göre ayıklar (hedefler az kalırsa kayıt silinir, varsayılan hedefler kullanılır). */
function restore_goals_after_services(array &$rep): void
{
    $lists = content_get('lists', []);
    if (!is_array($lists) || !isset($lists['goals']) || !is_array($lists['goals'])) {
        return;
    }
    $pruned = restore_goals_prune(list_clean('goals', $lists['goals']), $rep);
    if ($pruned === list_clean('goals', $lists['goals'])) {
        return;
    }
    if (list_errors('goals', $pruned)) {
        unset($lists['goals']);
        $rep['notes'][] = 'Hedef eşleştiricide geçerli hedef kalmadığı için kayıtlı liste kaldırıldı; varsayılan hedefler kullanılır. Kurumsal içerik > Hizmetler sayfasındaki hedef eşleştiriciyi gözden geçirin.';
    } else {
        $lists['goals'] = $pruned;
    }
    content_put('lists', $lists);
    $GLOBALS['site'] = lists_effective();
}

/* ---------- Görünürlük ---------- */

function restore_features_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'Görünürlük dosyası geçerli değil.';
        return null;
    }
    if (!$data && !restore_empty_ok($rep, 'Görünürlük')) {
        return null;
    }
    $out = [];
    foreach ($data as $k => $v) {
        $k = (string) $k;
        if (!array_key_exists($k, features_defaults()) || !is_bool($v)) {
            $rep['dropped'][] = 'Görünürlük «' . mb_substr($k, 0, 40) . '»: bilinmeyen bölüm ya da değer.';
            continue;
        }
        $out[$k] = $v;
    }
    if ($data && !$out) {
        $rep['error'] = 'Görünürlük dosyasında geçerli bir anahtar yok. Geri yüklenmedi.';
        return null;
    }
    return $out;
}

/* ---------- SEO ---------- */

function restore_seo_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'SEO ayarları dosyası geçerli değil.';
        return null;
    }
    if (!$data && !restore_empty_ok($rep, 'SEO ayarları')) {
        return null;
    }
    $out = [];
    $code = fn($v) => is_string($v) && ($v === '' || preg_match('/^[A-Za-z0-9_\-:.=]{4,120}$/D', $v) === 1);
    foreach ($data as $k => $v) {
        $k = (string) $k;
        if ($k === 'verify' && is_array($v)) {
            foreach ($v as $vk => $vv) {
                if (in_array((string) $vk, ['google', 'bing', 'yandex'], true) && $code($vv)) {
                    $out['verify'][(string) $vk] = $vv;
                } else {
                    $rep['dropped'][] = 'SEO doğrulama kodu «' . mb_substr((string) $vk, 0, 20) . '»: geçersiz.';
                }
            }
        } elseif (in_array($k, ['indexnow', 'ai_search', 'ai_training'], true) && is_bool($v)) {
            $out[$k] = $v;
        } elseif ($k === 'indexnow_key' && is_string($v) && ($v === '' || preg_match('/^[A-Za-z0-9\-]{8,128}$/D', $v) === 1)) {
            $out[$k] = $v;
        } else {
            $rep['dropped'][] = 'SEO ayarı «' . mb_substr($k, 0, 30) . '»: bilinmeyen alan ya da geçersiz değer.';
        }
    }
    if ($data && !$out) {
        $rep['error'] = 'SEO ayarları dosyasında geçerli bir alan yok. Geri yüklenmedi.';
        return null;
    }
    // Arama motoru doğrulama kodları ve IndexNow anahtarı sitenin kime ait olduğunu söyler: yedekten geri yüklemede yönetici açıkça istemedikçe
    // (restore formundaki kutu) şu anki değerler kalır; geçmiş sürümü geri alınırken değişir (yedekten gelen geçmiş sürümleri alınırken aynı kural uygulanır).
    $codesOk = (!empty($rep['history']) && empty($rep['import'])) || !empty($rep['seo_codes']);
    if (!$codesOk) {
        $cur = (array) content_get('seo', []);
        foreach (['verify' => 'Arama motoru doğrulama kodları', 'indexnow_key' => 'IndexNow anahtarı'] as $k => $label) {
            if (($out[$k] ?? null) != ($cur[$k] ?? null)) {
                $rep['notes'][] = $label . ' yedekteki değerle değiştirilmedi, mevcut hali korundu; değiştirmek için geri yüklerken “Arama motoru doğrulama kodlarını ve IndexNow anahtarını da geri yükle” kutusunu işaretleyin.';
            }
            unset($out[$k]);
            if (isset($cur[$k])) {
                $out[$k] = $cur[$k];
            }
        }
    }
    return $out;
}

/* ---------- Ayarlar (iletişim, şirket, e-posta) ---------- */

/**
 * İletişim ve şirket ayarları: yalnızca izin verilen alanlar, panelle aynı biçim denetimleri; geçmeyen alan atılır (varsayılan geçerli olur).
 * SMTP: geri yüklenen veriden ŞİFRE HİÇBİR ZAMAN ALINMAZ (yedeğe ve dışa aktarılan geçmişe girmez; eski sürümlerde yerel dosyadan kopyalanmış olabilir).
 *   - 'smtp' anahtarı yoksa: seçim yok (yapılandırma dosyası geçerli).
 *   - null: SMTP açıkça kapalı.
 *   - bağlantı bilgisi panelde şu an kayıtlı olanla aynıysa: şu anki panel şifresi korunur.
 *   - bağlantı bilgisi yapılandırma dosyasındakiyle aynıysa: seçim yok sayılır (şifre dosyada zaten var).
 *   - başka bir bağlantı bilgisiyse: şifresiz geri yüklenemez; şu anki SMTP durumu olduğu gibi kalır (yönetici panelden yeniden yazar).
 */
function restore_settings_clean($data, array &$rep): ?array
{
    if (!is_array($data)) {
        $rep['error'] = 'Ayarlar dosyası geçerli değil.';
        return null;
    }
    if (!$data && !restore_empty_ok($rep, 'Ayarlar')) {
        return null;
    }
    require_once APP . '/mailer.php';
    $cur = (array) content_get('settings', []);
    $out = [];
    $drop = function (string $field, string $why) use (&$rep): void {
        $rep['dropped'][] = 'Ayar «' . $field . '»: ' . $why;
    };
    $plain = function ($v, int $max): ?string {
        $s = val_line($v, $max + 1);
        return $s !== '' && mb_strlen($s) <= $max && !val_markup($s) ? $s : null;
    };
    foreach (['name' => 120, 'address' => 400, 'address_short' => 80] as $k => $max) {
        if (!array_key_exists($k, $data)) continue;
        ($v = $plain($data[$k], $max)) !== null ? $out[$k] = $v : $drop($k, 'boş, çok uzun ya da HTML içeriyor.');
    }
    if (array_key_exists('phone', $data)) {
        $v = val_line($data['phone'], 40);
        $digits = (string) preg_replace('/\D+/', '', $v);
        preg_match('/^[0-9+()\s.\-]+$/', $v) && strlen($digits) >= 10 && strlen($digits) <= 15 ? $out['phone'] = $v : $drop('phone', 'geçerli bir telefon numarası değil.');
    }
    if (array_key_exists('whatsapp', $data)) {
        $v = (string) preg_replace('/[\s+\-()]+/', '', val_line($data['whatsapp'], 40));
        preg_match('/^[1-9][0-9]{9,14}$/', $v) ? $out['whatsapp'] = $v : $drop('whatsapp', 'geçerli bir WhatsApp numarası değil.');
    }
    if (array_key_exists('email', $data)) {
        $v = val_line($data['email'], 120);
        mail_address_ok($v) ? $out['email'] = $v : $drop('email', 'geçerli bir e-posta adresi değil.');
    }
    if (array_key_exists('maps_url', $data)) {
        $v = val_line($data['maps_url'], 500);
        settings_url_ok($v) ? $out['maps_url'] = $v : $drop('maps_url', 'https:// ile başlayan geçerli bir adres değil.');
    }
    if (array_key_exists('store_submissions', $data)) {
        is_bool($data['store_submissions']) ? $out['store_submissions'] = $data['store_submissions'] : $drop('store_submissions', 'doğru/yanlış değil.');
    }
    if (is_array($data['company'] ?? null)) {
        foreach (['authorized' => 120, 'tax_office' => 80, 'tax_number' => 30] as $k => $max) {
            if (!array_key_exists($k, $data['company'])) continue;
            ($v = $plain($data['company'][$k], $max)) !== null ? $out['company'][$k] = $v : $drop('company.' . $k, 'boş, çok uzun ya da HTML içeriyor.');
        }
    }
    if (is_array($data['social'] ?? null)) {
        foreach (['Instagram', 'LinkedIn', 'Facebook', 'X'] as $net) {
            if (!array_key_exists($net, $data['social'])) continue;
            $v = val_line($data['social'][$net], 500);
            $v === '' || settings_url_ok($v) ? $out['social'][$net] = $v : $drop('social.' . $net, 'https:// ile başlayan geçerli bir adres değil.');
        }
    }
    // Bildirim ve gönderen adresi, ziyaretçilerin kişisel verilerinin e-postayla nereye gideceğini belirler: yedekten geri yüklemede yönetici açıkça
    // istemedikçe (restore formundaki kutu) şu anki değerler korunur; geçmiş sürümü geri alınırken değişir ama sonuçta eski ve yeni adres yazılır.
    $routesOk = (!empty($rep['history']) && empty($rep['import'])) || !empty($rep['mail_routes']);   // 'import': yedekten gelen geçmiş sürümü (restore_history_clean)
    $effBefore = settings_apply(config_inherited(), $cur)['mail'];
    if (is_array($data['mail'] ?? null)) {
        $m = $data['mail'];
        foreach (['to', 'from'] as $k) {
            if (!array_key_exists($k, $m)) continue;
            $v = val_line($m[$k], 120);
            if (!mail_address_ok($v)) { $drop('mail.' . $k, 'geçerli bir e-posta adresi değil.'); continue; }
            if ($routesOk) {
                $out['mail'][$k] = $v;
            } elseif ($v !== (string) ($effBefore[$k] ?? '')) {
                $rep['notes'][] = ($k === 'to' ? 'Bildirimlerin gideceği adres' : 'Gönderen adresi') . ' yedekteki değerle (' . $v . ') değiştirilmedi, mevcut hali (' . ($effBefore[$k] ?? '') . ') korundu; değiştirmek için geri yüklerken “Bildirim ve gönderen adreslerini de geri yükle” kutusunu işaretleyin.';
            }
        }
        if (array_key_exists('from_name', $m)) {
            ($v = $plain($m['from_name'], 80)) !== null ? $out['mail']['from_name'] = $v : $drop('mail.from_name', 'boş, çok uzun ya da HTML içeriyor.');
        }
        if (array_key_exists('smtp', $m)) {
            $st = smtp_state($cur);
            if ($m['smtp'] === null || (is_array($m['smtp']) && empty($m['smtp']['host']))) {
                $out['mail']['smtp'] = null;
            } elseif (is_array($m['smtp'])) {
                $x = $m['smtp'];
                $host = val_line($x['host'] ?? '', 120);
                $port = is_numeric($x['port'] ?? null) ? (int) $x['port'] : 0;
                $sec  = (string) ($x['secure'] ?? '');
                $user = val_line($x['user'] ?? '', 160);
                if (!preg_match('/^[A-Za-z0-9.\-]+$/D', $host) || $port < 1 || $port > 65535 || !in_array($sec, ['ssl', 'tls', 'none'], true) || val_markup($user)) {
                    $drop('mail.smtp', 'bağlantı bilgisi geçersiz; şu anki SMTP durumu korundu.');
                    if (array_key_exists('smtp', (array) ($cur['mail'] ?? []))) $out['mail']['smtp'] = $cur['mail']['smtp'];
                } else {
                    $t = ['host' => $host, 'port' => $port, 'secure' => $sec, 'user' => $user];
                    if ($st['panel'] === $t) {
                        $out['mail']['smtp'] = $t + ['pass' => $st['panel_pass']];
                    } elseif ($st['inherited'] === $t) {
                        $rep['notes'][] = 'SMTP: yedekteki bağlantı bilgisi sunucudaki yapılandırma dosyasındakiyle aynı; panelde seçim yok sayıldı (şifre dosyada kalır).';
                    } else {
                        $rep['notes'][] = 'SMTP şifresi yedeğe ve geçmişe girmediği için SMTP ayarı geri yüklenmedi; şu anki SMTP durumu korundu. Gerekirse İletişim ve şirket sayfasında SMTP’yi şifresiyle yeniden yazın.';
                        if (array_key_exists('smtp', (array) ($cur['mail'] ?? []))) $out['mail']['smtp'] = $cur['mail']['smtp'];
                    }
                }
            }
        }
    }
    if (!$routesOk) {
        // yedekteki adresler alınmadı: mevcut kayıtlı değerler yeni dosyada da kalsın (yoksa varsayılan geçerli olur, o da değişmez)
        foreach (['to', 'from'] as $k) {
            if (isset($cur['mail'][$k]) && is_string($cur['mail'][$k])) $out['mail'][$k] = $cur['mail'][$k];
        }
    } else {
        $after = settings_apply(config_inherited(), $out)['mail'];
        foreach (['to' => 'Bildirimlerin gideceği adres', 'from' => 'Gönderen adresi'] as $k => $label) {
            if ((string) ($effBefore[$k] ?? '') !== (string) ($after[$k] ?? '')) {
                $rep['notes'][] = $label . ' DEĞİŞTİ: ' . ($effBefore[$k] ?? '') . ' → ' . ($after[$k] ?? '') . '. Formlardan gelen bildirimler artık bu adrese gider.';
            }
        }
    }
    if ($data && !$out) {
        $rep['error'] = 'Ayarlar dosyasında geçerli bir alan yok. Geri yüklenmedi.';
        return null;
    }
    return $out;
}

/** Ayarlar dosyasından (yedek ya da dışa aktarılan geçmiş sürümü) SMTP şifresini çıkarır. @return array{0:string, 1:bool} [metin, şifre vardı mı] */
function settings_strip_secrets(string $json): array
{
    $d = json_decode($json, true);
    if (!is_array($d) || !is_array($d['mail']['smtp'] ?? null) || !array_key_exists('pass', $d['mail']['smtp'])) {
        return [$json, false];
    }
    $had = (string) $d['mail']['smtp']['pass'] !== '';
    unset($d['mail']['smtp']['pass']);
    return [(string) json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), $had];
}

/**
 * Ayarların geçmiş sürümlerinden SMTP şifresini siler (eski sürümler şifreyi düz metin tutuyordu). Ayarlar her kaydedildiğinde ve günlük bakımda çalışır;
 * şifre içermeyen dosyalara dokunmaz. @return int temizlenen dosya sayısı
 */
function settings_history_scrub(): int
{
    $n = 0;
    foreach (glob(CONTENT_DIR . '/_history/settings/*.json') ?: [] as $f) {
        $raw = (string) @file_get_contents($f);
        if (!str_contains($raw, '"pass"')) {
            continue;
        }
        [$clean, $had] = settings_strip_secrets($raw);
        if ($had && @file_put_contents($f, $clean, LOCK_EX) !== false) {
            $n++;
        }
    }
    return $n;
}

/* ---------- Yedekten gelen geçmiş sürümleri ---------- */

/** Bir yedekten alınabilecek geçmiş sürümü sayısı: bölüm başına CONTENT_HISTORY_KEEP'in altında kalır ki gerçek geçmiş yer açmak için silinmesin. */
function restore_history_room(string $key): int
{
    $have = array_filter(glob(CONTENT_DIR . '/_history/' . $key . '/*.json') ?: [], fn($f) => !str_ends_with($f, '-0000.json'));
    return max(0, CONTENT_HISTORY_KEEP - 1 - count($have));
}

/**
 * Yedekteki bir geçmiş sürümünü doğrular ve diske yazılacak metni döndürür; kabul edilmezse null ($why nedeni söyler).
 * Geçmiş sürümü ileride geri alınabildiği için canlı veriyle AYNI temizleme ve doğrulamadan geçer (restore_clean); ayarlar sürümlerinde bildirim/gönderen
 * adresi ve SEO sürümlerinde doğrulama kodları, ilgili kutu işaretli değilse şu anki değerle değiştirilir (aksi halde geçmişten geri alarak
 * onaysız değiştirilebilirdi). Gelecek tarihli sürüm ve sitenin özgün hali ("-0000") alınmaz; SMTP şifresi yazılmaz.
 * @param array $opt ['mail_routes' => bool, 'seo_codes' => bool]
 */
function restore_history_clean(string $key, string $rev, $val, array $opt, ?string &$why = null): ?string
{
    $dt = DateTime::createFromFormat('Ymd-His', substr($rev, 0, 15));
    if (!$dt || $dt->format('Ymd-His') !== substr($rev, 0, 15)) {
        $why = 'sürüm adındaki tarih geçersiz.';
        return null;
    }
    if ($dt->getTimestamp() > time() + 300) {
        $why = 'sürüm gelecek tarihli.';
        return null;
    }
    if (str_ends_with($rev, '-0000')) {
        $why = 'sitenin özgün hali yedekten alınmaz.';
        return null;
    }
    if (!is_array($val)) {
        $why = 'içerik geçerli bir liste değil.';
        return null;
    }
    $dropped = [];
    $rep = ['key' => $key, 'dropped' => [], 'notes' => [], 'error' => null, 'history' => true, 'import' => true] + $opt;
    if ($key === 'duyurular') {
        $clean = ann_clean_restore($val, $dropped);
        $bad = $val && !$clean;
    } elseif ($key === 'ilanlar') {
        $clean = ilan_clean_restore($val, $dropped);
        $bad = $val && !$clean;
    } elseif (in_array($key, restore_stores(), true)) {
        $clean = restore_clean($key, $val, $rep);
        $bad = $clean === null;
        $dropped = $rep['dropped'];
        if ($bad) {
            $why = (string) ($rep['error'] ?? 'içerik doğrulamadan geçmedi.');
        }
    } else {
        $why = 'bilinmeyen bölüm.';
        return null;
    }
    if ($bad) {
        $why = $why ?: 'hiçbir öğe doğrulamadan geçmedi.';
        return null;
    }
    $json = (string) json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return $key === 'settings' ? settings_strip_secrets($json)[0] : $json;
}

/* ---------- Giriş noktası ---------- */

/**
 * Bir deponun ham içeriğini (yedekten, geçmişten) temizler. Sonuç: temiz içerik, ya da depo reddedildiyse null ($rep['error'] doludur).
 * @param array{dropped:string[], notes:string[], error:?string} $rep
 */
function restore_clean(string $key, $data, array &$rep)
{
    switch ($key) {
        case 'services': return restore_services_clean($data, $rep);
        case 'posts':    return restore_posts_clean($data, $rep);
        case 'refs':     return restore_refs_clean($data, $rep);
        case 'lists':    return restore_lists_clean($data, $rep);
        case 'features': return restore_features_clean($data, $rep);
        case 'settings': return restore_settings_clean($data, $rep);
        case 'seo':      return restore_seo_clean($data, $rep);
        case 'texts':
            if (!is_array($data)) {
                $rep['error'] = 'Sayfa metinleri dosyası geçerli değil.';
                return null;
            }
            if (!$data && !restore_empty_ok($rep, 'Sayfa metinleri')) {
                return null;
            }
            $c = texts_sanitize_all($data);
            if (count($data) > count($c)) {
                $rep['dropped'][] = (count($data) - count($c)) . ' sayfa metni (bilinmeyen anahtar ya da kurala uymayan metin).';
            }
            if ($data && !$c) {
                $rep['error'] = 'Hiçbir sayfa metni geçerli değil. Geri yüklenmedi.';
                return null;
            }
            return $c;
        case 'legal':
            if (!is_array($data)) {
                $rep['error'] = 'Yasal metinler dosyası geçerli değil.';
                return null;
            }
            if (!$data && !restore_empty_ok($rep, 'Yasal metinler')) {
                return null;
            }
            $c = legal_sanitize_all($data);
            foreach (array_diff_key($data, $c) as $doc => $_) {
                $rep['dropped'][] = 'Yasal metin «' . mb_substr((string) $doc, 0, 30) . '»: bilinmeyen belge ya da kurallara uymuyor.';
            }
            if ($data && !$c) {
                $rep['error'] = 'Hiçbir yasal metin geçerli değil. Geri yüklenmedi.';
                return null;
            }
            return $c;
    }
    $rep['error'] = 'Bilinmeyen içerik bölümü: ' . $key;
    return null;
}

/**
 * Bir bölümü ham içerikten geri yükler: temizler, doğrular ve kaydeder (içerik depoları, duyurular, ilanlar).
 * $history: veri değişiklik geçmişinden geliyor (boş hal geçerli). Raporu restore_report() ile okunur. @return bool yazıldı mı (false: depo reddedildi ya da kaydedilemedi; hiçbir şey değişmedi)
 */
function restore_store(string $key, $data, bool $history = false, array $opt = []): bool
{
    $rep = ['key' => $key, 'dropped' => [], 'notes' => [], 'error' => null, 'history' => $history] + $opt;
    $ok = false;
    if ($key === 'duyurular') {
        if (!is_array($data)) {
            $rep['error'] = 'Duyurular dosyası geçerli değil.';
        } elseif (!($ok = ann_restore_all($data, $rep['dropped']))) {
            $rep['error'] = 'Hiçbir duyuru geçerli değil ya da kaydedilemedi. Duyurular geri yüklenmedi.';
        }
    } elseif ($key === 'ilanlar') {
        if (!is_array($data)) {
            $rep['error'] = 'İlanlar dosyası geçerli değil.';
        } elseif (!($ok = ilan_restore_all($data, $rep['dropped']))) {
            $rep['error'] = 'Hiçbir ilan geçerli değil ya da kaydedilemedi. İlanlar geri yüklenmedi.';
        }
    } elseif (in_array($key, restore_stores(), true)) {
        $c = restore_clean($key, $data, $rep);
        if ($c !== null) {
            if (!($ok = content_put($key, $c))) {
                $rep['error'] = 'Kaydedilemedi: storage klasörü yazılabilir mi?';
            } else {
                // Aynı işlemde sonraki doğrulamalar (ör. hedef eşleştirici hizmet adreslerine bakar) güncel değeri görsün
                if ($key === 'services') { $GLOBALS['services'] = services_effective(); restore_goals_after_services($rep); }
                if ($key === 'posts')    $GLOBALS['posts']    = content_get('posts') ?? require APP . '/data/posts.php';
                if ($key === 'lists')    $GLOBALS['site']     = lists_effective();
            }
        }
    } else {
        $rep['error'] = 'Bilinmeyen içerik bölümü: ' . $key;
    }
    restore_report($rep);
    return $ok;
}
