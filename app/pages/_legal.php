<?php
/**
 * Yasal metinler için ortak şablon: cerez.php ve kvkk.php kullanır.
 * Doğrudan adresi yoktur (index.php yalnızca tanımlı sayfaları açar).
 *
 * Sayfanın bütün yazıları kayıt defterindedir: çerçevenin sabit yazıları "yasal" grubunda, metnin kendisi (başlık, maddeler, fıkralar, sade Türkçesi)
 * sayfanın kendi grubunda ("kvkk", "cerez"). Madde numaraları, içindekiler ve bağlantı kimlikleri burada üretilir.
 *
 * $doc = [
 *   'no'       => 'ARS-2026/012',                  (düz metin)
 *   'subject'  => 'Konu satırı',                   (düz metin)
 *   'h1'       => 'Sayfa başlığı',                 (düz metin)
 *   'updated'  => '5 Ekim 2026',                   (düz metin)
 *   'lead'     => 'Başlığın altındaki tek cümle (isteğe bağlı)',
 *   'summary'  => ['güvenli HTML (<mark> içerebilir)', ...],
 *   'maddeler' => [['title' => 'Başlık', 'paras' => ['güvenli HTML', ...], 'list' => [['a', 'güvenli HTML'], ...] (isteğe bağlı), 'sade' => 'sade Türkçesi (güvenli HTML)', 'anchor' => 'dışarıdan bağlantı verilecek kısa kimlik (isteğe bağlı)'], ...],
 *   'next'     => ['label' => 'KVKK Aydınlatma Metni', 'href' => url(...), 'no' => '13'],
 * ]
 * Metinler th() ile gelen güvenli HTML'dir (kayıt defterinden); HTML olarak basılır.
 */

/**
 * Kayıt defterindeki madde yazılarından $doc['maddeler'] satırlarını kurar.
 * @param string $grup  Kayıt defteri grubu ("kvkk")
 * @param array  $spec  madde => ['id' => 'm1', 'f' => fıkra sayısı, 'bent' => liste maddesi sayısı (isteğe bağlı), 'sade' => bool, 'anchor' => '…']
 * @param array  $vars  th() için yerel yer tutucular (hepsine verilir; kullanılmayan yok sayılır)
 */
function legal_maddeler(string $grup, array $spec, array $vars = []): array
{
    $letters = ['a', 'b', 'c', 'ç', 'd', 'e', 'f', 'g', 'ğ', 'h', 'ı', 'i', 'j', 'k'];
    $out = [];
    foreach ($spec as $s) {
        $k = $grup . '.' . $s['id'];
        $m = ['title' => t($k . '.baslik'), 'paras' => []];
        for ($n = 1; $n <= (int) $s['f']; $n++) {
            $m['paras'][] = th($k . '.f' . $n, $vars);
        }
        for ($n = 1; $n <= (int) ($s['bent'] ?? 0); $n++) {
            $m['list'][] = [$letters[$n - 1], th($k . '.b' . $n, $vars)];
        }
        if (!empty($s['sade'])) {
            $m['sade'] = th($k . '.sade', $vars);
        }
        if (!empty($s['anchor'])) {
            $m['anchor'] = $s['anchor'];
        }
        $out[] = $m;
    }
    return $out;
}

/** Kısaca kutusunun cümleleri: kayıt defterinde vurgu [kalın] ile yazılır, sayfada <mark> olur. */
function legal_ozet(string $grup, int $adet, array $vars = []): array
{
    $out = [];
    for ($n = 1; $n <= $adet; $n++) {
        $out[] = str_replace(['<b>', '</b>'], ['<mark>', '</mark>'], th($grup . '.kisaca.m' . $n, $vars));
    }
    return $out;
}

