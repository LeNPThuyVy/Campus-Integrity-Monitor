<?php include 'includes/header.php'; ?>
<div class="flex flex-col w-full pb-space-xl">
<!-- Top Banner / Dashboard Action Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md py-space-md">
<div class="flex flex-col gap-0.5">
<div class="flex items-center gap-space-xs">
<span class="font-headline-xl text-headline-xl text-on-surface">Tổng quan hệ thống</span>
<span class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-secondary-fixed/40 text-on-secondary-fixed font-data-mono text-data-mono">
<span class="w-1.5 h-1.5 rounded-full bg-secondary animate-ping"></span>
          Trực tiếp
        </span>
</div>
<p class="font-body-md text-body-md text-on-surface-variant">Bảng điều khiển giám sát tác phong sinh viên theo thời gian thực tại các phòng học</p>
</div>
<!-- Action Buttons -->
<div class="flex items-center gap-space-sm self-start md:self-auto">
<button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface transition-all shadow-sm active:scale-95" id="refresh-btn">
<span class="material-symbols-outlined text-[18px] transition-transform duration-500" id="refresh-icon">sync</span>
<span class="font-label-md text-label-md">Làm mới dữ liệu</span>
</button>
<button class="flex items-center gap-space-xs px-4 py-2 rounded-xl bg-primary text-on-primary hover:bg-primary-container shadow-sm transition-all active:scale-95">
<span class="material-symbols-outlined text-[18px]">download</span>
<span class="font-label-md text-label-md font-semibold">Xuất báo cáo nhanh</span>
</button>
</div>
</div>
<!-- Operational Alert Box -->
<div class="mb-space-lg p-space-md rounded-xl bg-error-container/40 text-on-error-container shadow-sm flex items-start sm:items-center justify-between gap-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-9 h-9 rounded-xl bg-error text-on-error flex items-center justify-center shrink-0 shadow-sm">
<span class="material-symbols-outlined text-[20px]">notification_important</span>
</div>
<div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-space-sm">
<span class="font-label-lg text-label-lg font-bold text-error uppercase tracking-wide">Cảnh báo vi phạm đột biến:</span>
<span class="font-body-md text-body-md text-on-surface font-medium">
          Phòng <span class="font-semibold text-primary underline">A.305 (Tòa A)</span> ghi nhận <strong>12 lượt không đeo thẻ</strong> trong ca học sáng nay (07:30 - 09:30). Đề xuất nhắc nhở ban cán sự lớp.
        </span>
</div>
</div>
<div class="flex items-center gap-space-xs shrink-0">
<button class="px-2.5 py-1 rounded-xl bg-surface-container-lowest text-error font-label-sm text-label-sm shadow-sm hover:bg-error hover:text-on-error transition-colors">
        Xem chi tiết
      </button>
