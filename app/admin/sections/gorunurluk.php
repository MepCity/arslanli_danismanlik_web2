<?php
/**
 * Görünürlük: sitedeki bölümlerin açılıp kapatılması (content 'features').
 * Kapalı bir bölüm fihristten, altbilgiden, ana sayfadan, site haritasından ve akışlardan kalkar; adresleri 404 döner.
 * İçerikler panelde saklanmaya ve düzenlenmeye devam eder.
 */

$count = [
    'blog'        => count(array_filter($GLOBALS['posts'], fn($p) => empty($p['draft']))),
    'duyurular'   => count(array_filter(ann_all(), fn($a) => !empty($a['published']))),
    'referanslar' => count(refs_list()),
    'kariyer'     => count(ilan_published()),
];

// [anahtar, başlık, ikon, sitede nerede, kapalıyken ne olur, panel bölümü, sitedeki adres, hazır içerik satırı]
$groups = [
    'Sayfalar ve bölümler' => [
        ['blog', 'Yazılar', 'newspaper',
            ['Makaleler sayfası ve yazı sayfaları', 'Ana sayfadaki "Arslanlı Bülteni" yazı sütunları', 'Fihrist (menü) bağlantısı', 'Site haritası'],
            'Yazı adresleri "bulunamadı" döner; arama motorları bu sayfaları zamanla dizinden çıkarır. Açtığınızda hepsi geri gelir.',
            'blog', 'blog', $count['blog'] . ' yayına hazır yazı'],
        ['duyurular', 'Duyurular ve çağrı takvimi', 'megaphone',
            ['Duyurular sayfası', 'Sitenin açılışında öne çıkan duyuru penceresi', 'Fihrist ve altbilgi bağlantısı', 'Takvim aboneliği (.ics) ve site haritası'],
            'Duyurular, açılış penceresi ve takvim hiçbir yerde görünmez; takvim aboneliği de durur.',
            'duyurular', 'duyurular', $count['duyurular'] . ' yayındaki duyuru'],
        ['referanslar', 'Referanslar', 'images',
            ['Referanslar sayfası', 'Ana sayfadaki kaşe (logo) şeridi', 'Fihrist bağlantısı ve site haritası'],
            'Logolar hiçbir yerde görünmez; "Sonraki evrak" kartları bir sonraki sayfaya yönlenir.',
            'referanslar', 'referans', $count['referanslar'] . ' logo'],
        ['kariyer', 'Kariyer', 'briefcase',
            ['Kariyer sayfası ve iş başvuru formu', 'Fihrist ve altbilgi bağlantısı', 'Site haritası'],
            'Kariyer sayfası "bulunamadı" döner; yeni iş başvurusu alınmaz. Gelen başvurular ve ilanlar panelde kalır.',
            'basvurular', 'kariyer', $count['kariyer'] . ' açık ilan'],
    ],
    'Ziyaretçiyle iletişim' => [
        ['bulten', 'Bülten (Haberdar ol)', 'envelope-simple',
            ['Haberdar Ol sayfası (kupon)', 'Sağ kenardaki Bülten sekmesi ve kayıt penceresi', 'Altbilgideki "Haberdar ol" bağlantısı', 'İletişim sayfasındaki "Sonraki evrak" kartı'],
            'Haberdar Ol sayfası ve kayıt penceresi kalkar; yeni bülten kaydı alınmaz.',
            'kayitlar', 'haberdarol', ''],
        ['whatsapp', 'WhatsApp düğmesi', 'chat-circle',
            ['Sağ alt köşedeki WhatsApp düğmesi'],
            'Ziyaretçiler size WhatsApp\'tan tek dokunuşla yazamaz; telefon, e-posta ve iletişim sayfasındaki bağlantılar durur.',
            'ayarlar', '', ''],
    ],
];

/* ---------- Kaydet ---------- */
if ($method === 'POST') {
    $new = [];
    $changes = [];
    foreach ($groups as $items) {
        foreach ($items as [$key, $title]) {
            $new[$key] = post_bool('f_' . $key);
            if ($new[$key] !== feature($key)) $changes[] = $title . ($new[$key] ? ' açıldı' : ' kapatıldı');
        }
    }
    if (!$changes) {
        adm_flash('Değişiklik yok.');
    } elseif (content_put('features', $new)) {
        adm_flash(implode(', ', $changes) . '. Değişiklik sitede hemen geçerli.');
    } else {
        adm_flash('Kaydedilemedi: storage klasörü yazılabilir mi?', 'err');
    }
    adm_go('gorunurluk');
}

