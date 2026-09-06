<?php
/**
 * Admin Users & AI Model / Token Quota Management Endpoint
 * Exclusively accessible by Administrator (Auth::isAdmin())
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Strict Auth & Admin Authorization Gate
Auth::requireAuth(true);

if (!Auth::isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Acceso denegado. Se requieren privilegios de Administrador para gestionar usuarios y tokens.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];

// Allowed AI Models Catalog
$allowedModels = [
    'anthropic/claude-3.5-sonnet' => [
        'name' => 'Anthropic Claude 3.5 Sonnet',
        'badge' => '⭐ Recomendado',
        'desc' => 'Tono más humano, empático, natural y persuasivo'
    ],
    'deepseek/deepseek-chat' => [
        'name' => 'DeepSeek V3',
        'badge' => '⚡ Ultra Económico',
        'desc' => 'Excelente compresión y valor en español'
    ],
    'deepseek/deepseek-r1' => [
        'name' => 'DeepSeek R1',
        'badge' => '🧠 Razonamiento',
        'desc' => 'Capacidad de razonamiento y análisis profundo'
    ],
    'openai/gpt-4o-mini' => [
        'name' => 'OpenAI GPT-4o Mini',
        'badge' => '🚀 Rápido',
        'desc' => 'Respuestas rápidas, eficientes y concisas'
    ],
    'openai/gpt-4o' => [
        'name' => 'OpenAI GPT-4o',
        'badge' => '💎 Potencia Top',
        'desc' => 'Máxima potencia multimodal y conocimiento'
    ],
    'meta-llama/llama-3.3-70b-instruct' => [
        'name' => 'Meta Llama 3.3 70B',
        'badge' => '🏛️ Open-Source',
        'desc' => 'Líder en modelos abiertos con alto rendimiento'
    ],
    'google/gemini-2.0-flash-001' => [
        'name' => 'Google Gemini 2.0 Flash',
        'badge' => '⚡ Ultrarrápido',
        'desc' => 'Latencia mínima de generación en tiempo real'
    ],
    'heuristic' => [
        'name' => 'Motor Heurístico Local',
        'badge' => '⚡ Cero Tokens',
        'desc' => 'Motor nativo en servidor 100% gratuito sin coste de API'
    ]
];

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        try {
            $stmt = $pdo->query("
                SELECT 
                    u.id, 
                    u.tenant_id, 
                    u.name, 
                    u.email, 
                    u.role, 
                    u.avatar_url, 
                    u.created_at, 
                    u.last_login_at, 
                    u.last_activity_at,
                    COALESCE(u.ai_model, 'anthropic/claude-3.5-sonnet') AS ai_model,
                    COALESCE(u.max_tokens, 50000) AS max_tokens,
                    COALESCE(u.used_tokens, 0) AS used_tokens,
                    COALESCE(u.plan, 'starter') AS plan,
                    COALESCE(u.max_accounts, 1) AS max_accounts,
                    (SELECT COUNT(*) FROM accounts a WHERE a.user_id = u.id AND a.is_active = 1) AS connected_accounts_count,
                    (SELECT COUNT(*) FROM brand_voices bv WHERE bv.user_id = u.id) AS brand_voices_count,
                    CASE 
                        WHEN u.last_activity_at IS NOT NULL AND u.last_activity_at >= datetime('now', '-5 minutes') THEN 1 
                        ELSE 0 
                    END AS is_online
                FROM users u
                ORDER BY u.id ASC
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Compute KPIs
            $totalUsers = count($users);
            $onlineCount = 0;
            $totalTokensConsumed = 0;
            $modelCounts = [];

            foreach ($users as &$u) {
                $u['is_online'] = (bool)$u['is_online'];
                if ($u['is_online']) {
                    $onlineCount++;
                }
                $u['used_tokens'] = (int)$u['used_tokens'];
                $u['max_tokens'] = (int)$u['max_tokens'];
                $u['max_accounts'] = (int)$u['max_accounts'];
                $u['connected_accounts_count'] = (int)$u['connected_accounts_count'];
                $u['brand_voices_count'] = (int)$u['brand_voices_count'];
                $totalTokensConsumed += $u['used_tokens'];

                $model = $u['ai_model'];
                $modelCounts[$model] = ($modelCounts[$model] ?? 0) + 1;
            }
            unset($u);

            arsort($modelCounts);
            $topModelKey = !empty($modelCounts) ? array_key_first($modelCounts) : 'anthropic/claude-3.5-sonnet';
            $topModelName = $allowedModels[$topModelKey]['name'] ?? $topModelKey;

            echo json_encode([
                'success' => true,
                'users' => $users,
                'allowed_models' => $allowedModels,
                'models_catalog' => $allowedModels,
                'plans' => [
                    'starter' => Database::getPlanDetails('starter'),
                    'creator' => Database::getPlanDetails('creator'),
                    'pro' => Database::getPlanDetails('pro'),
                    'agency' => Database::getPlanDetails('agency')
                ],
                'kpis' => [
                    'total_users' => $totalUsers,
                    'online_users' => $onlineCount,
                    'total_tokens_consumed' => $totalTokensConsumed,
                    'top_model' => $topModelName
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error al obtener lista de usuarios: ' . $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

if ($method === 'POST') {
    Security::requireCsrf();
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $input['action'] ?? '';

    if ($action === 'update_user_ai') {
        $targetUserId = (int)($input['user_id'] ?? 0);
        $aiModel = trim((string)($input['ai_model'] ?? 'anthropic/claude-3.5-sonnet'));
        $maxTokens = (int)($input['max_tokens'] ?? 50000);

        if ($targetUserId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de usuario inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($maxTokens < 0) {
            $maxTokens = 0; // 0 represents unlimited tokens
        }

        $plan = strtolower(trim((string)($input['plan'] ?? '')));
        $maxAccounts = isset($input['max_accounts']) ? (int)$input['max_accounts'] : null;

        try {
            if (!empty($plan) && in_array($plan, ['starter', 'creator', 'pro', 'agency'], true)) {
                $pInfo = Database::getPlanDetails($plan);
                if ($maxAccounts === null || $maxAccounts <= 0) {
                    $maxAccounts = (int)$pInfo['accounts'];
                }
                $stmt = $pdo->prepare("UPDATE users SET ai_model = :model, max_tokens = :max, plan = :plan, max_accounts = :max_acc WHERE id = :id");
                $stmt->execute([
                    ':model' => $aiModel,
                    ':max' => $maxTokens,
                    ':plan' => $plan,
                    ':max_acc' => $maxAccounts,
                    ':id' => $targetUserId
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET ai_model = :model, max_tokens = :max WHERE id = :id");
                $stmt->execute([
                    ':model' => $aiModel,
                    ':max' => $maxTokens,
                    ':id' => $targetUserId
                ]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Configuración de usuario, modelo IA, tokens y plan actualizada con éxito.',
                'user_id' => $targetUserId,
                'ai_model' => $aiModel,
                'max_tokens' => $maxTokens,
                'plan' => $plan,
                'max_accounts' => $maxAccounts
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar usuario: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($action === 'reset_tokens') {
        $targetUserId = (int)($input['user_id'] ?? 0);
        if ($targetUserId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID de usuario inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET used_tokens = 0 WHERE id = :id");
            $stmt->execute([':id' => $targetUserId]);

            echo json_encode([
                'success' => true,
                'message' => 'Contador de tokens consumidos reiniciado a 0 para el usuario.',
                'user_id' => $targetUserId,
                'used_tokens' => 0
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al reiniciar tokens: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($action === 'update_role') {
        $targetUserId = (int)($input['user_id'] ?? 0);
        $newRole = trim((string)($input['role'] ?? 'user'));

        if ($targetUserId <= 0 || !in_array($newRole, ['admin', 'user', 'tester'], true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Rol o usuario inválido.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($targetUserId === 1 && $newRole !== 'admin') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No es posible revocar los permisos de administrador al usuario maestro #1.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute([':role' => $newRole, ':id' => $targetUserId]);

            echo json_encode([
                'success' => true,
                'message' => "Rol actualizado exitosamente a '{$newRole}'.",
                'user_id' => $targetUserId,
                'role' => $newRole
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al actualizar rol: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Acción no reconocida.'], JSON_UNESCAPED_UNICODE);
