<?php
declare(strict_types=1);

/**
 * Yapay zekâ erişimi (MCP sunucusu ve OAuth) adresleri. index.php tarafından, görünürlük denetiminden önce çağrılır;
 * kendi adresleri değilse sessizce döner ve yönlendirici devam eder.
 *
 *   /mcp                                         MCP sunucusu (POST)
 *   /.well-known/oauth-protected-resource[/mcp]  kaynak meta verisi
 *   /.well-known/oauth-authorization-server      yetkilendirme sunucusu meta verisi (openid-configuration aynı belge)
 *   /oauth/register  /oauth/authorize  /oauth/token  /oauth/revoke
 */

$__mcpPath = isset($path) ? (string) $path : trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');

if ($__mcpPath === 'mcp' || str_starts_with($__mcpPath, 'oauth/') || str_starts_with($__mcpPath, '.well-known/oauth-') || str_starts_with($__mcpPath, '.well-known/openid-configuration')) {
    // JSON yanıtlar uyarı metniyle bozulmasın (hatalar sunucu günlüğüne gider)
    @ini_set('display_errors', '0');
    require_once __DIR__ . '/oauth.php';

    $__mcpMethod = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($__mcpMethod === 'OPTIONS' && $__mcpPath !== 'oauth/authorize') {
        mcp_cors();
        http_response_code(204);
        exit;
    }

    switch ($__mcpPath) {
        case 'mcp':
            mcp_serve();
            break;
        case '.well-known/oauth-protected-resource':
        case '.well-known/oauth-protected-resource/mcp':
            oauth_serve_metadata(oauth_resource_metadata());
            break;
        case '.well-known/oauth-authorization-server':
        case '.well-known/oauth-authorization-server/mcp':
        case '.well-known/openid-configuration':
        case '.well-known/openid-configuration/mcp':
            oauth_serve_metadata(oauth_server_metadata());
            break;
        case 'oauth/register':
            oauth_register();
            break;
        case 'oauth/authorize':
            oauth_authorize();
            break;
        case 'oauth/token':
            oauth_token();
            break;
        case 'oauth/revoke':
            oauth_revoke();
            break;
    }
    // Buraya gelinirse adres bize ait değildir (örn. /oauth/bilinmeyen): yönlendirici 404 verir
}
unset($__mcpPath, $__mcpMethod);
