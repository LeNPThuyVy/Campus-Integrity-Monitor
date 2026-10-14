<?php include 'includes/header.php'; ?>
<div class="flex flex-col w-full pb-space-xl">
<!-- Top Context Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-space-md py-space-md">
<div class="flex flex-col gap-1">
<div class="flex items-center gap-space-xs text-primary font-label-sm uppercase tracking-wider">
<span class="material-symbols-outlined text-[16px]">security</span>
<span class="">Bảo mật &amp; Kiểm soát truy cập</span>
</div>
<h1 class="font-headline-xl text-headline-xl text-on-surface">Tài khoản &amp; Phân quyền</h1>
<p class="font-body-md text-body-md text-on-surface-variant max-w-3xl">
        Quản lý tài khoản cán bộ quản trị và thiết lập ma trận phân quyền chức năng hệ thống CTSV
      </p>
</div>
<!-- Quick Telemetry Chips -->
<div class="flex items-center gap-space-sm bg-surface-container-low p-1.5 rounded-xl self-start md:self-auto">
<div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-surface-container-lowest shadow-sm">
<span class="w-2 h-2 rounded-full bg-secondary"></span>
<span class="font-label-sm text-on-surface-variant">Đang trực tuyến:</span>
<span class="font-data-mono text-primary font-bold">14</span>
</div>
<div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-surface-container-lowest shadow-sm">
<span class="material-symbols-outlined text-[16px] text-outline">verified_user</span>
<span class="font-label-sm text-on-surface-variant">4 Nhóm vai trò</span>
</div>
</div>
</div>
<!-- Primary Tab Navigator -->
<div class="flex items-center gap-space-xs bg-surface-container-low p-1 rounded-xl w-fit mb-space-lg shadow-sm">
<button class="flex items-center gap-2 px-space-md py-2 rounded-lg font-label-lg transition-all bg-primary-container text-on-primary shadow-sm" id="tab-btn-accounts" onclick="switchMainTab('accounts')">
<span class="material-symbols-outlined text-[18px]">manage_accounts</span>
<span class="">Danh sách tài khoản</span>
<span class="font-data-mono text-[11px] px-2 py-0.5 rounded-full bg-surface-container-lowest/20 text-on-primary">28</span>
</button>
<button class="flex items-center gap-2 px-space-md py-2 rounded-lg font-label-lg transition-all text-on-surface-variant hover:text-on-surface hover:bg-surface-container" id="tab-btn-matrix" onclick="switchMainTab('matrix')">
<span class="material-symbols-outlined text-[18px]">grid_view</span>
<span class="">Ma trận phân quyền</span>
<span class="font-label-sm text-secondary px-1.5 py-0.2 rounded-full bg-secondary-container/40">RBAC</span>
</button>
</div>
<!-- TAB 1: DANH SÁCH TÀI KHOẢN -->
<div class="flex-col gap-space-md flex" id="view-accounts">
<!-- Filter & Action Bar -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-space-md">
<div class="flex flex-wrap items-center gap-space-sm flex-1">
<!-- Search Input -->
<div class="relative min-w-[260px] flex-1 max-w-md">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">search</span>
<input class="w-full h-9 pl-9 pr-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:outline-none focus:bg-surface-container focus:shadow-[0_0_0_2px_rgba(29,78,216,0.2)] transition-all" id="acc-search" placeholder="Tìm kiếm theo tên, username, email..." type="text">
</div>
<!-- Role Filter -->
<div class="relative min-w-[180px]">
<select class="w-full h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none focus:bg-surface-container appearance-none pr-8 cursor-pointer" id="acc-role-filter">
<option value="ALL">Tất cả vai trò</option>
<option value="ADMIN">Quản trị viên (Admin)</option>
<option value="CTSV">Cán bộ CTSV</option>
<option value="FACULTY">Cán bộ Khoa</option>
<option value="INSPECTOR">Thanh tra học đường</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">expand_more</span>
</div>
<!-- Status Filter -->
<div class="relative min-w-[160px]">
<select class="w-full h-9 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none focus:bg-surface-container appearance-none pr-8 cursor-pointer" id="acc-status-filter">
<option value="ALL">Mọi trạng thái</option>
<option value="ACTIVE">Đang hoạt động</option>
<option value="LOCKED">Đã khóa</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">expand_more</span>
</div>
<button class="h-9 px-3 rounded-lg bg-surface-container-low text-on-surface-variant hover:text-on-surface hover:bg-surface-container font-label-md flex items-center gap-1 transition-all" onclick="resetFilters()" title="Làm mới bộ lọc">
<span class="material-symbols-outlined text-[16px]">refresh</span>
<span class="">Đặt lại</span>
</button>
</div>
<!-- Action Buttons -->
<div class="flex items-center gap-space-sm self-end lg:self-auto shrink-0">
<button class="h-9 px-3.5 rounded-lg bg-surface-container-low text-on-surface-variant hover:text-on-surface hover:bg-surface-container font-label-md flex items-center gap-1.5 shadow-sm transition-all" onclick="exportAccountList()">
<span class="material-symbols-outlined text-[18px]">download</span>
<span class="">Xuất danh sách</span>
</button>
<button class="h-9 px-4 rounded-lg bg-primary-container hover:bg-primary text-on-primary font-label-md flex items-center gap-1.5 shadow-sm transition-all" onclick="openAccountDrawer('create')">
<span class="material-symbols-outlined text-[18px]">person_add</span>
<span class="">+ Thêm tài khoản mới</span>
</button>
</div>
</div>
<!-- Data Table Container -->
<div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col">
<div class="overflow-x-auto">
<table class="w-full text-left">
<thead class="bg-surface-container-low">
<tr class="h-10 text-outline font-label-sm uppercase tracking-wider">
<th class="px-space-md w-12 text-center">#</th>
<th class="px-space-md">Tài khoản &amp; Họ tên</th>
<th class="px-space-md">Email trường / SĐT</th>
<th class="px-space-md">Vai trò hệ thống</th>
<th class="px-space-md">Ngày tạo</th>
<th class="px-space-md">Đăng nhập gần nhất</th>
<th class="px-space-md">Trạng thái</th>
<th class="px-space-md text-right pr-space-lg">Thao tác</th>
</tr>
</thead>
<tbody class="font-body-md text-on-surface" id="account-table-body">
<!-- Row 1 -->
<tr class="h-16 hover:bg-surface-container-low/60 transition-colors">
<td class="px-space-md text-center font-data-mono text-outline">1</td>
<td class="px-space-md">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-full bg-primary-container text-on-primary flex items-center justify-center font-label-md shrink-0 shadow-sm">
                    NV
                  </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-on-surface hover:text-primary cursor-pointer truncate" onclick="openAccountDrawer('edit', 'admin_ctsv01')">Nguyễn Văn An</span>
