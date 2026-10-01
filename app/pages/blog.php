<?php
/** @var string|null $category  /blog/category/{slug} adresinden gelir */
$category = $category ?? null;
$all      = posts();

$slugify = fn(string $s) => strtolower(strtr($s, ['ı' => 'i', 'İ' => 'i', 'ğ' => 'g', 'Ğ' => 'g', 'ü' => 'u', 'Ü' => 'u', 'ş' => 's', 'Ş' => 's', 'ö' => 'o', 'Ö' => 'o', 'ç' => 'c', 'Ç' => 'c', ' ' => '-']));

// Eski sitedeki bölümler: yazısı olmasa da adresleri çalışsın
$cats = ['genel' => 'Genel'];
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
    'title'       => $category && isset($cats[$category]) ? $cats[$category] . ' · Makaleler' : 'Makaleler',
    'description' => 'Arslanlı Bülteni: Ar-Ge yapılanması, kalite belgelendirme, yatırım teşvikleri ve hibe programları üzerine sade ve somut yazılar.',
    'folio'       => 'Evrak 05 · <b>Makaleler</b>',
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
            <span>İstanbul · <?= e(tr_date(date('Y-m-d'))) ?></span>
            <span class="np__motto">Hibe, teşvik ve Ar-Ge mevzuatı üzerine notlar</span>
            <span>Sayı <?= count($all) ?></span>
          </p>
          <h1 class="np__name" id="np-name">Arslanlı Bülteni</h1>
          <nav class="np__sections" aria-label="Bölümler">
            <span class="np__lbl">Bölümler</span>
            <a href="<?= url('blog') ?>"<?= !$category ? ' aria-current="page"' : '' ?>>Tümü</a>
            <?php foreach ($cats as $cs => $cn): ?>
              <a href="<?= url('blog/category/' . $cs) ?>"<?= $category === $cs ? ' aria-current="page"' : '' ?>><?= e($cn) ?></a>
            <?php endforeach; ?>
          </nav>
        </header>

        <?php if ($category): ?>
          <p class="np__note">Bölüm: <b><?= e($cats[$category] ?? $category) ?></b> · <?= count($list) ?> yazı · <a class="link" href="<?= url('blog') ?>">Sayının tamamı</a></p>
        <?php endif; ?>

        <?php if ($lead): ?>
          <article class="np__lead">
            <div class="np__leadtext">
              <p class="np__kicker">Manşet · <?= e($lead['category']) ?> · <?= e(tr_date($lead['date'])) ?> · <?= reading_time($lead['body']) ?> dk okuma</p>
              <h2 class="np__h"><a href="<?= post_url($leadSlug) ?>"><?= $nw(e($lead['title'])) ?></a></h2>
              <p class="np__dek drop"><?= $nw(e($lead['excerpt'])) ?></p>
              <a class="np__more" href="<?= post_url($leadSlug) ?>">Yazının devamı <?= arrow() ?></a>
            </div>
            <figure class="np__fig">
              <a class="halftone" href="<?= post_url($leadSlug) ?>" tabindex="-1" aria-hidden="true">
                <img src="<?= asset('img/blog/' . $lead['image'] . '.webp') ?>" alt="" width="1200" height="800" loading="eager" decoding="async">
              </a>
              <figcaption>Temsilî fotoğraf.</figcaption>
            </figure>
          </article>
        <?php else: ?>
          <div class="np__empty">
            <p class="np__h">Bu bölümde henüz yazı yok.</p>
            <p>Yeni yazılar bu sayfada yayımlanır. <a class="link" href="<?= url('blog') ?>">Sayının tamamına dönün</a>.</p>
          </div>
        <?php endif; ?>
      </div>

      <div class="np__bottom">
        <div class="np__cols<?= $rest ? '' : ' np__cols--solo' ?>">
          <?php foreach ($rest as $slug => $post): ?>
            <article class="np__story">
              <p class="np__kicker"><?= e($post['category']) ?> · <?= e(tr_date($post['date'])) ?> · <?= reading_time($post['body']) ?> dk</p>
              <h3 class="np__sh"><a href="<?= post_url($slug) ?>"><?= $nw(e($post['title'])) ?></a></h3>
              <a class="halftone halftone--sm" href="<?= post_url($slug) ?>" tabindex="-1" aria-hidden="true">
                <img src="<?= asset('img/blog/' . $post['image'] . '.webp') ?>" alt="" width="1200" height="800" loading="lazy" decoding="async">
              </a>
              <p class="np__text drop drop--sm"><?= $nw(e($post['excerpt'])) ?></p>
              <a class="np__more" href="<?= post_url($slug) ?>">Devamı <?= arrow() ?></a>
            </article>
          <?php endforeach; ?>

          <aside class="np__aside" aria-label="Bu sayıda">
            <div class="np__index">
              <p class="np__boxh">Bu sayıda</p>
              <ol role="list">
                <?php foreach ($all as $slug => $post): ?>
                  <li><a href="<?= post_url($slug) ?>"><span><?= $nw(e($post['title'])) ?></span><span class="np__pg"><?= reading_time($post['body']) ?> dk</span></a></li>
                <?php endforeach; ?>
              </ol>
            </div>
            <a class="np__ilan" href="<?= url('haberdarol') ?>">
              <span class="np__ilanh">İlan</span>
              <span class="np__ilant">Yeni bir hibe ya da teşvik çağrısı açıldığında haber almak isteyen işletmelere duyurulur.</span>
              <span class="np__ilanc">Kuponu doldurun <?= arrow() ?></span>
            </a>
          </aside>
        </div>
        <footer class="np__foot"><span><?= e(cfg('name')) ?> yayınıdır.</span><span>Sayfa 1</span></footer>
      </div>
    </div>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('iletisim') ?>">
    <span class="next__k">Sonraki evrak · 06</span>
    <span class="next__t"><span>İletişim</span></span>
    <?= arrow() ?>
  </a>
</div>