</div>
</div>
<!-- 4 KPI Metrics Bento -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-space-md mb-space-lg">
<!-- Card 1: Vi phạm hôm nay -->
<div class="p-space-md rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between relative overflow-hidden group">
<div class="absolute right-0 top-0 translate-x-2 -translate-y-2 w-20 h-20 bg-error/5 rounded-full pointer-events-none group-hover:scale-110 transition-transform"></div>
<div class="flex items-center justify-between mb-space-sm">
<span class="font-label-md text-label-md text-on-surface-variant font-medium">Vi phạm hôm nay</span>
<div class="w-8 h-8 rounded-xl bg-error-container text-on-error-container flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">emergency</span>
</div>
</div>
<div class="flex items-baseline gap-space-sm">
<span class="font-headline-xl text-[28px] font-bold text-on-surface">42</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">lượt ghi nhận</span>
</div>
<div class="mt-space-sm pt-space-xs flex items-center gap-1.5 text-error">
<span class="material-symbols-outlined text-[16px]">trending_up</span>
<span class="font-label-sm text-label-sm font-semibold">+8%</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">so với hôm qua (39 lượt)</span>
</div>
</div>
<!-- Card 2: Vi phạm tuần này -->
<div class="p-space-md rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between relative overflow-hidden group">
<div class="absolute right-0 top-0 translate-x-2 -translate-y-2 w-20 h-20 bg-primary/5 rounded-full pointer-events-none group-hover:scale-110 transition-transform"></div>
<div class="flex items-center justify-between mb-space-sm">
<span class="font-label-md text-label-md text-on-surface-variant font-medium">Vi phạm tuần này</span>
<div class="w-8 h-8 rounded-xl bg-primary-fixed text-on-primary-fixed-variant flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">calendar_view_week</span>
</div>
</div>
<div class="flex items-baseline gap-space-sm">
<span class="font-headline-xl text-[28px] font-bold text-on-surface">286</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">lượt tổng kết</span>
</div>
<div class="mt-space-sm pt-space-xs flex items-center gap-1.5 text-secondary">
<span class="material-symbols-outlined text-[16px]">trending_down</span>
<span class="font-label-sm text-label-sm font-semibold">-12%</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">so với tuần trước (325 lượt)</span>
</div>
</div>
<!-- Card 3: Tỷ lệ tuân thủ tác phong -->
<div class="p-space-md rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between relative overflow-hidden group">
<div class="absolute right-0 top-0 translate-x-2 -translate-y-2 w-20 h-20 bg-secondary/5 rounded-full pointer-events-none group-hover:scale-110 transition-transform"></div>
<div class="flex items-center justify-between mb-space-sm">
<span class="font-label-md text-label-md text-on-surface-variant font-medium">Tỷ lệ tuân thủ tác phong</span>
<div class="w-8 h-8 rounded-xl bg-secondary-fixed text-on-secondary-fixed flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">verified_user</span>
</div>
</div>
<div class="flex items-baseline gap-space-sm">
<span class="font-headline-xl text-[28px] font-bold text-secondary">94.6%</span>
<span class="font-label-sm text-label-sm text-outline">/ Mục tiêu ≥ 95%</span>
</div>
<div class="mt-space-sm w-full bg-surface-container rounded-full h-1.5 overflow-hidden">
<div class="bg-secondary h-full rounded-full" style="width: 94.6%"></div>
</div>
</div>
<!-- Card 4: Camera đang hoạt động -->
<div class="p-space-md rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between relative overflow-hidden group">
<div class="absolute right-0 top-0 translate-x-2 -translate-y-2 w-20 h-20 bg-primary-container/5 rounded-full pointer-events-none group-hover:scale-110 transition-transform"></div>
<div class="flex items-center justify-between mb-space-sm">
<span class="font-label-md text-label-md text-on-surface-variant font-medium">Camera đang hoạt động</span>
<div class="w-8 h-8 rounded-xl bg-surface-container-highest text-primary flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">videocam</span>
</div>
</div>
<div class="flex items-baseline gap-space-sm">
<span class="font-headline-xl text-[28px] font-bold text-on-surface">18<span class="text-on-surface-variant text-headline-sm font-normal">/20</span></span>
<span class="font-label-sm text-label-sm px-2 py-0.5 rounded-full bg-secondary-fixed/50 text-on-secondary-fixed font-semibold">90% Online</span>
</div>
<div class="mt-space-sm pt-space-xs flex items-center gap-1.5 text-on-surface-variant">
<span class="w-2 h-2 rounded-full bg-outline"></span>
<span class="font-body-sm text-body-sm">2 camera đang bảo trì định kỳ</span>
</div>
</div>
</div>
<!-- Primary Content Grid: 7-Day Trend Chart & Camera Health Table -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-space-md mb-space-lg">
<!-- Chart Section: 8 Cols -->
<div class="lg:col-span-8 p-space-md rounded-xl bg-surface-container-lowest shadow-sm flex flex-col justify-between">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-space-sm pb-space-sm">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Xu hướng vi phạm 7 ngày gần nhất</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Phân tích hành vi theo 2 nhóm lỗi nhận diện từ AI camera</p>
</div>
<!-- Legends -->
<div class="flex items-center gap-space-md self-start sm:self-auto bg-surface-container-low px-3 py-1.5 rounded-xl">
<div class="flex items-center gap-1.5">
<span class="w-3 h-3 rounded-full bg-tertiary"></span>
<span class="font-label-sm text-label-sm text-on-surface font-medium">Không đeo thẻ</span>
</div>
<div class="flex items-center gap-1.5">
<span class="w-3 h-3 rounded-full bg-error"></span>
<span class="font-label-sm text-label-sm text-on-surface font-medium">Không mặc đồng phục</span>
</div>
</div>
</div>
<!-- SVG Line Chart -->
<div class="relative w-full h-64 mt-space-sm flex flex-col justify-end">
<svg class="w-full h-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 700 220">
<defs>
<linearGradient id="grad-card" x1="0%" x2="0%" y1="0%" y2="100%">
<stop offset="0%" stop-color="#7c2900" stop-opacity="0.18"></stop>
<stop offset="100%" stop-color="#7c2900" stop-opacity="0.0"></stop>
</linearGradient>
<linearGradient id="grad-uniform" x1="0%" x2="0%" y1="0%" y2="100%">
<stop offset="0%" stop-color="#ba1a1a" stop-opacity="0.18"></stop>
<stop offset="100%" stop-color="#ba1a1a" stop-opacity="0.0"></stop>
</linearGradient>
</defs>
<!-- Horizontal Grid Lines -->
<line stroke="#eff4ff" stroke-width="1.5" x1="40" x2="680" y1="20" y2="20"></line>
<line stroke="#eff4ff" stroke-width="1.5" x1="40" x2="680" y1="70" y2="70"></line>
<line stroke="#eff4ff" stroke-width="1.5" x1="40" x2="680" y1="120" y2="120"></line>
<line stroke="#eff4ff" stroke-width="1.5" x1="40" x2="680" y1="170" y2="170"></line>
<!-- Axis Values Left -->
<text class="text-[11px] font-data-mono fill-outline" text-anchor="start" x="15" y="24">60</text>
<text class="text-[11px] font-data-mono fill-outline" text-anchor="start" x="15" y="74">40</text>
<text class="text-[11px] font-data-mono fill-outline" text-anchor="start" x="15" y="124">20</text>
<text class="text-[11px] font-data-mono fill-outline" text-anchor="start" x="15" y="174">0</text>
<!-- Area Fills -->
<!-- Card Violations Area (T2:28, T3:35, T4:22, T5:45, T6:38, T7:18, CN:8) -->
<!-- Mapping: Y = 170 - (val / 60) * 150 -->
<!-- X coordinates: 60, 160, 260, 360, 460, 560, 660 -->
<!-- Pts Card: (60,100), (160,82.5), (260,115), (360,57.5), (460,75), (560,125), (660,150) -->
<polygon fill="url(#grad-card)" points="60,100 160,82.5 260,115 360,57.5 460,75 560,125 660,150 660,170 60,170"></polygon>
<!-- Uniform Violations Area (T2:15, T3:20, T4:14, T5:26, T6:22, T7:10, CN:4) -->
<!-- Pts Uniform: (60,132.5), (160,120), (260,135), (360,105), (460,115), (560,145), (660,160) -->
<polygon fill="url(#grad-uniform)" points="60,132.5 160,120 260,135 360,105 460,115 560,145 660,160 660,170 60,170"></polygon>
<!-- Card Violation Line -->
<polyline fill="none" points="60,100 160,82.5 260,115 360,57.5 460,75 560,125 660,150" stroke="#7c2900" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></polyline>
<!-- Uniform Violation Line -->
<polyline fill="none" points="60,132.5 160,120 260,135 360,105 460,115 560,145 660,160" stroke="#ba1a1a" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></polyline>
<!-- Points for Card Line -->
<circle cx="60" cy="100" fill="#ffffff" r="4" stroke="#7c2900" stroke-width="2"></circle>
<circle cx="160" cy="82.5" fill="#ffffff" r="4" stroke="#7c2900" stroke-width="2"></circle>
<circle cx="260" cy="115" fill="#ffffff" r="4" stroke="#7c2900" stroke-width="2"></circle>
<circle cx="360" cy="57.5" fill="#7c2900" r="5" stroke="#ffffff" stroke-width="2"></circle>
<circle cx="460" cy="75" fill="#ffffff" r="4" stroke="#7c2900" stroke-width="2"></circle>
<circle cx="560" cy="125" fill="#ffffff" r="4" stroke="#7c2900" stroke-width="2"></circle>
<circle cx="660" cy="150" fill="#ffffff" r="4" stroke="#7c2900" stroke-width="2"></circle>
<!-- Points for Uniform Line -->
<circle cx="60" cy="132.5" fill="#ffffff" r="4" stroke="#ba1a1a" stroke-width="2"></circle>
<circle cx="160" cy="120" fill="#ffffff" r="4" stroke="#ba1a1a" stroke-width="2"></circle>
<circle cx="260" cy="135" fill="#ffffff" r="4" stroke="#ba1a1a" stroke-width="2"></circle>
<circle cx="360" cy="105" fill="#ba1a1a" r="5" stroke="#ffffff" stroke-width="2"></circle>
<circle cx="460" cy="115" fill="#ffffff" r="4" stroke="#ba1a1a" stroke-width="2"></circle>
<circle cx="560" cy="145" fill="#ffffff" r="4" stroke="#ba1a1a" stroke-width="2"></circle>
<circle cx="660" cy="160" fill="#ffffff" r="4" stroke="#ba1a1a" stroke-width="2"></circle>
<!-- X Axis Dates Labels -->
<text class="text-[12px] font-label-sm fill-on-surface-variant font-medium" text-anchor="middle" x="60" y="195">T2 (18/11)</text>
<text class="text-[12px] font-label-sm fill-on-surface-variant font-medium" text-anchor="middle" x="160" y="195">T3 (19/11)</text>
<text class="text-[12px] font-label-sm fill-on-surface-variant font-medium" text-anchor="middle" x="260" y="195">T4 (20/11)</text>
<text class="text-[12px] font-label-sm fill-primary font-bold" text-anchor="middle" x="360" y="195">T5 (21/11)</text>
<text class="text-[12px] font-label-sm fill-on-surface-variant font-medium" text-anchor="middle" x="460" y="195">T6 (22/11)</text>
<text class="text-[12px] font-label-sm fill-on-surface-variant font-medium" text-anchor="middle" x="560" y="195">T7 (23/11)</text>
<text class="text-[12px] font-label-sm fill-on-surface-variant font-medium" text-anchor="middle" x="660" y="195">CN (24/11)</text>
</svg>
</div>
<!-- Footer Note of the Chart -->
<div class="pt-space-sm mt-space-xs flex items-center justify-between text-on-surface-variant">
<span class="font-body-sm text-body-sm flex items-center gap-1">
<span class="material-symbols-outlined text-[15px] text-tertiary">info</span>
          Ngày thứ Năm ghi nhận đỉnh điểm vi phạm do lịch học tập trung môn GDQP &amp; GDTC
        </span>
