---
name: change-verification-planner
description: >-
  Especialista en planificación estructurada, análisis de impacto y protocolos de verificación previa
  antes de ejecutar cualquier cambio en el código o base de datos de XINDRO AI Copilot.
  Utilizar SIEMPRE que se soliciten nuevas funcionalidades, refactorizaciones, cambios de esquema o modificaciones visuales.
---

# 📋 Change Verification & Implementation Planner

Esta skill establece el flujo de trabajo riguroso y obligatorio para XINDRO AI Copilot antes de realizar cualquier modificación en el código fuente, base de datos, APIs o interfaz de usuario.

---

## 🎯 1. Principio Fundamental: "Pensar y Verificar Antes de Modificar"

Ninguna línea de código de producción ni instrucción SQL debe ser ejecutada o modificada sin haber completado los siguientes pasos:
1. **Inspección Contextual:** Identificar exactamente qué archivos, modelos, servicios o tablas se ven afectados.
2. **Plan de Implementación (`implementation_plan.md`):** Documentar la arquitectura propuesta, riesgos potenciales, planes de reversión y pruebas.
3. **Aprobación Explícita:** Presentar la propuesta al usuario y esperar confirmación antes de la ejecución.
4. **Ejecución Asistida y Segura:** Modificar archivos preservando tipado estricto PHP 8+, seguridad PDO, aislamiento multi-tenant y estética dark mode.
5. **Protocolo de Verificación:** Validar sintaxis, endpoints y lógica de negocio antes de entregar el resultado.
6. **Resumen de Resultados (`walkthrough.md`):** Documentar los cambios completados y las pruebas exitosas.

---

## 🚦 2. Estructura Obligatoria del Plan de Implementación

Cada vez que se active este flujo, el artefacto `implementation_plan.md` debe estructurarse con:

```markdown
# [Nombre Claro del Cambio o Funcionalidad]

## 1. Diagnóstico y Estado Actual
- Descripción del comportamiento o necesidad detectada.
- Identificación de causas raíz (evitar suposiciones superficiales).

## 2. Decisiones de Diseño y Arquitectura
- Componentes afectados (Backend / Frontend / Base de Datos / Agentes IA).
- Estrategia de compatibilidad (PHP 8+, SQLite, Meta Graph API v19+).
- Alternativas evaluadas y justificación técnica de la solución elegida.

## 3. Plan de Cambios Propuestos (Paso a Paso)
- [NUEVO / MODIFICADO] Rutas exactas de archivos.
- Descripción de métodos, endpoints o tablas a crear/editar.
- Preservación de reglas multi-tenant y sanitización.

## 4. Plan de Pruebas y Verificación (Test Matrix)
- Verificación estática: Comandos PHP Lint (`php -l`).
- Verificación dinámica: Scripts de prueba CLI o validación HTTP de endpoints.
- Verificación visual: Comportamiento interactivo en UI (modales, feedback, responsive).

## 5. Medidas de Contingencia / Reversión
- Respaldos necesarios (ej. SQLite en caliente).
- Pasos de reversión si una llamada API externa o migración falla.
```

---

## 🧪 3. Protocolo de Verificación Práctica

### A. Verificación de Código PHP
Ejecutar siempre comprobación de sintaxis estricta:
```powershell
c:\xampp\php\php.exe -l "ruta\al\archivo.php"
```

### B. Verificación de Base de Datos y Queries
- Comprobar que toda consulta use PDO Prepared Statements (`?` o `:param`).
- Verificar que las consultas incluyan el filtro `user_id` o `client_id` para garantizar el aislamiento multi-tenant.
- Validar existencia de índices en columnas de filtrado frecuente (`is_archived`, `created_at`, etc.).

### C. Verificación de UI y Experiencia de Usuario
- Asegurar que los botones con acciones asíncronas muestren estado de carga (`loading`).
- Manejar estados vacíos (empty states) amigables e intuitivos.
- Comprobar que los modales o paneles interactivos permitan cerrar o regresar sin recargar la página completa.

---

## 🛡️ 4. Reglas Críticas de Seguridad y Estabilidad
- **No Pérdida de Datos:** Priorizar borrado lógico / archivado suave (`is_archived = 1`) en lugar de `DELETE` irreversibles en tablas de auditoría, publicaciones o comentarios.
- **Resiliencia ante Fallos de APIs Externas:** Todas las llamadas a Meta Graph API o proveedores de IA deben contar con fallback local (motor heurístico) y no bloquear el flujo del usuario.
- **Aislamiento de Sesión:** Validar `$_SESSION['user_id']` o `CSRF` en cada endpoint de mutación.
