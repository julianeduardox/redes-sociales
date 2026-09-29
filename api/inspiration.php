<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ API Endpoint: Radar de Creadores & Inspiración
 * Ruta: /api/inspiration.php
 * ══════════════════════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../services/InspirationRadarService.php';

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
    'GET'  => trim($_GET['action'] ?? 'get_radar'),
    'POST' => trim($input['action'] ?? ''),
    default => ''
};

// CSRF check for mutations
$mutatingActions = ['add_creator', 'remove_creator', 'sync_creator', 'sync_all', 'import_post', 'verify_quote', 'recreate', 'save_creation_status'];
if (in_array($action, $mutatingActions, true)) {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if (empty($csrfToken) || empty($sessionToken) || !hash_equals($sessionToken, $csrfToken)) {
        http_response_code(403);
        jsonResponse(false, null, 'Token de seguridad CSRF inválido', 'CSRF_INVALID');
    }
}

switch ($action) {
    case 'get_radar':
    case 'get_creators': {
        $creators = InspirationRadarService::getCreators($userId);
        $creatorId = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : null;
        $sort = trim($_GET['sort'] ?? 'engagement');
        $limit = min(60, max(1, (int)($_GET['limit'] ?? 30)));
        $posts = InspirationRadarService::getInspirationPosts($userId, $creatorId, $sort, $limit);

        jsonResponse(true, [
            'creators' => $creators,
            'posts' => $posts,
            'total_creators' => count($creators),
            'total_posts' => count($posts)
        ]);
    }

    case 'get_posts': {
        $creatorId = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : null;
        $sort = trim($_GET['sort'] ?? 'engagement');
        $limit = min(60, max(1, (int)($_GET['limit'] ?? 30)));
        $posts = InspirationRadarService::getInspirationPosts($userId, $creatorId, $sort, $limit);
        jsonResponse(true, ['posts' => $posts, 'total' => count($posts)]);
    }

    case 'add_creator': {
        $platform = trim($input['platform'] ?? 'instagram');
        $usernameOrUrl = trim($input['username'] ?? ($input['url'] ?? ''));
        $displayName = trim($input['display_name'] ?? '');

        if (empty($usernameOrUrl)) {
            jsonResponse(false, null, 'Ingresa el nombre de usuario o enlace de la cuenta', 'MISSING_USERNAME');
        }

        $res = InspirationRadarService::addCreator($userId, $platform, $usernameOrUrl, $displayName);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'remove_creator': {
        $creatorId = (int)($input['creator_id'] ?? 0);
        if ($creatorId <= 0) {
            jsonResponse(false, null, 'ID de creador requerido', 'INVALID_ID');
        }
        $res = InspirationRadarService::removeCreator($userId, $creatorId);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'sync_creator': {
        $creatorId = (int)($input['creator_id'] ?? 0);
        if ($creatorId <= 0) {
            jsonResponse(false, null, 'ID de creador requerido', 'INVALID_ID');
        }
        $res = InspirationRadarService::syncInstagramCreator($userId, $creatorId);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'sync_all': {
        $res = InspirationRadarService::syncAllCreators($userId);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'import_post': {
        $urlOrText = trim($input['url'] ?? ($input['text'] ?? ''));
        $caption = trim($input['caption'] ?? '');
        $mediaUrl = !empty($input['media_url']) ? trim($input['media_url']) : null;
        $creatorId = !empty($input['creator_id']) ? (int)$input['creator_id'] : null;

        if (empty($urlOrText) && empty($caption)) {
            jsonResponse(false, null, 'Debes ingresar un enlace, frase o texto a importar', 'MISSING_DATA');
        }

        $res = InspirationRadarService::importDirectPost($userId, $urlOrText, $caption, $mediaUrl, $creatorId);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'verify_quote': {
        $postId = (int)($input['post_id'] ?? 0);
        if ($postId <= 0) {
            jsonResponse(false, null, 'ID de publicación requerido', 'INVALID_ID');
        }
        $res = InspirationRadarService::analyzeAndVerifyQuote($userId, $postId);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'recreate': {
        $postId = (int)($input['post_id'] ?? 0);
        $brandVoiceId = (int)($input['brand_voice_id'] ?? 1);
        $forceRegenerate = !empty($input['force_regenerate']);
        if ($postId <= 0) {
            jsonResponse(false, null, 'ID de publicación requerido', 'INVALID_ID');
        }
        $res = InspirationRadarService::generateFortalezaRecreations($userId, $postId, $brandVoiceId, $forceRegenerate);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'save_creation_status': {
        $postId = (int)($input['post_id'] ?? 0);
        $variationType = trim($input['variation_type'] ?? 'short');
        $status = trim($input['status'] ?? 'approved');
        if ($postId <= 0) {
            jsonResponse(false, null, 'ID de publicación requerido', 'INVALID_ID');
        }
        $res = InspirationRadarService::saveCreationStatus($userId, $postId, $variationType, $status);
        jsonResponse($res['success'], $res['success'] ? $res : null, $res['error'] ?? '');
    }

    case 'get_creations_memory': {
        $postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : null;
        $creations = InspirationRadarService::getCreationsMemory($userId, $postId);
        jsonResponse(true, ['creations' => $creations]);
    }

    default: {
        http_response_code(400);
        jsonResponse(false, null, 'Acción no válida o no soportada', 'INVALID_ACTION');
    }
}
