<?php
declare(strict_types=1);

namespace Harness\Crypto;

/**
 * Cifrador Autenticado AES-256-GCM con soporte de rotación de claves,
 * autenticación de metadatos (AAD) y modo estricto Fail-Closed.
 *
 * Formato del payload: enc:<format_version>:<key_id>:<base64(iv(12) . tag(16) . ciphertext)>
 * AAD utilizado: "HarnessEnc:<format_version>:<key_id>"
 */
class AesGcmEncryptor implements EncryptorInterface
{
    private const ALGO = 'aes-256-gcm';
    private const FORMAT_VERSION = 'v1';
    private const IV_LEN = 12;
    private const TAG_LEN = 16;
    private const KEY_ID_REGEX = '/\A[a-zA-Z0-9_.-]{1,32}\z/';

    private string $activeKey;
    private string $activeKeyId;

    /**
     * @var array<string, string> Mapa de key_id => clave binaria estricta de 32 bytes
     */
    private array $allKeys = [];

    /**
     * @param string $activeKey Clave simétrica de 256 bits (32 bytes binarios directos o 64 hex)
     * @param string $activeKeyId Identificador de la clave activa (alfanumérico, sin espacios ni ':', máx 32 caracteres)
     * @param array<string, string> $previousKeys Mapa de [key_id => clave] para descifrado de registros históricos
     */
    public function __construct(string $activeKey, string $activeKeyId = 'default', array $previousKeys = [])
    {
        $this->activeKeyId = self::validateKeyId($activeKeyId);
        $this->activeKey = self::normalizeKey($activeKey);
        $this->allKeys[$this->activeKeyId] = $this->activeKey;

        foreach ($previousKeys as $keyId => $rawPrevKey) {
            $validId = self::validateKeyId((string)$keyId);
            if ($validId === $this->activeKeyId) {
                throw new \InvalidArgumentException(
                    "Conflicto de configuración: El key_id '{$validId}' en previousKeys " .
                    "sobrescribiría la clave activa. Cada versión de clave debe ser única."
                );
            }
            if (isset($this->allKeys[$validId])) {
                throw new \InvalidArgumentException(
                    "Conflicto de configuración: El key_id '{$validId}' está duplicado en previousKeys."
                );
            }
            $this->allKeys[$validId] = self::normalizeKey($rawPrevKey);
        }
    }

    /**
     * Valida que el identificador de clave cumpla con un formato restringido seguro.
     */
    public static function validateKeyId(string $keyId): string
    {
        if (!preg_match(self::KEY_ID_REGEX, $keyId)) {
            throw new \InvalidArgumentException(
                "Identificador de clave '{$keyId}' inválido. Debe contener entre 1 y 32 caracteres " .
                "alfanuméricos, puntos, guiones o guiones bajos (sin espacios ni ':')."
            );
        }
        return $keyId;
    }

    /**
     * Valida y normaliza la clave de 256 bits sin truncar bytes binarios.
     *
     * IMPORTANTE: No se aplica trim() a claves binarias para no alterar bytes de control legítimos.
     */
    public static function normalizeKey(string $rawKey): string
    {
        // 1. Si ya es una secuencia binaria exacta de 32 bytes, se retorna tal cual (CERO trim)
        if (strlen($rawKey) === 32) {
            return $rawKey;
        }

        // 2. Si es una representación hexadecimal en texto (64 caracteres hex ASCII)
        $trimmedHex = trim($rawKey);
        if (strlen($trimmedHex) === 64 && ctype_xdigit($trimmedHex)) {
            $bin = hex2bin($trimmedHex);
            if ($bin !== false && strlen($bin) === 32) {
                return $bin;
            }
        }

        throw new \InvalidArgumentException(
            'Clave criptográfica inválida: debe tener exactamente 256 bits ' .
            '(32 bytes binarios directos o 64 caracteres hexadecimales válidos).'
        );
    }

