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

    .sim-context-box {
      background: linear-gradient(135deg, rgba(245, 243, 255, 0.8) 0%, rgba(238, 242, 255, 0.6) 100%);
      border: 1px solid rgba(196, 181, 253, 0.7);
    }

    .step-number-badge {
      background: linear-gradient(135deg, #8B5CF6 0%, #6D28D9 100%);
      box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
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
      <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-[13px] xl:text-sm font-semibold text-slate-600">
        <a href="#funciones" data-i18n="nav_products" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Funciones</a>
        <a href="#como-empezar" data-i18n="nav_steps" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Cómo empezar</a>
        <a href="#simulador" data-i18n="nav_simulator" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors flex items-center gap-1.5">
          <span>Simulador</span>
          <span class="inline-block w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
        </a>
        <a href="#calculadora-roi" data-i18n="nav_roi" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Calculadora</a>
        <a href="#por-que-xindro" data-i18n="nav_why" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">¿Por qué Xindro?</a>
        <a href="#precios" data-i18n="nav_pricing" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Precios</a>
        <a href="#faq" data-i18n="nav_faq" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">FAQ</a>
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
          <span>⚡ Funciones y Beneficios</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#como-empezar" onclick="MobileNav.close()" data-i18n="nav_steps" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>🚀 Cómo empezar en 3 pasos</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#simulador" onclick="MobileNav.close()" data-i18n="nav_simulator" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span class="flex items-center gap-2">
            <span>✨ Simulador en Vivo</span>
            <span class="w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
          </span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#calculadora-roi" onclick="MobileNav.close()" data-i18n="nav_roi" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>🧮 Calculadora de Ahorro</span>
          <span class="text-slate-400">→</span>
        </a>
        <a href="#por-que-xindro" onclick="MobileNav.close()" data-i18n="nav_why" class="p-2.5 rounded-xl hover:bg-brand-50 hover:text-brand-700 transition-colors flex items-center justify-between">
          <span>🛡️ ¿Por qué Xindro?</span>
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
  <!-- 2. HERO SECTION CON PROMESA CONCRETA + DEMO VISUAL DEL FLUJO -->
  <!-- ========================================================================= -->
  <section class="relative pt-32 pb-14 md:pt-44 md:pb-20 hero-mesh-bg overflow-hidden border-b border-slate-100">
    
    <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[650px] h-[350px] bg-brand-400/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      
      <!-- Top Pill Badge -->
      <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-50 border border-brand-200/80 text-brand-700 text-xs sm:text-sm font-bold mb-6 sm:mb-8 shadow-sm hover:border-brand-300 transition-colors">
        <span class="flex h-2 w-2 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
        </span>
        <span data-i18n="hero_badge">El sistema operativo de IA para creadores de contenido y marcas</span>
      </div>

      <!-- Main Headline (H1) -->
      <h1 class="text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-midnight leading-[1.15] mb-5 sm:mb-6 max-w-4xl mx-auto">
        <span data-i18n="hero_h1_p1">Convierte los comentarios de Instagram y Facebook</span> <br class="hidden sm:inline" />
        <span class="gradient-text" data-i18n="hero_h1_p2">en conversaciones que hacen crecer tu negocio.</span>
      </h1>

      <!-- Subtitle (P) -->
      <p data-i18n="hero_sub" class="text-sm sm:text-lg md:text-xl text-slate-600 max-w-3xl mx-auto mb-8 sm:mb-10 leading-relaxed font-normal">
        Reúne tus comentarios, identifica consultas de compra y prepara respuestas con el estilo de tu marca. Revisa las sugerencias antes de publicar y dedica más tiempo a crear y atender oportunidades.
      </p>

      <!-- CTAs Button Group with Shimmer Glow -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4 max-w-md mx-auto mb-12 sm:mb-16">
        <a href="#simulador" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 sm:px-8 sm:py-4 rounded-xl text-sm sm:text-base font-bold text-white gradient-button shadow-glow-md shimmer-btn">
          <span data-i18n="hero_cta_sim">Probar una respuesta gratis</span>
          <span class="text-lg">✨</span>
        </a>
        <a href="#funciones" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-7 py-3.5 sm:px-8 sm:py-4 rounded-xl text-sm sm:text-base font-bold text-slate-700 bg-white border border-slate-200 hover:border-brand-300 hover:bg-slate-50 hover:text-brand-700 shadow-sm secondary-btn">
          <span data-i18n="hero_cta_calc">Qué puedes hacer</span>
          <span>⚡</span>
        </a>
      </div>

      <!-- Live Interactive Visual Hero Card: Flow: Comentario -> Intención -> Sugerencia -> Aprobación -->
      <div class="relative max-w-4xl mx-auto rounded-2xl sm:rounded-3xl bg-white border border-slate-200/90 shadow-elevated-card p-4 sm:p-6 md:p-8 text-left">
        
        <div class="flex flex-wrap items-center justify-between border-b border-slate-100 pb-4 mb-5 sm:mb-6 gap-2">
          <div class="flex items-center gap-2 sm:gap-3">
            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-red-400"></div>
            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-amber-400"></div>
            <div class="w-2.5 h-2.5 sm:w-3 sm:h-3 rounded-full bg-emerald-400"></div>
            <span class="text-[11px] sm:text-xs font-semibold text-slate-400 ml-1 sm:ml-2" data-i18n="hero_card_title">Flujo en Tiempo Real: Comentario ➔ Detección de Intención ➔ Sugerencia ➔ Aprobación Humana</span>
          </div>
          <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] sm:text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 live-dot"></span>
            <span data-i18n="hero_card_status">Meta Graph API Oficial</span>
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
              <span class="ml-auto text-[10px] sm:text-[11px] font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200/60" title="Prioridad Comercial basada en consulta de compra">
                Prioridad: 96/100
              </span>
            </div>
            <p id="hero-sample-comment" class="text-xs sm:text-[13px] text-slate-700 leading-relaxed font-medium">
              "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?"
            </p>
            <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-[10px] sm:text-[11px] text-slate-500 font-semibold">
              <span data-i18n="hero_card_intent">🎯 Intención: <strong class="text-brand-600">Pregunta de Mentoría</strong></span>
              <span class="text-emerald-600 font-bold">⚡ 142ms</span>
            </div>
          </div>

          <!-- Flow Arrow -->
          <div class="md:col-span-2 flex flex-col items-center justify-center text-brand-600 py-1 md:py-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-brand-50 border border-brand-200 flex items-center justify-center font-bold text-xs sm:text-sm shadow-sm">
              ⚡
            </div>
            <span class="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1" data-i18n="hero_card_calibrated">Voz Calibrada</span>
          </div>

          <!-- Generated AI Reply -->
          <div class="md:col-span-5 bg-gradient-to-br from-brand-50/70 to-indigo-50/50 rounded-xl sm:rounded-2xl p-4 border border-brand-200/80">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xs font-bold text-brand-800 flex items-center gap-1.5">
                <span data-i18n="hero_card_bot_reply">🤖 Sugerencia con Voz de Marca</span>
              </span>
              <span class="ml-auto text-[9px] sm:text-[10px] font-bold text-brand-700 bg-brand-100/70 px-2 py-0.5 rounded-full" data-i18n="hero_card_tone">
                Mentor Sabio
              </span>
            </div>
            <p id="hero-sample-reply" class="text-xs sm:text-[13px] text-brand-950 leading-relaxed font-medium">
              "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇"
            </p>
            <div class="mt-3 pt-2 border-t border-brand-200/50 flex items-center justify-between text-[10px] sm:text-[11px]">
              <span class="text-brand-700 font-semibold" data-i18n="hero_card_retention">🚀 Retención: <strong class="text-brand-900">+380%</strong></span>
              <span class="text-emerald-600 font-bold flex items-center gap-1" data-i18n="hero_card_ready">
                <span>✔</span> 1-Clic Aprobar
              </span>
            </div>
          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 3. MARQUEE CLARO & ENFOCADO EN CONFIANZA Y BENEFICIOS -->
  <!-- ========================================================================= -->
  <section class="py-5 sm:py-6 bg-slate-900 border-b border-slate-800 overflow-hidden text-white">
    <div class="max-w-7xl mx-auto px-4 mb-2.5 text-center">
      <p data-i18n="marquee_title" class="text-[10px] sm:text-[11px] font-extrabold uppercase tracking-[0.2em] text-slate-400">
        Infraestructura Oficial, Seguridad y Control Total
      </p>
    </div>

    <div class="relative overflow-hidden w-full select-none">
      <div class="animate-marquee items-center gap-6 sm:gap-10 font-sans text-[11px] sm:text-xs font-bold text-slate-300">
        
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-purple-400">📸</span> Meta Graph API Oficial (Instagram & Facebook)
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-emerald-400">🛡️</span> 100% Control Humano con Modo Copilot
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-indigo-400">🏢</span> Separación y Aislamiento Total de Marcas
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-amber-400">⚡</span> Respuestas en la Ventana de Oro del Algoritmo
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-cyan-400">🔒</span> Conexión Cifrada y Segura de Extremo a Extremo
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-emerald-400">🎯</span> Detección Automática de Consultas Comerciales
        </span>
        
        <!-- Loop duplicate -->
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-purple-400">📸</span> Meta Graph API Oficial (Instagram & Facebook)
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-emerald-400">🛡️</span> 100% Control Humano con Modo Copilot
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-indigo-400">🏢</span> Separación y Aislamiento Total de Marcas
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-amber-400">⚡</span> Respuestas en la Ventana de Oro del Algoritmo
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-cyan-400">🔒</span> Conexión Cifrada y Segura de Extremo a Extremo
        </span>
        <span class="flex items-center gap-2 shrink-0 px-3.5 py-1.5 rounded-full bg-slate-800/80 border border-slate-700">
          <span class="text-emerald-400">🎯</span> Detección Automática de Consultas Comerciales
        </span>

      </div>
    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 4. QUÉ PUEDES HACER CON XINDRO (MATRIZ DE FUNCIONES Y BENEFICIOS) -->
  <!-- ========================================================================= -->
  <section id="funciones" class="py-16 sm:py-24 bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
        <span data-i18n="feat_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Funciones y Beneficios Claros
        </span>
        <h2 data-i18n="feat_h2" class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-midnight tracking-tight mt-3 mb-3 sm:mb-4">
          Qué puedes hacer con XINDRO
        </h2>
        <p data-i18n="feat_sub" class="text-sm sm:text-lg text-slate-600 font-normal">
          Todo lo que necesitas para centralizar tus redes sociales, responder a tus seguidores con precisión y convertir comentarios en ventas.
        </p>
      </div>

      <!-- Feature-Benefit Grid (5 Key Pillars) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
        
        <!-- Card 1: Comentarios de Instagram y Facebook en un solo panel -->
        <div class="feature-matrix-card rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 flex flex-col justify-between hover:border-brand-300 hover:shadow-elevated-card">
          <div>
            <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl font-bold mb-4">
              💬
            </div>
            <h3 data-i18n="feat1_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Comentarios de Instagram y Facebook en un panel
            </h3>
            <p data-i18n="feat1_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
              <strong class="text-slate-800">Beneficio:</strong> Revisa y responde todas tus conversaciones en un solo lugar sin cambiar de cuenta constantemente ni perder mensajes importantes.
            </p>
          </div>
          <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] font-bold text-brand-700">
            <span data-i18n="feat1_status">✔ Meta Graph API Oficial Activa</span>
            <span class="text-slate-400">IG & FB</span>
          </div>
        </div>

        <!-- Card 2: Identificación de consultas comerciales -->
        <div class="feature-matrix-card rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 flex flex-col justify-between hover:border-brand-300 hover:shadow-elevated-card">
          <div>
            <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl font-bold mb-4">
              🎯
            </div>
            <h3 data-i18n="feat2_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Identificación de consultas comerciales
            </h3>
            <p data-i18n="feat2_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
              <strong class="text-slate-800">Beneficio:</strong> Encuentra de inmediato a quienes preguntan por precios, disponibilidad o servicios para cerrar ventas antes de que se enfríe el interés.
            </p>
          </div>
          <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] font-bold text-emerald-700">
            <span data-i18n="feat2_status">✔ Detección de Leads en &lt;180ms</span>
            <span class="text-slate-400">Score de Intención</span>
          </div>
        </div>

        <!-- Card 3: Sugerencias con voz de marca -->
        <div class="feature-matrix-card rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 flex flex-col justify-between hover:border-brand-300 hover:shadow-elevated-card">
          <div>
            <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-bold mb-4">
              🧠
            </div>
            <h3 data-i18n="feat3_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Sugerencias con voz de marca calibrada
            </h3>
            <p data-i18n="feat3_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
              <strong class="text-slate-800">Beneficio:</strong> Dedica hasta un 80% menos de tiempo a redactar respuestas repetitivas manteniendo el estilo, calidez y vocabulario exacto de tu marca.
            </p>
          </div>
          <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] font-bold text-blue-700">
            <span data-i18n="feat3_status">✔ Calibrador Multi-Tono & Anti-Alucinación</span>
            <span class="text-slate-400">Cero Fluff</span>
          </div>
        </div>

        <!-- Card 4: Revisión antes de publicar (Modo Copilot) -->
        <div class="feature-matrix-card rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 flex flex-col justify-between hover:border-brand-300 hover:shadow-elevated-card">
          <div>
            <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-2xl font-bold mb-4">
              🛡️
            </div>
            <h3 data-i18n="feat4_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Revisión antes de publicar (Modo Copilot)
            </h3>
            <p data-i18n="feat4_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
              <strong class="text-slate-800">Beneficio:</strong> Mantén control humano total sobre lo que dice tu marca. Revisa, edita o aprueba las sugerencias con 1 solo clic antes de que se envíen.
            </p>
          </div>
          <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] font-bold text-amber-700">
            <span data-i18n="feat4_status">✔ 100% Supervisión Humana Garantizada</span>
            <span class="text-slate-400">1-Clic</span>
          </div>
        </div>

        <!-- Card 5: Gestión por marca y multi-cliente -->
        <div class="feature-matrix-card rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 flex flex-col justify-between hover:border-brand-300 hover:shadow-elevated-card">
          <div>
            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl font-bold mb-4">
              🏢
            </div>
            <h3 data-i18n="feat5_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Gestión por marca y multi-cliente
            </h3>
            <p data-i18n="feat5_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
              <strong class="text-slate-800">Beneficio:</strong> Organiza y aísla el trabajo de distintos clientes o proyectos con perfiles de tono, catálogos y accesos totalmente independientes.
            </p>
          </div>
          <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] font-bold text-indigo-700">
            <span data-i18n="feat5_status">✔ Disponible en Plan Agencia</span>
            <span class="text-slate-400">Aislamiento Total</span>
          </div>
        </div>

        <!-- Card 6: Smart Timing Predictivo -->
        <div class="feature-matrix-card rounded-2xl bg-slatecard border border-slate-200/90 p-6 sm:p-7 flex flex-col justify-between hover:border-brand-300 hover:shadow-elevated-card">
          <div>
            <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-2xl font-bold mb-4">
              ⏰
            </div>
            <h3 data-i18n="feat6_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
              Smart Timing de Publicación
            </h3>
            <p data-i18n="feat6_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-4">
              <strong class="text-slate-800">Beneficio:</strong> Descubre los picos exactos de actividad y guardados de tu audiencia para publicar cuando el algoritmo te dará el máximo alcance orgánico.
            </p>
          </div>
          <div class="pt-3 border-t border-slate-200 flex items-center justify-between text-[11px] font-bold text-rose-700">
            <span data-i18n="feat6_status">✔ Algoritmo Predictivo de Audiencia</span>
            <span class="text-slate-400">+142% Alcance</span>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 5. CÓMO EMPEZAR EN TRES PASOS -->
  <!-- ========================================================================= -->
  <section id="como-empezar" class="py-16 sm:py-24 bg-slatecard border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
        <span data-i18n="steps_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Sin Complicaciones
        </span>
        <h2 data-i18n="steps_h2" class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-midnight tracking-tight mt-3 mb-3">
          Cómo empezar en 3 sencillos pasos
        </h2>
        <p data-i18n="steps_sub" class="text-sm sm:text-lg text-slate-600 font-normal">
          Configuración rápida en menos de 2 minutos. Sin instalaciones complejas ni contraseñas.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
        
        <!-- Step 1 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-sm hover:shadow-elevated-card transition-all text-center flex flex-col items-center group">
          <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-600 border border-blue-200/70 flex items-center justify-center text-3xl mb-5 relative group-hover:scale-110 transition-transform">
            <span>🔗</span>
            <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full step-number-badge text-white text-xs font-black flex items-center justify-center shadow-md">1</span>
          </div>
          <h3 data-i18n="step1_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
            1. Conecta tus Cuentas
          </h3>
          <p data-i18n="step1_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Inicia sesión con Meta OAuth oficial de forma segura. Selecciona las cuentas de Instagram y Facebook que deseas gestionar con permisos autorizados.
          </p>
        </div>

        <!-- Step 2 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-sm hover:shadow-elevated-card transition-all text-center flex flex-col items-center group">
          <div class="w-16 h-16 rounded-2xl bg-purple-50 text-purple-600 border border-purple-200/70 flex items-center justify-center text-3xl mb-5 relative group-hover:scale-110 transition-transform">
            <span>🎭</span>
            <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full step-number-badge text-white text-xs font-black flex items-center justify-center shadow-md">2</span>
          </div>
          <h3 data-i18n="step2_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
            2. Configura tu Voz y Datos
          </h3>
          <p data-i18n="step2_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Define los datos de tu negocio (productos, precios, FAQs) y calibra el tono de comunicación: empático, mentor o comercial de alto valor.
          </p>
        </div>

        <!-- Step 3 -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-6 sm:p-8 shadow-sm hover:shadow-elevated-card transition-all text-center flex flex-col items-center group">
          <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-200/70 flex items-center justify-center text-3xl mb-5 relative group-hover:scale-110 transition-transform">
            <span>🚀</span>
            <span class="absolute -top-2 -right-2 w-7 h-7 rounded-full step-number-badge text-white text-xs font-black flex items-center justify-center shadow-md">3</span>
          </div>
          <h3 data-i18n="step3_t" class="text-base sm:text-lg font-bold text-midnight mb-2">
            3. Revisa y Publica con Copilot
          </h3>
          <p data-i18n="step3_d" class="text-xs sm:text-sm text-slate-600 leading-relaxed">
            Recibe sugerencias instantáneas fundamentadas en tus datos. Aprueba con 1 clic o deja que el piloto automático trabaje por ti con total seguridad.
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 6. EL SIMULADOR INTERACTIVO CON CONTEXTO DE NEGOCIO GROUNDED -->
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
          Observa cómo la IA utiliza los datos de un negocio de ejemplo para responder con veracidad, tono humano y cero alucinaciones.
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
            <span data-i18n="sim_engine_status">Motor Anti-Alucinación Anclado a Datos Reales</span>
          </div>
        </div>

        <div class="p-5 sm:p-8">
          
          <!-- Explicit Sample Business Context Card -->
          <div class="sim-context-box rounded-xl p-3.5 sm:p-4 mb-5 text-xs text-slate-800 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-start gap-2.5">
              <span class="text-lg">💼</span>
              <div>
                <div class="font-bold text-slate-900 flex items-center gap-2">
                  <span data-i18n="sim_ctx_title">Negocio de Ejemplo: "Academia Stoic Pro"</span>
                  <span class="px-2 py-0.5 rounded-full bg-brand-100 text-brand-800 text-[10px] font-bold">Contexto Activo</span>
                </div>
                <div class="text-[11px] text-slate-600 mt-0.5" data-i18n="sim_ctx_desc">
                  <strong>Producto:</strong> Curso de Hábitos y Mentalidad (40h de clases grabadas, acceso vitalicio, comunidad privada). <strong>Precio:</strong> 149 € (pago único con enlace en bio).
                </div>
              </div>
            </div>
            <div class="text-[10px] font-semibold text-brand-700 bg-white/80 px-2.5 py-1 rounded-lg border border-brand-200 shrink-0">
              🛡️ Respuestas 100% Fundamentadas
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4 mb-5 sm:mb-6">
            
            <div>
              <label data-i18n="sim_lbl_tone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5 sm:mb-2">
                1. Tono de Marca:
              </label>
              <select id="sim-tone" class="sim-select-custom w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 sm:py-3 text-xs sm:text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer shadow-sm">
                <option value="mentor" selected data-i18n="sim_opt_mentor">🏛️ Estoico / Mentor Sabio</option>
                <option value="empathy" data-i18n="sim_opt_empathy">🤝 Cercano & Empático</option>
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
              <textarea id="sim-input-text" rows="3" class="w-full bg-slatecard border border-slate-300 rounded-xl p-3.5 sm:p-4 text-xs sm:text-sm font-medium text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all resize-none" placeholder="Escribe un comentario...">¿El curso incluye clases grabadas y cuánto tiempo tengo acceso?</textarea>
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
              <span data-i18n="sim_presets_title" class="text-xs font-bold text-slate-500 mr-1">Probar ejemplos:</span>
              <button type="button" onclick="Simulator.loadPreset(1)" data-i18n="sim_preset_1" class="preset-pill-btn text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 border border-slate-200/80">
                📚 "¿Clases grabadas y acceso?"
              </button>
              <button type="button" onclick="Simulator.loadPreset(2)" data-i18n="sim_preset_2" class="preset-pill-btn text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 border border-slate-200/80">
                🏛️ "Dicotomía del control"
              </button>
              <button type="button" onclick="Simulator.loadPreset(3)" data-i18n="sim_preset_3" class="preset-pill-btn text-[11px] sm:text-xs font-semibold px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 border border-slate-200/80">
                💎 "¿Precio del curso?"
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
                  🎯 Intención: Lead Calificado • Modalidad del Curso
                </span>
              </div>
              <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 sm:gap-3 text-[11px] sm:text-xs font-semibold text-slate-500 ml-auto sm:ml-0">
                <span class="flex items-center gap-1">
                  <span data-i18n="sim_score_label">Prioridad Comercial:</span> 
                  <strong id="sim-badge-score" class="text-brand-600 font-bold">96/100</strong>
                  <span class="text-[10px] text-slate-400 font-normal" data-i18n="sim_score_explain">(Consulta de Compra)</span>
                </span>
                <span class="text-emerald-600 font-bold">⚡ 120ms</span>
              </div>
            </div>

            <p id="sim-output-text" class="text-xs sm:text-base text-slate-800 font-medium leading-relaxed mb-4">
              "¡Hola! Sí, el curso incluye acceso vitalicio a todas las clases grabadas (más de 40 horas de formación práctica) para que estudies con total flexibilidad a tu propio ritmo. Tienes el temario completo e inscripción directa en el enlace de nuestra biografía. ¿Tienes alguna duda puntual sobre el programa? 🏛️"
            </p>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-200/60 text-xs text-slate-500">
              <span id="sim-badge-autopilot" data-i18n="sim_autopilot_ok" class="flex items-center gap-1.5 text-emerald-600 font-semibold text-[11px] sm:text-xs">
                <span>✔</span> Respuesta verificada y fundamentada en los datos del negocio (Sin alucinaciones)
              </span>
              <button type="button" onclick="Simulator.copyResponse()" id="sim-btn-copy" class="copy-btn-action text-xs font-bold text-brand-600 hover:text-brand-800 flex items-center gap-1 ml-auto">
                <span data-i18n="sim_btn_copy">📋 Copiar</span>
              </button>
            </div>

          </div>

        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 7. SOCIAL PROOF & CALCULADORA INTERACTIVA DE AHORRO & ROI -->
  <!-- ========================================================================= -->
  <section id="calculadora-roi" class="py-16 sm:py-24 bg-gradient-to-b from-slatecard to-white border-b border-slate-200/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Metrics Bar -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 md:gap-8 text-center divide-x-0 md:divide-x divide-slate-200/80 mb-14 sm:mb-18 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        
        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-midnight tracking-tight mb-1">
            +15 hrs<span class="text-brand-600">/mes</span>
          </div>
          <p data-i18n="stat_1" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Tiempo Manual Ahorrado
          </p>
        </div>

        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-brand-600 tracking-tight mb-1">
            100%
          </div>
          <p data-i18n="stat_2" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Control y Aprobación Humana
          </p>
        </div>

        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-midnight tracking-tight mb-1">
            0 leads
          </div>
          <p data-i18n="stat_3" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Oportunidades de Compra Perdidas
          </p>
        </div>

        <div class="p-2 sm:p-3 min-w-0">
          <div class="text-2xl sm:text-3xl md:text-4xl font-black text-emerald-600 tracking-tight mb-1">
            &lt; 2.5 min
          </div>
          <p data-i18n="stat_4" class="text-[10px] sm:text-xs md:text-sm font-semibold text-slate-500 uppercase tracking-wider leading-tight break-words">
            Tiempo Promedio de Respuesta
          </p>
        </div>

      </div>

      <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
        <span data-i18n="calc_badge" class="text-xs font-extrabold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3.5 py-1 rounded-full border border-emerald-200">
          Calculadora de Impacto
        </span>
        <h2 data-i18n="calc_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          ¿Cuánto tiempo y clientes potenciales ganas con Xindro?
        </h2>
        <p data-i18n="calc_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Ajusta tu volumen mensual y descubre el ahorro real y la retención generada.
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

            <div class="text-[11px] text-slate-500 bg-slate-50 p-3 rounded-xl border border-slate-200" data-i18n="calc_assumptions">
              ℹ️ <strong>Supuestos del cálculo:</strong> 45 segundos de redacción manual por comentario vs. 5 segundos con Copilot IA, y 2.4% promedio de consultas con intención comercial.
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
  <!-- 8. ¿POR QUÉ ELEGIR XINDRO? (6 Pilares Diferenciales) -->
  <!-- ========================================================================= -->
  <section id="por-que-xindro" class="py-16 sm:py-24 bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-3xl mx-auto mb-12 sm:mb-16">
        <span data-i18n="why_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Diferenciales Reales
        </span>
        <h2 data-i18n="why_h2" class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-midnight tracking-tight mt-3 mb-3 sm:mb-4">
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
              El algoritmo de Meta premia a las cuentas que interactúan en los primeros 15 minutos. Nuestro motor responde casi al instante.
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
              Ahorra +15 Horas de Trabajo al Mes
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
  <!-- 9. PRECIOS & PLANES TRANSPARENTES EN EUROS -->
  <!-- ========================================================================= -->
  <section id="precios" class="py-16 sm:py-24 bg-slatecard border-b border-slate-200/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-12 sm:mb-16">
        <span data-i18n="price_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Planes Transparentes
        </span>
        <h2 data-i18n="price_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          Comienza gratis y escala con tu comunidad.
        </h2>
        <p data-i18n="price_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Sin contratos forzosos ni permanencia. Facturación clara en Euros. Cancela en cualquier momento.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 sm:gap-6 items-stretch mb-8">
        
        <!-- Plan 1: Inicial (Gratis) -->
        <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200 p-5 sm:p-6 flex flex-col justify-between hover:shadow-subtle-card hover:border-slate-300 transition-all">
          <div>
            <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider mb-1">Entrada</div>
            <h3 data-i18n="plan1_t" class="text-base sm:text-lg font-bold text-midnight mb-1">Plan Inicial</h3>
            <p data-i18n="plan1_d" class="text-xs text-slate-500 mb-5 leading-relaxed">Para probar el motor y dar los primeros pasos.</p>
            <div class="flex items-baseline gap-1 mb-5">
              <span class="text-3xl sm:text-4xl font-black text-midnight">0 €</span>
              <span data-i18n="plan1_p" class="text-xs text-slate-500 font-bold">/ mes gratis</span>
            </div>
            <ul class="space-y-2.5 text-xs text-slate-600 font-medium mb-6">
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f1">1 cuenta conectada (IG o FB)</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f2">50 respuestas / tokens al mes</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f3">1 perfil de voz de marca</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f4">Asistente Copilot con revisión</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan1_f5">Soporte estándar</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan1_btn" class="w-full py-2.5 sm:py-3 rounded-xl text-center text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors shadow-sm">
            Comenzar Gratis
          </a>
        </div>

        <!-- Plan 2: Creador (Económico) -->
        <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200 p-5 sm:p-6 flex flex-col justify-between hover:shadow-subtle-card hover:border-brand-300 transition-all">
          <div>
            <div class="text-xs font-extrabold text-brand-600 uppercase tracking-wider mb-1">Económico</div>
            <h3 data-i18n="plan2_t" class="text-base sm:text-lg font-bold text-midnight mb-1">Plan Creador</h3>
            <p data-i18n="plan2_d" class="text-xs text-slate-500 mb-5 leading-relaxed">Para creadores individuales y marcas personales.</p>
            <div class="flex items-baseline gap-1 mb-5">
              <span class="text-3xl sm:text-4xl font-black text-midnight">9.99 €</span>
              <span data-i18n="plan2_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-2.5 text-xs text-slate-600 font-medium mb-6">
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan2_f1">2 canales (Instagram y Facebook)</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan2_f2">500 respuestas / tokens al mes</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan2_f3">1 perfil de voz de marca avanzado</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan2_f4">Ventana de oro del algoritmo</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan2_f5">Soporte prioritario por email</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan2_btn" class="w-full py-2.5 sm:py-3 rounded-xl text-center text-xs sm:text-sm font-bold text-brand-700 bg-brand-50 border border-brand-200 hover:bg-brand-100 transition-colors shadow-sm">
            Elegir Plan Creador
          </a>
        </div>

        <!-- Plan 3: Pro / Negocio (⭐ Featured Anchor) -->
        <div class="rounded-2xl sm:rounded-3xl bg-white border-2 border-brand-500 p-5 sm:p-6 flex flex-col justify-between shadow-glow-sm relative">
          <div data-i18n="plan3_badge" class="absolute -top-3 left-1/2 -translate-x-1/2 bg-gradient-to-r from-brand-600 to-indigo-600 text-white text-[10px] sm:text-[11px] font-extrabold uppercase tracking-wider px-3 py-0.5 rounded-full shadow-sm whitespace-nowrap">
            ⭐ Más Recomendado
          </div>
          <div>
            <div class="text-xs font-extrabold text-brand-700 uppercase tracking-wider mb-1">Recomendado</div>
            <h3 data-i18n="plan3_t" class="text-base sm:text-lg font-bold text-midnight mb-1">Pro / Negocio</h3>
            <p data-i18n="plan3_d" class="text-xs text-slate-500 mb-5 leading-relaxed">Para negocios y creadores que monetizan.</p>
            <div class="flex items-baseline gap-1 mb-5">
              <span class="text-3xl sm:text-4xl font-black text-brand-600">20.99 €</span>
              <span data-i18n="plan3_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-2.5 text-xs text-slate-700 font-medium mb-6">
              <li class="flex items-start gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan3_f1">Hasta 5 cuentas conectadas</span></li>
              <li class="flex items-start gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan3_f2">2.500 respuestas / tokens al mes</span></li>
              <li class="flex items-start gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan3_f3">Hasta 3 perfiles de marca</span></li>
              <li class="flex items-start gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan3_f4">Detección avanzada de leads</span></li>
              <li class="flex items-start gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan3_f5">Smart Timing para horarios óptimos</span></li>
              <li class="flex items-start gap-2"><span class="text-brand-600 font-bold">✔</span> <span data-i18n="plan3_f6">Soporte prioritario 24/7</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan3_btn" class="w-full py-2.5 sm:py-3 rounded-xl text-center text-xs sm:text-sm font-bold text-white gradient-button shadow-glow-sm shimmer-btn">
            Comenzar con Pro
          </a>
        </div>

        <!-- Plan 4: Agencia -->
        <div class="rounded-2xl sm:rounded-3xl bg-white border border-slate-200 p-5 sm:p-6 flex flex-col justify-between hover:shadow-subtle-card hover:border-slate-300 transition-all">
          <div>
            <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider mb-1">Escala</div>
            <h3 data-i18n="plan4_t" class="text-base sm:text-lg font-bold text-midnight mb-1">Plan Agencia</h3>
            <p data-i18n="plan4_d" class="text-xs text-slate-500 mb-5 leading-relaxed">Para agencias y gestión multi-cliente.</p>
            <div class="flex items-baseline gap-1 mb-5">
              <span class="text-3xl sm:text-4xl font-black text-midnight">79.99 €</span>
              <span data-i18n="plan4_p" class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-2.5 text-xs text-slate-600 font-medium mb-6">
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan4_f1">Hasta 20 cuentas conectadas</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan4_f2">10.000 respuestas / tokens al mes</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan4_f3">Hasta 20 marcas independientes</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan4_f4">Aislamiento total de marcas y datos</span></li>
              <li class="flex items-start gap-2"><span class="text-emerald-500 font-bold">✔</span> <span data-i18n="plan4_f5">Soporte dedicado & Onboarding</span></li>
            </ul>
          </div>
          <a href="login.php" data-i18n="plan4_btn" class="w-full py-2.5 sm:py-3 rounded-xl text-center text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors shadow-sm">
            Acceso para Agencias
          </a>
        </div>

      </div>

      <!-- Token Limit Clarity Box -->
      <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 text-xs text-slate-700 max-w-4xl mx-auto shadow-sm">
        <div class="flex items-start gap-3">
          <span class="text-xl">💡</span>
          <div>
            <h4 class="font-bold text-slate-900 mb-1" data-i18n="price_faq_box_title">¿Qué ocurre al alcanzar el límite de tokens mensuales de tu plan?</h4>
            <p class="text-slate-600 leading-relaxed" data-i18n="price_faq_box_desc">
              Tu panel de comentarios sigue <strong>100% operativo</strong> para responder y gestionar tu comunidad de forma manual. Si deseas continuar usando las sugerencias automáticas de IA, puedes añadir recargas de tokens o mejorar tu plan en 1 solo clic sin interrupción de servicio ni cargos ocultos.
            </p>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 10. PREGUNTAS FRECUENTES (FAQ Acordeón Interactivo) -->
  <!-- ========================================================================= -->
  <section id="faq" class="py-16 sm:py-24 bg-white border-b border-slate-200/80">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-10 sm:mb-14">
        <span data-i18n="faq_badge" class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Respuestas Claras
        </span>
        <h2 data-i18n="faq_h2" class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-2 sm:mb-3">
          Preguntas Frecuentes
        </h2>
        <p data-i18n="faq_sub" class="text-xs sm:text-base text-slate-600 font-normal">
          Todo lo que necesitas saber antes de empezar a automatizar tu comunidad con total confianza.
        </p>
      </div>

      <div class="space-y-3 sm:space-y-4">
        
        <!-- Q1 -->
        <div class="bg-slatecard rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(1)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q1">¿Es seguro para mi cuenta de Instagram o Facebook?</span>
            <span id="faq-icon-1" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-1" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a1">
            Totalmente seguro. Xindro opera exclusivamente a través de la API oficial de Meta Graph con permisos autorizados y webhooks verificados. No requerimos tu contraseña de Instagram y no utilizamos navegadores automatizados o emuladores no oficiales.
          </div>
        </div>

        <!-- Q2 -->
        <div class="bg-slatecard rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(2)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q2">¿La IA puede inventar información o hacer afirmaciones no realizadas?</span>
            <span id="faq-icon-2" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-2" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a2">
            No. La IA responde anclada estrictamente a la información de tu negocio, catálogo de productos y lineamientos que configures en tu panel. Si una consulta requiere datos internos no registrados (como números de pedido o reembolsos personalizados), el sistema lo reconoce y orienta amablemente hacia el enlace oficial de la bio o mensaje privado. Además, con el <strong>Modo Copilot</strong> cuentas con aprobación humana en 1 clic antes de publicar cada sugerencia.
          </div>
        </div>

        <!-- Q3 -->
        <div class="bg-slatecard rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(3)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q3">¿Cuál es la diferencia entre cuenta conectada, marca y cliente?</span>
            <span id="faq-icon-3" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-3" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a3">
            Una <strong>cuenta conectada</strong> es un canal individual de Instagram o Página de Facebook. Una <strong>marca</strong> es la configuración de tono, catálogo y contexto de negocio. En el <strong>Plan Agencia</strong>, puedes gestionar hasta 20 marcas o clientes de forma totalmente aislada, cada una con su propia voz, productos y reportes.
          </div>
        </div>

        <!-- Q4 -->
        <div class="bg-slatecard rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden">
          <button type="button" onclick="Faq.toggle(4)" class="w-full p-4 sm:p-6 text-left flex items-center justify-between gap-4 font-bold text-xs sm:text-base text-midnight hover:text-brand-600 transition-colors">
            <span data-i18n="faq_q4">¿Puedo empezar gratis sin ingresar tarjeta de crédito?</span>
            <span id="faq-icon-4" class="text-lg font-bold text-slate-400 transition-transform">+</span>
          </button>
          <div id="faq-ans-4" class="hidden px-4 sm:px-6 pb-5 sm:pb-6 text-xs sm:text-sm text-slate-600 leading-relaxed border-t border-slate-100 pt-4" data-i18n="faq_a4">
            Sí. El plan Inicial es 100% gratuito e incluye hasta 50 respuestas de prueba al mes y el asistente Copilot para que puedas comprobar el impacto en tu comunidad antes de decidir actualizar.
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 11. FINAL CTA BANNER -->
  <!-- ========================================================================= -->
  <section class="py-14 sm:py-20 bg-gradient-to-br from-brand-600 via-indigo-600 to-purple-800 text-white text-center relative overflow-hidden">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 relative z-10">
      <h2 class="text-2xl sm:text-4xl md:text-5xl font-extrabold mb-4 tracking-tight" data-i18n="final_cta_h2">
        Empieza a transformar tus comentarios en clientes hoy mismo
      </h2>
      <p class="text-sm sm:text-lg text-brand-100 mb-8 max-w-2xl mx-auto font-normal" data-i18n="final_cta_sub">
        Únete a creadores y agencias que ahorran más de 15 horas al mes y no pierden ni una sola oportunidad de venta.
      </p>
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3 sm:gap-4">
        <a href="login.php" class="w-full sm:w-auto px-8 py-4 rounded-xl text-sm sm:text-base font-bold text-brand-900 bg-white hover:bg-slate-100 shadow-xl transition-all shimmer-btn">
          <span data-i18n="final_cta_btn">Comenzar Gratis (Sin Tarjeta)</span> →
        </a>
      </div>
    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 12. FOOTER PROFESIONAL CON MARCA DE AGUA COMPLETA Y ENLACES LIMPIOS -->
  <!-- ========================================================================= -->
  <footer class="starry-footer-bg starry-overlay pt-14 sm:pt-16 pb-12 text-slate-200 text-sm overflow-hidden relative border-t border-slate-800/80">
    
    <!-- Giant Responsive Watermark -->
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
            El sistema operativo de IA para creadores y agencias de redes sociales. Respuestas en tiempo real, Smart Timing y API oficial de Meta.
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
            <li><a href="#funciones" data-i18n="nav_products" class="hover:text-white hover:underline transition-colors">Funciones y Beneficios</a></li>
            <li><a href="#como-empezar" data-i18n="nav_steps" class="hover:text-white hover:underline transition-colors">Cómo empezar</a></li>
            <li><a href="#simulador" data-i18n="foot_c2_2" class="hover:text-white hover:underline transition-colors">Simulador en Vivo</a></li>
            <li><a href="#calculadora-roi" data-i18n="nav_roi" class="hover:text-white hover:underline transition-colors">Calculadora de Ahorro</a></li>
            <li><a href="#precios" data-i18n="foot_c2_1" class="hover:text-white hover:underline transition-colors">Precios y Planes</a></li>
          </ul>
        </div>

        <!-- Col 3: Empresa -->
        <div>
          <h4 data-i18n="foot_c3_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Empresa</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#por-que-xindro" data-i18n="foot_c3_1" class="hover:text-white hover:underline transition-colors">¿Por qué Xindro?</a></li>
            <li><a href="#faq" data-i18n="nav_faq" class="hover:text-white hover:underline transition-colors">Preguntas Frecuentes</a></li>
            <li><a href="privacy-policy.php" data-i18n="foot_c3_7" class="hover:text-white hover:underline transition-colors">Seguridad y Privacidad</a></li>
            <li><a href="data-deletion.php" class="hover:text-white hover:underline transition-colors">Eliminación de Datos (Meta)</a></li>
          </ul>
        </div>

        <!-- Col 4: Redes sociales -->
        <div>
          <h4 data-i18n="foot_c4_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Redes sociales</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="hover:text-white hover:underline transition-colors">Instagram</a></li>
            <li><a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" class="hover:text-white hover:underline transition-colors">LinkedIn</a></li>
            <li><a href="https://tiktok.com" target="_blank" rel="noopener noreferrer" class="hover:text-white hover:underline transition-colors">TikTok</a></li>
            <li><a href="https://x.com" target="_blank" rel="noopener noreferrer" class="hover:text-white hover:underline transition-colors">X (Twitter)</a></li>
          </ul>
        </div>

        <!-- Col 5: Información legal -->
        <div>
          <h4 data-i18n="foot_c5_t" class="text-xs font-extrabold text-white uppercase tracking-wider mb-4">Información legal</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="terms-of-service.php" class="hover:text-white hover:underline transition-colors">Términos de Servicio</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white hover:underline transition-colors">Política de Privacidad</a></li>
            <li><a href="javascript:void(0)" onclick="CookieConsent.openSettings()" data-i18n="cookie_btn_pref" class="hover:text-white hover:underline transition-colors text-brand-300 font-semibold">Preferencias de cookies</a></li>
            <li><a href="data-deletion.php" class="hover:text-white hover:underline transition-colors">Instrucciones de Eliminación</a></li>
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
        const commentsInput = document.getElementById('calc-comments-range');
        const accountsInput = document.getElementById('calc-accounts-range');
        const commentsVal = document.getElementById('calc-comments-val');
        const accountsVal = document.getElementById('calc-accounts-val');
        const resHours = document.getElementById('calc-res-hours');
        const resLeads = document.getElementById('calc-res-leads');

        if (!commentsInput || !accountsInput) return;

        const comments = parseInt(commentsInput.value, 10) || 5000;
        const accounts = parseInt(accountsInput.value, 10) || 2;
        const lang = I18n.current || 'es';

        const commentsFormatted = comments.toLocaleString();
        const commentsUnit = lang === 'en' ? 'comments' : (lang === 'pt' ? 'comentários' : 'comentarios');
        const accountsUnit = accounts === 1
          ? (lang === 'en' ? 'account' : (lang === 'pt' ? 'conta' : 'cuenta'))
          : (lang === 'en' ? 'accounts' : (lang === 'pt' ? 'contas' : 'cuentas'));

        if (commentsVal) commentsVal.textContent = `${commentsFormatted} ${commentsUnit}`;
        if (accountsVal) accountsVal.textContent = `${accounts} ${accountsUnit}`;

        // 45s manual vs 5s copilot = 40s saved per comment -> (comments * 40 / 3600) hours
        const hoursSaved = Math.max(1, Math.round((comments * 40) / 3600));
        // ~2.4% inquiries have commercial intention
        const leads = Math.max(1, Math.round(comments * 0.024));

        if (resHours) resHours.textContent = `+${hoursSaved} hrs`;
        if (resLeads) resLeads.textContent = `+${leads}`;
      }
    };

    // 2. FAQ ACCORDION ENGINE
    const Faq = {
      toggle(id) {
        const ans = document.getElementById('faq-ans-' + id);
        const icon = document.getElementById('faq-icon-' + id);
        if (!ans) return;

        const isCurrentlyHidden = ans.classList.contains('hidden');

        for (let i = 1; i <= 6; i++) {
          const a = document.getElementById('faq-ans-' + i);
          const ic = document.getElementById('faq-icon-' + i);
          if (a) a.classList.add('hidden');
          if (ic) {
            ic.textContent = '+';
            ic.style.transform = 'rotate(0deg)';
          }
        }

        if (isCurrentlyHidden) {
          ans.classList.remove('hidden');
          if (icon) {
            icon.textContent = '−';
            icon.style.transform = 'rotate(180deg)';
          }
        }
      }
    };

    // 3. DICCIONARIO MULTI-IDIOMA (ES / EN / PT)
    const I18n = {
      current: '<?= $initialLang ?>',
      dict: {
        es: {
          page_title: "XINDRO — El Sistema Operativo de IA para Creadores de Contenido",
          page_desc: "Convierte los comentarios de Instagram y Facebook en conversaciones que hacen crecer tu negocio con IA contextualizada.",
          nav_products: "Funciones",
          nav_steps: "Cómo empezar",
          nav_why: "¿Por qué Xindro?",
          nav_simulator: "Simulador",
          nav_roi: "Calculadora",
          nav_pricing: "Precios",
          nav_faq: "FAQ",
          nav_login: "Iniciar sesión",
          nav_cta: "Comienza gratis",
          nav_dashboard: "Ir a mi Panel",
          hero_badge: "El sistema operativo de IA para creadores de contenido y marcas",
          hero_h1_p1: "Convierte los comentarios de Instagram y Facebook",
          hero_h1_p2: "en conversaciones que hacen crecer tu negocio.",
          hero_sub: "Reúne tus comentarios, identifica consultas de compra y prepara respuestas con el estilo de tu marca. Revisa las sugerencias antes de publicar y dedica más tiempo a crear y atender oportunidades.",
          hero_cta_sim: "Probar una respuesta gratis",
          hero_cta_calc: "Qué puedes hacer",
          hero_card_title: "Flujo en Tiempo Real: Comentario ➔ Detección de Intención ➔ Sugerencia ➔ Aprobación Humana",
          hero_card_status: "Meta Graph API Oficial",
          hero_card_time: "Instagram • Hace 2 seg",
          hero_card_intent: "🎯 Intención: <strong class=\"text-brand-600\">Pregunta de Mentoría</strong>",
          hero_card_calibrated: "Voz Calibrada",
          hero_card_bot_reply: "🤖 Sugerencia con Voz de Marca",
          hero_card_tone: "Mentor Empático",
          hero_card_retention: "🚀 Retención: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Listo para postear",
          hero_comment_sample: "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?",
          hero_reply_sample: "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇",
          marquee_title: "Integrado con la Infraestructura Oficial de Redes Sociales e Inteligencia Artificial",
          feat_badge: "Capacidades de la Plataforma",
          feat_h2: "¿Qué puedes hacer con XINDRO?",
          feat_sub: "Un ecosistema integral diseñado para creadores y marcas que buscan monetizar su comunidad sin perder autenticidad ni control.",
          feat1_t: "1. Bandeja Unificada Multicanal",
          feat1_d: "Centraliza todas las interacciones de Instagram y Facebook en una sola bandeja en tiempo real sin cambiar de pestaña.",
          feat1_status: "✔ Meta Graph API Oficial Activa",
          feat2_t: "2. Detección Inteligente de Intención",
          feat2_d: "Identifica al instante dudas de compra, preguntas filosóficas, felicitaciones o reclamos para priorizar lo que genera ingresos.",
          feat2_status: "✔ Detección de Leads en <180ms",
          feat3_t: "3. Voz de Marca Calibrada",
          feat3_d: "Entrena a la IA con los datos de tu negocio (cursos, precios, políticas) y ajusta el tono para respuestas 100% auténticas.",
          feat3_status: "✔ Calibrador Multi-Tono & Anti-Alucinación",
          feat4_t: "4. Modo Copilot con Revisión Humana",
          feat4_d: "Tú tienes el control total. Revisa, edita con un clic o aprueba sugerencias antes de que se publiquen en tus redes.",
          feat4_status: "✔ 100% Control y Seguridad de Marca",
          feat5_t: "5. Aislamiento Total Multi-Marca",
          feat5_d: "Gestiona múltiples marcas o clientes con bases de conocimiento independientes y aislamiento criptográfico de datos.",
          feat5_status: "✔ Privacidad Multi-Cliente Garantizada",
          feat6_t: "6. Smart Timing & Métricas de Impacto",
          feat6_d: "Publica e interactúa en la ventana de oro de mayor alcance orgánico y monitorea leads generados en tiempo real.",
          feat6_status: "✔ Optimización de Algoritmo Meta",
          steps_badge: "Paso a Paso",
          steps_h2: "Cómo empezar en 3 sencillos pasos",
          steps_sub: "Sin configuraciones técnicas complejas. Conecta tus redes y empieza a responder en minutos.",
          step1_t: "1. Conecta tus Cuentas",
          step1_d: "Inicia sesión con Meta OAuth oficial de forma segura. Selecciona las cuentas de Instagram y Facebook que deseas gestionar con permisos autorizados.",
          step2_t: "2. Configura tu Voz y Datos",
          step2_d: "Define los datos de tu negocio (productos, precios, FAQs) y calibra el tono de comunicación: empático, mentor o comercial de alto valor.",
          step3_t: "3. Revisa y Publica con Copilot",
          step3_d: "Recibe sugerencias instantáneas fundamentadas en tus datos. Aprueba con 1 clic o deja que el piloto automático trabaje por ti con total seguridad.",
          stat_1: "Tiempo Manual Ahorrado",
          stat_2: "Control y Aprobación Humana",
          stat_3: "Oportunidades de Compra Perdidas",
          stat_4: "Tiempo Promedio de Respuesta",
          why_badge: "Diferenciales Reales",
          why_h2: "¿Por qué los creadores y agencias eligen Xindro?",
          why_sub: "Diseñado desde el código para responder en segundos, proteger tu reputación y maximizar el algoritmo sin sonar como un robot.",
          why_p1_t: "Respuestas en Tiempo Real (<180ms)",
          why_p1_d: "El algoritmo de Meta premia a las cuentas que interactúan en los primeros 15 minutos. Nuestro motor responde casi al instante.",
          why_p1_tag: "✔ Cero demora de engagement",
          why_p2_t: "Voz de Marca Auténtica & Calibrada",
          why_p2_d: "Configura el nivel de calidez, profundidad y energía. Tus seguidores recibirán respuestas empáticas y humanas, nunca genéricas.",
          why_p2_tag: "✔ Personalización total de tono",
          why_p3_t: "100% Oficial con Meta Graph API",
          why_p3_d: "Cero riesgo de penalización o bloqueos de cuenta. Conexión autorizada por Meta con cifrado de tokens AES-256-GCM.",
          why_p3_tag: "✔ Seguridad y cumplimiento oficial",
          why_p4_t: "Smart Timing Basado en Datos",
          why_p4_d: "Analiza los patrones de actividad real de tu comunidad para decirte la hora exacta en la que obtendrás mayor alcance y guardados.",
          why_p4_tag: "✔ Horarios óptimos de publicación",
          why_p5_t: "Motor de Engagement Avanzado",
          why_p5_d: "Integra el motor de engagement en tus propios procesos de marketing con endpoints limpios y optimizados para alto rendimiento.",
          why_p5_tag: "✔ Flujo de trabajo optimizado",
          why_p6_t: "Ahorra +15 Horas de Trabajo al Mes",
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
          calc_assumptions: "* Estimación basada en un promedio de 45 segundos por respuesta manual y un 2.4% de tasa de consultas con alta intención comercial detectadas por la IA.",
          sim_badge: "Playground en Vivo",
          sim_h2: "Prueba el Motor de XINDRO en Tiempo Real",
          sim_sub: "Selecciona un tono, escribe cualquier comentario de tu comunidad y observa cómo la IA genera respuestas hipercontextualizadas.",
          sim_ctx_title: "Negocio de Ejemplo: \"Academia Stoic Pro\"",
          sim_ctx_desc: "<strong>Producto:</strong> Curso de Hábitos y Mentalidad (40h de clases grabadas, acceso vitalicio, comunidad privada). <strong>Precio:</strong> 149 € (pago único con enlace en bio).",
          sim_engine_status: "🛡️ Respuestas 100% Fundamentadas",
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
          sim_presets_title: "Probar ejemplos:",
          sim_preset_1: "📚 \"¿Clases grabadas y acceso?\"",
          sim_preset_2: "🏛️ \"Dicotomía del control\"",
          sim_preset_3: "💎 \"¿Precio del curso?\"",
          sim_btn_gen: "Generar Respuesta con IA",
          sim_res_title: "Resultado Generado",
          sim_score_label: "Prioridad Comercial:",
          sim_score_explain: "(Consulta de Compra)",
          sim_autopilot_ok: "✔ Apto para Autopilot en Instagram y Facebook",
          sim_btn_copy: "📋 Copiar",
          price_badge: "Planes Transparentes",
          price_h2: "Comienza gratis y escala con tu comunidad.",
          price_sub: "Sin contratos forzosos ni permanencia. Facturación clara en Euros. Cancela en cualquier momento.",
          plan1_t: "Plan Inicial",
          plan1_d: "Para probar el motor y dar los primeros pasos.",
          plan1_p: "/ mes gratis",
          plan1_f1: "1 cuenta conectada (IG o FB)",
          plan1_f2: "50 respuestas / tokens al mes",
          plan1_f3: "Asistente Copilot con revisión",
          plan1_f4: "Infraestructura oficial de Meta",
          plan1_f5: "Soporte estándar",
          plan1_btn: "Comenzar Gratis",
          plan2_t: "Plan Creador",
          plan2_d: "Para creadores individuales y marcas personales.",
          plan2_p: "/ mes",
          plan2_f1: "2 canales (Instagram y Facebook)",
          plan2_f2: "500 respuestas / tokens al mes",
          plan2_f3: "Automatización activa en canales clave",
          plan2_f4: "Ventana de oro del algoritmo",
          plan2_f5: "Configuración de tono base de IA",
          plan2_f6: "Soporte prioritario por email",
          plan2_btn: "Elegir Plan Creador",
          plan3_badge: "⭐ Más Recomendado",
          plan3_t: "Pro / Negocio",
          plan3_d: "Para negocios y creadores que monetizan.",
          plan3_p: "/ mes",
          plan3_f1: "Hasta 5 cuentas conectadas",
          plan3_f2: "2.500 respuestas / tokens al mes",
          plan3_f3: "Detección avanzada de leads y compra",
          plan3_f4: "Smart Timing para horarios óptimos",
          plan3_f5: "Prioridad en latencia de API",
          plan3_f6: "Calibrador de Voz de Marca multi-tono",
          plan3_f7: "Soporte prioritario 24/7",
          plan3_btn: "Comenzar con Pro",
          plan4_t: "Plan Agencia",
          plan4_d: "Para agencias y gestión multi-cliente.",
          plan4_p: "/ mes",
          plan4_f1: "Hasta 20 cuentas conectadas",
          plan4_f2: "10.000 respuestas / tokens al mes",
          plan4_f3: "Multi-cliente con voces independientes",
          plan4_f4: "Aislamiento total de marcas y datos",
          plan4_f5: "Máxima velocidad y exportación",
          plan4_f6: "Acceso para equipos y soporte dedicado",
          plan4_btn: "Acceso para Agencias",
          price_faq_box_title: "💡 ¿Cómo funcionan los tokens y respuestas mensuales?",
          price_faq_box_desc: "Cada respuesta generada y aprobada equivale a <strong>1 token / respuesta</strong>. Si alcanzas el límite mensual de tu plan, el sistema te notificará para ampliar tu cupo o cambiar de plan; nunca te cobraremos cargos sorpresa ni bloquearemos tus cuentas.",
          faq_badge: "Respuestas Claras",
          faq_h2: "Preguntas Frecuentes",
          faq_sub: "Todo lo que necesitas saber antes de empezar a automatizar tu comunidad.",
          faq_q1: "¿Es seguro para mi cuenta de Instagram o Facebook?",
          faq_a1: "Totalmente seguro. Xindro opera exclusivamente a través de la API oficial de Meta Graph con permisos autorizados y webhooks verificados. No requerimos tu contraseña de Instagram y no utilizamos navegadores automatizados o emuladores no oficiales.",
          faq_q2: "¿La IA puede responder cosas fuera de lugar o inventar información?",
          faq_a2: "El sistema incorpora estrictos filtros anti-alucinación: nunca inventa ofertas, cupos falsos ni afirma haber enviado mensajes no realizados. Si una consulta requiere datos internos no disponibles, la IA orienta amablemente hacia el enlace oficial de la bio o solicita aclaraciones. Además, cuentas con el <strong>Modo Copilot</strong> para revisar y aprobar sugerencias antes de que se publiquen, garantizando control y veracidad absoluta.",
          faq_q3: "¿Puedo conectar varias cuentas al mismo tiempo?",
          faq_a3: "Sí, puedes conectar y gestionar múltiples cuentas de Instagram y Páginas de Facebook de forma centralizada.",
          faq_q4: "¿Puedo empezar gratis sin ingresar tarjeta de crédito?",
          faq_a4: "Sí. El plan Inicial es 100% gratuito e incluye hasta 50 respuestas de prueba al mes y el asistente Copilot para que puedas probar el impacto en tu comunidad antes de decidir actualizar.",
          final_cta_h2: "¿Listo para transformar tus comentarios en oportunidades reales?",
          final_cta_sub: "Únete a los creadores y negocios que ya ahorran horas cada semana y maximizan sus ventas con XINDRO.",
          final_cta_btn: "Crear Cuenta Gratis Ahora",
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
          page_desc: "Turn Instagram and Facebook comments into conversations that grow your business with contextual AI.",
          nav_products: "Features",
          nav_steps: "How it works",
          nav_why: "Why Xindro?",
          nav_simulator: "Simulator",
          nav_roi: "Calculator",
          nav_pricing: "Pricing",
          nav_faq: "FAQ",
          nav_login: "Log in",
          nav_cta: "Get started free",
          nav_dashboard: "Go to Dashboard",
          hero_badge: "The AI operating system for content creators and brands",
          hero_h1_p1: "Turn Instagram and Facebook comments",
          hero_h1_p2: "into conversations that grow your business.",
          hero_sub: "Consolidate your comments, identify purchase inquiries, and draft replies tailored to your brand voice. Review suggestions before publishing and spend more time creating and closing deals.",
          hero_cta_sim: "Try a free AI reply",
          hero_cta_calc: "What you can do",
          hero_card_title: "Real-Time Flow: Comment ➔ Intent Detection ➔ Suggestion ➔ Human Review",
          hero_card_status: "Official Meta Graph API",
          hero_card_time: "Instagram • 2s ago",
          hero_card_intent: "🎯 Intent: <strong class=\"text-brand-600\">Mentorship Question</strong>",
          hero_card_calibrated: "Calibrated Voice",
          hero_card_bot_reply: "🤖 Brand Voice Suggestion",
          hero_card_tone: "Empathetic Mentor",
          hero_card_retention: "🚀 Retention: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Ready to post",
          hero_comment_sample: "I've been trying to stay consistent on social media for weeks but I run out of ideas and lose motivation. How do you structure your daily routine?",
          hero_reply_sample: "Alejandro, the key isn't motivation that comes and goes, but systems. Block 45 min every morning before checking your phone. Daily discipline beats sporadic inspiration. What is the first thing you will do tomorrow when you wake up? 👇",
          marquee_title: "Integrated with Official Social Media & AI Infrastructure",
          feat_badge: "Platform Capabilities",
          feat_h2: "What can you do with XINDRO?",
          feat_sub: "A complete AI operating system built for creators and brands who want to monetize their audience without losing authenticity or human control.",
          feat1_t: "1. Unified Multi-Channel Inbox",
          feat1_d: "Consolidate all Instagram and Facebook interactions in a single real-time inbox without switching tabs.",
          feat1_status: "✔ Official Meta Graph API Active",
          feat2_t: "2. Smart Intent Detection",
          feat2_d: "Instantly categorize purchase inquiries, philosophical questions, praises, or complaints to prioritize revenue-generating conversations.",
          feat2_status: "✔ Lead Detection in <180ms",
          feat3_t: "3. Calibrated Brand Voice",
          feat3_d: "Train the AI with your real business facts (products, pricing, policies) and tune tone of voice for 100% authentic answers.",
          feat3_status: "✔ Multi-Tone & Anti-Hallucination Guardrails",
          feat4_t: "4. Copilot Mode with Human Review",
          feat4_d: "You stay in full control. Review, edit with one click, or approve suggestions before they get published to your social feeds.",
          feat4_status: "✔ 100% Brand Safety & Review Control",
          feat5_t: "5. Total Multi-Brand Isolation",
          feat5_d: "Manage multiple brands or client accounts with isolated knowledge bases and cryptographic data separation.",
          feat5_status: "✔ Multi-Tenant Privacy Guaranteed",
          feat6_t: "6. Smart Timing & Impact Metrics",
          feat6_d: "Post and engage during peak organic reach windows and track generated sales leads in real time.",
          feat6_status: "✔ Meta Algorithm Optimization",
          steps_badge: "Step by Step",
          steps_h2: "How to get started in 3 easy steps",
          steps_sub: "No complex tech setup required. Connect your social channels and start replying in minutes.",
          step1_t: "1. Connect Your Accounts",
          step1_d: "Securely sign in via official Meta OAuth. Choose the Instagram and Facebook Pages you want to manage with verified permissions.",
          step2_t: "2. Configure Voice & Business Facts",
          step2_d: "Input your core business knowledge (products, pricing, FAQs) and calibrate your desired communication tone: mentor, friendly, or high-ticket sales.",
          step3_t: "3. Review & Post with Copilot",
          step3_d: "Receive instant AI drafts grounded in your real business facts. Approve with 1 click or let autopilot engage safely on your behalf.",
          stat_1: "Manual Time Saved",
          stat_2: "Human Review & Control",
          stat_3: "Lost Purchase Opportunities",
          stat_4: "Average Response Time",
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
          why_p4_tag: "✔ Optimal posting schedules",
          why_p5_t: "Advanced Engagement Engine",
          why_p5_d: "Embed the engagement engine into your own marketing workflows with clean, high-performance API endpoints.",
          why_p5_tag: "✔ Optimized workflows",
          why_p6_t: "Save +15 Hours of Manual Work / Month",
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
          calc_assumptions: "* Estimate based on an average of 45 seconds per manual reply and a 2.4% rate of high commercial intent questions detected by AI.",
          sim_badge: "Live Playground",
          sim_h2: "Test the XINDRO Engine in Real Time",
          sim_sub: "Select a tone, type any comment from your community, and watch the AI craft hyper-contextualized responses.",
          sim_ctx_title: "Sample Business: \"Academia Stoic Pro\"",
          sim_ctx_desc: "<strong>Product:</strong> Mindset & Habits Course (40h recorded lessons, lifetime access, private community). <strong>Price:</strong> 149 € (one-time payment via bio link).",
          sim_engine_status: "🛡️ 100% Grounded Responses",
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
          sim_preset_1: "📚 \"Recorded classes & access?\"",
          sim_preset_2: "🏛️ \"Dichotomy of control\"",
          sim_preset_3: "💎 \"Course price?\"",
          sim_btn_gen: "Generate AI Response",
          sim_res_title: "Result Generated",
          sim_score_label: "Commercial Priority:",
          sim_score_explain: "(Purchase Inquiry)",
          sim_autopilot_ok: "✔ Eligible for Autopilot on Instagram and Facebook",
          sim_btn_copy: "📋 Copy",
          price_badge: "Transparent Pricing",
          price_h2: "Start free and scale with your audience.",
          price_sub: "No lock-in contracts. Clear Euro billing. Cancel anytime.",
          plan1_t: "Starter Plan",
          plan1_d: "To test the engine and take the first steps.",
          plan1_p: "/ month free",
          plan1_f1: "1 connected account (IG or FB)",
          plan1_f2: "50 replies / tokens per month",
          plan1_f3: "Copilot assistant with human review",
          plan1_f4: "Official Meta Graph infrastructure",
          plan1_f5: "Standard support",
          plan1_btn: "Start for Free",
          plan2_t: "Creator Plan",
          plan2_d: "For individual creators and personal brands.",
          plan2_p: "/ month",
          plan2_f1: "2 channels (Instagram and Facebook)",
          plan2_f2: "500 replies / tokens per month",
          plan2_f3: "Active automation on key channels",
          plan2_f4: "Golden algorithm reply window",
          plan2_f5: "Base AI tone configuration",
          plan2_f6: "Priority email support",
          plan2_btn: "Choose Creator Plan",
          plan3_badge: "⭐ Most Popular",
          plan3_t: "Pro / Business",
          plan3_d: "For businesses and monetizing creators.",
          plan3_p: "/ month",
          plan3_f1: "Up to 5 connected accounts",
          plan3_f2: "2,500 replies / tokens per month",
          plan3_f3: "Advanced purchase & lead intent detection",
          plan3_f4: "Smart Timing for peak reach hours",
          plan3_f5: "Priority API response latency",
          plan3_f6: "Multi-tone Brand Voice Calibrator",
          plan3_f7: "24/7 Priority support",
          plan3_btn: "Get Started with Pro",
          plan4_t: "Agency Plan",
          plan4_d: "For marketing agencies and multi-client scale.",
          plan4_p: "/ month",
          plan4_f1: "Up to 20 connected accounts",
          plan4_f2: "10,000 replies / tokens per month",
          plan4_f3: "Multi-client management & custom brand voices",
          plan4_f4: "Strict tenant & brand database isolation",
          plan4_f5: "Maximum speed & PDF/Excel reporting",
          plan4_f6: "Team access & 24/7 dedicated support",
          plan4_btn: "Agency Access",
          price_faq_box_title: "💡 How do monthly tokens and replies work?",
          price_faq_box_desc: "Each generated and approved reply equals <strong>1 token / reply</strong>. If you reach your monthly limit, Xindro notifies you so you can easily upgrade or add tokens—we never charge surprise overages or restrict your accounts.",
          faq_badge: "Clear Answers",
          faq_h2: "Frequently Asked Questions",
          faq_sub: "Everything you need to know before automating your community.",
          faq_q1: "Is it safe for my Instagram or Facebook account?",
          faq_a1: "100% safe. Xindro operates strictly through official Meta Graph API endpoints with verified webhooks and granted permissions. We never ask for your account password or use unofficial scrapers.",
          faq_q2: "Can the AI hallucinate or post inappropriate replies?",
          faq_a2: "The system features strict anti-hallucination guardrails: it never invents offers, false scarcity, or unperformed actions. If a query requires specific unpublished business data, the AI warmly directs the user to the official bio link or DM. You also have <strong>Copilot Mode</strong> to review and approve suggestions with 1 click before publishing.",
          faq_q3: "Can I connect multiple accounts simultaneously?",
          faq_a3: "Yes, you can manage multiple Instagram accounts and Facebook Pages from a single centralized dashboard.",
          faq_q4: "Can I start for free without a credit card?",
          faq_a4: "Yes! The Starter plan is 100% free with up to 50 trial replies per month and the Copilot assistant included so you can test the impact on your community.",
          final_cta_h2: "Ready to turn your social comments into real business growth?",
          final_cta_sub: "Join creators and businesses who save hours every week and maximize sales with XINDRO.",
          final_cta_btn: "Create Free Account Now",
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
          page_desc: "Converta comentários do Instagram e Facebook em conversas que fazem seu negócio crescer com IA contextualizada.",
          nav_products: "Recursos",
          nav_steps: "Como começar",
          nav_why: "Por que a Xindro?",
          nav_simulator: "Simulador",
          nav_roi: "Calculadora",
          nav_pricing: "Preços",
          nav_faq: "FAQ",
          nav_login: "Entrar",
          nav_cta: "Comece grátis",
          nav_dashboard: "Ir ao Painel",
          hero_badge: "O sistema operacional de IA para criadores de conteúdo e marcas",
          hero_h1_p1: "Converta comentários do Instagram e Facebook",
          hero_h1_p2: "em conversas que fazem seu negócio crescer.",
          hero_sub: "Reúna seus comentários, identifique dúvidas de compra e prepare respostas no estilo da sua marca. Revise as sugestões antes de publicar e dedique mais tempo a criar e atender oportunidades.",
          hero_cta_sim: "Testar uma resposta grátis",
          hero_cta_calc: "O que você pode fazer",
          hero_card_title: "Fluxo em Tempo Real: Comentário ➔ Detecção de Intenção ➔ Sugestão ➔ Aprovação Humana",
          hero_card_status: "Meta Graph API Oficial",
          hero_card_time: "Instagram • Há 2 seg",
          hero_card_intent: "🎯 Intenção: <strong class=\"text-brand-600\">Pergunta de Mentoria</strong>",
          hero_card_calibrated: "Voz Calibrada",
          hero_card_bot_reply: "🤖 Sugestão com Tom de Marca",
          hero_card_tone: "Mentor Empático",
          hero_card_retention: "🚀 Retenção: <strong class=\"text-brand-900\">+380%</strong>",
          hero_card_ready: "✔ Pronto para postar",
          hero_comment_sample: "Estou há semanas tentando ter consistência nas redes, mas fico sem ideias e perco a motivação. Como vocês estruturam a rotina diária?",
          hero_reply_sample: "Alejandro, o segredo não é a motivação que vai e vem, mas os sistemas. Bloqueie 45 min toda manhã antes de olhar o celular. A disciplina diária supera a inspiração passageira. Qual é a primeira coisa que você fará amanhã ao acordar? 👇",
          marquee_title: "Integrado com a Infraestrutura Oficial de Redes Sociais e Inteligência Artificial",
          feat_badge: "Recursos da Plataforma",
          feat_h2: "O que você pode fazer com o XINDRO?",
          feat_sub: "Um ecossistema completo desenvolvido para criadores e marcas que desejam monetizar sua comunidade com autenticidade e controle.",
          feat1_t: "1. Caixa de Entrada Unificada Multicanal",
          feat1_d: "Centralize todas as interações do Instagram e Facebook em um único painel em tempo real sem trocar de abas.",
          feat1_status: "✔ Meta Graph API Oficial Ativa",
          feat2_t: "2. Detecção Inteligente de Intenção",
          feat2_d: "Identifique na hora dúvidas de compra, perguntas conceituais, elogios ou suporte para priorizar o que gera receita.",
          feat2_status: "✔ Detecção de Leads em <180ms",
          feat3_t: "3. Tom de Marca Calibrado",
          feat3_d: "Treine a IA com os dados reais do seu negócio (cursos, preços, políticas) e ajuste o tom para respostas 100% autênticas.",
          feat3_status: "✔ Calibrador Multi-Tom & Anti-Alucinação",
          feat4_t: "4. Modo Copilot com Revisão Humana",
          feat4_d: "Você no controle total. Revise, edite com um clique ou aprove sugestões antes de serem publicadas nas suas redes.",
          feat4_status: "✔ 100% de Controle e Segurança de Marca",
          feat5_t: "5. Isolamento Total Multi-Marca",
          feat5_d: "Gerencie múltiplas marcas ou clientes com bases de conhecimento independentes e isolamento criptográfico de dados.",
          feat5_status: "✔ Privacidade Multi-Cliente Garantizada",
          feat6_t: "6. Smart Timing & Métricas de Impacto",
          feat6_d: "Interaja nos horários de maior alcance orgânico e acompanhe oportunidades de vendas identificadas em tempo real.",
          feat6_status: "✔ Otimização de Algoritmo Meta",
          steps_badge: "Passo a Passo",
          steps_h2: "Como começar em 3 passos simples",
          steps_sub: "Sem configurações complexas. Conecte suas redes e comece a responder em minutos.",
          step1_t: "1. Conecte suas Contas",
          step1_d: "Faça login com Meta OAuth oficial com segurança. Selecione as contas do Instagram e Facebook que deseja gerenciar com permissões autorizadas.",
          step2_t: "2. Configure sua Voz e Dados",
          step2_d: "Defina os dados do seu negócio (produtos, preços, FAQs) e calibre o tom de comunicação: mentor, empático ou focado em conversão.",
          step3_t: "3. Revise e Publique com Copilot",
          step3_d: "Receba sugestões instantâneas baseadas nos dados do seu negócio. Aprove com 1 clique ou deixe o piloto automático agir com segurança.",
          stat_1: "Tempo Manual Economizado",
          stat_2: "Controle e Aprovação Humana",
          stat_3: "Oportunidades de Venda Perdidas",
          stat_4: "Tempo Médio de Resposta",
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
          why_p4_tag: "✔ Horários ideais de postagem",
          why_p5_t: "Motor de Engagement Avançado",
          why_p5_d: "Integre o motor de engajamento em seus próprios fluxos de marketing com endpoints simples e de alto desempenho.",
          why_p5_tag: "✔ Fluxos de trabalho otimizados",
          why_p6_t: "Economize +15 Horas de Trabalho por Mês",
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
          calc_assumptions: "* Estimativa baseada em uma média de 45 segundos por resposta manual e 2,4% de taxa de perguntas com alta intenção comercial detectadas pela IA.",
          sim_badge: "Playground ao Vivo",
          sim_h2: "Teste o Motor do XINDRO em Tempo Real",
          sim_sub: "Selecione um tom, digite qualquer comentário da sua comunidade e veja a IA gerar respostas hipercontextualizadas.",
          sim_ctx_title: "Negócio de Exemplo: \"Academia Stoic Pro\"",
          sim_ctx_desc: "<strong>Produto:</strong> Curso de Hábitos e Mentalidade (40h de aulas gravadas, acesso vitalício, comunidade privada). <strong>Preço:</strong> 149 € (pagamento único no link da bio).",
          sim_engine_status: "🛡️ Respostas 100% Fundamentadas",
          sim_lbl_tone: "1. Tom de Marca:",
          sim_opt_mentor: "🏛️ Estoico / Mentor Sábio",
          sim_opt_empathy: "🤝 Acolhedor & Empático",
          sim_opt_growth: "🔥 Dinâmico & Vendas High-Ticket",
          sim_lbl_plat: "2. Plataforma:",
          sim_lbl_close: "3. Pergunta no Final:",
          sim_opt_always: "Sempre incluir pergunta",
          sim_opt_rel: "Apenas quando relevante",
          sim_opt_never: "Sem pergunta final",
          sim_lbl_comment: "Comentario do seguidor para simular:",
          sim_presets_title: "Provar exemplos:",
          sim_preset_1: "📚 \"Aulas gravadas e acesso?\"",
          sim_preset_2: "🏛️ \"Dicotomia do controle\"",
          sim_preset_3: "💎 \"Preço do curso?\"",
          sim_btn_gen: "Gerar Resposta com IA",
          sim_res_title: "Resultado Gerado",
          sim_score_label: "Prioridade Comercial:",
          sim_score_explain: "(Dúvida de Compra)",
          sim_autopilot_ok: "✔ Apto para Autopilot no Instagram e Facebook",
          sim_btn_copy: "📋 Copiar",
          price_badge: "Planos Transparentes",
          price_h2: "Comece grátis e escale com sua comunidade.",
          price_sub: "Sem contratos obrigatórios. Faturamento claro em Euros. Cancele quando quiser.",
          plan1_t: "Plano Inicial",
          plan1_d: "Para testar o motor e dar os primeiros passos.",
          plan1_p: "/ mês grátis",
          plan1_f1: "1 conta conectada (IG ou FB)",
          plan1_f2: "50 respostas / tokens por mês",
          plan1_f3: "Assistente Copilot com revisão",
          plan1_f4: "Infraestrutura oficial da Meta",
          plan1_f5: "Suporte padrão",
          plan1_btn: "Começar Grátis",
          plan2_t: "Plano Criador",
          plan2_d: "Para criadores individuais e marcas pessoais.",
          plan2_p: "/ mês",
          plan2_f1: "2 canais (Instagram e Facebook)",
          plan2_f2: "500 respostas / tokens por mês",
          plan2_f3: "Automação ativa nos canais principais",
          plan2_f4: "Janela de ouro do algoritmo",
          plan2_f5: "Configuração de tom base de IA",
          plan2_f6: "Suporte prioritário por email",
          plan2_btn: "Escolher Plano Criador",
          plan3_badge: "⭐ Mais Recomendado",
          plan3_t: "Pro / Negócio",
          plan3_d: "Para negócios e criadores que monetizam.",
          plan3_p: "/ mês",
          plan3_f1: "Até 5 contas conectadas",
          plan3_f2: "2.500 respostas / tokens por mês",
          plan3_f3: "Detecção avançada de leads e compras",
          plan3_f4: "Smart Timing para horários ideais",
          plan3_f5: "Prioridade em latência de API",
          plan3_f6: "Calibrador de Tom de Marca multi-tom",
          plan3_f7: "Suporte prioritário 24/7",
          plan3_btn: "Começar com Pro",
          plan4_t: "Plano Agência",
          plan4_d: "Para agências e gestão multi-cliente.",
          plan4_p: "/ mês",
          plan4_f1: "Até 20 contas conectadas",
          plan4_f2: "10.000 respostas / tokens por mês",
          plan4_f3: "Multi-cliente com vozes de marca independentes",
          plan4_f4: "Isolamento total de marcas e dados",
          plan4_f5: "Velocidade máxima e exportação de relatórios",
          plan4_f6: "Acesso para equipes e suporte dedicado",
          plan4_btn: "Acesso para Agências",
          price_faq_box_title: "💡 Como funcionam os tokens e respostas mensais?",
          price_faq_box_desc: "Cada resposta gerada e aprovada equivale a <strong>1 token / resposta</strong>. Se você atingir o limite mensal do seu plano, o sistema notificará você para expandir seu limite; nunca cobraremos taxas surpresa.",
          faq_badge: "Respostas Claras",
          faq_h2: "Perguntas Frequentes",
          faq_sub: "Tudo o que você precisa saber antes de automatizar sua comunidade.",
          faq_q1: "É seguro para minha conta do Instagram ou Facebook?",
          faq_a1: "Totalmente seguro. O Xindro opera estritamente através da API oficial do Meta Graph com permissões autorizadas e webhooks verificados. Nunca solicitamos sua senha e não usamos navegadores ou emuladores não oficiais.",
          faq_q2: "A IA pode responder coisas fora de contexto?",
          faq_a2: "O sistema possui filtros rígidos anti-alucinação: nunca inventa ofertas, vagas falsas ou afirma ter enviado mensagens não realizadas. Se uma pergunta exige dados específicos não cadastrados, a IA orienta para o link oficial da bio ou DM. Além disso, você conta com o <strong>Modo Copilot</strong> para revisar e aprovar respostas antes da publicação.",
          faq_q3: "Posso conectar várias contas ao mesmo tempo?",
          faq_a3: "Sim, você pode conectar e gerenciar várias contas do Instagram e Páginas do Facebook de forma centralizada.",
          faq_q4: "Posso começar grátis sem cartão de crédito?",
          faq_a4: "Sim! O plano Inicial é 100% gratuito com até 50 respostas de teste por mês e o assistente Copilot incluído para você testar o impacto na sua comunidade.",
          final_cta_h2: "Pronto para transformar comentários em oportunidades reais?",
          final_cta_sub: "Junte-se a criadores e marcas que já economizam horas toda semana e aceleram vendas com a XINDRO.",
          final_cta_btn: "Criar Conta Grátis Agora",
          foot_brand_desc: "O sistema operacional de IA para criadores e agências de redes sociais. Respostas em tempo real, Smart Timing e sincronização oficial.",
          foot_status_pill: "Meta API 100% Operacional",
          foot_c2_t: "Produto",
          foot_c2_1: "Precios",
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
          cookie_cat3_d: "Lembram suas preferências de idioma (Espanhol, Inglés, Portugués), tom padrão e configurações do simulador.",
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
          if (d[key] !== undefined) el.innerHTML = d[key];
        });
        if (d.page_title) document.title = d.page_title;
        const metaDesc = document.getElementById('meta-page-desc');
        if (metaDesc && d.page_desc) metaDesc.setAttribute('content', d.page_desc);
        const sampleComment = document.getElementById('hero-sample-comment');
        const sampleReply = document.getElementById('hero-sample-reply');
        if (sampleComment && d.hero_comment_sample) sampleComment.textContent = `"${d.hero_comment_sample}"`;
        if (sampleReply && d.hero_reply_sample) sampleReply.textContent = `"${d.hero_reply_sample}"`;
        if (typeof Calculator !== 'undefined' && Calculator.update) {
          Calculator.update();
        }
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
    const CookieConsent = {
      init() {
        const consent = localStorage.getItem('xindro_cookie_consent');
        if (!consent) {
          const modal = document.getElementById('cookie-consent-modal');
          if (modal) modal.classList.remove('hidden');
        }
      },
      acceptAll() {
        localStorage.setItem('xindro_cookie_consent', JSON.stringify({
          essential: true,
          analytics: true,
          personalization: true,
          timestamp: Date.now()
        }));
        this.hideModal();
      },
      rejectNonEssential() {
        localStorage.setItem('xindro_cookie_consent', JSON.stringify({
          essential: true,
          analytics: false,
          personalization: false,
          timestamp: Date.now()
        }));
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
    const Simulator = {
      presets: {
        es: {
          1: { text: "¿El curso incluye clases grabadas y cuánto tiempo tengo acceso?", tone: "empathy", closing: "always" },
          2: { text: "¿Cómo puedo aplicar la dicotomía del control cuando siento estrés o sobrecarga en mis proyectos?", tone: "mentor", closing: "relevant" },
          3: { text: "¿Cuál es el precio del curso y qué incluye?", tone: "growth", closing: "always" }
        },
        en: {
          1: { text: "Does the course include recorded lessons and how long do I have access?", tone: "empathy", closing: "always" },
          2: { text: "How can I apply the dichotomy of control when I feel stressed or overwhelmed?", tone: "mentor", closing: "relevant" },
          3: { text: "What is the price of the course and what does it include?", tone: "growth", closing: "always" }
        },
        pt: {
          1: { text: "O curso inclui aulas gravadas e por quanto tempo tenho acesso?", tone: "empathy", closing: "always" },
          2: { text: "Como posso aplicar a dicotomia do controle quando sinto estresse ou sobrecarga?", tone: "mentor", closing: "relevant" },
          3: { text: "Qual é o preço do curso e o que está incluído?", tone: "growth", closing: "always" }
        }
      },
      loadPreset(num) {
        const lang = I18n.current || 'es';
        const p = this.presets[lang]?.[num] || this.presets['es'][num];
        if (!p) return;
        const input = document.getElementById('sim-input-text');
        const tone = document.getElementById('sim-tone');
        const closing = document.getElementById('sim-closing');
        if (input) input.value = p.text;
        if (tone) tone.value = p.tone;
        if (closing) closing.value = p.closing;
        this.generate();
      },
      generate() {
        const inputEl = document.getElementById('sim-input-text');
        const toneEl = document.getElementById('sim-tone');
        const closingEl = document.getElementById('sim-closing');
        const btn = document.getElementById('sim-btn-generate');
        const outputText = document.getElementById('sim-output-text');
        const badgeIntent = document.getElementById('sim-badge-intent');
        const badgeScore = document.getElementById('sim-badge-score');
        const badgeAutopilot = document.getElementById('sim-badge-autopilot');
        
        if (!inputEl) return;
        const text = inputEl.value.trim();
        const tone = toneEl ? toneEl.value : 'mentor';
        const closing = closingEl ? closingEl.value : 'always';
        const lang = I18n.current || 'es';

        if (!text) return;

        if (btn) {
          btn.disabled = true;
          btn.innerHTML = '<span>Generando respuesta con IA...</span>';
        }

        setTimeout(() => {
          let reply = '';
          let intent = 'Interés / Comunidad';
          let score = 92;
          let autopilotText = '✔ Apto para Autopilot en Instagram y Facebook';
          const cleanText = text.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

          if (lang === 'en') {
            const isPrice = cleanText.includes('price') || cleanText.includes('cost') || cleanText.includes('how much') || cleanText.includes('buy') || cleanText.includes('pay') || cleanText.includes('card') || cleanText.includes('purchase') || cleanText.includes('where') || cleanText.includes('link') || cleanText.includes('bio') || cleanText.includes('enroll') || cleanText.includes('invest');
            const isCourseQA = cleanText.includes('record') || cleanText.includes('access') || cleanText.includes('lesson') || cleanText.includes('class') || cleanText.includes('course') || cleanText.includes('curricul') || cleanText.includes('how long') || cleanText.includes('lifetime') || cleanText.includes('hour') || cleanText.includes('schedule') || cleanText.includes('format') || cleanText.includes('module');
            const isDichotomy = cleanText.includes('dichotom') || cleanText.includes('control') || cleanText.includes('stoic') || cleanText.includes('marcus') || cleanText.includes('seneca') || cleanText.includes('stress') || cleanText.includes('anxiet') || cleanText.includes('habit') || cleanText.includes('mindset') || cleanText.includes('procrastin') || cleanText.includes('focus') || cleanText.includes('discipline');

            if (isPrice) {
              intent = 'Commercial Lead: Pricing & Enrollment 💎';
              score = 98;
              autopilotText = '✔ Verified & Grounded for Autopilot (149 € facts)';
              reply = tone === 'mentor'
                ? "The investment in the course is 149 € (one-time payment), including lifetime access to all 40+ hours of recorded lessons, practical exercises, and our private community. You can enroll directly through our bio link. Any questions before getting started? 🏛️"
                : (tone === 'empathy'
                  ? "Hi! The course is 149 € (one-time payment) for full lifetime access to over 40 hours of recorded classes and the private community. You can check all details and sign up directly via our bio link. Would you like to review the syllabus? 🤝"
                  : "Hey! The course is 149 € in a single payment with instant lifetime access to 40+ hours of training and the private community. Direct registration is open via our bio link. Ready to level up? 🚀");
            } else if (isCourseQA) {
              intent = 'Lead: Course Structure & Lifetime Access 📚';
              score = 96;
              autopilotText = '✔ Verified & Grounded for Autopilot (40h recorded lessons)';
              reply = tone === 'mentor'
                ? "Hello! The program includes lifetime access to all recorded lessons (over 40 hours of practical training) and our private community, allowing you to advance with discipline at your own pace. Complete syllabus and registration are available in our bio link. Do you have any specific questions about the modules? 🏛️"
                : (tone === 'empathy'
                  ? "Hi! Yes, the course includes lifetime access to 40+ hours of recorded classes and the private community so you can study with total flexibility. Complete details and registration are available via our bio link. Would you like to review the syllabus? 🤝"
                  : "Hey! Absolutely, you get instant lifetime access to over 40 hours of recorded lessons and the private community to learn at your pace. Check details and sign up directly through our bio link. Ready to get started? 🚀");
            } else if (isDichotomy) {
              intent = 'Stoic Philosophy & Mindset Mentoring 🧠';
              score = 92;
              autopilotText = '✔ Verified & Grounded for Autopilot (Stoic frameworks)';
              reply = tone === 'mentor'
                ? "The dichotomy of control is about focusing 100% of your energy on what is within your power (your decisions, effort, and attitude) and letting go of external outcomes. When facing friction ask: 'Is this under my direct control?'. Where would you like to apply it today? 🏛️"
                : (tone === 'empathy'
                  ? "Hello! Applying the dichotomy of control starts by separating what you can directly influence from what you cannot. Focus your energy on your response and let go of anxiety over external factors. What challenge are you navigating this week? 🤝"
                  : "Great question! The dichotomy of control is the ultimate mental clarity tool: execute ruthlessly on your own actions and ignore external noise. Check our bio link for practical mindset frameworks. What habit are you building today? 🚀");
            } else {
              intent = 'Connection & Community ⚡';
              score = 90;
              autopilotText = '✔ Verified & Grounded for Autopilot';
              reply = tone === 'mentor'
                ? "Hello! At Academia Stoic Pro we focus on building solid habits and mental clarity through our 40-hour recorded course with lifetime access (149 €). Check out our bio link for all details. How can we support your goals today? 🏛️"
                : "Hi! Welcome to Academia Stoic Pro. We offer a comprehensive mindset and habits program with 40+ hours of recorded lessons and lifetime access. Feel free to check our bio link or ask us anything! 🤝";
            }
          } else if (lang === 'pt') {
            const isPrice = cleanText.includes('prec') || cleanText.includes('cust') || cleanText.includes('quant') || cleanText.includes('compr') || cleanText.includes('pag') || cleanText.includes('valor') || cleanText.includes('cart') || cleanText.includes('onde') || cleanText.includes('link') || cleanText.includes('bio') || cleanText.includes('inscric') || cleanText.includes('invest');
            const isCourseQA = cleanText.includes('gravad') || cleanText.includes('acess') || cleanText.includes('aula') || cleanText.includes('curs') || cleanText.includes('temp') || cleanText.includes('durac') || cleanText.includes('cronogram') || cleanText.includes('vitalici') || cleanText.includes('hora') || cleanText.includes('modul');
            const isDichotomy = cleanText.includes('dicotom') || cleanText.includes('control') || cleanText.includes('estoic') || cleanText.includes('marco') || cleanText.includes('seneca') || cleanText.includes('estress') || cleanText.includes('ansied') || cleanText.includes('procrastin') || cleanText.includes('habit') || cleanText.includes('mentoria') || cleanText.includes('foco') || cleanText.includes('disciplin');

            if (isPrice) {
              intent = 'Lead de Alta Conversão: Preço e Inscrição 💎';
              score = 98;
              autopilotText = '✔ Resposta Verificada Apta para Autopilot (149 €)';
              reply = tone === 'mentor'
                ? "O investimento no curso é de 149 € em pagamento único, incluindo acesso vitalício a mais de 40 horas de aulas gravadas e à comunidade privada. Você encontra o cronograma e inscrição direta no link da nossa biografia. Alguma dúvida sobre o programa? 🏛️"
                : (tone === 'empathy'
                  ? "Olá! O valor é de 149 € (pagamento único) com acesso vitalício a mais de 40 horas de aulas gravadas e à comunidade privada. Todas as informações e inscrições estão no link da nossa biografia. Gostaria de saber mais sobre o conteúdo? 🤝"
                  : "Olá! O curso custa 149 € em pagamento único com acesso vitalício imediato a mais de 40 horas de conteúdo e comunidade privada. Inscrições abertas no link da nossa bio. Pronto para começar? 🚀");
            } else if (isCourseQA) {
              intent = 'Lead: Estrutura do Curso e Acesso 📚';
              score = 96;
              autopilotText = '✔ Resposta Verificada Apta para Autopilot (40h gravadas)';
              reply = tone === 'mentor'
                ? "Olá! O programa inclui acesso vitalício a todas as aulas gravadas (mais de 40 horas de conteúdo prático) e à nossa comunidade privada, para você avançar com disciplina no seu próprio ritmo. Você pode consultar o cronograma e se inscrever no link da nossa biografia. Alguma dúvida sobre os módulos? 🏛️"
                : (tone === 'empathy'
                  ? "Olá! Sim, o curso inclui acesso vitalício a mais de 40 horas de aulas gravadas e à comunidade privada para você estudar com total flexibilidade no seu tempo. Todos os detalhes e inscrições estão no link da nossa biografia. Gostaria de saber mais sobre o conteúdo? 🤝"
                  : "Olá! Com certeza, você tem acesso vitalício imediato a mais de 40 horas de aulas gravadas e à comunidade privada. Confira os detalhes e faça sua inscrição no link da nossa bio. Pronto para começar? 🚀");
            } else if (isDichotomy) {
              intent = 'Filosofia Estoica & Mentoria 🧠';
              score = 92;
              autopilotText = '✔ Resposta Verificada Apta para Autopilot';
              reply = tone === 'mentor'
                ? "A dicotomia do controle ensina a focar 100% da sua energia no que depende de você (suas escolhas, ações e atitude) e aceitar com serenidade o externo. Diante de qualquer obstáculo, pergunte-se: 'Isso está sob meu controle direto?'. Onde você quer aplicar isso hoje? 🏛️"
                : (tone === 'empathy'
                  ? "Olá! Aplicar a dicotomia do controle começa por separar o que você pode influenciar diretamente daquilo que não pode. Foque sua energia na sua resposta e solte a ansiedade sobre o incontrolável. Que desafio você está enfrentando esta semana? 🤝"
                  : "Excelente pergunta! A dicotomia do controle é a chave para o foco: aja com intensidade máxima nas suas ações e elimine o ruído do que você não controla. No link da nossa bio compartilhamos recursos práticos sobre mentalidade. Qual hábito você quer fortalecer hoje? 🚀");
            } else {
              intent = 'Conexão & Comunidade ⚡';
              score = 90;
              autopilotText = '✔ Resposta Verificada Apta para Autopilot';
              reply = tone === 'mentor'
                ? "Olá! Na Academia Stoic Pro focamos no desenvolvimento de hábitos e clareza mental através do nosso curso de 40h com acesso vitalício. Você pode conferir os detalhes no link da nossa biografia. Como podemos te apoiar hoje? 🏛️"
                : "Olá! Bem-vindo à Academia Stoic Pro. Nosso curso inclui 40h de aulas gravadas com acesso vitalício e comunidade exclusiva. Confira o link da nossa bio para saber mais! 🤝";
            }
          } else {
            // Spanish (Default) - Fully typo-tolerant matching for words like 'pagvo', 'comom', 'presio', 'cuanto vale', etc.
            const isPrice = cleanText.includes('pag') || cleanText.includes('preci') || cleanText.includes('presi') || cleanText.includes('cost') || cleanText.includes('cuest') || cleanText.includes('cuant') || cleanText.includes('invers') || cleanText.includes('compr') || cleanText.includes('tarjet') || cleanText.includes('metod') || cleanText.includes('adquir') || cleanText.includes('donde') || cleanText.includes('link') || cleanText.includes('bio') || cleanText.includes('enlace') || cleanText.includes('val') || cleanText.includes('inscrib') || cleanText.includes('matricul');
            const isCourseQA = cleanText.includes('grab') || cleanText.includes('acces') || cleanText.includes('clase') || cleanText.includes('curs') || cleanText.includes('tiemp') || cleanText.includes('durac') || cleanText.includes('leccion') || cleanText.includes('modul') || cleanText.includes('temari') || cleanText.includes('vitalici') || cleanText.includes('horari') || cleanText.includes('contenid') || cleanText.includes('incluy') || cleanText.includes('hora');
            const isDichotomy = cleanText.includes('dicotom') || cleanText.includes('control') || cleanText.includes('estoic') || cleanText.includes('seneca') || cleanText.includes('marco') || cleanText.includes('epictet') || cleanText.includes('estres') || cleanText.includes('ansied') || cleanText.includes('procrastin') || cleanText.includes('mied') || cleanText.includes('motiv') || cleanText.includes('habit') || cleanText.includes('filosof') || cleanText.includes('disciplin') || cleanText.includes('mente');

            if (isPrice) {
              intent = 'Oportunidad Comercial / Precio y Pago 💎';
              score = 98;
              autopilotText = '✔ Apto para Autopilot (Respuesta fundamentada: 149 €)';
              reply = tone === 'mentor'
                ? "La inversión en el curso es de 149 € en pago único, e incluye acceso vitalicio a las 40 horas de clases grabadas, materiales prácticos y acceso a la comunidad privada. Puedes inscribirte y abonar directamente en el enlace de nuestra biografía. ¿Tienes alguna pregunta puntual sobre los módulos? 🏛️"
                : (tone === 'empathy'
                  ? "¡Hola! El precio del curso es de 149 € (pago único) con acceso vitalicio completo a las más de 40 horas de clases grabadas y la comunidad privada. Puedes inscribirte con tarjeta o tu método preferido en el enlace de nuestra biografía. ¿Te gustaría conocer el temario detallado? 🤝"
                  : "¡Hola! El curso tiene un precio de 149 € en pago único con acceso vitalicio inmediato a las 40 horas de formación y la comunidad privada. Puedes inscribirte directamente desde el enlace de nuestra biografía. ¿Listo para dar el siguiente paso? 🚀");
            } else if (isCourseQA) {
              intent = 'Lead Calificado • Modalidad del Curso 📚';
              score = 96;
              autopilotText = '✔ Apto para Autopilot (Respuesta fundamentada en datos de negocio)';
              reply = tone === 'mentor'
                ? "¡Hola! El programa incluye acceso vitalicio a todas las clases grabadas (más de 40 horas de formación práctica) y a nuestra comunidad privada, para que avances con disciplina a tu propio ritmo. Tienes el temario completo e inscripción directa en el enlace de nuestra biografía. ¿Tienes alguna duda puntual sobre el programa? 🏛️"
                : (tone === 'empathy'
                  ? "¡Hola! Sí, el curso incluye acceso vitalicio a todas las clases grabadas (más de 40 horas) y a la comunidad privada para que aprendas con total flexibilidad a tu propio ritmo. Encontrarás los detalles completos e inscripción directa en el enlace de nuestra biografía. ¿Te gustaría conocer el temario? 🤝"
                  : "¡Hola! Efectivamente, tienes acceso vitalicio e inmediato a más de 40 horas de clases grabadas y a la comunidad privada para formarte a tu propio ritmo. Tienes todos los detalles y registro en el enlace de nuestra biografía. ¿Listo para dar el siguiente paso? 🚀");
            } else if (isDichotomy) {
              intent = 'Consulta Conceptual & Mentoría Estoica 🧠';
              score = 92;
              autopilotText = '✔ Apto para Autopilot (Respuesta conceptual verificada y fundamentada)';
              reply = tone === 'mentor'
                ? "La dicotomía del control consiste en enfocar el 100% de nuestra energía en lo que sí depende de nosotros (nuestras decisiones, acciones y actitud) y soltar con serenidad lo externo. Ante cualquier sobrecarga pregúntate: '¿Está bajo mi control directo?'. Si lo está, actúa con determinación; si no, canaliza tu energía en tu propia respuesta y suelta lo demás. ¿En qué situación buscas aplicarlo hoy? 🏛️"
                : (tone === 'empathy'
                  ? "¡Hola! Aplicar la dicotomía del control comienza por reconocer qué parte de una situación está en tus manos y cuál no. Ante el estrés, enfócate con calma en tu propia respuesta y deja ir la angustia por lo incontrolable. ¿Hay algún reto puntual que estés enfrentando esta semana? 🤝"
                  : "¡Excelente pregunta! La dicotomía del control es la herramienta clave para mantener el foco: actúa con máxima intensidad en lo que depende de ti y elimina el ruido de lo que no puedes controlar. En el enlace de nuestra bio compartimos recursos prácticos sobre mentalidad y productividad. ¿Qué hábito quieres fortalecer hoy? 🚀");
            } else {
              intent = 'Conexión & Comunidad ⚡';
              score = 90;
              autopilotText = '✔ Apto para Autopilot (Comunidad y fidelización)';
              reply = tone === 'mentor'
                ? "¡Hola! En Academia Stoic Pro nos enfocamos en el desarrollo de hábitos sólidos y disciplina diaria a través de nuestro curso de 40 horas grabadas con acceso vitalicio (149 €). Puedes consultar el temario e inscribirte en el enlace de nuestra biografía. ¿En qué objetivo te gustaría que te apoyemos hoy? 🏛️"
                : "¡Hola! Con mucho gusto te ayudamos. En Academia Stoic Pro contamos con el programa completo de hábitos y mentalidad estoica (40h grabadas, acceso vitalicio y comunidad). Tienes toda la información en el enlace de nuestra biografía. ¿Tienes alguna duda puntual? 🤝";
            }
          }

          if (closing === 'never') {
            reply = reply.replace(/\¿[^\?]+\?\s*(👇|✨|🔥|🚀|📩|🤝|🏛️|📚)?$/i, '').replace(/\?[^\?]+\?\s*(👇|✨|🔥|🚀|📩|🤝|🏛️|📚)?$/i, '');
          }

          badgeIntent.textContent = '🎯 ' + intent;
          badgeScore.textContent = score + '/100';
          outputText.textContent = `"${reply}"`;

          if (badgeAutopilot) {
            badgeAutopilot.innerHTML = `<span>✔</span> ${autopilotText}`;
            badgeAutopilot.className = 'flex items-center gap-1.5 text-emerald-600 font-semibold text-[11px] sm:text-xs';
          }

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
