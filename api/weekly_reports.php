<?php
/**
 * REST API: Weekly Reports & Efficiency Metrics Controller
 * Hardened with Multi-Tenant Isolation, CSRF & Rate Limiting
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/WeeklyReportAgentService.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $action = Security::sanitizeString($_GET['action'] ?? 'list', 50);

        if ($action === 'inbox_summary') {
            $summary = WeeklyReportAgentService::getInboxSummary($userId);
            echo json_encode(['success' => true, 'data' => $summary], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'details') {
            $reportId = Security::sanitizeInt($_GET['id'] ?? 0, 1, 10000000, 0);
            if ($reportId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'ID de reporte inválido.']);
                exit;
            }

            $details = WeeklyReportAgentService::getReportDetails($userId, $reportId);
            if (!$details) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Reporte no encontrado.']);
                exit;
            }

            echo json_encode(['success' => true, 'data' => $details], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'kpi_drilldown') {
            $kpi = Security::sanitizeString($_GET['kpi'] ?? 'replied', 30);
            $limit = Security::sanitizeInt($_GET['limit'] ?? 60, 1, 100, 60);
            $comments = WeeklyReportAgentService::getReportCommentsByKpi($userId, $kpi, $limit);
            echo json_encode([
                'success' => true,
                'kpi' => $kpi,
                'count' => count($comments),
                'comments' => $comments
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'sla_breakdown') {
            $sla = WeeklyReportAgentService::getSlaBreakdown($userId);
            echo json_encode([
                'success' => true,
                'data' => $sla
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Default: List recent reports
        $limit = Security::sanitizeInt($_GET['limit'] ?? 20, 1, 100, 20);
        $reports = WeeklyReportAgentService::getReportsList($userId, $limit);

        echo json_encode([
            'success' => true,
            'data' => $reports,
            'count' => count($reports)
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        Security::requireCsrf();
        Security::requireRateLimit('weekly_reports_ops_' . $userId, 30, 60);

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST;
        $action = Security::validateEnum($input['action'] ?? 'run_cleanup', ['run_cleanup'], 'run_cleanup');

        if ($action === 'run_cleanup') {
            $archive = !empty($input['archive']) || !isset($input['archive']);
            $result = WeeklyReportAgentService::runCleanupAndReport($userId, $archive);

            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
} catch (Throwable $e) {
    error_log("Weekly Reports API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno al procesar reportes semanales: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
