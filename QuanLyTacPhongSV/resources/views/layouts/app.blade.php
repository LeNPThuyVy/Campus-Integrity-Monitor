@php
  $userRole = session('role', 'CTSV');
  $userName = session('display_name') ?: session('username', 'Chưa xác thực');
  $userRoleLabel = match ($userRole) {
      'ADMIN' => 'Quản trị viên',
      'CTSV' => 'Nhân viên CTSV',
      default => 'Chưa đăng nhập',
  };
  $currentRoute = Route::currentRouteName();
  $pageTitle = $pageTitle ?? 'Quản lý tác phong sinh viên';

  $navItems = [
      ['route' => 'dashboard', 'path' => '/home', 'icon' => 'dashboard', 'label' => 'Tổng quan', 'roles' => ['ADMIN', 'CTSV']],
      ['route' => 'violations', 'path' => '/list-vipham', 'icon' => 'warning', 'label' => 'Bản ghi vi phạm', 'roles' => ['ADMIN', 'CTSV']],
      ['route' => 'reports', 'path' => '/reporting', 'icon' => 'bar_chart', 'label' => 'Thống kê & báo cáo', 'roles' => ['ADMIN', 'CTSV']],
      ['route' => 'students', 'path' => '/ql-sinhvien', 'icon' => 'school', 'label' => 'Sinh viên', 'roles' => ['ADMIN', 'CTSV']],
      ['route' => 'catalogs', 'path' => '/danh-muc', 'icon' => 'category', 'label' => 'Danh mục', 'roles' => ['ADMIN', 'CTSV']],
      ['route' => 'facilities', 'path' => '/camera-khuvuc', 'icon' => 'videocam', 'label' => 'Camera & khu vực', 'roles' => ['ADMIN', 'CTSV']],
      ['route' => 'accounts', 'path' => '/phanquyen', 'icon' => 'admin_panel_settings', 'label' => 'Tài khoản & phân quyền', 'roles' => ['ADMIN']],
  ];
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="user-role" content="{{ $userRole }}">
  <title>{{ $pageTitle }} · CTSV</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
  <link rel="icon" href="{{ asset('assets/favicon.svg') }}" type="image/svg+xml">
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script src="{{ asset('assets/js/tailwind.config.js') }}"></script>
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
      @foreach ($navItems as $item)
        @if (in_array($userRole, $item['roles'], true))
          @php $active = ($currentRoute === $item['route']); @endphp
          <a class="flex items-center gap-space-sm px-space-md py-2.5 rounded-xl transition-all {{ $active ? 'bg-primary-container text-on-primary shadow-sm' : 'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' }}"
             href="{{ route($item['route']) }}"{!! $active ? ' aria-current="page"' : '' !!}>
            <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
            <span class="font-label-lg text-label-lg">{{ $item['label'] }}</span>
          </a>
        @endif
      @endforeach
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
        <span class="font-label-md text-label-md text-on-surface font-semibold truncate">{{ $pageTitle }}</span>
      </div>
    </div>
    <div class="relative" id="account-menu">
      <button class="flex items-center gap-space-sm pl-2 py-1 pr-1.5 rounded-xl hover:bg-surface-container-low transition-all" type="button" aria-haspopup="true" aria-expanded="false" id="account-menu-button">
        <span class="hidden sm:flex flex-col text-right">
          <span class="font-label-md text-label-md text-on-surface font-semibold leading-tight">{{ $userName }}</span>
          <span class="font-label-sm text-label-sm text-on-surface-variant leading-tight">{{ $userRoleLabel }}</span>
        </span>
        <span class="w-8 h-8 rounded-full bg-primary flex items-center justify-center">
          <span class="material-symbols-outlined text-on-primary text-[18px]">person</span>
        </span>
        <span class="material-symbols-outlined text-[16px] text-outline">expand_more</span>
      </button>
      <div class="absolute right-0 top-full mt-1.5 w-56 bg-surface-container-lowest rounded-xl shadow-[0_10px_15px_-3px_rgba(15,23,42,0.12)] py-1.5 hidden z-50" id="account-menu-panel">
        <div class="px-space-md py-2 border-b border-surface-container sm:hidden">
          <div class="font-label-md font-semibold text-on-surface">{{ $userName }}</div>
          <div class="font-label-sm text-on-surface-variant">{{ $userRoleLabel }}</div>
        </div>
        <a class="flex items-center gap-space-sm px-space-md py-2 text-on-surface hover:bg-surface-container-low font-label-md transition-colors" href="{{ route('password.change') }}">
          <span class="material-symbols-outlined text-[18px]">key</span>
          <span>Đổi mật khẩu</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="m-0">
          @csrf
          <button class="w-full flex items-center gap-space-sm px-space-md py-2 text-error hover:bg-error/10 font-label-md transition-colors text-left" type="submit">
            <span class="material-symbols-outlined text-[18px]">logout</span>
            <span>Đăng xuất</span>
          </button>
        </form>
      </div>
    </div>
  </header>

  <main class="pt-16 px-space-md sm:px-space-lg min-h-screen">
    @yield('content')
  </main>
</div>

<div class="fixed bottom-5 right-5 z-[60] flex max-w-sm flex-col gap-2" id="toast-region" aria-live="polite"></div>

<dialog class="w-[min(92vw,460px)] rounded-xl p-0 shadow-xl backdrop:bg-inverse-surface/50" id="confirm-dialog">
  <form method="dialog" class="flex flex-col gap-space-md p-space-lg">
    <h2 class="font-headline-md text-headline-md" id="confirm-title">Xác nhận thao tác</h2>
    <p class="text-on-surface-variant" id="confirm-message"></p>
    <div class="flex justify-end gap-space-sm">
      <button class="rounded-lg bg-surface-container px-space-md py-2 font-label-md" value="cancel" type="submit">Hủy</button>
      <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" value="ok" id="confirm-ok" type="submit">Xác nhận</button>
    </div>
  </form>
</dialog>

<script src="{{ asset('assets/js/common.js') }}"></script>
@stack('scripts')
</body>
</html>
