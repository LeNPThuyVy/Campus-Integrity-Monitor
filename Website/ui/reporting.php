<?php include 'includes/header.php'; ?>
<div class="flex flex-col w-full pb-space-xl">
<!-- Top Bar: Filters & Quick Actions -->
<div class="flex flex-col xl:flex-row items-start xl:items-center justify-between gap-space-md py-space-md">
<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-space-sm flex-wrap w-full xl:w-auto">
<!-- Period Selector -->
<div class="inline-flex p-1 bg-surface-container rounded-xl shadow-sm self-start">
<button class="px-3 py-1.5 rounded-lg text-body-sm font-label-md text-on-surface-variant hover:text-on-surface transition-all" type="button">Tuần này</button>
<button class="px-3 py-1.5 rounded-lg text-body-sm font-label-md text-on-surface-variant hover:text-on-surface transition-all" type="button">Tháng này</button>
<button class="px-3 py-1.5 rounded-lg text-body-sm font-label-md bg-surface-container-lowest text-primary shadow-sm" type="button">Học kỳ I (2024-2025)</button>
</div>
<!-- Date Range Picker -->
<div class="flex items-center gap-2 px-3 py-1.5 bg-surface-container-lowest rounded-xl shadow-sm">
<span class="material-symbols-outlined text-[18px] text-outline">calendar_today</span>
<input class="w-20 bg-transparent text-on-surface font-data-mono text-data-mono focus:outline-none" type="text" value="01/09/2024">
<span class="text-outline font-label-sm text-label-sm">đến</span>
<input class="w-20 bg-transparent text-on-surface font-data-mono text-data-mono focus:outline-none" type="text" value="31/10/2024">
</div>
<!-- Room / Camera Dropdown -->
<div class="relative">
<div class="flex items-center gap-2 px-3 py-2 bg-surface-container-lowest rounded-xl shadow-sm cursor-pointer min-w-[210px] justify-between">
<div class="flex items-center gap-2 truncate">
<span class="material-symbols-outlined text-[18px] text-primary">meeting_room</span>
<span class="font-label-md text-label-md text-on-surface truncate">Tất cả phòng học / Camera (6)</span>
</div>
<span class="material-symbols-outlined text-[18px] text-outline">arrow_drop_down</span>
</div>
</div>
</div>
<!-- Export & Print Actions -->
<div class="flex items-center gap-space-xs self-end xl:self-auto shrink-0">
<button class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface rounded-xl shadow-sm transition-all" title="Xuất tập tin Excel" type="button">
<span class="material-symbols-outlined text-[18px] text-secondary">table_view</span>
<span class="font-label-md text-label-md">Xuất Excel</span>
</button>
<button class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-lowest hover:bg-surface-container text-on-surface rounded-xl shadow-sm transition-all" title="Xuất tập tin PDF" type="button">
<span class="material-symbols-outlined text-[18px] text-error">picture_as_pdf</span>
<span class="font-label-md text-label-md">Xuất PDF</span>
</button>
<button class="flex items-center gap-1.5 px-4 py-2 bg-primary-container hover:bg-primary text-on-primary rounded-xl shadow-md transition-all" onclick="document.getElementById('report-preview-panel').scrollIntoView({behavior: 'smooth'})" type="button">
<span class="material-symbols-outlined text-[18px]">print</span>
<span class="font-label-md text-label-md font-semibold">Xem bản in A4</span>
</button>
</div>
</div>
<!-- 4 KPI Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-space-md my-space-sm">
<!-- Card 1: Tổng số vi phạm -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between relative overflow-hidden">
<div class="absolute -right-3 -top-3 w-16 h-16 bg-surface-container rounded-full opacity-60 pointer-events-none"></div>
<div class="flex items-center justify-between z-10">
<span class="font-label-md text-label-md text-on-surface-variant">Tổng số vi phạm AI ghi nhận</span>
<span class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">smart_toy</span>
</span>
</div>
<div class="mt-space-md z-10">
<div class="flex items-baseline gap-2">
<span class="font-headline-xl text-headline-xl font-bold text-on-surface tracking-tight">1,280</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">lượt</span>
</div>
<div class="flex items-center gap-1 mt-2">
<span class="inline-flex items-center text-secondary font-label-sm text-label-sm bg-secondary-fixed/40 px-1.5 py-0.5 rounded-full">
<span class="material-symbols-outlined text-[13px] mr-0.5">trending_down</span>
            -5.4%
          </span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">so với Học kỳ II (1,353 lượt)</span>
