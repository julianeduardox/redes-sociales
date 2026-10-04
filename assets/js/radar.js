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
            <button type="button" class="btn-creator-delete" onclick="RadarController.deleteCreator(${c.id}, '${App.escapeHtml(c.username).replace(/'/g, "\\'")}', event)" title="Eliminar cuenta de referencia del radar">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
              </svg>
            </button>
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
              ${p.creative_fit_score > 0 ? `<span class="radar-stat-pill creative-fit" title="Score de Afinidad Filosófica con Fortaleza Imparable">🏛️ ${Number(p.creative_fit_score).toFixed(1)}/10</span>` : ''}
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
              <span class="btn-badge-brand">4 Frases + Midjourney</span>
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

  currentSourceType: 'inspiration',

  async openRecreateModal(postId, forceRegenerate = false, sourceType = null, customVisualText = null, customCaption = null) {
    if (sourceType) {
      this.currentSourceType = sourceType;
    }
    const activeSourceType = this.currentSourceType || 'inspiration';
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
            ${activeSourceType === 'historical'
              ? 'Deconstruyendo tu publicación histórica de mayor impacto y aplicando las directrices empíricas aprendidas de tu audiencia...'
              : (forceRegenerate 
                ? 'Explorando nuevos ángulos conceptuales, hooks y prompt visual para @fortaleza_imparable...'
                : 'Consultando la biblioteca de textos clásicos (Meditaciones, Epicteto, Séneca, Dokkodo) y calibrando el tono de @fortaleza_imparable...')}
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
          force_regenerate: forceRegenerate ? 1 : 0,
          source_type: activeSourceType,
          custom_visual_text: customVisualText,
          custom_caption: customCaption
        })
      });

      let recData;
      try {
        recData = await recRes.json();
      } catch (jsonErr) {
        throw new Error('Respuesta inválida del servidor o tiempo de espera agotado');
      }

      if (recData && recData.success && recData.data) {
        const postObj = recData.data.post || recData.data.reference_post || {};
        const recreationsObj = recData.data.recreations || {};
        const phrasesObj = recData.data.phrases || {};
        const dnaObj = recData.data.dna || {};
        const visualDirectorObj = recData.data.visual_director || {};
        const oppScore = recData.data.opportunity_score || postObj.opportunity_score || 0;
        const creativeFitScore = recData.data.creative_fit_score || postObj.creative_fit_score || 0;
        const whyItWorks = recData.data.why_it_works || dnaObj.why_explanation || '';

        this.renderRecreationStudio(postObj, recreationsObj, postId, dnaObj, visualDirectorObj, oppScore, creativeFitScore, whyItWorks, phrasesObj);
        if (forceRegenerate) {
          App.showToast('¡Nuevas 4 frases aforísticas generadas por Atenea para @fortaleza_imparable!', 'success');
        }
      } else {
        const errMsg = recData?.error || 'No se pudieron generar las recreaciones';
        container.innerHTML = `
          <div style="padding: 40px; text-align: center; color: var(--accent-rose);">
            <div style="font-size: 1.1rem; font-weight: 700; margin-bottom: 8px;">⚠️ ${App.escapeHtml(errMsg)}</div>
            <div style="margin-top: 14px;">
              <button type="button" class="btn-primary-action" onclick="RadarController.openRecreateModal(${postId}, true, '${activeSourceType}')">
                🔄 Reintentar
              </button>
            </div>
          </div>
        `;
      }
    } catch (err) {
      console.error('Error generating recreations:', err);
      container.innerHTML = `
        <div style="padding: 40px 20px; text-align: center;">
          <div style="font-size: 1.1rem; font-weight: 700; color: #f87171; margin-bottom: 8px;">
            ⚠️ Error de conexión con el motor de IA
          </div>
          <p style="font-size: 0.85rem; color: var(--text-dim); max-width: 440px; margin: 0 auto 16px auto;">
            El servidor tardó en responder o la conexión fue interrumpida. Puedes reintentar ahora mismo.
          </p>
          <button type="button" class="btn-primary-action" style="padding: 8px 18px; font-weight: 700;" onclick="RadarController.openRecreateModal(${postId}, true, '${activeSourceType}')">
            🔄 Reintentar con Atenea
          </button>
        </div>
      `;
    }
  },

  renderRecreationStudio(post, recreations, postId = null, dna = null, visualDirector = null, opportunityScore = null, creativeFitScore = null, whyItWorks = '', phrases = null) {
    const container = document.getElementById('recreate-modal-content');
    if (!container) return;

    post = post || {};
    recreations = recreations || {};
    phrases = phrases || {};
    const currentId = postId || post.id || 0;

    // ADN Psicológico
    dna = dna || recreations.content_dna || (post.content_dna ? (typeof post.content_dna === 'string' ? JSON.parse(post.content_dna) : post.content_dna) : null) || {};
    visualDirector = visualDirector || recreations.visual_director || {};
    const scoreVal = opportunityScore || post.opportunity_score || 0;
    const cFitVal = creativeFitScore || post.creative_fit_score || dna.creative_fit_score || 0;
    const whyExplanation = whyItWorks || dna.why_explanation || 'Desarma la complacencia y ancla el impacto en la soberanía interior y la disciplina.';

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

    // 4 Frases Aforísticas cortas (8 a 22 palabras cada una)
    const phraseHook = phrases.hook || recreations.phrase_hook || recreations.option_short || '';
    const phraseContrarian = phrases.contrarian || recreations.phrase_contrarian || recreations.option_reflective || '';
    const phraseWarrior = phrases.warrior || recreations.phrase_warrior || recreations.option_warrior || '';
    const phraseStoic = phrases.stoic || recreations.phrase_stoic || recreations.option_stoic || '';
    const visualPrompt = visualDirector.midjourney_prompt || recreations.visual_prompt || recreations.image_prompt || '';

    const countWords = (t) => t ? t.trim().split(/\s+/).filter(Boolean).length : 0;

    // Separación Estricta de Fuentes: VISUAL_TEXT (Placa/Imagen) vs CAPTION_TEXT (Pie de foto)
    const visualText = post.visual_text || '';
    const visualSource = post.visual_text_source || 'NONE';
    const visualStatus = post.visual_text_status || (visualText ? 'CONFIRMED' : 'UNAVAILABLE');
    const visualConfidence = (post.visual_text_confidence !== undefined && post.visual_text_confidence !== null) ? Math.round(Number(post.visual_text_confidence) * 100) : 0;
    const captionText = post.caption_text || post.caption || '';
    const mediaUrl = post.media_url || '';

    let visualBadgeClass = 'badge-unavailable';
    let visualBadgeText = '⚠️ No se pudo determinar el texto de la imagen. Confirma o introduce manualmente la frase de la placa.';
    let visualSourceLabel = 'Fuente: ' + visualSource;

    if (visualStatus === 'CONFIRMED') {
      visualBadgeClass = 'badge-confirmed';
      visualBadgeText = `✅ Texto Detectado por Visión (${visualConfidence}% confianza)`;
    } else if (visualStatus === 'USER_CONFIRMED') {
      visualBadgeClass = 'badge-user';
      visualBadgeText = '👤 Frase Confirmada / Editada por el Usuario';
    } else if (visualStatus === 'NO_TEXT') {
      visualBadgeClass = 'badge-no-text';
      visualBadgeText = 'ℹ️ Sin texto visible identificado en la imagen';
    } else if (visualStatus === 'NEEDS_REVIEW') {
      visualBadgeClass = 'badge-review';
      visualBadgeText = `⚠️ Requiere Revisión (${visualConfidence}% confianza)`;
    }

    const isHistorical = (this.currentSourceType === 'historical' || (post.quote_author && post.quote_author.includes('Fortaleza Imparable')) || (post.quote_source_note && post.quote_source_note.includes('Top Viral')));
    const displayCreator = isHistorical ? 'fortaleza_imparable (Top Viral Propio)' : (post.creator_username ? `@${post.creator_username}` : '@nicho');
    const sourceSectionTitle = isHistorical ? '🏆 PUBLICACIÓN HISTÓRICA TOP VIRAL (FORTALEZA IMPARABLE)' : 'ORIGEN DE LA INSPIRACIÓN (SEPARACIÓN DE FUENTES)';

    container.innerHTML = `
      <div class="recreation-studio-layout">

        <!-- Columna Izquierda: Origen Dual, Scores, Insight y ADN -->
        <div class="recreation-left-col">
          <div class="recreation-source-card">
            <div class="studio-section-label" style="${isHistorical ? 'color: #34d399;' : ''}">${sourceSectionTitle}</div>
            <div class="source-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <span class="source-creator" style="${isHistorical ? 'color: #34d399; font-weight: 800;' : ''}">${App.escapeHtml(displayCreator)}</span>
              <div style="display: flex; gap: 6px; align-items: center;">
                <span class="source-likes">❤️ ${this.formatNumber(post.likes_count || 0)}</span>
                ${scoreVal > 0 ? `<span class="radar-stat-pill opportunity" style="font-size: 0.72rem; padding: 2px 7px;" title="Score de Oportunidad Viral">🎯 ${Number(scoreVal).toFixed(1)}/10</span>` : ''}
                ${cFitVal > 0 ? `<span class="radar-stat-pill creative-fit" style="font-size: 0.72rem; padding: 2px 7px;" title="Afinidad con Fortaleza Imparable">🏛️ ${Number(cFitVal).toFixed(1)}/10</span>` : ''}
              </div>
            </div>

            <!-- SECCIÓN 1: TEXTO DE LA IMAGEN / PLACA VISUAL (NÚCLEO PRINCIPAL) -->
            <div class="source-input-group" style="margin-bottom: 12px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <label class="source-dual-label" style="margin: 0; font-weight: 700; color: #f1f5f9; display: flex; align-items: center; gap: 5px;">
                  📸 TEXTO DE LA PLACA / IMAGEN:
                </label>
                <div style="display: flex; gap: 6px;">
                  ${mediaUrl ? `
                  <button type="button" class="btn-vision-extract" onclick="RadarController.extractVisionText(${currentId}, this)" title="Escanear imagen con Visión IA">
                    🔍 Leer con Visión IA
                  </button>
                  ` : ''}
                  <button type="button" class="btn-save-visual" onclick="RadarController.saveManualVisualText(${currentId}, this)" title="Guardar texto editado">
                    💾 Guardar
                  </button>
                </div>
              </div>

              <textarea id="studio-visual-text" class="studio-text-input" rows="2" placeholder="Escribe o confirma aquí la frase que aparece en la placa de la imagen...">${App.escapeHtml(visualText)}</textarea>

              <div id="studio-visual-meta" class="visual-meta-row" style="margin-top: 5px; display: flex; justify-content: space-between; align-items: center; font-size: 0.73rem; flex-wrap: wrap; gap: 4px;">
                <span class="visual-status-pill ${visualBadgeClass}" id="studio-visual-status-pill">${visualBadgeText}</span>
                <span class="visual-source-tag" id="studio-visual-source-tag">${App.escapeHtml(visualSourceLabel)}</span>
              </div>
            </div>

            <!-- SECCIÓN 2: COPY / PIE DE FOTO (CONTEXTO SECUNDARIO) -->
            <div class="source-input-group" style="margin-bottom: 8px;">
              <label class="source-dual-label" style="display: block; margin-bottom: 4px; font-weight: 600; color: #94a3b8;">
                📝 COPY / PIE DE FOTO (CONTEXTO SECUNDARIO):
              </label>
              <textarea id="studio-caption-text" class="studio-text-input secondary" rows="3" placeholder="Pie de foto original de la publicación...">${App.escapeHtml(captionText)}</textarea>
            </div>
          </div>

          <!-- ¿Por qué funciona? (Mecanismo Psicológico de Atenea) -->
          <div class="why-works-card">
            <div class="why-works-title">
              <span>💡</span> ¿Por qué funciona? (Mecanismo Psicológico)
            </div>
            <div class="why-works-text">
              ${App.escapeHtml(whyExplanation)}
            </div>
          </div>

          <!-- ADN Psicológico Extendido -->
          <div class="dna-card">
            <div class="dna-title">
              <span>🧬</span> ADN Psicológico & Filosófico
            </div>
            <div class="dna-grid">
              <div class="dna-item">
                <span class="dna-label">Concepto Nuclear</span>
                <span class="dna-value">${App.escapeHtml(dna.core_concept || 'Soberanía mental y dicotomía del control ante la adversidad.')}</span>
              </div>
              ${dna.audience_pain ? `
              <div class="dna-item">
                <span class="dna-label">Herida Oculta de la Audiencia</span>
                <span class="dna-value" style="color: #fca5a5;">${App.escapeHtml(dna.audience_pain)}</span>
              </div>
              ` : ''}
              ${dna.belief_challenged ? `
              <div class="dna-item">
                <span class="dna-label">Creencia Desafiada</span>
                <span class="dna-value" style="color: #fde047;">${App.escapeHtml(dna.belief_challenged)}</span>
              </div>
              ` : ''}
              ${dna.emotional_trigger ? `
              <div class="dna-item">
                <span class="dna-label">Gatillo Emocional</span>
                <span class="dna-value" style="color: #93c5fd;">${App.escapeHtml(dna.emotional_trigger)}</span>
              </div>
              ` : ''}
              ${dna.shareability_mechanism ? `
              <div class="dna-item">
                <span class="dna-label">Mecanismo de Viralidad / Guardado</span>
                <span class="dna-value" style="color: #86efac;">${App.escapeHtml(dna.shareability_mechanism)}</span>
              </div>
              ` : ''}
              <div class="dna-item">
                <span class="dna-label">Estilo de Gancho & Sintaxis</span>
                <span class="dna-value" style="color: #cbd5e1;">${App.escapeHtml((dna.hook_type ? dna.hook_type + ' • ' : '') + (dna.sentence_structure || 'Estructura aforística sobria'))}</span>
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
              💡 Formato vertical --ar 4:5 listo para portadas de Reels o placas en @fortaleza_imparable.
            </span>
          </div>
        </div>

        <!-- Columna Derecha: 4 Frases Aforísticas para Placas de @fortaleza_imparable -->
        <div class="recreation-right-col">
          <div class="recreations-header" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 8px;">
            <div>
              <h4 style="font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0 0 4px 0;">
                🏛️ 4 Frases de Alto Impacto para Placas / Reels
              </h4>
              <p style="font-size: 0.8rem; color: var(--text-muted); margin: 0;">
                Frases aforísticas afiladas (8 a 22 palabras) forjadas por Atenea para estampar en imagen o portada.
              </p>
            </div>
            ${currentId ? `
            <button type="button" class="btn-regenerate-ai" onclick="RadarController.regenerateVariations(${currentId}, this)" style="display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; font-size: 0.8rem; font-weight: 700; border-radius: 8px; border: 1px solid rgba(139,92,246,0.5); background: rgba(139,92,246,0.18); color: #c4b5fd; cursor: pointer; transition: all 0.2s ease; white-space: nowrap;">
              <span>🔄</span> Regenerar con Atenea
            </button>
            ` : ''}
          </div>

          <!-- Frase 1: Gancho Brutal / Golpe Psicológico -->
          <div class="phrase-plate-card">
            <div class="phrase-plate-header">
              <div class="copy-angle-tag direct">
                ⚡ Opción 1: Gancho Brutal / Golpe Psicológico
              </div>
              <div style="display: flex; gap: 6px; align-items: center;">
                <span class="word-count-pill">${countWords(phraseHook)} palabras</span>
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'hook_brutal', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(phraseHook.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar Frase
                </button>
              </div>
            </div>
            <div class="phrase-plate-box">
              <p class="phrase-plate-text">“${App.escapeHtml(phraseHook)}”</p>
            </div>
          </div>

          <!-- Frase 2: Antítesis / Rompe-Creencias -->
          <div class="phrase-plate-card">
            <div class="phrase-plate-header">
              <div class="copy-angle-tag reflective">
                🔄 Opción 2: Antítesis / Rompe-Creencias
              </div>
              <div style="display: flex; gap: 6px; align-items: center;">
                <span class="word-count-pill">${countWords(phraseContrarian)} palabras</span>
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'contrarian', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(phraseContrarian.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar Frase
                </button>
              </div>
            </div>
            <div class="phrase-plate-box">
              <p class="phrase-plate-text">“${App.escapeHtml(phraseContrarian)}”</p>
            </div>
          </div>

          <!-- Frase 3: Bushido & Disciplina de Guerra (Dokkōdō / Musashi) -->
          <div class="phrase-plate-card">
            <div class="phrase-plate-header">
              <div class="copy-angle-tag warrior">
                ⚔️ Opción 3: Bushido / Disciplina de Guerra (Musashi)
              </div>
              <div style="display: flex; gap: 6px; align-items: center;">
                <span class="word-count-pill">${countWords(phraseWarrior)} palabras</span>
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'warrior', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(phraseWarrior.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar Frase
                </button>
              </div>
            </div>
            <div class="phrase-plate-box">
              <p class="phrase-plate-text">“${App.escapeHtml(phraseWarrior)}”</p>
            </div>
          </div>

          <!-- Frase 4: Soberanía Mental / Estoicismo Clásico -->
          <div class="phrase-plate-card">
            <div class="phrase-plate-header">
              <div class="copy-angle-tag stoic">
                🏛️ Opción 4: Soberanía Mental / Estoicismo Clásico
              </div>
              <div style="display: flex; gap: 6px; align-items: center;">
                <span class="word-count-pill">${countWords(phraseStoic)} palabras</span>
                <button type="button" class="btn-approve-action" onclick="RadarController.saveVariationStatus(${currentId}, 'stoic', this)">
                  ⭐ Aprobar
                </button>
                <button type="button" class="btn-copy-action" onclick="RadarController.copyText('${App.escapeHtml(phraseStoic.replace(/'/g, "\\'"))}', this)">
                  📋 Copiar Frase
                </button>
              </div>
            </div>
            <div class="phrase-plate-box">
              <p class="phrase-plate-text">“${App.escapeHtml(phraseStoic)}”</p>
            </div>
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
    const visualInput = document.getElementById('studio-visual-text');
    const captionInput = document.getElementById('studio-caption-text');
    const customVisualText = visualInput ? visualInput.value.trim() : null;
    const customCaption = captionInput ? captionInput.value.trim() : null;

    if (btnEl) {
      btnEl.disabled = true;
      btnEl.style.opacity = '0.6';
      btnEl.innerHTML = '<span class="loading-spinner" style="width: 14px; height: 14px; display: inline-block; vertical-align: middle;"></span> Generando con Atenea...';
    }
    await this.openRecreateModal(postId, true, this.currentSourceType || 'inspiration', customVisualText, customCaption);
  },

  async extractVisionText(postId, btnEl) {
    if (!postId) return;
    const originalText = btnEl ? btnEl.innerHTML : '';
    if (btnEl) {
      btnEl.disabled = true;
      btnEl.innerHTML = '<span class="loading-spinner mini"></span> Leyendo...';
    }

    try {
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'extract_vision_text',
          post_id: postId
        })
      });
      const data = await res.json();
      if (data.success && data.data) {
        const d = data.data;
        const textarea = document.getElementById('studio-visual-text');
        const pill = document.getElementById('studio-visual-status-pill');
        const sourceTag = document.getElementById('studio-visual-source-tag');

        if (d.has_text && d.text) {
          if (textarea) textarea.value = d.text;
          if (pill) {
            pill.className = 'visual-status-pill badge-confirmed';
            pill.textContent = `✅ Texto Detectado (${Math.round((d.confidence || 0.94) * 100)}% confianza)`;
          }
          if (sourceTag) sourceTag.textContent = 'Fuente: VISION';
          App.showToast('¡Texto de placa extraído exitosamente con Visión IA!', 'success');
        } else {
          if (pill) {
            pill.className = 'visual-status-pill badge-no-text';
            pill.textContent = 'ℹ️ Sin texto visible identificado en la imagen';
          }
          if (sourceTag) sourceTag.textContent = 'Fuente: VISION';
          App.showToast('No se detectó texto en la imagen. Puedes introducirlo manualmente.', 'info');
        }
      } else {
        App.showToast(`Error de visión: ${data.error || 'No disponible'}`, 'error');
      }
    } catch (err) {
      console.error('Vision extraction error:', err);
      App.showToast('Error de conexión al procesar la imagen.', 'error');
    } finally {
      if (btnEl) {
        btnEl.disabled = false;
        btnEl.innerHTML = originalText;
      }
    }
  },

  async saveManualVisualText(postId, btnEl) {
    if (!postId) return;
    const textarea = document.getElementById('studio-visual-text');
    const val = textarea ? textarea.value.trim() : '';
    const originalText = btnEl ? btnEl.innerHTML : '';

    if (btnEl) {
      btnEl.disabled = true;
      btnEl.innerHTML = 'Guardando...';
    }

    try {
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'save_visual_text',
          post_id: postId,
          visual_text: val
        })
      });
      const data = await res.json();
      if (data.success) {
        const pill = document.getElementById('studio-visual-status-pill');
        const sourceTag = document.getElementById('studio-visual-source-tag');
        if (pill) {
          pill.className = 'visual-status-pill badge-user';
          pill.textContent = '👤 Confirmado por el Usuario';
        }
        if (sourceTag) sourceTag.textContent = 'Fuente: USER';
        App.showToast('Texto de placa guardado correctamente.', 'success');
      } else {
        App.showToast(`Error al guardar: ${data.error || ''}`, 'error');
      }
    } catch (err) {
      console.error('Save visual text error:', err);
      App.showToast('Error de red al guardar el texto.', 'error');
    } finally {
      if (btnEl) {
        btnEl.disabled = false;
        btnEl.innerHTML = originalText;
      }
    }
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

  async deleteCreator(creatorId, username, event) {
    if (event) {
      event.preventDefault();
      event.stopPropagation();
    }
    if (!creatorId) return;

    const confirmed = confirm(
      `¿Deseas eliminar la cuenta de referencia @${username} del Radar?\n\n` +
      `• Se quitará del monitoreo de creadores.\n` +
      `• Se removerán sus publicaciones asociadas del catálogo de inspiración.`
    );
    if (!confirmed) return;

    try {
      App.showToast(`Eliminando cuenta @${username}...`, 'info');
      const res = await App.fetchWithCsrf('api/inspiration.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'remove_creator',
          creator_id: creatorId,
          delete_posts: true
        })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast(data.message || `Cuenta @${username} eliminada con éxito.`, 'success');
        if (this.selectedCreatorId == creatorId) {
          this.selectedCreatorId = 'all';
        }
        await this.loadRadar();
      } else {
        App.showToast(`Error: ${data.error || 'No se pudo eliminar la cuenta'}`, 'error');
      }
    } catch (err) {
      console.error('Error deleting creator:', err);
      App.showToast('Error de conexión al eliminar la cuenta de referencia.', 'error');
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
