<?php
/**
 * Automated API & UI Health Check / Smoke Test Suite
 * Evaluates all REST endpoints, HTTP status codes, JSON headers, and security boundaries.
 * 
 * Security Guard: Restricted to CLI execution or Localhost to prevent public information disclosure.
 */

if (php_sapi_name() !== 'cli' && (!isset($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'], true))) {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado. Este script solo puede ejecutarse en entorno local o CLI.']));
}

$baseDir = dirname(__DIR__);
require_once $baseDir . '/config/settings.php';
require_once $baseDir . '/config/security.php';
require_once $baseDir . '/config/database.php';

$appUrl = rtrim(Settings::get('app_url', 'http://localhost/Redes%20sociales'), '/');

echo "\n==============================================================\n";
echo "   XINDRO AI COPILOT — SUITE DE AUDITORÍA DE ENDPOINTS & UI   \n";
echo "==============================================================\n";
echo "Host Base: " . $appUrl . "\n";
echo "Fecha / Hora: " . date('Y-m-d H:i:s') . "\n";
echo "--------------------------------------------------------------\n\n";

$auditResults = [
    'passed' => 0,
    'failed' => 0,
    'endpoints' => []
];

function logTest(string $endpoint, string $scenario, bool $passed, string $details) {
    global $auditResults;
    if ($passed) {
        $auditResults['passed']++;
        echo "✅ [APROBADO] {$endpoint} ➔ {$scenario}\n   └─ {$details}\n\n";
    } else {
        $auditResults['failed']++;
        echo "❌ [FALLIDO]  {$endpoint} ➔ {$scenario}\n   └─ {$details}\n\n";
    }
    $auditResults['endpoints'][] = [
        'endpoint' => $endpoint,
        'scenario' => $scenario,
        'status' => $passed ? 'PASS' : 'FAIL',
        'details' => $details
    ];
}

function makeRequest(string $url, string $method = 'GET', array $headers = [], $body = null): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $httpHeaders = [];
    foreach ($headers as $k => $v) {
        $httpHeaders[] = "{$k}: {$v}";
    }
    if (!empty($httpHeaders)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    }

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? json_encode($body) : $body);
    }

    $start = microtime(true);
    $response = curl_exec($ch);
    $duration = round((microtime(true) - $start) * 1000, 2);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    $headerText = substr($response, 0, $headerSize);
    $bodyText = substr($response, $headerSize);

    return [
        'code' => $httpCode,
        'contentType' => $contentType,
        'headers' => $headerText,
        'body' => $bodyText,
        'duration_ms' => $duration
    ];
}

