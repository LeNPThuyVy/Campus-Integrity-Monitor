<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$cimUser = cim_require_page();
$pageTitle = 'Tổng quan';
include __DIR__ . '/includes/header.php';
?>
<div class="flex flex-col gap-space-md py-space-md pb-space-xl">
  <div class="flex flex-col gap-1">
    <h1 class="font-headline-xl text-headline-xl">Tổng quan vi phạm tác phong</h1>
    <p class="text-on-surface-variant">Số liệu lấy trực tiếp từ SQL Server qua API. <span id="dashboard-range" class="font-semibold text-on-surface"></span></p>
  </div>

  <section class="grid grid-cols-2 gap-space-sm lg:grid-cols-4" aria-label="Số vi phạm theo kỳ">
    <?php foreach ([['today', 'Hôm nay', 'today'], ['week', 'Tuần này', 'date_range'], ['month', 'Tháng này', 'calendar_month'], ['semester', 'Học kỳ hiện tại', 'school']] as [$key, $label, $icon]): ?>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <div class="flex items-center justify-between text-on-surface-variant">
        <span class="font-label-md text-label-md"><?= cim_h($label) ?></span>
        <span class="material-symbols-outlined text-[20px]"><?= cim_h($icon) ?></span>
      </div>
      <p class="mt-2 font-headline-xl text-headline-xl" data-counter="<?= cim_h($key) ?>">…</p>
      <?php if ($key === 'semester'): ?><p class="font-label-sm text-label-sm text-on-surface-variant" id="semester-name"></p><?php endif; ?>
    </article>
    <?php endforeach; ?>
  </section>

  <?php include __DIR__ . '/includes/report_filters.php'; ?>

  <div class="hidden rounded-xl bg-error-container p-space-md text-on-error-container" id="dashboard-error" role="alert"></div>

  <section class="grid grid-cols-1 gap-space-md xl:grid-cols-3">
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm xl:col-span-2">
      <div class="flex items-baseline justify-between">
        <h2 class="font-headline-md text-headline-md">Vi phạm theo ngày</h2>
        <span class="font-label-md text-label-md text-on-surface-variant">Tổng: <strong id="summary-total">…</strong></span>
      </div>
      <div class="relative mt-space-sm h-64"><canvas id="chart-days" aria-label="Biểu đồ vi phạm theo ngày"></canvas></div>
    </article>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <h2 class="font-headline-md text-headline-md">Theo loại lỗi</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-types" aria-label="Biểu đồ theo loại lỗi"></canvas></div>
    </article>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm xl:col-span-2">
      <h2 class="font-headline-md text-headline-md">Khung giờ có vi phạm</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-hours" aria-label="Biểu đồ theo khung giờ"></canvas></div>
    </article>
    <article class="rounded-xl bg-surface-container-lowest p-space-md shadow-sm">
      <h2 class="font-headline-md text-headline-md">Theo khu vực</h2>
      <div class="relative mt-space-sm h-64"><canvas id="chart-areas" aria-label="Biểu đồ theo khu vực"></canvas></div>
    </article>
  </section>

  <section class="grid grid-cols-1 gap-space-md lg:grid-cols-2">
    <article class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
      <h2 class="px-space-md pt-space-md font-headline-md text-headline-md">Khu vực & khung giờ nhiều vi phạm</h2>
      <div class="overflow-x-auto"><table class="mt-space-sm w-full text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline">
          <tr><th class="px-space-md py-2">Khu vực</th><th class="px-space-md py-2">Khung giờ</th><th class="px-space-md py-2 text-right">Số vi phạm</th></tr>
        </thead>
        <tbody id="hotspot-body"></tbody>
      </table></div>
    </article>
    <article class="overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
      <h2 class="px-space-md pt-space-md font-headline-md text-headline-md">Thống kê theo loại lỗi</h2>
      <div class="overflow-x-auto"><table class="mt-space-sm w-full text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline">
          <tr><th class="px-space-md py-2">Loại vi phạm</th><th class="px-space-md py-2 text-right">Số vi phạm</th><th class="px-space-md py-2 text-right">Tỷ lệ</th></tr>
        </thead>
        <tbody id="type-body"></tbody>
      </table></div>
    </article>
  </section>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js" defer></script>
<script src="assets/js/filters.js" defer></script>
<script src="assets/js/charts.js" defer></script>
<script src="assets/js/dashboard.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
