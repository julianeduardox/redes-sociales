<?php
/**
 * XINDRO — El Sistema Operativo de IA para Creadores de Contenido y Redes Sociales
 * Landing Page de Alto Impacto Visual (Gamma.app Style + Multi-Idioma ES/EN/PT + Cookie Consent Manager RGPD/CCPA)
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

Security::applySecurityHeaders(false);
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

// Server-side browser language detection as fallback
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title id="meta-page-title">XINDRO — El Sistema Operativo de IA para Creadores de Contenido</title>
  <meta name="description" id="meta-page-desc" content="Automatiza tus redes sociales. Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y publica en múltiples plataformas desde una sola API.">
  
  <!-- Open Graph / Meta -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="XINDRO — Automatización Inteligente de Redes Sociales">
  <meta property="og:description" content="Escala tu comunidad sin perder el toque humano con Auto-Engagement, Smart Timing y API para Creadores.">
  <meta property="og:url" content="https://socialapi.turbogram.site/">
  
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">

  <!-- Google Fonts: Plus Jakarta Sans, Syne & JetBrains Mono -->
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
            slatecard: '#F8FAFC',
          },
          boxShadow: {
            'glow-sm': '0 0 15px rgba(139, 92, 246, 0.25)',
            'glow-md': '0 0 30px rgba(139, 92, 246, 0.35)',
            'glow-lg': '0 10px 40px -10px rgba(139, 92, 246, 0.45)',
            'subtle-card': '0 10px 30px -10px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.05)',
            'elevated-card': '0 20px 40px -15px rgba(15, 23, 42, 0.08), 0 0 1px 1px rgba(226, 232, 240, 0.8)',
            'cookie-popup': '0 20px 50px -10px rgba(15, 23, 42, 0.22), 0 0 0 1px rgba(226, 232, 240, 0.9)',
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

    /* Ambient radial gradient mesh */
    .hero-mesh-bg {
      background: radial-gradient(circle at 50% -10%, rgba(139, 92, 246, 0.12) 0%, rgba(248, 250, 252, 0) 65%),
                  radial-gradient(circle at 90% 20%, rgba(56, 189, 248, 0.08) 0%, rgba(255, 255, 255, 0) 50%),
                  radial-gradient(circle at 10% 30%, rgba(168, 85, 247, 0.08) 0%, rgba(255, 255, 255, 0) 50%);
    }

    .dark-mesh-bg {
      background: radial-gradient(circle at 50% 0%, rgba(139, 92, 246, 0.18) 0%, rgba(11, 15, 25, 1) 70%);
    }

    /* Starry Night Footer Background */
    .starry-footer-bg {
      background: radial-gradient(circle at 50% 10%, rgba(99, 102, 241, 0.25) 0%, rgba(15, 23, 42, 0.98) 75%),
                  linear-gradient(180deg, #131b2e 0%, #0b0f19 100%);
      position: relative;
    }

    .starry-overlay {
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
    }

    .gradient-text {
      background: linear-gradient(135deg, #7C3AED 0%, #4F46E5 50%, #06B6D4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .gradient-button {
      background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .gradient-button:hover {
      background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
      box-shadow: 0 8px 25px -4px rgba(124, 58, 237, 0.45);
      transform: translateY(-1px);
    }

    .glass-nav {
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
    }

    /* Gamma-style Geometric Wordmark */
    .gamma-wordmark {
      font-family: 'Syne', 'Plus Jakarta Sans', sans-serif;
      letter-spacing: -0.03em;
      font-weight: 900;
      text-transform: uppercase;
    }

    /* Giant Watermark in Footer */
    .giant-watermark {
      font-family: 'Syne', sans-serif;
      font-weight: 900;
      letter-spacing: -0.02em;
      line-height: 0.8;
      background: linear-gradient(180deg, rgba(165, 180, 252, 0.42) 0%, rgba(165, 180, 252, 0.05) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      user-select: none;
      pointer-events: none;
    }

    /* Cookie popup animation */
    @keyframes slideUpCookie {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.96);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    .cookie-animate {
      animation: slideUpCookie 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    pre code {
      font-family: 'JetBrains Mono', monospace;
    }

    /* Pulse animation */
    @keyframes pulse-dot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.85); }
    }
    .live-dot {
      animation: pulse-dot 2s infinite ease-in-out;
    }

    /* Toggle switch for cookie preferences */
    .toggle-checkbox:checked {
      right: 0;
      border-color: #8B5CF6;
    }
    .toggle-checkbox:checked + .toggle-label {
      background-color: #8B5CF6;
    }
  </style>
</head>
<body class="antialiased selection:bg-brand-500 selection:text-white">

  <!-- ========================================================================= -->
  <!-- 1. NAVBAR FIJA CON SELECTOR DE IDIOMA Y LOGO ESTILO GAMMA -->
  <!-- ========================================================================= -->
  <header class="fixed top-0 left-0 right-0 z-50 glass-nav transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Gamma-Style Minimalist Logo: XINDRO -->
      <a href="index.php" class="flex items-center gap-3 group shrink-0">
        <div class="flex items-center gap-2">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 via-indigo-600 to-brand-700 flex items-center justify-center text-white font-black text-lg shadow-glow-sm group-hover:scale-105 transition-transform">
            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
            </svg>
          </div>
          <span class="text-2xl gamma-wordmark tracking-tight text-midnight">
            XINDRO
          </span>
        </div>
      </a>

      <!-- Navigation Links -->
      <nav class="hidden lg:flex items-center gap-7 text-sm font-semibold text-slate-600">
        <a href="#funciones" data-i18n="nav_products" class="hover:text-brand-600 transition-colors">Productos</a>
        <a href="#simulador" data-i18n="nav_simulator" class="hover:text-brand-600 transition-colors flex items-center gap-1.5">
          Simulador
          <span class="inline-block w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
        </a>
        <a href="#smart-timing" data-i18n="nav_solutions" class="hover:text-brand-600 transition-colors">Soluciones</a>
        <a href="#api-docs" data-i18n="nav_api" class="hover:text-brand-600 transition-colors">API Creadores</a>
        <a href="#precios" data-i18n="nav_pricing" class="hover:text-brand-600 transition-colors">Precios</a>
      </nav>

      <!-- Right Controls: Language Selector & Auth CTAs -->
      <div class="flex items-center gap-3">
        
        <!-- Language Switcher Dropdown (ES / EN / PT) -->
        <div class="relative inline-block text-left" id="lang-dropdown-wrapper">
          <button type="button" id="lang-dropdown-btn" onclick="I18n.toggleLangMenu()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100/90 hover:bg-slate-200/80 border border-slate-200/80 text-xs font-bold text-slate-700 transition-colors">
            <span class="text-sm">🌐</span>
            <span id="current-lang-label">Español</span>
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
          </button>

          <!-- Dropdown Menu -->
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
          <a href="dashboard.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all">
            <span data-i18n="nav_dashboard">Ir a mi Panel</span>
            <span>→</span>
          </a>
        <?php else: ?>
          <a href="login.php" data-i18n="nav_login" class="text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 transition-colors hidden sm:inline-block">
            Iniciar sesión
          </a>
          <a href="login.php" data-i18n="nav_cta" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 hover:shadow-md transition-all">
            <span>Comienza gratis</span>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <!-- ========================================================================= -->
  <!-- 2. HERO SECTION MULTILINGÜE -->
  <!-- ========================================================================= -->
  <section class="relative pt-36 pb-20 md:pt-44 md:pb-28 hero-mesh-bg overflow-hidden border-b border-slate-100">
    
    <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[650px] h-[350px] bg-brand-400/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      
      <!-- Top Pill Badge -->
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-50 border border-brand-200/80 text-brand-700 text-xs sm:text-sm font-bold mb-8 shadow-sm hover:border-brand-300 transition-colors">
        <span class="flex h-2 w-2 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
        </span>
        <span data-i18n="hero_badge">El sistema operativo de IA para creadores de contenido</span>
      </div>

      <!-- Main Headline (H1) -->
      <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-midnight leading-[1.12] mb-6 max-w-4xl mx-auto">
        <span data-i18n="hero_h1_p1">Automatiza tus redes sociales.</span> <br class="hidden sm:inline" />
        <span class="gradient-text" data-i18n="hero_h1_p2">Escala tu comunidad sin perder el toque humano.</span>
      </h1>

      <!-- Subtitle (P) -->
      <p data-i18n="hero_sub" class="text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed font-normal">
        Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y publica en múltiples plataformas desde una sola API.
      </p>

      <!-- CTAs Button Group -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto mb-16">
        <a href="#simulador" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl text-base font-bold text-white gradient-button shadow-glow-md">
          <span data-i18n="hero_cta_sim">Prueba el Simulador</span>
          <span class="text-lg">✨</span>
        </a>
        <a href="#api-docs" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-slate-700 bg-white border border-slate-200 hover:border-brand-300 hover:bg-slate-50 hover:text-brand-700 shadow-sm transition-all">
          <span data-i18n="hero_cta_api">Documentación API</span>
          <span>&lt;/&gt;</span>
        </a>
      </div>

      <!-- Live Interactive Visual Hero Card -->
      <div class="relative max-w-4xl mx-auto rounded-2xl bg-white border border-slate-200/90 shadow-elevated-card p-4 sm:p-6 md:p-8 text-left">
        
        <!-- Header of Card -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-red-400"></div>
            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
            <span class="text-xs font-semibold text-slate-400 ml-2" data-i18n="hero_card_title">XINDRO Live Copilot — Flujo en Tiempo Real</span>
          </div>
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 live-dot"></span>
            <span data-i18n="hero_card_status">Meta Webhook Activo</span>
          </div>
        </div>

        <!-- Simulated Creator Post & AI Engagement Stream -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
          
          <!-- Incoming Comment (Left) -->
          <div class="md:col-span-5 bg-slatecard rounded-xl p-4 border border-slate-200/80">
            <div class="flex items-center gap-2.5 mb-2.5">
              <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=80&auto=format&fit=crop&q=80" class="w-8 h-8 rounded-full border border-slate-200 object-cover" alt="Avatar" />
              <div>
                <div class="text-xs font-bold text-slate-800">@alejandro.creator</div>
                <div class="text-[10px] text-slate-500" data-i18n="hero_card_time">Instagram • Hace 2 seg</div>
              </div>
              <span class="ml-auto text-[11px] font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200/60">
                Score: 96/100
              </span>
            </div>
            <p id="hero-sample-comment" class="text-xs text-slate-700 leading-relaxed font-medium">
              "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?"
            </p>
            <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500 font-semibold">
              <span data-i18n="hero_card_intent">🎯 Intención: <strong class="text-brand-600">Pregunta de Alto Valor</strong></span>
              <span class="text-emerald-600 font-bold">⚡ Latencia: 142ms</span>
            </div>
          </div>

          <!-- Flow Arrow (Center) -->
          <div class="md:col-span-2 flex flex-col items-center justify-center text-brand-600">
            <div class="w-9 h-9 rounded-full bg-brand-50 border border-brand-200 flex items-center justify-center font-bold text-sm shadow-sm">
              ⚡
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1" data-i18n="hero_card_calibrated">IA Calibrada</span>
          </div>

          <!-- Generated AI Reply (Right) -->
          <div class="md:col-span-5 bg-gradient-to-br from-brand-50/70 to-indigo-50/50 rounded-xl p-4 border border-brand-200/80">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xs font-bold text-brand-800 flex items-center gap-1.5">
                <span data-i18n="hero_card_bot_reply">🤖 Respuesta con Voz de Marca</span>
              </span>
              <span class="ml-auto text-[10px] font-bold text-brand-700 bg-brand-100/70 px-2 py-0.5 rounded-full" data-i18n="hero_card_tone">
                Tono: Mentor Empático
              </span>
            </div>
            <p id="hero-sample-reply" class="text-xs text-brand-950 leading-relaxed font-medium">
              "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇"
            </p>
            <div class="mt-3 pt-2 border-t border-brand-200/50 flex items-center justify-between text-[11px]">
              <span class="text-brand-700 font-semibold" data-i18n="hero_card_retention">🚀 Retención: <strong class="text-brand-900">+380%</strong></span>
              <span class="text-emerald-600 font-bold" data-i18n="hero_card_ready">✔ Listo para postear</span>
            </div>
          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 3. SOCIAL PROOF & MÉTRICAS DE IMPACTO -->
  <!-- ========================================================================= -->
  <section class="py-14 bg-slatecard border-b border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center divide-x-0 md:divide-x divide-slate-200/80">
        
        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-midnight tracking-tight mb-1">
            +500K<span class="text-brand-600">+</span>
          </div>
          <p data-i18n="stat_1" class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Comentarios Respondidos
          </p>
        </div>

        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-brand-600 tracking-tight mb-1">
            3.4x
          </div>
          <p data-i18n="stat_2" class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Aumento en Engagement
          </p>
        </div>

        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-midnight tracking-tight mb-1">
            99.8%
          </div>
          <p data-i18n="stat_3" class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Precisión de Voz Humana
          </p>
        </div>

        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-emerald-600 tracking-tight mb-1">
            &lt; 180ms
          </div>
          <p data-i18n="stat_4" class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Latencia de API en Vivo
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 4. MÓDULOS PRINCIPALES (Bento Grid de 3 Columnas) -->
  <!-- ========================================================================= -->
  <section id="funciones" class="py-24 bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Section Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span data-i18n="feat_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Infraestructura de Nueva Generación
        </span>
        <h2 data-i18n="feat_h2" class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-midnight tracking-tight mt-4 mb-4">
          Todo lo que un creador moderno necesita para dominar el algoritmo.
        </h2>
        <p data-i18n="feat_sub" class="text-base sm:text-lg text-slate-600 font-normal">
          Tres pilares integrados en una arquitectura de alta velocidad para maximizar tu retención, leads y alcance orgánico.
        </p>
      </div>

      <!-- 3 Columns Bento Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Card 1: Auto-Engagement -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-8 hover:border-brand-300 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-14 h-14 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl font-bold mb-6 group-hover:scale-110 transition-transform shadow-sm">
              🤖
            </div>
            <h3 data-i18n="card1_title" class="text-xl font-bold text-midnight mb-3">
              Auto-Engagement Contextual
            </h3>
            <p data-i18n="card1_desc" class="text-sm text-slate-600 leading-relaxed font-normal mb-6">
              IA que responde comentarios de forma natural y contextual en tus posts. Filtra spam, detecta intención de compra y fideliza seguidores 24/7 con tu propia voz de marca.
            </p>
          </div>
          <ul class="space-y-2.5 text-xs text-slate-600 font-semibold border-t border-slate-200 pt-5">
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="card1_b1">Calibración de Calidez, Profundidad y Energía</span></li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="card1_b2">Detección instantánea de Leads y Preguntas</span></li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="card1_b3">Modo Copilot (Sugerencias) y Autopilot</span></li>
          </ul>
        </div>

        <!-- Card 2: Smart Timing -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-8 hover:border-brand-300 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-14 h-14 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-bold mb-6 group-hover:scale-110 transition-transform shadow-sm">
              ⏰
            </div>
            <h3 data-i18n="card2_title" class="text-xl font-bold text-midnight mb-3">
              Smart Timing (Horarios Óptimos)
            </h3>
            <p data-i18n="card2_desc" class="text-sm text-slate-600 leading-relaxed font-normal mb-6">
              Análisis profundo de métricas e histórico de interacciones para recomendar el segundo exacto de publicación según los picos de actividad de tu audiencia real.
            </p>
          </div>
          <ul class="space-y-2.5 text-xs text-slate-600 font-semibold border-t border-slate-200 pt-5">
            <li class="flex items-center gap-2"><span class="text-blue-500 font-bold">✔</span> <span data-i18n="card2_b1">Mapas de calor de engagement por hora y día</span></li>
            <li class="flex items-center gap-2"><span class="text-blue-500 font-bold">✔</span> <span data-i18n="card2_b2">Predicción de alcance orgánico antes de publicar</span></li>
            <li class="flex items-center gap-2"><span class="text-blue-500 font-bold">✔</span> <span data-i18n="card2_b3">Alertas de ventanas de tráfico de alta retención</span></li>
          </ul>
        </div>

        <!-- Card 3: Multi-Publishing -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-8 hover:border-brand-300 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-14 h-14 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl font-bold mb-6 group-hover:scale-110 transition-transform shadow-sm">
              🚀
            </div>
            <h3 data-i18n="card3_title" class="text-xl font-bold text-midnight mb-3">
              Multi-Publishing & Copys IA
            </h3>
            <p data-i18n="card3_desc" class="text-sm text-slate-600 leading-relaxed font-normal mb-6">
              Sube una sola imagen o texto y la IA genera los copys adaptados al algoritmo de cada red social (Instagram, TikTok, Facebook) publicando en simultáneo.
            </p>
          </div>
          <ul class="space-y-2.5 text-xs text-slate-600 font-semibold border-t border-slate-200 pt-5">
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="card3_b1">Adaptación de ganchos (Hooks) y llamadas a la acción</span></li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="card3_b2">Selección inteligente de hashtags virales</span></li>
            <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="card3_b3">Distribución omnicanal con 1 solo clic</span></li>
          </ul>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 5. EL SIMULADOR INTERACTIVO -->
  <!-- ========================================================================= -->
  <section id="simulador" class="py-24 bg-gradient-to-b from-slatecard to-white border-b border-slate-200/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span data-i18n="sim_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Playground en Vivo
        </span>
        <h2 data-i18n="sim_h2" class="text-3xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-3">
          Prueba el Motor de XINDRO en Tiempo Real
        </h2>
        <p data-i18n="sim_sub" class="text-sm sm:text-base text-slate-600 font-normal">
          Selecciona un tono, escribe cualquier comentario de tu comunidad y observa cómo la IA genera respuestas hipercontextualizadas.
        </p>
      </div>

      <!-- Simulator Card Container -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-elevated-card overflow-hidden">
        
        <div class="bg-slate-900 text-white px-6 py-4 flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-2.5">
            <span class="text-brand-400 font-bold text-lg">⚡</span>
            <span class="text-sm font-bold tracking-tight">XINDRO Interactive Simulator v2.0</span>
          </div>
          <div class="flex items-center gap-2 text-xs font-mono text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            <span>Zero-Latency Heuristic + Gemini LLM Engine</span>
          </div>
        </div>

        <div class="p-6 sm:p-8">
          
          <!-- Controls Row -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            
            <div>
              <label data-i18n="sim_lbl_tone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                1. Tono de Marca:
              </label>
              <select id="sim-tone" class="w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer">
                <option value="mentor" data-i18n="sim_opt_mentor">🏛️ Estoico / Mentor Sabio</option>
                <option value="empathy" selected data-i18n="sim_opt_empathy">🤝 Cercano & Empático</option>
                <option value="growth" data-i18n="sim_opt_growth">🔥 Dinámico & Venta de Alto Valor</option>
              </select>
            </div>

            <div>
              <label data-i18n="sim_lbl_plat" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                2. Plataforma:
              </label>
              <select id="sim-platform" class="w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer">
                <option value="instagram">📸 Instagram</option>
                <option value="facebook">📘 Facebook</option>
                <option value="tiktok">🎵 TikTok</option>
              </select>
            </div>

            <div>
              <label data-i18n="sim_lbl_close" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                3. Pregunta al Final:
              </label>
              <select id="sim-closing" class="w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer">
                <option value="always" selected data-i18n="sim_opt_always">Siempre incluir pregunta</option>
                <option value="relevant" data-i18n="sim_opt_rel">Solo cuando sea relevante</option>
                <option value="never" data-i18n="sim_opt_never">Sin pregunta final</option>
              </select>
            </div>

          </div>

          <!-- Comment Input Area -->
          <div class="mb-6">
            <label data-i18n="sim_lbl_comment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Comentario de tu seguidor a simular:
            </label>
            <div class="relative">
              <textarea id="sim-input-text" rows="3" class="w-full bg-slatecard border border-slate-300 rounded-xl p-4 text-sm font-medium text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all resize-none" placeholder="Escribe un comentario...">Me encanta tu contenido pero siempre procrastino mis proyectos importantes por miedo al fracaso. ¿Qué consejo me das para empezar hoy mismo?</textarea>
            </div>
          </div>

          <!-- Trigger Button & Presets -->
          <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-2">
              <span data-i18n="sim_presets_title" class="text-xs font-bold text-slate-500">Comentarios rápidos:</span>
              <button type="button" onclick="Simulator.loadPreset(1)" data-i18n="sim_preset_1" class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                💡 "¿Precio del curso?"
              </button>
              <button type="button" onclick="Simulator.loadPreset(2)" data-i18n="sim_preset_2" class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                🔥 "Gran reflexión"
              </button>
            </div>

            <button type="button" id="sim-btn-generate" onclick="Simulator.generate()" class="px-6 py-3 rounded-xl text-sm font-bold text-white gradient-button flex items-center gap-2 shadow-glow-sm">
              <span data-i18n="sim_btn_gen">Generar Respuesta con IA</span>
              <span>⚡</span>
            </button>
          </div>

          <!-- Simulator Live Output Box -->
          <div id="sim-output-card" class="rounded-2xl bg-slate-50 border border-slate-200/90 p-6 transition-all duration-300">
            
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-3 mb-4">
              <div class="flex items-center gap-2">
                <span data-i18n="sim_res_title" class="text-xs font-bold uppercase tracking-wider text-slate-500">Resultado Generado por XINDRO</span>
                <span id="sim-badge-intent" class="text-[11px] font-bold px-2 py-0.5 rounded bg-brand-100 text-brand-800 border border-brand-200">
                  🎯 Intención: Consejo / Crecimiento
                </span>
              </div>
              <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                <span>Highlight Score: <strong id="sim-badge-score" class="text-brand-600 font-bold">94/100</strong></span>
                <span class="text-emerald-600 font-bold">⚡ 120ms</span>
              </div>
            </div>

            <p id="sim-output-text" class="text-sm sm:text-base text-slate-800 font-medium leading-relaxed mb-4">
              "El miedo al fracaso solo desaparece cuando actúas antes de que la mente empiece a dudar. Divide tu meta en una sola acción de 5 minutos para hoy. La perfección no existe, el progreso diario sí. ¿Qué pequeña tarea harás en los próximos 10 minutos? 👇"
            </p>

            <div class="flex items-center justify-between pt-3 border-t border-slate-200/60 text-xs text-slate-500">
              <span data-i18n="sim_autopilot_ok" class="flex items-center gap-1.5 text-emerald-600 font-semibold">
                <span>✔</span> Apto para Autopilot en Instagram y Facebook
              </span>
              <button type="button" onclick="Simulator.copyResponse()" id="sim-btn-copy" class="text-xs font-bold text-brand-600 hover:text-brand-800 flex items-center gap-1 transition-colors">
                <span data-i18n="sim_btn_copy">📋 Copiar</span>
              </button>
            </div>

          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 6. SMART TIMING (Análisis de Horarios Óptimos) -->
  <!-- ========================================================================= -->
  <section id="smart-timing" class="py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        
        <div class="lg:col-span-5">
          <span data-i18n="timing_badge" class="text-xs font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
            Algoritmo de Precisión
          </span>
          <h2 data-i18n="timing_h2" class="text-3xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-4">
            No publiques a ciegas. Publica en el segundo exacto.
          </h2>
          <p data-i18n="timing_sub" class="text-base text-slate-600 leading-relaxed font-normal mb-6">
            El Smart Timing de XINDRO cruza datos de más de 500,000 interacciones para identificar cuándo tus seguidores más valiosos están activos y listos para interactuar.
          </p>

          <div class="space-y-4">
            <div class="flex items-start gap-3.5">
              <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                📈
              </div>
              <div>
                <h4 data-i18n="timing_b1_t" class="text-sm font-bold text-midnight">Ventana de Máxima Retención</h4>
                <p data-i18n="timing_b1_d" class="text-xs text-slate-600 leading-relaxed">Publicar en el pico aumenta la retención inicial en los primeros 15 minutos en un 240%.</p>
              </div>
            </div>

            <div class="flex items-start gap-3.5">
              <div class="w-8 h-8 rounded-lg bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                🧠
              </div>
              <div>
                <h4 data-i18n="timing_b2_t" class="text-sm font-bold text-midnight">Alineación con el Algoritmo de Meta</h4>
                <p data-i18n="timing_b2_d" class="text-xs text-slate-600 leading-relaxed">Meta premia a las cuentas que responden rápido durante los picos de visualización.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Heatmap / Activity Widget -->
        <div class="lg:col-span-7 bg-slatecard rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-subtle-card">
          <div class="flex items-center justify-between mb-6">
            <div>
              <div data-i18n="timing_card_t" class="text-sm font-bold text-midnight">Pico de Engagement Semanal Detectado</div>
              <div data-i18n="timing_card_s" class="text-xs text-slate-500">Métricas analizadas en tiempo real</div>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
              +142% Alcance
            </span>
          </div>

          <div class="space-y-3 mb-6">
            <div>
              <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span data-i18n="timing_bar_1">Miércoles — 19:45 hrs</span>
                <span class="text-brand-600 font-bold" data-i18n="timing_bar_1_badge">98% Actividad Máxima 🔥</span>
              </div>
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div class="bg-gradient-to-r from-brand-500 to-indigo-600 h-full rounded-full" style="width: 98%;"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span data-i18n="timing_bar_2">Viernes — 21:00 hrs</span>
                <span class="text-slate-600" data-i18n="timing_bar_2_badge">84% Actividad</span>
              </div>
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div class="bg-indigo-400 h-full rounded-full" style="width: 84%;"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span data-i18n="timing_bar_3">Domingo — 20:15 hrs</span>
                <span class="text-slate-600" data-i18n="timing_bar_3_badge">76% Actividad</span>
              </div>
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div class="bg-indigo-300 h-full rounded-full" style="width: 76%;"></div>
              </div>
            </div>
          </div>

          <div class="p-4 rounded-xl bg-brand-50/70 border border-brand-200/60 flex items-center justify-between text-xs text-brand-900 font-medium">
            <span data-i18n="timing_recommendation">💡 <strong>Recomendación XINDRO:</strong> Programa tu próximo post hoy a las <strong>19:42 hrs</strong> para maximizar guardados y comentarios.</span>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 7. SECCIÓN DE API PARA CREADORES & DESARROLLADORES -->
  <!-- ========================================================================= -->
  <section id="api-docs" class="py-24 dark-mesh-bg bg-slate-900 text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span data-i18n="api_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-400 bg-brand-950/80 px-3.5 py-1 rounded-full border border-brand-500/40">
          Developer & Creator API
        </span>
        <h2 data-i18n="api_h2" class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight mt-4 mb-4">
          Ofrece la potencia de XINDRO dentro de tus propias herramientas.
        </h2>
        <p data-i18n="api_sub" class="text-base sm:text-lg text-slate-300 font-normal">
          Endpoints RESTful ultrarrápidos, webhooks criptográficos verificados y SDKs listos para integrar en tus bots, paneles o SaaS con 5 líneas de código.
        </p>
      </div>

      <!-- Code Terminal Component -->
      <div class="rounded-2xl bg-slate-950 border border-slate-800 shadow-2xl overflow-hidden mb-12">
        
        <div class="bg-slate-900 px-4 py-3 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-rose-500"></div>
            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
            <span class="text-xs font-mono text-slate-400 ml-2">POST https://socialapi.turbogram.site/api/agent.php</span>
          </div>

          <div class="flex items-center gap-1 bg-slate-950/70 p-1 rounded-lg border border-slate-800 text-xs font-mono">
            <button type="button" onclick="ApiTabs.switch('curl')" id="tab-curl" class="px-3 py-1 rounded bg-brand-600 text-white font-bold">cURL</button>
            <button type="button" onclick="ApiTabs.switch('js')" id="tab-js" class="px-3 py-1 rounded text-slate-400 hover:text-white">JavaScript</button>
            <button type="button" onclick="ApiTabs.switch('php')" id="tab-php" class="px-3 py-1 rounded text-slate-400 hover:text-white">PHP</button>
            <button type="button" onclick="ApiTabs.switch('python')" id="tab-python" class="px-3 py-1 rounded text-slate-400 hover:text-white">Python</button>
          </div>
        </div>

        <div class="p-6 relative">
          <button type="button" onclick="ApiTabs.copyCode()" id="btn-copy-code" class="absolute top-4 right-4 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono flex items-center gap-1.5 transition-colors">
            <span data-i18n="api_btn_copy">📋 Copiar Código</span>
          </button>

          <pre id="code-curl" class="text-xs sm:text-sm font-mono text-slate-200 overflow-x-auto leading-relaxed"><code>curl -X POST https://socialapi.turbogram.site/api/agent.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: tu_api_token" \
  -d '{
    "action": "test_voice_playground",
    "author_name": "Carlos Digital",
    "comment_text": "Me encanta tu contenido, ¿cómo forjar disciplina diaria?",
    "brand_tone": "mentor",
    "brand_warmth_level": 85,
    "brand_depth_level": 80
  }'</code></pre>

          <pre id="code-js" class="hidden text-xs sm:text-sm font-mono text-slate-200 overflow-x-auto leading-relaxed"><code>const response = await fetch('https://socialapi.turbogram.site/api/agent.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': 'TU_API_TOKEN_AQUI'
  },
  body: JSON.stringify({
    action: 'generate_replies',
    author_name: 'Carlos Digital',
    comment_text: 'Me encanta tu contenido, ¿cómo forjar disciplina diaria?',
    brand_tone: 'mentor'
  })
});

