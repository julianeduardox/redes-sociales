/**
 * ══════════════════════════════════════════════════════════════════════════════
 * 🏛️ XINDRO AI Copilot - AteneaLearningController (Fase 3 & Fase 4)
 * Control de Interfaz para el Motor de Aprendizaje Continuo, Experimentos Naturales
 * y Matriz de Hipótesis con Ciclo de Vida (@fortaleza_imparable)
 * ══════════════════════════════════════════════════════════════════════════════
 */

const AteneaLearningController = {
  data: null,
  activeSubTab: 'overview',
  isLoading: false,

  init() {
    if (window.location.hash === '#atenea-learning' || (window.App && window.App.activeTab === 'atenea-learning')) {
      this.loadOverview();
    }
  },

  switchSubTab(tabName) {
    this.activeSubTab = tabName;
    const subtabs = ['overview', 'predictions', 'comparisons', 'hypotheses', 'directives', 'double-brain'];
    
    subtabs.forEach(t => {
      const btn = document.getElementById(`tab-btn-${t}`);
      const view = document.getElementById(`subtab-view-${t}`);
      if (btn) {
        if (t === tabName) btn.classList.add('active');
        else btn.classList.remove('active');
      }
      if (view) {
        view.style.display = (t === tabName) ? 'block' : 'none';
      }
    });

    if (tabName === 'predictions') {
      this.loadPredictions();
    } else if (tabName === 'directives') {
      this.loadDirectives();
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
      buttonEl.innerHTML = '<span>⏳ Minando Patrones & Experimentos...</span>';
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
        const comp = json.data.natural_comparisons || 0;
        const pat = json.data.patterns || 0;
        window.App.showToast(`✅ Aprendizaje completado: ${pat} hipótesis y ${comp} experimentos A/B naturales detectados.`, 'success');
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
    this.setText('atenea-kpi-comparisons-count', c.natural_comparisons_count || 0);
    this.setText('badge-comparisons-count', c.natural_comparisons_count || 0);
    this.setText('atenea-kpi-hypotheses-count', (c.winners_count || 0) + (c.anti_patterns_count || 0));
    this.setText('atenea-kpi-anti-count', c.anti_patterns_count || 0);

    // 2. Percentiles Continuos Breakdown
    const p = data.continuous_percentiles || {};
    const totalP = (p.p90_count || 0) + (p.p75_count || 0) + (p.p50_count || 0) + (p.p25_count || 0) + (p.p10_count || 0);
    if (totalP > 0) {
      const pct90 = Math.round(((p.p90_count || 0) / totalP) * 100);
      const pct75 = Math.round(((p.p75_count || 0) / totalP) * 100);
      const pct50 = Math.round(((p.p50_count || 0) / totalP) * 100);
      const pct25 = Math.round(((p.p25_count || 0) / totalP) * 100);
      const pct10 = Math.round(((p.p10_count || 0) / totalP) * 100);

      this.setStyleWidth('tier-bar-p90', pct90 + '%');
      this.setStyleWidth('tier-bar-p75', pct75 + '%');
      this.setStyleWidth('tier-bar-p50', pct50 + '%');
      this.setStyleWidth('tier-bar-p25', pct25 + '%');
      this.setStyleWidth('tier-bar-p10', pct10 + '%');

      this.setText('tier-label-p90', `P90 - Top 10% (${p.p90_count || 0})`);
      this.setText('tier-label-p75', `P75 - Alto (${p.p75_count || 0})`);
      this.setText('tier-label-p50', `P50 - Medio (${p.p50_count || 0})`);
      this.setText('tier-label-p25', `P25 - Bajo (${p.p25_count || 0})`);
      this.setText('tier-label-p10', `P10 - Bottom 20% (${p.p10_count || 0})`);
    }

    // 3. Top 5 y Bottom 5 Publicaciones
    const topPostsContainer = document.getElementById('atenea-top-posts-grid');
    if (topPostsContainer && data.top_posts) {
      topPostsContainer.innerHTML = data.top_posts.map(post => this.renderPostCard(post, true)).join('');
    }

    const lowPostsContainer = document.getElementById('atenea-low-posts-grid');
    if (lowPostsContainer && data.low_posts) {
      lowPostsContainer.innerHTML = data.low_posts.map(post => this.renderPostCard(post, false)).join('');
    }

    // 4. Experimentos Naturales (A/B Twins)
    this.renderNaturalComparisons(data.natural_comparisons || []);

    // 5. Matriz de Hipótesis y Lifecycle
    this.renderHypothesesMatrix(data.hypotheses_matrix || {});
  },

  renderNaturalComparisons(comparisons) {
    const container = document.getElementById('atenea-natural-comparisons-grid');
    if (!container) return;

    if (!comparisons || comparisons.length === 0) {
      container.innerHTML = '<div style="color: var(--text-dim); padding: 25px; text-align: center;">No se detectaron experimentos gemelos en el corpus. Pulsa "Reconstruir & Minar Patrones".</div>';
      return;
    }

    container.innerHTML = comparisons.map((comp, idx) => {
      const deltaColor = comp.delta_percentage > 50 ? '#34d399' : '#38bdf8';
      const platAIcon = comp.post_a_platform === 'facebook' ? '📘' : '📸';
      const platBIcon = comp.post_b_platform === 'facebook' ? '📘' : '📸';

      const conf = comp.comparison_confidence || 'LOW';
      let confBadge = '';
      if (conf === 'HIGH') {
        confBadge = '<span style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; background: rgba(52,211,153,0.18); color: #34d399; font-weight: 800; border: 1px solid rgba(52,211,153,0.3);">🟢 Confianza Alta (Plataforma y Formato Aislados)</span>';
      } else if (conf === 'MEDIUM') {
        confBadge = '<span style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; background: rgba(251,191,36,0.18); color: #fbbf24; font-weight: 800; border: 1px solid rgba(251,191,36,0.3);">🟡 Confianza Media (Desfase Temporal)</span>';
      } else {
        confBadge = '<span style="font-size: 0.72rem; padding: 3px 8px; border-radius: 6px; background: rgba(248,113,113,0.18); color: #f87171; font-weight: 800; border: 1px solid rgba(248,113,113,0.3);">🔴 Confianza Baja (Cruce Plataforma IG vs FB)</span>';
      }

      const sameList = comp.same_variables_list || (comp.same_variables ? JSON.parse(comp.same_variables) : []);
      const diffList = comp.differing_variables_list || (comp.differing_variables ? JSON.parse(comp.differing_variables) : []);

      return `
        <div style="background: rgba(15,23,42,0.7); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; transition: all 0.2s;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;">
            <div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
                <span style="font-size: 0.74rem; font-weight: 800; color: #a5b4fc; text-transform: uppercase; letter-spacing: 0.04em;">
                  Comparación Natural #${idx + 1}
                </span>
                ${confBadge}
              </div>
              <div style="font-size: 0.95rem; font-weight: 700; color: #fff; margin-top: 3px;">
                "${this.escape(comp.concept_theme)}"
              </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 0.74rem; padding: 3px 9px; border-radius: 6px; background: rgba(52,211,153,0.15); color: ${deltaColor}; font-weight: 800;">
                Delta Observado: +${comp.delta_percentage}%
              </span>
              <button type="button" class="btn-primary-action" style="font-size: 0.74rem; padding: 5px 12px; background: linear-gradient(135deg, #7c3aed, #4f46e5);" onclick="AteneaLearningController.createPhrasesFromTopPost(${comp.post_a_id})">
                🏛️ Atenea Studio
              </button>
            </div>
          </div>

          <!-- Matriz de Variables Aisladas -->
          <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.05); border-radius: 8px; padding: 8px 12px; margin-bottom: 12px; font-size: 0.75rem; display: flex; flex-direction: column; gap: 6px;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
              <strong style="color: #34d399;">✓ VARIABLES CONTROLADAS (SAME):</strong>
              ${sameList.map(v => `<span style="background: rgba(52,211,153,0.12); color: #6ee7b7; padding: 2px 7px; border-radius: 4px; font-weight: 600;">${this.escape(v)}</span>`).join('')}
            </div>
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
              <strong style="color: #f59e0b;">≠ VARIABLE DIFERENTE (DIFF):</strong>
              ${diffList.map(v => `<span style="background: rgba(245,158,11,0.15); color: #fcd34d; padding: 2px 7px; border-radius: 4px; font-weight: 600;">${this.escape(v)}</span>`).join('')}
              ${comp.time_gap_hours ? `<span style="color: var(--text-dim); margin-left: 6px;">(Desfase: ${comp.time_gap_hours}h)</span>` : ''}
            </div>
          </div>

          <!-- Comparativa lado a lado -->
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
            <!-- Variante A -->
            <div style="background: rgba(10,13,20,0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 12px;">
              <div style="display: flex; justify-content: space-between; font-size: 0.74rem; color: #94a3b8; margin-bottom: 6px;">
                <span>${platAIcon} Variante A (ID #${comp.post_a_id}) [${this.escape(comp.post_a_platform.toUpperCase())}]</span>
                <span style="color: #38bdf8; font-weight: 700;">Score: ${comp.post_a_score}</span>
              </div>
              <div style="font-size: 0.85rem; font-weight: 600; color: #f1f5f9; margin-bottom: 8px;">
                🖼️ Placa: "${this.escape(comp.post_a_placa || comp.concept_theme)}"
              </div>
              <div style="display: flex; gap: 10px; font-size: 0.76rem; color: #cbd5e1; border-top: 1px solid rgba(255,255,255,0.04); padding-top: 6px;">
                <span>❤️ <strong>${(comp.post_a_likes || 0).toLocaleString()}</strong> likes</span>
                <span>🔄 <strong>${(comp.post_a_shares || 0).toLocaleString()}</strong> shares</span>
              </div>
            </div>

            <!-- Variante B -->
            <div style="background: rgba(10,13,20,0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 12px;">
              <div style="display: flex; justify-content: space-between; font-size: 0.74rem; color: #94a3b8; margin-bottom: 6px;">
                <span>${platBIcon} Variante B (ID #${comp.post_b_id}) [${this.escape(comp.post_b_platform.toUpperCase())}]</span>
                <span style="color: #34d399; font-weight: 700;">Score: ${comp.post_b_score}</span>
              </div>
              <div style="font-size: 0.85rem; font-weight: 600; color: #f1f5f9; margin-bottom: 8px;">
                🖼️ Placa: "${this.escape(comp.post_b_placa || comp.concept_theme)}"
              </div>
              <div style="display: flex; gap: 10px; font-size: 0.76rem; color: #cbd5e1; border-top: 1px solid rgba(255,255,255,0.04); padding-top: 6px;">
                <span>❤️ <strong>${(comp.post_b_likes || 0).toLocaleString()}</strong> likes</span>
                <span>🔄 <strong>${(comp.post_b_shares || 0).toLocaleString()}</strong> shares</span>
              </div>
            </div>
          </div>

          <div style="font-size: 0.78rem; color: var(--text-dim); background: rgba(0,0,0,0.25); padding: 8px 12px; border-radius: 6px;">
            💬 <strong>Conclusión Metodológica:</strong> ${this.escape(comp.observation_summary)}
          </div>
        </div>
      `;
    }).join('');
  },

  renderHypothesesMatrix(matrix) {
    const container = document.getElementById('atenea-hypotheses-matrix-container');
    if (!container) return;

    const tiersOrder = [
      { key: 'STRONG', title: '🟢 Evidencia Fuerte (STRONG)', desc: 'Muestra amplia (n >= 25) y límite inferior de Wilson supera holgadamente el baseline.', color: '#34d399' },
      { key: 'VALIDATED', title: '🔵 Hipótesis Validadas (VALIDATED)', desc: 'Muestra estadísticamente relevante (n >= 15) con intervalo positivo respecto al baseline.', color: '#38bdf8' },
      { key: 'OBSERVATION', title: '🟣 En Observación (OBSERVATION)', desc: 'Patrón observable consistente (6 <= n < 15) en proceso de acumulación de casos.', color: '#c084fc' },
      { key: 'EMERGING', title: '🟡 Hipótesis Incipientes (EMERGING)', desc: 'Primeras señales (n < 6) detectadas en la audiencia.', color: '#fbbf24' },
      { key: 'WEAKENING', title: '🔴 En Fatiga / Decaimiento (WEAKENING)', desc: 'Patrones cuyo rendimiento reciente ha decaído frente a su histórico.', color: '#f87171' }
    ];

    let html = '';
    tiersOrder.forEach(tier => {
      const items = matrix[tier.key] || [];
      if (items.length === 0) return;

      html += `
        <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 18px; margin-bottom: 12px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <h5 style="font-size: 0.95rem; font-weight: 800; color: ${tier.color}; margin: 0;">
              ${tier.title} (${items.length})
            </h5>
            <span style="font-size: 0.72rem; color: var(--text-dim);">${tier.desc}</span>
          </div>

          <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 12px;">
            ${items.map(it => this.renderHypothesisCard(it, tier.color)).join('')}
          </div>
        </div>
      `;
    });

    container.innerHTML = html || '<div style="color: var(--text-dim); padding: 25px; text-align: center;">No hay hipótesis registradas en la matriz aún.</div>';
  },

  renderHypothesisCard(it, accentColor) {
    const isAnti = it.pattern_type === 'ANTI_PATTERN';
    const tag = isAnti ? 'ANTI-PATRÓN' : 'HIPÓTESIS';
    const tagColor = isAnti ? '#f87171' : accentColor;

    return `
      <div style="background: rgba(10,13,20,0.7); border: 1px solid rgba(255,255,255,0.06); border-left: 3px solid ${tagColor}; border-radius: 8px; padding: 12px 14px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 6px;">
          <div style="display: flex; align-items: center; gap: 6px;">
            <span style="font-size: 0.72rem; font-weight: 800; color: ${tagColor}; padding: 2px 6px; border-radius: 4px; background: rgba(255,255,255,0.06);">
              ${tag}
            </span>
            <span style="font-size: 0.8rem; font-weight: 700; color: #fff;">
              ${this.escape(it.theme || it.structure || it.hook_type || 'Patrón Multi-Capa')}
            </span>
          </div>

          <div style="font-size: 0.72rem; color: #cbd5e1; display: flex; gap: 8px;">
            <span>Muestra: <strong>n=${it.sample_size}</strong></span>
            <span>Wilson 95%: <strong>[${it.wilson_lower}% - ${it.wilson_upper}%]</strong></span>
          </div>
        </div>

        <p style="font-size: 0.83rem; color: #cbd5e1; line-height: 1.45; margin: 0 0 8px 0;">
          ${this.escape(it.description)}
        </p>

        <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-dim); border-top: 1px solid rgba(255,255,255,0.04); padding-top: 6px;">
          <span>Tasa: <strong style="color: #fff;">${it.pattern_rate}%</strong> (Baseline: ${it.baseline_rate}%)</span>
          <span>Lift Relativo: <strong style="color: ${tagColor};">${it.lift_metric}</strong></span>
          ${it.decay_status === 'WEAKENING' ? '<span style="color: #f87171; font-weight: 800;">⚠️ Fatiga Temporal</span>' : '<span style="color: #34d399;">✓ Frescura Activa</span>'}
        </div>
      </div>
    `;
  },

  renderPostCard(p, isTop) {
    const platIcon = p.platform === 'facebook' ? '📘' : '📸';
    const tierColor = isTop ? '#34d399' : '#f87171';
    const quote = p.overlay_quote || (p.caption ? p.caption.substring(0, 90) + '...' : 'Sin texto');

    return `
      <div style="background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 14px; display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <span style="font-size: 0.78rem; font-weight: 700; color: #cbd5e1; display: flex; align-items: center; gap: 4px;">
              ${platIcon} ${p.platform.toUpperCase()}
            </span>
            <div style="display: flex; gap: 6px; align-items: center;">
              ${p.percentile_rank ? `<span style="font-size: 0.7rem; color: #a5b4fc; background: rgba(99,102,241,0.15); padding: 2px 6px; border-radius: 4px;">P${Math.round(p.percentile_rank)}</span>` : ''}
              <span style="font-size: 0.72rem; padding: 2px 8px; border-radius: 4px; background: rgba(${isTop ? '52,211,153' : '239,68,68'}, 0.15); color: ${tierColor}; font-weight: 700;">
                ${this.escape(p.performance_tier)}
              </span>
            </div>
          </div>
          <div style="font-size: 0.84rem; font-weight: 600; color: #fff; margin-bottom: 6px; line-height: 1.35;">
            "${this.escape(quote)}"
          </div>
          <div style="font-size: 0.74rem; color: var(--text-dim); margin-bottom: 10px;">
            Tema: <span style="color: #a5b4fc;">${this.escape(p.theme || 'Filosofía')}</span> • Familia: <span style="color: #cbd5e1;">${this.escape(p.sentence_family || 'Aforismo')}</span>
          </div>
        </div>

        <div>
          <div style="display: flex; gap: 10px; font-size: 0.75rem; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 8px; margin-bottom: ${isTop ? '10px' : '0'}; flex-wrap: wrap;">
            <span>❤️ <strong>${(p.raw_likes || 0).toLocaleString()}</strong></span>
            <span>💬 <strong>${(p.raw_comments || 0).toLocaleString()}</strong></span>
            ${p.platform === 'facebook' ? `<span>🔄 <strong>${(p.raw_shares || 0).toLocaleString()}</strong></span>` : `<span>🔖 <strong>${(p.raw_saves || 0).toLocaleString()}</strong></span>`}
            <span style="margin-left: auto; color: ${tierColor}; font-weight: 700;">Score: ${p.overall_performance_score}</span>
          </div>

          ${isTop ? `
          <button type="button" class="btn-recreate-fortaleza" style="width: 100%; justify-content: center; font-size: 0.8rem; padding: 7px 12px; margin-top: 2px;" onclick="AteneaLearningController.createPhrasesFromTopPost(${p.id})">
            <span>🏛️ Atenea Studio</span>
            <span class="btn-badge-brand">Crear 4 Variantes</span>
          </button>
          ` : ''}
        </div>
      </div>
    `;
  },

  // =========================================================================
  // FASE 5: PREDICCIÓN VS RESULTADO & LEARNING LEDGER
  // =========================================================================
  predictionsData: null,
  activePredFilter: 'all',

  async loadPredictions() {
    const tableContainer = document.getElementById('atenea-predictions-table-container');
    if (tableContainer) {
      tableContainer.innerHTML = '<div style="color: var(--text-dim); text-align: center; padding: 24px;"><div class="comment-loading" style="margin: 0 auto 8px;"></div>Cargando predicciones falsables...</div>';
    }

    try {
      const [predRes, ledRes] = await Promise.all([
        fetch('api/atenea_predictions.php?action=list'),
        fetch('api/atenea_predictions.php?action=ledger')
      ]);

      const predJson = await predRes.json();
      const ledJson = await ledRes.json();

      if (predJson.success) {
        this.predictionsData = predJson;
        this.renderPredictions(predJson, ledJson.ledger || []);
      }
    } catch (err) {
      console.error('loadPredictions error:', err);
      if (tableContainer) {
        tableContainer.innerHTML = '<div style="color: #f87171; text-align: center; padding: 20px;">Error al cargar predicciones y libro mayor.</div>';
      }
    }
  },

  renderPredictions(summary, ledger) {
    const m = summary.metrics || {};
    this.setText('pred-kpi-total', m.total_predictions || 0);
    this.setText('pred-kpi-evaluated-label', `${m.evaluated_predictions || 0} maduras evaluadas`);
    
    const evN = m.evaluated_predictions || 0;
    const kConf = m.k_confirmed || m.confirmed || 0;
    const confRate = m.confirmation_rate || 0;
    const wLower = m.wilson_lower || 0;
    const wUpper = m.wilson_upper || 0;

    this.setText('pred-kpi-confirmed', kConf);
    this.setText('pred-kpi-confirmed-rate', `k=${kConf} de n=${evN} (${confRate}%) | Wilson: [${wLower}%, ${wUpper}%]`);

    this.setText('pred-kpi-contradicted', m.contradicted || 0);
    this.setText('pred-kpi-contradicted-rate', `Tasa: ${m.contradiction_rate || 0}% (n=${m.contradicted || 0} de ${evN})`);

    this.setText('pred-kpi-inconclusive', m.inconclusive || 0);
    this.setText('pred-kpi-inconclusive-rate', `${m.inconclusive || 0} inconclusas (${m.immature_predictions || 0} inmaduras)`);

    const countBadge = document.getElementById('badge-predictions-count');
    if (countBadge) countBadge.textContent = m.total_predictions || 0;

    // Render Table
    this.renderPredictionsTable(summary.predictions || []);

    // Render Failed Predictions
    this.renderFailedPredictions(summary.predictions || []);

    // Render Learning Ledger
    this.renderLearningLedger(ledger);
  },

  filterPredictions(filter) {
    this.activePredFilter = filter;
    if (this.predictionsData && this.predictionsData.predictions) {
      this.renderPredictionsTable(this.predictionsData.predictions);
    }
  },

  renderPredictionsTable(predictions) {
    const container = document.getElementById('atenea-predictions-table-container');
    if (!container) return;

    let filtered = predictions;
    if (this.activePredFilter !== 'all') {
      filtered = predictions.filter(p => p.result_outcome === this.activePredFilter);
    }

    if (!filtered || filtered.length === 0) {
      container.innerHTML = '<div style="color: var(--text-dim); text-align: center; padding: 25px;">No hay predicciones registradas para este filtro. Pulsa "+ Formular Predicción Falsable" para crear la primera.</div>';
      return;
    }

    const rows = filtered.map(p => {
      let outcomeBadge = '<span style="background: rgba(148,163,184,0.15); color: #94a3b8; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">PENDIENTE</span>';
      if (p.result_outcome === 'CONFIRMED') {
        outcomeBadge = '<span style="background: rgba(16,185,129,0.2); color: #34d399; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 0.72rem; border: 1px solid rgba(16,185,129,0.3);">✓ CONFIRMADA</span>';
      } else if (p.result_outcome === 'CONTRADICTED') {
        outcomeBadge = '<span style="background: rgba(239,68,68,0.2); color: #f87171; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 0.72rem; border: 1px solid rgba(239,68,68,0.3);">✕ CONTRADICHA</span>';
      } else if (p.result_outcome === 'INCONCLUSIVE') {
        outcomeBadge = '<span style="background: rgba(245,158,11,0.2); color: #fbbf24; padding: 2px 8px; border-radius: 6px; font-weight: 800; font-size: 0.72rem;">? INCONCLUSA</span>';
      }

      let validityBadge = '';
      if (p.prediction_validity === 'RETROSPECTIVE_INVALID') {
        validityBadge = '<div style="margin-top: 4px;"><span style="background: rgba(239,68,68,0.2); color: #fca5a5; font-size: 0.65rem; padding: 1px 6px; border-radius: 4px; font-weight: 700; border: 1px solid rgba(239,68,68,0.3);">RETROSPECTIVA (INVÁLIDA)</span></div>';
      } else if (p.maturity_status === 'IMMATURE') {
        validityBadge = '<div style="margin-top: 4px;"><span style="background: rgba(245,158,11,0.2); color: #fde68a; font-size: 0.65rem; padding: 1px 6px; border-radius: 4px; font-weight: 700;">INMADURO (<24h)</span></div>';
      }

      const platformIcon = p.platform === 'facebook' ? '📘 FB' : '📸 IG';
      const pTier = p.actual_tier || '—';
      const pPercentile = p.actual_percentile !== null ? `P${p.actual_percentile}` : '—';
      const isEvaluated = p.prediction_status === 'EVALUATED';

      return `
        <tr style="border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 0.8rem;">
          <td style="padding: 12px 10px; font-weight: 700; color: #a5b4fc;">
            <div style="display: flex; align-items: center; gap: 6px;">
              <span style="font-size: 0.68rem; background: rgba(255,255,255,0.08); padding: 1px 5px; border-radius: 4px; color: #94a3b8;">${platformIcon}</span>
              ${this.escape(p.hypothesis_family)}
            </div>
            <div style="font-size: 0.7rem; color: var(--text-dim); margin-top: 2px;">${this.escape(p.source_evidence_type)}</div>
          </td>
          <td style="padding: 12px 10px; color: #cbd5e1; max-width: 300px;">
            <div style="font-weight: 600; color: #fff;">"${this.escape(p.prediction_statement)}"</div>
            <div style="font-size: 0.72rem; color: #94a3b8; margin-top: 3px;">Concepto: ${this.escape(p.input_post_concept || p.draft_phrase)}</div>
          </td>
          <td style="padding: 12px 10px; text-align: center;">
            <span style="background: rgba(139,92,246,0.15); color: #c4b5fd; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 0.72rem;">${this.escape(p.expected_tier)}</span>
          </td>
          <td style="padding: 12px 10px; text-align: center; color: #e2e8f0; font-weight: 600;">
            ${pTier}
            <div style="font-size: 0.7rem; color: #94a3b8;">${pPercentile}</div>
          </td>
          <td style="padding: 12px 10px; text-align: center;">
            ${outcomeBadge}
            ${validityBadge}
          </td>
          <td style="padding: 12px 10px; text-align: right; white-space: nowrap;">
            ${!isEvaluated ? `
              <button type="button" class="btn-primary-action" style="font-size: 0.72rem; padding: 4px 10px; background: linear-gradient(135deg, #10b981, #059669);" onclick="AteneaLearningController.openEvaluatePredictionModal(${p.id}, '${this.escape(p.prediction_statement)}', '${this.escape(p.expected_tier)}')">
                Evaluar Resultado
              </button>
            ` : `
              <span style="font-size: 0.7rem; color: #64748b;">Evaluada ${p.evaluated_at ? p.evaluated_at.substring(0, 10) : ''}</span>
            `}
          </td>
        </tr>
      `;
    }).join('');

    container.innerHTML = `
      <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
          <thead>
            <tr style="border-bottom: 1px solid rgba(255,255,255,0.12); font-size: 0.74rem; color: var(--text-dim); text-transform: uppercase;">
              <th style="padding: 8px 10px;">Hipótesis</th>
              <th style="padding: 8px 10px;">Promesa Falsable</th>
              <th style="padding: 8px 10px; text-align: center;">Tier Esperado</th>
              <th style="padding: 8px 10px; text-align: center;">Resultado Real</th>
              <th style="padding: 8px 10px; text-align: center;">Estado</th>
              <th style="padding: 8px 10px; text-align: right;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>
    `;
  },

  renderFailedPredictions(predictions) {
    const container = document.getElementById('atenea-failed-predictions-container');
    if (!container) return;

    const failed = (predictions || []).filter(p => p.result_outcome === 'CONTRADICTED');
    if (failed.length === 0) {
      container.innerHTML = '<div style="color: var(--text-dim); font-size: 0.8rem; padding: 12px; text-align: center; background: rgba(0,0,0,0.2); border-radius: 8px;">No hay predicciones contradichas registradas. Todas las predicciones evaluadas hasta ahora se comportaron en la dirección esperada o están pendientes.</div>';
      return;
    }

    container.innerHTML = failed.map(p => `
      <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(239,68,68,0.25); border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
          <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 3px;">
            <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; background: rgba(239,68,68,0.2); color: #fca5a5; font-weight: 800;">CONTRADICHA</span>
            <strong style="color: #fff; font-size: 0.84rem;">${this.escape(p.hypothesis_family)}</strong>
            <span style="font-size: 0.72rem; color: #94a3b8;">(Esperaba: ${this.escape(p.expected_tier)} • Real: ${this.escape(p.actual_tier || '—')} P${p.actual_percentile || 0})</span>
          </div>
          <div style="font-size: 0.8rem; color: #cbd5e1;">"${this.escape(p.prediction_statement)}"</div>
          <div style="font-size: 0.72rem; color: #f87171; margin-top: 4px;">Motivo: ${this.escape(p.result_notes || 'No alcanzó el percentil mínimo esperado.')}</div>
        </div>
        <span style="font-size: 0.72rem; color: #64748b;">${p.evaluated_at || p.created_at}</span>
      </div>
    `).join('');
  },

  renderLearningLedger(ledger) {
    const container = document.getElementById('atenea-learning-ledger-container');
    if (!container) return;

    if (!ledger || ledger.length === 0) {
      container.innerHTML = '<div style="color: var(--text-dim); text-align: center; padding: 20px;">El libro mayor no tiene asientos todavía. Evalúa una predicción para generar el primer registro de aprendizaje.</div>';
      return;
    }

    const items = ledger.map(l => {
      let actionColor = '#38bdf8';
      let actionIcon = '•';
      if (l.learning_action === 'UPWEIGHT') {
        actionColor = '#34d399';
        actionIcon = '▲';
      } else if (l.learning_action === 'DOWNWEIGHT') {
        actionColor = '#f87171';
        actionIcon = '▼';
      }

      return `
        <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.06); border-radius: 10px; padding: 12px 16px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap;">
          <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; flex-wrap: wrap;">
              <span style="font-size: 0.7rem; color: #64748b; font-family: monospace;">#LEDGER-${l.id}</span>
              <strong style="color: #c4b5fd; font-size: 0.82rem;">${this.escape(l.hypothesis_family)}</strong>
              <span style="font-size: 0.72rem; padding: 2px 7px; border-radius: 4px; background: rgba(255,255,255,0.06); color: ${actionColor}; font-weight: 800;">
                ${actionIcon} ${this.escape(l.learning_action)}
              </span>
              <span style="font-size: 0.72rem; color: #94a3b8;">(${this.escape(l.previous_status)} → <strong style="color:#fff;">${this.escape(l.new_status)}</strong>)</span>
            </div>
            <div style="font-size: 0.8rem; color: #cbd5e1; margin-top: 3px;">
              <strong>Razón:</strong> ${this.escape(l.reason)}
            </div>
          </div>
          <span style="font-size: 0.72rem; color: #64748b; white-space: nowrap;">${l.created_at ? l.created_at.substring(0, 16) : ''}</span>
        </div>
      `;
    }).join('');

    container.innerHTML = items;
  },

  openNewPredictionModal() {
    App.openModal('modal-new-prediction');
  },

  async submitNewPrediction(e) {
    e.preventDefault();
    const platform = document.getElementById('pred-input-platform')?.value || 'instagram';
    const family = document.getElementById('pred-input-family')?.value;
    const statement = document.getElementById('pred-input-statement')?.value;
    const tier = document.getElementById('pred-input-tier')?.value;
    const evidenceType = document.getElementById('pred-input-evidence-type')?.value;
    const concept = document.getElementById('pred-input-concept')?.value;

    if (!statement || !concept) {
      App.showToast('Por favor completa todos los campos requeridos', 'warning');
      return;
    }

    try {
      const res = await fetch('api/atenea_predictions.php?action=create', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': App.csrfToken || ''
        },
        body: JSON.stringify({
          platform: platform,
          hypothesis_family: family,
          prediction_statement: statement,
          expected_tier: tier,
          source_evidence_type: evidenceType,
          input_post_concept: concept,
          target_event: tier === 'BOTTOM20' ? 'BOTTOM20' : 'TOP20'
        })
      });

      const json = await res.json();
      if (!json.success) {
        throw new Error(json.error || 'Error al registrar predicción');
      }

      App.showToast('Predicción falsable registrada con éxito', 'success');
      App.closeModal('modal-new-prediction');
      this.loadPredictions();
    } catch (err) {
      App.showToast(err.message, 'error');
    }
  },

  openEvaluatePredictionModal(predId, statement, expectedTier) {
    document.getElementById('eval-pred-id').value = predId;
    document.getElementById('eval-pred-statement-label').textContent = `Promesa Falsable: "${statement}"`;
    document.getElementById('eval-pred-expected-label').textContent = `Tier Esperado: ${expectedTier}`;
    App.openModal('modal-evaluate-prediction');
  },

  async submitEvaluatePrediction(e) {
    e.preventDefault();
    const predId = document.getElementById('eval-pred-id')?.value;
    const actualTier = document.getElementById('eval-actual-tier')?.value;
    const maturityStatus = document.getElementById('eval-maturity-status')?.value || 'MATURE';
    const actualPercentile = document.getElementById('eval-actual-percentile')?.value;
    const actualLikes = document.getElementById('eval-actual-likes')?.value;
    const actualShares = document.getElementById('eval-actual-shares')?.value;
    const actualReach = document.getElementById('eval-actual-reach')?.value;

    try {
      const res = await fetch('api/atenea_predictions.php?action=evaluate', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': App.csrfToken || ''
        },
        body: JSON.stringify({
          prediction_id: predId,
          metrics: {
            actual_tier: actualTier,
            maturity_status: maturityStatus,
            actual_percentile: actualPercentile ? parseFloat(actualPercentile) : null,
            actual_likes: actualLikes ? parseInt(actualLikes) : null,
            actual_shares: actualShares ? parseInt(actualShares) : null,
            actual_reach: actualReach ? parseInt(actualReach) : null
          }
        })
      });

      const json = await res.json();
      if (!json.success) {
        throw new Error(json.error || 'Error al evaluar predicción');
      }

      App.showToast(`Predicción evaluada: ${json.data.result_outcome}`, 'success');
      App.closeModal('modal-evaluate-prediction');
      this.loadPredictions();
    } catch (err) {
      App.showToast(err.message, 'error');
    }
  },

  createPhrasesFromTopPost(postDnaId) {
    if (typeof RadarController !== 'undefined' && RadarController.openRecreateModal) {
      RadarController.openRecreateModal(postDnaId, false, 'historical');
    } else {
      App.showToast('Módulo Atenea Studio no disponible', 'error');
    }
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
