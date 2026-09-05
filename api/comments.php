<?php
/**
 * REST API: Comments Controller (Hardened with Multi-Tenant User Isolation, CSRF & Rate Limiting)
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/MetaApiService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$pdo = Database::getConnection();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $allowedPlatforms = ['all', 'instagram', 'facebook'];
        $allowedFilters = ['all', 'highlighted', 'leads', 'urgent', 'pending', 'replied'];

        $platform = Security::validateEnum($_GET['platform'] ?? 'all', $allowedPlatforms, 'all');
        $filter = Security::validateEnum($_GET['filter'] ?? 'all', $allowedFilters, 'all');
        $search = Security::sanitizeString($_GET['search'] ?? '', 100);
        $postId = isset($_GET['post_id']) && is_numeric($_GET['post_id']) ? (int)$_GET['post_id'] : null;
        $accountId = isset($_GET['account_id']) && is_numeric($_GET['account_id']) && (int)$_GET['account_id'] > 0 ? (int)$_GET['account_id'] : null;

        $sql = "
            SELECT 
                c.*, 
                p.caption as post_caption,
                p.media_url as post_media_url,
                p.platform as post_platform,
                p.total_likes as post_likes_count,
                p.total_comments as post_comments_count,
                p.reach as post_reach,
                p.impressions as post_impressions,
                p.account_id,
                COALESCE(a.account_name, 'Mi Cuenta') as account_name,
                a.avatar_url as account_avatar,
                a.page_id as account_page_id,
                COALESCE(a.platform, c.platform) as account_platform,
                COALESCE(p.brand_voice_id, a.brand_voice_id, 1) as brand_voice_id,
                COALESCE(bv.brand_name, 'Voz de Marca') as brand_voice_name,
                COALESCE(bv.tone_level, 'friendly_engaging') as brand_voice_tone,
                r.reply_text,
                r.variant_type as reply_variant_type,
                r.created_at as reply_created_at
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            LEFT JOIN accounts a ON p.account_id = a.id
            LEFT JOIN brand_voices bv ON COALESCE(p.brand_voice_id, a.brand_voice_id) = bv.id
            LEFT JOIN replies r ON r.comment_id = c.id
            WHERE c.user_id = :user_id
        ";
        $params = [':user_id' => $userId];

        if ($platform !== 'all') {
            $sql .= " AND c.platform = :platform";
            $params[':platform'] = $platform;
        }

        if ($accountId !== null) {
            $sql .= " AND p.account_id = :account_id";
            $params[':account_id'] = $accountId;
        }

        if ($postId !== null && $postId > 0) {
            $sql .= " AND c.post_id = :post_id";
            $params[':post_id'] = $postId;
        }

        if ($filter === 'highlighted') {
            $sql .= " AND (c.is_highlighted = 1 OR c.highlight_score >= 80)";
        } elseif ($filter === 'leads') {
            $sql .= " AND (c.sentiment = 'lead' OR c.intent LIKE 'lead_%')";
        } elseif ($filter === 'urgent') {
            $sql .= " AND (c.sentiment = 'urgent' OR c.intent = 'support')";
        } elseif ($filter === 'pending') {
            $sql .= " AND c.status = 'pending'";
        } elseif ($filter === 'replied') {
            $sql .= " AND c.status = 'replied'";
        } elseif ($filter === 'spam') {
            $sql .= " AND (c.status = 'spam' OR c.sentiment = 'spam')";
        }

        if (!empty($search)) {
            $sql .= " AND (c.comment_text LIKE :search OR c.author_name LIKE :search OR c.author_handle LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        $sql .= " ORDER BY c.is_highlighted DESC, c.highlight_score DESC, c.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $comments = $stmt->fetchAll();

        // Ensure real profile pictures are resolved
        foreach ($comments as &$c) {
            if (empty($c['account_avatar']) || str_contains($c['account_avatar'], 'ui-avatars.com')) {
                if (!empty($c['account_page_id'])) {
                    $c['account_avatar'] = "https://graph.facebook.com/v19.0/{$c['account_page_id']}/picture?type=large";
                }
            }
        }
        unset($c);

        // Calculate summary counts for this specific user
        $countStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_highlighted = 1 OR highlight_score >= 80 THEN 1 ELSE 0 END) as highlighted_count,
                SUM(CASE WHEN sentiment = 'lead' OR intent LIKE 'lead_%' THEN 1 ELSE 0 END) as leads_count,
                SUM(CASE WHEN sentiment = 'urgent' THEN 1 ELSE 0 END) as urgent_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'replied' THEN 1 ELSE 0 END) as replied_count,
                SUM(CASE WHEN status = 'spam' OR sentiment = 'spam' THEN 1 ELSE 0 END) as spam_count
            FROM comments
            WHERE user_id = :user_id
        ");
        $countStmt->execute([':user_id' => $userId]);
        $counts = $countStmt->fetch() ?: [];
        $counts['total'] = (int)($counts['total'] ?? 0);
        $counts['highlighted_count'] = (int)($counts['highlighted_count'] ?? 0);
        $counts['leads_count'] = (int)($counts['leads_count'] ?? 0);
        $counts['urgent_count'] = (int)($counts['urgent_count'] ?? 0);
        $counts['pending_count'] = (int)($counts['pending_count'] ?? 0);
        $counts['replied_count'] = (int)($counts['replied_count'] ?? 0);
        $counts['spam_count'] = (int)($counts['spam_count'] ?? 0);

        echo json_encode([
            'success' => true,
            'counts' => $counts,
            'data' => $comments
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
    }

    if ($method === 'POST') {
        // Enforce anti-CSRF check on all state modifications
        Security::requireCsrf();
        Security::requireRateLimit('comments_mutate_' . $userId, 80, 60);

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST;
        
        $allowedActions = ['reply', 'toggle_highlight', 'change_status', 'create_simulated', 'delete'];
        $action = Security::validateEnum($input['action'] ?? '', $allowedActions, '');

        if (empty($action)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Acción no permitida o inválida.']);
            exit;
        }

        if ($action === 'reply') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            $replyText = Security::sanitizeString($input['reply_text'] ?? '', 2000);
            $replyType = Security::sanitizeString($input['reply_type'] ?? 'copilot', 50);
            $toneUsed = Security::sanitizeString($input['tone_used'] ?? 'friendly', 50);
            $variantType = Security::validateEnum($input['variant_type'] ?? 'engagement', ['engagement', 'conversion', 'support', 'auto'], 'engagement');

            if ($commentId <= 0 || empty($replyText)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id y reply_text son obligatorios.']);
                exit;
            }

            // Verify comment belongs to current user
            $cCheck = $pdo->prepare("SELECT id FROM comments WHERE id = :id AND user_id = :uid LIMIT 1");
            $cCheck->execute([':id' => $commentId, ':uid' => $userId]);
            if (!$cCheck->fetch()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No tienes permiso para responder a este comentario.']);
                exit;
            }

            // Save reply in database with user_id
            $stmtReply = $pdo->prepare("
                INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                VALUES (:user_id, :comment_id, :reply_text, :reply_type, :tone_used, :variant_type, 1)
            ");
            $stmtReply->execute([
                ':user_id' => $userId,
                ':comment_id' => $commentId,
                ':reply_text' => $replyText,
                ':reply_type' => $replyType,
                ':tone_used' => $toneUsed,
                ':variant_type' => $variantType
            ]);

            // Update comment status to 'replied'
            $stmtUp = $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id AND user_id = :uid");
            $stmtUp->execute([':id' => $commentId, ':uid' => $userId]);

            // Post to Meta API with user context
            $metaResult = MetaApiService::postReplyToMeta($commentId, $replyText, $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Respuesta enviada y registrada con éxito.',
                'meta_result' => $metaResult
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'toggle_highlight') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            if ($commentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE comments SET is_highlighted = CASE WHEN is_highlighted = 1 THEN 0 ELSE 1 END WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $commentId, ':uid' => $userId]);

            $check = $pdo->prepare("SELECT is_highlighted FROM comments WHERE id = :id AND user_id = :uid LIMIT 1");
            $check->execute([':id' => $commentId, ':uid' => $userId]);
            $res = $check->fetch();

            echo json_encode(['success' => true, 'is_highlighted' => (int)($res['is_highlighted'] ?? 0)]);
            exit;
        }

        if ($action === 'change_status') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            $status = Security::validateEnum($input['status'] ?? 'pending', ['pending', 'replied', 'ignored'], 'pending');

            if ($commentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id inválido.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE comments SET status = :status WHERE id = :id AND user_id = :uid");
            $stmt->execute([':status' => $status, ':id' => $commentId, ':uid' => $userId]);

            echo json_encode(['success' => true, 'status' => $status]);
            exit;
        }

        if ($action === 'create_simulated') {
            $postId = Security::sanitizeInt($input['post_id'] ?? 1, 1, 10000000, 1);
            $platform = Security::validateEnum($input['platform'] ?? 'instagram', ['instagram', 'facebook'], 'instagram');
            $authorName = Security::sanitizeString($input['author_name'] ?? 'Usuario Demo', 80);
            $commentText = Security::sanitizeString($input['comment_text'] ?? '', 1500);
            
            if (empty($commentText)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'El comentario no puede estar vacío']);
                exit;
            }

            // Get post caption or fallback
            $stmtPost = $pdo->prepare("SELECT id, caption FROM posts WHERE (id = :id OR user_id = :uid) ORDER BY id DESC LIMIT 1");
            $stmtPost->execute([':id' => $postId, ':uid' => $userId]);
            $post = $stmtPost->fetch();
            
            if (!$post) {
                // If user has no posts yet, create a default welcome post for them
                $insPost = $pdo->prepare("
                    INSERT INTO posts (user_id, account_id, platform, caption, media_url, total_likes, total_comments, reach)
                    VALUES (:uid, 1, :platform, '¡Bienvenidos a nuestra comunidad oficial! Déjanos tus preguntas y reflexiones aquí 👇', 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=480&h=320&fit=crop&auto=format&q=75', 120, 5, 1200)
                ");
                $insPost->execute([':uid' => $userId, ':platform' => $platform]);
                $postId = (int)$pdo->lastInsertId();
                $caption = '¡Bienvenidos a nuestra comunidad oficial!';
            } else {
                $postId = (int)$post['id'];
                $caption = $post['caption'] ?? '';
            }

            // Run AI analysis immediately with user's settings
            $analysis = AiAgentService::analyzeComment($commentText, $caption, rand(1, 15), $userId);

            $cleanHandle = preg_replace('/[^a-zA-Z0-9_\.]/', '', strtolower(str_replace(' ', '', $authorName)));
            $handle = '@' . (empty($cleanHandle) ? 'usuario' : $cleanHandle);
            $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($authorName) . '&background=6366f1&color=fff&size=96';

            $stmtInsert = $pdo->prepare("
                INSERT INTO comments (
                    user_id, post_id, platform, external_comment_id, author_name, author_handle, 
                    author_avatar, comment_text, sentiment, intent, highlight_score, 
                    is_highlighted, highlight_reason, likes_count, status
                ) VALUES (
                    :user_id, :post_id, :platform, :ext_id, :author_name, :author_handle, 
                    :author_avatar, :comment_text, :sentiment, :intent, :highlight_score, 
                    :is_highlighted, :highlight_reason, :likes_count, 'pending'
                )
            ");

            $stmtInsert->execute([
                ':user_id' => $userId,
                ':post_id' => $postId,
                ':platform' => $platform,
                ':ext_id' => 'cmt_sim_' . time() . '_' . mt_rand(100, 999),
                ':author_name' => $authorName,
                ':author_handle' => $handle,
                ':author_avatar' => $avatar,
                ':comment_text' => $commentText,
                ':sentiment' => $analysis['sentiment'],
                ':intent' => $analysis['intent'],
                ':highlight_score' => $analysis['highlight_score'],
                ':is_highlighted' => $analysis['is_highlighted'],
                ':highlight_reason' => $analysis['highlight_reason'],
                ':likes_count' => rand(2, 20)
            ]);

            $newId = $pdo->lastInsertId();

            echo json_encode([
                'success' => true,
                'comment_id' => $newId,
                'analysis' => $analysis,
                'message' => 'Comentario simulado añadido y analizado por el agente de IA de forma segura.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'delete') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            if ($commentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id inválido']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $commentId, ':uid' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Comentario eliminado']);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método o acción no soportada']);
} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar la solicitud de comentarios.', $e);
}
