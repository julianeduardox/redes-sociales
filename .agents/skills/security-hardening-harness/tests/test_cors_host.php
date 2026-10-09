<?php
declare(strict_types=1);

/**
 * Suite de Pruebas Unitarias del Módulo CORS Zero-Trust y Anti Host-Poisoning (Harness\Http)
 * Bloque 4 del Security Hardening Harness.
 * Ejecutable vía CLI: php tests/test_cors_host.php
 */

require_once __DIR__ . '/../src/Http/CorsGuard.php';
require_once __DIR__ . '/../src/Http/TrustedHostGuard.php';

use Harness\Http\CorsGuard;
use Harness\Http\TrustedHostGuard;

$testsTotal = 0;
$testsPassed = 0;
$testsFailed = 0;

function assert_test(string $description, bool $condition, string $details = ''): void {
    global $testsTotal, $testsPassed, $testsFailed;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$description}\n";
    } else {
        $testsFailed++;
        echo "  [FAIL] {$description}" . ($details !== '' ? " - Detalle: {$details}" : '') . "\n";
    }
}

echo "=================================================================\n";
echo "   SUITE DE PRUEBAS UNITARIAS: HARNESS CORS & TRUSTED HOST       \n";
echo "=================================================================\n\n";

// =============================================================================
// SECCIÓN 1: CORS GUARD (CORS ZERO-TRUST & WHITELIST ESTRICTA)
// =============================================================================
echo "--- 1. CorsGuard: Normalización Estricta de Orígenes (RFC 6454) ---\n";

assert_test(
    "1.1 Origen HTTPS estándar elimina puerto 443 por defecto",
    CorsGuard::normalizeOrigin('https://api.xindro.app:443') === 'https://api.xindro.app'
);

assert_test(
    "1.2 Origen HTTP estándar elimina puerto 80 por defecto",
    CorsGuard::normalizeOrigin('http://localhost:80') === 'http://localhost'
);

assert_test(
    "1.3 Origen con puerto no estándar preserva tupla scheme://host:port",
    CorsGuard::normalizeOrigin('http://localhost:3000') === 'http://localhost:3000'
);

assert_test(
    "1.4 Normalización convierte mayúsculas a minúsculas",
    CorsGuard::normalizeOrigin('HTTPS://API.XINDRO.APP') === 'https://api.xindro.app'
);

assert_test(
    "1.5 Trailing slash es saneado para coincidencia exacta",
    CorsGuard::normalizeOrigin('https://api.xindro.app/') === 'https://api.xindro.app'
);

echo "\n--- 2. CorsGuard: Detección y Rechazo de Evasiones Maliciosas ---\n";

assert_test(
    "2.1 Origen nulo 'null' es rechazado",
    CorsGuard::normalizeOrigin('null') === null
);

assert_test(
    "2.2 Comodín asterisco '*' es rechazado",
    CorsGuard::normalizeOrigin('*') === null
);

assert_test(
    "2.3 Inyección CRLF (\\r\\n) en medio de origen es rechazada",
    CorsGuard::normalizeOrigin("https://xindro.app\r\nX-Evil: 1") === null
);

assert_test(
    "2.3b Inyección CRLF (\\r\\n) o LF al final de origen es rechazada antes de trim()",
    CorsGuard::normalizeOrigin("https://xindro.app\r\n") === null &&
    CorsGuard::normalizeOrigin("https://xindro.app\n") === null
);

assert_test(
    "2.4 Byte nulo (\\0) en origen es rechazado",
    CorsGuard::normalizeOrigin("https://xindro.app\0.evil.com") === null
);

assert_test(
    "2.5 Origen con userinfo/credenciales embebidas (@) es rechazado",
    CorsGuard::normalizeOrigin('https://attacker.com@xindro.app') === null
);

assert_test(
    "2.6 Origen con ruta (/api/v1) es rechazado como origen inválido",
    CorsGuard::normalizeOrigin('https://xindro.app/api/v1') === null
);

assert_test(
    "2.7 Origen con query string (?token=abc) es rechazado",
    CorsGuard::normalizeOrigin('https://xindro.app?token=abc') === null
);

assert_test(
    "2.8 Esquemas no HTTP/HTTPS (javascript:, file:, data:) son rechazados",
    CorsGuard::normalizeOrigin('javascript:alert(1)') === null &&
    CorsGuard::normalizeOrigin('file:///etc/passwd') === null
);

echo "\n--- 3. CorsGuard: Validación Zero-Trust contra Lista Blanca ---\n";

$cors = new CorsGuard([
    'https://xindro.app',
    'https://turbogram.site',
    'http://localhost:3000'
]);

assert_test(
    "3.1 Origen idéntico en lista blanca es AUTORIZADO",
    $cors->isAllowedOrigin('https://xindro.app') === true
);

assert_test(
    "3.2 Origen con puerto no estándar en lista blanca es AUTORIZADO",
    $cors->isAllowedOrigin('http://localhost:3000') === true
);