<span class="font-data-mono text-[11px] text-primary">admin_ctsv01</span>
</div>
</div>
</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-body-sm text-on-surface">an.nv@hcmut.edu.vn</span>
<span class="font-data-mono text-outline text-[11px]">0918.234.567</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary-fixed text-on-primary-fixed font-label-sm">
<span class="material-symbols-outlined text-[14px]">shield_person</span>
                  Quản trị viên (Admin)
                </span>
</td>
<td class="px-space-md font-data-mono text-[12px] text-on-surface-variant">15/01/2023</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-data-mono text-[12px] text-on-surface font-semibold">10:42 24/02/2025</span>
<span class="font-label-sm text-[10px] text-secondary">Phòng CTSV (IP: 10.0.12.4)</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container/50 text-on-secondary-container font-label-sm">
<span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                  Đang hoạt động
                </span>
</td>
<td class="px-space-md text-right pr-space-lg">
<div class="flex items-center justify-end gap-1">
<button class="w-8 h-8 rounded-lg text-outline hover:text-primary hover:bg-surface-container flex items-center justify-center transition-all" onclick="openAccountDrawer('edit', 'admin_ctsv01')" title="Chỉnh sửa tài khoản">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-tertiary-container hover:bg-surface-container flex items-center justify-center transition-all" onclick="triggerQuickResetPwd('admin_ctsv01')" title="Đặt lại mật khẩu">
<span class="material-symbols-outlined text-[18px]">lock_reset</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-error hover:bg-error-container/30 flex items-center justify-center transition-all" onclick="toggleLockAccount(this, 'Nguyễn Văn An')" title="Khóa/Mở tài khoản">
<span class="material-symbols-outlined text-[18px]">lock</span>
</button>
</div>
</td>
</tr>
<!-- Row 2 -->
<tr class="h-16 hover:bg-surface-container-low/60 transition-colors">
<td class="px-space-md text-center font-data-mono text-outline">2</td>
<td class="px-space-md">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center font-label-md shrink-0 shadow-sm">
                    TL
                  </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-on-surface hover:text-primary cursor-pointer truncate" onclick="openAccountDrawer('edit', 'linh.tt')">Trần Thùy Linh</span>
<span class="font-data-mono text-[11px] text-primary">linh.tt</span>
</div>
</div>
</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-body-sm text-on-surface">linh.tran@hcmut.edu.vn</span>
<span class="font-data-mono text-outline text-[11px]">0903.112.980</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-highest text-primary font-label-sm">
<span class="material-symbols-outlined text-[14px]">badge</span>
                  Chuyên viên CTSV
                </span>
</td>
<td class="px-space-md font-data-mono text-[12px] text-on-surface-variant">02/09/2023</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-data-mono text-[12px] text-on-surface font-semibold">08:15 24/02/2025</span>
<span class="font-label-sm text-[10px] text-outline">Khu H6 (IP: 10.0.12.89)</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container/50 text-on-secondary-container font-label-sm">
<span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                  Đang hoạt động
                </span>
</td>
<td class="px-space-md text-right pr-space-lg">
<div class="flex items-center justify-end gap-1">
<button class="w-8 h-8 rounded-lg text-outline hover:text-primary hover:bg-surface-container flex items-center justify-center transition-all" onclick="openAccountDrawer('edit', 'linh.tt')" title="Chỉnh sửa tài khoản">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-tertiary-container hover:bg-surface-container flex items-center justify-center transition-all" onclick="triggerQuickResetPwd('linh.tt')" title="Đặt lại mật khẩu">
<span class="material-symbols-outlined text-[18px]">lock_reset</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-error hover:bg-error-container/30 flex items-center justify-center transition-all" onclick="toggleLockAccount(this, 'Trần Thùy Linh')" title="Khóa/Mở tài khoản">
<span class="material-symbols-outlined text-[18px]">lock</span>
</button>
</div>
</td>
</tr>
<!-- Row 3 -->
<tr class="h-16 hover:bg-surface-container-low/60 transition-colors">
<td class="px-space-md text-center font-data-mono text-outline">3</td>
<td class="px-space-md">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-full bg-surface-container-highest text-on-surface flex items-center justify-center font-label-md shrink-0">
                    LD
                  </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-on-surface hover:text-primary cursor-pointer truncate" onclick="openAccountDrawer('edit', 'khoa.cntt_dung')">Lê Quốc Dũng</span>
<span class="font-data-mono text-[11px] text-primary">khoa.cntt_dung</span>
</div>
</div>
</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-body-sm text-on-surface">dung.le@cse.hcmut.edu.vn</span>
<span class="font-data-mono text-outline text-[11px]">0977.890.123</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-tertiary-fixed text-on-tertiary-fixed font-label-sm">
<span class="material-symbols-outlined text-[14px]">domain</span>
                  Cán bộ Khoa CNTT
                </span>
