# 🛡️ Arnés de Ciberseguridad & Blindaje de Código (Security Hardening Harness)

Este documento es la referencia técnica y operativa del **Arnés de Ciberseguridad** establecido para **XINDRO AI Copilot** y diseñado como estándar replicable para cualquier futuro proyecto de software (APIs, Webhooks, Single Page Apps, servicios SaaS).

---

## 📌 Contexto & Propósito

Tras auditorías exhaustivas de ciberseguridad en producción (SAST, DAST y análisis de penetración tipo Codex), se identificaron y mitigaron 7 vectores críticos de ataque que comúnmente afectan a aplicaciones web modernas.

Este arnés condensa los principios, recetas de código y checklists de verificación para asegurar que ningún futuro desarrollo vuelva a cometer estos fallos.

---

## 🚫 Los 7 Pecados Capitales (Vectores Prohibidos)

| # | Vector Vulnerable | Riesgo Real Demostrado | Solución Obligatoria |
|---|-------------------|------------------------|----------------------|
| **1** | Coincidencia parcial en CORS (`str_contains`) | Un atacante usa `https://tudominio.com.evil.example` y roba sesiones autenticadas. | Validación de tupla estricta `scheme://host[:port]` contra lista blanca. |
| **2** | Webhooks *Fail-Open* (retornar 200 sin secreto/HMAC) | Atacantes inyectan cargas falsas, encolan procesos IA y consumen recursos. | Rechazar de inmediato con `401` o `403` si la firma falta o no coincide con HMAC-SHA256. |
| **3** | Confianza ciega en `HTTP_HOST` | Host Header Poisoning en URLs de OAuth Callback, reseteos de clave y correos. | Construir URLs canónicas basándose estrictamente en `APP_URL` de `.env`. |
| **4** | Tokens de acceso en texto plano | Volcado de base de datos expone tokens permanentes de Meta, OpenAI o Stripe. | Cifrado en reposo simétrico autenticado **AES-256-GCM** (`enc:v1:...`). |
| **5** | Secretos por defecto en código fuente | Credenciales de fábrica conocidas (`Admin2026!Secure`, `cron_secret`) permiten acceso total. | Erradicación total. Generación obligatoria aleatoria vía CSPRNG durante setup. |
| **6** | Fuga de información por cabeceras (`X-Powered-By`) | Fingerprinting exacto de versión PHP/Apache facilita explotación dirigida. | Supresión explícita de cabeceras y aplicación de CSP estricto (`default-src 'none'` en API). |
| **7** | Mutaciones destructivas con comodines (`LIKE %$id%`) | Borrado colateral o inyección que afecta a datos de múltiples usuarios. | Coincidencia exacta por clave primaria (`id = ?`) y aislamiento estricto por tenant (`user_id = ?`). |

---

## 🧱 Arquitectura de los 7 Pilares

### Pilar 1: CORS Zero-Trust
Toda API que exponga recursos debe pasar por [Security::isAllowedOrigin()](file:///c:/xampp/htdocs/Redes%20sociales/config/security.php):
- Se analiza la URL del origen entrante (`parse_url`).
- Se reconstruye la tupla normalizada: `https://dominio.com` o `https://dominio.com:8443`.
- Se compara con `in_array($normalized, $allowedOrigins, true)`.
- Si no está en la lista blanca, **NO** se emite ninguna cabecera CORS.

### Pilar 2: Ingesta Fail-Closed en Webhooks & Endpoints de Datos
- Si `getenv('WEBHOOK_SECRET')` está vacío o ausente: responder `403 Forbidden` inmediatamente.
- Si `$_SERVER['HTTP_X_HUB_SIGNATURE_256']` no coincide con `hash_hmac('sha256', $rawPayload, $secret)` usando `hash_equals`: responder `401 Unauthorized` de inmediato.
- Los handshakes de verificación `GET` deben rechazar tokens predeterminados o vacíos con `403 Forbidden`.

### Pilar 3: Inmunidad a Host-Poisoning & URLs Canónicas
- Todas las URLs externas y callbacks de OAuth se generan mediante `Security::getAppUrl()`.
- Nunca se extrae el host de `$_SERVER['HTTP_HOST']` en entornos de producción.
- En caso de requerir un fallback local de desarrollo, se restringe estrictamente a `$_SERVER['SERVER_NAME']` validado o `localhost`.

### Pilar 4: Cifrado en Reposo Autenticado (AES-256-GCM)
- Los tokens y secretos de API se cifran antes de guardarse en base de datos.
- **Formato:** `enc:v1:` + `base64(iv[12 bytes] + tag[16 bytes] + ciphertext)`.
- **Clave:** 256 bits (32 bytes binarios / 64 caracteres hex) configurada en `.env` (`APP_ENCRYPTION_KEY`).
- Al leer de la base de datos, [Security::decrypt()](file:///c:/xampp/htdocs/Redes%20sociales/config/security.php) verifica la autenticidad del tag GCM antes de retornar el texto claro.

### Pilar 5: Erradicación de Secretos por Defecto
- El repositorio de código no contiene contraseñas fijas, tokens maestros ni frases de paso predefinidas.
- En instalaciones iniciales, el instalador o script de arranque genera contraseñas criptográficamente seguras (`bin2hex(random_bytes(12))`) y las almacena en archivos de un solo uso protegidos.

### Pilar 6: Supresión de Fuga de Información & CSP
- Desactivación de `X-Powered-By` en PHP (`header_remove('X-Powered-By')`) y en Apache (`Header unset X-Powered-By`).
- Cabecera CSP estricta para APIs JSON:
  ```http
  Content-Security-Policy: default-src 'none'; frame-ancestors 'none'; base-uri 'none'
  ```
- Cabecera CSP para páginas Web/HTML:
  ```http
  Content-Security-Policy: default-src 'self'; frame-ancestors 'self'; object-src 'none'; base-uri 'self'; form-action 'self'
  ```

### Pilar 7: Suite de Pruebas de Blindaje (Pen-Testing Automático)
Todo proyecto cuenta con un script de auditoría automatizada ejecutable desde consola (ej. [scratch/verify_security_suite.php](file:///c:/xampp/htdocs/Redes%20sociales/scratch/verify_security_suite.php)) que valida:
1. Rechazo de dominios maliciosos con subdominio trampolín (`.evil.example`).
2. Rechazo con 401/403 ante firmas HMAC faltantes o alteradas.
3. Rechazo de manipulación en payloads cifrados con AES-256-GCM.
4. Imposibilidad de alterar URLs de OAuth mediante cabecera `Host` falsificada.
5. Inexistencia de contraseñas conocidas en esquemas o migraciones.

---

## 📋 Checklist Rápido para Nuevos Proyectos

- [ ] ¿El archivo `.env` está en `.gitignore` y protegido contra acceso web directo?
- [ ] ¿Se definió `APP_URL` exacta con esquema HTTPS y sin slash final?
- [ ] ¿Las cabeceras CORS validan orígenes normalizados de forma exacta?
- [ ] ¿Los Webhooks rechazan con 401/403 si la firma HMAC falla o no hay secreto?
- [ ] ¿Se usa AES-256-GCM para tokens y credenciales de terceros en base de datos?
- [ ] ¿Se eliminó `X-Powered-By` y se configuró CSP estricto?
- [ ] ¿Se ejecutó y aprobó al 100% la suite de pruebas de seguridad?
