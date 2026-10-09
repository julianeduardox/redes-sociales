# 📋 Implementation Plan: Acción "🚫 No responder / Ignorar", Restauración con Soft-Revoke y Moderación Blindada en Hermes

**Proyecto:** XINDRO AI Copilot  
**Módulo:** Asistente de Respuestas (Modal / Drawer Lateral), Bandeja de Comentarios & Motor de Moderación Hermes  
**Fecha:** 2026-10-06  
**Estado:** Refinado y blindado con auditoría reversible, compatibilidad tri-motor (SQLite/MySQL/PostgreSQL) y política de omisión fail-closed  

---

## 0. Corrección de auditoría posterior a la implementación (2026-10-06)

La revisión del código implementado detectó tres brechas que se corrigen en este ajuste:

1. Las rutas de Autopilot y de análisis manual invocaban `evaluateCommentSuitability()` sin el contexto `user_id` y `brand_voice_id`; por ello las señales humanas no se consultaban.
2. Los endpoints de ignorar/restaurar separaban el cambio de estado y el registro/revocación de señal, y no comprobaban las filas afectadas. Una carrera podía devolver éxito o registrar una señal aunque la transición no se hubiera realizado.
3. La lectura de señales incluía sin distinción la voz de marca `1`, permitiendo que patrones de una marca afectaran a otra del mismo usuario.

### Alcance de la corrección

- Pasar el usuario y la voz de marca ya resueltos a cada llamada automática y manual a `evaluateCommentSuitability()`.
- Envolver las mutaciones de `ignore_comment` y `restore_to_pending` junto con el UPSERT/soft-revoke en una transacción PDO; confirmar `rowCount()` y tratar solamente el mismo estado final como idempotente.
- Consultar señales activas únicamente para la voz de marca exacta.
- Mantener las coincidencias de troll, ofensa e irrelevancia en `pending_review`; solo el spam inequívoco puede omitirse automáticamente.
- Añadir una prueba CLI de regresión que compruebe los contratos anteriores sin modificar datos de producción.

### Riesgo y reversión

No se altera el esquema ni se eliminan datos. Si hubiera que revertir, basta restaurar los archivos de aplicación: las filas de auditoría existentes no se modifican masivamente.

---

## 0.1 Corrección del runner unificado de seguridad (2026-10-09)

### Diagnóstico

- El runner oficial se bloquea durante `test_safe_http.php`, por lo que una suite colgada impide obtener un resultado consolidado.
- El runner versionado depende de pruebas ubicadas en `.agents/`; deben incluirse expresamente en el commit para que la verificación sea reproducible fuera del equipo local.

### Decisiones

1. Mantener el código runtime de seguridad en `services/Security/` y conservar el arnés reutilizable en `.agents/skills/security-hardening-harness/`, incorporándolo explícitamente al control de versiones junto con el runner.
2. En Windows, no iniciar por defecto el servidor mock que puede bloquear el intérprete CLI; declarar esas comprobaciones de transporte como omitidas de forma explícita. Permitir su ejecución opt-in con `HARNESS_ENABLE_LOCAL_TRANSPORT=1` o en CI Unix.
3. Dar a cada suite del runner un límite de ejecución. Si vence, el runner debe marcarla como fallo y continuar con las demás, terminando con código distinto de cero.

### Verificación

- Lint de los archivos PHP modificados.
- Ejecución aislada de SafeHttp sin procesos PHP residuales.
- Ejecución completa del runner con las seis suites.
- Confirmación de que `tests/security-harness/` y `scripts/run_unified_security_tests.php` aparecen como archivos rastreables por Git.

### Reversión

La corrección afecta exclusivamente a utilidades de prueba y automatización. Puede revertirse eliminando `tests/security-harness/` y restaurando el runner anterior sin modificar datos ni flujos de producción.

---

## 1. 🔍 Diagnóstico y Flujo Actual del Sistema

### 1.1 Esquema y Flujos Existentes
- **Tabla `comments`:**
  - Columna `status VARCHAR(50) DEFAULT 'pending'`: Estados actuales `pending`, `replied`, `failed`, `ignored`, `pending_review`.
  - Backend `GET api/comments.php` ya contempla el filtro `?filter=ignored` (`WHERE c.status = 'ignored'`) y calcula `ignored_count`.
  - Falta el flujo de UI para ignorar comentarios desde el asistente y la acción de restauración interactiva desde el filtro de ignorados.
