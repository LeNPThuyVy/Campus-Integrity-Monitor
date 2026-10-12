<?php include 'includes/header.php'; ?>
<div class="flex flex-col w-full pb-space-xl">
<!-- Top Section: Header, Summary Badges & Quick Action Controls -->
<div class="flex flex-col xl:flex-row items-start xl:items-end justify-between gap-space-md py-space-md">
<div class="flex flex-col gap-1 max-w-2xl">
<div class="flex items-center gap-space-xs text-primary font-label-sm text-label-sm uppercase tracking-wider">
<span class="material-symbols-outlined text-[16px]">school</span>
<span class="">Cơ sở dữ liệu sinh viên toàn trường</span>
</div>
<h1 class="font-headline-xl text-headline-xl text-on-surface">Quản lý sinh viên</h1>
<p class="font-body-md text-body-md text-on-surface-variant">Danh sách hồ sơ sinh viên</p>
</div>
<!-- Quick action buttons -->
<div class="flex flex-wrap items-center gap-space-sm w-full xl:w-auto">
<button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-primary font-label-md text-label-md transition-all shadow-sm" id="open-import-modal-btn">
<span class="material-symbols-outlined text-[18px]">upload_file</span>
<span class="">Nhập từ Excel</span>
</button>
<button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all shadow-sm">
<span class="material-symbols-outlined text-[18px]">file_download</span>
<span class="">Xuất danh sách</span>
</button>
<button class="flex items-center gap-space-xs px-4 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary transition-all font-label-md text-label-md shadow-sm" id="open-drawer-btn">
<span class="material-symbols-outlined text-[18px]">person_add</span>
<span class="">Thêm sinh viên</span>
</button>
</div>
</div>
<!-- Bento Section: 3 Stat Cards + Mini Visualizer Bar Chart -->
<div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-12 gap-space-md mt-space-sm mb-space-lg">
<!-- Stat 1: Total Students -->
<div class="md:col-span-1 xl:col-span-3 bg-surface-container-lowest p-space-md rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.04)] flex flex-col justify-between relative overflow-hidden">
<div class="flex items-center justify-between">
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Tổng sinh viên</span>
<div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">groups</span>
</div>
</div>
<div class="mt-space-md flex items-baseline gap-2">
<span class="font-headline-xl text-[28px] text-on-surface tracking-tight font-bold">12,450</span>
<span class="font-label-sm text-label-sm text-secondary font-medium">SV chính quy</span>
</div>
<div class="mt-space-xs flex items-center gap-1.5 text-on-surface-variant font-body-sm text-body-sm">
<span class="text-secondary font-medium flex items-center text-[11px]"><span class="material-symbols-outlined text-[14px]">trending_up</span> +4.2%</span>
<span class="">so với học kỳ I</span>
</div>
<div class="absolute -bottom-6 -right-6 w-20 h-20 bg-primary/5 rounded-full pointer-events-none"></div>
</div>
<!-- Stat 2: Faculties -->
<div class="md:col-span-1 xl:col-span-2 bg-surface-container-lowest p-space-md rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.04)] flex flex-col justify-between relative overflow-hidden">
<div class="flex items-center justify-between">
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Số khoa</span>
<div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">account_balance</span>
</div>
</div>
<div class="mt-space-md flex items-baseline gap-2">
<span class="font-headline-xl text-[28px] text-on-surface tracking-tight font-bold">8</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Khoa đào tạo</span>
</div>
<div class="mt-space-xs text-on-surface-variant font-body-sm text-body-sm truncate">
        CNTT chiếm 34%
      </div>
</div>
<!-- Stat 3: Classes -->
<div class="md:col-span-1 xl:col-span-2 bg-surface-container-lowest p-space-md rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.04)] flex flex-col justify-between relative overflow-hidden">
<div class="flex items-center justify-between">
<span class="font-label-md text-label-md text-on-surface-variant uppercase tracking-wider">Số lớp</span>
<div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
<span class="material-symbols-outlined text-[20px]">meeting_room</span>
</div>
</div>
<div class="mt-space-md flex items-baseline gap-2">
<span class="font-headline-xl text-[28px] text-on-surface tracking-tight font-bold">142</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Lớp sinh hoạt</span>
</div>
<div class="mt-space-xs text-on-surface-variant font-body-sm text-body-sm truncate">
        Trung bình 88 SV/lớp
      </div>
</div>
<!-- Mini Chart: Distribution by Department/Class -->

