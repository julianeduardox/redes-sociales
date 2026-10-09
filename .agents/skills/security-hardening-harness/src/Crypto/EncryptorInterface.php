<?php
declare(strict_types=1);

namespace Harness\Crypto;

interface EncryptorInterface
{
    /**
     * Cifra un texto plano devolviendo un payload autenticado.
     *
     * @param string $plainText
     * @return string Payload en formato estructurado (ej. enc:v1:<base64>)
     */
    public function encrypt(string $plainText): string;

    /**
     * Descifra un payload autenticado devolviendo el texto plano original.
     *
     * @param string $payload
     * @return string
     * @throws DecryptionFailedException Si la autenticación falla o el formato es inválido.
     */
    public function decrypt(string $payload): string;
}
