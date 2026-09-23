<?php
/**
 * Security & Defense Core Module
 * Centralized Cyber Security, Access Control, CSRF, Rate Limiting & Input Sanitization
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent() && php_sapi_name() !== 'cli') {
    // Determine HTTPS status including proxy headers
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    
    // Strict session security configurations
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
        'samesite' => 'Lax'
    ]);
    session_start();
}

class Security {

    /**
     * Apply strict HTTP security headers
     * Returns the array of applied security headers for verification and testing
     */
    public static function applySecurityHeaders(bool $isApi = false): array {
        $applied = [];

        // 1. Remove Information Disclosure Headers (Hide PHP version)
        if (!headers_sent()) {
            header_remove('X-Powered-By');
        }
        @ini_set('expose_php', '0');
        $applied['X-Powered-By'] = null; // Specifically unset

        // 2. Core Defense Headers
        $applied['X-Content-Type-Options'] = 'nosniff';
        $applied['X-Frame-Options'] = 'SAMEORIGIN';
        $applied['X-XSS-Protection'] = '1; mode=block';
        $applied['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        $applied['Permissions-Policy'] = 'camera=(), microphone=(), geolocation=()';

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        }

        if ($isApi) {
            $applied['Content-Type'] = 'application/json; charset=utf-8';
            $applied['Content-Security-Policy'] = "default-src 'none'; frame-ancestors 'none'; base-uri 'none'";
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'");
            }
            self::applyStrictCors();
        } else {
            // Hardened Content Security Policy for HTML pages
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "font-src 'self' https://fonts.gstatic.com data:",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' https://openrouter.ai https://graph.facebook.com https://graph.instagram.com",
                "frame-ancestors 'self'",
                "frame-src 'self' https://www.facebook.com https://www.instagram.com",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'"
            ];
            $cspString = implode('; ', $csp);
            $applied['Content-Security-Policy'] = $cspString;
            if (!headers_sent()) {
                header('Content-Security-Policy: ' . $cspString);
            }
        }

        // HSTS if HTTPS
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)) {
            $applied['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
            if (!headers_sent()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }

        return $applied;
    }

    /**
     * Get trusted canonical application base URL
     * Prioritizes APP_URL from .env to prevent HTTP Host Header Poisoning
     */
    public static function getAppUrl(): string {
        self::loadEnv();
        $configuredAppUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
        if (!empty($configuredAppUrl)) {
            return rtrim($configuredAppUrl, '/');
        }

        // Safe fallback for local development when APP_URL is not configured
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
        
        $protocol = $isHttps ? 'https' : 'http';
        $serverHost = $_SERVER['SERVER_NAME'] ?? $_SERVER['SERVER_ADDR'] ?? 'localhost';
        
        // Strict hostname validation to reject host poisoning
        if (!filter_var($serverHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) && $serverHost !== 'localhost') {
            $serverHost = 'localhost';
        }

        $port = (int)($_SERVER['SERVER_PORT'] ?? ($isHttps ? 443 : 80));
        $portSuffix = (!in_array($port, [80, 443], true)) ? ":{$port}" : '';

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseDir = rtrim(dirname($scriptName), '/\\');
        if (str_ends_with($baseDir, '/api') || str_ends_with($baseDir, '\\api') || str_ends_with($baseDir, '/services') || str_ends_with($baseDir, '\\services')) {
            $baseDir = dirname($baseDir);
        }
        $baseDir = ($baseDir === '/' || $baseDir === '\\') ? '' : rtrim($baseDir, '/\\');

        return "{$protocol}://{$serverHost}{$portSuffix}{$baseDir}";
    }

    /**
     * Get trusted canonical OAuth Redirect URI
     */
    public static function getOAuthRedirectUri(): string {
        return self::getAppUrl() . '/callback-meta.php';
    }

    /**
     * Get or generate a cryptographically secure CSRF Token
     */
    public static function getCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate incoming CSRF token from header or input
     */
    public static function validateCsrfToken(?string $token = null): bool {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        if ($token === null) {
            // Check request headers first (case-insensitive)
            $headers = function_exists('getallheaders') ? array_change_key_case(getallheaders(), CASE_LOWER) : [];
            $token = $headers['x-csrf-token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

            if (!$token) {
                // Check body or query params
                $rawInput = file_get_contents('php://input');
                $input = json_decode($rawInput, true);
                $token = $input['csrf_token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
            }
        }

        if (empty($token) || !is_string($token)) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Enforce CSRF protection on state-changing methods (POST, PUT, DELETE, PATCH)
     */
    public static function requireCsrf(): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            if (!self::validateCsrfToken()) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'Token de seguridad CSRF inválido o expirado. Por favor, recarga la página.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
    }

    /**
     * IP-based sliding window Rate Limiter
     * @param string $action Unique action identifier (e.g. 'ai_generate', 'comments_api')
     * @param int $maxRequests Max requests allowed within the window
     * @param int $windowSeconds Window duration in seconds
     */
    public static function checkRateLimit(string $action = 'default', int $maxRequests = 60, int $windowSeconds = 60): bool {
        $clientIp = self::getClientIp();
        $key = 'rate_' . md5($action . '_' . $clientIp);
        $now = time();

        try {
            require_once __DIR__ . '/database.php';
            $pdo = Database::getConnection();

            // Clean expired limits older than 1 hour occasionally
            if (mt_rand(1, 50) === 1) {
                $cleanupStmt = $pdo->prepare("DELETE FROM rate_limits WHERE expires_at < :now");
                $cleanupStmt->execute([':now' => $now]);
            }

            $stmt = $pdo->prepare("SELECT count, reset_at FROM rate_limits WHERE rate_key = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $record = $stmt->fetch();

            if ($record) {
                if ($now > $record['reset_at']) {
                    // Window expired, reset counter
                    $upStmt = $pdo->prepare("UPDATE rate_limits SET count = 1, reset_at = :reset_at, expires_at = :expires_at WHERE rate_key = :key");
                    $upStmt->execute([
                        ':reset_at' => $now + $windowSeconds,
                        ':expires_at' => $now + $windowSeconds + 3600,
                        ':key' => $key
                    ]);
                    return true;
                } else {
                    if ($record['count'] >= $maxRequests) {
                        return false; // Rate limit exceeded
                    }
                    $incStmt = $pdo->prepare("UPDATE rate_limits SET count = count + 1 WHERE rate_key = :key");
                    $incStmt->execute([':key' => $key]);
                    return true;
                }
            } else {
                $insStmt = $pdo->prepare("
                    INSERT INTO rate_limits (rate_key, count, reset_at, expires_at)
                    VALUES (:key, 1, :reset_at, :expires_at)
                ");
                $insStmt->execute([
                    ':key' => $key,
                    ':reset_at' => $now + $windowSeconds,
                    ':expires_at' => $now + $windowSeconds + 3600
                ]);
                return true;
            }
        } catch (Throwable $e) {
            // Fallback: If DB table not ready, allow request but log error
            error_log("RateLimiter DB Error: " . $e->getMessage());
            return true;
        }
    }

    /**
     * Enforce rate limit or terminate with 429 Too Many Requests
     */
    public static function requireRateLimit(string $action = 'default', int $maxRequests = 60, int $windowSeconds = 60): void {
        if (!self::checkRateLimit($action, $maxRequests, $windowSeconds)) {
            http_response_code(429);
            header('Retry-After: ' . $windowSeconds);
            echo json_encode([
                'success' => false,
                'error' => 'Demasiadas solicitudes en poco tiempo. Por favor, espera unos segundos.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Validate Meta Webhook HMAC-SHA256 signature
     */
    public static function validateMetaWebhookSignature(string $rawPayload, string $signatureHeader, string $appSecret): bool {
        if (empty($signatureHeader) || empty($appSecret)) {
            return false;
        }

        if (!str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signatureHeader, 7);
        $calculatedHash = hash_hmac('sha256', $rawPayload, $appSecret);

        return hash_equals($expectedHash, $calculatedHash);
    }

    /**
     * Get safe client IP address
     */
    public static function getClientIp(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '127.0.0.1';
    }

    /**
     * Sanitize string and restrict max length
     */
    public static function sanitizeString(?string $input, int $maxLength = 2000): string {
        if ($input === null) return '';
        $clean = trim(strip_tags($input));
        return mb_substr($clean, 0, $maxLength, 'UTF-8');
    }

    /**
     * Alias for sanitizeString for unified API input sanitization
     */
    public static function sanitizeInput(?string $input, int $maxLength = 2000): string {
        return self::sanitizeString($input, $maxLength);
    }

    /**
     * Validate an input value against an allowed whitelist enum
     */
    public static function validateEnum(string $value, array $allowed, string $default): string {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Sanitize Integer within a valid range
     */
    public static function sanitizeInt($value, int $min = 0, int $max = 1000000, int $default = 0): int {
        if (!is_numeric($value)) return $default;
        $val = (int)$value;
        return max($min, min($max, $val));
    }

    /**
     * Safe JSON error responder without leaking internal server stack traces
     */
    public static function sendJsonError(string $publicMessage = 'Error interno del servidor', ?Throwable $e = null, int $statusCode = 500): void {
        if ($e !== null) {
            error_log("[SECURITY/API ERROR] " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
        }
        http_response_code($statusCode);
        echo json_encode([
            'success' => false,
            'error' => $publicMessage
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Load environment variables from .env if present
     */
    public static function loadEnv(): void {
        static $loaded = false;
        if ($loaded) return;
        $loaded = true;

        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) continue;
                if (str_contains($line, '=')) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value, " \t\n\r\0\x0B\"'");
                    if (getenv($name) === false && !isset($_ENV[$name])) {
                        putenv("{$name}={$value}");
                        $_ENV[$name] = $value;
                        $_SERVER[$name] = $value;
                    }
                }
            }
        }
    }

    /**
     * Check if an Origin is explicitly whitelisted (Zero-Trust)
     */
    public static function isAllowedOrigin(string $origin): bool {
        if (empty($origin)) {
            return false;
        }

        self::loadEnv();

        $parsedOrigin = parse_url($origin);
        if (!$parsedOrigin || empty($parsedOrigin['scheme']) || empty($parsedOrigin['host'])) {
            return false;
        }

        $originScheme = strtolower($parsedOrigin['scheme']);
        $originHost = strtolower($parsedOrigin['host']);
        $originPort = $parsedOrigin['port'] ?? ($originScheme === 'https' ? 443 : 80);

        // Standardize origin representation (scheme://host[:port])
        $normalizedOrigin = $originScheme . '://' . $originHost;
        if (!($originScheme === 'https' && $originPort === 443) && !($originScheme === 'http' && $originPort === 80)) {
            $normalizedOrigin .= ':' . $originPort;
        }

        $allowedOrigins = [];

        // 1. Explicitly configured origins from .env (CORS_ALLOWED_ORIGINS)
        $configuredOrigins = getenv('CORS_ALLOWED_ORIGINS') ?: ($_ENV['CORS_ALLOWED_ORIGINS'] ?? '');
        if (!empty($configuredOrigins)) {
            foreach (explode(',', $configuredOrigins) as $o) {
                $trimmed = trim($o);
                if (!empty($trimmed)) {
                    $allowedOrigins[] = rtrim(strtolower($trimmed), '/');
                }
            }
        }

        // 2. Application canonical URL if configured in .env (APP_URL)
        $appUrl = getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? '');
        if (!empty($appUrl)) {
            $parsedApp = parse_url($appUrl);
            if ($parsedApp && !empty($parsedApp['host'])) {
                $appScheme = strtolower($parsedApp['scheme'] ?? 'http');
                $appHost = strtolower($parsedApp['host']);
                $appPort = $parsedApp['port'] ?? ($appScheme === 'https' ? 443 : 80);
                $appNorm = $appScheme . '://' . $appHost;
                if (!($appScheme === 'https' && $appPort === 443) && !($appScheme === 'http' && $appPort === 80)) {
                    $appNorm .= ':' . $appPort;
                }
                $allowedOrigins[] = $appNorm;
            }
        }

        // 3. Local trusted development addresses (exact matches only)
        $serverPort = (int)($_SERVER['SERVER_PORT'] ?? 80);
        $allowedOrigins[] = 'http://localhost';
        $allowedOrigins[] = 'https://localhost';
        $allowedOrigins[] = 'http://127.0.0.1';
        $allowedOrigins[] = 'https://127.0.0.1';
        if (!in_array($serverPort, [80, 443], true)) {
            $allowedOrigins[] = "http://localhost:{$serverPort}";
            $allowedOrigins[] = "https://localhost:{$serverPort}";
            $allowedOrigins[] = "http://127.0.0.1:{$serverPort}";
            $allowedOrigins[] = "https://127.0.0.1:{$serverPort}";
        }
        if (!in_array($originPort, [80, 443], true)) {
            if ($originHost === 'localhost' || $originHost === '127.0.0.1') {
                $allowedOrigins[] = "{$originScheme}://{$originHost}:{$originPort}";
            }
        }

        $allowedOrigins = array_unique($allowedOrigins);

        // Strict exact equality comparison (Zero-Trust)
        return in_array($normalizedOrigin, $allowedOrigins, true);
    }

    /**
     * Apply strict zero-trust CORS validation against an explicit whitelist
     * Eliminates Host-trust and substring-based CORS injection
     */
    public static function applyStrictCors(): void {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (empty($origin)) {
            return;
        }

        if (self::isAllowedOrigin($origin)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
            header('Vary: Origin');

            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
                http_response_code(204);
                exit;
            }
        }
    }

    /**
     * Get or initialize the 256-bit symmetric encryption key from .env or secure persistent keyfile
     */
    public static function getEncryptionKey(): string {
        self::loadEnv();
        $keyHex = getenv('APP_ENCRYPTION_KEY') ?: ($_ENV['APP_ENCRYPTION_KEY'] ?? '');

        if (!empty($keyHex)) {
            $bin = @hex2bin($keyHex);
            if ($bin !== false && strlen($bin) === 32) {
                return $bin;
            }
            if (strlen($keyHex) === 32) {
                return $keyHex;
            }
            return hash('sha256', $keyHex, true);
        }

        // Persistent fallback keyfile in data/ (denied by .htaccess)
        $dataDir = __DIR__ . '/../data';
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0750, true);
        }
        $keyFile = $dataDir . '/.app_encryption_key';
        if (file_exists($keyFile)) {
            $raw = @file_get_contents($keyFile);
            if ($raw !== false && strlen($raw) === 32) {
                return $raw;
            }
        }

        // Generate and persist 32-byte CSPRNG key
        $newKey = random_bytes(32);
        @file_put_contents($keyFile, $newKey, LOCK_EX);
        @chmod($keyFile, 0600);
        return $newKey;
    }

    /**
     * Authenticated Symmetric Encryption (AES-256-GCM)
     * Format: enc:v1:<base64(iv(12) . tag(16) . ciphertext)>
     */
    public static function encrypt(?string $plaintext): string {
        if ($plaintext === null || $plaintext === '') {
            return '';
        }

        // Already encrypted?
        if (str_starts_with($plaintext, 'enc:v1:')) {
            return $plaintext;
        }

        $key = self::getEncryptionKey();
        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException("Fallo crítico en el cifrado AES-256-GCM");
        }

        return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Authenticated Symmetric Decryption (AES-256-GCM)
     */
    public static function decrypt(?string $payload): string {
        if ($payload === null || $payload === '') {
            return '';
        }

        // Transparent backward compatibility: if not encrypted with enc:v1:, return as-is
        if (!str_starts_with($payload, 'enc:v1:')) {
            return $payload;
        }

        $encoded = substr($payload, 7);
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 28) {
            error_log("[SECURITY] Intento de descifrado con payload corrupto o inválido.");
            return '';
        }

        $iv = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $key = self::getEncryptionKey();

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            error_log("[SECURITY] Fallo de integridad o clave incorrecta al descifrar AES-256-GCM.");
            return '';
        }

        return $decrypted;
    }
}
