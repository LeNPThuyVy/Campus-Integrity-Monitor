(() => {
  'use strict';

  const form = document.getElementById('violation-filters');
  const tbody = document.getElementById('violation-body');
  const dialog = document.getElementById('violation-dialog');
  const state = { page: 1, pages: 1, current: null };
  const newBar = document.getElementById('new-violations-bar');
  let pendingNew = 0;
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

  function params() {
    const data = Object.fromEntries(new FormData(form).entries());
    return { ...data, page: state.page, page_size: document.getElementById('page-size').value };
  }

  async function load({ quiet = false } = {}) {
    newBar.classList.add('hidden');
    pendingNew = 0;
    if (!quiet) CIM.stateRow(tbody, 7, 'Đang tải bản ghi…', 'loading');
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
      tbody.replaceChildren(...result.items.map(renderRow));
    } catch (error) {
      CIM.stateRow(tbody, 7, error.message, 'error');
    }
  }

  function renderRow(item) {
    const row = CIM.el('tr', 'border-t border-surface-container hover:bg-surface-container-low/60');
    row.append(CIM.el('td', 'px-space-md py-3 font-data-mono', `#${item.id}`));
    row.append(CIM.el('td', 'px-space-md py-3 whitespace-nowrap', CIM.formatDateTime(item.occurred_at)));
    row.append(CIM.el('td', 'px-space-md py-3', item.type.name));
    const place = CIM.el('td', 'px-space-md py-3');
    place.append(CIM.el('div', 'font-semibold', item.area.name), CIM.el('div', 'text-on-surface-variant', `${item.camera.code} - ${item.camera.name}`));
    row.append(place);
    const evidenceCell = CIM.el('td', 'px-space-md py-3');
    evidenceCell.append(badge(...(EVIDENCE_BADGES[item.evidence.status] || EVIDENCE_BADGES.none)));
    row.append(evidenceCell);
    row.append(CIM.el('td', 'px-space-md py-3', CIM.PROCESSING_LABELS[item.processing.status] || item.processing.status));
    const actions = CIM.el('td', 'px-space-md py-3 text-right');
    const view = CIM.el('button', 'rounded-lg bg-surface-container px-3 py-1.5 font-label-md', 'Chi tiết');
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
      document.getElementById('detail-title').textContent = `Bản ghi vi phạm #${data.id}`;
      const percent = (v) => (v === null || v === undefined ? '—' : `${(v * 100).toFixed(1)}%`);
      document.getElementById('detail-fields').replaceChildren(
        ...field('Thời điểm', CIM.formatDateTime(data.occurred_at)),
        ...field('Loại vi phạm', data.type.name),
        ...field('Khu vực', data.area.name),
        ...field('Camera', `${data.camera.code} - ${data.camera.name}`),
        ...field('Độ tin cậy ĐP', percent(data.uniform_confidence)),
        ...field('Độ tin cậy thẻ', percent(data.card_confidence)),
        ...field('Ảnh hết hạn', CIM.formatDateTime(data.evidence.expires_at)),
        ...field('Người xử lý', data.processing.processed_by ? `${data.processing.processed_by} (${CIM.formatDateTime(data.processing.processed_at)})` : '—'),
      );
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
    if (status === 'BO_QUA' && !(await CIM.confirmAction('Đánh dấu bản ghi là cảnh báo sai? Bản ghi vẫn được lưu cho thống kê.'))) return;
    CIM.setBusy(button, true);
    try {
      const { data } = await CIM.api('violation', {
        method: 'PATCH', params: { id: state.current.id }, body: { status, note: target.elements.note.value },
      });
      state.current = data;
      CIM.toast('Đã cập nhật trạng thái xử lý.');
      dialog.close();
      load();
      window.dispatchEvent(new CustomEvent('cim:violations-changed'));
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

  // Bell (notifications.js) found new violations: refresh in place only when that cannot move rows under the user.
  window.addEventListener('cim:new-violations', (event) => {
    if (state.page === 1 && form.elements.sort.value === 'newest' && !dialog.open) {
      load({ quiet: true });
      return;
    }
    pendingNew += event.detail?.count || 1;
    document.getElementById('new-violations-text').textContent = `Có ${pendingNew} vi phạm mới từ camera.`;
    newBar.classList.remove('hidden');
  });
  document.getElementById('new-violations-reload').addEventListener('click', () => { state.page = 1; load(); });

  // Deletes AI detection records + images only; student violation details (CT_VIPHAM) stay.
  document.getElementById('delete-all-detections').addEventListener('click', async (event) => {
    const button = event.currentTarget;
    const ok = await CIM.confirmAction(
      'Xóa TẤT CẢ bản ghi phát hiện vi phạm và ảnh bằng chứng, kể cả bản ghi Mới chưa xem. '
      + 'Chi tiết vi phạm đã ghi cho sinh viên KHÔNG bị xóa. Thao tác này không hoàn tác được.',
      { title: 'Xóa toàn bộ bản ghi phát hiện?', okText: 'Xóa toàn bộ', danger: true },
    );
    if (!ok) return;
    button.disabled = true; // not CIM.setBusy: it would flatten the icon into text
    button.classList.add('opacity-60', 'cursor-wait');
    try {
      const { data } = await CIM.api('violations', { method: 'DELETE' });
      CIM.toast(`Đã xóa ${data.deleted.toLocaleString('vi-VN')} bản ghi phát hiện.`,
        data.errors ? 'error' : 'success');
      state.page = 1;
      load();
      window.dispatchEvent(new CustomEvent('cim:violations-changed'));
    } catch (error) {
      CIM.toast(error.message, 'error');
    } finally {
      button.disabled = false;
      button.classList.remove('opacity-60', 'cursor-wait');
    }
  });

  // Links from the bell: list_vipham.php?status=MOI&id=123
  const query = new URLSearchParams(window.location.search);
  if (['MOI', 'DA_XEM', 'DA_XU_LY', 'BO_QUA'].includes(query.get('status'))) form.elements.status.value = query.get('status');
  const linkedId = Number(query.get('id'));

  fillFilters();
  load();
  if (Number.isInteger(linkedId) && linkedId > 0) openDetail(linkedId);
})();
