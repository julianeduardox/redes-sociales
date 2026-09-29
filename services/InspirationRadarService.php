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

    public static function removeCreator(int $userId, int $creatorId): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("UPDATE creator_targets SET is_active = 0 WHERE id = ? AND user_id = ?");
            $stmt->execute([$creatorId, $userId]);
            return $stmt->rowCount() 
                ? ['success' => true, 'message' => 'Creador eliminado del radar']
                : ['success' => false, 'error' => 'Creador no encontrado'];
        } catch (Throwable $e) {
            error_log("InspirationRadar removeCreator: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al eliminar creador'];
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

            // Calcular Opportunity Score dinámico para posts que aún no lo tengan
            foreach ($posts as &$postItem) {
                if (empty($postItem['opportunity_score']) || (float)$postItem['opportunity_score'] <= 0) {
                    $postItem['opportunity_score'] = self::calculateOpportunityScore($postItem);
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
    // CEREBRO IA: EXTRACTOR Y VERIFICADOR DE CITAS ESTOICAS / BUSHIDO
    // ──────────────────────────────────────────────────────────────────────────

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
     * Extrae el ADN Psicológico y Filosófico de una publicación viral
     */
    public static function extractContentDna(string $caption, string $theme = ''): array {
        $text = mb_strtolower(trim(strip_tags($caption)), 'UTF-8');
        $themeClean = mb_strtolower(trim($theme), 'UTF-8');

        if (str_contains($text, 'silencio') || str_contains($text, 'hablar') || str_contains($text, 'opini')) {
            return [
                'core_concept' => 'El silencio estratégico y la soberanía interior sobre la opinión ajena.',
                'conflict' => 'La necesidad impulsiva de validación externa vs el autodominio del trabajo silencioso.',
                'transformation' => 'Dejar de justificar tus pasos para que la obra terminada hable por ti.',
                'hook_type' => 'Paradoja Contraintuitiva',
                'sentence_structure' => 'Axioma breve -> Contraste sabio vs mediocre -> Mandato imperativo de disciplina.'
            ];
        }

        if (str_contains($text, 'dolor') || str_contains($text, 'adversidad') || str_contains($text, 'sufrir') || str_contains($text, 'fuego')) {
            return [
                'core_concept' => 'La transmutación del dolor en combustible para la forja del carácter estoico.',
                'conflict' => 'El instinto moderno de huir de la incomodidad vs el principio de amor fati.',
                'transformation' => 'Dejar de preguntar "¿por qué a mí?" y exigir hombros más fuertes para la carga.',
                'hook_type' => 'Golpe de Realidad',
                'sentence_structure' => 'Metáfora de forja -> Cuestionamiento de la debilidad -> Afirmación de invulnerabilidad mental.'
            ];
        }

        if (str_contains($text, 'tiempo') || str_contains($text, 'muerte') || str_contains($text, 'vida') || str_contains($text, 'memento')) {
            return [
                'core_concept' => 'Memento Mori: La finitud de la existencia como catalizador de foco radical.',
                'conflict' => 'Vivir como si fuéramos inmortales postergando lo esencial por placeres efímeros.',
                'transformation' => 'Recuperar la posesión del presente antes de que el tiempo se disuelva en la nada.',
                'hook_type' => 'Urgencia Existencial',
                'sentence_structure' => 'Golpe de finitud -> Consecuencia de la distracción -> Llamado a la sobriedad presente.'
            ];
        }

        if (str_contains($text, 'lider') || str_contains($text, 'miedo') || str_contains($text, 'poder') || str_contains($text, 'autoridad')) {
            return [
                'core_concept' => 'La verdadera autoridad emana de la coherencia interna y no de la coacción.',
                'conflict' => 'La tentación de someter con amenazas vs la templanza de inspirar con hechos.',
                'transformation' => 'Gobernar primero tu propia mente antes de pretender dirigir a otros.',
                'hook_type' => 'Deconstrucción de Poder',
                'sentence_structure' => 'Antítesis tirano/líder -> Quiebre de la máscara -> Conclusión de liderazgo estoico.'
            ];
        }

        return [
            'core_concept' => 'Soberanía mental y dicotomía del control ante los embates de la vida.',
            'conflict' => 'Gastar energía en circunstancias incontrolables vs enfocarse en la propia respuesta.',
            'transformation' => 'Aceptar lo externo sin quejas y ejecutar lo propio con excelencia implacable.',
            'hook_type' => 'Quiebre de Perspectiva',
            'sentence_structure' => 'Principio cardinal -> Distinción entre controlable e incontrolable -> Regla de acción.'
        ];
    }

    /**
     * Genera la dirección visual y prompt para Midjourney v6
     */
    public static function generateVisualDirectorPrompt(array $dna, string $theme = ''): array {
        $themeLower = mb_strtolower($theme, 'UTF-8');
        $concept = $dna['core_concept'] ?? '';

        if (str_contains($themeLower, 'samur') || str_contains($themeLower, 'bushido') || str_contains($concept, 'guerrero')) {
            return [
                'subject' => 'Samurái estoico en meditación profunda bajo suave lluvia nocturna, postura inamovible con katana apoyada frente a él',
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
     * Generador Principal de Atenea: Deconstruye ADN, genera 4 variantes originales
     * y dirección visual para @fortaleza_imparable.
     */
    public static function generateFortalezaRecreations(int $userId, int $postId, int $brandVoiceId = 1, bool $forceRegenerate = false): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT * FROM inspiration_posts WHERE id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (!$post) return ['success' => false, 'error' => 'Publicación de inspiración no encontrada'];

            // Calcular y persistir Opportunity Score si está ausente
            $opportunityScore = (float)($post['opportunity_score'] ?? 0);
            if ($opportunityScore <= 0) {
                $opportunityScore = self::calculateOpportunityScore($post);
                try {
                    $upScore = $pdo->prepare("UPDATE inspiration_posts SET opportunity_score = ? WHERE id = ?");
                    $upScore->execute([$opportunityScore, $postId]);
                    $upScore->closeCursor();
                    $post['opportunity_score'] = $opportunityScore;
                } catch (Throwable) {}
            }

            // Si ya tiene recreaciones guardadas y no se fuerza regeneración, devolverlas de inmediato
            if (!$forceRegenerate && !empty($post['recreated_copies'])) {
                $cached = json_decode($post['recreated_copies'], true);
                if (is_array($cached) && !empty($cached['option_short'])) {
                    $dna = !empty($post['content_dna']) ? json_decode($post['content_dna'], true) : ($cached['content_dna'] ?? self::extractContentDna($post['caption'] ?? '', $post['theme'] ?? ''));
                    $visualDirector = $cached['visual_director'] ?? self::generateVisualDirectorPrompt($dna, $post['theme'] ?? '');

                    return [
                        'success' => true,
                        'from_cache' => true,
                        'post' => $post,
                        'opportunity_score' => $opportunityScore,
                        'reference_post' => [
                            'quote' => $post['quote_extracted'] ?: $post['caption'],
                            'author' => $post['quote_author'] ?: 'Estoico',
                            'theme' => $post['theme'] ?: 'Disciplina y Carácter Estoico',
                            'status' => $post['quote_verified_status'] ?: 'modern_idea'
                        ],
                        'dna' => $dna,
                        'recreations' => [
                            'option_short' => $cached['option_short'] ?? '',
                            'option_reflective' => $cached['option_reflective'] ?? '',
                            'option_warrior' => $cached['option_warrior'] ?? '',
                            'option_stoic' => $cached['option_stoic'] ?? ($cached['option_reflective'] ?? ''),
                            'visual_prompt' => $cached['visual_prompt'] ?? ($cached['image_prompt'] ?? ($visualDirector['midjourney_prompt'] ?? '')),
                            'image_prompt' => $cached['visual_prompt'] ?? ($cached['image_prompt'] ?? ($visualDirector['midjourney_prompt'] ?? ''))
                        ],
                        'visual_director' => $visualDirector
                    ];
                }
            }

            // Asegurarse de tener el análisis de la cita
            if (empty($post['quote_extracted'])) {
                self::analyzeAndVerifyQuote($userId, $postId);
                $stmt->execute([$postId, $userId]);
                $post = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            }

            $apiKey = Settings::get('openrouter_api_key', '', $userId);
            $aiModel = Settings::get('openrouter_model', 'nousresearch/hermes-3-llama-3.1-70b', $userId);

            $theme = $post['theme'] ?: 'Disciplina y Carácter Estoico';
            $quote = $post['quote_extracted'] ?: $post['caption'];
            $author = $post['quote_author'] ?: 'Estoico';

            $systemPrompt = "Eres ATENEA, la Directora de Estrategia de Contenido y Filosofía de 'Fortaleza Imparable' (@fortaleza_imparable). Eres una estratega maestra en psicología humana, retórica estoica clásica y persuasión de alto impacto. Tu misión NO es copiar ni resumir publicaciones virales, sino deconstruir su ADN psicológico profundo y transformarlo en 4 obras maestras originales de contenido más una dirección visual cinematográfica en Midjourney. Responde SIEMPRE única y exclusivamente en formato JSON estricto con las claves exactas requeridas, sin markdown ni explicaciones adicionales.";

            $userPrompt = <<<PROMPT
Analiza esta publicación viral en el nicho estoico/desarrollo personal:
- Cita o reflexión de referencia: "{$quote}"
- Autor atribuido: {$author}
- Tema central: {$theme}

Tu misión como ATENEA:
1. Deconstruye el ADN psicológico (concepto nuclear, conflicto, transformación, tipo de gancho y estructura sintáctica).
2. Genera 4 variantes de copys TOTALMENTE ORIGINALES para @fortaleza_imparable (NO copies ni parafrasees las mismas palabras; destila la sabiduría y crea nuevos ángulos de impacto):
   - "option_short": ⚡ Gancho Brutal / Impacto Rápido (1 a 2 frases afiladas para post visual o portada de carrusel, con 1 emoji sobrio ⚡ o 🏛️).
   - "option_reflective": 📖 Sabiduría Clásica & Reflexión Profunda (Gancho disruptivo + 2 a 3 párrafos concisos desarmando la debilidad moderna y aplicando el estoicismo real + remate contundente con llamada a la reflexión).
   - "option_warrior": ⚔️ Modo Guerrero / Bushido & Disciplina (Enfoque en honor, forja del carácter en la adversidad, soledad constructiva, vencer la queja y disciplina implacable).
   - "option_stoic": 🏛️ Modo Estoico / Virtud & Autodominio (Dicotomía del control, templanza imperturbable ante lo externo, no juzgar las acciones ajenas y soberanía interior).
3. Diseña la Dirección Visual Cinematográfica para Midjourney v6:
   - Sujeto (busto de mármol antiguo, samurái en meditación, guerrero espartano, filósofo solitario)
   - Entorno (templo milenario en ruinas, biblioteca en penumbra, cumbre neblinosa)
   - Atmósfera (claroscuro renacentista, contraluz dorado tenue, sombras densas)
   - Cámara (lente 35mm anamórfico, f/1.8, grano fílmico sutil)
   - Prompt completo en inglés optimizado para Midjourney (--ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime).

Estructura requerida en JSON:
{
  "content_dna": {
    "core_concept": "Definición concisa del principio psicológico o filosófico en 1 oración",
    "conflict": "La tensión o paradoja interna que experimenta el lector",
    "transformation": "El cambio de perspectiva o acción soberana requerida",
    "hook_type": "Clasificación (ej. Paradoja Contraintuitiva, Golpe de Realidad, Pregunta Incómoda, Contraste)",
    "sentence_structure": "Patrón sintáctico utilizado para máxima retención"
  },
  "option_short": "Texto variante 1...",
  "option_reflective": "Texto variante 2...",
  "option_warrior": "Texto variante 3...",
  "option_stoic": "Texto variante 4...",
  "visual_director": {
    "subject": "Descripción del sujeto...",
    "environment": "Descripción del entorno...",
    "atmosphere": "Descripción de la iluminación y sombras...",
    "camera": "Especificación fotográfica...",
    "midjourney_prompt": "Cinematic chiaroscuro... --ar 4:5 --v 6.0 --no text, typography, watermark, logo, cartoon, anime"
  }
}
PROMPT;

            $payload = [
                'model' => $aiModel,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.82,
                'max_tokens' => 1500
            ];

            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 25,
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
                    if (!empty($parsed['option_short'])) {
                        $dna = $parsed['content_dna'] ?? self::extractContentDna($quote, $theme);
                        $visualDirector = $parsed['visual_director'] ?? self::generateVisualDirectorPrompt($dna, $theme);
                        $visualPrompt = $visualDirector['midjourney_prompt'] ?? ($parsed['image_prompt'] ?? '');

                        // Completar opción estoica si el modelo la omitió
                        if (empty($parsed['option_stoic'])) {
                            $parsed['option_stoic'] = "Lo que escapa a tu control no merece un solo segundo de tu angustia. Tu única soberanía reside en el juicio que eliges tener hoy. Domina tu mente. 🏛️⚡";
                        }

                        $parsed['visual_prompt'] = $visualPrompt;
                        $parsed['visual_director'] = $visualDirector;
                        $parsed['content_dna'] = $dna;

                        // Persistir en SQLite (inspiration_posts, atenea_content_dna, atenea_creations_memory)
                        self::persistAteneaCreations($pdo, $userId, $postId, $dna, $parsed, $opportunityScore);

                        return [
                            'success' => true,
                            'from_cache' => false,
                            'post' => $post,
                            'opportunity_score' => $opportunityScore,
                            'reference_post' => [
                                'quote' => $quote,
                                'author' => $author,
                                'theme' => $theme,
                                'status' => $post['quote_verified_status']
                            ],
                            'dna' => $dna,
                            'recreations' => [
                                'option_short' => $parsed['option_short'],
                                'option_reflective' => $parsed['option_reflective'],
                                'option_warrior' => $parsed['option_warrior'],
                                'option_stoic' => $parsed['option_stoic'],
                                'visual_prompt' => $visualPrompt,
                                'image_prompt' => $visualPrompt
                            ],
                            'visual_director' => $visualDirector
                        ];
                    }
                }
            }

            // Fallback heurístico inteligente de Atenea (dinámico, original y contextual)
            $heuristic = self::generateDynamicHeuristicAtenea($quote, $author, $theme, $post, $opportunityScore);
            self::persistAteneaCreations($pdo, $userId, $postId, $heuristic['dna'], $heuristic['recreations'], $opportunityScore);

            return [
                'success' => true,
                'from_cache' => false,
                'post' => $post,
                'opportunity_score' => $opportunityScore,
                'reference_post' => [
                    'quote' => $quote,
                    'author' => $author,
                    'theme' => $theme,
                    'status' => $post['quote_verified_status']
                ],
                'dna' => $heuristic['dna'],
                'recreations' => $heuristic['recreations'],
                'visual_director' => $heuristic['visual_director']
            ];
        } catch (Throwable $e) {
            error_log("InspirationRadar generateFortalezaRecreations: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error al generar recreaciones: ' . $e->getMessage()];
        }
    }

    /**
     * Persiste el ADN y variaciones en las tablas de memoria de Atenea
     */
    private static function persistAteneaCreations(PDO $pdo, int $userId, int $postId, array $dna, array $parsed, float $opportunityScore): void {
        for ($attempt = 0; $attempt < 4; $attempt++) {
            try {
                // 1. Guardar en inspiration_posts
                $upStmt = $pdo->prepare("
                    UPDATE inspiration_posts SET 
                        recreated_copies = :recreated,
                        content_dna = :dna,
                        opportunity_score = :score
                    WHERE id = :id AND user_id = :uid
                ");
                $upStmt->execute([
                    ':recreated' => json_encode($parsed, JSON_UNESCAPED_UNICODE),
                    ':dna' => json_encode($dna, JSON_UNESCAPED_UNICODE),
                    ':score' => $opportunityScore,
                    ':id' => $postId,
                    ':uid' => $userId
                ]);

                // 2. Guardar en atenea_content_dna
                $dnaStmt = $pdo->prepare("
                    INSERT INTO atenea_content_dna (
                        user_id, post_id, core_concept, conflict, transformation, hook_type, sentence_structure, opportunity_score
                    ) VALUES (
                        :uid, :pid, :core, :conflict, :trans, :hook, :struct, :score
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
                    ':score' => $opportunityScore
                ]);

                // 3. Guardar en atenea_creations_memory para cada variación
                $variations = [
                    'short' => ['hook' => mb_substr($parsed['option_short'] ?? '', 0, 80), 'copy' => $parsed['option_short'] ?? ''],
                    'reflective' => ['hook' => mb_substr($parsed['option_reflective'] ?? '', 0, 80), 'copy' => $parsed['option_reflective'] ?? ''],
                    'warrior' => ['hook' => mb_substr($parsed['option_warrior'] ?? '', 0, 80), 'copy' => $parsed['option_warrior'] ?? ''],
                    'stoic' => ['hook' => mb_substr($parsed['option_stoic'] ?? '', 0, 80), 'copy' => $parsed['option_stoic'] ?? '']
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

                foreach ($variations as $type => $varData) {
                    if (!empty($varData['copy'])) {
                        try {
                            $memStmt->execute([
                                ':uid' => $userId,
                                ':pid' => $postId,
                                ':vtype' => $type,
                                ':hook' => $varData['hook'],
                                ':copy' => $varData['copy'],
                                ':vprompt' => $visPrompt,
                                ':vmatrix' => $visMatrix
                            ]);
                        } catch (Throwable) {}
                    }
                }
                // Si llegó aquí con éxito, salir del loop
                return;
            } catch (Throwable $e) {
                if ($attempt === 3) {
                    error_log("Failed persisting Atenea creations after retries: " . $e->getMessage());
                } else {
                    usleep(150000); // 150ms backoff para que SQLite libere cerraduras
                }
            }
        }
    }

    /**
     * Fallback heurístico inteligente de Atenea (4 variantes completas + ADN + Visual Director)
     */
    private static function generateDynamicHeuristicAtenea(string $quote, string $author, string $theme, array $post, float $opportunityScore): array {
        $dna = self::extractContentDna($quote, $theme);
        $visualDirector = self::generateVisualDirectorPrompt($dna, $theme);
        $quoteClean = trim(strip_tags($quote));
        $themeLower = mb_strtolower($theme, 'UTF-8');
        $quoteLower = mb_strtolower($quoteClean, 'UTF-8');

        if (str_contains($themeLower, 'lider') || str_contains($quoteLower, 'miedo') || str_contains($quoteLower, 'autoridad') || str_contains($quoteLower, 'poder')) {
            $variations = [
                'option_short' => "El miedo es el disfraz del tirano débil. La verdadera autoridad no exige sumisión; inspira por los hechos. 🏛️⚡",
                'option_reflective' => "Quien necesita infundir temor para ser obedecido confiesa de inmediato su propia incapacidad.\n\nEl verdadero poder no somete con amenazas, sino con dominio propio y coherencia absoluta. Si tus actos no despiertan respeto voluntario, ninguna orden impondrá lealtad.\n\nSé el líder de tu propia mente antes de aspirar a dirigir a otros. 🏛️",
                'option_warrior' => "El guerrero de honor no comanda por la fuerza del garrote, sino por el peso de su carácter. Quien gobierna con miedo cosecha rebelión; quien gobierna con templanza forja legiones invencibles. Firmeza serena. ⚡⚔️",
                'option_stoic' => "Desea mandar sobre los demás únicamente aquel que aún es esclavo de sus propias pasiones. El sabio estoico no busca súbditos; se gobierna a sí mismo con ley inquebrantable. Tu único imperio real eres tú. 🏛️",
                'visual_prompt' => $visualDirector['midjourney_prompt']
            ];
        } elseif (str_contains($themeLower, 'silencio') || str_contains($quoteLower, 'silencio') || str_contains($quoteLower, 'palabras')) {
            $variations = [
                'option_short' => "El sabio habla porque tiene algo que decir; el mediocre habla porque tiene que decir algo. Domina el silencio. 🏛️⚡",
                'option_reflective' => "En un mundo saturado de ruido y opiniones vacías, callar es un acto de soberanía interior.\n\nNo tienes que justificar tus pasos, tus metas ni tu proceso ante nadie. Deja que sea la constancia de tus resultados la que responda por ti.\n\nGuarda silencio, trabaja en penumbra y deja que la obra terminada hable por sí sola. ⚡",
                'option_warrior' => "La espada más afilada descansa en silencio dentro de su vaina. El guerrero no gasta energía en discusiones fútiles ni busca la aprobación de la multitud. Su disciplina es invisible; su impacto, definitivo. ⚔️🏛️",
                'option_stoic' => "La naturaleza nos dio dos orejas y una sola boca para que escuchemos el doble de lo que hablamos. Si lo que vas a decir no supera al silencio, guarda tu aliento para actuar. Autodominio. 🏛️",
                'visual_prompt' => $visualDirector['midjourney_prompt']
            ];
        } elseif (str_contains($themeLower, 'dolor') || str_contains($quoteLower, 'dolor') || str_contains($quoteLower, 'sufrir') || str_contains($quoteLower, 'adversidad')) {
            $variations = [
                'option_short' => "El fuego templa el acero; la adversidad forja al hombre inquebrantable. Acepta el desafío. ⚡🏛️",
                'option_reflective' => "El sufrimiento inútil viene de resistirse a la realidad. El dolor de crecer, en cambio, es el tributo que paga todo aquel que rehúsa ser ordinario.\n\nCuando las circunstancias te golpeen, no preguntes '¿por qué a mí?'. Pregunta '¿qué me exige esta prueba para elevar mi carácter?'.\n\nNo pidas cargas más ligeras; forja hombros más anchos. 🏛️",
                'option_warrior' => "La tormenta no pide permiso para azotar la montaña, y la montaña jamás se arrodilla ante el vendaval. Mantente erguido en medio de la prueba. El guerrero nace en la fricción. ⚡⚔️",
                'option_stoic' => "Los obstáculos no bloquean el camino: se convierten en el camino. Aquello que pretendía derribarte es el material con el que construyes tu fortaleza interior. Amor Fati. 🏛️⚡",
                'visual_prompt' => $visualDirector['midjourney_prompt']
            ];
        } elseif (str_contains($themeLower, 'tiempo') || str_contains($quoteLower, 'vida') || str_contains($quoteLower, 'muerte') || str_contains($quoteLower, 'memento')) {
            $variations = [
                'option_short' => "No tenemos poco tiempo; es que perdemos demasiado en lo irrelevante. Recuerda que vas a morir. 🏛️⚡",
                'option_reflective' => "La mayoría vive como si tuviera garantizado un suministro infinito de días, postergando lo esencial por perseguir placeres efímeros.\n\nCada hora que dejas escapar en distracciones es una porción de tu existencia que entregas voluntariamente a la nada.\n\nDespierta ahora. Tu única posesión real es este instante. 🏛️⏳",
                'option_warrior' => "El guerrero camina con la muerte como su consejera más lúcida. Saber que el fin es inevitable disuelve cualquier cobardía y enfoca el espíritu en el deber presente. Ni un segundo desperdiciado. ⚡⚔️",
                'option_stoic' => "Podrías dejar la vida ahora mismo; que eso determine lo que haces, dices y piensas. La muerte no es un castigo distante, sino la medida exacta del valor de tu presente. Memento Mori. 🏛️",
                'visual_prompt' => $visualDirector['midjourney_prompt']
            ];
        } else {
            $shortSnippet = mb_substr($quoteClean, 0, 75);
            $variations = [
                'option_short' => "Quien domina su juicio gobierna su destino. Firmeza ante la opinión ajena, disciplina ante uno mismo. 🏛️⚡",
                'option_reflective' => "Observa el principio de fondo: '{$shortSnippet}...'.\n\nEl secreto no reside en lamentarse de las circunstancias externas, sino en tomar el control indiscutible de nuestra respuesta.\n\nTodo lo que escapa a tu voluntad déjalo marchar; todo lo que dependa de tu carácter, ejecútalo con excelencia implacable. 🏛️",
                'option_warrior' => "Ninguna fortaleza exterior resiste si los muros internos están fracturados. Refuerza tu mente cada mañana con la sobriedad del guerrero que espera la batalla diaria. Sin quejas, sin excusas. ⚡⚔️",
                'option_stoic' => "No son las cosas las que perturban a los hombres, sino los juicios que hacen sobre las cosas. Cambia tu interpretación y la herida desaparecerá al instante. Soberanía absoluta. 🏛️",
                'visual_prompt' => $visualDirector['midjourney_prompt']
            ];
        }

        $variations['image_prompt'] = $visualDirector['midjourney_prompt'];

        return [
            'dna' => $dna,
            'recreations' => $variations,
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
