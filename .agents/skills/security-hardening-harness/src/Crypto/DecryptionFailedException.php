<?php
declare(strict_types=1);

namespace Harness\Crypto;

/**
 * Excepción lanzada cuando una operación de descifrado falla por manipulación,
 * tag de autenticación inválido, formato corrupto o clave incorrecta.
 */
class DecryptionFailedException extends \RuntimeException
{
}
