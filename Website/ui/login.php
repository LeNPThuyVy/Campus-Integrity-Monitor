<!DOCTYPE html><html lang="vi" style="width: 1280px; height: 1057px; overflow: hidden; position: relative;"><head><meta charset="utf-8"><meta content="width=device-width, initial-scale=1.0" name="viewport"><meta content="web_blank" name="shell-type"><link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&amp;display=swap" rel="stylesheet"><link rel="stylesheet" href="assets/css/style.css"><script src="https://cdn.tailwindcss.com"></script><script src="assets/js/tailwind.config.js"></script></head><body class="bg-surface font-body-md text-body-md text-on-surface antialiased"><main class="w-full min-h-screen bg-surface flex items-center justify-center p-space-md"><div class="flex flex-col w-full">
<div class="relative w-full min-h-screen flex items-center justify-center p-space-md overflow-hidden">
<div class="absolute inset-0 -z-10 bg-gradient-to-br from-surface via-surface-container-low to-surface-container-high opacity-80"></div>
<div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-primary-fixed opacity-40 blur-3xl pointer-events-none"></div>
<div class="absolute -bottom-24 -left-24 w-80 h-80 rounded-full bg-secondary-fixed opacity-30 blur-2xl pointer-events-none"></div>
<div class="w-full max-w-[1140px] grid grid-cols-1 lg:grid-cols-12 rounded-xl shadow-xl overflow-hidden bg-surface-container-lowest">
<div class="relative hidden lg:flex lg:col-span-5 flex-col justify-between p-space-xl bg-primary text-on-primary overflow-hidden">
<div class="absolute inset-0 bg-gradient-to-t from-primary via-primary/95 to-primary/80 z-10"></div>

<div class="relative z-20 flex items-center gap-space-sm">
<div class="w-10 h-10 rounded-lg bg-surface-container-lowest text-primary flex items-center justify-center shadow-md">

</div>
<div class="flex flex-col">
<span class="font-headline-sm text-headline-sm uppercase tracking-wide text-on-primary">trường Đại Học CÔNG THƯƠNG TPHCM</span>
<span class="font-label-sm text-label-sm text-primary-fixed-dim">Phòng Công Tác Sinh Viên</span>
</div>
</div>
<div class="relative z-20 my-space-xl space-y-space-md">
<div class="inline-flex items-center gap-space-xs px-space-sm py-1 rounded-full bg-primary-container text-on-primary-container font-label-sm text-label-sm shadow-sm">
<span class="material-symbols-outlined text-[15px]" style="font-variation-settings: 'FILL' 1;">verified</span>
<span class="">Trang Nội Bộ CTSV</span>
</div>
<h2 class="font-headline-xl text-headline-xl text-on-primary leading-tight">
            Nề nếp học đường kiến tạo giá trị tri thức
          </h2>
<p class="font-body-md text-body-md text-primary-fixed-dim">
            Chuẩn hóa tác phong, xây dựng môi trường học tập nghiêm túc, văn minh</p>

</div>
<div class="relative z-20 flex items-center justify-between text-primary-fixed-dim font-label-sm text-label-sm">


</div>
</div>
<div class="lg:col-span-7 p-space-lg sm:p-space-xl flex flex-col justify-between bg-surface-container-lowest">
<div>
<div class="flex items-center justify-between pb-space-lg">
<div class="flex items-center gap-space-sm lg:hidden">
<div class="w-9 h-9 rounded bg-primary-container text-on-primary flex items-center justify-center">
<span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">school</span>
</div>
<span class="font-headline-sm text-headline-sm text-primary-container">CTSV Portal</span>
</div>
<div class="hidden lg:block"></div>
<button class="inline-flex items-center gap-space-xs text-primary font-label-md text-label-md hover:underline cursor-pointer transition" onclick="openModal()" type="button">
<span class="material-symbols-outlined text-[16px]">lock_reset</span>
<span class="">Đổi mật khẩu tài khoản</span>
</button>
</div>
<div class="mb-space-lg">
<div class="inline-flex items-center gap-space-xs text-secondary font-label-sm text-label-sm mb-space-xs uppercase tracking-wider">
<span class="material-symbols-outlined text-[14px]">shield_person</span>
<span class="">Phòng Công tác Sinh viên</span>
</div>
<h1 class="font-headline-xl text-headline-xl text-on-surface text-primary mb-1">
              HỆ THỐNG QUẢN LÝ TÁC PHONG SINH VIÊN
            </h1>
