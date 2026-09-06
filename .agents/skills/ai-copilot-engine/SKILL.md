---
name: ai-copilot-engine
description: >-
  Especialista en el motor de IA, calibración de prompts de marca y moderación automática en XINDRO AI Copilot.
  Utilizar al modificar AiAgentService.php, ajustar la detección de intenciones, calibrar variantes comerciales
  (Conexión, Conversión, Autoridad), o evaluar la integración con OpenRouter y el motor heurístico local.
---

# 🤖 AI Copilot Engine & Brand Voice Tuner

Esta skill define la arquitectura de prompts, esquemas de datos JSON y estrategias de moderación con Inteligencia Artificial implementadas en [AiAgentService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/AiAgentService.php).

---

## 📐 1. Esquema JSON Estricto de Respuesta de la IA

Toda generación de respuesta y análisis de comentarios debe adherirse a esta estructura JSON para garantizar la compatibilidad con el frontend y el modo Autopilot:

```json
{
  "intent": "lead_price",
  "sentiment": "positive",
  "confidence": 0.95,
  "action_recommended": "auto_reply",
  "reply_suggestion": "¡Hola @usuario! 👋 Nuestro plan profesional incluye todas las herramientas de automatización. Te enviamos los detalles por privado 🚀",
  "variants": {
    "connection": "¡Hola @usuario! Nos encanta tu interés 😊 Te contamos todo sobre los planes...",
    "conversion": "¡Hola @usuario! Aprovecha hoy nuestra promoción activa haciendo clic en el enlace de la bio o escríbenos al DM 📩",
    "authority": "Nuestra plataforma procesa respuestas en tiempo real con 99.9% de precisión. Los detalles técnicos y planes están disponibles aquí..."
  },
  "flags": {
    "is_toxic": false,
    "is_spam": false,
    "requires_human": false
  }
}
```

---

## 🎯 2. Taxonomía de Intenciones Universales (`intent`)

El motor clasifica cada comentario o mensaje en una de las siguientes categorías:

1. **`lead_price`**: Preguntas sobre precios, cotizaciones, planes o tarifas.
2. **`objection_sales`**: Dudas sobre garantías, métodos de pago o comparativas con competidores.
3. **`feature_inquiry`**: Preguntas sobre compatibilidad, horarios, ubicación o características técnicas.
4. **`support_request`**: Problemas de acceso, dudas post-compra o solicitudes de ayuda.
5. **`praise_positive`**: Agradecimientos, felicitaciones o reseñas positivas.
6. **`criticism_negative`**: Quejas, reclamos o comentarios insatisfechos.
7. **`spam_irrelevant`**: Enlaces promocionales externos, bots o spam.

---

## 🎛️ 3. Parámetros de Calibración de Voz de Marca (`brand_voices`)

Cada marca o cliente en el sistema se calibra mediante estos parámetros:

* **`warmth_level` (1-10):** Grado de cercanía, uso de emojis y lenguaje coloquial.
* **`expertise_level` (1-10):** Rigor técnico, vocabulario especializado y formalidad.
* **`conversion_level` (1-10):** Enfoque en llamados a la acción (CTA), urgencia y derivación al DM / enlace.
* **`identity_keywords`:** Palabras clave o frases que definen el producto/servicio.
* **`forbidden_words`:** Palabras o temas que la IA nunca debe mencionar.

---

## 🔄 4. Estrategia de Fallback y Resiliencia

1. **Nivel 1 (OpenRouter API):** Envío de solicitud al modelo seleccionado (`anthropic/claude-3.5-sonnet`, `openai/gpt-4o`, `deepseek/deepseek-chat`).
2. **Nivel 2 (Timeout / Error 429/500):** Reintento automático con modelo ligero secundario.
3. **Nivel 3 (Motor Heurístico Local):** Si no hay conexión o se agotan los créditos de API, [AiAgentService.php](file:///c:/xampp/htdocs/Redes%20sociales/services/AiAgentService.php) analiza palabras clave por expresiones regulares locales y genera una respuesta segura precalibrada según la voz de la marca.
