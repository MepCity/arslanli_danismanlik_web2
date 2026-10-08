<?php
/** @var string|null $category  /blog/category/{slug} adresinden gelir */
$category = $category ?? null;
$all      = posts();

$slugify = fn(string $s) => strtolower(strtr($s, ['ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u', 'ş' => 's', 'Ş' => 's', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c', ' ' => '-']));

// Eski sitedeki bölümler: yazısı olmasa da adresleri çalışsın
$cats = ['genel' => t('blog.mast.genel')];
foreach ($all as $p) {
    $cats[$slugify($p['category'])] = $p['category'];
}

// "Ar-Ge" satır sonunda tireden bölünmesin
$nw = fn(string $html) => preg_replace('/Ar-Ge[^\s<&]*/u', '<span class="nw">$0</span>', $html);

$list = $all;
if ($category) {
    if (!isset($cats[$category]) && is_file(APP . '/pages/404.php')) {
        not_found();
    }
    $list = array_filter($all, fn($p) => $slugify($p['category']) === $category);
}

page([
    'id'          => 'blog',
    'title'       => $category && isset($cats[$category]) ? t('blog.seo.baslik_bolum', ['bolum' => $cats[$category], 'sayfa' => pg_name('blog')]) : pg_name('blog'),
    'description' => $category && isset($cats[$category]) ? t('blog.seo.description_bolum', ['bolum' => $cats[$category], 'n' => count($list)]) : t('blog.seo.description'),
    'folio'       => pg_folio('blog'),
]);

$leadSlug = array_key_first($list);
$lead     = $leadSlug ? $list[$leadSlug] : null;
$rest     = array_slice($list, 1, null, true);
?>

<section class="gazete pagehead" aria-labelledby="np-name">
  <div class="wrap">
    <div class="np">
      <div class="np__top">
        <header class="np__mast">
          <p class="np__strip">
            <span><?= e(t('blog.mast.sehir', ['tarih_uzun' => tr_date(date('Y-m-d'))])) ?></span>
            <span class="np__motto"><?= e(t('blog.mast.slogan')) ?></span>
            <span><?= e(t('blog.mast.sayi', ['n' => count($all)])) ?></span>
          </p>
          <h1 class="np__name" id="np-name"><?= e(t('blog.mast.ad')) ?></h1>
          <nav class="np__sections" aria-label="<?= e(t('blog.mast.bolumler_etiket')) ?>">
            <span class="np__lbl"><?= e(t('blog.mast.bolumler')) ?></span>
            <a href="<?= url('blog') ?>"<?= !$category ? ' aria-current="page"' : '' ?>><?= e(t('blog.mast.tumu')) ?></a>
            <?php foreach ($cats as $cs => $cn): ?>
              <a href="<?= url('blog/category/' . $cs) ?>"<?= $category === $cs ? ' aria-current="page"' : '' ?>><?= e($cn) ?></a>
            <?php endforeach; ?>
          </nav>
        </header>

        <?php if ($category): ?>
          <p class="np__note"><?= th('blog.mast.bolum_notu', ['bolum' => $cats[$category] ?? $category, 'n' => count($list), 'baglanti' => ['html' => '<a class="link" href="' . url('blog') . '">' . e(t('blog.mast.bolum_notu_baglanti')) . '</a>']]) ?></p>
        <?php endif; ?>

        <?php if ($lead): ?>
          <article class="np__lead">
            <div class="np__leadtext">
              <p class="np__kicker"><?= e(t('blog.manset.kunye', ['bolum' => $lead['category'], 'tarih_uzun' => tr_date($lead['date']), 'dk' => reading_time($lead['body'])])) ?></p>
              <h2 class="np__h"><a href="<?= post_url($leadSlug) ?>"><?= $nw(e($lead['title'])) ?></a></h2>
              <p class="np__dek drop"><?= $nw(e($lead['excerpt'])) ?></p>
              <a class="np__more" href="<?= post_url($leadSlug) ?>"><?= e(t('blog.manset.devam')) ?> <?= arrow() ?></a>
            </div>
            <figure class="np__fig">
              <a class="halftone" href="<?= post_url($leadSlug) ?>" tabindex="-1" aria-hidden="true">
                <img src="<?= asset('img/blog/' . $lead['image'] . '.webp') ?>" alt="" width="1200" height="800" loading="eager" decoding="async">
              </a>
              <figcaption><?= e(t('blog.manset.altyazi')) ?></figcaption>
            </figure>
          </article>
        <?php else: ?>
          <div class="np__empty">
            <p class="np__h"><?= e(t('blog.bos.baslik')) ?></p>
            <p><?= th('blog.bos.not', ['baglanti' => ['html' => '<a class="link" href="' . url('blog') . '">' . e(t('blog.bos.baglanti')) . '</a>']]) ?></p>
          </div>
        <?php endif; ?>
      </div>

      <div class="np__bottom">
        <div class="np__cols<?= $rest ? '' : ' np__cols--solo' ?>">
          <?php foreach ($rest as $slug => $post): ?>
            <article class="np__story">
              <p class="np__kicker"><?= e(t('blog.sutun.kunye', ['bolum' => $post['category'], 'tarih_uzun' => tr_date($post['date']), 'dk' => reading_time($post['body'])])) ?></p>
              <h3 class="np__sh"><a href="<?= post_url($slug) ?>"><?= $nw(e($post['title'])) ?></a></h3>
              <a class="halftone halftone--sm" href="<?= post_url($slug) ?>" tabindex="-1" aria-hidden="true">
                <img src="<?= asset('img/blog/' . $post['image'] . '.webp') ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
              </a>
              <p class="np__text drop drop--sm"><?= $nw(e($post['excerpt'])) ?></p>
              <a class="np__more" href="<?= post_url($slug) ?>"><?= e(t('blog.sutun.devam')) ?> <?= arrow() ?></a>
            </article>
          <?php endforeach; ?>

          <aside class="np__aside" aria-label="<?= e(t('blog.yan.etiket')) ?>">
            <div class="np__index">
              <p class="np__boxh"><?= e(t('blog.yan.baslik')) ?></p>
              <ol role="list">
                <?php foreach ($all as $slug => $post): ?>
                  <li><a href="<?= post_url($slug) ?>"><span><?= $nw(e($post['title'])) ?></span><span class="np__pg"><?= e(t('blog.yan.dk', ['dk' => reading_time($post['body'])])) ?></span></a></li>
                <?php endforeach; ?>
              </ol>
            </div>
<?php if (feature('bulten')): ?>
            <a class="np__ilan" href="<?= url('haberdarol') ?>">
              <span class="np__ilanh"><?= e(t('blog.ilan.baslik')) ?></span>
              <span class="np__ilant"><?= e(t('blog.ilan.metin')) ?></span>
              <span class="np__ilanc"><?= e(t('blog.ilan.dugme')) ?> <?= arrow() ?></span>
            </a>
<?php endif; ?>
          </aside>
        </div>
        <footer class="np__foot"><span><?= e(t('blog.alt.yayin')) ?></span><span><?= e(t('blog.alt.sayfa')) ?></span></footer>
      </div>
    </div>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k"><?= e(t('blog.sonraki.etiket', ['no' => pg_no('iletisim')])) ?></span>
    <span class="next__t"><span><?= e(pg_name('iletisim')) ?></span></span>
    <?= arrow() ?>
  </a>
</div>
