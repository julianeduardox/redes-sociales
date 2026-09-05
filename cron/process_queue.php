<?php
/**
 * Asynchronous Background Webhook Queue Worker
 * Execution: CLI (php cron/process_queue.php) or Webcron with Secret Token
 * 
 * Responsibilities:
 * 1. Reads pending webhook payloads from webhook_queue
 * 2. Deduplicates comments and resolves tenant/account ownership
 * 3. Executes AI Intent Classification & Sentiment Analysis
 * 4. Ingests comments into comments table
 * 5. Executes Autopilot (AI Reply Generation & Meta Graph Publication) if enabled
 * 6. Handles retries, error logging and automatic queue pruning
 */

// Allow CLI execution or authorized webcron
$isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
if (!$isCli) {
    require_once __DIR__ . '/../config/settings.php';
    require_once __DIR__ . '/../config/security.php';
    
    $secretKey = $_GET['key'] ?? '';
    $configuredSecret = Settings::get('cron_secret_key', 'cron_secure_token_2026');
    
    if (empty($secretKey) || !hash_equals($configuredSecret, $secretKey)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Acceso no autorizado al cron worker.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../services/CacheService.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/MetaApiService.php';

$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');

function cliLog(string $msg, string $type = 'info'): void {
    global $isCli;
    $time = date('H:i:s');
    $prefix = "[$time]";
    if ($isCli) {
        $colors = [
            'info' => "\033[0;36m",
            'success' => "\033[0;32m",
            'warn' => "\033[0;33m",
            'error' => "\033[0;31m",
            'reset' => "\033[0m"
        ];
        $c = $colors[$type] ?? $colors['info'];
        $r = $colors['reset'];
        echo "{$prefix} {$c}{$msg}{$r}\n";
    }
}

cliLog("🚀 Iniciando Worker de Cola de Webhooks...", 'info');

$pdo = Database::getConnection();

// 1. Fetch pending items from webhook_queue (Batch limit 50 per minute)
$batchLimit = 50;
$stmtFetch = $pdo->prepare("
    SELECT * FROM webhook_queue 
    WHERE status = 'pending' AND attempts < 3
    ORDER BY id ASC 
    LIMIT :limit
");
$stmtFetch->bindValue(':limit', $batchLimit, PDO::PARAM_INT);
$stmtFetch->execute();
$queueItems = $stmtFetch->fetchAll();

$totalFound = count($queueItems);
cliLog("📦 Eventos pendientes encontrados en cola: {$totalFound}", 'info');

if ($totalFound === 0) {
    cliLog("✨ Cola vacía. No hay eventos pendientes por procesar.", 'success');
    
    // Prune old processed items (older than 7 days)
    $pdo->exec("DELETE FROM webhook_queue WHERE status = 'processed' AND created_at < datetime('now', '-7 days')");
    
    if (!$isCli) {
        echo json_encode([
            'success' => true,
            'processed_count' => 0,
            'message' => 'Cola vacía.'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$processedCount = 0;
$commentsIngested = 0;
$repliesPosted = 0;
$failedCount = 0;

foreach ($queueItems as $item) {
    $queueId = (int)$item['id'];
    $rawPayload = $item['payload'];

    // Mark item as processing atomically
    $stmtLock = $pdo->prepare("UPDATE webhook_queue SET status = 'processing' WHERE id = :id AND status = 'pending'");
    $stmtLock->execute([':id' => $queueId]);
    if ($stmtLock->rowCount() === 0) {
        // Already taken by another parallel worker
        continue;
    }

    try {
        $payload = json_decode($rawPayload, true);
        if (!is_array($payload) || empty($payload['entry'])) {
            // Malformed or empty payload
            $stmtUp = $pdo->prepare("UPDATE webhook_queue SET status = 'processed', error_message = 'Payload sin entradas válidas', processed_at = CURRENT_TIMESTAMP WHERE id = :id");
            $stmtUp->execute([':id' => $queueId]);
            $processedCount++;
            continue;
        }

        foreach ($payload['entry'] as $entry) {
            $entryPageId = Security::sanitizeString($entry['id'] ?? '', 100);

            // Determine target user from accounts table (Cached in memory)
            $targetUserId = CacheService::getUserIdByPageId($entryPageId, $pdo);

            $isAutopilot = Settings::get('autopilot_enabled', '0', $targetUserId) === '1';
            $minAutopilotScore = Security::sanitizeInt(Settings::get('autopilot_min_score', 60, $targetUserId), 0, 100, 60);

            // 1. Process Facebook Page changes
            if (isset($entry['changes']) && is_array($entry['changes'])) {
                foreach ($entry['changes'] as $change) {
                    $field = $change['field'] ?? '';
                    $val = $change['value'] ?? [];

                    // CASE A: Facebook Page Feed Comment
                    if ($field === 'feed' && ($val['item'] ?? '') === 'comment' && ($val['verb'] ?? '') === 'add') {
                        $commentId = Security::sanitizeString($val['comment_id'] ?? '', 100);
                        $message = Security::sanitizeString($val['message'] ?? '', 2000);
                        $senderName = Security::sanitizeString($val['from']['name'] ?? 'Usuario Facebook', 80);
                        $senderId = preg_replace('/[^0-9]/', '', $val['from']['id'] ?? '');
                        $externalPostId = Security::sanitizeString($val['post_id'] ?? '', 100);

                        if (empty($commentId) || empty($message)) {
                            continue;
                        }

                        // Deduplication check
                        $checkStmt = $pdo->prepare("SELECT id FROM comments WHERE external_comment_id = :ext_id AND user_id = :uid LIMIT 1");
                        $checkStmt->execute([':ext_id' => $commentId, ':uid' => $targetUserId]);
                        if ($checkStmt->fetch()) {
                            cliLog("⏩ Comentario Facebook ya registrado previamente [ID: {$commentId}]", 'info');
                            continue;
                        }

                        // Find or link Post
                        $postId = 2; // Default fallback post
                        if (!empty($externalPostId)) {
                            $pStmt = $pdo->prepare("SELECT id, caption FROM posts WHERE external_post_id = :p_ext AND user_id = :uid LIMIT 1");
                            $pStmt->execute([':p_ext' => $externalPostId, ':uid' => $targetUserId]);
                            $pRow = $pStmt->fetch();
                            if ($pRow) {
                                $postId = (int)$pRow['id'];
                                $postCaption = $pRow['caption'];
                            } else {
                                $postCaption = 'Publicación en Página de Facebook';
                            }
                        } else {
                            $postCaption = 'Publicación en Página de Facebook';
                        }

                        // Analyze with AI Engine
                        $analysis = AiAgentService::analyzeComment($message, $postCaption, 0);

                        $insStmt = $pdo->prepare("
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
                        $insStmt->execute([
                            ':user_id' => $targetUserId,
                            ':post_id' => $postId,
                            ':ext_id' => $commentId,
                            ':author_name' => $senderName,
                            ':author_handle' => 'fb_' . ($senderId ?: substr(md5($senderName), 0, 8)),
                            ':author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($senderName) . '&background=1877f2&color=fff',
                            ':comment_text' => $message,
                            ':sentiment' => $analysis['sentiment'],
                            ':intent' => $analysis['intent'],
                            ':highlight_score' => $analysis['highlight_score'],
                            ':is_highlighted' => $analysis['is_highlighted'],
                            ':highlight_reason' => $analysis['highlight_reason']
                        ]);
                        $newDbId = (int)$pdo->lastInsertId();
                        $commentsIngested++;
                        cliLog("💬 Ingerido comentario Facebook: \"{$message}\" [Score: {$analysis['highlight_score']}]", 'success');

                        // Execute Autopilot if enabled
                        if ($isAutopilot && $analysis['highlight_score'] >= $minAutopilotScore && $newDbId > 0) {
                            $replies = AiAgentService::generateReplies($senderName, $message, 'facebook', $postCaption);
                            
                            $chosenVariant = 'engagement';
                            if ($analysis['sentiment'] === 'lead' || str_starts_with($analysis['intent'], 'lead_')) {
                                $chosenVariant = 'conversion';
                            } elseif ($analysis['sentiment'] === 'urgent' || $analysis['intent'] === 'customer_support') {
                                $chosenVariant = 'support';
                            }
                            $chosenReply = $replies[$chosenVariant] ?? $replies['engagement'];

                            MetaApiService::postReplyToMeta($newDbId, $chosenReply);

                            $stmtRep = $pdo->prepare("
                                INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                                VALUES (:uid, :cid, :text, 'autopilot', 'auto_selected', :variant, 1)
                            ");
                            $stmtRep->execute([
                                ':uid' => $targetUserId,
                                ':cid' => $newDbId,
                                ':text' => $chosenReply,
                                ':variant' => $chosenVariant
                            ]);

                            $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id")->execute([':id' => $newDbId]);
                            $repliesPosted++;
                            cliLog("🤖 Autopilot publicó respuesta a Facebook para: {$senderName}", 'success');
                        }
                    }

                    // CASE B: Instagram Comments Webhook
                    if (($field === 'comments' || $field === 'live_comments') && isset($val['id'])) {
                        $commentId = Security::sanitizeString($val['id'] ?? '', 100);
                        $message = Security::sanitizeString($val['text'] ?? ($val['message'] ?? ''), 2000);
                        $senderUsername = Security::sanitizeString($val['from']['username'] ?? ($val['username'] ?? 'usuario_ig'), 80);
                        $mediaId = Security::sanitizeString($val['media']['id'] ?? ($val['post_id'] ?? ''), 100);

                        if (empty($commentId) || empty($message)) {
                            continue;
                        }

                        // Deduplication
                        $checkStmt = $pdo->prepare("SELECT id FROM comments WHERE external_comment_id = :ext_id AND user_id = :uid LIMIT 1");
                        $checkStmt->execute([':ext_id' => $commentId, ':uid' => $targetUserId]);
                        if ($checkStmt->fetch()) {
                            cliLog("⏩ Comentario Instagram ya registrado previamente [ID: {$commentId}]", 'info');
                            continue;
                        }

                        // Find post or fallback
                        $postId = 1;
                        $postCaption = 'Publicación de Instagram';
                        if (!empty($mediaId)) {
                            $pStmt = $pdo->prepare("SELECT id, caption FROM posts WHERE external_post_id = :m_id AND user_id = :uid LIMIT 1");
                            $pStmt->execute([':m_id' => $mediaId, ':uid' => $targetUserId]);
                            $pRow = $pStmt->fetch();
                            if ($pRow) {
                                $postId = (int)$pRow['id'];
                                $postCaption = $pRow['caption'];
                            }
                        }

                        // Analyze with AI Engine
                        $analysis = AiAgentService::analyzeComment($message, $postCaption, 0);

                        $insStmt = $pdo->prepare("
                            INSERT INTO comments (
                                user_id, post_id, platform, external_comment_id, author_name, author_handle,
                                author_avatar, comment_text, sentiment, intent, highlight_score,
                                is_highlighted, highlight_reason, likes_count, status
                            ) VALUES (
                                :user_id, :post_id, 'instagram', :ext_id, :author_name, :author_handle,
                                :author_avatar, :comment_text, :sentiment, :intent, :highlight_score,
                                :is_highlighted, :highlight_reason, 0, 'pending'
                            )
                        ");
                        $insStmt->execute([
                            ':user_id' => $targetUserId,
                            ':post_id' => $postId,
                            ':ext_id' => $commentId,
                            ':author_name' => $senderUsername,
                            ':author_handle' => '@' . $senderUsername,
                            ':author_avatar' => 'https://ui-avatars.com/api/?name=' . urlencode($senderUsername) . '&background=6366f1&color=fff',
                            ':comment_text' => $message,
                            ':sentiment' => $analysis['sentiment'],
                            ':intent' => $analysis['intent'],
                            ':highlight_score' => $analysis['highlight_score'],
                            ':is_highlighted' => $analysis['is_highlighted'],
                            ':highlight_reason' => $analysis['highlight_reason']
                        ]);
                        $newDbId = (int)$pdo->lastInsertId();
                        $commentsIngested++;
                        cliLog("💬 Ingerido comentario Instagram: \"{$message}\" [Score: {$analysis['highlight_score']}]", 'success');

                        // Execute Autopilot if enabled
                        if ($isAutopilot && $analysis['highlight_score'] >= $minAutopilotScore && $newDbId > 0) {
                            $replies = AiAgentService::generateReplies($senderUsername, $message, 'instagram', $postCaption);
                            
                            $chosenVariant = 'engagement';
                            if ($analysis['sentiment'] === 'lead' || str_starts_with($analysis['intent'], 'lead_')) {
                                $chosenVariant = 'conversion';
                            } elseif ($analysis['sentiment'] === 'urgent' || $analysis['intent'] === 'customer_support') {
                                $chosenVariant = 'support';
                            }
                            $chosenReply = $replies[$chosenVariant] ?? $replies['engagement'];

                            MetaApiService::postReplyToMeta($newDbId, $chosenReply);

                            $stmtRep = $pdo->prepare("
                                INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                                VALUES (:uid, :cid, :text, 'autopilot', 'auto_selected', :variant, 1)
                            ");
                            $stmtRep->execute([
                                ':uid' => $targetUserId,
                                ':cid' => $newDbId,
                                ':text' => $chosenReply,
                                ':variant' => $chosenVariant
                            ]);

                            $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id")->execute([':id' => $newDbId]);
                            $repliesPosted++;
                            cliLog("🤖 Autopilot publicó respuesta a Instagram para: @{$senderUsername}", 'success');
                        }
                    }
                }
            }
        }

        // Mark queue item as successfully processed
        $stmtUp = $pdo->prepare("UPDATE webhook_queue SET status = 'processed', error_message = NULL, processed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmtUp->execute([':id' => $queueId]);
        $processedCount++;

    } catch (Throwable $e) {
        $failedCount++;
        $errMsg = $e->getMessage();
        cliLog("❌ Error procesando evento ID {$queueId}: {$errMsg}", 'error');
        
        $stmtFail = $pdo->prepare("
            UPDATE webhook_queue 
            SET attempts = attempts + 1,
                status = CASE WHEN attempts + 1 >= 3 THEN 'failed' ELSE 'pending' END,
                error_message = :err,
                processed_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        $stmtFail->execute([':err' => $errMsg, ':id' => $queueId]);
    }
}

// 2. Prune old processed items (older than 7 days)
$pdo->exec("DELETE FROM webhook_queue WHERE status = 'processed' AND created_at < datetime('now', '-7 days')");

$elapsed = round((microtime(true) - $startTime) * 1000, 2);
cliLog("🏁 Worker finalizado en {$elapsed} ms. Procesados: {$processedCount} | Comentarios: {$commentsIngested} | Autopilot: {$repliesPosted} | Fallidos: {$failedCount}", 'success');

if (!$isCli) {
    echo json_encode([
        'success' => true,
        'processed_events' => $processedCount,
        'comments_ingested' => $commentsIngested,
        'autopilot_replies' => $repliesPosted,
        'failed_events' => $failedCount,
        'execution_time_ms' => $elapsed,
        'timestamp' => $timestamp
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
