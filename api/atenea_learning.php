<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ API Endpoint: Atenea Learning Engine (Aprendizaje Continuo @fortaleza_imparable)
 * Ruta: /api/atenea_learning.php
 * ══════════════════════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../services/AteneaLearningEngine.php';

if (session_status() === PHP_SESSION_NONE) session_start();

function jsonResponse(bool $success, mixed $data = null, string $error = '', string $code = ''): never {
    $res = ['success' => $success];
    if ($success && $data !== null) $res['data'] = $data;
    if (!$success) {
        $res['error'] = $error;
        if ($code) $res['code'] = $code;
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    jsonResponse(false, null, 'Sesión no válida o expirada', 'UNAUTHENTICATED');
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$input = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw ?: '{}', true) ?? [];
    if (empty($input)) $input = $_POST;
}

$action = match($method) {
    'GET'  => trim($_GET['action'] ?? 'overview'),
    'POST' => trim($input['action'] ?? ''),
    default => ''
};

// CSRF check for mutations
$mutatingActions = ['rebuild', 'record_feedback'];
if (in_array($action, $mutatingActions, true)) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($csrfToken) || empty($sessionToken) || !hash_equals($sessionToken, $csrfToken)) {
        http_response_code(403);
        jsonResponse(false, null, 'Token de seguridad CSRF inválido', 'CSRF_INVALID');
    }
}

switch ($action) {
    case 'overview': {
        $data = AteneaLearningEngine::getLearningOverview($userId);
        if (!$data['success']) {
            jsonResponse(false, null, $data['error'] ?? 'Error al obtener resumen de aprendizaje', 'OVERVIEW_ERROR');
        }
        jsonResponse(true, $data);
    }

    case 'prompt_directives': {
        $platform = trim($_GET['platform'] ?? 'all');
        $directives = AteneaLearningEngine::getActivePatternsForPrompt($userId, $platform);
        jsonResponse(true, ['directives' => $directives]);
    }

    case 'natural_comparisons': {
        $overview = AteneaLearningEngine::getLearningOverview($userId);
        jsonResponse(true, [
            'comparisons' => $overview['natural_comparisons'] ?? [],
            'count' => count($overview['natural_comparisons'] ?? [])
        ]);
    }

    case 'hypotheses_matrix': {
        $overview = AteneaLearningEngine::getLearningOverview($userId);
        jsonResponse(true, [
            'matrix' => $overview['hypotheses_matrix'] ?? [],
            'continuous_percentiles' => $overview['continuous_percentiles'] ?? []
        ]);
    }

    case 'rebuild': {
        $res = AteneaLearningEngine::rebuildAll($userId);
        if (!$res['success']) {
            jsonResponse(false, null, $res['error'] ?? 'Error en la reconstrucción', 'REBUILD_ERROR');
        }
        jsonResponse(true, $res);
    }

    default:
        http_response_code(400);
        jsonResponse(false, null, 'Acción no soportada o inválida: ' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8'), 'INVALID_ACTION');
}