    /**
     * Genera una clave simétrica de 256 bits con CSPRNG en formato hexadecimal.
     */
    public static function generateKeyHex(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Genera el AAD (Additional Authenticated Data) para vincular criptográficamente
     * el formato y la versión de la clave al ciphertext.
     */
    private static function buildAad(string $formatVersion, string $keyId): string
    {
        return "HarnessEnc:{$formatVersion}:{$keyId}";
    }

    /**
     * Cifra texto plano con AES-256-GCM usando la clave activa y autenticando metadatos (AAD).
     */
    public function encrypt(string $plainText): string
    {
        $iv = random_bytes(self::IV_LEN);
        $tag = '';
        $aad = self::buildAad(self::FORMAT_VERSION, $this->activeKeyId);

        $cipher = openssl_encrypt(
            $plainText,
            self::ALGO,
            $this->activeKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad,
            self::TAG_LEN
        );

        if ($cipher === false) {
            throw new \RuntimeException('Error crítico del sistema al cifrar con AES-256-GCM.');
        }

        $packed = base64_encode($iv . $tag . $cipher);
        return 'enc:' . self::FORMAT_VERSION . ':' . $this->activeKeyId . ':' . $packed;
    }

    /**
     * Descifra un payload autenticado. Lanza DecryptionFailedException si falla la integridad,
     * el AAD, el tag, o si el formato es inválido.
     *
     * @throws DecryptionFailedException
     */
    public function decrypt(string $payload): string
    {
        if (!str_starts_with($payload, 'enc:')) {
            throw new DecryptionFailedException('El payload no cuenta con el prefijo de cifrado obligatorio (enc:).');
        }

        $parts = explode(':', $payload, 4);
        if (count($parts) !== 4) {
            throw new DecryptionFailedException('Formato de payload cifrado malformado (debe contener 4 segmentos).');
        }

        $formatVersion = $parts[1];
        $keyId = $parts[2];
        $b64 = $parts[3];

        if ($formatVersion !== self::FORMAT_VERSION) {
            throw new DecryptionFailedException(
                "Versión de formato de cifrado no soportada: '{$formatVersion}'."
            );
        }

        if (!isset($this->allKeys[$keyId])) {
            throw new DecryptionFailedException(
                "No existe clave registrada para el identificador '{$keyId}'. Imposible descifrar."
            );
        }

        $key = $this->allKeys[$keyId];
        $aad = self::buildAad($formatVersion, $keyId);

        $raw = base64_decode($b64, true);
        if ($raw === false || strlen($raw) < (self::IV_LEN + self::TAG_LEN)) {
            throw new DecryptionFailedException('Payload base64 corrupto, truncado o con longitud insuficiente.');
        }

        $iv = substr($raw, 0, self::IV_LEN);
        $tag = substr($raw, self::IV_LEN, self::TAG_LEN);
        $ciphertext = substr($raw, self::IV_LEN + self::TAG_LEN);

        $plain = openssl_decrypt(
            $ciphertext,
            self::ALGO,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            $aad
        );

        if ($plain === false) {
            throw new DecryptionFailedException(
                'Fallo de autenticación o integridad en AES-256-GCM: el tag no coincide, ' .
                'los metadatos (AAD) fueron manipulados o la clave es incorrecta.'
            );
        }

        return $plain;
    }

    /**
     * Re-cifra el payload con la clave activa más reciente únicamente si fue cifrado
     * con una clave anterior.
     *
     * IMPORTANTE: Valida y autentica rigurosamente el payload antes de cualquier operación.
     * Si el payload está corrupto, malformado o no autenticado, lanza DecryptionFailedException.
     * Esta función solo transforma la cadena en memoria; la persistencia atómica en BD
     * debe gestionarse en la capa de almacenamiento.
     *
     * @throws DecryptionFailedException
     */
    public function reencryptIfOutdated(string $payload): string
    {
        // 1. Validar y autenticar siempre el payload original (Fail-Closed)
        $plain = $this->decrypt($payload);

        // 2. Extraer el key_id autenticado
        $parts = explode(':', $payload, 4);
        $keyId = $parts[2];

        // 3. Si ya pertenece a la clave activa actual, retornar el payload validado sin alteraciones
        if ($keyId === $this->activeKeyId) {
            return $payload;
        }

        // 4. Si proviene de una clave previa, re-cifrar con la clave activa
        return $this->encrypt($plain);
    }
}
