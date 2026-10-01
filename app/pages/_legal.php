<?php
/**
 * Yasal metinler için ortak şablon: cerez.php ve kvkk.php kullanır.
 * Doğrudan adresi yoktur (index.php yalnızca tanımlı sayfaları açar).
 *
 * $doc = [
 *   'no'       => 'ARS-2026/012',
 *   'subject'  => 'Konu satırı',
 *   'h1'       => 'Sayfa başlığı',
 *   'updated'  => 'YYYY-AA-GG',
 *   'lead'     => 'Başlığın altındaki tek cümle (isteğe bağlı)',
 *   'summary'  => ['<mark> kullanılabilen sade cümleler', ...],
 *   'maddeler' => [['title' => 'Başlık', 'paras' => ['güvenli HTML', ...], 'list' => [['a', 'güvenli HTML'], ...] (isteğe bağlı), 'sade' => 'sade Türkçesi (güvenli HTML)'], ...],
 *   'next'     => ['label' => 'KVKK Aydınlatma Metni', 'href' => url(...), 'no' => '13'],
 * ]
 * Metinler sitenin kendi içeriğidir (kullanıcı girdisi değildir); HTML olarak basılır.
 */

function legal_doc(array $doc): void
{
    $maddeler = $doc['maddeler'];
    ?>
<section class="lg-head pagehead" aria-labelledby="lg-title">
  <div class="wrap lg-head__in">
    <div class="lg-head__main">
      <p class="docmeta" data-rise>
        <span>Sayı: <b><?= e($doc['no']) ?></b></span>
        <span>Konu: <b><?= e($doc['subject']) ?></b></span>
        <span>Son güncelleme: <b><?= e(tr_date($doc['updated'])) ?></b></span>
      </p>
      <h1 class="display lg-head__h" id="lg-title" data-rise style="--delay:.06s"><?= e($doc['h1']) ?></h1>
      <?php if (!empty($doc['lead'])): ?><p class="lead lg-head__lead" data-rise style="--delay:.1s"><?= e($doc['lead']) ?></p><?php endif; ?>
    </div>

    <aside class="kisaca" aria-labelledby="kisaca-h" data-rise style="--delay:.14s">
      <p class="kisaca__h" id="kisaca-h">Kısaca</p>
      <p class="kisaca__intro">Aşağıdaki metin hukuki bir metin; dili de öyle. Önce sade özeti:</p>
      <ul class="kisaca__list" role="list">
        <?php foreach ($doc['summary'] as $line): ?><li><?= $line ?></li><?php endforeach; ?>
      </ul>
    </aside>
  </div>
</section>

<section class="lg" aria-label="Metnin tamamı">
  <div class="wrap lg__grid">
    <nav class="lg__toc" aria-label="Maddeler">
      <p class="lg__toc-h">Maddeler</p>
      <ol class="lg__toc-list" role="list">
        <?php foreach ($maddeler as $i => $m): ?>
          <li><a href="#madde-<?= $i + 1 ?>" data-toc="madde-<?= $i + 1 ?>"><span class="lg__toc-no">Madde <?= $i + 1 ?></span><span class="lg__toc-t"><?= e($m['title']) ?></span></a></li>
        <?php endforeach; ?>
      </ol>
      <button class="lg__all" type="button" data-sade-all aria-pressed="false">Tümünü sadeleştir</button>
    </nav>

    <article class="lg__doc">
      <?php foreach ($maddeler as $i => $m):
          $paras = $m['paras'];
          $multi = count($paras) > 1 || !empty($m['list']);
      ?>
        <section class="madde" id="madde-<?= $i + 1 ?>" data-madde aria-labelledby="madde-<?= $i + 1 ?>-h">
          <h2 class="madde__h" id="madde-<?= $i + 1 ?>-h"><?= e($m['title']) ?></h2>
          <?php foreach ($paras as $k => $p): ?>
            <p><?php if ($k === 0): ?><b class="madde__no">Madde <?= $i + 1 ?> –</b> <?php endif; ?><?php if ($multi): ?><span class="madde__f">(<?= $k + 1 ?>)</span> <?php endif; ?><?= $p ?></p>
          <?php endforeach; ?>
          <?php if (!empty($m['list'])): ?>
            <ol class="madde__list" role="list">
              <?php foreach ($m['list'] as [$letter, $item]): ?><li><span class="madde__bent"><?= e($letter) ?>)</span> <?= $item ?></li><?php endforeach; ?>
            </ol>
          <?php endif; ?>
          <?php if (!empty($m['sade'])): ?>
            <details class="sade">
              <summary><svg class="sade__i" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><circle cx="8" cy="8" r="5.6"/><path d="M12.2 12.2 17.5 17.5"/></svg><span>Sade Türkçesi</span></summary>
              <p class="sade__body"><?= $m['sade'] ?></p>
            </details>
          <?php endif; ?>
        </section>
      <?php endforeach; ?>

      <p class="lg__end">Bu metin <?= e(tr_date($doc['updated'])) ?> tarihinde güncellenmiştir. Sorularınız için <a class="link" href="mailto:<?= e(cfg('email')) ?>"><?= e(cfg('email')) ?></a>.</p>
    </article>
  </div>
</section>

<?php if (!empty($doc['next'])): ?>
<section class="section lg-next">
  <div class="wrap">
    <a class="next" href="<?= e($doc['next']['href']) ?>">
      <span class="next__k">Sonraki evrak · <?= e($doc['next']['no']) ?></span>
      <span class="next__t"><span><?= e($doc['next']['label']) ?></span></span>
      <?= arrow() ?>
    </a>
  </div>
</section>
<?php endif; ?>
    <?php
}
