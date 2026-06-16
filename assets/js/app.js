(function () {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const baseUrl = (document.querySelector('meta[name="base-url"]')?.content || '').replace(/\/$/, '');

  function url(path) {
    const p = path.startsWith('/') ? path.slice(1) : path;
    return baseUrl ? `${baseUrl}/${p}` : `/${p}`;
  }

  let markAudioCtx = null;

  function getMarkAudioContext() {
    const Ctx = window.AudioContext || window.webkitAudioContext;
    if (!Ctx) return null;
    if (!markAudioCtx) markAudioCtx = new Ctx();
    if (markAudioCtx.state === 'suspended') {
      markAudioCtx.resume();
    }
    return markAudioCtx;
  }

  /** Som curto ao confirmar marcação (gesto do usuário já liberou o áudio). */
  function playMarkSound(hadLunch) {
    const ctx = getMarkAudioContext();
    if (!ctx) return;

    const isYes = parseInt(hadLunch, 10) === 1;
    const t0 = ctx.currentTime;

    function tone(freq, start, duration, volume) {
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.type = 'sine';
      osc.frequency.value = freq;
      gain.gain.setValueAtTime(0, start);
      gain.gain.linearRampToValueAtTime(volume, start + 0.015);
      gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.start(start);
      osc.stop(start + duration + 0.05);
    }

    if (isYes) {
      tone(523.25, t0, 0.1, 0.18);
      tone(659.25, t0 + 0.09, 0.12, 0.16);
      tone(783.99, t0 + 0.17, 0.22, 0.14);
    } else {
      tone(440, t0, 0.14, 0.12);
      tone(349.23, t0 + 0.1, 0.2, 0.1);
    }
  }

  function showToast(message, type, options) {
    const root = document.getElementById('toast-root');
    if (!root) return;

    const el = document.createElement('div');
    el.className = `toast toast--${type || 'info'}`;
    if (options?.undo) {
      el.innerHTML =
        '<span class="toast-text"></span><button type="button" class="toast-undo">Desfazer</button>';
      el.querySelector('.toast-text').textContent = message;
      el.querySelector('.toast-undo').addEventListener('click', () => {
        options.undo();
        el.remove();
      });
    } else {
      el.textContent = message;
    }
    root.appendChild(el);

    requestAnimationFrame(() => el.classList.add('is-visible'));

    const delay = options?.undo ? 8000 : 3200;
    setTimeout(() => {
      el.classList.remove('is-visible');
      setTimeout(() => el.remove(), 300);
    }, delay);
  }

  function confirmDialog(message, title) {
    return new Promise((resolve) => {
      const modal = document.getElementById('confirm-modal');
      if (!modal) {
        resolve(window.confirm(message));
        return;
      }
      const msg = document.getElementById('confirm-modal-message');
      const ttl = document.getElementById('confirm-modal-title');
      const ok = document.getElementById('confirm-modal-ok');
      const cancel = document.getElementById('confirm-modal-cancel');
      if (msg) msg.textContent = message;
      if (ttl) ttl.textContent = title || 'Confirmar';
      modal.hidden = false;

      function close(result) {
        modal.hidden = true;
        ok.removeEventListener('click', onOk);
        cancel.removeEventListener('click', onCancel);
        modal.querySelectorAll('[data-dismiss="modal"]').forEach((el) => {
          el.removeEventListener('click', onCancel);
        });
        resolve(result);
      }
      function onOk() {
        close(true);
      }
      function onCancel() {
        close(false);
      }
      ok.addEventListener('click', onOk);
      cancel.addEventListener('click', onCancel);
      modal.querySelectorAll('[data-dismiss="modal"]').forEach((el) => {
        el.addEventListener('click', onCancel);
      });
    });
  }

  function pinDialog(personName) {
    return new Promise((resolve) => {
      const modal = document.getElementById('pin-modal');
      const input = document.getElementById('pin-input');
      const display = document.getElementById('pin-display');
      const subtitle = document.getElementById('pin-modal-subtitle');
      const okBtn = document.getElementById('pin-modal-ok');
      const cancelBtn = document.getElementById('pin-modal-cancel');
      const pad = document.getElementById('pin-pad');

      if (!modal || !input) {
        resolve(null);
        return;
      }

      let value = '';

      function render() {
        const masked = value.replace(/./g, '•');
        if (display) display.textContent = masked.padEnd(4, '○');
        if (okBtn) okBtn.disabled = value.length !== 4;
      }

      function appendDigit(d) {
        if (value.length >= 4) return;
        value += d;
        input.value = value;
        render();
      }

      function backspace() {
        value = value.slice(0, -1);
        input.value = value;
        render();
      }

      function close(result) {
        modal.hidden = true;
        value = '';
        input.value = '';
        render();
        pad?.querySelectorAll('.pin-pad-key').forEach((btn) => {
          btn.removeEventListener('click', onPadClick);
        });
        okBtn?.removeEventListener('click', onOk);
        cancelBtn?.removeEventListener('click', onCancel);
        modal.querySelectorAll('[data-dismiss="pin-modal"]').forEach((el) => {
          el.removeEventListener('click', onCancel);
        });
        input.removeEventListener('input', onInput);
        resolve(result);
      }

      function onPadClick(e) {
        const key = e.currentTarget.dataset.key;
        if (key === '⌫') backspace();
        else if (key && /^\d$/.test(key)) appendDigit(key);
      }

      function onInput() {
        value = (input.value || '').replace(/\D/g, '').slice(0, 4);
        input.value = value;
        render();
      }

      function onOk() {
        if (value.length === 4) close(value);
      }

      function onCancel() {
        close(null);
      }

      if (subtitle) {
        subtitle.textContent = personName
          ? `Confirme sua identidade, ${personName}.`
          : 'Confirme sua identidade.';
      }

      render();
      modal.hidden = false;
      input.focus();

      pad?.querySelectorAll('.pin-pad-key').forEach((btn) => {
        btn.addEventListener('click', onPadClick);
      });
      okBtn?.addEventListener('click', onOk);
      cancelBtn?.addEventListener('click', onCancel);
      modal.querySelectorAll('[data-dismiss="pin-modal"]').forEach((el) => {
        el.addEventListener('click', onCancel);
      });
      input.addEventListener('input', onInput);
    });
  }

  function debounce(fn, ms) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), ms);
    };
  }

  function countUp(el, target, duration) {
    if (!el) return;
    const start = parseInt(el.textContent, 10) || 0;
    const startTime = performance.now();
    function step(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      const value = Math.floor(start + (target - start) * progress);
      el.textContent = String(value);
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  function setRowPendingState(row) {
    const yesBtn = row.querySelector('.btn-yes');
    const noBtn = row.querySelector('.btn-no');
    if (!yesBtn || !noBtn) return;

    yesBtn.classList.remove('active');
    noBtn.classList.remove('active');
    yesBtn.setAttribute('aria-pressed', 'false');
    noBtn.setAttribute('aria-pressed', 'false');
    row.classList.remove('is-marked-yes', 'is-marked-no', 'is-marked');
    row.classList.add('is-pending');
    row.dataset.status = 'pending';

    const pill = row.querySelector('.status-pill');
    if (pill) {
      pill.className = 'status-pill status-pill--pending';
      pill.textContent = 'Pendente';
    }

    if (typeof window.kioskApplyFilters === 'function') {
      window.kioskApplyFilters();
    }
  }

  function setRowMarkState(row, hadLunch) {
    if (hadLunch === null || hadLunch === undefined || hadLunch === '') {
      setRowPendingState(row);
      return;
    }

    const yesBtn = row.querySelector('.btn-yes');
    const noBtn = row.querySelector('.btn-no');
    if (!yesBtn || !noBtn) return;

    const isYes = parseInt(hadLunch, 10) === 1;
    yesBtn.classList.remove('active');
    noBtn.classList.remove('active');
    yesBtn.setAttribute('aria-pressed', 'false');
    noBtn.setAttribute('aria-pressed', 'false');
    row.classList.remove('is-marked-yes', 'is-marked-no', 'is-pending');
    row.classList.add('is-marked');
    row.dataset.status = isYes ? 'yes' : 'no';

    if (isYes) {
      yesBtn.classList.add('active');
      yesBtn.setAttribute('aria-pressed', 'true');
      row.classList.add('is-marked-yes');
      yesBtn.classList.add('pulse');
      setTimeout(() => yesBtn.classList.remove('pulse'), 400);
    } else {
      noBtn.classList.add('active');
      noBtn.setAttribute('aria-pressed', 'true');
      row.classList.add('is-marked-no');
      noBtn.classList.add('pulse');
      setTimeout(() => noBtn.classList.remove('pulse'), 400);
    }

    const pill = row.querySelector('.status-pill');
    if (pill) {
      pill.className = 'status-pill status-pill--' + (isYes ? 'yes' : 'no');
      pill.textContent = isYes ? 'Almoçou' : 'Não almoçou';
    }

    if (typeof window.kioskApplyFilters === 'function') {
      window.kioskApplyFilters();
    }
  }

  let lastUndoEmployeeId = null;

  async function performUndo() {
    try {
      const formData = new FormData();
      formData.append('csrf_token', csrfToken);
      if (lastUndoEmployeeId) {
        formData.append('employee_id', String(lastUndoEmployeeId));
      }
      const res = await fetch(url('api/undo-lunch.php'), { method: 'POST', body: formData });
      const data = await res.json();
      if (!data.success) {
        showToast(data.error || 'Não foi possível desfazer.', 'error');
        return;
      }
      const row = document.querySelector(`.employee-row[data-employee-id="${data.employee_id}"]`);
      if (row) {
        if (data.reverted && (data.had_lunch === null || data.had_lunch === undefined)) {
          setRowPendingState(row);
        } else {
          setRowMarkState(row, data.had_lunch);
        }
      }
      updateCounters(data.total_yes, data.total_no, data.total_pending);
      showToast('Marcação desfeita.', 'info');
    } catch {
      showToast('Falha ao desfazer.', 'error');
    }
  }

  function initLunchMarking(animateStats, options) {
    options = options || {};
    const requireEmployeePin = !!options.requireEmployeePin;
    const grid = document.getElementById('stats-grid');
    if (grid && animateStats) {
      const yes = parseInt(grid.dataset.yes, 10) || 0;
      const no = parseInt(grid.dataset.no, 10) || 0;
      const pending = parseInt(grid.dataset.pending, 10) || 0;
      countUp(document.getElementById('stat-yes'), yes, 500);
      countUp(document.getElementById('stat-no'), no, 500);
      countUp(document.getElementById('stat-pending'), pending, 500);
    }

    const list = document.getElementById('lunch-list');
    if (!list || list.dataset.locked === '1') return;

    const date = list.dataset.date;

    list.addEventListener('click', async function (e) {
      const btn = e.target.closest('.btn-lunch');
      if (!btn || btn.disabled) return;

      getMarkAudioContext();

      const row = btn.closest('.employee-row');
      const employeeId = row?.dataset.employeeId;
      const hadLunch = btn.dataset.hadLunch;
      if (!employeeId) return;

      const nameEl = row.querySelector('.employee-name, .kiosk-name');
      const personName = nameEl?.textContent?.trim() || 'este colaborador';

      if (hadLunch === '0') {
        const ok = await confirmDialog(
          `Confirmar que ${personName} não almoçou hoje?`,
          'Não almoçou'
        );
        if (!ok) return;
      }

      let employeePin = null;
      if (requireEmployeePin) {
        employeePin = await pinDialog(personName);
        if (!employeePin) return;
      }

      const allBtns = row.querySelectorAll('.btn-lunch');
      allBtns.forEach((b) => {
        b.disabled = true;
      });
      row.classList.add('is-saving');

      try {
        const formData = new FormData();
        formData.append('employee_id', employeeId);
        formData.append('had_lunch', hadLunch);
        formData.append('date', date);
        formData.append('csrf_token', csrfToken);
        if (requireEmployeePin && employeePin) {
          formData.append('employee_pin', employeePin);
        }

        const res = await fetch(url('api/toggle-lunch.php'), {
          method: 'POST',
          body: formData,
        });
        const data = await res.json();

        if (!data.success) {
          showToast(data.error || 'Erro ao registrar.', 'error');
          return;
        }

        setRowMarkState(row, data.had_lunch);
        updateCounters(data.total_yes, data.total_no, data.total_pending);
        playMarkSound(data.had_lunch);

        lastUndoEmployeeId = data.employee_id;
        const label = parseInt(data.had_lunch, 10) === 1 ? 'Almoçou' : 'Não almoçou';
        const name = data.employee_name || personName;
        showToast(`${name}: ${label}`, 'success', { undo: performUndo });
      } catch {
        showToast('Falha na comunicação com o servidor.', 'error');
      } finally {
        allBtns.forEach((b) => {
          b.disabled = false;
        });
        row.classList.remove('is-saving');
      }
    });
  }

  function initHome() {
    if (!document.getElementById('stats-grid')) return;
    initLunchMarking(true);
  }

  function initKiosk() {
    const requirePin =
      document.body.dataset.requireEmployeePin === '1' ||
      document.querySelector('meta[name="require-employee-pin"]')?.content === '1';
    initLunchMarking(false, { requireEmployeePin: requirePin });

    const search = document.getElementById('kiosk-search');
    const chips = document.querySelectorAll('#kiosk-filters .filter-chip');
    const noResults = document.getElementById('kiosk-no-results');
    const listMeta = document.getElementById('kiosk-list-meta');
    let activeFilter = 'all';

    function applyKioskFilters() {
      const q = (search?.value || '').trim().toLowerCase();
      let visible = 0;
      document.querySelectorAll('.kiosk-card').forEach((card) => {
        const name = card.dataset.name || '';
        const status = card.dataset.status || 'pending';
        const matchSearch = !q || name.includes(q);
        const matchFilter =
          activeFilter === 'all' ||
          (activeFilter === 'pending' && status === 'pending') ||
          (activeFilter === 'yes' && status === 'yes') ||
          (activeFilter === 'no' && status === 'no');
        const show = matchSearch && matchFilter;
        card.classList.toggle('is-hidden', !show);
        if (show) visible++;
      });
      if (noResults) noResults.classList.toggle('is-hidden', visible > 0);
      if (listMeta) {
        listMeta.textContent =
          visible === 1 ? '1 colaborador visível' : `${visible} colaboradores visíveis`;
      }
    }

    window.kioskApplyFilters = applyKioskFilters;

    chips.forEach((chip) => {
      chip.addEventListener('click', () => {
        activeFilter = chip.dataset.filter || 'all';
        chips.forEach((c) => {
          const on = c === chip;
          c.classList.toggle('is-active', on);
          c.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        applyKioskFilters();
      });
    });

    if (search) {
      search.addEventListener('input', debounce(applyKioskFilters, 150));
    }
    applyKioskFilters();

    const idleMinutes = parseInt(
      document.querySelector('meta[name="kiosk-idle-minutes"]')?.content || '0',
      10
    );
    if (idleMinutes > 0) {
      let idleTimer;
      const resetIdle = () => {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(() => {
          window.location.href = url('kiosk.php?action=lock');
        }, idleMinutes * 60 * 1000);
      };
      ['click', 'touchstart', 'keydown', 'input'].forEach((ev) => {
        document.addEventListener(ev, resetIdle, { passive: true });
      });
      resetIdle();
    }

    const clockEl = document.getElementById('kiosk-clock');
    if (clockEl) {
      const pad = (n) => String(n).padStart(2, '0');
      const tickClock = () => {
        const now = new Date();
        clockEl.textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
      };
      tickClock();
      setInterval(tickClock, 1000);
    }

    const listHash = document.querySelector('meta[name="kiosk-list-hash"]')?.content || '';
    const serverDate = document.querySelector('meta[name="kiosk-server-date"]')?.content || '';
    const refreshSec = parseInt(
      document.querySelector('meta[name="kiosk-refresh-seconds"]')?.content || '0',
      10
    );

    async function checkKioskFreshness() {
      try {
        const res = await fetch(url('api/kiosk-data.php'), { credentials: 'same-origin' });
        if (res.status === 401) {
          window.location.reload();
          return;
        }
        const data = await res.json();
        if (!data.success) return;
        if (data.date !== serverDate || data.list_hash !== listHash) {
          window.location.reload();
        }
      } catch {
        /* rede instável: mantém tela atual */
      }
    }

    if (refreshSec > 0) {
      setInterval(checkKioskFreshness, refreshSec * 1000);
    }

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        checkKioskFreshness();
      }
    });
  }

  function updateCounters(yes, no, pending) {
    const map = {
      'stat-yes': yes,
      'stat-no': no,
      'stat-pending': pending,
    };

    document.querySelectorAll('.stat-card, .kiosk-stat').forEach((c) => c.classList.add('is-updating'));
    setTimeout(() => {
      document.querySelectorAll('.stat-card, .kiosk-stat').forEach((c) => c.classList.remove('is-updating'));
    }, 350);

    Object.keys(map).forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.textContent = String(map[id]);
    });

    const grid = document.getElementById('stats-grid');
    const total = grid ? parseInt(grid.dataset.total, 10) || yes + no + pending : yes + no + pending;
    const marked = yes + no;
    const pct = total > 0 ? Math.round((marked / total) * 100) : 0;

    const fill = document.getElementById('progress-fill');
    const label = document.getElementById('progress-label');
    const pctEl = document.getElementById('progress-pct');
    if (fill) fill.style.width = pct + '%';
    if (label) label.textContent = `${marked} de ${total} registrados`;
    if (pctEl) pctEl.textContent = pct + '%';

    const progress = document.querySelector('.kiosk-progress, .day-progress');
    if (progress) progress.setAttribute('aria-valuenow', String(pct));

    if (grid) {
      grid.dataset.yes = yes;
      grid.dataset.no = no;
      grid.dataset.pending = pending;
    }
  }

  function initReport() {
    const form = document.getElementById('report-filters');
    if (!form) return;

    let sortBy = 'lunch_date';
    let sortDir = 'DESC';
    let currentPage = 1;
    let lastFilters = {};

    const rangeRadios = form.querySelectorAll('input[name="range_type"]');
    const fieldSingle = document.getElementById('field-single-date');
    const fieldPeriod = document.getElementById('field-period');

    function toggleRangeFields() {
      const isPeriod = form.querySelector('input[name="range_type"]:checked')?.value === 'period';
      if (fieldSingle) fieldSingle.classList.toggle('filter-field--hidden', isPeriod);
      if (fieldPeriod) {
        fieldPeriod.classList.toggle('filter-field--hidden', !isPeriod);
        if (isPeriod) fieldPeriod.classList.add('filter-field--period');
      }
    }

    rangeRadios.forEach((r) => r.addEventListener('change', toggleRangeFields));
    toggleRangeFields();

    const dateNotice = document.getElementById('report-date-notice');

    function updateReportDateNotice() {
      if (!dateNotice) return;
      const isPeriod = form.querySelector('input[name="range_type"]:checked')?.value === 'period';
      let filterDate = document.getElementById('filter-date')?.value;
      if (isPeriod) {
        const start = document.getElementById('filter-date-start')?.value;
        const end = document.getElementById('filter-date-end')?.value;
        const today = localTodayIso();
        dateNotice.classList.toggle('is-hidden', start === today && end === today);
        return;
      }
      dateNotice.classList.toggle('is-hidden', filterDate === localTodayIso());
    }

    ['filter-date', 'filter-date-start', 'filter-date-end'].forEach((id) => {
      document.getElementById(id)?.addEventListener('change', updateReportDateNotice);
    });
    rangeRadios.forEach((r) => r.addEventListener('change', updateReportDateNotice));
    updateReportDateNotice();

    function getFilters() {
      const isPeriod = form.querySelector('input[name="range_type"]:checked')?.value === 'period';
      let dateStart;
      let dateEnd;

      if (isPeriod) {
        dateStart = document.getElementById('filter-date-start')?.value;
        dateEnd = document.getElementById('filter-date-end')?.value;
      } else {
        const d = document.getElementById('filter-date')?.value;
        dateStart = d;
        dateEnd = d;
      }

      return {
        date_start: dateStart,
        date_end: dateEnd,
        department_id: document.getElementById('filter-department')?.value || '',
        status: document.getElementById('filter-status')?.value || 'all',
        sort_by: sortBy,
        sort_dir: sortDir,
        page: currentPage,
      };
    }

    function buildQuery(filters) {
      const params = new URLSearchParams();
      Object.entries(filters).forEach(([k, v]) => {
        if (v !== '' && v != null) params.set(k, v);
      });
      return params.toString();
    }

    async function loadReport() {
      lastFilters = getFilters();
      const qs = buildQuery(lastFilters);
      const tbody = document.getElementById('report-tbody');
      const summary = document.getElementById('report-summary');

      const cardRoot = document.getElementById('report-cards');
      if (cardRoot) cardRoot.innerHTML = '';
      tbody.innerHTML = '<tr><td colspan="6" class="table-empty">Carregando…</td></tr>';

      try {
        const res = await fetch(url('api/get-report.php?' + qs));
        const data = await res.json();

        if (!data.success) {
          tbody.innerHTML =
            '<tr><td colspan="6" class="table-empty">Erro ao carregar relatório.</td></tr>';
          showToast(data.error || 'Erro no relatório.', 'error');
          return;
        }

        if (!data.records.length) {
          tbody.innerHTML =
            '<tr><td colspan="6" class="table-empty">Nenhum registro encontrado.</td></tr>';
          if (summary) summary.textContent = '';
          renderPagination(data.pagination || { page: 1, total_pages: 1 });
          return;
        }

        const cardRoot = document.getElementById('report-cards');
        const rowsHtml = data.records
          .map((r) => {
            let statusClass = 'status-pending';
            if (r.had_lunch === 1) statusClass = 'status-yes';
            else if (r.had_lunch === 0) statusClass = 'status-no';
            const marked = r.marked_at ? formatDateTime(r.marked_at) : '—';
            const dateBr = formatDate(r.lunch_date);
            const empName = escapeHtml(formatDisplayName(r.employee_name));
            const source = r.marked_source ? escapeHtml(r.marked_source) : '—';
            return `<tr>
              <td>${empName}</td>
              <td>${escapeHtml(r.department_name)}</td>
              <td class="mono">${dateBr}</td>
              <td class="${statusClass}">${escapeHtml(r.status)}</td>
              <td class="mono">${marked}</td>
              <td class="mono report-source-col">${source}</td>
            </tr>`;
          })
          .join('');
        tbody.innerHTML = rowsHtml;

        if (cardRoot) {
          cardRoot.innerHTML = data.records
            .map((r) => {
              let pill = 'Pendente';
              let pillClass = 'status-pill--pending';
              if (r.had_lunch === 1) {
                pill = 'Almoçou';
                pillClass = 'status-pill--yes';
              } else if (r.had_lunch === 0) {
                pill = 'Não almoçou';
                pillClass = 'status-pill--no';
              }
              const marked = r.marked_at ? formatDateTime(r.marked_at) : '—';
              return `<article class="report-card">
                <div class="report-card-head">
                  <strong>${escapeHtml(formatDisplayName(r.employee_name))}</strong>
                  <span class="status-pill ${pillClass}">${pill}</span>
                </div>
                <dl class="report-card-meta">
                  <div><dt>Depto</dt><dd>${escapeHtml(r.department_name)}</dd></div>
                  <div><dt>Data</dt><dd>${formatDate(r.lunch_date)}</dd></div>
                  <div><dt>Horário</dt><dd>${marked}</dd></div>
                  ${r.marked_source ? `<div><dt>Origem</dt><dd>${escapeHtml(r.marked_source)}</dd></div>` : ''}
                </dl>
              </article>`;
            })
            .join('');
        }

        if (summary && data.summary) {
          if (data.mode === 'pending') {
            summary.textContent = `${data.summary.total_pending ?? data.summary.total_employees} colaboradores sem registro na data`;
          } else {
            summary.textContent =
              `Resumo: ${data.summary.total_yes} almoçaram · ` +
              `${data.summary.total_no} não almoçaram · ` +
              `${data.summary.total_employees} no período`;
          }
        }

        updateSortHeaders();
        renderPagination(data.pagination);
        updateReportDateNotice();
        updatePrintMeta(lastFilters);
      } catch {
        tbody.innerHTML =
          '<tr><td colspan="6" class="table-empty">Erro ao carregar relatório.</td></tr>';
        showToast('Falha ao carregar relatório.', 'error');
      }
    }

    function renderPagination(pag) {
      const nav = document.getElementById('report-pagination');
      if (!nav || !pag) return;

      nav.innerHTML = '';
      const { page, total_pages } = pag;
      if (total_pages <= 1) return;

      const prev = document.createElement('button');
      prev.textContent = '‹';
      prev.disabled = page <= 1;
      prev.addEventListener('click', () => {
        currentPage = page - 1;
        loadReport();
      });
      nav.appendChild(prev);

      for (let i = 1; i <= total_pages; i++) {
        if (total_pages > 7 && Math.abs(i - page) > 2 && i !== 1 && i !== total_pages) {
          if (i === 2 || i === total_pages - 1) {
            const dots = document.createElement('span');
            dots.textContent = '…';
            dots.style.padding = '0 0.25rem';
            nav.appendChild(dots);
          }
          continue;
        }
        const b = document.createElement('button');
        b.textContent = String(i);
        if (i === page) b.classList.add('is-active');
        b.addEventListener('click', () => {
          currentPage = i;
          loadReport();
        });
        nav.appendChild(b);
      }

      const next = document.createElement('button');
      next.textContent = '›';
      next.disabled = page >= total_pages;
      next.addEventListener('click', () => {
        currentPage = page + 1;
        loadReport();
      });
      nav.appendChild(next);
    }

    function updateSortHeaders() {
      document.querySelectorAll('#report-table th[data-sort]').forEach((th) => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        if (th.dataset.sort === sortBy) {
          th.classList.add(sortDir === 'ASC' ? 'sorted-asc' : 'sorted-desc');
        }
      });
    }

    document.querySelectorAll('#report-table th[data-sort]').forEach((th) => {
      th.addEventListener('click', () => {
        const col = th.dataset.sort;
        if (sortBy === col) {
          sortDir = sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
          sortBy = col;
          sortDir = 'ASC';
        }
        currentPage = 1;
        loadReport();
      });
    });

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      currentPage = 1;
      loadReport();
    });

    const pendingBtn = document.getElementById('btn-pending-today');
    if (pendingBtn) {
      pendingBtn.addEventListener('click', () => {
        const today = localTodayIso();
        document.getElementById('filter-date')?.setAttribute('value', today);
        document.getElementById('filter-date-start')?.setAttribute('value', today);
        document.getElementById('filter-date-end')?.setAttribute('value', today);
        const single = form.querySelector('input[name="range_type"][value="single"]');
        if (single) single.checked = true;
        toggleRangeFields();
        document.getElementById('filter-status').value = 'pending_today';
        currentPage = 1;
        updateReportDateNotice();
        loadReport();
      });
    }

    const exportBtn = document.getElementById('btn-export-csv');
    if (exportBtn) {
      exportBtn.addEventListener('click', (e) => {
        e.preventDefault();
        const f = getFilters();
        window.location.href = url('export/report-csv.php?' + buildQuery(f));
      });
    }

    const printBtn = document.getElementById('btn-print');
    if (printBtn) {
      printBtn.addEventListener('click', () => window.print());
    }

    function updatePrintMeta(filters) {
      const meta = document.getElementById('print-meta');
      if (!meta) return;
      meta.textContent = `Período: ${formatDate(filters.date_start)} a ${formatDate(filters.date_end)}`;
    }
  }

  function initAdmin() {
    const tabs = document.querySelectorAll('.admin-tab');
    if (!tabs.length) return;

    tabs.forEach((tab) => {
      tab.addEventListener('click', () => {
        const id = tab.dataset.tab;
        tabs.forEach((t) => t.classList.toggle('is-active', t === tab));
        document.querySelectorAll('.admin-tab-panel').forEach((p) => {
          p.classList.toggle('is-active', p.id === 'tab-' + id);
        });
      });
    });

    document.querySelectorAll('.btn-edit-emp').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.getElementById('employee_id').value = btn.dataset.id;
        document.getElementById('emp_name').value = btn.dataset.name;
        document.getElementById('emp_department').value = btn.dataset.dept;
      });
    });

    const clearEmp = document.getElementById('btn-clear-employee');
    if (clearEmp) {
      clearEmp.addEventListener('click', () => {
        document.getElementById('employee_id').value = '';
        document.getElementById('emp_name').value = '';
      });
    }

    document.querySelectorAll('.btn-edit-dept').forEach((btn) => {
      btn.addEventListener('click', () => {
        document.getElementById('department_edit_id').value = btn.dataset.id;
        document.getElementById('dept_name').value = btn.dataset.name;
      });
    });

    const clearDept = document.getElementById('btn-clear-dept');
    if (clearDept) {
      clearDept.addEventListener('click', () => {
        document.getElementById('department_edit_id').value = '';
        document.getElementById('dept_name').value = '';
      });
    }

    document.querySelectorAll('.upload-excel-form input[type="file"]').forEach((input) => {
      input.addEventListener('change', () => {
        if (input.files?.length) {
          input.closest('form')?.submit();
        }
      });
    });

    const empSearch = document.getElementById('emp-table-search');
    const empFilter = document.getElementById('emp-table-filter');
    const empCount = document.getElementById('emp-table-count');
    const empRows = document.querySelectorAll('.emp-table-row');

    function applyEmployeeTableFilter() {
      const q = (empSearch?.value || '').trim().toLowerCase();
      const mode = empFilter?.value || 'all';
      let visible = 0;
      empRows.forEach((row) => {
        const text = row.dataset.search || '';
        const active = row.dataset.active === '1';
        const hasPin = row.dataset.hasPin === '1';
        const matchSearch = !q || text.includes(q);
        let matchFilter = true;
        if (mode === 'active') matchFilter = active;
        else if (mode === 'inactive') matchFilter = !active;
        else if (mode === 'no-pin') matchFilter = active && !hasPin;
        const show = matchSearch && matchFilter;
        row.classList.toggle('is-hidden', !show);
        if (show) visible++;
      });
      if (empCount) {
        empCount.textContent =
          visible === 1 ? '1 funcionário' : `${visible} funcionários`;
      }
    }

    empSearch?.addEventListener('input', debounce(applyEmployeeTableFilter, 150));
    empFilter?.addEventListener('change', applyEmployeeTableFilter);
    applyEmployeeTableFilter();
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  function formatDisplayName(str) {
    if (!str) return '';
    return str
      .toLowerCase()
      .replace(/(?:^|\s)\S/g, (a) => a.toUpperCase())
      .replace(/\b(De|Da|Do|Dos|Das|E)\b/g, (m) => m.toLowerCase());
  }

  function localTodayIso() {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  }

  function formatDate(iso) {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
  }

  function formatDateTime(iso) {
    if (!iso) return '';
    const dt = new Date(iso.replace(' ', 'T'));
    if (isNaN(dt.getTime())) return iso;
    const pad = (n) => String(n).padStart(2, '0');
    return `${pad(dt.getDate())}/${pad(dt.getMonth() + 1)}/${dt.getFullYear()} ${pad(dt.getHours())}:${pad(dt.getMinutes())}`;
  }

  /* ---- Tema São João: ativa automaticamente em junho ---- */
  function initSaoJoaoTheme() {
    const month = new Date().getMonth(); // 0=jan … 5=jun
    if (month !== 5) return;
    if (sessionStorage.getItem('sj-dismissed')) return;

    document.documentElement.setAttribute('data-theme', 'sao-joao');
    injectSaoJoaoDecorations();
  }

  function injectSaoJoaoDecorations() {
    injectVaral();
    injectFogueira();
    injectToggle();
  }

  /* --- varal de bandeirolas triangulares SVG --- */
  function injectVaral() {
    const colors = ['#dc2626','#f59e0b','#16a34a','#7c3aed','#2563eb','#ec4899','#ea580c','#0891b2'];
    const W = window.innerWidth || 1400;
    const flagW = 28, flagH = 36, gap = 4, ropeY = 14;
    const total = Math.ceil(W / (flagW + gap)) + 2;
    let flags = '';
    for (let i = 0; i < total; i++) {
      const x = i * (flagW + gap);
      const c = colors[i % colors.length];
      const cx = x + flagW / 2;
      flags += `<polygon points="${x},0 ${x + flagW},0 ${cx},${flagH}"
        fill="${c}" opacity="0.92"
        style="animation:sj-swing ${(2.2 + (i % 4) * 0.35).toFixed(2)}s ease-in-out infinite alternate;transform-origin:${cx}px 0px"/>`;
    }
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${flagH + ropeY}"
      style="display:block;position:absolute;top:0;left:0">
      <line x1="0" y1="${ropeY}" x2="${W}" y2="${ropeY}" stroke="#5c2e08" stroke-width="2" opacity="0.9"/>
      ${flags}
    </svg>`;

    const wrap = document.createElement('div');
    wrap.id = 'sj-varal';
    wrap.innerHTML = svg;
    document.body.appendChild(wrap);
  }

  /* --- fogueira SVG animada no rodapé --- */
  function injectFogueira() {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 110"
        style="width:220px;height:110px;display:block">
      <defs>
        <radialGradient id="glow" cx="50%" cy="80%" r="55%">
          <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.35"/>
          <stop offset="100%" stop-color="#dc2626" stop-opacity="0"/>
        </radialGradient>
      </defs>
      <!-- reflexo no chão -->
      <ellipse cx="110" cy="104" rx="60" ry="8" fill="url(#glow)"/>
      <!-- lenhas -->
      <rect x="72" y="88" width="76" height="8" rx="4" fill="#7c2d12" transform="rotate(-10,110,92)"/>
      <rect x="72" y="88" width="76" height="8" rx="4" fill="#92400e" transform="rotate(10,110,92)"/>
      <!-- brasa -->
      <ellipse cx="110" cy="94" rx="34" ry="8" fill="#dc2626" opacity="0.7"
        style="animation:sj-fire-flicker 0.9s ease-in-out infinite"/>
      <ellipse cx="110" cy="94" rx="22" ry="6" fill="#f59e0b" opacity="0.85"
        style="animation:sj-fire-flicker 0.7s ease-in-out infinite reverse"/>
      <!-- chamas externas -->
      <ellipse cx="110" cy="70" rx="30" ry="38" fill="#dc2626" opacity="0.75"
        style="animation:sj-fire-flicker 1.1s ease-in-out infinite"/>
      <ellipse cx="97" cy="72" rx="18" ry="28" fill="#ea580c" opacity="0.7"
        style="animation:sj-fire-flicker 0.85s ease-in-out infinite 0.2s"/>
      <ellipse cx="123" cy="72" rx="18" ry="28" fill="#ea580c" opacity="0.7"
        style="animation:sj-fire-flicker 0.95s ease-in-out infinite 0.1s"/>
      <!-- chamas centrais (mais quentes) -->
      <ellipse cx="110" cy="65" rx="20" ry="32" fill="#f59e0b" opacity="0.8"
        style="animation:sj-fire-flicker 0.75s ease-in-out infinite"/>
      <ellipse cx="110" cy="60" rx="12" ry="26" fill="#fef08a" opacity="0.7"
        style="animation:sj-fire-flicker 0.65s ease-in-out infinite 0.15s"/>
      <!-- ponta branca -->
      <ellipse cx="110" cy="52" rx="6" ry="14" fill="#fffbeb" opacity="0.6"
        style="animation:sj-fire-flicker 0.55s ease-in-out infinite"/>
      <!-- brasas voando -->
      <circle cx="88"  cy="75" r="2.5" fill="#fbbf24" opacity="0.9"
        style="animation:sj-ember 1.8s ease-out infinite;--ex:-8px"/>
      <circle cx="132" cy="70" r="2"   fill="#f97316" opacity="0.85"
        style="animation:sj-ember 2.1s ease-out infinite 0.4s;--ex:10px"/>
      <circle cx="105" cy="60" r="1.5" fill="#fef08a" opacity="0.8"
        style="animation:sj-ember 1.6s ease-out infinite 0.9s;--ex:-4px"/>
      <circle cx="118" cy="65" r="2"   fill="#fbbf24" opacity="0.75"
        style="animation:sj-ember 2.3s ease-out infinite 0.2s;--ex:6px"/>
    </svg>`;

    const wrap = document.createElement('div');
    wrap.id = 'sj-fogueira';
    wrap.innerHTML = svg;
    document.body.appendChild(wrap);
  }

  /* --- botão liga/desliga --- */
  function injectToggle() {
    const btn = document.createElement('button');
    btn.id = 'sj-theme-toggle';
    btn.title = 'Desativar tema São João';
    btn.innerHTML = '🔥';

    let active = true;
    btn.addEventListener('click', function () {
      active = !active;
      if (active) {
        document.documentElement.setAttribute('data-theme', 'sao-joao');
        btn.innerHTML = '🔥';
        btn.title = 'Desativar tema São João';
        sessionStorage.removeItem('sj-dismissed');
        document.getElementById('sj-varal')   && (document.getElementById('sj-varal').style.display   = '');
        document.getElementById('sj-fogueira') && (document.getElementById('sj-fogueira').style.display = '');
      } else {
        document.documentElement.removeAttribute('data-theme');
        btn.innerHTML = '🌟';
        btn.title = 'Ativar tema São João';
        sessionStorage.setItem('sj-dismissed', '1');
        document.getElementById('sj-varal')   && (document.getElementById('sj-varal').style.display   = 'none');
        document.getElementById('sj-fogueira') && (document.getElementById('sj-fogueira').style.display = 'none');
      }
    });

    document.body.appendChild(btn);
  }

  document.addEventListener('DOMContentLoaded', function () {
    const page = document.body.dataset.page;
    initSaoJoaoTheme();
    if (page === 'home') initHome();
    if (page === 'kiosk') initKiosk();
    if (page === 'report') initReport();
    if (document.querySelector('.admin-panel')) initAdmin();
  });
})();