const data = await response.json();
console.log('Respuesta Generada:', data.replies.wisdom);</code></pre>

          <pre id="code-php" class="hidden text-xs sm:text-sm font-mono text-slate-200 overflow-x-auto leading-relaxed"><code>&lt;?php
$payload = json_encode([
    'action' => 'generate_replies',
    'author_name' => 'Carlos Digital',
    'comment_text' => 'Me encanta tu contenido, ¿cómo forjar disciplina diaria?',
    'brand_tone' => 'mentor'
]);

$ch = curl_init('https://socialapi.turbogram.site/api/agent.php');
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'X-CSRF-Token: TU_API_TOKEN']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = json_decode(curl_exec($ch), true);
curl_close($ch);

echo $result['replies']['wisdom'];
</code></pre>

          <pre id="code-python" class="hidden text-xs sm:text-sm font-mono text-slate-200 overflow-x-auto leading-relaxed"><code>import requests

url = "https://socialapi.turbogram.site/api/agent.php"
headers = {
    "Content-Type": "application/json",
    "X-CSRF-Token": "TU_API_TOKEN"
}
payload = {
    "action": "generate_replies",
    "author_name": "Carlos Digital",
    "comment_text": "Me encanta tu contenido, ¿cómo forjar disciplina diaria?",
    "brand_tone": "mentor"
}

