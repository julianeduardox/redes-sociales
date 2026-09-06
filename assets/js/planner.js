/**
 * Content Planner & Auto-Scheduler Controller
 * Hardened with XSS Prevention & Live Golden Window Matrix
 */

const PlannerController = {
  currentDate: new Date(),
  platform: 'all',
  cachedCalendarData: null,
  cachedGoldenSlots: [],
  generatedDrafts: [],
  selectedDraftIndex: -1,
  editingPostId: null,

  monthNames: [
    'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
    'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
  ],

  async loadPlanner() {
    await this.loadGoldenSlots();
    await this.loadCalendar();
  },

  filterPlatform(plat) {
    this.platform = plat;
    document.querySelectorAll('[data-planner-platform]').forEach(btn => {
      btn.classList.toggle('active', btn.dataset.plannerPlatform === plat);
    });
    this.loadCalendar();
    this.loadGoldenSlots();
  },

  prevMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
    this.loadCalendar();
  },

  nextMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
    this.loadCalendar();
  },

  goToCurrentMonth() {
    this.currentDate = new Date();
    this.loadCalendar();
  },

  async loadGoldenSlots() {
    try {
      const res = await App.fetchWithCsrf(`api/planner.php?action=golden_slots&platform=${encodeURIComponent(this.platform)}&days=14`);
      const data = await res.json();
      if (data.success) {
        this.cachedGoldenSlots = data.golden_slots || [];
        this.renderGoldenSlotsBanner();
      }
    } catch (e) {
      console.error("Error loading golden slots:", e);
    }
  },

  renderGoldenSlotsBanner() {
    const container = document.getElementById('planner-golden-slots-list');
    if (!container) return;

    if (this.cachedGoldenSlots.length === 0) {
      container.innerHTML = `
        <div style="color: var(--text-muted); font-size: 0.8rem; padding: 10px;">
          No hay horarios proyectados para esta plataforma.
        </div>
      `;
      return;
    }

    container.innerHTML = this.cachedGoldenSlots.slice(0, 5).map(s => {
      const isOcc = s.is_occupied;
      const safeDt = App.escapeHtml(s.full_datetime || '');
      const safeLbl = App.escapeHtml(s.golden_label || 'Pico Dorado');

      return `
        <div class="golden-slot-pill-card ${isOcc ? 'occupied' : ''}">
          <div class="slot-pill-header">
            <span class="slot-pill-rank">${safeLbl}</span>
            <span class="slot-pill-eng">${parseFloat(s.avg_engagement_rate || 0).toFixed(1)}% Eng.</span>
          </div>
          <div class="slot-pill-datetime">
            <span class="day">${App.escapeHtml(s.day_name)} ${App.escapeHtml(s.date.substring(5))}</span>
            <span class="time">⏰ ${App.escapeHtml(s.time_label)}</span>
          </div>
          ${!isOcc ? `
            <button type="button" class="btn-slot-use" onclick="PlannerController.openCreatorModal('${safeDt}', '${safeLbl}')">
              <span>+ Programar</span>
            </button>
          ` : `
            <span class="slot-occupied-tag">✓ Ocupado</span>
          `}
        </div>
      `;
    }).join('');
  },

  async loadCalendar() {
    const year = this.currentDate.getFullYear();
    const month = this.currentDate.getMonth(); // 0-indexed

    const firstDayDate = new Date(year, month, 1);
    const lastDayDate = new Date(year, month + 1, 0);

    const startDateStr = `${year}-${String(month + 1).padStart(2, '0')}-01`;
    const endDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(lastDayDate.getDate()).padStart(2, '0')}`;

    const titleEl = document.getElementById('calendar-month-title');
    if (titleEl) {
      titleEl.textContent = `${this.monthNames[month]} ${year}`;
    }

    try {
      const url = `api/planner.php?action=calendar&start_date=${startDateStr}&end_date=${endDateStr}&platform=${encodeURIComponent(this.platform)}`;
      const res = await App.fetchWithCsrf(url);
      const data = await res.json();

      if (data.success) {
        this.cachedCalendarData = data;
        this.updateKpiCounters(data.counts || {});
        this.renderCalendar(year, month, data.posts || []);
      }
    } catch (e) {
      console.error("Error loading calendar posts:", e);
    }
  },

  updateKpiCounters(counts) {
    const elSched = document.getElementById('kpi-count-scheduled');
    const elPub = document.getElementById('kpi-count-published');
    const elDraft = document.getElementById('kpi-count-drafts');

    if (elSched) elSched.textContent = `📌 ${counts.scheduled || 0} Programadas`;
    if (elPub) elPub.textContent = `✅ ${counts.published || 0} Publicadas`;
    if (elDraft) elDraft.textContent = `📝 ${counts.draft || 0} Borradores`;
  },

  renderCalendar(year, month, posts) {
    const container = document.getElementById('calendar-grid-cells');
    if (!container) return;

    // Group posts by YYYY-MM-DD
    const postsByDate = {};
    posts.forEach(p => {
      const datePart = (p.scheduled_for || '').substring(0, 10);
      if (!postsByDate[datePart]) postsByDate[datePart] = [];
      postsByDate[datePart].push(p);
    });

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const totalDays = lastDay.getDate();

    // In JS, getDay() returns 0 for Sunday, 1 for Monday.
    // We want Monday = 0 ... Sunday = 6
    let startingDayOfWeek = firstDay.getDay() - 1;
    if (startingDayOfWeek === -1) startingDayOfWeek = 6;

    const todayStr = new Date().toISOString().substring(0, 10);

    let html = '';

    // Empty lead cells from previous month
    for (let i = 0; i < startingDayOfWeek; i++) {
      html += `<div class="cal-day-cell cal-day-empty"></div>`;
    }

    // Days of current month
    for (let day = 1; day <= totalDays; day++) {
      const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      const isToday = (dateStr === todayStr);
      const dayPosts = postsByDate[dateStr] || [];

      // Check if this date has a golden slot
      const goldenMatch = this.cachedGoldenSlots.find(s => s.date === dateStr);

      html += `
        <div class="cal-day-cell ${isToday ? 'today' : ''}">
          <div class="cal-day-header">
            <span class="cal-day-num">${day}</span>
            <div style="display: flex; align-items: center; gap: 4px;">
              ${goldenMatch ? `
                <span class="cal-golden-badge" style="cursor: pointer;" onclick="PlannerController.openCreatorModal('${App.escapeHtml(goldenMatch.full_datetime)}', '${App.escapeHtml(goldenMatch.golden_label)}')" title="${App.escapeHtml(goldenMatch.golden_label)} (${goldenMatch.time_label})">
                  🔥 ${goldenMatch.time_label}
                </span>
              ` : ''}
              <button type="button" class="cal-btn-add" onclick="PlannerController.openCreatorModal('${dateStr} 19:30:00')" title="Programar en este día">+</button>
            </div>
          </div>

          <div class="cal-day-posts-list">
            ${dayPosts.map(p => {
              const isIG = p.platform === 'instagram';
              const isPublished = p.status === 'published';
              const isDraft = p.status === 'draft';
              const statusClass = isPublished ? 'published' : (isDraft ? 'draft' : 'scheduled');
              const timeFormatted = p.scheduled_for ? p.scheduled_for.substring(11, 16) : '19:30';
              const safeHook = App.escapeHtml(p.hook_title || p.caption.substring(0, 30));

              return `
                <div class="cal-post-chip ${statusClass}" onclick="PlannerController.openPostDetail(${parseInt(p.id, 10)})" title="${safeHook}">
                  <span class="cal-chip-plat ${isIG ? 'ig' : 'fb'}">${isIG ? '📸' : '📘'}</span>
                  <span class="cal-chip-time">${timeFormatted}</span>
                  <span class="cal-chip-hook">${safeHook}</span>
                </div>
              `;
            }).join('')}
          </div>
        </div>
      `;
    }

    container.innerHTML = html;
  },

  openCreatorModal(targetDatetime = '', goldenLabel = '') {
    this.generatedDrafts = [];
    this.selectedDraftIndex = -1;
    this.editingPostId = null;

    const topicInput = document.getElementById('creator-topic-input');
    if (topicInput) topicInput.value = '';

    const proposalsWrapper = document.getElementById('creator-proposals-wrapper');
    if (proposalsWrapper) proposalsWrapper.style.display = 'none';

    const editorBox = document.getElementById('creator-editor-box');
    if (editorBox) editorBox.style.display = 'none';

    // Populate golden slot selector
    const slotSelect = document.getElementById('editor-golden-slot-select');
    if (slotSelect) {
      slotSelect.innerHTML = `
        <option value="">-- Seleccionar Franja Dorada --</option>
        ${this.cachedGoldenSlots.map(s => {
          const isSelected = targetDatetime && s.full_datetime.startsWith(targetDatetime.substring(0, 13));
          return `
            <option value="${App.escapeHtml(s.full_datetime)}" data-label="${App.escapeHtml(s.golden_label)}" ${isSelected ? 'selected' : ''}>
              ${App.escapeHtml(s.day_name)} ${App.escapeHtml(s.date)} a las ${App.escapeHtml(s.time_label)} (${App.escapeHtml(s.golden_label)})
            </option>
          `;
        }).join('')}
      `;
    }

    const dtInput = document.getElementById('editor-datetime-input');
    if (dtInput) {
      if (targetDatetime) {
        dtInput.value = targetDatetime.substring(0, 16);
      } else if (this.cachedGoldenSlots.length > 0) {
        dtInput.value = this.cachedGoldenSlots[0].full_datetime.substring(0, 16);
      } else {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(19, 30, 0, 0);
        dtInput.value = tomorrow.toISOString().substring(0, 16);
      }
    }

    App.openModal('modal-content-creator');
  },

  closeCreatorModal() {
    App.closeModal('modal-content-creator');
  },

  async triggerGenerateDrafts() {
    const topic = (document.getElementById('creator-topic-input')?.value || '').trim();
    if (!topic) {
      App.showToast("Introduce una idea o tema para el post.", "warning");
      return;
    }

    const format = document.getElementById('creator-format-select')?.value || 'reel';
    const goal = document.getElementById('creator-goal-select')?.value || 'connection';
    const platform = document.querySelector('[data-planner-platform].active')?.dataset.plannerPlatform || 'instagram';

    const btn = document.getElementById('btn-generate-drafts');
    const oldHtml = btn ? btn.innerHTML : '';
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span>⏳ Generando copys...</span>`;
    }

    try {
      const res = await App.fetchWithCsrf('api/planner.php', {
        method: 'POST',
        body: JSON.stringify({
          action: 'generate',
          topic,
          platform: (platform === 'all' ? 'instagram' : platform),
          format,
          goal
        })
      });

      const data = await res.json();

      if (data.success && data.drafts && data.drafts.length > 0) {
        this.generatedDrafts = data.drafts;
        this.renderDraftProposals(data.drafts);
        App.showToast("¡3 propuestas generadas con éxito!", "success");
      } else {
        App.showToast(data.error || "No se pudieron generar las propuestas.", "error");
      }
    } catch (e) {
      console.error("Error generating drafts:", e);
      App.showToast("Error de conexión al generar copys.", "error");
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
      }
    }
  },

  renderDraftProposals(drafts) {
    const wrapper = document.getElementById('creator-proposals-wrapper');
    const grid = document.getElementById('creator-proposals-grid');
    if (!wrapper || !grid) return;

    wrapper.style.display = 'block';

    grid.innerHTML = drafts.map((d, idx) => {
      const safeTitle = App.escapeHtml(d.variant_title || `Variante #${idx + 1}`);
      const safeHook = App.escapeHtml(d.hook_title || '');
      const safeCopy = App.escapeHtml(d.caption || '');
      const safeVisual = App.escapeHtml(d.visual_concept || '');

      return `
        <div class="proposal-card ${this.selectedDraftIndex === idx ? 'selected' : ''}" onclick="PlannerController.selectProposal(${idx})">
          <div class="proposal-card-header">
            <span class="proposal-type-badge">${safeTitle}</span>
            <span class="proposal-select-indicator">${this.selectedDraftIndex === idx ? '✓ Seleccionado' : 'Elegir'}</span>
          </div>

          <div class="proposal-hook">
            <strong>Hook:</strong> "${safeHook}"
          </div>

          <div class="proposal-caption-preview">
            ${safeCopy}
          </div>

          ${safeVisual ? `
            <div class="proposal-visual-hint">
              🎬 <em>${safeVisual}</em>
            </div>
          ` : ''}
        </div>
      `;
    }).join('');

    // Automatically select the first proposal
    if (this.selectedDraftIndex === -1 && drafts.length > 0) {
      this.selectProposal(0);
    }
  },

  selectProposal(idx) {
    this.selectedDraftIndex = idx;
    const draft = this.generatedDrafts[idx];
    if (!draft) return;

    // Update selected styles
    document.querySelectorAll('.proposal-card').forEach((el, i) => {
      el.classList.toggle('selected', i === idx);
      const ind = el.querySelector('.proposal-select-indicator');
      if (ind) ind.textContent = (i === idx) ? '✓ Seleccionado' : 'Elegir';
    });

    const editorBox = document.getElementById('creator-editor-box');
    if (editorBox) editorBox.style.display = 'block';

    const hookInput = document.getElementById('editor-hook-input');
    const captionInput = document.getElementById('editor-caption-input');
    const platSelect = document.getElementById('editor-platform-select');

    if (hookInput) hookInput.value = draft.hook_title || '';
    if (captionInput) captionInput.value = draft.caption || '';
    if (platSelect) platSelect.value = (this.platform === 'facebook' ? 'facebook' : 'instagram');
  },

  onGoldenSlotSelectChanged(slotDatetime) {
    if (!slotDatetime) return;
    const dtInput = document.getElementById('editor-datetime-input');
    if (dtInput) {
      dtInput.value = slotDatetime.substring(0, 16);
    }
  },

  async savePostFromEditor(status = 'scheduled') {
    const hook = (document.getElementById('editor-hook-input')?.value || '').trim();
    const caption = (document.getElementById('editor-caption-input')?.value || '').trim();
    const mediaUrl = (document.getElementById('editor-media-input')?.value || '').trim();
    const scheduledFor = (document.getElementById('editor-datetime-input')?.value || '').trim();
    const platform = document.getElementById('editor-platform-select')?.value || 'instagram';
    const topic = (document.getElementById('creator-topic-input')?.value || '').trim();
    const format = document.getElementById('creator-format-select')?.value || 'reel';

    if (!caption) {
      App.showToast("El texto de la publicación no puede estar vacío.", "warning");
      return;
    }

    if (!scheduledFor) {
      App.showToast("Selecciona una fecha y hora para programar.", "warning");
      return;
    }

    // Match if chosen slot matches a golden window
    const goldenMatch = this.cachedGoldenSlots.find(s => s.full_datetime.startsWith(scheduledFor.substring(0, 13)));

    const payload = {
      action: 'save',
      id: this.editingPostId,
      platform,
      content_format: format,
      topic,
      hook_title: hook,
      caption,
      media_url: mediaUrl,
      scheduled_for: scheduledFor,
      is_golden_slot: goldenMatch ? 1 : 0,
      golden_window_label: goldenMatch ? goldenMatch.golden_label : '',
      status
    };

    try {
      const res = await App.fetchWithCsrf('api/planner.php', {
        method: 'POST',
        body: JSON.stringify(payload)
      });

      const data = await res.json();

      if (data.success) {
        App.showToast(data.message || "Publicación guardada en el calendario.", "success");
        this.closeCreatorModal();
        await this.loadGoldenSlots();
        await this.loadCalendar();
      } else {
        App.showToast(data.error || "Error al guardar publicación.", "error");
      }
    } catch (e) {
      console.error("Error saving post:", e);
      App.showToast("Error de conexión al guardar.", "error");
    }
  },

  openPostDetail(postId) {
    if (!this.cachedCalendarData || !this.cachedCalendarData.posts) return;
    const post = this.cachedCalendarData.posts.find(p => parseInt(p.id, 10) === parseInt(postId, 10));
    if (!post) return;

    const content = document.getElementById('preview-modal-content');
    if (!content) return;

    const isIG = post.platform === 'instagram';
    const safeCaption = App.escapeHtml(post.caption || '');
    const safeHook = App.escapeHtml(post.hook_title || 'Publicación Programada');
    const safeImg = App.sanitizeUrl(post.media_url, 'https://images.unsplash.com/photo-1544717305-2782549b5136?w=640&fit=crop&auto=format&q=75');
    const isGolden = parseInt(post.is_golden_slot, 10) === 1;
    const isPublished = post.status === 'published';

    content.innerHTML = `
      <div style="display: flex; flex-direction: column; gap: 16px;">
        <!-- Status & Platform Header -->
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span class="post-platform-badge ${isIG ? 'instagram' : 'facebook'}">
              ${isIG ? '📸 Instagram' : '📘 Facebook'}
            </span>
            ${isGolden ? `
              <span class="golden-tag-badge">🔥 ${App.escapeHtml(post.golden_window_label || 'Horario Dorado')}</span>
            ` : ''}
          </div>
          <span class="cal-badge-kpi ${post.status}">
            ${post.status === 'published' ? '✅ Publicado' : (post.status === 'draft' ? '📝 Borrador' : '📌 Programado')}
          </span>
        </div>

        <!-- Live Mock Preview Box -->
        <div class="mock-post-card">
          <div class="mock-post-header">
            <div class="mock-avatar"></div>
            <div class="mock-user-info">
              <span class="mock-name">${App.escapeHtml(post.account_name || 'Mi Marca')}</span>
              <span class="mock-date">📅 ${App.escapeHtml(post.scheduled_for || 'Próximamente')}</span>
            </div>
          </div>

          ${post.media_url ? `
            <div class="mock-media-wrap">
              <img src="${safeImg}" class="mock-media-img" alt="preview" />
            </div>
          ` : ''}

          <div class="mock-post-body">
            <div style="font-weight: 700; color: #fff; margin-bottom: 6px;">${safeHook}</div>
            <div style="white-space: pre-wrap; font-size: 0.82rem; color: #cbd5e1; line-height: 1.45;">${safeCaption}</div>
          </div>
        </div>

        <!-- Modal Actions -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 14px; margin-top: 6px;">
          <button type="button" class="btn-primary-action" style="background: rgba(244, 63, 94, 0.15); color: #fb7185; border: 1px solid rgba(244, 63, 94, 0.3);" onclick="PlannerController.deletePost(${parseInt(post.id, 10)})">
            🗑️ Eliminar
          </button>

          <div style="display: flex; gap: 10px;">
            ${!isPublished ? `
              <button type="button" class="btn-primary-action" style="background: linear-gradient(135deg, #10b981, #059669);" onclick="PlannerController.publishNow(${parseInt(post.id, 10)})">
                🚀 Publicar Ahora
              </button>
            ` : ''}
            <button type="button" class="btn-primary-action" style="background: rgba(255, 255, 255, 0.08);" onclick="PlannerController.closePreviewModal()">
              Cerrar
            </button>
          </div>
        </div>
      </div>
    `;

    App.openModal('modal-post-preview');
  },

  closePreviewModal() {
    App.closeModal('modal-post-preview');
  },

  async publishNow(postId) {
    if (!confirm("¿Deseas enviar esta publicación inmediatamente a la red social?")) return;

    try {
      const res = await App.fetchWithCsrf('api/planner.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'publish_now', id: postId })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast("Publicación enviada con éxito.", "success");
        this.closePreviewModal();
        await this.loadCalendar();
      } else {
        App.showToast(data.error || "Error al publicar.", "error");
      }
    } catch (e) {
      App.showToast("Error de conexión al publicar.", "error");
    }
  },

  async deletePost(postId) {
    if (!confirm("¿Estás seguro de que deseas eliminar esta publicación programada?")) return;

    try {
      const res = await App.fetchWithCsrf('api/planner.php', {
        method: 'POST',
        body: JSON.stringify({ action: 'delete', id: postId })
      });
      const data = await res.json();
      if (data.success) {
        App.showToast("Publicación eliminada.", "success");
        this.closePreviewModal();
        await this.loadGoldenSlots();
        await this.loadCalendar();
      } else {
        App.showToast(data.error || "Error al eliminar.", "error");
      }
    } catch (e) {
      App.showToast("Error de conexión al eliminar.", "error");
    }
  }
};
