<?php
/**
 * Términos y Condiciones de Uso / Terms of Service
 * XINDRO AI Platform — Blindaje Legal Integral (EU AI Act 2024/1689, Meta Graph API, GDPR/CCPA).
 */
require_once __DIR__ . '/config/security.php';
Security::applySecurityHeaders(false);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Términos del Servicio & Blindaje IA | XINDRO AI Platform</title>
  <meta name="description" content="Términos y Condiciones Generales de Uso, Blindaje de Inteligencia Artificial (EU AI Act), Limitación de Responsabilidad y Cumplimiento con Meta Graph API de XINDRO.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
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
      margin-bottom: 14px;
    }
    .legal-section ul {
      padding-left: 24px;
      margin-bottom: 18px;
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
    .legal-alert-box {
      background: rgba(239, 68, 68, 0.08);
      border: 1px solid rgba(239, 68, 68, 0.3);
      border-left: 4px solid #ef4444;
      border-radius: 12px;
      padding: 18px 22px;
      margin: 22px 0;
    }
    .legal-alert-box p {
      margin-bottom: 0;
      color: #fecaca;
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
          <h1 class="legal-title" id="page-title">Términos y Condiciones del Servicio</h1>
          <div class="legal-updated" id="page-updated">Última actualización: Septiembre de 2026 • Versión 3.5 (Cumplimiento EU AI Act 2024/1689 & Meta Graph API)</div>
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
          <h2><span class="sec-num">1.</span> Aceptación Vinculante y Naturaleza del Servicio</h2>
          <p>
            Al registrarse, acceder o utilizar <strong>XINDRO</strong> (en adelante, la "Plataforma" o el "Servicio"), usted (el "Usuario" o "Cliente") celebra un contrato legalmente vinculante sujeto a estos Términos de Servicio y a nuestra <a href="privacy-policy.php" style="color: #818cf8; text-decoration: underline;">Política de Privacidad</a>. Si utiliza el Servicio en representación de una empresa o agencia, usted declara y garantiza contar con la autoridad legal para vincular a dicha entidad.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Cumplimiento con el Reglamento de Inteligencia Artificial (EU AI Act 2024/1689)</h2>
          <p>
            En estricto apego al <strong>Reglamento (UE) 2024/1689 (Ley de Inteligencia Artificial de la Unión Europea)</strong> y a las normativas internacionales de gobernanza de IA:
          </p>
          <ul>
            <li><strong>Declaración de Uso de IA:</strong> XINDRO utiliza modelos fundacionales de Inteligencia Artificial generativa basados en procesamiento de lenguaje natural probabilístico (incluyendo Google Gemini y OpenAI GPT), así como motores heurísticos de clasificación de intenciones comerciales.</li>
            <li><strong>Transparencia hacia Usuarios Finales (Art. 50 EU AI Act):</strong> El Cliente asume la obligación legal de informar a su comunidad y seguidores de que las interacciones pueden ser asistidas o redactadas mediante sistemas de Inteligencia Artificial.</li>
            <li><strong>Supervisión Humana Obligatoria (Human-in-the-Loop):</strong> XINDRO provee una herramienta de asistencia. El Cliente es el <strong>único editor y responsable editorial</strong> de cualquier contenido generado o emitido mediante el modo Copiloto o Piloto Automático (*Autopilot*).</li>
            <li><strong>Descargo por Alucinaciones e Imprecisiones:</strong> Los modelos de lenguaje masivo (LLMs) pueden generar respuestas imprecisas, inexactitudes fácticas o "alucinaciones". XINDRO no garantiza la exactitud absoluta de las respuestas y queda <strong>totalmente exonerada</strong> de cualquier reclamación derivada de precios erróneos, compromisos comerciales involuntarios o interpretaciones emitidas por la IA.</li>
          </ul>
          <div class="legal-alert-box">
            <p><strong>⚠️ Prohibición de Usos Ilícitos:</strong> Queda estrictamente prohibido utilizar la IA de XINDRO para generar desinformación, manipulación electoral, contenido difamatorio, discursos de odio, material engañoso (*deepfakes*), o actividades clasificadas como de "Riesgo Inaceptable" por la legislación aplicable.</p>
          </div>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Integración con Meta Platforms (Facebook & Instagram) y Desvinculación Oficial</h2>
          <p>
            XINDRO opera como una plataforma SaaS de terceros construida sobre las APIs públicas y oficiales de Meta Platforms, Inc. (Meta Graph API).
          </p>
          <ul>
            <li><strong>Declaración de No Afiliación:</strong> XINDRO es un software independiente y <strong>NO está respaldado, patrocinado, afiliado ni administrado por Meta Platforms, Inc., Facebook, Instagram ni Google LLC</strong>.</li>
            <li><strong>Obligaciones del Usuario:</strong> El Usuario garantiza ser el titular legítimo o administrador de las Páginas de Facebook y Cuentas de Instagram Business/Creator vinculadas, y se compromete a respetar en todo momento los <em>Términos de la Plataforma de Desarrolladores de Meta</em> y las <em>Normas Comunitarias de Instagram</em>.</li>
            <li><strong>Exclusión por Sanciones Externas:</strong> XINDRO no se responsabiliza por bloqueos de cuentas, limitaciones de API, cambios en los algoritmos o sanciones disciplinarias impuestas por Meta hacia la cuenta del Usuario.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Exclusión Total de Garantías ("AS IS" y "AS AVAILABLE")</h2>
          <p>
            EL SERVICIO SE PROPORCIONA "TAL CUAL" Y "SEGÚN DISPONIBILIDAD", SIN GARANTÍAS DE NINGÚN TIPO, YA SEAN EXPRESAS, IMPLÍCITAS O LEGALES. XINDRO NO GARANTIZA QUE EL SERVICIO SEA ININTERRUMPIDO, LIBRE DE ERRORES, QUE LA IA LOGRE NIVELES ESPECÍFICOS DE VENTAS O ENGAGEMENT, NI QUE LAS APIS DE TERCEROS (META, OPENAI, GOOGLE) MANTENGAN SU DISPONIBILIDAD PERMANENTE.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Limitación Estricta de Responsabilidad Financiera (Liability Cap)</h2>
          <div class="legal-highlight-box">
            <p><strong>CLÁUSULA DE TOPE MÁXIMO DE RESPONSABILIDAD:</strong> EN LA MÁXIMA MEDIDA PERMITIDA POR LA LEY APLICABLE, LA RESPONSABILIDAD TOTAL Y ACUMULADA DE XINDRO, SUS DESARROLLADORES, DIRECTORES, EMPLEADOS Y AFILIADOS POR CUALQUIER RECLAMO DERIVADO DE ESTOS TÉRMINOS O DEL USO DEL SOFTWARE ESTARÁ ESTRICTAMENTE LIMITADA AL IMPORTE EFECTIVAMENTE PAGADO POR EL CLIENTE A XINDRO DURANTE LOS <strong>TRES (3) MESES ANTERIORES</strong> AL EVENTO QUE ORIGINÓ EL RECLAMO, O A LA CANTIDAD DE <strong>CINCUENTA DÓLARES ESTADOUNIDENSES ($50.00 USD / €50.00 EUR)</strong> EN CASO DE CUENTAS GRATUITAS O EN PERÍODO DE PRUEBA.</p>
          </div>
          <p>
            EN NINGÚN CASO XINDRO SERÁ RESPONSABLE POR DAÑOS INDIRECTOS, INCIDENTALES, PUNITIVOS, CONSECUENCIALES O ESPECIALES, INCLUYENDO PÉRDIDA DE BENEFICIOS, LUCRO CESANTE, PÉRDIDA DE CLIENTELA O DAÑO REPUTACIONAL.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> Cláusula de Indemnización Cruzada (Hold Harmless)</h2>
          <p>
            El Usuario acepta defender, indemnizar y mantener indemne a XINDRO, sus desarrolladores, agentes y directores frente a cualquier demanda, reclamación de terceros, proceso administrativo, sanción o multa impuesta por autoridades de protección de datos (como la AEPD, FTC o CNIL) o Meta Platforms, Inc., derivada de: (a) el contenido publicado por el usuario o sus agentes de IA; (b) el incumplimiento de estos Términos o leyes vigentes; o (c) la vulneración de derechos de propiedad intelectual de terceros.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">7.</span> Resolución de Controversias y Renuncia a Demandas Colectivas</h2>
          <p>
            Cualquier disputa se resolverá preferentemente mediante negociación amistosa directa. En caso de litigio, el Usuario <strong>renuncia expresamente a iniciar o participar en demandas colectivas (*Class Action Waiver*)</strong> contra XINDRO, sometiéndose a arbitraje o a la jurisdicción de los tribunales competentes establecidos en el domicilio legal de la plataforma.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">8.</span> Canal de Contacto Legal</h2>
          <div style="background: rgba(255,255,255,0.04); padding: 18px; border-radius: 12px; border: 1px solid #1f2937; font-size: 0.9rem; color: #f1f5f9;">
            📧 <strong>Departamento Legal y Cumplimiento Normativo:</strong> <a href="mailto:legal@xindro.app" style="color: #818cf8; text-decoration: none; font-weight: 700;">legal@xindro.app</a> • <a href="mailto:privacy@xindro.app" style="color: #818cf8; text-decoration: none; font-weight: 700;">privacy@xindro.app</a>
          </div>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN INGLÉS -->
      <!-- ================================================================= -->
      <div id="content-en" style="display:none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Binding Agreement & Service Nature</h2>
          <p>
            By creating an account, accessing, or using <strong>XINDRO</strong> (the "Platform" or "Service"), you ("User" or "Client") enter into a legally binding contract governed by these Terms of Service and our <a href="privacy-policy.php" style="color: #818cf8; text-decoration: underline;">Privacy Policy</a>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> EU AI Act Compliance & Artificial Intelligence Disclaimer</h2>
          <p>
            In strict compliance with <strong>EU Regulation 2024/1689 (European Artificial Intelligence Act)</strong> and global AI governance standards:
          </p>
          <ul>
            <li><strong>AI Disclosures:</strong> XINDRO deploys generative natural language models (including Google Gemini and OpenAI GPT) and heuristic intent classifiers.</li>
            <li><strong>Transparency to End-Users (Art. 50 EU AI Act):</strong> Client warrants to provide necessary disclosures to social media audiences indicating that interactions may be assisted or produced by Artificial Intelligence.</li>
            <li><strong>Human-in-the-Loop Requirement:</strong> XINDRO provides assistive technology. The Client remains the <strong>sole legal publisher and editor</strong> responsible for monitoring, configuring, and verifying all automated or suggested content.</li>
            <li><strong>Hallucination & Error Disclaimer:</strong> Large language models are probabilistic and may produce inaccuracies or hallucinations. XINDRO disclaims all liability for incorrect pricing, unintended commitments, or inaccuracies generated by AI models.</li>
          </ul>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Meta Graph API Integration & Independent Disclaimer</h2>
          <p>
            XINDRO is an independent SaaS tool operating over Meta Platforms, Inc. public APIs. <strong>XINDRO is not endorsed, sponsored, affiliated with, or operated by Meta Platforms, Inc., Facebook, Instagram, or Google LLC.</strong> Users must hold legitimate administration rights for connected assets and comply with Meta Developer Terms.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Strict Limitation of Liability (Liability Cap)</h2>
          <div class="legal-highlight-box">
            <p><strong>MAXIMUM LIABILITY CAP:</strong> TO THE FULLEST EXTENT PERMITTED BY LAW, XINDRO'S TOTAL AGGREGATE LIABILITY ARISING OUT OF OR RELATED TO THESE TERMS OR SOFTWARE USAGE SHALL BE STRICTLY CAPPED AT THE TOTAL AMOUNT PAID BY CLIENT IN THE <strong>THREE (3) MONTHS</strong> PRECEDING THE CLAIM, OR <strong>FIFTY US DOLLARS ($50.00 USD)</strong> FOR FREE OR TRIAL ACCOUNTS.</p>
          </div>
          <p>
            IN NO EVENT SHALL XINDRO BE LIABLE FOR INDIRECT, CONSEQUENTIAL, PUNITIVE, OR INCIDENTAL DAMAGES, INCLUDING LOST PROFITS, SOCIAL ACCOUNT RESTRICTIONS, OR REPUTATIONAL DAMAGE.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">5.</span> Indemnification (Hold Harmless) & Class Action Waiver</h2>
          <p>
            User agrees to defend, indemnify, and hold harmless XINDRO, its developers, and officers from any third-party claims, Meta enforcement actions, or regulatory fines arising from User's content, automated outputs, or breach of applicable laws. User expressly waives any right to initiate or join class action lawsuits.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">6.</span> Legal Contact</h2>
          <div style="background: rgba(255,255,255,0.04); padding: 18px; border-radius: 12px; border: 1px solid #1f2937; font-size: 0.9rem; color: #f1f5f9;">
            📧 <strong>Legal & Compliance Desk:</strong> <a href="mailto:legal@xindro.app" style="color: #818cf8; text-decoration: none; font-weight: 700;">legal@xindro.app</a> • <a href="mailto:privacy@xindro.app" style="color: #818cf8; text-decoration: none; font-weight: 700;">privacy@xindro.app</a>
          </div>
        </div>
      </div>

      <!-- ================================================================= -->
      <!-- CONTENIDO EN PORTUGUÉS -->
      <!-- ================================================================= -->
      <div id="content-pt" style="display:none;">
        <div class="legal-section">
          <h2><span class="sec-num">1.</span> Aceitação dos Termos e Natureza do Serviço</h2>
          <p>
            Ao utilizar a plataforma <strong>XINDRO</strong>, você concorda com estes Termos de Serviço e nossa <a href="privacy-policy.php" style="color: #818cf8; text-decoration: underline;">Política de Privacidade</a>.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">2.</span> Conformidade com Leis de IA e Isenção de Responsabilidade</h2>
          <p>
            A XINDRO utiliza modelos de Inteligência Artificial generativa (Google Gemini e OpenAI GPT). O Usuário reconhece que a IA é probabilística e assume a obrigação de <strong>supervisão humana ativa (*Human-in-the-Loop*)</strong>, sendo o único responsável editorial pelo conteúdo publicado em suas redes sociais. A XINDRO fica expressamente isenta de qualquer responsabilidade por alucinações ou imprecisões geradas pelos modelos.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">3.</span> Limitação de Responsabilidade e Indenização</h2>
          <p>
            A responsabilidade total da XINDRO está estritamente limitada ao valor pago pelo cliente nos últimos <strong>3 meses</strong> (ou R$ 250,00 para contas gratuitas). O usuário concorda em indenizar e isentar a XINDRO de quaisquer reclamações de terceiros ou sanções da Meta Platforms.
          </p>
        </div>

        <div class="legal-section">
          <h2><span class="sec-num">4.</span> Contato Jurídico</h2>
          <div style="background: rgba(255,255,255,0.04); padding: 18px; border-radius: 12px; border: 1px solid #1f2937; font-size: 0.9rem; color: #f1f5f9;">
            📧 <strong>Contato Jurídico:</strong> <a href="mailto:legal@xindro.app" style="color: #818cf8; text-decoration: none; font-weight: 700;">legal@xindro.app</a>
          </div>
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
        document.getElementById('page-title').textContent = 'Términos y Condiciones del Servicio';
        document.getElementById('page-updated').textContent = 'Última actualización: Septiembre de 2026 • Versión 3.5 (Cumplimiento EU AI Act 2024/1689 & Meta Graph API)';
      } else if (lang === 'en') {
        document.getElementById('btn-en').classList.add('active');
        document.getElementById('content-en').style.display = 'block';
        document.getElementById('page-title').textContent = 'Terms of Service & AI Liability Protection';
        document.getElementById('page-updated').textContent = 'Last Updated: September 2026 • Version 3.5 (EU AI Act 2024/1689 & Meta Graph API Compliant)';
      } else if (lang === 'pt') {
        document.getElementById('btn-pt').classList.add('active');
        document.getElementById('content-pt').style.display = 'block';
        document.getElementById('page-title').textContent = 'Termos de Serviço e Proteção de IA';
        document.getElementById('page-updated').textContent = 'Última atualização: Setembro de 2026 • Versão 3.5 (Conformidade com Leis de IA e Meta API)';
      }
    }
  </script>

</body>
</html>