</div>
</div>
</div>
<!-- Card 2: Không đeo thẻ -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between relative overflow-hidden">
<div class="flex items-center justify-between z-10">
<span class="font-label-md text-label-md text-on-surface-variant">Không đeo thẻ sinh viên</span>
<span class="w-8 h-8 rounded-lg bg-tertiary-fixed/60 flex items-center justify-center text-tertiary">
<span class="material-symbols-outlined text-[20px]">badge</span>
</span>
</div>
<div class="mt-space-md z-10">
<div class="flex items-baseline gap-2">
<span class="font-headline-xl text-headline-xl font-bold text-tertiary-container tracking-tight">756</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">lượt</span>
</div>
<div class="flex items-center justify-between mt-2">
<span class="font-label-sm text-label-sm px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-semibold">59.1% tổng lỗi</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Chủ yếu tại cửa lớp A.201</span>
</div>
</div>
</div>
<!-- Card 3: Không mặc đồng phục -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between relative overflow-hidden">
<div class="flex items-center justify-between z-10">
<span class="font-label-md text-label-md text-on-surface-variant">Không đồng phục / Tác phong</span>
<span class="w-8 h-8 rounded-lg bg-error-container flex items-center justify-center text-error">
<span class="material-symbols-outlined text-[20px]">styler</span>
</span>
</div>
<div class="mt-space-md z-10">
<div class="flex items-baseline gap-2">
<span class="font-headline-xl text-headline-xl font-bold text-error tracking-tight">412</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">lượt</span>
</div>
<div class="flex items-center justify-between mt-2">
<span class="font-label-sm text-label-sm px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-semibold">32.2% tổng lỗi</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Thứ 2 &amp; Thứ 6 cao điểm</span>
</div>
</div>
</div>
<!-- Card 4: Khung giờ cao điểm -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between relative overflow-hidden">
<div class="flex items-center justify-between z-10">
<span class="font-label-md text-label-md text-on-surface-variant">Khung giờ tập trung vi phạm</span>
<span class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">schedule</span>
</span>
</div>
<div class="mt-space-md z-10">
<div class="flex items-baseline gap-1">
<span class="font-headline-lg text-headline-lg font-bold text-on-surface">07:00 - 07:45</span>
</div>
<div class="flex items-center justify-between mt-2">
<span class="font-data-mono text-data-mono text-primary font-semibold">384 lượt (30.0%)</span>
<span class="font-label-sm text-label-sm px-1.5 py-0.5 bg-surface-container rounded text-on-surface-variant">Đầu ca 1</span>
</div>
</div>
</div>
</div>
<!-- Data Analytics Grid (Bento Matrix) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-space-md mt-space-md">
<!-- (A) Biểu đồ đường: Xu hướng theo tuần (Col 8) -->
<div class="lg:col-span-8 bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col">
<div class="flex items-center justify-between pb-space-sm">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Xu hướng vi phạm theo các tuần học kỳ</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Diễn biến phát hiện thẻ sinh viên &amp; đồng phục theo 8 tuần thực học</span>
</div>
<div class="flex items-center gap-3">
<div class="flex items-center gap-1.5">
<span class="w-3 h-3 rounded-full bg-primary"></span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Thẻ SV</span>
</div>
<div class="flex items-center gap-1.5">
<span class="w-3 h-3 rounded-full bg-error"></span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Đồng phục</span>
</div>
</div>
</div>
<!-- SVG Line Chart with gradient fills and data nodes -->
<div class="w-full h-56 pt-2">
<svg class="w-full h-full overflow-visible" viewBox="0 0 680 180">
<defs>
<linearGradient id="primaryAreaGrad" x1="0" x2="0" y1="0" y2="1">
<stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.25"></stop>
<stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.0"></stop>
</linearGradient>
<linearGradient id="errorAreaGrad" x1="0" x2="0" y1="0" y2="1">
<stop offset="0%" stop-color="#ba1a1a" stop-opacity="0.18"></stop>
<stop offset="100%" stop-color="#ba1a1a" stop-opacity="0.0"></stop>
</linearGradient>
</defs>
<!-- Horizontal guideline grid -->
<line stroke="#eff4ff" stroke-width="1.5" x1="30" x2="660" y1="20" y2="20"></line>
<text fill="#747686" font-family="Inter" font-size="10" text-anchor="end" x="18" y="24">140</text>
<line stroke="#eff4ff" stroke-width="1.5" x1="30" x2="660" y1="65" y2="65"></line>
<text fill="#747686" font-family="Inter" font-size="10" text-anchor="end" x="18" y="69">100</text>
<line stroke="#eff4ff" stroke-width="1.5" x1="30" x2="660" y1="110" y2="110"></line>
<text fill="#747686" font-family="Inter" font-size="10" text-anchor="end" x="18" y="114">60</text>
<line stroke="#eff4ff" stroke-width="1.5" x1="30" x2="660" y1="155" y2="155"></line>
<text fill="#747686" font-family="Inter" font-size="10" text-anchor="end" x="18" y="159">20</text>
<!-- Area Fills -->
<polygon fill="url(#primaryAreaGrad)" points="45,155 45,70 130,55 215,85 300,45 385,60 470,95 555,80 640,110 640,155"></polygon>
<polygon fill="url(#errorAreaGrad)" points="45,155 45,115 130,105 215,120 300,90 385,100 470,125 555,115 640,135 640,155"></polygon>
<!-- Line 1: Không thẻ sinh viên -->
<path d="M 45 70 L 130 55 L 215 85 L 300 45 L 385 60 L 470 95 L 555 80 L 640 110" fill="none" stroke="#1d4ed8" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
<!-- Line 2: Đồng phục -->
<path d="M 45 115 L 130 105 L 215 120 L 300 90 L 385 100 L 470 125 L 555 115 L 640 135" fill="none" stroke="#ba1a1a" stroke-dasharray="4 2" stroke-linecap="round" stroke-width="2.5"></path>
<!-- Circle Points Line 1 -->
<circle cx="45" cy="70" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<circle cx="130" cy="55" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<circle cx="215" cy="85" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<circle cx="300" cy="45" fill="#1d4ed8" r="5" stroke="#ffffff" stroke-width="2"></circle>
<circle cx="385" cy="60" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<circle cx="470" cy="95" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<circle cx="555" cy="80" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<circle cx="640" cy="110" fill="#ffffff" r="4" stroke="#1d4ed8" stroke-width="2"></circle>
<!-- X Labels -->
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="45" y="174">Tuần 1</text>
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="130" y="174">Tuần 2</text>
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="215" y="174">Tuần 3</text>
<text fill="#1d4ed8" font-size="10.5" font-weight="700" text-anchor="middle" x="300" y="174">Tuần 4 (Đỉnh)</text>
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="385" y="174">Tuần 5</text>
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="470" y="174">Tuần 6</text>
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="555" y="174">Tuần 7</text>
<text fill="#434655" font-size="10.5" font-weight="500" text-anchor="middle" x="640" y="174">Tuần 8</text>
</svg>
</div>
<div class="mt-2 pt-2 bg-surface-container-low rounded-xl px-space-md py-2 flex items-center justify-between text-body-sm text-on-surface-variant">
<span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[16px] text-primary">insights</span>Tuần 4 ghi nhận đỉnh cao nhất (118 ca) do bắt đầu kiểm tra điểm danh giữa kỳ.</span>
<span class="font-data-mono text-data-mono text-primary font-semibold cursor-pointer hover:underline">Chi tiết tuần 4 →</span>
</div>
</div>
<!-- (D) Biểu đồ Donut: Cơ cấu vi phạm (Col 4) -->
<div class="lg:col-span-4 bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between">
<div class="flex items-center justify-between">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Cơ cấu loại vi phạm</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Tỷ lệ phân bổ 3 nhóm</span>
</div>
<span class="material-symbols-outlined text-outline text-[20px]">pie_chart</span>
</div>
<!-- Donut Chart with central metric -->
<div class="relative w-40 h-40 mx-auto my-3 flex items-center justify-center">
<svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
<!-- Background circle track -->
<circle cx="50" cy="50" fill="transparent" r="38" stroke="#eff4ff" stroke-width="12"></circle>
<!-- Không đeo thẻ: 59% (stroke-dasharray = 59*2.387 = 140.8, circ = 238.7) -->
<circle cx="50" cy="50" fill="transparent" r="38" stroke="#1d4ed8" stroke-dasharray="140.8 97.9" stroke-dashoffset="0" stroke-width="12"></circle>
<!-- Không đồng phục: 32% (stroke-dasharray = 32*2.387 = 76.4) -->
<circle cx="50" cy="50" fill="transparent" r="38" stroke="#ba1a1a" stroke-dasharray="76.4 162.3" stroke-dashoffset="-140.8" stroke-width="12"></circle>
<!-- Cả hai lỗi: 9% (stroke-dasharray = 9*2.387 = 21.5) -->
<circle cx="50" cy="50" fill="transparent" r="38" stroke="#7c2900" stroke-dasharray="21.5 217.2" stroke-dashoffset="-217.2" stroke-width="12"></circle>
</svg>
<div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
<span class="font-headline-lg text-headline-lg font-bold text-on-surface">1,280</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Lượt vi phạm</span>
</div>
</div>
<!-- Donut Legend & Stats -->
<div class="flex flex-col gap-2">
<div class="flex items-center justify-between text-body-sm p-1.5 bg-surface-container-low rounded-lg">
<div class="flex items-center gap-2">
<span class="w-3 h-3 rounded-full bg-primary-container shrink-0"></span>
<span class="text-on-surface">Không đeo thẻ</span>
</div>
<div class="flex items-center gap-2">
<span class="font-data-mono text-data-mono font-semibold text-on-surface">756</span>
<span class="font-label-sm text-label-sm px-1.5 py-0.2 bg-primary-fixed text-on-primary-fixed rounded">59.1%</span>
</div>
</div>
<div class="flex items-center justify-between text-body-sm p-1.5 bg-surface-container-low rounded-lg">
<div class="flex items-center gap-2">
<span class="w-3 h-3 rounded-full bg-error shrink-0"></span>
<span class="text-on-surface">Không mặc đồng phục</span>
</div>
<div class="flex items-center gap-2">
<span class="font-data-mono text-data-mono font-semibold text-on-surface">412</span>
<span class="font-label-sm text-label-sm px-1.5 py-0.2 bg-error-container text-on-error-container rounded">32.2%</span>
</div>
</div>
<div class="flex items-center justify-between text-body-sm p-1.5 bg-surface-container-low rounded-lg">
<div class="flex items-center gap-2">
<span class="w-3 h-3 rounded-full bg-tertiary shrink-0"></span>
<span class="text-on-surface">Cả hai lỗi kết hợp</span>
</div>
<div class="flex items-center gap-2">
<span class="font-data-mono text-data-mono font-semibold text-on-surface">112</span>
<span class="font-label-sm text-label-sm px-1.5 py-0.2 bg-tertiary-fixed text-on-tertiary-fixed rounded">8.7%</span>
</div>
</div>
</div>
</div>
<!-- (B) Heatmap Ma trận: Khung giờ x Phòng học / Camera (Col 8) -->
<div class="lg:col-span-8 bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col">
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-space-sm">
<div class="flex flex-col">
<div class="flex items-center gap-2">
<span class="font-headline-sm text-headline-sm text-on-surface">Ma trận nhiệt vi phạm (Heatmap)</span>
<span class="px-2 py-0.5 rounded-full bg-surface-container text-on-surface-variant font-data-mono text-[11px]">AI Realtime Feed</span>
</div>
<span class="font-body-sm text-body-sm text-on-surface-variant">Phân bố mật độ vi phạm theo Khung giờ x Phòng học / Điểm đặt Camera AI</span>
</div>
<!-- Heat Legend -->
<div class="flex items-center gap-1.5 text-label-sm text-outline">
<span>0 lỗi</span>
<div class="flex gap-0.5">
<span class="w-3.5 h-3.5 rounded bg-surface-container" title="0-2 lỗi"></span>
<span class="w-3.5 h-3.5 rounded bg-surface-container-high" title="3-7 lỗi"></span>
<span class="w-3.5 h-3.5 rounded bg-tertiary-fixed" title="8-14 lỗi"></span>
<span class="w-3.5 h-3.5 rounded bg-tertiary-fixed-dim" title="15-22 lỗi"></span>
<span class="w-3.5 h-3.5 rounded bg-error-container text-on-error-container" title="23-30 lỗi"></span>
<span class="w-3.5 h-3.5 rounded bg-error" title="&gt;30 lỗi"></span>
</div>
<span class="text-error font-semibold">&gt;30 lỗi</span>
</div>
</div>
<!-- Matrix Heatmap Table Grid -->
<div class="overflow-x-auto mt-2">
<table class="w-full border-collapse">
<thead>
<tr>
<th class="text-left font-label-sm text-label-sm text-outline p-2 w-28 bg-surface-container-low rounded-l-lg">Khung giờ \ Phòng</th>
<th class="font-label-sm text-label-sm text-on-surface p-2 text-center bg-surface-container-low">Phòng A.201</th>
<th class="font-label-sm text-label-sm text-on-surface p-2 text-center bg-surface-container-low">Phòng A.305</th>
<th class="font-label-sm text-label-sm text-on-surface p-2 text-center bg-surface-container-low">Giảng đường B.102</th>
<th class="font-label-sm text-label-sm text-on-surface p-2 text-center bg-surface-container-low">Phòng B.204</th>
<th class="font-label-sm text-label-sm text-on-surface p-2 text-center bg-surface-container-low">Phòng máy C.402</th>
<th class="font-label-sm text-label-sm text-on-surface p-2 text-center bg-surface-container-low rounded-r-lg">GĐ C.101</th>
</tr>
</thead>
<tbody class="divide-y-0 text-center font-data-mono text-data-mono">
<!-- Row 1: 06:30 - 07:30 -->
<tr>
<td class="text-left font-label-md text-label-md text-on-surface-variant py-2.5 px-2">06:30 - 07:30</td>
<td class="p-1"><div class="p-2 rounded-lg bg-error text-on-error font-bold shadow-sm" title="34 lượt vi phạm">34</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed-dim text-on-tertiary-fixed font-semibold" title="19 lượt">19</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-error-container text-on-error-container font-bold" title="28 lượt">28</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed font-semibold" title="14 lượt">14</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="6 lượt">6</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed-dim text-on-tertiary-fixed font-semibold" title="21 lượt">21</div></td>
</tr>
<!-- Row 2: 07:30 - 08:30 -->
<tr>
<td class="text-left font-label-md text-label-md text-on-surface-variant py-2.5 px-2">07:30 - 08:30</td>
<td class="p-1"><div class="p-2 rounded-lg bg-error-container text-on-error-container font-semibold" title="24 lượt">24</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed" title="12 lượt">12</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed-dim text-on-tertiary-fixed font-semibold" title="18 lượt">18</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="7 lượt">7</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="2 lượt">2</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed" title="11 lượt">11</div></td>
</tr>
<!-- Row 3: 08:30 - 09:30 -->
<tr>
<td class="text-left font-label-md text-label-md text-on-surface-variant py-2.5 px-2">08:30 - 09:30</td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="6 lượt">6</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="4 lượt">4</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="7 lượt">7</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="1 lượt">1</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="0 lượt">0</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="5 lượt">5</div></td>
</tr>
<!-- Row 4: 12:30 - 13:30 -->
<tr>
<td class="text-left font-label-md text-label-md text-on-surface-variant py-2.5 px-2">12:30 - 13:30</td>
<td class="p-1"><div class="p-2 rounded-lg bg-error-container text-on-error-container font-semibold" title="26 lượt">26</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed" title="15 lượt">15</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-error text-on-error font-bold shadow-sm" title="31 lượt">31</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed" title="10 lượt">10</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="5 lượt">5</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed-dim text-on-tertiary-fixed font-semibold" title="19 lượt">19</div></td>
</tr>
<!-- Row 5: 13:30 - 14:30 -->
<tr>
<td class="text-left font-label-md text-label-md text-on-surface-variant py-2.5 px-2">13:30 - 14:30</td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed" title="11 lượt">11</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="7 lượt">7</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed" title="13 lượt">13</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="4 lượt">4</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="1 lượt">1</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="8 lượt">8</div></td>
</tr>
<!-- Row 6: 15:30 - 16:30 -->
<tr>
<td class="text-left font-label-md text-label-md text-on-surface-variant py-2.5 px-2">15:30 - 16:30</td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="4 lượt">4</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="2 lượt">2</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="5 lượt">5</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="2 lượt">2</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container text-on-surface-variant" title="0 lượt">0</div></td>
<td class="p-1"><div class="p-2 rounded-lg bg-surface-container-high text-on-surface" title="3 lượt">3</div></td>
</tr>
</tbody>
</table>
</div>
<div class="mt-3 flex items-center justify-between text-body-sm text-outline">
<span class="flex items-center gap-1"><span class="material-symbols-outlined text-[15px] text-error">campaign</span>Hai điểm nóng cần bố trí Đoàn Thanh niên hỗ trợ nhắc nhở: <strong>Cửa A.201</strong> và <strong>Giảng đường B.102</strong></span>
<span class="font-label-sm text-label-sm">Cập nhật 15 phút/lần</span>
</div>
</div>
<!-- (C) Biểu đồ cột: Top phòng học vi phạm nhiều nhất (Col 4) -->
<div class="lg:col-span-4 bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col justify-between">
<div class="flex items-center justify-between pb-space-sm">
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm text-on-surface">Top phòng học vi phạm</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Thứ hạng theo lưu lượng quét AI</span>
</div>
<span class="material-symbols-outlined text-[20px] text-primary">equalizer</span>
</div>
<!-- Horizontal Bar Visualization -->
<div class="flex flex-col gap-3.5 my-2">
<!-- Room 1 -->
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between text-body-sm">
<span class="font-label-md text-label-md text-on-surface font-semibold">1. Giảng đường B.102</span>
<span class="font-data-mono text-data-mono font-bold text-primary">312 lượt (24.4%)</span>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-primary-container rounded-full" style="width: 82%;"></div>
</div>
</div>
<!-- Room 2 -->
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between text-body-sm">
<span class="font-label-md text-label-md text-on-surface font-semibold">2. Phòng A.201</span>
<span class="font-data-mono text-data-mono font-bold text-primary">285 lượt (22.3%)</span>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-primary-container rounded-full" style="width: 74%;"></div>
</div>
</div>
<!-- Room 3 -->
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between text-body-sm">
<span class="font-label-md text-label-md text-on-surface font-semibold">3. Giảng đường C.101</span>
<span class="font-data-mono text-data-mono font-bold text-primary">248 lượt (19.4%)</span>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-primary rounded-full" style="width: 65%;"></div>
</div>
</div>
<!-- Room 4 -->
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between text-body-sm">
<span class="font-label-md text-label-md text-on-surface font-semibold">4. Phòng A.305</span>
<span class="font-data-mono text-data-mono font-bold text-on-surface-variant">194 lượt (15.2%)</span>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-surface-tint/60 rounded-full" style="width: 50%;"></div>
</div>
</div>
<!-- Room 5 -->
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between text-body-sm">
<span class="font-label-md text-label-md text-on-surface font-semibold">5. Phòng B.204</span>
<span class="font-data-mono text-data-mono font-bold text-on-surface-variant">142 lượt (11.1%)</span>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-surface-container-highest rounded-full" style="width: 37%;"></div>
</div>
</div>
<!-- Room 6 -->
<div class="flex flex-col gap-1">
<div class="flex items-center justify-between text-body-sm">
<span class="font-label-md text-label-md text-on-surface font-semibold">6. Phòng máy C.402</span>
<span class="font-data-mono text-data-mono font-bold text-on-surface-variant">99 lượt (7.6%)</span>
</div>
<div class="w-full h-2.5 bg-surface-container rounded-full overflow-hidden">
<div class="h-full bg-surface-container-highest rounded-full" style="width: 26%;"></div>
</div>
</div>
</div>
<div class="pt-2 bg-surface-container-low rounded-lg p-2.5 flex items-center justify-between">
<span class="font-body-sm text-body-sm text-on-surface-variant">Tỷ trọng 3 phòng lớn nhất:</span>
<span class="font-headline-sm text-headline-sm text-primary font-bold">66.1%</span>
</div>
</div>
</div>
<!-- Snapshot Camera Feeds Row with Image Placeholders -->
<div class="mt-space-md bg-surface-container-lowest p-space-md rounded-xl shadow-sm">
<div class="flex items-center justify-between mb-space-sm">
<div class="flex items-center gap-2">
<span class="w-2.5 h-2.5 rounded-full bg-error animate-pulse"></span>
<span class="font-headline-sm text-headline-sm text-on-surface">Hình ảnh trích xuất từ Camera AI thời gian thực</span>
<span class="font-label-sm text-label-sm text-outline hidden md:inline">• Nhận diện khuôn mặt &amp; phát hiện trang phục</span>
</div>
<button class="font-label-sm text-label-sm text-primary font-semibold hover:underline flex items-center gap-1">
        Xem tất cả camera <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
