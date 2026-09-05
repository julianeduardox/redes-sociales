<?php
/**
 * Política de Privacidad, Tratamiento de Datos & Cookies / Privacy & Cookie Policy
 * XINDRO — Blindaje Jurídico Global (RGPD / GDPR, LOPD-GDD, EU AI Act, CCPA/CPRA, LGPD Brasil y Meta Graph API).
 */
require_once __DIR__ . '/config/security.php';
Security::applySecurityHeaders(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Política de Privacidad, IA y Cookies | XINDRO AI Platform</title>
  <meta name="description" content="Política de Privacidad Integral, Tratamiento de Datos con Inteligencia Artificial, Cumplimiento RGPD/CCPA y Uso de Cookies de XINDRO.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <style>
    .legal-container {
      max-width: 980px;
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
      margin-bottom: 14px;
    }
    .legal-section ul {
      padding-left: 24px;
      margin-bottom: 18px;
    }
    .cookie-table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
      font-size: 0.88rem;
      background: rgba(0, 0, 0, 0.25);
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #1f2937;
    }
    .cookie-table th, .cookie-table td {
      padding: 14px 18px;
      text-align: left;
      border-bottom: 1px solid #1f2937;
    }
    .cookie-table th {
      background: rgba(124, 58, 237, 0.15);
      color: #e0e7ff;
      font-weight: 700;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    .legal-highlight-box {
      background: rgba(124, 58, 237, 0.08);
      border: 1px solid rgba(124, 58, 237, 0.3);
      border-left: 4px solid #7c3aed;
      border-radius: 12px;
      padding: 18px 22px;
      margin: 22px 0;
    }
    .legal-highlight-box p {
      margin-bottom: 0;
      color: #e0e7ff;
      font-size: 0.92rem;
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
      transition: color 0.2s;
    }
    .back-nav:hover {
      color: #fff;
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
          <h1 class="legal-title" id="page-title">Política de Privacidad, IA y Cookies</h1>
          <div class="legal-updated" id="page-updated">Última actualización: Septiembre de 2026 • Versión 4.0 (Cumplimiento RGPD, CCPA, LGPD, EU AI Act & Meta Graph API)</div>
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
          <h2><span class="sec-num">1.</span> Responsable del Tratamiento y Delegado de Protección de Datos</h2>
          <p>
            <strong>XINDRO</strong> (en adelante "la Plataforma" o "nosotros") asume la responsabilidad como Responsable del Tratamiento de los datos personales recopilados a través de nuestro sitio web y servicios SaaS. Garantizamos el estricto cumplimiento del Reglamento General de Protección de Datos de la Unión Europea (RGPD / GDPR 2016/679), la Ley Orgánica 3/2018 (LOPD-GDD), la Ley de Privacidad del Consumidor de California (CCPA/CPRA) y la Lei Geral de Proteção de Dados de Brasil (LGPD).
          </p>
          <p>
            Para cualquier solicitud de privacidad o ejercicio de derechos, contacte a nuestro Delegado de Protección de Datos (DPO) en: <a href="mailto:privacy@xindro.app" style="color: #818cf8; text-decoration: underline; font-weight: 700;">privacy@xindro.app</a>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Tratamiento de Datos mediante Modelos de Inteligencia Artificial</h2>
          <p>
            En cumplimiento del <strong>Reglamento de Inteligencia Artificial de la UE (EU AI Act 2024/1689)</strong>, informamos con total transparencia cómo procesamos los datos mediante IA:
          </p>
          <ul>
            <li><strong>Datos Analizados por la IA:</strong> Texto de comentarios públicos en publicaciones de Facebook e Instagram, nombres de usuario públicos (*handles*) y contexto de las publicaciones vinculadas.</li>
            <li><strong>Finalidad Exclusiva:</strong> Clasificación de intención comercial (identificación de preguntas sobre precios, soporte o felicitaciones), cálculo de sentimiento y redacción asistida de respuestas adaptadas al tono de marca del cliente.</li>
            <li><strong>Compromiso de No Entrenamiento:</strong> <strong>NO utilizamos los datos privados, comentarios ni prompts de nuestros clientes para entrenar o reentrenar modelos públicos de terceros</strong> (OpenRouter / Anthropic / DeepSeek / OpenAI). Las llamadas a las APIs de IA se ejecutan bajo acuerdos de cero retención para entrenamiento (*Zero Data Retention Agreements*).</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Bases Jurídicas del Tratamiento (Art. 6 RGPD)</h2>
          <p>El tratamiento de sus datos se fundamenta en:</p>
          <ul>
            <li><strong>Ejecución de Contrato (Art. 6.1.b RGPD):</strong> Necesario para prestar los servicios de moderación y respuesta de redes sociales solicitados.</li>
            <li><strong>Consentimiento Explícito (Art. 6.1.a RGPD):</strong> Para la activación de cookies no esenciales y el enlace voluntario de cuentas de Meta.</li>
            <li><strong>Interés Legítimo (Art. 6.1.f RGPD):</strong> Para garantizar la ciberseguridad, prevención de ataques de fuerza bruta y mitigación de spam.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Transferencias Internacionales y Subprocesadores</h2>
          <p>
            Para proveer el Servicio, transferimos fragmentos de texto procesados a los siguientes subprocesadores ubicados en Estados Unidos, todos certificados bajo el <strong>EU-U.S. Data Privacy Framework (DPF)</strong> y Cláusulas Contractuales Tipo (SCC):
          </p>
          <ul>
            <li><strong>OpenRouter LLC / Subprocesadores IA Asociados (EE.UU.):</strong> Enrutamiento unificado de inferencia de modelos LLM (Anthropic, DeepSeek, OpenAI, Meta).</li>
            <li><strong>Meta Platforms, Inc. / Meta Platforms Ireland Ltd. (EE.UU. / Irlanda):</strong> Ingesta y publicación mediante Meta Graph API.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Política de Cookies y Tecnologías de Rastreo</h2>
          <p>
            Utilizamos cookies técnicas necesarias y cookies opcionales conforme a las directrices de la AEPD y el Comité Europeo de Protección de Datos (CEPD):
          </p>
          <table class="cookie-table">
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Finalidad</th>
                <th>Duración</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>PHPSESSID</code> / <code>csrf_token</code></td>
                <td>Técnica (Esencial)</td>
                <td>Gestión de sesión segura y protección contra ataques CSRF.</td>
                <td>Sesión</td>
                <td><span style="color:#34d399; font-weight:bold;">Obligatoria</span></td>
              </tr>
              <tr>
                <td><code>rate_limit_*</code></td>
                <td>Seguridad (Esencial)</td>
                <td>Mitigación de ataques de denegación de servicio y wallet drain.</td>
                <td>1 a 60 min</td>
                <td><span style="color:#34d399; font-weight:bold;">Obligatoria</span></td>
              </tr>
              <tr>
                <td><code>cookie_consent</code></td>
                <td>Funcional</td>
                <td>Almacenar las preferencias de cookies elegidas por el usuario.</td>
                <td>12 meses</td>
                <td><span style="color:#34d399; font-weight:bold;">Obligatoria</span></td>
              </tr>
              <tr>
                <td><code>sb_lang</code> / <code>active_brand</code></td>
                <td>Personalización</td>
                <td>Recordar el idioma seleccionado (ES/EN/PT) y la marca activa.</td>
                <td>12 meses</td>
                <td><span style="color:#a78bfa; font-weight:bold;">Opcional</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> Derechos ARCO-POL (Acceso, Rectificación, Supresión y Olvido)</h2>
          <p>
            Usted puede ejercer en cualquier momento sus derechos de <strong>Acceso, Rectificación, Cancelación, Oposición, Portabilidad, Limitación y Supresión</strong> enviando un correo a <a href="mailto:privacy@xindro.app" style="color: #818cf8; text-decoration: underline;">privacy@xindro.app</a> indicando su solicitud.
          </p>
          <p>
            Para la eliminación específica de datos de Meta, consulte nuestras <a href="data-deletion.php" style="color: #818cf8; text-decoration: underline; font-weight: 700;">Instrucciones Oficiales de Eliminación de Datos (Meta)</a>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">7.</span> Capítulo Específico CCPA/CPRA (California) & LGPD (Brasil)</h2>
          <div class="legal-highlight-box">
            <p><strong>DO NOT SELL OR SHARE MY PERSONAL INFORMATION:</strong> XINDRO no vende ni comparte información personal de los usuarios con corredores de datos (*data brokers*) ni con fines de publicidad conductual cruzada.</p>
          </div>
          <p>
            Los residentes de California y Brasil gozan de los derechos reconocidos en la CCPA y la LGPD, incluyendo el derecho a conocer las categorías de datos recolectados, derecho a la eliminación y derecho a no ser discriminados por el ejercicio de sus derechos de privacidad.
          </p>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN INGLÉS -->
      <!-- ================================================================= -->
      <div id="content-en" style="display:none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Data Controller & Privacy Officer</h2>
          <p>
            <strong>XINDRO</strong> operates as the Data Controller under the EU General Data Protection Regulation (GDPR 2016/679), California Consumer Privacy Act (CCPA/CPRA), and Brazilian LGPD. You may reach our Data Protection Officer at: <a href="mailto:privacy@xindro.app" style="color: #818cf8; text-decoration: underline; font-weight: 700;">privacy@xindro.app</a>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Artificial Intelligence Data Processing & EU AI Act</h2>
          <p>
            In accordance with <strong>EU AI Act Regulation 2024/1689</strong>:
          </p>
          <ul>
            <li><strong>Processed Data:</strong> Public social media comments, user handles, and publication context.</li>
            <li><strong>Purpose:</strong> Commercial intent classification, sentiment scoring, and assistive copilot generation.</li>
            <li><strong>Zero Training Policy:</strong> We do <strong>NOT</strong> use customer data or social comments to train public generative foundation models.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> International Transfers & Subprocessors</h2>
          <p>
            Data transfers to third-party AI sub-processors located in the US (OpenRouter LLC, Meta Platforms Inc.) occur strictly under the <strong>EU-U.S. Data Privacy Framework (DPF)</strong> and Standard Contractual Clauses (SCCs).
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Meta Data Deletion Instructions</h2>
          <p>
            To purge all data ingested via Meta Graph API, visit our automated <a href="data-deletion.php" style="color: #818cf8; text-decoration: underline; font-weight: 700;">Meta User Data Deletion Instructions & Callback</a>.
          </p>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN PORTUGUÉS -->
      <!-- ================================================================= -->
      <div id="content-pt" style="display:none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Controlador de Dados e Conformidade LGPD</h2>
          <p>
            A <strong>XINDRO</strong> atua em total conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018) e o RGPD da União Europeia. Contato do DPO: <a href="mailto:privacy@xindro.app" style="color: #818cf8; text-decoration: underline; font-weight: 700;">privacy@xindro.app</a>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Processamento de IA e Não Utilização para Treinamento</h2>
          <p>
            Os comentários públicos são processados exclusivamente para fins de atendimento e classificação. <strong>Não utilizamos os dados de clientes para treinar modelos públicos de IA</strong>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Exclusão de Dados da Meta</h2>
          <p>
            Para solicitar a exclusão de dados da Meta, acesse nossas <a href="data-deletion.php" style="color: #818cf8; text-decoration: underline; font-weight: 700;">Instruções de Exclusão de Dados</a>.
          </p>
        </div>
      </div>

    </div>
  </div>

  <script>
    function setLanguage(lang) {
      document.querySelectorAll('.lang-btn').forEach(b => b.classList.remove('active'));
      document.getElementById('content-es').style.display = 'none';
      document.getElementById('content-en').style.display = 'none';
      document.getElementById('content-pt').style.display = 'none';

      if (lang === 'es') {
        document.getElementById('btn-es').classList.add('active');
        document.getElementById('content-es').style.display = 'block';
        document.getElementById('page-title').textContent = 'Política de Privacidad, IA y Cookies';
        document.getElementById('page-updated').textContent = 'Última actualización: Septiembre de 2026 • Versión 4.0 (Cumplimiento RGPD, CCPA, LGPD, EU AI Act & Meta Graph API)';
      } else if (lang === 'en') {
        document.getElementById('btn-en').classList.add('active');
        document.getElementById('content-en').style.display = 'block';
        document.getElementById('page-title').textContent = 'Privacy Policy, AI Processing & Cookies';
        document.getElementById('page-updated').textContent = 'Last Updated: September 2026 • Version 4.0 (GDPR, CCPA, LGPD, EU AI Act & Meta Graph API Compliant)';
      } else if (lang === 'pt') {
        document.getElementById('btn-pt').classList.add('active');
        document.getElementById('content-pt').style.display = 'block';
        document.getElementById('page-title').textContent = 'Política de Privacidade, IA e Cookies';
        document.getElementById('page-updated').textContent = 'Última atualização: Setembro de 2026 • Versão 4.0 (Conformidade com RGPD, LGPD e Leis de IA)';
      }
    }
  </script>

</body>
</html>
