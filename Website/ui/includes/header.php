<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$cimUser = $cimUser ?? cim_current_user();
$headerRole = $cimUser['role'] ?? null;
$headerName = $cimUser
    ? ($cimUser['display_name'] !== '' ? $cimUser['display_name'] : $cimUser['username'])
    : 'Chưa xác thực';
$headerRoleLabel = match ($headerRole) {
    'ADMIN' => 'Quản trị viên',
    'CTSV' => 'Nhân viên CTSV',
    default => 'Chưa đăng nhập',
};
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '');
$pageTitle = $pageTitle ?? 'Quản lý tác phong sinh viên';
$navigation = [
    ['home.php', 'dashboard', 'Tổng quan', ['ADMIN', 'CTSV']],
    ['list_vipham.php', 'warning', 'Bản ghi vi phạm', ['ADMIN', 'CTSV']],
    ['reporting.php', 'bar_chart', 'Thống kê & báo cáo', ['ADMIN', 'CTSV']],
    ['ql_sinhvien.php', 'school', 'Sinh viên', ['ADMIN', 'CTSV']],
    ['danh_muc.php', 'category', 'Danh mục', ['ADMIN', 'CTSV']],
    ['camera_khuvuc.php', 'videocam', 'Camera & khu vực', ['ADMIN', 'CTSV']],
    ['phanquyen.php', 'admin_panel_settings', 'Tài khoản & phân quyền', ['ADMIN']],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="<?= cim_h($cimUser ? student_csrf_token() : '') ?>">
  <meta name="user-role" content="<?= cim_h($headerRole) ?>">
  <title><?= cim_h($pageTitle) ?> · CTSV</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script src="assets/js/tailwind.config.js"></script>
</head>
<body class="bg-surface font-body-md text-body-md text-on-surface antialiased">
<div class="fixed inset-0 z-40 hidden bg-inverse-surface/40 lg:hidden" id="sidebar-backdrop"></div>
<aside class="fixed left-0 top-0 z-50 flex h-full w-[260px] -translate-x-full flex-col justify-between bg-surface-container-lowest shadow-[0_1px_8px_rgba(0,0,0,0.04)] transition-transform duration-200 select-none lg:translate-x-0" id="sidebar">
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
    <nav class="flex flex-col gap-space-xs px-space-sm" aria-label="Điều hướng chính">
      <?php foreach ($navigation as [$href, $icon, $label, $roles]): ?>
        <?php if ($headerRole !== null && !in_array($headerRole, $roles, true)) { continue; } ?>
        <?php $active = $href === $currentPage; ?>
        <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl transition-all <?= $active ? 'bg-primary-container text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' ?>"
           href="<?= cim_h($href) ?>"<?= $active ? ' aria-current="page"' : '' ?>>
          <span class="material-symbols-outlined text-[20px]"><?= cim_h($icon) ?></span>
          <span class="font-label-lg text-label-lg"><?= cim_h($label) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
  <p class="px-space-md pb-space-md font-label-sm text-label-sm text-outline">
    Bản ghi AI không chứa danh tính sinh viên. CTSV tự đối chiếu và xử lý điểm rèn luyện bên ngoài hệ thống.
  </p>
</aside>
<div class="lg:pl-[260px]">
  <header class="fixed top-0 left-0 right-0 lg:left-[260px] h-16 bg-surface-container-lowest/90 backdrop-blur-md shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-30 px-space-md sm:px-space-lg flex items-center justify-between gap-space-md">
    <div class="flex items-center gap-space-sm min-w-0">
      <button class="lg:hidden w-9 h-9 rounded-xl bg-surface-container-low flex items-center justify-center" id="sidebar-toggle" type="button" aria-label="Mở menu" aria-controls="sidebar" aria-expanded="false">
        <span class="material-symbols-outlined text-[20px]">menu</span>
      </button>
      <div class="flex items-center gap-1.5 text-on-surface-variant min-w-0">
        <span class="hidden sm:inline font-body-sm text-body-sm">CTSV</span>
        <span class="hidden sm:inline material-symbols-outlined text-[16px]">chevron_right</span>
        <span class="font-label-md text-label-md text-on-surface font-semibold truncate"><?= cim_h($pageTitle) ?></span>
      </div>
    </div>
    <?php if ($cimUser): ?>
    <div class="relative" id="account-menu">
      <button class="flex items-center gap-space-sm pl-2 py-1 pr-1.5 rounded-xl hover:bg-surface-container-low transition-all" type="button" aria-haspopup="true" aria-expanded="false" id="account-menu-button">
        <span class="hidden sm:flex flex-col text-right">
          <span class="font-label-md text-label-md text-on-surface font-semibold leading-tight"><?= cim_h($headerName) ?></span>
          <span class="font-label-sm text-label-sm text-on-surface-variant leading-tight"><?= cim_h($headerRoleLabel) ?></span>
        </span>
        <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
          <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
        </span>
        <span class="material-symbols-outlined text-[16px] text-outline">expand_more</span>
      </button>
      <div class="absolute right-0 top-full mt-1.5 w-56 bg-surface-container-lowest rounded-xl shadow-[0_10px_15px_-3px_rgba(15,23,42,0.12)] py-1.5 hidden z-50" id="account-menu-panel">
        <div class="px-space-md py-2">
          <p class="font-label-md text-label-md text-on-surface font-semibold"><?= cim_h($headerName) ?></p>
          <p class="font-label-sm text-label-sm text-on-surface-variant"><?= cim_h($headerRoleLabel) ?> · <?= cim_h($cimUser['username']) ?></p>
        </div>
        <a class="flex items-center gap-2 px-space-md py-2 text-on-surface-variant hover:bg-surface-container hover:text-on-surface" href="doi_mat_khau.php">
          <span class="material-symbols-outlined text-[18px]">key</span><span>Đổi mật khẩu</span>
        </a>
        <div class="h-px bg-surface-container my-1"></div>
        <form method="post" action="logout.php">
          <input type="hidden" name="csrf_token" value="<?= cim_h(student_csrf_token()) ?>">
          <button class="flex w-full items-center gap-2 px-space-md py-2 text-error hover:bg-error-container hover:text-on-error-container" type="submit">
            <span class="material-symbols-outlined text-[18px]">logout</span><span>Đăng xuất</span>
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </header>
  <main class="relative pt-16 w-full px-space-md sm:px-space-lg bg-surface min-h-screen">
<?php if (!empty($GLOBALS['cim_forbidden'])): ?>
  <section class="my-space-lg max-w-3xl rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" role="alert">
    <h1 class="font-headline-lg text-headline-lg">Không có quyền truy cập</h1>
    <p class="mt-2 text-on-surface-variant">Trang này chỉ dành cho Quản trị viên. Liên hệ quản trị viên nếu bạn cần quyền này.</p>
  </section>
<?php include __DIR__ . '/footer.php'; exit; endif; ?>
