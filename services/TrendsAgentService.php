<?php
/**
 * TrendsAgentService - Agente de Tendencias de Nicho
 *
 * Monitorea hashtags relacionados al nicho del usuario en Instagram,
 * recolecta posts en tendencia, calcula engagement scores y genera
 * un ranking inteligente con Top 3 AI Picks.
 *
 * Usa Instagram Hashtag Search API (Graph API v19+) - dentro de políticas de Meta.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

class TrendsAgentService {
    private const GRAPH_API_VERSION  = 'v19.0';
    private const BASE_URL           = 'https://graph.facebook.com/' . self::GRAPH_API_VERSION;
    private const HISTORY_DAYS       = 30;
    private const MAX_HASHTAGS_USER  = 10;
    private const API_TIMEOUT        = 15;

    // ==========================================================================
    // GESTIÓN DE NICHOS
    // ==========================================================================

    public static function getNiches(int $userId): array {
        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, hashtag, display_name, platform, ig_hashtag_id,
                       is_active, last_synced_at, created_at
                FROM trend_niches
                WHERE user_id = ? AND is_active = 1
                ORDER BY created_at ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log("TrendsAgent getNiches: " . $e->getMessage());
            return [];
        }
    }

    public static function addNiche(int $userId, string $hashtag, string $platform = 'instagram'): array {
        try {
            $hashtag = strtolower(trim(ltrim($hashtag, '#')));
            $hashtag = preg_replace('/[^a-z0-9_áéíóúüñ]/u', '', $hashtag);
            if (empty($hashtag)) return ['success' => false, 'error' => 'Hashtag inválido'];

            $pdo      = Database::getConnection();
            $cntStmt  = $pdo->prepare("SELECT COUNT(*) FROM trend_niches WHERE user_id = ? AND is_active = 1");
            $cntStmt->execute([$userId]);
            if ((int)$cntStmt->fetchColumn() >= self::MAX_HASHTAGS_USER) {
                return ['success' => false, 'error' => 'Límite de ' . self::MAX_HASHTAGS_USER . ' hashtags alcanzado'];
            }

            $exStmt = $pdo->prepare("SELECT id FROM trend_niches WHERE user_id = ? AND hashtag = ? AND platform = ?");
            $exStmt->execute([$userId, $hashtag, $platform]);
            $existing = $exStmt->fetch();
            if ($existing) {
                $pdo->prepare("UPDATE trend_niches SET is_active = 1 WHERE id = ?")->execute([$existing['id']]);
                return ['success' => true, 'message' => "Hashtag #$hashtag reactivado", 'niche_id' => (int)$existing['id']];
            }

            $displayName = '#' . ucfirst($hashtag);
            $insStmt = $pdo->prepare("INSERT INTO trend_niches (user_id, hashtag, display_name, platform) VALUES (?, ?, ?, ?)");
            $insStmt->execute([$userId, $hashtag, $displayName, $platform]);

            return [
                'success'      => true,
                'message'      => "Hashtag #$hashtag agregado",
                'niche_id'     => (int)$pdo->lastInsertId(),
                'hashtag'      => $hashtag,
                'display_name' => $displayName,
            ];
        } catch (Throwable $e) {
            error_log("TrendsAgent addNiche: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al agregar hashtag'];
        }
    }

    public static function removeNiche(int $userId, int $nicheId): array {
        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE trend_niches SET is_active = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$nicheId, $userId]);
            return $stmt->rowCount()
                ? ['success' => true,  'message' => 'Hashtag eliminado']
                : ['success' => false, 'error'   => 'No encontrado o sin permisos'];
        } catch (Throwable $e) {
            error_log("TrendsAgent removeNiche: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar'];
        }
    }

    // ==========================================================================
    // META GRAPH API — HASHTAG SEARCH
    // ==========================================================================

    public static function discoverHashtagId(string $hashtag, string $igUserId, string $token): ?string {
        $url  = self::BASE_URL . '/' . $igUserId . '/ig_hashtag_search'
              . '?q=' . urlencode($hashtag)
              . '&access_token=' . urlencode($token);
        $data = self::makeGetRequest($url);
        return !empty($data['data'][0]['id']) ? (string)$data['data'][0]['id'] : null;
    }

    public static function fetchTopPosts(string $hashtagId, string $igUserId, string $token): array {
        $fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count';
        $url    = self::BASE_URL . '/' . $hashtagId . '/top_media'
                . '?user_id=' . urlencode($igUserId)
                . '&fields=' . $fields
                . '&limit=30'
                . '&access_token=' . urlencode($token);
        return self::makeGetRequest($url)['data'] ?? [];
    }

    public static function fetchRecentPosts(string $hashtagId, string $igUserId, string $token): array {
        $fields = 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,like_count,comments_count';
        $url    = self::BASE_URL . '/' . $hashtagId . '/recent_media'
                . '?user_id=' . urlencode($igUserId)
                . '&fields=' . $fields
                . '&limit=20'
                . '&access_token=' . urlencode($token);
        return self::makeGetRequest($url)['data'] ?? [];
    }

    // ==========================================================================
    // SCORING
    // ==========================================================================

    public static function calculateEngagementScore(array $post): float {
        $likes    = (int)($post['like_count'] ?? $post['likes_count'] ?? 0);
        $comments = (int)($post['comments_count'] ?? 0);
        $shares   = (int)($post['shares_count'] ?? 0);
        $saves    = (int)($post['saves_count'] ?? 0);
        $reach    = (int)($post['reach_count'] ?? 0);
        $weighted = $likes + ($comments * 3) + ($shares * 5) + ($saves * 4);
        $base     = max($reach, $likes * 3, 1);
        return round(($weighted / $base) * 100, 2);
    }

    // ==========================================================================
    // SINCRONIZACIÓN
    // ==========================================================================

    public static function syncNicheTrends(int $userId, int $nicheId): array {
        try {
            $pdo      = Database::getConnection();
            $nStmt    = $pdo->prepare("SELECT * FROM trend_niches WHERE id = ? AND user_id = ? AND is_active = 1");
            $nStmt->execute([$nicheId, $userId]);
            $niche    = $nStmt->fetch();
            if (!$niche) return ['success' => false, 'error' => 'Nicho no encontrado'];

            $creds = self::getUserCredentials($userId);
            if (!$creds) return ['success' => false, 'error' => 'Sin cuenta Instagram conectada', 'no_token' => true];

            $token    = $creds['token'];
            $igUserId = $creds['ig_user_id'];
            $hashtag  = $niche['hashtag'];

            $hashtagId = $niche['ig_hashtag_id'];
            if (empty($hashtagId)) {
                $hashtagId = self::discoverHashtagId($hashtag, $igUserId, $token);
                if ($hashtagId) {
                    $pdo->prepare("UPDATE trend_niches SET ig_hashtag_id = ? WHERE id = ?")->execute([$hashtagId, $nicheId]);
                } else {
                    return ['success' => false, 'error' => "No se encontró el hashtag #$hashtag en Instagram"];
                }
            }

            $topPosts    = self::fetchTopPosts($hashtagId, $igUserId, $token);
            $recentPosts = self::fetchRecentPosts($hashtagId, $igUserId, $token);

            $allPosts = [];
            foreach (array_merge($topPosts, $recentPosts) as $p) {
                if (!empty($p['id'])) $allPosts[$p['id']] = $p;
            }

            $saved = 0;
            foreach ($allPosts as $post) {
                $mediaUrl    = $post['media_url'] ?? $post['thumbnail_url'] ?? null;
                $captionPrev = mb_substr($post['caption'] ?? '', 0, 300);
                $mediaType   = strtolower($post['media_type'] ?? 'IMAGE');
                $mediaType   = ($mediaType === 'carousel_album') ? 'carousel' : $mediaType;
                $likes       = (int)($post['like_count'] ?? 0);
                $comments    = (int)($post['comments_count'] ?? 0);
                $postedAt    = !empty($post['timestamp']) ? date('Y-m-d H:i:s', strtotime($post['timestamp'])) : null;
                $score       = self::calculateEngagementScore(['like_count' => $likes, 'comments_count' => $comments]);

                try {
                    $ins = $pdo->prepare("
                        INSERT INTO trend_posts
                            (user_id, niche_id, platform, external_post_id, caption_preview,
                             media_url, permalink, media_type, likes_count, comments_count,
                             engagement_score, posted_at, fetched_at)
                        VALUES (?, ?, 'instagram', ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                        ON CONFLICT(platform, external_post_id) DO UPDATE SET
                            likes_count      = excluded.likes_count,
                            comments_count   = excluded.comments_count,
                            engagement_score = excluded.engagement_score,
                            fetched_at       = CURRENT_TIMESTAMP
                    ");
                    $ins->execute([
                        $userId, $nicheId, $post['id'], $captionPrev,
                        $mediaUrl, $post['permalink'] ?? null, $mediaType,
                        $likes, $comments, $score, $postedAt
                    ]);
                    $saved++;
                } catch (Throwable $e) {
                    // Ignorar errores de inserción individual
                }
            }

            $pdo->prepare("UPDATE trend_niches SET last_synced_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$nicheId]);
            self::recalculateRanking($userId);
            self::selectAiTopPicks($userId);

            return [
                'success'     => true,
                'hashtag'     => '#' . $hashtag,
                'posts_found' => count($allPosts),
                'posts_saved' => $saved,
            ];
        } catch (Throwable $e) {
            error_log("TrendsAgent syncNicheTrends: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function syncAllNiches(int $userId): array {
        $niches  = self::getNiches($userId);
        $results = [];
        foreach ($niches as $niche) {
            $results[] = array_merge(['hashtag' => $niche['hashtag']], self::syncNicheTrends($userId, (int)$niche['id']));
            usleep(500000);
        }
        self::cleanupOldTrends($userId);
        return ['success' => true, 'synced' => count($results), 'results' => $results];
    }

    public static function syncAllActiveUsers(): void {
        try {
            $pdo   = Database::getConnection();
            $users = $pdo->query("SELECT DISTINCT user_id FROM trend_niches WHERE is_active = 1")->fetchAll();
            foreach ($users as $u) {
                try { self::syncAllNiches((int)$u['user_id']); }
                catch (Throwable $e) { error_log("TrendsAgent cron user {$u['user_id']}: " . $e->getMessage()); }
            }
        } catch (Throwable $e) {
            error_log("TrendsAgent syncAllActiveUsers: " . $e->getMessage());
        }
    }

    // ==========================================================================
    // RANKING & AI PICKS
    // ==========================================================================

    private static function recalculateRanking(int $userId): void {
        try {
            $pdo   = Database::getConnection();
            $stmt  = $pdo->prepare("
                SELECT id FROM trend_posts
                WHERE user_id = ?
                ORDER BY engagement_score DESC
                LIMIT 100
            ");
            $stmt->execute([$userId]);
            $posts    = $stmt->fetchAll();
            $rankStmt = $pdo->prepare("UPDATE trend_posts SET trend_rank = ? WHERE id = ?");
            foreach ($posts as $rank => $post) {
                $rankStmt->execute([$rank + 1, $post['id']]);
            }
        } catch (Throwable $e) {
            error_log("TrendsAgent recalculateRanking: " . $e->getMessage());
        }
    }

    public static function selectAiTopPicks(int $userId): void {
        try {
            $pdo  = Database::getConnection();
            $pdo->prepare("UPDATE trend_posts SET ai_top_pick = 0, ai_pick_reason = NULL WHERE user_id = ?")->execute([$userId]);

            $stmt = $pdo->prepare("
                SELECT tp.id, tp.media_type, tp.engagement_score, tp.likes_count,
                       tp.comments_count, tp.caption_preview, tn.hashtag
                FROM trend_posts tp
                JOIN trend_niches tn ON tp.niche_id = tn.id
                WHERE tp.user_id = ?
                  AND tp.caption_preview IS NOT NULL AND tp.caption_preview != ''
                ORDER BY tp.engagement_score DESC
                LIMIT 20
            ");
            $stmt->execute([$userId]);
            $candidates = $stmt->fetchAll();

            $picks = [];
            $usedTypes = [];
            foreach ($candidates as $c) {
                if (count($picks) >= 3) break;
                $type = strtolower($c['media_type'] ?? 'image');
                if (count($picks) < 2 || !in_array($type, $usedTypes, true)) {
                    $picks[]     = $c;
                    $usedTypes[] = $type;
                }
            }
            if (count($picks) < 3 && count($candidates) >= 3) {
                $picks = array_slice($candidates, 0, 3);
            }

            $updStmt = $pdo->prepare("UPDATE trend_posts SET ai_top_pick = 1, ai_pick_reason = ? WHERE id = ?");
            foreach ($picks as $rank => $pick) {
                $updStmt->execute([self::generatePickReason($pick, $rank + 1), $pick['id']]);
            }
        } catch (Throwable $e) {
            error_log("TrendsAgent selectAiTopPicks: " . $e->getMessage());
        }
    }

    private static function generatePickReason(array $post, int $rank): string {
        $score    = $post['engagement_score'];
        $likes    = number_format((int)$post['likes_count']);
        $comments = number_format((int)$post['comments_count']);
        $type     = strtolower($post['media_type'] ?? 'image');
        $hashtag  = '#' . $post['hashtag'];
        $label    = match($type) { 'video' => 'Reel/Video', 'carousel', 'carousel_album' => 'Carrusel', default => 'Imagen' };
        $emojis   = ['🔥', '⚡', '💡'];
        return "{$emojis[$rank-1]} #{$rank} en {$hashtag} — {$label} con {$likes} likes, {$comments} comentarios. Engagement: {$score}%.";
    }

    // ==========================================================================
    // CONSULTA DE DATOS
    // ==========================================================================

    public static function getTrendingPosts(int $userId, ?int $nicheId = null, int $limit = 20): array {
        try {
            $pdo     = Database::getConnection();
            $params  = [$userId];
            $nFilter = '';
            if ($nicheId !== null) { $nFilter = ' AND tp.niche_id = ?'; $params[] = $nicheId; }
            $params[] = max(1, min(50, $limit));
            $stmt = $pdo->prepare("
                SELECT tp.id, tp.niche_id, tp.platform, tp.external_post_id,
                       tp.author_handle, tp.caption_preview, tp.media_url, tp.permalink,
                       tp.media_type, tp.likes_count, tp.comments_count, tp.shares_count,
                       tp.saves_count, tp.reach_count, tp.engagement_score, tp.trend_rank,
                       tp.ai_top_pick, tp.ai_pick_reason, tp.posted_at, tp.fetched_at,
                       tn.hashtag, tn.display_name AS niche_display_name
                FROM trend_posts tp
                JOIN trend_niches tn ON tp.niche_id = tn.id
                WHERE tp.user_id = ? {$nFilter}
                ORDER BY tp.ai_top_pick DESC, tp.engagement_score DESC
                LIMIT ?
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log("TrendsAgent getTrendingPosts: " . $e->getMessage());
            return [];
        }
    }

    public static function getAiTopPicks(int $userId): array {
        try {
            $pdo  = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT tp.*, tn.hashtag, tn.display_name AS niche_display_name
                FROM trend_posts tp
                JOIN trend_niches tn ON tp.niche_id = tn.id
                WHERE tp.user_id = ? AND tp.ai_top_pick = 1
                ORDER BY tp.engagement_score DESC
                LIMIT 3
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            error_log("TrendsAgent getAiTopPicks: " . $e->getMessage());
            return [];
        }
    }

    public static function generateTrendInsights(int $userId): array {
        try {
            $pdo = Database::getConnection();

            $mediaStmt = $pdo->prepare("
                SELECT media_type, AVG(engagement_score) AS avg_score, COUNT(*) AS count
                FROM trend_posts WHERE user_id = ?
                GROUP BY media_type ORDER BY avg_score DESC LIMIT 3
            ");
            $mediaStmt->execute([$userId]);
            $mediaTypes = $mediaStmt->fetchAll();

            $capStmt = $pdo->prepare("
                SELECT
                    CASE WHEN LENGTH(caption_preview)<50 THEN 'muy_corto'
                         WHEN LENGTH(caption_preview)<150 THEN 'corto'
                         WHEN LENGTH(caption_preview)<300 THEN 'medio'
                         ELSE 'largo' END AS cap_len,
                    AVG(engagement_score) AS avg_score, COUNT(*) AS count
                FROM trend_posts
                WHERE user_id = ? AND caption_preview IS NOT NULL AND caption_preview != ''
                GROUP BY cap_len ORDER BY avg_score DESC
            ");
            $capStmt->execute([$userId]);
            $captionLengths = $capStmt->fetchAll();

            $nicheStmt = $pdo->prepare("
                SELECT tn.hashtag, tn.display_name, AVG(tp.engagement_score) AS avg_score,
                       COUNT(tp.id) AS post_count, MAX(tp.likes_count) AS max_likes
                FROM trend_posts tp JOIN trend_niches tn ON tp.niche_id = tn.id
                WHERE tp.user_id = ?
                GROUP BY tn.id, tn.hashtag, tn.display_name ORDER BY avg_score DESC
            ");
            $nicheStmt->execute([$userId]);

            $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM trend_posts WHERE user_id = ?");
            $totalStmt->execute([$userId]);
            $lastSyncStmt = $pdo->prepare("SELECT MAX(last_synced_at) FROM trend_niches WHERE user_id = ? AND is_active = 1");
            $lastSyncStmt->execute([$userId]);

            $typeMap = ['video' => '🎬 Reel/Video', 'image' => '🖼️ Imagen', 'carousel_album' => '🔄 Carrusel', 'carousel' => '🔄 Carrusel'];
            $bestMedia = null;
            if (!empty($mediaTypes[0])) {
                $rawType  = strtolower($mediaTypes[0]['media_type']);
                $bestMedia = ['label' => $typeMap[$rawType] ?? ucfirst($rawType), 'avg_score' => round((float)$mediaTypes[0]['avg_score'], 1), 'count' => (int)$mediaTypes[0]['count']];
            }

            $capMap = ['muy_corto' => '⚡ Muy corto (<50)', 'corto' => '✅ Corto (50-150)', 'medio' => '📝 Medio (150-300)', 'largo' => '📖 Largo (>300)'];
            $bestCap = null;
            if (!empty($captionLengths[0])) {
                $cl = $captionLengths[0];
                $bestCap = ['label' => $capMap[$cl['cap_len']] ?? $cl['cap_len'], 'avg_score' => round((float)$cl['avg_score'], 1)];
            }

            return [
                'success'            => true,
                'total_posts'        => (int)$totalStmt->fetchColumn(),
                'last_sync'          => $lastSyncStmt->fetchColumn(),
                'best_media_type'    => $bestMedia,
                'best_caption_length'=> $bestCap,
                'niche_performance'  => $nicheStmt->fetchAll(),
                'media_breakdown'    => $mediaTypes,
            ];
        } catch (Throwable $e) {
            error_log("TrendsAgent generateTrendInsights: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ==========================================================================
    // INSPIRACIÓN IA
    // ==========================================================================

    public static function generateTrendInspiredCaption(int $userId, int $trendPostId, int $brandVoiceId): array {
        try {
            $pdo     = Database::getConnection();
            $pStmt   = $pdo->prepare("SELECT tp.*, tn.hashtag FROM trend_posts tp JOIN trend_niches tn ON tp.niche_id = tn.id WHERE tp.id = ? AND tp.user_id = ?");
            $pStmt->execute([$trendPostId, $userId]);
            $tPost   = $pStmt->fetch();
            if (!$tPost) return ['success' => false, 'error' => 'Post no encontrado'];

            $bvStmt  = $pdo->prepare("SELECT * FROM brand_voices WHERE id = ? AND user_id = ?");
            $bvStmt->execute([$brandVoiceId, $userId]);
            $bv      = $bvStmt->fetch();
            if (!$bv) {
                $bvStmt2 = $pdo->prepare("SELECT * FROM brand_voices WHERE user_id = ? LIMIT 1");
                $bvStmt2->execute([$userId]);
                $bv = $bvStmt2->fetch();
            }

            $brandName   = $bv['brand_name']   ?? 'Tu Marca';
            $tone        = $bv['tone_level']    ?? 'inspiring';
            $keyPhrases  = $bv['key_phrases']   ?? '';
            $systemPrompt= $bv['system_prompt'] ?? '';

            $openrouterKey = Settings::get('openrouter_api_key', '');
            $aiModel       = Settings::get('ai_model', 'anthropic/claude-3-haiku');

            if (!empty($openrouterKey)) {
                $caps = self::generateCaptionsWithOpenRouter(
                    $openrouterKey, $aiModel, $brandName, $tone, $systemPrompt, $keyPhrases,
                    $tPost['caption_preview'] ?? '', '#' . $tPost['hashtag'],
                    $tPost['media_type'] ?? 'image', (float)$tPost['engagement_score'], (int)$tPost['likes_count']
                );
                if (!empty($caps)) return ['success' => true, 'captions' => $caps, 'source' => 'openrouter'];
            }

            $caps = self::generateCaptionsHeuristic($brandName, $tone, '#' . $tPost['hashtag'], (float)$tPost['engagement_score']);
            return ['success' => true, 'captions' => $caps, 'source' => 'heuristic'];
        } catch (Throwable $e) {
            error_log("TrendsAgent generateTrendInspiredCaption: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al generar caption'];
        }
    }

    private static function generateCaptionsWithOpenRouter(
        string $apiKey, string $model, string $brandName, string $tone,
        string $systemPrompt, string $keyPhrases, string $captionRef,
        string $hashtag, string $mediaType, float $engScore, int $likes
    ): array {
        $mediaLabel = match(strtolower($mediaType)) { 'video' => 'Reel/Video', 'carousel', 'carousel_album' => 'Carrusel', default => 'Imagen' };
        $prompt = "Eres el copiloto de marca de \"{$brandName}\".\n\n"
            . "Post en tendencia de {$hashtag}: tipo {$mediaLabel}, {$engScore}% engagement, {$likes} likes.\n"
            . "Caption referencia: \"{$captionRef}\"\n\n"
            . "Voz de marca: {$systemPrompt}\nFrases clave: {$keyPhrases}\n\n"
            . "Genera 3 captions ORIGINALES para {$brandName} inspirados en este post (NO copies el original).\n"
            . "Cada uno: hook poderoso, tono {$tone}, pregunta final, 3-5 emojis, máx 200 palabras.\n\n"
            . "Responde SOLO JSON: {\"captions\":[\"caption1\",\"caption2\",\"caption3\"]}";

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['model' => $model, 'messages' => [['role' => 'user', 'content' => $prompt]], 'max_tokens' => 800, 'temperature' => 0.85]),
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json', 'HTTP-Referer: https://xindro.app'],
        ]);
        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code !== 200 || !$response) return [];
        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? '';
        if (preg_match('/\{.*\}/s', $content, $m)) {
            $parsed = json_decode($m[0], true);
            if (!empty($parsed['captions'])) return array_slice($parsed['captions'], 0, 3);
        }
        return [];
    }

    private static function generateCaptionsHeuristic(string $brandName, string $tone, string $hashtag, float $engScore): array {
        $label = $engScore >= 5 ? 'viral' : ($engScore >= 2 ? 'alto impacto' : 'en tendencia');
        return [
            "🔥 Esto está siendo {$label} en {$hashtag} — y hay una razón profunda.\n\nEl contenido que conecta de verdad no sigue tendencias. Las encarna.\n\n¿Qué historia tuya merece ser contada hoy?\n\n{$hashtag} #mentalidad #crecimiento",
            "💡 Estudié los posts con mayor engagement en {$hashtag} y descubrí algo:\n\nLa autenticidad supera siempre a la perfección.\n\n¿Cuándo fue la última vez que publicaste algo 100% tuyo? 👇\n\n{$hashtag} #autenticidad #marca",
            "⚡ El contenido {$label} en {$hashtag} tiene algo en común: genera conversación real, no solo impresiones.\n\n¿Cuál es tu mayor aprendizaje de esta semana? Tu comunidad necesita escucharlo. 🙌\n\n{$hashtag} #comunidad #inspiracion",
        ];
    }

    // ==========================================================================
    // LIMPIEZA
    // ==========================================================================

    public static function cleanupOldTrends(int $userId): int {
        try {
            $pdo  = Database::getConnection();
            $days = self::HISTORY_DAYS;
            $stmt = $pdo->prepare("
                DELETE FROM trend_posts
                WHERE user_id = ? AND ai_top_pick = 0
                  AND fetched_at < datetime('now', '-{$days} days')
            ");
            $stmt->execute([$userId]);
            return $stmt->rowCount();
        } catch (Throwable $e) {
            error_log("TrendsAgent cleanup: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================================================
    // UTILIDADES PRIVADAS
    // ==========================================================================

    private static function getUserCredentials(int $userId): ?array {
        try {
            $pdo   = Database::getConnection();
            $stmt  = $pdo->prepare("
                SELECT access_token, page_id FROM accounts
                WHERE user_id = ? AND platform = 'instagram' AND is_active = 1
                  AND access_token IS NOT NULL AND access_token != ''
                ORDER BY id ASC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $account = $stmt->fetch();

            $token = '';
            $igId  = '';

            if (!empty($account['access_token'])) {
                $token = $account['access_token'];
                $igId  = Settings::getForUser($userId, 'meta_instagram_account_id', '');
                if (empty($igId) && !empty($account['page_id'])) {
                    $url  = self::BASE_URL . '/' . $account['page_id'] . '?fields=instagram_business_account&access_token=' . urlencode($token);
                    $data = self::makeGetRequest($url);
                    $igId = $data['instagram_business_account']['id'] ?? '';
                }
            } else {
                $token = Settings::getForUser($userId, 'meta_page_access_token', '');
                $igId  = Settings::getForUser($userId, 'meta_instagram_account_id', '');
            }

            return (!empty($token) && !empty($igId)) ? ['token' => $token, 'ig_user_id' => $igId] : null;
        } catch (Throwable $e) {
            error_log("TrendsAgent getUserCredentials: " . $e->getMessage());
            return null;
        }
    }

    private static function makeGetRequest(string $url): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::API_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) return [];
        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }
}