</button>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-space-md">
<!-- Feed 1 -->
<div class="bg-surface-container-low rounded-xl overflow-hidden shadow-sm flex flex-col">
<div class="relative h-44 w-full bg-surface-dim">
<img class="w-full h-full object-cover" data-alt="Camera surveillance viewpoint overlooking a modern university lecture hall doorway, highlighting students entering with colored bounding boxes around identification badges, crisp academic setting, cool fluorescent lighting" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCsv8k1rXJ4tFbpsVVEKG7_qz_--cd60f2Sf0xGd4P3TLwVWTzYwPSpK1IojKY-r2v9_DZTPRyCH2fFLqlZxcijFelrM-q_Jyphx1AJdBMqIbPPj8U73IKKnAmgAZnh_yWJM4K15X9HxQHmpKzTrcf0Dd_VHYS839MhMPyDu8olkYhMYq2tS10J4zjF8botjxBbnHuMopz2Iv9mtB58225vHsJ3VhmfXMf4oipMO3Y">
<div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-inverse-surface/80 text-inverse-on-surface font-data-mono text-[11px] backdrop-blur-sm">
            CAM_01 • GĐ B.102
          </div>
<div class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-[10px] font-bold">
            Không đeo thẻ
          </div>
<div class="absolute bottom-2 left-2 px-2 py-0.5 rounded bg-inverse-surface/90 text-inverse-on-surface font-data-mono text-[10px]">
            07:18:42 • 28/10/2024
          </div>
