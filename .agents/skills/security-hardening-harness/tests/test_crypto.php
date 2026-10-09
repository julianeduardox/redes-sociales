<?php
declare(strict_types=1);

/**
 * Suite de Pruebas Unitarias del Módulo Criptográfico (Harness\Crypto)
 * Ejecutable vía CLI: php tests/test_crypto.php
 */

require_once __DIR__ . '/../src/Crypto/DecryptionFailedException.php';
require_once __DIR__ . '/../src/Crypto/EncryptorInterface.php';
require_once __DIR__ . '/../src/Crypto/AesGcmEncryptor.php';

use Harness\Crypto\AesGcmEncryptor;
use Harness\Crypto\DecryptionFailedException;

$testsTotal = 0;
$testsPassed = 0;

function assert_true(string $description, bool $condition, string $details = ''): void {
    global $testsTotal, $testsPassed;
    $testsTotal++;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$description}\n";
    } else {
        echo "  [FAIL] {$description}" . ($details !== '' ? " - Detalle: {$details}" : '') . "\n";
    }
}

echo "=================================================================\n";
echo "   SUITE DE PRUEBAS UNITARIAS: HARNESS CRYPTO (AES-256-GCM)     \n";
echo "=================================================================\n\n";

// --- 1. Claves Binarias y Validación de Longitud (256 bits) ---
echo "--- 1. Claves Binarias y Validación de Longitud (256 bits) ---\n";

$validHexKey = AesGcmEncryptor::generateKeyHex();
assert_true("1.1 Generación de clave CSPRNG tiene longitud exacta de 64 caracteres hex", strlen($validHexKey) === 64 && ctype_xdigit($validHexKey));

$encryptor = new AesGcmEncryptor($validHexKey, 'key-2026');
assert_true("1.2 Instanciación exitosa con clave hexadecimal válida", $encryptor instanceof AesGcmEncryptor);

// 1.3 Clave binaria con bytes que trim() eliminaría (\x20, \x00, \t, \n)
$trickyBinaryKey = " \0" . random_bytes(28) . "\t\n";
assert_true("1.3 Clave binaria de prueba mide exactamente 32 bytes", strlen($trickyBinaryKey) === 32);

// Comprobación de que la normalización conserva idéntica la clave binaria (cero trim)
$normalizedBinaryKey = AesGcmEncryptor::normalizeKey($trickyBinaryKey);
assert_true("1.4 normalizeKey() preserva exactamente la clave binaria byte a byte sin alterarla con trim()", $normalizedBinaryKey === $trickyBinaryKey);

$encTricky = new AesGcmEncryptor($trickyBinaryKey, 'binary-edge');
$secretData = 'Datos ultra-sensibles con clave binaria con espacios y nulos';
$trickyCipher = $encTricky->encrypt($secretData);
$trickyPlain = $encTricky->decrypt($trickyCipher);
assert_true("1.5 Clave binaria con bytes de control cifra y descifra con roundtrip exacto", $trickyPlain === $secretData);

$shortKeyBlocked = false;
try {
    new AesGcmEncryptor('clave_de_16_byte', 'k1');
} catch (\InvalidArgumentException $e) {
    $shortKeyBlocked = true;
}
assert_true("1.6 Clave sub-dimensionada (16 bytes) es rechazada con InvalidArgumentException", $shortKeyBlocked);

$emptyKeyBlocked = false;
try {
    new AesGcmEncryptor('', 'k1');
} catch (\InvalidArgumentException $e) {
    $emptyKeyBlocked = true;
}
assert_true("1.7 Clave vacía es rechazada con InvalidArgumentException", $emptyKeyBlocked);

$invalidHexBlocked = false;
try {
    // 64 caracteres pero con caracteres no hexadecimales (ZZZZ...)
    new AesGcmEncryptor(str_repeat('Z', 64), 'k1');
} catch (\InvalidArgumentException $e) {
    $invalidHexBlocked = true;
}
assert_true("1.8 Clave de 64 caracteres no hexadecimales es rechazada con InvalidArgumentException", $invalidHexBlocked);


// --- 2. Validación de Formato de key_id con Delimitadores Absolutos (\A y \z) ---
echo "\n--- 2. Formato de key_id y Prevención de Conflictos en Rotación ---\n";

// Probar saltos de línea (\n, \r\n, \r), bytes nulos reales ("key\0null") y espacios
$invalidKeyIds = [
    "key\n",
    "key\r\n",
    "key\r",
    "key\0null",
    " key",
    "key with space",
    "key:with:colons",
    ""
];

