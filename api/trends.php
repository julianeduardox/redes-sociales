<?php
/**
 * API Endpoint: Agente de Tendencias
 * Ruta: /api/trends.php
 *
 * Acciones disponibles:
 *   GET  ?action=get_niches       — Lista hashtags del usuario
 *   GET  ?action=get_trending     — Posts en tendencia (Top 20)
 *   GET  ?action=get_ai_picks     — Top 3 AI Picks
 *   GET  ?action=get_insights     — Insights automáticos del nicho
 *   POST {"action":"add_niche"}   — Agregar hashtag a monitorear
 *   POST {"action":"remove_niche"}— Eliminar hashtag
 *   POST {"action":"sync_now"}    — Sincronización manual inmediata
 *   POST {"action":"inspire_me"}  — Generar caption inspirado (IA)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Bootstrap ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../services/TrendsAgentService.php';

// ─── Sesión ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();

function jsonResponse(bool $success, mixed $data = null, string $error = '', string $code = ''): never {
    $response = ['success' => $success];
    if ($success && $data !== null) $response['data'] = $data;
    if (!$success) {
        $response['error'] = $error;
        if ($code) $response['code'] = $code;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ─── Autenticación ─────────────────────────────────────────────────────────
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    jsonResponse(false, null, 'No autenticado', 'UNAUTHENTICATED');
}

$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── Parseo de entrada ──────────────────────────────────────────────────────
$input = [];
if ($method === 'POST') {
    $raw   = file_get_contents('php://input');
    $input = json_decode($raw ?: '{}', true) ?? [];
    if (empty($input)) $input = $_POST;
}

$action = match($method) {
    'GET'   => trim($_GET['action'] ?? ''),
    'POST'  => trim($input['action'] ?? ''),
    default => '',
};

// ─── CSRF — Mutaciones POST ─────────────────────────────────────────────────
$mutatingActions = ['add_niche', 'remove_niche', 'sync_now', 'inspire_me'];
if (in_array($action, $mutatingActions, true)) {
    $csrfToken   = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($csrfToken) || empty($sessionToken) || !hash_equals($sessionToken, $csrfToken)) {
        http_response_code(403);
        jsonResponse(false, null, 'Token CSRF inválido o ausente', 'CSRF_INVALID');
    }
}

// ─── Rate limiting básico para sync_now ────────────────────────────────────
if ($action === 'sync_now') {
    $syncKey = 'trend_sync_' . $userId;
    $lastSync = $_SESSION[$syncKey] ?? 0;
    if ((time() - $lastSync) < 60) {
        jsonResponse(false, null, 'Por favor espera 1 minuto entre sincronizaciones manuales', 'RATE_LIMITED');
    }
    $_SESSION[$syncKey] = time();
}

// ─── Enrutamiento ───────────────────────────────────────────────────────────
switch ($action) {

    // ── GET: Lista de nichos (hashtags) del usuario ─────────────────────────
    case 'get_niches': {
        $niches = TrendsAgentService::getNiches($userId);
        jsonResponse(true, [
            'niches' => $niches,
            'count'  => count($niches),
            'max'    => 10,
        ]);
    }

    // ── GET: Posts en tendencia ─────────────────────────────────────────────
    case 'get_trending': {
        $nicheId = isset($_GET['niche_id']) ? (int)$_GET['niche_id'] : null;
        $limit   = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $posts   = TrendsAgentService::getTrendingPosts($userId, $nicheId, $limit);
        jsonResponse(true, [
            'posts' => $posts,
            'total' => count($posts),
        ]);
    }

    // ── GET: Top 3 AI Picks ─────────────────────────────────────────────────
    case 'get_ai_picks': {
        $picks = TrendsAgentService::getAiTopPicks($userId);
        jsonResponse(true, ['picks' => $picks]);
    }

    // ── GET: Insights automáticos ───────────────────────────────────────────
    case 'get_insights': {
        $insights = TrendsAgentService::generateTrendInsights($userId);
        jsonResponse($insights['success'] ?? true, $insights);
    }

    // ── POST: Agregar hashtag ───────────────────────────────────────────────
    case 'add_niche': {
        $hashtag  = trim($input['hashtag'] ?? '');
        $platform = trim($input['platform'] ?? 'instagram');
        if (empty($hashtag)) {
            jsonResponse(false, null, 'El hashtag es requerido', 'MISSING_HASHTAG');
        }
        $result = TrendsAgentService::addNiche($userId, $hashtag, $platform);
        jsonResponse($result['success'], $result['success'] ? $result : null, $result['error'] ?? '');
    }

    // ── POST: Eliminar hashtag ──────────────────────────────────────────────
    case 'remove_niche': {
        $nicheId = (int)($input['niche_id'] ?? 0);
        if ($nicheId <= 0) {
            jsonResponse(false, null, 'niche_id requerido', 'MISSING_NICHE_ID');
        }
        $result = TrendsAgentService::removeNiche($userId, $nicheId);
        jsonResponse($result['success'], null, $result['error'] ?? '');
    }

    // ── POST: Sincronización manual ─────────────────────────────────────────
    case 'sync_now': {
        $nicheId = isset($input['niche_id']) ? (int)$input['niche_id'] : null;

        if ($nicheId !== null && $nicheId > 0) {
            // Sincronizar solo un nicho específico
            $result = TrendsAgentService::syncNicheTrends($userId, $nicheId);
            jsonResponse($result['success'], $result, $result['error'] ?? '');
        } else {
            // Sincronizar todos los nichos
            $result = TrendsAgentService::syncAllNiches($userId);
            jsonResponse($result['success'], $result, $result['error'] ?? '');
        }
    }

    // ── POST: Inspirarme (generar captions con IA) ──────────────────────────
    case 'inspire_me': {
        $trendPostId  = (int)($input['trend_post_id'] ?? 0);
        $brandVoiceId = (int)($input['brand_voice_id'] ?? 1);

        if ($trendPostId <= 0) {
            jsonResponse(false, null, 'trend_post_id requerido', 'MISSING_POST_ID');
        }

        $result = TrendsAgentService::generateTrendInspiredCaption($userId, $trendPostId, $brandVoiceId);
        jsonResponse($result['success'], $result, $result['error'] ?? '');
    }

    // ── Acción desconocida ──────────────────────────────────────────────────
    default: {
        http_response_code(400);
        jsonResponse(false, null, "Acción desconocida: '$action'. Acciones válidas: get_niches, get_trending, get_ai_picks, get_insights, add_niche, remove_niche, sync_now, inspire_me", 'UNKNOWN_ACTION');
    }
}
