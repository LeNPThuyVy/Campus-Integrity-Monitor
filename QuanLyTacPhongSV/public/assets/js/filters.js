/* Shared report/dashboard filter bar (includes/report_filters.php). */
window.CIMFilters = (() => {
  'use strict';

  async function fillSelect(select, route, valueFn, labelFn, params) {
    if (!select) return [];
    const result = await CIM.api(route, { params });
    const items = result.items || [];
    for (const item of items) select.add(new Option(labelFn(item), valueFn(item)));
    return items;
  }

  async function init(onChange, { defaultPeriod = 'month' } = {}) {
    const form = document.getElementById('report-filters');
    const today = new Date();
    form.elements.period.value = defaultPeriod;
    form.elements.anchor.value = CIM.isoDate(today);
    form.elements.month.value = CIM.isoDate(today).slice(0, 7);
    form.elements.date_from.value = CIM.isoDate(new Date(today.getFullYear(), today.getMonth(), 1));
    form.elements.date_to.value = CIM.isoDate(today);

    function syncVisibility() {
      const period = form.elements.period.value;
      form.querySelectorAll('[data-period]').forEach((node) => {
        node.classList.toggle('hidden', !node.dataset.period.split(' ').includes(period));
      });
    }
    form.elements.period.addEventListener('change', syncVisibility);
    syncVisibility();

    try {
      await Promise.all([
        fillSelect(form.elements.semester_id, 'semesters', (s) => s.id,
          (s) => `${s.name}${s.is_current ? ' [Hiện tại]' : ''} (${CIM.formatDate(s.start_date)} - ${CIM.formatDate(s.end_date)})`),
        fillSelect(form.elements.area_id, 'areas', (a) => a.id, (a) => `${a.name}${a.active ? '' : ' (ngừng)'}`),
        fillSelect(form.elements.camera_id, 'cameras', (c) => c.id, (c) => `${c.code} - ${c.name}`),
        fillSelect(form.elements.type_id, 'violation-types', (t) => t.id, (t) => t.name),
      ]);
    } catch (error) {
      CIM.toast(`Không tải được danh sách bộ lọc: ${error.message}`, 'error');
    }

    form.addEventListener('submit', (event) => {
      event.preventDefault();
      onChange(params());
    });
    return params;

    function params() {
      const data = Object.fromEntries(new FormData(form).entries());
      const period = data.period;
      const result = { period, area_id: data.area_id, camera_id: data.camera_id, type_id: data.type_id };
      if (period === 'day' || period === 'week') result.anchor = data.anchor;
      if (period === 'month') result.month = data.month;
      if (period === 'semester') result.semester_id = data.semester_id;
      if (period === 'custom') { result.date_from = data.date_from; result.date_to = data.date_to; }
      return result;
    }
  }

  return { init };
})();
