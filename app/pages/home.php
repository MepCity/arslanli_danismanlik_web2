<?php
$law     = site('hero_law');
$process = site('process');
page([
    'id'          => 'home',
    'description' => 'Hibe ve teşvik mevzuatını sade Türkçeye çeviriyor, TÜBİTAK, KOSGEB, Bakanlık, ihracat ve AB desteklerinde başvuru dosyasını hazırlayıp son ödemeye kadar yürütüyoruz. 2007’den beri İstanbul’da.',
    'folio'       => 'Evrak 01 · <b>Ana sayfa</b>',
    'vendor'      => ['CustomEase'],
]);

$claims = [
    ['“Hibeniz garanti.”', 'Kararı kurum verir. Biz dosyanın eksiksiz ve zamanında olmasını üstleniriz.'],
    ['“Her işletmeye uygun bir destek mutlaka vardır.”', 'Yoksa ilk görüşmede söyleriz.'],
    ['“Belgenizi biz veririz.”', 'ISO belgesini akredite kuruluş, teşvik belgesini Bakanlık verir. Biz hazırlarız.'],
    ['“Son gün yetiştiririz.”', 'Dosya, kapanıştan en az bir hafta önce hazır olur.'],
    ['“Bu desteği herkes alıyor, siz de alırsınız.”', 'Her başvuru kendi şartlarıyla değerlendirilir. Sizinkini baştan okuruz.'],
];