<span class="font-data-mono text-data-mono text-primary font-semibold">Cập nhật lúc 10:45:00</span>
</div>
</div>
<!-- Camera Status Widget: 4 Cols -->
<div class="lg:col-span-4 p-space-md rounded-xl bg-surface-container-lowest shadow-sm flex flex-col justify-between">
<div class="flex items-center justify-between pb-space-sm">
<div>
<h2 class="font-headline-md text-headline-md text-on-surface">Trạng thái Camera</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">20 điểm giám sát AI phòng học</p>
</div>
<span class="font-data-mono text-data-mono px-2 py-0.5 rounded-full bg-secondary-fixed/50 text-on-secondary-fixed font-bold">18 LIVE</span>
</div>
<!-- Camera Mini List -->
<div class="flex flex-col gap-space-xs my-space-xs overflow-y-auto max-h-64 pr-1">
<!-- Item 1 -->
<div class="p-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors flex items-center justify-between gap-space-sm">
<div class="flex items-center gap-space-sm min-w-0">
<div class="w-8 h-8 rounded-lg bg-surface-container-highest text-primary flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-[18px]">videocam</span>
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-1.5">
<span class="font-label-md text-label-md font-semibold text-on-surface truncate">CAM 01 - P.A201</span>
<span class="w-2 h-2 rounded-full bg-secondary"></span>
</div>
<span class="font-data-mono text-[11px] text-on-surface-variant">Tòa A • 25 FPS</span>
</div>
</div>
<button class="px-2 py-1 rounded-lg bg-surface-container-lowest text-primary hover:bg-primary hover:text-on-primary font-label-sm text-label-sm transition-all shadow-sm shrink-0 flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">visibility</span>
<span>Xem</span>
</button>
</div>
<!-- Item 2 -->
<div class="p-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors flex items-center justify-between gap-space-sm">
<div class="flex items-center gap-space-sm min-w-0">
<div class="w-8 h-8 rounded-lg bg-surface-container-highest text-primary flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-[18px]">videocam</span>
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-1.5">
<span class="font-label-md text-label-md font-semibold text-on-surface truncate">CAM 03 - P.B102</span>
<span class="w-2 h-2 rounded-full bg-secondary"></span>
</div>
<span class="font-data-mono text-[11px] text-on-surface-variant">Tòa B • 30 FPS</span>
</div>
</div>
<button class="px-2 py-1 rounded-lg bg-surface-container-lowest text-primary hover:bg-primary hover:text-on-primary font-label-sm text-label-sm transition-all shadow-sm shrink-0 flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">visibility</span>
<span>Xem</span>
</button>
</div>
<!-- Item 3 -->
<div class="p-2.5 rounded-xl bg-surface-container-low hover:bg-surface-container transition-colors flex items-center justify-between gap-space-sm">
<div class="flex items-center gap-space-sm min-w-0">
<div class="w-8 h-8 rounded-lg bg-surface-container-highest text-primary flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-[18px]">videocam</span>
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-1.5">
<span class="font-label-md text-label-md font-semibold text-on-surface truncate">CAM 05 - GĐ C101</span>
<span class="w-2 h-2 rounded-full bg-secondary"></span>
</div>
<span class="font-data-mono text-[11px] text-on-surface-variant">Giảng đường C • 30 FPS</span>
</div>
</div>
<button class="px-2 py-1 rounded-lg bg-surface-container-lowest text-primary hover:bg-primary hover:text-on-primary font-label-sm text-label-sm transition-all shadow-sm shrink-0 flex items-center gap-1">
<span class="material-symbols-outlined text-[14px]">visibility</span>
<span>Xem</span>
</button>
</div>
<!-- Item 4 (Maintenance) -->
<div class="p-2.5 rounded-xl bg-surface-container-low/60 hover:bg-surface-container transition-colors flex items-center justify-between gap-space-sm opacity-80">
<div class="flex items-center gap-space-sm min-w-0">
<div class="w-8 h-8 rounded-lg bg-surface-container text-outline flex items-center justify-center shrink-0">
<span class="material-symbols-outlined text-[18px]">build</span>
</div>
<div class="flex flex-col min-w-0">
<div class="flex items-center gap-1.5">
<span class="font-label-md text-label-md font-semibold text-on-surface-variant truncate">CAM 08 - P.C402</span>
<span class="w-2 h-2 rounded-full bg-outline"></span>
</div>
<span class="font-data-mono text-[11px] text-outline">Phòng máy • Bảo trì</span>
</div>
</div>
<span class="px-2 py-1 rounded-lg bg-surface-container text-outline font-label-sm text-label-sm shrink-0">
            Bảo trì
          </span>
