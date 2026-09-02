<?php
/**
 * Términos y Condiciones de Uso / Terms of Service
 * Cumplimiento oficial para Meta Graph API, App Review y Servicios de IA.
 */
require_once __DIR__ . '/config/security.php';
Security::applySecurityHeaders(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Términos del Servicio | SocialBoost AI & Mente Estoica</title>
  <meta name="description" content="Condiciones generales de uso del servicio SocialBoost AI y la integración con Meta Graph API.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .legal-container {
      max-width: 920px;
      margin: 0 auto;
      padding: 40px 24px 80px;
    }
    .legal-card {
      background: var(--bg-card);
      border: 1px solid var(--border-subtle);
      border-radius: var(--radius-lg);
      padding: 40px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      backdrop-filter: blur(16px);
    }
    .legal-header {
      border-bottom: 1px solid var(--border-subtle);
      padding-bottom: 24px;
      margin-bottom: 32px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 16px;
    }
    .legal-title {
      font-size: 2rem;
      font-weight: 800;
      color: #fff;
      background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 8px;
    }
    .legal-updated {
      font-size: 0.82rem;
      color: var(--text-dim);
    }
    .lang-switcher {
      display: flex;
      gap: 6px;
      background: rgba(255, 255, 255, 0.05);
      padding: 4px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-subtle);
    }
    .lang-btn {
      background: transparent;
      border: none;
      color: var(--text-muted);
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
    }
    .lang-btn.active {
      background: var(--primary);
      color: #fff;
    }
    .legal-section {
      margin-bottom: 32px;
    }
    .legal-section h2 {
      font-size: 1.25rem;
      font-weight: 800;
      color: #f1f5f9;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .legal-section h2 .sec-num {
      color: var(--accent-cyan);
      font-size: 1rem;
    }
    .legal-section p, .legal-section li {
      font-size: 0.92rem;
      line-height: 1.75;
      color: var(--text-muted);
      margin-bottom: 12px;
    }
    .legal-section ul {
      padding-left: 24px;
      margin-bottom: 16px;
    }
    .legal-highlight-box {
      background: rgba(99, 102, 241, 0.08);
      border: 1px solid var(--border-active);
      border-left: 4px solid var(--primary);
      border-radius: var(--radius-sm);
      padding: 16px 20px;
      margin: 20px 0;
    }
    .legal-highlight-box p {
      margin-bottom: 0;
      color: #cbd5e1;
    }
    .back-nav {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: var(--text-muted);
      text-decoration: none;
      font-size: 0.85rem;
      font-weight: 600;
      margin-bottom: 20px;
      transition: var(--transition-fast);
    }
    .back-nav:hover {
      color: #fff;
      transform: translateX(-3px);
    }
  </style>
