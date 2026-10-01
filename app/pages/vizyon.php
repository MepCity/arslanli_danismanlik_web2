<?php
$v = site('vision');
page([
    'id'          => 'vision',
    'title'       => 'Vizyonumuz',
    'description' => 'Vizyonumuzu, kuruluşumuzun otuzuncu yılında, ' . $v['open_year'] . '’de açılmak üzere bir mektuba yazdık: işletmelerin Ar-Ge ve yenilik kapasitesi, zamanında bilgi ve yeni nesil destek programları.',
    'folio'       => 'Evrak 09 · <b>Vizyonumuz</b>',
]);
$seal = 'M93.2 46.3Q94.0 50.0 93.9 53.9Q93.8 57.7 93.6 61.8Q93.4 65.8 90.8 68.9Q88.1 72.0 86.0 75.2Q83.9 78.4 82.3 82.5Q80.7 86.6 76.8 88.2Q72.9 89.7 69.0 90.7Q65.1 91.6 61.5 92.9Q57.8 94.1 53.9 94.9Q50.0 95.6 46.3 93.6Q42.7 91.6 38.9 91.1Q35.2 90.6 32.0 88.6Q28.9 86.6 24.8 85.8Q20.6 85.0 18.0 82.0Q15.4 79.0 14.5 75.0Q13.6 71.0 10.0 68.5Q6.3 65.9 5.3 62.0Q4.3 58.1 4.7 54.0Q5.1 50.0 3.9 45.8Q2.8 41.7 6.4 38.6Q10.0 35.4 11.9 32.3Q13.7 29.1 14.9 25.3Q16.0 21.5 19.5 19.6Q22.9 17.8 25.8 15.4Q28.6 13.0 32.0 11.3Q35.3 9.6 39.0 9.2Q42.7 8.7 46.4 5.7Q50.0 2.8 54.1 3.2Q58.2 3.6 61.9 5.3Q65.7 6.9 68.9 9.3Q72.1 11.6 75.5 13.6Q78.8 15.6 81.4 18.6Q83.9 21.6 86.4 24.5Q89.0 27.5 90.1 31.2Q91.3 35.0 91.9 38.7Q92.5 42.5 93.2 46.3Z';
$openNote = tr_upper($v['open_year'] . '’de açılacaktır');
?>

<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <defs>
    <radialGradient id="vz-wax" cx="40%" cy="34%" r="70%">
      <stop offset="0" stop-color="#d9493c"/>
      <stop offset=".55" stop-color="#b02a20"/>
      <stop offset="1" stop-color="#7c1812"/>
    </radialGradient>
    <clipPath id="vz-cl" clipPathUnits="userSpaceOnUse"><path d="M0 0H50L46 18 54 34 44 52 56 70 48 86 52 100H0Z"/></clipPath>
    <clipPath id="vz-cr" clipPathUnits="userSpaceOnUse"><path d="M50 0H100V100H52L48 86 56 70 44 52 54 34 46 18Z"/></clipPath>
    <symbol id="vz-seal" viewBox="0 0 100 100">
      <path d="<?= $seal ?>" fill="url(#vz-wax)"/>
      <circle cx="50" cy="50" r="31" fill="none" stroke="rgba(60,6,4,.35)" stroke-width="3"/>
      <circle cx="49.2" cy="49" r="31" fill="none" stroke="rgba(255,190,170,.22)" stroke-width="1.4"/>
      <text x="49" y="62" text-anchor="middle" font-size="36" font-family="Newsreader Display, Georgia, serif" fill="rgba(255,200,185,.25)">A</text>
      <text x="50" y="63" text-anchor="middle" font-size="36" font-family="Newsreader Display, Georgia, serif" fill="rgba(70,8,5,.42)">A</text>
      <ellipse cx="36" cy="28" rx="13" ry="6" fill="rgba(255,255,255,.18)" transform="rotate(-24 36 28)"/>
    </symbol>
  </defs>
</svg>