</div>
</div>
<!-- Quick Action -->
<a class="mt-space-sm pt-space-xs block text-center font-label-sm text-label-sm text-primary hover:underline font-semibold" href="#">
        Xem tất cả 20 camera phòng học →
      </a>
</div>
</div>
<!-- Recent Violations Strip: 6 Cards (2 rows x 3 cols or responsive grid) -->
<div class="flex flex-col gap-space-md">
<div class="flex items-center justify-between">
<div class="flex items-center gap-space-xs">
<div class="w-2.5 h-5 bg-primary rounded-full"></div>
<h2 class="font-headline-md text-headline-md text-on-surface">Vi phạm mới nhất ghi nhận từ Camera phòng học</h2>
<span class="px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-bold">Thời gian thực</span>
</div>
<a class="font-label-md text-label-md text-primary font-semibold hover:underline flex items-center gap-1" href="#">
<span>Xem toàn bộ nhật ký vi phạm</span>
<span class="material-symbols-outlined text-[16px]">arrow_forward</span>
</a>
</div>
<!-- 6 Snapshot Cards Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-space-md">
<!-- Card 1 -->
<div class="rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
<!-- Professional Camera Snapshot Placeholder Frame -->
<div class="relative w-full h-44 bg-surface-container-highest flex flex-col items-center justify-center overflow-hidden">
<div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-[36px]">photo_camera</span>
</div>
<span class="mt-2 font-data-mono text-data-mono text-on-surface-variant font-medium">CAMERA SNAPSHOT • AI DETECT</span>
<!-- Camera Label & Timestamp Overlay -->
<div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm">
<span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
<span class="font-data-mono text-[11px] font-bold">CAM 01</span>
</div>
<div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm font-data-mono text-[11px]">
            08:15:20 • Hôm nay
          </div>
