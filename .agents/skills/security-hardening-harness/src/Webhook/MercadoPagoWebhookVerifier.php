<?php
declare(strict_types=1);

namespace Harness\Webhook;

/**
 * Verificador criptográfico específico para Webhooks de Mercado Pago (v1).
 *
 * Implementa la especificación oficial de Mercado Pago para validación de origen:
 * 1. Extracción de 'ts' y 'v1' desde la cabecera 'x-signature'.
 * 2. Extracción de 'x-request-id'.
 * 3. Extracción de 'data.id' desde query params o body JSON.
 * 4. Ensamblado del manifest canónico: "id:[data.id];request-id:[x-request-id];ts:[ts];"
 * 5. Comparación HMAC-SHA256 en tiempo constante con hash_equals().
 * 6. Validación de ventana temporal anti-replay.
 * 7. Política Fail-Closed: ante cualquier anomalía lanza WebhookVerificationException.
 */
class MercadoPagoWebhookVerifier implements WebhookVerifierInterface
{
    private string $secretKey;
    private int $toleranceSeconds;

    public function __construct(string $secretKey, int $toleranceSeconds = 300)
    {
        if (empty(trim($secretKey))) {
            throw new \InvalidArgumentException('La clave secreta del webhook de Mercado Pago no puede estar vacía.');
        }

        $this->secretKey = $secretKey;
        $this->toleranceSeconds = max(5, $toleranceSeconds);
    }

    /**
     * Valida de forma estricta la notificación del webhook.
     *
     * @param string $payload Cuerpo crudo HTTP
     * @param array<string, string> $headers Cabeceras HTTP
     * @param array<string, mixed> $queryParams Parámetros de consulta
     * @return bool True únicamente si la firma es válida
     * @throws WebhookVerificationException
     */
    public function verify(string $payload, array $headers, array $queryParams = []): bool
    {
        // 1. Normalizar cabeceras a minúsculas
        $normalizedHeaders = [];
        foreach ($headers as $k => $v) {
            $normalizedHeaders[strtolower(trim((string)$k))] = trim((string)$v);
        }

        // 2. Extraer x-signature y x-request-id
        if (empty($normalizedHeaders['x-signature'])) {
            throw new WebhookVerificationException("Falta la cabecera obligatoria 'x-signature'.");
        }
        if (empty($normalizedHeaders['x-request-id'])) {
            throw new WebhookVerificationException("Falta la cabecera obligatoria 'x-request-id'.");
        }

        $xSignature = $normalizedHeaders['x-signature'];
        $xRequestId = $normalizedHeaders['x-request-id'];

        // 3. Parsear componentes ts y v1 de x-signature
        $signatureParts = $this->parseSignatureHeader($xSignature);
        if (empty($signatureParts['ts'])) {
            throw new WebhookVerificationException("Componente 'ts' ausente o vacío en 'x-signature'.");
        }
        if (empty($signatureParts['v1'])) {
            throw new WebhookVerificationException("Componente 'v1' ausente o vacío en 'x-signature'.");
        }

        $ts = $signatureParts['ts'];
        $receivedV1 = $signatureParts['v1'];

        // 4. Validar formato numérico de ts
        if (!ctype_digit($ts)) {
            throw new WebhookVerificationException("Formato no numérico inválido en timestamp 'ts'.");
        }

        $tsInt = (int)$ts;
        // Mercado Pago puede enviar ts en segundos o milisegundos; normalizar si supera 10^11
        $tsSeconds = ($tsInt > 100000000000) ? (int)floor($tsInt / 1000) : $tsInt;

        $now = time();
        if (abs($now - $tsSeconds) > $this->toleranceSeconds) {
            throw new WebhookVerificationException(
                "Webhook expirado o fuera de la ventana de tolerancia ({$this->toleranceSeconds}s). Diferencia: " . abs($now - $tsSeconds) . "s."
            );
        }

        // 5. Extraer el identificador del recurso (data.id)
        $dataId = $this->extractDataId($payload, $queryParams);
        if (empty($dataId)) {
            throw new WebhookVerificationException("No se pudo obtener el identificador del recurso ('data.id').");
        }

        // 6. Ensamblar el manifest canónico oficial
        // Formato: id:[data.id];request-id:[x-request-id];ts:[ts];
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        // 7. Calcular HMAC-SHA256
        $expectedV1 = hash_hmac('sha256', $manifest, $this->secretKey);

        // 8. Comparación en tiempo constante
        if (!hash_equals($expectedV1, $receivedV1)) {
            throw new WebhookVerificationException("Firma del webhook inválida (la firma HMAC-SHA256 no coincide).");
        }

        return true;
    }

    /**
     * Parsea la cabecera x-signature (ej: "ts=1704067200,v1=5d54839...").
     *
     * @return array<string, string>
     */
    private function parseSignatureHeader(string $rawHeader): array
    {
        $parts = [];
        $segments = explode(',', $rawHeader);
        foreach ($segments as $segment) {
            if (str_contains($segment, '=')) {
                [$key, $value] = explode('=', $segment, 2);
                $parts[trim($key)] = trim($value);
            }
        }
        return $parts;
    }

    /**
     * Extrae el 'data.id' desde queryParams o body JSON.
     */
    private function extractDataId(string $payload, array $queryParams): ?string
    {
        // 1. Buscar en queryParams ('data.id', 'data_id', o 'id')
        if (isset($queryParams['data.id']) && is_scalar($queryParams['data.id'])) {
            return (string)$queryParams['data.id'];
        }
        if (isset($queryParams['data']['id']) && is_scalar($queryParams['data']['id'])) {
            return (string)$queryParams['data']['id'];
        }
        if (isset($queryParams['id']) && is_scalar($queryParams['id'])) {
            return (string)$queryParams['id'];
        }

        // 2. Buscar en el cuerpo JSON si está disponible
        if (!empty(trim($payload))) {
            $json = json_decode($payload, true);
            if (is_array($json)) {
                if (isset($json['data']['id']) && is_scalar($json['data']['id'])) {
                    return (string)$json['data']['id'];
                }
                if (isset($json['id']) && is_scalar($json['id'])) {
                    return (string)$json['id'];
                }
            }
        }

        return null;
    }
}
