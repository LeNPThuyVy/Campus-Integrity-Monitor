<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$cimUser = cim_require_page();
$pageTitle = 'Đổi mật khẩu';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (!cim_verify_form_csrf()) {
        $error = 'Phiên biểu mẫu đã hết hạn. Vui lòng thử lại.';
    } elseif ($new !== $confirm) {
        $error = 'Mật khẩu nhập lại không khớp.';
    } else {
        [$status, $body] = cim_api_json('POST', '/api/v1/auth/change-password', [], [
            'current_password' => $current,
            'new_password' => $new,
        ], cim_api_token());
        if ($status === 200) {
            // The API revokes every existing token: sign out and ask for the new password.
            cim_logout();
            header('Location: login.php?password_changed=1', true, 303);
            exit;
        }
        if ($status === 401) {
            cim_clear_auth();
            header('Location: login.php', true, 303);
            exit;
        }
        $error = cim_api_error_message($body, 'Không thể đổi mật khẩu.');
        if (isset($body['error']['details']) && is_array($body['error']['details'])) {
            $error .= ' ' . implode(' ', array_filter($body['error']['details'], 'is_string'));
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="flex flex-col gap-space-md py-space-md pb-space-xl max-w-xl">
  <div>
    <h1 class="font-headline-xl text-headline-xl">Đổi mật khẩu</h1>
    <p class="text-on-surface-variant">Sau khi đổi, mọi phiên đăng nhập cũ sẽ bị thu hồi và bạn cần đăng nhập lại.</p>
  </div>
  <?php if ($error !== null): ?>
    <div class="rounded-lg bg-error-container px-space-md py-space-sm text-on-error-container" role="alert"><?= cim_h($error) ?></div>
  <?php endif; ?>
  <form class="flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" method="post" id="password-form">
    <input type="hidden" name="csrf_token" value="<?= cim_h(student_csrf_token()) ?>">
    <label class="flex flex-col gap-1">
      <span class="font-label-md text-label-md">Mật khẩu hiện tại</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="password" name="current_password" autocomplete="current-password" required maxlength="256">
    </label>
    <label class="flex flex-col gap-1">
      <span class="font-label-md text-label-md">Mật khẩu mới</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="password" name="new_password" id="new-password" autocomplete="new-password" required minlength="8" maxlength="128">
      <span class="font-label-sm text-label-sm text-on-surface-variant">Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường và chữ số.</span>
      <span class="flex h-1.5 gap-1 overflow-hidden rounded-full" aria-hidden="true">
        <span class="w-1/4 bg-surface-variant" data-bar></span><span class="w-1/4 bg-surface-variant" data-bar></span>
        <span class="w-1/4 bg-surface-variant" data-bar></span><span class="w-1/4 bg-surface-variant" data-bar></span>
      </span>
      <span class="font-label-sm text-label-sm" id="strength-label">Chưa nhập</span>
    </label>
    <label class="flex flex-col gap-1">
      <span class="font-label-md text-label-md">Nhập lại mật khẩu mới</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="password" name="confirm_password" id="confirm-password" autocomplete="new-password" required maxlength="128">
    </label>
    <button class="self-start rounded-lg bg-primary-container px-space-lg py-2 font-label-md text-on-primary" type="submit">Đổi mật khẩu</button>
  </form>
</div>
<script>
  (() => {
    const input = document.getElementById('new-password');
    const confirm = document.getElementById('confirm-password');
    const bars = document.querySelectorAll('[data-bar]');
    const label = document.getElementById('strength-label');
    const colors = ['bg-error', 'bg-tertiary', 'bg-secondary', 'bg-primary-container'];
    const names = ['Yếu', 'Trung bình', 'Khá', 'Mạnh'];
    input.addEventListener('input', () => {
      const value = input.value;
      let score = 0;
      if (value.length >= 8) score++;
      if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
      if (/[0-9]/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value) || value.length >= 12) score++;
      bars.forEach((bar, index) => { bar.className = 'w-1/4 ' + (index < score ? colors[score - 1] : 'bg-surface-variant'); });
      label.textContent = value ? names[Math.max(score, 1) - 1] : 'Chưa nhập';
    });
    document.getElementById('password-form').addEventListener('submit', (event) => {
      if (input.value !== confirm.value) {
        event.preventDefault();
        CIM.toast('Mật khẩu nhập lại không khớp.', 'error');
      }
    });
  })();
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
