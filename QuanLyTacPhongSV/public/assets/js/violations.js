(() => {
  'use strict';

  const form = document.getElementById('violation-filters');
  const tbody = document.getElementById('violation-body');
  const dialog = document.getElementById('violation-dialog');
  const state = { page: 1, pages: 1, current: null, source: 'ALL', lastKnownMaxId: 0, newIds: new Set() };
  const EVIDENCE_BADGES = {
    available: ['Còn ảnh', 'bg-secondary-container text-on-secondary-container'],
    expired: ['Hết hạn', 'bg-tertiary-container text-on-tertiary-container'],
    deleted: ['Đã xóa (30 ngày)', 'bg-surface-container text-on-surface-variant'],
    missing: ['Mất tệp', 'bg-error-container text-on-error-container'],
    none: ['Không có', 'bg-surface-container text-on-surface-variant'],
  };

  function badge(text, className) {
    return CIM.el('span', `inline-block rounded-full px-2 py-0.5 font-label-sm text-label-sm ${className}`, text);
  }

  function percent(val) {
    if (val === null || val === undefined || isNaN(val)) return '—';
    return `${Math.round(val * 100)}%`;
  }

  function params() {
    const data = Object.fromEntries(new FormData(form).entries());
    const res = { ...data, page: state.page, page_size: document.getElementById('page-size').value };
    if (state.source && state.source !== 'ALL') {
      res.source = state.source;
    }
    return res;
  }

  async function load(silent = false) {
    if (!silent) {
      CIM.stateRow(tbody, 7, 'Đang tải bản ghi…', 'loading');
    }
    try {
      const result = await CIM.api('violations', { params: params() });
      state.pages = result.pages;
      document.getElementById('violation-summary').textContent = `Tổng ${result.total.toLocaleString('vi-VN')} bản ghi`;
      document.getElementById('page-info').textContent = `Trang ${result.page}/${result.pages}`;
      document.getElementById('page-prev').disabled = result.page <= 1;
      document.getElementById('page-next').disabled = result.page >= result.pages;
      if (!result.items.length) {
        CIM.stateRow(tbody, 7, 'Không có bản ghi vi phạm phù hợp bộ lọc.');
        return;
      }

      // Phát hiện bản ghi mới khi người dùng đang xem trang 1
      if (state.page === 1 && result.items.length) {
        const itemIds = result.items.map((i) => i.id);
        const currentMax = Math.max(...itemIds);
        if (state.lastKnownMaxId > 0 && currentMax > state.lastKnownMaxId) {
          const newItems = result.items.filter((i) => i.id > state.lastKnownMaxId);
          newItems.forEach((i) => state.newIds.add(i.id));
          if (newItems.length === 1) {
            const first = newItems[0];
            const typeName = first.type?.name || 'Vi phạm tác phong';
            const areaName = first.area?.name || 'Khu vực';
            CIM.toast(`🔔 Camera phát hiện vi phạm mới: ${typeName} tại ${areaName} (#${first.id})!`, 'info');
          } else {
            CIM.toast(`🔔 Camera vừa phát hiện ${newItems.length} vi phạm mới cần kiểm tra!`, 'info');
          }
          setTimeout(() => {
            state.newIds.clear();
            document.querySelectorAll('.new-violation-row').forEach((r) => {
              r.classList.remove('bg-amber-100/60', 'new-violation-row');
            });
          }, 8000);
        }
        state.lastKnownMaxId = Math.max(state.lastKnownMaxId, currentMax);
      }

      tbody.replaceChildren(...result.items.map((item) => renderRow(item, state.newIds.has(item.id))));
    } catch (error) {
      if (!silent) {
        CIM.stateRow(tbody, 7, error.message, 'error');
      }
    }
  }

  function renderRow(item, isNew = false) {
    const rowClass = isNew
      ? 'border-t border-surface-container bg-amber-100/60 hover:bg-amber-100 transition-colors duration-500 new-violation-row'
      : 'border-t border-surface-container hover:bg-surface-container-low/60';
    const row = CIM.el('tr', rowClass);

    const idCell = CIM.el('td', 'px-space-md py-3 font-data-mono');
    idCell.append(CIM.el('span', '', `#${item.id}`));
    if (isNew) {
      idCell.append(' ', CIM.el('span', 'inline-block rounded bg-amber-600 text-white text-[10px] font-bold px-1.5 py-0.5 uppercase ml-1 animate-pulse', 'MỚI'));
    }
    if (item.source === 'CO_DO') {
      idCell.append(' ', CIM.el('span', 'inline-block rounded bg-red-100 text-red-800 text-[10px] font-bold px-1.5 py-0.5 uppercase ml-1', 'CỜ ĐỎ'));
    }
    row.append(idCell);

    row.append(CIM.el('td', 'px-space-md py-3 whitespace-nowrap', CIM.formatDateTime(item.occurred_at)));
    row.append(CIM.el('td', 'px-space-md py-3 font-medium', item.type.name));

    const place = CIM.el('td', 'px-space-md py-3');
    place.append(CIM.el('div', 'font-semibold', item.area.name));
    if (item.student) {
      place.append(CIM.el('div', 'text-primary font-medium text-[12px]', `SV: ${item.student.code} - ${item.student.name} (${item.student.class_name})`));
    } else {
      place.append(CIM.el('div', 'text-on-surface-variant', `${item.camera.code} - ${item.camera.name}`));
    }
    row.append(place);

    const evidenceCell = CIM.el('td', 'px-space-md py-3');
    if (item.source === 'CO_DO') {
      evidenceCell.append(badge('Biên bản', 'bg-blue-100 text-blue-800'));
    } else {
      evidenceCell.append(badge(...(EVIDENCE_BADGES[item.evidence.status] || EVIDENCE_BADGES.none)));
    }
    row.append(evidenceCell);

    const statusCell = CIM.el('td', 'px-space-md py-3');
    const isMoi = item.processing.status === 'MOI';
    const statusLabel = CIM.PROCESSING_LABELS[item.processing.status] || item.processing.status;
    if (isMoi) {
      statusCell.append(badge(statusLabel, 'bg-error-container text-on-error-container font-semibold animate-pulse'));
    } else {
      statusCell.append(CIM.el('span', '', statusLabel));
    }
    row.append(statusCell);

    const actions = CIM.el('td', 'px-space-md py-3 text-right');
    const view = CIM.el('button', 'rounded-lg bg-surface-container px-3 py-1.5 font-label-md hover:bg-surface-container-high transition-colors', 'Chi tiết');
    view.type = 'button';
    view.addEventListener('click', () => openDetail(item.id));
    actions.append(view);
    row.append(actions);
    return row;
  }

  function field(label, value) {
    return [CIM.el('dt', 'text-on-surface-variant', label), CIM.el('dd', 'font-semibold break-words', value ?? '—')];
  }

  async function openDetail(id) {
    try {
      const { data } = await CIM.api('violation', { params: { id } });
      state.current = data;
      const isCoDo = data.source === 'CO_DO' || data.student;
      document.getElementById('detail-title').textContent = isCoDo ? `Biên bản vi phạm Cờ Đỏ #${data.id}` : `Bản ghi vi phạm #${data.id}`;
      
      const fields = [
        ...field('Thời điểm', CIM.formatDateTime(data.occurred_at)),
        ...field('Loại vi phạm', data.type.name),
      ];

      if (data.student) {
        fields.push(
          ...field('Sinh viên', `${data.student.code} - ${data.student.name}`),
          ...field('Lớp', data.student.class_name || '—')
        );
      }

      fields.push(
        ...field('Vị trí / Khu vực', data.area.name),
        ...field('Nguồn ghi nhận', isCoDo ? 'Đội Cờ Đỏ kiểm tra trực tiếp' : `${data.camera.code} - ${data.camera.name}`)
      );

      if (!isCoDo) {
        fields.push(
          ...field('Độ tin cậy ĐP', percent(data.uniform_confidence)),
          ...field('Độ tin cậy thẻ', percent(data.card_confidence)),
          ...field('Ảnh hết hạn', CIM.formatDateTime(data.evidence.expires_at))
        );
      }

      fields.push(
        ...field('Người xử lý', data.processing.processed_by ? `${data.processing.processed_by} (${CIM.formatDateTime(data.processing.processed_at)})` : '—')
      );

      document.getElementById('detail-fields').replaceChildren(...fields);
      const processing = document.getElementById('processing-form');
      processing.elements.status.value = data.processing.status;
      processing.elements.note.value = data.processing.note || '';
      renderEvidence(data);
      dialog.showModal();
    } catch (error) {
      CIM.toast(error.message, 'error');
    }
  }

  function renderEvidence(data) {
    const box = document.getElementById('detail-evidence');
    const isCoDo = data.source === 'CO_DO' || data.student;
    if (isCoDo) {
      const message = CIM.el('div', 'flex flex-col items-center gap-3 text-center p-4 text-on-surface-variant');
      message.append(
        CIM.el('span', 'material-symbols-outlined text-[48px] text-primary', 'assignment_turned_in'),
        CIM.el('p', 'font-semibold text-on-surface text-body-md', 'Biên bản lập trực tiếp bởi Đội Cờ Đỏ'),
        CIM.el('p', 'font-body-sm text-body-sm text-on-surface-variant', data.processing?.note || 'Đã kiểm tra tác phong sinh viên tại phòng học/khu vực và lập biên bản ghi nhận vào CSDL.')
      );
      box.replaceChildren(message);
      return;
    }

    if (!data.evidence.available) {
      const message = CIM.el('div', 'flex flex-col items-center gap-2 text-center text-on-surface-variant');
      message.append(CIM.el('span', 'material-symbols-outlined text-[40px]', 'hide_image'),
        CIM.el('p', '', data.evidence.message || 'Không có ảnh bằng chứng.'),
        CIM.el('p', 'font-label-sm text-label-sm', 'Thông tin thời gian, khu vực, camera và loại vi phạm vẫn được giữ cho thống kê.'));
      box.replaceChildren(message);
      return;
    }
    const image = CIM.el('img', 'max-h-[420px] rounded-lg object-contain');
    image.alt = `Ảnh crop bằng chứng của bản ghi #${data.id}`;
    image.src = CIM.buildUrl('evidence', { id: data.id }).toString();
    image.addEventListener('error', () => {
      renderEvidence({ ...data, evidence: { available: false, message: 'Không tải được ảnh (ảnh có thể vừa hết hạn hoặc bị xóa).' } });
    });
    box.replaceChildren(image);
  }

  document.getElementById('processing-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const target = event.currentTarget;
    const button = target.querySelector('button[type="submit"]');
    const status = target.elements.status.value;
    CIM.setBusy(button, true);
    try {
      const { data } = await CIM.api('violation', {
        method: 'PATCH', params: { id: state.current.id }, body: { status, note: target.elements.note.value },
      });
      state.current = data;
      CIM.toast('Đã cập nhật trạng thái điều phối Đội Cờ Đỏ.');
      dialog.close();
      load();
    } catch (error) {
      CIM.toast(error.message, 'error');
    } finally {
      CIM.setBusy(button, false);
    }
  });

  document.getElementById('detail-close').addEventListener('click', () => dialog.close());
  form.addEventListener('submit', (event) => { event.preventDefault(); state.page = 1; load(); });
  form.addEventListener('reset', () => setTimeout(() => { state.page = 1; load(); }, 0));
  document.getElementById('page-size').addEventListener('change', () => { state.page = 1; load(); });
  document.getElementById('page-prev').addEventListener('click', () => { if (state.page > 1) { state.page--; load(); } });
  document.getElementById('page-next').addEventListener('click', () => { if (state.page < state.pages) { state.page++; load(); } });

  document.querySelectorAll('.source-tab-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetSource = btn.dataset.source || 'ALL';
      if (state.source === targetSource) return;
      state.source = targetSource;
      state.page = 1;

      document.querySelectorAll('.source-tab-btn').forEach((b) => {
        if (b.dataset.source === targetSource) {
          b.className = 'source-tab-btn flex items-center gap-1.5 px-4 py-2 rounded-xl font-label-md text-label-md transition-all bg-primary text-on-primary shadow-sm';
        } else {
          b.className = 'source-tab-btn flex items-center gap-1.5 px-4 py-2 rounded-xl font-label-md text-label-md transition-all text-on-surface-variant hover:bg-surface-container';
        }
      });

      load();
    });
  });

  async function fillFilters() {
    const fill = async (name, route, label) => {
      const result = await CIM.api(route);
      for (const item of result.items) form.elements[name].add(new Option(label(item), item.id));
    };
    try {
      await Promise.all([
        fill('area_id', 'areas', (a) => a.name),
        fill('camera_id', 'cameras', (c) => `${c.code} - ${c.name}`),
        fill('type_id', 'violation-types', (t) => t.name),
      ]);
    } catch (error) {
      CIM.toast(`Không tải được bộ lọc: ${error.message}`, 'error');
    }
  }

  // ---------------------------------------------------------------- Real-time: SSE (Phase 2) & Polling (Phase 1)
  let eventSource = null;
  function updateSSEStatus(status, text) {
    const statusEl = document.getElementById('camera-sse-status');
    const dotEl = document.getElementById('camera-sse-dot');
    const textEl = document.getElementById('camera-sse-text');
    if (!statusEl || !dotEl || !textEl) return;

    if (status === 'online') {
      statusEl.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-[12px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 self-start sm:self-auto';
      dotEl.className = 'w-2 h-2 rounded-full bg-emerald-500 shrink-0';
      textEl.textContent = text || 'Camera AI: Trực tuyến (SSE Live)';
    } else if (status === 'offline') {
      statusEl.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-[12px] font-medium bg-amber-50 text-amber-800 border border-amber-200 self-start sm:self-auto';
      dotEl.className = 'w-2 h-2 rounded-full bg-amber-500 shrink-0 animate-pulse';
      textEl.textContent = text || 'Camera AI: Mất kết nối (Kiểm tra Port 8001)';
    } else {
      statusEl.className = 'flex items-center gap-2 px-3 py-1.5 rounded-full text-[12px] font-medium bg-surface-container text-on-surface-variant self-start sm:self-auto';
      dotEl.className = 'w-2 h-2 rounded-full bg-slate-400 shrink-0 animate-pulse';
      textEl.textContent = text || 'Đang kết nối Camera AI...';
    }
  }

  function connectSSE() {
    if (typeof EventSource === 'undefined') {
      updateSSEStatus('offline', 'Trình duyệt không hỗ trợ SSE');
      return;
    }
    try {
      const apiBase = document.querySelector('meta[name="cim-api-base-url"]')?.content || 'http://127.0.0.1:8001';
      const sseUrl = `${apiBase.replace(/\/+$/, '')}/api/v1/violations/stream`;
      updateSSEStatus('connecting');

      if (eventSource) {
        eventSource.close();
      }

      eventSource = new EventSource(sseUrl);

      eventSource.onopen = () => {
        updateSSEStatus('online', 'Camera AI: Trực tuyến (SSE Live)');
      };

      eventSource.onmessage = (event) => {
        try {
          const payload = JSON.parse(event.data);
          if (payload.event === 'new_violation' && payload.data) {
            const v = payload.data;
            const typeName = v.type?.name || 'Vi phạm tác phong';
            const areaName = v.area?.name || 'Khu vực';
            const camCode = v.camera?.code ? ` (${v.camera.code})` : '';
            CIM.toast(`🔔 Cảnh báo AI: Phát hiện "${typeName}" tại ${areaName}${camCode}!`, 'info');
            if (state.page === 1) {
              load(true);
            }
          }
        } catch (e) {
          // ignore keepalive
        }
      };

      eventSource.onerror = () => {
        updateSSEStatus('offline', 'Camera AI: Mất kết nối (Cần bật Port 8001)');
      };
    } catch (e) {
      updateSSEStatus('offline', 'Không thể kết nối Camera AI');
    }
  }

  let pollingTimer = null;
  function startPolling() {
    stopPolling();
    pollingTimer = setInterval(() => {
      if (state.page === 1 && !document.hidden) {
        load(true);
      }
    }, 10000);
  }

  function stopPolling() {
    if (pollingTimer) {
      clearInterval(pollingTimer);
      pollingTimer = null;
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopPolling();
    } else {
      if (state.page === 1) load(true);
      startPolling();
    }
  });

  // ---------------------------------------------------------------- Ghi nhận Biên bản Cờ Đỏ & Reset Vi phạm
  const redflagModal = document.getElementById('redflag-modal');
  const tabManualBtn = document.getElementById('tab-manual-btn');
  const tabExcelBtn = document.getElementById('tab-excel-btn');
  const manualForm = document.getElementById('manual-record-form');
  const excelTab = document.getElementById('excel-import-tab');
  const rfStudentCode = document.getElementById('rf-student-code');
  const rfStudentName = document.getElementById('rf-student-name');
  const rfLookupMsg = document.getElementById('rf-lookup-msg');
  const rfStudentCard = document.getElementById('rf-student-card');
  const rfTypeId = document.getElementById('rf-type-id');
  const rfAreaId = document.getElementById('rf-area-id');
  const rfDatetime = document.getElementById('rf-datetime');
  const rfRoom = document.getElementById('rf-room');
  const rfNote = document.getElementById('rf-note');

  let rfPreviewToken = '';
  let lookupTimer = null;

  function switchTab(mode) {
    if (mode === 'manual') {
      tabManualBtn.className = 'py-2.5 px-4 font-label-md text-label-md font-semibold text-primary border-b-2 border-primary flex items-center gap-1.5 transition-colors';
      tabExcelBtn.className = 'py-2.5 px-4 font-label-md text-label-md text-on-surface-variant hover:text-primary flex items-center gap-1.5 transition-colors';
      manualForm.classList.remove('hidden');
      manualForm.classList.add('flex');
      excelTab.classList.remove('flex');
      excelTab.classList.add('hidden');
    } else {
      tabExcelBtn.className = 'py-2.5 px-4 font-label-md text-label-md font-semibold text-primary border-b-2 border-primary flex items-center gap-1.5 transition-colors';
      tabManualBtn.className = 'py-2.5 px-4 font-label-md text-label-md text-on-surface-variant hover:text-primary flex items-center gap-1.5 transition-colors';
      excelTab.classList.remove('hidden');
      excelTab.classList.add('flex');
      manualForm.classList.remove('flex');
      manualForm.classList.add('hidden');
    }
  }

  async function loadRedflagMeta() {
    try {
      const resp = await fetch('api/students.php?action=violation-form-meta', { credentials: 'same-origin' });
      if (resp.ok) {
        const meta = await resp.json();
        if (rfTypeId && meta.types) {
          rfTypeId.replaceChildren(new Option('-- Chọn lỗi vi phạm --', ''),
            ...meta.types.map(t => new Option(`${t.TEN_LOI} (trừ ${t.DIEM_TRU}đ)`, t.ID)));
        }
        if (rfAreaId && meta.areas) {
          rfAreaId.replaceChildren(new Option('-- Chọn khu vực --', ''),
            ...meta.areas.map(a => new Option(a.TEN_KHU_VUC, a.ID)));
        }
      }
    } catch (e) {}
  }

  function openRedflagModal(tab = 'manual') {
    if (!redflagModal) return;
    redflagModal.classList.remove('hidden');
    redflagModal.classList.add('flex');
    switchTab(tab);
    loadRedflagMeta();

    // Reset default datetime to current local
    if (rfDatetime) {
      const now = new Date();
      const localIso = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
      rfDatetime.value = localIso;
    }
    if (tab === 'manual' && rfStudentCode) {
      setTimeout(() => rfStudentCode.focus(), 100);
    }
  }

  function closeRedflagModal() {
    if (!redflagModal) return;
    redflagModal.classList.remove('flex');
    redflagModal.classList.add('hidden');
    manualForm.reset();
    if (rfStudentCard) rfStudentCard.classList.add('hidden');
    if (rfLookupMsg) rfLookupMsg.classList.add('hidden');
    rfPreviewToken = '';
    const previewSec = document.getElementById('rf-preview-section');
    if (previewSec) previewSec.classList.add('hidden');
    const fileNameEl = document.getElementById('rf-file-name');
    if (fileNameEl) fileNameEl.textContent = 'chưa chọn';
  }

  tabManualBtn?.addEventListener('click', () => switchTab('manual'));
  tabExcelBtn?.addEventListener('click', () => switchTab('excel'));
  document.getElementById('open-manual-btn')?.addEventListener('click', () => openRedflagModal('manual'));
  document.getElementById('open-excel-btn')?.addEventListener('click', () => openRedflagModal('excel'));
  document.getElementById('close-redflag-modal-btn')?.addEventListener('click', closeRedflagModal);
  document.getElementById('cancel-manual-record-btn')?.addEventListener('click', closeRedflagModal);
  document.getElementById('cancel-excel-import-btn')?.addEventListener('click', closeRedflagModal);
  document.getElementById('redflag-backdrop')?.addEventListener('click', closeRedflagModal);

  // Auto lookup student by MSSV
  rfStudentCode?.addEventListener('input', () => {
    clearTimeout(lookupTimer);
    const code = rfStudentCode.value.trim();
    if (!code) {
      rfStudentName.value = '';
      rfStudentCard.classList.add('hidden');
      rfLookupMsg.classList.add('hidden');
      return;
    }

    lookupTimer = setTimeout(async () => {
      try {
        rfLookupMsg.textContent = 'Đang tìm kiếm sinh viên…';
        rfLookupMsg.className = 'font-body-sm text-[11px] text-primary';
        rfLookupMsg.classList.remove('hidden');

        const resp = await fetch(`api/students.php?action=lookup-student&ma_sv=${encodeURIComponent(code)}`, { credentials: 'same-origin' });
        const data = await resp.json();
        if (resp.ok && data.student) {
          const s = data.student;
          rfStudentName.value = s.HOTEN;
          rfLookupMsg.textContent = '✓ Đã xác nhận sinh viên hợp lệ.';
          rfLookupMsg.className = 'font-body-sm text-[11px] text-secondary font-medium';
          
          document.getElementById('rf-student-class').textContent = s.MA_LOP || '—';
          document.getElementById('rf-student-faculty').textContent = s.TEN_KHOA || '—';
          document.getElementById('rf-student-violation-count').textContent = s.SO_VI_PHAM ?? 0;
          rfStudentCard.classList.remove('hidden');
          rfStudentCard.classList.add('flex');
        } else {
          rfStudentName.value = '';
          rfLookupMsg.textContent = '✗ ' + (data.error || 'Không tìm thấy sinh viên có mã này trong hệ thống.');
          rfLookupMsg.className = 'font-body-sm text-[11px] text-error font-medium';
          rfStudentCard.classList.add('hidden');
        }
      } catch (err) {
        rfLookupMsg.textContent = 'Lỗi kết nối khi tra cứu.';
        rfLookupMsg.className = 'font-body-sm text-[11px] text-error';
      }
    }, 250);
  });

  // Submit manual record
  manualForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = rfStudentCode.value.trim();
    const name = rfStudentName.value.trim();
    const typeId = rfTypeId.value;
    const dt = rfDatetime.value;
    const areaId = rfAreaId.value;
    const room = rfRoom.value.trim();
    const note = rfNote.value.trim();

    if (!code || !name) {
      CIM.toast('Vui lòng nhập MSSV hợp lệ.', 'error');
      rfStudentCode.focus();
      return;
    }
    if (!typeId) {
      CIM.toast('Vui lòng chọn loại vi phạm.', 'error');
      rfTypeId.focus();
      return;
    }
    if (!room) {
      CIM.toast('Vui lòng nhập vị trí bị ghi nhận / phòng học.', 'error');
      rfRoom.focus();
      return;
    }

    const submitBtn = document.getElementById('submit-manual-record-btn');
    CIM.setBusy(submitBtn, true);

    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const resp = await fetch('api/students.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          action: 'add_violation',
          ma_sv: code,
          type_id: typeId,
          datetime: dt,
          area_id: areaId || null,
          room: room,
          note: note,
          confirm_duplicate: true,
        }),
      });

      const res = await resp.json();
      if (!resp.ok) {
        throw new Error(res.error || 'Không thể lưu biên bản vi phạm.');
      }

      CIM.toast(`Đã ghi nhận biên bản vi phạm cho sinh viên ${name} (${code})!`, 'success');
      closeRedflagModal();
      load();
    } catch (err) {
      CIM.toast(err.message, 'error');
    } finally {
      CIM.setBusy(submitBtn, false);
    }
  });

  // Excel template download
  document.getElementById('rf-download-template-btn')?.addEventListener('click', () => {
    window.location.assign('api/students.php?action=template&dataset=VIPHAM');
  });

  // Excel drag & drop / file preview
  const rfDropZone = document.getElementById('rf-drop-zone');
  const rfExcelFile = document.getElementById('rf-excel-file');
  rfDropZone?.addEventListener('click', () => rfExcelFile.click());
  rfDropZone?.addEventListener('dragover', (e) => e.preventDefault());
  rfDropZone?.addEventListener('drop', (e) => {
    e.preventDefault();
    if (e.dataTransfer.files[0]) handleExcelUpload(e.dataTransfer.files[0]);
  });
  rfExcelFile?.addEventListener('change', (e) => {
    if (e.target.files[0]) handleExcelUpload(e.target.files[0]);
  });

  async function handleExcelUpload(file) {
    if (!file) return;
    document.getElementById('rf-file-name').textContent = file.name;
    const form = new FormData();
    form.append('dataset', 'VIPHAM');
    form.append('file', file);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
      const resp = await fetch('api/students.php', {
        method: 'POST',
        headers: { 'X-CSRF-Token': csrf },
        credentials: 'same-origin',
        body: form,
      });
      const data = await resp.json();
      if (!resp.ok) throw new Error(data.error || 'Lỗi đọc file Excel.');

      renderExcelPreview(data);
    } catch (err) {
      CIM.toast(err.message, 'error');
      document.getElementById('rf-preview-section')?.classList.add('hidden');
    }
  }

  function renderExcelPreview(data) {
    const tbody = document.getElementById('rf-preview-body');
    tbody.replaceChildren();

    for (const item of data.rows) {
      const tr = CIM.el('tr', item.status === 'error' ? 'h-9 bg-error-container/20' : item.status === 'duplicate' ? 'h-9 bg-amber-50' : 'h-9 bg-secondary-container/10');
      tr.append(
        CIM.el('td', 'px-3 py-1.5 text-center font-data-mono', item.row),
        CIM.el('td', 'px-3 py-1.5 font-data-mono font-semibold text-primary', item.values.MA_SV || '—'),
        CIM.el('td', 'px-3 py-1.5', item.values.HOTEN || '—'),
        CIM.el('td', 'px-3 py-1.5', item.values.VI_TRI || '—'),
        CIM.el('td', 'px-3 py-1.5 whitespace-nowrap', item.values.THOI_DIEM || '—'),
        CIM.el('td', 'px-3 py-1.5', item.values.TEN_LOI || '—')
      );

      const statusTd = CIM.el('td', 'px-3 py-1.5 text-right font-medium');
      if (item.status === 'error') {
        statusTd.className += ' text-error';
        statusTd.textContent = 'Lỗi: ' + item.errors.join('; ');
      } else if (item.status === 'duplicate') {
        statusTd.className += ' text-amber-700';
        statusTd.textContent = 'Trùng (bỏ qua)';
      } else {
        statusTd.className += ' text-secondary';
        statusTd.textContent = 'Hợp lệ';
      }
      tr.append(statusTd);
      tbody.append(tr);
    }

    const countsText = `${data.counts.valid} hợp lệ / ${data.counts.duplicate} trùng / ${data.counts.error} lỗi`;
    document.getElementById('rf-preview-counts').textContent = countsText;
    document.getElementById('rf-preview-section').classList.remove('hidden');

    const commitBtn = document.getElementById('rf-commit-import-btn');
    const commitLabel = document.getElementById('rf-commit-label');
    commitBtn.disabled = !data.can_commit || data.counts.valid === 0;
    commitLabel.textContent = data.can_commit ? `Xác nhận nhập ${data.counts.valid} biên bản` : 'Sửa lỗi trước khi nhập';
    rfPreviewToken = data.preview_token;
  }

  // Commit Excel import
  document.getElementById('rf-commit-import-btn')?.addEventListener('click', async () => {
    if (!rfPreviewToken) return;
    const btn = document.getElementById('rf-commit-import-btn');
    CIM.setBusy(btn, true);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
      const resp = await fetch('api/students.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'import-commit', preview_token: rfPreviewToken }),
      });
      const res = await resp.json();
      if (!resp.ok) throw new Error(res.error || 'Lỗi lưu dữ liệu biên bản.');

      CIM.toast(`Đã nhập thành công ${res.inserted} biên bản vi phạm vào CSDL!`, 'success');
      closeRedflagModal();
      load();
    } catch (err) {
      CIM.toast(err.message, 'error');
    } finally {
      CIM.setBusy(btn, false);
    }
  });

  // ---------------------------------------------------------------- Reset Vi Phạm Toàn Trường
  const resetModal = document.getElementById('reset-violations-modal');
  const resetForm = document.getElementById('reset-violations-form');
  const resetInput = document.getElementById('reset-password-input');
  const resetError = document.getElementById('reset-password-error');

  function openResetModal() {
    if (!resetModal) return;
    resetModal.classList.remove('hidden');
    resetModal.classList.add('flex');
    if (resetInput) {
      resetInput.value = '';
      setTimeout(() => resetInput.focus(), 100);
    }
    if (resetError) resetError.classList.add('hidden');
  }

  function closeResetModal() {
    if (!resetModal) return;
    resetModal.classList.remove('flex');
    resetModal.classList.add('hidden');
    if (resetInput) resetInput.value = '';
    if (resetError) resetError.classList.add('hidden');
  }

  document.getElementById('open-reset-btn')?.addEventListener('click', openResetModal);
  document.getElementById('close-reset-modal-btn')?.addEventListener('click', closeResetModal);
  document.getElementById('cancel-reset-btn')?.addEventListener('click', closeResetModal);
  document.getElementById('reset-modal-backdrop')?.addEventListener('click', closeResetModal);

  document.getElementById('toggle-reset-password-btn')?.addEventListener('click', () => {
    if (!resetInput) return;
    const isPass = resetInput.type === 'password';
    resetInput.type = isPass ? 'text' : 'password';
    const icon = document.getElementById('toggle-reset-password-icon');
    if (icon) icon.textContent = isPass ? 'visibility_off' : 'visibility';
  });

  resetForm?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pass = resetInput?.value || '';
    if (!pass) {
      resetError.textContent = 'Vui lòng nhập mật mã xác nhận.';
      resetError.classList.remove('hidden');
      return;
    }

    const confirmBtn = document.getElementById('confirm-reset-btn');
    CIM.setBusy(confirmBtn, true);
    resetError.classList.add('hidden');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    try {
      const includeCamera = document.getElementById('reset-include-camera-check')?.checked || false;
      const resp = await fetch('api/students.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'reset-violations', reset_password: pass, include_camera_records: includeCamera }),
      });
      const data = await resp.json();
      if (!resp.ok) {
        throw new Error(data.error || 'Mật mã bảo mật không chính xác.');
      }

      if (includeCamera) {
        CIM.toast(`Đã xóa ${data.deleted_student_violations} biên bản và ${data.deleted_camera_records} cảnh báo Camera AI. Toàn bộ vi phạm đã được đưa về 0!`, 'success');
      } else {
        CIM.toast(`Đã xóa ${data.deleted_student_violations} biên bản sinh viên vi phạm về 0! (Giữ nguyên nhật ký Camera AI)`, 'success');
      }
      closeResetModal();
      load();
    } catch (err) {
      resetError.textContent = err.message;
      resetError.classList.remove('hidden');
    } finally {
      CIM.setBusy(confirmBtn, false);
    }
  });

  fillFilters();
  load();
  connectSSE();
  startPolling();
})();