assert_test(
    "3.3 Ataque Subdomain Trick (xindro.app.evil.com) es RECHAZADO",
    $cors->isAllowedOrigin('https://xindro.app.evil.com') === false
);

assert_test(
    "3.4 Ataque Prefix Trick (evilxindro.app) es RECHAZADO",
    $cors->isAllowedOrigin('https://evilxindro.app') === false
);

assert_test(
    "3.5 Origen no registrado (https://malicious.example) es RECHAZADO",
    $cors->isAllowedOrigin('https://malicious.example') === false
);

assert_test(
    "3.6 Puerto no autorizado (http://localhost:8080 no en whitelist) es RECHAZADO",
    $cors->isAllowedOrigin('http://localhost:8080') === false
);

echo "\n--- 4. CorsGuard: Política Fail-Closed en Emisión de Cabeceras ---\n";

$untrustedHeaders = $cors->getCorsHeaders('https://evil.example');
assert_test(
    "4.1 Origen no autorizado NUNCA recibe Access-Control-Allow-Origin",
    !isset($untrustedHeaders['Access-Control-Allow-Origin'])
);

assert_test(
    "4.2 Origen no autorizado NUNCA recibe Access-Control-Allow-Credentials",
    !isset($untrustedHeaders['Access-Control-Allow-Credentials'])
);

$trustedHeaders = $cors->getCorsHeaders('https://xindro.app', isPreflight: false);
assert_test(
    "4.3 Origen autorizado recibe Access-Control-Allow-Origin exacto y Credentials",
    ($trustedHeaders['Access-Control-Allow-Origin'] ?? '') === 'https://xindro.app' &&
    ($trustedHeaders['Access-Control-Allow-Credentials'] ?? '') === 'true' &&
    ($trustedHeaders['Vary'] ?? '') === 'Origin'
);

$preflightHeaders = $cors->getCorsHeaders('https://xindro.app', isPreflight: true);
assert_test(
    "4.4 Petición preflight autorizada recibe Methods, Headers y Max-Age",
    isset($preflightHeaders['Access-Control-Allow-Methods']) &&
    isset($preflightHeaders['Access-Control-Allow-Headers']) &&
    isset($preflightHeaders['Access-Control-Max-Age'])
);

$corsEnv = CorsGuard::fromEnvironment(
    appUrl: 'https://xindro.app',
    corsOriginsEnv: 'https://admin.xindro.app, https://mobile.xindro.app',
    allowLocalDev: true
);
assert_test(
    "4.5 CorsGuard::fromEnvironment carga lista de orígenes, APP_URL y local dev",
    $corsEnv->isAllowedOrigin('https://admin.xindro.app') === true &&
    $corsEnv->isAllowedOrigin('https://mobile.xindro.app') === true &&
    $corsEnv->isAllowedOrigin('http://localhost') === true &&
    $corsEnv->isAllowedOrigin('https://evil.xindro.app') === false
);

// =============================================================================
// SECCIÓN 2: TRUSTED HOST GUARD (ANTI HOST-POISONING & CANONICAL URLS)
// =============================================================================
echo "\n--- 5. TrustedHostGuard: Normalización y Detección de Evasiones en Host ---\n";

assert_test(
    "5.1 Host legítimo con mayúsculas se normaliza a minúsculas",
    TrustedHostGuard::normalizeHost('APP.XINDRO.COM') === 'app.xindro.com'
);

assert_test(
    "5.2 Host con trailing dot de DNS raíz se normaliza limpiamente",
    TrustedHostGuard::normalizeHost('app.xindro.com.') === 'app.xindro.com'
);

assert_test(
    "5.3 Host con puerto numérico se normaliza como host:port",
    TrustedHostGuard::normalizeHost('localhost:8080') === 'localhost:8080'
);

assert_test(
    "5.4 Host IPv6 bracketed con puerto es soportado correctamente",
    TrustedHostGuard::normalizeHost('[::1]:8443') === '[::1]:8443'
);

assert_test(
    "5.5 Inyección CRLF (\\r\\n) en cabecera Host es rechazada",
    TrustedHostGuard::normalizeHost("xindro.app\r\nHost: attacker.com") === null
);

assert_test(
    "5.5b Inyección CRLF (\\r\\n) o LF al final de Host es rechazada antes de trim()",
    TrustedHostGuard::normalizeHost("xindro.app\r\n") === null &&
    TrustedHostGuard::normalizeHost("xindro.app\n") === null
);

assert_test(
    "5.6 Inyección de credenciales (@) en Host es rechazada",
    TrustedHostGuard::normalizeHost('attacker.com@xindro.app') === null
);

assert_test(
    "5.7 Inyección de rutas (/ o \\) en Host es rechazada",
    TrustedHostGuard::normalizeHost('xindro.app/evil') === null &&
    TrustedHostGuard::normalizeHost('xindro.app\\evil') === null
);

assert_test(
    "5.8 Puerto no numérico o fuera de rango (1-65535) es rechazado",
    TrustedHostGuard::normalizeHost('xindro.app:abc') === null &&
    TrustedHostGuard::normalizeHost('xindro.app:999999') === null
);

