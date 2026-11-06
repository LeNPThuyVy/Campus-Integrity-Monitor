/* Shared helpers for the CTSV dashboard pages. */
(() => {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const role = document.querySelector('meta[name="user-role"]')?.content || '';

  // ---------------------------------------------------------------- layout: sidebar + account menu
  const sidebar = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebar-backdrop');
  const toggle = document.getElementById('sidebar-toggle');
  function setSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full', !open);
    backdrop?.classList.toggle('hidden', !open);
    toggle?.setAttribute('aria-expanded', String(open));
  }
  toggle?.addEventListener('click', () => setSidebar(sidebar.classList.contains('-translate-x-full')));
  backdrop?.addEventListener('click', () => setSidebar(false));

  const menuButton = document.getElementById('account-menu-button');
  const menuPanel = document.getElementById('account-menu-panel');
  menuButton?.addEventListener('click', (event) => {
    event.stopPropagation();
    const open = menuPanel.classList.toggle('hidden') === false;
    menuButton.setAttribute('aria-expanded', String(open));
  });
  document.addEventListener('click', (event) => {
    if (menuPanel && !menuPanel.contains(event.target)) menuPanel.classList.add('hidden');
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') { menuPanel?.classList.add('hidden'); setSidebar(false); }
  });

  // ---------------------------------------------------------------- API (through the PHP proxy)
  class ApiError extends Error {
    constructor(message, status, details) {
      super(message);
      this.status = status;
      this.details = details;
    }
  }

  function buildUrl(route, params = {}) {
    const url = new URL('api/cim.php', window.location.href);
    url.searchParams.set('r', route);
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') url.searchParams.set(key, String(value));
    }
    return url;
  }

  async function api(route, { method = 'GET', params = {}, body } = {}) {
    const headers = { Accept: 'application/json' };
    if (method !== 'GET') headers['X-CSRF-Token'] = csrfToken;
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    let response;
    try {
      response = await fetch(buildUrl(route, params), {
        method, headers, credentials: 'same-origin', body: body === undefined ? undefined : JSON.stringify(body),
      });
    } catch {
      throw new ApiError('Không kết nối được máy chủ Web. Kiểm tra mạng và thử lại.', 0);
    }
    const isJson = (response.headers.get('content-type') || '').includes('application/json');
    const payload = isJson ? await response.json() : null;
    if (response.status === 401) {
      window.location.assign('login.php?next=' + encodeURIComponent(window.location.pathname.split('/').pop()));
      throw new ApiError('Phiên đăng nhập đã hết hạn.', 401);
    }
    if (!response.ok) {
      const error = payload?.error;
      const message = (typeof error === 'string' ? error : error?.message) || 'Không thể hoàn tất yêu cầu.';
      const details = typeof error === 'object' ? error?.details : undefined;
      const extra = Array.isArray(details)
        ? details.map((item) => (typeof item === 'string' ? item : item.message)).filter(Boolean).join(' ')
        : '';
      throw new ApiError(extra ? `${message} ${extra}` : message, response.status, details);
    }
    return payload;
  }

  // ---------------------------------------------------------------- UI helpers
  function toast(message, type = 'success') {
    const region = document.getElementById('toast-region');
    if (!region) return;
    const item = document.createElement('div');
    const styles = {
      success: 'bg-secondary text-white', error: 'bg-error text-on-error', info: 'bg-inverse-surface text-white',
    };
    item.className = `flex items-start gap-2 rounded-lg px-space-md py-space-sm shadow-xl ${styles[type] || styles.info}`;
    item.setAttribute('role', type === 'error' ? 'alert' : 'status');
    const icon = document.createElement('span');
    icon.className = 'material-symbols-outlined text-[20px]';
    icon.textContent = type === 'error' ? 'error' : type === 'success' ? 'check_circle' : 'info';
    const text = document.createElement('span');
    text.textContent = message;
    item.append(icon, text);
    region.append(item);
    setTimeout(() => item.remove(), type === 'error' ? 7000 : 4000);
  }

  function confirmAction(message, { title = 'Xác nhận thao tác', okText = 'Xác nhận', danger = false } = {}) {
    const dialog = document.getElementById('confirm-dialog');
    if (!dialog || typeof dialog.showModal !== 'function') return Promise.resolve(window.confirm(message));
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-message').textContent = message;
    const ok = document.getElementById('confirm-ok');
    ok.textContent = okText;
    ok.className = `rounded-lg px-space-md py-2 font-label-md ${danger ? 'bg-error text-on-error' : 'bg-primary-container text-on-primary'}`;
    return new Promise((resolve) => {
      dialog.addEventListener('close', () => resolve(dialog.returnValue === 'ok'), { once: true });
      dialog.returnValue = 'cancel';
      dialog.showModal();
    });
  }

  function el(tag, className = '', text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = String(text);
    return node;
  }

  function stateRow(tbody, colspan, message, kind = 'empty') {
    const row = el('tr');
    const cell = el('td', 'px-space-md py-8 text-center ' + (kind === 'error' ? 'text-error' : 'text-on-surface-variant'));
    cell.colSpan = colspan;
    if (kind === 'loading') {
      const spinner = el('span', 'material-symbols-outlined mr-2 animate-spin align-middle text-[18px]', 'progress_activity');
      cell.append(spinner, document.createTextNode(message));
    } else {
      cell.textContent = message;
    }
    row.append(cell);
    tbody.replaceChildren(row);
  }

  const pad2 = (n) => String(n).padStart(2, '0');
  // dd/mm/yyyy HH:mm:ss — the API returns local (school) time without a timezone suffix.
  function formatDateTime(value) {
    if (!value) return '—';
    const date = new Date(String(value).replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return String(value);
    return `${formatDate(value)} ${pad2(date.getHours())}:${pad2(date.getMinutes())}:${pad2(date.getSeconds())}`;
  }
  function formatDate(value) {
    if (!value) return '—';
    const date = new Date(String(value).slice(0, 10) + 'T00:00:00');
    if (Number.isNaN(date.getTime())) return String(value);
    return `${pad2(date.getDate())}/${pad2(date.getMonth() + 1)}/${date.getFullYear()}`;
  }
  function isoDate(date) {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
  }

  async function download(route, params, fallbackName) {
    const response = await fetch(buildUrl(route, params), { credentials: 'same-origin' });
    if (response.status === 401) {
      window.location.assign('login.php');
      return;
    }
    if (!response.ok) {
      let message = 'Không thể tải tệp.';
      try {
        const errJson = await response.json();
        const err = errJson?.error;
        message = (typeof err === 'string' ? err : err?.message) || message;
      } catch { /* binary or non-json error */ }
      throw new ApiError(message, response.status);
    }
    const disposition = response.headers.get('content-disposition') || '';
    const match = /filename="?([^";]+)"?/i.exec(disposition);
    const blob = await response.blob();
    const url = URL.createObjectURL(blob);
    const link = el('a');
    link.href = url;
    link.download = match ? match[1] : fallbackName;
    document.body.append(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2000);
  }

  function setBusy(button, busy, busyText = 'Đang xử lý…') {
    if (!button) return;
    if (busy) {
      button.dataset.label = button.textContent;
      button.textContent = busyText;
      button.disabled = true;
      button.classList.add('opacity-60', 'cursor-wait');
    } else {
      button.textContent = button.dataset.label || button.textContent;
      button.disabled = false;
      button.classList.remove('opacity-60', 'cursor-wait');
    }
  }

  const PROCESSING_LABELS = {
    MOI: 'Mới báo động (Chờ Cờ Đỏ)',
    DA_XEM: 'Đã điều Đội Cờ Đỏ',
    DA_XU_LY: 'Đã kiểm tra tại phòng học',
    BO_QUA: 'Đã kiểm tra',
  };

  window.CIM = {
    api, ApiError, toast, confirmAction, el, stateRow, formatDateTime, formatDate, isoDate, download, setBusy,
    buildUrl, role, isAdmin: role === 'ADMIN', PROCESSING_LABELS,
  };
})();
