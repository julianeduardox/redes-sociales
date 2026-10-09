<?php
declare(strict_types=1);

namespace Harness\RateLimit;

use PDO;
use PDOException;

/**
 * Limitador de tasa (Rate Limiter) respaldado por base de datos relacional (PDO).
 *
 * Características de seguridad:
 * 1. Concurrencia atómica (INSERT ... ON DUPLICATE KEY UPDATE en MySQL / ON CONFLICT en SQLite).
 * 2. Política Fail-Closed configurable (por defecto true): Si la BD falla, bloquea para prevenir abusos.
 * 3. Soporte de Dual-Bucket (IP + Cuenta) para mitigar credential stuffing distribuido y fuerza bruta dirigida.
 */
class PdoRateLimiter implements RateLimiterInterface
{
    private PDO $pdo;
    private string $tableName;
    private bool $failClosed;
    private string $driver;

    public function __construct(PDO $pdo, string $tableName = 'harness_rate_limits', bool $failClosed = true)
    {
        $this->pdo = $pdo;
        $this->tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
        $this->failClosed = $failClosed;
        $this->driver = (string)$this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Crea la tabla de almacenamiento si no existe aún.
     */
    public function ensureTableExists(): void
    {
        if ($this->driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                rate_key TEXT PRIMARY KEY,
                attempts INTEGER NOT NULL,
                reset_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            );";
        } else {
            // MySQL / MariaDB
            $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                rate_key VARCHAR(128) NOT NULL PRIMARY KEY,
                attempts INT UNSIGNED NOT NULL,
                reset_at INT UNSIGNED NOT NULL,
                updated_at INT UNSIGNED NOT NULL,
                INDEX idx_reset_at (reset_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        }

        $this->pdo->exec($sql);
    }

    /**
     * Consume un intento de forma atómica bajo la ventana de tiempo especificada.
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): array
    {
        $now = time();
        $resetAt = $now + max(1, $decaySeconds);

        try {
            if ($this->driver === 'sqlite') {
                $sql = "INSERT INTO {$this->tableName} (rate_key, attempts, reset_at, updated_at)
                        VALUES (:key, 1, :reset_at, :now)
                        ON CONFLICT(rate_key) DO UPDATE SET
                          attempts = CASE WHEN reset_at <= excluded.updated_at THEN 1 ELSE attempts + 1 END,
                          reset_at = CASE WHEN reset_at <= excluded.updated_at THEN excluded.reset_at ELSE reset_at END,
                          updated_at = excluded.updated_at;";
            } else {
                // MySQL / MariaDB
                $sql = "INSERT INTO {$this->tableName} (rate_key, attempts, reset_at, updated_at)
                        VALUES (:key, 1, :reset_at, :now)
                        ON DUPLICATE KEY UPDATE
                          attempts = IF(reset_at <= VALUES(updated_at), 1, attempts + 1),
                          reset_at = IF(reset_at <= VALUES(updated_at), VALUES(reset_at), reset_at),
                          updated_at = VALUES(updated_at);";
            }

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':key'      => $key,
                ':reset_at' => $resetAt,
                ':now'      => $now
            ]);

            // Leer estado consolidado de la clave
            $readStmt = $this->pdo->prepare("SELECT attempts, reset_at FROM {$this->tableName} WHERE rate_key = :key");
            $readStmt->execute([':key' => $key]);
            $row = $readStmt->fetch(PDO::FETCH_ASSOC);

            $currentAttempts = $row ? (int)$row['attempts'] : 1;
            $currentResetAt = $row ? (int)$row['reset_at'] : $resetAt;

            $allowed = $currentAttempts <= $maxAttempts;
            $remaining = max(0, $maxAttempts - $currentAttempts);
            $retryAfter = $allowed ? 0 : max(1, $currentResetAt - $now);

            return [
                'allowed'     => $allowed,
                'attempts'    => $currentAttempts,
                'remaining'   => $remaining,
                'retry_after' => $retryAfter,
                'reset_at'    => $currentResetAt
            ];
        } catch (PDOException $e) {
            if ($this->failClosed) {
                throw new RateLimitExceededException(
                    "Servicio de rate limiting no disponible (Fail-Closed: petición bloqueada): " . $e->getMessage(),
                    $decaySeconds,
                    429,
                    $e
                );
            }

            // Modo Fail-Open (no recomendado en módulos de seguridad estricta)
            return [
                'allowed'     => true,
                'attempts'    => 1,
                'remaining'   => max(0, $maxAttempts - 1),
                'retry_after' => 0,
                'reset_at'    => $resetAt
            ];
        }
    }

    /**
     * Comprueba si una clave tiene intentos disponibles sin consumir cuota.
     */
    public function check(string $key, int $maxAttempts): bool
    {
        $now = time();
        try {
            $stmt = $this->pdo->prepare("SELECT attempts, reset_at FROM {$this->tableName} WHERE rate_key = :key");
            $stmt->execute([':key' => $key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return true;
            }

            if ((int)$row['reset_at'] <= $now) {
                return true; // Ventana expirada
            }

            return (int)$row['attempts'] < $maxAttempts;
        } catch (PDOException $e) {
            if ($this->failClosed) {
                return false; // Denegar por defecto ante fallo
            }
            return true;
        }
    }

    /**
     * Reinicia el contador para una clave específica.
     */
    public function clear(string $key): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM {$this->tableName} WHERE rate_key = :key");
            $stmt->execute([':key' => $key]);
        } catch (PDOException $e) {
            if ($this->failClosed) {
                throw new RateLimitExceededException("Error al reiniciar rate limit para {$key}: " . $e->getMessage());
            }
        }
    }

    /**
     * Evaluación Dual-Bucket (IP + Cuenta): consume intentos en ambos buckets y bloquea si cualquiera excede.
     *
     * @return array{
     *     allowed: bool,
     *     ip_result: array,
     *     account_result: array,
     *     violated_bucket: ?string,
     *     retry_after: int
     * }
     */
    public function attemptDual(
        string $ipKey,
        int $ipMax,
        int $ipDecay,
        string $accountKey,
        int $accountMax,
        int $accountDecay
    ): array {
        $ipRes = $this->hit("ip:{$ipKey}", $ipMax, $ipDecay);
        $accRes = $this->hit("acc:{$accountKey}", $accountMax, $accountDecay);

        $allowed = $ipRes['allowed'] && $accRes['allowed'];
        $violatedBucket = null;
        $retryAfter = 0;

        if (!$ipRes['allowed'] && !$accRes['allowed']) {
            $violatedBucket = 'both';
            $retryAfter = max($ipRes['retry_after'], $accRes['retry_after']);
        } elseif (!$ipRes['allowed']) {
            $violatedBucket = 'ip';
            $retryAfter = $ipRes['retry_after'];
        } elseif (!$accRes['allowed']) {
            $violatedBucket = 'account';
            $retryAfter = $accRes['retry_after'];
        }

        return [
            'allowed'         => $allowed,
            'ip_result'       => $ipRes,
            'account_result'  => $accRes,
            'violated_bucket' => $violatedBucket,
            'retry_after'     => $retryAfter
        ];
    }
}
