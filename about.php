<?php
/**
 * XINDRO — Acerca de Nosotros / About Us (Gamma.app Inspired Architecture)
 * Nuestra Misión, Tecnología de IA, Arquitectura de Seguridad y Valores de Ingeniería.
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

Security::applySecurityHeaders(false);
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

$acceptLang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'es';
$initialLang = 'es';
if (preg_match('/^pt/i', $acceptLang)) {
    $initialLang = 'pt';
} elseif (preg_match('/^en/i', $acceptLang)) {
    $initialLang = 'en';
}
if (!empty($_GET['lang']) && in_array($_GET['lang'], ['es', 'en', 'pt'])) {
    $initialLang = $_GET['lang'];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($initialLang) ?>" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <title id="meta-page-title">Acerca de XINDRO — Nuestra Visión, Tecnología y Misión</title>
  <meta name="description" id="meta-page-desc" content="Conoce la historia, los principios de ingeniería y la arquitectura de IA detrás de XINDRO, el sistema operativo para creadores de contenido y marcas.">
  
  <meta property="og:type" content="website">
  <meta property="og:title" content="Acerca de XINDRO — Revolucionando el Engagement en Redes Sociales">
  <meta property="og:description" content="Descubre cómo ayudamos a creadores y agencias a responder miles de comentarios en la ventana de oro de Meta sin perder el toque humano.">
  
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            display: ['"Syne"', '"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          },
          colors: {
            brand: {
              50: '#F5F3FF',
              100: '#EDE9FE',
              200: '#DDD6FE',
              300: '#C4B5FD',
              400: '#A78BFA',
              500: '#8B5CF6',
              600: '#7C3AED',
              700: '#6D28D9',
              800: '#5B21B6',
              900: '#4C1D95',
            },
            midnight: '#0B0F19',
          },
          boxShadow: {
            'glow-sm': '0 0 20px rgba(139, 92, 246, 0.25)',
            'glow-md': '0 0 30px rgba(139, 92, 246, 0.35)',
            'elevated-card': '0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(226, 232, 240, 0.8)',
          }
        }
      }
    }
  </script>

  <style>
    body {
      background-color: #FFFFFF;
      color: #0B0F19;
      font-family: 'Plus Jakarta Sans', sans-serif;
      overflow-x: hidden;
    }
    .hero-mesh-bg {
      background: radial-gradient(circle at 50% -10%, rgba(139, 92, 246, 0.14) 0%, rgba(248, 250, 252, 0) 65%),
                  radial-gradient(circle at 90% 20%, rgba(56, 189, 248, 0.08) 0%, rgba(255, 255, 255, 0) 50%),
                  radial-gradient(circle at 10% 30%, rgba(168, 85, 247, 0.08) 0%, rgba(255, 255, 255, 0) 50%);
    }
    .gamma-wordmark {
      font-family: 'Syne', 'Plus Jakarta Sans', sans-serif;
      letter-spacing: -0.03em;
      font-weight: 900;
      text-transform: uppercase;
    }
    .gradient-text {
      background: linear-gradient(135deg, #7C3AED 0%, #4F46E5 50%, #06B6D4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .starry-footer-bg {
      background-color: #07090e !important;
      background: radial-gradient(circle at 50% 0%, rgba(139, 92, 246, 0.35) 0%, rgba(15, 23, 42, 0.98) 70%),
                  linear-gradient(180deg, #0f172a 0%, #07090e 100%) !important;
      position: relative;
      color: #cbd5e1;
    }
    .starry-overlay {
      position: relative;
    }
    .starry-overlay::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(2px 2px at 20px 30px, #ffffff, rgba(0,0,0,0)),
                        radial-gradient(2px 2px at 40px 70px, rgba(255,255,255,0.7), rgba(0,0,0,0)),
                        radial-gradient(1.5px 1.5px at 90px 40px, #ffffff, rgba(0,0,0,0)),
                        radial-gradient(2px 2px at 160px 120px, rgba(255,255,255,0.8), rgba(0,0,0,0)),
                        radial-gradient(1.5px 1.5px at 230px 80px, #ffffff, rgba(0,0,0,0)),
                        radial-gradient(2px 2px at 290px 150px, rgba(255,255,255,0.6), rgba(0,0,0,0)),
                        radial-gradient(1.5px 1.5px at 340px 50px, #ffffff, rgba(0,0,0,0)),
                        radial-gradient(2px 2px at 420px 180px, rgba(255,255,255,0.8), rgba(0,0,0,0)),
                        radial-gradient(1.5px 1.5px at 500px 90px, #ffffff, rgba(0,0,0,0));
      background-repeat: repeat;
      background-size: 550px 300px;
      pointer-events: none;
      opacity: 0.75;
      z-index: 1;
    }
    .starry-footer-bg > * {
      position: relative;
      z-index: 2;
    }
    .glass-nav {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.85);
    }
    .shimmer-btn {
      position: relative;
      overflow: hidden;
    }
    .shimmer-btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
      transition: transform 0.75s ease-in-out;
    }
    .shimmer-btn:hover::after {
      transform: translateX(200%);
    }
    .bento-card {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(16px);
      border: 1px solid rgba(226, 232, 240, 0.9);
      transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .bento-card:hover {
      transform: translateY(-3px);
      border-color: rgba(167, 139, 250, 0.7);
      box-shadow: 0 20px 40px -15px rgba(124, 58, 237, 0.12);
    }
    @keyframes pulse-dot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }
    .live-dot {
      animation: pulse-dot 2s infinite ease-in-out;
    }
  </style>
</head>
<body class="antialiased selection:bg-brand-500 selection:text-white">

  <!-- ========================================================================= -->
  <!-- NAVBAR SUPERIOR -->
  <!-- ========================================================================= -->
  <header class="fixed top-0 left-0 right-0 z-50 glass-nav transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-4">
      
      <!-- Logo -->
      <a href="index.php" class="flex items-center gap-2.5 sm:gap-3 group shrink-0">
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-brand-500 via-indigo-600 to-brand-700 flex items-center justify-center text-white font-black text-lg shadow-glow-sm group-hover:scale-105 transition-transform">
          <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
        </div>
        <span class="text-xl sm:text-2xl gamma-wordmark tracking-tight text-midnight">
          XINDRO
        </span>
      </a>

      <!-- Center Links -->
      <nav class="hidden md:flex items-center gap-1.5 lg:gap-2 text-sm font-semibold text-slate-600">
        <a href="index.php" class="px-3 py-2 rounded-xl hover:text-brand-600 hover:bg-slate-100/70 transition-colors" data-i18n="nav_home">Inicio</a>
        <a href="index.php#funciones" class="px-3 py-2 rounded-xl hover:text-brand-600 hover:bg-slate-100/70 transition-colors" data-i18n="nav_products">Funciones</a>
        <a href="index.php#simulador" class="px-3 py-2 rounded-xl hover:text-brand-600 hover:bg-slate-100/70 transition-colors flex items-center gap-1.5">
          <span data-i18n="nav_simulator">Simulador</span>
          <span class="w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
        </a>
        <a href="index.php#precios" class="px-3 py-2 rounded-xl hover:text-brand-600 hover:bg-slate-100/70 transition-colors" data-i18n="nav_pricing">Precios</a>
        <a href="about.php" class="px-3 py-2 rounded-xl text-brand-600 bg-brand-50 font-bold transition-colors" data-i18n="nav_about_active">Acerca de</a>
      </nav>

      <!-- Right Controls -->
      <div class="flex items-center gap-2.5 sm:gap-3">
        
        <!-- Language dropdown -->
        <div class="relative inline-block text-left" id="lang-dropdown-wrapper">
          <button type="button" onclick="I18n.toggleLangMenu()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-bold text-slate-700 transition-colors">
            <span class="text-sm">🌐</span>
            <span id="current-lang-label">Español</span>
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </button>

          <div id="lang-dropdown-menu" class="hidden absolute right-0 mt-2 w-36 rounded-2xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs font-semibold text-slate-700">
            <button type="button" onclick="I18n.setLanguage('es')" class="w-full text-left px-3.5 py-2 hover:bg-brand-50 hover:text-brand-700 flex items-center justify-between transition-colors">
              <span>🇪🇸 Español</span>
              <span id="check-es" class="text-brand-600 font-bold">✔</span>
            </button>
            <button type="button" onclick="I18n.setLanguage('en')" class="w-full text-left px-3.5 py-2 hover:bg-brand-50 hover:text-brand-700 flex items-center justify-between transition-colors">
              <span>🇺🇸 English</span>
              <span id="check-en" class="hidden text-brand-600 font-bold">✔</span>
            </button>
            <button type="button" onclick="I18n.setLanguage('pt')" class="w-full text-left px-3.5 py-2 hover:bg-brand-50 hover:text-brand-700 flex items-center justify-between transition-colors">
              <span>🇧🇷 Português</span>
              <span id="check-pt" class="hidden text-brand-600 font-bold">✔</span>
            </button>
          </div>
        </div>

        <?php if ($isLoggedIn): ?>
          <a href="dashboard.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all whitespace-nowrap shimmer-btn">
            <span data-i18n="nav_dashboard">Ir a mi Panel</span> →
          </a>
        <?php else: ?>
          <a href="login.php" class="hidden sm:inline-block text-xs sm:text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors" data-i18n="nav_login">
            Iniciar sesión
          </a>
          <a href="login.php" class="inline-flex items-center gap-1.5 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all whitespace-nowrap shimmer-btn" data-i18n="nav_cta">
            <span>Comienza gratis</span>
          </a>
        <?php endif; ?>

      </div>

    </div>
  </header>

  <!-- ========================================================================= -->
  <!-- 1. HERO SECTION: MISIÓN & VISIÓN -->
  <!-- ========================================================================= -->
  <section class="relative pt-36 pb-16 md:pt-44 md:pb-24 hero-mesh-bg border-b border-slate-100 overflow-hidden">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 text-center">
      
      <!-- Pill Badge -->
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-50 border border-brand-200 text-brand-700 text-xs font-bold tracking-wide uppercase mb-6 shadow-sm">
        <span class="text-sm">🏛️</span>
        <span data-i18n="about_hero_badge">Nuestra Misión & Filosofía</span>
      </div>

      <!-- Main Headline -->
      <h1 class="text-3xl sm:text-5xl md:text-6xl font-black text-midnight tracking-tight leading-[1.15] mb-6 font-display" data-i18n="about_hero_h1">
        Construyendo el <span class="gradient-text">Sistema Operativo de IA</span> para Creadores y Marcas.
      </h1>

      <!-- Subtitle -->
      <p class="text-base sm:text-xl text-slate-600 max-w-3xl mx-auto leading-relaxed font-normal mb-10" data-i18n="about_hero_sub">
        Nacimos para resolver el mayor desafío del creador moderno: responder a miles de comentarios en tiempo real para fidelizar a la comunidad y maximizar el algoritmo, sin perder la voz humana ni sufrir agotamiento.
      </p>

      <!-- Key Platform Metrics Bento -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-left max-w-4xl mx-auto pt-4">
        
        <div class="bento-card p-5 rounded-2xl bg-white">
          <div class="text-2xl sm:text-3xl font-black text-brand-600 mb-1">&lt; 180 ms</div>
          <div class="text-xs sm:text-sm font-bold text-slate-900 mb-1" data-i18n="about_stat1_title">Respuesta Heurística</div>
          <div class="text-[11px] text-slate-500" data-i18n="about_stat1_desc">Latencia ultrarrápida para ganar la ventana de oro de Meta.</div>
        </div>

        <div class="bento-card p-5 rounded-2xl bg-white">
          <div class="text-2xl sm:text-3xl font-black text-emerald-600 mb-1">100%</div>
          <div class="text-xs sm:text-sm font-bold text-slate-900 mb-1" data-i18n="about_stat2_title">Meta Graph API</div>
          <div class="text-[11px] text-slate-500" data-i18n="about_stat2_desc">Endpoints oficiales v19+ con cero riesgo de sanciones.</div>
        </div>

        <div class="bento-card p-5 rounded-2xl bg-white">
          <div class="text-2xl sm:text-3xl font-black text-indigo-600 mb-1">+15 hrs</div>
          <div class="text-xs sm:text-sm font-bold text-slate-900 mb-1" data-i18n="about_stat3_title">Ahorro Mensual</div>
          <div class="text-[11px] text-slate-500" data-i18n="about_stat3_desc">Tiempo manual ahorrado por cada cuenta conectada.</div>
        </div>

        <div class="bento-card p-5 rounded-2xl bg-white">
          <div class="text-2xl sm:text-3xl font-black text-purple-600 mb-1">0%</div>
          <div class="text-xs sm:text-sm font-bold text-slate-900 mb-1" data-i18n="about_stat4_title">Alucinaciones</div>
          <div class="text-[11px] text-slate-500" data-i18n="about_stat4_desc">Respuestas ancladas estrictamente a los datos de tu negocio.</div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 2. EL PROBLEMA Y POR QUÉ EXISTE XINDRO -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-slate-50/70 border-b border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 lg:gap-14 items-center">
        
        <!-- Left Column: Story -->
        <div class="lg:col-span-6 space-y-5">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold uppercase">
            <span>🔥 El Dilema del Engagement</span>
          </div>
          <h2 class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight leading-snug" data-i18n="about_prob_h2">
            La "Ventana de Oro" de los algoritmos y el agotamiento del creador.
          </h2>
          <p class="text-sm sm:text-base text-slate-600 leading-relaxed" data-i18n="about_prob_p1">
            Los algoritmos de Instagram y Facebook tienen una regla estricta: cuando una publicación recibe interacciones y se responden en los <strong>primeros 15 a 30 minutos</strong>, el alcance orgánico se multiplica hasta un 380%.
          </p>
          <p class="text-sm sm:text-base text-slate-600 leading-relaxed" data-i18n="about_prob_p2">
            Pero ningún ser humano puede estar disponible 24 horas al día, 7 días a la semana. Por otro lado, los bots tradicionales de palabras clave envían mensajes genéricos y fríos que dañan la reputación de la marca.
          </p>
          <p class="text-sm sm:text-base font-bold text-slate-800 leading-relaxed" data-i18n="about_prob_p3">
            XINDRO nace exactamente para cerrar esa brecha: inteligencia contextual de vanguardia que asiste en tiempo real pero mantiene al creador en control de cada palabra.
          </p>
        </div>

        <!-- Right Column: Visual Comparison Bento -->
        <div class="lg:col-span-6 space-y-4">
          
          <div class="p-5 rounded-2xl bg-white border border-rose-100 shadow-sm space-y-2">
            <div class="flex items-center gap-2 text-rose-600 font-bold text-sm">
              <span>❌</span>
              <span data-i18n="about_cmp_bad_title">El Método Tradicional (Manual o Bots Rígidos)</span>
            </div>
            <p class="text-xs text-slate-600 leading-relaxed" data-i18n="about_cmp_bad_desc">
              Horas perdidas copiando y pegando respuestas; lentitud que pierde la ventana de oro del algoritmo; respuestas robóticas ("¡Mándame DM!") que frustran a tus seguidores y destruyen oportunidades de venta.
            </p>
          </div>

          <div class="p-5 rounded-2xl bg-gradient-to-br from-brand-50 to-indigo-50/70 border border-brand-200 shadow-md space-y-2">
            <div class="flex items-center gap-2 text-brand-700 font-bold text-sm">
              <span>✨</span>
              <span data-i18n="about_cmp_good_title">La Experiencia XINDRO Copilot</span>
            </div>
            <p class="text-xs text-slate-700 leading-relaxed" data-i18n="about_cmp_good_desc">
              Detección de intención instantánea (preguntas de precio, dudas sobre cursos, felicitaciones). Borradores hiperprecisos en tu tono de marca listos para aprobar en 1 clic. Respuestas en segundos con 0% riesgo.
            </p>
          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 3. LOS 4 PILARES FUNDACIONALES DE XINDRO -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      
      <div class="text-center max-w-3xl mx-auto mb-14">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-50 border border-brand-200 text-brand-700 text-xs font-bold uppercase mb-3">
          <span>🛡️ Nuestros Principios</span>
        </div>
        <h2 class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mb-3" data-i18n="about_pillars_h2">
          Los 4 Pilares de Nuestra Ingeniería
        </h2>
        <p class="text-sm sm:text-base text-slate-600" data-i18n="about_pillars_sub">
          Cada línea de código en XINDRO se rige por estos compromisos inquebrantables.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 lg:gap-8">
        
        <!-- Pilar 1 -->
        <div class="bento-card p-6 sm:p-8 rounded-2xl bg-white space-y-3">
          <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold">
            🤝
          </div>
          <h3 class="text-lg font-black text-slate-900" data-i18n="about_pil1_title">1. Copilot First: Control Humano en 1 Clic</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_pil1_desc">
            Creemos que la IA debe amplificar la inteligencia humana, no reemplazarla a ciegas. Nuestro modo Copilot permite a los community managers y creadores revisar, ajustar o aprobar borradores generados en un parpadeo.
          </p>
        </div>

        <!-- Pilar 2 -->
        <div class="bento-card p-6 sm:p-8 rounded-2xl bg-white space-y-3">
          <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-2xl font-bold">
            🛡️
          </div>
          <h3 class="text-lg font-black text-slate-900" data-i18n="about_pil2_title">2. Cero Alucinaciones & Anti-Falsedad</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_pil2_desc">
            La IA está restringida criptográfica y lógicamente a los datos reales de tu negocio. Si un dato no está en el catálogo, jamás inventa descuentos, cupos o afirmaciones falsas; orienta amablemente hacia el enlace oficial.
          </p>
        </div>

        <!-- Pilar 3 -->
        <div class="bento-card p-6 sm:p-8 rounded-2xl bg-white space-y-3">
          <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-2xl font-bold">
            ⚡
          </div>
          <h3 class="text-lg font-black text-slate-900" data-i18n="about_pil3_title">3. 100% Meta Graph API Oficial</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_pil3_desc">
            Cero emuladores, cero navegadores automatizados y cero riesgo de bloqueos (shadowbans). Nos conectamos exclusivamente a través de los protocolos autorizados de Meta con tokens cifrados mediante AES-256-GCM.
          </p>
        </div>

        <!-- Pilar 4 -->
        <div class="bento-card p-6 sm:p-8 rounded-2xl bg-white space-y-3">
          <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl font-bold">
            🏢
          </div>
          <h3 class="text-lg font-black text-slate-900" data-i18n="about_pil4_title">4. Aislamiento Multi-Tenant Absoluto</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_pil4_desc">
            Diseñado para agencias y creadores de alto impacto. Cada marca opera en un silo de conocimiento y base de datos protegido, garantizando confidencialidad comercial y cumplimiento del RGPD / LGPD.
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 4. ARQUITECTURA TÉCNICA DEL MOTOR XINDRO -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-slate-900 text-white relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">
      
      <div class="text-center max-w-3xl mx-auto mb-14">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-900/80 border border-brand-500/40 text-brand-300 text-xs font-bold uppercase mb-3">
          <span>🧠 Arquitectura de IA</span>
        </div>
        <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight mb-3" data-i18n="about_arch_h2">
          Cómo Funciona el Pipeline de XINDRO
        </h2>
        <p class="text-sm sm:text-base text-slate-400" data-i18n="about_arch_sub">
          Un motor híbrido de doble capa diseñado para máxima velocidad y precisión comercial.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Step 1 -->
        <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2.5">
          <div class="text-xs font-mono text-brand-400 font-bold">PASO 01</div>
          <h4 class="text-base font-bold text-white" data-i18n="about_step1_t">Webhooks en Tiempo Real</h4>
          <p class="text-xs text-slate-300 leading-relaxed" data-i18n="about_step1_d">
            Meta notifica los nuevos comentarios al instante. Validamos la firma criptográfica HMAC-SHA256 para garantizar autenticidad.
          </p>
        </div>

        <!-- Step 2 -->
        <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2.5">
          <div class="text-xs font-mono text-cyan-400 font-bold">PASO 02</div>
          <h4 class="text-base font-bold text-white" data-i18n="about_step2_t">Clasificador de Intención</h4>
          <p class="text-xs text-slate-300 leading-relaxed" data-i18n="about_step2_d">
            En menos de 180ms, el motor heurístico clasifica si el seguidor pregunta por precios, acceso al curso, objeciones o soporte.
          </p>
        </div>

        <!-- Step 3 -->
        <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2.5">
          <div class="text-xs font-mono text-emerald-400 font-bold">PASO 03</div>
          <h4 class="text-base font-bold text-white" data-i18n="about_step3_t">Calibración de Voz de Marca</h4>
          <p class="text-xs text-slate-300 leading-relaxed" data-i18n="about_step3_d">
            Se inyecta el contexto de negocio (productos, precios y lineamientos) para generar una respuesta en el tono deseado (Mentor, Empático, Ventas).
          </p>
        </div>

        <!-- Step 4 -->
        <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2.5">
          <div class="text-xs font-mono text-indigo-400 font-bold">PASO 04</div>
          <h4 class="text-base font-bold text-white" data-i18n="about_step4_t">Modo Híbrido LLM + Fallback</h4>
          <p class="text-xs text-slate-300 leading-relaxed" data-i18n="about_step4_d">
            Utiliza OpenRouter (Claude / GPT / Llama) con fallback automático al motor heurístico local si la red externa presenta latencia.
          </p>
        </div>

        <!-- Step 5 -->
        <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2.5">
          <div class="text-xs font-mono text-amber-400 font-bold">PASO 05</div>
          <h4 class="text-base font-bold text-white" data-i18n="about_step5_t">Smart Timing & Human Review</h4>
          <p class="text-xs text-slate-300 leading-relaxed" data-i18n="about_step5_d">
            La respuesta se presenta en el panel Copilot para aprobación con 1 clic y se programa en la ventana de mayor alcance de la publicación.
          </p>
        </div>

        <!-- Step 6 -->
        <div class="p-6 rounded-2xl bg-slate-800/80 border border-slate-700/80 space-y-2.5">
          <div class="text-xs font-mono text-purple-400 font-bold">PASO 06</div>
          <h4 class="text-base font-bold text-white" data-i18n="about_step6_t">Métricas y Conversión</h4>
          <p class="text-xs text-slate-300 leading-relaxed" data-i18n="about_step6_d">
            Seguimiento de leads calificados, preguntas de compra resueltas y tiempo total de engagement medido en tiempo real.
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 5. FINAL CTA BANNER -->
  <!-- ========================================================================= -->
  <section class="py-16 sm:py-20 bg-gradient-to-br from-brand-600 via-indigo-600 to-purple-800 text-white text-center relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 relative z-10">
      <h2 class="text-2xl sm:text-4xl md:text-5xl font-extrabold mb-4 tracking-tight" data-i18n="about_final_h2">
        Únete a la evolución del engagement en redes sociales
      </h2>
      <p class="text-sm sm:text-lg text-brand-100 mb-8 max-w-2xl mx-auto font-normal" data-i18n="about_final_sub">
        Comienza gratis hoy mismo y descubre la tranquilidad de tener un Copilot que cuida tu comunidad las 24 horas del día.
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
        <a href="login.php" class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm sm:text-base font-bold text-brand-900 bg-white hover:bg-slate-100 shadow-xl transition-all shimmer-btn">
          <span data-i18n="about_final_btn">Crear Cuenta Gratis</span> →
        </a>
      </div>
    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 6. COSMIC STARRY FOOTER -->
  <!-- ========================================================================= -->
  <footer class="starry-footer-bg starry-overlay pt-14 sm:pt-16 pb-12 text-slate-200 text-sm overflow-hidden relative border-t border-slate-800/80">
    
    <div class="w-full max-w-full overflow-hidden flex justify-center items-center px-4 my-6 sm:my-8 select-none pointer-events-none">
      <div class="gamma-wordmark text-[clamp(2.8rem,13vw,11.5rem)] font-black tracking-tight leading-none text-center uppercase bg-clip-text text-transparent bg-gradient-to-r from-violet-400/40 via-purple-400/30 to-cyan-400/25 drop-shadow-md select-none">
        XINDRO
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-10 sm:gap-12 mb-14 sm:mb-18 pt-4">
        
        <!-- Brand Card -->
        <div class="sm:col-span-2 lg:col-span-4 pr-0 lg:pr-6">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 via-indigo-600 to-brand-700 flex items-center justify-center text-white font-extrabold text-lg shadow-md">
              ⚡
            </div>
            <span class="text-2xl font-black tracking-tight text-white gamma-wordmark">XINDRO<span class="text-brand-400">.</span></span>
          </div>
          <p class="text-sm text-slate-200 leading-relaxed mb-5 font-normal" data-i18n="foot_brand_desc">
            El sistema operativo de IA para creadores y agencias de redes sociales. Respuestas en tiempo real, Smart Timing y sincronización oficial con Meta Graph API.
          </p>
          <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-full bg-slate-900/90 border border-emerald-500/30 text-xs text-emerald-300 font-bold mb-5 shadow-inner">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 live-dot"></span>
            <span data-i18n="foot_status_pill">Meta API 100% Operativa</span>
          </div>
        </div>

        <!-- Producto -->
        <div class="lg:col-span-2">
          <h4 class="text-sm font-extrabold text-white uppercase tracking-wider mb-5">Producto</h4>
          <ul class="space-y-3.5 text-sm text-slate-200 font-medium">
            <li><a href="index.php#funciones" class="hover:text-cyan-300 transition-colors">Funciones</a></li>
            <li><a href="index.php#como-empezar" class="hover:text-cyan-300 transition-colors">Cómo empezar</a></li>
            <li><a href="index.php#simulador" class="hover:text-cyan-300 transition-colors">Simulador en Vivo</a></li>
            <li><a href="index.php#calculadora-roi" class="hover:text-cyan-300 transition-colors">Calculadora</a></li>
            <li><a href="index.php#precios" class="hover:text-cyan-300 transition-colors">Precios</a></li>
          </ul>
        </div>

        <!-- Empresa -->
        <div class="lg:col-span-2">
          <h4 class="text-sm font-extrabold text-white uppercase tracking-wider mb-5">Empresa</h4>
          <ul class="space-y-3.5 text-sm text-slate-200 font-medium">
            <li><a href="about.php" class="text-brand-300 font-bold">Acerca de XINDRO</a></li>
            <li><a href="index.php#por-que-xindro" class="hover:text-cyan-300 transition-colors">¿Por qué Xindro?</a></li>
            <li><a href="index.php#faq" class="hover:text-cyan-300 transition-colors">FAQ</a></li>
            <li><a href="privacy-policy.php" class="hover:text-cyan-300 transition-colors">Seguridad y Privacidad</a></li>
          </ul>
        </div>

        <!-- Redes sociales -->
        <div class="lg:col-span-2">
          <h4 class="text-sm font-extrabold text-white uppercase tracking-wider mb-5">Redes</h4>
          <ul class="space-y-3.5 text-sm text-slate-200 font-medium">
            <li><a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-300 transition-colors">Instagram</a></li>
            <li><a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-300 transition-colors">LinkedIn</a></li>
            <li><a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-300 transition-colors">TikTok</a></li>
            <li><a href="https://x.com" target="_blank" rel="noopener noreferrer" class="hover:text-cyan-300 transition-colors">X (Twitter)</a></li>
          </ul>
        </div>

        <!-- Legal -->
        <div class="lg:col-span-2">
          <h4 class="text-sm font-extrabold text-white uppercase tracking-wider mb-5">Legal</h4>
          <ul class="space-y-3.5 text-sm text-slate-200 font-medium">
            <li><a href="terms-of-service.php" class="hover:text-cyan-300 transition-colors">Términos de Servicio</a></li>
            <li><a href="privacy-policy.php" class="hover:text-cyan-300 transition-colors">Política de Privacidad</a></li>
            <li><a href="data-deletion.php" class="hover:text-cyan-300 transition-colors">Eliminación de Datos</a></li>
          </ul>
        </div>

      </div>

      <div class="border-t border-slate-800/80 pt-8 pb-4 flex flex-col sm:flex-row items-center justify-between gap-5 text-sm text-slate-300">
        <div class="flex items-center gap-2.5">
          <span class="font-extrabold text-white text-base">XINDRO</span>
          <span>•</span>
          <span class="text-slate-300 font-normal">© <?= date('Y') ?> Xindro Tech, Inc. Todos los derechos reservados.</span>
        </div>
        <div>
          <span class="flex items-center gap-2 text-emerald-400 font-semibold text-sm">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 live-dot"></span>
            Meta Graph API Verified
          </span>
        </div>
      </div>

    </div>
  </footer>

  <!-- ========================================================================= -->
  <!-- JAVASCRIPT: I18N DICTIONARY -->
  <!-- ========================================================================= -->
  <script>
    const I18n = {
      current: '<?= $initialLang ?>',
      dict: {
        es: {
          page_title: "Acerca de XINDRO — Nuestra Visión, Tecnología y Misión",
          nav_home: "Inicio",
          nav_products: "Funciones",
          nav_simulator: "Simulador",
          nav_pricing: "Precios",
          nav_about_active: "Acerca de",
          nav_login: "Iniciar sesión",
          nav_cta: "Comienza gratis",
          nav_dashboard: "Ir a mi Panel",
          about_hero_badge: "Nuestra Misión & Filosofía",
          about_hero_h1: "Construyendo el <span class=\"gradient-text\">Sistema Operativo de IA</span> para Creadores y Marcas.",
          about_hero_sub: "Nacimos para resolver el mayor desafío del creador moderno: responder a miles de comentarios en tiempo real para fidelizar a la comunidad y maximizar el algoritmo, sin perder la voz humana ni sufrir agotamiento.",
          about_stat1_title: "Respuesta Heurística",
          about_stat1_desc: "Latencia ultrarrápida para ganar la ventana de oro de Meta.",
          about_stat2_title: "Meta Graph API",
          about_stat2_desc: "Endpoints oficiales v19+ con cero riesgo de sanciones.",
          about_stat3_title: "Ahorro Mensual",
          about_stat3_desc: "Tiempo manual ahorrado por cada cuenta conectada.",
          about_stat4_title: "Alucinaciones",
          about_stat4_desc: "Respuestas ancladas estrictamente a los datos de tu negocio.",
          about_prob_h2: "La \"Ventana de Oro\" de los algoritmos y el agotamiento del creador.",
          about_prob_p1: "Los algoritmos de Instagram y Facebook tienen una regla estricta: cuando una publicación recibe interacciones y se responden en los <strong>primeros 15 a 30 minutos</strong>, el alcance orgánico se multiplica hasta un 380%.",
          about_prob_p2: "Pero ningún ser humano puede estar disponible 24 horas al día, 7 días a la semana. Por otro lado, los bots tradicionales de palabras clave envían mensajes genéricos y fríos que dañan la reputación de la marca.",
          about_prob_p3: "XINDRO nace exactamente para cerrar esa brecha: inteligencia contextual de vanguardia que asiste en tiempo real pero mantiene al creador en control de cada palabra.",
          about_cmp_bad_title: "El Método Tradicional (Manual o Bots Rígidos)",
          about_cmp_bad_desc: "Horas perdidas copiando y pegando respuestas; lentitud que pierde la ventana de oro del algoritmo; respuestas robóticas (\"¡Mándame DM!\") que frustran a tus seguidores y destruyen oportunidades de venta.",
          about_cmp_good_title: "La Experiencia XINDRO Copilot",
          about_cmp_good_desc: "Detección de intención instantánea (preguntas de precio, dudas sobre cursos, felicitaciones). Borradores hiperprecisos en tu tono de marca listos para aprobar en 1 clic. Respuestas en segundos con 0% riesgo.",
          about_pillars_h2: "Los 4 Pilares de Nuestra Ingeniería",
          about_pillars_sub: "Cada línea de código en XINDRO se rige por estos compromisos inquebrantables.",
          about_pil1_title: "1. Copilot First: Control Humano en 1 Clic",
          about_pil1_desc: "Creemos que la IA debe amplificar la inteligencia humana, no reemplazarla a ciegas. Nuestro modo Copilot permite a los community managers y creadores revisar, ajustar o aprobar borradores generados en un parpadeo.",
          about_pil2_title: "2. Cero Alucinaciones & Anti-Falsedad",
          about_pil2_desc: "La IA está restringida criptográfica y lógicamente a los datos reales de tu negocio. Si un dato no está en el catálogo, jamás inventa descuentos, cupos o afirmaciones falsas; orienta amablemente hacia el enlace oficial.",
          about_pil3_title: "3. 100% Meta Graph API Oficial",
          about_pil3_desc: "Cero emuladores, cero navegadores automatizados y cero riesgo de bloqueos (shadowbans). Nos conectamos exclusivamente a través de los protocolos autorizados de Meta con tokens cifrados mediante AES-256-GCM.",
          about_pil4_title: "4. Aislamiento Multi-Tenant Absoluto",
          about_pil4_desc: "Diseñado para agencias y creadores de alto impacto. Cada marca opera en un silo de conocimiento y base de datos protegido, garantizando confidencialidad comercial y cumplimiento del RGPD / LGPD.",
          about_arch_h2: "Cómo Funciona el Pipeline de XINDRO",
          about_arch_sub: "Un motor híbrido de doble capa diseñado para máxima velocidad y precisión comercial.",
          about_step1_t: "Webhooks en Tiempo Real",
          about_step1_d: "Meta notifica los nuevos comentarios al instante. Validamos la firma criptográfica HMAC-SHA256 para garantizar autenticidad.",
          about_step2_t: "Clasificador de Intención",
          about_step2_d: "En menos de 180ms, el motor heurístico clasifica si el seguidor pregunta por precios, acceso al curso, objeciones o soporte.",
          about_step3_t: "Calibración de Voz de Marca",
          about_step3_d: "Se inyecta el contexto de negocio (productos, precios y lineamientos) para generar una respuesta en el tono deseado (Mentor, Empático, Ventas).",
          about_step4_t: "Modo Híbrido LLM + Fallback",
          about_step4_d: "Utiliza OpenRouter (Claude / GPT / Llama) con fallback automático al motor heurístico local si la red externa presenta latencia.",
          about_step5_t: "Smart Timing & Human Review",
          about_step5_d: "La respuesta se presenta en el panel Copilot para aprobación con 1 clic y se programa en la ventana de mayor alcance de la publicación.",
          about_step6_t: "Métricas y Conversión",
          about_step6_d: "Seguimiento de leads calificados, preguntas de compra resueltas y tiempo total de engagement medido en tiempo real.",
          about_final_h2: "Únete a la evolución del engagement en redes sociales",
          about_final_sub: "Comienza gratis hoy mismo y descubre la tranquilidad de tener un Copilot que cuida tu comunidad las 24 horas del día.",
          about_final_btn: "Crear Cuenta Gratis",
          foot_brand_desc: "El sistema operativo de IA para creadores y agencias de redes sociales. Respuestas en tiempo real, Smart Timing y sincronización oficial con Meta Graph API.",
          foot_status_pill: "Meta API 100% Operativa"
        },
        en: {
          page_title: "About XINDRO — Our Vision, AI Technology & Mission",
          nav_home: "Home",
          nav_products: "Features",
          nav_simulator: "Simulator",
          nav_pricing: "Pricing",
          nav_about_active: "About",
          nav_login: "Log in",
          nav_cta: "Get started free",
          nav_dashboard: "Go to Dashboard",
          about_hero_badge: "Our Mission & Engineering Principles",
          about_hero_h1: "Building the <span class=\"gradient-text\">AI Operating System</span> for Creators and Brands.",
          about_hero_sub: "We built XINDRO to solve modern social engagement: replying to thousands of comments in real time to build loyalty and optimize reach, without losing your authentic human voice.",
          about_stat1_title: "Heuristic Latency",
          about_stat1_desc: "Sub-180ms response speed to win Meta's golden algorithm window.",
          about_stat2_title: "Meta Graph API",
          about_stat2_desc: "Official v19+ API endpoints with zero shadowban risk.",
          about_stat3_title: "Monthly Savings",
          about_stat3_desc: "Manual hours saved per connected account.",
          about_stat4_title: "Zero Hallucinations",
          about_stat4_desc: "Responses strictly grounded in your verified business facts.",
          about_prob_h2: "The Algorithm's Golden Window & Creator Burnout.",
          about_prob_p1: "Instagram and Facebook algorithms reward accounts that engage within the first 15 to 30 minutes, boosting organic reach by up to 380%.",
          about_prob_p2: "Yet no human can monitor feeds 24/7, and rigid keyword bots send spammy 'DM me!' replies that frustrate followers and harm brand trust.",
          about_prob_p3: "XINDRO bridges this gap: cutting-edge contextual AI that drafts intelligent replies in seconds while keeping the human in full control.",
          about_cmp_bad_title: "Traditional Method (Manual or Rigid Bots)",
          about_cmp_bad_desc: "Hours wasted copying and pasting replies; missed engagement windows; spammy bots that alienate high-intent buyers.",
          about_cmp_good_title: "The XINDRO Copilot Experience",
          about_cmp_good_desc: "Instant intent detection (pricing inquiries, course FAQs, praises). Calibrated brand drafts ready for 1-click review and posting.",
          about_pillars_h2: "The 4 Pillars of Our Engineering",
          about_pillars_sub: "Every line of code at XINDRO adheres to these fundamental commitments.",
          about_pil1_title: "1. Copilot First: 1-Click Human Control",
          about_pil1_desc: "AI should empower creators, not replace them blindly. Our Copilot allows community managers to approve or edit drafts in a heartbeat.",
          about_pil2_title: "2. Zero Hallucinations & Factual Guardrails",
          about_pil2_desc: "Grounded strictly in your business knowledge base. If information is missing, the AI never fabricates details; it directs followers to official links.",
          about_pil3_title: "3. 100% Official Meta Graph API",
          about_pil3_desc: "No unauthorized scrapers or web automation. Strictly compliant with Meta developer policies, AES-256-GCM encryption, and HMAC-SHA256 webhooks.",
          about_pil4_title: "4. Multi-Tenant Cryptographic Isolation",
          about_pil4_desc: "Architected for agencies and enterprise creators. Every brand operates within dedicated database partitions for maximum data privacy.",
          about_arch_h2: "How the XINDRO Pipeline Operates",
          about_arch_sub: "A hybrid dual-layer engine built for extreme velocity and commercial precision.",
          about_step1_t: "Real-Time Webhooks",
          about_step1_d: "Meta notifies new comments instantly. We verify the cryptographic HMAC-SHA256 signature to guarantee authenticity.",
          about_step2_t: "Intent Classifier",
          about_step2_d: "In under 180ms, the heuristic classifier categorizes purchase inquiries, syllabus questions, or feedback.",
          about_step3_t: "Brand Voice Calibrator",
          about_step3_d: "Injects business context and tone parameters (Mentor, Friendly, High-Ticket) to draft hyper-relevant replies.",
          about_step4_t: "Hybrid LLM + Fallback Engine",
          about_step4_d: "Leverages OpenRouter AI with automatic instant fallback to local heuristic models if external networks experience latency.",
          about_step5_t: "Smart Timing & Human Review",
          about_step5_d: "Drafts are staged in the Copilot inbox for 1-click publishing during peak audience activity windows.",
          about_step6_t: "Conversion Analytics",
          about_step6_d: "Track high-intent leads, resolved purchase questions, and saved operational hours in real time.",
          about_final_h2: "Join the Future of Social Media Engagement",
          about_final_sub: "Start for free today and experience the peace of mind of having an AI Copilot that nurtures your community 24/7.",
          about_final_btn: "Create Free Account",
          foot_brand_desc: "The AI operating system for creators and social media agencies. Real-time replies, Smart Timing, and official synchronization.",
          foot_status_pill: "Meta API 100% Operational"
        },
        pt: {
          page_title: "Sobre a XINDRO — Nossa Visão, Tecnologia e Missão",
          nav_home: "Início",
          nav_products: "Recursos",
          nav_simulator: "Simulador",
          nav_pricing: "Preços",
          nav_about_active: "Sobre",
          nav_login: "Entrar",
          nav_cta: "Comece grátis",
          nav_dashboard: "Ir ao Painel",
          about_hero_badge: "Nossa Missão & Filosofia",
          about_hero_h1: "Construindo o <span class=\"gradient-text\">Sistema Operacional de IA</span> para Criadores e Marcas.",
          about_hero_sub: "Nascemos para resolver o maior desafio do criador moderno: responder a milhares de comentários em tempo real para fidelizar a comunidade e vencer o algoritmo, sem perder o tom humano.",
          about_stat1_title: "Resposta Heurística",
          about_stat1_desc: "Latência inferior a 180ms para conquistar a janela de ouro da Meta.",
          about_stat2_title: "Meta Graph API",
          about_stat2_desc: "Endpoints oficiais v19+ com zero risco de penalidades.",
          about_stat3_title: "Economia Mensal",
          about_stat3_desc: "Horas manuais economizadas por cada conta conectada.",
          about_stat4_title: "Zero Alucinações",
          about_stat4_desc: "Respostas rigorosamente fundamentadas nos dados do seu negócio.",
          about_prob_h2: "A 'Janela de Ouro' dos algoritmos e a sobrecarga humana.",
          about_prob_p1: "Os algoritmos do Instagram e Facebook priorizam interações nos <strong>primeiros 15 a 30 minutos</strong>, aumentando o alcance orgânico em até 380%.",
          about_prob_p2: "Porém, ninguém consegue ficar online 24 horas por dia, e robôs tradicionais enviam mensagens genéricas que prejudicam a imagem da marca.",
          about_prob_p3: "A XINDRO fecha essa lacuna: inteligência contextual avançada que gera respostas precisas em segundos mantendo o controle humano total.",
          about_cmp_bad_title: "Método Tradicional (Manual ou Bots Rígidos)",
          about_cmp_bad_desc: "Horas perdidas copiando e colando; atrasos que perdem o algoritmo; respostas robóticas que afastam clientes.",
          about_cmp_good_title: "A Experiência XINDRO Copilot",
          about_cmp_good_desc: "Detecção instantânea de intenção de compra. Rascunhos no tom exato da marca prontos para aprovar com 1 clique.",
          about_pillars_h2: "Os 4 Pilares da Nossa Engenharia",
          about_pillars_sub: "Cada linha de código na XINDRO segue estes compromissos fundamentais.",
          about_pil1_title: "1. Copilot First: Controle Humano em 1 Clique",
          about_pil1_desc: "Acreditamos que a IA deve capacitar as pessoas. Nosso Copilot permite revisar e aprovar sugestões em segundos.",
          about_pil2_title: "2. Zero Alucinações e Dados Confiáveis",
          about_pil2_desc: "A IA é limitada aos dados reais do negócio. Se uma informação não existir, o sistema orienta para links oficiais.",
          about_pil3_title: "3. 100% Oficial com a Meta Graph API",
          about_pil3_desc: "Sem scrapers ou emuladores. Conexão oficial com criptografia AES-256-GCM e verificação HMAC-SHA256.",
          about_pil4_title: "4. Isolamento Multi-Tenant Rigoroso",
          about_pil4_desc: "Desenvolvido para agências e marcas. Cada cliente possui partições isoladas para proteção total de dados.",
          about_arch_h2: "Como Opera a Arquitetura da XINDRO",
          about_arch_sub: "Um motor híbrido desenvolvido para máxima velocidade e precisão comercial.",
          about_step1_t: "Webhooks em Tempo Real",
          about_step1_d: "Notificações instantâneas de novos comentários com validação de assinatura HMAC-SHA256.",
          about_step2_t: "Classificador de Intenção",
          about_step2_d: "Em menos de 180ms, identifica dúvidas de compra, perguntas técnicas ou suporte.",
          about_step3_t: "Calibrador de Tom de Marca",
          about_step3_d: "Aplica o contexto de produtos e o tom desejado (Mentor, Acolhedor, Vendas).",
          about_step4_t: "Motor Híbrido com Fallback",
          about_step4_d: "Integração OpenRouter com fallback automático para o motor heurístico local.",
          about_step5_t: "Smart Timing e Revisão",
          about_step5_d: "Rascunhos organizados para aprovação rápida na janela ideal de alcance.",
          about_step6_t: "Métricas de Conversão",
          about_step6_d: "Acompanhamento de leads gerados e horas de trabalho economizadas.",
          about_final_h2: "Evolua o Engajamento das suas Redes Sociais",
          about_final_sub: "Comece grátis hoje e tenha um Copilot inteligente cuidando da sua comunidade 24/7.",
          about_final_btn: "Criar Conta Grátis",
          foot_brand_desc: "O sistema operacional de IA para criadores e agências de redes sociais. Respostas em tempo real, Smart Timing e sincronização oficial.",
          foot_status_pill: "Meta API 100% Operacional"
        }
      },
      init() {
        const saved = localStorage.getItem('xindro_lang');
        if (saved && this.dict[saved]) {
          this.current = saved;
        } else {
          const userLang = (navigator.language || navigator.userLanguage || 'es').toLowerCase();
          if (userLang.startsWith('pt')) this.current = 'pt';
          else if (userLang.startsWith('en')) this.current = 'en';
          else this.current = 'es';
        }
        this.apply(this.current);
      },
      setLanguage(lang) {
        if (!this.dict[lang]) return;
        this.current = lang;
        localStorage.setItem('xindro_lang', lang);
        this.apply(lang);
        this.hideLangMenu();
      },
      apply(lang) {
        const d = this.dict[lang];
        if (!d) return;
        document.documentElement.lang = lang;
        const labels = { es: 'Español', en: 'English', pt: 'Português' };
        document.getElementById('current-lang-label').textContent = labels[lang] || 'Español';
        ['es', 'en', 'pt'].forEach(l => {
          const chk = document.getElementById('check-' + l);
          if (chk) {
            if (l === lang) chk.classList.remove('hidden');
            else chk.classList.add('hidden');
          }
        });
        document.querySelectorAll('[data-i18n]').forEach(el => {
          const key = el.getAttribute('data-i18n');
          if (d[key] !== undefined) el.innerHTML = d[key];
        });
        if (d.page_title) document.title = d.page_title;
        const metaDesc = document.getElementById('meta-page-desc');
        if (metaDesc && d.page_desc) metaDesc.setAttribute('content', d.page_desc);
      },
      toggleLangMenu() {
        const menu = document.getElementById('lang-dropdown-menu');
        if (menu) menu.classList.toggle('hidden');
      },
      hideLangMenu() {
        const menu = document.getElementById('lang-dropdown-menu');
        if (menu) menu.classList.add('hidden');
      }
    };

    document.addEventListener('click', (e) => {
      const wrapper = document.getElementById('lang-dropdown-wrapper');
      if (wrapper && !wrapper.contains(e.target)) I18n.hideLangMenu();
    });

    document.addEventListener('DOMContentLoaded', () => {
      I18n.init();
    });
  </script>
</body>
</html>