/** Kanun duvarı: iki katman aynı yapıyı paylaşır, böylece mercek altındaki sade metin tam yerine oturur. */
$wall = function (string $mode) use ($law): void { ?>
  <div class="wall wall--<?= $mode ?>" aria-hidden="true">
    <div class="wall__in">
      <div class="wall__head">
        <p class="wall__title"><?= e($law['title']) ?></p>
        <p class="wall__meta"><?php foreach ($law['meta'] as $m): ?><span><?= e($m) ?></span><?php endforeach; ?></p>
      </div>
      <div class="wall__grid">
        <?php foreach ($law['blocks'] as $i => $b): ?>
          <div class="mb" data-block="<?= $i ?>">
            <p class="mb__ref"><?= e($b['ref']) ?></p>
            <div class="mb__body">
              <p class="mb__law"><?= e($b['law']) ?></p>
              <p class="mb__plain"><?= $b['plain'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
<?php };
?>

<!-- 1 · Mercek: kanun metni ve sade Türkçesi -->
<section class="mercek" data-mercek aria-labelledby="home-title">
  <div class="stage" data-stage>
    <?php $wall('law'); $wall('plain'); ?>
    <div class="lens" aria-hidden="true" data-lens>
      <i class="lens__handle"></i>
      <i class="lens__ring"></i>
    </div>
  </div>

  <div class="ustyazi wrap">
    <div class="ustyazi__sheet" data-ustyazi>
      <p class="docmeta"><span>Sayı: <b>ARS-<?= date('Y') ?>/001</b></span><span>Konu: <b>Devlet destekleri hk.</b></span></p>
      <h1 class="display ustyazi__h" id="home-title"><span class="ln">Destek var.</span> <span class="ln">Dili zor.</span></h1>
      <p class="ustyazi__p">2007’den beri hibe ve teşvik başvurularında dosyayı biz hazırlıyor, kabulden son ödemeye kadar biz yürütüyoruz. Arkadaki metin 5746 sayılı Kanun’dan. <span class="only-fine">Merceği üzerinde gezdirin.</span><span class="only-touch">Bir paragrafa dokunun.</span></p>
      <div class="ustyazi__act">
        <a class="btn btn--ink" href="<?= url('iletisim') ?>">Ön görüşme isteyin <?= arrow() ?></a>
        <button class="btn" type="button" data-plain-all aria-pressed="false">Tümünü sadeleştir</button>
      </div>
    </div>
  </div>

  <dl class="sr-only">
    <?php foreach ($law['blocks'] as $b): ?>
      <dt>5746 sayılı Kanun, <?= e($b['ref']) ?>: <?= e($b['law']) ?></dt>
      <dd>Sade Türkçesi: <?= strip_tags($b['plain']) ?></dd>
    <?php endforeach; ?>
  </dl>
</section>

<!-- 2 · Takvim: bir başvurunun sekiz yaprağı -->
<section class="takvim" data-takvim aria-labelledby="takvim-title">
  <div class="takvim__in wrap" data-takvim-pin>
    <div class="takvim__text">
      <p class="label">Bir başvurunun akışı</p>
      <h2 class="display takvim__h" id="takvim-title">Sekiz yaprak.<br>Çoğu bizde.</h2>
      <p class="takvim__lead">Takvimden bir yaprak koptukça dosya bir adım ilerler. Her yaprakta önce bizim yaptığımız iş, altında sizden istediğimiz yazıyor. Alttaki satırın hep kısa olduğunu göreceksiniz.</p>
      <ol class="takvim__toc" role="list" aria-hidden="true">
        <?php foreach ($process as $i => $st): ?>
          <li data-toc="<?= $i ?>"><span><?= nn($i + 1) ?></span><?= e($st['title']) ?></li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div class="pad" data-pad>
      <div class="pad__bind" aria-hidden="true"><i></i><i></i></div>
      <div class="pad__stack">
        <?php foreach ($process as $i => $st): ?>
          <article class="leaf<?= $i === count($process) - 1 ? ' leaf--red' : '' ?>" style="--z:<?= count($process) - $i ?>" data-leaf>
            <header class="leaf__top">
              <span><?= e(tr_upper($st['phase'])) ?></span>
              <span>Yaprak <?= $i + 1 ?> / <?= count($process) ?></span>
            </header>
            <p class="leaf__no" aria-hidden="true"><?= $i + 1 ?></p>
            <h3 class="leaf__t"><?= e($st['title']) ?></h3>
            <dl class="leaf__dl">
              <div><dt>Biz</dt><dd><?= e($st['us']) ?></dd></div>
              <div class="leaf__you"><dt>Siz</dt><dd><?= e($st['you']) ?></dd></div>
            </dl>
            <footer class="leaf__foot" aria-hidden="true" lang="en">arslanlidanismanlik.com</footer>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- 3 · Dosyalar: dizin -->
<section class="dosyalar section" aria-labelledby="dosya-title">
  <div class="wrap">
    <header class="dosyalar__head">
      <p class="label">Dosyalar · 03</p>
      <h2 class="display h2" id="dosya-title">Dokuz alanda dosya hazırlıyoruz.</h2>
      <a class="link ui" href="<?= url('hizmetler') ?>">Dosya dolabını açın</a>
    </header>
    <ol class="dlist" role="list">
      <?php foreach (services() as $slug => $s): ?>
        <li class="drow" style="--c:var(--f-<?= e($s['color']) ?>)">
          <a href="<?= service_url($slug) ?>">
            <span class="drow__tab"><span>03.<?= (int) $s['no'] ?></span></span>
            <span class="drow__t"><?= e($s['title']) ?></span>
            <span class="drow__d"><?= e($s['short']) ?></span>
            <?= arrow('arw drow__arw') ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<!-- 4 · Kırmızı kalem -->
<section class="kalem section" aria-labelledby="kalem-title">
  <div class="wrap kalem__in">
    <header class="kalem__head">
      <p class="label">Düzeltme</p>
      <h2 class="display h2" id="kalem-title">Bu cümleleri bizden duymazsınız.</h2>
    </header>
    <ul class="kalem__list" role="list">
      <?php foreach ($claims as $i => [$claim, $fix]): ?>
        <li class="kalem__row" data-on style="--delay:<?= $i * 0.04 ?>s">
          <p class="kalem__claim"><s class="strike"><?= e($claim) ?></s></p>
          <p class="kalem__fix"><?= e($fix) ?></p>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php if (feature('referanslar')): ?>
<!-- 5 · Kaşeler -->
<section class="kaseler section" aria-labelledby="kaseler-title">
  <div class="wrap">
    <header class="kaseler__head">
      <h2 class="display h2" id="kaseler-title">Dosyasını hazırladığımız kurumlardan bazıları</h2>
      <a class="btn" href="<?= url('referans') ?>">Kaşe masasına geçin <?= arrow() ?></a>
    </header>
    <ul class="kaseler__grid" role="list">
      <?php foreach (site('refs') as $slug => $name): ?>
        <li><span class="inklogo" role="img" aria-label="<?= e($name) ?>" style="--src:url('<?= asset('img/refs/ink/' . $slug . '.webp') ?>')"></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php /* 6 · Bülten: makale sütunları yalnızca Yazılar bölümü açıkken (panel, Görünürlük) */ if (feature('blog')): ?>
<section class="bulten section" aria-labelledby="bulten-title">
  <div class="wrap">
    <header class="bulten__mast">
      <p class="bulten__side">Sayı <?= count(posts()) ?></p>
      <h2 class="bulten__name" id="bulten-title">Arslanlı Bülteni</h2>
      <p class="bulten__side"><?= e(tr_date(date('Y-m-d'))) ?></p>
    </header>
    <div class="bulten__cols">
      <?php foreach (array_slice(posts(), 0, 3, true) as $slug => $post): ?>
        <article class="bulten__col">
          <p class="label"><?= e(tr_date($post['date'])) ?> · <?= reading_time($post['body']) ?> dk</p>
          <h3><a href="<?= post_url($slug) ?>"><?= e($post['title']) ?></a></h3>
          <p><?= e($post['excerpt']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
    <a class="link ui bulten__all" href="<?= url('blog') ?>">Tüm makaleler</a>
  </div>
</section>
<?php endif; ?>

<!-- 7 · Son söz -->
<section class="masa section" aria-labelledby="masa-title">
  <div class="wrap masa__in">
    <h2 class="display masa__h" id="masa-title">Masanızda bir çağrı metni mi var?</h2>
    <div class="masa__side">
      <p class="lead">Gönderin. Okuyup işletmenize uyup uymadığını, uyuyorsa ne kadar sürede ve hangi belgelerle hazırlanacağını söyleyelim.</p>
      <div class="masa__act">
        <a class="btn btn--ink" href="<?= url('iletisim') ?>">Dilekçe yazın <?= arrow() ?></a>
        <a class="btn" href="tel:<?= e(cfg('phone_href')) ?>"><?= e(cfg('phone')) ?></a>
      </div>
    </div>
  </div>
</section>
