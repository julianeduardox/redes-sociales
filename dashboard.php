<?php
/**
 * SocialBoost AI - Multi-Tenant Stoic & Motivational Community Manager
 */
require_once __DIR__ . '/config/security.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';

Security::applySecurityHeaders(false);
Auth::requireAuth(false);

$currentUser = Auth::user();
$userId = $currentUser['id'] ?? 1;
$csrfToken = Security::getCsrfToken();
$userBrands = [];

try {
    $pdo = Database::getConnection();
    // Pre-render user's brand voices for instant display
    $stmtBrands = $pdo->prepare("SELECT id, brand_name, industry, is_default FROM brand_voices WHERE user_id = ? ORDER BY is_default DESC, id ASC");
    $stmtBrands->execute([$userId]);
    $userBrands = $stmtBrands->fetchAll(PDO::FETCH_ASSOC);

    if (empty($userBrands)) {
        Database::ensureDefaultBrandVoice($pdo, $userId, $currentUser['name'] ?? 'Mi Marca');
        $stmtBrands->execute([$userId]);
        $userBrands = $stmtBrands->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    error_log("Brand pre-render notice: " . $e->getMessage());
    $userBrands = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <!-- Performance Preconnect for External Assets -->
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="dns-prefetch" href="https://images.unsplash.com">
  <link rel="preconnect" href="https://ui-avatars.com" crossorigin>
  
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🏛️</text></svg>">
</head>
<body>

<div class="app-container">

  <!-- Mobile Sidebar Backdrop -->
  <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="App.toggleMobileSidebar(false)"></div>

  <!-- Sidebar Navigation -->
  <aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-header">
      <div class="brand-icon-box" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); color: #fff;">⚡</div>
      <div class="brand-text">
        <h1 style="font-family: 'Syne', sans-serif; font-weight: 900; letter-spacing: -0.02em;">XINDRO Copilot</h1>
        <div class="brand-tag">Multi-Brand & Agency AI OS</div>
      </div>
      <button type="button" class="btn-close-sidebar-mobile" onclick="App.toggleMobileSidebar(false)">&times;</button>
    </div>

    <!-- Active User Profile Pill -->
    <div class="user-session-card">
      <img src="<?= htmlspecialchars($currentUser['avatar_url'] ?? 'https://ui-avatars.com/api/?name=User&background=6366f1&color=fff&size=96', ENT_QUOTES, 'UTF-8') ?>" width="34" height="34" loading="lazy" decoding="async" class="user-avatar-mini" alt="avatar" />
      <div class="user-session-info">
        <div class="user-session-name"><?= htmlspecialchars($currentUser['name'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="user-session-email"><?= htmlspecialchars($currentUser['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
      </div>
      <button type="button" class="btn-logout-mini" onclick="App.logout()" title="Cerrar Sesión">🚪</button>
    </div>

    <!-- Left Sidebar Assistant Widget -->
    <div class="sidebar-assistant-card" onclick="AgentController.openAssistantModal()" title="Abrir Copiloto de Conversión & Respuestas">
      <div class="sidebar-assistant-top">
        <div class="sidebar-assistant-icon-box">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2L14.7 8.5L21.5 9.5L16.5 14.3L17.8 21.1L12 17.8L6.2 21.1L7.5 14.3L2.5 9.5L9.3 8.5L12 2Z"/>
          </svg>
        </div>
        <div class="sidebar-assistant-text">
          <div class="sidebar-assistant-title">
            <span>Copiloto IA</span>
            <span class="sidebar-assistant-pill">CONVERSIÓN</span>
          </div>
          <div class="sidebar-assistant-sub">Respuestas inteligentes & ventas</div>
        </div>
      </div>
      <div class="sidebar-assistant-actions">
        <button type="button" class="btn-sidebar-assistant" onclick="event.stopPropagation(); AgentController.openAssistantModal()">
          <span>Abrir Copiloto ✨</span>
        </button>
        <button type="button" class="sidebar-score-guide-btn" onclick="event.stopPropagation(); App.openScoreGuideModal()" title="Ver cómo se calcula el Score de IA">
          <span>🎯 Score</span>
        </button>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-title">Comunidad & Inbox</div>
      <button class="nav-btn active" data-tab="inbox">
        <span class="icon">📥</span>
        <span>Inbox de Comentarios</span>
        <span class="nav-badge" id="badge-count-inbox">0</span>
      </button>

      <button class="nav-btn" data-tab="highlights">
        <span class="icon">⭐</span>
        <span>Más Resaltantes</span>
        <span class="nav-badge fire" id="badge-count-highlights">0</span>
      </button>

      <button class="nav-btn" data-tab="leads">
        <span class="icon">🎯</span>
        <span>Leads & Precios</span>
        <span class="nav-badge" id="badge-count-leads">0</span>
      </button>

      <button class="nav-btn" data-tab="urgent">
        <span class="icon">🛡️</span>
        <span>Objeciones & Soporte</span>
      </button>

      <button class="nav-btn" data-tab="spam">
        <span class="icon">🚫</span>
        <span>Filtro Anti-Spam</span>
        <span class="nav-badge" id="badge-count-spam" style="background: rgba(244,63,94,0.25); color: #fb7185;">0</span>
      </button>

      <div class="nav-section-title" style="margin-top: 10px;">Inteligencia & Config</div>
      <button class="nav-btn" data-tab="analytics">
        <span class="icon">📈</span>
        <span>Métricas de Audiencia</span>
      </button>

      <button class="nav-btn" data-tab="settings">
        <span class="icon">🤖</span>
        <span>Voz de Marca & Prompt</span>
      </button>

      <button class="nav-btn" data-tab="meta">
        <span class="icon">⚙️</span>
        <span>Meta Graph API</span>
      </button>
    </nav>

    <div class="sidebar-footer">
      <div class="autopilot-widget">
        <div class="autopilot-info">
          <span class="autopilot-title">⚡ Auto-Responder</span>
          <span class="autopilot-sub">Comentarios destacados</span>
        </div>
        <label class="switch">
          <input type="checkbox" id="autopilot-sidebar-toggle">
          <span class="slider"></span>
        </label>
      </div>

      <div style="margin-top: 8px; padding-top: 6px; border-top: 1px solid var(--border-subtle); display: flex; flex-direction: column; gap: 3px; font-size: 0.7rem; color: var(--text-dim);">
        <div style="font-weight: 700; color: var(--text-muted); margin-bottom: 1px;">Legal & Meta Compliance:</div>
        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
          <a href="privacy-policy.php" target="_blank" style="color: var(--text-dim); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-dim)'">Privacidad</a> •
          <a href="terms-of-service.php" target="_blank" style="color: var(--text-dim); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-dim)'">Términos</a> •
          <a href="data-deletion.php" target="_blank" style="color: var(--text-dim); text-decoration: none; transition: 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='var(--text-dim)'">Eliminar Datos</a>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main Area -->
  <main class="app-main">

    <!-- Topbar -->
    <header class="app-topbar">
      <div class="topbar-left">
        <button type="button" class="btn-mobile-menu" id="btn-mobile-menu" onclick="App.toggleMobileSidebar(true)" aria-label="Abrir Menú">☰</button>
        <h2 class="page-title" id="topbar-page-title">Gestor de Comunidad & Conversión</h2>

        <!-- Agency Multi-Brand Switcher (Styled as Capsule Pill Group) -->
        <div class="topbar-brand-switcher" id="topbar-brand-switcher" title="Cambiar de marca o cliente activo">
          <div class="brand-select-pill">
            <span class="brand-pill-icon">🏢</span>
            <select id="topbar-brand-select" class="topbar-brand-select" onchange="App.switchActiveBrand(this.value)">
              <?php if (empty($userBrands)): ?>
                <option value="">Cargando marcas...</option>
              <?php else: ?>
                <?php foreach ($userBrands as $b): ?>
                  <option value="<?= (int)$b['id'] ?>" <?= !empty($b['is_default']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['brand_name'], ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
          <button type="button" class="btn-new-brand-pill" onclick="App.openNewBrandModal()">
            <span>+ Nueva Marca</span>
          </button>
        </div>

        <div class="platform-pill-group">
          <button class="platform-pill active" data-platform="all">🌐 Todos</button>
          <button class="platform-pill ig-active" data-platform="instagram">📸 IG</button>
          <button class="platform-pill fb-active" data-platform="facebook">📘 FB</button>
        </div>
      </div>

      <div class="topbar-stats">
        <div class="stat-pill score">
          <div class="dot"></div>
          <span id="count-pill-highlighted">0 Destacados</span>
        </div>
        <div class="stat-pill leads">
          <div class="dot"></div>
          <span id="count-pill-leads">0 Leads</span>
        </div>
        <div class="stat-pill urgent">
          <div class="dot"></div>
          <span id="count-pill-urgent">0 Soporte</span>
        </div>
        <button class="btn-primary-action" onclick="App.openModal('modal-simulate')">
          <span>+ Simular Comentario</span>
        </button>
        <button class="btn-primary-action" style="background: linear-gradient(135deg, #10b981, #047857);" onclick="AgentController.runAutopilotBatch()">
          <span>⚡ Auto-responder</span>
        </button>
        <button class="btn-logout-topbar" onclick="App.logout()" title="Cerrar Sesión">
          <span>Salir 🚪</span>
        </button>
      </div>
    </header>

    <!-- View 1: Main Workspace (Feed + Copilot) -->
    <div class="view-container" id="view-feed-workspace">
      
      <!-- Feed / Stream Column -->
      <section class="feed-column">
        
        <!-- Active Post Filter Banner (Rendered dynamically when activePostId is set) -->
        <div id="active-post-banner" class="active-post-banner" style="display: none;">
          <div class="active-post-banner-left">
            <img src="" id="active-post-thumb" width="48" height="48" loading="lazy" decoding="async" class="active-post-banner-thumb" alt="post thumb" />
            <div class="active-post-banner-info">
              <div id="active-post-caption" class="active-post-banner-caption">Filtrando comentarios de publicación...</div>
              <div class="active-post-banner-meta">
                <span id="active-post-platform-badge" class="platform-badge-mini instagram">IG</span>
                <span id="active-post-stats">👁️ 0 Reach • 💬 0 Comentarios</span>
              </div>
            </div>
          </div>
          <button type="button" class="btn-clear-post-filter" onclick="App.clearPostFilter()">
            <span>✕ Quitar filtro</span>
          </button>
        </div>

        <div class="feed-header">
          <div class="search-box">
            <span class="search-icon">🔍</span>
            <input type="text" id="feed-search-input" placeholder="Buscar por usuario, pregunta de precio, producto o palabra clave..." />
          </div>

          <div class="filter-tags">
            <button class="filter-tag active" data-filter="all">Todos</button>
            <button class="filter-tag" data-filter="highlighted">⭐ Más Resaltantes</button>
            <button class="filter-tag" data-filter="leads">🎯 Leads & Precios</button>
            <button class="filter-tag" data-filter="urgent">🛡️ Objeciones & Soporte</button>
            <button class="filter-tag" data-filter="pending">⏳ Pendientes</button>
            <button class="filter-tag" data-filter="replied">✅ Respondidos</button>
          </div>

          <div class="feed-toolbar-row">
            <div class="feed-toolbar-left">
              <span class="feed-counter-text" id="feed-counter-display">Cargando comentarios...</span>
              <button type="button" class="btn-toolbar-assistant" onclick="AgentController.openAssistantModal()" title="Abrir Copiloto de Conversión & Respuestas">
                <span>🪄 Copiloto IA</span>
              </button>
            </div>
            <div class="density-toggle-group">
              <button type="button" class="btn-density-toggle active" id="btn-density-cards" onclick="App.toggleViewDensity('cards')" title="Vista Detallada con multimedia">
                <span>🎴 Detallada</span>
              </button>
              <button type="button" class="btn-density-toggle" id="btn-density-compact" onclick="App.toggleViewDensity('compact')" title="Vista Compacta tipo lista rápida">
                <span>📋 Compacta</span>
              </button>
            </div>
          </div>
        </div>

        <div class="comments-scroll-area" id="comments-stream">
          <!-- Comments rendered dynamically -->
        </div>
      </section>

    </div>

    <!-- View 2: Analytics & Metrics -->
    <div id="view-analytics" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 20px;">
        <div>
          <h3 style="font-size: 1.4rem; font-weight: 800; color: #fff;">📈 Métricas de Audiencia & Meta Graph API</h3>
          <p style="font-size: 0.85rem; color: var(--text-muted);">Monitorea el alcance real, impresiones, guardados y engagement por cada publicación.</p>
        </div>

        <!-- Subtabs switch -->
        <div class="analytics-subnav">
          <button class="subtab-btn active" data-subtab="overview" onclick="AnalyticsController.switchSubtab('overview')">
            <span>📊 Visión General</span>
          </button>
          <button class="subtab-btn" data-subtab="posts" onclick="AnalyticsController.switchSubtab('posts')">
            <span>📱 Rendimiento por Publicación</span>
          </button>
        </div>
      </div>

      <!-- Subview 1: General Overview -->
      <div id="analytics-overview-subview">
        <div id="analytics-view-content">
          <!-- Overview metrics rendered dynamically -->
        </div>
      </div>

      <!-- Subview 2: Post-by-Post Insights -->
      <div id="analytics-posts-subview" style="display: none;">
        <!-- Post Filter Toolbar -->
        <div class="post-filter-toolbar">
          <div class="toolbar-left">
            <span style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim);">Plataforma:</span>
            <div class="platform-pill-group">
              <button class="platform-pill active" data-post-platform="all" onclick="AnalyticsController.filterPostsPlatform('all')">🌐 Todas</button>
              <button class="platform-pill" data-post-platform="instagram" onclick="AnalyticsController.filterPostsPlatform('instagram')">📸 Instagram</button>
              <button class="platform-pill" data-post-platform="facebook" onclick="AnalyticsController.filterPostsPlatform('facebook')">📘 Facebook</button>
            </div>
          </div>

          <div class="toolbar-right">
            <span style="font-size: 0.84rem; font-weight: 700; color: var(--text-dim);">Ordenar por:</span>
            <select class="sort-select" id="posts-sort-select" onchange="AnalyticsController.changePostsSort(this.value)">
              <option value="recent">📅 Más Recientes</option>
              <option value="reach">👁️ Mayor Alcance (Reach)</option>
              <option value="engagement">🔥 Mayor Engagement Rate %</option>
              <option value="comments">💬 Más Comentarios</option>
              <option value="likes">❤️ Más Likes</option>
            </select>

            <button class="btn-primary-action" style="background: #1877f2; padding: 7px 14px; font-size: 0.8rem;" onclick="App.triggerMetaSync()">
              <span>🔄 Sincronizar con Meta</span>
            </button>
          </div>
        </div>

        <!-- Dynamic Posts Grid -->
        <div id="posts-grid-container" class="posts-grid">
          <!-- Posts rendered dynamically -->
        </div>
      </div>
    </div>

    <!-- View 3: AI Brand Voice Studio & Identity Calibrator -->
    <div id="view-settings" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 1280px; margin: 0 auto;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px;">
          <div>
            <h3 style="font-size: 1.45rem; font-weight: 800; color: #fff; margin-bottom: 6px;">🤖 Estudio de Voz de Marca & Prompt Dinámico</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted);">Configura la personalidad, persona, idioma y prompt de la IA adaptado a cualquier cliente o nicho comercial.</p>
          </div>

          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="background: rgba(255,255,255,0.04); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 4px 10px; display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 0.85rem;">🏢 Marca en Edición:</span>
              <select id="settings-brand-voice-selector" class="topbar-brand-select" onchange="App.loadBrandVoiceDetails(this.value)">
                <?php if (empty($userBrands)): ?>
                  <option value="">Cargando marcas...</option>
                <?php else: ?>
                  <?php foreach ($userBrands as $b): ?>
                    <option value="<?= (int)$b['id'] ?>" <?= !empty($b['is_default']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($b['brand_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($b['industry'] ?? 'General', ENT_QUOTES, 'UTF-8') ?>)
                    </option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <button type="button" class="btn-primary-action" onclick="App.openNewBrandModal()">
              <span>+ Nueva Marca</span>
            </button>
          </div>
        </div>

        <div class="studio-grid-layout">
          <!-- Left Column: Identity Tuning & Golden Rules -->
          <div>
            <form onsubmit="App.saveBrandStudioForm(event)">
              <input type="hidden" id="setting-brand-id" value="" />
              
              <!-- Block 1: Brand Fundamentals -->
              <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
                <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 16px; color: var(--accent-cyan); display: flex; align-items: center; gap: 8px;">
                  <span>🏢 Identidad, Persona & Nicho del Cliente</span>
                </h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                  <div class="form-group">
                    <label>Nombre de la Marca o Cliente:</label>
                    <input type="text" id="setting-brand-name" placeholder="Ej: Xindro Studio / Nike / Inmobiliaria Premier" required />
                  </div>

                  <div class="form-group">
                    <label>Nombre de la Persona / Asistente:</label>
                    <input type="text" id="setting-persona-name" placeholder="Ej: Alex — Consultor Comercial / Sofía de Soporte" required />
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                  <div class="form-group">
                    <label>Industria / Nicho de Negocio:</label>
                    <input type="text" id="setting-brand-industry" placeholder="Ej: E-commerce, Fitness, Moda, Real Estate, Servicios B2B" required />
                  </div>

                  <div class="form-group">
                    <label>Idioma Principal de Respuestas:</label>
                    <select id="setting-brand-language">
                      <option value="es" selected>🇪🇸 Español (Predeterminado)</option>
                      <option value="en">🇺🇸 English</option>
                      <option value="pt">🇧🇷 Português</option>
                      <option value="any">🌐 Auto-detectar idioma del usuario</option>
                    </select>
                  </div>
                </div>

                <div class="form-group">
                  <label>Tono Base de Comunicación:</label>
                  <select id="setting-brand-tone">
                    <option value="friendly_engaging">🤝 Cercano, Amable & Empático (Conversacional)</option>
                    <option value="commercial_sales">🎯 Comercial & Orientado a Ventas (Enfoque CTA / DM)</option>
                    <option value="executive_formal">💼 Ejecutivo, Profesional & Corporativo</option>
                    <option value="educational_expert">💡 Educativo, Autoridad & Mentor Experto</option>
                    <option value="humorous_casual">🔥 Dinámico, Juvenil & Desenfadado</option>
                  </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                  <label>System Prompt Dinámico Personalizado (Instrucciones de la IA):</label>
                  <textarea id="setting-brand-desc" rows="4" placeholder="Ej: Eres el estratega oficial de comunicación de la marca. Responde siempre con carisma, aporta valor útil, resuelve dudas de clientes potenciales y orienta las conversaciones hacia la compra o contacto por DM sin sonar como un robot."></textarea>
                </div>
              </div>

              <!-- Block 2: Identity Calibration Sliders -->
              <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
                <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 6px; color: var(--accent-emerald); display: flex; align-items: center; gap: 8px;">
                  <span>🎚️ Calibrador de Identidad (Nivelación de Voz)</span>
                </h4>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 18px;">Ajusta con precisión cómo debe balancearse la cercanía humana frente al rigor profesional y la energía.</p>

                <!-- Slider 1: Warmth & Closeness -->
                <div class="tuning-slider-card">
                  <div class="tuning-slider-header">
                    <div class="tuning-slider-title">
                      <span>🤝 Cercanía & Calidez Humana:</span>
                      <span id="label-warmth-status" style="font-size: 0.78rem; color: var(--text-dim); font-weight: 600;">(Empático & Cálido)</span>
                    </div>
                    <span class="tuning-slider-badge emerald" id="badge-warmth-val">85%</span>
                  </div>
                  <div class="tuning-slider-desc">Controla la empatía, el saludo por su nombre de pila y la cercanía conversacional.</div>
                  <input type="range" min="1" max="100" value="85" class="range-slider-input" id="slider-warmth" oninput="App.updateSliderVal('warmth', this.value)" />
                  <div class="slider-scale-labels">
                    <span>Formal & Distante (10%)</span>
                    <span>Equilibrado (50%)</span>
                    <span>Muy Cercano & Fraternal (100%)</span>
                  </div>
                </div>

                <!-- Slider 2: Expertise & Depth -->
                <div class="tuning-slider-card">
                  <div class="tuning-slider-header">
                    <div class="tuning-slider-title">
                      <span>🧠 Profundidad / Expertise & Solución:</span>
                      <span id="label-depth-status" style="font-size: 0.78rem; color: var(--text-dim); font-weight: 600;">(Informativo & Sólido)</span>
                    </div>
                    <span class="tuning-slider-badge cyan" id="badge-depth-val">75%</span>
                  </div>
                  <div class="tuning-slider-desc">Determina el nivel de detalle técnico, fundamentación y claridad en las explicaciones.</div>
                  <input type="range" min="1" max="100" value="75" class="range-slider-input" id="slider-depth" oninput="App.updateSliderVal('depth', this.value)" />
                  <div class="slider-scale-labels">
                    <span>Práctico & Breve (10%)</span>
                    <span>Equilibrado (50%)</span>
                    <span>Alta Autoridad & Detallado (100%)</span>
                  </div>
                </div>

                <!-- Slider 3: Discipline & Energy -->
                <div class="tuning-slider-card">
                  <div class="tuning-slider-header">
                    <div class="tuning-slider-title">
                      <span>🚀 Enfoque a la Acción & Conversión:</span>
                      <span id="label-energy-status" style="font-size: 0.78rem; color: var(--text-dim); font-weight: 600;">(Proactivo & Venta)</span>
                    </div>
                    <span class="tuning-slider-badge" id="badge-energy-val">80%</span>
                  </div>
                  <div class="tuning-slider-desc">Define la energía y proactividad para cerrar ventas, invitar al DM o derivar al catálogo.</div>
                  <input type="range" min="1" max="100" value="80" class="range-slider-input" id="slider-energy" oninput="App.updateSliderVal('energy', this.value)" />
                  <div class="slider-scale-labels">
                    <span>Informativo Pasivo (10%)</span>
                    <span>Proactivo (50%)</span>
                    <span>Enfocado en Cierre & CTA (100%)</span>
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 14px;">
                  <div class="form-group" style="margin-bottom: 0;">
                    <label>Pregunta de Cierre (Engagement / Ventas):</label>
                    <select id="setting-closing-rule">
                      <option value="always">Siempre rematar con pregunta para fomentar el chat</option>
                      <option value="relevant">Solo en consultas comerciales o dudas</option>
                      <option value="never">Sin preguntas de cierre</option>
                    </select>
                  </div>
                  <div class="form-group" style="margin-bottom: 0;">
                    <label>Estilo de Emojis:</label>
                    <select id="setting-emoji-style">
                      <option value="minimal">Sobrio (1 emoji selecto: 🤝 o 💡)</option>
                      <option value="moderate" selected>Moderado (2-3 emojis: 🚀 🤝 💡 ✨)</option>
                      <option value="expressive">Expresivo & Dinámico (3-4 emojis)</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Block 3: Golden Rules (Keywords & Forbidden Words) -->
              <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
                <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 6px; color: var(--accent-cyan); display: flex; align-items: center; gap: 8px;">
                  <span>🛡️ Conceptos Clave & Frases Prohibidas</span>
                </h4>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 16px;">Define los términos que la IA debe incorporar y las palabras prohibidas para erradicar respuestas genéricas.</p>

                <!-- Key Phrases -->
                <div class="form-group">
                  <label>✨ Conceptos / Propuestas de Valor a Promover (Escribe y pulsa Enter):</label>
                  <div class="tag-chips-wrapper" id="key-phrases-container">
                    <input type="text" class="tag-chip-input" id="input-new-key-phrase" placeholder="+ Añadir concepto (ej: Envíos gratis) y Enter..." onkeydown="App.handleTagInput(event, 'key')" />
                  </div>
                </div>

                <!-- Forbidden Phrases (Blacklist) -->
                <div class="form-group" style="margin-bottom: 0;">
                  <label>🚫 Frases / Palabras Prohibidas (La IA nunca las usará):</label>
                  <div class="tag-chips-wrapper" id="forbidden-phrases-container">
                    <input type="text" class="tag-chip-input" id="input-new-forbidden-phrase" placeholder="+ Añadir frase prohibida (ej: Estimado cliente) y Enter..." onkeydown="App.handleTagInput(event, 'forbidden')" />
                  </div>
                </div>
              </div>

              <!-- Block 4: Few-Shot Master Training Examples -->
              <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px;">
                  <div>
                    <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary); display: flex; align-items: center; gap: 8px;">
                      <span>🧠 Ejemplos Maestros de Entrenamiento (Cero Tokens)</span>
                    </h4>
                    <p style="font-size: 0.78rem; color: var(--text-muted);">Enseña a la IA exactamente cómo responder a preguntas de precios, dudas o soporte de esta marca.</p>
                  </div>
                  <button type="button" class="btn-primary-action" style="padding: 6px 12px; font-size: 0.76rem;" onclick="App.openAddExampleModal()">
                    + Añadir Ejemplo de Oro
                  </button>
                </div>

                <div id="few-shot-examples-container" class="few-shot-list">
                  <!-- Rendered dynamically -->
                </div>
              </div>

              <!-- Block 5: Optional AI Engines -->
              <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 24px;">
                <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 14px; color: #fff;">🔌 Motor de Generación & Claves</h4>

                <div class="form-group">
                  <label>Proveedor de Inteligencia:</label>
                  <select id="setting-ai-provider">
                    <option value="gemini" selected>Google Gemini AI (Ultrarrápido y Calibrado con Voz de Marca)</option>
                    <option value="openai">OpenAI (GPT-4o Mini con Calibración Dinámica)</option>
                    <option value="heuristic">⚡ Motor Heurístico Calibrado Local (100% Gratuito - Cero Tokens)</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Google Gemini API Key (Opcional):</label>
                  <input type="password" id="setting-gemini-key" placeholder="AIzaSy..." />
                </div>

                <div class="form-group" style="margin-bottom: 0;">
                  <label>OpenAI API Key (Opcional):</label>
                  <input type="password" id="setting-openai-key" placeholder="sk-proj-..." />
                </div>
              </div>

              <button type="submit" class="btn-primary-action" style="width: 100%; justify-content: center; padding: 14px; font-size: 0.95rem; font-weight: 800;">
                Guardar Voz de Marca y Calibración 💾
              </button>
            </form>
          </div>

          <!-- Right Column: Live Voice Playground -->
          <div>
            <div class="playground-sticky-box">
              <div class="playground-header">
                <span style="font-size: 1.4rem;">⚡</span>
                <div>
                  <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff;">Simulador de Voz en Vivo</h4>
                  <p style="font-size: 0.76rem; color: var(--text-muted);">Evalúa cómo responde la IA con los parámetros de la marca activa en tiempo real.</p>
                </div>
              </div>

              <!-- Quick Scenarios -->
              <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 6px;">Casos Rápidos de Prueba:</div>
              <div class="quick-scenarios-row">
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('price_lead')">🎯 Precio / Lead</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('objection')">🛡️ Garantía / Objeción</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('support')">🛠️ Soporte / Ayuda</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('gratitude')">✨ Elogio / Testimonio</button>
              </div>

              <div class="form-group" style="margin-bottom: 10px;">
                <label style="font-size: 0.76rem;">Nombre del Seguidor:</label>
                <input type="text" id="playground-author" value="Alejandro" style="padding: 7px 10px; font-size: 0.82rem;" />
              </div>

              <div class="form-group">
                <label style="font-size: 0.76rem;">Comentario de Prueba:</label>
                <textarea id="playground-comment" rows="3" style="font-size: 0.82rem;" placeholder="Escribe cualquier comentario ficticio para evaluar la respuesta de la IA..."></textarea>
              </div>

              <button type="button" class="btn-primary-action" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #6366f1, #3b82f6);" onclick="App.testVoicePlayground()">
                <span>⚡ Probar Voz Calibrada de la IA</span>
              </button>

              <!-- Playground Output Results -->
              <div id="playground-results-container" class="playground-results-container" style="display: none;">
                <!-- Generated responses injected dynamically -->
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- View 4: Meta Graph API Setup & Diagnostics -->
    <div id="view-meta" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 860px; margin: 0 auto;">
        <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 6px;">⚙️ Conexión Oficial con Meta Graph API (Instagram & Facebook)</h3>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 24px;">Configura y diagnostica la vinculación segura de tu cuenta con Meta para acceder a estadísticas en vivo y moderación automática.</p>

        <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
          <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 16px; color: var(--fb-blue);">🔑 Credenciales y Tokens de Meta</h4>

          <form onsubmit="App.saveSettingsForm(event)">
            <div class="form-group">
              <label>Meta App ID:</label>
              <input type="text" id="setting-meta-app-id" placeholder="Ej: 102938475610293" />
            </div>

            <div class="form-group">
              <label>Instagram Business Account ID:</label>
              <input type="text" id="setting-meta-ig-id" placeholder="Ej: 17841400000000000" />
            </div>

            <div class="form-group">
              <label>Meta Page Access Token:</label>
              <input type="password" id="setting-meta-token" placeholder="EAA..." />
            </div>

            <div style="display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap;">
              <button type="submit" class="btn-primary-action">
                Guardar Tokens 💾
              </button>
              <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #06b6d4, #0284c7);" onclick="App.testMetaConnection()">
                🔍 Diagnosticar y Probar Conexión con Meta
              </button>
              <button type="button" class="btn-primary-action" style="background: #1877f2;" onclick="App.triggerMetaSync()">
                🔄 Sincronizar en Vivo Ahora
              </button>
            </div>
          </form>

          <!-- Live Diagnostics Report Box -->
          <div id="meta-diagnostic-container" style="display: none;">
            <!-- Diagnostic results injected dynamically -->
          </div>
        </div>

        <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
          <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 12px;">📡 Webhook en Tiempo Real (Meta Developers)</h4>
          <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 14px;">Para que el agente reciba y responda comentarios inmediatamente cuando se publican en tus posts:</p>

          <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.85rem; color: #a5b4fc; margin-bottom: 8px;">
            Callback URL: <strong>https://tudominio.com/api/webhook.php</strong>
          </div>
          <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.85rem; color: #34d399;">
            Verify Token: <strong>social_boost_secure_token_2026</strong>
          </div>
          <div style="margin-top: 10px; font-size: 0.78rem; color: var(--text-dim);">
            Campos requeridos en la suscripción del Webhook: <code style="color: #f1f5f9;">feed</code> (Páginas de Facebook) y <code style="color: #f1f5f9;">comments</code>, <code style="color: #f1f5f9;">mentions</code> (Instagram Graph API).
          </div>
        </div>

        <!-- Compliance & Security Guide -->
        <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
          <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 14px; color: var(--accent-cyan);">📋 URLs Oficiales para Registrar en tu Meta App Dashboard</h4>
          <p style="font-size: 0.84rem; color: var(--text-muted); margin-bottom: 16px;">
            Copia y pega estas URLs directamente en <strong>Meta for Developers &gt; Configuración básica de la App</strong> y en la sección de <strong>Revisión de la App (App Review)</strong> cuando subas la aplicación a tu servidor con dominio:
          </p>

          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
              <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 4px;">URL de la Política de Privacidad (Privacy Policy URL):</div>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <code style="color: #a5b4fc; font-size: 0.85rem;">https://tudominio.com/privacy-policy.php</code>
                <a href="privacy-policy.php" target="_blank" class="btn-primary-action" style="padding: 4px 10px; font-size: 0.72rem; text-decoration: none;">Ver Página ↗️</a>
              </div>
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
              <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 4px;">URL de las Condiciones del Servicio (Terms of Service URL):</div>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <code style="color: #a5b4fc; font-size: 0.85rem;">https://tudominio.com/terms-of-service.php</code>
                <a href="terms-of-service.php" target="_blank" class="btn-primary-action" style="padding: 4px 10px; font-size: 0.72rem; text-decoration: none;">Ver Página ↗️</a>
              </div>
            </div>

            <div style="background: rgba(0,0,0,0.3); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
              <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 4px;">URL de Eliminación de Datos de Usuario (Data Deletion Instructions / Callback):</div>
              <div style="display: flex; justify-content: space-between; align-items: center;">
                <code style="color: #38bdf8; font-size: 0.85rem;">https://tudominio.com/data-deletion.php</code>
                <a href="data-deletion.php" target="_blank" class="btn-primary-action" style="padding: 4px 10px; font-size: 0.72rem; text-decoration: none;">Ver Página ↗️</a>
              </div>
              <small style="font-size: 0.72rem; color: var(--text-dim); margin-top: 4px; display: block;">Callback API alternativo: <code>https://tudominio.com/api/data-deletion.php</code></small>
            </div>
          </div>
        </div>

        <!-- Compliance & Security Guide -->
        <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 12px; color: var(--accent-emerald);">🛡️ Requisitos para que Meta apruebe esta Integración Oficial</h4>
          <ul style="font-size: 0.84rem; color: var(--text-muted); line-height: 1.8; padding-left: 20px;">
            <li><strong>Verificación de Negocio (Business Verification):</strong> En Meta Business Manager para solicitar permisos avanzados en App Review.</li>
            <li><strong>Cuenta de Instagram Profesional:</strong> Debe ser tipo <em>Creador</em> o <em>Empresa</em> vinculada a una Página de Facebook.</li>
            <li><strong>Protocolo HTTPS / SSL:</strong> La URL de tu Webhook y sitio web debe contar con un certificado SSL válido (TLS 1.2+).</li>
            <li><strong>Políticas de Privacidad y Términos Públicos:</strong> Registradas con las URLs provistas arriba.</li>
          </ul>
        </div>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="mobile-bottom-nav">
      <button type="button" class="bottom-nav-btn active" data-tab="inbox" onclick="App.switchTab('inbox')">
        <span class="bottom-nav-icon">📥</span>
        <span>Inbox</span>
      </button>
      <button type="button" class="bottom-nav-btn" data-tab="analytics" onclick="App.switchTab('analytics')">
        <span class="bottom-nav-icon">📈</span>
        <span>Métricas</span>
      </button>
      <button type="button" class="bottom-nav-btn" data-tab="settings" onclick="App.switchTab('settings')">
        <span class="bottom-nav-icon">🏛️</span>
        <span>Voz IA</span>
      </button>
      <button type="button" class="bottom-nav-btn" data-tab="meta" onclick="App.switchTab('meta')">
        <span class="bottom-nav-icon">⚙️</span>
        <span>Meta</span>
      </button>
    </nav>

  </main>
</div>

<!-- Modal: Simulate New Comment -->
<div class="modal-overlay" id="modal-simulate">
  <div class="modal-box">
    <div class="modal-header">
      <h3>🚀 Simular Comentario de la Comunidad</h3>
      <button class="btn-close-modal" onclick="App.closeModal('modal-simulate')">&times;</button>
    </div>

    <form onsubmit="App.submitSimulatedComment(event)">
      <div class="form-group">
        <label>Red Social:</label>
        <select id="sim-platform">
          <option value="instagram">📸 Instagram</option>
          <option value="facebook">📘 Facebook</option>
        </select>
      </div>

      <div class="form-group">
        <label>Nombre del Seguidor:</label>
        <input type="text" id="sim-author" placeholder="Ej: Marcos Valenzuela" required />
      </div>

      <div class="form-group">
        <label>Texto del Comentario:</label>
        <textarea id="sim-comment" rows="3" placeholder="Ej: Me cuesta mucho mantener la disciplina cuando estoy desmotivado, ¿cómo aplico el estoicismo en mi día a día?" required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="btn-primary-action" style="background: rgba(255,255,255,0.1);" onclick="App.closeModal('modal-simulate')">Cancelar</button>
        <button type="submit" class="btn-primary-action">Analizar con Agente IA ✨</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add Few-Shot Master Training Example -->
<div class="modal-overlay" id="modal-add-few-shot">
  <div class="modal-box" style="max-width: 600px;">
    <div class="modal-header">
      <h3>🧠 Añadir Ejemplo Maestro de Entrenamiento (Cero Tokens)</h3>
      <button class="btn-close-modal" onclick="App.closeModal('modal-add-few-shot')">&times;</button>
    </div>

    <form onsubmit="App.saveNewFewShotExample(event)">
      <div class="form-group">
        <label>Etiqueta / Temática del Caso:</label>
        <input type="text" id="few-shot-tag-input" placeholder="Ej: desmotivacion, libros, habitos, duelo..." required />
      </div>

      <div class="form-group">
        <label>Comentario Típico del Seguidor:</label>
        <textarea id="few-shot-comment-input" rows="3" placeholder="Ej: Siento que no tengo fuerza de voluntad para entrenar todos los días..." required></textarea>
      </div>

      <div class="form-group">
        <label>Respuesta Maestra Ideal de la Marca (Usa {nombre} donde quieras insertar el nombre del seguidor):</label>
        <textarea id="few-shot-reply-input" rows="4" placeholder="Ej: Te entiendo, {nombre}. La motivación es pasajera; la disciplina es el hábito innegociable..." required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
        <button type="button" class="btn-primary-action" style="background: rgba(255,255,255,0.1);" onclick="App.closeModal('modal-add-few-shot')">Cancelar</button>
        <button type="submit" class="btn-primary-action">Guardar Ejemplo Maestro 🧠</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Asistente de Respuestas & Conexión (Popup de Sugerencias Reflexivas, Motivacionales, Comunitarias y Auto-Responder en Vivo) -->
<div class="modal-overlay modal-assistant-overlay" id="modal-assistant-replies" onclick="if(event.target===this) App.closeModal('modal-assistant-replies')">
  <div class="modal-box modal-assistant-box">
    
    <!-- Modal Header -->
    <div class="modal-header modal-assistant-header">
      <div class="modal-assistant-title-group">
        <div class="modal-assistant-icon-badge">🪄</div>
        <div>
          <h3 class="modal-assistant-title">Asistente de Respuestas & Conexión</h3>
          <p class="modal-assistant-subtitle">Sugerencias reflexivas, motivacionales y piloto automático para tu comunidad</p>
        </div>
      </div>
      <div class="modal-header-actions">
        <span class="score-badge high" id="modal-score-badge" onclick="App.openScoreGuideModal()" style="cursor: pointer;" title="Haz clic para ver la Guía de Score">⭐ Score 95/100 ℹ️</span>
        <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-assistant-replies')" title="Cerrar">&times;</button>
      </div>
    </div>

    <!-- Modal View Tabs Switcher -->
    <div class="modal-assistant-tab-switcher">
      <button type="button" class="modal-tab-btn active" id="modal-tab-btn-manual" onclick="AgentController.switchModalTab('manual')">
        <span>🪄 Sugerencias Manuales</span>
      </button>
      <button type="button" class="modal-tab-btn" id="modal-tab-btn-autopilot" onclick="AgentController.switchModalTab('autopilot')">
        <span>⚡ Piloto Automático & Respuestas en Vivo</span>
        <span class="pulse-live-indicator">LIVE</span>
      </button>
    </div>

    <div class="modal-assistant-body">
      
      <!-- SUBVIEW 1: Manual AI Suggestions -->
      <div id="modal-view-manual" class="modal-subview active">
        <!-- Top Bar: Select Comment & Tone -->
        <div class="modal-assistant-controls-row">
          <!-- Comment Switcher -->
          <div class="modal-control-item modal-control-item-comment">
            <label class="modal-control-label" for="modal-select-comment">💬 Comentario a Responder:</label>
            <select id="modal-select-comment" class="modal-control-select" onchange="AgentController.onModalCommentChange(this.value)">
              <option value="">Cargando comentarios...</option>
            </select>
          </div>

          <!-- Tone Switcher -->
          <div class="modal-control-item modal-control-item-tone">
            <label class="modal-control-label" for="modal-select-tone">🎭 Tono de la IA:</label>
            <select id="modal-select-tone" class="modal-control-select" onchange="AgentController.onModalToneChange(this.value)">
              <option value="stoic_mentor">🏛️ Estoico & Sabio (Filosófico)</option>
              <option value="disciplined_drive">⚔️ Motivador & Disciplinado (Fuerza)</option>
              <option value="empathetic_brother">🤝 Empático & Fraternal (Cercano)</option>
              <option value="stoic_quotes">📜 Citas & Sabiduría Práctica</option>
              <option value="challenging">🔥 Desafiante & Enérgico</option>
            </select>
          </div>

          <button type="button" class="btn-modal-refresh-suggestions" onclick="AgentController.refreshModalSuggestions()" title="Regenerar nuevas sugerencias con IA">
            <span>🔄 Regenerar</span>
          </button>
        </div>

        <!-- Follower Comment Context Box -->
        <div class="modal-follower-context-card">
          <div class="modal-follower-header">
            <div class="modal-follower-info">
              <img src="https://ui-avatars.com/api/?name=User&background=6366f1&color=fff&size=96" id="modal-author-avatar" width="44" height="44" loading="lazy" decoding="async" class="modal-follower-avatar" alt="avatar" />
              <div>
                <div class="modal-follower-name-row">
                  <strong id="modal-author-name">Selecciona un comentario</strong>
                  <span id="modal-platform-badge" class="platform-badge-mini instagram">IG</span>
                  <span id="modal-author-handle" class="modal-follower-handle">@usuario</span>
                </div>
                <div class="modal-post-ref" id="modal-post-caption-preview">Sobre: Publicación de la comunidad...</div>
              </div>
            </div>
            <div id="modal-sentiment-tag" class="sentiment-badge" style="background: rgba(99,102,241,0.15); color: #a5b4fc;">
              ✨ Engagement Activo
            </div>
          </div>

          <div class="modal-comment-quote-box">
            <span class="quote-icon">“</span>
            <p id="modal-comment-quote-text">Esperando selección de comentario...</p>
          </div>

          <div id="modal-reason-banner" class="modal-reason-banner">
            ✨ Análisis del agente: Sugerencias optimizadas para enriquecer la conversación comunitaria.
          </div>
        </div>

        <!-- 3 AI Suggestion Cards Container -->
        <div class="modal-suggestions-section">
          <div class="modal-section-title">
            <span>💡 3 Sugerencias Forjadas por la IA (Haz clic para usar o copiar):</span>
          </div>

          <div id="modal-suggestions-container" class="modal-suggestions-grid">
            <!-- Suggestion Cards injected dynamically -->
          </div>
        </div>

        <!-- Live Reply Customizer & Actions -->
        <div class="modal-reply-editor-section">
          <div class="modal-editor-header">
            <label class="modal-control-label" for="modal-reply-text-input">
              ✍️ Personalizar Mensaje para el Seguidor:
            </label>
            <div class="emoji-quick-pills">
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('🏛️')">🏛️</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('⚔️')">⚔️</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('🛡️')">🛡️</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('🔥')">🔥</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('🧠')">🧠</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('⏳')">⏳</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('🤝')">🤝</button>
              <button type="button" class="emoji-pill" onclick="AgentController.insertModalEmoji('✨')">✨</button>
            </div>
          </div>

          <textarea id="modal-reply-text-input" class="reply-textarea modal-reply-textarea" rows="3" placeholder="Selecciona una de las 3 opciones de arriba o redacta tu respuesta personalizada..."></textarea>

          <div class="modal-editor-footer">
            <div style="font-size: 0.74rem; color: var(--text-dim);">
              🛡️ Conexión empática y estoica con tu comunidad
            </div>
            <div style="display: flex; gap: 10px;">
              <button type="button" class="btn-modal-cancel" onclick="App.closeModal('modal-assistant-replies')">
                Cerrar
              </button>
              <button type="button" class="btn-send-reply modal-btn-send" id="btn-modal-submit-reply" onclick="AgentController.submitModalReply()">
                <span>Publicar Respuesta</span> 🏛️
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- SUBVIEW 2: Live Autopilot Monitor -->
      <div id="modal-view-autopilot" class="modal-subview" style="display: none;">
        <div class="autopilot-monitor-card">
          <div class="autopilot-monitor-header">
            <div class="autopilot-status-box">
              <span class="autopilot-status-dot"></span>
              <div>
                <h4>Piloto Automático de Respuestas con IA</h4>
                <p>La IA analiza y responde en tiempo real a los comentarios de mayor impacto comunitario.</p>
              </div>
            </div>
            <div class="autopilot-header-btn-wrap">
              <button type="button" class="btn-run-autopilot-live" id="btn-run-autopilot-live" onclick="AgentController.startLiveAutopilot()">
                <span class="btn-icon">⚡</span>
                <span id="btn-run-autopilot-live-text">Ejecutar Auto-Responder Ahora</span>
              </button>
            </div>
          </div>

          <!-- Autopilot Live Progress Bar -->
          <div class="autopilot-progress-container" id="autopilot-progress-container" style="display: none;">
            <div class="autopilot-progress-header">
              <span id="autopilot-progress-status">⚡ Conectando con el motor de IA...</span>
              <span id="autopilot-progress-percent">0%</span>
            </div>
            <div class="autopilot-progress-track">
              <div class="autopilot-progress-bar" id="autopilot-progress-bar" style="width: 0%;"></div>
            </div>
          </div>

          <!-- Live Activity Log Stream -->
          <div class="autopilot-live-stream-box">
            <div class="autopilot-stream-title-row">
              <h5>📡 Registro de Respuestas Forjadas por la IA en Tiempo Real:</h5>
              <span class="autopilot-stream-badge" id="autopilot-pending-count-badge">Calculando pendientes...</span>
            </div>

            <div class="autopilot-stream-list" id="autopilot-stream-list">
              <div class="autopilot-empty-state">
                <span style="font-size: 2rem;">🤖</span>
                <p style="font-size: 0.88rem; font-weight: 700; color: #fff; margin-top: 8px;">Monitor de Respuestas Listo</p>
                <p style="font-size: 0.78rem; color: var(--text-muted); max-width: 420px; margin: 4px auto 0;">
                  Haz clic en <strong>"Ejecutar Auto-Responder Ahora"</strong> para ver cómo la IA analiza a cada seguidor y publica respuestas reflexivas y motivacionales en vivo.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal: Guía del Sistema de Score de IA -->
<div class="modal-overlay" id="modal-score-guide" onclick="if(event.target===this) App.closeModal('modal-score-guide')">
  <div class="modal-box modal-score-guide-box">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.5rem;">🎯</span>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff;">¿Cómo funciona el Score de IA? (0 a 100)</h3>
          <p style="font-size: 0.76rem; color: var(--text-muted);">Índice inteligente de prioridad e impacto comunitario para conectar con tus seguidores</p>
        </div>
      </div>
      <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-score-guide')">&times;</button>
    </div>

    <div class="modal-score-guide-body">
      <div class="score-guide-intro">
        <p>Todo comentario inicia con una <strong>base de 45 puntos</strong> y el motor de IA analiza en tiempo real 4 pilares esenciales:</p>
      </div>

      <div class="score-cards-grid">
        <!-- Level 1: 95 -->
        <div class="score-guide-card score-card-95">
          <div class="score-guide-card-top">
            <span class="score-guide-badge badge-rose">🛡️ Score 95 / 100</span>
            <span class="score-guide-tag">Máxima Prioridad</span>
          </div>
          <h4>Apoyo Emocional & Resiliencia</h4>
          <p>Detecta seguidores atravesando momentos difíciles, duelo o ansiedad (ej: <em>"perdí mi trabajo"</em>, <em>"ansiedad"</em>, <em>"no puedo más"</em>, <em>"necesitaba leer esto"</em>).</p>
          <div class="score-guide-aim">🎯 <strong>Objetivo:</strong> Brindar respuesta empática y fraternal de inmediato.</div>
        </div>

        <!-- Level 2: 92 -->
        <div class="score-guide-card score-card-92">
          <div class="score-guide-card-top">
            <span class="score-guide-badge badge-cyan">🧠 Score 92 / 100</span>
            <span class="score-guide-tag">Alto Valor</span>
          </div>
          <h4>Pregunta Filosófica / Consejo Práctico</h4>
          <p>Detecta dudas profundas y consultas aplicadas (ej: <em>"¿cómo controlo la ira?"</em>, <em>"¿qué libro me recomiendas?"</em>, <em>"disciplina"</em>, <em>"Marco Aurelio"</em>).</p>
          <div class="score-guide-aim">🎯 <strong>Objetivo:</strong> Generar debate de alto nivel y enseñanza comunitaria.</div>
        </div>

        <!-- Level 3: 88 -->
        <div class="score-guide-card score-card-88">
          <div class="score-guide-card-top">
            <span class="score-guide-badge badge-emerald">✨ Score 88 / 100</span>
            <span class="score-guide-tag">Fidelización</span>
          </div>
          <h4>Testimonio de Impacto & Gratitud</h4>
          <p>Detecta seguidores inspirados o agradecidos (ej: <em>"cambió mi vida"</em>, <em>"oro puro"</em>, <em>"me llegó al alma"</em>, <em>"mi cuenta favorita"</em>).</p>
          <div class="score-guide-aim">🎯 <strong>Objetivo:</strong> Fidelizar embajadores de marca y fortalecer la lealtad.</div>
        </div>

        <!-- Level 4: 80 -->
        <div class="score-guide-card score-card-80">
          <div class="score-guide-card-top">
            <span class="score-guide-badge badge-amber">❓ Score 80 / 100</span>
            <span class="score-guide-tag">Interacción</span>
          </div>
          <h4>Pregunta Abierta General</h4>
          <p>Comentarios con signos de interrogación (<code>¿?</code>) que esperan una respuesta clara y directa.</p>
          <div class="score-guide-aim">🎯 <strong>Objetivo:</strong> Mantener una tasa de respuesta del 100% en dudas.</div>
        </div>
      </div>

      <!-- Bonification & Multipliers Banner -->
      <div class="score-multipliers-box">
        <h5 style="color: #fff; font-size: 0.88rem; font-weight: 700; margin-bottom: 8px;">🔥 Bonificaciones Adicionales por Tracción:</h5>
        <div class="score-multipliers-list">
          <div class="multiplier-item">
            <span class="multiplier-pill">+8 Pts</span>
            <span><strong>Alta Tracción:</strong> Si el comentario tiene <strong>≥ 10 likes</strong> de otros usuarios.</span>
          </div>
          <div class="multiplier-item">
            <span class="multiplier-pill">+4 Pts</span>
            <span><strong>Interés Común:</strong> Si el comentario tiene <strong>≥ 5 likes</strong>.</span>
          </div>
          <div class="multiplier-item">
            <span class="multiplier-pill">+6 Pts</span>
            <span><strong>Profundidad:</strong> Si supera los <strong>70 caracteres</strong> de reflexión elaborada.</span>
          </div>
        </div>
      </div>

      <!-- Autopilot connection note -->
      <div class="score-autopilot-note">
        <span>⚡</span>
        <div>
          <strong>Conexión con el Auto-Responder:</strong>
          <span>Los comentarios con <strong>Score ≥ 80</strong> son priorizados por el Piloto Automático para responder automáticamente con la mejor variante estoica forjada por la IA.</span>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 18px;">
        <button type="button" class="btn-primary-action" onclick="App.closeModal('modal-score-guide')">Entendido ✨</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Seguimiento & Detalle de Comentario (Publicación + Comentario + Respuesta Registrada) -->
<div class="modal-overlay" id="modal-comment-detail" onclick="if(event.target===this) App.closeModal('modal-comment-detail')">
  <div class="modal-box modal-comment-detail-box">
    
    <!-- Header -->
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 1.5rem;">📌</span>
        <div>
          <h3 style="font-size: 1.18rem; font-weight: 800; color: #fff;">Seguimiento de Comentario</h3>
          <p style="font-size: 0.78rem; color: var(--text-muted);">Visualiza la publicación de origen, el comentario del seguidor y la respuesta registrada.</p>
        </div>
      </div>
      <div style="display: flex; align-items: center; gap: 10px;">
        <span class="score-badge high" id="detail-score-badge">⭐ Score 95/100</span>
        <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-comment-detail')">&times;</button>
      </div>
    </div>

    <div class="modal-comment-detail-body">
      
      <!-- 1. Bloque: Publicación Original de Origen -->
      <div class="detail-section-block">
        <div class="detail-section-title">
          <span>📸 1. Publicación de Origen:</span>
          <span class="detail-platform-badge" id="detail-post-platform-badge">Instagram</span>
        </div>
        <div class="detail-post-card">
          <img src="https://images.unsplash.com/photo-1552346154-21d32810aba3?w=160&h=160&fit=crop&auto=format&q=75" id="detail-post-image" width="70" height="70" loading="lazy" decoding="async" class="detail-post-thumb" alt="post thumbnail" />
          <div class="detail-post-content">
            <div class="detail-post-meta" id="detail-post-meta-text">
              👁️ Alcance • ❤️ Likes • 💬 Comentarios
            </div>
            <div class="detail-post-caption-box">
              <span class="quote-symbol">“</span>
              <p id="detail-post-caption-text">Cargando publicación original...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Bloque: Comentario del Seguidor -->
      <div class="detail-section-block">
        <div class="detail-section-title">
          <span>💬 2. Comentario del Seguidor:</span>
          <span class="detail-sentiment-badge" id="detail-sentiment-badge">Apoyo Emocional</span>
        </div>
        <div class="detail-follower-card">
          <div class="detail-follower-header">
            <img src="https://ui-avatars.com/api/?name=User&background=6366f1&color=fff&size=96" id="detail-author-avatar" width="40" height="40" loading="lazy" decoding="async" class="detail-author-avatar" alt="avatar" />
            <div class="detail-author-names">
              <strong id="detail-author-name">Nombre del seguidor</strong>
              <span id="detail-author-handle" class="detail-author-handle">@usuario</span>
            </div>
            <div class="detail-comment-time" id="detail-comment-time">Reciente</div>
          </div>
          <div class="detail-comment-quote-box">
            <p id="detail-comment-text">"Cargando comentario..."</p>
          </div>
        </div>
      </div>

      <!-- 3. Bloque: Respuesta Registrada / Asistente -->
      <div class="detail-section-block">
        <div class="detail-section-title">
          <span>🏛️ 3. Respuesta Registrada & Conexión:</span>
          <span class="detail-status-badge" id="detail-status-badge">✅ Respondido</span>
        </div>

        <!-- If replied: shows the reply -->
        <div id="detail-reply-content-box" class="detail-reply-box" style="display: none;">
          <div class="detail-reply-header">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span class="detail-brand-avatar">⚡</span>
              <strong style="color: #fff; font-size: 0.88rem;" id="detail-brand-name-display">XINDRO Copilot</strong>
              <span class="detail-variant-pill" id="detail-reply-variant-tag">Respuesta Publicada</span>
            </div>
            <span class="detail-reply-time" id="detail-reply-time">Publicada</span>
          </div>
          <div class="detail-reply-text-box" id="detail-reply-text-box">
            "Respuesta registrada..."
          </div>
          <div class="detail-reply-actions">
            <button type="button" class="btn-detail-reopen-assistant" onclick="App.openAssistantFromDetail()">
              <span>🪄 Abrir en Copiloto para Responder Otra Cosa</span>
            </button>
          </div>
        </div>

        <!-- If pending: shows notice + button to open assistant -->
        <div id="detail-pending-notice-box" class="detail-pending-box" style="display: none;">
          <div class="detail-pending-icon">⏳</div>
          <div class="detail-pending-info">
            <h4>Este comentario aún no ha sido respondido</h4>
            <p>Abre el Copiloto de Conversión para forjar una respuesta inteligente calibrada con la voz de tu marca.</p>
          </div>
          <button type="button" class="btn-detail-respond-now" onclick="App.openAssistantFromDetail()">
            <span>🪄 Responder con Copiloto ✨</span>
          </button>
        </div>
      </div>

    </div>

    <div class="modal-footer" style="padding-top: 14px; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end;">
      <button type="button" class="btn-primary-action" onclick="App.closeModal('modal-comment-detail')">
        Cerrar
      </button>
    </div>

  </div>
</div>

<!-- Modal: Add New Brand Voice (Agency Multi-Client) -->
<div class="modal-overlay" id="modal-new-brand">
  <div class="modal-box" style="max-width: 580px;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 1.5rem;">🏢</span>
        <div>
          <h3 style="font-size: 1.18rem; font-weight: 800; color: #fff;">Crear Nueva Marca o Cliente</h3>
          <p style="font-size: 0.78rem; color: var(--text-muted);">Añade un nuevo cliente con su propia persona, nicho y prompt dinámico independiente.</p>
        </div>
      </div>
      <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-new-brand')">&times;</button>
    </div>

    <form onsubmit="App.submitCreateNewBrand(event)">
      <div class="modal-body" style="padding: 16px 0;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label>Nombre de la Marca / Cliente:</label>
            <input type="text" id="new-brand-name" placeholder="Ej: Tienda Aurora / Inmobiliaria VIP" required />
          </div>

          <div class="form-group" style="margin-bottom: 0;">
            <label>Persona / Asistente de Marca:</label>
            <input type="text" id="new-brand-persona" placeholder="Ej: Sofía de Ventas / Coach Daniel" required />
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
          <div class="form-group" style="margin-bottom: 0;">
            <label>Industria / Nicho:</label>
            <input type="text" id="new-brand-industry" placeholder="Ej: Moda & Calzado, Fitness, Consultoría" required />
          </div>

          <div class="form-group" style="margin-bottom: 0;">
            <label>Idioma Principal:</label>
            <select id="new-brand-language">
              <option value="es" selected>🇪🇸 Español</option>
              <option value="en">🇺🇸 English</option>
              <option value="pt">🇧🇷 Português</option>
              <option value="any">🌐 Auto-detectar</option>
            </select>
          </div>
        </div>

        <div class="form-group" style="margin-bottom: 12px;">
          <label>Tono Base de Comunicación:</label>
          <select id="new-brand-tone">
            <option value="friendly_engaging">🤝 Cercano, Amable & Empático</option>
            <option value="commercial_sales" selected>🎯 Comercial & Ventas (Enfoque en Leads / DM)</option>
            <option value="executive_formal">💼 Ejecutivo & Corporativo</option>
            <option value="educational_expert">💡 Educativo & Experto</option>
            <option value="humorous_casual">🔥 Dinámico & Casual</option>
          </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
          <label>System Prompt Dinámico Inicial (Instrucciones para la IA):</label>
          <textarea id="new-brand-prompt" rows="3" placeholder="Describe cómo debe responder la IA, qué servicios o productos vende esta marca y cómo dirigir a los prospectos al DM o web..."></textarea>
        </div>

      </div>

      <div class="modal-footer" style="padding-top: 14px; border-top: 1px solid var(--border-subtle); display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn-primary-action" style="background: rgba(255,255,255,0.08); color: var(--text-muted);" onclick="App.closeModal('modal-new-brand')">
          Cancelar
        </button>
        <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">
          Crear Marca y Activar 🚀
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Scripts -->
<script src="assets/js/agent-controller.js"></script>
<script src="assets/js/analytics.js"></script>
<script src="assets/js/app.js"></script>

</body>
</html>
