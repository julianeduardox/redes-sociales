<?php
declare(strict_types=1);

namespace Harness\Http;

/**
 * Guardián de CORS Zero-Trust (RFC 6454).
 * Valida de forma estricta los orígenes entrantes contra una lista blanca normalizada,
 * previniendo evasiones basadas en subcadenas, inyecciones de host y orígenes maliciosos.
 */
class CorsGuard
{
    /** @var array<string, bool> */
    private array $allowedOrigins = [];

    /** @var array<string> */
    private array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'PATCH'];

    /** @var array<string> */
    private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'];

    /** @var array<string> */
    private array $exposedHeaders = [];

    private bool $allowCredentials = true;
    private int $maxAge = 86400;

    /**
     * @param array<string> $allowedOrigins Lista de orígenes autorizados (e.g. ['https://app.example.com'])
     * @param array<string>|null $allowedMethods Métodos HTTP permitidos en preflight
     * @param array<string>|null $allowedHeaders Cabeceras HTTP permitidas en preflight
     * @param bool $allowCredentials Si se admite envío de cookies/credenciales (por defecto true)
     * @param int $maxAge Tiempo de caché de preflight en segundos (por defecto 86400)
     * @param array<string> $exposedHeaders Cabeceras expuestas al cliente
     */
    public function __construct(
        array $allowedOrigins = [],
        ?array $allowedMethods = null,
        ?array $allowedHeaders = null,
        bool $allowCredentials = true,
        int $maxAge = 86400,
        array $exposedHeaders = []
    ) {
        $this->setAllowedOrigins($allowedOrigins);

        if ($allowedMethods !== null) {
            $this->allowedMethods = array_values(array_unique(array_map('strtoupper', array_map('trim', $allowedMethods))));
        }

        if ($allowedHeaders !== null) {
            $this->allowedHeaders = array_values(array_unique(array_filter(array_map('trim', $allowedHeaders))));
        }

        $this->allowCredentials = $allowCredentials;
        $this->maxAge = max(0, $maxAge);
        $this->exposedHeaders = array_values(array_unique(array_filter(array_map('trim', $exposedHeaders))));
    }

    /**
     * Configura la lista de orígenes autorizados normalizándolos estrictamente.
     *
     * @param array<string> $origins
     */
    public function setAllowedOrigins(array $origins): self
    {
        $this->allowedOrigins = [];
        foreach ($origins as $origin) {
            $normalized = self::normalizeOrigin((string)$origin);
            if ($normalized !== null) {
                $this->allowedOrigins[$normalized] = true;
            }
        }
        return $this;
    }

    /**
     * Añade un origen a la lista blanca.
     */
    public function addAllowedOrigin(string $origin): self
    {
        $normalized = self::normalizeOrigin($origin);
        if ($normalized !== null) {
            $this->allowedOrigins[$normalized] = true;
        }
        return $this;
    }

    /**
     * Obtiene la lista de orígenes autorizados normalizados.
     *
     * @return array<string>
     */
    public function getAllowedOrigins(): array
    {
        return array_keys($this->allowedOrigins);
    }

    /**
     * Normaliza un origen según RFC 6454 en la tupla scheme://host[:port].
     * Rechaza terminantemente orígenes con path, query, credenciales, comodines o caracteres de control.
     * La validación de bytes de control se efectúa sobre el $origin ORIGINAL antes de cualquier trim().
     *
     * @param string $origin
     * @return string|null Tupla normalizada o null si el origen es sintácticamente inválido o inseguro
     */
    public static function normalizeOrigin(string $origin): ?string
    {
        // 1. Detección estricta de caracteres de control o inyección sobre la entrada original
        if (preg_match('/[\x00-\x1F\x7F]/', $origin)) {
            return null;
        }

        $trimmed = trim($origin);
        if ($trimmed === '' || $trimmed === 'null' || $trimmed === '*') {
            return null;
        }

        $parsed = parse_url($trimmed);
        if ($parsed === false || empty($parsed['scheme']) || empty($parsed['host'])) {
            return null;
        }

        $scheme = strtolower($parsed['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        // Un origen RFC 6454 no puede contener userinfo, path (salvo vacío o /), query ni fragment
        if (!empty($parsed['user']) || !empty($parsed['pass']) || !empty($parsed['query']) || !empty($parsed['fragment'])) {
            return null;
        }

        if (isset($parsed['path']) && $parsed['path'] !== '' && $parsed['path'] !== '/') {
            return null;
        }

        $host = strtolower($parsed['host']);

        // Detección de caracteres no válidos en host (evasiones de subdominio o espacios)
        if (str_contains($host, ' ') || str_contains($host, '/') || str_contains($host, '\\') || str_contains($host, '@')) {
            return null;
        }

        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            return null;
        }

        $normalized = "{$scheme}://{$host}";
        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
        if (!$isDefaultPort) {
            $normalized .= ":{$port}";
        }

        return $normalized;
    }

    /**
     * Verifica si un origen entrante está estrictamente autorizado (Zero-Trust).
     *
     * @param string $origin Origen provisto en la cabecera Origin
     * @return bool True únicamente si coincide exactamente con la lista blanca
     */
    public function isAllowedOrigin(string $origin): bool
    {
        $normalized = self::normalizeOrigin($origin);
        if ($normalized === null) {
            return false;
        }

        return isset($this->allowedOrigins[$normalized]);
    }

    /**
     * Genera el conjunto de cabeceras CORS seguro para una petición.
     * En caso de origen no autorizado, NUNCA emite Access-Control-Allow-Origin ni Credentials (Fail-Closed).
     *
     * @param string $origin Cabecera HTTP_ORIGIN
     * @param bool $isPreflight Si la petición es un OPTIONS preflight
     * @param string|null $requestMethod Método enviado en Access-Control-Request-Method
     * @param string|null $requestHeaders Cabeceras enviadas en Access-Control-Request-Headers
     * @return array<string, string> Mapa de cabeceras HTTP a aplicar
     */
    public function getCorsHeaders(
        string $origin,
        bool $isPreflight = false,
        ?string $requestMethod = null,
        ?string $requestHeaders = null
    ): array {
        if (!$this->isAllowedOrigin($origin)) {
            // Fail-closed: solo Vary Origin para cachés HTTP
            return ['Vary' => 'Origin'];
        }

        $normalized = (string)self::normalizeOrigin($origin);
        $headers = [
            'Access-Control-Allow-Origin' => $normalized,
            'Vary' => 'Origin',
        ];

        if ($this->allowCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        if (!empty($this->exposedHeaders)) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->exposedHeaders);
        }

        if ($isPreflight) {
            $headers['Access-Control-Allow-Methods'] = implode(', ', $this->allowedMethods);
            $headers['Access-Control-Allow-Headers'] = implode(', ', $this->allowedHeaders);
            $headers['Access-Control-Max-Age'] = (string)$this->maxAge;
        }

        return $headers;
    }

    /**
     * Emite directamente las cabeceras CORS en el flujo PHP si headers no fueron enviados.
     * Maneja automáticamente la respuesta preflight 204 No Content si corresponde.
     *
     * @param string|null $origin Origen entrante (por defecto toma $_SERVER['HTTP_ORIGIN'])
     * @param string|null $method Método de la petición (por defecto toma $_SERVER['REQUEST_METHOD'])
     * @return bool True si la petición fue un preflight OPTIONS manejado (finalizado), false si continúa
     */
    public function handleRequest(?string $origin = null, ?string $method = null): bool
    {
        $origin = $origin ?? ($_SERVER['HTTP_ORIGIN'] ?? '');
        $method = strtoupper($method ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $isPreflight = ($method === 'OPTIONS');

        $reqMethod = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] ?? null;
        $reqHeaders = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? null;

        $headers = $this->getCorsHeaders($origin, $isPreflight, $reqMethod, $reqHeaders);

        if (!headers_sent()) {
            foreach ($headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }

        if ($isPreflight && $this->isAllowedOrigin($origin)) {
            if (!headers_sent()) {
                http_response_code(204);
            }
            return true;
        }

        return false;
    }

    /**
     * Fábrica para crear una instancia preconfigurada a partir de variables de entorno y fallbacks locales.
     *
     * @param string|null $appUrl URL canónica base (APP_URL)
     * @param string|null $corsOriginsEnv Lista separada por comas (CORS_ALLOWED_ORIGINS)
     * @param bool $allowLocalDev Si se deben incluir orígenes estándar de desarrollo local (localhost, 127.0.0.1)
     */
    public static function fromEnvironment(
        ?string $appUrl = null,
        ?string $corsOriginsEnv = null,
        bool $allowLocalDev = false
    ): self {
        $allowed = [];

        if (!empty($corsOriginsEnv)) {
            foreach (explode(',', $corsOriginsEnv) as $o) {
                $trimmed = trim($o);
                if ($trimmed !== '') {
                    $allowed[] = $trimmed;
                }
            }
        }

        if (!empty($appUrl)) {
            $allowed[] = $appUrl;
        }

        if ($allowLocalDev) {
            $allowed[] = 'http://localhost';
            $allowed[] = 'https://localhost';
            $allowed[] = 'http://127.0.0.1';
            $allowed[] = 'https://127.0.0.1';
        }

        return new self($allowed);
    }
}
