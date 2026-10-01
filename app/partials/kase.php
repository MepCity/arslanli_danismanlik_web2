<?php
/**
 * Firma kaşesi: ticari unvan, adres, vergi dairesi ve numarası.
 * Türkiye'de resmi yazıların altına basılan kaşenin aynısı; bilgiler config.php'den gelir.
 * @var string $kaseClass  ek sınıf (isteğe bağlı)
 */
$kaseClass = $kaseClass ?? '';
?>
<div class="kase <?= e($kaseClass) ?>" data-stamp aria-label="Firma kaşesi: <?= e(cfg('name')) ?>, <?= e(cfg('address')) ?>, <?= e(cfg('company.tax_office')) ?> Vergi Dairesi <?= e(cfg('company.tax_number')) ?>" role="img">
  <svg viewBox="0 0 400 196" aria-hidden="true">
    <rect x="5" y="5" width="390" height="186" rx="15" fill="none" stroke="currentColor" stroke-width="4"/>
    <rect x="14" y="14" width="372" height="168" rx="9" fill="none" stroke="currentColor" stroke-width="1.6"/>
    <text x="200" y="58" text-anchor="middle" font-size="27" font-weight="800" style="font-stretch:68%;letter-spacing:.02em"><?= e(tr_upper(cfg('name'))) ?></text>
    <line x1="58" y1="73" x2="342" y2="73" stroke="currentColor" stroke-width="1.4"/>
    <text x="200" y="97" text-anchor="middle" font-size="15.5" font-weight="600" style="font-stretch:80%">Yenişehir Mah. Millet Cad. Sümbül Sk. No:10 D:28</text>
    <text x="200" y="118" text-anchor="middle" font-size="15.5" font-weight="600" style="font-stretch:80%">Starport Residence · 34912 Pendik / İSTANBUL</text>
    <text x="200" y="146" text-anchor="middle" font-size="16" font-weight="750" style="font-stretch:78%"><?= e(tr_upper(cfg('company.tax_office'))) ?> V.D. · <?= e(cfg('company.tax_number')) ?></text>
    <text x="200" y="168" text-anchor="middle" font-size="14.5" font-weight="600" style="font-stretch:80%">Tel: <?= e(cfg('phone')) ?></text>
  </svg>
</div>
