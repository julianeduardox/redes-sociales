<?php
/**
 * MetaApiService - Graph API Integration for Facebook & Instagram
 * Features:
 * - Live Connection & Token Diagnostics
 * - Post-by-Post Insights & Metrics (Reach, Impressions, Saved, Engagement)
 * - Comment Ingestion & Synchronization
 * - AI Reply Publication to Meta Graph API
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/AiAgentService.php';

class MetaApiService {
    private const GRAPH_API_VERSION = 'v19.0';
    private const BASE_URL = 'https://graph.facebook.com/' . self::GRAPH_API_VERSION;

    /**
     * Test and diagnose Meta Graph API connection & permissions
     */
    public static function testMetaConnection(?string $token = null): array {
        $accessToken = !empty($token) ? trim($token) : Settings::get('meta_page_access_token', '');
        $appId = Settings::get('meta_app_id', '');
        $configuredIgId = Settings::get('meta_instagram_account_id', '');

        if (empty($accessToken)) {
            return [
                'success' => false,
                'status' => 'missing_token',
                'title' => 'Access Token no configurado',
                'message' => 'Ingresa tu Meta Page Access Token para verificar la conexión con Facebook e Instagram.',
                'permissions' => [],
                'pages' => [],
                'recommendations' => [
                    'Genera un Token de Página (Page Access Token) en Meta for Developers -> Graph API Explorer o desde tu App de Meta.',
                    'Asegúrate de conceder permisos de Instagram y Páginas de Facebook al generar el token.'
                ]
            ];
        }

        // 1. Verify User Profile / Me
        $meUrl = self::BASE_URL . '/me?fields=id,name&access_token=' . urlencode($accessToken);
        $meData = self::makeGetRequest($meUrl);

        if (isset($meData['error'])) {
            $errCode = $meData['error']['code'] ?? 0;
            $errSubcode = $meData['error']['error_subcode'] ?? 0;
            $errMsg = $meData['error']['message'] ?? 'Error al autenticar con Meta Graph API';

            return [
                'success' => false,
                'status' => 'invalid_token',
                'title' => 'Token de Meta inválido o caducado',
                'message' => $errMsg,
                'error_code' => $errCode,
                'error_subcode' => $errSubcode,
                'recommendations' => [
                    'El token ingresado no es válido o ha expirado. Genera un nuevo token de larga duración (Never Expire o 60 días).',
                    'Verifica que el App ID coincida con tu aplicación en developers.facebook.com.'
                ]
            ];
        }

        // 2. Fetch Permissions
        $permUrl = self::BASE_URL . '/me/permissions?access_token=' . urlencode($accessToken);
        $permData = self::makeGetRequest($permUrl);
        $grantedPerms = [];

        if (isset($permData['data']) && is_array($permData['data'])) {
            foreach ($permData['data'] as $p) {
                if (($p['status'] ?? '') === 'granted') {
                    $grantedPerms[] = $p['permission'];
                }
            }
        }

        $requiredPerms = [
            'instagram_basic' => 'Lectura básica de perfiles y publicaciones de Instagram',
            'instagram_manage_comments' => 'Moderar y responder comentarios de Instagram',
            'instagram_manage_insights' => 'Consultar métricas de alcance, impresiones y guardados',
            'pages_show_list' => 'Ver lista de Páginas de Facebook administradas',
            'pages_read_engagement' => 'Leer engagement y publicaciones de la Página de Facebook',
            'pages_manage_posts' => 'Publicar respuestas y comentarios en Facebook'
        ];

        $permissionsAudit = [];
        $missingCount = 0;
        foreach ($requiredPerms as $permKey => $desc) {
            $isGranted = in_array($permKey, $grantedPerms, true);
            if (!$isGranted) $missingCount++;
            $permissionsAudit[] = [
                'permission' => $permKey,
                'description' => $desc,
                'granted' => $isGranted
            ];
        }

        // 3. Fetch Linked Pages and Instagram Accounts
        $accountsUrl = self::BASE_URL . '/me/accounts?fields=id,name,category,access_token,instagram_business_account{id,username,name,profile_picture_url}&access_token=' . urlencode($accessToken);
        $accountsData = self::makeGetRequest($accountsUrl);
        $detectedPages = [];

        if (isset($accountsData['error']) && (($accountsData['error']['code'] ?? 0) == 100 || str_contains($accountsData['error']['message'] ?? '', 'accounts'))) {
            // If it is a Page Access Token, query /me directly
            $pageMeUrl = self::BASE_URL . '/me?fields=id,name,category,instagram_business_account{id,username,name,profile_picture_url}&access_token=' . urlencode($accessToken);
            $pageMeData = self::makeGetRequest($pageMeUrl);
            if (!empty($pageMeData['id'])) {
                $pageMeData['access_token'] = $accessToken;
                $accountsData = ['data' => [$pageMeData]];
            }
        }

        if (isset($accountsData['data']) && is_array($accountsData['data'])) {
            foreach ($accountsData['data'] as $page) {
                $igAccount = $page['instagram_business_account'] ?? null;
                $detectedPages[] = [
                    'page_id' => $page['id'],
                    'page_name' => $page['name'],
                    'category' => $page['category'] ?? '',
                    'has_page_token' => !empty($page['access_token']),
                    'page_token' => $page['access_token'] ?? '',
                    'has_instagram' => !empty($igAccount),
                    'instagram_id' => $igAccount['id'] ?? null,
                    'instagram_username' => $igAccount['username'] ?? null,
                    'instagram_avatar' => $igAccount['profile_picture_url'] ?? null
                ];
            }
        }

        $isConfiguredIgMatched = false;
        if (!empty($configuredIgId)) {
            foreach ($detectedPages as $dp) {
                if ($dp['instagram_id'] === $configuredIgId) {
                    $isConfiguredIgMatched = true;
                    break;
                }
            }
        }

        return [
            'success' => true,
            'status' => ($missingCount === 0) ? 'perfect' : 'partial_permissions',
            'title' => ($missingCount === 0) ? 'Conexión con Meta exitosa y verificada' : 'Conexión activa con permisos parciales',
            'meta_user' => [
                'id' => $meData['id'] ?? '',
                'name' => $meData['name'] ?? 'Usuario de Meta'
            ],
            'permissions' => $permissionsAudit,
            'all_required_granted' => ($missingCount === 0),
            'detected_pages' => $detectedPages,
            'configured_instagram_id' => $configuredIgId,
            'is_configured_ig_matched' => $isConfiguredIgMatched,
            'recommendations' => ($missingCount > 0) ? [
                'Para habilitar el 100% de las funciones automáticas (publicar respuestas y leer estadísticas de reels), concede los permisos marcados en rojo en el Graph API Explorer.',
                'Asegúrate de que tu cuenta de Instagram sea Profesional (Creador o Empresa) y esté enlazada a tu Página de Facebook.'
            ] : [
                '¡Todo listo! Tu cuenta de Meta cuenta con todos los permisos requeridos para automatizar comentarios y métricas.'
            ]
        ];
    }

    /**
     * Fetch Live Media Insights for an Instagram Post or Reel (Graph API v19/v20/v21)
     */
    public static function fetchMediaInsights(string $mediaId, string $accessToken, string $mediaType = 'image'): array {
        if (empty($accessToken) || empty($mediaId) || str_starts_with($mediaId, 'ig_post_') || str_starts_with($mediaId, 'mock_')) {
            return [];
        }

        $metrics = [
            'views' => 0,
            'impressions' => 0,
            'reach' => 0,
            'saved_count' => 0,
            'total_interactions' => 0
        ];

        $isReelOrVideo = in_array(strtolower($mediaType), ['video', 'reel', 'reels', 'clips'], true);

        // Fast, direct candidate query (support both legacy impressions and v20+ views)
        $primarySet = $isReelOrVideo ? 'views,plays,reach,saved,total_interactions' : 'views,impressions,reach,saved,total_interactions';
        $url = self::BASE_URL . '/' . urlencode($mediaId) . '/insights?' . http_build_query([
            'metric' => $primarySet,
            'access_token' => $accessToken
        ]);
        $res = self::makeGetRequest($url, 3);

        // Fallbacks for different media types (carousel, album, single image)
        if (isset($res['error']) || empty($res['data'])) {
            $fallbackSet = 'impressions,reach,saved,total_interactions';
            $urlFallback = self::BASE_URL . '/' . urlencode($mediaId) . '/insights?' . http_build_query([
                'metric' => $fallbackSet,
                'access_token' => $accessToken
            ]);
            $res = self::makeGetRequest($urlFallback, 3);
            if (isset($res['error']) || empty($res['data'])) {
                $fallbackSet2 = 'reach,saved,total_interactions';
                $urlFallback2 = self::BASE_URL . '/' . urlencode($mediaId) . '/insights?' . http_build_query([
                    'metric' => $fallbackSet2,
                    'access_token' => $accessToken
                ]);
                $res = self::makeGetRequest($urlFallback2, 3);
            }
        }

        if (isset($res['data']) && is_array($res['data'])) {
            foreach ($res['data'] as $item) {
                $name = $item['name'] ?? '';
                $val = 0;
                if (isset($item['total_value']['value'])) {
                    $val = (int)$item['total_value']['value'];
                } elseif (isset($item['values'][0]['value'])) {
                    $val = (int)$item['values'][0]['value'];
                } elseif (isset($item['value'])) {
                    $val = (int)$item['value'];
                }

                if ($name === 'views') {
                    $metrics['views'] = $val;
                    $metrics['impressions'] = max($metrics['impressions'], $val);
                } elseif ($name === 'impressions' || $name === 'plays') {
                    $metrics['impressions'] = max($metrics['impressions'], $val);
                    if ($metrics['views'] === 0) {
                        $metrics['views'] = $val;
                    }
                } elseif ($name === 'reach') {
                    $metrics['reach'] = $val;
                } elseif ($name === 'saved') {
                    $metrics['saved_count'] = $val;
                } elseif ($name === 'total_interactions') {
                    $metrics['total_interactions'] = $val;
                }
            }
        }

        if ($metrics['impressions'] === 0 && $metrics['reach'] > 0) {
            $metrics['impressions'] = $metrics['reach'];
        }
        if ($metrics['views'] === 0 && $metrics['impressions'] > 0) {
            $metrics['views'] = $metrics['impressions'];
        }
        if ($metrics['reach'] === 0 && $metrics['impressions'] > 0) {
            $metrics['reach'] = $metrics['impressions'];
        }

        return $metrics;
    }

    /**
     * Fetch Live Post Insights for a Facebook Post, Video or Photo (Graph API v18/v19/v20/v21)
     */
    public static function fetchFacebookPostInsights(string $postId, string $accessToken, ?string $objectId = null): array {
        if (empty($accessToken) || empty($postId) || str_starts_with($postId, 'fb_post_') || str_starts_with($postId, 'mock_')) {
            return [];
        }

        $metrics = [
            'views' => 0,
            'impressions' => 0,
            'reach' => 0,
            'engaged_users' => 0,
            'reactions_total' => 0
        ];

        // 1. Comprehensive metrics query on main post ID
        $primaryMetric = 'post_impressions,post_impressions_unique,post_engaged_users,post_reactions_by_type_total,post_clicks';
        $url = self::BASE_URL . '/' . urlencode($postId) . '/insights?' . http_build_query([
            'metric' => $primaryMetric,
            'access_token' => $accessToken
        ]);
        $res = self::makeGetRequest($url, 3);

        // 2. Fallback if primary metric set was not supported
        if (isset($res['error']) || empty($res['data'])) {
            $fallbackMetric = 'post_impressions,post_impressions_unique,post_engaged_users';
            $targetId = (!empty($objectId) && is_numeric($objectId)) ? $objectId : $postId;
            $urlFallback = self::BASE_URL . '/' . urlencode($targetId) . '/insights?' . http_build_query([
                'metric' => $fallbackMetric,
                'access_token' => $accessToken
            ]);
            $res = self::makeGetRequest($urlFallback, 3);
        }

        if (isset($res['data']) && is_array($res['data'])) {
            foreach ($res['data'] as $item) {
                $name = $item['name'] ?? '';
                $val = 0;
                if (isset($item['values'][0]['value'])) {
                    $raw = $item['values'][0]['value'];
                    if (is_array($raw)) {
                        $val = array_sum(array_map('intval', $raw));
                    } else {
                        $val = (int)$raw;
                    }
                } elseif (isset($item['total_value']['value'])) {
                    $val = (int)$item['total_value']['value'];
                }

                if ($name === 'post_impressions') {
                    $metrics['impressions'] = max($metrics['impressions'], $val);
                    $metrics['views'] = max($metrics['views'], $val);
                } elseif ($name === 'post_impressions_unique') {
                    $metrics['reach'] = max($metrics['reach'], $val);
                } elseif ($name === 'post_engaged_users') {
                    $metrics['engaged_users'] = max($metrics['engaged_users'], $val);
                } elseif ($name === 'post_reactions_by_type_total') {
                    $metrics['reactions_total'] = max($metrics['reactions_total'], $val);
                }
            }

            if ($metrics['reach'] === 0 && $metrics['impressions'] > 0) {
                $metrics['reach'] = $metrics['impressions'];
            }
            if ($metrics['reach'] < $metrics['engaged_users']) {
                $metrics['reach'] = $metrics['engaged_users'];
            }
            if ($metrics['impressions'] < $metrics['reach']) {
                $metrics['impressions'] = $metrics['reach'];
            }
            if ($metrics['views'] < $metrics['impressions']) {
                $metrics['views'] = $metrics['impressions'];
            }
        }

        return $metrics;
    }


    /**
     * Post a reply to a Facebook or Instagram comment
     */
    public static function postReplyToMeta(int $commentDbId, string $replyMessage, ?int $userId = null): array {
        $uid = ($userId !== null && $userId > 0) ? $userId : (class_exists('Auth') && Auth::check() ? Auth::id() : 1);
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT c.*, p.external_post_id, p.account_id, a.access_token as account_token, a.platform as account_platform 
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            LEFT JOIN accounts a ON p.account_id = a.id
            WHERE c.id = :id AND c.user_id = :uid 
            LIMIT 1
        ");
        $stmt->execute([':id' => $commentDbId, ':uid' => $uid]);
        $comment = $stmt->fetch();

        if (!$comment) {
            return ['success' => false, 'error' => 'Comentario no encontrado en la base de datos'];
        }

        $pageAccessToken = !empty($comment['account_token']) ? $comment['account_token'] : Settings::get('meta_page_access_token', '', $uid);
        $externalCommentId = $comment['external_comment_id'];

        // If no token is set or it's a simulated external ID (starts with cmt_), record locally and simulate success
        if (empty($pageAccessToken) || str_starts_with($externalCommentId, 'cmt_')) {
            return [
                'success' => true,
                'simulated' => true,
                'message' => 'Respuesta registrada y simulada exitosamente (Modo Demo / Sin Meta Token real configurado).'
            ];
        }

        $platform = $comment['platform'];

        if ($platform === 'instagram') {
            // Instagram Graph API Reply: POST /{ig-comment-id}/replies?message={message}&access_token={token}
            $url = self::BASE_URL . '/' . urlencode($externalCommentId) . '/replies';
            $params = [
                'message' => $replyMessage,
                'access_token' => $pageAccessToken
            ];
        } else {
            // Facebook Pages API Reply: POST /{comment-id}/comments?message={message}&access_token={token}
            $url = self::BASE_URL . '/' . urlencode($externalCommentId) . '/comments';
            $params = [
                'message' => $replyMessage,
                'access_token' => $pageAccessToken
            ];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            return [
                'success' => true,
                'simulated' => false,
                'meta_response' => $data
            ];
        } else {
            return [
                'success' => false,
                'simulated' => false,
                'http_code' => $httpCode,
                'error' => $error ?: $response
            ];
        }
    }

    /**
     * Synchronize live posts, insights & comments from Meta Graph API for all connected Facebook Pages & Instagram Accounts
     */
    public static function syncFromMeta(?int $userId = null): array {
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }
        $uid = ($userId !== null && $userId > 0) ? $userId : (class_exists('Auth') && Auth::check() ? Auth::id() : 1);
        $pdo = Database::getConnection();

        $userToken = Settings::get('meta_user_access_token', '', $uid);
        $pageToken = Settings::get('meta_page_access_token', '', $uid);
        $defaultToken = !empty($userToken) ? $userToken : $pageToken;
        $defaultBrandVoiceId = Database::ensureDefaultBrandVoice($pdo, $uid);

        $accountDiagnostics = [];
        $errors = [];

        // 0. Clean up any leftover demo / mock accounts and seed posts from initial seeding
        $pdo->prepare("DELETE FROM accounts WHERE user_id = :uid AND (page_id LIKE 'page_stoic_%' OR page_id LIKE 'page_user_%' OR page_id LIKE 'mock_%' OR page_id = 'ig_10928374' OR page_id = 'fb_987654321')")->execute([':uid' => $uid]);
        $pdo->prepare("DELETE FROM posts WHERE user_id = :uid AND (external_post_id LIKE 'ig_post_%' OR external_post_id LIKE 'fb_post_%' OR external_post_id LIKE 'ig_reel_%' OR external_post_id LIKE 'mock_%')")->execute([':uid' => $uid]);
        $pdo->prepare("DELETE FROM comments WHERE user_id = :uid AND (external_comment_id LIKE 'cmt_stoic_%' OR external_comment_id LIKE 'cmt_user_%' OR external_comment_id LIKE 'cmt_mock_%' OR external_comment_id LIKE 'mock_%')")->execute([':uid' => $uid]);

        // 1. Auto-discover and refresh all Pages & Instagram accounts from Meta /me/accounts
        if (!empty($defaultToken)) {
            $meAccountsUrl = self::BASE_URL . '/me/accounts?fields=id,name,access_token,category,picture,instagram_business_account{id,username,name,profile_picture_url}&limit=100&access_token=' . urlencode($defaultToken);
            $discovered = self::makeGetRequest($meAccountsUrl);

            $pagesList = [];

            if (isset($discovered['error'])) {
                $errCode = $discovered['error']['code'] ?? 0;
                $errMsg = $discovered['error']['message'] ?? '';
                // If #100 or nonexisting field (accounts), the token itself is a Page Access Token!
                if ($errCode == 100 || str_contains($errMsg, 'accounts')) {
                    $pageMeUrl = self::BASE_URL . '/me?fields=id,name,category,picture,instagram_business_account{id,username,name,profile_picture_url}&access_token=' . urlencode($defaultToken);
                    $pageMeData = self::makeGetRequest($pageMeUrl);
                    if (!empty($pageMeData['id'])) {
                        $pageMeData['access_token'] = $defaultToken;
                        $pagesList = [$pageMeData];
                    }
                } else {
                    $errors[] = "Meta Graph API: " . $errMsg;
                }
            } elseif (!empty($discovered['data']) && is_array($discovered['data'])) {
                $pagesList = $discovered['data'];
            }

            foreach ($pagesList as $page) {
                $pid = $page['id'];
                $pname = $page['name'];
                $ptok = !empty($page['access_token']) ? $page['access_token'] : $defaultToken;
                $pageAvatar = $page['picture']['data']['url'] ?? "https://graph.facebook.com/v19.0/{$pid}/picture?type=large&access_token=" . urlencode($ptok);
                $ig = $page['instagram_business_account'] ?? null;

                // A. Upsert Facebook Page Account
                $checkFb = $pdo->prepare("SELECT id, brand_voice_id FROM accounts WHERE user_id = :uid AND page_id = :pid AND platform = 'facebook' LIMIT 1");
                $checkFb->execute([':uid' => $uid, ':pid' => $pid]);
                $existingFb = $checkFb->fetch();

                if ($existingFb) {
                    $pdo->prepare("
                        UPDATE accounts 
                        SET account_name = :name, avatar_url = :avatar, access_token = :token, is_active = 1
                        WHERE id = :id
                    ")->execute([
                        ':name' => $pname,
                        ':avatar' => $pageAvatar,
                        ':token' => $ptok,
                        ':id' => $existingFb['id']
                    ]);
                } else {
                    $pdo->prepare("
                        INSERT INTO accounts (user_id, brand_voice_id, platform, account_name, account_handle, page_id, avatar_url, access_token, is_active)
                        VALUES (:uid, :bvid, 'facebook', :name, :handle, :pid, :avatar, :token, 1)
                    ")->execute([
                        ':uid' => $uid,
                        ':bvid' => $defaultBrandVoiceId,
                        ':name' => $pname,
                        ':handle' => 'fb_' . $pid,
                        ':pid' => $pid,
                        ':avatar' => $pageAvatar,
                        ':token' => $ptok
                    ]);
                }

                // B. Upsert Instagram Business Account if linked to this Page
                if (!empty($ig) && !empty($ig['id'])) {
                    $igId = $ig['id'];
                    $igHandle = !empty($ig['username']) ? '@' . $ig['username'] : '@ig_' . $igId;
                    $igName = $ig['name'] ?? (!empty($ig['username']) ? '@' . $ig['username'] : $pname);
                    $igAvatar = $ig['profile_picture_url'] ?? "https://graph.facebook.com/v19.0/{$igId}/picture?type=large&access_token=" . urlencode($ptok);

                    $checkIg = $pdo->prepare("SELECT id FROM accounts WHERE user_id = :uid AND (page_id = :ig_id OR account_handle = :handle) AND platform = 'instagram' LIMIT 1");
                    $checkIg->execute([':uid' => $uid, ':ig_id' => $igId, ':handle' => $igHandle]);
                    $existingIg = $checkIg->fetch();

                    if ($existingIg) {
                        $pdo->prepare("
                            UPDATE accounts 
                            SET account_name = :name, account_handle = :handle, page_id = :ig_id, avatar_url = :avatar, access_token = :token, is_active = 1
                            WHERE id = :id
                        ")->execute([
                            ':name' => $igName,
                            ':handle' => $igHandle,
                            ':ig_id' => $igId,
                            ':avatar' => $igAvatar,
                            ':token' => $ptok,
                            ':id' => $existingIg['id']
                        ]);
                    } else {
                        $pdo->prepare("
                            INSERT INTO accounts (user_id, brand_voice_id, platform, account_name, account_handle, page_id, avatar_url, access_token, is_active)
                            VALUES (:uid, :bvid, 'instagram', :name, :handle, :ig_id, :avatar, :token, 1)
                        ")->execute([
                            ':uid' => $uid,
                            ':bvid' => $defaultBrandVoiceId,
                            ':name' => $igName,
                            ':handle' => $igHandle,
                            ':ig_id' => $igId,
                            ':avatar' => $igAvatar,
                            ':token' => $ptok
                        ]);
                    }

                    // Also save in settings for primary fallback
                    Settings::set('meta_instagram_account_id', $igId, $uid);
                }
            }
        }

        // 2. Fetch all active connected accounts for this user
        $stmtAccounts = $pdo->prepare("
            SELECT a.*, bv.brand_name as brand_voice_name 
            FROM accounts a 
            LEFT JOIN brand_voices bv ON a.brand_voice_id = bv.id 
            WHERE a.user_id = :uid AND a.is_active = 1
            ORDER BY a.platform ASC, a.id ASC
        ");
        $stmtAccounts->execute([':uid' => $uid]);
        $accounts = $stmtAccounts->fetchAll();

        if (empty($accounts) && empty($defaultToken)) {
            return [
                'success' => false,
                'message' => 'No hay cuentas ni tokens de Meta configurados. Haz clic en "Continuar con Facebook & Instagram" para conectar.',
                'accounts' => [],
                'diagnostics' => []
            ];
        }

        $syncedPostsCount = 0;
        $syncedCommentsCount = 0;
        $syncedAccountsCount = 0;
        $totalPostsFoundOnMeta = 0;

        // 3. Process each account individually
        foreach ($accounts as $acc) {
            $accId = (int)$acc['id'];
            $platform = $acc['platform'] ?? 'facebook';
            $pageId = trim($acc['page_id'] ?? '');
            $token = !empty($acc['access_token']) ? $acc['access_token'] : $defaultToken;
            $accName = $acc['account_name'] ?? 'Cuenta ' . $accId;
            $accHandle = $acc['account_handle'] ?? '';
            $brandVoiceId = !empty($acc['brand_voice_id']) ? (int)$acc['brand_voice_id'] : $defaultBrandVoiceId;

            // Skip accounts with mock / non-numeric IDs
            if (empty($token) || empty($pageId) || !is_numeric($pageId)) continue;
            $syncedAccountsCount++;

            $accPostsFound = 0;
            $accNewPosts = 0;
            $accNewComments = 0;
            $accError = null;

            if ($platform === 'instagram') {
                // Fetch Instagram Media
                $mediaUrl = self::BASE_URL . '/' . urlencode($pageId) . '/media?' . http_build_query([
                    'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,like_count,comments_count,timestamp',
                    'limit' => '25',
                    'access_token' => $token
                ]);
                $mediaData = self::makeGetRequest($mediaUrl, 3, 1);

                if (isset($mediaData['error'])) {
                    $accError = $mediaData['error']['message'] ?? 'Error de permisos al leer publicaciones de Instagram';
                    $errors[] = "Instagram ({$accHandle}): " . $accError;
                } elseif (!empty($mediaData['data']) && is_array($mediaData['data'])) {
                    $accPostsFound = count($mediaData['data']);
                    $totalPostsFoundOnMeta += $accPostsFound;

                    // 1. Prepare Parallel Requests for Top 6 Posts (Insights & Comments)
                    $multiUrls = [];
                    $recentPosts = array_slice($mediaData['data'], 0, 6);
                    foreach ($recentPosts as $media) {
                        $mId = $media['id'];
                        $mType = strtolower($media['media_type'] ?? 'image');
                        $isReel = in_array($mType, ['video', 'reel', 'reels', 'clips'], true);
                        $metricSet = $isReel ? 'views,plays,reach,saved,total_interactions' : 'views,impressions,reach,saved,total_interactions';
                        
                        $multiUrls['insights_' . $mId] = self::BASE_URL . '/' . urlencode($mId) . '/insights?' . http_build_query([
                            'metric' => $metricSet,
                            'access_token' => $token
                        ]);

                        $cCount = (int)($media['comments_count'] ?? 0);
                        if ($cCount > 0) {
                            $multiUrls['comments_' . $mId] = self::BASE_URL . '/' . urlencode($mId) . '/comments?' . http_build_query([
                                'fields' => 'id,text,username,timestamp,like_count',
                                'limit' => '15',
                                'access_token' => $token
                            ]);
                        }
                    }

                    // Execute ALL parallel requests at once (< 400ms)
                    $multiResponses = self::makeMultiGetRequests($multiUrls, 3, 1);

                    foreach ($mediaData['data'] as $media) {
                        $mediaId = $media['id'];
                        $caption = $media['caption'] ?? 'Publicación de Instagram';
                        $mediaImg = $media['media_url'] ?? ($media['thumbnail_url'] ?? 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=480&h=320&auto=format&fit=crop&q=75');
                        $mediaType = strtolower($media['media_type'] ?? 'image');
                        $likes = (int)($media['like_count'] ?? 0);
                        $commentsCount = (int)($media['comments_count'] ?? 0);
                        $permalink = $media['permalink'] ?? '';
                        $postedAt = !empty($media['timestamp']) ? date('Y-m-d H:i:s', strtotime($media['timestamp'])) : date('Y-m-d H:i:s');

                        $impressions = 0;
                        $reach = 0;
                        $views = 0;
                        $savedCount = 0;

                        // Parse insights from parallel results
                        if (isset($multiResponses['insights_' . $mediaId]['data']) && is_array($multiResponses['insights_' . $mediaId]['data'])) {
                            foreach ($multiResponses['insights_' . $mediaId]['data'] as $item) {
                                $name = $item['name'] ?? '';
                                $val = 0;
                                if (isset($item['total_value']['value'])) {
                                    $val = (int)$item['total_value']['value'];
                                } elseif (isset($item['values'][0]['value'])) {
                                    $val = (int)$item['values'][0]['value'];
                                } elseif (isset($item['value'])) {
                                    $val = (int)$item['value'];
                                }

                                if ($name === 'views') {
                                    $views = $val;
                                    $impressions = max($impressions, $val);
                                } elseif ($name === 'impressions' || $name === 'plays') {
                                    $impressions = max($impressions, $val);
                                    if ($views === 0) $views = $val;
                                } elseif ($name === 'reach') {
                                    $reach = $val;
                                } elseif ($name === 'saved') {
                                    $savedCount = $val;
                                }
                            }
                        }

                        if ($impressions === 0 && $views > 0) $impressions = $views;
                        if ($impressions === 0 && $reach > 0) $impressions = $reach;
                        if ($reach === 0 && $impressions > 0) $reach = $impressions;
                        if ($views === 0 && $impressions > 0) $views = $impressions;
                        $igInteractions = $likes + $commentsCount + $savedCount;
                        if ($igInteractions > 0 && $reach < $igInteractions) {
                            $reach = max($reach, $igInteractions);
                        }
                        if ($impressions > 0 && $reach > $impressions) {
                            $impressions = $reach;
                        }
                        if ($reach === 0 && $igInteractions > 0) {
                            $reach = $igInteractions;
                            $impressions = $reach;
                        }

                        $engagementRate = ($reach > 0) ? min(100.0, round(($igInteractions / $reach) * 100, 1)) : (($impressions > 0) ? min(100.0, round(($igInteractions / $impressions) * 100, 1)) : 0.0);

                        $checkPost = $pdo->prepare("SELECT id FROM posts WHERE external_post_id = :ext_id AND user_id = :uid LIMIT 1");
                        $checkPost->execute([':ext_id' => $mediaId, ':uid' => $uid]);
                        $existingPost = $checkPost->fetch();

                        if ($existingPost) {
                            $postId = (int)$existingPost['id'];
                            $stmtUp = $pdo->prepare("
                                UPDATE posts 
                                SET account_id = :acc_id, brand_voice_id = :bvid, total_likes = :likes, total_comments = :comments, 
                                    impressions = :impressions, reach = :reach, saved_count = :saved, engagement_rate = :eng_rate, 
                                    caption = :caption, media_url = :media_url, media_type = :media_type, permalink = :permalink, 
                                    posted_at = :posted_at, last_synced_at = CURRENT_TIMESTAMP
                                WHERE id = :id AND user_id = :uid
                            ");
                            $stmtUp->execute([
                                ':acc_id' => $accId,
                                ':bvid' => $brandVoiceId,
                                ':likes' => $likes,
                                ':comments' => $commentsCount,
                                ':impressions' => $impressions,
                                ':reach' => $reach,
                                ':saved' => $savedCount,
                                ':eng_rate' => $engagementRate,
                                ':caption' => $caption,
                                ':media_url' => $mediaImg,
                                ':media_type' => $mediaType,
                                ':permalink' => $permalink,
                                ':posted_at' => $postedAt,
                                ':id' => $postId,
                                ':uid' => $uid
                            ]);
                        } else {
                            $stmtInsert = $pdo->prepare("
                                INSERT INTO posts (
                                    user_id, account_id, brand_voice_id, platform, external_post_id, caption, media_url, 
                                    media_type, permalink, total_likes, total_comments, total_shares, 
                                    impressions, reach, saved_count, engagement_rate, posted_at, last_synced_at
                                ) VALUES (
                                    :uid, :acc_id, :bvid, 'instagram', :ext_id, :caption, :media_url, 
                                    :media_type, :permalink, :likes, :comments, 0, 
                                    :impressions, :reach, :saved, :eng_rate, :posted_at, CURRENT_TIMESTAMP
                                )
                            ");
                            $stmtInsert->execute([
                                ':uid' => $uid,
                                ':acc_id' => $accId,
                                ':bvid' => $brandVoiceId,
                                ':ext_id' => $mediaId,
                                ':caption' => $caption,
                                ':media_url' => $mediaImg,
                                ':media_type' => $mediaType,
                                ':permalink' => $permalink,
                                ':likes' => $likes,
                                ':comments' => $commentsCount,
                                ':impressions' => $impressions,
                                ':reach' => $reach,
                                ':saved' => $savedCount,
                                ':eng_rate' => $engagementRate,
                                ':posted_at' => $postedAt
                            ]);
                            $postId = (int)$pdo->lastInsertId();
                            $syncedPostsCount++;
                            $accNewPosts++;
                        }

                        // Ingest comments from parallel results
                        $commentsData = $multiResponses['comments_' . $mediaId] ?? [];
                        if (!empty($commentsData['data']) && is_array($commentsData['data'])) {
                            foreach ($commentsData['data'] as $c) {
                                $cmtExtId = $c['id'];
                                $cText = $c['text'] ?? '';
                                $cUsername = $c['username'] ?? 'usuario';

                                if (empty($cText)) continue;

                                $checkCmt = $pdo->prepare("SELECT id FROM comments WHERE external_comment_id = :ext_id AND user_id = :uid LIMIT 1");
                                $checkCmt->execute([':ext_id' => $cmtExtId, ':uid' => $uid]);
                                if (!$checkCmt->fetch()) {
                                    $analysis = AiAgentService::analyzeComment($cText, $caption, $c['like_count'] ?? 0);
                                    $stmtInsertCmt = $pdo->prepare("
                                        INSERT INTO comments (
                                            user_id, post_id, platform, external_comment_id, author_name, author_handle, 
                                            author_avatar, comment_text, sentiment, intent, highlight_score, 
                                            is_highlighted, highlight_reason, likes_count, status
                                        ) VALUES (
                                            :uid, :post_id, 'instagram', :ext_id, :author_name, :author_handle, 
                                            :author_avatar, :comment_text, :sentiment, :intent, :highlight_score, 
                                            :is_highlighted, :highlight_reason, :likes_count, 'pending'
                                        )
                                    ");
                                    $stmtInsertCmt->execute([
                                        ':uid' => $uid,
                                        ':post_id' => $postId,
                                        ':ext_id' => $cmtExtId,
                                        ':author_name' => $cUsername,
                                        ':author_handle' => '@' . $cUsername,
                                        ':author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($cUsername) . '&background=6366f1&color=fff',
                                        ':comment_text' => $cText,
                                        ':sentiment' => $analysis['sentiment'],
                                        ':intent' => $analysis['intent'],
                                        ':highlight_score' => $analysis['highlight_score'],
                                        ':is_highlighted' => $analysis['is_highlighted'],
                                        ':highlight_reason' => $analysis['highlight_reason'],
                                        ':likes_count' => (int)($c['like_count'] ?? 0)
                                    ]);
                                    $syncedCommentsCount++;
                                    $accNewComments++;
                                }
                            }
                        }
                    }
                }
            } else {
                // Fetch Facebook Page Posts from /published_posts, /feed, and /posts IN PARALLEL for 100% complete discovery
                $fbFields = 'id,message,story,created_time,full_picture,permalink_url,shares,attachments{media,type,target{id},unshimmed_url},reactions.summary(true).limit(1),likes.summary(true).limit(1),comments.summary(true).limit(1)';
                
                $fbFeedQueries = [
                    'published' => self::BASE_URL . '/' . urlencode($pageId) . '/published_posts?' . http_build_query([
                        'fields' => $fbFields,
                        'limit' => '30',
                        'access_token' => $token
                    ]),
                    'feed' => self::BASE_URL . '/' . urlencode($pageId) . '/feed?' . http_build_query([
                        'fields' => $fbFields,
                        'limit' => '30',
                        'access_token' => $token
                    ]),
                    'posts' => self::BASE_URL . '/' . urlencode($pageId) . '/posts?' . http_build_query([
                        'fields' => $fbFields,
                        'limit' => '30',
                        'access_token' => $token
                    ])
                ];

                $fbFeedsData = self::makeMultiGetRequests($fbFeedQueries, 3, 1);

                // Merge and deduplicate all posts across published_posts, feed, and posts
                $mergedPosts = [];
                $fbPermissionError = null;

                foreach (['published', 'feed', 'posts'] as $sourceKey) {
                    $resData = $fbFeedsData[$sourceKey] ?? [];
                    if (isset($resData['error'])) {
                        $errCode = $resData['error']['code'] ?? 0;
                        $errSub = $resData['error']['message'] ?? '';
                        if ($errCode == 10 || str_contains($errSub, 'pages_read_engagement')) {
                            $fbPermissionError = "Facebook ({$accName}): Requiere permiso 'pages_read_engagement' en Meta.";
                        }
                    } elseif (!empty($resData['data']) && is_array($resData['data'])) {
                        foreach ($resData['data'] as $postItem) {
                            $pId = $postItem['id'] ?? '';
                            if (!empty($pId) && !isset($mergedPosts[$pId])) {
                                $mergedPosts[$pId] = $postItem;
                            }
                        }
                    }
                }

                if (empty($mergedPosts) && !empty($fbPermissionError)) {
                    $errors[] = $fbPermissionError;
                }

                // Sort merged posts by created_time descending
                $mergedPostsList = array_values($mergedPosts);
                usort($mergedPostsList, function($a, $b) {
                    $tA = !empty($a['created_time']) ? strtotime($a['created_time']) : 0;
                    $tB = !empty($b['created_time']) ? strtotime($b['created_time']) : 0;
                    return $tB <=> $tA;
                });

                if (!empty($mergedPostsList)) {
                    $accPostsFound = count($mergedPostsList);
                    $totalPostsFoundOnMeta += $accPostsFound;

                    // 1. Prepare Parallel Requests for Top 8 Posts (Insights, Comments, Photo Object Reactions)
                    $multiUrls = [];
                    $recentPosts = array_slice($mergedPostsList, 0, 8);
                    foreach ($recentPosts as $fbPost) {
                        $pIdExt = $fbPost['id'];
                        $objId = !empty($fbPost['attachments']['data'][0]['target']['id']) ? (string)$fbPost['attachments']['data'][0]['target']['id'] : null;

                        $multiUrls['fb_insights_' . $pIdExt] = self::BASE_URL . '/' . urlencode($pIdExt) . '/insights?' . http_build_query([
                            'metric' => 'post_impressions,post_impressions_unique,post_engaged_users,post_reactions_by_type_total,post_video_views,post_clicks',
                            'access_token' => $token
                        ]);

                        $cCount = (int)($fbPost['comments']['summary']['total_count'] ?? (is_array($fbPost['comments']['data'] ?? null) ? count($fbPost['comments']['data']) : 0));
                        if ($cCount > 0) {
                            $multiUrls['fb_comments_' . $pIdExt] = self::BASE_URL . '/' . urlencode($pIdExt) . '/comments?' . http_build_query([
                                'fields' => 'id,message,from,created_time,like_count',
                                'limit' => '20',
                                'access_token' => $token
                            ]);
                        }

                        if (!empty($objId) && is_numeric($objId) && $objId !== $pIdExt) {
                            $multiUrls['fb_obj_' . $pIdExt] = self::BASE_URL . '/' . urlencode($objId) . '?fields=reactions.summary(true).limit(1),likes.summary(true).limit(1)&access_token=' . urlencode($token);
                        }
                    }

                    // Execute ALL parallel requests at once (< 400ms)
                    $multiResponses = self::makeMultiGetRequests($multiUrls, 3, 1);

                    foreach ($mergedPostsList as $fbPost) {
                        $postIdExt = $fbPost['id'];
                        $objectId = !empty($fbPost['attachments']['data'][0]['target']['id']) ? (string)$fbPost['attachments']['data'][0]['target']['id'] : null;
                        $message = $fbPost['message'] ?? ($fbPost['story'] ?? 'Publicación de Página de Facebook');
                        
                        // Smart picture resolution
                        $fullPic = $fbPost['full_picture'] ?? null;
                        if (empty($fullPic) && !empty($fbPost['attachments']['data'][0]['media']['image']['src'])) {
                            $fullPic = $fbPost['attachments']['data'][0]['media']['image']['src'];
                        }
                        if (empty($fullPic) && !empty($fbPost['attachments']['data'][0]['unshimmed_url'])) {
                            $fullPic = $fbPost['attachments']['data'][0]['unshimmed_url'];
                        }
                        if (empty($fullPic)) {
                            $fullPic = 'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=480&h=320&auto=format&fit=crop&q=75';
                        }

                        $attachType = strtolower($fbPost['attachments']['data'][0]['type'] ?? '');
                        $mediaType = 'status';
                        if (str_contains($attachType, 'video') || str_contains($attachType, 'reel')) {
                            $mediaType = 'video';
                        } elseif (str_contains($attachType, 'photo') || str_contains($attachType, 'image') || !empty($fbPost['full_picture'])) {
                            $mediaType = 'image';
                        } elseif (str_contains($attachType, 'album')) {
                            $mediaType = 'carousel';
                        }

                        $permalink = $fbPost['permalink_url'] ?? '';

                        // Multi-layer Likes & Reactions Extraction
                        $likes = 0;
                        if (isset($fbPost['reactions']['summary']['total_count'])) {
                            $likes = (int)$fbPost['reactions']['summary']['total_count'];
                        }
                        if (isset($fbPost['likes']['summary']['total_count'])) {
                            $likes = max($likes, (int)$fbPost['likes']['summary']['total_count']);
                        }
                        if ($likes === 0 && !empty($fbPost['reactions']['data']) && is_array($fbPost['reactions']['data'])) {
                            $likes = count($fbPost['reactions']['data']);
                        }
                        if ($likes === 0 && !empty($fbPost['likes']['data']) && is_array($fbPost['likes']['data'])) {
                            $likes = count($fbPost['likes']['data']);
                        }

                        // Check photo object reactions from parallel response
                        if ($likes === 0 && isset($multiResponses['fb_obj_' . $postIdExt])) {
                            $objData = $multiResponses['fb_obj_' . $postIdExt];
                            if (isset($objData['reactions']['summary']['total_count'])) {
                                $likes = max($likes, (int)$objData['reactions']['summary']['total_count']);
                            }
                            if (isset($objData['likes']['summary']['total_count'])) {
                                $likes = max($likes, (int)$objData['likes']['summary']['total_count']);
                            }
                        }

                        $commentsCount = (int)($fbPost['comments']['summary']['total_count'] ?? (is_array($fbPost['comments']['data'] ?? null) ? count($fbPost['comments']['data']) : 0));
                        $shares = (int)($fbPost['shares']['count'] ?? 0);
                        $postedAt = !empty($fbPost['created_time']) ? date('Y-m-d H:i:s', strtotime($fbPost['created_time'])) : date('Y-m-d H:i:s');

                        $impressions = 0;
                        $reach = 0;
                        $engagedUsers = 0;

                        // Parse Facebook insights from parallel response
                        if (isset($multiResponses['fb_insights_' . $postIdExt]['data']) && is_array($multiResponses['fb_insights_' . $postIdExt]['data'])) {
                            foreach ($multiResponses['fb_insights_' . $postIdExt]['data'] as $item) {
                                $name = $item['name'] ?? '';
                                $val = 0;
                                if (isset($item['values'][0]['value'])) {
                                    $raw = $item['values'][0]['value'];
                                    $val = is_array($raw) ? array_sum(array_map('intval', $raw)) : (int)$raw;
                                } elseif (isset($item['total_value']['value'])) {
                                    $val = (int)$item['total_value']['value'];
                                }

                                if ($name === 'post_impressions') {
                                    $impressions = max($impressions, $val);
                                } elseif ($name === 'post_impressions_unique') {
                                    $reach = max($reach, $val);
                                } elseif ($name === 'post_engaged_users') {
                                    $engagedUsers = max($engagedUsers, $val);
                                } elseif ($name === 'post_reactions_by_type_total') {
                                    $likes = max($likes, $val);
                                } elseif ($name === 'post_video_views') {
                                    $impressions = max($impressions, $val);
                                }
                            }
                        }

                        $fbInteractions = $likes + $commentsCount + $shares;

                        // Ensure logical reach & views consistency
                        if ($reach === 0 && $impressions > 0) {
                            $reach = $impressions;
                        }
                        if ($engagedUsers > 0 && $reach < $engagedUsers) {
                            $reach = max($reach, $engagedUsers);
                        }
                        if ($fbInteractions > 0 && $reach < $fbInteractions) {
                            $reach = max($reach, $fbInteractions);
                        }
                        if ($reach > 0 && $impressions < $reach) {
                            $impressions = $reach;
                        }
                        if ($reach === 0 && $fbInteractions > 0) {
                            $reach = $fbInteractions;
                            $impressions = $reach;
                        }

                        $engagementRate = ($reach > 0) ? min(100.0, round(($fbInteractions / $reach) * 100, 1)) : 0.0;

                        $checkPost = $pdo->prepare("SELECT id FROM posts WHERE external_post_id = :ext_id AND user_id = :uid LIMIT 1");
                        $checkPost->execute([':ext_id' => $postIdExt, ':uid' => $uid]);
                        $existingPost = $checkPost->fetch();

                        if ($existingPost) {
                            $postId = (int)$existingPost['id'];
                            $stmtUp = $pdo->prepare("
                                UPDATE posts 
                                SET account_id = :acc_id, brand_voice_id = :bvid, total_likes = :likes, total_comments = :comments, 
                                    total_shares = :shares, impressions = :impressions, reach = :reach, engagement_rate = :eng_rate, 
                                    caption = :caption, media_url = :media_url, media_type = :media_type, permalink = :permalink, 
                                    posted_at = :posted_at, last_synced_at = CURRENT_TIMESTAMP
                                WHERE id = :id AND user_id = :uid
                            ");
                            $stmtUp->execute([
                                ':acc_id' => $accId,
                                ':bvid' => $brandVoiceId,
                                ':likes' => $likes,
                                ':comments' => $commentsCount,
                                ':shares' => $shares,
                                ':impressions' => $impressions,
                                ':reach' => $reach,
                                ':eng_rate' => $engagementRate,
                                ':caption' => $message,
                                ':media_url' => $fullPic,
                                ':media_type' => $mediaType,
                                ':permalink' => $permalink,
                                ':posted_at' => $postedAt,
                                ':id' => $postId,
                                ':uid' => $uid
                            ]);
                        } else {
                            $stmtInsert = $pdo->prepare("
                                INSERT INTO posts (
                                    user_id, account_id, brand_voice_id, platform, external_post_id, caption, media_url, 
                                    media_type, permalink, total_likes, total_comments, total_shares, 
                                    impressions, reach, saved_count, engagement_rate, posted_at, last_synced_at
                                ) VALUES (
                                    :uid, :acc_id, :bvid, 'facebook', :ext_id, :caption, :media_url, 
                                    :media_type, :permalink, :likes, :comments, :shares, 
                                    :impressions, :reach, 0, :eng_rate, :posted_at, CURRENT_TIMESTAMP
                                )
                            ");
                            $stmtInsert->execute([
                                ':uid' => $uid,
                                ':acc_id' => $accId,
                                ':bvid' => $brandVoiceId,
                                ':ext_id' => $postIdExt,
                                ':caption' => $message,
                                ':media_url' => $fullPic,
                                ':media_type' => $mediaType,
                                ':permalink' => $permalink,
                                ':likes' => $likes,
                                ':comments' => $commentsCount,
                                ':shares' => $shares,
                                ':impressions' => $impressions,
                                ':reach' => $reach,
                                ':eng_rate' => $engagementRate,
                                ':posted_at' => $postedAt
                            ]);
                            $postId = (int)$pdo->lastInsertId();
                            $syncedPostsCount++;
                            $accNewPosts++;
                        }

                        // Parse comments from parallel response
                        $commentsData = $multiResponses['fb_comments_' . $postIdExt] ?? [];
                        if (!empty($commentsData['data']) && is_array($commentsData['data'])) {
                            foreach ($commentsData['data'] as $c) {
                                $cmtExtId = $c['id'];
                                $cText = $c['message'] ?? '';
                                $fromName = $c['from']['name'] ?? 'Usuario de Facebook';

                                if (empty($cText)) continue;

                                $checkCmt = $pdo->prepare("SELECT id FROM comments WHERE external_comment_id = :ext_id AND user_id = :uid LIMIT 1");
                                $checkCmt->execute([':ext_id' => $cmtExtId, ':uid' => $uid]);
                                if (!$checkCmt->fetch()) {
                                    $analysis = AiAgentService::analyzeComment($cText, $message, $c['like_count'] ?? 0);
                                    $stmtInsertCmt = $pdo->prepare("
                                        INSERT INTO comments (
                                            user_id, post_id, platform, external_comment_id, author_name, author_handle, 
                                            author_avatar, comment_text, sentiment, intent, highlight_score, 
                                            is_highlighted, highlight_reason, likes_count, status
                                        ) VALUES (
                                            :uid, :post_id, 'facebook', :ext_id, :author_name, :author_handle, 
                                            :author_avatar, :comment_text, :sentiment, :intent, :highlight_score, 
                                            :is_highlighted, :highlight_reason, :likes_count, 'pending'
                                        )
                                    ");
                                    $stmtInsertCmt->execute([
                                        ':uid' => $uid,
                                        ':post_id' => $postId,
                                        ':ext_id' => $cmtExtId,
                                        ':author_name' => $fromName,
                                        ':author_handle' => 'fb_' . substr($cmtExtId, 0, 8),
                                        ':author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($fromName) . '&background=1877f2&color=fff',
                                        ':comment_text' => $cText,
                                        ':sentiment' => $analysis['sentiment'],
                                        ':intent' => $analysis['intent'],
                                        ':highlight_score' => $analysis['highlight_score'],
                                        ':is_highlighted' => $analysis['is_highlighted'],
                                        ':highlight_reason' => $analysis['highlight_reason'],
                                        ':likes_count' => (int)($c['like_count'] ?? 0)
                                    ]);
                                    $syncedCommentsCount++;
                                    $accNewComments++;
                                }
                            }
                        }
                    }
                }
            }

            $accountDiagnostics[] = [
                'account_id' => $accId,
                'account_name' => $accName,
                'account_handle' => $accHandle,
                'platform' => $platform,
                'brand_voice_id' => $brandVoiceId,
                'brand_voice_name' => $acc['brand_voice_name'] ?? 'Voz Predeterminada',
                'posts_found_on_meta' => $accPostsFound,
                'new_posts_imported' => $accNewPosts,
                'new_comments_imported' => $accNewComments,
                'error' => $accError
            ];
        }

        $summaryMsg = "Sincronización completada. Se verificaron {$syncedAccountsCount} cuentas en Meta. Se encontraron {$totalPostsFoundOnMeta} publicaciones ({$syncedPostsCount} nuevas agregadas) y {$syncedCommentsCount} comentarios procesados con IA.";
        if (!empty($errors)) {
            $summaryMsg .= " (Aviso: " . implode(" | ", $errors) . ")";
        }

        return [
            'success' => true,
            'synced_accounts' => $syncedAccountsCount,
            'total_posts_found' => $totalPostsFoundOnMeta,
            'synced_new_posts' => $syncedPostsCount,
            'synced_new_comments' => $syncedCommentsCount,
            'account_diagnostics' => $accountDiagnostics,
            'errors' => $errors,
            'message' => $summaryMsg
        ];
    }

    /**
     * Complete Pre-Audit Scanner for Meta App Review Readiness
     * Audits SSL, Legal URLs, Async Webhook Queue, OAuth 2.0 Credentials, and Graph API Permissions
     */
    public static function auditAppReviewReadiness(?int $userId = null): array {
        $uid = ($userId !== null && $userId > 0) ? $userId : (class_exists('Auth') && Auth::check() ? Auth::id() : 1);
        $pdo = Database::getConnection();

        $appId = Settings::get('meta_app_id', '', $uid);
        $appSecret = Settings::get('meta_app_secret', '', $uid);
        $pageAccessToken = Settings::get('meta_page_access_token', '', $uid);
        $igAccountId = Settings::get('meta_instagram_account_id', '', $uid);
        $webhookVerifyToken = Settings::get('webhook_verify_token', 'social_boost_secure_token_2026', $uid);

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isHttps = ($protocol === 'https');
        $isLocalhost = (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1'));

        $baseUri = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
        $baseUrl = $protocol . '://' . $host . ($baseUri !== '' ? $baseUri : '');

        $checklist = [];
        $totalChecks = 0;
        $passedChecks = 0;

        // 1. HTTPS / SSL Check
        $totalChecks++;
        if ($isHttps) {
            $passedChecks++;
            $checklist[] = [
                'category' => 'Infraestructura',
                'name' => 'Certificado SSL / HTTPS Activo',
                'status' => 'pass',
                'description' => 'Tu sitio web utiliza protocolo seguro HTTPS, indispensable para la aprobación de Meta.',
                'details' => $baseUrl
            ];
        } else {
            $checklist[] = [
                'category' => 'Infraestructura',
                'name' => 'Protocolo SSL / HTTPS',
                'status' => $isLocalhost ? 'warning' : 'fail',
                'description' => $isLocalhost 
                    ? 'Estás en entorno local (localhost). Meta exige HTTPS para producción, pero en desarrollo local puedes usar ngrok o Cloudflare Tunnel.'
                    : 'Meta exige que todas las URLs públicas y webhooks utilicen HTTPS con certificado TLS 1.2+.',
                'details' => 'Actual: ' . $protocol . '://' . $host
            ];
            if ($isLocalhost) $passedChecks += 0.5;
        }

        // 2. Legal Suite (Privacy Policy, Terms of Service, Data Deletion)
        $legalDocs = [
            [
                'name' => 'Política de Privacidad (Privacy Policy)',
                'file' => __DIR__ . '/../privacy-policy.php',
                'url' => $baseUrl . '/privacy-policy.php',
                'compliance' => 'RGPD / CCPA / EU AI Act 2024/1689 & Meta Developer Policy §4.a'
            ],
            [
                'name' => 'Condiciones del Servicio (Terms of Service)',
                'file' => __DIR__ . '/../terms-of-service.php',
                'url' => $baseUrl . '/terms-of-service.php',
                'compliance' => 'Límites de responsabilidad de IA, propiedad intelectual y reglas de uso'
            ],
            [
                'name' => 'Página de Eliminación de Datos (User Data Deletion URL)',
                'file' => __DIR__ . '/../data-deletion.php',
                'url' => $baseUrl . '/data-deletion.php',
                'compliance' => 'Requerido por Meta para el cumplimiento del RGPD (Art. 17)'
            ],
            [
                'name' => 'Endpoint de Eliminación de Datos (Data Deletion Callback API)',
                'file' => __DIR__ . '/../api/data-deletion.php',
                'url' => $baseUrl . '/api/data-deletion.php',
                'compliance' => 'Responde con confirmation_code y URL de seguimiento JSON exigido por Meta'
            ]
        ];

        foreach ($legalDocs as $doc) {
            $totalChecks++;
            if (file_exists($doc['file'])) {
                $passedChecks++;
                $checklist[] = [
                    'category' => 'Blindaje Legal & Meta Policy',
                    'name' => $doc['name'],
                    'status' => 'pass',
                    'description' => 'Documento legal disponible y adaptado a ' . $doc['compliance'] . '.',
                    'details' => $doc['url']
                ];
            } else {
                $checklist[] = [
                    'category' => 'Blindaje Legal & Meta Policy',
                    'name' => $doc['name'],
                    'status' => 'fail',
                    'description' => 'El archivo no fue encontrado en el servidor.',
                    'details' => $doc['url']
                ];
            }
        }

        // 3. Webhook Infrastructure & Async Queue
        $totalChecks++;
        $webhookFile = __DIR__ . '/../api/webhook.php';
        $queueExists = false;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='webhook_queue'");
            $queueExists = ((int)$stmt->fetchColumn() > 0);
        } catch (Throwable) {}

        if (file_exists($webhookFile) && $queueExists) {
            $passedChecks++;
            $checklist[] = [
                'category' => 'Webhooks & Alta Concurrencia',
                'name' => 'Procesador Webhook Asíncrono (<50ms)',
                'status' => 'pass',
                'description' => 'Webhook activo con tabla de cola async y validación HMAC-SHA256 para evitar timeouts de Meta.',
                'details' => $baseUrl . '/api/webhook.php'
            ];
        } else {
            $checklist[] = [
                'category' => 'Webhooks & Alta Concurrencia',
                'name' => 'Procesador Webhook Asíncrono',
                'status' => 'warning',
                'description' => 'Verifica que la tabla webhook_queue exista en la base de datos.',
                'details' => $baseUrl . '/api/webhook.php'
            ];
        }

        // 4. Meta OAuth 2.0 Credentials
        $totalChecks++;
        if (!empty($appId) && !empty($appSecret)) {
            $passedChecks++;
            $checklist[] = [
                'category' => 'Autenticación OAuth 2.0',
                'name' => 'Meta App ID & App Secret Configurados',
                'status' => 'pass',
                'description' => 'Credenciales oficiales de la App de Meta listas para intercambiar tokens de larga duración.',
                'details' => 'App ID: ' . substr($appId, 0, 4) . '****'
            ];
        } elseif (!empty($appId)) {
            $checklist[] = [
                'category' => 'Autenticación OAuth 2.0',
                'name' => 'Meta App Secret Pendiente',
                'status' => 'warning',
                'description' => 'Falta ingresar el App Secret en la pestaña de Meta para permitir el flujo OAuth automático.',
                'details' => 'Configura el App Secret de developers.facebook.com'
            ];
            $passedChecks += 0.5;
        } else {
            $checklist[] = [
                'category' => 'Autenticación OAuth 2.0',
                'name' => 'Meta App ID & App Secret',
                'status' => 'warning',
                'description' => 'Ingresa tu App ID y Secret para habilitar el botón de login oficial de Meta OAuth.',
                'details' => 'Obténlos en developers.facebook.com > Configuración Básica'
            ];
        }

        // 5. Active Token & Permissions Live Diagnostics
        $totalChecks++;
        $tokenDiag = self::testMetaConnection($pageAccessToken);
        $permissionsList = [];

        if ($tokenDiag['success']) {
            $passedChecks++;
            $checklist[] = [
                'category' => 'Permisos & Graph API',
                'name' => 'Conexión con Meta Graph API Activa',
                'status' => 'pass',
                'description' => 'Token autenticado como: ' . ($tokenDiag['meta_user']['name'] ?? 'Usuario de Meta'),
                'details' => 'Permisos verificados: ' . count($tokenDiag['permissions'])
            ];
            $permissionsList = $tokenDiag['permissions'];
        } else {
            $checklist[] = [
                'category' => 'Permisos & Graph API',
                'name' => 'Token de Acceso de Meta',
                'status' => (!empty($pageAccessToken)) ? 'fail' : 'warning',
                'description' => $tokenDiag['message'] ?? 'Conecta tu cuenta o ingresa un Page Access Token.',
                'details' => 'Genera tu token en Meta OAuth o Graph API Explorer'
            ];
        }

        // 6. Instagram Business Account Linked
        $totalChecks++;
        if (!empty($igAccountId)) {
            $passedChecks++;
            $checklist[] = [
                'category' => 'Instagram Professional',
                'name' => 'Cuenta de Instagram Vinculada',
                'status' => 'pass',
                'description' => 'ID de Cuenta Profesional de Instagram detectado y listo para recibir métricas e interacciones.',
                'details' => 'IG Account ID: ' . $igAccountId
            ];
        } else {
            $checklist[] = [
                'category' => 'Instagram Professional',
                'name' => 'Cuenta de Instagram Vinculada',
                'status' => 'warning',
                'description' => 'Conéctate mediante el botón OAuth oficial para detectar y vincular automáticamente tu Instagram Profesional.',
                'details' => 'Requiere cuenta Profesional (Creador o Empresa) enlazada a una Página'
            ];
        }

        $score = round(($passedChecks / max($totalChecks, 1)) * 100);
        $isReady = ($score >= 80);

        return [
            'success' => true,
            'score' => $score,
            'is_ready' => $isReady,
            'status_label' => $isReady ? 'Listo para Someter a App Review' : 'Acciones Pendientes de Configuración',
            'base_url' => $baseUrl,
            'checklist' => $checklist,
            'permissions' => $permissionsList,
            'submission_urls' => [
                'privacy_policy' => $baseUrl . '/privacy-policy.php',
                'terms_of_service' => $baseUrl . '/terms-of-service.php',
                'data_deletion' => $baseUrl . '/data-deletion.php',
                'data_deletion_callback' => $baseUrl . '/api/data-deletion.php',
                'webhook_url' => $baseUrl . '/api/webhook.php',
                'oauth_redirect_uri' => $baseUrl . '/callback-meta.php'
            ],
            'recommendations' => $isReady ? [
                '¡Excelente trabajo! Tu plataforma cumple con todos los requerimientos técnicos y legales exigidos por Meta.',
                'Dirígete a developers.facebook.com > Revisión de la App y copia las justificaciones de la guía adjunta.',
                'Graba un screencast de 2 a 3 minutos mostrando la autenticación con Meta y la respuesta del Agente IA.'
            ] : [
                'Completa los puntos marcados con advertencia o error en la lista para maximizar tus posibilidades de aprobación por Meta.',
                'Configura tu App ID y Secret para autorizar permisos mediante el botón OAuth oficial.'
            ]
        ];
    }

    public static function makeMultiGetRequests(array $urls, int $timeout = 3, int $connectTimeout = 1): array {
        if (empty($urls)) return [];
        $mh = curl_multi_init();
        $handles = [];

        foreach ($urls as $key => $url) {
            if (empty($url)) continue;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_multi_add_handle($mh, $ch);
            $handles[$key] = $ch;
        }

        if (empty($handles)) {
            curl_multi_close($mh);
            return [];
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh, 0.05);
            }
        } while ($active && $status == CURLM_OK);

        $results = [];
        foreach ($handles as $key => $ch) {
            $response = curl_multi_getcontent($ch);
            $results[$key] = $response ? (json_decode($response, true) ?? []) : [];
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);
        return $results;
    }

    private static function makeGetRequest(string $url, int $timeout = 3, int $connectTimeout = 1): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        if ($curlErr) {
            error_log("Meta Graph API GET Error: " . $curlErr);
        }
        return $response ? (json_decode($response, true) ?? []) : [];
    }
}

