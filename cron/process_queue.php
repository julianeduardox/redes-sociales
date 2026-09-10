<?php
/**
 * Asynchronous Background Webhook Queue Worker
 * Execution: CLI (php cron/process_queue.php) or Webcron with Secret Token
 * Can also be included as a library via define('PROCESS_QUEUE_LIB_ONLY', true);
 * 
 * Responsibilities:
 * 1. Reads pending webhook payloads from webhook_queue
 * 2. Deduplicates comments and resolves tenant/account ownership
 * 3. Executes AI Intent Classification & Sentiment Analysis
 * 4. Ingests comments into comments table
 * 5. Executes Autopilot (AI Reply Generation & Meta Graph Publication) if enabled
 * 6. Handles retries, error logging and automatic queue pruning
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../services/CacheService.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/MetaApiService.php';

function cliLog(string $msg, string $type = 'info', bool $silent = false): void {
    if ($silent) return;
    $isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
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

/**
 * Process pending items in webhook_queue
 *
 * @param PDO $pdo Database connection
 * @param int $batchLimit Maximum number of events to process
 * @param int|null $specificQueueId If provided, processes ONLY this queue item
 * @param bool $silent If true, suppresses CLI terminal logging
 * @return array Processing summary statistics
 */
function processWebhookQueue(PDO $pdo, int $batchLimit = 50, ?int $specificQueueId = null, bool $silent = false): array {
    $startTime = microtime(true);
    $timestamp = date('Y-m-d H:i:s');

    cliLog("🚀 Iniciando Worker de Cola de Webhooks...", 'info', $silent);

    // 1. Fetch pending items from webhook_queue
    if ($specificQueueId !== null && $specificQueueId > 0) {
        $stmtFetch = $pdo->prepare("
            SELECT * FROM webhook_queue 
            WHERE id = :qid AND status = 'pending' AND attempts < 3
            LIMIT 1
        ");
        $stmtFetch->execute([':qid' => $specificQueueId]);
    } else {
        $stmtFetch = $pdo->prepare("
            SELECT * FROM webhook_queue 
            WHERE status = 'pending' AND attempts < 3
            ORDER BY id ASC 
            LIMIT :limit
        ");
        $stmtFetch->bindValue(':limit', $batchLimit, PDO::PARAM_INT);
        $stmtFetch->execute();
    }
    $queueItems = $stmtFetch->fetchAll();
    $totalFound = count($queueItems);

    cliLog("📦 Eventos pendientes encontrados en cola: {$totalFound}", 'info', $silent);

    if ($totalFound === 0) {
        cliLog("✨ Cola vacía. No hay eventos pendientes por procesar.", 'success', $silent);
        
        // Prune old processed items (older than 7 days)
        $pdo->exec("DELETE FROM webhook_queue WHERE status = 'processed' AND created_at < datetime('now', '-7 days')");
        
        return [
            'success' => true,
            'total_found' => 0,
            'processed_events' => 0,
            'comments_ingested' => 0,
            'autopilot_replies' => 0,
            'failed_events' => 0,
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'timestamp' => $timestamp,
            'message' => 'Cola vacía.'
        ];
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
                $defaultBrandVoiceId = Database::ensureDefaultBrandVoice($pdo, $targetUserId);

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
                                cliLog("⏩ Comentario Facebook ya registrado previamente [ID: {$commentId}]", 'info', $silent);
                                continue;
                            }

                            // Find or link Post & Brand Voice (Foreign Key Safe)
                            $postId = null;
                            $brandVoiceId = $defaultBrandVoiceId;
                            $postCaption = 'Publicación en Página de Facebook';

                            if (!empty($externalPostId)) {
                                $pStmt = $pdo->prepare("
                                    SELECT p.id, p.caption, p.brand_voice_id, a.brand_voice_id as acc_bv_id 
                                    FROM posts p 
                                    LEFT JOIN accounts a ON p.account_id = a.id 
                                    WHERE p.external_post_id = :p_ext AND p.user_id = :uid 
                                    LIMIT 1
                                ");
                                $pStmt->execute([':p_ext' => $externalPostId, ':uid' => $targetUserId]);
                                $pRow = $pStmt->fetch();
                                if ($pRow) {
                                    $postId = (int)$pRow['id'];
                                    $postCaption = $pRow['caption'] ?: $postCaption;
                                    $brandVoiceId = !empty($pRow['brand_voice_id']) ? (int)$pRow['brand_voice_id'] : (!empty($pRow['acc_bv_id']) ? (int)$pRow['acc_bv_id'] : $defaultBrandVoiceId);
                                }
                            }

                            if ($postId === null) {
                                $fbFallback = $pdo->prepare("SELECT id, caption, brand_voice_id FROM posts WHERE user_id = :uid AND platform = 'facebook' ORDER BY id DESC LIMIT 1");
                                $fbFallback->execute([':uid' => $targetUserId]);
                                $fbRow = $fbFallback->fetch();
                                if ($fbRow) {
                                    $postId = (int)$fbRow['id'];
                                    $postCaption = $fbRow['caption'] ?: $postCaption;
                                    if (!empty($fbRow['brand_voice_id'])) $brandVoiceId = (int)$fbRow['brand_voice_id'];
                                } else {
                                    $insPost = $pdo->prepare("
                                        INSERT INTO posts (user_id, platform, external_post_id, caption, brand_voice_id, total_likes, total_comments, total_shares, impressions, reach, posted_at, last_synced_at)
                                        VALUES (:uid, 'facebook', :ext_id, :caption, :bvid, 0, 0, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                                    ");
                                    $insPost->execute([
                                        ':uid' => $targetUserId,
                                        ':ext_id' => !empty($externalPostId) ? $externalPostId : 'fb_post_' . time(),
                                        ':caption' => $postCaption,
                                        ':bvid' => $brandVoiceId
                                    ]);
                                    $postId = (int)$pdo->lastInsertId();
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
                            cliLog("💬 Ingerido comentario Facebook: \"{$message}\" [Score: {$analysis['highlight_score']}]", 'success', $silent);

                            // Execute Autopilot if enabled
                            if ($isAutopilot && $newDbId > 0) {
                                $suitability = AiAgentService::evaluateCommentSuitability($message);
                                if ($suitability['status'] === 'spam') {
                                    $pdo->prepare("UPDATE comments SET status = 'spam', sentiment = 'spam', highlight_reason = :reason WHERE id = :id AND user_id = :uid")
                                        ->execute([':reason' => $suitability['reason'], ':id' => $newDbId, ':uid' => $targetUserId]);
                                } elseif ($suitability['status'] === 'ignored') {
                                    $pdo->prepare("UPDATE comments SET status = 'ignored', highlight_reason = :reason WHERE id = :id AND user_id = :uid")
                                        ->execute([':reason' => $suitability['reason'], ':id' => $newDbId, ':uid' => $targetUserId]);
                                } else {
                                    $replies = AiAgentService::generateReplies($senderName, $message, 'facebook', $postCaption, '', [
                                        'user_id' => $targetUserId,
                                        'post_id' => $postId,
                                        'brand_voice_id' => $brandVoiceId,
                                        'reply_index' => $repliesPosted
                                    ]);
                                    
                                    $chosenVariant = 'engagement';
                                    if ($analysis['sentiment'] === 'lead' || str_starts_with($analysis['intent'], 'lead_')) {
                                        $chosenVariant = 'conversion';
                                    } elseif ($analysis['sentiment'] === 'urgent' || $analysis['intent'] === 'customer_support' || $analysis['intent'] === 'support') {
                                        $chosenVariant = 'support';
                                    }
                                    $chosenReply = $replies[$chosenVariant] ?? $replies['engagement'];

                                    $metaRes = MetaApiService::postReplyToMeta($newDbId, $chosenReply, $targetUserId);
                                    $isPosted = !empty($metaRes['success']) ? 1 : 0;

                                    $stmtRep = $pdo->prepare("
                                        INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                                        VALUES (:uid, :cid, :text, 'autopilot', 'auto_selected', :variant, :is_posted)
                                    ");
                                    $stmtRep->execute([
                                        ':uid' => $targetUserId,
                                        ':cid' => $newDbId,
                                        ':text' => $chosenReply,
                                        ':variant' => $chosenVariant,
                                        ':is_posted' => $isPosted
                                    ]);

                                    $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id AND user_id = :uid")->execute([':id' => $newDbId, ':uid' => $targetUserId]);
                                    $repliesPosted++;
                                    cliLog("🤖 Autopilot publicó respuesta a Facebook para: {$senderName}", 'success', $silent);
                                }
                            }
                        }

                        // CASE A2: Facebook Page New Post / Photo / Video Ingestion (Real-Time Feed Webhook)
                        $fbPostItems = ['status', 'photo', 'video', 'post', 'share', 'album'];
                        if ($field === 'feed' && in_array($val['item'] ?? '', $fbPostItems, true) && ($val['verb'] ?? '') === 'add') {
                            $externalPostId = Security::sanitizeString($val['post_id'] ?? ($val['id'] ?? ''), 100);
                            $postCaption = Security::sanitizeString($val['message'] ?? ($val['story'] ?? 'Publicación en Página de Facebook'), 2500);
                            $rawCreated = $val['created_time'] ?? null;
                            $postedAt = !empty($rawCreated) ? (is_numeric($rawCreated) ? date('Y-m-d H:i:s', (int)$rawCreated) : date('Y-m-d H:i:s', strtotime($rawCreated))) : date('Y-m-d H:i:s');
                            
                            $itemType = $val['item'] ?? 'status';
                            $mediaType = 'status';
                            if ($itemType === 'photo' || $itemType === 'album') $mediaType = 'image';
                            elseif ($itemType === 'video') $mediaType = 'video';

                            $mediaUrl = Security::sanitizeString($val['photos'][0] ?? ($val['link'] ?? ''), 500);
                            $permalink = Security::sanitizeString($val['link'] ?? "https://www.facebook.com/{$externalPostId}", 500);

                            if (!empty($externalPostId)) {
                                // Find connected account ID and brand voice
                                $accStmt = $pdo->prepare("SELECT id, brand_voice_id FROM accounts WHERE user_id = :uid AND page_id = :pid AND platform = 'facebook' LIMIT 1");
                                $accStmt->execute([':uid' => $targetUserId, ':pid' => $entryPageId]);
                                $accRow = $accStmt->fetch();
                                $fbAccountId = $accRow ? (int)$accRow['id'] : null;
                                $postBvId = !empty($accRow['brand_voice_id']) ? (int)$accRow['brand_voice_id'] : $defaultBrandVoiceId;

                                // Check if post already exists
                                $checkPostStmt = $pdo->prepare("SELECT id FROM posts WHERE external_post_id = :p_ext AND user_id = :uid LIMIT 1");
                                $checkPostStmt->execute([':p_ext' => $externalPostId, ':uid' => $targetUserId]);
                                $existingPostRow = $checkPostStmt->fetch();

                                if ($existingPostRow) {
                                    $pdo->prepare("
                                        UPDATE posts 
                                        SET caption = :caption, media_url = COALESCE(NULLIF(:media_url, ''), media_url),
                                            media_type = :media_type, last_synced_at = CURRENT_TIMESTAMP
                                        WHERE id = :id AND user_id = :uid
                                    ")->execute([
                                        ':caption' => $postCaption,
                                        ':media_url' => $mediaUrl,
                                        ':media_type' => $mediaType,
                                        ':id' => $existingPostRow['id'],
                                        ':uid' => $targetUserId
                                    ]);
                                    cliLog("🔄 Actualizada publicación de Facebook en tiempo real [ID: {$externalPostId}]", 'info', $silent);
                                } else {
                                    $pdo->prepare("
                                        INSERT INTO posts (
                                            user_id, account_id, brand_voice_id, platform, external_post_id,
                                            caption, media_url, media_type, permalink, total_likes, total_comments, total_shares,
                                            impressions, reach, saved_count, engagement_rate, posted_at, last_synced_at
                                        ) VALUES (
                                            :uid, :acc_id, :bvid, 'facebook', :ext_id,
                                            :caption, :media_url, :media_type, :permalink, 0, 0, 0,
                                            0, 0, 0, 0.0, :posted_at, CURRENT_TIMESTAMP
                                        )
                                    ")->execute([
                                        ':uid' => $targetUserId,
                                        ':acc_id' => $fbAccountId,
                                        ':bvid' => $postBvId,
                                        ':ext_id' => $externalPostId,
                                        ':caption' => $postCaption,
                                        ':media_url' => $mediaUrl,
                                        ':media_type' => $mediaType,
                                        ':permalink' => $permalink,
                                        ':posted_at' => $postedAt
                                    ]);
                                    cliLog("📸 Ingerida nueva publicación de Facebook en tiempo real [ID: {$externalPostId}]", 'success', $silent);
                                }
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
                                cliLog("⏩ Comentario Instagram ya registrado previamente [ID: {$commentId}]", 'info', $silent);
                                continue;
                            }

                            // Find post or fallback (Foreign Key Safe)
                            $postId = null;
                            $postCaption = 'Publicación de Instagram';
                            $brandVoiceId = $defaultBrandVoiceId;

                            if (!empty($mediaId)) {
                                $pStmt = $pdo->prepare("
                                    SELECT p.id, p.caption, p.brand_voice_id, a.brand_voice_id as acc_bv_id 
                                    FROM posts p 
                                    LEFT JOIN accounts a ON p.account_id = a.id 
                                    WHERE p.external_post_id = :m_id AND p.user_id = :uid 
                                    LIMIT 1
                                ");
                                $pStmt->execute([':m_id' => $mediaId, ':uid' => $targetUserId]);
                                $pRow = $pStmt->fetch();
                                if ($pRow) {
                                    $postId = (int)$pRow['id'];
                                    $postCaption = $pRow['caption'] ?: $postCaption;
                                    $brandVoiceId = !empty($pRow['brand_voice_id']) ? (int)$pRow['brand_voice_id'] : (!empty($pRow['acc_bv_id']) ? (int)$pRow['acc_bv_id'] : $defaultBrandVoiceId);
                                }
                            }

                            if ($postId === null) {
                                $igFallback = $pdo->prepare("SELECT id, caption, brand_voice_id FROM posts WHERE user_id = :uid AND platform = 'instagram' ORDER BY id DESC LIMIT 1");
                                $igFallback->execute([':uid' => $targetUserId]);
                                $igRow = $igFallback->fetch();
                                if ($igRow) {
                                    $postId = (int)$igRow['id'];
                                    $postCaption = $igRow['caption'] ?: $postCaption;
                                    if (!empty($igRow['brand_voice_id'])) $brandVoiceId = (int)$igRow['brand_voice_id'];
                                } else {
                                    $insPost = $pdo->prepare("
                                        INSERT INTO posts (user_id, platform, external_post_id, caption, brand_voice_id, total_likes, total_comments, total_shares, impressions, reach, posted_at, last_synced_at)
                                        VALUES (:uid, 'instagram', :ext_id, :caption, :bvid, 0, 0, 0, 0, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
                                    ");
                                    $insPost->execute([
                                        ':uid' => $targetUserId,
                                        ':ext_id' => !empty($mediaId) ? $mediaId : 'ig_post_' . time(),
                                        ':caption' => $postCaption,
                                        ':bvid' => $brandVoiceId
                                    ]);
                                    $postId = (int)$pdo->lastInsertId();
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
                                ':author_handle' => '@' . ltrim($senderUsername, '@'),
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
                            cliLog("💬 Ingerido comentario Instagram: \"{$message}\" [Score: {$analysis['highlight_score']}]", 'success', $silent);

                            // Execute Autopilot if enabled
                            if ($isAutopilot && $newDbId > 0) {
                                $suitability = AiAgentService::evaluateCommentSuitability($message);
                                if ($suitability['status'] === 'spam') {
                                    $pdo->prepare("UPDATE comments SET status = 'spam', sentiment = 'spam', highlight_reason = :reason WHERE id = :id AND user_id = :uid")
                                        ->execute([':reason' => $suitability['reason'], ':id' => $newDbId, ':uid' => $targetUserId]);
                                } elseif ($suitability['status'] === 'ignored') {
                                    $pdo->prepare("UPDATE comments SET status = 'ignored', highlight_reason = :reason WHERE id = :id AND user_id = :uid")
                                        ->execute([':reason' => $suitability['reason'], ':id' => $newDbId, ':uid' => $targetUserId]);
                                } else {
                                    $replies = AiAgentService::generateReplies($senderUsername, $message, 'instagram', $postCaption, '', [
                                        'user_id' => $targetUserId,
                                        'post_id' => $postId,
                                        'brand_voice_id' => $brandVoiceId,
                                        'reply_index' => $repliesPosted
                                    ]);
                                    
                                    $chosenVariant = 'engagement';
                                    if ($analysis['sentiment'] === 'lead' || str_starts_with($analysis['intent'], 'lead_')) {
                                        $chosenVariant = 'conversion';
                                    } elseif ($analysis['sentiment'] === 'urgent' || $analysis['intent'] === 'customer_support' || $analysis['intent'] === 'support') {
                                        $chosenVariant = 'support';
                                    }
                                    $chosenReply = $replies[$chosenVariant] ?? $replies['engagement'];

                                    $metaRes = MetaApiService::postReplyToMeta($newDbId, $chosenReply, $targetUserId);
                                    $isPosted = !empty($metaRes['success']) ? 1 : 0;

                                    $stmtRep = $pdo->prepare("
                                        INSERT INTO replies (user_id, comment_id, reply_text, reply_type, tone_used, variant_type, is_posted_to_platform)
                                        VALUES (:uid, :cid, :text, 'autopilot', 'auto_selected', :variant, :is_posted)
                                    ");
                                    $stmtRep->execute([
                                        ':uid' => $targetUserId,
                                        ':cid' => $newDbId,
                                        ':text' => $chosenReply,
                                        ':variant' => $chosenVariant,
                                        ':is_posted' => $isPosted
                                    ]);

                                    $pdo->prepare("UPDATE comments SET status = 'replied' WHERE id = :id AND user_id = :uid")->execute([':id' => $newDbId, ':uid' => $targetUserId]);
                                    $repliesPosted++;
                                    cliLog("🤖 Autopilot publicó respuesta a Instagram para: @{$senderUsername}", 'success', $silent);
                                }
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
            cliLog("❌ Error procesando evento ID {$queueId}: {$errMsg}", 'error', $silent);
            
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
    cliLog("🏁 Worker finalizado en {$elapsed} ms. Procesados: {$processedCount} | Comentarios: {$commentsIngested} | Autopilot: {$repliesPosted} | Fallidos: {$failedCount}", 'success', $silent);

    return [
        'success' => true,
        'total_found' => $totalFound,
        'processed_events' => $processedCount,
        'comments_ingested' => $commentsIngested,
        'autopilot_replies' => $repliesPosted,
        'failed_events' => $failedCount,
        'execution_time_ms' => $elapsed,
        'timestamp' => $timestamp
    ];
}

// Auto-run if executed directly via CLI or Webcron (NOT when required as library)
if (!defined('PROCESS_QUEUE_LIB_ONLY')) {
    $isCli = (php_sapi_name() === 'cli' || defined('STDIN'));
    if (!$isCli) {
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

    $pdo = Database::getConnection();
    $result = processWebhookQueue($pdo, 50, null, !$isCli);

    // ─── Trends Agent: Sincronización automática cada 6 horas ──────────────
    try {
        $currentHour = (int)date('G'); // 0-23
        if ($currentHour % 6 === 0) {
            require_once __DIR__ . '/../services/TrendsAgentService.php';
            cliLog("🔥 Iniciando sincronización de tendencias (hora {$currentHour})...", 'info', !$isCli);
            TrendsAgentService::syncAllActiveUsers();
            cliLog("✅ Tendencias sincronizadas correctamente.", 'success', !$isCli);
            $result['trends_sync'] = 'completed';
        } else {
            $result['trends_sync'] = 'skipped (next at hour ' . ((intdiv($currentHour, 6) + 1) * 6) . ':00)';
        }
    } catch (Throwable $trendsEx) {
        error_log("Cron TrendsAgent error: " . $trendsEx->getMessage());
        cliLog("⚠️ Error en sync de tendencias: " . $trendsEx->getMessage(), 'warn', !$isCli);
        $result['trends_sync'] = 'error: ' . $trendsEx->getMessage();
    }

    if (!$isCli) {
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}

