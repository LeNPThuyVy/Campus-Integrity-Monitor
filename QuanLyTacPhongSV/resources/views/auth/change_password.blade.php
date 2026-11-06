@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-space-md py-space-md pb-space-xl max-w-xl">
  <div>
    <h1 class="font-headline-xl text-headline-xl">Đổi mật khẩu</h1>
    <p class="text-on-surface-variant">Sau khi đổi, mọi phiên đăng nhập cũ sẽ bị thu hồi và bạn cần đăng nhập lại.</p>
  </div>
  <div class="rounded-lg bg-error-container px-space-md py-space-sm text-on-error-container {{ empty($error) ? 'hidden' : '' }}" id="password-error" role="alert">{{ $error ?? '' }}</div>
  <form class="flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" method="post" action="{{ route('password.change') }}" id="password-form" novalidate>
    @csrf
    <label class="flex flex-col gap-1">
      <span class="font-label-md text-label-md">Mật khẩu hiện tại</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="password" name="current_password" id="current-password" autocomplete="current-password" required maxlength="256">
    </label>
    <label class="flex flex-col gap-1">
      <span class="font-label-md text-label-md">Mật khẩu mới</span>
      <input class="h-10 rounded-lg bg-surface-container-low px-3" type="password" name="new_password" id="new-password" autocomplete="new-password" required maxlength="128">
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
@endsection

@push('scripts')
<script>
  (() => {
    const current = document.getElementById('current-password');
    const input = document.getElementById('new-password');
    const confirm = document.getElementById('confirm-password');
    const errorBox = document.getElementById('password-error');
    const bars = document.querySelectorAll('[data-bar]');
    const label = document.getElementById('strength-label');
    const colors = ['bg-error', 'bg-tertiary', 'bg-secondary', 'bg-primary-container'];
    const names = ['Yếu', 'Trung bình', 'Khá', 'Mạnh'];

    function checkRequirements(value) {
      const missing = [];
      if (value.length < 8) missing.push('ít nhất 8 ký tự');
      if (!/[A-Z]/.test(value)) missing.push('chữ in hoa');
      if (!/[a-z]/.test(value)) missing.push('chữ thường');
      if (!/[0-9]/.test(value)) missing.push('chữ số');
      return missing;
    }

    function showError(msg) {
      if (errorBox) {
        errorBox.textContent = msg;
        errorBox.classList.remove('hidden');
      }
      if (window.CIM) CIM.toast(msg, 'error');
    }

    input.addEventListener('input', () => {
      const value = input.value;
      const missing = checkRequirements(value);
      let score = 0;
      if (value.length > 0) {
        if (missing.length > 0) {
          score = 1; // Bắt buộc hiển thị 'Yếu' nếu thiếu bất kỳ điều kiện bắt buộc nào
        } else {
          score = 2;
          if (/[^A-Za-z0-9]/.test(value)) score++;
          if (value.length >= 12) score++;
        }
      }
      bars.forEach((bar, index) => {
        bar.className = 'w-1/4 ' + (index < score ? colors[score - 1] : 'bg-surface-variant');
      });
      label.textContent = value ? names[score - 1] : 'Chưa nhập';
    });

    document.getElementById('password-form').addEventListener('submit', (event) => {
      if (!current.value || !input.value || !confirm.value) {
        event.preventDefault();
        showError('Vui lòng nhập đầy đủ mật khẩu hiện tại, mật khẩu mới và xác nhận mật khẩu.');
        return;
      }
      const missing = checkRequirements(input.value);
      if (missing.length > 0) {
        event.preventDefault();
        showError('Mật khẩu mới chưa đủ độ phức tạp (cần tối thiểu 8 ký tự, gồm chữ hoa, chữ thường và chữ số). Đang thiếu: ' + missing.join(', ') + '.');
        return;
      }
      if (input.value === current.value) {
        event.preventDefault();
        showError('Mật khẩu mới phải khác mật khẩu hiện tại.');
        return;
      }
      if (input.value !== confirm.value) {
        event.preventDefault();
        showError('Mật khẩu nhập lại không khớp.');
      }
    });
  })();
</script>
@endpush
