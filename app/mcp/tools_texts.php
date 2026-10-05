<?php
declare(strict_types=1);

/**
 * MCP sayfa metni araçları (metin_ara, metin_getir, metin_guncelle).
 * KOŞULLU: yalnızca sayfa metni kayıt defteri (texts_registry, texts_flat; içerik anahtarı "texts") yüklüyse kayda girer
 * (bkz. mcp_tools() ve mcp_has_texts()); metin_guncelle ayrıca doğrulayıp kaydeden texts_save() ister (panelle aynı işlev).
 * İşlevler yoksa araçlar hiç listelenmez; sahte sonuç üretilmez.
 *
 * Metin türleri (kayıt defteri): line = tek satır, text = paragraf (satır sonları boşluk), lines = her satır sonu sayfada satır sonudur,
 * rich = düz metin + köşeli biçim işaretleri ([kalın]…[/kalın]); HTML yazılamaz. Yer tutucular: {firma}, {n|yazı} gibi.
 */

/** Bir metnin anahtardan grubu, grup adı ve bölümü */
function mcp_text_locate(string $key): array
{
    foreach (texts_registry() as $gid => $g) {
        foreach ((array) ($g['sections'] ?? []) as $sec => $items) {
            if (isset($items[$key])) {
                return [(string) $gid, (string) $g['label'], mcp_section_name((string) $sec)];
            }
        }
    }
    return ['', '', ''];
}

/** Metnin şu anki değeri: panelden değiştirilmişse o, değilse özgün metin. */
function mcp_text_current(string $key, array $item, ?array $over = null): string
{
    $over = $over ?? (array) content_get('texts', []);
    return isset($over[$key]) && is_string($over[$key]) && trim($over[$key]) !== '' ? (string) $over[$key] : (string) $item[1];
}

function mcp_text_row(string $key, array $it, string $cur, string $gid, string $glabel, string $sec, bool $clip): array
{
    $def = (string) $it[1];
    $row = [
        'anahtar'      => $key,
        'grup'         => $gid,
        'grup_adi'     => $glabel,
        'bolum'        => $sec,
        'etiket'       => (string) $it[0],
        'tur'          => (string) ($it[2] ?? 'line'),
        'mevcut'       => $clip ? mcp_clip($cur, 1200) : $cur,
        'varsayilan'   => $clip ? mcp_clip($def, 1200) : $def,
        'degistirildi' => $cur !== $def,
    ];
    if (!empty($it[3])) {
        $row['yardim'] = (string) $it[3];
    }
    if (!$clip && function_exists('texts_opts')) {
        $o = texts_opts($key);
        $row['sinirlar'] = ['en_cok_karakter' => (int) ($o['max'] ?? 0), 'en_cok_satir' => (int) ($o['lines'] ?? 1), 'yer_tutucular' => (object) ($o['vars'] ?? []), 'silinmemesi_gereken_yer_tutucular' => array_values((array) ($o['need'] ?? []))];
    }
    if ($clip && (mb_strlen($cur) > 1200 || mb_strlen($def) > 1200)) {
        $row['kisaltildi'] = true;
    }
    return $row;
}