<p class="font-body-md text-body-md text-on-surface-variant">
              Giám sát &amp; Quản lý tác phong sinh viên</p>
</div>
<form class="space-y-space-md" id="loginForm" onsubmit="handleLogin(event)">
<div class="space-y-space-xs">
<label class="block font-label-md text-label-md text-on-surface" for="username">
                Tên đăng nhập / Mã cán bộ <span class="text-error">*</span>
</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-3 text-outline text-[18px] pointer-events-none">account_circle</span>
<input class="w-full h-9 pl-9 pr-3 rounded-lg bg-surface-container-lowest text-on-surface font-body-md text-body-md placeholder-outline focus:bg-surface-container-lowest focus:shadow-md transition outline-none shadow-sm" id="username" placeholder="VD: admin_ctsv hoặc CB0124" required="" type="text">
</div>
</div>
<div class="space-y-space-xs">
<div class="flex items-center justify-between">
<label class="block font-label-md text-label-md text-on-surface" for="password">
                  Mật khẩu truy cập <span class="text-error">*</span>
</label>
<a class="font-label-sm text-label-sm text-primary-container hover:underline" href="javascript:void(0)" onclick="alert('Vui lòng liên hệ Quản trị viên Trung tâm CNTT hoặc Phòng CTSV (ext: 104) để khôi phục mật khẩu cán bộ.')">
                  Quên mật khẩu?
                </a>
</div>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-3 text-outline text-[18px] pointer-events-none">lock</span>
<input class="w-full h-9 pl-9 pr-10 rounded-lg bg-surface-container-lowest text-on-surface font-body-md text-body-md placeholder-outline focus:bg-surface-container-lowest focus:shadow-md transition outline-none shadow-sm" id="password" placeholder="Nhập mật khẩu của bạn" required="" type="password">
<button aria-label="Ẩn hiện mật khẩu" class="absolute right-2.5 p-1 text-outline hover:text-on-surface cursor-pointer flex items-center" onclick="togglePasswordVisibility('password', 'passwordToggleIcon')" type="button">
<span class="material-symbols-outlined text-[18px]" id="passwordToggleIcon">visibility</span>
</button>
</div>
</div>
<div class="flex items-center justify-between py-1">
<label class="flex items-center gap-space-sm cursor-pointer select-none">
<input class="w-4 h-4 accent-primary rounded cursor-pointer" id="rememberMe" type="checkbox">
<span class="font-body-md text-body-md text-on-surface-variant">Duy trì đăng nhập</span>
</label>

</div>
<button class="w-full h-10 mt-space-sm rounded-lg bg-primary-container text-on-primary font-headline-sm text-headline-sm flex items-center justify-center gap-space-sm shadow-md hover:bg-primary transition active:scale-[0.99] cursor-pointer" type="submit">
<span class="">Đăng nhập vào hệ thống</span>
<span class="material-symbols-outlined text-[18px]">arrow_forward</span>
</button>
</form>
<div class="mt-space-lg p-space-sm rounded-lg bg-surface-container-low flex items-start gap-space-sm">
<span class="material-symbols-outlined text-primary text-[20px] mt-0.5" style="font-variation-settings: 'FILL' 1;">security</span>
<div class="text-on-surface-variant font-body-sm text-body-sm leading-relaxed">
<span class="font-headline-sm text-on-surface">Lưu ý an toàn nghiệp vụ:</span> Hệ thống chỉ dành cho cán bộ, thanh tra và ban quản lý tác phong được ủy quyền. Mọi thao tác đều được hệ thống ghi nhận IP và kiểm toán tự động.
            </div>
</div>
</div>

