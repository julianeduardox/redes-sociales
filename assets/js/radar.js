/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🧭 RADAR DE CREADORES & RE-CREACIÓN ESTOICA
 * Controlador de Frontend para Monitoreo de Nicho, Auditoría de Citas y Generación
 * de Contenido de Marca (@fortaleza_imparable).
 * ══════════════════════════════════════════════════════════════════════════════
 */

const RadarController = {
  creators: [],
  posts: [],
  filteredPosts: [],
  activeSort: 'engagement',
  selectedCreatorId: 'all',
  selectedQuoteStatus: 'all',
  searchQuery: '',
  activePostModal: null,

  init() {
    this.bindEvents();
    this.loadRadar();
  },

  bindEvents() {
    const searchInput = document.getElementById('radar-search-input');
    if (searchInput) {
      searchInput.addEventListener('input', (e) => {
        this.searchQuery = e.target.value.toLowerCase().trim();
        this.applyFiltersAndRender();
      });
    }

    const sortSelect = document.getElementById('radar-sort-select');
    if (sortSelect) {
      sortSelect.addEventListener('change', (e) => {
        this.activeSort = e.target.value;
        this.loadRadar();
      });
    }

    const creatorFilter = document.getElementById('radar-creator-filter');
    if (creatorFilter) {
      creatorFilter.addEventListener('change', (e) => {
        this.selectedCreatorId = e.target.value;
        this.applyFiltersAndRender();
      });
    }

    const statusFilter = document.getElementById('radar-status-filter');
    if (statusFilter) {
      statusFilter.addEventListener('change', (e) => {
        this.selectedQuoteStatus = e.target.value;
        this.applyFiltersAndRender();
      });
    }
  },

  async loadRadar(showFeedback = false) {
    const feedContainer = document.getElementById('radar-posts-grid');
    const creatorsContainer = document.getElementById('radar-creators-carousel');

    if (feedContainer && this.posts.length === 0) {
      feedContainer.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 40px; text-align: center; color: var(--text-dim);">
          <div class="loading-spinner" style="margin: 0 auto 12px auto; width: 32px; height: 32px;"></div>
          <p style="margin: 0; font-size: 0.9rem;">Sintonizando el radar de creadores de nicho...</p>
        </div>
      `;
    }

    try {
      const res = await App.fetchWithCsrf(`api/inspiration.php?action=get_radar&sort=${encodeURIComponent(this.activeSort)}`);
      const data = await res.json();

      if (data.success && data.data) {
        this.creators = data.data.creators || [];
        this.posts = data.data.posts || [];

        this.renderCreators();
        this.updateFilterDropdowns();
        this.applyFiltersAndRender();

        if (showFeedback) {
          App.showToast(`🧭 Radar actualizado: ${this.posts.length} publicaciones disponibles.`, 'success');
        }
      } else {
        if (feedContainer) {
          feedContainer.innerHTML = `
            <div style="grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--accent-rose);">
              ⚠️ ${App.escapeHtml(data.error || 'No se pudieron cargar los datos del radar.')}
            </div>
          `;
        }
      }
    } catch (err) {
      console.error('Error loading radar data:', err);
      if (feedContainer) {
        feedContainer.innerHTML = `
          <div style="grid-column: 1 / -1; padding: 30px; text-align: center; color: var(--accent-rose);">
            ⚠️ Error de conexión al cargar el radar. Verifica tu conexión local.
          </div>
        `;
      }
    }
  },

  renderCreators() {
    const container = document.getElementById('radar-creators-carousel');
    if (!container) return;

    if (!this.creators || this.creators.length === 0) {
      container.innerHTML = `
        <div style="padding: 20px; color: var(--text-dim); font-size: 0.85rem;">
          No hay creadores agregados aún. Usa el botón "+ Monitorear Creador" para comenzar.
        </div>
      `;
      return;
    }

    container.innerHTML = this.creators.map(c => {
      const isIg = c.platform === 'instagram';
      const platformIcon = isIg ? '📸' : '📘';
      const platformClass = isIg ? 'platform-badge-mini instagram' : 'platform-badge-mini facebook';
      const followersText = c.followers_count ? this.formatNumber(c.followers_count) + ' seg.' : 'Manual';
      const mediaText = c.media_count ? `${c.media_count} posts` : '';
      const avatarSrc = c.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(c.display_name || c.username)}&background=7c3aed&color=fff&size=100`;

      const lastSyncText = c.last_synced_at ? this.formatRelativeTime(c.last_synced_at) : 'Nunca';

      return `
        <div class="creator-card ${isIg ? 'ig-card' : 'fb-card'}" data-creator-id="${c.id}">
          <div class="creator-card-header">
            <div class="creator-avatar-wrap">
              <img src="${App.escapeHtml(avatarSrc)}" alt="${App.escapeHtml(c.username)}" class="creator-avatar-img" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(c.username)}&background=4f46e5&color=fff';" />
              <span class="${platformClass}">${platformIcon}</span>
            </div>
            <div class="creator-info">
              <div class="creator-name" title="${App.escapeHtml(c.display_name || c.username)}">${App.escapeHtml(c.display_name || c.username)}</div>
              <div class="creator-handle">@${App.escapeHtml(c.username)}</div>
            </div>
          </div>

          <div class="creator-metrics-row">
            <div class="creator-metric-item">
              <span class="metric-val">${followersText}</span>
              <span class="metric-lbl">Audiencia</span>
            </div>
            <div class="creator-metric-item">
              <span class="metric-val">${mediaText || platformIcon}</span>
              <span class="metric-lbl">Catálogo</span>
            </div>
          </div>

          <div class="creator-card-footer">
            <span class="creator-sync-status" title="Última sincronización: ${App.escapeHtml(c.last_synced_at || 'Pendiente')}">
              🕒 ${App.escapeHtml(lastSyncText)}
            </span>
            ${isIg ? `
              <button type="button" class="btn-creator-sync" onclick="RadarController.syncCreator(${c.id}, this)" title="Sincronizar publicaciones recientes con Meta Graph API">
                <span>🔄 Sync</span>
              </button>
            ` : `
              <button type="button" class="btn-creator-sync fb-btn" onclick="RadarController.openImportForCreator(${c.id}, '${App.escapeHtml(c.username)}')" title="Importar publicación o enlace de Facebook">
                <span>📥 Importar</span>
              </button>
            `}
          </div>
        </div>
      `;
    }).join('');
  },

  updateFilterDropdowns() {
    const creatorFilter = document.getElementById('radar-creator-filter');
    const importCreator = document.getElementById('radar-import-creator');

    if (creatorFilter) {
      let opts = '<option value="all">🌐 Todos los Creadores</option>';
      this.creators.forEach(c => {
        const icon = c.platform === 'instagram' ? '📸' : '📘';
        opts += `<option value="${c.id}" ${c.id == this.selectedCreatorId ? 'selected' : ''}>${icon} @${App.escapeHtml(c.username)} (${App.escapeHtml(c.display_name || c.username)})</option>`;
      });
      creatorFilter.innerHTML = opts;
    }

    if (importCreator) {
      let opts = '<option value="">(Opcional) Asociar a Creador</option>';
      this.creators.forEach(c => {
        const icon = c.platform === 'instagram' ? '📸' : '📘';
        opts += `<option value="${c.id}">${icon} @${App.escapeHtml(c.username)}</option>`;
      });
      importCreator.innerHTML = opts;
    }
  },

  applyFiltersAndRender() {
    let filtered = [...this.posts];

    if (this.selectedCreatorId !== 'all') {
      filtered = filtered.filter(p => p.creator_id == this.selectedCreatorId);
    }

    if (this.selectedQuoteStatus !== 'all') {
      filtered = filtered.filter(p => (p.quote_verified_status || 'pending') === this.selectedQuoteStatus);
    }

    if (this.searchQuery) {
      filtered = filtered.filter(p => {
        const caption = (p.caption || '').toLowerCase();
        const quote = (p.quote_extracted || '').toLowerCase();
        const author = (p.quote_author || '').toLowerCase();
        const creator = (p.creator_username || '').toLowerCase();
        return caption.includes(this.searchQuery) ||
               quote.includes(this.searchQuery) ||
               author.includes(this.searchQuery) ||
               creator.includes(this.searchQuery);
      });
    }

    this.filteredPosts = filtered;
    this.renderPosts(filtered);
  },

  renderPosts(posts) {
    const container = document.getElementById('radar-posts-grid');
    const countBadge = document.getElementById('radar-posts-count-badge');
    if (!container) return;

    if (countBadge) {
      countBadge.textContent = `${posts.length} de ${this.posts.length} posts`;
    }

    if (!posts || posts.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 48px 20px; text-align: center; color: var(--text-dim); background: rgba(18, 24, 38, 0.4); border-radius: 16px; border: 1px dashed var(--border-subtle);">
          <div style="font-size: 2.5rem; margin-bottom: 10px;">🔍</div>
          <strong style="color: #fff; display: block; font-size: 1.05rem; margin-bottom: 6px;">No se encontraron publicaciones</strong>
          <p style="margin: 0; font-size: 0.85rem; max-width: 460px; margin: 0 auto; line-height: 1.5;">
            Prueba ajustando los filtros de búsqueda o haz clic en "Sincronizar Todo" para traer los últimos posts de tus creadores favoritos.
          </p>
        </div>
      `;
      return;
    }

    container.innerHTML = posts.map(p => {
      const isIg = p.platform === 'instagram';
      const platformIcon = isIg ? '📸' : '📘';
      const likesFormatted = this.formatNumber(p.likes_count || 0);
      const commentsFormatted = this.formatNumber(p.comments_count || 0);
      const relativeTime = p.posted_at ? this.formatRelativeTime(p.posted_at) : 'Reciente';

      const quoteStatus = p.quote_verified_status || 'pending';
      const statusBadge = this.renderStatusBadge(quoteStatus);

      const creatorHandle = p.creator_username ? `@${p.creator_username}` : (p.platform === 'facebook' ? 'Facebook Post' : 'Instagram');
      const creatorAvatar = p.creator_avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(creatorHandle)}&background=6366f1&color=fff`;

      // Media thumbnail or elegant fallback
      const hasMedia = p.media_url && p.media_url.trim().length > 0;
      const mediaHtml = hasMedia ? `
        <div class="radar-post-thumb-wrap">
          <img src="${App.escapeHtml(p.media_url)}" alt="Post preview" class="radar-post-thumb-img" loading="lazy" referrerpolicy="no-referrer" onerror="this.parentElement.innerHTML='<div class=\\'radar-thumb-fallback\\'>🏛️</div>';" />
          <span class="radar-thumb-badge">${platformIcon} ${p.media_type || 'post'}</span>
        </div>
      ` : `
        <div class="radar-thumb-fallback">
          <span style="font-size: 2.2rem;">🏛️</span>
          <span style="font-size: 0.72rem; color: var(--text-dim); margin-top: 4px;">Post Filosófico</span>
        </div>
      `;

      const cleanCaption = p.caption || '(Sin texto)';
      const truncatedCaption = cleanCaption.length > 220 ? cleanCaption.slice(0, 220) + '...' : cleanCaption;

      const hasRecreations = p.recreated_copies ? true : false;

      return `
        <div class="radar-post-card" data-post-id="${p.id}">
          <div class="radar-post-header">
            <div class="radar-post-author">
              <img src="${App.escapeHtml(creatorAvatar)}" class="radar-author-avatar" alt="avatar" referrerpolicy="no-referrer" />
              <div class="radar-author-meta">
                <span class="radar-author-name">${App.escapeHtml(creatorHandle)}</span>
                <span class="radar-post-date">${App.escapeHtml(relativeTime)}</span>
              </div>
            </div>
            ${p.permalink ? `
              <a href="${App.escapeHtml(p.permalink)}" target="_blank" rel="noopener noreferrer" class="radar-external-link" title="Ver publicación original en ${isIg ? 'Instagram' : 'Facebook'}">
                ↗
              </a>
            ` : ''}
          </div>

          ${mediaHtml}

          <div class="radar-post-body">
            <div class="radar-engagement-stats">
              <span class="radar-stat-pill likes" title="Likes">❤️ ${likesFormatted}</span>
              <span class="radar-stat-pill comments" title="Comentarios">💬 ${commentsFormatted}</span>
              ${p.engagement_score > 0 ? `<span class="radar-stat-pill score" title="Índice de Viralidad">🔥 ${Math.round(p.engagement_score)} pts</span>` : ''}
              ${p.opportunity_score > 0 ? `<span class="radar-stat-pill opportunity" title="Score de Oportunidad Viral de Atenea">🎯 ${Number(p.opportunity_score).toFixed(1)}/10</span>` : ''}
            </div>

            <div class="radar-post-status-row">
              ${statusBadge}
              ${p.quote_author ? `<span class="radar-author-tag">🏛️ ${App.escapeHtml(p.quote_author)}</span>` : ''}
            </div>

            <div class="radar-post-caption" title="${App.escapeHtml(cleanCaption)}">
              ${App.escapeHtml(truncatedCaption)}
            </div>
          </div>

          <div class="radar-post-actions">
            <button type="button" class="btn-recreate-fortaleza" onclick="RadarController.openRecreateModal(${p.id})">
              <span>🏛️ Atenea Studio</span>
              <span class="btn-badge-brand">4 Copys + Midjourney</span>
            </button>
          </div>
        </div>
      `;
    }).join('');
  },

  renderStatusBadge(status) {
    switch (status) {
      case 'verified_authentic':
        return '<span class="quote-badge-pill verified" title="Cita históricamente auténtica y verificada en textos clásicos">🏛️ Auténtica</span>';
      case 'apocryphal':
        return '<span class="quote-badge-pill apocryphal" title="Cita falsa o atribuida erróneamente en redes">⚠️ Apócrifa / Mito</span>';
      case 'modern_idea':
        return '<span class="quote-badge-pill modern" title="Reflexión contemporánea o idea de creador actual">💡 Idea Moderna</span>';
      default:
        return '<span class="quote-badge-pill pending" title="Pendiente de análisis con el motor de IA">⏳ Sin Auditar</span>';
    }
  },

  async syncCreator(creatorId, btnEl) {
    if (!creatorId) return;
    const originalText = btnEl ? btnEl.innerHTML : '';
    if (btnEl) {
      btnEl.disabled = true;
      btnEl.innerHTML = '<span class="loading-spinner mini"></span> Syncing...';
    }

    try {
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'sync_creator',
          creator_id: creatorId
        })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast(data.message || 'Creador sincronizado exitosamente.', 'success');
        await this.loadRadar();
      } else {
        App.showToast(`Error: ${data.error || 'No se pudo sincronizar'}`, 'error');
      }
    } catch (err) {
      console.error('Error syncing creator:', err);
      App.showToast('Error de conexión al sincronizar creador.', 'error');
    } finally {
      if (btnEl) {
        btnEl.disabled = false;
        btnEl.innerHTML = originalText;
      }
    }
  },

  async syncAll(btnEl) {
    const originalText = btnEl ? btnEl.innerHTML : '';
    if (btnEl) {
      btnEl.disabled = true;
      btnEl.innerHTML = '<span class="loading-spinner mini"></span> Sincronizando cuentas...';
    }

    try {
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'sync_all' })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast(`🚀 Sincronización completa: ${data.total_posts_synced || 0} publicaciones actualizadas.`, 'success');
        await this.loadRadar();
      } else {
        App.showToast(`Error: ${data.error || 'No se pudo sincronizar todo'}`, 'error');
      }
    } catch (err) {
      console.error('Error syncing all:', err);
      App.showToast('Error de red al sincronizar todas las cuentas.', 'error');
    } finally {
      if (btnEl) {
        btnEl.disabled = false;
        btnEl.innerHTML = originalText;
      }
    }
  },

  openImportForCreator(creatorId, username) {
    const importInput = document.getElementById('radar-import-input');
    const importCreator = document.getElementById('radar-import-creator');
    if (importCreator) importCreator.value = creatorId;
    if (importInput) {
      importInput.placeholder = `Pega aquí el enlace o texto del post de @${username}...`;
      importInput.focus();
    }
    App.showToast(`Modo importación rápida para @${username}. Pega el post abajo.`, 'info');
  },

  async submitDirectImport(e) {
    if (e) e.preventDefault();
    const input = document.getElementById('radar-import-input');
    const creatorSelect = document.getElementById('radar-import-creator');
    const btn = document.getElementById('btn-radar-import-submit');

    if (!input || !input.value.trim()) {
      App.showToast('Por favor introduce un enlace o texto a importar.', 'error');
      return;
    }

    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<span class="loading-spinner mini"></span> Analizando...';
    }

    try {
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'import_post',
          url: input.value.trim(),
          creator_id: creatorSelect ? creatorSelect.value : null
        })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast('¡Publicación importada y auditada con éxito! 🏛️', 'success');
        input.value = '';
        await this.loadRadar();
        if (data.post_id) {
          this.openRecreateModal(data.post_id);
        }
      } else {
        App.showToast(`Error: ${data.error || 'No se pudo importar'}`, 'error');
      }
    } catch (err) {
      console.error('Error importing post:', err);
      App.showToast('Error al procesar la importación.', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    }
  },

  // ──────────────────────────────────────────────────────────────────────────
  // MODAL DE DESMONTE Y RE-CREACIÓN PARA FORTALEZA IMPARABLE
  // ──────────────────────────────────────────────────────────────────────────

  async openRecreateModal(postId, forceRegenerate = false) {
    const modal = document.getElementById('modal-recreate-fortaleza');
    if (!modal) return;

    modal.classList.add('active');
    document.body.style.overflow = 'hidden';

    const container = document.getElementById('recreate-modal-content');
    if (container && (!container.children.length || !container.querySelector('.recreation-studio-layout') || forceRegenerate)) {
      container.innerHTML = `
        <div style="padding: 60px 20px; text-align: center; color: var(--text-dim);">
          <div class="loading-spinner" style="margin: 0 auto 16px auto; width: 42px; height: 42px;"></div>
          <strong style="color: #fff; font-size: 1.1rem; display: block; margin-bottom: 6px;">
            ${forceRegenerate ? 'Regenerando Nuevas Variaciones con IA...' : 'Auditoría Filosófica & Generación de Contenido Original'}
          </strong>
          <p style="margin: 0; font-size: 0.85rem; max-width: 480px; margin: 0 auto; color: var(--text-muted);">
            ${forceRegenerate 
              ? 'Explorando nuevos ángulos conceptuales, hooks y prompt visual para @fortaleza_imparable...'
              : 'Consultando la biblioteca de textos clásicos (Meditaciones, Epicteto, Séneca, Dokkodo) y calibrando el tono de @fortaleza_imparable...'}
          </p>
        </div>
      `;
    }

    try {
      const activeBrandId = document.getElementById('topbar-brand-select')?.value || 1;
      const recRes = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'recreate',
          post_id: postId,
          brand_voice_id: activeBrandId,
          force_regenerate: forceRegenerate ? 1 : 0
        })
      });
      const recData = await recRes.json();

      if (recData.success && recData.data) {
        const postObj = recData.data.post || recData.data.reference_post || {};
        const recreationsObj = recData.data.recreations || {};
        const dnaObj = recData.data.dna || {};
        const visualDirectorObj = recData.data.visual_director || {};
        const oppScore = recData.data.opportunity_score || postObj.opportunity_score || 0;

        this.renderRecreationStudio(postObj, recreationsObj, postId, dnaObj, visualDirectorObj, oppScore);
        if (forceRegenerate) {
          App.showToast('¡Nuevas 4 variaciones y dirección visual generadas por Atenea!', 'success');
        }
      } else {
        container.innerHTML = `
          <div style="padding: 40px; text-align: center; color: var(--accent-rose);">
            ⚠️ Error: ${App.escapeHtml(recData.error || 'No se pudieron generar las recreaciones')}
          </div>
        `;
      }
    } catch (err) {
      console.error('Error generating recreations:', err);
      container.innerHTML = `
        <div style="padding: 40px; text-align: center; color: var(--accent-rose);">
          ⚠️ Error de conexión con el motor de IA. Inténtalo nuevamente.
        </div>
      `;
    }
  },

  renderRecreationStudio(post, recreations, postId = null, dna = null, visualDirector = null, opportunityScore = null) {
    const container = document.getElementById('recreate-modal-content');
    if (!container) return;

    post = post || {};
    recreations = recreations || {};
    const currentId = postId || post.id || 0;

    // ADN Psicológico
    dna = dna || recreations.content_dna || (post.content_dna ? (typeof post.content_dna === 'string' ? JSON.parse(post.content_dna) : post.content_dna) : null) || {};
    visualDirector = visualDirector || recreations.visual_director || {};
    const scoreVal = opportunityScore || post.opportunity_score || 0;

    const quoteStatus = post.quote_verified_status || post.status || 'pending';
    let statusClass = 'verified';
    let statusLabel = '🏛️ CITA AUTÉNTICA (VERIFICADA)';
    let statusDesc = 'Atribución histórica confirmada en los textos clásicos.';

    if (quoteStatus === 'apocryphal') {
      statusClass = 'apocryphal';
      statusLabel = '⚠️ CITA APÓCRIFA / MITO DE REDES';
      statusDesc = 'Esta frase circula atribuida erróneamente a este autor. Atenea no copiará la mentira, sino la verdad histórica.';
    } else if (quoteStatus === 'modern_idea') {
      statusClass = 'modern';
      statusLabel = '💡 IDEA MODERNA / REFLEXIÓN CONTEMPORÁNEA';
      statusDesc = 'Concepto original o adaptación moderna sin autor clásico específico.';
    }

    const optShort = recreations.option_short || '';
    const optReflective = recreations.option_reflective || '';
    const optWarrior = recreations.option_warrior || '';
    const optStoic = recreations.option_stoic || recreations.option_reflective || '';
    const visualPrompt = visualDirector.midjourney_prompt || recreations.visual_prompt || recreations.image_prompt || '';

    container.innerHTML = `
      <div class="recreation-studio-layout">

        <!-- Columna Izquierda: Origen, ADN y Dirección Visual -->
        <div class="recreation-left-col">
          <div class="recreation-source-card">
            <div class="studio-section-label">ORIGEN DE LA INSPIRACIÓN</div>
            <div class="source-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
              <span class="source-creator">@${App.escapeHtml(post.creator_username || 'nicho')}</span>
              <div style="display: flex; gap: 6px; align-items: center;">
                <span class="source-likes">❤️ ${this.formatNumber(post.likes_count || 0)}</span>
                ${scoreVal > 0 ? `<span class="radar-stat-pill opportunity" style="font-size: 0.72rem; padding: 2px 7px;">🎯 ${Number(scoreVal).toFixed(1)}/10</span>` : ''}
              </div>
            </div>
            <div class="source-caption-box">
              "${App.escapeHtml(post.caption || '')}"
            </div>
          </div>

          <!-- ADN Psicológico de Atenea -->
          <div class="dna-card">
            <div class="dna-title">
              <span>🧬</span> ADN Psicológico (Atenea Strategy)
            </div>
            <div class="dna-grid">
              <div class="dna-item">
                <span class="dna-label">Concepto Nuclear</span>
                <span class="dna-value">${App.escapeHtml(dna.core_concept || 'Soberanía mental y dicotomía del control ante los embates de la vida.')}</span>
              </div>
              <div class="dna-item">
                <span class="dna-label">Conflicto Humano</span>
                <span class="dna-value">${App.escapeHtml(dna.conflict || 'Impulso de reaccionar vs autodominio del trabajo silencioso.')}</span>
              </div>
              <div class="dna-item">
                <span class="dna-label">Transformación</span>
                <span class="dna-value">${App.escapeHtml(dna.transformation || 'Aceptar lo externo y forjar excelencia implacable.')}</span>
              </div>
              <div class="dna-item">
                <span class="dna-label">Estilo de Gancho</span>
                <span class="dna-value" style="color: #cbd5e1;">${App.escapeHtml((dna.hook_type ? dna.hook_type + ' • ' : '') + (dna.sentence_structure || 'Estructura clásica'))}</span>
              </div>
            </div>
          </div>

          <!-- Auditoría Histórica -->
          <div class="audit-card ${statusClass}">
            <div class="audit-status-badge ${statusClass}">
              ${statusLabel}
            </div>
            <div class="audit-quote-extracted">
              "${App.escapeHtml(post.quote_extracted || post.quote || post.caption || '')}"
            </div>
            <div class="audit-author-tag">
              Autor atribuido: <strong>${App.escapeHtml(post.quote_author || post.author || 'Desconocido')}</strong>
            </div>
            <div class="audit-note">
              ${App.escapeHtml(post.quote_source_note || statusDesc)}
            </div>
          </div>

          <!-- Dirección Visual Cinematográfica -->
          <div class="visual-prompt-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <div class="studio-section-label">🎨 DIRECCIÓN VISUAL (MIDJOURNEY V6)</div>
              <button type="button" class="btn-copy-mini" onclick="RadarController.copyText('${App.escapeHtml(visualPrompt.replace(/'/g, "\\'"))}', this)">
                📋 Copiar Prompt
              </button>
            </div>

            <div class="visual-chips">
              <div class="visual-chip">🗿 <strong>Sujeto:</strong> ${App.escapeHtml(visualDirector.subject || 'Busto imperial de Marco Aurelio en mármol oscuro')}</div>
              <div class="visual-chip">🏛️ <strong>Entorno:</strong> ${App.escapeHtml(visualDirector.environment || 'Templo romano en penumbra')}</div>
              <div class="visual-chip">💡 <strong>Atmósfera:</strong> ${App.escapeHtml(visualDirector.atmosphere || 'Claroscuro dramático, iluminación dorada')}</div>
              <div class="visual-chip">📷 <strong>Cámara:</strong> ${App.escapeHtml(visualDirector.camera || '35mm anamórfico, f/1.8')}</div>
            </div>

            <div class="prompt-text-box" style="margin-top: 8px;">
              ${App.escapeHtml(visualPrompt || 'Cinematic dark fine art portrait of Marcus Aurelius in obsidian marble, chiaroscuro lighting, 8k --ar 4:5 --v 6.0 --no text, typography')}
            </div>
            <span style="font-size: 0.72rem; color: var(--text-dim); display: block; margin-top: 6px;">
              💡 Listo para pegar en Midjourney Discord (--ar 4:5 vertical para Instagram).
            </span>
          </div>
        </div>

        <!-- Columna Derecha: 4 Copys Re-creados por Atenea -->
        <div class="recreation-right-col">
          <div class="recreations-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
            <div>
              <h4 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0 0 4px 0;">
                🏛️ 4 Variaciones Estratégicas por Atenea
              </h4>
              <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                Copys 100% originales calibrados para la voz de @fortaleza_imparable. Haz clic en Copiar o Aprobar.
              </p>
            </div>
            ${currentId ? `
            <button type="button" class="btn-regenerate-ai" onclick="RadarController.regenerateVariations(${currentId}, this)" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 0.8rem; font-weight: 700; border-radius: 8px; border: 1px solid rgba(139,92,246,0.5); background: rgba(139,92,246,0.18); color: #c4b5fd; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;">
              <span>🔄</span> Regenerar con Atenea
            </button>
            ` : ''}
          </div>

          <!-- Opción 1: Gancho Brutal -->
          <div class="copy-variation-card">
            <div class="copy-card-top">
              <div class="copy-angle-tag direct">
                ⚡ Opción 1: Gancho Brutal / Impacto Rápido
              </div>
              <div style="display: flex; gap: 6px;">
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'short', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(optShort.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar
                </button>
              </div>
            </div>
            <div class="copy-content-box">${this.formatCopyText(optShort)}</div>
          </div>

          <!-- Opción 2: Lección Profunda -->
          <div class="copy-variation-card">
            <div class="copy-card-top">
              <div class="copy-angle-tag reflective">
                📖 Opción 2: Sabiduría Clásica / Reflexión Profunda
              </div>
              <div style="display: flex; gap: 6px;">
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'reflective', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(optReflective.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar
                </button>
              </div>
            </div>
            <div class="copy-content-box">${this.formatCopyText(optReflective)}</div>
          </div>

          <!-- Opción 3: Modo Guerrero -->
          <div class="copy-variation-card">
            <div class="copy-card-top">
              <div class="copy-angle-tag warrior">
                ⚔️ Opción 3: Modo Guerrero / Bushido & Disciplina
              </div>
              <div style="display: flex; gap: 6px;">
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'warrior', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(optWarrior.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar
                </button>
              </div>
            </div>
            <div class="copy-content-box">${this.formatCopyText(optWarrior)}</div>
          </div>

          <!-- Opción 4: Modo Estoico -->
          <div class="copy-variation-card">
            <div class="copy-card-top">
              <div class="copy-angle-tag stoic">
                🏛️ Opción 4: Modo Estoico / Virtud & Autodominio
              </div>
              <div style="display: flex; gap: 6px;">
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'stoic', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(optStoic.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar
                </button>
              </div>
            </div>
            <div class="copy-content-box">${this.formatCopyText(optStoic)}</div>
          </div>

        </div>

      </div>
    `;
  },

  formatCopyText(text) {
    if (!text) return '<span style="color: var(--text-dim);">(Sin contenido generado)</span>';
    return App.escapeHtml(text).replace(/\n/g, '<br>');
  },

  async copyText(text, btnEl) {
    if (!text) return;
    try {
      await navigator.clipboard.writeText(text);
      if (btnEl) {
        const orig = btnEl.innerHTML;
        btnEl.innerHTML = '✔ ¡Copiado!';
        btnEl.classList.add('copied');
        setTimeout(() => {
          btnEl.innerHTML = orig;
          btnEl.classList.remove('copied');
        }, 2200);
      }
      App.showToast('¡Texto copiado al portapapeles! Listo para pegar en Meta.', 'success');
    } catch (e) {
      // Fallback
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      App.showToast('¡Texto copiado al portapapeles!', 'success');
    }
  },

  async saveVariationStatus(postId, variationType, btnEl) {
    if (!postId) return;
    const origHtml = btnEl ? btnEl.innerHTML : '';
    try {
      if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = 'Guardando...';
      }
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'save_creation_status',
          post_id: postId,
          variation_type: variationType,
          status: 'approved'
        })
      });
      const data = await res.json();
      if (data.success) {
        if (btnEl) {
          btnEl.innerHTML = '✔ ¡Aprobado!';
          btnEl.classList.add('approved');
        }
        App.showToast('¡Variación aprobada y guardada en la memoria de Atenea!', 'success');
      } else {
        if (btnEl) {
          btnEl.disabled = false;
          btnEl.innerHTML = origHtml;
        }
        App.showToast(data.error || 'Error al guardar estado', 'error');
      }
    } catch (e) {
      if (btnEl) {
        btnEl.disabled = false;
        btnEl.innerHTML = origHtml;
      }
      App.showToast('Error de conexión al guardar estado.', 'error');
    }
  },

  closeRecreateModal() {
    const modal = document.getElementById('modal-recreate-fortaleza');
    if (modal) {
      modal.classList.remove('active');
      document.body.style.overflow = '';
    }
  },

  async regenerateVariations(postId, btnEl) {
    if (!postId) return;
    if (btnEl) {
      btnEl.disabled = true;
      btnEl.style.opacity = '0.6';
      btnEl.innerHTML = '<span class="loading-spinner" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></span> Generando...';
    }
    await this.openRecreateModal(postId, true);
  },

  openAddCreatorModal() {
    App.openModal('modal-add-creator');
  },

  async submitAddCreator(e) {
    if (e) e.preventDefault();
    const platform = document.getElementById('new-creator-platform')?.value || 'instagram';
    const username = document.getElementById('new-creator-username')?.value.trim();
    const displayName = document.getElementById('new-creator-display-name')?.value.trim();

    if (!username) {
      App.showToast('Por favor ingresa el usuario o enlace.', 'error');
      return;
    }

    try {
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'add_creator',
          platform: platform,
          username: username,
          display_name: displayName
        })
      });
      const data = await res.json();
      if (data.success) {
        App.closeModal('modal-add-creator');
        document.getElementById('new-creator-username').value = '';
        document.getElementById('new-creator-display-name').value = '';
        App.showToast(`¡Creador @${username} agregado al radar!`, 'success');
        await this.loadRadar();
      } else {
        App.showToast(`Error: ${data.error || 'No se pudo agregar creador'}`, 'error');
      }
    } catch (err) {
      console.error('Error adding creator:', err);
      App.showToast('Error de conexión al agregar creador.', 'error');
    }
  },

  // ──────────────────────────────────────────────────────────────────────────
  // UTILIDADES
  // ──────────────────────────────────────────────────────────────────────────

  formatNumber(num) {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return String(num);
  },

  formatRelativeTime(dateStr) {
    if (!dateStr) return '';
    try {
      const date = new Date(dateStr.replace(' ', 'T'));
      const now = new Date();
      const diffSecs = Math.floor((now - date) / 1000);

      if (diffSecs < 60) return 'hace un momento';
      if (diffSecs < 3600) return `hace ${Math.floor(diffSecs / 60)}m`;
      if (diffSecs < 86400) return `hace ${Math.floor(diffSecs / 3600)}h`;
      if (diffSecs < 604800) return `hace ${Math.floor(diffSecs / 86400)}d`;
      return date.toLocaleDateString('es-ES', { day: 'numeric', month: 'short' });
    } catch (e) {
      return dateStr;
    }
  }
};
