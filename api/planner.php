<?php
/**
 * REST API: Content Planner & Auto-Scheduler Controller
 * Hardened with Multi-Tenant User Isolation, CSRF Validation & Rate Limiting
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/ContentPlannerService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    Security::requireRateLimit('planner_api_' . $userId, 100, 60);

    if ($method === 'GET') {
        $action = Security::validateEnum($_GET['action'] ?? 'calendar', ['calendar', 'golden_slots'], 'calendar');
        $platform = Security::validateEnum($_GET['platform'] ?? 'all', ['all', 'instagram', 'facebook'], 'all');

        if ($action === 'golden_slots') {
            $days = isset($_GET['days']) && is_numeric($_GET['days']) ? min(30, max(1, (int)$_GET['days'])) : 14;
            $accountId = isset($_GET['account_id']) && is_numeric($_GET['account_id']) ? (int)$_GET['account_id'] : null;
            $slots = ContentPlannerService::getUpcomingGoldenSlots($userId, $platform, $accountId, $days);

            echo json_encode([
                'success' => true,
                'days_projected' => $days,
                'platform' => $platform,
                'golden_slots' => $slots
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Calendar Action
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-t');

        // Sanitize dates (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = date('Y-m-t');

        $posts = ContentPlannerService::getCalendarPosts($userId, $startDate, $endDate, $platform);
        $goldenSlots = ContentPlannerService::getUpcomingGoldenSlots($userId, $platform, null, 14);

        $counts = [
            'total' => count($posts),
            'scheduled' => 0,
            'published' => 0,
            'draft' => 0
        ];
        foreach ($posts as $p) {
            $st = $p['status'] ?? 'scheduled';
            if (isset($counts[$st])) $counts[$st]++;
        }

        echo json_encode([
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'platform' => $platform,
            'counts' => $counts,
            'posts' => $posts,
            'golden_slots' => $goldenSlots
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;

    } elseif ($method === 'POST') {
        Security::requireCsrf();
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $action = Security::validateEnum($data['action'] ?? '', ['generate', 'save', 'delete', 'publish_now'], '');

        if (empty($action)) {
            Security::sendJsonError("Acción de planificador no válida o no especificada.", null, 400);
        }

        if ($action === 'generate') {
            $topic = Security::sanitizeString($data['topic'] ?? '', 1000);
            if (empty($topic)) {
                Security::sendJsonError("Por favor introduce una idea o tema para el contenido.", null, 422);
            }

            $platform = Security::validateEnum($data['platform'] ?? 'instagram', ['instagram', 'facebook'], 'instagram');
            $format = Security::validateEnum($data['format'] ?? 'reel', ['reel', 'video', 'carousel', 'image', 'story'], 'reel');
            $goal = Security::validateEnum($data['goal'] ?? 'connection', ['connection', 'conversion', 'authority'], 'connection');

            $result = ContentPlannerService::generateContentDrafts($userId, $topic, $platform, $format, $goal);
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        } elseif ($action === 'save') {
            $saveRes = ContentPlannerService::saveScheduledPost($userId, $data);
            echo json_encode($saveRes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        } elseif ($action === 'delete') {
            $postId = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : 0;
            if ($postId <= 0) {
                Security::sendJsonError("ID de publicación no válido.", null, 422);
            }
            $deleted = ContentPlannerService::deleteScheduledPost($userId, $postId);
            echo json_encode([
                'success' => $deleted,
                'message' => $deleted ? 'Publicación eliminada del calendario.' : 'No se pudo eliminar la publicación.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;

        } elseif ($action === 'publish_now') {
            $postId = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : 0;
            if ($postId <= 0) {
                Security::sendJsonError("ID de publicación no válido.", null, 422);
            }
            $pubRes = ContentPlannerService::publishScheduledPostNow($userId, $postId);
            echo json_encode($pubRes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        Security::sendJsonError("Método HTTP no soportado.", null, 405);
    }
} catch (Throwable $e) {
    Security::sendJsonError('Error en el servicio del Planificador de Contenido.', $e);
}