<!-- Bounding box visual cue -->
<div class="absolute top-8 right-12 w-16 h-20 border-2 border-dashed border-error/70 rounded-md pointer-events-none flex items-start justify-end p-0.5">
<span class="bg-error text-on-error text-[8px] font-bold px-1 rounded">No Tag</span>
</div>
</div>
<!-- Card Body -->
<div class="p-space-md flex flex-col justify-between flex-1 gap-space-sm">
<div class="flex items-start justify-between gap-space-xs">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Phòng A.201 - Tòa A</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Lớp: DH22CNTT01 • Tiết 2</span>
</div>
<!-- Violation Badges -->
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">badge</span>
              Không đeo thẻ
            </span>
</div>
<div class="flex items-center justify-between pt-space-xs text-on-surface-variant">
<span class="font-data-mono text-data-mono">Độ tin cậy AI: <strong class="text-secondary font-bold">96.8%</strong></span>
<button class="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-0.5">
              Xử lý vi phạm
              <span class="material-symbols-outlined text-[14px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Card 2 -->
<div class="rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
<div class="relative w-full h-44 bg-surface-container-highest flex flex-col items-center justify-center overflow-hidden">
<div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-[36px]">videocam</span>
</div>
<span class="mt-2 font-data-mono text-data-mono text-on-surface-variant font-medium">CAMERA SNAPSHOT • AI DETECT</span>
<div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm">
<span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
<span class="font-data-mono text-[11px] font-bold">CAM 03</span>
</div>
<div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm font-data-mono text-[11px]">
            08:24:45 • Hôm nay
          </div>
