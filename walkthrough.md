# 🚀 Walkthrough: Acción "🚫 No responder / Ignorar", Restauración y Señales de Moderación para Hermes

**Proyecto:** XINDRO AI Copilot  
**Fecha de Implementación:** 2026-10-06  
**Resultado de Verificación:** Lint PHP sin errores, regresión de moderación 8/8 y suite de seguridad 37/37.

## Corrección posterior de auditoría: contexto, concurrencia y aislamiento

- Todas las rutas manuales y automáticas que evalúan un comentario entregan ahora `user_id` y `brand_voice_id` a Hermes, por lo que las señales humanas de moderación sí participan en la evaluación.
- Las señales activas se consultan exclusivamente para la voz de marca exacta; no se comparten de forma implícita entre marcas.
- Trolls, ofensas e irrelevancias que coincidan con una señal humana se mantienen en `pending_review`; solo el spam inequívoco se omite automáticamente.
- Ignorar y restaurar ejecutan su transición de estado junto con el UPSERT o soft-revoke en una transacción. Si no se completa todo, se revierte todo.
- Las solicitudes repetidas devuelven un resultado idempotente sin reactivar ni contaminar una señal de moderación.
- Se agregó `scratch/verify_moderation_feedback.php`, una prueba CLI sin mutaciones de datos que valida estos contratos. Resultado: 8/8.

---

## Corrección del runner unificado de seguridad (2026-10-09)

- El runner versionado `scripts/run_unified_security_tests.php` ahora ejecuta cada suite en un proceso aislado con un límite de 30 segundos. Si una prueba externa se bloquea, informa el fallo y continúa con el resto de la verificación.
- Se corrigió la suite `test_safe_http.php` para que el transporte mock local no congele PHP en Windows. En Windows se omiten explícitamente las seis comprobaciones de red local por defecto; se pueden ejecutar en CI Unix o en un host Windows compatible definiendo `HARNESS_ENABLE_LOCAL_TRANSPORT=1`.
- La validación TLS externa también se marca como `SKIP` cuando el entorno no dispone de resolución DNS o salida de red. No se confunde una restricción del entorno de pruebas con una vulnerabilidad del cliente.
- Ejecución verificada: las seis suites concluyeron correctamente en menos de cinco segundos. Resultado: B1 25/25, B2 22 PASS y 7 SKIP justificados por el entorno, B3A 16/16, B3B 16/16, B4 46/46 y XINDRO 43/43.
- Antes de subir los cambios se debe incluir en el commit el contenido no ignorado de `.agents/skills/security-hardening-harness/src/` y `.agents/skills/security-hardening-harness/tests/`, además de `services/Security/` y `scripts/run_unified_security_tests.php`; el runner los requiere para reproducir la verificación en otro equipo.

---

## 🌟 Resumen de la Funcionalidad

Se ha implementado en el **Asistente de Respuestas** (tanto en modo Ventana Flotante como en Panel Lateral Drawer) la acción **“🚫 No responder / Ignorar”** para comentarios que no deben recibir respuesta (spam, trolls, provocaciones, lenguaje ofensivo, irrelevantes o ya atendidos).

La funcionalidad opera bajo una arquitectura **auditable, reversible, tri-motor (SQLite, MySQL, PostgreSQL) y blindada contra falsos positivos**, garantizando que las señales de omisión alimenten el motor de moderación de Hermes sin contaminar los ejemplos de redacción de respuestas.

---

## 🛠️ Cambios Realizados

### 1. Base de Datos Multi-Motor ([config/database.php](file:///c:/xampp/htdocs/Redes%20sociales/config/database.php))
- Creada la tabla dedicada `ai_moderation_feedback` para **SQLite**, **PostgreSQL** y **MySQL**:
  - `user_id`, `brand_voice_id`, `comment_id`, `comment_text`, `author_name`, `author_handle`, `platform`, `reason`, `notes`, `is_active`, `restored_at`, `created_at`, `updated_at`.
  - Restricción única de idempotencia: `CONSTRAINT uq_user_comment_moderation UNIQUE (user_id, comment_id)`.
  - Índice portable optimizado: `CREATE INDEX IF NOT EXISTS idx_ai_mod_perf ON ai_moderation_feedback(user_id, brand_voice_id, is_active, updated_at)`.
- Incrementada la versión del esquema SQLite (`$targetSchemaVersion = 20261016`) para migración automática en caliente.

