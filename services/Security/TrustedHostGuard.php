<?php
declare(strict_types=1);

namespace Harness\Http;

/**
 * Guardián Anti Host-Header-Poisoning y Generador de URLs Canónicas.
 * Previene la manipulación de cabeceras Host/X-Forwarded-Host en flujos sensibles
 * (OAuth Redirections, Emails transaccionales de reseteo de clave, Callbacks de datos).
 */
class TrustedHostGuard
{
    private string $canonicalBaseUrl;

    /** @var array<string, bool> */
    private array $trustedHosts = [];

    private bool $strictMode = false;
    private bool $trustProxy = false;

    /** @var array<string, bool> */
    private array $trustedProxies = [];

    /**
     * @param string $canonicalBaseUrl URL base canónica fija (e.g. 'https://xindro.app')
     * @param array<string> $trustedHosts Lista de dominios/hosts permitidos (e.g. ['xindro.app', 'localhost:8080'])
     * @param bool $strictMode Si true, lanza excepción ante host hostil en lugar de fallback a canonical
     * @param bool $trustProxy Si true, permite evaluar X-Forwarded-Host solo desde proxies autorizados
     * @param array<string> $trustedProxies Lista de IPs de proxies inversos confiables
     */
    public function __construct(
        string $canonicalBaseUrl,
        array $trustedHosts = [],
        bool $strictMode = false,
        bool $trustProxy = false,
        array $trustedProxies = []
    ) {
        $this->canonicalBaseUrl = self::cleanBaseUrl($canonicalBaseUrl);
        $this->strictMode = $strictMode;
        $this->trustProxy = $trustProxy;

        foreach ($trustedProxies as $proxyIp) {
            $trimmed = trim((string)$proxyIp);
            if ($trimmed !== '') {
                $this->trustedProxies[$trimmed] = true;
            }
        }

        // Extraer automáticamente el host canónico de la URL base
        $parsedCanonical = parse_url($this->canonicalBaseUrl);
        if ($parsedCanonical && !empty($parsedCanonical['host'])) {
            $cHost = strtolower($parsedCanonical['host']);
            $cPort = $parsedCanonical['port'] ?? null;
            $cEntry = $cPort ? "{$cHost}:{$cPort}" : $cHost;
            $this->trustedHosts[$cEntry] = true;
            $this->trustedHosts[$cHost] = true;
        }

        foreach ($trustedHosts as $host) {
            $this->addTrustedHost((string)$host);
        }
    }

    /**
     * Limpia y normaliza una URL base eliminando trailing slashes.
     */
    public static function cleanBaseUrl(string $url): string
    {
        $trimmed = trim($url);
        if ($trimmed === '') {
            return 'http://localhost';
        }
        $parsed = parse_url($trimmed);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return 'http://localhost';
        }

        $scheme = strtolower($parsed['scheme']);
        $host = strtolower($parsed['host']);
        $port = $parsed['port'] ?? null;
        $path = isset($parsed['path']) ? rtrim($parsed['path'], '/') : '';