</div>
<div class="p-3 flex items-center justify-between">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Trần Minh Hoàng</span>
<span class="font-data-mono text-data-mono text-primary">MSSV: 20214892 • ĐTVT-02</span>
</div>
<button class="px-2.5 py-1 rounded-lg bg-surface-container-lowest text-primary hover:bg-surface-container font-label-sm text-label-sm shadow-sm transition-all">Chi tiết</button>
</div>
</div>
<!-- Feed 2 -->
<div class="bg-surface-container-low rounded-xl overflow-hidden shadow-sm flex flex-col">
<div class="relative h-44 w-full bg-surface-dim">
<img class="w-full h-full object-cover" data-alt="Security camera snapshot showing university students walking along a bright hallway near classroom A201, AI detection bounding box highlighting non-regulation clothing, high resolution campus surveillance" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDiUdo04NXkV0zgi8pOzITPHtcqtzdnkhjMkovcChBPUwIM7szN6invZ3_8PQcaZvQ9c6kkeK7gAv1qme_5y5sKDqFDAB_PsWmmjnN4lTVgaF_-Q3zftuCoNZum0v7ArDHBvsffX7xH3MkAhG1eaCw2ckrXW4kME9_dVCm2znfEYP4sjCjpgerXB9wMrQkMhSH1O26QThat17mzrLHc4yGFdNJhW_V1ouahXQ-dLS8">
<div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-inverse-surface/80 text-inverse-on-surface font-data-mono text-[11px] backdrop-blur-sm">
            CAM_04 • Hành lang A.201
          </div>
