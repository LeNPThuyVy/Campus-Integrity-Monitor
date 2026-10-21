@extends('layouts.app')

@section('content')
@php
  $input = 'h-10 rounded-lg bg-surface-container-low px-3';
@endphp
<div class="flex flex-col gap-space-md py-space-md pb-space-xl">
  <div>
    <h1 class="font-headline-xl text-headline-xl">Camera, khu vực & học kỳ</h1>
    <p class="text-on-surface-variant">
      @if ($isAdmin)
        Thêm, sửa hoặc ngừng sử dụng khu vực/camera. Khóa thiết bị chỉ hiển thị một lần khi tạo hoặc cấp lại.
      @else
        Bạn đang xem ở chế độ chỉ đọc. Thông tin nhạy cảm của camera chỉ hiển thị cho Quản trị viên.
      @endif
    </p>
  </div>

  <div class="flex gap-1 rounded-xl bg-surface-container-low p-1 self-start" role="tablist">
    @foreach (['areas' => 'Khu vực', 'cameras' => 'Camera', 'semesters' => 'Học kỳ'] as $tab => $label)
      <button class="rounded-lg px-space-md py-2 font-label-md" data-tab="{{ $tab }}" role="tab" type="button">{{ $label }}</button>
    @endforeach
  </div>

  <!-- Areas -->
  <section class="grid grid-cols-1 gap-space-md xl:grid-cols-[minmax(280px,360px)_1fr]" data-panel="areas">
    @if ($isAdmin)
    <form class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm" id="area-form">
      <h2 class="font-headline-md text-headline-md" data-title>Thêm khu vực</h2>
      <input type="hidden" name="id">
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Mã khu vực</span>
        <input class="{{ $input }}" name="code" maxlength="20" required pattern="[A-Za-z0-9_\-]+"></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Tên khu vực</span>
        <input class="{{ $input }}" name="name" maxlength="100" required></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Mô tả</span>
        <input class="{{ $input }}" name="description" maxlength="255"></label>
      <label class="flex items-center gap-2"><input type="checkbox" name="active" checked> Đang sử dụng</label>
      <div class="flex gap-space-sm">
        <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Lưu khu vực</button>
        <button class="hidden rounded-lg bg-surface-container px-space-md py-2 font-label-md" type="reset" data-cancel>Hủy sửa</button>
      </div>
    </form>
    @endif
    <div class="min-w-0 overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm {{ $isAdmin ? '' : 'xl:col-span-2' }}">
      <div class="overflow-x-auto"><table class="w-full text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline"><tr>
          <th class="px-space-md py-3">Mã</th><th class="px-space-md py-3">Tên</th><th class="px-space-md py-3">Mô tả</th>
          <th class="px-space-md py-3">Số camera</th><th class="px-space-md py-3">Trạng thái</th><th class="px-space-md py-3 text-right">Thao tác</th>
        </tr></thead>
        <tbody id="area-body"></tbody>
      </table></div>
    </div>
  </section>

  <!-- Cameras -->
  <section class="hidden grid-cols-1 gap-space-md xl:grid-cols-[minmax(280px,360px)_1fr]" data-panel="cameras">
    @if ($isAdmin)
    <form class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm" id="camera-form">
      <h2 class="font-headline-md text-headline-md" data-title>Thêm camera</h2>
      <input type="hidden" name="id">
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Mã camera</span>
        <input class="{{ $input }}" name="code" maxlength="30" required pattern="[A-Za-z0-9_\-]+"></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Tên camera</span>
        <input class="{{ $input }}" name="name" maxlength="100" required></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Khu vực</span>
        <select class="{{ $input }}" name="area_id" required></select></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Vị trí lắp đặt</span>
        <input class="{{ $input }}" name="location" maxlength="255"></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Nguồn video (RTSP/URL – thông tin nhạy cảm)</span>
        <input class="{{ $input }}" name="stream_source" maxlength="500" autocomplete="off"></label>
      <label class="flex items-center gap-2"><input type="checkbox" name="active" checked> Đang sử dụng</label>
      <div class="flex gap-space-sm">
        <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Lưu camera</button>
        <button class="hidden rounded-lg bg-surface-container px-space-md py-2 font-label-md" type="reset" data-cancel>Hủy sửa</button>
      </div>
    </form>
    @endif
    <div class="min-w-0 overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm {{ $isAdmin ? '' : 'xl:col-span-2' }}">
      <div class="overflow-x-auto"><table class="w-full min-w-[720px] text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline"><tr>
          <th class="px-space-md py-3">Mã</th><th class="px-space-md py-3">Tên / vị trí</th><th class="px-space-md py-3">Khu vực</th>
          <th class="px-space-md py-3">Kết nối gần nhất</th><th class="px-space-md py-3">Trạng thái</th><th class="px-space-md py-3 text-right">Thao tác</th>
        </tr></thead>
        <tbody id="camera-body"></tbody>
      </table></div>
    </div>
  </section>

  <!-- Semesters -->
  <section class="hidden grid-cols-1 gap-space-md xl:grid-cols-[minmax(280px,360px)_1fr]" data-panel="semesters">
    @if ($isAdmin)
    <form class="flex flex-col gap-space-sm rounded-xl bg-surface-container-lowest p-space-md shadow-sm" id="semester-form">
      <h2 class="font-headline-md text-headline-md" data-title>Thêm học kỳ</h2>
      <input type="hidden" name="id">
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Mã học kỳ</span>
        <input class="{{ $input }}" name="code" maxlength="20" required pattern="[A-Za-z0-9_\-]+" placeholder="VD: HK1-2026"></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Tên học kỳ</span>
        <input class="{{ $input }}" name="name" maxlength="100" required placeholder="Học kỳ 1 năm học 2026-2027"></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Ngày bắt đầu</span>
        <input class="{{ $input }}" type="date" name="start_date" required></label>
      <label class="flex flex-col gap-1"><span class="font-label-sm text-label-sm">Ngày kết thúc</span>
        <input class="{{ $input }}" type="date" name="end_date" required></label>
      <div class="flex gap-space-sm">
        <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" type="submit">Lưu học kỳ</button>
        <button class="hidden rounded-lg bg-surface-container px-space-md py-2 font-label-md" type="reset" data-cancel>Hủy sửa</button>
      </div>
    </form>
    @endif
    <div class="min-w-0 overflow-hidden rounded-xl bg-surface-container-lowest shadow-sm {{ $isAdmin ? '' : 'xl:col-span-2' }}">
      <div class="overflow-x-auto"><table class="w-full text-left">
        <thead class="bg-surface-container-low font-label-sm text-label-sm uppercase text-outline"><tr>
          <th class="px-space-md py-3">Mã</th><th class="px-space-md py-3">Tên</th><th class="px-space-md py-3">Bắt đầu</th>
          <th class="px-space-md py-3">Kết thúc</th><th class="px-space-md py-3 text-right">Thao tác</th>
        </tr></thead>
        <tbody id="semester-body"></tbody>
      </table></div>
    </div>
  </section>
