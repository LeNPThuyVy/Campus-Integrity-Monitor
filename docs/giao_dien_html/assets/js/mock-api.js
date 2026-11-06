/* Giả lập API cho bản HTML tĩnh của giao diện. Toàn bộ dữ liệu dưới đây là dữ liệu mẫu, không phải dữ liệu thật. */
(() => {
  'use strict';

  /* Cố định ngày hiện tại của bản tĩnh để bộ lọc, biểu đồ và bản ghi khớp nhau. */
  const RealDate = Date;
  const FIXED_NOW = new RealDate('2026-10-31T10:30:00').getTime();
  window.Date = class extends RealDate {
    constructor(...args) { if (args.length) super(...args); else super(FIXED_NOW); }
  };

  const areas = [
    { id: 1, code: 'KV-A', name: 'Sảnh tòa A', description: 'Lối vào chính tòa A', camera_count: 2, active: true },
    { id: 2, code: 'KV-B', name: 'Hành lang tòa B', description: 'Hành lang tầng 1 tòa B', camera_count: 1, active: true },
    { id: 3, code: 'KV-C', name: 'Cổng số 1', description: 'Cổng chính của trường', camera_count: 1, active: true },
    { id: 4, code: 'KV-D', name: 'Thư viện', description: 'Khu vực trước cửa thư viện', camera_count: 1, active: false },
  ];
  const areaRef = (id) => ({ id, code: areas[id - 1].code, name: areas[id - 1].name });
  const cameras = [
    { id: 1, code: 'CAM-01', name: 'Camera sảnh A trái', location: 'Tầng trệt, cột A2', area: areaRef(1), active: true },
    { id: 2, code: 'CAM-02', name: 'Camera sảnh A phải', location: 'Tầng trệt, cột A5', area: areaRef(1), active: true },
    { id: 3, code: 'CAM-03', name: 'Camera hành lang B', location: 'Tầng 1, đầu hành lang', area: areaRef(2), active: true },
    { id: 4, code: 'CAM-04', name: 'Camera cổng 1', location: 'Bên trong cổng số 1', area: areaRef(3), active: true },
    { id: 5, code: 'CAM-05', name: 'Camera thư viện', location: 'Trước cửa thư viện', area: areaRef(4), active: false },
  ];
  const semesters = [
    { id: 1, academic_year: '2026-2027', term: 1, code: 'HK1-2627', name: 'Học kỳ 1 năm học 2026-2027', start_date: '2026-08-31', end_date: '2027-01-10', is_current: true },
    { id: 2, academic_year: '2025-2026', term: 2, code: 'HK2-2526', name: 'Học kỳ 2 năm học 2025-2026', start_date: '2026-01-19', end_date: '2026-06-14', is_current: false },
    { id: 3, academic_year: '2025-2026', term: 1, code: 'HK1-2526', name: 'Học kỳ 1 năm học 2025-2026', start_date: '2025-09-01', end_date: '2026-01-11', is_current: false },
  ];
  const types = [
    { id: 1, ID: 1, MA_LOI: 'UNIFORM', name: 'Không mặc đồng phục', TEN_LOI: 'Không mặc đồng phục', DIEM_TRU: 5, DANG_SU_DUNG: 1 },
    { id: 2, ID: 2, MA_LOI: 'CARD', name: 'Không đeo thẻ sinh viên', TEN_LOI: 'Không đeo thẻ sinh viên', DIEM_TRU: 3, DANG_SU_DUNG: 1 },
    { id: 3, ID: 3, MA_LOI: 'BOTH', name: 'Không đồng phục và không đeo thẻ', TEN_LOI: 'Không đồng phục và không đeo thẻ', DIEM_TRU: 8, DANG_SU_DUNG: 1 },
    { id: 4, ID: 4, MA_LOI: null, name: 'Trang phục không phù hợp', TEN_LOI: 'Trang phục không phù hợp', DIEM_TRU: 2, DANG_SU_DUNG: 1 },
  ];

  const faculties = [
    { ID: 1, MA_KHOA: 'CNTT', TEN_KHOA: 'Công nghệ thông tin' },
    { ID: 2, MA_KHOA: 'QTKD', TEN_KHOA: 'Quản trị kinh doanh' },
    { ID: 3, MA_KHOA: 'NNA', TEN_KHOA: 'Ngôn ngữ Anh' },
  ];
  const majors = [
    { ID: 1, MA_NGANH: 'KTPM', TEN_NGANH: 'Kỹ thuật phần mềm', MA_KHOA: 'CNTT', TEN_KHOA: 'Công nghệ thông tin' },
    { ID: 2, MA_NGANH: 'HTTT', TEN_NGANH: 'Hệ thống thông tin', MA_KHOA: 'CNTT', TEN_KHOA: 'Công nghệ thông tin' },
    { ID: 3, MA_NGANH: 'MKT', TEN_NGANH: 'Marketing', MA_KHOA: 'QTKD', TEN_KHOA: 'Quản trị kinh doanh' },
    { ID: 4, MA_NGANH: 'TA', TEN_NGANH: 'Tiếng Anh thương mại', MA_KHOA: 'NNA', TEN_KHOA: 'Ngôn ngữ Anh' },
  ];
  const classes = [
    { ID: 1, MA_LOP: 'KTPM22A', MA_NGANH: 'KTPM', MA_KHOA: 'CNTT' },
    { ID: 2, MA_LOP: 'KTPM22B', MA_NGANH: 'KTPM', MA_KHOA: 'CNTT' },
    { ID: 3, MA_LOP: 'HTTT22A', MA_NGANH: 'HTTT', MA_KHOA: 'CNTT' },
    { ID: 4, MA_LOP: 'MKT23A', MA_NGANH: 'MKT', MA_KHOA: 'QTKD' },
    { ID: 5, MA_LOP: 'TA23A', MA_NGANH: 'TA', MA_KHOA: 'NNA' },
  ];
  const names = ['Nguyễn Văn An', 'Trần Thị Bình', 'Lê Hoàng Cường', 'Phạm Thu Dung', 'Hoàng Minh Đức', 'Vũ Thị Hà',
    'Đặng Quốc Huy', 'Bùi Thanh Lan', 'Đỗ Tuấn Minh', 'Ngô Thị Nga'];
  const students = names.map((name, i) => {
    const c = classes[i % classes.length];
    const m = majors.find((x) => x.MA_NGANH === c.MA_NGANH);
    return { ID: i + 1, MA_SV: `SV22${String(1001 + i)}`, HOTEN: name, MA_LOP: c.MA_LOP, MA_NGANH: c.MA_NGANH, TEN_NGANH: m.TEN_NGANH,
      MA_KHOA: m.MA_KHOA, TEN_KHOA: m.TEN_KHOA, NIEN_KHOA: i % 5 > 2 ? 2023 : 2022, SO_VI_PHAM: [2, 0, 1, 0, 3, 0, 0, 1, 0, 0][i] };
  });

  const stamp = (d, h, m) => `2026-10-${String(d).padStart(2, '0')} ${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:12`;
  const statuses = ['MOI', 'MOI', 'DA_XEM', 'MOI', 'DA_XU_LY', 'BO_QUA', 'DA_XU_LY', 'MOI', 'DA_XEM', 'DA_XU_LY'];
  const violations = [];
  for (let i = 0; i < 10; i++) {
    const cam = cameras[i % 4];
    const type = types[i % 3];
    const isFlag = i === 3 || i === 7;
    const st = isFlag ? students[i === 3 ? 0 : 4] : null;
    violations.push({
      id: 248 - i, source: isFlag ? 'CO_DO' : 'CAMERA',
      occurred_at: stamp(31 - Math.floor(i / 3), 9 - (i % 3), 42 - i * 3),
      type: { id: type.id, name: type.name }, area: { id: cam.area.id, name: cam.area.name },
      camera: { id: cam.id, code: cam.code, name: cam.name },
      student: st ? { code: st.MA_SV, name: st.HOTEN, class_name: st.MA_LOP } : null,
      semester: { code: semesters[0].code, name: semesters[0].name },
      evidence: isFlag ? { status: 'none', available: false } : { status: 'available', available: true, expires_at: '2026-11-30 09:42:12' },
      processing: { status: isFlag ? 'DA_XU_LY' : statuses[i], note: isFlag ? 'Đội Cờ Đỏ đã nhắc nhở và ghi nhận tại chỗ.' : '',
        processed_by: statuses[i] === 'MOI' && !isFlag ? null : 'Nhân viên CTSV', processed_at: statuses[i] === 'MOI' && !isFlag ? null : '2026-10-31 10:05:00' },
      confidence: 0.9 - i * 0.02,
    });
  }

  const allDays = Array.from({ length: 31 }, (_, i) => ({ date: `2026-10-${String(i + 1).padStart(2, '0')}`, count: [3, 5, 2, 0, 0, 6, 8, 4, 7, 3][i % 10] }));
  /* Chia tổng theo tỉ lệ cố định, phần dư cộng vào mục đầu để các biểu đồ luôn cộng đúng bằng tổng. */
  const split = (total, ratios) => {
    const parts = ratios.map((r) => Math.floor(total * r));
    parts[0] += total - parts.reduce((a, b) => a + b, 0);
    return parts;
  };
  function makeSummary(byDay) {
    const total = byDay.reduce((sum, d) => sum + d.count, 0);
    const typeParts = split(total, [0.46, 0.33, 0.21]);
    const areaParts = split(total, [0.39, 0.33, 0.28]);
    const hours = [8, 7, 9, 13, 14, 10, 15, 11, 12, 16];
    const hourParts = split(total, [0.23, 0.16, 0.13, 0.11, 0.12, 0.08, 0.07, 0.06, 0.03, 0.01]);
    const byHour = hours.map((hour, i) => ({ hour, count: hourParts[i] })).sort((a, b) => a.hour - b.hour);
    const weekParts = split(total, [0.21, 0.19, 0.18, 0.17, 0.15, 0.1, 0]);
    const statusParts = split(total, [0.56, 0.19, 0.16, 0.09]);
    return {
      total, by_day: byDay,
      by_type: typeParts.map((count, i) => ({ name: types[i].name, count })),
      by_hour: byHour,
      by_area: [0, 2, 1].map((a, i) => ({ name: areas[a].name, count: areaParts[i] })),
      by_weekday: ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ nhật'].map((name, i) => ({ name, count: weekParts[i] })),
      hotspots: [
        { area_name: areas[0].name, hour: 8, count: Math.ceil(hourParts[0] * 0.55), label: `${areas[0].name}, 08:00 - 08:59` },
        { area_name: areas[2].name, hour: 7, count: Math.ceil(hourParts[1] * 0.6), label: `${areas[2].name}, 07:00 - 07:59` },
        { area_name: areas[1].name, hour: 13, count: Math.ceil(hourParts[3] * 0.5), label: `${areas[1].name}, 13:00 - 13:59` },
      ],
      peak_hour: { label: '08:00 - 08:59', count: hourParts[0] },
      peak_area: { name: areas[0].name, count: areaParts[0] },
      by_status: { DA_XU_LY: statusParts[0], MOI: statusParts[1], DA_XEM: statusParts[2], BO_QUA: statusParts[3] },
    };
  }
  const monthSummary = makeSummary(allDays);
  const weekSummary = makeSummary(allDays.slice(-7));

  const accounts = [
    { id: 1, username: 'admin_ctsv', full_name: 'Quản trị hệ thống', role: 'ADMIN', active: true, locked_until: null, last_login_at: '2026-10-31 08:02:11' },
    { id: 2, username: 'nv_ctsv01', full_name: 'Nhân viên CTSV 01', role: 'CTSV', active: true, locked_until: null, last_login_at: '2026-10-31 07:45:30' },
    { id: 3, username: 'nv_ctsv02', full_name: 'Nhân viên CTSV 02', role: 'CTSV', active: true, locked_until: null, last_login_at: '2026-10-30 16:20:02' },
    { id: 4, username: 'nv_ctsv03', full_name: 'Nhân viên CTSV 03', role: 'CTSV', active: false, locked_until: null, last_login_at: '2026-09-28 09:11:47' },
  ];

  const cim = (url) => {
    const r = url.searchParams.get('r');
    const period = url.searchParams.get('period');
    const week = period === 'week';
    const summary = week ? weekSummary : monthSummary;
    const range = { label: week ? 'tuần 25/10/2026 - 31/10/2026' : 'tháng 10/2026' };
    switch (r) {
      case 'dashboard': return { data: { counters: { today: allDays[30].count, week: weekSummary.total, month: monthSummary.total, semester: 416 }, current_semester: semesters[0], range, summary } };
      case 'report-summary': return { data: { range, filters: [{ label: 'Khu vực', value: 'Tất cả' }, { label: 'Camera', value: 'Tất cả' }, { label: 'Loại vi phạm', value: 'Tất cả' }], summary } };
      case 'semesters': return { items: semesters };
      case 'areas': return { items: areas };
      case 'cameras': return { items: cameras };
      case 'violation-types': return { items: types };
      case 'accounts': return { items: accounts };
      case 'violations': {
        const source = url.searchParams.get('source');
        const items = source ? violations.filter((v) => v.source === source) : violations;
        return { items, total: 416, page: 1, pages: 42 };
      }
      case 'violation': {
        const id = Number(url.searchParams.get('id'));
        return { data: violations.find((v) => v.id === id) || violations[0] };
      }
      default: return {};
    }
  };

  const studentsApi = (url) => {
    const action = url.searchParams.get('action');
    const list = { rows: students, total: 1250, page: 1, pages: 125, page_size: 10 };
    const typeRows = types.map((t) => ({ ID: t.ID, TEN_LOI: t.TEN_LOI, DIEM_TRU: t.DIEM_TRU }));
    const areaRows = areas.map((a) => ({ ID: a.id, TEN_KHU_VUC: a.name }));
    switch (action) {
      case 'init': return { csrf_token: 'mau', catalog: { faculties, majors, classes }, statistics: { students: 1250, faculties: 3, classes: 5 }, list };
      case 'list': return list;
      case 'detail': return { student: students.find((s) => String(s.ID) === url.searchParams.get('id')) || students[0] };
      case 'violation-form-meta': return { types: typeRows, areas: areaRows };
      case 'lookup-student': return { student: { ...students[0], LOP: students[0].MA_LOP } };
      case 'violations': return { types: typeRows, areas: areaRows, violations: [
        { ID: 31, NGAY_VIPHAM: '2026-10-28', THOI_DIEM: '2026-10-28 08:15:00', TEN_LOI: types[0].TEN_LOI, TEN_KHU_VUC: areas[0].name, DIEM_TRU: 5, MA_HK: 'HK1-2627', TEN_HK: semesters[0].name },
        { ID: 18, NGAY_VIPHAM: '2026-10-21', THOI_DIEM: '2026-10-21 07:50:00', TEN_LOI: types[1].TEN_LOI, TEN_KHU_VUC: areas[2].name, DIEM_TRU: 3, MA_HK: 'HK1-2627', TEN_HK: semesters[0].name },
      ] };
      default: return {};
    }
  };

  const catalogsApi = () => ({ csrf_token: 'mau', role: 'ADMIN', catalogs: { KHOA: faculties, NGANH: majors, LOP: classes, LOI_VIPHAM: types } });

  const realFetch = window.fetch;
  window.fetch = async (input, options) => {
    const url = new URL(String(input), window.location.href);
    let payload = null;
    if (url.pathname.endsWith('api/cim.php')) payload = cim(url);
    else if (url.pathname.endsWith('api/students.php')) payload = studentsApi(url);
    else if (url.pathname.endsWith('api/catalogs.php')) payload = catalogsApi();
    if (payload === null) return realFetch(input, options);
    return new Response(JSON.stringify(payload), { status: 200, headers: { 'Content-Type': 'application/json' } });
  };

  /* Trạng thái kết nối camera trên bản tĩnh. */
  window.EventSource = class {
    constructor() { setTimeout(() => this.onopen?.(), 0); }
    close() {}
  };

  /* Ảnh bằng chứng trên bản tĩnh là ảnh minh họa, không có người thật. */
  const srcDesc = Object.getOwnPropertyDescriptor(HTMLImageElement.prototype, 'src');
  Object.defineProperty(HTMLImageElement.prototype, 'src', {
    ...srcDesc,
    set(value) { srcDesc.set.call(this, String(value).includes('r=evidence') ? 'assets/images/anh_minh_hoa.svg' : value); },
  });
})();
