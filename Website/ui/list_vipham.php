<?php include 'includes/header.php'; ?>
<div class="flex flex-col w-full">
<!-- Top Action & Overview Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md mb-space-lg">
<div class="flex flex-col">
<div class="flex items-center gap-space-sm mb-space-xs">
<h1 class="font-headline-xl text-headline-xl text-on-surface tracking-tight">Danh sách vi phạm</h1>
<span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-fixed-variant font-label-sm text-label-sm">
<span class="h-1.5 w-1.5 rounded-full bg-secondary animate-pulse"></span>
          Hôm nay: 42 lượt ghi nhận
        </span>
</div>
<p class="font-body-md text-body-md text-on-surface-variant">Dữ liệu nhận diện tự động từ hệ thống Camera AI giám sát tác phong trong phòng học (Giảng đường &amp; Phòng máy)</p>
</div>
<!-- Actions -->
<div class="flex items-center gap-space-sm flex-wrap">
<button class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface rounded-lg shadow-sm transition-all text-label-md font-label-md" type="button">
<span class="material-symbols-outlined text-[18px] text-secondary">description</span>
        Xuất Excel
      </button>
<button class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container-low hover:text-on-surface rounded-lg shadow-sm transition-all text-label-md font-label-md" type="button">
<span class="material-symbols-outlined text-[18px] text-error">picture_as_pdf</span>
        Xuất PDF
      </button>
<button class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary-container text-on-primary hover:bg-primary rounded-lg shadow-sm transition-all text-label-md font-label-md" type="button">
<span class="material-symbols-outlined text-[18px]">sync</span>
        Làm mới dữ liệu
      </button>
</div>
</div>
<!-- Filter Container Card -->
<div class="bg-surface-container-lowest rounded-lg p-space-md shadow-sm mb-space-lg flex flex-col gap-space-md">
<!-- Row 1: Time, Range Chips & Inputs -->
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
<div class="flex flex-wrap items-center gap-space-sm">
<span class="font-label-md text-label-md text-on-surface-variant mr-1">Khoảng thời gian:</span>
<div class="inline-flex p-0.5 bg-surface-container-low rounded-lg gap-1" id="dateFilterChips">
<button class="date-chip px-3 py-1 text-label-sm font-label-sm rounded-lg bg-primary-container text-on-primary shadow-xs transition-colors" data-chip="today" type="button">Hôm nay</button>
<button class="date-chip px-3 py-1 text-label-sm font-label-sm rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest transition-colors" data-chip="week" type="button">Tuần này</button>
<button class="date-chip px-3 py-1 text-label-sm font-label-sm rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest transition-colors" data-chip="month" type="button">Tháng này</button>
<button class="date-chip px-3 py-1 text-label-sm font-label-sm rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-highest transition-colors" data-chip="semester" type="button">Học kỳ I (2024-2025)</button>
</div>
</div>
<div class="flex flex-wrap items-center gap-space-sm">
<div class="flex items-center gap-2 bg-surface-container-low px-2.5 py-1.5 rounded-lg">
<span class="material-symbols-outlined text-[18px] text-outline">calendar_today</span>
<span class="text-body-sm font-body-sm text-outline">Từ</span>
<input class="w-20 bg-transparent text-label-md font-label-md text-on-surface focus:outline-none text-center" type="text" value="24/10/2024">
<span class="text-body-sm font-body-sm text-outline">- Đến</span>
<input class="w-20 bg-transparent text-label-md font-label-md text-on-surface focus:outline-none text-center" type="text" value="31/10/2024">
</div>
<div class="flex items-center gap-1.5 bg-surface-container-low px-2.5 py-1.5 rounded-lg">
<span class="material-symbols-outlined text-[18px] text-outline">schedule</span>
<select class="bg-transparent font-label-md text-label-md text-on-surface focus:outline-none pr-2 cursor-pointer"><option class="">Tất cả phòng học</option><option class="">Phòng A.201 (Cam 01)</option><option class="">Phòng A.304 (Cam 02)</option><option class="">Phòng B.102 (Cam 03)</option><option class="">Phòng B.405 (Cam 04)</option><option class="">Phòng máy C.501 (Cam 05)</option><option class="">Giảng đường C.101 (Cam 06)</option></select>
</div>
</div>
</div>
<!-- Row 2: Location, Violation Type & View Toggle -->
<div class="flex flex-col lg:flex-row lg:items-center justify-between gap-space-md pt-space-md bg-surface-container-lowest">
<div class="flex flex-wrap items-center gap-space-md">
<!-- Area / Camera selector -->
<div class="flex items-center gap-2 bg-surface-container-low px-3 py-1.5 rounded-lg">
<span class="material-symbols-outlined text-[18px] text-primary">videocam</span>
<select class="bg-transparent font-label-md text-label-md text-on-surface focus:outline-none cursor-pointer"><option class="">Tất cả phòng học</option><option class="">Phòng A.201 (Cam 01)</option><option class="">Phòng A.304 (Cam 02)</option><option class="">Phòng B.102 (Cam 03)</option><option class="">Phòng B.405 (Cam 04)</option><option class="">Phòng máy C.501 (Cam 05)</option><option class="">Giảng đường C.101 (Cam 06)</option></select>
</div>
<!-- Violation types segmented pills -->
<div class="flex flex-wrap items-center gap-1.5" id="violationPills">
<button class="filter-pill px-3 py-1 rounded-full bg-surface-container-high text-on-surface font-label-sm text-label-sm transition-colors" type="button">
            Tất cả <span class="font-data-mono text-data-mono ml-1 opacity-75">1,280</span>