<div class="absolute top-6 right-16 w-20 h-24 border-2 border-dashed border-error/70 rounded-md pointer-events-none flex items-start justify-end p-0.5">
<span class="bg-error text-on-error text-[8px] font-bold px-1 rounded">No Uniform</span>
</div>
</div>
<div class="p-space-md flex flex-col justify-between flex-1 gap-space-sm">
<div class="flex items-start justify-between gap-space-xs">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Phòng B.102 - Tòa B</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Lớp: DH21QTKD03 • Tiết 2</span>
</div>
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">warning</span>
              Không mặc đồng phục
            </span>
</div>
<div class="flex items-center justify-between pt-space-xs text-on-surface-variant">
<span class="font-data-mono text-data-mono">Độ tin cậy AI: <strong class="text-secondary font-bold">98.2%</strong></span>
<button class="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-0.5">
              Xử lý vi phạm
              <span class="material-symbols-outlined text-[14px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Card 3 -->
<div class="rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
<div class="relative w-full h-44 bg-surface-container-highest flex flex-col items-center justify-center overflow-hidden">
<div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-[36px]">photo_camera</span>
</div>
<span class="mt-2 font-data-mono text-data-mono text-on-surface-variant font-medium">CAMERA SNAPSHOT • AI DETECT</span>
<div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm">
<span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
<span class="font-data-mono text-[11px] font-bold">CAM 05</span>
</div>
<div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm font-data-mono text-[11px]">
            08:42:10 • Hôm nay
          </div>
</div>
<div class="p-space-md flex flex-col justify-between flex-1 gap-space-sm">
<div class="flex items-start justify-between gap-space-xs">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Giảng đường C.101</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Lớp: DH23KT02 • Tiết 2</span>
</div>
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">badge</span>
              Không đeo thẻ
            </span>
</div>
<div class="flex items-center justify-between pt-space-xs text-on-surface-variant">
<span class="font-data-mono text-data-mono">Độ tin cậy AI: <strong class="text-secondary font-bold">94.1%</strong></span>
<button class="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-0.5">
              Xử lý vi phạm
              <span class="material-symbols-outlined text-[14px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Card 4 -->
<div class="rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
<div class="relative w-full h-44 bg-surface-container-highest flex flex-col items-center justify-center overflow-hidden">
<div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-[36px]">videocam</span>
</div>
<span class="mt-2 font-data-mono text-data-mono text-on-surface-variant font-medium">CAMERA SNAPSHOT • AI DETECT</span>
<div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm">
<span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
<span class="font-data-mono text-[11px] font-bold">CAM 01</span>
</div>
<div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm font-data-mono text-[11px]">
            09:05:12 • Hôm nay
          </div>
