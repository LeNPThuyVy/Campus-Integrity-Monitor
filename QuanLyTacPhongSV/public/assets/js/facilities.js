(() => {
  'use strict';

  const state = { areas: [], cameras: [], semesters: [] };
  const statusBadge = (active) => CIM.el('span',
    `rounded-full px-2 py-0.5 font-label-sm text-label-sm ${active ? 'bg-secondary-container text-on-secondary-container' : 'bg-surface-container text-on-surface-variant'}`,
    active ? 'Đang sử dụng' : 'Ngừng sử dụng');

  // ------------------------------------------------------------------ tabs
  const tabs = document.querySelectorAll('[data-tab]');
  function showTab(name) {
    tabs.forEach((tab) => {
      const active = tab.dataset.tab === name;
      tab.classList.toggle('bg-surface-container-lowest', active);
      tab.classList.toggle('shadow-sm', active);
      tab.setAttribute('aria-selected', String(active));
    });
    document.querySelectorAll('[data-panel]').forEach((panel) => {
      const active = panel.dataset.panel === name;
      panel.classList.toggle('hidden', !active);
      panel.classList.toggle('grid', active);
    });
  }
  tabs.forEach((tab) => tab.addEventListener('click', () => showTab(tab.dataset.tab)));
  showTab('areas');

  // ------------------------------------------------------------------ generic form helpers
  function setupForm(formId, onSubmit, titles) {
    const form = document.getElementById(formId);
    if (!form) return null;
    const cancel = form.querySelector('[data-cancel]');
    const title = form.querySelector('[data-title]');
    form.addEventListener('reset', () => setTimeout(() => {
      form.elements.id.value = '';
      title.textContent = titles.create;
      cancel.classList.add('hidden');
    }, 0));
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const button = form.querySelector('button[type="submit"]');
      CIM.setBusy(button, true);
      try {
        await onSubmit(form, form.elements.id.value || null);
        form.reset();
      } catch (error) {
        CIM.toast(error.message, 'error');
      } finally {
        CIM.setBusy(button, false);
      }
    });
    return {
      edit(values) {
        for (const [key, value] of Object.entries(values)) {
          const input = form.elements[key];
          if (!input) continue;
          if (input.type === 'checkbox') input.checked = Boolean(value); else input.value = value ?? '';
        }
        title.textContent = titles.edit;
        cancel.classList.remove('hidden');
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
      },
    };
  }

  function actionButton(label, handler, className = 'bg-surface-container') {
    const button = CIM.el('button', `ml-1 rounded-lg px-3 py-1.5 font-label-md ${className}`, label);
    button.type = 'button';
    button.addEventListener('click', handler);
    return button;
  }

  function showKey(cameraCode, key) {
    document.getElementById('key-camera').textContent = cameraCode;
    document.getElementById('key-value').textContent = key;
    document.getElementById('key-dialog').showModal();
  }
  document.getElementById('key-copy').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(document.getElementById('key-value').textContent);
      CIM.toast('Đã sao chép khóa thiết bị.');
    } catch {
      CIM.toast('Trình duyệt không cho phép sao chép; hãy chọn và sao chép thủ công.', 'error');
    }
  });

  // ------------------------------------------------------------------ areas
  const areaForm = setupForm('area-form', async (form, id) => {
    const body = { code: form.elements.code.value, name: form.elements.name.value,
      description: form.elements.description.value || null, active: form.elements.active.checked };
    if (id && !body.active && !(await CIM.confirmAction(
      'Ngừng sử dụng khu vực này? Camera thuộc khu vực sẽ không ghi nhận vi phạm mới; dữ liệu cũ vẫn được giữ.',
      { danger: true }))) return;
    await CIM.api(id ? 'area' : 'areas', { method: id ? 'PUT' : 'POST', params: id ? { id } : {}, body });
    CIM.toast(id ? 'Đã cập nhật khu vực.' : 'Đã thêm khu vực.');
    await loadAll();
  }, { create: 'Thêm khu vực', edit: 'Sửa khu vực' });

  function renderAreas() {
    const tbody = document.getElementById('area-body');
    if (!state.areas.length) { CIM.stateRow(tbody, 6, 'Chưa có khu vực. Quản trị viên cần khai báo khu vực trước khi thêm camera.'); return; }
    tbody.replaceChildren(...state.areas.map((area) => {
      const row = CIM.el('tr', 'border-t border-surface-container');
      row.append(CIM.el('td', 'px-space-md py-3 font-data-mono', area.code), CIM.el('td', 'px-space-md py-3', area.name),
        CIM.el('td', 'px-space-md py-3 text-on-surface-variant', area.description || '—'),
        CIM.el('td', 'px-space-md py-3', area.camera_count));
      const status = CIM.el('td', 'px-space-md py-3');
      status.append(statusBadge(area.active));
      const actions = CIM.el('td', 'px-space-md py-3 text-right whitespace-nowrap');
      if (areaForm) actions.append(actionButton('Sửa', () => areaForm.edit(area)));
      else actions.textContent = 'Chỉ xem';
      row.append(status, actions);
      return row;
    }));
  }

  // ------------------------------------------------------------------ cameras
  const cameraForm = setupForm('camera-form', async (form, id) => {
    const body = { code: form.elements.code.value, name: form.elements.name.value,
      area_id: Number(form.elements.area_id.value), location: form.elements.location.value || null,
      stream_source: form.elements.stream_source.value || null, active: form.elements.active.checked };
    if (id && !body.active && !(await CIM.confirmAction(
      'Ngừng sử dụng camera này? Desktop dùng camera này sẽ bị từ chối khi gửi vi phạm mới.', { danger: true }))) return;
    const result = await CIM.api(id ? 'camera' : 'cameras', { method: id ? 'PUT' : 'POST', params: id ? { id } : {}, body });
    CIM.toast(id ? 'Đã cập nhật camera.' : 'Đã thêm camera.');
    if (!id && result.device_key) showKey(result.data.code, result.device_key);
    await loadAll();
  }, { create: 'Thêm camera', edit: 'Sửa camera' });

  function renderCameras() {
    const tbody = document.getElementById('camera-body');
    if (!state.cameras.length) { CIM.stateRow(tbody, 6, 'Chưa có camera.'); return; }
    tbody.replaceChildren(...state.cameras.map((camera) => {
      const row = CIM.el('tr', 'border-t border-surface-container');
      const name = CIM.el('td', 'px-space-md py-3');
      name.append(CIM.el('div', 'font-semibold', camera.name), CIM.el('div', 'text-on-surface-variant', camera.location || '—'));
      row.append(CIM.el('td', 'px-space-md py-3 font-data-mono', camera.code), name,
        CIM.el('td', 'px-space-md py-3', camera.area.name),
        CIM.el('td', 'px-space-md py-3', camera.last_seen_at ? CIM.formatDateTime(camera.last_seen_at) : 'Chưa kết nối'));
      const status = CIM.el('td', 'px-space-md py-3');
      status.append(statusBadge(camera.active));
      const actions = CIM.el('td', 'px-space-md py-3 text-right whitespace-nowrap');
      if (cameraForm) {
        actions.append(actionButton('Sửa', () => cameraForm.edit({ ...camera, area_id: camera.area.id })));
        actions.append(actionButton('Cấp lại khóa', async () => {
          if (!(await CIM.confirmAction(`Cấp khóa mới cho ${camera.code}? Khóa cũ trên Desktop sẽ mất hiệu lực ngay.`, { danger: true }))) return;
          try {
            const result = await CIM.api('camera-key', { method: 'POST', params: { id: camera.id } });
            showKey(camera.code, result.device_key);
          } catch (error) { CIM.toast(error.message, 'error'); }
        }, 'bg-tertiary-container text-on-tertiary-container'));
      } else {
        actions.textContent = 'Chỉ xem';
      }
      row.append(status, actions);
      return row;
    }));
  }

  // Kept separate from renderCameras: the first camera can only be created once this list is filled.
  function renderAreaChoices() {
    const areaSelect = document.getElementById('camera-form')?.elements.area_id;
    if (!areaSelect) return;
    const selected = areaSelect.value;
    areaSelect.replaceChildren(new Option('-- Chọn khu vực --', ''),
      ...state.areas.map((a) => new Option(`${a.code} - ${a.name}${a.active ? '' : ' (ngừng)'}`, a.id)));
    areaSelect.value = selected;
  }

  // ------------------------------------------------------------------ semesters
  const semesterForm = setupForm('semester-form', async (form, id) => {
    const body = { code: form.elements.code.value, name: form.elements.name.value,
      start_date: form.elements.start_date.value, end_date: form.elements.end_date.value };
    await CIM.api(id ? 'semester' : 'semesters', { method: id ? 'PUT' : 'POST', params: id ? { id } : {}, body });
    CIM.toast(id ? 'Đã cập nhật học kỳ.' : 'Đã thêm học kỳ.');
    await loadAll();
  }, { create: 'Thêm học kỳ', edit: 'Sửa học kỳ' });

  function renderSemesters() {
    const tbody = document.getElementById('semester-body');
    if (!state.semesters.length) { CIM.stateRow(tbody, 5, 'Chưa khai báo học kỳ. Báo cáo theo học kỳ cần ít nhất một học kỳ.'); return; }
    tbody.replaceChildren(...state.semesters.map((semester) => {
      const row = CIM.el('tr', 'border-t border-surface-container');
      row.append(CIM.el('td', 'px-space-md py-3 font-data-mono', semester.code), CIM.el('td', 'px-space-md py-3', semester.name),
        CIM.el('td', 'px-space-md py-3', CIM.formatDate(semester.start_date)), CIM.el('td', 'px-space-md py-3', CIM.formatDate(semester.end_date)));
      const actions = CIM.el('td', 'px-space-md py-3 text-right whitespace-nowrap');
      if (semesterForm) {
        actions.append(actionButton('Sửa', () => semesterForm.edit(semester)));
        actions.append(actionButton('Xóa', async () => {
          if (!(await CIM.confirmAction(`Xóa học kỳ ${semester.code}? Bản ghi vi phạm không bị ảnh hưởng.`, { danger: true, okText: 'Xóa' }))) return;
          try {
            await CIM.api('semester', { method: 'DELETE', params: { id: semester.id } });
            CIM.toast('Đã xóa học kỳ.');
            await loadAll();
          } catch (error) { CIM.toast(error.message, 'error'); }
        }, 'bg-error-container text-on-error-container'));
      } else {
        actions.textContent = 'Chỉ xem';
      }
      row.append(actions);
      return row;
    }));
  }

  async function loadAll() {
    for (const id of ['area-body', 'camera-body']) CIM.stateRow(document.getElementById(id), 6, 'Đang tải…', 'loading');
    CIM.stateRow(document.getElementById('semester-body'), 5, 'Đang tải…', 'loading');
    try {
      const [areas, cameras, semesters] = await Promise.all([CIM.api('areas'), CIM.api('cameras'), CIM.api('semesters')]);
      state.areas = areas.items;
      state.cameras = cameras.items;
      state.semesters = semesters.items;
      renderAreas();
      renderAreaChoices();
      renderCameras();
      renderSemesters();
    } catch (error) {
      for (const id of ['area-body', 'camera-body']) CIM.stateRow(document.getElementById(id), 6, error.message, 'error');
      CIM.stateRow(document.getElementById('semester-body'), 5, error.message, 'error');
    }
  }

  loadAll();
})();
