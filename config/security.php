<?php
/**
 * Security & Defense Core Module
 * Centralized Cyber Security, Access Control, CSRF, Rate Limiting & Input Sanitization
 */

if (session_status() === PHP_SESSION_NONE) {
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
        'samesite' => 'Strict'
    ]);
    session_start();
}

class Security {

    /**
     * Apply strict HTTP security headers
     */
    public static function applySecurityHeaders(bool $isApi = false): void {
        if (headers_sent()) {
            return;
        }

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevent Clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Cross-Site Scripting filter for legacy browsers
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions policy (camera, mic, geolocation disabled unless explicitly needed)
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            // Restrict CORS to same-origin by default
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            if (!empty($origin) && str_contains($origin, $host)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
                header('Access-Control-Allow-Credentials: true');
            }
        } else {
            // Content Security Policy for HTML pages
            $csp = [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "font-src 'self' https://fonts.gstatic.com data:",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' https://generativelanguage.googleapis.com https://api.openai.com https://graph.facebook.com",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'"
            ];
            header('Content-Security-Policy: ' . implode('; ', $csp));
        }

        // HSTS if HTTPS
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
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
}
