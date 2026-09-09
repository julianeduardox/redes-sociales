/**
 * TrendsAgent — Frontend Controller
 * Agente de Tendencias del Nicho para XINDRO
 * Gestiona: hashtags, Top 20 grid, AI Picks, Insights y modal Inspirarme
 */

const TrendsAgent = {
  initialized: false,
  niches: [],
  currentNicheFilter: null,

  // ── Utilidad CSRF ──────────────────────────────────────────────────────────
  getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || window.__csrf_token || '';
  },

  // ── Fetch Seguro con CSRF y Ruta Relativa ───────────────────────────────────
  async request(url, options = {}) {
    if (typeof App !== 'undefined' && App.fetchWithCsrf) {
      return App.fetchWithCsrf(url, options);
    }
    const opts = { ...options };
    opts.headers = {
      'Content-Type': 'application/json',
      'X-CSRF-Token': this.getCsrf(),
      ...(opts.headers || {})
    };
    return fetch(url, opts);
  },

  // ── Toast ──────────────────────────────────────────────────────────────────
  toast(msg, type = 'success') {
    if (typeof App !== 'undefined' && App.showToast) {
      App.showToast(msg, type);
    } else {
      console.log(`[TrendsAgent ${type}]`, msg);
    }
  },

  // ── Inicialización ─────────────────────────────────────────────────────────
  async init() {
    if (this.initialized) return;
    this.initialized = true;
    await this.loadNiches();
    await this.loadTrendingPosts();
    await this.loadAiPicks();
    await this.loadInsights();
  },

  // ── Cargar y renderizar nichos ─────────────────────────────────────────────
  async loadNiches() {
    try {
      const res  = await this.request('api/trends.php?action=get_niches');
      const json = await res.json();
      if (!json.success) return;

      this.niches = json.data.niches || [];
      this.renderNicheChips();
      this.renderNicheFilterSelect();

      const countEl = document.getElementById('trends-niche-count');
      if (countEl) countEl.textContent = `(${this.niches.length}/${json.data.max})`;
    } catch (e) {
      console.error('TrendsAgent loadNiches:', e);
    }
  },

  renderNicheChips() {
    const el = document.getElementById('trends-niche-chips');
    if (!el) return;

    if (!this.niches.length) {
      el.innerHTML = `<span style="color:var(--text-dim);font-size:0.8rem;">
        Aún no tienes hashtags. Agrega tu primer nicho arriba ↑
      </span>`;
      return;
    }

    el.innerHTML = this.niches.map(n => `
      <div style="display:inline-flex; align-items:center; gap:6px;
                  background:rgba(124,58,237,0.15); border:1px solid rgba(124,58,237,0.3);
                  border-radius:999px; padding:5px 12px; font-size:0.79rem; color:#c4b5fd;">
        <span>#${this.esc(n.hashtag)}</span>
        <button onclick="TrendsAgent.removeNiche(${n.id}, '${this.esc(n.hashtag)}')"
          style="background:none;border:none;color:#9f7aea;cursor:pointer;font-size:1rem;
                 line-height:1;padding:0;margin-left:2px;" title="Eliminar">×</button>
      </div>
    `).join('');
  },

  renderNicheFilterSelect() {
    const sel = document.getElementById('trends-filter-niche');
    if (!sel) return;
    sel.innerHTML = `<option value="">🌐 Todos los nichos</option>` +
      this.niches.map(n =>
        `<option value="${n.id}">#${this.esc(n.hashtag)}</option>`
      ).join('');
  },

  // ── Agregar niche ──────────────────────────────────────────────────────────
  async addNiche() {
    const input   = document.getElementById('trends-new-hashtag');
    const hashtag = (input?.value || '').trim().replace(/^#/, '');
    if (!hashtag) { this.toast('Escribe un hashtag primero', 'error'); return; }

    try {
      const res  = await this.request('api/trends.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'add_niche', hashtag }),
      });
      const json = await res.json();
      if (json.success) {
        this.toast(`✅ #${hashtag} agregado correctamente`);
        if (input) input.value = '';
        this.initialized = false;
        await this.init();
      } else {
        this.toast(json.error || 'Error al agregar hashtag', 'error');
      }
    } catch (e) {
      this.toast('Error de conexión', 'error');
    }
  },

  // ── Eliminar niche ─────────────────────────────────────────────────────────
  async removeNiche(nicheId, hashtag) {
    if (!confirm(`¿Eliminar #${hashtag} del monitoreo?`)) return;
    try {
      const res  = await this.request('api/trends.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'remove_niche', niche_id: nicheId }),
      });
      const json = await res.json();
      if (json.success) {
        this.toast(`#${hashtag} eliminado del monitoreo`);
        this.initialized = false;
        await this.init();
      } else {
        this.toast(json.error || 'Error al eliminar', 'error');
      }
    } catch (e) {
      this.toast('Error de conexión', 'error');
    }
  },

  // ── Sync Now ───────────────────────────────────────────────────────────────
  async syncNow(nicheId = null) {
    const btn  = document.getElementById('btn-trends-sync');
    const icon = document.getElementById('trends-sync-icon');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }
    if (icon) icon.style.animation = 'spin 1s linear infinite';

    this.toast('🔄 Sincronizando tendencias...', 'info');

    try {
      const body = { action: 'sync_now' };
      if (nicheId) body.niche_id = nicheId;

      const res  = await this.request('api/trends.php', {
        method: 'POST',
        body: JSON.stringify(body),
      });
      const json = await res.json();

      if (json.success && (json.data?.synced > 0 || json.data?.total_saved > 0)) {
        const synced = json.data?.synced || 0;
        const saved  = json.data?.total_saved || 0;
        this.toast(`✅ Sincronización completada — ${synced} hashtag(s) con ${saved} post(s) actualizados`);
        // Reload data
        await this.loadTrendingPosts();
        await this.loadAiPicks();
        await this.loadInsights();
        await this.loadNiches();
      } else {
        const errMsg = json.error || 'Meta no devolvió publicaciones para estos hashtags. Verifica la conexión.';
        this.toast(`⚠️ ${errMsg}`, 'error');
        await this.loadNiches();
      }
    } catch (e) {
      this.toast('Error de conexión al sincronizar', 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.style.opacity = '1'; }
      if (icon) icon.style.animation = '';
    }
  },

  // ── Trending Posts ─────────────────────────────────────────────────────────
  async loadTrendingPosts(nicheId = null) {
    try {
      let url = 'api/trends.php?action=get_trending&limit=20';
      if (nicheId) url += `&niche_id=${nicheId}`;

      const res  = await this.request(url);
      const json = await res.json();
      if (!json.success) return;

      this.renderTrendingGrid(json.data.posts || []);

      // Update last sync
      if (json.data.posts?.length) {
        const lastFetch = json.data.posts[0]?.fetched_at;
        if (lastFetch) {
          const el = document.getElementById('trends-last-sync');
          if (el) el.textContent = 'Última sync: ' + this.formatRelativeTime(lastFetch);
        }
      }
    } catch (e) {
      console.error('TrendsAgent loadTrendingPosts:', e);
    }
  },

  renderTrendingGrid(posts) {
    const grid = document.getElementById('trends-posts-grid');
    if (!grid) return;

    if (!posts.length) {
      grid.innerHTML = `
        <div style="grid-column:1/-1; text-align:center; padding:50px 20px; color:var(--text-dim);">
          <div style="font-size:2.5rem; margin-bottom:12px;">🔥</div>
          <div style="font-weight:700; font-size:0.95rem; margin-bottom:8px;">No hay posts en tendencia aún</div>
          <div style="font-size:0.82rem;">Agrega hashtags de tu nicho y haz clic en "Sincronizar Ahora"</div>
        </div>`;
      return;
    }

    grid.innerHTML = posts.map((post, idx) => this.renderPostCard(post, idx)).join('');
  },

  renderPostCard(post, idx) {
    const rank      = post.trend_rank || (idx + 1);
    const isAiPick  = post.ai_top_pick == 1;
    const score     = parseFloat(post.engagement_score || 0).toFixed(1);
    const likes     = this.formatNumber(post.likes_count);
    const comments  = this.formatNumber(post.comments_count);
    const hashtag   = '#' + this.esc(post.hashtag || '');
    const caption   = this.esc(post.caption_preview || '');
    const capShort  = caption.length > 120 ? caption.substring(0, 120) + '…' : caption;
    const permalink = this.esc(post.permalink || '#');
    const mediaType = (post.media_type || 'image').toLowerCase();
    const typeEmoji = mediaType === 'video' ? '🎬' : (mediaType === 'carousel' ? '🔄' : '🖼️');

    const rankBadge = isAiPick
      ? `<div style="position:absolute;top:10px;left:10px;background:linear-gradient(135deg,#f59e0b,#ef4444);
                     border-radius:6px;padding:3px 8px;font-size:0.68rem;font-weight:800;color:#fff;z-index:2;">
           🤖 AI PICK
         </div>`
      : rank <= 3
        ? `<div style="position:absolute;top:10px;left:10px;background:rgba(0,0,0,0.6);
                       border-radius:6px;padding:3px 8px;font-size:0.7rem;font-weight:700;color:#fff;">
             ${rank === 1 ? '🥇' : rank === 2 ? '🥈' : '🥉'} #${rank}
           </div>`
        : `<div style="position:absolute;top:10px;left:10px;background:rgba(0,0,0,0.5);
                       border-radius:6px;padding:3px 8px;font-size:0.68rem;color:rgba(255,255,255,0.7);">
             #${rank}
           </div>`;

    const mediaPreview = post.media_url
      ? `<img src="${this.esc(post.media_url)}" alt="Post" loading="lazy"
             style="width:100%;height:160px;object-fit:cover;border-radius:10px 10px 0 0;display:block;"
             onerror="this.style.display='none'">`
      : `<div style="width:100%;height:100px;background:linear-gradient(135deg,rgba(124,58,237,0.2),rgba(79,70,229,0.15));
                     border-radius:10px 10px 0 0;display:flex;align-items:center;justify-content:center;font-size:2.5rem;">
           ${typeEmoji}
         </div>`;

    const border = isAiPick
      ? '1px solid rgba(245,158,11,0.4)'
      : `1px solid rgba(255,255,255,0.07)`;
    const glow = isAiPick ? 'box-shadow:0 0 20px rgba(245,158,11,0.15);' : '';

    return `
      <div style="background:#1a1c2e;border-radius:14px;border:${border};overflow:hidden;
                  position:relative;transition:transform 0.2s,box-shadow 0.2s;${glow}"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 12px 40px rgba(0,0,0,0.3)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        ${rankBadge}
        <div style="position:absolute;top:10px;right:10px;z-index:2;background:rgba(0,0,0,0.6);
                    border-radius:6px;padding:3px 8px;font-size:0.68rem;color:#e2e8f0;">
          ${typeEmoji} ${mediaType}
        </div>
        ${mediaPreview}
        <div style="padding:14px;">
          <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px;flex-wrap:wrap;">
            <span style="background:rgba(124,58,237,0.2);border-radius:999px;padding:2px 10px;
                         font-size:0.72rem;color:#c4b5fd;font-weight:600;">${hashtag}</span>
            <span style="font-size:0.72rem;color:var(--text-dim);">${this.formatRelativeTime(post.posted_at || post.fetched_at)}</span>
          </div>
          ${capShort ? `<p style="font-size:0.79rem;color:#cbd5e1;line-height:1.5;margin:0 0 12px;display:-webkit-box;
                              -webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;">${capShort}</p>` : ''}
          <div style="display:flex;gap:12px;font-size:0.78rem;color:var(--text-dim);margin-bottom:12px;flex-wrap:wrap;">
            <span>❤️ ${likes}</span>
            <span>💬 ${comments}</span>
            <span style="color:${score >= 5 ? '#f59e0b' : score >= 2 ? '#a78bfa' : '#64748b'};font-weight:700;">
              📊 ${score}%
            </span>
          </div>
          <div style="display:flex;gap:8px;">
            ${permalink !== '#' ? `
              <a href="${permalink}" target="_blank" rel="noopener"
                 style="flex:1;text-align:center;padding:6px 0;border-radius:8px;font-size:0.76rem;font-weight:600;
                        background:rgba(255,255,255,0.06);color:#94a3b8;text-decoration:none;
                        transition:background 0.2s;border:1px solid rgba(255,255,255,0.08);"
                 onmouseover="this.style.background='rgba(255,255,255,0.12)'"
                 onmouseout="this.style.background='rgba(255,255,255,0.06)'">
                Ver ↗
              </a>` : ''}
            <button onclick="TrendsAgent.openInspireModal(${post.id}, '${hashtag}')"
              style="flex:2;padding:6px 0;border-radius:8px;font-size:0.76rem;font-weight:700;cursor:pointer;
                     background:linear-gradient(135deg,rgba(124,58,237,0.3),rgba(79,70,229,0.2));
                     color:#c4b5fd;border:1px solid rgba(124,58,237,0.3);transition:all 0.2s;"
              onmouseover="this.style.background='linear-gradient(135deg,rgba(124,58,237,0.5),rgba(79,70,229,0.4))'"
              onmouseout="this.style.background='linear-gradient(135deg,rgba(124,58,237,0.3),rgba(79,70,229,0.2))'">
              ✨ Inspirarme
            </button>
          </div>
        </div>
      </div>`;
  },

  // ── AI Top Picks ───────────────────────────────────────────────────────────
  async loadAiPicks() {
    try {
      const res  = await this.request('api/trends.php?action=get_ai_picks');
      const json = await res.json();
      if (!json.success) return;

      const picks   = json.data.picks || [];
      const section = document.getElementById('trends-ai-picks-section');
      const grid    = document.getElementById('trends-ai-picks-grid');
      if (!section || !grid) return;

      if (!picks.length) { section.style.display = 'none'; return; }

      section.style.display = 'block';
      grid.innerHTML = picks.map(p => this.renderAiPickCard(p)).join('');
    } catch (e) {
      console.error('TrendsAgent loadAiPicks:', e);
    }
  },

  renderAiPickCard(post) {
    const score   = parseFloat(post.engagement_score || 0).toFixed(1);
    const likes   = this.formatNumber(post.likes_count);
    const reason  = this.esc(post.ai_pick_reason || '');
    const hashtag = '#' + this.esc(post.hashtag || '');
    const permalink = this.esc(post.permalink || '#');

    return `
      <div style="background:linear-gradient(135deg,rgba(245,158,11,0.08),rgba(239,68,68,0.05));
                  border:1px solid rgba(245,158,11,0.25);border-radius:14px;padding:16px;
                  transition:transform 0.2s;"
           onmouseover="this.style.transform='translateY(-2px)'"
           onmouseout="this.style.transform=''">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
          <div style="font-size:1.3rem;">🤖</div>
          <div>
            <div style="font-size:0.82rem;font-weight:700;color:#fbbf24;">${reason}</div>
            <div style="font-size:0.72rem;color:var(--text-dim);margin-top:2px;">${hashtag} · ❤️ ${likes} · 📊 ${score}%</div>
          </div>
        </div>
        <div style="display:flex;gap:8px;">
          ${permalink !== '#' ? `<a href="${permalink}" target="_blank" rel="noopener"
              style="flex:1;text-align:center;padding:7px 0;border-radius:8px;font-size:0.75rem;
                     font-weight:600;background:rgba(245,158,11,0.15);color:#fbbf24;text-decoration:none;
                     border:1px solid rgba(245,158,11,0.2);">Ver Post ↗</a>` : ''}
          <button onclick="TrendsAgent.openInspireModal(${post.id}, '${hashtag}')"
            style="flex:2;padding:7px 0;border-radius:8px;font-size:0.75rem;font-weight:700;cursor:pointer;
                   background:linear-gradient(135deg,#7c3aed,#4f46e5);color:#fff;border:none;">
            ✨ Inspirarme con este
          </button>
        </div>
      </div>`;
  },

  // ── Insights ───────────────────────────────────────────────────────────────
  async loadInsights() {
    try {
      const res  = await this.request('api/trends.php?action=get_insights');
      const json = await res.json();
      if (!json.success || !json.data) return;

      const bar = document.getElementById('trends-insights-bar');
      if (!bar) return;

      const d = json.data;
      const cards = [];

      if (d.total_posts > 0) {
        cards.push({ emoji: '📊', label: 'Posts monitoreados', value: d.total_posts });
      }
      if (d.best_media_type) {
        cards.push({ emoji: '🎯', label: 'Tipo más viral', value: d.best_media_type.label, sub: `${d.best_media_type.avg_score}% avg eng.` });
      }
      if (d.best_caption_length) {
        cards.push({ emoji: '📝', label: 'Caption óptimo', value: d.best_caption_length.label, sub: `${d.best_caption_length.avg_score}% avg eng.` });
      }
      if (d.niche_performance?.[0]) {
        const top = d.niche_performance[0];
        cards.push({ emoji: '🏆', label: 'Nicho top', value: top.display_name || ('#'+top.hashtag), sub: `${parseFloat(top.avg_score||0).toFixed(1)}% avg eng.` });
      }

      if (!cards.length) { bar.innerHTML = ''; return; }

      bar.innerHTML = cards.map(c => `
        <div style="background:#1a1c2e;border:1px solid rgba(255,255,255,0.07);border-radius:12px;
                    padding:14px 16px;transition:border-color 0.2s;"
             onmouseover="this.style.borderColor='rgba(124,58,237,0.3)'"
             onmouseout="this.style.borderColor='rgba(255,255,255,0.07)'">
          <div style="font-size:1.4rem;margin-bottom:6px;">${c.emoji}</div>
          <div style="font-size:0.72rem;color:var(--text-dim);text-transform:uppercase;
                      letter-spacing:0.05em;font-weight:600;margin-bottom:4px;">${c.label}</div>
          <div style="font-size:0.9rem;font-weight:800;color:#e2e8f0;">${c.value}</div>
          ${c.sub ? `<div style="font-size:0.72rem;color:#a78bfa;margin-top:3px;">${c.sub}</div>` : ''}
        </div>`).join('');
    } catch (e) {
      console.error('TrendsAgent loadInsights:', e);
    }
  },

  // ── Filter by Niche ────────────────────────────────────────────────────────
  filterByNiche(nicheId) {
    this.currentNicheFilter = nicheId ? parseInt(nicheId) : null;
    this.loadTrendingPosts(this.currentNicheFilter);
  },

  // ── Modal Inspirarme ───────────────────────────────────────────────────────
  openInspireModal(trendPostId, hashtag) {
    const modal    = document.getElementById('modal-inspire');
    const loading  = document.getElementById('inspire-loading');
    const results  = document.getElementById('inspire-results');
    const ref      = document.getElementById('inspire-post-ref');

    if (!modal) return;

    if (ref) ref.textContent = `Inspirado en el post de ${hashtag}`;
    if (loading) loading.style.display = 'block';
    if (results) results.style.display = 'none';
    modal.style.display = 'flex';

    // Get current brand voice id
    const brandVoiceId = parseInt(document.getElementById('setting-brand-id')?.value || '1') || 1;

    this.request('api/trends.php', {
      method: 'POST',
      body: JSON.stringify({ action: 'inspire_me', trend_post_id: trendPostId, brand_voice_id: brandVoiceId }),
    })
    .then(r => r.json())
    .then(json => {
      if (loading) loading.style.display = 'none';
      if (!json.success) { this.toast(json.error || 'Error al generar captions', 'error'); this.closeInspireModal(); return; }

      const captions = json.data?.captions || [];
      const source   = json.data?.source || 'heuristic';
      const list     = document.getElementById('inspire-captions-list');
      const badge    = document.getElementById('inspire-source-badge');
      const resultsEl = document.getElementById('inspire-results');

      if (list) {
        list.innerHTML = captions.map((cap, i) => `
          <div style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);
                      border-radius:12px;padding:14px;position:relative;">
            <div style="font-size:0.72rem;font-weight:700;color:#a78bfa;margin-bottom:8px;">
              Variante ${i + 1}
            </div>
            <pre style="font-size:0.82rem;color:#e2e8f0;white-space:pre-wrap;word-wrap:break-word;
                        font-family:inherit;margin:0 0 10px;">${this.esc(cap)}</pre>
            <button onclick="TrendsAgent.copyCaption(this, ${JSON.stringify(cap)})"
              style="padding:6px 14px;background:rgba(124,58,237,0.3);border:1px solid rgba(124,58,237,0.4);
                     border-radius:8px;color:#c4b5fd;font-size:0.75rem;font-weight:600;cursor:pointer;">
              📋 Copiar
            </button>
          </div>`).join('');
      }
      if (badge) badge.textContent = source === 'openrouter' ? '⚡ Generado con IA (OpenRouter)' : '🔧 Generado con motor heurístico local';
      if (resultsEl) resultsEl.style.display = 'block';
    })
    .catch(() => {
      if (loading) loading.style.display = 'none';
      this.toast('Error al conectar con el servidor', 'error');
      this.closeInspireModal();
    });
  },

  closeInspireModal() {
    const modal = document.getElementById('modal-inspire');
    if (modal) modal.style.display = 'none';
  },

  copyCaption(btn, text) {
    navigator.clipboard.writeText(text).then(() => {
      const orig = btn.textContent;
      btn.textContent = '✅ Copiado!';
      btn.style.background = 'rgba(16,185,129,0.3)';
      btn.style.borderColor = 'rgba(16,185,129,0.4)';
      btn.style.color = '#6ee7b7';
      setTimeout(() => {
        btn.textContent = orig;
        btn.style.background = '';
        btn.style.borderColor = '';
        btn.style.color = '';
      }, 2000);
    }).catch(() => this.toast('No se pudo copiar al portapapeles', 'error'));
  },

  // ── Utilidades ─────────────────────────────────────────────────────────────
  esc(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  },

  formatNumber(n) {
    n = parseInt(n) || 0;
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 1000)    return (n / 1000).toFixed(1) + 'K';
    return n.toString();
  },

  formatRelativeTime(dateStr) {
    if (!dateStr) return '--';
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
    if (diff < 60)   return 'hace un momento';
    if (diff < 3600) return `hace ${Math.floor(diff / 60)}m`;
    if (diff < 86400) return `hace ${Math.floor(diff / 3600)}h`;
    return `hace ${Math.floor(diff / 86400)}d`;
  },
};

// Agregar estilos de animación spin para el botón de sync
(function() {
  const style = document.createElement('style');
  style.textContent = `
    @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    #analytics-trends-subview input[type="text"]:focus {
      border-color: rgba(124,58,237,0.5) !important;
      box-shadow: 0 0 0 3px rgba(124,58,237,0.1);
    }
    #modal-inspire { display: none; }
    #modal-inspire[style*="flex"] { display: flex !important; }
  `;
  document.head.appendChild(style);
})();
