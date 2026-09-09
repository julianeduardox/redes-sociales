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
    if (isset($_GET['json']) && $_GET['json'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Configura primero tu Meta App ID en la pestaña de Meta Graph API antes de iniciar la conexión OAuth.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    // Render user-friendly error UI for browser/popup
    $isAdmin = Auth::isAdmin();
    $errorMessage = $isAdmin 
        ? 'Como administrador, ingresa tu <strong>Meta App ID</strong> y <strong>Meta App Secret</strong> en la pestaña <em>⚙️ Meta Graph API &gt; Configuración Avanzada</em> para habilitar la conexión automática para ti y todos tus usuarios.'
        : 'La plataforma aún está sincronizando la aplicación oficial de Meta. Por favor, contacta al administrador del sistema para activar la vinculación.';
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Conexión con Meta | XINDRO</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
      <style>
        body { background: #0b0f19; color: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 24px; box-sizing: border-box; }
        .card { background: #111827; border: 1px solid #1f2937; border-radius: 20px; padding: 36px 28px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .icon { font-size: 2.8rem; margin-bottom: 16px; display: inline-block; }
        h2 { font-size: 1.35rem; font-weight: 800; margin: 0 0 12px 0; color: #fff; }
        p { font-size: 0.88rem; color: #94a3b8; line-height: 1.6; margin: 0 0 24px 0; }
        .btn { display: inline-block; background: #3b82f6; color: #fff; border: none; font-family: inherit; font-weight: 700; font-size: 0.9rem; padding: 12px 24px; border-radius: 10px; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn:hover { background: #2563eb; }
      </style>
    </head>
    <body>
      <div class="card">
        <span class="icon">⚙️</span>
        <h2>Configuración Requerida en Meta</h2>
        <p><?= $errorMessage ?></p>
        <button class="btn" onclick="window.close();">Entendido / Cerrar Ventana</button>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Enforce plan multi-account limit
$pdo = Database::getConnection();
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE user_id = ? AND is_active = 1");
$stmtCount->execute([$userId]);
$activeAccounts = (int)$stmtCount->fetchColumn();

$user = Auth::user();
$plan = $user['plan'] ?? 'starter';
$planInfo = Database::getPlanDetails($plan);
$maxAccounts = (int)($user['max_accounts'] ?? $planInfo['accounts'] ?? 1);

if ($activeAccounts >= $maxAccounts) {
    if (isset($_GET['json']) && $_GET['json'] === '1') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => "Has alcanzado el límite máximo de {$maxAccounts} cuenta(s) de tu {$planInfo['name']}. Mejora tu plan para vincular más cuentas.",
            'upgrade_required' => true,
            'current_plan' => $plan,
            'max_accounts' => $maxAccounts
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
      <meta charset="UTF-8">
      <title>Límite de Cuentas Alcanzado | XINDRO</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
      <style>
        body { background: #0b0f19; color: #f1f5f9; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 24px; box-sizing: border-box; }
        .card { background: #111827; border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 20px; padding: 36px 28px; max-width: 480px; width: 100%; text-align: center; box-shadow: 0 20px 40px rgba(0,0,0,0.6); }
        .icon { font-size: 2.8rem; margin-bottom: 16px; display: inline-block; }
        h2 { font-size: 1.35rem; font-weight: 800; margin: 0 0 12px 0; color: #fff; }
        p { font-size: 0.88rem; color: #94a3b8; line-height: 1.6; margin: 0 0 24px 0; }
        .badge { display: inline-block; background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; margin-bottom: 14px; }
        .btn { display: inline-block; background: linear-gradient(135deg, #f59e0b, #d97706); color: #fff; border: none; font-family: inherit; font-weight: 700; font-size: 0.9rem; padding: 12px 20px; border-radius: 10px; cursor: pointer; text-decoration: none; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
        .btn-secondary { background: #1f2937; color: #cbd5e1; box-shadow: none; border: 1px solid #374151; }
      </style>
    </head>
    <body>
      <div class="card">
        <span class="icon">⭐</span><br>
        <div class="badge"><?= htmlspecialchars($planInfo['name'], ENT_QUOTES, 'UTF-8') ?> (<?= $maxAccounts ?> <?= $maxAccounts === 1 ? 'cuenta' : 'cuentas' ?>)</div>
        <h2>Límite de Cuentas Alcanzado</h2>
        <p>Ya tienes <strong><?= $activeAccounts ?> cuenta(s)</strong> vinculada(s), que es el límite máximo permitido en tu <strong><?= htmlspecialchars($planInfo['name'], ENT_QUOTES, 'UTF-8') ?></strong>.<br><br>Para conectar nuevas páginas o cuentas de Instagram adicionales, actualiza tu suscripción al siguiente nivel.</p>
        <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
          <button class="btn" onclick="if(window.opener && window.opener.App){ window.opener.App.showUpgradePlanModal(); } window.close();">Mejorar Mi Plan ⭐</button>
          <button class="btn btn-secondary" onclick="window.close();">Cerrar</button>
        </div>
      </div>
    </body>
    </html>
    <?php
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
    // Core active scopes for Facebook Pages, Instagram Business, Comments Moderation & Real-Time Insights (Meta Graph v19+)
    $scopes = [
        'pages_show_list',
        'pages_read_engagement',
        'pages_read_user_content',
        'read_insights',
        'pages_manage_posts',
        'pages_manage_metadata',
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
