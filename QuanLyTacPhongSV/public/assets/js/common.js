/* Shared helpers for the CTSV dashboard pages. */
(() => {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const role = document.querySelector('meta[name="user-role"]')?.content || '';

  // AU-11: Chống xem lại trang nội bộ từ BFCache khi nhấn nút Back sau khi đã đăng xuất
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
      window.location.reload();
    }
  });

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
    setTimeout(() => item.remove(), type === 'error' ? 7000 : 5000);
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
    BO_QUA: 'Bỏ qua (cảnh báo sai)',
  };

  // ---------------------------------------------------------------- Realtime & Notification Bell
  let currentMoiCount = 0;
  let unreadWhileHidden = 0;
  const originalDocumentTitle = document.title;

  function getBaseDocumentTitle() {
    return (originalDocumentTitle || document.title).replace(/^\(\d+\)\s*/, '');
  }

  function updateTabTitle() {
    if (document.hidden && unreadWhileHidden > 0) {
      document.title = `(${unreadWhileHidden}) ${getBaseDocumentTitle()}`;
    } else {
      document.title = getBaseDocumentTitle();
    }
  }

  function clearHiddenUnread() {
    unreadWhileHidden = 0;
    updateTabTitle();
  }

  function updateBadgeDisplay(count) {
    currentMoiCount = Math.max(0, Number(count) || 0);
    const badge = document.getElementById('notification-badge');
    const panelCount = document.getElementById('notification-panel-count');
    if (panelCount) panelCount.textContent = String(currentMoiCount);
    if (!badge) return;
    if (currentMoiCount > 0) {
      badge.textContent = currentMoiCount > 99 ? '99+' : String(currentMoiCount);
      badge.classList.remove('hidden');
      badge.classList.add('flex');
    } else {
      badge.textContent = '0';
      badge.classList.add('hidden');
      badge.classList.remove('flex');
    }
  }

  function adjustNotificationBadge(delta) {
    updateBadgeDisplay(currentMoiCount + delta);
  }

  function triggerBellShake() {
    const icon = document.getElementById('notification-bell-icon');
    if (!icon) return;
    icon.classList.remove('bell-shake');
    void icon.offsetWidth;
    icon.classList.add('bell-shake');
    setTimeout(() => icon.classList.remove('bell-shake'), 700);
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
      clearHiddenUnread();
    }
  });

  window.addEventListener('focus', () => {
    clearHiddenUnread();
  });

  async function loadNotificationList() {
    const listEl = document.getElementById('notification-list');
    if (!listEl) return;
    listEl.innerHTML = '<div class="p-4 text-center text-on-surface-variant text-sm"><span class="material-symbols-outlined mr-2 animate-spin align-middle text-[16px]">progress_activity</span>Đang tải...</div>';
    try {
      const res = await api('violations', { params: { status: 'MOI', limit: 10, page: 1 } });
      const items = res?.items || [];
      const total = res?.total !== undefined ? res.total : items.length;
      updateBadgeDisplay(total);

      if (items.length === 0) {
        listEl.innerHTML = '<div class="p-6 text-center text-on-surface-variant text-sm">Không có vi phạm nào chưa xử lý</div>';
        return;
      }

      listEl.innerHTML = '';
      items.forEach((item) => {
        const row = document.createElement('a');
        row.href = `list_vipham.php?status=MOI&id=${encodeURIComponent(item.id)}`;
        row.className = 'block p-3 hover:bg-surface-container-low transition-colors cursor-pointer text-left';
        
        const cameraName = item.camera_name || item.camera_id || 'Camera';
        const areaName = item.area_name ? ` · ${item.area_name}` : '';
        const timeStr = formatDateTime(item.timestamp || item.created_at);
        const violationType = item.violation_type || 'Vi phạm tác phong';

        row.innerHTML = `
          <div class="flex items-start justify-between gap-2">
            <span class="font-label-md font-semibold text-primary truncate">${violationType}</span>
            <span class="text-[11px] text-outline shrink-0">${timeStr.split(' ')[1] || ''}</span>
          </div>
          <div class="text-xs text-on-surface-variant mt-0.5 truncate">
            <span class="material-symbols-outlined text-[13px] align-middle text-outline mr-0.5">videocam</span>${cameraName}${areaName}
          </div>
        `;
        listEl.appendChild(row);
      });
    } catch {
      listEl.innerHTML = '<div class="p-4 text-center text-error text-xs">Không thể tải danh sách thông báo</div>';
    }
  }

  function initNotificationBell() {
    const bellBtn = document.getElementById('notification-bell-btn');
    const panel = document.getElementById('notification-panel');
    const refreshBtn = document.getElementById('notification-refresh-btn');

    if (!bellBtn || !panel) return;

    bellBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      const isHidden = panel.classList.toggle('hidden');
      bellBtn.setAttribute('aria-expanded', String(!isHidden));
      if (!isHidden) {
        loadNotificationList();
      }
    });

    refreshBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      loadNotificationList();
    });

    document.addEventListener('click', (e) => {
      if (!panel.contains(e.target) && !bellBtn.contains(e.target)) {
        panel.classList.add('hidden');
        bellBtn.setAttribute('aria-expanded', 'false');
      }
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        panel.classList.add('hidden');
        bellBtn.setAttribute('aria-expanded', 'false');
      }
    });

    api('violations', { params: { status: 'MOI', limit: 1, page: 1 } })
      .then((res) => {
        if (res?.total !== undefined) {
          updateBadgeDisplay(res.total);
        }
      })
      .catch(() => {});
  }

  // ---------------------------------------------------------------- SSE Realtime Client
  let sseEventSource = null;
  let sseRetryCount = 0;
  let sseRetryTimer = null;
  let hasShownDisconnectToast = false;

  function updateSseStatus(status) {
    const badge = document.getElementById('sse-status-badge');
    const dot = document.getElementById('sse-status-dot');
    const text = document.getElementById('sse-status-text');
    if (!badge || !dot || !text) return;

    if (status === 'connected') {
      badge.className = 'hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200';
      dot.className = 'w-2 h-2 rounded-full bg-emerald-500 animate-pulse';
      text.textContent = 'Trực tuyến';
      hasShownDisconnectToast = false;
    } else if (status === 'reconnecting') {
      badge.className = 'hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200';
      dot.className = 'w-2 h-2 rounded-full bg-amber-500 animate-ping';
      text.textContent = 'Đang kết nối lại...';
    } else {
      badge.className = 'hidden sm:flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200';
      dot.className = 'w-2 h-2 rounded-full bg-rose-500';
      text.textContent = 'Mất kết nối';
    }
  }

  function startSseConnection() {
    const apiBaseMeta = document.querySelector('meta[name="cim-api-base-url"]');
    const baseUrl = apiBaseMeta?.content ? apiBaseMeta.content.replace(/\/$/, '') : '';
    const sseUrl = baseUrl ? `${baseUrl}/api/v1/violations/stream` : '/api/v1/violations/stream';

    try {
      if (sseEventSource) {
        sseEventSource.close();
      }
      sseEventSource = new EventSource(sseUrl);

      sseEventSource.onopen = () => {
        sseRetryCount = 0;
        updateSseStatus('connected');
      };

      let lastProcessedViolationId = null;

      function handleIncomingViolation(raw) {
        if (!raw || typeof raw !== 'object') return;
        const eventName = raw.event || '';
        if (eventName === 'connected') return;

        const payload = raw.data || raw;
        const id = payload?.id;
        if (id && lastProcessedViolationId === id) return;
        if (id) lastProcessedViolationId = id;

        adjustNotificationBadge(1);
        triggerBellShake();

        const cam = payload?.camera?.name || payload?.camera_name || payload?.camera?.code || payload?.camera_id || 'Camera';
        const type = payload?.type?.name || payload?.violation_type || 'Tác phong';
        toast(`Có vi phạm mới từ ${cam} (${type})`, 'info');

        if (document.hidden) {
          unreadWhileHidden++;
          updateTabTitle();
        }

        window.dispatchEvent(new CustomEvent('cim:new-violation', { detail: payload }));
      }

      sseEventSource.onmessage = (e) => {
        try {
          const parsed = JSON.parse(e.data);
          handleIncomingViolation(parsed);
        } catch {}
      };

      sseEventSource.addEventListener('new_violation', (e) => {
        try {
          const parsed = JSON.parse(e.data);
          handleIncomingViolation(parsed);
        } catch {}
      });

      sseEventSource.addEventListener('violation', (e) => {
        try {
          const payload = JSON.parse(e.data);
          handleIncomingViolation(payload);
        } catch {}
      });

      sseEventSource.onerror = () => {
        sseEventSource.close();
        sseEventSource = null;
        updateSseStatus('disconnected');

        if (!hasShownDisconnectToast) {
          toast('Mất kết nối với máy chủ AI Realtime. Đang tự động kết nối lại...', 'error');
          hasShownDisconnectToast = true;
        }

        const backoffSeconds = Math.min(30, 2 * Math.pow(2, sseRetryCount));
        sseRetryCount++;
        
        clearTimeout(sseRetryTimer);
        sseRetryTimer = setTimeout(() => {
          updateSseStatus('reconnecting');
          startSseConnection();
        }, backoffSeconds * 1000);
      };
    } catch {
      updateSseStatus('disconnected');
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    initNotificationBell();
    startSseConnection();
  });

  window.CIM = {
    api, ApiError, toast, confirmAction, el, stateRow, formatDateTime, formatDate, isoDate, download, setBusy,
    buildUrl, role, isAdmin: role === 'ADMIN', PROCESSING_LABELS,
    adjustNotificationBadge, updateBadgeDisplay,
  };
})();
