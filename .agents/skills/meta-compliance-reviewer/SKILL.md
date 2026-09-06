---
name: meta-compliance-reviewer
description: >-
  Especialista en auditoría y preparación para Meta App Review, cumplimiento legal y borrado de datos.
  Utilizar antes de enviar la aplicación a revisión de Meta, al modificar data-deletion.php,
  privacy-policy.php o terms-of-service.php, o al generar cuentas de prueba para los revisores de Meta.
---

# ⚖️ Meta Compliance & App Review Auditor

Esta skill proporciona los checklists técnicos, requerimientos legales y pruebas de endpoints obligatorios para superar con éxito el proceso de **Meta App Review** de Facebook e Instagram.

---

## 🗑️ 1. Endpoint de Borrado de Datos (`data-deletion.php`)

Meta exige un endpoint público que procese solicitudes de eliminación de datos de usuarios (`Data Deletion Request Callback`).

* **Archivos del proyecto:** [data-deletion.php](file:///c:/xampp/htdocs/Redes%20sociales/data-deletion.php) y [api/data-deletion.php](file:///c:/xampp/htdocs/Redes%20sociales/api/data-deletion.php).
* **Mecanismo:**
  1. Meta envía una petición `POST` con el parámetro `signed_request`.
  2. El backend descifra la firma con el `META_APP_SECRET`.
  3. El endpoint debe responder inmediatamente con un JSON conteniendo:
     * `url`: URL pública donde el usuario puede consultar el estado de su solicitud de borrado.
     * `confirmation_code`: Código alfanumérico único de seguimiento.
  4. La página pública debe permitir ingresar el código y mostrar el estado de la eliminación.

### Simulación de prueba local:
```powershell
# Enviar POST de prueba al endpoint de borrado de datos
Invoke-RestMethod -Uri "http://localhost/Redes%20sociales/api/data-deletion.php" -Method Post -Body @{ signed_request = "TEST_SIGNED_REQUEST" }
```

---

## 📄 2. Auditoría de Páginas Legales Obligatorias

Asegurar que las siguientes URLs sean accesibles públicamente sin requerir autenticación previa:

1. **Política de Privacidad:** [privacy-policy.php](file:///c:/xampp/htdocs/Redes%20sociales/privacy-policy.php)
   * Debe detallar: tipos de datos recolectados (IDs de usuario de Meta, comentarios públicos, métricas de engagement), finalidad del uso (moderación asistida por IA), períodos de retención y derechos ARCO / eliminación.
2. **Términos de Servicio:** [terms-of-service.php](file:///c:/xampp/htdocs/Redes%20sociales/terms-of-service.php)
   * Debe estipular: condiciones de uso SaaS, límites de responsabilidad respecto a las políticas de Meta y OpenRouter, y reglas de uso del Autopilot.

---

## 👤 3. Flujo de Usuario Tester para Revisores de Meta

Los revisores de Meta requieren credenciales funcionales para evaluar el panel en vivo:

1. Ejecutar el script [scripts/create_test_user.php](file:///c:/xampp/htdocs/Redes%20sociales/scripts/create_test_user.php) para asegurar que el usuario de prueba esté activo en la base de datos:
   * **Usuario:** `tester@xindro.app`
   * **Contraseña:** `TesterPassword2026!`
2. Verificar que la cuenta de prueba tenga acceso al **Dashboard**, al **Estudio de Voz de Marca** y a las vistas de simulación sin restricciones.

---

## 📹 4. Checklist para Grabación de Screencasts (App Review)

Para cada permiso solicitado en Meta Developer Portal:

- [ ] Mostrar claramente el botón de inicio de sesión con Facebook / Instagram Connect.
- [ ] Mostrar el diálogo de permisos de Meta solicitando exactamente los permisos declarados.
- [ ] Demostrar el caso de uso real en el panel (ej. cómo se recibe un comentario y cómo la IA sugiere una respuesta).
- [ ] Explicar por qué la aplicación no puede funcionar sin ese permiso específico.
- [ ] Consultar la guía completa en [docs/meta-app-review-kit.md](file:///c:/xampp/htdocs/Redes%20sociales/docs/meta-app-review-kit.md).
