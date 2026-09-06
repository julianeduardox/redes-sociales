<?php
/**
 * ContentPlannerService - AI Content Strategist & Smart Auto-Scheduler
 * Features:
 * - Projects Golden Windows from AnalyticsEngine across upcoming calendar dates
 * - Generates high-converting hooks, copies, CTAs & hashtags based on Brand Voice
 * - Manages multi-tenant scheduled posts, drafts, and publication states
 * - Supports OpenRouter AI with robust Local Heuristic Fallback
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AnalyticsEngineService.php';

class ContentPlannerService {

    private const DAY_NAMES = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado'
    ];

    /**
     * Get upcoming Golden Window slots projected over the next X days
     */
    public static function getUpcomingGoldenSlots(int $userId, string $platform = 'all', ?int $accountId = null, int $daysAhead = 14): array {
        $pdo = Database::getConnection();

        // 1. Fetch Golden Windows from Analytics Engine
        $timing = AnalyticsEngineService::getTimingAnalysis($userId, $platform, $accountId);
        $goldenWindows = $timing['top_golden_windows'] ?? [];

        if (empty($goldenWindows)) {
            $goldenWindows = [
                ['day_name' => 'Jueves', 'hour' => 19, 'hour_label' => '19:30', 'avg_engagement_rate' => 14.5, 'note' => 'Horario dorado para Reels y Feed'],
                ['day_name' => 'Martes', 'hour' => 13, 'hour_label' => '13:15', 'avg_engagement_rate' => 12.0, 'note' => 'Pausa de mediodía de alta concentración'],
                ['day_name' => 'Domingo', 'hour' => 20, 'hour_label' => '20:00', 'avg_engagement_rate' => 11.5, 'note' => 'Conexión y reflexión dominical']
            ];
        }

        // Map day names to Day of Week integer (0=Sun, 1=Mon, ..., 6=Sat)
        $dayMap = [
            'domingo' => 0, 'lunes' => 1, 'martes' => 2, 'miércoles' => 3, 
            'miercoles' => 3, 'jueves' => 4, 'viernes' => 5, 'sábado' => 6, 'sabado' => 6
        ];

        // 2. Fetch already scheduled posts in the range to detect occupied slots
        $startDate = date('Y-m-d 00:00:00');
        $endDate = date('Y-m-d 23:59:59', strtotime("+{$daysAhead} days"));

        $stmtSched = $pdo->prepare("
            SELECT id, scheduled_for, platform, hook_title, status 
            FROM scheduled_posts 
            WHERE user_id = :uid AND scheduled_for BETWEEN :s AND :e AND status != 'cancelled'
        ");
        $stmtSched->execute([':uid' => $userId, ':s' => $startDate, ':e' => $endDate]);
        $existing = $stmtSched->fetchAll(PDO::FETCH_ASSOC);

        $occupiedMap = [];
        foreach ($existing as $ex) {
            $ymdHour = date('Y-m-d H', strtotime($ex['scheduled_for']));
            $occupiedMap[$ymdHour] = $ex;
        }

        // 3. Project slots for the upcoming dates
        $projectedSlots = [];
        $today = new DateTime();

        for ($i = 0; $i <= $daysAhead; $i++) {
            $currDate = (clone $today)->modify("+{$i} days");
            $dayOfWeek = (int)$currDate->format('w');
            $dateStr = $currDate->format('Y-m-d');

            foreach ($goldenWindows as $rankIdx => $gw) {
                $targetDayName = mb_strtolower(trim($gw['day_name'] ?? ''), 'UTF-8');
                $targetDayNum = $dayMap[$targetDayName] ?? ($gw['day_index'] ?? -1);

                if ($dayOfWeek === $targetDayNum) {
                    $hour = isset($gw['hour']) ? (int)$gw['hour'] : 19;
                    $minute = 30;
                    if (!empty($gw['hour_label']) && str_contains($gw['hour_label'], ':')) {
                        $parts = explode(':', trim(explode('-', $gw['hour_label'])[0]));
                        $hour = (int)$parts[0];
                        $minute = isset($parts[1]) ? (int)$parts[1] : 0;
                    }

                    $slotDatetime = sprintf('%s %02d:%02d:00', $dateStr, $hour, $minute);
                    $ymdHourKey = sprintf('%s %02d', $dateStr, $hour);

                    // Skip slots that are in the past
                    if (strtotime($slotDatetime) < time()) {
                        continue;
                    }

                    $isOccupied = isset($occupiedMap[$ymdHourKey]);
                    $occupiedPost = $isOccupied ? $occupiedMap[$ymdHourKey] : null;

                    $rankEmoji = ($rankIdx === 0) ? '🥇' : (($rankIdx === 1) ? '🥈' : '🥉');
                    $rankLabel = ($rankIdx === 0) ? 'Pico #1 Recomendado' : (($rankIdx === 1) ? 'Pico #2 Alternativo' : 'Pico #3 Secundario');

                    $projectedSlots[] = [
                        'slot_id' => 'slot_' . md5($slotDatetime),
                        'date' => $dateStr,
                        'day_name' => self::DAY_NAMES[$dayOfWeek],
                        'time_label' => sprintf('%02d:%02d', $hour, $minute),
                        'full_datetime' => $slotDatetime,
                        'golden_rank' => $rankIdx + 1,
                        'golden_label' => "{$rankEmoji} {$rankLabel}",
                        'avg_engagement_rate' => (float)($gw['avg_engagement_rate'] ?? 0.0),
                        'note' => $gw['note'] ?? 'Horario de alta retención comunitaria',
                        'is_occupied' => $isOccupied,
                        'occupied_post' => $occupiedPost
                    ];
                }
            }
        }

        // Sort chronologically
        usort($projectedSlots, function($a, $b) {
            return strcmp($a['full_datetime'], $b['full_datetime']);
        });

        return $projectedSlots;
    }

    /**
     * Generate Strategic Content Proposals (Hooks, Full Copy, CTAs, Hashtags) with Brand Voice
     */
    public static function generateContentDrafts(int $userId, string $topic, string $platform = 'instagram', string $format = 'reel', string $goal = 'connection'): array {
        $pdo = Database::getConnection();

        // 1. Fetch Brand Voice configuration
        $stmtBv = $pdo->prepare("SELECT * FROM brand_voices WHERE user_id = :uid AND is_default = 1 LIMIT 1");
        $stmtBv->execute([':uid' => $userId]);
        $brandVoice = $stmtBv->fetch(PDO::FETCH_ASSOC);

        if (!$brandVoice) {
            $stmtBvFirst = $pdo->prepare("SELECT * FROM brand_voices WHERE user_id = :uid ORDER BY id ASC LIMIT 1");
            $stmtBvFirst->execute([':uid' => $userId]);
            $brandVoice = $stmtBvFirst->fetch(PDO::FETCH_ASSOC);
        }

        $brandName = $brandVoice['brand_name'] ?? 'Mi Marca';
        $tone = $brandVoice['tone_level'] ?? 'friendly_engaging';
        $persona = $brandVoice['persona_name'] ?? 'Copiloto de Marca';
        $keyPhrases = $brandVoice['key_phrases'] ?? '';
        $forbiddenPhrases = $brandVoice['forbidden_phrases'] ?? '';
        $emojiStyle = $brandVoice['emoji_style'] ?? 'moderate';

        // 2. Fetch winning keywords & timing from Analytics Engine
        $performers = AnalyticsEngineService::getTopPerformersAnalysis($userId, $platform);
        $winningKeywords = $performers['patterns']['top_caption_keywords'] ?? ['crecimiento', 'enfoque', 'disciplina', 'impacto'];
        $bestFormat = $performers['patterns']['best_format'] ?? 'Video / Reel';

        // 3. Try generating through OpenRouter AI
        $openRouterKey = self::getSetting($pdo, $userId, 'openrouter_api_key');
        $aiModel = self::getSetting($pdo, $userId, 'ai_model') ?: 'meta-llama/llama-3.3-70b-instruct';

        $drafts = null;

        if (!empty($openRouterKey)) {
            $drafts = self::generateViaOpenRouter($openRouterKey, $aiModel, $topic, $platform, $format, $goal, $brandVoice, $winningKeywords);
        }

        // 4. Fallback to smart local heuristic generator if OpenRouter is absent or fails
        if (empty($drafts)) {
            $drafts = self::generateViaLocalHeuristics($topic, $platform, $format, $goal, $brandVoice, $winningKeywords);
        }

        return [
            'success' => true,
            'topic' => $topic,
            'platform' => $platform,
            'format' => $format,
            'brand_voice_name' => $brandName,
            'winning_keywords_used' => array_slice($winningKeywords, 0, 4),
            'drafts' => $drafts
        ];
    }

    /**
     * Generate content via OpenRouter LLM API
     */
    private static function generateViaOpenRouter(string $apiKey, string $model, string $topic, string $platform, string $format, string $goal, array $brandVoice, array $winningKeywords): ?array {
        $brandName = $brandVoice['brand_name'] ?? 'Mi Marca';
        $tone = $brandVoice['tone_level'] ?? 'friendly_engaging';
        $persona = $brandVoice['persona_name'] ?? 'Copiloto de Marca';
        $sysPrompt = $brandVoice['system_prompt'] ?? "Eres el estratega de contenido para {$brandName}.";
        $kwList = implode(', ', $winningKeywords);

        $prompt = "
Actúa como un Director Creativo y Estratega de Redes Sociales de élite para {$brandName}.
Identidad y Tono de la Marca: {$tone} ({$persona}).
Instrucciones de voz: {$sysPrompt}
Palabras clave ganadoras de la audiencia: {$kwList}

Tema del contenido: \"{$topic}\"
Plataforma: {$platform}
Formato: {$format} (Reel / Video / Carrusel / Imagen)
Objetivo principal: {$goal}

Genera exactamente 3 variantes estratégicas de publicación con las siguientes tipologías:
1. Variante A (Conexión & Empatía / Storytelling)
2. Variante B (Conversión & Lead Magnet / Oferta)
3. Variante C (Autoridad & Educación / 3 Pasos)

Devuelve ÚNICAMENTE un objeto JSON válido con la siguiente estructura exacta (sin texto extra):
{
  \"proposals\": [
    {
      \"variant_key\": \"connection\",
      \"variant_title\": \"🤝 Conexión & Storytelling\",
      \"hook_title\": \"Gancho irresistible de 1 línea\",
      \"caption\": \"Texto completo estructurado con saltos de línea y emojis adecuados\",
      \"cta\": \"Llamada a la acción para provocar comentarios\",
      \"hashtags\": \"#hashtag1 #hashtag2 #hashtag3 #hashtag4\",
      \"suggested_format\": \"{$format}\",
      \"visual_concept\": \"Idea visual o sugerencia de toma / imagen de apoyo\"
    },
    {
      \"variant_key\": \"conversion\",
      \"variant_title\": \"🎯 Conversión & Lead Magnet\",
      \"hook_title\": \"...\",
      \"caption\": \"...\",
      \"cta\": \"...\",
      \"hashtags\": \"...\",
      \"suggested_format\": \"{$format}\",
      \"visual_concept\": \"...\"
    },
    {
      \"variant_key\": \"authority\",
      \"variant_title\": \"🧠 Autoridad & Educación\",
      \"hook_title\": \"...\",
      \"caption\": \"...\",
      \"cta\": \"...\",
      \"hashtags\": \"...\",
      \"suggested_format\": \"{$format}\",
      \"visual_concept\": \"...\"
    }
  ]
}
";

        try {
            $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . trim($apiKey),
                    'HTTP-Referer: http://localhost/Redes%20sociales',
                    'X-Title: XINDRO Content Planner'
                ],
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => "Eres un estratega de contenido que devuelve únicamente JSON estricto."],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object']
                ]),
                CURLOPT_TIMEOUT => 25
            ]);

            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($res)) {
                $decoded = json_decode($res, true);
                $contentStr = $decoded['choices'][0]['message']['content'] ?? '';
                $jsonParsed = json_decode($contentStr, true);
                if (!empty($jsonParsed['proposals']) && is_array($jsonParsed['proposals'])) {
                    return $jsonParsed['proposals'];
                }
            }
        } catch (Throwable $e) {
            error_log("OpenRouter Planner error: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Generate content via Local Heuristic Generator (Zero-dependency & instant)
     */
    private static function generateViaLocalHeuristics(string $topic, string $platform, string $format, string $goal, array $brandVoice, array $winningKeywords): array {
        $cleanTopic = trim($topic) ?: 'Crecimiento y enfoque estratégico';
        $brandName = $brandVoice['brand_name'] ?? 'XINDRO';
        $kw1 = $winningKeywords[0] ?? 'crecimiento';
        $kw2 = $winningKeywords[1] ?? 'enfoque';
        $kw3 = $winningKeywords[2] ?? 'impacto';

        $isReel = str_contains(strtolower($format), 'reel') || str_contains(strtolower($format), 'video');

        return [
            // 1. Connection Variant
            [
                'variant_key' => 'connection',
                'variant_title' => '🤝 Conexión & Storytelling',
                'hook_title' => "La verdad que nadie te dice sobre {$cleanTopic} 👇",
                'caption' => "Durante mucho tiempo pensé que para dominar {$cleanTopic} necesitaba más tiempo o motivación perfecta.\n\nPero la realidad es diferente: la verdadera clave está en la constancia y en el {$kw2} diario.\n\nCuando dejas de buscar atajos y construyes hábitos sólidos, los resultados empiezan a multiplicarse de forma natural. ✨\n\n¿Cuál ha sido tu mayor reto con este tema últimamente? Te leo en los comentarios. 👇",
                'cta' => 'Comenta tu opinión abajo y comparte con quien necesite leer esto hoy.',
                'hashtags' => "#{$kw1} #{$kw2} #{$kw3} #inspiracion #estrategia #desarrollo",
                'suggested_format' => $format,
                'visual_concept' => $isReel ? "Toma en primer plano hablando directamente a la cámara con subtítulos dinámicos y música reflexiva." : "Imagen limpia y minimalista con una frase de impacto centrada."
            ],

            // 2. Conversion Variant
            [
                'variant_key' => 'conversion',
                'variant_title' => '🎯 Conversión & Lead Magnet',
                'hook_title' => "¿Quieres dominar {$cleanTopic} en tiempo récord? Lee esto 🚀",
                'caption' => "Si estás listo para dar un salto en tu {$kw1} y dejar de postergar tus metas, hemos preparado una guía paso a paso con las mejores herramientas de {$brandName}.\n\n🔹 Paso 1: Claridad total en tus objetivos\n🔹 Paso 2: Automatización y {$kw2} inteligente\n🔹 Paso 3: Medición de resultados en tiempo real\n\nEscribe la palabra 'INFO' en los comentarios y nuestro copiloto te enviará el acceso exclusivo por mensaje directo. 📥",
                'cta' => "Comenta 'INFO' o 'GUIAS' para enviarte el enlace directo.",
                'hashtags' => "#{$kw1} #{$kw2} #oportunidad #resultados #productividad #exito",
                'suggested_format' => $format,
                'visual_concept' => $isReel ? "Demostración rápida de pantalla o carrusel de 3 diapositivas con puntos visuales clave." : "Gráfico tipo tarjeta ejecutiva destacando los 3 pasos de transformación."
            ],

            // 3. Authority Variant
            [
                'variant_key' => 'authority',
                'variant_title' => '🧠 Autoridad & Educación',
                'hook_title' => "3 Errores comunes al abordar {$cleanTopic} (y cómo evitarlos) 💡",
                'caption' => "El 90% de los creadores y marcas cometen estos 3 fallos con {$cleanTopic}:\n\n1️⃣ Intentar hacer todo a la vez sin priorizar.\n2️⃣ No analizar las métricas y horarios donde su comunidad está activa.\n3️⃣ Olvidar que la disciplina supera al talento.\n\nAplica este marco de trabajo y verás cómo tu {$kw3} se dispara.\n\nGuarda este post para consultarlo cuando vayas a planificar tu semana. 🔖",
                'cta' => 'Guarda este post en tu colección y compártelo con tu equipo.',
                'hashtags' => "#{$kw1} #{$kw2} #autoridad #educacion #estrategiadigital #tips",
                'suggested_format' => $format,
                'visual_concept' => $isReel ? "Transición rápida mostrando los 3 puntos con textos flotantes y transiciones limpias." : "Infografía visual de alto contraste dividida en 3 columnas."
            ]
        ];
    }

    /**
     * Get Calendar Posts in a Date Range
     */
    public static function getCalendarPosts(int $userId, string $startDate, string $endDate, string $platform = 'all'): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                sp.*,
                COALESCE(a.account_name, 'Mi Cuenta') as account_name,
                COALESCE(a.account_handle, '') as account_handle,
                COALESCE(bv.brand_name, 'Voz de Marca') as brand_voice_name
            FROM scheduled_posts sp
            LEFT JOIN accounts a ON sp.account_id = a.id
            LEFT JOIN brand_voices bv ON sp.brand_voice_id = bv.id
            WHERE sp.user_id = :uid AND sp.scheduled_for BETWEEN :s AND :e
        ";
        $params = [
            ':uid' => $userId,
            ':s' => $startDate . ' 00:00:00',
            ':e' => $endDate . ' 23:59:59'
        ];

        if ($platform !== 'all') {
            $sql .= " AND sp.platform = :plat";
            $params[':plat'] = $platform;
        }

        $sql .= " ORDER BY sp.scheduled_for ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create or Update a Scheduled Post
     */
    public static function saveScheduledPost(int $userId, array $data): array {
        $pdo = Database::getConnection();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $accountId = !empty($data['account_id']) ? (int)$data['account_id'] : null;
        $brandVoiceId = !empty($data['brand_voice_id']) ? (int)$data['brand_voice_id'] : null;
        $platform = in_array($data['platform'] ?? '', ['instagram', 'facebook'], true) ? $data['platform'] : 'instagram';
        $contentFormat = $data['content_format'] ?? 'reel';
        $topic = trim($data['topic'] ?? '');
        $hookTitle = trim($data['hook_title'] ?? '');
        $caption = trim($data['caption'] ?? '');
        $mediaUrl = trim($data['media_url'] ?? '');
        $scheduledFor = trim($data['scheduled_for'] ?? '');
        $isGoldenSlot = !empty($data['is_golden_slot']) ? 1 : 0;
        $goldenLabel = trim($data['golden_window_label'] ?? '');
        $status = in_array($data['status'] ?? '', ['draft', 'scheduled', 'published', 'failed', 'cancelled'], true) ? $data['status'] : 'scheduled';

        if (empty($caption)) {
            throw new InvalidArgumentException("El texto de la publicación no puede estar vacío.");
        }

        if (empty($scheduledFor) || strtotime($scheduledFor) === false) {
            $scheduledFor = date('Y-m-d H:i:s', strtotime('+1 day 19:30:00'));
        } else {
            $scheduledFor = date('Y-m-d H:i:s', strtotime($scheduledFor));
        }

        if ($id !== null && $id > 0) {
            // Update existing
            $stmt = $pdo->prepare("
                UPDATE scheduled_posts SET
                    account_id = :acc_id,
                    brand_voice_id = :bv_id,
                    platform = :plat,
                    content_format = :fmt,
                    topic = :topic,
                    hook_title = :hook,
                    caption = :cap,
                    media_url = :media,
                    scheduled_for = :sched_for,
                    is_golden_slot = :is_gold,
                    golden_window_label = :gold_lbl,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND user_id = :uid
            ");
            $stmt->execute([
                ':acc_id' => $accountId,
                ':bv_id' => $brandVoiceId,
                ':plat' => $platform,
                ':fmt' => $contentFormat,
                ':topic' => $topic,
                ':hook' => $hookTitle,
                ':cap' => $caption,
                ':media' => $mediaUrl,
                ':sched_for' => $scheduledFor,
                ':is_gold' => $isGoldenSlot,
                ':gold_lbl' => $goldenLabel,
                ':status' => $status,
                ':id' => $id,
                ':uid' => $userId
            ]);
            $savedId = $id;
        } else {
            // Insert new
            $stmt = $pdo->prepare("
                INSERT INTO scheduled_posts 
                (user_id, account_id, brand_voice_id, platform, content_format, topic, hook_title, caption, media_url, scheduled_for, is_golden_slot, golden_window_label, status, created_at, updated_at)
                VALUES 
                (:uid, :acc_id, :bv_id, :plat, :fmt, :topic, :hook, :cap, :media, :sched_for, :is_gold, :gold_lbl, :status, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':acc_id' => $accountId,
                ':bv_id' => $brandVoiceId,
                ':plat' => $platform,
                ':fmt' => $contentFormat,
                ':topic' => $topic,
                ':hook' => $hookTitle,
                ':cap' => $caption,
                ':media' => $mediaUrl,
                ':sched_for' => $scheduledFor,
                ':is_gold' => $isGoldenSlot,
                ':gold_lbl' => $goldenLabel,
                ':status' => $status
            ]);
            $savedId = (int)$pdo->lastInsertId();
        }

        return [
            'success' => true,
            'id' => $savedId,
            'message' => 'Publicación guardada en el calendario con éxito.'
        ];
    }

    /**
     * Delete a scheduled post
     */
    public static function deleteScheduledPost(int $userId, int $postId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM scheduled_posts WHERE id = :id AND user_id = :uid");
        return $stmt->execute([':id' => $postId, ':uid' => $userId]);
    }

    /**
     * Publish scheduled post immediately (Simulated/Meta direct)
     */
    public static function publishScheduledPostNow(int $userId, int $postId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM scheduled_posts WHERE id = :id AND user_id = :uid LIMIT 1");
        $stmt->execute([':id' => $postId, ':uid' => $userId]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post) {
            throw new Exception("Publicación no encontrada.");
        }

        $metaPublishId = 'meta_pub_' . substr(md5(uniqid((string)time(), true)), 0, 16);

        // Update status to published
        $up = $pdo->prepare("
            UPDATE scheduled_posts 
            SET status = 'published', meta_publish_id = :pub_id, updated_at = CURRENT_TIMESTAMP 
            WHERE id = :id AND user_id = :uid
        ");
        $up->execute([':pub_id' => $metaPublishId, ':id' => $postId, ':uid' => $userId]);

        return [
            'success' => true,
            'message' => 'Publicación enviada y marcada como publicada con éxito.',
            'meta_publish_id' => $metaPublishId
        ];
    }

    /**
     * Helper to fetch a setting
     */
    private static function getSetting(PDO $pdo, int $userId, string $key): string {
        $stmt = $pdo->prepare("SELECT value FROM settings WHERE user_id = :uid AND key = :key LIMIT 1");
        $stmt->execute([':uid' => $userId, ':key' => $key]);
        return (string)($stmt->fetchColumn() ?: '');
    }
}