function legal_doc(array $doc): void
{
    $maddeler = $doc['maddeler'];
    ?>
<section class="lg-head pagehead" aria-labelledby="lg-title">
  <div class="wrap lg-head__in">
    <div class="lg-head__main">
      <p class="docmeta" data-rise>
        <span><?= th('yasal.ust.sayi', ['sayi' => $doc['no']]) ?></span>
        <span><?= th('yasal.ust.konu', ['konu' => $doc['subject']]) ?></span>
        <span><?= th('yasal.ust.guncelleme', ['tarih_son' => $doc['updated']]) ?></span>
      </p>
      <h1 class="display lg-head__h" id="lg-title" data-rise style="--delay:.06s"><?= e($doc['h1']) ?></h1>
      <?php if (!empty($doc['lead'])): ?><p class="lead lg-head__lead" data-rise style="--delay:.1s"><?= e($doc['lead']) ?></p><?php endif; ?>
    </div>

    <aside class="kisaca" aria-labelledby="kisaca-h" data-rise style="--delay:.14s">
      <p class="kisaca__h" id="kisaca-h"><?= e(t('yasal.kisaca.baslik')) ?></p>
      <p class="kisaca__intro"><?= th('yasal.kisaca.giris') ?></p>
      <ul class="kisaca__list" role="list">
        <?php foreach ($doc['summary'] as $line): ?><li><?= $line ?></li><?php endforeach; ?>
      </ul>
    </aside>
  </div>
</section>

<section class="lg" aria-label="<?= e(t('yasal.metin.etiket')) ?>">
  <div class="wrap lg__grid">
    <nav class="lg__toc" aria-label="<?= e(t('yasal.liste.etiket')) ?>">
      <p class="lg__toc-h"><?= e(t('yasal.liste.baslik')) ?></p>
      <ol class="lg__toc-list" role="list">
        <?php foreach ($maddeler as $i => $m): ?>
          <li><a href="#madde-<?= $i + 1 ?>" data-toc="madde-<?= $i + 1 ?>"><span class="lg__toc-no"><?= e(t('yasal.liste.madde', ['n' => $i + 1])) ?></span><span class="lg__toc-t"><?= e($m['title']) ?></span></a></li>
        <?php endforeach; ?>
      </ol>
      <button class="lg__all" type="button" data-sade-all aria-pressed="false"><?= e(t('yasal.liste.tumu')) ?></button>
    </nav>

    <article class="lg__doc">
      <?php foreach ($maddeler as $i => $m):
          $paras = $m['paras'];
          $multi = count($paras) > 1 || !empty($m['list']);
      ?>
        <section class="madde" id="madde-<?= $i + 1 ?>" data-madde aria-labelledby="madde-<?= $i + 1 ?>-h">
          <?php if (!empty($m['anchor'])): ?><span id="<?= e($m['anchor']) ?>"></span><?php endif; ?>
          <h2 class="madde__h" id="madde-<?= $i + 1 ?>-h"><?= e($m['title']) ?></h2>
          <?php foreach ($paras as $k => $p): ?>
            <p><?php if ($k === 0): ?><b class="madde__no"><?= e(t('yasal.madde.basi', ['n' => $i + 1])) ?></b> <?php endif; ?><?php if ($multi): ?><span class="madde__f">(<?= $k + 1 ?>)</span> <?php endif; ?><?= $p ?></p>
          <?php endforeach; ?>
          <?php if (!empty($m['list'])): ?>
            <ol class="madde__list" role="list">
              <?php foreach ($m['list'] as [$letter, $item]): ?><li><span class="madde__bent"><?= e($letter) ?>)</span> <?= $item ?></li><?php endforeach; ?>
            </ol>
          <?php endif; ?>
          <?php if (!empty($m['sade'])): ?>
            <details class="sade">
              <summary><svg class="sade__i" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="5.6"/><path d="M12.2 12.2 17.5 17.5"/></svg><span><?= e(t('yasal.madde.sade')) ?></span></summary>
              <p class="sade__body"><?= $m['sade'] ?></p>
            </details>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

      <p class="lg__end"><?= th('yasal.son.cumle', ['tarih_son' => $doc['updated']]) ?></p>
    </article>
  </div>
</section>

<?php if (!empty($doc['next'])): ?>
<section class="section lg-next">
  <div class="wrap">
    <a class="next" href="<?= e($doc['next']['href']) ?>">
      <span class="next__k"><?= e(t('yasal.sonraki.etiket', ['no' => $doc['next']['no']])) ?></span>
      <span class="next__t"><span><?= e($doc['next']['label']) ?></span></span>
      <?= arrow() ?>
    </a>
  </div>
</section>
<?php endif; ?>
    <?php
}
