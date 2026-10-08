<?php
declare(strict_types=1);

/**
 * OAuth 2.1 (claude.ai, ChatGPT gibi uygulamalar yalnızca adresle bağlanabilsin diye)
 * ---------------------------------------------------------------------------
 *   /.well-known/oauth-protected-resource        RFC 9728 kaynak meta verisi
 *   /.well-known/oauth-authorization-server      RFC 8414 yetkilendirme sunucusu meta verisi (openid-configuration aynı belge)
 *   POST /oauth/register                          RFC 7591 dinamik istemci kaydı
 *   GET|POST /oauth/authorize                     onay sayfası: kişi kendi erişim anahtarını yazar; tek kullanımlık kod (10 dk), PKCE S256 zorunlu
 *   POST /oauth/token                             authorization_code ve refresh_token (her kullanımda döner); erişim 1 saat, yenileme 30 gün
 *   POST /oauth/revoke                            RFC 7009
 *
 * Belirteçlerin hepsi rastgeledir ve yalnızca sha256 özeti saklanır; her biri bir erişim anahtarına bağlıdır:
 * anahtar kaldırılır ya da süresi dolarsa o anahtardan çıkan her şey geçersiz olur.
 */

require_once __DIR__ . '/server.php';

const OAUTH_CODE_TTL    = 600;
const OAUTH_ACCESS_TTL  = 3600;
const OAUTH_REFRESH_TTL = 2592000;   // 30 gün

function oauth_scope_names(): array
{
    return array_keys(mcp_scopes());
}

/** JSON yanıt (CORS ve önbelleksiz) */
function oauth_json(int $status, $data, array $headers = []): void
{
    mcp_cors();
    header('Pragma: no-cache');
    mcp_json_out($status, $data, $headers);
}

function oauth_error(int $status, string $error, string $desc, array $headers = []): void
{
    oauth_json($status, ['error' => $error, 'error_description' => $desc], $headers);
}

/* =========================================================================
   Meta veri
   ========================================================================= */

function oauth_resource_metadata(): array
{
    return [
        'resource'                 => mcp_url('mcp'),
        'authorization_servers'    => [mcp_origin()],
        'bearer_methods_supported' => ['header'],
        'scopes_supported'         => oauth_scope_names(),
        'resource_name'            => 'Arslanlı Yatırım & Danışmanlık sitesi (yönetim erişimi)',
    ];
}

function oauth_server_metadata(): array
{
    $o = mcp_origin();
    return [
        'issuer'                                => $o,
        'authorization_endpoint'                => $o . '/oauth/authorize',
        'token_endpoint'                        => $o . '/oauth/token',
        'registration_endpoint'                 => $o . '/oauth/register',
        'revocation_endpoint'                   => $o . '/oauth/revoke',
        'response_types_supported'              => ['code'],
        'response_modes_supported'              => ['query'],
        'grant_types_supported'                 => ['authorization_code', 'refresh_token'],
        'code_challenge_methods_supported'      => ['S256'],
        'token_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
        'revocation_endpoint_auth_methods_supported' => ['none', 'client_secret_post', 'client_secret_basic'],
        'scopes_supported'                      => oauth_scope_names(),
    ];
}

function oauth_serve_metadata(array $doc): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        http_response_code(405);
        header('Allow: GET, HEAD, OPTIONS');
        exit;
    }
    oauth_json(200, $doc, ['Cache-Control: public, max-age=300']);
}

/* =========================================================================
   Yardımcılar
   ========================================================================= */

function oauth_state(): array
{
    $d = mcp_json_read('oauth');
    return $d + ['clients' => [], 'codes' => [], 'access' => [], 'refresh' => []];
}

function oauth_client_get(string $id): ?array
{
    $c = oauth_state()['clients'][$id] ?? null;
    return is_array($c) ? $c + ['id' => $id] : null;
}

/** Süresi dolmuş kodları ve belirteçleri (ve 30 gündür hiç kullanılmamış istemcileri) temizler. */
function oauth_prune(array &$d): void
{
    $now = time();
    foreach (['codes', 'access', 'refresh'] as $b) {
        foreach ((array) ($d[$b] ?? []) as $h => $row) {
            if ((int) ($row['expires'] ?? 0) <= $now) {
                unset($d[$b][$h]);
            }
        }
    }
    foreach ((array) ($d['clients'] ?? []) as $id => $c) {
        if (empty($c['last_used']) && (int) ($c['created'] ?? 0) < $now - 30 * 86400) {
            unset($d['clients'][$id]);
        }
    }
}

function oauth_redirect_ok(string $u): bool
{
    if ($u === '' || strlen($u) > 300 || preg_match('/[\s\x00-\x1F\x7F]/', $u) || str_contains($u, '#')) {
        return false;
    }
    $p = parse_url($u);
    if (!is_array($p) || empty($p['scheme']) || empty($p['host']) || isset($p['user']) || isset($p['pass'])) {
        return false;
    }
    $scheme = strtolower((string) $p['scheme']);
    $host = strtolower((string) $p['host']);
    if ($scheme === 'https') {
        return true;
    }
    return $scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
}

