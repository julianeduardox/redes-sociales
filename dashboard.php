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
$isAdmin = Auth::isAdmin();
$csrfToken = Security::getCsrfToken();
$userBrands = [];
$activeAccountsCount = 0;

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

    $stmtAccCount = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE user_id = ? AND is_active = 1");
    $stmtAccCount->execute([$userId]);
    $activeAccountsCount = (int)$stmtAccCount->fetchColumn();

    $userPlan = $currentUser['plan'] ?? 'starter';
    $planDetails = Database::getPlanDetails($userPlan);
    $maxAccounts = (int)($currentUser['max_accounts'] ?? $planDetails['accounts'] ?? 1);
} catch (Throwable $e) {
    error_log("Brand pre-render notice: " . $e->getMessage());
    $userBrands = [];
    $activeAccountsCount = 0;
    $userPlan = 'starter';
    $planDetails = Database::getPlanDetails('starter');
    $maxAccounts = 1;
}
$isMetaConnected = ($activeAccountsCount > 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
  <meta name="user-id" content="<?= (int)$userId ?>">
  <!-- Performance Preconnect for External Assets -->
  <link rel="preconnect" href="https://images.unsplash.com" crossorigin>
  <link rel="dns-prefetch" href="https://images.unsplash.com">
  <link rel="preconnect" href="https://ui-avatars.com" crossorigin>
  
  <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <link rel="alternate icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg%20xmlns=%22http://www.w3.org/2000/svg%22%20viewBox=%220%200%20100%20100%22%3E%3Cdefs%3E%3ClinearGradient%20id=%22g%22%20x1=%220%25%22%20y1=%220%25%22%20x2=%22100%25%22%20y2=%22100%25%22%3E%3Cstop%20offset=%220%25%22%20stop-color=%22%239353FF%22/%3E%3Cstop%20offset=%2250%25%22%20stop-color=%22%237C3AED%22/%3E%3Cstop%20offset=%22100%25%22%20stop-color=%22%234F46E5%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect%20width=%22100%22%20height=%22100%22%20rx=%2226%22%20fill=%22url(%23g)%22/%3E%3Cpath%20d=%22M54%2016%20L25%2053%20H47%20L43%2084%20L75%2047%20H53%20Z%22%20fill=%22%23FFFFFF%22%20stroke=%22%23FFFFFF%22%20stroke-width=%224%22%20stroke-linejoin=%22round%22%20stroke-linecap=%22round%22/%3E%3C/svg%3E">
</head>
<body>

<div class="app-container">

  <!-- Mobile Sidebar Backdrop -->
  <div class="sidebar-backdrop" id="sidebar-backdrop" onclick="App.toggleMobileSidebar(false)"></div>

  <!-- Sidebar Navigation -->
  <aside class="app-sidebar" id="app-sidebar">
    <div class="sidebar-header">
      <div class="brand-icon-box" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); display: flex; align-items: center; justify-content: center;">
        <svg width="20" height="20" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M54 16 L25 53 H47 L43 84 L75 47 H53 Z" fill="#FFFFFF" stroke="#FFFFFF" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"/>
        </svg>
      </div>
      <div class="brand-text">
        <div style="display: flex; align-items: center; gap: 7px;">
          <h1 style="font-family: 'Syne', sans-serif; font-weight: 900; letter-spacing: -0.02em; margin: 0; font-size: 1.15rem;">XINDRO Copilot</h1>
          <span id="sidebar-connection-status-pill" class="connection-status-badge <?= $isMetaConnected ? 'on' : 'off' ?>" title="<?= $isMetaConnected ? ($activeAccountsCount . ' cuenta' . ($activeAccountsCount === 1 ? '' : 's') . ' vinculada' . ($activeAccountsCount === 1 ? '' : 's')) : 'Sin cuentas vinculadas' ?>">
            <span class="status-dot"></span>
            <span class="status-label"><?= $isMetaConnected ? 'ON' : 'OFF' ?></span>
          </span>
        </div>
        <div class="brand-tag" style="margin-top: 2px;">Multi-Brand & Agency AI OS</div>
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

    <nav class="sidebar-nav">
      <div class="nav-section-title">Comunidad & Respuestas</div>
      <button class="nav-btn active" data-tab="inbox">
        <span class="icon">📥</span>
        <span>Comentarios</span>
        <span class="nav-badge" id="badge-count-inbox">0</span>
      </button>

      <button class="nav-btn" data-tab="highlights" title="Comentarios destacados por IA y consultas comerciales (Leads)">
        <span class="icon">⭐</span>
        <span>Destacados & Leads</span>
        <span class="nav-badge fire" id="badge-count-highlights">0</span>
      </button>

      <button class="nav-btn" data-tab="urgent">
        <span class="icon">🛡️</span>
        <span>Soporte</span>
      </button>

      <button class="nav-btn" data-tab="spam">
        <span class="icon">🚫</span>
        <span>Spam</span>
        <span class="nav-badge" id="badge-count-spam" style="background: rgba(244,63,94,0.25); color: #fb7185;">0</span>
      </button>

      <div class="nav-section-title" style="margin-top: 10px;">Estrategia & Crecimiento</div>
      <button class="nav-btn" data-tab="planner">
        <span class="icon">📅</span>
        <span>Planificador</span>
      </button>

      <button class="nav-btn" data-tab="radar" title="Radar de Creadores & Recreación de Frases Épicas">
        <span class="icon">🧭</span>
        <span>Radar Creadores</span>
        <span class="nav-badge" id="badge-count-radar" style="background: rgba(139,92,246,0.25); color: #a78bfa;">IA</span>
      </button>

      <button class="nav-btn" data-tab="atenea-learning" title="Atenea Intelligence - Motor de Aprendizaje Continuo de @fortaleza_imparable">
        <span class="icon">🏛️</span>
        <span>Atenea Learning</span>
        <span class="nav-badge" id="badge-atenea-learning" style="background: rgba(245,158,11,0.25); color: #fbbf24;">PRO</span>
      </button>

      <button class="nav-btn" data-tab="analytics">
        <span class="icon">📊</span>
        <span>Estadísticas</span>
      </button>

      <button class="nav-btn" data-tab="settings">
        <span class="icon">🤖</span>
        <span>Voz de Marca IA</span>
      </button>

      <button class="nav-btn" data-tab="meta">
        <span class="icon">🔗</span>
        <span>Conexión Meta</span>
      </button>

      <?php if ($isAdmin): ?>
      <button class="nav-btn" data-tab="users">
        <span class="icon">👥</span>
        <span>Usuarios & Tokens</span>
      </button>
      <?php endif; ?>
    </nav>

    <!-- Compact Copilot Quick Trigger -->
    <div class="sidebar-assistant-compact">
      <button type="button" class="btn-sidebar-assistant-compact" onclick="AgentController.openAssistantModal()" title="Abrir Copiloto IA de Respuestas">
        <span>✨ Copiloto IA</span>
      </button>
      <button type="button" class="sidebar-score-guide-btn" onclick="App.openScoreGuideModal()" title="Ver cómo se calcula el Score de IA">
        <span>🎯 Score</span>
      </button>
    </div>

    <div class="sidebar-footer">
      <div class="autopilot-widget">
        <div class="autopilot-info">
          <span class="autopilot-title">⚡ Auto-Responder</span>
          <span class="autopilot-sub" id="autopilot-sub-mode-text">Respuestas inteligentes</span>
        </div>
        <label class="switch">
          <input type="checkbox" id="autopilot-sidebar-toggle">
          <span class="slider"></span>
        </label>
      </div>

      <button type="button" class="btn-sidebar-autopilot-run" onclick="AgentController.runAutopilotBatch()" title="Ejecutar respuestas automáticas pendientes ahora">
        <span>⚡ Ejecutar Auto-responder</span>
      </button>

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

    <!-- Topbar (Streamlined & Clean) -->
    <header class="app-topbar">
      <div class="topbar-left">
        <button type="button" class="btn-mobile-menu" id="btn-mobile-menu" onclick="App.toggleMobileSidebar(true)" aria-label="Abrir Menú">☰</button>
        <h2 class="page-title" id="topbar-page-title">Comentarios & Conversación</h2>

        <!-- Agency Multi-Brand Switcher (Shown ONLY when in Voz de Marca IA) -->
        <div class="topbar-brand-switcher" id="topbar-brand-switcher" title="Cambiar de marca o cliente activo" style="display: none;">
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
      </div>

      <div class="topbar-stats">
        <div class="stat-pill score" title="Comentarios destacados por IA">
          <div class="dot"></div>
          <span id="count-pill-highlighted">0 Destacados</span>
        </div>
        <div class="stat-pill leads" title="Consultas de compra y precio">
          <div class="dot"></div>
          <span id="count-pill-leads">0 Leads</span>
        </div>
        <div class="stat-pill urgent" title="Objeciones y soporte prioritario">
          <div class="dot"></div>
          <span id="count-pill-urgent">0 Soporte</span>
        </div>

        <!-- Autonomous Auto-Sync Heartbeat Indicator -->
        <div id="heartbeat-sync-badge" class="stat-pill sync-indicator" title="Sincronización autónoma en vivo (cada 3 min). Clic para sincronizar ahora." onclick="App.executeHeartbeat(true)">
          <div class="heartbeat-dot" id="heartbeat-dot"></div>
          <span id="heartbeat-status-text">Auto-Sync: Activo (3m)</span>
        </div>
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

        <!-- 3-Step Interactive Onboarding Wizard Banner (Full Card) -->
        <div id="dashboard-onboarding-banner" class="dashboard-onboarding-card">
          <div class="onboarding-card-header">
            <div class="onboarding-card-title">
              <span class="onboarding-badge">🚀 Guía Rápida</span>
              <h4>Comienza en 3 pasos simples</h4>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <button type="button" class="btn-toggle-onboarding" onclick="App.toggleOnboardingGuide(false)" title="Plegar a tira compacta">▲ Plegar</button>
              <button type="button" class="btn-dismiss-onboarding" onclick="App.dismissOnboarding()" title="Ocultar guía permanentemente">&times;</button>
            </div>
          </div>
          <div class="onboarding-steps-grid">
            <div class="onboarding-step-item <?= $isMetaConnected ? 'completed' : 'active' ?>" onclick="App.switchTab('meta')">
              <div class="step-num"><?= $isMetaConnected ? '✔' : '1' ?></div>
              <div class="step-content">
                <div class="step-title">1. Conectar Redes</div>
                <div class="step-desc">Vincula tu Instagram o Facebook oficial.</div>
              </div>
              <span class="step-action-arrow">↗</span>
            </div>
            <div class="onboarding-step-item <?= (!empty($userBrands) && count($userBrands) > 0) ? 'completed' : '' ?>" onclick="App.switchTab('settings')">
              <div class="step-num"><?= (!empty($userBrands) && count($userBrands) > 0) ? '✔' : '2' ?></div>
              <div class="step-content">
                <div class="step-title">2. Estilo de Respuesta</div>
                <div class="step-desc">Define el tono y la voz de tu marca.</div>
              </div>
              <span class="step-action-arrow">↗</span>
            </div>
            <div class="onboarding-step-item" onclick="AgentController.openAssistantModal()">
              <div class="step-num">3</div>
              <div class="step-content">
                <div class="step-title">3. Probar Copiloto</div>
                <div class="step-desc">Genera tu primera respuesta contextualizada.</div>
              </div>
              <span class="step-action-arrow">✨</span>
            </div>
          </div>
        </div>

        <!-- Sleek Collapsed Strip (38px) -->
        <div id="dashboard-onboarding-strip" class="dashboard-onboarding-strip" style="display: none;">
          <div class="onboarding-strip-content">
            <span class="onboarding-strip-badge">🚀 Onboarding:</span>
            <span class="strip-step <?= $isMetaConnected ? 'done' : '' ?>"><?= $isMetaConnected ? '✓ Redes conectadas' : '1. Conectar Redes' ?></span>
            <span class="strip-sep">·</span>
            <span class="strip-step <?= (!empty($userBrands) && count($userBrands) > 0) ? 'done' : '' ?>"><?= (!empty($userBrands) && count($userBrands) > 0) ? '✓ Voz configurada' : '2. Estilo de voz' ?></span>
            <span class="strip-sep">·</span>
            <button type="button" class="strip-btn-copilot" onclick="AgentController.openAssistantModal()">🪄 Probar Copiloto</button>
          </div>
          <div class="onboarding-strip-actions">
            <button type="button" class="btn-strip-toggle" onclick="App.toggleOnboardingGuide(true)" title="Ver guía completa de 3 pasos">▾ Expandir guía</button>
            <button type="button" class="btn-strip-close" onclick="App.dismissOnboarding()" title="Ocultar">&times;</button>
          </div>
        </div>

        <div class="feed-header">
          <!-- Live Meta Token Expired Warning Alert Banner -->
          <div id="meta-token-expired-banner" class="token-alert-banner" style="display: none; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 12px; padding: 12px 18px; margin-bottom: 12px; align-items: center; justify-content: space-between; gap: 14px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.15);">
            <div style="display: flex; align-items: center; gap: 12px;">
              <span style="font-size: 1.5rem;">⚠️</span>
              <div>
                <strong style="color: #f87171; font-size: 0.9rem; display: block; font-weight: 700;">Token de Meta Expirado o Desconectado (Error 190)</strong>
                <span class="token-alert-message" style="color: #cbd5e1; font-size: 0.8rem; line-height: 1.4;">Tus respuestas se guardan localmente pero no se envían a Facebook porque el token de sesión caducó. Actualiza el Token en Configuración para restablecer el envío automático.</span>
              </div>
            </div>
            <div style="display: flex; gap: 8px; flex-shrink: 0;">
              <button type="button" class="btn-renew-token" onclick="App.openModal('modal-settings')" style="padding: 7px 13px; border-radius: 8px; background: #ef4444; color: #fff; font-weight: 600; border: none; cursor: pointer; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);">
                <span>⚙️ Renovar Token</span>
              </button>
            </div>
          </div>

          <!-- FILA 1: Resumen de Gestión & KPIs Operativos + Selector de Cuenta -->
          <div class="feed-kpi-summary-row">
            <div class="feed-kpis-left">
              <span class="feed-kpi-chip kpi-pending" id="kpi-summary-pending" onclick="App.setFilterTag('pending_all')" title="Comentarios pendientes por gestionar">
                <span class="kpi-chip-dot"></span>
                <span><strong id="kpi-count-pending">0</strong> por gestionar</span>
              </span>
              <span class="feed-kpi-chip kpi-failed" id="kpi-summary-failed" onclick="App.setFilterTag('failed')" title="Comentarios con fallos de envío en Meta que requieren reintento" style="display: none;">
                <span class="kpi-chip-icon">⚠️</span>
                <span><strong id="kpi-count-failed">0</strong> requieren reintento</span>
              </span>
              <span class="feed-kpi-chip kpi-review" id="kpi-summary-review" onclick="App.setFilterTag('ai_review')" title="Comentarios retenidos para aprobación humana" style="display: none;">
                <span class="kpi-chip-icon">🤖</span>
                <span><strong id="kpi-count-review">0</strong> por aprobar</span>
              </span>
            </div>

            <!-- Account Filter Selector -->
            <div class="feed-account-filter-wrap" title="Filtrar comentarios por cuenta conectada">
              <div class="brand-select-pill account-select-pill">
                <span class="brand-pill-icon">📱</span>
                <select id="topbar-account-select" class="topbar-brand-select topbar-account-select" onchange="App.filterByAccount(this.value)">
                  <option value="all">🌐 Todas las Cuentas</option>
                </select>
              </div>
            </div>
          </div>

          <!-- FILA 2: Búsqueda Amplia con Filtro de Plataforma Integrado -->
          <div class="feed-search-integrated-row">
            <div class="search-box">
              <span class="search-icon">🔍</span>
              <input type="text" id="feed-search-input" placeholder="Buscar por usuario, pregunta de precio, producto o palabra clave..." />
            </div>

            <div class="platform-pill-group">
              <button class="platform-pill active" data-platform="all" title="Ver comentarios de todas las plataformas">🌐 Todos</button>
              <button class="platform-pill ig-active" data-platform="instagram" title="Filtrar comentarios de Instagram">📸 IG</button>
              <button class="platform-pill fb-active" data-platform="facebook" title="Filtrar comentarios de Facebook">📘 FB</button>
            </div>
          </div>

          <!-- FILA 3: Filtros de Estado Inteligentes & Menú 'Más filtros ▾' -->
          <div class="feed-filter-tags-row">
            <div class="filter-tags" id="primary-filter-tags">
              <button class="filter-tag active" data-filter="pending_all" id="btn-filter-pending-all">📥 Pendientes <span class="tag-badge" id="tag-count-pending-all">0</span></button>
              <button class="filter-tag" data-filter="pending_new" id="btn-filter-pending-new" style="color: #60a5fa; border-color: rgba(96, 165, 250, 0.4);">✨ Nuevos <span class="tag-badge" id="tag-count-pending-new">0</span></button>
              <button class="filter-tag filter-tag-failed" data-filter="failed" id="btn-filter-failed" style="color: #f87171; border-color: rgba(239, 68, 68, 0.4); display: none;">⚠️ Fallidos <span class="tag-badge" id="tag-count-failed">0</span></button>
              <button class="filter-tag" data-filter="ai_review" id="btn-filter-ai-review" style="color: #c084fc; border-color: rgba(192, 132, 252, 0.4); display: none;">🤖 Por Aprobar <span class="tag-badge" id="tag-count-ai-review">0</span></button>
              <button class="filter-tag" data-filter="leads_urgent" id="btn-filter-leads-urgent" style="color: #facc15; border-color: rgba(250, 204, 21, 0.4); display: none;">🎯 Leads & Urgentes <span class="tag-badge" id="tag-count-leads-urgent">0</span></button>
              <button class="filter-tag" data-filter="replied" id="btn-filter-replied" style="color: #34d399; border-color: rgba(52, 211, 153, 0.35);">✅ Respondidos <span class="tag-badge" id="tag-count-replied">0</span></button>
            </div>

            <!-- More Filters Menu Dropdown -->
            <div class="more-filters-dropdown-wrap" id="more-filters-wrap" style="position: relative;">
              <button type="button" class="btn-more-filters" id="btn-more-filters" onclick="App.toggleMoreFiltersMenu()" title="Ver más categorías de filtrado">
                <span>Más filtros <span id="more-filters-badge" class="more-filters-count" style="display: none;">0</span> ▾</span>
              </button>
              <div class="more-filters-menu" id="more-filters-menu" style="display: none;">
                <button type="button" class="more-filter-item" data-filter="archived" onclick="App.setFilterTag('archived'); App.toggleMoreFiltersMenu(false);">
                  <span>🗄️ Histórico / Archivados</span>
                  <span class="tag-badge" id="tag-count-archived">0</span>
                </button>
                <button type="button" class="more-filter-item" id="menu-item-leads" data-filter="leads_urgent" onclick="App.setFilterTag('leads_urgent'); App.toggleMoreFiltersMenu(false);" style="display: none;">
                  <span>🎯 Leads & Urgentes</span>
                  <span class="tag-badge" id="menu-count-leads-urgent">0</span>
                </button>
                <button type="button" class="more-filter-item" id="menu-item-ai-review" data-filter="ai_review" onclick="App.setFilterTag('ai_review'); App.toggleMoreFiltersMenu(false);" style="display: none;">
                  <span>🤖 Por Aprobar</span>
                  <span class="tag-badge" id="menu-count-ai-review">0</span>
                </button>
                <button type="button" class="more-filter-item" id="menu-item-failed" data-filter="failed" onclick="App.setFilterTag('failed'); App.toggleMoreFiltersMenu(false);" style="display: none;">
                  <span>⚠️ Fallidos</span>
                  <span class="tag-badge" id="menu-count-failed">0</span>
                </button>
              </div>
            </div>
          </div>

          <!-- FILA 4: Barra de Herramientas Operativa (Contador, Menú ⚙️ Acciones y Selector de Densidad) -->
          <div class="feed-toolbar-row">
            <div class="feed-toolbar-left" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span class="feed-counter-text" id="feed-counter-display">Cargando comentarios...</span>
              <button type="button" class="btn-toolbar-assistant" onclick="AgentController.openAssistantModal()" title="Abrir Copiloto IA de Respuestas">
                <span>🪄 Copiloto IA</span>
              </button>
              
              <!-- Menú Desplegable Secundario ⚙️ Acciones -->
              <div class="toolbar-actions-dropdown-wrap" id="toolbar-actions-wrap" style="position: relative;">
                <button type="button" class="btn-toolbar-secondary-menu" id="btn-toolbar-actions-toggle" onclick="App.toggleToolbarActionsMenu()" title="Acciones de mantenimiento y auditoría">
                  <span>⚙️ Acciones ▾</span>
                </button>
                <div class="toolbar-actions-menu" id="toolbar-actions-menu" style="display: none;">
                  <button type="button" class="toolbar-action-item" onclick="App.confirmAndRunWeeklyCleanup(); App.toggleToolbarActionsMenu(false);" title="Archivar comentarios resueltos y despejar la bandeja">
                    <span>🧹 Limpiar Bandeja</span>
                    <span class="badge-cleanup-count" id="badge-cleanup-count" style="display: none;">0</span>
                  </button>
                  <button type="button" class="toolbar-action-item" onclick="App.openWeeklyReportModal(); App.toggleToolbarActionsMenu(false);" title="Ver Reporte Semanal de Eficiencia y Auditoría">
                    <span>📊 Reporte Semanal</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Selector de Densidad: Operativa (Default 88px) | Compacta (56px) | Detallada -->
            <div class="density-toggle-group">
              <button type="button" class="btn-density-toggle active" id="btn-density-operational" onclick="App.toggleViewDensity('operational')" title="Vista Operativa (88px, texto legible, semáforo lateral)">
                <span>⚡ Operativa</span>
              </button>
              <button type="button" class="btn-density-toggle" id="btn-density-compact" onclick="App.toggleViewDensity('compact')" title="Vista Compacta (56px, 1 línea de texto)">
                <span>📏 Compacta</span>
              </button>
              <button type="button" class="btn-density-toggle" id="btn-density-cards" onclick="App.toggleViewDensity('cards')" title="Vista Detallada con multimedia">
                <span>🎴 Detallada</span>
              </button>
            </div>
          </div>
        </div>

        <div class="comments-scroll-area" id="comments-stream">
          <!-- Comments rendered dynamically -->
        </div>

        <div class="feed-pagination-bar" id="feed-pagination-bar" style="display: none;">
          <div style="font-size: 0.82rem; color: #94a3b8;" id="pagination-info-text">
            Página 1 de 1 (0 comentarios)
          </div>
          <div style="display: flex; align-items: center; gap: 8px;">
            <button type="button" class="btn-pagination" id="btn-pagination-prev" onclick="App.prevPage()" disabled>
              ◀ Anterior
            </button>
            <span id="pagination-current-display" style="font-size: 0.82rem; font-weight: 700; color: #818cf8; padding: 4px 10px; border-radius: 6px; background: rgba(99,102,241,0.12);">1</span>
            <button type="button" class="btn-pagination" id="btn-pagination-next" onclick="App.nextPage()" disabled>
              Siguiente ▶
            </button>
          </div>
        </div>
      </section>

    </div>

    <!-- View: AI Content Planner & Smart Auto-Scheduler -->
    <div id="view-planner" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 1400px; margin: 0 auto;">
        
        <!-- Planner Top Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 22px;">
          <div>
            <h3 style="font-size: 1.4rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
              <span>📅</span> Planificador de Contenido & Horarios Dorados
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted);">
              Crea contenido de alto impacto con IA y programa tus publicaciones automáticamente en las horas de mayor engagement.
            </p>
          </div>

          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div class="platform-pill-group">
              <button class="platform-pill active" data-planner-platform="all" onclick="PlannerController.filterPlatform('all')">🌐 Todas</button>
              <button class="platform-pill" data-planner-platform="instagram" onclick="PlannerController.filterPlatform('instagram')">📸 Instagram</button>
              <button class="platform-pill" data-planner-platform="facebook" onclick="PlannerController.filterPlatform('facebook')">📘 Facebook</button>
            </div>

            <button class="btn-primary-action" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); font-weight: 700; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);" onclick="PlannerController.openCreatorModal()">
              <span>✨ Redactar con IA Copilot</span>
            </button>
          </div>
        </div>

        <!-- Section: Available Golden Slots Banner -->
        <div class="planner-golden-banner" style="margin-bottom: 24px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 1.1rem;">🏆</span>
              <span style="font-size: 0.9rem; font-weight: 800; color: #fff;">Próximas Ventanas Doradas Recomendadas por el Algoritmo</span>
            </div>
            <span style="font-size: 0.76rem; color: #a5b4fc; background: rgba(99, 102, 241, 0.15); padding: 3px 10px; border-radius: 12px; border: 1px solid rgba(99, 102, 241, 0.3);">
              Calculado con tus métricas reales de retención
            </span>
          </div>
          <div id="planner-golden-slots-list" class="golden-slots-horizontal-list">
            <!-- Rendered by PlannerController.renderGoldenSlotsBanner() -->
          </div>
        </div>

        <!-- Section: Calendar Navigation & Month Matrix -->
        <div class="analytics-section-card" style="padding: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <h4 id="calendar-month-title" style="font-size: 1.15rem; font-weight: 800; color: #fff;">Septiembre 2026</h4>
              <div class="calendar-nav-buttons">
                <button class="cal-nav-btn" onclick="PlannerController.prevMonth()" title="Mes anterior">◀</button>
                <button class="cal-nav-btn today" onclick="PlannerController.goToCurrentMonth()">Hoy</button>
                <button class="cal-nav-btn" onclick="PlannerController.nextMonth()" title="Mes siguiente">▶</button>
              </div>
            </div>

            <!-- Status KPI chips -->
            <div class="calendar-status-summary">
              <span class="cal-badge-kpi scheduled" id="kpi-count-scheduled">📌 0 Programadas</span>
              <span class="cal-badge-kpi published" id="kpi-count-published">✅ 0 Publicadas</span>
              <span class="cal-badge-kpi drafts" id="kpi-count-drafts">📝 0 Borradores</span>
            </div>
          </div>

          <!-- Calendar Days Header -->
          <div class="calendar-grid-header">
            <div>Lun</div>
            <div>Mar</div>
            <div>Mié</div>
            <div>Jue</div>
            <div>Vie</div>
            <div>Sáb</div>
            <div>Dom</div>
          </div>

          <!-- Calendar Grid Cells -->
          <div id="calendar-grid-cells" class="calendar-grid-body">
            <!-- Rendered by PlannerController.renderCalendar() -->
          </div>
        </div>

      </div>
    </div>

    <!-- View: Radar de Creadores & Re-creación Estoica -->
    <div id="view-radar" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 1400px; margin: 0 auto;">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; margin-bottom: 22px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 1.5rem;">🧭</span>
              <h3 style="font-size: 1.4rem; font-weight: 800; color: #fff; margin: 0;">
                Radar de Creadores & Re-creación Estoica
              </h3>
              <span class="quote-badge-pill modern" style="font-size: 0.72rem;">@fortaleza_imparable</span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 6px 0 0 0; max-width: 780px;">
              Monitorea las mejores publicaciones de tus competidores y referentes de nicho (Estoicismo, Bushido, Samurái), audita si sus citas son históricamente auténticas o mitos de redes, y genera copys y prompts visuales originales para tu marca.
            </p>
          </div>

          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); font-weight: 700; box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);" onclick="RadarController.syncAll(this)">
              <span>🔄 Sincronizar Todo (Meta API)</span>
            </button>
            <button type="button" class="btn-secondary-action" style="border: 1px solid rgba(255,255,255,0.15); padding: 8px 14px; font-size: 0.82rem;" onclick="RadarController.openAddCreatorModal()">
              <span>➕ Monitorear Creador</span>
            </button>
          </div>
        </div>

        <!-- Section 1: Creadores en Monitoreo -->
        <div style="margin-bottom: 18px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div style="font-size: 0.92rem; font-weight: 800; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
              <span>🎯</span> Cuentas de Referencia en Monitoreo
            </div>
            <span style="font-size: 0.76rem; color: var(--text-dim);">📸 Instagram (Auto-Sync & Métricas) • Gestión con Eliminación Directa</span>
          </div>
          <div id="radar-creators-carousel" class="creators-carousel">
            <!-- Rendered dynamically by RadarController.renderCreators() -->
          </div>
        </div>

        <!-- Section 2: Barra de Importación Rápida -->
        <div class="radar-quick-import-bar">
          <form class="radar-import-form" onsubmit="RadarController.submitDirectImport(event)">
            <div class="radar-import-input-wrap">
              <span class="radar-import-icon">📥</span>
              <input type="text" id="radar-import-input" class="radar-import-input" placeholder="Pegar enlace de post (IG o FB), frase de autor o texto a auditar y re-crear..." />
            </div>

            <select id="radar-import-creator" style="background: rgba(10,13,20,0.8); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; padding: 11px 14px; color: #e2e8f0; font-size: 0.84rem; outline: none;">
              <option value="">(Opcional) Asociar a Creador</option>
            </select>

            <button type="submit" id="btn-radar-import-submit" class="btn-primary-action" style="background: linear-gradient(135deg, #10b981, #059669); font-weight: 700; white-space: nowrap; padding: 11px 18px; border-radius: 10px;">
              <span>⚡ Auditar & Desmontar</span>
            </button>
          </form>
        </div>

        <!-- Section 3: Toolbar de Filtrado & Feed -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 18px;">
          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div class="search-box" style="width: 240px;">
              <span class="search-icon">🔍</span>
              <input type="text" id="radar-search-input" placeholder="Buscar por frase, palabra..." style="padding-left: 34px;" />
            </div>

            <select id="radar-creator-filter" style="background: rgba(23,31,48,0.7); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 7px 12px; color: #e2e8f0; font-size: 0.82rem; outline: none;">
              <option value="all">🌐 Todos los Creadores</option>
            </select>

            <select id="radar-status-filter" style="background: rgba(23,31,48,0.7); border: 1px solid rgba(255,255,255,0.12); border-radius: 8px; padding: 7px 12px; color: #e2e8f0; font-size: 0.82rem; outline: none;">
              <option value="all">⚖️ Todo Tipo de Citas</option>
              <option value="verified_authentic">🏛️ Solo Auténticas</option>
              <option value="apocryphal">⚠️ Solo Apócrifas / Mitos</option>
              <option value="modern_idea">💡 Solo Ideas Modernas</option>
              <option value="pending">⏳ Pendientes de Auditoría</option>
            </select>
          </div>

          <div style="display: flex; align-items: center; gap: 10px;">
            <span id="radar-posts-count-badge" style="font-size: 0.78rem; color: var(--text-dim);">0 posts</span>
            <select id="radar-sort-select" class="sort-select">
              <option value="engagement">🔥 Mayor Engagement</option>
              <option value="opportunity">🎯 Mayor Oportunidad Viral</option>
              <option value="creative_fit">🏛️ Mayor Afinidad Fortaleza</option>
              <option value="likes">❤️ Más Likes</option>
              <option value="recent">📅 Más Recientes</option>
            </select>
          </div>
        </div>

        <!-- Grid de Publicaciones de Inspiración -->
        <div id="radar-posts-grid" class="radar-posts-grid">
          <!-- Rendered dynamically by RadarController.renderPosts() -->
        </div>

      </div>
    </div>

    <!-- View: Atenea Continuous Learning Engine (@fortaleza_imparable) -->
    <div id="view-atenea-learning" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 1400px; margin: 0 auto;" id="atenea-learning-container">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 14px; margin-bottom: 22px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 1.6rem;">🏛️</span>
              <h3 style="font-size: 1.45rem; font-weight: 800; color: #fff; margin: 0;">
                Atenea Intelligence — Motor de Aprendizaje Continuo
              </h3>
              <span class="quote-badge-pill modern" style="background: rgba(245, 158, 11, 0.2); color: #fbbf24; border-color: rgba(245, 158, 11, 0.4); font-size: 0.72rem;">@fortaleza_imparable</span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin: 6px 0 0 0; max-width: 820px;">
              Atenea aprende directamente del comportamiento histórico de tu audiencia en Facebook e Instagram. Analiza correlaciones de ADN de contenido, percentiles y extrae fórmulas ganadoras comprobadas y anti-patrones que deben evitarse.
            </p>
          </div>

          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #f59e0b, #d97706); font-weight: 700; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);" onclick="AteneaLearningController.rebuildAll(this)">
              <span>⚡ Reconstruir & Minar Patrones Ahora</span>
            </button>
          </div>
        </div>

        <!-- Spinner loader -->
        <div id="atenea-learning-spinner" style="display: none; text-align: center; padding: 30px;">
          <div class="comment-loading" style="margin: 0 auto 12px;"></div>
          <div style="color: var(--text-muted); font-size: 0.85rem;">Analizando correlaciones de audiencia y percentiles...</div>
        </div>

        <!-- Subtabs Navigation -->
        <div class="atenea-subtabs-nav" style="display: flex; gap: 8px; margin-bottom: 22px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; flex-wrap: wrap;">
          <button type="button" class="btn-subtab active" id="tab-btn-overview" onclick="AteneaLearningController.switchSubTab('overview')">
            <span>📊 Resumen & Percentiles</span>
          </button>
          <button type="button" class="btn-subtab" id="tab-btn-predictions" onclick="AteneaLearningController.switchSubTab('predictions')">
            <span>🔬 Predicción vs Resultado</span>
            <span class="badge-subtab" id="badge-predictions-count" style="background: rgba(139,92,246,0.2); color: #c4b5fd; font-size: 0.72rem; padding: 2px 7px; border-radius: 10px; margin-left: 6px;">0</span>
          </button>
          <button type="button" class="btn-subtab" id="tab-btn-comparisons" onclick="AteneaLearningController.switchSubTab('comparisons')">
            <span>🔬 Experimentos Naturales (A/B Twins)</span>
            <span class="badge-subtab" id="badge-comparisons-count" style="background: rgba(16,185,129,0.2); color: #34d399; font-size: 0.72rem; padding: 2px 7px; border-radius: 10px; margin-left: 6px;">0</span>
          </button>
          <button type="button" class="btn-subtab" id="tab-btn-hypotheses" onclick="AteneaLearningController.switchSubTab('hypotheses')">
            <span>🧬 Matriz de Hipótesis & Lifecycle</span>
          </button>
          <button type="button" class="btn-subtab" id="tab-btn-directives" onclick="AteneaLearningController.switchSubTab('directives')">
            <span>🧠 Directivas en Vivo</span>
          </button>
          <button type="button" class="btn-subtab" id="tab-btn-double-brain" onclick="AteneaLearningController.switchSubTab('double-brain')">
            <span>⚖️ Doble Cerebro (Nicho vs Propio)</span>
          </button>
        </div>

        <div id="atenea-learning-content">
          <!-- 1. SUBVIEW: RESUMEN & PERCENTILES -->
          <div id="subtab-view-overview">
            <!-- KPI Cards Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 22px;">
              <div class="atenea-kpi-card">
                <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">Publicaciones Indexadas</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #fff; margin: 4px 0;" id="atenea-kpi-total-indexed">215</div>
                <div style="font-size: 0.76rem; color: #a5b4fc; display: flex; gap: 8px;">
                  <span id="atenea-kpi-fb-count">115 Facebook</span> • <span id="atenea-kpi-ig-count">100 Instagram</span>
                </div>
              </div>

              <div class="atenea-kpi-card">
                <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">Experimentos A/B Gemelos</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #34d399; margin: 4px 0;" id="atenea-kpi-comparisons-count">0</div>
                <div style="font-size: 0.76rem; color: #6ee7b7;">Mismo concepto, diferente framing</div>
              </div>

              <div class="atenea-kpi-card">
                <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">Hipótesis en Lifecycle</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #c084fc; margin: 4px 0;" id="atenea-kpi-hypotheses-count">0</div>
                <div style="font-size: 0.76rem; color: #e9d5ff;">Evaluadas con Wilson 95%</div>
              </div>

              <div class="atenea-kpi-card">
                <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">Anti-Patrones Detectados</div>
                <div style="font-size: 1.8rem; font-weight: 900; color: #f87171; margin: 4px 0;" id="atenea-kpi-anti-count">0</div>
                <div style="font-size: 0.76rem; color: #fca5a5;">Franja baja (Bottom 20% - Qué evitar)</div>
              </div>
            </div>

            <!-- Continuous Percentiles Matrix -->
            <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: var(--radius-md); padding: 18px 22px; margin-bottom: 24px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-size: 0.85rem; font-weight: 800; color: #fff;">Distribución Continua de Rendimiento (Ranking Percentilar)</span>
                <span style="font-size: 0.75rem; color: var(--text-dim);">Ponderado por Shares (Facebook) y Guardados/Alcance (Instagram)</span>
              </div>

              <div class="atenea-tier-progress-track">
                <div id="tier-bar-p90" class="atenea-tier-seg top10" style="width: 10%;" title="P90 (Top 10%)"></div>
                <div id="tier-bar-p75" class="atenea-tier-seg top20" style="width: 15%;" title="P75 (Top 25%)"></div>
                <div id="tier-bar-p50" class="atenea-tier-seg avg" style="width: 25%;" title="P50 (Medio)"></div>
                <div id="tier-bar-p25" class="atenea-tier-seg low20" style="width: 25%;" style="background: #f59e0b;" title="P25 (Bajo)"></div>
                <div id="tier-bar-p10" class="atenea-tier-seg low20" style="width: 25%;" title="P10 (Bottom 20%)"></div>
              </div>

              <div style="display: flex; justify-content: space-between; font-size: 0.74rem; color: #cbd5e1; flex-wrap: wrap; gap: 8px;">
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #10b981;"></span>
                  <span id="tier-label-p90">P90 - Top 10% (0)</span>
                </span>
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #06b6d4;"></span>
                  <span id="tier-label-p75">P75 - Alto (0)</span>
                </span>
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #6366f1;"></span>
                  <span id="tier-label-p50">P50 - Medio (0)</span>
                </span>
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #f59e0b;"></span>
                  <span id="tier-label-p25">P25 - Bajo (0)</span>
                </span>
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span style="display: inline-block; width: 10px; height: 10px; border-radius: 2px; background: #ef4444;"></span>
                  <span id="tier-label-p10">P10 - Bottom 20% (0)</span>
                </span>
              </div>
            </div>

            <!-- Top Performing Posts vs Low Performing Posts -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
              <div>
                <div style="font-size: 0.92rem; font-weight: 800; color: #34d399; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                  <span>🏆</span> Publicaciones Históricas de Mayor Impacto (Top 5)
                </div>
                <div id="atenea-top-posts-grid" style="display: flex; flex-direction: column; gap: 10px;">
                  <!-- Injected dynamically -->
                </div>
              </div>

              <div>
                <div style="font-size: 0.92rem; font-weight: 800; color: #f87171; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                  <span>📉</span> Publicaciones Históricas de Menor Impacto (Bottom 5 - Auditoría)
                </div>
                <div id="atenea-low-posts-grid" style="display: flex; flex-direction: column; gap: 10px;">
                  <!-- Injected dynamically -->
                </div>
              </div>
            </div>
          </div>

          <!-- 1.B SUBVIEW: PREDICCIÓN VS RESULTADO REAL (FASE 5) -->
          <div id="subtab-view-predictions" style="display: none;">
            <!-- Epistemological Banner -->
            <div style="background: rgba(139, 92, 246, 0.08); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 12px; padding: 18px; margin-bottom: 20px;">
              <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div>
                  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                    <span style="font-size: 1.3rem;">🔬</span>
                    <h4 style="font-size: 1.05rem; font-weight: 800; color: #c4b5fd; margin: 0;">Ciclo de Predicción vs Resultado Real & Learning Ledger</h4>
                  </div>
                  <p style="font-size: 0.83rem; color: #cbd5e1; margin: 0; line-height: 1.5; max-width: 900px;">
                    Atenea aprende qué hipótesis sobreviven al contraste con nuevos resultados. Las predicciones son <strong>promesas falsables registradas antes de publicar</strong>. Las observaciones individuales no demuestran causalidad ni se auto-promocionan sin acumulación estadística.
                  </p>
                </div>
                <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); font-weight: 700; font-size: 0.82rem; padding: 8px 16px;" onclick="AteneaLearningController.openNewPredictionModal()">
                  <span>➕ Formular Predicción Falsable</span>
                </button>
              </div>
            </div>

            <!-- Prediction Accuracy & Stats Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px;">
              <div class="atenea-kpi-card" style="border-left: 3px solid #8b5cf6;">
                <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">Predicciones Registradas</div>
                <div style="font-size: 1.7rem; font-weight: 900; color: #fff; margin: 4px 0;" id="pred-kpi-total">0</div>
                <div style="font-size: 0.74rem; color: #a5b4fc;" id="pred-kpi-evaluated-label">0 evaluadas con resultado</div>
              </div>

              <div class="atenea-kpi-card" style="border-left: 3px solid #10b981;">
                <div style="font-size: 0.72rem; font-weight: 700; color: #34d399; text-transform: uppercase;">Confirmadas</div>
                <div style="font-size: 1.7rem; font-weight: 900; color: #34d399; margin: 4px 0;" id="pred-kpi-confirmed">0</div>
                <div style="font-size: 0.74rem; color: #6ee7b7;" id="pred-kpi-confirmed-rate">Tasa: 0.0% (n=0)</div>
              </div>

              <div class="atenea-kpi-card" style="border-left: 3px solid #ef4444;">
                <div style="font-size: 0.72rem; font-weight: 700; color: #f87171; text-transform: uppercase;">Contradichas (Errores)</div>
                <div style="font-size: 1.7rem; font-weight: 900; color: #f87171; margin: 4px 0;" id="pred-kpi-contradicted">0</div>
                <div style="font-size: 0.74rem; color: #fca5a5;" id="pred-kpi-contradicted-rate">Tasa: 0.0% (n=0)</div>
              </div>

              <div class="atenea-kpi-card" style="border-left: 3px solid #f59e0b;">
                <div style="font-size: 0.72rem; font-weight: 700; color: #fbbf24; text-transform: uppercase;">Inconclusas</div>
                <div style="font-size: 1.7rem; font-weight: 900; color: #fbbf24; margin: 4px 0;" id="pred-kpi-inconclusive">0</div>
                <div style="font-size: 0.74rem; color: #fde68a;" id="pred-kpi-inconclusive-rate">Datos insuficientes</div>
              </div>

              <div class="atenea-kpi-card" style="border-left: 3px solid #38bdf8;">
                <div style="font-size: 0.72rem; font-weight: 700; color: #38bdf8; text-transform: uppercase;">Modo Operativo</div>
                <div style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 4px 0;" id="pred-kpi-mode">EXPLORATION</div>
                <div style="font-size: 0.74rem; color: #7dd3fc;" id="pred-kpi-knowledge-state">NEGATIVE_ONLY (0 fórmulas)</div>
              </div>
            </div>

            <!-- Predictions Table & Failed Predictions Grid -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
              <!-- 1. Active & Evaluated Predictions Table -->
              <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                  <div style="font-size: 0.95rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <span>📋</span> Experimentos & Predicciones Registradas
                  </div>
                  <div style="display: flex; gap: 6px;">
                    <button type="button" class="btn-secondary-action" style="font-size: 0.74rem; padding: 4px 10px;" onclick="AteneaLearningController.filterPredictions('all')">Todas</button>
                    <button type="button" class="btn-secondary-action" style="font-size: 0.74rem; padding: 4px 10px; color: #34d399;" onclick="AteneaLearningController.filterPredictions('CONFIRMED')">Confirmadas</button>
                    <button type="button" class="btn-secondary-action" style="font-size: 0.74rem; padding: 4px 10px; color: #f87171;" onclick="AteneaLearningController.filterPredictions('CONTRADICTED')">Contradichas</button>
                    <button type="button" class="btn-secondary-action" style="font-size: 0.74rem; padding: 4px 10px; color: #fbbf24;" onclick="AteneaLearningController.filterPredictions('INCONCLUSIVE')">Inconclusas</button>
                  </div>
                </div>

                <div id="atenea-predictions-table-container">
                  <!-- Injected via JavaScript -->
                  <div style="color: var(--text-dim); text-align: center; padding: 24px;">Cargando predicciones...</div>
                </div>
              </div>

              <!-- 2. Failed Predictions (Transparencia Epistemológica) -->
              <div style="background: rgba(239, 68, 68, 0.04); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 12px; padding: 20px;">
                <div style="font-size: 0.95rem; font-weight: 800; color: #f87171; display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                  <span>⚠️</span> Predicciones Contradichas (Failed Predictions — Honestidad Científica)
                </div>
                <p style="font-size: 0.8rem; color: #cbd5e1; margin: 0 0 14px 0;">
                  Estas son las publicaciones donde la hipótesis esperaba alto rendimiento pero el resultado real quedó en bajo rendimiento o promedio. El fallo evita la sobreconfianza y genera aprendizaje inmutable en el ledger.
                </p>
                <div id="atenea-failed-predictions-container">
                  <!-- Injected via JavaScript -->
                </div>
              </div>

              <!-- 3. Learning Ledger (Libro Mayor Inmutable) -->
              <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                  <div style="font-size: 0.95rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
                    <span>📜</span> Learning Ledger (Libro Mayor Inmutable de Evolución del Conocimiento)
                  </div>
                  <span style="font-size: 0.74rem; color: var(--text-dim);">Registros históricos inalterables</span>
                </div>
                <div id="atenea-learning-ledger-container">
                  <!-- Injected via JavaScript -->
                  <div style="color: var(--text-dim); text-align: center; padding: 24px;">Cargando libro mayor...</div>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. SUBVIEW: EXPERIMENTOS NATURALES (A/B TWINS) -->
          <div id="subtab-view-comparisons" style="display: none;">
            <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 12px; padding: 18px; margin-bottom: 20px;">
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 1.3rem;">🔬</span>
                <h4 style="font-size: 1.05rem; font-weight: 800; color: #34d399; margin: 0;">Laboratorio de Experimentos Naturales (Pares Gemelos)</h4>
              </div>
              <p style="font-size: 0.84rem; color: #cbd5e1; margin: 0; line-height: 1.5;">
                Atenea detecta automáticamente publicaciones reales que comparten el <strong>mismo concepto nuclear o frase de caption</strong> pero difieren en su <strong>framing visual (placa)</strong>. Esto permite comparar las diferencias observadas entre variaciones de framing en condiciones reales de audiencia (diseño observacional).
              </p>
            </div>

            <div id="atenea-natural-comparisons-grid" style="display: flex; flex-direction: column; gap: 16px;">
              <!-- Rendered dynamically by AteneaLearningController -->
            </div>
          </div>

          <!-- 3. SUBVIEW: MATRIZ DE HIPÓTESIS & LIFECYCLE -->
          <div id="subtab-view-hypotheses" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
              <div>
                <h4 style="font-size: 1.1rem; font-weight: 800; color: #fff; margin: 0;">Matriz de Hipótesis y Ciclo de Vida Estadístico</h4>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin: 4px 0 0 0;">
                  Las hipótesis transitan por estados según su tamaño de muestra ($n$), Intervalo de Wilson al 95% y recencia temporal.
                </p>
              </div>
              <div style="display: flex; gap: 8px; font-size: 0.74rem;">
                <span style="padding: 4px 10px; border-radius: 6px; background: rgba(16,185,129,0.15); color: #34d399; font-weight: 700;">🟢 STRONG</span>
                <span style="padding: 4px 10px; border-radius: 6px; background: rgba(56,189,248,0.15); color: #38bdf8; font-weight: 700;">🔵 VALIDATED</span>
                <span style="padding: 4px 10px; border-radius: 6px; background: rgba(168,85,247,0.15); color: #c084fc; font-weight: 700;">🟣 OBSERVATION</span>
                <span style="padding: 4px 10px; border-radius: 6px; background: rgba(245,158,11,0.15); color: #fbbf24; font-weight: 700;">🟡 EMERGING</span>
                <span style="padding: 4px 10px; border-radius: 6px; background: rgba(239,68,68,0.15); color: #f87171; font-weight: 700;">🔴 WEAKENING</span>
              </div>
            </div>

            <div id="atenea-hypotheses-matrix-container" style="display: flex; flex-direction: column; gap: 14px;">
              <!-- Rendered dynamically by AteneaLearningController -->
            </div>
          </div>

          <!-- 4. SUBVIEW: DIRECTIVAS EN VIVO -->
          <div id="subtab-view-directives" style="display: none;">
            <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(139, 92, 246, 0.25); border-radius: var(--radius-md); padding: 20px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                  <span style="font-size: 1.2rem;">🧠</span>
                  <h4 style="font-size: 0.95rem; font-weight: 800; color: #c084fc; margin: 0;">Directivas Empíricas Inyectadas en el Prompt de Atenea</h4>
                </div>
                <span style="font-size: 0.72rem; color: #a5b4fc; background: rgba(139, 92, 246, 0.15); padding: 3px 8px; border-radius: 4px;">Inyección Automática en Producción</span>
              </div>
              <pre id="atenea-live-directives-code" class="atenea-directives-terminal">Cargando directivas empíricas...</pre>
            </div>
          </div>

          <!-- 5. SUBVIEW: DOBLE CEREBRO (RADAR EXTERNO VS RADAR INTERNO) -->
          <div id="subtab-view-double-brain" style="display: none;">
            <div style="background: rgba(10, 15, 26, 0.7); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 14px; padding: 22px;">
              <div style="text-align: center; max-width: 780px; margin: 0 auto 24px auto;">
                <h4 style="font-size: 1.25rem; font-weight: 800; color: #fff; margin-bottom: 8px;">
                  🏛️ Arquitectura de Dos Cerebros de Referencia
                </h4>
                <p style="font-size: 0.86rem; color: var(--text-muted); line-height: 1.5;">
                  Atenea combina la inteligencia del nicho exterior con el aprendizaje empírico interno de tu propia audiencia para alimentar Atenea Studio.
                </p>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
                <!-- Cerebro 1 -->
                <div style="background: rgba(139, 92, 246, 0.06); border: 1px solid rgba(139, 92, 246, 0.25); border-radius: 12px; padding: 18px;">
                  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <span style="font-size: 1.3rem;">📡</span>
                    <h5 style="font-size: 0.95rem; font-weight: 800; color: #c084fc; margin: 0;">RADAR EXTERNO (Mercado y Nicho)</h5>
                  </div>
                  <ul style="font-size: 0.82rem; color: #cbd5e1; list-style: none; padding: 0; margin: 0; line-height: 1.8;">
                    <li>• Creadores y referentes monitoreados en Instagram/Facebook.</li>
                    <li>• Qué temas y ganchos están en tendencia actual en el sector estoico.</li>
                    <li>• Inspiración y detección de ángulos vírgenes.</li>
                  </ul>
                  <button type="button" class="btn-secondary-action" style="margin-top: 14px; width: 100%; justify-content: center; font-size: 0.8rem;" onclick="App.switchTab('radar')">
                    Ir al Radar de Creadores
                  </button>
                </div>

                <!-- Cerebro 2 -->
                <div style="background: rgba(16, 185, 129, 0.06); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 12px; padding: 18px;">
                  <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                    <span style="font-size: 1.3rem;">🎯</span>
                    <h5 style="font-size: 0.95rem; font-weight: 800; color: #34d399; margin: 0;">RADAR INTERNO (@fortaleza_imparable)</h5>
                  </div>
                  <ul style="font-size: 0.82rem; color: #cbd5e1; list-style: none; padding: 0; margin: 0; line-height: 1.8;">
                    <li>• Corpus histórico de 215 publicaciones analizadas.</li>
                    <li>• Micro-DNA y qué estructuras generan shares masivos reales.</li>
                    <li>• Anti-patrones que tu audiencia rechaza empíricamente.</li>
                  </ul>
                  <button type="button" class="btn-secondary-action" style="margin-top: 14px; width: 100%; justify-content: center; font-size: 0.8rem;" onclick="AteneaLearningController.switchSubTab('hypotheses')">
                    Ver Hipótesis Validadas
                  </button>
                </div>
              </div>

              <!-- Síntesis en Atenea Studio -->
              <div style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.12), rgba(168, 85, 247, 0.12)); border: 1px solid rgba(168, 85, 247, 0.35); border-radius: 12px; padding: 20px; text-align: center;">
                <div style="font-size: 1.2rem; margin-bottom: 6px;">✨</div>
                <h5 style="font-size: 1.05rem; font-weight: 800; color: #fff; margin-bottom: 6px;">
                  Atenea Studio: 70% Explotación + 30% Exploración
                </h5>
                <p style="font-size: 0.84rem; color: #e2e8f0; max-width: 680px; margin: 0 auto 16px auto; line-height: 1.5;">
                  Generación de contenido original guiada por las hipótesis validadas de tu audiencia, con un 30% de espacio controlado para experimentar con nuevos framings visuales y ángulos.
                </p>
                <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); font-weight: 700; padding: 9px 22px; font-size: 0.86rem;" onclick="RadarController.openRecreateModal(null, false, 'custom')">
                  🏛️ Abrir Atenea Studio
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
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
          <button class="subtab-btn" data-subtab="timing" onclick="AnalyticsController.switchSubtab('timing')">
            <span>⏰ Horarios & Mejores Posts</span>
          </button>
          <button class="subtab-btn" data-subtab="posts" onclick="AnalyticsController.switchSubtab('posts')">
            <span>📱 Rendimiento por Publicación</span>
          </button>
          <button class="subtab-btn" data-subtab="trends" onclick="AnalyticsController.switchSubtab('trends')">
            <span>🔥 Tendencias del Nicho</span>
          </button>
        </div>
      </div>

      <!-- Subview 1: General Overview -->
      <div id="analytics-overview-subview">
        <div id="analytics-view-content">
          <!-- Overview metrics rendered dynamically -->
        </div>
      </div>

      <!-- Subview 2: Smart Timing, Heatmap & Hall of Fame -->
      <div id="analytics-timing-subview" style="display: none;">
        <div id="analytics-timing-content">
          <!-- Rendered dynamically by AnalyticsController.renderTimingSubtab() -->
        </div>
      </div>

      <!-- Subview 3: Post-by-Post Insights -->
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

      <!-- ═══════════════════════════════════════════════════════════════════
           Subview 4: AGENTE DE TENDENCIAS DEL NICHO
           ═══════════════════════════════════════════════════════════════════ -->
      <div id="analytics-trends-subview" style="display: none;">

        <!-- ─── Header + Controls ─────────────────────────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:14px; margin-bottom:24px;">
          <div>
            <h4 style="font-size:1.1rem; font-weight:800; color:#fff; margin:0 0 4px;">🔥 Tendencias del Nicho</h4>
            <p style="font-size:0.82rem; color:var(--text-muted); margin:0;">Monitorea hashtags de tu nicho y descubre el contenido con mayor engagement. Actualización cada 6h.</p>
          </div>
          <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <span id="trends-last-sync" style="font-size:0.76rem; color:var(--text-dim);">Última sync: --</span>
            <button id="btn-trends-sync" class="btn-primary-action" style="background: linear-gradient(135deg,#7c3aed,#4f46e5); padding:8px 16px; font-size:0.8rem; display:flex; align-items:center; gap:6px;" onclick="TrendsAgent.syncNow()">
              <svg id="trends-sync-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Sincronizar Ahora</span>
            </button>
          </div>
        </div>

        <!-- ─── Hashtag Manager ────────────────────────────────────────────── -->
        <div class="analytics-section-card" style="margin-bottom:20px; padding:18px 20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div style="font-size:0.88rem; font-weight:700; color:#e2e8f0;">
              📌 Hashtags Monitoreados <span id="trends-niche-count" style="font-size:0.75rem; color:var(--text-dim); font-weight:400;"></span>
            </div>
            <div style="display:flex; gap:8px;">
              <input id="trends-new-hashtag" type="text" placeholder="#estoicismo" maxlength="60"
                style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:6px 12px; color:#e2e8f0; font-size:0.82rem; width:160px; outline:none;"
                onkeydown="if(event.key==='Enter') TrendsAgent.addNiche()"/>
              <button class="btn-primary-action" style="padding:6px 14px; font-size:0.8rem;" onclick="TrendsAgent.addNiche()">
                + Agregar
              </button>
            </div>
          </div>
          <div id="trends-niche-chips" style="display:flex; flex-wrap:wrap; gap:8px; min-height:32px;">
            <span style="color:var(--text-dim); font-size:0.8rem;">Cargando hashtags...</span>
          </div>
        </div>

        <!-- ─── AI Top 3 Picks ─────────────────────────────────────────────── -->
        <div id="trends-ai-picks-section" style="margin-bottom:24px; display:none;">
          <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
            <div style="background:linear-gradient(135deg,#f59e0b,#ef4444); border-radius:8px; padding:5px 12px; font-size:0.75rem; font-weight:800; color:#fff; letter-spacing:0.04em;">🤖 AI TOP 3 PICKS</div>
            <span style="font-size:0.8rem; color:var(--text-dim);">Los mejores posts del nicho seleccionados por el agente</span>
          </div>
          <div id="trends-ai-picks-grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px;"></div>
        </div>

        <!-- ─── Insights Bar ──────────────────────────────────────────────── -->
        <div id="trends-insights-bar" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; margin-bottom:20px;"></div>

        <!-- ─── Top 20 Trending Grid ─────────────────────────────────────── -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:10px;">
          <div style="font-size:0.9rem; font-weight:700; color:#e2e8f0;">📊 Top 20 Posts en Tendencia</div>
          <div style="display:flex; gap:8px; align-items:center;">
            <select id="trends-filter-niche" style="background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.12); border-radius:8px; padding:5px 10px; color:#e2e8f0; font-size:0.79rem; outline:none;" onchange="TrendsAgent.filterByNiche(this.value)">
              <option value="">🌐 Todos los nichos</option>
            </select>
          </div>
        </div>
        <div id="trends-posts-grid" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(310px,1fr)); gap:16px; margin-bottom:30px;">
          <div style="grid-column:1/-1; text-align:center; padding:40px; color:var(--text-dim);">
            <div style="font-size:2rem; margin-bottom:10px;">🔥</div>
            <div style="font-weight:600;">Agrega hashtags para empezar a monitorear tendencias</div>
            <div style="font-size:0.8rem; margin-top:6px;">Tus nichos de referencia aparecerán aquí con su engagement score en tiempo real</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ═══ MODAL: Inspirarme 🤖 ════════════════════════════════════════════ -->
    <div id="modal-inspire" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.7); backdrop-filter:blur(4px); align-items:center; justify-content:center;" onclick="if(event.target===this)TrendsAgent.closeInspireModal()">
      <div style="background:#1a1c2e; border:1px solid rgba(124,58,237,0.3); border-radius:20px; padding:28px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
          <div>
            <div style="font-size:1rem; font-weight:800; color:#fff; margin-bottom:4px;">✨ Inspirarme con IA</div>
            <div id="inspire-post-ref" style="font-size:0.78rem; color:var(--text-dim);"></div>
          </div>
          <button onclick="TrendsAgent.closeInspireModal()" style="background:none; border:none; color:var(--text-dim); cursor:pointer; font-size:1.4rem; line-height:1; padding:0;">×</button>
        </div>

        <div id="inspire-loading" style="text-align:center; padding:30px;">
          <div class="comment-loading" style="margin:0 auto 12px;"></div>
          <div style="color:var(--text-muted); font-size:0.85rem;">Generando captions con tu voz de marca...</div>
        </div>

        <div id="inspire-results" style="display:none;">
          <div style="font-size:0.82rem; font-weight:700; color:#a78bfa; margin-bottom:12px;">📝 3 Variantes de Caption Generadas:</div>
          <div id="inspire-captions-list" style="display:flex; flex-direction:column; gap:12px;"></div>
          <div id="inspire-source-badge" style="margin-top:14px; font-size:0.72rem; color:var(--text-dim); text-align:right;"></div>
        </div>
      </div>
    </div>

    <!-- View 3: AI Brand Voice Studio & Identity Calibrator -->
    <div id="view-settings" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 1280px; margin: 0 auto;">
        
        <div style="margin-bottom: 24px;">
          <h3 style="font-size: 1.45rem; font-weight: 800; color: #fff; margin-bottom: 6px;">🤖 Estudio de Voz de Marca & Prompt Dinámico</h3>
          <p style="font-size: 0.88rem; color: var(--text-muted);">Configura la personalidad, persona, idioma y prompt de la IA adaptado a cualquier cliente o nicho comercial.</p>
        </div>

        <div class="studio-grid-layout">
          <!-- Left Column: Identity Tuning & Golden Rules -->
          <div>
            <form onsubmit="App.saveBrandStudioForm(event)" novalidate autocomplete="off">
              <input type="hidden" id="setting-brand-id" value="" />
              
              <!-- Progressive Disclosure Sub-tabs Bar -->
              <div class="studio-subtabs-bar">
                <button type="button" class="studio-subtab-btn active" id="btn-subtab-identity" onclick="App.switchStudioTab('identity')">
                  <span>🎨 1. Personalidad & Tono</span>
                </button>
                <button type="button" class="studio-subtab-btn" id="btn-subtab-rules" onclick="App.switchStudioTab('rules')">
                  <span>🛡️ 2. Reglas & Ejemplos</span>
                </button>
                <?php if ($isAdmin): ?>
                <button type="button" class="studio-subtab-btn" id="btn-subtab-model" onclick="App.switchStudioTab('model')">
                  <span>🧠 3. Motor IA & API</span>
                </button>
                <?php endif; ?>
              </div>

              <!-- Tab Pane 1: Identity, Personality & Tone Calibration -->
              <div id="studio-tab-identity" class="studio-tab-pane">
                <!-- Block 1: Brand Fundamentals -->
              <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
                <h4 style="font-size: 1rem; font-weight: 800; margin-bottom: 16px; color: var(--accent-cyan); display: flex; align-items: center; gap: 8px;">
                  <span>🏢 Identidad, Persona & Nicho del Cliente</span>
                </h4>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                  <div class="form-group">
                    <label>Nombre de la Marca o Cliente:</label>
                    <input type="text" id="setting-brand-name" placeholder="Ej: Xindro Studio / Nike / Inmobiliaria Premier" />
                  </div>

                  <div class="form-group">
                    <label>Nombre de la Persona / Asistente:</label>
                    <input type="text" id="setting-persona-name" placeholder="Ej: Alex — Consultor Comercial / Sofía de Soporte" />
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                  <div class="form-group">
                    <label>Industria / Nicho de Negocio:</label>
                    <input type="text" id="setting-brand-industry" placeholder="Ej: E-commerce, Fitness, Moda, Real Estate, Servicios B2B" />
                  </div>

                  <div class="form-group">
                    <label>Idioma Principal de Respuestas:</label>
                    <select id="setting-brand-language">
                      <option value="es" selected>🇪🇸 Español</option>
                      <option value="pt">🇵🇹 Português</option>
                      <option value="en">🇬🇧 English</option>
                      <option value="any">🌐 Detectar y responder en español, portugués o inglés</option>
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
              </div><!-- End studio-tab-identity -->

              <!-- Tab Pane 2: Golden Rules & Few-Shot Master Examples -->
              <div id="studio-tab-rules" class="studio-tab-pane" style="display: none;">
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
              </div><!-- End studio-tab-rules -->

              <?php if ($isAdmin): ?>
              <!-- Tab Pane 3: AI Engine & Model Settings (Admin Only) -->
              <div id="studio-tab-model" class="studio-tab-pane" style="display: none;">
                <!-- Block 5: OpenRouter AI Engine & Multi-Model -->
                <div style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 24px;">
                  <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;">
                    <h4 style="font-size: 1rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
                      <span>🌐 Motor de Inteligencia Artificial (OpenRouter)</span>
                    </h4>
                    <span id="badge-openrouter-status" style="font-size: 0.72rem; padding: 4px 10px; border-radius: 6px; background: rgba(99,102,241,0.15); color: #a5b4fc; font-weight: 700; border: 1px solid rgba(99,102,241,0.3);">Multi-Model Hub</span>
                  </div>

                  <div class="form-group">
                    <label>Proveedor de Generación:</label>
                    <select id="setting-ai-provider" onchange="App.toggleAiProviderFields()">
                      <option value="openrouter" selected>🌐 OpenRouter (Claude Sonnet 4.5, DeepSeek V3/R1, GPT-4o, Gemini 2.5)</option>
                      <option value="heuristic">⚡ Motor Heurístico Calibrado Local (100% Gratuito • Cero Tokens • 0ms)</option>
                    </select>
                  </div>

                  <div id="openrouter-settings-fields">
                    <div class="form-group">
                      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <label style="margin-bottom: 0;">OpenRouter API Key:</label>
                        <span id="label-openrouter-key-status" style="font-size: 0.72rem; color: #34d399; font-weight: 600; display: none;">✅ Clave Activa</span>
                      </div>
                      <input type="text" class="masked-key-input" id="setting-openrouter-key" autocomplete="new-password" spellcheck="false" data-lpignore="true" data-form-type="other" placeholder="sk-or-v1-..." />
                      <small style="color: var(--text-dim); font-size: 0.74rem; display: block; margin-top: 4px;">Obtén o gestiona tu clave en <a href="https://openrouter.ai/keys" target="_blank" rel="noopener noreferrer" style="color: var(--primary); text-decoration: underline;">openrouter.ai/keys</a> para conectar cualquier LLM con un solo saldo.</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                      <label>Modelo de Inteligencia Preferido:</label>
                      <div style="display: flex; gap: 8px;">
                        <select id="setting-openrouter-model" style="flex: 1;" onchange="App.onOpenRouterModelSelect(this.value)">
                          <option value="nousresearch/hermes-3-llama-3.1-70b" selected>🌟 Nous Hermes 3 (Llama 3.1 70B) — (Recomendado • Ingenioso, empático y ultra conversacional)</option>
                          <option value="nousresearch/hermes-3-llama-3.1-405b">🧠 Nous Hermes 3 (405B) — (Máxima potencia de razonamiento)</option>
                          <option value="google/gemini-2.5-flash">⭐ Google Gemini 2.5 Flash (Ultrarrápido y económico)</option>
                          <option value="anthropic/claude-sonnet-4.5">💎 Anthropic Claude Sonnet 4.5 (Tono humano profundo y empático)</option>
                          <option value="deepseek/deepseek-chat">⚡ DeepSeek V3 (Ultra económico • Excelente en español y valor)</option>
                          <option value="openai/gpt-4o-mini">🚀 OpenAI GPT-4o Mini (Rápido, inteligente y equilibrado)</option>
                          <option value="openai/gpt-4o">🧠 OpenAI GPT-4o (Máxima potencia de razonamiento)</option>
                          <option value="meta-llama/llama-3.3-70b-instruct">🏛️ Meta Llama 3.3 70B (Open-Source líder)</option>
                          <option value="anthropic/claude-3-haiku">💨 Anthropic Claude 3 Haiku (Respuestas instantáneas)</option>
                          <option value="deepseek/deepseek-r1">🔍 DeepSeek R1 (Razonamiento profundo paso a paso)</option>
                          <option value="custom">✏️ Especificar otro modelo personalizado...</option>
                        </select>
                      </div>
                      <div id="openrouter-custom-model-wrapper" style="display: none; margin-top: 8px;">
                        <input type="text" id="setting-openrouter-custom-model" placeholder="ej. mistralai/mistral-large-2407" style="font-size: 0.82rem;" />
                      </div>
                    </div>

                    <!-- OpenRouter Action Buttons -->
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                      <button type="button" class="btn-primary-action" id="btn-save-ai-engine" onclick="App.saveAiEngineSettings(event)" style="padding: 10px 18px; font-weight: 700;">
                        <span>💾 Guardar Clave & Modelo IA</span>
                      </button>
                      <button type="button" class="btn-primary-action" id="btn-test-openrouter" onclick="App.testOpenRouterConnection()" style="background: linear-gradient(135deg, #06b6d4, #0284c7); padding: 10px 18px; font-weight: 700;">
                        <span>⚡ Probar Conexión & Saldo</span>
                      </button>
                    </div>

                    <!-- Live Test Results Box -->
                    <div id="openrouter-test-result" style="display: none; margin-top: 14px;"></div>
                  </div>
                </div>
              </div><!-- End studio-tab-model -->
              <?php endif; ?>

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
              <div class="quick-scenarios-row" style="display: flex; flex-wrap: wrap; gap: 6px;">
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('emoji_reaction')">🔥 Emojis / Reacción Rápida</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('course_qa')">📚 Curso / Acceso</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('philosophy')">🏛️ Dicotomía / Concepto</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('price_lead')">🎯 Precio / Lead</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('support')">🛠️ Soporte / Técnico</button>
                <button type="button" class="quick-scenario-btn" onclick="App.setPlaygroundScenario('gratitude')">✨ Elogio / Comunidad</button>
              </div>

              <div class="form-group" style="margin-bottom: 10px;">
                <label style="font-size: 0.76rem;">Nombre del Seguidor:</label>
                <input type="text" id="playground-author" value="Alejandro" style="padding: 7px 10px; font-size: 0.82rem;" />
              </div>

              <div class="form-group">
                <label style="font-size: 0.76rem;">Comentario de Prueba:</label>
                <textarea id="playground-comment" rows="3" style="font-size: 0.82rem;" placeholder="Escribe cualquier comentario ficticio para evaluar la respuesta de la IA..."></textarea>
              </div>

              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <button type="button" class="btn-primary-action" style="justify-content: center; background: linear-gradient(135deg, #6366f1, #3b82f6); font-size: 0.8rem; padding: 10px 8px;" onclick="App.testVoicePlayground()">
                  <span>⚡ Probar Respuesta IA</span>
                </button>
                <button type="button" class="btn-primary-action" style="justify-content: center; background: rgba(255, 255, 255, 0.08); border: 1px solid var(--border-subtle); color: #fff; font-size: 0.8rem; padding: 10px 8px;" onclick="App.openSimulateCommentModalFromPlayground()" title="Simular e inyectar este comentario en el Feed del Dashboard">
                  <span>📥 Simular en Feed</span>
                </button>
              </div>

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
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 24px;">Configura y diagnostica la vinculación segura de tu cuenta con Meta para acceder a estadísticas en vivo, moderación automática y certificación en App Review.</p>

        <!-- Official OAuth 2.0 One-Click Connect Box -->
        <div style="background: linear-gradient(135deg, rgba(24, 119, 242, 0.12), rgba(99, 102, 241, 0.12)); border: 1px solid rgba(24, 119, 242, 0.35); padding: 24px; border-radius: var(--radius-md); margin-bottom: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 1.3rem;">🚀</span>
                <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0;">Conexión Oficial con Facebook & Instagram (OAuth 2.0)</h4>
              </div>
              <p style="font-size: 0.84rem; color: #cbd5e1; margin: 0; max-width: 520px; line-height: 1.5;">
                Genera automáticamente <strong>Tokens de Larga Duración (60 días / Permanentes de Página)</strong> sin tener que copiar y pegar tokens manualmente desde Graph API Explorer.
              </p>
            </div>
            <button type="button" onclick="App.openMetaOAuthPopup()" class="btn-primary-action" style="background: #1877f2; padding: 12px 20px; font-size: 0.9rem; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(24, 119, 242, 0.4); border-radius: var(--radius-sm); color: #fff;">
              <span style="font-size: 1.1rem;">📘</span>
              <span>Continuar con Facebook & Instagram</span>
              <span>↗️</span>
            </button>
          </div>
        </div>

        <!-- Connected Accounts & Brand Voice Routing Manager Card -->
        <div class="connected-accounts-card" style="background: var(--bg-card); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span>📱 Cuentas Vinculadas & Asignación de Voz de Marca</span>
                <span class="badge-count-accounts" id="badge-total-connected-accounts" style="background: rgba(99, 102, 241, 0.2); color: #818cf8; font-size: 0.75rem; padding: 3px 10px; border-radius: 12px; font-weight: 700;">
                  <?= $activeAccountsCount ?> / <?= $maxAccounts ?> cuentas • <?= htmlspecialchars($planDetails['name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if ($userPlan !== 'agency'): ?>
                <button type="button" onclick="App.showUpgradePlanModal()" class="btn-primary-action" style="padding: 4px 10px; font-size: 0.74rem; background: linear-gradient(135deg, #f59e0b, #d97706); border: none; box-shadow: 0 2px 8px rgba(245, 158, 11, 0.35); cursor: pointer; border-radius: 6px;">
                  ⭐ Mejorar Plan
                </button>
                <?php endif; ?>
              </h4>
              <p style="font-size: 0.8rem; color: var(--text-muted); margin: 4px 0 0 0;">
                Asigna una <strong>Voz de Marca independiente</strong> a cada cuenta de Instagram o Página de Facebook vinculada. Tu plan actual te permite hasta <strong><?= $maxAccounts ?> <?= $maxAccounts === 1 ? 'cuenta' : 'cuentas' ?></strong>.
              </p>
            </div>
            <div style="display: flex; gap: 8px;">
              <button type="button" id="btn-reload-connected-accounts" class="btn-primary-action" style="padding: 8px 14px; font-size: 0.8rem; background: rgba(99,102,241,0.15); border: 1px solid var(--border-active);" onclick="App.loadConnectedAccounts(true)">
                <span>🔄 Recargar Cuentas</span>
              </button>
            </div>
          </div>

          <div id="connected-accounts-list" class="connected-accounts-grid">
            <div style="padding: 24px; text-align: center; color: var(--text-dim); font-size: 0.84rem;">
              Cargando cuentas vinculadas...
            </div>
          </div>
        </div>

        <?php if ($isAdmin): ?>
        <!-- Advanced Developer Settings (Collapsible - Admin Only) -->
        <details class="advanced-dev-details" style="background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 20px; overflow: hidden;">
          <summary style="padding: 18px 24px; font-size: 0.95rem; font-weight: 800; color: var(--text-main); cursor: pointer; display: flex; align-items: center; justify-content: space-between; user-select: none;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span>🛠️</span>
              <span>Configuración Avanzada / Desarrolladores (Webhooks, Tokens Manuales & App Review)</span>
            </div>
            <span style="font-size: 0.75rem; color: var(--text-dim); background: rgba(255,255,255,0.05); padding: 3px 8px; border-radius: 4px;">Solo Admin ▼</span>
          </summary>

          <div style="padding: 0 24px 24px 24px; border-top: 1px solid var(--border-subtle);">
            
            <!-- Meta App Credentials & Tokens Card -->
            <div style="margin-top: 20px; margin-bottom: 20px;">
              <h4 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 12px; color: var(--fb-blue);">🔑 Credenciales y Tokens Manuales de Meta</h4>

              <form onsubmit="App.saveSettingsForm(event)" autocomplete="off">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                  <div class="form-group">
                    <label>Meta App ID:</label>
                    <input type="text" id="setting-meta-app-id" autocomplete="off" spellcheck="false" placeholder="Ej: 102938475610293" />
                  </div>

                  <div class="form-group">
                    <label>Meta App Secret:</label>
                    <input type="text" class="masked-key-input" id="setting-meta-app-secret" autocomplete="new-password" spellcheck="false" data-lpignore="true" data-form-type="other" placeholder="••••••••••••••••" />
                  </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                  <div class="form-group">
                    <label>Instagram Business Account ID:</label>
                    <input type="text" id="setting-meta-ig-id" autocomplete="off" spellcheck="false" placeholder="Ej: 17841400000000000" />
                  </div>

                  <div class="form-group">
                    <label>Meta Page Access Token (Manual / Override):</label>
                    <input type="text" class="masked-key-input" id="setting-meta-token" autocomplete="new-password" spellcheck="false" data-lpignore="true" data-form-type="other" placeholder="EAA..." />
                  </div>
                </div>

                <div style="display: flex; gap: 12px; margin-top: 20px; flex-wrap: wrap;">
                  <button type="submit" class="btn-primary-action">
                    Guardar Credenciales 💾
                  </button>
                  <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #06b6d4, #0284c7);" onclick="App.testMetaConnection()">
                    🔍 Diagnosticar Token & Permisos
                  </button>
                  <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #10b981, #059669);" onclick="App.auditMetaAppReview()">
                    🛡️ Pre-Auditoría App Review Meta
                  </button>
                  <button type="button" class="btn-primary-action" style="background: #1877f2;" onclick="App.triggerMetaSync()">
                    🔄 Sincronizar en Vivo
                  </button>
                </div>
              </form>

              <!-- Live Diagnostics Report Box -->
              <div id="meta-diagnostic-container" style="display: none; margin-top: 20px;">
                <!-- Diagnostic results injected dynamically -->
              </div>

              <!-- Pre-Audit Scanner Report Box -->
              <div id="meta-audit-container" style="display: none; margin-top: 20px;">
                <!-- Pre-audit results injected dynamically -->
              </div>
            </div>

            <!-- Webhooks Box -->
            <div style="background: rgba(0,0,0,0.2); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
              <h4 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 10px;">📡 Webhook en Tiempo Real (Meta Developers)</h4>
              <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 12px;">Para que el agente reciba y responda comentarios inmediatamente cuando se publican en tus posts:</p>

              <div style="background: rgba(0,0,0,0.4); padding: 10px 14px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.82rem; color: #a5b4fc; margin-bottom: 8px;">
                Callback URL: <strong>https://socialapi.turbogram.site/api/webhook.php</strong>
              </div>
              <div style="background: rgba(0,0,0,0.4); padding: 10px 14px; border-radius: var(--radius-sm); font-family: monospace; font-size: 0.82rem; color: #34d399;">
                Verify Token: <strong>social_boost_secure_token_2026</strong>
              </div>
              <div style="margin-top: 8px; font-size: 0.76rem; color: var(--text-dim);">
                Campos requeridos en la suscripción del Webhook: <code style="color: #f1f5f9;">feed</code> (Páginas de Facebook) y <code style="color: #f1f5f9;">comments</code>, <code style="color: #f1f5f9;">mentions</code> (Instagram Graph API).
              </div>
            </div>

            <!-- Compliance & URLs -->
            <div style="background: rgba(0,0,0,0.2); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 20px;">
              <h4 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 12px; color: var(--accent-cyan);">📋 URLs Oficiales para Meta App Dashboard</h4>
              <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;">
                Copia y pega estas URLs directamente en <strong>Meta for Developers &gt; Configuración básica</strong>:
              </p>

              <div style="display: flex; flex-direction: column; gap: 8px;">
                <div style="background: rgba(0,0,0,0.3); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                  <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 2px;">URL de Privacidad:</div>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="color: #a5b4fc; font-size: 0.82rem;">https://socialapi.turbogram.site/privacy-policy.php</code>
                    <a href="privacy-policy.php" target="_blank" class="btn-primary-action" style="padding: 3px 8px; font-size: 0.7rem; text-decoration: none;">Ver ↗️</a>
                  </div>
                </div>

                <div style="background: rgba(0,0,0,0.3); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                  <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 2px;">URL de Términos:</div>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="color: #a5b4fc; font-size: 0.82rem;">https://socialapi.turbogram.site/terms-of-service.php</code>
                    <a href="terms-of-service.php" target="_blank" class="btn-primary-action" style="padding: 3px 8px; font-size: 0.7rem; text-decoration: none;">Ver ↗️</a>
                  </div>
                </div>

                <div style="background: rgba(0,0,0,0.3); padding: 10px 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
                  <div style="font-size: 0.72rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase; margin-bottom: 2px;">URL de Eliminación de Datos:</div>
                  <div style="display: flex; justify-content: space-between; align-items: center;">
                    <code style="color: #38bdf8; font-size: 0.82rem;">https://socialapi.turbogram.site/data-deletion.php</code>
                    <a href="data-deletion.php" target="_blank" class="btn-primary-action" style="padding: 3px 8px; font-size: 0.7rem; text-decoration: none;">Ver ↗️</a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Compliance Checklist -->
            <div style="background: rgba(0,0,0,0.2); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle);">
              <h4 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 10px; color: var(--accent-emerald);">🛡️ Kit de Aprobación para Meta App Review</h4>
              <ul style="font-size: 0.82rem; color: var(--text-muted); line-height: 1.7; padding-left: 18px; margin: 0;">
                <li><strong>Verificación de Negocio:</strong> En Meta Business Manager para solicitar permisos avanzados en App Review.</li>
                <li><strong>Cuenta de Instagram Profesional:</strong> Tipo <em>Creador</em> o <em>Empresa</em> vinculada a una Página de Facebook.</li>
                <li><strong>Protocolo HTTPS / SSL:</strong> Certificado SSL válido (TLS 1.2+).</li>
                <li><strong>Kit de Textos de Justificación:</strong> En la carpeta <code>docs/meta-app-review-kit.md</code>.</li>
              </ul>
            </div>

          </div>
        </details>
        <?php endif; ?>

      </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- View 5: Admin Users, AI Models & Token Quota Management -->
    <div id="view-users" style="display: none; padding: 28px; overflow-y: auto; height: calc(100vh - 70px);">
      <div style="max-width: 1280px; margin: 0 auto;">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 24px;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
              <h3 style="font-size: 1.45rem; font-weight: 800; color: #fff; margin: 0;">👥 Gestión de Usuarios, Modelos IA & Cuotas de Tokens</h3>
              <span style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; background: rgba(99,102,241,0.18); color: #a5b4fc; font-weight: 700; border: 1px solid rgba(99,102,241,0.3);">Panel Admin</span>
            </div>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin: 0;">Supervisa usuarios registrados, detecta conexiones en tiempo real, asigna el modelo LLM por cuenta y define los límites de tokens.</p>
          </div>

          <div style="display: flex; gap: 10px;">
            <button type="button" class="btn-primary-action" onclick="App.loadAdminUsers(true)" style="background: rgba(255,255,255,0.06); border: 1px solid var(--border-subtle); color: #fff; padding: 10px 16px;">
              <span>🔄 Actualizar Estado</span>
            </button>
          </div>
        </div>

        <!-- KPI Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; margin-bottom: 24px;">
          <div class="admin-kpi-card">
            <div class="admin-kpi-icon" style="background: rgba(99,102,241,0.15); color: #818cf8;">👥</div>
            <div>
              <div class="admin-kpi-label">Usuarios Registrados</div>
              <div class="admin-kpi-val" id="kpi-total-users">--</div>
            </div>
          </div>

          <div class="admin-kpi-card">
            <div class="admin-kpi-icon" style="background: rgba(16,185,129,0.15); color: #34d399;">🟢</div>
            <div>
              <div class="admin-kpi-label">En Línea Ahora</div>
              <div class="admin-kpi-val" id="kpi-online-users" style="color: #34d399;">--</div>
            </div>
          </div>

          <div class="admin-kpi-card">
            <div class="admin-kpi-icon" style="background: rgba(245,158,11,0.15); color: #fbbf24;">⚡</div>
            <div>
              <div class="admin-kpi-label">Tokens Totales Consumidos</div>
              <div class="admin-kpi-val" id="kpi-total-tokens">--</div>
            </div>
          </div>

          <div class="admin-kpi-card">
            <div class="admin-kpi-icon" style="background: rgba(236,72,153,0.15); color: #f472b6;">🧠</div>
            <div>
              <div class="admin-kpi-label">Modelo Más Asignado</div>
              <div class="admin-kpi-val" id="kpi-top-model" style="font-size: 0.95rem; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;">--</div>
            </div>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div style="background: var(--bg-card); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
          <div style="display: flex; align-items: center; gap: 10px; flex: 1; min-width: 260px;">
            <span style="color: var(--text-dim); font-size: 1.1rem;">🔍</span>
            <input type="text" id="admin-user-search-input" placeholder="Buscar por nombre o correo electrónico..." style="background: transparent; border: none; outline: none; color: #fff; width: 100%; font-size: 0.88rem;" oninput="App.filterAdminUsersTable(this.value)" />
          </div>

          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <button type="button" class="admin-filter-pill active" id="filter-user-all" onclick="App.setAdminUserFilter('all')">Todos</button>
            <button type="button" class="admin-filter-pill" id="filter-user-online" onclick="App.setAdminUserFilter('online')">🟢 En Línea</button>
            <button type="button" class="admin-filter-pill" id="filter-user-client" onclick="App.setAdminUserFilter('user')">Clientes</button>
            <button type="button" class="admin-filter-pill" id="filter-user-admin" onclick="App.setAdminUserFilter('admin')">Admins</button>
          </div>
        </div>

        <!-- Users Data Table Container -->
        <div style="background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-subtle); overflow: hidden; box-shadow: var(--shadow-sm);">
          <div style="overflow-x: auto;">
            <table class="admin-users-table" style="width: 100%; border-collapse: collapse; text-align: left;">
              <thead>
                <tr style="border-bottom: 1px solid var(--border-subtle); background: rgba(255,255,255,0.02); font-size: 0.76rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">
                  <th style="padding: 14px 18px;">Usuario / Cuenta</th>
                  <th style="padding: 14px 18px;">Estado & Presencia</th>
                  <th style="padding: 14px 18px; min-width: 280px;">Modelo IA Asignado</th>
                  <th style="padding: 14px 18px; min-width: 260px;">Cuota de Tokens & Consumo</th>
                  <th style="padding: 14px 18px; text-align: right;">Acciones</th>
                </tr>
              </thead>
              <tbody id="admin-users-tbody">
                <tr>
                  <td colspan="5" style="padding: 36px; text-align: center; color: var(--text-muted); font-size: 0.88rem;">
                    <div style="display: inline-block; width: 22px; height: 22px; border: 2px solid rgba(99,102,241,0.3); border-top-color: #6366f1; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 8px;"></div>
                    <div>Cargando directorio de usuarios...</div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </div>
    <?php endif; ?>

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

<!-- Modal: Reporte Semanal de Eficiencia & Auditoría de Bandeja -->
<div class="modal-overlay" id="modal-weekly-report" onclick="if(event.target===this) App.closeModal('modal-weekly-report')">
  <div class="modal-box modal-weekly-box">
    
    <!-- Modal Header -->
    <div class="modal-header">
      <div class="modal-assistant-title-group">
        <div class="modal-assistant-icon-badge" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4); color: #34d399;">📊</div>
        <div>
          <h3 class="modal-assistant-title">Reporte Semanal de Eficiencia & Auditoría</h3>
          <p class="modal-assistant-subtitle">Auditoría de respuestas, SLA de atención y síntesis ejecutiva generada con IA</p>
        </div>
      </div>
      <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-weekly-report')" title="Cerrar">&times;</button>
    </div>

    <!-- Modal View Tabs Switcher -->
    <div class="modal-assistant-tab-switcher">
      <button type="button" class="modal-tab-btn active" id="modal-tab-btn-weekly-current" onclick="App.switchWeeklyReportModalTab('current')">
        <span>📈 Reporte Semanal Actual</span>
      </button>
      <button type="button" class="modal-tab-btn" id="modal-tab-btn-weekly-history" onclick="App.switchWeeklyReportModalTab('history')">
        <span>🗄️ Historial de Reportes</span>
      </button>
    </div>

    <div class="modal-assistant-body" style="padding: 22px;">
      
      <!-- SUBVIEW 1: Current Report -->
      <div id="weekly-report-view-current" class="weekly-modal-subview">
        <!-- Top Status Banner -->
        <div class="weekly-report-top-banner">
          <div>
            <span style="font-size: 0.76rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 0.04em;">Periodo Analizado</span>
            <h4 id="weekly-report-label" style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 2px 0 0 0;">Cargando reporte...</h4>
          </div>
          <div class="weekly-score-pill" id="weekly-report-score-pill">
            <span>⭐</span>
            <span id="weekly-report-score-text">--% Eficiencia</span>
          </div>
        </div>

        <!-- 4 Interactive Auditable KPI Cards -->
        <div class="weekly-kpi-grid">
          <div class="weekly-kpi-card interactive" onclick="App.showWeeklyDrilldown('replied')" style="cursor: pointer;" title="👆 Haz clic para auditar los comentarios respondidos con éxito">
            <span class="weekly-kpi-icon">💬</span>
            <span class="weekly-kpi-label">Respuestas Efectivas</span>
            <span class="weekly-kpi-value" id="kpi-weekly-replied">0</span>
            <span class="weekly-kpi-sub" id="kpi-weekly-total-sub">de 0 comentarios</span>
            <span class="weekly-kpi-click-hint">👆 Clic para auditar</span>
          </div>
          <div class="weekly-kpi-card interactive" onclick="App.showWeeklyDrilldown('sla')" style="cursor: pointer;" title="👆 Haz clic para ver el desglose de velocidad y distribución SLA">
            <span class="weekly-kpi-icon">⏱️</span>
            <span class="weekly-kpi-label">Tiempo SLA Promedio</span>
            <span class="weekly-kpi-value" id="kpi-weekly-time" style="color: #67e8f9;">0m</span>
            <span class="weekly-kpi-sub">Velocidad de respuesta</span>
            <span class="weekly-kpi-click-hint">📊 Desglose de velocidad</span>
          </div>
          <div class="weekly-kpi-card interactive" onclick="App.showWeeklyDrilldown('leads')" style="cursor: pointer;" title="👆 Haz clic para ver los leads y oportunidades de venta">
            <span class="weekly-kpi-icon">🎯</span>
            <span class="weekly-kpi-label">Leads & Ventas</span>
            <span class="weekly-kpi-value" id="kpi-weekly-leads" style="color: #fde047;">0</span>
            <span class="weekly-kpi-sub">Oportunidades comerciales</span>
            <span class="weekly-kpi-click-hint">💼 Ver oportunidades</span>
          </div>
          <div class="weekly-kpi-card interactive" onclick="App.showWeeklyDrilldown('copilot')" style="cursor: pointer;" title="👆 Haz clic para auditar respuestas del Copiloto IA vs Manuales">
            <span class="weekly-kpi-icon">🤖</span>
            <span class="weekly-kpi-label">Asistencia Copiloto IA</span>
            <span class="weekly-kpi-value" id="kpi-weekly-copilot" style="color: #c084fc;">0</span>
            <span class="weekly-kpi-sub" id="kpi-weekly-manual-sub">0 manuales</span>
            <span class="weekly-kpi-click-hint">⚡ Ver automatización</span>
          </div>
        </div>

        <!-- Interactive Drilldown Panel Container -->
        <div id="weekly-drilldown-panel" class="weekly-drilldown-panel" style="display: none;">
          <div class="weekly-drilldown-header">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span id="weekly-drilldown-icon" style="font-size: 1.15rem;">💬</span>
              <h4 id="weekly-drilldown-title" style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #fff;">Auditoría de Comentarios</h4>
            </div>
            <button type="button" class="btn-close-drilldown" onclick="App.closeWeeklyDrilldown()" title="Cerrar vista">&times;</button>
          </div>
          <div id="weekly-drilldown-content" class="weekly-drilldown-content">
            <!-- Contenido cargado dinámicamente -->
          </div>
        </div>

        <!-- AI Executive Insights Box -->
        <div class="weekly-ai-insights-box">
          <div class="weekly-ai-insights-header">
            <span>✨</span>
            <span>Diagnóstico Ejecutivo & Recomendaciones de la IA</span>
          </div>
          <div class="weekly-ai-insights-content" id="weekly-report-ai-text">
            Generando síntesis de rendimiento semanal...
          </div>
        </div>

        <!-- Actions Row -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-top: 15px;">
          <button type="button" class="btn-primary-action" style="background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3);" onclick="App.viewArchivedFromModal()">
            <span>🗄️ Ver Comentarios Archivados</span>
          </button>
          <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); border: none;" onclick="App.confirmAndRunWeeklyCleanup()">
            <span>🧹 Ejecutar Limpieza & Actualizar Reporte</span>
          </button>
        </div>
      </div>

      <!-- SUBVIEW 2: History of Reports -->
      <div id="weekly-report-view-history" class="weekly-modal-subview" style="display: none;">
        <div class="weekly-history-list" id="weekly-history-container">
          <div style="text-align: center; color: #94a3b8; padding: 30px;">Cargando historial de reportes semanales...</div>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Modal: Asistente de Respuestas & Conexión (Popup de Sugerencias Reflexivas, Motivacionales, Comunitarias y Auto-Responder en Vivo) -->
