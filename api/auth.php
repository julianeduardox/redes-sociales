<?php
/**
 * REST API: User Authentication Controller (Login, Register, Logout, Me)
 * Hardened with Anti-CSRF, Rate Limiting & Input Sanitization
 */
require_once __DIR__ . '/../config/security.php';
require_once __DIR__ . '/../config/auth.php';

Security::applySecurityHeaders(true);

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $allowedGetActions = ['me', 'validate_token'];
        $action = Security::validateEnum($_GET['action'] ?? 'me', $allowedGetActions, 'me');

        if ($action === 'validate_token') {
            $token = $_GET['token'] ?? '';
            $validation = Auth::validateResetToken($token);
            if (!$validation['valid']) {
                http_response_code(400);
            }
            echo json_encode($validation, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'me') {
            if (Auth::check()) {
                $user = Auth::user();
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => (int)$user['id'],
                        'tenant_id' => $user['tenant_id'] ?? ('tnt_' . $user['id']),
                        'name' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                        'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                        'role' => $user['role'],
                        'avatar_url' => $user['avatar_url']
                    ],
                    'csrf_token' => Security::getCsrfToken()
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode([
                    'success' => true,
                    'authenticated' => false,
                    'csrf_token' => Security::getCsrfToken()
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
    }

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?? $_POST;

        $allowedActions = ['login', 'register', 'logout', 'forgot_password', 'reset_password'];
        $action = Security::validateEnum($input['action'] ?? 'login', $allowedActions, 'login');

        // Enforce anti-CSRF check
        Security::requireCsrf();

        if ($action === 'login') {
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $result = Auth::login($email, $password);

            if (!$result['success']) {
                http_response_code(401);
            }
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'register') {
            $name = $input['name'] ?? '';
            $email = $input['email'] ?? '';
            $password = $input['password'] ?? '';
            $result = Auth::register($name, $email, $password);

            if (!$result['success']) {
                http_response_code(400);
            }
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'forgot_password') {
            $email = $input['email'] ?? '';
            $result = Auth::requestPasswordReset($email);

            if (!$result['success']) {
                http_response_code(400);
            }
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'reset_password') {
            $token = $input['token'] ?? '';
            $password = $input['password'] ?? '';
            $result = Auth::resetPassword($token, $password);

            if (!$result['success']) {
                http_response_code(400);
            }
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'logout') {
            Auth::logout();
            echo json_encode([
                'success' => true,
                'message' => 'Sesión cerrada correctamente.'
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
} catch (Throwable $e) {
    Security::sendJsonError('Error al procesar la autenticación.', $e);
}