<section class="vz pagehead" data-vz data-year="<?= $v['open_year'] ?>" aria-labelledby="vz-title">
  <div class="wrap vz__wrap">
    <header class="vz__head">
      <p class="docmeta"><span>Sayı: <b>ARS-<?= date('Y') ?>/009</b></span><span>Konu: <b>Vizyonumuz</b></span><span>Açılış: <b><?= $v['open_year'] ?></b></span></p>
      <h1 class="display vz__h" id="vz-title">Vizyonumuz</h1>
      <p class="lead vz__lead">Vizyonumuzu, kuruluşumuzun otuzuncu yılında, <?= $v['open_year'] ?>’de açılmak üzere bir mektuba yazdık. Beklemek istemezseniz şimdi açabilirsiniz.</p>
    </header>

    <div class="vz__stage" data-stage>
      <div class="env" data-env>
        <div class="env__back" aria-hidden="true"></div>
        <svg class="env__pocket" viewBox="0 0 1000 618" preserveAspectRatio="none" aria-hidden="true">
          <path d="M0 0L512 352 0 618Z" fill="#e6d7b5"/>
          <path d="M1000 0L488 352 1000 618Z" fill="#e3d3b0"/>
          <path d="M0 618L500 262 1000 618Z" fill="#ecdfc0"/>
          <path d="M0 618L500 262 1000 618" fill="none" stroke="rgba(90,70,30,.16)" stroke-width="2"/>
          <path d="M0 0L512 352M1000 0L488 352" fill="none" stroke="rgba(90,70,30,.1)" stroke-width="2"/>
        </svg>
        <p class="env__to" aria-hidden="true">
          <span>Alıcı:</span> <em><?= e(cfg('name')) ?></em><br>
          <span>Adres:</span> <em>İstanbul, <?= $v['open_year'] ?></em>
        </p>
        <div class="env__flap" data-flap aria-hidden="true">
          <i class="env__flap-out"></i>
          <i class="env__flap-in"></i>
        </div>
        <button class="seal" type="button" data-seal tabindex="-1" aria-hidden="true">
          <svg class="seal__half seal__half--l" viewBox="0 0 100 100"><use href="#vz-seal" clip-path="url(#vz-cl)"/></svg>
          <svg class="seal__half seal__half--r" viewBox="0 0 100 100"><use href="#vz-seal" clip-path="url(#vz-cr)"/></svg>
        </button>
        <div class="env__note" aria-hidden="true"><div class="kase kase--red" data-stamp style="--rot:-9deg">
          <svg viewBox="0 0 330 92">
            <rect x="4" y="4" width="322" height="84" rx="8" fill="none" stroke="currentColor" stroke-width="3.4"/>
            <rect x="11" y="11" width="308" height="70" rx="4" fill="none" stroke="currentColor" stroke-width="1.3"/>
            <text x="165" y="50" text-anchor="middle" font-size="27" font-weight="800" style="font-stretch:66%;letter-spacing:.03em"><?= e($openNote) ?></text>
            <text x="165" y="70" text-anchor="middle" font-size="13" font-weight="650" style="font-stretch:82%;letter-spacing:.12em">KURULUŞUMUZUN 30. YILI</text>
          </svg>
        </div></div>
      </div>
      <button class="btn btn--ink vz__open" type="button" data-open aria-controls="mektup" aria-expanded="false">Mektubu şimdi açın <?= arrow() ?></button>
    </div>

    <article class="letter" id="mektup" tabindex="-1" aria-label="Vizyon mektubu" data-letter>
      <div class="letter__in">
        <header class="letter__head">
          <span class="letter__logo"><?php require APP . '/partials/logo.php'; ?></span>
          <span class="letter__date">İstanbul, Ekim 2026</span>
        </header>
        <p class="letter__subj">Konu: <b>Vizyonumuz</b> · Açılış tarihi: <b><?= $v['open_year'] ?></b></p>
        <p class="letter__greet"><?= e($v['greeting']) ?></p>
        <p class="letter__p"><?= e($v['intro']) ?></p>
        <ol class="letter__list" role="list">
          <?php foreach ($v['items'] as $i => $item): ?>
            <li><span class="letter__no" aria-hidden="true"><?= $i + 1 ?></span><span><?= e($item) ?></span></li>
          <?php endforeach; ?>
        </ol>
        <p class="letter__p letter__close"><?= e($v['closing']) ?></p>
        <p class="letter__sign"><em><?= e(cfg('name')) ?></em><span>İstanbul, Ekim 2026</span></p>
      </div>
    </article>
  </div>
</section>

<div class="wrap">
  <a class="next" href="<?= url('kurumsal/mihenk-taslarimiz') ?>">
    <span class="next__k">Sonraki evrak · 10</span>
    <span class="next__t"><span>Mihenk Taşlarımız</span></span>
    <?= arrow() ?>
  </a>
</div>