<div class="modal-overlay modal-assistant-overlay" id="modal-assistant-replies">
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
      <div class="modal-header-actions" style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="btn-modal-sugg-action" id="btn-modal-capture-image" style="background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.35); font-size: 0.75rem; padding: 6px 12px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; font-weight: 600;" onclick="AgentController.captureModalAsImage()" title="📸 Capturar y copiar imagen PNG del asistente directamente al portapapeles y descargar">
          <span>📸 Capturar Imagen</span>
        </button>
        <button type="button" class="btn-modal-sugg-action" style="background: rgba(99,102,241,0.2); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.35); font-size: 0.75rem; padding: 6px 12px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" onclick="AgentController.copyFullModalSummary()" title="Copiar todo el resumen y opciones del asistente al portapapeles">
          <span>📋 Copiar Resumen</span>
        </button>
        <button type="button" class="btn-modal-sugg-action" id="btn-toggle-assistant-drawer" style="background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); font-size: 0.75rem; padding: 6px 10px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;" onclick="AgentController.toggleAssistantViewMode()" title="Alternar entre Panel Lateral (Split-View) y Modal Centrado">
          <span id="label-assistant-viewmode">◧ Panel Lateral</span>
        </button>
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
          <!-- Connected Account & Brand Voice Indicator Bar -->
          <div class="modal-account-voice-bar" id="modal-account-voice-bar" style="display: flex; align-items: center; justify-content: space-between; background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.2); padding: 8px 14px; border-radius: var(--radius-sm); margin-bottom: 12px; font-size: 0.78rem;">
            <div style="display: flex; align-items: center; gap: 6px;">
              <span style="color: var(--text-dim);">📱 Cuenta:</span>
              <strong id="modal-account-name-badge" style="color: #fff;">@cuenta</strong>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
              <span style="color: var(--text-dim);">🎭 Voz Calibrada:</span>
              <strong id="modal-brand-voice-badge" style="color: var(--accent-cyan);">Voz de Marca</strong>
            </div>
          </div>

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
            <div style="font-size: 0.74rem; color: var(--text-dim); display: flex; align-items: center; gap: 6px;">
              <span style="color: #34d399; font-weight: 700;">🧠 Gemini Learning:</span>
              <span>Tus aprobaciones y correcciones entrenan su estilo en tiempo real</span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <button type="button" class="btn-modal-cancel" onclick="App.closeModal('modal-assistant-replies')">
                Cerrar
              </button>
              <button type="button" class="btn-modal-sugg-action" id="btn-modal-save-gold-custom" style="background: rgba(245,158,11,0.2); color: #fbbf24; border: 1px solid rgba(245,158,11,0.4); font-weight: 700; padding: 9px 15px; border-radius: 8px; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;" onclick="AgentController.saveAsGoldExample(null, true)" title="⭐ Guardar tus palabras editadas en la memoria de Hermes sin publicar en Meta">
                <span>⭐ Guardar como Oro (Sin Publicar)</span>
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
            <div class="autopilot-header-btn-wrap" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
              <div style="display: flex; align-items: center; gap: 6px;">
                <label for="autopilot-delay-select" style="font-size: 0.74rem; color: var(--text-dim); margin-bottom: 0; white-space: nowrap;">⏳ Cadencia:</label>
                <select id="autopilot-delay-select" style="padding: 7px 10px; font-size: 0.76rem; border-radius: 8px; background: rgba(15,23,42,0.7); color: #34d399; border: 1px solid rgba(52,211,153,0.3); font-weight: 600;">
                  <option value="natural" selected>🧘 Humano Natural (4-7s aleatorio • Anti-Bot)</option>
                  <option value="safe">🛡️ Máxima Seguridad (8-14s • Cuentas Nuevas)</option>
                  <option value="fast">⚡ Rápido (2-3s • Pruebas)</option>
                </select>
              </div>
              <button type="button" class="btn-run-autopilot-live" id="btn-run-autopilot-live" onclick="AgentController.startLiveAutopilot()">
                <span class="btn-icon">⚡</span>
                <span id="btn-run-autopilot-live-text">Ejecutar Auto-Responder Ahora</span>
              </button>
              <button type="button" class="btn-cancel" id="btn-pause-autopilot-live" style="display: none; padding: 9px 16px; font-size: 0.8rem; font-weight: 700; background: rgba(239, 68, 68, 0.18); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.4); border-radius: 8px; cursor: pointer;" onclick="AgentController.pauseLiveAutopilot()">
                <span>⏸️ Pausar</span>
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
              <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" onclick="AgentController.resetFailedComments()" style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; font-size: 0.72rem; padding: 4px 8px; border-radius: 6px; cursor: pointer;" title="Restablece comentarios que hayan fallado para volver a procesarlos">🔄 Reintentar Fallidos</button>
                <span class="autopilot-stream-badge" id="autopilot-pending-count-badge">Calculando pendientes...</span>
              </div>
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
            <p>Abre el Copiloto IA para forjar una respuesta inteligente calibrada con tu comunidad.</p>
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
<div class="modal-overlay" id="modal-new-brand" onclick="if(event.target===this) App.closeModal('modal-new-brand')">
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
              <option value="pt">🇵🇹 Português</option>
              <option value="en">🇬🇧 English</option>
              <option value="any">🌐 Detectar y responder en español, portugués o inglés</option>
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

