<?php
declare(strict_types=1);

/**
 * Suite de Pruebas Unitarias del Verificador de Webhooks Mercado Pago (Harness\Webhook)
 * Ejecutable vía CLI: php tests/test_webhook_mp.php
 */

require_once __DIR__ . '/../src/Webhook/WebhookVerifierInterface.php';
require_once __DIR__ . '/../src/Webhook/WebhookVerificationException.php';
require_once __DIR__ . '/../src/Webhook/MercadoPagoWebhookVerifier.php';

use Harness\Webhook\MercadoPagoWebhookVerifier;
use Harness\Webhook\WebhookVerificationException;

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
echo "   SUITE DE PRUEBAS UNITARIAS: HARNESS WEBHOOK MERCADO PAGO      \n";
echo "=================================================================\n\n";

$secret = 'test_secret_key_mercado_pago_987654321';
$verifier = new MercadoPagoWebhookVerifier($secret, toleranceSeconds: 300);

// Helper para calcular firma legítima según manifest oficial:
// manifest = "id:[data.id];request-id:[x-request-id];ts:[ts];"
function generate_mp_headers(string $secret, string $dataId, string $requestId, int $ts): array {
    $manifest = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
    $v1 = hash_hmac('sha256', $manifest, $secret);
    return [
        'x-request-id' => $requestId,
        'x-signature'  => "ts={$ts},v1={$v1}"
    ];
}

$now = time();
$dataId = '9988776655';
$requestId = 'req_uuid_12345678-abcd';
$validHeaders = generate_mp_headers($secret, $dataId, $requestId, $now);
$validPayload = json_encode(['action' => 'payment.created', 'data' => ['id' => $dataId]]);


// --- 1. Verificación Exitosa de Firma Legítima ---
echo "--- 1. Verificación de Notificaciones Legítimas ---\n";

$okQuery = $verifier->verify($validPayload, $validHeaders, ['data.id' => $dataId]);
assert_true("1.1 Webhook legítimo con data.id en query params es verificado exitosamente", $okQuery === true);

$okJson = $verifier->verify($validPayload, $validHeaders, []);
assert_true("1.2 Webhook legítimo extrayendo data.id del payload JSON es verificado exitosamente", $okJson === true);

$msTs = $now * 1000; // Milisegundos
$msHeaders = generate_mp_headers($secret, $dataId, $requestId, $msTs);
$okMs = $verifier->verify($validPayload, $msHeaders, ['data.id' => $dataId]);
assert_true("1.3 Timestamp en milisegundos es normalizado y verificado correctamente", $okMs === true);


// --- 2. Detección de Manipulación de Parámetros y Firmas Inválidas ---
echo "\n--- 2. Detección de Manipulación (Integridad y Autenticidad) ---\n";

// 2.1 data.id manipulado
$tamperedIdCaught = false;
try {
    $verifier->verify($validPayload, $validHeaders, ['data.id' => 'TAMPERED_ID']);
} catch (WebhookVerificationException $e) {
    $tamperedIdCaught = str_contains($e->getMessage(), 'Firma del webhook inválida');
}
assert_true("2.1 Manipulación de data.id es detectada y abortada", $tamperedIdCaught);