</head>
<body>

  <div class="legal-container">
    <a href="index.php" class="back-nav">
      ← Volver al Panel de Control
    </a>

    <div class="legal-card">
      <div class="legal-header">
        <div>
          <h1 class="legal-title" id="page-title">Términos y Condiciones del Servicio</h1>
          <div class="legal-updated" id="page-updated">Última actualización: 31 de Agosto de 2026 • Versión 2.4 (Cumplimiento Meta Graph API)</div>
        </div>

        <div class="lang-switcher">
          <button class="lang-btn active" onclick="setLanguage('es')">Español</button>
          <button class="lang-btn" onclick="setLanguage('en')">English</button>
        </div>
      </div>

      <!-- SPANISH CONTENT -->
      <div id="content-es">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Aceptación de los Términos</h2>
          <p>
            Al acceder, registrarse o utilizar <strong>SocialBoost AI / Mente Estoica</strong> (en adelante, el "Servicio" o la "Plataforma"), usted acepta quedar vinculado legalmente por estos Términos y Condiciones de Uso, así como por nuestra <a href="privacy-policy.php" style="color: var(--accent-cyan);">Política de Privacidad</a>. Si no está de acuerdo con alguna disposición de estos términos, no debe utilizar la Plataforma.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Descripción del Servicio y Asistencia con IA</h2>
          <p>
            SocialBoost AI es una plataforma de software diseñada para creadores de contenido, marcas y administradores comunitarios, que facilita la moderación, categorización de intenciones, visualización de métricas de audiencia y generación asistida de respuestas para redes sociales (Meta Graph API: Facebook e Instagram) empleando modelos avanzados de Inteligencia Artificial.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Integración con Meta Platforms y Permisos de Cuenta</h2>
          <p>
            Para que el Servicio opere correctamente, el usuario autoriza la conexión con su cuenta de Meta mediante el protocolo OAuth. El usuario declara y garantiza que:
          </p>
          <ul>
            <li>Es el propietario legítimo o administrador debidamente autorizado de las Páginas de Facebook y Cuentas de Instagram Business vinculadas.</li>
            <li>Cumplirá en todo momento con las Políticas de la Plataforma de Desarrolladores de Meta y los Términos de Servicio de Instagram y Facebook.</li>
            <li>No utilizará la API para enviar mensajes no deseados (spam), comentarios masivos no solicitados ni contenido engañoso.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Descargo de Responsabilidad sobre Contenido Generado por IA</h2>
          <div class="legal-highlight-box">
            <p><strong>Naturaleza Asistencial de la IA:</strong> Las respuestas generadas por los motores de IA (Gemini / OpenAI / Heurística) actúan como sugerencias para asistir al creador. Si bien el sistema incorpora filtros éticos y de tono de marca, el usuario es el responsable final de la supervisión, configuración del piloto automático y publicación de las respuestas en sus canales sociales.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Uso Aceptable y Restricciones Prohibidas</h2>
          <p>Queda estrictamente prohibido:</p>
          <ul>
            <li>Utilizar la Plataforma para difundir discursos de odio, difamación, acoso, contenido sexual explícito, violencia o cualquier actividad ilegal.</li>
            <li>Intentar vulnerar la seguridad, realizar ataques de denegación de servicio (DoS) o eludir los limitadores de tasa (rate limits) de la API.</li>
            <li>Revender, sublicenciar o explotar comercialmente el código fuente de la aplicación sin autorización expresa.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> Propiedad Intelectual</h2>
          <p>
            Todos los derechos de propiedad intelectual sobre el software, diseño, logotipos, algoritmos heurísticos y código fuente pertenecen a SocialBoost AI / Mente Estoica. El usuario conserva la total propiedad y derechos sobre sus publicaciones, imágenes, marcas comerciales y contenidos subidos a sus redes sociales.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">7.</span> Limitación de Responsabilidad y Exclusión de Garantías</h2>
          <p>
            El Servicio se proporciona "tal cual" y "según disponibilidad". No garantizamos que la conexión con las APIs de terceros (Meta, OpenAI, Google) sea ininterrumpida o libre de errores derivados de caídas externas de servicio de dichos proveedores. En ningún caso seremos responsables por daños indirectos, pérdida de ingresos o sanciones aplicadas por Meta debido al uso indebido por parte del usuario.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">8.</span> Modificaciones de los Términos y Contacto</h2>
          <p>
            Nos reservamos el derecho de modificar estos Términos periódicamente para reflejar cambios legales o técnicos. La fecha de última actualización en la parte superior reflejará la versión vigente.
          </p>
          <div style="background: rgba(255,255,255,0.04); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); font-size: 0.88rem; color: #f1f5f9;">
            📧 <strong>Consultas Legales y Soporte:</strong> <a href="mailto:soporte@mentestoica.app" style="color: var(--accent-cyan); text-decoration: none;">soporte@mentestoica.app</a>
          </div>
        </div>
      </div>

      <!-- ENGLISH CONTENT -->
      <div id="content-en" style="display: none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Acceptance of Terms</h2>
          <p>
            By accessing or using <strong>SocialBoost AI / Mente Estoica</strong> (the "Service" or "Platform"), you agree to be bound by these Terms of Service and our <a href="privacy-policy.php" style="color: var(--accent-cyan);">Privacy Policy</a>. If you do not agree to these terms, you may not use the Service.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Service Overview & AI Assistance</h2>
          <p>
            SocialBoost AI provides intelligent community management software that assists creators and brand managers with intent categorization, Meta Graph API analytics, and automated/suggested AI replies.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Meta Platforms Integration & Compliance</h2>
          <p>
            Users must comply with all Meta Developer Platform Terms and Community Guidelines. Users must be authorized administrators of connected Facebook Pages and Instagram Business Accounts and shall not use the service for spam or unsolicited mass messaging.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> AI Generated Content Disclaimer</h2>
          <div class="legal-highlight-box">
            <p><strong>AI Copilot Functionality:</strong> AI-generated responses (via Google Gemini / OpenAI) are suggestions to assist creators. The user retains ultimate oversight and responsibility for automated and published social interactions.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Acceptable Use & Prohibitions</h2>
          <p>
            Users may not deploy the Platform for unlawful activities, harassment, hate speech, API abuse, security circumvention, or unauthorized commercial reselling.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> Limitation of Liability</h2>
          <p>
            The Service is provided "AS IS". We are not liable for indirect damages, service interruptions from third-party APIs (Meta, Google, OpenAI), or external platform policy enforcements resulting from user actions.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">7.</span> Contact Us</h2>
          <div style="background: rgba(255,255,255,0.04); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); font-size: 0.88rem; color: #f1f5f9;">
            📧 <strong>Legal Support:</strong> <a href="mailto:soporte@mentestoica.app" style="color: var(--accent-cyan); text-decoration: none;">soporte@mentestoica.app</a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script>
    function setLanguage(lang) {
      document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
      if (lang === 'es') {
        document.querySelector('.lang-btn:nth-child(1)').classList.add('active');
        document.getElementById('content-es').style.display = 'block';
        document.getElementById('content-en').style.display = 'none';
        document.getElementById('page-title').textContent = 'Términos y Condiciones del Servicio';
        document.getElementById('page-updated').textContent = 'Última actualización: 31 de Agosto de 2026 • Versión 2.4 (Cumplimiento Meta Graph API)';
      } else {
        document.querySelector('.lang-btn:nth-child(2)').classList.add('active');
        document.getElementById('content-es').style.display = 'none';
        document.getElementById('content-en').style.display = 'block';
        document.getElementById('page-title').textContent = 'Terms of Service';
        document.getElementById('page-updated').textContent = 'Last Updated: August 31, 2026 • Version 2.4 (Meta Graph API Compliant)';
      }
    }
  </script>

</body>
</html>
