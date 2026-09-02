<?php
/**
 * Política de Privacidad & Cookies / Privacy & Cookie Policy
 * XINDRO — Cumplimiento oficial para Meta Graph API, App Review, GDPR/RGPD, CCPA y LGPD Brasil.
 */
require_once __DIR__ . '/config/security.php';
Security::applySecurityHeaders(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Política de Privacidad y Cookies | XINDRO</title>
  <meta name="description" content="Política de Privacidad, Tratamiento de Datos y Uso de Cookies para la plataforma XINDRO y la integración con Meta Graph API.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <style>
    .legal-container {
      max-width: 960px;
      margin: 0 auto;
      padding: 40px 24px 80px;
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .legal-card {
      background: var(--bg-card, #111827);
      border: 1px solid var(--border-subtle, #1f2937);
      border-radius: 24px;
      padding: 44px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(16px);
      color: #e2e8f0;
    }
    .legal-header {
      border-bottom: 1px solid #1f2937;
      padding-bottom: 24px;
      margin-bottom: 32px;
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      flex-wrap: wrap;
      gap: 16px;
    }
    .legal-title {
      font-size: 2.2rem;
      font-weight: 800;
      color: #fff;
      background: linear-gradient(135deg, #fff 0%, #a5b4fc 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 8px;
    }
    .legal-updated {
      font-size: 0.85rem;
      color: #94a3b8;
    }
    .lang-switcher {
      display: flex;
      gap: 6px;
      background: rgba(255, 255, 255, 0.05);
      padding: 4px;
      border-radius: 12px;
      border: 1px solid #334155;
    }
    .lang-btn {
      background: transparent;
      border: none;
      color: #94a3b8;
      padding: 6px 14px;
      border-radius: 8px;
      font-size: 0.8rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }
    .lang-btn.active {
      background: #7c3aed;
      color: #fff;
    }
    .legal-section {
      margin-bottom: 36px;
    }
    .legal-section h2 {
      font-size: 1.3rem;
      font-weight: 800;
      color: #f8fafc;
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .legal-section h2 .sec-num {
      color: #818cf8;
      font-size: 1.1rem;
    }
    .legal-section p, .legal-section li {
      font-size: 0.94rem;
      line-height: 1.8;
      color: #cbd5e1;
      margin-bottom: 12px;
    }
    .legal-section ul {
      padding-left: 24px;
      margin-bottom: 16px;
    }
    .legal-highlight-box {
      background: rgba(124, 58, 237, 0.1);
      border: 1px solid rgba(139, 92, 246, 0.3);
      border-left: 4px solid #7c3aed;
      border-radius: 12px;
      padding: 18px 22px;
      margin: 20px 0;
    }
    .legal-highlight-box p {
      margin-bottom: 0;
      color: #e2e8f0;
    }
    .cookie-table {
      width: 100%;
      border-collapse: collapse;
      margin: 16px 0 24px;
      font-size: 0.85rem;
    }
    .cookie-table th, .cookie-table td {
      border: 1px solid #334155;
      padding: 10px 14px;
      text-align: left;
    }
    .cookie-table th {
      background: rgba(255, 255, 255, 0.04);
      color: #f8fafc;
      font-weight: 700;
    }
    .back-nav {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #94a3b8;
      text-decoration: none;
      font-size: 0.88rem;
      font-weight: 600;
      margin-bottom: 24px;
      transition: all 0.2s;
    }
    .back-nav:hover {
      color: #fff;
      transform: translateX(-4px);
    }
  </style>
</head>
<body>

  <div class="legal-container">
    <a href="index.php" class="back-nav">
      ← Volver al Inicio (XINDRO)
    </a>

    <div class="legal-card">
      <div class="legal-header">
        <div>
          <h1 class="legal-title" id="page-title">Política de Privacidad y Cookies</h1>
          <div class="legal-updated" id="page-updated">Última actualización: Septiembre de 2026 • Versión 3.0 (Cumplimiento Meta Graph API, RGPD, CCPA y LGPD)</div>
        </div>

        <div class="lang-switcher">
          <button class="lang-btn active" id="btn-es" onclick="setLanguage('es')">🇪🇸 Español</button>
          <button class="lang-btn" id="btn-en" onclick="setLanguage('en')">🇺🇸 English</button>
          <button class="lang-btn" id="btn-pt" onclick="setLanguage('pt')">🇧🇷 Português</button>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN ESPAÑOL -->
      <!-- ================================================================= -->
      <div id="content-es">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Identidad y Responsable del Tratamiento</h2>
          <p>
            Bienvenido a <strong>XINDRO</strong> (propiedad de <em>Xindro Tech, Inc.</em>, en adelante "la Plataforma" o "nosotros"). Nos comprometemos a garantizar la máxima confidencialidad, seguridad e integridad en el tratamiento de los datos personales de nuestros usuarios, creadores digitales y visitantes, en estricto cumplimiento del Reglamento General de Protección de Datos (RGPD / GDPR de la UE), la Ley de Privacidad del Consumidor de California (CCPA) y los Términos de la Plataforma de Desarrolladores de Meta.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Política de Cookies y Tecnologías de Rastreo</h2>
          <p>
            Utilizamos cookies propias y de terceros con el fin de garantizar el funcionamiento técnico de la plataforma, permitir el inicio de sesión seguro, medir el rendimiento de nuestros endpoints de inteligencia artificial y recordar tus preferencias (como el idioma seleccionado).
          </p>

          <table class="cookie-table">
            <thead>
              <tr>
                <th>Categoría</th>
                <th>Finalidad</th>
                <th>Duración</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Técnicas / Esenciales</strong></td>
                <td>Mantener la sesión autenticada, tokens anti-CSRF y protección contra ataques.</td>
                <td>Sesión / 30 días</td>
                <td><span style="color:#34d399; font-weight:bold;">Obligatorias</span></td>
              </tr>
              <tr>
                <td><strong>Analítica & Rendimiento</strong></td>
                <td>Medir latencia de respuesta de la IA, estabilidad de la API y patrones anónimos de uso.</td>
                <td>12 meses</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Opcional (Configurable)</span></td>
              </tr>
              <tr>
                <td><strong>Personalización</strong></td>
                <td>Recordar el idioma elegido (ES/EN/PT), tonos del simulador y preferencias guardadas.</td>
                <td>12 meses</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Opcional (Configurable)</span></td>
              </tr>
            </tbody>
          </table>

          <div class="legal-highlight-box">
            <p>
              💡 Puedes modificar tus preferencias de cookies en cualquier momento haciendo clic en el enlace <strong>"Preferencias de cookies"</strong> ubicado en el pie de página de nuestro sitio web.
            </p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Integración con Meta Graph API (Facebook e Instagram)</h2>
          <p>
            Nuestra plataforma se integra de manera oficial con la API Graph de Meta bajo estrictos protocolos de consentimiento granular:
          </p>
          <ul>
            <li><strong>Permisos Solicitados:</strong> <code>pages_show_list</code>, <code>pages_read_engagement</code>, <code>pages_manage_posts</code>, <code>instagram_basic</code>, <code>instagram_manage_comments</code>.</li>
            <li><strong>Uso Exclusivo:</strong> Los tokens de acceso se almacenan cifrados con AES-256-GCM y se usan únicamente para sincronizar comentarios, calcular horarios de publicación y emitir respuestas bajo las directrices del creador.</li>
            <li><strong>No Venta de Datos:</strong> Nunca vendemos, transferimos ni compartimos datos obtenidos de Meta con terceros anunciantes ni intermediarios.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Eliminación y Retención de Datos</h2>
          <p>
            Cualquier usuario puede solicitar la eliminación total e inmediata de sus datos y tokens en nuestro endpoint verificado: <a href="data-deletion.php" style="color:#818cf8; text-decoration:underline;">Mecanismo de Eliminación de Datos (Meta)</a>.
          </p>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN INGLÉS -->
      <!-- ================================================================= -->
      <div id="content-en" style="display:none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Identity & Data Controller</h2>
          <p>
            Welcome to <strong>XINDRO</strong> (operated by <em>Xindro Tech, Inc.</em>, hereinafter "the Platform" or "we"). We are firmly committed to ensuring the confidentiality, security, and integrity of the personal data of our users, creators, and visitors in strict compliance with the EU General Data Protection Regulation (GDPR), the California Consumer Privacy Act (CCPA), and Meta Developer Platform Terms.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Cookie Policy & Tracking Technologies</h2>
          <p>
            We use first-party and third-party cookies to provide core security functionality, secure session authentication, measure AI latency and endpoint performance, and store your user preferences (such as selected language).
          </p>

          <table class="cookie-table">
            <thead>
              <tr>
                <th>Category</th>
                <th>Purpose</th>
                <th>Duration</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Essential / Security</strong></td>
                <td>Session management, CSRF attack prevention, and brute force defenses.</td>
                <td>Session / 30 days</td>
                <td><span style="color:#34d399; font-weight:bold;">Strictly Required</span></td>
              </tr>
              <tr>
                <td><strong>Analytics & Performance</strong></td>
                <td>Measure AI generation speed, API uptime, and anonymous usage trends.</td>
                <td>12 months</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Optional (Configurable)</span></td>
              </tr>
              <tr>
                <td><strong>Personalization</strong></td>
                <td>Store preferred language (ES/EN/PT), tone settings, and simulator states.</td>
                <td>12 months</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Optional (Configurable)</span></td>
              </tr>
            </tbody>
          </table>

          <div class="legal-highlight-box">
            <p>
              💡 You can change your cookie preferences at any time by clicking the <strong>"Cookie Preferences"</strong> link in the footer of our website.
            </p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Meta Graph API Integration (Instagram & Facebook)</h2>
          <p>
            Our service connects to Meta Graph API following strict granular authorization:
          </p>
          <ul>
            <li><strong>Requested Scopes:</strong> <code>pages_show_list</code>, <code>pages_read_engagement</code>, <code>pages_manage_posts</code>, <code>instagram_basic</code>, <code>instagram_manage_comments</code>.</li>
            <li><strong>Data Usage:</strong> Tokens are encrypted using AES-256-GCM. We only process incoming comments and schedule creator posts according to your configured brand guidelines.</li>
            <li><strong>Zero Data Selling:</strong> We never sell or broker Meta data to third parties.</li>
          </ul>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN PORTUGUÉS -->
      <!-- ================================================================= -->
      <div id="content-pt" style="display:none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Identidade e Controlador de Dados</h2>
          <p>
            Bem-vindo ao <strong>XINDRO</strong> (operado por <em>Xindro Tech, Inc.</em>). Estamos comprometidos com a privacidade, segurança e integridade no tratamento dos dados pessoais de criadores e visitantes, em conformidade com a Lei Geral de Proteção de Dados (LGPD Brasil), o RGPD da União Europeia e os Termos de Desenvolvedores da Meta.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Política de Cookies e Tecnologias de Rastreamento</h2>
          <p>
            Utilizamos cookies para garantir a segurança da plataforma, autenticar sessões, medir a estabilidade da IA e lembrar o idioma preferido do usuário.
          </p>

          <table class="cookie-table">
            <thead>
              <tr>
                <th>Categoria</th>
                <th>Finalidade</th>
                <th>Duração</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><strong>Essenciais / Segurança</strong></td>
                <td>Sessão segura, tokens anti-CSRF e proteção contra ataques.</td>
                <td>Sessão / 30 dias</td>
                <td><span style="color:#34d399; font-weight:bold;">Obrigatórios</span></td>
              </tr>
              <tr>
                <td><strong>Desempenho & Análise</strong></td>
                <td>Monitorar latência da IA e estabilidade da API de forma anônima.</td>
                <td>12 meses</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Opcional</span></td>
              </tr>
              <tr>
                <td><strong>Personalização</strong></td>
                <td>Lembrar idioma (ES/EN/PT) e configurações do simulador.</td>
                <td>12 meses</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Opcional</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Integração com a Meta Graph API</h2>
          <p>
            Todos os tokens do Facebook e Instagram são armazenados com criptografia AES-256-GCM e utilizados exclusivamente para automação autorizada pelo criador.
          </p>
        </div>
      </div>

    </div>
  </div>

  <script>
    function setLanguage(lang) {
      document.getElementById('content-es').style.display = lang === 'es' ? 'block' : 'none';
      document.getElementById('content-en').style.display = lang === 'en' ? 'block' : 'none';
      document.getElementById('content-pt').style.display = lang === 'pt' ? 'block' : 'none';

      document.getElementById('btn-es').className = 'lang-btn' + (lang === 'es' ? ' active' : '');
      document.getElementById('btn-en').className = 'lang-btn' + (lang === 'en' ? ' active' : '');
      document.getElementById('btn-pt').className = 'lang-btn' + (lang === 'pt' ? ' active' : '');
    }
  </script>

</body>
</html>