<div class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-[10px] font-bold">
            Không đồng phục
          </div>
<div class="absolute bottom-2 left-2 px-2 py-0.5 rounded bg-inverse-surface/90 text-inverse-on-surface font-data-mono text-[10px]">
            07:22:15 • 28/10/2024
          </div>
</div>
<div class="p-3 flex items-center justify-between">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Lê Bảo Trâm</span>
<span class="font-data-mono text-data-mono text-primary">MSSV: 20223105 • CNTT-04</span>
</div>
<button class="px-2.5 py-1 rounded-lg bg-surface-container-lowest text-primary hover:bg-surface-container font-label-sm text-label-sm shadow-sm transition-all">Chi tiết</button>
</div>
</div>
<!-- Feed 3 -->
<div class="bg-surface-container-low rounded-xl overflow-hidden shadow-sm flex flex-col">
<div class="relative h-44 w-full bg-surface-dim">
<img class="w-full h-full object-cover" data-alt="University computer laboratory entrance camera view with artificial intelligence green badge verified frame, soft natural lighting through windows, calm academic environment" src="https://lh3.googleusercontent.com/aida-public/AB6AXuCKq-aNWS3JdPByL2wZDhGbeQqJ2IWVJKmGvE_6xuUA_qoCHj1O_KgLr3MN3icVPbsqMfH0AyY-IZPLXLYulThS6w6JCX9CYCyddZIIoEmKDXo_PDR_n_cufWZ6zJcjLzcLEWWqTgVru0wGADZH6E_W1n43PMp7tSZX3pssQRJg-MrvFMY57pVSq9ZQx3bQA54umy2HbYKp54LMo41Sf3DH4GxpaoqnCESGcu3Xmas">
<div class="absolute top-2 left-2 px-2 py-0.5 rounded-md bg-inverse-surface/80 text-inverse-on-surface font-data-mono text-[11px] backdrop-blur-sm">
            CAM_06 • Cửa C.402
          </div>
