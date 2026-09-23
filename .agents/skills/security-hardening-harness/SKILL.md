---
name: security-hardening-harness
description: >-
  Arnés integral de ciberseguridad, hardening y blindaje de código para aplicaciones web y APIs (PHP/Node/Python).
  CORS Zero-Trust, Webhooks Fail-Closed, Cifrado en reposo AES-256-GCM, Anti Host-Poisoning y prevención de fugas.
---

# 🛡️ Arnés de Ciberseguridad & Hardening (Universal Security Harness)

Este arnés condensa los estándares de seguridad críticos derivados de auditorías de penetración en producción (Codex / SAST / DAST). Aplica de forma obligatoria en el diseño, desarrollo, auditoría y despliegue de **cualquier proyecto** presente y futuro.

---

## 🚫 Los 7 Pecados Capitales de Seguridad (Prohibiciones Estrictas)

1. **PROHIBIDO:** Usar `str_contains($origin, $host)` o `stripos()` para validar CORS. Permite dominios maliciosos como `https://dominio.com.evil.example`.
2. **PROHIBIDO:** Comportamiento *Fail-Open* en Webhooks. Si falta el secreto o la firma HMAC, NUNCA responder 200 ni aceptar la carga.
3. **PROHIBIDO:** Usar `$_SERVER['HTTP_HOST']` para construir Redirect URIs de OAuth, enlaces de reseteo de contraseña o URLs canónicas (Vulnerable a Host Header Poisoning).
4. **PROHIBIDO:** Guardar tokens de acceso (Meta, OpenAI, Stripe, etc.) en texto plano en la base de datos.
5. **PROHIBIDO:** Dejar contraseñas o tokens por defecto en el código (ej. `'admin123'`, `'cron_secret_key_2026'`, `'test_token'`).
6. **PROHIBIDO:** Exponer cabeceras informativas de versión (`X-Powered-By: PHP/8.x`, `Server: Apache/x`).
7. **PROHIBIDO:** Ejecutar consultas destructivas con comodines (`DELETE ... LIKE %$id%`). Toda mutación debe ser por ID exacto y aislar el tenant (`user_id = ?`).
8. **PROHIBIDO:** Permitir acceso web directo a scripts administrativos, migraciones o herramientas CLI (`scripts/`, `scratch/`). Deben estar bloqueados en servidor web (`.htaccess`) y tener guarda estricta `php_sapi_name() === 'cli'`.

---

## 🧱 Los 7 Pilares del Arnés de Blindaje

### 1. CORS Zero-Trust (Whitelist Exacta)
- **Regla:** Ningún origen externo recibe cabeceras CORS a menos que coincida **exactamente** (`scheme://host[:port]`) con la lista blanca configurada en `.env` (`CORS_ALLOWED_ORIGINS`).
- **Implementación Estándar:**
  ```php
  public static function isAllowedOrigin(string $origin): bool {
      if (empty($origin)) return false;
      $parsed = parse_url($origin);
      if (!$parsed || empty($parsed['scheme']) || empty($parsed['host'])) return false;

      $scheme = strtolower($parsed['scheme']);
      $host = strtolower($parsed['host']);
      $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);

      $normalized = "{$scheme}://{$host}" . (!in_array($port, [80, 443]) ? ":{$port}" : "");

      $allowed = array_filter(array_map('trim', explode(',', getenv('CORS_ALLOWED_ORIGINS') ?: '')));
      $allowed[] = rtrim(getenv('APP_URL') ?: '', '/');
      if (getenv('APP_ENV') !== 'production') {
          $allowed = array_merge($allowed, ['http://localhost', 'https://localhost', 'http://127.0.0.1', 'https://127.0.0.1']);
      }

      return in_array($normalized, array_unique($allowed), true);
  }
  ```
- Si la validación falla, **NO emitir** `Access-Control-Allow-Origin` ni `Access-Control-Allow-Credentials`.

---

### 2. Webhooks & Callbacks Fail-Closed (HMAC Obligatorio)
- **Regla:** Si falta la clave secreta en la configuración o la firma HMAC enviada no coincide, la petición DEBE ser rechazada inmediatamente (`HTTP 401 Unauthorized` o `HTTP 403 Forbidden`).
- **Implementación Estándar:**
  ```php
  $secret = getenv('WEBHOOK_SECRET');
  if (empty($secret)) {
      http_response_code(403);
      echo json_encode(['error' => 'Webhook secret no configurado']);
      exit;
  }

  $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
  $rawPayload = file_get_contents('php://input');

  $expected = 'sha256=' . hash_hmac('sha256', $rawPayload, $secret);
  if (empty($signature) || !hash_equals($expected, $signature)) {
      http_response_code(401);
      echo json_encode(['error' => 'Firma HMAC inválida']);
      exit;
  }
  ```
