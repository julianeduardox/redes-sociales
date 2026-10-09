<?php
declare(strict_types=1);

namespace Harness\RateLimit;

/**
 * Excepción lanzada ante violaciones de cuota o fallos operativos en el Rate Limiter.
 */
class RateLimitExceededException extends \RuntimeException
{
    private int $retryAfter;

    public function __construct(string $message = 'Demasiadas solicitudes. Límite de tasa excedido.', int $retryAfter = 60, int $code = 429, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->retryAfter = max(1, $retryAfter);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
