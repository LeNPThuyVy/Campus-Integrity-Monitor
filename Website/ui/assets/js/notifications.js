/* Header bell: polls the API (through the PHP proxy) for unprocessed violations and announces new ones. */
(() => {
  'use strict';

  const button = document.getElementById('notif-button');
  if (!button || !window.CIM) return;

  const panel = document.getElementById('notif-panel');
  const badge = document.getElementById('notif-badge');
  const icon = document.getElementById('notif-icon');
  const list = document.getElementById('notif-list');
  const totalLabel = document.getElementById('notif-total');

  // php -S serves one request at a time, so short polls are used instead of a held-open stream.
  const VISIBLE_INTERVAL = 5000;
  const HIDDEN_INTERVAL = 30000;
  const MAX_INTERVAL = 60000;
  const STORAGE_KEY = 'cim.notif.lastSeenId';
  const baseTitle = document.title;

  let tabLastId = null; // newest id this tab has seen (drives the page refresh event)
  let unread = 0;       // arrivals while the tab was in the background (shown in the tab title)
  let failures = 0;
  let timer = null;
  let polling = false;

  function readStoredId() {
    try { return Number(localStorage.getItem(STORAGE_KEY)) || 0; } catch { return 0; }
  }
  function storeId(id) {
    try { localStorage.setItem(STORAGE_KEY, String(id)); } catch { /* private mode: per-tab only */ }
  }

  function render(result) {
    const total = result.total || 0;
    badge.textContent = total > 99 ? '99+' : String(total);
    badge.classList.toggle('hidden', total === 0);
    totalLabel.textContent = total ? `${total.toLocaleString('vi-VN')} bản ghi` : '';
    if (!result.items.length) {
      list.replaceChildren(CIM.el('li', 'px-space-md py-6 text-center text-on-surface-variant', 'Không có vi phạm nào chờ xử lý.'));
      return;
    }
    list.replaceChildren(...result.items.map((item) => {
      const link = CIM.el('a', 'flex items-start gap-space-sm px-space-md py-2.5 hover:bg-surface-container-low');
      link.href = `list_vipham.php?status=MOI&id=${encodeURIComponent(item.id)}`;
      const body = CIM.el('div', 'min-w-0 flex-1');
      body.append(
        CIM.el('div', 'font-label-md text-label-md font-semibold truncate', item.type.name),
        CIM.el('div', 'font-label-sm text-label-sm text-on-surface-variant truncate', `${item.area.name} · ${item.camera.code}`),
      );
      link.append(
        CIM.el('span', 'material-symbols-outlined text-[20px] text-error mt-0.5', 'warning'),
        body,
        CIM.el('span', 'font-label-sm text-label-sm text-outline whitespace-nowrap', CIM.formatDateTime(item.occurred_at).slice(-8)),
      );
      const row = CIM.el('li');
      row.append(link);
      return row;
    }));
  }

  function announce(result) {
    const newestId = result.items.length ? result.items[0].id : 0;
    if (tabLastId === null) {
      // First poll of this tab: report what arrived since the last visit (if any), then just track.
      tabLastId = newestId;
    } else if (newestId > tabLastId) {
      const fresh = result.items.filter((item) => item.id > tabLastId).length;
      tabLastId = newestId;
      window.dispatchEvent(new CustomEvent('cim:new-violations', { detail: { count: fresh } }));
      if (document.hidden) {
        unread += fresh;
        document.title = `(${unread}) ${baseTitle}`;
      }
    }
    // The shared id keeps several open tabs from toasting the same violation.
    const storedId = readStoredId();
    if (newestId > storedId) {
      if (storedId > 0) {
        const fresh = result.items.filter((item) => item.id > storedId).length;
        CIM.toast(`Có ${fresh}${fresh >= result.items.length ? '+' : ''} vi phạm mới từ camera.`, 'info');
        icon.classList.add('animate-bounce', 'text-error');
        setTimeout(() => icon.classList.remove('animate-bounce', 'text-error'), 3000);
      }
      storeId(newestId);
    }
  }

  function schedule() {
    clearTimeout(timer);
    const base = document.hidden ? HIDDEN_INTERVAL : VISIBLE_INTERVAL;
    timer = setTimeout(poll, failures ? Math.min(base * 2 ** failures, MAX_INTERVAL) : base);
  }

  async function poll() {
    if (polling) return;
    polling = true;
    try {
      const result = await CIM.api('violations', { params: { status: 'MOI', page_size: 10, sort: 'newest' }, background: true });
      failures = 0;
      render(result);
      announce(result);
    } catch (error) {
      if (error.status === 401) return; // CIM.api is already redirecting to the login page
      failures = Math.min(failures + 1, 5);
    } finally {
      polling = false;
    }
    schedule();
  }

  function setOpen(open) {
    panel.classList.toggle('hidden', !open);
    button.setAttribute('aria-expanded', String(open));
    if (open) document.getElementById('account-menu-panel')?.classList.add('hidden');
  }

  button.addEventListener('click', (event) => {
    event.stopPropagation();
    setOpen(panel.classList.contains('hidden'));
  });
  document.addEventListener('click', (event) => {
    if (!panel.contains(event.target)) setOpen(false);
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setOpen(false);
  });
  document.addEventListener('visibilitychange', () => {
    if (document.hidden) { schedule(); return; }
    unread = 0;
    document.title = baseTitle;
    clearTimeout(timer);
    poll();
  });
  // A processing update elsewhere on the page changes the count: refresh without waiting.
  window.addEventListener('cim:violations-changed', () => { clearTimeout(timer); poll(); });

  poll();
})();
