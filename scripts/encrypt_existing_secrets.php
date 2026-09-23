<?php
/**
 * Data Migration Script: Encrypt Existing Plaintext Secrets (AES-256-GCM)
 * Safely migrates existing tokens in `settings` and `accounts` tables to authenticated ciphertext
 */

// Seguridad: Restricción exclusiva a entorno de consola (CLI)
if (php_sapi_name() !== 'cli' && !defined('STDIN')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado: este script de migración solo puede ejecutarse vía CLI.']);
    exit(1);
}

require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/CacheService.php';

echo "=== MIGRACIÓN DE CIFRADO EN REPOSO (AES-256-GCM) ===" . PHP_EOL;

try {
    $pdo = Database::getConnection();

    // 1. Encrypt sensitive settings
    $sensitiveKeys = [
        'openrouter_api_key',
        'meta_page_access_token',
        'meta_user_access_token',
        'meta_instagram_token',
        'meta_app_secret',
        'cron_secret_key',
        'webhook_verify_token'
    ];

    $inClause = "'" . implode("','", $sensitiveKeys) . "'";
    $stmt = $pdo->query("SELECT user_id, key, value FROM settings WHERE key IN ({$inClause})");
    $settingsRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $settingsEncrypted = 0;
    foreach ($settingsRows as $row) {
        $val = (string)($row['value'] ?? '');
        if (!empty($val) && !str_starts_with($val, 'enc:v1:')) {
            $encryptedVal = Security::encrypt($val);
            $upStmt = $pdo->prepare("UPDATE settings SET value = :val WHERE user_id = :uid AND key = :key");
            $upStmt->execute([
                ':val' => $encryptedVal,
                ':uid' => $row['user_id'],
                ':key' => $row['key']
            ]);
            $settingsEncrypted++;
            echo " [SETTINGS] Cifrado exitoso para clave '{$row['key']}' (Usuario ID: {$row['user_id']})" . PHP_EOL;
        }
    }

    // 2. Encrypt account access tokens
    $stmtAcc = $pdo->query("SELECT id, user_id, platform, account_name, access_token FROM accounts WHERE access_token IS NOT NULL AND access_token != ''");
    $accountsRows = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);

    $accountsEncrypted = 0;
    foreach ($accountsRows as $acc) {
        $token = (string)($acc['access_token'] ?? '');
        if (!empty($token) && !str_starts_with($token, 'enc:v1:')) {
            $encryptedToken = Security::encrypt($token);
            $upAcc = $pdo->prepare("UPDATE accounts SET access_token = :token WHERE id = :id");
            $upAcc->execute([
                ':token' => $encryptedToken,
                ':id' => $acc['id']
            ]);
            $accountsEncrypted++;
            echo " [ACCOUNTS] Cifrado exitoso para cuenta '{$acc['account_name']}' [{$acc['platform']}] (ID: {$acc['id']})" . PHP_EOL;
        }
    }

    // 3. Clear cache
    CacheService::flush();

    echo "=== RESUMEN DE MIGRACIÓN ===" . PHP_EOL;
    echo " Configuraciones sensibles cifradas: {$settingsEncrypted}" . PHP_EOL;
    echo " Tokens de cuentas conectados cifrados: {$accountsEncrypted}" . PHP_EOL;
    echo " Caché invalidado y purgado correctamente." . PHP_EOL;
    echo " ESTADO: CIFRADO COMPLETO Y ASEGURADO." . PHP_EOL;

} catch (Throwable $e) {
    echo "ERROR DURANTE LA MIGRACIÓN: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
