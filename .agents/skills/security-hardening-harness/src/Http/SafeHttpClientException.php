<?php
declare(strict_types=1);

namespace Harness\Http;

/**
 * Excepción lanzada cuando una solicitud HTTP saliente es bloqueada
 * por violar políticas anti-SSRF, esquemas no permitidos, puertos prohibidos,
 * resolución de IPs privadas o evasiones maliciosas.
 */
class SafeHttpClientException extends \RuntimeException
{
}
