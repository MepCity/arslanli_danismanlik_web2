<?php
/**
 * Firma kaşesi: ticari unvan, adres, vergi dairesi ve numarası.
 * Türkiye'de resmi yazıların altına basılan kaşenin aynısı; bilgiler config.php'den gelir.
 * @var string $kaseClass  ek sınıf (isteğe bağlı)
 */
$kaseClass = $kaseClass ?? '';
[$kaseAdres1, $kaseAdres2] = array_pad(explode("\n", t('genel.kase.adres')), 2, '');   // iki satır: genel.kase.adres
?>
<div class="kase <?= e($kaseClass) ?>" data-stamp aria-label="<?= e(t('genel.kase.label')) ?>" role="img">
  <svg viewBox="0 0 400 196" aria-hidden="true">
    <rect x="5" y="5" width="390" height="186" rx="15" fill="none" stroke="currentColor" stroke-width="4"/>
    <rect x="14" y="14" width="372" height="168" rx="9" fill="none" stroke="currentColor" stroke-width="1.6"/>
    <text x="200" y="58" text-anchor="middle" font-size="27" font-weight="800" style="font-stretch:68%;letter-spacing:.02em"><?= e(t('genel.kase.unvan')) ?></text>
    <line x1="58" y1="73" x2="342" y2="73" stroke="currentColor" stroke-width="1.4"/>
    <text x="200" y="97" text-anchor="middle" font-size="15.5" font-weight="600" style="font-stretch:80%"><?= e($kaseAdres1) ?></text>
    <text x="200" y="118" text-anchor="middle" font-size="15.5" font-weight="600" style="font-stretch:80%"><?= e($kaseAdres2) ?></text>
    <text x="200" y="146" text-anchor="middle" font-size="16" font-weight="750" style="font-stretch:78%"><?= e(t('genel.kase.vergi')) ?></text>
    <text x="200" y="168" text-anchor="middle" font-size="14.5" font-weight="600" style="font-stretch:80%"><?= e(t('genel.kase.tel')) ?></text>
  </svg>
</div>
