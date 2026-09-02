<?php
/**
 * Xindro — El Sistema Operativo de IA para Creadores de Contenido y Redes Sociales
 * Landing Page de Alto Impacto Visual (Gamma.app Style + Giant Watermark + Cookie Popup)
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';

Security::applySecurityHeaders(false);
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>XINDRO — El Sistema Operativo de IA para Creadores de Contenido</title>
  <meta name="description" content="Automatiza tus redes sociales. Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y publica en múltiples plataformas desde una sola API.">
  
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
            'cookie-popup': '0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(226, 232, 240, 0.9)',
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

    /* Gamma-style ambient radial gradient mesh */
    .hero-mesh-bg {
      background: radial-gradient(circle at 50% -10%, rgba(139, 92, 246, 0.12) 0%, rgba(248, 250, 252, 0) 65%),
                  radial-gradient(circle at 90% 20%, rgba(56, 189, 248, 0.08) 0%, rgba(255, 255, 255, 0) 50%),
                  radial-gradient(circle at 10% 30%, rgba(168, 85, 247, 0.08) 0%, rgba(255, 255, 255, 0) 50%);
    }

    .dark-mesh-bg {
      background: radial-gradient(circle at 50% 0%, rgba(139, 92, 246, 0.18) 0%, rgba(11, 15, 25, 1) 70%);
    }

    /* Gamma-style Starry Night Footer Background */
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
      background: rgba(255, 255, 255, 0.88);
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
      background: linear-gradient(180deg, rgba(165, 180, 252, 0.45) 0%, rgba(165, 180, 252, 0.08) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      user-select: none;
      pointer-events: none;
    }

    /* Cookie popup entrance animation */
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
      animation: slideUpCookie 0.45s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    /* Code block styling */
    pre code {
      font-family: 'JetBrains Mono', monospace;
    }

    /* Custom smooth scrollbar */
    ::-webkit-scrollbar {
      width: 8px;
    }
    ::-webkit-scrollbar-track {
      background: #F8FAFC;
    }
    ::-webkit-scrollbar-thumb {
      background: #CBD5E1;
      border-radius: 4px;
    }
    ::-webkit-scrollbar-thumb:hover {
      background: #94A3B8;
    }

    /* Pulse animation for live badge */
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
  <!-- 1. NAVBAR FIJA (Estilo Gamma.app) -->
  <!-- ========================================================================= -->
  <header class="fixed top-0 left-0 right-0 z-50 glass-nav transition-all duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
      
      <!-- Gamma-Style Minimalist Logo: XINDRO -->
      <a href="index.php" class="flex items-center gap-3 group">
        <!-- Geometric Gamma-Style Symbol & Typography -->
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
      <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-600">
        <a href="#funciones" class="hover:text-brand-600 transition-colors">Productos</a>
        <a href="#simulador" class="hover:text-brand-600 transition-colors flex items-center gap-1.5">
          Simulador
          <span class="inline-block w-2 h-2 rounded-full bg-brand-500 live-dot"></span>
        </a>
        <a href="#smart-timing" class="hover:text-brand-600 transition-colors">Soluciones</a>
        <a href="#api-docs" class="hover:text-brand-600 transition-colors">API Creadores</a>
        <a href="#precios" class="hover:text-brand-600 transition-colors">Precios</a>
      </nav>

      <!-- Right Action CTA -->
      <div class="flex items-center gap-3">
        <?php if ($isLoggedIn): ?>
          <a href="dashboard.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all">
            <span>Ir a mi Panel</span>
            <span>→</span>
          </a>
        <?php else: ?>
          <a href="login.php" class="text-sm font-bold text-slate-700 hover:text-brand-600 px-3 py-2 transition-colors">
            Iniciar sesión
          </a>
          <a href="login.php" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-full text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 hover:shadow-md transition-all">
            <span>Comienza gratis</span>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <!-- ========================================================================= -->
  <!-- 2. HERO SECTION (Gancho Visual de Alto Impacto) -->
  <!-- ========================================================================= -->
  <section class="relative pt-36 pb-20 md:pt-44 md:pb-28 hero-mesh-bg overflow-hidden border-b border-slate-100">
    
    <!-- Ambient Glow Blobs -->
    <div class="absolute top-20 left-1/2 -translate-x-1/2 w-[650px] h-[350px] bg-brand-400/10 blur-[120px] rounded-full pointer-events-none -z-10"></div>
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      
      <!-- Top Pill Badge -->
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-brand-50 border border-brand-200/80 text-brand-700 text-xs sm:text-sm font-bold mb-8 shadow-sm hover:border-brand-300 transition-colors">
        <span class="flex h-2 w-2 relative">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-2 w-2 bg-brand-500"></span>
        </span>
        <span>El sistema operativo de IA para creadores de contenido</span>
      </div>

      <!-- Main Headline (H1) -->
      <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight text-midnight leading-[1.12] mb-6 max-w-4xl mx-auto">
        Automatiza tus redes sociales. <br class="hidden sm:inline" />
        <span class="gradient-text">Escala tu comunidad sin perder el toque humano.</span>
      </h1>

      <!-- Subtitle (P) -->
      <p class="text-lg sm:text-xl text-slate-600 max-w-2xl mx-auto mb-10 leading-relaxed font-normal">
        Responde comentarios en piloto automático, analiza métricas de engagement para encontrar tu horario perfecto y publica en múltiples plataformas desde una sola API.
      </p>

      <!-- CTAs Button Group -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto mb-16">
        <a href="#simulador" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-8 py-4 rounded-xl text-base font-bold text-white gradient-button shadow-glow-md">
          <span>Prueba el Simulador</span>
          <span class="text-lg">✨</span>
        </a>
        <a href="#api-docs" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl text-base font-bold text-slate-700 bg-white border border-slate-200 hover:border-brand-300 hover:bg-slate-50 hover:text-brand-700 shadow-sm transition-all">
          <span>Documentación API</span>
          <span>&lt;/&gt;</span>
        </a>
      </div>

      <!-- Live Interactive Visual Hero Card (Gamma Style) -->
      <div class="relative max-w-4xl mx-auto rounded-2xl bg-white border border-slate-200/90 shadow-elevated-card p-4 sm:p-6 md:p-8 text-left">
        
        <!-- Header of Card -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
          <div class="flex items-center gap-3">
            <div class="w-3 h-3 rounded-full bg-red-400"></div>
            <div class="w-3 h-3 rounded-full bg-amber-400"></div>
            <div class="w-3 h-3 rounded-full bg-emerald-400"></div>
            <span class="text-xs font-semibold text-slate-400 ml-2">XINDRO Live Copilot — Flujo en Tiempo Real</span>
          </div>
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 live-dot"></span>
            <span>Meta Webhook Activo</span>
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
                <div class="text-[10px] text-slate-500">Instagram • Hace 2 seg</div>
              </div>
              <span class="ml-auto text-[11px] font-bold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200/60">
                Score: 96/100
              </span>
            </div>
            <p class="text-xs text-slate-700 leading-relaxed font-medium">
              "Llevo semanas intentando ser constante en mis redes pero me quedo sin ideas y pierdo motivación. ¿Cómo estructuran su rutina diaria?"
            </p>
            <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-center justify-between text-[11px] text-slate-500 font-semibold">
              <span>🎯 Intención: <strong class="text-brand-600">Pregunta de Alto Valor</strong></span>
              <span class="text-emerald-600 font-bold">⚡ Latencia: 142ms</span>
            </div>
          </div>

          <!-- Flow Arrow (Center) -->
          <div class="md:col-span-2 flex flex-col items-center justify-center text-brand-600">
            <div class="w-9 h-9 rounded-full bg-brand-50 border border-brand-200 flex items-center justify-center font-bold text-sm shadow-sm">
              ⚡
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 mt-1">IA Calibrada</span>
          </div>

          <!-- Generated AI Reply (Right) -->
          <div class="md:col-span-5 bg-gradient-to-br from-brand-50/70 to-indigo-50/50 rounded-xl p-4 border border-brand-200/80">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xs font-bold text-brand-800 flex items-center gap-1.5">
                <span>🤖 Respuesta con Voz de Marca</span>
              </span>
              <span class="ml-auto text-[10px] font-bold text-brand-700 bg-brand-100/70 px-2 py-0.5 rounded-full">
                Tono: Mentor Empático
              </span>
            </div>
            <p class="text-xs text-brand-950 leading-relaxed font-medium">
              "Alejandro, la clave no es la motivación que va y viene, sino los sistemas. Bloquea 45 min cada mañana antes de revisar el móvil. La disciplina diaria supera a la inspiración esporádica. ¿Qué es lo primero que harás mañana al despertar? 👇"
            </p>
            <div class="mt-3 pt-2 border-t border-brand-200/50 flex items-center justify-between text-[11px]">
              <span class="text-brand-700 font-semibold">🚀 Retención estimada: <strong class="text-brand-900">+380%</strong></span>
              <span class="text-emerald-600 font-bold">✔ Listo para postear</span>
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
          <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Comentarios Respondidos
          </p>
        </div>

        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-brand-600 tracking-tight mb-1">
            3.4x
          </div>
          <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Aumento en Engagement
          </p>
        </div>

        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-midnight tracking-tight mb-1">
            99.8%
          </div>
          <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
            Precisión de Voz Humana
          </p>
        </div>

        <div class="p-2">
          <div class="text-3xl sm:text-4xl font-black text-emerald-600 tracking-tight mb-1">
            &lt; 180ms
          </div>
          <p class="text-xs sm:text-sm font-semibold text-slate-500 uppercase tracking-wider">
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
        <span class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Infraestructura de Nueva Generación
        </span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-midnight tracking-tight mt-4 mb-4">
          Todo lo que un creador moderno necesita para dominar el algoritmo.
        </h2>
        <p class="text-base sm:text-lg text-slate-600 font-normal">
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
            <h3 class="text-xl font-bold text-midnight mb-3">
              Auto-Engagement Contextual
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed font-normal mb-6">
              IA que responde comentarios de forma natural y contextual en tus posts. Filtra spam, detecta intención de compra y fideliza seguidores 24/7 con tu propia voz de marca.
            </p>
          </div>
          <ul class="space-y-2.5 text-xs text-slate-600 font-semibold border-t border-slate-200 pt-5">
            <li class="flex items-center gap-2">
              <span class="text-emerald-500 font-bold">✔</span> Calibración de Calidez, Profundidad y Energía
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-500 font-bold">✔</span> Detección instantánea de Leads y Preguntas
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-500 font-bold">✔</span> Modo Copilot (Sugerencias) y Autopilot
            </li>
          </ul>
        </div>

        <!-- Card 2: Smart Timing -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-8 hover:border-brand-300 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-14 h-14 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl font-bold mb-6 group-hover:scale-110 transition-transform shadow-sm">
              ⏰
            </div>
            <h3 class="text-xl font-bold text-midnight mb-3">
              Smart Timing (Horarios Óptimos)
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed font-normal mb-6">
              Análisis profundo de métricas e histórico de interacciones para recomendar el segundo exacto de publicación según los picos de actividad de tu audiencia real.
            </p>
          </div>
          <ul class="space-y-2.5 text-xs text-slate-600 font-semibold border-t border-slate-200 pt-5">
            <li class="flex items-center gap-2">
              <span class="text-blue-500 font-bold">✔</span> Mapas de calor de engagement por hora y día
            </li>
            <li class="flex items-center gap-2">
              <span class="text-blue-500 font-bold">✔</span> Predicción de alcance orgánico antes de publicar
            </li>
            <li class="flex items-center gap-2">
              <span class="text-blue-500 font-bold">✔</span> Alertas de ventanas de tráfico de alta retención
            </li>
          </ul>
        </div>

        <!-- Card 3: Multi-Publishing -->
        <div class="rounded-2xl bg-slatecard border border-slate-200/90 p-8 hover:border-brand-300 hover:shadow-elevated-card transition-all duration-300 flex flex-col justify-between group">
          <div>
            <div class="w-14 h-14 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl font-bold mb-6 group-hover:scale-110 transition-transform shadow-sm">
              🚀
            </div>
            <h3 class="text-xl font-bold text-midnight mb-3">
              Multi-Publishing & Copys IA
            </h3>
            <p class="text-sm text-slate-600 leading-relaxed font-normal mb-6">
              Sube una sola imagen o texto y la IA genera los copys adaptados al algoritmo de cada red social (Instagram, TikTok, Facebook) publicando en simultáneo.
            </p>
          </div>
          <ul class="space-y-2.5 text-xs text-slate-600 font-semibold border-t border-slate-200 pt-5">
            <li class="flex items-center gap-2">
              <span class="text-emerald-500 font-bold">✔</span> Adaptación de ganchos (Hooks) y llamadas a la acción
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-500 font-bold">✔</span> Selección inteligente de hashtags virales
            </li>
            <li class="flex items-center gap-2">
              <span class="text-emerald-500 font-bold">✔</span> Distribución omnicanal con 1 solo clic
            </li>
          </ul>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 5. EL SIMULADOR INTERACTIVO (El corazón de la conversión) -->
  <!-- ========================================================================= -->
  <section id="simulador" class="py-24 bg-gradient-to-b from-slatecard to-white border-b border-slate-200/80">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-12">
        <span class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Playground en Vivo
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-3">
          Prueba el Motor de XINDRO en Tiempo Real
        </h2>
        <p class="text-sm sm:text-base text-slate-600 font-normal">
          Selecciona un tono, escribe cualquier comentario de tu comunidad y observa cómo la IA genera respuestas hipercontextualizadas.
        </p>
      </div>

      <!-- Simulator Card Container -->
      <div class="bg-white rounded-3xl border border-slate-200 shadow-elevated-card overflow-hidden">
        
        <!-- Simulator Top Bar -->
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
          
          <!-- Controls Row: Tone Selector & Target Platform -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                1. Tono de Marca:
              </label>
              <select id="sim-tone" class="w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer">
                <option value="mentor">🏛️ Estoico / Mentor Sabio</option>
                <option value="empathy" selected>🤝 Cercano & Empático</option>
                <option value="growth">🔥 Dinámico & Venta de Alto Valor</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                2. Plataforma:
              </label>
              <select id="sim-platform" class="w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer">
                <option value="instagram">📸 Instagram</option>
                <option value="facebook">📘 Facebook</option>
                <option value="tiktok">🎵 TikTok</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                3. Pregunta al Final:
              </label>
              <select id="sim-closing" class="w-full bg-slatecard border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm font-semibold text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all cursor-pointer">
                <option value="always" selected>Siempre incluir pregunta</option>
                <option value="relevant">Solo cuando sea relevante</option>
                <option value="never">Sin pregunta final</option>
              </select>
            </div>

          </div>

          <!-- Comment Input Area -->
          <div class="mb-6">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
              Comentario de tu seguidor a simular:
            </label>
            <div class="relative">
              <textarea id="sim-input-text" rows="3" class="w-full bg-slatecard border border-slate-300 rounded-xl p-4 text-sm font-medium text-slate-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-100 transition-all resize-none" placeholder="Escribe un comentario...">Me encanta tu contenido pero siempre procrastino mis proyectos importantes por miedo al fracaso. ¿Qué consejo me das para empezar hoy mismo?</textarea>
            </div>
          </div>

          <!-- Trigger Button & Presets -->
          <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
            <div class="flex items-center gap-2">
              <span class="text-xs font-bold text-slate-500">Comentarios rápidos:</span>
              <button type="button" onclick="Simulator.loadPreset(1)" class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                💡 "¿Precio del curso?"
              </button>
              <button type="button" onclick="Simulator.loadPreset(2)" class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors">
                🔥 "Gran reflexión"
              </button>
            </div>

            <button type="button" id="sim-btn-generate" onclick="Simulator.generate()" class="px-6 py-3 rounded-xl text-sm font-bold text-white gradient-button flex items-center gap-2 shadow-glow-sm">
              <span>Generar Respuesta con IA</span>
              <span>⚡</span>
            </button>
          </div>

          <!-- Simulator Live Output Box -->
          <div id="sim-output-card" class="rounded-2xl bg-slate-50 border border-slate-200/90 p-6 transition-all duration-300">
            
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/80 pb-3 mb-4">
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Resultado Generado por XINDRO</span>
                <span id="sim-badge-intent" class="text-[11px] font-bold px-2 py-0.5 rounded bg-brand-100 text-brand-800 border border-brand-200">
                  🎯 Intención: Consejo / Crecimiento
                </span>
              </div>
              <div class="flex items-center gap-3 text-xs font-semibold text-slate-500">
                <span>Highlight Score: <strong id="sim-badge-score" class="text-brand-600 font-bold">94/100</strong></span>
                <span class="text-emerald-600 font-bold">⚡ 120ms</span>
              </div>
            </div>

            <!-- Output Message Stream -->
            <p id="sim-output-text" class="text-sm sm:text-base text-slate-800 font-medium leading-relaxed mb-4">
              "El miedo al fracaso solo desaparece cuando actúas antes de que la mente empiece a dudar. Divide tu meta en una sola acción de 5 minutos para hoy. La perfección no existe, el progreso diario sí. ¿Qué pequeña tarea harás en los próximos 10 minutos? 👇"
            </p>

            <div class="flex items-center justify-between pt-3 border-t border-slate-200/60 text-xs text-slate-500">
              <span class="flex items-center gap-1.5 text-emerald-600 font-semibold">
                <span>✔</span> Apto para Autopilot en Instagram y Facebook
              </span>
              <button type="button" onclick="Simulator.copyResponse()" id="sim-btn-copy" class="text-xs font-bold text-brand-600 hover:text-brand-800 flex items-center gap-1 transition-colors">
                <span>📋 Copiar</span>
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
        
        <!-- Left Column: Copy & Insights -->
        <div class="lg:col-span-5">
          <span class="text-xs font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
            Algoritmo de Precisión
          </span>
          <h2 class="text-3xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-4">
            No publiques a ciegas. Publica en el segundo exacto.
          </h2>
          <p class="text-base text-slate-600 leading-relaxed font-normal mb-6">
            El Smart Timing de XINDRO cruza datos de más de 500,000 interacciones para identificar cuándo tus seguidores más valiosos están activos y listos para interactuar.
          </p>

          <div class="space-y-4">
            <div class="flex items-start gap-3.5">
              <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                📈
              </div>
              <div>
                <h4 class="text-sm font-bold text-midnight">Ventana de Máxima Retención</h4>
                <p class="text-xs text-slate-600 leading-relaxed">Publicar en el pico aumenta la retención inicial en los primeros 15 minutos en un 240%.</p>
              </div>
            </div>

            <div class="flex items-start gap-3.5">
              <div class="w-8 h-8 rounded-lg bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                🧠
              </div>
              <div>
                <h4 class="text-sm font-bold text-midnight">Alineación con el Algoritmo de Meta</h4>
                <p class="text-xs text-slate-600 leading-relaxed">Meta premia a las cuentas que responden rápido durante los picos de visualización.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Interactive Heatmap Widget -->
        <div class="lg:col-span-7 bg-slatecard rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-subtle-card">
          <div class="flex items-center justify-between mb-6">
            <div>
              <div class="text-sm font-bold text-midnight">Pico de Engagement Semanal Detectado</div>
              <div class="text-xs text-slate-500">Métricas analizadas en tiempo real</div>
            </div>
            <span class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
              +142% Alcance
            </span>
          </div>

          <!-- Heatmap / Activity Bars -->
          <div class="space-y-3 mb-6">
            
            <div>
              <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span>Miércoles (Hoy) — 19:45 hrs</span>
                <span class="text-brand-600 font-bold">98% Actividad Máxima 🔥</span>
              </div>
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div class="bg-gradient-to-r from-brand-500 to-indigo-600 h-full rounded-full" style="width: 98%;"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span>Viernes — 21:00 hrs</span>
                <span class="text-slate-600">84% Actividad</span>
              </div>
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div class="bg-indigo-400 h-full rounded-full" style="width: 84%;"></div>
              </div>
            </div>

            <div>
              <div class="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                <span>Domingo — 20:15 hrs</span>
                <span class="text-slate-600">76% Actividad</span>
              </div>
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div class="bg-indigo-300 h-full rounded-full" style="width: 76%;"></div>
              </div>
            </div>

          </div>

          <div class="p-4 rounded-xl bg-brand-50/70 border border-brand-200/60 flex items-center justify-between text-xs text-brand-900 font-medium">
            <span>💡 <strong>Recomendación XINDRO:</strong> Programa tu próximo post hoy a las <strong>19:42 hrs</strong> para maximizar guardados y comentarios.</span>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 7. SECCIÓN DE API PARA CREADORES & DESARROLLADORES (Código Limpio) -->
  <!-- ========================================================================= -->
  <section id="api-docs" class="py-24 dark-mesh-bg bg-slate-900 text-white">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <!-- Section Header -->
      <div class="text-center max-w-3xl mx-auto mb-16">
        <span class="text-xs font-extrabold uppercase tracking-wider text-brand-400 bg-brand-950/80 px-3.5 py-1 rounded-full border border-brand-500/40">
          Developer & Creator API
        </span>
        <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-white tracking-tight mt-4 mb-4">
          Ofrece la potencia de XINDRO dentro de tus propias herramientas.
        </h2>
        <p class="text-base sm:text-lg text-slate-300 font-normal">
          Endpoints RESTful ultrarrápidos, webhooks criptográficos verificados y SDKs listos para integrar en tus bots, paneles o SaaS con 5 líneas de código.
        </p>
      </div>

      <!-- Code Terminal Component -->
      <div class="rounded-2xl bg-slate-950 border border-slate-800 shadow-2xl overflow-hidden mb-12">
        
        <!-- Terminal Header with Tabs -->
        <div class="bg-slate-900 px-4 py-3 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
          
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded-full bg-rose-500"></div>
            <div class="w-3 h-3 rounded-full bg-amber-500"></div>
            <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
            <span class="text-xs font-mono text-slate-400 ml-2">POST https://socialapi.turbogram.site/api/agent.php</span>
          </div>

          <!-- Code Language Tabs -->
          <div class="flex items-center gap-1 bg-slate-950/70 p-1 rounded-lg border border-slate-800 text-xs font-mono">
            <button type="button" onclick="ApiTabs.switch('curl')" id="tab-curl" class="px-3 py-1 rounded bg-brand-600 text-white font-bold">cURL</button>
            <button type="button" onclick="ApiTabs.switch('js')" id="tab-js" class="px-3 py-1 rounded text-slate-400 hover:text-white">JavaScript</button>
            <button type="button" onclick="ApiTabs.switch('php')" id="tab-php" class="px-3 py-1 rounded text-slate-400 hover:text-white">PHP</button>
            <button type="button" onclick="ApiTabs.switch('python')" id="tab-python" class="px-3 py-1 rounded text-slate-400 hover:text-white">Python</button>
          </div>

        </div>

        <!-- Code Snippet Area -->
        <div class="p-6 relative">
          <button type="button" onclick="ApiTabs.copyCode()" id="btn-copy-code" class="absolute top-4 right-4 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-mono flex items-center gap-1.5 transition-colors">
            <span>📋 Copiar Código</span>
          </button>

          <!-- Snippet 1: cURL -->
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

          <!-- Snippet 2: JS -->
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

          <!-- Snippet 3: PHP -->
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

          <!-- Snippet 4: Python -->
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

        <!-- Terminal Footer Output Example -->
        <div class="bg-slate-900/80 px-6 py-4 border-t border-slate-800 flex flex-wrap items-center justify-between text-xs font-mono text-slate-400">
          <div class="flex items-center gap-3">
            <span class="text-emerald-400 font-bold">Status: 200 OK</span>
            <span>Latency: 142ms</span>
            <span>Tokens Consumed: 0 (Local Engine)</span>
          </div>
          <span class="text-brand-400 font-bold">JSON Response Ready</span>
        </div>

      </div>

      <!-- Developer Features 3-Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
        <div class="p-5 rounded-xl bg-slate-950/60 border border-slate-800">
          <div class="text-brand-400 font-bold text-lg mb-2">⚡ Webhooks en Tiempo Real</div>
          <p class="text-slate-400 text-xs leading-relaxed">Recibe y procesa comentarios de Instagram y Facebook en milisegundos con verificación HMAC-SHA256.</p>
        </div>

        <div class="p-5 rounded-xl bg-slate-950/60 border border-slate-800">
          <div class="text-blue-400 font-bold text-lg mb-2">🛡️ Multi-Tenant & Aislamiento</div>
          <p class="text-slate-400 text-xs leading-relaxed">Cada usuario y creador cuenta con su propio espacio aislado de datos y rate limiting anti-abusos.</p>
        </div>

        <div class="p-5 rounded-xl bg-slate-950/60 border border-slate-800">
          <div class="text-emerald-400 font-bold text-lg mb-2">🔌 Integración con Gemini & OpenAI</div>
          <p class="text-slate-400 text-xs leading-relaxed">Conecta tus propias claves o utiliza nuestro motor heurístico local sin coste de tokens.</p>
        </div>
      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 8. PRECIOS & PLANES (SaaS para Creadores & Agencias) -->
  <!-- ========================================================================= -->
  <section id="precios" class="py-24 bg-white border-b border-slate-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
      
      <div class="text-center max-w-2xl mx-auto mb-16">
        <span class="text-xs font-extrabold uppercase tracking-wider text-brand-600 bg-brand-50 px-3.5 py-1 rounded-full border border-brand-200">
          Planes Transparentes
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-midnight tracking-tight mt-3 mb-3">
          Comienza gratis y escala con tu comunidad.
        </h2>
        <p class="text-sm sm:text-base text-slate-600 font-normal">
          Sin contratos forzosos. Cancela en cualquier momento.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
        
        <!-- Plan 1: Starter -->
        <div class="rounded-3xl bg-slatecard border border-slate-200 p-8 flex flex-col justify-between hover:shadow-subtle-card transition-all">
          <div>
            <h3 class="text-lg font-bold text-midnight mb-1">Creador Starter</h3>
            <p class="text-xs text-slate-500 mb-6">Para creadores que dan sus primeros pasos.</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-black text-midnight">$0</span>
              <span class="text-xs text-slate-500 font-bold">/ mes gratis</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-600 font-medium mb-8">
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Hasta 1 cuenta de Instagram/Facebook</li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> 100 respuestas automáticas / mes</li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Asistente Copilot IA</li>
              <li class="flex items-center gap-2 text-slate-400"><span class="text-slate-300">✖</span> Acceso a API de desarrolladores</li>
            </ul>
          </div>
          <a href="login.php" class="w-full py-3 rounded-full text-center text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
            Crear Cuenta Gratis
          </a>
        </div>

        <!-- Plan 2: Pro Growth (Featured) -->
        <div class="rounded-3xl bg-white border-2 border-brand-500 p-8 flex flex-col justify-between shadow-glow-sm relative">
          <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-brand-600 to-indigo-600 text-white text-[11px] font-extrabold uppercase tracking-wider px-3.5 py-1 rounded-full shadow-sm">
            Más Popular
          </div>
          <div>
            <h3 class="text-lg font-bold text-midnight mb-1">Creador Pro</h3>
            <p class="text-xs text-slate-500 mb-6">Para marcas y creadores en rápido crecimiento.</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-black text-midnight">$29</span>
              <span class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-700 font-medium mb-8">
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> Cuentas ilimitadas de Meta</li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> Respuestas ilimitadas en Autopilot</li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> Calibrador de Voz de Marca personalizado</li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> Smart Timing & Analíticas de Engagement</li>
              <li class="flex items-center gap-2"><span class="text-brand-600 font-bold">✔</span> Soporte prioritario 24/7</li>
            </ul>
          </div>
          <a href="login.php" class="w-full py-3.5 rounded-full text-center text-sm font-bold text-white gradient-button shadow-glow-sm">
            Comenzar con Pro
          </a>
        </div>

        <!-- Plan 3: API & Agencias -->
        <div class="rounded-3xl bg-slatecard border border-slate-200 p-8 flex flex-col justify-between hover:shadow-subtle-card transition-all">
          <div>
            <h3 class="text-lg font-bold text-midnight mb-1">API & Agencias</h3>
            <p class="text-xs text-slate-500 mb-6">Para desarrolladores y agencias de marketing.</p>
            <div class="flex items-baseline gap-1 mb-6">
              <span class="text-4xl font-black text-midnight">$79</span>
              <span class="text-xs text-slate-500 font-bold">/ mes</span>
            </div>
            <ul class="space-y-3 text-xs text-slate-600 font-medium mb-8">
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Acceso total a REST API & Webhooks</li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Gestión de hasta 25 clientes aislados</li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> 100,000 llamadas a API incluidas / mes</li>
              <li class="flex items-center gap-2"><span class="text-emerald-500 font-bold">✔</span> Marca blanca & Webhook dedicado</li>
            </ul>
          </div>
          <a href="login.php" class="w-full py-3 rounded-full text-center text-sm font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors">
            Acceso para Agencias
          </a>
        </div>

      </div>

    </div>
  </section>

  <!-- ========================================================================= -->
  <!-- 9. FOOTER PROFESIONAL CON MARCA DE AGUA GIGANTE ESTILO GAMMA -->
  <!-- ========================================================================= -->
  <footer class="starry-footer-bg starry-overlay pt-20 pb-12 text-slate-300 text-sm overflow-hidden relative">
    
    <!-- Giant Gamma-Style Watermark Behind Links -->
    <div class="w-full text-center my-6 overflow-hidden select-none pointer-events-none">
      <div class="giant-watermark text-[16vw] font-black uppercase tracking-widest leading-none">
        XINDRO
      </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
      
      <!-- Footer Columns (Gamma Structure) -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-8 mb-16 pt-6">
        
        <!-- Col 1: Descarga la app / Acceso -->
        <div class="col-span-2 md:col-span-1">
          <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Descarga la app</h4>
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
          <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Producto</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#precios" class="hover:text-white transition-colors">Precios</a></li>
            <li><a href="#simulador" class="hover:text-white transition-colors">Inspiración</a></li>
            <li><a href="#funciones" class="hover:text-white transition-colors">Educación</a></li>
            <li><a href="#simulador" class="hover:text-white transition-colors">Guía de prompts</a></li>
            <li><a href="#funciones" class="hover:text-white transition-colors">Plantillas</a></li>
            <li><a href="#smart-timing" class="hover:text-white transition-colors">Explorar</a></li>
            <li><a href="#api-docs" class="hover:text-white transition-colors">Integraciones</a></li>
            <li><a href="dashboard.php" class="hover:text-white transition-colors">Accesibilidad</a></li>
          </ul>
        </div>

        <!-- Col 3: Empresa -->
        <div>
          <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Empresa</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="#funciones" class="hover:text-white transition-colors">Acerca de</a></li>
            <li><a href="login.php" class="hover:text-white transition-colors">Carreras</a></li>
            <li><a href="login.php" class="hover:text-white transition-colors">Equipo</a></li>
            <li><a href="login.php" class="hover:text-white transition-colors">Ayuda</a></li>
            <li><a href="#simulador" class="hover:text-white transition-colors">Comunidad</a></li>
            <li><a href="#api-docs" class="hover:text-white transition-colors">Documentación para desarrolladores</a></li>
            <li><a href="#funciones" class="hover:text-white transition-colors">Marca</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white transition-colors">Seguridad</a></li>
            <li><a href="login.php" class="hover:text-white transition-colors">Contáctanos</a></li>
          </ul>
        </div>

        <!-- Col 4: Redes sociales -->
        <div>
          <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Redes sociales</h4>
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
          <h4 class="text-xs font-bold text-white uppercase tracking-wider mb-4">Información legal</h4>
          <ul class="space-y-2.5 text-xs text-slate-300 font-medium">
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Acceptable Use Policy</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white transition-colors">Cookie Notice</a></li>
            <li><a href="javascript:void(0)" onclick="CookieConsent.openSettings()" class="hover:text-white transition-colors">Preferencias de cookies</a></li>
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Data Processing Addendum</a></li>
            <li><a href="privacy-policy.php" class="hover:text-white transition-colors">Privacy Policy</a></li>
            <li><a href="data-deletion.php" class="hover:text-white transition-colors">Data Deletion (Meta)</a></li>
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Terms of Service</a></li>
            <li><a href="terms-of-service.php" class="hover:text-white transition-colors">Third Party Terms</a></li>
          </ul>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="border-t border-slate-800/80 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
        <div class="flex items-center gap-2">
          <span class="font-bold text-white">XINDRO</span>
          <span>•</span>
          <span>© <?= date('Y') ?> Xindro Tech, Inc. Todos los derechos reservados.</span>
        </div>
        <div class="flex items-center gap-4">
          <span class="flex items-center gap-1.5 text-emerald-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400 live-dot"></span>
            Meta Graph API Verified
          </span>
          <a href="javascript:void(0)" onclick="CookieConsent.openSettings()" class="text-slate-400 hover:text-white underline">Ajustes de Cookies</a>
        </div>
      </div>

    </div>
  </footer>

  <!-- ========================================================================= -->
  <!-- 10. POPUP DE COOKIES EXACTO AL ESTILO GAMMA (Bottom-Left Card Modal) -->
  <!-- ========================================================================= -->
  <div id="cookie-consent-modal" class="fixed bottom-5 left-5 z-50 max-w-[420px] w-[calc(100%-40px)] bg-white rounded-2xl shadow-cookie-popup p-5 border border-slate-200/90 text-slate-800 cookie-animate hidden">
    
    <!-- Top Row: Title + Close Button -->
    <div class="flex items-start justify-between gap-3 mb-2">
      <div class="text-sm font-extrabold text-midnight flex items-center gap-1.5">
        <span>About our cookies</span>
        <span>🍪</span>
      </div>
      <button type="button" onclick="CookieConsent.close()" class="text-slate-400 hover:text-slate-700 text-lg font-bold leading-none p-1" title="Cerrar">
        &times;
      </button>
    </div>

    <!-- Description Text -->
    <p class="text-[11.5px] text-slate-600 leading-relaxed mb-4">
      We use cookies and similar technologies as set out in our <a href="privacy-policy.php" class="text-blue-600 hover:underline font-semibold">Cookie Notice</a>. By clicking "Accept All", you agree to our use of optional cookies and similar technologies for the purposes set out in our <a href="privacy-policy.php" class="text-blue-600 hover:underline font-semibold">Cookie Notice</a>.
    </p>

    <!-- Action Buttons Row (Exact Gamma Layout) -->
    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
      <button type="button" onclick="CookieConsent.openSettings()" class="text-[11px] font-bold px-3.5 py-1.5 rounded-full border border-slate-300 text-slate-700 hover:bg-slate-50 transition-colors">
        Cookies Settings
      </button>

      <div class="flex items-center gap-1.5">
        <button type="button" onclick="CookieConsent.rejectAll()" class="text-[11px] font-bold px-3.5 py-1.5 rounded-full bg-slate-900 hover:bg-black text-white transition-colors">
          Reject All
        </button>
        <button type="button" onclick="CookieConsent.acceptAll()" class="text-[11px] font-bold px-4 py-1.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white shadow-sm transition-colors">
          Accept All
        </button>
      </div>
    </div>

  </div>

  <!-- Detailed Cookies Preferences Modal (Optional Modal) -->
  <div id="cookie-settings-modal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 text-slate-800">
      <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
        <h3 class="text-base font-extrabold text-midnight">Centro de Preferencias de Cookies</h3>
        <button type="button" onclick="CookieConsent.closeSettings()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
      </div>
      <p class="text-xs text-slate-600 mb-4 leading-relaxed">
        Gestiona las categorías de cookies que utilizamos para brindarte la mejor experiencia en XINDRO.
      </p>

      <div class="space-y-3 text-xs mb-6">
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
          <div>
            <div class="font-bold text-midnight">Cookies Esenciales (Requeridas)</div>
            <div class="text-slate-500 text-[11px]">Necesarias para la autenticación y seguridad de la plataforma.</div>
          </div>
          <span class="text-[11px] font-bold text-emerald-600">Siempre activas</span>
        </div>

        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
          <div>
            <div class="font-bold text-midnight">Cookies de Rendimiento & Analítica</div>
            <div class="text-slate-500 text-[11px]">Nos ayudan a medir el rendimiento de la API y el simulador.</div>
          </div>
          <input type="checkbox" id="chk-analytics-cookies" checked class="w-4 h-4 text-brand-600 rounded cursor-pointer" />
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
        <button type="button" onclick="CookieConsent.saveCustom()" class="px-4 py-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition-colors">
          Guardar Preferencias
        </button>
      </div>
    </div>
  </div>

  <!-- ========================================================================= -->
  <!-- 11. JAVASCRIPT DEL SIMULADOR, COOKIES Y PESTAÑAS DE CÓDIGO -->
  <!-- ========================================================================= -->
  <script>
    // 1. Cookie Consent Manager (Exact Gamma Style with localStorage persistence)
    const CookieConsent = {
      init() {
        const consent = localStorage.getItem('xindro_cookie_consent');
        if (!consent) {
          // Show popup after a smooth delay of 800ms
          setTimeout(() => {
            const modal = document.getElementById('cookie-consent-modal');
            if (modal) modal.classList.remove('hidden');
          }, 800);
        }
      },

      acceptAll() {
        localStorage.setItem('xindro_cookie_consent', 'accepted_all');
        this.hideModal();
      },

      rejectAll() {
        localStorage.setItem('xindro_cookie_consent', 'rejected_optional');
        this.hideModal();
      },

      close() {
        localStorage.setItem('xindro_cookie_consent', 'closed');
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
        localStorage.setItem('xindro_cookie_consent', JSON.stringify({ essential: true, analytics: !!analytics }));
        this.closeSettings();
        this.hideModal();
      }
    };

    // Initialize cookies on DOM ready
    document.addEventListener('DOMContentLoaded', () => {
      CookieConsent.init();
    });

    // 2. Interactive Simulator Engine
    const Simulator = {
      presets: {
        1: {
          text: "¿Cuánto cuesta la mentoría o curso? ¿Tienen cupos disponibles para este mes?",
          tone: "growth",
          closing: "always"
        },
        2: {
          text: "¡Qué gran reflexión! Marco Aurelio y Séneca cambiaron mi forma de ver los problemas cotidianos. Gracias por compartir.",
          tone: "mentor",
          closing: "relevant"
        }
      },

      loadPreset(num) {
        const p = this.presets[num];
        if (!p) return;
        document.getElementById('sim-input-text').value = p.text;
        document.getElementById('sim-tone').value = p.tone;
        document.getElementById('sim-closing').value = p.closing;
        this.generate();
      },

      generate() {
        const text = document.getElementById('sim-input-text').value.trim();
        const tone = document.getElementById('sim-tone').value;
        const platform = document.getElementById('sim-platform').value;
        const closing = document.getElementById('sim-closing').value;
        const btn = document.getElementById('sim-btn-generate');
        const outputText = document.getElementById('sim-output-text');
        const badgeIntent = document.getElementById('sim-badge-intent');
        const badgeScore = document.getElementById('sim-badge-score');

        if (!text) return;

        btn.disabled = true;
        btn.innerHTML = '<span>Generando respuesta con IA...</span>';

        setTimeout(() => {
          let reply = '';
          let intent = 'Interés / Comunidad';
          let score = 92;

          const textLower = text.toLowerCase();

          if (textLower.includes('precio') || textLower.includes('curso') || textLower.includes('cuanto') || textLower.includes('costo') || textLower.includes('cupo')) {
            intent = 'Lead de Alta Conversión 💎';
            score = 98;
            if (tone === 'mentor') {
              reply = "El valor real está en la transformación y la disciplina diaria. Te envié todos los detalles y el temario completo por mensaje privado para que veas si encaja con tus objetivos. ¿Listo para dar el salto? 🚀";
            } else if (tone === 'growth') {
              reply = "¡Hola! Sí, abrimos solo 10 cupos para este mes para trabajar de forma personalizada. Ya te escribí por DM con el enlace y una sorpresa especial. ¿Pudiste revisarlo? 📩";
            } else {
              reply = "¡Qué alegría tu interés! Te acabo de enviar un mensaje privado con toda la información y precios especiales. Cualquier duda estoy aquí para ayudarte. ¿Qué objetivo te gustaría lograr primero? ✨";
            }
          } else if (textLower.includes('procrastino') || textLower.includes('miedo') || textLower.includes('consejo') || textLower.includes('empezar')) {
            intent = 'Pregunta de Mentoría / Alto Valor 🧠';
            score = 95;
            if (tone === 'mentor') {
              reply = "El miedo al fracaso solo desaparece cuando actúas antes de que la mente empiece a dudar. Divide tu meta en una sola acción de 5 minutos para hoy. La perfección no existe, el progreso diario sí. ¿Qué pequeña tarea harás en los próximos 10 minutos? 👇";
            } else if (tone === 'growth') {
              reply = "La acción cura el miedo. No esperes a sentirte listo: empieza imperfecto hoy. Define tu prioridad #1 y ejecútala sin negociar contigo mismo. ¿Qué proyecto vas a desbloquear hoy? 🔥";
            } else {
              reply = "Te entiendo muchísimo, todos hemos pasado por ahí. El truco es no mirar toda la montaña, sino solo el primer paso de hoy. Da un pequeño paso y celebra ese avance. ¿Con qué tarea pequeña te gustaría empezar hoy? 🤝";
            }
          } else {
            intent = 'Conexión & Retención ⚡';
            score = 90;
            if (tone === 'mentor') {
              reply = "Exactamente. Cuando dominas tu mente y aplicas la sabiduría en tu rutina, los problemas externos pierden todo su poder. Gracias por ser parte de esta comunidad. ¿Qué principio estoico te ha servido más esta semana? 🏛️";
            } else if (tone === 'growth') {
              reply = "¡Totalmente de acuerdo! La mentalidad lo es todo cuando se trata de escalar y mantener el foco. Vamos con todo esta semana. ¿Cuál es tu mayor meta de estos días? 🚀";
            } else {
              reply = "¡Muchísimas gracias por tus palabras! Me alegra de corazón que estas reflexiones te aporten valor y claridad en el día a día. ¿Qué tema te gustaría que toquemos en el próximo post? ✨";
            }
          }

          if (closing === 'never') {
            reply = reply.replace(/\¿[^\?]+\?\s*(👇|✨|🔥|🚀|📩|🤝)?$/i, '');
          }

          badgeIntent.textContent = '🎯 ' + intent;
          badgeScore.textContent = score + '/100';
          outputText.textContent = `"${reply}"`;

          btn.disabled = false;
          btn.innerHTML = '<span>Generar Respuesta con IA</span><span>⚡</span>';
        }, 350);
      },

      copyResponse() {
        const text = document.getElementById('sim-output-text').textContent.replace(/^"|"$/g, '');
        navigator.clipboard.writeText(text).then(() => {
          const btn = document.getElementById('sim-btn-copy');
          btn.innerHTML = '<span class="text-emerald-600 font-bold">✔ ¡Copiado!</span>';
          setTimeout(() => {
            btn.innerHTML = '<span>📋 Copiar</span>';
          }, 2000);
        });
      }
    };

    // 3. API Code Tabs Switcher
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
          btn.innerHTML = '<span class="text-emerald-400">✔ ¡Código Copiado!</span>';
          setTimeout(() => {
            btn.innerHTML = '<span>📋 Copiar Código</span>';
          }, 2000);
        });
      }
    };
  </script>

</body>
</html>
