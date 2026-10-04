<?php
/**
 * Maintenance endpoint: Flush OPcache, APCu and File Cache
 * Authenticated via session OR CRON_SECRET_KEY
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../services/CacheService.php';

Database::loadEnv();

header('Content-Type: application/json; charset=utf-8');

$cronSecret = getenv('CRON_SECRET_KEY') ?: ($_ENV['CRON_SECRET_KEY'] ?? Settings::get('cron_secret_key', ''));
$providedKey = $_GET['key'] ?? ($_POST['key'] ?? '');

$isAuth = false;

// 1. Check CRON_SECRET_KEY
if (!empty($cronSecret) && !empty($providedKey) && hash_equals($cronSecret, $providedKey)) {
    $isAuth = true;
}

// 2. Check active user session
if (!$isAuth) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['user_id'])) {
        $isAuth = true;
    }
}

if (!$isAuth) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit;
}

$opcacheReset = false;
if (function_exists('opcache_reset')) {
    $opcacheReset = @opcache_reset();
}

CacheService::flush();

echo json_encode([
    'success' => true,
    'message' => 'OPcache y Caché de aplicación limpiados exitosamente.',
    'opcache_reset' => $opcacheReset,
    'timestamp' => time()
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
