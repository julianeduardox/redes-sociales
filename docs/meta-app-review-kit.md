# 🚀 Guía Maestra y Kit de Aprobación para Meta App Review (Facebook & Instagram)
> **Plataforma:** XINDRO Studio — Suite de Automatización & Moderación Inteligente  
> **Versión de Graph API:** v19.0+  
> **Documento Oficial para Revisores de Meta & Desarrolladores**

---

## 📌 1. Resumen de Requisitos Previos

Antes de someter tu aplicación en [developers.facebook.com](https://developers.facebook.com), asegúrate de tener:
1. **Verificación de Negocio (Business Verification):** En Meta Business Manager (`Configuración del Negocio > Centro de Seguridad`).
2. **Dominio con Certificado SSL Válido (HTTPS):** Certificado TLS 1.2+ activo.
3. **Página de Facebook vinculada a una cuenta de Instagram Profesional** (Creador o Empresa).
4. **URLs de Políticas y Eliminación de Datos** registradas en *Configuración Básica de la App*:
   - **Política de Privacidad:** `https://tudominio.com/privacy-policy.php`
   - **Condiciones del Servicio:** `https://tudominio.com/terms-of-service.php`
   - **Instrucciones de Eliminación de Datos:** `https://tudominio.com/data-deletion.php`
   - **Callback URL de Eliminación:** `https://tudominio.com/api/data-deletion.php`
   - **URI de Redireccionamiento OAuth:** `https://tudominio.com/callback-meta.php`
   - **Webhook Callback URL:** `https://tudominio.com/api/webhook.php` (Token: `social_boost_secure_token_2026`)

---

## 📝 2. Textos de Justificación para cada Permiso (Copiar y Pegar en App Review)

Copia estos textos exactamente en el formulario de solicitud de cada permiso en Meta Developers:

---

### 🔹 Permiso 1: `instagram_basic`
* **Pregunta de Meta: ¿Cómo utiliza tu aplicación este permiso?**
  * **English (Recomendado para revisores globales):**
    > *Our platform, XINDRO, requires `instagram_basic` to allow authenticated business owners and creators to read their Instagram Business Account profile details (username, profile picture, account ID) and list their published media objects (photos, reels, carousels). This data is displayed in our private, authenticated dashboard so users can monitor their content engagement and manage community interactions.*
  * **Español:**
    > *Nuestra plataforma XINDRO requiere `instagram_basic` para permitir a los propietarios y creadores de negocios autenticados leer los detalles de perfil de su cuenta de Instagram Profesional (nombre de usuario, foto de perfil, ID) y listar sus publicaciones (fotos, reels, carruseles). Estos datos se muestran en el panel de control privado para supervisar el rendimiento y gestionar interacciones.*

---

### 🔹 Permiso 2: `instagram_manage_comments`
* **Pregunta de Meta: ¿Cómo utiliza tu aplicación este permiso?**
  * **English:**
    > *XINDRO uses `instagram_manage_comments` to ingest incoming public comments left on the creator's posts via Meta Webhooks and the Graph API. It enables business administrators to view comments in an unified Inbox, moderate spam, analyze sentiment, and publish personalized, brand-tailored replies back to Instagram comments. This enhances customer service and community engagement in real time.*
  * **Español:**
    > *XINDRO utiliza `instagram_manage_comments` para recibir comentarios públicos dejados en las publicaciones del creador a través de Webhooks y la API Graph. Permite a los administradores visualizar comentarios en un Inbox unificado, moderar spam, analizar sentimiento y publicar respuestas personalizadas con la voz de la marca hacia Instagram.*

---

### 🔹 Permiso 3: `instagram_manage_insights`
* **Pregunta de Meta: ¿Cómo utiliza tu aplicación este permiso?**
  * **English:**
    > *We use `instagram_manage_insights` to retrieve post-level aggregated performance metrics (impressions, reach, saved count, and total interactions) for the business owner's own posts. Our analytics dashboard compiles these metrics into visual charts to help the business evaluate which content resonates best with their community.*
  * **Español:**
    > *Utilizamos `instagram_manage_insights` para recuperar métricas agregadas de rendimiento a nivel de publicación (impresiones, alcance, guardados y total de interacciones) para las publicaciones del negocio. Nuestro panel de analítica compila estas métricas en gráficos interactivos para evaluar el impacto del contenido.*

---

### 🔹 Permiso 4: `pages_show_list`
* **Pregunta de Meta: ¿Cómo utiliza tu aplicación este permiso?**
  * **English:**
    > *During the official OAuth 2.0 authorization flow, `pages_show_list` is required to query the `/me/accounts` endpoint. This allows our app to list the Facebook Pages managed by the logged-in user so they can select which Page and linked Instagram Business Account to connect to XINDRO for automated moderation.*
  * **Español:**
    > *Durante el flujo de autenticación OAuth 2.0, se requiere `pages_show_list` para consultar `/me/accounts`. Esto permite a nuestra aplicación listar las Páginas de Facebook administradas por el usuario autenticado para que pueda seleccionar qué Página y cuenta de Instagram vinculada conectará a XINDRO.*

---

### 🔹 Permiso 5: `pages_read_engagement`
* **Pregunta de Meta: ¿Cómo utiliza tu aplicación este permiso?**
  * **English:**
    > *`pages_read_engagement` is used to read follower comments, post reactions, and engagement data on the business's connected Facebook Page feed, allowing the community inbox to display incoming interactions for moderation.*
  * **Español:**
    > *`pages_read_engagement` se utiliza para leer comentarios de seguidores, reacciones y datos de interacción en el feed de la Página de Facebook conectada, permitiendo al inbox comunitario mostrar interacciones entrantes para su moderación.*

---

### 🔹 Permiso 6: `pages_manage_posts`
* **Pregunta de Meta: ¿Cómo utiliza tu aplicación este permiso?**
  * **English:**
    > *This permission allows XINDRO to post administrative and community replies to comments left by users on Facebook Page posts, either upon manual approval by the page manager or through automated customer support rules.*
  * **Español:**
    > *Este permiso permite a XINDRO publicar respuestas administrativas y de soporte comunitario a comentarios dejados por usuarios en las publicaciones de la Página de Facebook, ya sea tras aprobación manual del administrador o mediante reglas automáticas.*

---

## 🎬 3. Guión Paso a Paso para el Video Demostrativo (Screencast de 2 a 3 Minutos)

Meta exige un video claro (formato `.mp4` o `.mov`, sin música de fondo molesta) que demuestre el flujo de extremo a extremo. Sigue este guión:

| Tiempo | Acción en Pantalla | Qué decir o demostrar (Voz en off o texto explicativo) |
|---|---|---|
| **0:00 - 0:35** | Abre `dashboard.php` y navega a la pestaña **⚙️ Meta Graph API**. Muestra el botón *"Continuar con Facebook & Instagram"*. | *"Esta es la plataforma XINDRO. El usuario inicia la conexión oficial mediante Meta OAuth 2.0 haciendo clic en el botón oficial."* |
| **0:35 - 1:10** | Haz clic en el botón. Se abre la ventana de diálogo de Meta (`facebook.com/dialog/oauth`). Muestra claramente la pantalla de consentimiento de Meta con los permisos solicitados y haz clic en *"Continuar"*. | *"El usuario es redirigido a Meta, donde autoriza la lectura de sus Páginas e Instagram Profesional con permisos de moderación e insights."* |
| **1:10 - 1:40** | Redirección a `callback-meta.php` con la tarjeta de éxito que lista las páginas y cuentas vinculadas, y regreso al Dashboard. | *"El sistema intercambia el código por un token de larga duración y detecta automáticamente las cuentas de Instagram vinculadas."* |
| **1:40 - 2:20** | Ve a la pestaña **📥 Inbox de Comentarios**. Muestra cómo se visualiza un comentario recibido, el análisis de sentimiento por IA y haz clic en el botón **"Asistente de Respuestas & Conexión 🪄"** para publicar una respuesta en vivo. | *"Aquí el administrador visualiza los comentarios públicos, genera una respuesta personalizada con la voz de su marca y la publica directamente en Meta Graph API."* |
| **2:20 - 2:45** | Ve a la pestaña **📈 Métricas & Rendimiento**. Muestra los gráficos de alcance, impresiones y tasa de engagement. | *"En esta sección se visualizan las métricas de engagement obtenidas mediante instagram_manage_insights."* |
| **2:45 - 3:00** | Muestra el pie de página con los enlaces a `privacy-policy.php`, `terms-of-service.php` y `data-deletion.php`. | *"La aplicación cuenta con políticas de privacidad públicas y sistema de eliminación de datos de usuario en cumplimiento con las directrices de Meta."* |

---

## 🧪 4. Configuración de Cuentas de Prueba para Revisores de Meta

En el formulario de App Review, Meta te solicitará credenciales de acceso para que su equipo pueda probar tu plataforma:
1. **URL de Acceso de Prueba:** `https://tudominio.com/login.php`
2. **Usuario Demo:** `meta_reviewer@tudominio.com` (o tu usuario de prueba)
3. **Contraseña Demo:** `ReviewerMeta2026!`
4. **Instrucciones adicionales para el Revisor:**
   > *1. Log in with the provided credentials.*  
   > *2. Navigate to the 'Meta' tab in the left sidebar.*  
   > *3. Click on 'Continuar con Facebook & Instagram' to test the OAuth flow with your Meta test account.*  
   > *4. In the 'Inbox' tab, click on any comment and use 'Asistente de Respuestas' to test reply publishing.*

---

## 🛡️ 5. Por qué esta Arquitectura está 100% Blindada contra Rechazos

1. **Sin caídas por Webhook Timeout:** El webhook responde con HTTP 200 en `<50ms` mediante la cola SQLite WAL asíncrona (`webhook_queue`).
2. **Cumplimiento Estricto de Privacidad:** Toda la plataforma opera bajo el RGPD / CCPA y la ley europea de Inteligencia Artificial (*EU AI Act 2024/1689*), con botón directo de eliminación de datos y código de confirmación.
3. **Tokens de Larga Duración Oficiales:** Todo el ciclo de vida de credenciales pasa por `fb_exchange_token` y Page Tokens oficiales, eliminando tokens vencidos o no autorizados.