</button>
<button class="filter-pill px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-sm text-label-sm flex items-center gap-1.5 transition-colors" type="button">
<span class="w-2 h-2 rounded-full bg-tertiary-container"></span>
            Không đeo thẻ <span class="font-data-mono text-data-mono opacity-75">756</span>
</button>
<button class="filter-pill px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-sm text-label-sm flex items-center gap-1.5 transition-colors" type="button">
<span class="w-2 h-2 rounded-full bg-error"></span>
            Không mặc đồng phục <span class="font-data-mono text-data-mono opacity-75">412</span>
</button>
<button class="filter-pill px-3 py-1 rounded-full bg-surface-container-low text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-sm text-label-sm flex items-center gap-1.5 transition-colors" type="button">
<span class="flex -space-x-1"><span class="w-2 h-2 rounded-full bg-tertiary-container"></span><span class="w-2 h-2 rounded-full bg-error"></span></span>
            Cả hai lỗi <span class="font-data-mono text-data-mono opacity-75">112</span>
</button>
</div>
</div>
<!-- View switcher & Counter -->
<div class="flex items-center justify-between lg:justify-end gap-space-md">
<span class="font-body-sm text-body-sm text-on-surface-variant">Hiển thị <strong class="text-on-surface">12</strong> / 1,280 bản ghi</span>
<div class="flex items-center p-0.5 bg-surface-container-low rounded-lg">
<button class="flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container-lowest text-primary shadow-xs font-label-sm text-label-sm" id="btnViewGrid" type="button">
<span class="material-symbols-outlined text-[16px]">grid_view</span>
            Lưới ảnh
          </button>
<button class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-on-surface-variant hover:text-on-surface font-label-sm text-label-sm" id="btnViewTable" type="button">
<span class="material-symbols-outlined text-[16px]">view_list</span>
            Bảng dữ liệu
          </button>