</div>
<!-- Filter Toolbar Card -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.04)] mb-space-md">
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-space-sm items-center">
<!-- Search Input -->
<div class="lg:col-span-4 relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[18px] text-outline">search</span>
<input class="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none transition-all shadow-inner" placeholder="Tìm kiếm theo MSSV hoặc Họ tên sinh viên..." type="text">
</div>
<!-- Khoa Dropdown -->
<div class="lg:col-span-3">
<div class="relative">
<select class="w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none cursor-pointer">
<option value="">-- Tất cả Khoa --</option>
<option selected="" value="cntt">Khoa Công nghệ thông tin</option>
<option value="kt">Khoa Kinh tế &amp; Quản trị</option>
<option value="ck">Khoa Cơ khí - Động lực</option>
<option value="nn">Khoa Ngoại ngữ</option>
<option value="dt">Khoa Điện tử - Viễn thông</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<!-- Lớp Dropdown -->
<div class="lg:col-span-3">
<div class="relative">
<select class="w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none cursor-pointer">
<option value="">-- Tất cả Lớp --</option>
<option value="21DTH01">21DTH01 (K2021)</option>
<option value="21DTH02">21DTH02 (K2021)</option>
<option value="22DTH01">22DTH01 (K2022)</option>
<option value="22DTH02">22DTH02 (K2022)</option>
<option value="23DTH01">23DTH01 (K2023)</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<!-- Trạng thái Dropdown -->
<div class="lg:col-span-2">
<div class="relative">
<select class="w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none cursor-pointer">
<option value="all">Trạng thái: Tất cả</option>
<option selected="" value="active">Đang học</option>
<option value="reserve">Bảo lưu</option>
<option value="graduated">Tốt nghiệp</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">filter_list</span>
</div>
</div>
</div>
</div>
<!-- Student Master Data Table -->
<div class="bg-surface-container-lowest rounded-xl shadow-[0_1px_4px_rgba(0,0,0,0.04)] overflow-hidden flex flex-col">
<!-- Responsive Table Scroll Area -->
<div class="overflow-x-auto w-full">
<table class="w-full text-left border-collapse min-w-[900px]">
<thead>
<tr class="h-10 bg-surface-container-low text-outline font-label-sm text-label-sm uppercase tracking-wider">
<th class="px-space-md w-14 text-center">STT</th>
<th class="px-space-md w-36">MSSV</th>
<th class="px-space-md min-w-[220px]">Họ và tên</th>
<th class="px-space-md w-32">Lớp</th>
<th class="px-space-md min-w-[180px]">Khoa</th>
<th class="px-space-md w-28">Khóa học</th>
<th class="px-space-md min-w-[190px]">Vi phạm tác phong</th>
<th class="px-space-md w-28 text-right">Thao tác</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-container-low font-body-md text-body-md text-on-surface">
<!-- Row 1: Compliant (0 infractions) -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">01</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">21110823</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  NA
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Nguyễn An An</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">anan.nguyen@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">21DTH01</td>
<td class="px-space-md text-on-surface-variant truncate">Công nghệ thông tin</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2021</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-secondary-container/40 text-on-secondary-container">
<span class="material-symbols-outlined text-[13px]">verified</span>
<span class="">0 lần (Tuân thủ tốt)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" onclick="openDrawerWithData('21110823', 'Nguyễn An An', '21DTH01', 'CNTT', 'K2021', 'anan.nguyen@student.edu.vn', '0901234567')" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
<!-- Row 2: Warning (1-2 infractions) -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">02</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">21110845</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-secondary-fixed text-on-secondary-fixed font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  TB
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Trần Bảo Bình</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">baobinh.tran@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">21DTH01</td>
<td class="px-space-md text-on-surface-variant truncate">Công nghệ thông tin</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2021</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-tertiary-fixed text-on-tertiary-fixed font-medium">
<span class="material-symbols-outlined text-[13px]">warning</span>
<span class="">1 lần (Quên thẻ SV)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
<!-- Row 3: Severe (>=3 infractions) -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">03</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">22119012</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-error-container text-on-error-container font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  LD
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Lê Doãn Dũng</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">doandung.le@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">22DTH02</td>
<td class="px-space-md text-on-surface-variant truncate">Công nghệ thông tin</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2022</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-error text-on-error font-medium">
<span class="material-symbols-outlined text-[13px]">gpp_bad</span>
<span class="">4 lần (Cảnh cáo học vụ)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
<!-- Row 4: Compliant -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">04</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">22119034</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  PH
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Phạm Hoàng Hải</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">hoanghai.pham@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">22DTH02</td>
<td class="px-space-md text-on-surface-variant truncate">Công nghệ thông tin</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2022</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-secondary-container/40 text-on-secondary-container">
<span class="material-symbols-outlined text-[13px]">verified</span>
<span class="">0 lần (Tuân thủ tốt)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
<!-- Row 5: 2 infractions -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">05</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">21124089</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  VK
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Vũ Tuấn Kiệt</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">tuankiet.vu@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">21DKT01</td>
<td class="px-space-md text-on-surface-variant truncate">Kinh tế &amp; Quản trị</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2021</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-tertiary-fixed text-on-tertiary-fixed font-medium">
<span class="material-symbols-outlined text-[13px]">warning</span>
<span class="">2 lần (Không đồng phục)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
<!-- Row 6: 0 infractions -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">06</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">23115002</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-primary-fixed text-on-primary-fixed font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  ĐL
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Đinh Khánh Linh</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">khanhlinh.dinh@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">23DCK03</td>
<td class="px-space-md text-on-surface-variant truncate">Cơ khí - Động lực</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2023</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-secondary-container/40 text-on-secondary-container">
<span class="material-symbols-outlined text-[13px]">verified</span>
<span class="">0 lần (Tuân thủ tốt)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
<!-- Row 7: 3 infractions -->
<tr class="h-12 hover:bg-surface-container-low/70 transition-colors group">
<td class="px-space-md text-center font-data-mono text-outline">07</td>
<td class="px-space-md font-data-mono font-semibold text-primary">
<a class="hover:underline" href="javascript:void(0)">22132019</a>
</td>
<td class="px-space-md">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-error-container text-on-error-container font-headline-sm text-headline-sm flex items-center justify-center font-semibold shrink-0">
                  HM
                </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-label-lg text-on-surface group-hover:text-primary transition-colors">Hoàng Văn Minh</span>
