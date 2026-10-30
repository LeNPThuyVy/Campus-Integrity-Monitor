@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-space-md py-space-md pb-space-xl">
  <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-space-md">
    <div class="max-w-2xl">
      <div class="flex items-center gap-space-xs text-primary font-label-sm text-label-sm uppercase tracking-wider mb-1">
        <span class="material-symbols-outlined text-[16px]">security</span>
        <span>Hệ thống giám sát tác phong học đường</span>
      </div>
      <h1 class="font-headline-xl text-headline-xl">Cảnh báo vi phạm & Điều phối Đội Cờ Đỏ</h1>
      <p class="text-on-surface-variant">
        Nhật ký cảnh báo tự động từ Camera AI và biên bản kiểm tra thực tế của Đội Cờ Đỏ tại các giảng đường, phòng học và khu vực.
      </p>
    </div>
    <div class="flex flex-wrap items-center gap-space-sm">
      <button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-primary font-label-md text-label-md transition-all shadow-sm" id="open-excel-btn" type="button">
        <span class="material-symbols-outlined text-[18px]">upload_file</span>
        <span>Nhập từ Excel</span>
      </button>
      <button class="flex items-center gap-space-xs px-4 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary transition-all font-label-md text-label-md shadow-sm" id="open-manual-btn" type="button">
        <span class="material-symbols-outlined text-[18px]">post_add</span>
        <span>Lập biên bản Cờ Đỏ</span>
      </button>
      @if (session('role') === 'ADMIN')
      <button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-error-container text-on-error-container hover:bg-error hover:text-white transition-all font-label-md text-label-md shadow-sm" id="open-reset-btn" type="button" title="Đặt lại vi phạm toàn trường sau học kỳ">
        <span class="material-symbols-outlined text-[18px]">restart_alt</span>
        <span>Reset vi phạm</span>
      </button>
      @endif
    </div>
  </div>

  <!-- Live SSE Status & Source Navigation Tabs -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm border-b border-surface-container pb-space-xs">
    <div class="flex items-center gap-space-xs" id="source-tabs">
      <button class="source-tab-btn flex items-center gap-1.5 px-4 py-2 rounded-xl font-label-md text-label-md transition-all bg-primary text-on-primary shadow-sm" type="button" data-source="ALL">
        <span class="material-symbols-outlined text-[18px]">view_list</span>
        <span>Tất cả</span>
      </button>
      <button class="source-tab-btn flex items-center gap-1.5 px-4 py-2 rounded-xl font-label-md text-label-md transition-all text-on-surface-variant hover:bg-surface-container" type="button" data-source="CAMERA">
        <span class="material-symbols-outlined text-[18px]">videocam</span>
        <span>Cảnh báo Camera AI</span>
        <span class="w-2 h-2 rounded-full bg-primary inline-block"></span>
      </button>
      <button class="source-tab-btn flex items-center gap-1.5 px-4 py-2 rounded-xl font-label-md text-label-md transition-all text-on-surface-variant hover:bg-surface-container" type="button" data-source="CO_DO">
        <span class="material-symbols-outlined text-[18px]">flag</span>
        <span>Biên bản Đội Cờ Đỏ</span>
      </button>
    </div>

    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full text-[12px] font-medium bg-surface-container text-on-surface-variant self-start sm:self-auto" id="camera-sse-status" title="Trạng thái kết nối Server-Sent Events thời gian thực tới API Camera">
      <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0 animate-pulse" id="camera-sse-dot"></span>
      <span id="camera-sse-text">Đang kết nối Camera AI...</span>
    </div>
  </div>

  <form class="grid grid-cols-1 gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8" id="violation-filters">
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Từ ngày</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="date_from"></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Đến ngày</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="date_to"></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Khu vực / Phòng học</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="area_id"><option value="">Tất cả</option></select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Camera</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="camera_id"><option value="">Tất cả</option></select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Loại vi phạm</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="type_id"><option value="">Tất cả</option></select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Trạng thái điều phối</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="status">
        <option value="">Tất cả</option><option value="MOI">Mới báo động (Chờ Cờ Đỏ)</option>
        <option value="DA_XEM">Đã điều Đội Cờ Đỏ</option><option value="DA_XU_LY">Đã kiểm tra tại phòng học</option>
      </select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Sắp xếp</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="sort">
        <option value="newest">Mới nhất trước</option><option value="oldest">Cũ nhất trước</option>
      </select></label>
    <div class="flex items-end gap-space-sm">
      <button class="h-10 flex-1 rounded-lg bg-primary-container px-space-md font-label-md text-on-primary" type="submit">Lọc</button>
      <button class="h-10 rounded-lg bg-surface-container px-space-sm font-label-md" type="reset" title="Xóa bộ lọc">
        <span class="material-symbols-outlined text-[18px] align-middle">filter_alt_off</span>
      </button>
    </div>
  </form>

  <section class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[760px] text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase tracking-wide text-outline">
          <tr>
            <th class="px-space-md py-3">Mã</th><th class="px-space-md py-3">Thời điểm</th><th class="px-space-md py-3">Loại vi phạm</th>
            <th class="px-space-md py-3">Khu vực / camera</th><th class="px-space-md py-3">Ảnh</th>
            <th class="px-space-md py-3">Xử lý</th><th class="px-space-md py-3 text-right">Thao tác</th>
          </tr>
        </thead>
        <tbody id="violation-body"></tbody>
      </table>
    </div>
    <div class="flex flex-col gap-space-sm border-t border-surface-container px-space-md py-space-sm sm:flex-row sm:items-center sm:justify-between">
      <span class="text-on-surface-variant" id="violation-summary"></span>
      <div class="flex items-center gap-space-sm">
        <label class="flex items-center gap-1 text-on-surface-variant">Mỗi trang
          <select class="h-9 rounded-lg bg-surface-container-low px-2" id="page-size">
            <option>10</option><option selected>20</option><option>50</option><option>100</option>
          </select></label>
        <button class="h-9 w-9 rounded-lg bg-surface-container disabled:opacity-40" id="page-prev" type="button" aria-label="Trang trước">
          <span class="material-symbols-outlined text-[18px] align-middle">chevron_left</span></button>
        <span id="page-info" class="min-w-[90px] text-center"></span>
        <button class="h-9 w-9 rounded-lg bg-surface-container disabled:opacity-40" id="page-next" type="button" aria-label="Trang sau">
          <span class="material-symbols-outlined text-[18px] align-middle">chevron_right</span></button>
      </div>
    </div>
  </section>