assert_test(
    "5.9 Inyección de múltiples puertos (:80:80) es rechazada",
    TrustedHostGuard::normalizeHost('xindro.app:80:80') === null
);

echo "\n--- 6. TrustedHostGuard: Resolución Segura y Anti Host-Poisoning ---\n";

$hostGuard = new TrustedHostGuard(
    canonicalBaseUrl: 'https://xindro.app',
    trustedHosts: ['xindro.app', 'api.xindro.app', 'localhost:8080']
);

assert_test(
    "6.1 Host confiable en HTTP_HOST es resuelto exitosamente",
    $hostGuard->resolveHost(['HTTP_HOST' => 'xindro.app']) === 'xindro.app'
);

assert_test(
    "6.2 Host confiable con puerto autorizado es resuelto exitosamente",
    $hostGuard->resolveHost(['HTTP_HOST' => 'localhost:8080']) === 'localhost:8080'
);

assert_test(
    "6.3 Ataque Host Poisoning (attacker.phishing.example) falla a canonical 'xindro.app'",
    $hostGuard->resolveHost(['HTTP_HOST' => 'attacker.phishing.example']) === 'xindro.app'
);

assert_test(
    "6.4 Ataque de inyección de puerto malicioso falla a canonical 'xindro.app'",
    $hostGuard->resolveHost(['HTTP_HOST' => 'xindro.app:6666']) === 'xindro.app'
);

$strictGuard = new TrustedHostGuard(
    canonicalBaseUrl: 'https://xindro.app',
    trustedHosts: ['xindro.app'],
    strictMode: true
);

$strictThrew = false;
try {
    $strictGuard->resolveHost(['HTTP_HOST' => 'evil-host.com']);
} catch (\UnexpectedValueException $e) {
    $strictThrew = true;
}
assert_test(
    "6.5 Modo estricto lanza UnexpectedValueException ante Host Header Poisoning",
    $strictThrew === true
);

echo "\n--- 7. TrustedHostGuard: Proxy Inverso Seguro (X-Forwarded-Host) ---\n";

$proxyGuard = new TrustedHostGuard(
    canonicalBaseUrl: 'https://xindro.app',
    trustedHosts: ['xindro.app', 'api.xindro.app'],
    strictMode: false,
    trustProxy: true,
    trustedProxies: ['10.0.0.1']
);

assert_test(
    "7.1 X-Forwarded-Host desde IP de proxy confiable (10.0.0.1) es aceptado si el host es de confianza",
    $proxyGuard->resolveHost([
        'REMOTE_ADDR' => '10.0.0.1',
        'HTTP_X_FORWARDED_HOST' => 'api.xindro.app'
    ]) === 'api.xindro.app'
);

assert_test(
    "7.2 X-Forwarded-Host desde IP no autorizada (192.168.1.50) es IGNORADO",
    $proxyGuard->resolveHost([
        'REMOTE_ADDR' => '192.168.1.50',
        'HTTP_X_FORWARDED_HOST' => 'api.xindro.app',
        'HTTP_HOST' => 'xindro.app'
    ]) === 'xindro.app'
);

echo "\n--- 8. TrustedHostGuard: Generación Inmune de URLs Canónicas ---\n";

assert_test(
    "8.1 buildCanonicalUrl() construye URL absoluta basada en base canónica",
    $hostGuard->buildCanonicalUrl('/dashboard.php', ['tab' => 'meta']) === 'https://xindro.app/dashboard.php?tab=meta'
);

assert_test(
    "8.2 getOAuthRedirectUri() genera URI oficial para Meta sin depender de HTTP_HOST",
    $hostGuard->getOAuthRedirectUri('/callback-meta.php') === 'https://xindro.app/callback-meta.php'
);

assert_test(
    "8.3 getPasswordResetUrl() genera URL de reseteo con token codificado segura",
    $hostGuard->getPasswordResetUrl('token_hex_12345') === 'https://xindro.app/reset-password.php?token=token_hex_12345'
);

assert_test(
    "8.4 getDataDeletionStatusUrl() genera URL de comprobación de borrado oficial",
    $hostGuard->getDataDeletionStatusUrl('del_abc987') === 'https://xindro.app/data-deletion.php?id=del_abc987'
);

echo "\n=================================================================\n";
echo "RESUMEN DE PRUEBAS HARNESS BLOQUE 4 (CORS & TRUSTED HOST):\n";
echo "  - Total Ejecutadas:  {$testsTotal}\n";
echo "  - Superadas (PASS):  {$testsPassed}\n";
echo "  - Fallidas (FAIL):   {$testsFailed}\n";
echo "=================================================================\n";

if ($testsFailed === 0) {
    echo "ESTADO: BLOQUE 4 - TODAS LAS PRUEBAS SUPERADAS EXITOSAMENTE (EXIT 0)\n\n";
    exit(0);
} else {
    echo "ESTADO: BLOQUE 4 - HUBO PRUEBAS FALLIDAS (EXIT 1)\n\n";
    exit(1);
}
