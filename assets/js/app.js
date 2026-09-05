/**
 * Main Application Orchestrator (Hardened with Anti-CSRF, DOM XSS Prevention, Mobile Responsive & Bottom Nav)
 */

const App = {
  activeTab: 'inbox',
  activePlatform: 'all',
  activeAccountId: 'all',
  activeFilter: 'all',
  activePostId: null,
  viewDensity: localStorage.getItem('preferred_view_density') || 'cards',
  searchQuery: '',
  selectedCommentId: null,
  commentsList: [],
  connectedAccounts: [],
  currentPage: 1,
  pageSize: 6,

  // Brand Voice Studio State
  keyPhrases: ['Dicotomía del control', 'Amor Fati', 'Memento Mori', 'Autodominio', 'Fortaleza mental', 'Disciplina diaria'],
  forbiddenPhrases: ['Estimado cliente', 'Compra ya', 'Oferta imperdible', 'Somos un bot', 'Haz clic aquí'],
  fewShotExamples: [],

  // Read CSRF Token from meta tag
  getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
  },

  // Centralized secure fetch wrapper with CSRF injection
  async fetchWithCsrf(url, options = {}) {
    const opts = { ...options };
    opts.headers = {
      ...(opts.headers || {}),
      'X-CSRF-Token': this.getCsrfToken()
    };
    if (opts.method && opts.method.toUpperCase() !== 'GET' && !opts.headers['Content-Type']) {
      opts.headers['Content-Type'] = 'application/json';
    }
    return fetch(url, opts);
  },

  async init() {
    this.bindEvents();
    this.initViewDensity();

    // 1. Detect and restore active tab from URL hash or storage immediately on page load / F5
    const hash = window.location.hash ? window.location.hash.replace('#', '').trim() : '';
    let savedTab = hash || sessionStorage.getItem('xindro_active_tab') || localStorage.getItem('xindro_active_tab') || 'inbox';
    const validTabs = ['inbox', 'highlights', 'leads', 'urgent', 'spam', 'analytics', 'settings', 'meta'];
    const tabToRestore = validTabs.includes(savedTab) ? savedTab : 'inbox';
    this.switchTab(tabToRestore, false);

    // 2. Listen for URL hash changes (browser back/forward navigation)
    window.addEventListener('hashchange', () => {
      const currentHash = window.location.hash.replace('#', '').trim();
      if (currentHash && validTabs.includes(currentHash) && currentHash !== this.activeTab) {
        this.switchTab(currentHash, false);
      }
    });

    try { await this.loadBrands(); } catch (e) { console.error('loadBrands error:', e); }
    try { await this.loadConnectedAccounts(); } catch (e) { console.error('loadConnectedAccounts error:', e); }
    try { await this.loadSettings(); } catch (e) { console.error('loadSettings error:', e); }
    try { await this.loadComments(); } catch (e) { console.error('loadComments error:', e); }
    this.renderTagChips();
    this.renderFewShotExamples();
  },

  // Connected Accounts & Multi-Brand Voice Routing Manager
  async loadConnectedAccounts() {
    try {
      const res = await this.fetchWithCsrf('api/settings.php?action=list_accounts');
      const data = await res.json();
      if (data && data.success) {
        this.connectedAccounts = data.accounts || [];
        const brands = data.brands || [];

        // 1. Populate topbar account filter dropdown
        const topbarAccountSelect = document.getElementById('topbar-account-select');
        if (topbarAccountSelect) {
          const currentVal = this.activeAccountId;
          let opts = `<option value="all">🌐 Todas las Cuentas (${this.connectedAccounts.length})</option>`;
          this.connectedAccounts.forEach(a => {
            const icon = a.platform === 'instagram' ? '📸' : '📘';
            const handleText = a.account_handle ? ` (${a.account_handle})` : '';
            opts += `<option value="${a.id}" ${a.id == currentVal ? 'selected' : ''}>${icon} ${this.escapeHtml(a.account_name)}${this.escapeHtml(handleText)}</option>`;
          });
          topbarAccountSelect.innerHTML = opts;
        }

        // 2. Update badge count
        const badgeCount = document.getElementById('badge-total-connected-accounts');
        if (badgeCount) {
          badgeCount.textContent = `${this.connectedAccounts.length} cuenta${this.connectedAccounts.length === 1 ? '' : 's'}`;
        }

        // 3. Update sidebar connection status pill (ON / OFF)
        this.updateSidebarConnectionStatus();

        // 4. Render accounts manager cards in settings/meta
        this.renderAccountsManager(this.connectedAccounts, brands);
      }
    } catch (err) {
      console.error('Error loading connected accounts:', err);
      const container = document.getElementById('connected-accounts-list');
      if (container) {
        container.innerHTML = `
          <div style="padding: 20px; text-align: center; color: var(--accent-rose); font-size: 0.85rem;">
            ⚠️ No se pudieron cargar las cuentas vinculadas.
            <button type="button" class="btn-secondary-mini" onclick="App.loadConnectedAccounts()" style="margin-left: 8px;">Reintentar</button>
          </div>
        `;
      }
    }
  },

  updateSidebarConnectionStatus() {
    const statusPill = document.getElementById('sidebar-connection-status-pill');
    if (!statusPill) return;
    const isConnected = Array.isArray(this.connectedAccounts) && this.connectedAccounts.length > 0;
    if (isConnected) {
      statusPill.className = 'connection-status-badge on';
      statusPill.innerHTML = '<span class="status-dot"></span><span class="status-label">ON</span>';
      statusPill.title = `${this.connectedAccounts.length} cuenta${this.connectedAccounts.length === 1 ? '' : 's'} vinculada${this.connectedAccounts.length === 1 ? '' : 's'}`;
    } else {
      statusPill.className = 'connection-status-badge off';
      statusPill.innerHTML = '<span class="status-dot"></span><span class="status-label">OFF</span>';
      statusPill.title = 'Sin cuentas vinculadas (Desconectado)';
    }
  },

  renderAccountsManager(accounts, brands) {
    const container = document.getElementById('connected-accounts-list');
    if (!container) return;

    if (!accounts || accounts.length === 0) {
      container.innerHTML = `
        <div style="padding: 28px 16px; text-align: center; color: var(--text-dim); font-size: 0.84rem;">
          <div style="font-size: 2.2rem; margin-bottom: 8px;">📱</div>
          <strong style="color: #fff; display: block; margin-bottom: 4px; font-size: 0.95rem;">No hay cuentas de Meta vinculadas todavía</strong>
          <p style="margin: 0; max-width: 420px; margin: 0 auto; line-height: 1.5;">
            Haz clic en <strong>"Continuar con Facebook & Instagram"</strong> arriba para conectar automáticamente tus Páginas y perfiles.
          </p>
        </div>
      `;
      return;
    }

    try {
      container.innerHTML = accounts.map(a => {
        const isIg = a.platform === 'instagram';
        const icon = isIg ? '📸' : '📘';
        const defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(a.account_name || 'Account')}&background=${isIg ? 'e1306c' : '1877f2'}&color=fff`;
        const safeAvatar = this.sanitizeUrl(a.avatar_url, defaultAvatar);

        const brandOptionsHtml = (brands || []).map(b => `
          <option value="${b.id}" ${b.id == a.brand_voice_id ? 'selected' : ''}>
            ${this.escapeHtml(b.brand_name)} (${this.escapeHtml(b.tone_level || 'General')})
          </option>
        `).join('');

        return `
          <div class="account-item-card" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle); border-radius: var(--radius-md); padding: 18px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 14px; min-width: 240px;">
              <div style="position: relative;">
                <img src="${safeAvatar}" alt="avatar" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid ${isIg ? '#e1306c' : '#1877f2'};" onerror="this.onerror=null; this.src='${defaultAvatar}';" />
                <span style="position: absolute; bottom: -2px; right: -2px; font-size: 0.8rem; background: #0f172a; border-radius: 50%; padding: 2px;">${icon}</span>
              </div>
              <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                  <strong style="color: #fff; font-size: 0.95rem;">${this.escapeHtml(a.account_name)}</strong>
                  <span class="platform-badge-mini ${isIg ? 'instagram' : 'facebook'}">${isIg ? 'IG' : 'FB'}</span>
                </div>
                <div style="font-size: 0.8rem; color: var(--text-dim); margin-top: 2px;">
                  ${this.escapeHtml(a.account_handle || '')} • 📊 ${parseInt(a.posts_count || 0, 10)} posts • 💬 ${parseInt(a.comments_count || 0, 10)} comentarios
                </div>
              </div>
            </div>

            <!-- Brand Voice Selector & Disconnect Action -->
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
              <div style="display: flex; flex-direction: column; gap: 4px;">
                <label style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: var(--accent-cyan); letter-spacing: 0.04em;">
                  🎭 Voz de Marca Asignada:
                </label>
                <select class="account-brand-voice-select" style="background: #0f172a; border: 1px solid var(--border-active); color: #fff; padding: 8px 12px; border-radius: var(--radius-sm); font-size: 0.84rem; font-weight: 700; cursor: pointer; min-width: 220px;" onchange="App.assignAccountBrandVoice(${a.id}, this.value)">
                  ${brandOptionsHtml}
                </select>
              </div>
              
              <div style="display: flex; align-items: center; gap: 8px; align-self: flex-end;">
                <span style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.15); color: #34d399; font-weight: 700; padding: 7px 11px; border-radius: 6px; border: 1px solid rgba(16, 185, 129, 0.3);">
                  🟢 Conectada
                </span>
                <button type="button" class="btn-disconnect-account" onclick="App.disconnectAccount(${a.id})" title="Desconectar y remover esta cuenta de Meta">
                  🔌 Desconectar
                </button>
              </div>
            </div>
          </div>
        `;
      }).join('');
    } catch (renderErr) {
      console.error('Error rendering accounts manager:', renderErr);
      container.innerHTML = `
        <div style="padding: 20px; text-align: center; color: var(--accent-rose); font-size: 0.85rem;">
          ⚠️ Error al mostrar las cuentas vinculadas.
        </div>
      `;
    }
  },

  async disconnectAccount(accountId, accountName) {
    if (!accountId) return;
    const target = (this.connectedAccounts || []).find(x => x.id == accountId);
    const name = accountName || (target ? target.account_name : `cuenta #${accountId}`);
    if (!confirm(`¿Estás seguro de que deseas desconectar la cuenta "${name}"?\n\nAl desconectarla, ya no aparecerá como conectada en tu panel ni se sincronizarán sus publicaciones.`)) {
      return;
    }
    try {
      const res = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'disconnect_account',
          account_id: parseInt(accountId, 10)
        })
      });
      const data = await res.json();
      if (data.success) {
        this.showToast(data.message || `Cuenta "${name}" desconectada exitosamente.`, 'success');
        if (this.activeAccountId == accountId) {
          this.activeAccountId = 'all';
        }
        await this.loadConnectedAccounts();
        await this.loadComments();
      } else {
        this.showToast(`Error: ${data.error || 'No se pudo desconectar la cuenta.'}`, 'error');
      }
    } catch (err) {
      console.error('Error disconnecting account:', err);
      this.showToast('Error de conexión al desconectar la cuenta.', 'error');
    }
  },

  async assignAccountBrandVoice(accountId, brandVoiceId) {
    if (!accountId || !brandVoiceId) return;
    try {
      const res = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'assign_account_brand',
          account_id: parseInt(accountId, 10),
          brand_voice_id: parseInt(brandVoiceId, 10)
        })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast(data.message || 'Voz de Marca asignada a la cuenta.', 'success');
        await this.loadConnectedAccounts();
        await this.loadComments();
      } else {
        App.showToast(`Error: ${data.error || 'No se pudo asignar la voz'}`, 'error');
      }
    } catch (err) {
      console.error('Error assigning brand voice to account:', err);
      App.showToast('Error de conexión al asignar voz a la cuenta.', 'error');
    }
  },

  filterByAccount(accountId) {
    this.activeAccountId = accountId;
    this.currentPage = 1;
    this.loadComments();
    const selectedAcc = this.connectedAccounts.find(a => a.id == accountId);
    if (selectedAcc) {
      this.showToast(`Filtrando por cuenta: ${selectedAcc.account_name} (${selectedAcc.account_handle || ''})`, 'success');
    } else {
      this.showToast('Mostrando comentarios de todas las cuentas.', 'success');
    }
  },

  // Agency & Multi-Brand Voice Management
  async loadBrands() {
    try {
      const res = await this.fetchWithCsrf('api/settings.php?action=list_brands');
      const data = await res.json();
      if (data.success && Array.isArray(data.brands) && data.brands.length > 0) {
        const topbarSelect = document.getElementById('topbar-brand-select');
        const settingsSelect = document.getElementById('settings-brand-voice-selector');
        const activeId = data.active_brand_id;

        const optionsHtml = data.brands.map(b => `
          <option value="${b.id}" ${b.id == activeId ? 'selected' : ''}>
            ${this.escapeHtml(b.brand_name)} (${this.escapeHtml(b.industry || 'General')})
          </option>
        `).join('');

        if (topbarSelect) topbarSelect.innerHTML = optionsHtml;
        if (settingsSelect) settingsSelect.innerHTML = optionsHtml;
      }
    } catch (err) {
      console.error('Error loading brand list:', err);
    }
  },

  async switchActiveBrand(brandId) {
    if (!brandId) return;
    try {
      const res = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'set_active_brand',
          brand_id: parseInt(brandId, 10)
        })
      });
      const data = await res.json();
      if (data.success) {
        // Sync both selectors
        const topbarSelect = document.getElementById('topbar-brand-select');
        const settingsSelect = document.getElementById('settings-brand-voice-selector');
        if (topbarSelect) topbarSelect.value = brandId;
        if (settingsSelect) settingsSelect.value = brandId;

        App.showToast(`🏢 Espacio cambiado a: ${data.brand?.brand_name || 'Marca Activa'}`, 'success');
        
        // Reload workspace with brand context
        await this.loadBrandVoiceDetails(brandId);
        await this.loadComments();
        if (typeof Analytics !== 'undefined' && Analytics.loadSummary) {
          Analytics.loadSummary();
        }
      } else {
        App.showToast(data.error || 'No se pudo cambiar de marca', 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error de conexión al cambiar de marca', 'error');
    }
  },

  openNewBrandModal() {
    this.openModal('modal-new-brand');
  },

  async submitCreateNewBrand(e) {
    if (e) e.preventDefault();
    const name = document.getElementById('new-brand-name')?.value.trim();
    const persona = document.getElementById('new-brand-persona')?.value.trim() || 'Asistente de Marca';
    const industry = document.getElementById('new-brand-industry')?.value.trim() || 'Comercio & Creadores';
    const language = document.getElementById('new-brand-language')?.value || 'es';
    const tone = document.getElementById('new-brand-tone')?.value || 'commercial_sales';
    const prompt = document.getElementById('new-brand-prompt')?.value.trim() || 'Asistente oficial de la marca.';

    if (!name) {
      App.showToast('Por favor introduce el nombre de la marca o cliente.', 'error');
      return;
    }

    try {
      const res = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'save_brand',
          brand_name: name,
          persona_name: persona,
          industry: industry,
          language: language,
          tone_level: tone,
          system_prompt: prompt
        })
      });
      const data = await res.json();
      if (data.success) {
        this.closeModal('modal-new-brand');
        document.getElementById('new-brand-name').value = '';
        document.getElementById('new-brand-persona').value = '';
        document.getElementById('new-brand-industry').value = '';
        document.getElementById('new-brand-prompt').value = '';

        App.showToast(`¡Cliente "${name}" creado exitosamente! 🚀`, 'success');
        await this.loadBrands();
        if (data.brand_id) {
          await this.switchActiveBrand(data.brand_id);
        }
      } else {
        App.showToast(data.error || 'Error al crear la marca', 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error de conexión', 'error');
    }
  },

  async loadBrandVoiceDetails(brandId) {
    try {
      const res = await this.fetchWithCsrf(`api/settings.php?action=get_brand&id=${brandId}`);
      const data = await res.json();
      if (data.success && data.brand) {
        const b = data.brand;
        this.setInputValue('setting-brand-id', b.id);
        this.setInputValue('setting-brand-name', b.brand_name);
        this.setInputValue('setting-persona-name', b.persona_name);
        this.setInputValue('setting-brand-industry', b.industry);
        this.setInputValue('setting-brand-language', b.language || 'es');
        this.setInputValue('setting-brand-tone', b.tone_level || 'friendly_engaging');
        this.setInputValue('setting-brand-desc', b.system_prompt);

        if (b.warmth_level !== undefined) this.updateSliderVal('warmth', b.warmth_level);
        if (b.depth_level !== undefined) this.updateSliderVal('depth', b.depth_level);
        if (b.energy_level !== undefined) this.updateSliderVal('energy', b.energy_level);
        this.setInputValue('setting-closing-rule', b.closing_question_rule || 'always');
        this.setInputValue('setting-emoji-style', b.emoji_style || 'moderate');

        this.keyPhrases = Array.isArray(b.key_phrases) ? b.key_phrases : [];
        this.forbiddenPhrases = Array.isArray(b.forbidden_phrases) ? b.forbidden_phrases : [];
        this.fewShotExamples = Array.isArray(b.few_shot_examples) ? b.few_shot_examples : [];

        this.renderTagChips();
        this.renderFewShotExamples();
      }
    } catch (err) {
      console.error(err);
    }
  },

  initViewDensity() {
    const stream = document.getElementById('comments-stream');
    const btnCards = document.getElementById('btn-density-cards');
    const btnCompact = document.getElementById('btn-density-compact');

    if (this.viewDensity === 'compact') {
      if (stream) stream.classList.add('compact-mode');
      if (btnCards) btnCards.classList.remove('active');
      if (btnCompact) btnCompact.classList.add('active');
    } else {
      if (stream) stream.classList.remove('compact-mode');
      if (btnCards) btnCards.classList.add('active');
      if (btnCompact) btnCompact.classList.remove('active');
    }
  },

  toggleViewDensity(mode) {
    this.viewDensity = mode;
    localStorage.setItem('preferred_view_density', mode);
    this.initViewDensity();
    this.renderComments(this.commentsList);
  },

  // Mobile Drawer Controls
  toggleMobileSidebar(force) {
    const sidebar = document.getElementById('app-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (!sidebar || !backdrop) return;
    const shouldOpen = typeof force === 'boolean' ? force : !sidebar.classList.contains('mobile-open');
    sidebar.classList.toggle('mobile-open', shouldOpen);
    backdrop.classList.toggle('active', shouldOpen);
  },

  bindEvents() {
    // Navigation items
    document.querySelectorAll('.sidebar-nav .nav-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const tab = btn.dataset.tab;
        if (tab) this.switchTab(tab);
      });
    });

    // Platform switcher pills in header
    document.querySelectorAll('.app-topbar .platform-pill').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.app-topbar .platform-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        this.activePlatform = btn.dataset.platform;
        this.loadComments();
      });
    });

    // Filter tags in feed
    document.querySelectorAll('.filter-tag').forEach(tag => {
      tag.addEventListener('click', () => {
        document.querySelectorAll('.filter-tag').forEach(t => t.classList.remove('active'));
        tag.classList.add('active');
        this.activeFilter = tag.dataset.filter;
        this.loadComments();
      });
    });

    // Search input debounce
    const searchInput = document.getElementById('feed-search-input');
    if (searchInput) {
      let timeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          this.searchQuery = e.target.value.trim();
          this.loadComments();
        }, 300);
      });
    }

    // Autopilot master toggle in sidebar
    const autopilotCheckbox = document.getElementById('autopilot-sidebar-toggle');
    if (autopilotCheckbox) {
      autopilotCheckbox.addEventListener('change', async (e) => {
        const isChecked = e.target.checked ? '1' : '0';
        try {
          const res = await this.fetchWithCsrf('api/settings.php', {
            method: 'POST',
            body: JSON.stringify({ autopilot_enabled: isChecked })
          });
          const data = await res.json();
          if (data.success) {
            App.showToast(isChecked === '1' ? '🤖 Piloto Automático activado' : 'Piloto Automático desactivado', 'success');
          } else {
            App.showToast(data.error || 'Error al cambiar piloto automático', 'error');
          }
        } catch (err) {
          App.showToast('Error de conexión', 'error');
        }
      });
    }

    // Tone select change
    const toneSelect = document.getElementById('select-tone');
    if (toneSelect) {
      toneSelect.addEventListener('change', () => {
        if (AgentController.activeComment) {
          AgentController.loadSuggestions(AgentController.activeComment, toneSelect.value);
        }
      });
    }

    // New Brand button listener in topbar
    document.querySelectorAll('.btn-new-brand-pill, [data-action="new-brand"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        this.openNewBrandModal();
      });
    });

    // Escape key listener for closing modals
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(modal => {
          modal.classList.remove('active');
        });
      }
    });
  },

  switchTab(tab, updateHistory = true) {
    const validTabs = ['inbox', 'highlights', 'leads', 'urgent', 'spam', 'analytics', 'settings', 'meta'];
    const activeTab = validTabs.includes(tab) ? tab : 'inbox';
    this.activeTab = activeTab;
    
    // 1. Persist active tab across browser reloads (F5) and sessions
    try {
      sessionStorage.setItem('xindro_active_tab', activeTab);
      localStorage.setItem('xindro_active_tab', activeTab);
    } catch (e) {}

    // 2. Synchronize URL hash without causing viewport jumping
    if (updateHistory !== false) {
      if (window.history && window.history.replaceState) {
        window.history.replaceState({ tab: activeTab }, '', '#' + activeTab);
      } else {
        window.location.hash = activeTab;
      }
    }

    // 3. Sync desktop sidebar navigation active state
    document.querySelectorAll('.sidebar-nav .nav-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === activeTab);
    });

    // 4. Sync mobile bottom navigation bar active state
    document.querySelectorAll('.mobile-bottom-nav .bottom-nav-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.tab === activeTab);
    });

    this.toggleMobileSidebar(false);

    // 5. Show/hide view containers seamlessly
    const mainFeedView = document.getElementById('view-feed-workspace');
    const settingsView = document.getElementById('view-settings');
    const analyticsView = document.getElementById('view-analytics');
    const metaView = document.getElementById('view-meta');

    if (mainFeedView) mainFeedView.style.display = (activeTab === 'inbox' || activeTab === 'highlights' || activeTab === 'leads' || activeTab === 'urgent' || activeTab === 'spam') ? 'flex' : 'none';
    if (settingsView) settingsView.style.display = (activeTab === 'settings') ? 'block' : 'none';
    if (analyticsView) analyticsView.style.display = (activeTab === 'analytics') ? 'block' : 'none';
    if (metaView) metaView.style.display = (activeTab === 'meta') ? 'block' : 'none';

    // 6. Synchronize topbar page title dynamically
    const topbarTitle = document.getElementById('topbar-page-title');
    if (topbarTitle) {
      switch (activeTab) {
        case 'analytics':
          topbarTitle.textContent = 'Métricas de Audiencia & Meta Graph API';
          break;
        case 'settings':
          topbarTitle.textContent = 'Estudio de Voz de Marca & Prompt Dinámico';
          break;
        case 'meta':
          topbarTitle.textContent = 'Configuración de Meta Graph API & Webhooks';
          break;
        case 'highlights':
          topbarTitle.textContent = 'Comentarios Más Resaltantes';
          break;
        case 'leads':
          topbarTitle.textContent = 'Leads & Consultas de Precios';
          break;
        case 'urgent':
          topbarTitle.textContent = 'Objeciones & Soporte Prioritario';
          break;
        case 'spam':
          topbarTitle.textContent = 'Filtro Anti-Spam & Moderación';
          break;
        default:
          topbarTitle.textContent = 'Gestor de Comunidad & Conversión';
          break;
      }
    }

    // 7. Route and initialize view-specific behaviors
    if (activeTab === 'highlights') {
      this.setFilterTag('highlighted');
    } else if (activeTab === 'leads') {
      this.setFilterTag('leads');
    } else if (activeTab === 'urgent') {
      this.setFilterTag('urgent');
    } else if (activeTab === 'spam') {
      this.setFilterTag('spam');
    } else if (activeTab === 'inbox') {
      this.setFilterTag('all');
    } else if (activeTab === 'analytics') {
      if (typeof AnalyticsController !== 'undefined') {
        const savedSubtab = sessionStorage.getItem('xindro_analytics_subtab') || localStorage.getItem('xindro_analytics_subtab') || 'overview';
        AnalyticsController.switchSubtab(savedSubtab);
      }
    } else if (activeTab === 'meta') {
      this.loadConnectedAccounts();
    }
  },

  setFilterTag(filterName) {
    this.activeFilter = filterName;
    document.querySelectorAll('.filter-tag').forEach(t => {
      t.classList.toggle('active', t.dataset.filter === filterName);
    });
    this.loadComments();
  },

  jumpToPostComments(postId) {
    this.activePostId = postId;
    this.activeFilter = 'all';
    this.currentPage = 1;
    this.switchTab('inbox');
    this.loadComments();
    this.showToast(`Filtrando comentarios para la publicación #${postId}`, 'success');
  },

  clearPostFilter() {
    this.activePostId = null;
    this.currentPage = 1;
    const banner = document.getElementById('active-post-banner');
    if (banner) banner.style.display = 'none';
    this.loadComments();
    this.showToast('Filtro de publicación eliminado. Viendo todos los comentarios.', 'success');
  },

  async loadComments() {
    const listContainer = document.getElementById('comments-stream');
    if (!listContainer) return;

    try {
      let url = `api/comments.php?platform=${encodeURIComponent(this.activePlatform)}&filter=${encodeURIComponent(this.activeFilter)}&search=${encodeURIComponent(this.searchQuery)}`;
      if (this.activeAccountId && this.activeAccountId !== 'all') {
        url += `&account_id=${encodeURIComponent(this.activeAccountId)}`;
      }
      if (this.activePostId) {
        url += `&post_id=${encodeURIComponent(this.activePostId)}`;
      }

      const response = await this.fetchWithCsrf(url);
      const res = await response.json();

      if (res.success) {
        this.commentsList = res.data || [];
        this.updateTopCounts(res.counts);
        this.updateActivePostBanner(this.commentsList);
        this.renderComments(this.commentsList);
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al cargar comentarios.', 'error');
    }
  },

  updateActivePostBanner(comments) {
    const banner = document.getElementById('active-post-banner');
    if (!banner) return;

    if (this.activePostId && comments.length > 0) {
      const first = comments[0];
      banner.style.display = 'flex';
      
      const thumb = document.getElementById('active-post-thumb');
      const caption = document.getElementById('active-post-caption');
      const badge = document.getElementById('active-post-platform-badge');
      const stats = document.getElementById('active-post-stats');

      if (thumb) thumb.src = this.sanitizeUrl(first.post_media_url, '');
      if (caption) caption.textContent = first.post_caption || `Publicación #${this.activePostId}`;
      if (badge) {
        badge.className = `platform-badge-mini ${first.post_platform === 'facebook' ? 'facebook' : 'instagram'}`;
        badge.textContent = first.post_platform === 'facebook' ? 'FB' : 'IG';
      }
      if (stats) {
        stats.textContent = `👁️ ${parseInt(first.post_reach || 0, 10).toLocaleString()} Alcance • 💬 ${parseInt(first.post_comments_count || comments.length, 10)} Comentarios`;
      }
    } else if (!this.activePostId) {
      banner.style.display = 'none';
    }
  },

  updateTopCounts(counts) {
    if (!counts) return;
    const leadsPill = document.getElementById('count-pill-leads');
    const urgentPill = document.getElementById('count-pill-urgent');
    const scorePill = document.getElementById('count-pill-highlighted');

    if (leadsPill) leadsPill.textContent = `${counts.leads_count || 0} Leads`;
    if (urgentPill) urgentPill.textContent = `${counts.urgent_count || 0} Soporte`;
    if (scorePill) scorePill.textContent = `${counts.highlighted_count || 0} Destacados`;

    // Sidebar badges
    const badgeInbox = document.getElementById('badge-count-inbox');
    const badgeHigh = document.getElementById('badge-count-highlights');
    const badgeLeads = document.getElementById('badge-count-leads');

    if (badgeInbox) badgeInbox.textContent = counts.pending_count || '0';
    if (badgeHigh) badgeHigh.textContent = counts.highlighted_count || '0';
    if (badgeLeads) badgeLeads.textContent = counts.leads_count || '0';
    
    const badgeSpam = document.getElementById('badge-count-spam');
    if (badgeSpam) badgeSpam.textContent = counts.spam_count || '0';
  },

  renderComments(comments) {
    const listContainer = document.getElementById('comments-stream');
    const counterDisplay = document.getElementById('feed-counter-display');
    if (!listContainer) return;

    const totalItems = comments.length;
    const totalPages = Math.ceil(totalItems / this.pageSize) || 1;
    if (this.currentPage > totalPages) this.currentPage = totalPages;
    if (this.currentPage < 1) this.currentPage = 1;

    const startIdx = (this.currentPage - 1) * this.pageSize;
    const endIdx = Math.min(startIdx + this.pageSize, totalItems);
    const pageComments = comments.slice(startIdx, endIdx);

    const pendingCount = comments.filter(c => c.status === 'pending').length;
    if (counterDisplay) {
      counterDisplay.textContent = `Mostrando ${totalItems === 0 ? 0 : startIdx + 1} - ${endIdx} de ${totalItems} comentarios (${pendingCount} pendientes)`;
    }

    if (comments.length === 0) {
      listContainer.innerHTML = `
        <div style="padding: 40px 20px; text-align: center; color: var(--text-dim);">
          <div style="font-size: 2.5rem; margin-bottom: 10px;">✨</div>
          <h4 style="font-size: 1rem; color: #fff; font-weight: 700;">No hay comentarios en este filtro</h4>
          <p style="font-size: 0.82rem; margin-top: 4px;">Todo está al día o prueba cambiando el filtro de búsqueda.</p>
          ${this.activePostId ? `
            <button class="btn-primary-action" style="margin: 14px auto 0; padding: 6px 12px; font-size: 0.78rem;" onclick="App.clearPostFilter()">
              Ver todas las publicaciones
            </button>
          ` : ''}
        </div>
      `;
      return;
    }

    const isCompact = this.viewDensity === 'compact';

    const cardsHtml = pageComments.map(c => {
      const isSpam = c.status === 'spam' || c.sentiment === 'spam';
      const isLead = c.sentiment === 'lead' || (c.intent && c.intent.startsWith('lead_'));
      const isUrgent = c.sentiment === 'urgent' || c.intent === 'support';
      const isHigh = c.is_highlighted == 1 || c.highlight_score >= 80;
      const isSelected = this.selectedCommentId === c.id;

      let cardClass = 'comment-card';
      if (isSelected) cardClass += ' selected';
      if (isSpam) cardClass += ' is-spam';
      else if (isLead) cardClass += ' is-lead';
      else if (isUrgent) cardClass += ' is-urgent';
      else if (isHigh) cardClass += ' is-highlight';

      let scoreClass = 'score-badge';
      if (isSpam) scoreClass += ' urgent';
      else if (isLead) scoreClass += ' lead';
      else if (isUrgent) scoreClass += ' urgent';
      else scoreClass += ' high';

      const safeAvatar = this.sanitizeUrl(c.author_avatar, 'https://ui-avatars.com/api/?name=User&background=6366f1&color=fff&size=96');
      const safePostImg = this.sanitizeUrl(c.post_media_url, 'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=160&h=160&fit=crop&auto=format&q=75');
      const safeScore = parseInt(c.highlight_score, 10) || 50;

      const accName = c.account_name || (c.platform === 'facebook' ? 'Página FB' : '@cuenta_ig');
      const voiceName = c.brand_voice_name || 'Voz por Defecto';

      if (isCompact) {
        // Streamlined Compact Row
        return `
          <div class="${cardClass}" onclick="App.selectCommentById(${parseInt(c.id, 10)}, true)">
            <div class="card-top">
              <div class="author-info">
                <img src="${safeAvatar}" class="author-avatar" alt="avatar" loading="lazy" decoding="async" width="36" height="36" />
                <div class="author-name">
                  ${this.escapeHtml(c.author_name)}
                  <span class="platform-badge-mini ${c.platform === 'facebook' ? 'facebook' : 'instagram'}">${c.platform === 'instagram' ? 'IG' : 'FB'}</span>
                  <span class="card-account-pill" title="Cuenta: ${this.escapeHtml(accName)}">📱 ${this.escapeHtml(accName)}</span>
                  <span class="card-origin-voice-pill" title="Voz de Marca: ${this.escapeHtml(voiceName)}">🎭 ${this.escapeHtml(voiceName)}</span>
                </div>
              </div>
              <div class="card-badges">
                <button type="button" class="btn-card-assistant" onclick="event.stopPropagation(); AgentController.openAssistantModal(${parseInt(c.id, 10)})" title="Abrir Asistente para responder">
                  <span class="assistant-btn-icon">🪄</span> Asistente
                </button>
                <div class="${scoreClass}" onclick="event.stopPropagation(); App.openScoreGuideModal()" style="cursor: pointer;" title="Haz clic para ver cómo funciona el Score de IA">⭐ ${safeScore} ℹ️</div>
                <div class="status-pill ${c.status === 'replied' ? 'replied' : 'pending'}" title="${c.status === 'replied' ? 'Respondido' : 'Pendiente'}">
                  ${c.status === 'replied' ? '✅' : '⏳'}
                </div>
              </div>
            </div>
            <div class="comment-body">
              ${this.escapeHtml(c.comment_text)}
            </div>
          </div>
        `;
      }

      // Detailed Media-Rich Card (6 items, clean and spacious)
      const postLikes = parseInt(c.post_likes_count || 0, 10).toLocaleString();
      const postComments = parseInt(c.post_comments_count || 0, 10).toLocaleString();
      const postReach = parseInt(c.post_reach || 0, 10).toLocaleString();
      const postCaptionText = c.post_caption || 'Publicación en redes sociales';

      return `
        <div class="${cardClass}" onclick="App.selectCommentById(${parseInt(c.id, 10)}, true)">
          
          <!-- Prominent Post Context Header -->
          <div class="card-origin-post">
            <div class="card-origin-post-left">
              <div class="card-origin-post-thumb-wrap">
                <img src="${safePostImg}" class="card-origin-post-thumb" alt="post thumbnail" loading="lazy" decoding="async" width="48" height="48" />
                <span class="card-origin-platform-badge ${c.platform === 'facebook' ? 'facebook' : 'instagram'}">${c.platform === 'instagram' ? '📸 IG' : '📘 FB'}</span>
              </div>
              <div class="card-origin-post-details">
                <div class="card-origin-post-topline">
                  <span class="card-origin-post-tag">📱 ${this.escapeHtml(accName)}</span>
                  <span class="card-origin-voice-pill">🎭 ${this.escapeHtml(voiceName)}</span>
                  <span class="card-origin-post-stats">👁️ ${postReach} alcance • ❤️ ${postLikes} likes • 💬 ${postComments} comentarios</span>
                </div>
                <div class="card-origin-post-caption" title="${this.escapeHtml(postCaptionText)}">
                  "${this.escapeHtml(postCaptionText)}"
                </div>
              </div>
            </div>
          </div>

          <!-- Follower & Comment Info -->
          <div class="card-follower-section">
            <div class="card-top">
              <div class="author-info">
                <img src="${safeAvatar}" class="author-avatar" alt="avatar" loading="lazy" decoding="async" width="36" height="36" />
                <div class="author-names">
                  <div class="author-name">
                    ${this.escapeHtml(c.author_name)}
                    <span class="author-handle">${this.escapeHtml(c.author_handle || '')}</span>
                  </div>
                  <div class="author-meta-sub">💬 Comentario del seguidor</div>
                </div>
              </div>
              <div class="card-badges">
                <button type="button" class="btn-card-assistant" onclick="event.stopPropagation(); AgentController.openAssistantModal(${parseInt(c.id, 10)})" title="Abrir Asistente para responder directamente">
                  <span class="assistant-btn-icon">🪄</span> Asistente
                </button>
                <div class="${scoreClass}" onclick="event.stopPropagation(); App.openScoreGuideModal()" style="cursor: pointer;" title="Haz clic para ver cómo funciona el Score de IA">
                  ⭐ ${safeScore} ℹ️
                </div>
                <div class="status-pill ${c.status === 'replied' ? 'replied' : 'pending'}" title="${c.status === 'replied' ? 'Respondido' : 'Pendiente'}">
                  ${c.status === 'replied' ? '✅' : '⏳'}
                </div>
              </div>
            </div>

            <div class="comment-body detailed-body">
              ${this.escapeHtml(c.comment_text)}
            </div>

            ${c.highlight_reason ? `
              <div class="highlight-reason-banner">
                ${this.escapeHtml(c.highlight_reason)}
              </div>
            ` : ''}

            ${c.status === 'replied' && c.reply_text ? `
              <div class="card-replied-preview">
                <span class="replied-label">🏛️ Respuesta publicada:</span>
                <span class="replied-text">"${this.escapeHtml(c.reply_text)}"</span>
              </div>
            ` : ''}
          </div>

        </div>
      `;
    }).join('');

    const paginationHtml = totalPages > 1 ? `
      <div class="pagination-bar">
        <button type="button" class="btn-pagination" onclick="App.changePage(${this.currentPage - 1})" ${this.currentPage === 1 ? 'disabled' : ''}>
          ← Anterior
        </button>
        <div class="pagination-pages-info">
          <span>Página <strong>${this.currentPage}</strong> de <strong>${totalPages}</strong></span>
          <span class="pagination-count-tag">(${totalItems} comentarios)</span>
        </div>
        <button type="button" class="btn-pagination" onclick="App.changePage(${this.currentPage + 1})" ${this.currentPage === totalPages ? 'disabled' : ''}>
          Siguiente →
        </button>
      </div>
    ` : '';

    listContainer.innerHTML = cardsHtml + paginationHtml;

    // Auto select first comment on desktop if none selected
    if (!this.selectedCommentId && pageComments.length > 0 && window.innerWidth > 900) {
      this.selectComment(pageComments[0], false);
    }
  },

  changePage(newPage) {
    const totalPages = Math.ceil(this.commentsList.length / this.pageSize) || 1;
    if (newPage >= 1 && newPage <= totalPages) {
      this.currentPage = newPage;
      this.renderComments(this.commentsList);
      const stream = document.getElementById('comments-stream');
      if (stream) stream.scrollTo({ top: 0, behavior: 'smooth' });
    }
  },

  selectCommentById(id, isUserAction = false) {
    const comment = this.commentsList.find(c => c.id == id);
    if (comment) this.selectComment(comment, isUserAction);
  },

  selectComment(comment, isUserAction = false) {
    this.selectedCommentId = comment.id;

    // Highlight card visually
    document.querySelectorAll('.comment-card').forEach(el => el.classList.remove('selected'));
    const activeEl = document.querySelector(`.comment-card[data-id="${comment.id}"]`);
    if (activeEl) activeEl.classList.add('selected');

    // If clicked by user, open the dedicated Comment Detail & Tracking modal!
    if (isUserAction) {
      this.openCommentDetailModal(comment.id);
    }
  },

  openCommentDetailModal(commentId) {
    const comment = this.commentsList.find(c => c.id == commentId);
    if (!comment) return;

    this.selectedCommentId = comment.id;

    // 1. Post of Origin info
    const postImg = document.getElementById('detail-post-image');
    const postBadge = document.getElementById('detail-post-platform-badge');
    const postMeta = document.getElementById('detail-post-meta-text');
    const postCaption = document.getElementById('detail-post-caption-text');
    const scoreBadge = document.getElementById('detail-score-badge');

    const safePostImg = this.sanitizeUrl(comment.post_media_url, 'https://images.unsplash.com/photo-1552346154-21d32810aba3?w=160&h=160&fit=crop&auto=format&q=75');
    if (postImg) postImg.src = safePostImg;
    if (postBadge) {
      postBadge.className = `detail-platform-badge ${comment.platform === 'facebook' ? 'facebook' : 'instagram'}`;
      postBadge.textContent = comment.platform === 'facebook' ? '📘 Facebook' : '📸 Instagram';
    }
    if (postMeta) {
      const reach = parseInt(comment.post_reach || 0, 10).toLocaleString();
      const likes = parseInt(comment.post_likes_count || 0, 10).toLocaleString();
      const coms = parseInt(comment.post_comments_count || 0, 10).toLocaleString();
      postMeta.textContent = `👁️ ${reach} alcance • ❤️ ${likes} likes • 💬 ${coms} comentarios`;
    }
    if (postCaption) {
      postCaption.textContent = comment.post_caption || 'Publicación en redes sociales';
    }
    if (scoreBadge) {
      scoreBadge.textContent = `⭐ Score ${parseInt(comment.highlight_score, 10) || 50}/100`;
    }

    // 2. Follower Comment info
    const authorAvatar = document.getElementById('detail-author-avatar');
    const authorName = document.getElementById('detail-author-name');
    const authorHandle = document.getElementById('detail-author-handle');
    const commentText = document.getElementById('detail-comment-text');
    const commentTime = document.getElementById('detail-comment-time');
    const sentimentBadge = document.getElementById('detail-sentiment-badge');

    const safeAvatar = this.sanitizeUrl(comment.author_avatar, 'https://ui-avatars.com/api/?name=User&background=6366f1&color=fff&size=96');
    if (authorAvatar) authorAvatar.src = safeAvatar;
    if (authorName) authorName.textContent = comment.author_name || 'Usuario';
    if (authorHandle) authorHandle.textContent = comment.author_handle || '';
    if (commentText) commentText.textContent = `"${comment.comment_text}"`;
    if (commentTime) commentTime.textContent = comment.created_at || 'Reciente';

    if (sentimentBadge) {
      if (comment.sentiment === 'urgent') {
        sentimentBadge.className = 'detail-sentiment-badge urgent';
        sentimentBadge.textContent = '🛡️ Apoyo Emocional & Resiliencia';
      } else if (comment.sentiment === 'lead') {
        sentimentBadge.className = 'detail-sentiment-badge lead';
        sentimentBadge.textContent = '🧠 Pregunta Filosófica / Consejo';
      } else if (comment.highlight_score >= 80) {
        sentimentBadge.className = 'detail-sentiment-badge high';
        sentimentBadge.textContent = '✨ Testimonio de Alto Impacto';
      } else {
        sentimentBadge.className = 'detail-sentiment-badge';
        sentimentBadge.textContent = '💬 Comentario de la Comunidad';
      }
    }

    // 3. Registered Reply or Pending Action
    const statusBadge = document.getElementById('detail-status-badge');
    const replyContentBox = document.getElementById('detail-reply-content-box');
    const pendingNoticeBox = document.getElementById('detail-pending-notice-box');
    const replyTextBox = document.getElementById('detail-reply-text-box');
    const replyTime = document.getElementById('detail-reply-time');
    const replyVariantTag = document.getElementById('detail-reply-variant-tag');

    if (comment.status === 'replied') {
      if (statusBadge) {
        statusBadge.className = 'detail-status-badge replied';
        statusBadge.textContent = '✅ Respondido y Publicado';
      }
      if (replyContentBox) replyContentBox.style.display = 'block';
      if (pendingNoticeBox) pendingNoticeBox.style.display = 'none';
      if (replyTextBox) replyTextBox.textContent = `"${comment.reply_text || 'Respuesta publicada a la comunidad.'}"`;
      if (replyTime) replyTime.textContent = comment.replied_at || comment.reply_created_at || 'Publicado';
      if (replyVariantTag) replyVariantTag.textContent = comment.variant_type || comment.reply_variant_type || 'Respuesta Estoica';
    } else {
      if (statusBadge) {
        statusBadge.className = 'detail-status-badge pending';
        statusBadge.textContent = '⏳ Pendiente de Respuesta';
      }
      if (replyContentBox) replyContentBox.style.display = 'none';
      if (pendingNoticeBox) pendingNoticeBox.style.display = 'flex';
    }

    this.openModal('modal-comment-detail');
  },

  openAssistantFromDetail() {
    this.closeModal('modal-comment-detail');
    if (this.selectedCommentId) {
      AgentController.openAssistantModal(this.selectedCommentId);
    }
  },

  openScoreGuideModal() {
    this.openModal('modal-score-guide');
  },

  async loadSettings() {
    try {
      const response = await this.fetchWithCsrf('api/settings.php');
      const res = await response.json();
      if (res.success && res.data) {
        const d = res.data;
        // Sidebar toggle
        const toggle = document.getElementById('autopilot-sidebar-toggle');
        if (toggle) toggle.checked = d.autopilot_enabled === '1';

        // Settings Form Inputs
        this.setInputValue('setting-brand-name', d.brand_name);
        this.setInputValue('setting-brand-industry', d.brand_industry);
        this.setInputValue('setting-brand-tone', d.brand_tone);
        this.setInputValue('setting-brand-desc', d.brand_description);
        this.setInputValue('setting-ai-provider', d.ai_provider);
        this.setInputValue('setting-gemini-key', d.gemini_api_key_masked);
        this.setInputValue('setting-openai-key', d.openai_api_key_masked);
        this.setInputValue('setting-closing-rule', d.brand_closing_question_rule);
        this.setInputValue('setting-emoji-style', d.brand_emoji_style);

        // Sliders
        if (d.brand_warmth_level !== undefined) this.updateSliderVal('warmth', d.brand_warmth_level);
        if (d.brand_depth_level !== undefined) this.updateSliderVal('depth', d.brand_depth_level);
        if (d.brand_energy_level !== undefined) this.updateSliderVal('energy', d.brand_energy_level);

        // Key phrases & Forbidden words
        if (Array.isArray(d.brand_key_phrases)) this.keyPhrases = d.brand_key_phrases;
        if (Array.isArray(d.brand_forbidden_phrases)) this.forbiddenPhrases = d.brand_forbidden_phrases;
        if (Array.isArray(d.brand_few_shot_examples)) this.fewShotExamples = d.brand_few_shot_examples;

        this.renderTagChips();
        this.renderFewShotExamples();

        // Meta Inputs
        this.setInputValue('setting-meta-app-id', d.meta_app_id);
        this.setInputValue('setting-meta-app-secret', d.meta_app_secret_masked);
        this.setInputValue('setting-meta-ig-id', d.meta_instagram_account_id);
        this.setInputValue('setting-meta-token', d.meta_page_access_token_masked);
      }
    } catch (err) {
      console.error(err);
    }
  },

  updateSliderVal(type, val) {
    const num = parseInt(val, 10) || 50;
    const slider = document.getElementById(`slider-${type}`);
    if (slider) slider.value = num;

    const badge = document.getElementById(`badge-${type}-val`);
    if (badge) badge.textContent = `${num}%`;

    const label = document.getElementById(`label-${type}-status`);
    if (label) {
      if (type === 'warmth') {
        if (num >= 80) label.textContent = '(Fraternal & Muy Cercano)';
        else if (num >= 50) label.textContent = '(Cálido & Empático)';
        else if (num >= 30) label.textContent = '(Equilibrado)';
        else label.textContent = '(Formal & Distante)';
      } else if (type === 'depth') {
        if (num >= 80) label.textContent = '(Citas de Marco Aurelio & Séneca)';
        else if (num >= 50) label.textContent = '(Reflexiones Prácticas)';
        else label.textContent = '(Consejos Directos)';
      } else if (type === 'energy') {
        if (num >= 80) label.textContent = '(Enérgico & Cero Excusas)';
        else if (num >= 50) label.textContent = '(Motivador & Firme)';
        else label.textContent = '(Sereno & Calmado)';
      }
    }
  },

  // Tag Chips Management
  renderTagChips() {
    const keyContainer = document.getElementById('key-phrases-container');
    const inputKey = document.getElementById('input-new-key-phrase');
    if (keyContainer && inputKey) {
      const chipsHtml = this.keyPhrases.map((phrase, idx) => `
        <span class="tag-chip key">
          <span>${this.escapeHtml(phrase)}</span>
          <button type="button" class="tag-chip-remove" onclick="App.removeTag('key', ${idx})">&times;</button>
        </span>
      `).join('');
      keyContainer.innerHTML = chipsHtml;
      keyContainer.appendChild(inputKey);
    }

    const forbiddenContainer = document.getElementById('forbidden-phrases-container');
    const inputForbidden = document.getElementById('input-new-forbidden-phrase');
    if (forbiddenContainer && inputForbidden) {
      const chipsHtml = this.forbiddenPhrases.map((phrase, idx) => `
        <span class="tag-chip forbidden">
          <span>🚫 ${this.escapeHtml(phrase)}</span>
          <button type="button" class="tag-chip-remove" onclick="App.removeTag('forbidden', ${idx})">&times;</button>
        </span>
      `).join('');
      forbiddenContainer.innerHTML = chipsHtml;
      forbiddenContainer.appendChild(inputForbidden);
    }
  },

  handleTagInput(e, type) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const val = e.target.value.trim();
      if (!val) return;

      if (type === 'key') {
        if (!this.keyPhrases.includes(val)) {
          this.keyPhrases.push(val);
          this.renderTagChips();
        }
      } else if (type === 'forbidden') {
        if (!this.forbiddenPhrases.includes(val)) {
          this.forbiddenPhrases.push(val);
          this.renderTagChips();
        }
      }
      e.target.value = '';
    }
  },

  removeTag(type, index) {
    if (type === 'key') {
      this.keyPhrases.splice(index, 1);
    } else if (type === 'forbidden') {
      this.forbiddenPhrases.splice(index, 1);
    }
    this.renderTagChips();
  },

  // Few-Shot Master Training Examples Management
  renderFewShotExamples() {
    const container = document.getElementById('few-shot-examples-container');
    if (!container) return;

    if (this.fewShotExamples.length === 0) {
      container.innerHTML = `
        <div style="padding: 20px; text-align: center; color: var(--text-dim); font-size: 0.82rem;">
          No hay ejemplos de oro registrados aún. Añade uno para que la IA clone tu estilo exacto.
        </div>
      `;
      return;
    }

    container.innerHTML = this.fewShotExamples.map((ex, idx) => `
      <div class="few-shot-card">
        <div class="few-shot-top">
          <span class="few-shot-tag-pill">#${this.escapeHtml(ex.tag || 'general')}</span>
          <button type="button" class="btn-post-action" style="padding: 2px 8px; font-size: 0.7rem; color: var(--accent-rose); border-color: rgba(244,63,94,0.3);" onclick="App.removeFewShotExample(${idx})">
            Eliminar 🗑️
          </button>
        </div>
        <div class="few-shot-comment-preview">💬 " ${this.escapeHtml(ex.comment)} "</div>
        <div class="few-shot-reply-preview">🏛️ <strong>Respuesta Ideal:</strong> ${this.escapeHtml(ex.reply)}</div>
      </div>
    `).join('');
  },

  openAddExampleModal() {
    this.openModal('modal-add-few-shot');
  },

  saveNewFewShotExample(e) {
    if (e) e.preventDefault();
    const tag = document.getElementById('few-shot-tag-input')?.value.trim() || 'general';
    const comment = document.getElementById('few-shot-comment-input')?.value.trim();
    const reply = document.getElementById('few-shot-reply-input')?.value.trim();

    if (!comment || !reply) {
      App.showToast('El comentario y la respuesta maestra son obligatorios.', 'error');
      return;
    }

    this.fewShotExamples.push({ tag, comment, reply });
    this.renderFewShotExamples();
    this.closeModal('modal-add-few-shot');
    
    // Clear inputs
    document.getElementById('few-shot-tag-input').value = '';
    document.getElementById('few-shot-comment-input').value = '';
    document.getElementById('few-shot-reply-input').value = '';
    
    App.showToast('¡Ejemplo de oro añadido al entrenamiento local de la IA!', 'success');
  },

  removeFewShotExample(index) {
    this.fewShotExamples.splice(index, 1);
    this.renderFewShotExamples();
    App.showToast('Ejemplo eliminado del entrenamiento.', 'success');
  },

  // Live Voice Playground
  setPlaygroundScenario(scenarioKey) {
    const authorInput = document.getElementById('playground-author');
    const commentInput = document.getElementById('playground-comment');

    if (scenarioKey === 'price_lead') {
      if (authorInput) authorInput.value = 'Carlos Ramos';
      if (commentInput) commentInput.value = '¡Hola! Me interesan mucho sus productos y servicios. ¿Cuál es el precio y qué catálogo tienen disponible?';
    } else if (scenarioKey === 'objection') {
      if (authorInput) authorInput.value = 'Lucía Morales';
      if (commentInput) commentInput.value = '¿Qué garantía ofrecen y cómo sé si funcionará de forma segura para mi empresa?';
    } else if (scenarioKey === 'support') {
      if (authorInput) authorInput.value = 'David Silva';
      if (commentInput) commentInput.value = 'Hola, tengo una duda urgente con mi cuenta y no puedo acceder. ¿Me pueden ayudar por favor?';
    } else if (scenarioKey === 'gratitude') {
      if (authorInput) authorInput.value = 'Elena Ortiz';
      if (commentInput) commentInput.value = '¡Excelente servicio y atención de primera! Quedé encantada con la rapidez y la calidad del trabajo.';
    }
  },

  async testVoicePlayground() {
    const author = document.getElementById('playground-author')?.value.trim() || 'Seguidor de Prueba';
    const comment = document.getElementById('playground-comment')?.value.trim();
    const resultsContainer = document.getElementById('playground-results-container');

    if (!comment) {
      App.showToast('Escribe o selecciona un comentario de prueba.', 'error');
      return;
    }

    if (resultsContainer) {
      resultsContainer.style.display = 'block';
      resultsContainer.innerHTML = `
        <div style="padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.84rem;">
          <div style="display: inline-block; width: 24px; height: 24px; border: 2px solid rgba(99,102,241,0.3); border-top-color: #6366f1; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 8px;"></div>
          <div>Calibrando y generando respuestas con identidad de marca...</div>
        </div>
      `;
    }

    const payload = {
      action: 'test_voice_playground',
      author_name: author,
      comment_text: comment,
      brand_name: document.getElementById('setting-brand-name')?.value,
      persona_name: document.getElementById('setting-persona-name')?.value,
      brand_industry: document.getElementById('setting-brand-industry')?.value,
      language: document.getElementById('setting-brand-language')?.value || 'es',
      brand_tone: document.getElementById('setting-brand-tone')?.value,
      brand_description: document.getElementById('setting-brand-desc')?.value,
      brand_warmth_level: parseInt(document.getElementById('slider-warmth')?.value, 10) || 85,
      brand_depth_level: parseInt(document.getElementById('slider-depth')?.value, 10) || 75,
      brand_energy_level: parseInt(document.getElementById('slider-energy')?.value, 10) || 80,
      brand_closing_question_rule: document.getElementById('setting-closing-rule')?.value || 'always',
      brand_emoji_style: document.getElementById('setting-emoji-style')?.value || 'moderate',
      brand_key_phrases: this.keyPhrases,
      brand_forbidden_phrases: this.forbiddenPhrases,
      brand_few_shot_examples: this.fewShotExamples,
      ai_provider: document.getElementById('setting-ai-provider')?.value || 'heuristic'
    };

    try {
      const response = await this.fetchWithCsrf('api/agent.php', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      const res = await response.json();

      if (res.success && res.replies) {
        const reps = res.replies;
        let sourceTag = '⚡ Motor Heurístico Calibrado (Cero Tokens)';
        if (reps.source && reps.source.includes('gemini')) sourceTag = '✨ Gemini AI Calibrado';
        else if (reps.source && reps.source.includes('openai')) sourceTag = '🧠 OpenAI Calibrado';
        else if (reps.source && reps.source.includes('trained')) sourceTag = '🎯 Ejemplo Maestro de Oro Aplicado';

        resultsContainer.innerHTML = `
          <div style="font-size: 0.72rem; font-weight: 700; color: var(--accent-emerald); margin-bottom: 4px;">
            ${sourceTag}
          </div>
          
          <div class="playground-variant-box" style="border-left: 3px solid var(--primary);">
            <div class="playground-variant-title" style="color: var(--primary);">🤝 Opción 1: Conexión & Empatía</div>
            <div>${this.escapeHtml(reps.engagement)}</div>
          </div>

          <div class="playground-variant-box" style="border-left: 3px solid var(--accent-cyan);">
            <div class="playground-variant-title" style="color: var(--accent-cyan);">🎯 Opción 2: Conversión & Ventas / CTA</div>
            <div>${this.escapeHtml(reps.conversion)}</div>
          </div>

          <div class="playground-variant-box" style="border-left: 3px solid var(--accent-emerald);">
            <div class="playground-variant-title" style="color: var(--accent-emerald);">💡 Opción 3: Autoridad & Solución</div>
            <div>${this.escapeHtml(reps.support)}</div>
          </div>

          ${reps.engagement_tips ? `
            <div style="font-size: 0.75rem; color: var(--text-dim); padding: 4px 6px;">
              💡 <em>${this.escapeHtml(reps.engagement_tips)}</em>
            </div>
          ` : ''}
        `;
        App.showToast('Simulación de voz de marca completada.', 'success');
      } else {
        resultsContainer.innerHTML = `<div style="color: var(--accent-rose); font-size: 0.8rem;">Error: ${res.error || 'No se pudo generar la simulación.'}</div>`;
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al probar simulador de voz.', 'error');
    }
  },

  async saveBrandStudioForm(e) {
    if (e) e.preventDefault();
    const brandId = document.getElementById('setting-brand-id')?.value;
    const payload = {
      action: 'save_brand',
      brand_id: brandId ? parseInt(brandId, 10) : undefined,
      brand_name: document.getElementById('setting-brand-name')?.value,
      persona_name: document.getElementById('setting-persona-name')?.value,
      brand_industry: document.getElementById('setting-brand-industry')?.value,
      language: document.getElementById('setting-brand-language')?.value || 'es',
      brand_tone: document.getElementById('setting-brand-tone')?.value,
      system_prompt: document.getElementById('setting-brand-desc')?.value,
      brand_warmth_level: parseInt(document.getElementById('slider-warmth')?.value, 10) || 85,
      brand_depth_level: parseInt(document.getElementById('slider-depth')?.value, 10) || 75,
      brand_energy_level: parseInt(document.getElementById('slider-energy')?.value, 10) || 80,
      brand_closing_question_rule: document.getElementById('setting-closing-rule')?.value,
      brand_emoji_style: document.getElementById('setting-emoji-style')?.value,
      brand_key_phrases: this.keyPhrases,
      brand_forbidden_phrases: this.forbiddenPhrases,
      brand_few_shot_examples: this.fewShotExamples
    };

    // Also send AI Engine settings
    const globalPayload = {
      action: 'save_all',
      ai_provider: document.getElementById('setting-ai-provider')?.value,
      gemini_api_key: document.getElementById('setting-gemini-key')?.value,
      openai_api_key: document.getElementById('setting-openai-key')?.value
    };

    try {
      const response = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      const res = await response.json();

      await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify(globalPayload)
      });

      if (res.success) {
        App.showToast('¡Identidad de marca y calibración guardadas exitosamente!', 'success');
        await this.loadBrands();
      } else {
        App.showToast(`Error: ${res.error}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al guardar configuración de marca.', 'error');
    }
  },

  async saveSettingsForm(e) {
    if (e) e.preventDefault();
    const payload = {
      meta_app_id: document.getElementById('setting-meta-app-id')?.value,
      meta_app_secret: document.getElementById('setting-meta-app-secret')?.value,
      meta_instagram_account_id: document.getElementById('setting-meta-ig-id')?.value,
      meta_page_access_token: document.getElementById('setting-meta-token')?.value
    };

    try {
      const response = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify(payload)
      });
      const res = await response.json();
      if (res.success) {
        App.showToast('Credenciales de Meta guardadas correctamente.', 'success');
      } else {
        App.showToast(`Error: ${res.error}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al guardar tokens.', 'error');
    }
  },

  // Live Meta Diagnostics & Permissions Verification
  async testMetaConnection() {
    const diagContainer = document.getElementById('meta-diagnostic-container');
    if (!diagContainer) return;

    const tokenInput = document.getElementById('setting-meta-token')?.value || '';

    diagContainer.style.display = 'block';
    diagContainer.innerHTML = `
      <div class="meta-diagnostic-card" style="text-align: center; padding: 28px;">
        <div style="display: inline-block; width: 32px; height: 32px; border: 3px solid rgba(24,119,242,0.3); border-top-color: #1877f2; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 12px;"></div>
        <div style="font-size: 0.95rem; font-weight: 700; color: #fff;">Conectando con Meta Graph API y auditando permisos...</div>
        <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">Verificando validez de token, páginas administradas y cuentas de Instagram Business.</p>
      </div>
    `;

    try {
      const response = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'test_meta',
          meta_page_access_token: tokenInput
        })
      });

      const res = await response.json();

      let statusClass = 'warning';
      let icon = '⚠️';
      if (res.status === 'perfect') {
        statusClass = 'success';
        icon = '✅';
      } else if (res.status === 'invalid_token' || res.status === 'missing_token') {
        statusClass = 'error';
        icon = '❌';
      }

      diagContainer.innerHTML = `
        <div class="meta-diagnostic-card">
          <div class="diagnostic-header-status">
            <div class="status-indicator-icon ${statusClass}">${icon}</div>
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff;">${this.escapeHtml(res.title || 'Diagnóstico de Meta')}</h4>
              <p style="font-size: 0.82rem; color: var(--text-muted);">${this.escapeHtml(res.message || '')}</p>
              ${res.meta_user ? `
                <div style="font-size: 0.76rem; color: var(--accent-cyan); margin-top: 4px;">
                  👤 Usuario autenticado: <strong>${this.escapeHtml(res.meta_user.name)}</strong> (ID: ${this.escapeHtml(res.meta_user.id)})
                </div>
              ` : ''}
            </div>
          </div>

          ${res.permissions && res.permissions.length > 0 ? `
            <div style="margin-top: 14px;">
              <div style="font-size: 0.84rem; font-weight: 700; color: #fff; margin-bottom: 8px;">📋 Auditoría de Permisos de la API:</div>
              <div class="permissions-checklist-grid">
                ${res.permissions.map(p => `
                  <div class="permission-check-item">
                    <div>
                      <div style="font-weight: 700; color: #f1f5f9;">${this.escapeHtml(p.permission)}</div>
                      <div style="font-size: 0.72rem; color: var(--text-dim);">${this.escapeHtml(p.description)}</div>
                    </div>
                    <span class="perm-pill ${p.granted ? 'granted' : 'missing'}">
                      ${p.granted ? '✓ Concedido' : '✗ Falta'}
                    </span>
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}

          ${res.detected_pages && res.detected_pages.length > 0 ? `
            <div style="margin-top: 16px;">
              <div style="font-size: 0.84rem; font-weight: 700; color: #fff; margin-bottom: 8px;">📲 Cuentas Vinculadas Detectadas en tu Meta Token:</div>
              <div class="detected-accounts-list">
                ${res.detected_pages.map(dp => `
                  <div class="detected-account-row">
                    <div class="account-info-left">
                      <img src="${dp.instagram_avatar || 'https://ui-avatars.com/api/?name=' + urlencode(dp.page_name)}" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover;" />
                      <div>
                        <div style="font-size: 0.88rem; font-weight: 700; color: #fff;">
                          📘 ${this.escapeHtml(dp.page_name)}
                          ${dp.has_instagram ? `<span style="color: #f43f5e; font-size: 0.8rem; margin-left: 6px;">📸 @${this.escapeHtml(dp.instagram_username)}</span>` : ''}
                        </div>
                        <div style="font-size: 0.72rem; color: var(--text-dim);">
                          Page ID: ${this.escapeHtml(dp.page_id)} ${dp.instagram_id ? '• IG ID: ' + this.escapeHtml(dp.instagram_id) : '(Sin cuenta de Instagram conectada)'}
                        </div>
                      </div>
                    </div>
                    ${dp.instagram_id ? `
                      <button type="button" class="btn-use-account" onclick="App.selectDetectedAccount('${this.escapeHtml(dp.instagram_id)}', '${dp.page_token ? this.escapeHtml(dp.page_token) : ''}')">
                        Vincular Cuenta 📲
                      </button>
                    ` : `
                      <span style="font-size: 0.72rem; color: var(--accent-amber);">Enlaza IG a esta Página</span>
                    `}
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}

          ${res.recommendations && res.recommendations.length > 0 ? `
            <div style="margin-top: 16px; background: rgba(255, 255, 255, 0.02); padding: 12px; border-radius: var(--radius-sm); border-left: 3px solid var(--accent-cyan);">
              <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-cyan); margin-bottom: 4px;">💡 Recomendaciones para la API:</div>
              <ul style="font-size: 0.78rem; color: var(--text-muted); padding-left: 18px; line-height: 1.6;">
                ${res.recommendations.map(r => `<li>${this.escapeHtml(r)}</li>`).join('')}
              </ul>
            </div>
          ` : ''}
        </div>
      `;

      if (res.success) {
        this.showToast('Diagnóstico de Meta completado.', 'success');
      } else {
        this.showToast('Atención: Revisa el reporte de diagnóstico de Meta.', 'error');
      }

    } catch (err) {
      console.error(err);
      diagContainer.innerHTML = `
        <div class="meta-diagnostic-card">
          <div class="diagnostic-header-status">
            <div class="status-indicator-icon error">❌</div>
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff;">Error de Conexión</h4>
              <p style="font-size: 0.82rem; color: var(--text-muted);">No se pudo establecer comunicación con el servidor para diagnosticar Meta.</p>
            </div>
          </div>
        </div>
      `;
    }
  },

  // Pre-Audit Scanner for Meta App Review Readiness
  async auditMetaAppReview() {
    const auditContainer = document.getElementById('meta-audit-container');
    if (!auditContainer) return;

    auditContainer.style.display = 'block';
    auditContainer.innerHTML = `
      <div class="meta-diagnostic-card" style="text-align: center; padding: 28px;">
        <div style="display: inline-block; width: 32px; height: 32px; border: 3px solid rgba(16,185,129,0.3); border-top-color: #10b981; border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 12px;"></div>
        <div style="font-size: 0.95rem; font-weight: 700; color: #fff;">Ejecutando Pre-Auditoría para Meta App Review...</div>
        <p style="font-size: 0.8rem; color: var(--text-dim); margin-top: 4px;">Comprobando SSL, Términos y Privacidad, eliminación de datos, cola asíncrona de webhooks y permisos.</p>
      </div>
    `;

    try {
      const response = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'audit_meta' })
      });

      const res = await response.json();

      let scoreColor = '#10b981';
      if (res.score < 50) scoreColor = '#ef4444';
      else if (res.score < 80) scoreColor = '#f59e0b';

      auditContainer.innerHTML = `
        <div class="meta-diagnostic-card" style="border-color: rgba(16, 185, 129, 0.3);">
          <!-- Top Score Banner -->
          <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; margin-bottom: 18px; padding-bottom: 16px; border-bottom: 1px solid var(--border-subtle);">
            <div>
              <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.4rem;">🛡️</span>
                <div>
                  <h4 style="font-size: 1.1rem; font-weight: 800; color: #fff; margin: 0;">Resultado de Auditoría Pre-App Review</h4>
                  <p style="font-size: 0.8rem; color: var(--text-muted); margin: 2px 0 0;">${this.escapeHtml(res.status_label || '')}</p>
                </div>
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
              <div style="text-align: right;">
                <div style="font-size: 0.72rem; color: var(--text-dim); text-transform: uppercase; font-weight: 700;">Cumplimiento Meta</div>
                <div style="font-size: 1.4rem; font-weight: 900; color: ${scoreColor};">${res.score || 0}%</div>
              </div>
              <div style="width: 54px; height: 54px; border-radius: 50%; background: conic-gradient(${scoreColor} ${(res.score || 0) * 3.6}deg, rgba(255,255,255,0.08) 0deg); display: flex; align-items: center; justify-content: center;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: #111827; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 800; color: #fff;">
                  ${res.score || 0}
                </div>
              </div>
            </div>
          </div>

          <!-- Checklist Grid -->
          <div style="font-size: 0.84rem; font-weight: 700; color: #fff; margin-bottom: 10px;">📋 Requisitos Técnicos y Políticas Auditados:</div>
          <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 18px;">
            ${(res.checklist || []).map(item => {
              let badgeColor = 'rgba(16, 185, 129, 0.15)';
              let badgeText = '#34d399';
              let icon = '✓';
              if (item.status === 'fail') {
                badgeColor = 'rgba(239, 68, 68, 0.15)';
                badgeText = '#f87171';
                icon = '✕';
              } else if (item.status === 'warning') {
                badgeColor = 'rgba(245, 158, 11, 0.15)';
                badgeText = '#fbbf24';
                icon = '⚠️';
              }
              return `
                <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-subtle); border-radius: var(--radius-sm); padding: 12px 14px; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                  <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                      <span style="font-size: 0.72rem; text-transform: uppercase; font-weight: 800; color: var(--accent-cyan); background: rgba(6,182,212,0.1); padding: 2px 6px; border-radius: 4px;">${this.escapeHtml(item.category)}</span>
                      <strong style="font-size: 0.88rem; color: #f1f5f9;">${this.escapeHtml(item.name)}</strong>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin: 6px 0 2px; line-height: 1.4;">${this.escapeHtml(item.description)}</p>
                    ${item.details ? `<code style="font-size: 0.74rem; color: #94a3b8;">${this.escapeHtml(item.details)}</code>` : ''}
                  </div>
                  <span style="background: ${badgeColor}; color: ${badgeText}; font-size: 0.74rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; white-space: nowrap;">
                    ${icon} ${item.status.toUpperCase()}
                  </span>
                </div>
              `;
            }).join('')}
          </div>

          <!-- Official Submission URLs to Copy -->
          ${res.submission_urls ? `
            <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.25); padding: 16px; border-radius: var(--radius-sm); margin-bottom: 16px;">
              <div style="font-size: 0.82rem; font-weight: 800; color: #a5b4fc; margin-bottom: 8px;">🔗 URLs de Registro Oficial en developers.facebook.com:</div>
              <div style="display: grid; grid-template-columns: 1fr; gap: 8px; font-size: 0.78rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 6px;">
                  <span style="color: #94a3b8;">Privacy Policy:</span>
                  <code style="color: #fff;">${this.escapeHtml(res.submission_urls.privacy_policy)}</code>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 6px;">
                  <span style="color: #94a3b8;">Terms of Service:</span>
                  <code style="color: #fff;">${this.escapeHtml(res.submission_urls.terms_of_service)}</code>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 6px;">
                  <span style="color: #94a3b8;">User Data Deletion:</span>
                  <code style="color: #fff;">${this.escapeHtml(res.submission_urls.data_deletion)}</code>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 6px;">
                  <span style="color: #94a3b8;">OAuth Redirect URI:</span>
                  <code style="color: #fff;">${this.escapeHtml(res.submission_urls.oauth_redirect_uri)}</code>
                </div>
              </div>
            </div>
          ` : ''}

          <!-- Recommendations -->
          ${res.recommendations && res.recommendations.length > 0 ? `
            <div style="background: rgba(255, 255, 255, 0.02); padding: 12px; border-radius: var(--radius-sm); border-left: 3px solid var(--accent-emerald);">
              <div style="font-size: 0.8rem; font-weight: 700; color: var(--accent-emerald); margin-bottom: 4px;">🚀 Próximos Pasos para la Aprobación:</div>
              <ul style="font-size: 0.78rem; color: var(--text-muted); padding-left: 18px; line-height: 1.6; margin: 0;">
                ${res.recommendations.map(r => `<li>${this.escapeHtml(r)}</li>`).join('')}
              </ul>
            </div>
          ` : ''}
        </div>
      `;

      if (res.is_ready) {
        this.showToast('¡Auditoría completada! Tu app está lista para App Review.', 'success');
      } else {
        this.showToast('Auditoría completada con advertencias.', 'info');
      }

    } catch (err) {
      console.error(err);
      auditContainer.innerHTML = `
        <div class="meta-diagnostic-card">
          <div class="diagnostic-header-status">
            <div class="status-indicator-icon error">❌</div>
            <div>
              <h4 style="font-size: 1.05rem; font-weight: 800; color: #fff;">Error de Auditoría</h4>
              <p style="font-size: 0.82rem; color: var(--text-muted);">No se pudo ejecutar la auditoría pre-app review.</p>
            </div>
          </div>
        </div>
      `;
    }
  },

  selectDetectedAccount(igId, pageToken) {
    if (igId) {
      const elIg = document.getElementById('setting-meta-ig-id');
      if (elIg) elIg.value = igId;
    }
    if (pageToken) {
      const elTok = document.getElementById('setting-meta-token');
      if (elTok) elTok.value = pageToken;
    }
    this.showToast('¡IDs de cuenta de Instagram y Token seleccionados! Haz clic en Guardar Tokens.', 'success');
  },

  async triggerMetaSync() {
    App.showToast('Sincronizando publicaciones, métricas e insights desde Meta Graph API...', 'success');
    try {
      const response = await this.fetchWithCsrf('api/settings.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'sync_meta' })
      });
      const res = await response.json();
      if (res.success) {
        let toastMsg = res.message || 'Sincronización completada con éxito.';
        if (res.data) {
          toastMsg += ` (Cuentas: ${res.data.pages_count || 0}, Posts: ${res.data.synced_posts || 0}, Comentarios: ${res.data.synced_comments || 0})`;
        }
        App.showToast(toastMsg, 'success');
        await this.loadConnectedAccounts();
        await this.loadComments();
        if (typeof AnalyticsController !== 'undefined' && AnalyticsController.loadAnalytics) {
          AnalyticsController.loadAnalytics();
        }
      } else {
        App.showToast(res.message || 'No se pudo sincronizar.', 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error en la sincronización con Meta.', 'error');
    }
  },

  async submitSimulatedComment(e) {
    if (e) e.preventDefault();
    const platform = document.getElementById('sim-platform')?.value || 'instagram';
    const authorName = document.getElementById('sim-author')?.value || 'Usuario Test';
    const commentText = document.getElementById('sim-comment')?.value || '';

    if (!commentText.trim()) {
      App.showToast('Escribe el texto del comentario.', 'error');
      return;
    }

    try {
      const response = await this.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'create_simulated',
          platform: platform,
          author_name: authorName,
          comment_text: commentText,
          post_id: 1
        })
      });
      const res = await response.json();
      if (res.success) {
        App.showToast('¡Comentario generado y analizado con éxito por la IA!', 'success');
        this.closeModal('modal-simulate');
        document.getElementById('sim-comment').value = '';
        await this.loadComments();
        if (res.comment_id) {
          this.selectCommentById(res.comment_id, true);
        }
      } else {
        App.showToast(`Error: ${res.error || 'Error al procesar comentario'}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al crear comentario simulado.', 'error');
    }
  },

  openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.add('active');
      modal.style.display = 'flex';
      modal.style.opacity = '1';
      modal.style.pointerEvents = 'auto';
      modal.style.visibility = 'visible';
    }
  },

  closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
      modal.classList.remove('active');
      modal.style.display = '';
      modal.style.opacity = '';
      modal.style.pointerEvents = '';
      modal.style.visibility = '';
    }
  },

  async logout() {
    try {
      await this.fetchWithCsrf('api/auth.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'logout' })
      });
      window.location.href = 'login.php';
    } catch (err) {
      window.location.href = 'login.php';
    }
  },

  // Safe DOM Toast without XSS injection risk
  showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type === 'error' ? 'error' : 'success'}`;

    const iconSpan = document.createElement('span');
    iconSpan.textContent = type === 'success' ? '✨' : '⚠️';

    const textSpan = document.createElement('span');
    textSpan.textContent = String(message);

    toast.appendChild(iconSpan);
    toast.appendChild(textSpan);
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(10px)';
      toast.style.transition = '0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 4000);
  },

  setInputValue(id, val) {
    const el = document.getElementById(id);
    if (el && val !== undefined && val !== null) el.value = val;
  },

  escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  },

  escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
  },

  sanitizeUrl(url, fallback = '') {
    if (!url || typeof url !== 'string') return fallback;
    const clean = url.trim();
    // Only allow http, https and data URIs for images
    if (/^(https?:\/\/|data:image\/)/i.test(clean)) {
      return this.escapeHtml(clean);
    }
    return fallback;
  }
};

document.addEventListener('DOMContentLoaded', () => {
  App.init();
});