</td>
<td class="px-space-md font-data-mono text-[12px] text-on-surface-variant">10/11/2023</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-data-mono text-[12px] text-on-surface font-semibold">16:50 21/02/2025</span>
<span class="font-label-sm text-[10px] text-outline">Khoa CNTT - A1</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container/50 text-on-secondary-container font-label-sm">
<span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                  Đang hoạt động
                </span>
</td>
<td class="px-space-md text-right pr-space-lg">
<div class="flex items-center justify-end gap-1">
<button class="w-8 h-8 rounded-lg text-outline hover:text-primary hover:bg-surface-container flex items-center justify-center transition-all" onclick="openAccountDrawer('edit', 'khoa.cntt_dung')" title="Chỉnh sửa tài khoản">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-tertiary-container hover:bg-surface-container flex items-center justify-center transition-all" onclick="triggerQuickResetPwd('khoa.cntt_dung')" title="Đặt lại mật khẩu">
<span class="material-symbols-outlined text-[18px]">lock_reset</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-error hover:bg-error-container/30 flex items-center justify-center transition-all" onclick="toggleLockAccount(this, 'Lê Quốc Dũng')" title="Khóa/Mở tài khoản">
<span class="material-symbols-outlined text-[18px]">lock</span>
</button>
</div>
</td>
</tr>
<!-- Row 4 -->
<tr class="h-16 hover:bg-surface-container-low/60 transition-colors">
<td class="px-space-md text-center font-data-mono text-outline">4</td>
<td class="px-space-md">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-full bg-surface-container-high text-on-surface flex items-center justify-center font-label-md shrink-0">
                    PQ
                  </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-on-surface hover:text-primary cursor-pointer truncate" onclick="openAccountDrawer('edit', 'tt.quang')">Phạm Nhật Quang</span>
<span class="font-data-mono text-[11px] text-primary">tt.quang</span>
</div>
</div>
</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-body-sm text-on-surface">quang.pn@hcmut.edu.vn</span>
<span class="font-data-mono text-outline text-[11px]">0982.554.331</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container-high text-on-surface font-label-sm">
<span class="material-symbols-outlined text-[14px]">local_police</span>
                  Thanh tra học đường
                </span>
</td>
<td class="px-space-md font-data-mono text-[12px] text-on-surface-variant">04/01/2024</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-data-mono text-[12px] text-on-surface font-semibold">09:12 24/02/2025</span>
<span class="font-label-sm text-[10px] text-outline">Tuần tra Khu B4</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-secondary-container/50 text-on-secondary-container font-label-sm">
<span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>
                  Đang hoạt động
                </span>
</td>
<td class="px-space-md text-right pr-space-lg">
<div class="flex items-center justify-end gap-1">
<button class="w-8 h-8 rounded-lg text-outline hover:text-primary hover:bg-surface-container flex items-center justify-center transition-all" onclick="openAccountDrawer('edit', 'tt.quang')" title="Chỉnh sửa tài khoản">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-tertiary-container hover:bg-surface-container flex items-center justify-center transition-all" onclick="triggerQuickResetPwd('tt.quang')" title="Đặt lại mật khẩu">
<span class="material-symbols-outlined text-[18px]">lock_reset</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-error hover:bg-error-container/30 flex items-center justify-center transition-all" onclick="toggleLockAccount(this, 'Phạm Nhật Quang')" title="Khóa/Mở tài khoản">
<span class="material-symbols-outlined text-[18px]">lock</span>
</button>
</div>
</td>
</tr>
<!-- Row 5 (Locked status) -->
<tr class="h-16 hover:bg-surface-container-low/60 transition-colors opacity-80">
<td class="px-space-md text-center font-data-mono text-outline">5</td>
<td class="px-space-md">
<div class="flex items-center gap-3">
<div class="w-9 h-9 rounded-full bg-error-container text-on-error-container flex items-center justify-center font-label-md shrink-0">
                    VT
                  </div>
<div class="flex flex-col min-w-0">
<span class="font-label-lg text-on-surface hover:text-primary cursor-pointer line-through text-outline truncate" onclick="openAccountDrawer('edit', 'thanh.vu')">Vũ Hoàng Thanh</span>
<span class="font-data-mono text-[11px] text-outline">thanh.vu</span>
</div>
</div>
</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-body-sm text-outline">thanh.vu@dee.hcmut.edu.vn</span>
<span class="font-data-mono text-outline text-[11px]">0913.990.221</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-outline font-label-sm">
<span class="material-symbols-outlined text-[14px]">domain</span>
                  Cán bộ Khoa Điện - ĐT
                </span>
</td>
<td class="px-space-md font-data-mono text-[12px] text-outline">12/05/2023</td>
<td class="px-space-md">
<div class="flex flex-col">
<span class="font-data-mono text-[12px] text-outline">14:02 10/12/2024</span>
<span class="font-label-sm text-[10px] text-error">Chuyển công tác</span>
</div>
</td>
<td class="px-space-md">
<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-error-container text-on-error-container font-label-sm">
<span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                  Đã khóa
                </span>
