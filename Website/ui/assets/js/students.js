(() => {
  'use strict';

  const endpoint = 'api/students.php';
  const page = document.getElementById('student-module');
  if (!page) return;

  const state = {
    csrfToken: '',
    catalog: { faculties: [], majors: [], classes: [] },
    page: 1,
    pageSize: 10,
    previewToken: '',
    previewDataset: '',
    editId: null,
    drawerMode: 'edit',
  };

  const byId = (id) => document.getElementById(id);
  const status = byId('student-status');

  function showStatus(message, isError = false) {
    if (!status) return;
    status.textContent = message;
    status.classList.toggle('hidden', !message);
    status.classList.toggle('text-error', isError);
    status.classList.toggle('text-secondary', !isError);
  }

  async function request(url, options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.body && !(options.body instanceof FormData)) {
      headers.set('Content-Type', 'application/json');
    }
    if (options.method && options.method !== 'GET' && state.csrfToken) {
      headers.set('X-CSRF-Token', state.csrfToken);
    }
    const response = await fetch(url, {
      ...options,
      headers,
      credentials: 'same-origin',
    });
    const contentType = response.headers.get('content-type') || '';
    const body = contentType.includes('application/json') ? await response.json() : null;
    if (!response.ok) {
      if (response.status === 401) {
        window.location.assign('login.php');
      }
      const error = new Error(body?.error || 'Không thể hoàn tất yêu cầu.');
      error.status = response.status;
      error.code = body?.code;
      throw error;
    }
    return body;
  }

  function addOptions(select, items, valueKey, labelFn, placeholder) {
    if (!select) return;
    const selected = select.value;
    select.replaceChildren(new Option(placeholder, ''));
    for (const item of items) {
      select.add(new Option(labelFn(item), item[valueKey]));
    }
    if ([...select.options].some((option) => option.value === selected)) {
      select.value = selected;
    }
  }

  function renderCatalog() {
    addOptions(byId('filter-khoa'), state.catalog.faculties, 'MA_KHOA',
      (row) => `${row.MA_KHOA} - ${row.TEN_KHOA}`, '-- Tất cả Khoa --');
    addOptions(byId('filter-nganh'), state.catalog.majors, 'MA_NGANH',
      (row) => `${row.MA_NGANH} - ${row.TEN_NGANH}`, '-- Tất cả Ngành --');
    addOptions(byId('filter-lop'), state.catalog.classes, 'MA_LOP',
      (row) => row.MA_LOP, '-- Tất cả Lớp --');
    addOptions(byId('input-khoa'), state.catalog.faculties, 'MA_KHOA',
      (row) => `${row.MA_KHOA} - ${row.TEN_KHOA}`, 'Chọn khoa');
    refreshMajorChoices();
    refreshClassChoices();
  }

  function refreshMajorChoices() {
    const facultyCode = byId('input-khoa')?.value || '';
    const majors = state.catalog.majors.filter((item) => !facultyCode || item.MA_KHOA === facultyCode);
    addOptions(byId('input-nganh'), majors, 'MA_NGANH',
      (row) => `${row.MA_NGANH} - ${row.TEN_NGANH}`, 'Chọn ngành');
  }

  function refreshClassChoices() {
    const majorCode = byId('input-nganh')?.value || '';
    const facultyCode = byId('input-khoa')?.value || '';
    const classes = state.catalog.classes.filter((item) =>
      (!majorCode || item.MA_NGANH === majorCode) &&
      (!facultyCode || item.MA_KHOA === facultyCode));
    addOptions(byId('input-lop'), classes, 'MA_LOP', (row) => row.MA_LOP, 'Chọn lớp');
  }

  function makeCell(text, className = '') {
    const cell = document.createElement('td');
    cell.className = className;
    cell.textContent = text == null ? '' : String(text);
    return cell;
  }

  function actionButton(icon, title, action, className) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = className;
    button.title = title;
    button.dataset.action = action;
    const symbol = document.createElement('span');
    symbol.className = 'material-symbols-outlined text-[18px]';
    symbol.textContent = icon;
    button.append(symbol);
    return button;
  }

  function renderRows(result) {
    const body = byId('student-table-body');
    body.replaceChildren();
    result.rows.forEach((student, index) => {
      const row = document.createElement('tr');
      row.className = 'h-12 hover:bg-surface-container-low/70 transition-colors group';
      row.dataset.id = student.ID;
      row.append(makeCell(String((result.page - 1) * result.page_size + index + 1).padStart(2, '0'),
        'px-space-md text-center font-data-mono text-outline'));

      const codeCell = document.createElement('td');
      codeCell.className = 'px-space-md font-data-mono font-semibold text-primary';
      const code = document.createElement('button');
      code.type = 'button';
      code.className = 'hover:underline';
      code.dataset.action = 'detail';
      code.textContent = student.MA_SV;
      codeCell.append(code);
      row.append(codeCell);
      row.append(makeCell(student.HOTEN, 'px-space-md'));
      row.append(makeCell(student.MA_LOP, 'px-space-md font-label-md text-label-md'));
      row.append(makeCell(`${student.MA_KHOA} - ${student.TEN_KHOA}`,
        'px-space-md text-on-surface-variant truncate'));
      row.append(makeCell(String(student.NIEN_KHOA), 'px-space-md font-data-mono text-on-surface-variant'));

      const violations = document.createElement('td');
      violations.className = 'px-space-md';
      const badge = document.createElement('span');
      badge.className = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-surface-container-low text-on-surface-variant';
      const number = Number(student.SO_VI_PHAM);
      badge.textContent = `${number} lượt vi phạm`;
      violations.append(badge);
      row.append(violations);

      const actions = document.createElement('td');
      actions.className = 'px-space-md text-right';
      const group = document.createElement('div');
      group.className = 'flex items-center justify-end gap-1 text-on-surface-variant';
      group.append(
        actionButton('visibility', 'Xem chi tiết', 'detail',
          'p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors'),
        actionButton('edit', 'Chỉnh sửa', 'edit',
          'p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors'),
        actionButton('delete', 'Xóa hồ sơ', 'delete',
          'p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors'),
      );
      actions.append(group);
      row.append(actions);
      body.append(row);
    });
    if (!result.rows.length) {
      const empty = document.createElement('tr');
      const message = makeCell('Không có sinh viên phù hợp.', 'px-space-md py-8 text-center text-on-surface-variant');
      message.colSpan = 8;
      empty.append(message);
      body.append(empty);
    }

    const start = result.total === 0 ? 0 : (result.page - 1) * result.page_size + 1;
    const end = Math.min(result.total, result.page * result.page_size);
    byId('student-range').textContent = `${start} - ${end}`;
    byId('student-total').textContent = Number(result.total).toLocaleString('vi-VN');
    byId('student-page-buttons').replaceChildren();
    const prev = pageButton('chevron_left', 'Trang trước', result.page <= 1 ? null : result.page - 1);
    byId('student-page-buttons').append(prev);
    const first = Math.max(1, result.page - 2);
    const last = Math.min(result.pages, result.page + 2);
    for (let number = first; number <= last; number++) {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = number === result.page
        ? 'w-8 h-8 rounded-lg bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold shadow-xs'
        : 'w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container font-label-sm text-label-sm transition-colors';
      button.textContent = String(number);
      button.addEventListener('click', () => loadStudents(number));
      byId('student-page-buttons').append(button);
    }
    byId('student-page-buttons').append(
      pageButton('chevron_right', 'Trang sau', result.page >= result.pages ? null : result.page + 1),
    );
  }

  function pageButton(icon, title, targetPage) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container disabled:opacity-40 transition-colors';
    button.title = title;
    button.disabled = targetPage === null;
    const symbol = document.createElement('span');
    symbol.className = 'material-symbols-outlined text-[18px]';
    symbol.textContent = icon;
    button.append(symbol);
    if (targetPage !== null) button.addEventListener('click', () => loadStudents(targetPage));
    return button;
  }

  function filters() {
    return new URLSearchParams({
      action: 'list',
      page: String(state.page),
      page_size: String(state.pageSize),
      search: byId('student-search').value.trim(),
      faculty: byId('filter-khoa').value,
      major: byId('filter-nganh').value,
      class: byId('filter-lop').value,
    });
  }

  async function loadStudents(pageNumber = 1) {
    state.page = pageNumber;
    try {
      const result = await request(`${endpoint}?${filters().toString()}`);
      renderRows(result);
      showStatus('');
    } catch (error) {
      showStatus(error.message, true);
    }
  }

  function setFormValue(id, value) {
    const input = byId(id);
    if (input) input.value = value == null ? '' : String(value);
  }

  function setReadOnly(readOnly) {
    ['input-mssv', 'input-name', 'input-khoa', 'input-nganh', 'input-lop', 'input-khoa-hoc']
      .forEach((id) => { if (byId(id)) byId(id).disabled = readOnly; });
    const save = document.querySelector('[form="student-form"][type="submit"]');
    if (save) save.classList.toggle('hidden', readOnly);
  }

  function openDrawer(mode, student = null) {
    state.drawerMode = mode;
    state.editId = student?.ID ?? null;
    const drawer = byId('student-drawer');
    byId('drawer-title').textContent = mode === 'create'
      ? 'Thêm mới sinh viên'
      : mode === 'view' ? `Chi tiết sinh viên: ${student.MA_SV}` : `Chỉnh sửa sinh viên: ${student.MA_SV}`;
    drawer.classList.remove('pointer-events-none');
    byId('drawer-backdrop').classList.remove('pointer-events-none', 'opacity-0');
    byId('drawer-backdrop').classList.add('opacity-100');
    byId('drawer-panel').classList.remove('translate-x-full');
    byId('drawer-panel').classList.add('translate-x-0');

    if (mode === 'create') {
      byId('student-form').reset();
      state.editId = null;
      refreshMajorChoices();
      refreshClassChoices();
    } else {
      setFormValue('input-mssv', student.MA_SV);
      setFormValue('input-name', student.HOTEN);
      setFormValue('input-khoa-hoc', student.NIEN_KHOA);
      setFormValue('input-khoa', student.MA_KHOA);
      refreshMajorChoices();
      setFormValue('input-nganh', student.MA_NGANH);
      refreshClassChoices();
      setFormValue('input-lop', student.MA_LOP);
    }
    setReadOnly(mode === 'view');
    window.CIMStudentViolations?.show(mode === 'create' ? null : student);
  }

  function closeDrawer() {
    byId('drawer-panel').classList.remove('translate-x-0');
    byId('drawer-panel').classList.add('translate-x-full');
    byId('drawer-backdrop').classList.remove('opacity-100');
    byId('drawer-backdrop').classList.add('opacity-0');
    setTimeout(() => {
      byId('student-drawer').classList.add('pointer-events-none');
      byId('drawer-backdrop').classList.add('pointer-events-none');
      setReadOnly(false);
      window.CIMStudentViolations?.show(null);
    }, 300);
  }

  async function openStudent(id, mode) {
    try {
      const result = await request(`${endpoint}?action=detail&id=${encodeURIComponent(id)}`);
      openDrawer(mode, result.student);
    } catch (error) {
      showStatus(error.message, true);
    }
  }

  async function handleStudentSubmit(event) {
    event.preventDefault();
    const student = {
      ma_sv: byId('input-mssv').value.trim(),
      ho_ten: byId('input-name').value.trim(),
      nien_khoa: byId('input-khoa-hoc').value,
      ma_khoa: byId('input-khoa').value,
      ma_nganh: byId('input-nganh').value,
      ma_lop: byId('input-lop').value,
    };
    if (state.editId !== null) student.id = state.editId;
    try {
      await request(endpoint, {
        method: 'POST',
        body: JSON.stringify({ action: 'save', student }),
      });
      closeDrawer();
      await loadStudents(state.page);
      showStatus('Đã lưu hồ sơ sinh viên.');
    } catch (error) {
      showStatus(error.message, true);
    }
  }

  function currentDataset() {
    return document.querySelector('input[name="import-type"]:checked')?.value || '';
  }

  function renderPreview(data) {
    const body = byId('import-preview-body');
    const headers = ({
      KHOA: ['MA_KHOA', 'TEN_KHOA'],
      NGANH: ['MA_NGANH', 'TEN_NGANH', 'MA_KHOA'],
      LOP: ['MA_LOP', 'MA_NGANH'],
      SINHVIEN: ['MA_SV', 'HOTEN', 'NIEN_KHOA', 'MA_LOP'],
    })[data.dataset];
    const tableHead = byId('import-preview-head');
    tableHead.replaceChildren();
    const headerRow = document.createElement('tr');
    ['Dòng', ...headers, 'Kết quả'].forEach((label) => {
      const cell = document.createElement('th');
      cell.className = 'px-3 py-2';
      cell.textContent = label;
      headerRow.append(cell);
    });
    tableHead.append(headerRow);
    body.replaceChildren();
    for (const item of data.rows) {
      const row = document.createElement('tr');
      row.className = item.status === 'error' ? 'h-10 bg-error-container/30'
        : item.status === 'duplicate' ? 'h-10 bg-tertiary-fixed/30' : 'h-10 bg-secondary-container/10';
      row.append(makeCell(item.row, 'px-3 text-center font-data-mono'));
      for (const header of headers) {
        row.append(makeCell(item.values[header] ?? '', 'px-3'));
      }
      const errors = item.errors.join('; ');
      const result = item.status === 'error' ? `Lỗi dòng ${item.row}: ${errors}`
        : item.status === 'duplicate' ? `Trùng — sẽ bỏ qua: ${errors}` : 'Hợp lệ';
      row.append(makeCell(result, `px-3 ${item.status === 'error' ? 'text-error' : 'text-secondary'}`));
      body.append(row);
    }
    byId('import-counts').textContent =
      `${data.counts.valid} hợp lệ / ${data.counts.duplicate} trùng-bỏ qua / ${data.counts.error} lỗi`;
    byId('import-commit-btn').disabled = !data.can_commit || data.counts.valid === 0;
    byId('import-commit-label').textContent = data.can_commit
      ? `Xác nhận nhập ${data.counts.valid} dòng`
      : 'Sửa lỗi trước khi xác nhận';
    byId('import-preview-section').classList.remove('hidden');
    state.previewToken = data.preview_token;
    state.previewDataset = data.dataset;
  }

  async function previewImport(file) {
    if (!file) return;
    const dataset = currentDataset();
    if (!dataset) {
      showStatus('Hãy chọn loại dữ liệu import.', true);
      return;
    }
    const form = new FormData();
    form.append('dataset', dataset);
    form.append('file', file);
    byId('import-file-name').textContent = file.name;
    try {
      const data = await request(endpoint, { method: 'POST', body: form });
      renderPreview(data);
      showStatus('');
    } catch (error) {
      showStatus(error.message, true);
      byId('import-preview-section').classList.add('hidden');
    }
  }

  async function confirmImport() {
    if (!state.previewToken) return;
    try {
      const result = await request(endpoint, {
        method: 'POST',
        body: JSON.stringify({ action: 'import-commit', preview_token: state.previewToken }),
      });
      state.previewToken = '';
      byId('import-modal').classList.remove('flex');
      byId('import-modal').classList.add('hidden');
      await loadStudents(1);
      showStatus(`Đã nhập ${result.inserted} dòng; bỏ qua ${result.skipped_duplicates} mã trùng.`);
    } catch (error) {
      showStatus(error.message, true);
    }
  }

  function downloadTemplate() {
    const dataset = currentDataset();
    if (!dataset) {
      showStatus('Hãy chọn loại dữ liệu import.', true);
      return;
    }
    window.location.assign(`${endpoint}?action=template&dataset=${encodeURIComponent(dataset)}`);
  }

  byId('open-drawer-btn').addEventListener('click', () => openDrawer('create'));
  byId('close-drawer-btn').addEventListener('click', closeDrawer);
  byId('close-drawer-footer-btn').addEventListener('click', closeDrawer);
  byId('drawer-backdrop').addEventListener('click', closeDrawer);
  byId('student-form').addEventListener('submit', handleStudentSubmit);
  byId('input-khoa').addEventListener('change', () => {
    refreshMajorChoices();
    refreshClassChoices();
  });
  byId('input-nganh').addEventListener('change', refreshClassChoices);
  byId('student-table-body').addEventListener('click', async (event) => {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const row = button.closest('tr[data-id]');
    if (!row) return;
    const id = row.dataset.id;
    if (button.dataset.action === 'delete') {
      if (!window.confirm('Bạn có chắc chắn muốn xóa hồ sơ này? Dữ liệu vi phạm tham chiếu có thể khiến thao tác bị từ chối.')) return;
      try {
        await request(endpoint, { method: 'DELETE', body: JSON.stringify({ id }) });
        await loadStudents(state.page);
        showStatus('Đã xóa hồ sơ sinh viên.');
      } catch (error) {
        showStatus(error.message, true);
      }
    } else {
      await openStudent(id, button.dataset.action === 'detail' ? 'view' : 'edit');
    }
  });

  let searchTimer;
  byId('student-search').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadStudents(1), 250);
  });
  ['filter-khoa', 'filter-nganh', 'filter-lop'].forEach((id) => {
    byId(id).addEventListener('change', () => loadStudents(1));
  });
  byId('student-page-size').addEventListener('change', () => {
    state.pageSize = Number(byId('student-page-size').value);
    loadStudents(1);
  });
  byId('export-students-btn').addEventListener('click', () => {
    window.location.assign(`${endpoint}?action=export&${filters().toString().replace(/^action=list&/, '')}`);
  });

  const importModal = byId('import-modal');
  byId('open-import-modal-btn').addEventListener('click', () => {
    importModal.classList.remove('hidden');
    importModal.classList.add('flex');
    byId('import-preview-section').classList.add('hidden');
    byId('import-file').value = '';
    state.previewToken = '';
  });
  byId('close-import-modal-btn').addEventListener('click', () => {
    importModal.classList.remove('flex');
    importModal.classList.add('hidden');
  });
  byId('cancel-import-btn').addEventListener('click', () => {
    importModal.classList.remove('flex');
    importModal.classList.add('hidden');
  });
  byId('import-backdrop').addEventListener('click', () => {
    importModal.classList.remove('flex');
    importModal.classList.add('hidden');
  });
  byId('import-file').addEventListener('change', (event) => previewImport(event.target.files[0]));
  byId('import-drop-zone').addEventListener('click', (event) => {
    if (event.target.id !== 'import-file') byId('import-file').click();
  });
  byId('import-drop-zone').addEventListener('dragover', (event) => event.preventDefault());
  byId('import-drop-zone').addEventListener('drop', (event) => {
    event.preventDefault();
    previewImport(event.dataTransfer.files[0]);
  });
  byId('import-template-btn').addEventListener('click', downloadTemplate);
  byId('import-commit-btn').addEventListener('click', confirmImport);

  // Refresh the "SO_VI_PHAM" column after a manual violation is added or removed.
  window.addEventListener('student-violations-changed', () => loadStudents(state.page));

  async function initialize() {
    try {
      const data = await request(`${endpoint}?action=init`);
      state.csrfToken = data.csrf_token;
      state.catalog = data.catalog;
      renderCatalog();
      byId('student-count-stat').textContent = Number(data.statistics.students).toLocaleString('vi-VN');
      byId('faculty-count-stat').textContent = Number(data.statistics.faculties).toLocaleString('vi-VN');
      byId('class-count-stat').textContent = Number(data.statistics.classes).toLocaleString('vi-VN');
      renderRows(data.list);
    } catch (error) {
      showStatus(error.message, true);
      byId('student-table-body').replaceChildren();
    }
  }

  initialize();
})();
