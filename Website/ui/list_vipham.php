<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$cimUser = cim_require_page();
$pageTitle = 'Bản ghi vi phạm';
include __DIR__ . '/includes/header.php';
?>
<div class="flex flex-col gap-space-md py-space-md pb-space-xl">
  <div>
    <h1 class="font-headline-xl text-headline-xl">Tra cứu bản ghi vi phạm</h1>
    <p class="text-on-surface-variant">
      Bản ghi do Desktop/AI gửi lên. Hệ thống không gắn MSSV hay họ tên; CTSV tự đối chiếu danh tính và xử lý điểm rèn luyện bên ngoài hệ thống.
    </p>
  </div>

  <form class="grid grid-cols-1 gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8" id="violation-filters">
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Từ ngày</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="date_from"></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Đến ngày</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="date_to"></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Khu vực</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="area_id"><option value="">Tất cả</option></select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Camera</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="camera_id"><option value="">Tất cả</option></select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Loại vi phạm</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="type_id"><option value="">Tất cả</option></select></label>
    <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm text-on-surface-variant">Trạng thái xử lý</span>
      <select class="h-10 rounded-lg bg-surface-container-low px-3" name="status">
        <option value="">Tất cả</option><option value="MOI">Mới</option><option value="DA_XEM">Đã xem</option>
        <option value="DA_XU_LY">Đã xử lý</option><option value="BO_QUA">Bỏ qua (cảnh báo sai)</option>
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
        <h3 class="font-headline-sm text-headline-sm">Cập nhật xử lý</h3>
        <select class="h-10 rounded-lg bg-surface-container-lowest px-3" name="status">
          <option value="MOI">Mới</option><option value="DA_XEM">Đã xem</option>
          <option value="DA_XU_LY">Đã xử lý</option><option value="BO_QUA">Bỏ qua (cảnh báo sai)</option>
        </select>
        <textarea class="min-h-[80px] rounded-lg bg-surface-container-lowest p-3" name="note" maxlength="500" placeholder="Ghi chú nội bộ (không ghi MSSV/họ tên vào bản ghi AI)"></textarea>
        <button class="self-start rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Lưu trạng thái</button>
      </form>
    </div>
  </div>
</dialog>
<script src="assets/js/violations.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
