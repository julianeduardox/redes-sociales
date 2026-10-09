<?php
declare(strict_types=1);

/**
 * ==============================================================================
 * RUNNER UNIFICADO DE SEGURIDAD & HARDENING: HARNESS + XINDRO COPILOT (VERSIONADO)
 * ==============================================================================
 * Ejecuta todas las suites unitarias del Security Hardening Harness (Bloques 1 al 4)
 * y la suite de verificación integral de XINDRO.
 *
 * Ubicación versionada: scripts/run_unified_security_tests.php
 * Ejecución vía CLI:
 *   php scripts/run_unified_security_tests.php
 */

if (php_sapi_name() !== 'cli' && !defined('STDIN')) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Acceso denegado: este script solo puede ejecutarse vía CLI.']);
    exit(1);
}

$phpBinary = PHP_BINARY;
if (empty($phpBinary) || !file_exists($phpBinary)) {
    $phpBinary = 'c:\\xampp\\php\\php.exe';
}

$rootDir = realpath(__DIR__ . '/..');
$harnessTestsDir = $rootDir . '/.agents/skills/security-hardening-harness/tests';

$testSuites = [
    [
        'id' => 'HARNESS_B1',
        'name' => 'Harness Bloque 1: Cifrado Autenticado (AES-256-GCM)',
        'path' => $harnessTestsDir . '/test_crypto.php',
    ],
    [
        'id' => 'HARNESS_B2',
        'name' => 'Harness Bloque 2: Anti-SSRF & Transporte Seguro (SafeHttpClient)',
        'path' => $harnessTestsDir . '/test_safe_http.php',
    ],
    [
        'id' => 'HARNESS_B3A',
        'name' => 'Harness Bloque 3A: Rate Limiting Atómico (PdoRateLimiter)',
        'path' => $harnessTestsDir . '/test_ratelimit.php',
    ],
    [
        'id' => 'HARNESS_B3B',
        'name' => 'Harness Bloque 3B: Webhook Verifier (Mercado Pago)',
        'path' => $harnessTestsDir . '/test_webhook_mp.php',
    ],
    [
        'id' => 'HARNESS_B4',
        'name' => 'Harness Bloque 4: CORS Zero-Trust & Anti Host-Poisoning (CorsGuard / TrustedHostGuard)',
        'path' => $harnessTestsDir . '/test_cors_host.php',
    ],
    [
        'id' => 'XINDRO_SUITE',
        'name' => 'XINDRO Copilot: Suite de Seguridad & Hardening Integral (43 Tests)',
        'path' => $rootDir . '/scratch/verify_security_suite.php',
    ],
];

echo "================================================================================\n";
echo "       RUNNER UNIFICADO DE SEGURIDAD & HARDENING HARNESS (PHP " . PHP_VERSION . ")\n";
echo "================================================================================\n\n";

$results = [];
$totalSuitesPassed = 0;
$totalSuitesFailed = 0;
$overallExitCode = 0;
$suiteTimeoutSeconds = 30;

/**
 * Execute a test suite with a hard timeout so a blocked child cannot prevent
 * the rest of the security verification from completing.
 *
 * @return array{output: string, exit_code: int, timed_out: bool}
 */
