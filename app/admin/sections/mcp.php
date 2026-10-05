<?php
/**
 * Yapay zekâ erişimi: kişiye özel erişim anahtarları, bağlantı rehberi, işlem kaydı, bağlantı testi.
 * Sunucu: app/mcp/ (MCP uç noktası /mcp ve OAuth). Anahtarlar storage/mcp/keys.json içinde yalnızca özet olarak saklanır.
 * Adresler: /yonetim/mcp  ·  POST /yonetim/mcp/olustur  ·  POST /yonetim/mcp/kaldir/{id}  ·  POST /yonetim/mcp/test
 */

require_once APP . '/mcp/server.php';

$mcpUrl = mcp_url('mcp');

/** "3 Ekim 2026, 14:05" */
function mcx_when(?string $iso): string
{
    $t = $iso ? (int) strtotime($iso) : 0;
    return $t ? tr_date(date('Y-m-d', $t)) . ', ' . date('H:i', $t) : '';
}

/* ---------- Anahtar oluştur ---------- */
if (($rest[0] ?? '') === 'olustur' && $method === 'POST') {
    $name = mb_substr(trim((string) preg_replace('/\s+/u', ' ', post_str('name', 200))), 0, 80);
    $dur  = post_str('dur', 6);
    $days = ['30' => 30, '90' => 90, '365' => 365, '0' => null];
    $scopes = array_values(array_filter((array) ($_POST['scope'] ?? []), 'is_string'));
    $active = count(array_filter(mcp_keys(), 'mcp_key_active'));
    if ($name === '') {
        adm_flash('Kişinin adını yazın.', 'err');
    } elseif (!array_key_exists($dur, $days)) {
        adm_flash('Geçerlilik süresini seçin.', 'err');
    } elseif ($active >= 50) {
        adm_flash('En fazla 50 etkin erişim olabilir. Kullanılmayanları kaldırın.', 'err');
    } else {
        try {
            [$secret, $rec] = mcp_key_create($name, $scopes, $days[$dur]);
            $_SESSION['mcp_new'] = ['key' => $secret, 'name' => $rec['name'], 'id' => $rec['id']];
            changelog_event('erisim', 'Yapay zekâ erişim anahtarı oluşturuldu: ' . $rec['name'] . ' (' . implode(', ', array_map(fn($x) => mcp_scopes()[$x][0], $rec['scopes'])) . ')');
            adm_flash('Erişim anahtarı oluşturuldu. Anahtar yalnızca bir kez gösterilir.');
        } catch (Throwable $ex) {
            adm_flash('Anahtar oluşturulamadı: storage klasörü yazılabilir mi?', 'err');
        }
    }
    adm_go('mcp');
}

/* ---------- Erişimi kaldır ---------- */
if (($rest[0] ?? '') === 'kaldir' && $method === 'POST') {
    $id = (string) ($rest[1] ?? '');
    $k  = preg_match('/^[a-f0-9]{12}$/', $id) ? mcp_key_get($id) : null;
    if ($k && mcp_key_revoke($id)) {
        changelog_event('erisim', 'Yapay zekâ erişimi kaldırıldı: ' . $k['name']);
        adm_flash($k['name'] . ' kişisinin erişimi kaldırıldı. Bu anahtarla bağlı tüm uygulamaların bağlantısı hemen kesildi.');
    } else {
        adm_flash('Erişim bulunamadı ya da zaten kaldırılmış.', 'err');
    }
    adm_go('mcp');
}

