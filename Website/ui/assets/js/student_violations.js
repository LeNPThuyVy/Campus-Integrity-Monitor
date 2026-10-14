/* Manual violation records (CT_VIPHAM) inside the student drawer of ql_sinhvien.php. */
window.CIMStudentViolations = (() => {
  'use strict';

  const endpoint = 'api/students.php';
  const byId = (id) => document.getElementById(id);
  const section = byId('manual-violation-section');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  let student = null;
  let requestSeq = 0;

  async function call(url, options = {}) {
    const headers = { Accept: 'application/json' };
    if (options.body) headers['Content-Type'] = 'application/json';
    if (options.method && options.method !== 'GET') headers['X-CSRF-Token'] = csrfToken;
    const response = await fetch(url, { ...options, headers, credentials: 'same-origin' });
    const body = (response.headers.get('content-type') || '').includes('application/json') ? await response.json() : null;
    if (response.status === 401) window.location.assign('login.php');
    if (!response.ok) {
      const error = new Error(body?.error || 'Không thể hoàn tất yêu cầu.');
      error.status = response.status;
      error.code = body?.code;
      throw error;
    }
    return body;
  }

  function setStatus(message, isError = false) {
    const status = byId('mv-status');
    status.textContent = message;
    status.className = 'font-body-sm text-body-sm ' + (isError ? 'text-error' : 'text-secondary');
  }

  function formatDate(value) {
    const [y, m, d] = String(value).slice(0, 10).split('-');
    return d && m && y ? `${d}/${m}/${y}` : String(value);
  }

  function renderList(rows) {
    const list = byId('mv-list');
    if (!rows.length) {
      list.replaceChildren(Object.assign(document.createElement('li'), {
        className: 'px-3 py-3 text-center text-on-surface-variant', textContent: 'Chưa có vi phạm nào được ghi nhận.',
      }));
      return;
    }
    list.replaceChildren(...rows.map((row) => {
      const item = document.createElement('li');
      item.className = 'flex items-start justify-between gap-2 px-3 py-2';
      const text = document.createElement('div');
      const title = document.createElement('p');
      title.className = 'font-label-md text-label-md';
      title.textContent = `${formatDate(row.NGAY_VIPHAM)} · ${row.TEN_LOI}`;
      const meta = document.createElement('p');
      meta.className = 'font-body-sm text-body-sm text-on-surface-variant';
      meta.textContent = `Mã #${row.ID} · điểm tham khảo ${row.DIEM_TRU}${row.GHI_CHU ? ' · ' + row.GHI_CHU : ''}`;
      text.append(title, meta);
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'shrink-0 rounded-lg px-2 py-1 text-error hover:bg-error-container';
      remove.textContent = 'Xóa';
      remove.setAttribute('aria-label', `Xóa vi phạm #${row.ID}`);
      remove.addEventListener('click', () => removeViolation(row));
      item.append(text, remove);
      return item;
    }));
  }

  async function load() {
    const seq = ++requestSeq;
    setStatus('Đang tải lịch sử vi phạm…');
    try {
      const data = await call(`${endpoint}?action=violations&id=${encodeURIComponent(student.ID)}`);
      if (seq !== requestSeq) return;
      const select = byId('mv-type');
      const selected = select.value;
      select.replaceChildren(new Option('-- Chọn loại vi phạm --', ''),
        ...data.types.map((t) => new Option(`${t.TEN_LOI} (điểm tham khảo ${t.DIEM_TRU})`, t.ID)));
      select.value = selected;
      const dateInput = byId('mv-date');
      dateInput.max = data.today;
      if (!dateInput.value) dateInput.value = data.today;
      renderList(data.violations);
      setStatus(`${data.violations.length} lần vi phạm đã ghi nhận.`);
    } catch (error) {
      if (seq === requestSeq) setStatus(error.message, true);
    }
  }

  async function addViolation(confirmDuplicate = false) {
    const violation = {
      student_id: student.ID,
      type_id: byId('mv-type').value,
      date: byId('mv-date').value,
      note: byId('mv-note').value,
      confirm_duplicate: confirmDuplicate,
    };
    if (!violation.type_id || !violation.date) {
      setStatus('Chọn loại vi phạm và ngày vi phạm.', true);
      return;
    }
    const button = byId('mv-submit');
    button.disabled = true;
    try {
      await call(endpoint, { method: 'POST', body: JSON.stringify({ action: 'violation-add', violation }) });
      byId('mv-note').value = '';
      await load();
      setStatus('Đã ghi nhận vi phạm vào hồ sơ sinh viên.');
      window.dispatchEvent(new Event('student-violations-changed'));
    } catch (error) {
      if (error.code === 'duplicate_same_day' && !confirmDuplicate) {
        const ok = window.CIM
          ? await CIM.confirmAction(error.message, { title: 'Có thể bị ghi trùng', okText: 'Vẫn ghi nhận' })
          : window.confirm(error.message);
        if (ok) await addViolation(true);
        return;
      }
      setStatus(error.message, true);
    } finally {
      button.disabled = false;
    }
  }

  async function removeViolation(row) {
    const message = `Xóa vi phạm #${row.ID} (${row.TEN_LOI}, ${formatDate(row.NGAY_VIPHAM)}) khỏi hồ sơ? Thao tác không thể hoàn tác.`;
    const ok = window.CIM ? await CIM.confirmAction(message, { danger: true, okText: 'Xóa' }) : window.confirm(message);
    if (!ok) return;
    try {
      await call(endpoint, { method: 'POST', body: JSON.stringify({ action: 'violation-delete', id: row.ID, student_id: student.ID }) });
      await load();
      setStatus('Đã xóa bản ghi vi phạm.');
      window.dispatchEvent(new Event('student-violations-changed'));
    } catch (error) {
      setStatus(error.message, true);
    }
  }

  byId('manual-violation-form').addEventListener('submit', (event) => {
    event.preventDefault();
    addViolation(false);
  });

  return {
    show(selected) {
      student = selected;
      requestSeq++;
      section.classList.toggle('hidden', !student);
      section.classList.toggle('flex', Boolean(student));
      if (!student) return;
      byId('mv-note').value = '';
      byId('mv-date').value = '';
      load();
    },
  };
})();