res = requests.post(url, json=payload, headers=headers).json()
print("Respuesta IA:", res["replies"]["wisdom"])
</code></pre>
        </div>

        <div class="bg-slate-900/80 px-6 py-4 border-t border-slate-800 flex flex-wrap items-center justify-between text-xs font-mono text-slate-400">
          <div class="flex items-center gap-3">
            <span class="text-emerald-400 font-bold">Status: 200 OK</span>
            <span>Latency: 142ms</span>
            <span>Tokens: 0 (Local Engine)</span>
          </div>
          <span class="text-brand-400 font-bold">JSON Response Ready</span>
        </div>

      </div>

      <!-- Developer Features 3-Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <div class="p-5 rounded-xl bg-slate-950/60 border border-slate-800">
          <div class="text-brand-400 font-bold text-lg mb-2" data-i18n="api_f1_t">⚡ Webhooks en Tiempo Real</div>
          <p class="text-slate-400 text-xs leading-relaxed" data-i18n="api_f1_d">Recibe y procesa comentarios de Instagram y Facebook en milisegundos con verificación HMAC-SHA256.</p>
        </div>

        <div class="p-5 rounded-xl bg-slate-950/60 border border-slate-800">
          <div class="text-blue-400 font-bold text-lg mb-2" data-i18n="api_f2_t">🛡️ Multi-Tenant & Aislamiento</div>
          <p class="text-slate-400 text-xs leading-relaxed" data-i18n="api_f2_d">Cada usuario y creador cuenta con su propio espacio aislado de datos y rate limiting anti-abusos.</p>
        </div>

        <div class="p-5 rounded-xl bg-slate-950/60 border border-slate-800">
          <div class="text-emerald-400 font-bold text-lg mb-2" data-i18n="api_f3_t">🔌 Integración con Gemini & OpenAI</div>
          <p class="text-slate-400 text-xs leading-relaxed" data-i18n="api_f3_d">Conecta tus propias claves o utiliza nuestro motor heurístico local sin coste de tokens.</p>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 8. PRECIOS & PLANES -->
  <!-- ========================================================================= -->
  <section id="precios" class="py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-16">
        <span data-i18n="price_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Planes Transparentes
        </span>
        <h2 data-i18n="price_h2" class="text-3xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-3">
          Comienza gratis y escala con tu comunidad.
        </h2>
        <p data-i18n="price_sub" class="text-sm sm:text-base text-slate-600 font-normal">
          Sin contratos forzosos. Cancela en cualquier momento.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
        
        <!-- Plan 1: Starter -->
        <div class="rounded-3xl bg-slatecard border border-slate-200 p-8 flex flex-col justify-between hover:shadow-subtle-card transition-all">
          <div>
            <h3 data-i18n="plan1_t" class="text-lg font-bold text-midnight mb-1">Creador Starter</h3>
            <p data-i18n="plan1_d" class="text-xs text-slate-500 mb-6">Para creadores que dan sus primeros pasos.</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-black text-midnight">$0</span>
              <span data-i18n="plan1_p" class="text-xs text-slate-500 font-bold">/ mes gratis</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-600 font-medium mb-8">
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f1">Hasta 1 cuenta de Instagram/Facebook</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f2">100 respuestas automáticas / mes</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f3">Asistente Copilot IA</span></li>
              <li class="flex items-center gap-2 text-slate-400"><span class="text-slate-300">✖</span> <span data-i18n="plan1_f4">Acceso a API de desarrolladores</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan1_btn" class="w-full py-3 rounded-full text-center text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
            Crear Cuenta Gratis
          </a>
        </div>

        <!-- Plan 2: Pro Growth (Featured) -->
        <div class="rounded-3xl bg-white border-2 border-brand-500 p-8 flex flex-col justify-between shadow-glow-sm relative">
          <div data-i18n="plan2_badge" class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-brand-600 to-indigo-600 text-white text-[11px] font-extrabold uppercase tracking-wider px-3.5 py-1 rounded-full shadow-sm">
            Más Popular
          </div>
          <div>
            <h3 data-i18n="plan2_t" class="text-lg font-bold text-midnight mb-1">Creador Pro</h3>
            <p data-i18n="plan2_d" class="text-xs text-slate-500 mb-6">Para marcas y creadores en rápido crecimiento.</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-black text-midnight">$29</span>
              <span data-i18n="plan2_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-700 font-medium mb-8">
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f1">Cuentas ilimitadas de Meta</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f2">Respuestas ilimitadas en Autopilot</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f3">Calibrador de Voz de Marca personalizado</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f4">Smart Timing & Analíticas de Engagement</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f5">Soporte prioritario 24/7</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan2_btn" class="w-full py-3.5 rounded-full text-center text-sm font-bold text-white gradient-button shadow-glow-sm">
            Comenzar con Pro
          </a>
        </div>

        <!-- Plan 3: API & Agencias -->
        <div class="rounded-3xl bg-slatecard border border-slate-200 p-8 flex flex-col justify-between hover:shadow-subtle-card transition-all">
          <div>
            <h3 data-i18n="plan3_t" class="text-lg font-bold text-midnight mb-1">API & Agencias</h3>
            <p data-i18n="plan3_d" class="text-xs text-slate-500 mb-6">Para desarrolladores y agencias de marketing.</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-black text-midnight">$79</span>
              <span data-i18n="plan3_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-600 font-medium mb-8">
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f1">Acceso total a REST API & Webhooks</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f2">Gestión de hasta 25 clientes aislados</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f3">100,000 llamadas a API incluidas / mes</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f4">Marca blanca & Webhook dedicado</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan3_btn" class="w-full py-3 rounded-full text-center text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
            Acceso para Agencias
          </a>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 9. FOOTER PROFESIONAL CON MARCA DE AGUA GIGANTE -->
  <!-- ========================================================================= -->
  <footer class="starry-footer-bg starry-overlay pt-20 pb-12 text-slate-300 text-sm overflow-hidden relative">
    
    <!-- Giant Gamma-Style Watermark -->
    <div class="w-full text-center my-6 overflow-hidden select-none pointer-events-none">
      <div class="giant-watermark text-[16vw] font-black uppercase tracking-widest leading-none">
        XINDRO
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      
      <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-16 pt-6">
        
        <!-- Col 1: Descarga la app -->
        <div class="col-span-2 md:col-span-1">
          <h4 data-i18n="foot_c1_t" class="text-xs font-bold text-white uppercase tracking-wider mb-4">Descarga la app</h4>
          <div class="space-y-2.5">
            <a href="login.php" class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/90 border border-slate-700/80 hover:border-slate-500 transition-colors text-xs text-slate-200">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 0.92-2.85-.9.04-1.99.6-2.64 1.35-.58.67-.99 1.74-.86 2.76 1 .08 1.96-.51 2.58-1.26z"/></svg>
              <div>
                <div class="text-[9px] text-slate-400 leading-none">Consíguelo en el</div>
                <div class="text-[11px] font-bold text-white">App Store</div>
              </div>
            </a>
            <a href="login.php" class="flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900/90 border border-slate-700/80 hover:border-slate-500 transition-colors text-xs text-slate-200">
              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M3.609 1.814L13.792 12 3.61 22.186c-.188-.188-.299-.444-.299-.714V2.528c0-.27.111-.526.298-.714zM15.207 13.414l2.122 2.121-12.015 6.94 9.893-9.061zm0-2.828L5.314 1.525l12.015 6.94-2.122 2.121zm1.414 1.414l3.182-1.838c.848-.49.848-1.286 0-1.776l-3.182-1.838-1.414 1.414 1.414 4.038z"/></svg>
              <div>
                <div class="text-[9px] text-slate-400 leading-none">DISPONIBLE EN</div>
                <div class="text-[11px] font-bold text-white">Google Play</div>
              </div>
            </a>
          </div>
        </div>

        <!-- Col 2: Producto -->
        <div>
          <h4 data-i18n="foot_c2_t" class="text-xs font-bold text-white uppercase tracking-wider mb-4">Producto</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#precios" data-i18n="foot_c2_1" class="hover:text-white transition-colors">Precios</a></li>
            <li><a href="#simulador" data-i18n="foot_c2_2" class="hover:text-white transition-colors">Inspiración</a></li>
            <li><a href="#funciones" data-i18n="foot_c2_3" class="hover:text-white transition-colors">Educación</a></li>
            <li><a href="#simulador" data-i18n="foot_c2_4" class="hover:text-white transition-colors">Guía de prompts</a></li>
            <li><a href="#funciones" data-i18n="foot_c2_5" class="hover:text-white transition-colors">Plantillas</a></li>
            <li><a href="#smart-timing" data-i18n="foot_c2_6" class="hover:text-white transition-colors">Explorar</a></li>
            <li><a href="#api-docs" data-i18n="foot_c2_7" class="hover:text-white transition-colors">Integraciones</a></li>
          </ul>
        </div>

        <!-- Col 3: Empresa -->
        <div>
          <h4 data-i18n="foot_c3_t" class="text-xs font-bold text-white uppercase tracking-wider mb-4">Empresa</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#funciones" data-i18n="foot_c3_1" class="hover:text-white transition-colors">Acerca de</a></li>
            <li><a href="login.php" data-i18n="foot_c3_2" class="hover:text-white transition-colors">Carreras</a></li>
            <li><a href="login.php" data-i18n="foot_c3_3" class="hover:text-white transition-colors">Equipo</a></li>
            <li><a href="login.php" data-i18n="foot_c3_4" class="hover:text-white transition-colors">Ayuda</a></li>
            <li><a href="#simulador" data-i18n="foot_c3_5" class="hover:text-white transition-colors">Comunidad</a></li>
            <li><a href="#api-docs" data-i18n="foot_c3_6" class="hover:text-white transition-colors">Documentación para desarrolladores</a></li>
            <li><a href="privacy-policy.php" data-i18n="foot_c3_7" class="hover:text-white transition-colors">Seguridad</a></li>
          </ul>
        </div>

        <!-- Col 4: Redes sociales -->
        <div>
          <h4 data-i18n="foot_c4_t" class="text-xs font-bold text-white uppercase tracking-wider mb-4">Redes sociales</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="https://instagram.com" target="_blank" rel="noopener" class="hover:text-white transition-colors">Instagram</a></li>
            <li><a href="https://linkedin.com" target="_blank" rel="noopener" class="hover:text-white transition-colors">LinkedIn</a></li>
            <li><a href="https://tiktok.com" target="_blank" rel="noopener" class="hover:text-white transition-colors">TikTok</a></li>
            <li><a href="https://x.com" target="_blank" rel="noopener" class="hover:text-white transition-colors">X (Twitter)</a></li>
            <li><a href="https://youtube.com" target="_blank" rel="noopener" class="hover:text-white transition-colors">YouTube</a></li>
          </ul>
        </div>

        <!-- Col 5: Información legal -->
        <div>
          <h4 data-i18n="foot_c5_t" class="text-xs font-bold text-white uppercase tracking-wider mb-4">Información legal</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Acceptable Use Policy</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white transition-colors">Cookie Notice</a></li>
            <li><a href="javascript:void(0)" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_pref" class="hover:text-white transition-colors text-brand-300 font-semibold">Preferencias de cookies</a></li>
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Data Processing Addendum</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white transition-colors">Privacy Policy</a></li>
            <li><a href="data-deletion.php" class="hover:text-white transition-colors">Data Deletion (Meta)</a></li>
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Terms of Service</a></li>
          </ul>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="border-t border-slate-800/80 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
        <div class="flex items-center gap-2">
          <span class="font-bold text-white">XINDRO</span>
          <span>•</span>
          <span>© <?= date('Y') ?> Xindro Tech, Inc. <span data-i18n="foot_rights">Todos los derechos reservados.</span></span>
        </div>
        <div class="flex items-center gap-4">
          <span class="flex items-center gap-1.5 text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            Meta Graph API Verified
          </span>
          <a href="javascript:void(0)" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_pref" class="text-slate-400 hover:text-white underline">Ajustes de Cookies</a>
        </div>
      </div>

    </div>
  </footer>

  <!-- ========================================================================= -->
  <!-- 10. POPUP PROFESIONAL DE COOKIES (Bottom-Left Banner RGPD/CCPA Compliant) -->
  <!-- ========================================================================= -->
  <div id="cookie-consent-modal" class="fixed bottom-5 left-5 z-50 max-w-[430px] w-[calc(100%-40px)] bg-white/95 backdrop-blur-md rounded-2xl shadow-cookie-popup p-5 border border-slate-200 text-slate-800 cookie-animate hidden">
    
    <!-- Top Row: Title + Close Button -->
    <div class="flex items-start justify-between gap-3 mb-2">
      <div class="text-sm font-extrabold text-midnight flex items-center gap-1.5">
        <span data-i18n="cookie_title">Sobre nuestras cookies</span>
        <span>🍪</span>
      </div>
      <button type="button" onclick="CookieConsent.close()" class="text-slate-400 hover:text-slate-700 text-lg font-bold leading-none p-1" title="Cerrar">
        &times;
      </button>
    </div>

    <!-- Description Text -->
    <p class="text-[12px] text-slate-600 leading-relaxed mb-4">
      <span data-i18n="cookie_desc_1">Utilizamos cookies y tecnologías similares según se establece en nuestra</span> <a href="privacy-policy.php" data-i18n="cookie_link" class="text-blue-600 hover:underline font-semibold">Política de Cookies</a>. <span data-i18n="cookie_desc_2">Al hacer clic en "Aceptar Todo", aceptas el uso de cookies para personalizar tu experiencia, optimizar la IA y analizar el tráfico de la API.</span>
    </p>

    <!-- Action Buttons Row -->
    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
      <button type="button" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_settings" class="text-[11px] font-bold px-3.5 py-1.5 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-100 transition-colors">
        Configurar Cookies
      </button>

      <div class="flex items-center gap-1.5">
        <button type="button" onclick="CookieConsent.rejectAll()" data-i18n="cookie_btn_reject" class="text-[11px] font-bold px-3.5 py-1.5 rounded-full bg-slate-900 hover:bg-black text-white transition-colors">
          Rechazar Todo
        </button>
        <button type="button" onclick="CookieConsent.acceptAll()" data-i18n="cookie_btn_accept" class="text-[11px] font-bold px-4 py-1.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-colors">
          Aceptar Todo
        </button>
      </div>
    </div>

  </div>

  <!-- Detailed Cookies Preferences Modal -->
  <div id="cookie-settings-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-slate-200 text-slate-800 max-h-[90vh] overflow-y-auto">
      
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h3 data-i18n="modal_pref_title" class="text-lg font-extrabold text-midnight flex items-center gap-2">
          <span>Centro de Preferencias de Cookies</span>
          <span>🛡️</span>
        </h3>
        <button type="button" onclick="CookieConsent.closeSettings()" class="text-slate-400 hover:text-slate-700 text-2xl font-bold leading-none">&times;</button>
      </div>
      
      <p data-i18n="modal_pref_desc" class="text-xs text-slate-600 mb-6 leading-relaxed">
        Cumplimos estrictamente con las normativas internacionales de protección de datos (RGPD de la UE, CCPA y LGPD Brasil). Selecciona qué tipos de cookies deseas permitir:
      </p>

      <div class="space-y-4 text-xs mb-6">
        
        <!-- Category 1: Essential (Required) -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
          <div class="flex items-center justify-between mb-1">
            <div data-i18n="cookie_cat1_t" class="font-bold text-midnight text-sm">Cookies Técnicas y de Seguridad (Esenciales)</div>
            <span data-i18n="cookie_cat1_status" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Siempre Activas</span>
          </div>
          <p data-i18n="cookie_cat1_d" class="text-slate-500 text-[11.5px] leading-relaxed">
            Requeridas para la autenticación de sesión, tokens de seguridad CSRF y protección de la infraestructura contra ataques.
          </p>
        </div>

        <!-- Category 2: Analytics & Performance -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
          <div>
            <div data-i18n="cookie_cat2_t" class="font-bold text-midnight text-sm mb-1">Cookies de Rendimiento & Analítica</div>
            <p data-i18n="cookie_cat2_d" class="text-slate-500 text-[11.5px] leading-relaxed">
              Nos permiten medir la velocidad de respuesta de la IA, uso de endpoints y optimizar la experiencia de los creadores.
            </p>
          </div>
          <input type="checkbox" id="chk-analytics-cookies" checked class="w-5 h-5 accent-brand-600 rounded cursor-pointer shrink-0" />
        </div>

        <!-- Category 3: Personalization & Language -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
          <div>
            <div data-i18n="cookie_cat3_t" class="font-bold text-midnight text-sm mb-1">Cookies de Personalización & Idioma</div>
            <p data-i18n="cookie_cat3_d" class="text-slate-500 text-[11.5px] leading-relaxed">
              Recuerdan tus preferencias de idioma (Español, Inglés, Portugués), tono predeterminado y configuraciones del simulador.
            </p>
          </div>
          <input type="checkbox" id="chk-personalization-cookies" checked class="w-5 h-5 accent-brand-600 rounded cursor-pointer shrink-0" />
        </div>

      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
        <button type="button" onclick="CookieConsent.saveCustom()" data-i18n="modal_pref_save" class="px-5 py-2.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors">
          Guardar Mis Preferencias
        </button>
      </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- 11. SISTEMA MULTI-IDIOMA i18n + MOTOR DEL SIMULADOR -->
  <!-- ========================================================================= -->
  <script>
    // -------------------------------------------------------------
    // 1. DICCIONARIO MULTI-IDIOMA (ES / EN / PT)
    // -------------------------------------------------------------
    const I18n = {
      current: '<?= $initialLang ?>',

      dict: {
        es: {
          page_title: "XINDRO — El Sistema Operativo de IA para Creadores de Contenido",
          page_desc: "Automatiza tus redes sociales. Responde comentarios en piloto automático, analiza métricas de engagement y publica en múltiples plataformas desde una sola API.",
          nav_products: "Productos",
          nav_simulator: "Simulador",
          nav_solutions: "Soluciones",
          nav_api: "API Creadores",
          nav_pricing: "Precios",
          nav_login: "Iniciar sesión",
          nav_cta: "Comienza gratis",
          nav_dashboard: "Ir a mi Panel",
          hero_badge: "El sistema operativo de IA para creadores de contenido",
          hero_h1_p1: "Automatiza tus redes sociales.",
          hero_h1_p2: "Escala tu comunidad sin perder el toque humano.",
          hero_sub: "Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y publica en múltiples plataformas desde una sola API.",
          hero_cta_sim: "Prueba el Simulador",
          hero_cta_api: "Documentación API",
          hero_card_title: "XINDRO Live Copilot — Flujo en Tiempo Real",
          hero_card_status: "Meta Webhook Activo",
          hero_card_time: "Instagram • Hace 2 seg",
          hero_card_intent: "🎯 Intención: <strong class=\"text-brand-600\">Pregunta de Alto Valor</strong>",
          hero_card_calibrated: "IA Calibrada",
          hero_card_bot_reply: "🤖 Respuesta con Voz de Marca",
          hero_card_tone: "Tono: Mentor Empático",
          hero_card_retention: "🚀 Retención: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Listo para postear",
          hero_comment_sample: "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?",
          hero_reply_sample: "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇",
          stat_1: "Comentarios Respondidos",
          stat_2: "Aumento en Engagement",
          stat_3: "Precisión de Voz Humana",
          stat_4: "Latencia de API en Vivo",
          feat_badge: "Infraestructura de Nueva Generación",
          feat_h2: "Todo lo que un creador moderno necesita para dominar el algoritmo.",
          feat_sub: "Tres pilares integrados en una arquitectura de alta velocidad para maximizar tu retención, leads y alcance orgánico.",
          card1_title: "Auto-Engagement Contextual",
          card1_desc: "IA que responde comentarios de forma natural y contextual en tus posts. Filtra spam, detecta intención de compra y fideliza seguidores 24/7 con tu propia voz de marca.",
          card1_b1: "Calibración de Calidez, Profundidad y Energía",
          card1_b2: "Detección instantánea de Leads y Preguntas",
          card1_b3: "Modo Copilot (Sugerencias) y Autopilot",
          card2_title: "Smart Timing (Horarios Óptimos)",
          card2_desc: "Análisis profundo de métricas e histórico de interacciones para recomendar el segundo exacto de publicación según los picos de actividad de tu audiencia real.",
          card2_b1: "Mapas de calor de engagement por hora y día",
          card2_b2: "Predicción de alcance orgánico antes de publicar",
          card2_b3: "Alertas de ventanas de tráfico de alta retención",
          card3_title: "Multi-Publishing & Copys IA",
          card3_desc: "Sube una sola imagen o texto y la IA genera los copys adaptados al algoritmo de cada red social (Instagram, TikTok, Facebook) publicando en simultáneo.",
          card3_b1: "Adaptación de ganchos (Hooks) y llamadas a la acción",
          card3_b2: "Selección inteligente de hashtags virales",
          card3_b3: "Distribución omnicanal con 1 solo clic",
          sim_badge: "Playground en Vivo",
          sim_h2: "Prueba el Motor de XINDRO en Tiempo Real",
          sim_sub: "Selecciona un tono, escribe cualquier comentario de tu comunidad y observa cómo la IA genera respuestas hipercontextualizadas.",
          sim_lbl_tone: "1. Tono de Marca:",
          sim_opt_mentor: "🏛️ Estoico / Mentor Sabio",
          sim_opt_empathy: "🤝 Cercano & Empático",
          sim_opt_growth: "🔥 Dinámico & Venta de Alto Valor",
          sim_lbl_plat: "2. Plataforma:",
          sim_lbl_close: "3. Pregunta al Final:",
          sim_opt_always: "Siempre incluir pregunta",
          sim_opt_rel: "Solo cuando sea relevante",
          sim_opt_never: "Sin pregunta final",
          sim_lbl_comment: "Comentario de tu seguidor a simular:",
          sim_presets_title: "Comentarios rápidos:",
          sim_preset_1: "💡 \"¿Precio del curso?\"",
          sim_preset_2: "🔥 \"Gran reflexión\"",
          sim_btn_gen: "Generar Respuesta con IA",
          sim_res_title: "Resultado Generado por XINDRO",
          sim_autopilot_ok: "✔ Apto para Autopilot en Instagram y Facebook",
          sim_btn_copy: "📋 Copiar",
          timing_badge: "Algoritmo de Precisión",
          timing_h2: "No publiques a ciegas. Publica en el segundo exacto.",
          timing_sub: "El Smart Timing de XINDRO cruza datos de más de 500,000 interacciones para identificar cuándo tus seguidores más valiosos están activos y listos para interactuar.",
          timing_b1_t: "Ventana de Máxima Retención",
          timing_b1_d: "Publicar en el pico aumenta la retención inicial en los primeros 15 minutos en un 240%.",
          timing_b2_t: "Alineación con el Algoritmo de Meta",
          timing_b2_d: "Meta premia a las cuentas que responden rápido durante los picos de visualización.",
          timing_card_t: "Pico de Engagement Semanal Detectado",
          timing_card_s: "Métricas analizadas en tiempo real",
          timing_bar_1: "Miércoles — 19:45 hrs",
          timing_bar_1_badge: "98% Actividad Máxima 🔥",
          timing_bar_2: "Viernes — 21:00 hrs",
          timing_bar_2_badge: "84% Actividad",
          timing_bar_3: "Domingo — 20:15 hrs",
          timing_bar_3_badge: "76% Actividad",
          timing_recommendation: "💡 <strong>Recomendación XINDRO:</strong> Programa tu próximo post hoy a las <strong>19:42 hrs</strong> para maximizar guardados y comentarios.",
          api_badge: "Developer & Creator API",
          api_h2: "Ofrece la potencia de XINDRO dentro de tus propias herramientas.",
          api_sub: "Endpoints RESTful ultrarrápidos, webhooks criptográficos verificados y SDKs listos para integrar en tus bots, paneles o SaaS con 5 líneas de código.",
          api_btn_copy: "📋 Copiar Código",
          api_f1_t: "⚡ Webhooks en Tiempo Real",
          api_f1_d: "Recibe y procesa comentarios de Instagram y Facebook en milisegundos con verificación HMAC-SHA256.",
          api_f2_t: "🛡️ Multi-Tenant & Aislamiento",
          api_f2_d: "Cada usuario y creador cuenta con su propio espacio aislado de datos y rate limiting anti-abusos.",
          api_f3_t: "🔌 Integración con Gemini & OpenAI",
          api_f3_d: "Conecta tus propias claves o utiliza nuestro motor heurístico local sin coste de tokens.",
          price_badge: "Planes Transparentes",
          price_h2: "Comienza gratis y escala con tu comunidad.",
          price_sub: "Sin contratos forzosos. Cancela en cualquier momento.",
          plan1_t: "Creador Starter",
          plan1_d: "Para creadores que dan sus primeros pasos.",
          plan1_p: "/ mes gratis",
          plan1_f1: "Hasta 1 cuenta de Instagram/Facebook",
          plan1_f2: "100 respuestas automáticas / mes",
          plan1_f3: "Asistente Copilot IA",
          plan1_f4: "Acceso a API de desarrolladores",
          plan1_btn: "Crear Cuenta Gratis",
          plan2_badge: "Más Popular",
          plan2_t: "Creador Pro",
          plan2_d: "Para marcas y creadores en rápido crecimiento.",
          plan2_p: "/ mes",
          plan2_f1: "Cuentas ilimitadas de Meta",
          plan2_f2: "Respuestas ilimitadas en Autopilot",
          plan2_f3: "Calibrador de Voz de Marca personalizado",
          plan2_f4: "Smart Timing & Analíticas de Engagement",
          plan2_f5: "Soporte prioritario 24/7",
          plan2_btn: "Comenzar con Pro",
          plan3_t: "API & Agencias",
          plan3_d: "Para desarrolladores y agencias de marketing.",
          plan3_p: "/ mes",
          plan3_f1: "Acceso total a REST API & Webhooks",
          plan3_f2: "Gestión de hasta 25 clientes aislados",
          plan3_f3: "100,000 llamadas a API incluidas / mes",
          plan3_f4: "Marca blanca & Webhook dedicado",
          plan3_btn: "Acceso para Agencias",
          foot_c1_t: "Descarga la app",
          foot_c2_t: "Producto",
          foot_c2_1: "Precios",
          foot_c2_2: "Inspiración",
          foot_c2_3: "Educación",
          foot_c2_4: "Guía de prompts",
          foot_c2_5: "Plantillas",
          foot_c2_6: "Explorar",
          foot_c2_7: "Integraciones",
          foot_c3_t: "Empresa",
          foot_c3_1: "Acerca de",
          foot_c3_2: "Carreras",
          foot_c3_3: "Equipo",
          foot_c3_4: "Ayuda",
          foot_c3_5: "Comunidad",
          foot_c3_6: "Documentación para desarrolladores",
          foot_c3_7: "Seguridad",
          foot_c4_t: "Redes sociales",
          foot_c5_t: "Información legal",
          foot_rights: "Todos los derechos reservados.",
          cookie_title: "Sobre nuestras cookies",
          cookie_desc_1: "Utilizamos cookies y tecnologías similares según se establece en nuestra",
          cookie_link: "Política de Cookies",
          cookie_desc_2: "Al hacer clic en \"Aceptar Todo\", aceptas el uso de cookies para personalizar tu experiencia, optimizar la IA y analizar el tráfico de la API.",
          cookie_btn_settings: "Configurar Cookies",
          cookie_btn_reject: "Rechazar Todo",
          cookie_btn_accept: "Aceptar Todo",
          cookie_btn_pref: "Preferencias de cookies",
          modal_pref_title: "Centro de Preferencias de Cookies",
          modal_pref_desc: "Cumplimos estrictamente con las normativas internacionales de protección de datos (RGPD de la UE, CCPA y LGPD Brasil). Selecciona qué tipos de cookies deseas permitir:",
          cookie_cat1_t: "Cookies Técnicas y de Seguridad (Esenciales)",
          cookie_cat1_status: "Siempre Activas",
          cookie_cat1_d: "Requeridas para la autenticación de sesión, tokens de seguridad CSRF y protección de la infraestructura contra ataques.",
          cookie_cat2_t: "Cookies de Rendimiento & Analítica",
          cookie_cat2_d: "Nos permiten medir la velocidad de respuesta de la IA, uso de endpoints y optimizar la experiencia de los creadores.",
          cookie_cat3_t: "Cookies de Personalización & Idioma",
          cookie_cat3_d: "Recuerdan tus preferencias de idioma (Español, Inglés, Portugués), tono predeterminado y configuraciones del simulador.",
          modal_pref_save: "Guardar Mis Preferencias"
        },

        en: {
          page_title: "XINDRO — The AI Operating System for Content Creators",
          page_desc: "Automate your social media. Reply to comments on autopilot, analyze engagement metrics to find your perfect posting time, and publish across multiple platforms from a single API.",
          nav_products: "Products",
          nav_simulator: "Simulator",
          nav_solutions: "Solutions",
          nav_api: "Creator API",
          nav_pricing: "Pricing",
          nav_login: "Log in",
          nav_cta: "Get started free",
          nav_dashboard: "Go to Dashboard",
          hero_badge: "The AI operating system for content creators",
          hero_h1_p1: "Automate your social media.",
          hero_h1_p2: "Scale your community without losing the human touch.",
          hero_sub: "Reply to comments on autopilot, analyze engagement metrics to find your perfect posting time, and publish across multiple platforms from a single API.",
          hero_cta_sim: "Try the Simulator",
          hero_cta_api: "API Documentation",
          hero_card_title: "XINDRO Live Copilot — Real-Time Flow",
          hero_card_status: "Meta Webhook Active",
          hero_card_time: "Instagram • 2s ago",
          hero_card_intent: "🎯 Intent: <strong class=\"text-brand-600\">High-Value Question</strong>",
          hero_card_calibrated: "Calibrated AI",
          hero_card_bot_reply: "🤖 Brand Voice Reply",
          hero_card_tone: "Tone: Empathetic Mentor",
          hero_card_retention: "🚀 Retention: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Ready to post",
          hero_comment_sample: "I've been trying to stay consistent on social media for weeks but I run out of ideas and lose motivation. How do you structure your daily routine?",
          hero_reply_sample: "Alejandro, the key isn't motivation that comes and goes, but systems. Block 45 min every morning before checking your phone. Daily discipline beats sporadic inspiration. What is the first thing you will do tomorrow when you wake up? 👇",
          stat_1: "Comments Replied",
          stat_2: "Increase in Engagement",
          stat_3: "Human Voice Accuracy",
          stat_4: "Live API Latency",
          feat_badge: "Next-Gen Infrastructure",
          feat_h2: "Everything a modern creator needs to master the algorithm.",
          feat_sub: "Three pillars integrated into a high-speed architecture to maximize your retention, qualified leads, and organic reach.",
          card1_title: "Contextual Auto-Engagement",
          card1_desc: "AI that responds to comments naturally and contextually on your posts. Filters spam, detects buying intent, and retains followers 24/7 with your authentic brand voice.",
          card1_b1: "Warmth, Depth & Energy calibration",
          card1_b2: "Instant Lead and Question detection",
          card1_b3: "Copilot (Suggestions) & Autopilot modes",
          card2_title: "Smart Timing (Optimal Posting Hours)",
          card2_desc: "Deep analysis of historical engagement metrics to recommend the exact second to post based on your real audience's peak activity.",
          card2_b1: "Hourly and daily engagement heatmaps",
          card2_b2: "Organic reach prediction before posting",
          card2_b3: "High-retention traffic window alerts",
          card3_title: "Multi-Publishing & AI Copies",
          card3_desc: "Upload a single image or text and the AI crafts tailored copy adapted to each platform's algorithm (Instagram, TikTok, Facebook) publishing simultaneously.",
          card3_b1: "Hooks and call-to-action adaptation",
          card3_b2: "Intelligent viral hashtag selection",
          card3_b3: "Omnichannel distribution in 1 click",
          sim_badge: "Live Playground",
          sim_h2: "Test the XINDRO Engine in Real Time",
          sim_sub: "Select a tone, type any comment from your community, and watch the AI craft hyper-contextualized responses.",
          sim_lbl_tone: "1. Brand Tone:",
          sim_opt_mentor: "🏛️ Stoic / Wise Mentor",
          sim_opt_empathy: "🤝 Friendly & Empathetic",
          sim_opt_growth: "🔥 Dynamic & High-Ticket Sales",
          sim_lbl_plat: "2. Platform:",
          sim_lbl_close: "3. Closing Question:",
          sim_opt_always: "Always include question",
          sim_opt_rel: "Only when relevant",
          sim_opt_never: "No closing question",
          sim_lbl_comment: "Follower comment to simulate:",
          sim_presets_title: "Quick presets:",
          sim_preset_1: "💡 \"Course pricing?\"",
          sim_preset_2: "🔥 \"Great post\"",
          sim_btn_gen: "Generate AI Response",
          sim_res_title: "Result Generated by XINDRO",
          sim_autopilot_ok: "✔ Eligible for Autopilot on Instagram and Facebook",
          sim_btn_copy: "📋 Copy",
          timing_badge: "Precision Algorithm",
          timing_h2: "Don't post blindly. Post at the exact second.",
          timing_sub: "XINDRO Smart Timing analyzes over 500,000 interactions to identify when your most valuable followers are active and ready to engage.",
          timing_b1_t: "Peak Retention Window",
          timing_b1_d: "Posting at the peak boosts early 15-minute retention by over 240%.",
          timing_b2_t: "Meta Algorithm Alignment",
          timing_b2_d: "Meta rewards accounts that engage rapidly during viewer traffic spikes.",
          timing_card_t: "Weekly Engagement Peak Detected",
          timing_card_s: "Metrics analyzed in real-time",
          timing_bar_1: "Wednesday — 19:45 hrs",
          timing_bar_1_badge: "98% Peak Activity 🔥",
          timing_bar_2: "Friday — 21:00 hrs",
          timing_bar_2_badge: "84% Activity",
          timing_bar_3: "Sunday — 20:15 hrs",
          timing_bar_3_badge: "76% Activity",
          timing_recommendation: "💡 <strong>XINDRO Recommendation:</strong> Schedule your next post today at <strong>19:42 hrs</strong> to maximize saves and comments.",
          api_badge: "Developer & Creator API",
          api_h2: "Deliver the power of XINDRO inside your own tools.",
          api_sub: "Ultra-fast RESTful endpoints, cryptographic verified webhooks, and SDKs ready to integrate in your bots, dashboards, or SaaS in 5 lines of code.",
          api_btn_copy: "📋 Copy Code",
          api_f1_t: "⚡ Real-Time Webhooks",
          api_f1_d: "Ingest and process Instagram & Facebook comments in milliseconds with HMAC-SHA256 signatures.",
          api_f2_t: "🛡️ Multi-Tenant & Isolation",
          api_f2_d: "Each creator and client enjoys isolated storage, dedicated configs, and anti-abuse rate limiting.",
          api_f3_t: "🔌 Gemini & OpenAI Integration",
          api_f3_d: "Plug in your own API keys or leverage our zero-token local heuristic engine at no cost.",
          price_badge: "Transparent Pricing",
          price_h2: "Start free and scale with your audience.",
          price_sub: "No lock-in contracts. Cancel anytime.",
          plan1_t: "Creator Starter",
          plan1_d: "For creators taking their first steps.",
          plan1_p: "/ month free",
          plan1_f1: "Up to 1 Instagram/Facebook account",
          plan1_f2: "100 automated replies / month",
          plan1_f3: "AI Copilot Assistant",
          plan1_f4: "Developer API access",
          plan1_btn: "Create Free Account",
          plan2_badge: "Most Popular",
          plan2_t: "Creator Pro",
          plan2_d: "For rapidly growing creators and personal brands.",
          plan2_p: "/ month",
          plan2_f1: "Unlimited Meta accounts",
          plan2_f2: "Unlimited Autopilot replies",
          plan2_f3: "Custom Brand Voice Calibrator",
          plan2_f4: "Smart Timing & Engagement Analytics",
          plan2_f5: "24/7 Priority Support",
          plan2_btn: "Get Started with Pro",
          plan3_t: "API & Agencies",
          plan3_d: "For developers and marketing agencies.",
          plan3_p: "/ month",
          plan3_f1: "Full REST API & Webhooks access",
          plan3_f2: "Manage up to 25 isolated client tenants",
          plan3_f3: "100,000 API calls included / month",
          plan3_f4: "White-label & Dedicated Webhook",
          plan3_btn: "Agency Access",
          foot_c1_t: "Get the app",
          foot_c2_t: "Product",
          foot_c2_1: "Pricing",
          foot_c2_2: "Inspiration",
          foot_c2_3: "Education",
          foot_c2_4: "Prompt guide",
          foot_c2_5: "Templates",
          foot_c2_6: "Explore",
          foot_c2_7: "Integrations",
          foot_c3_t: "Company",
          foot_c3_1: "About",
          foot_c3_2: "Careers",
          foot_c3_3: "Team",
          foot_c3_4: "Help",
          foot_c3_5: "Community",
          foot_c3_6: "Developer Docs",
          foot_c3_7: "Security",
          foot_c4_t: "Socials",
          foot_c5_t: "Legal",
          foot_rights: "All rights reserved.",
          cookie_title: "About our cookies",
          cookie_desc_1: "We use cookies and similar technologies as set out in our",
          cookie_link: "Cookie Notice",
          cookie_desc_2: "By clicking \"Accept All\", you agree to our use of optional cookies to personalize your experience, optimize AI inference, and analyze API traffic.",
          cookie_btn_settings: "Cookies Settings",
          cookie_btn_reject: "Reject All",
          cookie_btn_accept: "Accept All",
          cookie_btn_pref: "Cookie Preferences",
          modal_pref_title: "Cookie Preference Center",
          modal_pref_desc: "We strictly comply with global data privacy regulations (EU GDPR, CCPA, and Brazil LGPD). Select which cookie categories you wish to allow:",
          cookie_cat1_t: "Essential & Security Cookies (Required)",
          cookie_cat1_status: "Always Active",
          cookie_cat1_d: "Required for session authentication, CSRF security tokens, and infrastructure attack defense.",
          cookie_cat2_t: "Performance & Analytics Cookies",
          cookie_cat2_d: "Allow us to monitor AI response latency, endpoint usage, and optimize creator workflows.",
          cookie_cat3_t: "Personalization & Language Cookies",
          cookie_cat3_d: "Remember your language preferences (Spanish, English, Portuguese), default tone, and simulator presets.",
          modal_pref_save: "Save My Preferences"
        },

        pt: {
          page_title: "XINDRO — O Sistema Operacional de IA para Criadores de Conteúdo",
          page_desc: "Automatize suas redes sociais. Responda a comentários no piloto automático, analise métricas de engajamento para encontrar seu horário perfeito e publique em várias plataformas a partir de uma única API.",
          nav_products: "Produtos",
          nav_simulator: "Simulador",
          nav_solutions: "Soluções",
          nav_api: "API Criadores",
          nav_pricing: "Preços",
          nav_login: "Entrar",
          nav_cta: "Comece grátis",
          nav_dashboard: "Ir ao Painel",
          hero_badge: "O sistema operacional de IA para criadores de conteúdo",
          hero_h1_p1: "Automatize suas redes sociais.",
          hero_h1_p2: "Escale sua comunidade sem perder o toque humano.",
          hero_sub: "Responda a comentários no piloto automático, analise métricas de engajamento para encontrar seu horário perfeito e publique em várias plataformas a partir de uma única API.",
          hero_cta_sim: "Testar o Simulador",
          hero_cta_api: "Documentação da API",
          hero_card_title: "XINDRO Live Copilot — Fluxo em Tempo Real",
          hero_card_status: "Meta Webhook Ativo",
          hero_card_time: "Instagram • Há 2 seg",
          hero_card_intent: "🎯 Intenção: <strong class=\"text-brand-600\">Pergunta de Alto Valor</strong>",
          hero_card_calibrated: "IA Calibrada",
          hero_card_bot_reply: "🤖 Resposta com Tom de Marca",
          hero_card_tone: "Tom: Mentor Empático",
          hero_card_retention: "🚀 Retenção: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Pronto para postar",
          hero_comment_sample: "Estou há semanas tentando ter consistência nas redes, mas fico sem ideias e perco a motivação. Como vocês estruturam a rotina diária?",
          hero_reply_sample: "Alejandro, o segredo não é a motivação que vai e vem, mas os sistemas. Bloqueie 45 min toda manhã antes de olhar o celular. A disciplina diária supera a inspiração passageira. Qual é a primeira coisa que você fará amanhã ao acordar? 👇",
          stat_1: "Comentários Respondidos",
          stat_2: "Aumento no Engajamento",
          stat_3: "Precisão de Voz Humana",
          stat_4: "Latência de API em Tempo Real",
          feat_badge: "Infraestrutura de Nova Geração",
          feat_h2: "Tudo o que um criador moderno precisa para dominar o algoritmo.",
          feat_sub: "Três pilares integrados em uma arquitetura de alta velocidade para maximizar sua retenção, leads e alcance orgânico.",
          card1_title: "Autoengajamento Contextual",
          card1_desc: "IA que responde a comentários de forma natural e contextual nos seus posts. Filtra spam, identifica intenção de compra e fideliza seguidores 24/7 com sua própria voz de marca.",
          card1_b1: "Calibração de Acolhimento, Profundidade e Energia",
          card1_b2: "Detecção instantânea de Leads e Perguntas",
          card1_b3: "Modos Copilot (Sugestões) e Autopilot",
          card2_title: "Smart Timing (Horários Otimizados)",
          card2_desc: "Análise profunda de métricas e histórico de interações para recomendar o segundo exato de postagem com base nos picos de atividade da sua audiência real.",
          card2_b1: "Mapas de calor de engajamento por hora e dia",
          card2_b2: "Previsão de alcance orgânico antes de postar",
          card2_b3: "Alertas de janelas de tráfego de alta retenção",
          card3_title: "Multipublicação e Copys de IA",
          card3_desc: "Envie uma única imagem ou texto e a IA gera os copys adaptados ao algoritmo de cada rede social (Instagram, TikTok, Facebook) publicando simultaneamente.",
          card3_b1: "Adaptação de ganchos (Hooks) e chamadas para ação",
          card3_b2: "Seleção inteligente de hashtags virais",
          card3_b3: "Distribuição multicanal com 1 clique",
          sim_badge: "Playground ao Vivo",
          sim_h2: "Teste o Motor do XINDRO em Tempo Real",
          sim_sub: "Selecione um tom, digite qualquer comentário da sua comunidade e veja a IA gerar respostas hipercontextualizadas.",
          sim_lbl_tone: "1. Tom de Marca:",
          sim_opt_mentor: "🏛️ Estoico / Mentor Sábio",
          sim_opt_empathy: "🤝 Acolhedor & Empático",
          sim_opt_growth: "🔥 Dinâmico & Vendas High-Ticket",
          sim_lbl_plat: "2. Plataforma:",
          sim_lbl_close: "3. Pergunta no Final:",
          sim_opt_always: "Sempre incluir pergunta",
          sim_opt_rel: "Apenas quando relevante",
          sim_opt_never: "Sem pergunta final",
          sim_lbl_comment: "Comentário do seguidor para simular:",
          sim_presets_title: "Comentários rápidos:",
          sim_preset_1: "💡 \"Preço do curso?\"",
          sim_preset_2: "🔥 \"Ótima reflexão\"",
          sim_btn_gen: "Gerar Resposta com IA",
          sim_res_title: "Resultado Gerado pelo XINDRO",
          sim_autopilot_ok: "✔ Apto para Autopilot no Instagram e Facebook",
          sim_btn_copy: "📋 Copiar",
          timing_badge: "Algoritmo de Precisão",
          timing_h2: "Não publique no escuro. Publique no segundo exato.",
          timing_sub: "O Smart Timing do XINDRO analisa mais de 500.000 interações para identificar quando seus seguidores mais valiosos estão ativos e prontos para interagir.",
          timing_b1_t: "Janela de Máxima Retenção",
          timing_b1_d: "Publicar no pico aumenta a retenção inicial nos primeiros 15 minutos em 240%.",
          timing_b2_t: "Alinhamento com o Algoritmo da Meta",
          timing_b2_d: "A Meta recompensa contas que respondem rápido durante os picos de visualização.",
          timing_card_t: "Pico de Engajamento Semanal Detectado",
          timing_card_s: "Métricas analisadas em tempo real",
          timing_bar_1: "Quarta-feira — 19:45 hrs",
          timing_bar_1_badge: "98% Atividade Máxima 🔥",
          timing_bar_2: "Sexta-feira — 21:00 hrs",
          timing_bar_2_badge: "84% Atividade",
          timing_bar_3: "Domingo — 20:15 hrs",
          timing_bar_3_badge: "76% Atividade",
          timing_recommendation: "💡 <strong>Recomendação XINDRO:</strong> Agende seu próximo post hoje às <strong>19:42 hrs</strong> para maximizar salvamentos e comentários.",
          api_badge: "Developer & Creator API",
          api_h2: "Ofereça o poder do XINDRO dentro das suas próprias ferramentas.",
          api_sub: "Endpoints RESTful ultrarrápidos, webhooks criptográficos verificados e SDKs prontos para integrar em seus bots, painéis ou SaaS em 5 linhas de código.",
          api_btn_copy: "📋 Copiar Código",
          api_f1_t: "⚡ Webhooks em Tempo Real",
          api_f1_d: "Receba e processe comentários do Instagram e Facebook em milissegundos com verificação HMAC-SHA256.",
          api_f2_t: "🛡️ Multi-Tenant & Isolamento",
          api_f2_d: "Cada criador e cliente possui seu próprio espaço isolado de dados e rate limiting anti-abusos.",
          api_f3_t: "🔌 Integração com Gemini & OpenAI",
          api_f3_d: "Conecte suas próprias chaves ou use nosso motor heurístico local sem custo de tokens.",
          price_badge: "Planos Transparentes",
          price_h2: "Comece grátis e escale com sua comunidade.",
          price_sub: "Sem contratos obrigatórios. Cancele quando quiser.",
          plan1_t: "Criador Starter",
          plan1_d: "Para criadores dando os primeiros passos.",
          plan1_p: "/ mês grátis",
          plan1_f1: "Até 1 conta do Instagram/Facebook",
          plan1_f2: "100 respostas automáticas / mês",
          plan1_f3: "Assistente Copilot IA",
          plan1_f4: "Acesso à API de desenvolvedores",
          plan1_btn: "Criar Conta Grátis",
          plan2_badge: "Mais Popular",
          plan2_t: "Criador Pro",
          plan2_d: "Para criadores e marcas em rápido crescimento.",
          plan2_p: "/ mês",
          plan2_f1: "Contas ilimitadas da Meta",
          plan2_f2: "Respostas ilimitadas em Autopilot",
          plan2_f3: "Calibrador de Tom de Marca personalizado",
          plan2_f4: "Smart Timing & Métricas de Engajamento",
          plan2_f5: "Suporte prioritário 24/7",
          plan2_btn: "Começar com Pro",
          plan3_t: "API & Agências",
          plan3_d: "Para desenvolvedores e agências de marketing.",
          plan3_p: "/ mês",
          plan3_f1: "Acesso total à REST API & Webhooks",
          plan3_f2: "Gerenciamento de até 25 clientes isolados",
          plan3_f3: "100.000 chamadas de API incluídas / mês",
          plan3_f4: "Marca branca & Webhook dedicado",
          plan3_btn: "Acesso para Agências",
          foot_c1_t: "Baixe o aplicativo",
          foot_c2_t: "Produto",
          foot_c2_1: "Preços",
          foot_c2_2: "Inspiração",
          foot_c2_3: "Educação",
          foot_c2_4: "Guia de prompts",
          foot_c2_5: "Modelos",
          foot_c2_6: "Explorar",
          foot_c2_7: "Integrações",
          foot_c3_t: "Empresa",
          foot_c3_1: "Sobre nós",
          foot_c3_2: "Carreiras",
          foot_c3_3: "Equipe",
          foot_c3_4: "Ajuda",
          foot_c3_5: "Comunidade",
          foot_c3_6: "Docs para Desenvolvedores",
          foot_c3_7: "Segurança",
          foot_c4_t: "Redes sociais",
          foot_c5_t: "Informações legais",
          foot_rights: "Todos os direitos reservados.",
          cookie_title: "Sobre os nossos cookies",
          cookie_desc_1: "Utilizamos cookies e tecnologias semelhantes conforme estabelecido no nosso",
          cookie_link: "Aviso de Cookies",
          cookie_desc_2: "Ao clicar em \"Aceitar Tudo\", você concorda com o uso de cookies para personalizar sua experiência, otimizar a IA e analisar o tráfego da API.",
          cookie_btn_settings: "Configurar Cookies",
          cookie_btn_reject: "Rejeitar Tudo",
          cookie_btn_accept: "Aceitar Tudo",
          cookie_btn_pref: "Preferências de cookies",
          modal_pref_title: "Central de Preferências de Cookies",
          modal_pref_desc: "Cumprimos rigorosamente as normas internacionais de proteção de dados (LGPD Brasil, RGPD da UE e CCPA). Selecione quais categorias de cookies deseja permitir:",
          cookie_cat1_t: "Cookies Técnicos e de Segurança (Essenciais)",
          cookie_cat1_status: "Sempre Ativos",
          cookie_cat1_d: "Necessários para autenticação de sessão, tokens de segurança CSRF e proteção da infraestrutura contra ataques.",
          cookie_cat2_t: "Cookies de Desempenho & Análise",
          cookie_cat2_d: "Permitem medir a velocidade de resposta da IA, uso de endpoints e otimizar a experiência dos criadores.",
          cookie_cat3_t: "Cookies de Personalização & Idioma",
          cookie_cat3_d: "Lembram suas preferências de idioma (Espanhol, Inglês, Português), tom padrão e configurações do simulador.",
          modal_pref_save: "Salvar Minhas Preferências"
        }
      },

      init() {
        // 1. Check saved language in localStorage
        const saved = localStorage.getItem('xindro_lang');
        if (saved && this.dict[saved]) {
          this.current = saved;
        } else {
          // 2. Auto-detect browser language
          const userLang = (navigator.language || navigator.userLanguage || 'es').toLowerCase();
          if (userLang.startsWith('pt')) {
            this.current = 'pt';
          } else if (userLang.startsWith('en')) {
            this.current = 'en';
          } else {
            this.current = 'es';
          }
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

        // Update active label and checks
        const labels = { es: 'Español', en: 'English', pt: 'Português' };
        document.getElementById('current-lang-label').textContent = labels[lang] || 'Español';

        ['es', 'en', 'pt'].forEach(l => {
          const chk = document.getElementById('check-' + l);
          if (chk) {
            if (l === lang) chk.classList.remove('hidden');
            else chk.classList.add('hidden');
          }
        });

        // Translate all data-i18n elements
        document.querySelectorAll('[data-i18n]').forEach(el => {
          const key = el.getAttribute('data-i18n');
          if (d[key] !== undefined) {
            el.innerHTML = d[key];
          }
        });

        // Translate Title & Meta
        if (d.page_title) document.title = d.page_title;
        const metaDesc = document.getElementById('meta-page-desc');
        if (metaDesc && d.page_desc) metaDesc.setAttribute('content', d.page_desc);

        // Update simulator samples if applicable
        const sampleComment = document.getElementById('hero-sample-comment');
        const sampleReply = document.getElementById('hero-sample-reply');
        if (sampleComment && d.hero_comment_sample) sampleComment.textContent = `"${d.hero_comment_sample}"`;
        if (sampleReply && d.hero_reply_sample) sampleReply.textContent = `"${d.hero_reply_sample}"`;
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

    // Close lang menu when clicking outside
    document.addEventListener('click', (e) => {
      const wrapper = document.getElementById('lang-dropdown-wrapper');
      if (wrapper && !wrapper.contains(e.target)) {
        I18n.hideLangMenu();
      }
    });

    // -------------------------------------------------------------
    // 2. COOKIE CONSENT MANAGER (RGPD / CCPA / LGPD Compliant)
    // -------------------------------------------------------------
    const CookieConsent = {
      init() {
        const consent = localStorage.getItem('xindro_cookie_consent');
        if (!consent) {
          setTimeout(() => {
            const modal = document.getElementById('cookie-consent-modal');
            if (modal) modal.classList.remove('hidden');
          }, 600);
        }
      },

      acceptAll() {
        localStorage.setItem('xindro_cookie_consent', JSON.stringify({ essential: true, analytics: true, personalization: true, timestamp: Date.now() }));
        this.hideModal();
      },

      rejectAll() {
        localStorage.setItem('xindro_cookie_consent', JSON.stringify({ essential: true, analytics: false, personalization: false, timestamp: Date.now() }));
        this.hideModal();
      },

      close() {
        localStorage.setItem('xindro_cookie_consent', 'dismissed');
        this.hideModal();
      },

      hideModal() {
        const modal = document.getElementById('cookie-consent-modal');
        if (modal) modal.classList.add('hidden');
      },

      openSettings() {
        const settingsModal = document.getElementById('cookie-settings-modal');
        if (settingsModal) settingsModal.classList.remove('hidden');
      },

      closeSettings() {
        const settingsModal = document.getElementById('cookie-settings-modal');
        if (settingsModal) settingsModal.classList.add('hidden');
      },

      saveCustom() {
        const analytics = document.getElementById('chk-analytics-cookies')?.checked;
        const personalization = document.getElementById('chk-personalization-cookies')?.checked;
        localStorage.setItem('xindro_cookie_consent', JSON.stringify({
          essential: true,
          analytics: !!analytics,
          personalization: !!personalization,
          timestamp: Date.now()
        }));
        this.closeSettings();
        this.hideModal();
      }
    };

    // -------------------------------------------------------------
    // 3. INTERACTIVE SIMULATOR ENGINE (Multilingual Ready)
    // -------------------------------------------------------------
    const Simulator = {
      presets: {
        es: {
          1: { text: "¿Cuánto cuesta la mentoría o curso? ¿Tienen cupos disponibles para este mes?", tone: "growth", closing: "always" },
          2: { text: "¡Qué gran reflexión! Marco Aurelio y Séneca cambiaron mi forma de ver los problemas cotidianos. Gracias por compartir.", tone: "mentor", closing: "relevant" }
        },
        en: {
          1: { text: "How much is the mentorship or course? Do you have spots available for this month?", tone: "growth", closing: "always" },
          2: { text: "What an inspiring reflection! Marcus Aurelius changed how I view daily obstacles. Thank you for sharing.", tone: "mentor", closing: "relevant" }
        },
        pt: {
          1: { text: "Quanto custa a mentoria ou curso? Vocês têm vagas disponíveis para este mês?", tone: "growth", closing: "always" },
          2: { text: "Que reflexão incrível! Marco Aurélio mudou minha forma de encarar os desafios do dia a dia. Obrigado por compartilhar.", tone: "mentor", closing: "relevant" }
        }
      },

      loadPreset(num) {
        const lang = I18n.current || 'es';
        const langPresets = this.presets[lang] || this.presets['es'];
        const p = langPresets[num];
        if (!p) return;
        document.getElementById('sim-input-text').value = p.text;
        document.getElementById('sim-tone').value = p.tone;
        document.getElementById('sim-closing').value = p.closing;
        this.generate();
      },

      generate() {
        const text = document.getElementById('sim-input-text').value.trim();
        const tone = document.getElementById('sim-tone').value;
        const closing = document.getElementById('sim-closing').value;
        const btn = document.getElementById('sim-btn-generate');
        const outputText = document.getElementById('sim-output-text');
        const badgeIntent = document.getElementById('sim-badge-intent');
        const badgeScore = document.getElementById('sim-badge-score');
        const lang = I18n.current || 'es';

        if (!text) return;

        btn.disabled = true;
        btn.innerHTML = '<span>Generando respuesta con IA...</span>';

        setTimeout(() => {
          let reply = '';
          let intent = 'Interés / Comunidad';
          let score = 92;

          const textLower = text.toLowerCase();

          if (lang === 'en') {
            if (textLower.includes('price') || textLower.includes('cost') || textLower.includes('course') || textLower.includes('spot') || textLower.includes('how much')) {
              intent = 'High-Converting Lead 💎';
              score = 98;
              reply = tone === 'mentor'
                ? "True value lies in transformation and daily execution. I just sent you the full curriculum and details via DM so you can see if it fits your goals. Ready to take the leap? 🚀"
                : "Hey! We opened only 10 spots this month for 1-on-1 focus. I just sent you a DM with the private link and a special perk. Were you able to check it? 📩";
            } else if (textLower.includes('procrastinat') || textLower.includes('fear') || textLower.includes('advice') || textLower.includes('start')) {
              intent = 'High-Value Mentorship Question 🧠';
              score = 95;
              reply = "Fear of failure only fades when you take action before your mind starts doubting. Break your goal into one 5-minute task today. Perfection doesn't exist; daily progress does. What small step will you take in the next 10 minutes? 👇";
            } else {
              intent = 'Connection & Retention ⚡';
              score = 90;
              reply = "Exactly. When you master your mind and apply wisdom in your daily routine, external obstacles lose their power. Thank you for being part of this community. Which habit helped you most this week? 🏛️";
            }
          } else if (lang === 'pt') {
            if (textLower.includes('preço') || textLower.includes('custo') || textLower.includes('curso') || textLower.includes('vaga') || textLower.includes('quanto')) {
              intent = 'Lead de Alta Conversão 💎';
              score = 98;
              reply = tone === 'mentor'
                ? "O valor real está na transformação e na disciplina diária. Acabei de te enviar o cronograma e os detalhes por mensagem privada para avaliar seus objetivos. Pronto para dar esse passo? 🚀"
                : "Olá! Abrimos apenas 10 vagas este mês para acompanhamento exclusivo. Já te enviei um DM com o link e um benefício especial. Conseguiu ver? 📩";
            } else if (textLower.includes('procrastin') || textLower.includes('medo') || textLower.includes('conselho') || textLower.includes('começar')) {
              intent = 'Pergunta de Mentoria & Valor 🧠';
              score = 95;
              reply = "O medo do fracasso só desaparece quando você age antes que a mente comece a duvidar. Divida sua meta em uma ação de 5 minutos para hoje. Perfeição não existe; progresso diário sim. Qual pequena tarefa você fará nos próximos 10 minutos? 👇";
            } else {
              intent = 'Conexão & Retenção ⚡';
              score = 90;
              reply = "Exatamente. Quando você domina sua mente e aplica sabedoria na rotina, os obstáculos externos perdem a força. Obrigado por fazer parte da comunidade. Qual princípio mais te ajudou esta semana? 🏛️";
            }
          } else {
            // Spanish default
            if (textLower.includes('precio') || textLower.includes('curso') || textLower.includes('cuanto') || textLower.includes('costo') || textLower.includes('cupo')) {
              intent = 'Lead de Alta Conversión 💎';
              score = 98;
              reply = tone === 'mentor'
                ? "El valor real está en la transformación y la disciplina diaria. Te envié todos los detalles y el temario completo por mensaje privado para que veas si encaja con tus objetivos. ¿Listo para dar el salto? 🚀"
                : "¡Hola! Sí, abrimos solo 10 cupos para este mes para trabajar de forma personalizada. Ya te escribí por DM con el enlace y una sorpresa especial. ¿Pudiste revisarlo? 📩";
            } else if (textLower.includes('procrastino') || textLower.includes('miedo') || textLower.includes('consejo') || textLower.includes('empezar')) {
              intent = 'Pregunta de Mentoría / Alto Valor 🧠';
              score = 95;
              reply = "El miedo al fracaso solo desaparece cuando actúas antes de que la mente empiece a dudar. Divide tu meta en una sola acción de 5 minutos para hoy. La perfección no existe, el progreso diario sí. ¿Qué pequeña tarea harás en los próximos 10 minutos? 👇";
            } else {
              intent = 'Conexión & Retención ⚡';
              score = 90;
              reply = "Exactamente. Cuando dominas tu mente y aplicas la sabiduría en tu rutina, los problemas externos pierden todo su poder. Gracias por ser parte de esta comunidad. ¿Qué principio estoico te ha servido más esta semana? 🏛️";
            }
          }

          if (closing === 'never') {
            reply = reply.replace(/\¿[^\?]+\?\s*(👇|✨|🔥|🚀|📩|🤝)?$/i, '').replace(/\?[^\?]+\?\s*(👇|✨|🔥|🚀|📩|🤝)?$/i, '');
          }

          badgeIntent.textContent = '🎯 ' + intent;
          badgeScore.textContent = score + '/100';
          outputText.textContent = `"${reply}"`;

          btn.disabled = false;
          btn.innerHTML = `<span>${I18n.dict[lang]?.sim_btn_gen || 'Generar Respuesta con IA'}</span><span>⚡</span>`;
        }, 300);
      },

      copyResponse() {
        const text = document.getElementById('sim-output-text').textContent.replace(/^"|"$/g, '');
        navigator.clipboard.writeText(text).then(() => {
          const btn = document.getElementById('sim-btn-copy');
          btn.innerHTML = '<span class="text-emerald-600 font-bold">✔ Copied!</span>';
          setTimeout(() => {
            const lang = I18n.current || 'es';
            btn.innerHTML = `<span>${I18n.dict[lang]?.sim_btn_copy || '📋 Copiar'}</span>`;
          }, 2000);
        });
      }
    };

    // -------------------------------------------------------------
    // 4. API TABS SWITCHER
    // -------------------------------------------------------------
    const ApiTabs = {
      languages: ['curl', 'js', 'php', 'python'],

      switch(lang) {
        this.languages.forEach(l => {
          const tabBtn = document.getElementById('tab-' + l);
          const codeBlock = document.getElementById('code-' + l);
          if (l === lang) {
            tabBtn.className = 'px-3 py-1 rounded bg-brand-600 text-white font-bold';
            codeBlock.classList.remove('hidden');
          } else {
            tabBtn.className = 'px-3 py-1 rounded text-slate-400 hover:text-white';
            codeBlock.classList.add('hidden');
          }
        });
      },

      copyCode() {
        const visiblePre = document.querySelector('pre:not(.hidden) code');
        if (!visiblePre) return;
        navigator.clipboard.writeText(visiblePre.textContent).then(() => {
          const btn = document.getElementById('btn-copy-code');
          btn.innerHTML = '<span class="text-emerald-400">✔ ¡Copiado!</span>';
          setTimeout(() => {
            const lang = I18n.current || 'es';
            btn.innerHTML = `<span>${I18n.dict[lang]?.api_btn_copy || '📋 Copiar Código'}</span>`;
          }, 2000);
        });
      }
    };

    // Initialize Everything on Load
    document.addEventListener('DOMContentLoaded', () => {
      I18n.init();
      CookieConsent.init();
    });
  </script>

</body>
</html>
