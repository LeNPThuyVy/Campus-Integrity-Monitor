<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

student_start_session();
header('Cache-Control: no-store');
header('X-Frame-Options: DENY');

$next = cim_safe_next($_GET['next'] ?? $_POST['next'] ?? null);
if (cim_current_user() !== null) {
    header('Location: ' . $next, true, 302);
    exit;
}

$error = null;
$notice = null;
if (isset($_SESSION['auth_notice'])) {
    $notice = (string)$_SESSION['auth_notice'];
    unset($_SESSION['auth_notice']);
}
if (isset($_GET['logged_out'])) {
    $notice = 'Bạn đã đăng xuất.';
}
if (isset($_GET['password_changed'])) {
    $notice = 'Đã đổi mật khẩu. Vui lòng đăng nhập lại bằng mật khẩu mới.';
}
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    if (!cim_verify_form_csrf()) {
        $error = 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.';
    } elseif ($username === '' || $password === '') {
        $error = 'Vui lòng nhập tên đăng nhập và mật khẩu.';
    } else {
        $result = cim_login($username, $password);
        if ($result['ok']) {
            header('Location: ' . $next, true, 303);
            exit;
        }
        $error = $result['message'];
    }
}
$csrf = student_csrf_token();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Đăng nhập · Quản lý tác phong sinh viên</title>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet">
  <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <script src="assets/js/tailwind.config.js"></script>
</head>
<body class="bg-surface font-body-md text-body-md text-on-surface antialiased">
<main class="relative w-full min-h-screen flex items-center justify-center p-space-md overflow-hidden">
  <div class="absolute inset-0 -z-10 bg-gradient-to-br from-surface via-surface-container-low to-surface-container-high opacity-80"></div>
  <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary-fixed opacity-40 blur-3xl pointer-events-none"></div>
  <div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-secondary-fixed opacity-30 blur-2xl pointer-events-none"></div>
  <div class="w-full max-w-[1040px] grid grid-cols-1 lg:grid-cols-12 rounded-xl shadow-xl overflow-hidden bg-surface-container-lowest">
    <div class="relative hidden lg:flex lg:col-span-5 flex-col justify-between p-space-xl bg-primary text-on-primary overflow-hidden">
      <div class="relative z-20 flex items-center gap-space-sm">
        <div class="w-10 h-10 rounded-lg bg-surface-container-lowest text-primary flex items-center justify-center shadow-md">
          <span class="material-symbols-outlined">school</span>
        </div>
        <div class="flex flex-col">
          <span class="font-headline-sm text-headline-sm uppercase tracking-wide">Trường Đại học Công Thương TP.HCM</span>
          <span class="font-label-sm text-label-sm text-primary-fixed-dim">Phòng Công tác Sinh viên</span>
        </div>
      </div>
      <div class="relative z-20 my-space-xl space-y-space-md">
        <div class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full bg-primary-container text-on-primary-container font-label-sm text-label-sm shadow-sm">
          <span class="material-symbols-outlined text-[15px]">verified</span>
          <span>Trang nội bộ CTSV</span>
        </div>
        <h2 class="font-headline-xl text-headline-xl leading-tight">Nề nếp học đường kiến tạo giá trị tri thức</h2>
        <p class="text-primary-fixed-dim">Chuẩn hóa tác phong, xây dựng môi trường học tập nghiêm túc, văn minh.</p>
      </div>
      <p class="relative z-20 font-label-sm text-label-sm text-primary-fixed-dim">
        Hệ thống không nhận diện danh tính sinh viên; mọi đối chiếu do CTSV thực hiện.
      </p>
    </div>
    <div class="lg:col-span-7 p-space-lg sm:p-space-xl flex flex-col justify-center bg-surface-container-lowest">
      <div class="mb-space-lg">
        <div class="inline-flex items-center gap-space-xs text-secondary font-label-sm text-label-sm mb-space-xs uppercase tracking-wider">
          <span class="material-symbols-outlined text-[14px]">shield_person</span>
          <span>Phòng Công tác Sinh viên</span>
        </div>
        <h1 class="font-headline-xl text-headline-xl text-primary mb-1">Hệ thống quản lý tác phong sinh viên</h1>
        <p class="text-on-surface-variant">Đăng nhập bằng tài khoản do quản trị viên cấp.</p>
      </div>

      <?php if ($notice !== null): ?>
        <div class="mb-space-md rounded-lg bg-secondary-container px-space-md py-space-sm text-on-secondary-container" role="status"><?= cim_h($notice) ?></div>
      <?php endif; ?>
      <?php if ($error !== null): ?>
        <div class="mb-space-md rounded-lg bg-error-container px-space-md py-space-sm text-on-error-container" role="alert"><?= cim_h($error) ?></div>
      <?php endif; ?>

      <form class="space-y-space-md" method="post" action="login.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= cim_h($csrf) ?>">
        <input type="hidden" name="next" value="<?= cim_h($next) ?>">
        <label class="block space-y-1">
          <span class="font-label-md text-label-md">Tên đăng nhập</span>
          <span class="relative flex items-center">
            <span class="material-symbols-outlined absolute left-2.5 text-[18px] text-outline">person</span>
            <input class="w-full h-10 pl-9 pr-3 rounded-lg bg-surface-container-low outline-none focus:ring-2 focus:ring-primary-container"
                   name="username" autocomplete="username" maxlength="50" required autofocus value="<?= cim_h($username) ?>">
          </span>
        </label>
        <label class="block space-y-1">
          <span class="font-label-md text-label-md">Mật khẩu</span>
          <span class="relative flex items-center">
            <span class="material-symbols-outlined absolute left-2.5 text-[18px] text-outline">lock</span>
            <input class="w-full h-10 pl-9 pr-10 rounded-lg bg-surface-container-low outline-none focus:ring-2 focus:ring-primary-container"
                   id="password" name="password" type="password" autocomplete="current-password" maxlength="256" required>
            <button class="absolute right-2 p-1 text-outline hover:text-on-surface" type="button" id="toggle-password" aria-label="Hiện/ẩn mật khẩu">
              <span class="material-symbols-outlined text-[18px]">visibility</span>
            </button>
          </span>
        </label>
        <button class="w-full h-10 rounded-lg bg-primary-container text-on-primary font-headline-sm text-headline-sm flex items-center justify-center gap-space-sm shadow-md hover:bg-primary transition" type="submit">
          <span>Đăng nhập</span><span class="material-symbols-outlined text-[18px]">arrow_forward</span>
        </button>
      </form>
      <p class="mt-space-lg rounded-lg bg-surface-container-low p-space-sm font-body-sm text-body-sm text-on-surface-variant">
        Quên mật khẩu? Liên hệ quản trị viên để được đặt lại. Tài khoản tạm khóa 15 phút sau nhiều lần đăng nhập sai.
      </p>
    </div>
  </div>
</main>
<script>
  document.getElementById('toggle-password').addEventListener('click', (event) => {
    const field = document.getElementById('password');
    const icon = event.currentTarget.querySelector('span');
    const show = field.type === 'password';
    field.type = show ? 'text' : 'password';
    icon.textContent = show ? 'visibility_off' : 'visibility';
  });
</script>
</body>
</html>