<!-- Modal: AI Content Creator & Golden Scheduler -->
<div class="modal-overlay" id="modal-content-creator" onclick="if(event.target===this) PlannerController.closeCreatorModal()">
  <div class="modal-box modal-lg" style="max-width: 960px; max-height: 90vh; display: flex; flex-direction: column;">
    <div class="modal-header">
      <div style="display: flex; align-items: center; gap: 10px;">
        <span style="font-size: 1.4rem;">✨</span>
        <div>
          <h3 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0;">
            Estudio de Creación de Contenido con IA
          </h3>
          <p style="font-size: 0.78rem; color: var(--text-muted); margin: 0;">
            Genera copys de alta conversión calibrados con tu voz de marca y programa en horarios dorados.
          </p>
        </div>
      </div>
      <button type="button" class="btn-close-modal" onclick="PlannerController.closeCreatorModal()">&times;</button>
    </div>

    <div class="modal-body" style="padding: 16px 0; overflow-y: auto; flex: 1;">
      <!-- Step 1: Input Form -->
      <div id="creator-step-input" style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 20px;">
        <div style="grid-column: 1 / -1;">
          <label style="font-size: 0.82rem; font-weight: 700; color: #fff; margin-bottom: 6px; display: block;">
            💡 ¿De qué quieres hablar en esta publicación? (Idea / Tema / Noticia)
          </label>
          <div style="display: flex; gap: 10px;">
            <input type="text" id="creator-topic-input" class="form-input" style="flex: 1;" placeholder="Ej. 3 Claves de enfoque diario para emprendedores, o Lanzamiento de nueva membresía..." />
            <button class="btn-primary-action" id="btn-generate-drafts" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); min-width: 180px; justify-content: center;" onclick="PlannerController.triggerGenerateDrafts()">
              <span>⚡ Generar con IA</span>
            </button>
          </div>
        </div>

        <div>
          <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">Formato de Publicación:</label>
          <select id="creator-format-select" class="form-select">
            <option value="reel" selected>🎥 Video / Reel (Mayor Retención)</option>
            <option value="carousel">📑 Carrusel Educativo</option>
            <option value="image">📷 Imagen / Frase Gráfica</option>
            <option value="story">📱 Historia / Story</option>
          </select>
        </div>

        <div>
          <label style="font-size: 0.8rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">Objetivo Comercial:</label>
          <select id="creator-goal-select" class="form-select">
            <option value="connection" selected>🤝 Conexión & Reflexión (Engagement)</option>
            <option value="conversion">🎯 Conversión & Leads (Ventas / DM)</option>
            <option value="authority">🧠 Autoridad & Educación (Guardados)</option>
          </select>
        </div>
      </div>

      <!-- Step 2: Generated Proposals Grid -->
      <div id="creator-proposals-wrapper" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
          <h5 style="font-size: 0.92rem; font-weight: 800; color: var(--accent-cyan);">
            🎯 Selecciona una Propuesta Generada para Personalizar:
          </h5>
          <span style="font-size: 0.74rem; color: var(--text-muted);">Haz clic en una tarjeta para editar y programar</span>
        </div>

        <div id="creator-proposals-grid" class="proposals-cards-grid">
          <!-- Rendered dynamically -->
        </div>

        <!-- Selected Proposal Editor -->
        <div id="creator-editor-box" class="creator-editor-container" style="display: none; margin-top: 20px;">
          <h5 style="font-size: 0.9rem; font-weight: 800; color: #fff; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
            <span>✏️</span> Editor Final & Programación en Horario Dorado
          </h5>

          <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 18px;">
            <!-- Left: Text Editor -->
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <div>
                <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">Gancho / Hook de Entrada:</label>
                <input type="text" id="editor-hook-input" class="form-input" style="font-weight: 700; color: #fff;" />
              </div>

              <div>
                <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">Texto Completo del Copy:</label>
                <textarea id="editor-caption-input" class="form-textarea" rows="7" style="line-height: 1.5; font-size: 0.84rem;"></textarea>
              </div>

              <div>
                <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">URL de Imagen / Media de Apoyo (Opcional):</label>
                <input type="text" id="editor-media-input" class="form-input" placeholder="https://images.unsplash.com/..." />
              </div>
            </div>

            <!-- Right: Golden Slot Selector & Details -->
            <div style="background: rgba(255, 255, 255, 0.025); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
              <div style="display: flex; flex-direction: column; gap: 12px;">
                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--accent-amber); margin-bottom: 6px; display: block;">
                    🏆 Horario Dorado Sugerido:
                  </label>
                  <select id="editor-golden-slot-select" class="form-select" onchange="PlannerController.onGoldenSlotSelectChanged(this.value)">
                    <!-- Populated dynamically -->
                  </select>
                </div>

                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">
                    Fecha y Hora Exacta (Editable):
                  </label>
                  <input type="datetime-local" id="editor-datetime-input" class="form-input" />
                </div>

                <div>
                  <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-dim); margin-bottom: 4px; display: block;">Plataforma de Publicación:</label>
                  <select id="editor-platform-select" class="form-select">
                    <option value="instagram">📸 Instagram (Feed & Reels)</option>
                    <option value="facebook">📘 Facebook (Muro)</option>
                  </select>
                </div>
              </div>

              <div style="display: flex; gap: 8px; margin-top: 16px;">
                <button type="button" class="btn-primary-action" style="flex: 1; justify-content: center; background: linear-gradient(135deg, #10b981 0%, #059669 100%);" onclick="PlannerController.savePostFromEditor('scheduled')">
                  <span>📅 Programar Post</span>
                </button>
                <button type="button" class="btn-primary-action" style="background: rgba(255, 255, 255, 0.06); color: var(--text-muted);" onclick="PlannerController.savePostFromEditor('draft')">
                  <span>📝 Borrador</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Post Detail & Live Preview Modal -->