</div>

<dialog class="w-[min(96vw,880px)] rounded-xl p-0 shadow-xl backdrop:bg-inverse-surface/50" id="violation-dialog">
  <div class="flex items-center justify-between border-b border-surface-container px-space-lg py-space-md">
    <h2 class="font-headline-md text-headline-md" id="detail-title">Chi tiết bản ghi</h2>
    <button class="h-9 w-9 rounded-full hover:bg-surface-container" type="button" id="detail-close" aria-label="Đóng">
      <span class="material-symbols-outlined align-middle">close</span></button>
  </div>
  <div class="grid grid-cols-1 gap-space-lg p-space-lg md:grid-cols-2">
    <div class="flex min-h-[260px] items-center justify-center rounded-lg bg-surface-container-low p-space-sm" id="detail-evidence"></div>
    <div class="flex flex-col gap-space-md">
      <dl class="grid grid-cols-[130px_1fr] gap-y-2" id="detail-fields"></dl>
      <form class="flex flex-col gap-space-sm rounded-lg bg-surface-container-low p-space-md" id="processing-form">
        <h3 class="font-headline-sm text-headline-sm">Điều phối Đội Cờ Đỏ</h3>
        <select class="h-10 rounded-lg bg-surface-container-lowest px-3" name="status">
          <option value="MOI">Mới báo động (Chờ Cờ Đỏ)</option>
          <option value="DA_XEM">Đã điều Đội Cờ Đỏ</option>
          <option value="DA_XU_LY">Đã kiểm tra tại phòng học</option>
        </select>
        <textarea class="min-h-[80px] rounded-lg bg-surface-container-lowest p-3" name="note" maxlength="500" placeholder="Ghi chú điều phối (VD: Cử 2 bạn Cờ Đỏ phòng A201 lúc 07:35)"></textarea>
        <button class="self-start rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Cập nhật điều phối</button>
      </form>
    </div>
  </div>
</dialog>