function runSuiteProcess(string $phpBinary, string $path, int $timeoutSeconds): array {
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $pipes = [];
    $process = proc_open([$phpBinary, $path], $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        return ['output' => 'No se pudo iniciar el proceso de prueba.', 'exit_code' => 127, 'timed_out' => false];
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $output = '';
    $startedAt = microtime(true);
    $timedOut = false;

    while (true) {
        $output .= stream_get_contents($pipes[1]);
        $output .= stream_get_contents($pipes[2]);
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if ((microtime(true) - $startedAt) >= $timeoutSeconds) {
            $timedOut = true;
            if (strncasecmp(PHP_OS, 'WIN', 3) === 0 && !empty($status['pid'])) {
                @exec('taskkill /F /T /PID ' . (int)$status['pid'] . ' 2>&1');
            }
            @proc_terminate($process);
            break;
        }
        usleep(50000);
    }

    $output .= stream_get_contents($pipes[1]);
    $output .= stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($timedOut) {
        $exitCode = 124;
        $output .= "\n[RUNNER] La suite excedió el límite de {$timeoutSeconds} segundos.\n";
    }

    return ['output' => $output, 'exit_code' => $exitCode, 'timed_out' => $timedOut];
}

foreach ($testSuites as $suite) {
    echo ">> Ejecutando: {$suite['name']}...\n";

    if (!file_exists($suite['path'])) {
        echo "   [ERROR] Archivo no encontrado: {$suite['path']}\n\n";
        $results[] = [
            'name' => $suite['name'],
            'status' => 'FAIL',
            'exit_code' => 127,
            'summary' => 'Archivo no encontrado'
        ];
        $totalSuitesFailed++;
        $overallExitCode = 1;
        continue;
    }

    $execution = runSuiteProcess($phpBinary, $suite['path'], $suiteTimeoutSeconds);
    $output = $execution['output'];
    $outputLines = preg_split('/\R/', $output) ?: [];
    $exitCode = $execution['exit_code'];

    $isPass = ($exitCode === 0);

    // Extraer conteo de pruebas aprobadas si existe en la salida
    $summaryNote = '';
    if (preg_match('/(\d+)\s+de\s+(\d+)\s+PRUEBAS SUPERADAS/i', $output, $m)) {
        $summaryNote = "{$m[1]}/{$m[2]} aprobadas";
    } elseif (preg_match('/Superadas\s*\(PASS\):\s*(\d+)/i', $output, $m)) {
        $summaryNote = "{$m[1]} pruebas superadas";
    } elseif (preg_match('/RESUMEN FINAL:\s*(\d+)\/(\d+)/i', $output, $m)) {
        $summaryNote = "{$m[1]}/{$m[2]} pruebas superadas";
    }

    if ($isPass) {
        $totalSuitesPassed++;
        echo "   [PASS] Completado con éxito" . ($summaryNote ? " ({$summaryNote})" : "") . "\n\n";
        $results[] = [
            'name' => $suite['name'],
            'status' => 'PASS',
            'exit_code' => 0,
            'summary' => $summaryNote
        ];
    } else {
        $totalSuitesFailed++;
        $overallExitCode = 1;
        $failureReason = !empty($execution['timed_out'])
            ? "Excedió el límite de {$suiteTimeoutSeconds} segundos"
            : "Falló con código de salida: {$exitCode}";
        echo "   [FAIL] {$failureReason}\n";
        echo "   --- Salida parcial del error ---\n";
        $tail = array_slice($outputLines, -10);
        foreach ($tail as $line) {
            echo "   | " . $line . "\n";
        }
        echo "   -------------------------------\n\n";
        $results[] = [
            'name' => $suite['name'],
            'status' => 'FAIL',
            'exit_code' => $exitCode,
            'summary' => $summaryNote
        ];
    }
}

echo "================================================================================\n";
echo "                      RESUMEN DE RESULTADOS UNIFICADO                           \n";
echo "================================================================================\n";

printf("%-60s | %-8s | %-15s\n", "SUITE DE PRUEBAS", "ESTADO", "DETALLE");
echo str_repeat("-", 88) . "\n";

foreach ($results as $r) {
    printf("%-60s | %-8s | %-15s\n", mb_substr($r['name'], 0, 60), $r['status'], $r['summary'] ?? '');
}

echo str_repeat("=", 88) . "\n";
echo "Suites Ejecutadas: " . count($testSuites) . " | Superadas: {$totalSuitesPassed} | Fallidas: {$totalSuitesFailed}\n";

if ($overallExitCode === 0) {
    echo "ESTADO FINAL: 100% PASS - HARNESS Y XINDRO TOTALMENTE VERIFICADOS Y BLINDADOS\n\n";
    exit(0);
} else {
    echo "ESTADO FINAL: HUBO ERRORES EN UNA O MÁS SUITES\n\n";
    exit(1);
}
