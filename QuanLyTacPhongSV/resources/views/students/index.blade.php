@extends('layouts.app')

@section('content')
<div class="flex flex-col w-full pb-space-xl" id="student-module">
<div class="hidden mb-space-md rounded-lg px-space-md py-3 font-label-md" id="student-status" role="status"></div>
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
<button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all shadow-sm" id="export-students-btn">
<span class="material-symbols-outlined text-[18px]">file_download</span>
<span class="">Xuất danh sách</span>
</button>
<button class="flex items-center gap-space-xs px-3.5 py-2 rounded-xl bg-error-container hover:bg-error hover:text-white text-on-error-container font-label-md text-label-md transition-all shadow-sm" id="open-reset-violations-btn" type="button" title="Đặt lại số lượt vi phạm của toàn bộ sinh viên về 0">
<span class="material-symbols-outlined text-[18px]">restart_alt</span>
<span class="">Reset vi phạm</span>
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
<span class="font-headline-xl text-[28px] text-on-surface tracking-tight font-bold" id="student-count-stat">—</span>
<span class="font-label-sm text-label-sm text-secondary font-medium">Sinh viên trong CSDL</span>
</div>
<div class="mt-space-xs flex items-center gap-1.5 text-on-surface-variant font-body-sm text-body-sm">
<span class="text-secondary font-medium flex items-center text-[11px]"><span class="material-symbols-outlined text-[14px]">database</span> SQL</span>
<span class="">dữ liệu hiện tại</span>
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
<span class="font-headline-xl text-[28px] text-on-surface tracking-tight font-bold" id="faculty-count-stat">—</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Khoa đào tạo</span>
</div>
<div class="mt-space-xs text-on-surface-variant font-body-sm text-body-sm truncate">
        Tổng số khoa trong CSDL
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
<span class="font-headline-xl text-[28px] text-on-surface tracking-tight font-bold" id="class-count-stat">—</span>
<span class="font-label-sm text-label-sm text-on-surface-variant">Lớp sinh hoạt</span>
</div>
<div class="mt-space-xs text-on-surface-variant font-body-sm text-body-sm truncate">
        Tổng số lớp trong CSDL
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
<input class="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none transition-all shadow-inner" id="student-search" placeholder="Tìm kiếm theo MSSV hoặc Họ tên sinh viên..." type="search">
</div>
<!-- Khoa Dropdown -->
<div class="lg:col-span-3">
<div class="relative">
<select class="w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none cursor-pointer" id="filter-khoa">
<option value="">-- Tất cả Khoa --</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<!-- Lớp Dropdown -->
<div class="lg:col-span-3">
<div class="relative">
<select class="w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none cursor-pointer" id="filter-nganh">
<option value="">-- Tất cả Ngành --</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[18px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<!-- Trạng thái Dropdown -->
<div class="lg:col-span-2">
<div class="relative">
<select class="w-full h-10 pl-3 pr-8 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md appearance-none focus:outline-none cursor-pointer" id="filter-lop">
<option value="">-- Tất cả Lớp --</option>
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
<tbody class="divide-y divide-surface-container-low font-body-md text-body-md text-on-surface" id="student-table-body"></tbody>
</table>
</div>
<!-- Standard Pagination Bar -->
<div class="p-space-md flex flex-col sm:flex-row items-center justify-between gap-space-sm bg-surface-container-low/40">
<span class="font-body-sm text-body-sm text-on-surface-variant">
        Hiển thị <span class="font-semibold text-on-surface" id="student-range">0 - 0</span> trong tổng số <span class="font-semibold text-on-surface" id="student-total">0</span> sinh viên
      </span>
<div class="flex items-center gap-2">
<label class="font-body-sm text-body-sm text-on-surface-variant" for="student-page-size">Số dòng</label>
<select class="h-8 rounded-lg bg-surface-container-low px-2 font-label-sm" id="student-page-size">
<option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option>
</select>
<div class="flex items-center gap-1" id="student-page-buttons">
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
<span class="font-label-sm text-label-sm text-on-surface-variant">Thông tin hồ sơ sinh viên (AI không nhận diện danh tính)</span>
</div>
</div>
<button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-drawer-btn">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<!-- Drawer Form Body -->
<form class="flex-1 overflow-y-auto p-space-lg flex flex-col gap-space-md" id="student-form">
<!-- Photo placeholder / capture -->
<div class="flex items-center gap-space-md p-space-md rounded-xl bg-surface-container-low hidden">
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
<div class="grid grid-cols-1 sm:grid-cols-2 gap-space-sm hidden">
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
<option value="">Chọn khoa</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-[16px] text-outline pointer-events-none">expand_more</span>
</div>
</div>
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Ngành trực thuộc <span class="text-error">*</span></label>
<select class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-nganh" required="">
<option value="">Chọn ngành</option>
</select>
</div>
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Lớp sinh hoạt <span class="text-error">*</span></label>
<select class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-lop" required="">
<option value="">Chọn lớp</option>
</select>
</div>
</div>
<!-- Row: Academic Batch (Niên khóa) -->
<div class="flex flex-col gap-1">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Niên khóa đào tạo</label>
<div class="relative">
<input class="w-full h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-khoa-hoc" type="number" min="1" max="9999" placeholder="Ví dụ: 14 hoặc 2021" required>
</div>
</div>
<!-- Contact Info: School Email & Phone -->
<div class="flex flex-col gap-1 hidden">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Email trường (@student.edu.vn)</label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-email" placeholder="VD: mssv@student.edu.vn" type="email">
</div>
<div class="flex flex-col gap-1 hidden">
<label class="font-label-sm text-label-sm text-on-surface font-medium">Số điện thoại liên lạc</label>
<input class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:outline-none" id="input-phone" placeholder="VD: 0901234567" type="tel">
</div>
<section class="hidden flex-col gap-space-sm border-t border-surface-container pt-space-md" id="manual-violation-section" aria-labelledby="manual-violation-title">
<div>
<h3 class="font-headline-sm text-headline-sm text-on-surface" id="manual-violation-title">Biên bản vi phạm (Đội Cờ Đỏ ghi nhận)</h3>
<p class="font-body-sm text-body-sm text-on-surface-variant">Ghi nhận thông tin cụ thể (thời điểm, địa điểm/phòng học) do Đội Cờ Đỏ kiểm tra trực tiếp.</p>
</div>
<div class="grid grid-cols-2 gap-space-sm" id="manual-violation-inputs">
<label class="col-span-2 flex flex-col gap-1"><span class="font-label-sm text-label-sm">Loại vi phạm <span class="text-error">*</span></span>
<select class="h-9 rounded-lg bg-surface-container-low px-3" form="manual-violation-form" id="mv-type" required></select></label>
<label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Ngày vi phạm <span class="text-error">*</span></span>
<input class="h-9 rounded-lg bg-surface-container-low px-3" form="manual-violation-form" id="mv-date" type="date" required></label>
<label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Giờ vi phạm <span class="text-error">*</span></span>
<input class="h-9 rounded-lg bg-surface-container-low px-3" form="manual-violation-form" id="mv-time" type="time" value="07:30" required></label>
<label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Khu vực vi phạm</span>
<select class="h-9 rounded-lg bg-surface-container-low px-3" form="manual-violation-form" id="mv-area"><option value="">-- Chọn khu vực --</option></select></label>
<label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Phòng học / Vị trí</span>
<input class="h-9 rounded-lg bg-surface-container-low px-3" form="manual-violation-form" id="mv-room" type="text" placeholder="VD: Phòng B204, Giảng đường A"></label>
<label class="col-span-2 flex flex-col gap-1"><span class="font-label-sm text-label-sm">Ghi chú biên bản (tùy chọn)</span>
<textarea class="min-h-[60px] rounded-lg bg-surface-container-low p-2" form="manual-violation-form" id="mv-note" maxlength="500" placeholder="VD: Lập biên bản kiểm tra đột xuất do cảnh báo từ Camera"></textarea></label>
<div class="col-span-2 flex items-end"><button class="h-9 w-full rounded-lg bg-error px-3 font-label-md text-on-error" form="manual-violation-form" type="submit" id="mv-submit">Lập biên bản vi phạm</button></div>
</div>
<p class="font-body-sm text-body-sm" id="mv-status" role="status" aria-live="polite"></p>
<ul class="flex flex-col divide-y divide-surface-container rounded-lg bg-surface-container-low" id="mv-list"></ul>
</section>
</form>
<form class="hidden" id="manual-violation-form" novalidate></form>
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
<p class="font-body-sm text-body-sm text-on-surface-variant">Import theo thứ tự: KHOA → NGANH → LOP → SINHVIEN. Mã quan hệ phải trùng mã trong danh mục đã nhập trước.</p>
<div class="grid grid-cols-1 md:grid-cols-5 gap-space-sm mt-1">
<label class="p-space-md rounded-xl bg-surface-container-low/60 hover:bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="KHOA">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Thông tin Khoa</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 01</span>
</div>
</label>
<label class="p-space-md rounded-xl bg-surface-container-low/60 hover:bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="NGANH">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Thông tin Ngành</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 02</span>
</div>
</label>
<label class="p-space-md rounded-xl bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="LOP">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Thông tin Lớp</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 03</span>
</div>
</label>
<label class="p-space-md rounded-xl bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input checked="" class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="SINHVIEN">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-primary font-bold">Thông tin Sinh viên</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 04</span>
</div>
</label>
</div>
</div>
<label class="p-space-md rounded-xl bg-surface-container-low cursor-pointer flex items-start gap-space-sm transition-all">
<input class="mt-0.5 text-primary focus:ring-0" name="import-type" type="radio" value="VIPHAM">
<div class="flex flex-col">
<span class="font-label-md text-label-md text-on-surface font-semibold">Biên bản vi phạm</span>
<span class="font-body-sm text-[11px] text-on-surface-variant">Bước thiết lập 05</span>
</div>
</label>
<!-- STEP 2: Drag & Drop Zone + Download Sample -->
<div class="flex flex-col gap-space-xs">
<div class="flex items-center justify-between">
<span class="font-label-lg text-label-lg text-on-surface font-semibold">Bước 2: Tải lên workbook Excel (.xlsx)</span>
<button class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-label-sm text-label-sm transition-colors" id="import-template-btn" type="button">
<span class="material-symbols-outlined text-[16px]">download</span>
<span class="">Tải file Excel mẫu (.xlsx)</span>
</button>
</div>
<!-- Drag and drop zone -->
<div class="mt-1 p-6 rounded-xl bg-surface-container-low/50 flex flex-col items-center justify-center text-center cursor-pointer hover:bg-surface-container-low transition-all" id="import-drop-zone">
<input accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" id="import-file" type="file">
<div class="w-12 h-12 rounded-full bg-surface-container flex items-center justify-center text-primary mb-2">
<span class="material-symbols-outlined text-[24px]">cloud_upload</span>
</div>
<p class="font-label-md text-label-md text-on-surface font-medium">Kéo thả tệp tin vào đây hoặc nhấn để duyệt từ máy tính</p>
<p class="font-body-sm text-[11px] text-on-surface-variant mt-1">Dung lượng tối đa: 15MB. Chỉ .xlsx; .xls không được hỗ trợ. Tệp đã chọn: <span class="font-data-mono font-semibold text-primary" id="import-file-name">chưa chọn</span></p>
</div>
</div>
<!-- STEP 3: Preview Table with Validation Badges & Errors -->
<div class="flex flex-col gap-space-xs hidden" id="import-preview-section">
<div class="flex flex-wrap items-center justify-between gap-2">
<div class="flex flex-wrap items-center gap-2">
<span class="font-label-lg text-label-lg text-on-surface font-semibold">Bước 3: Xem trước &amp; Đối soát dữ liệu (Preview)</span>
<span class="px-2 py-0.5 rounded-full bg-secondary-container/40 text-on-secondary-container font-data-mono text-[11px] font-semibold" id="import-counts">0 dòng</span>
<button class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-surface-container hover:bg-surface-container-high text-primary font-label-sm text-[11px] transition-colors" id="download-normalized-btn" type="button">
<span class="material-symbols-outlined text-[14px]">download</span>
<span class="">Tải bản đã chuẩn hóa (.xlsx)</span>
</button>
</div>
<span class="font-body-sm text-[11px] text-error">Mọi dòng lỗi chặn toàn bộ giao dịch; dòng mã trùng sẽ được bỏ qua</span>
</div>
<!-- Preview Table -->
<div class="overflow-x-auto rounded-xl bg-surface-container-low/30">
<table class="w-full text-left text-[12px] border-collapse min-w-[700px]">
<thead class="h-9 bg-surface-container-low text-outline font-label-sm text-[11px] uppercase tracking-wider" id="import-preview-head"></thead>
<tbody class="divide-y divide-surface-container-low font-body-sm text-body-sm" id="import-preview-body"></tbody>
</table>
</div>
</div>
</div>
<!-- Modal Footer -->
<div class="p-space-lg bg-surface-container-low flex items-center justify-between">
<span class="font-body-sm text-body-sm text-on-surface">All-or-nothing: chỉ lưu khi không có lỗi; mã trùng mặc định bỏ qua.</span>
<div class="flex items-center gap-space-sm">
<button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-import-btn">
            Hủy thao tác
          </button>
<button class="px-5 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5" id="import-commit-btn" disabled="" type="button">
<span class="material-symbols-outlined text-[18px]">cloud_done</span>
<span class="" id="import-commit-label">Chọn workbook để xem trước</span>
</button>
</div>
</div>
</div>
</div>

<!-- Modal: Export Security Password -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md overflow-y-auto" id="export-password-modal">
  <div class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-xs transition-opacity cursor-pointer" id="export-password-backdrop"></div>
  <div class="relative w-full max-w-md bg-surface-container-lowest rounded-xl shadow-[0_20px_25px_-5px_rgba(15,23,42,0.1)] flex flex-col z-10 overflow-hidden my-auto">
    <div class="p-space-lg bg-surface-container-low flex items-center justify-between">
      <div class="flex items-center gap-space-sm">
        <div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shadow-sm">
          <span class="material-symbols-outlined text-[20px]">lock</span>
        </div>
        <div>
          <h2 class="font-headline-sm text-headline-sm text-on-surface">Bảo mật xuất dữ liệu</h2>
          <p class="font-body-sm text-[11px] text-on-surface-variant">Xác thực quyền trích xuất hồ sơ sinh viên</p>
        </div>
      </div>
      <button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-export-modal-btn" type="button">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>
    <form class="p-space-lg flex flex-col gap-space-md" id="export-password-form">
      <div class="flex flex-col gap-1.5">
        <label class="font-label-sm text-label-sm text-on-surface font-medium" for="export-password-input">
          Mật khẩu bảo mật xuất dữ liệu <span class="text-error">*</span>
        </label>
        <div class="relative">
          <input class="w-full h-10 px-3 pr-10 rounded-lg bg-surface-container-low text-on-surface font-body-md text-body-md focus:bg-surface-container-lowest focus:outline-none transition-all" id="export-password-input" type="password" placeholder="Nhập mật khẩu riêng cho tính năng xuất..." required autocomplete="current-password">
          <button class="absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface flex items-center" id="toggle-export-password-btn" type="button">
            <span class="material-symbols-outlined text-[18px]" id="toggle-export-password-icon">visibility</span>
          </button>
        </div>
        <p class="font-body-sm text-[11px] text-error hidden" id="export-password-error"></p>
      </div>
      <div class="p-space-sm rounded-lg bg-surface-container-low/60 flex items-start gap-2 text-on-surface-variant font-body-sm text-[11px]">
        <span class="material-symbols-outlined text-[16px] text-primary shrink-0">info</span>
        <span>Tính năng này yêu cầu mật khẩu bảo mật riêng để ngăn chặn việc trích xuất và rò rỉ dữ liệu danh sách sinh viên ngoài ý muốn.</span>
      </div>
      <div class="flex items-center justify-end gap-space-sm pt-2">
        <button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-export-btn" type="button">
          Hủy bỏ
        </button>
        <button class="px-5 py-2 rounded-xl bg-primary-container text-on-primary hover:bg-primary font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5" type="submit" id="confirm-export-btn">
          <span class="material-symbols-outlined text-[18px]">file_download</span>
          <span>Xác nhận xuất Excel</span>
        </button>
      </div>
    </form>
  </div>
</div>
</div>
<!-- Modal: Reset Toàn bộ Vi phạm Học kỳ -->
<div class="fixed inset-0 z-50 hidden items-center justify-center p-space-md overflow-y-auto" id="student-reset-modal">
  <div class="fixed inset-0 bg-inverse-surface/40 backdrop-blur-xs transition-opacity cursor-pointer" id="student-reset-backdrop"></div>
  <div class="relative w-full max-w-md bg-surface-container-lowest rounded-xl shadow-[0_20px_25px_-5px_rgba(15,23,42,0.15)] flex flex-col z-10 overflow-hidden my-auto">
    <div class="p-space-lg bg-surface-container-low flex items-center justify-between border-b border-surface-container">
      <div class="flex items-center gap-space-sm">
        <div class="w-10 h-10 rounded-xl bg-error-container text-on-error-container flex items-center justify-center shadow-sm">
          <span class="material-symbols-outlined text-[22px]">restart_alt</span>
        </div>
        <div>
          <h2 class="font-headline-sm text-headline-sm text-error">Reset toàn bộ vi phạm</h2>
          <p class="font-body-sm text-[11px] text-on-surface-variant">Xác nhận đưa vi phạm sinh viên về 0</p>
        </div>
      </div>
      <button class="w-8 h-8 rounded-lg text-outline hover:bg-surface-container hover:text-on-surface flex items-center justify-center transition-colors" id="close-student-reset-btn" type="button">
        <span class="material-symbols-outlined text-[20px]">close</span>
      </button>
    </div>
    <form class="p-space-lg flex flex-col gap-space-md" id="student-reset-form">
      <div class="p-space-sm rounded-lg bg-error-container/20 border border-error/20 flex items-start gap-2 text-error font-body-sm text-[12px]">
        <span class="material-symbols-outlined text-[18px] shrink-0 mt-0.5">warning</span>
        <div>
          <p class="font-semibold">Cảnh báo hành động không thể hoàn tác!</p>
          <p class="mt-0.5">Toàn bộ biên bản vi phạm và cảnh báo sẽ bị xóa. Số lượt vi phạm của tất cả sinh viên sẽ trả về <strong>0 lượt vi phạm</strong>.</p>
        </div>
      </div>
      <div class="flex flex-col gap-1.5">
        <label class="font-label-sm text-label-sm text-on-surface font-semibold" for="student-reset-password">
          Mật mã bảo mật xác nhận xóa <span class="text-error">*</span>
        </label>
        <div class="relative">
          <input class="w-full h-10 px-3 pr-10 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:bg-surface-container-lowest focus:outline-none transition-all" id="student-reset-password" type="password" placeholder="Nhập mật mã bảo mật để reset..." required autocomplete="current-password">
          <button class="absolute right-2.5 top-1/2 -translate-y-1/2 text-outline hover:text-on-surface flex items-center" id="toggle-student-reset-pwd" type="button">
            <span class="material-symbols-outlined text-[18px]" id="toggle-student-reset-pwd-icon">visibility</span>
          </button>
        </div>
        <p class="font-body-sm text-[11px] text-error hidden" id="student-reset-error"></p>
      </div>
      <div class="flex items-center justify-end gap-space-sm pt-2">
        <button class="px-4 py-2 rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-label-md text-label-md transition-all" id="cancel-student-reset-btn" type="button">
          Hủy bỏ
        </button>
        <button class="px-5 py-2 rounded-xl bg-error text-on-error hover:bg-error/90 font-label-md text-label-md shadow-sm transition-all flex items-center gap-1.5" id="confirm-student-reset-btn" type="submit">
          <span class="material-symbols-outlined text-[18px]">delete_forever</span>
          <span>Xác nhận Reset về 0</span>
        </button>
      </div>
    </form>
  </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('assets/js/students.js') }}" defer></script>
<script src="{{ asset('assets/js/student_violations.js') }}" defer></script>
@endpush