</div>
</div>
</div>
</div>
<!-- MAIN VIEW: Grid Section -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-space-md" id="violationGridView">
<!-- Card 1 -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng A.201 - Tòa A (Cam 01)" data-id="VP-20241031-0142" data-retention="Còn 29 ngày lưu trữ" data-time="07:18:24 - 31/10/2024" data-type="card"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Phòng A.201 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 01 #0142</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 07:18:24 - 31/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng A.201 - Tòa A (Cam 01)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">badge</span> Không đeo thẻ</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-secondary font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 29 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 2 -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng A.305 - Tòa A (Cam 03)" data-id="VP-20241031-0139" data-retention="Còn 29 ngày lưu trữ" data-time="07:22:15 - 31/10/2024" data-type="uniform"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Phòng A.305 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 03 #0139</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 07:22:15 - 31/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng A.305 - Tòa A (Cam 03)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">checkroom</span> Không mặc đồng phục</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-secondary font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 29 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 3 (Both errors) -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Giảng đường B.102 - Tòa B (Cam 01)" data-id="VP-20241031-0131" data-retention="Còn 29 ngày lưu trữ" data-time="07:30:02 - 31/10/2024" data-type="both"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Giảng đường B.102 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 01 #0131</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 07:30:02 - 31/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Giảng đường B.102 - Tòa B (Cam 01)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">badge</span> Không đeo thẻ</span><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">checkroom</span> Không đồng phục</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-secondary font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 29 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 4 -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng máy C.402 - Tòa C (Cam 04)" data-id="VP-20241030-0089" data-retention="Còn 28 ngày lưu trữ" data-time="08:05:41 - 30/10/2024" data-type="card"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Phòng máy C.402 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 04 #0089</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 08:05:41 - 30/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng máy C.402 - Tòa C (Cam 04)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">badge</span> Không đeo thẻ</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-secondary font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 28 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 5 -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng B.204 - Tòa B (Cam 02)" data-id="VP-20241028-0051" data-retention="Còn 26 ngày lưu trữ" data-time="13:12:09 - 28/10/2024" data-type="uniform"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Phòng B.204 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 02 #0051</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 13:12:09 - 28/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng B.204 - Tòa B (Cam 02)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">checkroom</span> Không mặc đồng phục</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-secondary font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 26 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 6 -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng A.108 - Tòa A (Cam 03)" data-id="VP-20241025-0018" data-retention="Còn 23 ngày lưu trữ" data-time="13:45:30 - 25/10/2024" data-type="card"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Phòng A.108 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 03 #0018</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 13:45:30 - 25/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng A.108 - Tòa A (Cam 03)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">badge</span> Không đeo thẻ</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-secondary font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 23 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 7 -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng B.301 - Tòa B (Cam 01)" data-id="VP-20241015-0044" data-retention="Còn 13 ngày lưu trữ" data-time="07:15:00 - 15/10/2024" data-type="uniform"><div class="relative w-full aspect-[4/3] bg-surface-container-low border-b border-outline-variant/30 flex flex-col items-center justify-center p-4 text-center overflow-hidden"><div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-outline mb-1.5"><span class="material-symbols-outlined text-[22px]">photo_camera</span></div><span class="font-label-sm text-label-sm text-on-surface font-medium">Ảnh ghi nhận camera AI</span><span class="font-body-sm text-[11px] text-outline mt-0.5">Phòng B.301 - Giám sát tự động</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-inverse-surface/80 backdrop-blur text-inverse-on-surface font-data-mono text-label-sm">CAM 01 #0044</div><div class="absolute inset-0 bg-primary/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"><span class="px-3 py-1.5 rounded-lg bg-surface-container-lowest text-primary font-label-sm text-label-sm shadow-md flex items-center gap-1"><span class="material-symbols-outlined text-[16px]">visibility</span> Xem chi tiết</span></div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 07:15:00 - 15/10/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng B.301 - Tòa B (Cam 01)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">checkroom</span> Không mặc đồng phục</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-tertiary-container font-medium flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-tertiary-container"></span> Còn 13 ngày lưu trữ</span><span class="text-primary group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 8: EXPIRED RECORD (> 30 days) -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Giảng đường A.101 - Tòa A (Cam 01)" data-id="VP-20240920-0012" data-retention="Đã hết hạn lưu trữ" data-time="07:05:12 - 20/09/2024" data-type="card"><div class="relative w-full aspect-[4/3] bg-surface-container-low flex flex-col items-center justify-center p-4 text-center"><span class="material-symbols-outlined text-[36px] text-outline mb-1">videocam_off</span><span class="font-label-md text-label-md text-on-surface-variant">Ảnh đã hết hạn lưu trữ</span><span class="font-body-sm text-[11px] text-outline mt-1 leading-tight max-w-[200px]">Đã tự động hủy theo quy định bảo mật 30 ngày</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-surface-dim text-on-surface-variant font-data-mono text-label-sm">CAM 01 #0012</div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 07:05:12 - 20/09/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Giảng đường A.101 - Tòa A (Cam 01)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">badge</span> Không đeo thẻ</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-outline font-medium flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">lock_clock</span> Đã hết hạn lưu</span><span class="text-on-surface-variant group-hover:underline">Chi tiết →</span></div></div></div>
<!-- Card 9: EXPIRED RECORD (> 30 days) -->
<div class="violation-card group bg-surface-container-lowest rounded-lg shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden cursor-pointer" data-cam="Phòng A.402 - Tòa A (Cam 06)" data-id="VP-20240918-0005" data-retention="Đã hết hạn lưu trữ" data-time="09:20:18 - 18/09/2024" data-type="uniform"><div class="relative w-full aspect-[4/3] bg-surface-container-low flex flex-col items-center justify-center p-4 text-center"><span class="material-symbols-outlined text-[36px] text-outline mb-1">lock</span><span class="font-label-md text-label-md text-on-surface-variant">Ảnh đã hết hạn lưu trữ</span><span class="font-body-sm text-[11px] text-outline mt-1 leading-tight max-w-[200px]">Đã tự động hủy theo quy định bảo mật 30 ngày</span><div class="absolute top-2 left-2 px-2 py-0.5 rounded-full bg-surface-dim text-on-surface-variant font-data-mono text-label-sm">CAM 06 #0005</div></div><div class="p-space-md flex flex-col flex-1 justify-between gap-space-sm"><div><div class="flex items-center justify-between text-body-sm font-body-sm text-on-surface-variant mb-1"><span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px]">schedule</span> 09:20:18 - 18/09/2024</span></div><p class="font-label-lg text-label-lg text-on-surface flex items-center gap-1 mb-2"><span class="material-symbols-outlined text-[16px] text-outline">location_on</span> Phòng A.402 - Tòa A (Cam 06)</p><div class="flex flex-wrap gap-1.5 mb-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm"><span class="material-symbols-outlined text-[13px]">checkroom</span> Không mặc đồng phục</span></div></div><div class="flex items-center justify-between pt-space-xs text-label-sm font-label-sm"><span class="text-outline font-medium flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">lock_clock</span> Đã hết hạn lưu</span><span class="text-on-surface-variant group-hover:underline">Chi tiết →</span></div></div></div>
</div>
<!-- MAIN VIEW: Table Section (Hidden by default, togglable) -->
<div class="hidden bg-surface-container-lowest rounded-lg shadow-sm overflow-hidden" id="violationTableView">
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low text-on-surface-variant font-label-sm text-label-sm h-10">
<th class="pl-4 pr-2 w-10">
<input class="w-4 h-4 rounded text-primary focus:ring-0" type="checkbox">
</th>
<th class="px-3 py-2 w-28">Ảnh trích xuất</th>
<th class="px-4 py-2">Mã vi phạm</th>
<th class="px-4 py-2">Thời gian ghi nhận</th>
<th class="px-4 py-2">Khu vực / Camera</th>
<th class="px-4 py-2">Loại vi phạm</th>
<th class="px-4 py-2">Trạng thái lưu trữ</th>
<th class="px-4 py-2 text-right pr-4">Thao tác</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-container font-body-md text-body-md"><tr class="hover:bg-surface-container-low/70 transition-colors cursor-pointer" onclick="openDetailModal('VP-20241031-0142', '07:18:24 - 31/10/2024', 'Phòng A.201 - Tòa A (Cam 01)')"><td class="pl-4 pr-2 py-2" onclick="event.stopPropagation()"><input class="w-4 h-4 rounded text-primary focus:ring-0" type="checkbox"></td><td class="px-3 py-2"><div class="w-16 h-11 rounded bg-surface-container flex items-center justify-center text-outline"><span class="material-symbols-outlined text-[18px]">photo_camera</span></div></td><td class="px-4 py-2 font-data-mono text-data-mono text-primary font-semibold">VP-20241031-0142</td><td class="px-4 py-2 text-on-surface">07:18:24 - 31/10/2024</td><td class="px-4 py-2 text-on-surface">Phòng A.201 - Tòa A (Cam 01)</td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm"><span class="material-symbols-outlined text-[12px]">badge</span> Không đeo thẻ</span></td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 text-secondary font-label-sm text-label-sm font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 29 ngày</span></td><td class="px-4 py-2 text-right pr-4" onclick="event.stopPropagation()"><button class="p-1 hover:bg-surface-container rounded text-primary" onclick="openDetailModal('VP-20241031-0142', '07:18:24 - 31/10/2024', 'Phòng A.201 - Tòa A (Cam 01)')" title="Xem chi tiết" type="button"><span class="material-symbols-outlined text-[18px]">visibility</span></button><button class="p-1 hover:bg-surface-container rounded text-on-surface-variant" title="Tải ảnh" type="button"><span class="material-symbols-outlined text-[18px]">download</span></button></td></tr><tr class="hover:bg-surface-container-low/70 transition-colors cursor-pointer" onclick="openDetailModal('VP-20241031-0139', '07:22:15 - 31/10/2024', 'Phòng A.305 - Tòa A (Cam 03)')"><td class="pl-4 pr-2 py-2" onclick="event.stopPropagation()"><input class="w-4 h-4 rounded text-primary focus:ring-0" type="checkbox"></td><td class="px-3 py-2"><div class="w-16 h-11 rounded bg-surface-container flex items-center justify-center text-outline"><span class="material-symbols-outlined text-[18px]">photo_camera</span></div></td><td class="px-4 py-2 font-data-mono text-data-mono text-primary font-semibold">VP-20241031-0139</td><td class="px-4 py-2 text-on-surface">07:22:15 - 31/10/2024</td><td class="px-4 py-2 text-on-surface">Phòng A.305 - Tòa A (Cam 03)</td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm"><span class="material-symbols-outlined text-[12px]">checkroom</span> Không mặc đồng phục</span></td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 text-secondary font-label-sm text-label-sm font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 29 ngày</span></td><td class="px-4 py-2 text-right pr-4" onclick="event.stopPropagation()"><button class="p-1 hover:bg-surface-container rounded text-primary" onclick="openDetailModal('VP-20241031-0139', '07:22:15 - 31/10/2024', 'Phòng A.305 - Tòa A (Cam 03)')" title="Xem chi tiết" type="button"><span class="material-symbols-outlined text-[18px]">visibility</span></button><button class="p-1 hover:bg-surface-container rounded text-on-surface-variant" title="Tải ảnh" type="button"><span class="material-symbols-outlined text-[18px]">download</span></button></td></tr><tr class="hover:bg-surface-container-low/70 transition-colors cursor-pointer" onclick="openDetailModal('VP-20241031-0131', '07:30:02 - 31/10/2024', 'Giảng đường B.102 - Tòa B (Cam 01)')"><td class="pl-4 pr-2 py-2" onclick="event.stopPropagation()"><input class="w-4 h-4 rounded text-primary focus:ring-0" type="checkbox"></td><td class="px-3 py-2"><div class="w-16 h-11 rounded bg-surface-container flex items-center justify-center text-outline"><span class="material-symbols-outlined text-[18px]">photo_camera</span></div></td><td class="px-4 py-2 font-data-mono text-data-mono text-primary font-semibold">VP-20241031-0131</td><td class="px-4 py-2 text-on-surface">07:30:02 - 31/10/2024</td><td class="px-4 py-2 text-on-surface">Giảng đường B.102 - Tòa B (Cam 01)</td><td class="px-4 py-2 flex flex-wrap gap-1"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm">Không đeo thẻ</span><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-error-container text-error font-label-sm text-label-sm">Không đồng phục</span></td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 text-secondary font-label-sm text-label-sm font-semibold"><span class="w-1.5 h-1.5 rounded-full bg-secondary"></span> Còn 29 ngày</span></td><td class="px-4 py-2 text-right pr-4" onclick="event.stopPropagation()"><button class="p-1 hover:bg-surface-container rounded text-primary" onclick="openDetailModal('VP-20241031-0131', '07:30:02 - 31/10/2024', 'Giảng đường B.102 - Tòa B (Cam 01)')" title="Xem chi tiết" type="button"><span class="material-symbols-outlined text-[18px]">visibility</span></button><button class="p-1 hover:bg-surface-container rounded text-on-surface-variant" title="Tải ảnh" type="button"><span class="material-symbols-outlined text-[18px]">download</span></button></td></tr><tr class="hover:bg-surface-container-low/70 transition-colors opacity-75 cursor-pointer" onclick="openDetailModal('VP-20240920-0012', '07:05:12 - 20/09/2024', 'Giảng đường A.101 - Tòa A (Cam 01)')"><td class="pl-4 pr-2 py-2" onclick="event.stopPropagation()"><input class="w-4 h-4 rounded text-primary focus:ring-0" type="checkbox"></td><td class="px-3 py-2"><div class="w-16 h-11 rounded bg-surface-container-low flex items-center justify-center text-outline"><span class="material-symbols-outlined text-[16px]">lock</span></div></td><td class="px-4 py-2 font-data-mono text-data-mono text-on-surface-variant font-semibold">VP-20240920-0012</td><td class="px-4 py-2 text-on-surface-variant">07:05:12 - 20/09/2024</td><td class="px-4 py-2 text-on-surface-variant">Giảng đường A.101 - Tòa A (Cam 01)</td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm">Không đeo thẻ</span></td><td class="px-4 py-2"><span class="inline-flex items-center gap-1 text-outline font-label-sm text-label-sm"><span class="material-symbols-outlined text-[14px]">lock_clock</span> Đã hết hạn lưu</span></td><td class="px-4 py-2 text-right pr-4" onclick="event.stopPropagation()"><button class="p-1 hover:bg-surface-container rounded text-on-surface-variant" onclick="openDetailModal('VP-20240920-0012', '07:05:12 - 20/09/2024', 'Giảng đường A.101 - Tòa A (Cam 01)')" title="Xem chi tiết" type="button"><span class="material-symbols-outlined text-[18px]">visibility</span></button></td></tr></tbody>
</table>
</div>
</div>
<!-- Pagination Bottom Bar -->
<div class="flex flex-col sm:flex-row items-center justify-between gap-space-md mt-space-lg p-space-md bg-surface-container-lowest rounded-lg shadow-sm">
<div class="flex items-center gap-space-md">
<span class="font-body-md text-body-md text-on-surface-variant">
        Hiển thị <span class="font-semibold text-on-surface">1 - 12</span> trong tổng số <span class="font-semibold text-on-surface">1,280</span> vi phạm
      </span>
