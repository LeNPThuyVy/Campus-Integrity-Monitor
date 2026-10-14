<?php
declare(strict_types=1);

/** Shared filter bar for the dashboard and report pages. Set $filterExtended = true for camera/type filters. */
$filterExtended = $filterExtended ?? false;
?>
<form class="grid grid-cols-1 gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm sm:grid-cols-2 xl:grid-cols-6" id="report-filters">
  <label class="flex flex-col gap-1">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Kỳ thống kê</span>
    <select class="h-10 rounded-lg bg-surface-container-low px-3" name="period">
      <option value="day">Theo ngày</option>
      <option value="week">Theo tuần</option>
      <option value="month" selected>Theo tháng</option>
      <option value="semester">Theo học kỳ</option>
      <option value="custom">Khoảng tùy chọn</option>
    </select>
  </label>
  <label class="flex flex-col gap-1" data-period="day week">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Ngày / tuần chứa ngày</span>
    <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="anchor">
  </label>
  <label class="flex flex-col gap-1" data-period="month">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Tháng</span>
    <input class="h-10 rounded-lg bg-surface-container-low px-3" type="month" name="month">
  </label>
  <label class="flex flex-col gap-1" data-period="semester">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Học kỳ</span>
    <select class="h-10 rounded-lg bg-surface-container-low px-3" name="semester_id">
      <option value="">Học kỳ hiện tại</option>
    </select>
  </label>
  <label class="flex flex-col gap-1" data-period="custom">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Từ ngày</span>
    <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="date_from">
  </label>
  <label class="flex flex-col gap-1" data-period="custom">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Đến ngày</span>
    <input class="h-10 rounded-lg bg-surface-container-low px-3" type="date" name="date_to">
  </label>
  <label class="flex flex-col gap-1">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Khu vực</span>
    <select class="h-10 rounded-lg bg-surface-container-low px-3" name="area_id">
      <option value="">Tất cả khu vực</option>
    </select>
  </label>
  <?php if ($filterExtended): ?>
  <label class="flex flex-col gap-1">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Camera</span>
    <select class="h-10 rounded-lg bg-surface-container-low px-3" name="camera_id">
      <option value="">Tất cả camera</option>
    </select>
  </label>
  <label class="flex flex-col gap-1">
    <span class="font-label-sm text-label-sm text-on-surface-variant">Loại vi phạm</span>
    <select class="h-10 rounded-lg bg-surface-container-low px-3" name="type_id">
      <option value="">Tất cả loại</option>
    </select>
  </label>
  <?php endif; ?>
  <div class="flex items-end">
    <button class="h-10 w-full rounded-lg bg-primary-container px-space-md font-label-md text-on-primary" type="submit">Áp dụng bộ lọc</button>
  </div>
</form>
