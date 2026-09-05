<?php
/**
 * Official Meta OAuth 2.0 Callback Handler
 * Exchanges authorization code for Long-Lived Page Access Tokens (Never Expire / 60 days)
 * Automatically detects and configures linked Instagram Business & Facebook Pages
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/settings.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/services/CacheService.php';
require_once __DIR__ . '/services/MetaApiService.php';

Security::applySecurityHeaders(false);
Auth::initSession();

$userId = Auth::id() ?: ($_SESSION['meta_oauth_user_id'] ?? ($_SESSION['user_id'] ?? 1));
$error = $_GET['error'] ?? '';
$errorReason = $_GET['error_reason'] ?? '';
$errorDesc = $_GET['error_description'] ?? '';
$code = $_GET['code'] ?? '';
$state = $_GET['state'] ?? '';

$expectedState = $_SESSION['meta_oauth_state'] ?? '';
unset($_SESSION['meta_oauth_state']);

$appId = Settings::get('meta_app_id', '', $userId) ?: Settings::get('meta_app_id', '', 1);
$appSecret = Settings::get('meta_app_secret', '', $userId) ?: Settings::get('meta_app_secret', '', 1);

// Determine redirect URI
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUri = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$redirectUri = $protocol . '://' . $host . ($baseUri !== '' ? $baseUri : '') . '/callback-meta.php';

$status = 'processing';
$message = '';
$detectedAccounts = [];

if (!empty($error)) {
    $status = 'error';
    $message = 'Cancelaste o rechazaste la autorización en Meta: ' . htmlspecialchars($errorDesc ?: $errorReason ?: $error, ENT_QUOTES, 'UTF-8');
} elseif (empty($code) || empty($state) || !hash_equals($expectedState, $state)) {
    $status = 'error';
    $message = 'Error de validación de seguridad anti-CSRF (State inválido o sesión expirada). Por favor, intenta conectar nuevamente.';
} elseif (empty($appSecret)) {
    $status = 'error';
    $message = 'Falta configurar el Meta App Secret en la pestaña de Meta Graph API para poder intercambiar el código por el Token oficial.';
} else {
    try {
        // 1. Exchange code for Short-Lived User Access Token
        $tokenUrl = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code
        ]);

        $ch = curl_init($tokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $res = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode($res, true);
        $shortLivedToken = $tokenData['access_token'] ?? '';

        if (empty($shortLivedToken)) {
            $errText = $tokenData['error']['message'] ?? 'Error desconocido al intercambiar código';
            throw new RuntimeException("Meta Token Error: {$errText}");
        }

        // 2. Exchange Short-Lived Token for Long-Lived User Access Token (60 days)
        $longTokenUrl = 'https://graph.facebook.com/v19.0/oauth/access_token?' . http_build_query([
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken
        ]);

        $ch = curl_init($longTokenUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $resLong = curl_exec($ch);
        curl_close($ch);

        $longData = json_decode($resLong, true);
        $longLivedUserToken = $longData['access_token'] ?? $shortLivedToken;

        // 3. Query /me/accounts with Long-Lived Token to fetch Permanent Page Tokens and Linked Instagram accounts
        $accountsUrl = 'https://graph.facebook.com/v19.0/me/accounts?' . http_build_query([
            'fields' => 'id,name,access_token,category,instagram_business_account{id,username,name,profile_picture_url}',
            'limit' => '100',
            'access_token' => $longLivedUserToken
        ]);

        $ch = curl_init($accountsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $resAcc = curl_exec($ch);
        curl_close($ch);

        $accountsData = json_decode($resAcc, true);
        $pages = $accountsData['data'] ?? [];

        if (empty($pages)) {
            $status = 'warning';
            $message = 'Te autenticaste con Meta, pero no se encontraron Páginas de Facebook administradas por tu cuenta de usuario. Asegúrate de crear una Página de Facebook y vincular tu cuenta de Instagram Profesional.';
        } else {
            $pdo = Database::getConnection();
            $primaryPageToken = '';
            $primaryIgId = '';

            foreach ($pages as $page) {
                $pageId = $page['id'];
                $pageName = $page['name'];
                $pageToken = $page['access_token'] ?? '';
                $igAccount = $page['instagram_business_account'] ?? null;

                if (empty($primaryPageToken) && !empty($pageToken)) {
                    $primaryPageToken = $pageToken;
                }

                $igId = $igAccount['id'] ?? null;
                $igUsername = $igAccount['username'] ?? null;
                $igAvatar = $igAccount['profile_picture_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($pageName) . '&background=6366f1&color=fff';

                if (empty($primaryIgId) && !empty($igId)) {
                    $primaryIgId = $igId;
                }

                // Add Facebook Page to display list
                $detectedAccounts[] = [
                    'id' => $pageId,
                    'name' => $pageName,
                    'platform' => 'facebook',
                    'handle' => 'fb_' . $pageId,
                    'is_ig' => false
                ];

                // 1. Upsert Facebook Page account
                $stmtCheckFb = $pdo->prepare("SELECT id FROM accounts WHERE user_id = :uid AND page_id = :pid AND platform = 'facebook' LIMIT 1");
                $stmtCheckFb->execute([':uid' => $userId, ':pid' => $pageId]);
                $existingFb = $stmtCheckFb->fetch();

                if ($existingFb) {
                    $stmtUp = $pdo->prepare("
                        UPDATE accounts 
                        SET access_token = :token, account_name = :name, avatar_url = :avatar, is_active = 1
                        WHERE id = :id
                    ");
                    $stmtUp->execute([
                        ':token' => $pageToken,
                        ':name' => $pageName,
                        ':avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($pageName) . '&background=1877f2&color=fff',
                        ':id' => $existingFb['id']
                    ]);
                } else {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO accounts (user_id, platform, account_name, account_handle, page_id, avatar_url, access_token, is_active)
                        VALUES (:uid, 'facebook', :name, :handle, :pid, :avatar, :token, 1)
                    ");
                    $stmtIns->execute([
                        ':uid' => $userId,
                        ':name' => $pageName,
                        ':handle' => 'fb_' . $pageId,
                        ':pid' => $pageId,
                        ':avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($pageName) . '&background=1877f2&color=fff',
                        ':token' => $pageToken
                    ]);
                }

                // 2. Upsert Instagram Business Account if linked to this Page
                if (!empty($igId)) {
                    $igDisplayName = $igAccount['name'] ?? ($igUsername ? "@{$igUsername}" : $pageName);

                    // Add Instagram account to display list
                    $detectedAccounts[] = [
                        'id' => $igId,
                        'name' => $igDisplayName,
                        'platform' => 'instagram',
                        'handle' => $igUsername ? "@{$igUsername}" : '@ig_' . $igId,
                        'is_ig' => true
                    ];

                    $stmtCheckIg = $pdo->prepare("SELECT id FROM accounts WHERE user_id = :uid AND page_id = :igid AND platform = 'instagram' LIMIT 1");
                    $stmtCheckIg->execute([':uid' => $userId, ':igid' => $igId]);
                    $existingIg = $stmtCheckIg->fetch();

                    if ($existingIg) {
                        $stmtUpIg = $pdo->prepare("
                            UPDATE accounts 
                            SET access_token = :token, account_name = :name, account_handle = :handle, avatar_url = :avatar, is_active = 1
                            WHERE id = :id
                        ");
                        $stmtUpIg->execute([
                            ':token' => $pageToken,
                            ':name' => $igDisplayName,
                            ':handle' => $igUsername ? "@{$igUsername}" : '@ig_' . $igId,
                            ':avatar' => $igAvatar,
                            ':id' => $existingIg['id']
                        ]);
                    } else {
                        $stmtInsIg = $pdo->prepare("
                            INSERT INTO accounts (user_id, platform, account_name, account_handle, page_id, avatar_url, access_token, is_active)
                            VALUES (:uid, 'instagram', :name, :handle, :igid, :avatar, :token, 1)
                        ");
                        $stmtInsIg->execute([
                            ':uid' => $userId,
                            ':name' => $igDisplayName,
                            ':handle' => $igUsername ? "@{$igUsername}" : '@ig_' . $igId,
                            ':igid' => $igId,
                            ':avatar' => $igAvatar,
                            ':token' => $pageToken
                        ]);
                    }
                }
            }

            // Remove any leftover demo/mock accounts from earlier seeds
            $pdo->prepare("DELETE FROM accounts WHERE user_id = :uid AND (page_id LIKE 'page_stoic_%' OR page_id LIKE 'page_user_%' OR page_id LIKE 'mock_%')")->execute([':uid' => $userId]);

            // Save active credentials in settings
            if (!empty($primaryPageToken)) {
                Settings::set('meta_page_access_token', $primaryPageToken, $userId);
            }
            if (!empty($primaryIgId)) {
                Settings::set('meta_instagram_account_id', $primaryIgId, $userId);
            }

            // Invalidate in-memory caches
            CacheService::invalidateUserSettings($userId);
            CacheService::invalidateAccountMappings();

            $status = 'success';
            $message = '¡Conexión oficial OAuth 2.0 completada exitosamente con Meta! Tus Páginas y cuentas de Instagram han sido vinculadas.';
        }

    } catch (Throwable $e) {
        $status = 'error';
        $message = 'Error durante la autenticación con Meta Graph API: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Conexión Oficial con Meta | XINDRO</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0b0f19;
      font-family: 'Plus Jakarta Sans', sans-serif;
      padding: 20px;
    }
    .oauth-card {
      background: #111827;
      border: 1px solid #1f2937;
      border-radius: 24px;
      max-width: 580px;
      width: 100%;
      padding: 40px 32px;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.6);
      text-align: center;
      color: #e2e8f0;
    }
    .status-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      margin-bottom: 20px;
    }
    .status-icon.success { background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981; color: #10b981; }
    .status-icon.error { background: rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; color: #ef4444; }
    .status-icon.warning { background: rgba(245, 158, 11, 0.15); border: 2px solid #f59e0b; color: #f59e0b; }
    .btn-return {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #6366f1, #3b82f6);
      color: #fff;
      text-decoration: none;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 14px 28px;
      border-radius: 12px;
      margin-top: 24px;
      transition: all 0.2s;
    }
    .btn-return:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
    }
    .account-badge {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid #334155;
      border-radius: 12px;
      padding: 12px 16px;
      text-align: left;
      margin-top: 10px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
  </style>
</head>
<body>

  <div class="oauth-card">
    <?php if ($status === 'success'): ?>
      <div class="status-icon success">✓</div>
      <h2 style="font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 12px;">¡Conexión con Meta Exitosa!</h2>
      <p style="font-size: 0.92rem; color: #94a3b8; line-height: 1.6; margin-bottom: 24px;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>

      <?php if (!empty($detectedAccounts)): ?>
        <div style="text-align: left; margin-bottom: 20px;">
          <h4 style="font-size: 0.85rem; text-transform: uppercase; color: #818cf8; font-weight: 800; margin-bottom: 8px;">Cuentas Vinculadas Automáticamente:</h4>
          <?php foreach ($detectedAccounts as $acc): ?>
            <div class="account-badge">
              <div>
                <strong style="color: #fff; font-size: 0.9rem;">
                  <?= ($acc['platform'] === 'instagram') ? '📸 ' : '📘 ' ?><?= htmlspecialchars($acc['name'], ENT_QUOTES, 'UTF-8') ?>
                </strong>
                <div style="font-size: 0.78rem; color: #94a3b8; margin-top: 2px;">
                  <?= ($acc['platform'] === 'instagram') ? 'Instagram: ' . htmlspecialchars($acc['handle'], ENT_QUOTES, 'UTF-8') : 'Página de Facebook (' . htmlspecialchars($acc['id'], ENT_QUOTES, 'UTF-8') . ')' ?>
                </div>
              </div>
              <span style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.15); color: #34d399; font-weight: 700; padding: 4px 8px; border-radius: 6px;">Conectada</span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <a href="dashboard.php" class="btn-return">
        <span>Ir al Panel de Control</span>
        <span>→</span>
      </a>

    <?php elseif ($status === 'warning'): ?>
      <div class="status-icon warning">⚠️</div>
      <h2 style="font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 12px;">Autenticado con Permisos Parciales</h2>
      <p style="font-size: 0.92rem; color: #cbd5e1; line-height: 1.6; margin-bottom: 24px;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
      <a href="dashboard.php" class="btn-return">
        <span>Volver a la Configuración</span>
      </a>

    <?php else: ?>
      <div class="status-icon error">✕</div>
      <h2 style="font-size: 1.6rem; font-weight: 800; color: #fff; margin-bottom: 12px;">Error en la Conexión</h2>
      <p style="font-size: 0.92rem; color: #fca5a5; line-height: 1.6; margin-bottom: 24px;"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
      <a href="dashboard.php" class="btn-return" style="background: #374151;">
        <span>Volver a Intentar</span>
      </a>
    <?php endif; ?>
  </div>

</body>
</html>
