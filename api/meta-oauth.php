<?php
/**
 * REST API / OAuth Initiator for Meta Graph API (Facebook & Instagram)
 * Secure OAuth 2.0 Authorization Flow Generator with Anti-CSRF State Protection
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/settings.php';

Security::applySecurityHeaders(false);
Auth::requireAuth(false);

$userId = Auth::id();
$appId = Settings::get('meta_app_id', '', $userId);

if (empty($appId)) {
    // If user has not configured app_id yet, fallback to default or prompt error
    $appId = Settings::get('meta_app_id', '', 1);
}

if (empty($appId)) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Configura primero tu Meta App ID en la pestaña de Meta Graph API antes de iniciar la conexión OAuth.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Generate cryptographically secure anti-CSRF state token
$state = 'meta_oauth_' . bin2hex(random_bytes(16));
$_SESSION['meta_oauth_state'] = $state;
$_SESSION['meta_oauth_user_id'] = $userId;

// Determine absolute redirect URI
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUri = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
$redirectUri = $protocol . '://' . $host . ($baseUri !== '' ? $baseUri : '') . '/callback-meta.php';

// Requested Meta App Review permissions
$requestedScopes = $_GET['scopes'] ?? '';
if (!empty($requestedScopes)) {
    $scopes = array_filter(array_map('trim', explode(',', $requestedScopes)));
} else {
    // Core active scopes for Facebook Pages, Instagram Business, Comments Moderation & Insights
    $scopes = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_manage_posts',
        'pages_manage_engagement',
        'instagram_basic',
        'instagram_manage_comments',
        'instagram_manage_insights'
    ];
}

$authUrl = 'https://www.facebook.com/v19.0/dialog/oauth?' . http_build_query([
    'client_id' => $appId,
    'redirect_uri' => $redirectUri,
    'state' => $state,
    'scope' => implode(',', $scopes),
    'response_type' => 'code',
    'auth_type' => 'rerequest'
]);

if (isset($_GET['json']) && $_GET['json'] === '1') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'auth_url' => $authUrl,
        'redirect_uri' => $redirectUri
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

// Direct 302 redirect to Meta OAuth Dialog
header('Location: ' . $authUrl);
exit;
