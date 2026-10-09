<?php
declare(strict_types=1);

namespace Harness\Webhook;

/**
 * Interfaz común para verificadores de webhooks criptográficos (Fail-Closed).
 */
interface WebhookVerifierInterface
{
    /**
     * Valida de forma estricta la autenticidad e integridad del webhook.
     *
     * @param string $payload Cuerpo crudo (raw) de la petición HTTP
     * @param array<string, string> $headers Cabeceras HTTP recibidas
     * @param array<string, mixed> $queryParams Parámetros GET recibidos
     * @return bool True únicamente si la firma y los metadatos son 100% legítimos y no expirados
     * @throws WebhookVerificationException Si la verificación falla (Fail-Closed)
     */
    public function verify(string $payload, array $headers, array $queryParams = []): bool;
}
