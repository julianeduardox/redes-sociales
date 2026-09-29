/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ XINDRO AI Copilot - AteneaLearningController
 * Control de Interfaz para el Motor de Aprendizaje Continuo (@fortaleza_imparable)
 * ══════════════════════════════════════════════════════════════════════════════
 */

const AteneaLearningController = {
  data: null,
  isLoading: false,

  init() {
    // Si la pestaña actual al recargar es atenea-learning, cargar inmediatamente
    if (window.location.hash === '#atenea-learning' || (window.App && window.App.activeTab === 'atenea-learning')) {
      this.loadOverview();
    }
  },

  async loadOverview() {
    const container = document.getElementById('atenea-learning-container');
    if (!container) return;

    this.isLoading = true;
    this.renderLoadingState(true);

    try {
      const res = await fetch('api/atenea_learning.php?action=overview');
      const json = await res.json();

      if (!json.success) {
        throw new Error(json.error || 'Error al obtener datos de aprendizaje');
      }

      this.data = json.data;
      this.renderOverview(this.data);
      this.loadDirectives();
    } catch (err) {
      console.error('AteneaLearning loadOverview:', err);
      if (window.App && window.App.showToast) {
        window.App.showToast('Error al cargar datos de Atenea Learning: ' + err.message, 'error');
      }
    } finally {
      this.isLoading = false;
      this.renderLoadingState(false);
    }
  },

  renderLoadingState(isLoading) {
    const spinner = document.getElementById('atenea-learning-spinner');
    const content = document.getElementById('atenea-learning-content');
    if (spinner) spinner.style.display = isLoading ? 'block' : 'none';
    if (content) content.style.opacity = isLoading ? '0.5' : '1';
  },

  async rebuildAll(buttonEl) {
    if (this.isLoading) return;

    const originalHtml = buttonEl ? buttonEl.innerHTML : '';
    if (buttonEl) {
      buttonEl.disabled = true;
      buttonEl.innerHTML = '<span>⏳ Minando Patrones...</span>';
    }

    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const csrfToken = csrfMeta ? csrfMeta.content : '';

      const res = await fetch('api/atenea_learning.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({ action: 'rebuild' })
      });

      const json = await res.json();
      if (!json.success) {
        throw new Error(json.error || 'Fallo en la reconstrucción');
      }

      if (window.App && window.App.showToast) {
        window.App.showToast(`✅ Aprendizaje completado: ${json.data.patterns || 0} patrones actualizados sobre ${json.data.analyzed || 0} publicaciones.`, 'success');
      }

      await this.loadOverview();
    } catch (err) {
      console.error('AteneaLearning rebuildAll error:', err);
      if (window.App && window.App.showToast) {
        window.App.showToast('Error al reconstruir aprendizaje: ' + err.message, 'error');
      }
    } finally {
      if (buttonEl) {
        buttonEl.disabled = false;
        buttonEl.innerHTML = originalHtml;
      }
    }
  },

  async loadDirectives() {
    const directivesPre = document.getElementById('atenea-live-directives-code');
    if (!directivesPre) return;

    try {
      const res = await fetch('api/atenea_learning.php?action=prompt_directives');
      const json = await res.json();
      if (json.success && json.data.directives) {
        directivesPre.textContent = json.data.directives;
      }
    } catch (e) {
      console.error('Error loading directives:', e);
    }
  },

  renderOverview(data) {
    // 1. Contadores KPIs
    const c = data.counts || {};
    this.setText('atenea-kpi-total-indexed', c.total_indexed || 0);
    this.setText('atenea-kpi-fb-count', (c.facebook_posts || 0) + ' Facebook');
    this.setText('atenea-kpi-ig-count', (c.instagram_posts || 0) + ' Instagram');
    this.setText('atenea-kpi-winners-count', c.winners_count || 0);
    this.setText('atenea-kpi-anti-count', c.anti_patterns_count || 0);

    // 2. Tiers Breakdown
    const tiers = data.tiers || {};
    const totalTiers = (tiers.TOP_10 || 0) + (tiers.TOP_20 || 0) + (tiers.AVERAGE || 0) + (tiers.LOW_20 || 0);
    if (totalTiers > 0) {
      const pTop10 = Math.round(((tiers.TOP_10 || 0) / totalTiers) * 100);
      const pTop20 = Math.round(((tiers.TOP_20 || 0) / totalTiers) * 100);
      const pAvg = Math.round(((tiers.AVERAGE || 0) / totalTiers) * 100);
      const pLow = Math.round(((tiers.LOW_20 || 0) / totalTiers) * 100);

      this.setStyleWidth('tier-bar-top10', pTop10 + '%');
      this.setStyleWidth('tier-bar-top20', pTop20 + '%');
      this.setStyleWidth('tier-bar-avg', pAvg + '%');
      this.setStyleWidth('tier-bar-low20', pLow + '%');

      this.setText('tier-label-top10', `Top 10% (${tiers.TOP_10 || 0})`);
      this.setText('tier-label-top20', `Top 20% (${tiers.TOP_20 || 0})`);
      this.setText('tier-label-avg', `Promedio (${tiers.AVERAGE || 0})`);
      this.setText('tier-label-low20', `Bottom 20% (${tiers.LOW_20 || 0})`);
    }

    // 3. Fórmulas Ganadoras (Top Performers)
    const winnersContainer = document.getElementById('atenea-winners-list');
    if (winnersContainer) {
      if (!data.winners || data.winners.length === 0) {
        winnersContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px; text-align: center;">No hay patrones ganadores detectados aún. Pulsa "Reconstruir & Minar Patrones".</div>';
      } else {
        winnersContainer.innerHTML = data.winners.map(w => this.renderPatternCard(w, 'winner')).join('');
      }
    }

    // 4. Anti-Patrones (Bottom 20% - Qué evitar)
    const antiContainer = document.getElementById('atenea-anti-patterns-list');
    if (antiContainer) {
      if (!data.anti_patterns || data.anti_patterns.length === 0) {
        antiContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px; text-align: center;">No se han detectado anti-patrones críticos en la muestra.</div>';
      } else {
        antiContainer.innerHTML = data.anti_patterns.map(a => this.renderPatternCard(a, 'anti')).join('');
      }
    }

    // 5. Top 5 Publicaciones Históricas
    const topPostsContainer = document.getElementById('atenea-top-posts-grid');
    if (topPostsContainer && data.top_posts) {
      topPostsContainer.innerHTML = data.top_posts.map(p => this.renderPostCard(p, true)).join('');
    }

    // 6. Bottom 3 Publicaciones (Para Auditoría de Fallos)
    const lowPostsContainer = document.getElementById('atenea-low-posts-grid');
    if (lowPostsContainer && data.low_posts) {
      lowPostsContainer.innerHTML = data.low_posts.map(p => this.renderPostCard(p, false)).join('');
    }
  },

  renderPatternCard(p, type) {
    const isWinner = type === 'winner';
    const borderColor = isWinner ? 'rgba(52, 211, 153, 0.35)' : 'rgba(239, 68, 68, 0.35)';
    const bgGradient = isWinner ? 'rgba(16, 185, 129, 0.06)' : 'rgba(239, 68, 68, 0.06)';
    const badgeColor = isWinner ? '#34d399' : '#f87171';
    const icon = isWinner ? '⭐' : '⚠️';

    const confClass = p.confidence_level === 'HIGH' ? 'confidence-high' : (p.confidence_level === 'MEDIUM' ? 'confidence-med' : 'confidence-low');

    return `
      <div class="atenea-pattern-card" style="background: ${bgGradient}; border: 1px solid ${borderColor}; border-radius: 12px; padding: 16px; margin-bottom: 12px; transition: all 0.2s;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 1rem;">${icon}</span>
            <span style="font-size: 0.76rem; font-weight: 800; text-transform: uppercase; color: ${badgeColor}; letter-spacing: 0.04em;">
              ${isWinner ? 'Fórmula Comprobada' : 'Anti-Patrón (Prohibido)'}
            </span>
          </div>
          <div style="display: flex; gap: 6px;">
            <span class="atenea-conf-badge ${confClass}" title="Nivel de Confianza">
              Confianza: ${this.escape(p.confidence_level)} (n=${p.sample_size})
            </span>
            <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 5px; background: rgba(255,255,255,0.08); color: #e2e8f0; font-weight: 700;">
              ${this.escape(p.status)}
            </span>
          </div>
        </div>

        <p style="font-size: 0.86rem; color: #f1f5f9; line-height: 1.45; margin: 0 0 10px 0; font-weight: 500;">
          ${this.escape(p.description)}
        </p>

        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-dim); border-top: 1px solid rgba(255,255,255,0.06); padding-top: 8px;">
          <span>Impacto en Audiencia: <strong style="color: ${badgeColor};">${this.escape(p.lift_metric)}</strong></span>
          <span>Muestra: <strong>${p.sample_size} publicaciones</strong></span>
        </div>
      </div>
    `;
  },

  renderPostCard(p, isTop) {
    const platIcon = p.platform === 'facebook' ? '📘' : '📸';
    const tierColor = isTop ? '#34d399' : '#f87171';
    const quote = p.overlay_quote || p.caption.substring(0, 90) + '...';

    return `
      <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: flex; align-items: center; gap: 4px;">
              ${platIcon} ${p.platform.toUpperCase()}
            </span>
            <span style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; background: rgba(${isTop ? '52,211,153' : '239,68,68'}, 0.15); color: ${tierColor}; font-weight: 700;">
              ${this.escape(p.performance_tier)}
            </span>
          </div>
          <div style="font-size: 0.84rem; font-weight: 600; color: #fff; margin-bottom: 6px; line-height: 1.35;">
            "${this.escape(quote)}"
          </div>
          <div style="font-size: 0.74rem; color: var(--text-dim); margin-bottom: 10px;">
            Tema: <span style="color: #a5b4fc;">${this.escape(p.theme || 'Filosofía')}</span> • Gancho: <span style="color: #cbd5e1;">${this.escape(p.hook_type || 'General')}</span>
          </div>
        </div>

        <div style="display: flex; gap: 10px; font-size: 0.75rem; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 8px;">
          <span>❤️ <strong>${(p.raw_likes || 0).toLocaleString()}</strong></span>
          <span>💬 <strong>${(p.raw_comments || 0).toLocaleString()}</strong></span>
          ${p.platform === 'facebook' ? `<span>🔄 <strong>${(p.raw_shares || 0).toLocaleString()}</strong></span>` : `<span>🔖 <strong>${(p.raw_saves || 0).toLocaleString()}</strong></span>`}
          <span style="margin-left: auto; color: ${tierColor}; font-weight: 700;">Score: ${p.overall_performance_score}</span>
        </div>
      </div>
    `;
  },

  setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  },

  setStyleWidth(id, val) {
    const el = document.getElementById(id);
    if (el) el.style.width = val;
  },

  escape(str) {
    if (!str) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
};

window.AteneaLearningController = AteneaLearningController;

document.addEventListener('DOMContentLoaded', () => {
  AteneaLearningController.init();
});
