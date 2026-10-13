<?php
declare(strict_types=1);

require_once __DIR__ . '/student_module.php';
student_start_session();
$headerRole = $_SESSION['role'] ?? null;
$headerAuthenticated = ($_SESSION['authenticated'] ?? false) === true
    && in_array($headerRole, ['ADMIN', 'CTSV'], true);
$headerName = $headerAuthenticated
    ? (is_string($_SESSION['display_name'] ?? null)
        ? $_SESSION['display_name']
        : (is_string($_SESSION['username'] ?? null) ? $_SESSION['username'] : 'Tài khoản'))
    : 'Chưa xác thực';
$headerRoleLabel = match ($headerRole) {
    'ADMIN' => 'Quản trị viên',
    'CTSV' => 'Nhân viên CTSV',
    default => 'Chưa có phiên xác thực Web',
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="web_dashboard" name="shell-type">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="assets/js/tailwind.config.js"></script>
</head>
<body class="bg-surface font-body-md text-body-md text-on-surface antialiased">
<aside class="fixed left-0 top-0 h-full w-[260px] bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50 flex flex-col justify-between select-none">
  <div class="flex flex-col">
    <div class="p-space-md flex items-center gap-space-sm bg-surface-container-low">
      <div class="w-10 h-10 rounded-xl bg-primary-container flex items-center justify-center shrink-0 shadow-sm">
        <span class="material-symbols-outlined text-on-primary text-[22px]">videocam</span>
      </div>
      <div class="flex flex-col min-w-0">
        <span class="font-headline-sm text-headline-sm text-primary uppercase tracking-tight truncate">Quản lý tác phong</span>
        <span class="font-label-sm text-label-sm text-on-surface-variant truncate">Phòng Công tác Sinh viên</span>
      </div>
    </div>
    <div class="px-space-md pt-space-md pb-space-xs">
      <span class="font-label-sm text-label-sm uppercase tracking-wider text-outline">Hệ thống giám sát</span>
    </div>
    <nav class="flex flex-col gap-space-xs px-space-sm" data-active-classes="bg-primary-container text-on-primary font-label-lg rounded-xl shadow-sm">
      <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-all" data-path="tong-quan" href="#"><span class="material-symbols-outlined text-[20px]">dashboard</span><span class="font-label-lg text-label-lg">Tổng quan</span></a>
      <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-all" data-path="vi-pham" href="#"><span class="material-symbols-outlined text-[20px]">warning</span><span class="font-label-lg text-label-lg">Vi phạm</span></a>
      <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-all" data-path="thong-ke-bao-cao" href="#"><span class="material-symbols-outlined text-[20px]">bar_chart</span><span class="font-label-lg text-label-lg">Thống kê &amp; báo cáo</span></a>
      <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-all" data-path="sinh-vien" href="#"><span class="material-symbols-outlined text-[20px]">school</span><span class="font-label-lg text-label-lg">Sinh viên</span></a>
      <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-all" data-path="danh-muc" href="#"><span class="material-symbols-outlined text-[20px]">category</span><span class="font-label-lg text-label-lg">Danh mục</span></a>
      <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-all" data-path="tai-khoan-phan-quyen" href="#"><span class="material-symbols-outlined text-[20px]">admin_panel_settings</span><span class="font-label-lg text-label-lg">Tài khoản &amp; phân quyền</span></a>
    </nav>
  </div>
</aside>
<div class="pl-[260px]">
  <header class="fixed top-0 left-[260px] right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 px-space-lg flex items-center justify-between gap-space-md">
    <div class="flex items-center gap-space-md min-w-0">
      <div class="flex items-center gap-1.5 text-on-surface-variant">
        <span class="font-body-sm text-body-sm text-on-surface-variant">CTSV</span>
        <span class="material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="font-label-md text-label-md text-on-surface font-semibold">Hệ thống Camera AI</span>
      </div>
    </div>
    <div class="flex items-center gap-space-md">
      <button class="relative w-9 h-9 rounded-xl bg-surface-container-low text-on-surface-variant flex items-center justify-center opacity-60 cursor-not-allowed" title="Chưa có dịch vụ thông báo được cấu hình" type="button" disabled>
        <span class="material-symbols-outlined text-[20px]">notifications</span>
      </button>
      <div class="relative group">
        <button class="flex items-center gap-space-sm pl-2 py-1 pr-1.5 rounded-xl hover:bg-surface-container-low transition-all" type="button" aria-label="Tài khoản">
          <span class="flex flex-col text-right hidden sm:flex">
            <span class="font-label-md text-label-md text-on-surface font-semibold leading-tight"><?= htmlspecialchars($headerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
            <span class="font-label-sm text-label-sm text-on-surface-variant leading-tight"><?= htmlspecialchars($headerRoleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
          </span>
          <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
            <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
          </span>
          <span class="material-symbols-outlined text-[16px] text-outline">expand_more</span>
        </button>
        <div class="absolute right-0 top-full mt-1.5 w-52 bg-surface-container-lowest rounded-xl shadow-[0_10px_15px_-3px_rgba(15,23,42,0.08)] py-1.5 hidden group-hover:block z-50">
          <div class="px-space-md py-2">
            <p class="font-label-md text-label-md text-on-surface font-semibold"><?= htmlspecialchars($headerName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <p class="font-label-sm text-label-sm text-on-surface-variant"><?= htmlspecialchars($headerRoleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
          </div>
          <a class="flex items-center gap-2 px-space-md py-2 font-body-md text-body-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface" href="login.php">
            <span class="material-symbols-outlined text-[18px]">key</span><span>Đổi mật khẩu</span>
          </a>
          <div class="h-px bg-surface-container my-1"></div>
          <a class="flex items-center gap-2 px-space-md py-2 font-body-md text-body-md text-error hover:bg-error-container hover:text-on-error-container" href="logout.php">
            <span class="material-symbols-outlined text-[18px]">logout</span><span>Đăng xuất</span>
          </a>
        </div>
      </div>
    </div>
  </header>
  <main class="relative pt-16 w-full px-space-lg bg-surface">