</td>
<td class="px-space-md text-right pr-space-lg">
<div class="flex items-center justify-end gap-1">
<button class="w-8 h-8 rounded-lg text-outline hover:text-primary hover:bg-surface-container flex items-center justify-center transition-all" onclick="openAccountDrawer('edit', 'thanh.vu')" title="Chỉnh sửa tài khoản">
<span class="material-symbols-outlined text-[18px]">edit</span>
</button>
<button class="w-8 h-8 rounded-lg text-outline hover:text-tertiary-container hover:bg-surface-container flex items-center justify-center transition-all" onclick="triggerQuickResetPwd('thanh.vu')" title="Đặt lại mật khẩu">
<span class="material-symbols-outlined text-[18px]">lock_reset</span>
</button>
<button class="w-8 h-8 rounded-lg text-error hover:bg-error-container/30 flex items-center justify-center transition-all" onclick="toggleLockAccount(this, 'Vũ Hoàng Thanh')" title="Khóa/Mở tài khoản">
<span class="material-symbols-outlined text-[18px]">lock_open</span>
</button>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<!-- Table Pagination Bar -->
<div class="p-space-md bg-surface-container-low flex flex-col sm:flex-row items-center justify-between gap-space-sm">
<div class="flex items-center gap-2">
<span class="font-body-sm text-outline">Hiển thị</span>
<select class="h-8 px-2 rounded-lg bg-surface-container-lowest text-on-surface font-data-mono text-body-sm focus:outline-none cursor-pointer shadow-sm">
<option>10</option>
<option selected="">20</option>
<option>50</option>
</select>
<span class="font-body-sm text-outline">trên tổng số <strong class="text-on-surface font-semibold">28</strong> tài khoản cán bộ</span>
</div>
<div class="flex items-center gap-1.5">
<button class="w-8 h-8 rounded-lg bg-surface-container-lowest text-outline hover:text-on-surface flex items-center justify-center shadow-sm disabled:opacity-40" disabled="">
<span class="material-symbols-outlined text-[18px]">chevron_left</span>
</button>
<button class="w-8 h-8 rounded-lg bg-primary text-on-primary font-data-mono text-body-sm font-semibold shadow-sm">1</button>
<button class="w-8 h-8 rounded-lg bg-surface-container-lowest text-on-surface-variant hover:bg-surface-container font-data-mono text-body-sm shadow-sm">2</button>
<button class="w-8 h-8 rounded-lg bg-surface-container-lowest text-outline hover:text-on-surface flex items-center justify-center shadow-sm">
<span class="material-symbols-outlined text-[18px]">chevron_right</span>
</button>
</div>
</div>
</div>
</div>
<!-- TAB 2: MA TRẬN PHÂN QUYỀN (PERMISSION MATRIX) -->
<div class="flex-col gap-space-md hidden" id="view-matrix">
<!-- Matrix Information & Summary Banner -->
<div class="bg-surface-container-lowest p-space-md rounded-xl shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-space-md">
<div class="flex items-center gap-space-md">
<div class="w-12 h-12 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shrink-0 shadow-sm">
<span class="material-symbols-outlined text-[26px]">tune</span>
</div>
<div class="flex flex-col">
<h2 class="font-headline-md text-headline-md text-on-surface">Cấu hình phân quyền dựa trên vai trò (RBAC)</h2>
<p class="font-body-sm text-body-sm text-on-surface-variant">
            Điều chỉnh chi tiết quyền hạn: <strong>Xem (X)</strong>, <strong>Thêm (T)</strong>, <strong>Sửa (S)</strong>, <strong>Xóa (Xó)</strong> cho 6 phân hệ cốt lõi. Nhấn "Lưu cấu hình" để áp dụng ngay.
          </p>