<div class="absolute top-2 right-2 px-2 py-0.5 rounded-full bg-secondary-fixed text-on-secondary-fixed font-label-sm text-[10px] font-bold">
            Hợp lệ / Chuẩn tác phong
          </div>
<div class="absolute bottom-2 left-2 px-2 py-0.5 rounded bg-inverse-surface/90 text-inverse-on-surface font-data-mono text-[10px]">
            07:35:02 • 28/10/2024
          </div>
</div>
<div class="p-3 flex items-center justify-between">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Nguyễn Quốc Thái</span>
<span class="font-data-mono text-data-mono text-primary">MSSV: 20205561 • KTPM-01</span>
</div>
<button class="px-2.5 py-1 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container font-label-sm text-label-sm shadow-sm transition-all">Lưu trữ</button>
</div>
</div>
</div>
</div>
<!-- (4) PANEL XEM TRƯỚC BÁO CÁO IN ẤN CHUẨN A4 -->
<div class="mt-space-lg flex flex-col gap-space-sm" id="report-preview-panel">
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-space-sm">
<div class="flex items-center gap-2">
<div class="w-8 h-8 rounded-lg bg-primary-container text-on-primary flex items-center justify-center">
<span class="material-symbols-outlined text-[20px]">document_scanner</span>
</div>
<div class="flex flex-col">
<span class="font-headline-lg text-headline-lg font-bold text-on-surface">Xem trước bản in Báo cáo Tổng hợp (Chuẩn A4)</span>
<span class="font-body-sm text-body-sm text-on-surface-variant">Định dạng hành chính chuẩn theo quy chế Phòng Công tác Sinh viên Đại học</span>
</div>
</div>
<div class="flex items-center gap-2 self-end sm:self-auto">
<button class="flex items-center gap-1.5 px-4 py-2 bg-primary hover:bg-primary-container text-on-primary rounded-xl shadow-md transition-all" onclick="window.print()" type="button">
<span class="material-symbols-outlined text-[18px]">print</span>
<span class="font-label-md text-label-md font-semibold">Tiến hành in ngay</span>
</button>
<button class="flex items-center gap-1 px-3 py-2 bg-surface-container hover:bg-surface-container-high text-on-surface rounded-xl shadow-sm transition-all" type="button">
<span class="material-symbols-outlined text-[18px]">fullscreen</span>
<span class="font-label-md text-label-md">Toàn màn hình</span>
</button>
</div>
</div>
<!-- Khung mô phỏng tờ giấy A4 màu trắng chính xác -->
<div class="bg-surface-container-lowest text-on-surface rounded-xl shadow-xl p-8 sm:p-12 md:p-16 mx-auto w-full max-w-4xl my-4 flex flex-col font-body-md text-body-md select-text">
<!-- Header Văn bản Hành chính Chuẩn -->
<div class="flex flex-col sm:flex-row items-center sm:items-start justify-between text-center sm:text-left gap-4 pb-6">
<div class="flex flex-col items-center sm:items-start">
<span class="font-label-md text-label-md uppercase tracking-wider font-semibold text-on-surface">ĐẠI HỌC BÁCH KHOA HÀ NỘI</span>
<span class="font-headline-sm text-headline-sm uppercase font-bold text-primary">PHÒNG CÔNG TÁC SINH VIÊN</span>
<span class="font-data-mono text-[11px] text-outline mt-0.5">Số: 842 /BC-CTSV-AI</span>
</div>
<div class="flex flex-col items-center sm:items-end text-center sm:text-right">
<span class="font-label-md text-label-md uppercase font-bold text-on-surface">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</span>
<span class="font-label-sm text-label-sm font-semibold text-on-surface">Độc lập - Tự do - Hạnh phúc</span>
<div class="w-32 h-[1px] bg-outline my-1 mx-auto sm:mr-0"></div>
<span class="font-body-sm text-body-sm italic text-outline mt-1">Hà Nội, ngày 31 tháng 10 năm 2024</span>
</div>
</div>
<!-- Tên Báo Cáo Chính -->
<div class="text-center my-6 flex flex-col items-center">
<span class="font-headline-xl text-headline-xl font-bold uppercase tracking-tight text-on-surface">BÁO CÁO TỔNG HỢP VI PHẠM TÁC PHONG SINH VIÊN</span>
<span class="font-headline-sm text-headline-sm font-medium text-primary mt-1">Học kỳ I - Năm học 2024 - 2025 (Dữ liệu Camera AI giám sát)</span>
<span class="font-body-sm text-body-sm italic text-on-surface-variant mt-0.5">(Thời gian trích xuất: Từ ngày 01/09/2024 đến hết ngày 31/10/2024)</span>
</div>
<!-- Khái quát số liệu -->
<div class="my-3">
<p class="font-body-md text-body-md text-on-surface leading-relaxed text-justify indent-6">
          Căn cứ Quy chế Công tác Sinh viên hiện hành và dữ liệu nhận diện tự động từ Hệ thống Camera AI tại các giảng đường khu A, B, C; Phòng Công tác Sinh viên kính báo cáo tổng kết tình hình chấp hành nếp sống văn hóa học đường, đeo thẻ sinh viên và đồng phục tác phong trong 02 tháng đầu Học kỳ I năm học 2024 - 2025 với các nội dung chi tiết như sau:
        </p>