- **Aprendizaje Continuo (`AiAgentService`):**
  - `ai_learning_feedback` se reserva para *Few-Shot Prompting* (redacción de respuestas exitosas aprobadas por humanos).
  - El motor de moderación y la heurística de idoneidad (`evaluateCommentSuitability`) requieren señales de descarte separadas para calibrar el riesgo de comentarios sin contaminar la generación de respuestas.
- **Asistente (`#modal-assistant-replies`):**
  - Admite vista modal centrada y panel lateral drawer (`.drawer-mode`).

---

## 2. 🏛️ Decisiones de Diseño y Arquitectura Blindadas

### 2.1 Esquema Portátil y Auditoría con Soft-Revoke (`ai_moderation_feedback`)
1. **Definición de Tabla Multimotor (SQLite, PostgreSQL, MySQL):**
   ```sql
   CREATE TABLE IF NOT EXISTS ai_moderation_feedback (
       id INTEGER PRIMARY KEY AUTOINCREMENT, -- SERIAL en PostgreSQL, AUTO_INCREMENT en MySQL
       user_id INTEGER NOT NULL,
       brand_voice_id INTEGER NOT NULL DEFAULT 1,
       comment_id INTEGER NOT NULL,
       comment_text TEXT NOT NULL,
       author_name VARCHAR(255) NULL,
       author_handle VARCHAR(255) NULL,
       platform VARCHAR(50) NULL,
       reason VARCHAR(100) NOT NULL,
       notes VARCHAR(500) NULL,
       is_active INTEGER NOT NULL DEFAULT 1,
       restored_at DATETIME NULL,
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       CONSTRAINT uq_user_comment_moderation UNIQUE (user_id, comment_id)
   );
   ```

2. **Índice Portátil (Sin `DESC` para máxima compatibilidad):**
   ```sql
   CREATE INDEX IF NOT EXISTS idx_ai_mod_perf ON ai_moderation_feedback(user_id, brand_voice_id, is_active, created_at);
   ```
   *Cualquiera de los motores (SQLite, MySQL, PostgreSQL) puede recorrer este índice de forma bidireccional.*

3. **UPSERT Específico por Motor (Idempotencia y Prevención de Duplicados):**
   - En [services/AiAgentService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/AiAgentService.php), se detecta el driver activo mediante `Database::getDriver()`:
     - **SQLite & PostgreSQL:**
       ```sql
       INSERT INTO ai_moderation_feedback (
           user_id, brand_voice_id, comment_id, comment_text, author_name, author_handle, platform, reason, notes, is_active, restored_at, updated_at
       ) VALUES (
           :uid, :bvid, :cid, :txt, :aname, :ahandle, :plat, :reason, :notes, 1, NULL, CURRENT_TIMESTAMP
       )
       ON CONFLICT (user_id, comment_id) DO UPDATE SET
           brand_voice_id = EXCLUDED.brand_voice_id,
           reason = EXCLUDED.reason,
           notes = EXCLUDED.notes,
           is_active = 1,
           restored_at = NULL,
           updated_at = CURRENT_TIMESTAMP;
       ```
     - **MySQL:**
       ```sql
       INSERT INTO ai_moderation_feedback (
           user_id, brand_voice_id, comment_id, comment_text, author_name, author_handle, platform, reason, notes, is_active, restored_at, updated_at
       ) VALUES (
           :uid, :bvid, :cid, :txt, :aname, :ahandle, :plat, :reason, :notes, 1, NULL, CURRENT_TIMESTAMP
       )
       ON DUPLICATE KEY UPDATE
           brand_voice_id = VALUES(brand_voice_id),
           reason = VALUES(reason),
           notes = VALUES(notes),
           is_active = 1,
           restored_at = NULL,
           updated_at = CURRENT_TIMESTAMP;
       ```

