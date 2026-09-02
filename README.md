# 🏛️ SocialBoost AI / Mente Estoica - Social Media Engagement Agent

Sistema inteligente de gestión de engagement, moderación asistida con Inteligencia Artificial (Gemini / OpenAI / Motor Heurístico Stoic local), webhook en tiempo real y analítica para **Instagram & Facebook (Meta Graph API)**.

---

## 🚀 Características Principales

- **🤖 Copiloto de Respuestas IA:** Generación en 3 variantes (Conexión/Empatía, Conversión/Valor, Sabiduría/Profundidad) calibradas con la voz de marca estoica.
- **⚡ Modo Autopilot:** Detección de intenciones de alto valor y respuesta automatizada instantánea a comentarios prioritarios.
- **🌐 Webhooks en Tiempo Real:** Integración nativa con Meta Graph API (`/api/webhook.php`) verificada criptográficamente con HMAC-SHA256.
- **📊 Analíticas & Insights:** Métricas de alcance, engagement rate, impresiones y comentarios destacados.
- **🛡️ Ciberseguridad Blindada:**
  - Protección Anti-CSRF criptográfica en todas las mutaciones.
  - Rate Limiting por IP con ventana deslizante.
  - Multi-Tenant Data Isolation con hashing de contraseñas Bcrypt.
  - Consultas protegidas con PDO Prepared Statements contra inyecciones SQL.
  - Bloqueo de acceso HTTP directo a bases de datos y carpetas del sistema mediante `.htaccess`.
- **⚖️ Cumplimiento Meta App Review:** Páginas oficiales de Términos de Servicio (`/terms-of-service.php`), Política de Privacidad (`/privacy-policy.php`) y Callback de Eliminación de Datos (`/api/data-deletion.php`).

---

## 🛠️ Requisitos del Servidor

- **PHP:** 8.0, 8.1, 8.2 o 8.3.
- **Extensiones PHP:** `pdo_sqlite`, `curl`, `openssl`, `mbstring`, `json`.
- **Servidor Web:** Apache o LiteSpeed (con soporte para `.htaccess`).
- **HTTPS:** Certificado SSL activo (requerido para Webhooks de Meta).

---

## 📦 Instalación y Despliegue

1. **Clonar o subir los archivos al servidor:**
   ```bash
   git clone <URL_DEL_REPOSITORIO> .
   ```
2. **Permisos de la carpeta de datos:**
   Asegúrate de que la carpeta `data/` tenga permisos de escritura (`chmod 755` o `775`).
3. **Primer inicio:**
   Accede a `https://tudominio.com/login.php`
   - **Email Administrador inicial:** `admin@menteestoica.com`
   - **Contraseña:** `admin1234` *(cámbiala o crea tu propia cuenta)*.
4. **Configuración de APIs:**
   Ingresa a la pestaña **Ajustes** y coloca tus claves de API de Google Gemini / OpenAI y el Token de Acceso de Meta Graph API.

---

## 🔒 Licencia y Seguridad
Este proyecto está protegido bajo estrictos estándares de seguridad y confidencialidad. No compartas tus tokens de acceso ni claves de API en repositorios públicos.
