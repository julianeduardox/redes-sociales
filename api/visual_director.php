<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../services/AteneaVisualDirectorService.php';

if (session_status() === PHP_SESSION_NONE) session_start();
function visualResponse(bool $success, mixed $data = null, string $error = '', string $code = ''): never {
    $out = ['success' => $success];
    if ($success) $out['data'] = $data;
    else { $out['error'] = $error; $out['code'] = $code; }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}
if (empty($_SESSION['user_id'])) { http_response_code(401); visualResponse(false, null, 'Sesión no válida', 'UNAUTHENTICATED'); }
$userId = (int)$_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = $method === 'POST' ? (json_decode(file_get_contents('php://input') ?: '{}', true) ?: $_POST) : $_GET;
$action = trim((string)($input['action'] ?? 'list'));
if ($method === 'POST') {
    $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrf)) { http_response_code(403); visualResponse(false, null, 'Token CSRF inválido', 'CSRF_INVALID'); }
    Security::requireRateLimit('visual_director_' . $userId, 30, 60);
}
$requestedBrand = (int)($input['brand_voice_id'] ?? 0);
$pdo = Database::getConnection();
$brandStmt = $requestedBrand > 0
    ? $pdo->prepare('SELECT id FROM brand_voices WHERE id = :id AND user_id = :uid LIMIT 1')
    : $pdo->prepare('SELECT id FROM brand_voices WHERE user_id = :uid ORDER BY is_default DESC, id ASC LIMIT 1');
$brandStmt->execute($requestedBrand > 0 ? [':id' => $requestedBrand, ':uid' => $userId] : [':uid' => $userId]);
$brandVoiceId = (int)($brandStmt->fetchColumn() ?: 1);
try {
    if ($action === 'list') visualResponse(true, ['references' => AteneaVisualDirectorService::listReferences($userId, $brandVoiceId), 'brand_voice_id' => $brandVoiceId]);
    if ($action === 'add') {
        $result = AteneaVisualDirectorService::addReference($userId, $brandVoiceId, (string)($input['prompt'] ?? ''), (string)($input['aspect_ratio'] ?? '4:5'), !empty($input['is_favorite']));
        visualResponse(true, $result);
    }
    if ($action === 'archive') {
        $ok = AteneaVisualDirectorService::archiveReference($userId, $brandVoiceId, (int)($input['reference_id'] ?? 0));
        if (!$ok) { http_response_code(404); visualResponse(false, null, 'Referencia no encontrada', 'NOT_FOUND'); }
        visualResponse(true, ['archived' => true]);
    }
    if ($action === 'normalize_ratio') visualResponse(true, ['prompt' => AteneaVisualDirectorService::normalizeAspectRatio((string)($input['prompt'] ?? ''), (string)($input['aspect_ratio'] ?? '4:5'))]);
    http_response_code(400); visualResponse(false, null, 'Acción no válida', 'INVALID_ACTION');
} catch (InvalidArgumentException $e) { http_response_code(422); visualResponse(false, null, $e->getMessage(), 'INVALID_INPUT');
} catch (Throwable $e) { error_log('visual_director API: ' . $e->getMessage()); http_response_code(500); visualResponse(false, null, 'No se pudo completar la operación visual', 'VISUAL_ERROR'); }
