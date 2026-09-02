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
    $userId = null;

    if (!empty($signedRequest) && str_contains($signedRequest, '.')) {
        list($encodedSig, $payload) = explode('.', $signedRequest, 2);
        $sig = base64_decode(strtr($encodedSig, '-_', '+/'));
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        // Optional HMAC validation if App Secret is configured
        $appSecret = Settings::get('meta_app_secret', '');
        if (!empty($appSecret)) {
            $expectedSig = hash_hmac('sha256', $payload, $appSecret, true);
            if (!hash_equals($sig, $expectedSig)) {
                Security::sendJsonError('Firma de signed_request inválida.');
                exit;
            }
        }

        $userId = $data['user_id'] ?? null;
    }

    $confirmationCode = 'del_' . bin2hex(random_bytes(10));

    // Delete or disassociate any comments/records if userId is identified
    if (!empty($userId)) {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("DELETE FROM comments WHERE external_comment_id LIKE :user_prefix");
            $stmt->execute([':user_prefix' => "%$userId%"]);
        } catch (Throwable $e) {
            error_log("Data deletion cleanup error: " . $e->getMessage());
        }
    }

    // Determine host and script path dynamically
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUri = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\');
    $statusUrl = $protocol . '://' . $host . ($baseUri !== '' ? $baseUri : '') . '/data-deletion.php?id=' . urlencode($confirmationCode);

    echo json_encode([
        'url' => $statusUrl,
        'confirmation_code' => $confirmationCode
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar callback de eliminación de datos de Meta.', $e);
}