<span class="font-body-sm text-body-sm text-on-surface-variant truncate">vanminh.hoang@student.edu.vn</span>
</div>
</div>
</td>
<td class="px-space-md font-label-md text-label-md">22DNN01</td>
<td class="px-space-md text-on-surface-variant truncate">Ngoại ngữ</td>
<td class="px-space-md font-data-mono text-on-surface-variant">K2022</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full font-label-sm text-[11px] bg-error text-on-error font-medium">
<span class="material-symbols-outlined text-[13px]">gpp_bad</span>
<span class="">3 lần (Tái phạm)</span>
</span>
</td>
<td class="px-space-md text-right">
<div class="flex items-center justify-end gap-1 text-on-surface-variant">
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Xem chi tiết">
<span class="material-symbols-outlined text-[18px]">visibility</span>
</button>
<button class="p-1 rounded-lg hover:bg-surface-container hover:text-primary transition-colors" title="Chỉnh sửa">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="p-1 rounded-lg hover:bg-error-container hover:text-error transition-colors" title="Xóa hồ sơ">
<span class="material-symbols-outlined text-[18px]">delete</span>
</button>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<!-- Standard Pagination Bar -->
<div class="p-space-md flex flex-col sm:flex-row items-center justify-between gap-space-sm bg-surface-container-low/40">
<span class="font-body-sm text-body-sm text-on-surface-variant">
        Hiển thị <span class="font-semibold text-on-surface">1 - 10</span> trong tổng số <span class="font-semibold text-on-surface">12,450</span> sinh viên
      </span>
