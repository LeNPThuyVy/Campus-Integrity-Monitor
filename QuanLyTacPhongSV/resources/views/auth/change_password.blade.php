@extends('layouts.app')

@section('content')
<div class="flex flex-col gap-space-md py-space-md pb-space-xl max-w-xl">
  <div>
    <h1 class="font-headline-xl text-headline-xl">Đổi mật khẩu</h1>
    <p class="text-on-surface-variant">Sau khi đổi, mọi phiên đăng nhập cũ sẽ bị thu hồi và bạn cần đăng nhập lại.</p>
  </div>
  @if (!empty($error))
    <div class="rounded-lg bg-error-container px-space-md py-space-sm text-on-error-container" role="alert">{{ $error }}</div>
  @endif
  <form class="flex flex-col gap-space-md rounded-xl bg-surface-container-lowest p-space-lg shadow-sm" method="post" action="{{ route('password.change') }}" id="password-form">
    @csrf
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
@endsection

@push('scripts')
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
@endpush