</div>
<!-- Bảng 1: Số liệu tổng hợp theo Khoa / Viện -->
<div class="my-4">
<div class="font-headline-sm text-headline-sm font-bold text-on-surface mb-2 flex items-center gap-1.5">
<span>I. Thống kê số lượng vi phạm phân bổ theo Khoa / Viện đào tạo</span>
</div>
<div class="overflow-x-auto rounded-lg shadow-sm">
<table class="w-full text-left font-body-sm text-body-sm">
<thead class="bg-surface-container-low text-on-surface font-label-sm text-label-sm uppercase">
<tr>
<th class="py-2 px-3 text-center w-12">STT</th>
<th class="py-2 px-3">Khoa / Viện quản trị</th>
<th class="py-2 px-3 text-center">Tổng SV</th>
<th class="py-2 px-3 text-center">Không đeo thẻ</th>
<th class="py-2 px-3 text-center">Không đồng phục</th>
<th class="py-2 px-3 text-center">Tổng vi phạm</th>
<th class="py-2 px-3 text-center">Tỷ lệ / SV</th>
</tr>
</thead>
<tbody class="divide-y-0 text-on-surface">
<tr class="hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">01</td>
<td class="py-2 px-3 font-semibold">Trường CNTT &amp; Truyền thông</td>
<td class="py-2 px-3 text-center font-data-mono">4,120</td>
<td class="py-2 px-3 text-center font-data-mono text-tertiary">238</td>
<td class="py-2 px-3 text-center font-data-mono text-error">115</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-primary">353</td>
<td class="py-2 px-3 text-center font-data-mono">8.56%</td>
</tr>
<tr class="bg-surface-container-lowest hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">02</td>
<td class="py-2 px-3 font-semibold">Trường Cơ khí - Ô tô</td>
<td class="py-2 px-3 text-center font-data-mono">3,850</td>
<td class="py-2 px-3 text-center font-data-mono text-tertiary">210</td>
<td class="py-2 px-3 text-center font-data-mono text-error">128</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-primary">338</td>
<td class="py-2 px-3 text-center font-data-mono">8.77%</td>
</tr>
<tr class="hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">03</td>
<td class="py-2 px-3 font-semibold">Trường Điện - Điện tử</td>
<td class="py-2 px-3 text-center font-data-mono">3,400</td>
<td class="py-2 px-3 text-center font-data-mono text-tertiary">174</td>
<td class="py-2 px-3 text-center font-data-mono text-error">89</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-primary">263</td>
<td class="py-2 px-3 text-center font-data-mono">7.73%</td>
</tr>
<tr class="bg-surface-container-lowest hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">04</td>
<td class="py-2 px-3 font-semibold">Viện Kinh tế &amp; Quản lý</td>
<td class="py-2 px-3 text-center font-data-mono">2,150</td>
<td class="py-2 px-3 text-center font-data-mono text-tertiary">84</td>
<td class="py-2 px-3 text-center font-data-mono text-error">42</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-primary">126</td>
<td class="py-2 px-3 text-center font-data-mono">5.86%</td>
</tr>
<tr class="hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">05</td>
<td class="py-2 px-3 font-semibold">Các Khoa Đào tạo Quốc tế</td>
<td class="py-2 px-3 text-center font-data-mono">1,980</td>
<td class="py-2 px-3 text-center font-data-mono text-tertiary">50</td>
<td class="py-2 px-3 text-center font-data-mono text-error">38</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-primary">88</td>
<td class="py-2 px-3 text-center font-data-mono">4.44%</td>
</tr>
<!-- Summary Row -->
<tr class="bg-surface-container-low font-bold">
<td class="py-2.5 px-3 text-right uppercase text-on-surface font-label-md" colspan="2">Tổng cộng toàn trường:</td>
<td class="py-2.5 px-3 text-center font-data-mono">15,500</td>
<td class="py-2.5 px-3 text-center font-data-mono text-tertiary">756</td>
<td class="py-2.5 px-3 text-center font-data-mono text-error">412</td>
<td class="py-2.5 px-3 text-center font-data-mono text-primary font-bold">1,280</td>
<td class="py-2.5 px-3 text-center font-data-mono">8.25%</td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Bảng 2: Danh sách rút gọn các trường hợp điển hình -->
<div class="my-4">
<div class="font-headline-sm text-headline-sm font-bold text-on-surface mb-2 flex items-center justify-between">
<span>II. Danh sách trích lục sinh viên tái phạm trên 03 lần (Đã gửi cảnh cáo)</span>
<span class="font-label-sm text-label-sm text-outline font-normal">Trích 4 / 28 trường hợp</span>
</div>
<div class="overflow-x-auto rounded-lg shadow-sm">
<table class="w-full text-left font-body-sm text-body-sm">
<thead class="bg-surface-container-low text-on-surface font-label-sm text-label-sm">
<tr>
<th class="py-2 px-3 text-center w-12">STT</th>
<th class="py-2 px-3">MSSV</th>
<th class="py-2 px-3">Họ và tên</th>
<th class="py-2 px-3">Lớp sinh hoạt</th>
<th class="py-2 px-3 text-center">Số lần vi phạm</th>
<th class="py-2 px-3">Lỗi chủ yếu</th>
<th class="py-2 px-3 text-center">Hình thức đề xuất</th>
</tr>
</thead>
<tbody class="divide-y-0 text-on-surface">
<tr class="hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">01</td>
<td class="py-2 px-3 font-data-mono font-bold text-primary">20215033</td>
<td class="py-2 px-3 font-semibold">Phạm Văn Dũng</td>
<td class="py-2 px-3">KTPM 02-K66</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-error">5 lần</td>
<td class="py-2 px-3 text-on-surface-variant">Không đeo thẻ sinh viên</td>
<td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-[11px] font-bold">Trừ 5đ Rèn luyện</span></td>
</tr>
<tr class="bg-surface-container-lowest hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">02</td>
<td class="py-2 px-3 font-data-mono font-bold text-primary">20224190</td>
<td class="py-2 px-3 font-semibold">Trần Quỳnh Anh</td>
<td class="py-2 px-3">QTKD 01-K67</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-error">4 lần</td>
<td class="py-2 px-3 text-on-surface-variant">Không mang đồng phục ca 1</td>
<td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm text-[11px] font-bold">Trừ 3đ Rèn luyện</span></td>
</tr>
<tr class="hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">03</td>
<td class="py-2 px-3 font-data-mono font-bold text-primary">20202914</td>
<td class="py-2 px-3 font-semibold">Vũ Hoàng Nam</td>
<td class="py-2 px-3">CĐT 03-K65</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-tertiary">3 lần</td>
<td class="py-2 px-3 text-on-surface-variant">Cả hai lỗi (Thẻ + Áo)</td>
<td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-[11px] font-semibold">Cảnh cáo nhắc nhở</span></td>
</tr>
<tr class="bg-surface-container-lowest hover:bg-surface-container-low">
<td class="py-2 px-3 text-center font-data-mono">04</td>
<td class="py-2 px-3 font-data-mono font-bold text-primary">20231882</td>
<td class="py-2 px-3 font-semibold">Ngô Thanh Tùng</td>
<td class="py-2 px-3">DTVT 01-K68</td>
<td class="py-2 px-3 text-center font-data-mono font-bold text-tertiary">3 lần</td>
<td class="py-2 px-3 text-on-surface-variant">Không đeo thẻ sinh viên</td>
<td class="py-2 px-3 text-center"><span class="px-2 py-0.5 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm text-[11px] font-semibold">Cảnh cáo nhắc nhở</span></td>
</tr>
</tbody>
</table>
</div>
</div>
<!-- Kết luận & Đề xuất -->
<div class="my-3 text-body-md text-on-surface leading-relaxed">
<span class="font-headline-sm text-headline-sm font-bold">III. Đánh giá chung &amp; Kiến nghị:</span>
<ul class="list-disc list-inside mt-1 space-y-1 text-on-surface-variant">
<li>Tỷ lệ vi phạm toàn trường đạt <strong>8.25%</strong>, giảm 0.6% so với kỳ trước, cho thấy hiệu quả răn đe của hệ thống Camera AI giám sát tự động.</li>
<li>Đề nghị Ban cán sự các lớp K67, K68 tăng cường đôn đốc sinh viên đeo thẻ ngay từ sảnh tầng 1 trước 07:00.</li>
<li>Đồng bộ kết quả quét tự động vào cơ sở dữ liệu Hệ thống Quản lý Điểm rèn luyện sinh viên trước ngày 05/11/2024.</li>
</ul>
</div>
<!-- Chữ Ký Trưởng Phòng và Người Lập Báo Cáo -->
<div class="mt-8 pt-4 flex flex-row items-start justify-between text-center px-8">
<div class="flex flex-col items-center">
<span class="font-label-md text-label-md uppercase font-bold text-on-surface">NGƯỜI LẬP BÁO CÁO</span>
<span class="font-body-sm text-body-sm italic text-outline">(Ký và ghi rõ họ tên)</span>
<div class="h-20 flex items-center justify-center">
<span class="font-data-mono text-[13px] text-primary/40 italic select-none">Nguyen Van A (Đã ký số)</span>
</div>
<span class="font-label-md text-label-md font-semibold text-on-surface">Nguyễn Văn A</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Chuyên viên Quản lý Dữ liệu AI CTSV</span>
</div>
<div class="flex flex-col items-center">
<span class="font-label-md text-label-md uppercase font-bold text-on-surface">TRƯỞNG PHÒNG CÔNG TÁC SINH VIÊN</span>
<span class="font-body-sm text-body-sm italic text-outline">(Ký tên và đóng dấu xác nhận)</span>
<div class="h-20 flex items-center justify-center">
<span class="font-data-mono text-[13px] text-error/40 italic select-none">TS. Tran B (Đã duyệt)</span>
</div>
<span class="font-label-md text-label-md font-semibold text-on-surface">PGS. TS. Trần Đình B</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Trưởng phòng CTSV</span>
</div>
</div>
<!-- Footer trang in A4 -->
<div class="mt-12 pt-4 flex items-center justify-between text-[11px] text-outline font-data-mono">
<span>Hệ thống giám sát Camera AI • v2.4</span>
<span>Trang 01 / 02</span>
<span>Thời gian in: 31/10/2024 16:45:10</span>
</div>
</div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