/* ---------- Ekran ---------- */
$navPreview = [
    ['hizmetler', 'Hizmetler', ''], ['hakkimizda', 'Hakkımızda', ''], ['referans', 'Referanslar', 'referanslar'],
    ['duyurular', 'Duyurular', 'duyurular'], ['blog', 'Makaleler', 'blog'], ['kariyer', 'Kariyer', 'kariyer'], ['haberdarol', 'Haberdar Ol', 'bulten'], ['iletisim', 'İletişim', ''],
];
$preview = '<div class="vis-nav" aria-hidden="true">';
foreach ($navPreview as [$p, $label, $f]) {
    $preview .= '<span class="vis-nav__i' . ($f !== '' && !feature($f) ? ' is-off' : '') . '"' . ($f !== '' ? ' data-vis-nav="' . e($f) . '"' : '') . '>' . e($label) . '</span>';
}
$preview .= '</div>';

ob_start(); ?>
<form id="vis-form" method="post" action="<?= adm_url('gorunurluk') ?>" class="vis">
  <?= adm_csrf_field() ?>
  <?= ui_card('Fihrist (menü) böyle görünecek', $preview . '<p class="fld__help" style="margin-top:12px">Anahtarları değiştirdikçe önizleme güncellenir. Kaydettiğinizde site de aynı anda güncellenir.</p>', ['class' => 'vis-prev']) ?>
  <?php foreach ($groups as $gTitle => $items): ?>
    <h2 class="vis__group"><?= e($gTitle) ?></h2>
    <div class="vis__grid">
      <?php foreach ($items as [$key, $title, $ic, $where, $offNote, $admSec, $pubPath, $ready]): $on = feature($key); ?>
        <section class="card vis-card<?= $on ? '' : ' is-off' ?>" data-vis-card>
          <div class="card__body">
            <div class="vis-card__head">
              <span class="vis-card__ic"><?= ui_icon($ic) ?></span>
              <div class="vis-card__t">
                <h3><?= e($title) ?></h3>
                <span class="badge badge--ok vis-card__on"><i class="dot"></i>Sitede açık</span>
                <span class="badge vis-card__off">Sitede kapalı</span>
              </div>
              <label class="tgl vis-card__tgl" for="f-<?= e($key) ?>">
                <input type="hidden" name="f_<?= e($key) ?>" value="0">
                <input type="checkbox" id="f-<?= e($key) ?>" name="f_<?= e($key) ?>" value="1"<?= $on ? ' checked' : '' ?> data-vis-toggle="<?= e($key) ?>">
                <span class="tgl__track" aria-hidden="true"><span class="tgl__dot"></span></span>
                <span class="sr"><?= e($title) ?> sitede görünsün</span>
              </label>
            </div>
            <p class="vis-card__label">Sitede görünen yerler</p>
            <ul class="vis-card__where">
              <?php foreach ($where as $w): ?><li><?= ui_icon('check-circle') ?><?= e($w) ?></li><?php endforeach; ?>
            </ul>
            <p class="vis-card__note"><?= ui_icon('info') ?><span><strong>Kapalıyken:</strong> <?= e($offNote) ?></span></p>
            <div class="vis-card__foot">
              <?php if ($ready !== ''): ?><span class="muted"><?= e($ready) ?></span><?php endif; ?>
              <span class="vis-card__links">
                <?php if (!isset(adm_pending_sections()[$admSec])): ?><a class="btn btn--ghost btn--sm" href="<?= adm_url($admSec) ?>"><?= ui_icon('pencil-simple') ?>Panelde aç</a><?php endif; ?>
                <?php if ($on && $pubPath !== ''): ?><?= ui_view_link(url($pubPath)) ?><?php endif; ?>
              </span>
            </div>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
  <div class="vis__foot"><button class="btn" type="submit"><?= ui_icon('floppy-disk') ?>Kaydet</button></div>
</form>
<script>
(function () {
  var form = document.getElementById('vis-form');
  if (!form) return;
  form.querySelectorAll('[data-vis-toggle]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var key = inp.getAttribute('data-vis-toggle');
      var card = inp.closest('[data-vis-card]');
      if (card) card.classList.toggle('is-off', !inp.checked);
      form.querySelectorAll('[data-vis-nav="' + key + '"]').forEach(function (el) { el.classList.toggle('is-off', !inp.checked); });
    });
  });
})();
</script>
<?php
adm_layout('Görünürlük', (string) ob_get_clean(), [
    'section'  => 'gorunurluk',
    'subtitle' => 'Sitede hangi bölümlerin görüneceğini seçin. Kapalı bir bölüm fihristten, altbilgiden, ana sayfadan ve site haritasından kalkar; içeriği panelde saklanmaya devam eder.',
    'form'     => 'vis-form',
]);