</div>
</div>
</div>
<div class="fixed inset-0 z-50 hidden flex items-center justify-center p-space-md bg-inverse-surface/60 backdrop-blur-sm transition-opacity duration-300" id="changePasswordModal">
<div class="w-full max-w-[500px] bg-surface-container-lowest rounded-xl shadow-xl overflow-hidden transform transition-all duration-300">
<div class="p-space-lg bg-surface-container-low flex items-center justify-between">
<div class="flex items-center gap-space-sm">
<div class="w-8 h-8 rounded-full bg-primary-container text-on-primary flex items-center justify-center">
<span class="material-symbols-outlined text-[18px]">password</span>
</div>
<div>
<h3 class="font-headline-md text-headline-md text-on-surface">Đổi mật khẩu tài khoản</h3>
<p class="font-body-sm text-body-sm text-on-surface-variant">Phòng Công tác Sinh viên • Quản trị cán bộ</p>
</div>
</div>
<button class="w-8 h-8 rounded-full hover:bg-surface-container-high flex items-center justify-center text-outline hover:text-on-surface cursor-pointer transition" onclick="closeModal()" type="button">
<span class="material-symbols-outlined text-[20px]">close</span>
</button>
</div>
<div class="p-space-lg space-y-space-md">
<div class="p-space-sm rounded-lg bg-surface-container text-on-surface-variant font-body-sm text-body-sm flex gap-space-sm items-start">
<span class="material-symbols-outlined text-secondary text-[18px] mt-0.5">info</span>
<div class="">
<span class="font-headline-sm text-on-surface">Quy tắc bảo mật cán bộ:</span> Mật khẩu bắt buộc có tối thiểu 8 ký tự, bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 chữ số.
          </div>
</div>
<form class="space-y-space-md" id="changePasswordForm" onsubmit="handleChangePassword(event)">
<div class="space-y-space-xs">
<label class="block font-label-md text-label-md text-on-surface" for="currentPassword">
              Mật khẩu hiện tại <span class="text-error">*</span>
</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-3 text-outline text-[18px] pointer-events-none">key</span>
<input class="w-full h-9 pl-9 pr-10 rounded-lg bg-surface text-on-surface font-body-md text-body-md placeholder-outline focus:bg-surface-container-lowest focus:shadow-md transition outline-none shadow-sm" id="currentPassword" placeholder="Nhập mật khẩu đang dùng" required="" type="password">
<button class="absolute right-2.5 p-1 text-outline hover:text-on-surface cursor-pointer flex items-center" onclick="togglePasswordVisibility('currentPassword', 'currPassIcon')" type="button">
<span class="material-symbols-outlined text-[18px]" id="currPassIcon">visibility</span>
</button>
</div>
</div>
<div class="space-y-space-xs">
<label class="block font-label-md text-label-md text-on-surface" for="newPassword">
              Mật khẩu mới <span class="text-error">*</span>
</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-3 text-outline text-[18px] pointer-events-none">lock_open</span>
<input class="w-full h-9 pl-9 pr-10 rounded-lg bg-surface text-on-surface font-body-md text-body-md placeholder-outline focus:bg-surface-container-lowest focus:shadow-md transition outline-none shadow-sm" id="newPassword" oninput="evaluatePasswordStrength(this.value)" placeholder="Tối thiểu 8 ký tự, gồm số và hoa" required="" type="password">
<button class="absolute right-2.5 p-1 text-outline hover:text-on-surface cursor-pointer flex items-center" onclick="togglePasswordVisibility('newPassword', 'newPassIcon')" type="button">
<span class="material-symbols-outlined text-[18px]" id="newPassIcon">visibility</span>
</button>
</div>
<div class="pt-1.5 space-y-1">
<div class="flex justify-between items-center text-label-sm font-label-sm">
<span class="text-on-surface-variant">Độ mạnh mật khẩu:</span>
<span class="text-outline font-data-mono" id="strengthLabel">Chưa nhập</span>
</div>
<div class="w-full h-1.5 bg-surface-container rounded-full overflow-hidden flex gap-1">
<div class="h-full w-1/4 bg-surface-variant transition-colors duration-300" id="bar1"></div>
<div class="h-full w-1/4 bg-surface-variant transition-colors duration-300" id="bar2"></div>
<div class="h-full w-1/4 bg-surface-variant transition-colors duration-300" id="bar3"></div>
<div class="h-full w-1/4 bg-surface-variant transition-colors duration-300" id="bar4"></div>
</div>
</div>
</div>
<div class="space-y-space-xs">
<label class="block font-label-md text-label-md text-on-surface" for="confirmPassword">
              Xác nhận mật khẩu mới <span class="text-error">*</span>