</div>
<div class="p-space-md flex flex-col justify-between flex-1 gap-space-sm">
<div class="flex items-start justify-between gap-space-xs">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Phòng A.201 - Tòa A</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Lớp: DH22CNTT01 • Tiết 3</span>
</div>
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">warning</span>
              Không mặc đồng phục
            </span>
</div>
<div class="flex items-center justify-between pt-space-xs text-on-surface-variant">
<span class="font-data-mono text-data-mono">Độ tin cậy AI: <strong class="text-secondary font-bold">95.4%</strong></span>
<button class="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-0.5">
              Xử lý vi phạm
              <span class="material-symbols-outlined text-[14px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Card 5 -->
<div class="rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
<div class="relative w-full h-44 bg-surface-container-highest flex flex-col items-center justify-center overflow-hidden">
<div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-[36px]">photo_camera</span>
</div>
<span class="mt-2 font-data-mono text-data-mono text-on-surface-variant font-medium">CAMERA SNAPSHOT • AI DETECT</span>
<div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm">
<span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
<span class="font-data-mono text-[11px] font-bold">CAM 03</span>
</div>
<div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm font-data-mono text-[11px]">
            09:20:00 • Hôm nay
          </div>
</div>
<div class="p-space-md flex flex-col justify-between flex-1 gap-space-sm">
<div class="flex items-start justify-between gap-space-xs">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Phòng B.102 - Tòa B</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Lớp: DH21QTKD03 • Tiết 3</span>
</div>
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">badge</span>
              Không đeo thẻ
            </span>
</div>
<div class="flex items-center justify-between pt-space-xs text-on-surface-variant">
<span class="font-data-mono text-data-mono">Độ tin cậy AI: <strong class="text-secondary font-bold">97.0%</strong></span>
<button class="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-0.5">
              Xử lý vi phạm
              <span class="material-symbols-outlined text-[14px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Card 6 -->
<div class="rounded-xl bg-surface-container-lowest shadow-sm hover:shadow-md transition-all flex flex-col overflow-hidden group">
<div class="relative w-full h-44 bg-surface-container-highest flex flex-col items-center justify-center overflow-hidden">
<div class="w-16 h-16 rounded-full bg-surface-container flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-[36px]">videocam</span>
</div>
<span class="mt-2 font-data-mono text-data-mono text-on-surface-variant font-medium">CAMERA SNAPSHOT • AI DETECT</span>
<div class="absolute top-2.5 left-2.5 flex items-center gap-1.5 px-2 py-1 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm">
<span class="w-2 h-2 rounded-full bg-error animate-pulse"></span>
<span class="font-data-mono text-[11px] font-bold">CAM 05</span>
</div>
<div class="absolute bottom-2.5 left-2.5 px-2 py-0.5 rounded bg-inverse-surface/80 text-inverse-on-surface backdrop-blur-sm font-data-mono text-[11px]">
            09:35:48 • Hôm nay
          </div>
</div>
<div class="p-space-md flex flex-col justify-between flex-1 gap-space-sm">
<div class="flex items-start justify-between gap-space-xs">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Giảng đường C.101</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Lớp: DH23KT02 • Tiết 3</span>
</div>
<!-- Both violations tag -->
<div class="flex flex-col gap-1 items-end">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">badge</span>
                Không đeo thẻ
              </span>
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-label-sm font-semibold">
<span class="material-symbols-outlined text-[13px]">warning</span>
                Sai đồng phục
              </span>
</div>
</div>
<div class="flex items-center justify-between pt-space-xs text-on-surface-variant">
<span class="font-data-mono text-data-mono">Độ tin cậy AI: <strong class="text-secondary font-bold">99.1%</strong></span>
<button class="font-label-sm text-label-sm text-primary hover:underline font-semibold flex items-center gap-0.5">
              Xử lý vi phạm
              <span class="material-symbols-outlined text-[14px]">chevron_right</span>
</button>
</div>
</div>
</div>
</div>
</div>
</div>
<script>
  // Simple micro-interaction for Refresh Button
  document.getElementById('refresh-btn')?.addEventListener('click', function() {
    const icon = document.getElementById('refresh-icon');
    if (icon) {
      icon.classList.add('rotate-180');
      setTimeout(() => {
        icon.classList.remove('rotate-180');
      }, 500);
    }
  });
</script>
<?php include 'includes/footer.php'; ?>
