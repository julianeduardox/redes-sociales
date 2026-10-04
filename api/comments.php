<?php
/**
 * REST API: Comments Controller (Hardened with Multi-Tenant User Isolation, CSRF & Rate Limiting)
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/MetaApiService.php';
require_once __DIR__ . '/../services/WeeklyReportAgentService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
Auth::releaseSessionLock();
$pdo = Database::getConnection();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $allowedPlatforms = ['all', 'instagram', 'facebook'];
        $allowedFilters = [
            'all', 'inbox', 'new', 'archived', 'highlighted', 'leads', 'highlighted_leads',
            'urgent', 'support', 'pending', 'replied', 'spam', 'failed',
            'pending_all', 'pending_new', 'ai_review', 'leads_urgent', 'ignored', 'spam_ignored'
        ];

        $platform = Security::validateEnum($_GET['platform'] ?? 'all', $allowedPlatforms, 'all');
        $filter = Security::validateEnum($_GET['filter'] ?? 'all', $allowedFilters, 'all');
        $search = Security::sanitizeString($_GET['search'] ?? '', 100);
        $postId = isset($_GET['post_id']) && is_numeric($_GET['post_id']) ? (int)$_GET['post_id'] : null;
        $accountId = isset($_GET['account_id']) && is_numeric($_GET['account_id']) && (int)$_GET['account_id'] > 0 ? (int)$_GET['account_id'] : null;
        $includeArchived = !empty($_GET['include_archived']) && $_GET['include_archived'] == '1';

        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 20;
        if ($limit < 1) $limit = 1;
        if ($limit > 100) $limit = 100;
        $offset = ($page - 1) * $limit;

        $fromClause = "
            FROM comments c
            JOIN posts p ON c.post_id = p.id
            LEFT JOIN accounts a ON p.account_id = a.id
            LEFT JOIN brand_voices bv ON COALESCE(p.brand_voice_id, a.brand_voice_id) = bv.id
            LEFT JOIN (
                SELECT comment_id, id, reply_text, variant_type, is_posted_to_platform, created_at
                FROM replies
                WHERE id IN (SELECT MAX(id) FROM replies GROUP BY comment_id)
            ) r ON r.comment_id = c.id
        ";
        $whereSql = " WHERE c.user_id = :user_id";
        $params = [':user_id' => $userId];

        if ($platform !== 'all') {
            $whereSql .= " AND c.platform = :platform";
            $params[':platform'] = $platform;
        }

        if ($accountId !== null) {
            $whereSql .= " AND p.account_id = :account_id";
            $params[':account_id'] = $accountId;
        }

        if ($postId !== null && $postId > 0) {
            $whereSql .= " AND c.post_id = :post_id";
            $params[':post_id'] = $postId;
        }

        if ($filter === 'archived') {
            $whereSql .= " AND c.is_archived = 1";
        } else {
            // All active views exclude archived comments unless explicitly requested
            if (!$includeArchived) {
                $whereSql .= " AND (c.is_archived = 0 OR c.is_archived IS NULL)";
            }

            if ($filter === 'pending_all') {
                $whereSql .= " AND (c.status IN ('pending', 'pending_review', 'ai_unavailable', 'invalid_ai_output', 'failed') OR (r.is_posted_to_platform = 0 AND r.reply_text IS NOT NULL))";
            } elseif ($filter === 'pending_new') {
                $whereSql .= " AND c.status = 'pending'";
            } elseif ($filter === 'ai_review') {
                $whereSql .= " AND c.status IN ('pending_review', 'ai_unavailable', 'invalid_ai_output')";
            } elseif ($filter === 'leads_urgent') {
                $whereSql .= " AND (c.sentiment IN ('lead', 'urgent') OR c.intent LIKE 'lead_%' OR c.intent = 'support') AND (c.status IN ('pending', 'pending_review', 'ai_unavailable', 'invalid_ai_output', 'failed') OR (r.is_posted_to_platform = 0 AND r.reply_text IS NOT NULL))";
            } elseif ($filter === 'ignored') {
                $whereSql .= " AND c.status = 'ignored'";
            } elseif ($filter === 'spam_ignored') {
                $whereSql .= " AND (c.status IN ('spam', 'ignored') OR c.sentiment = 'spam')";
            } elseif ($filter === 'new' || $filter === 'pending') {
                $whereSql .= " AND c.status = 'pending'";
            } elseif ($filter === 'highlighted') {
                $whereSql .= " AND (c.is_highlighted = 1 OR c.highlight_score >= 80)";
            } elseif ($filter === 'leads' || $filter === 'highlighted_leads') {
                $whereSql .= " AND (c.sentiment = 'lead' OR c.intent LIKE 'lead_%' OR c.is_highlighted = 1 OR c.highlight_score >= 80)";
            } elseif ($filter === 'urgent' || $filter === 'support') {
                $whereSql .= " AND (c.sentiment = 'urgent' OR c.intent = 'support' OR c.status = 'failed')";
            } elseif ($filter === 'replied') {
                $whereSql .= " AND c.status = 'replied'";
            } elseif ($filter === 'failed') {
                $whereSql .= " AND (c.status = 'failed' OR (r.is_posted_to_platform = 0 AND r.reply_text IS NOT NULL))";
            } elseif ($filter === 'spam') {
                $whereSql .= " AND (c.status = 'spam' OR c.sentiment = 'spam')";
            }
            // 'all' and 'inbox' show all active unarchived comments
        }

        if (!empty($search)) {
            $whereSql .= " AND (c.comment_text LIKE :search OR c.author_name LIKE :search OR c.author_handle LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        // Count total matching items for pagination
        $countQuery = "SELECT COUNT(*) " . $fromClause . $whereSql;
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $totalItems = (int)($countStmt->fetchColumn() ?: 0);
        $totalPages = $totalItems > 0 ? (int)ceil($totalItems / $limit) : 1;

        $selectCols = "
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
                r.id as reply_id,
                r.reply_text,
                r.variant_type as reply_variant_type,
                r.is_posted_to_platform,
                r.created_at as reply_created_at,
                c.highlight_reason as meta_error
        ";

        $sql = $selectCols . $fromClause . $whereSql . " ORDER BY c.is_highlighted DESC, c.highlight_score DESC, c.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
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

        // Calculate summary counts for this specific user (both active and total)
        // Explicitly joining latest reply to align pending_all_count and failed_count exactly with list filters
        $countStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_all,
                SUM(CASE WHEN (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as total,
                SUM(CASE WHEN c.is_archived = 1 THEN 1 ELSE 0 END) as archived_count,
                SUM(CASE WHEN c.status = 'replied' AND (c.is_archived = 0 OR c.is_archived IS NULL) AND (r.is_posted_to_platform = 1 OR r.is_posted_to_platform IS NULL) THEN 1 ELSE 0 END) as can_archive_count,
                SUM(CASE WHEN (c.is_highlighted = 1 OR c.highlight_score >= 80) AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as highlighted_count,
                SUM(CASE WHEN (c.sentiment = 'lead' OR c.intent LIKE 'lead_%') AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as leads_count,
                SUM(CASE WHEN c.sentiment = 'urgent' AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as urgent_count,
                SUM(CASE WHEN c.status = 'pending' AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN c.status = 'pending' AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as pending_new_count,
                SUM(CASE WHEN c.status IN ('pending_review', 'ai_unavailable', 'invalid_ai_output') AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as ai_review_count,
                SUM(CASE WHEN (c.status = 'failed' OR (r.is_posted_to_platform = 0 AND r.reply_text IS NOT NULL)) AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN (c.sentiment IN ('lead', 'urgent') OR c.intent LIKE 'lead_%' OR c.intent = 'support') AND (c.status IN ('pending', 'pending_review', 'ai_unavailable', 'invalid_ai_output', 'failed') OR (r.is_posted_to_platform = 0 AND r.reply_text IS NOT NULL)) AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as leads_urgent_count,
                SUM(CASE WHEN (c.status IN ('pending', 'pending_review', 'ai_unavailable', 'invalid_ai_output', 'failed') OR (r.is_posted_to_platform = 0 AND r.reply_text IS NOT NULL)) AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as pending_all_count,
                SUM(CASE WHEN c.status = 'replied' AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as replied_count,
                SUM(CASE WHEN (c.status = 'spam' OR c.sentiment = 'spam') AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as spam_count,
                SUM(CASE WHEN c.status = 'ignored' AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as ignored_count,
                SUM(CASE WHEN (c.status IN ('spam', 'ignored') OR c.sentiment = 'spam') AND (c.is_archived = 0 OR c.is_archived IS NULL) THEN 1 ELSE 0 END) as spam_ignored_count
            FROM comments c
            LEFT JOIN (
                SELECT comment_id, is_posted_to_platform, reply_text
                FROM replies
                WHERE id IN (SELECT MAX(id) FROM replies GROUP BY comment_id)
            ) r ON r.comment_id = c.id
            WHERE c.user_id = :user_id
        ");
        $countStmt->execute([':user_id' => $userId]);
        $counts = $countStmt->fetch() ?: [];
        foreach ($counts as $k => $v) {
            $counts[$k] = (int)($v ?? 0);
        }

        echo json_encode([
            'success' => true,
            'counts' => $counts,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_items' => $totalItems,
                'total_pages' => $totalPages
            ],
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
        
        $allowedActions = ['reply', 'retry_reply', 'toggle_highlight', 'change_status', 'create_simulated', 'delete', 'run_weekly_cleanup', 'archive_comment', 'restore_comment', 'save_gold_example', 'save_draft'];
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
            $wasEdited = !empty($input['was_edited']);
            $originalSuggestion = Security::sanitizeString($input['original_suggestion'] ?? '', 2000);
            $isGoldExample = !empty($input['is_gold_example']);

            if ($commentId <= 0 || empty($replyText)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id y reply_text son obligatorios.']);
                exit;
            }

            // Verify comment belongs to current user
            $cCheck = $pdo->prepare("
                SELECT c.id, c.platform, c.external_comment_id, c.comment_text,
                       COALESCE(p.brand_voice_id, a.brand_voice_id, 1) as brand_voice_id
                FROM comments c 
                LEFT JOIN posts p ON c.post_id = p.id
                LEFT JOIN accounts a ON p.account_id = a.id
                WHERE c.id = :id AND c.user_id = :uid LIMIT 1
            ");
            $cCheck->execute([':id' => $commentId, ':uid' => $userId]);
            $commentData = $cCheck->fetch();
            if (!$commentData) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No tienes permiso para responder a este comentario.']);
                exit;
            }

            $platformName = ucfirst($commentData['platform'] ?? 'red social');

            // Post to Meta API first to verify if it actually publishes (manual action)
            $metaResult = MetaApiService::postReplyToMeta($commentId, $replyText, $userId, true);
            $isPosted = !empty($metaResult['success']) ? 1 : 0;

            // Save reply in database with user_id and actual publication flag
            $stmtReply = $pdo->prepare("
                INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                VALUES (:user_id, :comment_id, :reply_text, :reply_type, :tone_used, :variant_type, :is_posted)
            ");
            $stmtReply->execute([
                ':user_id' => $userId,
                ':comment_id' => $commentId,
                ':reply_text' => $replyText,
                ':reply_type' => $replyType,
                ':tone_used' => $toneUsed,
                ':variant_type' => $variantType,
                ':is_posted' => $isPosted
            ]);

            // Human-in-the-Loop Continuous Learning: Record feedback to train Gemini's active context
            $learnedCommentText = $commentData['comment_text'] ?? '';
            $learnedBrandVoiceId = (int)($commentData['brand_voice_id'] ?? 1);
            if (!empty($learnedCommentText)) {
                AiAgentService::recordLearningFeedback(
                    $userId,
                    $learnedBrandVoiceId,
                    $learnedCommentText,
                    $replyText,
                    $originalSuggestion,
                    $wasEdited,
                    $isGoldExample,
                    $commentId
                );
            }

            if ($isPosted) {
                // Successfully posted to Meta or simulated locally in demo mode
                $stmtUp = $pdo->prepare("UPDATE comments SET status = 'replied', highlight_reason = NULL WHERE id = :id AND user_id = :uid");
                $stmtUp->execute([':id' => $commentId, ':uid' => $userId]);

                echo json_encode([
                    'success' => true,
                    'is_posted_to_platform' => 1,
                    'learned' => true,
                    'was_edited' => $wasEdited,
                    'message' => "¡Respuesta publicada y registrada con éxito en {$platformName}! Gemini ha aprendido de esta interacción. 🧠",
                    'meta_result' => $metaResult
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                // Failed to post to Meta platform
                $errorReason = $metaResult['error'] ?? "Error desconocido al publicar en {$platformName}";
                $stmtUp = $pdo->prepare("UPDATE comments SET status = 'failed', highlight_reason = :reason WHERE id = :id AND user_id = :uid");
                $stmtUp->execute([':reason' => $errorReason, ':id' => $commentId, ':uid' => $userId]);

                echo json_encode([
                    'success' => false,
                    'is_posted_to_platform' => 0,
                    'error' => "No se pudo publicar en {$platformName}: {$errorReason}",
                    'is_token_expired' => !empty($metaResult['is_token_expired']),
                    'meta_result' => $metaResult
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        if ($action === 'retry_reply') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            if ($commentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id inválido.']);
                exit;
            }

            // Verify comment belongs to current user
            $cCheck = $pdo->prepare("SELECT id, platform, external_comment_id FROM comments WHERE id = :id AND user_id = :uid LIMIT 1");
            $cCheck->execute([':id' => $commentId, ':uid' => $userId]);
            $commentData = $cCheck->fetch();
            if (!$commentData) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Comentario no encontrado o sin permisos.']);
                exit;
            }

            $platformName = ucfirst($commentData['platform'] ?? 'red social');

            // Find existing reply text
            $rCheck = $pdo->prepare("SELECT id, reply_text FROM replies WHERE comment_id = :cid AND user_id = :uid ORDER BY id DESC LIMIT 1");
            $rCheck->execute([':cid' => $commentId, ':uid' => $userId]);
            $existingReply = $rCheck->fetch();

            $replyText = !empty($input['reply_text']) ? Security::sanitizeString($input['reply_text'], 2000) : ($existingReply['reply_text'] ?? '');

            if (empty($replyText)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'No hay texto de respuesta registrado para reintentar.']);
                exit;
            }

            // Re-attempt Meta Graph API post (manual action)
            $metaResult = MetaApiService::postReplyToMeta($commentId, $replyText, $userId, true);

            if (!empty($metaResult['success'])) {
                if ($existingReply) {
                    $pdo->prepare("UPDATE replies SET reply_text = :txt, is_posted_to_platform = 1 WHERE id = :rid AND user_id = :uid")
                        ->execute([':txt' => $replyText, ':rid' => $existingReply['id'], ':uid' => $userId]);
                } else {
                    $pdo->prepare("INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform) VALUES (:uid, :cid, :txt, 'retry', 'friendly', 'engagement', 1)")
                        ->execute([':uid' => $userId, ':cid' => $commentId, ':txt' => $replyText]);
                }

                $pdo->prepare("UPDATE comments SET status = 'replied', highlight_reason = NULL WHERE id = :id AND user_id = :uid")
                    ->execute([':id' => $commentId, ':uid' => $userId]);

                echo json_encode([
                    'success' => true,
                    'is_posted_to_platform' => 1,
                    'message' => "¡Respuesta reintentada y publicada con éxito en {$platformName}!",
                    'meta_result' => $metaResult
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                $errorReason = $metaResult['error'] ?? "Error al reintentar publicación en {$platformName}";
                $pdo->prepare("UPDATE comments SET status = 'failed', highlight_reason = :reason WHERE id = :id AND user_id = :uid")
                    ->execute([':reason' => $errorReason, ':id' => $commentId, ':uid' => $userId]);

                echo json_encode([
                    'success' => false,
                    'is_posted_to_platform' => 0,
                    'error' => "Fallo al reintentar publicación en {$platformName}: {$errorReason}",
                    'is_token_expired' => !empty($metaResult['is_token_expired']),
                    'meta_result' => $metaResult
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
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

            // Get post caption or fallback with strict multi-tenant isolation
            if ($postId > 0) {
                $stmtPost = $pdo->prepare("SELECT id, caption FROM posts WHERE id = :id AND user_id = :uid LIMIT 1");
                $stmtPost->execute([':id' => $postId, ':uid' => $userId]);
            } else {
                $stmtPost = $pdo->prepare("SELECT id, caption FROM posts WHERE user_id = :uid ORDER BY id DESC LIMIT 1");
                $stmtPost->execute([':uid' => $userId]);
            }
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

        if ($action === 'run_weekly_cleanup') {
            $archive = !empty($input['archive']) || !isset($input['archive']);
            $result = WeeklyReportAgentService::runCleanupAndReport($userId, $archive);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'archive_comment') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            if ($commentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id inválido']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE comments SET is_archived = 1, archived_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $commentId, ':uid' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Comentario archivado']);
            exit;
        }

        if ($action === 'restore_comment') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            if ($commentId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id inválido']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE comments SET is_archived = 0, archived_at = NULL WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $commentId, ':uid' => $userId]);
            echo json_encode(['success' => true, 'message' => 'Comentario restaurado a la bandeja activa']);
            exit;
        }

        if ($action === 'save_gold_example') {
            $commentText = Security::sanitizeString($input['comment_text'] ?? '', 1500);
            $replyText = Security::sanitizeString($input['reply_text'] ?? '', 1500);
            $brandVoiceId = Security::sanitizeInt($input['brand_voice_id'] ?? 1, 1, 1000000, 1);
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 0, 10000000, 0);
            $originalSuggestion = Security::sanitizeString($input['original_suggestion'] ?? '', 1500);
            $wasEdited = !empty($input['was_edited']);

            // Defensive fallback: if comment_id is given, retrieve comment text and brand voice from DB if needed
            if ($commentId > 0) {
                try {
                    $cStmt = $pdo->prepare("
                        SELECT c.comment_text, COALESCE(p.brand_voice_id, a.brand_voice_id, 1) as bvid
                        FROM comments c
                        LEFT JOIN posts p ON c.post_id = p.id
                        LEFT JOIN accounts a ON p.account_id = a.id
                        WHERE c.id = :id AND c.user_id = :uid LIMIT 1
                    ");
                    $cStmt->execute([':id' => $commentId, ':uid' => $userId]);
                    $cRow = $cStmt->fetch();
                    if ($cRow) {
                        if (empty($commentText)) {
                            $commentText = $cRow['comment_text'] ?? '';
                        }
                        if ($brandVoiceId <= 1 && !empty($cRow['bvid'])) {
                            $brandVoiceId = (int)$cRow['bvid'];
                        }
                    }
                } catch (Throwable) {}
            }

            if (empty($commentText) || empty($replyText)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'El texto del comentario y la respuesta son obligatorios.']);
                exit;
            }

            $saved = AiAgentService::recordLearningFeedback(
                $userId,
                $brandVoiceId,
                $commentText,
                $replyText,
                $originalSuggestion,
                $wasEdited,
                true,
                $commentId > 0 ? $commentId : null
            );

            // Also persist as draft reply in replies table if commentId > 0 so it's recorded
            if ($commentId > 0) {
                try {
                    $stmtReply = $pdo->prepare("
                        INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                        VALUES (:user_id, :comment_id, :reply_text, 'gold_draft', 'stoic_mentor', 'gold', 0)
                    ");
                    $stmtReply->execute([
                        ':user_id' => $userId,
                        ':comment_id' => $commentId,
                        ':reply_text' => $replyText
                    ]);
                } catch (Throwable) {}
            }

            echo json_encode([
                'success' => (bool)$saved,
                'message' => '⭐ ¡Frase guardada como Ejemplo de Oro con éxito! Hermes la usará como estándar de estilo sin publicarla en Meta.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'save_draft') {
            $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
            $replyText = Security::sanitizeString($input['reply_text'] ?? '', 2000);
            $variantType = Security::validateEnum($input['variant_type'] ?? 'engagement', ['engagement', 'conversion', 'support', 'gold', 'auto'], 'engagement');

            if ($commentId <= 0 || empty($replyText)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'comment_id y reply_text son obligatorios.']);
                exit;
            }

            $stmtReply = $pdo->prepare("
                INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                VALUES (:user_id, :comment_id, :reply_text, 'draft', 'stoic_mentor', :variant_type, 0)
            ");
            $stmtReply->execute([
                ':user_id' => $userId,
                ':comment_id' => $commentId,
                ':reply_text' => $replyText,
                ':variant_type' => $variantType
            ]);

            echo json_encode([
                'success' => true,
                'message' => '💾 Borrador guardado localmente en la base de datos (sin publicar en Meta).'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método o acción no soportada']);
} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar la solicitud de comentarios.', $e);
}