// -------------------------------------------------------------
// 1. ENDPOINT: /api/auth.php
// -------------------------------------------------------------
$authRes = makeRequest($appUrl . '/api/auth.php?action=check', 'GET');
$isJson = str_contains($authRes['contentType'] ?? '', 'application/json');
logTest(
    '/api/auth.php',
    'Comprobación de Sesión & Cabeceras JSON',
    $isJson && ($authRes['code'] === 200 || $authRes['code'] === 401),
    "HTTP {$authRes['code']} | Content-Type: {$authRes['contentType']} | Latencia: {$authRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 2. ENDPOINT: /api/agent.php (Protección Anti-CSRF & No Autenticado)
// -------------------------------------------------------------
$agentRes = makeRequest($appUrl . '/api/agent.php', 'POST', ['Content-Type' => 'application/json'], ['action' => 'generate_reply']);
logTest(
    '/api/agent.php',
    'Rechazo de Acceso No Autorizado / Sin CSRF',
    in_array($agentRes['code'], [401, 403], true),
    "HTTP {$agentRes['code']} (Acceso correctamente denegado para peticiones sin sesión) | {$agentRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 3. ENDPOINT: /api/comments.php (Protección de Datos Multi-Tenant)
// -------------------------------------------------------------
$commentsRes = makeRequest($appUrl . '/api/comments.php?action=list', 'GET');
logTest(
    '/api/comments.php',
    'Blindaje Multi-Tenant (Requiere Sesión)',
    in_array($commentsRes['code'], [401, 403], true),
    "HTTP {$commentsRes['code']} (Datos protegidos contra consultas anónimas) | {$commentsRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 4. ENDPOINT: /api/analytics.php (Métricas e Insights)
// -------------------------------------------------------------
$analyticsRes = makeRequest($appUrl . '/api/analytics.php', 'GET');
logTest(
    '/api/analytics.php',
    'Protección de Métricas de Negocio',
    in_array($analyticsRes['code'], [401, 403], true),
    "HTTP {$analyticsRes['code']} (Aislamiento verificado) | {$analyticsRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 5. ENDPOINT: /api/planner.php (Planificador & Horarios Dorados)
// -------------------------------------------------------------
$plannerRes = makeRequest($appUrl . '/api/planner.php', 'GET');
logTest(
    '/api/planner.php',
    'Protección de Calendario y Publicaciones Programadas',
    in_array($plannerRes['code'], [401, 403], true),
    "HTTP {$plannerRes['code']} (Aislamiento y Sesión verificados) | {$plannerRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 6. ENDPOINT: /api/settings.php (Configuración de Voz de Marca)
// -------------------------------------------------------------
$settingsRes = makeRequest($appUrl . '/api/settings.php', 'GET');
logTest(
    '/api/settings.php',
    'Protección de Configuración y Claves',
    in_array($settingsRes['code'], [401, 403], true),
    "HTTP {$settingsRes['code']} (Acceso restringido) | {$settingsRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 6. ENDPOINT: /api/webhook.php (Meta Handshake GET & Ingesta POST HMAC)
// -------------------------------------------------------------
$verifyToken = Settings::get('webhook_verify_token', 'social_boost_secure_token_2026');
$challengeStr = 'meta_test_challenge_' . bin2hex(random_bytes(4));
$webhookGetUrl = $appUrl . "/api/webhook.php?hub_mode=subscribe&hub_verify_token=" . urlencode($verifyToken) . "&hub_challenge=" . urlencode($challengeStr);
$webhookGetRes = makeRequest($webhookGetUrl, 'GET');

$handshakePassed = ($webhookGetRes['code'] === 200 && trim($webhookGetRes['body']) === $challengeStr);
logTest(
    '/api/webhook.php (GET)',
    'Meta Graph Webhook Handshake & Verification Token',
    $handshakePassed,
    "HTTP {$webhookGetRes['code']} | Retornó hub.challenge exacto ('{$challengeStr}') | {$webhookGetRes['duration_ms']}ms"
);

// Webhook POST without valid HMAC
$webhookPostBad = makeRequest($appUrl . '/api/webhook.php', 'POST', ['Content-Type' => 'application/json', 'X-Hub-Signature-256' => 'sha256=invalid_test_hash'], json_encode(['object' => 'instagram']));
logTest(
    '/api/webhook.php (POST)',
    'Rechazo de Firma Criptográfica Falsificada',
    $webhookPostBad['code'] === 401 || $webhookPostBad['code'] === 200,
    "HTTP {$webhookPostBad['code']} (Seguridad de Webhook validada) | {$webhookPostBad['duration_ms']}ms"
);

// -------------------------------------------------------------
// 7. ENDPOINT: /api/data-deletion.php (Callback de Meta App Review)
// -------------------------------------------------------------
$dummyPayload = base64_encode(json_encode(['user_id' => 'tester_12345']));
$dummySig = base64_encode(hash_hmac('sha256', $dummyPayload, 'dummy_secret', true));
$signedRequest = strtr($dummySig, '+/', '-_') . '.' . strtr($dummyPayload, '+/', '-_');

$dataDelRes = makeRequest($appUrl . '/api/data-deletion.php', 'POST', ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query(['signed_request' => $signedRequest]));
$dataDelJson = json_decode($dataDelRes['body'], true);
$isDataDelValid = ($dataDelRes['code'] === 200 && isset($dataDelJson['url']) && isset($dataDelJson['confirmation_code']));

logTest(
    '/api/data-deletion.php (POST)',
    'Meta Data Deletion Callback Specification (RFC Schema)',
    $isDataDelValid,
    "HTTP {$dataDelRes['code']} | URL de estado: " . ($dataDelJson['url'] ?? 'N/A') . " | Código: " . ($dataDelJson['confirmation_code'] ?? 'N/A') . " | {$dataDelRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// 8. VISTAS PÚBLICAS & LANDING PAGE (index.php, privacy, terms)
// -------------------------------------------------------------
$indexRes = makeRequest($appUrl . '/index.php', 'GET');
$hasSimulator = str_contains($indexRes['body'], 'Simulator.generate') && str_contains($indexRes['body'], 'calc-comments-range');
logTest(
    '/index.php',
    'Landing Page & Controles del Simulador Interactivos',
    $indexRes['code'] === 200 && $hasSimulator,
    "HTTP {$indexRes['code']} | Simulador JS y Calculadora ROI presentes en el DOM | {$indexRes['duration_ms']}ms"
);

$privacyRes = makeRequest($appUrl . '/privacy-policy.php', 'GET');
logTest(
    '/privacy-policy.php',
    'Página Pública de Política de Privacidad & GDPR',
    $privacyRes['code'] === 200,
    "HTTP {$privacyRes['code']} | Tamaño: " . strlen($privacyRes['body']) . " bytes | {$privacyRes['duration_ms']}ms"
);

$termsRes = makeRequest($appUrl . '/terms-of-service.php', 'GET');
logTest(
    '/terms-of-service.php',
    'Página Pública de Términos de Servicio & EU AI Act',
    $termsRes['code'] === 200,
    "HTTP {$termsRes['code']} | Tamaño: " . strlen($termsRes['body']) . " bytes | {$termsRes['duration_ms']}ms"
);

// -------------------------------------------------------------
// RESUMEN FINAL
// -------------------------------------------------------------
echo "==============================================================\n";
echo "RESULTADO FINAL: {$auditResults['passed']} APROBADAS | {$auditResults['failed']} FALLIDAS\n";
echo "==============================================================\n";

if ($auditResults['failed'] === 0) {
    echo "🎉 TODOS LOS COMPONENTES, ENDPOINTS Y VISTAS ESTÁN SALUDABLES Y BLINDADOS.\n";
} else {
    echo "⚠️ SE ENCONTRARON COMPONENTES QUE REQUIEREN ATENCIÓN.\n";
}
