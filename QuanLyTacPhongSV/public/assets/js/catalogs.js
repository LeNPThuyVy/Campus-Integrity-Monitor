(() => {
  'use strict';

  const endpoint = 'api/catalogs.php';
  const definitions = {
    KHOA: {
      title: 'Khoa',
      fields: [
        { name: 'MA_KHOA', label: 'Mã khoa', maxLength: 10, code: true },
        { name: 'TEN_KHOA', label: 'Tên khoa', maxLength: 50 },
      ],
      columns: ['MA_KHOA', 'TEN_KHOA'],
    },
    NGANH: {
      title: 'Ngành',
      fields: [
        { name: 'MA_NGANH', label: 'Mã ngành', maxLength: 10, code: true },
        { name: 'TEN_NGANH', label: 'Tên ngành', maxLength: 50 },
        { name: 'MA_KHOA', label: 'Khoa cha', options: 'KHOA' },
      ],
      columns: ['MA_NGANH', 'TEN_NGANH', 'MA_KHOA', 'TEN_KHOA'],
    },
    LOP: {
      title: 'Lớp',
      fields: [
        { name: 'MA_LOP', label: 'Mã lớp', maxLength: 20, code: true },
        { name: 'MA_NGANH', label: 'Ngành cha', options: 'NGANH' },
      ],
      columns: ['MA_LOP', 'MA_NGANH', 'MA_KHOA'],
    },
    LOI_VIPHAM: {
      title: 'Loại vi phạm',
      adminOnly: true,
      fields: [
        { name: 'TEN_LOI', label: 'Tên lỗi', maxLength: 100 },
        { name: 'DIEM_TRU', label: 'Điểm trừ tham khảo (hệ thống không tự trừ điểm)', number: true },
        { name: 'DANG_SU_DUNG', label: 'Trạng thái', choices: [['1', 'Đang sử dụng'], ['0', 'Ngừng sử dụng']] },
      ],
      columns: ['MA_LOI', 'TEN_LOI', 'DIEM_TRU', 'DANG_SU_DUNG'],
    },
    HOCKY: {
      title: 'Học kỳ',
      adminOnly: true,
      fields: [
        { name: 'MA_HK', label: 'Mã học kỳ (VD: HK1-2026)', maxLength: 20, code: true },
        { name: 'TEN_HK', label: 'Tên học kỳ (VD: Học kỳ 1 (2026-2027))', maxLength: 100 },
        { name: 'NGAY_BAT_DAU', label: 'Ngày bắt đầu (YYYY-MM-DD)', maxLength: 10 },
        { name: 'NGAY_KET_THUC', label: 'Ngày kết thúc (YYYY-MM-DD)', maxLength: 10 },
      ],
      columns: ['MA_HK', 'TEN_HK', 'NGAY_BAT_DAU', 'NGAY_KET_THUC', 'HIEN_TAI'],
    },
  };
  const state = { catalogs: {}, csrfToken: '', id: null, role: null };
  const columnLabels = {
    MA_KHOA: 'Mã khoa', TEN_KHOA: 'Tên khoa', MA_NGANH: 'Mã ngành', TEN_NGANH: 'Tên ngành', MA_LOP: 'Mã lớp',
    MA_LOI: 'Mã AI', TEN_LOI: 'Tên lỗi', DIEM_TRU: 'Điểm tham khảo', DANG_SU_DUNG: 'Trạng thái',
    MA_HK: 'Mã HK', TEN_HK: 'Tên học kỳ', NGAY_BAT_DAU: 'Bắt đầu', NGAY_KET_THUC: 'Kết thúc', HIEN_TAI: 'Hiện tại',
  };
  const canEdit = (entity) => !definitions[entity].adminOnly || state.role === 'ADMIN';
  const byId = (id) => document.getElementById(id);
  const entitySelect = byId('catalog-entity');
  const status = byId('catalog-status');

  function showStatus(message, isError = false) {
    status.textContent = message;
    status.classList.toggle('border-error/20', isError);
    status.classList.toggle('bg-error-container/40', isError);
    status.classList.toggle('text-on-error-container', isError);
    status.classList.toggle('border-secondary/20', !isError);
    status.classList.toggle('bg-secondary-container/40', !isError);
    status.classList.toggle('text-on-secondary-container', !isError);
  }

  async function request(options = {}) {
    const headers = new Headers(options.headers || {});
    if (options.body) headers.set('Content-Type', 'application/json');
    if (options.method === 'POST' && state.csrfToken) {
      headers.set('X-CSRF-Token', state.csrfToken);
    }
    const response = await fetch(endpoint, {
      ...options,
      headers,
      credentials: 'same-origin',
    });
    const contentType = response.headers.get('content-type') || '';
    const result = contentType.includes('application/json') ? await response.json() : null;
    if (!response.ok) {
      if (response.status === 401) window.location.assign('login.php');
      throw new Error(result?.error || 'Không thể hoàn tất yêu cầu danh mục.');
    }
    return result;
  }

  function optionData(field) {
    if (field.options === 'KHOA') return state.catalogs.KHOA || [];
    if (field.options === 'NGANH') return state.catalogs.NGANH || [];
    return [];
  }

  function fieldControl(field) {
    const label = document.createElement('label');
    label.className = 'flex flex-col gap-1';
    const caption = document.createElement('span');
    caption.className = 'font-label-sm text-label-sm text-on-surface-variant';
    caption.textContent = field.label;
    let control;
    if (field.choices) {
      control = document.createElement('select');
      for (const [value, text] of field.choices) control.add(new Option(text, value));
    } else if (field.options) {
      control = document.createElement('select');
      control.append(new Option('-- Chọn --', ''));
      for (const item of optionData(field)) {
        const value = field.options === 'KHOA' ? item.MA_KHOA : item.MA_NGANH;
        const labelText = field.options === 'KHOA'
          ? `${item.MA_KHOA} - ${item.TEN_KHOA}`
          : `${item.MA_NGANH} - ${item.TEN_NGANH}`;
        control.add(new Option(labelText, value));
      }
    } else {
      control = document.createElement('input');
      control.type = field.number ? 'number' : 'text';
      if (field.maxLength) control.maxLength = field.maxLength;
      if (field.number) {
        control.step = '1';
        control.min = '-2147483648';
        control.max = '2147483647';
      }
      control.autocomplete = 'off';
    }
    control.className = 'h-10 rounded-lg bg-surface-container-low px-3 text-on-surface';
    control.name = field.name;
    control.required = true;
    control.dataset.code = field.code ? 'true' : 'false';
    label.append(caption, control);
    return label;
  }

  function renderForm() {
    const definition = definitions[entitySelect.value];
    byId('catalog-form-title').textContent = state.id === null
      ? `Thêm ${definition.title.toLocaleLowerCase('vi')}`
      : `Cập nhật ${definition.title.toLocaleLowerCase('vi')}`;
    const fields = byId('catalog-fields');
    fields.replaceChildren(...definition.fields.map(fieldControl));
    const editable = canEdit(entitySelect.value);
    byId('catalog-form').classList.toggle('hidden', !editable);
    byId('catalog-cancel').classList.toggle('hidden', state.id === null);
  }

  function displayValue(entity, item, column) {
    if (column === 'DANG_SU_DUNG') return Number(item.DANG_SU_DUNG) === 1 ? 'Đang sử dụng' : 'Ngừng sử dụng';
    if (column === 'HIEN_TAI') return Number(item.HIEN_TAI) === 1 ? 'Đang diễn ra' : 'Không';
    if (column === 'MA_LOI') return item.MA_LOI || '—';
    if (column === 'TEN_KHOA' && entity === 'NGANH') return item.TEN_KHOA;
    if (column === 'MA_KHOA' && entity === 'LOP') return item.MA_KHOA;
    return item[column] == null ? '' : String(item[column]);
  }

  function renderTable() {
    const entity = entitySelect.value;
    const definition = definitions[entity];
    const head = byId('catalog-table-head');
    const body = byId('catalog-table-body');
    head.replaceChildren();
    body.replaceChildren();

    for (const column of ['ID', ...definition.columns, 'THAO_TAC']) {
      const cell = document.createElement('th');
      cell.className = 'px-space-md py-3';
      cell.textContent = column === 'THAO_TAC' ? 'Thao tác' : (columnLabels[column] || column);
      head.append(cell);
    }
    const rows = state.catalogs[entity] || [];
    rows.forEach((item) => {
      const row = document.createElement('tr');
      row.className = 'border-t border-surface-container hover:bg-surface-container-low/60';
      for (const column of ['ID', ...definition.columns]) {
        const cell = document.createElement('td');
        cell.className = 'px-space-md py-3';
        cell.textContent = column === 'ID' ? String(item.ID) : displayValue(entity, item, column);
        row.append(cell);
      }
      const actions = document.createElement('td');
      actions.className = 'whitespace-nowrap px-space-md py-3 text-right';
      const edit = document.createElement('button');
      edit.type = 'button';
      edit.className = 'mr-2 rounded-lg bg-surface-container px-3 py-1.5';
      edit.textContent = 'Sửa';
      edit.addEventListener('click', () => startEdit(item));
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'rounded-lg bg-error-container px-3 py-1.5 text-on-error-container';
      remove.textContent = 'Xóa';
      remove.addEventListener('click', () => removeItem(item));
      if (canEdit(entity)) {
        actions.append(edit, remove);
      } else {
        actions.textContent = 'Chỉ xem';
        actions.classList.add('text-on-surface-variant');
      }
      row.append(actions);
      body.append(row);
    });
    if (rows.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = definition.columns.length + 2;
      cell.className = 'px-space-md py-8 text-center text-on-surface-variant';
      cell.textContent = 'Cơ sở dữ liệu không có bản ghi danh mục.';
      row.append(cell);
      body.append(row);
    }
  }

  function startEdit(item) {
    state.id = Number(item.ID);
    renderForm();
    const form = byId('catalog-form');
    for (const field of definitions[entitySelect.value].fields) {
      const input = form.elements.namedItem(field.name);
      input.value = item[field.name] == null ? '' : String(item[field.name]);
    }
    byId('catalog-form-title').focus?.();
    showStatus('Đang sửa bản ghi. Thay đổi chỉ được lưu sau khi xác nhận.');
  }

  async function removeItem(item) {
    if (!window.confirm('Xác nhận xóa danh mục này? Nếu dữ liệu khác đang tham chiếu, SQL Server sẽ từ chối và không xóa.')) {
      return;
    }
    try {
      await request({
        method: 'POST',
        body: JSON.stringify({ action: 'delete', entity: entitySelect.value, id: item.ID }),
      });
      await loadCatalogs();
      showStatus('Đã xóa danh mục trong cơ sở dữ liệu.');
    } catch (error) {
      showStatus(error.message, true);
    }
  }

  async function loadCatalogs() {
    try {
      const result = await request();
      state.catalogs = result.catalogs;
      state.csrfToken = result.csrf_token;
      state.role = result.role;
      renderForm();
      renderTable();
      showStatus('Danh mục đã được tải từ cơ sở dữ liệu.');
    } catch (error) {
      showStatus(`${error.message} Không có dữ liệu mẫu được hiển thị.`, true);
    }
  }

  entitySelect.addEventListener('change', () => {
    state.id = null;
    renderForm();
    renderTable();
  });
  byId('catalog-refresh').addEventListener('click', loadCatalogs);
  byId('catalog-cancel').addEventListener('click', () => {
    state.id = null;
    byId('catalog-form').reset();
    renderForm();
  });
  byId('catalog-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const record = {};
    for (const field of definitions[entitySelect.value].fields) {
      const control = event.currentTarget.elements.namedItem(field.name);
      record[field.name] = field.number ? Number(control.value) : control.value.trim();
      if (field.code && !/^[A-Za-z0-9_-]+$/.test(record[field.name])) {
        showStatus(`Mã ${field.label} không đúng định dạng.`, true);
        control.focus();
        return;
      }
    }
    try {
      await request({
        method: 'POST',
        body: JSON.stringify({
          action: 'save',
          entity: entitySelect.value,
          id: state.id,
          record,
        }),
      });
      state.id = null;
      event.currentTarget.reset();
      await loadCatalogs();
      showStatus('Đã lưu danh mục trong cơ sở dữ liệu.');
    } catch (error) {
      showStatus(error.message, true);
    }
  });

  renderForm();
  renderTable();
  loadCatalogs();
})();