</div>
</div>
<div class="flex items-center gap-space-sm self-end md:self-auto shrink-0">
<button class="h-9 px-3.5 rounded-lg bg-surface-container-low text-on-surface-variant hover:text-on-surface hover:bg-surface-container font-label-md flex items-center gap-1.5 transition-all" onclick="restoreMatrixDefaults()">
<span class="material-symbols-outlined text-[18px]">restart_alt</span>
<span class="">Khôi phục mặc định</span>
</button>
<button class="h-9 px-4 rounded-lg bg-primary-container hover:bg-primary text-on-primary font-label-md flex items-center gap-1.5 shadow-sm transition-all" onclick="saveMatrixConfig()">
<span class="material-symbols-outlined text-[18px]">save</span>
<span class="">Lưu cấu hình phân quyền</span>
</button>
</div>
</div>
<!-- Matrix Grid Table -->
<div class="bg-surface-container-lowest rounded-xl shadow-sm overflow-hidden flex flex-col">
<div class="overflow-x-auto">
<table class="w-full text-left border-collapse">
<thead>
<tr class="bg-surface-container-low">
<!-- Left Corner Header -->
<th class="p-space-md w-[320px] font-label-md text-on-surface uppercase tracking-wider bg-surface-container-low sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center justify-between">
<span class="">Chức năng hệ thống</span>
<span class="material-symbols-outlined text-[18px] text-outline">dataset</span>
</div>
</th>
<!-- Role Column 1 -->
<th class="p-space-md min-w-[210px] text-center bg-primary-container/10">
<div class="flex flex-col items-center gap-1">
<div class="flex items-center gap-1.5 text-primary font-label-lg">
<span class="material-symbols-outlined text-[18px]">shield_person</span>
<span class="">Admin CTSV</span>
</div>
<span class="font-label-sm text-[11px] text-primary/80">Quản trị tối cao</span>
<div class="flex items-center justify-center gap-3 pt-2 font-label-sm text-[11px] text-outline">
<span class="w-5 text-center" title="Xem">Xem</span>
<span class="w-5 text-center" title="Thêm">Thêm</span>
<span class="w-5 text-center" title="Sửa">Sửa</span>
<span class="w-5 text-center" title="Xóa">Xóa</span>
</div>
</div>
</th>
<!-- Role Column 2 -->
<th class="p-space-md min-w-[210px] text-center">
<div class="flex flex-col items-center gap-1">
<div class="flex items-center gap-1.5 text-on-surface font-label-lg">
<span class="material-symbols-outlined text-[18px] text-primary">badge</span>
<span class="">Chuyên viên CTSV</span>
</div>
<span class="font-label-sm text-[11px] text-on-surface-variant">Xử lý &amp; Hậu kiểm</span>
<div class="flex items-center justify-center gap-3 pt-2 font-label-sm text-[11px] text-outline">
<span class="w-5 text-center" title="Xem">Xem</span>
<span class="w-5 text-center" title="Thêm">Thêm</span>
<span class="w-5 text-center" title="Sửa">Sửa</span>
<span class="w-5 text-center" title="Xóa">Xóa</span>
</div>
</div>
</th>
<!-- Role Column 3 -->
<th class="p-space-md min-w-[210px] text-center bg-surface-container-low/40">
<div class="flex flex-col items-center gap-1">
<div class="flex items-center gap-1.5 text-on-surface font-label-lg">
<span class="material-symbols-outlined text-[18px] text-tertiary">domain</span>
<span class="">Cán bộ Khoa</span>
</div>
<span class="font-label-sm text-[11px] text-on-surface-variant">Theo dõi SV khoa</span>
<div class="flex items-center justify-center gap-3 pt-2 font-label-sm text-[11px] text-outline">
<span class="w-5 text-center" title="Xem">Xem</span>
<span class="w-5 text-center" title="Thêm">Thêm</span>
<span class="w-5 text-center" title="Sửa">Sửa</span>
<span class="w-5 text-center" title="Xóa">Xóa</span>
</div>
</div>
</th>
<!-- Role Column 4 -->
<th class="p-space-md min-w-[210px] text-center">
<div class="flex flex-col items-center gap-1">
<div class="flex items-center gap-1.5 text-on-surface font-label-lg">
<span class="material-symbols-outlined text-[18px] text-secondary">local_police</span>
<span class="">Thanh tra học đường</span>
</div>
<span class="font-label-sm text-[11px] text-on-surface-variant">Tuần tra &amp; Báo lỗi</span>
<div class="flex items-center justify-center gap-3 pt-2 font-label-sm text-[11px] text-outline">
<span class="w-5 text-center" title="Xem">Xem</span>
<span class="w-5 text-center" title="Thêm">Thêm</span>
<span class="w-5 text-center" title="Sửa">Sửa</span>
<span class="w-5 text-center" title="Xóa">Xóa</span>
</div>
</div>
</th>
</tr>
</thead>
<tbody class="divide-y divide-surface-container-high font-body-md">
<!-- Feature 1 -->
<tr class="hover:bg-surface-container-low/40 transition-colors">
<td class="p-space-md bg-surface-container-lowest sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center gap-3">
<span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined text-[16px]">videocam</span>
</span>
<div>
<p class="font-label-lg text-on-surface leading-snug">1. Xem danh sách &amp; ảnh vi phạm camera</p>
<p class="font-body-sm text-[11px] text-outline">Camera giám sát AI, ảnh bằng chứng phục vụ xử lý</p>
</div>
</div>
</td>
<!-- Admin -->
<td class="p-space-md text-center bg-primary-container/5">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded text-primary-container accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded text-primary-container accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded text-primary-container accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded text-primary-container accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- CTSV -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- Faculty -->
<td class="p-space-md text-center bg-surface-container-low/20">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Inspector -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
</tr>
<!-- Feature 2 -->
<tr class="hover:bg-surface-container-low/40 transition-colors">
<td class="p-space-md bg-surface-container-lowest sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center gap-3">
<span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined text-[16px]">file_download</span>
</span>
<div>
<p class="font-label-lg text-on-surface leading-snug">2. Xuất báo cáo (Excel, PDF)</p>
<p class="font-body-sm text-[11px] text-outline">Báo cáo tổng hợp vi phạm theo tuần, tháng, học kỳ</p>
</div>
</div>
</td>
<!-- Admin -->
<td class="p-space-md text-center bg-primary-container/5">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- CTSV -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- Faculty -->
<td class="p-space-md text-center bg-surface-container-low/20">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Inspector -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
</tr>
<!-- Feature 3 -->
<tr class="hover:bg-surface-container-low/40 transition-colors">
<td class="p-space-md bg-surface-container-lowest sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center gap-3">
<span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined text-[16px]">school</span>
</span>
<div>
<p class="font-label-lg text-on-surface leading-snug">3. Quản lý sinh viên</p>
<p class="font-body-sm text-[11px] text-outline">Hồ sơ kỷ luật sinh viên, import Excel, tra cứu MSSV</p>
</div>
</div>
</td>
<!-- Admin -->
<td class="p-space-md text-center bg-primary-container/5">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- CTSV -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- Faculty -->
<td class="p-space-md text-center bg-surface-container-low/20">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Inspector -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
</tr>
<!-- Feature 4 -->
<tr class="hover:bg-surface-container-low/40 transition-colors">
<td class="p-space-md bg-surface-container-lowest sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center gap-3">
<span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined text-[16px]">category</span>
</span>
<div>
<p class="font-label-lg text-on-surface leading-snug">4. Quản lý danh mục</p>
<p class="font-body-sm text-[11px] text-outline">Danh sách Khoa, Lớp sinh hoạt, Loại lỗi vi phạm, Tọa độ Camera</p>
</div>
</div>
</td>
<!-- Admin -->
<td class="p-space-md text-center bg-primary-container/5">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- CTSV -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Faculty -->
<td class="p-space-md text-center bg-surface-container-low/20">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Inspector -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
</tr>
<!-- Feature 5 -->
<tr class="hover:bg-surface-container-low/40 transition-colors">
<td class="p-space-md bg-surface-container-lowest sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center gap-3">
<span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined text-[16px]">admin_panel_settings</span>
</span>
<div>
<p class="font-label-lg text-on-surface leading-snug">5. Quản lý tài khoản &amp; gán quyền</p>
<p class="font-body-sm text-[11px] text-outline">Tạo lập cán bộ, cấp quyền truy cập chức năng</p>
</div>
</div>
</td>
<!-- Admin -->
<td class="p-space-md text-center bg-primary-container/5">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- CTSV -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Faculty -->
<td class="p-space-md text-center bg-surface-container-low/20">
<div class="flex items-center justify-center gap-3">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Inspector -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
</tr>
<!-- Feature 6 -->
<tr class="hover:bg-surface-container-low/40 transition-colors">
<td class="p-space-md bg-surface-container-lowest sticky left-0 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.06)]">
<div class="flex items-center gap-3">
<span class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary shrink-0">
<span class="material-symbols-outlined text-[16px]">psychology</span>
</span>
<div>
<p class="font-label-lg text-on-surface leading-snug">6. Cấu hình AI &amp; Ngưỡng nhận diện</p>
<p class="font-body-sm text-[11px] text-outline">Độ tin cậy thẻ SV, đồng phục (Threshold 85%+), FPS phân tích</p>
</div>
</div>
</td>
<!-- Admin -->
<td class="p-space-md text-center bg-primary-container/5">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
</div>
</td>
<!-- CTSV -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input checked="" class="w-4 h-4 rounded accent-primary-container cursor-pointer" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Faculty -->
<td class="p-space-md text-center bg-surface-container-low/20">
<div class="flex items-center justify-center gap-3">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
<!-- Inspector -->
<td class="p-space-md text-center">
<div class="flex items-center justify-center gap-3">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
<input class="w-4 h-4 rounded opacity-40 cursor-not-allowed" disabled="" type="checkbox">
</div>
</td>
</tr>
</tbody>
</table>
</div>
<!-- Matrix Footer Notes -->
<div class="p-space-md bg-surface-container-low flex flex-col md:flex-row items-start md:items-center justify-between gap-space-sm text-outline font-label-sm">
<div class="flex items-center gap-4 flex-wrap">
<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-primary-container"></span> Có quyền thực thi</span>
<span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-outline-variant/40"></span> Không có quyền</span>
<span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[14px]">lock</span> Khóa cứng theo chuẩn hệ thống</span>
</div>
<div>
<span class="">Cập nhật ma trận gần nhất: <strong>14:20 18/02/2025</strong> bởi Admin CTSV</span>
</div>
</div>
</div>
</div>
<!-- SIDE DRAWER MODAL: TẠO / SỬA TÀI KHOẢN -->
<div class="fixed inset-0 bg-on-background/40 backdrop-blur-sm z-50 transition-opacity hidden" id="account-drawer-backdrop" onclick="closeAccountDrawer()"></div>
<div class="fixed top-0 right-0 h-full w-full max-w-[460px] bg-surface-container-lowest shadow-2xl z-50 transform transition-transform duration-300 flex flex-col justify-between translate-x-full" id="account-drawer">
<!-- Drawer Header -->
<div class="p-space-lg bg-surface-container-low flex items-center justify-between">
<div class="flex items-center gap-space-sm">
<div class="w-10 h-10 rounded-xl bg-primary-container text-on-primary flex items-center justify-center shadow-sm">
<span class="material-symbols-outlined text-[22px]">person_add</span>
</div>
<div>
<h3 class="font-headline-md text-headline-md text-on-surface" id="drawer-title">Chỉnh sửa tài khoản</h3>
<p class="font-label-sm text-outline">Hệ thống giám sát camera tác phong CTSV</p>
</div>
</div>
<button class="w-9 h-9 rounded-xl hover:bg-surface-container text-outline hover:text-on-surface flex items-center justify-center transition-colors" onclick="closeAccountDrawer()">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<!-- Drawer Body Form -->
<div class="flex-1 overflow-y-auto p-space-lg flex flex-col gap-space-md">
<!-- Input: Username -->
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface flex items-center justify-between">
<span class="">Tên đăng nhập <span class="text-error">*</span></span>
<span class="font-label-sm text-outline">Chỉ chữ thường, số, dấu chấm</span>
</label>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">account_circle</span>
<input class="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-data-mono text-body-md focus:outline-none focus:bg-surface-container focus:shadow-[0_0_0_2px_rgba(29,78,216,0.2)] transition-all" id="drawer-username" placeholder="vd: pham.thanh_ctsv" type="text">
</div>
</div>
<!-- Input: Fullname -->
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface">
          Họ và tên cán bộ <span class="text-error">*</span>