/* ---------- Bağlantıyı test et: sunucunun kendi işleyicisi, ağ kullanılmaz ---------- */
if (($rest[0] ?? '') === 'test' && $method === 'POST') {
    $t0 = microtime(true);
    $res = ['ok' => false, 'msg' => ''];
    try {
        $pr  = ['key_id' => 'test', 'name' => 'Bağlantı testi', 'scopes' => array_keys(mcp_scopes()), 'client' => 'Yönetim paneli', 'via' => 'panel'];
        $ctx = ['principal' => $pr, 'audit' => false, 'rate' => false];
        $call = function (string $m, array $p = []) use ($ctx): array {
            [$st, $body] = mcp_handle(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $m, 'params' => $p ?: new stdClass()]), $ctx);
            if ($st !== 200 || isset($body['error'])) {
                throw new RuntimeException($m . ': ' . ($body['error']['message'] ?? 'HTTP ' . $st));
            }
            return (array) $body['result'];
        };
        $init  = $call('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => new stdClass(), 'clientInfo' => ['name' => 'panel', 'version' => '1']]);
        $tools = $call('tools/list');
        $ress  = $call('resources/list');
        $prom  = $call('prompts/list');
        $day   = $call('tools/call', ['name' => 'site_durumu', 'arguments' => new stdClass()]);
        if (!empty($day['isError'])) {
            throw new RuntimeException('site_durumu: ' . ($day['content'][0]['text'] ?? ''));
        }
        $res = ['ok' => true, 'tools' => count($tools['tools']), 'resources' => count($ress['resources']), 'prompts' => count($prom['prompts']),
            'proto' => (string) $init['protocolVersion'], 'ms' => (int) round((microtime(true) - $t0) * 1000)];
    } catch (Throwable $ex) {
        $res = ['ok' => false, 'msg' => $ex->getMessage()];
    }
    $_SESSION['mcp_test'] = $res;
    adm_go('mcp#test');
}

if ($rest) {
    adm_go('mcp');
}

/* =========================================================================
   Ekran
   ========================================================================= */

$new  = is_array($_SESSION['mcp_new'] ?? null) ? $_SESSION['mcp_new'] : null;
unset($_SESSION['mcp_new']);                      // anahtar yalnızca bir kez gösterilir
$test = is_array($_SESSION['mcp_test'] ?? null) ? $_SESSION['mcp_test'] : null;
unset($_SESSION['mcp_test']);

$keyShown = $new['key'] ?? '<ERİŞİM_ANAHTARI>';
$scopeDefs = mcp_scopes();
$scopeIcons = ['okuma' => 'eye', 'duyurular' => 'megaphone', 'icerik' => 'text-aa', 'ayarlar' => 'gear', 'gelen_kutusu' => 'tray'];
$scopeTitles = ['okuma' => 'Okuma', 'duyurular' => 'Duyurular', 'icerik' => 'Sayfa içerikleri', 'ayarlar' => 'Ayarlar', 'gelen_kutusu' => 'Gelen kutusu'];

/** Kopyala düğmesi */
$copyBtn = fn(string $label = 'Kopyala') => '<button class="btn btn--ghost btn--sm mx-copy" type="button" data-mcx-copy>' . ui_icon('copy') . '<span>' . e($label) . '</span></button>';

/** Kod bloğu (kopyalanabilir) */
$code = fn(string $text, string $lang = '') => '<div class="mx-code"' . ($lang !== '' ? ' data-lang="' . e($lang) . '"' : '') . '><pre><code>' . e($text) . '</code></pre>' . $copyBtn() . '</div>';