$allInvalidBlocked = true;
foreach ($invalidKeyIds as $kid) {
    try {
        new AesGcmEncryptor($validHexKey, $kid);
        $allInvalidBlocked = false;
        echo "    [ERROR] No se rechazó key_id malformado: " . addcslashes($kid, "\0..\37") . "\n";
    } catch (\InvalidArgumentException $e) {
        // Correcto
    }
}
assert_true("2.1 key_id con saltos de línea finales (\\n, \\r), bytes nulos (\\0) o ':' es rechazado (\A...\z)", $allInvalidBlocked);

$conflictCaught = false;
try {
    // previousKeys contiene el mismo key_id que la clave activa
    new AesGcmEncryptor($validHexKey, 'active-k1', [
        'active-k1' => AesGcmEncryptor::generateKeyHex()
    ]);
} catch (\InvalidArgumentException $e) {
    $conflictCaught = true;
}
assert_true("2.2 previousKeys con key_id idéntico al activo es rechazado (evita sobrescritura)", $conflictCaught);

$dynamicPrev = [];
$dynamicPrev['old-k1'] = AesGcmEncryptor::generateKeyHex();
$encWithPrev = new AesGcmEncryptor($validHexKey, 'active-k1', $dynamicPrev);
assert_true("2.3 Configuración de clave activa y claves previas independientes permitida", $encWithPrev instanceof AesGcmEncryptor);


// --- 3. Cifrado, Descifrado y Manejo de Cadenas Vacías ---
echo "\n--- 3. Cifrado Roundtrip y Autenticación de Cadenas Vacías ---\n";

// 3.1 Mensaje vacío legítimo DEBE cifrarse con IV y Tag
$emptyCipher = $encryptor->encrypt('');
assert_true("3.1 Cifrado de string vacío genera payload con IV, Tag y AAD (no string vacío)", str_starts_with($emptyCipher, 'enc:v1:key-2026:') && strlen($emptyCipher) > 20);

$emptyDecrypted = $encryptor->decrypt($emptyCipher);
assert_true("3.2 Descifrado de payload vacío autenticado devuelve string vacío ''", $emptyDecrypted === '');

// 3.3 decrypt('') no debe devolver vacío sin autenticar
$plainEmptyBlocked = false;
try {
    $encryptor->decrypt('');
} catch (DecryptionFailedException $e) {
    $plainEmptyBlocked = true;
}
assert_true("3.3 decrypt('') sin formato lanza DecryptionFailedException (Fail-Closed)", $plainEmptyBlocked);

// 3.4 Roundtrip en datos variados
$messages = [
    'Texto simple en ASCII',
    'Texto UTF-8: Ñandú con café y emojis 🛡️🔐',
    json_encode(['token' => 'sk_live_1234567890abcdef', 'user_id' => 42]),
    str_repeat('A', 4096) // 4 KB
];
$allRoundtripsPass = true;
foreach ($messages as $msg) {
    $c = $encryptor->encrypt($msg);
    if ($encryptor->decrypt($c) !== $msg) {
        $allRoundtripsPass = false;
    }
}
assert_true("3.4 Roundtrip exacto en payloads variados (ASCII, UTF-8, JSON, 4KB)", $allRoundtripsPass);


// --- 4. Integridad Criptográfica, AAD, IV, Truncamiento y Clave Incorrecta ---
echo "\n--- 4. Detección de Manipulación, AAD, IV, Truncamiento y Clave Incorrecta ---\n";

$sampleCipher = $encryptor->encrypt("Token_Bancario_12345");
$parts = explode(':', $sampleCipher, 4);
$raw = base64_decode($parts[3]);

// 4.1 Manipulación de Tag (1 bit)
$tamperedTag = $raw;
$tamperedTag[14] = chr(ord($tamperedTag[14]) ^ 0xFF);
$cipherTamperedTag = "enc:v1:key-2026:" . base64_encode($tamperedTag);

$tagMismatch = false;
try {
    $encryptor->decrypt($cipherTamperedTag);
} catch (DecryptionFailedException $e) {
    $tagMismatch = true;
}
assert_true("4.1 Manipulación de 1 bit en Auth Tag lanza DecryptionFailedException", $tagMismatch);

// 4.2 Manipulación de Ciphertext (1 bit)
$tamperedCipher = $raw;
$tamperedCipher[strlen($tamperedCipher) - 1] = chr(ord($tamperedCipher[strlen($tamperedCipher) - 1]) ^ 0x01);
$cipherTamperedText = "enc:v1:key-2026:" . base64_encode($tamperedCipher);

$cipherMismatch = false;
try {
    $encryptor->decrypt($cipherTamperedText);
} catch (DecryptionFailedException $e) {
    $cipherMismatch = true;
}
assert_true("4.2 Manipulación de 1 bit en Ciphertext lanza DecryptionFailedException", $cipherMismatch);

