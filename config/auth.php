<?php
/**
 * Authentication & Multi-Tenant Session Security Manager
 * Features:
 * - Bcrypt Password Hashing
 * - Session Fixation Defenses & Secure Cookie Params
 * - Brute-Force Rate Limiting
 * - Per-User Tenant Data Isolation
 */
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';

class Auth {
    private static ?array $cachedUser = null;

    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $cookieParams = [
                'lifetime' => 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            session_set_cookie_params($cookieParams);
            session_start();
        }
    }

    public static function check(): bool {
        self::initSession();
        return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
    }

    public static function id(): int {
        self::initSession();
        return (int)($_SESSION['user_id'] ?? 1);
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email, role, avatar_url, created_at, last_login_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => self::id()]);
        $user = $stmt->fetch();

        if ($user) {
            self::$cachedUser = $user;
            return $user;
        }
        return null;
    }

    public static function requireAuth(bool $isApi = false): void {
        if (!self::check()) {
            if ($isApi) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Sesión expirada o no autenticada. Inicia sesión para continuar.'], JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                header('Location: login.php');
                exit;
            }
        }
    }

    public static function login(string $email, string $password): array {
        self::initSession();
        $email = trim(mb_strtolower($email, 'UTF-8'));

        // Rate limit by email to prevent brute-force attacks (max 8 attempts per minute)
        Security::requireRateLimit('auth_login_' . md5($email), 8, 60);

        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Por favor ingresa tu correo y contraseña.'];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Correo electrónico o contraseña incorrectos.'];
        }

        // Regenerate session ID to prevent session fixation attacks
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        self::$cachedUser = $user;

        // Update last login timestamp
        $up = $pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
        $up->execute([':id' => $user['id']]);

        return [
            'success' => true,
            'message' => 'Sesión iniciada con éxito.',
            'user' => [
                'id' => (int)$user['id'],
                'name' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                'role' => $user['role'],
                'avatar_url' => $user['avatar_url']
            ]
        ];
    }

    public static function register(string $name, string $email, string $password): array {
        self::initSession();
        $name = Security::sanitizeString($name, 80);
        $email = trim(mb_strtolower($email, 'UTF-8'));

        // Rate limit registration (max 5 new accounts per hour per IP)
        Security::requireRateLimit('auth_register_ip', 5, 3600);

        if (empty($name) || mb_strlen($name) < 2) {
            return ['success' => false, 'error' => 'El nombre debe tener al menos 2 caracteres.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'El formato del correo electrónico no es válido.'];
        }

        if (mb_strlen($password) < 6) {
            return ['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'];
        }

        $pdo = Database::getConnection();

        // Check if email already registered
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'error' => 'Este correo electrónico ya está registrado. Inicia sesión.'];
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=6366f1&color=fff';

        $stmtIns = $pdo->prepare("
            INSERT INTO users (name, email, password_hash, role, avatar_url, last_login_at)
            VALUES (:name, :email, :hash, 'user', :avatar, CURRENT_TIMESTAMP)
        ");
        $stmtIns->execute([
            ':name' => $name,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':avatar' => $avatarUrl
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        // Seed default brand voice settings for this new user
        Database::seedInitialData($pdo, $newUserId);

        // Auto login
        session_regenerate_id(true);
        $_SESSION['user_id'] = $newUserId;

        return [
            'success' => true,
            'message' => '¡Cuenta creada con éxito! Bienvenido a tu gestor de redes.',
            'user' => [
                'id' => $newUserId,
                'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                'role' => 'user',
                'avatar_url' => $avatarUrl
            ]
        ];
    }

    public static function logout(): void {
        self::initSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$cachedUser = null;
    }
}
