<?php
$refs = site('refs');
page([
    'id'          => 'refs',
    'title'       => 'Referanslar',
    'description' => 'Hibe, teşvik ve Ar-Ge başvurularında dosyasını hazırladığımız kurumlardan bazıları: Pilot Seating, Tork, CNK Havacılık, Acar Kaporta, Sacform, Mado, NSK ve diğerleri.',
    'folio'       => 'Evrak 04 · <b>Referanslar</b>',
]);

// JavaScript yokken de masada birkaç kaşe görünsün: [sıra, x%, y%, açı, mürekkep]
$preset = [
    [0, 21, 30, -6, 'blue'],
    [1, 57, 22, 4, 'red'],
    [2, 37, 66, 3, 'violet'],
    [3, 76, 62, -5, 'blue'],
];
$slugs = array_keys($refs);
?>

<section class="kmasa pagehead" aria-labelledby="refs-title">
  <div class="wrap">
    <div class="kmasa__head">
      <div>
        <p class="docmeta"><span>Sayı: <b>ARS-<?= date('Y') ?>/004</b></span><span>Konu: <b>Referanslar</b></span></p>
        <h1 class="serif-display kmasa__h" id="refs-title">Dosyasını hazırladığımız kurumlardan bazıları.</h1>
      </div>
      <div class="kmasa__side">
        <p class="kmasa__p">Aşağıdaki sümen sizin. Masanın üzerinde bir yere tıkladığınızda sıradaki kurumun kaşesi oraya basılır. Kurumların tam listesi masanın altındaki kayıt defterinde.</p>
        <div class="kmasa__act">
          <button class="btn btn--ink btn--sm" type="button" data-stamp-all>Hepsini bas</button>
          <button class="btn btn--sm" type="button" data-stamp-one>Bir kaşe bas</button>
          <button class="btn btn--sm" type="button" data-clear>Masayı temizle</button>
        </div>
        <p class="kmasa__next" aria-live="polite">Sıradaki kaşe: <b data-next-name><?= e($refs[$slugs[count($preset) % count($slugs)]]) ?></b></p>
      </div>
    </div>
  </div>

  <div class="wrap">
    <div class="sumen">
      <i class="sumen__side" aria-hidden="true"></i>
      <div class="desk" data-desk aria-hidden="true">
        <p class="desk__hint only-fine">Tıklayın, kaşe basılsın.</p>
        <p class="desk__hint only-touch">Dokunun, kaşe basılsın.</p>
        <div class="desk__layer" data-layer>
          <?php foreach ($preset as [$i, $x, $y, $rot, $ink]): $slug = $slugs[$i]; ?>
            <span class="imp imp--<?= $ink ?>" data-preset style="left:<?= $x ?>%;top:<?= $y ?>%;--rot:<?= $rot ?>deg">
              <span class="imp__logo" style="--src:url('<?= asset('img/refs/ink/' . $slug . '.webp') ?>')"></span>
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
      fn($slug, $name) => ['slug' => $slug, 'name' => $name, 'src' => asset('img/refs/ink/' . $slug . '.webp')],
      $slugs,
      array_values($refs)
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
</section>

<section class="defter section" aria-labelledby="defter-title">
  <div class="wrap">
    <header class="defter__head">
      <p class="label">Kayıt defteri</p>
      <h2 class="display h2" id="defter-title">Kurumlar, sırasıyla.</h2>
    </header>

    <div class="book">
      <?php foreach (array_chunk($refs, (int) ceil(count($refs) / 2), true) as $p => $chunk): ?>
        <div class="book__page">
          <p class="book__cols" aria-hidden="true"><span>Sıra</span><span>Kurum</span><span>Kaşe</span></p>
          <ol class="ledger" role="list" start="<?= $p * (int) ceil(count($refs) / 2) + 1 ?>">
            <?php $n = $p * (int) ceil(count($refs) / 2); foreach ($chunk as $slug => $name): $n++; ?>
              <li class="ledger__row">
                <span class="ledger__no" aria-hidden="true"><?= nn($n) ?></span>
                <span class="ledger__name"><?= e($name) ?></span>
                <span class="ledger__ink" aria-hidden="true" style="--src:url('<?= asset('img/refs/ink/' . $slug . '.webp') ?>')"></span>
              </li>
            <?php endforeach; ?>
          </ol>
          <p class="book__folio" aria-hidden="true"><?= $p + 1 ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url(blog_on() ? 'blog' : 'duyurular') ?>">
    <span class="next__k">Sonraki evrak · 05</span>
    <span class="next__t"><span><?= blog_on() ? 'Makaleler' : 'Duyurular' ?></span></span>
    <?= arrow() ?>
  </a>
</div>