</div>

<dialog class="w-[min(94vw,560px)] rounded-xl p-0 shadow-xl backdrop:bg-inverse-surface/50" id="key-dialog">
  <form method="dialog" class="flex flex-col gap-space-md p-space-lg">
    <h2 class="font-headline-md text-headline-md">Khóa thiết bị cho Desktop</h2>
    <p class="text-on-surface-variant">Nhập mã camera và khóa này vào phần "Kết nối API" của ứng dụng Desktop. Khóa <strong>không được hiển thị lại</strong>; nếu mất hãy cấp lại khóa mới.</p>
    <dl class="grid grid-cols-[110px_1fr] gap-2">
      <dt class="text-on-surface-variant">Mã camera</dt><dd class="font-data-mono font-semibold" id="key-camera"></dd>
      <dt class="text-on-surface-variant">Khóa thiết bị</dt><dd class="font-data-mono break-all rounded bg-surface-container-low p-2" id="key-value"></dd>
    </dl>
    <div class="flex justify-end gap-space-sm">
      <button class="rounded-lg bg-surface-container px-space-md py-2 font-label-md" type="button" id="key-copy">Sao chép khóa</button>
      <button class="rounded-lg bg-primary-container px-space-md py-2 font-label-md text-on-primary" value="close">Tôi đã lưu khóa</button>
    </div>
  </form>
</dialog>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/facilities.js') }}?v={{ time() }}" defer></script>
@endpush
