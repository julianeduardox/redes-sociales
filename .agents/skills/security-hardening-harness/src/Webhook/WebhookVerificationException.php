<?php
declare(strict_types=1);

namespace Harness\Webhook;

/**
 * Excepción lanzada cuando un webhook no supera la verificación criptográfica o temporal.
 */
class WebhookVerificationException extends \RuntimeException
{
}