</label>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">badge</span>
<input class="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:outline-none focus:bg-surface-container focus:shadow-[0_0_0_2px_rgba(29,78,216,0.2)] transition-all" id="drawer-fullname" placeholder="vd: Phạm Minh Thành" type="text">
</div>
</div>
<!-- Input: Email -->
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface">
          Email trường (.edu.vn) <span class="text-error">*</span>
</label>
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">mail</span>
<input class="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:outline-none focus:bg-surface-container focus:shadow-[0_0_0_2px_rgba(29,78,216,0.2)] transition-all" id="drawer-email" placeholder="thanh.pm@hcmut.edu.vn" type="email">
</div>
</div>
<!-- Input: Phone & Unit -->
<div class="grid grid-cols-2 gap-space-sm">
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface">Số điện thoại</label>
<input class="w-full h-10 px-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-data-mono text-body-md focus:outline-none focus:bg-surface-container transition-all" id="drawer-phone" placeholder="0987.xxx.xxx" type="text">
</div>
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface">Đơn vị / Khoa</label>
<input class="w-full h-10 px-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:outline-none focus:bg-surface-container transition-all" id="drawer-unit" placeholder="Phòng CTSV" type="text">
</div>
</div>
<!-- Select: Role -->
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface flex items-center justify-between">
<span class="">Vai trò hệ thống <span class="text-error">*</span></span>
<span class="font-label-sm text-primary cursor-pointer hover:underline" onclick="switchMainTab('matrix'); closeAccountDrawer();">Xem ma trận quyền</span>
</label>
<div class="relative">
<select class="w-full h-10 px-3 rounded-lg bg-surface-container-low text-on-surface font-body-md focus:outline-none focus:bg-surface-container appearance-none pr-8 cursor-pointer" id="drawer-role">
<option value="ADMIN">Quản trị viên (Admin CTSV)</option>
<option selected="" value="CTSV">Chuyên viên CTSV</option>
<option value="FACULTY">Cán bộ phụ trách Khoa</option>
<option value="INSPECTOR">Thanh tra tác phong sinh viên</option>
</select>
<span class="material-symbols-outlined absolute right-2.5 top-1/2 -translate-y-1/2 text-outline text-[18px] pointer-events-none">expand_more</span>
</div>
</div>
<!-- Input: Temporary Password & Action -->
<div class="p-space-md bg-surface-container-low rounded-xl flex flex-col gap-2">
<div class="flex items-center justify-between">
<label class="font-label-md text-on-surface flex items-center gap-1.5">
<span class="material-symbols-outlined text-[16px] text-tertiary">key</span>
<span class="">Mật khẩu tạm thời</span>
</label>
<button class="font-label-sm text-primary hover:underline flex items-center gap-1" onclick="generateRandomPass()" type="button">
<span class="material-symbols-outlined text-[14px]">autorenew</span>
<span class="">Tạo ngẫu nhiên</span>
</button>
</div>
<div class="relative">
<input class="w-full h-9 px-3 rounded-lg bg-surface-container-lowest text-primary font-data-mono text-body-md focus:outline-none focus:bg-surface-container transition-all" id="drawer-pass" type="text" value="Ctsv@2025#AiCam">
</div>
<p class="font-body-sm text-[11px] text-outline">Người dùng sẽ được yêu cầu đổi mật khẩu ở lần đăng nhập đầu tiên.</p>
<div class="pt-2" id="drawer-reset-pwd-container">
<button class="w-full h-9 rounded-lg bg-tertiary-fixed text-on-tertiary-fixed hover:bg-tertiary-fixed-dim font-label-md flex items-center justify-center gap-1.5 transition-all" onclick="triggerResetMail()" type="button">
<span class="material-symbols-outlined text-[16px]">forward_to_inbox</span>
<span class="">Gửi email đặt lại mật khẩu</span>
</button>
</div>
</div>
<!-- Note / Remarks -->
<div class="flex flex-col gap-1.5">
<label class="font-label-md text-on-surface">Ghi chú phân công / Nhiệm vụ</label>
<textarea class="w-full p-3 rounded-lg bg-surface-container-low text-on-surface placeholder:text-outline font-body-md focus:outline-none focus:bg-surface-container focus:shadow-[0_0_0_2px_rgba(29,78,216,0.2)] transition-all resize-none" id="drawer-notes" placeholder="Ghi chú phân công phụ trách tầng học, khu vực camera..." rows="3"></textarea>
</div>
<!-- Active Toggle Switch -->
<div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low">
<div class="flex flex-col">
<span class="font-label-md text-on-surface">Trạng thái kích hoạt</span>
<span class="font-body-sm text-[11px] text-outline">Cho phép đăng nhập ngay vào hệ thống</span>
</div>
<label class="relative inline-flex items-center cursor-pointer">
<input checked="" class="sr-only peer" id="drawer-status-toggle" type="checkbox">
<div class="w-11 h-6 bg-surface-variant peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-container"></div>
</label>
</div>
</div>
<!-- Drawer Footer Actions -->
<div class="p-space-lg bg-surface-container-low flex items-center justify-end gap-space-sm">
<button class="h-10 px-4 rounded-lg bg-surface-container text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high font-label-md transition-all" onclick="closeAccountDrawer()">
        Hủy bỏ
      </button>