<div class="flex items-center gap-1 bg-surface-container-low px-2 py-1 rounded-lg">
<select class="bg-transparent font-label-sm text-label-sm text-on-surface focus:outline-none cursor-pointer"><option class="">Tất cả phòng học</option><option class="">Phòng A.201 (Cam 01)</option><option class="">Phòng A.304 (Cam 02)</option><option class="">Phòng B.102 (Cam 03)</option><option class="">Phòng B.405 (Cam 04)</option><option class="">Phòng máy C.501 (Cam 05)</option><option class="">Giảng đường C.101 (Cam 06)</option></select>
</div>
</div>
<div class="inline-flex items-center gap-1">
<button class="px-2 py-1 text-on-surface-variant hover:bg-surface-container rounded text-label-sm font-label-sm disabled:opacity-40" type="button">Đầu</button>
<button class="p-1 text-on-surface-variant hover:bg-surface-container rounded flex items-center justify-center" type="button">
<span class="material-symbols-outlined text-[18px]">chevron_left</span>
</button>
<button class="w-7 h-7 rounded bg-primary-container text-on-primary font-label-sm text-label-sm flex items-center justify-center shadow-xs" type="button">1</button>
<button class="w-7 h-7 rounded hover:bg-surface-container text-on-surface-variant font-label-sm text-label-sm flex items-center justify-center" type="button">2</button>
<button class="w-7 h-7 rounded hover:bg-surface-container text-on-surface-variant font-label-sm text-label-sm flex items-center justify-center" type="button">3</button>
<span class="px-1 text-outline font-body-sm">...</span>
<button class="w-8 h-7 rounded hover:bg-surface-container text-on-surface-variant font-label-sm text-label-sm flex items-center justify-center" type="button">107</button>
<button class="p-1 text-on-surface-variant hover:bg-surface-container rounded flex items-center justify-center" type="button">
<span class="material-symbols-outlined text-[18px]">chevron_right</span>
</button>
<button class="px-2 py-1 text-on-surface-variant hover:bg-surface-container rounded text-label-sm font-label-sm" type="button">Cuối</button>
</div>
</div>
<!-- Detail Modal (Backdrop + Dialog) -->
<div class="fixed inset-0 z-50 flex items-center justify-center p-4 hidden" id="violationDetailModal">
<!-- Backdrop -->
<div class="fixed inset-0 bg-inverse-surface/50 backdrop-blur-xs transition-opacity" onclick="closeDetailModal()"></div>
<!-- Modal Content Card -->
<div class="relative bg-surface-container-lowest rounded-xl shadow-xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden z-10">
<!-- Modal Header -->
<div class="px-space-lg py-space-md border-b border-surface-container flex items-center justify-between bg-surface-container-low/40">
<div class="flex items-center gap-space-sm">
<span class="material-symbols-outlined text-primary text-[24px]">policy</span>
<div>
<h2 class="font-headline-md text-headline-md text-on-surface tracking-tight" id="modalTitle">Chi tiết vi phạm #VP-20241031-0142</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Dữ liệu đối soát camera trí tuệ nhân tạo (AI Engine)</p>
</div>
</div>
<button class="p-1 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container transition-colors" onclick="closeDetailModal()" type="button">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<!-- Modal Body (2-Column Layout) -->
<div class="p-space-lg overflow-y-auto flex-1 grid grid-cols-1 md:grid-cols-12 gap-space-lg">
<!-- Left Column: Frame with AI Bounding Box -->
<div class="md:col-span-7 flex flex-col gap-space-sm">
<div class="relative rounded-lg overflow-hidden bg-surface-container-low border border-outline-variant aspect-[4/3] flex flex-col items-center justify-center group p-6 text-center"><div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-outline mb-2 shadow-xs"><span class="material-symbols-outlined text-[36px]">photo_camera</span></div><span class="font-headline-sm text-headline-sm text-on-surface font-semibold">Khu vực hiển thị ảnh trích xuất camera phòng học</span><span class="font-body-sm text-body-sm text-on-surface-variant mt-1 max-w-sm">Chưa tải ảnh lên / Ảnh ghi nhận camera AI đã được đối chiếu metadata lớp học</span><div class="absolute top-2 left-2 px-2.5 py-1 rounded bg-inverse-surface/85 font-data-mono text-label-sm text-inverse-on-surface tracking-wider">2024-10-31 07:18:24 CAM_01 ROOM_A201</div><div class="absolute bottom-2 right-2 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface font-label-sm text-label-sm flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">hd</span> Full HD 1080p</div></div>
<div class="flex items-center justify-between text-body-sm text-on-surface-variant px-1">
<span class="">Tọa độ khung hình: X:448 Y:320 W:180 H:210</span>
<span class="text-secondary font-medium">Tốc độ phân tích: 48ms</span>
</div>
</div>
<!-- Right Column: Record Specifications -->
<div class="md:col-span-5 flex flex-col justify-between gap-space-md">
<div class="flex flex-col gap-space-md">
<!-- Information items -->
<div class="flex flex-col gap-space-sm bg-surface-container-low/60 p-space-md rounded-lg">
<div class="flex flex-col">
<span class="font-label-sm text-label-sm text-on-surface-variant">Thời gian ghi nhận:</span>
<span class="font-label-lg text-label-lg text-on-surface" id="modalDateTime">07:18:24 - 31/10/2024</span>
</div>
<div class="flex flex-col">
<span class="font-label-sm text-label-sm text-on-surface-variant">Vị trí / Thiết bị:</span>
<span class="font-body-md text-body-md text-on-surface font-medium" id="modalLocation">Phòng A.201 - Tòa A (Camera AI Giảng đường #01 - Full HD)</span>
</div>
<div class="flex flex-col">
<span class="font-label-sm text-label-sm text-on-surface-variant">Lỗi vi phạm phát hiện:</span>
<div class="flex flex-wrap gap-1 mt-0.5" id="modalViolationBadges">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-tertiary-container font-label-sm text-label-sm">
<span class="material-symbols-outlined text-[13px]">badge</span> Không đeo thẻ sinh viên
                  </span>
</div>
</div>
<div class="grid grid-cols-2 gap-2 pt-1 border-t border-surface-container">
<div>
<span class="font-label-sm text-label-sm text-on-surface-variant">Độ tin cậy AI:</span>
<p class="font-data-mono text-label-lg text-secondary font-semibold">98.4% (Cao)</p>
</div>
<div>
<span class="font-label-sm text-label-sm text-on-surface-variant">Đối soát danh tính:</span>
<p class="font-body-sm text-body-sm text-on-surface-variant">Chờ đối soát thẻ</p>
</div>
</div>
</div>
<!-- Important Retention Regulatory Alert Box -->
<div class="bg-surface-container-high/60 p-space-md rounded-lg flex items-start gap-space-sm">
<span class="material-symbols-outlined text-primary text-[20px] shrink-0 mt-0.5">verified_user</span>
<div class="flex flex-col gap-0.5">
<span class="font-label-md text-label-md text-on-surface">Quy định lưu trữ dữ liệu hình ảnh:</span>
<p class="font-body-sm text-body-sm text-on-surface-variant leading-relaxed">
                  Ảnh trích xuất sẽ tự động bị xóa sau <strong class="text-primary font-semibold">29 ngày</strong>. Tuân thủ quy định tối đa 30 ngày lưu trữ dữ liệu hình ảnh camera theo Nghị định bảo vệ dữ liệu cá nhân.
                </p>
</div>
</div>
<!-- Clerical Notes Input -->
<div class="flex flex-col gap-1">
<label class="font-label-md text-label-md text-on-surface" for="processingNotes">Ghi chú xác minh:</label>
<textarea class="w-full bg-surface-container-low p-2.5 rounded-lg font-body-md text-body-md text-on-surface placeholder:text-outline focus:outline-none focus:ring-1 focus:ring-primary resize-none" id="processingNotes" placeholder="Nhập ghi chú hoặc chuyển phòng CTSV đối chiếu MSSV..." rows="2"></textarea>
</div>
</div>
</div>
</div>
<!-- Modal Footer -->
<div class="px-space-lg py-space-md bg-surface-container-low border-t border-surface-container flex items-center justify-between">
<button class="px-4 py-2 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container hover:text-on-surface font-label-md text-label-md transition-colors" onclick="closeDetailModal()" type="button">
          Đóng
        </button>
<div class="flex items-center gap-space-sm">
<button class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg bg-surface-container-lowest text-on-surface font-label-md text-label-md hover:bg-surface-container shadow-xs transition-colors" type="button">
<span class="material-symbols-outlined text-[18px]">download</span> Tải ảnh gốc HD
          </button>
<button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary-container text-on-primary font-label-md text-label-md hover:bg-primary shadow-xs transition-colors" type="button">
<span class="material-symbols-outlined text-[18px]">print</span> Trích xuất biên bản PDF
          </button>
</div>
</div>
</div>
</div>
<!-- Micro-interactions JavaScript -->
<script>
    // View Switcher (Grid vs Table)
    const btnViewGrid = document.getElementById('btnViewGrid');
    const btnViewTable = document.getElementById('btnViewTable');
    const gridView = document.getElementById('violationGridView');
    const tableView = document.getElementById('violationTableView');

    btnViewGrid.addEventListener('click', () => {
      gridView.classList.remove('hidden');
      tableView.classList.add('hidden');
      btnViewGrid.classList.add('bg-surface-container-lowest', 'text-primary', 'shadow-xs');
      btnViewGrid.classList.remove('text-on-surface-variant');
      btnViewTable.classList.remove('bg-surface-container-lowest', 'text-primary', 'shadow-xs');
      btnViewTable.classList.add('text-on-surface-variant');
    });

    btnViewTable.addEventListener('click', () => {
      gridView.classList.add('hidden');
      tableView.classList.remove('hidden');
      btnViewTable.classList.add('bg-surface-container-lowest', 'text-primary', 'shadow-xs');
      btnViewTable.classList.remove('text-on-surface-variant');
      btnViewGrid.classList.remove('bg-surface-container-lowest', 'text-primary', 'shadow-xs');
      btnViewGrid.classList.add('text-on-surface-variant');
    });

    // Date Chips Toggle
    const dateChips = document.querySelectorAll('.date-chip');
    dateChips.forEach(chip => {
      chip.addEventListener('click', () => {
        dateChips.forEach(c => {
          c.classList.remove('bg-primary-container', 'text-on-primary', 'shadow-xs');
          c.classList.add('text-on-surface-variant');
        });
        chip.classList.add('bg-primary-container', 'text-on-primary', 'shadow-xs');
        chip.classList.remove('text-on-surface-variant');
      });
    });

    // Violation Filter Pills Toggle
    const pills = document.querySelectorAll('.filter-pill');
    pills.forEach(pill => {
      pill.addEventListener('click', () => {
        pills.forEach(p => {
          p.classList.remove('bg-surface-container-high', 'text-on-surface');
          p.classList.add('bg-surface-container-low', 'text-on-surface-variant');
        });
        pill.classList.remove('bg-surface-container-low', 'text-on-surface-variant');
        pill.classList.add('bg-surface-container-high', 'text-on-surface');
      });
    });

    // Modal Control
    const detailModal = document.getElementById('violationDetailModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalDateTime = document.getElementById('modalDateTime');
    const modalLocation = document.getElementById('modalLocation');

    function openDetailModal(id, time, area) {
      if (id) {
        modalTitle.textContent = 'Chi tiết vi phạm #' + id;
      }
      if (time) {
        modalDateTime.textContent = time;
      }
      if (area) {
        modalLocation.textContent = area + ' (Camera AI Hikvision - Full HD)';
      }
      detailModal.classList.remove('hidden');
    }

    function closeDetailModal() {
      detailModal.classList.add('hidden');
    }

    // Attach card click handlers
    document.querySelectorAll('.violation-card').forEach(card => {
      card.addEventListener('click', () => {
        const id = card.getAttribute('data-id');
        const time = card.getAttribute('data-time');
        const cam = card.getAttribute('data-cam');
        openDetailModal(id, time, cam);
      });
    });

    // Escape key closes modal
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !detailModal.classList.contains('hidden')) {
        closeDetailModal();
      }
    });
  </script>
</div>
<?php include 'includes/footer.php'; ?>
