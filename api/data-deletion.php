<?php
/**
 * REST API: Meta User Data Deletion Callback Endpoint
 * Implements the official Meta Data Deletion Request Callback specification.
 * Returns JSON: {"url": "<status_url>", "confirmation_code": "<confirmation_code>"}
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';

Security::applySecurityHeaders(true);

try {
    Security::requireRateLimit('data_deletion_cb', 60, 60);

    $signedRequest = $_POST['signed_request'] ?? '';
    if (empty($signedRequest) || !str_contains($signedRequest, '.')) {
        http_response_code(400);
        echo json_encode(['error' => 'Parámetro signed_request obligatorio y con formato válido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $appSecret = getenv('META_APP_SECRET') ?: ($_ENV['META_APP_SECRET'] ?? Settings::get('meta_app_secret', ''));
    if (empty($appSecret)) {
        http_response_code(403);
        echo json_encode(['error' => 'Meta App Secret no configurado en el servidor. Petición rechazada.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    list($encodedSig, $payload) = explode('.', $signedRequest, 2);
    $sig = base64_decode(strtr($encodedSig, '-_', '+/'));
    $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Payload de signed_request inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Strict HMAC validation (Fail-Closed)
    $expectedSig = hash_hmac('sha256', $payload, $appSecret, true);
    if (!hash_equals($sig, $expectedSig)) {
        http_response_code(403);
        echo json_encode(['error' => 'Firma de signed_request inválida'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $userId = !empty($data['user_id']) ? preg_replace('/[^0-9]/', '', (string)$data['user_id']) : null;
    $confirmationCode = 'del_' . bin2hex(random_bytes(10));

    // Disassociate/wipe connected accounts matching exact user/page ID (No wildcard deletions)
    if (!empty($userId)) {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                UPDATE accounts 
                SET is_active = 0, access_token = NULL 
                WHERE page_id = :uid
            ");
            $stmt->execute([':uid' => $userId]);

            // Safe audit logging in data directory
            $auditLog = __DIR__ . '/../data/data_deletion_audit.log';
            @file_put_contents(
                $auditLog, 
                date('c') . " | Meta Data Deletion | User: {$userId} | Code: {$confirmationCode}" . PHP_EOL, 
                FILE_APPEND | LOCK_EX
            );
        } catch (Throwable $e) {
            error_log("Data deletion cleanup error: " . $e->getMessage());
        }
    }

    // Canonical status URL (Immune to Host Header Poisoning)
    $statusUrl = Security::getAppUrl() . '/data-deletion.php?id=' . urlencode($confirmationCode);

    echo json_encode([
        'url' => $statusUrl,
        'confirmation_code' => $confirmationCode
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar callback de eliminación de datos de Meta.', $e);
}
