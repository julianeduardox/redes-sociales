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
     * Fetch Live Media Insights for an Instagram Post
     */
    public static function fetchMediaInsights(string $mediaId, string $accessToken): array {
        if (empty($accessToken) || empty($mediaId) || str_starts_with($mediaId, 'ig_post_')) {
            return [];
        }

        // Available metrics for Instagram posts: impressions, reach, saved, total_interactions
        $url = self::BASE_URL . '/' . urlencode($mediaId) . '/insights?metric=impressions,reach,saved,total_interactions&access_token=' . urlencode($accessToken);
        $res = self::makeGetRequest($url);

        $metrics = [
            'impressions' => 0,
            'reach' => 0,
            'saved_count' => 0,
            'total_interactions' => 0
        ];

        if (isset($res['data']) && is_array($res['data'])) {
            foreach ($res['data'] as $item) {
                $name = $item['name'] ?? '';
                $val = $item['values'][0]['value'] ?? 0;
                if (isset($metrics[$name])) {
                    $metrics[$name] = (int)$val;
                } elseif ($name === 'saved') {
                    $metrics['saved_count'] = (int)$val;
                }
            }
        }

        return $metrics;
    }

    /**
     * Post a reply to a Facebook or Instagram comment
     */
    public static function postReplyToMeta(int $commentDbId, string $replyMessage): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT c.*, p.external_post_id FROM comments c JOIN posts p ON c.post_id = p.id WHERE c.id = :id LIMIT 1");
        $stmt->execute([':id' => $commentDbId]);
        $comment = $stmt->fetch();

        if (!$comment) {
            return ['success' => false, 'error' => 'Comentario no encontrado en la base de datos'];
        }

        $pageAccessToken = Settings::get('meta_page_access_token', '');
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
     * Synchronize live posts, insights & comments from Meta Graph API
     */
    public static function syncFromMeta(?int $userId = null): array {
        $uid = ($userId !== null && $userId > 0) ? $userId : (class_exists('Auth') && Auth::check() ? Auth::id() : 1);
        $accessToken = Settings::get('meta_page_access_token', '', $uid);
        $igAccountId = Settings::get('meta_instagram_account_id', '', $uid);

        if (empty($accessToken)) {
            return [
                'success' => false,
                'message' => 'Configura el Page Access Token en la sección de Meta API para sincronizar en vivo.'
            ];
        }

        $pdo = Database::getConnection();
        $syncedPostsCount = 0;
        $syncedCommentsCount = 0;

        // 1. Sync Instagram if Account ID exists
        if (!empty($igAccountId)) {
            $mediaUrl = self::BASE_URL . '/' . urlencode($igAccountId) . '/media?fields=id,caption,media_type,media_url,permalink,like_count,comments_count,timestamp&limit=25&access_token=' . urlencode($accessToken);
            $mediaData = self::makeGetRequest($mediaUrl);

            if (isset($mediaData['data']) && is_array($mediaData['data'])) {
                foreach ($mediaData['data'] as $media) {
                    $mediaId = $media['id'];
                    $caption = $media['caption'] ?? 'Publicación de Instagram';
                    $mediaImg = $media['media_url'] ?? 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=800&auto=format&fit=crop&q=80';
                    $mediaType = strtolower($media['media_type'] ?? 'image');
                    $likes = (int)($media['like_count'] ?? 0);
                    $comments = (int)($media['comments_count'] ?? 0);
                    $permalink = $media['permalink'] ?? '';

                    // Fetch Meta Insights for this media
                    $insights = self::fetchMediaInsights($mediaId, $accessToken);
                    $impressions = $insights['impressions'] ?? ($likes * 8);
                    $reach = $insights['reach'] ?? ($likes * 6);
                    $savedCount = $insights['saved_count'] ?? (int)($likes * 0.15);
                    $engagementRate = ($reach > 0) ? round((($likes + $comments + $savedCount) / $reach) * 100, 1) : 0.0;

                    // Check if post exists
                    $checkPost = $pdo->prepare("SELECT id FROM posts WHERE external_post_id = :ext_id AND user_id = :uid LIMIT 1");
                    $checkPost->execute([':ext_id' => $mediaId, ':uid' => $uid]);
                    $existingPost = $checkPost->fetch();

                    if ($existingPost) {
                        $postId = $existingPost['id'];
                        // Update metrics
                        $stmtUp = $pdo->prepare("
                            UPDATE posts 
                            SET total_likes = :likes, total_comments = :comments, impressions = :impressions, 
                                reach = :reach, saved_count = :saved, engagement_rate = :eng_rate, 
                                last_synced_at = datetime('now')
                            WHERE id = :id AND user_id = :uid
                        ");
                        $stmtUp->execute([
                            ':likes' => $likes,
                            ':comments' => $comments,
                            ':impressions' => $impressions,
                            ':reach' => $reach,
                            ':saved' => $savedCount,
                            ':eng_rate' => $engagementRate,
                            ':id' => $postId,
                            ':uid' => $uid
                        ]);
                    } else {
                        $stmtInsert = $pdo->prepare("
                            INSERT INTO posts (
                                user_id, account_id, platform, external_post_id, caption, media_url, 
                                media_type, permalink, total_likes, total_comments, total_shares, 
                                impressions, reach, saved_count, engagement_rate, last_synced_at
                            ) VALUES (
                                :uid, 1, 'instagram', :ext_id, :caption, :media_url, 
                                :media_type, :permalink, :likes, :comments, 0, 
                                :impressions, :reach, :saved, :eng_rate, datetime('now')
                            )
                        ");
                        $stmtInsert->execute([
                            ':uid' => $uid,
                            ':ext_id' => $mediaId,
                            ':caption' => $caption,
                            ':media_url' => $mediaImg,
                            ':media_type' => $mediaType,
                            ':permalink' => $permalink,
                            ':likes' => $likes,
                            ':comments' => $comments,
                            ':impressions' => $impressions,
                            ':reach' => $reach,
                            ':saved' => $savedCount,
                            ':eng_rate' => $engagementRate
                        ]);
                        $postId = $pdo->lastInsertId();
                        $syncedPostsCount++;
                    }

                    // Fetch comments for this media
                    $commentsUrl = self::BASE_URL . '/' . urlencode($mediaId) . '/comments?fields=id,text,username,timestamp,like_count&limit=50&access_token=' . urlencode($accessToken);
                    $commentsData = self::makeGetRequest($commentsUrl);

                    if (isset($commentsData['data']) && is_array($commentsData['data'])) {
                        foreach ($commentsData['data'] as $c) {
                            $checkCmt = $pdo->prepare("SELECT id FROM comments WHERE external_comment_id = :ext_id AND user_id = :uid LIMIT 1");
                            $checkCmt->execute([':ext_id' => $c['id'], ':uid' => $uid]);
                            if (!$checkCmt->fetch()) {
                                $analysis = AiAgentService::analyzeComment($c['text'], $caption, $c['like_count'] ?? 0, $uid);
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
                                    ':ext_id' => $c['id'],
                                    ':author_name' => $c['username'] ?? 'Usuario Instagram',
                                    ':author_handle' => '@' . ($c['username'] ?? 'usuario'),
                                    ':author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($c['username'] ?? 'U') . '&background=6366f1&color=fff',
                                    ':comment_text' => $c['text'],
                                    ':sentiment' => $analysis['sentiment'],
                                    ':intent' => $analysis['intent'],
                                    ':highlight_score' => $analysis['highlight_score'],
                                    ':is_highlighted' => $analysis['is_highlighted'],
                                    ':highlight_reason' => $analysis['highlight_reason'],
                                    ':likes_count' => (int)($c['like_count'] ?? 0)
                                ]);
                                $syncedCommentsCount++;
                            }
                        }
                    }
                }
            }
        }

        return [
            'success' => true,
            'synced_new_posts' => $syncedPostsCount,
            'synced_new_comments' => $syncedCommentsCount,
            'message' => "Sincronización completada con Meta Graph API. Se sincronizaron las publicaciones e importaron $syncedCommentsCount comentarios."
        ];
    }

    private static function makeGetRequest(string $url): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
