<?php
declare(strict_types=1);

/**
 * MCP yasal metin araçları: KVKK Aydınlatma Metni ve Çerez Politikası maddeleri (yasal_metin_getir, yasal_metin_guncelle).
 * Panelin Yasal metinler bölümüyle aynı işlevleri kullanır (app/legal.php: legal_get, legal_save); doğrulama ve kayıt tek yerdedir.
 * "Kontrol edildi" işareti (hukuk danışmanı beyanı) yalnızca panelde kalır.
 */

/** Maddeleri yapay zekâya gösterilen biçime çevirir. */
function mcp_legal_public(string $doc): array
{
    $cond = legal_conditions();
    $leaf = fn(array $x) => ['metin' => (string) ($x['t'] ?? '')] + (($x['when'] ?? '') !== '' ? ['kosul' => (string) $x['when']] : []);
    $out = [];
    foreach (legal_get($doc) as $i => $a) {
        $out[] = ['no' => $i + 1, 'baslik' => (string) ($a['title'] ?? ''), 'fikralar' => array_map($leaf, (array) ($a['paras'] ?? [])),
            'liste' => array_map(fn($x) => (string) ($x['t'] ?? ''), (array) ($a['items'] ?? [])), 'sade' => array_map($leaf, (array) ($a['plain'] ?? []))]
            + (!empty($a['anchor']) ? ['kimlik' => (string) $a['anchor']] : []);
    }
    return $out;
}

