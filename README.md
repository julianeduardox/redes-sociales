# ⚡ XINDRO AI Copilot - Multi-Brand Social Media Agency OS

Plataforma SaaS multi-tenant y multi-cliente (White-label para agencias y creadores) para gestión de engagement, moderación asistida con Inteligencia Artificial agnóstica (Gemini / OpenAI / Motor Heurístico Comercial local), webhooks en tiempo real y analítica para **Instagram & Facebook (Meta Graph API)**.

---

## 🚀 Características Principales

- **🏢 Soporte Multi-Marca & Multi-Cliente:** Gestiona múltiples marcas, creadores o clientes desde un mismo panel con cambio en caliente instantáneo (`brand_voices`).
- **🤖 Copiloto de IA Agnóstico:** System prompt dinámico por cliente con calibración de identidad, sliders de calidez/expertise/conversión y 3 variantes comerciales (Conexión/Empatía, Conversión/CTA, Autoridad/Solución).
- **⚡ Modo Autopilot:** Detección de intenciones universales (Leads de precio, objeciones de ventas, soporte, consultas generales) y auto-respuesta en tiempo real.
- **🌐 Webhooks en Tiempo Real:** Integración nativa con Meta Graph API (`/api/webhook.php`) verificada criptográficamente con HMAC-SHA256.
- **📊 Analíticas & Insights:** Métricas de alcance, engagement rate, impresiones y comentarios destacados por marca o publicación.
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
3. **Acceso al Panel:**
   Accede a `https://tudominio.com/login.php`
   - **Usuario Tester API:** `tester@xindro.app`
   - **Contraseña:** `TesterPassword2026!`
4. **Configuración de Marcas & APIs:**
   Ingresa al **Estudio de Voz de Marca** para configurar clientes, prompts y proveedores de IA (Gemini / OpenAI).

---

## 🔒 Licencia y Seguridad
Este proyecto está protegido bajo estrictos estándares de seguridad y confidencialidad. No compartas tus tokens de acceso ni claves de API en repositorios públicos.

---
*Despliegue Continuo (CI/CD) automatizado vía GitHub Actions & Hostinger FTP.*
