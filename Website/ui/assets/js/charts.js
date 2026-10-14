/* Chart rendering shared by the dashboard and the report page (Chart.js). */
window.CIMCharts = (() => {
  'use strict';

  const palette = ['#2563eb', '#0f766e', '#b45309', '#7c3aed', '#be123c', '#0891b2', '#4d7c0f', '#c2410c'];
  const charts = {};

  function render(id, config) {
    const canvas = document.getElementById(id);
    if (!canvas || typeof Chart === 'undefined') return;
    charts[id]?.destroy();
    charts[id] = new Chart(canvas, config);
  }

  function baseOptions(extra = {}) {
    return {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
      scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      ...extra,
    };
  }

  function renderSummary(summary, prefix = '') {
    const days = summary.by_day.length > 62 ? summary.by_day.filter((d) => d.count > 0) : summary.by_day;
    render(prefix + 'chart-days', {
      type: 'bar',
      data: {
        labels: days.map((d) => CIM.formatDate(d.date).slice(0, 5)),
        datasets: [{ label: 'Số vi phạm', data: days.map((d) => d.count), backgroundColor: palette[0], borderRadius: 4 }],
      },
      options: baseOptions(),
    });
    render(prefix + 'chart-types', {
      type: 'doughnut',
      data: {
        labels: summary.by_type.map((t) => t.name),
        datasets: [{ data: summary.by_type.map((t) => t.count), backgroundColor: palette }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
    render(prefix + 'chart-hours', {
      type: 'bar',
      data: {
        labels: summary.by_hour.map((h) => `${String(h.hour).padStart(2, '0')}h`),
        datasets: [{ label: 'Số vi phạm', data: summary.by_hour.map((h) => h.count), backgroundColor: palette[2], borderRadius: 4 }],
      },
      options: baseOptions(),
    });
    render(prefix + 'chart-areas', {
      type: 'bar',
      data: {
        labels: summary.by_area.map((a) => a.name),
        datasets: [{ label: 'Số vi phạm', data: summary.by_area.map((a) => a.count), backgroundColor: palette[1], borderRadius: 4 }],
      },
      options: baseOptions({ indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } }),
    });
  }

  function fillTable(tbodyId, rows, columns, emptyText) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    if (!rows.length) {
      CIM.stateRow(tbody, columns.length, emptyText);
      return;
    }
    tbody.replaceChildren(...rows.map((row) => {
      const tr = CIM.el('tr', 'border-t border-surface-container');
      for (const column of columns) {
        tr.append(CIM.el('td', 'px-space-md py-2 ' + (column.className || ''), column.value(row)));
      }
      return tr;
    }));
  }

  return { renderSummary, fillTable };
})();
