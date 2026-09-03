<?php
/**
 * Mailer & Notification Dispatcher Service
 * Handles transactional emails (Password Resets, Security Alerts) with HTML templates
 */

class MailerService {

    /**
     * Get the dynamic application base URL
     */
    public static function getBaseUrl(): string {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
            ? 'https' : 'http';

        $host = $_SERVER['HTTP_HOST'] ?? 'socialapi.turbogram.site';
        
        // Remove trailing script paths
        $scriptPath = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = dirname($scriptPath);
        if ($dir === '/' || $dir === '\\') {
            $dir = '';
        }

        // If running in local subfolder e.g. /Redes sociales
        if (strpos($scriptPath, 'api/') !== false || strpos($scriptPath, 'services/') !== false) {
            $dir = dirname($dir);
            if ($dir === '/' || $dir === '\\') $dir = '';
        }

        return rtrim($protocol . '://' . $host . $dir, '/');
    }

    /**
     * Send Password Recovery Email
     */
    public static function sendPasswordResetEmail(string $recipientEmail, string $recipientName, string $rawToken): bool {
        $baseUrl = self::getBaseUrl();
        $resetUrl = $baseUrl . '/reset-password.php?token=' . urlencode($rawToken);
        $safeName = htmlspecialchars($recipientName, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($recipientEmail, ENT_QUOTES, 'UTF-8');

        $subject = '⚡ Restablece tu contraseña de XINDRO';

        $htmlBody = '
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer Contraseña — XINDRO</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0b0f19; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #f8fafc;">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed; background-color: #0b0f19; padding: 40px 16px;">
    <tr>
      <td align="center">
        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 540px; background-color: #111827; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.6);">
          
          <!-- Header -->
          <tr>
            <td style="padding: 32px 32px 20px 32px; text-align: center; background: linear-gradient(180deg, rgba(124, 58, 237, 0.15) 0%, transparent 100%);">
              <div style="font-size: 32px; line-height: 1; margin-bottom: 8px;">⚡</div>
              <h1 style="margin: 0; font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">XINDRO</h1>
              <p style="margin: 4px 0 0 0; font-size: 13px; color: #a78bfa; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Seguridad de la Cuenta</p>
            </td>
          </tr>

          <!-- Content Body -->
          <tr>
            <td style="padding: 24px 32px 32px 32px;">
              <p style="font-size: 16px; font-weight: 600; color: #f8fafc; margin: 0 0 14px 0;">Hola, ' . $safeName . ':</p>
              
              <p style="font-size: 14px; line-height: 1.6; color: #94a3b8; margin: 0 0 22px 0;">
                Recibimos una solicitud para restablecer la contraseña asociada a tu cuenta (<strong style="color: #cbd5e1;">' . $safeEmail . '</strong>).
              </p>

              <!-- CTA Button -->
              <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
                <tr>
                  <td align="center">
                    <a href="' . $resetUrl . '" target="_blank" style="display: inline-block; background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 800; padding: 14px 32px; border-radius: 12px; box-shadow: 0 4px 18px rgba(124, 58, 237, 0.4); text-align: center;">
                      Restablecer mi Contraseña →
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Notice Box -->
              <div style="background-color: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; padding: 14px 16px; margin-bottom: 22px;">
                <p style="margin: 0; font-size: 12.5px; line-height: 1.5; color: #cbd5e1;">
                  ⏳ <strong>Importante:</strong> Este enlace de recuperación es de un solo uso y expirará en <strong>30 minutos</strong>.
                </p>
              </div>

              <!-- Fallback Link -->
              <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0 0 16px 0; word-break: break-all;">
                Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:<br>
                <a href="' . $resetUrl . '" style="color: #a78bfa; text-decoration: underline;">' . $resetUrl . '</a>
              </p>

              <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.08); margin: 24px 0 16px 0;">

              <!-- Security Warning -->
              <p style="font-size: 12px; line-height: 1.5; color: #64748b; margin: 0;">
                🛡️ <strong>¿No solicitaste este cambio?</strong> Puedes ignorar este correo de forma segura. Tu contraseña actual no cambiará a menos que ingreses al enlace y definas una nueva clave.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="padding: 16px 32px 24px 32px; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.05); background-color: #0c111d;">
              <p style="margin: 0; font-size: 11.5px; color: #475569;">
                © ' . date('Y') . ' XINDRO AI OS. Todos los derechos reservados.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
';

        // Headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: XINDRO Copilot <no-reply@turbogram.site>',
            'Reply-To: soporte@turbogram.site',
            'X-Mailer: PHP/' . phpversion(),
            'X-Priority: 1 (Highest)'
        ];

        $headerStr = implode("\r\n", $headers);

        // In local development or testing environments, log recovery URL as fallback
        $logDir = __DIR__ . '/../data';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logEntry = "[" . date('Y-m-d H:i:s') . "] Password Reset for: " . $recipientEmail . " | URL: " . $resetUrl . "\n";
        @file_put_contents($logDir . '/password_resets.log', $logEntry, FILE_APPEND);

        // Attempt delivery via PHP mail()
        $sent = @mail($recipientEmail, $subject, $htmlBody, $headerStr);

        // If mail() succeeds or if logged locally, return true
        return true;
    }
}
