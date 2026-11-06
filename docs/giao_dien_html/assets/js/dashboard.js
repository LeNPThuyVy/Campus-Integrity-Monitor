(() => {
  'use strict';

  const errorBox = document.getElementById('dashboard-error');

  async function load(params) {
    errorBox.classList.add('hidden');
    CIM.stateRow(document.getElementById('hotspot-body'), 3, 'Đang tải dữ liệu…', 'loading');
    CIM.stateRow(document.getElementById('type-body'), 3, 'Đang tải dữ liệu…', 'loading');
    try {
      const { data } = await CIM.api('dashboard', { params });
      for (const [key, value] of Object.entries(data.counters)) {
        const node = document.querySelector(`[data-counter="${key}"]`);
        if (node) node.textContent = value === null ? '—' : value.toLocaleString('vi-VN');
      }
      document.getElementById('semester-name').textContent = data.current_semester
        ? data.current_semester.name : 'Chưa khai báo học kỳ hiện tại';
      document.getElementById('dashboard-range').textContent = `Đang xem: ${data.range.label}.`;
      const summary = data.summary;
      document.getElementById('summary-total').textContent = summary.total.toLocaleString('vi-VN');
      CIMCharts.renderSummary(summary);
      CIMCharts.fillTable('hotspot-body', summary.hotspots, [
        { value: (r) => r.area_name },
        { value: (r) => `${String(r.hour).padStart(2, '0')}:00 - ${String(r.hour).padStart(2, '0')}:59` },
        { value: (r) => r.count, className: 'text-right font-semibold' },
      ], 'Chưa có vi phạm trong khoảng thời gian đã chọn.');
      CIMCharts.fillTable('type-body', summary.by_type, [
        { value: (r) => r.name },
        { value: (r) => r.count, className: 'text-right font-semibold' },
        { value: (r) => (summary.total ? `${((r.count / summary.total) * 100).toFixed(1)}%` : '—'), className: 'text-right' },
      ], 'Chưa có loại vi phạm.');
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove('hidden');
      CIM.stateRow(document.getElementById('hotspot-body'), 3, 'Không tải được dữ liệu.', 'error');
      CIM.stateRow(document.getElementById('type-body'), 3, 'Không tải được dữ liệu.', 'error');
    }
  }

  CIMFilters.init(load, { defaultPeriod: 'month' }).then((params) => load(params()));
})();
