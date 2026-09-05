/**
 * Analytics & Per-Post Performance Controller
 * Hardened with XSS Prevention & Meta Graph API Insights
 */

const AnalyticsController = {
  currentSubtab: 'overview',
  postsPlatform: 'all',
  postsSort: 'recent',
  cachedAnalyticsData: null,

  switchSubtab(subtab) {
    const validSubtabs = ['overview', 'posts'];
    const activeSubtab = validSubtabs.includes(subtab) ? subtab : 'overview';
    this.currentSubtab = activeSubtab;
    
    // Persist subtab state across F5 reloads
    try {
      sessionStorage.setItem('xindro_analytics_subtab', activeSubtab);
      localStorage.setItem('xindro_analytics_subtab', activeSubtab);
    } catch (e) {}
    
    document.querySelectorAll('.analytics-subnav .subtab-btn').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.subtab === activeSubtab);
    });

    const overviewView = document.getElementById('analytics-overview-subview');
    const postsView = document.getElementById('analytics-posts-subview');

    if (overviewView) overviewView.style.display = (activeSubtab === 'overview') ? 'block' : 'none';
    if (postsView) postsView.style.display = (activeSubtab === 'posts') ? 'block' : 'none';

    this.loadAnalytics();
  },

  filterPostsPlatform(platform) {
    this.postsPlatform = platform;
    document.querySelectorAll('#analytics-posts-subview .platform-pill').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.postPlatform === platform);
    });
    this.loadAnalytics();
  },

  changePostsSort(sortVal) {
    this.postsSort = sortVal;
    this.loadAnalytics();
  },

  async loadAnalytics() {
    const container = document.getElementById('analytics-view-content');
    if (!container) return;

    try {
      const url = `api/analytics.php?platform=${encodeURIComponent(this.postsPlatform)}&sort=${encodeURIComponent(this.postsSort)}`;
      const response = await App.fetchWithCsrf(url);
      const res = await response.json();

      if (res.success) {
        this.cachedAnalyticsData = res;
        this.renderOverview(res);
        this.renderPosts(res.posts || []);
      }
    } catch (err) {
      console.error("Error loading analytics:", err);
    }
  },

  renderOverview(data) {
    const container = document.getElementById('analytics-view-content');
    if (!container) return;

    const stats = data.stats || {};
    const sentiments = data.sentiment_distribution || {};

    const total = parseInt(stats.total_comments, 10) || 1;
    const leadsCount = parseInt(sentiments.lead, 10) || 0;
    const urgentCount = parseInt(sentiments.urgent, 10) || 0;
    const positiveCount = parseInt(sentiments.positive, 10) || 0;
    const questionsCount = parseInt(sentiments.question, 10) || 0;

    const leadsPercent = Math.min(100, Math.max(0, Math.round((leadsCount / total) * 100)));
    const urgentPercent = Math.min(100, Math.max(0, Math.round((urgentCount / total) * 100)));
    const positivePercent = Math.min(100, Math.max(0, Math.round((positiveCount / total) * 100)));
    const questionsPercent = Math.min(100, Math.max(0, Math.round((questionsCount / total) * 100)));

    const formatNumber = (num) => {
      const n = parseInt(num, 10) || 0;
      if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
      if (n >= 10000) return (n / 1000).toFixed(1) + 'K';
      if (n >= 1000) return n.toLocaleString();
      return n.toString();
    };

    container.innerHTML = `
      <!-- Top Meta Insights & Engagement KPIs -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">👁️ Visualizaciones Totales (Views)</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-cyan); margin: 6px 0;">${formatNumber(stats.total_impressions || 0)}</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${formatNumber(stats.total_reach || 0)} personas alcanzadas en Meta</div>
        </div>

        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">👥 Alcance Único (Reach)</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: #a855f7; margin: 6px 0;">${formatNumber(stats.total_reach || 0)}</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${stats.total_posts || 0} publicaciones analizadas</div>
        </div>

        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">🔥 Engagement Rate %</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-emerald); margin: 6px 0;">${(parseFloat(stats.avg_engagement_rate) || 0).toFixed(1)}%</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${formatNumber(stats.total_post_likes || 0)} likes • ${formatNumber(stats.total_saved || 0)} guardados</div>
        </div>

        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">⚡ Tasa de Respuesta IA</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: var(--primary); margin: 6px 0;">${(parseFloat(stats.reply_rate_percent) || 0).toFixed(1)}%</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${parseInt(stats.replied_count, 10) || 0} de ${parseInt(stats.total_comments, 10) || 0} comentarios atendidos</div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Sentiment Breakdown -->
        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <h4 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 16px;">🎯 Distribución de Intención & Sentimiento de Audiencia</h4>
          <div style="display: flex; flex-direction: column; gap: 14px;">
            <div>
              <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px;">
                <span>🧠 Preguntas Filosóficas & Consejos (${questionsCount})</span>
                <span style="font-weight: 700; color: var(--accent-amber);">${questionsPercent}%</span>
              </div>
              <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                <div style="width: ${questionsPercent}%; height: 100%; background: var(--accent-amber);"></div>
              </div>
            </div>

            <div>
              <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px;">
                <span>🛡️ Apoyo Emocional & Resiliencia (${urgentCount})</span>
                <span style="font-weight: 700; color: var(--accent-rose);">${urgentPercent}%</span>
              </div>
              <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                <div style="width: ${urgentPercent}%; height: 100%; background: var(--accent-rose);"></div>
              </div>
            </div>

            <div>
              <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px;">
                <span>✨ Testimonios de Impacto & Elogios (${positiveCount})</span>
                <span style="font-weight: 700; color: var(--accent-cyan);">${positivePercent}%</span>
              </div>
              <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                <div style="width: ${positivePercent}%; height: 100%; background: var(--accent-cyan);"></div>
              </div>
            </div>

            <div>
              <div style="display: flex; justify-content: space-between; font-size: 0.82rem; margin-bottom: 4px;">
                <span>🎯 Leads & Membresías / Cursos (${leadsCount})</span>
                <span style="font-weight: 700; color: var(--accent-emerald);">${leadsPercent}%</span>
              </div>
              <div style="height: 8px; background: rgba(255,255,255,0.06); border-radius: 4px; overflow: hidden;">
                <div style="width: ${leadsPercent}%; height: 100%; background: var(--accent-emerald);"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Strategy & Fast Actions Card -->
        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: flex; flex-direction: column; justify-content: space-between;">
          <div>
            <h4 style="font-size: 0.95rem; font-weight: 800; margin-bottom: 12px; color: var(--accent-cyan);">💡 Estrategia de Crecimiento & Algoritmo Meta</h4>
            <p style="font-size: 0.84rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 14px;">
              Responder a los comentarios dentro de los primeros 60 minutos aumenta la retención y la distribución en el feed de Instagram y Reels hasta en un <strong>320%</strong>.
            </p>
            <div style="background: rgba(99, 102, 241, 0.08); border: 1px solid var(--border-active); padding: 12px; border-radius: var(--radius-sm); font-size: 0.8rem; color: #a5b4fc;">
              ⚡ <strong>Piloto Automático:</strong> Responder comentarios de score <strong>&ge; 75</strong> con preguntas introspectivas maximiza el número de respuestas por hilo.
            </div>
          </div>

          <div style="display: flex; gap: 10px; margin-top: 18px;">
            <button class="btn-primary-action" style="flex: 1; justify-content: center;" onclick="AnalyticsController.switchSubtab('posts')">
              📱 Ver Publicaciones Individuales
            </button>
            <button class="btn-primary-action" style="background: rgba(255,255,255,0.06);" onclick="App.switchTab('inbox')">
              📥 Ir al Inbox
            </button>
          </div>
        </div>
      </div>
    `;
  },

  renderPosts(posts) {
    const container = document.getElementById('posts-grid-container');
    if (!container) return;

    if (posts.length === 0) {
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 40px 20px; text-align: center; color: var(--text-dim);">
          <div style="font-size: 2.5rem; margin-bottom: 10px;">📱</div>
          <h4 style="font-size: 1rem; color: #fff; font-weight: 700;">No hay publicaciones en este filtro</h4>
          <p style="font-size: 0.82rem; margin-top: 4px;">Sincroniza con Meta o cambia los filtros de plataforma.</p>
        </div>
      `;
      return;
    }

    const formatNumber = (num) => {
      const n = parseInt(num, 10) || 0;
      if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
      if (n >= 10000) return (n / 1000).toFixed(1) + 'K';
      if (n >= 1000) return n.toLocaleString();
      return n.toString();
    };

    container.innerHTML = posts.map(p => {
      const safeImg = App.sanitizeUrl(p.media_url, 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=480&h=320&fit=crop&auto=format&q=75');
      const rawLikes = parseInt(p.total_likes, 10) || 0;
      const rawComments = parseInt(p.total_comments, 10) || 0;
      const rawShares = parseInt(p.total_shares, 10) || 0;
      const rawSaved = parseInt(p.saved_count, 10) || 0;
      let rawReach = parseInt(p.reach, 10) || 0;
      let rawImpressions = parseInt(p.impressions, 10) || 0;

      if (rawImpressions === 0 && rawReach > 0) {
        rawImpressions = rawReach;
      }
      if (rawReach === 0 && rawImpressions > 0) {
        rawReach = rawImpressions;
      }
      if (rawReach > 0 && rawImpressions < rawReach) {
        rawImpressions = rawReach;
      }

      const safeLikes = formatNumber(rawLikes);
      const safeComments = formatNumber(rawComments);
      const safeShares = formatNumber(rawShares);
      const safeImpressions = formatNumber(rawImpressions);
      const safeReach = formatNumber(rawReach);
      const safeSaved = formatNumber(rawSaved);
      const engRate = (typeof p.engagement_rate !== 'undefined' && p.engagement_rate !== null) ? parseFloat(p.engagement_rate).toFixed(1) : '0.0';
      const mediaType = (p.media_type || 'image').toLowerCase();

      let mediaIcon = '📷 Imagen';
      if (mediaType === 'video' || mediaType === 'reel' || mediaType === 'reels') mediaIcon = '🎥 Video / Reel';
      else if (mediaType === 'carousel' || mediaType === 'carousel_album') mediaIcon = '📑 Carrusel';

      // Sentiment counts
      const localComments = parseInt(p.local_comments_count, 10) || 0;
      const positiveCount = parseInt(p.post_positive_count, 10) || 0;
      const questionCount = parseInt(p.post_questions_count, 10) || 0;
      const urgentCount = parseInt(p.post_urgent_count, 10) || 0;
      const leadsCount = parseInt(p.post_leads_count, 10) || 0;

      const totalSent = Math.max(1, positiveCount + questionCount + urgentCount + leadsCount);
      const posPct = Math.round((positiveCount / totalSent) * 100);
      const qPct = Math.round((questionCount / totalSent) * 100);
      const urgPct = Math.round((urgentCount / totalSent) * 100);
      const leadPct = Math.round((leadsCount / totalSent) * 100);

      const isInstagram = p.platform === 'instagram';

      return `
        <div class="post-analytics-card">
          <div class="post-card-media-wrapper">
            <img src="${safeImg}" class="post-card-media" loading="lazy" decoding="async" alt="Media preview" />
            <div class="media-type-badge">${mediaIcon}</div>
            <div class="post-platform-badge ${isInstagram ? 'instagram' : 'facebook'}">
              ${isInstagram ? '📸 Instagram' : '📘 Facebook'}
            </div>
          </div>

          <div class="post-card-content">
            <div>
              <div class="post-card-date">
                📅 Publicado: ${p.posted_at ? App.escapeHtml(p.posted_at.substring(0, 16)) : 'Reciente'}
              </div>

              <div class="post-card-caption" title="${safeCaption}">
                ${safeCaption}
              </div>

              <!-- Meta Graph API Insights -->
              <div class="post-metrics-chips">
                <div class="metric-chip" title="Total de visualizaciones / impresiones (Views)">
                  <span class="metric-chip-label">Visualizaciones</span>
                  <span class="metric-chip-value views">👁️ ${safeImpressions}</span>
                </div>
                <div class="metric-chip" title="Cuentas únicas alcanzadas / Espectadores (Reach)">
                  <span class="metric-chip-label">Alcance</span>
                  <span class="metric-chip-value reach">👥 ${safeReach}</span>
                </div>
                <div class="metric-chip" title="Tasa de engagement calculada sobre interacciones reales">
                  <span class="metric-chip-label">Engagement</span>
                  <span class="metric-chip-value engagement">🔥 ${engRate}%</span>
                </div>
                <div class="metric-chip" title="Me gusta / Reacciones">
                  <span class="metric-chip-label">Likes</span>
                  <span class="metric-chip-value">❤️ ${safeLikes}</span>
                </div>
                <div class="metric-chip" title="Comentarios">
                  <span class="metric-chip-label">Comentarios</span>
                  <span class="metric-chip-value">💬 ${safeComments}</span>
                </div>
                <div class="metric-chip" title="Guardados en Instagram">
                  <span class="metric-chip-label">Guardados</span>
                  <span class="metric-chip-value saved">🔖 ${safeSaved}</span>
                </div>
                <div class="metric-chip" title="Veces compartido">
                  <span class="metric-chip-label">Shares</span>
                  <span class="metric-chip-value">🔄 ${safeShares}</span>
                </div>
              </div>


              <!-- Sentiment Distribution -->
              <div class="post-sentiment-wrapper">
                <div class="sentiment-meter-label">
                  <span>Sentimiento de la audiencia:</span>
                  <span><strong>${localComments}</strong> comentarios registrados</span>
                </div>
                <div class="sentiment-stacked-bar">
                  <div class="sentiment-segment positive" style="width: ${posPct}%;" title="Elogios: ${posPct}%"></div>
                  <div class="sentiment-segment questions" style="width: ${qPct}%;" title="Preguntas: ${qPct}%"></div>
                  <div class="sentiment-segment urgent" style="width: ${urgPct}%;" title="Apoyo: ${urgPct}%"></div>
                  <div class="sentiment-segment leads" style="width: ${leadPct}%;" title="Leads: ${leadPct}%"></div>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="post-card-actions">
              <button class="btn-post-action primary" onclick="App.jumpToPostComments(${parseInt(p.id, 10)})">
                <span>💬 Ver Comentarios (${localComments})</span>
              </button>
              ${p.permalink ? `
                <a href="${App.escapeHtml(p.permalink)}" target="_blank" rel="noopener noreferrer" class="btn-post-action" style="text-decoration: none;">
                  <span>Ver en ${isInstagram ? 'IG' : 'FB'} ↗️</span>
                </a>
              ` : ''}
            </div>
          </div>
        </div>
      `;
    }).join('');
  }
};
