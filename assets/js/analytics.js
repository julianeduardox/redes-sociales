/**
 * Analytics & Per-Post Performance Controller
 * Hardened with XSS Prevention, 7x24 Smart Heatmap & Hall of Fame Engine
 */

const AnalyticsController = {
  currentSubtab: 'overview',
  postsPlatform: 'all',
  postsSort: 'recent',
  activeTopCategory: 'engagement',
  cachedAnalyticsData: null,

  switchSubtab(subtab) {
    const validSubtabs = ['overview', 'timing', 'posts', 'trends'];
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
    const timingView   = document.getElementById('analytics-timing-subview');
    const postsView    = document.getElementById('analytics-posts-subview');
    const trendsView   = document.getElementById('analytics-trends-subview');

    if (overviewView) overviewView.style.display = (activeSubtab === 'overview') ? 'block' : 'none';
    if (timingView)   timingView.style.display   = (activeSubtab === 'timing')   ? 'block' : 'none';
    if (postsView)    postsView.style.display    = (activeSubtab === 'posts')    ? 'block' : 'none';
    if (trendsView)   trendsView.style.display   = (activeSubtab === 'trends')   ? 'block' : 'none';

    if (!this.cachedAnalyticsData) {
      this.loadAnalytics();
    } else {
      if (activeSubtab === 'timing') {
        this.renderTimingSubtab(this.cachedAnalyticsData);
      } else if (activeSubtab === 'overview') {
        this.renderOverview(this.cachedAnalyticsData);
      } else if (activeSubtab === 'posts') {
        this.renderPosts(this.cachedAnalyticsData.posts || []);
      }
    }

    // Inicializar Trends Agent al activar el tab
    if (activeSubtab === 'trends' && typeof TrendsAgent !== 'undefined') {
      TrendsAgent.init();
    }
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

  switchTopCategory(category) {
    this.activeTopCategory = category;
    document.querySelectorAll('.top-category-pill').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.topCategory === category);
    });
    if (this.cachedAnalyticsData && this.cachedAnalyticsData.top_performers) {
      this.renderCategoryList(this.cachedAnalyticsData.top_performers);
    }
  },

  async loadAnalytics() {
    try {
      const url = `api/analytics.php?platform=${encodeURIComponent(this.postsPlatform)}&sort=${encodeURIComponent(this.postsSort)}`;
      const response = await App.fetchWithCsrf(url);
      const res = await response.json();

      if (res.success) {
        this.cachedAnalyticsData = res;
        this.renderOverview(res);
        this.renderTimingSubtab(res);
        this.renderPosts(res.posts || []);
      }
    } catch (err) {
      console.error("Error loading analytics:", err);
    }
  },

  formatNumber(num) {
    const n = parseInt(num, 10) || 0;
    if (n >= 1000000) return (n / 1000000).toFixed(1) + 'M';
    if (n >= 10000) return (n / 1000).toFixed(1) + 'K';
    if (n >= 1000) return n.toLocaleString();
    return n.toString();
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

    container.innerHTML = `
      <!-- Top Meta Insights & Engagement KPIs -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">👁️ Visualizaciones Totales (Views)</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-cyan); margin: 6px 0;">${this.formatNumber(stats.total_impressions || 0)}</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${this.formatNumber(stats.total_reach || 0)} personas alcanzadas en Meta</div>
        </div>

        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">👥 Alcance Único (Reach)</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: #a855f7; margin: 6px 0;">${this.formatNumber(stats.total_reach || 0)}</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${stats.total_posts || 0} publicaciones analizadas</div>
        </div>

        <div style="background: var(--bg-card); padding: 20px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
          <div style="font-size: 0.74rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">🔥 Engagement Rate %</div>
          <div style="font-size: 1.8rem; font-weight: 800; color: var(--accent-emerald); margin: 6px 0;">${(parseFloat(stats.avg_engagement_rate) || 0).toFixed(1)}%</div>
          <div style="font-size: 0.76rem; color: var(--text-muted);">${this.formatNumber(stats.total_post_likes || 0)} likes • ${this.formatNumber(stats.total_saved || 0)} guardados</div>
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

          <div style="display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap;">
            <button class="btn-primary-action" style="flex: 1; min-width: 140px; justify-content: center;" onclick="AnalyticsController.switchSubtab('timing')">
              ⏰ Ver Horarios & Mejores Posts
            </button>
            <button class="btn-primary-action" style="flex: 1; min-width: 140px; justify-content: center; background: rgba(255,255,255,0.06);" onclick="AnalyticsController.switchSubtab('posts')">
              📱 Ver Publicaciones
            </button>
          </div>
        </div>
      </div>
    `;
  },

  renderTimingSubtab(data) {
    const container = document.getElementById('analytics-timing-content');
    if (!container) return;

    const timing = data.timing_analysis || {};
    const performers = data.top_performers || {};
    const goldenWindows = timing.top_golden_windows || [];
    const heatmap = timing.heatmap_matrix || {};
    const daysBreakdown = timing.days_breakdown || [];
    const slotsBreakdown = timing.slots_breakdown || [];
    const podium = performers.podium || [];
    const patterns = performers.patterns || {};
    const recommendations = timing.recommendations || [];

    const orderedDayKeys = [1, 2, 3, 4, 5, 6, 0];
    const dayLabels = { 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb', 0: 'Dom' };
    const dayFullLabels = { 1: 'Lunes', 2: 'Martes', 3: 'Miércoles', 4: 'Jueves', 5: 'Viernes', 6: 'Sábado', 0: 'Domingo' };

    // Find max day engagement for progress bar scaling
    let maxDayEng = 1.0;
    daysBreakdown.forEach(d => {
      if (d.avg_engagement_rate > maxDayEng) maxDayEng = d.avg_engagement_rate;
    });

    container.innerHTML = `
      <!-- Section 1: Golden Timing Banner Cards -->
      <div style="margin-bottom: 28px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
          <div>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
              <span>🏆</span> Ventanas Doradas de Publicación (Smart Timing)
            </h4>
            <p style="font-size: 0.82rem; color: var(--text-muted);">
              Franjas horarias con el mayor ratio de engagement y velocidad de interacción en tu cuenta.
            </p>
          </div>
          <div style="background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.3); padding: 6px 12px; border-radius: 20px; font-size: 0.78rem; color: #a5b4fc; font-weight: 600;">
            📊 Basado en ${timing.total_analyzed_posts || 0} publicaciones
          </div>
        </div>

        <div class="golden-windows-grid">
          ${goldenWindows.map((gw, idx) => {
            const rankEmoji = idx === 0 ? '🥇' : (idx === 1 ? '🥈' : '🥉');
            const rankLabel = idx === 0 ? 'Pico #1 Recomendado' : (idx === 1 ? 'Pico #2 Alternativo' : 'Pico #3 Secundario');
            const engVal = parseFloat(gw.avg_engagement_rate || 0).toFixed(1);
            return `
              <div class="golden-card ${idx === 0 ? 'golden-card-primary' : ''}">
                <div class="golden-card-header">
                  <span class="golden-card-badge">${rankEmoji} ${rankLabel}</span>
                  <span class="golden-card-eng">${engVal}% Eng.</span>
                </div>
                <div class="golden-card-time">
                  <span class="golden-day">${App.escapeHtml(gw.day_name || 'Día')}</span>
                  <span class="golden-hour">${App.escapeHtml(gw.hour_label || 'Horario')}</span>
                </div>
                <div class="golden-card-note">
                  ${App.escapeHtml(gw.note || (idx === 0 ? 'Mayor concentración de comentarios y guardados' : 'Excelente respuesta orgánica'))}
                </div>
              </div>
            `;
          }).join('')}
        </div>
      </div>

      <!-- Section 2: 7x24 Interactive Weekly Heatmap Matrix -->
      <div class="analytics-section-card" style="margin-bottom: 28px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
          <div>
            <h4 style="font-size: 1rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
              <span>🗺️</span> Mapa de Calor Semanal 7x24 (Heatmap)
            </h4>
            <p style="font-size: 0.8rem; color: var(--text-muted);">
              Pasa el cursor por cada celda para ver el volumen de posts, alcance promedio y engagement por hora exacta.
            </p>
          </div>

          <!-- Heatmap Legend -->
          <div class="heatmap-legend">
            <span style="font-size: 0.74rem; color: var(--text-dim); margin-right: 4px;">Intensidad:</span>
            <span class="legend-item"><span class="legend-dot lvl-0"></span> Sin posts</span>
            <span class="legend-item"><span class="legend-dot lvl-1"></span> 1-4%</span>
            <span class="legend-item"><span class="legend-dot lvl-2"></span> 5-8%</span>
            <span class="legend-item"><span class="legend-dot lvl-3"></span> 9-13%</span>
            <span class="legend-item"><span class="legend-dot lvl-4"></span> >14% 🔥</span>
          </div>
        </div>

        <div class="heatmap-table-container">
          <table class="heatmap-table">
            <thead>
              <tr>
                <th class="heatmap-corner-th">Día / Hora</th>
                ${Array.from({ length: 24 }, (_, h) => `<th class="heatmap-hour-th">${String(h).padStart(2, '0')}h</th>`).join('')}
              </tr>
            </thead>
            <tbody>
              ${orderedDayKeys.map(dayKey => {
                const dayName = dayLabels[dayKey];
                const dayFullName = dayFullLabels[dayKey];
                return `
                  <tr>
                    <td class="heatmap-day-td" title="${dayFullName}">
                      <strong>${dayName}</strong>
                    </td>
                    ${Array.from({ length: 24 }, (_, hour) => {
                      const cell = (heatmap[dayKey] && heatmap[dayKey][hour]) ? heatmap[dayKey][hour] : { posts_count: 0, avg_engagement_rate: 0, avg_reach: 0, total_reach: 0 };
                      const count = cell.posts_count || 0;
                      const eng = parseFloat(cell.avg_engagement_rate || 0);
                      const avgReach = (cell.avg_reach && cell.avg_reach > 0) 
                        ? cell.avg_reach 
                        : (count > 0 && cell.total_reach ? Math.round(cell.total_reach / count) : (count > 0 ? (timing.account_avg_reach || 1250) : 0));
                      
                      let lvlClass = 'lvl-0';
                      if (count > 0) {
                        if (eng >= 14) lvlClass = 'lvl-4';
                        else if (eng >= 9) lvlClass = 'lvl-3';
                        else if (eng >= 5) lvlClass = 'lvl-2';
                        else lvlClass = 'lvl-1';
                      }

                      const tooltipText = count > 0 
                        ? `${dayFullName} ${String(hour).padStart(2, '0')}:00\n• Publicaciones: ${count}\n• Engagement: ${eng.toFixed(1)}%\n• Alcance prom: ${AnalyticsController.formatNumber(avgReach)}`
                        : `${dayFullName} ${String(hour).padStart(2, '0')}:00\nSin publicaciones registradas`;

                      return `
                        <td class="heatmap-cell ${lvlClass}" data-tooltip="${App.escapeHtml(tooltipText)}">
                          ${count > 0 ? `<span class="heatmap-cell-val">${count}</span>` : ''}
                        </td>
                      `;
                    }).join('')}
                  </tr>
                `;
              }).join('')}
            </tbody>
          </table>
        </div>
      </div>

      <!-- Section 3: Days Breakdown & Dayparts Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px;">
        
        <!-- Peak Days Bar Breakdown -->
        <div class="analytics-section-card">
          <h4 style="font-size: 0.95rem; font-weight: 800; color: #fff; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <span>📅</span> Rendimiento por Día de la Semana
          </h4>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            ${orderedDayKeys.map(dayKey => {
              const dayData = daysBreakdown.find(d => d.day_index === dayKey) || { day_name: dayFullLabels[dayKey], posts_count: 0, avg_engagement_rate: 0 };
              const engRate = parseFloat(dayData.avg_engagement_rate || 0);
              const barWidth = Math.min(100, Math.max(4, Math.round((engRate / maxDayEng) * 100)));
              return `
                <div>
                  <div style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 4px;">
                    <span style="font-weight: 600; color: var(--text-base);">${dayFullLabels[dayKey]} (${dayData.posts_count || 0} posts)</span>
                    <span style="font-weight: 800; color: ${engRate > 0 ? 'var(--accent-emerald)' : 'var(--text-dim)'};">${engRate.toFixed(1)}% Eng.</span>
                  </div>
                  <div class="day-progress-track">
                    <div class="day-progress-fill" style="width: ${barWidth}%;"></div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>

        <!-- Daypart / Slots Breakdown -->
        <div class="analytics-section-card">
          <h4 style="font-size: 0.95rem; font-weight: 800; color: #fff; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
            <span>🌓</span> Rendimiento por Franja Horaria
          </h4>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            ${slotsBreakdown.map(s => {
              const engRate = parseFloat(s.avg_engagement_rate || 0).toFixed(1);
              let icon = '☀️';
              if (s.key === 'afternoon') icon = '🌤️';
              else if (s.key === 'evening') icon = '🌙';
              else if (s.key === 'night') icon = '🌌';

              return `
                <div class="slot-stat-card">
                  <div style="font-size: 1.2rem; margin-bottom: 4px;">${icon}</div>
                  <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-dim); text-transform: uppercase;">${App.escapeHtml(s.name)}</div>
                  <div style="font-size: 1.35rem; font-weight: 800; color: var(--accent-cyan); margin: 4px 0;">${engRate}%</div>
                  <div style="font-size: 0.72rem; color: var(--text-muted);">${s.posts_count || 0} publicaciones</div>
                </div>
              `;
            }).join('')}
          </div>
        </div>
      </div>

      <!-- Section 4: Podium / Hall of Fame (Top 3 Posts) -->
      <div style="margin-bottom: 28px;">
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
          <div>
            <h4 style="font-size: 1.15rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px;">
              <span>🏆</span> Podio de Publicaciones Estrella (Hall of Fame)
            </h4>
            <p style="font-size: 0.82rem; color: var(--text-muted);">
              Tus 3 mejores publicaciones históricas ponderadas por interacción, visualizaciones y tracción en la comunidad.
            </p>
          </div>
        </div>

        ${podium.length > 0 ? `
          <div class="podium-grid">
            ${podium.map((pod, idx) => {
              const medalClass = pod.medal_type === 'oro' ? 'podium-gold' : (pod.medal_type === 'plata' ? 'podium-silver' : 'podium-bronze');
              const safeImg = App.sanitizeUrl(pod.media_url, 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=480&h=320&fit=crop&auto=format&q=75');
              const safeCaption = App.escapeHtml(pod.caption || 'Publicación destacada');
              const isInstagram = pod.platform === 'instagram';
              const engRate = parseFloat(pod.engagement_rate || 0).toFixed(1);

              return `
                <div class="podium-card ${medalClass}">
                  <div class="podium-badge-header">
                    <span class="podium-medal-icon">${pod.medal_emoji || '⭐'}</span>
                    <span class="podium-medal-title">${App.escapeHtml(pod.medal_title || 'Mención')}</span>
                    <span class="podium-diff-tag ${pod.is_above_average ? 'positive' : ''}">${App.escapeHtml(pod.vs_average_label || '')}</span>
                  </div>

                  <div class="podium-media-wrapper">
                    <img src="${safeImg}" class="podium-media-img" loading="lazy" alt="Media preview" />
                    <span class="podium-platform-tag ${isInstagram ? 'instagram' : 'facebook'}">
                      ${isInstagram ? '📸 IG' : '📘 FB'}
                    </span>
                  </div>

                  <div class="podium-content">
                    <div class="podium-date">📅 ${pod.posted_at ? App.escapeHtml(pod.posted_at.substring(0, 16)) : 'Reciente'}</div>
                    <div class="podium-caption" title="${safeCaption}">${safeCaption}</div>

                    <div class="podium-metrics-grid">
                      <div class="podium-metric-item">
                        <span class="p-label">Engagement</span>
                        <span class="p-val fire">🔥 ${engRate}%</span>
                      </div>
                      <div class="podium-metric-item">
                        <span class="p-label">Alcance</span>
                        <span class="p-val reach">👥 ${this.formatNumber(pod.reach || 0)}</span>
                      </div>
                      <div class="podium-metric-item">
                        <span class="p-label">Likes</span>
                        <span class="p-val">❤️ ${this.formatNumber(pod.total_likes || 0)}</span>
                      </div>
                      <div class="podium-metric-item">
                        <span class="p-label">Comentarios</span>
                        <span class="p-val">💬 ${this.formatNumber(pod.total_comments || 0)}</span>
                      </div>
                    </div>

                    <div class="podium-actions">
                      <button class="btn-post-action primary" onclick="App.jumpToPostComments(${parseInt(pod.id, 10)})">
                        <span>💬 Ver Hilo</span>
                      </button>
                      ${pod.permalink ? `
                        <a href="${App.escapeHtml(pod.permalink)}" target="_blank" rel="noopener noreferrer" class="btn-post-action" style="text-decoration: none;">
                          <span>Ver en Red ↗️</span>
                        </a>
                      ` : ''}
                    </div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        ` : `
          <div style="background: var(--bg-card); padding: 30px; text-align: center; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); color: var(--text-dim);">
            <div style="font-size: 2rem; margin-bottom: 8px;">🌟</div>
            <h5 style="color: #fff; font-weight: 700;">No hay suficientes publicaciones registradas</h5>
            <p style="font-size: 0.82rem;">Sincroniza tus publicaciones con Meta para generar el Podio Oficial.</p>
          </div>
        `}
      </div>

      <!-- Section 5: Top Performers by Specific Category & Pattern Analysis -->
      <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 20px; margin-bottom: 28px;">
        
        <!-- Category Explorer -->
        <div class="analytics-section-card">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <h4 style="font-size: 0.95rem; font-weight: 800; color: #fff;">
              🎯 Top 5 por Categoría Específica
            </h4>
            <div class="top-category-pills">
              <button class="top-category-pill active" data-top-category="engagement" onclick="AnalyticsController.switchTopCategory('engagement')">🔥 Engagement</button>
              <button class="top-category-pill" data-top-category="reach" onclick="AnalyticsController.switchTopCategory('reach')">👥 Alcance</button>
              <button class="top-category-pill" data-top-category="comments" onclick="AnalyticsController.switchTopCategory('comments')">💬 Conversación</button>
              <button class="top-category-pill" data-top-category="shares" onclick="AnalyticsController.switchTopCategory('shares')">🔄 Viralidad</button>
            </div>
          </div>

          <div id="top-category-list-container">
            <!-- Rendered by renderCategoryList -->
          </div>
        </div>

        <!-- Success Patterns & AI Recommendations -->
        <div style="display: flex; flex-direction: column; gap: 16px;">
          <!-- Format Winner -->
          <div class="analytics-section-card">
            <h4 style="font-size: 0.9rem; font-weight: 800; color: var(--accent-cyan); margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
              <span>🎬</span> Formato Más Exitoso
            </h4>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <div style="font-size: 1.1rem; font-weight: 800; color: #fff;">${App.escapeHtml(patterns.best_format || 'Imagen / Reel')}</div>
                <div style="font-size: 0.76rem; color: var(--text-muted); margin-top: 2px;">Genera el mayor tiempo de permanencia e interacciones.</div>
              </div>
              <div style="font-size: 1.3rem; font-weight: 800; color: var(--accent-emerald);">
                ${patterns.best_format_avg_eng || 0}% <span style="font-size: 0.72rem; color: var(--text-dim);">Eng. Prom.</span>
              </div>
            </div>

            ${(patterns.top_caption_keywords && patterns.top_caption_keywords.length > 0) ? `
              <div style="margin-top: 14px; border-top: 1px solid var(--border-subtle); padding-top: 10px;">
                <div style="font-size: 0.76rem; font-weight: 700; color: var(--text-dim); margin-bottom: 6px;">🔑 Palabras Clave Ganadoras en Copies:</div>
                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                  ${patterns.top_caption_keywords.map(kw => `
                    <span class="winning-kw-badge">#${App.escapeHtml(kw)}</span>
                  `).join('')}
                </div>
              </div>
            ` : ''}
          </div>

          <!-- AI Directives -->
          <div class="analytics-section-card" style="flex: 1;">
            <h4 style="font-size: 0.9rem; font-weight: 800; color: #a855f7; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
              <span>🤖</span> Directrices de la IA Copilot
            </h4>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              ${recommendations.map(r => `
                <div class="ai-rec-box">
                  <div style="font-size: 0.82rem; font-weight: 700; color: #fff; margin-bottom: 3px;">${r.icon || '⚡'} ${App.escapeHtml(r.title)}</div>
                  <div style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.4;">${App.escapeHtml(r.description)}</div>
                  <div style="font-size: 0.74rem; color: #a5b4fc; margin-top: 4px; font-weight: 600;">👉 ${App.escapeHtml(r.action)}</div>
                </div>
              `).join('')}
            </div>
          </div>
        </div>
      </div>
    `;

    this.renderCategoryList(performers);
  },

  renderCategoryList(performers) {
    const container = document.getElementById('top-category-list-container');
    if (!container) return;

    let posts = [];
    let metricLabel = 'Engagement';
    let metricKey = 'engagement_rate';
    let isPercent = true;

    if (this.activeTopCategory === 'reach') {
      posts = performers.top_reach || [];
      metricLabel = 'Alcance';
      metricKey = 'reach';
      isPercent = false;
    } else if (this.activeTopCategory === 'comments') {
      posts = performers.top_comments || [];
      metricLabel = 'Comentarios';
      metricKey = 'total_comments';
      isPercent = false;
    } else if (this.activeTopCategory === 'shares') {
      posts = performers.top_shares || [];
      metricLabel = 'Shares';
      metricKey = 'total_shares';
      isPercent = false;
    } else {
      posts = performers.top_engagement || [];
      metricLabel = 'Engagement';
      metricKey = 'engagement_rate';
      isPercent = true;
    }

    if (posts.length === 0) {
      container.innerHTML = `
        <div style="padding: 24px; text-align: center; color: var(--text-dim); font-size: 0.82rem;">
          No hay publicaciones suficientes para esta categoría.
        </div>
      `;
      return;
    }

    container.innerHTML = `
      <div style="display: flex; flex-direction: column; gap: 10px;">
        ${posts.slice(0, 5).map((p, idx) => {
          const safeCaption = App.escapeHtml(p.caption || 'Publicación');
          const isInstagram = p.platform === 'instagram';
          const safeImg = App.sanitizeUrl(p.media_url, 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=200&h=200&fit=crop&auto=format&q=75');
          const val = isPercent ? `${parseFloat(p[metricKey] || 0).toFixed(1)}%` : this.formatNumber(p[metricKey] || 0);

          return `
            <div class="top-list-item">
              <span class="top-list-rank">#${idx + 1}</span>
              <img src="${safeImg}" class="top-list-thumb" loading="lazy" alt="thumb" />
              <div class="top-list-info">
                <div class="top-list-caption" title="${safeCaption}">${safeCaption}</div>
                <div class="top-list-meta">
                  <span class="top-list-platform ${isInstagram ? 'ig' : 'fb'}">${isInstagram ? 'Instagram' : 'Facebook'}</span>
                  <span>📅 ${p.posted_at ? App.escapeHtml(p.posted_at.substring(0, 10)) : 'Reciente'}</span>
                </div>
              </div>
              <div class="top-list-metric">
                <span class="top-list-val">${val}</span>
                <span class="top-list-label">${metricLabel}</span>
              </div>
            </div>
          `;
        }).join('')}
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

    container.innerHTML = posts.map(p => {
      const safeImg = App.sanitizeUrl(p.media_url, 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=480&h=320&fit=crop&auto=format&q=75');
      const safeCaption = App.escapeHtml(p.caption || (p.platform === 'instagram' ? 'Publicación de Instagram' : 'Publicación de Facebook'));
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

      const safeLikes = this.formatNumber(rawLikes);
      const safeComments = this.formatNumber(rawComments);
      const safeShares = this.formatNumber(rawShares);
      const safeImpressions = this.formatNumber(rawImpressions);
      const safeReach = this.formatNumber(rawReach);
      const safeSaved = this.formatNumber(rawSaved);
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
