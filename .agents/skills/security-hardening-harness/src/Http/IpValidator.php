<?php
declare(strict_types=1);

namespace Harness\Http;

/**
 * Validador estricto de direcciones IP (IPv4 e IPv6) para prevención de SSRF.
 * Detecta y bloquea redes privadas, loopback, link-local, metadatos de nube (169.254.169.254),
 * direcciones IPv4 mapeadas en IPv6 y formatos de evasión.
 */
class IpValidator
{
    /**
     * Rangos IPv4 prohibidos (CIDR)
     */
    private const BLOCKED_IPV4_RANGES = [
        '0.0.0.0/8',          // Esta red
        '10.0.0.0/8',         // RFC 1918 privada
        '100.64.0.0/10',      // Shared Address Space (CGNAT)
        '127.0.0.0/8',        // Loopback
        '169.254.0.0/16',     // Link-Local (incluye 169.254.169.254 metadata)
        '172.16.0.0/12',      // RFC 1918 privada
        '192.0.0.0/24',       // IETF Protocol Assignments
        '192.0.2.0/24',       // TEST-NET-1
        '192.168.0.0/16',     // RFC 1918 privada
        '198.18.0.0/15',      // Benchmarking
        '198.51.100.0/24',    // TEST-NET-2
        '203.0.113.0/24',     // TEST-NET-3
        '224.0.0.0/4',        // Multicast
        '240.0.0.0/4',        // Reservado
        '255.255.255.255/32', // Broadcast limitado
    ];

    /**
     * Rangos IPv6 prohibidos (CIDR)
     */
    private const BLOCKED_IPV6_RANGES = [
        '::/128',          // No especificada
        '::1/128',         // Loopback
        '0100::/64',       // Discard prefix
        '2001:2::/48',     // Benchmarking
        '2001:db8::/32',   // Documentación
        'fc00::/7',        // Unique Local Address (ULA privada)
        'fe80::/10',       // Link-Local
        'ff00::/8',        // Multicast
    ];

    /**
     * Determina si una IP es pública y segura para conectarse.
     * Retorna false si la IP es privada, reservada, loopback, metadatos o inválida.
     */
    public static function isPublicIp(string $ip): bool
    {
        $cleanIp = trim($ip);

        // 1. Detectar si es una representación IPv4 en formatos de evasión (entero, octal o hex)
        $canonicalIpv4 = self::canonicalizeIpv4($cleanIp);
        if ($canonicalIpv4 !== null) {
            $cleanIp = $canonicalIpv4;
        }

        $packed = @inet_pton($cleanIp);
        if ($packed === false) {
            return false;
        }

        // 2. Manejo de IPv4 (4 bytes)
        if (strlen($packed) === 4) {
            return self::isPublicIpv4($cleanIp);
        }

        // 3. Manejo de IPv6 (16 bytes)
        if (strlen($packed) === 16) {
            // Detección de IPv4-mapped IPv6 (::ffff:x.x.x.x) o IPv4-compatible (::x.x.x.x)
            // Bytes 0-9: \x00, Bytes 10-11: \xFF\xFF
            if (str_starts_with($packed, "\0\0\0\0\0\0\0\0\0\0\xFF\xFF") ||
                str_starts_with($packed, "\0\0\0\0\0\0\0\0\0\0\0\0")) {
                $embeddedIpv4 = inet_ntop(substr($packed, 12));
                if ($embeddedIpv4 !== false) {
                    return self::isPublicIpv4($embeddedIpv4);
                }
            }

            return self::isPublicIpv6($cleanIp);
        }

        return false;
    }

    /**
     * Valida si una IPv4 es estrictamente pública.
     */
    public static function isPublicIpv4(string $ip): bool
    {
        // Validación con banderas nativas de PHP
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return false;
        }

        // Comprobación manual exhaustiva contra la lista de CIDRs bloqueados
        foreach (self::BLOCKED_IPV4_RANGES as $cidr) {
            if (self::ipv4InCidr($ipLong, $cidr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida si una IPv6 es estrictamente pública.
     */
    public static function isPublicIpv6(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        foreach (self::BLOCKED_IPV6_RANGES as $cidr) {
            if (self::ipv6InCidr($ip, $cidr)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convierte representaciones de evasión de IPv4 (decimal de 32 bits, hex, octal) a formato canónico.
     */
    public static function canonicalizeIpv4(string $host): ?string
    {
        // 1. Entero decimal puro (ej: 2130706433 -> 127.0.0.1)
        if (ctype_digit($host)) {
            $num = filter_var($host, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0, 'max_range' => 4294967295]
            ]);
            if ($num !== false) {
                return long2ip($num);
            }
        }

        // 2. Notación hexadecimal pura (ej: 0x7f000001 -> 127.0.0.1)
        if (preg_match('/\A0x[0-9a-fA-F]{1,8}\z/', $host)) {
            $num = hexdec(substr($host, 2));
            if ($num >= 0 && $num <= 4294967295) {
                return long2ip((int)$num);
            }
        }

        // 3. Notaciones octales o mixtas por partes (ej: 0177.0.0.1 o 0x7f.0.0.1)
        if (str_contains($host, '.')) {
            $parts = explode('.', $host);
            if (count($parts) === 4) {
                $decParts = [];
                foreach ($parts as $p) {
                    $p = trim($p);
                    if (preg_match('/\A0x[0-9a-fA-F]+\z/', $p)) {
                        $decParts[] = hexdec(substr($p, 2));
                    } elseif (preg_match('/\A0[0-7]+\z/', $p)) {
                        $decParts[] = octdec($p);
                    } elseif (ctype_digit($p)) {
                        $decParts[] = (int)$p;
                    } else {
                        return null;
                    }
                }
                foreach ($decParts as $val) {
                    if ($val < 0 || $val > 255) {
                        return null;
                    }
                }
                return implode('.', $decParts);
            }
        }

        return null;
    }

    private static function ipv4InCidr(int $ipLong, string $cidr): bool
    {
        [$subnet, $mask] = explode('/', $cidr, 2);
        $subnetLong = ip2long($subnet);
        if ($subnetLong === false) {
            return false;
        }

        $maskBits = (int)$mask;
        if ($maskBits === 0) {
            return true;
        }

        $maskLong = ~((1 << (32 - $maskBits)) - 1);
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }

    private static function ipv6InCidr(string $ip, string $cidr): bool
    {
        [$subnet, $maskBits] = explode('/', $cidr, 2);
        $mask = (int)$maskBits;

        $ipPacked = inet_pton($ip);
        $subnetPacked = inet_pton($subnet);

        if ($ipPacked === false || $subnetPacked === false) {
            return false;
        }

        $bytesToCheck = intdiv($mask, 8);
        $bitsRemainder = $mask % 8;

        if ($bytesToCheck > 0) {
            if (substr($ipPacked, 0, $bytesToCheck) !== substr($subnetPacked, 0, $bytesToCheck)) {
                return false;
            }
        }

        if ($bitsRemainder > 0 && $bytesToCheck < 16) {
            $maskByte = 0xFF << (8 - $bitsRemainder) & 0xFF;
            $ipByte = ord($ipPacked[$bytesToCheck]);
            $subnetByte = ord($subnetPacked[$bytesToCheck]);
            if (($ipByte & $maskByte) !== ($subnetByte & $maskByte)) {
                return false;
            }
        }

        return true;
    }
}