function oauth_b64url(string $bin): string
{
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

/** İzin listesi: arayüz için [{id, baslik, aciklama, kisisel_veri}] */
function oauth_scope_cards(array $scopes): array
{
    $all = mcp_scopes();
    $out = [];
    foreach ($scopes as $s) {
        if (isset($all[$s])) {
            $out[] = ['id' => $s, 'baslik' => $all[$s][0], 'aciklama' => $all[$s][1], 'kisisel_veri' => $all[$s][2]];
        }
    }
    return $out;
}

/** İstenen kaynak (RFC 8707) bizim /mcp adresimiz mi? */
function oauth_resource_ok(string $r): bool
{
    $r = rtrim($r, '/');
    return $r === rtrim(mcp_url('mcp'), '/') || $r === rtrim(mcp_origin(), '/');
}

function oauth_issue_tokens(array &$d, string $clientId, string $keyId, string $family, array $scopes): array
{
    $access = 'arat_' . mcp_random(48);
    $refresh = 'arrt_' . mcp_random(48);
    $now = time();
    $d['access'][mcp_hash($access)] = ['client_id' => $clientId, 'key_id' => $keyId, 'family' => $family, 'expires' => $now + OAUTH_ACCESS_TTL];
    $d['refresh'][mcp_hash($refresh)] = ['client_id' => $clientId, 'key_id' => $keyId, 'family' => $family, 'scopes' => $scopes, 'expires' => $now + OAUTH_REFRESH_TTL, 'used' => false];
    if (isset($d['clients'][$clientId])) {
        $d['clients'][$clientId]['last_used'] = $now;
    }
    return [
        'access_token'  => $access,
        'token_type'    => 'Bearer',
        'expires_in'    => OAUTH_ACCESS_TTL,
        'refresh_token' => $refresh,
        'scope'         => implode(' ', $scopes),
    ];
}

/** Bir aileye (aynı yetkilendirmeden çıkan tüm belirteçler) bağlı her şeyi siler. */
function oauth_revoke_family(array &$d, string $family): void
{
    foreach (['access', 'refresh'] as $b) {
        foreach ((array) ($d[$b] ?? []) as $h => $row) {
            if (($row['family'] ?? '') === $family) {
                unset($d[$b][$h]);
            }
        }
    }
}

/* =========================================================================
   POST /oauth/register
   ========================================================================= */

function oauth_register(): void
{
    mcp_cors();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST, OPTIONS');
        exit;
    }
    if (!mcp_ip_limit('reg', 20, 3600, true)) {
        oauth_error(429, 'invalid_client_metadata', 'Çok fazla kayıt isteği. Bir saat sonra tekrar deneyin.', ['Retry-After: 3600']);
    }
    $raw = (string) file_get_contents('php://input', false, null, 0, 16385);
    $in = strlen($raw) <= 16384 ? json_decode($raw, true, 16) : null;
    if (!is_array($in) || mcp_is_list($in)) {
        oauth_error(400, 'invalid_client_metadata', 'İstek gövdesi bir JSON nesnesi olmalı (en fazla 16 KB).');
    }

    $uris = $in['redirect_uris'] ?? null;
    if (!is_array($uris) || !$uris || !mcp_is_list($uris) || count($uris) > 5) {
        oauth_error(400, 'invalid_redirect_uri', 'redirect_uris 1 ile 5 adreslik bir liste olmalı.');
    }
    foreach ($uris as $u) {
        if (!is_string($u) || !oauth_redirect_ok($u)) {
            oauth_error(400, 'invalid_redirect_uri', 'Geçersiz yönlendirme adresi: yalnızca https:// (ya da localhost için http://) kabul edilir, adres en fazla 300 karakter olabilir ve adreste # parçası olamaz.');
        }
    }
    $uris = array_values(array_unique($uris));

    $grants = $in['grant_types'] ?? ['authorization_code'];
    if (!is_array($grants) || !$grants) {
        oauth_error(400, 'invalid_client_metadata', 'grant_types bir liste olmalı.');
    }
    foreach ($grants as $g) {
        if (!in_array($g, ['authorization_code', 'refresh_token'], true)) {
            oauth_error(400, 'invalid_client_metadata', 'Desteklenmeyen grant_type: ' . (is_string($g) ? mb_substr($g, 0, 40) : '?') . '. Desteklenenler: authorization_code, refresh_token.');
        }
    }
    $grants = array_values(array_unique($grants));
    if (!in_array('authorization_code', $grants, true)) {
        oauth_error(400, 'invalid_client_metadata', 'grant_types içinde authorization_code bulunmalı.');
    }
    $rtypes = $in['response_types'] ?? ['code'];
    if ($rtypes !== ['code']) {
        oauth_error(400, 'invalid_client_metadata', 'response_types yalnızca ["code"] olabilir.');
    }
    $method = $in['token_endpoint_auth_method'] ?? 'client_secret_basic';
    if (!in_array($method, ['none', 'client_secret_post', 'client_secret_basic'], true)) {
        oauth_error(400, 'invalid_client_metadata', 'Desteklenmeyen token_endpoint_auth_method. Desteklenenler: none, client_secret_post, client_secret_basic.');
    }
    $name = $in['client_name'] ?? '';
    $name = is_string($name) ? trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $name)) : '';
    $name = mb_substr($name, 0, 100);
    if ($name === '') {
        $name = 'Adsız uygulama';
    }

    $id = 'arc_' . mcp_random(28);
    $secret = $method === 'none' ? null : 'arcs_' . mcp_random(40);
    $now = time();
    $rec = ['name' => $name, 'redirect_uris' => $uris, 'grant_types' => $grants, 'auth_method' => $method,
        'secret_hash' => $secret !== null ? mcp_hash($secret) : null, 'created' => $now, 'last_used' => null];
    mcp_json_update('oauth', function (array &$d) use ($id, $rec): void {
        $d += ['clients' => [], 'codes' => [], 'access' => [], 'refresh' => []];
        oauth_prune($d);
        if (count($d['clients']) >= 300) {           // kayıt şişirme saldırısına karşı üst sınır: en eski kullanılmamışlar gider
            uasort($d['clients'], fn($a, $b) => (int) ($a['created'] ?? 0) <=> (int) ($b['created'] ?? 0));
            foreach (array_keys($d['clients']) as $cid) {
                if (count($d['clients']) < 300) break;
                if (empty($d['clients'][$cid]['last_used'])) unset($d['clients'][$cid]);
            }
        }
        $d['clients'][$id] = $rec;
    });

    $out = ['client_id' => $id];
    if ($secret !== null) {
        $out['client_secret'] = $secret;
    }
    $out += [
        'client_id_issued_at'        => $now,
        'client_secret_expires_at'   => 0,
        'redirect_uris'              => $uris,
        'client_name'                => $name,
        'grant_types'                => $grants,
        'response_types'             => ['code'],
        'token_endpoint_auth_method' => $method,
        'scope'                      => implode(' ', oauth_scope_names()),
    ];
    oauth_json(201, $out);
}

