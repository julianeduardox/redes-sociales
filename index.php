<?php
/**
 * XINDRO — El Sistema Operativo de IA para Creadores de Contenido y Redes Sociales
 * Landing Page de Alto Impacto Visual (Gamma + Indrox Architecture + i18n ES/EN/PT + Cookie Manager + Responsive Mobile Menu)
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <title id="meta-page-title">XINDRO — El Sistema Operativo de IA para Creadores de Contenido</title>
  <meta name="description" id="meta-page-desc" content="Automatiza tus redes sociales. Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y escala tu presencia en Instagram y Facebook.">
  
  <!-- Open Graph / Meta -->
  <meta property="og:type" content="website">
  <meta property="og:title" content="XINDRO — Automatización Inteligente de Redes Sociales">
  <meta property="og:description" content="Escala tu comunidad sin perder el toque humano con Auto-Engagement, Smart Timing y Respuestas con IA.">
  <meta property="og:url" content="https://socialapi.turbogram.site/">
  
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">

  <!-- Google Fonts: Plus Jakarta Sans, Syne & JetBrains Mono -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">

  <!-- Performance Preconnect for External Assets (Unsplash & UI-Avatars) -->
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="dns-prefetch" href="https://images.unsplash.com">
  <link rel="preconnect" href="https://ui-avatars.com" crossorigin>

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

    /* Vibrant Cosmic Starry Night Footer Background */
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

    .gradient-text {
      background: linear-gradient(135deg, #7C3AED 0%, #4F46E5 50%, #06B6D4 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .gradient-button {
      background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
      transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .gradient-button:hover {
      background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%);
      box-shadow: 0 10px 28px -4px rgba(124, 58, 237, 0.5), 0 0 20px rgba(139, 92, 246, 0.35);
      transform: translateY(-2px) scale(1.02);
    }
    .gradient-button:active {
      transform: translateY(1px) scale(0.97);
      box-shadow: 0 2px 10px rgba(124, 58, 237, 0.3);
    }

    /* Secondary interactive button with subtle micro-interactions */
    .secondary-btn {
      transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .secondary-btn:hover {
      transform: translateY(-1.5px) scale(1.02);
      box-shadow: 0 6px 20px -4px rgba(15, 23, 42, 0.1);
    }
    .secondary-btn:active {
      transform: translateY(1px) scale(0.97);
    }

    /* Simulator native select styling for touch & no-text-overlap */
    .sim-select-custom {
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237C3AED' stroke-width='2.5'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 16px 16px;
      padding-right: 2.75rem !important;
      min-height: 44px;
      font-size: 14px;
      cursor: pointer;
    }
    @media (max-width: 640px) {
      .sim-select-custom {
        font-size: 15px;
        min-height: 46px;
      }
    }

    /* Preset & action pill micro-interactions */
    .preset-pill-btn {
      transition: all 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .preset-pill-btn:hover {
      transform: translateY(-1px) scale(1.04);
      box-shadow: 0 2px 8px rgba(124, 58, 237, 0.15);
    }
    .preset-pill-btn:active {
      transform: translateY(1px) scale(0.95);
    }

    .copy-btn-action {
      transition: all 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }
    .copy-btn-action:hover {
      transform: scale(1.06);
    }
    .copy-btn-action:active {
      transform: scale(0.93);
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

    .glass-nav {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.85);
    }

    .gamma-wordmark {
      font-family: 'Syne', 'Plus Jakarta Sans', sans-serif;
      letter-spacing: -0.03em;
      font-weight: 900;
      text-transform: uppercase;
    }

    /* Infinite Marquee Animation for Tech Stack */
    @keyframes marquee {
      0% { transform: translateX(0%); }
      100% { transform: translateX(-50%); }
    }
    .animate-marquee {
      display: flex;
      width: 200%;
      animation: marquee 25s linear infinite;
    }
    .animate-marquee:hover {
      animation-play-state: paused;
    }

    @keyframes slideUpCookie {
      from { opacity: 0; transform: translateY(30px) scale(0.96); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .cookie-animate {
      animation: slideUpCookie 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    pre code {
      font-family: 'JetBrains Mono', monospace;
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
  <!-- 1. NAVBAR FIJA RESPONSIVA (DESKTOP + MOBILE DRAWER) -->
  <!-- ========================================================================= -->
  <header class="fixed top-0 left-0 right-0 z-50 glass-nav transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between gap-3 sm:gap-6">
      
      <!-- Logo: XINDRO -->
      <a href="index.php" class="flex items-center gap-2.5 sm:gap-3 group shrink-0 mr-2 xl:mr-6">
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-gradient-to-br from-brand-500 via-indigo-600 to-brand-700 flex items-center justify-center text-white font-black text-lg shadow-glow-sm group-hover:scale-105 transition-transform">
          <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
          </svg>
        </div>
        <span class="text-xl sm:text-2xl gamma-wordmark tracking-tight text-midnight">
          XINDRO
        </span>
      </a>

      <!-- Desktop Navigation Links with generous spacing -->
      <nav class="hidden lg:flex items-center gap-1 xl:gap-2.5 text-[13px] xl:text-sm font-semibold text-slate-600">
        <a href="#funciones" data-i18n="nav_products" class="px-3 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Productos</a>
        <a href="#por-que-xindro" data-i18n="nav_why" class="px-3 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">¿Por qué Xindro?</a>
        <a href="#simulador" data-i18n="nav_simulator" class="px-3 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors flex items-center gap-1.5">
          <span>Simulador</span>
          <span class="inline-block w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
        </a>
        <a href="#calculadora-roi" data-i18n="nav_roi" class="px-3 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Calculadora</a>
        <a href="#precios" data-i18n="nav_pricing" class="px-3 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Precios</a>
        <a href="#faq" data-i18n="nav_faq" class="px-3 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">FAQ</a>
      </nav>

      <!-- Right Controls: Language Selector & Auth CTAs & Mobile Toggle -->
      <div class="flex items-center gap-2 sm:gap-3 shrink-0 ml-auto lg:ml-0">
        
        <!-- Language Switcher Dropdown (ES / EN / PT) -->
        <div class="relative inline-block text-left" id="lang-dropdown-wrapper">
          <button type="button" id="lang-dropdown-btn" onclick="I18n.toggleLangMenu()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full bg-slate-100/90 hover:bg-slate-200/80 border border-slate-200/80 text-xs font-bold text-slate-700 transition-colors whitespace-nowrap">
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
          <a href="dashboard.php" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all whitespace-nowrap shimmer-btn">
            <span data-i18n="nav_dashboard">Ir a mi Panel</span>
            <span>→</span>
          </a>
        <?php else: ?>
          <a href="login.php" data-i18n="nav_login" class="text-xs sm:text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 rounded-xl hover:bg-slate-100/70 transition-colors whitespace-nowrap hidden md:inline-block">
            Iniciar sesión
          </a>
          <a href="login.php" data-i18n="nav_cta" class="hidden sm:inline-flex items-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 hover:shadow-md transition-all whitespace-nowrap shimmer-btn">
            <span>Comienza gratis</span>
          </a>
        <?php endif; ?>

        <!-- Mobile Menu Toggle Button (Hamburger) -->
        <button type="button" id="mobile-menu-btn" onclick="MobileNav.toggle()" class="lg:hidden p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-700 focus:outline-none transition-colors" aria-label="Abrir menú">
          <svg id="hamburger-icon-open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          <svg id="hamburger-icon-close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>

      </div>

    </div>

    <!-- Mobile Slide-Down Navigation Menu -->
    <div id="mobile-nav-drawer" class="lg:hidden hidden bg-white/95 backdrop-blur-2xl border-b border-slate-200 shadow-2xl px-6 py-6 transition-all">
      <div class="flex flex-col space-y-3 font-semibold text-slate-700 text-sm">
        <a href="#funciones" onclick="MobileNav.close()" data-i18n="nav_products" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>📦 Productos</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#por-que-xindro" onclick="MobileNav.close()" data-i18n="nav_why" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>🛡️ ¿Por qué Xindro?</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#simulador" onclick="MobileNav.close()" data-i18n="nav_simulator" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span class="flex items-center gap-2">
            <span>⚡ Simulador en Vivo</span>
            <span class="w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
          </span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#calculadora-roi" onclick="MobileNav.close()" data-i18n="nav_roi" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>🧮 Calculadora de Impacto</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#precios" onclick="MobileNav.close()" data-i18n="nav_pricing" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>💎 Precios y Planes</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#faq" onclick="MobileNav.close()" data-i18n="nav_faq" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>❓ Preguntas Frecuentes</span>
          <span class="text-slate-400">→</span>
        </a>
      </div>

      <div class="mt-6 pt-5 border-t border-slate-200 flex flex-col gap-3">
        <?php if ($isLoggedIn): ?>
          <a href="dashboard.php" class="w-full py-3 rounded-xl text-center font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-colors">
            <span data-i18n="nav_dashboard">Ir a mi Panel</span> →
          </a>
        <?php else: ?>
          <a href="login.php" data-i18n="nav_cta" class="w-full py-3 rounded-xl text-center font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-colors">
            Comienza gratis
          </a>
          <a href="login.php" data-i18n="nav_login" class="w-full py-2.5 rounded-xl text-center font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors">
            Iniciar sesión
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- ========================================================================= -->
  <!-- 2. HERO SECTION MULTILINGÜE -->
  <!-- ========================================================================= -->
  <section class="relative pt-32 pb-14 md:pt-44 md:pb-24 hero-mesh-bg overflow-hidden border-b border-slate-100">
    
    <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[650px] h-[350px] bg-brand-400/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      
      <!-- Top Pill Badge -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-50 border border-brand-200/80 text-brand-700 text-xs sm:text-sm font-bold mb-6 sm:mb-8 shadow-sm hover:border-brand-300 transition-colors">
        <span class="flex h-2 w-2 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
        </span>
        <span data-i18n="hero_badge">El sistema operativo de IA para creadores de contenido</span>
      </div>

      <!-- Main Headline (H1) -->
      <h1 class="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-midnight leading-[1.15] mb-5 sm:mb-6 max-w-4xl mx-auto">
        <span data-i18n="hero_h1_p1">Automatiza tus redes sociales.</span> <br class="hidden sm:inline" />
        <span class="gradient-text" data-i18n="hero_h1_p2">Escala tu comunidad sin perder el toque humano.</span>
      </h1>

      <!-- Subtitle (P) -->
      <p data-i18n="hero_sub" class="text-sm sm:text-lg md:text-xl text-slate-600 max-w-2xl mx-auto mb-8 sm:mb-10 leading-relaxed font-normal">
        Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y escala tu presencia en Instagram y Facebook sin esfuerzo manual.
      </p>

      <!-- CTAs Button Group with Shimmer Glow -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 max-w-md mx-auto mb-12 sm:mb-16">
        <a href="#simulador" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 sm:px-8 sm:py-4 rounded-xl text-sm sm:text-base font-bold text-white gradient-button shadow-glow-md shimmer-btn">
          <span data-i18n="hero_cta_sim">Prueba el Simulador</span>
          <span class="text-lg">✨</span>
        </a>
        <a href="#calculadora-roi" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 sm:px-8 sm:py-4 rounded-xl text-sm sm:text-base font-bold text-slate-700 bg-white border border-slate-200 hover:border-brand-300 hover:bg-slate-50 hover:text-brand-700 shadow-sm secondary-btn">
          <span data-i18n="hero_cta_calc">Calcular Impacto ROI</span>
          <span>📊</span>
        </a>
      </div>

      <!-- Live Interactive Visual Hero Card -->
      <div class="relative max-w-4xl mx-auto rounded-2xl sm:rounded-3xl bg-white border border-slate-200/90 shadow-elevated-card p-4 sm:p-6 md:p-8 text-left">
        
        <div class="flex flex-wrap items-center justify-between border-b border-slate-100 pb-4 mb-5 sm:mb-6 gap-2">
          <div class="flex items-center gap-2 sm:gap-3">
            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-red-400"></div>
            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-400"></div>
            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-emerald-400"></div>
            <span class="text-[11px] sm:text-xs font-semibold text-slate-400 ml-1 sm:ml-2" data-i18n="hero_card_title">XINDRO Live Copilot — Flujo en Tiempo Real</span>
          </div>
          <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] sm:text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 live-dot"></span>
            <span data-i18n="hero_card_status">Meta Webhook Activo</span>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 sm:gap-6 items-center">
          
          <!-- Incoming Comment -->
          <div class="md:col-span-5 bg-slatecard rounded-xl sm:rounded-2xl p-4 border border-slate-200/80">
            <div class="flex items-center gap-2.5 mb-2.5">
              <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=96&h=96&fit=crop&crop=faces&auto=format&q=75" width="32" height="32" loading="lazy" decoding="async" class="w-7 h-7 sm:w-8 sm:h-8 rounded-full border border-slate-200 object-cover" alt="Avatar" />
              <div>
                <div class="text-xs font-bold text-slate-800">@alejandro.creator</div>
                <div class="text-[10px] text-slate-500" data-i18n="hero_card_time">Instagram • Hace 2 seg</div>
              </div>
              <span class="ml-auto text-[10px] sm:text-[11px] font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200/60">
                Score: 96/100
              </span>
            </div>
            <p id="hero-sample-comment" class="text-xs sm:text-[13px] text-slate-700 leading-relaxed font-medium">
              "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?"
            </p>
            <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-[10px] sm:text-[11px] text-slate-500 font-semibold">
              <span data-i18n="hero_card_intent">🎯 Intención: <strong class="text-brand-600">Pregunta Clave</strong></span>
              <span class="text-emerald-600 font-bold">⚡ 142ms</span>
            </div>
          </div>

          <!-- Flow Arrow -->
          <div class="md:col-span-2 flex flex-col items-center justify-center text-brand-600 py-1 md:py-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-brand-50 border border-brand-200 flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm">
              ⚡
            </div>
            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1" data-i18n="hero_card_calibrated">IA Calibrada</span>
          </div>

          <!-- Generated AI Reply -->
          <div class="md:col-span-5 bg-gradient-to-br from-brand-50/70 to-indigo-50/50 rounded-xl sm:rounded-2xl p-4 border border-brand-200/80">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xs font-bold text-brand-800 flex items-center gap-1.5">
                <span data-i18n="hero_card_bot_reply">🤖 Respuesta con Voz de Marca</span>
              </span>
              <span class="ml-auto text-[9px] sm:text-[10px] font-bold text-brand-700 bg-brand-100/70 px-2 py-0.5 rounded-full" data-i18n="hero_card_tone">
                Mentor Empático
              </span>
            </div>
            <p id="hero-sample-reply" class="text-xs sm:text-[13px] text-brand-950 leading-relaxed font-medium">
              "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇"
            </p>
            <div class="mt-3 pt-2 border-t border-brand-200/50 flex items-center justify-between text-[10px] sm:text-[11px]">
              <span class="text-brand-700 font-semibold" data-i18n="hero_card_retention">🚀 Retención: <strong class="text-brand-900">+380%</strong></span>
              <span class="text-emerald-600 font-bold" data-i18n="hero_card_ready">✔ Listo para postear</span>
            </div>
          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 3. MARQUEE INFINITO DE TECNOLOGÍAS & ECOSISTEMA -->
  <!-- ========================================================================= -->
  <section class="py-6 sm:py-7 bg-slate-900 border-b border-slate-800 overflow-hidden text-white">
    <div class="max-w-7xl mx-auto px-4 mb-3 text-center">
      <p data-i18n="marquee_title" class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-[0.2em] sm:tracking-[0.25em] text-slate-400">
        Integrado con la Infraestructura Oficial de Redes Sociales e Inteligencia Artificial
      </p>
    </div>

    <div class="relative overflow-hidden w-full select-none">
      <div class="animate-marquee items-center gap-8 sm:gap-12 font-mono text-[11px] sm:text-xs font-bold text-slate-300">
        
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-purple-400">📸</span> Meta Graph API (Instagram & Facebook)
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-blue-400">⚡</span> Google Gemini 2.0 Engine
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-emerald-400">🧠</span> OpenAI GPT-4o Integration
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-amber-400">🔒</span> HMAC-SHA256 Cryptographic Webhooks
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-indigo-400">🏢</span> Multi-Tenant SQLite Database Isolation
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-cyan-400">🛡️</span> GDPR / CCPA / LGPD Compliant
        </span>
        
        <!-- Duplicate for loop -->
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-purple-400">📸</span> Meta Graph API (Instagram & Facebook)
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-blue-400">⚡</span> Google Gemini 2.0 Engine
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-emerald-400">🧠</span> OpenAI GPT-4o Integration
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-amber-400">🔒</span> HMAC-SHA256 Cryptographic Webhooks
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-indigo-400">🏢</span> Multi-Tenant SQLite Database Isolation
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-cyan-400">🛡️</span> GDPR / CCPA / LGPD Compliant
        </span>

      </div>
    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 4. SOCIAL PROOF & MÉTRICAS DE IMPACTO -->
  <!-- ========================================================================= -->
  <section class="py-10 sm:py-12 bg-slatecard border-b border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 md:gap-8 text-center divide-x-0 md:divide-x divide-slate-200/80">
        
        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-midnight tracking-tight mb-1">
            +500K<span class="text-brand-600">+</span>
          </div>
          <p data-i18n="stat_1" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Comentarios Respondidos
          </p>
        </div>

        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-brand-600 tracking-tight mb-1">
            3.4x
          </div>
          <p data-i18n="stat_2" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Aumento en Engagement
          </p>
        </div>

        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-midnight tracking-tight mb-1">
            99.8%
          </div>
          <p data-i18n="stat_3" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Precisión de Voz Humana
          </p>
        </div>

        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-emerald-600 tracking-tight mb-1">
            &lt; 180ms
          </div>
          <p data-i18n="stat_4" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Latencia de API en Vivo
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 5. ¿POR QUÉ ELEGIR XINDRO? (6 Razones de Valor Real) -->
  <!-- ========================================================================= -->
  <section id="por-que-xindro" class="py-16 sm:py-24 bg-white border-b border-slate-100 relative">
    <div id="funciones" class="absolute -top-20"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
        <span data-i18n="why_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Diferenciales Reales
        </span>
        <h2 data-i18n="why_h2" class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-midnight tracking-tight mt-3 sm:mt-4 mb-3 sm:mb-4">
          ¿Por qué los creadores y agencias eligen Xindro?
        </h2>
        <p data-i18n="why_sub" class="text-sm sm:text-lg text-slate-600 font-normal">
          Diseñado desde el código para responder en segundos, proteger tu reputación y maximizar el algoritmo sin sonar como un robot.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-8">
        
        <!-- Pillar 1 -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 hover:border-brand-400 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl font-bold mb-4 sm:mb-5 group-hover:scale-110 transition-transform">
              ⚡
            </div>
            <h3 data-i18n="why_p1_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Respuestas en Tiempo Real (&lt;180ms)
            </h3>
            <p data-i18n="why_p1_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              El algoritmo de Meta premia a las cuentas que interactúan en los primeros 15 minutos. Nuestro motor heurístico responde casi al instante.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-200 text-xs font-bold text-brand-700" data-i18n="why_p1_tag">
            ✔ Cero demora de engagement
          </div>
        </div>

        <!-- Pillar 2 -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 hover:border-brand-400 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold mb-4 sm:mb-5 group-hover:scale-110 transition-transform">
              🧠
            </div>
            <h3 data-i18n="why_p2_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Voz de Marca Auténtica & Calibrada
            </h3>
            <p data-i18n="why_p2_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              Configura el nivel de calidez, profundidad y energía. Tus seguidores recibirán respuestas empáticas y humanas, nunca genéricas.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-200 text-xs font-bold text-blue-700" data-i18n="why_p2_tag">
            ✔ Personalización total de tono
          </div>
        </div>

        <!-- Pillar 3 -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 hover:border-brand-400 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold mb-4 sm:mb-5 group-hover:scale-110 transition-transform">
              🛡️
            </div>
            <h3 data-i18n="why_p3_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              100% Oficial con Meta Graph API
            </h3>
            <p data-i18n="why_p3_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              Cero riesgo de penalización o bloqueos de cuenta. Conexión autorizada por Meta con cifrado de tokens AES-256-GCM.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-200 text-xs font-bold text-emerald-700" data-i18n="why_p3_tag">
            ✔ Seguridad y cumplimiento oficial
          </div>
        </div>

        <!-- Pillar 4 -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 hover:border-brand-400 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold mb-4 sm:mb-5 group-hover:scale-110 transition-transform">
              ⏰
            </div>
            <h3 data-i18n="why_p4_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Smart Timing Basado en Datos
            </h3>
            <p data-i18n="why_p4_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              Analiza los patrones de actividad real de tu comunidad para decirte la hora exacta en la que obtendrás mayor alcance y guardados.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-200 text-xs font-bold text-amber-700" data-i18n="why_p4_tag">
            ✔ +142% de alcance orgánico
          </div>
        </div>

        <!-- Pillar 5 -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 hover:border-brand-400 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl font-bold mb-4 sm:mb-5 group-hover:scale-110 transition-transform">
              🎯
            </div>
            <h3 data-i18n="why_p5_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Detección de Leads & Filtro Anti-Spam
            </h3>
            <p data-i18n="why_p5_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              Identifica automáticamente intenciones de compra, dudas frecuentes de clientes y filtra comentarios repetitivos o spam para proteger tu reputación.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-200 text-xs font-bold text-indigo-700" data-i18n="why_p5_tag">
            ✔ Comunidad protegida y fidelizada
          </div>
        </div>

        <!-- Pillar 6 -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 hover:border-brand-400 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl font-bold mb-4 sm:mb-5 group-hover:scale-110 transition-transform">
              💰
            </div>
            <h3 data-i18n="why_p6_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Ahorra +35 Horas de Trabajo al Mes
            </h3>
            <p data-i18n="why_p6_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
              Elimina el trabajo repetitivo de responder dudas frecuentes y aprovecha ese tiempo para crear contenido que mueva tu negocio.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-200 text-xs font-bold text-rose-700" data-i18n="why_p6_tag">
            ✔ Enfoque 100% en crear
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 6. CALCULADORA INTERACTIVA DE AHORRO & ROI -->
  <!-- ========================================================================= -->
  <section id="calculadora-roi" class="py-16 sm:py-24 bg-gradient-to-b from-slatecard to-white border-b border-slate-200/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
        <span data-i18n="calc_badge" class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3.5 py-1 rounded-full border border-emerald-200">
          Calculadora de Impacto
        </span>
        <h2 data-i18n="calc_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          ¿Cuánto tiempo y alcance ganas con Xindro?
        </h2>
        <p data-i18n="calc_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Ajusta el volumen de comentarios mensuales y descubre el impacto real en tu comunidad.
        </p>
      </div>

      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-elevated-card p-5 sm:p-8 md:p-10">
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-center">
          
          <!-- Sliders -->
          <div class="lg:col-span-6 space-y-5 sm:space-y-6">
            
            <div>
              <div class="flex justify-between items-center mb-2 gap-2">
                <label data-i18n="calc_lbl_comments" class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                  Comentarios recibidos al mes:
                </label>
                <span id="calc-comments-val" class="text-xs sm:text-sm font-black text-brand-600 bg-brand-50 px-2.5 py-0.5 rounded-lg border border-brand-200 whitespace-nowrap">
                  5,000 comentarios
                </span>
              </div>
              <input type="range" id="calc-comments-range" min="500" max="50000" step="500" value="5000" oninput="Calculator.update()" class="w-full accent-brand-600 cursor-pointer h-2 bg-slate-200 rounded-lg" />
              <div class="flex justify-between text-[10px] text-slate-400 font-bold mt-1">
                <span>500</span>
                <span>25,000</span>
                <span>50,000+</span>
              </div>
            </div>

            <div>
              <div class="flex justify-between items-center mb-2 gap-2">
                <label data-i18n="calc_lbl_accounts" class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                  Cuentas de Instagram / Facebook:
                </label>
                <span id="calc-accounts-val" class="text-xs sm:text-sm font-black text-brand-600 bg-brand-50 px-2.5 py-0.5 rounded-lg border border-brand-200 whitespace-nowrap">
                  2 cuentas
                </span>
              </div>
              <input type="range" id="calc-accounts-range" min="1" max="10" step="1" value="2" oninput="Calculator.update()" class="w-full accent-brand-600 cursor-pointer h-2 bg-slate-200 rounded-lg" />
              <div class="flex justify-between text-[10px] text-slate-400 font-bold mt-1">
                <span>1 cuenta</span>
                <span>5 cuentas</span>
                <span>10 cuentas</span>
              </div>
            </div>

          </div>

          <!-- Results -->
          <div class="lg:col-span-6 bg-gradient-to-br from-slate-900 to-slate-950 text-white rounded-2xl p-5 sm:p-7 shadow-xl border border-slate-800">
            <div class="text-xs font-bold uppercase tracking-wider text-brand-400 mb-4" data-i18n="calc_res_title">
              Impacto Estimado Mensual
            </div>

            <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-5 sm:mb-6">
              
              <div class="bg-slate-900/90 p-3 sm:p-4 rounded-xl border border-slate-800 min-w-0">
                <div class="text-lg sm:text-2xl md:text-3xl font-black text-emerald-400 mb-0.5 tracking-tight" id="calc-res-hours">
                  +38 hrs
                </div>
                <div class="text-[9.5px] sm:text-[11px] font-semibold text-slate-400 uppercase leading-tight break-words tracking-normal sm:tracking-wider" data-i18n="calc_res_h_label">
                  Tiempo Manual Ahorrado
                </div>
              </div>

              <div class="bg-slate-900/90 p-3 sm:p-4 rounded-xl border border-slate-800 min-w-0">
                <div class="text-lg sm:text-2xl md:text-3xl font-black text-brand-400 mb-0.5 tracking-tight" id="calc-res-leads">
                  +120
                </div>
                <div class="text-[9.5px] sm:text-[11px] font-semibold text-slate-400 uppercase leading-tight break-words tracking-normal sm:tracking-wider" data-i18n="calc_res_l_label">
                  Leads / Preguntas Clave
                </div>
              </div>

            </div>

            <div class="text-[11px] sm:text-xs text-slate-300 leading-relaxed border-t border-slate-800 pt-3">
              <span class="text-emerald-400 font-bold">✔ 99.4%</span> <span data-i18n="calc_res_footer">de respuestas entregadas en la ventana de oro del algoritmo sin agotamiento humano.</span>
            </div>
          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 7. EL SIMULADOR INTERACTIVO -->
  <!-- ========================================================================= -->
  <section id="simulador" class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-12">
        <span data-i18n="sim_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Playground en Vivo
        </span>
        <h2 data-i18n="sim_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          Prueba el Motor de XINDRO en Tiempo Real
        </h2>
        <p data-i18n="sim_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Selecciona un tono, escribe cualquier comentario de tu comunidad y observa cómo la IA genera respuestas hipercontextualizadas.
        </p>
      </div>

      <div class="bg-white rounded-2xl sm:rounded-3xl border border-slate-200 shadow-elevated-card overflow-hidden">
        
        <div class="bg-slate-900 text-white px-5 sm:px-6 py-3.5 sm:py-4 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2 sm:gap-2.5">
            <span class="text-brand-400 font-bold text-base sm:text-lg">⚡</span>
            <span class="text-xs sm:text-sm font-bold tracking-tight">XINDRO Interactive Simulator v2.0</span>
          </div>
          <div class="flex items-center gap-2 text-[10px] sm:text-xs font-mono text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            <span>Zero-Latency Heuristic + Gemini LLM Engine</span>
          </div>
        </div>

        <div class="p-5 sm:p-8">
          
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">
            
            <div>
              <label data-i18n="sim_lbl_tone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 sm:mb-2">
                1. Tono de Marca:
              </label>
              <select id="sim-tone" class="sim-select-custom w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer shadow-sm">
                <option value="mentor" data-i18n="sim_opt_mentor">🏛️ Estoico / Mentor Sabio</option>
                <option value="empathy" selected data-i18n="sim_opt_empathy">🤝 Cercano & Empático</option>
                <option value="growth" data-i18n="sim_opt_growth">🔥 Dinámico & Venta de Alto Valor</option>
              </select>
            </div>

            <div>
              <label data-i18n="sim_lbl_plat" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 sm:mb-2">
                2. Plataforma:
              </label>
              <select id="sim-platform" class="sim-select-custom w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer shadow-sm">
                <option value="instagram">📸 Instagram</option>
                <option value="facebook">📘 Facebook</option>
                <option value="tiktok">🎵 TikTok</option>
              </select>
            </div>

            <div>
              <label data-i18n="sim_lbl_close" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 sm:mb-2">
                3. Pregunta al Final:
              </label>
              <select id="sim-closing" class="sim-select-custom w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer shadow-sm">
                <option value="always" selected data-i18n="sim_opt_always">Siempre incluir pregunta</option>
                <option value="relevant" data-i18n="sim_opt_rel">Solo cuando sea relevante</option>
                <option value="never" data-i18n="sim_opt_never">Sin pregunta final</option>
              </select>
            </div>

          </div>

          <div class="mb-5 sm:mb-6">
            <label data-i18n="sim_lbl_comment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Comentario de tu seguidor a simular:
            </label>
            <div class="relative">
              <textarea id="sim-input-text" rows="3" class="w-full bg-slatecard border border-slate-300 rounded-xl p-3.5 sm:p-4 text-xs sm:text-sm font-medium text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all resize-none" placeholder="Escribe un comentario...">Me encanta tu contenido pero siempre procrastino mis proyectos importantes por miedo al fracaso. ¿Qué consejo me das para empezar hoy mismo?</textarea>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
              <span data-i18n="sim_presets_title" class="text-xs font-bold text-slate-500 mr-1">Rápidos:</span>
              <button type="button" onclick="Simulator.loadPreset(1)" data-i18n="sim_preset_1" class="preset-pill-btn text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 border border-slate-200/80">
                💡 "¿Precio del curso?"
              </button>
              <button type="button" onclick="Simulator.loadPreset(2)" data-i18n="sim_preset_2" class="preset-pill-btn text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 border border-slate-200/80">
                🔥 "Gran reflexión"
              </button>
            </div>

            <button type="button" id="sim-btn-generate" onclick="Simulator.generate()" class="w-full sm:w-auto px-6 py-3.5 rounded-xl text-xs sm:text-sm font-bold text-white gradient-button flex items-center justify-center gap-2 shadow-glow-sm shimmer-btn">
              <span data-i18n="sim_btn_gen">Generar Respuesta con IA</span>
              <span>⚡</span>
            </button>
          </div>

          <div id="sim-output-card" class="rounded-2xl bg-slate-50 border border-slate-200/90 p-4 sm:p-6 transition-all duration-300">
            
            <div class="flex flex-wrap items-center justify-between gap-2.5 border-b border-slate-200/80 pb-3 mb-3 sm:mb-4">
              <div class="flex flex-wrap items-center gap-2">
                <span data-i18n="sim_res_title" class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-500">Resultado Generado</span>
                <span id="sim-badge-intent" class="text-[10px] sm:text-[11px] font-bold px-2.5 py-0.5 rounded bg-brand-100 text-brand-800 border border-brand-200">
                  🎯 Intención: Consejo / Crecimiento
                </span>
              </div>
              <div class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs font-semibold text-slate-500 ml-auto sm:ml-0">
                <span>Score: <strong id="sim-badge-score" class="text-brand-600 font-bold">94/100</strong></span>
                <span class="text-emerald-600 font-bold">⚡ 120ms</span>
              </div>
            </div>

            <p id="sim-output-text" class="text-xs sm:text-base text-slate-800 font-medium leading-relaxed mb-4">
              "El miedo al fracaso solo desaparece cuando actúas antes de que la mente empiece a dudar. Divide tu meta en una sola acción de 5 minutos para hoy. La perfección no existe, el progreso diario sí. ¿Qué pequeña tarea harás en los próximos 10 minutos? 👇"
            </p>

            <div class="flex items-center justify-between pt-3 border-t border-slate-200/60 text-xs text-slate-500">
              <span data-i18n="sim_autopilot_ok" class="flex items-center gap-1.5 text-emerald-600 font-semibold text-[11px] sm:text-xs">
                <span>✔</span> Apto para Autopilot en Instagram y Facebook
              </span>
              <button type="button" onclick="Simulator.copyResponse()" id="sim-btn-copy" class="copy-btn-action text-xs font-bold text-brand-600 hover:text-brand-800 flex items-center gap-1">
                <span data-i18n="sim_btn_copy">📋 Copiar</span>
              </button>
            </div>

          </div>

        </div>

      </div>

    </div>
  </section>



  <!-- ========================================================================= -->
  <!-- 9. PRECIOS & PLANES -->
  <!-- ========================================================================= -->
  <section id="precios" class="py-16 sm:py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
        <span data-i18n="price_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Planes Transparentes
        </span>
        <h2 data-i18n="price_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          Comienza gratis y escala con tu comunidad.
        </h2>
        <p data-i18n="price_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Sin contratos forzosos. Cancela en cualquier momento.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 items-stretch">
        
        <!-- Plan 1: Starter -->
        <div class="rounded-2xl sm:rounded-3xl bg-slatecard border border-slate-200 p-6 sm:p-8 flex flex-col justify-between hover:shadow-subtle-card transition-all">
          <div>
            <h3 data-i18n="plan1_t" class="text-base sm:text-lg font-bold text-midnight mb-1">Creador Starter</h3>
            <p data-i18n="plan1_d" class="text-xs text-slate-500 mb-5 sm:mb-6">Para creadores que dan sus primeros pasos.</p>
            <div class="flex items-baseline gap-1 mb-5 sm:mb-6">
              <span class="text-3xl sm:text-4xl font-black text-midnight">$0</span>
              <span data-i18n="plan1_p" class="text-xs text-slate-500 font-bold">/ mes gratis</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-600 font-medium mb-6 sm:mb-8">
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f1">Hasta 1 cuenta de Instagram/Facebook</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f2">100 respuestas automáticas / mes</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f3">Asistente Copilot IA</span></li>
              <li class="flex items-center gap-2 text-slate-400"><span class="text-slate-300">✖</span> <span data-i18n="plan1_f4">Acceso a API de desarrolladores</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan1_btn" class="w-full py-3 rounded-full text-center text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
            Crear Cuenta Gratis
          </a>
        </div>

        <!-- Plan 2: Pro Growth (Featured) -->
        <div class="rounded-2xl sm:rounded-3xl bg-white border-2 border-brand-500 p-6 sm:p-8 flex flex-col justify-between shadow-glow-sm relative">
          <div data-i18n="plan2_badge" class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-brand-600 to-indigo-600 text-white text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider px-3.5 py-1 rounded-full shadow-sm">
            Más Popular
          </div>
          <div>
            <h3 data-i18n="plan2_t" class="text-base sm:text-lg font-bold text-midnight mb-1">Creador Pro</h3>
            <p data-i18n="plan2_d" class="text-xs text-slate-500 mb-5 sm:mb-6">Para marcas y creadores en rápido crecimiento.</p>
            <div class="flex items-baseline gap-1 mb-5 sm:mb-6">
              <span class="text-3xl sm:text-4xl font-black text-midnight">$29</span>
              <span data-i18n="plan2_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-700 font-medium mb-6 sm:mb-8">
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f1">Cuentas ilimitadas de Meta</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f2">Respuestas ilimitadas en Autopilot</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f3">Calibrador de Voz de Marca personalizado</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f4">Smart Timing & Analíticas de Engagement</span></li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan2_f5">Soporte prioritario 24/7</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan2_btn" class="w-full py-3.5 rounded-full text-center text-xs sm:text-sm font-bold text-white gradient-button shadow-glow-sm shimmer-btn">
            Comenzar con Pro
          </a>
        </div>

        <!-- Plan 3: API & Agencias -->
        <div class="rounded-2xl sm:rounded-3xl bg-slatecard border border-slate-200 p-6 sm:p-8 flex flex-col justify-between hover:shadow-subtle-card transition-all">
          <div>
            <h3 data-i18n="plan3_t" class="text-base sm:text-lg font-bold text-midnight mb-1">API & Agencias</h3>
            <p data-i18n="plan3_d" class="text-xs text-slate-500 mb-5 sm:mb-6">Para desarrolladores y agencias de marketing.</p>
            <div class="flex items-baseline gap-1 mb-5 sm:mb-6">
              <span class="text-3xl sm:text-4xl font-black text-midnight">$79</span>
              <span data-i18n="plan3_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-600 font-medium mb-6 sm:mb-8">
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f1">Acceso total a REST API & Webhooks</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f2">Gestión de hasta 25 clientes aislados</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f3">100,000 llamadas a API incluidas / mes</span></li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan3_f4">Marca blanca & Webhook dedicado</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan3_btn" class="w-full py-3 rounded-full text-center text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
            Acceso para Agencias
          </a>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 10. PREGUNTAS FRECUENTES (FAQ Acordeón Interactivo) -->
  <!-- ========================================================================= -->
  <section id="faq" class="py-16 sm:py-24 bg-slatecard border-b border-slate-200/80">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
        <span data-i18n="faq_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Respuestas Claras
        </span>
        <h2 data-i18n="faq_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          Preguntas Frecuentes
        </h2>
        <p data-i18n="faq_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Todo lo que necesitas saber antes de empezar a automatizar tu comunidad.
        </p>
      </div>

      <div class="space-y-3 sm:space-y-4">
        
        <!-- Q1 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(1)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q1">¿Es seguro para mi cuenta de Instagram o Facebook?</span>
            <span id="faq-icon-1" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-1" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a1">
            Totalmente seguro. Xindro opera exclusivamente a través de la API oficial de Meta Graph con permisos autorizados y webhooks verificados. No requerimos tu contraseña de Instagram y no utilizamos navegadores automatizados o emuladores no oficiales.
          </div>
        </div>

        <!-- Q2 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(2)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q2">¿La IA puede responder cosas fuera de lugar o inventar información?</span>
            <span id="faq-icon-2" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-2" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a2">
            No. Cuentas con un calibrador de voz de marca donde defines tus principios, tono y longitud. Además, dispones del <strong>Modo Copilot</strong> que te muestra sugerencias para que las apruebes con un solo clic antes de que se publiquen, dándote control absoluto.
          </div>
        </div>

        <!-- Q3 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(3)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q3">¿Cómo puedo integrar la API en mis propias herramientas o software?</span>
            <span id="faq-icon-3" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-3" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a3">
            Nuestra API RESTful recibe peticiones POST en formato JSON y devuelve las respuestas contextualizadas en menos de 180ms. Puedes enviar comentarios desde cualquier backend en Python, Node.js, PHP o cURL usando tu API Token privado.
          </div>
        </div>

        <!-- Q4 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(4)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q4">¿Puedo empezar gratis sin ingresar tarjeta de crédito?</span>
            <span id="faq-icon-4" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-4" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a4">
            Sí. El plan Creador Starter es 100% gratuito e incluye hasta 100 respuestas al mes y el asistente Copilot para que puedas probar el impacto en tu comunidad antes de decidir actualizar.
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 11. FOOTER PROFESIONAL CON MARCA DE AGUA COMPLETA Y LLAMATIVA -->
  <!-- ========================================================================= -->
  <footer class="starry-footer-bg starry-overlay pt-14 sm:pt-16 pb-12 text-slate-200 text-sm overflow-hidden relative border-t border-slate-800/80">
    
    <!-- Giant Responsive Watermark: Uses the EXACT same typography (Syne 900 / gamma-wordmark) as the Logo -->
    <div class="w-full max-w-full overflow-hidden flex justify-center items-center px-4 my-6 sm:my-8 select-none pointer-events-none">
      <div class="gamma-wordmark text-[clamp(2.8rem,13vw,11.5rem)] font-black tracking-tight leading-none text-center uppercase bg-clip-text text-transparent bg-gradient-to-r from-violet-400/40 via-purple-400/30 to-cyan-400/25 drop-shadow-md select-none">
        XINDRO
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-8 mb-12 sm:mb-16 pt-2">
        
        <!-- Col 1: Brand Info Card -->
        <div class="sm:col-span-2 lg:col-span-1">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 via-indigo-600 to-brand-700 flex items-center justify-center text-white font-bold text-sm shadow-sm">
              ⚡
            </div>
            <span class="text-lg font-black tracking-tight text-white gamma-wordmark">XINDRO<span class="text-brand-400">.</span></span>
          </div>
          <p data-i18n="foot_brand_desc" class="text-xs text-slate-300 leading-relaxed mb-4">
            El sistema operativo de IA para creadores y agencias de redes sociales. Respuestas en tiempo real, Smart Timing y API oficial.
          </p>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-900 border border-slate-700 text-xs text-emerald-400 font-semibold mb-4">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            <span data-i18n="foot_status_pill">Meta API 100% Operativa</span>
          </div>
          <div>
            <a href="login.php" data-i18n="nav_cta" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white gradient-button shadow-glow-sm shimmer-btn">
              <span>Comenzar gratis</span>
              <span>🚀</span>
            </a>
          </div>
        </div>

        <!-- Col 2: Producto -->
        <div>
          <h4 data-i18n="foot_c2_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Producto</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#precios" data-i18n="foot_c2_1" class="hover:text-white hover:underline transition-colors">Precios</a></li>
            <li><a href="#simulador" data-i18n="foot_c2_2" class="hover:text-white hover:underline transition-colors">Inspiración</a></li>
            <li><a href="#por-que-xindro" data-i18n="nav_why" class="hover:text-white hover:underline transition-colors">¿Por qué Xindro?</a></li>
            <li><a href="#calculadora-roi" data-i18n="nav_roi" class="hover:text-white hover:underline transition-colors">Calculadora</a></li>
            <li><a href="#funciones" data-i18n="nav_products" class="hover:text-white hover:underline transition-colors">Funciones</a></li>
          </ul>
        </div>

        <!-- Col 3: Empresa -->
        <div>
          <h4 data-i18n="foot_c3_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Empresa</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#por-que-xindro" data-i18n="foot_c3_1" class="hover:text-white hover:underline transition-colors">Acerca de</a></li>
            <li><a href="login.php" data-i18n="foot_c3_2" class="hover:text-white hover:underline transition-colors">Carreras</a></li>
            <li><a href="login.php" data-i18n="foot_c3_3" class="hover:text-white hover:underline transition-colors">Equipo</a></li>
            <li><a href="#faq" data-i18n="nav_faq" class="hover:text-white hover:underline transition-colors">Preguntas Frecuentes</a></li>
            <li><a href="privacy-policy.php" data-i18n="foot_c3_7" class="hover:text-white hover:underline transition-colors">Seguridad</a></li>
          </ul>
        </div>

        <!-- Col 4: Redes sociales -->
        <div>
          <h4 data-i18n="foot_c4_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Redes sociales</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="https://instagram.com" target="_blank" rel="noopener" class="hover:text-white hover:underline transition-colors">Instagram</a></li>
            <li><a href="https://linkedin.com" target="_blank" rel="noopener" class="hover:text-white hover:underline transition-colors">LinkedIn</a></li>
            <li><a href="https://tiktok.com" target="_blank" rel="noopener" class="hover:text-white hover:underline transition-colors">TikTok</a></li>
            <li><a href="https://x.com" target="_blank" rel="noopener" class="hover:text-white hover:underline transition-colors">X (Twitter)</a></li>
            <li><a href="https://youtube.com" target="_blank" rel="noopener" class="hover:text-white hover:underline transition-colors">YouTube</a></li>
          </ul>
        </div>

        <!-- Col 5: Información legal -->
        <div>
          <h4 data-i18n="foot_c5_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Información legal</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="terms-of-service.php" class="hover:text-white hover:underline transition-colors">Acceptable Use Policy</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white hover:underline transition-colors">Cookie Notice</a></li>
            <li><a href="javascript:void(0)" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_pref" class="hover:text-white hover:underline transition-colors text-brand-300 font-semibold">Preferencias de cookies</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white hover:underline transition-colors">Privacy Policy</a></li>
            <li><a href="data-deletion.php" class="hover:text-white hover:underline transition-colors">Data Deletion (Meta)</a></li>
            <li><a href="terms-of-service.php" class="hover:text-white hover:underline transition-colors">Terms of Service</a></li>
          </ul>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="border-t border-slate-800/80 pt-6 sm:pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-300">
        <div class="flex items-center gap-2 text-center sm:text-left">
          <span class="font-bold text-white">XINDRO</span>
          <span>•</span>
          <span>© <?= date('Y') ?> Xindro Tech, Inc. <span data-i18n="foot_rights">Todos los derechos reservados.</span></span>
        </div>
        <div class="flex items-center gap-4">
          <span class="flex items-center gap-1.5 text-emerald-400 font-medium">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            Meta Graph API Verified
          </span>
          <a href="javascript:void(0)" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_pref" class="text-slate-300 hover:text-white underline">Ajustes de Cookies</a>
        </div>
      </div>

  </footer>

  <!-- ========================================================================= -->
  <!-- 12. POPUP PROFESIONAL DE COOKIES (RESPONSIVO MÓVIL Y DESKTOP) -->
  <!-- ========================================================================= -->
  <div id="cookie-consent-modal" class="fixed bottom-3 inset-x-3 sm:inset-x-auto sm:bottom-5 sm:left-5 z-50 sm:max-w-[430px] w-auto bg-white/95 backdrop-blur-md rounded-2xl shadow-cookie-popup p-4 sm:p-5 border border-slate-200 text-slate-800 cookie-animate hidden">
    
    <div class="flex items-start justify-between gap-3 mb-2">
      <div class="text-xs sm:text-sm font-extrabold text-midnight flex items-center gap-1.5">
        <span data-i18n="cookie_title">Sobre nuestras cookies</span>
        <span>🍪</span>
      </div>
      <button type="button" onclick="CookieConsent.close()" class="text-slate-400 hover:text-slate-700 text-lg font-bold leading-none p-1" title="Cerrar">
        &times;
      </button>
    </div>

    <p class="text-[11px] sm:text-[12px] text-slate-600 leading-relaxed mb-3 sm:mb-4">
      <span data-i18n="cookie_desc_1">Utilizamos cookies y tecnologías similares según se establece en nuestra</span> <a href="privacy-policy.php" data-i18n="cookie_link" class="text-blue-600 hover:underline font-semibold">Política de Cookies</a>. <span data-i18n="cookie_desc_2">Al hacer clic en "Aceptar Todo", aceptas el uso de cookies para personalizar tu experiencia, optimizar la IA y analizar el tráfico de la API.</span>
    </p>

    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
      <button type="button" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_settings" class="text-[10px] sm:text-[11px] font-bold px-3 py-1.5 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-100 transition-colors">
        Configurar Cookies
      </button>

      <div class="flex items-center gap-1.5">
        <button type="button" onclick="CookieConsent.rejectAll()" data-i18n="cookie_btn_reject" class="text-[10px] sm:text-[11px] font-bold px-3 py-1.5 rounded-full bg-slate-900 hover:bg-black text-white transition-colors">
          Rechazar Todo
        </button>
        <button type="button" onclick="CookieConsent.acceptAll()" data-i18n="cookie_btn_accept" class="text-[10px] sm:text-[11px] font-bold px-3.5 sm:px-4 py-1.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-colors shimmer-btn">
          Aceptar Todo
        </button>
      </div>
    </div>

  </div>

  <!-- Detailed Cookies Preferences Modal -->
  <div id="cookie-settings-modal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl sm:rounded-3xl max-w-lg w-full p-5 sm:p-8 shadow-2xl border border-slate-200 text-slate-800 max-h-[90vh] overflow-y-auto">
      
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h3 data-i18n="modal_pref_title" class="text-base sm:text-lg font-extrabold text-midnight flex items-center gap-2">
          <span>Centro de Preferencias de Cookies</span>
          <span>🛡️</span>
        </h3>
        <button type="button" onclick="CookieConsent.closeSettings()" class="text-slate-400 hover:text-slate-700 text-2xl font-bold leading-none">&times;</button>
      </div>
      
      <p data-i18n="modal_pref_desc" class="text-xs text-slate-600 mb-5 leading-relaxed">
        Cumplimos estrictamente con las normativas internacionales de protección de datos (RGPD de la UE, CCPA y LGPD Brasil). Selecciona qué tipos de cookies deseas permitir:
      </p>

      <div class="space-y-3.5 text-xs mb-6">
        
        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200">
          <div class="flex items-center justify-between mb-1">
            <div data-i18n="cookie_cat1_t" class="font-bold text-midnight text-xs sm:text-sm">Cookies Técnicas y de Seguridad (Esenciales)</div>
            <span data-i18n="cookie_cat1_status" class="text-[10px] sm:text-[11px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Siempre Activas</span>
          </div>
          <p data-i18n="cookie_cat1_d" class="text-slate-500 text-[11px] leading-relaxed">
            Requeridas para la autenticación de sesión, tokens de seguridad CSRF y protección de la infraestructura contra ataques.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
          <div>
            <div data-i18n="cookie_cat2_t" class="font-bold text-midnight text-xs sm:text-sm mb-1">Cookies de Rendimiento & Analítica</div>
            <p data-i18n="cookie_cat2_d" class="text-slate-500 text-[11px] leading-relaxed">
              Nos permiten medir la velocidad de respuesta de la IA, uso de endpoints y optimizar la experiencia de los creadores.
            </p>
          </div>
          <input type="checkbox" id="chk-analytics-cookies" checked class="w-5 h-5 accent-brand-600 rounded cursor-pointer shrink-0" />
        </div>

        <div class="p-3.5 sm:p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-4">
          <div>
            <div data-i18n="cookie_cat3_t" class="font-bold text-midnight text-xs sm:text-sm mb-1">Cookies de Personalización & Idioma</div>
            <p data-i18n="cookie_cat3_d" class="text-slate-500 text-[11px] leading-relaxed">
              Recuerdan tus preferencias de idioma (Español, Inglés, Portugués), tono predeterminado y configuraciones del simulador.
            </p>
          </div>
          <input type="checkbox" id="chk-personalization-cookies" checked class="w-5 h-5 accent-brand-600 rounded cursor-pointer shrink-0" />
        </div>

      </div>

      <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
        <button type="button" onclick="CookieConsent.saveCustom()" data-i18n="modal_pref_save" class="w-full sm:w-auto px-5 py-2.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition-colors text-center">
          Guardar Mis Preferencias
        </button>
      </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- 13. JAVASCRIPT: i18n + MOBILE NAV + SIMULATOR + CALCULATOR + FAQ -->
  <!-- ========================================================================= -->
  <script>
    // 0. MOBILE NAVIGATION DRAWER
    const MobileNav = {
      isOpen: false,
      toggle() {
        this.isOpen = !this.isOpen;
        const drawer = document.getElementById('mobile-nav-drawer');
        const iconOpen = document.getElementById('hamburger-icon-open');
        const iconClose = document.getElementById('hamburger-icon-close');
        
        if (this.isOpen) {
          drawer.classList.remove('hidden');
          iconOpen.classList.add('hidden');
          iconClose.classList.remove('hidden');
        } else {
          drawer.classList.add('hidden');
          iconOpen.classList.remove('hidden');
          iconClose.classList.add('hidden');
        }
      },
      close() {
        this.isOpen = false;
        const drawer = document.getElementById('mobile-nav-drawer');
        const iconOpen = document.getElementById('hamburger-icon-open');
        const iconClose = document.getElementById('hamburger-icon-close');
        if (drawer) drawer.classList.add('hidden');
        if (iconOpen) iconOpen.classList.remove('hidden');
        if (iconClose) iconClose.classList.add('hidden');
      }
    };

    // 1. CALCULATOR ENGINE
    const Calculator = {
      update() {
        const comments = parseInt(document.getElementById('calc-comments-range').value, 10);
        const accounts = parseInt(document.getElementById('calc-accounts-range').value, 10);

        document.getElementById('calc-comments-val').textContent = comments.toLocaleString() + ' comentarios';
        document.getElementById('calc-accounts-val').textContent = accounts + (accounts === 1 ? ' cuenta' : ' cuentas');

        const totalMinutes = (comments * accounts * 0.75);
        const hoursSaved = Math.round(totalMinutes / 60);
        const leadsDetected = Math.round((comments * accounts) * 0.024);

        document.getElementById('calc-res-hours').textContent = `+${hoursSaved} hrs`;
        document.getElementById('calc-res-leads').textContent = `+${leadsDetected.toLocaleString()}`;
      }
    };

    // 2. FAQ ACCORDION ENGINE
    const Faq = {
      toggle(id) {
        const ans = document.getElementById('faq-ans-' + id);
        const icon = document.getElementById('faq-icon-' + id);
        if (!ans) return;

        const isHidden = ans.classList.contains('hidden');
        for (let i = 1; i <= 4; i++) {
          const a = document.getElementById('faq-ans-' + i);
          const ic = document.getElementById('faq-icon-' + i);
          if (a) a.classList.add('hidden');
          if (ic) { ic.textContent = '+'; ic.style.transform = 'rotate(0deg)'; }
        }

        if (isHidden) {
          ans.classList.remove('hidden');
          if (icon) { icon.textContent = '−'; icon.style.transform = 'rotate(180deg)'; }
        }
      }
    };

    // 3. DICCIONARIO MULTI-IDIOMA (ES / EN / PT)
    const I18n = {
      current: '<?= $initialLang ?>',

      dict: {
        es: {
          page_title: "XINDRO — El Sistema Operativo de IA para Creadores de Contenido",
          page_desc: "Automatiza tus redes sociales. Responde comentarios en piloto automático, analiza métricas de engagement y publica en múltiples plataformas desde una sola API.",
          nav_products: "Productos",
          nav_why: "¿Por qué Xindro?",
          nav_simulator: "Simulador",
          nav_roi: "Calculadora",
          nav_pricing: "Precios",
          nav_faq: "FAQ",
          nav_login: "Iniciar sesión",
          nav_cta: "Comienza gratis",
          nav_dashboard: "Ir a mi Panel",
          hero_badge: "El sistema operativo de IA para creadores de contenido",
          hero_h1_p1: "Automatiza tus redes sociales.",
          hero_h1_p2: "Escala tu comunidad sin perder el toque humano.",
          hero_sub: "Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y publica en múltiples plataformas.",
          hero_cta_sim: "Prueba el Simulador",
          hero_card_title: "XINDRO Live Copilot — Flujo en Tiempo Real",
          hero_card_status: "Meta Webhook Activo",
          hero_card_time: "Instagram • Hace 2 seg",
          hero_card_intent: "🎯 Intención: <strong class=\"text-brand-600\">Pregunta Clave</strong>",
          hero_card_calibrated: "IA Calibrada",
          hero_card_bot_reply: "🤖 Respuesta con Voz de Marca",
          hero_card_tone: "Mentor Empático",
          hero_card_retention: "🚀 Retención: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Listo para postear",
          hero_comment_sample: "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?",
          hero_reply_sample: "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇",
          marquee_title: "Integrado con la Infraestructura Oficial de Redes Sociales e Inteligencia Artificial",
          stat_1: "Comentarios Respondidos",
          stat_2: "Aumento en Engagement",
          stat_3: "Precisión de Voz Humana",
          stat_4: "Latencia de Sistema en Vivo",
          why_badge: "Diferenciales Reales",
          why_h2: "¿Por qué los creadores y agencias eligen Xindro?",
          why_sub: "Diseñado desde el código para responder en segundos, proteger tu reputación y maximizar el algoritmo sin sonar como un robot.",
          why_p1_t: "Respuestas en Tiempo Real (<180ms)",
          why_p1_d: "El algoritmo de Meta premia a las cuentas que interactúan en los primeros 15 minutos. Nuestro motor heurístico responde casi al instante.",
          why_p1_tag: "✔ Cero demora de engagement",
          why_p2_t: "Voz de Marca Auténtica & Calibrada",
          why_p2_d: "Configura el nivel de calidez, profundidad y energía. Tus seguidores recibirán respuestas empáticas y humanas, nunca genéricas.",
          why_p2_tag: "✔ Personalización total de tono",
          why_p3_t: "100% Oficial con Meta Graph API",
          why_p3_d: "Cero riesgo de penalización o bloqueos de cuenta. Conexión autorizada por Meta con cifrado de tokens AES-256-GCM.",
          why_p3_tag: "✔ Seguridad y cumplimiento oficial",
          why_p4_t: "Smart Timing Basado en Datos",
          why_p4_d: "Analiza los patrones de actividad real de tu comunidad para decirte la hora exacta en la que obtendrás mayor alcance y guardados.",
          why_p4_tag: "✔ +142% de alcance orgánico",
          why_p5_t: "Motor de Engagement Avanzado",
          why_p5_d: "Integra el motor de engagement en tus propios procesos de marketing con endpoints limpios y optimizados para alto rendimiento.",
          why_p5_tag: "✔ Flujo de trabajo optimizado",
          why_p6_t: "Ahorra +35 Horas de Trabajo al Mes",
          why_p6_d: "Elimina el trabajo repetitivo de responder dudas frecuentes y aprovecha ese tiempo para crear contenido que mueva tu negocio.",
          why_p6_tag: "✔ Enfoque 100% en crear",
          calc_badge: "Calculadora de Impacto",
          calc_h2: "¿Cuánto tiempo y alcance ganas con Xindro?",
          calc_sub: "Ajusta el volumen de comentarios mensuales y descubre el impacto real en tu comunidad.",
          calc_lbl_comments: "Comentarios recibidos al mes:",
          calc_lbl_accounts: "Cuentas de Instagram / Facebook:",
          calc_res_title: "Impacto Estimado Mensual",
          calc_res_h_label: "Tiempo Manual Ahorrado",
          calc_res_l_label: "Leads / Preguntas Clave",
          calc_res_footer: "de respuestas entregadas en la ventana de oro del algoritmo sin agotamiento humano.",
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
          sim_presets_title: "Rápidos:",
          sim_preset_1: "💡 \"¿Precio del curso?\"",
          sim_preset_2: "🔥 \"Gran reflexión\"",
          sim_btn_gen: "Generar Respuesta con IA",
          sim_res_title: "Resultado Generado",
          sim_autopilot_ok: "✔ Apto para Autopilot en Instagram y Facebook",
          sim_btn_copy: "📋 Copiar",
          price_badge: "Planes Transparentes",
          price_h2: "Comienza gratis y escala con tu comunidad.",
          price_sub: "Sin contratos forzosos. Cancela en cualquier momento.",
          plan1_t: "Creador Starter",
          plan1_d: "Para creadores que dan sus primeros pasos.",
          plan1_p: "/ mes gratis",
          plan1_f1: "Hasta 1 cuenta de Instagram/Facebook",
          plan1_f2: "100 respuestas automáticas / mes",
          plan1_f3: "Asistente Copilot IA",
          plan1_f4: "Acceso al motor base",
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
          plan3_t: "Agencias",
          plan3_d: "Para equipos de marketing y gestión de marcas.",
          plan3_p: "/ mes",
          plan3_f1: "Gestión de cuentas centralizada",
          plan3_f2: "Gestión de hasta 25 clientes aislados",
          plan3_f3: "Respuestas automáticas ilimitadas",
          plan3_f4: "Marca blanca & Soporte dedicado",
          plan3_btn: "Acceso para Agencias",
          faq_badge: "Respuestas Claras",
          faq_h2: "Preguntas Frecuentes",
          faq_sub: "Todo lo que necesitas saber antes de empezar a automatizar tu comunidad.",
          faq_q1: "¿Es seguro para mi cuenta de Instagram o Facebook?",
          faq_a1: "Totalmente seguro. Xindro opera exclusivamente a través de la API oficial de Meta Graph con permisos autorizados y webhooks verificados. No requerimos tu contraseña de Instagram y no utilizamos navegadores automatizados o emuladores no oficiales.",
          faq_q2: "¿La IA puede responder cosas fuera de lugar o inventar información?",
          faq_a2: "No. Cuentas con un calibrador de voz de marca donde defines tus principios, tono y longitud. Además, dispones del <strong>Modo Copilot</strong> que te muestra sugerencias para que las apruebes con un solo clic antes de que se publiquen, dándote control absoluto.",
          faq_q3: "¿Puedo conectar varias cuentas al mismo tiempo?",
          faq_a3: "Sí, puedes conectar y gestionar múltiples cuentas de Instagram y Páginas de Facebook de forma centralizada.",
          faq_q4: "¿Puedo empezar gratis sin ingresar tarjeta de crédito?",
          faq_a4: "Sí. El plan Creador Starter es 100% gratuito e incluye hasta 100 respuestas al mes y el asistente Copilot para que puedas probar el impacto en tu comunidad antes de decidir actualizar.",
          foot_brand_desc: "El sistema operativo de IA para creadores y agencias de redes sociales. Respuestas en tiempo real, Smart Timing y sincronización oficial.",
          foot_status_pill: "Meta API 100% Operativa",
          foot_c2_t: "Producto",
          foot_c2_1: "Precios",
          foot_c2_2: "Inspiración",
          foot_c3_t: "Empresa",
          foot_c3_1: "Acerca de",
          foot_c3_2: "Carreras",
          foot_c3_3: "Equipo",
          foot_c3_7: "Seguridad",
          foot_c4_t: "Redes sociales",
          foot_c5_t: "Información legal",
          foot_rights: "Todos los derechos reservados.",
          cookie_title: "Sobre nuestras cookies",
          cookie_desc_1: "Utilizamos cookies y tecnologías similares según se establece en nuestra",
          cookie_link: "Política de Cookies",
          cookie_desc_2: "Al hacer clic en \"Aceptar Todo\", aceptas el uso de cookies para personalizar tu experiencia, optimizar la IA y analizar el tráfico.",
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
          cookie_cat2_d: "Nos permiten medir la velocidad de respuesta de la IA y optimizar la experiencia de los creadores.",
          cookie_cat3_t: "Cookies de Personalización & Idioma",
          cookie_cat3_d: "Recuerdan tus preferencias de idioma (Español, Inglés, Portugués), tono predeterminado y configuraciones del simulador.",
          modal_pref_save: "Guardar Mis Preferencias"
        },

        en: {
          page_title: "XINDRO — The AI Operating System for Content Creators",
          page_desc: "Automate your social media. Reply to comments on autopilot, analyze engagement metrics to find your perfect posting time, and scale your presence across platforms.",
          nav_products: "Products",
          nav_why: "Why Xindro?",
          nav_simulator: "Simulator",
          nav_roi: "Calculator",
          nav_pricing: "Pricing",
          nav_faq: "FAQ",
          nav_login: "Log in",
          nav_cta: "Get started free",
          nav_dashboard: "Go to Dashboard",
          hero_badge: "The AI operating system for content creators",
          hero_h1_p1: "Automate your social media.",
          hero_h1_p2: "Scale your community without losing the human touch.",
          hero_sub: "Reply to comments on autopilot, analyze engagement metrics to find your perfect posting time, and publish across multiple platforms seamlessly.",
          hero_cta_sim: "Try the Simulator",
          hero_card_title: "XINDRO Live Copilot — Real-Time Flow",
          hero_card_status: "Meta Webhook Active",
          hero_card_time: "Instagram • 2s ago",
          hero_card_intent: "🎯 Intent: <strong class=\"text-brand-600\">High-Value Question</strong>",
          hero_card_calibrated: "Calibrated AI",
          hero_card_bot_reply: "🤖 Brand Voice Reply",
          hero_card_tone: "Empathetic Mentor",
          hero_card_retention: "🚀 Retention: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Ready to post",
          hero_comment_sample: "I've been trying to stay consistent on social media for weeks but I run out of ideas and lose motivation. How do you structure your daily routine?",
          hero_reply_sample: "Alejandro, the key isn't motivation that comes and goes, but systems. Block 45 min every morning before checking your phone. Daily discipline beats sporadic inspiration. What is the first thing you will do tomorrow when you wake up? 👇",
          marquee_title: "Integrated with Official Social Media & AI Infrastructure",
          stat_1: "Comments Replied",
          stat_2: "Increase in Engagement",
          stat_3: "Human Voice Accuracy",
          stat_4: "Live System Latency",
          why_badge: "Real Differentials",
          why_h2: "Why do creators and agencies choose Xindro?",
          why_sub: "Architected from code to reply in seconds, protect your brand reputation, and win the algorithm without sounding robotic.",
          why_p1_t: "Real-Time Responses (<180ms)",
          why_p1_d: "Meta's algorithm rewards accounts that interact within the first 15 minutes. Our engine responds almost instantly.",
          why_p1_tag: "✔ Zero engagement lag",
          why_p2_t: "Authentic & Calibrated Brand Voice",
          why_p2_d: "Configure warmth, depth, and energy levels. Followers receive genuine, human-feeling replies—never generic fluff.",
          why_p2_tag: "✔ 100% custom tone control",
          why_p3_t: "100% Official via Meta Graph API",
          why_p3_d: "Zero risk of shadowbans or account restrictions. Official Meta connection with AES-256-GCM token encryption.",
          why_p3_tag: "✔ Enterprise compliance & security",
          why_p4_t: "Data-Driven Smart Timing",
          why_p4_d: "Analyzes actual audience activity to predict the exact second your post will receive peak reach and saves.",
          why_p4_tag: "✔ +142% organic reach boost",
          why_p5_t: "Advanced Engagement Engine",
          why_p5_d: "Embed the engagement engine into your own marketing workflows with clean, high-performance API endpoints.",
          why_p5_tag: "✔ Optimized workflows",
          why_p6_t: "Save +35 Hours of Manual Work / Month",
          why_p6_d: "Eliminate repetitive comment triage and spend your valuable energy on creating high-impact content.",
          why_p6_tag: "✔ 100% focus on creating",
          calc_badge: "Impact Calculator",
          calc_h2: "How much time and reach do you gain with Xindro?",
          calc_sub: "Adjust monthly comment volume and discover the real operational impact on your business.",
          calc_lbl_comments: "Monthly comments received:",
          calc_lbl_accounts: "Instagram / Facebook accounts:",
          calc_res_title: "Estimated Monthly Impact",
          calc_res_h_label: "Manual Hours Saved",
          calc_res_l_label: "Qualified Leads / Questions",
          calc_res_footer: "of replies delivered in the golden algorithm window without human burnout.",
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
          sim_res_title: "Result Generated",
          sim_autopilot_ok: "✔ Eligible for Autopilot on Instagram and Facebook",
          sim_btn_copy: "📋 Copy",
          price_badge: "Transparent Pricing",
          price_h2: "Start free and scale with your audience.",
          price_sub: "No lock-in contracts. Cancel anytime.",
          plan1_t: "Creator Starter",
          plan1_d: "For creators taking their first steps.",
          plan1_p: "/ month free",
          plan1_f1: "Up to 1 Instagram/Facebook account",
          plan1_f2: "100 automated replies / month",
          plan1_f3: "AI Copilot Assistant",
          plan1_f4: "Base engine access",
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
          plan3_t: "Agencies",
          plan3_d: "For content teams and marketing agencies.",
          plan3_p: "/ month",
          plan3_f1: "Centralized account management",
          plan3_f2: "Manage up to 25 isolated client tenants",
          plan3_f3: "Unlimited automated replies",
          plan3_f4: "White-label & Dedicated support",
          plan3_btn: "Agency Access",
          faq_badge: "Clear Answers",
          faq_h2: "Frequently Asked Questions",
          faq_sub: "Everything you need to know before automating your community.",
          faq_q1: "Is it safe for my Instagram or Facebook account?",
          faq_a1: "100% safe. Xindro operates strictly through official Meta Graph API endpoints with verified webhooks and granted permissions. We never ask for your account password or use unofficial scrapers.",
          faq_q2: "Can the AI hallucinate or post inappropriate replies?",
          faq_a2: "No. You have a Brand Voice Calibrator to establish guidelines and tone. You also have <strong>Copilot Mode</strong>, which generates suggested replies for 1-click human approval before posting.",
          faq_q3: "Can I connect multiple accounts simultaneously?",
          faq_a3: "Yes, you can manage multiple Instagram accounts and Facebook Pages from a single centralized dashboard.",
          faq_q4: "Can I start for free without a credit card?",
          faq_a4: "Yes! The Creator Starter plan is 100% free with up to 100 automated replies per month and Copilot assistant included.",
          foot_brand_desc: "The AI operating system for creators and social media agencies. Real-time replies, Smart Timing, and official synchronization.",
          foot_status_pill: "Meta API 100% Operational",
          foot_c2_t: "Product",
          foot_c2_1: "Pricing",
          foot_c2_2: "Inspiration",
          foot_c3_t: "Company",
          foot_c3_1: "About",
          foot_c3_2: "Careers",
          foot_c3_3: "Team",
          foot_c3_7: "Security",
          foot_c4_t: "Socials",
          foot_c5_t: "Legal",
          foot_rights: "All rights reserved.",
          cookie_title: "About our cookies",
          cookie_desc_1: "We use cookies and similar technologies as set out in our",
          cookie_link: "Cookie Notice",
          cookie_desc_2: "By clicking \"Accept All\", you agree to our use of optional cookies to personalize your experience and optimize the platform.",
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
          cookie_cat2_d: "Allow us to monitor AI response latency and optimize creator workflows.",
          cookie_cat3_t: "Personalization & Language Cookies",
          cookie_cat3_d: "Remember your language preferences (Spanish, English, Portuguese), default tone, and simulator presets.",
          modal_pref_save: "Save My Preferences"
        },

        pt: {
          page_title: "XINDRO — O Sistema Operacional de IA para Criadores de Conteúdo",
          page_desc: "Automatize suas redes sociais. Responda a comentários no piloto automático, analise métricas de engajamento para encontrar seu horário perfeito e escale sua presença.",
          nav_products: "Produtos",
          nav_why: "Por que a Xindro?",
          nav_simulator: "Simulador",
          nav_roi: "Calculadora",
          nav_pricing: "Preços",
          nav_faq: "FAQ",
          nav_login: "Entrar",
          nav_cta: "Comece grátis",
          nav_dashboard: "Ir ao Painel",
          hero_badge: "O sistema operacional de IA para criadores de conteúdo",
          hero_h1_p1: "Automatize suas redes sociais.",
          hero_h1_p2: "Escale sua comunidade sem perder o toque humano.",
          hero_sub: "Responda a comentários no piloto automático, analise métricas de engajamento para encontrar seu horário perfeito e publique em várias plataformas.",
          hero_cta_sim: "Testar o Simulador",
          hero_card_title: "XINDRO Live Copilot — Fluxo em Tempo Real",
          hero_card_status: "Meta Webhook Ativo",
          hero_card_time: "Instagram • Há 2 seg",
          hero_card_intent: "🎯 Intenção: <strong class=\"text-brand-600\">Pergunta Clave</strong>",
          hero_card_calibrated: "IA Calibrada",
          hero_card_bot_reply: "🤖 Resposta com Tom de Marca",
          hero_card_tone: "Mentor Empático",
          hero_card_retention: "🚀 Retenção: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Pronto para postar",
          hero_comment_sample: "Estou há semanas tentando ter consistência nas redes, mas fico sem ideias e perco a motivação. Como vocês estruturam a rotina diária?",
          hero_reply_sample: "Alejandro, o segredo não é a motivação que vai e vem, mas os sistemas. Bloqueie 45 min toda manhã antes de olhar o celular. A disciplina diária supera a inspiração passageira. Qual é a primeira coisa que você fará amanhã ao acordar? 👇",
          marquee_title: "Integrado com a Infraestrutura Oficial de Redes Sociais e Inteligência Artificial",
          stat_1: "Comentários Respondidos",
          stat_2: "Aumento no Engajamento",
          stat_3: "Precisão de Voz Humana",
          stat_4: "Latência de Sistema em Vivo",
          why_badge: "Diferenciais Reais",
          why_h2: "Por que criadores e agências escolhem a Xindro?",
          why_sub: "Projetado desde o código para responder em segundos, proteger sua marca e vencer o algoritmo sem soar artificial.",
          why_p1_t: "Respostas em Tempo Real (<180ms)",
          why_p1_d: "O algoritmo da Meta prioriza contas que interagem nos primeiros 15 minutos. Nosso motor heurístico responde quase instantaneamente.",
          why_p1_tag: "✔ Zero atraso de engajamento",
          why_p2_t: "Voz de Marca Autêntica & Calibrada",
          why_p2_d: "Defina os níveis de acolhimento, profundidade e energia para que seus seguidores recebam respostas empáticas e humanas.",
          why_p2_tag: "✔ Personalização total de tom",
          why_p3_t: "100% Oficial com a Meta Graph API",
          why_p3_d: "Zero risco de bloqueios. Conexão oficial da Meta com criptografia de tokens AES-256-GCM.",
          why_p3_tag: "✔ Segurança e conformidade oficial",
          why_p4_t: "Smart Timing Baseado em Dados",
          why_p4_d: "Analisa a atividade real da sua comunidade para indicar o segundo exato de postagem com maior alcance.",
          why_p4_tag: "✔ +142% de alcance orgánico",
          why_p5_t: "Motor de Engagement Avançado",
          why_p5_d: "Integre o motor de engajamento em seus próprios fluxos de marketing com endpoints simples e de alto desempenho.",
          why_p5_tag: "✔ Fluxos de trabalho otimizados",
          why_p6_t: "Economize +35 Horas de Trabalho por Mês",
          why_p6_d: "Elimine o trabalho repetitivo de responder dúvidas frequentes e foque em criar conteúdo de alto impacto.",
          why_p6_tag: "✔ 100% de foco na criação",
          calc_badge: "Calculadora de Impacto",
          calc_h2: "Quanto tempo e alcance você ganha com a Xindro?",
          calc_sub: "Ajuste o volume mensal de comentários e veja o impacto real no seu negócio.",
          calc_lbl_comments: "Comentários recebidos por mês:",
          calc_lbl_accounts: "Contas do Instagram / Facebook:",
          calc_res_title: "Impacto Estimado Mensual",
          calc_res_h_label: "Horas Manuais Economizadas",
          calc_res_l_label: "Leads / Perguntas Qualificadas",
          calc_res_footer: "das respostas entregues na janela de ouro do algoritmo sem exaustão humana.",
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
          sim_presets_title: "Rápidos:",
          sim_preset_1: "💡 \"Preço do curso?\"",
          sim_preset_2: "🔥 \"Ótima reflexão\"",
          sim_btn_gen: "Gerar Resposta com IA",
          sim_res_title: "Resultado Gerado",
          sim_autopilot_ok: "✔ Apto para Autopilot no Instagram e Facebook",
          sim_btn_copy: "📋 Copiar",
          price_badge: "Planos Transparentes",
          price_h2: "Comece grátis e escale com sua comunidade.",
          price_sub: "Sem contratos obrigatórios. Cancele quando quiser.",
          plan1_t: "Criador Starter",
          plan1_d: "Para criadores dando os primeiros passos.",
          plan1_p: "/ mês grátis",
          plan1_f1: "Até 1 conta do Instagram/Facebook",
          plan1_f2: "100 respostas automáticas / mês",
          plan1_f3: "Assistente Copilot IA",
          plan1_f4: "Acesso ao motor base",
          plan1_btn: "Criar Conta Grátis",
          plan2_badge: "Mais Popular",
          plan2_t: "Criador Pro",
          plan2_d: "Para marcas e criadores em rápido crescimento.",
          plan2_p: "/ mês",
          plan2_f1: "Contas ilimitadas da Meta",
          plan2_f2: "Respostas ilimitadas no Autopilot",
          plan2_f3: "Calibrador de Tom de Marca personalizado",
          plan2_f4: "Smart Timing & Métricas de Engajamento",
          plan2_f5: "Suporte prioritário 24/7",
          plan2_btn: "Começar com Pro",
          plan3_t: "Agências",
          plan3_d: "Para equipes de marketing e gestão de marcas.",
          plan3_p: "/ mês",
          plan3_f1: "Gerenciamento centralizado de contas",
          plan3_f2: "Gerenciamento de até 25 clientes isolados",
          plan3_f3: "Respostas automáticas ilimitadas",
          plan3_f4: "Marca branca & Suporte dedicado",
          plan3_btn: "Acesso para Agências",
          faq_badge: "Respostas Claras",
          faq_h2: "Perguntas Frequentes",
          faq_sub: "Tudo o que você precisa saber antes de automatizar sua comunidade.",
          faq_q1: "É seguro para minha conta do Instagram ou Facebook?",
          faq_a1: "Totalmente seguro. O Xindro opera estritamente através da API oficial do Meta Graph com permissões autorizadas e webhooks verificados. Nunca solicitamos sua senha e não usamos navegadores ou emuladores não oficiais.",
          faq_q2: "A IA pode responder coisas fora de contexto?",
          faq_a2: "Não. Você conta com um calibrador de voz de marca para definir princípios e tom. Além disso, o <strong>Modo Copilot</strong> gera sugestões para aprovação humana com 1 clique antes de postar.",
          faq_q3: "Posso conectar várias contas ao mesmo tempo?",
          faq_a3: "Sim, você pode conectar e gerenciar várias contas do Instagram e Páginas do Facebook de forma centralizada.",
          faq_q4: "Posso começar grátis sem cartão de crédito?",
          faq_a4: "Sim! O plano Criador Starter é 100% gratuito com até 100 respostas automáticas por mês e assistente Copilot incluído.",
          foot_brand_desc: "O sistema operacional de IA para criadores e agências de redes sociais. Respostas em tempo real, Smart Timing e sincronização oficial.",
          foot_status_pill: "Meta API 100% Operacional",
          foot_c2_t: "Produto",
          foot_c2_1: "Preços",
          foot_c2_2: "Inspiração",
          foot_c3_t: "Empresa",
          foot_c3_1: "Sobre",
          foot_c3_2: "Carreiras",
          foot_c3_3: "Equipe",
          foot_c3_7: "Segurança",
          foot_c4_t: "Redes sociais",
          foot_c5_t: "Informações legais",
          foot_rights: "Todos os direitos reservados.",
          cookie_title: "Sobre nossos cookies",
          cookie_desc_1: "Usamos cookies e tecnologias semelhantes conforme estabelecido em nossa",
          cookie_link: "Política de Cookies",
          cookie_desc_2: "Ao clicar em \"Aceitar Tudo\", você concorda com o uso de cookies para personalizar sua experiência e otimizar a plataforma.",
          cookie_btn_settings: "Configurar Cookies",
          cookie_btn_reject: "Rejeitar Tudo",
          cookie_btn_accept: "Aceitar Tudo",
          cookie_btn_pref: "Preferências de cookies",
          modal_pref_title: "Centro de Preferências de Cookies",
          modal_pref_desc: "Cumprimos rigorosamente os regulamentos globais de privacidade de dados (LGPD Brasil, GDPR da UE e CCPA). Selecione quais categorias de cookies deseja permitir:",
          cookie_cat1_t: "Cookies Essenciais e de Segurança (Obrigatórios)",
          cookie_cat1_status: "Sempre Ativos",
          cookie_cat1_d: "Necessários para autenticação de sessão, tokens de segurança CSRF e proteção da infraestrutura contra ataques.",
          cookie_cat2_t: "Cookies de Desempenho & Análise",
          cookie_cat2_d: "Permitem medir a velocidade de resposta da IA e otimizar a experiência dos criadores.",
          cookie_cat3_t: "Cookies de Personalização & Idioma",
          cookie_cat3_d: "Lembram suas preferências de idioma (Espanhol, Inglês, Português), tom padrão e configurações do simulador.",
          modal_pref_save: "Salvar Minhas Preferências"
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
          if (d[key] !== undefined) {
            el.innerHTML = d[key];
          }
        });

        if (d.page_title) document.title = d.page_title;
        const metaDesc = document.getElementById('meta-page-desc');
        if (metaDesc && d.page_desc) metaDesc.setAttribute('content', d.page_desc);

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

    document.addEventListener('click', (e) => {
      const wrapper = document.getElementById('lang-dropdown-wrapper');
      if (wrapper && !wrapper.contains(e.target)) {
        I18n.hideLangMenu();
      }
    });

    // 4. COOKIE CONSENT MANAGER
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

    // 5. SIMULATOR ENGINE
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



    document.addEventListener('DOMContentLoaded', () => {
      I18n.init();
      CookieConsent.init();
      Calculator.update();
    });
  </script>

</body>
</html>
