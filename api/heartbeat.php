<?php
/**
 * Autonomous Heartbeat Endpoint
 * Called periodically (every 3 minutes) by the client or background worker.
 * 
 * Responsibilities:
 * 1. Validates Authentication & Anti-CSRF
 * 2. Drains pending events from webhook_queue
 * 3. Executes Meta quickSync (metrics update & autonomous auto-reply)
 * 4. Sweeps pending comments for autopilot
 * 5. Returns real-time health and synchronization metrics
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../services/AiAgentService.php';
require_once __DIR__ . '/../services/MetaApiService.php';

if (!defined('PROCESS_QUEUE_LIB_ONLY')) {
    define('PROCESS_QUEUE_LIB_ONLY', true);
}
require_once __DIR__ . '/../cron/process_queue.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$pdo = Database::getConnection();

try {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Método no permitido. Utiliza POST.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // CSRF Protection
    Security::requireCsrf();

    // General rate limit: up to 30 heartbeat requests / minute per user
    Security::requireRateLimit('heartbeat_req_' . $userId, 30, 60);

    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
    $forceSync = !empty($input['force']);

    // 1. Process pending items from webhook queue (Fast, local DB processing)
    $queueStats = processWebhookQueue($pdo, 20, null, true);

    // 2. Determine if Meta quickSync is due (Rate limiting quickSync to 1 per 75 seconds unless forced)
    $cacheKey = "last_meta_quicksync_user_{$userId}";
    $lastSyncTime = (int)Settings::get($cacheKey, 0, $userId);
    $now = time();
    $minInterval = 75; // minimum seconds between full Meta quickSync calls
    $syncDue = $forceSync || (($now - $lastSyncTime) >= $minInterval);

    $quickSyncStats = null;
    if ($syncDue) {
        $quickSyncStats = MetaApiService::quickSync($userId);
        Settings::set($cacheKey, (string)$now, $userId);
    }

    $autopilotEnabled = Settings::get('autopilot_enabled', '0', $userId) === '1';

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'message' => 'Heartbeat procesado correctamente.',
        'data' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'autopilot_enabled' => $autopilotEnabled,
            'webhook_queue' => [
                'processed' => $queueStats['processed_events'] ?? 0,
                'comments_ingested' => $queueStats['comments_ingested'] ?? 0,
                'autopilot_replies' => $queueStats['autopilot_replies'] ?? 0
            ],
            'quick_sync_executed' => $syncDue,
            'quick_sync' => $quickSyncStats,
            'next_sync_in_seconds' => $syncDue ? $minInterval : max(0, $minInterval - ($now - $lastSyncTime))
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
    error_log("Heartbeat error: " . $e->getMessage());
    Security::sendJsonError('Error al ejecutar el latido de sincronización autónoma.', $e);
}