function mcp_tools_texts(): array
{
    $RO = [true, false, true];
    $T = [];
    $groups = implode(', ', array_keys(texts_registry()));
    $marks = defined('TEXT_MARKS') ? implode(', ', array_map(fn($m) => '[' . $m . ']…[/' . $m . ']', array_keys(TEXT_MARKS))) : '[kalın]…[/kalın]';

    $T[] = mcp_def('metin_ara', 'okuma', 'Sayfa metni ara',
        'Sitedeki düzenlenebilir sayfa metinlerinde (başlıklar, paragraflar, düğme yazıları, form iletileri, menü ve altbilgi yazıları) arar. Türkçe karakter ve büyük/küçük harf farkı yok sayılır ("hizmetler" ile "HİZMETLER" aynıdır); birden fazla kelime yazarsanız hepsinin geçtiği metinler gelir. Sonuçta metnin anahtarı (metin_guncelle için), bulunduğu grup, şu anki metin, özgün (varsayılan) metin, türü ve değiştirilip değiştirilmediği yer alır. Sitede gördüğünüz bir cümleyi değiştirmek için önce bunu kullanın. Gruplar: ' . $groups . '.',
        sc_obj([
            'sorgu' => sc_str('Aranan kelime ya da cümle (en az 2 karakter). Metnin anahtarını da (örneğin "genel.sayfa.home") yazabilirsiniz.', ['minLength' => 2, 'maxLength' => 120]),
            'grup'  => sc_str('İsteğe bağlı: aramayı tek bir metin grubuna daralt (sayfalari_listele sonucundaki metin_gruplari; şu an: ' . $groups . ').', ['maxLength' => 40]),
        ], ['sorgu']), $RO, function (array $a): array {
            $reg = texts_registry();
            $grup = $a['grup'] ?? '';
            if ($grup !== '' && !isset($reg[$grup])) {
                mcp_fail('Bilinmeyen grup: ' . mcp_clip($grup, 40) . '. Geçerli gruplar: ' . implode(', ', array_keys($reg)));
            }
            $over = (array) content_get('texts', []);
            $tokens = array_values(array_filter(explode(' ', mcp_fold($a['sorgu']))));
            if (!$tokens || mb_strlen(implode('', $tokens)) < 2) {
                mcp_fail('Aramak için en az iki harf yazın.');
            }
            $hits = [];
            $total = 0;
            foreach ($reg as $gid => $g) {
                if ($grup !== '' && $gid !== $grup) {
                    continue;
                }
                foreach ((array) ($g['sections'] ?? []) as $sec => $items) {
                    foreach ($items as $k => $it) {
                        $cur = mcp_text_current($k, $it, $over);
                        $hay = mcp_fold($k . ' ' . $it[0] . ' ' . $cur . ' ' . $it[1]);
                        foreach ($tokens as $tk) {
                            if (strpos($hay, $tk) === false) {
                                continue 2;
                            }
                        }
                        $total++;
                        if (count($hits) < 30) {
                            $hits[] = mcp_text_row($k, $it, $cur, (string) $gid, (string) $g['label'], mcp_section_name((string) $sec), true);
                        }
                    }
                }
            }
            return mcp_ok(['sorgu' => $a['sorgu'], 'toplam_eslesen' => $total, 'gosterilen' => count($hits), 'sonuclar' => $hits],
                $total === 0 ? 'Eşleşen metin bulunamadı. Daha kısa ya da farklı bir kelime deneyin; hizmet dosyaları, yazılar, duyurular, iş ilanları ve kurumsal listeler ve yasal metinler (KVKK, çerez politikası) bu aramada çıkmaz (kendi araçları vardır: yasal_metin_getir).' : '');
        });

    $T[] = mcp_def('metin_getir', 'okuma', 'Sayfa metnini getir',
        'Tek bir sayfa metnini anahtarıyla getirir: şu anki metin, özgün metin, türü (line: tek satır; text: paragraf; lines: her satır sonu sayfada satır sonu olur; rich: köşeli biçim işaretli metin), bulunduğu grup ve bölüm, varsa yardım notu ve sınırları: en çok karakter, en çok satır, yazılabilen yer tutucular ve silinmemesi gerekenler. Anahtarı metin_ara ile bulun.',
        sc_obj(['anahtar' => sc_str('Metin anahtarı, örneğin "genel.sayfa.home".', ['minLength' => 1, 'maxLength' => 120])], ['anahtar']),
        $RO, function (array $a): array {
            $flat = texts_flat();
            $key = trim($a['anahtar']);
            if (!isset($flat[$key])) {
                mcp_fail('Bu anahtarda bir metin yok: ' . mcp_clip($key, 60) . '. Anahtarı bulmak için metin_ara kullanın.');
            }
            [$gid, $glabel, $sec] = mcp_text_locate($key);
            return mcp_ok(mcp_text_row($key, $flat[$key], mcp_text_current($key, $flat[$key]), $gid, $glabel, $sec, false));
        });

    if (function_exists('texts_save')) {
        $T[] = mcp_def('metin_guncelle', 'icerik', 'Sayfa metnini güncelle',
            'Sitedeki bir sayfa metnini (başlık, paragraf, düğme yazısı, form iletisi) değiştirir; değişiklik sitede hemen yayınlanır. Anahtarı metin_ara ile bulun; mevcut metni, türünü ve sınırlarını metin_getir ile okuyun. Panelin Sayfa metinleri bölümüyle aynı denetim uygulanır: en çok karakter ve satır sınırı (tasarım buna göre kuruludur), yalnızca o metne tanımlı yer tutucular ({firma}, {n|yazı}, {n|sıra}, {n|iyelik} gibi; hangilerinin yazılabildiği metin_getir sonucundaki sinirlar.yer_tutucular alanındadır, silinmemesi gerekenler korunur) ve HTML yazılamaz. '
            . 'Türler: line = tek satır (satır sonları boşluğa çevrilir); text = paragraf; lines = her satır sonu sayfada satır sonu olur; rich = biçimli metin, biçim için köşeli işaretler kullanılır: ' . $marks . ' (iç içe kullanım kuralları aracın hata iletisinde yazar). Boş metin ("") ya da özgün metnin aynısı verilirse metin özgün haline döner. Uzunluğu özgün metne yakın tutun. Yeni metinde tarih, tutar ya da kanun alıntısı uydurmayın.',
            sc_obj([
                'anahtar' => sc_str('Metin anahtarı, örneğin "genel.sayfa.home".', ['minLength' => 1, 'maxLength' => 120]),
                'metin'   => sc_str('Yeni metin (en fazla 20000 karakter; metne özgü karakter sınırı metin_getir sonucundaki sinirlar alanındadır). Boş metin özgün hale döndürür.', ['maxLength' => 20000]),
            ], ['anahtar', 'metin']), [false, true, true], function (array $a): array {
                $flat = texts_flat();
                $key = trim($a['anahtar']);
                if (!isset($flat[$key])) {
                    mcp_fail('Bu anahtarda bir metin yok: ' . mcp_clip($key, 60) . '. Anahtarı bulmak için metin_ara kullanın.');
                }
                $item = $flat[$key];
                $before = mcp_text_current($key, $item);
                $r = texts_save([$key => mcp_str($a['metin'])]);
                if (!$r['ok']) {
                    mcp_fail("Metin kaydedilmedi:\n- " . implode("\n- ", array_values($r['errors'])) . "\nSınırlar ve yer tutucular için metin_getir kullanın.");
                }
                [$gid, $glabel, $sec] = mcp_text_locate($key);
                $after = mcp_text_current($key, $item);
                if (!$r['changed']) {
                    return mcp_ok(['mesaj' => 'Değişiklik yok: metin zaten bu halde.', 'anahtar' => $key, 'metin' => $after], 'Değişiklik yok: metin zaten bu halde.');
                }
                $reset = $after === (string) $item[1];
                $msg = $reset ? 'Metin özgün haline döndürüldü.' : 'Metin güncellendi.';
                return mcp_ok(['mesaj' => $msg, 'anahtar' => $key, 'grup' => $gid, 'grup_adi' => $glabel, 'bolum' => $sec, 'onceki' => $before, 'yeni' => $after, 'ozgun_metne_dondu' => $reset], $msg);
            });
    }

    return $T;
}
