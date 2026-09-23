# ⚡ XINDRO AI Copilot - Reglas y Estándares del Proyecto

Este documento establece las directrices arquitectónicas, de ciberseguridad y de estilo de código para **XINDRO AI Copilot** (PHP 8+, SQLite, Meta Graph API v19+, OpenRouter AI). Todas las tareas de desarrollo deben respetar estas reglas.

---

## 🛡️ 1. Ciberseguridad y Protección de Datos

1. **Consultas a Base de Datos (PDO Prepared Statements):**
   * Queda estrictamente prohibido concatenar variables en consultas SQL.
   * Utiliza siempre *Prepared Statements* con marcadores de posición (`?` o `:param`) a través de la clase de conexión en [config/database.php](file:///c:/xampp/htdocs/Redes%20sociales/config/database.php).
   ```php
   // CORRECTO:
   $stmt = $pdo->prepare("SELECT * FROM comments WHERE brand_id = ? AND client_id = ?");
   $stmt->execute([$brandId, $clientId]);
   ```

2. **Aislamiento Multi-Tenant:**
   * Toda consulta que lea, actualice o elimine datos de usuarios, marcas, publicaciones o comentarios DEBE incluir explícitamente el filtro del cliente actual (`client_id` / `brand_id`).

3. **Protección Anti-CSRF:**
   * Todos los endpoints de mutación (`POST`, `PUT`, `DELETE`) en `/api/*.php` deben validar el token CSRF enviado en el header (`X-CSRF-Token`) o en el cuerpo de la petición.

4. **Sanitización y Escapado de Salida:**
   * Toda salida HTML renderizada directamente en templates PHP debe escaparse con `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.

5. **Protección de Archivos Sensibles:**
   * No exponer rutas directas a bases de datos (`data/*.sqlite`), logs o archivos de configuración. La carpeta `data/` debe estar protegida con directivas `.htaccess` (`Require all denied`).

---

## 🏗️ 2. Arquitectura Backend y Endpoints API

1. **Formato de Respuesta JSON Estándar:**
   * Todas las respuestas de `/api/*.php` deben devolver el encabezado `Content-Type: application/json; charset=utf-8` y seguir la estructura estándar:
   ```json
   {
     "success": true,
     "message": "Operación completada",
     "data": { ... }
   }
   ```
   * En caso de error:
   ```json
   {
     "success": false,
     "error": "Mensaje descriptivo del error",
     "code": "INVALID_PARAMS"
   }
   ```

2. **Manejo de Excepciones:**
   * Envuelve las llamadas externas (Meta Graph API, OpenRouter, envíos de correo) en bloques `try { ... } catch (Throwable $e) { ... }`.
   * Registra los errores detallados en los logs de `data/` y devuelve un mensaje amigable al cliente sin filtrar información sensible del servidor.

3. **Compatibilidad PHP:**
   * Mantener compatibilidad estricta con **PHP 8.0, 8.1, 8.2 y 8.3**.
   * Utilizar tipado estricto en métodos de servicios ([services/AiAgentService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/AiAgentService.php), [services/MetaApiService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/MetaApiService.php)).

---

## 🎨 3. Frontend & Experiencia de Usuario (UI/UX)

1. **Tecnología Frontend:**
   * Uso de **HTML5 semántico**, **Vanilla CSS** moderno y **Vanilla JavaScript** (sin dependencias pesadas innecesarias).
2. **Estética Visual Premium:**
   * Mantener el tema visual moderno (Dark mode elegante, detalles glassmorphism, tarjetas con bordes sutiles y estados `:hover`).
   * Usar tipografías claras (Inter / Roboto) y paletas de colores armoniosas (púrpura/azul índigo para acentos IA, verde para activos, rojo/ámbar para alertas).
3. **Feedback Interactivo:**
   * Todo botón con acción asíncrona debe mostrar estado de carga (`loading spinner` o deshabilitado temporal) y notificaciones Toast o modales informativos claros.

---

## 🤖 4. Integración de IA & Meta Graph API

1. **Respaldo Heurístico Local:**
   * Siempre mantener activo el motor heurístico local como fallback en caso de indisponibilidad de OpenRouter o falta de saldo de API.
2. **Webhooks de Meta:**
   * Verificar la cabecera `X-Hub-Signature-256` con HMAC-SHA256 en [api/webhook.php](file:///c:/xampp/htdocs/Redes%20sociales/api/webhook.php) antes de procesar cualquier evento entrante.
   * Política Fail-Closed: si el secreto no está configurado o la firma no coincide, responder de inmediato con `401 Unauthorized` o `403 Forbidden`. Nunca devolver `200 OK` si la autenticidad falla.

---

## 🔒 5. Arnés de Ciberseguridad & Blindaje (Security Hardening Harness)

Para todo desarrollo presente y futuro, es de cumplimiento obligatorio aplicar los 7 pilares descritos en [docs/SECURITY_HARNESS.md](file:///c:/xampp/htdocs/Redes%20sociales/docs/SECURITY_HARNESS.md) y la skill `.agents/skills/security-hardening-harness/SKILL.md`:

1. **CORS Zero-Trust:** Solo admitir orígenes normalizados (`scheme://host[:port]`) que coincidan exactamente con `CORS_ALLOWED_ORIGINS` o `APP_URL`. Queda prohibido `str_contains` o `stripos` con el host.
2. **Webhooks Fail-Closed:** Rechazar con 401/403 ante secretos faltantes o firmas HMAC-SHA256 inválidas.
3. **Inmunidad a Host-Poisoning:** Generar URLs canónicas y callbacks de OAuth a partir de `APP_URL` de `.env`, nunca confiando en `$_SERVER['HTTP_HOST']`.
4. **Cifrado en Reposo Autenticado:** Tokens de acceso a APIs de terceros deben cifrarse con AES-256-GCM (`enc:v1:...`).
5. **Erradicación de Secretos por Defecto:** Prohibido dejar contraseñas de fábrica en el código. Generar siempre credenciales CSPRNG aleatorias durante la inicialización.
6. **Supresión de Fuga de Información & CSP:** Remover `X-Powered-By` y aplicar CSP estricto (`default-src 'none'` en API).
7. **Aislamiento CLI de Scripts Administrativos:** Denegar acceso HTTP a `scripts/`, `scratch/` y `data/` en `.htaccess` y validar `php_sapi_name() === 'cli'` en código.
8. **Suite de Pruebas de Blindaje:** Ejecutar periódicamente la suite automatizada de seguridad ([scratch/verify_security_suite.php](file:///c:/xampp/htdocs/Redes%20sociales/scratch/verify_security_suite.php)) con 100% de pruebas aprobadas antes de desplegar.