        $base = "{$scheme}://{$host}";
        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);
        if ($port && !$isDefaultPort) {
            $base .= ":{$port}";
        }

        return $base . $path;
    }

    /**
     * Añade un host confiable a la lista blanca.
     */
    public function addTrustedHost(string $rawHost): self
    {
        $normalized = self::normalizeHost($rawHost);
        if ($normalized !== null) {
            $this->trustedHosts[$normalized] = true;

            // Si contiene puerto, registrar también la variante sin puerto
            if (str_contains($normalized, ':') && !str_starts_with($normalized, '[')) {
                $parts = explode(':', $normalized, 2);
                $this->trustedHosts[$parts[0]] = true;
            }
        }
        return $this;
    }

    /**
     * Obtiene la lista de hosts confiables registrados.
     *
     * @return array<string>
     */
    public function getTrustedHosts(): array
    {
        return array_keys($this->trustedHosts);
    }

    /**
     * Normaliza y valida sintácticamente una cabecera Host.
     * Rechaza inyecciones CRLF, caracteres de control, userinfo, rutas o puertos no numéricos.
     * La validación de bytes de control se efectúa sobre el $host ORIGINAL antes de cualquier trim().
     *
     * @param string $host Cadena cruda de la cabecera Host
     * @return string|null Host normalizado (host o host:port) o null si es inválido/malicioso
     */
    public static function normalizeHost(string $host): ?string
    {
        // 1. Detección estricta de caracteres de control o inyección sobre la entrada original
        if (preg_match('/[\x00-\x1F\x7F]/', $host)) {
            return null;
        }

        $trimmed = trim($host);
        if ($trimmed === '') {
            return null;
        }

        // Rechazo de userinfo (@), rutas (/ o \) o caracteres prohibidos
        if (str_contains($trimmed, '@') || str_contains($trimmed, '/') || str_contains($trimmed, '\\')) {
            return null;
        }

        // Eliminar punto final de DNS raíz (e.g. example.com. -> example.com)
        $trimmed = rtrim($trimmed, '.');

        $port = null;
        $hostname = $trimmed;

        // Soporte de dirección IPv6 bracketed: [::1]:8080 o [::1]
        if (str_starts_with($trimmed, '[')) {
            $closingBracket = strpos($trimmed, ']');
            if ($closingBracket === false) {
                return null; // Malformada
            }
            $ipPart = substr($trimmed, 1, $closingBracket - 1);
            if (!filter_var($ipPart, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return null;
            }
            $remainder = substr($trimmed, $closingBracket + 1);
            if ($remainder !== '') {
                if (!str_starts_with($remainder, ':')) {
                    return null;
                }
                $rawPort = substr($remainder, 1);
                if (!ctype_digit($rawPort) || (int)$rawPort < 1 || (int)$rawPort > 65535) {
                    return null;
                }
                $port = (int)$rawPort;
            }
            return $port ? "[{$ipPart}]:{$port}" : "[{$ipPart}]";
        }

        // Hostname IPv4 o DNS tradicional
        if (str_contains($trimmed, ':')) {
            $parts = explode(':', $trimmed);
            if (count($parts) !== 2) {
                return null; // Múltiples dos puntos inválidos
            }
            $hostname = $parts[0];
            $rawPort = $parts[1];
            if (!ctype_digit($rawPort) || (int)$rawPort < 1 || (int)$rawPort > 65535) {
                return null;
            }
            $port = (int)$rawPort;
        }

        $hostname = strtolower($hostname);

        // Validar que sea un dominio RFC 1123, localhost o IP válida
        $isValidHost = ($hostname === 'localhost')
            || filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            || filter_var($hostname, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;

        if (!$isValidHost) {
            return null;
        }

        return $port ? "{$hostname}:{$port}" : $hostname;
    }

    /**
     * Determina si un host específico está en la lista blanca de confianza.
     *
     * @param string $host Cadena de host a evaluar
     */
    public function isHostTrusted(string $host): bool
    {
        $normalized = self::normalizeHost($host);
        if ($normalized === null) {
            return false;
        }

        if (isset($this->trustedHosts[$normalized])) {
            return true;
        }

        // Si incluye puerto estándar (80 o 443), verificar si el host base sin puerto está autorizado
        if (str_contains($normalized, ':') && !str_starts_with($normalized, '[')) {
            $parts = explode(':', $normalized, 2);
            $port = (int)$parts[1];
            if (($port === 80 || $port === 443) && isset($this->trustedHosts[$parts[0]])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resuelve de forma segura el host de la petición actual inspeccionando cabeceras
     * de servidor con política Fail-Closed.
     *
     * @param array<string, mixed> $server Arreglo $_SERVER o similar
     * @return string Host de confianza verificado o el host canónico configurado
     */
    public function resolveHost(array $server = []): string
    {
        if (empty($server)) {
            $server = $_SERVER;
        }

        $candidateHost = null;

        // Evaluar X-Forwarded-Host solo si el proxy inverso está expresamente autorizado
        if ($this->trustProxy && !empty($server['HTTP_X_FORWARDED_HOST'])) {
            $remoteIp = $server['REMOTE_ADDR'] ?? '';
            if (isset($this->trustedProxies[$remoteIp])) {
                $rawForwarded = explode(',', (string)$server['HTTP_X_FORWARDED_HOST'])[0];
                $candidateHost = trim($rawForwarded);
            }
        }

        if ($candidateHost === null && !empty($server['HTTP_HOST'])) {
            $candidateHost = (string)$server['HTTP_HOST'];
        }

        if ($candidateHost === null && !empty($server['SERVER_NAME'])) {
            $candidateHost = (string)$server['SERVER_NAME'];
        }

        if ($candidateHost !== null && $this->isHostTrusted($candidateHost)) {
            return (string)self::normalizeHost($candidateHost);
        }

        if ($this->strictMode) {
            throw new \UnexpectedValueException(
                "Ataque potencial de Host Header Poisoning detectado: host '{$candidateHost}' no está autorizado."
            );
        }

        // Fallback seguro a la URL canónica preconfigurada
        $parsed = parse_url($this->canonicalBaseUrl);
        $fallbackHost = $parsed['host'] ?? 'localhost';
        if (!empty($parsed['port'])) {
            $fallbackHost .= ':' . $parsed['port'];
        }

        return $fallbackHost;
    }

    /**
     * Devuelve la URL base canónica garantizada.
     */
    public function getCanonicalBaseUrl(): string
    {
        return $this->canonicalBaseUrl;
    }

    /**
     * Construye una URL canónica absoluta inmune a manipulación de cabeceras.
     *
     * @param string $path Ruta relativa (e.g. '/reset-password.php' o 'api/endpoint.php')
     * @param array<string, mixed> $query Parámetros de consulta a adjuntar
     */
    public function buildCanonicalUrl(string $path = '', array $query = []): string
    {
        $normalizedPath = '/' . ltrim($path, '/');
        $url = $this->canonicalBaseUrl . ($path !== '' ? $normalizedPath : '');

        if (!empty($query)) {
            $queryString = http_build_query($query);
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }

        return $url;
    }

    /**
     * Genera la URI canónica de redirección para Meta OAuth.
     */
    public function getOAuthRedirectUri(string $path = '/callback-meta.php'): string
    {
        return $this->buildCanonicalUrl($path);
    }

    /**
     * Genera la URL de reseteo de contraseña segura para correos transaccionales.
     */
    public function getPasswordResetUrl(string $rawToken, string $path = '/reset-password.php'): string
    {
        return $this->buildCanonicalUrl($path, ['token' => $rawToken]);
    }

    /**
     * Genera la URL de comprobación de borrado de datos de usuario de Meta.
     */
    public function getDataDeletionStatusUrl(string $confirmationCode, string $path = '/data-deletion.php'): string
    {
        return $this->buildCanonicalUrl($path, ['id' => $confirmationCode]);
    }

    /**
     * Fábrica para crear una instancia preconfigurada a partir de variables de entorno.
     *
     * @param string|null $appUrl URL canónica base (APP_URL)
     * @param array<string> $additionalTrustedHosts Dominios adicionales
     * @param bool $strictMode
     */
    public static function fromEnvironment(
        ?string $appUrl = null,
        array $additionalTrustedHosts = [],
        bool $strictMode = false
    ): self {
        $baseUrl = !empty($appUrl) ? $appUrl : (getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://localhost'));
        $hosts = array_merge(['localhost', '127.0.0.1'], $additionalTrustedHosts);

        return new self($baseUrl, $hosts, $strictMode);
    }
}