<div class="modal-overlay" id="modal-post-preview" onclick="if(event.target===this) PlannerController.closePreviewModal()">
  <div class="modal-box" style="max-width: 640px;">
    <div class="modal-header">
      <h4 id="preview-modal-title" style="font-size: 1rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 6px;">
        <span>📱</span> Detalle de Publicación Programada
      </h4>
      <button type="button" class="btn-close-modal" onclick="PlannerController.closePreviewModal()">&times;</button>
    </div>

    <div class="modal-body" id="preview-modal-content" style="padding: 16px 0;">
      <!-- Rendered dynamically -->
    </div>
  </div>
</div>

<!-- Modal: Upgrade Plan / Comparador de Planes -->
<div class="modal-overlay" id="modal-upgrade-plan" style="display: none;" onclick="if(event.target===this) App.closeUpgradePlanModal()">
  <div class="modal-box" style="max-width: 860px; background: #0f172a; border: 1px solid rgba(99, 102, 241, 0.35); box-shadow: 0 25px 60px rgba(0,0,0,0.85);">
    <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 16px;">
      <div>
        <h4 style="font-size: 1.2rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; margin: 0;">
          <span>⭐</span> Planes & Capacidad de Cuentas Conectadas
        </h4>
        <p style="font-size: 0.84rem; color: var(--text-muted); margin: 4px 0 0 0;">
          Escala tu plan para vincular más canales de Instagram y Páginas de Facebook con respuestas automáticas.
        </p>
      </div>
      <button type="button" class="btn-close-modal" onclick="App.closeUpgradePlanModal()">&times;</button>
    </div>

    <div class="modal-body" style="padding: 22px 0 10px 0;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 14px;">
        
        <!-- Inicial -->
        <div class="plan-upgrade-card <?= ($userPlan === 'starter') ? 'current-active' : '' ?>" style="background: rgba(255,255,255,0.02); border: 1px solid <?= ($userPlan === 'starter') ? '#10b981' : 'rgba(255,255,255,0.08)' ?>; border-radius: 14px; padding: 16px;">
          <div style="font-size: 0.72rem; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Entrada</div>
          <h5 style="font-size: 1rem; font-weight: 800; color: #fff; margin: 4px 0;">Plan Inicial</h5>
          <div style="font-size: 1.3rem; font-weight: 900; color: #fff; margin: 8px 0 12px 0;">0 € <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 600;">/ mes gratis</span></div>
          <ul style="font-size: 0.76rem; color: #cbd5e1; list-style: none; padding: 0; margin: 0 0 16px 0; line-height: 1.8;">
            <li>✔ <strong>1 cuenta conectada</strong></li>
            <li>✔ 50.000 tokens / mes</li>
            <li>✔ 1 Voz de Marca</li>
          </ul>
          <?php if ($userPlan === 'starter'): ?>
            <span style="display: block; text-align: center; padding: 7px; font-size: 0.75rem; font-weight: 800; color: #10b981; background: rgba(16,185,129,0.1); border-radius: 8px;">Tu Plan Actual</span>
          <?php else: ?>
            <span style="display: block; text-align: center; padding: 7px; font-size: 0.75rem; font-weight: 700; color: #64748b;">Plan Base</span>
          <?php endif; ?>
        </div>

        <!-- Creador -->
        <div class="plan-upgrade-card <?= ($userPlan === 'creator') ? 'current-active' : '' ?>" style="background: rgba(59,130,246,0.04); border: 1px solid <?= ($userPlan === 'creator') ? '#3b82f6' : 'rgba(59,130,246,0.3)' ?>; border-radius: 14px; padding: 16px;">
          <div style="font-size: 0.72rem; font-weight: 800; color: #60a5fa; text-transform: uppercase;">Económico</div>
          <h5 style="font-size: 1rem; font-weight: 800; color: #fff; margin: 4px 0;">Plan Creador</h5>
          <div style="font-size: 1.3rem; font-weight: 900; color: #60a5fa; margin: 8px 0 12px 0;">9.99 € <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 600;">/ mes</span></div>
          <ul style="font-size: 0.76rem; color: #cbd5e1; list-style: none; padding: 0; margin: 0 0 16px 0; line-height: 1.8;">
            <li>✔ <strong>2 cuentas conectadas</strong></li>
            <li>✔ 150.000 tokens / mes</li>
            <li>✔ Ventana de oro algoritmo</li>
          </ul>
          <?php if ($userPlan === 'creator'): ?>
            <span style="display: block; text-align: center; padding: 7px; font-size: 0.75rem; font-weight: 800; color: #3b82f6; background: rgba(59,130,246,0.1); border-radius: 8px;">Tu Plan Actual</span>
          <?php else: ?>
            <a href="https://wa.me/?text=<?= urlencode('Hola, quiero actualizar mi cuenta al Plan Creador (2 cuentas)') ?>" target="_blank" class="btn-primary-action" style="display: block; text-align: center; padding: 8px; font-size: 0.75rem; background: #2563eb; text-decoration: none;">Elegir Creador</a>
          <?php endif; ?>
        </div>

        <!-- Pro / Negocio -->
        <div class="plan-upgrade-card featured <?= ($userPlan === 'pro') ? 'current-active' : '' ?>" style="background: rgba(124,58,237,0.08); border: 2px solid #7c3aed; border-radius: 14px; padding: 16px; position: relative;">
          <div style="position: absolute; top: -10px; right: 12px; background: #7c3aed; color: #fff; font-size: 0.65rem; font-weight: 800; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;">Popular</div>
          <div style="font-size: 0.72rem; font-weight: 800; color: #c084fc; text-transform: uppercase;">Recomendado</div>
          <h5 style="font-size: 1rem; font-weight: 800; color: #fff; margin: 4px 0;">Pro / Negocio</h5>
          <div style="font-size: 1.3rem; font-weight: 900; color: #a78bfa; margin: 8px 0 12px 0;">20.99 € <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 600;">/ mes</span></div>
          <ul style="font-size: 0.76rem; color: #cbd5e1; list-style: none; padding: 0; margin: 0 0 16px 0; line-height: 1.8;">
            <li>✔ <strong>Hasta 5 cuentas conectadas</strong></li>
            <li>✔ 500.000 tokens / mes</li>
            <li>✔ Detección leads & timing</li>
          </ul>
          <?php if ($userPlan === 'pro'): ?>
            <span style="display: block; text-align: center; padding: 7px; font-size: 0.75rem; font-weight: 800; color: #a78bfa; background: rgba(124,58,237,0.15); border-radius: 8px;">Tu Plan Actual</span>
          <?php else: ?>
            <a href="https://wa.me/?text=<?= urlencode('Hola, quiero actualizar mi cuenta al Plan Pro / Negocio (5 cuentas)') ?>" target="_blank" class="btn-primary-action" style="display: block; text-align: center; padding: 8px; font-size: 0.75rem; background: linear-gradient(135deg, #7c3aed, #4f46e5); text-decoration: none;">Comenzar con Pro</a>
          <?php endif; ?>
        </div>

        <!-- Agencia -->
        <div class="plan-upgrade-card <?= ($userPlan === 'agency') ? 'current-active' : '' ?>" style="background: rgba(245,158,11,0.04); border: 1px solid <?= ($userPlan === 'agency') ? '#f59e0b' : 'rgba(245,158,11,0.3)' ?>; border-radius: 14px; padding: 16px;">
          <div style="font-size: 0.72rem; font-weight: 800; color: #fbbf24; text-transform: uppercase;">Escala</div>
          <h5 style="font-size: 1rem; font-weight: 800; color: #fff; margin: 4px 0;">Plan Agencia</h5>
          <div style="font-size: 1.3rem; font-weight: 900; color: #fbbf24; margin: 8px 0 12px 0;">79.99 € <span style="font-size: 0.72rem; color: #94a3b8; font-weight: 600;">/ mes</span></div>
          <ul style="font-size: 0.76rem; color: #cbd5e1; list-style: none; padding: 0; margin: 0 0 16px 0; line-height: 1.8;">
            <li>✔ <strong>Hasta 20 cuentas conectadas</strong></li>
            <li>✔ 2.000.000 tokens / mes</li>
            <li>✔ Hasta 20 marcas multi-tenant</li>
          </ul>
          <?php if ($userPlan === 'agency'): ?>
            <span style="display: block; text-align: center; padding: 7px; font-size: 0.75rem; font-weight: 800; color: #fbbf24; background: rgba(245,158,11,0.1); border-radius: 8px;">Tu Plan Actual</span>
          <?php else: ?>
            <a href="https://wa.me/?text=<?= urlencode('Hola, quiero actualizar mi cuenta al Plan Agencia (20 cuentas)') ?>" target="_blank" class="btn-primary-action" style="display: block; text-align: center; padding: 8px; font-size: 0.75rem; background: #d97706; text-decoration: none;">Acceso Agencia</a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Modal: Atenea Studio - Estrategia Creativa & Filosofía para Fortaleza Imparable -->