4. **Restauración con Soft-Revoke (Cero Pérdida de Datos y Trazabilidad Total):**
   - Al restaurar un comentario a pendientes:
     - **NO se ejecuta `DELETE`**.
     - Se actualiza:
       ```sql
       UPDATE ai_moderation_feedback
       SET is_active = 0, restored_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
       WHERE user_id = :uid AND comment_id = :cid;
       ```
     - El historial queda intacto para auditoría y Hermes excluye de inmediato esa señal (`WHERE is_active = 1`).

### 2.2 Cálculo Seguro de Marca en el Servidor (Zero-Trust)
- `brand_voice_id` se resuelve exclusivamente en el backend desde la relación `comments -> posts -> accounts/brand_voices`:
  `COALESCE(p.brand_voice_id, a.brand_voice_id, 1)`
- Se ignora cualquier valor de marca enviado desde el cliente.

### 2.3 Sanitización Profunda y Renderizado Seguro de Notas
- Longitud acotada a **500 caracteres** (`mb_substr`).
- En servidor: Sanitización inicial con `strip_tags()`.
- En renderizado PHP: `htmlspecialchars($notes, ENT_QUOTES, 'UTF-8')`.
- En cliente JS: Inyección exclusiva mediante propiedades seguras de texto (`element.textContent = notes`), evitando categóricamente `innerHTML`.

### 2.4 Política de Moderación y Aprendizaje de Hermes (Fail-Closed & Prevención de Falsos Positivos)
- **Consulta de contexto:** Hermes solo consulta señales activas (`is_active = 1`) para la misma marca del usuario.
- **Regla estricta contra omisión ciega:**
  - Los comentarios de seguidores son entradas no confiables; un parecido superficial no debe censurar a un cliente legítimo.
  - El historial de moderación se utiliza para **elevar el nivel de riesgo** o **recomendar el botón "🚫 No responder"** dentro del asistente.
  - **Solo se omite de forma 100% automática en autopilot ante spam inequívoco comprobado** (enlaces externos fraudulentos, cryptobots o patrones comerciales invasivos conocidos).
  - Comentarios catalogados como `troll_provocation`, `offensive_language` o `irrelevant` **NUNCA se omiten automáticamente a ciegas**: se retienen para **revisión humana** (`status = 'pending_review'` o sugerencia marcada con advertencia en el asistente).

### 2.5 Actualización Fluida de Interfaz (Sin Recarga Completa)
- Ninguna acción recarga la ventana (`window.location.reload()` proscrito).
- Al confirmar "No responder":
  1. Deshabilitación de botón y spinner de carga en el modal.
  2. Notificación Toast de confirmación.
  3. Mutación en memoria de la lista de comentarios y actualización de insignias.
  4. Llamada en segundo plano a `await App.loadComments()` para refrescar la bandeja.
  5. Avance automático en el asistente al siguiente comentario pendiente (`advanceToNextPendingComment(commentId)`).
- En el filtro "Ignorados":
  - Cada tarjeta dispone del botón primario `↩️ Volver a pendientes`.
  - Al pulsarlo, el comentario regresa a pendientes, se actualizan los contadores y se muestra Toast informativo sin refrescar la página.

---

## 3. 🛠️ Plan de Cambios Propuestos (Paso a Paso)

### Paso 1: Base de Datos & Migración ([config/database.php](file:///c:/xampp/htdocs/Redes%20sociales/config/database.php))
- Creación de tabla `ai_moderation_feedback` para los 3 drivers (`sqlite`, `pgsql`, `mysql`) con columnas `is_active`, `restored_at`, `updated_at` y restricción única `(user_id, comment_id)`.
- Creación del índice portable `idx_ai_mod_perf (user_id, brand_voice_id, is_active, created_at)`.

### Paso 2: Servicio Hermes ([services/AiAgentService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/AiAgentService.php))
- Método `recordModerationFeedback()` con bifurcación de UPSERT según `Database::getDriver()`.
- Método `revokeModerationFeedback($userId, $commentId)` para soft-revoke al restaurar.
- Método `getActiveModerationSignals($userId, $brandVoiceId)` consultando solo `is_active = 1`.
- Integración en `evaluateCommentSuitability()` bajo la política *fail-closed* (elevar riesgo y sugerir no responder, reservando omisión automática únicamente para spam inequívoco).