// 4.3 Manipulación del IV (1 bit)
$tamperedIv = $raw;
$tamperedIv[0] = chr(ord($tamperedIv[0]) ^ 0x01);
$cipherTamperedIv = "enc:v1:key-2026:" . base64_encode($tamperedIv);

$ivMismatch = false;
try {
    $encryptor->decrypt($cipherTamperedIv);
} catch (DecryptionFailedException $e) {
    $ivMismatch = true;
}
assert_true("4.3 Manipulación de 1 bit en el IV (12 bytes) lanza DecryptionFailedException", $ivMismatch);

// 4.4 Payload truncado (< 28 bytes)
$truncatedPayload = "enc:v1:key-2026:" . base64_encode(substr($raw, 0, 15));
$truncatedCaught = false;
try {
    $encryptor->decrypt($truncatedPayload);
} catch (DecryptionFailedException $e) {
    $truncatedCaught = true;
}
assert_true("4.4 Payload truncado (< 28 bytes) lanza DecryptionFailedException", $truncatedCaught);

// 4.5 Manipulación de AAD (cambiar key_id en prefijo para engañar al sistema)
$fakeKeyIdCipher = "enc:v1:other-key:" . $parts[3];
$aadTampered = false;
try {
    $multiEnc = new AesGcmEncryptor($validHexKey, 'key-2026', ['other-key' => $validHexKey]);
    $multiEnc->decrypt($fakeKeyIdCipher);
} catch (DecryptionFailedException $e) {
    $aadTampered = true;
}
assert_true("4.5 Modificación del key_id en el prefijo es detectada por AAD y lanza DecryptionFailedException", $aadTampered);

// 4.6 Descifrado con clave incorrecta
$wrongKeyEnc = new AesGcmEncryptor(AesGcmEncryptor::generateKeyHex(), 'key-2026');
$wrongKeyCaught = false;
try {
    $wrongKeyEnc->decrypt($sampleCipher);
} catch (DecryptionFailedException $e) {
    $wrongKeyCaught = true;
}
assert_true("4.6 Descifrado con clave diferente de 32 bytes falla autenticación (DecryptionFailedException)", $wrongKeyCaught);


// --- 5. Re-encriptación y Validación de Payloads Obsoletos ---
echo "\n--- 5. Re-encriptación Segura (reencryptIfOutdated) ---\n";

$oldKey = AesGcmEncryptor::generateKeyHex();
$newKey = AesGcmEncryptor::generateKeyHex();

$oldEnc = new AesGcmEncryptor($oldKey, 'v1-legacy');
$legacyCipher = $oldEnc->encrypt("Secreto en base de datos migrada");

$migratedEnc = new AesGcmEncryptor($newKey, 'v2-current', ['v1-legacy' => $oldKey]);

// 5.1 Re-encriptar payload válido migra de v1-legacy a v2-current
$migratedCipher = $migratedEnc->reencryptIfOutdated($legacyCipher);
assert_true("5.1 reencryptIfOutdated() migra payload v1 a formato v2-current", str_contains($migratedCipher, ':v2-current:'));
assert_true("5.2 Payload migrado descifra el texto original intacto", $migratedEnc->decrypt($migratedCipher) === "Secreto en base de datos migrada");

// 5.3 Payload que ya está en v2 no es alterado
$sameVersionCipher = $migratedEnc->reencryptIfOutdated($migratedCipher);
assert_true("5.3 reencryptIfOutdated() sobre payload que ya está en la versión activa lo devuelve intacto", $sameVersionCipher === $migratedCipher);

// 5.4 reencryptIfOutdated() con payload corrupto DEBE lanzar excepción (Fail-Closed)
$corruptPayload = "enc:v1:v2-current:not_a_valid_base64_string";
$corruptBlocked = false;
try {
    $migratedEnc->reencryptIfOutdated($corruptPayload);
} catch (DecryptionFailedException $e) {
    $corruptBlocked = true;
}
assert_true("5.4 reencryptIfOutdated() con payload corrupto lanza DecryptionFailedException (nunca retorna sin validar)", $corruptBlocked);


// --- Resumen ---
echo "\n=================================================================\n";
echo "RESULTADOS SUITE HARNESS CRYPTO: {$testsPassed} de {$testsTotal} PRUEBAS SUPERADAS\n";
echo "=================================================================\n";

if ($testsPassed === $testsTotal) {
    echo "ESTADO: BLOQUE 1 - PRUEBAS SUPERADAS EXITOSAMENTE (EXIT 0)\n\n";
    exit(0);
} else {
    echo "ESTADO: FALLARON PRUEBAS EN BLOQUE 1 (EXIT 1)\n\n";
    exit(1);
}
