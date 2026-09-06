---
name: meta-graph-expert
description: >-
  Especialista en integración con Meta Graph API (v19.0+) y Webhooks de Instagram/Facebook.
  Utilizar cuando se necesite probar, depurar o implementar endpoints de Meta, simular eventos
  de Webhooks en tiempo real, validar firmas HMAC-SHA256, o gestionar tokens y permisos de Meta.
---

# 🌐 Meta Graph API & Webhook Expert (Instagram & Facebook)

Esta skill proporciona los procedimientos, plantillas de eventos y guías de depuración para operar la integración con la **API de Meta Graph** y los **Webhooks en tiempo real** en XINDRO AI Copilot.

---

## 🔑 1. Verificación Inicial de Webhook (Handshake GET)

Cuando configuras la URL de Webhook en el Meta App Dashboard, Meta envía una petición `GET`:

* **Parámetros esperados:**
  * `hub.mode=subscribe`
  * `hub.challenge=<VALOR_ALEATORIO>`
  * `hub.verify_token=<TU_VERIFY_TOKEN>`
* **Comportamiento requerido:**
  * Si `hub.verify_token` coincide con el configurado en [config/settings.php](file:///c:/xampp/htdocs/Redes%20sociales/config/settings.php) (`META_WEBHOOK_VERIFY_TOKEN`), responder únicamente con el texto plano de `hub.challenge` y código HTTP 200.

---

## ⚡ 2. Simulación de Eventos de Webhook (POST con HMAC-SHA256)

Meta envía las notificaciones en tiempo real mediante peticiones `POST` firmadas criptográficamente.

### A. Estructura de Payload: Nuevo Comentario en Instagram
```json
{
  "object": "instagram",
  "entry": [
    {
      "id": "17841400000000000",
      "time": 1715000000,
      "changes": [
        {
          "field": "comments",
          "value": {
            "id": "17999999999999999",
            "text": "¿Cuál es el precio del plan mensual y qué incluye?",
            "from": {
              "id": "17841455555555555",
              "username": "cliente_potencial"
            },
            "media": {
              "id": "18000000000000000",
              "media_product_type": "FEED"
            }
          }
        }
      ]
    }
  ]
}
```

### B. Generación de Firma `X-Hub-Signature-256` en PHP / Local:
```php
$appSecret = 'TU_META_APP_SECRET';
$rawPayload = json_encode($payload);
$signature = 'sha256=' . hash_hmac('sha256', $rawPayload, $appSecret);
```

### C. Prueba local vía cURL (PowerShell):
```powershell
$headers = @{
    "Content-Type" = "application/json"
    "X-Hub-Signature-256" = "sha256=<FIRMA_CALCULADA>"
}
Invoke-RestMethod -Uri "http://localhost/Redes%20sociales/api/webhook.php" -Method Post -Headers $headers -Body $rawJson
```

---

## 📋 3. Permisos Clave de Meta Graph API

* **Instagram:**
  * `instagram_basic`: Información básica de la cuenta.
  * `instagram_manage_comments`: Leer, responder y ocultar comentarios en publicaciones.
  * `instagram_manage_insights`: Lectura de métricas de alcance e interacciones.
* **Facebook Pages:**
  * `pages_show_list`: Listar páginas administradas.
  * `pages_read_engagement`: Leer publicaciones y engagement de páginas.
  * `pages_manage_posts`: Publicar y gestionar contenido en Facebook.
  * `pages_messaging`: Interacción con mensajes directos (DM).

---

## 🛠️ 4. Depuración de Access Tokens

1. **Inspección de Tokens:**
   ```
   GET https://graph.facebook.com/debug_token?input_token={TOKEN}&access_token={APP_ID}|{APP_SECRET}
   ```
2. **Intercambio a Long-Lived Token (60 días):**
   ```
   GET https://graph.facebook.com/v19.0/oauth/access_token?grant_type=fb_exchange_token&client_id={APP_ID}&client_secret={APP_SECRET}&fb_exchange_token={SHORT_LIVED_TOKEN}
   ```
3. El servicio encargado de estas operaciones en el proyecto es [MetaApiService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/MetaApiService.php).
