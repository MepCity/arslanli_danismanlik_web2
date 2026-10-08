<?php
$refs = refs_map();
page([
    'id'          => 'refs',
    'title'       => pg_name('referans'),
    'description' => t('referans.seo.description'),
    'folio'       => pg_folio('referans'),
]);

// JavaScript yokken de masada birkaç kaşe görünsün: [sıra, x%, y%, açı, mürekkep]
$preset = [
    [0, 21, 30, -6, 'blue'],
    [1, 57, 22, 4, 'red'],
    [2, 37, 66, 3, 'violet'],
    [3, 76, 62, -5, 'blue'],
];
$slugs = array_keys($refs);
$preset = array_values(array_filter($preset, fn($p) => isset($slugs[$p[0]])));   // az referans olsa da boş kaşe ve sıfıra bölme olmasın
?>

<section class="kmasa pagehead" aria-labelledby="refs-title">
  <div class="wrap">
    <div class="kmasa__head">
      <div>
        <p class="docmeta"><span><?= th('referans.hero.sayi') ?></span><span><?= th('referans.hero.konu') ?></span></p>
        <h1 class="serif-display kmasa__h" id="refs-title"><?= e(t('referans.hero.baslik')) ?></h1>
      </div>
      <div class="kmasa__side">
        <p class="kmasa__p"><?= e(t('referans.masa.giris')) ?></p>
        <div class="kmasa__act">
          <button class="btn btn--ink btn--sm" type="button" data-stamp-all><?= e(t('referans.masa.hepsi')) ?></button>
          <button class="btn btn--sm" type="button" data-stamp-one><?= e(t('referans.masa.bir')) ?></button>
          <button class="btn btn--sm" type="button" data-clear><?= e(t('referans.masa.temizle')) ?></button>
        </div>
        <p class="kmasa__next" aria-live="polite"><?= th('referans.masa.siradaki', ['ad' => ['html' => '<b data-next-name>' . e($slugs ? $refs[$slugs[count($preset) % count($slugs)]] : '') . '</b>']]) ?></p>
      </div>
    </div>
  </div>

  <div class="wrap">
    <div class="sumen">
      <i class="sumen__side" aria-hidden="true"></i>
      <div class="desk" data-desk aria-hidden="true">
        <p class="desk__hint only-fine"><?= e(t('referans.masa.ipucu_fare')) ?></p>
        <p class="desk__hint only-touch"><?= e(t('referans.masa.ipucu_dokunma')) ?></p>
        <div class="desk__layer" data-layer>
          <?php foreach ($preset as [$i, $x, $y, $rot, $ink]): $slug = $slugs[$i]; ?>
            <span class="imp imp--<?= $ink ?>" data-preset style="left:<?= $x ?>%;top:<?= $y ?>%;--rot:<?= $rot ?>deg">
              <span class="imp__logo" style="--src:url('<?= e(ref_ink_url($slug)) ?>')"></span>
            </span>
          <?php endforeach; ?>
        </div>
        <div class="tool" data-tool>
          <div class="tool__in">
            <i class="tool__knob"></i>
            <i class="tool__neck"></i>
            <span class="tool__block"><span class="tool__label"><span class="tool__logo" data-tool-logo></span></span></span>
            <i class="tool__rubber"></i>
          </div>
        </div>
      </div>
      <i class="sumen__side" aria-hidden="true"></i>
    </div>
  </div>

  <script type="application/json" data-refs><?= json_encode(array_map(
      fn($slug, $name) => ['slug' => $slug, 'name' => $name, 'src' => ref_ink_url($slug)],
      $slugs,
      array_values($refs)
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</section>

<section class="defter section" aria-labelledby="defter-title">
  <div class="wrap">
    <header class="defter__head">
      <p class="label"><?= e(t('referans.defter.etiket')) ?></p>
      <h2 class="display h2" id="defter-title"><?= e(t('referans.defter.baslik')) ?></h2>
    </header>

    <div class="book">
      <?php foreach (array_chunk($refs, (int) max(1, ceil(count($refs) / 2)), true) as $p => $chunk): ?>
        <div class="book__page">
          <p class="book__cols" aria-hidden="true"><span><?= e(t('referans.defter.sutun_sira')) ?></span><span><?= e(t('referans.defter.sutun_kurum')) ?></span><span><?= e(t('referans.defter.sutun_kase')) ?></span></p>
          <ol class="ledger" role="list" start="<?= $p * (int) max(1, ceil(count($refs) / 2)) + 1 ?>">
            <?php $n = $p * (int) max(1, ceil(count($refs) / 2)); foreach ($chunk as $slug => $name): $n++; ?>
              <li class="ledger__row">
                <span class="ledger__no" aria-hidden="true"><?= nn($n) ?></span>
                <span class="ledger__name"><?= e($name) ?></span>
                <span class="ledger__ink" aria-hidden="true" style="--src:url('<?= e(ref_ink_url($slug)) ?>')"></span>
              </li>
            <?php endforeach; ?>
          </ol>
          <p class="book__folio" aria-hidden="true"><?= $p + 1 ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($nx = pg_next('referans')): ?>
<div class="wrap">
  <a class="next" href="<?= url($nx['path']) ?>">
    <span class="next__k"><?= e(t('referans.sonraki.etiket', ['no' => $nx['nn']])) ?></span>
    <span class="next__t"><span><?= e($nx['label']) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
<?php endif; ?>
