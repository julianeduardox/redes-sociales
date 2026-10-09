<?php
declare(strict_types=1);

namespace Harness\Http;

/**
 * Cliente HTTP cURL Seguro con protección Anti-SSRF estricta y mitigación de fugas.
 *
 * Características de seguridad:
 * 1. Pre-resolución DNS y validación de todas las IPs (IPv4 e IPv6).
 * 2. Inyección de CURLOPT_RESOLVE (IPv4 y [IPv6]) para mitigar DNS Rebinding / TOCTOU conservando TLS y SNI.
 * 3. Aislamiento de proxies: CURLOPT_PROXY forzado a '' para ignorar variables de entorno (http_proxy, https_proxy).
 * 4. Control estricto de buffers mediante callbacks de streaming (CURLOPT_HEADERFUNCTION y CURLOPT_WRITEFUNCTION)
 *    con límites independientes para cabeceras y cuerpo.
 * 5. Control estricto de redirecciones: bloqueo por defecto entre orígenes. Si se permite, política Whitelist
 *    estricta de cabeceras (solo preserva Accept/User-Agent, eliminando todo token de autenticación)
 *    y bloqueo absoluto de reenvío de cuerpos en 307/308 hacia terceros.
 * 6. Normalización robusta de URLs relativas (//, .., ., ?query).
 */
class SafeHttpClient
{
    protected bool $allowHttp;
    protected int $timeoutSeconds;
    protected int $maxRedirects;
    protected array $allowedPorts;
    protected int $maxHeaderBytes;
    protected int $maxBodyBytes;
    protected bool $allowCrossDomainRedirect;

    /**
     * Lista blanca exclusiva de cabeceras seguras permitidas al saltar entre orígenes diferentes.
     * Cualquier otra cabecera (tokens de autorización, credenciales, cookies, API keys) es eliminada.
     */
    private const SAFE_CROSS_ORIGIN_HEADERS = [
        'accept',
        'accept-language',
        'accept-encoding',
        'user-agent'
    ];

