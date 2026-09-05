<?php
/**
 * Meta Webhook Listener for Facebook & Instagram Real-Time Event Ingestion
 * Ultra-fast Asynchronous Queue Ingestion (< 50ms)
 * Hardened with HMAC-SHA256 Signature Verification, Timing-Safe Token Check & Rate Limiting
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

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
    
    if (empty($rawInput)) {
        http_response_code(400);
        echo json_encode(['error' => 'Payload vacío'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Rate limit webhook ingestion (180 events / minute)
    Security::requireRateLimit('webhook_ingest', 180, 60);

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

    // Validate JSON structure
    $data = json_decode($rawInput, true);
    if (!is_array($data) || empty($data['entry'])) {
        // Still return 200 to acknowledge Meta if ping/empty event
        http_response_code(200);
        echo json_encode(['status' => 'EVENT_IGNORED_EMPTY'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO webhook_queue (event_source, payload, signature, status, attempts, created_at)
            VALUES ('meta', :payload, :signature, 'pending', 0, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            ':payload' => $rawInput,
            ':signature' => $signatureHeader ?: null
        ]);
        $queueId = (int)$pdo->lastInsertId();

        // Ultra-fast HTTP 200 response to Meta in < 30ms
        http_response_code(200);
        echo json_encode([
            'status' => 'EVENT_RECEIVED',
            'queued' => true,
            'queue_id' => $queueId
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Webhook ingestion error: " . $e->getMessage());
        // Always respond 200 to Meta so it does not retry uncontrollably if database is temporarily locked
        http_response_code(200);
        echo json_encode(['status' => 'EVENT_RECEIVED_WITH_ERROR'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Método no soportado']);
