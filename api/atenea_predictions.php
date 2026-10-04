<?php
/**
 * REST API: Atenea Predictions & Learning Ledger (Fase 5)
 * Hardened with Multi-Tenant Isolation, CSRF Validation & Observational Integrity
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/AteneaLearningEngine.php';

Security::applySecurityHeaders(true);
Auth::requireAuth(true);

$userId = Auth::id();
Auth::releaseSessionLock();

header('Content-Type: application/json; charset=utf-8');

try {
    Security::requireRateLimit('atenea_predictions_api_' . $userId, 120, 60);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $action = $_GET['action'] ?? ($method === 'POST' ? 'create' : 'list');

    if ($method === 'POST') {
        Security::requireCsrfToken();
        $rawInput = file_get_contents('php://input');
        $payload = json_decode($rawInput, true) ?: $_POST;

        if ($action === 'create') {
            $result = AteneaLearningEngine::createPrediction($userId, $payload);
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'prediction_id' => $result['prediction_id'],
                    'prediction_validity' => $result['prediction_validity'] ?? 'VALID_PRE_PUBLICATION'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        if ($action === 'evaluate') {
            $predictionId = isset($payload['prediction_id']) ? (int)$payload['prediction_id'] : 0;
            if ($predictionId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'prediction_id requerido y válido.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $metrics = $payload['metrics'] ?? $payload;
            $result = AteneaLearningEngine::evaluatePrediction($userId, $predictionId, $metrics);

            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => ($result['prediction_validity'] ?? '') === 'RETROSPECTIVE_INVALID'
                        ? 'Predicción evaluada pero catalogada como RETROSPECTIVE_INVALID.'
                        : 'Predicción evaluada y registrada exitosamente en el Learning Ledger.',
                    'data' => $result
                ], JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => $result['error']], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Acción POST no reconocida.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // GET Requests
    if ($action === 'ledger') {
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
        $ledgerRes = AteneaLearningEngine::getLearningLedger($userId, $limit);
        echo json_encode($ledgerRes, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'candidates') {
        $candRes = AteneaLearningEngine::getExperimentCandidates($userId);
        echo json_encode($candRes, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Default: list predictions and summary metrics
    $filters = [
        'platform' => $_GET['platform'] ?? null,
        'status' => $_GET['status'] ?? null,
        'outcome' => $_GET['outcome'] ?? null,
        'family' => $_GET['family'] ?? null
    ];
    $summaryRes = AteneaLearningEngine::getPredictionsSummary($userId, $filters);
    echo json_encode($summaryRes, JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("api/atenea_predictions.php error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error interno al procesar predicciones.'], JSON_UNESCAPED_UNICODE);
}
