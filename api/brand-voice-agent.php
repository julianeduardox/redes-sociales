<?php
/**
 * REST API: Brand Voice Training & Calibration Agent ("Stoic Voice Coach")
 * Compliant with XINDRO AGENTS.md standards:
 * - Anti-CSRF validation
 * - PDO prepared statements with strict multi-tenant isolation
 * - Standard JSON output schema
 * - Exception logging and graceful error responses
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/CacheService.php';

header('Content-Type: application/json; charset=utf-8');
Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$pdo = Database::getConnection();

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

    // 1. GET: Retrieve active Brand Voice & Agent Health
    if ($method === 'GET') {
        $stmtBv = $pdo->prepare("
            SELECT * FROM brand_voices 
            WHERE user_id = :uid 
            ORDER BY is_default DESC, id ASC 
            LIMIT 1
        ");
        $stmtBv->execute([':uid' => $userId]);
        $bv = $stmtBv->fetch(PDO::FETCH_ASSOC);

        // Fetch User AI Quota
        $uStmt = $pdo->prepare("SELECT ai_model, max_tokens, used_tokens FROM users WHERE id = :uid LIMIT 1");
        $uStmt->execute([':uid' => $userId]);
        $userQuota = $uStmt->fetch(PDO::FETCH_ASSOC) ?: ['ai_model' => 'heuristic', 'max_tokens' => 50000, 'used_tokens' => 0];

        $openrouterKey = Settings::get('openrouter_api_key', '');
        $hasActiveOpenRouter = !empty($openrouterKey) && (int)$userQuota['used_tokens'] < (int)$userQuota['max_tokens'];

        // Linked Accounts
        $stmtAcc = $pdo->prepare("SELECT id, platform, account_name, brand_voice_id, is_active FROM accounts WHERE user_id = :uid");
        $stmtAcc->execute([':uid' => $userId]);
        $accounts = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

        $fewShot = !empty($bv['few_shot_examples']) ? json_decode($bv['few_shot_examples'], true) : AiAgentService::getDefaultFewShotExamples();
        $keyPhrases = !empty($bv['key_phrases']) ? json_decode($bv['key_phrases'], true) : [];
        $forbiddenPhrases = !empty($bv['forbidden_phrases']) ? json_decode($bv['forbidden_phrases'], true) : [];

        echo json_encode([
            'success' => true,
            'message' => 'Estado del Agente de Voz obtenido correctamente',
            'data' => [
                'brand_voice' => [
                    'id' => (int)($bv['id'] ?? 0),
                    'brand_name' => $bv['brand_name'] ?? 'Fortaleza Imparable',
                    'persona_name' => $bv['persona_name'] ?? 'Mentor Estoico',
                    'industry' => $bv['industry'] ?? 'Desarrollo Personal & Filosofía',
                    'tone_level' => $bv['tone_level'] ?? 'stoic_authoritative',
                    'system_prompt' => $bv['system_prompt'] ?? '',
                    'warmth_level' => (int)($bv['warmth_level'] ?? 80),
                    'depth_level' => (int)($bv['depth_level'] ?? 90),
                    'energy_level' => (int)($bv['energy_level'] ?? 85),
                    'closing_question_rule' => $bv['closing_question_rule'] ?? 'relevant',
                    'emoji_style' => $bv['emoji_style'] ?? 'moderate',
                    'key_phrases' => is_array($keyPhrases) ? $keyPhrases : [],
                    'forbidden_phrases' => is_array($forbiddenPhrases) ? $forbiddenPhrases : [],
                    'few_shot_examples' => is_array($fewShot) ? $fewShot : []
                ],
                'engine_status' => [
                    'active_engine' => $hasActiveOpenRouter ? 'openrouter_hybrid' : 'heuristic_humanized_v2',
                    'heuristic_ready' => true,
                    'openrouter_configured' => !empty($openrouterKey),
                    'tokens_used' => (int)($userQuota['used_tokens'] ?? 0),
                    'tokens_max' => (int)($userQuota['max_tokens'] ?? 50000),
                    'model_assigned' => $userQuota['ai_model'] ?? 'anthropic/claude-3.5-sonnet'
                ],
                'linked_accounts' => $accounts
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. POST: Actions (Mutation & Simulation)
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método HTTP no permitido', 'code' => 'METHOD_NOT_ALLOWED']);
        exit;
    }

    Security::requireCsrf();
    Security::requireRateLimit('brand_voice_agent', 40, 60);

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?? $_POST;
    $action = Security::sanitizeString($input['action'] ?? 'simulate', 50);

    // ACTION: Simulate comment response
    if ($action === 'simulate') {
        $commentText = Security::sanitizeString($input['comment_text'] ?? '', 2000);
        if (empty($commentText)) {
            echo json_encode(['success' => false, 'error' => 'El comentario de prueba no puede estar vacío', 'code' => 'EMPTY_COMMENT']);
            exit;
        }

        $authorName = Security::sanitizeString($input['author_name'] ?? 'Usuario de Facebook', 100);
        $platform = Security::validateEnum($input['platform'] ?? 'facebook', ['facebook', 'instagram'], 'facebook');
        $postCaption = Security::sanitizeString($input['post_caption'] ?? 'Publicación sobre mentalidad y disciplina estoica.', 1500);

        // Analysis
        $analysis = AiAgentService::analyzeComment($commentText, $postCaption);

        // Generate
        $replies = AiAgentService::generateReplies($authorName, $commentText, $platform, $postCaption, '', [
            'user_id' => $userId
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Simulación de respuesta completada',
            'data' => [
                'analysis' => $analysis,
                'replies' => $replies,
                'author_evaluation' => [
                    'original_author' => $authorName,
                    'is_generic_placeholder' => AiAgentService::isGenericAuthorName($authorName),
                    'extracted_first_name' => AiAgentService::extractCleanFirstName($authorName)
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ACTION: Teach a new Few-Shot Example to the Heuristic Engine
    if ($action === 'teach_example') {
        $tag = Security::sanitizeString($input['tag'] ?? 'patron_personalizado', 50);
        $patternComment = Security::sanitizeString($input['comment'] ?? '', 1000);
        $masterReply = Security::sanitizeString($input['reply'] ?? '', 2000);

        if (empty($patternComment) || empty($masterReply)) {
            echo json_encode(['success' => false, 'error' => 'El comentario patrón y la respuesta no pueden estar vacíos', 'code' => 'INVALID_PARAMS']);
            exit;
        }

        $stmtBv = $pdo->prepare("SELECT id, few_shot_examples FROM brand_voices WHERE user_id = :uid ORDER BY is_default DESC LIMIT 1");
        $stmtBv->execute([':uid' => $userId]);
        $bv = $stmtBv->fetch(PDO::FETCH_ASSOC);

        if (!$bv) {
            echo json_encode(['success' => false, 'error' => 'No se encontró una voz de marca activa para entrenar', 'code' => 'NOT_FOUND']);
            exit;
        }

        $examples = !empty($bv['few_shot_examples']) ? json_decode($bv['few_shot_examples'], true) : [];
        if (!is_array($examples)) $examples = [];

        // Prepend new example
        array_unshift($examples, [
            'tag' => $tag,
            'comment' => $patternComment,
            'reply' => $masterReply
        ]);

        // Keep maximum 20 high-fidelity master examples
        $examples = array_slice($examples, 0, 20);

        $up = $pdo->prepare("UPDATE brand_voices SET few_shot_examples = :ex WHERE id = :id AND user_id = :uid");
        $up->execute([
            ':ex' => json_encode($examples, JSON_UNESCAPED_UNICODE),
            ':id' => $bv['id'],
            ':uid' => $userId
        ]);

        CacheService::clear();

        echo json_encode([
            'success' => true,
            'message' => '✅ Ejemplo maestro enseñado y guardado en el motor heurístico',
            'data' => [
                'total_examples' => count($examples),
                'new_example' => [
                    'tag' => $tag,
                    'comment' => $patternComment,
                    'reply' => $masterReply
                ]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ACTION: Delete a Few-Shot Example
    if ($action === 'delete_example') {
        $index = (int)($input['index'] ?? -1);

        $stmtBv = $pdo->prepare("SELECT id, few_shot_examples FROM brand_voices WHERE user_id = :uid ORDER BY is_default DESC LIMIT 1");
        $stmtBv->execute([':uid' => $userId]);
        $bv = $stmtBv->fetch(PDO::FETCH_ASSOC);

        if (!$bv) {
            echo json_encode(['success' => false, 'error' => 'Voz de marca no encontrada', 'code' => 'NOT_FOUND']);
            exit;
        }

        $examples = !empty($bv['few_shot_examples']) ? json_decode($bv['few_shot_examples'], true) : [];
        if (is_array($examples) && isset($examples[$index])) {
            array_splice($examples, $index, 1);
            $up = $pdo->prepare("UPDATE brand_voices SET few_shot_examples = :ex WHERE id = :id AND user_id = :uid");
            $up->execute([
                ':ex' => json_encode($examples, JSON_UNESCAPED_UNICODE),
                ':id' => $bv['id'],
                ':uid' => $userId
            ]);
            CacheService::clear();
        }

        echo json_encode([
            'success' => true,
            'message' => 'Ejemplo eliminado con éxito',
            'data' => ['remaining_examples' => count($examples)]
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Acción no reconocida', 'code' => 'UNKNOWN_ACTION']);

} catch (Throwable $e) {
    error_log("Brand Voice Agent Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno al procesar el agente de voz',
        'code' => 'SERVER_ERROR'
    ]);
}
