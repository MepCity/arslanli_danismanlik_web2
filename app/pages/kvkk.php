<?php
/**
 * KVKK Aydınlatma Metni (6698 sayılı Kanun m. 10)
 *
 * İçerik sitenin kodu (app/form.php, app/spam.php, assets/js) okunarak yazıldı: dört form (dilekçe, Haberdar Ol, bülten, iş başvurusu),
 * gönderimlerin storage/ altında saklanması ve şirket adresine e-postalanması, özgeçmiş dosyası, istenmeyen gönderim süzgeci.
 * Sitede ziyaretçi sayacı, yönetim paneli, toplu bülten gönderimi ve abonelikten ayrılma bağlantısı YOKTUR; metin bunlardan söz etmez.
 * Kariyer sayfası bu metindeki #basvuru-adaylari bağlantısına gider: o maddenin 'anchor' değerini değiştirmeyin.
 *
 * TASLAKTIR: Yayına almadan önce hukuki kontrol gerekir. Özellikle şunlar netleştirilmeli:
 *  - Hukuki sebepler (özellikle Haberdar Ol / ticari elektronik ileti için dayanılan sebep),
 *  - Saklama süreleri (özellikle iş başvurularının saklama süresi: kodda otomatik silme yoktur, süre belirtilmemiştir),
 *  - Barındırma ve e-posta hizmet sağlayıcılarının adları ve sunucularının yurt dışında olup olmadığı,
 *  - İYS kaydı yükümlülüğünün sizin için geçerli olup olmadığı.
 * Metnin yazıları kayıt defterindedir (app/data/texts/52-kvkk.php; panelde Sayfa metinleri > KVKK Aydınlatma Metni): her madde, fıkra ve sade Türkçesi ayrı kayıttır.
 * Teknik not: form.php gönderimleri storage/submissions.jsonl dosyasına IP adresiyle birlikte yazar;
 * hız sınırı kayıtları (storage/rate-*.json) kendiliğinden silinmez, sunucuda dönemsel temizlik önerilir.
 */
require_once APP . '/pages/_legal.php';

page([
    'id'          => 'legal',
    'title'       => pg_name('kvkk'),
    'description' => t('kvkk.seo.description'),
    'folio'       => pg_folio('kvkk'),
]);

// Maddelerin içindeki yerel yer tutucular (genel olanlar: {firma}, {eposta_baglanti}… kayıt defteri tarafından doldurulur)
$vars = [
    'yetkili'        => (string) cfg('company.authorized'),
    'cerez_baglanti' => ['html' => '<a class="link" href="' . url('kurumsal/cerez-politikasi') . '">' . e(pg_name('cerez')) . '</a>'],
];

// id: kayıt anahtarının ikinci parçası, f: fıkra sayısı, bent: harfli liste maddesi sayısı. Kariyer sayfası #basvuru-adaylari bağlantısına gider: 'anchor' değerini değiştirmeyin.
$maddeler = legal_maddeler('kvkk', [
    ['id' => 'm1', 'f' => 2, 'sade' => true],
    ['id' => 'm2', 'f' => 6, 'sade' => true],
    ['id' => 'm3', 'f' => 2, 'sade' => true],
    ['id' => 'm4', 'f' => 3, 'sade' => true],
    ['id' => 'm5', 'f' => 4, 'sade' => true, 'anchor' => 'basvuru-adaylari'],
    ['id' => 'm6', 'f' => 2, 'sade' => true],
    ['id' => 'm7', 'f' => 4, 'sade' => true],
    ['id' => 'm8', 'f' => 1, 'bent' => 9, 'sade' => true],
    ['id' => 'm9', 'f' => 2, 'sade' => true],
    ['id' => 'm10', 'f' => 1],
], $vars);

legal_doc([
    'no'       => t('kvkk.baslik.sayi'),
    'subject'  => t('kvkk.baslik.konu'),
    'h1'       => t('kvkk.baslik.baslik'),
    'updated'  => t('kvkk.baslik.guncelleme'),
    'lead'     => t('kvkk.baslik.giris'),
    'summary'  => legal_ozet('kvkk', 5),
    'maddeler' => $maddeler,
    'next'     => ['label' => t('kvkk.sonraki.ad'), 'href' => url('kurumsal/cerez-politikasi'), 'no' => pg_no('cerez')],
]);