<div class="modal-overlay" id="modal-recreate-fortaleza" onclick="if(event.target===this) RadarController.closeRecreateModal()">
  <div class="modal-box" style="max-width: 1100px; background: #0c101b; border: 1px solid rgba(139,92,246,0.35); box-shadow: 0 25px 70px rgba(0,0,0,0.9);">
    <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 14px;">
      <div>
        <h4 style="font-size: 1.15rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; margin: 0;">
          <span>🏛️</span> Atenea Studio — Directora de Estrategia Creativa (@fortaleza_imparable)
        </h4>
        <span style="font-size: 0.78rem; color: var(--text-muted);">
          Deconstrucción de ADN Psicológico + 4 Variantes Originales (Corto, Profundo, Guerrero, Estoico) + Dirección Visual Midjourney
        </span>
      </div>
      <button type="button" class="btn-close-modal" onclick="RadarController.closeRecreateModal()">&times;</button>
    </div>

    <div class="modal-body" id="recreate-modal-content" style="padding: 18px 0 6px 0;">
      <!-- Dynamic content rendered by RadarController.renderRecreationStudio() -->
    </div>
  </div>
</div>

<!-- Modal: Monitorear Nuevo Creador -->
<div class="modal-overlay" id="modal-add-creator" onclick="if(event.target===this) App.closeModal('modal-add-creator')">
  <div class="modal-box" style="max-width: 480px;">
    <div class="modal-header">
      <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 6px; margin: 0;">
        <span>➕</span> Monitorear Nuevo Creador de Nicho
      </h4>
      <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-add-creator')">&times;</button>
    </div>

    <form class="modal-body" onsubmit="RadarController.submitAddCreator(event)" style="padding: 18px 0 6px 0; display: flex; flex-direction: column; gap: 14px;">
      <div>
        <label style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 5px;">Plataforma</label>
        <select id="new-creator-platform" class="modal-control-select" style="width: 100%;">
          <option value="instagram" selected>📸 Instagram (Recomendado: Extracción de métricas y auto-sync con Meta API)</option>
          <option value="facebook">📘 Facebook (Solo importación manual - sin API de métricas)</option>
        </select>
        <span style="display: block; font-size: 0.73rem; color: #94a3b8; margin-top: 5px; line-height: 1.4;">
          💡 <strong>Recomendación:</strong> Usa Instagram para obtener automáticamente posts, likes, comentarios y frases para inspiración. Meta no permite extraer métricas de perfiles de terceros en Facebook.
        </span>
      </div>

      <div>
        <label style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 5px;">Usuario o Enlace del Perfil</label>
        <input type="text" id="new-creator-username" class="modal-control-input" placeholder="ej. gloriaestoica o https://instagram.com/..." required />
      </div>

      <div>
        <label style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 5px;">Nombre de Referencia (Opcional)</label>
        <input type="text" id="new-creator-display-name" class="modal-control-input" placeholder="ej. Gloria Estoica" />
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
        <button type="button" class="btn-secondary-action" onclick="App.closeModal('modal-add-creator')">Cancelar</button>
        <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #7c3aed, #4f46e5);">Agregar Creador</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Formular Nueva Predicción Falsable (Fase 5) -->