- En handshakes `GET`, rechazar tokens por defecto o vacíos (`HTTP 403`).

---

### 3. URLs Canónicas & Inmunidad a Host Header Poisoning
- **Regla:** Toda URL externa (OAuth Callback, emails transaccionales, enlaces de webhook) debe basarse en la variable `APP_URL` de `.env`.
- **Implementación Estándar:**
  ```php
  public static function getAppUrl(): string {
      $appUrl = getenv('APP_URL');
      if (!empty($appUrl)) {
          return rtrim($appUrl, '/');
      }
      // Fallback estricto solo para local (nunca confiar ciegamente en HTTP_HOST)
      $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
      return "http://{$host}";
  }

  public static function getOAuthRedirectUri(): string {
      return self::getAppUrl() . '/callback.php';
  }
  ```

---

### 4. Cifrado en Reposo Autenticado (AES-256-GCM)
- **Regla:** Toda clave API o token de acceso guardado en SQLite/PostgreSQL/MySQL debe estar cifrado en reposo.
- **Formato:** `enc:v1:<base64(iv(12) . tag(16) . ciphertext)>`
- **Clave Simétrica:** 256 bits (`APP_ENCRYPTION_KEY` en `.env`), NUNCA en la base de datos.
- **Implementación Estándar:**
  ```php
  public static function encrypt(string $plain): string {
      $key = hex2bin(getenv('APP_ENCRYPTION_KEY'));
      $iv = random_bytes(12);
      $tag = '';
      $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
      return 'enc:v1:' . base64_encode($iv . $tag . $cipher);
  }

  public static function decrypt(string $payload): string {
      if (!str_starts_with($payload, 'enc:v1:')) return $payload;
      $raw = base64_decode(substr($payload, 7));
      if (strlen($raw) < 28) return '';
      $iv = substr($raw, 0, 12);
      $tag = substr($raw, 12, 16);
      $cipher = substr($raw, 28);
      $key = hex2bin(getenv('APP_ENCRYPTION_KEY'));
      $decrypted = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
      return $decrypted !== false ? $decrypted : '';
  }
  ```

---

### 5. Erradicación de Secretos por Defecto
- El archivo `.env.example` solo debe contener plantillas vacías y comandos CSPRNG para generar claves.
- Si una instalación se inicializa sin contraseña en `.env`, generar una contraseña aleatoria (`bin2hex(random_bytes(10))`) y guardarla en un archivo con permisos `0600` de un solo uso.

---

### 6. Supresión de Fuga de Información & CSP
- **PHP:**
  ```php
  header_remove('X-Powered-By');
  @ini_set('expose_php', '0');
  ```
- **Apache (`.htaccess`):**
  ```apache
  Header unset X-Powered-By
  Header always unset X-Powered-By
  ServerSignature Off
  ```
- **Content Security Policy (CSP):**
  - **APIs:** `Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'`
  - **HTML:** `default-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'`

---

### 7. Aislamiento CLI & Blindaje de Scripts Administrativos
- **Regla:** Scripts de migración, generadores de credenciales, herramientas de consola y carpetas de prueba (`scripts/`, `scratch/`) NUNCA deben responder por HTTP.
- **Implementación Servidor (`.htaccess`):**
  ```apache
  RewriteRule ^(data|config|services|scripts|scratch|\.agents)/ - [F,L,NC]
  ```
- **Implementación PHP (Defensa en Profundidad):**
  ```php
  if (php_sapi_name() !== 'cli' && !defined('STDIN')) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'Acceso denegado: este script solo puede ejecutarse vía CLI.']);
      exit(1);
  }
  ```

---

### 8. Suite de Pruebas de Penetración Automatizadas
Antes de entregar o desplegar cualquier cambio crítico, se debe ejecutar un script de prueba automatizado que verifique:
1. `isAllowedOrigin('https://dominio.evil.example') === false`
2. POST a webhook con HMAC falsa retorna `401`.
3. POST a webhook sin secret retorna `403`.
4. Manipulación de payload cifrado AES-256-GCM es rechazada.
5. Inyección de `HTTP_HOST` malicioso no contamina `getOAuthRedirectUri()`.
6. Intento de acceso web a scripts administrativos retorna `403 Forbidden`.