### 2. Motor de Moderación & Aprendizaje Hermes ([services/AiAgentService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/AiAgentService.php))
- **`recordModerationFeedback()`:** Implementa UPSERT especializado según el driver activo (`ON CONFLICT` en SQLite/PgSQL, `ON DUPLICATE KEY UPDATE` en MySQL) garantizando idempotencia ante doble clic y reactivación suave de señales con actualización de `updated_at`.
- **`revokeModerationFeedback()`:** Ejecuta un *soft-revoke* (`is_active = 0, restored_at = CURRENT_TIMESTAMP`) al restaurar un comentario a pendientes. No se borra la fila para mantener la trazabilidad.
- **`getActiveModerationSignals()`:** Consulta únicamente señales con `is_active = 1` ordenadas por `updated_at DESC`.
- **`evaluateCommentSuitability()` (Política Fail-Closed):**
  - Inyecta señales activas de la marca.
  - Omitir de forma 100% automática en autopilot **única y exclusivamente ante spam inequívoco reincidente**.
  - Ante patrones de troll, ofensa o irrelevancia previos, **nunca descarta a ciegas**: retiene para **revisión humana** (`status = 'pending_review'`) y recomienda no responder.

### 3. Endpoints Seguros ([api/comments.php](file:///c:/xampp/htdocs/Redes%20sociales/api/comments.php))
- **`ignore_comment`:**
  - Protección obligatoria CSRF, rate limit multi-tenant y verificación de pertenencia (`user_id`).
  - Resuelve `brand_voice_id` 100% en el servidor mediante `COALESCE(p.brand_voice_id, a.brand_voice_id, 1)`.
  - Transición atómica en `comments`: `UPDATE comments SET status = 'ignored' WHERE id = :id AND user_id = :uid AND status IN ('pending', 'failed', 'pending_review')`.
  - Sanitiza notas con `strip_tags()` y acotado estricto a 500 caracteres.
  - Cero llamadas a Meta Graph API y cero registros en `replies`.
- **`restore_to_pending`:**
  - Actualiza **únicamente** cuando `status = 'ignored'` para evitar devolver por error comentarios respondidos a pendientes.
  - Ejecuta *soft-revoke* en `ai_moderation_feedback`.

### 4. Interfaz de Usuario ([dashboard.php](file:///c:/xampp/htdocs/Redes%20sociales/dashboard.php))
- Botón **“🚫 No responder”** añadido al pie del editor del asistente lateral y modal `#modal-assistant-replies`.
- Modal accesible de confirmación `#modal-confirm-ignore`:
  - 6 motivos seleccionables (`spam_link`, `troll_provocation`, `offensive_language`, `irrelevant`, `already_resolved`, `other`).
  - Textarea con contador en vivo (0/500).
  - Soporte de teclado: foco accesible, cierre con tecla `Escape` y confirmación con `Enter`.
- Menú "Más filtros ▾":
  - Ítem interactivo **`🚫 Ignorados / Omitidos`** con badge dinámico `tag-count-ignored`.

### 5. Controladores Frontend ([assets/js/agent-controller.js](file:///c:/xampp/htdocs/Redes%20sociales/assets/js/agent-controller.js) & [assets/js/app.js](file:///c:/xampp/htdocs/Redes%20sociales/assets/js/app.js))
- **`AgentController`:**
  - `openIgnoreConfirmationModal()`, `closeIgnoreConfirmationModal()`, `onIgnoreNotesInput()`, `submitIgnoreComment()`.
  - Deshabilita botones durante la llamada mostrando spinner `⏳ Ignorando...`.
  - Transición automática al siguiente comentario pendiente (`advanceToNextPendingComment`) o aviso de "¡Bandeja al día!".
- **`App`:**
  - En la vista del filtro `Ignorados`, las tarjetas muestran el botón primario `↩️ Volver a pendientes`.
  - Implementado `restoreCommentToPending(commentId)` con sincronización asíncrona sin recarga completa de página (`window.location.reload()` proscrito).
  - Renderizado de notas protegido contra XSS mediante `.textContent` y `htmlspecialchars()`.

---

## 🧪 Pruebas Realizadas y Resultados

1. **PHP Lint:**
   - `config/database.php`: 0 errores.
   - `services/AiAgentService.php`: 0 errores.
   - `api/comments.php`: 0 errores.
   - `dashboard.php`: 0 errores.
2. **Suite de Moderación Automatizada ([scratch/test_moderation_feature.php](file:///c:/xampp/htdocs/Redes%20sociales/scratch/test_moderation_feature.php)):**
   - Creación de comentario de prueba: Aprobada.
   - Registro y sanitización de notas: Aprobada (tags eliminados).
   - Idempotencia ante doble clic (UPSERT): Aprobada (exactamente 1 fila).
   - Consulta de señales activas indexadas: Aprobada.
   - Soft-revoke al restaurar (`is_active = 0, restored_at` registrado): Aprobada.
   - Exclusión de señales revocadas en Hermes: Aprobada.
   - Reactivación limpia al re-ignorar (`is_active = 1, restored_at = NULL`): Aprobada.
   - Política fail-closed en evaluación: Aprobada (`status: pending_review` para troll/ofensivo, sin auto-omisión ciega).
3. **Suite General de Hardening ([scratch/verify_security_suite.php](file:///c:/xampp/htdocs/Redes%20sociales/scratch/verify_security_suite.php)):**
   - 37 de 37 pruebas aprobadas exitosamente (100% PASS).