<div class="modal-overlay" id="modal-new-prediction" style="display: none;">
  <div class="modal-box" style="max-width: 600px; background: #0f172a; border: 1px solid rgba(139, 92, 246, 0.35); border-radius: 16px; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; margin-bottom: 16px;">
      <h4 style="font-size: 1.1rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
        <span>🔬</span> Formular Predicción Falsable (Previa a Publicación)
      </h4>
      <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-new-prediction')">&times;</button>
    </div>

    <form onsubmit="AteneaLearningController.submitNewPrediction(event)" style="display: flex; flex-direction: column; gap: 14px;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div>
          <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Plataforma de Publicación</label>
          <select id="pred-input-platform" class="modal-control-select" style="width: 100%;">
            <option value="instagram">📸 Instagram (@fortaleza_imparable)</option>
            <option value="facebook">📘 Facebook (Página Conectada)</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Candidato a Experimentación / Familia</label>
          <select id="pred-input-family" class="modal-control-select" style="width: 100%;">
            <option value="FAMILY_WARRIOR_ETHOS">Vencedor Solitario / Bushido (RECENT_EMERGING)</option>
            <option value="FAMILY_LONG_FORM">Reflexión Extensa / Párrafo > 35 pal. (OBSERVATION)</option>
            <option value="FAMILY_PROVOCATION">Provocación a la Complacencia (RECENT_EMERGING)</option>
            <option value="FAMILY_RHETORICAL_QUESTION">Interrogación con Revelación (OBSERVATION)</option>
            <option value="FAMILY_CONTROL_SOVEREIGNTY">Dicotomía del Control (OBSERVATION)</option>
            <option value="FAMILY_SHORT_FORM">Frase Ultracorta (ANTI-PATRÓN para prueba negativa)</option>
            <option value="FAMILY_NOVEL">Exploración Novedosa (NOVEL_EXPLORATION)</option>
          </select>
        </div>
      </div>

      <div>
        <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Promesa Falsable (Enunciado de la Predicción)</label>
        <textarea id="pred-input-statement" class="modal-control-input" rows="2" style="width: 100%; resize: vertical;" placeholder="ej. Si publicamos esta narrativa con estructura condicional, esperamos observar concentración en el quintil superior TOP 20." required></textarea>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div>
          <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Expectativa Ordinal (Tier)</label>
          <select id="pred-input-tier" class="modal-control-select" style="width: 100%;">
            <option value="TOP20">TOP 20 (Quintil Superior)</option>
            <option value="MIDDLE">MIDDLE (Promedio)</option>
            <option value="BOTTOM20">BOTTOM 20 (Prueba de Anti-Patrón)</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Nivel de Evidencia Origen</label>
          <select id="pred-input-evidence-type" class="modal-control-select" style="width: 100%;">
            <option value="RECENT_EMERGING">RECENT_EMERGING (Señal Reciente)</option>
            <option value="OBSERVATIONAL">OBSERVATIONAL (Observación Preliminar)</option>
            <option value="NOVEL_EXPLORATION">NOVEL_EXPLORATION (Hipótesis Nueva)</option>
          </select>
        </div>
      </div>

      <div>
        <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Concepto Nuclear o Frase Diseñada</label>
        <textarea id="pred-input-concept" class="modal-control-input" rows="2" style="width: 100%; resize: vertical;" placeholder="ej. Quien teme caminar solo termina marchando al paso de quienes nunca llegaron a ningún lado." required></textarea>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
        <button type="button" class="btn-secondary-action" onclick="App.closeModal('modal-new-prediction')">Cancelar</button>
        <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #7c3aed, #4f46e5); font-weight: 700;">Registrar Predicción Falsable</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Evaluar Predicción con Resultado Observado (Fase 5) -->
