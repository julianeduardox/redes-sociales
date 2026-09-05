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

  // Render the 3 variant cards in side copilot
  renderSuggestionCards(replies) {
    const suggestionsContainer = document.getElementById('suggestions-container');
    if (!suggestionsContainer) return;

    suggestionsContainer.innerHTML = `
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
        </div>
      </div>

      <!-- Conversion & Sales CTA Card -->
      <div class="suggestion-card" id="card-variant-conversion" onclick="AgentController.selectVariant('conversion')">
        <div class="suggestion-header">
          <span class="suggestion-tag conversion">🎯 Opción 2: Conversión & Venta</span>
          <span style="font-size: 0.72rem; color: var(--text-dim);">Enfoque Comercial & CTA</span>
        </div>
        <p class="suggestion-text" id="text-variant-conversion">${this.escapeHtml(replies.conversion || '')}</p>
        <div class="suggestion-actions">
          <button type="button" class="btn-suggestion-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.conversion)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(16,185,129,0.2); color: #6ee7b7;" onclick="event.stopPropagation(); AgentController.selectVariant('conversion')">
            ✏️ Usar
          </button>
        </div>
      </div>

      <!-- Authority & Support Solution Card -->
      <div class="suggestion-card" id="card-variant-support" onclick="AgentController.selectVariant('support')">
        <div class="suggestion-header">
          <span class="suggestion-tag support">💡 Opción 3: Autoridad & Solución</span>
          <span style="font-size: 0.72rem; color: var(--text-dim);">Resolución & Soporte</span>
        </div>
        <p class="suggestion-text" id="text-variant-support">${this.escapeHtml(replies.support || '')}</p>
        <div class="suggestion-actions">
          <button type="button" class="btn-suggestion-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.support)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-suggestion-action" style="background: rgba(168,85,247,0.2); color: #d8b4fe;" onclick="event.stopPropagation(); AgentController.selectVariant('support')">
            ✏️ Usar
          </button>
        </div>
      </div>

      ${replies.engagement_tips ? `
        <div class="suggestion-tip">
          💡 <strong>Estrategia Comercial:</strong> ${this.escapeHtml(replies.engagement_tips)}
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
        App.showToast('No se pudo copiar automáticamente.', 'error');
      });
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

    const btn = document.getElementById('btn-send-action');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span>Publicando...</span>`;
    }

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'reply',
          comment_id: parseInt(this.activeComment.id, 10),
          reply_text: replyText,
          variant_type: this.selectedVariant,
          tone_used: document.getElementById('select-tone')?.value || 'stoic_mentor'
        })
      });

      const res = await response.json();

      if (res.success) {
        App.showToast('¡Respuesta publicada y conexión comunitaria registrada! 🏛️✨', 'success');
        await App.loadComments();
        const updated = App.commentsList.find(c => c.id === this.activeComment.id);
        if (updated) {
          App.selectComment(updated);
        }
      } else {
        App.showToast(`Error: ${res.error || 'No se pudo enviar la respuesta.'}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al enviar la respuesta.', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<span>Publicar Respuesta</span> 🏛️`;
      }
    }
  },

  // =========================================================================
  // POPUP MODAL: Asistente de Respuestas & Conexión
  // =========================================================================

  // Open the dedicated Assistant Popup Modal
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
    this.populateModalCommentsDropdown(targetComment.id);
    this.updateModalFollowerContext(targetComment);

    // Sync tone from settings or active selection
    const modalToneSelect = document.getElementById('modal-select-tone');
    if (modalToneSelect) {
      const currentTone = document.getElementById('select-tone')?.value || 'stoic_mentor';
      modalToneSelect.value = currentTone;
    }

    // Switch to manual view by default
    this.switchModalTab('manual');

    // Open the modal
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
    if (score) score.textContent = `⭐ Score ${parseInt(comment.highlight_score, 10) || 50}/100`;
    
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
  },

  // On comment selection changed in modal dropdown
  onModalCommentChange(commentId) {
    if (!commentId) return;
    const comment = App.commentsList.find(c => c.id == commentId);
    if (comment) {
      this.modalActiveComment = comment;
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

    container.innerHTML = `
      <div style="grid-column: 1 / -1; padding: 28px; text-align: center; color: var(--text-muted);">
        <div style="display: inline-block; width: 28px; height: 28px; border: 3px solid rgba(99,102,241,0.3); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin-bottom: 10px;"></div>
        <p style="font-size: 0.85rem; font-weight: 700; color: #fff;">Forjando 3 sugerencias (Reflexiva, Motivacional y Comunitaria)...</p>
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
        container.innerHTML = `
          <div style="grid-column: 1 / -1; padding: 16px; color: var(--accent-rose); font-size: 0.84rem; background: rgba(244,63,94,0.1); border-radius: 8px;">
            ⚠️ No se pudieron generar sugerencias: ${this.escapeHtml(res.error || 'Error desconocido')}
          </div>
        `;
      }
    } catch (err) {
      console.error(err);
      container.innerHTML = `
        <div style="grid-column: 1 / -1; padding: 16px; color: var(--accent-rose); font-size: 0.84rem; background: rgba(244,63,94,0.1); border-radius: 8px;">
          ⚠️ Error de conexión con el motor de IA.
        </div>
      `;
    }
  },

  // Render the 3 variant cards inside the popup modal
  renderModalSuggestionCards(replies) {
    const container = document.getElementById('modal-suggestions-container');
    if (!container) return;

    container.innerHTML = `
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
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(99,102,241,0.25); color: #a5b4fc;" onclick="event.stopPropagation(); AgentController.quickPostModalVariant('engagement')" title="Publicar directamente esta opción">
            ⚡ Publicar
          </button>
        </div>
      </div>

      <!-- Variant 2: Conversion & Sales CTA -->
      <div class="modal-suggestion-card card-motivation" id="modal-card-conversion" onclick="AgentController.selectModalVariant('conversion')">
        <div class="modal-suggestion-header">
          <span class="modal-suggestion-tag motivation">🎯 Opción 2: Conversión & Venta</span>
          <span class="modal-suggestion-sub">Enfoque Comercial, Producto & DM</span>
        </div>
        <div class="modal-suggestion-text" id="modal-text-conversion">${this.escapeHtml(replies.conversion || '')}</div>
        <div class="modal-suggestion-actions">
          <button type="button" class="btn-modal-sugg-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.conversion)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(16,185,129,0.18); color: #6ee7b7;" onclick="event.stopPropagation(); AgentController.selectModalVariant('conversion')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(16,185,129,0.25); color: #34d399;" onclick="event.stopPropagation(); AgentController.quickPostModalVariant('conversion')" title="Publicar directamente esta opción">
            ⚡ Publicar
          </button>
        </div>
      </div>

      <!-- Variant 3: Authority & Support Solution -->
      <div class="modal-suggestion-card card-community" id="modal-card-support" onclick="AgentController.selectModalVariant('support')">
        <div class="modal-suggestion-header">
          <span class="modal-suggestion-tag community">💡 Opción 3: Autoridad & Solución</span>
          <span class="modal-suggestion-sub">Resolución Directa, Confianza & Soporte</span>
        </div>
        <div class="modal-suggestion-text" id="modal-text-support">${this.escapeHtml(replies.support || '')}</div>
        <div class="modal-suggestion-actions">
          <button type="button" class="btn-modal-sugg-action" onclick="event.stopPropagation(); AgentController.copySuggestion('${this.escapeJs(replies.support)}')">
            📋 Copiar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(168,85,247,0.18); color: #d8b4fe;" onclick="event.stopPropagation(); AgentController.selectModalVariant('support')">
            ✏️ Usar
          </button>
          <button type="button" class="btn-modal-sugg-action" style="background: rgba(168,85,247,0.25); color: #c084fc;" onclick="event.stopPropagation(); AgentController.quickPostModalVariant('support')" title="Publicar directamente esta opción">
            ⚡ Publicar
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
    if (!this.modalActiveComment) {
      App.showToast('No hay un comentario seleccionado en el asistente.', 'error');
      return;
    }

    const textarea = document.getElementById('modal-reply-text-input');
    const replyText = textarea?.value?.trim() || '';

    if (!replyText) {
      App.showToast('El texto de la respuesta no puede estar vacío.', 'error');
      return;
    }

    const btn = document.getElementById('btn-modal-submit-reply');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span>Publicando...</span>`;
    }

    try {
      const response = await App.fetchWithCsrf('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'reply',
          comment_id: parseInt(this.modalActiveComment.id, 10),
          reply_text: replyText,
          variant_type: this.modalSelectedVariant,
          tone_used: document.getElementById('modal-select-tone')?.value || 'stoic_mentor'
        })
      });

      const res = await response.json();

      if (res.success) {
        App.showToast('¡Respuesta publicada y conexión comunitaria registrada! 🏛️✨', 'success');
        App.closeModal('modal-assistant-replies');
        await App.loadComments();
        
        // Also update selection in main copilot if same comment
        const updated = App.commentsList.find(c => c.id === this.modalActiveComment.id);
        if (updated) {
          App.selectComment(updated);
        }
      } else {
        App.showToast(`Error: ${res.error || 'No se pudo enviar la respuesta.'}`, 'error');
      }
    } catch (err) {
      console.error(err);
      App.showToast('Error al enviar la respuesta.', 'error');
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `<span>Publicar Respuesta</span> 🏛️`;
      }
    }
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
    const pending = App.commentsList ? App.commentsList.filter(c => c.status === 'pending') : [];
    const highPending = pending.filter(c => (c.is_highlighted == 1 || c.highlight_score >= 80));
    badge.textContent = `${highPending.length} de alto impacto listos (${pending.length} pendientes en total)`;
  },

  // Live Autopilot Execution with Real-Time Step-by-Step UI
  async startLiveAutopilot() {
    const btn = document.getElementById('btn-run-autopilot-live');
    const btnText = document.getElementById('btn-run-autopilot-live-text');
    const progressContainer = document.getElementById('autopilot-progress-container');
    const progressBar = document.getElementById('autopilot-progress-bar');
    const progressStatus = document.getElementById('autopilot-progress-status');
    const progressPercent = document.getElementById('autopilot-progress-percent');
    const streamList = document.getElementById('autopilot-stream-list');

    if (btn) btn.disabled = true;
    if (btnText) btnText.textContent = 'Procesando con IA...';
    if (progressContainer) progressContainer.style.display = 'block';
    if (progressBar) progressBar.style.width = '20%';
    if (progressPercent) progressPercent.textContent = '20%';
    if (progressStatus) progressStatus.textContent = '⚡ Analizando intención, score y generando respuestas estoicas...';

    try {
      const response = await App.fetchWithCsrf('api/agent.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'batch_autopilot' })
      });
      const res = await response.json();

      if (!res.success) {
        App.showToast(`Error: ${res.error || 'No se pudo procesar el auto-responder'}`, 'error');
        if (progressStatus) progressStatus.textContent = '❌ Error durante la ejecución';
        return;
      }

      const items = res.items || [];
      if (items.length === 0) {
        if (progressBar) progressBar.style.width = '100%';
        if (progressPercent) progressPercent.textContent = '100%';
        if (progressStatus) progressStatus.textContent = '✅ Todos los comentarios con score alto ya están respondidos';
        if (streamList) {
          streamList.innerHTML = `
            <div class="autopilot-empty-state">
              <span style="font-size: 2rem;">✨</span>
              <p style="font-weight: 700; color: #fff; margin-top: 6px;">Todo al día</p>
              <p style="font-size: 0.78rem; color: var(--text-muted);">No hay comentarios pendientes con Score alto por responder.</p>
            </div>
          `;
        }
        App.showToast('No hay comentarios pendientes con Score alto para responder automáticamente.', 'success');
        return;
      }

      // Animate live stream item by item
      if (streamList) streamList.innerHTML = '';

      for (let i = 0; i < items.length; i++) {
        const item = items[i];
        const percent = Math.round(((i + 1) / items.length) * 100);
        if (progressBar) progressBar.style.width = `${percent}%`;
        if (progressPercent) progressPercent.textContent = `${percent}%`;
        
        const cardEl = document.createElement('div');

        if (item.action === 'marked_spam') {
          if (progressStatus) progressStatus.textContent = `🚫 Detectado Spam/Inglés en @${item.author} (${i + 1}/${items.length})...`;
          cardEl.className = 'autopilot-live-card spam';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">🚫</span>
                <strong>@${this.escapeHtml(item.author)}</strong>
                <span class="autopilot-variant-tag spam">SPAM / INGLÉS</span>
              </div>
              <span class="autopilot-status-spam">⚠️ Por Revisar</span>
            </div>
            <div class="autopilot-live-reply-quote spam">
              ${this.escapeHtml(item.reason || 'Comentario en idioma extranjero o enlace detectado. Guardado para revisión.')}
            </div>
          `;
        } else if (item.action === 'ignored_sticker') {
          if (progressStatus) progressStatus.textContent = `🎨 Evaluando @${item.author} (${i + 1}/${items.length})...`;
          cardEl.className = 'autopilot-live-card sticker';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">🎨</span>
                <strong>@${this.escapeHtml(item.author)}</strong>
                <span class="autopilot-variant-tag sticker">STICKER / EMOJIS</span>
              </div>
              <span class="autopilot-status-ignored">Omitido</span>
            </div>
            <div class="autopilot-live-reply-quote sticker">
              ${this.escapeHtml(item.reason || 'Solo emojis/stickers. Omitido para no saturar al seguidor.')}
            </div>
          `;
        } else {
          if (progressStatus) progressStatus.textContent = `⚡ Publicando respuesta para @${item.author} (${i + 1}/${items.length})...`;
          cardEl.className = 'autopilot-live-card replied';
          cardEl.innerHTML = `
            <div class="autopilot-live-card-header">
              <div class="autopilot-live-author">
                <span class="autopilot-live-avatar">🏛️</span>
                <strong>@${this.escapeHtml(item.author)}</strong>
                <span class="autopilot-variant-tag">${this.escapeHtml(item.variant || 'engagement')}</span>
              </div>
              <span class="autopilot-status-success">✅ Publicada</span>
            </div>
            <div class="autopilot-live-reply-quote">
              "${this.escapeHtml(item.reply)}"
            </div>
          `;
        }

        if (streamList) streamList.prepend(cardEl);

        // Visual delay so the user watches the AI replying in real time
        await new Promise(r => setTimeout(r, 380));
      }

      if (progressStatus) progressStatus.textContent = `✨ ¡Proceso Completado! ${res.message}`;
      App.showToast(`✨ ${res.message}`, 'success');
      await App.loadComments();
      this.updateAutopilotPendingBadge();

    } catch (err) {
      console.error(err);
      App.showToast('Error de conexión al procesar el piloto automático.', 'error');
      if (progressStatus) progressStatus.textContent = '❌ Error de red';
    } finally {
      if (btn) btn.disabled = false;
      if (btnText) btnText.textContent = 'Ejecutar Auto-Responder Ahora';
    }
  },

  // Trigger Autopilot run (global)
  async runAutopilotBatch() {
    return this.startLiveAutopilot();
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
