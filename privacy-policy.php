<?php
/**
 * Política de Privacidad / Privacy Policy
 * Cumplimiento oficial para Meta Graph API, App Review, GDPR y CCPA.
 */
require_once __DIR__ . '/config/security.php';
Security::applySecurityHeaders(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Política de Privacidad | SocialBoost AI & Mente Estoica</title>
  <meta name="description" content="Política de Privacidad y Tratamiento de Datos para la aplicación SocialBoost AI y la integración con Meta Graph API.">
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
          <h1 class="legal-title" id="page-title">Política de Privacidad</h1>
          <div class="legal-updated" id="page-updated">Última actualización: 31 de Agosto de 2026 • Versión 2.4 (Cumplimiento Meta Graph API & GDPR)</div>
        </div>

        <div class="lang-switcher">
          <button class="lang-btn active" onclick="setLanguage('es')">Español</button>
          <button class="lang-btn" onclick="setLanguage('en')">English</button>
        </div>
      </div>

      <!-- SPANISH CONTENT -->
      <div id="content-es">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Introducción y Responsable del Tratamiento</h2>
          <p>
            Bienvenido a <strong>SocialBoost AI / Mente Estoica</strong> (en adelante, la "Plataforma", "nosotros" o "la Aplicación"). Respetamos profundamente la privacidad de nuestros usuarios y nos comprometemos a proteger sus datos personales de conformidad con las leyes internacionales de protección de datos (incluidos el RGPD / GDPR de la Unión Europea, la Ley de Privacidad del Consumidor de California / CCPA y los Términos de la Plataforma de Desarrolladores de Meta).
          </p>
          <p>
            Esta Política de Privacidad describe cómo recopilamos, utilizamos, almacenamos, procesamos y protegemos la información obtenida a través del sitio web y mediante la integración oficial con la <strong>Meta Graph API</strong> (Facebook e Instagram).
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Datos que Recopilamos a través de Meta Graph API</h2>
          <p>
            Para brindar servicios de automatización de comentarios, análisis de intención con Inteligencia Artificial y visualización de estadísticas de audiencia, recopilamos únicamente los datos mínimos indispensables autorizados por el usuario mediante OAuth:
          </p>
          <ul>
            <li><strong>Información de Páginas y Cuentas Profesionales:</strong> Identificador de la Página de Facebook (Page ID), Identificador de la cuenta de Instagram Business (IG User ID), nombre público y foto de perfil.</li>
            <li><strong>Contenido de Publicaciones y Medios:</strong> Identificadores de publicaciones (`media_id`, `post_id`), texto de pies de foto (captions), tipo de medio (imagen, video, carrusel) y enlaces permanentes públicos (`permalink`).</li>
            <li><strong>Interacciones y Comentarios de la Audiencia:</strong> Texto de los comentarios públicos dejados por los usuarios en las publicaciones vinculadas, nombre de usuario público del autor (`username`), identificador del comentario (`comment_id`), conteo de likes y marca de tiempo (`timestamp`).</li>
            <li><strong>Métricas e Insights Agregados de Meta:</strong> Métricas anónimas y cuantitativas proporcionadas por Meta Graph API, tales como número de impresiones (`impressions`), alcance único (`reach`), guardados (`saved`), reproducciones y tasa de interacción (`engagement rate`).</li>
          </ul>
          <div class="legal-highlight-box">
            <p><strong>Nota importante sobre Privacidad:</strong> NO recopilamos mensajes directos privados (DMs), números de teléfono, correos electrónicos personales de seguidores, contraseñas de cuentas, datos de tarjetas de crédito ni información confidencial no relacionada con las publicaciones públicas del creador.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Finalidad y Uso de la Información</h2>
          <p>La información recopilada se utiliza exclusivamente para los siguientes fines:</p>
          <ul>
            <li><strong>Análisis y Moderación Inteligente:</strong> Clasificar el sentimiento de los comentarios (apoyo emocional, consultas reflexivas, agradecimientos o leads) para priorizar interacciones comunitarias valiosas.</li>
            <li><strong>Generación Asistida de Respuestas con IA:</strong> Proporcionar sugerencias de respuestas personalizadas que respeten la voz de marca estoica y motivacional del creador.</li>
            <li><strong>Publicación de Respuestas en Meta:</strong> Enviar las respuestas aprobadas por el usuario o autorizadas en piloto automático hacia la API de Meta para interactuar con la audiencia.</li>
            <li><strong>Visualización de Rendimiento:</strong> Mostrar métricas analíticas de alcance y guardados para ayudar al creador a optimizar su estrategia de contenido.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Procesamiento con Inteligencia Artificial y Proveedores Externos</h2>
          <p>
            Para la interpretación de comentarios y generación de respuestas, la Plataforma se conecta mediante canales seguros cifrados (TLS 1.2+) con proveedores de IA de primer nivel:
          </p>
          <ul>
            <li><strong>Google Gemini API / OpenAI API:</strong> Se transmiten únicamente fragmentos de texto de los comentarios públicos y el pie de foto de la publicación para su análisis semántico. Ningún dato se utiliza para reentrenar modelos públicos de terceros ni se comercializa.</li>
            <li><strong>Meta Platforms, Inc.:</strong> Para la sincronización de Webhooks y publicación de respuestas a través de la Graph API oficial.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Seguridad, Almacenamiento y Retención de Datos</h2>
          <p>
            Implementamos estrictas medidas de seguridad técnicas y organizativas para proteger los datos contra accesos no autorizados, alteración o divulgación:
          </p>
          <ul>
            <li><strong>Cifrado y Protocolo Seguro:</strong> Todas las comunicaciones se realizan bajo protocolo HTTPS / TLS con verificación estricta de certificados SSL.</li>
            <li><strong>Protección de Base de Datos:</strong> Las bases de datos locales están blindadas con directivas de servidor que impiden el acceso HTTP directo y cuentan con permisos de lectura restringidos (`0640`).</li>
            <li><strong>Tokens de Acceso Cifrados:</strong> Los Access Tokens de Meta se almacenan de forma segura y se enmascaran en todas las vistas de administración.</li>
            <li><strong>Retención Mínima:</strong> Los registros de comentarios se conservan únicamente durante el tiempo necesario para la gestión comunitaria y pueden ser purgados en cualquier momento por el usuario.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> Derechos del Usuario y Eliminación de Datos (Data Deletion)</h2>
          <p>
            De conformidad con las normativas internacionales y los requisitos de Meta, usted tiene derecho a:
          </p>
          <ul>
            <li>Acceder a los datos personales almacenados en la Plataforma.</li>
            <li>Solicitar la rectificación o actualización de su información.</li>
            <li>Solicitar la <strong>eliminación total e irrevocable</strong> de todos sus datos y comentarios asociados.</li>
            <li>Revocar el acceso de la aplicación a su cuenta de Meta en cualquier momento desde la configuración de Facebook o Instagram.</li>
          </ul>
          <div class="legal-highlight-box">
            <p>Para solicitar la eliminación de datos o consultar el estado de una solicitud, visite nuestra página oficial de <a href="data-deletion.php" style="color: #a5b4fc; font-weight: 700; text-decoration: underline;">Instrucciones de Eliminación de Datos de Usuario (Data Deletion)</a>.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">7.</span> Contacto del Responsable de Privacidad</h2>
          <p>
            Si tiene alguna pregunta, inquietud o solicitud referente a esta Política de Privacidad o al tratamiento de sus datos personales, puede ponerse en contacto con nuestro Oficial de Protección de Datos en:
          </p>
          <div style="background: rgba(255,255,255,0.04); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); font-size: 0.88rem; color: #f1f5f9;">
            📧 <strong>Correo Electrónico de Privacidad & Soporte:</strong> <a href="mailto:soporte@mentestoica.app" style="color: var(--accent-cyan); text-decoration: none;">soporte@mentestoica.app</a><br>
            🏢 <strong>Plataforma:</strong> SocialBoost AI / Mente Estoica Automation Engine<br>
            🌐 <strong>Portal de Privacidad:</strong> <a href="privacy-policy.php" style="color: var(--accent-cyan);">https://tudominio.com/privacy-policy.php</a>
          </div>
        </div>
      </div>

      <!-- ENGLISH CONTENT (FOR META APP REVIEW AUDITORS) -->
      <div id="content-en" style="display: none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Introduction & Data Controller</h2>
          <p>
            Welcome to <strong>SocialBoost AI / Mente Estoica</strong> (hereinafter referred to as the "Platform", "we", "us", or "our"). We deeply respect user privacy and are committed to safeguarding personal data in full compliance with global data protection laws (including GDPR, CCPA, and Meta Developer Platform Terms).
          </p>
          <p>
            This Privacy Policy details how we collect, use, process, store, and protect information received through our web application and official integration with the <strong>Meta Graph API</strong> (Facebook & Instagram).
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Information We Collect via Meta Graph API</h2>
          <p>
            To provide AI-assisted community management, sentiment analysis, and audience performance metrics, we only collect the minimum necessary data authorized by the user through Meta OAuth:
          </p>
          <ul>
            <li><strong>Page & Professional Account Info:</strong> Facebook Page ID, Instagram Business Account ID, public page name, and profile picture URL.</li>
            <li><strong>Media & Post Data:</strong> Media IDs, post captions, media type (image, video, carousel), timestamps, and public permalinks.</li>
            <li><strong>Audience Comments & Interactions:</strong> Text of public comments posted on connected posts, commenter's public username, comment ID, like count, and timestamp.</li>
            <li><strong>Aggregated Meta Insights:</strong> Non-identifying statistical metrics provided by Meta Graph API, including reach, impressions, saved count, video views, and engagement rates.</li>
          </ul>
          <div class="legal-highlight-box">
            <p><strong>Important Privacy Notice:</strong> We do NOT collect private direct messages (DMs), phone numbers, personal emails of followers, account passwords, credit card information, or sensitive private data.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Purpose and Use of Data</h2>
          <p>We process collected information exclusively for the following purposes:</p>
          <ul>
            <li><strong>Sentiment & Intent Categorization:</strong> Classifying incoming comments (support inquiries, philosophical questions, community feedback, or leads) to prioritize meaningful engagement.</li>
            <li><strong>AI-Assisted Reply Suggestions:</strong> Generating thoughtful, philosophical response recommendations aligned with the creator's brand tone.</li>
            <li><strong>Publishing Replies to Meta:</strong> Submitting creator-approved or automated responses back to Facebook and Instagram via Meta Graph API endpoints.</li>
            <li><strong>Performance Analytics:</strong> Aggregating post reach, impressions, and saves to help creators evaluate content reception.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> AI Processing & Third-Party Service Providers</h2>
          <p>
            Data transmission to AI processing engines (Google Gemini API / OpenAI) is conducted over secure encrypted TLS 1.2+ channels. Only public comment snippets and post captions are evaluated. We do not sell user data, nor is it used to train third-party public models.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Data Security & Retention</h2>
          <p>
            We enforce industry-standard security safeguards including TLS encryption, restricted database permissions (`0640`), server-level access blocking (`.htaccess`), and masked API token storage. Data is retained only as long as necessary to provide community management services.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> User Rights & Meta Data Deletion Instructions</h2>
          <p>
            Users and audience members have the right to access, rectify, or request complete deletion of their stored data. Users may revoke application access at any time via Facebook/Instagram Settings -> Apps and Websites.
          </p>
          <div class="legal-highlight-box">
            <p>To request data deletion or track a deletion request, please visit our dedicated <a href="data-deletion.php" style="color: #a5b4fc; font-weight: 700; text-decoration: underline;">User Data Deletion Request Page</a>.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">7.</span> Contact Information</h2>
          <div style="background: rgba(255,255,255,0.04); padding: 16px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); font-size: 0.88rem; color: #f1f5f9;">
            📧 <strong>Privacy & Support Email:</strong> <a href="mailto:soporte@mentestoica.app" style="color: var(--accent-cyan); text-decoration: none;">soporte@mentestoica.app</a><br>
            🏢 <strong>Application:</strong> SocialBoost AI / Mente Estoica Automation Engine<br>
            🌐 <strong>Privacy Portal:</strong> <a href="privacy-policy.php" style="color: var(--accent-cyan);">https://yourdomain.com/privacy-policy.php</a>
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
        document.getElementById('page-title').textContent = 'Política de Privacidad';
        document.getElementById('page-updated').textContent = 'Última actualización: 31 de Agosto de 2026 • Versión 2.4 (Cumplimiento Meta Graph API & GDPR)';
      } else {
        document.querySelector('.lang-btn:nth-child(2)').classList.add('active');
        document.getElementById('content-es').style.display = 'none';
        document.getElementById('content-en').style.display = 'block';
        document.getElementById('page-title').textContent = 'Privacy Policy';
        document.getElementById('page-updated').textContent = 'Last Updated: August 31, 2026 • Version 2.4 (Meta Graph API & GDPR Compliant)';
      }
    }
  </script>

</body>
</html>