    public function __construct(
        bool $allowHttp = false,
        int $timeoutSeconds = 15,
        int $maxRedirects = 0,
        array $allowedPorts = [443],
        int $maxBodyBytes = 2097152,     // 2 MB por defecto
        int $maxHeaderBytes = 32768,      // 32 KB por defecto
        bool $allowCrossDomainRedirect = false
    ) {
        $this->allowHttp = $allowHttp;
        $this->timeoutSeconds = max(1, $timeoutSeconds);
        $this->maxRedirects = max(0, $maxRedirects);
        $this->allowedPorts = $allowedPorts;
        $this->maxBodyBytes = max(1024, $maxBodyBytes);
        $this->maxHeaderBytes = max(1024, $maxHeaderBytes);
        $this->allowCrossDomainRedirect = $allowCrossDomainRedirect;

        if ($this->allowHttp && !in_array(80, $this->allowedPorts, true)) {
            $this->allowedPorts[] = 80;
        }
    }

    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, ['headers' => $headers]);
    }

    public function post(string $url, string|array $body = '', array $headers = []): array
    {
        return $this->request('POST', $url, [
            'headers' => $headers,
            'body'    => $body
        ]);
    }

    /**
     * Ejecuta una petición HTTP con inspección de seguridad en cada salto.
     *
     * @throws SafeHttpClientException
     */
    public function request(string $method, string $url, array $options = []): array
    {
        $currentUrl = $url;
        $originalParsed = parse_url($url);
        $originalOrigin = ($originalParsed['scheme'] ?? '') . '://' . ($originalParsed['host'] ?? '') . (isset($originalParsed['port']) ? ':' . $originalParsed['port'] : '');

        $redirectsFollowed = 0;
        $currentHeaders = $options['headers'] ?? [];
        $currentBody = $options['body'] ?? '';
        $currentMethod = strtoupper($method);

        while (true) {
            $validatedTarget = $this->validateAndResolveUrl($currentUrl);
            $response = $this->executeCurl($currentMethod, $currentUrl, $validatedTarget, $currentHeaders, $currentBody);

            $statusCode = $response['status'];
            if (in_array($statusCode, [301, 302, 303, 307, 308], true) && isset($response['headers']['location'])) {
                if ($redirectsFollowed >= $this->maxRedirects) {
                    if ($this->maxRedirects === 0) {
                        return $response;
                    }
                    throw new SafeHttpClientException(
                        "Límite máximo de redirecciones superado ({$this->maxRedirects})."
                    );
                }

                $location = trim($response['headers']['location']);
                $nextUrl = $this->resolveRedirectUrl($currentUrl, $location);

                $nextParsed = parse_url($nextUrl);
                $nextOrigin = ($nextParsed['scheme'] ?? '') . '://' . ($nextParsed['host'] ?? '') . (isset($nextParsed['port']) ? ':' . $nextParsed['port'] : '');

                $isCrossDomain = (strcasecmp($originalOrigin, $nextOrigin) !== 0);

                // 1. Bloqueo de redirecciones entre diferentes orígenes por defecto
                if ($isCrossDomain && !$this->allowCrossDomainRedirect) {
                    throw new SafeHttpClientException(
                        "Redirección entre diferentes orígenes bloqueada por política de seguridad " .
                        "(origen inicial: '{$originalOrigin}', destino: '{$nextOrigin}')."
                    );
                }

                // 2. Si se permite redirección entre orígenes, filtrar cabeceras con lista blanca estricta
                if ($isCrossDomain) {
                    $currentHeaders = $this->filterHeadersForCrossOrigin($currentHeaders);
                }

                // 3. Manejo de métodos y cuerpos en redirecciones (RFC 7231 / 9110)
                if (in_array($statusCode, [301, 302, 303], true)) {
                    $currentMethod = 'GET';
                    $currentBody = '';
                } elseif (in_array($statusCode, [307, 308], true) && $isCrossDomain) {
                    // En 307/308 hacia otro origen, PROHIBIDO reenviar el cuerpo de la petición
                    throw new SafeHttpClientException(
                        "Redirección {$statusCode} hacia un origen diferente rechazada: " .
                        "impide la retransmisión involuntaria del cuerpo de la petición a un tercero."
                    );
                }

                $currentUrl = $nextUrl;
                $redirectsFollowed++;
                continue;
            }

            return $response;
        }
    }

    /**
     * Valida la URL y resuelve el destino de forma segura.
     *
     * @return array{host: string, port: int, ip: string, scheme: string}
     */
    public function validateAndResolveUrl(string $url): array
    {
        $parsed = parse_url($url);
        if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            throw new SafeHttpClientException("URL malformada o inválida: '{$url}'.");
        }

        $scheme = strtolower($parsed['scheme']);
        if ($scheme === 'http') {
            if (!$this->allowHttp) {
                throw new SafeHttpClientException(
                    "Esquema 'http' no permitido por defecto. El arnés exige 'https' para mitigar MITM y fugas."
                );
            }
        } elseif ($scheme !== 'https') {
            throw new SafeHttpClientException(
                "Esquema no permitido: '{$scheme}'. Solo se admite HTTPS (y HTTP si está explícitamente configurado)."
            );
        }

        if (!empty($parsed['user']) || !empty($parsed['pass'])) {
            throw new SafeHttpClientException(
                "URLs con credenciales de autenticación básica embebidas están estrictamente prohibidas."
            );
        }

        $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (!in_array($port, $this->allowedPorts, true)) {
            throw new SafeHttpClientException(
                "Puerto de destino {$port} no permitido. Puertos autorizados: " . implode(', ', $this->allowedPorts) . "."
            );
        }

        $rawHost = $parsed['host'];
        $cleanHost = strtolower(trim($rawHost, '[]'));

        // Host literal (IPv4 o IPv6 directa o en formato de evasión)
        $canonicalIpv4 = IpValidator::canonicalizeIpv4($cleanHost);
        $isLiteralIp = filter_var($cleanHost, FILTER_VALIDATE_IP) !== false || $canonicalIpv4 !== null;

        if ($isLiteralIp) {
            $ipToCheck = $canonicalIpv4 ?? $cleanHost;
            if (!$this->isPermittedIp($ipToCheck)) {
                throw new SafeHttpClientException(
                    "Acceso denegado (SSRF): La IP de destino '{$ipToCheck}' pertenece a un rango privado, reservado o no permitido."
                );
            }
            return [
                'host'   => $rawHost,
                'port'   => $port,
                'ip'     => $ipToCheck,
                'scheme' => $scheme
            ];
        }

        // Host es un FQDN: Pre-resolver DNS y validar todas las IPs
        $resolvedIps = $this->resolveDnsRecords($cleanHost);
        if (empty($resolvedIps)) {
            throw new SafeHttpClientException(
                "Fallo de resolución DNS: No se pudieron obtener direcciones IP para el host '{$cleanHost}'."
            );
        }

        foreach ($resolvedIps as $ip) {
            if (!$this->isPermittedIp($ip)) {
                throw new SafeHttpClientException(
                    "Acceso denegado (SSRF / DNS Rebinding): El dominio '{$cleanHost}' resolvió a la dirección no pública '{$ip}'."
                );
            }
        }

        return [
            'host'   => $rawHost,
            'port'   => $port,
            'ip'     => $resolvedIps[0],
            'scheme' => $scheme
        ];
    }

    /**
     * Comprueba si una IP está autorizada para la conexión.
     * En producción y por defecto, NUNCA permite direcciones privadas.
     */
    protected function isPermittedIp(string $ip): bool
    {
        return IpValidator::isPublicIp($ip);
    }

    protected function resolveDnsRecords(string $host): array
    {
        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $r) {
                    if (isset($r['ip'])) {
                        $ips[] = $r['ip'];
                    } elseif (isset($r['ipv6'])) {
                        $ips[] = $r['ipv6'];
                    }
                }
            }
        }

        if (empty($ips) && function_exists('gethostbynamel')) {
            $aRecords = @gethostbynamel($host);
            if (is_array($aRecords)) {
                $ips = array_merge($ips, $aRecords);
            }
        }

        return array_values(array_unique($ips));
    }

    /**
     * Ejecuta cURL inyectando CURLOPT_RESOLVE, deshabilitando proxies y controlando
     * el tamaño de cabeceras y cuerpo mediante callbacks en tiempo real.
     */
    private function executeCurl(string $method, string $url, array $target, array $headers, string|array $body): array
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new \RuntimeException('No se pudo inicializar el recurso cURL.');
        }

        // 1. Formato de CURLOPT_RESOLVE: IPv6 envuelta en corchetes [address]
        $formattedIp = (filter_var($target['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)
            ? '[' . trim($target['ip'], '[]') . ']'
            : $target['ip'];

        $resolveEntry = "{$target['host']}:{$target['port']}:{$formattedIp}";
        curl_setopt($ch, CURLOPT_RESOLVE, [$resolveEntry]);

        // 2. Aislamiento de proxies de entorno
        curl_setopt($ch, CURLOPT_PROXY, '');

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Manejado por callbacks de escritura
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, max(1, min(3, $this->timeoutSeconds)));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

        // 3. Verificación TLS con SNI
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $allowedProtocols = $this->allowHttp ? (CURLPROTO_HTTPS | CURLPROTO_HTTP) : CURLPROTO_HTTPS;
        curl_setopt($ch, CURLOPT_PROTOCOLS, $allowedProtocols);
        curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, $allowedProtocols);

        // 4. Control de tamaño en streaming para cabeceras y cuerpo
        $rawHeaders = '';
        $headerBytes = 0;
        $maxHeaders = $this->maxHeaderBytes;
        $headerLimitExceeded = false;

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, string $line) use (&$rawHeaders, &$headerBytes, $maxHeaders, &$headerLimitExceeded): int {
            $len = strlen($line);
            $headerBytes += $len;
            if ($headerBytes > $maxHeaders) {
                $headerLimitExceeded = true;
                return -1; // Aborta la transferencia de inmediato
            }
            $rawHeaders .= $line;
            return $len;
        });

        $responseBody = '';
        $bodyBytes = 0;
        $maxBody = $this->maxBodyBytes;
        $bodyLimitExceeded = false;

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, string $chunk) use (&$responseBody, &$bodyBytes, $maxBody, &$bodyLimitExceeded): int {
            $len = strlen($chunk);
            $bodyBytes += $len;
            if ($bodyBytes > $maxBody) {
                $bodyLimitExceeded = true;
                return -1; // Aborta la transferencia de inmediato
            }
            $responseBody .= $chunk;
            return $len;
        });

        // Método y datos
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : $body);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        // Validación estricta y formateo de cabeceras salientes (Anti-CRLF Injection)
        if (!empty($headers)) {
            $formattedHeaders = self::validateAndFormatHeaders($headers);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $formattedHeaders);
        }

        $success = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($headerLimitExceeded) {
            throw new SafeHttpClientException(
                "Transferencia abortada: El tamaño de las cabeceras HTTP excedió el límite máximo ({$this->maxHeaderBytes} bytes)."
            );
        }

        if ($bodyLimitExceeded) {
            throw new SafeHttpClientException(
                "Transferencia abortada: El tamaño del cuerpo de la respuesta excedió el límite máximo ({$this->maxBodyBytes} bytes)."
            );
        }

        if ($curlErrno !== 0) {
            throw new SafeHttpClientException("Error en conexión cURL ({$curlErrno}): {$curlError}");
        }

        return [
            'status'  => $httpCode,
            'headers' => $this->parseHeaders($rawHeaders),
            'body'    => $responseBody,
            'ip'      => $target['ip']
        ];
    }

    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $lines = explode("\r\n", $rawHeaders);
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * Valida y formatea las cabeceras HTTP salientes, neutralizando ataques de inyección CRLF.
     *
     * @param array $headers
     * @return array<int, string> Lista de cabeceras validadas en formato "Header-Name: value"
     * @throws SafeHttpClientException Si se detecta salto de línea (\r, \n) o nombre inválido.
     */
    public static function validateAndFormatHeaders(array $headers): array
    {
        $formatted = [];
        foreach ($headers as $k => $v) {
            // 1. Validar tipos de entrada
            if (!is_int($k) && !is_string($k)) {
                throw new SafeHttpClientException("Clave de cabecera inválida (debe ser string o int).");
            }
            if (!is_scalar($v) && $v !== null) {
                throw new SafeHttpClientException("Valor de cabecera inválido (debe ser escalar o string).");
            }

            // 2. Detección estricta de caracteres de control y bytes nulos en la entrada original ANTES de cualquier trim()
            // trim() en PHP elimina por defecto \0, \r, \n, \t, espacios y \x0B; validamos la entrada cruda primero
            if (is_string($k) && preg_match('/[\x00-\x1F\x7F]/', $k)) {
                throw new SafeHttpClientException(
                    "Inyección de cabecera HTTP (CRLF o caracteres de control / byte nulo) detectada en el nombre de la cabecera original."
                );
            }

            $vStr = (string)$v;
            if (preg_match('/[\x00-\x1F\x7F]/', $vStr)) {
                throw new SafeHttpClientException(
                    "Inyección de cabecera HTTP (CRLF o caracteres de control / byte nulo) detectada en el valor de la cabecera original."
                );
            }

            $rawLine = is_int($k) ? $vStr : "{$k}: {$vStr}";

            // Detección en la representación de línea completa antes de trim()
            if (preg_match('/[\x00-\x1F\x7F]/', $rawLine)) {
                throw new SafeHttpClientException(
                    "Inyección de cabecera HTTP (CRLF o caracteres de control) detectada en '{$rawLine}'."
                );
            }

            // 3. Separar nombre y valor
            if (!str_contains($rawLine, ':')) {
                throw new SafeHttpClientException(
                    "Cabecera HTTP malformada: falta el delimitador ':' en '{$rawLine}'."
                );
            }

            [$name, $value] = explode(':', $rawLine, 2);
            $cleanName = trim($name);
            $cleanValue = trim($value);

            // 4. Validar nombre de cabecera conforme a RFC 7230 / 9110 (tokens válidos)
            if (!preg_match('/\A[a-zA-Z0-9!#$%&\'*+\-.^_`|~]+\z/', $cleanName)) {
                throw new SafeHttpClientException(
                    "Nombre de cabecera HTTP inválido o malicioso: '{$cleanName}'."
                );
            }

            $formatted[] = "{$cleanName}: {$cleanValue}";
        }
        return $formatted;
    }

    /**
     * Filtra las cabeceras usando una lista blanca estricta ante redirecciones cross-origin,
     * tras validar que no existan inyecciones CRLF.
     */
    private function filterHeadersForCrossOrigin(array $headers): array
    {
        $validated = self::validateAndFormatHeaders($headers);
        $safeHeaders = [];
        foreach ($validated as $line) {
            [$name] = explode(':', $line, 2);
            if (in_array(strtolower(trim($name)), self::SAFE_CROSS_ORIGIN_HEADERS, true)) {
                $safeHeaders[] = $line;
            }
        }
        return $safeHeaders;
    }

    public function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        $trimmedLoc = trim($location);

        if (preg_match('#\Ahttps?://#i', $trimmedLoc)) {
            return $trimmedLoc;
        }

        $baseParsed = parse_url($baseUrl);
        $scheme = $baseParsed['scheme'] ?? 'https';
        $host = $baseParsed['host'] ?? '';
        $port = isset($baseParsed['port']) ? ':' . $baseParsed['port'] : '';

        // Protocol-relative (//dominio.com/ruta)
        if (str_starts_with($trimmedLoc, '//')) {
            return "{$scheme}:{$trimmedLoc}";
        }

        // Query string (?param=1)
        if (str_starts_with($trimmedLoc, '?')) {
            $basePath = $baseParsed['path'] ?? '/';
            return "{$scheme}://{$host}{$port}{$basePath}{$trimmedLoc}";
        }

        // Ruta absoluta (/ruta)
        if (str_starts_with($trimmedLoc, '/')) {
            return "{$scheme}://{$host}{$port}{$trimmedLoc}";
        }

        // Ruta relativa (../ruta, ./ruta, o ruta-simple)
        $basePath = $baseParsed['path'] ?? '/';
        $baseDir = rtrim(dirname($basePath), '/\\');
        $rawCombined = ($baseDir === '' ? '' : $baseDir) . '/' . $trimmedLoc;

        $segments = explode('/', str_replace('\\', '/', $rawCombined));
        $normalized = [];
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($normalized);
            } else {
                $normalized[] = $seg;
            }
        }

        $canonicalPath = '/' . implode('/', $normalized);
        return "{$scheme}://{$host}{$port}{$canonicalPath}";
    }
}
