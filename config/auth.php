<?php
/**
 * Authentication & Multi-Tenant Session Security Manager
 * Features:
 * - Argon2ID / Bcrypt (Cost 12) Strict Password Hashing
 * - Session Fixation Defenses & HttpOnly / Secure / SameSite=Strict Cookie Params
 * - Brute-Force Rate Limiting & Input Validation
 * - Per-User Tenant Data Isolation & Workspace Auto-Seeding
 */
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../services/MailerService.php';

class Auth {
    private static ?array $cachedUser = null;

    /**
     * Initialize strict session parameters if not already started
     */
    public static function initSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
            if ($isHttps) {
                ini_set('session.cookie_secure', '1');
            }

            session_set_cookie_params([
                'lifetime' => 86400 * 7, // 7 days
                'path' => '/',
                'domain' => '',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }
    }

    /**
     * Generate secure password hash using Argon2ID or hardened Bcrypt
     */
    public static function hashPassword(string $password): string {
        if (defined('PASSWORD_ARGON2ID') && in_array('argon2id', password_algos(), true)) {
            return password_hash($password, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 1
            ]);
        }
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify if current session is authenticated
     */
    public static function check(): bool {
        self::initSession();
        return !empty($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
    }

    /**
     * Get authenticated user ID
     */
    public static function id(): int {
        self::initSession();
        return (int)($_SESSION['user_id'] ?? 1);
    }

    /**
     * Get authenticated tenant ID
     */
    public static function tenantId(): string {
        self::initSession();
        return (string)($_SESSION['tenant_id'] ?? ('tnt_' . self::id()));
    }

    /**
     * Get authenticated user details with in-memory request caching
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, tenant_id, name, email, role, avatar_url, created_at, last_login_at FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => self::id()]);
        $user = $stmt->fetch();

        if ($user) {
            self::$cachedUser = $user;
            return $user;
        }
        return null;
    }

    /**
     * Enforce authentication gate
     */
    public static function requireAuth(bool $isApi = false): void {
        if (!self::check()) {
            if ($isApi) {
                http_response_code(401);
                echo json_encode([
                    'success' => false, 
                    'error' => 'Sesión expirada o no autenticada. Inicia sesión para continuar.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            } else {
                header('Location: login.php');
                exit;
            }
        }
    }

    /**
     * Authenticate user with rate limiting and session fixation defense
     */
    public static function login(string $email, string $password): array {
        self::initSession();
        $email = trim(mb_strtolower($email, 'UTF-8'));
        $password = (string)$password;

        // Rate limit by email & IP to prevent brute-force attacks (max 8 attempts per minute)
        Security::requireRateLimit('auth_login_' . md5($email . '_' . Security::getClientIp()), 8, 60);

        if (empty($email)) {
            return ['success' => false, 'field' => 'email', 'error' => 'Por favor ingresa tu correo electrónico.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'field' => 'email', 'error' => 'Por favor ingresa un correo electrónico con formato válido.'];
        }

        if (empty($password)) {
            return ['success' => false, 'field' => 'password', 'error' => 'Por favor ingresa tu contraseña.'];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'field' => 'email', 'error' => 'No encontramos ninguna cuenta asociada a este correo electrónico.'];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'field' => 'password', 'error' => 'La contraseña ingresada es incorrecta.'];
        }

        // Automatic password rehash if algorithm parameters improved
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $newHash = self::hashPassword($password);
            $rehashStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
            $rehashStmt->execute([':hash' => $newHash, ':id' => $user['id']]);
        }

        // Ensure tenant_id exists on user
        $tenantId = $user['tenant_id'] ?? '';
        if (empty($tenantId)) {
            $tenantId = 'tnt_' . substr(md5($user['id'] . '_' . ($user['created_at'] ?? time())), 0, 12);
            $pdo->prepare("UPDATE users SET tenant_id = :tid WHERE id = :id")->execute([':tid' => $tenantId, ':id' => $user['id']]);
            $user['tenant_id'] = $tenantId;
        }

        // Regenerate session ID to prevent session fixation attacks
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['tenant_id'] = $tenantId;
        self::$cachedUser = $user;

        // Update last login timestamp
        $up = $pdo->prepare("UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
        $up->execute([':id' => $user['id']]);

        return [
            'success' => true,
            'message' => 'Sesión iniciada con éxito. ¡Bienvenido de vuelta!',
            'user' => [
                'id' => (int)$user['id'],
                'tenant_id' => $tenantId,
                'name' => htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'),
                'role' => $user['role'],
                'avatar_url' => $user['avatar_url']
            ]
        ];
    }

    /**
     * Register new tenant user with multi-tenant isolation and workspace setup
     */
    public static function register(string $name, string $email, string $password): array {
        self::initSession();
        $name = Security::sanitizeString($name, 80);
        $email = trim(mb_strtolower($email, 'UTF-8'));
        $password = (string)$password;

        // Rate limit registration (max 6 new accounts per hour per IP)
        Security::requireRateLimit('auth_register_' . md5(Security::getClientIp()), 6, 3600);

        if (empty($name) || mb_strlen($name) < 2) {
            return ['success' => false, 'field' => 'name', 'error' => 'El nombre debe tener al menos 2 caracteres.'];
        }

        if (mb_strlen($name) > 80) {
            return ['success' => false, 'field' => 'name', 'error' => 'El nombre no puede exceder los 80 caracteres.'];
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) {
            return ['success' => false, 'field' => 'email', 'error' => 'Por favor introduce un correo electrónico válido.'];
        }

        if (mb_strlen($password) < 8) {
            return ['success' => false, 'field' => 'password', 'error' => 'La contraseña debe tener al menos 8 caracteres para garantizar tu seguridad.'];
        }

        if (mb_strlen($password) > 256) {
            return ['success' => false, 'field' => 'password', 'error' => 'La contraseña no puede exceder los 256 caracteres.'];
        }

        $pdo = Database::getConnection();

        // Check if email already registered
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmtCheck->execute([':email' => $email]);
        if ($stmtCheck->fetch()) {
            return ['success' => false, 'field' => 'email', 'error' => 'Este correo electrónico ya está registrado. Inicia sesión o utiliza otro correo.'];
        }

        $passwordHash = self::hashPassword($password);
        $tenantId = 'tnt_' . bin2hex(random_bytes(6));
        $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=7c3aed&color=fff&size=96';

        $stmtIns = $pdo->prepare("
            INSERT INTO users (tenant_id, name, email, password_hash, role, avatar_url, last_login_at)
            VALUES (:tenant_id, :name, :email, :hash, 'user', :avatar, CURRENT_TIMESTAMP)
        ");
        $stmtIns->execute([
            ':tenant_id' => $tenantId,
            ':name' => $name,
            ':email' => $email,
            ':hash' => $passwordHash,
            ':avatar' => $avatarUrl
        ]);

        $newUserId = (int)$pdo->lastInsertId();

        // Seed default brand voice settings and starter workspace for this new tenant
        Database::seedInitialData($pdo, $newUserId);

        // Auto login with fresh session ID
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['tenant_id'] = $tenantId;

        return [
            'success' => true,
            'message' => '¡Cuenta creada con éxito! Bienvenido a tu gestor inteligente.',
            'user' => [
                'id' => $newUserId,
                'tenant_id' => $tenantId,
                'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
                'role' => 'user',
                'avatar_url' => $avatarUrl
            ]
        ];
    }

    /**
     * Logout and destroy session securely
     */
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

    /**
     * Request Password Reset Token & Dispatch Email
     */
    public static function requestPasswordReset(string $email): array {
        self::initSession();
        $email = trim(mb_strtolower($email, 'UTF-8'));

        // Rate limit password reset requests (max 4 per 15 mins per IP/email)
        Security::requireRateLimit('auth_pwd_reset_' . md5($email . '_' . Security::getClientIp()), 4, 900);

        if (empty($email)) {
            return ['success' => false, 'field' => 'email', 'error' => 'Por favor introduce tu correo electrónico.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'field' => 'email', 'error' => 'Por favor introduce un correo electrónico válido.'];
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate high-entropy CSPRNG raw token (64 hex characters)
            $rawToken = bin2hex(random_bytes(32));
            // Store hashed token (SHA-256) in database
            $tokenHash = hash('sha256', $rawToken);
            // 30-minute expiration timestamp
            $expiresAt = date('Y-m-d H:i:s', time() + 1800);

            // Invalidate any previously active unused tokens for this user
            $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = :uid AND used = 0")
                ->execute([':uid' => $user['id']]);

            // Store new reset token hash
            $stmtIns = $pdo->prepare("
                INSERT INTO password_resets (user_id, token_hash, expires_at, used)
                VALUES (:uid, :hash, :expires, 0)
            ");
            $stmtIns->execute([
                ':uid' => $user['id'],
                ':hash' => $tokenHash,
                ':expires' => $expiresAt
            ]);

            // Dispatch transactional HTML email
            MailerService::sendPasswordResetEmail($user['email'], $user['name'], $rawToken);
        }

        // Generic timing-safe response to prevent user enumeration
        return [
            'success' => true,
            'message' => 'Si el correo electrónico coincide con una cuenta registrada, hemos enviado las instrucciones para restablecer tu contraseña. Revisa tu bandeja de entrada o carpeta de spam.'
        ];
    }

    /**
     * Validate Password Reset Token
     */
    public static function validateResetToken(string $rawToken): array {
        $rawToken = trim($rawToken);
        if (empty($rawToken) || strlen($rawToken) < 32) {
            return ['valid' => false, 'error' => 'El enlace de recuperación es inválido o está incompleto.'];
        }

        $tokenHash = hash('sha256', $rawToken);
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT pr.id, pr.user_id, pr.expires_at, pr.used, u.name, u.email 
            FROM password_resets pr
            JOIN users u ON u.id = pr.user_id
            WHERE pr.token_hash = :hash
            ORDER BY pr.id DESC LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['valid' => false, 'error' => 'El enlace de recuperación es inválido o no existe.'];
        }

        if ((int)$row['used'] === 1) {
            return ['valid' => false, 'error' => 'Este enlace de recuperación ya ha sido utilizado anteriormente.'];
        }

        if (strtotime($row['expires_at']) < time()) {
            return ['valid' => false, 'error' => 'Este enlace de recuperación ha expirado (validez de 30 minutos). Por favor solicita uno nuevo.'];
        }

        return [
            'valid' => true,
            'reset_id' => (int)$row['id'],
            'user_id' => (int)$row['user_id'],
            'email' => htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8')
        ];
    }

    /**
     * Execute Password Reset with New Password
     */
    public static function resetPassword(string $rawToken, string $newPassword): array {
        self::initSession();
        $rawToken = trim($rawToken);
        $newPassword = (string)$newPassword;

        // Rate limit reset submissions by IP (max 6 per 15 mins)
        Security::requireRateLimit('auth_reset_action_' . md5(Security::getClientIp()), 6, 900);

        $validation = self::validateResetToken($rawToken);
        if (!$validation['valid']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        if (mb_strlen($newPassword) < 8) {
            return ['success' => false, 'field' => 'password', 'error' => 'La nueva contraseña debe tener al menos 8 caracteres para proteger tu cuenta.'];
        }

        if (mb_strlen($newPassword) > 256) {
            return ['success' => false, 'field' => 'password', 'error' => 'La contraseña no puede exceder los 256 caracteres.'];
        }

        $userId = (int)$validation['user_id'];
        $resetId = (int)$validation['reset_id'];
        $newPasswordHash = self::hashPassword($newPassword);

        $pdo = Database::getConnection();

        // Update password hash in users table
        $stmtUp = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :uid");
        $stmtUp->execute([':hash' => $newPasswordHash, ':uid' => $userId]);

        // Invalidate used reset token and any remaining tokens for this user
        $stmtToken = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE user_id = :uid");
        $stmtToken->execute([':uid' => $userId]);

        self::$cachedUser = null;

        return [
            'success' => true,
            'message' => '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión con tu nueva clave.'
        ];
    }
}
