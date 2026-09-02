<?php
/**
 * REST API: AI Agent Controller (Hardened with CSRF, Rate Limiting & Denial of Wallet Protection)
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AiAgentService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();

$pdo = Database::getConnection();

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método no permitido']);
        exit;
    }

    // CSRF protection
    Security::requireCsrf();

    // Rate limiting for AI operations (40 requests / minute per IP)
    Security::requireRateLimit('ai_agent_ops', 40, 60);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? $_POST;
    
    $allowedActions = ['generate_replies', 'analyze_comment', 'batch_autopilot', 'test_voice_playground'];
    $action = Security::validateEnum($input['action'] ?? 'generate_replies', $allowedActions, 'generate_replies');

    if ($action === 'test_voice_playground') {
        $authorName = Security::sanitizeString($input['author_name'] ?? 'Seguidor de Prueba', 80);
        $commentText = Security::sanitizeString($input['comment_text'] ?? 'Llevo días sin motivación, ¿cómo forjar disciplina diaria?', 1500);
        $platform = Security::validateEnum($input['platform'] ?? 'instagram', ['instagram', 'facebook'], 'instagram');
        $postCaption = Security::sanitizeString($input['post_caption'] ?? 'El obstáculo es el camino. Dicotomía del control.', 1500);
        $overrideTone = Security::sanitizeString($input['tone'] ?? '', 60);

        $runtimeOverrides = [];
        if (isset($input['brand_name'])) $runtimeOverrides['brand_name'] = Security::sanitizeString($input['brand_name'], 150);
        if (isset($input['brand_description'])) $runtimeOverrides['brand_description'] = Security::sanitizeString($input['brand_description'], 2000);
        if (isset($input['brand_tone'])) $runtimeOverrides['brand_tone'] = Security::sanitizeString($input['brand_tone'], 60);
        if (isset($input['brand_warmth_level'])) $runtimeOverrides['brand_warmth_level'] = Security::sanitizeInt($input['brand_warmth_level'], 1, 100, 85);
        if (isset($input['brand_depth_level'])) $runtimeOverrides['brand_depth_level'] = Security::sanitizeInt($input['brand_depth_level'], 1, 100, 80);
        if (isset($input['brand_energy_level'])) $runtimeOverrides['brand_energy_level'] = Security::sanitizeInt($input['brand_energy_level'], 1, 100, 75);
        if (isset($input['brand_closing_question_rule'])) $runtimeOverrides['brand_closing_question_rule'] = Security::validateEnum($input['brand_closing_question_rule'], ['always', 'relevant', 'never'], 'always');
        if (isset($input['brand_emoji_style'])) $runtimeOverrides['brand_emoji_style'] = Security::validateEnum($input['brand_emoji_style'], ['minimal', 'moderate', 'expressive'], 'moderate');
        if (isset($input['brand_key_phrases']) && is_array($input['brand_key_phrases'])) $runtimeOverrides['brand_key_phrases'] = $input['brand_key_phrases'];
        if (isset($input['brand_forbidden_phrases']) && is_array($input['brand_forbidden_phrases'])) $runtimeOverrides['brand_forbidden_phrases'] = $input['brand_forbidden_phrases'];
        if (isset($input['brand_few_shot_examples']) && is_array($input['brand_few_shot_examples'])) $runtimeOverrides['brand_few_shot_examples'] = $input['brand_few_shot_examples'];
        if (isset($input['ai_provider'])) $runtimeOverrides['ai_provider'] = Security::validateEnum($input['ai_provider'], ['gemini', 'openai', 'heuristic'], 'heuristic');

        $replies = AiAgentService::generateReplies($authorName, $commentText, $platform, $postCaption, $overrideTone, $runtimeOverrides);

        echo json_encode([
            'success' => true,
            'replies' => $replies,
            'simulated_for' => [
                'author' => $authorName,
                'comment' => $commentText
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'generate_replies') {
        $commentId = Security::sanitizeInt($input['comment_id'] ?? 0, 1, 10000000, 0);
        $overrideTone = Security::sanitizeString($input['tone'] ?? '', 60);

        if ($commentId > 0) {
            $stmt = $pdo->prepare("
                SELECT c.*, p.caption as post_caption 
                FROM comments c 
                JOIN posts p ON c.post_id = p.id 
                WHERE c.id = :id LIMIT 1
            ");
            $stmt->execute([':id' => $commentId]);
            $comment = $stmt->fetch();

            if (!$comment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Comentario no encontrado']);
                exit;
            }

            $authorName = $comment['author_name'];
            $commentText = $comment['comment_text'];
            $platform = $comment['platform'];
            $postCaption = $comment['post_caption'];
        } else {
            $authorName = Security::sanitizeString($input['author_name'] ?? 'Usuario', 80);
            $commentText = Security::sanitizeString($input['comment_text'] ?? '', 1500);
            $platform = Security::validateEnum($input['platform'] ?? 'instagram', ['instagram', 'facebook'], 'instagram');
            $postCaption = Security::sanitizeString($input['post_caption'] ?? '', 1500);
        }

        if (empty($commentText)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'El texto del comentario es obligatorio']);
            exit;
        }

        $replies = AiAgentService::generateReplies($authorName, $commentText, $platform, $postCaption, $overrideTone);

        echo json_encode([
            'success' => true,
            'replies' => $replies
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'analyze_comment') {
        $commentText = Security::sanitizeString($input['comment_text'] ?? '', 1500);
        $postCaption = Security::sanitizeString($input['post_caption'] ?? '', 1500);
        $likesCount = Security::sanitizeInt($input['likes_count'] ?? 0, 0, 1000000, 0);

        $analysis = AiAgentService::analyzeComment($commentText, $postCaption, $likesCount);

        echo json_encode([
            'success' => true,
            'analysis' => $analysis
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'batch_autopilot') {
        // Rate limit for batch autopilot
        Security::requireRateLimit('ai_batch_autopilot', 10, 60);

        // Fetch all pending comments for user (any score), ordered with priority
        $stmt = $pdo->prepare("
            SELECT c.*, p.caption as post_caption 
            FROM comments c 
            JOIN posts p ON c.post_id = p.id 
            WHERE c.user_id = :user_id AND c.status = 'pending'
            ORDER BY c.highlight_score DESC, c.id DESC 
            LIMIT 25
        ");
        $stmt->execute([':user_id' => $userId]);
        $pendingComments = $stmt->fetchAll();

        $processed = [];
        $repliedCount = 0;
        $spamCount = 0;
        $ignoredCount = 0;

        foreach ($pendingComments as $c) {
            $suitability = AiAgentService::evaluateCommentSuitability($c['comment_text']);

            if ($suitability['status'] === 'spam') {
                // Mark as spam for human review
                $stmtUp = $pdo->prepare("
                    UPDATE comments 
                    SET status = 'spam', sentiment = 'spam', highlight_reason = :reason 
                    WHERE id = :id
                ");
                $stmtUp->execute([
                    ':reason' => $suitability['reason'],
                    ':id' => $c['id']
                ]);

                $spamCount++;
                $processed[] = [
                    'comment_id' => (int)$c['id'],
                    'author' => htmlspecialchars($c['author_name'], ENT_QUOTES, 'UTF-8'),
                    'action' => 'marked_spam',
                    'reason' => $suitability['reason'],
                    'status' => 'spam'
                ];
                continue;
            }

            if ($suitability['status'] === 'ignored') {
                // Pure emoji / sticker comment without text
                $stmtUp = $pdo->prepare("
                    UPDATE comments 
                    SET status = 'ignored', highlight_reason = :reason 
                    WHERE id = :id
                ");
                $stmtUp->execute([
                    ':reason' => $suitability['reason'],
                    ':id' => $c['id']
                ]);

                $ignoredCount++;
                $processed[] = [
                    'comment_id' => (int)$c['id'],
                    'author' => htmlspecialchars($c['author_name'], ENT_QUOTES, 'UTF-8'),
                    'action' => 'ignored_sticker',
                    'reason' => $suitability['reason'],
                    'status' => 'ignored'
                ];
                continue;
            }

            // Legitimate comment in Spanish (of any score) -> Generate and Post AI Reply
            $replies = AiAgentService::generateReplies($c['author_name'], $c['comment_text'], $c['platform'], $c['post_caption']);
            
            // Select best variant
            $chosenVariant = 'engagement';
            if ($c['sentiment'] === 'lead' || str_starts_with($c['intent'], 'lead_')) {
                $chosenVariant = 'conversion';
            } elseif ($c['sentiment'] === 'urgent' || $c['intent'] === 'support') {
                $chosenVariant = 'support';
            }

            $chosenReply = $replies[$chosenVariant] ?? $replies['engagement'];

            // Insert reply
            $stmtRep = $pdo->prepare("
                INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                VALUES (:user_id, :comment_id, :reply_text, 'autopilot', 'auto_selected', :variant_type, 1)
            ");
            $stmtRep->execute([
                ':user_id' => $userId,
                ':comment_id' => $c['id'],
                ':reply_text' => $chosenReply,
                ':variant_type' => $chosenVariant
            ]);

            // Update status
            $stmtUp = $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id");
            $stmtUp->execute([':id' => $c['id']]);

            $repliedCount++;
            $processed[] = [
                'comment_id' => (int)$c['id'],
                'author' => htmlspecialchars($c['author_name'], ENT_QUOTES, 'UTF-8'),
                'action' => 'replied',
                'reply' => htmlspecialchars($chosenReply, ENT_QUOTES, 'UTF-8'),
                'variant' => $chosenVariant,
                'status' => 'replied'
            ];
        }

        $summaryMsg = "Se procesaron " . count($processed) . " comentarios: {$repliedCount} respondidos con IA";
        if ($spamCount > 0) $summaryMsg .= ", {$spamCount} marcados como spam/inglés para revisión";
        if ($ignoredCount > 0) $summaryMsg .= ", {$ignoredCount} stickers omitidos";
        $summaryMsg .= ".";

        echo json_encode([
            'success' => true,
            'processed_count' => count($processed),
            'replied_count' => $repliedCount,
            'spam_count' => $spamCount,
            'ignored_count' => $ignoredCount,
            'items' => $processed,
            'message' => $summaryMsg
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar la solicitud con el Agente IA.', $e);
}
