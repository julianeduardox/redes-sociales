<?php
declare(strict_types=1);

/**
 * Suite de Pruebas Unitarias del Módulo Rate Limiting (Harness\RateLimit)
 * Ejecutable vía CLI: php tests/test_ratelimit.php
 */

require_once __DIR__ . '/../src/RateLimit/RateLimiterInterface.php';
require_once __DIR__ . '/../src/RateLimit/RateLimitExceededException.php';
require_once __DIR__ . '/../src/RateLimit/PdoRateLimiter.php';

use Harness\RateLimit\PdoRateLimiter;
use Harness\RateLimit\RateLimitExceededException;

$testsTotal = 0;
$testsPassed = 0;
$testsFailed = 0;

function assert_true(string $description, bool $condition, string $details = ''): void {
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
echo "   SUITE DE PRUEBAS UNITARIAS: HARNESS RATE LIMITER (PDO)        \n";
echo "=================================================================\n\n";

// Usamos SQLite en memoria para pruebas reproducibles y atómicas
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$limiter = new PdoRateLimiter($pdo, 'test_rate_limits', failClosed: true);
$limiter->ensureTableExists();

// --- 1. Incremento Atómico y Límite de Ventana ---
echo "--- 1. Incremento Atómico y Límite de Ventana ---\n";

$res1 = $limiter->hit('client_1', maxAttempts: 3, decaySeconds: 60);
assert_true("1.1 Primer intento permitido (attempts=1, remaining=2, allowed=true)", $res1['allowed'] === true && $res1['attempts'] === 1 && $res1['remaining'] === 2);

$res2 = $limiter->hit('client_1', maxAttempts: 3, decaySeconds: 60);
assert_true("1.2 Segundo intento permitido (attempts=2, remaining=1, allowed=true)", $res2['allowed'] === true && $res2['attempts'] === 2 && $res2['remaining'] === 1);

$res3 = $limiter->hit('client_1', maxAttempts: 3, decaySeconds: 60);
assert_true("1.3 Tercer intento permitido alcanzando el máximo (attempts=3, remaining=0, allowed=true)", $res3['allowed'] === true && $res3['attempts'] === 3 && $res3['remaining'] === 0);

$res4 = $limiter->hit('client_1', maxAttempts: 3, decaySeconds: 60);
assert_true("1.4 Cuarto intento bloqueado (attempts=4, allowed=false, retry_after > 0)", $res4['allowed'] === false && $res4['attempts'] === 4 && $res4['retry_after'] > 0);


// --- 2. Inspección No Destructiva (check) y Limpieza (clear) ---
echo "\n--- 2. Inspección No Destructiva y Limpieza ---\n";

assert_true("2.1 check() reporta false para clave con límite agotado", $limiter->check('client_1', maxAttempts: 3) === false);
assert_true("2.2 check() reporta true para clave con cuota disponible", $limiter->check('client_fresh', maxAttempts: 3) === true);

$limiter->clear('client_1');
assert_true("2.3 clear() elimina el registro y restaura disponibilidad en check()", $limiter->check('client_1', maxAttempts: 3) === true);


// --- 3. Expiración y Reinicio de Ventana Temporal ---
echo "\n--- 3. Expiración de Ventana Temporal ---\n";

// Simular ventana expirada insertando registro con reset_at en el pasado
$stmt = $pdo->prepare("INSERT OR REPLACE INTO test_rate_limits (rate_key, attempts, reset_at, updated_at) VALUES ('client_expired', 10, :past, :past)");
$stmt->execute([':past' => time() - 10]);

$resExpired = $limiter->hit('client_expired', maxAttempts: 5, decaySeconds: 60);
assert_true("3.1 Clave con ventana expirada se reinicia atómicamente a 1 intento", $resExpired['allowed'] === true && $resExpired['attempts'] === 1 && $resExpired['remaining'] === 4);


// --- 4. Dual-Bucket Limiter (IP + Cuenta) ---
echo "\n--- 4. Dual-Bucket Limiter (IP + Cuenta) ---\n";

$dualLimiter = new PdoRateLimiter($pdo, 'test_rate_limits_dual', failClosed: true);
$dualLimiter->ensureTableExists();

// Caso 4.1: Ambos dentro del límite
$d1 = $dualLimiter->attemptDual(
    ipKey: '192.0.2.1', ipMax: 2, ipDecay: 60,
    accountKey: 'admin@corp.internal', accountMax: 2, accountDecay: 60
);
assert_true("4.1 Dual-Bucket permite intento cuando ambos buckets están disponibles", $d1['allowed'] === true && $d1['violated_bucket'] === null);

// Caso 4.2: Segundo intento, ambos aún en límite
$d2 = $dualLimiter->attemptDual(
    ipKey: '192.0.2.1', ipMax: 2, ipDecay: 60,
    accountKey: 'admin@corp.internal', accountMax: 2, accountDecay: 60
);
assert_true("4.2 Segundo intento permitido en ambos buckets", $d2['allowed'] === true);

// Caso 4.3: Tercer intento, ambos excedidos
$d3 = $dualLimiter->attemptDual(
    ipKey: '192.0.2.1', ipMax: 2, ipDecay: 60,
    accountKey: 'admin@corp.internal', accountMax: 2, accountDecay: 60
);
assert_true("4.3 Tercer intento bloquea con violated_bucket='both'", $d3['allowed'] === false && $d3['violated_bucket'] === 'both');

// Caso 4.4: IP bloqueada pero cuenta diferente (ataque distribuido o spraying desde misma IP)
$d4 = $dualLimiter->attemptDual(
    ipKey: '192.0.2.1', ipMax: 2, ipDecay: 60,
    accountKey: 'victim2@corp.internal', accountMax: 2, accountDecay: 60
);
assert_true("4.4 IP agotada bloquea petición aun si la cuenta es distinta (violated_bucket='ip')", $d4['allowed'] === false && $d4['violated_bucket'] === 'ip');

// Caso 4.5: Cuenta bloqueada desde IP fresca (ataque credential stuffing distribuido con rotación de IP)
$d5 = $dualLimiter->attemptDual(
    ipKey: '198.51.100.99', ipMax: 10, ipDecay: 60,
    accountKey: 'admin@corp.internal', accountMax: 2, accountDecay: 60
);
assert_true("4.5 Cuenta agotada bloquea petición aun si la IP es fresca (violated_bucket='account')", $d5['allowed'] === false && $d5['violated_bucket'] === 'account');


// --- 5. Política Fail-Closed y Resiliencia ante Errores de BD ---
echo "\n--- 5. Política Fail-Closed ante Caída de Base de Datos ---\n";

$brokenPdo = new PDO('sqlite::memory:');
// No creamos tabla para forzar error en ejecución
$failClosedLimiter = new PdoRateLimiter($brokenPdo, 'non_existent_table', failClosed: true);

$failClosedExceptionCaught = false;
try {
    $failClosedLimiter->hit('ip_test', 5, 60);
} catch (RateLimitExceededException $e) {
    $failClosedExceptionCaught = str_contains($e->getMessage(), 'Fail-Closed');
}
assert_true("5.1 Modo Fail-Closed: Error de BD bloquea acceso lanzando RateLimitExceededException", $failClosedExceptionCaught);

$failClosedCheckBlocked = $failClosedLimiter->check('ip_test', 5);
assert_true("5.2 Modo Fail-Closed: check() retorna false por defecto ante error de BD", $failClosedCheckBlocked === false);

$failOpenLimiter = new PdoRateLimiter($brokenPdo, 'non_existent_table', failClosed: false);
$failOpenResult = $failOpenLimiter->hit('ip_test', 5, 60);
assert_true("5.3 Modo Fail-Open (opcional): Error de BD retorna allowed=true si está explícitamente configurado", $failOpenResult['allowed'] === true);


// --- Resumen ---
echo "\n=================================================================\n";
echo "RESUMEN DE PRUEBAS HARNESS RATE LIMITER:\n";
echo "  - Total Ejecutadas:  {$testsTotal}\n";
echo "  - Superadas (PASS):  {$testsPassed}\n";
echo "  - Fallidas (FAIL):   {$testsFailed}\n";
echo "=================================================================\n";

if ($testsFailed === 0 && $testsPassed > 0) {
    echo "ESTADO: BLOQUE 3 (RATE LIMITER) - TODAS LAS PRUEBAS SUPERADAS (EXIT 0)\n\n";
    exit(0);
} else {
    echo "ESTADO: BLOQUE 3 (RATE LIMITER) - EXISTEN PRUEBAS FALLIDAS (EXIT 1)\n\n";
    exit(1);
}
