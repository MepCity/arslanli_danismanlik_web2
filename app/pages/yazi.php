<?php
/**
 * @var string $slug
 * @var array  $post
 */
$all   = posts();
$keys  = array_keys($all);
$pos   = array_search($slug, $keys, true);
$next  = $keys[($pos + 1) % count($keys)];
$other = array_diff_key($all, [$slug => true]);
$abs   = absolute_url('blog/' . $slug);
$img   = 'img/blog/' . $post['image'] . '.webp';
// "Ar-Ge" satır sonunda tireden bölünmesin
$nw    = fn(string $html) => preg_replace('/Ar-Ge[^\s<&]*/u', '<span class="nw">$0</span>', $html);

page([
    'id'          => 'post',
    'title'       => $post['title'],
    'description' => $post['excerpt'],
    'image'       => absolute_url('assets/' . $img),
    'canonical'   => $abs,
    'folio'       => pg_folio('blog', 'Makale'),
]);

$share = [
    'LinkedIn' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($abs),
    'X'        => 'https://twitter.com/intent/tweet?url=' . rawurlencode($abs) . '&text=' . rawurlencode($post['title']),
    'WhatsApp' => 'https://wa.me/?text=' . rawurlencode($post['title'] . ' ' . $abs),
];
?>

<article class="yazi" aria-labelledby="post-title">
  <header class="yazi__head pagehead">
    <div class="wrap">
      <p class="yazi__back"><a class="link ui" href="<?= url('blog') ?>">Arslanlı Bülteni</a><span aria-hidden="true"> / </span><span><?= e($post['category']) ?></span></p>
      <h1 class="serif-display yazi__h" id="post-title"><?= $nw(nowidow($post['title'])) ?></h1>
      <div class="yazi__meta">
        <p class="docmeta">
          <span>Tarih: <b><time datetime="<?= e($post['date']) ?>"><?= e(tr_date($post['date'])) ?></time></b></span>
          <span>Okuma: <b><?= reading_time($post['body']) ?> dakika</b></span>
          <span>Bölüm: <b><?= e($post['category']) ?></b></span>
        </p>
        <p class="yazi__lead"><?= $nw(e($post['excerpt'])) ?></p>
      </div>
    </div>
  </header>

  <figure class="yazi__fig wrap">
    <div class="halftone">
      <img src="<?= asset($img) ?>" alt="" width="1200" height="800" decoding="async">
    </div>
    <figcaption>Temsilî fotoğraf.</figcaption>
  </figure>

  <div class="yazi__body wrap">
    <div class="pencil" aria-hidden="true" data-pencil>
      <i class="pencil__line"></i>
      <svg class="pencil__tool" viewBox="0 0 22 150">
        <rect x="3" y="0" width="16" height="14" rx="3" fill="#e39a95"/>
        <rect x="2" y="13" width="18" height="12" fill="#b9b5ab"/>
        <rect x="2" y="15.5" width="18" height="1.6" fill="#8e8a80"/>
        <rect x="2" y="21" width="18" height="1.6" fill="#8e8a80"/>
        <rect x="3" y="25" width="16" height="96" fill="#e8c445"/>
        <rect x="9.6" y="25" width="2.8" height="96" fill="#d4ae2f"/>
        <path d="M3 121h16l-8 25z" fill="#e7cba0"/>
        <path d="M8.1 137h5.8L11 147.5z" fill="#2b2b2e"/>
      </svg>
    </div>

    <div class="prose yazi__text" data-text>
      <?= $nw($post['body']) ?>
    </div>

    <footer class="yazi__foot">
      <p class="label">Bu yazıyı paylaşın</p>
      <ul class="yazi__share" role="list">
        <?php foreach ($share as $name => $href): ?>
          <li><a class="link ui" href="<?= e($href) ?>" rel="noopener" target="_blank"><?= e($name) ?></a></li>
        <?php endforeach; ?>
        <li><button class="yazi__copy ui" type="button" data-copy="<?= e($abs) ?>" data-copy-msg="Bağlantı kopyalandı">Bağlantıyı kopyala</button></li>
      </ul>
    </footer>
  </div>
</article>

<?php if ($other): ?>
<section class="yazi__more section" aria-labelledby="more-title">
  <div class="wrap">
    <h2 class="display h3 yazi__moreh" id="more-title">Aynı sayıdan</h2>
    <div class="yazi__list">
      <?php foreach ($other as $oslug => $o): ?>
        <article class="yazi__item">
          <p class="label"><?= e(tr_date($o['date'])) ?> · <?= reading_time($o['body']) ?> dk</p>
          <h3><a href="<?= post_url($oslug) ?>"><?= $nw(e($o['title'])) ?></a></h3>
          <p><?= $nw(e($o['excerpt'])) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="wrap">
  <a class="next" href="<?= post_url($next) ?>">
    <span class="next__k">Sonraki makale</span>
    <span class="next__t next__t--post"><span><?= $nw(nowidow($all[$next]['title'])) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