function mcp_tools_yasal(): array
{
    $T = [];
    $docs = implode(', ', array_map(fn($k, $d) => $k . ' = ' . $d[0], array_keys(legal_docs()), legal_docs()));
    $conds = implode('; ', array_map(fn($k, $v) => $k . ' = ' . $v, array_keys(legal_conditions()), legal_conditions()));

    $T[] = mcp_def('yasal_metin_getir', 'okuma', 'Yasal metin maddelerini getir',
        'KVKK Aydınlatma Metni (kvkk) ya da Çerez Politikası (cerez) maddelerini sırayla getirir: her madde için başlık, fıkralar, harfli liste maddeleri ve sade Türkçesi. Sayfada madde numaraları ve içindekiler sıradan kendiliğinden üretilir. '
        . 'Çerez Politikası’nda bazı fıkralar sitenin kodu taranarak seçilir: fıkranın kosul alanı doluysa fıkra yalnızca o durumda görünür (' . $conds . '). Kimlik alanı dolu olan madde (KVKK’da “iş başvurusu yapan adaylar”) Kariyer sayfasındaki forma bağlıdır ve silinemez. '
        . 'Metinlerde [kalın]…[/kalın], [eğik]…[/eğik], [vurgu]…[/vurgu] işaretleri ve {firma}, {eposta_baglanti} gibi yer tutucular bulunur; olduğu gibi geri yazın.',
        sc_obj(['belge' => sc_enum(array_keys(legal_docs()), 'Belge: ' . $docs . '.')], ['belge']),
        [true, false, true], function (array $a): array {
            $doc = $a['belge'];
            return mcp_ok(['belge' => $doc, 'ad' => legal_docs()[$doc][0], 'degistirildi' => json_encode(legal_get($doc)) !== json_encode(legal_default($doc)), 'sayfa' => '/' . legal_docs()[$doc][2],
                'sinirlar' => ['madde' => legal_limits()['articles'], 'baslik_karakter' => legal_limits()['title'], 'fikra_sayisi' => legal_limits()['paras'], 'liste_maddesi' => legal_limits()['items'], 'metin_karakter' => legal_limits()['text']],
                'yer_tutucular' => array_values(array_unique(array_merge(array_keys(text_globals()), array_keys(legal_local_vars($doc))))),
                'maddeler' => mcp_legal_public($doc)]);
        });

    $leafSchema = sc_obj(['metin' => sc_str('Metin (en çok ' . legal_limits()['text'][1] . ' karakter).', ['minLength' => 1, 'maxLength' => 3000]),
        'kosul' => sc_str('İsteğe bağlı, yalnızca Çerez Politikası: fıkra yalnızca bu durumda görünür. ' . $conds . '.', ['maxLength' => 40])], ['metin']);
    $T[] = mcp_def('yasal_metin_guncelle', 'icerik', 'Yasal metin maddelerini güncelle',
        'KVKK Aydınlatma Metni ya da Çerez Politikası’nın maddelerini TÜMÜYLE değiştirir (verdiğiniz liste eskisinin yerine geçer): madde eklemek, silmek, sıralamak ve metni düzenlemek bu araçla yapılır. Önce yasal_metin_getir ile mevcut halini okuyun ve değiştirmediğiniz maddeleri de aynen yazın (kimlik alanlarıyla birlikte). '
        . 'Panelin Yasal metinler bölümüyle aynı doğrulama uygulanır: ' . legal_limits()['articles'][0] . ' ile ' . legal_limits()['articles'][1] . ' madde; HTML yazılamaz; yer tutucular tanıtılmış olmalı; KVKK’daki kimlikli madde (iş başvurusu yapan adaylar) silinemez. '
        . 'Çerez Politikası’nın taramayla üretilen kısmı (hangi fıkranın görüneceği) kodu okuyarak belirlenir; yalnızca fıkraların metnini ve koşullarını yazarsınız. HUKUKİ METİN: değişikliği yapmadan önce kullanıcıya gösterip onay alın, hukuki içeriği kendiniz uydurmayın; “kontrol edildi” işareti yalnızca panelde konur.',
        sc_obj([
            'belge' => sc_enum(array_keys(legal_docs()), 'Belge: ' . $docs . '.'),
            'maddeler' => sc_list(sc_obj([
                'baslik' => sc_str('Madde başlığı (3-90 karakter; içindekiler listesinde görünür).', ['minLength' => 3, 'maxLength' => 120]),
                'fikralar' => sc_list($leafSchema, 'Fıkralar (1-10). Sayfada sırayla basılır.', ['minItems' => 1, 'maxItems' => 10]),
                'liste' => sc_list(sc_str('Harfli liste maddesi.', ['maxLength' => 3000]), 'İsteğe bağlı harfli liste (en çok 14); a), b)… harfleri kendiliğinden verilir.', ['maxItems' => 14]),
                'sade' => sc_list($leafSchema, 'İsteğe bağlı “sade Türkçesi” (en çok 3; koşula uyan ilki görünür).', ['maxItems' => 3]),
                'kimlik' => sc_str('yasal_metin_getir sonucundaki kimlik (varsa aynen geri yazın; yeni maddede yazmayın).', ['maxLength' => 60]),
            ], ['baslik', 'fikralar']), 'Belgenin yeni, tam madde listesi (sıra sayfadaki sıradır).', ['minItems' => 3, 'maxItems' => 20]),
        ], ['belge', 'maddeler']), [false, true, true], function (array $a): array {
            $doc = $a['belge'];
            $raw = [];
            foreach ($a['maddeler'] as $m) {
                $leaf = fn($rows) => array_map(fn($x) => ['t' => mcp_str($x['metin'] ?? ''), 'when' => (string) ($x['kosul'] ?? '')], is_array($rows) ? $rows : []);
                $raw[] = ['title' => mcp_line($m['baslik'] ?? ''), 'paras' => $leaf($m['fikralar'] ?? []),
                    'items' => array_map(fn($t) => ['t' => mcp_str($t)], is_array($m['liste'] ?? null) ? $m['liste'] : []), 'plain' => $leaf($m['sade'] ?? []), 'anchor' => (string) ($m['kimlik'] ?? '')];
            }
            $r = legal_save($doc, $raw);
            if (!$r['ok']) {
                mcp_fail("Yasal metin kaydedilmedi:\n- " . implode("\n- ", $r['errors']));
            }
            $msg = $r['changed'] ? 'Yasal metin güncellendi: ' . legal_docs()[$doc][0] . '. Hukuk danışmanının yeniden görmesi için panelde “kontrol edildi” işaretini yenilemesini önerin.' : 'Değişiklik yok: metin zaten bu halde.';
            return mcp_ok(['mesaj' => $msg, 'belge' => $doc, 'degisti' => (bool) $r['changed'], 'madde_sayisi' => count(legal_get($doc))], $msg);
        });

    return $T;
}