// 2.2 x-request-id manipulado
$tamperedReqIdHeaders = $validHeaders;
$tamperedReqIdHeaders['x-request-id'] = 'tampered-request-id';
$tamperedReqIdCaught = false;
try {
    $verifier->verify($validPayload, $tamperedReqIdHeaders, ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $tamperedReqIdCaught = str_contains($e->getMessage(), 'Firma del webhook inválida');
}
assert_true("2.2 Manipulación de x-request-id es detectada y abortada", $tamperedReqIdCaught);

// 2.3 Firma HMAC modificada (1 byte)
$tamperedSigHeaders = $validHeaders;
$tamperedSigHeaders['x-signature'] = "ts={$now},v1=deadbeef1234567890abcdef1234567890abcdef1234567890abcdef12345678";
$tamperedSigCaught = false;
try {
    $verifier->verify($validPayload, $tamperedSigHeaders, ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $tamperedSigCaught = str_contains($e->getMessage(), 'Firma del webhook inválida');
}
assert_true("2.3 Firma v1 falsa/adulterada es detectada y abortada con hash_equals()", $tamperedSigCaught);

// 2.4 Clave secreta diferente
$wrongVerifier = new MercadoPagoWebhookVerifier('different_secret_key');
$wrongSecretCaught = false;
try {
    $wrongVerifier->verify($validPayload, $validHeaders, ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $wrongSecretCaught = str_contains($e->getMessage(), 'Firma del webhook inválida');
}
assert_true("2.4 Verificación con clave secreta errónea es rechazada", $wrongSecretCaught);


// --- 3. Prevención de Ataques de Replay (Ventana Temporal) ---
echo "\n--- 3. Prevención de Ataques de Replay ---\n";

// Timestamp de hace 15 minutos (fuera de tolerancia de 300s = 5m)
$oldTs = $now - 900;
$oldHeaders = generate_mp_headers($secret, $dataId, $requestId, $oldTs);
$replayOldCaught = false;
try {
    $verifier->verify($validPayload, $oldHeaders, ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $replayOldCaught = str_contains($e->getMessage(), 'Webhook expirado');
}
assert_true("3.1 Webhook con timestamp de hace 15 minutos es rechazado por expiración", $replayOldCaught);

// Timestamp en el futuro lejano (+10 minutos)
$futureTs = $now + 600;
$futureHeaders = generate_mp_headers($secret, $dataId, $requestId, $futureTs);
$replayFutureCaught = false;
try {
    $verifier->verify($validPayload, $futureHeaders, ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $replayFutureCaught = str_contains($e->getMessage(), 'Webhook expirado');
}
assert_true("3.2 Webhook con timestamp en el futuro lejano (+10m) es rechazado por expiración", $replayFutureCaught);


// --- 4. Política Fail-Closed ante Metadatos Faltantes o Malformados ---
echo "\n--- 4. Política Fail-Closed ante Cabeceras Ausentes o Malformadas ---\n";

// 4.1 Falta x-signature
$noSigCaught = false;
try {
    $verifier->verify($validPayload, ['x-request-id' => $requestId], ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $noSigCaught = str_contains($e->getMessage(), "x-signature");
}
assert_true("4.1 Ausencia de cabecera x-signature lanza WebhookVerificationException", $noSigCaught);

// 4.2 Falta x-request-id
$noReqIdCaught = false;
try {
    $verifier->verify($validPayload, ['x-signature' => $validHeaders['x-signature']], ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $noReqIdCaught = str_contains($e->getMessage(), "x-request-id");
}
assert_true("4.2 Ausencia de cabecera x-request-id lanza WebhookVerificationException", $noReqIdCaught);

// 4.3 Componente ts faltante en x-signature
$noTsCaught = false;
try {
    $verifier->verify($validPayload, ['x-signature' => 'v1=abc123', 'x-request-id' => $requestId], ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $noTsCaught = str_contains($e->getMessage(), "ts");
}
assert_true("4.3 Componente 'ts' ausente en x-signature lanza WebhookVerificationException", $noTsCaught);

// 4.4 Componente v1 faltante en x-signature
$noV1Caught = false;
try {
    $verifier->verify($validPayload, ['x-signature' => "ts={$now}", 'x-request-id' => $requestId], ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $noV1Caught = str_contains($e->getMessage(), "v1");
}
assert_true("4.4 Componente 'v1' ausente en x-signature lanza WebhookVerificationException", $noV1Caught);

// 4.5 Timestamp con caracteres no numéricos
$badTsCaught = false;
try {
    $verifier->verify($validPayload, ['x-signature' => "ts=abc123nonnumeric,v1=test", 'x-request-id' => $requestId], ['data.id' => $dataId]);
} catch (WebhookVerificationException $e) {
    $badTsCaught = str_contains($e->getMessage(), "Formato no numérico");
}
assert_true("4.5 Timestamp 'ts' no numérico lanza WebhookVerificationException", $badTsCaught);

// 4.6 Imposible extraer data.id
$noDataIdCaught = false;
try {
    $verifier->verify('{}', $validHeaders, []);
} catch (WebhookVerificationException $e) {
    $noDataIdCaught = str_contains($e->getMessage(), "data.id");
}
assert_true("4.6 Ausencia absoluta de data.id lanza WebhookVerificationException", $noDataIdCaught);

// 4.7 Constructor con clave secreta vacía
$emptySecretCaught = false;
try {
    new MercadoPagoWebhookVerifier('   ');
} catch (\InvalidArgumentException $e) {
    $emptySecretCaught = true;
}
assert_true("4.7 Clave secreta vacía o solo espacios es rechazada en el constructor", $emptySecretCaught);


// --- Resumen ---
echo "\n=================================================================\n";
echo "RESUMEN DE PRUEBAS HARNESS WEBHOOK MERCADO PAGO:\n";
echo "  - Total Ejecutadas:  {$testsTotal}\n";
echo "  - Superadas (PASS):  {$testsPassed}\n";
echo "  - Fallidas (FAIL):   {$testsFailed}\n";
echo "=================================================================\n";

if ($testsFailed === 0 && $testsPassed > 0) {
    echo "ESTADO: BLOQUE 3 (MERCADO PAGO WEBHOOK) - TODAS LAS PRUEBAS SUPERADAS (EXIT 0)\n\n";
    exit(0);
} else {
    echo "ESTADO: BLOQUE 3 (MERCADO PAGO WEBHOOK) - EXISTEN PRUEBAS FALLIDAS (EXIT 1)\n\n";
    exit(1);
}