<!-- Modal: Ghi nhận Biên bản Đội Cờ Đỏ (2 Phương pháp: Nhập từ UI hoặc Import Excel) -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md overflow-y-auto" id="redflag-modal">
  <div class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-xs transition-opacity cursor-pointer" id="redflag-backdrop"></div>
  <div class="relative w-full max-w-3xl bg-surface-container-lowest rounded-xl shadow-[0_20px_25px_-5px_rgba(15,23,42,0.15)] flex flex-col z-10 max-h-[92vh] overflow-hidden my-auto">
    <!-- Modal Header -->
    <div class="p-space-lg bg-surface-container-low flex items-center justify-between">
      <div class="flex items-center gap-space-sm">
        <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shadow-sm">
          <span class="material-symbols-outlined text-[20px]">flag</span>
        </div>
        <div>
          <h2 class="font-headline-lg text-headline-lg text-on-surface">Biên bản vi phạm Đội Cờ Đỏ</h2>
          <p class="font-body-sm text-[12px] text-on-surface-variant">Ghi nhận thông tin sinh viên vi phạm vào cơ sở dữ liệu giám sát</p>
        </div>
      </div>
      <button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-redflag-modal-btn" type="button">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>

    <!-- Tab Switcher -->
    <div class="flex border-b border-surface-container px-space-lg bg-surface-container-low/60">
      <button class="py-2.5 px-4 font-label-md text-label-md font-semibold text-primary border-b-2 border-primary flex items-center gap-1.5 transition-colors" id="tab-manual-btn" type="button">
        <span class="material-symbols-outlined text-[18px]">edit_note</span>
        <span>Phương pháp 1: Nhập trực tiếp từ Web</span>
      </button>
      <button class="py-2.5 px-4 font-label-md text-label-md text-on-surface-variant hover:text-primary flex items-center gap-1.5 transition-colors" id="tab-excel-btn" type="button">
        <span class="material-symbols-outlined text-[18px]">table_view</span>
        <span>Phương pháp 2: Nhập từ file Excel (.xlsx)</span>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-space-lg overflow-y-auto flex flex-col gap-space-md">
      <!-- TAB 1: NHẬP TRỰC TIẾP TỪ WEB -->
      <form class="flex flex-col gap-space-md" id="manual-record-form">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-sm">
          <div class="flex flex-col gap-1">
            <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-student-code">
              Mã số sinh viên (MSSV) <span class="text-error">*</span>
            </label>
            <div class="relative">
              <input class="w-full h-10 px-3 pl-9 rounded-lg bg-surface-container-low text-on-surface font-data-mono text-body-md focus:bg-surface-container-lowest focus:outline-none transition-all" id="rf-student-code" placeholder="VD: 21110823" required autocomplete="off">
              <span class="material-symbols-outlined absolute left-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline">badge</span>
            </div>
            <p class="font-body-sm text-[11px] text-secondary hidden" id="rf-lookup-msg"></p>
          </div>

          <div class="flex flex-col gap-1">
            <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-student-name">
              Họ và tên sinh viên (Tự động hiển thị) <span class="text-error">*</span>
            </label>
            <input class="w-full h-10 px-3 rounded-lg bg-surface-container text-on-surface font-body-md font-semibold cursor-not-allowed" id="rf-student-name" placeholder="Tự động hiện tên khi nhập MSSV..." readonly required>
          </div>
        </div>

        <!-- Student Meta preview card (hidden until student matched) -->
        <div class="hidden p-space-sm rounded-xl bg-surface-container-low/70 border border-surface-container flex-wrap items-center gap-x-4 gap-y-1 text-[12px]" id="rf-student-card">
          <div class="flex items-center gap-1 text-on-surface-variant">
            <span class="material-symbols-outlined text-[16px] text-primary">school</span>
            <span>Lớp: <strong class="text-on-surface font-data-mono" id="rf-student-class">—</strong></span>
          </div>
          <div class="flex items-center gap-1 text-on-surface-variant">
            <span class="material-symbols-outlined text-[16px] text-primary">account_balance</span>
            <span>Khoa: <strong class="text-on-surface" id="rf-student-faculty">—</strong></span>
          </div>
          <div class="flex items-center gap-1 text-on-surface-variant">
            <span class="material-symbols-outlined text-[16px] text-primary">history</span>
            <span>Đã vi phạm: <strong class="text-error font-semibold" id="rf-student-violation-count">0</strong> lần</span>
          </div>
        </div>

        <!-- Row: Violation Type & Occurred At -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-sm">
          <div class="flex flex-col gap-1">
            <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-type-id">
              Lỗi bị ghi nhận <span class="text-error">*</span>
            </label>
            <select class="h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none" id="rf-type-id" required>
              <option value="">-- Chọn lỗi vi phạm --</option>
            </select>
          </div>

          <div class="flex flex-col gap-1">
            <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-datetime">
              Thời gian bị ghi nhận <span class="text-error">*</span>
            </label>
            <input class="h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none" id="rf-datetime" type="datetime-local" required>
          </div>
        </div>

        <!-- Row: Area & Room / Specific Location -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-space-sm">
          <div class="flex flex-col gap-1">
            <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-area-id">
              Khu vực vi phạm
            </label>
            <select class="h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none" id="rf-area-id">
              <option value="">-- Chọn khu vực --</option>
            </select>
          </div>

          <div class="flex flex-col gap-1">
            <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-room">
              Vị trí bị ghi nhận / Phòng học <span class="text-error">*</span>
            </label>
            <input class="h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none" id="rf-room" placeholder="VD: Phòng A201, Giảng đường B..." required>
          </div>
        </div>

        <div class="flex flex-col gap-1">
          <label class="font-label-sm text-label-sm font-semibold text-on-surface" for="rf-note">
            Ghi chú biên bản (tùy chọn)
          </label>
          <textarea class="min-h-[70px] p-2.5 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none" id="rf-note" maxlength="500" placeholder="VD: Đội Cờ Đỏ kiểm tra đột xuất tại phòng học, sinh viên không đeo thẻ theo quy định."></textarea>
        </div>

        <div class="flex items-center justify-end gap-space-sm pt-2">
          <button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-manual-record-btn" type="button">
            Hủy bỏ
          </button>
          <button class="px-5 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5" id="submit-manual-record-btn" type="submit">
            <span class="material-symbols-outlined text-[18px]">save</span>
            <span>Lưu biên bản vi phạm</span>
          </button>
        </div>
      </form>

      <!-- TAB 2: IMPORT TỪ FILE EXCEL -->
      <div class="hidden flex-col gap-space-md" id="excel-import-tab">
        <div class="p-space-sm rounded-lg bg-surface-container-low flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2 text-[12px]">
          <div class="flex items-center gap-2 text-on-surface-variant">
            <span class="material-symbols-outlined text-[18px] text-primary">info</span>
            <span>File Excel gồm 5 cột: <strong>Mã sinh viên, Họ tên, Vị trí bị ghi nhận, Thời gian bị ghi nhận, Lỗi bị ghi nhận</strong></span>
          </div>
          <button class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-label-sm text-[11px] font-semibold transition-colors shrink-0" id="rf-download-template-btn" type="button">
            <span class="material-symbols-outlined text-[16px]">download</span>
            <span>Tải file Excel mẫu (.xlsx)</span>
          </button>
        </div>

        <!-- Drop zone -->
        <div class="p-6 rounded-xl bg-surface-container-low/50 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-surface-container-low transition-all border-2 border-dashed border-surface-container-high" id="rf-drop-zone">
          <input accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" id="rf-excel-file" type="file">
          <div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-primary mb-2">
            <span class="material-symbols-outlined text-[24px]">cloud_upload</span>
          </div>
          <p class="font-label-md text-label-md text-on-surface font-semibold">Kéo thả file Excel biên bản vào đây hoặc nhấn để duyệt</p>
          <p class="font-body-sm text-[11px] text-on-surface-variant mt-1">Định dạng .xlsx (Tối đa 15MB). File đã chọn: <span class="font-data-mono font-semibold text-primary" id="rf-file-name">chưa chọn</span></p>
        </div>

        <!-- Preview Section -->
        <div class="hidden flex-col gap-space-xs" id="rf-preview-section">
          <div class="flex items-center justify-between">
            <span class="font-label-md text-label-md font-semibold text-on-surface">Xem trước &amp; Đối soát danh sách biên bản</span>
            <span class="px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-data-mono text-[11px] font-semibold" id="rf-preview-counts">0 dòng</span>
          </div>
          <div class="overflow-x-auto rounded-xl bg-surface-container-low/40 max-h-[260px] overflow-y-auto">
            <table class="w-full text-left text-[12px] border-collapse min-w-[620px]">
              <thead class="h-8 bg-surface-container-low text-outline font-label-sm text-[11px] uppercase tracking-wider sticky top-0">
                <tr>
                  <th class="px-3 py-1.5 w-12 text-center">Dòng</th>
                  <th class="px-3 py-1.5 w-24">MSSV</th>
                  <th class="px-3 py-1.5">Họ tên</th>
                  <th class="px-3 py-1.5">Vị trí ghi nhận</th>
                  <th class="px-3 py-1.5">Thời gian</th>
                  <th class="px-3 py-1.5">Lỗi ghi nhận</th>
                  <th class="px-3 py-1.5 text-right">Trạng thái</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-surface-container font-body-sm text-body-sm" id="rf-preview-body"></tbody>
            </table>
          </div>
        </div>

        <div class="flex items-center justify-end gap-space-sm pt-2">
          <button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-excel-import-btn" type="button">
            Hủy bỏ
          </button>
          <button class="px-5 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5 disabled:opacity-40" id="rf-commit-import-btn" disabled type="button">
            <span class="material-symbols-outlined text-[18px]">cloud_done</span>
            <span id="rf-commit-label">Chọn file Excel để xem trước</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Xác thực Mật mã Reset Toàn bộ Vi phạm -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md overflow-y-auto" id="reset-violations-modal">
  <div class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-xs transition-opacity cursor-pointer" id="reset-modal-backdrop"></div>
  <div class="relative w-full max-w-md bg-surface-container-lowest rounded-xl shadow-[0_20px_25px_-5px_rgba(15,23,42,0.15)] flex flex-col z-10 overflow-hidden my-auto">
    <div class="p-space-lg bg-surface-container-low flex items-center justify-between border-b border-surface-container">
      <div class="flex items-center gap-space-sm">
        <div class="w-10 h-10 rounded-xl bg-error-container text-on-error-container flex items-center justify-center shadow-sm">
          <span class="material-symbols-outlined text-[22px]">restart_alt</span>
        </div>
        <div>
          <h2 class="font-headline-sm text-headline-sm text-error">Reset toàn bộ vi phạm</h2>
          <p class="font-body-sm text-[11px] text-on-surface-variant">Bắt đầu chu kỳ học kỳ mới</p>
        </div>
      </div>
      <button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-reset-modal-btn" type="button">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>

    <form class="p-space-lg flex flex-col gap-space-md" id="reset-violations-form">
      <div class="p-space-sm rounded-lg bg-error-container/20 border border-error/20 flex items-start gap-2 text-error font-body-sm text-[12px]">
        <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">warning</span>
        <div>
          <p class="font-semibold">Cảnh báo hành động không thể hoàn tác!</p>
          <p class="mt-0.5">Toàn bộ bản ghi biên bản vi phạm và cảnh báo camera sẽ bị xóa vĩnh viễn. Số lượt vi phạm của tất cả sinh viên sẽ được trả về <strong>0 lượt vi phạm</strong>.</p>
        </div>
      </div>

      <div class="flex flex-col gap-1.5">
        <label class="font-label-sm text-label-sm text-on-surface font-semibold" for="reset-password-input">
          Nhập mật mã xác nhận xóa vi phạm <span class="text-error">*</span>
        </label>
        <div class="relative">
          <input class="w-full h-10 px-3 pr-10 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:bg-surface-container-lowest focus:outline-none transition-all" id="reset-password-input" type="password" placeholder="Nhập mật mã bảo mật để reset..." required autocomplete="current-password">
          <button class="absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface flex items-center" id="toggle-reset-password-btn" type="button">
            <span class="material-symbols-outlined text-[18px]" id="toggle-reset-password-icon">visibility</span>
          </button>
        </div>
        <p class="font-body-sm text-[11px] text-error hidden" id="reset-password-error"></p>
      </div>

      <div class="p-space-sm rounded-lg bg-surface-container-low border border-surface-container flex flex-col gap-2">
        <label class="flex items-center gap-2 text-on-surface font-body-sm text-[12px] cursor-pointer">
          <input class="rounded text-primary focus:ring-primary w-4 h-4" id="reset-student-violations-check" type="checkbox" checked disabled>
          <span>Đưa số lượt vi phạm của toàn bộ sinh viên về <strong>0 vi phạm</strong> (Bảng CT_VIPHAM)</span>
        </label>
        <label class="flex items-center gap-2 text-on-surface-variant font-body-sm text-[12px] cursor-pointer">
          <input class="rounded text-error focus:ring-error w-4 h-4" id="reset-include-camera-check" type="checkbox">
          <span>Xóa kèm cả lịch sử ảnh và cảnh báo Camera AI (mặc định giữ lại để đối soát thống kê)</span>
        </label>
      </div>

      <div class="flex items-center justify-end gap-space-sm pt-2">
        <button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-reset-btn" type="button">
          Hủy bỏ
        </button>
        <button class="px-5 py-2 rounded-xl bg-error text-on-error hover:bg-error/90 font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5" id="confirm-reset-btn" type="submit">
          <span class="material-symbols-outlined text-[18px]">delete_forever</span>
          <span>Xác nhận xóa &amp; Reset về 0</span>
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/violations.js') }}?v={{ time() }}" defer></script>
@endpush