/* =========================================================================
   POST /oauth/token
   ========================================================================= */

/** Gövde: form kodlu ya da JSON */
function oauth_body(): array
{
    if (!empty($_POST)) {
        return $_POST;
    }
    $ct = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($ct, 'json')) {
        $j = json_decode((string) file_get_contents('php://input', false, null, 0, 65537), true, 8);
        return is_array($j) ? $j : [];
    }
    return [];
}

/**
 * İstemci kimliği: client_secret_basic, client_secret_post ya da none.
 * @return array istemci kaydı (id dahil); doğrulanamazsa 401 ile çıkar
 */
function oauth_client_auth(array $in): array
{
    $id = '';
    $secret = '';
    $viaHeader = false;
    $h = mcp_auth_header();
    if (stripos($h, 'Basic ') === 0) {
        $dec = base64_decode(trim(substr($h, 6)), true);
        if ($dec !== false && str_contains($dec, ':')) {
            [$id, $secret] = explode(':', $dec, 2);
            $id = urldecode($id);
            $secret = urldecode($secret);
            $viaHeader = true;
        }
    }
    if ($id === '') {
        $id = is_string($in['client_id'] ?? null) ? $in['client_id'] : '';
        $secret = is_string($in['client_secret'] ?? null) ? $in['client_secret'] : '';
    }
    $c = $id !== '' ? oauth_client_get($id) : null;
    $fail = fn() => oauth_error(401, 'invalid_client', 'İstemci doğrulanamadı.', $viaHeader ? ['WWW-Authenticate: Basic realm="oauth"'] : []);
    if (!$c) {
        $fail();
    }
    if (($c['auth_method'] ?? 'none') !== 'none') {
        if ($secret === '' || empty($c['secret_hash']) || !hash_equals((string) $c['secret_hash'], mcp_hash($secret))) {
            $fail();
        }
    }
    return $c;
}

function oauth_token(): void
{
    mcp_cors();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST, OPTIONS');
        exit;
    }
    $in = oauth_body();
    $client = oauth_client_auth($in);
    $grant = is_string($in['grant_type'] ?? null) ? $in['grant_type'] : '';
    if (!in_array($grant, (array) $client['grant_types'], true) && in_array($grant, ['authorization_code', 'refresh_token'], true)) {
        oauth_error(400, 'unauthorized_client', 'Bu istemci bu grant_type için kayıtlı değil.');
    }
    if (isset($in['resource']) && (!is_string($in['resource']) || !oauth_resource_ok($in['resource']))) {
        oauth_error(400, 'invalid_target', 'resource bu sunucunun /mcp adresi olmalı.');
    }

    if ($grant === 'authorization_code') {
        oauth_grant_code($client, $in);
    }
    if ($grant === 'refresh_token') {
        oauth_grant_refresh($client, $in);
    }
    oauth_error(400, 'unsupported_grant_type', 'Desteklenen grant_type değerleri: authorization_code, refresh_token.');
}

function oauth_grant_code(array $client, array $in): void
{
    $code = is_string($in['code'] ?? null) ? $in['code'] : '';
    $ver = is_string($in['code_verifier'] ?? null) ? $in['code_verifier'] : '';
    $redir = is_string($in['redirect_uri'] ?? null) ? $in['redirect_uri'] : '';
    if ($code === '' || !preg_match('/^arac_[0-9A-Za-z]{40}$/', $code)) {
        oauth_error(400, 'invalid_grant', 'Yetkilendirme kodu geçersiz ya da süresi dolmuş.');
    }
    if (!preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $ver)) {
        oauth_error(400, 'invalid_request', 'code_verifier 43-128 karakterlik, yalnızca A-Z a-z 0-9 - . _ ~ içeren bir değer olmalı.');
    }
    $h = mcp_hash($code);
    $result = mcp_json_update('oauth', function (array &$d) use ($h, $client, $redir, $ver): array {
        $d += ['clients' => [], 'codes' => [], 'access' => [], 'refresh' => []];
        oauth_prune($d);
        $row = $d['codes'][$h] ?? null;
        if (!is_array($row)) {
            return ['err' => 'Yetkilendirme kodu geçersiz ya da süresi dolmuş.'];
        }
        if (!empty($row['used'])) {                 // tekrar kullanım: bu koddan çıkan her şey iptal edilir
            oauth_revoke_family($d, (string) $row['family']);
            return ['err' => 'Yetkilendirme kodu daha önce kullanılmış; ondan çıkan belirteçler iptal edildi.'];
        }
        $d['codes'][$h]['used'] = true;             // tek kullanım: doğrulama başarısız olsa bile yanar
        if (($row['client_id'] ?? '') !== $client['id']) {
            return ['err' => 'Kod bu istemciye verilmemiş.'];
        }
        if ($redir === '' || !hash_equals((string) $row['redirect_uri'], $redir)) {
            return ['err' => 'redirect_uri yetkilendirme isteğindekiyle aynı olmalı.'];
        }
        if (!hash_equals((string) $row['challenge'], oauth_b64url(hash('sha256', $ver, true)))) {
            return ['err' => 'code_verifier doğrulanamadı.'];
        }
        $key = mcp_key_get((string) $row['key_id']);
        if (!$key || !mcp_key_active($key)) {
            return ['err' => 'Bu kodu veren erişim anahtarı artık geçerli değil.'];
        }
        return ['ok' => oauth_issue_tokens($d, $client['id'], (string) $row['key_id'], (string) $row['family'], mcp_clean_scopes((array) ($key['scopes'] ?? [])))];
    });
    if (isset($result['err'])) {
        oauth_error(400, 'invalid_grant', $result['err']);
    }
    oauth_json(200, $result['ok']);
}

