<?php
/**
 * Meta Webhook Listener for Facebook & Instagram Real-Time Comment Ingestion
 * Hardened with HMAC-SHA256 Signature Verification, Timing-Safe Token Check & Strict Sanitization
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/MetaApiService.php';

Security::applySecurityHeaders(true);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// 1. Webhook Verification (GET request by Meta Developers)
if ($method === 'GET') {
    $hubMode = $_GET['hub_mode'] ?? '';
    $hubToken = $_GET['hub_verify_token'] ?? '';
    $hubChallenge = $_GET['hub_challenge'] ?? '';
    $expectedToken = Settings::get('webhook_verify_token', 'social_boost_secure_token_2026');

    // Timing-safe token verification
    if ($hubMode === 'subscribe' && hash_equals($expectedToken, $hubToken)) {
        http_response_code(200);
        // Echo challenge directly (Meta requires raw challenge)
        echo Security::sanitizeString($hubChallenge, 200);
        exit;
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Token de verificación incorrecto o no autorizado'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// 2. Incoming Event Ingestion (POST request by Meta Graph Webhook)
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    
    // Rate limit webhook ingestion (120 events / minute)
    Security::requireRateLimit('webhook_ingest', 120, 60);

    // Validate Meta Signature if App Secret is configured
    $metaAppSecret = Settings::get('meta_app_secret', '');
    $signatureHeader = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

    if (!empty($metaAppSecret)) {
        if (!Security::validateMetaWebhookSignature($rawInput, $signatureHeader, $metaAppSecret)) {
            http_response_code(401);
            echo json_encode(['error' => 'Firma criptográfica HMAC inválida'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    $data = json_decode($rawInput, true);

    if (!empty($data) && isset($data['entry']) && is_array($data['entry'])) {
        $pdo = Database::getConnection();
        $isAutopilot = Settings::get('autopilot_enabled', '0') === '1';
        $minAutopilotScore = Security::sanitizeInt(Settings::get('autopilot_min_score', 60), 0, 100, 60);

        foreach ($data['entry'] as $entry) {
            // Check Facebook Page changes
            if (isset($entry['changes']) && is_array($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    if (($change['field'] ?? '') === 'feed' && isset($change['value']['item']) && $change['value']['item'] === 'comment') {
                        $val = $change['value'];
                        if (($val['verb'] ?? '') === 'add') {
                            $commentId = Security::sanitizeString($val['comment_id'] ?? '', 100);
                            $message = Security::sanitizeString($val['message'] ?? '', 2000);
                            $senderName = Security::sanitizeString($val['from']['name'] ?? 'Usuario Facebook', 80);
                            $senderId = preg_replace('/[^0-9]/', '', $val['from']['id'] ?? '');
                            $postId = 2; // Default to Facebook post

                            if (empty($commentId) || empty($message)) {
                                continue;
                            }

                            $entryPageId = $entry['id'] ?? '';
                            $uStmt = $pdo->prepare("SELECT user_id FROM accounts WHERE page_id = :pid LIMIT 1");
                            $uStmt->execute([':pid' => $entryPageId]);
                            $uRow = $uStmt->fetch();
                            $targetUserId = $uRow ? (int)$uRow['user_id'] : 1;

                            // Analyze with AI Agent
                            $analysis = AiAgentService::analyzeComment($message, 'Publicación de Facebook', 0, $targetUserId);

                            $stmt = $pdo->prepare("
                                INSERT INTO comments (
                                    user_id, post_id, platform, external_comment_id, author_name, author_handle,
                                    author_avatar, comment_text, sentiment, intent, highlight_score,
                                    is_highlighted, highlight_reason, likes_count, status
                                ) VALUES (
                                    :user_id, :post_id, 'facebook', :ext_id, :author_name, :author_handle,
                                    :author_avatar, :comment_text, :sentiment, :intent, :highlight_score,
                                    :is_highlighted, :highlight_reason, 0, 'pending'
                                )
                            ");
                            $stmt->execute([
                                ':user_id' => $targetUserId,
                                ':post_id' => $postId,
                                ':ext_id' => $commentId,
                                ':author_name' => $senderName,
                                ':author_handle' => 'fb_' . $senderId,
                                ':author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($senderName) . '&background=1877f2&color=fff',
                                ':comment_text' => $message,
                                ':sentiment' => $analysis['sentiment'],
                                ':intent' => $analysis['intent'],
                                ':highlight_score' => $analysis['highlight_score'],
                                ':is_highlighted' => $analysis['is_highlighted'],
                                ':highlight_reason' => $analysis['highlight_reason']
                            ]);
                            $newDbId = (int)$pdo->lastInsertId();

                            // Autopilot execution if enabled and score meets requirement
                            if ($isAutopilot && $analysis['highlight_score'] >= $minAutopilotScore && $newDbId > 0) {
                                $replies = AiAgentService::generateReplies($senderName, $message, 'facebook');
                                $chosen = ($analysis['sentiment'] === 'lead') ? $replies['conversion'] : $replies['engagement'];
                                
                                MetaApiService::postReplyToMeta($newDbId, $chosen);
                                
                                $stmtRep = $pdo->prepare("
                                    INSERT INTO replies (comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform) 
                                    VALUES (:cid, :text, 'autopilot', 'auto', 'auto', 1)
                                ");
                                $stmtRep->execute([':cid' => $newDbId, ':text' => $chosen]);
                                
                                $stmtUp = $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id");
                                $stmtUp->execute([':id' => $newDbId]);
                            }
                        }
                    }
                }
            }
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'EVENT_RECEIVED']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método no soportado']);