<button class="h-10 px-5 rounded-lg bg-primary-container hover:bg-primary text-on-primary font-label-md flex items-center gap-2 shadow-sm transition-all" onclick="saveAccountFromDrawer()">
<span class="material-symbols-outlined text-[18px]">save</span>
<span class="">Lưu tài khoản</span>
</button>
</div>
</div>
<!-- Notification Toast (Injected dynamic) -->
<div class="fixed bottom-6 right-6 bg-on-background text-inverse-on-surface px-4 py-3 rounded-xl shadow-xl flex items-center gap-3 transform translate-y-24 opacity-0 transition-all duration-300 z-50" id="toast-notify">
<span class="material-symbols-outlined text-secondary text-[22px]" id="toast-icon">check_circle</span>
<span class="font-body-md" id="toast-msg">Đã cập nhật dữ liệu thành công.</span>
</div>
</div>
<!-- CLIENT JAVASCRIPT LOGIC -->
<script>
  // TAB SWITCHING
  function switchMainTab(tab) {
    const viewAccounts = document.getElementById('view-accounts');
    const viewMatrix = document.getElementById('view-matrix');
    const btnAccounts = document.getElementById('tab-btn-accounts');
    const btnMatrix = document.getElementById('tab-btn-matrix');

    if (tab === 'accounts') {
      viewAccounts.classList.remove('hidden');
      viewAccounts.classList.add('flex');
      viewMatrix.classList.add('hidden');
      viewMatrix.classList.remove('flex');

      btnAccounts.className = "flex items-center gap-2 px-space-md py-2 rounded-lg font-label-lg transition-all bg-primary-container text-on-primary shadow-sm";
      btnMatrix.className = "flex items-center gap-2 px-space-md py-2 rounded-lg font-label-lg transition-all text-on-surface-variant hover:text-on-surface hover:bg-surface-container";
    } else {
      viewAccounts.classList.add('hidden');
      viewAccounts.classList.remove('flex');
      viewMatrix.classList.remove('hidden');
      viewMatrix.classList.add('flex');

      btnMatrix.className = "flex items-center gap-2 px-space-md py-2 rounded-lg font-label-lg transition-all bg-primary-container text-on-primary shadow-sm";
      btnAccounts.className = "flex items-center gap-2 px-space-md py-2 rounded-lg font-label-lg transition-all text-on-surface-variant hover:text-on-surface hover:bg-surface-container";
    }
  }

  // DRAWER TOGGLE & POPULATE
  function openAccountDrawer(mode, username = '') {
    const backdrop = document.getElementById('account-drawer-backdrop');
    const drawer = document.getElementById('account-drawer');
    const title = document.getElementById('drawer-title');
    const resetPwdBox = document.getElementById('drawer-reset-pwd-container');

    backdrop.classList.remove('hidden');
    setTimeout(() => drawer.classList.remove('translate-x-full'), 10);

    if (mode === 'edit') {
      title.innerText = 'Chỉnh sửa tài khoản';
      resetPwdBox.classList.remove('hidden');
      // Mock populate data
      if (username === 'admin_ctsv01') {
        document.getElementById('drawer-username').value = 'admin_ctsv01';
        document.getElementById('drawer-fullname').value = 'Nguyễn Văn An';
        document.getElementById('drawer-email').value = 'an.nv@hcmut.edu.vn';
        document.getElementById('drawer-phone').value = '0918.234.567';
        document.getElementById('drawer-unit').value = 'Phòng CTSV';
        document.getElementById('drawer-role').value = 'ADMIN';
        document.getElementById('drawer-notes').value = 'Chịu trách nhiệm vận hành hệ thống camera AI toàn trường';
      } else if (username === 'linh.tt') {
        document.getElementById('drawer-username').value = 'linh.tt';
        document.getElementById('drawer-fullname').value = 'Trần Thùy Linh';
        document.getElementById('drawer-email').value = 'linh.tran@hcmut.edu.vn';
        document.getElementById('drawer-phone').value = '0903.112.980';
        document.getElementById('drawer-unit').value = 'Phòng CTSV';
        document.getElementById('drawer-role').value = 'CTSV';
        document.getElementById('drawer-notes').value = 'Phụ trách hậu kiểm ảnh vi phạm thẻ & đồng phục tòa H6';
      }
    } else {
      title.innerText = 'Thêm tài khoản cán bộ mới';
      resetPwdBox.classList.add('hidden');
      // Reset form
      document.getElementById('drawer-username').value = '';
      document.getElementById('drawer-fullname').value = '';
      document.getElementById('drawer-email').value = '';
      document.getElementById('drawer-phone').value = '';
      document.getElementById('drawer-unit').value = '';
      document.getElementById('drawer-notes').value = '';
      generateRandomPass();
    }
  }

  function closeAccountDrawer() {
    const backdrop = document.getElementById('account-drawer-backdrop');
    const drawer = document.getElementById('account-drawer');
    drawer.classList.add('translate-x-full');
    setTimeout(() => backdrop.classList.add('hidden'), 250);
  }

  function generateRandomPass() {
    const charset = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@#$";
    let pwd = "Ctsv@";
    for (let i = 0; i < 6; i++) {
      pwd += charset.charAt(Math.floor(Math.random() * charset.length));
    }
    document.getElementById('drawer-pass').value = pwd;
  }

  function saveAccountFromDrawer() {
    const username = document.getElementById('drawer-username').value;
    const fullname = document.getElementById('drawer-fullname').value;
    if (!username || !fullname) {
      showToast('Vui lòng điền đủ Tên đăng nhập và Họ tên cán bộ!', 'error');
      return;
    }
    closeAccountDrawer();
    showToast(`Đã lưu tài khoản "${fullname}" (${username}) thành công!`);
  }

  // QUICK ACTIONS & NOTIFICATIONS
  function triggerQuickResetPwd(username) {
    if (confirm(`Bạn có chắc chắn muốn cấp lại mật khẩu tạm thời cho tài khoản [${username}]? Hệ thống sẽ gửi email xác nhận.`)) {
      showToast(`Đã gửi đường dẫn đặt lại mật khẩu đến email cán bộ [${username}].`);
    }
  }

  function triggerResetMail() {
    const email = document.getElementById('drawer-email').value;
    if (!email) {
      showToast('Vui lòng nhập email cán bộ trước khi gửi yêu cầu!', 'error');
      return;
    }
    showToast(`Đã gửi email khôi phục mật khẩu tới ${email}`);
  }

  function toggleLockAccount(btn, name) {
    const isLocked = btn.innerHTML.includes('lock_open');
    const action = isLocked ? 'MỞ KHÓA' : 'TẠM KHÓA';
    if (confirm(`Xác nhận ${action} quyền truy cập của cán bộ ${name}?`)) {
      showToast(`Đã cập nhật trạng thái tài khoản: ${name}`);
    }
  }

  function resetFilters() {
    document.getElementById('acc-search').value = '';
    document.getElementById('acc-role-filter').value = 'ALL';
    document.getElementById('acc-status-filter').value = 'ALL';
    showToast('Đã làm mới bộ lọc danh sách.');
  }

  function exportAccountList() {
    showToast('Đang kết xuất danh sách tài khoản định dạng Excel (.xlsx)...');
  }

  function saveMatrixConfig() {
    showToast('Đã lưu cấu hình ma trận phân quyền RBAC thành công!');
  }

  function restoreMatrixDefaults() {
    if (confirm('Khôi phục ma trận phân quyền về cấu hình mặc định ban đầu của Nhà trường?')) {
      showToast('Đã khôi phục ma trận phân quyền mặc định.');
    }
  }

  function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast-notify');
    const toastMsg = document.getElementById('toast-msg');
    const toastIcon = document.getElementById('toast-icon');

    toastMsg.innerText = msg;
    if (type === 'error') {
      toastIcon.innerText = 'error';
      toastIcon.className = 'material-symbols-outlined text-error text-[22px]';
    } else {
      toastIcon.innerText = 'check_circle';
      toastIcon.className = 'material-symbols-outlined text-secondary text-[22px]';
    }

    toast.classList.remove('translate-y-24', 'opacity-0');
    setTimeout(() => {
      toast.classList.add('translate-y-24', 'opacity-0');
    }, 3200);
  }
</script>
<?php include 'includes/footer.php'; ?>
