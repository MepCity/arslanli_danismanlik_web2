<?php
declare(strict_types=1);

/**
 * Yönetim paneli giriş noktası: /yonetim/...
 * @var string $path  index.php'den gelen yol (ör. "yonetim/hizmetler/tubitak-1989")
 */

require __DIR__ . '/core.php';
adm_boot();

$sub     = trim(substr($path, strlen('yonetim')), '/');
$parts   = $sub === '' ? [] : explode('/', $sub);
$section = $parts[0] ?? '';
$rest    = array_slice($parts, 1);
$method  = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* ---------- Giriş ---------- */

if ($section === 'giris' && $method === 'POST') {
    if (adm_throttle()) {
        adm_flash('Çok fazla hatalı deneme yapıldı. 15 dakika sonra tekrar deneyin.', 'err');
        adm_go();
    }
    if (adm_csrf_ok() && password_verify((string) ($_POST['password'] ?? ''), adm_hash())) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        $_SESSION['until'] = time() + 8 * 3600;
        $_SESSION['pw']    = adm_pw_mark();   // şifre değişirse bu oturum geçersiz kalır

// Sırada bekleyen arama motoru bildirimleri (IndexNow) varsa sayfa gönderildikten sonra iletilir.
// Aşama 3A: app/indexnow.php eklenene dek dosya yoktur ve bu adım atlanır.
if (is_file(APP . '/indexnow.php')) {
    require_once APP . '/indexnow.php';
    indexnow_flush_later();
}
        $back = (string) ($_POST['_back'] ?? '');
        adm_go(preg_match('#^[a-z0-9/_.\-]*$#', $back) ? $back : '');
    }
    adm_throttle(true);
    adm_flash('Şifre hatalı.', 'err');
    adm_go();
}

if (!adm_logged()) {
    $err = adm_flash();
    ob_start(); ?>
    <div class="login">
      <div class="login__brand"><img src="<?= asset('admin/logo-light.webp') ?>" alt="<?= e(cfg('name')) ?>"><span>Yönetim paneli</span></div>
      <form class="login__card" method="post" action="<?= adm_url('giris') ?>">
        <span class="login__wave" aria-hidden="true"><?php for ($i = 0; $i < 18; $i++): ?><i style="--h:<?= round(0.25 + 0.75 * abs(sin($i * 0.8) * cos($i * 0.27)), 2) ?>;--i:<?= $i ?>"></i><?php endfor; ?></span>
        <div>
          <h1>Tekrar hoş geldiniz</h1>
          <p>Sitenin içeriğini, duyuruları ve gelen başvuruları buradan yönetirsiniz.</p>
        </div>
        <?php if ($err): ?><p class="login__err" role="alert"><?= e($err[0]) ?></p><?php endif; ?>
        <?= adm_csrf_field() ?>
        <input type="hidden" name="_back" value="<?= e($sub === 'giris' ? '' : $sub) ?>">
        <?= ui_text('password', 'Şifre', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
        <button class="btn btn--block" type="submit">Giriş yap</button>
      </form>
    </div>
    <?php adm_bare('Giriş', (string) ob_get_clean());
}

$_SESSION['until'] = time() + 8 * 3600;
actor(['type' => 'panel', 'name' => 'Yönetim paneli']);   // bu istekteki değişiklikler geçmişe "panelden" diye yazılır

/* ---------- Çıkış ---------- */

if ($section === 'cikis' && $method === 'POST') {
    if (adm_csrf_ok()) {
        $_SESSION = [];
        session_destroy();
    }
    header('Location: ' . adm_url(), true, 303);
    exit;
}

if ($method === 'POST' && !adm_csrf_ok()) {
    adm_flash('Oturum doğrulanamadı. Sayfayı yenileyip tekrar deneyin.', 'err');
    adm_go($sub);
}

/* ---------- Bölümler ---------- */

// Sonraki aşamalarda uyarlanacak bölümler (bkz. adm_pending_sections, app/admin/layout.php) Genel bakışa yönlendirir
$GLOBALS['adm_defs_only'] = true;
require __DIR__ . '/layout.php';
unset($GLOBALS['adm_defs_only']);

$map = [
    ''            => 'genel',
    'ziyaretciler' => 'ziyaretciler',
    'duyurular'   => 'duyurular',
    'ilanlar'     => 'ilanlar',
    'hizmetler'   => 'hizmetler',
    'blog'        => 'blog',
    'referanslar' => 'referanslar',
    'metinler'    => 'metinler',
    'kurumsal'    => 'kurumsal',
    'kayitlar'    => 'kayitlar',
    'bulten'      => 'bulten',
    'basvurular'  => 'basvurular',
    'cv'          => 'basvurular',   // eski CV indirme adresi
    'gorunurluk'  => 'gorunurluk',
    'ayarlar'     => 'ayarlar',
    'seo'         => 'seo',
    'mcp'         => 'mcp',
    'guvenlik'    => 'guvenlik',
    'gecmis'      => 'gecmis',
];

$file = isset($map[$section]) ? __DIR__ . '/sections/' . $map[$section] . '.php' : null;
if (!$file || !is_file($file) || isset(adm_pending_sections()[$map[$section] ?? ''])) {
    adm_flash('Bu bölüm henüz hazır değil.', 'err');
    adm_go();
}
require $file;