$json = fn(array $a) => (string) json_encode($a, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
$auth = 'Bearer ' . $keyShown;

$notHttps = str_starts_with($mcpUrl, 'http://') && !preg_match('#^http://(localhost|127\.0\.0\.1|\[::1\])#', $mcpUrl);

/* ---------- Üst: ne olduğu, sunucu adresi, test ---------- */
$testHtml = '';
if ($test) {
    $testHtml = $test['ok']
        ? ui_alert('<strong>Bağlantı çalışıyor.</strong> Sunucu yanıt verdi: ' . (int) $test['tools'] . ' araç, ' . (int) $test['resources'] . ' kaynak ve ' . (int) $test['prompts'] . ' hazır komut kullanıma hazır (' . (int) $test['ms'] . ' ms). Bu deneme sitenin kendi içinden yapıldı; uygulamaların bağlanabilmesi için sitenin internetten https:// ile açılıyor olması gerekir.', 'ok')
        : ui_alert('<strong>Bağlantı testi başarısız.</strong> ' . e((string) $test['msg']) . ' Sorun sürerse teknik destekle paylaşın.');
}
$hero = '<section class="mx-hero" id="test">'
    . '<div class="mx-hero__txt"><h2>Siteyi yapay zekâ asistanlarına bağlayın</h2>'
    . '<p>Yönetime katacağınız her kişi, kendi yapay zekâ asistanını (Claude, ChatGPT ve benzerleri) siteye bağlayabilir. Asistan, verdiğiniz izinler ölçüsünde panelde yapılan işlerin neredeyse tamamını yapabilir: duyuru, hizmet, yazı ve referans ekler, günceller, siler; sayfa metinlerini, iletişim, e-posta ve arama motoru ayarlarını değiştirir; ziyaretçi sayılarını okur. Her değişiklik, kimin yaptığıyla birlikte <a href="' . adm_url('gecmis') . '">Değişiklik geçmişi</a>\'ne yazılır ve oradan geri alınabilir. Panel şifresi, erişim anahtarları, yedekler ve özgeçmiş dosyaları yalnızca bu panelden yönetilir.</p></div>'
    . '<div class="mx-addr"><span class="mx-addr__l">Sunucu adresi</span>'
    . '<div class="mx-addr__row"><code id="mx-url">' . e($mcpUrl) . '</code><button class="btn btn--sm mx-addr__copy" type="button" data-mcx-copy data-mcx-from="#mx-url">' . ui_icon('copy') . '<span>Kopyala</span></button></div>'
    . '<form method="post" action="' . adm_url('mcp/test') . '" class="mx-addr__test">' . adm_csrf_field()
    . '<button class="btn btn--sm btn--ghost" type="submit">' . ui_icon('arrows-clockwise') . 'Bağlantıyı test et</button>'
    . '<span class="mx-addr__hint">Sunucunun çalıştığını ve kaç aracın hazır olduğunu denetler.</span></form></div>'
    . '</section>';

/* ---------- Yeni anahtar (bir kez) ---------- */
$newHtml = '';
if ($new) {
    $newHtml = '<section class="card mx-new" id="yeni-anahtar" aria-live="polite"><div class="card__body">'
        . '<div class="mx-new__head"><span class="mx-new__ic">' . ui_icon('key') . '</span><div><h2 class="card__title">' . e($new['name']) . ' için erişim anahtarı hazır</h2>'
        . '<p class="card__desc">Aşağıdaki anahtarı kişiye güvenli bir yolla iletin. Anahtar şifre gibidir: başkasıyla paylaşılmaz, sitede ya da herkese açık bir yerde yayınlanmaz.</p></div></div>'
        . '<div class="mx-key"><code id="mx-key">' . e($new['key']) . '</code><button class="btn btn--sm" type="button" data-mcx-copy data-mcx-from="#mx-key">' . ui_icon('copy') . '<span>Kopyala</span></button></div>'
        . '<p class="mx-once">' . ui_icon('warning-circle') . '<span><strong>Bu anahtar bir daha gösterilmez.</strong> Şimdi kopyalayın. Kaybolursa erişimi kaldırıp kişiye yeni anahtar oluşturursunuz.</span></p>'
        . '<p class="mx-new__go">Aşağıdaki "Bağlantı rehberi" bölümünde adresi ve anahtarı hazır yazılmış adımlar var.</p>'
        . '</div></section>';
}

/* ---------- Erişim ver ---------- */
$permHtml = '';
foreach ($scopeDefs as $sid => [$stitle, $sdesc, $personal]) {
    $always = $sid === 'okuma';
    $extra  = '';
    if ($sid === 'icerik') $sdesc = 'Sayfa metinlerini ve kurumsal listeleri değiştirir; hizmet, yazı, referans ve iş ilanı ekler, günceller ve siler (iş ilanını yayınlar ve kapatır); görsel yükler. İçerik değişikliklerini geri alabilir. Banka hesaplarını değiştirmek için Ayarlar izni de gerekir.';
    if ($sid === 'ayarlar') $sdesc = 'Bölümleri sitede açıp kapatır; iletişim ve şirket bilgilerini, e-posta gönderim ayarlarını ve arama motoru ayarlarını değiştirir. Form bildirimlerinin gittiği e-posta adresini ya da kayıtların panelde saklanmasını değiştirmek için Gelen kutusu izni de gerekir.';
    if ($sid === 'okuma') $sdesc = 'Siteyi, duyuruları, iş ilanlarını, sayfa metinlerini, ziyaretçi sayılarını ve değişiklik geçmişini okur; arama motoru kontrolü yapar. Hiçbir şeyi değiştiremez.';
    if ($sid === 'gelen_kutusu') {
        $extra = '<span class="mx-perm__kvkk">' . ui_icon('warning-circle') . '<span><strong>Kişisel veri.</strong> Bu izin verilirse ziyaretçilerin ve iş adaylarının adı, e-postası, telefonu ve mesajları kişinin kullandığı yapay zekâ hizmetine, yurt dışındaki sunuculara da, aktarılabilir. KVKK gereği yalnızca gerçekten gerekiyorsa verin. Asistan ziyaretçilerin yazdığı metinleri okuyacağı için, gerekmedikçe bu izni aynı anahtarda değişiklik yapan izinlerle (Duyurular, Sayfa içerikleri, Ayarlar) birlikte vermeyin.</span></span>';
    }
    $permHtml .= '<label class="mx-perm' . ($personal ? ' mx-perm--warn' : '') . '">'
        . '<input type="checkbox" name="' . ($always ? 'scope_fixed' : 'scope[]') . '" value="' . e($sid) . '"' . ($always ? ' checked disabled' : '') . '>'
        . '<span class="mx-perm__box"><span class="mx-perm__top"><span class="mx-perm__ic">' . ui_icon($scopeIcons[$sid]) . '</span>'
        . '<span class="mx-perm__t">' . e($scopeTitles[$sid]) . '</span>'
        . ($always ? '<span class="badge badge--navy">Her zaman açık</span>' : '<span class="mx-perm__on" aria-hidden="true">' . ui_icon('check-circle') . '</span>')
        . '</span><span class="mx-perm__d">' . e($sdesc) . '</span>' . $extra . '</span></label>';
}
$durHtml = '';
foreach (['30' => '30 gün', '90' => '90 gün', '365' => '1 yıl', '0' => 'Süresiz'] as $v => $l) {
    $v = (string) $v;
    $durHtml .= '<label class="mx-seg__i"><input type="radio" name="dur" value="' . $v . '"' . ($v === '90' ? ' checked' : '') . '><span>' . e($l) . '</span></label>';
}
$form = '<form method="post" action="' . adm_url('mcp/olustur') . '" class="mx-form" autocomplete="off">' . adm_csrf_field()
    . ui_text('name', 'Kişinin adı', '', ['required' => true, 'maxlength' => 80, 'placeholder' => 'Örn: Ayşe Yılmaz (sosyal medya)', 'help' => 'Anahtarın kime verildiğini anlamanız için. Kayıt ve işlem listesinde bu ad görünür.', 'autocomplete' => 'off'])
    . '<div class="fld"><p class="fld__label">Neler yapabilsin?</p><div class="mx-perms">' . $permHtml . '</div></div>'
    . '<div class="fld"><p class="fld__label" id="dur-l">Erişim ne kadar sürsün?</p><div class="mx-seg" role="radiogroup" aria-labelledby="dur-l">' . $durHtml . '</div>'
    . '<p class="fld__help">Süre dolunca anahtar kendiliğinden çalışmaz. İstediğiniz zaman aşağıdaki listeden hemen kaldırabilirsiniz.</p></div>'
    . '<div><button class="btn" type="submit">' . ui_icon('key') . 'Erişim anahtarı oluştur</button></div></form>';
$giveCard = ui_card('Bir kişiye erişim ver', $form, ['desc' => 'Her kişi kendi anahtarını kullanır; böylece kimin ne yaptığı bellidir ve birinin erişimi diğerlerini etkilemeden kaldırılabilir.', 'id' => 'ver']);

/* ---------- Bağlantı rehberi ---------- */
$tabs = [
    'claude'  => 'Claude',
    'chatgpt' => 'ChatGPT',
    'code'    => 'Claude Code',
    'cursor'  => 'Cursor',
    'vscode'  => 'VS Code',
    'gemini'  => 'Gemini CLI',
    'diger'   => 'Diğer',
];
$steps = fn(array $items) => '<ol class="mx-steps">' . implode('', array_map(fn($s) => '<li>' . $s . '</li>', $items)) . '</ol>';
$urlLine = '<div class="mx-inline"><code>' . e($mcpUrl) . '</code><button class="btn btn--ghost btn--sm mx-copy" type="button" data-mcx-copy data-mcx-text="' . e($mcpUrl) . '">' . ui_icon('copy') . '<span>Kopyala</span></button></div>';
$keyLine = $new
    ? '<div class="mx-inline"><code>' . e($new['key']) . '</code><button class="btn btn--ghost btn--sm mx-copy" type="button" data-mcx-copy data-mcx-text="' . e($new['key']) . '">' . ui_icon('copy') . '<span>Kopyala</span></button></div>'
    : '';
$noKeyNote = $new ? '' : '<p class="mx-hint">' . ui_icon('info') . '<span>Önce yukarıdan bir erişim anahtarı oluşturun; anahtar oluşturulunca bu adımlar adres ve anahtarla hazır yazılır. Şimdilik <code>&lt;ERİŞİM_ANAHTARI&gt;</code> yazan yere kişinin anahtarı gelecek.</span></p>';

$panes = [];
$panes['claude'] = '<p class="mx-lead">Claude uygulamasında (claude.ai, masaüstü ya da telefon). Özel bağlayıcılar Pro, Max, Team ve Enterprise planlarında kullanılabilir; Team ve Enterprise\'ta kuruluş yöneticisinin önce izin vermesi gerekebilir.</p>'
    . $steps([
        'Claude\'da <strong>Ayarlar</strong> bölümünü açın ve <strong>Bağlayıcılar</strong> (Connectors) sayfasına gidin.',
        '<strong>Özel bağlayıcı ekle</strong> (Add custom connector) düğmesine basın.',
        'Ad olarak "Arslanlı site" yazın; adres olarak şunu yapıştırın:' . $urlLine,
        '<strong>Ekle</strong>\'ye, ardından <strong>Bağlan</strong> (Connect) düğmesine basın.',
        'Açılan sayfada erişim anahtarını yapıştırın ve <strong>İzin ver</strong>\'e basın.' . $keyLine,
        'Yeni bir sohbette bağlayıcının açık olduğundan emin olun ve deneyin: "Sitenin durumuna bak."',
    ]) . '<p class="mx-hint">' . ui_icon('info') . '<span>Bağlayıcıyı bir kez eklediğinizde aynı hesapla genellikle masaüstü ve telefon uygulamasında da görünür.</span></p>';
$panes['chatgpt'] = '<p class="mx-lead">ChatGPT\'de geliştirici modu ile eklenen özel bağlayıcılar (uygulamalar) ile. Bu özellik ChatGPT\'nin planına (Pro, Business, Enterprise, Edu) ve sürümüne göre sunulur; planınızda görünmüyorsa yöneticinizle konuşun.</p>'
    . $steps([
        'ChatGPT\'de <strong>Ayarlar</strong> bölümünü açın; <strong>Uygulamalar ve Bağlayıcılar</strong> (Apps &amp; Connectors) sayfasına gidin.',
        '<strong>Gelişmiş ayarlar</strong> altından <strong>Geliştirici modu</strong>\'nu (Developer mode) açın.',
        'Yeni bağlayıcı oluşturun (<strong>Oluştur</strong> / Create). Ad: "Arslanlı site". Sunucu adresi (MCP server URL):' . $urlLine,
        'Kimlik doğrulama olarak <strong>OAuth</strong>\'u seçip oluşturun; sonra <strong>Bağlan</strong>\'a basın.',
        'Açılan sayfada erişim anahtarını yapıştırın ve <strong>İzin ver</strong>\'e basın.' . $keyLine,
        'Sohbette bağlayıcıyı etkinleştirip deneyin: "Sitenin durumuna bak."',
    ]);
$panes['code'] = '<p class="mx-lead">Claude Code\'u (terminal) kullanan biri için. Komutu terminalde bir kez çalıştırması yeterlidir.</p>'
    . $steps(['Terminali açın ve şu komutu çalıştırın:' . $code('claude mcp add --transport http arslanli ' . $mcpUrl . ' --header "Authorization: ' . $auth . '"', 'bash'),
        '<code>claude</code> komutuyla Claude Code\'u açıp <code>/mcp</code> yazın; "arslanli" bağlı görünmelidir.',
        'Deneyin: "Sitenin durumuna bak."']);
$panes['cursor'] = '<p class="mx-lead">Cursor\'da. Kişi aşağıdaki ayarı kendi bilgisayarındaki dosyaya ekler.</p>'
    . $steps(['Cursor\'da <strong>Settings</strong> bölümünden <strong>MCP</strong> sayfasını açın ve yeni sunucu ekleyin (<code>~/.cursor/mcp.json</code> dosyası da aynı işi görür).',
        'Dosyaya şunu yazın (başka sunucular varsa <code>"arslanli"</code> bloğunu yalnızca <code>mcpServers</code> içine ekleyin):' . $code($json(['mcpServers' => ['arslanli' => ['url' => $mcpUrl, 'headers' => ['Authorization' => $auth]]]]), 'json'),
        'Sunucunun yanındaki anahtarın açık ve yeşil olduğunu görün; deneyin: "Sitenin durumuna bak."']);
$panes['vscode'] = '<p class="mx-lead">Visual Studio Code\'da (GitHub Copilot sohbeti).</p>'
    . $steps(['Komut paletini açın (<kbd>Ctrl/⌘ Shift P</kbd>) ve <strong>MCP: Open User Configuration</strong> komutunu seçin (proje için <code>.vscode/mcp.json</code> da olur).',
        'Dosyaya şunu yazın:' . $code($json(['servers' => ['arslanli' => ['type' => 'http', 'url' => $mcpUrl, 'headers' => ['Authorization' => $auth]]]]), 'json'),
        'Sohbeti "Agent" kipine alın, araçlar listesinden "arslanli"yi açın ve deneyin: "Sitenin durumuna bak."']);
$panes['gemini'] = '<p class="mx-lead">Gemini CLI\'da (terminal).</p>'
    . $steps(['<code>~/.gemini/settings.json</code> dosyasını açın (yoksa oluşturun).',
        'Şunu ekleyin:' . $code($json(['mcpServers' => ['arslanli' => ['httpUrl' => $mcpUrl, 'headers' => ['Authorization' => $auth]]]]), 'json'),
        'Gemini CLI\'ı yeniden başlatın, <code>/mcp</code> yazarak bağlantıyı görün ve deneyin: "Sitenin durumuna bak."']);
$panes['diger'] = '<p class="mx-lead">MCP destekleyen başka bir uygulama için bilgiler:</p>'
    . '<dl class="mx-dl"><div><dt>Sunucu adresi</dt><dd>' . $urlLine . '</dd></div>'
    . '<div><dt>Bağlantı türü</dt><dd>Uzak MCP, "Streamable HTTP" (adrese POST ile JSON gider)</dd></div>'
    . '<div><dt>Kimlik doğrulama</dt><dd>Başlık: <code>Authorization: ' . e($auth) . '</code><br>OAuth destekleyen uygulamalar yalnızca adresle bağlanır; bağlanırken anahtar sorulur.</dd></div></dl>'
    . $code('Authorization: ' . $auth, 'http');

$tabBtn = '';
$tabPane = '';
$i = 0;
foreach ($tabs as $k => $label) {
    $on = $i === 0;
    $tabBtn .= '<button class="tab mx-tab' . ($on ? ' is-on' : '') . '" type="button" role="tab" id="mxt-' . $k . '" aria-controls="mxp-' . $k . '" aria-selected="' . ($on ? 'true' : 'false') . '" tabindex="' . ($on ? '0' : '-1') . '" data-mx-tab="' . $k . '">' . e($label) . '</button>';
    $tabPane .= '<div class="mx-pane" role="tabpanel" id="mxp-' . $k . '" aria-labelledby="mxt-' . $k . '"' . ($on ? '' : ' hidden') . '>' . $panes[$k] . '</div>';
    $i++;
}
$guide = ui_card('Bağlantı rehberi', ($notHttps ? ui_alert('Site adresi <strong>https://</strong> ile başlamıyor. Claude ve ChatGPT yalnızca güvenli (https) adrese bağlanır; sitenin SSL ayarı yapılınca çalışır.', 'warn') : '')
    . $noKeyNote
    . '<div class="tabs mx-tabs" role="tablist" aria-label="Uygulama seçin">' . $tabBtn . '</div>' . $tabPane
    . '<p class="mx-menu-note">' . ui_icon('info') . '<span>Bu uygulamalar menülerini ve adlarını zaman zaman değiştirir; yukarıdaki adımlardaki adlar birebir aynı olmayabilir. Bulamazsanız uygulamanın "özel bağlayıcı" ya da "MCP sunucusu ekle" bölümüne bakın. Adres ve anahtar her yerde aynıdır.</span></p>', [
    'desc' => 'Kişinin kullandığı uygulamayı seçin. Adres ve anahtar adımların içine hazır yazılır.', 'id' => 'rehber',
]);

/* ---------- Anahtar listesi ---------- */
$keys = array_reverse(mcp_keys());
usort($keys, fn($a, $b) => (int) mcp_key_active($b) <=> (int) mcp_key_active($a));
$rows = '';
foreach ($keys as $k) {
    $act = mcp_key_active($k);
    $revoked = !empty($k['revoked']);
    $status = $revoked ? '<span class="badge">Kaldırıldı</span>' : ($act ? '<span class="badge badge--ok"><i class="dot"></i>Etkin</span>' : '<span class="badge badge--warn">Süresi doldu</span>');
    $chips = '';
    foreach (mcp_clean_scopes((array) ($k['scopes'] ?? [])) as $s) {
        $chips .= '<span class="mx-sc' . ($s === 'gelen_kutusu' ? ' mx-sc--warn' : '') . '">' . e($scopeTitles[$s] ?? $s) . '</span>';
    }
    $meta = '<span>Oluşturuldu: ' . e(mcx_when($k['created'] ?? '')) . '</span>'
        . '<span>' . (!empty($k['last_used']) ? 'Son kullanım: ' . e(mcx_when($k['last_used'])) . (!empty($k['last_client']) ? ' · ' . e((string) $k['last_client']) : '') : 'Henüz kullanılmadı') . '</span>'
        . '<span>' . (!empty($k['expires']) ? ($act || $revoked ? 'Bitiş: ' : 'Bitti: ') . e(mcx_when($k['expires'])) : 'Süresiz') . '</span>';
    $btn = $act
        ? '<form method="post" action="' . adm_url('mcp/kaldir/' . $k['id']) . '" data-confirm="' . e($k['name']) . ' kişisinin erişimi kaldırılsın mı? Bu anahtarla bağlı tüm uygulamaların bağlantısı hemen kesilir.">' . adm_csrf_field()
          . '<button class="btn btn--danger btn--sm" type="submit">' . ui_icon('trash') . 'Erişimi kaldır</button></form>'
        : '';
    $rows .= '<div class="list__row mx-krow' . ($act ? '' : ' is-off') . '"><div class="list__main">'
        . '<span class="list__title mx-ktitle">' . e($k['name']) . ' ' . $status . '<code class="mx-prefix">' . e((string) $k['prefix']) . '…</code></span>'
        . '<span class="mx-scs">' . $chips . '</span><span class="list__meta">' . $meta . '</span></div><div class="list__side">' . $btn . '</div></div>';
}
$keysBody = $rows
    ? '<div class="list">' . $rows . '</div>'
    : '<div class="empty">' . ui_icon('key') . '<strong>Henüz kimseye erişim verilmedi</strong><span>Yukarıdaki "Bir kişiye erişim ver" bölümünden ilk anahtarı oluşturun.</span></div>';
$nActive = count(array_filter($keys, 'mcp_key_active'));
$keysCard = ui_card('Erişimi olan kişiler', $keysBody, ['desc' => $nActive . ' etkin erişim. "Erişimi kaldır" anahtarı ve onunla bağlanmış tüm uygulamaları anında devre dışı bırakır.', 'id' => 'kisiler']);

/* ---------- İşlem kaydı ---------- */
$who   = preg_match('/^[a-f0-9]{12}$/', (string) ($_GET['kisi'] ?? '')) ? (string) $_GET['kisi'] : '';
$log   = mcp_log_tail(100, $who);
$names = [];
foreach (mcp_keys() as $k) $names[$k['id']] = $k['name'];
$chipsL = '<a class="chip' . ($who === '' ? ' is-on' : '') . '" href="' . adm_url('mcp') . '#kayit">Herkes</a>';
foreach ($names as $kid => $kn) {
    $chipsL .= '<a class="chip' . ($who === $kid ? ' is-on' : '') . '" href="' . adm_url('mcp') . '?kisi=' . e($kid) . '#kayit">' . e($kn) . '</a>';
}
$trs = '';
foreach ($log as $r) {
    $ok = !empty($r['ok']);
    $trs .= '<tr><td class="nowrap">' . e(mcx_when((string) ($r['time'] ?? ''))) . '</td><td>' . e((string) ($r['person'] ?? '')) . '</td><td>' . e((string) ($r['client'] ?? '')) . '</td>'
        . '<td><code class="mx-tool">' . e((string) ($r['tool'] ?? '')) . '</code>' . (!empty($r['args']) ? '<span class="mx-args">' . e((string) $r['args']) . '</span>' : '') . '</td>'
        . '<td>' . ($ok ? '<span class="badge badge--ok">Tamam</span>' : '<span class="badge badge--err">Hata</span>') . (in_array((string) ($r['msg'] ?? ''), ['', 'Tamam'], true) ? '' : '<span class="mx-msg">' . e((string) $r['msg']) . '</span>') . '</td></tr>';
}
$logBody = ($names ? '<div class="chips mx-chips">' . $chipsL . '</div>' : '')
    . ($trs
        ? '<div class="tbl-wrap mx-tbl"><table class="tbl"><thead><tr><th>Zaman</th><th>Kişi</th><th>Uygulama</th><th>İşlem</th><th>Sonuç</th></tr></thead><tbody>' . $trs . '</tbody></table></div>'
        : '<div class="empty">' . ui_icon('clock-counter-clockwise') . '<strong>Henüz işlem yok</strong><span>Birisi asistanını bağlayıp bir şey yaptığında burada görünür.</span></div>');
$logCard = ui_card('Son işlemler', $logBody, ['desc' => 'Son 100 işlem, en yenisi üstte. Gelen kutusu işlemlerinde yalnızca filtreler kaydedilir; kişisel veri kayda yazılmaz.', 'id' => 'kayit']);

adm_layout('Yapay zekâ erişimi', $testHtml . $hero . $newHtml . $giveCard . $guide . $keysCard . $logCard . '<script>
(function () {
  var d = document;
  var copyText = function (t, btn) {
    var done = function () {
      var s = btn.querySelector("span"), old = s ? s.textContent : "";
      btn.classList.add("is-done"); if (s) s.textContent = "Kopyalandı";
      setTimeout(function () { btn.classList.remove("is-done"); if (s) s.textContent = old; }, 1600);
    };
    if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(t).then(done, fallback); } else { fallback(); }
    function fallback() {
      var ta = d.createElement("textarea"); ta.value = t; ta.setAttribute("readonly", ""); ta.style.cssText = "position:fixed;opacity:0;top:0";
      d.body.appendChild(ta); ta.select();
      try { d.execCommand("copy"); done(); } catch (e) {} ta.remove();
    }
  };
  d.addEventListener("click", function (e) {
    var b = e.target.closest("[data-mcx-copy]");
    if (!b) return;
    var src = b.getAttribute("data-mcx-from"), txt = b.getAttribute("data-mcx-text");
    if (txt === null) {
      var el = src ? d.querySelector(src) : b.parentNode.querySelector("pre code");
      txt = el ? el.textContent : "";
    }
    copyText(txt, b);
  });
  var tabs = [].slice.call(d.querySelectorAll("[data-mx-tab]"));
  var show = function (t, focus) {
    tabs.forEach(function (x) {
      var on = x === t;
      x.classList.toggle("is-on", on); x.setAttribute("aria-selected", on ? "true" : "false"); x.tabIndex = on ? 0 : -1;
      d.getElementById("mxp-" + x.getAttribute("data-mx-tab")).hidden = !on;
    });
    if (focus) t.focus();
  };
  tabs.forEach(function (t, i) {
    t.addEventListener("click", function () { show(t, false); });
    t.addEventListener("keydown", function (e) {
      var n = e.key === "ArrowRight" ? i + 1 : e.key === "ArrowLeft" ? i - 1 : e.key === "Home" ? 0 : e.key === "End" ? tabs.length - 1 : null;
      if (n === null) return;
      e.preventDefault(); show(tabs[(n + tabs.length) % tabs.length], true);
    });
  });
  var nk = d.getElementById("yeni-anahtar");
  if (nk && nk.scrollIntoView) nk.scrollIntoView({ block: "start" });
})();
</script>', [
    'section'  => 'mcp',
    'subtitle' => 'Her kişi kendi yapay zekâ asistanını siteye bağlar; erişim kişiye özeldir ve istediğiniz an kaldırılır.',
]);
