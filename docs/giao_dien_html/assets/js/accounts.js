(() => {
  'use strict';

  const form = document.getElementById('account-form');
  const tbody = document.getElementById('account-body');
  const resetDialog = document.getElementById('reset-dialog');
  const ROLE_LABELS = { ADMIN: 'Quản trị viên', CTSV: 'Nhân viên CTSV' };
  let resetTarget = null;

  function setMode(editing) {
    document.getElementById('account-form-title').textContent = editing ? 'Sửa tài khoản' : 'Tạo tài khoản';
    form.elements.username.readOnly = editing;
    form.elements.password.required = !editing;
    form.querySelector('[data-create-only]').classList.toggle('hidden', editing);
    const editOnly = form.querySelector('[data-edit-only]');
    editOnly.classList.toggle('hidden', !editing);
    editOnly.classList.toggle('flex', editing);
    document.getElementById('account-cancel').classList.toggle('hidden', !editing);
  }

  async function load() {
    CIM.stateRow(tbody, 5, 'Đang tải tài khoản…', 'loading');
    try {
      const { items } = await CIM.api('accounts');
      if (!items.length) { CIM.stateRow(tbody, 5, 'Chưa có tài khoản.'); return; }
      tbody.replaceChildren(...items.map(renderRow));
    } catch (error) {
      CIM.stateRow(tbody, 5, error.message, 'error');
    }
  }

  function renderRow(account) {
    const row = CIM.el('tr', 'border-t border-surface-container');
    const who = CIM.el('td', 'px-space-md py-3');
    who.append(CIM.el('div', 'font-semibold', account.full_name), CIM.el('div', 'font-data-mono text-on-surface-variant', account.username));
    const locked = account.locked_until && new Date(account.locked_until.replace(' ', 'T')) > new Date();
    const status = CIM.el('td', 'px-space-md py-3',
      !account.active ? 'Đã vô hiệu hóa' : locked ? `Tạm khóa đến ${CIM.formatDateTime(account.locked_until)}` : 'Đang hoạt động');
    const actions = CIM.el('td', 'px-space-md py-3 text-right whitespace-nowrap');
    const edit = CIM.el('button', 'rounded-lg bg-surface-container px-3 py-1.5 font-label-md', 'Sửa');
    edit.type = 'button';
    edit.addEventListener('click', () => {
      form.elements.id.value = account.id;
      form.elements.username.value = account.username;
      form.elements.full_name.value = account.full_name;
      form.elements.role.value = account.role;
      form.elements.active.checked = account.active;
      setMode(true);
    });
    const reset = CIM.el('button', 'ml-1 rounded-lg bg-tertiary-container px-3 py-1.5 font-label-md text-on-tertiary-container', 'Đặt lại mật khẩu');
    reset.type = 'button';
    reset.addEventListener('click', () => {
      resetTarget = account;
      document.getElementById('reset-username').textContent = account.username;
      document.getElementById('reset-form').reset();
      resetDialog.showModal();
    });
    actions.append(edit, reset);
    row.append(who, CIM.el('td', 'px-space-md py-3', ROLE_LABELS[account.role] || account.role), status,
      CIM.el('td', 'px-space-md py-3', CIM.formatDateTime(account.last_login_at)), actions);
    return row;
  }

  form.addEventListener('reset', () => setTimeout(() => { form.elements.id.value = ''; setMode(false); }, 0));
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const id = form.elements.id.value;
    const button = form.querySelector('button[type="submit"]');
    try {
      if (id) {
        const body = { full_name: form.elements.full_name.value, role: form.elements.role.value, active: form.elements.active.checked };
        if (!body.active && !(await CIM.confirmAction('Vô hiệu hóa tài khoản này? Người dùng sẽ bị đăng xuất ngay.', { danger: true }))) return;
        CIM.setBusy(button, true);
        await CIM.api('account', { method: 'PUT', params: { id }, body });
        CIM.toast('Đã cập nhật tài khoản.');
      } else {
        CIM.setBusy(button, true);
        await CIM.api('accounts', { method: 'POST', body: {
          username: form.elements.username.value, full_name: form.elements.full_name.value,
          role: form.elements.role.value, password: form.elements.password.value,
        } });
        CIM.toast('Đã tạo tài khoản.');
      }
      form.reset();
      load();
    } catch (error) {
      CIM.toast(error.message, 'error');
    } finally {
      CIM.setBusy(button, false);
    }
  });

  document.getElementById('reset-cancel').addEventListener('click', () => resetDialog.close());
  document.getElementById('reset-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const target = event.currentTarget;
    if (!target.reportValidity() || !resetTarget) return;
    try {
      await CIM.api('account-password', { method: 'POST', params: { id: resetTarget.id },
        body: { new_password: target.elements.new_password.value } });
      resetDialog.close();
      CIM.toast(`Đã đặt lại mật khẩu cho ${resetTarget.username}.`);
    } catch (error) {
      CIM.toast(error.message, 'error');
    }
  });

  setMode(false);
  load();
})();
