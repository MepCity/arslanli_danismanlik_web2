<?php
/*
 * KVKK Aydınlatma Metni (6698 sayılı Kanun md. 10)
 * TASLAKTIR: Yayına almadan önce hukuk danışmanınızın kontrol etmesi gerekir.
 * Özellikle saklama süreleri ve hizmet sağlayıcılarınızın (barındırma, e-posta) yurt dışında olup olmadığı netleştirilmelidir.
 */
page([
    'id'          => 'legal',
    'theme'       => 'light',
    'title'       => 'KVKK Aydınlatma Metni',
    'description' => 'Arslanlı Yatırım & Danışmanlık web sitesi formları aracılığıyla işlenen kişisel verilere ilişkin aydınlatma metni.',
]);
$mail = '<a class="ulink" href="mailto:' . e(cfg('email')) . '">' . e(cfg('email')) . '</a>';
$sections = [
    'sorumlu' => ['Veri sorumlusu', [
        '6698 sayılı Kişisel Verilerin Korunması Kanunu ("KVKK") uyarınca, kişisel verileriniz veri sorumlusu sıfatıyla ' . e(cfg('name')) . ' ("Arslanlı") tarafından aşağıda açıklanan kapsamda işlenmektedir.',
        'Adres: ' . e(cfg('address')) . '<br>E-posta: ' . $mail . '<br>Telefon: ' . e(cfg('phone')),
    ]],
    'veriler' => ['İşlenen kişisel veriler', [
        'Web sitemizdeki iletişim, bülten ve bilgilendirme formları aracılığıyla şu verileri işleriz: ad, soyad (kimlik); e-posta adresi, telefon numarası, il (iletişim); sektör, firma unvanı ve faaliyet bilgisi (mesleki bilgi); formda paylaştığınız mesaj içeriği; gönderim tarihi, saati ve IP adresi (işlem güvenliği).',
    ]],
    'amac' => ['İşleme amaçları', [
        'Kişisel verilerinizi; talebinize dönüş yapmak ve sizinle iletişim kurmak, işletmenize uygun olabilecek destek programları hakkında ön değerlendirme yapmak, onay vermeniz halinde yeni çağrılar ve programlar hakkında bilgilendirme ve ticari elektronik ileti göndermek, hukuki yükümlülüklerimizi yerine getirmek ve bilgi güvenliğini sağlamak amaçlarıyla işleriz.',
    ]],
    'hukuki' => ['Toplama yöntemi ve hukuki sebepler', [
        'Verileriniz web sitemizdeki formlar aracılığıyla elektronik ortamda toplanır. İşlemenin hukuki sebepleri; KVKK md. 5/2-c (bir sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması), md. 5/2-ç (hukuki yükümlülüğün yerine getirilmesi) ve md. 5/2-f (temel hak ve özgürlüklerinize zarar vermemek kaydıyla meşru menfaatimiz) hükümleridir.',
        'Bülten ve ticari elektronik ileti gönderimi, 6563 sayılı Elektronik Ticaretin Düzenlenmesi Hakkında Kanun kapsamında verdiğiniz onaya dayanır. Bu onayı dilediğiniz zaman, ücretsiz olarak geri alabilirsiniz.',
    ]],
    'aktarim' => ['Kişisel verilerin aktarılması', [
        'Kişisel verileriniz; hizmetin yürütülmesi için destek aldığımız barındırma ve e-posta altyapısı sağlayıcılarına, ticari elektronik ileti onaylarının kaydı için mevzuat gereği İleti Yönetim Sistemi\'ne (İYS) ve talep edilmesi halinde yetkili kamu kurum ve kuruluşlarına, KVKK md. 8 ve 9\'da belirtilen şartlar çerçevesinde aktarılabilir.',
    ]],
    'sure' => ['Saklama süresi', [
        'Kişisel verileriniz, işleme amacının gerektirdiği süre ve ilgili mevzuatta öngörülen süreler boyunca saklanır; bu sürelerin sonunda silinir, yok edilir ya da anonim hale getirilir.',
    ]],
    'haklar' => ['Haklarınız', [
        'KVKK md. 11 uyarınca; kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse buna ilişkin bilgi talep etme, işlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme, yurt içinde veya yurt dışında aktarıldığı üçüncü kişileri bilme, eksik veya yanlış işlenmişse düzeltilmesini isteme, KVKK md. 7 çerçevesinde silinmesini veya yok edilmesini isteme, bu işlemlerin aktarıldığı üçüncü kişilere bildirilmesini isteme, münhasıran otomatik sistemlerle analiz edilmesi nedeniyle aleyhinize bir sonuç doğmasına itiraz etme ve kanuna aykırı işleme nedeniyle zarara uğramanız halinde zararın giderilmesini talep etme haklarına sahipsiniz.',
    ]],
    'basvuru' => ['Başvuru', [
        'Haklarınıza ilişkin taleplerinizi ' . $mail . ' adresine e-posta ile ya da yukarıdaki adresimize yazılı olarak iletebilirsiniz. Başvurunuz, niteliğine göre en geç otuz gün içinde sonuçlandırılır.',
        'Son güncelleme: ' . e(tr_date('2026-10-02')),
    ]],
];
?>
<section class="lg page-hero" data-theme="light">
  <div class="wrap">
    <h1 class="d2" data-split="intro">KVKK Aydınlatma Metni</h1>
  </div>
</section>
<section class="lg-body section" data-theme="light" aria-label="Aydınlatma metni">
  <div class="wrap lg__grid">
    <nav class="lg__toc" aria-label="İçindekiler">
      <p class="tag">İçindekiler</p>
      <ol>
        <?php foreach ($sections as $id => [$h]): ?><li><a href="#<?= $id ?>" data-toc><?= e($h) ?></a></li><?php endforeach; ?>
      </ol>
    </nav>
    <div class="prose lg__prose">
      <?php foreach ($sections as $id => [$h, $ps]): ?>
        <section id="<?= $id ?>" class="lg__sec" data-toc-target>
          <h2><?= e($h) ?></h2>
          <?php foreach ($ps as $para): ?><p><?= $para ?></p><?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    </div>
  </div>
</section>