</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-3 text-outline text-[18px] pointer-events-none">verified_user</span>
<input class="w-full h-9 pl-9 pr-10 rounded-lg bg-surface text-on-surface font-body-md text-body-md placeholder-outline focus:bg-surface-container-lowest focus:shadow-md transition outline-none shadow-sm" id="confirmPassword" placeholder="Nhập lại mật khẩu mới" required="" type="password">
<button class="absolute right-2.5 p-1 text-outline hover:text-on-surface cursor-pointer flex items-center" onclick="togglePasswordVisibility('confirmPassword', 'confPassIcon')" type="button">
<span class="material-symbols-outlined text-[18px]" id="confPassIcon">visibility</span>
</button>
</div>
</div>
<div class="flex items-center justify-end gap-space-sm pt-space-md">
<button class="px-space-md h-9 rounded-lg bg-surface-container text-on-surface-variant hover:bg-surface-container-high font-headline-sm text-headline-sm transition cursor-pointer" onclick="closeModal()" type="button">
              Hủy bỏ
            </button>
<button class="px-space-md h-9 rounded-lg bg-primary-container text-on-primary font-headline-sm text-headline-sm hover:bg-primary transition shadow-md cursor-pointer flex items-center gap-space-xs" type="submit">
<span class="material-symbols-outlined text-[16px]">save</span>
<span class="">Cập nhật mật khẩu mới</span>
</button>
</div>
</form>
</div>
</div>
</div>
<div class="fixed bottom-5 right-5 z-50 hidden transform transition-all duration-300 translate-y-3 opacity-0" id="toastNotification">
<div class="flex items-center gap-space-sm px-space-md py-space-sm rounded-lg shadow-xl text-on-primary font-headline-sm text-headline-sm bg-primary" id="toastContent">
<span class="material-symbols-outlined text-[20px]" id="toastIcon">check_circle</span>
<span id="toastMessage" class="">Thông báo tác vụ</span>
</div>
</div>
</div>
<script>
  function togglePasswordVisibility(fieldId, iconId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(iconId);
    if (!field || !icon) return;
    
    if (field.type === 'password') {
      field.type = 'text';
      icon.textContent = 'visibility_off';
    } else {
      field.type = 'password';
      icon.textContent = 'visibility';
    }
  }

  function openModal() {
    const modal = document.getElementById('changePasswordModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    setTimeout(() => {
      modal.classList.add('opacity-100');
    }, 10);
  }

  function closeModal() {
    const modal = document.getElementById('changePasswordModal');
    if (!modal) return;
    modal.classList.remove('opacity-100');
    setTimeout(() => {
      modal.classList.add('hidden');
      document.getElementById('changePasswordForm').reset();
      evaluatePasswordStrength('');
    }, 200);
  }

  function evaluatePasswordStrength(val) {
    const bar1 = document.getElementById('bar1');
    const bar2 = document.getElementById('bar2');
    const bar3 = document.getElementById('bar3');
    const bar4 = document.getElementById('bar4');
    const label = document.getElementById('strengthLabel');

    const resetBars = () => {
      [bar1, bar2, bar3, bar4].forEach(b => {
        b.className = 'h-full w-1/4 bg-surface-variant transition-colors duration-300';
      });
    };

    if (!val || val.length === 0) {
      resetBars();
      label.textContent = 'Chưa nhập';
      label.className = 'text-outline font-data-mono';
      return;
    }

    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    resetBars();

    if (score === 1) {
      bar1.className = 'h-full w-1/4 bg-error transition-colors duration-300';
      label.textContent = 'Yếu';
      label.className = 'text-error font-data-mono font-bold';
    } else if (score === 2) {
      bar1.className = 'h-full w-1/4 bg-tertiary transition-colors duration-300';
      bar2.className = 'h-full w-1/4 bg-tertiary transition-colors duration-300';
      label.textContent = 'Trung bình';
      label.className = 'text-tertiary font-data-mono font-bold';
    } else if (score === 3) {
      bar1.className = 'h-full w-1/4 bg-secondary transition-colors duration-300';
      bar2.className = 'h-full w-1/4 bg-secondary transition-colors duration-300';
      bar3.className = 'h-full w-1/4 bg-secondary transition-colors duration-300';
      label.textContent = 'Khá mạnh';
      label.className = 'text-secondary font-data-mono font-bold';
    } else if (score >= 4) {
      [bar1, bar2, bar3, bar4].forEach(b => {
        b.className = 'h-full w-1/4 bg-primary-container transition-colors duration-300';
      });
      label.textContent = 'Rất mạnh (Tối ưu)';
      label.className = 'text-primary-container font-data-mono font-bold';
    }
  }

  function showToast(msg, isSuccess = true) {
    const toast = document.getElementById('toastNotification');
    const toastMessage = document.getElementById('toastMessage');
    const toastContent = document.getElementById('toastContent');
    const toastIcon = document.getElementById('toastIcon');

    if (!toast || !toastMessage || !toastContent || !toastIcon) return;

    toastMessage.textContent = msg;
    if (isSuccess) {
      toastContent.className = 'flex items-center gap-space-sm px-space-md py-space-sm rounded-lg shadow-xl text-on-primary font-headline-sm text-headline-sm bg-primary-container';
      toastIcon.textContent = 'check_circle';
    } else {
      toastContent.className = 'flex items-center gap-space-sm px-space-md py-space-sm rounded-lg shadow-xl text-on-error font-headline-sm text-headline-sm bg-error';
      toastIcon.textContent = 'error';
    }

    toast.classList.remove('hidden');
    setTimeout(() => {
      toast.classList.remove('translate-y-3', 'opacity-0');
      toast.classList.add('translate-y-0', 'opacity-100');
    }, 10);

    setTimeout(() => {
      toast.classList.remove('translate-y-0', 'opacity-100');
      toast.classList.add('translate-y-3', 'opacity-0');
      setTimeout(() => toast.classList.add('hidden'), 300);
    }, 3500);
  }

  function handleLogin(e) {
    e.preventDefault();
    const user = document.getElementById('username').value.trim();
    const pass = document.getElementById('password').value;

    if (!user || !pass) {
      showToast('Vui lòng điền đầy đủ tên đăng nhập và mật khẩu', false);
      return;
    }

    showToast('Xác thực cán bộ thành công! Đang chuyển hướng vào hệ thống...');
  }

  function handleChangePassword(e) {
    e.preventDefault();
    const curr = document.getElementById('currentPassword').value;
    const newPass = document.getElementById('newPassword').value;
    const confPass = document.getElementById('confirmPassword').value;

    if (newPass.length < 8 || !/[A-Z]/.test(newPass) || !/[0-9]/.test(newPass)) {
      showToast('Mật khẩu mới chưa thỏa điều kiện tối thiểu 8 ký tự, có chữ hoa và số!', false);
      return;
    }

    if (newPass !== confPass) {
      showToast('Xác nhận mật khẩu không khớp. Vui lòng kiểm tra lại!', false);
      return;
    }

    if (curr === newPass) {
      showToast('Mật khẩu mới không được trùng với mật khẩu hiện tại!', false);
      return;
    }

    closeModal();
    showToast('Đã đổi mật khẩu thành công! Vui lòng đăng nhập lại bằng mật khẩu mới.');
  }

  document.addEventListener('keydown', function(evt) {
    if (evt.key === 'Escape') {
      closeModal();
    }
  });
</script></main>

</body></html>