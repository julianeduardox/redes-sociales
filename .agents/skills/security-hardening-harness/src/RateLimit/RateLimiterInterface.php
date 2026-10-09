<?php
declare(strict_types=1);

namespace Harness\RateLimit;

/**
 * Interfaz para limitadores de tasa (Rate Limiters) atómicos y Fail-Closed.
 */
interface RateLimiterInterface
{
    /**
     * Consume un intento para una clave dentro de una ventana de tiempo dada.
     *
     * @param string $key Identificador único (ej: IP, cuenta, acción)
     * @param int $maxAttempts Límite máximo de intentos permitidos en la ventana
     * @param int $decaySeconds Duración de la ventana temporal en segundos
     * @return array{
     *     allowed: bool,
     *     attempts: int,
     *     remaining: int,
     *     retry_after: int,
     *     reset_at: int
     * }
     */
    public function hit(string $key, int $maxAttempts, int $decaySeconds): array;

    /**
     * Comprueba si una clave tiene intentos disponibles sin incrementar el contador.
     */
    public function check(string $key, int $maxAttempts): bool;

    /**
     * Reinicia el contador para una clave específica (ej: tras login exitoso).
     */
    public function clear(string $key): void;
}