function oauth_grant_refresh(array $client, array $in): void
{
    $tok = is_string($in['refresh_token'] ?? null) ? $in['refresh_token'] : '';
    if (!preg_match('/^arrt_[0-9A-Za-z]{48}$/', $tok)) {
        oauth_error(400, 'invalid_grant', 'Yenileme belirteci geçersiz ya da süresi dolmuş.');
    }
    $scopeReq = is_string($in['scope'] ?? null) && trim($in['scope']) !== '' ? preg_split('/\s+/', trim($in['scope'])) : null;
    $h = mcp_hash($tok);
    $result = mcp_json_update('oauth', function (array &$d) use ($h, $client, $scopeReq): array {
        $d += ['clients' => [], 'codes' => [], 'access' => [], 'refresh' => []];
        oauth_prune($d);
        $row = $d['refresh'][$h] ?? null;
        if (!is_array($row)) {
            return ['err' => 'Yenileme belirteci geçersiz ya da süresi dolmuş.'];
        }
        if (($row['client_id'] ?? '') !== $client['id']) {
            return ['err' => 'Belirteç bu istemciye verilmemiş.'];
        }
        if (!empty($row['used'])) {                 // eski belirteç yeniden gösterildi: ailenin tamamı iptal
            oauth_revoke_family($d, (string) $row['family']);
            return ['err' => 'Yenileme belirteci daha önce kullanılmış; güvenlik için bu bağlantının tüm belirteçleri iptal edildi. Yeniden bağlanın.'];
        }
        $key = mcp_key_get((string) $row['key_id']);
        if (!$key || !mcp_key_active($key)) {
            return ['err' => 'Bu belirteci veren erişim anahtarı artık geçerli değil.'];
        }
        $scopes = mcp_clean_scopes((array) ($key['scopes'] ?? []));
        if ($scopeReq !== null) {
            $bad = array_diff($scopeReq, $scopes);
            if ($bad) {
                return ['scope' => 'İstenen izin bu anahtarda yok: ' . implode(', ', array_map(fn($s) => mb_substr((string) $s, 0, 30), $bad))];
            }
            $scopes = mcp_clean_scopes($scopeReq);
        }
        $d['refresh'][$h]['used'] = true;           // döner: eski belirteç ölür (kayıt, yeniden kullanımı yakalamak için süresi dolana dek kalır)
        return ['ok' => oauth_issue_tokens($d, $client['id'], (string) $row['key_id'], (string) $row['family'], $scopes)];
    });
    if (isset($result['scope'])) {
        oauth_error(400, 'invalid_scope', $result['scope']);
    }
    if (isset($result['err'])) {
        oauth_error(400, 'invalid_grant', $result['err']);
    }
    oauth_json(200, $result['ok']);
}

/* =========================================================================
   POST /oauth/revoke (RFC 7009)
   ========================================================================= */

function oauth_revoke(): void
{
    mcp_cors();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST, OPTIONS');
        exit;
    }
    $in = oauth_body();
    $client = oauth_client_auth($in);
    $tok = is_string($in['token'] ?? null) ? $in['token'] : '';
    if ($tok === '') {
        oauth_error(400, 'invalid_request', 'token gerekir.');
    }
    $h = mcp_hash($tok);
    mcp_json_update('oauth', function (array &$d) use ($h, $client): void {
        $d += ['clients' => [], 'codes' => [], 'access' => [], 'refresh' => []];
        if (isset($d['access'][$h]) && ($d['access'][$h]['client_id'] ?? '') === $client['id']) {
            unset($d['access'][$h]);
        } elseif (isset($d['refresh'][$h]) && ($d['refresh'][$h]['client_id'] ?? '') === $client['id']) {
            oauth_revoke_family($d, (string) $d['refresh'][$h]['family']);
            unset($d['refresh'][$h]);
        }
    });
    oauth_json(200, new stdClass());      // belirteç bilinmese de 200
}

/* =========================================================================
   GET|POST /oauth/authorize: onay sayfası
   ========================================================================= */

function oauth_csrf_secret(): string
{
    $f = mcp_dir() . '/secret.key';
    $s = is_file($f) ? trim((string) @file_get_contents($f)) : '';
    if (strlen($s) < 32) {
        $s = bin2hex(random_bytes(32));
        @file_put_contents($f, $s, LOCK_EX);
        @chmod($f, 0600);
    }
    return $s;
}

function oauth_csrf_cookie_name(): string
{
    return 'arsl_oauth';
}

