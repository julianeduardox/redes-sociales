/**
 * Agent Controller - Stoic & Motivational AI Community Manager (Hardened with Anti-CSRF)
 */

const AgentController = {
  activeComment: null,
  activeReplies: null,
  selectedVariant: 'engagement',

  // Modal State
  modalActiveComment: null,
  modalActiveReplies: null,
  modalSelectedVariant: 'engagement',
  isSubmittingReply: false,

  // Request AI replies for the active comment in sidebar copilot
  async loadSuggestions(comment, overrideTone = '') {
    this.activeComment = comment;
    const suggestionsContainer = document.getElementById('suggestions-container');
    const threadHistoryBox = document.getElementById('copilot-thread-history');
    const threadEventsList = document.getElementById('thread-events-list');

    // Handle Thread History if replied
    if (comment.status === 'replied') {
      if (threadHistoryBox && threadEventsList) {
        threadHistoryBox.style.display = 'block';
        const safeAuthor = this.escapeHtml(comment.author_name);
        const safeComment = this.escapeHtml(comment.comment_text);
        const safeReply = this.escapeHtml(comment.reply_text || 'Respuesta registrada y publicada a la comunidad.');
        const safeTime = this.escapeHtml(comment.created_at || 'Reciente');
        const safeReplyTime = this.escapeHtml(comment.replied_at || 'Publicado');

        threadEventsList.innerHTML = `
          <div class="timeline-event">
            <div class="timeline-icon">💬</div>
            <div class="timeline-content">
              <div class="timeline-top">
                <span class="timeline-author">${safeAuthor}</span>
                <span class="timeline-time">${safeTime}</span>
              </div>
              <div class="timeline-text">"${safeComment}"</div>
            </div>
          </div>

          <div class="timeline-event">
            <div class="timeline-icon" style="background: rgba(99,102,241,0.2); border-color: var(--primary);">⚡</div>
            <div class="timeline-content" style="border-left: 2px solid var(--accent-emerald); background: rgba(16,185,129,0.05);">
              <div class="timeline-top">
                <span class="timeline-author" style="color: var(--accent-emerald);">XINDRO Copilot (Respuesta Publicada)</span>
                <span class="timeline-time">${safeReplyTime}</span>
              </div>
              <div class="timeline-text">${safeReply}</div>
            </div>
          </div>
        `;
      }
    } else {
      if (threadHistoryBox) threadHistoryBox.style.display = 'none';
    }

    if (!suggestionsContainer) return;

    // Show loading skeleton
    suggestionsContainer.innerHTML = `
      <div style="padding: 20px; text-align: center; color: var(--text-muted);">
        <div style="display: inline-block; width: 24px; height: 24px; border: 3px solid rgba(99,102,241,0.3); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 8px;"></div>
        <p style="font-size: 0.82rem; font-weight: 600;">Agente Estoico analizando el contexto y forjando 3 respuestas de alto impacto...</p>
      </div>
    `;

    try {
      const tone = overrideTone || document.getElementById('select-tone')?.value || '';
      const response = await App.fetchWithCsrf('api/agent.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'generate_replies',
          comment_id: parseInt(comment.id, 10),
          tone: tone
        })
      });

      const res = await response.json();

      if (res.success && res.replies) {
        this.activeReplies = res.replies;
        this.renderSuggestionCards(res.replies);
        
        // Auto-select the first or most appropriate variant
        let defaultVar = 'engagement';
        if (comment.sentiment === 'urgent' || comment.intent === 'support') {
          defaultVar = 'support';
        } else if (comment.sentiment === 'question') {
          defaultVar = 'engagement';
        }
        this.selectVariant(defaultVar);
      } else {
        suggestionsContainer.innerHTML = `
          <div style="padding: 12px; color: var(--accent-rose); font-size: 0.82rem;">
            ⚠️ No se pudieron generar sugerencias: ${this.escapeHtml(res.error || 'Error desconocido')}
          </div>
        `;
      }
    } catch (err) {
      console.error(err);
      suggestionsContainer.innerHTML = `
        <div style="padding: 12px; color: var(--accent-rose); font-size: 0.82rem;">
          ⚠️ Error al conectar con el motor de IA.
        </div>
      `;
    }
  },

  getLanguageBadgeHtml(replies) {
    if (!replies) return '';
    const lang = (replies.detected_language || replies.detected_comment_language || '').toLowerCase();
    const respLang = (replies.response_language || lang).toLowerCase();
    const conf = replies.language_confidence !== undefined && replies.language_confidence !== null ? Math.round(replies.language_confidence * 100) : null;
    const isAmbiguous = Boolean(replies.requires_human_review && (lang === 'und' || lang === 'mixed' || !['es', 'pt', 'en'].includes(lang)));

    if (!lang && !respLang) return '';

    let flag = '🌐';
    let name = 'Multilingüe';
    if (lang === 'pt') { flag = '🇵🇹'; name = 'Português'; }
    else if (lang === 'en') { flag = '🇬🇧'; name = 'English'; }
    else if (lang === 'es') { flag = '🇪🇸'; name = 'Español'; }

    if (isAmbiguous) {
      return `
        <div style="grid-column: 1 / -1; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fbbf24; font-size: 0.72rem; font-weight: 600; margin-bottom: 8px; width: fit-content;">
          <span>⚠️ Idioma no confirmado · Requiere revisión humana</span>
        </div>
      `;
    }

    const confLabel = conf !== null ? ` · ${conf}% certeza` : '';
    const respLabel = respLang && respLang !== lang ? ` (Respuesta en ${respLang.toUpperCase()})` : '';

    return `
      <div style="grid-column: 1 / -1; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; background: rgba(99, 102, 241, 0.12); border: 1px solid rgba(99, 102, 241, 0.25); color: #a5b4fc; font-size: 0.72rem; font-weight: 600; margin-bottom: 8px; width: fit-content;">
        <span>${flag} ${name} detectado${confLabel}${respLabel}</span>
      </div>
    `;
  },

  // Render the 3 variant cards in side copilot
  renderSuggestionCards(replies) {
    const suggestionsContainer = document.getElementById('suggestions-container');
    if (!suggestionsContainer) return;

    const badgeHtml = this.getLanguageBadgeHtml(replies);

    suggestionsContainer.innerHTML = `
      ${badgeHtml}
      <!-- Connection & Empathy Card -->
      <div class="suggestion-card active" id="card-variant-engagement" onclick="AgentController.selectVariant('engagement')">
        <div class="suggestion-header">
          <span class="suggestion-tag engagement">🤝 Opción 1: Conexión & Empatía</span>
          <span style="font-size: 0.72rem; color: var(--text-dim);">Cercanía & Pregunta</span>
        </div>
        <p class="suggestion-text" id="text-variant-engagement">${this.escapeHtml(replies.engagement || '')}</p>
        <div class="suggestion-actions">
          <button type="button" class="btn-suggestion-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.engagement)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(99,102,241,0.2); color: #a5b4fc;" onclick="event.stopPropagation(); AgentController.selectVariant('engagement')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;" onclick="event.stopPropagation(); AgentController.saveAsGoldExample('engagement', false)" title="⭐ Guardar como Ejemplo de Oro para educar a Gemini">
            ⭐ Oro
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(16,185,129,0.25); color: #34d399; font-weight: 700;" onclick="event.stopPropagation(); AgentController.quickSendVariant('engagement')" title="Publicar directamente con 1 solo clic">
            ⚡ Enviar
          </button>
        </div>
      </div>

      <!-- Variant 2: Stoic Wisdom & Character -->
      <div class="suggestion-card" id="card-variant-conversion" onclick="AgentController.selectVariant('conversion')">
        <div class="suggestion-header">
          <span class="suggestion-tag conversion">🏛️ Opción 2: Sabiduría & Fortaleza Estoica</span>
          <span style="font-size: 0.72rem; color: var(--text-dim);">Reflexión Filosófica & Carácter</span>
        </div>
        <p class="suggestion-text" id="text-variant-conversion">${this.escapeHtml(replies.conversion || '')}</p>
        <div class="suggestion-actions">
          <button type="button" class="btn-suggestion-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.conversion)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(16,185,129,0.2); color: #6ee7b7;" onclick="event.stopPropagation(); AgentController.selectVariant('conversion')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;" onclick="event.stopPropagation(); AgentController.saveAsGoldExample('conversion', false)" title="⭐ Guardar como Ejemplo de Oro para educar a Gemini">
            ⭐ Oro
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(16,185,129,0.25); color: #34d399; font-weight: 700;" onclick="event.stopPropagation(); AgentController.quickSendVariant('conversion')" title="Publicar directamente con 1 solo clic">
            ⚡ Enviar
          </button>
        </div>
      </div>

      <!-- Variant 3: Drive & Determination -->
      <div class="suggestion-card" id="card-variant-support" onclick="AgentController.selectVariant('support')">
        <div class="suggestion-header">
          <span class="suggestion-tag support">⚡ Opción 3: Impulso & Determinación</span>
          <span style="font-size: 0.72rem; color: var(--text-dim);">Fuerza Mental & Resiliencia</span>
        </div>
        <p class="suggestion-text" id="text-variant-support">${this.escapeHtml(replies.support || '')}</p>
        <div class="suggestion-actions">
          <button type="button" class="btn-suggestion-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.support)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(168,85,247,0.2); color: #d8b4fe;" onclick="event.stopPropagation(); AgentController.selectVariant('support')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;" onclick="event.stopPropagation(); AgentController.saveAsGoldExample('support', false)" title="⭐ Guardar como Ejemplo de Oro para educar a Gemini">
            ⭐ Oro
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(16,185,129,0.25); color: #34d399; font-weight: 700;" onclick="event.stopPropagation(); AgentController.quickSendVariant('support')" title="Publicar directamente con 1 solo clic">
            ⚡ Enviar
          </button>
        </div>
      </div>

      ${replies.engagement_tips ? `
        <div class="suggestion-tip">
          🏛️ <strong>Estrategia Comunitaria:</strong> ${this.escapeHtml(replies.engagement_tips)}
        </div>
      ` : ''}
    `;
  },

  copySuggestion(text) {
    if (!text) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(() => {
        App.showToast('¡Texto copiado al portapapeles! 📋', 'success');
      }).catch(() => {
        this.fallbackCopyText(text, '¡Texto copiado al portapapeles! 📋');
      });
    } else {
      this.fallbackCopyText(text, '¡Texto copiado al portapapeles! 📋');
    }
  },

  fallbackCopyText(text, successMsg = '¡Copiado al portapapeles! 📋') {
    try {
      const el = document.createElement('textarea');
      el.value = text;
      el.setAttribute('readonly', '');
      el.style.position = 'fixed';
      el.style.left = '-9999px';
      el.style.top = '-9999px';
      document.body.appendChild(el);
      el.focus();
      el.select();
      const successful = document.execCommand('copy');
      document.body.removeChild(el);
      if (successful) {
        App.showToast(successMsg, 'success');
      } else {
        App.showToast('No se pudo copiar automáticamente.', 'error');
      }
    } catch (e) {
      App.showToast('No se pudo copiar automáticamente.', 'error');
    }
  },

  // Copy structured summary of modal assistant (comment + 3 variants + strategy + custom text)
  copyFullModalSummary() {
    if (!this.modalActiveComment) {
      App.showToast('No hay ningún comentario cargado en el asistente.', 'info');
      return;
    }

    const c = this.modalActiveComment;
    const author = c.author_name || c.author_handle || 'Usuario';
    const text = c.comment_text || '';
    const platform = (c.platform || 'Social').toUpperCase();
    const score = c.highlight_score || 0;
    const replies = this.modalActiveReplies || {};
    const customText = (document.getElementById('modal-reply-text-input')?.value || '').trim();

    let summary = `═══════════════════════════════════════════════════\n`;
    summary += `🪄 ASISTENTE DE RESPUESTAS XINDRO (HERMES AI)\n`;
    summary += `═══════════════════════════════════════════════════\n\n`;
    summary += `💬 COMENTARIO SELECCIONADO:\n`;
    summary += `• Autor: ${author} (${platform})\n`;
    summary += `• Score de Impacto: ${score}/100\n`;
    summary += `• Sentimiento / Intención: ${c.sentiment || 'neutro'} | ${c.intent || 'general'}\n`;
    summary += `• Texto: "${text}"\n\n`;
    summary += `───────────────────────────────────────────────────\n`;
    summary += `💡 SUGERENCIAS FORJADAS POR LA IA:\n`;
    summary += `───────────────────────────────────────────────────\n\n`;
    summary += `1️⃣ [Conexión & Empatía]:\n${replies.engagement || 'N/A'}\n\n`;
    summary += `2️⃣ [Sabiduría & Fortaleza Estoica]:\n${replies.conversion || 'N/A'}\n\n`;
    summary += `3️⃣ [Impulso & Determinación]:\n${replies.support || 'N/A'}\n\n`;

    if (replies.engagement_tips) {
      summary += `💡 [Estrategia de Conexión]:\n${replies.engagement_tips}\n\n`;
    }

    if (customText) {
      summary += `✍️ [Texto Personalizado / Editado]:\n${customText}\n\n`;
    }
    summary += `═══════════════════════════════════════════════════\n`;

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(summary).then(() => {
        App.showToast('¡Resumen completo del asistente copiado al portapapeles! 📋✨', 'success');
      }).catch(() => {
        this.fallbackCopyText(summary, '¡Resumen completo del asistente copiado al portapapeles! 📋✨');
      });
    } else {
      this.fallbackCopyText(summary, '¡Resumen completo del asistente copiado al portapapeles! 📋✨');
    }
  },

  // 1-Click High-Resolution Visual Card Screenshot Generator
  // 1-Click High-Resolution Visual Card Screenshot Generator
  captureModalAsImage() {
    if (!this.modalActiveComment) {
      App.showToast('No hay ningún comentario cargado en el asistente.', 'info');
      return;
    }

    if (this.isGeneratingModalSuggestions) {
      App.showToast('⏳ Hermes está forjando las 3 sugerencias con IA (toma unos 5-8 seg). Por favor espera un momento para tomar la captura completa.', 'warning', 4500);
      return;
    }

    const replies = this.modalActiveReplies || {};
    const hasAnyReply = replies && (replies.engagement || replies.conversion || replies.support);
    if (!hasAnyReply) {
      App.showToast('⚠️ Las 3 sugerencias aún no se han terminado de generar para este comentario. Espera a que aparezcan o haz clic en "🔄 Regenerar".', 'warning', 4500);
      return;
    }

    const c = this.modalActiveComment;
    const author = c.author_name || c.author_handle || 'Usuario';
    const handle = c.author_handle ? (c.author_handle.startsWith('@') ? c.author_handle : '@' + c.author_handle) : '@comunidad';
    const text = c.comment_text || '';
    const platform = (c.platform || 'Social').toUpperCase();
    const score = c.highlight_score || 0;
    const sentiment = (c.sentiment || 'neutral').toUpperCase();
    const accountName = document.getElementById('modal-account-name-badge')?.textContent || 'Cuenta Conectada';
    const brandVoice = document.getElementById('modal-brand-voice-badge')?.textContent || 'Fortaleza Imparable';
    const customText = (document.getElementById('modal-reply-text-input')?.value || '').trim();

    // Check if customText is genuinely a custom edition (not just a verbatim copy of one of the 3 variants)
    const isCustomEdited = customText && 
      customText !== (replies.engagement || '').trim() && 
      customText !== (replies.conversion || '').trim() && 
      customText !== (replies.support || '').trim();

    App.showToast('📸 Generando captura visual en alta resolución...', 'info');

    try {
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      const width = 1040;
      const dpr = 2; // Ultra-crisp Retina 2x scale
      const contentW = width - 80;

      // Text measuring helper respecting explicit line breaks and wrapping
      const getWrappedLines = (textToWrap, maxW, font) => {
        ctx.font = font;
        const paragraphs = String(textToWrap || '').split(/\r?\n/);
        const result = [];
        for (const p of paragraphs) {
          const trimmed = p.trim();
          if (!trimmed) {
            result.push('');
            continue;
          }
          const words = trimmed.split(/\s+/);
          let curLine = '';
          for (const w of words) {
            const test = curLine ? curLine + ' ' + w : w;
            if (ctx.measureText(test).width > maxW && curLine) {
              result.push(curLine);
              curLine = w;
            } else {
              curLine = test;
            }
          }
          if (curLine) result.push(curLine);
        }
        return result.length ? result : [''];
      };

      // Measure dynamic block heights
      const commentLines = getWrappedLines(`“${text}”`, contentW - 50, 'italic 18px system-ui, -apple-system, Segoe UI, Roboto, sans-serif');
      const commentCardH = Math.max(90, 48 + commentLines.length * 26);

      const var1Lines = getWrappedLines(replies.engagement || 'Sin sugerencia disponible', contentW - 50, '15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif');
      const var1H = 58 + var1Lines.length * 24;

      const var2Lines = getWrappedLines(replies.conversion || 'Sin sugerencia disponible', contentW - 50, '15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif');
      const var2H = 58 + var2Lines.length * 24;

      const var3Lines = getWrappedLines(replies.support || 'Sin sugerencia disponible', contentW - 50, '15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif');
      const var3H = 58 + var3Lines.length * 24;

      let customH = 0;
      let customLines = [];
      if (isCustomEdited) {
        customLines = getWrappedLines(customText, contentW - 50, '15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif');
        customH = 58 + customLines.length * 24;
      }

      // Total canvas height calculation
      const headerH = 110;
      const sectionTitleH = 34;
      const footerH = 55;
      const totalH = headerH + commentCardH + 20 + sectionTitleH + var1H + 16 + var2H + 16 + var3H + (isCustomEdited ? 16 + customH : 0) + footerH;

      canvas.width = width * dpr;
      canvas.height = totalH * dpr;
      canvas.style.width = width + 'px';
      canvas.style.height = totalH + 'px';
      ctx.scale(dpr, dpr);

      // Helper for rounded rectangles
      const drawCardBg = (x, y, w, h, radius, fillStyle, strokeStyle, strokeW = 1) => {
        ctx.beginPath();
        if (ctx.roundRect) {
          ctx.roundRect(x, y, w, h, radius);
        } else {
          ctx.rect(x, y, w, h);
        }
        if (fillStyle) {
          ctx.fillStyle = fillStyle;
          ctx.fill();
        }
        if (strokeStyle) {
          ctx.strokeStyle = strokeStyle;
          ctx.lineWidth = strokeW;
          ctx.stroke();
        }
      };

      // 1. Overall Background
      const bgGrad = ctx.createLinearGradient(0, 0, width, totalH);
      bgGrad.addColorStop(0, '#0a0e1a');
      bgGrad.addColorStop(0.5, '#0f172a');
      bgGrad.addColorStop(1, '#090d16');
      ctx.fillStyle = bgGrad;
      ctx.fillRect(0, 0, width, totalH);

      // Neon Top Border Accent
      const topGrad = ctx.createLinearGradient(0, 0, width, 0);
      topGrad.addColorStop(0, '#6366f1');
      topGrad.addColorStop(0.5, '#06b6d4');
      topGrad.addColorStop(1, '#10b981');
      ctx.fillStyle = topGrad;
      ctx.fillRect(0, 0, width, 4);

      // Outer Card Frame
      ctx.strokeStyle = 'rgba(99, 102, 241, 0.35)';
      ctx.lineWidth = 1.5;
      ctx.strokeRect(1, 1, width - 2, totalH - 2);

      // 2. Header Bar
      ctx.fillStyle = '#6366f1';
      ctx.font = 'bold 22px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.fillText('🪄 XINDRO AI COPILOT · HERMES v2.1', 40, 48);

      ctx.fillStyle = '#94a3b8';
      ctx.font = '13px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.fillText(`Asistente de Respuestas & Conexión · ${accountName} (${brandVoice})`, 40, 74);

      // Platform & Score Pill (Right-aligned)
      const pillW = 210;
      const pillX = width - 40 - pillW;
      drawCardBg(pillX, 32, pillW, 44, 8, 'rgba(99, 102, 241, 0.15)', 'rgba(99, 102, 241, 0.4)');
      ctx.fillStyle = '#f8fafc';
      ctx.font = 'bold 14px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.fillText(`${platform} · ⭐ SCORE ${score}/100`, pillX + 16, 59);

      let curY = headerH;

      // 3. Follower Comment Card
      drawCardBg(40, curY, contentW, commentCardH, 10, 'rgba(30, 41, 59, 0.75)', 'rgba(148, 163, 184, 0.25)');
      // Cyan accent bar on comment card
      ctx.fillStyle = '#06b6d4';
      ctx.fillRect(40, curY, 5, commentCardH);

      // Author & Sentiment Header
      ctx.fillStyle = '#38bdf8';
      ctx.font = 'bold 15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.fillText(`💬 ${author} (${handle})`, 60, curY + 28);

      ctx.fillStyle = '#94a3b8';
      ctx.font = '12px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.fillText(`SENTIMIENTO: ${sentiment}`, width - 260, curY + 28);

      // Comment Text
      ctx.fillStyle = '#f1f5f9';
      ctx.font = 'italic 16px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      let textY = curY + 56;
      for (const line of commentLines) {
        ctx.fillText(line, 60, textY);
        textY += 24;
      }

      curY += commentCardH + 20;

      // 4. Section Title
      ctx.fillStyle = '#cbd5e1';
      ctx.font = 'bold 15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      ctx.fillText('💡 SUGERENCIAS FORJADAS POR LA IA (HERMES v2.1):', 40, curY);
      curY += 22;

      // Helper to render suggestion card
      const drawVariantBlock = (title, subtitle, lines, boxH, borderColor, tagColor) => {
        drawCardBg(40, curY, contentW, boxH, 8, 'rgba(15, 23, 42, 0.88)', borderColor, 1.2);

        // Left accent bar
        ctx.fillStyle = tagColor;
        ctx.fillRect(40, curY, 5, boxH);

        // Header Title
        ctx.fillStyle = tagColor;
        ctx.font = 'bold 14px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        ctx.fillText(title, 60, curY + 25);
        const titleWidth = ctx.measureText(title).width;

        // Subtitle
        ctx.fillStyle = '#64748b';
        ctx.font = '12px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        ctx.fillText(`— ${subtitle}`, 60 + titleWidth + 12, curY + 25);

        // Body Text
        ctx.fillStyle = '#e2e8f0';
        ctx.font = '15px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
        let lineY = curY + 52;
        for (const l of lines) {
          ctx.fillText(l, 60, lineY);
          lineY += 24;
        }

        curY += boxH + 16;
      };

      // 1. Connection & Empathy
      drawVariantBlock(
        '🤝 Opción 1: Conexión & Empatía',
        'Cercanía, Agradecimiento & Pregunta',
        var1Lines,
        var1H,
        'rgba(6, 182, 212, 0.45)',
        '#06b6d4'
      );

      // 2. Stoic Wisdom
      drawVariantBlock(
        '🏛️ Opción 2: Sabiduría & Fortaleza Estoica',
        'Profundidad Filosófica & Autodominio',
        var2Lines,
        var2H,
        'rgba(16, 185, 129, 0.45)',
        '#10b981'
      );

      // 3. Drive & Determination
      drawVariantBlock(
        '⚡ Opción 3: Impulso & Determinación',
        'Energía, Resiliencia & Disciplina',
        var3Lines,
        var3H,
        'rgba(168, 85, 247, 0.45)',
        '#a855f7'
      );

      // Custom Edited Card (if applicable)
      if (isCustomEdited) {
        drawVariantBlock(
          '✍️ Respuesta Personalizada Redactada',
          'Ajuste manual del usuario',
          customLines,
          customH,
          'rgba(245, 158, 11, 0.5)',
          '#fbbf24'
        );
      }

      // 5. Footer
      ctx.fillStyle = '#475569';
      ctx.font = '12px system-ui, -apple-system, Segoe UI, Roboto, sans-serif';
      const timestamp = new Date().toLocaleString();
      ctx.fillText(`Generado con XINDRO Copilot · ${brandVoice} · ${timestamp} · Protección Anti-Alucinación Activa`, 40, totalH - 18);

      // Convert to blob and export
      canvas.toBlob(blob => {
        if (!blob) {
          App.showToast('No se pudo generar la imagen.', 'error');
          return;
        }

        // Trigger automatic download
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `asistente-comentario-${c.id || 'xindro'}.png`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        setTimeout(() => URL.revokeObjectURL(url), 4000);

        // Try writing to clipboard as PNG
        if (navigator.clipboard && window.ClipboardItem) {
          navigator.clipboard.write([
            new ClipboardItem({ 'image/png': blob })
          ]).then(() => {
            App.showToast('📸 ¡Captura Ultra-HD copiada al portapapeles y descargada! Puedes pegarla con Ctrl+V.', 'success', 6000);
          }).catch(() => {
            App.showToast('📸 ¡Captura descargada en tu carpeta de Descargas!', 'success', 5000);
          });
        } else {
          App.showToast('📸 ¡Captura descargada en tu carpeta de Descargas!', 'success', 5000);
        }
      }, 'image/png');

    } catch (err) {
      console.error(err);
      App.showToast('Error al capturar la imagen del asistente.', 'error');
    }
  },

  // Select a variant in sidebar copilot
  selectVariant(variantType) {
    this.selectedVariant = variantType;
    
    // Highlight active card
    document.querySelectorAll('.suggestion-card').forEach(el => el.classList.remove('active'));
    const targetCard = document.getElementById(`card-variant-${variantType}`);
    if (targetCard) targetCard.classList.add('active');

    // Populate textarea
    const textarea = document.getElementById('reply-text-input');
    if (textarea && this.activeReplies && this.activeReplies[variantType]) {
      textarea.value = this.activeReplies[variantType];
      textarea.focus();
    }
  },

  // Quick send a specific variant with 1 click directly from sidebar copilot
  async quickSendVariant(variantType) {
    if (this.isSubmittingReply) return;

    if (!this.activeComment) {
      App.showToast('Selecciona un comentario para responder.', 'error');
      return;
    }
    if (!this.activeReplies || !this.activeReplies[variantType]) {
      App.showToast('No hay una respuesta generada para esta opción.', 'error');
      return;
    }

    this.isSubmittingReply = true;
    const commentId = parseInt(this.activeComment.id, 10);
    const replyText = this.activeReplies[variantType];
    this.selectVariant(variantType);

    // Disable all card action buttons to prevent double-click race conditions
    const cardEl = document.getElementById(`card-variant-${variantType}`);
    const cardButtons = cardEl ? cardEl.querySelectorAll('button') : [];
    cardButtons.forEach(b => { b.disabled = true; });
    const quickBtn = cardButtons.length > 0 ? cardButtons[cardButtons.length - 1] : null;
    const oldQuickText = quickBtn ? quickBtn.innerHTML : '';
    if (quickBtn) quickBtn.innerHTML = `<span>⏳ Enviando...</span>`;

    App.showToast(`⚡ Publicando opción "${variantType}" en Meta...`, 'info');

    try {
      const tone = document.getElementById('select-tone')?.value || 'friendly_engaging';
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'reply',
          comment_id: commentId,
          reply_text: replyText,
          variant_type: variantType,
          tone_used: tone
        })
      });

      const res = await response.json();
      if (res.success) {
        App.showToast(res.message || '¡Respuesta publicada y enviada con éxito! 🚀✨', 'success');
        await App.loadComments();
        this.advanceToNextPendingComment(commentId);
      } else {
        if (res.is_token_expired) {
          App.showToast('⚠️ La respuesta se guardó pero NO se publicó en Meta: Token expirado (Error 190). Renuévalo en Configuración.', 'warning', 8000);
          if (App.showTokenExpiredBanner) App.showTokenExpiredBanner();
        } else {
          App.showToast(`Error al enviar a la red social: ${res.error || 'No se pudo enviar'}`, 'error');
        }
        await App.loadComments();
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error de red al enviar la respuesta.', 'error');
    } finally {
      this.isSubmittingReply = false;
      cardButtons.forEach(b => { b.disabled = false; });
      if (quickBtn) quickBtn.innerHTML = oldQuickText;
    }
  },

  // Insert emoji in sidebar copilot
  insertEmoji(emoji) {
    const textarea = document.getElementById('reply-text-input');
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + emoji + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
  },

  // Send the reply from sidebar copilot
  async submitReply() {
    if (this.isSubmittingReply) return;

    if (!this.activeComment) {
      App.showToast('Selecciona un comentario para responder.', 'error');
      return;
    }

    const textarea = document.getElementById('reply-text-input');
    const replyText = textarea?.value?.trim() || '';

    if (!replyText) {
      App.showToast('El texto de la respuesta no puede estar vacío.', 'error');
      return;
    }

    this.isSubmittingReply = true;
    const commentId = parseInt(this.activeComment.id, 10);
    const btn = document.getElementById('btn-send-action');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span>⏳ Publicando...</span>`;
    }

    const originalSuggestion = (this.activeReplies && this.activeReplies[this.selectedVariant]) ? this.activeReplies[this.selectedVariant].trim() : '';
    const wasEdited = !!(originalSuggestion && originalSuggestion !== replyText);

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'reply',
          comment_id: commentId,
          reply_text: replyText,
          variant_type: this.selectedVariant,
          tone_used: document.getElementById('select-tone')?.value || 'stoic_mentor',
          was_edited: wasEdited,
          original_suggestion: originalSuggestion
        })
      });

      const res = await response.json();

      if (res.success) {
        const learnMsg = wasEdited 
          ? '¡Respuesta publicada y estilo corregido aprendido por Gemini! 🧠✨' 
          : '¡Respuesta aprobada y registrada! Gemini aprendió este éxito. 🏛️✨';
        App.showToast(learnMsg, 'success');
        await App.loadComments();
        this.advanceToNextPendingComment(commentId);
      } else {
        if (res.is_token_expired) {
          App.showToast('⚠️ Respuesta guardada pero NO se publicó en Meta: Token expirado (Error 190). Renuévalo en Configuración.', 'warning', 8000);
          if (App.showTokenExpiredBanner) App.showTokenExpiredBanner();
        } else {
          App.showToast(`Error: ${res.error || 'No se pudo enviar la respuesta.'}`, 'error');
        }
        await App.loadComments();
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al enviar la respuesta.', 'error');
    } finally {
      this.isSubmittingReply = false;
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<span>Publicar Respuesta</span> 🏛️`;
      }
    }
  },

  // =========================================================================
  // POPUP MODAL & DRAWER: Asistente de Respuestas & Conexión
  // =========================================================================

  initAssistantViewMode() {
    this.initAssistantGlobalListeners();
    const userId = (typeof App !== 'undefined' && App.getUserId) ? App.getUserId() : 'default';
    // Por defecto 'modal' para que en pantallas de computador abra como ventana centrada completa y elegante
    const savedMode = localStorage.getItem(`xindro_assistant_view_mode_v2_${userId}`) || 'modal';
    this.setAssistantViewMode(savedMode);
  },

  // Registrar listeners globales de teclado una sola vez (Escape & Focus Trap para Lightbox)
  initAssistantGlobalListeners() {
    if (this._hasInitAssistantListeners) return;
    this._hasInitAssistantListeners = true;

    document.addEventListener('keydown', (e) => {
      const viewer = document.getElementById('modal-post-media-viewer');
      if (!viewer || !viewer.classList.contains('active')) return;

      // 1. ESC / Escape -> Cerrar lightbox y devolver foco
      if (e.key === 'Escape' || e.key === 'Esc') {
        e.preventDefault();
        e.stopPropagation();
        this.closePostMediaViewer();
        return;
      }

      // 2. Focus Trap (Tab & Shift+Tab) dentro del Lightbox aria-modal="true"
      if (e.key === 'Tab') {
        const focusable = viewer.querySelectorAll('button:not([disabled]), [tabindex]:not([tabindex="-1"])');
        if (focusable.length === 0) {
          e.preventDefault();
          return;
        }

        const firstEl = focusable[0];
        const lastEl = focusable[focusable.length - 1];

        if (e.shiftKey) {
          // Shift + Tab (hacia atrás)
          if (document.activeElement === firstEl || !viewer.contains(document.activeElement)) {
            e.preventDefault();
            lastEl.focus();
          }
        } else {
          // Tab (hacia adelante)
          if (document.activeElement === lastEl || !viewer.contains(document.activeElement)) {
            e.preventDefault();
            firstEl.focus();
          }
        }
      }
    });
  },

  setAssistantViewMode(mode) {
    const modal = document.getElementById('modal-assistant-replies');
    const label = document.getElementById('label-assistant-viewmode');
    const isDrawer = (mode === 'drawer');

    if (modal) {
      if (isDrawer) {
        modal.classList.add('drawer-mode');
      } else {
        modal.classList.remove('drawer-mode');
      }
    }
    if (label) {
      label.textContent = isDrawer ? '🗗 Ventana Flotante' : '◧ Panel Lateral';
    }
  },

  toggleAssistantViewMode() {
    const modal = document.getElementById('modal-assistant-replies');
    const isCurrentlyDrawer = modal ? modal.classList.contains('drawer-mode') : false;
    const newMode = isCurrentlyDrawer ? 'modal' : 'drawer';
    const userId = (typeof App !== 'undefined' && App.getUserId) ? App.getUserId() : 'default';

    localStorage.setItem(`xindro_assistant_view_mode_v2_${userId}`, newMode);
    this.setAssistantViewMode(newMode);
    if (typeof App !== 'undefined' && App.showToast) {
      App.showToast(newMode === 'drawer' ? 'Vista: Panel Lateral acoplado a la derecha' : 'Vista: Ventana Flotante Centrada (Pantalla Completa)', 'info', 3000);
    }
  },

  // Open the dedicated Assistant Popup Modal or Drawer
  openAssistantModal(commentId = null) {
    let targetComment = null;

    if (commentId) {
      targetComment = App.commentsList.find(c => c.id == commentId);
    }

    if (!targetComment) {
      if (this.activeComment) {
        targetComment = this.activeComment;
      } else if (App.commentsList.length > 0) {
        // Find first pending or simply first
        targetComment = App.commentsList.find(c => c.status === 'pending') || App.commentsList[0];
      }
    }

    if (!targetComment) {
      App.showToast('No hay comentarios disponibles para analizar.', 'error');
      return;
    }

    this.modalActiveComment = targetComment;
    this.modalActiveReplies = null;
    this.modalSelectedVariant = null;
    const textInput = document.getElementById('modal-reply-text-input');
    if (textInput) textInput.value = '';
    this.populateModalCommentsDropdown(targetComment.id);
    this.updateModalFollowerContext(targetComment);

    // Sync tone from settings or active selection
    const modalToneSelect = document.getElementById('modal-select-tone');
    if (modalToneSelect) {
      const currentTone = document.getElementById('select-tone')?.value || 'stoic_mentor';
      modalToneSelect.value = currentTone;
    }

    // Initialize drawer/modal mode according to user preference
    this.initAssistantViewMode();

    // Switch to manual view by default
    this.switchModalTab('manual');

    // Open the modal/drawer
    App.openModal('modal-assistant-replies');

    // Load AI suggestions for modal
    this.loadModalSuggestions(targetComment);
  },

  // Populate the comment dropdown in modal (Showing ONLY pending comments)
  populateModalCommentsDropdown(selectedId) {
    const select = document.getElementById('modal-select-comment');
    if (!select) return;

    if (!App.commentsList || App.commentsList.length === 0) {
      select.innerHTML = `<option value="">No hay comentarios</option>`;
      return;
    }

    // Filter to show ONLY unreplied (pending) comments
    const pending = App.commentsList.filter(c => c.status === 'pending');

    let listToShow = [...pending];
    // If the currently active comment is replied, keep it visible in select
    if (selectedId && !listToShow.some(c => c.id == selectedId)) {
      const current = App.commentsList.find(c => c.id == selectedId);
      if (current) listToShow.unshift(current);
    }

    if (listToShow.length === 0) {
      select.innerHTML = `<option value="">✨ ¡Al día! Todos los comentarios han sido respondidos</option>`;
      return;
    }

    select.innerHTML = listToShow.map(c => {
      const isSelected = c.id == selectedId;
      const statusIcon = c.status === 'replied' ? '✅' : '⏳';
      const shortSnippet = (c.comment_text || '').substring(0, 48) + ((c.comment_text || '').length > 48 ? '...' : '');
      const author = c.author_name || 'Usuario';
      const platform = c.platform === 'facebook' ? 'FB' : 'IG';
      return `
        <option value="${c.id}" ${isSelected ? 'selected' : ''}>
          ${statusIcon} [${platform}] ${author}: "${shortSnippet}"
        </option>
      `;
    }).join('');
  },

  // Update modal follower context display
  updateModalFollowerContext(comment) {
    const errBox = document.getElementById('modal-reply-error-box');
    if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }

    const avatar = document.getElementById('modal-author-avatar');
    const name = document.getElementById('modal-author-name');
    const handle = document.getElementById('modal-author-handle');
    const badge = document.getElementById('modal-platform-badge');
    const postCaption = document.getElementById('modal-post-caption-preview');
    const quote = document.getElementById('modal-comment-quote-text');
    const score = document.getElementById('modal-score-badge');
    const reason = document.getElementById('modal-reason-banner');
    const sentiment = document.getElementById('modal-sentiment-tag');
    const accNameBadge = document.getElementById('modal-account-name-badge');
    const brandVoiceBadge = document.getElementById('modal-brand-voice-badge');

    if (accNameBadge) {
      const accName = comment.account_name || comment.account_handle || (comment.platform === 'facebook' ? 'Página FB' : '@cuenta_ig');
      const icon = comment.platform === 'facebook' ? '📘' : '📸';
      accNameBadge.textContent = `${icon} ${accName}`;
    }
    if (brandVoiceBadge) {
      brandVoiceBadge.textContent = comment.brand_voice_name || 'Voz por Defecto';
    }

    if (avatar) avatar.src = App.sanitizeUrl(comment.author_avatar, 'https://ui-avatars.com/api/?name=User');
    if (name) name.textContent = comment.author_name || 'Seguidor';
    if (handle) handle.textContent = comment.author_handle || `@${(comment.author_name || 'usuario').toLowerCase().replace(/\s+/g, '')}`;
    if (badge) {
      badge.className = `platform-badge-mini ${comment.platform === 'facebook' ? 'facebook' : 'instagram'}`;
      badge.textContent = comment.platform === 'facebook' ? 'FB' : 'IG';
    }
    if (postCaption) {
      postCaption.textContent = comment.post_caption ? `Sobre: "${comment.post_caption}"` : 'Publicación de la comunidad';
    }
    if (quote) quote.textContent = comment.comment_text || '';
    if (score) score.textContent = `⭐ Prioridad Comercial: ${parseInt(comment.highlight_score, 10) || 50}/100`;
    
    if (reason) {
      reason.textContent = comment.highlight_reason ? `✨ Análisis: ${comment.highlight_reason}` : '✨ Análisis de conexión y engagement comunitario activo.';
    }

    if (sentiment) {
      let sentimentText = '✨ Engagement Activo';
      let sentimentStyle = 'background: rgba(99,102,241,0.15); color: #a5b4fc;';

      if (comment.sentiment === 'lead' || (comment.intent && comment.intent.startsWith('lead_'))) {
        sentimentText = '🧠 Pregunta / Consejo';
        sentimentStyle = 'background: rgba(16,185,129,0.15); color: #34d399;';
      } else if (comment.sentiment === 'urgent' || comment.intent === 'support') {
        sentimentText = '🛡️ Apoyo / Resiliencia';
        sentimentStyle = 'background: rgba(244,63,94,0.15); color: #fb7185;';
      } else if (comment.is_highlighted == 1 || comment.highlight_score >= 80) {
        sentimentText = '⭐ Resaltante';
        sentimentStyle = 'background: rgba(245,158,11,0.15); color: #fbbf24;';
      }
      sentiment.textContent = sentimentText;
      sentiment.setAttribute('style', sentimentStyle);
    }

    // Actualizar bloque visual de la publicación de origen (imagen, caption y visor)
    this.updatePostOriginCard(comment);
  },

  // Validar URL de imagen: HTTPS para orígenes remotos; HTTP únicamente para localhost de pruebas
  isValidPostImageUrl(url) {
    if (!url || typeof url !== 'string') return false;
    try {
      const parsed = new URL(url.trim());
      if (parsed.protocol === 'https:') return true;
      return parsed.protocol === 'http:' &&
        (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1');
    } catch (e) {
      return false;
    }
  },

  // Actualizar la miniatura y contexto de la publicación original en el modal
  updatePostOriginCard(comment) {
    const card = document.getElementById('modal-post-origin-card');
    const mediaBtn = document.getElementById('modal-post-origin-media-btn');
    const img = document.getElementById('modal-post-origin-img');
    const fallback = document.getElementById('modal-post-origin-fallback');
    const captionEl = document.getElementById('modal-post-origin-caption');
    const badge = document.getElementById('modal-post-origin-platform-badge');

    if (!card) return;

    const rawUrl = comment.post_media_url || '';
    const isValid = this.isValidPostImageUrl(rawUrl);

    if (isValid) {
      if (img) {
        img.src = rawUrl;
      }
      if (mediaBtn) {
        mediaBtn.style.display = 'flex';
        mediaBtn.disabled = false;
        const captionPreview = comment.post_caption ? comment.post_caption.substring(0, 60) : 'ver imagen';
        mediaBtn.setAttribute('aria-label', `Ampliar imagen de la publicación original: ${captionPreview}`);
      }
      if (fallback) {
        fallback.style.display = 'none';
      }
    } else {
      if (img) img.src = '';
      if (mediaBtn) {
        mediaBtn.style.display = 'none';
        mediaBtn.disabled = true;
      }
      if (fallback) {
        fallback.style.display = 'flex';
      }
    }

    if (captionEl) {
      captionEl.textContent = comment.post_caption ? comment.post_caption : 'Publicación de la comunidad sin descripción adicional.';
    }

    if (badge) {
      const isFb = (comment.platform === 'facebook' || comment.post_platform === 'facebook');
      badge.className = `platform-badge-mini ${isFb ? 'facebook' : 'instagram'}`;
      badge.textContent = isFb ? 'FB' : 'IG';
    }
  },

  // Fallback seguro cuando una imagen de Meta o CDN falla al cargar (404 / expirado)
  onPostImageError(imgEl) {
    if (imgEl) {
      imgEl.onerror = null;
      imgEl.removeAttribute('src');
    }
    const mediaBtn = document.getElementById('modal-post-origin-media-btn');
    if (mediaBtn) {
      mediaBtn.style.display = 'none';
      mediaBtn.disabled = true;
    }
    const fallback = document.getElementById('modal-post-origin-fallback');
    if (fallback) {
      fallback.style.display = 'flex';
    }
  },

  // Abrir Visor Ampliado (Lightbox) con gestión accesible de foco
  openPostMediaViewer() {
    const comment = this.modalActiveComment;
    if (!comment) return;

    const mediaUrl = comment.post_media_url || '';
    if (!this.isValidPostImageUrl(mediaUrl)) return;

    const viewer = document.getElementById('modal-post-media-viewer');
    const expandedImg = document.getElementById('lightbox-expanded-img');
    const captionText = document.getElementById('lightbox-caption-text');
    const closeBtn = document.getElementById('btn-close-post-lightbox');

    if (!viewer) return;

    // Guardar elemento activo para restaurar el foco al cerrar
    this.mediaViewerTriggerEl = document.activeElement;

    if (expandedImg) {
      expandedImg.src = mediaUrl;
    }
    if (captionText) {
      captionText.textContent = comment.post_caption ? `📌 ${comment.post_caption}` : '📌 Publicación sin descripción adicional.';
    }

    viewer.classList.add('active');

    // Mover foco accesible al botón de cierre
    if (closeBtn) {
      closeBtn.focus();
    }
  },

  // Cerrar Visor Ampliado y restaurar foco
  closePostMediaViewer() {
    const viewer = document.getElementById('modal-post-media-viewer');
    if (viewer) {
      viewer.classList.remove('active');
    }
    // Restaurar foco al botón disparador que abrió el visor
    if (this.mediaViewerTriggerEl && typeof this.mediaViewerTriggerEl.focus === 'function') {
      try {
        this.mediaViewerTriggerEl.focus();
      } catch (e) {
        // Safe focus restoration
      }
    }
  },

  // On comment selection changed in modal dropdown
  onModalCommentChange(commentId) {
    if (!commentId) return;
    const comment = App.commentsList.find(c => c.id == commentId);
    if (comment) {
      this.modalActiveComment = comment;
      this.modalActiveReplies = null;
      this.modalSelectedVariant = null;
      const textInput = document.getElementById('modal-reply-text-input');
      if (textInput) textInput.value = '';
      this.updateModalFollowerContext(comment);
      this.loadModalSuggestions(comment);
    }
  },

  // On tone changed in modal
  onModalToneChange(tone) {
    if (this.modalActiveComment) {
      this.loadModalSuggestions(this.modalActiveComment, tone);
    }
  },

  // Refresh modal suggestions
  refreshModalSuggestions() {
    if (this.modalActiveComment) {
      const tone = document.getElementById('modal-select-tone')?.value || '';
      this.loadModalSuggestions(this.modalActiveComment, tone);
      App.showToast('Regenerando sugerencias con IA...', 'success');
    }
  },

  // Load 3 AI suggestions specifically into the modal
  async loadModalSuggestions(comment, overrideTone = '') {
    const container = document.getElementById('modal-suggestions-container');
    if (!container) return;

    this.isGeneratingModalSuggestions = true;
    this.modalActiveReplies = null;

    const captureBtn = document.getElementById('btn-modal-capture-image');
    if (captureBtn) {
      captureBtn.disabled = true;
      captureBtn.style.opacity = '0.6';
      captureBtn.innerHTML = '<span>⏳ Generando IA...</span>';
    }

    container.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 28px; text-align: center; color: var(--text-muted);">
        <div style="display: inline-block; width: 28px; height: 28px; border: 3px solid rgba(99,102,241,0.3); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 10px;"></div>
        <p style="font-size: 0.85rem; font-weight: 700; color: #fff;">Forjando 3 sugerencias con IA (Reflexiva, Motivacional y Comunitaria)...</p>
        <span style="font-size: 0.74rem; color: var(--text-dim);">Adaptando el mensaje a la filosofía estoica y voz de marca</span>
      </div>
    `;

    try {
      const tone = overrideTone || document.getElementById('modal-select-tone')?.value || '';
      const response = await App.fetchWithCsrf('api/agent.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'generate_replies',
          comment_id: parseInt(comment.id, 10),
          tone: tone
        })
      });

      const res = await response.json();

      if (res.success && res.replies) {
        this.modalActiveReplies = res.replies;
        this.renderModalSuggestionCards(res.replies);

        // Auto-select initial variant
        let defaultVar = 'engagement';
        if (comment.sentiment === 'urgent' || comment.intent === 'support') {
          defaultVar = 'support';
        } else if (comment.sentiment === 'lead' || (comment.intent && comment.intent.startsWith('lead_'))) {
          defaultVar = 'conversion';
        }
        this.selectModalVariant(defaultVar);
      } else {
        this.modalActiveReplies = null;
        container.innerHTML = `
          <div style="grid-column: 1 / -1; padding: 16px; color: var(--accent-rose); font-size: 0.84rem; background: rgba(244,63,94,0.1); border-radius: 8px;">
            ⚠️ No se pudieron generar sugerencias: ${this.escapeHtml(res.error || 'Error desconocido')}
            <div style="margin-top: 10px;">
              <button type="button" class="btn-modal-sugg-action" onclick="AgentController.refreshModalSuggestions()" style="background: rgba(244,63,94,0.2); color: #fff; cursor: pointer; padding: 6px 12px; border-radius: 6px;">
                🔄 Reintentar Ahora
              </button>
            </div>
          </div>
        `;
      }
    } catch (err) {
      console.error(err);
      this.modalActiveReplies = null;
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 16px; color: var(--accent-rose); font-size: 0.84rem; background: rgba(244,63,94,0.1); border-radius: 8px;">
          ⚠️ Error de conexión con el motor de IA.
          <div style="margin-top: 10px;">
            <button type="button" class="btn-modal-sugg-action" onclick="AgentController.refreshModalSuggestions()" style="background: rgba(244,63,94,0.2); color: #fff; cursor: pointer; padding: 6px 12px; border-radius: 6px;">
              🔄 Reintentar Ahora
            </button>
          </div>
        </div>
      `;
    } finally {
      this.isGeneratingModalSuggestions = false;
      const captureBtn = document.getElementById('btn-modal-capture-image');
      if (captureBtn) {
        captureBtn.disabled = false;
        captureBtn.style.opacity = '1';
        captureBtn.innerHTML = '<span>📸 Capturar Imagen</span>';
      }
    }
  },

  // Render the 3 variant cards inside the popup modal
  renderModalSuggestionCards(replies) {
    const container = document.getElementById('modal-suggestions-container');
    if (!container) return;

    const badgeHtml = this.getLanguageBadgeHtml(replies);

    container.innerHTML = `
      ${badgeHtml}
      <!-- Variant 1: Connection & Empathy -->
      <div class="modal-suggestion-card card-reflection active" id="modal-card-engagement" onclick="AgentController.selectModalVariant('engagement')">
        <div class="modal-suggestion-header">
          <span class="modal-suggestion-tag reflection">🤝 Opción 1: Conexión & Empatía</span>
          <span class="modal-suggestion-sub">Cercanía, Agradecimiento & Pregunta</span>
        </div>
        <div class="modal-suggestion-text" id="modal-text-engagement">${this.escapeHtml(replies.engagement || '')}</div>
        <div class="modal-suggestion-actions">
          <button type="button" class="btn-modal-sugg-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.engagement)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(6,182,212,0.18); color: #67e8f9;" onclick="event.stopPropagation(); AgentController.selectModalVariant('engagement')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;" onclick="event.stopPropagation(); AgentController.saveAsGoldExample('engagement', true)" title="⭐ Guardar permanentemente como Ejemplo de Oro para Gemini">
            ⭐ Oro
          </button>
          <button type="button" class="btn-modal-sugg-action btn-modal-sugg-quicksend" onclick="event.stopPropagation(); AgentController.quickPostModalVariant('engagement')" title="Publicar directamente esta opción con 1 clic">
            ⚡ Enviar Directo (1 Clic)
          </button>
        </div>
      </div>

      <!-- Variant 2: Stoic Wisdom & Character -->
      <div class="modal-suggestion-card card-motivation" id="modal-card-conversion" onclick="AgentController.selectModalVariant('conversion')">
        <div class="modal-suggestion-header">
          <span class="modal-suggestion-tag motivation">🏛️ Opción 2: Sabiduría & Fortaleza Estoica</span>
          <span class="modal-suggestion-sub">Profundidad Filosófica & Autodominio</span>
        </div>
        <div class="modal-suggestion-text" id="modal-text-conversion">${this.escapeHtml(replies.conversion || '')}</div>
        <div class="modal-suggestion-actions">
          <button type="button" class="btn-modal-sugg-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.conversion)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(16,185,129,0.18); color: #6ee7b7;" onclick="event.stopPropagation(); AgentController.selectModalVariant('conversion')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;" onclick="event.stopPropagation(); AgentController.saveAsGoldExample('conversion', true)" title="⭐ Guardar permanentemente como Ejemplo de Oro para Gemini">
            ⭐ Oro
          </button>
          <button type="button" class="btn-modal-sugg-action btn-modal-sugg-quicksend" onclick="event.stopPropagation(); AgentController.quickPostModalVariant('conversion')" title="Publicar directamente esta opción con 1 clic">
            ⚡ Enviar Directo (1 Clic)
          </button>
        </div>
      </div>

      <!-- Variant 3: Drive & Determination -->
      <div class="modal-suggestion-card card-community" id="modal-card-support" onclick="AgentController.selectModalVariant('support')">
        <div class="modal-suggestion-header">
          <span class="modal-suggestion-tag community">⚡ Opción 3: Impulso & Determinación</span>
          <span class="modal-suggestion-sub">Energía, Resiliencia & Disciplina</span>
        </div>
        <div class="modal-suggestion-text" id="modal-text-support">${this.escapeHtml(replies.support || '')}</div>
        <div class="modal-suggestion-actions">
          <button type="button" class="btn-modal-sugg-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.support)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(168,85,247,0.18); color: #d8b4fe;" onclick="event.stopPropagation(); AgentController.selectModalVariant('support')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(245,158,11,0.2); color: #fbbf24; font-weight: 700;" onclick="event.stopPropagation(); AgentController.saveAsGoldExample('support', true)" title="⭐ Guardar permanentemente como Ejemplo de Oro para Gemini">
            ⭐ Oro
          </button>
          <button type="button" class="btn-modal-sugg-action btn-modal-sugg-quicksend" onclick="event.stopPropagation(); AgentController.quickPostModalVariant('support')" title="Publicar directamente esta opción con 1 clic">
            ⚡ Enviar Directo (1 Clic)
          </button>
        </div>
      </div>

      ${replies.engagement_tips ? `
        <div style="grid-column: 1 / -1; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); border-radius: var(--radius-sm); padding: 8px 14px; font-size: 0.78rem; color: #fbbf24;">
          💡 <strong>Estrategia de Conexión:</strong> ${this.escapeHtml(replies.engagement_tips)}
        </div>
      ` : ''}
    `;
  },

  // Select a variant in the modal
  selectModalVariant(variantType) {
    this.modalSelectedVariant = variantType;

    // Highlight active card
    document.querySelectorAll('.modal-suggestion-card').forEach(el => el.classList.remove('active'));
    const targetCard = document.getElementById(`modal-card-${variantType}`);
    if (targetCard) targetCard.classList.add('active');

    // Populate modal textarea
    const textarea = document.getElementById('modal-reply-text-input');
    if (textarea && this.modalActiveReplies && this.modalActiveReplies[variantType]) {
      textarea.value = this.modalActiveReplies[variantType];
      textarea.focus();
    }
  },

  // Quick post a specific variant directly
  async quickPostModalVariant(variantType) {
    this.selectModalVariant(variantType);
    await this.submitModalReply();
  },

  // Insert emoji in modal textarea
  insertModalEmoji(emoji) {
    const textarea = document.getElementById('modal-reply-text-input');
    if (!textarea) return;
    const start = textarea.selectionStart || 0;
    const end = textarea.selectionEnd || 0;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + emoji + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
  },

  // Submit reply from the modal
  async submitModalReply() {
    if (this.isSubmittingReply) return;

    if (!this.modalActiveComment) {
      App.showToast('No hay comentario activo para responder.', 'error');
      return;
    }

    const textarea = document.getElementById('modal-reply-text-input') || document.getElementById('modal-custom-reply-text');
    let replyText = textarea?.value?.trim() || '';

    // Si el textarea estuviera vacío pero hay una opción seleccionada, usarla como respaldo
    if (!replyText && this.modalSelectedVariant && this.modalActiveReplies && this.modalActiveReplies[this.modalSelectedVariant]) {
      replyText = this.modalActiveReplies[this.modalSelectedVariant].trim();
      if (textarea) textarea.value = replyText;
    }

    if (!replyText) {
      App.showToast('El texto de la respuesta no puede estar vacío.', 'error');
      return;
    }

    this.isSubmittingReply = true;
    const commentId = parseInt(this.modalActiveComment.id, 10);
    const originalSuggestion = (this.modalActiveReplies && this.modalActiveReplies[this.modalSelectedVariant]) ? this.modalActiveReplies[this.modalSelectedVariant].trim() : '';
    const wasEdited = !!(originalSuggestion && originalSuggestion !== replyText);

    const btn = document.getElementById('btn-modal-submit-reply');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span>⏳ Publicando...</span>`;
    }

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'reply',
          comment_id: commentId,
          reply_text: replyText,
          variant_type: this.modalSelectedVariant,
          tone_used: document.getElementById('modal-select-tone')?.value || 'stoic_mentor',
          was_edited: wasEdited,
          original_suggestion: originalSuggestion
        })
      });

      const res = await response.json();

      if (res.success) {
        const learnMsg = wasEdited 
          ? '¡Respuesta publicada y corrección aprendida por Gemini! 🧠✨' 
          : '¡Respuesta aprobada y publicada! Gemini registró el éxito de este patrón. 🏛️✨';
        App.showToast(learnMsg, 'success');
        const errBox = document.getElementById('modal-reply-error-box');
        if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }
        App.closeModal('modal-assistant-replies');
        await App.loadComments();
        this.advanceToNextPendingComment(commentId);
      } else {
        if (res.is_token_expired) {
          App.showToast('⚠️ Respuesta guardada pero NO se publicó en Meta: Token expirado (Error 190). Renuévalo en Configuración.', 'warning', 8000);
          if (App.showTokenExpiredBanner) App.showTokenExpiredBanner();
        } else {
          App.showToast(`Error: ${res.error || 'No se pudo enviar la respuesta.'}`, 'error');
        }

        // Show interactive recovery banner inside the modal
        const errBox = document.getElementById('modal-reply-error-box');
        if (errBox) {
          errBox.style.display = 'block';
          errBox.innerHTML = `
            <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 10px 14px; font-size: 0.82rem; color: #fca5a5;">
              <div style="font-weight: 700; margin-bottom: 4px;">⚠️ ${App.escapeHtml(res.error || 'Fallo de publicación')}</div>
              <p style="margin: 0 0 8px; font-size: 0.78rem; color: #cbd5e1; line-height: 1.4;">
                A veces la red social procesa el mensaje pero la conexión se corta. Si el mensaje se publicó, puedes comprobarlo o marcarlo como respondido:
              </p>
              <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" class="btn-primary-action" style="padding: 5px 12px; font-size: 0.78rem; background: rgba(99, 102, 241, 0.25); border: 1px solid rgba(99, 102, 241, 0.45); color: #c7d2fe; cursor: pointer;" onclick="AgentController.verifyActiveCommentOnPlatform()">
                  🔍 Comprobar si ya se publicó
                </button>
                <button type="button" class="btn-primary-action" style="padding: 5px 12px; font-size: 0.78rem; background: rgba(52, 211, 153, 0.18); border: 1px solid rgba(52, 211, 153, 0.4); color: #6ee7b7; cursor: pointer;" onclick="AgentController.markActiveCommentAsReplied()">
                  ✅ Ya la vi publicada (Marcar Respondido)
                </button>
              </div>
            </div>
          `;
        }
        // Do NOT close the modal on error so the assistant remains visible for capture or edits
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al enviar la respuesta.', 'error');
      const errBox = document.getElementById('modal-reply-error-box');
      if (errBox) {
        errBox.style.display = 'block';
        errBox.innerHTML = `
          <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 8px; padding: 10px 14px; font-size: 0.82rem; color: #fca5a5;">
            <div style="font-weight: 700; margin-bottom: 4px;">⚠️ Error de conexión o red</div>
            <p style="margin: 0 0 8px; font-size: 0.78rem; color: #cbd5e1; line-height: 1.4;">
              Hubo una interrupción en la red. Si el mensaje llegó a publicarse en Instagram/Facebook, puedes verificarlo aquí:
            </p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
              <button type="button" class="btn-primary-action" style="padding: 5px 12px; font-size: 0.78rem; background: rgba(99, 102, 241, 0.25); border: 1px solid rgba(99, 102, 241, 0.45); color: #c7d2fe; cursor: pointer;" onclick="AgentController.verifyActiveCommentOnPlatform()">
                🔍 Comprobar si ya se publicó
              </button>
              <button type="button" class="btn-primary-action" style="padding: 5px 12px; font-size: 0.78rem; background: rgba(52, 211, 153, 0.18); border: 1px solid rgba(52, 211, 153, 0.4); color: #6ee7b7; cursor: pointer;" onclick="AgentController.markActiveCommentAsReplied()">
                ✅ Ya la vi publicada (Marcar Respondido)
              </button>
            </div>
          </div>
        `;
      }
    } finally {
      this.isSubmittingReply = false;
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<span>Publicar Respuesta</span> 🏛️`;
      }
    }
  },

  // Verify whether the currently active comment was published on Instagram/Facebook
  async verifyActiveCommentOnPlatform() {
    const comment = this.modalActiveComment || this.activeComment;
    if (!comment || !comment.id) return;

    const commentId = parseInt(comment.id, 10);
    const platName = comment.platform === 'facebook' ? 'Facebook' : 'Instagram';
    App.showToast(`Verificando publicación en ${platName}... 🔍`, 'info');

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'verify_platform_reply',
          comment_id: commentId
        })
      });

      const res = await response.json();
      if (res.success && res.is_replied) {
        App.showToast(`¡Confirmado! La respuesta ya está visible en ${platName}. Estado sincronizado a Respondido. ✅✨`, 'success', 6000);
        const errBox = document.getElementById('modal-reply-error-box');
        if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }
        App.closeModal('modal-assistant-replies');
        await App.loadComments();
        this.advanceToNextPendingComment(commentId);
      } else {
        App.showToast(`No se detectó respuesta pública de tu cuenta en ${platName}. Puedes intentar enviar nuevamente.`, 'warning', 5000);
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al verificar estado en la red.', 'error');
    }
  },

  // Manually mark currently active comment as replied
  async markActiveCommentAsReplied() {
    const comment = this.modalActiveComment || this.activeComment;
    if (!comment || !comment.id) return;

    const commentId = parseInt(comment.id, 10);
    const platName = comment.platform === 'facebook' ? 'Facebook' : 'Instagram';

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'mark_as_replied',
          comment_id: commentId
        })
      });

      const res = await response.json();
      if (res.success) {
        App.showToast(`¡Comentario marcado como respondido en ${platName}! 🏛️✨`, 'success');
        const errBox = document.getElementById('modal-reply-error-box');
        if (errBox) { errBox.style.display = 'none'; errBox.innerHTML = ''; }
        App.closeModal('modal-assistant-replies');
        await App.loadComments();
        this.advanceToNextPendingComment(commentId);
      } else {
        App.showToast(`Error: ${res.error || 'No se pudo actualizar'}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al marcar como respondido.', 'error');
    }
  },

  // Open the Ignore Confirmation Modal for the active comment
  openIgnoreConfirmationModal() {
    const comment = this.modalActiveComment || this.activeComment;
    if (!comment) {
      App.showToast('No hay un comentario activo seleccionado para ignorar.', 'warning');
      return;
    }

    const authorEl = document.getElementById('modal-ignore-author');
    const platEl = document.getElementById('modal-ignore-platform');
    const snippetEl = document.getElementById('modal-ignore-snippet');
    const reasonSelect = document.getElementById('ignore-reason-select');
    const notesInput = document.getElementById('ignore-notes-input');
    const counterEl = document.getElementById('ignore-notes-counter');

    if (authorEl) authorEl.textContent = comment.author_name || 'Seguidor';
    if (platEl) {
      platEl.className = `platform-badge-mini ${comment.platform === 'facebook' ? 'facebook' : 'instagram'}`;
      platEl.textContent = comment.platform === 'facebook' ? 'FB' : 'IG';
    }
    if (snippetEl) {
      const rawText = comment.comment_text || '';
      snippetEl.textContent = `"${rawText.length > 180 ? rawText.substring(0, 180) + '...' : rawText}"`;
    }

    // Heuristically pre-select most likely reason
    if (reasonSelect) {
      const txtLower = (comment.comment_text || '').toLowerCase();
      if (txtLower.includes('http') || txtLower.includes('www.') || txtLower.includes('.com') || txtLower.includes('dm ') || txtLower.includes('whatsapp') || txtLower.includes('telegram')) {
        reasonSelect.value = 'spam_link';
      } else {
        reasonSelect.value = 'troll_provocation';
      }
    }

    if (notesInput) notesInput.value = '';
    if (counterEl) counterEl.textContent = '0 / 500';

    App.openModal('modal-confirm-ignore');
    setTimeout(() => {
      reasonSelect?.focus();
    }, 100);
  },

  // Close the Ignore Confirmation Modal
  closeIgnoreConfirmationModal() {
    App.closeModal('modal-confirm-ignore');
  },

  // Character counter for ignore notes textarea
  onIgnoreNotesInput(textarea) {
    const counter = document.getElementById('ignore-notes-counter');
    if (!counter || !textarea) return;
    const len = (textarea.value || '').length;
    counter.textContent = `${len} / 500`;
  },

  // Submit Ignore Comment action to api/comments.php
  async submitIgnoreComment() {
    if (this.isSubmittingIgnore) return;

    const comment = this.modalActiveComment || this.activeComment;
    if (!comment || !comment.id) {
      App.showToast('No se encontró el comentario a ignorar.', 'error');
      return;
    }

    const commentId = parseInt(comment.id, 10);
    const reasonSelect = document.getElementById('ignore-reason-select');
    const notesInput = document.getElementById('ignore-notes-input');
    const reason = reasonSelect ? reasonSelect.value : 'irrelevant';
    const notes = notesInput ? notesInput.value.trim() : '';

    const btnSubmit = document.getElementById('btn-submit-confirm-ignore');
    const labelSubmit = document.getElementById('label-submit-ignore');
    const btnModalIgnore = document.getElementById('btn-modal-ignore-comment');

    this.isSubmittingIgnore = true;
    if (btnSubmit) btnSubmit.disabled = true;
    if (btnModalIgnore) btnModalIgnore.disabled = true;
    if (labelSubmit) labelSubmit.textContent = '⏳ Ignorando...';

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'ignore_comment',
          comment_id: commentId,
          reason: reason,
          notes: notes
        })
      });

      const res = await response.json();

      if (res.success) {
        App.showToast('Comentario marcado como ignorado. Hermes registró el patrón de descarte. 🛡️', 'success');
        this.closeIgnoreConfirmationModal();

        // Mutate status locally in memory to keep UI responsive immediately
        const localComment = App.commentsList?.find(c => c.id == commentId);
        if (localComment) {
          localComment.status = 'ignored';
          localComment.highlight_reason = '🚫 Omitido por el moderador';
        }

        // Refresh counts and comment list in background without reloading whole page
        await App.loadComments();

        // Transition seamlessly to next pending comment
        this.advanceToNextPendingComment(commentId);
      } else {
        App.showToast(`Error: ${res.error || 'No se pudo ignorar el comentario.'}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error de conexión al procesar la omisión.', 'error');
    } finally {
      this.isSubmittingIgnore = false;
      if (btnSubmit) btnSubmit.disabled = false;
      if (btnModalIgnore) btnModalIgnore.disabled = false;
      if (labelSubmit) labelSubmit.textContent = '🚫 Confirmar y no responder';
    }
  },

  // Transition seamlessly to next pending comment after successful publication or ignore
  advanceToNextPendingComment(repliedCommentId) {
    if (!App.commentsList || App.commentsList.length === 0) {
      this.activeComment = null;
      this.clearCopilotView();
      return;
    }

    const nextPending = App.commentsList.find(c => c.id != repliedCommentId && (c.status === 'pending' || c.status === 'failed'));
    if (nextPending) {
      App.selectComment(nextPending);
      this.loadSuggestions(nextPending);

      // If Assistant modal/drawer is open, also seamlessly update the modal
      const modal = document.getElementById('modal-assistant-replies');
      if (modal && (modal.classList.contains('active') || modal.style.display === 'flex' || modal.style.display === 'block')) {
        this.modalActiveComment = nextPending;
        this.modalActiveReplies = null;
        this.modalSelectedVariant = null;
        const textInput = document.getElementById('modal-reply-text-input');
        if (textInput) textInput.value = '';
        this.populateModalCommentsDropdown(nextPending.id);
        this.updateModalFollowerContext(nextPending);
        this.loadModalSuggestions(nextPending);
      }
    } else {
      this.activeComment = null;
      this.clearCopilotView();
      // If modal was open, close it gently and notify user
      const modal = document.getElementById('modal-assistant-replies');
      if (modal && modal.classList.contains('active')) {
        App.closeModal('modal-assistant-replies');
        App.showToast('🎉 ¡Bandeja al día! Todos los comentarios pendientes han sido atendidos.', 'success', 5000);
      }
    }
  },

  clearCopilotView() {
    const suggestionsContainer = document.getElementById('suggestions-container');
    if (suggestionsContainer) {
      suggestionsContainer.innerHTML = `
        <div style="text-align: center; padding: 40px 20px; color: var(--text-dim);">
          <div style="font-size: 2.2rem; margin-bottom: 12px;">🎉</div>
          <h4 style="color: #cbd5e1; font-weight: 600; margin-bottom: 6px;">¡Bandeja al día!</h4>
          <p style="font-size: 0.85rem; max-width: 280px; margin: 0 auto; line-height: 1.4;">
            No quedan comentarios pendientes en esta vista. Selecciona otro comentario de la lista para continuar.
          </p>
        </div>
      `;
    }
    const threadHistoryBox = document.getElementById('copilot-thread-history');
    if (threadHistoryBox) threadHistoryBox.style.display = 'none';
    const textarea = document.getElementById('reply-text-input');
    if (textarea) textarea.value = '';
  },

  // Switch sub-tabs inside Assistant Modal (Manual Suggestions vs Live Autopilot)
  switchModalTab(tab) {
    const btnManual = document.getElementById('modal-tab-btn-manual');
    const btnAutopilot = document.getElementById('modal-tab-btn-autopilot');
    const viewManual = document.getElementById('modal-view-manual');
    const viewAutopilot = document.getElementById('modal-view-autopilot');

    if (tab === 'autopilot') {
      if (btnManual) btnManual.classList.remove('active');
      if (btnAutopilot) btnAutopilot.classList.add('active');
      if (viewManual) viewManual.style.display = 'none';
      if (viewAutopilot) viewAutopilot.style.display = 'block';
      this.updateAutopilotPendingBadge();
    } else {
      if (btnAutopilot) btnAutopilot.classList.remove('active');
      if (btnManual) btnManual.classList.add('active');
      if (viewAutopilot) viewAutopilot.style.display = 'none';
      if (viewManual) viewManual.style.display = 'block';
    }
  },

  updateAutopilotPendingBadge() {
    const badge = document.getElementById('autopilot-pending-count-badge');
    if (!badge) return;
    const pending = App.commentsList ? App.commentsList.filter(c => c.status === 'pending' || c.status === 'failed') : [];
    const highPending = pending.filter(c => (c.is_highlighted == 1 || c.highlight_score >= 80));
    badge.textContent = `${highPending.length} de alto impacto listos (${pending.length} pendientes en total)`;
  },

  autopilotPaused: false,

  pauseLiveAutopilot() {
    this.autopilotPaused = true;
    const progressStatus = document.getElementById('autopilot-progress-status');
    if (progressStatus) progressStatus.textContent = '⏸️ Pausando el Auto-Responder tras el comentario actual...';
    App.showToast('Pausa solicitada. Se detendrá al terminar el comentario actual.', 'info');
  },

  async resetFailedComments() {
    if (!confirm('¿Deseas restablecer los comentarios fallidos a pendientes para volver a procesarlos con el Auto-Responder?')) return;
    try {
      const res = await App.fetchWithCsrf('api/agent.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'reset_failed_comments' })
      }).then(r => r.json());
      if (res.success) {
        App.showToast(res.message, 'success');
        await App.loadComments();
        this.updateAutopilotPendingBadge();
      } else {
        App.showToast(res.error || 'Error al restablecer comentarios', 'error');
      }
    } catch (e) {
      App.showToast('Error de conexión al restablecer comentarios', 'error');
    }
  },

  // Live Autopilot Execution with Real-Time Step-by-Step UI & Anti-Bot Cadence
  async startLiveAutopilot() {
    const btn = document.getElementById('btn-run-autopilot-live');
    const btnText = document.getElementById('btn-run-autopilot-live-text');
    const btnPause = document.getElementById('btn-pause-autopilot-live');
    const progressContainer = document.getElementById('autopilot-progress-container');
    const progressBar = document.getElementById('autopilot-progress-bar');
    const progressStatus = document.getElementById('autopilot-progress-status');
    const progressPercent = document.getElementById('autopilot-progress-percent');
    const streamList = document.getElementById('autopilot-stream-list');

    this.autopilotPaused = false;

    if (btn) btn.disabled = true;
    if (btnText) btnText.textContent = 'Iniciando Piloto...';
    if (btnPause) btnPause.style.display = 'inline-flex';
    if (progressContainer) progressContainer.style.display = 'block';
    if (progressBar) progressBar.style.width = '5%';
    if (progressPercent) progressPercent.textContent = '5%';
    if (progressStatus) progressStatus.textContent = '🔍 Obteniendo cola de comentarios pendientes para Gemini...';

    try {
      // 1. Fetch queue item by item
      const qRes = await App.fetchWithCsrf('api/agent.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'get_autopilot_queue', include_failed: true })
      }).then(r => r.json());

      if (!qRes.success) {
        App.showToast(`Error al obtener cola: ${qRes.error || 'Error de conexión'}`, 'error');
        if (progressStatus) progressStatus.textContent = '❌ Error al consultar cola de comentarios';
        return;
      }

      const queue = qRes.queue || [];
      if (queue.length === 0) {
        if (progressBar) progressBar.style.width = '100%';
        if (progressPercent) progressPercent.textContent = '100%';
        if (progressStatus) progressStatus.textContent = '✅ Todos los comentarios ya han sido procesados.';
        if (streamList) {
          streamList.innerHTML = `
            <div class="autopilot-empty-state">
              <span style="font-size: 2rem;">✨</span>
              <p style="font-weight: 700; color: #fff; margin-top: 6px;">Todo al día</p>
              <p style="font-size: 0.78rem; color: var(--text-muted);">No hay comentarios pendientes por responder en este momento.</p>
            </div>
          `;
        }
        App.showToast('No hay comentarios pendientes para responder automáticamente.', 'success');
        return;
      }

      if (streamList) streamList.innerHTML = '';

      let repliedCount = 0;
      let failedCount = 0;
      let spamCount = 0;
      let ignoredCount = 0;

      // 2. Iterate comment by comment (Zero timeout, real-time live feed)
      for (let i = 0; i < queue.length; i++) {
        if (this.autopilotPaused) {
          if (progressStatus) progressStatus.textContent = `⏸️ Piloto Automático pausado (${i}/${queue.length} procesados).`;
          App.showToast(`Auto-Responder pausado. Se procesaron ${i} comentarios.`, 'info');
          break;
        }

        const comment = queue[i];
        const currentPercent = Math.round(((i) / queue.length) * 100);
        if (progressBar) progressBar.style.width = `${Math.max(5, currentPercent)}%`;
        if (progressPercent) progressPercent.textContent = `${Math.max(5, currentPercent)}%`;
        if (progressStatus) progressStatus.textContent = `⚡ [${i + 1}/${queue.length}] Gemini analizando y respondiendo a @${this.escapeHtml(comment.author_name)}...`;

        let itemResult = null;
        try {
          const singleRes = await App.fetchWithCsrf('api/agent.php', {
            method: 'POST',
            body: JSON.stringify({
              action: 'autopilot_single_comment',
              comment_id: parseInt(comment.id, 10),
              reply_index: i
            })
          }).then(r => r.json());

          if (singleRes.success && singleRes.item) {
            itemResult = singleRes.item;
          } else {
            itemResult = {
              comment_id: comment.id,
              author: comment.author_name,
              action: 'failed',
              reply: 'No se pudo generar respuesta',
              status: 'failed',
              error: singleRes.error || 'Error desconocido'
            };
          }
        } catch (itemErr) {
          console.error(itemErr);
          itemResult = {
            comment_id: comment.id,
            author: comment.author_name,
            action: 'failed',
            reply: 'Error de conexión puntual',
            status: 'failed',
            error: 'Fallo temporal de conexión'
          };
        }

        // Render card
        const cardEl = document.createElement('div');
        if (itemResult.action === 'marked_spam') {
          spamCount++;
          cardEl.className = 'autopilot-live-card spam';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">🚫</span>
                <strong>@${this.escapeHtml(itemResult.author)}</strong>
                <span class="autopilot-variant-tag spam">SPAM / ENLACE</span>
              </div>
              <span class="autopilot-status-spam">⚠️ Por Revisar</span>
            </div>
            <div class="autopilot-live-reply-quote spam">
              ${this.escapeHtml(itemResult.reason || 'Comentario sospechoso marcado para revisión.')}
            </div>
          `;
        } else if (itemResult.action === 'ignored_sticker') {
          ignoredCount++;
          cardEl.className = 'autopilot-live-card sticker';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">🎨</span>
                <strong>@${this.escapeHtml(itemResult.author)}</strong>
                <span class="autopilot-variant-tag sticker">STICKER / EMOJIS</span>
              </div>
              <span class="autopilot-status-ignored">Omitido</span>
            </div>
            <div class="autopilot-live-reply-quote sticker">
              ${this.escapeHtml(itemResult.reason || 'Solo emojis. Omitido para no saturar al seguidor.')}
            </div>
          `;
        } else if (itemResult.action === 'failed' || itemResult.is_posted === 0) {
          failedCount++;
          cardEl.className = 'autopilot-live-card failed';
          cardEl.style.borderLeft = '3px solid #ef4444';
          const isExp = itemResult.is_token_expired ? '⚠️ Token de Meta Expirado' : '⚠️ Falló Meta';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">⚠️</span>
                <strong>@${this.escapeHtml(itemResult.author)}</strong>
                <span class="autopilot-variant-tag" style="background: rgba(239, 68, 68, 0.2); color: #f87171;">${isExp}</span>
              </div>
              <span class="autopilot-status-failed" style="color: #f87171; font-weight: 700; font-size: 0.78rem;">Fallo en Meta</span>
            </div>
            <div class="autopilot-live-reply-quote" style="border-left-color: #ef4444;">
              "${this.escapeHtml(itemResult.reply || '')}"
            </div>
            <div style="font-size: 0.74rem; color: #fca5a5; margin-top: 4px;">ℹ️ ${this.escapeHtml(itemResult.error || 'Token de Meta expirado. Renueva en Configuración.')}</div>
          `;
        } else {
          repliedCount++;
          cardEl.className = 'autopilot-live-card replied';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">🏛️</span>
                <strong>@${this.escapeHtml(itemResult.author)}</strong>
                <span class="autopilot-variant-tag">${this.escapeHtml(itemResult.variant || 'engagement')}</span>
              </div>
              <span class="autopilot-status-success">✅ Publicada</span>
            </div>
            <div class="autopilot-live-reply-quote">
              "${this.escapeHtml(itemResult.reply || '')}"
            </div>
          `;
        }

        if (streamList) streamList.prepend(cardEl);

        const donePercent = Math.round(((i + 1) / queue.length) * 100);
        if (progressBar) progressBar.style.width = `${donePercent}%`;
        if (progressPercent) progressPercent.textContent = `${donePercent}%`;

        // 3. Humanized anti-bot cadence delay before next comment
        if (i < queue.length - 1 && !this.autopilotPaused) {
          const delayMode = document.getElementById('autopilot-delay-select')?.value || 'natural';
          let delayMs = 5000;
          if (delayMode === 'fast') {
            delayMs = Math.floor(Math.random() * 1000) + 2000; // 2s - 3s
          } else if (delayMode === 'safe') {
            delayMs = Math.floor(Math.random() * 6000) + 8000; // 8s - 14s
          } else {
            delayMs = Math.floor(Math.random() * 3500) + 4000; // 4s - 7.5s (natural)
          }

          const startDelay = Date.now();
          while ((Date.now() - startDelay) < delayMs && !this.autopilotPaused) {
            const leftSecs = Math.max(1, Math.ceil((delayMs - (Date.now() - startDelay)) / 1000));
            if (progressStatus) {
              progressStatus.textContent = `☕ Cadencia humana anti-bot: esperando ${leftSecs}s antes del siguiente comentario...`;
            }
            await new Promise(r => setTimeout(r, 400));
          }
        }
      }

      const finishMsg = this.autopilotPaused 
        ? `Piloto en pausa. (${repliedCount} publicados, ${failedCount} fallos, ${spamCount} spam)` 
        : `¡Proceso completado! (${repliedCount} publicados con éxito en Meta, ${failedCount} fallos, ${spamCount} spam)`;
      
      if (progressStatus) progressStatus.textContent = finishMsg;
      App.showToast(finishMsg, repliedCount > 0 ? 'success' : 'info');
      await App.loadComments();
      this.updateAutopilotPendingBadge();

    } catch (err) {
      console.error(err);
      App.showToast('Error de ejecución en el piloto automático.', 'error');
      if (progressStatus) progressStatus.textContent = '❌ Error de ejecución';
    } finally {
      if (btn) btn.disabled = false;
      if (btnText) btnText.textContent = 'Ejecutar Auto-Responder Ahora';
      if (btnPause) btnPause.style.display = 'none';
      this.autopilotPaused = false;
    }
  },

  // Trigger Autopilot run (global)
  async runAutopilotBatch() {
    this.switchModalTab('autopilot');
    App.openModal('modal-assistant-replies');
    return this.startLiveAutopilot();
  },

  // Save a chosen variant or the customized textarea text as permanent Gold Example to train Hermes
  async saveAsGoldExample(variantType = null, isModal = false) {
    const comment = isModal ? this.modalActiveComment : this.activeComment;
    if (!comment) {
      App.showToast('No hay un comentario activo seleccionado.', 'error');
      return;
    }

    const modalTextarea = isModal ? document.getElementById('modal-reply-text-input') : document.getElementById('reply-text-input');
    const textareaVal = (modalTextarea ? modalTextarea.value : '').trim();

    const replies = isModal ? this.modalActiveReplies : this.activeReplies;
    const activeVar = variantType || (isModal ? this.modalSelectedVariant : this.selectedVariant) || 'engagement';
    let replyText = '';
    let originalSuggestion = '';
    let wasEdited = false;

    if (replies && replies[activeVar]) {
      originalSuggestion = (replies[activeVar] || '').trim();
    }

    if (variantType && replies && replies[variantType]) {
      // If the user modified the textarea after selecting this variant, prioritize the user's customized words!
      if (textareaVal.length > 0 && textareaVal !== originalSuggestion) {
        replyText = textareaVal;
        wasEdited = true;
      } else {
        replyText = originalSuggestion;
      }
    } else if (textareaVal.length > 0) {
      replyText = textareaVal;
      wasEdited = !!(originalSuggestion && originalSuggestion !== textareaVal);
    } else if (originalSuggestion) {
      replyText = originalSuggestion;
    } else if (replies && (replies.engagement || replies.conversion || replies.support)) {
      replyText = (replies[activeVar] || replies.engagement || replies.conversion || replies.support || '').trim();
    }

    if (!replyText) {
      App.showToast('Escribe o selecciona una respuesta antes de guardar como Ejemplo de Oro.', 'error');
      return;
    }

    const commentText = (comment.comment_text || '').trim();
    const commentId = parseInt(comment.id, 10);
    const brandVoiceId = parseInt(comment.brand_voice_id || comment.effective_brand_voice_id || 1, 10);

    App.showToast('⭐ Guardando como Ejemplo de Oro para Hermes...', 'info');

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'save_gold_example',
          comment_id: commentId,
          comment_text: commentText,
          reply_text: replyText,
          original_suggestion: originalSuggestion,
          was_edited: wasEdited,
          brand_voice_id: brandVoiceId
        })
      });
      const res = await response.json();
      if (res.success) {
        App.showToast(res.message || '⭐ ¡Ejemplo de Oro guardado con éxito! Hermes lo usará como estándar.', 'success');
      } else {
        App.showToast(res.error || 'No se pudo guardar el Ejemplo de Oro.', 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error de conexión al guardar Ejemplo de Oro.', 'error');
    }
  },

  escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  },

  escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '&quot;').replace(/\n/g, ' ');
  }
};