### Paso 3: Endpoints API ([api/comments.php](file:///c:/xampp/htdocs/Redes%20sociales/api/comments.php))
- Acción `ignore_comment`:
  - Rate limiting, CSRF y Auth.
  - Consulta y resolución en servidor de `brand_voice_id`.
  - Validación de enum de motivo (`spam_link`, `troll_provocation`, `offensive_language`, `irrelevant`, `already_resolved`, `other`).
  - Acotado de notas a 500 chars.
  - Transición atómica en `comments` y llamada a `recordModerationFeedback()`.
- Acción `restore_to_pending`:
  - Rate limiting, CSRF y Auth.
  - Transición atómica `comments SET status = 'pending', highlight_reason = NULL`.
  - Llamada a `revokeModerationFeedback()` (soft-revoke con `is_active = 0`).

### Paso 4: Vistas & Componentes Visuales ([dashboard.php](file:///c:/xampp/htdocs/Redes%20sociales/dashboard.php))
- Botón **“🚫 No responder”** en el editor del asistente lateral y modal `#modal-assistant-replies`.
- Modal accesible de confirmación de descarte `#modal-confirm-ignore`:
  - Opciones de motivo, contador de caracteres para notas (0/500).
  - Soporte de teclado (Escape / Enter) y foco accesible.
- En el menú *"Más filtros ▾"*, agregar ítem interactivo `🚫 Ignorados / Sin responder` con contador dinámico `tag-count-ignored`.

### Paso 5: Controladores Frontend ([assets/js/agent-controller.js](file:///c:/xampp/htdocs/Redes%20sociales/assets/js/agent-controller.js) & [assets/js/app.js](file:///c:/xampp/htdocs/Redes%20sociales/assets/js/app.js))
- En `AgentController`:
  - Gestión del modal de confirmación, validación y envío seguro con CSRF.
  - Transición automática al siguiente pendiente tras ignorar.
- En `App`:
  - Renderizado de botón `↩️ Volver a pendientes` cuando el filtro activo sea `ignored`.
  - Método `restoreCommentToPending(commentId)` con recarga fluida sin refrescar la página.
  - Renderizado seguro de notas mediante `textContent`.

---

## 4. 🧪 Plan de Pruebas y Matriz de Verificación

| Prueba | Tipo | Criterio de Aprobación |
| :--- | :--- | :--- |
| **Sintaxis PHP** | Estática | `php -l` en `config/database.php`, `services/AiAgentService.php`, `api/comments.php` sin errores. |
| **Sintaxis JS** | Estática | `node --check` en `assets/js/agent-controller.js`, `assets/js/app.js` sin errores. |
| **UPSERT Idempotente** | Dinámica | Múltiples envíos para el mismo comentario actualizan campos y reactivan `is_active = 1` sin generar duplicados. |
| **Soft-Revoke al Restaurar** | Base de Datos | Al restaurar un comentario, `is_active` pasa a 0 y `restored_at` se completa. La fila no se elimina. |
| **Política Fail-Closed** | IA / Hermes | Comentario similar a un troll previo eleva alerta para revisión humana; no se auto-omite silenciosamente. Solo spam evidente se auto-omite. |
| **Renderizado XSS-Proof** | Seguridad | Notas con `<script>` o `<img>` se muestran estrictamente como texto plano vía `textContent` o `htmlspecialchars`. |
| **Aislamiento Multi-Tenant** | Seguridad | Intentos de ignorar o restaurar comentarios de otro usuario devuelven `403 Forbidden`. |
| **Protección CSRF** | Seguridad | Petición sin token CSRF devuelve `403 Forbidden`. |
| **Suite de Blindaje General** | Seguridad | Ejecución de `scratch/verify_security_suite.php` con 100% aprobado. |

---

## 5. 🛡️ Medidas de Reversión
- Cambios aditivos: reversión de código vía Git sin impacto destructivo.
- Base de datos: la tabla `ai_moderation_feedback` es independiente; puede eliminarse con `DROP TABLE IF EXISTS ai_moderation_feedback` en caso de requerirse sin afectar la operativa de comentarios existentes.