<div class="flex items-center gap-1">
<button class="w-8 h-8 rounded-lg flex items-center justify-center text-outline hover:bg-surface-container hover:text-on-surface disabled:opacity-40 transition-colors" disabled="">
<span class="material-symbols-outlined text-[18px]">chevron_left</span>
</button>
<button class="w-8 h-8 rounded-lg bg-primary-container text-on-primary font-label-sm text-label-sm font-semibold shadow-xs">1</button>
<button class="w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container font-label-sm text-label-sm transition-colors">2</button>
<button class="w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container font-label-sm text-label-sm transition-colors">3</button>
<span class="px-1 text-outline font-body-sm text-body-sm">...</span>
<button class="w-8 h-8 rounded-lg text-on-surface hover:bg-surface-container font-label-sm text-label-sm transition-colors">1245</button>
<button class="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container transition-colors">
<span class="material-symbols-outlined text-[18px]">chevron_right</span>
</button>
</div>
</div>
</div>
<!-- Slide-Over Drawer: Add / Edit Student (Hidden by default, slides in) -->
<div class="fixed inset-0 z-50 pointer-events-none transition-all duration-300" id="student-drawer">
<!-- Backdrop overlay -->
<div class="absolute inset-0 bg-inverse-surface/40 opacity-0 transition-opacity duration-300 cursor-pointer pointer-events-none" id="drawer-backdrop"></div>
<!-- Drawer container -->
<div class="absolute top-0 right-0 h-full w-full max-w-[460px] bg-surface-container-lowest shadow-[0_20px_25px_-5px_rgba(15,23,42,0.15)] transform translate-x-full transition-transform duration-300 flex flex-col pointer-events-auto overflow-hidden" id="drawer-panel">
<!-- Drawer Header -->
<div class="p-space-lg bg-surface-container-low flex items-center justify-between">
<div class="flex items-center gap-space-sm">
<div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shadow-sm">
<span class="material-symbols-outlined text-[20px]">person_add</span>
</div>
<div class="flex flex-col">
<h2 class="font-headline-md text-headline-md text-on-surface" id="drawer-title">Thêm mới sinh viên</h2>
<span class="font-label-sm text-label-sm text-on-surface-variant">Thông tin hồ sơ và định danh AI</span>
</div>
</div>
<button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-drawer-btn">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<!-- Drawer Form Body -->
<form class="flex-1 overflow-y-auto p-space-lg flex flex-col gap-space-md" id="student-form">
<!-- Photo placeholder / capture -->
<div class="flex items-center gap-space-md p-space-md rounded-xl bg-surface-container-low">
<div class="w-20 h-24 rounded-lg bg-surface-container flex flex-col items-center justify-center text-outline overflow-hidden relative group shrink-0">
<img class="w-full h-full object-cover hidden" data-alt="Official Vietnamese university student ID portrait photo against solid light blue studio background, clean neat grooming with white collared shirt." id="preview-avatar" src="https://lh3.googleusercontent.com/aida-public/AB6AXuDh18I2Cvq_-w4x44ACG-vXKQNo_t72KqMbaSIx0Pb6kTEV4C7Pa3lfVadYk0qNCxm0ftlEVMeoadIm2IDLJ_xfWIEeiFQC8CswQsAG2KKxvOyGcfITrh_-USI7tkgsiaSkdm6GzImVIP6xExbgNRC2zFuuf_k8Nx5dNVmc1xmgTFGFt7xtgwCBiwspiflWyUc9q0ql-ntdWX2qaBUQxw3w1XrlBh9BVwtpNrrSzfw">
<div class="flex flex-col items-center" id="placeholder-avatar-icon">
<span class="material-symbols-outlined text-[28px]">badge</span>
<span class="font-label-sm text-[10px] mt-1 text-center">Ảnh 3x4</span>
</div>
</div>
<div class="flex flex-col gap-1">
<span class="font-label-md text-label-md text-on-surface font-semibold">Ảnh thẻ sinh viên</span>
<p class="font-body-sm text-[11px] text-on-surface-variant">Dùng huấn luyện mô hình nhận diện AI camera giảng đường.</p>
<label class="mt-1 cursor-pointer inline-flex items-center gap-1 text-primary hover:underline font-label-sm text-label-sm">
<span class="material-symbols-outlined text-[15px]">photo_camera</span>
<span class="">Tải ảnh lên (.jpg, .png)</span>
<input accept="image/*" class="hidden" type="file">
</label>
</div>
</div>
<!-- Row: MSSV & Full Name -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Mã số sinh viên (MSSV) <span class="text-error">*</span></label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-data-mono text-body-md focus:bg-surface-container-lowest focus:outline-none transition-all" id="input-mssv" placeholder="VD: 21110823" required="" type="text">
</div>
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Họ và tên <span class="text-error">*</span></label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none transition-all" id="input-name" placeholder="VD: Nguyễn Văn A" required="" type="text">
</div>
</div>
<!-- Row: DoB & Gender -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Ngày sinh</label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" type="date" value="2003-05-18">
</div>
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Giới tính</label>
<div class="relative">
<select class="w-full h-9 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none">
<option value="male">Nam</option>
<option value="female">Nữ</option>
<option value="other">Khác</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[16px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
</div>
<!-- Row: Faculty & Class -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm">
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Khoa trực thuộc <span class="text-error">*</span></label>
<div class="relative">
<select class="w-full h-9 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none" id="input-khoa" required="">
<option value="CNTT">Công nghệ thông tin</option>
<option value="Kinh tế">Kinh tế &amp; Quản trị</option>
<option value="Cơ khí">Cơ khí - Động lực</option>
<option value="Ngoại ngữ">Ngoại ngữ</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[16px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Lớp sinh hoạt <span class="text-error">*</span></label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-lop" placeholder="VD: 21DTH01" required="" type="text">
</div>
</div>
<!-- Row: Academic Batch (Niên khóa) -->
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Niên khóa đào tạo</label>
<div class="relative">
<select class="w-full h-9 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none" id="input-khoa-hoc">
<option value="K2020">K2020 (Khóa 2020 - 2024)</option>
<option selected="" value="K2021">K2021 (Khóa 2021 - 2025)</option>
<option value="K2022">K2022 (Khóa 2022 - 2026)</option>
<option value="K2023">K2023 (Khóa 2023 - 2027)</option>
<option value="K2024">K2024 (Khóa 2024 - 2028)</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[16px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<!-- Contact Info: School Email & Phone -->
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Email trường (@student.edu.vn)</label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-email" placeholder="VD: mssv@student.edu.vn" type="email">
</div>
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Số điện thoại liên lạc</label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-phone" placeholder="VD: 0901234567" type="tel">
</div>
</form>
<!-- Drawer Footer Actions -->
<div class="p-space-md bg-surface-container-low flex items-center justify-end gap-space-sm">
<button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="close-drawer-footer-btn" type="button">
          Hủy bỏ
        </button>
<button class="px-5 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5" form="student-form" type="submit">
<span class="material-symbols-outlined text-[18px]">save</span>
<span class="">Lưu thông tin</span>
</button>
</div>
</div>
</div>
<!-- Modal: 3-Step Excel Batch Import Workflow -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md overflow-y-auto" id="import-modal">
<!-- Backdrop overlay -->
<div class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-xs transition-opacity" id="import-backdrop"></div>
<!-- Modal Content Card -->
<div class="relative w-full max-w-4xl bg-surface-container-lowest rounded-xl shadow-[0_20px_25px_-5px_rgba(15,23,42,0.1)] flex flex-col z-10 max-h-[92vh] overflow-hidden my-auto">
<!-- Modal Header -->
<div class="p-space-lg bg-surface-container-low flex items-center justify-between">
<div class="flex items-center gap-space-sm">
<div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shadow-sm">
<span class="material-symbols-outlined text-[20px]">file_upload</span>
</div>
<div>
<h2 class="font-headline-lg text-headline-lg text-on-surface">Nhập dữ liệu sinh viên từ Excel</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">Quy trình 3 bước chuẩn hóa hồ sơ học vụ vào hệ thống giám sát tác phong</p>
</div>
</div>
<button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-import-modal-btn">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<!-- Stepper Indicator -->
<div class="px-space-lg py-3 bg-surface-container-low/50 flex items-center justify-between">
<!-- Step 1 -->
<div class="flex items-center gap-2">
<div class="w-6 h-6 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-sm text-[11px] font-bold">1</div>
<span class="font-label-sm text-label-sm text-primary font-semibold">Chọn loại dữ liệu</span>
</div>
<div class="h-0.5 flex-1 mx-4 bg-primary/20"></div>
<!-- Step 2 -->
<div class="flex items-center gap-2">
<div class="w-6 h-6 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-sm text-[11px] font-bold">2</div>
<span class="font-label-sm text-label-sm text-primary font-semibold">Tải lên tệp tin</span>
</div>
<div class="h-0.5 flex-1 mx-4 bg-primary/20"></div>
<!-- Step 3 -->
<div class="flex items-center gap-2">
<div class="w-6 h-6 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-sm text-[11px] font-bold">3</div>
<span class="font-label-sm text-label-sm text-primary font-semibold">Xem trước &amp; Xác thực</span>
</div>
</div>
<!-- Modal Body (Steps Content) -->
<div class="p-space-lg overflow-y-auto flex flex-col gap-space-lg max-h-[calc(90vh-190px)]">
<!-- STEP 1: Select Type & Hierarchy Notice -->
<div class="flex flex-col gap-space-xs">
<div class="flex items-center gap-2">
<span class="font-label-lg text-label-lg text-on-surface font-semibold">Bước 1: Chọn loại danh mục import</span>
<span class="text-error font-body-sm text-body-sm">*</span>
</div>
<p class="font-body-sm text-body-sm text-on-surface-variant">Lưu ý tuân thủ đúng trình tự hệ thống: Thông tin khoa -&gt; Thông tin khóa -&gt; Thông tin sinh viên để đảm bảo toàn vẹn dữ liệu.</p>
<div class="grid grid-cols-1 md:grid-cols-3 gap-space-sm mt-1">
<label class="p-space-md rounded-xl bg-surface-container-low/60 hover:bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="khoa">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Thông tin Khoa</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 01</span>
</div>
</label>
<label class="p-space-md rounded-xl bg-surface-container-low/60 hover:bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="khoa-hoc">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Thông tin Khóa / Lớp</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 02</span>
</div>
</label>
<label class="p-space-md rounded-xl bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input checked="" class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="sinh-vien">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-primary font-bold">Thông tin Sinh viên</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Danh sách hồ sơ chính quy</span>
</div>
</label>
</div>
</div>
<!-- STEP 2: Drag & Drop Zone + Download Sample -->
<div class="flex flex-col gap-space-xs">
<div class="flex items-center justify-between">
<span class="font-label-lg text-label-lg text-on-surface font-semibold">Bước 2: Tải lên tệp Excel dữ liệu (.xlsx, .xls)</span>
<button class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-label-sm text-label-sm transition-colors">
<span class="material-symbols-outlined text-[16px]">download</span>
<span class="">Tải file Excel mẫu (.xlsx)</span>
</button>
</div>
<!-- Drag and drop zone -->
<div class="mt-1 p-6 rounded-xl bg-surface-container-low/50 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-surface-container-low transition-all">
<div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-primary mb-2">
<span class="material-symbols-outlined text-[24px]">cloud_upload</span>
</div>
<p class="font-label-md text-label-md text-on-surface font-medium">Kéo thả tệp tin vào đây hoặc nhấn để duyệt từ máy tính</p>
<p class="font-body-sm text-[11px] text-on-surface-variant mt-1">Dung lượng tối đa: 15MB. Đã chọn tệp: <span class="font-data-mono font-semibold text-primary">DS_SinhVien_KhoaCNTT_HK2_2024.xlsx</span></p>
</div>
</div>
<!-- STEP 3: Preview Table with Validation Badges & Errors -->
<div class="flex flex-col gap-space-xs">
<div class="flex items-center justify-between">
<div class="flex items-center gap-2">
<span class="font-label-lg text-label-lg text-on-surface font-semibold">Bước 3: Xem trước &amp; Đối soát dữ liệu (Preview)</span>
<span class="px-2 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-data-mono text-[11px] font-semibold">45 hợp lệ / 3 lỗi</span>
</div>
<span class="font-body-sm text-[11px] text-error">Yêu cầu sửa lỗi hoặc bỏ qua hàng lỗi trước khi nhập</span>
</div>
<!-- Preview Table -->
<div class="overflow-x-auto rounded-xl bg-surface-container-low/30">
<table class="w-full text-left text-[12px] border-collapse min-w-[700px]">
<thead>
<tr class="h-9 bg-surface-container-low text-outline font-label-sm text-[11px] uppercase tracking-wider">
<th class="px-3 w-16 text-center">Dòng</th>
<th class="px-3 w-28">MSSV</th>
<th class="px-3 min-w-[150px]">Họ và tên</th>
<th class="px-3 w-24">Lớp</th>
<th class="px-3 min-w-[140px]">Khoa</th>
<th class="px-3 min-w-[200px]">Trạng thái kiểm tra</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-container-low font-body-sm text-body-sm">
<!-- Row Valid 1 -->
<tr class="h-10 bg-secondary-container/10">
<td class="px-3 text-center font-data-mono text-outline">02</td>
<td class="px-3 font-data-mono font-medium text-on-surface">21110825</td>
<td class="px-3 text-on-surface">Đoàn Minh Anh</td>
<td class="px-3 text-on-surface">21DTH01</td>
<td class="px-3 text-on-surface-variant">CNTT</td>
<td class="px-3">
<span class="inline-flex items-center gap-1 text-secondary font-label-sm text-[11px] font-medium">
<span class="material-symbols-outlined text-[14px]">check_circle</span>
<span class="">Hợp lệ</span>
</span>
</td>
</tr>
<!-- Row Valid 2 -->
<tr class="h-10 bg-secondary-container/10">
<td class="px-3 text-center font-data-mono text-outline">03</td>
<td class="px-3 font-data-mono font-medium text-on-surface">21110826</td>
<td class="px-3 text-on-surface">Bùi Quốc Bảo</td>
<td class="px-3 text-on-surface">21DTH01</td>
<td class="px-3 text-on-surface-variant">CNTT</td>
<td class="px-3">
<span class="inline-flex items-center gap-1 text-secondary font-label-sm text-[11px] font-medium">
<span class="material-symbols-outlined text-[14px]">check_circle</span>
<span class="">Hợp lệ</span>
</span>
</td>
</tr>
<!-- Row Error 1 -->
<tr class="h-10 bg-error-container/30">
<td class="px-3 text-center font-data-mono text-error font-bold">04</td>
<td class="px-3 font-data-mono font-medium text-error line-through">21110823</td>
<td class="px-3 text-on-surface">Nguyễn An An</td>
<td class="px-3 text-on-surface">21DTH01</td>
<td class="px-3 text-on-surface-variant">CNTT</td>
<td class="px-3">
<span class="inline-flex items-center gap-1 text-error font-label-sm text-[11px] font-semibold">
<span class="material-symbols-outlined text-[14px]">error</span>
<span class="">Lỗi dòng 4: MSSV đã tồn tại trên hệ thống</span>
</span>
</td>
</tr>
<!-- Row Valid 3 -->
<tr class="h-10 bg-secondary-container/10">
<td class="px-3 text-center font-data-mono text-outline">05</td>
<td class="px-3 font-data-mono font-medium text-on-surface">21110827</td>
<td class="px-3 text-on-surface">Hà Thu Cúc</td>
<td class="px-3 text-on-surface">21DTH01</td>
<td class="px-3 text-on-surface-variant">CNTT</td>
<td class="px-3">
<span class="inline-flex items-center gap-1 text-secondary font-label-sm text-[11px] font-medium">
<span class="material-symbols-outlined text-[14px]">check_circle</span>
<span class="">Hợp lệ</span>
</span>
</td>
</tr>
<!-- Row Error 2 -->
<tr class="h-10 bg-error-container/30">
<td class="px-3 text-center font-data-mono text-error font-bold">08</td>
<td class="px-3 font-data-mono font-medium text-on-surface">21110830</td>
<td class="px-3 text-on-surface">Trần Văn Cường</td>
<td class="px-3 text-error font-semibold">99XYZ01</td>
<td class="px-3 text-on-surface-variant">CNTT</td>
<td class="px-3">
<span class="inline-flex items-center gap-1 text-error font-label-sm text-[11px] font-semibold">
<span class="material-symbols-outlined text-[14px]">error</span>
<span class="">Lỗi dòng 8: Mã Lớp không thuộc danh mục Khoa CNTT</span>
</span>
</td>
</tr>
</tbody>
</table>
</div>
</div>
</div>
<!-- Modal Footer -->
<div class="p-space-lg bg-surface-container-low flex items-center justify-between">
<label class="flex items-center gap-2 cursor-pointer">
<input checked="" class="w-4 h-4 rounded text-primary focus:ring-0" type="checkbox">
<span class="font-body-sm text-body-sm text-on-surface">Tự động bỏ qua các hàng bị lỗi và tiếp tục nhập</span>
</label>
<div class="flex items-center gap-space-sm">
<button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-import-btn">
            Hủy thao tác
          </button>
<button class="px-5 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5">
<span class="material-symbols-outlined text-[18px]">cloud_done</span>
<span class="">Nhập dữ liệu (45/48 hợp lệ)</span>
</button>
</div>
</div>
</div>
</div>
</div>
<!-- Inline Interactive Script -->
<script>
  // Drawer Interactions
  const drawer = document.getElementById('student-drawer');
  const drawerPanel = document.getElementById('drawer-panel');
  const drawerBackdrop = document.getElementById('drawer-backdrop');
  const openDrawerBtn = document.getElementById('open-drawer-btn');
  const closeDrawerBtn = document.getElementById('close-drawer-btn');
  const closeDrawerFooterBtn = document.getElementById('close-drawer-footer-btn');
  const drawerTitle = document.getElementById('drawer-title');

  function openDrawer(isEdit = false) {
    drawer.classList.remove('pointer-events-none');
    drawerBackdrop.classList.remove('pointer-events-none');
    drawerBackdrop.classList.remove('opacity-0');
    drawerBackdrop.classList.add('opacity-100');
    drawerPanel.classList.remove('translate-x-full');
    drawerPanel.classList.add('translate-x-0');
    if (!isEdit) {
      drawerTitle.textContent = 'Thêm mới sinh viên';
      document.getElementById('student-form').reset();
    }
  }

  function closeDrawer() {
    drawerPanel.classList.remove('translate-x-0');
    drawerPanel.classList.add('translate-x-full');
    drawerBackdrop.classList.remove('opacity-100');
    drawerBackdrop.classList.add('opacity-0');
    setTimeout(() => {
      drawer.classList.add('pointer-events-none');
      drawerBackdrop.classList.add('pointer-events-none');
    }, 300);
  }

  openDrawerBtn.addEventListener('click', () => openDrawer(false));
  closeDrawerBtn.addEventListener('click', closeDrawer);
  closeDrawerFooterBtn.addEventListener('click', closeDrawer);
  drawerBackdrop.addEventListener('click', closeDrawer);

  window.openDrawerWithData = function(mssv, name, lop, khoa, khoahoc, email, phone) {
    openDrawer(true);
    drawerTitle.textContent = 'Chỉnh sửa sinh viên: ' + mssv;
    document.getElementById('input-mssv').value = mssv;
    document.getElementById('input-name').value = name;
    document.getElementById('input-lop').value = lop;
    document.getElementById('input-khoa').value = khoa;
    document.getElementById('input-khoa-hoc').value = khoahoc;
    document.getElementById('input-email').value = email;
    document.getElementById('input-phone').value = phone;
  };

  // Import Modal Interactions
  const importModal = document.getElementById('import-modal');
  const openImportModalBtn = document.getElementById('open-import-modal-btn');
  const closeImportModalBtn = document.getElementById('close-import-modal-btn');
  const cancelImportBtn = document.getElementById('cancel-import-btn');
  const importBackdrop = document.getElementById('import-backdrop');

  function openImportModal() {
    importModal.classList.remove('hidden');
    importModal.classList.add('flex');
  }

  function closeImportModal() {
    importModal.classList.remove('flex');
    importModal.classList.add('hidden');
  }

  openImportModalBtn.addEventListener('click', openImportModal);
  closeImportModalBtn.addEventListener('click', closeImportModal);
  cancelImportBtn.addEventListener('click', closeImportModal);
  importBackdrop.addEventListener('click', closeImportModal);

  // Mini Chart Toggle (Khoa vs Lop)
  const toggleKhoaBtn = document.getElementById('toggle-khoa-btn');
  const toggleLopBtn = document.getElementById('toggle-lop-btn');
  const chartKhoa = document.getElementById('chart-khoa');
  const chartLop = document.getElementById('chart-lop');
  const chartTitle = document.getElementById('chart-title');

  toggleKhoaBtn.addEventListener('click', () => {
    toggleKhoaBtn.classList.add('bg-surface-container-lowest', 'text-primary', 'font-semibold', 'shadow-xs');
    toggleKhoaBtn.classList.remove('text-on-surface-variant');
    toggleLopBtn.classList.remove('bg-surface-container-lowest', 'text-primary', 'font-semibold', 'shadow-xs');
    toggleLopBtn.classList.add('text-on-surface-variant');
    chartKhoa.classList.remove('hidden');
    chartKhoa.classList.add('flex');
    chartLop.classList.remove('flex');
    chartLop.classList.add('hidden');
    chartTitle.textContent = 'Phân bổ sinh viên theo khoa';
  });

  toggleLopBtn.addEventListener('click', () => {
    toggleLopBtn.classList.add('bg-surface-container-lowest', 'text-primary', 'font-semibold', 'shadow-xs');
    toggleLopBtn.classList.remove('text-on-surface-variant');
    toggleKhoaBtn.classList.remove('bg-surface-container-lowest', 'text-primary', 'font-semibold', 'shadow-xs');
    toggleKhoaBtn.classList.add('text-on-surface-variant');
    chartLop.classList.remove('hidden');
    chartLop.classList.add('flex');
    chartKhoa.classList.remove('flex');
    chartKhoa.classList.add('hidden');
    chartTitle.textContent = 'Phân bổ sinh viên theo lớp';
  });
</script>
<?php include 'includes/footer.php'; ?>