/** Çerezdeki rastgele değeri hazırlar (yoksa üretir) ve forma konacak imzalı belirteci döndürür. */
function oauth_csrf_issue(): string
{
    $c = $_COOKIE[oauth_csrf_cookie_name()] ?? '';
    if (!is_string($c) || !preg_match('/^[0-9a-f]{32}$/', $c)) {
        $c = bin2hex(random_bytes(16));
        // Vekil sunucu arkasında HTTPS algılanmasa da site adresi https ise çerez yalnızca güvenli bağlantıda gönderilir
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || str_starts_with(mcp_origin(), 'https://');
        setcookie(oauth_csrf_cookie_name(), $c, ['expires' => 0, 'path' => (base_path() ?: '') . '/oauth/authorize', 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
    }
    return hash_hmac('sha256', $c, oauth_csrf_secret());
}

function oauth_csrf_ok(string $token): bool
{
    $c = $_COOKIE[oauth_csrf_cookie_name()] ?? '';
    return is_string($c) && preg_match('/^[0-9a-f]{32}$/', $c) && $token !== '' && hash_equals(hash_hmac('sha256', $c, oauth_csrf_secret()), $token);
}

function oauth_authorize_redirect(string $uri, array $params): void
{
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    $sep = str_contains($uri, '?') ? '&' : '?';
    header('Cache-Control: no-store');
    header('Referrer-Policy: no-referrer');
    header('Location: ' . $uri . $sep . http_build_query($params, '', '&', PHP_QUERY_RFC3986), true, 302);
    exit;
}

/**
 * Yetkilendirme isteği parametrelerini doğrular.
 * @return array{client:array, redirect_uri:string, state:string, challenge:string, resource:string}|array{error:string}
 */
function oauth_authorize_params(array $q): array
{
    $get = fn(string $k): string => is_string($q[$k] ?? null) ? (string) $q[$k] : '';
    $client = $get('client_id') !== '' ? oauth_client_get($get('client_id')) : null;
    if (!$client) {
        return ['fatal' => 'Bu uygulama tanınmıyor (client_id geçersiz ya da kaydı silinmiş). Uygulamadan bağlantıyı yeniden başlatın.'];
    }
    $redir = $get('redirect_uri');
    $uris = (array) $client['redirect_uris'];
    if ($redir === '' && count($uris) === 1) {
        $redir = (string) $uris[0];
    }
    if (!in_array($redir, $uris, true)) {
        return ['fatal' => 'Geri dönüş adresi (redirect_uri) uygulamanın kayıtlı adresleriyle eşleşmiyor. Güvenliğiniz için devam edilemez.'];
    }
    $base = ['client' => $client, 'redirect_uri' => $redir, 'state' => mb_substr($get('state'), 0, 500), 'challenge' => '', 'resource' => ''];
    $err = null;
    if (!in_array('authorization_code', (array) $client['grant_types'], true)) {
        $err = ['unauthorized_client', 'Uygulama yetkilendirme kodu akışı için kayıtlı değil.'];
    } elseif ($get('response_type') !== 'code') {
        $err = ['unsupported_response_type', 'response_type "code" olmalı.'];
    } elseif ($get('code_challenge') === '' || !preg_match('/^[A-Za-z0-9\-._~]{43,128}$/', $get('code_challenge'))) {
        $err = ['invalid_request', 'PKCE zorunlu: geçerli bir code_challenge gerekir.'];
    } elseif ($get('code_challenge_method') !== 'S256') {
        $err = ['invalid_request', 'PKCE yöntemi yalnızca S256 olabilir (code_challenge_method=S256).'];
    } elseif ($get('resource') !== '' && !oauth_resource_ok($get('resource'))) {
        $err = ['invalid_target', 'resource bu sunucunun /mcp adresi olmalı.'];
    }
    if ($err) {
        return $base + ['error' => $err[0], 'error_description' => $err[1]];
    }
    $base['challenge'] = $get('code_challenge');
    $base['resource'] = $get('resource');
    return $base;
}

/** Kısa, bağımsız HTML sayfası (panelin giriş sayfasının görsel dili) */
function oauth_html(string $title, string $body, int $status = 200, string $nonce = ''): void
{
    http_response_code($status);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('Pragma: no-cache');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'none'; style-src 'self' 'unsafe-inline'; font-src 'self'; img-src 'self' data:; script-src 'nonce-" . $nonce . "'; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'");
    header('X-Robots-Tag: noindex, nofollow');
    $css = str_replace('__FONTS__', e(url('assets/fonts/')), oauth_page_css());
    echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<meta name="robots" content="noindex, nofollow"><title>' . e($title) . ' · ' . e((string) (cfg('short_name') ?: cfg('name'))) . '</title>'
        . '<link rel="icon" href="' . e(url('assets/img/favicon.png')) . '">'
        . '<style>' . $css . '</style></head><body>' . $body . '</body></html>';
    exit;
}

function oauth_page_css(): string
{
    return <<<'CSS'
@font-face{font-family:"Archivo";src:url("__FONTS__archivo.woff2") format("woff2");font-weight:100 900;font-stretch:62% 125%;font-display:swap}
@font-face{font-family:"Newsreader";src:url("__FONTS__newsreader.woff2") format("woff2");font-weight:300 700;font-style:normal;font-display:swap}
@font-face{font-family:"Newsreader";src:url("__FONTS__newsreader-italic.woff2") format("woff2");font-weight:300 600;font-style:italic;font-display:swap}
@font-face{font-family:"Courier Prime";src:url("__FONTS__courier-prime.woff2") format("woff2");font-weight:400;font-display:swap}
@font-face{font-family:"Courier Prime";src:url("__FONTS__courier-prime-bold.woff2") format("woff2");font-weight:700;font-display:swap}
:root{--paper:#eeeae1;--paper-2:#e4dfd2;--sheet:#f9f7f2;--field:#fffefb;--ink:#1a1a1d;--ink-2:#47464a;--ink-3:#66625a;--edge:#7d786d;--rule-2:rgba(26,26,29,.42);--pen:#23409c;--pen-d:#1a3870;--pen-soft:#dfe2ee;--red:#b02f1e;--red-soft:#f4ddd6;--hl:#f2df4f;--hl-soft:#f7efae;--ok:#2a5e3a;--ok-soft:#dfe9d9;--f:"Archivo","Helvetica Neue",Arial,sans-serif;--serif:"Newsreader","Iowan Old Style",Georgia,serif;--type:"Courier Prime","Courier New",monospace;--r:4px}
*,*::before,*::after{box-sizing:border-box}
[hidden]{display:none!important}
html{-webkit-text-size-adjust:100%}
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:clamp(16px,4vw,40px) 16px;font:400 15.5px/1.5 var(--f);color:var(--ink);-webkit-font-smoothing:antialiased;background:var(--paper)}
::selection{background:var(--hl);color:var(--ink)}
h1,h2,h3,p,ul{margin:0}ul{padding:0;list-style:none}
button,input{font:inherit;color:inherit}
.wrap{width:min(460px,100%);display:grid;gap:22px}
.brand{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;background:var(--ink);border-radius:3px;color:var(--paper)}
.brand img{width:124px;height:auto;display:block}
.brand span{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border:1.5px solid var(--paper);border-radius:3px;font:700 11px/1 var(--type);letter-spacing:.08em;text-transform:uppercase;transform:rotate(-2deg)}
.brand svg{width:14px;height:14px}
.card{--pad:clamp(20px,5vw,28px);background:var(--sheet);border:1.5px solid var(--ink);border-radius:3px;padding:0 var(--pad) var(--pad);display:grid;gap:18px;box-shadow:6px 6px 0 rgba(26,26,29,.85)}
.card::before{content:"Yetkili bağlantı";margin:0 calc(var(--pad)*-1);padding:10px var(--pad);background:var(--ink);color:var(--paper);font:700 14px/1 var(--f);font-stretch:82%;letter-spacing:.08em;text-transform:uppercase}
.pair{display:flex;align-items:center;justify-content:center;gap:0;margin:2px 0 4px}
.pair__a,.pair__b{width:54px;height:54px;border-radius:4px;display:grid;place-items:center;flex:none}
.pair__a{background:var(--paper-2);border:1.5px solid var(--ink);color:var(--ink);font:800 26px/1 var(--f);font-stretch:70%}
.pair__b{background:var(--ink)}
.pair__b img{width:32px;height:auto}
.pair__w{display:flex;align-items:center;gap:3px;height:22px;padding:0 10px}
.pair__w i{width:3px;border-radius:2px;background:var(--red);height:calc(var(--h)*100%)}
h1{font:800 28px/1.08 var(--f);font-stretch:70%;text-transform:uppercase;text-align:center;text-wrap:balance}
h1 b{font-weight:800;color:var(--pen);overflow-wrap:anywhere}
.lead{font:400 17px/1.45 var(--serif);color:var(--ink-2);text-align:center;text-wrap:pretty}
.dest{padding:11px 13px;border-radius:var(--r);background:var(--hl-soft);border:1px solid var(--rule-2);border-left:6px solid var(--ink);font-size:14px;text-align:center}
.dest code{display:block;margin:4px 0 6px;font:700 16px/1.35 var(--type);color:var(--ink);overflow-wrap:anywhere}
.dest span{display:block;font-weight:700;color:var(--ink)}
.err{padding:11px 13px;border-radius:0 var(--r) var(--r) 0;background:var(--red-soft);color:var(--ink);font-weight:650;font-size:15px;border-left:5px solid var(--red)}
.note{padding:11px 13px;border-radius:var(--r);background:var(--hl-soft);color:var(--ink);font-size:14.5px;border:1px solid var(--rule-2);border-left:6px solid var(--ink)}
form{display:grid;gap:16px}
.fld{display:grid;gap:6px}
.fld label{font:650 15.5px/1.25 var(--f);font-stretch:82%}
.inp{position:relative;display:flex}
.inp input{width:100%;min-height:46px;padding:10px 78px 10px 13px;border:1px solid var(--edge);border-radius:var(--r);background:var(--field);font-size:16px;font-family:var(--type);letter-spacing:.01em}
.inp input:hover{border-color:var(--ink-2)}
.inp input:focus{outline:2.5px solid var(--pen);outline-offset:1px;border-color:var(--pen)}
.inp input::placeholder{color:var(--ink-3);opacity:1;font-family:var(--f)}
.inp button{position:absolute;right:5px;top:5px;bottom:5px;min-width:60px;padding:0 12px;border-radius:3px;border:1.5px solid var(--ink);background:var(--sheet);color:var(--ink);font:650 14px/1 var(--f);font-stretch:82%;cursor:pointer}
.inp button:hover{background:var(--hl)}
.help{font:italic 400 15px/1.35 var(--serif);color:var(--ink-3)}
.perms{border:1px solid var(--rule-2);border-left:6px solid var(--ink);border-radius:var(--r);background:var(--paper);padding:14px 16px;display:grid;gap:10px}
.perms h2{font:800 17px/1.2 var(--f);font-stretch:70%;text-transform:uppercase;display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.perms h2 span{font:700 12px/1.1 var(--type);letter-spacing:.04em;color:var(--ok);border:1.5px solid var(--ok);padding:2px 8px;border-radius:3px;text-transform:uppercase}
.perms li{display:grid;grid-template-columns:20px minmax(0,1fr);gap:9px;font-size:14.5px;color:var(--ink-2)}
.perms li svg{width:17px;height:17px;margin-top:2px;color:var(--ok)}
.perms li b{color:var(--ink);display:block;font-weight:700}
.perms li.is-personal svg{color:#7a5200}
.perms li em{font:700 11.5px/1.1 var(--type);font-style:normal;display:inline-block;margin-left:6px;color:var(--ink);background:var(--hl);padding:2px 7px;border-radius:3px;text-transform:uppercase}
.perms .bad{color:var(--red);font-weight:700;font-size:14.5px}
.actions{display:grid;gap:10px;margin-top:2px}
.btn{display:flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:0 18px;border:1.5px solid var(--ink);border-radius:6px;background:var(--ink);color:var(--paper);box-shadow:inset 0 0 0 2.5px var(--ink),inset 0 0 0 3.7px var(--paper);font:650 16px/1 var(--f);font-stretch:82%;letter-spacing:.01em;cursor:pointer}
.btn:hover{background:var(--pen-d);border-color:var(--pen-d);box-shadow:inset 0 0 0 2.5px var(--pen-d),inset 0 0 0 3.7px var(--paper)}.btn:active{transform:translateY(1px)}
.btn--ghost{background:var(--sheet);color:var(--ink);box-shadow:inset 0 0 0 2.5px var(--sheet),inset 0 0 0 3.7px var(--ink)}.btn--ghost:hover{background:var(--field);color:var(--pen);border-color:var(--pen);box-shadow:inset 0 0 0 2.5px var(--field),inset 0 0 0 3.7px var(--pen)}
:focus-visible{outline:2.5px solid var(--pen);outline-offset:2px}
.fine{font:italic 400 15px/1.4 var(--serif);color:var(--ink-3);text-align:center;text-wrap:pretty}
.fine code{font:400 13px/1.3 var(--type);background:var(--paper-2);padding:1px 6px;border-radius:3px;overflow-wrap:anywhere;font-style:normal;color:var(--ink)}
.foot{text-align:center;color:var(--ink-3);font:700 12px/1 var(--type);letter-spacing:.06em;text-transform:uppercase}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.001ms!important;animation-iteration-count:1!important;transition-duration:.001ms!important}}
CSS;
}

/** Panelin simge kümesinden (app/icons/ui, Phosphor regular) satır içi SVG */
function oauth_icon(string $name): string
{
    $f = APP . '/icons/ui/' . $name . '.svg';
    $svg = is_file($f) ? (string) file_get_contents($f) : '';
    return str_replace('<svg ', '<svg aria-hidden="true" focusable="false" ', $svg);
}

/** Onay sayfasının gövdesi */
function oauth_consent_view(array $p, string $csrf, string $nonce, string $error = '', int $status = 200): void
{
    $client = $p['client'];
    $name = (string) $client['name'];
    $host = (string) parse_url($p['redirect_uri'], PHP_URL_HOST);
    $initial = mb_strtoupper(mb_substr($name, 0, 1)) ?: '?';
    $hidden = '';
    foreach (['client_id' => $client['id'], 'redirect_uri' => $p['redirect_uri'], 'state' => $p['state'], 'code_challenge' => $p['challenge'],
        'code_challenge_method' => 'S256', 'response_type' => 'code', 'resource' => $p['resource'], 'csrf' => $csrf] as $k => $v) {
        $hidden .= '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">';
    }
    $wave = '';
    for ($i = 0; $i < 7; $i++) {
        $wave .= '<i style="--h:' . round(0.3 + 0.7 * abs(sin($i * 0.9) * cos($i * 0.31)), 2) . ';--i:' . $i . '"></i>';
    }
    $check = oauth_icon('check-circle');
    $plug = oauth_icon('plugs-connected');

    $body = '<main class="wrap">'
        . '<header class="brand"><img src="' . e(asset('admin/logo-light.webp')) . '" alt="' . e((string) cfg('name')) . '" width="130" height="47"><span>' . $plug . 'Güvenli bağlantı</span></header>'
        . '<section class="card" aria-labelledby="t">'
        . '<div class="pair" aria-hidden="true"><span class="pair__a">' . e($initial) . '</span><span class="pair__w">' . $wave . '</span><span class="pair__b"><img src="' . e(asset('admin/logo-light.webp')) . '" alt=""></span></div>'
        . '<h1 id="t"><b>' . e($name) . '</b> sitenizi yönetmek için bağlanmak istiyor</h1>'
        // Uygulama adını kendisi seçer; gerçek hedef izin sonrası dönülen adrestir, bu yüzden başlığın hemen altında gösterilir
        . '<p class="dest">Bağlantı sonrası döneceğiniz adres: <code>' . e($host) . '</code><span>Yalnızca bu bağlantıyı o uygulamada kendiniz başlattıysanız devam edin.</span></p>'
        . '<p class="lead">Bağlanınca bu uygulama, anahtarınızın izin verdiği ölçüde sitenin duyurularını ve içeriğini okuyabilir ve değiştirebilir. Yapılan her değişiklik kaydedilir ve geri alınabilir.</p>'
        . ($error !== '' ? '<p class="err" role="alert">' . e($error) . '</p>' : '')
        . '<form method="post" action="' . e(url('oauth/authorize')) . '" id="f" autocomplete="off">' . $hidden
        . '<div class="fld"><label for="k">Erişim anahtarınız</label>'
        . '<div class="inp"><input id="k" name="key" type="password" required placeholder="arsl_ ile başlayan anahtar" autocomplete="off" autocapitalize="off" autocorrect="off" spellcheck="false" aria-describedby="kh"><button type="button" id="tg" aria-pressed="false">Göster</button></div>'
        . '<p class="help" id="kh">Anahtarı site yöneticisi panelde "Yapay zekâ erişimi" bölümünden oluşturur. Anahtarınız yoksa yöneticiden isteyin.</p></div>'
        . '<div class="perms" id="pm" hidden aria-live="polite"></div>'
        . '<noscript><p class="note">Bu bağlantıya, yazacağınız anahtarın izinleri verilir. İzinler panelde anahtarın yanında yazar.</p></noscript>'
        . '<div class="actions"><button class="btn" type="submit" name="action" value="allow">İzin ver</button>'
        . '<button class="btn btn--ghost" type="submit" name="action" value="deny" formnovalidate>Vazgeç</button></div></form>'
        . '<p class="fine">İzin verirseniz bu sayfa sizi <code>' . e($host) . '</code> adresine geri götürür. Erişimi istediğiniz zaman yönetim panelinden kaldırabilirsiniz.</p>'
        . '</section><p class="foot">' . e((string) cfg('name')) . '</p></main>'
        . '<script nonce="' . e($nonce) . '">' . oauth_consent_js($check) . '</script>';
    oauth_html('Bağlantı izni', $body, $status, $nonce);
}

function oauth_consent_js(string $checkSvg): string
{
    $svg = json_encode($checkSvg, JSON_UNESCAPED_SLASHES);
    return <<<JS
(function () {
  var f = document.getElementById('f'), k = document.getElementById('k'), pm = document.getElementById('pm'), tg = document.getElementById('tg');
  var CHECK = $svg, timer = null, last = '';
  if (!f || !k) return;
  tg.addEventListener('click', function () {
    var show = k.type === 'password';
    k.type = show ? 'text' : 'password';
    tg.textContent = show ? 'Gizle' : 'Göster';
    tg.setAttribute('aria-pressed', show ? 'true' : 'false');
  });
  function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
  function render(r) {
    if (!r || !r.ok) { pm.hidden = false; pm.innerHTML = '<p class="bad">' + esc((r && r.error) || 'Anahtar doğrulanamadı.') + '</p>'; return; }
    var h = '<h2>Bu anahtar şunlara izin veriyor <span>' + esc(r.name) + '</span></h2><ul>';
    r.scopes.forEach(function (s) {
      h += '<li class="' + (s.kisisel_veri ? 'is-personal' : '') + '">' + CHECK + '<span><b>' + esc(s.baslik) + (s.kisisel_veri ? '<em>Kişisel veri</em>' : '') + '</b>' + esc(s.aciklama) + '</span></li>';
    });
    pm.innerHTML = h + '</ul>';
    pm.hidden = false;
  }
  function check() {
    var v = k.value.trim();
    if (v === last) return;
    last = v;
    if (!/^arsl_[0-9A-Za-z]{40}$/.test(v)) { pm.hidden = true; pm.innerHTML = ''; return; }
    var fd = new FormData(f); fd.set('action', 'check'); fd.set('key', v);
    fetch(f.getAttribute('action'), { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (x) { return x.json(); }).then(render).catch(function () { pm.hidden = true; });
  }
  k.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(check, 250); });
  k.addEventListener('paste', function () { clearTimeout(timer); timer = setTimeout(check, 30); });
  k.addEventListener('blur', check);
  if (k.value) check();
})();
JS;
}

function oauth_authorize(): void
{
    $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $nonce = base64_encode(random_bytes(12));
    if ($method === 'OPTIONS') {
        mcp_cors();
        http_response_code(204);
        exit;
    }
    if (!in_array($method, ['GET', 'HEAD', 'POST'], true)) {
        http_response_code(405);
        header('Allow: GET, POST, OPTIONS');
        exit;
    }
    $q = $method === 'POST' ? $_POST : $_GET;
    $p = oauth_authorize_params($q);
    // Geçersiz istekte yönlendirme yapılmaz, hata burada gösterilir: istemci kaydı herkese açık olduğundan, hatalı bir istekle
    // ziyaretçi bu siteden başkasının adresine yönlendirilebilirdi
    $bad = $p['fatal'] ?? $p['error_description'] ?? null;
    if ($bad !== null) {
        oauth_html('Bağlantı kurulamadı', '<main class="wrap"><section class="card"><h1>Bağlantı kurulamadı</h1><p class="err">' . e($bad) . '</p>'
            . '<p class="fine">Hiçbir izin verilmedi. Bu sayfayı kapatıp uygulamadan bağlantıyı yeniden başlatın.</p></section></main>', 400, $nonce);
    }
    $state = (string) $p['state'];

    if ($method !== 'POST') {
        oauth_consent_view($p, oauth_csrf_issue(), $nonce);
    }

    /* ---- POST ---- */
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $csrfToken = is_string($_POST['csrf'] ?? null) ? $_POST['csrf'] : '';
    if (!oauth_csrf_ok($csrfToken)) {
        if ($action === 'check') {
            oauth_json(403, ['ok' => false, 'error' => 'Oturum doğrulanamadı. Sayfayı yenileyin (tarayıcınızda çerezlere izin verilmiş olmalı).']);
        }
        oauth_consent_view($p, oauth_csrf_issue(), $nonce, 'Oturum doğrulanamadı. Sayfa yenilendi; lütfen anahtarınızı yeniden yazın. Sorun sürerse tarayıcınızda çerezlere izin verildiğinden emin olun.', 403);
    }

    if ($action === 'deny') {
        oauth_authorize_redirect($p['redirect_uri'], ['error' => 'access_denied', 'error_description' => 'Kullanıcı bağlantıya izin vermedi.', 'state' => $state]);
    }
    if (!in_array($action, ['allow', 'check'], true)) {
        oauth_consent_view($p, oauth_csrf_issue(), $nonce, 'Geçersiz işlem.', 400);
    }

    // Hatalı anahtar denemesi sınırı: IP başına 15 dakikada 5
    if (mcp_ip_blocked('auth', 5, 900)) {
        $m = 'Çok fazla hatalı deneme yapıldı. 15 dakika sonra tekrar deneyin.';
        if ($action === 'check') {
            oauth_json(429, ['ok' => false, 'error' => $m], ['Retry-After: 900']);
        }
        header('Retry-After: 900');
        oauth_consent_view($p, oauth_csrf_issue(), $nonce, $m, 429);
    }
    $secret = is_string($_POST['key'] ?? null) ? trim($_POST['key']) : '';
    $key = $secret !== '' ? mcp_key_by_secret($secret) : null;
    if (!$key || !mcp_key_active($key)) {
        mcp_ip_limit('auth', 5, 900, true);
        $m = $key ? 'Bu anahtarın süresi dolmuş ya da erişimi kaldırılmış. Yöneticiden yeni anahtar isteyin.' : 'Erişim anahtarı geçersiz. Anahtarı eksiksiz yapıştırdığınızdan emin olun.';
        if ($action === 'check') {
            oauth_json(200, ['ok' => false, 'error' => $m]);
        }
        oauth_consent_view($p, oauth_csrf_issue(), $nonce, $m);
    }
    $scopes = mcp_clean_scopes((array) ($key['scopes'] ?? []));
    if ($action === 'check') {
        oauth_json(200, ['ok' => true, 'name' => (string) $key['name'], 'scopes' => oauth_scope_cards($scopes)]);
    }

    // İzin verildi: tek kullanımlık kod
    $code = 'arac_' . mcp_random(40);
    $row = ['client_id' => $p['client']['id'], 'redirect_uri' => $p['redirect_uri'], 'challenge' => $p['challenge'], 'key_id' => (string) $key['id'],
        'scopes' => $scopes, 'resource' => $p['resource'], 'family' => bin2hex(random_bytes(8)), 'expires' => time() + OAUTH_CODE_TTL, 'used' => false];
    mcp_json_update('oauth', function (array &$d) use ($code, $row): void {
        $d += ['clients' => [], 'codes' => [], 'access' => [], 'refresh' => []];
        oauth_prune($d);
        $d['codes'][mcp_hash($code)] = $row;
    });
    oauth_authorize_redirect($p['redirect_uri'], ['code' => $code, 'state' => $state]);
}