<div class="modal-overlay" id="modal-evaluate-prediction" style="display: none;">
  <div class="modal-box" style="max-width: 520px; background: #0f172a; border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 16px; padding: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; margin-bottom: 16px;">
      <h4 style="font-size: 1.1rem; font-weight: 800; color: #fff; margin: 0; display: flex; align-items: center; gap: 8px;">
        <span>📊</span> Evaluar Predicción vs Resultado Real
      </h4>
      <button type="button" class="btn-close-modal" onclick="App.closeModal('modal-evaluate-prediction')">&times;</button>
    </div>

    <form onsubmit="AteneaLearningController.submitEvaluatePrediction(event)" style="display: flex; flex-direction: column; gap: 14px;">
      <input type="hidden" id="eval-pred-id" value="" />
      
      <div style="background: rgba(255,255,255,0.04); border-radius: 8px; padding: 10px 12px; font-size: 0.8rem; color: #cbd5e1;">
        <div style="font-weight: 700; color: #a5b4fc; margin-bottom: 3px;" id="eval-pred-statement-label">Promesa Falsable:</div>
        <div style="color: #94a3b8; font-size: 0.76rem;" id="eval-pred-expected-label">Tier Esperado: TOP20</div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div>
          <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Tier Observado Real</label>
          <select id="eval-actual-tier" class="modal-control-select" style="width: 100%;">
            <option value="TOP20">TOP20 (Quintil Superior)</option>
            <option value="MIDDLE">MIDDLE (Rango Intermedio)</option>
            <option value="BOTTOM20">BOTTOM20 (Quintil Inferior)</option>
          </select>
        </div>
        <div>
          <label style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: block; margin-bottom: 4px;">Madurez del Post al Evaluar</label>
          <select id="eval-maturity-status" class="modal-control-select" style="width: 100%;">
            <option value="MATURE">MADURO (Tracción estabilizada para contrastar)</option>
            <option value="IMMATURE">INMADURO (&lt; 24h, clasificar INCONCLUSIVE)</option>
            <option value="UNAVAILABLE">NO DISPONIBLE (Sin métricas)</option>
          </select>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
        <div>
          <label style="font-size: 0.74rem; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 3px;">Likes</label>
          <input type="number" id="eval-actual-likes" class="modal-control-input" placeholder="Opcional" />
        </div>
        <div>
          <label style="font-size: 0.74rem; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 3px;">Shares</label>
          <input type="number" id="eval-actual-shares" class="modal-control-input" placeholder="Opcional" />
        </div>
        <div>
          <label style="font-size: 0.74rem; font-weight: 600; color: #94a3b8; display: block; margin-bottom: 3px;">Alcance / Reach</label>
          <input type="number" id="eval-actual-reach" class="modal-control-input" placeholder="Opcional" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 8px;">
        <button type="button" class="btn-secondary-action" onclick="App.closeModal('modal-evaluate-prediction')">Cancelar</button>
        <button type="submit" class="btn-primary-action" style="background: linear-gradient(135deg, #10b981, #059669); font-weight: 700;">Evaluar y Asentar en Ledger</button>
      </div>
    </form>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Scripts (Cache Busted) -->
<script src="assets/js/agent-controller.js?v=<?= time() ?>"></script>
<script src="assets/js/analytics.js?v=<?= time() ?>"></script>
<script src="assets/js/planner.js?v=<?= time() ?>"></script>
<script src="assets/js/trends.js?v=<?= time() ?>"></script>
<script src="assets/js/radar.js?v=<?= time() ?>"></script>
<script src="assets/js/atenea_learning.js?v=<?= time() ?>"></script>
<script src="assets/js/app.js?v=<?= time() ?>"></script>

</body>
</html>
