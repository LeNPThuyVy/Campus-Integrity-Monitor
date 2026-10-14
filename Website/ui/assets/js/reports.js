(() => {
  'use strict';

  const errorBox = document.getElementById('report-error');
  let currentParams = null;

  function kpi(label, value) {
    const card = CIM.el('div', 'rounded-lg bg-surface-container-low p-space-sm');
    card.append(CIM.el('p', 'font-label-sm text-label-sm text-on-surface-variant', label),
      CIM.el('p', 'font-headline-lg text-headline-lg', value));
    return card;
  }

  async function load(params) {
    currentParams = params;
    errorBox.classList.add('hidden');
    for (const id of ['hotspot-body', 'area-body', 'weekday-body']) {
      CIM.stateRow(document.getElementById(id), 2, 'Đang tải…', 'loading');
    }
    try {
      const { data } = await CIM.api('report-summary', { params });
      const summary = data.summary;
      document.getElementById('report-title').textContent = `Báo cáo vi phạm: ${data.range.label}`;
      document.getElementById('report-filters-text').replaceChildren(
        ...data.filters.map((f) => CIM.el('li', '', `${f.label}: ${f.value}`)));
      const peakHour = summary.peak_hour ? `${summary.peak_hour.label} (${summary.peak_hour.count})` : '—';
      const peakArea = summary.peak_area ? `${summary.peak_area.name} (${summary.peak_area.count})` : '—';
      const handled = (summary.by_status.DA_XU_LY || 0) + (summary.by_status.BO_QUA || 0);
      document.getElementById('report-kpis').replaceChildren(
        kpi('Tổng số vi phạm', summary.total.toLocaleString('vi-VN')),
        kpi('Khung giờ cao điểm', peakHour),
        kpi('Khu vực nhiều nhất', peakArea),
        kpi('Đã xử lý / bỏ qua', `${handled} / ${summary.total}`),
      );
      CIMCharts.renderSummary(summary);
      CIMCharts.fillTable('hotspot-body', summary.hotspots, [
        { value: (r) => r.label }, { value: (r) => r.count, className: 'text-right font-semibold' },
      ], 'Không có vi phạm trong kỳ đã chọn.');
      CIMCharts.fillTable('area-body', summary.by_area, [
        { value: (r) => r.name }, { value: (r) => r.count, className: 'text-right font-semibold' },
      ], 'Không có vi phạm trong kỳ đã chọn.');
      CIMCharts.fillTable('weekday-body', summary.by_weekday, [
        { value: (r) => r.name }, { value: (r) => r.count, className: 'text-right font-semibold' },
      ], 'Không có dữ liệu.');
    } catch (error) {
      errorBox.textContent = error.message;
      errorBox.classList.remove('hidden');
      document.getElementById('report-title').textContent = 'Không tải được báo cáo';
      for (const id of ['hotspot-body', 'area-body', 'weekday-body']) {
        CIM.stateRow(document.getElementById(id), 2, 'Không tải được dữ liệu.', 'error');
      }
    }
  }

  async function exportReport(format, button) {
    if (!currentParams) return;
    CIM.setBusy(button, true, 'Đang tạo tệp…');
    try {
      await CIM.download('report-export', { ...currentParams, format }, `bao-cao-vi-pham.${format}`);
      CIM.toast(`Đã xuất báo cáo ${format.toUpperCase()}.`);
    } catch (error) {
      CIM.toast(error.message, 'error');
    } finally {
      CIM.setBusy(button, false);
    }
  }

  document.getElementById('export-xlsx').addEventListener('click', (e) => exportReport('xlsx', e.currentTarget));
  document.getElementById('export-pdf').addEventListener('click', (e) => exportReport('pdf', e.currentTarget));
  CIMFilters.init(load, { defaultPeriod: 'week' }).then((params) => load(params()));
})();
