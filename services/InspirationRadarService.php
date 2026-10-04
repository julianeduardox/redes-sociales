<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ XINDRO AI Copilot - InspirationRadarService
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * Responsabilidades:
 * 1. Monitorea creadores referentes de Instagram usando Meta Business Discovery API (100% legal).
 * 2. Gestiona perfiles de inspiración de Instagram y Facebook.
 * 3. Importador directo de enlaces públicos de Facebook / Instagram y capturas.
 * 4. Extractor y Verificador de Citas Estoicas/Bushido (auténticas vs apócrifas).
 * 5. Generador de Recreaciones Originales y Prompts Visuales para @fortaleza_imparable.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/AteneaLearningEngine.php';

class InspirationRadarService {
    private const GRAPH_API_VERSION = 'v19.0';
    private const BASE_URL = 'https://graph.facebook.com/' . self::GRAPH_API_VERSION;
    private const API_TIMEOUT = 15;

    // ──────────────────────────────────────────────────────────────────────────
    // GESTIÓN DE CREADORES
    // ──────────────────────────────────────────────────────────────────────────

    public static function getCreators(int $userId): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT c.*, 
                       COUNT(p.id) as saved_posts_count,
                       MAX(p.engagement_score) as top_engagement_score
                FROM creator_targets c
                LEFT JOIN inspiration_posts p ON p.creator_id = c.id
                WHERE c.user_id = ? AND c.is_active = 1
                GROUP BY c.id
                ORDER BY c.followers_count DESC, c.created_at ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log("InspirationRadar getCreators: " . $e->getMessage());
            return [];
        }
    }

    public static function addCreator(int $userId, string $platform, string $usernameOrUrl, string $displayName = ''): array {
        try {
            $pdo = Database::getConnection();
            $platform = strtolower(trim($platform)) === 'facebook' ? 'facebook' : 'instagram';
            $raw = trim($usernameOrUrl);

            // Clean username from URL if user pasted full profile link
            $username = $raw;
            if (str_contains($raw, 'instagram.com/')) {
                preg_match('/instagram\.com\/([a-zA-Z0-9_\.]+)/i', $raw, $m);
                if (!empty($m[1])) $username = $m[1];
            } elseif (str_contains($raw, 'facebook.com/')) {
                preg_match('/facebook\.com\/([a-zA-Z0-9_\.]+)/i', $raw, $m);
                if (!empty($m[1])) $username = $m[1];
            }

            $username = ltrim(trim($username, "/ \t\n\r\0\x0B"), '@');
            if (empty($username)) {
                return ['success' => false, 'error' => 'Nombre de usuario o enlace inválido'];
            }

            if (empty($displayName)) {
                $displayName = ucfirst($username);
            }

            $profileUrl = $platform === 'instagram' 
                ? "https://www.instagram.com/{$username}" 
                : "https://www.facebook.com/{$username}";

            $exStmt = $pdo->prepare("SELECT id, is_active FROM creator_targets WHERE user_id = ? AND platform = ? AND username = ?");
            $exStmt->execute([$userId, $platform, $username]);
            $existing = $exStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                if ((int)$existing['is_active'] === 0) {
                    $pdo->prepare("UPDATE creator_targets SET is_active = 1, display_name = ? WHERE id = ?")
                        ->execute([$displayName, $existing['id']]);
                    return ['success' => true, 'message' => "Creador reactivado con éxito", 'creator_id' => (int)$existing['id']];
                }
                return ['success' => false, 'error' => 'Este creador ya está en tu radar'];
            }

            $insStmt = $pdo->prepare("
                INSERT INTO creator_targets (user_id, platform, username, display_name, profile_url)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insStmt->execute([$userId, $platform, $username, $displayName, $profileUrl]);
            $newId = (int)$pdo->lastInsertId();

            // Si es Instagram, intentar sincronizar de inmediato
            if ($platform === 'instagram') {
                self::syncInstagramCreator($userId, $newId);
            }

            return [
                'success' => true,
                'message' => 'Creador agregado exitosamente al radar',
                'creator_id' => $newId,
                'username' => $username,
                'platform' => $platform
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar addCreator: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al agregar creador'];
        }
    }

    public static function removeCreator(int $userId, int $creatorId, bool $deletePosts = true): array {
        try {
            $pdo = Database::getConnection();
            $chk = $pdo->prepare("SELECT id, username, display_name, platform FROM creator_targets WHERE id = ? AND user_id = ?");
            $chk->execute([$creatorId, $userId]);
            $creator = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$creator) {
                return ['success' => false, 'error' => 'Cuenta de referencia no encontrada o no pertenece a tu usuario'];
            }

            $deletedPostsCount = 0;
            if ($deletePosts) {
                $delPosts = $pdo->prepare("DELETE FROM inspiration_posts WHERE creator_id = ? AND user_id = ?");
                $delPosts->execute([$creatorId, $userId]);
                $deletedPostsCount = $delPosts->rowCount();
            }

            $delCreator = $pdo->prepare("DELETE FROM creator_targets WHERE id = ? AND user_id = ?");
            $delCreator->execute([$creatorId, $userId]);

            return [
                'success' => true,
                'message' => "Cuenta de referencia @{$creator['username']} eliminada correctamente del radar" . ($deletedPostsCount > 0 ? " ({$deletedPostsCount} publicaciones removidas)" : ""),
                'deleted_id' => $creatorId,
                'deleted_posts_count' => $deletedPostsCount
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar removeCreator: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar la cuenta de referencia: ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SINCRONIZACIÓN OFICIAL META BUSINESS DISCOVERY (INSTAGRAM)
    // ──────────────────────────────────────────────────────────────────────────

    public static function syncInstagramCreator(int $userId, int $creatorId): array {
        try {
            $pdo = Database::getConnection();
            $cStmt = $pdo->prepare("SELECT * FROM creator_targets WHERE id = ? AND user_id = ? AND is_active = 1");
            $cStmt->execute([$creatorId, $userId]);
            $creator = $cStmt->fetch(PDO::FETCH_ASSOC);

            if (!$creator) return ['success' => false, 'error' => 'Creador no encontrado'];
            if ($creator['platform'] !== 'instagram') {
                return ['success' => false, 'error' => 'La sincronización automática de Meta es exclusiva para cuentas de Instagram'];
            }

            $creds = self::getUserMetaCredentials($userId);
            if (!$creds) {
                return ['success' => false, 'error' => 'No tienes cuenta de Instagram profesional conectada en XINDRO'];
            }

            $token = $creds['token'];
            $igUserId = $creds['ig_user_id'];
            $username = $creator['username'];

            $fields = "business_discovery.username({$username}){" .
                "name,username,followers_count,media_count,profile_picture_url," .
                "media.limit(25){id,caption,like_count,comments_count,timestamp,permalink,media_type,media_url,thumbnail_url}" .
            "}";

            $url = self::BASE_URL . "/{$igUserId}?fields=" . urlencode($fields) . "&access_token=" . urlencode($token);
            $data = self::makeGetRequest($url);

            if (empty($data['business_discovery'])) {
                $errMsg = $data['error']['message'] ?? 'No se pudo obtener datos del perfil';
                return ['success' => false, 'error' => "Meta API: $errMsg"];
            }

            $bd = $data['business_discovery'];
            $followers = (int)($bd['followers_count'] ?? $creator['followers_count']);
            $mediaCount = (int)($bd['media_count'] ?? $creator['media_count']);
            $avatarUrl = $bd['profile_picture_url'] ?? $creator['avatar_url'];
            $displayName = !empty($bd['name']) ? $bd['name'] : $creator['display_name'];

            // Actualizar metadata del creador
            $upCreator = $pdo->prepare("
                UPDATE creator_targets 
                SET followers_count = :followers, media_count = :media_count, 
                    avatar_url = :avatar, display_name = :display_name, last_synced_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upCreator->execute([
                ':followers' => $followers,
                ':media_count' => $mediaCount,
                ':avatar' => $avatarUrl,
                ':display_name' => $displayName,
                ':id' => $creatorId
            ]);

            $rawPosts = $bd['media']['data'] ?? [];
            $ingestedCount = 0;

            $insPost = $pdo->prepare("
                INSERT INTO inspiration_posts (
                    user_id, creator_id, platform, external_post_id, caption, 
                    media_url, permalink, media_type, likes_count, comments_count, 
                    engagement_score, posted_at, fetched_at
                ) VALUES (
                    :user_id, :creator_id, 'instagram', :ext_id, :caption,
                    :media_url, :permalink, :media_type, :likes, :comments,
                    :score, :posted_at, CURRENT_TIMESTAMP
                )
                ON CONFLICT(user_id, platform, external_post_id) DO UPDATE SET
                    likes_count = EXCLUDED.likes_count,
                    comments_count = EXCLUDED.comments_count,
                    engagement_score = EXCLUDED.engagement_score,
                    caption = EXCLUDED.caption,
                    media_url = COALESCE(EXCLUDED.media_url, inspiration_posts.media_url)
            ");

            foreach ($rawPosts as $p) {
                if (empty($p['id'])) continue;
                $likes = (int)($p['like_count'] ?? 0);
                $comms = (int)($p['comments_count'] ?? 0);
                $caption = $p['caption'] ?? '';
                $postedAt = !empty($p['timestamp']) ? date('Y-m-d H:i:s', strtotime($p['timestamp'])) : date('Y-m-d H:i:s');

                // Engagement score relativo: Likes + Comentarios ponderados respecto a seguidores
                $baseFollowers = max(500, $followers);
                $weightedEng = $likes + ($comms * 3);
                $score = round(($weightedEng / $baseFollowers) * 100, 2);

                $mediaImg = !empty($p['thumbnail_url']) ? $p['thumbnail_url'] : ($p['media_url'] ?? null);

                $insPost->execute([
                    ':user_id' => $userId,
                    ':creator_id' => $creatorId,
                    ':ext_id' => $p['id'],
                    ':caption' => $caption,
                    ':media_url' => $mediaImg,
                    ':permalink' => $p['permalink'] ?? "https://www.instagram.com/p/{$p['id']}",
                    ':media_type' => strtolower($p['media_type'] ?? 'image'),
                    ':likes' => $likes,
                    ':comments' => $comms,
                    ':score' => $score,
                    ':posted_at' => $postedAt
                ]);
                $ingestedCount++;
            }

            return [
                'success' => true,
                'message' => "Sincronizados {$ingestedCount} posts de @{$username}",
                'creator' => [
                    'username' => $username,
                    'followers' => $followers,
                    'media_count' => $mediaCount
                ],
                'synced_posts' => $ingestedCount
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar syncInstagramCreator: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error durante la sincronización: ' . $e->getMessage()];
        }
    }

    public static function syncAllCreators(int $userId): array {
        $creators = self::getCreators($userId);
        $totalSynced = 0;
        $results = [];

        foreach ($creators as $c) {
            if ($c['platform'] === 'instagram') {
                $res = self::syncInstagramCreator($userId, (int)$c['id']);
                $results[$c['username']] = $res;
                if (!empty($res['success'])) {
                    $totalSynced += ($res['synced_posts'] ?? 0);
                }
            }
        }

        return [
            'success' => true,
            'total_creators' => count($creators),
            'total_posts_synced' => $totalSynced,
            'details' => $results
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // IMPORTADOR MANUAL RÁPIDO (FACEBOOK / INSTAGRAM / CAPTURAS)
    // ──────────────────────────────────────────────────────────────────────────

    public static function importDirectPost(int $userId, string $urlOrText, string $caption = '', ?string $mediaUrl = null, ?int $creatorId = null): array {
        try {
            $pdo = Database::getConnection();
            $url = trim($urlOrText);
            $cleanCaption = trim($caption);

            // Si el usuario pegó solo texto directamente en lugar de una URL
            if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
                if (empty($cleanCaption)) {
                    $cleanCaption = $url;
                }
                $url = 'https://custom-import.local/' . md5($cleanCaption . time());
            }

            $platform = str_contains($url, 'facebook.com') ? 'facebook' : 'instagram';
            $extId = 'imp_' . substr(md5($url . time()), 0, 16);

            $insStmt = $pdo->prepare("
                INSERT INTO inspiration_posts (
                    user_id, creator_id, platform, external_post_id, caption,
                    media_url, permalink, media_type, likes_count, comments_count,
                    engagement_score, posted_at, fetched_at
                ) VALUES (
                    :user_id, :creator_id, :platform, :ext_id, :caption,
                    :media_url, :permalink, 'image', 0, 0,
                    100.0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ");
            $insStmt->execute([
                ':user_id' => $userId,
                ':creator_id' => $creatorId,
                ':platform' => $platform,
                ':ext_id' => $extId,
                ':caption' => $cleanCaption,
                ':media_url' => $mediaUrl,
                ':permalink' => $url
            ]);

            $postId = (int)$pdo->lastInsertId();

            // Analizar inmediatamente la cita si contiene texto
            if (!empty($cleanCaption)) {
                self::analyzeAndVerifyQuote($userId, $postId);
            }

            return [
                'success' => true,
                'message' => 'Publicación importada exitosamente en el radar',
                'post_id' => $postId
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar importDirectPost: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al importar publicación: ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CONSULTA DE POSTS DEL RADAR
    // ──────────────────────────────────────────────────────────────────────────

    public static function getInspirationPosts(int $userId, ?int $creatorId = null, string $sort = 'engagement', int $limit = 40): array {
        try {
            $pdo = Database::getConnection();
            $params = [$userId];
            $whereCreator = "";

            if ($creatorId !== null && $creatorId > 0) {
                $whereCreator = " AND p.creator_id = ? ";
                $params[] = $creatorId;
            }

            $orderSql = match($sort) {
                'likes' => 'p.likes_count DESC',
                'recent' => 'p.posted_at DESC',
                'creative_fit' => 'COALESCE(p.creative_fit_score, 0) DESC, COALESCE(p.opportunity_score, 0) DESC',
                'opportunity' => 'COALESCE(p.opportunity_score, 0) DESC, p.engagement_score DESC',
                default => 'p.engagement_score DESC, p.likes_count DESC'
            };

            $sql = "
                SELECT p.*, 
                       c.username as creator_username,
                       c.display_name as creator_name,
                       c.avatar_url as creator_avatar
                FROM inspiration_posts p
                LEFT JOIN creator_targets c ON p.creator_id = c.id
                WHERE p.user_id = ? {$whereCreator}
                ORDER BY {$orderSql}
                LIMIT {$limit}
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // Calcular Opportunity Score y Creative Fit Score dinámicos para posts que aún no los tengan
            foreach ($posts as &$postItem) {
                if (empty($postItem['opportunity_score']) || (float)$postItem['opportunity_score'] <= 0) {
                    $postItem['opportunity_score'] = self::calculateOpportunityScore($postItem);
                }
                if (empty($postItem['creative_fit_score']) || (float)$postItem['creative_fit_score'] <= 0) {
                    $postItem['creative_fit_score'] = self::calculateCreativeFitScore($postItem);
                }
            }
            unset($postItem);

            return $posts;
        } catch (Throwable $e) {
            error_log("InspirationRadar getInspirationPosts: " . $e->getMessage());
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CEREBRO IA: EXTRACTOR Y VERIFICADOR DE CITAS ESTOICAS / BUSHIDO & OCR VISIÓN
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Extracción Óptica de Texto en Placas mediante Visión Multimodal (OpenRouter)
     * Directiva: Transcripción exclusiva del texto visible sin interpretación semántica.
     * Retorna:
     * {"has_text": true, "text": "...", "confidence": 0.94} o {"has_text": false, "text": null, "confidence": 0}
     * Regla estricta: NUNCA asignar caption_text a visual_text si vision falla o no hay texto.
     */
    public static function extractOverlayTextWithVision(int $userId, int $postId, ?string $overrideMediaUrl = null): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, media_url, caption, visual_text, visual_text_source, visual_text_status FROM inspiration_posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$post) {
                return ['success' => false, 'error' => 'Publicación no encontrada'];
            }

            $mediaUrl = trim($overrideMediaUrl ?: ($post['media_url'] ?? ''));

            if (empty($mediaUrl)) {
                $up = $pdo->prepare("UPDATE inspiration_posts SET visual_text = NULL, visual_text_source = 'NONE', visual_text_status = 'NO_TEXT', visual_text_confidence = 0.0 WHERE id = ? AND user_id = ?");
                $up->execute([$postId, $userId]);
                return [
                    'success' => true,
                    'has_text' => false,
                    'text' => null,
                    'confidence' => 0.0,
                    'visual_text' => null,
                    'visual_text_source' => 'NONE',
                    'visual_text_status' => 'NO_TEXT',
                    'visual_text_confidence' => 0.0,
                    'message' => 'La publicación no contiene una URL de imagen válida para inspección visual.'
                ];
            }

            $apiKey = Settings::get('openrouter_api_key', '', $userId);
            if (empty($apiKey)) {
                $apiKey = Settings::get('openrouter_api_key', '');
            }

            if (empty($apiKey)) {
                $up = $pdo->prepare("UPDATE inspiration_posts SET visual_text = NULL, visual_text_source = 'NONE', visual_text_status = 'UNAVAILABLE', visual_text_confidence = 0.0 WHERE id = ? AND user_id = ?");
                $up->execute([$postId, $userId]);
                return [
                    'success' => false,
                    'error' => 'Clave de OpenRouter no configurada para procesar visión multimodal.',
                    'visual_text' => null,
                    'visual_text_status' => 'UNAVAILABLE'
                ];
            }

            // Modelo multimodal de alta velocidad y precisión óptica
            $visionModel = 'google/gemini-2.0-flash-001';

            $promptVision = "Eres un transcriptor óptico (OCR) estricto. Tu ÚNICA misión es transcribir textualmente cualquier texto escrito o estampado visible en la imagen gráfica o portada del post.\n" .
                "REGLAS OBLIGATORIAS:\n" .
                "1. Transcribe ÚNICAMENTE el texto que esté visible en la imagen gráfica.\n" .
                "2. NO interpretes, NO completes, NO corrijas faltas, NO parafrasees y NO inventes texto que no exista en la imagen.\n" .
                "3. Si la imagen NO contiene texto, o es solo una fotografía/busto sin letras, responde has_text: false y text: null.\n" .
                "4. Responde SIEMPRE única y exclusivamente en formato JSON estricto con esta estructura:\n" .
                "{\"has_text\": boolean, \"text\": string|null, \"confidence\": float_entre_0_y_1}";

            $payload = [
                'model' => $visionModel,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $promptVision],
                            ['type' => 'image_url', 'image_url' => ['url' => $mediaUrl]]
                        ]
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
                'max_tokens' => 300
            ];

            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'HTTP-Referer: https://xindro.app',
                    'X-Title: XINDRO Vision OCR'
                ]
            ]);

            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($code !== 200 || !$res) {
                // Falla en API: visual_text = NULL, status = UNAVAILABLE
                $up = $pdo->prepare("UPDATE inspiration_posts SET visual_text = NULL, visual_text_source = 'NONE', visual_text_status = 'UNAVAILABLE', visual_text_confidence = 0.0 WHERE id = ? AND user_id = ?");
                $up->execute([$postId, $userId]);

                return [
                    'success' => false,
                    'error' => "Error en visión multimodal (HTTP $code): " . ($curlErr ?: 'Respuesta vacía o error de API'),
                    'visual_text' => null,
                    'visual_text_status' => 'UNAVAILABLE'
                ];
            }

            $data = json_decode($res, true);
            $rawContent = trim($data['choices'][0]['message']['content'] ?? '');
            $rawContent = preg_replace('/^```(?:json)?\s*/i', '', $rawContent);
            $rawContent = preg_replace('/\s*```$/', '', $rawContent);

            $parsedVision = json_decode($rawContent, true);
            if (!is_array($parsedVision)) {
                if (preg_match('/\{.*\}/s', $rawContent, $m)) {
                    $parsedVision = json_decode($m[0], true);
                }
            }

            $hasText = !empty($parsedVision['has_text']) && !empty(trim($parsedVision['text'] ?? ''));
            $detectedText = $hasText ? trim(strip_tags($parsedVision['text'])) : null;
            $confidence = $hasText ? max(0.4, min(1.0, (float)($parsedVision['confidence'] ?? 0.90))) : 0.0;
            $status = $hasText ? ($confidence >= 0.85 ? 'CONFIRMED' : 'NEEDS_REVIEW') : 'NO_TEXT';
            $source = $hasText ? 'VISION' : 'NONE';

            // Actualizar en DB
            $upStmt = $pdo->prepare("
                UPDATE inspiration_posts SET
                    visual_text = :vtext,
                    visual_text_source = :vsource,
                    visual_text_status = :vstatus,
                    visual_text_confidence = :vconf,
                    quote_extracted = COALESCE(:vtext_compat, quote_extracted)
                WHERE id = :id AND user_id = :uid
            ");
            $upStmt->execute([
                ':vtext' => $detectedText,
                ':vsource' => $source,
                ':vstatus' => $status,
                ':vconf' => $confidence,
                ':vtext_compat' => $detectedText,
                ':id' => $postId,
                ':uid' => $userId
            ]);

            return [
                'success' => true,
                'has_text' => $hasText,
                'text' => $detectedText,
                'confidence' => $confidence,
                'visual_text' => $detectedText,
                'visual_text_source' => $source,
                'visual_text_status' => $status,
                'visual_text_confidence' => round($confidence * 100, 1),
                'message' => $hasText 
                    ? 'Texto visual extraído exitosamente de la placa.' 
                    : 'La imagen fue inspeccionada y no se detectó texto superpuesto.'
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar extractOverlayTextWithVision: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage(), 'visual_text' => null, 'visual_text_status' => 'UNAVAILABLE'];
        }
    }

    /**
     * Guarda y confirma manualmente el texto de la placa editado por el usuario
     */
    public static function saveVisualText(int $userId, int $postId, string $text): array {
        try {
            $pdo = Database::getConnection();
            $clean = trim($text);
            $source = !empty($clean) ? 'USER' : 'NONE';
            $status = !empty($clean) ? 'USER_CONFIRMED' : 'NO_TEXT';
            $conf = !empty($clean) ? 1.0 : 0.0;

            $stmt = $pdo->prepare("
                UPDATE inspiration_posts SET
                    visual_text = :vtext,
                    visual_text_source = :vsource,
                    visual_text_status = :vstatus,
                    visual_text_confidence = :vconf,
                    quote_extracted = :compat
                WHERE id = :id AND user_id = :uid
            ");
            $stmt->execute([
                ':vtext' => !empty($clean) ? $clean : null,
                ':vsource' => $source,
                ':vstatus' => $status,
                ':vconf' => $conf,
                ':compat' => !empty($clean) ? $clean : null,
                ':id' => $postId,
                ':uid' => $userId
            ]);

            return [
                'success' => true,
                'visual_text' => !empty($clean) ? $clean : null,
                'visual_text_source' => $source,
                'visual_text_status' => $status,
                'visual_text_confidence' => 100.0,
                'message' => 'Texto visual confirmado por el usuario.'
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar saveVisualText: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public static function analyzeAndVerifyQuote(int $userId, int $postId): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM inspiration_posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$post) return ['success' => false, 'error' => 'Publicación no encontrada'];

            $caption = $post['caption'] ?? '';
            if (empty(trim($caption))) {
                return ['success' => false, 'error' => 'La publicación no contiene texto para analizar'];
            }

            $apiKey = Settings::get('openrouter_api_key', '');
            $aiModel = Settings::get('openrouter_model', 'nousresearch/hermes-3-llama-3.1-70b');

            $analysis = null;
            if (!empty($apiKey)) {
                $analysis = self::callAiForQuoteVerification($apiKey, $aiModel, $caption);
            }

            // Fallback heurístico si no hay API key o falló la llamada
            if (!$analysis) {
                $analysis = self::heuristicQuoteAnalysis($caption);
            }

            // Guardar resultados en la base de datos
            $upStmt = $pdo->prepare("
                UPDATE inspiration_posts SET
                    quote_extracted = :quote,
                    quote_author = :author,
                    quote_verified_status = :status,
                    quote_source_note = :source_note,
                    theme = :theme,
                    emotion = :emotion,
                    hook = :hook,
                    ai_analysis = :analysis
                WHERE id = :id AND user_id = :uid
            ");
            $upStmt->execute([
                ':quote' => $analysis['quote_extracted'] ?? '',
                ':author' => $analysis['quote_author'] ?? 'Desconocido',
                ':status' => $analysis['quote_verified_status'] ?? 'pending',
                ':source_note' => $analysis['quote_source_note'] ?? '',
                ':theme' => $analysis['theme'] ?? 'Desarrollo Personal',
                ':emotion' => $analysis['emotion'] ?? 'Determinación',
                ':hook' => $analysis['hook'] ?? '',
                ':analysis' => json_encode($analysis, JSON_UNESCAPED_UNICODE),
                ':id' => $postId,
                ':uid' => $userId
            ]);

            return [
                'success' => true,
                'analysis' => $analysis
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar analyzeAndVerifyQuote: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al analizar la cita: ' . $e->getMessage()];
        }
    }

    private static function callAiForQuoteVerification(string $apiKey, string $model, string $caption): ?array {
        $systemPrompt = "Eres un historiador y erudito experto en Filosofía Estoica clásica (Marco Aurelio, Séneca, Epicteto, Musonio Rufo) y código Bushido / Samurái (Miyamoto Musashi, Yamamoto Tsunetomo). Analiza publicaciones de redes sociales y responde SIEMPRE única y exclusivamente en formato JSON estricto sin texto conversacional ni preámbulos.";

        $userPrompt = <<<PROMPT
Analiza el siguiente texto de una publicación de redes sociales:
"""
{$caption}
"""

Tu tarea:
1. Extrae la Frase o Cita Principal ("quote_extracted").
2. Identifica al autor histórico ("quote_author", o 'Desconocido / Reflexión Moderna' si no es clásico).
3. Dictamina el estado de autenticidad ("quote_verified_status"):
   - "verified_authentic": Si es una cita clásica real de textos conocidos (Meditaciones, Cartas a Lucilio, Dokkodo, Hagakure).
   - "apocryphal": Si es una cita inventada/falsa atribuida erróneamente en redes sociales.
   - "modern_idea": Si es una reflexión contemporánea de liderazgo, psicología o desarrollo personal.
4. "quote_source_note": Especifica el libro/carta exacto si es auténtica, o explica por qué es apócrifa/moderna.
5. "theme": Tema principal (ej. Disciplina, Silencio, Traición, Dolor, Muerte/Memento Mori, Autocontrol, Honor, Liderazgo).
6. "emotion": Emoción predominante (ej. Firmeza, Calma, Despertar, Superación, Autoridad).
7. "hook": La frase de impacto o gancho inicial que atrapa al lector.
8. "why_it_worked": Breve explicación (máximo 2 líneas) de por qué este post conectó con la audiencia.

Responde ÚNICAMENTE en JSON con esta estructura exacta:
{
  "quote_extracted": "...",
  "quote_author": "...",
  "quote_verified_status": "verified_authentic | apocryphal | modern_idea",
  "quote_source_note": "...",
  "theme": "...",
  "emotion": "...",
  "hook": "...",
  "why_it_worked": "..."
}
PROMPT;

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.3,
            'max_tokens' => 700
        ];

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: https://xindro.app',
                'X-Title: XINDRO Social AI'
            ]
        ]);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $res) {
            $data = json_decode($res, true);
            $content = trim($data['choices'][0]['message']['content'] ?? '');
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            if (preg_match('/\{.*\}/s', $content, $m)) {
                $parsed = json_decode($m[0], true);
                if (!empty($parsed['quote_extracted'])) {
                    return $parsed;
                }
            }
        }
        return null;
    }

    private static function heuristicQuoteAnalysis(string $caption): array {
        $capLower = mb_strtolower($caption, 'UTF-8');
        $author = 'Desconocido';
        $status = 'modern_idea';
        $source = 'Reflexión contemporánea de desarrollo personal';
        $theme = 'Disciplina y Carácter';
        $emotion = 'Firmeza';

        if (str_contains($capLower, 'marco aurelio') || str_contains($capLower, 'marcus aurelius')) {
            $author = 'Marco Aurelio';
            $status = 'verified_authentic';
            $source = 'Meditaciones (Verificar traducción)';
            $theme = 'Autocontrol';
        } elseif (str_contains($capLower, 'séneca') || str_contains($capLower, 'seneca')) {
            $author = 'Séneca';
            $status = 'verified_authentic';
            $source = 'Cartas a Lucilio / De la Brevedad de la Vida';
            $theme = 'Tiempo y Serenidad';
        } elseif (str_contains($capLower, 'epicteto') || str_contains($capLower, 'epictetus')) {
            $author = 'Epicteto';
            $status = 'verified_authentic';
            $source = 'Enquiridión / Disertaciones';
            $theme = 'Dicotomía del Control';
        } elseif (str_contains($capLower, 'musashi') || str_contains($capLower, 'samurai') || str_contains($capLower, 'samurái')) {
            $author = 'Miyamoto Musashi / Tradición Samurái';
            $status = 'verified_authentic';
            $source = 'El Libro de los Cinco Anillos / Dokkodo';
            $theme = 'Honor y Guerra Interior';
        } elseif (str_contains($capLower, 'lider') || str_contains($capLower, 'liderazgo') || str_contains($capLower, 'poder') || str_contains($capLower, 'miedo') || str_contains($capLower, 'autoridad')) {
            $theme = 'Liderazgo y Poder';
            $emotion = 'Autoridad serena';
            $source = 'Tratado de liderazgo y conducta estoica';
        } elseif (str_contains($capLower, 'traición') || str_contains($capLower, 'traicion')) {
            $theme = 'Traición y Lealtad';
            $emotion = 'Decepción constructiva';
        } elseif (str_contains($capLower, 'silencio')) {
            $theme = 'Poder del Silencio';
            $emotion = 'Templanza';
        } elseif (str_contains($capLower, 'dolor')) {
            $theme = 'Superación del Dolor';
            $emotion = 'Resiliencia';
        }

        // Extraer la primera frase como cita/hook
        $lines = explode("\n", trim($caption));
        $firstLine = trim($lines[0] ?? $caption);

        return [
            'quote_extracted' => mb_substr($firstLine, 0, 150),
            'quote_author' => $author,
            'quote_verified_status' => $status,
            'quote_source_note' => $source,
            'theme' => $theme,
            'emotion' => $emotion,
            'hook' => mb_substr($firstLine, 0, 90),
            'why_it_worked' => 'Contraste emocional fuerte que despierta identificación sobre la lucha diaria.'
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // 🏛️ MOTOR ATENEA: DIRECTORA DE ESTRATEGIA DE CONTENIDO & FILOSOFÍA ESTOICA
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Calcula el Opportunity Score algorítmico (1.0 a 10.0) combinando engagement,
     * afinidad temática con Fortaleza Imparable, frescura temporal y autenticidad.
     */
    public static function calculateOpportunityScore(array $post): float {
        $likes = (int)($post['likes_count'] ?? 0);
        $comments = (int)($post['comments_count'] ?? 0);
        $caption = mb_strtolower(($post['caption'] ?? '') . ' ' . ($post['theme'] ?? ''), 'UTF-8');

        // 1. Base de engagement e interactividad (0 a 4.0 pts)
        $rawEngagement = $likes + ($comments * 3);
        $engagementScore = min(4.0, log10(max(1, $rawEngagement) + 1) * 1.15);

        // 2. Afinidad de palabras clave del nicho Fortaleza Imparable (0 a 3.0 pts)
        $nicheKeywords = [
            'estoicismo', 'estoico', 'marco aurelio', 'séneca', 'seneca', 'epicteto',
            'disciplina', 'carácter', 'caracter', 'fortaleza', 'mente', 'silencio',
            'dolor', 'adversidad', 'resiliencia', 'samurai', 'samurái', 'bushido',
            'musashi', 'honor', 'victoria', 'templanza', 'soberanía', 'guerrero',
            'hábitos', 'habitos', 'enfoque', 'propósito', 'sabiduría', 'sabiduria',
            'control', 'autodominio', 'memento mori', 'amor fati', 'virtud'
        ];
        $matches = 0;
        foreach ($nicheKeywords as $kw) {
            if (str_contains($caption, $kw)) {
                $matches++;
            }
        }
        $keywordScore = min(3.0, $matches * 0.75);

        // 3. Frescura temporal / Decaimiento de novedad (0 a 1.5 pts)
        $recencyScore = 1.0;
        if (!empty($post['posted_at'])) {
            $daysOld = (time() - strtotime($post['posted_at'])) / 86400;
            if ($daysOld <= 3) {
                $recencyScore = 1.5;
            } elseif ($daysOld <= 14) {
                $recencyScore = 1.2;
            } elseif ($daysOld <= 30) {
                $recencyScore = 0.8;
            } else {
                $recencyScore = 0.4;
            }
        }

        // 4. Verificación y sustancia de la cita (0 a 1.5 pts)
        $status = $post['quote_verified_status'] ?? '';
        $substanceScore = 0.6;
        if ($status === 'verified_authentic') {
            $substanceScore = 1.5;
        } elseif ($status === 'modern_idea') {
            $substanceScore = 1.1;
        }
        if (mb_strlen($post['caption'] ?? '') > 120) {
            $substanceScore = min(1.5, $substanceScore + 0.3);
        }

        $totalScore = round(min(10.0, max(1.0, $engagementScore + $keywordScore + $recencyScore + $substanceScore)), 1);
        return $totalScore;
    }

    /**
     * Calcula el Creative Fit Score (1.0 a 10.0) midiendo la afinidad filosófica real
     * con la identidad de @fortaleza_imparable (Estoicismo clásico, Bushido, rigor y soberanía mental)
     * versus clichés de motivación barata de gimnasio ("conviértete en bestia", "modo tiburón", etc.).
     */
    public static function calculateCreativeFitScore(array $post): float {
        $text = mb_strtolower(
            ($post['caption'] ?? '') . ' ' . 
            ($post['quote_extracted'] ?? '') . ' ' . 
            ($post['quote_author'] ?? '') . ' ' . 
            ($post['theme'] ?? ''), 
            'UTF-8'
        );

        $score = 5.0; // Puntuación base neutral

        // 1. Pilares Clásicos & Filosóficos (+0.85 por coincidencia, máx +3.5)
        $highValueTerms = [
            'marco aurelio', 'marcus aurelius', 'séneca', 'seneca', 'epicteto', 'epictetus',
            'musonio', 'zenón', 'zenon', 'cleantes', 'crisipo', 'meditaciones', 'enquiridion',
            'cartas a lucilio', 'dicotomía del control', 'dicotomia del control', 'memento mori',
            'amor fati', 'ataraxia', 'apatheia', 'soberanía mental', 'soberania mental',
            'autodominio', 'templanza', 'silencio', 'carácter', 'caracter', 'virtud',
            'dokkodo', 'dokkōdō', 'hagakure', 'musashi', 'miyamoto', 'bushido', 'código samurái',
            'disciplina solitaria', 'honradez', 'deber'
        ];
        $matches = 0;
        foreach ($highValueTerms as $term) {
            if (str_contains($text, $term)) {
                $matches++;
            }
        }
        $score += min(3.5, $matches * 0.85);

        // 2. Autenticidad histórica de la cita (+1.0 si es verificada, penaliza apócrifas)
        $status = $post['quote_verified_status'] ?? '';
        if ($status === 'verified_authentic') {
            $score += 1.0;
        } elseif ($status === 'modern_idea') {
            $score += 0.3;
        } elseif ($status === 'apocryphal') {
            $score -= 0.8;
        }

        // 3. Penalización por Clichés de Gimnasio / Motivación Barata / Vendehúmos (-1.2 por cliché, máx -4.0)
        $cliches = [
            'conviértete en una bestia', 'conviertete en una bestia', 'modo bestia', 'sé una bestia',
            'se una bestia', 'sé imparable', 'se imparable', 'mente de tiburón', 'mente de tiburon',
            'mente millonaria', 'hazte rico', 'facturar', 'actitud de león', 'actitud de leon',
            'sin dolor no hay gloria', 'los débiles mueren', 'los debiles mueren', 'sal de tu zona de confort',
            'nadie te detendrá', 'vamos con todo leones', 'rugir', 'alfa', 'macho alfa', 'sigma', 'mentalidad alfa'
        ];
        $clicheHits = 0;
        foreach ($cliches as $cliche) {
            if (str_contains($text, $cliche)) {
                $clicheHits++;
            }
        }
        $score -= min(4.0, $clicheHits * 1.2);

        // 4. Bonificación por profundidad reflexiva
        if (mb_strlen($post['caption'] ?? '') > 140 && !str_contains($text, '#fitnessmotivation')) {
            $score += 0.5;
        }

        return round(min(10.0, max(1.0, $score)), 1);
    }

    /**
     * Extrae el ADN Psicológico y Filosófico de una publicación viral a partir
     * de la frase de la imagen/vídeo y del pie de foto original.
     */
    public static function extractContentDna(string $caption, string $theme = '', string $quote = ''): array {
        $combined = mb_strtolower(trim(strip_tags($quote . ' ' . $caption)), 'UTF-8');
        $themeClean = mb_strtolower(trim($theme), 'UTF-8');

        if (str_contains($combined, 'silencio') || str_contains($combined, 'hablar') || str_contains($combined, 'opini') || str_contains($combined, 'palabras')) {
            return [
                'core_concept' => 'El silencio estratégico y la soberanía interior sobre la opinión ajena.',
                'conflict' => 'La necesidad impulsiva de validación externa vs el autodominio del trabajo silencioso.',
                'transformation' => 'Dejar de justificar tus pasos para que la obra terminada hable por ti.',
                'hook_type' => 'Paradoja Contraintuitiva',
                'sentence_structure' => 'Axioma breve -> Contraste sabio vs mediocre -> Mandato imperativo de disciplina.',
                'audience_pain' => 'Desgaste emocional por buscar aprobación de personas que no construyen nada.',
                'belief_challenged' => 'La ilusión de que compartir tus planes en redes te acerca a lograrlos.',
                'emotional_trigger' => 'Vergüenza constructiva que empuja al recogimiento y la concentración.',
                'shareability_mechanism' => 'Declaración de principios: el usuario lo comparte para comunicar que trabaja en silencio.',
                'creative_fit_score' => 9.2,
                'why_explanation' => 'Desarma la necesidad de aplauso inmediato y valida el poder del trabajo invisible.'
            ];
        }

        if (str_contains($combined, 'dolor') || str_contains($combined, 'adversidad') || str_contains($combined, 'sufrir') || str_contains($combined, 'fuego') || str_contains($combined, 'herida')) {
            return [
                'core_concept' => 'La transmutación del dolor en combustible para la forja del carácter estoico.',
                'conflict' => 'El instinto moderno de huir de la incomodidad vs el principio de amor fati.',
                'transformation' => 'Dejar de preguntar "¿por qué a mí?" y exigir hombros más fuertes para la carga.',
                'hook_type' => 'Golpe de Realidad',
                'sentence_structure' => 'Metáfora de forja -> Cuestionamiento de la debilidad -> Afirmación de invulnerabilidad mental.',
                'audience_pain' => 'Sensación de agobio o parálisis ante dificultades que percibe injustas.',
                'belief_challenged' => 'La falsa promesa moderna de una vida sin fricción ni sufrimiento.',
                'emotional_trigger' => 'Orgullo estoico y recuperación inmediata de la agencia personal.',
                'shareability_mechanism' => 'Recordatorio de batalla personal que el lector guarda para momentos duros.',
                'creative_fit_score' => 9.5,
                'why_explanation' => 'Convierte la queja pasiva en determinación activa al redefinir la dificultad como forja.'
            ];
        }

        if (str_contains($combined, 'tiempo') || str_contains($combined, 'muerte') || str_contains($combined, 'vida') || str_contains($combined, 'memento') || str_contains($combined, 'hora')) {
            return [
                'core_concept' => 'Memento Mori: La finitud de la existencia como catalizador de foco radical.',
                'conflict' => 'Vivir como si fuéramos inmortales postergando lo esencial por placeres efímeros.',
                'transformation' => 'Recuperar la posesión del presente antes de que el tiempo se disuelva en la nada.',
                'hook_type' => 'Urgencia Existencial',
                'sentence_structure' => 'Golpe de finitud -> Consecuencia de la distracción -> Llamado a la sobriedad presente.',
                'audience_pain' => 'Culpa silenciosa por postergar decisiones cruciales mientras pasan los meses.',
                'belief_challenged' => 'Pensar que "habrá tiempo después" para vivir con verdadera disciplina.',
                'emotional_trigger' => 'Conciencia aguda de mortalidad que extingue las excusas triviales.',
                'shareability_mechanism' => 'Llamada de atención profunda que despierta empatía existencial compartida.',
                'creative_fit_score' => 9.4,
                'why_explanation' => 'Rompe la complacencia diaria al recordarle al lector que cada hora perdida es irrecuperable.'
            ];
        }

        if (str_contains($combined, 'musashi') || str_contains($combined, 'bushido') || str_contains($combined, 'dokkodo') || str_contains($combined, 'samur')) {
            return [
                'core_concept' => 'El camino solitario del guerrero: disciplina marcial sin apego al resultado.',
                'conflict' => 'Depender del reconocimiento o compañía vs la autosuficiencia del camino del deber.',
                'transformation' => 'Aceptar la soledad como taller sagrado de maestría y honor personal.',
                'hook_type' => 'Axioma Marcial',
                'sentence_structure' => 'Máxima de Musashi -> Desapego del ruido social -> Compromiso implacable con el camino.',
                'audience_pain' => 'Temor al aislamiento o a ser incomprendido por elegir un estándar más alto.',
                'belief_challenged' => 'La necesidad de encajar con el grupo a costa de diluir la propia disciplina.',
                'emotional_trigger' => 'Serenidad marcial y respeto por la senda solitaria del honor.',
                'shareability_mechanism' => 'Símbolo de identidad para quienes eligen la senda exigente sin buscar aprobación.',
                'creative_fit_score' => 9.6,
                'why_explanation' => 'Eleva la soledad disciplinada de estigma social a insignia de honor marcial.'
            ];
        }

        if (str_contains($combined, 'lider') || str_contains($combined, 'miedo') || str_contains($combined, 'poder') || str_contains($combined, 'autoridad')) {
            return [
                'core_concept' => 'La verdadera autoridad emana de la coherencia interna y no de la coacción.',
                'conflict' => 'La tentación de someter con amenazas vs la templanza de inspirar con hechos.',
                'transformation' => 'Gobernar primero tu propia mente antes de pretender dirigir a otros.',
                'hook_type' => 'Deconstrucción de Poder',
                'sentence_structure' => 'Antítesis tirano/líder -> Quiebre de la máscara -> Conclusión de liderazgo estoico.',
                'audience_pain' => 'Frustración con líderes incoherentes o miedo a imponer límites.',
                'belief_challenged' => 'Confundir agresividad con fortaleza de carácter.',
                'emotional_trigger' => 'Autoridad serena que inspira respeto natural sin alzar la voz.',
                'shareability_mechanism' => 'Manifiesto de conducta que el usuario comparte para señalar liderazgo real.',
                'creative_fit_score' => 9.0,
                'why_explanation' => 'Diferencia el poder ruidoso e inseguro de la autoridad tranquila basada en hechos.'
            ];
        }

        return [
            'core_concept' => 'Soberanía mental y dicotomía del control ante los embates de la vida.',
            'conflict' => 'Gastar energía en circunstancias incontrolables vs enfocarse en la propia respuesta.',
            'transformation' => 'Aceptar lo externo sin quejas y ejecutar lo propio con excelencia implacable.',
            'hook_type' => 'Quiebre de Perspectiva',
            'sentence_structure' => 'Principio cardinal -> Distinción entre controlable e incontrolable -> Regla de acción.',
            'audience_pain' => 'Ansiedad y desgaste por intentar controlar el comportamiento de los demás.',
            'belief_challenged' => 'Creer que la paz mental depende de que el mundo exterior sea justo.',
            'emotional_trigger' => 'Liberación psicológica inmediata al soltar lo que no depende de uno.',
            'shareability_mechanism' => 'Recordatorio de sabiduría práctica que cualquiera necesita releer a diario.',
            'creative_fit_score' => 9.1,
            'why_explanation' => 'Devuelve el 100% de la responsabilidad y el alivio a la esfera del control personal.'
        ];
    }

    /**
     * Genera la dirección visual y prompt para Midjourney v6
     */
    public static function generateVisualDirectorPrompt(array $dna, string $theme = ''): array {
        $themeLower = mb_strtolower($theme, 'UTF-8');
        $concept = $dna['core_concept'] ?? '';

        if (str_contains($themeLower, 'samur') || str_contains($themeLower, 'bushido') || str_contains($concept, 'guerrero') || str_contains($concept, 'marcial')) {
            return [
                'subject' => 'Samurái en meditación profunda bajo suave lluvia nocturna, postura inamovible con katana apoyada frente a él',
                'environment' => 'Patio de piedra milenario de un templo zen, musgo oscuro y linternas de piedra apagadas',
                'atmosphere' => 'Claroscuro cinematográfico, reflejos de agua en la piedra, tenue luz dorada de borde (rim light)',
                'camera' => 'Lente 35mm anamórfico, f/1.8 profundidad de campo reducida, grano de película cinematográfico 35mm',
                'midjourney_prompt' => 'Cinematic chiaroscuro portrait of a stoic samurai warrior meditating motionless in the rain, ancient stone temple courtyard, subtle golden rim light cutting through darkness, photorealistic, 8k resolution, Kodak Portra film grain, dramatic shadows, moody atmosphere --ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime'
            ];
        }

        if (str_contains($themeLower, 'tiempo') || str_contains($concept, 'memento') || str_contains($concept, 'muerte')) {
            return [
                'subject' => 'Reloj de arena de bronce antiguo con arena oscura cayendo, junto a un casco espartano desgastado por batallas',
                'environment' => 'Mesa de madera maciza de roble en una biblioteca clásica en penumbra',
                'atmosphere' => 'Rayo de luz solar dorada oblicua atravesando el polvo en el aire, sombras profundas y misteriosas',
                'camera' => 'Lente 50mm macro f/2.0, nitidez microscópica en el cristal y el bronce, bokeh cinematográfico suave',
                'midjourney_prompt' => 'Cinematic memento mori still life, antique bronze hourglass with dark sand beside a weathered spartan battle helmet, single dramatic ray of golden sun piercing dark library shadows, ultra-detailed textures, 8k resolution, shot on 35mm --ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime'
            ];
        }

        // Busto clásico romano en mármol oscuro / obsidiana (Estética insignia Fortaleza Imparable)
        return [
            'subject' => 'Busto imperial de Marco Aurelio tallado en mármol negro y obsidiana con sutiles fracturas doradas estilo Kintsugi',
            'environment' => 'Cámara de templo romano en ruinas con niebla densa y columnas de piedra estriadas',
            'atmosphere' => 'Iluminación lateral dramática de claroscuro renacentista, contraluz dorado tenue, penumbra envolvente',
            'camera' => 'Lente Zeiss 35mm f/1.8, enfoque nítido en el rostro sereno, grano de película sutil, textura hiperrealista',
            'midjourney_prompt' => 'Cinematic dark fine art portrait of Marcus Aurelius carved in weathered obsidian marble with subtle golden Kintsugi veins, dramatic Rembrandt chiaroscuro lighting, volumetric fog, deep charcoal shadows, 8k resolution, shot on 35mm film, hyper-realistic stone texture --ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime'
        ];
    }

    /**
     * Generador Principal de Atenea:
     * Deconstruye el ADN psicológico a partir de la frase de la imagen y el copy original,
     * y forja exactamente 4 FRASES ORIGINALES DE ALTO IMPACTO (8 a 22 palabras cada una)
     * para estampar en placas visuales / portadas de Reels/Vídeos de @fortaleza_imparable.
     */
    /**
     * Generador Principal de Atenea:
     * Deconstruye el ADN psicológico a partir de la frase de la imagen (VISUAL_TEXT) y el copy original (CAPTION_TEXT),
     * preservando estrictamente el núcleo semántico de la frase visual sin semantic drift ni coincidencias léxicas falsas,
     * y forja exactamente 4 FRASES ORIGINALES DE ALTO IMPACTO (8 a 22 palabras cada una)
     * para estampar en placas visuales / portadas de Reels/Vídeos de @fortaleza_imparable.
     */
    public static function generateFortalezaRecreations(
        int $userId,
        int $postId,
        int $brandVoiceId = 1,
        bool $forceRegenerate = false,
        string $sourceType = 'inspiration',
        ?string $customVisualText = null,
        ?string $customCaption = null
    ): array {
        try {
            $pdo = Database::getConnection();
            $post = null;

            // 1. Si el origen es histórico de Atenea Learning o el postId no está en inspiration_posts, buscar en atenea_post_dna
            if ($sourceType === 'historical' || $sourceType === 'dna') {
                $stmtDna = $pdo->prepare("
                    SELECT d.*, p.raw_likes, p.raw_comments, p.raw_shares, p.raw_saves,
                           p.overall_performance_score, p.performance_tier
                    FROM atenea_post_dna d
                    LEFT JOIN atenea_performance_dna p ON p.post_dna_id = d.id
                    WHERE d.id = ? AND d.user_id = ?
                ");
                $stmtDna->execute([$postId, $userId]);
                $dnaRow = $stmtDna->fetch(PDO::FETCH_ASSOC);
                $stmtDna->closeCursor();

                if ($dnaRow) {
                    $extKey = 'top_dna_' . $dnaRow['id'];
                    $stmtFind = $pdo->prepare("SELECT * FROM inspiration_posts WHERE user_id = ? AND external_post_id = ?");
                    $stmtFind->execute([$userId, $extKey]);
                    $post = $stmtFind->fetch(PDO::FETCH_ASSOC);
                    $stmtFind->closeCursor();

                    if (!$post) {
                        $oppScore = 9.8;
                        $fitScore = 10.0;
                        $insQuote = $dnaRow['overlay_quote'] ?: ($dnaRow['core_concept'] ?: mb_substr($dnaRow['caption'] ?? '', 0, 140));
                        $insStmt = $pdo->prepare("
                            INSERT INTO inspiration_posts (
                                user_id, creator_id, platform, permalink, caption, quote_extracted, quote_author,
                                quote_verified_status, quote_source_note, theme, likes_count, comments_count,
                                engagement_score, opportunity_score, creative_fit_score, external_post_id, content_dna,
                                visual_text, visual_text_source, visual_text_status, visual_text_confidence, caption_text
                            ) VALUES (
                                :uid, NULL, :plat, '', :cap, :quote, :author,
                                'verified_authentic', :note, :theme, :likes, :comments,
                                :eng, :opp, :fit, :ext, :dna,
                                :vtext, 'DNA_HISTORIC', 'CONFIRMED', 0.95, :captext
                            )
                        ");
                        $insStmt->execute([
                            ':uid' => $userId,
                            ':plat' => $dnaRow['platform'] ?? 'instagram',
                            ':cap' => $dnaRow['caption'] ?? '',
                            ':quote' => $insQuote,
                            ':author' => 'Fortaleza Imparable (@fortaleza_imparable)',
                            ':note' => 'Publicación histórica Top Viral (' . ($dnaRow['performance_tier'] ?? 'TOP_10') . ') con ' . number_format((float)($dnaRow['raw_likes'] ?? 0)) . ' interacciones.',
                            ':theme' => $dnaRow['theme'] ?: 'Vencedor Solitario y Forja Interior',
                            ':likes' => (int)($dnaRow['raw_likes'] ?? 0),
                            ':comments' => (int)($dnaRow['raw_comments'] ?? 0),
                            ':eng' => (float)($dnaRow['overall_performance_score'] ?? 0),
                            ':opp' => $oppScore,
                            ':fit' => $fitScore,
                            ':ext' => $extKey,
                            ':dna' => json_encode([
                                'core_concept' => $dnaRow['core_concept'] ?? $insQuote,
                                'conflict' => $dnaRow['conflict'] ?? 'El impulso de rendirse vs el deber de forjar carácter',
                                'transformation' => $dnaRow['transformation'] ?? 'Dejar de esperar validación ajena para actuar con soberanía propia',
                                'hook_type' => $dnaRow['hook_type'] ?? 'Paradoja Contraintuitiva',
                                'sentence_structure' => $dnaRow['sentence_structure'] ?? 'Axioma Corto Aforístico',
                                'audience_pain' => $dnaRow['audience_pain'] ?? 'Miedo a no encajar y necesidad de aprobación grupal',
                                'belief_challenged' => $dnaRow['belief_challenged'] ?? 'Creer que estar solo es señal de fracaso o debilidad',
                                'emotional_trigger' => $dnaRow['emotional_trigger'] ?? 'Identidad Guerrera y Soledad Constructiva',
                                'shareability_mechanism' => $dnaRow['shareability_mechanism'] ?? 'Orgullo de caminar solo por elección propia',
                                'why_explanation' => 'Frase histórica récord de @fortaleza_imparable con alto enganche y retención comprobada en tu audiencia.'
                            ], JSON_UNESCAPED_UNICODE),
                            ':vtext' => $dnaRow['overlay_quote'] ?: null,
                            ':captext' => $dnaRow['caption'] ?? ''
                        ]);
                        $newId = (int)$pdo->lastInsertId();
                        $insStmt->closeCursor();

                        $stmtGet = $pdo->prepare("SELECT * FROM inspiration_posts WHERE id = ?");
                        $stmtGet->execute([$newId]);
                        $post = $stmtGet->fetch(PDO::FETCH_ASSOC);
                        $stmtGet->closeCursor();
                        $postId = $newId;
                    } else {
                        $postId = (int)$post['id'];
                    }
                }
            }

            if (!$post) {
                $stmt = $pdo->prepare("SELECT * FROM inspiration_posts WHERE id = ? AND user_id = ?");
                $stmt->execute([$postId, $userId]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            }

            // Fallback secundario a atenea_post_dna si no se encontró en inspiration_posts
            if (!$post) {
                $stmtDnaAlt = $pdo->prepare("
                    SELECT d.*, p.raw_likes, p.raw_comments, p.raw_shares, p.raw_saves,
                           p.overall_performance_score, p.performance_tier
                    FROM atenea_post_dna d
                    LEFT JOIN atenea_performance_dna p ON p.post_dna_id = d.id
                    WHERE d.id = ? AND d.user_id = ?
                ");
                $stmtDnaAlt->execute([$postId, $userId]);
                $dnaRowAlt = $stmtDnaAlt->fetch(PDO::FETCH_ASSOC);
                $stmtDnaAlt->closeCursor();

                if ($dnaRowAlt) {
                    return self::generateFortalezaRecreations($userId, $postId, $brandVoiceId, $forceRegenerate, 'historical', $customVisualText, $customCaption);
                }

                return ['success' => false, 'error' => 'Publicación de inspiración no encontrada'];
            }

            // Supervisión Humana: Si el usuario envía texto visual o caption editado manualmente
            $hasCustomInputs = ($customVisualText !== null || $customCaption !== null);
            if ($hasCustomInputs) {
                $forceRegenerate = true;
                if ($customVisualText !== null) {
                    $trimmedVis = trim($customVisualText);
                    $post['visual_text'] = $trimmedVis !== '' ? $trimmedVis : null;
                    $post['visual_text_source'] = $trimmedVis !== '' ? 'USER' : 'NONE';
                    $post['visual_text_status'] = $trimmedVis !== '' ? 'USER_CONFIRMED' : 'NO_TEXT';
                    $post['visual_text_confidence'] = $trimmedVis !== '' ? 1.0 : 0.0;
                    try {
                        $upVis = $pdo->prepare("
                            UPDATE inspiration_posts 
                            SET visual_text = ?, visual_text_source = ?, visual_text_status = ?, visual_text_confidence = ? 
                            WHERE id = ? AND user_id = ?
                        ");
                        $upVis->execute([$post['visual_text'], $post['visual_text_source'], $post['visual_text_status'], $post['visual_text_confidence'], $postId, $userId]);
                        $upVis->closeCursor();
                    } catch (Throwable) {}
                }

                if ($customCaption !== null) {
                    $trimmedCap = trim($customCaption);
                    $post['caption'] = $trimmedCap;
                    $post['caption_text'] = $trimmedCap;
                    try {
                        $upCap = $pdo->prepare("
                            UPDATE inspiration_posts 
                            SET caption = ?, caption_text = ? 
                            WHERE id = ? AND user_id = ?
                        ");
                        $upCap->execute([$trimmedCap, $trimmedCap, $postId, $userId]);
                        $upCap->closeCursor();
                    } catch (Throwable) {}
                }
            }

            // Calcular y persistir scores duales si están ausentes
            $opportunityScore = (float)($post['opportunity_score'] ?? 0);
            if ($opportunityScore <= 0) {
                $opportunityScore = self::calculateOpportunityScore($post);
            }

            $creativeFitScore = (float)($post['creative_fit_score'] ?? 0);
            if ($creativeFitScore <= 0) {
                $creativeFitScore = self::calculateCreativeFitScore($post);
            }

            try {
                $upScores = $pdo->prepare("UPDATE inspiration_posts SET opportunity_score = ?, creative_fit_score = ? WHERE id = ?");
                $upScores->execute([$opportunityScore, $creativeFitScore, $postId]);
                $upScores->closeCursor();
                $post['opportunity_score'] = $opportunityScore;
                $post['creative_fit_score'] = $creativeFitScore;
            } catch (Throwable) {}

            $visualText = !empty($post['visual_text']) ? trim($post['visual_text']) : null;
            $visualTextStatus = $post['visual_text_status'] ?? 'UNAVAILABLE';
            $visualTextSource = $post['visual_text_source'] ?? 'NONE';
            $visualTextConfidence = (float)($post['visual_text_confidence'] ?? 0.0);
            $caption = trim($post['caption_text'] ?? ($post['caption'] ?? ''));

            // Si ya tiene recreaciones cacheadas y no se fuerza regeneración ni hubo edición manual, devolverlas
            if (!$forceRegenerate && !$hasCustomInputs && !empty($post['recreated_copies'])) {
                $cached = json_decode($post['recreated_copies'], true);
                if (is_array($cached) && (!empty($cached['phrase_hook']) || !empty($cached['option_short']))) {
                    $quoteText = $visualText ?: $caption;
                    $dna = !empty($post['content_dna']) 
                        ? (is_array($post['content_dna']) ? $post['content_dna'] : json_decode($post['content_dna'], true))
                        : ($cached['content_dna'] ?? self::extractContentDna($caption, $post['theme'] ?? '', $quoteText));
                    $visualDirector = $cached['visual_director'] ?? self::generateVisualDirectorPrompt($dna, $post['theme'] ?? '');

                    $phraseHook = $cached['phrase_hook'] ?? ($cached['option_short'] ?? '');
                    $phraseContrarian = $cached['phrase_contrarian'] ?? ($cached['option_reflective'] ?? '');
                    $phraseWarrior = $cached['phrase_warrior'] ?? ($cached['option_warrior'] ?? '');
                    $phraseStoic = $cached['phrase_stoic'] ?? ($cached['option_stoic'] ?? '');
                    $whyExplanation = $cached['why_it_works'] ?? ($dna['why_explanation'] ?? 'Alineación de impacto psicológico sobre la disciplina y soberanía mental.');

                    return [
                        'success' => true,
                        'from_cache' => true,
                        'post' => $post,
                        'opportunity_score' => $opportunityScore,
                        'creative_fit_score' => $creativeFitScore,
                        'why_it_works' => $whyExplanation,
                        'reference_post' => [
                            'visual_text' => $visualText,
                            'visual_text_source' => $visualTextSource,
                            'visual_text_status' => $visualTextStatus,
                            'visual_text_confidence' => $visualTextConfidence,
                            'caption_text' => $caption,
                            'caption' => $caption,
                            'quote' => $visualText ?: $caption,
                            'author' => $post['quote_author'] ?: 'Fortaleza Imparable',
                            'theme' => $post['theme'] ?: 'Disciplina y Carácter Estoico',
                            'status' => $post['quote_verified_status'] ?: 'verified_authentic'
                        ],
                        'dna' => $dna,
                        'phrases' => [
                            'hook' => $phraseHook,
                            'contrarian' => $phraseContrarian,
                            'warrior' => $phraseWarrior,
                            'stoic' => $phraseStoic
                        ],
                        'recreations' => [
                            'phrase_hook' => $phraseHook,
                            'phrase_contrarian' => $phraseContrarian,
                            'phrase_warrior' => $phraseWarrior,
                            'phrase_stoic' => $phraseStoic,
                            'option_short' => $phraseHook,
                            'option_reflective' => $phraseContrarian,
                            'option_warrior' => $phraseWarrior,
                            'option_stoic' => $phraseStoic,
                            'visual_prompt' => $cached['visual_prompt'] ?? ($visualDirector['midjourney_prompt'] ?? ''),
                            'image_prompt' => $cached['visual_prompt'] ?? ($visualDirector['midjourney_prompt'] ?? '')
                        ],
                        'visual_director' => $visualDirector
                    ];
                }
            }

            if (empty($post['theme'])) {
                $heuristicRef = self::heuristicQuoteAnalysis($caption);
                $post['theme'] = $heuristicRef['theme'] ?? 'Disciplina y Carácter Estoico';
            }
            if (empty($post['quote_author'])) {
                $post['quote_author'] = 'Fortaleza Imparable';
            }

            $theme = $post['theme'];
            $author = $post['quote_author'];

            $apiKey = Settings::get('openrouter_api_key', '', $userId);
            $aiModel = Settings::get('openrouter_model', 'nousresearch/hermes-3-llama-3.1-70b', $userId);

            // Obtener directivas y patrones empíricos aprendidos de la propia audiencia de @fortaleza_imparable
            $learnedDirectives = AteneaLearningEngine::getActivePatternsForPrompt($userId);

            $systemPrompt = "Eres ATENEA, la Directora de Estrategia de Contenido y Filosofía de 'Fortaleza Imparable' (@fortaleza_imparable). Eres una estratega maestra en psicología estoica grecorromana y ética marcial samurái (Bushido / Dokkōdō).

DIRECTIVA CARDINAL: Tu misión es deconstruir el ADN psicológico de la publicación original y sintetizar exactamente 4 FRASES ORIGINALES DE ALTO IMPACTO (8 a 22 palabras cada una), listas para ser estampadas como texto principal en placas visuales y portadas de @fortaleza_imparable.

REGLAS DE RIGOR FILOSÓFICO Y PREVENCIÓN DE DRIFT SEMÁNTICO:
1. SEPARACIÓN ESTRICTA DE FUENTES:
   - 'VISUAL_TEXT': Es la frase nuclear grabada en la imagen/placa original. Representa el núcleo temático INDISCUTIBLE.
   - 'CAPTION_TEXT': Es el pie de foto de la publicación. Sirve exclusivamente como contexto complementario o secundario.
2. PRESERVACIÓN DEL NÚCLEO SEMÁNTICO (ANTI-DRIFT):
   - Las 4 propuestas DEBEN preservar el núcleo semántico de VISUAL_TEXT y NO introducir un tema dominante diferente.
   - Ejemplo de rigor: Si VISUAL_TEXT aborda gratitud, lealtad, memoria moral o reciprocidad (ej. 'Nunca olvides a quien te invitó a sentarte a la mesa...'), las 4 frases DEBEN ser sobre gratitud, honor, lealtad y reciprocidad.
   - PROHIBICIÓN ESTRICTA DE DRIFT POR COINCIDENCIA LÉXICA: Está terminantemente prohibido desviar la generación hacia 'Memento Mori', 'brevedad de la vida' o 'la muerte' simplemente porque en el caption aparezca la palabra 'vida', 'olvido' o un verbo similar. No confundas una coincidencia léxica con comprensión semántica.
3. ANTI-CLICHÉ ESTRICTO: Queda terminantemente prohibido usar frases trilladas ('sé imparable', 'conviértete en bestia', 'sal de tu zona de confort'). Emplea sobriedad aforística, filo intelectual y peso moral.
4. RIGOR HISTÓRICO: Diferencia nítidamente la tradición estoica grecorromana (Marco Aurelio, Séneca, Epicteto) de la tradición marcial japonesa (Miyamoto Musashi, Dokkōdō, Hagakure). Jamás clasifiques a Musashi como 'estoico'.
5. EXTENSIÓN ESTRICTA: Cada una de las 4 frases debe tener entre 8 y 22 palabras exactas.
6. Responde SIEMPRE única y exclusivamente en formato JSON estricto sin markdown ni preámbulos.";

            if (!empty($learnedDirectives)) {
                $systemPrompt .= "\n\n" . $learnedDirectives;
            }

            $visualTextDisplay = !empty($visualText) 
                ? "\"{$visualText}\"" 
                : "[NO DISPONIBLE / SIN TEXTO IDENTIFICADO EN LA IMAGEN]";

            $userPrompt = <<<PROMPT
Analiza la siguiente publicación de referencia respetando la estricta jerarquía de fuentes:

[FUENTE 1 - NÚCLEO PRINCIPAL]
- VISUAL_TEXT (Texto en la imagen / placa): {$visualTextDisplay}
  * Estado: {$visualTextStatus} | Fuente: {$visualTextSource}

[FUENTE 2 - CONTEXTO SECUNDARIO]
- CAPTION_TEXT (Pie de foto original): "{$caption}"
- Autor atribuido: {$author}
- Tema de partida: {$theme}

DIRECTIVA DE GENERACIÓN:
Conserva rigurosamente el núcleo semántico de VISUAL_TEXT (o de CAPTION_TEXT si VISUAL_TEXT no estuviera disponible). No sustituyas el tema central por temas ajenos como Memento Mori o la muerte salvo que VISUAL_TEXT verse explícitamente sobre ello.

Genera las 4 FRASES AFORÍSTICAS ORIGINALES para placas visuales de @fortaleza_imparable (8 a 22 palabras cada una):

1. "phrase_hook" (Gancho Brutal / Golpe Psicológico):
   - Frase afilada y cortante que frena el scroll desarmando la complacencia del lector sobre este núcleo semántico. (8 a 22 palabras).

2. "phrase_contrarian" (Antítesis / Rompe-Creencias):
   - Frase contraintuitiva que desafía el sentido común convencional sobre este núcleo semántico. (8 a 22 palabras).

3. "phrase_warrior" (Disciplina & Forja / Dokkōdō / Bushido):
   - Inspirada en la ética de Miyamoto Musashi: rigor, soledad constructiva, templanza y fidelidad al deber sobre este núcleo. (8 a 22 palabras).

4. "phrase_stoic" (Soberanía Mental / Virtud Moral):
   - Inspirada en Séneca, Epicteto o Marco Aurelio: dominio del juicio interior y virtud moral sobre este núcleo. (8 a 22 palabras).

5. "why_it_works":
   - Explicación de 2 líneas sintetizando el mecanismo psicológico que hace memorable este ángulo.

6. "creative_fit_score":
   - Calificación de 1.0 a 10.0 de afinidad con la identidad de Fortaleza Imparable.

7. Dirección Visual Cinematográfica para Midjourney v6 en inglés (--ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime).

Estructura requerida en JSON estricto:
{
  "content_dna": {
    "core_concept": "Principio filosófico nuclear en 1 oración (fiel al núcleo de VISUAL_TEXT)",
    "conflict": "La tensión interna entre comodidad y carácter",
    "transformation": "El cambio de mentalidad exigido al lector",
    "hook_type": "Clasificación (ej. Paradoja Contraintuitiva, Golpe de Realidad, Axioma Moral)",
    "sentence_structure": "Patrón sintáctico utilizado",
    "audience_pain": "Herida o debilidad oculta que sufre la audiencia",
    "belief_challenged": "Creencia complaciente que se desmonta",
    "emotional_trigger": "Gatillo emocional de impacto",
    "shareability_mechanism": "Razón psicológica por la que se comparte o guarda"
  },
  "why_it_works": "2 líneas explicando por qué este ángulo psicológico conecta con el rigor y carácter...",
  "creative_fit_score": 9.3,
  "phrase_hook": "Frase aforística gancho (8 a 22 palabras)...",
  "phrase_contrarian": "Frase aforística antítesis (8 a 22 palabras)...",
  "phrase_warrior": "Frase aforística guerrera (8 a 22 palabras)...",
  "phrase_stoic": "Frase aforística estoica (8 a 22 palabras)...",
  "visual_director": {
    "subject": "Descripción del sujeto...",
    "environment": "Descripción del entorno...",
    "atmosphere": "Descripción de iluminación y sombras...",
    "camera": "Lente y especificación técnica...",
    "midjourney_prompt": "Cinematic dark fine art... --ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime"
  }
}
PROMPT;

            $parsed = null;
            $lastError = '';

            // Validación fail-closed de credenciales OpenRouter
            if (empty($apiKey)) {
                return [
                    'success' => false,
                    'generation_eligible' => false,
                    'error' => 'No hay una API Key de OpenRouter configurada en el sistema. Configúrala en Ajustes para generar recreaciones con IA.',
                    'post' => $post
                ];
            }

            // Llamada robusta a OpenRouter con clasificación HTTP, timeouts calibrados y reintentos limitados
            $maxRetries = 2;
            $startTime = microtime(true);
            $maxTotalSeconds = 55.0;

            $payload = [
                'model' => $aiModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.75,
                'max_tokens' => 1200
            ];

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                if ((microtime(true) - $startTime) > $maxTotalSeconds) {
                    $lastError = "Límite total de tiempo de operación superado ({$maxTotalSeconds}s).";
                    break;
                }

                $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_TIMEOUT => 45,
                    CURLOPT_CONNECTTIMEOUT => 8,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                        'HTTP-Referer: https://xindro.app',
                        'X-Title: XINDRO Social AI'
                    ]
                ]);

                $res = curl_exec($ch);
                $curlErr = curl_error($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $res) {
                    $data = json_decode($res, true);
                    $content = trim($data['choices'][0]['message']['content'] ?? '');
                    $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
                    $content = preg_replace('/\s*```$/', '', $content);

                    if (preg_match('/\{.*\}/s', $content, $m)) {
                        $jsonCandidate = json_decode($m[0], true);
                        if (!empty($jsonCandidate['phrase_hook']) || !empty($jsonCandidate['option_short'])) {
                            $parsed = $jsonCandidate;
                            break; // Generación completada exitosamente
                        }
                    }
                    $lastError = "La respuesta de OpenRouter no contenía las 4 frases aforísticas esperadas.";
                } elseif ($httpCode === 401 || $httpCode === 403 || $httpCode === 400) {
                    // Errores de cliente o autenticación: fail-fast inmediato sin reintento inútil
                    $lastError = "Error de cliente HTTP {$httpCode} en OpenRouter: " . ($res ?: $curlErr);
                    break;
                } elseif ($httpCode === 429) {
                    // Rate limit: backoff breve
                    $lastError = "Rate limit (HTTP 429) en OpenRouter. Reintento {$attempt}/{$maxRetries}...";
                    if ($attempt < $maxRetries) {
                        usleep(2000000); // 2 segundos
                    }
                } elseif ($httpCode >= 500) {
                    // Error de servidor: reintento con backoff
                    $lastError = "Error de servidor OpenRouter (HTTP {$httpCode}). Reintento {$attempt}/{$maxRetries}...";
                    if ($attempt < $maxRetries) {
                        usleep(1500000); // 1.5 segundos
                    }
                } else {
                    // Timeout o error de red
                    $lastError = "Error de conexión o timeout cURL: " . ($curlErr ?: "HTTP {$httpCode}");
                    if ($attempt < $maxRetries) {
                        usleep(1000000); // 1 segundo
                    }
                }
            }

            // FAIL-CLOSED: Si el LLM no generó la respuesta, reportar error explícito. NUNCA emitir frases heurísticas inventadas.
            if ($parsed === null) {
                return [
                    'success' => false,
                    'generation_eligible' => false,
                    'error' => "Atenea no pudo generar las recreaciones a través del modelo de IA: {$lastError}. Por favor, verifica tu clave de OpenRouter o reintenta en unos instantes.",
                    'post' => $post
                ];
            }

            $pHook = $parsed['phrase_hook'] ?? ($parsed['option_short'] ?? '');
            $pContrarian = $parsed['phrase_contrarian'] ?? ($parsed['option_reflective'] ?? '');
            $pWarrior = $parsed['phrase_warrior'] ?? ($parsed['option_warrior'] ?? '');
            $pStoic = $parsed['phrase_stoic'] ?? ($parsed['option_stoic'] ?? '');

            $dna = $parsed['content_dna'] ?? self::extractContentDna($caption, $theme, $visualText ?: $caption);
            $visualDirector = $parsed['visual_director'] ?? self::generateVisualDirectorPrompt($dna, $theme);
            $visualPrompt = $visualDirector['midjourney_prompt'] ?? ($parsed['image_prompt'] ?? '');
            $whyWorks = $parsed['why_it_works'] ?? ($dna['why_explanation'] ?? 'Alineación psicológica que conecta con la necesidad de rigor y soberanía interior.');
            $cFitScore = !empty($parsed['creative_fit_score']) ? (float)$parsed['creative_fit_score'] : $creativeFitScore;

            $parsed['phrase_hook'] = $pHook;
            $parsed['phrase_contrarian'] = $pContrarian;
            $parsed['phrase_warrior'] = $pWarrior;
            $parsed['phrase_stoic'] = $pStoic;
            $parsed['why_it_works'] = $whyWorks;
            $parsed['creative_fit_score'] = $cFitScore;
            $parsed['visual_prompt'] = $visualPrompt;
            $parsed['visual_director'] = $visualDirector;
            $parsed['content_dna'] = $dna;

            try {
                self::persistAteneaCreations($pdo, $userId, $postId, $dna, $parsed, $opportunityScore, $cFitScore);
            } catch (Throwable) {}

            return [
                'success' => true,
                'from_cache' => false,
                'post' => $post,
                'opportunity_score' => $opportunityScore,
                'creative_fit_score' => $cFitScore,
                'why_it_works' => $whyWorks,
                'reference_post' => [
                    'visual_text' => $visualText,
                    'visual_text_source' => $visualTextSource,
                    'visual_text_status' => $visualTextStatus,
                    'visual_text_confidence' => $visualTextConfidence,
                    'caption_text' => $caption,
                    'caption' => $caption,
                    'quote' => $visualText ?: $caption,
                    'author' => $author,
                    'theme' => $theme,
                    'status' => $post['quote_verified_status'] ?? 'verified_authentic'
                ],
                'dna' => $dna,
                'phrases' => [
                    'hook' => $pHook,
                    'contrarian' => $pContrarian,
                    'warrior' => $pWarrior,
                    'stoic' => $pStoic
                ],
                'recreations' => [
                    'phrase_hook' => $pHook,
                    'phrase_contrarian' => $pContrarian,
                    'phrase_warrior' => $pWarrior,
                    'phrase_stoic' => $pStoic,
                    'option_short' => $pHook,
                    'option_reflective' => $pContrarian,
                    'option_warrior' => $pWarrior,
                    'option_stoic' => $pStoic,
                    'visual_prompt' => $visualPrompt,
                    'image_prompt' => $visualPrompt
                ],
                'visual_director' => $visualDirector,
                'learned_patterns_applied' => !empty($learnedDirectives)
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar generateFortalezaRecreations: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al generar recreaciones: ' . $e->getMessage()];
        }
    }

    /**
     * Persiste el ADN psicológico y las 4 frases aforísticas en las tablas de memoria de Atenea
     */
    private static function persistAteneaCreations(PDO $pdo, int $userId, int $postId, array $dna, array $parsed, float $opportunityScore, float $creativeFitScore): void {
        for ($attempt = 0; $attempt < 4; $attempt++) {
            try {
                // 1. Guardar en inspiration_posts
                $upStmt = $pdo->prepare("
                    UPDATE inspiration_posts SET 
                        recreated_copies = :recreated,
                        content_dna = :dna,
                        opportunity_score = :score,
                        creative_fit_score = :cfit
                    WHERE id = :id AND user_id = :uid
                ");
                $upStmt->execute([
                    ':recreated' => json_encode($parsed, JSON_UNESCAPED_UNICODE),
                    ':dna' => json_encode($dna, JSON_UNESCAPED_UNICODE),
                    ':score' => $opportunityScore,
                    ':cfit' => $creativeFitScore,
                    ':id' => $postId,
                    ':uid' => $userId
                ]);
                $upStmt->closeCursor();

                // 2. Guardar en atenea_content_dna con dimensiones psicológicas ampliadas
                $whyText = $parsed['why_it_works'] ?? ($dna['why_explanation'] ?? '');
                $dnaStmt = $pdo->prepare("
                    INSERT INTO atenea_content_dna (
                        user_id, post_id, core_concept, conflict, transformation, hook_type, sentence_structure,
                        opportunity_score, creative_fit_score, audience_pain, belief_challenged, emotional_trigger,
                        shareability_mechanism, why_explanation
                    ) VALUES (
                        :uid, :pid, :core, :conflict, :trans, :hook, :struct,
                        :score, :cfit, :pain, :belief, :trigger,
                        :share, :why
                    )
                ");
                $dnaStmt->execute([
                    ':uid' => $userId,
                    ':pid' => $postId,
                    ':core' => $dna['core_concept'] ?? '',
                    ':conflict' => $dna['conflict'] ?? '',
                    ':trans' => $dna['transformation'] ?? '',
                    ':hook' => $dna['hook_type'] ?? '',
                    ':struct' => $dna['sentence_structure'] ?? '',
                    ':score' => $opportunityScore,
                    ':cfit' => $creativeFitScore,
                    ':pain' => $dna['audience_pain'] ?? '',
                    ':belief' => $dna['belief_challenged'] ?? '',
                    ':trigger' => $dna['emotional_trigger'] ?? '',
                    ':share' => $dna['shareability_mechanism'] ?? '',
                    ':why' => $whyText
                ]);
                $dnaStmt->closeCursor();

                // 3. Guardar en atenea_creations_memory para cada una de las 4 ramas aforísticas
                $variations = [
                    'hook_brutal' => $parsed['phrase_hook'] ?? ($parsed['option_short'] ?? ''),
                    'contrarian' => $parsed['phrase_contrarian'] ?? ($parsed['option_reflective'] ?? ''),
                    'warrior' => $parsed['phrase_warrior'] ?? ($parsed['option_warrior'] ?? ''),
                    'stoic' => $parsed['phrase_stoic'] ?? ($parsed['option_stoic'] ?? '')
                ];

                $visPrompt = $parsed['visual_prompt'] ?? ($parsed['image_prompt'] ?? '');
                $visMatrix = json_encode($parsed['visual_director'] ?? [], JSON_UNESCAPED_UNICODE);

                $memStmt = $pdo->prepare("
                    INSERT INTO atenea_creations_memory (
                        user_id, inspiration_post_id, variation_type, hook, full_copy, visual_prompt, visual_matrix, status
                    ) VALUES (
                        :uid, :pid, :vtype, :hook, :copy, :vprompt, :vmatrix, 'draft'
                    )
                ");

                foreach ($variations as $type => $phraseText) {
                    if (!empty($phraseText)) {
                        try {
                            $memStmt->execute([
                                ':uid' => $userId,
                                ':pid' => $postId,
                                ':vtype' => $type,
                                ':hook' => mb_substr($phraseText, 0, 80),
                                ':copy' => $phraseText,
                                ':vprompt' => $visPrompt,
                                ':vmatrix' => $visMatrix
                            ]);
                            $memStmt->closeCursor();
                        } catch (Throwable) {}
                    }
                }
                return;
            } catch (Throwable $e) {
                if ($attempt === 3) {
                    error_log("Failed persisting Atenea creations after retries: " . $e->getMessage());
                } else {
                    usleep(150000); // 150ms backoff para liberar locks en SQLite
                }
            }
        }
    }

    /**
     * Fallback heurístico inteligente de Atenea:
     * Genera 4 frases aforísticas breves (8 a 22 palabras cada una), ADN psicológico completo y dirección visual.
     */
    private static function generateDynamicHeuristicAtenea(string $quote, string $caption, string $author, string $theme, array $post, float $opportunityScore, float $creativeFitScore): array {
        $dna = self::extractContentDna($caption, $theme, $quote);
        $visualDirector = self::generateVisualDirectorPrompt($dna, $theme);
        $combinedText = mb_strtolower(trim(strip_tags($quote . ' ' . $caption)), 'UTF-8');
        $themeLower = mb_strtolower($theme, 'UTF-8');

        if (str_contains($themeLower, 'lider') || str_contains($combinedText, 'miedo') || str_contains($combinedText, 'autoridad') || str_contains($combinedText, 'poder')) {
            $pHook = "El miedo es el disfraz del tirano débil; la verdadera autoridad inspira por coherencia, no por sumisión. 🏛️⚡";
            $pContrarian = "Quien necesita alzar la voz para hacerse obedecer ya ha perdido el respeto de su propia gente. 🏛️";
            $pWarrior = "El samurái comanda su propio espíritu antes de atreverse a dirigir el filo hacia los demás. ⚔️";
            $pStoic = "Desea gobernar sobre otros únicamente aquel que todavía es esclavo de sus propias pasiones desordenadas. 🏛️";
            $whyExplanation = "Desmonta el falso liderazgo basado en la agresividad y establece la templanza como la máxima demostración de poder.";
        } elseif (str_contains($themeLower, 'silencio') || str_contains($combinedText, 'silencio') || str_contains($combinedText, 'palabras')) {
            $pHook = "El sabio calla porque conoce el valor de la obra; el mediocre habla para ocultar su vacío. 🏛️⚡";
            $pContrarian = "No tienes que anunciar tus metas al mundo; deja que tus resultados terminados hablen por ti. ⚡";
            $pWarrior = "La espada más peligrosa descansa en silencio dentro de su vaina hasta el instante de la victoria. ⚔️";
            $pStoic = "La naturaleza te dio dos oídos y una sola boca para escuchar el doble de lo que hablas. 🏛️";
            $whyExplanation = "Transforma el impulso compulsivo de compartir en soberanía interior mediante el trabajo en discreción.";
        } elseif (str_contains($themeLower, 'dolor') || str_contains($combinedText, 'dolor') || str_contains($combinedText, 'sufrir') || str_contains($combinedText, 'adversidad')) {
            $pHook = "La adversidad no viene a destruirte; llega para revelarte de qué material está forjado tu carácter. ⚡🏛️";
            $pContrarian = "No le pidas a la vida cargas más ligeras; exige hombros más fuertes para soportar la prueba. 🏛️";
            $pWarrior = "La tormenta no pide permiso a la montaña, y la montaña jamás se arrodilla ante el vendaval. ⚡⚔️";
            $pStoic = "El obstáculo en tu camino no detiene la marcha: se convierte en el nuevo camino a seguir. 🏛️⚡";
            $whyExplanation = "Convierte la victimización en fortaleza activa al reencuadrar el dolor como el único crisol de crecimiento.";
        } elseif (str_contains($themeLower, 'tiempo') || str_contains($combinedText, 'vida') || str_contains($combinedText, 'muerte') || str_contains($combinedText, 'memento')) {
            $pHook = "No es que tengamos poco tiempo, es que perdemos demasiado en lo que no tiene valor alguno. 🏛️⏳";
            $pContrarian = "Vives como si fueras a existir eternamente, aplazando lo importante por placeres que no dejan nada. 🏛️";
            $pWarrior = "El guerrero camina con la muerte a su lado para recordar que cada golpe debe ser definitivo. ⚔️";
            $pStoic = "Podrías dejar esta vida ahora mismo; permite que esa certeza determine lo que piensas, dices y haces. 🏛️";
            $whyExplanation = "Aplica la urgencia de Memento Mori para extinguir la procrastinación y enfocar la energía en el presente.";
        } elseif (str_contains($themeLower, 'samur') || str_contains($combinedText, 'bushido') || str_contains($combinedText, 'musashi') || str_contains($combinedText, 'dokkodo')) {
            $pHook = "El camino del deber se recorre en soledad; quien mendiga compañía termina traicionando su propia disciplina. ⚔️⚡";
            $pContrarian = "No busques la paz en la ausencia de conflicto, sino en la quietud inamovible de tu propio espíritu. ⚔️";
            $pWarrior = "Acepta todo tal y como es, sin quejas ni arrepentimiento; ese es el Dokkōdō del guerrero intachable. ⚔️🏛️";
            $pStoic = "Quien domina su juicio interior no teme a diez mil espadas desenvainadas a su alrededor. 🏛️";
            $whyExplanation = "Inyecta el rigor del Dokkōdō samurái, convirtiendo la soledad disciplinada en una insignia de honor.";
        } else {
            $pHook = "Quien gobierna su juicio gobierna su destino; templanza ante la opinión ajena, disciplina ante uno mismo. 🏛️⚡";
            $pContrarian = "La libertad real no consiste en hacer lo que te apetece, sino en dominar lo que sientes. 🏛️";
            $pWarrior = "Ninguna fortaleza exterior resiste si los muros de tu mente ya están agrietados por la queja. ⚡⚔️";
            $pStoic = "No son las cosas las que te perturban, sino el juicio equivocado que decides emitir sobre ellas. 🏛️";
            $whyExplanation = "Ancla el mensaje en la dicotomía del control, recordando que la paz mental es una decisión interna.";
        }

        $phrases = [
            'hook' => $pHook,
            'contrarian' => $pContrarian,
            'warrior' => $pWarrior,
            'stoic' => $pStoic
        ];

        $recreations = [
            'phrase_hook' => $pHook,
            'phrase_contrarian' => $pContrarian,
            'phrase_warrior' => $pWarrior,
            'phrase_stoic' => $pStoic,
            'option_short' => $pHook,
            'option_reflective' => $pContrarian,
            'option_warrior' => $pWarrior,
            'option_stoic' => $pStoic,
            'visual_prompt' => $visualDirector['midjourney_prompt'],
            'image_prompt' => $visualDirector['midjourney_prompt']
        ];

        $parsed = array_merge($recreations, [
            'why_it_works' => $whyExplanation,
            'creative_fit_score' => $creativeFitScore,
            'content_dna' => $dna,
            'visual_director' => $visualDirector
        ]);

        return [
            'dna' => $dna,
            'phrases' => $phrases,
            'recreations' => $recreations,
            'parsed' => $parsed,
            'why_it_works' => $whyExplanation,
            'visual_director' => $visualDirector
        ];
    }

    /**
     * Guarda el estado de aprobación de una variación en la memoria de Atenea
     */
    public static function saveCreationStatus(int $userId, int $postId, string $variationType, string $status): array {
        try {
            $pdo = Database::getConnection();
            $allowed = ['draft', 'approved', 'discarded', 'published'];
            if (!in_array($status, $allowed, true)) {
                $status = 'approved';
            }
            $stmt = $pdo->prepare("
                UPDATE atenea_creations_memory 
                SET status = ? 
                WHERE user_id = ? AND inspiration_post_id = ? AND variation_type = ?
            ");
            $stmt->execute([$status, $userId, $postId, $variationType]);
            return ['success' => true, 'status' => $status];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene creaciones previas de la memoria de Atenea
     */
    public static function getCreationsMemory(int $userId, ?int $postId = null, int $limit = 20): array {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT * FROM atenea_creations_memory WHERE user_id = ?";
            $params = [$userId];
            if ($postId !== null && $postId > 0) {
                $sql .= " AND inspiration_post_id = ?";
                $params[] = $postId;
            }
            $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UTILIDADES PRIVADAS
    // ──────────────────────────────────────────────────────────────────────────

    private static function getUserMetaCredentials(int $userId): ?array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT access_token, page_id FROM accounts
                WHERE user_id = ? AND platform = 'instagram' AND is_active = 1
                ORDER BY id ASC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

            $token = '';
            $igId = '';

            if ($acc && !empty($acc['access_token'])) {
                $token = Security::decrypt(trim($acc['access_token']));
                $igId = !empty($acc['page_id']) ? (string)$acc['page_id'] : Settings::getForUser($userId, 'meta_instagram_account_id', '');
            }

            if (empty($token)) {
                $token = Settings::getForUser($userId, 'meta_instagram_token', '');
            }
            if (empty($token)) {
                $token = Settings::getForUser($userId, 'meta_page_access_token', '');
            }
            if (empty($igId)) {
                $igId = Settings::getForUser($userId, 'meta_instagram_account_id', '');
            }

            return (!empty($token) && !empty($igId)) ? ['token' => $token, 'ig_user_id' => $igId] : null;
        } catch (Throwable $e) {
            error_log("InspirationRadar getUserMetaCredentials: " . $e->getMessage());
            return null;
        }
    }

    private static function makeGetRequest(string $url): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::API_TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if (!$response) return [];
        $decoded = json_decode($response, true);
        return is_array($decoded) ? $decoded : [];
    }
}
