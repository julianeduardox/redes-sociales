<?php
declare(strict_types=1);

namespace Harness\Tests;

use Harness\Http\SafeHttpClient;

/**
 * Cliente HTTP derivado exclusivamente para pruebas locales de transporte.
 * Sobreescribe la verificación de IP para permitir el puerto mock de test local (127.0.0.1).
 */
class TestableSafeHttpClient extends SafeHttpClient
{
    private array $allowedLocalIps;

    public function __construct(
        bool $allowHttp = false,
        int $timeoutSeconds = 15,
        int $maxRedirects = 0,
        array $allowedPorts = [443],
        int $maxBodyBytes = 2097152,
        int $maxHeaderBytes = 32768,
        bool $allowCrossDomainRedirect = false,
        array $allowedLocalIps = ['127.0.0.1']
    ) {
        parent::__construct(
            $allowHttp,
            $timeoutSeconds,
            $maxRedirects,
            $allowedPorts,
            $maxBodyBytes,
            $maxHeaderBytes,
            $allowCrossDomainRedirect
        );
        $this->allowedLocalIps = $allowedLocalIps;
    }

    protected function isPermittedIp(string $ip): bool
    {
        if (in_array($ip, $this->allowedLocalIps, true)) {
            return true;
        }
        return parent::isPermittedIp($ip);
    }
}
