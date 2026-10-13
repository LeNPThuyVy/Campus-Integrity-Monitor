<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
$cimUser = cim_require_page(['ADMIN']);
$pageTitle = 'Tài khoản & phân quyền';
include __DIR__ . '/includes/header.php';
$input = 'h-10 rounded-lg bg-surface-container-low px-3';
?>
<div class="flex flex-col gap-space-md py-space-md pb-space-xl">
  <div>
    <h1 class="font-headline-xl text-headline-xl">Tài khoản & phân quyền</h1>
    <p class="text-on-surface-variant">Hai vai trò: <strong>Quản trị viên</strong> (toàn quyền, quản lý tài khoản, camera, loại vi phạm) và <strong>Nhân viên CTSV</strong> (sinh viên, danh mục, tra cứu/xử lý vi phạm, báo cáo).</p>
  </div>

  <section class="grid grid-cols-1 gap-space-md xl:grid-cols-[minmax(280px,360px)_1fr]">
    <form class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm" id="account-form">
      <h2 class="font-headline-md text-headline-md" id="account-form-title">Tạo tài khoản</h2>
      <input type="hidden" name="id">
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Tên đăng nhập</span>
        <input class="<?= $input ?>" name="username" minlength="3" maxlength="50" required pattern="[A-Za-z0-9_.\-]+" autocomplete="off"></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Họ tên</span>
        <input class="<?= $input ?>" name="full_name" maxlength="100" required></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Vai trò</span>
        <select class="<?= $input ?>" name="role"><option value="CTSV">Nhân viên CTSV</option><option value="ADMIN">Quản trị viên</option></select></label>
      <label class="flex flex-col gap-1" data-create-only><span class="font-label-sm text-label-sm">Mật khẩu ban đầu</span>
        <input class="<?= $input ?>" type="password" name="password" minlength="8" maxlength="128" autocomplete="new-password">
        <span class="font-label-sm text-label-sm text-on-surface-variant">Tối thiểu 8 ký tự, có chữ hoa, chữ thường và chữ số.</span></label>
      <label class="hidden items-center gap-2" data-edit-only><input type="checkbox" name="active" checked> Đang hoạt động</label>
      <div class="flex gap-space-sm">
        <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Lưu tài khoản</button>
        <button class="hidden rounded-lg bg-surface-container px-space-md py-2 font-label-md" type="reset" id="account-cancel">Hủy sửa</button>
      </div>
    </form>
    <div class="min-w-0 overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm">
      <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline"><tr>
          <th class="px-space-md py-3">Tài khoản</th><th class="px-space-md py-3">Vai trò</th><th class="px-space-md py-3">Trạng thái</th>
          <th class="px-space-md py-3">Đăng nhập gần nhất</th><th class="px-space-md py-3 text-right">Thao tác</th>
        </tr></thead>
        <tbody id="account-body"></tbody>
      </table></div>
    </div>
  </section>
</div>

<dialog class="w-[min(94vw,440px)] rounded-xl p-0 shadow-xl backdrop:bg-inverse-surface/50" id="reset-dialog">
  <form class="flex flex-col gap-space-md p-space-lg" id="reset-form">
    <h2 class="font-headline-md text-headline-md">Đặt lại mật khẩu</h2>
    <p class="text-on-surface-variant">Tài khoản: <strong id="reset-username"></strong>. Các phiên đăng nhập hiện tại của tài khoản sẽ bị thu hồi.</p>
    <input class="<?= $input ?>" type="password" name="new_password" minlength="8" maxlength="128" required autocomplete="new-password" placeholder="Mật khẩu mới">
    <div class="flex justify-end gap-space-sm">
      <button class="rounded-lg bg-surface-container px-space-md py-2 font-label-md" type="button" id="reset-cancel">Hủy</button>
      <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Đặt lại</button>
    </div>
  </form>
</dialog>
<script src="assets/js/accounts.js" defer></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
