<?php
/**
 * XINDRO — Acerca de Nosotros / About Us (Gamma.app Inspired Architecture)
 * Nuestra Misión, Tecnología de IA, Trayectoria, Equipo, Seguridad y Principios de Ingeniería.
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

  <!-- Fonts: Plus Jakarta Sans, Syne & JetBrains Mono -->
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

    /* ========================================================================= */
    /* MEGA MENU SYSTEM                                                          */
    /* ========================================================================= */
    .mega-menu-item {
      position: relative;
    }
    .mega-menu-trigger {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.5rem 0.75rem;
      border-radius: 0.75rem;
      font-weight: 600;
      color: #475569;
      transition: all 0.15s ease-in-out;
      cursor: pointer;
      user-select: none;
    }
    .mega-menu-trigger:hover,
    .mega-menu-item:hover .mega-menu-trigger,
    .mega-menu-item:focus-within .mega-menu-trigger {
      color: #7C3AED;
      background-color: rgba(241, 245, 249, 0.85);
    }
    .mega-chevron {
      transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .mega-menu-item:hover .mega-chevron,
    .mega-menu-item:focus-within .mega-chevron {
      transform: rotate(180deg);
      color: #7C3AED;
    }
    .mega-dropdown-panel {
      position: absolute;
      top: 100%;
      left: 50%;
      transform: translateX(-50%) translateY(10px);
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
      transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
      z-index: 60;
    }
    .mega-dropdown-panel::before {
      content: '';
      position: absolute;
      top: -14px;
      left: 0;
      right: 0;
      height: 14px;
    }
    .mega-menu-item:hover .mega-dropdown-panel,
    .mega-menu-item:focus-within .mega-dropdown-panel {
      opacity: 1;
      visibility: visible;
      pointer-events: auto;
      transform: translateX(-50%) translateY(0);
    }
    .mega-card-box {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(24px);
      -webkit-backdrop-filter: blur(24px);
      border: 1px solid rgba(226, 232, 240, 0.9);
      box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.16), 0 0 0 1px rgba(226, 232, 240, 0.5);
      border-radius: 1.25rem;
    }
    .mega-item-link {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 0.75rem 0.85rem;
      border-radius: 0.875rem;
      transition: all 0.15s ease-in-out;
      text-decoration: none;
    }
    .mega-item-link:hover {
      background-color: rgba(248, 250, 252, 0.95);
      transform: translateX(3px);
    }
    .mega-icon-wrap {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 0.75rem;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 1.25rem;
      transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .mega-item-link:hover .mega-icon-wrap {
      transform: scale(1.1);
      box-shadow: 0 4px 12px rgba(124, 58, 237, 0.15);
    }
  </style>
</head>
<body class="antialiased selection:bg-brand-500 selection:text-white">

  <!-- ========================================================================= -->
  <!-- 1. NAVBAR FIJA RESPONSIVA CON MEGA MENÚ -->
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

      <!-- Desktop Navigation with Mega Menus -->
      <nav class="hidden lg:flex items-center gap-1 xl:gap-2 text-[13px] xl:text-sm font-semibold text-slate-600">
        
        <!-- MEGA MENU: PRODUCTO -->
        <div class="mega-menu-item">
          <button type="button" class="mega-menu-trigger">
            <span data-i18n="nav_menu_product">Producto</span>
            <svg class="w-3.5 h-3.5 text-slate-400 mega-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>

          <div class="mega-dropdown-panel pt-2">
            <div class="mega-card-box p-5 w-[680px] grid grid-cols-12 gap-5">
              <div class="col-span-7 space-y-1">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pb-1">Funciones Clave</div>
                <a href="index.php#funciones" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-purple-50 text-purple-600 border border-purple-100">📥</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_feat1_title">Bandeja Unificada</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_feat1_desc">Gestiona Instagram y Facebook en tiempo real desde un solo lugar.</div>
                  </div>
                </a>
                <a href="index.php#funciones" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-indigo-50 text-indigo-600 border border-indigo-100">🎭</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_feat2_title">Voz de Marca Calibrada</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_feat2_desc">Respuestas auténticas con datos de tu negocio y anti-alucinación.</div>
                  </div>
                </a>
                <a href="index.php#funciones" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-emerald-50 text-emerald-600 border border-emerald-100">🎯</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_feat3_title">Detección de Leads & Compras</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_feat3_desc">Identifica oportunidades comerciales en comentarios en <180ms.</div>
                  </div>
                </a>
                <a href="index.php#funciones" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-sky-50 text-sky-600 border border-sky-100">⏱️</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_feat4_title">Smart Timing & Antiban</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_feat4_desc">Publica en la ventana de oro oficial del algoritmo de Meta.</div>
                  </div>
                </a>
              </div>
              <div class="col-span-5 flex flex-col justify-between p-4 rounded-2xl bg-gradient-to-br from-purple-50/80 via-indigo-50/50 to-slate-50 border border-purple-100/80">
                <div>
                  <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-100 text-purple-700 text-[10px] font-black uppercase tracking-wider mb-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-500 live-dot"></span>
                    <span>Playground</span>
                  </div>
                  <h4 class="text-sm font-black text-slate-900 mb-1.5" data-i18n="nav_mega_card_sim_title">Simulador de IA en Vivo</h4>
                  <p class="text-xs text-slate-600 font-normal leading-relaxed mb-4" data-i18n="nav_mega_card_sim_desc">Prueba cómo responde la IA a comentarios de tu audiencia en segundos.</p>
                </div>
                <a href="index.php#simulador" class="inline-flex items-center justify-center gap-1.5 w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-brand-600 hover:bg-brand-700 shadow-sm transition-all" data-i18n="nav_mega_card_sim_btn">Probar Simulador →</a>
              </div>
            </div>
          </div>
        </div>

        <!-- MEGA MENU: SOLUCIONES -->
        <div class="mega-menu-item">
          <button type="button" class="mega-menu-trigger">
            <span data-i18n="nav_menu_solutions">Soluciones</span>
            <svg class="w-3.5 h-3.5 text-slate-400 mega-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>

          <div class="mega-dropdown-panel pt-2">
            <div class="mega-card-box p-5 w-[660px] grid grid-cols-12 gap-5">
              <div class="col-span-7 space-y-1">
                <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pb-1">Por Tipo de Negocio</div>
                <a href="index.php#por-que-xindro" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-pink-50 text-pink-600 border border-pink-100">🎨</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_sol1_title">Creadores e Influencers</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_sol1_desc">Mantén tu comunidad activa y fidelizada sin pasar horas respondiendo.</div>
                  </div>
                </a>
                <a href="index.php#por-que-xindro" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-blue-50 text-blue-600 border border-blue-100">🏢</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_sol2_title">Agencias & CMs</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_sol2_desc">Administra múltiples clientes y marcas con total aislamiento de datos.</div>
                  </div>
                </a>
                <a href="index.php#por-que-xindro" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-amber-50 text-amber-600 border border-amber-100">🛍️</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_sol3_title">Marcas & E-commerce</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_sol3_desc">Convierte dudas en compras y acelera la atención al cliente.</div>
                  </div>
                </a>
                <a href="index.php#por-que-xindro" class="mega-item-link group/item">
                  <div class="mega-icon-wrap bg-emerald-50 text-emerald-600 border border-emerald-100">🎓</div>
                  <div>
                    <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_sol4_title">Coaches & Infoproductores</div>
                    <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_sol4_desc">Vende formaciones y cursos 24/7 respondiendo preguntas clave.</div>
                  </div>
                </a>
              </div>
              <div class="col-span-5 flex flex-col justify-between p-4 rounded-2xl bg-gradient-to-br from-emerald-50/70 via-teal-50/40 to-slate-50 border border-emerald-100/80">
                <div>
                  <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-wider mb-3">
                    <span>Ahorro & ROI</span>
                  </div>
                  <h4 class="text-sm font-black text-slate-900 mb-1.5" data-i18n="nav_mega_card_roi_title">Calculadora de Ahorro</h4>
                  <p class="text-xs text-slate-600 font-normal leading-relaxed mb-4" data-i18n="nav_mega_card_roi_desc">Calcula cuánto tiempo y leads calificados puedes ganar al mes.</p>
                </div>
                <a href="index.php#calculadora-roi" class="inline-flex items-center justify-center gap-1.5 w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 shadow-sm transition-all" data-i18n="nav_mega_card_roi_btn">Calcular Impacto →</a>
              </div>
            </div>
          </div>
        </div>

        <!-- MEGA MENU: EMPRESA -->
        <div class="mega-menu-item">
          <button type="button" class="mega-menu-trigger">
            <span data-i18n="nav_menu_company">Empresa</span>
            <svg class="w-3.5 h-3.5 text-slate-400 mega-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>

          <div class="mega-dropdown-panel pt-2">
            <div class="mega-card-box p-5 w-[520px] space-y-1">
              <div class="text-[11px] font-black uppercase tracking-wider text-slate-400 px-3 pb-1">Conoce XINDRO</div>
              <a href="about.php" class="mega-item-link group/item bg-purple-50/50">
                <div class="mega-icon-wrap bg-purple-100 text-purple-700 border border-purple-200">🏛️</div>
                <div class="flex-1">
                  <div class="text-sm font-bold text-brand-700 flex items-center gap-2">
                    <span data-i18n="nav_mega_comp1_title">Acerca de XINDRO</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-brand-100 text-brand-700 tracking-wide">Activo</span>
                  </div>
                  <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_comp1_desc">Nuestra visión, misión, tecnología de IA y equipo.</div>
                </div>
              </a>
              <a href="privacy-policy.php" class="mega-item-link group/item">
                <div class="mega-icon-wrap bg-emerald-50 text-emerald-600 border border-emerald-100">🛡️</div>
                <div>
                  <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_comp2_title">Seguridad & Privacidad</div>
                  <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_comp2_desc">Cumplimiento estricto GDPR, Meta API oficial y cifrado de datos.</div>
                </div>
              </a>
              <a href="terms-of-service.php" class="mega-item-link group/item">
                <div class="mega-icon-wrap bg-slate-50 text-slate-700 border border-slate-200">📜</div>
                <div>
                  <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_comp3_title">Términos del Servicio</div>
                  <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_comp3_desc">Transparencia legal, garantías y condiciones de uso.</div>
                </div>
              </a>
              <a href="index.php#faq" class="mega-item-link group/item">
                <div class="mega-icon-wrap bg-amber-50 text-amber-600 border border-amber-100">❓</div>
                <div>
                  <div class="text-sm font-bold text-slate-900 group-hover/item:text-brand-600 transition-colors" data-i18n="nav_mega_comp4_title">Preguntas Frecuentes</div>
                  <div class="text-xs text-slate-500 font-normal leading-relaxed" data-i18n="nav_mega_comp4_desc">Respuestas claras sobre funcionamiento, límites y planes.</div>
                </div>
              </a>
            </div>
          </div>
        </div>

        <!-- Direct Nav Links -->
        <a href="index.php#simulador" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors flex items-center gap-1.5">
          <span data-i18n="nav_simulator">Simulador</span>
          <span class="inline-block w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
        </a>
        <a href="index.php#precios" data-i18n="nav_pricing" class="px-2.5 py-2 rounded-xl whitespace-nowrap hover:text-brand-600 hover:bg-slate-100/70 transition-colors">Precios</a>
      </nav>

      <!-- Right Controls -->
      <div class="flex items-center gap-2 sm:gap-3 shrink-0 ml-auto lg:ml-0">
        
        <!-- Language Switcher -->
        <div class="relative inline-block text-left" id="lang-dropdown-wrapper">
          <button type="button" onclick="I18n.toggleLangMenu()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-full bg-slate-100 hover:bg-slate-200 border border-slate-200 text-xs font-bold text-slate-700 transition-colors whitespace-nowrap">
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
          <a href="dashboard.php" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all whitespace-nowrap shimmer-btn">
            <span data-i18n="nav_dashboard">Ir a mi Panel</span>
            <span>→</span>
          </a>
        <?php else: ?>
          <a href="login.php" data-i18n="nav_login" class="text-xs sm:text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 rounded-xl hover:bg-slate-100 transition-colors whitespace-nowrap hidden md:inline-block">
            Iniciar sesión
          </a>
          <a href="login.php" data-i18n="nav_cta" class="hidden sm:inline-flex items-center gap-2 px-4 sm:px-5 py-2 sm:py-2.5 rounded-full text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 hover:shadow-md transition-all whitespace-nowrap shimmer-btn">
            <span>Comienza gratis</span>
          </a>
        <?php endif; ?>

        <!-- Mobile Menu Toggle Button -->
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

    <!-- Mobile Drawer -->
    <div id="mobile-nav-drawer" class="lg:hidden hidden bg-white/98 backdrop-blur-2xl border-b border-slate-200 shadow-2xl px-5 py-6 transition-all max-h-[85vh] overflow-y-auto">
      <div class="flex flex-col space-y-2.5 text-slate-700 text-sm">
        
        <div class="border border-slate-100 rounded-2xl bg-slate-50/70 overflow-hidden">
          <button type="button" onclick="MobileNav.toggleAccordion('prod')" class="w-full px-4 py-3 font-bold text-slate-800 flex items-center justify-between hover:bg-slate-100/80 transition-colors">
            <span class="flex items-center gap-2">
              <span class="text-base">⚡</span>
              <span data-i18n="nav_menu_product">Producto</span>
            </span>
            <svg id="mob-acc-icon-prod" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="mob-acc-content-prod" class="hidden px-4 pb-3 pt-1 space-y-2 text-xs border-t border-slate-100">
            <a href="index.php#funciones" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">📥</span>
              <span data-i18n="nav_mega_feat1_title">Bandeja Unificada</span>
            </a>
            <a href="index.php#funciones" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🎭</span>
              <span data-i18n="nav_mega_feat2_title">Voz de Marca Calibrada</span>
            </a>
            <a href="index.php#funciones" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🎯</span>
              <span data-i18n="nav_mega_feat3_title">Detección de Leads</span>
            </a>
            <a href="index.php#funciones" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">⏱️</span>
              <span data-i18n="nav_mega_feat4_title">Smart Timing & Antiban</span>
            </a>
          </div>
        </div>

        <div class="border border-slate-100 rounded-2xl bg-slate-50/70 overflow-hidden">
          <button type="button" onclick="MobileNav.toggleAccordion('sol')" class="w-full px-4 py-3 font-bold text-slate-800 flex items-center justify-between hover:bg-slate-100/80 transition-colors">
            <span class="flex items-center gap-2">
              <span class="text-base">💼</span>
              <span data-i18n="nav_menu_solutions">Soluciones</span>
            </span>
            <svg id="mob-acc-icon-sol" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="mob-acc-content-sol" class="hidden px-4 pb-3 pt-1 space-y-2 text-xs border-t border-slate-100">
            <a href="index.php#por-que-xindro" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🎨</span>
              <span data-i18n="nav_mega_sol1_title">Para Creadores e Influencers</span>
            </a>
            <a href="index.php#por-que-xindro" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🏢</span>
              <span data-i18n="nav_mega_sol2_title">Para Agencias & CMs</span>
            </a>
            <a href="index.php#por-que-xindro" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🛍️</span>
              <span data-i18n="nav_mega_sol3_title">Para Marcas & E-commerce</span>
            </a>
            <a href="index.php#por-que-xindro" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🎓</span>
              <span data-i18n="nav_mega_sol4_title">Para Coaches & Educadores</span>
            </a>
          </div>
        </div>

        <div class="border border-slate-100 rounded-2xl bg-slate-50/70 overflow-hidden">
          <button type="button" onclick="MobileNav.toggleAccordion('emp')" class="w-full px-4 py-3 font-bold text-slate-800 flex items-center justify-between hover:bg-slate-100/80 transition-colors">
            <span class="flex items-center gap-2">
              <span class="text-base">🏛️</span>
              <span data-i18n="nav_menu_company">Empresa</span>
            </span>
            <svg id="mob-acc-icon-emp" class="w-4 h-4 text-slate-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="mob-acc-content-emp" class="hidden px-4 pb-3 pt-1 space-y-2 text-xs border-t border-slate-100">
            <a href="about.php" class="flex items-center justify-between py-2 px-2.5 rounded-xl bg-purple-50 text-brand-700 font-semibold transition-colors">
              <span class="flex items-center gap-2.5">
                <span class="text-sm">✨</span>
                <span data-i18n="nav_mega_comp1_title">Acerca de XINDRO</span>
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-brand-200 text-brand-800">Activo</span>
            </a>
            <a href="privacy-policy.php" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">🛡️</span>
              <span data-i18n="nav_mega_comp2_title">Seguridad & Privacidad</span>
            </a>
            <a href="terms-of-service.php" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">📜</span>
              <span data-i18n="nav_mega_comp3_title">Términos del Servicio</span>
            </a>
            <a href="index.php#faq" class="flex items-center gap-2.5 py-2 px-2.5 rounded-xl hover:bg-white text-slate-700 font-semibold transition-colors">
              <span class="text-sm">❓</span>
              <span data-i18n="nav_mega_comp4_title">Preguntas Frecuentes</span>
            </a>
          </div>
        </div>

        <a href="index.php#simulador" class="p-3 rounded-2xl hover:bg-slate-100 font-bold text-slate-800 transition-colors flex items-center justify-between">
          <span class="flex items-center gap-2.5">
            <span class="text-base">✨</span>
            <span data-i18n="nav_simulator">Simulador en Vivo</span>
          </span>
          <span class="w-2.5 h-2.5 rounded-full bg-brand-500 live-dot"></span>
        </a>

        <a href="index.php#precios" class="p-3 rounded-2xl hover:bg-slate-100 font-bold text-slate-800 transition-colors flex items-center justify-between">
          <span class="flex items-center gap-2.5">
            <span class="text-base">💎</span>
            <span data-i18n="nav_pricing">Precios y Planes</span>
          </span>
          <span class="text-slate-400">→</span>
        </a>

      </div>

      <div class="mt-5 pt-4 border-t border-slate-200 flex flex-col gap-2.5">
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
  <!-- 2. HERO SECTION: MISIÓN & VISIÓN -->
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
  <!-- 3. EL PROBLEMA Y POR QUÉ EXISTE XINDRO -->
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
  <!-- 4. NUESTRA TRAYECTORIA / TIMELINE -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      
      <div class="text-center max-w-3xl mx-auto mb-14">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold uppercase mb-3">
          <span>🚀 Nuestra Evolución</span>
        </div>
        <h2 class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mb-3" data-i18n="about_time_h2">
          De un Experimento Interno a un Copilot SaaS
        </h2>
        <p class="text-sm sm:text-base text-slate-600" data-i18n="about_time_sub">
          Cómo evolucionó XINDRO para resolver los dolores de escala de agencias y creadores en todo el mundo.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Milestone 1 -->
        <div class="bento-card p-6 sm:p-7 rounded-2xl bg-white space-y-3 relative overflow-hidden">
          <div class="text-xs font-mono font-bold text-brand-600 bg-brand-50 px-3 py-1 rounded-full inline-block">
            2024 • FASE 1
          </div>
          <h3 class="text-base font-black text-slate-900" data-i18n="about_time_m1_t">El Descubrimiento de la Brecha</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_time_m1_d">
            Observamos que los creadores con más de 10k seguidores perdían el 70% de las consultas de compra en comentarios debido a la imposibilidad de responder a tiempo.
          </p>
        </div>

        <!-- Milestone 2 -->
        <div class="bento-card p-6 sm:p-7 rounded-2xl bg-white space-y-3 relative overflow-hidden">
          <div class="text-xs font-mono font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full inline-block">
            2025 • FASE 2
          </div>
          <h3 class="text-base font-black text-slate-900" data-i18n="about_time_m2_t">Arquitectura Heurística & Meta Graph</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_time_m2_d">
            Desarrollamos el motor heurístico de sub-180ms con fallback y blindaje criptográfico HMAC-SHA256 bajo la API oficial Meta Graph v19+.
          </p>
        </div>

        <!-- Milestone 3 -->
        <div class="bento-card p-6 sm:p-7 rounded-2xl bg-white space-y-3 relative overflow-hidden border-brand-300 shadow-glow-sm">
          <div class="text-xs font-mono font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full inline-block">
            2026 • FASE 3 (ACTUAL)
          </div>
          <h3 class="text-base font-black text-slate-900" data-i18n="about_time_m3_t">XINDRO Copilot Multi-Tenant</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_time_m3_d">
            Lanzamiento del sistema operativo integral: bandejas unificadas, calibradores de voz independientes para agencias y soporte multi-idioma (ES/EN/PT).
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 5. LOS 4 PILARES FUNDACIONALES DE XINDRO -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-slate-50/70 border-b border-slate-200/80">
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
  <!-- 6. ARQUITECTURA TÉCNICA DEL MOTOR XINDRO -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-slate-900 text-white relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 relative z-10">
      
      <div class="text-center max-w-3xl mx-auto mb-14">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-900/80 border border-brand-500/40 text-brand-300 text-xs font-bold uppercase mb-3">
          <span>🧠 Pipeline de IA</span>
        </div>
        <h2 class="text-2xl sm:text-4xl font-extrabold tracking-tight mb-3" data-i18n="about_arch_h2">
          Cómo Opera el Pipeline de XINDRO
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
  <!-- 7. NUESTROS VALORES DE INGENIERÍA -->
  <!-- ========================================================================= -->
  <section class="py-16 md:py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
      
      <div class="text-center max-w-3xl mx-auto mb-14">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold uppercase mb-3">
          <span>⚙️ Cultura Técnica</span>
        </div>
        <h2 class="text-2xl sm:text-4xl font-extrabold text-midnight tracking-tight mb-3" data-i18n="about_val_h2">
          Nuestros Valores de Ingeniería
        </h2>
        <p class="text-sm sm:text-base text-slate-600" data-i18n="about_val_sub">
          Construimos software eficiente, seguro y de alto rendimiento que respeta los recursos y la privacidad del usuario.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
          <div class="text-2xl">⚡</div>
          <h3 class="text-base font-black text-slate-900" data-i18n="about_val1_t">Velocidad sin Sobrecarga</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_val1_d">
            Construido en PHP 8.2 nativo con SQLite WAL Mode. Sin dependencias innecesarias, entregando respuestas en milisegundos y con un consumo de recursos mínimo.
          </p>
        </div>

        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
          <div class="text-2xl">🔒</div>
          <h3 class="text-base font-black text-slate-900" data-i18n="about_val2_t">Privacidad Criptográfica</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_val2_d">
            Tokens cifrados con AES-256-GCM y cumplimiento riguroso de normativas internacionales de protección de datos (RGPD, CCPA y LGPD Brasil).
          </p>
        </div>

        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
          <div class="text-2xl">✨</div>
          <h3 class="text-base font-black text-slate-900" data-i18n="about_val3_t">Diseño & Experiencia Sublime</h3>
          <p class="text-xs sm:text-sm text-slate-600 leading-relaxed" data-i18n="about_val3_d">
            Una interfaz inspirada en herramientas líderes mundiales como Gamma y Linear, diseñada para deleitar la vista y acelerar el flujo de trabajo diario.
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 8. FINAL CTA BANNER -->
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
  <!-- 9. COSMIC STARRY FOOTER -->
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
  <!-- JAVASCRIPT: MOBILE NAV & I18N DICTIONARY -->
  <!-- ========================================================================= -->
  <script>
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
      },
      toggleAccordion(key) {
        const content = document.getElementById('mob-acc-content-' + key);
        const icon = document.getElementById('mob-acc-icon-' + key);
        if (!content) return;
        const isHidden = content.classList.contains('hidden');
        
        ['prod', 'sol', 'emp'].forEach(k => {
          const c = document.getElementById('mob-acc-content-' + k);
          const i = document.getElementById('mob-acc-icon-' + k);
          if (c) c.classList.add('hidden');
          if (i) i.style.transform = 'rotate(0deg)';
        });

        if (isHidden) {
          content.classList.remove('hidden');
          if (icon) icon.style.transform = 'rotate(180deg)';
        }
      }
    };

    const I18n = {
      current: '<?= $initialLang ?>',
      dict: {
        es: {
          page_title: "Acerca de XINDRO — Nuestra Visión, Tecnología y Misión",
          nav_menu_product: "Producto",
          nav_menu_solutions: "Soluciones",
          nav_menu_company: "Empresa",
          nav_simulator: "Simulador",
          nav_pricing: "Precios",
          nav_login: "Iniciar sesión",
          nav_cta: "Comienza gratis",
          nav_dashboard: "Ir a mi Panel",
          nav_mega_feat1_title: "Bandeja Unificada",
          nav_mega_feat1_desc: "Gestiona Instagram y Facebook en tiempo real desde un solo lugar.",
          nav_mega_feat2_title: "Voz de Marca Calibrada",
          nav_mega_feat2_desc: "Respuestas auténticas con datos de tu negocio y anti-alucinación.",
          nav_mega_feat3_title: "Detección de Leads & Compras",
          nav_mega_feat3_desc: "Identifica oportunidades comerciales en comentarios en <180ms.",
          nav_mega_feat4_title: "Smart Timing & Antiban",
          nav_mega_feat4_desc: "Publica en la ventana de oro oficial del algoritmo de Meta.",
          nav_mega_card_sim_title: "Simulador de IA en Vivo",
          nav_mega_card_sim_desc: "Prueba cómo responde la IA a comentarios de tu audiencia en segundos.",
          nav_mega_card_sim_btn: "Probar Simulador →",
          nav_mega_sol1_title: "Creadores e Influencers",
          nav_mega_sol1_desc: "Mantén tu comunidad activa y fidelizada sin pasar horas respondiendo.",
          nav_mega_sol2_title: "Agencias & Community Managers",
          nav_mega_sol2_desc: "Administra múltiples clientes y marcas con total aislamiento de datos.",
          nav_mega_sol3_title: "Marcas & E-commerce",
          nav_mega_sol3_desc: "Convierte dudas en compras y acelera la atención al cliente.",
          nav_mega_sol4_title: "Coaches & Infoproductores",
          nav_mega_sol4_desc: "Vende formaciones y cursos 24/7 respondiendo preguntas clave.",
          nav_mega_card_roi_title: "Calculadora de Ahorro",
          nav_mega_card_roi_desc: "Calcula cuánto tiempo y leads calificados puedes ganar al mes.",
          nav_mega_card_roi_btn: "Calcular Impacto →",
          nav_mega_comp1_title: "Acerca de XINDRO",
          nav_mega_comp1_desc: "Nuestra visión, misión, tecnología de IA y equipo.",
          nav_mega_comp2_title: "Seguridad & Privacidad",
          nav_mega_comp2_desc: "Cumplimiento estricto GDPR, Meta API oficial y cifrado de datos.",
          nav_mega_comp3_title: "Términos del Servicio",
          nav_mega_comp3_desc: "Transparencia legal, garantías y condiciones de uso.",
          nav_mega_comp4_title: "Preguntas Frecuentes",
          nav_mega_comp4_desc: "Respuestas claras sobre funcionamiento, límites y planes.",
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
          about_time_h2: "De un Experimento Interno a un Copilot SaaS",
          about_time_sub: "Cómo evolucionó XINDRO para resolver los dolores de escala de agencias y creadores en todo el mundo.",
          about_time_m1_t: "El Descubrimiento de la Brecha",
          about_time_m1_d: "Observamos que los creadores con más de 10k seguidores perdían el 70% de las consultas de compra en comentarios debido a la imposibilidad de responder a tiempo.",
          about_time_m2_t: "Arquitectura Heurística & Meta Graph",
          about_time_m2_d: "Desarrollamos el motor heurístico de sub-180ms con fallback y blindaje criptográfico HMAC-SHA256 bajo la API oficial Meta Graph v19+.",
          about_time_m3_t: "XINDRO Copilot Multi-Tenant",
          about_time_m3_d: "Lanzamiento del sistema operativo integral: bandejas unificadas, calibradores de voz independientes para agencias y soporte multi-idioma (ES/EN/PT).",
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
          about_arch_h2: "Cómo Opera el Pipeline de XINDRO",
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
          about_val_h2: "Nuestros Valores de Ingeniería",
          about_val_sub: "Construimos software eficiente, seguro y de alto rendimiento que respeta los recursos y la privacidad del usuario.",
          about_val1_t: "Velocidad sin Sobrecarga",
          about_val1_d: "Construido en PHP 8.2 nativo con SQLite WAL Mode. Sin dependencias innecesarias, entregando respuestas en milisegundos y con un consumo de recursos mínimo.",
          about_val2_t: "Privacidad Criptográfica",
          about_val2_d: "Tokens cifrados con AES-256-GCM y cumplimiento riguroso de normativas internacionales de protección de datos (RGPD, CCPA y LGPD Brasil).",
          about_val3_t: "Diseño & Experiencia Sublime",
          about_val3_d: "Una interfaz inspirada en herramientas líderes mundiales como Gamma y Linear, diseñada para deleitar la vista y acelerar el flujo de trabajo diario.",
          about_final_h2: "Únete a la evolución del engagement en redes sociales",
          about_final_sub: "Comienza gratis hoy mismo y descubre la tranquilidad de tener un Copilot que cuida tu comunidad las 24 horas del día.",
          about_final_btn: "Crear Cuenta Gratis",
          foot_brand_desc: "El sistema operativo de IA para creadores y agencias de redes sociales. Respuestas en tiempo real, Smart Timing y sincronización oficial con Meta Graph API.",
          foot_status_pill: "Meta API 100% Operativa"
        },
        en: {
          page_title: "About XINDRO — Our Vision, AI Technology & Mission",
          nav_menu_product: "Product",
          nav_menu_solutions: "Solutions",
          nav_menu_company: "Company",
          nav_simulator: "Simulator",
          nav_pricing: "Pricing",
          nav_login: "Log in",
          nav_cta: "Get started free",
          nav_dashboard: "Go to Dashboard",
          nav_mega_feat1_title: "Unified Multi-Channel Inbox",
          nav_mega_feat1_desc: "Manage Instagram and Facebook comments in real-time in one place.",
          nav_mega_feat2_title: "Calibrated Brand Voice",
          nav_mega_feat2_desc: "Authentic responses grounded in your business facts with anti-hallucination.",
          nav_mega_feat3_title: "Lead & Purchase Detection",
          nav_mega_feat3_desc: "Identify high-intent buyer questions in comments in under 180ms.",
          nav_mega_feat4_title: "Smart Timing & Antiban",
          nav_mega_feat4_desc: "Post within Meta's golden algorithmic engagement window.",
          nav_mega_card_sim_title: "Live AI Playground",
          nav_mega_card_sim_desc: "Test how calibrated AI crafts responses to your community in seconds.",
          nav_mega_card_sim_btn: "Open Simulator →",
          nav_mega_sol1_title: "Creators & Influencers",
          nav_mega_sol1_desc: "Keep your community engaged without spending hours in comment replies.",
          nav_mega_sol2_title: "Agencies & Community Managers",
          nav_mega_sol2_desc: "Manage multi-brand clients with isolated knowledge bases.",
          nav_mega_sol3_title: "Brands & E-commerce",
          nav_mega_sol3_desc: "Convert product inquiries into sales and speed up support.",
          nav_mega_sol4_title: "Coaches & Educators",
          nav_mega_sol4_desc: "Sell courses and programs 24/7 by resolving high-intent questions.",
          nav_mega_card_roi_title: "Impact Calculator",
          nav_mega_card_roi_desc: "Calculate how many hours and qualified leads you gain every month.",
          nav_mega_card_roi_btn: "Calculate Impact →",
          nav_mega_comp1_title: "About XINDRO",
          nav_mega_comp1_desc: "Our mission, AI architecture, and engineering philosophy.",
          nav_mega_comp2_title: "Security & Privacy",
          nav_mega_comp2_desc: "GDPR compliance, official Meta API, and cryptographic token isolation.",
          nav_mega_comp3_title: "Terms of Service",
          nav_mega_comp3_desc: "Legal transparency, usage conditions, and SLA.",
          nav_mega_comp4_title: "Frequently Asked Questions",
          nav_mega_comp4_desc: "Clear answers about platform capabilities and pricing.",
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
          about_time_h2: "From an Internal Experiment to an AI Copilot SaaS",
          about_time_sub: "How XINDRO evolved to solve scaling pain points for agencies and creators worldwide.",
          about_time_m1_t: "Discovering the Scale Gap",
          about_time_m1_d: "We noticed creators with 10k+ followers lost 70% of buyer inquiries in comments because they couldn't respond in time.",
          about_time_m2_t: "Heuristic Architecture & Meta Graph",
          about_time_m2_d: "Built the sub-180ms heuristic engine with instant fallback and HMAC-SHA256 signature verification via official Meta Graph API v19+.",
          about_time_m3_t: "XINDRO Multi-Tenant Copilot",
          about_time_m3_d: "Launched the full operating system: unified inboxes, isolated brand voice calibrators for agencies, and multi-language support.",
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
          about_val_h2: "Our Engineering Values",
          about_val_sub: "We build efficient, reliable, high-performance software that respects user privacy and resources.",
          about_val1_t: "Velocity Without Bloat",
          about_val1_d: "Engineered in native PHP 8.2 with SQLite WAL Mode. Zero bloated dependencies, delivering sub-second replies with minimal memory footprint.",
          about_val2_t: "Cryptographic Privacy",
          about_val2_d: "AES-256-GCM token encryption and strict compliance with global privacy regulations (GDPR, CCPA, and Brazil LGPD).",
          about_val3_t: "Sublime UI & Craft",
          about_val3_d: "Interfaces inspired by world-class tools like Gamma and Linear, designed to delight the eyes and streamline daily workflows.",
          about_final_h2: "Join the Future of Social Media Engagement",
          about_final_sub: "Start for free today and experience the peace of mind of having an AI Copilot that nurtures your community 24/7.",
          about_final_btn: "Create Free Account",
          foot_brand_desc: "The AI operating system for creators and social media agencies. Real-time replies, Smart Timing, and official synchronization.",
          foot_status_pill: "Meta API 100% Operational"
        },
        pt: {
          page_title: "Sobre a XINDRO — Nossa Visão, Tecnologia e Missão",
          nav_menu_product: "Produto",
          nav_menu_solutions: "Soluções",
          nav_menu_company: "Empresa",
          nav_simulator: "Simulador",
          nav_pricing: "Preços",
          nav_login: "Entrar",
          nav_cta: "Comece grátis",
          nav_dashboard: "Ir ao Painel",
          nav_mega_feat1_title: "Caixa de Entrada Unificada",
          nav_mega_feat1_desc: "Centralize interações do Instagram e Facebook em tempo real.",
          nav_mega_feat2_title: "Tom de Marca Calibrado",
          nav_mega_feat2_desc: "Respostas autênticas com dados do seu negócio e anti-alucinación.",
          nav_mega_feat3_title: "Detecção de Leads e Compras",
          nav_mega_feat3_desc: "Identifique intenção comercial nos comentários em menos de 180ms.",
          nav_mega_feat4_title: "Smart Timing & Antiban",
          nav_mega_feat4_desc: "Publique na janela de ouro oficial do algoritmo da Meta.",
          nav_mega_card_sim_title: "Simulador de IA ao Vivo",
          nav_mega_card_sim_desc: "Veja como a IA responde aos seus seguidores em tempo real.",
          nav_mega_card_sim_btn: "Abrir Simulador →",
          nav_mega_sol1_title: "Criadores e Influenciadores",
          nav_mega_sol1_desc: "Mantenha sua comunidade engajada sem gastar horas respondendo.",
          nav_mega_sol2_title: "Agências & Gestores",
          nav_mega_sol2_desc: "Gerencie múltiplos clientes com isolamento total de marcas.",
          nav_mega_sol3_title: "Marcas & E-commerce",
          nav_mega_sol3_desc: "Converta dúvidas em vendas e acelere o suporte ao cliente.",
          nav_mega_sol4_title: "Coaches & Infoprodutores",
          nav_mega_sol4_desc: "Venda cursos e mentorias 24/7 respondendo dúvidas estratégicas.",
          nav_mega_card_roi_title: "Calculadora de Impacto",
          nav_mega_card_roi_desc: "Calcule quantas horas e leads qualificados você ganha por mês.",
          nav_mega_card_roi_btn: "Calcular Impacto →",
          nav_mega_comp1_title: "Sobre a XINDRO",
          nav_mega_comp1_desc: "Nossa visão, tecnologia de IA e filosofia de engenharia.",
          nav_mega_comp2_title: "Segurança & Privacidade",
          nav_mega_comp2_desc: "Conformidade LGPD, API oficial da Meta e criptografia.",
          nav_mega_comp3_title: "Termos de Serviço",
          nav_mega_comp3_desc: "Transparência legal, garantias e condições de uso.",
          nav_mega_comp4_title: "Perguntas Frecuentes",
          nav_mega_comp4_desc: "Respostas claras sobre planos, recursos e funcionamento.",
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
          about_time_h2: "De um Experimento Interno a um Copilot SaaS",
          about_time_sub: "Como a XINDRO evoluiu para atender agências e criadores em escala global.",
          about_time_m1_t: "Identificação do Gargalo",
          about_time_m1_d: "Notamos que criadores com 10k+ seguidores perdiam 70% das oportunidades de venda por falta de tempo para responder.",
          about_time_m2_t: "Arquitetura Heurística & Meta Graph",
          about_time_m2_d: "Desenvolvimento do motor heurístico de sub-180ms com validação HMAC-SHA256 e integração oficial com a Meta Graph API v19+.",
          about_time_m3_t: "XINDRO Copilot Multi-Tenant",
          about_time_m3_d: "Lançamento do sistema operacional completo com bandejas unificadas, múltiplos tons de marca e suporte a 3 idiomas.",
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
          about_val_h2: "Nossos Valores de Engenharia",
          about_val_sub: "Construímos software eficiente e seguro que respeita os recursos e a privacidade do usuário.",
          about_val1_t: "Velocidade sem Sobrecarga",
          about_val1_d: "Construído em PHP 8.2 nativo com SQLite WAL Mode. Sem dependências pesadas, entregando respostas em milisegundos.",
          about_val2_t: "Privacidade Criptográfica",
          about_val2_d: "Criptografia AES-256-GCM de tokens e conformidade com LGPD, GDPR e CCPA.",
          about_val3_t: "Design e Experiência Superior",
          about_val3_d: "Interface moderna e fluida inspirada em referências globais como Gamma e Linear.",
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
